<?php
/**
 * The Third Party integration with WooCommerce PDF Product Vouchers.
 *
 * @since		5.1.0
 */
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class WC_PDF_Product_Vouchers {
	/**
	 * Do not cache generated vouchers
	 *
	 * @since 5.1.0
	 */
	public static function detect() {
		if ( ! class_exists( '\WC_PDF_Product_Vouchers_Loader' ) ) {
			return;
		}

		$is_voucher =
			! empty( $_GET['post_type'] )
			&& 'wc_voucher' === $_GET['post_type'];
		$has_key =
			! empty( $_GET['voucher_key'] )
			|| ! empty( $_GET['key'] );

		if ( $is_voucher && $has_key ) {
			do_action( 'litespeed_control_set_nocache', '3rd WC PDF Product Voucher' );
		}
	}
}
