<?php


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cur_dir = dirname(__FILE__) ;
require_once $cur_dir . '/includes/class-litespeed-cache-config.php';
require_once $cur_dir . '/admin/class-litespeed-cache-admin.php';
require_once $cur_dir . '/admin/class-litespeed-cache-admin-rules.php';

$adv_cache_path = ABSPATH . 'wp-content/advanced-cache.php';
if (file_exists($adv_cache_path)) {
	unlink($adv_cache_path) ;
}

if (!LiteSpeed_Cache_Config::wp_cache_var_setter(false)) {
	error_log('In wp-config.php: WP_CACHE could not be set to false during deactivation!') ;
}

LiteSpeed_Cache_Admin_Rules::clear_rules();
delete_option(LiteSpeed_Cache_Config::OPTION_NAME);
if (is_multisite()) {
	delete_site_option(LiteSpeed_Cache_Config::OPTION_NAME);
}


