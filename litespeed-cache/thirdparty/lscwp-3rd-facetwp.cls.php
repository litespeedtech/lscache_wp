<?php
/**
 * The Third Party integration with FacetWP.
 *
 * @since		2.9.9
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
		 * For Facetwp, if the template is "wp", return the buffered HTML
		 * So marked as rest call to put is_json to ESI
		 */
		if (
			! empty( $_POST['action'] )
			&& ! empty( $_POST['data'] )
			&& ! empty( $_POST['data']['template'] )
			&& $_POST['data']['template'] === 'wp'
		) {
			LiteSpeed_Cache_API::hook_esi_param( 'LiteSpeed_Cache_ThirdParty_Facetwp::set_is_json' ) ;
		}
	}

 	public static function set_is_json( $params )
	{
		$params[ 'is_json' ] = 1 ;
		return $params ;
	}
}