<?php
/**
 * The Third Party integration with the Theme My Login plugin.
 *
 * @since       1.0.15
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class Theme_My_Login {

	/**
	 * Detects if Better Theme My Login is active.
	 *
	 * @since 1.0.15
	 * @access public
	 */
	public static function detect() {
		if (defined('THEME_MY_LOGIN_PATH')) {
			add_action('litespeed_control_finalize', __CLASS__ . '::set_control');
		}
	}

	/**
	 * This filter is used to let the cache know if a page is cacheable.
	 *
	 * @access public
	 * @since 1.0.15
	 */
	public static function set_control() {
		if (!apply_filters('litespeed_control_cacheable', false)) {
			return;
		}

		// check if this page is TML page or not
		if (class_exists('Theme_My_Login') && \Theme_My_Login::is_tml_page()) {
			do_action('litespeed_control_set_nocache', 'Theme My Login');
		}
	}
}
