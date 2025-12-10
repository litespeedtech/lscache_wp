<?php
/**
 * Database upgrade funcs
 *
 * NOTE: whenever called this file, always call Data::get_upgrade_lock and Data::_set_upgrade_lock first.
 *
 * @package LiteSpeed
 * @since 3.0
 */

defined( 'WPINC' ) || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Cloud;
use LiteSpeed\Conf;

/**
 * Check whether a DB table exists.
 *
 * @since 7.2
 *
 * @param string $table_name Fully-qualified table name.
 * @return bool
 */
function litespeed_table_exists( $table_name ) {
	global $wpdb;

	$save_state = $wpdb->suppress_errors;
	$wpdb->suppress_errors( true );
	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$tb_exists = $wpdb->get_var( $wpdb->prepare( 'DESCRIBE `%1s`', $table_name ) );
	$wpdb->suppress_errors( $save_state );

	return null !== $tb_exists;
}

/**
 * Migrate v7.0- url_files URL from no trailing slash to trailing slash.
 *
 * @since 7.0.1
 * @return void
 */
function litespeed_update_7_0_1() {
	global $wpdb;

	Debug2::debug( '[Data] v7.0.1 upgrade started' );

	$tb_url = $wpdb->prefix . 'litespeed_url';
	if ( ! litespeed_table_exists( $tb_url ) ) {
		Debug2::debug( '[Data] Table `litespeed_url` not found, bypassed migration' );
		return;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$list          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$tb_url}` WHERE url LIKE %s", 'https://%/' ), ARRAY_A );
	$existing_urls = array();
	if ($list) {
		foreach ($list as $v) {
			$existing_urls[] = $v['url'];
		}
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$tb_url}` WHERE url LIKE %s", 'https://%' ), ARRAY_A );
	if ( ! $list ) {
		return;
	}
	foreach ( $list as $v ) {
		if ( '/' === substr( $v['url'], -1 ) ) {
			continue;
		}
		$new_url = $v['url'] . '/';
		if ( in_array( $new_url, $existing_urls, true ) ) {
			continue;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $wpdb->prepare( "UPDATE `{$tb_url}` SET url = %s WHERE id = %d", $new_url, $v['id'] ) );
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
	if ( ! empty( $resp['qc_activated'] ) ) {
		if ( 'deleted' !== $resp['qc_activated'] ) {
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
 * Drop deprecated guest_ips and guest_uas from DB options.
 * Migrate url table to make all links trailing slash for UCSS/CCSS.
 *
 * These values are now read from files instead.
 *
 * @since 7.7
 */
function litespeed_update_7_7() {
	global $wpdb;

	Debug2::debug( '[Data] v7.7 upgrade: dropping guest_ips/guest_uas options' );

	Conf::delete_option( 'conf.guest_ips' );
	Conf::delete_option( 'conf.guest_uas' );
	Conf::delete_site_option( 'conf.guest_ips' );
	Conf::delete_site_option( 'conf.guest_uas' );

	// Normalize all URLs to have trailing slash to match UCSS/CCSS generation logic
	Debug2::debug( '[Data] v7.7 upgrade: normalizing URL trailing slashes' );

	// Skip if plain permalink mode (no trailing slash)
	$permalink_structure = get_option( 'permalink_structure' );
	if ( empty( $permalink_structure ) ) {
		Debug2::debug( '[Data] Plain permalink mode, bypassed URL trailing slash migration' );
		return;
	}

	$tb_url = $wpdb->prefix . 'litespeed_url';
	if ( ! litespeed_table_exists( $tb_url ) ) {
		Debug2::debug( '[Data] Table `litespeed_url` not found, bypassed URL migration' );
		return;
	}

	// Check if there are URLs without trailing slash (exclude URLs with query string)
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$tb_url}` WHERE url LIKE 'https://%' AND url NOT LIKE '%/' AND url NOT LIKE '%?%'" );
	if ( ! $count ) {
		Debug2::debug( '[Data] No URLs without trailing slash found, bypassed' );
		return;
	}

	// Append trailing slash to all URLs that don't have one and don't have duplicate with slash (exclude URLs with query string)
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "UPDATE `{$tb_url}` SET url = CONCAT(url, '/') WHERE url LIKE 'https://%' AND url NOT LIKE '%/' AND url NOT LIKE '%?%' AND CONCAT(url, '/') NOT IN (SELECT * FROM (SELECT url FROM `{$tb_url}` WHERE url LIKE '%/') AS tmp)" );
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
	if ( litespeed_table_exists( $tb ) ) {
		$q = "ALTER TABLE `{$tb}`
				ADD COLUMN `mobile` tinyint(4) NOT NULL COMMENT 'mobile=1',
				ADD COLUMN `webp` tinyint(4) NOT NULL COMMENT 'webp=1'
			";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $q );
	}
}
