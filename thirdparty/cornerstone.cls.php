<?php
/**
 * The Third Party integration with Themeco Cornerstone (Pro theme / standalone).
 *
 * Detects the Cornerstone editor and disables LiteSpeed Cache features that
 * interfere with live editing. Cornerstone runs on a frontend app path
 * (default /cornerstone), so ESI would otherwise rewrite wp_create_nonce( 'wp_rest' )
 * into an HTML comment inside wp_localize_script / csAppConfig JSON and break the builder.
 *
 * @since      7.9
 * @package    LiteSpeed
 * @subpackage LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

/**
 * Handles Cornerstone compatibility.
 */
class Cornerstone {

	/**
	 * Preload hooks and disable caching features during Cornerstone editor flows.
	 *
	 * This method only inspects request/server values to detect editor context.
	 * No privileged actions are performed here, so nonce verification is not required.
	 *
	 * @since 7.9
	 * @return void
	 */
	public static function preload() {
		if ( ! defined( 'CS_VERSION' ) ) {
			return;
		}

		$slug = self::app_slug();
		if ( '' === $slug ) {
			return;
		}

		// Cornerstone editor bootstrap (frontend app route, not wp-admin).
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::path_has_app_slug( $request_uri, $slug ) ) {
			do_action( 'litespeed_disable_all', 'cornerstone edit mode' );
			return;
		}

		// AJAX/REST from the editor: Referer is the Cornerstone app URL.
		$http_referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::path_has_app_slug( $http_referer, $slug ) ) {
			do_action( 'litespeed_disable_all', 'cornerstone edit mode in HTTP_REFERER' );
		}
	}

	/**
	 * Resolve Cornerstone's app path slug (default "cornerstone", or custom_app_slug).
	 *
	 * @since 7.9
	 * @return string
	 */
	private static function app_slug() {
		$settings = get_option( 'cornerstone_settings', array() );
		if ( is_array( $settings ) && ! empty( $settings['custom_app_slug'] ) ) {
			return sanitize_title_with_dashes( $settings['custom_app_slug'] );
		}

		/**
		 * Allow themes/plugins that filter the default slug before settings exist.
		 *
		 * @see apply_filters( 'cs_app_slug', ... ) in Cornerstone Settings.
		 */
		$slug = apply_filters( 'cs_app_slug', 'cornerstone' );
		return is_string( $slug ) ? sanitize_title_with_dashes( $slug ) : 'cornerstone';
	}

	/**
	 * Whether a URI or full URL contains the Cornerstone app path as a segment.
	 *
	 * @since 7.9
	 * @param string $uri  Request URI or referer URL.
	 * @param string $slug App slug (e.g. cornerstone).
	 * @return bool
	 */
	private static function path_has_app_slug( $uri, $slug ) {
		if ( '' === $uri || '' === $slug ) {
			return false;
		}

		$path = wp_parse_url( $uri, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			$path = $uri;
		}

		return (bool) preg_match( '#(?:^|/)' . preg_quote( $slug, '#' ) . '(?:/|$)#', $path );
	}
}
