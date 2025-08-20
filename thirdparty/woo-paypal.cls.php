<?php
// phpcs:ignoreFile
/**
 * The Third Party integration with WooCommerce PayPal Checkout Gateway
 *
 * @ref https://wordpress.org/plugins/woocommerce-gateway-paypal-express-checkout/
 *
 * @since       3.0
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class Woo_Paypal {

	public static function detect() {
		if (!defined('WC_GATEWAY_PPEC_VERSION')) {
			return;
		}

		do_action('litespeed_nonce', '_wc_ppec_update_shipping_costs_nonce private');
		do_action('litespeed_nonce', '_wc_ppec_start_checkout_nonce private');
		do_action('litespeed_nonce', '_wc_ppec_generate_cart_nonce private');
	}
}
