<?php
/**
 * The registry for Third Party Plugins Integration files.
 *
 * This file is only used to include the integration files/classes.
 * This works as an entry point for the initial add_action for the
 * detect function.
 *
 * It is not required to add all integration files here, this just provides
 * a common place for plugin authors to append their file to.
 *
 */
defined( 'WPINC' ) || exit ;


use \LiteSpeed\API ;

$third_cls = array(
	'Aelia_CurrencySwitcher',
	'Autoptimize',
	'Avada',
	'BBPress',
	'Divi_Theme_Builder',
	'Facetwp',
	'Like_Dislike_Counter',
	'Theme_My_Login',
	'WooCommerce',
	'Wp_Polls',
	'WP_PostRatings',
	'Wpdiscuz',
	'WPLister',
	'WPML',
	'WpTouch',
	'Yith_Wishlist',
) ;

foreach ( $third_cls as $v ) {
	API::thirdparty( $v ) ;
}

// Preload needed for certain thirdparty
API::hook_init( 'LiteSpeed\Thirdparty\Divi_Theme_Builder::preload' ) ;
API::hook_init( 'LiteSpeed\Thirdparty\WooCommerce::preload' ) ;
API::hook_init( 'LiteSpeed\Thirdparty\NextGenGallery::preload' ) ;
API::hook_init( 'LiteSpeed\Thirdparty\AMP::preload' ) ;
