<?php
/**
 * The Third Party integration with the bbPress plugin.
 *
 * @since		1.0.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;
use \LiteSpeed\Router ;

class BBPress
{
	/**
	 * Detect if bbPress is installed and if the page is a bbPress page.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public static function detect()
	{
		if ( function_exists( 'is_bbpress' ) ) {
			API::hook_purge_post( __CLASS__ . '::on_purge' ) ;
			if ( Router::esi_enabled() ) {// don't consider private cache yet (will do if any feedback)
				API::hook_control( __CLASS__ . '::set_control' ) ;
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
		if ( API::not_cacheable() ) {
			return ;
		}

		// set non ESI public
		if ( is_bbpress() && Router::is_logged_in() ) {
			API::set_nocache( 'bbpress cant cache loggedin' ) ;
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
		API::purge( API::TYPE_ARCHIVE_POSTTYPE . bbp_get_forum_post_type() ) ;
		$ancestors = get_post_ancestors( $post_id ) ;

		// If there are ancestors, need to purge them as well.
		if ( ! empty( $ancestors ) ) {
			foreach ( $ancestors as $ancestor ) {
				API::purge( API::TYPE_POST . $ancestor ) ;
			}
		}

		global $wp_widget_factory ;
		if ( bbp_is_reply( $post_id ) && ! is_null( $wp_widget_factory->widgets[ 'BBP_Replies_Widget' ] ) ) {
			API::purge( API::TYPE_WIDGET . $wp_widget_factory->widgets[ 'BBP_Replies_Widget' ]->id ) ;
		}
		if ( bbp_is_topic( $post_id ) && ! is_null( $wp_widget_factory->widgets[ 'BBP_Topics_Widget' ] ) ) {
			API::purge( API::TYPE_WIDGET . $wp_widget_factory->widgets[ 'BBP_Topics_Widget' ]->id ) ;
		}
	}
}

