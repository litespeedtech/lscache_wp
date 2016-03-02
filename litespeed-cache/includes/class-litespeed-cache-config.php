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
	const OPID_DEBUG = 'debug' ;
	const OPID_ADMIN_IPS = 'admin_ips' ;
	const OPID_PUBLIC_TTL = 'public_ttl' ;
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

	protected $options ;
	protected $purge_options ;
	protected $debug_tag = 'LiteSpeed_Cache' ;

	public function __construct()
	{
		$options = get_option(self::OPTION_NAME, $this->get_default_options()) ;

		/* Later we'll see what needs to put in site_options, not used now
		 * if ( is_multisite() && is_array($site_options = get_site_option(self::OPTION_NAME)) ) {
		  $options = array_merge($options, $site_options); // Multisite network options.
		  } */
		$this->options = $options ;
		$this->purge_options = explode('.', $options[self::OPID_PURGE_BY_POST]) ;

		if ( true === WP_DEBUG /* && $this->options[self::OPID_DEBUG] && $this->options[self::OPID_ENABLED] */ ) {
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
		if ( isset($this->options[$id]) )
			return $this->options[$id] ;
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

		$default_options = array(
			self::OPID_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::OPID_ENABLED => false,
			self::OPID_DEBUG => self::LOG_LEVEL_NONE,
			self::OPID_ADMIN_IPS => '127.0.0.1',
			self::OPID_TEST_IPS => '',
			self::OPID_PUBLIC_TTL => 28800,
			self::OPID_NOCACHE_VARS => '',
			self::OPID_NOCACHE_PATH => '',
			self::OPID_PURGE_BY_POST => implode('.', $default_purge_options),
            self::OPID_EXCLUDES_URI => '',
            self::OPID_EXCLUDES_CAT => '',
            self::OPID_EXCLUDES_TAG => ''
				) ;

		return $default_options ;
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

	private function wp_cache_var_setter( $setting )
	{
		$file = ABSPATH . 'wp-config.php' ;
		if ( is_writeable($file) ) {
			$file_content = file_get_contents($file) ;

			if ( $setting == 'set' ) {
				$count = 0 ;

				$new_file_content = preg_replace('/define\(.*\'WP_CACHE\'.+;\n/m', "define('WP_CACHE', true);\n", $file_content, -1, $count) ;
				if ( $count == 0 ) {
					$new_file_content = preg_replace('/(\$table_prefix[^;]+;\n)/m', "$1\ndefine('WP_CACHE', true);\n", $file_content) ;
				}
			}
			elseif ( $setting == 'unset' ) {
				$new_file_content = preg_replace('/define\(.*\'WP_CACHE\'.+;\n/m', "define('WP_CACHE', false);\n", $file_content) ;
			}

			file_put_contents($file, $new_file_content) ;
			return true ;
		}
		return false ;
	}

	public function set_wp_cache_var()
	{
		if ( defined('WP_CACHE') && constant('WP_CACHE') == true ) {
			return true ;
		}
		else {
			$this->wp_cache_var_setter('set') ;
		}
	}

	public function unset_wp_cache_var()
	{
		if ( ! defined('WP_CACHE') || (defined('WP_CACHE') && constant('WP_CACHE') == false) ) {
			return true ;
		}
		else {
			return $this->wp_cache_var_setter('unset') ;
		}
	}

	public function plugin_activation()
	{
		$res = update_option(self::OPTION_NAME, $this->get_default_options()) ;
		$this->debug_log("plugin_activation update option = $res", ($res ? self::LOG_LEVEL_NOTICE : self::LOG_LEVEL_ERROR)) ;
	}

	public function plugin_deactivation()
	{
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

	public function module_enabled()
	{
		$enabled = 0 ;
		if ( isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE'] ) {
			$enabled = 1 ; // server module enabled

			if ( $this->options[self::OPID_ENABLED] ) {
				$enabled |= 2 ;
			}
		}
		return $enabled ;
	}

}
