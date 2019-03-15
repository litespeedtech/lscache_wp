<?php
/**
 * The Third Party integration with WPML.
 *
 * @since		2.9.4
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
defined( 'WPINC' ) || exit ;

LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_WPML' ) ;


class LiteSpeed_Cache_ThirdParty_WPML
{

	public static function detect()
	{
		if ( ! defined( 'WPML_PLUGIN_BASENAME' ) ) return ;

		add_filter( 'litespeed_internal_domains', __CLASS__ . '::append_domains' ) ;
	}

	/**
	 * Take language domains as internal domains
	 */
	public static function append_domains( $domains )
	{
		$wpml_domains = apply_filters( 'wpml_setting', false, 'language_domains' ) ;
		if ( $wpml_domains ) {
			$domains = array_merge( $domains, array_values( $wpml_domains ) ) ;
		}

		return $domains ;
	}

}