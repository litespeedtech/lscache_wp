<?php
/**
 * The Third Party integration with FacetWP.
 *
 * @since		2.9.2
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die() ;
}
LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_Facetwp' ) ;

class LiteSpeed_Cache_ThirdParty_Facetwp
{
	public static function detect()
	{
		if ( ! defined( 'FACETWP_VERSION' ) ) return ;

		/**
		 * Following Facetwp rules to determine is the request from their app
		 * json_encoded admin_bar for wp_json
		 *
		 * FacetWP_Ajax::intercept_request()
		 */

		if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' ) return ;

		$valid_actions = array(
			'facetwp_refresh',
			'facetwp_autocomplete_load'
		);

		$action = isset( $_POST[ 'action' ] ) ? $_POST[ 'action' ] : '';

		$in_valid_actions = in_array( $action, $valid_actions ) ;
		$wp_template = $_POST[ 'data' ][ 'template' ] === 'wp' ;
		$_autocomplete_load = $action === 'facetwp_autocomplete_load' ;

		if ( $in_valid_actions && $wp_template && ! $_autocomplete_load ) {
			add_filter('litespeed_cache_sub_esi_params-admin-bar', 'LiteSpeed_Cache_ThirdParty_Facetwp::esi_admin_bar_add_slash');
		}
	}

	public static function esi_admin_bar_add_slash( $params )
	{
		$params[ 'is_json' ] = 1 ;
		$params[ '_ls_silence' ] = 1 ;

		return $params ;
	}

}
