<?php
/**
 * The Third Party integration with DIVI Theme.
 *
 * @since		2.9.0
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	die() ;
}
LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_Wpml' ) ;

class LiteSpeed_Cache_ThirdParty_Wpml
{
	private static $sub_domains = array() ;

	public static function detect()
	{
		if ( ! function_exists( 'icl_object_id' ) ) {
			return ;
		}

		$wpml_domain = get_option( 'icl_sitepress_settings', array() ) ;

		$setting_exists = isset( $wpml_domain[ 'language_domains' ] )
			&& ! empty ( $wpml_domain[ 'language_domains' ] )
			&& is_array ( $wpml_domain[ 'language_domains' ] ) ;

		if ( $setting_exists ) {
			$wpml_domains = $wpml_domain['language_domains'] ;

			foreach ( $wpml_domains as $lang => $url ) {
				array_push( self::$sub_domains, $url ) ;
			}

			add_filter( 'litespeed_cache_internal_sub_domains', 'LiteSpeed_Cache_ThirdParty_Wpml::hook_multi_url' ) ;
		}
	}

	public static function hook_multi_url( $value )
	{
		if ( ! empty( self::$sub_domains ) ) {
			$value = array_merge( $value, self::$sub_domains ) ;
		}

		return $value ;
	}
}
