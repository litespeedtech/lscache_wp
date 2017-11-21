<?php

/**
 * The CDN class.
 *
 * @since      	1.2.3
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_CDN
{
	private static $_instance ;

	const BYPASS = 'LITESPEED_BYPASS_CDN' ;

	private $content ;

	private $cfg_cdn ;
	private $cfg_url_ori ;
	private $cfg_cdn_url ;
	private $cfg_cdn_inc_img ;
	private $cfg_cdn_inc_css ;
	private $cfg_cdn_inc_js ;
	private $cfg_cdn_filetype ;
	private $cfg_cdn_exclude ;
	private $cfg_cdn_remote_jquery ;

	/**
	 * Init
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( 'CDN init' ) ;

		if ( ! $this->can_cdn() ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true ) ;
			}
			return ;
		}

		/**
		 * Remotely load jQuery
		 * This is separate from CDN on/off
		 * @since 1.5
		 */
		$this->cfg_cdn_remote_jquery = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_REMOTE_JQUERY ) ;
		if ( $this->cfg_cdn_remote_jquery ) {
			add_action( 'init', array( $this, 'load_jquery_remotely' ) ) ;
		}

		$this->cfg_cdn = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN ) ;
		if ( ! $this->cfg_cdn ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true ) ;
			}
			return ;
		}

		$this->cfg_url_ori = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_ORI ) ;
		$this->cfg_cdn_url = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_URL ) ;
		if ( ! $this->cfg_url_ori || ! $this->cfg_cdn_url ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true ) ;
			}
			return ;
		}

		// Check if need preg_replace
		if ( strpos( $this->cfg_url_ori, '*' ) !== false ) {
			LiteSpeed_Cache_Log::debug( 'CDN: wildcard rule in ' . $this->cfg_url_ori ) ;
			$this->cfg_url_ori = preg_quote( $this->cfg_url_ori, '#' ) ;
			$this->cfg_url_ori = str_replace( '\*', '.*', $this->cfg_url_ori ) ;
			LiteSpeed_Cache_Log::debug2( 'CDN: translated rule is ' . $this->cfg_url_ori ) ;
		}

		$this->cfg_cdn_inc_img = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_INC_IMG ) ;
		$this->cfg_cdn_inc_css = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_INC_CSS ) ;
		$this->cfg_cdn_inc_js = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_INC_JS ) ;
		$this->cfg_cdn_filetype = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_FILETYPE ) ;
		if ( ! $this->cfg_cdn_inc_img && ! $this->cfg_cdn_inc_css && ! $this->cfg_cdn_inc_js && ! $this->cfg_cdn_filetype ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true ) ;
			}
			return ;
		}

		$this->cfg_cdn_filetype = explode( "\n", $this->cfg_cdn_filetype ) ;

		$this->cfg_cdn_exclude = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_EXCLUDE ) ;
		$this->cfg_cdn_exclude = explode( "\n", $this->cfg_cdn_exclude ) ;

		if ( $this->cfg_cdn_inc_img ) {
			// Hook to srcset
			if ( function_exists( 'wp_calculate_image_srcset' ) ) {
				add_filter( 'wp_calculate_image_srcset', __CLASS__ . '::srcset', 999 ) ;
			}
			// Hook to mime icon
			add_filter( 'wp_get_attachment_image_src', __CLASS__ . '::attach_img_src', 999 ) ;
			add_filter( 'wp_get_attachment_url', __CLASS__ . '::url', 999 ) ;
		}

		if ( $this->cfg_cdn_inc_css ) {
			add_filter( 'style_loader_src', __CLASS__ . '::url', 999 ) ;
		}

		if ( $this->cfg_cdn_inc_js  ) {
			add_filter( 'script_loader_src', __CLASS__ . '::url', 999 ) ;
		}

	}

	/**
	 * If include css/js in CDN
	 *
	 * @since  1.6.2.1
	 * @return bool true if included in CDN
	 */
	public static function inc_type( $type )
	{
		$instance = self::get_instance() ;

		if ( $type == 'css' && $instance->cfg_cdn_inc_css ) {
			return true ;
		}

		if ( $type == 'js' && $instance->cfg_cdn_inc_js ) {
			return true ;
		}

		return false ;
	}

	/**
	 * Check if the host is the CDN internal host
	 *
	 * @since  1.2.3
	 *
	 */
	public static function internal( $host )
	{
		if ( defined( self::BYPASS ) ) {
			return false ;
		}

		if ( ! defined( 'LITESPEED_CDN_HOST' ) ) {
			$instance = self::get_instance() ;
			define( 'LITESPEED_CDN_HOST', parse_url( $instance->cfg_cdn_url, PHP_URL_HOST ) ) ;
		}

		return $host === LITESPEED_CDN_HOST ;
	}

	/**
	 * Run CDN process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * @since  1.2.3
	 * @access public
	 * @return  string The content that is after optimization
	 */
	public static function finalize( $content )
	{
		$instance = self::get_instance() ;
		$instance->content = $content ;

		$instance->_finalize() ;
		return $instance->content ;
	}

	/**
	 * Check if it can use CDN replacement
	 *
	 * @since  1.2.3
	 * @access public
	 */
	public function can_cdn()
	{
		if ( is_admin() ) {
			return false ;
		}

		if ( is_feed() ) {
			return false ;
		}

		if ( is_preview() ) {
			return false ;
		}

		/**
		 * Bypass login/reg page
		 * @since  1.6
		 */
		if ( in_array( $GLOBALS[ 'pagenow' ], array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			LiteSpeed_Cache_Log::debug( 'CDN bypassed as is login/reg page' ) ;
			return false ;
		}

		return true ;
	}

	/**
	 * Replace CDN url
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _finalize()
	{
		if ( defined( self::BYPASS ) ) {
			LiteSpeed_Cache_Log::debug2( 'CDN bypass' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'CDN _finalize' ) ;

		// Start replacing img src
		if ( $this->cfg_cdn_inc_img ) {
			$this->_replace_img() ;
			$this->_replace_inline_css() ;
		}

		if ( $this->cfg_cdn_filetype ) {
			$this->_replace_file_types() ;
		}

	}

	/**
	 * Parse all file types
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _replace_file_types()
	{
		preg_match_all( '#(src|data-src|href)\s*=\s*[\'"]([^\'"]+)[\'"]#i', $this->content, $matches ) ;
		if ( empty( $matches[ 2 ] ) ) {
			return ;
		}
		foreach ( $matches[ 2 ] as $k => $url ) {
			$url_parsed = parse_url( $url ) ;
			if ( empty( $url_parsed[ 'path' ] ) ) {
				continue ;
			}
			$postfix = substr( $url_parsed[ 'path' ], strrpos( $url_parsed[ 'path' ], '.' ) ) ;
			if ( ! in_array( $postfix, $this->cfg_cdn_filetype ) ) {
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( 'CDN matched file_type ' . $postfix . ' : ' . $url ) ;

			if( ! $url2 = $this->rewrite( $url ) ) {
				continue ;
			}

			$attr = str_replace( $url, $url2, $matches[ 0 ][ $k ] ) ;
			$this->content = str_replace( $matches[ 0 ][ $k ], $attr, $this->content ) ;
		}
	}

	/**
	 * Parse all images
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _replace_img()
	{
		preg_match_all( '#<img([^>]+?)src=([\'"\\\]*)([^\'"\s\\\>]+)([\'"\\\]*)([^>]*)>#i', $this->content, $matches ) ;
		foreach ( $matches[ 3 ] as $k => $url ) {
			// Check if is a DATA-URI
			if ( strpos( $url, 'data:image' ) !== false ) {
				continue ;
			}

			if ( ! $url2 = $this->rewrite( $url ) ) {
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
	 * Parse and replace all inline styles containing url()
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _replace_inline_css()
	{
		// preg_match_all( '/url\s*\(\s*(?!["\']?data:)(?![\'|\"]?[\#|\%|])([^)]+)\s*\)([^;},\s]*)/i', $this->content, $matches ) ;
		preg_match_all( '#url\((?![\'"]?data)[\'"]?([^\)\'"]+)[\'"]?\)#i', $this->content, $matches ) ;
		foreach ( $matches[ 1 ] as $k => $url ) {
			$url = str_replace( array( ' ', '\t', '\n', '\r', '\0', '\x0B', '"', "'", '&quot;', '&#039;' ), '', $url ) ;

			if ( ! $url2 = $this->rewrite( $url ) ) {
				continue ;
			}
			$attr = str_replace( $matches[ 1 ][ $k ], $url2, $matches[ 0 ][ $k ] ) ;
			$this->content = str_replace( $matches[ 0 ][ $k ], $attr, $this->content ) ;
		}
	}

	/**
	 * Hook to wp_get_attachment_image_src
	 *
	 * @since  1.2.3
	 * @access public
	 * @param  array $img The URL of the attachment image src, the width, the height
	 * @return array
	 */
	public static function attach_img_src( $img )
	{
		$instance = self::get_instance() ;
		if ( $img && $url = $instance->rewrite( $img[ 0 ] ) ) {
			$img[ 0 ] = $url ;
		}
		return $img ;
	}

	/**
	 * Try to rewrite one URL with CDN
	 *
	 * @since  1.2.3
	 * @access public
	 * @param  string $url
	 * @return string
	 */
	public static function url( $url )
	{
		$instance = self::get_instance() ;
		if ( $url && $url2 = $instance->rewrite( $url ) ) {
			$url = $url2 ;
		}
		return $url ;
	}

	/**
	 * Hook to replace WP responsive images
	 *
	 * @since  1.2.3
	 * @access public
	 * @param  array $srcs
	 * @return array
	 */
	public static function srcset( $srcs )
	{
		if ( $srcs ) {
			$instance = self::get_instance() ;
			foreach ( $srcs as $w => $data ) {
				if( ! $url = $instance->rewrite( $data[ 'url' ] ) ) {
					continue ;
				}
				$srcs[ $w ][ 'url' ] = $url ;
			}
		}
		return $srcs ;
	}

	/**
	 * Replace URL to CDN URL
	 *
	 * @since  1.2.3
	 * @access public
	 * @param  string $url
	 * @return string        Replaced URL
	 */
	public function rewrite( $url )
	{
		LiteSpeed_Cache_Log::debug2( 'CDN: try rewriting ' . $url ) ;
		$url_parsed = parse_url( $url ) ;

		// Only images under wp-cotnent/wp-includes can be replaced
		if ( stripos( $url_parsed[ 'path' ], LSWCP_CONTENT_FOLDER ) === false && stripos( $url_parsed[ 'path' ], 'wp-includes' ) === false  && stripos( $url_parsed[ 'path' ], '/min/' ) === false ) {
			if ( ! defined( 'UPLOADS' ) || stripos( $url_parsed[ 'path' ], UPLOADS ) === false ) {
				LiteSpeed_Cache_Log::debug2( 'CDN:    rewriting failed: path not match: ' . LSWCP_CONTENT_FOLDER ) ;
				return false ;
			}
		}

		// Check if is external url
		if ( ! empty( $url_parsed[ 'host' ] ) && ! LiteSpeed_Cache_Utility::internal( $url_parsed[ 'host' ] ) ) {
			LiteSpeed_Cache_Log::debug2( 'CDN:    rewriting failed: host not internal' ) ;
			return false ;
		}

		if ( $this->cfg_cdn_exclude ) {
			foreach ( $this->cfg_cdn_exclude as $exclude ) {
				if ( stripos( $url, $exclude ) !== false ) {
					LiteSpeed_Cache_Log::debug2( 'CDN:    Abort excludes ' . $exclude ) ;
					return false ;
				}
			}
		}

		// Fill full url before replacement
		if ( empty( $url_parsed[ 'host' ] ) ) {
			$url = LiteSpeed_Cache_Utility::uri2url( $url ) ;
			LiteSpeed_Cache_Log::debug2( 'CDN:    fill before rewritten: ' . $url ) ;

			$url_parsed = parse_url( $url ) ;
		}

		$scheme = ! empty( $url_parsed[ 'scheme' ] ) ? $url_parsed[ 'scheme' ] . ':' : '' ;
		if ( $scheme ) {
			LiteSpeed_Cache_Log::debug2( 'CDN:    scheme from url: ' . $scheme ) ;
		}

		// Now lets replace CDN url
		if ( strpos( $this->cfg_url_ori, '*' ) !== false ) {
			$url = preg_replace( '#' . $scheme . $this->cfg_url_ori . '#iU', $this->cfg_cdn_url, $url ) ;
		}
		else {
			$url = str_replace( $scheme . $this->cfg_url_ori, $this->cfg_cdn_url, $url ) ;
		}
		LiteSpeed_Cache_Log::debug2( 'CDN:    after rewritten: ' . $url ) ;

		return $url ;
	}

	/**
	 * Remote load jQuery remotely
	 *
	 * @since  1.5
	 * @access public
	 */
	public function load_jquery_remotely()
	{
		// default jq version
		$v = '1.12.4' ;

		// load wp's jq version
		global $wp_scripts ;
		if ( isset( $wp_scripts->registered[ 'jquery' ]->ver ) ) {
			$v = $wp_scripts->registered[ 'jquery' ]->ver ;
		}

		$src = $this->cfg_cdn_remote_jquery === LiteSpeed_Cache_Config::VAL_ON ? "//ajax.googleapis.com/ajax/libs/jquery/$v/jquery.min.js" : "//cdnjs.cloudflare.com/ajax/libs/jquery/$v/jquery.min.js" ;

		LiteSpeed_Cache_Log::debug2( 'CDN: load_jquery_remotely: ' . $src ) ;

		wp_deregister_script( 'jquery' ) ;

		wp_register_script( 'jquery', $src, false, $v ) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.2.3
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}



