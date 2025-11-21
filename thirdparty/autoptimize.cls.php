<?php
/**
 * The Third Party integration with the Autoptimize plugin.
 *
 * @since      1.0.12
 * @package    LiteSpeed
 * @subpackage LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

/**
 * Integration for Autoptimize cache events.
 */
class Autoptimize {

	/**
	 * Detects if Autoptimize is active.
	 *
	 * @since 1.0.12
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if ( defined( 'AUTOPTIMIZE_PLUGIN_DIR' ) ) {
			add_action( 'litespeed_purge_finalize', __CLASS__ . '::purge' );
		}
	}

	/**
	 * Purges LiteSpeed cache when Autoptimize's cache is purged.
	 *
	 * @since 1.0.12
	 * @access public
	 * @return void
	 */
	public static function purge() {
		if ( defined( 'AUTOPTIMIZE_PURGE' ) || has_action( 'shutdown', 'autoptimize_do_cachepurged_action', 11 ) ) {
			do_action( 'litespeed_purge_all', '3rd Autoptimize' );
		}
	}
}
