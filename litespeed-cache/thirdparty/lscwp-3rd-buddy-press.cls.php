<?php
/**
 * The Third Party integration with Buddy Press.
 *
 * @since		2.9.8.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	die() ;
}

LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_Buddy_Press' ) ;

class LiteSpeed_Cache_ThirdParty_Buddy_Press
{
	public static function detect()
	{
		if ( ! defined( 'BP_PLUGIN_DIR' ) ) return ;

		/*
		* Convert menu to private cache by user
		*/
		add_filter( 'wp_nav_menu', 'LiteSpeed_Cache_ThirdParty_Buddy_Press::make_esi_menu', 10, 2 ) ;
		LiteSpeed_Cache_API::hook_tpl_esi( 'bp-menu', 'LiteSpeed_Cache_ThirdParty_Buddy_Press::hook_esi' ) ;

		/*
		* Add tag to every users for purge on action
		*/
		if ( is_user_logged_in() ) {
			LiteSpeed_Cache_API::tag_add( 'buddy_press_user_' . get_current_user_id() ) ;
		}

		// Purge on profile update
		add_action( 'xprofile_updated_profile', 'LiteSpeed_Cache_ThirdParty_Buddy_Press::purge_on_profile_update' ) ;
		// Purge on avatar update
		add_action( 'xprofile_avatar_uploaded', 'LiteSpeed_Cache_ThirdParty_Buddy_Press::purge_on_profile_update' ) ;
		// Purge on cover image update
		if ( ! empty( $_POST[ 'action' ] ) && $_POST[ 'action' ] === 'bp_cover_image_upload' ) {
			if ( empty( $bp_params = $_POST[ 'bp_params' ] ) ) return ;
			if ( empty( $user_id = $bp_params[ 'item_id' ] ) ) return ;
			LiteSpeed_Cache_ThirdParty_Buddy_Press::purge_on_profile_update( $user_id ) ;
		}
	}

	public static function make_esi_menu( $nav_menu, $args )
	{
		if ( method_exists( 'LiteSpeed_Cache_API', 'esi_enabled' ) && LiteSpeed_Cache_API::esi_enabled() && ! isset( $_GET[ 'lsesi' ] ) ) {
			$term_id = $args->menu->term_id ;
			$menu_class = $args->menu_class ;
			$esi_args = array( $term_id, $menu_class ) ;

			$esi_url = LiteSpeed_Cache_API::esi_url( 'bp-menu', 'BP Menu', $esi_args, 'private,no-vary' ) ;
			return $esi_url ;
		}

		return $nav_menu ;
	}

	public static function hook_esi( $args )
	{
		$menu_array = array( 'menu' => $args[ 0 ], 'menu_class' => $args[ 1 ] ) ;
		wp_nav_menu( $menu_array ) ;
		exit;
	}

	public static function purge_on_profile_update( $user_id ) {
		LiteSpeed_Cache_API::purge( 'buddy_press_user_' . $user_id ) ;
	}
}
