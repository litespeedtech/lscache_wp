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

	private $content ;
	private $wp_upload_dir ;

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
		if ( get_option( LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL ) ) {
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

		$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, 'webp' . $post_id ) ;
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

		$link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, 'orig' . $post_id ) ;
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
		$size_meta = get_post_meta( $post_id, LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_SIZE, true ) ;
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
			LiteSpeed_Cache_Log::debug( '[Media] run lazyload lib' ) ;

			$file = LSCWP_DIR . 'js/lazyload.min.js' ;

			$content = Litespeed_File::read( $file ) ;

			$static_file = LSCWP_CONTENT_DIR . '/cache/js/lazyload.js' ;

			// Save to cache folder to enable directly usage by .htacess
			if ( ! file_exists( $static_file ) ) {
				Litespeed_File::save( $static_file, $content, true ) ;
				LiteSpeed_Cache_Log::debug( '[Media] save lazyload lib to ' . $static_file ) ;
			}

			LiteSpeed_Cache_Control::set_cacheable() ;
			LiteSpeed_Cache_Control::set_public_forced( 'OPTM: lazyload js' ) ;
			LiteSpeed_Cache_Control::set_no_vary() ;
			LiteSpeed_Cache_Control::set_custom_ttl( 8640000 ) ;
			LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_MIN . '_LAZY' ) ;

			header( 'Content-Length: ' . strlen( $content ) ) ;
			header( 'Content-Type: application/x-javascript; charset=utf-8' ) ;

			echo $content ;
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
			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
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
		if ( $this->cfg_img_webp && $this->webp_support() ) {
			$this->_replace_buffer_img_webp() ;
		}

		$cfg_img_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY ) ;
		$cfg_iframe_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IFRAME_LAZY ) ;

		if ( $cfg_img_lazy ) {
			list( $src_list, $html_list ) = $this->_parse_img() ;
			$html_list_ori = $html_list ;
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
				LiteSpeed_Cache_Log::debug2( '[Media] bypassed base64 img' ) ;
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( '[Media] found: ' . $attrs[ 'src' ] ) ;

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) || ! empty( $attrs[ 'data-srcset' ] ) ) {
				LiteSpeed_Cache_Log::debug2( '[Media] bypassed' ) ;
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
		LiteSpeed_Cache_Log::debug2( '[Media] changing attach src: ' . $img[0] ) ;
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
		LiteSpeed_Cache_Log::debug2( '[Media] webp replacing: ' . $url ) ;
		if ( LiteSpeed_Cache_Utility::is_internal_file( $url ) ) {
			// check if has webp file
			if ( LiteSpeed_Cache_Utility::is_internal_file( $url  . '.webp' ) ) {
				$url .= '.webp' ;
			}
			else {
				LiteSpeed_Cache_Log::debug2( '[Media] no WebP file, bypassed' ) ;
				return false ;
			}
		}
		else {
			LiteSpeed_Cache_Log::debug2( '[Media] no file, bypassed' ) ;
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