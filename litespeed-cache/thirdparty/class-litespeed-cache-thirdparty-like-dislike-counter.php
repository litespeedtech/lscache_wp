<?php

/**
 * The Third Party integration with the Like Dislike Counter plugin.
 *
 * @since		1.0.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
    die();
}

class LiteSpeed_Cache_ThirdParty_Like_Dislike_Counter
{

    /**
     * Need to purge the post after someone 'likes' or 'dislikes' the post.
     */
    public static function add_purge_tags()
    {
		if (isset($_POST['post_id'])) {
			LiteSpeed_Cache_Tags::add_purge_tag(
					LiteSpeed_Cache_Tags::TYPE_POST . $_POST['post_id']);
		}
    }

}

/**
 * NOTICE: Since this plugin doesn't set the DOING_AJAX global during its
 * Ajax request, we do not know when the request is an ajax request.
 *
 * This solution works for now, but is not the optimal way of handling this.
 */
if ((function_exists('ldclite_get_version')) && $_POST && isset($_POST['up_type'])) {
	add_action('litespeed_cache_add_purge_tags',
			'LiteSpeed_Cache_ThirdParty_Like_Dislike_Counter::add_purge_tags');
}

