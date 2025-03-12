<?php

/**
 * The quic.cloud class.
 *
 * @since      	2.4.1
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/src/cdn
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed\CDN;

use LiteSpeed\Cloud;
use LiteSpeed\Base;

defined('WPINC') || exit();

class Quic extends Base
{
	const LOG_TAG = '☁️';

	const TYPE_REG = 'reg';

	protected $_summary;
	public function __construct()
	{
		$this->_summary = self::get_summary();
	}

	/**
	 * Notify CDN new config updated
	 *
	 * @access public
	 */
	public static function try_sync_config()
	{
		self::cls()->try_sync_conf();
	}

	public function try_sync_conf($force = false)
	{
		$options = $this->get_options();

		if (!$options[self::O_CDN_QUIC]) {
			if (!empty($this->_summary['conf_md5'])) {
				self::save_summary(array('conf_md5' => ''));
			}
			return false;
		}

		// Security: Remove cf key in report
		$secure_fields = array(self::O_CDN_CLOUDFLARE_KEY, self::O_OBJECT_PSWD);
		foreach ($secure_fields as $v) {
			if (!empty($options[$v])) {
				$options[$v] = str_repeat('*', strlen($options[$v]));
			}
		}
		unset($options[self::O_MEDIA_LQIP_EXC]);
		unset($options[self::O_API_KEY]);
		unset($options[self::_VER]);

		// Remove overflow multi lines fields
		foreach ($options as $k => $v) {
			if (is_array($v) && count($v) > 30) {
				$v = array_slice($v, 0, 30);
				$options[$k] = $v;
			}
		}

		// Rest url
		$options['_rest'] = function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : apply_filters('rest_url_prefix', 'wp-json');
		$options['_home_url'] = home_url('/');

		// Append hooks
		$options['_tp_cookies'] = apply_filters('litespeed_vary_cookies', array());

		// Build necessary options only
		$options_needed = array(
			self::O_CACHE_DROP_QS,
			self::O_CACHE_EXC_COOKIES,
			self::O_CACHE_EXC_USERAGENTS,
			self::O_CACHE_FAVICON,
			self::O_CACHE_LOGIN_COOKIE,
			self::O_CACHE_VARY_COOKIES,
			self::O_CACHE_MOBILE_RULES,
			self::O_CACHE_MOBILE,
			self::O_CACHE_RES,
			self::O_CACHE_BROWSER,
			self::O_CACHE_TTL_BROWSER,
			self::O_IMG_OPTM_WEBP,
			self::O_GUEST,
			self::O_GUEST_OPTM,
			'_tp_cookies',
		);
		$consts_needed = array(
			'WP_CONTENT_DIR',
			'LSCWP_CONTENT_DIR',
			'LSCWP_CONTENT_FOLDER',
			'LSWCP_TAG_PREFIX',
		);
		$options_for_md5 = array();
		foreach ($options_needed as $v) {
			if (isset($options[$v])) {
				$options_for_md5[$v] = $options[$v];
			}
		}
		$server_vars = $this->server_vars();
		foreach ($consts_needed as $v) {
			if (isset($server_vars[$v])) {
				if (!is_array($options_for_md5['_server'])) {
					$options_for_md5['_server'] = array();
				}
				$options_for_md5['_server'][$v] = $server_vars[$v];
			}
		}

		$conf_md5 = md5(\json_encode($options_for_md5));
		if (!empty($this->_summary['conf_md5'])) {
			if ($conf_md5 == $this->_summary['conf_md5']) {
				if (!$force) {
					self::debug('Bypass sync conf to QC due to same md5', $conf_md5);
					return;
				}
				self::debug('!!!Force sync conf even same md5');
			} else {
				self::debug('[conf_md5] ' . $conf_md5 . ' [existing_conf_md5] ' . $this->_summary['conf_md5']);
			}
		}

		self::save_summary(array('conf_md5' => $conf_md5));
		self::debug('sync conf to QC', $options_for_md5);

		Cloud::post(Cloud::SVC_D_SYNC_CONF, $options_for_md5);
	}
}
