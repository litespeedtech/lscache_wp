<?php
/**
 * The core plugin config class.
 *
 * This maintains all the options and settings for this plugin.
 *
 * @since      	1.0.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Config
{
	private static $_instance ;

	const OPTION_NAME = 'litespeed-cache-conf' ;
	const VARY_GROUP = 'litespeed-cache-vary-group' ;
	const EXCLUDE_OPTIMIZATION_ROLES = 'litespeed-cache-exclude-optimization-roles' ;
	const EXCLUDE_CACHE_ROLES = 'litespeed-cache-exclude-cache-roles' ;
	const ITEM_OPTM_CSS = 'litespeed-optm-css' ;// separate critical css that should be stored in option table
	const ITEM_OPTM_JS_DEFER_EXC = 'litespeed-optm-js-defer-excludes' ;
	const ITEM_MEDIA_LAZY_IMG_EXC = 'litespeed-media-lazy-img-excludes' ;
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

	const ITEM_SETTING_MODE = 'litespeed-setting-mode' ;
	const ITEM_CRAWLER_HASH = 'litespeed-crawler-hash' ;
	const ITEM_GUIDE = 'litespeed-guide' ; // Array of each guidance tag as key, step as val

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

	const OPID_CACHE_PRIV = 'cache_priv' ;
	const OPID_CACHE_COMMENTER = 'cache_commenter' ;
	const OPID_CACHE_REST = 'cache_rest' ;
	const OPID_CACHE_PAGE_LOGIN = 'cache_page_login' ;
	const OPID_CACHE_FAVICON = 'cache_favicon' ;
	const OPID_CACHE_RES = 'cache_resources' ;
	const OPID_CACHE_MOBILE = 'mobileview_enabled' ;
	const ID_MOBILEVIEW_LIST = 'mobileview_rules' ;
	const OPID_CACHE_URI_PRIV = 'cache_uri_priv' ;
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
	const OPID_EXCLUDES_URI = 'excludes_uri' ;
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
	const OPID_OPTM_JS_DEFER = 'optm_js_defer' ;
	const OPID_OPTM_EMOJI_RM = 'optm_emoji_rm' ;
	const OPID_OPTM_EXCLUDES = 'optm_excludes' ;
	const OPID_OPTM_EXC_JQUERY = 'optm_exclude_jquery' ;
	const OPID_OPTM_GGFONTS_ASYNC = 'optm_ggfonts_async' ;
	const OPID_OPTM_MAX_SIZE = 'optm_max_size' ;
	const OPID_OPTM_RM_COMMENT = 'optm_rm_comment' ;

	const OPID_CDN = 'cdn' ;
	const OPID_CDN_ORI = 'cdn_ori' ;
	const OPID_CDN_EXCLUDE = 'cdn_exclude' ;
	const OPID_CDN_REMOTE_JQUERY = 'cdn_remote_jquery' ;
	const OPID_CDN_QUIC = 'cdn_quic' ;
	const OPID_CDN_QUIC_EMAIL = 'cdn_quic_email' ;
	const OPID_CDN_QUIC_KEY = 'cdn_quic_key' ;
	const OPID_CDN_QUIC_SITE = 'cdn_quic_site' ;
	const OPID_CDN_CLOUDFLARE = 'cdn_cloudflare' ;
	const OPID_CDN_CLOUDFLARE_EMAIL = 'cdn_cloudflare_email' ;
	const OPID_CDN_CLOUDFLARE_KEY = 'cdn_cloudflare_key' ;
	const OPID_CDN_CLOUDFLARE_NAME = 'cdn_cloudflare_name' ;
	const OPID_CDN_CLOUDFLARE_ZONE = 'cdn_cloudflare_zone' ;

	const OPID_MEDIA_IMG_LAZY = 'media_img_lazy' ;
	const OPID_MEDIA_IMG_LAZY_PLACEHOLDER = 'media_img_lazy_placeholder' ;
	const OPID_MEDIA_IFRAME_LAZY = 'media_iframe_lazy' ;
	const OPID_MEDIA_IMG_OPTM_CRON_OFF = 'media_img_optm_cron_off' ;
	const OPID_MEDIA_IMG_WEBP = 'media_img_webp' ;
	const OPID_MEDIA_IMG_WEBP_ONLY = 'media_img_webp_only' ;
	const OPID_MEDIA_IMG_EXIF = 'media_img_exif' ;
	const OPID_MEDIA_IMG_WEBP_LOSSLESS = 'media_img_webp_lossless' ;

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
	const CRWL_HTTP2 = 'crawler_http2' ;

	const CRWL_CRON_ACTIVE = 'crawler_cron_active' ;

	const CRWL_DATE_DESC = 'date_desc' ;
	const CRWL_DATE_ASC = 'date_asc' ;
	const CRWL_ALPHA_DESC = 'alpha_desc' ;
	const CRWL_ALPHA_ASC = 'alpha_asc' ;

	protected $options ;
	protected $vary_groups ;
	protected $exclude_optimization_roles ;
	protected $exclude_cache_roles ;
	protected $purge_options ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct()
	{
		if ( is_multisite() ) {
			$options = $this->construct_multisite_options() ;
		}
		else {
			$options = get_option( self::OPTION_NAME, $this->get_default_options() ) ;

			// Check advanced_cache set
			$this->_check_adv_cache( $options ) ;
		}

		$this->options = $options ;
		$this->purge_options = explode('.', $options[ self::OPID_PURGE_BY_POST ] ) ;

		// Init global const cache on set
		if ( $this->options[ self::OPID_ENABLED_RADIO ] === self::VAL_ON
		//	 || ( is_multisite() && is_network_admin() && current_user_can( 'manage_network_options' ) && $this->options[ LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED ] ) todo: need to check when primary is off and network is on, if can manage
		) {
			$this->_define_cache_on() ;
		}

		// Vary group settings
		$this->vary_groups = (array) get_option( self::VARY_GROUP, array() ) ;

		// Exclude optimization role setting
		$this->exclude_optimization_roles = get_option( self::EXCLUDE_OPTIMIZATION_ROLES, array() ) ;

		// Exclude cache role setting
		$this->exclude_cache_roles = get_option( self::EXCLUDE_CACHE_ROLES, array() ) ;

		// Set security key if not initialized yet
		if ( isset( $this->options[ self::HASH ] ) && empty( $this->options[ self::HASH ] ) ) {
			$this->update_options( array( self::HASH => Litespeed_String::rrand( 32 ) ) ) ;
		}

	}

	/**
	 * Define `LSCACHE_ADV_CACHE` based on options setting
	 *
	 * NOTE: this must be before `LITESPEED_ON` defination
	 *
	 * @since 2.1
	 * @access private
	 */
	private function _check_adv_cache( $options )
	{
		if ( isset( $options[ self::OPID_CHECK_ADVANCEDCACHE ] ) && $options[ self::OPID_CHECK_ADVANCEDCACHE ] === false && ! defined( 'LSCACHE_ADV_CACHE' ) ) {
			define( 'LSCACHE_ADV_CACHE', true ) ;
		}
	}

	/**
	 * Define `LITESPEED_ON`
	 *
	 * @since 2.1
	 * @access private
	 */
	private function _define_cache_on()
	{
		defined( 'LITESPEED_ALLOWED' ) && defined( 'LSCACHE_ADV_CACHE' ) && ! defined( 'LITESPEED_ON' ) && define( 'LITESPEED_ON', true ) ;

		// Use this for cache enabled setting check
		! defined( 'LITESPEED_ON_IN_SETTING' ) && define( 'LITESPEED_ON_IN_SETTING', true ) ;
	}

	/**
	 * For multisite installations, the single site options need to be updated with the network wide options.
	 *
	 * @since 1.0.13
	 * @access private
	 * @return array The updated options.
	 */
	private function construct_multisite_options()
	{
		$site_options = get_site_option( self::OPTION_NAME ) ;

		$this->_check_adv_cache( $site_options ) ;

		$options = get_option( self::OPTION_NAME, $this->get_default_options() ) ;

		/**
		 * In case this is called outside the admin page
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;
		}

		// If don't have site options
		if ( ! $site_options || ! is_array( $site_options ) || ! is_plugin_active_for_network( 'litespeed-cache/litespeed-cache.php' ) ) {
			if ( $options[ self::OPID_ENABLED_RADIO ] === self::VAL_ON2 ) { // Default to cache on
				$this->_define_cache_on() ;
			}
			return $options ;
		}

		// If network set to use primary setting
		if ( ! empty ( $site_options[ self::NETWORK_OPID_USE_PRIMARY ] ) ) {

			// save temparary cron setting
			$CRWL_CRON_ACTIVE = $options[ self::CRWL_CRON_ACTIVE ] ;

			// Get the primary site settings
			$options = get_blog_option( BLOG_ID_CURRENT_SITE, LiteSpeed_Cache_Config::OPTION_NAME, array() ) ;

			// crawler cron activation is separated
			$options[ self::CRWL_CRON_ACTIVE ] = $CRWL_CRON_ACTIVE ;
		}

		// If use network setting
		if ( $options[ self::OPID_ENABLED_RADIO ] === self::VAL_ON2 && $site_options[ self::NETWORK_OPID_ENABLED ] ) {
			$this->_define_cache_on() ;
		}
		// Set network eanble to on
		if ( $site_options[ self::NETWORK_OPID_ENABLED ] ) {
			! defined( 'LITESPEED_NETWORK_ON' ) && define( 'LITESPEED_NETWORK_ON', true ) ;
		}

		// These two are not for single blog options
		unset( $site_options[ self::NETWORK_OPID_ENABLED ] ) ;
		unset( $site_options[ self::NETWORK_OPID_USE_PRIMARY ] ) ;

		// Append site options to single blog options
		$options = array_merge( $options, $site_options ) ;

		return $options ;
	}

	/**
	 * Get the list of configured options for the blog.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The list of configured options.
	 */
	public function get_options()
	{
		return $this->options ;
	}

	/**
	 * Get the selected configuration option.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $id Configuration ID.
	 * @return mixed Selected option if set, NULL if not.
	 */
	public function get_option( $id )
	{
		if ( isset( $this->options[$id] ) ) {
			return $this->options[$id] ;
		}

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Cfg] Invalid option ID ' . $id ) ;

		return NULL ;
	}

	/**
	 * Set the configured options.
	 *
	 * NOTE: No validation here. Do validate before use this function with LiteSpeed_Cache_Admin_Settings->validate_plugin_settings().
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array $new_cfg The new settings to update, which will be update $this->options too.
	 * @return array The result of update.
	 */
	public function update_options( $new_cfg = array() )
	{
		if ( ! empty( $new_cfg ) ) {
			$this->options = array_merge( $this->options, $new_cfg ) ;
		}
		return update_option( self::OPTION_NAME, $this->options ) ;
	}

	/**
	 * Save frontend url to private cached uri/no cache uri
	 *
	 * @since 1.3
	 * @access public
	 */
	public static function frontend_save()
	{
		if ( empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
			exit( 'no referer' ) ;
		}

		if ( ! $type = LiteSpeed_Cache_Router::verify_type() ) {
			exit( 'no type' ) ;
		}

		switch ( $type ) {
			case 'private' :
				$id = self::OPID_CACHE_URI_PRIV ;
				break ;

			case 'nonoptimize' :
				$id = self::OPID_OPTM_EXCLUDES ;
				break ;

			case 'nocache' :
			default:
				$id = self::OPID_EXCLUDES_URI ;
				break ;
		}

		$instance = self::get_instance() ;
		$list = $instance->get_option( $id ) ;

		$list = explode( "\n", $list ) ;
		$list[] = $_SERVER[ 'HTTP_REFERER' ] ;
		$list = array_map( 'LiteSpeed_Cache_Utility::make_relative', $list ) ;// Remove domain
		$list = array_unique( $list ) ;
		$list = array_filter( $list ) ;
		$list = implode( "\n", $list ) ;

		$instance->update_options( array( $id => $list ) ) ;

		// Purge this page & redirect
		LiteSpeed_Cache_Purge::frontend_purge() ;
		exit() ;
	}

	/**
	 * Check if one user role is in vary group settings
	 *
	 * @since 1.2.0
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_vary_group( $role )
	{
		$group = 0 ;
		if ( array_key_exists( $role, $this->vary_groups ) ) {
			$group = $this->vary_groups[ $role ] ;
		}
		elseif ( $role === 'administrator' ) {
			$group = 99 ;
		}

		if ( $group ) {
			LiteSpeed_Cache_Log::debug2( '[Cfg] role in vary_group [group] ' . $group ) ;
		}

		return $group ;
	}

	/**
	 * Check if one user role is in exclude optimization group settings
	 *
	 * @since 1.6
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_exclude_optimization_roles( $role = null )
	{
		// Get user role
		if ( $role === null ) {
			$role = LiteSpeed_Cache_Router::get_role() ;
		}

		if ( ! $role ) {
			return false ;
		}

		return in_array( $role, $this->exclude_optimization_roles ) ? $role : false ;
	}

	/**
	 * Check if one user role is in exclude cache group settings
	 *
	 * @since 1.6.2
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_exclude_cache_roles( $role = null )
	{
		// Get user role
		if ( $role === null ) {
			$role = LiteSpeed_Cache_Router::get_role() ;
		}

		if ( ! $role ) {
			return false ;
		}

		return in_array( $role, $this->exclude_cache_roles ) ? $role : false ;
	}

	/**
	 * Get the configured purge options.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The list of purge options.
	 */
	public function get_purge_options()
	{
		return $this->purge_options ;
	}

	/**
	 * Check if the flag type of posts should be purged on updates.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $flag Post type. Refer to LiteSpeed_Cache_Config::PURGE_*
	 * @return boolean True if the post type should be purged, false otherwise.
	 */
	public function purge_by_post( $flag )
	{
		return in_array( $flag, $this->purge_options ) ;
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
			self::ID_MOBILEVIEW_LIST => false,
			self::OPID_CACHE_URI_PRIV => '',
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
			self::OPID_FEED_TTL => 0,
			self::OPID_403_TTL => 3600,
			self::OPID_404_TTL => 3600,
			self::OPID_500_TTL => 3600,
			self::OPID_PURGE_BY_POST => implode('.', $default_purge_options),
			self::OPID_EXCLUDES_URI => '',
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
			self::OPID_OPTM_JS_DEFER => false,
			self::OPID_OPTM_EMOJI_RM => false,
			self::OPID_OPTM_EXCLUDES => '',
			self::OPID_OPTM_EXC_JQUERY => true,
			self::OPID_OPTM_GGFONTS_ASYNC => false,
			self::OPID_OPTM_MAX_SIZE => 1.2,
			self::OPID_OPTM_RM_COMMENT => false,

			self::OPID_CDN 			=> false,
			self::OPID_CDN_ORI 		=> '',
			self::OPID_CDN_EXCLUDE 	=> '',
			self::OPID_CDN_REMOTE_JQUERY 	=> false,
			self::OPID_CDN_QUIC 	=> false,
			self::OPID_CDN_QUIC_EMAIL 	=> '',
			self::OPID_CDN_QUIC_KEY 	=> '',
			self::OPID_CDN_QUIC_SITE 	=> '',
			self::OPID_CDN_CLOUDFLARE 	=> false,
			self::OPID_CDN_CLOUDFLARE_EMAIL 	=> '',
			self::OPID_CDN_CLOUDFLARE_KEY 	=> '',
			self::OPID_CDN_CLOUDFLARE_NAME 	=> '',
			self::OPID_CDN_CLOUDFLARE_ZONE 	=> '',

			self::OPID_MEDIA_IMG_LAZY 	=> false,
			self::OPID_MEDIA_IMG_LAZY_PLACEHOLDER 	=> '',
			self::OPID_MEDIA_IFRAME_LAZY 	=> false,
			self::OPID_MEDIA_IMG_OPTM_CRON_OFF 	=> false,
			self::OPID_MEDIA_IMG_WEBP 	=> false,
			self::OPID_MEDIA_IMG_WEBP_ONLY 	=> false,
			self::OPID_MEDIA_IMG_EXIF 	=> false,
			self::OPID_MEDIA_IMG_WEBP_LOSSLESS 	=> false,

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
			self::CRWL_HTTP2 => true,
				) ;

		if ( LSWCP_ESI_SUPPORT ) {
			$default_options[self::OPID_ESI_ENABLE] = false ;
			$default_options[self::OPID_ESI_CACHE_ADMBAR] = true ;
			$default_options[self::OPID_ESI_CACHE_COMMFORM] = true ;
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
			self::OPID_PURGE_ON_UPGRADE => true,
			self::OPID_CACHE_FAVICON => true,
			self::OPID_CACHE_RES => true,
			self::OPID_CACHE_MOBILE => 0, // todo: why not false
			self::ID_MOBILEVIEW_LIST => false,
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
			self::OPID_MEDIA_IMG_WEBP => false,
		) ;
		return $default_site_options ;
	}

	/**
	 * Get default item val
	 *
	 * @since 1.8
	 * @access public
	 */
	public function default_item( $k )
	{
		switch ( $k ) {
			case self::ITEM_OBJECT_GLOBAL_GROUPS :
				return "users\nuserlogins\nusermeta\nuser_meta\nsite-transient\nsite-options\nsite-lookup\nblog-lookup\nblog-details\nrss\nglobal-posts\nblog-id-cache" ;

			case self::ITEM_OBJECT_NON_PERSISTENT_GROUPS :
				return "comment\ncounts\nplugins" ;

			default :
				break ;
		}

		return false ;
	}

	/**
	 * Get the plugin's site wide options.
	 *
	 * If the site wide options are not set yet, set it to default.
	 *
	 * @since 1.0.2
	 * @access public
	 * @return array Returns the current site options.
	 */
	public function get_site_options()
	{
		if ( ! is_multisite() ) {
			return null ;
		}
		$site_options = get_site_option( self::OPTION_NAME ) ;

		if ( isset( $site_options ) && is_array( $site_options ) ) {
			return $site_options ;
		}

		$default_site_options = $this->get_default_site_options() ;
		add_site_option( self::OPTION_NAME, $default_site_options ) ;

		return $default_site_options ;
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

	/**
	 * Helper function to convert the options to replicate the input format.
	 *
	 * The only difference is the checkboxes.
	 *
	 * @since 1.0.15
	 * @access public
	 * @param array $options The options array to port to input format.
	 * @return array $options The options array with input format.
	 */
	public static function convert_options_to_input($options)
	{
		foreach ( $options as $key => $val ) {
			if ( $val === true ) {
				$options[$key] = self::VAL_ON ;
			}
			elseif ( $val === false ) {
				$options[$key] = self::VAL_OFF ;
			}
		}
		if ( isset($options[self::OPID_PURGE_BY_POST]) ) {
			$purge_opts = explode('.', $options[self::OPID_PURGE_BY_POST]) ;

			foreach ($purge_opts as $purge_opt) {
				$options['purge_' . $purge_opt] = self::VAL_ON ;
			}
		}

		return $options ;
	}

	/**
	 * Get the difference between the current options and the default options.
	 *
	 * @since 1.0.11
	 * @access public
	 * @param array $default_options The default options.
	 * @param array $options The current options.
	 * @return array New options.
	 */
	public static function option_diff($default_options, $options)
	{
		$dkeys = array_keys($default_options) ;
		$keys = array_keys($options) ;
		$newkeys = array_diff($dkeys, $keys) ;
		$log = '' ;//todo: useless
		if ( ! empty($newkeys) ) {
			foreach ( $newkeys as $newkey ) {
				$options[$newkey] = $default_options[$newkey]  ;
				$log .= ' Added ' . $newkey . ' = ' . $default_options[$newkey]  ;
			}
		}
		$retiredkeys = array_diff($keys, $dkeys)  ;
		if ( ! empty($retiredkeys) ) {
			foreach ( $retiredkeys as $retired ) {
				unset($options[$retired])  ;
				$log .= 'Removed ' . $retired  ;
			}
		}
		$options[self::OPID_VERSION] = LiteSpeed_Cache::PLUGIN_VERSION ;

		return $options ;
	}

	/**
	 * Verify that the options are still valid.
	 *
	 * This is used only when upgrading the plugin versions.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_upgrade()
	{
		$default_options = $this->get_default_options() ;

		if ( $this->options[ self::OPID_VERSION ] == $default_options[ self::OPID_VERSION ] && count( $default_options ) == count( $this->options ) ) {
			return ;
		}

		/**
		 * Resave cdn cfg from lscfg to separate cfg when upgrade to v1.7
		 * @since 1.7
		 */
		if ( isset( $this->options[ 'cdn_url' ] ) ) {
			$cdn_mapping = array(
				self::ITEM_CDN_MAPPING_URL 		=> $this->options[ 'cdn_url' ],
				self::ITEM_CDN_MAPPING_INC_IMG 	=> $this->options[ 'cdn_inc_img' ],
				self::ITEM_CDN_MAPPING_INC_CSS 	=> $this->options[ 'cdn_inc_css' ],
				self::ITEM_CDN_MAPPING_INC_JS 	=> $this->options[ 'cdn_inc_js' ],
				self::ITEM_CDN_MAPPING_FILETYPE => $this->options[ 'cdn_filetype' ],
			) ;
			update_option( LiteSpeed_Cache_Config::ITEM_CDN_MAPPING, array( $cdn_mapping ) ) ;
			LiteSpeed_Cache_Log::debug( "[Cfg] plugin_upgrade option adding CDN map" ) ;
		}

		$this->options = self::option_diff( $default_options, $this->options ) ;

		$res = $this->update_options() ;
		define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;
		LiteSpeed_Cache_Log::debug( "[Cfg] plugin_upgrade option changed = $res" ) ;

		// Update img_optm table data for upgrading
		LiteSpeed_Cache_Data::get_instance() ;
	}

	/**
	 * Upgrade network options when the plugin is upgraded.
	 *
	 * @since 1.0.11
	 * @access public
	 */
	public function plugin_site_upgrade()
	{
		$default_options = $this->get_default_site_options() ;
		$options = $this->get_site_options() ;

		if ( $options[ self::OPID_VERSION ] == $default_options[ self::OPID_VERSION ] && count( $default_options ) == count( $options ) ) {
			return ;
		}

		$options = self::option_diff( $default_options, $options ) ;

		$res = update_site_option( self::OPTION_NAME, $options ) ;

		LiteSpeed_Cache_Log::debug( "[Cfg] plugin_upgrade option changed = $res\n" ) ;
	}

	/**
	 * Update the WP_CACHE variable in the wp-config.php file.
	 *
	 * If enabling, check if the variable is defined, and if not, define it.
	 * Vice versa for disabling.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param boolean $enable True if enabling, false if disabling.
	 * @return boolean True if the variable is the correct value, false if something went wrong.
	 */
	public static function wp_cache_var_setter( $enable )
	{
		if ( $enable ) {
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				return true ;
			}
		}
		elseif ( ! defined( 'WP_CACHE' ) || ( defined( 'WP_CACHE' ) && ! WP_CACHE ) ) {
				return true ;
		}

		$file = ABSPATH . 'wp-config.php' ;

		if ( ! is_writeable( $file ) ) {
			$file = dirname( ABSPATH ) . '/wp-config.php' ; // todo: is the path correct?
			if ( ! is_writeable( $file ) ) {
				error_log( 'wp-config file not writable for \'WP_CACHE\'' ) ;
				return LiteSpeed_Cache_Admin_Error::E_CONF_WRITE ;
			}
		}

		$file_content = file_get_contents( $file ) ;

		if ( $enable ) {
			$count = 0 ;

			$new_file_content = preg_replace( '/[\/]*define\(.*\'WP_CACHE\'.+;/', "define('WP_CACHE', true);", $file_content, -1, $count ) ;
			if ( $count == 0 ) {
				$new_file_content = preg_replace( '/(\$table_prefix)/', "define('WP_CACHE', true);\n$1", $file_content ) ;
				if ( $count == 0 ) {
					$new_file_content = preg_replace( '/(\<\?php)/', "$1\ndefine('WP_CACHE', true);", $file_content, -1, $count ) ;
				}

				if ( $count == 0 ) {
					error_log( 'wp-config file did not find a place to insert define.' ) ;
					return LiteSpeed_Cache_Admin_Error::E_CONF_FIND ;
				}
			}
		}
		else {
			$new_file_content = preg_replace( '/define\(.*\'WP_CACHE\'.+;/', "define('WP_CACHE', false);", $file_content ) ;
		}

		file_put_contents( $file, $new_file_content ) ;
		return true ;
	}

	/**
	 * On plugin activation, load the default options.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int $count The count of blogs active in multisite.
	 */
	public function plugin_activation( $count )
	{
		$errors = array() ;

		$res = add_option( self::OPTION_NAME, $this->get_default_options() ) ;

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( "[Cfg] plugin_activation update option = " . var_export( $res, true ) ) ;

		$disable_lscache = false ;

		if ( is_multisite() ) {

			if ( ! is_network_admin() ) {
				if ( $count === 1 ) {
					// Only itself is activated, set .htaccess with only CacheLookUp
					LiteSpeed_Cache_Admin_Rules::get_instance()->insert_ls_wrapper() ;
				}
				return ;
			}

			$options = $this->get_site_options() ;

			if ( ! $options[ self::NETWORK_OPID_ENABLED ] ) {
				// NOTE: Network admin still need to make a lscache wrapper to avoid subblogs cache not work
				$disable_lscache = true ;
			}

		}
		else {
			$options = $this->get_options() ;
			if ( ! $options[ self::OPID_ENABLED_RADIO ] ) {
				$disable_lscache = true ;
			}
		}

		$res = LiteSpeed_Cache_Admin_Rules::get_instance()->update( $options, $disable_lscache ) ;

        if ( $res !== true ) {
        	if ( ! is_array( $res ) ) {
        		exit( $res ) ;
        	}
			exit( implode( "\n", $res ) ) ;
        }

	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
