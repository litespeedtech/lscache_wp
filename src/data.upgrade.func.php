<?php
/**
 * Database upgrade funcs
 *
 * NOTE: whenever called this file, always call Data::get_upgrade_lock and Data::_set_upgrade_lock first.
 *
 * @since  3.0
 */
defined( 'WPINC' ) || exit;

use LiteSpeed\Debug2;
use LiteSpeed\Conf;
use LiteSpeed\Admin_Display;
use LiteSpeed\File;

/**
 * Drop cssjs table and rm cssjs folder
 * @since 4.3
 */
function litespeed_update_4_3() {
	if ( file_exists( LITESPEED_STATIC_DIR . '/ccsjs' ) ) {
		File::rrmdir( LITESPEED_STATIC_DIR . '/ccsjs' );
	}
}

/**
 * Drop object cache data file
 * @since 4.1
 */
function litespeed_update_4_1() {
	if ( file_exists( WP_CONTENT_DIR . '/.object-cache.ini' ) ) {
		unlink( WP_CONTENT_DIR . '/.object-cache.ini' );
	}
}

/**
 * Drop cssjs table and rm cssjs folder
 * @since 4.0
 */
function litespeed_update_4() {
	global $wpdb;
	$tb = $wpdb->prefix . 'litespeed_cssjs';
	$existed = $wpdb->get_var( "SHOW TABLES LIKE '$tb'" );
	if ( ! $existed ) {
		return;
	}

	$q = 'DROP TABLE IF EXISTS ' . $tb;
	$wpdb->query( $q );

	if ( file_exists( LITESPEED_STATIC_DIR . '/ccsjs' ) ) {
		File::rrmdir( LITESPEED_STATIC_DIR . '/ccsjs' );
	}
}

/**
 * Append jQuery to JS optm exclude list for max compatibility
 * Turn off JS Combine and Defer
 *
 * @since  3.5.1
 */
function litespeed_update_3_5() {
	$__conf = Conf::cls();
	// Excludes jQuery
	foreach ( array( 'optm-js_exc', 'optm-js_defer_exc' ) as $v ) {
		$curr_setting = $__conf->conf( $v );
		$curr_setting[] = 'jquery.js';
		$curr_setting[] = 'jquery.min.js';
		$__conf->update( $v, $curr_setting );
	}
	// Turn off JS Combine and defer
	$show_msg = false;
	foreach ( array( 'optm-js_comb', 'optm-js_defer', 'optm-js_inline_defer' ) as $v ) {
		$curr_setting = $__conf->conf( $v );
		if ( ! $curr_setting ) {
			continue;
		}
		$show_msg = true;
		$__conf->update( $v, false );
	}

	if ( $show_msg ) {
		$msg = sprintf( __( 'LiteSpeed Cache upgraded successfully. NOTE: Due to changes in this version, the settings %1$s and %2$s have been turned OFF. Please turn them back on manually and verify that your site layout is correct, and you have no JS errors.', 'litespeed-cache' ), '<code>' . __( 'JS Combine', 'litespeed-cache' ) . '</code>', '<code>' . __( 'JS Defer', 'litespeed-cache' ) . '</code>' );
		$msg .= sprintf( ' <a href="admin.php?page=litespeed-page_optm#settings_js">%s</a>.', __( 'Click here to settings', 'litespeed-cache' ) );
		Admin_Display::info( $msg, false, true );
	}
}

/**
 * For version under v2.0 to v2.0+
 *
 * @since  3.0
 */
