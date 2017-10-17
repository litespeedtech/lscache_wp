<?php

/**
 * The Third Party integration with the WP-PostRatings plugin.
 *
 * @since		1.1.1
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}
LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_WP_PostRatings') ;

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
		if ( defined('WP_POSTRATINGS_VERSION') ) {
			add_action('rate_post', 'LiteSpeed_Cache_ThirdParty_WP_PostRatings::flush', 10, 3) ;
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
		LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_POST . $post_id) ;
	}

}
