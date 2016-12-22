<?php

/**
 * The Third Party integration with the Autoptimize plugin.
 *
 * @since		1.0.12
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
	die();
}

class LiteSpeed_Cache_ThirdParty_Autoptimize
{
	/**
	 * Detects if Autoptimize is active.
	 *
	 *@since 1.0.12
	 *@access public
	 */
	public static function detect()
	{
		if (defined('AUTOPTIMIZE_PLUGIN_DIR')) {
			add_action('litespeed_cache_add_purge_tags',
				'LiteSpeed_Cache_ThirdParty_Autoptimize::add_purge_tags');
		}
	}

	/**
	 * Purges the cache when Autoptimize's cache is purged.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public static function add_purge_tags()
	{
		if ((defined('AUTOPTIMIZE_PURGE'))
			|| (has_action('shutdown', 'autoptimize_do_cachepurged_action', 11))) {
			LiteSpeed_Cache_Tags::add_purge_tag('*');
		}
	}
}

add_action('litespeed_cache_detect_thirdparty',
	'LiteSpeed_Cache_ThirdParty_Autoptimize::detect');