function litespeed_update_2_0( $ver ) {
	global $wpdb ;

	// Table version only exists after all old data migrated
	// Last modified is v2.4.2
	if ( version_compare( $ver, '2.4.2', '<' ) ) {
		/**
		 * Convert old data from postmeta to img_optm table
		 * @since  2.0
		 */

		// Migrate data from `wp_postmeta` to `wp_litespeed_img_optm`
		$mids_to_del = array() ;
		$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_id" ;
		$meta_value_list = $wpdb->get_results( $wpdb->prepare( $q, 'litespeed-optimize-data' ) ) ;
		if ( $meta_value_list ) {
			$max_k = count( $meta_value_list ) - 1 ;
			foreach ( $meta_value_list as $k => $v ) {
				$md52src_list = maybe_unserialize( $v->meta_value ) ;
				foreach ( $md52src_list as $md5 => $v2 ) {
					$f = array(
						'post_id'	=> $v->post_id,
						'optm_status'		=> $v2[ 1 ],
						'src'		=> $v2[ 0 ],
						'srcpath_md5'		=> md5( $v2[ 0 ] ),
						'src_md5'		=> $md5,
						'server'		=> $v2[ 2 ],
					) ;
					$wpdb->replace( $wpdb->prefix . 'litespeed_img_optm', $f ) ;
				}
				$mids_to_del[] = $v->meta_id ;

				// Delete from postmeta
				if ( count( $mids_to_del ) > 100 || $k == $max_k ) {
					$q = "DELETE FROM $wpdb->postmeta WHERE meta_id IN ( " . implode( ',', array_fill( 0, count( $mids_to_del ), '%s' ) ) . " ) " ;
					$wpdb->query( $wpdb->prepare( $q, $mids_to_del ) ) ;

					$mids_to_del = array() ;
				}
			}

			Debug2::debug( '[Data] img_optm inserted records: ' . $k ) ;
		}

		$q = "DELETE FROM $wpdb->postmeta WHERE meta_key = %s" ;
		$rows = $wpdb->query( $wpdb->prepare( $q, 'litespeed-optimize-status' ) ) ;
		Debug2::debug( '[Data] img_optm delete optm_status records: ' . $rows ) ;

	}

	/**
	 * Add target_md5 field to table
	 * @since  2.4.2
	 */
	if ( version_compare( $ver, '2.4.2', '<' ) && version_compare( $ver, '2.0', '>=' ) ) {// NOTE: For new users, need to bypass this section
		$sql = sprintf(
			'ALTER TABLE `%1$s` ADD `server_info` text NOT NULL, DROP COLUMN `server`',
			$wpdb->prefix . 'litespeed_img_optm'
		) ;

		$res = $wpdb->query( $sql ) ;
		if ( $res !== true ) {
			Debug2::debug( '[Data] Warning: Alter table img_optm failed!', $sql ) ;
		}
		else {
			Debug2::debug( '[Data] Successfully upgraded table img_optm.' ) ;
		}

	}

	// Delete img optm tb version
	delete_option( $wpdb->prefix . 'litespeed_img_optm' ) ;


	// Delete possible HTML optm data from wp_options
	delete_option( 'litespeed-cache-optimized' ) ;

	// Delete HTML optm tb version
	delete_option( $wpdb->prefix . 'litespeed_optimizer' ) ;

}



/**
 * Move all options in litespeed-cache-conf from v3.0- to separate records
 *
 * @since  3.0
 */
