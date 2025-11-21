<?php
/**
 * The Third Party integration with the Theme My Login plugin.
 *
 * @since 1.0.15
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides compatibility for the Theme My Login plugin.
 */
class Theme_My_Login {

	/**
	 * Detects if Better Theme My Login is active.
	 *
	 * @since 1.0.15
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if (defined('THEME_MY_LOGIN_PATH')) {
			add_action('litespeed_control_finalize', __CLASS__ . '::set_control');
		}
	}

	/**
	 * This filter is used to let the cache know if a page is cacheable.
	 *
	 * @since 1.0.15
	 * @access public
	 * @return void
	 */
	public static function set_control() {
		if (!apply_filters('litespeed_control_cacheable', false)) {
			return;
		}

		// Check if this page is TML page or not.
		if (class_exists('Theme_My_Login') && \Theme_My_Login::is_tml_page()) {
			do_action('litespeed_control_set_nocache', 'Theme My Login');
		}
	}
}
