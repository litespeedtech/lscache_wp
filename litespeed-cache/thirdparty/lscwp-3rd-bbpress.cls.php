<?php

/**
 * The Third Party integration with the bbPress plugin.
 *
 * @since		1.0.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
    die() ;
}
LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_BBPress') ;

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
		if ( function_exists('is_bbpress') ) {
			LiteSpeed_Cache_API::hook_purge_post('LiteSpeed_Cache_ThirdParty_BBPress::on_purge') ;
			if ( LiteSpeed_Cache_Router::esi_enabled() ) {// don't consider private cache yet (will do if any feedback)
				LiteSpeed_Cache_API::hook_control('LiteSpeed_Cache_ThirdParty_BBPress::set_control') ;
			}
		}
	}

	/**
	 * This filter is used to let the cache know if a page is cacheable.
	 *
	 * @access public
	 * @since 1.2.0
	 */
	public static function set_control()
	{
		if ( LiteSpeed_Cache_API::not_cacheable() ) {
			return ;
		}

		// set non ESI public
		if ( is_bbpress() && LiteSpeed_Cache_Router::is_logged_in() ) {
			LiteSpeed_Cache_API::set_nocache( 'bbpress cant cache loggedin' ) ;
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
		if ( ! is_bbpress() && ! bbp_is_forum($post_id) && ! bbp_is_topic($post_id) && ! bbp_is_reply($post_id) ) {
			return ;
		}

		// Need to purge base forums page, bbPress page was updated.
		LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_ARCHIVE_POSTTYPE . bbp_get_forum_post_type()) ;
		$ancestors = get_post_ancestors($post_id) ;

		// If there are ancestors, need to purge them as well.
		if ( ! empty($ancestors) ) {
			foreach ($ancestors as $ancestor) {
				LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_POST . $ancestor) ;
			}
		}

		global $wp_widget_factory;
		if ( bbp_is_reply($post_id) && ! is_null($wp_widget_factory->widgets['BBP_Replies_Widget']) ) {
			LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_WIDGET . $wp_widget_factory->widgets['BBP_Replies_Widget']->id) ;
		}
		if (bbp_is_topic($post_id) && ! is_null($wp_widget_factory->widgets['BBP_Topics_Widget']) ) {
			LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_WIDGET . $wp_widget_factory->widgets['BBP_Topics_Widget']->id) ;
		}
	}
}

