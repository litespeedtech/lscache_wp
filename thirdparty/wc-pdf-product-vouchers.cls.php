<?php
/**
 * The Third Party integration with WooCommerce PDF Product Vouchers.
 *
 * @since 5.1.0
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides compatibility for WooCommerce PDF Product Vouchers.
 */
class WC_PDF_Product_Vouchers {

	/**
	 * Disable caching for generated vouchers.
	 *
	 * @since 5.1.0
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if (!class_exists('\WC_PDF_Product_Vouchers_Loader')) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_voucher = !empty($_GET['post_type']) && 'wc_voucher' === sanitize_text_field(wp_unslash($_GET['post_type']));
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$has_key = !empty($_GET['voucher_key']) || !empty($_GET['key']);

		if ($is_voucher && $has_key) {
			do_action('litespeed_control_set_nocache', '3rd WC PDF Product Voucher');
		}
	}
}
