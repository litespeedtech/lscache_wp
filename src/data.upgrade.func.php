<?php
// phpcs:ignoreFile

/**
 * Database upgrade funcs
 *
 * NOTE: whenever called this file, always call Data::get_upgrade_lock and Data::_set_upgrade_lock first.
 *
 * @since  3.0
 */
defined('WPINC') || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Cloud;

/**
 * Table existence check function
 *
 * @since 7.2
 */
function litespeed_table_exists( $table_name ) {
	global $wpdb;
	$save_state = $wpdb->suppress_errors;
	$wpdb->suppress_errors(true);
	$tb_exists = $wpdb->get_var('DESCRIBE `' . $table_name . '`');
	$wpdb->suppress_errors($save_state);

	return $tb_exists !== null;
}

/**
 * Migrate v7.0- url_files URL from no trailing slash to trailing slash
 *
 * @since 7.0.1
 */
function litespeed_update_7_0_1() {
	global $wpdb;
	Debug2::debug('[Data] v7.0.1 upgrade started');

	$tb_url = $wpdb->prefix . 'litespeed_url';
	if (!litespeed_table_exists($tb_url)) {
		Debug2::debug('[Data] Table `litespeed_url` not found, bypassed migration');
		return;
	}

	$q             = "SELECT * FROM `$tb_url` WHERE url LIKE 'https://%/'";
	$q             = $wpdb->prepare($q);
	$list          = $wpdb->get_results($q, ARRAY_A);
	$existing_urls = array();
	if ($list) {
		foreach ($list as $v) {
			$existing_urls[] = $v['url'];
		}
	}

	$q    = "SELECT * FROM `$tb_url` WHERE url LIKE 'https://%'";
	$q    = $wpdb->prepare($q);
	$list = $wpdb->get_results($q, ARRAY_A);
	if (!$list) {
		return;
	}
	foreach ($list as $v) {
		if (substr($v['url'], -1) == '/') {
			continue;
		}
		$new_url = $v['url'] . '/';
		if (in_array($new_url, $existing_urls)) {
			continue;
		}
		$q = "UPDATE `$tb_url` SET url = %s WHERE id = %d";
		$q = $wpdb->prepare($q, $new_url, $v['id']);
		$wpdb->query($q);
	}
}

/**
 * Migrate from domain key to pk/sk for QC
 *
 * @since 7.0
 */
function litespeed_update_7() {
	Debug2::debug('[Data] v7 upgrade started');

	$__cloud = Cloud::cls();

	$domain_key = $__cloud->conf('api_key');
	if (!$domain_key) {
		Debug2::debug('[Data] No domain key, bypassed migration');
		return;
	}

	$new_prepared = $__cloud->init_qc_prepare();
	if (!$new_prepared && $__cloud->activated()) {
		Debug2::debug('[Data] QC previously activated in v7, bypassed migration');
		return;
	}
	$data = array(
		'domain_key' => $domain_key,
	);
	$resp = $__cloud->post(Cloud::SVC_D_V3UPGRADE, $data);
	if (!empty($resp['qc_activated'])) {
		if ($resp['qc_activated'] != 'deleted') {
			$cloud_summary_updates = array( 'qc_activated' => $resp['qc_activated'] );
			if (!empty($resp['main_domain'])) {
				$cloud_summary_updates['main_domain'] = $resp['main_domain'];
			}
			Cloud::save_summary($cloud_summary_updates);
			Debug2::debug('[Data] Updated QC activated status to ' . $resp['qc_activated']);
		}
	}
}

/**
 * Append webp/mobile to url_file
 *
 * @since 5.3
 */
function litespeed_update_5_3() {
	global $wpdb;
	Debug2::debug('[Data] Upgrade url_file table');

	$tb = $wpdb->prefix . 'litespeed_url_file';
	if (litespeed_table_exists($tb)) {
		$q =
			'ALTER TABLE `' .
			$tb .
			'`
				ADD COLUMN `mobile` tinyint(4) NOT NULL COMMENT "mobile=1",
				ADD COLUMN `webp` tinyint(4) NOT NULL COMMENT "webp=1"
			';
		$wpdb->query($q);
	}
}
