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

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_CDN
{
	private static $_instance ;

	const BYPASS = 'LITESPEED_BYPASS_CDN' ;

	private $content ;

	private $_cfg_cdn ;
	private $_cfg_url_ori ;
	private $_cfg_ori_dir ;
	private $_cfg_cdn_mapping = array() ;
	private $_cfg_cdn_exclude ;
	private $_cfg_cdn_remote_jquery ;

	private $cdn_mapping_hosts = array() ;

	private $__cfg ;// cfg instance

	/**
	 * Init
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( 'CDN init' ) ;

		if ( ! LiteSpeed_Cache_Router::can_cdn() ) {
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
		$this->_cfg_cdn_remote_jquery = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_REMOTE_JQUERY ) ;
		if ( $this->_cfg_cdn_remote_jquery ) {
			$this->_load_jquery_remotely() ;
		}

		$this->_cfg_cdn = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN ) ;
		if ( ! $this->_cfg_cdn ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true ) ;
			}
			return ;
		}

		$this->__cfg = LiteSpeed_Cache_Config::get_instance() ;

		$this->_cfg_url_ori = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_ORI ) ;
		$cfg_cdn_url = $this->__cfg->get_item( LiteSpeed_Cache_Config::ITEM_CDN_MAPPING ) ;
		// Parse cdn mapping data to array( 'filetype' => 'url' )
		$mapping_to_check = array(
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS
		) ;
		foreach ( $cfg_cdn_url as $v ) {
			if ( ! $v[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL ] ) {
				continue ;
			}
			$this_url = $v[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL ] ;
			$this_host = parse_url( $this_url, PHP_URL_HOST ) ;
			// Check img/css/js
			foreach ( $mapping_to_check as $to_check ) {
				if ( $v[ $to_check ] ) {
					LiteSpeed_Cache_Log::debug2( '[CDN] mapping ' . $to_check . ' -> ' . $this_url ) ;

					// If filetype to url is one to many, make url be an array
					$this->_append_cdn_mapping( $to_check, $this_url ) ;

					if ( ! in_array( $this_host, $this->cdn_mapping_hosts ) ) {
						$this->cdn_mapping_hosts[] = $this_host ;
					}
				}
			}
			// Check file types
			if ( $v[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ] ) {
				$filetypes = array_map( 'trim', explode( "\n", $v[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ] ) ) ;
				foreach ( $filetypes as $v2 ) {
					if ( $v2 ) {
						$this->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ] = true ;

						// If filetype to url is one to many, make url be an array
						$this->_append_cdn_mapping( $v2, $this_url ) ;

						if ( ! in_array( $this_host, $this->cdn_mapping_hosts ) ) {
							$this->cdn_mapping_hosts[] = $this_host ;
						}
					}
				}
				LiteSpeed_Cache_Log::debug2( '[CDN] mapping ' . implode( ',', $filetypes ) . ' -> ' . $this_url ) ;
			}
		}

		if ( ! $this->_cfg_url_ori || ! $this->_cfg_cdn_mapping ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true ) ;
			}
			return ;
		}

		$this->_cfg_ori_dir = $this->__cfg->get_item( LiteSpeed_Cache_Config::ITEM_CDN_ORI_DIR ) ;
		// In case user customized upload path
		if ( defined( 'UPLOADS' ) ) {
			$this->_cfg_ori_dir[] = UPLOADS ;
		}

		// Check if need preg_replace
		if ( strpos( $this->_cfg_url_ori, '*' ) !== false ) {
			LiteSpeed_Cache_Log::debug( '[CDN] wildcard rule in ' . $this->_cfg_url_ori ) ;
			$this->_cfg_url_ori = preg_quote( $this->_cfg_url_ori, '#' ) ;
			$this->_cfg_url_ori = str_replace( '\*', '.*', $this->_cfg_url_ori ) ;
			LiteSpeed_Cache_Log::debug2( '[CDN] translated rule is ' . $this->_cfg_url_ori ) ;
		}

		$this->_cfg_url_ori = explode( ',', $this->_cfg_url_ori ) ;

		$this->_cfg_cdn_exclude = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_EXCLUDE ) ;
		$this->_cfg_cdn_exclude = $this->_cfg_cdn_exclude ? explode( "\n", $this->_cfg_cdn_exclude ) : array() ;// todo: convert to cfg->get_item()

		if ( ! empty( $this->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ] ) ) {
			// Hook to srcset
			if ( function_exists( 'wp_calculate_image_srcset' ) ) {
				add_filter( 'wp_calculate_image_srcset', array( $this, 'srcset' ), 999 ) ;
			}
			// Hook to mime icon
			add_filter( 'wp_get_attachment_image_src', array( $this, 'attach_img_src' ), 999 ) ;
			add_filter( 'wp_get_attachment_url', array( $this, 'url_img' ), 999 ) ;
		}

		if ( ! empty( $this->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS ] ) ) {
			add_filter( 'style_loader_src', array( $this, 'url_css' ), 999 ) ;
		}

		if ( ! empty( $this->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS ] ) ) {
			add_filter( 'script_loader_src', array( $this, 'url_js' ), 999 ) ;
		}

	}

	/**
	 * Associate all filetypes with url
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _append_cdn_mapping( $filetype, $url )
	{
		// If filetype to url is one to many, make url be an array
		if ( empty( $this->_cfg_cdn_mapping[ $filetype ] ) ) {
			$this->_cfg_cdn_mapping[ $filetype ] = $url ;
		}
		elseif ( is_array( $this->_cfg_cdn_mapping[ $filetype ] ) ) {
			// Append url to filetype
			$this->_cfg_cdn_mapping[ $filetype ][] = $url ;
		}
		else {
			// Convert _cfg_cdn_mapping from string to array
			$this->_cfg_cdn_mapping[ $filetype ] = array( $this->_cfg_cdn_mapping[ $filetype ], $url ) ;
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.7.2
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
	 * If include css/js in CDN
	 *
	 * @since  1.6.2.1
	 * @return bool true if included in CDN
	 */
	public static function inc_type( $type )
	{
		$instance = self::get_instance() ;

		if ( $type == 'css' && ! empty( $instance->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS ] ) ) {
			return true ;
		}

		if ( $type == 'js' && ! empty( $instance->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS ] ) ) {
			return true ;
		}

		return false ;
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
		if ( ! empty( $this->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ] ) ) {
			$this->_replace_img() ;
			$this->_replace_inline_css() ;
		}

		if ( ! empty( $this->_cfg_cdn_mapping[ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ] ) ) {
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
		preg_match_all( '#(src|data-src|href)\s*=\s*[\'"]([^\'"\\\]+)[\'"]#i', $this->content, $matches ) ;
		if ( empty( $matches[ 2 ] ) ) {
			return ;
		}

		$filetypes = array_keys( $this->_cfg_cdn_mapping ) ;
		foreach ( $matches[ 2 ] as $k => $url ) {
			$url_parsed = parse_url( $url ) ;
			if ( empty( $url_parsed[ 'path' ] ) ) {
				continue ;
			}
			$postfix = substr( $url_parsed[ 'path' ], strrpos( $url_parsed[ 'path' ], '.' ) ) ;
			if ( ! in_array( $postfix, $filetypes ) ) {
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( 'CDN matched file_type ' . $postfix . ' : ' . $url ) ;

			if( ! $url2 = $this->rewrite( $url, LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE, $postfix ) ) {
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

			if ( ! $url2 = $this->rewrite( $url, LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ) ) {
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

		/**
		 * Excludes `\` from URL matching
		 * @see  #959152 - Wordpress LSCache CDN Mapping causing malformed URLS
		 * @see  #685485
		 * @since 3.0
		 */
		preg_match_all( '#url\((?![\'"]?data)[\'"]?([^\)\'"\\\]+)[\'"]?\)#i', $this->content, $matches ) ;
		foreach ( $matches[ 1 ] as $k => $url ) {
			$url = str_replace( array( ' ', '\t', '\n', '\r', '\0', '\x0B', '"', "'", '&quot;', '&#039;' ), '', $url ) ;

			if ( ! $url2 = $this->rewrite( $url, LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ) ) {
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
	 * @since  1.7 Removed static from function
	 * @access public
	 * @param  array $img The URL of the attachment image src, the width, the height
	 * @return array
	 */
	public function attach_img_src( $img )
	{
		if ( $img && $url = $this->rewrite( $img[ 0 ], LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ) ) {
			$img[ 0 ] = $url ;
		}
		return $img ;
	}

	/**
	 * Try to rewrite one URL with CDN
	 *
	 * @since  1.7
	 * @access public
	 */
	public function url_img( $url )
	{
		if ( $url && $url2 = $this->rewrite( $url, LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ) ) {
			$url = $url2 ;
		}
		return $url ;
	}

	/**
	 * Try to rewrite one URL with CDN
	 *
	 * @since  1.7
	 * @access public
	 */
	public function url_css( $url )
	{
		if ( $url && $url2 = $this->rewrite( $url, LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS ) ) {
			$url = $url2 ;
		}
		return $url ;
	}

	/**
	 * Try to rewrite one URL with CDN
	 *
	 * @since  1.7
	 * @access public
	 */
	public function url_js( $url )
	{
		if ( $url && $url2 = $this->rewrite( $url, LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS ) ) {
			$url = $url2 ;
		}
		return $url ;
	}

	/**
	 * Hook to replace WP responsive images
	 *
	 * @since  1.2.3
	 * @since  1.7 Removed static from function
	 * @access public
	 * @param  array $srcs
	 * @return array
	 */
	public function srcset( $srcs )
	{
		if ( $srcs ) {
			foreach ( $srcs as $w => $data ) {
				if( ! $url = $this->rewrite( $data[ 'url' ], LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ) ) {
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
	public function rewrite( $url, $mapping_kind, $postfix = false )
	{
		LiteSpeed_Cache_Log::debug2( '[CDN] rewrite ' . $url ) ;
		$url_parsed = parse_url( $url ) ;

		if ( empty( $url_parsed[ 'path' ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[CDN] -rewrite bypassed: no path' ) ;
			return false ;
		}

		// Only images under wp-cotnent/wp-includes can be replaced
		$is_internal_folder = LiteSpeed_Cache_Utility::str_hit_array( $url_parsed[ 'path' ], $this->_cfg_ori_dir ) ;
		if ( ! $is_internal_folder ) {
			LiteSpeed_Cache_Log::debug2( '[CDN] -rewrite failed: path not match: ' . LSCWP_CONTENT_FOLDER ) ;
			return false ;
		}

		// Check if is external url
		if ( ! empty( $url_parsed[ 'host' ] ) ) {
			if ( ! LiteSpeed_Cache_Utility::internal( $url_parsed[ 'host' ] ) && ! $this->_is_ori_url( $url ) ) {
				LiteSpeed_Cache_Log::debug2( '[CDN] -rewrite failed: host not internal' ) ;
				return false ;
			}
		}

		if ( $this->_cfg_cdn_exclude ) {
			$exclude = LiteSpeed_Cache_Utility::str_hit_array( $url, $this->_cfg_cdn_exclude ) ;
			if ( $exclude ) {
				LiteSpeed_Cache_Log::debug2( '[CDN] -abort excludes ' . $exclude ) ;
				return false ;
			}
		}

		// Fill full url before replacement
		if ( empty( $url_parsed[ 'host' ] ) ) {
			$url = LiteSpeed_Cache_Utility::uri2url( $url ) ;
			LiteSpeed_Cache_Log::debug2( '[CDN] -fill before rewritten: ' . $url ) ;

			$url_parsed = parse_url( $url ) ;
		}

		$scheme = ! empty( $url_parsed[ 'scheme' ] ) ? $url_parsed[ 'scheme' ] . ':' : '' ;
		if ( $scheme ) {
			// LiteSpeed_Cache_Log::debug2( '[CDN] -scheme from url: ' . $scheme ) ;
		}

		// Find the mapping url to be replaced to
		if ( empty( $this->_cfg_cdn_mapping[ $mapping_kind ] ) ) {
			return false ;
		}
		if ( $mapping_kind !== LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ) {
			$final_url = $this->_cfg_cdn_mapping[ $mapping_kind ] ;
		}
		else {
			// select from file type
			$final_url = $this->_cfg_cdn_mapping[ $postfix ] ;
		}

		// If filetype to url is one to many, need to random one
		if ( is_array( $final_url ) ) {
			$final_url = $final_url[ mt_rand( 0, count( $final_url ) - 1 ) ] ;
		}

		// Now lets replace CDN url
		foreach ( $this->_cfg_url_ori as $v ) {
			if ( strpos( $v, '*' ) !== false ) {
				$url = preg_replace( '#' . $scheme . $v . '#iU', $final_url, $url ) ;
			}
			else {
				$url = str_replace( $scheme . $v, $final_url, $url ) ;
			}
		}
		LiteSpeed_Cache_Log::debug2( '[CDN] -rewritten: ' . $url ) ;

		return $url ;
	}

	/**
	 * Check if is orignal URL of CDN or not
	 *
	 * @since  2.1
	 * @access private
	 */
	private function _is_ori_url( $url )
	{
		$url_parsed = parse_url( $url ) ;

		$scheme = ! empty( $url_parsed[ 'scheme' ] ) ? $url_parsed[ 'scheme' ] . ':' : '' ;

		foreach ( $this->_cfg_url_ori as $v ) {
			$needle = $scheme . $v ;
			if ( strpos( $v, '*' ) !== false ) {
				if( preg_match( '#' . $needle . '#iU', $url ) ) {
					return true ;
				}
			}
			else {
				if ( strpos( $url, $needle ) === 0 ) {
					return true ;
				}
			}
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

		$instance = self::get_instance() ;

		return in_array( $host, $instance->cdn_mapping_hosts ) ;// todo: can add $this->_is_ori_url() check in future
	}

	/**
	 * Remote load jQuery remotely
	 *
	 * @since  1.5
	 * @since  2.9.8 Changed to private
	 * @access private
	 */
	private function _load_jquery_remotely()
	{
		// default jq version
		$v = '1.12.4' ;

		// load wp's jq version
		global $wp_scripts ;
		if ( isset( $wp_scripts->registered[ 'jquery-core' ]->ver ) ) {
			$v = $wp_scripts->registered[ 'jquery-core' ]->ver ;
			// Remove all unexpected chars to fix WP5.2.1 jq version issue @see https://wordpress.org/support/topic/problem-with-wordpress-5-2-1/
			$v = preg_replace( '|[^\d\.]|', '', $v ) ;
		}

		$src = $this->_cfg_cdn_remote_jquery === LiteSpeed_Cache_Config::VAL_ON ? "//ajax.googleapis.com/ajax/libs/jquery/$v/jquery.min.js" : "//cdnjs.cloudflare.com/ajax/libs/jquery/$v/jquery.min.js" ;

		LiteSpeed_Cache_Log::debug2( '[CDN] load_jquery_remotely: ' . $src ) ;

		wp_deregister_script( 'jquery-core' ) ;

		wp_register_script( 'jquery-core', $src, false, $v ) ;
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
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}
