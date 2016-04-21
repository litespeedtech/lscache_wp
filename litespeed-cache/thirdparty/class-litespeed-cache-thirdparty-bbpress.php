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
			add_filter('litespeed_cache_get_purge_tags', 'LiteSpeed_Cache_ThirdParty_BBPress::get_purge_tags', 10, 2);
		}
	}

	public static function get_purge_tags($purge_tags, $post_id)
	{
		// Check for null to prevent crash because of another plugin's mistake.
		if (is_null($purge_tags)) {
			return NULL;
		}

		// Need to purge base forums page, bbPress page was updated.
		$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_POSTTYPE . bbp_get_forum_post_type();
		$ancestors = get_post_ancestors($post_id);

		// If there are ancestors, need to purge them as well.
		if (!empty($ancestors)) {
			foreach ($ancestors as $ancestor) {
				$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_POST . $ancestor ;
			}
		}

		return $purge_tags;
	}


}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_BBPress::detect');
