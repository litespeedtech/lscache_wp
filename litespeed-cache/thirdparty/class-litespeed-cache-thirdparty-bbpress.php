<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class LiteSpeed_Cache_ThirdParty_BBPress
{


	public static function detect()
	{
		if ((function_exists('is_bbpress')) && (is_bbpress())){
			add_action('litespeed_cache_on_purge_post', 'LiteSpeed_Cache_ThirdParty_BBPress::on_purge');
		}
	}

	public static function on_purge($post_id)
	{
		// Need to purge base forums page, bbPress page was updated.
		LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_ARCHIVE_POSTTYPE . bbp_get_forum_post_type());
		$ancestors = get_post_ancestors($post_id);

		// If there are ancestors, need to purge them as well.
		if (!empty($ancestors)) {
			foreach ($ancestors as $ancestor) {
				LiteSpeed_Cache_Tags::add_purge_tag(LiteSpeed_Cache_Tags::TYPE_POST . $ancestor);
			}
		}
	}
}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_BBPress::detect');
