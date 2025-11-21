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
			add_action('init', __CLASS__ . '::disable_litespeed_esi', 4);
		}
	}

	/**
	 * Disable LiteSpeed ESI when Perfmatters Script Manager is active.
	 *
	 * @since 4.4.5
	 * @return void
	 */
	public static function disable_litespeed_esi() {
		if (!defined('LITESPEED_ESI_OFF')) {
			define('LITESPEED_ESI_OFF', true);
		}
		do_action('litespeed_debug', 'Disable ESI due to Perfmatters script manager');
	}
}
