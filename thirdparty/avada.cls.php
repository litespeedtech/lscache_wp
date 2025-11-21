<?php
/**
 * The Third Party integration with the Avada plugin.
 *
 * @since      1.1.0
 * @package    LiteSpeed
 * @subpackage LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

/**
 * Integration for Avada cache flushing.
 */
class Avada {

	/**
	 * Detects if Avada is installed.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if ( ! defined( 'AVADA_VERSION' ) ) {
			return;
		}

		add_action( 'update_option_avada_dynamic_css_posts', __CLASS__ . '::flush' );
		add_action( 'update_option_fusion_options', __CLASS__ . '::flush' );
	}

	/**
	 * Purges the cache.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return void
	 */
	public static function flush() {
		do_action( 'litespeed_purge_all', '3rd avada' );
	}
}
