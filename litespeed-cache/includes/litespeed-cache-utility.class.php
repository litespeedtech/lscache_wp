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
	 * Make URL to be relative
	 *
	 * @param  string $url
	 * @return string      Relative URL
	 */
	public static function make_relative( $url )
	{
		// replace site_url if the url is full url
		// NOTE: for subfolder site_url, need to strip subfolder part (strip anything but scheme and host)
		require_once LSWCP_DIR . 'lib/litespeed-php-compatibility.func.php' ;
		$site_url_domain = http_build_url( LiteSpeed_Cache_Router::get_siteurl(), array(), HTTP_URL_STRIP_ALL ) ;
		if ( strpos( $url, $site_url_domain ) === 0 ) {
			$url = substr( $url, strlen( $site_url_domain ) ) ;
		}
		return trim( $url ) ;
	}
}