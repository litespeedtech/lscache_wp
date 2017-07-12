<?php
/**
 * The Third Party integration with the Better WP Minify plugin.
 *
 * @since		1.0.12
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
    die() ;
}
LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_Better_WP_Minify') ;

class LiteSpeed_Cache_ThirdParty_Better_WP_Minify
{
	/**
	 * Detects if Better WP Minify is active.
	 *
	 * @since 1.0.12
	 * @access public
	 *
	 */
	public static function detect()
	{
		if ( class_exists('BWP_MINIFY') ) {
			add_action('toplevel_page_bwp_minify_general', 'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::maybe_flush', 9) ;
		}
	}

	/**
	 * Hooked to the toplevel_page_bwp_minify_general action.
	 *
	 * Will check parts of the request to see if the cache should be flushed.
	 * Will register functions to purge the cache if needed.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public static function maybe_flush()
	{
		if ( ! empty($_POST) && (isset($_POST['flush_cache']) || isset($_POST['save_flush'])) && ! BWP_MINIFY::is_normal_admin() ) {
			add_action('check_admin_referer', 'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::flush') ;
			add_action('bwp_option_action_before_submit_button', 'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::clear_flush') ;
		}
	}

	/**
	 * Purges the cache when Better WP Minify needs to purge.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public static function flush()
	{
		LiteSpeed_Cache_API::purge_all() ;
		self::clear_flush() ;
	}

	/**
	 * Clears the flush cache callbacks.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public static function clear_flush()
	{
		remove_action('check_admin_referer', 'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::flush') ;
		remove_action('bwp_option_action_before_submit_button', 'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::clear_flush') ;
	}

}
