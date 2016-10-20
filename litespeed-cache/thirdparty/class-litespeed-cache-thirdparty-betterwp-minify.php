<?php

/**
 * The Third Party integration with the Better WP Minify plugin.
 *
 * @since		1.0.12
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
    die();
}

class LiteSpeed_Cache_ThirdParty_Better_WP_Minify
{
	public static function detect()
	{
		if (class_exists('BWP_MINIFY')) {
			add_action('toplevel_page_bwp_minify_general',
				'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::maybe_flush', 9);
		}
	}

	public static function maybe_flush()
	{
		if ((!empty($_POST))
			&& (isset($_POST['flush_cache']) || isset($_POST['save_flush']))
			&& (!BWP_MINIFY::is_normal_admin())) {
			add_action('check_admin_referer',
				'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::flush');
			add_action('bwp_option_action_before_submit_button',
				'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::clear_flush');
		}
	}

	public static function flush()
	{
		LiteSpeed_Cache_Tags::add_purge_tag('*');
		self::clear_flush();
	}

	public static function clear_flush()
	{
		remove_action('check_admin_referer',
			'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::flush');
		remove_action('bwp_option_action_before_submit_button',
			'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::clear_flush');
	}


}

add_action('litespeed_cache_detect_thirdparty',
	'LiteSpeed_Cache_ThirdParty_Better_WP_Minify::detect');

