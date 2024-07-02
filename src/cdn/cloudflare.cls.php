<?php
/**
 * The cloudflare CDN class.
 *
 * @since      	2.1
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/src/cdn
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\CDN;

use LiteSpeed\Core;
use LiteSpeed\Base;
use LiteSpeed\Debug2;
use LiteSpeed\Router;
use LiteSpeed\Admin;
use LiteSpeed\Admin_Display;

defined('WPINC') || exit();

class Cloudflare extends Base
{
	const TYPE_PURGE_ALL = 'purge_all';
	const TYPE_GET_DEVMODE = 'get_devmode';
	const TYPE_SET_DEVMODE_ON = 'set_devmode_on';
	const TYPE_SET_DEVMODE_OFF = 'set_devmode_off';

	const ITEM_STATUS = 'status';

	/**
	 * Update zone&name based on latest settings
	 *
	 * @since  3.0
	 * @access public
	 */
	public function try_refresh_zone()
	{
		if (!$this->conf(self::O_CDN_CLOUDFLARE)) {
			return;
		}

		$zone = $this->_fetch_zone();
		if ($zone) {
			$this->cls('Conf')->update(self::O_CDN_CLOUDFLARE_NAME, $zone['name']);

			$this->cls('Conf')->update(self::O_CDN_CLOUDFLARE_ZONE, $zone['id']);

			Debug2::debug("[Cloudflare] Get zone successfully \t\t[ID] $zone[id]");
		} else {
			$this->cls('Conf')->update(self::O_CDN_CLOUDFLARE_ZONE, '');
			Debug2::debug('[Cloudflare] ❌ Get zone failed, clean zone');
		}
	}

	/**
	 * Get Cloudflare development mode
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _get_devmode($show_msg = true)
	{
		Debug2::debug('[Cloudflare] _get_devmode');

		$zone = $this->_zone();
		if (!$zone) {
			return;
		}

		$url = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/settings/development_mode';
		$res = $this->_cloudflare_call($url, 'GET', false, $show_msg);

		if (!$res) {
			return;
		}
		Debug2::debug('[Cloudflare] _get_devmode result ', $res);

		$curr_status = self::get_option(self::ITEM_STATUS, array());
		$curr_status['devmode'] = $res['value'];
		$curr_status['devmode_expired'] = $res['time_remaining'] + time();

		// update status
		self::update_option(self::ITEM_STATUS, $curr_status);
	}

	/**
	 * Set Cloudflare development mode
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _set_devmode($type)
	{
		Debug2::debug('[Cloudflare] _set_devmode');

		$zone = $this->_zone();
		if (!$zone) {
			return;
		}

		$url = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/settings/development_mode';
		$new_val = $type == self::TYPE_SET_DEVMODE_ON ? 'on' : 'off';
		$data = array('value' => $new_val);
		$res = $this->_cloudflare_call($url, 'PATCH', $data);

		if (!$res) {
			return;
		}

		$res = $this->_get_devmode(false);

		if ($res) {
			$msg = sprintf(__('Notified Cloudflare to set development mode to %s successfully.', 'litespeed-cache'), strtoupper($new_val));
			Admin_Display::succeed($msg);
		}
	}

	/**
	 * Purge Cloudflare cache
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _purge_all()
	{
		Debug2::debug('[Cloudflare] _purge_all');

		$cf_on = $this->conf(self::O_CDN_CLOUDFLARE);
		if (!$cf_on) {
			$msg = __('Cloudflare API is set to off.', 'litespeed-cache');
			Admin_Display::error($msg);
			return;
		}

		$zone = $this->_zone();
		if (!$zone) {
			return;
		}

		$url = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/purge_cache';
		$data = array('purge_everything' => true);

		$res = $this->_cloudflare_call($url, 'DELETE', $data);

		if ($res) {
			$msg = __('Notified Cloudflare to purge all successfully.', 'litespeed-cache');
			Admin_Display::succeed($msg);
		}
	}

	/**
	 * Get current Cloudflare zone from cfg
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _zone()
	{
		$zone = $this->conf(self::O_CDN_CLOUDFLARE_ZONE);
		if (!$zone) {
			$msg = __('No available Cloudflare zone', 'litespeed-cache');
			Admin_Display::error($msg);
			return false;
		}

		return $zone;
	}

	/**
	 * Get Cloudflare zone settings
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _fetch_zone()
	{
		$kw = $this->conf(self::O_CDN_CLOUDFLARE_NAME);

		$url = 'https://api.cloudflare.com/client/v4/zones?status=active&match=all';

		// Try exact match first
		if ($kw && strpos($kw, '.')) {
			$zones = $this->_cloudflare_call($url . '&name=' . $kw, 'GET', false, false);
			if ($zones) {
				Debug2::debug('[Cloudflare] fetch_zone exact matched');
				return $zones[0];
			}
		}

		// Can't find, try to get default one
		$zones = $this->_cloudflare_call($url, 'GET', false, false);

		if (!$zones) {
			Debug2::debug('[Cloudflare] fetch_zone no zone');
			return false;
		}

		if (!$kw) {
			Debug2::debug('[Cloudflare] fetch_zone no set name, use first one by default');
			return $zones[0];
		}

		foreach ($zones as $v) {
			if (strpos($v['name'], $kw) !== false) {
				Debug2::debug('[Cloudflare] fetch_zone matched ' . $kw . ' [name] ' . $v['name']);
				return $v;
			}
		}

		// Can't match current name, return default one
		Debug2::debug('[Cloudflare] fetch_zone failed match name, use first one by default');
		return $zones[0];
	}

	/**
	 * Cloudflare API
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function _cloudflare_call($url, $method = 'GET', $data = false, $show_msg = true)
	{
		Debug2::debug("[Cloudflare] _cloudflare_call \t\t[URL] $url");

		if (40 == strlen($this->conf(self::O_CDN_CLOUDFLARE_KEY))) {
			$headers = array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->conf(self::O_CDN_CLOUDFLARE_KEY),
			);
		} else {
			$headers = array(
				'Content-Type' => 'application/json',
				'X-Auth-Email' => $this->conf(self::O_CDN_CLOUDFLARE_EMAIL),
				'X-Auth-Key' => $this->conf(self::O_CDN_CLOUDFLARE_KEY),
			);
		}

		$wp_args = array(
			'method' => $method,
			'headers' => $headers,
		);

		if ($data) {
			if (is_array($data)) {
				$data = \json_encode($data);
			}
			$wp_args['body'] = $data;
		}
		$resp = wp_remote_request($url, $wp_args);
		if (is_wp_error($resp)) {
			Debug2::debug('[Cloudflare] error in response');
			if ($show_msg) {
				$msg = __('Failed to communicate with Cloudflare', 'litespeed-cache');
				Admin_Display::error($msg);
			}
			return false;
		}

		$result = wp_remote_retrieve_body($resp);

		$json = \json_decode($result, true);

		if ($json && $json['success'] && $json['result']) {
			Debug2::debug('[Cloudflare] _cloudflare_call called successfully');
			if ($show_msg) {
				$msg = __('Communicated with Cloudflare successfully.', 'litespeed-cache');
				Admin_Display::succeed($msg);
			}

			return $json['result'];
		}

		Debug2::debug("[Cloudflare] _cloudflare_call called failed: $result");
		if ($show_msg) {
			$msg = __('Failed to communicate with Cloudflare', 'litespeed-cache');
			Admin_Display::error($msg);
		}

		return false;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.7.2
	 * @access public
	 */
	public function handler()
	{
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_PURGE_ALL:
				$this->_purge_all();
				break;

			case self::TYPE_GET_DEVMODE:
				$this->_get_devmode();
				break;

			case self::TYPE_SET_DEVMODE_ON:
			case self::TYPE_SET_DEVMODE_OFF:
				$this->_set_devmode($type);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
