<?php

/**
 * The Third Party integration with the bbPress plugin.
 *
 * @since		1.0.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
    die();
}

class LiteSpeed_Cache_ThirdParty_BBPress
{


	/**
	 * Detect if bbPress is installed and if the page is a bbPress page.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public static function detect()
	{
		if ((function_exists('is_bbpress')) && (is_bbpress())){
			add_action('litespeed_cache_on_purge_post', 'LiteSpeed_Cache_ThirdParty_BBPress::on_purge');
		}
	}

	/**
	 * When a bbPress page is purged, need to purge the forums list and
	 * any/all ancestor pages.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param integer $post_id The post id of the page being purged.
	 */
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
