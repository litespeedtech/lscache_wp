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

	const OPTION_NAME = 'litespeed-cache-conf' ;
	const LOG_LEVEL_NONE = 0 ;
	const LOG_LEVEL_ERROR = 1 ;
	const LOG_LEVEL_NOTICE = 2 ;
	const LOG_LEVEL_INFO = 3 ;
	const LOG_LEVEL_DEBUG = 4 ;
	const OPID_VERSION = 'version' ;
	const OPID_ENABLED = 'enabled' ;
	const OPID_ENABLED_RADIO = 'radio_select';
	const OPID_ENABLED_DISABLE = 0;
	const OPID_ENABLED_ENABLE = 1;
	const OPID_ENABLED_NOTSET = 2;
	const OPID_PURGE_ON_UPGRADE = 'purge_upgrade';
	const OPID_CACHE_COMMENTERS = 'cache_commenters';
	const OPID_CACHE_LOGIN = 'cache_login';
	const OPID_CACHE_FAVICON = 'cache_favicon';
	const OPID_CACHE_RES = 'cache_resources';
	const OPID_MOBILEVIEW_ENABLED = 'mobileview_enabled';
	const OPID_LOGIN_COOKIE = 'login_cookie';
	const OPID_TAG_PREFIX = 'tag_prefix';
	const OPID_CHECK_ADVANCEDCACHE = 'check_advancedcache';
	// do NOT set default options for these three, it is used for admin.
	const ID_MOBILEVIEW_LIST = 'mobileview_rules';
	const ID_NOCACHE_COOKIES = 'nocache_cookies' ;
	const ID_NOCACHE_USERAGENTS = 'nocache_useragents' ;
	const OPID_DEBUG = 'debug' ;
	const OPID_ADMIN_IPS = 'admin_ips' ;
	const OPID_PUBLIC_TTL = 'public_ttl' ;
	const OPID_FRONT_PAGE_TTL = 'front_page_ttl';
	const OPID_FEED_TTL = 'feed_ttl';
	const OPID_404_TTL = '404_ttl';
	const OPID_NOCACHE_VARS = 'nocache_vars' ;
	const OPID_NOCACHE_PATH = 'nocache_path' ;
	const OPID_PURGE_BY_POST = 'purge_by_post' ;
	const OPID_TEST_IPS = 'test_ips' ;
	const PURGE_ALL_PAGES = '-' ;
	const PURGE_FRONT_PAGE = 'F' ;
	const PURGE_HOME_PAGE = 'H' ;
	const PURGE_AUTHOR = 'A' ;
	const PURGE_YEAR = 'Y' ;
	const PURGE_MONTH = 'M' ;
	const PURGE_DATE = 'D' ;
	const PURGE_TERM = 'T' ; // include category|tag|tax
	const PURGE_POST_TYPE = 'PT' ;
	const OPID_EXCLUDES_URI = 'excludes_uri' ;
	const OPID_EXCLUDES_CAT = 'excludes_cat' ;
	const OPID_EXCLUDES_TAG = 'excludes_tag' ;

	const NETWORK_OPID_ENABLED = 'network_enabled';
	const NETWORK_OPID_USE_PRIMARY = 'use_primary_settings';

	protected $options ;
	protected $purge_options ;
	protected $debug_tag = 'LiteSpeed_Cache' ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		if ( is_multisite()) {
			$options = $this->construct_multisite_options();
		}
		else {
			$options = get_option(self::OPTION_NAME,
				$this->get_default_options());
		}
		$this->options = $options ;
		$this->purge_options = explode('.', $options[self::OPID_PURGE_BY_POST]) ;

		if ((isset($options[self::OPID_CHECK_ADVANCEDCACHE]))
			&& ($options[self::OPID_CHECK_ADVANCEDCACHE] === false)
			&& (!defined('LSCACHE_ADV_CACHE'))) {
			define('LSCACHE_ADV_CACHE', true);
		}

		if ( true === WP_DEBUG /* && $this->options[self::OPID_DEBUG] */ ) {
			$msec = microtime() ;
			$msec1 = substr($msec, 2, strpos($msec, ' ') - 2) ;
			if ( array_key_exists('REMOTE_ADDR', $_SERVER) && array_key_exists('REMOTE_PORT', $_SERVER) ) {
				$this->debug_tag .= ' [' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'] . ':' . $msec1 . '] ' ;
			}
		}
	}

	private function construct_multisite_options()
	{
		$site_options = get_site_option(self::OPTION_NAME);
		if ((!$site_options) || (!is_array($site_options))) {
			$options = get_option(self::OPTION_NAME,
				$this->get_default_options());
			return $options;
		}
		if ((isset($site_options[self::NETWORK_OPID_USE_PRIMARY]))
			&& ($site_options[self::NETWORK_OPID_USE_PRIMARY])) {
			$main_id = BLOG_ID_CURRENT_SITE;
			$options = get_blog_option($main_id,
				LiteSpeed_Cache_Config::OPTION_NAME, array());
		}
		else {
			$options = get_option(self::OPTION_NAME,
				$this->get_default_options());
		}
		$options[self::NETWORK_OPID_ENABLED] = $site_options[self::NETWORK_OPID_ENABLED];
		if ($options[self::OPID_ENABLED_RADIO] == self::OPID_ENABLED_NOTSET) {
			$options[self::OPID_ENABLED] = $options[self::NETWORK_OPID_ENABLED];
		}
		$options[self::OPID_PURGE_ON_UPGRADE]
			= $site_options[self::OPID_PURGE_ON_UPGRADE];
		$options[self::OPID_MOBILEVIEW_ENABLED]
			= $site_options[self::OPID_MOBILEVIEW_ENABLED];
		$options[self::ID_MOBILEVIEW_LIST]
			= $site_options[self::ID_MOBILEVIEW_LIST];
		$options[self::OPID_LOGIN_COOKIE]
			= $site_options[self::OPID_LOGIN_COOKIE];
		$options[self::OPID_TAG_PREFIX]
			= $site_options[self::OPID_TAG_PREFIX];
		return $options;
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
	public function get_option($id)
	{
		if (isset($this->options[$id])) {
			return $this->options[$id];
		}
		if (defined('LSCWP_LOG')) {
			LiteSpeed_Cache::debug_log('Invalid option ID ' . $id);
		}
		return NULL;
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
		return in_array($flag, $this->purge_options) ;
	}

	/**
	 * Gets the default single site options
	 *
	 * @since 1.0.0
	 * @access protected
	 * @param bool $include_thirdparty Whether to include the thirdparty options.
	 * @return array An array of the default options.
	 */
	protected function get_default_options($include_thirdparty = true)
	{
		$default_purge_options = array(
			self::PURGE_FRONT_PAGE,
			self::PURGE_HOME_PAGE,
			self::PURGE_AUTHOR,
			self::PURGE_MONTH,
			self::PURGE_TERM,
			self::PURGE_POST_TYPE
				) ;
		sort($default_purge_options) ;

		//For multi site, default is 2 (Use Network Admin Settings). For single site, default is 1 (Enabled).
		if ( is_multisite()) {
			$default_enabled = false;
			$default_radio = self::OPID_ENABLED_NOTSET;
		}
		else {
			$default_enabled = true;
			$default_radio = self::OPID_ENABLED_ENABLE;
		}

		$default_options = array(
			self::OPID_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::OPID_ENABLED => $default_enabled,
			self::OPID_ENABLED_RADIO => $default_radio,
			self::OPID_PURGE_ON_UPGRADE => true,
			self::OPID_CACHE_COMMENTERS => true,
			self::OPID_CACHE_LOGIN => true,
			self::OPID_CACHE_FAVICON => true,
			self::OPID_CACHE_RES => true,
			self::OPID_MOBILEVIEW_ENABLED => false,
			self::ID_MOBILEVIEW_LIST => false,
			self::OPID_LOGIN_COOKIE => '',
			self::OPID_TAG_PREFIX => '',
			self::OPID_CHECK_ADVANCEDCACHE => true,
			self::OPID_DEBUG => self::LOG_LEVEL_NONE,
			self::OPID_ADMIN_IPS => '127.0.0.1',
			self::OPID_TEST_IPS => '',
			self::OPID_PUBLIC_TTL => 28800,
			self::OPID_FRONT_PAGE_TTL => 1800,
			self::OPID_FEED_TTL => 0,
			self::OPID_404_TTL => 3600,
			self::OPID_NOCACHE_VARS => '',
			self::OPID_NOCACHE_PATH => '',
			self::OPID_PURGE_BY_POST => implode('.', $default_purge_options),
			self::OPID_EXCLUDES_URI => '',
			self::OPID_EXCLUDES_CAT => '',
			self::OPID_EXCLUDES_TAG => '',
			self::ID_NOCACHE_COOKIES => '',
			self::ID_NOCACHE_USERAGENTS => '',
				) ;

		if (is_multisite()) {
			$default_options[self::NETWORK_OPID_ENABLED] = false;
		}

		if (!$include_thirdparty) {
			return $default_options;
		}

		$tp_options = $this->get_thirdparty_options($default_options);
		if ((!isset($tp_options)) || (!is_array($tp_options))) {
			return $default_options;
		}
		return array_merge($default_options, $tp_options);
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
			self::OPID_MOBILEVIEW_ENABLED => 0,
			self::ID_MOBILEVIEW_LIST => false,
			self::OPID_LOGIN_COOKIE => '',
			self::OPID_TAG_PREFIX => '',
			self::OPID_CHECK_ADVANCEDCACHE => true,
			self::ID_NOCACHE_COOKIES => '',
			self::ID_NOCACHE_USERAGENTS => '',
				);
		return $default_site_options;
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
			LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED => false,
			LiteSpeed_Cache_Config::OPID_CACHE_FAVICON => false,
			LiteSpeed_Cache_Config::OPID_CACHE_RES => false,
			LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST => false,
			LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES => '',
			LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS => '',
			LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE => ''
		);
		return $reset;
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
		if (!is_multisite()) {
			return null;
		}
		$site_options = get_site_option(self::OPTION_NAME);
		if (isset($site_options) && is_array($site_options)) {
			return $site_options;
		}
		elseif (isset($site_options) && is_string($site_options)) {
			return $site_options;
		}
		$default_site_options = $this->get_default_site_options();
		add_site_option(self::OPTION_NAME, $default_site_options);
		return $default_site_options;
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
		$tp_options = apply_filters('litespeed_cache_get_options', array());
		if (empty($tp_options)) {
			return false;
		}
		if (!isset($options)) {
			$options = $this->get_default_options(false);
		}
		return array_diff_key($tp_options, $options);
	}

	/**
	 * Get the difference between the current options and the default options.
	 *
	 * @since 1.0.11
	 * @access private
	 * @param array $default_options The default options.
	 * @param array $options The current options.
	 */
	private static function option_diff($default_options, &$options)
	{
		$dkeys = array_keys($default_options);
		$keys = array_keys($options);
		$newkeys = array_diff($dkeys, $keys);
		$log = '' ;
		if ( ! empty($newkeys) ) {
			foreach ( $newkeys as $newkey ) {
				$options[$newkey] = $default_options[$newkey] ;
				$log .= ' Added ' . $newkey . ' = ' . $default_options[$newkey] ;
			}
		}
		$retiredkeys = array_diff($keys, $dkeys) ;
		if ( ! empty($retiredkeys) ) {
			foreach ( $retiredkeys as $retired ) {
				unset($options[$retired]) ;
				$log .= 'Removed ' . $retired ;
			}
		}
		$options[self::OPID_VERSION] = LiteSpeed_Cache::PLUGIN_VERSION;

		if ($options[self::OPID_MOBILEVIEW_ENABLED] === false) {
			$options[self::ID_MOBILEVIEW_LIST] = false;
		}
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

		if (($this->options[self::OPID_VERSION] == $default_options[self::OPID_VERSION])
				&& (count($default_options) == count($this->options))) {
			return;
		}

		self::option_diff($default_options, $this->options);

//		if ((!is_multisite()) || (is_network_admin())) {
//			$this->options[self::OPID_LOGIN_COOKIE]
//				= LiteSpeed_Cache_Admin_Rules::get_instance()->scan_upgrade();
//		}

		$res = update_option(self::OPTION_NAME, $this->options) ;
		if (defined('LSCWP_LOG')) {
			LiteSpeed_Cache::debug_log(
				"plugin_upgrade option changed = $res\n");
		}
	}

	/**
	 * Upgrade network options when the plugin is upgraded.
	 *
	 * @since 1.0.11
	 * @access public
	 */
	public function plugin_site_upgrade()
	{
		$default_options = $this->get_default_site_options();
		$options = $this->get_site_options();

		if (($options[self::OPID_VERSION] == $default_options[self::OPID_VERSION])
				&& (count($default_options) == count($options))) {
			return;
		}

		self::option_diff($default_options, $options);

		$res = update_site_option(self::OPTION_NAME, $options);

		if (defined('LSCWP_LOG')) {
			LiteSpeed_Cache::debug_log("plugin_upgrade option changed = $res\n");
		}

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
			if ( defined('WP_CACHE') && constant('WP_CACHE') == true ) {
				return true ;
			}
		}
		elseif (! defined('WP_CACHE') || (defined('WP_CACHE') && constant('WP_CACHE') == false) ) {
				return true;
		}
		$file = ABSPATH . 'wp-config.php' ;
		if ( !is_writeable($file) ) {
			$file = dirname(ABSPATH) . '/wp-config.php';
			if ( !is_writeable($file) ) {
				error_log('wp-config file not writeable for \'WP_CACHE\'');
				return false;
			}
		}
		$file_content = file_get_contents($file) ;

		if ( $enable ) {
			$count = 0 ;

			$new_file_content = preg_replace('/[\/]*define\(.*\'WP_CACHE\'.+;/',
								"define('WP_CACHE', true);", $file_content, -1, $count) ;
			if ( $count == 0 ) {
				$new_file_content = preg_replace('/(\$table_prefix)/',
								"define('WP_CACHE', true);\n$1", $file_content) ;
			}
		}
		else {
			$new_file_content = preg_replace('/define\(.*\'WP_CACHE\'.+;/',
								"define('WP_CACHE', false);", $file_content) ;
		}

		file_put_contents($file, $new_file_content) ;
		return true ;
	}

	/**
	 * On plugin activation, load the default options.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int $count In multisite, the number of blogs active.
	 */
	public function plugin_activation($count)
	{
		$errors = array();
		$rules = LiteSpeed_Cache_Admin_Rules::get_instance();
		$default = $this->get_default_options();
		$res = add_option(self::OPTION_NAME, $default);
		if (defined('LSCWP_LOG')) {
			LiteSpeed_Cache::debug_log("plugin_activation update option = $res");
		}
		if (is_multisite()) {
			if (!is_network_admin()) {
				if ($count === 1) {
					$rules->validate_common_rewrites(array(), $errors);
				}
				return;
			}
			$options = $this->get_site_options();
			if (isset($options) && (is_string($options))) {
				$options = unserialize($options);
				update_site_option(self::OPTION_NAME, $options);
			}
			if (($res == true)
				|| (($options[self::NETWORK_OPID_ENABLED] == false))) {
				return;
			}
		}
		elseif (($res == false)
			&& (($this->get_option(self::OPID_ENABLED) == false))) {
			return;
		}
		else {
			$options = $this->get_options();
		}

		$default = self::get_rule_reset_options();

		if ($options[self::OPID_CACHE_FAVICON]) {
			$options['lscwp_' . self::OPID_CACHE_FAVICON]
				= self::OPID_CACHE_FAVICON;
		}
		if ($options[self::OPID_CACHE_RES]) {
			$options['lscwp_' . self::OPID_CACHE_RES] = self::OPID_CACHE_RES;
		}
		if ($options[self::OPID_MOBILEVIEW_ENABLED]) {
			$options['lscwp_' . self::OPID_MOBILEVIEW_ENABLED]
				= self::OPID_MOBILEVIEW_ENABLED;
		}

		$diff = $rules->check_input($default, $options, $errors);

        if (!empty($diff)) {
            $rules->validate_common_rewrites($diff, $errors);
        }

        if (!empty($errors)) {
			exit(implode("\n", $errors));
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
		if ( isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE']) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if caching is allowed, then if the plugin is enabled in configs.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return boolean True if enabled, false otherwise.
	 */
	public function is_plugin_enabled()
	{
		if (!$this->is_caching_allowed()) {
			return false;
		}
		elseif ((is_multisite()) && (is_network_admin())
			&& (current_user_can('manage_network_options'))) {
			return $this->options[self::NETWORK_OPID_ENABLED];
		}
		return $this->options[self::OPID_ENABLED];
	}

}
