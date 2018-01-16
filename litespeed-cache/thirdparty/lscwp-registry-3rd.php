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
if ( ! defined('ABSPATH') ) {
    die() ;
}

$thirdparty_list = array(
	'aelia-currencyswitcher',
	'autoptimize',
	'bbpress',
	'betterwp-minify',
	'contact-form-7',
	'nextgengallery',
	'theme-my-login',
	'woocommerce',
	'wp-polls',
	'wplister',
	'wptouch',
	'yith-wishlist',
	'wpforo',
	'avada',
	'wp-postratings',
	'login-with-ajax',
) ;

foreach ($thirdparty_list as $val) {
	include_once(LSCWP_DIR . 'thirdparty/lscwp-3rd-' . $val . '.cls.php') ;
}
