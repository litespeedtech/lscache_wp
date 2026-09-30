<?php
/**
 * The Third Party integration with the Perfmatters plugin.
 *
 * @since 4.4.5
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides compatibility for the Perfmatters plugin.
 */
class Perfmatters {

	/**
	 * Preload Perfmatters integration.
	 *
	 * @since 4.4.5
	 * @return void
	 */
	public static function preload() {
		if (!defined('PERFMATTERS_VERSION')) {
			return;
		}

		if (is_admin()) {
			return;
		}

		if (has_action('shutdown', 'perfmatters_script_manager') !== false) {
			add_action('init', __CLASS__ . '::disable_script_manager_on_esi', 20);
		}
	}

	/**
	 * Disable Perfmatters Script Manager during LiteSpeed ESI requests.
	 *
	 * @since 4.4.5
	 * @return void
	 */
	public static function disable_script_manager_on_esi() {
		if (!defined('LSCACHE_IS_ESI')) {
			return;
		}

		$priority = has_action('shutdown', 'perfmatters_script_manager');

		if ($priority !== false) {
			remove_action('shutdown', 'perfmatters_script_manager', $priority);
			do_action('litespeed_debug', 'Disable Perfmatters script manager on ESI request');
		}
	}
}
