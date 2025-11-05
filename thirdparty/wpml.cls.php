<?php
/**
 * The Third Party integration with WPML.
 *
 * Adds WPML language domains to LiteSpeed's list of internal domains.
 *
 * @since 2.9.4
 * @package LiteSpeed
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

/**
 * WPML integration for LiteSpeed Cache.
 */
class WPML {

	/**
	 * Registers filters when WPML is active.
	 *
	 * @since 2.9.4
	 * @return void
	 */
	public static function detect() {
		if ( ! defined( 'WPML_PLUGIN_BASENAME' ) ) {
			return;
		}

		add_filter( 'litespeed_internal_domains', __CLASS__ . '::append_domains' );
	}

	/**
	 * Take language domains as internal domains.
	 *
	 * @since 2.9.4
	 *
	 * @param array $domains Existing internal domains.
	 * @return array Modified list of internal domains including WPML language domains.
	 */
	public static function append_domains( $domains ) {
		$wpml_domains = apply_filters( 'wpml_setting', false, 'language_domains' );
		if ( $wpml_domains ) {
			$domains = array_merge( $domains, array_values( $wpml_domains ) );
		}

		return $domains;
	}
}
