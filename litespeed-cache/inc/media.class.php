<?php

/**
 * The class to operate media data.
 *
 * @since 		1.4
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_Media
{
	private static $_instance ;

	const LAZY_LIB = '/min/lazyload.js' ;

	const TYPE_IMG_OPTIMIZE = 'img_optm' ;
	const OPT_CRON_RUN = 'img_optm_cron_run' ; // last cron running time

	const DB_IMG_OPTIMIZE_DATA = 'litespeed-optimize-data' ;
	const DB_IMG_OPTIMIZE_STATUS = 'litespeed-optimize-status' ;
	const DB_IMG_OPTIMIZE_STATUS_REQUESTED = 'requested' ;
	const DB_IMG_OPTIMIZE_STATUS_NOTIFIED = 'notified' ;
	const DB_IMG_OPTIMIZE_STATUS_PULLED = 'pulled' ;

	private $content ;
	private $wp_upload_dir ;
	private $tmp_pid ;
	private $tmp_path ;
	private $_img_in_queue = array() ;
	private $_img_total = 0 ;

	private $cfg_img_webp ;

	/**
	 * Init
	 *
	 * @since  1.4
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( 'Media init' ) ;

		$this->_static_request_check() ;

		$this->wp_upload_dir = wp_upload_dir() ;
		$this->cfg_img_webp = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP ) ;

		// Due to ajax call doesn't send correct accept header, have to limit webp to HTML only
		if ( $this->cfg_img_webp ) {
			/**
			 * Add vary filter
			 * @since  1.6.2
			 */
			// Moved to htaccess
			// add_filter( 'litespeed_vary', array( $this, 'vary_add' ) ) ;

			//
			if ( $this->webp_support() ) {
				// Hook to srcset
				if ( function_exists( 'wp_calculate_image_srcset' ) ) {
					add_filter( 'wp_calculate_image_srcset', array( $this, 'webp_srcset' ), 988 ) ;
				}
				// Hook to mime icon
				// add_filter( 'wp_get_attachment_image_src', array( $this, 'webp_attach_img_src' ), 988 ) ;// todo: need to check why not
				// add_filter( 'wp_get_attachment_url', array( $this, 'webp_url' ), 988 ) ; // disabled to avoid wp-admin display
			}
		}

		add_action( 'litspeed_after_admin_init', array( $this, 'after_admin_init' ) ) ;
	}

	/**
	 * Register admin menu
	 *
	 * @since 1.6.2
	 * @access public
	 */
	public function after_admin_init()
	{
		add_filter( 'media_row_actions', array( $this, 'media_row_actions' ), 10, 2 ) ;
	}

	/**
	 * Register admin menu
	 *
	 * @since 1.6.2
	 * @access public
	 */
	public function media_row_actions( $actions, $post )
	{
		$local_file = get_attached_file( $post->ID ) ;

		$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, 'webp' . $post->ID ) ;
		$desc = false ;
		if ( file_exists( $local_file . '.webp' ) ) {
			$desc = __( 'Disable Webp', 'litespeed-cache' ) ;
		}
		elseif ( file_exists( $local_file . '.optm.webp' ) ) {
			$desc = __( 'Enable Webp', 'litespeed-cache' ) ;
		}
		if ( $desc ) {
			$actions[ 'webp_bypass' ] = sprintf( '<a href="%s">%s</a>', $link, $desc ) ;
		}

		$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
		$bk_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension ;
		$bk_optm_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.optm.' . $extension ;

		$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, 'orig' . $post->ID ) ;
		$desc = false ;
		if ( file_exists( $bk_file ) ) {
			$desc = __( 'Restore Original File', 'litespeed-cache' ) ;
		}
		elseif ( file_exists( $bk_optm_file ) ) {
			$desc = __( 'Swith To Optimized File', 'litespeed-cache' ) ;
		}
		if ( $desc ) {
			$actions[ 'ori_recover' ] = sprintf( '<a href="%s">%s</a>', $link, $desc ) ;
		}

		return $actions ;
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
		$local_file = get_attached_file( $pid ) ;

		//todo: also change child images from get_image_sizes()

		// to switch webp file
		if ( substr( $type, 0, 4 ) === 'webp' ) {
			if ( file_exists( $local_file . '.webp' ) ) {
				rename( $local_file . '.webp', $local_file . '.optm.webp' ) ;
				$msg = __( 'Disabled webp file successfully.', 'litespeed-cache' ) ;
			}
			elseif ( file_exists( $local_file . '.optm.webp' ) ) {
				rename( $local_file . '.optm.webp', $local_file . '.webp' ) ;
				$msg = __( 'Enable webp file successfully.', 'litespeed-cache' ) ;
			}

		}
		// to switch original file
		else {
			$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
			$bk_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension ;
			$bk_optm_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.optm.' . $extension ;

			// revert ori back
			if ( file_exists( $bk_file ) ) {
				rename( $local_file, $bk_optm_file ) ;
				rename( $bk_file, $local_file ) ;
				$msg = __( 'Restored original file successfully.', 'litespeed-cache' ) ;
			}
			elseif ( file_exists( $bk_optm_file ) ) {
				rename( $local_file, $bk_file ) ;
				rename( $bk_optm_file, $local_file ) ;
				$msg = __( 'Swithed to optimized file successfully.', 'litespeed-cache' ) ;
			}

		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg ) ;
	}

	/**
	 * Get wp size info
	 *
	 * @since 1.6.2
	 * @access private
	 * @return array $sizes Data for all currently-registered image sizes.
	 */
	private function get_image_sizes() {
		global $_wp_additional_image_sizes ;
		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $_size ][ 'width' ] = get_option( $_size . '_size_w' ) ;
				$sizes[ $_size ][ 'height' ] = get_option( $_size . '_size_h' ) ;
				$sizes[ $_size ][ 'crop' ] = (bool) get_option( $_size . '_crop' ) ;
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width' => $_wp_additional_image_sizes[ $_size ][ 'width' ],
					'height' => $_wp_additional_image_sizes[ $_size ][ 'height' ],
					'crop' =>  $_wp_additional_image_sizes[ $_size ][ 'crop' ]
				) ;
			}
		}

		return $sizes ;
	}


	/**
	 * Exclude role from optimization filter
	 *
	 * @since  1.6.2
	 * @access public
	 */
	private function webp_support()
	{
		if ( empty( $_SERVER[ 'HTTP_ACCEPT' ] ) || strpos( $_SERVER[ 'HTTP_ACCEPT' ], 'image/webp' ) === false ) {
			return false ;
		}

		return true ;
	}

	/**
	 * Check if the request is for static file
	 *
	 * @since  1.4
	 * @access private
	 * @return  string The static file content
	 */
	private function _static_request_check()
	{
		// This request is for js/css_async.js
		if ( strpos( $_SERVER[ 'REQUEST_URI' ], self::LAZY_LIB ) !== false ) {
			LiteSpeed_Cache_Log::debug( 'Media run lazyload lib' ) ;

			LiteSpeed_Cache_Control::set_cacheable() ;
			LiteSpeed_Cache_Control::set_no_vary() ;
			LiteSpeed_Cache_Control::set_custom_ttl( 8640000 ) ;
			LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_MIN . '_LAZY' ) ;

			$file = LSWCP_DIR . 'js/lazyload.min.js' ;

			header( 'Content-Length: ' . filesize( $file ) ) ;
			header( 'Content-Type: application/x-javascript; charset=utf-8' ) ;

			echo file_get_contents( $file ) ;
			exit ;
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_IMG_OPTIMIZE :
				$instance->_img_optimize() ;
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
	 * Check if fetch cron is running
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _cron_running()
	{
		$last_run = get_option( self::OPT_CRON_RUN ) ;
		if ( ! $last_run || time() - $last_run > 120 ) {
			return false ;
		}

		return true ;
	}

	/**
	 * Update fetch cron timestamp tag
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _update_cron_running()
	{
		update_option( self::OPT_CRON_RUN, time() ) ;
	}

	/**
	 * Pull optimized img
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function pull_optimized_img()
	{
		LiteSpeed_Cache_Log::debug( 'Media: Cron pull_optimized_img started' ) ;
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
		if ( $this->_cron_running() ) {
			LiteSpeed_Cache_Log::debug( 'Media: fetch cron is running' ) ;
			return ;
		}

		global $wpdb ;

		$q = "SELECT a.meta_id, a.post_id, b.meta_id as bmeta_id, b.meta_value
			FROM $wpdb->postmeta a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.post_id
			WHERE a.meta_key = %s AND a.meta_value = %s AND b.meta_key = %s
			ORDER BY a.post_id DESC
			LIMIT 10
		" ;
		$cond = array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED, self::DB_IMG_OPTIMIZE_DATA ) ;
		$meta_value_list = $wpdb->get_results( $wpdb->prepare( $q, $cond ) ) ;

		foreach ( $meta_value_list as $v ) {
			$meta_value = unserialize( $v->meta_value ) ;

			// Start fetching
			foreach ( $meta_value as $md5 => $v2 ) {

				/**
				 * Update cron timestamp to avoid duplicated running
				 * @since  1.6.2
				 */
				$this->_update_cron_running() ;

				if ( $v2[ 1 ] === self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) {
					$server = $v2[ 2 ] ;
					// send fetch request
					$data = array(
						'pid' => $v->post_id,
						'src_md5' => $md5,
					) ;
					$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::SAPI_ACTION_PULL_IMG, $data, $server ) ;
					if ( empty( $json[ 'webp' ] ) ) {
						LiteSpeed_Cache_Log::debug( 'Media: Failed to pull optimized img: ', $json ) ;
						return ;
					}

					$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $v2[ 0 ] ;

					// Fetch webp image
					file_put_contents( $local_file . '.webp', file_get_contents( $json[ 'webp' ] ) ) ;
					// Unknown issue
					if ( md5_file( $local_file . '.webp' ) !== $json[ 'webp_md5' ] ) {
						LiteSpeed_Cache_Log::debug( 'Media: Failed to pull optimized img webp: file md5 dismatch, server md5: ' . $json[ 'webp_md5' ] ) ;
						return ;// exit from running pull process
					}

					LiteSpeed_Cache_Log::debug( 'Media: Pulled optimized img webp: ' . $local_file . '.webp' ) ;

					// Fetch optimized image itself
					if ( ! empty( $json[ 'target_file' ] ) ) {
						file_put_contents( $local_file . '.tmp', file_get_contents( $json[ 'target_file' ] ) ) ;
						// Unknown issue
						if ( md5_file( $local_file . '.tmp' ) !== $json[ 'target_md5' ] ) {
							LiteSpeed_Cache_Log::debug( 'Media: Failed to pull optimized img iteself: file md5 dismatch, server md5: ' . $json[ 'target_md5' ] ) ;
							return ; // exit from running pull process
						}

						// Backup ori img
						$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
						$bk_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension ;
						rename( $local_file, $bk_file ) ;

						// Replace ori img
						rename( $local_file . '.tmp', $local_file ) ;

						LiteSpeed_Cache_Log::debug( 'Media: Pulled optimized img: ' . $local_file ) ;
					}


					// Update meta value
					$meta_value[ $md5 ][ 1 ] = self::DB_IMG_OPTIMIZE_STATUS_PULLED ;
				}
			}

			// Update data tag
			$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d ";
			$wpdb->query( $wpdb->prepare( $q, array( serialize( $meta_value ), $v->bmeta_id ) ) ) ;

			// Update status tag if all pulled or still has requested img
			$has_notify = false ;// it may be bypassed in above loop
			$has_request = false ;
			foreach ( $meta_value as $v2 ) {
				if ( $v2[ 1 ] === self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) {
					$has_request = true ;
				}
				if ( $v2[ 1 ] === self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) {
					$has_notify = true ;
				}
			}

			$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d ";

			// Update pid status
			if ( ! $has_notify ) {
				$new_status = self::DB_IMG_OPTIMIZE_STATUS_PULLED ;
				if ( $has_request ) {
					$new_status = self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ;
				}
				$wpdb->query( $wpdb->prepare( $q, array( $new_status, $v->meta_id ) ) ) ;
			}
		}

		// If all pulled, update tag to done
		$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1" ;
		$meta_value_list = $wpdb->get_row( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ) ) ;
		if ( ! $meta_value_list ) {
			update_option( LiteSpeed_Cache_Config::ITEM_MEDIA_NEED_PULL, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ;
		}
	}

	/**
	 * Check if need to do a pull for optimized img
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function check_need_pull()
	{
		$tag = get_option( LiteSpeed_Cache_Config::ITEM_MEDIA_NEED_PULL ) ;
		return defined( 'DOING_CRON' ) && $tag && $tag === self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ;
	}

	/**
	 * LiteSpeed Child server notify Client img has been optimized and can be pulled
	 *
	 * @since  1.6
	 * @access private
	 */
	public function notify_img_optimized()
	{
		$notified_data = unserialize( base64_decode( $_POST[ 'data' ] ) ) ;
		if ( empty( $notified_data ) || ! is_array( $notified_data ) ) {
			LiteSpeed_Cache_Log::debug( 'Media: notify_img_optimized exit: no notified data' ) ;
			exit( json_encode( 'no notified data' ) ) ;
		}

		if ( empty( $_POST[ 'server' ] ) || substr( $_POST[ 'server' ], -21 ) !== 'api.litespeedtech.com' ) {
			LiteSpeed_Cache_Log::debug( 'Media: notify_img_optimized exit: no/wrong server' ) ;
			exit( json_encode( 'no/wrong server' ) ) ;
		}
		$server = $_POST[ 'server' ] ;

		global $wpdb ;

		$pids = array_keys( $notified_data ) ;

		$q = "SELECT meta_id, post_id, meta_value FROM $wpdb->postmeta WHERE post_id IN ( " . implode( ',', array_fill( 0, count( $pids ), '%d' ) ) . " ) AND meta_key = %s" ;
		$meta_value_list = $wpdb->get_results( $wpdb->prepare( $q, array_merge( $pids, array( self::DB_IMG_OPTIMIZE_DATA ) ) ) ) ;

		$need_pull = false ;

		foreach ( $meta_value_list as $v ) {
			$changed = false ;
			$md52src_list = unserialize( $v->meta_value ) ;
			// replace src tag from requested to notified
			foreach ( $md52src_list as $md5 => $v2 ) {
				if ( in_array( $md5, $notified_data[ $v->post_id ] ) && $v2[ 1 ] === self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) {
					$md52src_list[ $md5 ][ 1 ] = self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ;
					$md52src_list[ $md5 ][ 2 ] = $server ;
					$changed = true ;
				}
			}

			if ( ! $changed ) {
				LiteSpeed_Cache_Log::debug( 'Media: notify_img_optimized continue: no change meta' ) ;
				continue ;
			}

			// Save meta data
			$md52src_list = serialize( $md52src_list ) ;
			$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d" ;
			$wpdb->query( $wpdb->prepare( $q, array( $md52src_list, $v->meta_id ) ) ) ;

			// Save meta status to server finished to get client fetch it
			$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE post_id = %d AND meta_key = %s" ;
			$wpdb->query( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED, $v->post_id, self::DB_IMG_OPTIMIZE_STATUS ) ) ) ;

			LiteSpeed_Cache_Log::debug( 'Media: notify_img_optimized update post_meta pid: ' . $v->post_id ) ;

			$need_pull = true ;
		}

		if ( $need_pull ) {
			update_option( LiteSpeed_Cache_Config::ITEM_MEDIA_NEED_PULL, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ;
		}

		echo json_encode( array( 'count' => count( $notified_data ) ) ) ;
		exit() ;
	}

	/**
	 * Push raw img to LiteSpeed server
	 *
	 * @since 1.6
	 * @access private
	 */
	private function _img_optimize()
	{

		LiteSpeed_Cache_Log::debug( 'Media preparing images to push' ) ;

		global $wpdb ;
		// Get images
		$q = "SELECT b.post_id, b.meta_value
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			LEFT JOIN $wpdb->postmeta c ON c.post_id = a.ID AND c.meta_key = %s
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
				AND c.post_id IS NULL
			ORDER BY a.ID DESC
			LIMIT %d
			" ;
		$q = $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, apply_filters( 'litespeed_img_optimize_max_rows', 100 ) ) ) ;

		$img_set = array() ;
		$list = $wpdb->get_results( $q ) ;
		if ( ! $list ) {
			LiteSpeed_Cache_Log::debug( 'Media optimize bypass: no image found' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'Media found images: ' . count( $list ) ) ;

		foreach ( $list as $v ) {

			if ( ! $v->meta_value ) {
				LiteSpeed_Cache_Log::debug( 'Media bypass image due to no meta_value: pid ' . $v->post_id ) ;
				continue ;
			}

			try {
				$meta_value = unserialize( $v->meta_value ) ;
			}
			catch ( \Exception $e ) {
				LiteSpeed_Cache_Log::debug( 'Media bypass image due to meta_value not json: pid ' . $v->post_id ) ;
				continue ;
			}

			if ( empty( $meta_value[ 'file' ] ) ) {
				LiteSpeed_Cache_Log::debug( 'Media bypass image due to no ori file: pid ' . $v->post_id ) ;
				continue ;
			}

			// push orig image to queue
			$this->tmp_pid = $v->post_id ;
			$this->tmp_path = pathinfo( $meta_value[ 'file' ], PATHINFO_DIRNAME ) . '/' ;
			$this->_img_queue( $meta_value, true ) ;
			if ( ! empty( $meta_value[ 'sizes' ] ) ) {
				array_map( array( $this, '_img_queue' ), $meta_value[ 'sizes' ] ) ;
			}

		}

		// push to LiteSpeed server
		if ( ! empty( $this->_img_in_queue ) ) {
			$total_groups = count( $this->_img_in_queue ) ;
			LiteSpeed_Cache_Log::debug( 'Media prepared images to push: groups ' . $total_groups . ' images ' . $this->_img_total ) ;

			// Push to LiteSpeed server
			$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::SAPI_ACTION_REQUEST_OPTIMIZE, LiteSpeed_Cache_Utility::arr2str( $this->_img_in_queue ) ) ;

			if ( ! is_array( $json ) ) {
				LiteSpeed_Cache_Log::debug( 'Media: Failed to post to LiteSpeed server ', $json ) ;
				$msg = sprintf( __( 'Failed to push to LiteSpeed server: %s', 'litespeed-cache' ), $json ) ;
				LiteSpeed_Cache_Admin_Display::error( $msg ) ;
				return ;
			}

			// Mark them as requested
			$pids = $json[ 'pids' ] ;
			if ( empty( $pids ) || ! is_array( $pids ) ) {
				LiteSpeed_Cache_Log::debug( 'Media: Failed to parse data from LiteSpeed server ', $pids ) ;
				$msg = sprintf( __( 'Failed to parse data from LiteSpeed server: %s', 'litespeed-cache' ), $pids ) ;
				LiteSpeed_Cache_Admin_Display::error( $msg ) ;
				return ;
			}

			LiteSpeed_Cache_Log::debug( 'Media: posts data from LiteSpeed server count: ' . count( $pids ) ) ;

			// Exclude those who have meta already
			$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s and post_id in ( " . implode( ',', array_fill( 0, count( $pids ), '%s' ) ) . " )" ;
			$tmp = $wpdb->get_results( $wpdb->prepare( $q, array_merge( array( self::DB_IMG_OPTIMIZE_STATUS ), $pids ) ) ) ;
			$exists_pids = array() ;
			foreach ( $tmp as $v ) {
				$exists_pids[] = $v->post_id ;
			}
			if ( $exists_pids ) {
				LiteSpeed_Cache_Log::debug( 'Media: existing posts data from LiteSpeed server count: ' . count( $exists_pids ) ) ;
			}
			$pids = array_diff( $pids, $exists_pids ) ;

			if ( ! $pids ) {
				LiteSpeed_Cache_Log::debug( 'Media: Failed to store data from LiteSpeed server with empty pids' ) ;
				LiteSpeed_Cache_Admin_Display::error( __( 'Post data is empty.', 'litespeed-cache' ) ) ;
				return ;
			}

			LiteSpeed_Cache_Log::debug( 'Media: diff posts data from LiteSpeed server count: ' . count( $pids ) ) ;

			$q = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES " ;
			$data_to_add = array() ;
			foreach ( $pids as $pid ) {
				$data_to_add[] = $pid ;
				$data_to_add[] = self::DB_IMG_OPTIMIZE_STATUS ;
				$data_to_add[] = self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ;
				$data_to_add[] = $pid ;
				$data_to_add[] = self::DB_IMG_OPTIMIZE_DATA ;
				$md52src_list = array() ;
				foreach ( $this->_img_in_queue[ $pid ] as $md5 => $src_data ) {
					$md52src_list[ $md5 ] = array( $src_data[ 'file' ], self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) ;
				}
				$data_to_add[] = serialize( $md52src_list ) ;
			}
			// Add placeholder
			$q .= implode( ',', array_map(
				function( $el ) { return '(' . implode( ',', $el ) . ')' ; },
				array_chunk( array_fill( 0, count( $data_to_add ), '%s' ), 3 )
			) ) ;
			// Store data
			$wpdb->query( $wpdb->prepare( $q, $data_to_add ) ) ;


			$accepted_groups = count( $pids ) ;
			$accepted_imgs = $json[ 'total' ] ;

			$msg = sprintf( __( 'Pushed %1$s groups with %2$s images to LiteSpeed optimization server, accepted %3$s groups with %4$s images.', 'litespeed-cache' ), $total_groups, $this->_img_total, $accepted_groups, $accepted_imgs ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
		}
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
			LiteSpeed_Cache_Log::debug2( 'Media bypass image due to lack of file/w/h: pid ' . $this->tmp_pid, $meta_value ) ;
			return ;
		}

		if ( ! $ori_file ) {
			$meta_value[ 'file' ] = $this->tmp_path . $meta_value[ 'file' ] ;
		}

		// check file exists or not
		$real_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $meta_value[ 'file' ] ;
		if ( ! file_exists( $real_file ) ) {
			LiteSpeed_Cache_Log::debug2( 'Media bypass image due to file not exist: pid ' . $this->tmp_pid . ' ' . $real_file ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug2( 'Media adding image: pid ' . $this->tmp_pid ) ;

		$img_info = array(
			'url'	=> $this->wp_upload_dir[ 'baseurl' ] . '/' . $meta_value[ 'file' ],
			'file'	=> $meta_value[ 'file' ], // not needed in LiteSpeed sapi, just leave for local storage after post
			'width'	=> $meta_value[ 'width' ],
			'height'	=> $meta_value[ 'height' ],
			'mime_type'	=> ! empty( $meta_value[ 'mime-type' ] ) ? $meta_value[ 'mime-type' ] : '' ,
		) ;
		$md5 = md5_file( $real_file ) ;

		if ( empty( $this->_img_in_queue[ $this->tmp_pid ] ) ) {
			$this->_img_in_queue[ $this->tmp_pid ] = array() ;
		}
		$this->_img_in_queue[ $this->tmp_pid ][ $md5 ] = $img_info ;
		$this->_img_total ++ ;
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
			LEFT JOIN $wpdb->postmeta c ON c.post_id = a.ID
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
				AND c.meta_key = %s
				AND c.meta_value= %s
			" ;
		$total_requested = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) ) ) ;
		$total_server_finished = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ) ) ;
		$total_pulled = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_PULLED ) ) ) ;

		$total_not_requested = $total_img - $total_requested - $total_server_finished - $total_pulled ;

		return array(
			'total_img'	=> $total_img,
			'total_not_requested'	=> $total_not_requested,
			'total_requested'	=> $total_requested,
			'total_server_finished'	=> $total_server_finished,
			'total_pulled'	=> $total_pulled,
		) ;
	}

	/**
	 * Run lazy load process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * Only do for main page. Do NOT do for esi or dynamic content.
	 *
	 * @since  1.4
	 * @access public
	 * @return  string The buffer
	 */
	public static function finalize( $content )
	{
		if ( defined( 'LITESPEED_NO_LAZY' ) ) {
			LiteSpeed_Cache_Log::debug2( 'Media bypass: NO_LAZY const' ) ;
			return $content ;
		}

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			LiteSpeed_Cache_Log::debug2( 'Media bypass: Not frontend HTML type' ) ;
			return $content ;
		}

		LiteSpeed_Cache_Log::debug( 'Media start' ) ;

		$instance = self::get_instance() ;
		$instance->content = $content ;

		$instance->_finalize() ;
		return $instance->content ;
	}

	/**
	 * Run lazyload replacement for images in buffer
	 *
	 * @since  1.4
	 * @access private
	 */
	private function _finalize()
	{
		$cfg_img_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY ) ;
		$cfg_iframe_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IFRAME_LAZY ) ;

		if ( $cfg_img_lazy ) {
			list( $src_list, $html_list ) = $this->_parse_img() ;
			$html_list_ori = $html_list ;
		}

		/**
		 * Use webp for optimized images
		 * @since 1.6.2
		 */
		if ( $this->cfg_img_webp ) {
			$this->_replace_buffer_img_webp() ;
		}

		// image lazy load
		if ( $cfg_img_lazy ) {

			$placeholder = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY_PLACEHOLDER ) ?: LITESPEED_PLACEHOLDER ;

			foreach ( $html_list as $k => $v ) {
				$snippet = '<noscript>' . $v . '</noscript>' ;
				$v = str_replace( array( ' src=', ' srcset=', ' sizes=' ), array( ' data-src=', ' data-srcset=', ' data-sizes=' ), $v ) ;
				$v = str_replace( '<img ', '<img data-lazyloaded="1" src="' . $placeholder . '" ', $v ) ;
				$snippet = $v . $snippet ;

				$html_list[ $k ] = $snippet ;
			}
		}

		if ( $cfg_img_lazy ) {
			$this->content = str_replace( $html_list_ori, $html_list, $this->content ) ;
		}

		// iframe lazy load
		if ( $cfg_iframe_lazy ) {
			$html_list = $this->_parse_iframe() ;
			$html_list_ori = $html_list ;

			foreach ( $html_list as $k => $v ) {
				$snippet = '<noscript>' . $v . '</noscript>' ;
				$v = str_replace( ' src=', ' data-src=', $v ) ;
				$v = str_replace( '<iframe ', '<iframe data-lazyloaded="1" src="about:blank" ', $v ) ;
				$snippet = $v . $snippet ;

				$html_list[ $k ] = $snippet ;
			}

			$this->content = str_replace( $html_list_ori, $html_list, $this->content ) ;
		}

		// Include lazyload lib js and init lazyload
		if ( $cfg_img_lazy || $cfg_iframe_lazy ) {
			$lazy_lib_url = LiteSpeed_Cache_Utility::get_permalink_url( self::LAZY_LIB ) ;
			$this->content = str_replace( '</body>', '<script src="' . $lazy_lib_url . '"></script></body>', $this->content ) ;
		}
	}

	/**
	 * Parse img src
	 *
	 * @since  1.4
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_img()
	{
		/**
		 * Exclude list
		 * @since 1.5
		 */
		$excludes = apply_filters( 'litespeed_cache_media_lazy_img_excludes', get_option( LiteSpeed_Cache_Config::ITEM_MEDIA_LAZY_IMG_EXC ) ) ;
		if ( $excludes ) {
			$excludes = explode( "\n", $excludes ) ;
		}

		$src_list = array() ;
		$html_list = array() ;

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<img \s*([^>]+)/?>#isU', $content, $matches, PREG_SET_ORDER ) ;
		foreach ( $matches as $match ) {
			$attrs = LiteSpeed_Cache_Utility::parse_attr( $match[ 1 ] ) ;

			if ( empty( $attrs[ 'src' ] ) ) {
				continue ;
			}

			/**
			 * Add src validation to bypass base64 img src
			 * @since  1.6
			 */
			if ( strpos( $attrs[ 'src' ], 'base64' ) !== false || substr( $attrs[ 'src' ], 0, 5 ) === 'data:' ) {
				LiteSpeed_Cache_Log::debug2( 'Media bypassed base64 img' ) ;
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( 'Media: found: ' . $attrs[ 'src' ] ) ;

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) || ! empty( $attrs[ 'data-srcset' ] ) ) {
				LiteSpeed_Cache_Log::debug2( 'Media bypassed' ) ;
				continue ;
			}

			/**
			 * Exclude from lazyload by setting
			 * @since  1.5
			 */
			if ( $excludes && LiteSpeed_Cache_Utility::str_hit_array( $attrs[ 'src' ], $excludes ) ) {
				LiteSpeed_Cache_Log::debug2( 'Media: lazyload image exclude ' . $attrs[ 'src' ] ) ;
				continue ;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue ;
			}

			$src_list[] = $attrs[ 'src' ] ;
			$html_list[] = $match[ 0 ] ;
		}

		return array( $src_list, $html_list ) ;
	}

	/**
	 * Parse iframe src
	 *
	 * @since  1.4
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_iframe()
	{
		$html_list = array() ;

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<iframe \s*([^>]+)></iframe>#isU', $content, $matches, PREG_SET_ORDER ) ;
		foreach ( $matches as $match ) {
			$attrs = LiteSpeed_Cache_Utility::parse_attr( $match[ 1 ] ) ;

			if ( empty( $attrs[ 'src' ] ) ) {
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( 'Media found iframe: ' . $attrs[ 'src' ] ) ;

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) ) {
				LiteSpeed_Cache_Log::debug2( 'Media bypassed' ) ;
				continue ;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue ;
			}

			$html_list[] = $match[ 0 ] ;
		}

		return $html_list ;
	}

	/**
	 * Replace image src to webp
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _replace_buffer_img_webp()
	{
		preg_match_all( '#<img([^>]+?)src=([\'"\\\]*)([^\'"\s\\\>]+)([\'"\\\]*)([^>]*)>#i', $this->content, $matches ) ;
		foreach ( $matches[ 3 ] as $k => $url ) {
			// Check if is a DATA-URI
			if ( strpos( $url, 'data:image' ) !== false ) {
				continue ;
			}

			if ( ! $url2 = $this->_replace_webp( $url ) ) {
				continue ;
			}

			$html_snippet = sprintf(
				'<img %1$s src=%2$s %3$s>',
				$matches[ 1 ][ $k ],
				$matches[ 2 ][ $k ] . $url2 . $matches[ 4 ][ $k ],
				$matches[ 5 ][ $k ]
			) ;
			$this->content = str_replace( $matches[ 0 ][ $k ], $html_snippet, $this->content ) ;
		}
	}

	/**
	 * Hook to wp_get_attachment_image_src
	 *
	 * @since  1.6.2
	 * @access public
	 * @param  array $img The URL of the attachment image src, the width, the height
	 * @return array
	 */
	public function webp_attach_img_src( $img )
	{
		LiteSpeed_Cache_Log::debug2( 'Media changing attach src: ' . $img[0] ) ;
		if ( $img && $url = $this->_replace_webp( $img[ 0 ] ) ) {
			$img[ 0 ] = $url ;
		}
		return $img ;
	}

	/**
	 * Try to replace img url
	 *
	 * @since  1.6.2
	 * @access public
	 * @param  string $url
	 * @return string
	 */
	public function webp_url( $url )
	{
		if ( $url && $url2 = $this->_replace_webp( $url ) ) {
			$url = $url2 ;
		}
		return $url ;
	}

	/**
	 * Hook to replace WP responsive images
	 *
	 * @since  1.6.2
	 * @access public
	 * @param  array $srcs
	 * @return array
	 */
	public function webp_srcset( $srcs )
	{
		if ( $srcs ) {
			foreach ( $srcs as $w => $data ) {
				if( ! $url = $this->_replace_webp( $data[ 'url' ] ) ) {
					continue ;
				}
				$srcs[ $w ][ 'url' ] = $url ;
			}
		}
		return $srcs ;
	}

	/**
	 * Replace internal image src to webp
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _replace_webp( $url )
	{
		LiteSpeed_Cache_Log::debug2( 'Media: webp replacing: ' . $url ) ;
		if ( LiteSpeed_Cache_Utility::is_internal_file( $url ) ) {
			// check if has webp file
			if ( LiteSpeed_Cache_Utility::is_internal_file( $url  . '.webp' ) ) {
				$url .= '.webp' ;
			}
			else {
				LiteSpeed_Cache_Log::debug2( 'Media: no webp file, bypassed' ) ;
				return false ;
			}
		}
		else {
			LiteSpeed_Cache_Log::debug2( 'Media: no file, bypassed' ) ;
			return false ;
		}

		return $url ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.4
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