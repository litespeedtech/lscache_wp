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
use LiteSpeed\Conf;
use LiteSpeed\Data;
use LiteSpeed\Htaccess;

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
	$existing_urls = [];
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

/**
 * Drop the legacy img_optm table. [2026-07-02]
 *
 * The permanent img_optm table was replaced by the img_optming working queue + DB_SIZE postmeta + optimized-file existence checks. Fresh installs never created it; this migration removes the leftover table on upgraded sites.
 *
 * @since 7.9
 */
function litespeed_update_7_9() {
	global $wpdb;
	Debug2::debug( '[Data] Drop legacy img_optm table' );

	$tb = $wpdb->prefix . 'litespeed_img_optm';
	if ( litespeed_table_exists( $tb ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS `{$tb}`" );
	}
}

/**
 * Remove debug, purge and crawler logs still named by the v7.9.1 formula. [2026-10-14]
 *
 * The log files were renamed in v7.9.2, so the old copies were neither cleared nor downloadable; this runs regardless of the debug setting.
 * Also flags LiteSpeed blocks v7.9.1 left in an ancestor .htaccess, which v7.9.2 no longer manages.
 *
 * @since 7.9.2
 * @return void
 */
function litespeed_update_7_9_2() {
	Debug2::remove_legacy_logs();

	// v7.9.2 no longer writes outside the WordPress/frontend roots; point the administrator to any block left there.
	Htaccess::cls()->notice_orphan_rules();
}

/**
 * Drop the removed Inline CSS Async Lib option. [2026-11-11]
 *
 * @since 7.9.4
 * @return void
 */
function litespeed_update_7_9_4() {
	Conf::delete_option( 'optm-css_async_inline' );
	Conf::delete_site_option( 'optm-css_async_inline' );
}

/**
 * Store URL tags that do not fit the `url`(191) unique index prefix as hash keys. [2026-11-25]
 *
 * Raw rows of 191 bytes or more are rewritten in place to Data::url_key(), keeping their ids so every url_file child stays attached. A row whose key already exists is skipped by IGNORE and stays an unreachable leftover. Rows of exactly 500 characters may hold a URL truncated by non-strict SQL mode, so they are never converted. Orphaned url_file rows saved with url_id=0 by the failed inserts are removed (rows only, never the files they name). Re-runnable.
 *
 * @since 7.9.5
 * @return void
 */
function litespeed_update_7_9_5() {
	global $wpdb;

	Debug2::debug( '[Data] v7.9.5 upgrade: converting long URL tags to hash keys' );

	$tb_url = $wpdb->prefix . Data::TB_URL;
	if ( ! litespeed_table_exists( $tb_url ) ) {
		Debug2::debug( '[Data] Table `litespeed_url` not found, bypassed URL key migration' );
		return;
	}

	// Ids stay the decimal strings the database returns: the column is BIGINT and a PHP int cast would collapse ids above 2147483647 on 32-bit PHP. Only ctype_digit strings are interpolated into SQL.
	$last_id = '0';
	do {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, url FROM `{$tb_url}` WHERE id > {$last_id} AND LENGTH(url) >= %d AND CHAR_LENGTH(url) < %d ORDER BY id LIMIT %d", Data::URL_KEY_RAW_LIMIT, Data::URL_COLUMN_CHARS, Data::URL_KEY_UPGRADE_BATCH ), ARRAY_A );
		if ( ! $rows ) {
			if ( $wpdb->last_error ) {
				Debug2::debug( '[Data] URL key migration: select failed after id ' . $last_id . ': ' . $wpdb->last_error );
			}
			break;
		}

		$fetched = count( $rows );
		$cases   = [];
		$keys    = [];
		$ids     = [];
		foreach ( $rows as $row ) {
			$id = (string) $row['id'];
			if ( ! ctype_digit( $id ) ) {
				Debug2::debug( '[Data] URL key migration: skipped row with non-numeric id ' . $id );
				continue;
			}
			$cases[] = "WHEN {$id} THEN %s";
			$keys[]  = Data::url_key( $row['url'] );
			$ids[]   = $id;
		}

		if ( $ids ) {
			$sql = "UPDATE IGNORE `{$tb_url}` SET url = CASE id " . implode( ' ', $cases ) . ' END WHERE id IN (' . implode( ',', $ids ) . ')';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$res = $wpdb->query( $wpdb->prepare( $sql, $keys ) );
			if ( false === $res ) {
				Debug2::debug( '[Data] URL key migration: update failed for ids ' . reset( $ids ) . '-' . end( $ids ) . ': ' . $wpdb->last_error );
			}
		}

		// Rows come ORDER BY id, so the last fetched id moves the cursor past the whole batch and a row that cannot be updated never loops.
		$last_row = end( $rows );
		$last_id  = (string) $last_row['id'];
		if ( ! ctype_digit( $last_id ) ) {
			Debug2::debug( '[Data] URL key migration: stopped at non-numeric id ' . $last_id );
			break;
		}
	} while ( $fetched >= Data::URL_KEY_UPGRADE_BATCH );

	$tb_url_file = $wpdb->prefix . Data::TB_URL_FILE;
	if ( ! litespeed_table_exists( $tb_url_file ) ) {
		Debug2::debug( '[Data] Table `litespeed_url_file` not found, bypassed orphan cleanup' );
		return;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	if ( false === $wpdb->query( "DELETE FROM `{$tb_url_file}` WHERE url_id = 0" ) ) {
		Debug2::debug( '[Data] URL key migration: orphan cleanup failed: ' . $wpdb->last_error );
	}
}
