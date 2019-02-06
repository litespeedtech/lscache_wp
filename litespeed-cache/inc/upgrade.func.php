<?php
defined( 'WPINC' ) || exit ;
/**
 * Database upgrade funcs
 *
 * @since  3.0
 */

/**
 * For version under v2.0 to v2.0+
 *
 * @since  3.0
 */
function litespeed_update_200(){
	$ver = get_option(  )

	/**
	 * Convert old data from postmeta to img_optm table
	 * @since  2.0
	 */
	if ( ! $ver || version_compare( $ver, '2.0', '<' ) ) {
		// Migrate data from `wp_postmeta` to `wp_litespeed_img_optm`
		$mids_to_del = array() ;
		$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_id" ;
		$meta_value_list = $wpdb->get_results( $wpdb->prepare( $q, array( LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_DATA ) ) ) ;
		if ( $meta_value_list ) {
			$max_k = count( $meta_value_list ) - 1 ;
			foreach ( $meta_value_list as $k => $v ) {
				$md52src_list = unserialize( $v->meta_value ) ;
				foreach ( $md52src_list as $md5 => $v2 ) {
					$f = array(
						'post_id'	=> $v->post_id,
						'optm_status'		=> $v2[ 1 ],
						'src'		=> $v2[ 0 ],
						'srcpath_md5'		=> md5( $v2[ 0 ] ),
						'src_md5'		=> $md5,
						'server'		=> $v2[ 2 ],
					) ;
					$wpdb->replace( $this->_tb_img_optm, $f ) ;
				}
				$mids_to_del[] = $v->meta_id ;

				// Delete from postmeta
				if ( count( $mids_to_del ) > 100 || $k == $max_k ) {
					$q = "DELETE FROM $wpdb->postmeta WHERE meta_id IN ( " . implode( ',', array_fill( 0, count( $mids_to_del ), '%s' ) ) . " ) " ;
					$wpdb->query( $wpdb->prepare( $q, $mids_to_del ) ) ;

					$mids_to_del = array() ;
				}
			}

			LiteSpeed_Cache_Log::debug( '[Data] img_optm inserted records: ' . $k ) ;
		}

		$q = "DELETE FROM $wpdb->postmeta WHERE meta_key = %s" ;
		$rows = $wpdb->query( $wpdb->prepare( $q, LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS ) ) ;
		LiteSpeed_Cache_Log::debug( '[Data] img_optm delete optm_status records: ' . $rows ) ;
	}

}