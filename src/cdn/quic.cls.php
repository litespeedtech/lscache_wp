<?php

/**
 * The quic.cloud class.
 *
 * @since       2.4.1
 * @package     LiteSpeed
 * @subpackage  LiteSpeed/src/cdn
 * @author      LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed\CDN;

use LiteSpeed\Cloud;
use LiteSpeed\Base;

defined('WPINC') || exit();

class Quic extends Base {

	const LOG_TAG = '☁️';

	const TYPE_REG = 'reg';

	protected $_summary;
	private $_force = false;
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Notify CDN new config updated
	 *
	 * @access public
	 */
	public function try_sync_conf( $force = false ) {
		if ($force) {
			$this->_force = $force;
		}

		if (!$this->conf(self::O_CDN_QUIC)) {
			if (!empty($this->_summary['conf_md5'])) {
				self::debug('❌ No QC CDN, clear conf md5!');
				self::save_summary(array( 'conf_md5' => '' ));
			}
			return false;
		}

		// Notice: Sync conf must be after `wp_loaded` hook, to get 3rd party vary injected (e.g. `woocommerce_cart_hash`).
		if (!did_action('wp_loaded')) {
			add_action('wp_loaded', array( $this, 'try_sync_conf' ), 999);
			self::debug('WP not loaded yet, delay sync to wp_loaded:999');
			return;
		}

		$options                = $this->get_options();
		$options['_tp_cookies'] = apply_filters('litespeed_vary_cookies', array());

		// Build necessary options only
		$options_needed  = array(
			self::O_CACHE_DROP_QS,
			self::O_CACHE_EXC_COOKIES,
			self::O_CACHE_EXC_USERAGENTS,
			self::O_CACHE_LOGIN_COOKIE,
			self::O_CACHE_VARY_COOKIES,
			self::O_CACHE_MOBILE_RULES,
			self::O_CACHE_MOBILE,
			self::O_CACHE_BROWSER,
			self::O_CACHE_TTL_BROWSER,
			self::O_IMG_OPTM_WEBP,
			self::O_GUEST,
			'_tp_cookies',
		);
		$consts_needed   = array( 'LSWCP_TAG_PREFIX' );
		$options_for_md5 = array();
		foreach ($options_needed as $v) {
			if (isset($options[$v])) {
				$options_for_md5[$v] = $options[$v];
				// Remove overflow multi lines fields
				if (is_array($options_for_md5[$v]) && count($options_for_md5[$v]) > 30) {
					$options_for_md5[$v] = array_slice($options_for_md5[$v], 0, 30);
				}
			}
		}

		$server_vars = $this->server_vars();
		foreach ($consts_needed as $v) {
			if (isset($server_vars[$v])) {
				if (empty($options_for_md5['_server'])) {
					$options_for_md5['_server'] = array();
				}
				$options_for_md5['_server'][$v] = $server_vars[$v];
			}
		}

		$conf_md5 = md5(\json_encode($options_for_md5));
		if (!empty($this->_summary['conf_md5'])) {
			if ($conf_md5 == $this->_summary['conf_md5']) {
				if (!$this->_force) {
					self::debug('Bypass sync conf to QC due to same md5', $conf_md5);
					return;
				}
				self::debug('!!!Force sync conf even same md5');
			} else {
				self::debug('[conf_md5] ' . $conf_md5 . ' [existing_conf_md5] ' . $this->_summary['conf_md5']);
			}
		}

		self::save_summary(array( 'conf_md5' => $conf_md5 ));
		self::debug('sync conf to QC');

		Cloud::post(Cloud::SVC_D_SYNC_CONF, $options_for_md5);
	}
}
