<?php

/**
 * The base consts
 *
 * @since      	3.7
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Base extends Root
{
	// This is redundant since v3.0
	// New conf items are `litespeed.key`
	const OPTION_NAME = 'litespeed-cache-conf';

	const _CACHE = '_cache'; // final cache status from setting

	## -------------------------------------------------- ##
	## --------------     	General	    ----------------- ##
	## -------------------------------------------------- ##
	const _VER = '_version'; // Not set-able
	const HASH = 'hash'; // Not set-able
	const O_AUTO_UPGRADE = 'auto_upgrade';
	const O_API_KEY = 'api_key'; // Deprecated since v6.4. TODO: Will drop after v6.5
	const O_SERVER_IP = 'server_ip';
	const O_GUEST = 'guest';
	const O_GUEST_OPTM = 'guest_optm';
	const O_NEWS = 'news';
	const O_GUEST_UAS = 'guest_uas';
	const O_GUEST_IPS = 'guest_ips';

	## -------------------------------------------------- ##
	## --------------		Cache 		----------------- ##
	## -------------------------------------------------- ##
	const O_CACHE = 'cache';
	const O_CACHE_PRIV = 'cache-priv';
	const O_CACHE_COMMENTER = 'cache-commenter';
	const O_CACHE_REST = 'cache-rest';
	const O_CACHE_PAGE_LOGIN = 'cache-page_login';
	const O_CACHE_FAVICON = 'cache-favicon'; // Deprecated since v6.2. TODO: Will drop after v6.5
	const O_CACHE_RES = 'cache-resources';
	const O_CACHE_MOBILE = 'cache-mobile';
	const O_CACHE_MOBILE_RULES = 'cache-mobile_rules';
	const O_CACHE_BROWSER = 'cache-browser';
	const O_CACHE_EXC_USERAGENTS = 'cache-exc_useragents';
	const O_CACHE_EXC_COOKIES = 'cache-exc_cookies';
	const O_CACHE_EXC_QS = 'cache-exc_qs';
	const O_CACHE_EXC_CAT = 'cache-exc_cat';
	const O_CACHE_EXC_TAG = 'cache-exc_tag';
	const O_CACHE_FORCE_URI = 'cache-force_uri';
	const O_CACHE_FORCE_PUB_URI = 'cache-force_pub_uri';
	const O_CACHE_PRIV_URI = 'cache-priv_uri';
	const O_CACHE_EXC = 'cache-exc';
	const O_CACHE_EXC_ROLES = 'cache-exc_roles';
	const O_CACHE_DROP_QS = 'cache-drop_qs';
	const O_CACHE_TTL_PUB = 'cache-ttl_pub';
	const O_CACHE_TTL_PRIV = 'cache-ttl_priv';
	const O_CACHE_TTL_FRONTPAGE = 'cache-ttl_frontpage';
	const O_CACHE_TTL_FEED = 'cache-ttl_feed';
	const O_CACHE_TTL_REST = 'cache-ttl_rest';
	const O_CACHE_TTL_STATUS = 'cache-ttl_status';
	const O_CACHE_TTL_BROWSER = 'cache-ttl_browser';
	const O_CACHE_AJAX_TTL = 'cache-ajax_ttl';
	const O_CACHE_LOGIN_COOKIE = 'cache-login_cookie';
	const O_CACHE_VARY_COOKIES = 'cache-vary_cookies';
	const O_CACHE_VARY_GROUP = 'cache-vary_group';

	## -------------------------------------------------- ##
	## --------------		Purge 		----------------- ##
	## -------------------------------------------------- ##
	const O_PURGE_ON_UPGRADE = 'purge-upgrade';
	const O_PURGE_STALE = 'purge-stale';
	const O_PURGE_POST_ALL = 'purge-post_all';
	const O_PURGE_POST_FRONTPAGE = 'purge-post_f';
	const O_PURGE_POST_HOMEPAGE = 'purge-post_h';
	const O_PURGE_POST_PAGES = 'purge-post_p';
	const O_PURGE_POST_PAGES_WITH_RECENT_POSTS = 'purge-post_pwrp';
	const O_PURGE_POST_AUTHOR = 'purge-post_a';
	const O_PURGE_POST_YEAR = 'purge-post_y';
	const O_PURGE_POST_MONTH = 'purge-post_m';
	const O_PURGE_POST_DATE = 'purge-post_d';
	const O_PURGE_POST_TERM = 'purge-post_t'; // include category|tag|tax
	const O_PURGE_POST_POSTTYPE = 'purge-post_pt';
	const O_PURGE_TIMED_URLS = 'purge-timed_urls';
	const O_PURGE_TIMED_URLS_TIME = 'purge-timed_urls_time';
	const O_PURGE_HOOK_ALL = 'purge-hook_all';

	## -------------------------------------------------- ##
	## --------------     	 ESI	    ----------------- ##
	## -------------------------------------------------- ##
	const O_ESI = 'esi';
	const O_ESI_CACHE_ADMBAR = 'esi-cache_admbar';
	const O_ESI_CACHE_COMMFORM = 'esi-cache_commform';
	const O_ESI_NONCE = 'esi-nonce';

	## -------------------------------------------------- ##
	## --------------     Utilities	    ----------------- ##
	## -------------------------------------------------- ##
	const O_UTIL_INSTANT_CLICK = 'util-instant_click';
	const O_UTIL_NO_HTTPS_VARY = 'util-no_https_vary';

	## -------------------------------------------------- ##
	## --------------		Debug 		----------------- ##
	## -------------------------------------------------- ##
	const O_DEBUG_DISABLE_ALL = 'debug-disable_all';
	const O_DEBUG = 'debug';
	const O_DEBUG_IPS = 'debug-ips';
	const O_DEBUG_LEVEL = 'debug-level';
	const O_DEBUG_FILESIZE = 'debug-filesize';
	const O_DEBUG_COOKIE = 'debug-cookie'; // For backwards compatibility, will drop after v7.0
	const O_DEBUG_COLLAPSE_QS = 'debug-collapse_qs';
	const O_DEBUG_COLLAPS_QS = 'debug-collapse_qs'; // For backwards compatibility, will drop after v6.5
	const O_DEBUG_INC = 'debug-inc';
	const O_DEBUG_EXC = 'debug-exc';
	const O_DEBUG_EXC_STRINGS = 'debug-exc_strings';

	## -------------------------------------------------- ##
	## --------------	   DB Optm  	----------------- ##
	## -------------------------------------------------- ##
	const O_DB_OPTM_REVISIONS_MAX = 'db_optm-revisions_max';
	const O_DB_OPTM_REVISIONS_AGE = 'db_optm-revisions_age';

	## -------------------------------------------------- ##
	## --------------	  HTML Optm 	----------------- ##
	## -------------------------------------------------- ##
	const O_OPTM_CSS_MIN = 'optm-css_min';
	const O_OPTM_CSS_COMB = 'optm-css_comb';
	const O_OPTM_CSS_COMB_EXT_INL = 'optm-css_comb_ext_inl';
	const O_OPTM_UCSS = 'optm-ucss';
	const O_OPTM_UCSS_INLINE = 'optm-ucss_inline';
	const O_OPTM_UCSS_SELECTOR_WHITELIST = 'optm-ucss_whitelist';
	const O_OPTM_UCSS_FILE_EXC_INLINE = 'optm-ucss_file_exc_inline';
	const O_OPTM_UCSS_EXC = 'optm-ucss_exc';
	const O_OPTM_CSS_EXC = 'optm-css_exc';
	const O_OPTM_JS_MIN = 'optm-js_min';
	const O_OPTM_JS_COMB = 'optm-js_comb';
	const O_OPTM_JS_COMB_EXT_INL = 'optm-js_comb_ext_inl';
	const O_OPTM_JS_DELAY_INC = 'optm-js_delay_inc';
	const O_OPTM_JS_EXC = 'optm-js_exc';
	const O_OPTM_HTML_MIN = 'optm-html_min';
	const O_OPTM_HTML_LAZY = 'optm-html_lazy';
	const O_OPTM_HTML_SKIP_COMMENTS = 'optm-html_skip_comment';
	const O_OPTM_QS_RM = 'optm-qs_rm';
	const O_OPTM_GGFONTS_RM = 'optm-ggfonts_rm';
	const O_OPTM_CSS_ASYNC = 'optm-css_async';
	const O_OPTM_CCSS_PER_URL = 'optm-ccss_per_url';
	const O_OPTM_CCSS_SEP_POSTTYPE = 'optm-ccss_sep_posttype';
	const O_OPTM_CCSS_SEP_URI = 'optm-ccss_sep_uri';
	const O_OPTM_CCSS_SELECTOR_WHITELIST = 'optm-ccss_whitelist';
	const O_OPTM_CSS_ASYNC_INLINE = 'optm-css_async_inline';
	const O_OPTM_CSS_FONT_DISPLAY = 'optm-css_font_display';
	const O_OPTM_JS_DEFER = 'optm-js_defer';
	const O_OPTM_LOCALIZE = 'optm-localize';
	const O_OPTM_LOCALIZE_DOMAINS = 'optm-localize_domains';
	const O_OPTM_EMOJI_RM = 'optm-emoji_rm';
	const O_OPTM_NOSCRIPT_RM = 'optm-noscript_rm';
	const O_OPTM_GGFONTS_ASYNC = 'optm-ggfonts_async';
	const O_OPTM_EXC_ROLES = 'optm-exc_roles';
	const O_OPTM_CCSS_CON = 'optm-ccss_con';
	const O_OPTM_JS_DEFER_EXC = 'optm-js_defer_exc';
	const O_OPTM_GM_JS_EXC = 'optm-gm_js_exc';
	const O_OPTM_DNS_PREFETCH = 'optm-dns_prefetch';
	const O_OPTM_DNS_PREFETCH_CTRL = 'optm-dns_prefetch_ctrl';
	const O_OPTM_DNS_PRECONNECT = 'optm-dns_preconnect';
	const O_OPTM_EXC = 'optm-exc';
	const O_OPTM_GUEST_ONLY = 'optm-guest_only';

	## -------------------------------------------------- ##
	## --------------	Object Cache	----------------- ##
	## -------------------------------------------------- ##
	const O_OBJECT = 'object';
	const O_OBJECT_KIND = 'object-kind';
	const O_OBJECT_HOST = 'object-host';
	const O_OBJECT_PORT = 'object-port';
	const O_OBJECT_LIFE = 'object-life';
	const O_OBJECT_PERSISTENT = 'object-persistent';
	const O_OBJECT_ADMIN = 'object-admin';
	const O_OBJECT_TRANSIENTS = 'object-transients';
	const O_OBJECT_DB_ID = 'object-db_id';
	const O_OBJECT_USER = 'object-user';
	const O_OBJECT_PSWD = 'object-pswd';
	const O_OBJECT_GLOBAL_GROUPS = 'object-global_groups';
	const O_OBJECT_NON_PERSISTENT_GROUPS = 'object-non_persistent_groups';

	## -------------------------------------------------- ##
	## --------------	Discussion		----------------- ##
	## -------------------------------------------------- ##
	const O_DISCUSS_AVATAR_CACHE = 'discuss-avatar_cache';
	const O_DISCUSS_AVATAR_CRON = 'discuss-avatar_cron';
	const O_DISCUSS_AVATAR_CACHE_TTL = 'discuss-avatar_cache_ttl';

	## -------------------------------------------------- ##
	## --------------		 Media 		----------------- ##
	## -------------------------------------------------- ##
	const O_MEDIA_PRELOAD_FEATURED = 'media-preload_featured'; // Deprecated since v6.2. TODO: Will drop after v6.5
	const O_MEDIA_LAZY = 'media-lazy';
	const O_MEDIA_LAZY_PLACEHOLDER = 'media-lazy_placeholder';
	const O_MEDIA_PLACEHOLDER_RESP = 'media-placeholder_resp';
	const O_MEDIA_PLACEHOLDER_RESP_COLOR = 'media-placeholder_resp_color';
	const O_MEDIA_PLACEHOLDER_RESP_SVG = 'media-placeholder_resp_svg';
	const O_MEDIA_LQIP = 'media-lqip';
	const O_MEDIA_LQIP_QUAL = 'media-lqip_qual';
	const O_MEDIA_LQIP_MIN_W = 'media-lqip_min_w';
	const O_MEDIA_LQIP_MIN_H = 'media-lqip_min_h';
	const O_MEDIA_PLACEHOLDER_RESP_ASYNC = 'media-placeholder_resp_async';
	const O_MEDIA_IFRAME_LAZY = 'media-iframe_lazy';
	const O_MEDIA_ADD_MISSING_SIZES = 'media-add_missing_sizes';
	const O_MEDIA_LAZY_EXC = 'media-lazy_exc';
	const O_MEDIA_LAZY_CLS_EXC = 'media-lazy_cls_exc';
	const O_MEDIA_LAZY_PARENT_CLS_EXC = 'media-lazy_parent_cls_exc';
	const O_MEDIA_IFRAME_LAZY_CLS_EXC = 'media-iframe_lazy_cls_exc';
	const O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC = 'media-iframe_lazy_parent_cls_exc';
	const O_MEDIA_LAZY_URI_EXC = 'media-lazy_uri_exc';
	const O_MEDIA_LQIP_EXC = 'media-lqip_exc';
	const O_MEDIA_VPI = 'media-vpi';
	const O_MEDIA_VPI_CRON = 'media-vpi_cron';
	const O_IMG_OPTM_JPG_QUALITY = 'img_optm-jpg_quality';

	## -------------------------------------------------- ##
	## --------------	  Image Optm 	----------------- ##
	## -------------------------------------------------- ##
	const O_IMG_OPTM_AUTO = 'img_optm-auto';
	const O_IMG_OPTM_CRON = 'img_optm-cron'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_IMG_OPTM_ORI = 'img_optm-ori';
	const O_IMG_OPTM_RM_BKUP = 'img_optm-rm_bkup';
	const O_IMG_OPTM_WEBP = 'img_optm-webp';
	const O_IMG_OPTM_LOSSLESS = 'img_optm-lossless';
	const O_IMG_OPTM_EXIF = 'img_optm-exif';
	const O_IMG_OPTM_WEBP_ATTR = 'img_optm-webp_attr';
	const O_IMG_OPTM_WEBP_REPLACE_SRCSET = 'img_optm-webp_replace_srcset';

	## -------------------------------------------------- ##
	## --------------		Crawler		----------------- ##
	## -------------------------------------------------- ##
	const O_CRAWLER = 'crawler';
	const O_CRAWLER_USLEEP = 'crawler-usleep'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_CRAWLER_RUN_DURATION = 'crawler-run_duration'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_CRAWLER_RUN_INTERVAL = 'crawler-run_interval'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_CRAWLER_CRAWL_INTERVAL = 'crawler-crawl_interval';
	const O_CRAWLER_THREADS = 'crawler-threads'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_CRAWLER_TIMEOUT = 'crawler-timeout'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_CRAWLER_LOAD_LIMIT = 'crawler-load_limit';
	const O_CRAWLER_SITEMAP = 'crawler-sitemap';
	const O_CRAWLER_DROP_DOMAIN = 'crawler-drop_domain'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_CRAWLER_MAP_TIMEOUT = 'crawler-map_timeout'; // @Deprecated since v7.0 TODO: remove after v7.5
	const O_CRAWLER_ROLES = 'crawler-roles';
	const O_CRAWLER_COOKIES = 'crawler-cookies';

	## -------------------------------------------------- ##
	## --------------		 Misc 		----------------- ##
	## -------------------------------------------------- ##
	const O_MISC_HEARTBEAT_FRONT = 'misc-heartbeat_front';
	const O_MISC_HEARTBEAT_FRONT_TTL = 'misc-heartbeat_front_ttl';
	const O_MISC_HEARTBEAT_BACK = 'misc-heartbeat_back';
	const O_MISC_HEARTBEAT_BACK_TTL = 'misc-heartbeat_back_ttl';
	const O_MISC_HEARTBEAT_EDITOR = 'misc-heartbeat_editor';
	const O_MISC_HEARTBEAT_EDITOR_TTL = 'misc-heartbeat_editor_ttl';

	## -------------------------------------------------- ##
	## --------------		 CDN 		----------------- ##
	## -------------------------------------------------- ##
	const O_CDN = 'cdn';
	const O_CDN_ORI = 'cdn-ori';
	const O_CDN_ORI_DIR = 'cdn-ori_dir';
	const O_CDN_EXC = 'cdn-exc';
	const O_CDN_QUIC = 'cdn-quic'; // No more a visible setting since v7
	const O_CDN_CLOUDFLARE = 'cdn-cloudflare';
	const O_CDN_CLOUDFLARE_EMAIL = 'cdn-cloudflare_email';
	const O_CDN_CLOUDFLARE_KEY = 'cdn-cloudflare_key';
	const O_CDN_CLOUDFLARE_NAME = 'cdn-cloudflare_name';
	const O_CDN_CLOUDFLARE_ZONE = 'cdn-cloudflare_zone';
	const O_CDN_MAPPING = 'cdn-mapping';
	const O_CDN_ATTR = 'cdn-attr';
	const O_QC_NAMESERVERS = 'qc-nameservers';
	const O_QC_CNAME = 'qc-cname';

	const NETWORK_O_USE_PRIMARY = 'use_primary_settings';

	/*** Other consts ***/
	const O_GUIDE = 'litespeed-guide'; // Array of each guidance tag as key, step as val //xx todo: may need to remove

	// Server variables
	const ENV_CRAWLER_USLEEP = 'CRAWLER_USLEEP';
	const ENV_CRAWLER_LOAD_LIMIT = 'CRAWLER_LOAD_LIMIT';
	const ENV_CRAWLER_LOAD_LIMIT_ENFORCE = 'CRAWLER_LOAD_LIMIT_ENFORCE';

	const CRWL_COOKIE_NAME = 'name';
	const CRWL_COOKIE_VALS = 'vals';

	const CDN_MAPPING_URL = 'url';
	const CDN_MAPPING_INC_IMG = 'inc_img';
	const CDN_MAPPING_INC_CSS = 'inc_css';
	const CDN_MAPPING_INC_JS = 'inc_js';
	const CDN_MAPPING_FILETYPE = 'filetype';

	const VAL_OFF = 0;
	const VAL_ON = 1;
	const VAL_ON2 = 2;

	/* This is for API hook usage */
	const IMG_OPTM_BM_ORI = 1; // @Deprecated since v7.0
	const IMG_OPTM_BM_WEBP = 2; // @Deprecated since v7.0
	const IMG_OPTM_BM_LOSSLESS = 4; // @Deprecated since v7.0
	const IMG_OPTM_BM_EXIF = 8; // @Deprecated since v7.0
	const IMG_OPTM_BM_AVIF = 16; // @Deprecated since v7.0

	/* Site related options (Will not overwrite other sites' config) */
	protected static $SINGLE_SITE_OPTIONS = array(
		self::O_CRAWLER,
		self::O_CRAWLER_SITEMAP,
		self::O_CDN,
		self::O_CDN_ORI,
		self::O_CDN_ORI_DIR,
		self::O_CDN_EXC,
		self::O_CDN_CLOUDFLARE,
		self::O_CDN_CLOUDFLARE_EMAIL,
		self::O_CDN_CLOUDFLARE_KEY,
		self::O_CDN_CLOUDFLARE_NAME,
		self::O_CDN_CLOUDFLARE_ZONE,
		self::O_CDN_MAPPING,
		self::O_CDN_ATTR,
		self::O_QC_NAMESERVERS,
		self::O_QC_CNAME,
	);

	protected static $_default_options = array(
		self::_VER => '',
		self::HASH => '',
		self::O_API_KEY => '',
		self::O_AUTO_UPGRADE => false,
		self::O_SERVER_IP => '',
		self::O_GUEST => false,
		self::O_GUEST_OPTM => false,
		self::O_NEWS => false,
		self::O_GUEST_UAS => array(),
		self::O_GUEST_IPS => array(),

		// Cache
		self::O_CACHE => false,
		self::O_CACHE_PRIV => false,
		self::O_CACHE_COMMENTER => false,
		self::O_CACHE_REST => false,
		self::O_CACHE_PAGE_LOGIN => false,
		self::O_CACHE_RES => false,
		self::O_CACHE_MOBILE => false,
		self::O_CACHE_MOBILE_RULES => array(),
		self::O_CACHE_BROWSER => false,
		self::O_CACHE_EXC_USERAGENTS => array(),
		self::O_CACHE_EXC_COOKIES => array(),
		self::O_CACHE_EXC_QS => array(),
		self::O_CACHE_EXC_CAT => array(),
		self::O_CACHE_EXC_TAG => array(),
		self::O_CACHE_FORCE_URI => array(),
		self::O_CACHE_FORCE_PUB_URI => array(),
		self::O_CACHE_PRIV_URI => array(),
		self::O_CACHE_EXC => array(),
		self::O_CACHE_EXC_ROLES => array(),
		self::O_CACHE_DROP_QS => array(),
		self::O_CACHE_TTL_PUB => 0,
		self::O_CACHE_TTL_PRIV => 0,
		self::O_CACHE_TTL_FRONTPAGE => 0,
		self::O_CACHE_TTL_FEED => 0,
		self::O_CACHE_TTL_REST => 0,
		self::O_CACHE_TTL_BROWSER => 0,
		self::O_CACHE_TTL_STATUS => array(),
		self::O_CACHE_LOGIN_COOKIE => '',
		self::O_CACHE_AJAX_TTL => array(),
		self::O_CACHE_VARY_COOKIES => array(),
		self::O_CACHE_VARY_GROUP => array(),

		// Purge
		self::O_PURGE_ON_UPGRADE => false,
		self::O_PURGE_STALE => false,
		self::O_PURGE_POST_ALL => false,
		self::O_PURGE_POST_FRONTPAGE => false,
		self::O_PURGE_POST_HOMEPAGE => false,
		self::O_PURGE_POST_PAGES => false,
		self::O_PURGE_POST_PAGES_WITH_RECENT_POSTS => false,
		self::O_PURGE_POST_AUTHOR => false,
		self::O_PURGE_POST_YEAR => false,
		self::O_PURGE_POST_MONTH => false,
		self::O_PURGE_POST_DATE => false,
		self::O_PURGE_POST_TERM => false,
		self::O_PURGE_POST_POSTTYPE => false,
		self::O_PURGE_TIMED_URLS => array(),
		self::O_PURGE_TIMED_URLS_TIME => '',
		self::O_PURGE_HOOK_ALL => array(),

		// ESI
		self::O_ESI => false,
		self::O_ESI_CACHE_ADMBAR => false,
		self::O_ESI_CACHE_COMMFORM => false,
		self::O_ESI_NONCE => array(),

		// Util
		self::O_UTIL_INSTANT_CLICK => false,
		self::O_UTIL_NO_HTTPS_VARY => false,

		// Debug
		self::O_DEBUG_DISABLE_ALL => false,
		self::O_DEBUG => false,
		self::O_DEBUG_IPS => array(),
		self::O_DEBUG_LEVEL => false,
		self::O_DEBUG_FILESIZE => 0,
		self::O_DEBUG_COLLAPSE_QS => false,
		self::O_DEBUG_INC => array(),
		self::O_DEBUG_EXC => array(),
		self::O_DEBUG_EXC_STRINGS => array(),

		// DB Optm
		self::O_DB_OPTM_REVISIONS_MAX => 0,
		self::O_DB_OPTM_REVISIONS_AGE => 0,

		// HTML Optm
		self::O_OPTM_CSS_MIN => false,
		self::O_OPTM_CSS_COMB => false,
		self::O_OPTM_CSS_COMB_EXT_INL => false,
		self::O_OPTM_UCSS => false,
		self::O_OPTM_UCSS_INLINE => false,
		self::O_OPTM_UCSS_SELECTOR_WHITELIST => array(),
		self::O_OPTM_UCSS_FILE_EXC_INLINE => array(),
		self::O_OPTM_UCSS_EXC => array(),
		self::O_OPTM_CSS_EXC => array(),
		self::O_OPTM_JS_MIN => false,
		self::O_OPTM_JS_COMB => false,
		self::O_OPTM_JS_COMB_EXT_INL => false,
		self::O_OPTM_JS_DELAY_INC => array(),
		self::O_OPTM_JS_EXC => array(),
		self::O_OPTM_HTML_MIN => false,
		self::O_OPTM_HTML_LAZY => array(),
		self::O_OPTM_HTML_SKIP_COMMENTS => array(),
		self::O_OPTM_QS_RM => false,
		self::O_OPTM_GGFONTS_RM => false,
		self::O_OPTM_CSS_ASYNC => false,
		self::O_OPTM_CCSS_PER_URL => false,
		self::O_OPTM_CCSS_SEP_POSTTYPE => array(),
		self::O_OPTM_CCSS_SEP_URI => array(),
		self::O_OPTM_CCSS_SELECTOR_WHITELIST => array(),
		self::O_OPTM_CSS_ASYNC_INLINE => false,
		self::O_OPTM_CSS_FONT_DISPLAY => false,
		self::O_OPTM_JS_DEFER => false,
		self::O_OPTM_EMOJI_RM => false,
		self::O_OPTM_NOSCRIPT_RM => false,
		self::O_OPTM_GGFONTS_ASYNC => false,
		self::O_OPTM_EXC_ROLES => array(),
		self::O_OPTM_CCSS_CON => '',
		self::O_OPTM_JS_DEFER_EXC => array(),
		self::O_OPTM_GM_JS_EXC => array(),
		self::O_OPTM_DNS_PREFETCH => array(),
		self::O_OPTM_DNS_PREFETCH_CTRL => false,
		self::O_OPTM_DNS_PRECONNECT => array(),
		self::O_OPTM_EXC => array(),
		self::O_OPTM_GUEST_ONLY => false,

		// Object
		self::O_OBJECT => false,
		self::O_OBJECT_KIND => false,
		self::O_OBJECT_HOST => '',
		self::O_OBJECT_PORT => 0,
		self::O_OBJECT_LIFE => 0,
		self::O_OBJECT_PERSISTENT => false,
		self::O_OBJECT_ADMIN => false,
		self::O_OBJECT_TRANSIENTS => false,
		self::O_OBJECT_DB_ID => 0,
		self::O_OBJECT_USER => '',
		self::O_OBJECT_PSWD => '',
		self::O_OBJECT_GLOBAL_GROUPS => array(),
		self::O_OBJECT_NON_PERSISTENT_GROUPS => array(),

		// Discuss
		self::O_DISCUSS_AVATAR_CACHE => false,
		self::O_DISCUSS_AVATAR_CRON => false,
		self::O_DISCUSS_AVATAR_CACHE_TTL => 0,
		self::O_OPTM_LOCALIZE => false,
		self::O_OPTM_LOCALIZE_DOMAINS => array(),

		// Media
		self::O_MEDIA_LAZY => false,
		self::O_MEDIA_LAZY_PLACEHOLDER => '',
		self::O_MEDIA_PLACEHOLDER_RESP => false,
		self::O_MEDIA_PLACEHOLDER_RESP_COLOR => '',
		self::O_MEDIA_PLACEHOLDER_RESP_SVG => '',
		self::O_MEDIA_LQIP => false,
		self::O_MEDIA_LQIP_QUAL => 0,
		self::O_MEDIA_LQIP_MIN_W => 0,
		self::O_MEDIA_LQIP_MIN_H => 0,
		self::O_MEDIA_PLACEHOLDER_RESP_ASYNC => false,
		self::O_MEDIA_IFRAME_LAZY => false,
		self::O_MEDIA_ADD_MISSING_SIZES => false,
		self::O_MEDIA_LAZY_EXC => array(),
		self::O_MEDIA_LAZY_CLS_EXC => array(),
		self::O_MEDIA_LAZY_PARENT_CLS_EXC => array(),
		self::O_MEDIA_IFRAME_LAZY_CLS_EXC => array(),
		self::O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC => array(),
		self::O_MEDIA_LAZY_URI_EXC => array(),
		self::O_MEDIA_LQIP_EXC => array(),
		self::O_MEDIA_VPI => false,
		self::O_MEDIA_VPI_CRON => false,

		// Image Optm
		self::O_IMG_OPTM_AUTO => false,
		self::O_IMG_OPTM_ORI => false,
		self::O_IMG_OPTM_RM_BKUP => false,
		self::O_IMG_OPTM_WEBP => false,
		self::O_IMG_OPTM_LOSSLESS => false,
		self::O_IMG_OPTM_EXIF => false,
		self::O_IMG_OPTM_WEBP_ATTR => array(),
		self::O_IMG_OPTM_WEBP_REPLACE_SRCSET => false,
		self::O_IMG_OPTM_JPG_QUALITY => 0,

		// Crawler
		self::O_CRAWLER => false,
		self::O_CRAWLER_CRAWL_INTERVAL => 0,
		self::O_CRAWLER_LOAD_LIMIT => 0,
		self::O_CRAWLER_SITEMAP => '',
		self::O_CRAWLER_ROLES => array(),
		self::O_CRAWLER_COOKIES => array(),

		// Misc
		self::O_MISC_HEARTBEAT_FRONT => false,
		self::O_MISC_HEARTBEAT_FRONT_TTL => 0,
		self::O_MISC_HEARTBEAT_BACK => false,
		self::O_MISC_HEARTBEAT_BACK_TTL => 0,
		self::O_MISC_HEARTBEAT_EDITOR => false,
		self::O_MISC_HEARTBEAT_EDITOR_TTL => 0,

		// CDN
		self::O_CDN => false,
		self::O_CDN_ORI => array(),
		self::O_CDN_ORI_DIR => array(),
		self::O_CDN_EXC => array(),
		self::O_CDN_QUIC => false,
		self::O_CDN_CLOUDFLARE => false,
		self::O_CDN_CLOUDFLARE_EMAIL => '',
		self::O_CDN_CLOUDFLARE_KEY => '',
		self::O_CDN_CLOUDFLARE_NAME => '',
		self::O_CDN_CLOUDFLARE_ZONE => '',
		self::O_CDN_MAPPING => array(),
		self::O_CDN_ATTR => array(),

		self::O_QC_NAMESERVERS => '',
		self::O_QC_CNAME => '',
	);

	protected static $_default_site_options = array(
		self::_VER => '',
		self::O_CACHE => false,
		self::NETWORK_O_USE_PRIMARY => false,
		self::O_AUTO_UPGRADE => false,
		self::O_GUEST => false,

		self::O_CACHE_RES => false,
		self::O_CACHE_BROWSER => false,
		self::O_CACHE_MOBILE => false,
		self::O_CACHE_MOBILE_RULES => array(),
		self::O_CACHE_LOGIN_COOKIE => '',
		self::O_CACHE_VARY_COOKIES => array(),
		self::O_CACHE_EXC_COOKIES => array(),
		self::O_CACHE_EXC_USERAGENTS => array(),
		self::O_CACHE_TTL_BROWSER => 0,

		self::O_PURGE_ON_UPGRADE => false,

		self::O_OBJECT => false,
		self::O_OBJECT_KIND => false,
		self::O_OBJECT_HOST => '',
		self::O_OBJECT_PORT => 0,
		self::O_OBJECT_LIFE => 0,
		self::O_OBJECT_PERSISTENT => false,
		self::O_OBJECT_ADMIN => false,
		self::O_OBJECT_TRANSIENTS => false,
		self::O_OBJECT_DB_ID => 0,
		self::O_OBJECT_USER => '',
		self::O_OBJECT_PSWD => '',
		self::O_OBJECT_GLOBAL_GROUPS => array(),
		self::O_OBJECT_NON_PERSISTENT_GROUPS => array(),

		// Debug
		self::O_DEBUG_DISABLE_ALL => false,
		self::O_DEBUG => false,
		self::O_DEBUG_IPS => array(),
		self::O_DEBUG_LEVEL => false,
		self::O_DEBUG_FILESIZE => 0,
		self::O_DEBUG_COLLAPSE_QS => false,
		self::O_DEBUG_INC => array(),
		self::O_DEBUG_EXC => array(),
		self::O_DEBUG_EXC_STRINGS => array(),

		self::O_IMG_OPTM_WEBP => false,
	);

	// NOTE: all the val of following items will be int while not bool
	protected static $_multi_switch_list = array(
		self::O_DEBUG => 2,
		self::O_OPTM_JS_DEFER => 2,
		self::O_IMG_OPTM_WEBP => 2,
	);

	/**
	 * Correct the option type
	 *
	 * TODO: add similar network func
	 *
	 * @since  3.0.3
	 */
	protected function type_casting($val, $id, $is_site_conf = false)
	{
		$default_v = !$is_site_conf ? self::$_default_options[$id] : self::$_default_site_options[$id];
		if (is_bool($default_v)) {
			if ($val === 'true') {
				$val = true;
			}
			if ($val === 'false') {
				$val = false;
			}

			$max = $this->_conf_multi_switch($id);
			if ($max) {
				$val = (int) $val;
				$val %= $max + 1;
			} else {
				$val = (bool) $val;
			}
		} elseif (is_array($default_v)) {
			// from textarea input
			if (!is_array($val)) {
				$val = Utility::sanitize_lines($val, $this->_conf_filter($id));
			}
		} elseif (!is_string($default_v)) {
			$val = (int) $val;
		} else {
			// Check if the string has a limit set
			$val = $this->_conf_string_val($id, $val);
		}

		return $val;
	}

	/**
	 * Load default network settings from data.ini
	 *
	 * @since  3.0
	 */
	public function load_default_site_vals()
	{
		// Load network_default.json
		if (file_exists(LSCWP_DIR . 'data/const.network_default.json')) {
			$default_ini_cfg = json_decode(File::read(LSCWP_DIR . 'data/const.network_default.json'), true);
			foreach (self::$_default_site_options as $k => $v) {
				if (!array_key_exists($k, $default_ini_cfg)) {
					continue;
				}

				// Parse value in ini file
				$ini_v = $this->type_casting($default_ini_cfg[$k], $k, true);

				if ($ini_v == $v) {
					continue;
				}

				self::$_default_site_options[$k] = $ini_v;
			}
		}

		self::$_default_site_options[self::_VER] = Core::VER;

		return self::$_default_site_options;
	}

	/**
	 * Load default values from default.json
	 *
	 * @since 3.0
	 * @access public
	 */
	public function load_default_vals()
	{
		// Load default.json
		if (file_exists(LSCWP_DIR . 'data/const.default.json')) {
			$default_ini_cfg = json_decode(File::read(LSCWP_DIR . 'data/const.default.json'), true);
			foreach (self::$_default_options as $k => $v) {
				if (!array_key_exists($k, $default_ini_cfg)) {
					continue;
				}

				// Parse value in ini file
				$ini_v = $this->type_casting($default_ini_cfg[$k], $k);

				// NOTE: Multiple lines value must be stored as array
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
				if ($k == self::O_CDN_MAPPING) {
					$mapping_fields = array(
						self::CDN_MAPPING_URL,
						self::CDN_MAPPING_INC_IMG,
						self::CDN_MAPPING_INC_CSS,
						self::CDN_MAPPING_INC_JS,
						self::CDN_MAPPING_FILETYPE, // Array
					);
					$ini_v2 = array();
					foreach ($ini_v[self::CDN_MAPPING_URL] as $k2 => $v2) {
						// $k2 is numeric
						$this_row = array();
						foreach ($mapping_fields as $v3) {
							$this_v = !empty($ini_v[$v3][$k2]) ? $ini_v[$v3][$k2] : false;
							if ($v3 == self::CDN_MAPPING_URL) {
								$this_v = $this_v ?: '';
							}
							if ($v3 == self::CDN_MAPPING_FILETYPE) {
								$this_v = $this_v ? Utility::sanitize_lines($this_v) : array(); // Note: Since v3.0 its already an array
							}
							$this_row[$v3] = $this_v;
						}
						$ini_v2[$k2] = $this_row;
					}
					$ini_v = $ini_v2;
				}

				if ($ini_v == $v) {
					continue;
				}

				self::$_default_options[$k] = $ini_v;
			}
		}

		// Load internal default vals
		// Setting the default bool to int is also to avoid type casting override it back to bool
		self::$_default_options[self::O_CACHE] = is_multisite() ? self::VAL_ON2 : self::VAL_ON; //For multi site, default is 2 (Use Network Admin Settings). For single site, default is 1 (Enabled).

		// Load default vals containing variables
		if (!self::$_default_options[self::O_CDN_ORI_DIR]) {
			self::$_default_options[self::O_CDN_ORI_DIR] = LSCWP_CONTENT_FOLDER . "\nwp-includes";
			self::$_default_options[self::O_CDN_ORI_DIR] = explode("\n", self::$_default_options[self::O_CDN_ORI_DIR]);
			self::$_default_options[self::O_CDN_ORI_DIR] = array_map('trim', self::$_default_options[self::O_CDN_ORI_DIR]);
		}

		// Set security key if not initialized yet
		if (!self::$_default_options[self::HASH]) {
			self::$_default_options[self::HASH] = Str::rrand(32);
		}

		self::$_default_options[self::_VER] = Core::VER;

		return self::$_default_options;
	}

	/**
	 * Format the string value
	 *
	 * @since  3.0
	 */
	protected function _conf_string_val($id, $val)
	{
		return $val;
	}

	/**
	 * If the switch setting is a triple value or not
	 *
	 * @since  3.0
	 */
	protected function _conf_multi_switch($id)
	{
		if (!empty(self::$_multi_switch_list[$id])) {
			return self::$_multi_switch_list[$id];
		}

		if ($id == self::O_CACHE && is_multisite()) {
			return self::VAL_ON2;
		}

		return false;
	}

	/**
	 * Append a new multi switch max limit for the bool option
	 *
	 * @since  3.0
	 */
	public static function set_multi_switch($id, $v)
	{
		self::$_multi_switch_list[$id] = $v;
	}

	/**
	 * Generate const name based on $id
	 *
	 * @since  3.0
	 */
	public static function conf_const($id)
	{
		return 'LITESPEED_CONF__' . strtoupper(str_replace('-', '__', $id));
	}

	/**
	 * Filter to be used when saving setting
	 *
	 * @since  3.0
	 */
	protected function _conf_filter($id)
	{
		$filters = array(
			self::O_MEDIA_LAZY_EXC => 'uri',
			self::O_DEBUG_INC => 'relative',
			self::O_DEBUG_EXC => 'relative',
			self::O_MEDIA_LAZY_URI_EXC => 'relative',
			self::O_CACHE_PRIV_URI => 'relative',
			self::O_PURGE_TIMED_URLS => 'relative',
			self::O_CACHE_FORCE_URI => 'relative',
			self::O_CACHE_FORCE_PUB_URI => 'relative',
			self::O_CACHE_EXC => 'relative',
			// self::O_OPTM_CSS_EXC		=> 'uri', // Need to comment out for inline & external CSS
			// self::O_OPTM_JS_EXC			=> 'uri',
			self::O_OPTM_EXC => 'relative',
			self::O_OPTM_CCSS_SEP_URI => 'uri',
			// self::O_OPTM_JS_DEFER_EXC	=> 'uri',
			self::O_OPTM_DNS_PREFETCH => 'domain',
			self::O_CDN_ORI => 'noprotocol,trailingslash', // `Original URLs`
			// self::O_OPTM_LOCALIZE_DOMAINS	=> 'noprotocol', // `Localize Resources`
			// self::	=> '',
			// self::	=> '',
		);

		if (!empty($filters[$id])) {
			return $filters[$id];
		}

		return false;
	}

	/**
	 * If the setting changes worth a purge or not
	 *
	 * @since  3.0
	 */
	protected function _conf_purge($id)
	{
		$check_ids = array(
			self::O_MEDIA_LAZY_URI_EXC,
			self::O_OPTM_EXC,
			self::O_CACHE_PRIV_URI,
			self::O_PURGE_TIMED_URLS,
			self::O_CACHE_FORCE_URI,
			self::O_CACHE_FORCE_PUB_URI,
			self::O_CACHE_EXC,
		);

		return in_array($id, $check_ids);
	}

	/**
	 * If the setting changes worth a purge ALL or not
	 *
	 * @since  3.0
	 */
	protected function _conf_purge_all($id)
	{
		$check_ids = array(self::O_CACHE, self::O_ESI, self::O_DEBUG_DISABLE_ALL, self::NETWORK_O_USE_PRIMARY);

		return in_array($id, $check_ids);
	}

	/**
	 * If the setting is a pswd or not
	 *
	 * @since  3.0
	 */
	protected function _conf_pswd($id)
	{
		$check_ids = array(self::O_CDN_CLOUDFLARE_KEY, self::O_OBJECT_PSWD);

		return in_array($id, $check_ids);
	}

	/**
	 * If the setting is cron related or not
	 *
	 * @since  3.0
	 */
	protected function _conf_cron($id)
	{
		$check_ids = array(self::O_OPTM_CSS_ASYNC, self::O_MEDIA_PLACEHOLDER_RESP_ASYNC, self::O_DISCUSS_AVATAR_CRON, self::O_IMG_OPTM_AUTO, self::O_CRAWLER);

		return in_array($id, $check_ids);
	}

	/**
	 * If the setting changes worth a purge, return the tag
	 *
	 * @since  3.0
	 */
	protected function _conf_purge_tag($id)
	{
		$check_ids = array(
			self::O_CACHE_PAGE_LOGIN => Tag::TYPE_LOGIN,
		);

		if (!empty($check_ids[$id])) {
			return $check_ids[$id];
		}

		return false;
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
			'LSWCP_TAG_PREFIX',
			'COOKIEHASH',
		);
		$server_vars = array();
		foreach ($consts as $v) {
			$server_vars[$v] = defined($v) ? constant($v) : null;
		}

		return $server_vars;
	}
}
