<?php

/**
 * The class to optimize image.
 *
 * @since 		2.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

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

	const ITEM_IMG_OPTM_CRON_RUN = 'litespeed-img_optm_cron_run' ; // last cron running time

	const DB_IMG_OPTIMIZE_DESTROY = 'litespeed-optimize-destroy' ;
	const DB_IMG_OPTIMIZE_DATA = 'litespeed-optimize-data' ;
	const DB_IMG_OPTIMIZE_STATUS = 'litespeed-optimize-status' ;
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
		$this->_table_img_optm = LiteSpeed_Cache_Data::get_tb_img_optm() ;
	}

	/**
	 * Sync data from litespeed IAPI server
	 *
	 * @since  1.6.5
	 * @access private
	 */
	private function _sync_data()
	{
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_MEDIA_SYNC_DATA ) ;

		if ( ! is_array( $json ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to post to LiteSpeed IAPI server ', $json ) ;
			$msg = __( 'Failed to communicate with LiteSpeed IAPI server', 'litespeed-cache' ) . ': ' . $json ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return ;
		}

		if ( ! empty( $json ) ) {
			update_option( self::DB_IMG_OPTM_SUMMARY, $json ) ;
		}

		$msg = __( 'Communicated with LiteSpeed Image Optimization Server successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		// Update guidance
		if ( ! empty( $json[ 'level' ] ) && $json[ 'level' ] > 1 ) {
			$this->_update_guidance_pos( 'done' ) ;
		}
		elseif ( $this->get_guidance_pos() == 1 ) {
			$this->_update_guidance_pos( 2 ) ;
		}

		LiteSpeed_Cache_Admin::redirect() ;

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
		$q = $wpdb->prepare( $q, apply_filters( 'litespeed_img_optimize_max_rows', 500 ) ) ;

		$img_set = array() ;
		$list = $wpdb->get_results( $q ) ;
		if ( ! $list ) {
			$msg = __( 'No image found.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

			LiteSpeed_Cache_Log::debug( '[Img_Optm] optimize bypass: no image found' ) ;
			return ;
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
			return ;
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
			return ;
		}

		$total_groups = count( $this->_img_in_queue ) ;
		LiteSpeed_Cache_Log::debug( '[Img_Optm] prepared images to push: groups ' . $total_groups . ' images ' . $this->_img_total ) ;

		// Push to LiteSpeed IAPI server
		$json = $this->_push_img_in_queue_to_iapi() ;
		if ( $json === null ) {
			return ;
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

		// Update guidance
		if ( $this->get_guidance_pos() == 2 ) {
			$this->_update_guidance_pos( 3 ) ;
		}

	}

	/**
	 * Insert data into table img_optm
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _insert_img_optm( $data, $fields = 'post_id, optm_status, src, srcpath_md5, src_md5, src_filesize' )
	{
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

		if ( ! $ori_file ) {
			$meta_value[ 'file' ] = $this->tmp_path . $meta_value[ 'file' ] ;
		}

		// check file exists or not
		$real_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $meta_value[ 'file' ] ;
		if ( ! file_exists( $real_file ) ) {
			$this->_missed_img_in_queue[] = array(
				'pid'	=> $this->tmp_pid,
				'src'	=> $meta_value[ 'file' ],
				'srcpath_md5'	=> md5( $meta_value[ 'file' ] ),
			) ;
			LiteSpeed_Cache_Log::debug2( '[Img_Optm] bypass image due to file not exist: pid ' . $this->tmp_pid . ' ' . $real_file ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug2( '[Img_Optm] adding image: pid ' . $this->tmp_pid ) ;

		$img_info = array(
			'url'	=> $this->wp_upload_dir[ 'baseurl' ] . '/' . $meta_value[ 'file' ],
			'src'	=> $meta_value[ 'file' ], // not needed in LiteSpeed sapi, just leave for local storage after post
			'width'	=> $meta_value[ 'width' ],
			'height'	=> $meta_value[ 'height' ],
			'mime_type'	=> ! empty( $meta_value[ 'mime-type' ] ) ? $meta_value[ 'mime-type' ] : '' ,
			'srcpath_md5'	=> md5( $meta_value[ 'file' ] ),
			'src_filesize'	=> filesize( $real_file ),
		) ;
		$md5 = md5_file( $real_file ) ;

		if ( empty( $this->_img_in_queue[ $this->tmp_pid ] ) ) {
			$this->_img_in_queue[ $this->tmp_pid ] = array() ;
		}
		$this->_img_in_queue[ $this->tmp_pid ][ $md5 ] = $img_info ;
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
			'list' => $this->_img_in_queue,
			'webp_only'	=> LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_ONLY ),
			'keep_exif'	=> LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_EXIF ),
			'webp_lossless'	=> LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_LOSSLESS ),
		) ;

		// Push to LiteSpeed IAPI server
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_REQUEST_OPTIMIZE, LiteSpeed_Cache_Utility::arr2str( $data ) ) ;

		if ( $json === null ) {// admin_api will handle common err
			return null ;
		}

		if ( ! is_array( $json ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to post to LiteSpeed IAPI server ', $json ) ;
			$msg = sprintf( __( 'Failed to push to LiteSpeed IAPI server: %s', 'litespeed-cache' ), $json ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return null ;
		}

		// Check data format
		if ( empty( $json[ 'pids' ] ) || ! is_array( $json[ 'pids' ] ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to parse data from LiteSpeed IAPI server ', $json[ 'pids' ] ) ;
			$msg = sprintf( __( 'Failed to parse data from LiteSpeed IAPI server: %s', 'litespeed-cache' ), $json[ 'pids' ] ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return null ;
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

		$q = "SELECT * FROM $this->_table_img_optm WHERE post_id IN ( " . implode( ',', array_fill( 0, count( $pids ), '%d' ) ) . " ) AND optm_status != %s" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, array_merge( $pids, array( self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ) ) ;

		$need_pull = false ;
		$last_log_pid = 0 ;

		foreach ( $list as $v ) {
			if ( ! in_array( $v->src_md5, $notified_data[ $v->post_id ] ) ) {
				// This image is not in notifcation
				continue ;
			}

			// Save data
			$q = "UPDATE $this->_table_img_optm SET optm_status = %s, server = %s WHERE id = %d" ;
			$wpdb->query( $wpdb->prepare( $q, array( $status, $server, $v->id ) ) ) ;

			$pid_log = $last_log_pid == $v->post_id ? '.' : $v->post_id ;
			LiteSpeed_Cache_Log::debug( '[Img_Optm] notify_img [status] ' . $status . " \t\t[pid] " . $pid_log . " \t\t[id] " . $v->id ) ;
			$last_log_pid = $v->post_id ;

			if ( $status == self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) {
				$need_pull = true ;
			}
		}

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
		$notified_data = unserialize( base64_decode( $_POST[ 'data' ] ) ) ;
		if ( empty( $notified_data ) || ! is_array( $notified_data ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] notify exit: no notified data' ) ;
			exit( json_encode( 'no notified data' ) ) ;
		}

		if ( empty( $_POST[ 'server' ] ) || substr( $_POST[ 'server' ], -21 ) !== 'api.litespeedtech.com' ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] notify exit: no/wrong server' ) ;
			exit( json_encode( 'no/wrong server' ) ) ;
		}

		$_allowed_status = array(
			self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED,
			self::DB_IMG_OPTIMIZE_STATUS_ERR,
			self::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH,
			self::DB_IMG_OPTIMIZE_STATUS_ERR_OPTM,
			self::DB_IMG_OPTIMIZE_STATUS_REQUESTED,
		) ;

		if ( empty( $_POST[ 'status' ] ) || ! in_array( $_POST[ 'status' ], $_allowed_status ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] notify exit: no/wrong status' ) ;
			exit( json_encode( 'no/wrong status' ) ) ;
		}

		return array( $notified_data, $_POST[ 'server' ], $_POST[ 'status' ] ) ;
	}

	/**
	 * Pull optimized img
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function pull_optimized_img()
	{
		LiteSpeed_Cache_Log::debug( '[Img_Optm] Cron pull_optimized_img started' ) ;
		$instance = self::get_instance() ;
		$instance->_pull_optimized_img() ;
	}

	/**
	 * Pull optimized img
	 *
	 * @since  1.6
	 * @access private
	 */
	private function _pull_optimized_img()
	{
		if ( $this->cron_running() ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] fetch cron is running' ) ;
			return ;
		}

		global $wpdb ;

		$q = "SELECT a.*, b.meta_id as b_meta_id, b.meta_value AS b_optm_info
				FROM $this->_table_img_optm a
				LEFT JOIN $wpdb->postmeta b ON b.post_id = a.post_id AND b.meta_key = %s
				WHERE a.root_id = 0 AND a.optm_status = %s ORDER BY a.id LIMIT 1" ;
		$_q = $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_SIZE, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ) ;

		$webp_only = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_ONLY ) ;

		// pull 10 images each time
		for ( $i=0 ; $i < 10 ; $i++ ) {
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

			// Default optm info array
			$optm_info = array(
				'ori_total' => 0,
				'ori_saved' => 0,
				'webp_total' => 0,
				'webp_saved' => 0,
			) ;
			if ( ! empty( $row_img->b_meta_id ) ) {
				$optm_info = array_merge( $optm_info, unserialize( $row_img->b_optm_info ) ) ;
			}

			// send fetch request
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Connecting IAPI server for [pid] ' . $row_img->post_id . ' [src_md5]' . $row_img->src_md5 ) ;
			$server = $row_img->server ;
			$data = array(
				'pid' => $row_img->post_id,
				'src_md5' => $row_img->src_md5,
			) ;
			$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PULL_IMG, $data, $server, true ) ;
			if ( empty( $json[ 'webp' ] ) ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to pull optimized img: ', $json ) ;
				return ;
			}

			$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $row_img->src ;

			/**
			 * Use wp orignal get func to avoid allow_url_open off issue
			 * @since  1.6.5
			 */
			// Fetch webp image
			$response = wp_remote_get( $json[ 'webp' ], array( 'timeout' => 15 ) ) ;
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message() ;
				LiteSpeed_Cache_Log::debug( 'IAPI failed to pull image: ' . $error_message ) ;
				return ;
			}

			file_put_contents( $local_file . '.webp', $response[ 'body' ] ) ;

			if ( ! file_exists( $local_file . '.webp' ) ) {
				return ;
			}

			// Unknown issue
			if ( md5_file( $local_file . '.webp' ) !== $json[ 'webp_md5' ] ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to pull optimized img WebP: file md5 dismatch, server md5: ' . $json[ 'webp_md5' ] ) ;

				// update status to failed
				$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE id = %d " ;
				$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;
				// Update child images
				$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE root_id = %d " ;
				$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;

				// Notify server to update status
				LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PULL_IMG_FAILED, $data, $server, true ) ;

				return ;// exit from running pull process
			}

			$ori_size = $row_img->src_filesize ?:  filesize( $local_file ) ;

			// log webp file saved size summary
			$webp_size = filesize( $local_file . '.webp' ) ;
			$webp_saved = $ori_size - $webp_size ;
			if ( $webp_saved > 0 ) {
				$optm_info[ 'webp_total' ] += $ori_size ;
				$optm_info[ 'webp_saved' ] += $webp_saved ;
			}
			else {
				$webp_saved = 0 ;
			}

			LiteSpeed_Cache_Log::debug( '[Img_Optm] Pulled optimized img WebP: ' . $local_file . '.webp' ) ;

			// Fetch optimized image itself
			$target_size = 0 ;
			$target_saved = 0 ;
			if ( ! $webp_only && ! empty( $json[ 'target_file' ] ) ) {

				// Fetch failed, unkown issue, return
				// NOTE: if this failed more than 5 times, next time fetching webp will touch err limit on server side, whole image will be failed
				$response = wp_remote_get( $json[ 'target_file' ], array( 'timeout' => 15 ) ) ;
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message() ;
					LiteSpeed_Cache_Log::debug( 'IAPI failed to pull image: ' . $error_message ) ;
					return ;
				}

				file_put_contents( $local_file . '.tmp', $response[ 'body' ] ) ;
				// Unknown issue
				if ( md5_file( $local_file . '.tmp' ) !== $json[ 'target_md5' ] ) {
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Failed to pull optimized img iteself: file md5 dismatch, server md5: ' . $json[ 'target_md5' ] ) ;

					// update status to failed
					$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE id = %d " ;
					$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;
					// Update child images
					$q = "UPDATE $this->_table_img_optm SET optm_status = %s WHERE root_id = %d " ;
					$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_FAILED, $row_img->id ) ) ) ;

					// Notify server to update status
					LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PULL_IMG_FAILED, $data, $server, true ) ;

					return ; // exit from running pull process
				}

				// log webp file saved size summary
				$target_size = filesize( $local_file . '.tmp' ) ;
				$target_saved = $ori_size - $target_size ;
				if ( $target_saved > 0 ) {
					$optm_info[ 'ori_total' ] += $ori_size ;
					$optm_info[ 'ori_saved' ] += $target_saved ;
				}
				else {
					$target_saved = 0 ;
				}

				// Backup ori img
				$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
				$bk_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension ;
				rename( $local_file, $bk_file ) ;

				// Replace ori img
				rename( $local_file . '.tmp', $local_file ) ;

				LiteSpeed_Cache_Log::debug( '[Img_Optm] Pulled optimized img: ' . $local_file ) ;
			}

			LiteSpeed_Cache_Log::debug2( '[Img_Optm] Update _table_img_optm record [id] ' . $row_img->id ) ;

			// Update pulled status
			$q = "UPDATE $this->_table_img_optm SET optm_status = %s, target_filesize = %d, target_saved = %d, webp_filesize = %d, webp_saved = %d WHERE id = %d " ;
			$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_PULLED, $target_size, $target_saved, $webp_size, $webp_saved, $row_img->id ) ) ) ;

			// Update child images
			$q = "UPDATE $this->_table_img_optm SET optm_status = %s, target_filesize = %d, target_saved = %d, webp_filesize = %d, webp_saved = %d WHERE root_id = %d " ;
			$child_count = $wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_PULLED, $target_size, $target_saved, $webp_size, $webp_saved, $row_img->id ) ) ) ;

			/**
			 * Update size saved info
			 * @since  1.6.5
			 */
			$optm_info = serialize( $optm_info ) ;
			if ( ! empty( $row_img->b_meta_id ) ) {
				$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d " ;
				$wpdb->query( $wpdb->prepare( $q, array( $optm_info, $row_img->b_meta_id ) ) ) ;
			}
			else {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] New size info [pid] ' . $row_img->post_id ) ;
				$q = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )" ;
				$wpdb->query( $wpdb->prepare( $q, array( $row_img->post_id, self::DB_IMG_OPTIMIZE_SIZE, $optm_info ) ) ) ;
			}

			// Update size saved info of child images
			if ( $child_count ) {
				LiteSpeed_Cache_Log::debug( '[Img_Optm] Proceed child images [total] ' . $child_count ) ;

				$q = "SELECT a.*, b.meta_id as b_meta_id
					FROM $this->_table_img_optm a
					LEFT JOIN $wpdb->postmeta b ON b.post_id = a.post_id AND b.meta_key = %s
					WHERE a.root_id = %d GROUP BY a.post_id" ;
				$pids = array() ;
				$tmp = $wpdb->get_results( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_SIZE, $row_img->id ) ) ) ;
				$pids_to_update = array() ;
				$pids_data_to_insert = array() ;
				foreach ( $tmp as $v ) {
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
		}

		// Update guidance
		if ( $this->get_guidance_pos() == 3 ) {
			$this->_update_guidance_pos( 4 ) ;
		}

		// Check if there is still task in queue
		$q = "SELECT * FROM $this->_table_img_optm WHERE root_id = 0 AND optm_status = %s LIMIT 1" ;
		$tmp = $wpdb->get_row( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ) ;
		if ( $tmp ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] Task in queue, to be continued...' ) ;
			return 'to_be_continued' ;
		}

		// If all pulled, update tag to done
		LiteSpeed_Cache_Log::debug( '[Img_Optm] Marked pull status to all pulled' ) ;
		update_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ;
	}

	/**
	 * Check if need to do a pull for optimized img
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function check_need_pull()
	{
		$tag = get_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL ) ;
		return defined( 'DOING_CRON' ) && $tag && $tag === self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ;
	}

	/**
	 * Show an image's optm status
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public function check_img()
	{
		$pid = $_POST[ 'data' ] ;

		LiteSpeed_Cache_Log::debug( '[Img_Optm] Check image [ID] ' . $pid ) ;

		$data = array() ;

		$data[ 'img_count' ] = $this->img_count() ;

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
					'server'	=> $v->server,
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

		if ( function_exists( 'is_serialized' ) && ! is_serialized( $v->meta_value ) ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] bypassed parsing meta due to wrong meta_value: pid ' . $v->post_id ) ;
			return false ;
		}

		try {
			$meta_value = @unserialize( $v->meta_value ) ;
		}
		catch ( \Exception $e ) {
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
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_REQUEST_DESTROY_UNFINISHED ) ;

		// confirm link will be displayed by Admin_API automatically
		if ( is_array( $json ) && $json ) {
			LiteSpeed_Cache_Log::debug( '[Img_Optm] cmd result', $json ) ;
		}

		// If failed to run request to IAPI
		if ( ! is_array( $json ) || empty( $json[ 'success' ] ) ) {

			// For other errors that Admin_API didn't take
			if ( ! is_array( $json ) && $json !== null ) {
				LiteSpeed_Cache_Admin_Display::error( $json ) ;

				LiteSpeed_Cache_Log::debug( '[Img_Optm] err ', $json ) ;
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


		$msg = __( 'Destroy unfinished data successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

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
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_REQUEST_DESTROY ) ;

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
			exit( 'Destroy callback timeout ( 300 seconds )' ) ;
		}

		// Start deleting files
		$q = "SELECT * FROM $this->_table_img_optm WHERE optm_status = %s" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ;
		foreach ( $list as $v ) {
			$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $v->src ;

			// del webp
			file_exists( $local_file . '.webp' ) && unlink( $local_file . '.webp' ) ;
			file_exists( $local_file . '.optm.webp' ) && unlink( $local_file . '.optm.webp' ) ;

			$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
			$local_filename = substr( $local_file, 0, - strlen( $extension ) - 1 ) ;
			$bk_file = $local_filename . '.bk.' . $extension ;
			$bk_optm_file = $local_filename . '.bk.optm.' . $extension ;

			// del optimized ori
			if ( file_exists( $bk_file ) ) {
				unlink( $local_file ) ;
				rename( $bk_file, $local_file ) ;
			}
			file_exists( $bk_optm_file ) && unlink( $bk_optm_file ) ;
		}

		// Delete optm info
		$q = "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'litespeed-optimize%'" ;
		$wpdb->query( $q ) ;

		// Delete img_optm table
		LiteSpeed_Cache_Data::get_instance()->delete_tb_img_optm() ;

		// Clear credit info
		delete_option( self::DB_IMG_OPTM_SUMMARY ) ;

		$this->_update_guidance_pos( 1 ) ;

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
			$optm_meta = $optm_data_list[ $v->post_id ] = unserialize( $v->cmeta_value ) ;
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
		if ( $json === null ) {
			return ;
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
	 * Update client credit info
	 *
	 * @since 1.6.5
	 * @access private
	 */
	private function _update_credit( $credit )
	{
		$summary = get_option( self::DB_IMG_OPTM_SUMMARY, array() ) ;
		$summary[ 'credit' ] = $credit ;

		update_option( self::DB_IMG_OPTM_SUMMARY, $summary ) ;
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
		$q = "SELECT * FROM $this->_table_img_optm WHERE optm_status = %s" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ;

		$i = 0 ;
		foreach ( $list as $v ) {
			$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $v->src ;

			$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
			$local_filename = substr( $local_file, 0, - strlen( $extension ) - 1 ) ;
			$bk_file = $local_filename . '.bk.' . $extension ;
			$bk_optm_file = $local_filename . '.bk.optm.' . $extension ;

			// switch to ori
			if ( $type === self::TYPE_IMG_BATCH_SWITCH_ORI ) {
				if ( ! file_exists( $bk_file ) ) {
					continue ;
				}

				$i ++ ;

				rename( $local_file, $bk_optm_file ) ;
				rename( $bk_file, $local_file ) ;
			}
			// switch to optm
			elseif ( $type === self::TYPE_IMG_BATCH_SWITCH_OPTM ) {
				if ( ! file_exists( $bk_optm_file ) ) {
					continue ;
				}

				$i ++ ;

				rename( $local_file, $bk_file ) ;
				rename( $bk_optm_file, $local_file ) ;
			}
		}

		LiteSpeed_Cache_Log::debug( '[Img_Optm] batch switched images total: ' . $i ) ;

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
		$q = "SELECT * FROM $this->_table_img_optm WHERE optm_status = %s AND post_id = %d" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_PULLED, $pid ) ) ) ;

		$msg = 'Unknown Msg' ;

		foreach ( $list as $v ) {
			$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $v->src ;

			// to switch webp file
			if ( $switch_type === 'webp' ) {
				if ( file_exists( $local_file . '.webp' ) ) {
					rename( $local_file . '.webp', $local_file . '.optm.webp' ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Disabled WebP: ' . $local_file ) ;

					$msg = __( 'Disabled WebP file successfully.', 'litespeed-cache' ) ;
				}
				elseif ( file_exists( $local_file . '.optm.webp' ) ) {
					rename( $local_file . '.optm.webp', $local_file . '.webp' ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Enable WebP: ' . $local_file ) ;

					$msg = __( 'Enabled WebP file successfully.', 'litespeed-cache' ) ;
				}
			}
			// to switch original file
			else {
				$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
				$local_filename = substr( $local_file, 0, - strlen( $extension ) - 1 ) ;
				$bk_file = $local_filename . '.bk.' . $extension ;
				$bk_optm_file = $local_filename . '.bk.optm.' . $extension ;

				// revert ori back
				if ( file_exists( $bk_file ) ) {
					rename( $local_file, $bk_optm_file ) ;
					rename( $bk_file, $local_file ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Restore original img: ' . $bk_file ) ;

					$msg = __( 'Restored original file successfully.', 'litespeed-cache' ) ;
				}
				elseif ( file_exists( $bk_optm_file ) ) {
					rename( $local_file, $bk_file ) ;
					rename( $bk_optm_file, $local_file ) ;
					LiteSpeed_Cache_Log::debug( '[Img_Optm] Switch to optm img: ' . $local_file ) ;

					$msg = __( 'Switched to optimized file successfully.', 'litespeed-cache' ) ;
				}

			}
		}

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
				if ( $result === 'to_be_continued' ) {
					$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_PULL ) ;
					LiteSpeed_Cache_Admin::redirect( html_entity_decode( $link ) ) ;
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
	 * Get the current guidance step
	 *
	 * @since  2.0
	 * @access public
	 */
	public function get_guidance_pos()
	{
		$guide_status = get_option( LiteSpeed_Cache_Config::ITEM_GUIDE ) ;

		$current_step = 'done' ;
		if ( ! $guide_status || empty( $guide_status[ 'img_optm' ] ) || $guide_status[ 'img_optm' ] !== 'done' ) {
			$current_step = empty( $guide_status[ 'img_optm' ] ) ? 1 : $guide_status[ 'img_optm' ] ;
		}

		return $current_step ;
	}

	/**
	 * Update current guidance step
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _update_guidance_pos( $pos )
	{
		$guide_status = get_option( LiteSpeed_Cache_Config::ITEM_GUIDE ) ;

		if ( ! $guide_status ) {
			$guide_status = array() ;
		}

		if ( ! empty( $guide_status[ 'img_optm' ] ) && $guide_status[ 'img_optm' ] == $pos ) {
			LiteSpeed_Cache_Log::debug2( '[Img_Optm] _update_guidance_pos: bypassed due to same pos [step] ' . $pos ) ;
			return ;
		}

		$guide_status[ 'img_optm' ] = $pos ;

		LiteSpeed_Cache_Log::debug( '[Img_Optm] _update_guidance_pos [step] ' . $pos ) ;

		update_option( LiteSpeed_Cache_Config::ITEM_GUIDE, $guide_status ) ;
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