<?php

/**
 * The Third Party integration with the WP-Polls plugin.
 *
 * @since		1.0.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
    die();
}

class LiteSpeed_Cache_ThirdParty_Wp_Polls
{

	public static function is_cacheable()
	{
		LiteSpeed_Cache_Tags::set_noncacheable();
	}


}

add_filter('wp_polls_display_pollvote',
		'LiteSpeed_Cache_ThirdParty_Wp_Polls::is_cacheable');
add_filter('wp_polls_display_pollresult',
		'LiteSpeed_Cache_ThirdParty_Wp_Polls::is_cacheable');

