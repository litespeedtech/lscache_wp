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
	const OPID_CACHE_COMMENTERS = 'cache_commenters';
	const OPID_MOBILEVIEW_ENABLED = 'mobileview_enabled';
	// do NOT set default options for these three, it is used for admin.
	const ID_MOBILEVIEW_LIST = 'mobileview_rules';
	const ID_NOCACHE_COOKIES = 'nocache_cookies' ;
	const ID_NOCACHE_USERAGENTS = 'nocache_useragents' ;
	const OPID_DEBUG = 'debug' ;
	const OPID_ADMIN_IPS = 'admin_ips' ;
	const OPID_PUBLIC_TTL = 'public_ttl' ;
	const OPID_FRONT_PAGE_TTL = 'front_page_ttl';
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

	const OPID_EXCLUDES_COOKIE = 'excludes_cookie' ;
	const OPID_EXCLUDES_USERAGENT = 'excludes_useragent' ;

	const NETWORK_OPID_ENABLED = 'network_enabled';
	const NETWORK_OPID_CNT = 'network_enabled_count';

	protected $options ;
	protected $purge_options ;
	protected $debug_tag = 'LiteSpeed_Cache' ;

	public function __construct()
	{
		$options = get_option(self::OPTION_NAME, $this->get_default_options()) ;

		if ( is_multisite()) {
			$site_options = get_site_option(self::OPTION_NAME);
			if ( $site_options && is_array($site_options)) {
				$options[self::NETWORK_OPID_ENABLED] = $site_options[self::NETWORK_OPID_ENABLED];
				if ($options[self::OPID_ENABLED_RADIO] == self::OPID_ENABLED_NOTSET) {
					$options[self::OPID_ENABLED] = $options[self::NETWORK_OPID_ENABLED];
				}
			}
		}
		$this->options = $options ;
		$this->purge_options = explode('.', $options[self::OPID_PURGE_BY_POST]) ;

		if ( true === WP_DEBUG /* && $this->options[self::OPID_DEBUG] */ ) {
			$msec = microtime() ;
			$msec1 = substr($msec, 2, strpos($msec, ' ') - 2) ;
			if ( array_key_exists('REMOTE_ADDR', $_SERVER) && array_key_exists('REMOTE_PORT', $_SERVER) ) {
				$this->debug_tag .= ' [' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'] . ':' . $msec1 . '] ' ;
			}
		}
	}

	public function get_options()
	{
		return $this->options ;
	}

	public function get_option( $id )
	{
		if ( isset($this->options[$id]) ) {
			return $this->options[$id] ;
		}
		else {
			$this->debug_log('Invalid option ID ' . $id, self::LOG_LEVEL_ERROR) ;
			return NULL ;
		}
	}

	public function get_purge_options()
	{
		return $this->purge_options ;
	}

	public function purge_by_post( $flag )
	{
		return in_array($flag, $this->purge_options) ;
	}

	protected function get_default_options()
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
			self::OPID_CACHE_COMMENTERS => true,
			self::OPID_MOBILEVIEW_ENABLED => false,
			self::OPID_DEBUG => self::LOG_LEVEL_NONE,
			self::OPID_ADMIN_IPS => '127.0.0.1',
			self::OPID_TEST_IPS => '',
			self::OPID_PUBLIC_TTL => 28800,
			self::OPID_FRONT_PAGE_TTL => 1800,
			self::OPID_NOCACHE_VARS => '',
			self::OPID_NOCACHE_PATH => '',
			self::OPID_PURGE_BY_POST => implode('.', $default_purge_options),
			self::OPID_EXCLUDES_URI => '',
			self::OPID_EXCLUDES_CAT => '',
			self::OPID_EXCLUDES_TAG => '',
			self::OPID_EXCLUDES_COOKIE => '',
			self::OPID_EXCLUDES_USERAGENT => '',
				) ;

		return $default_options ;
	}

	public function get_site_options()
	{
		if (!is_multisite()) {
			return null;
		}
		$site_options = get_site_option(self::OPTION_NAME);
		if ( isset($site_options) && is_array($site_options)) {
			return $site_options;
		}
		$default_site_options = array(
			self::NETWORK_OPID_ENABLED => false,
			self::NETWORK_OPID_CNT => 0,
			self::OPID_MOBILEVIEW_ENABLED => 0,
			self::OPID_EXCLUDES_COOKIE => '',
			self::OPID_EXCLUDES_USERAGENT => '',
				);
		add_site_option(self::OPTION_NAME, $default_site_options);
		return $default_site_options;
	}

	public function plugin_upgrade()
	{
		$default_options = $this->get_default_options() ;

		if ( ($this->options[self::OPID_VERSION] != $default_options[self::OPID_VERSION]) || (count($default_options) != count($this->options)) ) {
			$old_options = $this->options ;
			$dkeys = array_keys($default_options) ;
			$keys = array_keys($this->options) ;
			$newkeys = array_diff($dkeys, $keys) ;
			$log = '' ;
			if ( ! empty($newkeys) ) {
				foreach ( $newkeys as $newkey ) {
					$this->options[$newkey] = $default_options[$newkey] ;
					$log .= ' Added ' . $newkey . ' = ' . $default_options[$newkey] ;
				}
			}
			$retiredkeys = array_diff($keys, $dkeys) ;
			if ( ! empty($retiredkeys) ) {
				foreach ( $retiredkeys as $retired ) {
					unset($this->options[$retired]) ;
					$log .= 'Removed ' . $retired ;
				}
			}

			$res = update_option(self::OPTION_NAME, $this->options) ;
			$this->debug_log("plugin_upgrade option changed = $res $log\n", ($res ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR)) ;
		}
	}

	public function wp_cache_var_setter( $enable )
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
			$this->debug_log('wp-config file not writeable for \'WP_CACHE\'');
			return false;
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

	public function incr_multi_enabled() {
		$site_options = $this->get_site_options();
		$count = $site_options[LiteSpeed_Cache_Config::NETWORK_OPID_CNT];
		++$count;
		$site_options[LiteSpeed_Cache_Config::NETWORK_OPID_CNT] = $count;
		update_site_option(LiteSpeed_Cache_Config::OPTION_NAME, $site_options);
		return $count;
	}

	public function decr_multi_enabled() {
		$site_options = $this->get_site_options();
		if ( !site_options) {
			$this->config->debug_log('LSCWP Enabled Count does not exist');
			exit(__("LSCWP Enabled Count does not exist", 'litespeed-cache'));
		}
		$count = $site_options[LiteSpeed_Cache_Config::NETWORK_OPID_CNT];
		--$count;
		$site_options[LiteSpeed_Cache_Config::NETWORK_OPID_CNT] = $count;
		update_site_option(LiteSpeed_Cache_Config::OPTION_NAME, $site_options);
		return $count;
	}

	public function plugin_activation()
	{
		$res = update_option(self::OPTION_NAME, $this->get_default_options()) ;
		$this->debug_log("plugin_activation update option = $res", ($res ? self::LOG_LEVEL_NOTICE : self::LOG_LEVEL_ERROR)) ;
	}

	public function plugin_deactivation()
	{
		if ((!is_multisite()) || (is_network_admin())) {
			LiteSpeed_Cache_Admin::clear_htaccess();
		}
		$res = delete_option(self::OPTION_NAME) ;
		$this->debug_log("plugin_deactivation option deleted = $res", ($res ? self::LOG_LEVEL_NOTICE : self::LOG_LEVEL_ERROR)) ;
	}

	public function debug_log( $mesg, $log_level = self::LOG_LEVEL_DEBUG )
	{
		if ( (true === WP_DEBUG) && ($log_level <= $this->options[self::OPID_DEBUG]) ) {
			$tag = '[' ;
			if ( self::LOG_LEVEL_ERROR == $log_level )
				$tag .= 'ERROR' ;
			elseif ( self::LOG_LEVEL_NOTICE == $log_level )
				$tag .= 'NOTICE' ;
			elseif ( self::LOG_LEVEL_INFO == $log_level )
				$tag .= 'INFO' ;
			else
				$tag .= 'DEBUG' ;

			$tag .= '] ' . $this->debug_tag ;
			error_log($tag . $mesg) ;
		}
	}

	public function is_caching_allowed() {
		if ( isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE']) {
			return true;
		}
		return false;
	}

	public function is_plugin_enabled() {
		if ( $this->is_caching_allowed() && ($this->options[self::OPID_ENABLED])) {
			return true;
		}
		return false;
	}

}
