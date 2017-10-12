<?php

/**
 * The utility class.
 *
 * @since      	1.1.5
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Utility
{
	/**
	 * Parse attributes from string
	 *
	 * @since  1.2.2
	 * @since  1.4 Moved from optimize to utility
	 * @access private
	 * @param  string $str
	 * @return array  All the attributes
	 */
	public static function parse_attr( $str )
	{
		$attrs = array() ;
		preg_match_all( '#(\w+)=["\']([^"\']*)["\']#isU', $str, $matches, PREG_SET_ORDER ) ;
		foreach ( $matches as $match ) {
			$attrs[ $match[ 1 ] ] = trim( $match[ 2 ] ) ;
		}
		return $attrs ;
	}

	/**
	 * Get url based on permalink setting
	 *
	 * @since  1.3
	 * @access public
	 * @return string
	 */
	public static function get_permalink_url( $relative_url )
	{
		return $GLOBALS[ 'wp_rewrite' ]->using_permalinks() ? home_url( $relative_url ) : home_url() . '/?' . $relative_url ;
	}

	/**
	 * Check if an array has a string
	 *
	 * Support $ exact match
	 *
	 * @since 1.3
	 * @access private
	 * @param string $needle The string to search with
	 * @param array $haystack
	 * @return bool|string False if not found, otherwise return the matched string in haystack.
	 */
	public static function str_hit_array( $needle, $haystack )
	{
		foreach( $haystack as $item ) {
			if ( substr( $item, -1 ) === '$' ) {
				// do exact match
				if ( substr( $item, 0, -1 ) === $needle ) {
					return $item ;
				}
			}
			elseif ( substr( $item, 0, 1 ) === '^' ) {
				// match beginning
				if ( substr( $item, 1 ) === substr( $needle, 0, strlen( $item ) - 1 ) ) {
					return $item ;
				}
			}
			else {
				if ( strpos( $needle, $item ) !== false ) {
					return $item ;
				}
			}
		}

		return false ;
	}

	/**
	 * Improve compatibility to PHP old versions
	 *
	 * @since  1.2.2
	 *
	 */
	public static function compatibility()
	{
		require_once LSWCP_DIR . 'lib/litespeed-php-compatibility.func.php' ;
	}

	/**
	 * Check if the host is the internal host
	 *
	 * @since  1.2.3
	 *
	 */
	public static function internal( $host )
	{
		if ( ! defined( 'LITESPEED_FRONTEND_HOST' ) ) {
			define( 'LITESPEED_FRONTEND_HOST', parse_url( get_option( 'home' ), PHP_URL_HOST ) ) ;
		}

		return $host === LITESPEED_FRONTEND_HOST ;
	}

	/**
	 * Convert URI to URL
	 *
	 * @since  1.3
	 * @access public
	 * @param  string $uri `xx/xx.html` or `/subfolder/xx/xx.html`
	 * @return  string http://www.example.com/subfolder/xx/xx.html
	 */
	public static function uri2url( $uri )
	{
		if ( substr( $uri, 0, 1 ) === '/' ) {
			self::domain_const() ;
			$url = LSCWP_DOMAIN . $uri ;
		}
		else {
			$url = home_url( '/' ) . $uri ;
		}

		return $url ;
	}

	/**
	 * Convert URL to URI
	 *
	 * @since  1.2.2
	 * @access public
	 */
	public static function url2uri( $url )
	{
		$url = trim( $url ) ;
		$uri = @parse_url( $url, PHP_URL_PATH ) ;
		return $uri ;
	}

	/**
	 * Make URL to be relative
	 *
	 * NOTE: for subfolder home_url, will keep subfolder part (strip nothing but scheme and host)
	 *
	 * @param  string $url
	 * @return string      Relative URL, start with /
	 */
	public static function make_relative( $url )
	{
		// replace home_url if the url is full url
		self::domain_const() ;
		if ( strpos( $url, LSCWP_DOMAIN ) === 0 ) {
			$url = substr( $url, strlen( LSCWP_DOMAIN ) ) ;
		}
		return trim( $url ) ;
	}

	/**
	 * Generate domain const
	 *
	 * This will generate http://www.example.com even there is a subfolder in home_url setting
	 *
	 * Const LSCWP_DOMAIN has NO trailing /
	 *
	 * @since  1.3
	 * @access public
	 */
	public static function domain_const()
	{
		if ( defined( 'LSCWP_DOMAIN' ) ) {
			return ;
		}

		$home_url = get_home_url( is_multisite() ? get_current_blog_id() : null ) ;

		self::compatibility() ;
		$domain = http_build_url( $home_url, array(), HTTP_URL_STRIP_ALL ) ;

		define( 'LSCWP_DOMAIN', $domain ) ;
	}

	/**
	 * Array map one textarea to sanitize the url
	 *
	 * @since  1.3
	 * @access public
	 * @param  string $content
	 * @param  bool $type String handler type
	 * @return string
	 */
	public static function sanitize_lines( $content, $type = null )
	{
		if ( ! $content ) {
			return $content ;
		}

		$arr = explode( "\n", $content ) ;
		if ( $type === 'uri' ) {
			$arr = array_map( 'LiteSpeed_Cache_Utility::url2uri', $arr ) ;
		}
		if ( $type === 'relative' ) {
			$arr = array_map( 'LiteSpeed_Cache_Utility::make_relative', $arr ) ;// Remove domain
		}
		$arr = array_map( 'trim', $arr ) ;
		$arr = array_unique( $arr ) ;
		$arr = array_filter( $arr ) ;
		return implode( "\n", $arr ) ;
	}

	/**
	 * Builds an url with an action and a nonce.
	 *
	 * Assumes user capabilities are already checked.
	 *
	 * @access public
	 * @param string $action The LSCWP_CTRL action to do in the url.
	 * @param string $ajax_action AJAX call's action
	 * @param string $append_str The appending string to url
	 * @return string The built url.
	 */
	public static function build_url( $action, $ajax_action = false, $append_str = false, $page = null )
	{
		$prefix = '?' ;

		if ( $ajax_action === false ) {
			if ( $page ) {
				// If use admin url
				if ( $page === true ) {
					$page = 'admin.php' ;
				}
				else {
					if ( strpos( $page, '?' ) !== false ) {
						$prefix = '&' ;
					}
				}
				$combined = $page . $prefix . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
			}
			else {
				// Current page rebuild URL
				$params = $_GET ;

				if ( ! empty( $params ) ) {
					if ( isset( $params[ 'LSCWP_CTRL' ] ) ) {
						unset( $params[ 'LSCWP_CTRL' ] ) ;
					}
					if ( isset( $params[ '_wpnonce' ] ) ) {
						unset( $params[ '_wpnonce' ] ) ;
					}
					if ( ! empty( $params ) ) {
						$prefix .= http_build_query( $params ) . '&' ;
					}
				}
				global $pagenow ;
				$combined = $pagenow . $prefix . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
			}
		}
		else {
			$combined = 'admin-ajax.php?action=' . $ajax_action . '&' . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
		}

		if ( is_network_admin() ) {
			$prenonce = network_admin_url( $combined ) ;
		}
		else {
			$prenonce = admin_url( $combined ) ;
		}
		$url = wp_nonce_url( $prenonce, $action, LiteSpeed_Cache::NONCE_NAME ) ;

		if ( $append_str ) {
			$url .= '&' . $append_str ;
		}

		return $url ;
	}
}



