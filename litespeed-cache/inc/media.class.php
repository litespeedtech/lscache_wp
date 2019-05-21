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

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Media
{
	private static $_instance ;

	const LIB_FILE_IMG_LAZYLOAD = 'js/lazyload.min.js' ;

	const TYPE_GENERATE_PLACEHOLDER = 'generate_placeholder' ;
	const DB_PLACEHOLDER_SUMMARY = 'litespeed-media-placeholder-summary' ;

	private $content ;
	private $wp_upload_dir ;

	private $_cfg_img_webp ;
	private $_cfg_placeholder_resp ;
	private $_cfg_placeholder_resp_color ;
	private $_cfg_placeholder_resp_async ;
	private $_placeholder_resp_dict = array() ;
	private $_ph_queue = array() ;

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
			$this->_cfg_img_webp = self::webp_enabled() ;

			// Due to ajax call doesn't send correct accept header, have to limit webp to HTML only
			if ( $this->_cfg_img_webp ) {
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

		$this->_cfg_placeholder_resp = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_PLACEHOLDER_RESP ) ;
		$this->_cfg_placeholder_resp_async = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_PLACEHOLDER_RESP_ASYNC ) ;
		$this->_cfg_placeholder_resp_color = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_PLACEHOLDER_RESP_COLOR ) ;
		// Encode the color
		if ( $this->_cfg_placeholder_resp_color ) {
			$this->_cfg_placeholder_resp_color = base64_encode( $this->_cfg_placeholder_resp_color ) ;
		}

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
	 * Check if enabled webp or not
	 *
	 * @since  2.4
	 * @access public
	 */
	public static function webp_enabled()
	{
		return LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_WEBP_REPLACE ) ;
	}

	/**
	 * Register admin menu
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public function after_admin_init()
	{
		if ( get_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL ) ) {
			add_filter( 'manage_media_columns', array( $this, 'media_row_title' ) ) ;
			add_filter( 'manage_media_custom_column', array( $this, 'media_row_actions' ), 10, 2 ) ;

			// Hook to attachment delete action
			add_action( 'delete_attachment', array( $this, 'delete_attachment' ) ) ;
		}
	}

	/**
	 * Media delete action hook
	 *
	 * @since 2.4.3
	 * @access public
	 */
	public function delete_attachment( $post_id )
	{
		LiteSpeed_Cache_Log::debug( '[Media] delete_attachment [pid] ' . $post_id ) ;
		LiteSpeed_Cache_Img_Optm::get_instance()->reset_row( $post_id ) ;
	}

	/**
	 * Return media file info if exists
	 *
	 * This is for remote attachment plugins
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function info( $short_file_path, $post_id )
	{
		$real_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $short_file_path ;

		if ( file_exists( $real_file ) ) {
			return array(
				'url'	=> $this->wp_upload_dir[ 'baseurl' ] . '/' . $short_file_path,
				'md5'	=> md5_file( $real_file ),
				'size'	=> filesize( $real_file ),
			) ;
		}

		/**
		 * WP Stateless compatibility #143 https://github.com/litespeedtech/lscache_wp/issues/143
		 * @since 2.9.8
		 * @return array( 'url', 'md5', 'size' )
		 */
		$info = apply_filters( 'litespeed_media_info', array(), $short_file_path, $post_id ) ;
		if ( ! empty( $info[ 'url' ] ) && ! empty( $info[ 'md5' ] ) && ! empty( $info[ 'size' ] ) ) {
			return $info ;
		}

		return false ;
	}

	/**
	 * Delete media file
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function del( $short_file_path, $post_id )
	{
		$real_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $short_file_path ;

		if ( file_exists( $real_file ) ) {
			unlink( $real_file ) ;
			LiteSpeed_Cache_Log::debug( '[Img_Optm] deleted ' . $real_file ) ;
		}

		do_action( 'litespeed_media_del', $short_file_path, $post_id ) ;
	}

	/**
	 * Rename media file
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function rename( $short_file_path, $short_file_path_new, $post_id )
	{
		$real_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $short_file_path ;
		$real_file_new = $this->wp_upload_dir[ 'basedir' ] . '/' . $short_file_path_new ;

		if ( file_exists( $real_file ) ) {
			rename( $real_file, $real_file_new ) ;
			LiteSpeed_Cache_Log::debug( '[Img_Optm] renamed ' . $real_file . ' to ' . $real_file_new ) ;
		}

		do_action( 'litespeed_media_rename', $short_file_path, $short_file_path_new, $post_id ) ;
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
		$local_file = substr( $local_file, strlen( $this->wp_upload_dir[ 'basedir' ] ) ) ;

		$size_meta = get_post_meta( $post_id, LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_SIZE, true ) ;

		// WebP info
		$info_webp = '' ;
		if ( $size_meta && ! empty ( $size_meta[ 'webp_saved' ] ) ) {
			$percent = ceil( $size_meta[ 'webp_saved' ] * 100 / $size_meta[ 'webp_total' ] ) ;
			$pie_webp = LiteSpeed_Cache_GUI::pie( $percent, 30 ) ;
			$txt_webp = sprintf( __( 'WebP saved %s', 'litespeed-cache' ), LiteSpeed_Cache_Utility::real_size( $size_meta[ 'webp_saved' ] ) ) ;

			$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, 'webp' . $post_id ) ;
			$desc = false ;
			$cls = 'litespeed-icon-media-webp' ;
			$cls_webp = '' ;
			if ( $this->info( $local_file . '.webp', $post_id ) ) {
				$desc = __( 'Click to Disable WebP', 'litespeed-cache' ) ;
				$cls_webp = 'litespeed-txt-webp' ;
			}
			elseif ( $this->info( $local_file . '.optm.webp', $post_id ) ) {
				$cls .= '-disabled' ;
				$desc = __( 'Click to Enable WebP', 'litespeed-cache' ) ;
				$cls_webp = 'litespeed-txt-disabled' ;
			}

			$info_webp = "<div class='litespeed-media-p $cls_webp litespeed-right20'><div class='litespeed-text-dimgray litespeed-text-center'>WebP</div>" ;

			if ( $desc ) {
				$info_webp .= sprintf( '<div><a href="%1$s" class="litespeed-media-href" title="%2$s' . "\n\n" . '%3$s">%4$s</a></div>', $link, $txt_webp, $desc, $pie_webp ) ;
			}
			else {
				$info_webp .= sprintf( '<div title="%1$s">%2$s</div>', $txt_webp, $pie_webp ) ;
			}

			$info_webp .= '</div>' ;
		}

		// Original image info
		$info_ori = '' ;
		if ( $size_meta && ! empty ( $size_meta[ 'ori_saved' ] ) ) {
			$percent = ceil( $size_meta[ 'ori_saved' ] * 100 / $size_meta[ 'ori_total' ] ) ;
			$pie_ori = LiteSpeed_Cache_GUI::pie( $percent, 30 ) ;
			$txt_ori = sprintf( __( 'Original saved %s', 'litespeed-cache' ), LiteSpeed_Cache_Utility::real_size( $size_meta[ 'ori_saved' ] ) ) ;

			$extension = pathinfo( $local_file, PATHINFO_EXTENSION ) ;
			$bk_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension ;
			$bk_optm_file = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.optm.' . $extension ;

			$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, 'orig' . $post_id ) ;
			$desc = false ;
			$cls = 'litespeed-icon-media-optm' ;
			$cls_ori = '' ;
			if ( $this->info( $bk_file, $post_id ) ) {
				$desc = __( 'Click to Restore Original File', 'litespeed-cache' ) ;
				$cls_ori = 'litespeed-txt-ori' ;
			}
			elseif ( $this->info( $bk_optm_file, $post_id ) ) {
				$cls .= '-disabled' ;
				$desc = __( 'Click to Switch To Optimized File', 'litespeed-cache' ) ;
				$cls_ori = 'litespeed-txt-disabled' ;
			}

			$info_ori = "<div class='litespeed-media-p $cls_ori litespeed-right30'><div class='litespeed-text-dimgray litespeed-text-center'>Orig.</div>" ;

			if ( $desc ) {
				$info_ori .= sprintf( '<div><a href="%1$s" class="litespeed-media-href" title="%2$s' . "\n\n" . '%3$s">%4$s</a></div>', $link, $txt_ori, $desc, $pie_ori ) ;
			}
			else {
				$info_ori .= sprintf( '<div title="%1$s">%2$s</div>', $txt_ori, $pie_ori ) ;
			}

			$info_ori .= '</div>' ;
		}

		// Delete row btn
		$del_row = '' ;
		if ( $size_meta ) {
			$del_row = '<div><div class="litespeed-text-dimgray litespeed-text-center">' . __( 'Reset', 'litespeed-cache' ) . '</div>' ;
			$del_row .= sprintf( '<div class="litespeed-media-p"><a href="%1$s" class="">%2$s</a></div>',
				LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_RESET_ROW, false, null, array( 'id' => $post_id ) ),
				'<span class="dashicons dashicons-trash dashicons-large litespeed-warning litespeed-dashicons-large"></span>'
			) ;
			$del_row .= '</div>' ;
		}

		echo <<<eot
			<div class="litespeed-flex-container">
				$info_webp
				$info_ori
				$del_row
			</div>
eot;

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
		if ( ! empty( $_SERVER[ 'HTTP_ACCEPT' ] ) && strpos( $_SERVER[ 'HTTP_ACCEPT' ], 'image/webp' ) !== false ) {
			return true ;
		}

		if ( ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) && strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'Page Speed' ) !== false ) {
			return true ;
		}

		return false ;
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
			LiteSpeed_Cache_Log::debug2( '[Media] bypass: NO_LAZY const' ) ;
			return $content ;
		}

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			LiteSpeed_Cache_Log::debug2( '[Media] bypass: Not frontend HTML type' ) ;
			return $content ;
		}

		LiteSpeed_Cache_Log::debug( '[Media] finalize' ) ;

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
		/**
		 * Use webp for optimized images
		 * @since 1.6.2
		 */
		if ( $this->_cfg_img_webp && $this->webp_support() ) {
			$this->_replace_buffer_img_webp() ;
		}

		$cfg_img_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY ) ;
		$cfg_iframe_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IFRAME_LAZY ) ;

		if ( $cfg_img_lazy ) {
			list( $src_list, $html_list, $placeholder_list ) = $this->_parse_img() ;
			$html_list_ori = $html_list ;
		}

		// image lazy load
		if ( $cfg_img_lazy ) {

			$default_placeholder = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY_PLACEHOLDER ) ?: LITESPEED_PLACEHOLDER ;

			foreach ( $html_list as $k => $v ) {
				$size = $placeholder_list[ $k ] ;
				// Check if need to enable responsive placeholder or not
				$this_placeholder = $this->_placeholder( $size ) ?: $default_placeholder ;

				$additional_attr = '' ;
				if ( $this_placeholder != $default_placeholder ) {
					LiteSpeed_Cache_Log::debug2( '[Media] Use resp placeholder [size] ' . $size ) ;
					$additional_attr = ' data-placeholder-resp="' . $size . '"' ;
				}

				$snippet = '<noscript>' . $v . '</noscript>' ;
				$v = str_replace( array( ' src=', ' srcset=', ' sizes=' ), array( ' data-src=', ' data-srcset=', ' data-sizes=' ), $v ) ;
				$v = str_replace( '<img ', '<img data-lazyloaded="1"' . $additional_attr . ' src="' . $this_placeholder . '" ', $v ) ;
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
			if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZYJS_INLINE ) ) {
				$lazy_lib = '<script>' . Litespeed_File::read( LSCWP_DIR . self::LIB_FILE_IMG_LAZYLOAD ) . '</script>' ;
			} else {
				$lazy_lib_url = LSWCP_PLUGIN_URL . self::LIB_FILE_IMG_LAZYLOAD ;
				$lazy_lib = '<script src="' . $lazy_lib_url . '"></script>' ;
			}

			$this->content = str_replace( '</body>', $lazy_lib . '</body>', $this->content ) ;
		}
	}

	/**
	 * Generate responsive placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder( $size )
	{
		if ( ! $size ) {
			return false ;
		}

		if ( ! $this->_cfg_placeholder_resp ) {
			return false ;
		}

		// Check if its already in dict or not
		if ( ! empty( $this->_placeholder_resp_dict[ $size ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Media] Resp placeholder already in dict [size] ' . $size ) ;

			return $this->_placeholder_resp_dict[ $size ] ;
		}

		// Need to generate the responsive placeholder
		$placeholder_realpath = $this->_placeholder_realpath( $size ) ;
		if ( file_exists( $placeholder_realpath ) ) {
			LiteSpeed_Cache_Log::debug2( '[Media] Resp placeholder file exists [size] ' . $size ) ;
			$this->_placeholder_resp_dict[ $size ] = Litespeed_File::read( $placeholder_realpath ) ;

			return $this->_placeholder_resp_dict[ $size ] ;
		}

		// Add to cron queue

		// Prevent repeated requests
		if ( in_array( $size, $this->_ph_queue ) ) {
			LiteSpeed_Cache_Log::debug2( '[Media] Resp placeholder file bypass generating due to in queue [size] ' . $size ) ;
			return false ;
		}
		$this->_ph_queue[] = $size ;

		$req_summary = self::get_summary() ;

		// Send request to generate placeholder
		if ( ! $this->_cfg_placeholder_resp_async ) {
			// If requested recently, bypass
			if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
				LiteSpeed_Cache_Log::debug2( '[Media] Resp placeholder file bypass generating due to interval limit [size] ' . $size ) ;
				return false ;
			}
			// Generate immediately
			$this->_placeholder_resp_dict[ $size ] = $this->_generate_placeholder( $size ) ;

			return $this->_placeholder_resp_dict[ $size ] ;
		}

		// Store it to prepare for cron
		if ( empty( $req_summary[ 'queue' ] ) ) {
			$req_summary[ 'queue' ] = array() ;
		}
		if ( in_array( $size, $req_summary[ 'queue' ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Media] Resp placeholder already in queue [size] ' . $size ) ;

			return false ;
		}

		$req_summary[ 'queue' ][] = $size ;

		LiteSpeed_Cache_Log::debug( '[Media] Added placeholder queue [size] ' . $size ) ;

		$this->_save_summary( $req_summary ) ;
		return false ;

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
		 * @since  2.7.1 Changed to array
		 */
		$excludes = apply_filters( 'litespeed_cache_media_lazy_img_excludes', LiteSpeed_Cache_Config::get_instance()->get_item( LiteSpeed_Cache_Config::ITEM_MEDIA_LAZY_IMG_EXC ) ) ;

		$cls_excludes = apply_filters( 'litespeed_media_lazy_img_cls_excludes', LiteSpeed_Cache_Config::get_instance()->get_item( LiteSpeed_Cache_Config::ITEM_MEDIA_LAZY_IMG_CLS_EXC ) ) ;

		$src_list = array() ;
		$html_list = array() ;
		$placeholder_list = array() ;

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
				LiteSpeed_Cache_Log::debug2( '[Media] lazyload bypassed base64 img' ) ;
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( '[Media] lazyload found: ' . $attrs[ 'src' ] ) ;

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) || ! empty( $attrs[ 'data-srcset' ] ) ) {
				LiteSpeed_Cache_Log::debug2( '[Media] bypassed' ) ;
				continue ;
			}

			if ( ! empty( $attrs[ 'class' ] ) && $hit = LiteSpeed_Cache_Utility::str_hit_array( $attrs[ 'class' ], $cls_excludes ) ) {
				LiteSpeed_Cache_Log::debug2( '[Media] lazyload image cls excludes [hit] ' . $hit ) ;
				continue ;
			}

			/**
			 * Exclude from lazyload by setting
			 * @since  1.5
			 */
			if ( $excludes && LiteSpeed_Cache_Utility::str_hit_array( $attrs[ 'src' ], $excludes ) ) {
				LiteSpeed_Cache_Log::debug2( '[Media] lazyload image exclude ' . $attrs[ 'src' ] ) ;
				continue ;
			}

			/**
			 * Excldues invalid image src from buddypress avatar crop
			 * @see  https://wordpress.org/support/topic/lazy-load-breaking-buddypress-upload-avatar-feature/#post-11040512
			 * @since  2.9.1
			 */
			if ( strpos( $attrs[ 'src' ], '{' ) !== false ) {
				LiteSpeed_Cache_Log::debug2( '[Media] image src has {} ' . $attrs[ 'src' ] ) ;
				continue ;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue ;
			}

			$placeholder = false ;
			if ( ! empty( $attrs[ 'width' ] ) && ! empty( $attrs[ 'height' ] ) ) {
				$placeholder = $attrs[ 'width' ] . 'x' . $attrs[ 'height' ] ;
			}

			$src_list[] = $attrs[ 'src' ] ;
			$html_list[] = $match[ 0 ] ;
			$placeholder_list[] = $placeholder ;
		}

		return array( $src_list, $html_list, $placeholder_list ) ;
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

			LiteSpeed_Cache_Log::debug2( '[Media] found iframe: ' . $attrs[ 'src' ] ) ;

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) ) {
				LiteSpeed_Cache_Log::debug2( '[Media] bypassed' ) ;
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
		// preg_match_all( '#<img([^>]+?)src=([\'"\\\]*)([^\'"\s\\\>]+)([\'"\\\]*)([^>]*)>#i', $this->content, $matches ) ;
		/**
		 * Added custom element & attribute support
		 * @since 2.2.2
		 */
		$webp_ele_to_check = LiteSpeed_Cache_Config::get_instance()->get_item( LiteSpeed_Cache_Config::ITEM_MEDIA_WEBP_ATTRIBUTE ) ;

		foreach ( $webp_ele_to_check as $v ) {
			if ( ! $v || strpos( $v, '.' ) === false ) {
				LiteSpeed_Cache_Log::debug2( '[Media] buffer_webp no . attribute ' . $v ) ;
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( '[Media] buffer_webp attribute ' . $v ) ;

			$v = explode( '.', $v ) ;
			$attr = preg_quote( $v[ 1 ], '#' ) ;
			if ( $v[ 0 ] ) {
				$pattern = '#<' . preg_quote( $v[ 0 ], '#' ) . '([^>]+)' . $attr . '=([\'"])(.+)\g{2}#iU' ;
			}
			else {
				$pattern = '# ' . $attr . '=([\'"])(.+)\g{1}#iU' ;
			}

			preg_match_all( $pattern, $this->content, $matches ) ;

			foreach ( $matches[ $v[ 0 ] ? 3 : 2 ] as $k2 => $url ) {
				// Check if is a DATA-URI
				if ( strpos( $url, 'data:image' ) !== false ) {
					continue ;
				}

				if ( ! $url2 = $this->replace_webp( $url ) ) {
					continue ;
				}

				if ( $v[ 0 ] ) {
					$html_snippet = sprintf(
						'<' . $v[ 0 ] . '%1$s' . $v[ 1 ] . '=%2$s',
						$matches[ 1 ][ $k2 ],
						$matches[ 2 ][ $k2 ] . $url2 . $matches[ 2 ][ $k2 ]
					) ;
				}
				else {
					$html_snippet = sprintf(
						' ' . $v[ 1 ] . '=%1$s',
						$matches[ 1 ][ $k2 ] . $url2 . $matches[ 1 ][ $k2 ]
					) ;
				}

				$this->content = str_replace( $matches[ 0 ][ $k2 ], $html_snippet, $this->content ) ;

			}
		}

		// parse srcset
		// todo: should apply this to cdn too
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_WEBP_REPLACE_SRCSET ) ) {
			$this->content = LiteSpeed_Cache_Utility::srcset_replace( $this->content, array( $this, 'replace_webp' ) ) ;
		}

		// Replace background-image
		preg_match_all( '#background\-image:(\s*)url\((.*)\)#iU', $this->content, $matches ) ;
		foreach ( $matches[ 2 ] as $k => $url ) {
			// Check if is a DATA-URI
			if ( strpos( $url, 'data:image' ) !== false ) {
				continue ;
			}

			/**
			 * Support quotes in src `background-image: url('src')`
			 * @since 2.9.3
			 */
			$url = trim( $url, '\'"' ) ;

			if ( ! $url2 = $this->replace_webp( $url ) ) {
				continue ;
			}

			// $html_snippet = sprintf( 'background-image:%1$surl(%2$s)', $matches[ 1 ][ $k ], $url2 ) ;
			$html_snippet = str_replace( $url, $url2, $matches[ 0 ][ $k ] ) ;
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
		LiteSpeed_Cache_Log::debug2( '[Media] changing attach src: ' . $img[0] ) ;
		if ( $img && $url = $this->replace_webp( $img[ 0 ] ) ) {
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
		if ( $url && $url2 = $this->replace_webp( $url ) ) {
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
				if( ! $url = $this->replace_webp( $data[ 'url' ] ) ) {
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
	 * @access public
	 */
	public function replace_webp( $url )
	{
		LiteSpeed_Cache_Log::debug2( '[Media] webp replacing: ' . $url, 4 ) ;

		if ( substr( $url, -5 ) == '.webp' ) {
			LiteSpeed_Cache_Log::debug2( '[Media] already webp' ) ;
			return false ;
		}

		/**
		 * WebP API hook
		 * NOTE: As $url may contain query strings, WebP check will need to parse_url before appending .webp
		 * @since  2.9.5
		 * @see  #751737 - API docs for WEBP generation
		 */
		if ( apply_filters( 'litespeed_media_check_ori', LiteSpeed_Cache_Utility::is_internal_file( $url ), $url ) ) {
			// check if has webp file
			if ( apply_filters( 'litespeed_media_check_webp', LiteSpeed_Cache_Utility::is_internal_file( $url, 'webp' ), $url ) ) {
				$url .= '.webp' ;
			}
			else {
				LiteSpeed_Cache_Log::debug2( '[Media] -no WebP file, bypassed' ) ;
				return false ;
			}
		}
		else {
			LiteSpeed_Cache_Log::debug2( '[Media] -no file, bypassed' ) ;
			return false ;
		}

		LiteSpeed_Cache_Log::debug2( '[Media] - replaced to: ' . $url ) ;

		return $url ;
	}

	/**
	 * Check if there is a queue for cron or not
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function has_queue()
	{
		$req_summary = self::get_summary() ;
		if ( ! empty( $req_summary[ 'queue' ] ) ) {
			return true ;
		}

		return false ;
	}

	/**
	 * Check if there is a placeholder cache folder
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function has_placehoder_cache()
	{
		return is_dir( LSCWP_CONTENT_DIR . '/cache/placeholder' ) ;
	}

	/**
	 * Save image placeholder summary
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _save_summary( $data )
	{
		update_option( self::DB_PLACEHOLDER_SUMMARY, $data ) ;
	}

	/**
	 * Read last time generated info
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function get_summary()
	{
		return get_option( self::DB_PLACEHOLDER_SUMMARY, array() ) ;
	}

	/**
	 * Generate realpath of placeholder file
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder_realpath( $size )
	{
		return LSCWP_CONTENT_DIR . "/cache/placeholder/$size." . md5( $this->_cfg_placeholder_resp_color ) ;
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public function rm_cache_folder()
	{
		if ( file_exists( LSCWP_CONTENT_DIR . '/cache/placeholder' ) ) {
			Litespeed_File::rrmdir( LSCWP_CONTENT_DIR . '/cache/placeholder' ) ;
		}

		// Clear placeholder in queue too
		$this->_save_summary( array() ) ;

		LiteSpeed_Cache_Log::debug2( '[Media] Cleared placeholder queue' ) ;
	}

	/**
	 * Cron placeholder generation
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function cron_placeholder( $continue = false )
	{
		$req_summary = self::get_summary() ;
		if ( empty( $req_summary[ 'queue' ] ) ) {
			return ;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
				return ;
			}
		}

		foreach ( $req_summary[ 'queue' ] as $v ) {
			LiteSpeed_Cache_Log::debug( '[Media] cron job [size] ' . $v ) ;

			self::get_instance()->_generate_placeholder( $v ) ;

			// only request first one
			if ( ! $continue ) {
				return ;
			}
		}
	}

	/**
	 * Send to LiteSpeed API to generate placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _generate_placeholder( $size )
	{
		$req_summary = self::get_summary() ;

		$file = $this->_placeholder_realpath( $size ) ;

		// Update request status
		$req_summary[ 'curr_request' ] = time() ;
		$this->_save_summary( $req_summary ) ;

		// Generate placeholder
		$req_data = array(
			'size'	=> $size,
			'color'	=> $this->_cfg_placeholder_resp_color,
		) ;
		$data = LiteSpeed_Cache_Admin_API::get( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PLACEHOLDER, $req_data, true ) ;

		LiteSpeed_Cache_Log::debug( '[Media] _generate_placeholder ' ) ;

		if ( strpos( $data, 'data:image/png;base64,' ) !== 0 ) {
			LiteSpeed_Cache_Log::debug( '[Media] failed to decode response: ' . $data ) ;
			return false ;
		}

		// Write to file
		Litespeed_File::save( $file, $data, true ) ;

		// Save summary data
		$req_summary[ 'last_spent' ] = time() - $req_summary[ 'curr_request' ] ;
		$req_summary[ 'last_request' ] = $req_summary[ 'curr_request' ] ;
		$req_summary[ 'curr_request' ] = 0 ;
		if ( ! empty( $req_summary[ 'queue' ] ) && in_array( $size, $req_summary[ 'queue' ] ) ) {
			unset( $req_summary[ 'queue' ][ array_search( $size, $req_summary[ 'queue' ] ) ] ) ;
		}

		$this->_save_summary( $req_summary ) ;

		LiteSpeed_Cache_Log::debug( '[Media] saved placeholder ' . $file ) ;

		LiteSpeed_Cache_Log::debug2( '[Media] placeholder con: ' . $data ) ;

		return $data ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_GENERATE_PLACEHOLDER :
				self::cron_placeholder( true ) ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
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