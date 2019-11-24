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
	const OPTION_NAME = 'litespeed-cache-conf' ;
	const VARY_GROUP = 'litespeed-cache-vary-group' ;
	const EXCLUDE_OPTIMIZATION_ROLES = 'litespeed-cache-exclude-optimization-roles' ;
	const EXCLUDE_CACHE_ROLES = 'litespeed-cache-exclude-cache-roles' ;
	const ITEM_OPTM_CSS = 'litespeed-optm-css' ;// separate critical css that should be stored in option table
	const ITEM_OPTM_JS_DEFER_EXC = 'litespeed-optm-js-defer-excludes' ;
	const ITEM_MEDIA_LAZY_IMG_EXC = 'litespeed-media-lazy-img-excludes' ;
	const ITEM_MEDIA_LAZY_IMG_CLS_EXC = 'litespeed-media-lazy-img-cls-excludes' ;
	const ITEM_IMG_OPTM_NEED_PULL = 'litespeed-media-need-pull' ;
	const ITEM_ENV_REF = 'litespeed-env-ref' ;
	const ITEM_CACHE_DROP_QS = 'litespeed-cache-drop_qs' ;
	const ITEM_CDN_MAPPING = 'litespeed-cache-cdn_mapping' ;
	const ITEM_DNS_PREFETCH = 'litespeed-cache-dns_prefetch' ;
	const ITEM_CLOUDFLARE_STATUS = 'litespeed-cache-cloudflare_status' ;
	const ITEM_LOG_IGNORE_FILTERS = 'litespeed-log_ignore_filters' ;
	const ITEM_LOG_IGNORE_PART_FILTERS = 'litespeed-log_ignore_part_filters' ;
	const ITEM_OBJECT_GLOBAL_GROUPS = 'litespeed-object_global_groups' ;
	const ITEM_OBJECT_NON_PERSISTENT_GROUPS = 'litespeed-object_non_persistent_groups' ;
	const ITEM_CRWL_AS_UIDS = 'litespeed-crawler-as-uids' ;
	const ITEM_CRWL_COOKIES = 'litespeed-crawler-cookies' ;
	const ITEM_ADV_PURGE_ALL_HOOKS = 'litespeed-adv-purge_all_hooks' ;
	const ITEM_CDN_ORI_DIR = 'litespeed-cdn-ori_dir' ;
	const ITEM_MEDIA_WEBP_ATTRIBUTE = 'litespeed-media-webp_attribute' ;
	const ITEM_FORCE_CACHE_URI = 'litespeed-forced_cache_uri' ;
	const ITEM_CACHE_URI_PRIV = 'litespeed-cache_uri_priv' ;
	const ITEM_OPTM_EXCLUDES = 'litespeed-optm_excludes' ;
	const ITEM_EXCLUDES_URI = 'litespeed-excludes_uri' ;
	const ITEM_OPTM_CCSS_SEPARATE_POSTTYPE = 'litespeed-optm-ccss-separate_posttype' ;
	const ITEM_OPTM_CCSS_SEPARATE_URI = 'litespeed-optm-css-separate_uri' ;

	const ITEM_SETTING_MODE = 'litespeed-setting-mode' ;
	const ITEM_CRAWLER_HASH = 'litespeed-crawler-hash' ;
	const ITEM_GUIDE = 'litespeed-guide' ; // Array of each guidance tag as key, step as val
	const ITEM_TIMESTAMP_PURGE_CSS = 'litespeed-timestamp-purge-css' ;

	// Server variables
	const ENV_CRAWLER_USLEEP = 'CRAWLER_USLEEP' ;
	const ENV_CRAWLER_LOAD_LIMIT = 'CRAWLER_LOAD_LIMIT' ;
	const ENV_CRAWLER_LOAD_LIMIT_ENFORCE = 'CRAWLER_LOAD_LIMIT_ENFORCE' ;

	// const ITEM_FAVICON = 'litespeed-cache-favicon' ;

	const ITEM_CDN_MAPPING_URL = 'url' ;
	const ITEM_CDN_MAPPING_INC_IMG = 'inc_img' ;
	const ITEM_CDN_MAPPING_INC_CSS = 'inc_css' ;
	const ITEM_CDN_MAPPING_INC_JS = 'inc_js' ;
	const ITEM_CDN_MAPPING_FILETYPE = 'filetype' ;

	const VAL_OFF = 0 ;
	const VAL_ON = 1 ;
	const VAL_ON2 = 2 ;

	const LOG_LEVEL_NONE = 0 ;
	const LOG_LEVEL_ERROR = 1 ;
	const LOG_LEVEL_NOTICE = 2 ;
	const LOG_LEVEL_INFO = 3 ;
	const LOG_LEVEL_DEBUG = 4 ;
	const OPID_VERSION = 'version' ;
	const OPID_ENABLED_RADIO = 'radio_select' ;

	const OPT_AUTO_UPGRADE = 'auto_upgrade' ;
	const OPT_NEWS = 'news' ;
	const OPID_CACHE_PRIV = 'cache_priv' ;
	const OPID_CACHE_COMMENTER = 'cache_commenter' ;
	const OPID_CACHE_REST = 'cache_rest' ;
	const OPID_CACHE_PAGE_LOGIN = 'cache_page_login' ;
	const OPID_CACHE_FAVICON = 'cache_favicon' ;
	const OPID_CACHE_RES = 'cache_resources' ;
	const OPID_CACHE_MOBILE = 'mobileview_enabled' ;
	const ID_MOBILEVIEW_LIST = 'mobileview_rules' ;
	const OPID_CACHE_OBJECT = 'cache_object' ;
	const OPID_CACHE_OBJECT_KIND = 'cache_object_kind' ;
	const OPID_CACHE_OBJECT_HOST = 'cache_object_host' ;
	const OPID_CACHE_OBJECT_PORT = 'cache_object_port' ;
	const OPID_CACHE_OBJECT_LIFE = 'cache_object_life' ;
	const OPID_CACHE_OBJECT_PERSISTENT = 'cache_object_persistent' ;
	const OPID_CACHE_OBJECT_ADMIN = 'cache_object_admin' ;
	const OPID_CACHE_OBJECT_TRANSIENTS = 'cache_object_transients' ;
	const OPID_CACHE_OBJECT_DB_ID = 'cache_object_db_id' ;
	const OPID_CACHE_OBJECT_USER = 'cache_object_user' ;
	const OPID_CACHE_OBJECT_PSWD = 'cache_object_pswd' ;
	const OPID_CACHE_BROWSER = 'cache_browser' ;
	const OPID_CACHE_BROWSER_TTL = 'cache_browser_ttl' ;

	const OPID_PURGE_ON_UPGRADE = 'purge_upgrade' ;
	const OPID_TIMED_URLS = 'timed_urls' ;
	const OPID_TIMED_URLS_TIME = 'timed_urls_time' ;

	const OPID_LOGIN_COOKIE = 'login_cookie' ;
	const OPID_CHECK_ADVANCEDCACHE = 'check_advancedcache' ;
	const OPID_USE_HTTP_FOR_HTTPS_VARY = 'use_http_for_https_vary' ;
	// do NOT set default options for these three, it is used for admin.
	const ID_NOCACHE_COOKIES = 'nocache_cookies' ;
	const ID_NOCACHE_USERAGENTS = 'nocache_useragents' ;
	const OPID_DEBUG_DISABLE_ALL = 'debug_disable_all' ;
	const OPID_DEBUG = 'debug' ;
	const OPID_ADMIN_IPS = 'admin_ips' ;
	const OPID_DEBUG_LEVEL = 'debug_level' ;
	const OPID_LOG_FILE_SIZE = 'log_file_size' ;
	const OPID_HEARTBEAT = 'heartbeat' ;
	const OPID_DEBUG_COOKIE = 'debug_cookie' ;
	const OPID_COLLAPS_QS = 'collaps_qs' ;
	const OPID_LOG_FILTERS = 'log_filters' ;

	const OPID_PUBLIC_TTL = 'public_ttl' ;
	const OPID_PRIVATE_TTL = 'private_ttl' ;
	const OPID_FRONT_PAGE_TTL = 'front_page_ttl' ;
	const OPID_FEED_TTL = 'feed_ttl' ;
	const OPID_403_TTL = '403_ttl' ;
	const OPID_404_TTL = '404_ttl' ;
	const OPID_500_TTL = '500_ttl' ;
	const OPID_PURGE_BY_POST = 'purge_by_post' ;
	const OPID_ESI_ENABLE = 'esi_enabled' ;
	const OPID_ESI_CACHE_ADMBAR = 'esi_cached_admbar' ;
	const OPID_ESI_CACHE_COMMFORM = 'esi_cached_commform' ;
	const PURGE_ALL_PAGES = '-' ;
	const PURGE_FRONT_PAGE = 'F' ;
	const PURGE_HOME_PAGE = 'H' ;
	const PURGE_PAGES = 'PGS' ;
	const PURGE_PAGES_WITH_RECENT_POSTS = 'PGSRP' ;
	const PURGE_AUTHOR = 'A' ;
	const PURGE_YEAR = 'Y' ;
	const PURGE_MONTH = 'M' ;
	const PURGE_DATE = 'D' ;
	const PURGE_TERM = 'T' ; // include category|tag|tax
	const PURGE_POST_TYPE = 'PT' ;

	const OPID_EXCLUDES_QS = 'excludes_qs' ;
	const OPID_EXCLUDES_CAT = 'excludes_cat' ;
	const OPID_EXCLUDES_TAG = 'excludes_tag' ;

	// const OPID_ADV_FAVICON = 'adv_favicon' ;
	const OPID_ADV_INSTANT_CLICK = 'instant_click' ;

	const OPID_CSS_MINIFY = 'css_minify' ;
	const OPID_CSS_INLINE_MINIFY = 'css_inline_minify' ;
	const OPID_CSS_COMBINE = 'css_combine' ;
	const OPID_CSS_COMBINED_PRIORITY = 'css_combined_priority' ;
	const OPID_CSS_HTTP2 = 'css_http2' ;
	const OPID_CSS_EXCLUDES = 'css_exclude' ;
	const OPID_JS_MINIFY = 'js_minify' ;
	const OPID_JS_INLINE_MINIFY = 'js_inline_minify' ;
	const OPID_JS_COMBINE = 'js_combine' ;
	const OPID_JS_COMBINED_PRIORITY = 'js_combined_priority' ;
	const OPID_JS_HTTP2 = 'js_http2' ;
	const OPID_JS_EXCLUDES = 'js_exclude' ;
	const OPID_OPTIMIZE_TTL = 'optimize_ttl' ;
	const OPID_HTML_MINIFY = 'html_minify' ;
	const OPID_OPTM_QS_RM = 'optm_qs_rm' ;
	const OPID_OPTM_GGFONTS_RM = 'optm_ggfonts_rm' ;
	const OPID_OPTM_CSS_ASYNC = 'optm_css_async' ;
	const OPT_OPTM_CCSS_GEN = 'optm_ccss_gen' ;
	const OPT_OPTM_CCSS_ASYNC = 'optm_ccss_async' ;
	const OPT_OPTM_CSS_ASYNC_INLINE = 'optm_css_async_inline' ;
	const OPID_OPTM_JS_DEFER = 'optm_js_defer' ;
	const OPID_OPTM_EMOJI_RM = 'optm_emoji_rm' ;
	const OPID_OPTM_EXC_JQUERY = 'optm_exclude_jquery' ;
	const OPID_OPTM_GGFONTS_ASYNC = 'optm_ggfonts_async' ;
	const OPID_OPTM_MAX_SIZE = 'optm_max_size' ;
	const OPID_OPTM_RM_COMMENT = 'optm_rm_comment' ;

	const OPID_CDN = 'cdn' ;
	const OPID_CDN_ORI = 'cdn_ori' ;
	const OPID_CDN_EXCLUDE = 'cdn_exclude' ;
	const OPID_CDN_REMOTE_JQUERY = 'cdn_remote_jquery' ;
	const OPT_CDN_QUIC = 'cdn_quic' ;
	const OPT_CDN_QUIC_EMAIL = 'cdn_quic_email' ;
	const OPT_CDN_QUIC_KEY = 'cdn_quic_key' ;
	const OPID_CDN_CLOUDFLARE = 'cdn_cloudflare' ;
	const OPID_CDN_CLOUDFLARE_EMAIL = 'cdn_cloudflare_email' ;
	const OPID_CDN_CLOUDFLARE_KEY = 'cdn_cloudflare_key' ;
	const OPID_CDN_CLOUDFLARE_NAME = 'cdn_cloudflare_name' ;
	const OPID_CDN_CLOUDFLARE_ZONE = 'cdn_cloudflare_zone' ;

	const OPID_MEDIA_IMG_LAZY = 'media_img_lazy' ;
	const OPID_MEDIA_IMG_LAZY_PLACEHOLDER = 'media_img_lazy_placeholder' ;
	const OPID_MEDIA_PLACEHOLDER_RESP = 'media_placeholder_resp' ;
	const OPID_MEDIA_PLACEHOLDER_RESP_COLOR = 'media_placeholder_resp_color' ;
	const OPID_MEDIA_PLACEHOLDER_RESP_ASYNC = 'media_placeholder_resp_async' ;
	const OPID_MEDIA_IFRAME_LAZY = 'media_iframe_lazy' ;
	const OPID_MEDIA_IMG_LAZYJS_INLINE = 'media_img_lazyjs_inline' ;
	const OPT_MEDIA_OPTM_AUTO = 'media_optm_auto' ;
	const OPT_MEDIA_OPTM_CRON = 'media_optm_cron' ;
	const OPT_MEDIA_OPTM_ORI = 'media_optm_ori' ;
	const OPT_MEDIA_RM_ORI_BKUP = 'media_rm_ori_bkup' ;
	const OPT_MEDIA_OPTM_WEBP = 'media_optm_webp' ;
	const OPT_MEDIA_OPTM_LOSSLESS = 'media_optm_lossless' ;
	const OPT_MEDIA_OPTM_EXIF = 'media_optm_exif' ;
	const OPT_MEDIA_WEBP_REPLACE = 'media_webp_replace' ;
	const OPT_MEDIA_WEBP_REPLACE_SRCSET = 'media_webp_replace_srcset' ;

	const HASH = 'hash' ;

	const NETWORK_OPID_ENABLED = 'network_enabled' ;
	const NETWORK_OPID_USE_PRIMARY = 'use_primary_settings' ;

	const CRWL_POSTS = 'crawler_include_posts' ;
	const CRWL_PAGES = 'crawler_include_pages' ;
	const CRWL_CATS = 'crawler_include_cats' ;
	const CRWL_TAGS = 'crawler_include_tags' ;
	const CRWL_EXCLUDES_CPT = 'crawler_excludes_cpt' ;
	const CRWL_ORDER_LINKS = 'crawler_order_links' ;
	const CRWL_USLEEP = 'crawler_usleep' ;
	const CRWL_RUN_DURATION = 'crawler_run_duration' ;
	const CRWL_RUN_INTERVAL = 'crawler_run_interval' ;
	const CRWL_CRAWL_INTERVAL = 'crawler_crawl_interval' ;
	const CRWL_THREADS = 'crawler_threads' ;
	const CRWL_LOAD_LIMIT = 'crawler_load_limit' ;
	const CRWL_DOMAIN_IP = 'crawler_domain_ip' ;
	const CRWL_CUSTOM_SITEMAP = 'crawler_custom_sitemap' ;

	const CRWL_CRON_ACTIVE = 'crawler_cron_active' ;

	const CRWL_DATE_DESC = 'date_desc' ;
	const CRWL_DATE_ASC = 'date_asc' ;
	const CRWL_ALPHA_DESC = 'alpha_desc' ;
	const CRWL_ALPHA_ASC = 'alpha_asc' ;

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
			self::VARY_GROUP,
			self::EXCLUDE_OPTIMIZATION_ROLES,
			self::EXCLUDE_CACHE_ROLES,
			self::ITEM_OPTM_CSS,
			self::ITEM_OPTM_JS_DEFER_EXC,
			self::ITEM_MEDIA_LAZY_IMG_EXC,
			self::ITEM_MEDIA_LAZY_IMG_CLS_EXC,
			self::ITEM_IMG_OPTM_NEED_PULL,
			self::ITEM_ENV_REF,
			self::ITEM_CACHE_DROP_QS,
			self::ITEM_CDN_MAPPING,
			self::ITEM_CDN_ORI_DIR,
			self::ITEM_DNS_PREFETCH,
			self::ITEM_CLOUDFLARE_STATUS,
			self::ITEM_LOG_IGNORE_FILTERS,
			self::ITEM_LOG_IGNORE_PART_FILTERS,
			self::ITEM_OBJECT_GLOBAL_GROUPS,
			self::ITEM_OBJECT_NON_PERSISTENT_GROUPS,
			self::ITEM_CRWL_AS_UIDS,
			self::ITEM_CRWL_COOKIES,
			self::ITEM_ADV_PURGE_ALL_HOOKS,
			self::ITEM_FORCE_CACHE_URI,
			self::ITEM_CACHE_URI_PRIV,
			self::ITEM_OPTM_EXCLUDES,
			self::ITEM_EXCLUDES_URI,
			self::ITEM_MEDIA_WEBP_ATTRIBUTE,
			self::ITEM_OPTM_CCSS_SEPARATE_POSTTYPE,
			self::ITEM_OPTM_CCSS_SEPARATE_URI,
		) ;
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
				if ( $item == self::ITEM_CDN_MAPPING ) {
					$mapping_fields = array(
						self::ITEM_CDN_MAPPING_URL,
						self::ITEM_CDN_MAPPING_INC_IMG,
						self::ITEM_CDN_MAPPING_INC_CSS,
						self::ITEM_CDN_MAPPING_INC_JS,
						self::ITEM_CDN_MAPPING_FILETYPE
					) ;
					$cdn_mapping = array() ;
					foreach ( $default_ini_cfg[ $item ][ self::ITEM_CDN_MAPPING_URL ] as $k => $v ) {// $k is numeric
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

		switch ( $item ) {
			case self::ITEM_OBJECT_GLOBAL_GROUPS :
				return "users\nuserlogins\nusermeta\nuser_meta\nsite-transient\nsite-options\nsite-lookup\nblog-lookup\nblog-details\nrss\nglobal-posts\nblog-id-cache" ;

			case self::ITEM_OBJECT_NON_PERSISTENT_GROUPS :
				return "comment\ncounts\nplugins\nwc_session_id" ;

			case self::ITEM_ADV_PURGE_ALL_HOOKS :
				return "switch_theme\nwp_create_nav_menu\nwp_update_nav_menu\nwp_delete_nav_menu\ncreate_term\nedit_terms\ndelete_term\nadd_link\nedit_link\ndelete_link" ;

			case self::ITEM_CDN_ORI_DIR :
				return LSCWP_CONTENT_FOLDER . "\nwp-includes\n/min/" ;

			case self::ITEM_MEDIA_WEBP_ATTRIBUTE :
				return "img.src\n" .
						"div.data-thumb\n" .
						"img.data-src\n" .
						"div.data-large_image\n" .
						"img.retina_logo_url" ;

			case self::ITEM_LOG_IGNORE_FILTERS :
				return "gettext\ngettext_with_context\nget_the_terms\nget_term" ;

			case self::ITEM_LOG_IGNORE_PART_FILTERS :
				return "i18n\nlocale\nsettings\noption" ;

			default :
				break ;
		}

		return false ;
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
			self::OPID_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::NETWORK_OPID_ENABLED => false,
			self::NETWORK_OPID_USE_PRIMARY => false,
			self::OPT_AUTO_UPGRADE => false,
			self::OPID_PURGE_ON_UPGRADE => true,
			self::OPID_CACHE_FAVICON => true,
			self::OPID_CACHE_RES => true,
			self::OPID_CACHE_MOBILE => 0, // todo: why not false
			self::ID_MOBILEVIEW_LIST => 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi',
			self::OPID_CACHE_OBJECT => false,
			self::OPID_CACHE_OBJECT_KIND => false,
			self::OPID_CACHE_OBJECT_HOST => 'localhost',
			self::OPID_CACHE_OBJECT_PORT => '11211',
			self::OPID_CACHE_OBJECT_LIFE => '360',
			self::OPID_CACHE_OBJECT_PERSISTENT => true,
			self::OPID_CACHE_OBJECT_ADMIN => true,
			self::OPID_CACHE_OBJECT_TRANSIENTS => true,
			self::OPID_CACHE_OBJECT_DB_ID => 0,
			self::OPID_CACHE_OBJECT_USER => '',
			self::OPID_CACHE_OBJECT_PSWD => '',
			self::OPID_CACHE_BROWSER => false,
			self::OPID_CACHE_BROWSER_TTL => 2592000,
			self::OPID_LOGIN_COOKIE => '',
			self::OPID_CHECK_ADVANCEDCACHE => true,
			self::ID_NOCACHE_COOKIES => '',
			self::ID_NOCACHE_USERAGENTS => '',
			self::OPT_MEDIA_WEBP_REPLACE => false,
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

		//For multi site, default is 2 (Use Network Admin Settings). For single site, default is 1 (Enabled).
		if ( is_multisite() ) {
			$default_radio = 2 ;
		}
		else {
			$default_radio = 1 ;
		}

		$default_options = array(
			self::OPID_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::OPID_ENABLED_RADIO => $default_radio,
			self::OPT_AUTO_UPGRADE => false,
			self::OPT_NEWS => false,
			self::OPID_PURGE_ON_UPGRADE => true,
			self::OPID_CACHE_PRIV => true,
			self::OPID_CACHE_COMMENTER => true,
			self::OPID_CACHE_REST => true,
			self::OPID_CACHE_PAGE_LOGIN => true,
			self::OPID_TIMED_URLS => '',
			self::OPID_TIMED_URLS_TIME => '',
			self::OPID_CACHE_FAVICON => true,
			self::OPID_CACHE_RES => true,
			self::OPID_CACHE_MOBILE => false,
			self::ID_MOBILEVIEW_LIST => 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi',
			self::OPID_CACHE_OBJECT => false,
			self::OPID_CACHE_OBJECT_KIND => false,
			self::OPID_CACHE_OBJECT_HOST => 'localhost',
			self::OPID_CACHE_OBJECT_PORT => '11211',
			self::OPID_CACHE_OBJECT_LIFE => '360',
			self::OPID_CACHE_OBJECT_PERSISTENT => true,
			self::OPID_CACHE_OBJECT_ADMIN => true,
			self::OPID_CACHE_OBJECT_TRANSIENTS => true,
			self::OPID_CACHE_OBJECT_DB_ID => 0,
			self::OPID_CACHE_OBJECT_USER => '',
			self::OPID_CACHE_OBJECT_PSWD => '',
			self::OPID_CACHE_BROWSER => false,
			self::OPID_CACHE_BROWSER_TTL => 2592000,

			self::OPID_LOGIN_COOKIE => '',
			self::OPID_CHECK_ADVANCEDCACHE => true,
			self::OPID_USE_HTTP_FOR_HTTPS_VARY => false,
			self::OPID_DEBUG_DISABLE_ALL => false,
			self::OPID_DEBUG => self::LOG_LEVEL_NONE,
			self::OPID_ADMIN_IPS => '127.0.0.1',
			self::OPID_DEBUG_LEVEL => false,
			self::OPID_LOG_FILE_SIZE => 3,
			self::OPID_HEARTBEAT => true,
			self::OPID_DEBUG_COOKIE => false,
			self::OPID_COLLAPS_QS => false,
			self::OPID_LOG_FILTERS => false,
			self::OPID_PUBLIC_TTL => 604800,
			self::OPID_PRIVATE_TTL => 1800,
			self::OPID_FRONT_PAGE_TTL => 604800,
			self::OPID_FEED_TTL => 1,
			self::OPID_403_TTL => 3600,
			self::OPID_404_TTL => 3600,
			self::OPID_500_TTL => 3600,
			self::OPID_PURGE_BY_POST => implode('.', $default_purge_options),
			self::OPID_EXCLUDES_QS => '',
			self::OPID_EXCLUDES_CAT => '',
			self::OPID_EXCLUDES_TAG => '',

			// self::OPID_ADV_FAVICON 	=> false,
			self::OPID_ADV_INSTANT_CLICK 	=> false,

			self::OPID_CSS_MINIFY 	=> false,
			self::OPID_CSS_INLINE_MINIFY 	=> false,
			self::OPID_CSS_COMBINE 	=> false,
			self::OPID_CSS_COMBINED_PRIORITY 	=> false,
			self::OPID_CSS_HTTP2 	=> false,
			self::OPID_CSS_EXCLUDES => '',
			self::OPID_JS_MINIFY 	=> false,
			self::OPID_JS_INLINE_MINIFY 	=> false,
			self::OPID_JS_COMBINE 	=> false,
			self::OPID_JS_COMBINED_PRIORITY 	=> false,
			self::OPID_JS_HTTP2 	=> false,
			self::OPID_JS_EXCLUDES 	=> '',
			self::OPID_OPTIMIZE_TTL => 604800,
			self::OPID_HTML_MINIFY 	=> false,
			self::OPID_OPTM_QS_RM 	=> false,
			self::OPID_OPTM_GGFONTS_RM => false,
			self::OPID_OPTM_CSS_ASYNC => false,
			self::OPT_OPTM_CCSS_GEN => true,
			self::OPT_OPTM_CCSS_ASYNC => true,
			self::OPT_OPTM_CSS_ASYNC_INLINE => true,
			self::OPID_OPTM_JS_DEFER => false,
			self::OPID_OPTM_EMOJI_RM => false,
			self::OPID_OPTM_EXC_JQUERY => true,
			self::OPID_OPTM_GGFONTS_ASYNC => false,
			self::OPID_OPTM_MAX_SIZE => 1.2,
			self::OPID_OPTM_RM_COMMENT => false,

			self::OPID_CDN 			=> false,
			self::OPID_CDN_ORI 		=> '',
			self::OPID_CDN_EXCLUDE 	=> '',
			self::OPID_CDN_REMOTE_JQUERY 	=> false,
			self::OPT_CDN_QUIC 		=> false,
			self::OPT_CDN_QUIC_EMAIL 	=> '',
			self::OPT_CDN_QUIC_KEY 		=> '',
			self::OPID_CDN_CLOUDFLARE 	=> false,
			self::OPID_CDN_CLOUDFLARE_EMAIL 	=> '',
			self::OPID_CDN_CLOUDFLARE_KEY 	=> '',
			self::OPID_CDN_CLOUDFLARE_NAME 	=> '',
			self::OPID_CDN_CLOUDFLARE_ZONE 	=> '',

			self::OPID_MEDIA_IMG_LAZY 				=> false,
			self::OPID_MEDIA_IMG_LAZY_PLACEHOLDER 	=> '',
			self::OPID_MEDIA_PLACEHOLDER_RESP		=> false,
			self::OPID_MEDIA_PLACEHOLDER_RESP_COLOR		=> '#cfd4db',
			self::OPID_MEDIA_PLACEHOLDER_RESP_ASYNC	=> true,
			self::OPID_MEDIA_IFRAME_LAZY 			=> false,
			self::OPID_MEDIA_IMG_LAZYJS_INLINE 		=> false,
			self::OPT_MEDIA_OPTM_AUTO 		=> false,
			self::OPT_MEDIA_OPTM_CRON 		=> true,
			self::OPT_MEDIA_OPTM_ORI 		=> true,
			self::OPT_MEDIA_RM_ORI_BKUP 	=> false,
			self::OPT_MEDIA_OPTM_WEBP 		=> false,
			self::OPT_MEDIA_OPTM_LOSSLESS 	=> false,
			self::OPT_MEDIA_OPTM_EXIF 		=> false,
			self::OPT_MEDIA_WEBP_REPLACE 	=> false,
			self::OPT_MEDIA_WEBP_REPLACE_SRCSET 	=> false,

			self::HASH 	=> '',

			self::ID_NOCACHE_COOKIES => '',
			self::ID_NOCACHE_USERAGENTS => '',
			self::CRWL_POSTS => true,
			self::CRWL_PAGES => true,
			self::CRWL_CATS => true,
			self::CRWL_TAGS => true,
			self::CRWL_EXCLUDES_CPT => '',
			self::CRWL_ORDER_LINKS => self::CRWL_DATE_DESC,
			self::CRWL_USLEEP => 500,
			self::CRWL_RUN_DURATION => 400,
			self::CRWL_RUN_INTERVAL => 600,
			self::CRWL_CRAWL_INTERVAL => 302400,
			self::CRWL_THREADS => 3,
			self::CRWL_LOAD_LIMIT => 1,
			self::CRWL_DOMAIN_IP => '',
			self::CRWL_CUSTOM_SITEMAP => '',
			self::CRWL_CRON_ACTIVE => false,
		) ;

		// if ( LSWCP_ESI_SUPPORT ) {
			$default_options[self::OPID_ESI_ENABLE] = false ;
			$default_options[self::OPID_ESI_CACHE_ADMBAR] = true ;
			$default_options[self::OPID_ESI_CACHE_COMMFORM] = true ;
		// }

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