function litespeed_update_3_0( $ver ) {
	global $wpdb;
	// Upgrade v2.0- to v2.0 first
	if ( version_compare( $ver, '2.0', '<' ) ) {
		litespeed_update_2_0( $ver ) ;
	}

	set_time_limit( 86400 );

	// conv items to litespeed.conf.*
	Debug2::debug( "[Data] Conv items to litespeed.conf.*" );
	$data = array(
		'litespeed-cache-exclude-cache-roles' 		=> 'cache-exc_roles',
		'litespeed-cache-drop_qs' 					=> 'cache-drop_qs',
		'litespeed-forced_cache_uri' 				=> 'cache-force_uri',
		'litespeed-cache_uri_priv' 					=> 'cache-priv_uri',
		'litespeed-excludes_uri' 					=> 'cache-exc',
		'litespeed-cache-vary-group' 				=> 'cache-vary_group',
		'litespeed-adv-purge_all_hooks' 			=> 'purge-hook_all',
		'litespeed-object_global_groups' 			=> 'object-global_groups',
		'litespeed-object_non_persistent_groups' 	=> 'object-non_persistent_groups',
		'litespeed-media-lazy-img-excludes' 		=> 'media-lazy_exc',
		'litespeed-media-lazy-img-cls-excludes' 	=> 'media-lazy_cls_exc',
		'litespeed-media-webp_attribute' 			=> 'img_optm-webp_attr',
		'litespeed-optm-css' 						=> 'optm-ccss_con',
		'litespeed-optm_excludes' 					=> 'optm-exc',
		'litespeed-optm-ccss-separate_posttype' 	=> 'optm-ccss_sep_posttype',
		'litespeed-optm-css-separate_uri' 			=> 'optm-ccss_sep_uri',
		'litespeed-optm-js-defer-excludes' 			=> 'optm-js_defer_exc',
		'litespeed-cache-dns_prefetch' 				=> 'optm-dns_prefetch',
		'litespeed-cache-exclude-optimization-roles' => 'optm-exc_roles',
		'litespeed-log_ignore_filters' 				=> 'debug-log_no_filters', // depreciated
		'litespeed-log_ignore_part_filters' 		=> 'debug-log_no_part_filters', // depreciated
		'litespeed-cdn-ori_dir' 					=> 'cdn-ori_dir',
		'litespeed-cache-cdn_mapping' 				=> 'cdn-mapping',
		'litespeed-crawler-as-uids' 				=> 'crawler-roles',
		'litespeed-crawler-cookies' 				=> 'crawler-cookies',
	) ;
	foreach ( $data as $k => $v ) {
		$old_data = get_option( $k ) ;
		if ( $old_data ) {
			Debug2::debug( "[Data] Convert $k" );
			// They must be an array
			if ( ! is_array( $old_data ) && $v != 'optm-ccss_con' ) {
				$old_data = explode( "\n", $old_data ) ;
			}

			if ( $v == 'crawler-cookies' ) {
				$tmp = array() ;
				$i = 0 ;
				foreach ( $old_data as $k2 => $v2 ) {
					$tmp[ $i ][ 'name' ] = $k2 ;
					$tmp[ $i ][ 'vals' ] = explode( "\n", $v2 ) ;
					$i ++ ;
				}
				$old_data = $tmp ;
			}

			add_option( 'litespeed.conf.' . $v, $old_data ) ;
		}
		Debug2::debug( "[Data] Delete $k" );
		delete_option( $k ) ;
	}

	// conv other items
	$data = array(
		'litespeed-setting-mode' 			=> 'litespeed.setting.mode',
		'litespeed-media-need-pull' 		=> 'litespeed.img_optm.need_pull',
		'litespeed-env-ref' 				=> 'litespeed.env.ref',
		'litespeed-cache-cloudflare_status' => 'litespeed.cdn.cloudflare.status',

	) ;
	foreach ( $data as $k => $v ) {
		$old_data = get_option( $k ) ;
		if ( $old_data ) {
			add_option( $v, $old_data ) ;
		}
		delete_option( $k ) ;
	}

	// Conv conf from litespeed-cache-conf child to litespeed.conf.*
	Debug2::debug( "[Data] Conv conf from litespeed-cache-conf child to litespeed.conf.*" );
	$previous_options = get_option( 'litespeed-cache-conf' ) ;

	$data = array(
		'radio_select'				=> 'cache',
		'hash'						=> 'hash',
		'auto_upgrade'				=> 'auto_upgrade',
		'news'						=> 'news',
		'crawler_domain_ip' 		=> 'server_ip',

		'esi_enabled'				=> 'esi',
		'esi_cached_admbar'			=> 'esi-cache_admbar',
		'esi_cached_commform'		=> 'esi-cache_commform',

		'heartbeat'					=> 'misc-heartbeat_front',

		'cache_browser'				=> 'cache-browser',
		'cache_browser_ttl'			=> 'cache-ttl_browser',
		'instant_click'				=> 'util-instant_click',
		'use_http_for_https_vary' 	=> 'util-no_https_vary',

		'purge_upgrade'				=> 'purge-upgrade',
		'timed_urls' 				=> 'purge-timed_urls',
		'timed_urls_time' 			=> 'purge-timed_urls_time',

		'cache_priv' 				=> 'cache-priv',
		'cache_commenter'			=> 'cache-commenter',
		'cache_rest' 				=> 'cache-rest',
		'cache_page_login'			=> 'cache-page_login',
		'cache_favicon'				=> 'cache-favicon',
		'cache_resources'			=> 'cache-resources',
		'mobileview_enabled'		=> 'cache-mobile',
		'mobileview_rules'			=> 'cache-mobile_rules',
		'nocache_useragents' 		=> 'cache-exc_useragents',
		'nocache_cookies' 			=> 'cache-exc_cookies',
		'excludes_qs' 				=> 'cache-exc_qs',
		'excludes_cat' 				=> 'cache-exc_cat',
		'excludes_tag' 				=> 'cache-exc_tag',
		'public_ttl'				=> 'cache-ttl_pub',
		'private_ttl'				=> 'cache-ttl_priv',
		'front_page_ttl'			=> 'cache-ttl_frontpage',
		'feed_ttl'					=> 'cache-ttl_feed',
		'login_cookie'				=> 'cache-login_cookie',

		'debug_disable_all'			=> 'debug-disable_all',
		'debug'						=> 'debug',
		'admin_ips' 				=> 'debug-ips',
		'debug_level' 				=> 'debug-level',
		'log_file_size'				=> 'debug-filesize',
		'debug_cookie'				=> 'debug-cookie',
		'collaps_qs'				=> 'debug-collaps_qs',
		// 'log_filters' 				=> 'debug-log_filters',

		'crawler_cron_active' 		=> 'crawler',
		// 'crawler_include_posts' 	=> 'crawler-inc_posts',
		// 'crawler_include_pages' 	=> 'crawler-inc_pages',
		// 'crawler_include_cats' 		=> 'crawler-inc_cats',
		// 'crawler_include_tags' 		=> 'crawler-inc_tags',
		// 'crawler_excludes_cpt' 		=> 'crawler-exc_cpt',
		// 'crawler_order_links' 		=> 'crawler-order_links',
		'crawler_usleep' 			=> 'crawler-usleep',
		'crawler_run_duration' 		=> 'crawler-run_duration',
		'crawler_run_interval' 		=> 'crawler-run_interval',
		'crawler_crawl_interval' 	=> 'crawler-crawl_interval',
		'crawler_threads' 			=> 'crawler-threads',
		'crawler_load_limit' 		=> 'crawler-load_limit',
		'crawler_custom_sitemap' 	=> 'crawler-sitemap',

		'cache_object'				=> 'object',
		'cache_object_kind'			=> 'object-kind',
		'cache_object_host'			=> 'object-host',
		'cache_object_port'			=> 'object-port',
		'cache_object_life'			=> 'object-life',
		'cache_object_persistent'	=> 'object-persistent',
		'cache_object_admin'		=> 'object-admin',
		'cache_object_transients'	=> 'object-transients',
		'cache_object_db_id'		=> 'object-db_id',
		'cache_object_user'			=> 'object-user',
		'cache_object_pswd'			=> 'object-psw',

		'cdn'						=> 'cdn',
		'cdn_ori'					=> 'cdn-ori',
		'cdn_exclude' 				=> 'cdn-exc',
		// 'cdn_remote_jquery'			=> 'cdn-remote_jq',
		'cdn_quic'					=> 'cdn-quic',
		'cdn_cloudflare'			=> 'cdn-cloudflare',
		'cdn_cloudflare_email'		=> 'cdn-cloudflare_email',
		'cdn_cloudflare_key'		=> 'cdn-cloudflare_key',
		'cdn_cloudflare_name'		=> 'cdn-cloudflare_name',
		'cdn_cloudflare_zone'		=> 'cdn-cloudflare_zone',

		'media_img_lazy'				=> 'media-lazy',
		'media_img_lazy_placeholder'	=> 'media-lazy_placeholder',
		'media_placeholder_resp'		=> 'media-placeholder_resp',
		'media_placeholder_resp_color'	=> 'media-placeholder_resp_color',
		'media_placeholder_resp_async'	=> 'media-placeholder_resp_async',
		'media_iframe_lazy'				=> 'media-iframe_lazy',
		'media_img_lazyjs_inline'		=> 'media-lazyjs_inline',

		'media_optm_auto'			=> 'img_optm-auto',
		'media_optm_cron'			=> 'img_optm-cron',
		'media_optm_ori'			=> 'img_optm-ori',
		'media_rm_ori_bkup'			=> 'img_optm-rm_bkup',
		'media_optm_webp'			=> 'img_optm-webp',
		'media_optm_lossless'		=> 'img_optm-lossless',
		'media_optm_exif'			=> 'img_optm-exif',
		'media_webp_replace'		=> 'img_optm-webp_replace',
		'media_webp_replace_srcset'	=> 'img_optm-webp_replace_srcset',

		'css_minify'			=> 'optm-css_min',
		// 'css_inline_minify'		=> 'optm-css_inline_min',
		'css_combine'			=> 'optm-css_comb',
		// 'css_combined_priority'	=> 'optm-css_comb_priority',
		'css_http2'				=> 'optm-css_http2',
		'css_exclude' 			=> 'optm-css_exc',
		'js_minify'				=> 'optm-js_min',
		// 'js_inline_minify'		=> 'optm-js_inline_min',
		'js_combine'			=> 'optm-js_comb',
		// 'js_combined_priority'	=> 'optm-js_comb_priority',
		'js_http2'				=> 'optm-js_http2',
		'js_exclude' 			=> 'optm-js_exc',
		// 'optimize_ttl'			=> 'optm-ttl',
		'html_minify'			=> 'optm-html_min',
		'optm_qs_rm'			=> 'optm-qs_rm',
		'optm_ggfonts_rm'		=> 'optm-ggfonts_rm',
		'optm_css_async'		=> 'optm-css_async',
		// 'optm_ccss_gen'			=> 'optm-ccss_gen',
		// 'optm_ccss_async'		=> 'optm-ccss_async',
		'optm_css_async_inline'	=> 'optm-css_async_inline',
		'optm_js_defer'			=> 'optm-js_defer',
		'optm_emoji_rm'			=> 'optm-emoji_rm',
		// 'optm_exclude_jquery'	=> 'optm-exc_jq',
		'optm_ggfonts_async'	=> 'optm-ggfonts_async',
		// 'optm_max_size'			=> 'optm-max_size',
		// 'optm_rm_comment'		=> 'optm-rm_comment',
	) ;
	foreach ( $data as $k => $v ) {
		if ( ! isset( $previous_options[ $k ] ) ) {
			continue ;
		}
		// The folllowing values must be array
		if ( ! is_array( $previous_options[ $k ] ) ) {
			if ( in_array( $v, array( 'cdn-ori', 'cache-exc_cat', 'cache-exc_tag' ) ) ) {
				$previous_options[ $k ] = explode( ',', $previous_options[ $k ] ) ;
				$previous_options[ $k ] = array_filter( $previous_options[ $k ] ) ;
			}
			elseif ( in_array( $v, array( 'cache-mobile_rules', 'cache-exc_useragents', 'cache-exc_cookies' ) ) ) {
				$previous_options[ $k ] = explode( '|', str_replace( '\\ ', ' ', $previous_options[ $k ] ) ) ;
				$previous_options[ $k ] = array_filter( $previous_options[ $k ] ) ;
			}
			elseif ( in_array( $v, array(
					'purge-timed_urls',
					'cache-exc_qs',
					'debug-ips',
					// 'crawler-exc_cpt',
					'cdn-exc',
					'optm-css_exc',
					'optm-js_exc',
				) ) ) {
				$previous_options[ $k ] = explode( "\n", $previous_options[ $k ] ) ;
				$previous_options[ $k ] = array_filter( $previous_options[ $k ] ) ;
			}
		}

		// Special handler for heartbeat
		if ( $v == 'misc-heartbeat_front' ) {
			if ( ! $previous_options[ $k ] ) {
				add_option( 'litespeed.conf.misc-heartbeat_front', true ) ;
				add_option( 'litespeed.conf.misc-heartbeat_back', true ) ;
				add_option( 'litespeed.conf.misc-heartbeat_editor', true ) ;
				add_option( 'litespeed.conf.misc-heartbeat_front_ttl', 0 ) ;
				add_option( 'litespeed.conf.misc-heartbeat_back_ttl', 0 ) ;
				add_option( 'litespeed.conf.misc-heartbeat_editor_ttl', 0 ) ;
			}
			continue ;
		}

		add_option( 'litespeed.conf.' . $v, $previous_options[ $k ] ) ;
	}
	// Conv purge_by_post
	$data = array(
		'-'		=> 'purge-post_all',
		'F'		=> 'purge-post_f',
		'H'		=> 'purge-post_h',
		'PGS'	=> 'purge-post_p',
		'PGSRP'	=> 'purge-post_pwrp',
		'A'		=> 'purge-post_a',
		'Y'		=> 'purge-post_y',
		'M'		=> 'purge-post_m',
		'D'		=> 'purge-post_d',
		'T'		=> 'purge-post_t',
		'PT'	=> 'purge-post_pt',
	) ;
	if ( isset( $previous_options[ 'purge_by_post' ] ) ) {
		$purge_by_post = explode( '.', $previous_options[ 'purge_by_post' ] ) ;
		foreach ( $data as $k => $v ) {
			add_option( 'litespeed.conf.' . $v, in_array( $k, $purge_by_post ) ) ;
		}
	}
	// Conv 404/403/500 TTL
	$ttl_status = array() ;
	if ( isset( $previous_options[ '403_ttl' ] ) ) {
		$ttl_status[] = '403 ' . $previous_options[ '403_ttl' ] ;
	}
	if ( isset( $previous_options[ '404_ttl' ] ) ) {
		$ttl_status[] = '404 ' . $previous_options[ '404_ttl' ] ;
	}
	if ( isset( $previous_options[ '500_ttl' ] ) ) {
		$ttl_status[] = '500 ' . $previous_options[ '500_ttl' ] ;
	}
	add_option( 'litespeed.conf.cache-ttl_status', $ttl_status ) ;

	/**
	 * Resave cdn cfg from lscfg to separate cfg when upgrade to v1.7
	 *
	 * NOTE: this can be left here as `add_option` bcos it is after the item `litespeed-cache-cdn_mapping` is converted
	 *
	 * @since 1.7
	 */
	if ( isset( $previous_options[ 'cdn_url' ] ) ) {
		$cdn_mapping = array(
			'url' 		=> $previous_options[ 'cdn_url' ],
			'inc_img' 	=> $previous_options[ 'cdn_inc_img' ],
			'inc_css' 	=> $previous_options[ 'cdn_inc_css' ],
			'inc_js' 	=> $previous_options[ 'cdn_inc_js' ],
			'filetype' 	=> $previous_options[ 'cdn_filetype' ],
		) ;
		add_option( 'litespeed.conf.cdn-mapping', array( $cdn_mapping ) ) ;
		Debug2::debug( "[Data] plugin_upgrade option adding CDN map" ) ;
	}

	/**
	 * Move Exclude settings to separate item
	 *
	 * NOTE: this can be left here as `add_option` bcos it is after the relevant items are converted
	 *
	 * @since  2.3
	 */
	if ( isset( $previous_options[ 'forced_cache_uri' ] ) ) {
		add_option( 'litespeed.conf.cache-force_uri', $previous_options[ 'forced_cache_uri' ] ) ;
	}
	if ( isset( $previous_options[ 'cache_uri_priv' ] ) ) {
		add_option( 'litespeed.conf.cache-priv_uri', $previous_options[ 'cache_uri_priv' ] ) ;
	}
	if ( isset( $previous_options[ 'optm_excludes' ] ) ) {
		add_option( 'litespeed.conf.optm-exc', $previous_options[ 'optm_excludes' ] ) ;
	}
	if ( isset( $previous_options[ 'excludes_uri' ] ) ) {
		add_option( 'litespeed.conf.cache-exc', $previous_options[ 'excludes_uri' ] ) ;
	}

	// Backup stale conf
	Debug2::debug( "[Data] Backup stale conf" );
	delete_option( 'litespeed-cache-conf' );
	add_option( 'litespeed-cache-conf.bk', $previous_options );

	// Upgrade site_options if is network
	if ( is_multisite() ) {
		$ver = get_site_option( 'litespeed.conf._version' ) ;
		if ( ! $ver ) {
			Debug2::debug( "[Data] Conv multisite" );
			$previous_site_options = get_site_option( 'litespeed-cache-conf' ) ;

			$data = array(
				'network_enabled'		=> 'cache',
				'use_primary_settings'	=> 'use_primary_settings',
				'auto_upgrade'			=> 'auto_upgrade',
				'purge_upgrade'			=> 'purge-upgrade',

				'cache_favicon'			=> 'cache-favicon',
				'cache_resources'		=> 'cache-resources',
				'mobileview_enabled'	=> 'cache-mobile',
				'mobileview_rules'		=> 'cache-mobile_rules',
				'login_cookie'				=> 'cache-login_cookie',
				'nocache_cookies' 			=> 'cache-exc_cookies',
				'nocache_useragents' 		=> 'cache-exc_useragents',

				'cache_object'				=> 'object',
				'cache_object_kind'			=> 'object-kind',
				'cache_object_host'			=> 'object-host',
				'cache_object_port'			=> 'object-port',
				'cache_object_life'			=> 'object-life',
				'cache_object_persistent'	=> 'object-persistent',
				'cache_object_admin'		=> 'object-admin',
				'cache_object_transients'	=> 'object-transients',
				'cache_object_db_id'		=> 'object-db_id',
				'cache_object_user'			=> 'object-user',
				'cache_object_pswd'			=> 'object-psw',

				'cache_browser'				=> 'cache-browser',
				'cache_browser_ttl'			=> 'cache-ttl_browser',

				'media_webp_replace'		=> 'img_optm-webp_replace',
			) ;
			foreach ( $data as $k => $v ) {
				if ( ! isset( $previous_site_options[ $k ] ) ) {
					continue ;
				}
				// The folllowing values must be array
				if ( ! is_array( $previous_site_options[ $k ] ) ) {
					if ( in_array( $v, array( 'cache-mobile_rules', 'cache-exc_useragents', 'cache-exc_cookies' ) ) ) {
						$previous_site_options[ $k ] = explode( '|', str_replace( '\\ ', ' ', $previous_site_options[ $k ] ) ) ;
						$previous_site_options[ $k ] = array_filter( $previous_site_options[ $k ] ) ;
					}
				}

				add_site_option( 'litespeed.conf.' . $v, $previous_site_options[ $k ] ) ;
			}

			// These are already converted to single record in single site
			$data = array(
				'object-global_groups',
				'object-non_persistent_groups',
			) ;
			foreach ( $data as $v ) {
				$old_data = get_option( $v ) ;
				if ( $old_data ) {
					add_site_option( 'litespeed.conf.' . $v, $old_data ) ;
				}
			}

			delete_site_option( 'litespeed-cache-conf' ) ;

			add_site_option( 'litespeed.conf._version', '3.0' ) ;
		}

	}

	// delete tables
	Debug2::debug( "[Data] Drop litespeed_optimizer" );
	$q = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'litespeed_optimizer' ;
	$wpdb->query( $q ) ;

	// Update image optm table
	Debug2::debug( "[Data] Upgrade img_optm table" );
	$tb_exists = $wpdb->get_var( 'SHOW TABLES LIKE "' . $wpdb->prefix . 'litespeed_img_optm"' );
	if ( $tb_exists ) {
		$status_mapping = array(
			'requested'	=> 3,
			'notified'	=> 6,
			'pulled'	=> 9,
			'failed'	=> -1,
			'miss'		=> -3,
			'err'		=> -9,
			'err_fetch'	=> -5,
			'err_optm'	=> -7,
			'xmeta'		=> -8,
		);
		foreach ( $status_mapping as $k => $v ) {
			$q = "UPDATE `" . $wpdb->prefix . "litespeed_img_optm` SET optm_status='$v' WHERE optm_status='$k'";
			$wpdb->query( $q ) ;
		}

		$q = 'ALTER TABLE `' . $wpdb->prefix . 'litespeed_img_optm`
				DROP INDEX `post_id_2`,
				DROP INDEX `root_id`,
				DROP INDEX `src_md5`,
				DROP INDEX `srcpath_md5`,
				DROP COLUMN `srcpath_md5`,
				DROP COLUMN `src_md5`,
				DROP COLUMN `root_id`,
				DROP COLUMN `target_saved`,
				DROP COLUMN `webp_saved`,
				DROP COLUMN `server_info`,
				MODIFY COLUMN `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				MODIFY COLUMN `optm_status` tinyint(4) NOT NULL DEFAULT 0,
				MODIFY COLUMN `src` text COLLATE utf8mb4_unicode_ci NOT NULL
			';
		$wpdb->query( $q ) ;
	}

	delete_option( 'litespeed-recommended' );

	Debug2::debug( "[Data] litespeed_update_3_0 done!" );

	add_option( 'litespeed.conf._version', '3.0' ) ;

}























