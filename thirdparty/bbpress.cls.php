<?php
// phpcs:ignoreFile
/**
 * The Third Party integration with the bbPress plugin.
 *
 * @since       1.0.5
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

use LiteSpeed\Router;

class BBPress {

	/**
	 * Detect if bbPress is installed and if the page is a bbPress page.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public static function detect() {
		if (function_exists('is_bbpress')) {
			add_action('litespeed_api_purge_post', __CLASS__ . '::on_purge'); // todo
			if (apply_filters('litespeed_esi_status', false)) {
				// don't consider private cache yet (will do if any feedback)
				add_action('litespeed_control_finalize', __CLASS__ . '::set_control');
			}
		}
	}

	/**
	 * This filter is used to let the cache know if a page is cacheable.
	 *
	 * @access public
	 * @since 1.2.0
	 */
	public static function set_control() {
		if (!apply_filters('litespeed_control_cacheable', false)) {
			return;
		}

		// set non ESI public
		if (is_bbpress() && Router::is_logged_in()) {
			do_action('litespeed_control_set_nocache', 'bbpress nocache due to loggedin');
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
	public static function on_purge( $post_id ) {
		if (!is_bbpress()) {
			if (!function_exists('bbp_is_forum') || !function_exists('bbp_is_topic') || !function_exists('bbp_is_reply')) {
				return;
			}
			if (!bbp_is_forum($post_id) && !bbp_is_topic($post_id) && !bbp_is_reply($post_id)) {
				return;
			}
		}

		// Need to purge base forums page, bbPress page was updated.
		do_action('litespeed_purge_posttype', bbp_get_forum_post_type());
		$ancestors = get_post_ancestors($post_id);

		// If there are ancestors, need to purge them as well.
		if (!empty($ancestors)) {
			foreach ($ancestors as $ancestor) {
				do_action('litespeed_purge_post', $ancestor);
			}
		}

		global $wp_widget_factory;
		$replies_widget = $wp_widget_factory->get_widget_object('BBP_Replies_Widget');
		if (bbp_is_reply($post_id) && $replies_widget) {
			do_action('litespeed_purge_widget', $replies_widget->id);
		}

		$topic_widget = $wp_widget_factory->get_widget_object('BBP_Topics_Widget');
		if (bbp_is_topic($post_id) && $topic_widget) {
			do_action('litespeed_purge_widget', $topic_widget->id);
		}
	}
}
