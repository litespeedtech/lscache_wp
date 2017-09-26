<?php

/**
 * The utility class.
 *
 * @since      1.1.5
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Utility
{

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
	public static function is_in_array( $needle, $haystack )
	{
		foreach( $haystack as $item ) {
			// do exact match
			if ( substr( $item, -1 ) === '$' ) {
				if ( $needle === substr( $item, 1 ) ) {
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
	 * Convert URL to URI
	 *
	 * @since  1.2.2
	 *
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
	 * NOTE: for subfolder site_url, need to strip subfolder part (strip anything but scheme and host)
	 *
	 * @param  string $url
	 * @return string      Relative URL, start with /
	 */
	public static function make_relative( $url )
	{
		// replace site_url if the url is full url
		self::compatibility() ;
		$site_url_domain = http_build_url( LiteSpeed_Cache_Router::get_siteurl(), array(), HTTP_URL_STRIP_ALL ) ;
		if ( strpos( $url, $site_url_domain ) === 0 ) {
			$url = substr( $url, strlen( $site_url_domain ) ) ;
		}
		return trim( $url ) ;
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



