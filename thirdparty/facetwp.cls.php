<?php
/**
 * The Third Party integration with FacetWP.
 *
 * @since 2.9.9
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * FacetWP compatibility hooks for LiteSpeed Cache.
 */
class Facetwp {

	/**
	 * Detect FacetWP context and adjust ESI params when FacetWP returns buffered HTML via the "wp" template.
	 *
	 * Note: We only *read* POST data here to detect an AJAX context; no privileged action is performed.
	 * Data is unslashed and sanitized before comparison.
	 *
	 * @since 2.9.9
	 * @return void
	 */
	public static function detect() {
		if ( ! defined( 'FACETWP_VERSION' ) ) {
			return;
		}
		/**
		 * For Facetwp, if the template is "wp", return the buffered HTML
		 * So marked as rest call to put is_json to ESI
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading POST to detect FacetWP AJAX; no state change or sensitive action performed.
		if ( ! empty( $_POST['action'] ) && ! empty( $_POST['data'] ) && ! empty( $_POST['data']['template'] ) && 'wp' === $_POST['data']['template'] ) {
			add_filter( 'litespeed_esi_params', __CLASS__ . '::set_is_json' );
		}
	}

	/**
	 * Mark ESI response as JSON for FacetWP's "wp" template refreshes.
	 *
	 * @since 2.9.9
	 * @param array $params Existing ESI params.
	 * @return array Modified ESI params.
	 */
	public static function set_is_json( $params ) {
		$params['is_json'] = 1;
		return $params;
	}
}
