<?php
// phpcs:ignoreFile
/**
 * The registry for Third Party Plugins Integration files.
 *
 * This file is only used to include the integration files/classes.
 * This works as an entry point for the initial add_action for the
 * detect function.
 *
 * It is not required to add all integration files here, this just provides
 * a common place for plugin authors to append their file to.
 */
defined('WPINC') || exit();

use LiteSpeed\API;

$third_cls = array(
	'Aelia_CurrencySwitcher',
	'Autoptimize',
	'Avada',
	'BBPress',
	'Beaver_Builder',
	'Caldera_Forms',
	'Divi_Theme_Builder',
	'Facetwp',
	'LiteSpeed_Check',
	'Theme_My_Login',
	'User_Switching',
	'WCML',
	'WooCommerce',
	'WC_PDF_Product_Vouchers',
	'Woo_Paypal',
	'Wp_Polls',
	'WP_PostRatings',
	'Wpdiscuz',
	'WPLister',
	'WPML',
	'WpTouch',
	'Yith_Wishlist',
);

foreach ($third_cls as $cls) {
	add_action('litespeed_load_thirdparty', 'LiteSpeed\Thirdparty\\' . $cls . '::detect');
}

// Preload needed for certain thirdparty
add_action('litespeed_init', 'LiteSpeed\Thirdparty\Divi_Theme_Builder::preload');
add_action('litespeed_init', 'LiteSpeed\Thirdparty\WooCommerce::preload');
add_action('litespeed_init', 'LiteSpeed\Thirdparty\NextGenGallery::preload');
add_action('litespeed_init', 'LiteSpeed\Thirdparty\AMP::preload');
add_action('litespeed_init', 'LiteSpeed\Thirdparty\Elementor::preload');
add_action('litespeed_init', 'LiteSpeed\Thirdparty\Gravity_Forms::preload');
add_action('litespeed_init', 'LiteSpeed\Thirdparty\Perfmatters::preload');
