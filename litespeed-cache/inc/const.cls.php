<?php
/**
 * The core consts for config
 *
 * @since      	2.4
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Const
{
	// This is redundant since v3.0
	// New conf items are `litespeed.key`
	const OPTION_NAME = 'litespeed-cache-conf' ;

	const _CACHE = '_cache' ; // final cache status from setting

	/*** Single settings ***/
	const _VERSION = '_version' ; // Not set-able
	const O_AUTO_UPGRADE = 'auto_upgrade' ;

	const O_CACHE_BROWSER = 'cache_browser' ;
	const O_CACHE_BROWSER_TTL = 'cache_browser_ttl' ;

	const O_PURGE_ON_UPGRADE = 'purge_upgrade' ;
	const O_TIMED_URLS = 'purge.timed_urls' ;
	const O_TIMED_URLS_TIME = 'purge.timed_urls_time' ;

	const O_LOGIN_COOKIE = 'login_cookie' ;
	const O_CHECK_ADVCACHE = 'check_advancedcache' ;
	const O_USE_HTTP_FOR_HTTPS_VARY = 'use_http_for_https_vary' ;

	const O_PURGE_BY_POST = 'purge_by_post' ;
	const O_ESI_ENABLE = 'esi_enabled' ;
	const O_ESI_CACHE_ADMBAR = 'esi_cached_admbar' ;
	const O_ESI_CACHE_COMMFORM = 'esi_cached_commform' ;

	// const O_ADV_FAVICON = 'adv_favicon' ;
	const O_ADV_INSTANT_CLICK = 'instant_click' ;

	const O_VARY_GROUP = 'vary_group' ;
	const O_ADV_PURGE_ALL_HOOKS = 'adv.purge_all_hooks' ;


	## -------------------------------------------------- ##
	## --------------		Cache 		----------------- ##
	## -------------------------------------------------- ##
	const O_CACHE 					= 'cache' ;
	const O_CACHE_PRIV 				= 'cache.priv' ;
	const O_CACHE_COMMENTER 		= 'cache.commenter' ;
	const O_CACHE_REST 				= 'cache.rest' ;
	const O_CACHE_PAGE_LOGIN		= 'cache.page_login' ;
	const O_CACHE_FAVICON 			= 'cache.favicon' ;
	const O_CACHE_RES 				= 'cache.resources' ;
	const O_CACHE_MOBILE 			= 'cache.mobile' ;
	const O_CACHE_MOBILE_RULES		= 'cache.mobile_rules' ;
	const O_CACHE_EXC_USERAGENTS 	= 'cache.exc_useragents' ;
	const O_CACHE_EXC_COOKIES 		= 'cache.exc_cookies' ;
	const O_CACHE_EXC_QS 			= 'cache.exc_qs' ;
	const O_CACHE_EXC_CAT 			= 'cache.exc_cat' ;
	const O_CACHE_EXC_TAG 			= 'cache.exc_tag' ;
	const O_CACHE_FORCE_URI 		= 'cache.force_uri' ;
	const O_CACHE_PRIV_URI 			= 'cache.priv_uri' ;
	const O_CACHE_EXC 				= 'cache.exc' ;
	const O_CACHE_EXC_ROLES 		= 'cache.exc_roles' ;
	const O_CACHE_DROP_QS 			= 'cache.drop_qs' ;
	const O_CACHE_TTL_PUB 			= 'cache.ttl_pub' ;
	const O_CACHE_TTL_PRIV 			= 'cache.ttl_priv' ;
	const O_CACHE_TTL_FRONTPAGE 	= 'cache.ttl_frontpage' ;
	const O_CACHE_TTL_FEED 			= 'cache.ttl_feed' ;
	const O_CACHE_TTL_403 = '403_ttl' ;
	const O_CACHE_TTL_404 = '404_ttl' ;
	const O_CACHE_TTL_500 = '500_ttl' ;

	## -------------------------------------------------- ##
	## --------------		Debug 		----------------- ##
	## -------------------------------------------------- ##
	const O_DEBUG_DISABLE_ALL = 'debug.disable_all' ;
	const O_DEBUG = 'debug' ;
	const O_ADMIN_IPS = 'admin_ips' ;
	const O_DEBUG_LEVEL = 'debug_level' ;
	const O_LOG_FILE_SIZE = 'log_file_size' ;
	const O_HEARTBEAT = 'heartbeat' ;
	const O_DEBUG_COOKIE = 'debug_cookie' ;
	const O_COLLAPS_QS = 'collaps_qs' ;
	const O_LOG_FILTERS = 'log_filters' ;
	const O_LOG_IGNORE_FILTERS = 'debug.log_ignore_filters' ;
	const O_LOG_IGNORE_PART_FILTERS = 'debug.log_ignore_part_filters' ;


	## -------------------------------------------------- ##
	## --------------	HTML Optm 		----------------- ##
	## -------------------------------------------------- ##
	const O_OPTM_CSS_MIN 			= 'optm.css_min' ;
	const O_OPTM_CSS_INLINE_MIN 	= 'optm.css_inline_min' ;
	const O_OPTM_CSS_COMB 			= 'optm.css_comb' ;
	const O_OPTM_CSS_COMB_PRIO 		= 'optm.css_comb_priority' ;
	const O_OPTM_CSS_HTTP2 			= 'optm.css_http2' ;
	const O_OPTM_CSS_EXC 			= 'optm.css_exc' ;
	const O_OPTM_JS_MIN 			= 'optm.js_min' ;
	const O_OPTM_JS_INLINE_MIN 		= 'optm.js_inline_min' ;
	const O_OPTM_JS_COMB 			= 'optm.js_comb' ;
	const O_OPTM_JS_COMB_PRIO 		= 'optm.js_comb_priority' ;
	const O_OPTM_JS_HTTP2 			= 'optm.js_http2' ;
	const O_OPTM_JS_EXC 			= 'optm.js_exc' ;
	const O_OPTM_TTL 				= 'optm.ttl' ;
	const O_OPTM_HTML_MIN 			= 'optm.html_min' ;
	const O_OPTM_QS_RM 				= 'optm.qs_rm' ;
	const O_OPTM_GGFONTS_RM 		= 'optm.ggfonts_rm' ;
	const O_OPTM_CSS_ASYNC 			= 'optm.css_async' ;
	const O_OPTM_CCSS_GEN 			= 'optm.ccss_gen' ;
	const O_OPTM_CCSS_ASYNC 		= 'optm.ccss_async' ;
	const O_OPTM_CSS_ASYNC_INLINE 	= 'optm.css_async_inline' ;
	const O_OPTM_JS_DEFER 			= 'optm.js_defer' ;
	const O_OPTM_EMOJI_RM 			= 'optm.emoji_rm' ;
	const O_OPTM_EXC_JQ 			= 'optm.exc_jq' ;
	const O_OPTM_GGFONTS_ASYNC 		= 'optm.ggfonts_async' ;
	const O_OPTM_MAX_SIZE 			= 'optm.max_size' ;
	const O_OPTM_RM_COMMENT 		= 'optm.rm_comment' ;
	const O_OPTM_EXC_ROLES 			= 'optm.exc_roles' ;
	const O_OPTM_CSS 				= 'optm.ccss' ;
	const O_OPTM_JS_DEFER_EXC 		= 'optm.js_defer_exc' ;
	const O_OPTM_DNS_PREFETCH		= 'optm.dns_prefetch' ;
	const O_OPTM_EXC 				= 'optm.exc' ;
	const O_OPTM_CCSS_SEP_POSTTYPE 	= 'optm.ccss_sep_posttype' ;
	const O_OPTM_CCSS_SEP_URI 		= 'optm.ccss_sep_uri' ;

	## -------------------------------------------------- ##
	## --------------	Object Cache	----------------- ##
	## -------------------------------------------------- ##
	const O_OBJECT				 = 'object' ;
	const O_OBJECT_KIND			 = 'object.kind' ;
	const O_OBJECT_HOST			 = 'object.host' ;
	const O_OBJECT_PORT			 = 'object.port' ;
	const O_OBJECT_LIFE			 = 'object.life' ;
	const O_OBJECT_PERSISTENT	 = 'object.persistent' ;
	const O_OBJECT_ADMIN		 = 'object.admin' ;
	const O_OBJECT_TRANSIENTS	 = 'object.transients' ;
	const O_OBJECT_DB_ID		 = 'object.db_id' ;
	const O_OBJECT_USER			 = 'object.user' ;
	const O_OBJECT_PSWD			 = 'object.pswd' ;
	const O_OBJECT_GLOBAL_GROUPS = 'object.global_groups' ;
	const O_OBJECT_NON_PERSISTENT_GROUPS = 'object.non_persistent_groups' ;

	## -------------------------------------------------- ##
	## --------------		 Media 		----------------- ##
	## -------------------------------------------------- ##
	const O_MEDIA_LAZY 						= 'media.lazy' ;
	const O_MEDIA_LAZY_EXC 					= 'media.lazy_exc' ;
	const O_MEDIA_LAZY_CLS_EXC 				= 'media.lazy_cls_exc' ;
	const O_MEDIA_LAZY_PLACEHOLDER 			= 'media.lazy_placeholder' ;
	const O_MEDIA_PLACEHOLDER_RESP 			= 'media.placeholder_resp' ;
	const O_MEDIA_PLACEHOLDER_RESP_COLOR	= 'media.placeholder_resp_color' ;
	const O_MEDIA_PLACEHOLDER_RESP_ASYNC	= 'media.placeholder_resp_async' ;
	const O_MEDIA_IFRAME_LAZY 				= 'media.iframe_lazy' ;
	const O_MEDIA_LAZYJS_INLINE 			= 'media.lazyjs_inline' ;

	## -------------------------------------------------- ##
	## --------------	Image Optm 		----------------- ##
	## -------------------------------------------------- ##
	const O_IMG_OPTM_AUTO 				= 'img_optm.auto' ;
	const O_IMG_OPTM_CRON 				= 'img_optm.cron' ;
	const O_IMG_OPTM_ORI 				= 'img_optm.ori' ;
	const O_IMG_OPTM_RM_BKUP 			= 'img_optm.rm_bkup' ;
	const O_IMG_OPTM_WEBP 				= 'img_optm.webp' ;
	const O_IMG_OPTM_LOSSLESS 			= 'img_optm.lossless' ;
	const O_IMG_OPTM_EXIF 				= 'img_optm.exif' ;
	const O_IMG_OPTM_WEBP_REPLACE 		= 'img_optm.webp_replace' ;
	const O_IMG_OPTM_WEBP_ATTR 			= 'img_optm.webp_attr' ;
	const O_IMG_OPTM_WEBP_REPLACE_SRCSET = 'img_optm.webp_replace_srcset' ;

	## -------------------------------------------------- ##
	## --------------		Crawler		----------------- ##
	## -------------------------------------------------- ##
	const O_CRWL_POSTS 			= 'crawler.inc_posts' ;
	const O_CRWL_PAGES 			= 'crawler.inc_pages' ;
	const O_CRWL_CATS 			= 'crawler.inc_cats' ;
	const O_CRWL_TAGS 			= 'crawler.inc_tags' ;
	const O_CRWL_EXC_CPT 		= 'crawler.exc_cpt' ;
	const O_CRWL_ORDER_LINKS 	= 'crawler.order_links' ;
	const O_CRWL_USLEEP 		= 'crawler.usleep' ;
	const O_CRWL_RUN_DURATION 	= 'crawler.run_duration' ;
	const O_CRWL_RUN_INTERVAL 	= 'crawler.run_interval' ;
	const O_CRWL_CRAWL_INTERVAL = 'crawler.crawl_interval' ;
	const O_CRWL_THREADS 		= 'crawler.threads' ;
	const O_CRWL_LOAD_LIMIT 	= 'crawler.load_limit' ;
	const O_CRWL_DOMAIN_IP 		= 'crawler.domain_ip' ;
	const O_CRWL_CUSTOM_SITEMAP = 'crawler.custom_sitemap' ;
	const O_CRWL_CRON_ACTIVE 	= 'crawler.cron_active' ;
	const O_CRWL_ROLES 			= 'crawler.roles' ;
	const O_CRWL_COOKIES 		= 'crawler.cookies' ;

	## -------------------------------------------------- ##
	## --------------		 CDN 		----------------- ##
	## -------------------------------------------------- ##
	const O_CDN 				= 'cdn' ;
	const O_CDN_ORI 			= 'cdn.ori' ;
	const O_CDN_ORI_DIR 		= 'cdn.ori_dir' ;
	const O_CDN_EXC 			= 'cdn.exc' ;
	const O_CDN_REMOTE_JQ 		= 'cdn.remote_jq' ;
	const O_CDN_QUIC 			= 'cdn.quic' ;
	const O_CDN_QUIC_EMAIL		= 'cdn.quic_email' ;
	const O_CDN_QUIC_KEY 		= 'cdn.quic_key' ;
	const O_CDN_CLOUDFLARE 		= 'cdn.cloudflare' ;
	const O_CDN_CLOUDFLARE_EMAIL= 'cdn.cloudflare_email' ;
	const O_CDN_CLOUDFLARE_KEY 	= 'cdn.cloudflare_key' ;
	const O_CDN_CLOUDFLARE_NAME = 'cdn.cloudflare_name' ;
	const O_CDN_CLOUDFLARE_ZONE = 'cdn.cloudflare_zone' ;
	const O_CDN_MAPPING 		= 'cdn.mapping' ;


	const NETWORK_O_ENABLED = 'network_enabled' ;
	const NETWORK_O_USE_PRIMARY = 'use_primary_settings' ;

	/*** Other consts ***/
	const HASH = 'hash' ;

	const PURGE_ALL_PAGES 	= '-' ;
	const PURGE_FRONT_PAGE 	= 'F' ;
	const PURGE_HOME_PAGE 	= 'H' ;
	const PURGE_PAGES 		= 'PGS' ;
	const PURGE_PAGES_WITH_RECENT_POSTS = 'PGSRP' ;
	const PURGE_AUTHOR 		= 'A' ;
	const PURGE_YEAR 		= 'Y' ;
	const PURGE_MONTH 		= 'M' ;
	const PURGE_DATE 		= 'D' ;
	const PURGE_TERM 		= 'T' ; // include category|tag|tax
	const PURGE_POST_TYPE 	= 'PT' ;

	const O_GUIDE = 'litespeed-guide' ; // Array of each guidance tag as key, step as val

	// Server variables
	const ENV_CRAWLER_USLEEP = 'CRAWLER_USLEEP' ;
	const ENV_CRAWLER_LOAD_LIMIT = 'CRAWLER_LOAD_LIMIT' ;
	const ENV_CRAWLER_LOAD_LIMIT_ENFORCE = 'CRAWLER_LOAD_LIMIT_ENFORCE' ;

	// const O_FAVICON = 'litespeed-cache-favicon' ;

	const CDN_MAPPING_URL = 'url' ;
	const CDN_MAPPING_INC_IMG = 'inc_img' ;
	const CDN_MAPPING_INC_CSS = 'inc_css' ;
	const CDN_MAPPING_INC_JS = 'inc_js' ;
	const CDN_MAPPING_FILETYPE = 'filetype' ;

	const CRWL_DATE_DESC 		= 'date_desc' ;
	const CRWL_DATE_ASC 		= 'date_asc' ;
	const CRWL_ALPHA_DESC 		= 'alpha_desc' ;
	const CRWL_ALPHA_ASC 		= 'alpha_asc' ;

	const VAL_OFF = 0 ;
	const VAL_ON = 1 ;
	const VAL_ON2 = 2 ;

	const IMG_OPTM_BM_ORI = 1 ;
	const IMG_OPTM_BM_WEBP = 2 ;
	const IMG_OPTM_BM_LOSSLESS = 4 ;
	const IMG_OPTM_BM_EXIF = 8 ;

	/**
	 * Get the items in wp_options that need for backup
	 *
	 * @since 2.2.1
	 * @access public
	 */
	public function stored_items()
	{
		return array(
			self::OPTION_NAME,
			self::O_VARY_GROUP,
			self::O_OPTM_EXC_ROLES,
			self::O_CACHE_EXC_ROLES,
			self::O_OPTM_CSS,
			self::O_OPTM_JS_DEFER_EXC,
			self::O_MEDIA_LAZY_EXC,
			self::O_MEDIA_LAZY_CLS_EXC,
			self::O_CACHE_DROP_QS,
			self::O_CDN_MAPPING,
			self::O_CDN_ORI_DIR,
			self::O_OPTM_DNS_PREFETCH,
			self::O_LOG_IGNORE_FILTERS,
			self::O_LOG_IGNORE_PART_FILTERS,
			self::O_OBJECT_GLOBAL_GROUPS,
			self::O_OBJECT_NON_PERSISTENT_GROUPS,
			self::O_CRWL_ROLES,
			self::O_CRWL_COOKIES,
			self::O_ADV_PURGE_ALL_HOOKS,
			self::O_CACHE_FORCE_URI,
			self::O_CACHE_PRIV_URI,
			self::O_OPTM_EXC,
			self::O_CACHE_EXC,
			self::O_IMG_OPTM_WEBP_ATTR,
			self::O_OPTM_CCSS_SEP_POSTTYPE,
			self::O_OPTM_CCSS_SEP_URI,
		) ;
	}


	/**
	 * Gets the default network options
	 *
	 * @since 1.0.11
	 * @access protected
	 * @return array An array of the default options.
	 */
	protected function get_default_site_options()
	{
		$default_site_options = array(
			self::_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::NETWORK_O_ENABLED => false,
			self::NETWORK_O_USE_PRIMARY => false,
			self::O_AUTO_UPGRADE => false,
			self::O_PURGE_ON_UPGRADE => true,
			self::O_CACHE_FAVICON => true,
			self::O_CACHE_RES => true,
			self::O_CACHE_MOBILE => 0, // todo: why not false
			self::O_CACHE_MOBILE_RULES => 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi',
			self::O_OBJECT => false,
			self::O_OBJECT_KIND => false,
			self::O_OBJECT_HOST => 'localhost',
			self::O_OBJECT_PORT => '11211',
			self::O_OBJECT_LIFE => '360',
			self::O_OBJECT_PERSISTENT => true,
			self::O_OBJECT_ADMIN => true,
			self::O_OBJECT_TRANSIENTS => true,
			self::O_OBJECT_DB_ID => 0,
			self::O_OBJECT_USER => '',
			self::O_OBJECT_PSWD => '',
			self::O_CACHE_BROWSER => false,
			self::O_CACHE_BROWSER_TTL => 2592000,
			self::O_LOGIN_COOKIE => '',
			self::O_CHECK_ADVCACHE => true,
			self::O_CACHE_EXC_COOKIES => '',
			self::O_CACHE_EXC_USERAGENTS => '',
			self::O_IMG_OPTM_WEBP_REPLACE => false,
		) ;
		return $default_site_options ;
	}

	/**
	 * Gets the default single site options
	 *
	 * @since 1.0.0
	 * @access public
	 * @param bool $include_thirdparty Whether to include the thirdparty options.
	 * @return array An array of the default options.
	 */
	public function get_default_options($include_thirdparty = true)
	{
		$default_purge_options = array(
			self::PURGE_FRONT_PAGE,
			self::PURGE_HOME_PAGE,
			self::PURGE_PAGES,
			self::PURGE_PAGES_WITH_RECENT_POSTS,
			self::PURGE_AUTHOR,
			self::PURGE_MONTH,
			self::PURGE_TERM,
			self::PURGE_POST_TYPE,
		) ;
		sort($default_purge_options) ;

		$default_options = array(
			self::_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::O_CACHE => is_multisite() ? self::VAL_ON2 : self::VAL_ON, //For multi site, default is 2 (Use Network Admin Settings). For single site, default is 1 (Enabled).
			self::O_AUTO_UPGRADE => false,
			self::O_PURGE_ON_UPGRADE => true,
			self::O_CACHE_PRIV => true,
			self::O_CACHE_COMMENTER => true,
			self::O_CACHE_REST => true,
			self::O_CACHE_PAGE_LOGIN => true,
			self::O_TIMED_URLS => '',
			self::O_TIMED_URLS_TIME => '',
			self::O_CACHE_FAVICON => true,
			self::O_CACHE_RES => true,
			self::O_CACHE_MOBILE => false,
			self::O_CACHE_MOBILE_RULES => 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi',
			self::O_OBJECT => false,
			self::O_OBJECT_KIND => false,
			self::O_OBJECT_HOST => 'localhost',
			self::O_OBJECT_PORT => '11211',
			self::O_OBJECT_LIFE => '360',
			self::O_OBJECT_PERSISTENT => true,
			self::O_OBJECT_ADMIN => true,
			self::O_OBJECT_TRANSIENTS => true,
			self::O_OBJECT_DB_ID => 0,
			self::O_OBJECT_USER => '',
			self::O_OBJECT_PSWD => '',
			self::O_CACHE_BROWSER => false,
			self::O_CACHE_BROWSER_TTL => 2592000,

			self::O_LOGIN_COOKIE => '',
			self::O_CHECK_ADVCACHE => true,
			self::O_USE_HTTP_FOR_HTTPS_VARY => false,
			self::O_DEBUG_DISABLE_ALL => false,
			self::O_DEBUG => false,
			self::O_ADMIN_IPS => '127.0.0.1',
			self::O_DEBUG_LEVEL => false,
			self::O_LOG_FILE_SIZE => 3,
			self::O_HEARTBEAT => true,
			self::O_DEBUG_COOKIE => false,
			self::O_COLLAPS_QS => false,
			self::O_LOG_FILTERS => false,
			self::O_CACHE_TTL_PUB => 604800,
			self::O_CACHE_TTL_PRIV => 1800,
			self::O_CACHE_TTL_FRONTPAGE => 604800,
			self::O_CACHE_TTL_FEED => 0,
			self::O_CACHE_TTL_403 => 3600,
			self::O_CACHE_TTL_404 => 3600,
			self::O_CACHE_TTL_500 => 3600,
			self::O_PURGE_BY_POST => implode('.', $default_purge_options),
			self::O_CACHE_EXC_QS => '',
			self::O_CACHE_EXC_CAT => '',
			self::O_CACHE_EXC_TAG => '',

			// self::O_ADV_FAVICON 	=> false,
			self::O_ADV_INSTANT_CLICK 	=> false,

			self::O_OPTM_CSS_MIN 	=> false,
			self::O_OPTM_CSS_INLINE_MIN 	=> false,
			self::O_OPTM_CSS_COMB 	=> false,
			self::O_OPTM_CSS_COMB_PRIO 	=> false,
			self::O_OPTM_CSS_HTTP2 	=> false,
			self::O_OPTM_CSS_EXC => '',
			self::O_OPTM_JS_MIN 	=> false,
			self::O_OPTM_JS_INLINE_MIN 	=> false,
			self::O_OPTM_JS_COMB 	=> false,
			self::O_OPTM_JS_COMB_PRIO 	=> false,
			self::O_OPTM_JS_HTTP2 	=> false,
			self::O_OPTM_JS_EXC 	=> '',
			self::O_OPTM_TTL => 604800,
			self::O_OPTM_HTML_MIN 	=> false,
			self::O_OPTM_QS_RM 	=> false,
			self::O_OPTM_GGFONTS_RM => false,
			self::O_OPTM_CSS_ASYNC => false,
			self::O_OPTM_CCSS_GEN => true,
			self::O_OPTM_CCSS_ASYNC => true,
			self::O_OPTM_CSS_ASYNC_INLINE => true,
			self::O_OPTM_JS_DEFER => false,
			self::O_OPTM_EMOJI_RM => false,
			self::O_OPTM_EXC_JQ => true,
			self::O_OPTM_GGFONTS_ASYNC => false,
			self::O_OPTM_MAX_SIZE => 1.2,
			self::O_OPTM_RM_COMMENT => false,

			self::O_CDN 			=> false,
			self::O_CDN_ORI 		=> '',
			self::O_CDN_EXC 	=> '',
			self::O_CDN_REMOTE_JQ 	=> false,
			self::O_CDN_QUIC 		=> false,
			self::O_CDN_QUIC_EMAIL 	=> '',
			self::O_CDN_QUIC_KEY 		=> '',
			self::O_CDN_CLOUDFLARE 	=> false,
			self::O_CDN_CLOUDFLARE_EMAIL 	=> '',
			self::O_CDN_CLOUDFLARE_KEY 	=> '',
			self::O_CDN_CLOUDFLARE_NAME 	=> '',
			self::O_CDN_CLOUDFLARE_ZONE 	=> '',

			self::O_MEDIA_LAZY 				=> false,
			self::O_MEDIA_LAZY_PLACEHOLDER 	=> '',
			self::O_MEDIA_PLACEHOLDER_RESP		=> false,
			self::O_MEDIA_PLACEHOLDER_RESP_COLOR		=> '#cfd4db',
			self::O_MEDIA_PLACEHOLDER_RESP_ASYNC	=> true,
			self::O_MEDIA_IFRAME_LAZY 			=> false,
			self::O_MEDIA_LAZYJS_INLINE 		=> false,
			self::O_IMG_OPTM_AUTO 		=> false,
			self::O_IMG_OPTM_CRON 		=> true,
			self::O_IMG_OPTM_ORI 		=> true,
			self::O_IMG_OPTM_RM_BKUP 	=> false,
			self::O_IMG_OPTM_WEBP 		=> false,
			self::O_IMG_OPTM_LOSSLESS 	=> false,
			self::O_IMG_OPTM_EXIF 		=> false,
			self::O_IMG_OPTM_WEBP_REPLACE 	=> false,
			self::O_IMG_OPTM_WEBP_REPLACE_SRCSET 	=> false,

			self::HASH 	=> '',

			self::O_CACHE_EXC_COOKIES => '',
			self::O_CACHE_EXC_USERAGENTS => '',
			self::O_CRWL_POSTS => true,
			self::O_CRWL_PAGES => true,
			self::O_CRWL_CATS => true,
			self::O_CRWL_TAGS => true,
			self::O_CRWL_EXC_CPT => '',
			self::O_CRWL_ORDER_LINKS => self::CRWL_DATE_DESC,
			self::O_CRWL_USLEEP => 500,
			self::O_CRWL_RUN_DURATION => 400,
			self::O_CRWL_RUN_INTERVAL => 600,
			self::O_CRWL_CRAWL_INTERVAL => 302400,
			self::O_CRWL_THREADS => 3,
			self::O_CRWL_LOAD_LIMIT => 1,
			self::O_CRWL_DOMAIN_IP => '',
			self::O_CRWL_CUSTOM_SITEMAP => '',
			self::O_CRWL_CRON_ACTIVE => false,
		) ;

		if ( LSWCP_ESI_SUPPORT ) {
			$default_options[self::O_ESI_ENABLE] = false ;
			$default_options[self::O_ESI_CACHE_ADMBAR] = true ;
			$default_options[self::O_ESI_CACHE_COMMFORM] = true ;
		}

		// Default items
		if ( ! $default_options[ self::O_CDN_ORI_DIR ] ) {
			$default_options[ self::O_CDN_ORI_DIR ] = LSCWP_CONTENT_FOLDER . "\nwp-includes\n/min/" ;
		}

		// Load default.ini
		if ( file_exists( LSCWP_DIR . 'data/const.default.ini' ) ) {
			$default_ini_cfg = parse_ini_file( LSCWP_DIR . 'data/const.default.ini', true ) ;
			foreach ( $default_options as $k => $v ) {
				if ( ! array_key_exists( $k, $default_ini_cfg ) ) {
					continue ;
				}

				// Parse value in ini file
				$ini_v = $default_ini_cfg[ $k ] ;
				if ( is_bool( $v ) ) { // Keep value type constantly
					$ini_v = (bool) $default_ini_cfg[ $k ] ;
				}

				if ( $ini_v == $v ) {
					continue ;
				}

				$default_options[ $k ] = $ini_v ;
			}

			// Handle items in $this->default_item()

		}

		if ( ! $include_thirdparty ) {
			return $default_options ;
		}

		$tp_options = $this->get_thirdparty_options($default_options) ;
		if ( ! isset($tp_options) || ! is_array($tp_options) ) {
			return $default_options ;
		}
		return array_merge($default_options, $tp_options) ;
	}

	/**
	 * Get default item val
	 *
	 * @since 1.8
	 * @access public
	 */
	public function default_item( $item )
	{
		/**
		 * Allow terms default value
		 * @since  2.7.1
		 */
		if ( file_exists( LSCWP_DIR . 'data/const.default.ini' ) ) {
			$default_ini_cfg = parse_ini_file( LSCWP_DIR . 'data/const.default.ini', true ) ;

			if ( ! empty( $default_ini_cfg[ $item ] ) ) {

				/**
				 * Special handler for CDN_mapping
				 *
				 * format in .ini:
				 * 		[litespeed-cache-cdn_mapping]
				 *   	url[0] = 'https://example.com/'
				 *     	inc_js[0] = true
				 *
				 * format out:
				 * 		[0] = [ 'url' => 'https://example.com', 'inc_js' => true ]
				 */
				if ( $item == self::O_CDN_MAPPING ) {
					$mapping_fields = array(
						self::CDN_MAPPING_URL,
						self::CDN_MAPPING_INC_IMG,
						self::CDN_MAPPING_INC_CSS,
						self::CDN_MAPPING_INC_JS,
						self::CDN_MAPPING_FILETYPE
					) ;
					$cdn_mapping = array() ;
					foreach ( $default_ini_cfg[ $item ][ self::CDN_MAPPING_URL ] as $k => $v ) {// $k is numeric
						$this_row = array() ;
						foreach ( $mapping_fields as $v2 ) {
							$this_row[ $v2 ] = ! empty( $default_ini_cfg[ $item ][ $v2 ][ $k ] ) ? $default_ini_cfg[ $item ][ $v2 ][ $k ] : false ;
						}
						$cdn_mapping[ $k ] = $this_row ;
					}

					return $cdn_mapping ;
				}

				return $default_ini_cfg[ $item ] ;
			}
		}

		return '' ;// Here should not return false in case it is wrongly treated by conf::_set_conf() is_bool condition
	}


	/**
	 * Generate conf name for wp_options record
	 *
	 * @since 3.0
	 */
	public static function conf_name( $k, $type = 'conf' )
	{
		return 'litespeed.' . $type . '.' . $k ;
	}

	/**
	 * Generate server vars
	 *
	 * @since 2.4.1
	 */
	public function server_vars()
	{
		$consts = array(
			'WP_SITEURL',
			'WP_HOME',
			'WP_CONTENT_DIR',
			'SHORTINIT',
			'LSCWP_CONTENT_DIR',
			'LSCWP_CONTENT_FOLDER',
			'LSCWP_DIR',
			'LITESPEED_TIME_OFFSET',
			'LITESPEED_SERVER_TYPE',
			'LITESPEED_CLI',
			'LITESPEED_ALLOWED',
			'LITESPEED_ON',
			'LITESPEED_ON_IN_SETTING',
			'LSCACHE_ADV_CACHE',
		) ;
		$server_vars = array() ;
		foreach ( $consts as $v ) {
			$server_vars[ $v ] = defined( $v ) ? constant( $v ) : NULL ;
		}

		return $server_vars ;
	}

	/**
	 * Gets the third party options.
	 * Will also strip the options that are actually normal options.
	 *
	 * @access public
	 * @since 1.0.9
	 * @param array $options Optional. The default options to compare against.
	 * @return mixed boolean on failure, array of keys on success.
	 */
	public function get_thirdparty_options($options = null)
	{
		$tp_options = apply_filters('litespeed_cache_get_options', array()) ;
		if ( empty($tp_options) ) {
			return false ;
		}
		if ( ! isset($options) ) {
			$options = $this->get_default_options(false) ;
		}
		return array_diff_key($tp_options, $options) ;
	}

}