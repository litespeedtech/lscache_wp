<?php
/**
 * The core plugin config class.
 *
 * This maintains all the options and settings for this plugin.
 *
 * @since      1.0.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Config
{
	private static $_instance ;

	const OPTION_NAME = 'litespeed-cache-conf' ;
	const VAL_OFF = 0 ;
	const VAL_ON = 1 ;
	const VAL_NOTSET = 2 ;

	const LOG_LEVEL_NONE = 0 ;
	const LOG_LEVEL_ERROR = 1 ;
	const LOG_LEVEL_NOTICE = 2 ;
	const LOG_LEVEL_INFO = 3 ;
	const LOG_LEVEL_DEBUG = 4 ;
	const OPID_VERSION = 'version' ;
	const OPID_ENABLED = 'enabled' ;
	const OPID_ENABLED_RADIO = 'radio_select' ;

	const OPID_CACHE_PRIV = 'cache_priv' ;
	const OPID_CACHE_COMMENTER = 'cache_commenter' ;
	const OPID_CACHE_REST = 'cache_rest' ;
	const OPID_CACHE_PAGE_LOGIN = 'cache_page_login' ;
	const OPID_CACHE_FAVICON = 'cache_favicon' ;
	const OPID_CACHE_RES = 'cache_resources' ;
	const OPID_CACHE_MOBILE = 'mobileview_enabled' ;
	const ID_MOBILEVIEW_LIST = 'mobileview_rules' ;

	const OPID_PURGE_ON_UPGRADE = 'purge_upgrade' ;
	const OPID_TIMED_URLS = 'timed_urls' ;
	const OPID_TIMED_URLS_TIME = 'timed_urls_time' ;

	const OPID_LOGIN_COOKIE = 'login_cookie' ;
	const OPID_CHECK_ADVANCEDCACHE = 'check_advancedcache' ;
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
	const OPID_LOG_IGNORE_FILTERS = 'log_ignore_filters' ;
	const OPID_LOG_IGNORE_PART_FILTERS = 'log_ignore_part_filters' ;

	const OPID_PUBLIC_TTL = 'public_ttl' ;
	const OPID_FRONT_PAGE_TTL = 'front_page_ttl' ;
	const OPID_FEED_TTL = 'feed_ttl' ;
	const OPID_403_TTL = '403_ttl' ;
	const OPID_404_TTL = '404_ttl' ;
	const OPID_500_TTL = '500_ttl' ;
	const OPID_PURGE_BY_POST = 'purge_by_post' ;
	const OPID_TEST_IPS = 'test_ips' ;
	const OPID_ESI_ENABLE = 'esi_enabled' ;
	const OPID_ESI_CACHE = 'esi_cached' ;
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
	const OPID_EXCLUDES_CAT = 'excludes_cat' ;
	const OPID_EXCLUDES_TAG = 'excludes_tag' ;

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

	protected $options ;
	protected $purge_options ;
	protected $debug_tag = 'LiteSpeed_Cache' ;

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
		}
		$this->options = $options ;
		$this->purge_options = explode('.', $options[ self::OPID_PURGE_BY_POST ] ) ;

		if ( isset( $options[ self::OPID_CHECK_ADVANCEDCACHE ] ) && $options[self::OPID_CHECK_ADVANCEDCACHE] === false && ! defined('LSCACHE_ADV_CACHE') ) {
			define('LSCACHE_ADV_CACHE', true) ;
		}

		if (WP_DEBUG /* && $this->options[self::OPID_DEBUG] */ ) {
			$msec = microtime() ;
			$msec1 = substr($msec, 2, strpos($msec, ' ') - 2) ;
			if ( array_key_exists('REMOTE_ADDR', $_SERVER) && array_key_exists('REMOTE_PORT', $_SERVER) ) {
				$this->debug_tag .= ' [' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'] . ':' . $msec1 . '] ' ;
			}
		}
	}

	/**
	 * For multisite installations, the single site options need to be updated
	 * with the network wide options.
	 *
	 * @since 1.0.13
	 * @access private
	 * @return array The updated options.
	 */
	private function construct_multisite_options()
	{
		$site_options = get_site_option(self::OPTION_NAME) ;

		if ( ! function_exists('is_plugin_active_for_network') ) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php') ;
		}

		$options = get_option(self::OPTION_NAME, $this->get_default_options()) ;

		if ( ! $site_options || ! is_array($site_options) || ! is_plugin_active_for_network('litespeed-cache/litespeed-cache.php') ) {
			if ($options[self::OPID_ENABLED_RADIO] == 2) {
				$options[self::OPID_ENABLED] = true ;
			}
			return $options ;
		}

		if ( isset($site_options[self::NETWORK_OPID_USE_PRIMARY]) && $site_options[self::NETWORK_OPID_USE_PRIMARY] ) {
			// save temparary cron setting
			$CRWL_CRON_ACTIVE = $options[self::CRWL_CRON_ACTIVE] ;

			$options = get_blog_option(BLOG_ID_CURRENT_SITE, LiteSpeed_Cache_Config::OPTION_NAME, array()) ;

			// crawler cron activation is separated
			$options[self::CRWL_CRON_ACTIVE] = $CRWL_CRON_ACTIVE ;
		}

		$options[self::NETWORK_OPID_ENABLED] = $site_options[self::NETWORK_OPID_ENABLED] ;
		if ($options[self::OPID_ENABLED_RADIO] == 2) {
			$options[self::OPID_ENABLED] = $options[self::NETWORK_OPID_ENABLED] ;
		}
		unset( $site_options[ self::NETWORK_OPID_ENABLED ] ) ;
		unset( $site_options[ self::NETWORK_OPID_USE_PRIMARY ] ) ;
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
		if ( LiteSpeed_Cache_Log::initialized() ) {
			LiteSpeed_Cache_Log::debug( 'Invalid option ID ' . $id ) ;
		}
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
			self::PURGE_POST_TYPE
		) ;
		sort($default_purge_options) ;

		//For multi site, default is 2 (Use Network Admin Settings). For single site, default is 1 (Enabled).
		if ( is_multisite() ) {
			$default_enabled = false ;
			$default_radio = 2 ;
		}
		else {
			$default_enabled = true ;
			$default_radio = 1 ;
		}

		$default_options = array(
			self::OPID_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::OPID_ENABLED => $default_enabled,
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
			self::OPID_LOGIN_COOKIE => '',
			self::OPID_CHECK_ADVANCEDCACHE => true,
			self::OPID_DEBUG => self::LOG_LEVEL_NONE,
			self::OPID_ADMIN_IPS => '127.0.0.1',
			self::OPID_DEBUG_LEVEL => false,
			self::OPID_LOG_FILE_SIZE => 30,
			self::OPID_HEARTBEAT => true,
			self::OPID_DEBUG_COOKIE => false,
			self::OPID_COLLAPS_QS => false,
			self::OPID_LOG_FILTERS => false,
			self::OPID_LOG_IGNORE_FILTERS => "gettext\ngettext_with_context\nget_the_terms\nget_term",
			self::OPID_LOG_IGNORE_PART_FILTERS => "i18n\nlocale\nsettings\noption",
			self::OPID_TEST_IPS => '',
			self::OPID_PUBLIC_TTL => 604800,
			self::OPID_FRONT_PAGE_TTL => 604800,
			self::OPID_FEED_TTL => 0,
			self::OPID_403_TTL => 3600,
			self::OPID_404_TTL => 3600,
			self::OPID_500_TTL => 3600,
			self::OPID_PURGE_BY_POST => implode('.', $default_purge_options),
			self::OPID_EXCLUDES_URI => '',
			self::OPID_EXCLUDES_CAT => '',
			self::OPID_EXCLUDES_TAG => '',
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

		if ( LSWCP_ESI_SUPPORT ) {
			$default_options[self::OPID_ESI_ENABLE] = false ;
			$default_options[self::OPID_ESI_CACHE] = false ;
		}

		if ( is_multisite() ) {
			$default_options[self::NETWORK_OPID_ENABLED] = false ;
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
			self::OPID_LOGIN_COOKIE => '',
			self::OPID_CHECK_ADVANCEDCACHE => true,
			self::ID_NOCACHE_COOKIES => '',
			self::ID_NOCACHE_USERAGENTS => '',
		) ;
		return $default_site_options ;
	}

	/**
	 * When the .htaccess files need to be reset, use this array to denote
	 * everything off.
	 *
	 * @since 1.0.12
	 * @access public
	 * @return array The list of options to reset.
	 */
	public static function get_rule_reset_options()
	{
		$reset = array(
			LiteSpeed_Cache_Config::OPID_CACHE_MOBILE => false,
			LiteSpeed_Cache_Config::OPID_CACHE_FAVICON => false,
			LiteSpeed_Cache_Config::OPID_CACHE_RES => false,
			LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST => false,
			LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES => '',
			LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS => '',
			LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE => ''
		) ;
		return $reset ;
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
		$site_options = get_site_option(self::OPTION_NAME) ;
		if ( isset($site_options) && is_array($site_options) ) {
			return $site_options ;
		}
		elseif ( isset($site_options) && is_string($site_options) ) {
			return $site_options ;
		}
		$default_site_options = $this->get_default_site_options() ;
		add_site_option(self::OPTION_NAME, $default_site_options) ;
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

		if ( $options[self::OPID_CACHE_MOBILE] === false ) {
			$options[self::ID_MOBILEVIEW_LIST] = false ;
		}
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

		if ( $this->options[self::OPID_VERSION] == $default_options[self::OPID_VERSION] && count($default_options) == count($this->options) ) {
			return ;
		}

		$this->options = self::option_diff($default_options, $this->options) ;

		$res = $this->update_options() ;
		define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;
		LiteSpeed_Cache_Log::debug("plugin_upgrade option changed = $res\n") ;
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

		if ( $options[self::OPID_VERSION] == $default_options[self::OPID_VERSION] && count($default_options) == count($options) ) {
			return ;
		}

		$options = self::option_diff($default_options, $options) ;

		$res = update_site_option(self::OPTION_NAME, $options) ;

		LiteSpeed_Cache_Log::debug("plugin_upgrade option changed = $res\n") ;
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
			if ( defined('WP_CACHE') && WP_CACHE ) {
				return true ;
			}
		}
		elseif ( ! defined('WP_CACHE') || (defined('WP_CACHE') && ! WP_CACHE) ) {
				return true ;
		}
		$file = ABSPATH . 'wp-config.php' ;
		if ( ! is_writeable($file) ) {
			$file = dirname(ABSPATH) . '/wp-config.php' ;
			if ( ! is_writeable($file) ) {
				error_log('wp-config file not writeable for \'WP_CACHE\'') ;
				return LiteSpeed_Cache_Admin_Error::E_CONF_WRITE ;
			}
		}
		$file_content = file_get_contents($file) ;

		if ( $enable ) {
			$count = 0 ;

			$new_file_content = preg_replace('/[\/]*define\(.*\'WP_CACHE\'.+;/', "define('WP_CACHE', true);", $file_content, -1, $count) ;
			if ( $count == 0 ) {
				$new_file_content = preg_replace('/(\$table_prefix)/', "define('WP_CACHE', true);\n$1", $file_content) ;
				if ( $count == 0 ) {
					$new_file_content = preg_replace('/(\<\?php)/', "$1\ndefine('WP_CACHE', true);", $file_content, -1, $count) ;
				}

				if ( $count == 0 ) {
					error_log('wp-config file did not find a place to insert define.') ;
					return LiteSpeed_Cache_Admin_Error::E_CONF_FIND ;
				}
			}
		}
		else {
			$new_file_content = preg_replace('/define\(.*\'WP_CACHE\'.+;/', "define('WP_CACHE', false);", $file_content) ;
		}

		file_put_contents($file, $new_file_content) ;
		return true ;
	}

	/**
	 * On plugin activation, load the default options.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int $count The count of blogs active in multisite.
	 */
	public function plugin_activation($count)
	{
		$errors = array() ;
		$rules = LiteSpeed_Cache_Admin_Rules::get_instance() ;
		$default = $this->get_default_options() ;
		$res = add_option(self::OPTION_NAME, $default) ;
		if ( LiteSpeed_Cache_Log::get_enabled() ) {
			LiteSpeed_Cache_Log::push("plugin_activation update option = ".var_export($res, true)) ;
		}
		if ( is_multisite() ) {
			if ( ! is_network_admin() ) {
				if ( $count === 1 ) {
					$rules->validate_common_rewrites(array(), $errors) ;
				}
				return ;
			}
			$options = $this->get_site_options() ;
			if ( isset($options) && is_string($options) ) {
				$options = unserialize($options) ;
				update_site_option(self::OPTION_NAME, $options) ;
			}
			if ( $res == true || $options[self::NETWORK_OPID_ENABLED] == false ) {
				return ;
			}
		}
		elseif ( $res == false && $this->get_option(self::OPID_ENABLED) == false ) {
			return ;
		}
		else {
			$options = $this->get_options() ;
		}

		$default = self::get_rule_reset_options() ;

		$diff = $rules->check_input_for_rewrite($default, $options, $errors) ;

        if ( ! empty($diff) ) {
            $rules->validate_common_rewrites($diff, $errors) ;
        }

        if ( ! empty($errors) ) {
			exit(implode("\n", $errors)) ;
        }

	}

	/**
	 * Checks if caching is allowed via server variable.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return boolean True if allowed, false otherwise.
	 */
	public function is_caching_allowed()
	{
		//lsws
		if ( isset( $_SERVER['X-LSCACHE'] ) && $_SERVER['X-LSCACHE'] ) {
			return true ;
		}
		// web adc
		if ( LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_ADC' ) {
			return true ;
		}
		// cli call
		if ( PHP_SAPI == 'cli' ) {
			return true ;
		}
		return false ;
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
		$cls = get_called_class() ;
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new $cls() ;
		}

		return self::$_instance ;
	}
}
