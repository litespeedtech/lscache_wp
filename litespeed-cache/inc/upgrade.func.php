<?php
defined( 'WPINC' ) || exit ;
/**
 * Database upgrade funcs
 *
 * @since  3.0
 */

/**
 * For version under v2.0 to v2.0+
 *
 * @since  3.0
 */
function litespeed_update_2_0()
{
	$ver = get_option(  )

	/**
	 * Convert old data from postmeta to img_optm table
	 * @since  2.0
	 */
	if ( ! $ver || version_compare( $ver, '2.0', '<' ) ) {
		// Migrate data from `wp_postmeta` to `wp_litespeed_img_optm`
		$mids_to_del = array() ;
		$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_id" ;
		$meta_value_list = $wpdb->get_results( $wpdb->prepare( $q, array( LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_DATA ) ) ) ;
		if ( $meta_value_list ) {
			$max_k = count( $meta_value_list ) - 1 ;
			foreach ( $meta_value_list as $k => $v ) {
				$md52src_list = unserialize( $v->meta_value ) ;
				foreach ( $md52src_list as $md5 => $v2 ) {
					$f = array(
						'post_id'	=> $v->post_id,
						'optm_status'		=> $v2[ 1 ],
						'src'		=> $v2[ 0 ],
						'srcpath_md5'		=> md5( $v2[ 0 ] ),
						'src_md5'		=> $md5,
						'server'		=> $v2[ 2 ],
					) ;
					$wpdb->replace( $this->_tb_img_optm, $f ) ;
				}
				$mids_to_del[] = $v->meta_id ;

				// Delete from postmeta
				if ( count( $mids_to_del ) > 100 || $k == $max_k ) {
					$q = "DELETE FROM $wpdb->postmeta WHERE meta_id IN ( " . implode( ',', array_fill( 0, count( $mids_to_del ), '%s' ) ) . " ) " ;
					$wpdb->query( $wpdb->prepare( $q, $mids_to_del ) ) ;

					$mids_to_del = array() ;
				}
			}

			LiteSpeed_Cache_Log::debug( '[Data] img_optm inserted records: ' . $k ) ;
		}

		$q = "DELETE FROM $wpdb->postmeta WHERE meta_key = %s" ;
		$rows = $wpdb->query( $wpdb->prepare( $q, LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS ) ) ;
		LiteSpeed_Cache_Log::debug( '[Data] img_optm delete optm_status records: ' . $rows ) ;
	}

}



/**
 * Move all options in litespeed-cache-conf from v3.0- to separate records
 *
 * @since  3.0
 */
