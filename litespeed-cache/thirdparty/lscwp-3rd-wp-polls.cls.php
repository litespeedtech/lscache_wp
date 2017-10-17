<?php

/**
 * The Third Party integration with the WP-Polls plugin.
 *
 * @since		1.0.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
    die() ;
}
add_filter('wp_polls_display_pollvote', 'LiteSpeed_Cache_ThirdParty_Wp_Polls::set_control') ;
add_filter('wp_polls_display_pollresult', 'LiteSpeed_Cache_ThirdParty_Wp_Polls::set_control') ;

class LiteSpeed_Cache_ThirdParty_Wp_Polls
{
	public static function set_control()
	{
		LiteSpeed_Cache_API::set_nocache() ;
	}
}

