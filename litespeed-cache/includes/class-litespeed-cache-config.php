<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class LiteSpeed_Cache_Config
{
	const OPTION_NAME = 'litespeed-cache-conf';

	const LOG_LEVEL_NONE = 0;
	const LOG_LEVEL_ERROR = 1;
	const LOG_LEVEL_WARN = 2;
	const LOG_LEVEL_INFO = 3;
	const LOG_LEVEL_DEBUG = 4;

	const OPID_VERSION = 'version';
	const OPID_ENABLED = 'enabled';
	const OPID_DEBUG = 'debug';
	const OPID_ADMIN_IPS = 'admin_ips';
	const OPID_PUBLIC_TTL = 'public_ttl';
	const OPID_NOCACHE_VARS = 'nocache_vars';
	const OPID_NOCACHE_PATH = 'nocache_path';
	const OPID_PURGE_BY_POST = 'purge_by_post';
	const OPID_TEST_IPS = 'test_ips';

	const PURGE_ALL_PAGES = '-';
	const PURGE_FRONT_PAGE = 'F';
	const PURGE_HOME_PAGE = 'H';
	const PURGE_AUTHOR = 'A';
	const PURGE_YEAR = 'Y';
	const PURGE_MONTH = 'M';
	const PURGE_DATE = 'D';
	const PURGE_TERM = 'T'; // include category|tag|tax
	const PURGE_POST_TYPE = 'PT';

	protected $options;
	protected $purge_options;
	protected $debug_tag = 'LiteSpeed_Cache';

	public function __construct()
	{
		$options = get_option(self::OPTION_NAME, $this->get_default_options());
		if ( is_multisite() && is_array($site_options = get_site_option(self::OPTION_NAME)) ) {
			$options = array_merge($options, $site_options); // Multisite network options.
		}
		$this->options = $options;
		$this->purge_options = explode('.', $options[self::OPID_PURGE_BY_POST]);

		if (true === WP_DEBUG /*&& $this->options[self::OPID_DEBUG] && $this->options[self::OPID_ENABLED]*/) {
			$msec = microtime();
			$msec1 = substr($msec, 2, strpos($msec, ' ') -2);
			$this->debug_tag .= ' [' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'] . ':' . $msec1 . '] ' ;
		}

	}

	public function get_options()
	{
		return $this->options;
	}

	public function get_option($id)
	{
		if (isset($this->options[$id]))
			return $this->options[$id];
		else {
			$this->debug_log('Invalid option ID ' . $id,  self::LOG_LEVEL_ERROR);
			return NULL;
		}
	}

	public function get_purge_options()
	{
		return $this->purge_options;
	}

	public function purge_by_post($flag)
	{
		return in_array($flag, $this->purge_options);
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
		);
		sort($default_purge_options);

		$default_options = array(
			self::OPID_VERSION => LiteSpeed_Cache::PLUGIN_VERSION,
			self::OPID_ENABLED => false,
			self::OPID_DEBUG => self::LOG_LEVEL_NONE,
			self::OPID_ADMIN_IPS => '127.0.0.1',
			self::OPID_TEST_IPS => '',
			self::OPID_PUBLIC_TTL => 28800,
			self::OPID_NOCACHE_VARS => '',
			self::OPID_NOCACHE_PATH => '',
			self::OPID_PURGE_BY_POST => implode('.', $default_purge_options)
		);

		return $default_options;
	}

	public function plugin_upgrade()
	{
		$default_options = $this->get_default_options();

		if (($this->options[self::OPID_VERSION] != $default_options[self::OPID_VERSION])
				|| (count($default_options) != count($this->options))) {
			$old_options = $this->options;
			$dkeys = array_keys($default_options);
			$keys = array_keys($this->options);
			$newkeys = array_diff($dkeys, $keys);
			$log = '';
			if (!empty($newkeys)) {
				foreach ($newkeys as $newkey) {
					$this->options[$newkey] = $default_options[$newkey];
					$log .= ' Added ' . $newkey . ' = ' . $default_options[$newkey];
				}
			}
			$retiredkeys = array_diff($keys, $dkeys);
			if (!empty($retiredkeys)) {
				foreach ($retiredkeys as $retired) {
					unset($this->options[$retired]);
					$log .= 'Removed ' . $retired;
				}
			}

			$res = update_option(self::OPTION_NAME, $this->options);
			$this->debug_log("plugin_upgrade option changed = $res $log\n", ($res ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR));
		}
	}


	public function plugin_activation()
	{
		$res = update_option(self::OPTION_NAME, $this->get_default_options());
		$this->debug_log("plugin_activation update option = $res", ($res ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR));
	}

	public function plugin_deactivation()
	{
		$res = delete_option(self::OPTION_NAME);
		$this->debug_log("plugin_deactivation option deleted = $res", ($res ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR));
	}

	public function debug_log($mesg, $log_level=self::LOG_LEVEL_DEBUG)
	{
		if ((true === WP_DEBUG)/* && ($log_level <= $this->options[self::OPID_DEBUG])*/) {
			error_log($this->debug_tag . $mesg);
		}
	}

	public function module_enabled()
	{
		$enabled = 0;
        if (isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE']) {
			$enabled = 1; // server module enabled

			if ($this->options[self::OPID_ENABLED]) {
				$enabled |= 2;
			}
		}
		return $enabled;

	}
}
