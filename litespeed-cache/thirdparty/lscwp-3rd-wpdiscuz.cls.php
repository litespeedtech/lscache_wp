<?php
/**
 * The Third Party integration with Wpdiscuz.
 *
 * @since		2.9.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}

LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_Wpdiscuz' ) ;

class LiteSpeed_Cache_ThirdParty_Wpdiscuz
{
	public static function detect()
	{
		if ( ! defined( 'WPDISCUZ_DS' ) ) return ;

		LiteSpeed_Cache_ThirdParty_Wpdiscuz::check_commenter() ;
		add_action( 'wpdiscuz_add_comment', 'LiteSpeed_Cache_ThirdParty_Wpdiscuz::add_comment' ) ;

	}

	public static function add_comment()
	{
		LiteSpeed_Cache_Vary::get_instance()->append_commenter() ;
	}

	public static function check_commenter()
	{
		$commentor = wp_get_current_commenter() ;

		if ( strlen( $commentor[ 'comment_author' ] ) > 0 ) {
			add_filter( 'litespeed_vary_check_commenter_pending', '__return_false' ) ;
		}
	}
}
