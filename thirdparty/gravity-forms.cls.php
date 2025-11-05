<?php
/**
 * The Third Party integration with Gravity Forms.
 *
 * @since 4.1.0
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Gravity Forms compatibility hooks for LiteSpeed Cache.
 */
class Gravity_Forms {

	/**
	 * Check if GF is enabled and disable LSCWP on gf-download and gf-signature URI.
	 *
	 * Note: Query params are only read to detect special Gravity Forms endpoints.
	 * Nonce verification is not applicable here as no privileged action is performed.
	 *
	 * @since 4.1.0 #900899 #827184
	 * @return void
	 */
	public static function preload() {
		if (class_exists('GFCommon')) {
			$gf_download  = isset($_GET['gf-download']) ? sanitize_text_field(wp_unslash($_GET['gf-download'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$gf_signature = isset($_GET['gf-signature']) ? sanitize_text_field(wp_unslash($_GET['gf-signature'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ('' !== $gf_download || '' !== $gf_signature) {
				do_action('litespeed_disable_all', 'Stopped for Gravity Form');
			}
		}
	}
}
