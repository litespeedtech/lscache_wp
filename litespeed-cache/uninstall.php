<?php


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cur_dir = dirname(__FILE__) ;
require_once $cur_dir . '/includes/class-litespeed-cache.php';
require_once $cur_dir . '/includes/class-litespeed-cache-config.php';
require_once $cur_dir . '/admin/class-litespeed-cache-admin.php';
require_once $cur_dir . '/admin/class-litespeed-cache-admin-rules.php';

LiteSpeed_Cache_Admin_Rules::clear_rules();
delete_option(LiteSpeed_Cache_Config::OPTION_NAME);
if (is_multisite()) {
	delete_site_option(LiteSpeed_Cache_Config::OPTION_NAME);
}


