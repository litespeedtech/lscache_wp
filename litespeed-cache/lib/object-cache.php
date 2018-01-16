<?php
/**
 * LiteSpeed Object Cache
 *
 * @since  1.8
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

! defined( 'LSCWP_OBJECT_CACHE' ) && define( 'LSCWP_OBJECT_CACHE', true ) ;

// Initialize const `LSCWP_DIR` and locate LSCWP plugin foder
$lscwp_dir = ( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins' ) . '/litespeed-cache/' ;
// Use plugin as higher priority than MU plugin
if ( ! file_exists( $lscwp_dir . 'litespeed-cache.php' ) ) {
	// Check if is mu plugin or not
	$lscwp_dir = ( defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins' ) . '/litespeed-cache/' ;
	if ( ! file_exists( $lscwp_dir . 'litespeed-cache.php' ) ) {
		$lscwp_dir = '' ;
	}
}

$data_file = WP_CONTENT_DIR . '/.object-cache.ini' ;

// Can't find LSCWP location, terminate object cache process
if ( ! $lscwp_dir || ! file_exists( $data_file ) ) {
	if ( ! is_admin() ) { // Bypass object cache for frontend
		require_once ABSPATH . WPINC . '/cache.php' ;
	}
	else {
		$err = 'Can NOT find LSCWP path for object cache initialization in ' . __FILE__ ;
		error_log( $err ) ;
		echo $err ;
	}
}
else {
	// Init object cache & LSCWP
	require_once $lscwp_dir . 'inc/object.lib.php' ;
}
