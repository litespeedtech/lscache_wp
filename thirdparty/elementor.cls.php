<?php
/**
 * The Third Party integration with the Elementor plugin.
 *
 * Detects Elementor editor/preview actions and safely disables LiteSpeed Cache features
 * that could interfere with live editing. Also hooks cache purge when Elementor regenerates
 * its CSS & data.
 *
 * @since      2.9.8.8
 * @package    LiteSpeed
 * @subpackage LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Handles Elementor compatibility.
 */
class Elementor {

	/**
	 * Preload hooks and disable caching features during Elementor edit/preview flows.
	 *
	 * This method only inspects query/server values to detect editor context.
	 * No privileged actions are performed here, so nonce verification is not required.
	 *
	 * @since 2.9.8.8
	 * @return void
	 */
	public static function preload() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		// If user explicitly opened the Elementor editor, disable all LSCWP features.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'elementor' === $action ) {
			do_action( 'litespeed_disable_all', 'elementor edit mode' );
		}

		// If the referrer indicates an Elementor editor context, inspect possible save actions.
		$http_referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $http_referer && false !== strpos( $http_referer, 'action=elementor' ) ) {
			// Elementor posts JSON in the 'actions' request field when saving from editor.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$actions_raw = isset( $_REQUEST['actions'] ) ? wp_unslash( $_REQUEST['actions'] ) : '';
			if ( '' !== $actions_raw ) {
				// Use a forgiving sanitizer for JSON strings, then decode.
				$json = json_decode( sanitize_textarea_field( $actions_raw ), true );
				// Debug2::debug( '3rd Elementor', $json );

				if (
					! empty( $json['save_builder']['action'] ) &&
					'save_builder' === $json['save_builder']['action'] &&
					! empty( $json['save_builder']['data']['status'] ) &&
					'publish' === $json['save_builder']['data']['status']
				) {
					// Publishing from editor — allow normal flow so crawler/purge can run.
					return;
				}
			}

			// In all other editor-referrer cases, disable LSCWP features during edit.
			do_action( 'litespeed_disable_all', 'elementor edit mode in HTTP_REFERER' );
		}

		// Clear LSC cache when Elementor regenerates CSS & Data.
		add_action( 'elementor/core/files/clear_cache', __CLASS__ . '::regenerate_litespeed_cache' );
	}

	/**
	 * Disable LiteSpeed ESI explicitly (kept for backward compatibility if re-enabled).
	 *
	 * @since 2.9.8.8
	 * @return void
	 */
	public static function disable_litespeed_esi() {
		if ( ! defined( 'LITESPEED_ESI_OFF' ) ) {
			define( 'LITESPEED_ESI_OFF', true );
		}
	}

	/**
	 * Purge LiteSpeed Cache when Elementor regenerates its CSS & Data.
	 *
	 * @since 2.9.8.8
	 * @return void
	 */
	public static function regenerate_litespeed_cache() {
		do_action( 'litespeed_purge_all', 'Elementor - Regenerate CSS & Data' );
	}
}