function litespeed_update_3_0()
{
	// conv items to litespeed.conf.*
	$data = array(
		'litespeed-cache-exclude-cache-roles' 		=> 'cache.exc_roles',
		'litespeed-cache-drop_qs' 					=> 'cache.drop_qs',
		'litespeed-forced_cache_uri' 				=> 'cache.force_uri',
		'litespeed-cache_uri_priv' 					=> 'cache.priv_uri',
		'litespeed-excludes_uri' 					=> 'cache.exc',
		'litespeed-cache-vary-group' 				=> 'cache.vary_group',
		'litespeed-adv-purge_all_hooks' 			=> 'purge.hook_all',
		'litespeed-object_global_groups' 			=> 'object.global_groups',
		'litespeed-object_non_persistent_groups' 	=> 'object.non_persistent_groups',
		'litespeed-media-lazy-img-excludes' 		=> 'media.lazy_exc',
		'litespeed-media-lazy-img-cls-excludes' 	=> 'media.lazy_cls_exc',
		'litespeed-media-webp_attribute' 			=> 'img_optm.webp_attr',
		'litespeed-optm-css' 						=> 'optm.ccss_con',
		'litespeed-optm_excludes' 					=> 'optm.exc',
		'litespeed-optm-ccss-separate_posttype' 	=> 'optm.ccss_sep_posttype',
		'litespeed-optm-css-separate_uri' 			=> 'optm.ccss_sep_uri',
		'litespeed-optm-js-defer-excludes' 			=> 'optm.js_defer_exc',
		'litespeed-cache-dns_prefetch' 				=> 'optm.dns_prefetch',
		'litespeed-cache-exclude-optimization-roles' => 'optm.exc_roles',
		'litespeed-log_ignore_filters' 				=> 'debug.log_no_filters',
		'litespeed-log_ignore_part_filters' 		=> 'debug.log_no_part_filters',
		'litespeed-cdn-ori_dir' 					=> 'cdn.ori_dir',
		'litespeed-cache-cdn_mapping' 				=> 'cdn.mapping',
		'litespeed-crawler-as-uids' 				=> 'crawler.roles',
		'litespeed-crawler-cookies' 				=> 'crawler.cookies',
	) ;
	foreach ( $data as $k => $v ) {
		$old_data = get_option( $k ) ;
		if ( $old_data ) {
			add_option( 'litespeed.conf.' . $v, $old_data ) ;
		}
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
	$previous_options = get_option( 'litespeed-cache-conf', array() ) ;

	$data = array(
		'radio_select'				=> 'cache',
		'auto_upgrade'				=> 'auto_upgrade',

		'esi_enabled'				=> 'esi',
		'esi_cached_admbar'			=> 'esi.cache_admbar',
		'esi_cached_commform'		=> 'esi.cache_commform',

		'heartbeat'					=> 'util.heartbeat',
		'cache_browser'				=> 'util.browser_cache',
		'cache_browser_ttl'			=> 'util.browser_cache_ttl',
		'instant_click'				=> 'util.instant_click',
		'check_advancedcache'		=> 'util.check_advcache',
		'use_http_for_https_vary' 	=> 'util.no_https_vary',

		'purge_upgrade'				=> 'purge.upgrade',
		'timed_urls' 				=> 'purge.timed_urls',
		'timed_urls_time' 			=> 'purge.timed_urls_time',

		'cache_priv' 				=> 'cache.priv',
		'cache_commenter'			=> 'cache.commenter',
		'cache_rest' 				=> 'cache.rest',
		'cache_page_login'			=> 'cache.page_login',
		'cache_favicon'				=> 'cache.favicon',
		'cache_resources'			=> 'cache.resources',
		'mobileview_enabled'		=> 'cache.mobile',
		'mobileview_rules'			=> 'cache.mobile_rules',
		'nocache_useragents' 		=> 'cache.exc_useragents',
		'nocache_cookies' 			=> 'cache.exc_cookies',
		'excludes_qs' 				=> 'cache.exc_qs',
		'excludes_cat' 				=> 'cache.exc_cat',
		'excludes_tag' 				=> 'cache.exc_tag',
		'public_ttl'				=> 'cache.ttl_pub',
		'private_ttl'				=> 'cache.ttl_priv',
		'front_page_ttl'			=> 'cache.ttl_frontpage',
		'feed_ttl'					=> 'cache.ttl_feed',
		'login_cookie'				=> 'cache.login_cookie',

		'debug_disable_all'			=> 'debug.disable_all',
		'admin_ips' 				=> 'debug.ips',
		'debug_level' 				=> 'debug.level',
		'log_file_size'				=> 'debug.filesize',
		'debug_cookie'				=> 'debug.cookie',
		'collaps_qs'				=> 'debug.collaps_qs',
		'log_filters' 				=> 'debug.log_filters',

		'crawler_cron_active' 		=> 'crawler',
		'crawler_include_posts' 	=> 'crawler.inc_posts',
		'crawler_include_pages' 	=> 'crawler.inc_pages',
		'crawler_include_cats' 		=> 'crawler.inc_cats',
		'crawler_include_tags' 		=> 'crawler.inc_tags',
		'crawler_excludes_cpt' 		=> 'crawler.exc_cpt',
		'crawler_order_links' 		=> 'crawler.order_links',
		'crawler_usleep' 			=> 'crawler.usleep',
		'crawler_run_duration' 		=> 'crawler.run_duration',
		'crawler_run_interval' 		=> 'crawler.run_interval',
		'crawler_crawl_interval' 	=> 'crawler.crawl_interval',
		'crawler_threads' 			=> 'crawler.threads',
		'crawler_load_limit' 		=> 'crawler.load_limit',
		'crawler_domain_ip' 		=> 'crawler.domain_ip',
		'crawler_custom_sitemap' 	=> 'crawler.custom_sitemap',

		'cache_object'				=> 'object',
		'cache_object_kind'			=> 'object.kind',
		'cache_object_host'			=> 'object.host',
		'cache_object_port'			=> 'object.port',
		'cache_object_life'			=> 'object.life',
		'cache_object_persistent'	=> 'object.persistent',
		'cache_object_admin'		=> 'object.admin',
		'cache_object_transients'	=> 'object.transients',
		'cache_object_db_id'		=> 'object.db_id',
		'cache_object_user'			=> 'object.user',
		'cache_object_pswd'			=> 'object.psw',

		'cdn_ori'					=> 'cdn.ori',
		'cdn_exclude' 				=> 'cdn.exc',
		'cdn_remote_jquery'			=> 'cdn.remote_jq',
		'cdn_cloudflare'			=> 'cdn.cloudflare',
		'cdn_cloudflare_email'		=> 'cdn.cloudflare_email',
		'cdn_cloudflare_key'		=> 'cdn.cloudflare_key',
		'cdn_cloudflare_name'		=> 'cdn.cloudflare_name',
		'cdn_cloudflare_zone'		=> 'cdn.cloudflare_zone',

		'media_img_lazy'				=> 'media.lazy',
		'media_img_lazy_placeholder'	=> 'media.lazy_placeholder',
		'media_placeholder_resp'		=> 'media.placeholder_resp',
		'media_placeholder_resp_color'	=> 'media.placeholder_resp_color',
		'media_placeholder_resp_async'	=> 'media.placeholder_resp_async',
		'media_iframe_lazy'				=> 'media.iframe_lazy',
		'media_img_lazyjs_inline'		=> 'media.lazyjs_inline',

		'media_optm_auto'			=> 'img_optm.auto',
		'media_optm_cron'			=> 'img_optm.cron',
		'media_optm_ori'			=> 'img_optm.ori',
		'media_rm_ori_bkup'			=> 'img_optm.rm_bkup',
		'media_optm_webp'			=> 'img_optm.webp',
		'media_optm_lossless'		=> 'img_optm.lossless',
		'media_optm_exif'			=> 'img_optm.exif',
		'media_webp_replace'		=> 'img_optm.webp_replace',
		'media_webp_replace_srcset'	=> 'img_optm.webp_replace_srcset',

		'css_minify'			=> 'optm.css_min',
		'css_inline_minify'		=> 'optm.css_inline_min',
		'css_combine'			=> 'optm.css_comb',
		'css_combined_priority'	=> 'optm.css_comb_priority',
		'css_http2'				=> 'optm.css_http2',
		'css_exclude' 			=> 'optm.css_exc',
		'js_minify'				=> 'optm.js_min',
		'js_inline_minify'		=> 'optm.js_inline_min',
		'js_combine'			=> 'optm.js_comb',
		'js_combined_priority'	=> 'optm.js_comb_priority',
		'js_http2'				=> 'optm.js_http2',
		'js_exclude' 			=> 'optm.js_exc',
		'optimize_ttl'			=> 'optm.ttl',
		'html_minify'			=> 'optm.html_min',
		'optm_qs_rm'			=> 'optm.qs_rm',
		'optm_ggfonts_rm'		=> 'optm.ggfonts_rm',
		'optm_css_async'		=> 'optm.css_async',
		'optm_ccss_gen'			=> 'optm.ccss_gen',
		'optm_ccss_async'		=> 'optm.ccss_async',
		'optm_css_async_inline'	=> 'optm.css_async_inline',
		'optm_js_defer'			=> 'optm.js_defer',
		'optm_emoji_rm'			=> 'optm.emoji_rm',
		'optm_exclude_jquery'	=> 'optm.exc_jq',
		'optm_ggfonts_async'	=> 'optm.ggfonts_async',
		'optm_max_size'			=> 'optm.max_size',
		'optm_rm_comment'		=> 'optm.rm_comment',
	) ;
	foreach ( $data as $k => $v ) {
		if ( ! isset( $previous_options[ $k ] ) ) {
			continue ;
		}
		add_option( 'litespeed.conf.' . $v, $previous_options[ $k ] ) ;
	}
	// Conv purge_by_post
	$data = array(
		'-'		=> 'purge.post_all',
		'F'		=> 'purge.post_f',
		'H'		=> 'purge.post_h',
		'PGS'	=> 'purge.post_p',
		'PGSRP'	=> 'purge.post_pwrp',
		'A'		=> 'purge.post_a',
		'Y'		=> 'purge.post_y',
		'M'		=> 'purge.post_m',
		'D'		=> 'purge.post_d',
		'T'		=> 'purge.post_t',
		'PT'	=> 'purge.post_pt',
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
	add_option( 'litespeed.conf.cache.ttl_status', $ttl_status ) ;

	/**
	 * Resave cdn cfg from lscfg to separate cfg when upgrade to v1.7
	 * @since 1.7
	 */
	if ( isset( $previous_options[ 'cdn_url' ] ) ) {xx
		$cdn_mapping = array(
			self::CDN_MAPPING_URL 		=> $previous_options[ 'cdn_url' ],
			self::CDN_MAPPING_INC_IMG 	=> $previous_options[ 'cdn_inc_img' ],
			self::CDN_MAPPING_INC_CSS 	=> $previous_options[ 'cdn_inc_css' ],
			self::CDN_MAPPING_INC_JS 	=> $previous_options[ 'cdn_inc_js' ],
			self::CDN_MAPPING_FILETYPE => $previous_options[ 'cdn_filetype' ],
		) ;
		add_option( LiteSpeed_Cache_Config::O_CDN_MAPPING, array( $cdn_mapping ) ) ;
		LiteSpeed_Cache_Log::debug( "[Conf] plugin_upgrade option adding CDN map" ) ;
	}

	/**
	 * Move Exclude settings to separate item
	 * @since  2.3
	 */
	if ( isset( $previous_options[ 'forced_cache_uri' ] ) ) {
		add_option( LiteSpeed_Cache_Config::O_CACHE_FORCE_URI, $previous_options[ 'forced_cache_uri' ] ) ;
	}
	if ( isset( $previous_options[ 'cache_uri_priv' ] ) ) {
		add_option( LiteSpeed_Cache_Config::O_CACHE_PRIV_URI, $previous_options[ 'cache_uri_priv' ] ) ;
	}
	if ( isset( $previous_options[ 'optm_excludes' ] ) ) {
		add_option( LiteSpeed_Cache_Config::O_OPTM_EXC, $previous_options[ 'optm_excludes' ] ) ;
	}
	if ( isset( $previous_options[ 'excludes_uri' ] ) ) {
		add_option( LiteSpeed_Cache_Config::O_CACHE_EXC, $previous_options[ 'excludes_uri' ] ) ;
	}

	// Backup stale conf
	delete_option( 'litespeed-cache-conf' ) ;
	add_option( 'litespeed-cache-conf.bk', $previous_options ) ;







	version -> _version


	// Update img_optm table data for upgrading
	// NOTE: no new change since v3.0 yet
	LiteSpeed_Cache_Data::get_instance() ;
}