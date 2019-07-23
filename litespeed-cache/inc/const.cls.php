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

	## -------------------------------------------------- ##
	## --------------     	General	    ----------------- ##
	## -------------------------------------------------- ##
	const _VERSION 	= '_version' ; // Not set-able
	const HASH 		= 'hash' ; // Not set-able
	const O_AUTO_UPGRADE = 'auto_upgrade' ;

	## -------------------------------------------------- ##
	## --------------		Cache 		----------------- ##
	## -------------------------------------------------- ##
	const O_CACHE 					= 'cache' ;
	const O_CACHE_PRIV 				= 'cache-priv' ;
	const O_CACHE_COMMENTER 		= 'cache-commenter' ;
	const O_CACHE_REST 				= 'cache-rest' ;
	const O_CACHE_PAGE_LOGIN		= 'cache-page_login' ;
	const O_CACHE_FAVICON 			= 'cache-favicon' ;
	const O_CACHE_RES 				= 'cache-resources' ;
	const O_CACHE_MOBILE 			= 'cache-mobile' ;
	const O_CACHE_MOBILE_RULES		= 'cache-mobile_rules' ;
	const O_CACHE_EXC_USERAGENTS 	= 'cache-exc_useragents' ;
	const O_CACHE_EXC_COOKIES 		= 'cache-exc_cookies' ;
	const O_CACHE_EXC_QS 			= 'cache-exc_qs' ;
	const O_CACHE_EXC_CAT 			= 'cache-exc_cat' ;
	const O_CACHE_EXC_TAG 			= 'cache-exc_tag' ;
	const O_CACHE_FORCE_URI 		= 'cache-force_uri' ;
	const O_CACHE_FORCE_PUB_URI		= 'cache-force_pub_uri' ;
	const O_CACHE_PRIV_URI 			= 'cache-priv_uri' ;
	const O_CACHE_EXC 				= 'cache-exc' ;
	const O_CACHE_EXC_ROLES 		= 'cache-exc_roles' ;
	const O_CACHE_DROP_QS 			= 'cache-drop_qs' ;
	const O_CACHE_TTL_PUB 			= 'cache-ttl_pub' ;
	const O_CACHE_TTL_PRIV 			= 'cache-ttl_priv' ;
	const O_CACHE_TTL_FRONTPAGE 	= 'cache-ttl_frontpage' ;
	const O_CACHE_TTL_FEED 			= 'cache-ttl_feed' ;
	const O_CACHE_TTL_STATUS 		= 'cache-ttl_status' ;
	const O_CACHE_LOGIN_COOKIE 		= 'cache-login_cookie' ;
	const O_CACHE_VARY_GROUP 		= 'cache-vary_group' ;

	## -------------------------------------------------- ##
	## --------------		Purge 		----------------- ##
	## -------------------------------------------------- ##
	const O_PURGE_ON_UPGRADE 		= 'purge-upgrade' ;
	const O_PURGE_POST_ALL 			= 'purge-post_all' ;
	const O_PURGE_POST_FRONTPAGE 	= 'purge-post_f' ;
	const O_PURGE_POST_HOMEPAGE 	= 'purge-post_h' ;
	const O_PURGE_POST_PAGES 		= 'purge-post_p' ;
	const O_PURGE_POST_PAGES_WITH_RECENT_POSTS = 'purge-post_pwrp' ;
	const O_PURGE_POST_AUTHOR 		= 'purge-post_a' ;
	const O_PURGE_POST_YEAR 		= 'purge-post_y' ;
	const O_PURGE_POST_MONTH 		= 'purge-post_m' ;
	const O_PURGE_POST_DATE 		= 'purge-post_d' ;
	const O_PURGE_POST_TERM 		= 'purge-post_t' ; // include category|tag|tax
	const O_PURGE_POST_POSTTYPE 	= 'purge-post_pt' ;
	const O_PURGE_TIMED_URLS 		= 'purge-timed_urls' ;
	const O_PURGE_TIMED_URLS_TIME 	= 'purge-timed_urls_time' ;
	const O_PURGE_HOOK_ALL 			= 'purge-hook_all' ;

	## -------------------------------------------------- ##
	## --------------     	 ESI	    ----------------- ##
	## -------------------------------------------------- ##
	const O_ESI 				= 'esi' ;
	const O_ESI_CACHE_ADMBAR 	= 'esi-cache_admbar' ;
	const O_ESI_CACHE_COMMFORM 	= 'esi-cache_commform' ;

	## -------------------------------------------------- ##
	## --------------     Utilities	    ----------------- ##
	## -------------------------------------------------- ##
	const O_UTIL_HEARTBEAT 			= 'util-heartbeat' ;
	const O_UTIL_BROWSER_CACHE 		= 'util-browser_cache' ;
	const O_UTIL_BROWSER_CACHE_TTL 	= 'util-browser_cache_ttl' ;
	const O_UTIL_INSTANT_CLICK 		= 'util-instant_click' ;
	const O_UTIL_CHECK_ADVCACHE 	= 'util-check_advcache' ;
	const O_UTIL_NO_HTTPS_VARY 		= 'util-no_https_vary' ;

	## -------------------------------------------------- ##
	## --------------		Debug 		----------------- ##
	## -------------------------------------------------- ##
	const O_DEBUG_DISABLE_ALL 			= 'debug-disable_all' ;
	const O_DEBUG 						= 'debug' ;
	const O_DEBUG_IPS 					= 'debug-ips' ;
	const O_DEBUG_LEVEL 				= 'debug-level' ;
	const O_DEBUG_FILESIZE 				= 'debug-filesize' ;
	const O_DEBUG_COOKIE 				= 'debug-cookie' ;
	const O_DEBUG_COLLAPS_QS 			= 'debug-collaps_qs' ;
	const O_DEBUG_LOG_FILTERS 			= 'debug-log_filters' ;
	const O_DEBUG_LOG_NO_FILTERS 		= 'debug-log_no_filters' ;
	const O_DEBUG_LOG_NO_PART_FILTERS 	= 'debug-log_no_part_filters' ;

	## -------------------------------------------------- ##
	## --------------	  HTML Optm 	----------------- ##
	## -------------------------------------------------- ##
	const O_OPTM_CSS_MIN 			= 'optm-css_min' ;
	const O_OPTM_CSS_INLINE_MIN 	= 'optm-css_inline_min' ;
	const O_OPTM_CSS_COMB 			= 'optm-css_comb' ;
	const O_OPTM_CSS_COMB_PRIO 		= 'optm-css_comb_priority' ;
	const O_OPTM_CSS_HTTP2 			= 'optm-css_http2' ;
	const O_OPTM_CSS_EXC 			= 'optm-css_exc' ;
	const O_OPTM_JS_MIN 			= 'optm-js_min' ;
	const O_OPTM_JS_INLINE_MIN 		= 'optm-js_inline_min' ;
	const O_OPTM_JS_COMB 			= 'optm-js_comb' ;
	const O_OPTM_JS_COMB_PRIO 		= 'optm-js_comb_priority' ;
	const O_OPTM_JS_HTTP2 			= 'optm-js_http2' ;
	const O_OPTM_JS_EXC 			= 'optm-js_exc' ;
	const O_OPTM_TTL 				= 'optm-ttl' ;
	const O_OPTM_HTML_MIN 			= 'optm-html_min' ;
	const O_OPTM_QS_RM 				= 'optm-qs_rm' ;
	const O_OPTM_GGFONTS_RM 		= 'optm-ggfonts_rm' ;
	const O_OPTM_CSS_ASYNC 			= 'optm-css_async' ;
	const O_OPTM_CCSS_GEN 			= 'optm-ccss_gen' ;
	const O_OPTM_CCSS_ASYNC 		= 'optm-ccss_async' ;
	const O_OPTM_CSS_ASYNC_INLINE 	= 'optm-css_async_inline' ;
	const O_OPTM_CSS_FONT_DISPLAY 	= 'optm-css_font_display' ;
	const O_OPTM_JS_DEFER 			= 'optm-js_defer' ;
	const O_OPTM_JS_INLINE_DEFER	= 'optm-js_inline_defer' ;
	const O_OPTM_EMOJI_RM 			= 'optm-emoji_rm' ;
	const O_OPTM_EXC_JQ 			= 'optm-exc_jq' ;
	const O_OPTM_GGFONTS_ASYNC 		= 'optm-ggfonts_async' ;
	const O_OPTM_MAX_SIZE 			= 'optm-max_size' ;
	const O_OPTM_RM_COMMENT 		= 'optm-rm_comment' ;
	const O_OPTM_EXC_ROLES 			= 'optm-exc_roles' ;
	const O_OPTM_CCSS_CON			= 'optm-ccss_con' ;
	const O_OPTM_JS_DEFER_EXC 		= 'optm-js_defer_exc' ;
	const O_OPTM_DNS_PREFETCH		= 'optm-dns_prefetch' ;
	const O_OPTM_EXC 				= 'optm-exc' ;
	const O_OPTM_CCSS_SEP_POSTTYPE 	= 'optm-ccss_sep_posttype' ;
	const O_OPTM_CCSS_SEP_URI 		= 'optm-ccss_sep_uri' ;

	## -------------------------------------------------- ##
	## --------------	Object Cache	----------------- ##
	## -------------------------------------------------- ##
	const O_OBJECT				 = 'object' ;
	const O_OBJECT_KIND			 = 'object-kind' ;
	const O_OBJECT_HOST			 = 'object-host' ;
	const O_OBJECT_PORT			 = 'object-port' ;
	const O_OBJECT_LIFE			 = 'object-life' ;
	const O_OBJECT_PERSISTENT	 = 'object-persistent' ;
	const O_OBJECT_ADMIN		 = 'object-admin' ;
	const O_OBJECT_TRANSIENTS	 = 'object-transients' ;
	const O_OBJECT_DB_ID		 = 'object-db_id' ;
	const O_OBJECT_USER			 = 'object-user' ;
	const O_OBJECT_PSWD			 = 'object-pswd' ;
	const O_OBJECT_GLOBAL_GROUPS = 'object-global_groups' ;
	const O_OBJECT_NON_PERSISTENT_GROUPS = 'object-non_persistent_groups' ;

	## -------------------------------------------------- ##
	## --------------		 Media 		----------------- ##
	## -------------------------------------------------- ##
	const O_MEDIA_LAZY 							= 'media-lazy' ;
	const O_MEDIA_LAZY_PLACEHOLDER 				= 'media-lazy_placeholder' ;
	const O_MEDIA_PLACEHOLDER_RESP 				= 'media-placeholder_resp' ;
	const O_MEDIA_PLACEHOLDER_RESP_COLOR		= 'media-placeholder_resp_color' ;
	const O_MEDIA_PLACEHOLDER_RESP_GENERATOR	= 'media-placeholder_resp_generator' ;
	const O_MEDIA_PLACEHOLDER_RESP_SVG			= 'media-placeholder_resp_svg' ;
	const O_MEDIA_PLACEHOLDER_RESP_ASYNC		= 'media-placeholder_resp_async' ;
	const O_MEDIA_IFRAME_LAZY 					= 'media-iframe_lazy' ;
	const O_MEDIA_LAZYJS_INLINE 				= 'media-lazyjs_inline' ;
	const O_MEDIA_LAZY_EXC 						= 'media-lazy_exc' ;
	const O_MEDIA_LAZY_CLS_EXC 					= 'media-lazy_cls_exc' ;
	const O_MEDIA_LAZY_PARENT_CLS_EXC 			= 'media-lazy_parent_cls_exc' ;
	const O_MEDIA_IFRAME_LAZY_CLS_EXC 			= 'media-iframe_lazy_cls_exc' ;
	const O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC 	= 'media-iframe_lazy_parent_cls_exc' ;
	const O_MEDIA_LAZY_URI_EXC					= 'media-lazy_uri_exc' ;

	## -------------------------------------------------- ##
	## --------------	  Image Optm 	----------------- ##
	## -------------------------------------------------- ##
	const O_IMG_OPTM_AUTO 				= 'img_optm-auto' ;
	const O_IMG_OPTM_CRON 				= 'img_optm-cron' ;
	const O_IMG_OPTM_ORI 				= 'img_optm-ori' ;
	const O_IMG_OPTM_RM_BKUP 			= 'img_optm-rm_bkup' ;
	const O_IMG_OPTM_WEBP 				= 'img_optm-webp' ;
	const O_IMG_OPTM_LOSSLESS 			= 'img_optm-lossless' ;
	const O_IMG_OPTM_EXIF 				= 'img_optm-exif' ;
	const O_IMG_OPTM_WEBP_REPLACE 		= 'img_optm-webp_replace' ;
	const O_IMG_OPTM_WEBP_ATTR 			= 'img_optm-webp_attr' ;
	const O_IMG_OPTM_WEBP_REPLACE_SRCSET = 'img_optm-webp_replace_srcset' ;
	const O_IMG_OPTM_JPG_QUALITY 		= 'img_optm-jpg_quality' ;

	## -------------------------------------------------- ##
	## --------------		Crawler		----------------- ##
	## -------------------------------------------------- ##
	const O_CRWL 				= 'crawler' ;
	const O_CRWL_POSTS 			= 'crawler-inc_posts' ;
	const O_CRWL_PAGES 			= 'crawler-inc_pages' ;
	const O_CRWL_CATS 			= 'crawler-inc_cats' ;
	const O_CRWL_TAGS 			= 'crawler-inc_tags' ;
	const O_CRWL_EXC_CPT 		= 'crawler-exc_cpt' ;
	const O_CRWL_ORDER_LINKS 	= 'crawler-order_links' ;
	const O_CRWL_USLEEP 		= 'crawler-usleep' ;
	const O_CRWL_RUN_DURATION 	= 'crawler-run_duration' ;
	const O_CRWL_RUN_INTERVAL 	= 'crawler-run_interval' ;
	const O_CRWL_CRAWL_INTERVAL = 'crawler-crawl_interval' ;
	const O_CRWL_THREADS 		= 'crawler-threads' ;
	const O_CRWL_TIMEOUT 		= 'crawler-timeout' ;
	const O_CRWL_LOAD_LIMIT 	= 'crawler-load_limit' ;
	const O_CRWL_DOMAIN_IP 		= 'crawler-domain_ip' ;
	const O_CRWL_CUSTOM_SITEMAP = 'crawler-custom_sitemap' ;
	const O_CRWL_ROLES 			= 'crawler-roles' ;
	const O_CRWL_COOKIES 		= 'crawler-cookies' ;

	## -------------------------------------------------- ##
	## --------------		 CDN 		----------------- ##
	## -------------------------------------------------- ##
	const O_CDN 				= 'cdn' ;
	const O_CDN_ORI 			= 'cdn-ori' ;
	const O_CDN_ORI_DIR 		= 'cdn-ori_dir' ;
	const O_CDN_EXC 			= 'cdn-exc' ;
	const O_CDN_REMOTE_JQ 		= 'cdn-remote_jq' ;
	const O_CDN_QUIC 			= 'cdn-quic' ;
	const O_CDN_QUIC_EMAIL		= 'cdn-quic_email' ;
	const O_CDN_QUIC_KEY 		= 'cdn-quic_key' ;
	const O_CDN_CLOUDFLARE 		= 'cdn-cloudflare' ;
	const O_CDN_CLOUDFLARE_EMAIL= 'cdn-cloudflare_email' ;
	const O_CDN_CLOUDFLARE_KEY 	= 'cdn-cloudflare_key' ;
	const O_CDN_CLOUDFLARE_NAME = 'cdn-cloudflare_name' ;
	const O_CDN_CLOUDFLARE_ZONE = 'cdn-cloudflare_zone' ;
	const O_CDN_MAPPING 		= 'cdn-mapping' ;


	const NETWORK_O_ENABLED = 'network_enabled' ;
	const NETWORK_O_USE_PRIMARY = 'use_primary_settings' ;

	/*** Other consts ***/
	const O_GUIDE = 'litespeed-guide' ; // Array of each guidance tag as key, step as val //xx todo: may need to remove

	// Server variables
	const ENV_CRAWLER_USLEEP = 'CRAWLER_USLEEP' ;
	const ENV_CRAWLER_LOAD_LIMIT = 'CRAWLER_LOAD_LIMIT' ;
	const ENV_CRAWLER_LOAD_LIMIT_ENFORCE = 'CRAWLER_LOAD_LIMIT_ENFORCE' ;

	// const O_FAVICON = 'litespeed-cache-favicon' ;

	const CRWL_COOKIE_NAME 		= 'name' ;
	const CRWL_COOKIE_VALS 		= 'vals' ;

	const CDN_MAPPING_URL 		= 'url' ;
	const CDN_MAPPING_INC_IMG 	= 'inc_img' ;
	const CDN_MAPPING_INC_CSS 	= 'inc_css' ;
	const CDN_MAPPING_INC_JS 	= 'inc_js' ;
	const CDN_MAPPING_FILETYPE 	= 'filetype' ;

	const CRWL_DATE_DESC 		= 'date_desc' ;
	const CRWL_DATE_ASC 		= 'date_asc' ;
	const CRWL_ALPHA_DESC 		= 'alpha_desc' ;
	const CRWL_ALPHA_ASC 		= 'alpha_asc' ;

	const VAL_OFF 	= 0 ;
	const VAL_ON 	= 1 ;
	const VAL_ON2 	= 2 ;

	/* This is for API hook usage */
	const IMG_OPTM_BM_ORI 		= 1 ;
	const IMG_OPTM_BM_WEBP 		= 2 ;
	const IMG_OPTM_BM_LOSSLESS 	= 4 ;
	const IMG_OPTM_BM_EXIF 		= 8 ;

	/* Site related options (Will not overwrite other sites' config) */
	const SINGLE_SITE_OPTIONS = array(
		self::O_CRWL,
		self::O_CDN,
		self::O_CDN_ORI,
		self::O_CDN_ORI_DIR,
		self::O_CDN_EXC,
		self::O_CDN_REMOTE_JQ,
		self::O_CDN_QUIC,
		self::O_CDN_QUIC_EMAIL,
		self::O_CDN_QUIC_KEY,
		self::O_CDN_CLOUDFLARE,
		self::O_CDN_CLOUDFLARE_EMAIL,
		self::O_CDN_CLOUDFLARE_KEY,
		self::O_CDN_CLOUDFLARE_NAME,
		self::O_CDN_CLOUDFLARE_ZONE,
		self::O_CDN_MAPPING,
	) ;

	const CSS_FONT_DISPLAY_SET = array(
		1 => 'block',
		2 => 'swap',
		3 => 'fallback',
		4 => 'optional',
	) ;

	protected $_default_options = array(
		self::_VERSION 			=> '',
		self::HASH				=> '',
		self::O_AUTO_UPGRADE 	=> false,

		// Cache
		self::O_CACHE 					=> false,
		self::O_CACHE_PRIV 				=> false,
		self::O_CACHE_COMMENTER 		=> false,
		self::O_CACHE_REST 				=> false,
		self::O_CACHE_PAGE_LOGIN 		=> false,
		self::O_CACHE_FAVICON 			=> false,
		self::O_CACHE_RES 				=> false,
		self::O_CACHE_MOBILE 			=> false,
		self::O_CACHE_MOBILE_RULES 		=> array(),
		self::O_CACHE_EXC_USERAGENTS 	=> array(),
		self::O_CACHE_EXC_COOKIES 		=> array(),
		self::O_CACHE_EXC_QS 			=> array(),
		self::O_CACHE_EXC_CAT 			=> array(),
		self::O_CACHE_EXC_TAG 			=> array(),
		self::O_CACHE_FORCE_URI			=> array(),
		self::O_CACHE_FORCE_PUB_URI		=> array(),
		self::O_CACHE_PRIV_URI			=> array(),
		self::O_CACHE_EXC 				=> array(),
		self::O_CACHE_EXC_ROLES 		=> array(),
		self::O_CACHE_DROP_QS 			=> array(),
		self::O_CACHE_TTL_PUB 			=> 0,
		self::O_CACHE_TTL_PRIV 			=> 0,
		self::O_CACHE_TTL_FRONTPAGE 	=> 0,
		self::O_CACHE_TTL_FEED 			=> 0,
		self::O_CACHE_TTL_STATUS 		=> array(),
		self::O_CACHE_LOGIN_COOKIE 		=> '',
		self::O_CACHE_VARY_GROUP		=> array(),

		// Purge
		self::O_PURGE_ON_UPGRADE 		=> false,
		self::O_PURGE_POST_ALL			=> false,
		self::O_PURGE_POST_FRONTPAGE	=> false,
		self::O_PURGE_POST_HOMEPAGE		=> false,
		self::O_PURGE_POST_PAGES		=> false,
		self::O_PURGE_POST_PAGES_WITH_RECENT_POSTS	=> false,
		self::O_PURGE_POST_AUTHOR		=> false,
		self::O_PURGE_POST_YEAR			=> false,
		self::O_PURGE_POST_MONTH		=> false,
		self::O_PURGE_POST_DATE			=> false,
		self::O_PURGE_POST_TERM			=> false,
		self::O_PURGE_POST_POSTTYPE		=> false,
		self::O_PURGE_TIMED_URLS 		=> array(),
		self::O_PURGE_TIMED_URLS_TIME 	=> '',
		self::O_PURGE_HOOK_ALL			=> array(),

		// ESI
		self::O_ESI 	 				=> false,
		self::O_ESI_CACHE_ADMBAR 	 	=> false,
		self::O_ESI_CACHE_COMMFORM 	 	=> false,

		// Util
		self::O_UTIL_HEARTBEAT 			=> false,
		self::O_UTIL_BROWSER_CACHE 		=> false,
		self::O_UTIL_BROWSER_CACHE_TTL 	=> 0,
		self::O_UTIL_INSTANT_CLICK 		=> false,
		self::O_UTIL_CHECK_ADVCACHE 	=> false,
		self::O_UTIL_NO_HTTPS_VARY 		=> false,

		// Debug
		self::O_DEBUG_DISABLE_ALL 		=> false,
		self::O_DEBUG 					=> false,
		self::O_DEBUG_IPS 				=> array(),
		self::O_DEBUG_LEVEL 			=> false,
		self::O_DEBUG_FILESIZE 			=> 0,
		self::O_DEBUG_COOKIE 			=> false,
		self::O_DEBUG_COLLAPS_QS 		=> false,
		self::O_DEBUG_LOG_FILTERS 		=> false,
		self::O_DEBUG_LOG_NO_FILTERS 	=> array(),
		self::O_DEBUG_LOG_NO_PART_FILTERS => array(),

		// HTML Optm
		self::O_OPTM_CSS_MIN 			=> false,
		self::O_OPTM_CSS_INLINE_MIN 	=> false,
		self::O_OPTM_CSS_COMB 			=> false,
		self::O_OPTM_CSS_COMB_PRIO 		=> false,
		self::O_OPTM_CSS_HTTP2 			=> false,
		self::O_OPTM_CSS_EXC 			=> array(),
		self::O_OPTM_JS_MIN 			=> false,
		self::O_OPTM_JS_INLINE_MIN 		=> false,
		self::O_OPTM_JS_COMB 			=> false,
		self::O_OPTM_JS_COMB_PRIO 		=> false,
		self::O_OPTM_JS_HTTP2 			=> false,
		self::O_OPTM_JS_EXC 			=> array(),
		self::O_OPTM_TTL 				=> 0,
		self::O_OPTM_HTML_MIN 			=> false,
		self::O_OPTM_QS_RM 				=> false,
		self::O_OPTM_GGFONTS_RM 		=> false,
		self::O_OPTM_CSS_ASYNC 			=> false,
		self::O_OPTM_CCSS_GEN 			=> false,
		self::O_OPTM_CCSS_ASYNC 		=> false,
		self::O_OPTM_CSS_ASYNC_INLINE 	=> false,
		self::O_OPTM_CSS_FONT_DISPLAY 	=> false,
		self::O_OPTM_JS_DEFER 			=> false,
		self::O_OPTM_JS_INLINE_DEFER	=> false,
		self::O_OPTM_EMOJI_RM 			=> false,
		self::O_OPTM_EXC_JQ 			=> false,
		self::O_OPTM_GGFONTS_ASYNC 		=> false,
		self::O_OPTM_MAX_SIZE 			=> 0,
		self::O_OPTM_RM_COMMENT 		=> false,
		self::O_OPTM_EXC_ROLES			=> array(),
		self::O_OPTM_CCSS_CON			=> '',
		self::O_OPTM_JS_DEFER_EXC		=> array(),
		self::O_OPTM_DNS_PREFETCH		=> array(),
		self::O_OPTM_EXC				=> array(),
		self::O_OPTM_CCSS_SEP_POSTTYPE	=> array(),
		self::O_OPTM_CCSS_SEP_URI		=> array(),

		// Object
		self::O_OBJECT 					=> false,
		self::O_OBJECT_KIND 			=> false,
		self::O_OBJECT_HOST 			=> '',
		self::O_OBJECT_PORT 			=> 0,
		self::O_OBJECT_LIFE 			=> 0,
		self::O_OBJECT_PERSISTENT 		=> false,
		self::O_OBJECT_ADMIN 			=> false,
		self::O_OBJECT_TRANSIENTS 		=> false,
		self::O_OBJECT_DB_ID 			=> 0,
		self::O_OBJECT_USER 			=> '',
		self::O_OBJECT_PSWD 			=> '',
		self::O_OBJECT_GLOBAL_GROUPS	=> array(),
		self::O_OBJECT_NON_PERSISTENT_GROUPS => array(),

		// Media
		self::O_MEDIA_LAZY 							=> false,
		self::O_MEDIA_LAZY_PLACEHOLDER 				=> '',
		self::O_MEDIA_PLACEHOLDER_RESP				=> false,
		self::O_MEDIA_PLACEHOLDER_RESP_COLOR		=> '',
		self::O_MEDIA_PLACEHOLDER_RESP_GENERATOR	=> false,
		self::O_MEDIA_PLACEHOLDER_RESP_SVG			=> '',
		self::O_MEDIA_PLACEHOLDER_RESP_ASYNC		=> false,
		self::O_MEDIA_IFRAME_LAZY 					=> false,
		self::O_MEDIA_LAZYJS_INLINE 				=> false,
		self::O_MEDIA_LAZY_EXC 						=> array(),
		self::O_MEDIA_LAZY_CLS_EXC 					=> array(),
		self::O_MEDIA_LAZY_PARENT_CLS_EXC 			=> array(),
		self::O_MEDIA_IFRAME_LAZY_CLS_EXC 			=> array(),
		self::O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC 	=> array(),
		self::O_MEDIA_LAZY_URI_EXC 					=> array(),

		// Image Optm
		self::O_IMG_OPTM_AUTO 			=> false,
		self::O_IMG_OPTM_CRON 			=> false,
		self::O_IMG_OPTM_ORI 			=> false,
		self::O_IMG_OPTM_RM_BKUP 		=> false,
		self::O_IMG_OPTM_WEBP 			=> false,
		self::O_IMG_OPTM_LOSSLESS 		=> false,
		self::O_IMG_OPTM_EXIF 			=> false,
		self::O_IMG_OPTM_WEBP_REPLACE 	=> false,
		self::O_IMG_OPTM_WEBP_ATTR		=> array(),
		self::O_IMG_OPTM_WEBP_REPLACE_SRCSET 	=> false,
		self::O_IMG_OPTM_JPG_QUALITY 	=> 0,

		// Crawler
		self::O_CRWL 					=> false,
		self::O_CRWL_POSTS 				=> false,
		self::O_CRWL_PAGES 				=> false,
		self::O_CRWL_CATS 				=> false,
		self::O_CRWL_TAGS 				=> false,
		self::O_CRWL_EXC_CPT 			=> array(),
		self::O_CRWL_ORDER_LINKS 		=> '',
		self::O_CRWL_USLEEP 			=> 0,
		self::O_CRWL_RUN_DURATION 		=> 0,
		self::O_CRWL_RUN_INTERVAL 		=> 0,
		self::O_CRWL_CRAWL_INTERVAL 	=> 0,
		self::O_CRWL_THREADS 			=> 0,
		self::O_CRWL_TIMEOUT 			=> 0,
		self::O_CRWL_LOAD_LIMIT 		=> 0,
		self::O_CRWL_DOMAIN_IP 			=> '',
		self::O_CRWL_CUSTOM_SITEMAP 	=> '',
		self::O_CRWL_ROLES				=> array(),
		self::O_CRWL_COOKIES 			=> array(),

		// CDN
		self::O_CDN 				=> false,
		self::O_CDN_ORI 			=> array(),
		self::O_CDN_ORI_DIR 		=> array(),
		self::O_CDN_EXC 			=> array(),
		self::O_CDN_REMOTE_JQ 		=> false,
		self::O_CDN_QUIC 			=> false,
		self::O_CDN_QUIC_EMAIL 		=> '',
		self::O_CDN_QUIC_KEY 		=> '',
		self::O_CDN_CLOUDFLARE 		=> false,
		self::O_CDN_CLOUDFLARE_EMAIL => '',
		self::O_CDN_CLOUDFLARE_KEY 	=> '',
		self::O_CDN_CLOUDFLARE_NAME => '',
		self::O_CDN_CLOUDFLARE_ZONE => '',
		self::O_CDN_MAPPING 		=> array(),

	) ;

	protected $_default_site_options = array(
		self::_VERSION 					=> '',
		self::NETWORK_O_ENABLED 		=> false,
		self::NETWORK_O_USE_PRIMARY 	=> false,
		self::O_AUTO_UPGRADE 			=> false,

		self::O_CACHE_FAVICON 			=> false,
		self::O_CACHE_RES 				=> false,
		self::O_CACHE_MOBILE 			=> false,
		self::O_CACHE_MOBILE_RULES 		=> array(),
		self::O_CACHE_LOGIN_COOKIE 		=> '',
		self::O_CACHE_EXC_COOKIES 		=> array(),
		self::O_CACHE_EXC_USERAGENTS 	=> array(),

		self::O_PURGE_ON_UPGRADE 		=> false,

		self::O_OBJECT 					=> false,
		self::O_OBJECT_KIND 			=> false,
		self::O_OBJECT_HOST 			=> '',
		self::O_OBJECT_PORT 			=> 0,
		self::O_OBJECT_LIFE 			=> 0,
		self::O_OBJECT_PERSISTENT 		=> false,
		self::O_OBJECT_ADMIN 			=> false,
		self::O_OBJECT_TRANSIENTS 		=> false,
		self::O_OBJECT_DB_ID 			=> 0,
		self::O_OBJECT_USER 			=> '',
		self::O_OBJECT_PSWD 			=> '',
		self::O_OBJECT_GLOBAL_GROUPS	=> array(),
		self::O_OBJECT_NON_PERSISTENT_GROUPS => array(),

		self::O_UTIL_BROWSER_CACHE 		=> false,
		self::O_UTIL_BROWSER_CACHE_TTL 	=> 0,
		self::O_UTIL_CHECK_ADVCACHE 	=> false,

		self::O_IMG_OPTM_WEBP_REPLACE 	=> false,
	) ;

	private function __construct()
	{
	}

	protected function default_site_vals()
	{
		// Load network_default.ini
		if ( file_exists( LSCWP_DIR . 'data/const.network_default.ini' ) ) {
			$default_ini_cfg = parse_ini_file( LSCWP_DIR . 'data/const.network_default.ini', true ) ;
			foreach ( $this->_default_site_options as $k => $v ) {
				if ( ! array_key_exists( $k, $default_ini_cfg ) ) {
					continue ;
				}

				// Parse value in ini file
				$ini_v = $default_ini_cfg[ $k ] ;

				if ( is_bool( $v ) ) { // Keep value type constantly
					$max = $this->_conf_multi_switch( $k ) ;
					if ( $max && $ini_v > 1 ) {
						$ini_v %= $max + 1 ;
					}
					else {
						$ini_v = (bool) $ini_v ;
					}
				}

				if ( is_array( $v ) ) {
					/**
					 * Default multiple lines to array
					 */
					$ini_v = explode( "\n", $ini_v ) ;
				}

				if ( $ini_v == $v ) {
					continue ;
				}

				$this->_default_site_options[ $k ] = $ini_v ;

			}
		}

		$this->_default_site_options[ self::_VERSION ] = LiteSpeed_Cache::PLUGIN_VERSION ;

		return $this->_default_site_options ;
	}

	/**
	 * Load default values from default.ini
	 *
	 * @since 3.0
	 * @access public
	 */
	public function default_vals()
	{
		// Load default.ini
		if ( file_exists( LSCWP_DIR . 'data/const.default.ini' ) ) {
			$default_ini_cfg = parse_ini_file( LSCWP_DIR . 'data/const.default.ini', true ) ;
			foreach ( $this->_default_options as $k => $v ) {
				if ( ! array_key_exists( $k, $default_ini_cfg ) ) {
					continue ;
				}

				// Parse value in ini file
				$ini_v = $default_ini_cfg[ $k ] ;

				if ( is_bool( $v ) ) { // Keep value type constantly
					$max = $this->_conf_multi_switch( $k ) ;
					if ( $max && $ini_v > 1 ) {
						$ini_v %= $max + 1 ;
					}
					else {
						$ini_v = (bool) $ini_v ;
					}
				}

				// NOTE: Multiple lines value must be stored as array
				if ( is_array( $v ) ) {
					/**
					 * Special handler for CDN_mapping
					 *
					 * format in .ini:
					 * 		[cdn-mapping]
					 *   	url[0] = 'https://example.com/'
					 *     	inc_js[0] = true
					 *     	filetype[0] = '.css
					 *     				   .js
					 *     				   .jpg'
					 *
					 * format out:
					 * 		[0] = [ 'url' => 'https://example.com', 'inc_js' => true, 'filetype' => [ '.css', '.js', '.jpg' ] ]
					 */
					if ( $k == self::O_CDN_MAPPING ) {
						$mapping_fields = array(
							self::CDN_MAPPING_URL,
							self::CDN_MAPPING_INC_IMG,
							self::CDN_MAPPING_INC_CSS,
							self::CDN_MAPPING_INC_JS,
							self::CDN_MAPPING_FILETYPE, // Array
						) ;
						$ini_v = array() ;
						foreach ( $default_ini_cfg[ $k ][ self::CDN_MAPPING_URL ] as $k2 => $v2 ) {// $k2 is numeric
							$this_row = array() ;
							foreach ( $mapping_fields as $v3 ) {
								$this_v = ! empty( $ini_v[ $v3 ][ $k2 ] ) ? $ini_v[ $v3 ][ $k2 ] : false ;
								if ( $v3 == self::CDN_MAPPING_FILETYPE ) {
									$this_v = $this_v ? LiteSpeed_Cache_Utility::sanitize_lines( $this_v ) : array() ;
								}
								$this_row[ $v3 ] = $this_v ;
							}
							$ini_v[ $k2 ] = $this_row ;
						}
					}
					/**
					 * Default multiple lines to array
					 */
					else {
						$ini_v = LiteSpeed_Cache_Utility::sanitize_lines( $ini_v ) ;
					}
				}

				if ( $ini_v == $v ) {
					continue ;
				}

				$this->_default_options[ $k ] = $ini_v ;
			}

		}

		// Load internal default vals
		$this->_default_options[ self::O_CACHE ] = is_multisite() ? self::VAL_ON2 : self::VAL_ON ; //For multi site, default is 2 (Use Network Admin Settings). For single site, default is 1 (Enabled).

		// Load default vals containing variables
		if ( ! $this->_default_options[ self::O_CDN_ORI_DIR ] ) {
			$this->_default_options[ self::O_CDN_ORI_DIR ] = LSCWP_CONTENT_FOLDER . "\nwp-includes\n/min/" ;
			$this->_default_options[ self::O_CDN_ORI_DIR ] = explode( "\n", $this->_default_options[ self::O_CDN_ORI_DIR ] ) ;
			$this->_default_options[ self::O_CDN_ORI_DIR ] = array_map( 'trim', $this->_default_options[ self::O_CDN_ORI_DIR ] ) ;
		}

		// Set security key if not initialized yet
		if ( ! $this->_default_options[ self::HASH ] ) {
			$this->_default_options[ self::HASH ] = Litespeed_String::rrand( 32 ) ;
		}

		$this->_default_options[ self::_VERSION ] = LiteSpeed_Cache::PLUGIN_VERSION ;

		return $this->_default_options ;
	}

	/**
	 * Format the string value
	 *
	 * @since  3.0
	 */
	protected function _conf_string_val( $id, $val )
	{
		if ( $id == self::O_CRWL_ORDER_LINKS ) {
			if ( ! in_array( $id, array(
				self::CRWL_DATE_DESC,
				self::CRWL_DATE_ASC,
				self::CRWL_ALPHA_DESC,
				self::CRWL_ALPHA_ASC,
			) ) ) {
				$val = self::CRWL_DATE_DESC ;
			}
		}

		return $val ;
	}

	/**
	 * If the switch setting is a triple value or not
	 *
	 * @since  3.0
	 */
	protected function _conf_multi_switch( $id )
	{
		$list = array(
			self::O_CDN_REMOTE_JQ 	=> 2,
			self::O_DEBUG 			=> 2,
			self::O_OPTM_CSS_FONT_DISPLAY 	=> 4,
		) ;

		if ( ! empty( $list[ $id ] ) ) {
			return $list[ $id ] ;
		}

		return false ;
	}

	/**
	 * Filter to be used when saving setting
	 *
	 * @since  3.0
	 */
	protected function _conf_filter( $id )
	{
		$filters = array(
			self::O_MEDIA_LAZY_EXC		=> 'uri',
			self::O_MEDIA_LAZY_URI_EXC	=> 'relative',
			self::O_CACHE_PRIV_URI		=> 'relative',
			self::O_PURGE_TIMED_URLS	=> 'relative',
			self::O_CACHE_FORCE_URI		=> 'relative',
			self::O_CACHE_FORCE_PUB_URI	=> 'relative',
			self::O_CACHE_EXC			=> 'relative',
			self::O_OPTM_CSS_EXC		=> 'uri',
			self::O_OPTM_JS_EXC			=> 'uri',
			self::O_OPTM_EXC			=> 'relative',
			self::O_OPTM_JS_DEFER_EXC	=> 'uri',
			self::O_OPTM_DNS_PREFETCH	=> 'domain',
			self::O_OPTM_CCSS_SEP_URI	=> 'uri',
			// self::	=> '',
			// self::	=> '',
		) ;

		if ( ! empty( $filters[ $id ] ) ) {
			return $filters[ $id ] ;
		}

		return false ;
	}

	/**
	 * If the setting changes worth a purge or not
	 *
	 * @since  3.0
	 */
	protected function _conf_purge( $id )
	{
		$check_ids = array(
			self::O_MEDIA_LAZY_URI_EXC,
			self::O_OPTM_EXC,
			self::O_CACHE_PRIV_URI,
			self::O_PURGE_TIMED_URLS,
			self::O_CACHE_FORCE_URI,
			self::O_CACHE_FORCE_PUB_URI,
			self::O_CACHE_EXC,
		) ;

		if ( in_array( $id, $check_ids ) ) {
			return true ;
		}

		return false ;
	}

	/**
	 * If the setting changes worth a purge ALL or not
	 *
	 * @since  3.0
	 */
	protected function _conf_purge_all( $id )
	{
		$check_ids = array(
			self::O_CACHE,
			self::O_ESI,
			self::O_DEBUG_DISABLE_ALL,
			self::NETWORK_O_ENABLED,
			self::NETWORK_O_USE_PRIMARY,
		) ;

		if ( in_array( $id, $check_ids ) ) {
			return true ;
		}

		return false ;
	}

	/**
	 * If the setting changes worth a purge, return the tag
	 *
	 * @since  3.0
	 */
	protected function _conf_purge_tag( $id )
	{
		$check_ids = array(
			self::O_CACHE_PAGE_LOGIN	=> LiteSpeed_Cache_Tag::TYPE_LOGIN,
		) ;

		if ( ! empty( $check_ids[ $id ] ) ) {
			return $check_ids[ $id ] ;
		}

		return false ;
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
			'LSWCP_TAG_PREFIX',
		) ;
		$server_vars = array() ;
		foreach ( $consts as $v ) {
			$server_vars[ $v ] = defined( $v ) ? constant( $v ) : NULL ;
		}

		return $server_vars ;
	}

}