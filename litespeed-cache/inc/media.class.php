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

	const TYPE_SYNC_DATA = 'sync_data' ;
	const TYPE_IMG_OPTIMIZE = 'img_optm' ;
	const TYPE_IMG_OPTIMIZE_RESCAN = 'img_optm_rescan' ;
	const TYPE_IMG_OPTIMIZE_DESTROY = 'img_optm_destroy' ;
	const TYPE_IMG_PULL = 'img_pull' ;
	const TYPE_IMG_BATCH_SWITCH_ORI = 'img_optm_batch_switch_ori' ;
	const TYPE_IMG_BATCH_SWITCH_OPTM = 'img_optm_batch_switch_optm' ;
	const OPT_CRON_RUN = 'litespeed-img_optm_cron_run' ; // last cron running time

	const DB_IMG_OPTIMIZE_DESTROY = 'litespeed-optimize-destroy' ;
	const DB_IMG_OPTIMIZE_DATA = 'litespeed-optimize-data' ;
	const DB_IMG_OPTIMIZE_STATUS = 'litespeed-optimize-status' ;
	const DB_IMG_OPTIMIZE_STATUS_REQUESTED = 'requested' ;
	const DB_IMG_OPTIMIZE_STATUS_NOTIFIED = 'notified' ;
	const DB_IMG_OPTIMIZE_STATUS_PULLED = 'pulled' ;
	const DB_IMG_OPTIMIZE_STATUS_FAILED = 'failed' ;
	const DB_IMG_OPTIMIZE_STATUS_ERR = 'err' ;
	const DB_IMG_OPTIMIZE_SIZE = 'litespeed-optimize-size' ;

	const DB_IMG_OPTM_SUMMARY = 'litespeed_img_optm_summary' ;

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

		$this->wp_upload_dir = wp_upload_dir() ;

		if ( $this->can_media() ) {
			$this->_static_request_check() ;

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
		}

		add_action( 'litspeed_after_admin_init', array( $this, 'after_admin_init' ) ) ;
	}

	/**
	 * Check if it can use Media frontend
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function can_media()
	{
		if ( is_admin() ) {
			return false ;
		}

		return true ;
	}

	/**
	 * Register admin menu
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public function after_admin_init()
	{
		if ( get_option( LiteSpeed_Cache_Config::ITEM_MEDIA_NEED_PULL ) ) {
			add_filter( 'manage_media_columns', array( $this, 'media_row_title' ) ) ;
			add_filter( 'manage_media_custom_column', array( $this, 'media_row_actions' ), 10, 2 ) ;
		}
	}

	/**
	 * Media Admin Menu -> Image Optimization Column Title
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public function media_row_title( $posts_columns )
	{
		$posts_columns[ 'imgoptm' ] = __( 'LiteSpeed Optimization', 'litespeed-cache' ) ;

		return $posts_columns ;
	}

	/**
	 * Media Admin Menu -> Image Optimization Column
	 *
	 * @since 1.6.2
	 * @access public
	 */
	public function media_row_actions( $column_name, $post_id )
	{
		if ( $column_name !== 'imgoptm' ) {
			return ;
		}

		$local_file = get_attached_file( $post_id ) ;

		$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, 'webp' . $post_id ) ;
		$desc = false ;
		$cls = 'litespeed-icon-media-webp' ;
		$cls_webp = '' ;
		if ( file_exists( $local_file . '.webp' ) ) {
			$desc = __( 'Disable WebP', 'litespeed-cache' ) ;
			$cls_webp = 'litespeed-txt-webp' ;
		}
		elseif ( file_exists( $local_file . '.optm.webp' ) ) {
			$cls .= '-disabled' ;
			$desc = __( 'Enable WebP', 'litespeed-cache' ) ;
			$cls_webp = 'litespeed-txt-disabled' ;
		}

		$link_webp = '' ;
		if ( $desc ) {
			$link_webp = sprintf( '<a href="%1$s" class="litespeed-media-href" title="%3$s"><span class="%2$s"></span></a>', $link, $cls, $desc ) ;
		}

		$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
		$bk_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension ;
		$bk_optm_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.optm.' . $extension ;

		$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, 'orig' . $post_id ) ;
		$desc = false ;
		$cls = 'litespeed-icon-media-optm' ;
		$cls_ori = '' ;
		if ( file_exists( $bk_file ) ) {
			$desc = __( 'Restore Original File', 'litespeed-cache' ) ;
			$cls_ori = 'litespeed-txt-ori' ;
		}
		elseif ( file_exists( $bk_optm_file ) ) {
			$cls .= '-disabled' ;
			$desc = __( 'Switch To Optimized File', 'litespeed-cache' ) ;
			$cls_ori = 'litespeed-txt-disabled' ;
		}

		$link_ori = '' ;
		if ( $desc ) {
			$link_ori = sprintf( '<a href="%1$s" class="litespeed-media-href" title="%3$s"><span class="%2$s"></span></a>', $link, $cls, $desc ) ;
		}

		$info_webp = '' ;
		$size_meta = get_post_meta( $post_id, self::DB_IMG_OPTIMIZE_SIZE, true ) ;
		if ( $size_meta && ! empty ( $size_meta[ 'webp_saved' ] ) ) {
			$percent = ceil( $size_meta[ 'webp_saved' ] * 100 / $size_meta[ 'webp_total' ] ) ;
			$pie_webp = LiteSpeed_Cache_GUI::pie( $percent, 30 ) ;
			$txt_webp = sprintf( __( 'WebP saved %s', 'litespeed-cache' ), LiteSpeed_Cache_Utility::real_size( $size_meta[ 'webp_saved' ] ) ) ;

			$info_webp = sprintf( '%s %s', $pie_webp, $txt_webp ) ;
		}

		$info_ori = '' ;
		if ( $size_meta && ! empty ( $size_meta[ 'ori_saved' ] ) ) {
			$percent = ceil( $size_meta[ 'ori_saved' ] * 100 / $size_meta[ 'ori_total' ] ) ;
			$pie_ori = LiteSpeed_Cache_GUI::pie( $percent, 30 ) ;
			$txt_ori = sprintf( __( 'Original saved %s', 'litespeed-cache' ), LiteSpeed_Cache_Utility::real_size( $size_meta[ 'ori_saved' ] ) ) ;

			$info_ori = sprintf( '%s %s', $pie_ori, $txt_ori ) ;
		}

		echo "<p class='litespeed-media-p $cls_webp'>$info_webp $link_webp</p><p class='litespeed-media-p $cls_ori'>$info_ori $link_ori</p>" ;
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
		$q = "SELECT meta_value
			FROM $wpdb->postmeta
			WHERE meta_key = %s
		" ;
		$cond = array( self::DB_IMG_OPTIMIZE_DATA ) ;
		$meta_value_lists = $wpdb->get_results( $wpdb->prepare( $q, $cond ) ) ;

		$i = 0 ;
		foreach ( $meta_value_lists as $v ) {
			$meta_value_list = unserialize( $v->meta_value ) ;

			foreach ( $meta_value_list as $v2 ) {
				if ( $v2[ 1 ] !== 'pulled' ) {
					continue ;
				}

				$src = $v2[ 0 ] ;
				$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $src ;

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
		}

		LiteSpeed_Cache_Log::debug( 'Media: batch switched images total: ' . $i ) ;

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
		$q = "SELECT meta_value
			FROM $wpdb->postmeta
			WHERE post_id = %d AND meta_key = %s
		" ;
		$cond = array( $pid, self::DB_IMG_OPTIMIZE_DATA ) ;
		$meta_value_list = $wpdb->get_var( $wpdb->prepare( $q, $cond ) ) ;
		$meta_value_list = unserialize( $meta_value_list ) ;

		$msg = 'Unknown Msg' ;

		foreach ( $meta_value_list as $v ) {
			if ( $v[ 1 ] !== 'pulled' ) {
				continue ;
			}

			$src = $v[ 0 ] ;
			$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $src ;

			// to switch webp file
			if ( $switch_type === 'webp' ) {
				if ( file_exists( $local_file . '.webp' ) ) {
					rename( $local_file . '.webp', $local_file . '.optm.webp' ) ;
					LiteSpeed_Cache_Log::debug( 'Media: Disabled WebP: ' . $local_file ) ;

					$msg = __( 'Disabled WebP file successfully.', 'litespeed-cache' ) ;
				}
				elseif ( file_exists( $local_file . '.optm.webp' ) ) {
					rename( $local_file . '.optm.webp', $local_file . '.webp' ) ;
					LiteSpeed_Cache_Log::debug( 'Media: Enable WebP: ' . $local_file ) ;

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
					LiteSpeed_Cache_Log::debug( 'Media: Restore original img: ' . $bk_file ) ;

					$msg = __( 'Restored original file successfully.', 'litespeed-cache' ) ;
				}
				elseif ( file_exists( $bk_optm_file ) ) {
					rename( $local_file, $bk_file ) ;
					rename( $bk_optm_file, $local_file ) ;
					LiteSpeed_Cache_Log::debug( 'Media: Switch to optm img: ' . $local_file ) ;

					$msg = __( 'Switched to optimized file successfully.', 'litespeed-cache' ) ;
				}

			}
		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg ) ;
	}

	/**
	 * Get wp size info
	 *
	 * NOTE: this is not used because it has to be after admin_init
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
			LiteSpeed_Cache_Control::set_public_forced( 'OPTM: lazyload js' ) ;
			LiteSpeed_Cache_Control::set_no_vary() ;
			LiteSpeed_Cache_Control::set_custom_ttl( 8640000 ) ;
			LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_MIN . '_LAZY' ) ;

			$file = LSCWP_DIR . 'js/lazyload.min.js' ;

			header( 'Content-Length: ' . filesize( $file ) ) ;
			header( 'Content-Type: application/x-javascript; charset=utf-8' ) ;

			echo file_get_contents( $file ) ;
			exit ;
		}
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
			LiteSpeed_Cache_Log::debug( 'Media: Failed to post to LiteSpeed IAPI server ', $json ) ;
			$msg = __( 'Failed to communicate with LiteSpeed IAPI server', 'litespeed-cache' ) . ': ' . $json ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return ;
		}

		if ( ! empty( $json ) ) {
			update_option( self::DB_IMG_OPTM_SUMMARY, $json ) ;
		}

		$msg = __( 'Communicated with LiteSpeed Image Optimization Server successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		LiteSpeed_Cache_Admin::redirect() ;

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
			/**
			 * Batch switch
			 * @since 1.6.3
			 */
			case self::TYPE_IMG_BATCH_SWITCH_ORI :
			case self::TYPE_IMG_BATCH_SWITCH_OPTM :
				$instance->_batch_switch( $type ) ;
				break ;

			case self::TYPE_SYNC_DATA :
				$instance->_sync_data() ;
				break ;

			case self::TYPE_IMG_OPTIMIZE :
				$instance->_img_optimize() ;
				break ;

			case self::TYPE_IMG_OPTIMIZE_RESCAN :
				$instance->_img_optimize_rescan() ;
				break ;

			case self::TYPE_IMG_OPTIMIZE_DESTROY :
				$instance->_img_optimize_destroy() ;
				break ;

			case self::TYPE_IMG_PULL :
				LiteSpeed_Cache_Log::debug( 'Media: Manually running Cron pull_optimized_img' ) ;
				$instance->_pull_optimized_img() ;
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
	 * @access public
	 */
	public function cron_running( $bool_res = true )
	{
		$last_run = get_option( self::OPT_CRON_RUN ) ;

		$is_running = $last_run && time() - $last_run <= 120 ;

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
		if ( $this->cron_running() ) {
			LiteSpeed_Cache_Log::debug( 'Media: fetch cron is running' ) ;
			return ;
		}

		global $wpdb ;

		$q = "SELECT a.meta_id, a.post_id, b.meta_id as bmeta_id, b.meta_value, c.meta_id as cmeta_id, c.meta_value as cmeta_value
			FROM $wpdb->postmeta a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.post_id
			LEFT JOIN $wpdb->postmeta c ON c.post_id = a.post_id AND c.meta_key = %s
			WHERE a.meta_key = %s AND a.meta_value = %s AND b.meta_key = %s
			ORDER BY a.post_id DESC
			LIMIT 1
		" ;
		$cond = array( self::DB_IMG_OPTIMIZE_SIZE, self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED, self::DB_IMG_OPTIMIZE_DATA ) ;
		$query = $wpdb->prepare( $q, $cond ) ;

		$webp_only = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_ONLY ) ;

		for ( $i=0 ; $i < 10 ; $i++ ) {
			$meta_value_row = $wpdb->get_row( $query ) ;

			if ( ! $meta_value_row ) {
				break ;
			}

			$meta_value = unserialize( $meta_value_row->meta_value ) ;

			if ( $meta_value_row->cmeta_value ) {
				$cmeta_value = unserialize( $meta_value_row->cmeta_value ) ;
			}
			else {
				$cmeta_value = array(
					'ori_total' => 0,
					'ori_saved' => 0,
					'webp_total' => 0,
					'webp_saved' => 0,
				) ;
			}

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
						'pid' => $meta_value_row->post_id,
						'src_md5' => $md5,
						'meta'	=> $meta_value_row->meta_value,
					) ;
					$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PULL_IMG, $data, $server ) ;
					if ( empty( $json[ 'webp' ] ) ) {
						LiteSpeed_Cache_Log::debug( 'Media: Failed to pull optimized img: ', $json ) ;
						return ;
					}

					$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $v2[ 0 ] ;
					$ori_size = filesize( $local_file ) ;

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
						LiteSpeed_Cache_Log::debug( 'Media: Failed to pull optimized img WebP: file md5 dismatch, server md5: ' . $json[ 'webp_md5' ] ) ;

						// update status to failed
						$meta_value[ $md5 ][ 1 ] = self::DB_IMG_OPTIMIZE_STATUS_FAILED ;
						$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d ";
						$wpdb->query( $wpdb->prepare( $q, array( serialize( $meta_value ), $meta_value_row->bmeta_id ) ) ) ;

						// Notify server to update status
						LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PULL_IMG_FAILED, $data, $server ) ;

						return ;// exit from running pull process
					}

					// log webp file saved size summary
					$saved = $ori_size - filesize( $local_file . '.webp' ) ;
					if ( $saved > 0 ) {
						$cmeta_value[ 'webp_total' ] += $ori_size ;
						$cmeta_value[ 'webp_saved' ] += $saved ;
					}

					LiteSpeed_Cache_Log::debug( 'Media: Pulled optimized img WebP: ' . $local_file . '.webp' ) ;

					// Fetch optimized image itself
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
							LiteSpeed_Cache_Log::debug( 'Media: Failed to pull optimized img iteself: file md5 dismatch, server md5: ' . $json[ 'target_md5' ] ) ;

							// update status to failed
							$meta_value[ $md5 ][ 1 ] = self::DB_IMG_OPTIMIZE_STATUS_FAILED ;
							$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d ";
							$wpdb->query( $wpdb->prepare( $q, array( serialize( $meta_value ), $meta_value_row->bmeta_id ) ) ) ;

							// Notify server to update status
							LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PULL_IMG_FAILED, $data, $server ) ;

							return ; // exit from running pull process
						}

						// log webp file saved size summary
						$saved = $ori_size - filesize( $local_file . '.tmp' ) ;
						if ( $saved > 0 ) {
							$cmeta_value[ 'ori_total' ] += $ori_size ;
							$cmeta_value[ 'ori_saved' ] += $saved ;
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

			LiteSpeed_Cache_Log::debug( 'Media: Pulled optimized img done, updating record pid: ' . $meta_value_row->post_id ) ;

			// Update data tag
			$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d ";
			$wpdb->query( $wpdb->prepare( $q, array( serialize( $meta_value ), $meta_value_row->bmeta_id ) ) ) ;

			/**
			 * Update size saved info
			 * @since  1.6.5
			 */
			if ( $meta_value_row->cmeta_id ) {
				$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d ";
				$wpdb->query( $wpdb->prepare( $q, array( serialize( $cmeta_value ), $meta_value_row->cmeta_id ) ) ) ;
			}
			else {
				$q = "INSERT INTO $wpdb->postmeta SET meta_value = %s, meta_id = %d, meta_key = %s, post_id = %d ";
				$wpdb->query( $wpdb->prepare( $q, array( serialize( $cmeta_value ), $meta_value_row->cmeta_id, self::DB_IMG_OPTIMIZE_SIZE, $meta_value_row->post_id ) ) ) ;
			}

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
				LiteSpeed_Cache_Log::debug( 'Media: Updated pid status: ' . $new_status ) ;

				$wpdb->query( $wpdb->prepare( $q, array( $new_status, $meta_value_row->meta_id ) ) ) ;
			}
		}

		// If all pulled, update tag to done
		$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1" ;
		$meta_value_list = $wpdb->get_row( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ) ) ;
		if ( ! $meta_value_list ) {
			LiteSpeed_Cache_Log::debug( 'Media: Marked poll status to all pulled ' ) ;
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
	 * Show an image's optm status
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public function check_img()
	{
		$pid = $_POST[ 'data' ] ;

		LiteSpeed_Cache_Log::debug( 'Media: Check image [ID] ' . $pid ) ;

		$data = array() ;

		$data[ 'img_count' ] = $this->img_count() ;

		$info = get_post_meta( $pid, self::DB_IMG_OPTIMIZE_STATUS, true ) ;
		$data[ self::DB_IMG_OPTIMIZE_STATUS ] = $info ;

		$info = get_post_meta( $pid, self::DB_IMG_OPTIMIZE_DATA, true ) ;
		$data[ self::DB_IMG_OPTIMIZE_DATA ] = $info ;

		echo json_encode( $data ) ;
		exit;
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
			LiteSpeed_Cache_Log::debug( 'Media: notify exit: no notified data' ) ;
			exit( json_encode( 'no notified data' ) ) ;
		}

		if ( empty( $_POST[ 'server' ] ) || substr( $_POST[ 'server' ], -21 ) !== 'api.litespeedtech.com' ) {
			LiteSpeed_Cache_Log::debug( 'Media: notify exit: no/wrong server' ) ;
			exit( json_encode( 'no/wrong server' ) ) ;
		}

		$_allowed_status = array( self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED, self::DB_IMG_OPTIMIZE_STATUS_ERR, self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) ;

		if ( empty( $_POST[ 'status' ] ) || ! in_array( $_POST[ 'status' ], $_allowed_status ) ) {
			LiteSpeed_Cache_Log::debug( 'Media: notify exit: no/wrong status' ) ;
			exit( json_encode( 'no/wrong status' ) ) ;
		}

		return array( $notified_data, $_POST[ 'server' ], $_POST[ 'status' ] ) ;
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
		list( $notified_data, $server, $status ) = $this->_parse_notify_data() ;

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
				if ( in_array( $md5, $notified_data[ $v->post_id ] ) && $v2[ 1 ] !== self::DB_IMG_OPTIMIZE_STATUS_PULLED ) {
					$md52src_list[ $md5 ][ 1 ] = $status ;
					$md52src_list[ $md5 ][ 2 ] = $server ;
					$changed = true ;
				}
			}

			if ( ! $changed ) {
				LiteSpeed_Cache_Log::debug( 'Media: notify_img [status] ' . $status . ' continue: no changed meta [pid] ' . $v->post_id ) ;
				continue ;
			}

			$new_status = $this->_get_status_by_meta_data( $md52src_list, $status ) ;

			// Save meta data
			$md52src_list = serialize( $md52src_list ) ;
			$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d" ;
			$wpdb->query( $wpdb->prepare( $q, array( $md52src_list, $v->meta_id ) ) ) ;

			// Overwrite post meta status to the latest one
			$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE post_id = %d AND meta_key = %s" ;
			// If partly needs notified to pull, notified should overwrite this post's status always
			$wpdb->query( $wpdb->prepare( $q, array( $new_status, $v->post_id, self::DB_IMG_OPTIMIZE_STATUS ) ) ) ;

			LiteSpeed_Cache_Log::debug( 'Media: notify_img [status] ' . $status . ' updated post_meta [pid] ' . $v->post_id ) ;

			if ( $status == self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) {
				$need_pull = true ;
			}
		}

		if ( $need_pull ) {
			update_option( LiteSpeed_Cache_Config::ITEM_MEDIA_NEED_PULL, self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) ;
		}

		// redo count err

		echo json_encode( array( 'count' => count( $notified_data ) ) ) ;
		exit() ;
	}

	/**
	 * Generate post's img optm status from child images meta value
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _get_status_by_meta_data( $md52src_list, $default_status )
	{
		$has_notify = false ;
		$has_request = false ;
		$has_pull = false ;

		foreach ( $md52src_list as $v ) {
			if ( $v[ 1 ] == self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ) {
				$has_notify = true ;
			}
			if ( $v[ 1 ] == self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) {
				$has_request = true ;
			}
			if ( $v[ 1 ] == self::DB_IMG_OPTIMIZE_STATUS_PULLED ) {
				$has_pull = true ;
			}
		}

		if ( $has_notify ) {
			$new_status = self::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ;
		}
		elseif ( $has_request ) {
			$new_status = self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ;
		}
		elseif ( $has_pull ) {
			$new_status = self::DB_IMG_OPTIMIZE_STATUS_PULLED ;
		}
		else {
			$new_status = $default_status ;
		}

		return $new_status ;

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
				LiteSpeed_Cache_Log::debug( 'Media: bypassed parsing meta due to no meta_value: pid ' . $v->post_id ) ;
				return false ;
			}

			try {
				$meta_value = unserialize( $v->meta_value ) ;
			}
			catch ( \Exception $e ) {
				LiteSpeed_Cache_Log::debug( 'Media: bypassed parsing meta due to meta_value not json: pid ' . $v->post_id ) ;
				return false ;
			}

			if ( empty( $meta_value[ 'file' ] ) ) {
				LiteSpeed_Cache_Log::debug( 'Media: bypassed parsing meta due to no ori file: pid ' . $v->post_id ) ;
				return false ;
			}

			return $meta_value ;

	}

	/**
	 * Push img to LiteSpeed IAPI server
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _push_img_in_queue_to_ls()
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
			LiteSpeed_Cache_Log::debug( 'Media: Failed to post to LiteSpeed IAPI server ', $json ) ;
			$msg = sprintf( __( 'Failed to push to LiteSpeed IAPI server: %s', 'litespeed-cache' ), $json ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return null ;
		}

		// Check data format
		if ( empty( $json[ 'pids' ] ) || ! is_array( $json[ 'pids' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'Media: Failed to parse data from LiteSpeed IAPI server ', $json[ 'pids' ] ) ;
			$msg = sprintf( __( 'Failed to parse data from LiteSpeed IAPI server: %s', 'litespeed-cache' ), $json[ 'pids' ] ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return null ;
		}

		LiteSpeed_Cache_Log::debug( 'Media: posts data from LiteSpeed IAPI server count: ' . count( $json[ 'pids' ] ) ) ;

		return $json ;

	}

	/**
	 * Send destroy all requests cmd to LiteSpeed IAPI server and get the link to finish it ( avoid click by mistake )
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _img_optimize_destroy()
	{
		LiteSpeed_Cache_Log::debug( 'Media: sending DESTROY cmd to LiteSpeed IAPI' ) ;

		// Mark request time to avoid duplicated request
		update_option( self::DB_IMG_OPTIMIZE_DESTROY, time() ) ;

		// Push to LiteSpeed IAPI server
		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_REQUEST_DESTROY ) ;

		// confirm link will be displayed by Admin_API automatically
		if ( is_array( $json ) && $json ) {
			LiteSpeed_Cache_Log::debug( 'Media: cmd result', $json ) ;
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
		LiteSpeed_Cache_Log::debug( 'Media: excuting DESTROY process' ) ;

		$request_time = get_option( self::DB_IMG_OPTIMIZE_DESTROY ) ;
		if ( time() - $request_time > 300 ) {
			LiteSpeed_Cache_Log::debug( 'Media: terminate DESTROY process due to timeout' ) ;
			exit( 'Destroy callback timeout ( 300 seconds )' ) ;
		}

		// Start deleting files
		$q = "SELECT * from $wpdb->postmeta WHERE meta_key = %s" ;
		$list = $wpdb->get_results( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_DATA ) ) ) ;
		if ( $list ) {
			foreach ( $list as $v ) {
				$meta_value_list = unserialize( $v->meta_value ) ;
				foreach ( $meta_value_list as $v2 ) {

					$src = $v2[ 0 ] ;
					$local_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $src ;

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
			}
		}

		// Delete optm info
		$q = "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'litespeed-optimize%'" ;
		$wpdb->query( $q ) ;

		// Clear credit info
		delete_option( self::DB_IMG_OPTM_SUMMARY ) ;

		exit( 'ok' ) ;
	}

	/**
	 * Resend requested img to LiteSpeed IAPI server
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _img_optimize_rescan()
	{
		LiteSpeed_Cache_Log::debug( 'Media: resend requested images' ) ;

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
			LiteSpeed_Cache_Log::debug( 'Media: resend request bypassed: no image found' ) ;
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
				LiteSpeed_Cache_Log::debug( 'Media: resend img request hit limit: [total] ' . $this->_img_total . " \t[add] $num_will_incease \t[credit] $_credit" ) ;
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
		LiteSpeed_Cache_Log::debug( 'Media: prepared images to push: groups ' . $total_groups . ' images ' . $this->_img_total ) ;

		// Push to LiteSpeed IAPI server
		$json = $this->_push_img_in_queue_to_ls() ;
		if ( $json === null ) {
			return ;
		}
		// Returned data is the requested and notifed images
		$pids = $json[ 'pids' ] ;

		LiteSpeed_Cache_Log::debug( 'Media: returned data from LiteSpeed IAPI server count: ' . count( $pids ) ) ;

		$q = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d" ;

		// Update data
		foreach ( $pids as $pid ) {
			$md52src_list = $optm_data_list[ $pid ] ;

			foreach ( $this->_img_in_queue[ $pid ] as $md5 => $src_data ) {
				$md52src_list[ $md5 ] = array( $src_data[ 'file' ], self::DB_IMG_OPTIMIZE_STATUS_REQUESTED ) ;
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
	 * Push raw img to LiteSpeed IAPI server
	 *
	 * @since 1.6
	 * @access private
	 */
	private function _img_optimize()
	{
		$_credit = (int) $this->summary_info( 'credit' ) ;
		$credit_recovered = (int) $this->summary_info( 'credit_recovered' ) ;

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

			$meta_value = $this->_parse_wp_meta_value( $v ) ;
			if ( ! $meta_value ) {
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
				LiteSpeed_Cache_Log::debug( 'Media img request hit limit: [total] ' . $this->_img_total . " \t[add] $num_will_incease \t[credit] $_credit" ) ;
				break ;
			}
			/**
			 * Check if need to test run ( new user only allow 1 group at first time)
			 * @since 1.6.6.1
			 */
			if ( $this->_img_total && ! $credit_recovered ) {
				LiteSpeed_Cache_Log::debug( 'Media: test run only allow 1 group ' ) ;
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

		// push to LiteSpeed IAPI server
		if ( empty( $this->_img_in_queue ) ) {
			$msg = __( 'No image found.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
			return ;
		}

		$total_groups = count( $this->_img_in_queue ) ;
		LiteSpeed_Cache_Log::debug( 'Media prepared images to push: groups ' . $total_groups . ' images ' . $this->_img_total ) ;

		// Push to LiteSpeed IAPI server
		$json = $this->_push_img_in_queue_to_ls() ;
		if ( $json === null ) {
			return ;
		}
		$pids = $json[ 'pids' ] ;

		// Exclude those who have meta already
		$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s and post_id in ( " . implode( ',', array_fill( 0, count( $pids ), '%s' ) ) . " )" ;
		$tmp = $wpdb->get_results( $wpdb->prepare( $q, array_merge( array( self::DB_IMG_OPTIMIZE_STATUS ), $pids ) ) ) ;
		$exists_pids = array() ;
		foreach ( $tmp as $v ) {
			$exists_pids[] = $v->post_id ;
		}
		if ( $exists_pids ) {
			LiteSpeed_Cache_Log::debug( 'Media: existing posts data from LiteSpeed IAPI server count: ' . count( $exists_pids ) ) ;
		}
		$pids = array_diff( $pids, $exists_pids ) ;

		if ( ! $pids ) {
			LiteSpeed_Cache_Log::debug( 'Media: Failed to store data from LiteSpeed IAPI server with empty pids' ) ;
			LiteSpeed_Cache_Admin_Display::error( __( 'Post data is empty.', 'litespeed-cache' ) ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'Media: diff posts data from LiteSpeed IAPI server count: ' . count( $pids ) ) ;

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

		// Update credit info
		if ( isset( $json[ 'credit' ] ) ) {
			$this->_update_credit( $json[ 'credit' ] ) ;
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
		$total_err = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS, self::DB_IMG_OPTIMIZE_STATUS_ERR ) ) ) ;

		$q = "SELECT count(*)
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			LEFT JOIN $wpdb->postmeta c ON c.post_id = a.ID AND c.meta_key = %s
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
				AND c.post_id IS NULL
			" ;
		$total_not_requested = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_IMG_OPTIMIZE_STATUS ) ) ) ;

		return array(
			'total_img'	=> $total_img,
			'total_not_requested'	=> $total_not_requested,
			'total_requested'	=> $total_requested,
			'total_err'	=> $total_err,
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

		LiteSpeed_Cache_Log::debug( 'Media finalize' ) ;

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
		if ( $this->cfg_img_webp && $this->webp_support() ) {
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
				LiteSpeed_Cache_Log::debug2( 'Media: no WebP file, bypassed' ) ;
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