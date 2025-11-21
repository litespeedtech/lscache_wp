<?php
/**
 * The Third Party integration with the WP-Polls plugin.
 *
 * Ensures WP-Polls pages are marked as non-cacheable in LiteSpeed Cache.
 *
 * @since 1.0.7
 * @package LiteSpeed
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

/**
 * WP-Polls integration.
 */
class Wp_Polls {

	/**
	 * Register WP-Polls display filters to mark output as non-cacheable.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function detect() {
		add_filter( 'wp_polls_display_pollvote', __CLASS__ . '::set_control' );
		add_filter( 'wp_polls_display_pollresult', __CLASS__ . '::set_control' );
	}

	/**
	 * Mark WP-Polls output as non-cacheable.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function set_control() {
		do_action( 'litespeed_control_set_nocache', 'wp polls' );
	}
}
