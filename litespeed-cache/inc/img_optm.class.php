<?php
/**
 * The class to optimize image.
 *
 * @since 		2.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Img_Optm
{
	private static $_instance ;

	const TYPE_SYNC_DATA = 'sync_data' ;
	const TYPE_IMG_OPTIMIZE = 'img_optm' ;
	const TYPE_IMG_OPTIMIZE_RESCAN = 'img_optm_rescan' ;
	const TYPE_IMG_OPTM_DESTROY = 'img_optm_destroy' ;
	const TYPE_IMG_OPTM_DESTROY_UNFINISHED = 'img_optm_destroy-unfinished' ;
	const TYPE_IMG_PULL = 'img_pull' ;
	const TYPE_IMG_BATCH_SWITCH_ORI = 'img_optm_batch_switch_ori' ;
	const TYPE_IMG_BATCH_SWITCH_OPTM = 'img_optm_batch_switch_optm' ;
	const TYPE_CALC_BKUP = 'calc_bkup' ;
	const TYPE_RESET_ROW = 'reset_row' ;
	const TYPE_RM_BKUP = 'rm_bkup' ;

	const ITEM_IMG_OPTM_CRON_RUN = 'litespeed-img_optm_cron_run' ; // last cron running time

	const DB_IMG_OPTIMIZE_DESTROY = 'litespeed-optimize-destroy' ;
	const DB_IMG_OPTIMIZE_DATA = 'litespeed-optimize-data' ;
	const DB_IMG_OPTIMIZE_STATUS = 'litespeed-optimize-status' ;
	const DB_IMG_OPTIMIZE_STATUS_PREPARE = 'prepare' ;
	const DB_IMG_OPTIMIZE_STATUS_REQUESTED = 'requested' ;
	const DB_IMG_OPTIMIZE_STATUS_NOTIFIED = 'notified' ;
	const DB_IMG_OPTIMIZE_STATUS_PULLED = 'pulled' ;
	const DB_IMG_OPTIMIZE_STATUS_FAILED = 'failed' ;
	const DB_IMG_OPTIMIZE_STATUS_MISS = 'miss' ;
	const DB_IMG_OPTIMIZE_STATUS_ERR = 'err' ;
	const DB_IMG_OPTIMIZE_STATUS_ERR_FETCH = 'err_fetch' ;
	const DB_IMG_OPTIMIZE_STATUS_ERR_OPTM = 'err_optm' ;
	const DB_IMG_OPTIMIZE_STATUS_XMETA = 'xmeta' ;
	const DB_IMG_OPTIMIZE_SIZE = 'litespeed-optimize-size' ;

	const DB_IMG_OPTM_SUMMARY = 'litespeed_img_optm_summary' ;
	const DB_IMG_OPTM_BK_SUMMARY = 'litespeed_img_optm_bk_summary' ;
	const DB_IMG_OPTM_RMBK_SUMMARY = 'litespeed_img_optm_rmbk_summary' ;

	const NUM_THRESHOLD_AUTO_REQUEST = 1200 ;

	private $wp_upload_dir ;
	private $tmp_pid ;
	private $tmp_path ;
	private $_img_in_queue = array() ;
	private $_img_duplicated_in_queue = array() ;
	private $_missed_img_in_queue = array() ;
	private $_img_srcpath_md5_array = array() ;
	private $_img_total = 0 ;
	private $_table_img_optm ;
	private $_cron_ran = false ;

	private $__media ;

	/**
	 * Init
	 *
	 * @since  2.0
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( 'ImgOptm init' ) ;

		$this->wp_upload_dir = wp_upload_dir() ;
		$this->__media = LiteSpeed_Cache_Media::get_instance() ;
		$this->_table_img_optm = LiteSpeed_Cache_Data::get_tb_img_optm() ;
	}

	/**
	 * Sync data from litespeed IAPI server for CLI usage
	 *
	 * @since  2.4.4
	 * @access public
	 */
	public function sync_data()
	{
		return $this->_sync_data( true ) ;
	}

	/**
	 * Sync data from litespeed IAPI server
	 *
	 * @since  1.6.5
	 * @access private
	 */
	private function _sync_data( $try_level_up = false )
	{
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_MEDIA_SYNC_DATA, false, true ) ;

		if ( ! is_array( $json ) ) {
			return ;
		}

		if ( ! empty( $json ) ) {
			update_option( self::DB_IMG_OPTM_SUMMARY, $json ) ;
		}

		// If this is for level up try, return data directly
		if ( $try_level_up ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Try Level Up ~ !' ) ;
			return $json ;
		}

		$msg = __( 'Communicated with LiteSpeed Image Optimization Server successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		if ( ! defined( 'LITESPEED_CLI' ) ) {
			LiteSpeed_Cache_Admin::redirect() ;
		}
	}

	/**
	 * Request optm to litespeed IAPI server for CLI usage
	 *
	 * @since  2.4.4
	 * @access public
	 */
	public function request_optm()
	{
		return $this->_request_optm() ;
	}

	/**
	 * Push raw img to LiteSpeed IAPI server
	 *
	 * @since 1.6
	 * @access private
	 */
	private function _request_optm()
	{
		global $wpdb ;

		$_credit = (int) $this->summary_info( 'credit' ) ;
		$credit_recovered = (int) $this->summary_info( 'credit_recovered' ) ;


		LiteSpeed_Cache_Log::debug( '[Img_Optm] preparing images to push' ) ;

		// Get images
		$q = "SELECT b.post_id, b.meta_value
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			LEFT JOIN $this->_table_img_optm c ON c.post_id = a.ID
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
				AND c.id IS NULL
			ORDER BY a.ID DESC
			LIMIT %d
			" ;
		$q = $wpdb->prepare( $q, apply_filters( 'litespeed_img_optimize_max_rows', 3000 ) ) ;

		$img_set = array() ;
		$list = $wpdb->get_results( $q ) ;
		if ( ! $list ) {
			$msg = __( 'No image found.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

			LiteSpeed_Cache_Log::debug( '[Img_Optm] optimize bypass: no image found' ) ;
			return $msg ;
		}

		LiteSpeed_Cache_Log::debug( '[Img_Optm] found images: ' . count( $list ) ) ;

		foreach ( $list as $v ) {

			$meta_value = $this->_parse_wp_meta_value( $v ) ;
			if ( ! $meta_value ) {
				$this->_mark_wrong_meta_src( $v->post_id ) ;
				continue ;
			}

			/**
			 * Only send 500 images one time
			 * @since 1.6.3
			 * @since 1.6.5 use credit limit
			 */
			$num_will_incease = 1 ;
			if ( ! empty( $meta_value[ 'sizes' ] ) ) {
				$num_will_incease += count( $meta_value[ 'sizes' ] ) ;
			}
			if ( $this->_img_total + $num_will_incease > $_credit ) {
				if ( ! $this->_img_total ) {
					$msg = sprintf( __( 'Number of images in one image group (%s) exceeds the credit (%s)', 'litespeed-cache' ), $num_will_incease, $_credit ) ;
					LiteSpeed_Cache_Admin_Display::error( $msg ) ;
				}
				LiteSpeed_Cache_Log::debug( '[Img_Optm] img request hit limit: [total] ' . $this->_img_total . " \t[add] $num_will_incease \t[credit] $_credit" ) ;
				break ;
			}
			/**
			 * Check if need to test run ( new user only allow 1 group at first time)
			 * @since 1.6.6.1
			 */
			if ( $this->_img_total && ! $credit_recovered ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] test run only allow 1 group ' ) ;
				break ;
			}

			// push orig image to queue
			$this->tmp_pid = $v->post_id ;
			$this->tmp_path = pathinfo( $meta_value[ 'file' ], PATHINFO_DIRNAME ) . '/' ;
			$this->_img_queue( $meta_value, true ) ;
			if ( ! empty( $meta_value[ 'sizes' ] ) ) {
				array_map( array( $this, '_img_queue' ), $meta_value[ 'sizes' ] ) ;
			}
		}

		// Save missed images into img_optm
		$this->_save_missed_into_img_optm() ;

		if ( empty( $this->_img_in_queue ) ) {
			$msg = __( 'Requested successfully.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

			LiteSpeed_Cache_Log::debug( '[Img_Optm] optimize bypass: empty _img_in_queue' ) ;
			return array( 'ok' => $msg ) ;
		}

		// Filtered from existing data
		$this->_filter_existing_src() ;

		/**
		 * Filter same src in $this->_img_in_queue
		 *
		 * 1. Save them to tmp array $this->_img_duplicated_in_queue
		 * 2. Remove them from $this->_img_in_queue
		 * 3. After inserted $this->_img_in_queue into img_optm, insert $this->_img_duplicated_in_queue into img_optm with root_id
		 */
		$this->_filter_duplicated_src() ;

		if ( empty( $this->_img_in_queue ) ) {
			$msg = __( 'Optimized successfully.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
			return array( 'ok' => $msg ) ;
		}

		$total_groups = count( $this->_img_in_queue ) ;
		LiteSpeed_Cache_Log::debug( '[Img_Optm] prepared images to push: groups ' . $total_groups . ' images ' . $this->_img_total ) ;

		// Push to LiteSpeed IAPI server
		$json = $this->_push_img_in_queue_to_iapi() ;
		if ( ! is_array( $json ) ) {
			return $json ;
		}
		$pids = $json[ 'pids' ] ;

		$data_to_add = array() ;
		foreach ( $pids as $pid ) {
			foreach ( $this->_img_in_queue[ $pid ] as $md5 => $src_data ) {
				$data_to_add[] = $pid ;
				$data_to_add[] = self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ;
				$data_to_add[] = $src_data[ 'src' ] ;
				$data_to_add[] = $src_data[ 'srcpath_md5' ] ;
				$data_to_add[] = $md5 ;
				$data_to_add[] = $src_data[ 'src_filesize' ] ;
			}
		}
		$this->_insert_img_optm( $data_to_add ) ;

		// Insert duplicated data
		if ( $this->_img_duplicated_in_queue ) {
			// Generate root_id from inserted ones
			$srcpath_md5_to_search = array() ;
			foreach ( $this->_img_duplicated_in_queue as $v ) {
				$srcpath_md5_to_search[] = $v[ 'info' ][ 'srcpath_md5' ] ;
			}
			$existing_img_list = $this->_select_img_by_root_srcpath( $srcpath_md5_to_search ) ;

			$data_to_add = array() ;
			foreach ( $this->_img_duplicated_in_queue as $v ) {
				$existing_info = $existing_img_list[ $v[ 'info' ][ 'srcpath_md5' ] ] ;

				$data_to_add[] = $v[ 'pid' ] ;
				$data_to_add[] = $existing_info[ 'status' ] ;
				$data_to_add[] = $existing_info[ 'src' ] ;
				$data_to_add[] = $existing_info[ 'srcpath_md5' ] ;
				$data_to_add[] = $existing_info[ 'src_md5' ] ;
				$data_to_add[] = $existing_info[ 'src_filesize' ] ;
				$data_to_add[] = $existing_info[ 'id' ] ;
			}
			$this->_insert_img_optm( $data_to_add, 'post_id, optm_status, src, srcpath_md5, src_md5, src_filesize, root_id' ) ;
		}

		$accepted_groups = count( $pids ) ;
		$accepted_imgs = $json[ 'total' ] ;

		$placeholder1 = LiteSpeed_Cache_Admin_Display::print_plural( $total_groups ) . ' (' . LiteSpeed_Cache_Admin_Display::print_plural( $this->_img_total, 'image' ) . ')' ;
		$placeholder2 = LiteSpeed_Cache_Admin_Display::print_plural( $accepted_groups ) . ' (' . LiteSpeed_Cache_Admin_Display::print_plural( $accepted_imgs, 'image' ) . ')' ;
		$msg = sprintf( __( 'Pushed %1$s to LiteSpeed optimization server, accepted %2$s.', 'litespeed-cache' ), $placeholder1, $placeholder2 ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		// Update credit info
		if ( isset( $json[ 'credit' ] ) ) {
			$this->_update_credit( $json[ 'credit' ] ) ;
		}

		return array( 'ok' => $msg ) ;

	}

	/**
	 * Insert data into table img_optm
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _insert_img_optm( $data, $fields = 'post_id, optm_status, src, srcpath_md5, src_md5, src_filesize' )
	{
		if ( empty( $data ) ) {
			return ;
		}

		global $wpdb ;

		$division = substr_count( $fields, ',' ) + 1 ;

		$q = "REPLACE INTO $this->_table_img_optm ( $fields ) VALUES " ;

		// Add placeholder
		$q .= $this->_chunk_placeholder( $data, $division ) ;

		// Store data
		$wpdb->query( $wpdb->prepare( $q, $data ) ) ;
	}

	/**
	 * Get all root img data by srcpath_md5
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _select_img_by_root_srcpath( $srcpath_md5_to_search )
	{
		global $wpdb ;

		$existing_img_list = array() ;

		$srcpath_md5_to_search = array_unique( $srcpath_md5_to_search ) ;

		$q = "SELECT * FROM $this->_table_img_optm WHERE root_id=0 AND srcpath_md5 IN ( " . implode( ',', array_fill( 0, count( $srcpath_md5_to_search ), '%s' ) ) . " )" ;
		$tmp = $wpdb->get_results( $wpdb->prepare( $q, $srcpath_md5_to_search ) ) ;
		foreach ( $tmp as $v ) {
			$existing_img_list[ $v->srcpath_md5 ] = array(
				'id'		=> $v->id,
				'status'	=> $v->optm_status,
				'pid'		=> $v->post_id,
				'src'		=> $v->src,
				'srcpath_md5'	=> $v->srcpath_md5,
				'src_md5'	=> $v->src_md5,
				'src_filesize'	=> $v->src_filesize,
			) ;
		}

		return $existing_img_list ;
	}

	/**
	 * Save failed to parse meta info
	 *
	 * @since 2.1.1
	 * @access private
	 */
	private function _mark_wrong_meta_src( $pid )
	{
		$data = array(
			$pid,
			self::DB_IMG_OPTIMIZE_STATUS_XMETA,
		) ;
		$this->_insert_img_optm( $data, 'post_id, optm_status' ) ;
		LiteSpeed_Cache_Log::debug( '[Img_Optm] Mark wrong meta [pid] ' . $pid ) ;
	}

	/**
	 * Handle existing same src path images
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _filter_existing_src()
	{
		global $wpdb ;
// var_dump($this->_img_in_queue);
// var_dump($this->_img_srcpath_md5_array);
		$existing_img_list = $this->_select_img_by_root_srcpath( $this->_img_srcpath_md5_array ) ;
// var_dump($existing_img_list);
		// Handle existing same src data
		$existing_img_optm = array() ;
		$size_to_store = array() ;// pulled images need to update `wp_postmeta` size info
		foreach ( $this->_img_in_queue as $pid => $img_list ) {
			$changed = false ;
			foreach ( $img_list as $md5 => $v ) {
				if ( array_key_exists( $v[ 'srcpath_md5' ], $existing_img_list ) ) {
					$existing_info = $existing_img_list[ $v[ 'srcpath_md5' ] ] ;

					// Insert into img_optm table directly
					$existing_img_optm[] = $pid ;
					$existing_img_optm[] = $existing_info[ 'status' ] ;
					$existing_img_optm[] = $existing_info[ 'src' ] ;
					$existing_img_optm[] = $existing_info[ 'srcpath_md5' ] ;
					$existing_img_optm[] = $existing_info[ 'src_md5' ] ;
					$existing_img_optm[] = $existing_info[ 'src_filesize' ] ;
					$existing_img_optm[] = $existing_info[ 'id' ] ;

					// Bypass IAPI posting by removing from img_in_queue
					unset( $this->_img_in_queue[ $pid ][ $md5 ] ) ;

					// Size info exists. Prepare size info for `wp_postmeta`
					// Only pulled images have size_info
					if ( $existing_info[ 'status' ] == self::DB_IMG_OPTIMIZE_STATUS_PULLED ) {
						$size_to_store[ $pid ] = $existing_info[ 'pid' ] ;
					}

					LiteSpeed_Cache_Log::debug( '[Img_Optm] Existing pulled [pid] ' . $pid . " \t\t\t[status] " . $existing_info[ 'status' ] . " \t\t\t[src] " . $v[ 'src' ] ) ;

					$changed = true ;
				}
			}

			if ( $changed ) {
				if ( empty( $this->_img_in_queue[ $pid ] ) ) {
					unset( $this->_img_in_queue[ $pid ] ) ;
				}
			}

		}
// var_dump($this->_img_in_queue);
// var_dump($existing_img_list);
// var_dump($existing_img_optm);//exit;
		// Existing img needs to be inserted separately
		if ( $existing_img_optm ) {
			$this->_insert_img_optm( $existing_img_optm, 'post_id, optm_status, src, srcpath_md5, src_md5, src_filesize, root_id' ) ;
		}

		// These post_meta in key need to update size info to same as post_meta in val
		if ( $size_to_store ) {
			// Get current data
			$pids = array_unique( $size_to_store ) ;

			LiteSpeed_Cache_Log::debug( '[Img_Optm] Existing size info root pids', $pids ) ;

			// NOTE: Separate this query while not using LEFT JOIN in SELECT * FROM $this->_table_img_optm in previous query to lower db load
			$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND post_id IN ( " . implode( ',', array_fill( 0, count( $pids ), '%s' ) ) . " )" ;
			$tmp = $wpdb->get_results( $wpdb->prepare( $q, array_merge( array( self::DB_IMG_OPTIMIZE_SIZE ), $pids ) ) ) ;
			$existing_sizes = array() ;
			foreach ( $tmp as $v ) {
				$existing_sizes[ $v->post_id ] = $v->meta_value ;
			}

			// Get existing new data
			$size_to_store_pids = array_keys( $size_to_store ) ;
			$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND post_id IN ( " . implode( ',', array_fill( 0, count( $size_to_store_pids ), '%s' ) ) . " )" ;
			$tmp = $wpdb->get_results( $wpdb->prepare( $q, array_merge( array( self::DB_IMG_OPTIMIZE_SIZE ), $size_to_store_pids ) ) ) ;
			$q_to_update = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d" ;
			$size_to_update_pids = array() ;
			foreach ( $tmp as $v ) {
				$size_to_update_pids[] = $v->post_id ;
				$from_pid = $size_to_store[ $v->post_id ] ;
				// Update existing data ( Replaced with existing size info wholly )
				$wpdb->query( $wpdb->prepare( $q_to_update, array( $existing_sizes[ $from_pid ], $v->meta_id ) ) ) ;

				LiteSpeed_Cache_Log::debug( '[Img_Optm] Updated optm_size info [pid] ' . $v->post_id . " \t\t\t[from_pid] " . $from_pid ) ;
			}

			// Insert new size info
			$size_to_insert_pids = array_diff( $size_to_store_pids, $size_to_update_pids ) ;
			$q = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES " ;
			$data = array() ;
			foreach ( $size_to_insert_pids as $pid ) {
				$data[] = $pid ;
				$data[] = self::DB_IMG_OPTIMIZE_SIZE ;
				$data[] = $existing_sizes[ $size_to_store[ $pid ] ] ;
			}

			// Add placeholder
			$q .= $this->_chunk_placeholder( $data, 3 ) ;

			// Store data
			$wpdb->query( $wpdb->prepare( $q, $data ) ) ;

			LiteSpeed_Cache_Log::debug( '[Img_Optm] Inserted optm_size info [total] ' . count( $size_to_insert_pids ) ) ;

		}

	}

	/**
	 * Filter duplicated src in $this->_img_in_queue
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _filter_duplicated_src()
	{
		$srcpath_md5_list = array() ;
		$total_img_duplicated = 0 ;
		$total_pid_unset = 0 ;
		foreach ( $this->_img_in_queue as $pid => $img_list ) {
			foreach ( $img_list as $md5 => $v ) {
				if ( in_array( $v[ 'srcpath_md5' ], $srcpath_md5_list ) ) {
					$this->_img_duplicated_in_queue[] = array(
						'pid'	=> $pid,
						'info'	=> $v,
					) ;

					$total_img_duplicated ++ ;

					unset( $this->_img_in_queue[ $pid ][ $md5 ] ) ;

					continue ;
				}

				$srcpath_md5_list[ $pid . '.' . $md5 ] = $v[ 'srcpath_md5' ] ;

			}

			if ( empty( $this->_img_in_queue[ $pid ] ) ) {
				unset( $this->_img_in_queue[ $pid ] ) ;
				$total_pid_unset ++ ;
			}
		}

		if ( $this->_img_duplicated_in_queue ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Found duplicated src [total_img_duplicated] ' . $total_img_duplicated . ' [total_pid_unset] ' . $total_pid_unset ) ;
		}
	}

	/**
	 * Generate placeholder for an array to query
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _chunk_placeholder( $data, $division )
	{
		$q = implode( ',', array_map(
			function( $el ) { return '(' . implode( ',', $el ) . ')' ; },
			array_chunk( array_fill( 0, count( $data ), '%s' ), $division )
		) ) ;

		return $q ;
	}

	/**
	 * Saved non-existed images into img_optm
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _save_missed_into_img_optm()
	{
		if ( ! $this->_missed_img_in_queue ) {
			return ;
		}
		LiteSpeed_Cache_Log::debug( '[Img_Optm] Missed img need to save [total] ' . count( $this->_missed_img_in_queue ) ) ;

		$data_to_add = array() ;
		foreach ( $this->_missed_img_in_queue as $src_data ) {
			$data_to_add[] = $src_data[ 'pid' ] ;
			$data_to_add[] = self::DB_IMG_OPTIMIZE_STATUS_MISS ;
			$data_to_add[] = $src_data[ 'src' ] ;
			$data_to_add[] = $src_data[ 'srcpath_md5' ] ;
		}
		$this->_insert_img_optm( $data_to_add, 'post_id, optm_status, src, srcpath_md5' ) ;
	}

	/**
	 * Add a new img to queue which will be pushed to LiteSpeed
	 *
	 * @since 1.6
	 * @access private
	 */
	private function _img_queue( $meta_value, $ori_file = false )
	{
		if ( empty( $meta_value[ 'file' ] ) || empty( $meta_value[ 'width' ] ) || empty( $meta_value[ 'height' ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Img_Optm] bypass image due to lack of file/w/h: pid ' . $this->tmp_pid, $meta_value ) ;
			return ;
		}

		$short_file_path = $meta_value[ 'file' ] ;

		if ( ! $ori_file ) {
			$short_file_path = $this->tmp_path . $short_file_path ;
		}

		// check file exists or not
		$_img_info = $this->__media->info( $short_file_path, $this->tmp_pid ) ;

		if ( ! $_img_info || ! in_array( pathinfo( $short_file_path, PATHINFO_EXTENSION ), array( 'jpg', 'jpeg', 'png' ) ) ) {
			$this->_missed_img_in_queue[] = array(
				'pid'	=> $this->tmp_pid,
				'src'	=> $short_file_path,
				'srcpath_md5'	=> md5( $short_file_path ),
			) ;
			LiteSpeed_Cache_Log::debug2( '[Img_Optm] bypass image due to file not exist: pid ' . $this->tmp_pid . ' ' . $short_file_path ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug2( '[Img_Optm] adding image: pid ' . $this->tmp_pid ) ;

		/**
		 * Filter `litespeed_img_optm_options_per_image`
		 * @since 2.4.2
		 */
		/**
		 * To use the filter `litespeed_img_optm_options_per_image` to manipulate `optm_options`, do below:
		 *
		 * 		add_filter( 'litespeed_img_optm_options_per_image', function( $optm_options, $file ){
		 * 			// To add optimize original image
		 * 			if ( Your conditions ) {
		 * 				$optm_options |= LiteSpeed_Cache_API::IMG_OPTM_BM_ORI ;
		 * 			}
		 *
		 * 			// To add optimize webp image
		 * 			if ( Your conditions ) {
		 * 				$optm_options |= LiteSpeed_Cache_API::IMG_OPTM_BM_WEBP ;
		 * 			}
		 *
		 * 			// To turn on lossless optimize for this image e.g. if filename contains `magzine`
		 * 			if ( strpos( $file, 'magzine' ) !== false ) {
		 * 				$optm_options |= LiteSpeed_Cache_API::IMG_OPTM_BM_LOSSLESS ;
		 * 			}
		 *
		 * 			// To set keep exif info for this image
		 * 			if ( Your conditions ) {
		 * 				$optm_options |= LiteSpeed_Cache_API::IMG_OPTM_BM_EXIF ;
		 * 			}
		 *
		 *			return $optm_options ;
		 *   	} ) ;
		 *
		 */
		$optm_options = apply_filters( 'litespeed_img_optm_options_per_image', 0, $short_file_path ) ;

		$img_info = array(
			'url'	=> $_img_info[ 'url' ],
			'src'	=> $short_file_path, // not needed in LiteSpeed IAPI, just leave for local storage after post
			'width'	=> $meta_value[ 'width' ],
			'height'	=> $meta_value[ 'height' ],
			'mime_type'	=> ! empty( $meta_value[ 'mime-type' ] ) ? $meta_value[ 'mime-type' ] : '' ,
			'srcpath_md5'	=> md5( $short_file_path ),
			'src_filesize'	=> $_img_info[ 'size' ], // Only used for local storage and calculation
			'optm_options'	=> $optm_options,
		) ;

		if ( empty( $this->_img_in_queue[ $this->tmp_pid ] ) ) {
			$this->_img_in_queue[ $this->tmp_pid ] = array() ;
		}
		$this->_img_in_queue[ $this->tmp_pid ][ $_img_info[ 'md5' ] ] = $img_info ;
		$this->_img_total ++ ;

		// Build existing data checking array
		$this->_img_srcpath_md5_array[] = $img_info[ 'srcpath_md5' ] ;
	}

	/**
	 * Push img to LiteSpeed IAPI server
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _push_img_in_queue_to_iapi()
	{
		$data = array(
			'list' 			=> $this->_img_in_queue,
			'optm_ori'		=> LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_ORI ) ? 1 : 0,
			'optm_webp'		=> LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_WEBP ) ? 1 : 0,
			'optm_lossless'	=> LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_LOSSLESS ) ? 1 : 0,
			'keep_exif'		=> LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_EXIF ) ? 1 : 0,
		) ;

		// Push to LiteSpeed IAPI server
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_REQUEST_OPTIMIZE, LiteSpeed_Cache_Utility::arr2str( $data ), true, false ) ;

		// admin_api will handle common err
		if ( ! is_array( $json ) ) {
			return $json ;
		}

		// Check data format
		if ( empty( $json[ 'pids' ] ) || ! is_array( $json[ 'pids' ] ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to parse data from LiteSpeed IAPI server ', $json[ 'pids' ] ) ;
			$msg = sprintf( __( 'Failed to parse data from LiteSpeed IAPI server: %s', 'litespeed-cache' ), var_export( $json[ 'pids' ], true ) ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return $json ;
		}

		LiteSpeed_Cache_Log::debug( '[Img_Optm] Returned data from LiteSpeed IAPI server count: ' . count( $json[ 'pids' ] ) ) ;

		return $json ;

	}

	/**
	 * LiteSpeed Child server notify Client img status changed
	 *
	 * @since  1.6
	 * @since  1.6.5 Added err/request status free switch
	 * @access public
	 */
	public function notify_img()
	{
		global $wpdb ;

		list( $notified_data, $server, $status ) = $this->_parse_notify_data() ;

		$pids = array_keys( $notified_data ) ;

		$q = "SELECT a.*, b.meta_id as b_meta_id, b.meta_value AS b_optm_info
				FROM $this->_table_img_optm a
				LEFT JOIN $wpdb->postmeta b ON b.post_id = a.post_id AND b.meta_key = %s
				WHERE a.optm_status != %s AND a.post_id IN ( " . implode( ',', array_fill( 0, count( $pids ), '%d' ) ) . " )" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, array_merge( array( self::DB_IMG_OPTIMIZE_SIZE, self::DB_IMG_OPTIMIZE_STATUS_PULLED ), $pids ) ) ) ;

		$need_pull = false ;
		$last_log_pid = 0 ;
		$postmeta_info = array() ;
		$child_postmeta_info = array() ;

		foreach ( $list as $v ) {
			if ( ! array_key_exists( $v->src_md5, $notified_data[ $v->post_id ] ) ) {
				// This image is not in notifcation
				continue ;
			}

			$json = $notified_data[ $v->post_id ][ $v->src_md5 ] ;

			$server_info = array(
				'server'	=> $server,
			) ;

			// Only need to update meta_info for pull notification, for other notifications, no need to modify meta_info
			if ( ! empty( $json[ 'ori' ] ) || ! empty( $json[ 'webp' ] ) ) {
				// Save server side ID to send taken notification after pulled
				$server_info[ 'id' ] = $json[ 'id' ] ;

				// Default optm info array
				if ( empty( $postmeta_info[ $v->post_id ] ) ) {
					$postmeta_info[ $v->post_id ] =  array(
						'meta_id'	=> $v->b_meta_id,
						'meta_info'	=> array(
							'ori_total' => 0,
							'ori_saved' => 0,
							'webp_total' => 0,
							'webp_saved' => 0,
						),
					) ;
					// Init optm_info for the first one
					if ( ! empty( $v->b_meta_id ) ) {
						foreach ( maybe_unserialize( $v->b_optm_info ) as $k2 => $v2 ) {
							$postmeta_info[ $v->post_id ][ 'meta_info' ][ $k2 ] += $v2 ;
						}
					}
				}

			}

			$target_saved = 0 ;
			if ( ! empty( $json[ 'ori' ] ) ) {
				$server_info[ 'ori_md5' ] = $json[ 'ori_md5' ] ;
				$server_info[ 'ori' ] = $json[ 'ori' ] ;

				$target_saved = $json[ 'ori_reduced' ] ;

				// Append meta info
				$postmeta_info[ $v->post_id ][ 'meta_info' ][ 'ori_total' ] += $json[ 'src_size' ] ;
				$postmeta_info[ $v->post_id ][ 'meta_info' ][ 'ori_saved' ] += $json[ 'ori_reduced' ] ;

			}

			$webp_saved = 0 ;
			if ( ! empty( $json[ 'webp' ] ) ) {
				$server_info[ 'webp_md5' ] = $json[ 'webp_md5' ] ;
				$server_info[ 'webp' ] = $json[ 'webp' ] ;

				$webp_saved = $json[ 'webp_reduced' ] ;

				// Append meta info
				$postmeta_info[ $v->post_id ][ 'meta_info' ][ 'webp_total' ] += $json[ 'src_size' ] ;
				$postmeta_info[ $v->post_id ][ 'meta_info' ][ 'webp_saved' ] += $json[ 'webp_reduced' ] ;
			}

			// Update status and data
			$q = "UPDATE $this->_table_img_optm SET optm_status = %s, target_saved = %d, webp_saved = %d, server_info = %s WHERE id = %d " ;
			$wpdb->query( $wpdb->prepare( $q, array( $status, $target_saved, $webp_saved, json_encode( $server_info ), $v->id ) ) ) ;

			// Update child images ( same md5 files )
			$q = "UPDATE $this->_table_img_optm SET optm_status = %s, target_saved = %d, webp_saved = %d WHERE root_id = %d " ;
			$child_count = $wpdb->query( $wpdb->prepare( $q, array( $status, $target_saved, $webp_saved, $v->id ) ) ) ;

			// Group child meta_info for later update
			if ( ! empty( $json[ 'ori' ] ) || ! empty( $json[ 'webp' ] ) ) {
				if ( $child_count ) {
					$child_postmeta_info[ $v->id ] = $postmeta_info[ $v->post_id ][ 'meta_info' ] ;
				}
			}

			// write log
			$pid_log = $last_log_pid == $v->post_id ? '.' : $v->post_id ;
			LiteSpeed_Cache_Log::debug( '[Img_Optm] notify_img [status] ' . $status . " \t\t[pid] " . $pid_log . " \t\t[id] " . $v->id ) ;
			$last_log_pid = $v->post_id ;

			// set need_pull tag
			if ( $status == self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) {
				$need_pull = true ;
			}

		}

		/**
		 * Update size saved info
		 * @since  1.6.5
		 */
		if ( $postmeta_info ) {
			foreach ( $postmeta_info as $post_id => $optm_arr ) {
				$optm_info = serialize( $optm_arr[ 'meta_info' ] ) ;

				if ( ! empty( $optm_arr[ 'meta_id' ] ) ) {
					$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d " ;
					$wpdb->query( $wpdb->prepare( $q, array( $optm_info, $optm_arr[ 'meta_id' ] ) ) ) ;
				}
				else {
					LiteSpeed_Cache_Log::debug( '[Img_Optm] New size info [pid] ' . $post_id ) ;
					$q = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )" ;
					$wpdb->query( $wpdb->prepare( $q, array( $post_id, self::DB_IMG_OPTIMIZE_SIZE, $optm_info ) ) ) ;
				}
			}
		}

		// Update child postmeta data based on root_id
		if ( $child_postmeta_info ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Proceed child images [total] ' . count( $child_postmeta_info ) ) ;

			$root_id_list = array_keys( $child_postmeta_info ) ;

			$q = "SELECT a.*, b.meta_id as b_meta_id
				FROM $this->_table_img_optm a
				LEFT JOIN $wpdb->postmeta b ON b.post_id = a.post_id AND b.meta_key = %s
				WHERE a.root_id IN ( " . implode( ',', array_fill( 0, count( $root_id_list ), '%d' ) ) . " ) GROUP BY a.post_id" ;

			$tmp = $wpdb->get_results( $wpdb->prepare( $q, array_merge( array( self::DB_IMG_OPTIMIZE_SIZE ), $root_id_list ) ) ) ;

			$pids_to_update = array() ;
			$pids_data_to_insert = array() ;
			foreach ( $tmp as $v ) {
				$optm_info = serialize( $child_postmeta_info[ $v->root_id ] ) ;

				if ( $v->b_meta_id ) {
					$pids_to_update[] = $v->post_id ;
				}
				else {
					$pids_data_to_insert[] = $v->post_id ;
					$pids_data_to_insert[] = self::DB_IMG_OPTIMIZE_SIZE ;
					$pids_data_to_insert[] = $optm_info ;
				}
			}

			// Update these size_info
			if ( $pids_to_update ) {
				$pids_to_update = array_unique( $pids_to_update ) ;
				LiteSpeed_Cache_Log::debug( '[Img_Optm] Update child group size_info [total] ' . count( $pids_to_update ) ) ;

				$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_key = %s AND post_id IN ( " . implode( ',', array_fill( 0, count( $pids_to_update ), '%d' ) ) . " )" ;
				$wpdb->query( $wpdb->prepare( $q, array_merge( array( $optm_info, self::DB_IMG_OPTIMIZE_SIZE ), $pids_to_update ) ) ) ;
			}

			// Insert these size_info
			if ( $pids_data_to_insert ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] Insert child group size_info [total] ' . ( count( $pids_data_to_insert ) / 3 ) ) ;

				$q = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES " ;
				// Add placeholder
				$q .= $this->_chunk_placeholder( $pids_data_to_insert, 3 ) ;
				$wpdb->query( $wpdb->prepare( $q, $pids_data_to_insert ) ) ;
			}

		}

		// Mark need_pull tag for cron
		if ( $need_pull ) {
			update_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ;
		}

		// redo count err

		echo json_encode( array( 'count' => count( $notified_data ) ) ) ;
		exit() ;

	}

	/**
	 * parse LiteSpeed IAPI server data
	 *
	 * @since  1.6.5
	 * @access public
	 */
	private function _parse_notify_data()
	{
		$notified_data = json_decode( base64_decode( $_POST[ 'data' ] ), true ) ;
		if ( empty( $notified_data ) || ! is_array( $notified_data ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] ❌ notify exit: no notified data' ) ;
			exit( json_encode( 'no notified data' ) ) ;
		}

		if ( empty( $_POST[ 'server' ] ) || substr( $_POST[ 'server' ], -21 ) !== 'api.litespeedtech.com' ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] notify exit: no/wrong server' ) ;
			exit( json_encode( 'no/wrong server' ) ) ;
		}

		$_allowed_status = array(
			self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED,
			self::DB_IMG_OPTIMIZE_STATUS_REQUESTED,
		) ;

		if ( empty( $_POST[ 'status' ] ) || ( ! in_array( $_POST[ 'status' ], $_allowed_status ) && substr( $_POST[ 'status' ], 0, 3 ) != self::DB_IMG_OPTIMIZE_STATUS_ERR ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] notify exit: no/wrong status' ) ;
			exit( json_encode( 'no/wrong status' ) ) ;
		}

		return array( $notified_data, $_POST[ 'server' ], $_POST[ 'status' ] ) ;
	}

	/**
	 * Cron pull optimized img
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function cron_pull_optimized_img()
	{
		if ( ! defined( 'DOING_CRON' ) ) {
			return ;
		}

		$tag = get_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL ) ;

		if ( ! $tag || $tag !== self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Img_Optm] Cron pull_optimized_img started' ) ;

		self::get_instance()->_pull_optimized_img() ;
	}

	/**
	 * Pull optm data from litespeed IAPI server for CLI usage
	 *
	 * @since  2.4.4
	 * @access public
	 */
	public function pull_img()
	{
		$res = $this->_pull_optimized_img() ;

		$this->_update_cron_running( true ) ;

		return $res ;
	}

	/**
	 * Pull optimized img
	 *
	 * @since  1.6
	 * @access private
	 */
	private function _pull_optimized_img( $manual = false )
	{
		if ( $this->cron_running() ) {
			$msg = '[Img_Optm] fetch cron is running' ;
			LiteSpeed_Cache_Log::debug( $msg ) ;
			return $msg ;
		}

		global $wpdb ;

		$q = "SELECT * FROM $this->_table_img_optm FORCE INDEX ( optm_status ) WHERE root_id = 0 AND optm_status = %s ORDER BY id LIMIT 1" ;
		$_q = $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ;

		$optm_ori = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_ORI ) ;
		$rm_ori_bkup = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_RM_ORI_BKUP ) ;
		$optm_webp = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_WEBP ) ;

		// pull 1 min images each time
		$end_time = time() + ( $manual ? 120 : 60 ) ;

		$server_list = array() ;

		$total_pulled_ori = 0 ;
		$total_pulled_webp = 0 ;
		$beginning = time() ;

		set_time_limit( $end_time + 20 ) ;
		while ( time() < $end_time ) {
			$row_img = $wpdb->get_row( $_q ) ;
			if ( ! $row_img ) {
				// No image
				break ;
			}

			/**
			 * Update cron timestamp to avoid duplicated running
			 * @since  1.6.2
			 */
			$this->_update_cron_running() ;

			/**
			 * If no server_info, will fail to pull
			 * This is only for v2.4.2- data
			 * @see  https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization:2-4-2-upgrade
			 */
			$server_info = json_decode( $row_img->server_info, true ) ;
			if ( empty( $server_info[ 'server' ] ) ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to decode server_info.' ) ;

				$msg = sprintf(
					__( 'LSCWP %1$s has simplified the image pulling process. Please %2$s, or resend the pull notification this one time only. After that, the process will be automated.', 'litespeed-cache' ),
					'v2.9.6',
					LiteSpeed_Cache_GUI::img_optm_clean_up_unfinished()
				) ;

				$msg .= LiteSpeed_Cache_Doc::learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization:2-4-2-upgrade' ) ;

				LiteSpeed_Cache_Admin_Display::error( $msg ) ;

				return ;
			}
			$server = $server_info[ 'server' ] ;

			$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $row_img->src ;

			// Save ori optm image
			$target_size = 0 ;

			if ( ! empty( $server_info[ 'ori' ] ) ) {
				/**
				 * Use wp orignal get func to avoid allow_url_open off issue
				 * @since  1.6.5
				 */
				$response = wp_remote_get( $server_info[ 'ori' ], array( 'timeout' => 15 ) ) ;
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message() ;
					LiteSpeed_Cache_Log::debug( 'IAPI failed to pull image: ' . $error_message ) ;
					return ;
				}

				file_put_contents( $local_file . '.tmp', $response[ 'body' ] ) ;

				if ( ! file_exists( $local_file . '.tmp' ) || ! filesize( $local_file . '.tmp' ) || md5_file( $local_file . '.tmp' ) !== $server_info[ 'ori_md5' ] ) {
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to pull optimized img: file md5 dismatch, server md5: ' . $server_info[ 'ori_md5' ] ) ;

					// update status to failed
					$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE id = %d " ;
					$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;
					// Update child images
					$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE root_id = %d " ;
					$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;

					return 'Md5 dismatch' ; // exit from running pull process
				}

				// Backup ori img
				$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
				$bk_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension ;

				if ( ! $rm_ori_bkup ) {
					file_exists( $local_file ) && rename( $local_file, $bk_file ) ;
				}

				// Replace ori img
				rename( $local_file . '.tmp', $local_file ) ;

				LiteSpeed_Cache_Log::debug( '[Img_Optm] Pulled optimized img: ' . $local_file ) ;

				$target_size = filesize( $local_file ) ;

				/**
				 * API Hook
				 * @since  2.9.5
				 */
				do_action( 'litespeed_img_pull_ori', $row_img, $local_file ) ;

				$total_pulled_ori ++ ;
			}

			// Save webp image
			$webp_size = 0 ;

			if ( ! empty( $server_info[ 'webp' ] ) ) {

				// Fetch
				$response = wp_remote_get( $server_info[ 'webp' ], array( 'timeout' => 15 ) ) ;
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message() ;
					LiteSpeed_Cache_Log::debug( 'IAPI failed to pull webp image: ' . $error_message ) ;
					return ;
				}

				file_put_contents( $local_file . '.webp', $response[ 'body' ] ) ;

				if ( ! file_exists( $local_file . '.webp' ) || ! filesize( $local_file . '.webp' ) || md5_file( $local_file . '.webp' ) !== $server_info[ 'webp_md5' ] ) {
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to pull optimized webp img: file md5 dismatch, server md5: ' . $server_info[ 'webp_md5' ] ) ;

					// update status to failed
					$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE id = %d " ;
					$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;
					// Update child images
					$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE root_id = %d " ;
					$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;

					return 'WebP md5 dismatch' ; // exit from running pull process
				}

				LiteSpeed_Cache_Log::debug( '[Img_Optm] Pulled optimized img WebP: ' . $local_file . '.webp' ) ;

				$webp_size = filesize( $local_file . '.webp' ) ;

				/**
				 * API for WebP
				 * @since 2.9.5
				 * @see #751737  - API docs for WEBP generation
				 */
				do_action( 'litespeed_img_pull_webp', $row_img, $local_file . '.webp' ) ;

				$total_pulled_webp ++ ;
			}

			LiteSpeed_Cache_Log::debug2( '[Img_Optm] Update _table_img_optm record [id] ' . $row_img->id ) ;

			// Update pulled status
			$q = "UPDATE $this->_table_img_optm SET optm_status = %s, target_filesize = %d, webp_filesize = %d WHERE id = %d " ;
			$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_PULLED, $target_size, $webp_size, $row_img->id ) ) ) ;

			// Update child images ( same md5 files )
			$q = "UPDATE $this->_table_img_optm SET optm_status = %s, target_filesize = %d, webp_filesize = %d WHERE root_id = %d " ;
			$child_count = $wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_PULLED, $target_size, $webp_size, $row_img->id ) ) ) ;

			// Save server_list to notify taken
			if ( empty( $server_list[ $server ] ) ) {
				$server_list[ $server ] = array() ;
			}
			$server_list[ $server ][] = $server_info[ 'id' ] ;

		}

		// Notify IAPI images taken
		$json = false ;
		foreach ( $server_list as $server => $img_list ) {
			$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_IMG_TAKEN, $img_list, $server, true ) ;
		}

		// use latest credit from last server response
		// Recover credit
		if ( is_array( $json ) && isset( $json[ 'credit' ] ) ) {
			$this->_update_credit( $json[ 'credit' ] ) ;
		}

		// Try level up
		$tried_level_up = $this->_try_level_up() ;

		// Check if there is still task in queue
		$q = "SELECT * FROM $this->_table_img_optm WHERE root_id = 0 AND optm_status = %s LIMIT 1" ;
		$tmp = $wpdb->get_row( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ) ;
		if ( $tmp ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Task in queue, to be continued...' ) ;
			return array( 'ok' => 'to_be_continued' ) ;
		}

		// If all pulled, update tag to done
		LiteSpeed_Cache_Log::debug( '[Img_Optm] Marked pull status to all pulled' ) ;
		update_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ;

		$time_cost = time() - $beginning ;
		if ( $tried_level_up ) {
			$tried_level_up = "[Msg] $tried_level_up" ;
		}

		return array( 'ok' => "Pulled [ori] $total_pulled_ori [WebP] $total_pulled_webp [cost] {$time_cost}s $tried_level_up" ) ;
	}

	/**
	 * Auto send optm request
	 *
	 * @since  2.4.1
	 * @access public
	 */
	public static function cron_auto_request()
	{
		if ( ! defined( 'DOING_CRON' ) ) {
			return false ;
		}

		$instance = self::get_instance() ;

		$credit = (int) $instance->summary_info( 'credit' ) ;
		if ( $credit < self::NUM_THRESHOLD_AUTO_REQUEST ) {
			return false ;
		}

		// No need to check last time request interval for now

		$instance->_request_optm( 'from cron' ) ;
	}

	/**
	 * Show an image's optm status
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public function check_img()
	{
		global $wpdb ;

		$pid = $_POST[ 'data' ] ;

		LiteSpeed_Cache_Log::debug( '[Img_Optm] Check image [ID] ' . $pid ) ;

		$data = array() ;

		$data[ 'img_count' ] = $this->img_count() ;
		$data[ 'optm_summary' ] = $this->summary_info() ;

		$data[ '_wp_attached_file' ] = get_post_meta( $pid, '_wp_attached_file', true ) ;
		$data[ '_wp_attachment_metadata' ] = get_post_meta( $pid, '_wp_attachment_metadata', true ) ;

		// Get img_optm data
		$q = "SELECT * FROM $this->_table_img_optm WHERE post_id = %d" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, $pid ) ) ;
		$img_data = array() ;
		if ( $list ) {
			foreach ( $list as $v ) {
				$img_data[] = array(
					'id'	=> $v->id,
					'optm_status'	=> $v->optm_status,
					'src'	=> $v->src,
					'srcpath_md5'	=> $v->srcpath_md5,
					'src_md5'	=> $v->src_md5,
					'server_info'	=> $v->server_info,
				) ;
			}
		}
		$data[ 'img_data' ] = $img_data ;

		echo json_encode( $data ) ;
		exit;
	}

	/**
	 * Parse wp's meta value
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _parse_wp_meta_value( $v )
	{
		if ( ! $v->meta_value ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] bypassed parsing meta due to no meta_value: pid ' . $v->post_id ) ;
			return false ;
		}

		$meta_value = @maybe_unserialize( $v->meta_value ) ;
		if ( ! is_array( $meta_value ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] bypassed parsing meta due to meta_value not json: pid ' . $v->post_id ) ;
			return false ;
		}

		if ( empty( $meta_value[ 'file' ] ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] bypassed parsing meta due to no ori file: pid ' . $v->post_id ) ;
			return false ;
		}

		return $meta_value ;
	}

	/**
	 * Clean up unfinished data for CLI usage
	 *
	 * @since  2.4.4
	 * @access public
	 */
	public function destroy_unfinished()
	{
		$res = $this->_img_optimize_destroy_unfinished() ;

		return $res ;
	}

	/**
	 * Destroy all unfinished queue locally and to LiteSpeed IAPI server
	 *
	 * @since 2.1.2
	 * @access private
	 */
	private function _img_optimize_destroy_unfinished()
	{
		global $wpdb ;

		LiteSpeed_Cache_Log::debug( '[Img_Optm] sending DESTROY_UNFINISHED cmd to LiteSpeed IAPI' ) ;

		// Push to LiteSpeed IAPI server and recover credit
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_REQUEST_DESTROY_UNFINISHED, false, true ) ;

		// confirm link will be displayed by Admin_API automatically
		if ( is_array( $json ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] cmd result', $json ) ;
		}

		// If failed to run request to IAPI
		if ( ! is_array( $json ) || empty( $json[ 'success' ] ) ) {

			// For other errors that Admin_API didn't take
			if ( ! is_array( $json ) ) {
				LiteSpeed_Cache_Admin_Display::error( $json ) ;

				LiteSpeed_Cache_Log::debug( '[Img_Optm] err ', $json ) ;

				return $json ;
			}

			return ;
		}

		// Clear local queue
		$_status_to_clear = array(
			self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED,
			self::DB_IMG_OPTIMIZE_STATUS_REQUESTED,
			self::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH,
		) ;
		$q = "DELETE FROM $this->_table_img_optm WHERE optm_status IN ( " . implode( ',', array_fill( 0, count( $_status_to_clear ), '%s' ) ) . " )" ;
		$wpdb->query( $wpdb->prepare( $q, $_status_to_clear ) ) ;

		// Recover credit
		$this->_sync_data( true ) ;

		$msg = __( 'Destroy unfinished data successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		return $msg ;

	}

	/**
	 * Send destroy all requests cmd to LiteSpeed IAPI server and get the link to finish it ( avoid click by mistake )
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _img_optimize_destroy()
	{
		LiteSpeed_Cache_Log::debug( '[Img_Optm] sending DESTROY cmd to LiteSpeed IAPI' ) ;

		// Mark request time to avoid duplicated request
		update_option( self::DB_IMG_OPTIMIZE_DESTROY, time() ) ;

		// Push to LiteSpeed IAPI server
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_REQUEST_DESTROY, false, true ) ;

		// confirm link will be displayed by Admin_API automatically
		if ( is_array( $json ) && $json ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] cmd result', $json ) ;
		}

	}

	/**
	 * Callback from LiteSpeed IAPI server to destroy all optm data
	 *
	 * @since 1.6.7
	 * @access private
	 */
	public function img_optimize_destroy_callback()
	{
		global $wpdb ;
		LiteSpeed_Cache_Log::debug( '[Img_Optm] excuting DESTROY process' ) ;

		$request_time = get_option( self::DB_IMG_OPTIMIZE_DESTROY ) ;
		if ( time() - $request_time > 300 ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] terminate DESTROY process due to timeout' ) ;
			exit( 'Destroy callback timeout ( 300 seconds )[' . time() . " - $request_time]" ) ;
		}

		/**
		 * Limit to 3000 images each time before redirection to fix Out of memory issue. #665465
		 * @since  2.9.8
		 */
		// Start deleting files
		$limit = apply_filters( 'litespeed_imgoptm_destroy_max_rows', 3000 ) ;
		$q = "SELECT src,post_id FROM $this->_table_img_optm WHERE optm_status = %s ORDER BY id LIMIT %d" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED, $limit ) ) ;
		foreach ( $list as $v ) {
			// del webp
			$this->__media->info( $v->src . '.webp', $v->post_id ) && $this->__media->del( $v->src . '.webp', $v->post_id ) ;
			$this->__media->info( $v->src . '.optm.webp', $v->post_id ) && $this->__media->del( $v->src . '.optm.webp', $v->post_id ) ;

			$extension = pathinfo( $v->src, PATHINFO_EXTENSION ) ;
			$local_filename = substr( $v->src, 0, - strlen( $extension ) - 1 ) ;
			$bk_file = $local_filename . '.bk.' . $extension ;
			$bk_optm_file = $local_filename . '.bk.optm.' . $extension ;

			// del optimized ori
			if ( $this->__media->info( $bk_file, $v->post_id ) ) {
				$this->__media->del( $v->src, $v->post_id ) ;
				$this->__media->rename( $bk_file, $v->src, $v->post_id ) ;
			}
			$this->__media->info( $bk_optm_file, $v->post_id ) && $this->__media->del( $bk_optm_file, $v->post_id ) ;
		}

		// Check if there are more images, then return `to_be_continued` code
		$q = "SELECT COUNT(*) FROM $this->_table_img_optm WHERE optm_status = %s" ;
		$total_img = $wpdb->get_var( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ;
		if ( $total_img > $limit ) {
			$q = "DELETE FROM $this->_table_img_optm WHERE optm_status = %s ORDER BY id LIMIT %d" ;
			$wpdb->query( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED, $limit ) ) ;

			// Return continue signal
			update_option( self::DB_IMG_OPTIMIZE_DESTROY, time() ) ;

			LiteSpeed_Cache_Log::debug( '[Img_Optm] To be continued 🚦' ) ;

			exit( 'to_be_continued' ) ;
		}

		// Delete optm info
		$q = "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'litespeed-optimize%'" ;
		$wpdb->query( $q ) ;

		// Delete img_optm table
		LiteSpeed_Cache_Data::get_instance()->delete_tb_img_optm() ;

		// Clear credit info
		delete_option( self::DB_IMG_OPTM_SUMMARY ) ;
		delete_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL ) ;

		exit( 'ok' ) ;
	}

	/**
	 * Resend requested img to LiteSpeed IAPI server
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _img_optimize_rescan()
	{return;
		LiteSpeed_Cache_Log::debug( '[Img_Optm] resend requested images' ) ;

		$_credit = (int) $this->summary_info( 'credit' ) ;

		global $wpdb ;

		$q = "SELECT a.post_id, a.meta_value, b.meta_id as bmeta_id, c.meta_id as cmeta_id, c.meta_value as cmeta_value
			FROM $wpdb->postmeta a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.post_id
			LEFT JOIN $wpdb->postmeta c ON c.post_id = a.post_id
			WHERE a.meta_key = '_wp_attachment_metadata'
				AND b.meta_key = %s
				AND c.meta_key = %s
			LIMIT %d
			" ;
		$limit_rows = apply_filters( 'litespeed_img_optm_resend_rows', 300 ) ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_DATA, $limit_rows ) ) ) ;
		if ( ! $list ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] resend request bypassed: no image found' ) ;
			$msg = __( 'No image found.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return ;
		}

		// meta list
		$optm_data_list = array() ;
		$optm_data_pid2mid_list = array() ;

		foreach ( $list as $v ) {
			// wp meta
			$meta_value = $this->_parse_wp_meta_value( $v ) ;
			if ( ! $meta_value ) {
				continue ;
			}
			if ( empty( $meta_value[ 'sizes' ] ) ) {
				continue ;
			}

			$optm_data_pid2mid_list[ $v->post_id ] = array( 'status_mid' => $v->bmeta_id, 'data_mid' => $v->cmeta_id ) ;

			// prepare for pushing
			$this->tmp_pid = $v->post_id ;
			$this->tmp_path = pathinfo( $meta_value[ 'file' ], PATHINFO_DIRNAME ) . '/' ;

			// ls optimized meta
			$optm_meta = $optm_data_list[ $v->post_id ] = maybe_unserialize( $v->cmeta_value ) ;
			$optm_list = array() ;
			foreach ( $optm_meta as $md5 => $optm_row ) {
				$optm_list[] = $optm_row[ 0 ] ;
				// only do for requested/notified img
				// if ( ! in_array( $optm_row[ 1 ], array( self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED, self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) ) ) {
				// 	continue ;
				// }
			}

			// check if there is new files from wp meta
			$img_queue = array() ;
			foreach ( $meta_value[ 'sizes' ] as $v2 ) {
				$curr_file = $this->tmp_path . $v2[ 'file' ] ;

				// new child file OR not finished yet
				if ( ! in_array( $curr_file, $optm_list ) ) {
					$img_queue[] = $v2 ;
				}
			}

			// nothing to add
			if ( ! $img_queue ) {
				continue ;
			}

			$num_will_incease = count( $img_queue ) ;
			if ( $this->_img_total + $num_will_incease > $_credit ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] resend img request hit limit: [total] ' . $this->_img_total . " \t[add] $num_will_incease \t[credit] $_credit" ) ;
				break ;
			}

			foreach ( $img_queue as $v2 ) {
				$this->_img_queue( $v2 ) ;
			}
		}

		// push to LiteSpeed IAPI server
		if ( empty( $this->_img_in_queue ) ) {
			$msg = __( 'No image found.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
			return ;
		}

		$total_groups = count( $this->_img_in_queue ) ;
		LiteSpeed_Cache_Log::debug( '[Img_Optm] prepared images to push: groups ' . $total_groups . ' images ' . $this->_img_total ) ;

		// Push to LiteSpeed IAPI server
		$json = $this->_push_img_in_queue_to_iapi() ;
		if ( ! is_array( $json ) ) {
			return $json ;
		}
		// Returned data is the requested and notifed images
		$pids = $json[ 'pids' ] ;

		$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d" ;

		// Update data
		foreach ( $pids as $pid ) {
			$md52src_list = $optm_data_list[ $pid ] ;

			foreach ( $this->_img_in_queue[ $pid ] as $md5 => $src_data ) {
				$md52src_list[ $md5 ] = array( $src_data[ 'src' ], self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) ;
			}

			$new_status = $this->_get_status_by_meta_data( $md52src_list, self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) ;

			$md52src_list = serialize( $md52src_list ) ;

			// Store data
			$wpdb->query( $wpdb->prepare( $q, array( $new_status, $optm_data_pid2mid_list[ $pid ][ 'status_mid' ] ) ) ) ;
			$wpdb->query( $wpdb->prepare( $q, array( $md52src_list, $optm_data_pid2mid_list[ $pid ][ 'data_mid' ] ) ) ) ;
		}

		$accepted_groups = count( $pids ) ;
		$accepted_imgs = $json[ 'total' ] ;

		$msg = sprintf( __( 'Pushed %1$s groups with %2$s images to LiteSpeed optimization server, accepted %3$s groups with %4$s images.', 'litespeed-cache' ), $total_groups, $this->_img_total, $accepted_groups, $accepted_imgs ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		// Update credit info
		if ( isset( $json[ 'credit' ] ) ) {
			$this->_update_credit( $json[ 'credit' ] ) ;
		}

	}

	/**
	 * Try to level up
	 *
	 * @since 2.4.1
	 * @access private
	 */
	private function _try_level_up()
	{
		$optm_summary = $this->summary_info() ;
		if ( empty( $optm_summary[ 'level' ] ) || empty( $optm_summary[ 'credit_recovered' ] ) || empty( $optm_summary[ '_level_data' ] ) ) {
			return ;
		}

		// level beyond 5 should be triggered manually
		if ( $optm_summary[ 'level' ] >= 5 ) {
			return ;
		}

		$next_level = $optm_summary[ 'level' ] + 1 ;
		$next_level_data = $optm_summary[ '_level_data' ][ $next_level ] ;

		if ( $optm_summary[ 'credit_recovered' ] <= $next_level_data[ 0 ] ) {
			return ;
		}

		// Now do level up magic
		// Bless we can get more reviews to encourage me ~
		$json = $this->_sync_data( true ) ;
		if ( $json[ 'level' ] > $optm_summary[ 'level' ] ) {
			$msg = "Upgraded to level $json[level] !" ;
			LiteSpeed_Cache_Log::debug( "[Img_Optm] $msg" ) ;
			return $msg ;
		}
		else {
			LiteSpeed_Cache_Log::debug( "[Img_Optm] Upgrade failed [old level data] " . var_export( $optm_summary, true ), $json ) ;
		}
	}

	/**
	 * Update client credit info
	 *
	 * @since 1.6.5
	 * @access private
	 */
	private function _update_credit( $credit )
	{
		$summary = get_option( self::DB_IMG_OPTM_SUMMARY, array() ) ;

		if ( empty( $summary[ 'credit' ] ) ) {
			$summary[ 'credit' ] = 0 ;
		}

		if ( $credit === '++' ) {
			$credit = $summary[ 'credit' ] + 1 ;
		}

		$old = $summary[ 'credit' ] ?: '-' ;
		LiteSpeed_Cache_Log::debug( "[Img_Optm] Credit updated \t\t[Old] $old \t\t[New] $credit" ) ;

		// Mark credit recovered
		if ( $credit > $summary[ 'credit' ] ) {
			if ( empty( $summary[ 'credit_recovered' ] ) ) {
				$summary[ 'credit_recovered' ] = 0 ;
			}
			$summary[ 'credit_recovered' ] += $credit - $summary[ 'credit' ] ;
		}

		$summary[ 'credit' ] = $credit ;

		update_option( self::DB_IMG_OPTM_SUMMARY, $summary ) ;
	}

	/**
	 * Calculate bkup original images storage
	 *
	 * @since 2.2.6
	 * @access private
	 */
	private function _calc_bkup()
	{
		global $wpdb ;
		$q = "SELECT src,post_id FROM $this->_table_img_optm WHERE optm_status = %s" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ;

		$i = 0 ;
		$total_size = 0 ;
		foreach ( $list as $v ) {
			$extension = pathinfo( $v->src, PATHINFO_EXTENSION ) ;
			$local_filename = substr( $v->src, 0, - strlen( $extension ) - 1 ) ;
			$bk_file = $local_filename . '.bk.' . $extension ;

			$img_info = $this->__media->info( $bk_file, $v->post_id ) ;
			if ( ! $img_info ) {
				continue ;
			}

			$i ++ ;
			$total_size += $img_info[ 'size' ] ;

		}

		$data = array(
			'date' => time(),
			'count' => $i,
			'sum' => $total_size,
		) ;
		update_option( self::DB_IMG_OPTM_BK_SUMMARY, $data ) ;

		LiteSpeed_Cache_Log::debug( '[Img_Optm] _calc_bkup total: ' . $i . ' [size] ' . $total_size ) ;

	}

	/**
	 * Remove backups for CLI usage
	 *
	 * @since  2.5
	 * @access public
	 */
	public function rm_bkup()
	{
		return $this->_rm_bkup() ;
	}

	/**
	 * Delete bkup original images storage
	 *
	 * @since 2.2.6
	 * @access private
	 */
	private function _rm_bkup()
	{
		global $wpdb ;
		$q = "SELECT src,post_id FROM $this->_table_img_optm WHERE optm_status = %s" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ;

		$i = 0 ;
		$total_size = 0 ;
		foreach ( $list as $v ) {
			$extension = pathinfo( $v->src, PATHINFO_EXTENSION ) ;
			$local_filename = substr( $v->src, 0, - strlen( $extension ) - 1 ) ;
			$bk_file = $local_filename . '.bk.' . $extension ;

			// Del ori file
			$img_info = $this->__media->info( $bk_file, $v->post_id ) ;
			if ( ! $img_info ) {
				continue ;
			}

			$i ++ ;
			$total_size += $img_info[ 'size' ] ;

			$this->__media->del( $bk_file, $v->post_id ) ;
		}

		$data = array(
			'date' => time(),
			'count' => $i,
			'sum' => $total_size,
		) ;
		update_option( self::DB_IMG_OPTM_RMBK_SUMMARY, $data ) ;

		LiteSpeed_Cache_Log::debug( '[Img_Optm] _rm_bkup total: ' . $i . ' [size] ' . $total_size ) ;

		$msg = sprintf( __( 'Removed %1$s images and saved %2$s successfully.', 'litespeed-cache' ), $i, LiteSpeed_Cache_Utility::real_size( $total_size ) ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		return $msg ;
	}

	/**
	 * Get optm summary
	 *
	 * @since 1.6.5
	 * @access public
	 */
	public function summary_info( $field = false )
	{
		$optm_summary = get_option( self::DB_IMG_OPTM_SUMMARY, array() ) ;

		if ( ! $field ) {
			return $optm_summary ;
		}
		return ! empty( $optm_summary[ $field ] ) ? $optm_summary[ $field ] : 0 ;
	}

	/**
	 * Get optm bkup usage summary
	 *
	 * @since 2.2.6
	 * @access public
	 */
	public function storage_data()
	{
		$summary = get_option( self::DB_IMG_OPTM_BK_SUMMARY, array() ) ;
		$rm_log = get_option( self::DB_IMG_OPTM_RMBK_SUMMARY, array() ) ;

		return array( $summary, $rm_log ) ;
	}

	/**
	 * Count images
	 *
	 * @since 1.6
	 * @access public
	 */
	public function img_count()
	{
		global $wpdb ;
		$q = "SELECT count(*)
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
			" ;
		// $q = "SELECT count(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type IN ('image/jpeg', 'image/png') " ;
		$total_img = $wpdb->get_var( $q ) ;

		$q = "SELECT count(*)
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			LEFT JOIN $this->_table_img_optm c ON c.post_id = a.ID
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
				AND c.id IS NULL
			" ;
		$total_not_requested = $wpdb->get_var( $q ) ;

		// images count from img_optm table
		$q_groups = "SELECT count(distinct post_id) FROM $this->_table_img_optm WHERE optm_status = %s" ;
		$q = "SELECT count(*) FROM $this->_table_img_optm WHERE optm_status = %s" ;

		// The groups to check
		$images_to_check = $groups_to_check = array(
			self::DB_IMG_OPTIMIZE_STATUS_REQUESTED,
			self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED,
			self::DB_IMG_OPTIMIZE_STATUS_PULLED,
			self::DB_IMG_OPTIMIZE_STATUS_ERR,
			self::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH,
			self::DB_IMG_OPTIMIZE_STATUS_ERR_OPTM,
			self::DB_IMG_OPTIMIZE_STATUS_MISS,
		) ;

		// The images to check
		$images_to_check[] = self::DB_IMG_OPTIMIZE_STATUS_XMETA ;

		$count_list = array() ;

		foreach ( $groups_to_check as $v ) {
			$count_list[ 'group.' . $v ] = $wpdb->get_var( $wpdb->prepare( $q_groups, $v ) ) ;
		}

		foreach ( $images_to_check as $v ) {
			$count_list[ 'img.' . $v ] = $wpdb->get_var( $wpdb->prepare( $q, $v ) ) ;
		}

		$data = array(
			'total_img'	=> $total_img,
			'total_not_requested'	=> $total_not_requested,
		) ;

		return array_merge( $data, $count_list ) ;
	}

	/**
	 * Check if fetch cron is running
	 *
	 * @since  1.6.2
	 * @access public
	 */
	public function cron_running( $bool_res = true )
	{
		$last_run = get_option( self::ITEM_IMG_OPTM_CRON_RUN ) ;

		$is_running = $last_run && time() - $last_run < 120 ;

		if ( $bool_res ) {
			return $is_running ;
		}

		return array( $last_run, $is_running ) ;
	}

	/**
	 * Update fetch cron timestamp tag
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _update_cron_running( $done = false )
	{
		$ts = time() ;

		if ( $done ) {
			// Only update cron tag when its from the active running cron
			if ( $this->_cron_ran ) {
				// Rollback for next running
				$ts -= 120 ;
			}
			else {
				return ;
			}
		}

		update_option( self::ITEM_IMG_OPTM_CRON_RUN, $ts ) ;

		$this->_cron_ran = true ;
	}

	/**
	 * Batch switch images to ori/optm version
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _batch_switch( $type )
	{
		global $wpdb ;
		$q = "SELECT src,post_id FROM $this->_table_img_optm WHERE optm_status = %s" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ;

		$i = 0 ;
		foreach ( $list as $v ) {
			$extension = pathinfo( $v->src, PATHINFO_EXTENSION ) ;
			$local_filename = substr( $v->src, 0, - strlen( $extension ) - 1 ) ;
			$bk_file = $local_filename . '.bk.' . $extension ;
			$bk_optm_file = $local_filename . '.bk.optm.' . $extension ;

			// switch to ori
			if ( $type === self::TYPE_IMG_BATCH_SWITCH_ORI ) {
				if ( ! $this->__media->info( $bk_file, $v->post_id ) ) {
					continue ;
				}

				$i ++ ;

				$this->__media->rename( $v->src, $bk_optm_file, $v->post_id ) ;
				$this->__media->rename( $bk_file, $v->src, $v->post_id ) ;
			}
			// switch to optm
			elseif ( $type === self::TYPE_IMG_BATCH_SWITCH_OPTM ) {
				if ( ! $this->__media->info( $bk_optm_file, $v->post_id ) ) {
					continue ;
				}

				$i ++ ;

				$this->__media->rename( $v->src, $bk_file, $v->post_id ) ;
				$this->__media->rename( $bk_optm_file, $v->src, $v->post_id ) ;
			}
		}

		LiteSpeed_Cache_Log::debug( '[Img_Optm] batch switched images total: ' . $i ) ;
		$msg = __( 'Switched images successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg ) ;

	}

	/**
	 * Switch image between original one and optimized one
	 *
	 * @since 1.6.2
	 * @access private
	 */
	private function _switch_optm_file( $type )
	{
		$pid = substr( $type, 4 ) ;
		$switch_type = substr( $type, 0, 4 ) ;

		global $wpdb ;
		$q = "SELECT src,post_id FROM $this->_table_img_optm WHERE optm_status = %s AND post_id = %d" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_PULLED, $pid ) ) ) ;

		$msg = 'Unknown Msg' ;

		foreach ( $list as $v ) {
			// to switch webp file
			if ( $switch_type === 'webp' ) {
				if ( $this->__media->info( $v->src . '.webp', $v->post_id ) ) {
					$this->__media->rename( $v->src . '.webp', $v->src . '.optm.webp', $v->post_id ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Disabled WebP: ' . $v->src ) ;

					$msg = __( 'Disabled WebP file successfully.', 'litespeed-cache' ) ;
				}
				elseif ( $this->__media->info( $v->src . '.optm.webp', $v->post_id ) ) {
					$this->__media->rename( $v->src . '.optm.webp', $v->src . '.webp', $v->post_id ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Enable WebP: ' . $v->src ) ;

					$msg = __( 'Enabled WebP file successfully.', 'litespeed-cache' ) ;
				}
			}
			// to switch original file
			else {
				$extension = pathinfo( $v->src, PATHINFO_EXTENSION ) ;
				$local_filename = substr( $v->src, 0, - strlen( $extension ) - 1 ) ;
				$bk_file = $local_filename . '.bk.' . $extension ;
				$bk_optm_file = $local_filename . '.bk.optm.' . $extension ;

				// revert ori back
				if ( $this->__media->info( $bk_file, $v->post_id ) ) {
					$this->__media->rename( $v->src, $bk_optm_file, $v->post_id ) ;
					$this->__media->rename( $bk_file, $v->src, $v->post_id ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Restore original img: ' . $bk_file ) ;

					$msg = __( 'Restored original file successfully.', 'litespeed-cache' ) ;
				}
				elseif ( $this->__media->info( $bk_optm_file, $v->post_id ) ) {
					$this->__media->rename( $v->src, $bk_file, $v->post_id ) ;
					$this->__media->rename( $bk_optm_file, $v->src, $v->post_id ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Switch to optm img: ' . $v->src ) ;

					$msg = __( 'Switched to optimized file successfully.', 'litespeed-cache' ) ;
				}

			}
		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg ) ;
	}

	/**
	 * Delete one optm data and recover original file
	 *
	 * @since 2.4.2
	 * @access public
	 */
	public function reset_row( $post_id )
	{
		if ( ! $post_id ) {
			return ;
		}

		$size_meta = get_post_meta( $post_id, self::DB_IMG_OPTIMIZE_SIZE, true ) ;

		if ( ! $size_meta ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Img_Optm] _reset_row [pid] ' . $post_id ) ;

		global $wpdb ;
		$q = "SELECT src,post_id FROM $this->_table_img_optm WHERE post_id = %d" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, array( $post_id ) ) ) ;

		foreach ( $list as $v ) {
			$this->__media->info( $v->src . '.webp', $v->post_id ) && $this->__media->del( $v->src . '.webp', $v->post_id ) ;
			$this->__media->info( $v->src . '.optm.webp', $v->post_id ) && $this->__media->del( $v->src . '.optm.webp', $v->post_id ) ;

			$extension = pathinfo( $v->src, PATHINFO_EXTENSION ) ;
			$local_filename = substr( $v->src, 0, - strlen( $extension ) - 1 ) ;
			$bk_file = $local_filename . '.bk.' . $extension ;
			$bk_optm_file = $local_filename . '.bk.optm.' . $extension ;

			if ( $this->__media->info( $bk_file, $v->post_id ) ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] _reset_row Revert ori file' . $bk_file ) ;
				$this->__media->del( $v->src, $v->post_id ) ;
				$this->__media->rename( $bk_file, $v->src, $v->post_id ) ;
			}
			elseif ( $this->__media->info( $bk_optm_file, $v->post_id ) ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] _reset_row Del ori bk file' . $bk_optm_file ) ;
				$this->__media->del( $bk_optm_file, $v->post_id ) ;
			}
		}

		$q = "DELETE FROM $this->_table_img_optm WHERE post_id = %d" ;
		$wpdb->query( $wpdb->prepare( $q, $post_id ) ) ;

		delete_post_meta( $post_id, self::DB_IMG_OPTIMIZE_SIZE ) ;

		$msg = __( 'Reset the optimized data successfully.', 'litespeed-cache' ) ;

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg ) ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.0
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_RESET_ROW :
				$instance->reset_row( ! empty( $_GET[ 'id' ] ) ? $_GET[ 'id' ] : false ) ;
				break ;

			case self::TYPE_CALC_BKUP :
				$instance->_calc_bkup() ;
				break ;

			case self::TYPE_RM_BKUP :
				$instance->_rm_bkup() ;
				break ;

			case self::TYPE_SYNC_DATA :
				$instance->_sync_data() ;
				break ;

			case self::TYPE_IMG_OPTIMIZE :
				$instance->_request_optm() ;
				break ;

			case self::TYPE_IMG_OPTIMIZE_RESCAN :
				$instance->_img_optimize_rescan() ;
				break ;

			case self::TYPE_IMG_OPTM_DESTROY :
				$instance->_img_optimize_destroy() ;
				break ;

			case self::TYPE_IMG_OPTM_DESTROY_UNFINISHED :
				$instance->_img_optimize_destroy_unfinished() ;
				break ;

			case self::TYPE_IMG_PULL :
				LiteSpeed_Cache_Log::debug( 'ImgOptm: Manually running Cron pull_optimized_img' ) ;
				$result = $instance->_pull_optimized_img( true ) ;
				// Manually running needs to roll back timestamp for next running
				$instance->_update_cron_running( true ) ;

				// Check if need to self redirect
				if ( is_array( $result ) && $result[ 'ok' ] === 'to_be_continued' ) {
					$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_PULL ) ;
					// Add i to avoid browser too many redirected warning
					$i = ! empty( $_GET[ 'i' ] ) ? $_GET[ 'i' ] : 0 ;
					$i ++ ;
					$url = html_entity_decode( $link ) . '&i=' . $i ;
					exit( "<meta http-equiv='refresh' content='0;url=$url'>" ) ;
					// LiteSpeed_Cache_Admin::redirect( $url ) ;
				}
				break ;

			/**
			 * Batch switch
			 * @since 1.6.3
			 */
			case self::TYPE_IMG_BATCH_SWITCH_ORI :
			case self::TYPE_IMG_BATCH_SWITCH_OPTM :
				$instance->_batch_switch( $type ) ;
				break ;

			case substr( $type, 0, 4 ) === 'webp' :
			case substr( $type, 0, 4 ) === 'orig' :
				$instance->_switch_optm_file( $type ) ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 2.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}