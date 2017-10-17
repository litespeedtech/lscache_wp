<?php

/**
 * The Third Party integration with the Like Dislike Counter plugin.
 *
 * @since		1.0.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}

/**
 * NOTICE: Since this plugin doesn't set the DOING_AJAX global during its
 * Ajax request, we do not know when the request is an ajax request.
 *
 * This solution works for now, but is not the optimal way of handling this.
 */
if ( function_exists('ldclite_get_version') && $_POST && isset($_POST['up_type']) ) {
	LiteSpeed_Cache_API::hook_purge('LiteSpeed_Cache_ThirdParty_Like_Dislike_Counter::purge') ;
}

class LiteSpeed_Cache_ThirdParty_Like_Dislike_Counter
{
	/**
	 * Need to purge the post after someone 'likes' or 'dislikes' the post.
	 */
	public static function purge()
	{
		if ( isset($_POST['post_id']) ) {
			LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_POST . $_POST['post_id']) ;
		}
	}

}

