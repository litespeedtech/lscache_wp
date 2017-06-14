<?php

/**
 * The Third Party integration with the WP-PostRatings plugin.
 *
 * @since		1.1.1
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
	die();
}

class LiteSpeed_Cache_ThirdParty_WP_PostRatings
{

	/**
	 * Detects if plugin is installed.
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function detect()
	{
		if (defined('WP_POSTRATINGS_VERSION')) {
			add_action('rate_post', 'LiteSpeed_Cache_ThirdParty_WP_PostRatings::flush', 10, 3);
		}
	}

	/**
	 * Purges the cache
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function flush($uid, $post_id, $post_ratings_score)
	{
		LiteSpeed_Cache_Tags::add_purge_tag(LiteSpeed_Cache_Tags::TYPE_POST . $post_id);
	}

}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_WP_PostRatings::detect');


