<?php

/**
 * The avatar cache class
 *
 * @since       3.0
 * @package     LiteSpeed
 * @subpackage  LiteSpeed/inc
 * @author      LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Avatar extends Base {

	const TYPE_GENERATE = 'generate';

	private $_conf_cache_ttl;
	private $_tb;

	private $_avatar_realtime_gen_dict = array();
	protected $_summary;

	/**
	 * Init
	 *
	 * @since  1.4
	 */
	public function __construct() {
		if (!$this->conf(self::O_DISCUSS_AVATAR_CACHE)) {
			return;
		}

		Debug2::debug2('[Avatar] init');

		$this->_tb = $this->cls('Data')->tb('avatar');

		$this->_conf_cache_ttl = $this->conf(self::O_DISCUSS_AVATAR_CACHE_TTL);

		add_filter('get_avatar_url', array( $this, 'crawl_avatar' ));

		$this->_summary = self::get_summary();
	}

	/**
	 * Check if need db table or not
	 *
	 * @since 3.0
	 * @access public
	 */
	public function need_db() {
		if ($this->conf(self::O_DISCUSS_AVATAR_CACHE)) {
			return true;
		}

		return false;
	}
	/**
	 * Get gravatar URL from DB and regenerate
	 *
	 * @since  3.0
	 * @access public
	 */
	public function serve_static( $md5 ) {
		global $wpdb;

		Debug2::debug('[Avatar] is avatar request');

		if (strlen($md5) !== 32) {
			Debug2::debug('[Avatar] wrong md5 ' . $md5);
			return;
		}

		$q   = "SELECT url FROM `$this->_tb` WHERE md5=%s";
		$url = $wpdb->get_var($wpdb->prepare($q, $md5));

		if (!$url) {
			Debug2::debug('[Avatar] no matched url for md5 ' . $md5);
			return;
		}

		$url = $this->_generate($url);

		wp_redirect($url);
		exit();
	}

	/**
	 * Localize gravatar
	 *
	 * @since  3.0
	 * @access public
	 */
	public function crawl_avatar( $url ) {
		if (!$url) {
			return $url;
		}

		// Check if its already in dict or not
		if (!empty($this->_avatar_realtime_gen_dict[$url])) {
			Debug2::debug2('[Avatar] already in dict [url] ' . $url);

			return $this->_avatar_realtime_gen_dict[$url];
		}

		$realpath = $this->_realpath($url);
		if (file_exists($realpath) && time() - filemtime($realpath) <= $this->_conf_cache_ttl) {
			Debug2::debug2('[Avatar] cache file exists [url] ' . $url);
			return $this->_rewrite($url, filemtime($realpath));
		}

		if (!strpos($url, 'gravatar.com')) {
			return $url;
		}

		// Send request
		if (!empty($this->_summary['curr_request']) && time() - $this->_summary['curr_request'] < 300) {
			Debug2::debug2('[Avatar] Bypass generating due to interval limit [url] ' . $url);
			return $url;
		}

		// Generate immediately
		$this->_avatar_realtime_gen_dict[$url] = $this->_generate($url);

		return $this->_avatar_realtime_gen_dict[$url];
	}

	/**
	 * Read last time generated info
	 *
	 * @since  3.0
	 * @access public
	 */
	public function queue_count() {
		global $wpdb;

		// If var not exists, mean table not exists // todo: not true
		if (!$this->_tb) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb` WHERE dateline<" . (time() - $this->_conf_cache_ttl);
		return $wpdb->get_var($q);
	}

	/**
	 * Get the final URL of local avatar
	 *
	 * Check from db also
	 *
	 * @since  3.0
	 */
	private function _rewrite( $url, $time = null ) {
		return LITESPEED_STATIC_URL . '/avatar/' . $this->_filepath($url) . ($time ? '?ver=' . $time : '');
	}

	/**
	 * Generate realpath of the cache file
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _realpath( $url ) {
		return LITESPEED_STATIC_DIR . '/avatar/' . $this->_filepath($url);
	}

	/**
	 * Get filepath
	 *
	 * @since  4.0
	 */
	private function _filepath( $url ) {
		$filename = md5($url) . '.jpg';
		if (is_multisite()) {
			$filename = get_current_blog_id() . '/' . $filename;
		}
		return $filename;
	}

	/**
	 * Cron generation
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function cron( $force = false ) {
		global $wpdb;

		$_instance = self::cls();
		if (!$_instance->queue_count()) {
			Debug2::debug('[Avatar] no queue');
			return;
		}

		// For cron, need to check request interval too
		if (!$force) {
			if (!empty($_instance->_summary['curr_request']) && time() - $_instance->_summary['curr_request'] < 300) {
				Debug2::debug('[Avatar] curr_request too close');
				return;
			}
		}

		$q = "SELECT url FROM `$_instance->_tb` WHERE dateline < %d ORDER BY id DESC LIMIT %d";
		$q = $wpdb->prepare($q, array( time() - $_instance->_conf_cache_ttl, apply_filters('litespeed_avatar_limit', 30) ));

		$list = $wpdb->get_results($q);
		Debug2::debug('[Avatar] cron job [count] ' . count($list));

		foreach ($list as $v) {
			Debug2::debug('[Avatar] cron job [url] ' . $v->url);

			$_instance->_generate($v->url);
		}
	}

	/**
	 * Remote generator
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _generate( $url ) {
		global $wpdb;

		// Record the data

		$file = $this->_realpath($url);

		// Update request status
		self::save_summary(array( 'curr_request' => time() ));

		// Generate
		$this->_maybe_mk_cache_folder('avatar');

		$response = wp_safe_remote_get($url, array(
			'timeout' => 180,
			'stream' => true,
			'filename' => $file,
		));

		Debug2::debug('[Avatar] _generate [url] ' . $url);

		// Parse response data
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			file_exists($file) && unlink($file);
			Debug2::debug('[Avatar] failed to get: ' . $error_message);
			return $url;
		}

		// Save summary data
		self::save_summary(array(
			'last_spent' => time() - $this->_summary['curr_request'],
			'last_request' => $this->_summary['curr_request'],
			'curr_request' => 0,
		));

		// Update DB
		$md5     = md5($url);
		$q       = "UPDATE `$this->_tb` SET dateline=%d WHERE md5=%s";
		$existed = $wpdb->query($wpdb->prepare($q, array( time(), $md5 )));
		if (!$existed) {
			$q = "INSERT INTO `$this->_tb` SET url=%s, md5=%s, dateline=%d";
			$wpdb->query($wpdb->prepare($q, array( $url, $md5, time() )));
		}

		Debug2::debug('[Avatar] saved avatar ' . $file);

		return $this->_rewrite($url);
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_GENERATE:
            self::cron(true);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
