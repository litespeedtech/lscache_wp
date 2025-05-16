<?php

/**
 * The plugin logging class.
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Debug2 extends Root {

	private static $log_path;
	private static $log_path_prefix;
	private static $_prefix;

	const TYPE_CLEAR_LOG = 'clear_log';
	const TYPE_BETA_TEST = 'beta_test';

	const BETA_TEST_URL = 'beta_test_url';

	const BETA_TEST_URL_WP = 'https://downloads.wordpress.org/plugin/litespeed-cache.zip';

	/**
	 * Log class Confructor
	 *
	 * NOTE: in this process, until last step ( define const LSCWP_LOG = true ), any usage to WP filter will not be logged to prevent infinite loop with log_filters()
	 *
	 * @since 1.1.2
	 * @access public
	 */
	public function __construct() {
		self::$log_path_prefix = LITESPEED_STATIC_DIR . '/debug/';
		// Maybe move legacy log files
		$this->_maybe_init_folder();

		self::$log_path = $this->path('debug');
		if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'lscache_') === 0) {
			self::$log_path = $this->path('crawler');
		}

		!defined('LSCWP_LOG_TAG') && define('LSCWP_LOG_TAG', get_current_blog_id());

		if ($this->conf(Base::O_DEBUG_LEVEL)) {
			!defined('LSCWP_LOG_MORE') && define('LSCWP_LOG_MORE', true);
		}

		defined('LSCWP_DEBUG_EXC_STRINGS') || define('LSCWP_DEBUG_EXC_STRINGS', $this->conf(Base::O_DEBUG_EXC_STRINGS));
	}

	/**
	 * Try moving legacy logs into /litespeed/debug/ folder
	 *
	 * @since 6.5
	 */
	private function _maybe_init_folder() {
		if (file_exists(self::$log_path_prefix . 'index.php')) {
			return;
		}
		file::save(self::$log_path_prefix . 'index.php', '<?php // Silence is golden.', true);

		$logs = array( 'debug', 'debug.purge', 'crawler' );
		foreach ($logs as $log) {
			if (file_exists(LSCWP_CONTENT_DIR . '/' . $log . '.log') && !file_exists($this->path($log))) {
				rename(LSCWP_CONTENT_DIR . '/' . $log . '.log', $this->path($log));
			}
		}
	}

	/**
	 * Generate log file path
	 *
	 * @since 6.5
	 */
	public function path( $type ) {
		return self::$log_path_prefix . self::FilePath($type);
	}

	/**
	 * Generate the fixed log filename
	 *
	 * @since 6.5
	 */
	public static function FilePath( $type ) {
		if ($type == 'debug.purge') {
			$type = 'purge';
		}
		$key  = defined('AUTH_KEY') ? AUTH_KEY : md5(__FILE__);
		$rand = substr(md5(substr($key, -16)), -16);
		return $type . $rand . '.log';
	}

	/**
	 * End call of one request process
	 *
	 * @since 4.7
	 * @access public
	 */
	public static function ended() {
		$headers = headers_list();
		foreach ($headers as $key => $header) {
			if (stripos($header, 'Set-Cookie') === 0) {
				unset($headers[$key]);
			}
		}
		self::debug('Response headers', $headers);

		$elapsed_time = number_format((microtime(true) - LSCWP_TS_0) * 1000, 2);
		self::debug("End response\n--------------------------------------------------Duration: " . $elapsed_time . " ms------------------------------\n");
	}

	/**
	 * Beta test upgrade
	 *
	 * @since 2.9.5
	 * @access public
	 */
	public function beta_test( $zip = false ) {
		if (!$zip) {
			if (empty($_REQUEST[self::BETA_TEST_URL])) {
				return;
			}

			$zip = $_REQUEST[self::BETA_TEST_URL];
			if ($zip !== self::BETA_TEST_URL_WP) {
				if ($zip === 'latest') {
					$zip = self::BETA_TEST_URL_WP;
				} else {
					// Generate zip url
					$zip = $this->_package_zip($zip);
				}
			}
		}

		if (!$zip) {
			self::debug('[Debug2] ❌  No ZIP file');
			return;
		}

		self::debug('[Debug2] ZIP file ' . $zip);

		$update_plugins = get_site_transient('update_plugins');
		if (!is_object($update_plugins)) {
			$update_plugins = new \stdClass();
		}

		$plugin_info              = new \stdClass();
		$plugin_info->new_version = Core::VER;
		$plugin_info->slug        = Core::PLUGIN_NAME;
		$plugin_info->plugin      = Core::PLUGIN_FILE;
		$plugin_info->package     = $zip;
		$plugin_info->url         = 'https://wordpress.org/plugins/litespeed-cache/';

		$update_plugins->response[Core::PLUGIN_FILE] = $plugin_info;

		set_site_transient('update_plugins', $update_plugins);

		// Run upgrade
		Activation::cls()->upgrade();
	}

	/**
	 * Git package refresh
	 *
	 * @since  2.9.5
	 * @access private
	 */
	private function _package_zip( $commit ) {
		$data = array(
			'commit' => $commit,
		);
		$res  = Cloud::get(Cloud::API_BETA_TEST, $data);

		if (empty($res['zip'])) {
			return false;
		}

		return $res['zip'];
	}

	/**
	 * Log Purge headers separately
	 *
	 * @since 2.7
	 * @access public
	 */
	public static function log_purge( $purge_header ) {
		// Check if debug is ON
		if (!defined('LSCWP_LOG') && !defined('LSCWP_LOG_BYPASS_NOTADMIN')) {
			return;
		}

		$purge_file = self::cls()->path('purge');

		self::cls()->_init_request($purge_file);

		$msg = $purge_header . self::_backtrace_info(6);

		File::append($purge_file, self::format_message($msg));
	}

	/**
	 * Enable debug log
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function init() {
		$debug = $this->conf(Base::O_DEBUG);
		if ($debug == Base::VAL_ON2) {
			if (!$this->cls('Router')->is_admin_ip()) {
				defined('LSCWP_LOG_BYPASS_NOTADMIN') || define('LSCWP_LOG_BYPASS_NOTADMIN', true);
				return;
			}
		}

		/**
		 * Check if hit URI includes/excludes
		 * This is after LSCWP_LOG_BYPASS_NOTADMIN to make `log_purge()` still work
		 *
		 * @since  3.0
		 */
		$list = $this->conf(Base::O_DEBUG_INC);
		if ($list) {
			$result = Utility::str_hit_array($_SERVER['REQUEST_URI'], $list);
			if (!$result) {
				return;
			}
		}

		$list = $this->conf(Base::O_DEBUG_EXC);
		if ($list) {
			$result = Utility::str_hit_array($_SERVER['REQUEST_URI'], $list);
			if ($result) {
				return;
			}
		}

		if (!defined('LSCWP_LOG')) {
			// If not initialized, do it now
			$this->_init_request();
			define('LSCWP_LOG', true);
		}
	}

	/**
	 * Create the initial log messages with the request parameters.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _init_request( $log_file = null ) {
		if (!$log_file) {
			$log_file = self::$log_path;
		}

		// Check log file size
		$log_file_size = $this->conf(Base::O_DEBUG_FILESIZE);
		if (file_exists($log_file) && filesize($log_file) > $log_file_size * 1000000) {
			File::save($log_file, '');
		}

		// For more than 2s's requests, add more break
		if (file_exists($log_file) && time() - filemtime($log_file) > 2) {
			File::append($log_file, "\n\n\n\n");
		}

		if (PHP_SAPI == 'cli') {
			return;
		}

		$servervars = array(
			'Query String' => '',
			'HTTP_ACCEPT' => '',
			'HTTP_USER_AGENT' => '',
			'HTTP_ACCEPT_ENCODING' => '',
			'HTTP_COOKIE' => '',
			'REQUEST_METHOD' => '',
			'SERVER_PROTOCOL' => '',
			'X-LSCACHE' => '',
			'LSCACHE_VARY_COOKIE' => '',
			'LSCACHE_VARY_VALUE' => '',
			'ESI_CONTENT_TYPE' => '',
		);
		$server     = array_merge($servervars, $_SERVER);
		$params     = array();

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$server['SERVER_PROTOCOL'] .= ' (HTTPS) ';
		}

		$param = sprintf('💓 ------%s %s %s', $server['REQUEST_METHOD'], $server['SERVER_PROTOCOL'], strtok($server['REQUEST_URI'], '?'));

		$qs = !empty($server['QUERY_STRING']) ? $server['QUERY_STRING'] : '';
		if ($this->conf(Base::O_DEBUG_COLLAPSE_QS)) {
			$qs = $this->_omit_long_message($qs);
			if ($qs) {
				$param .= ' ? ' . $qs;
			}
			$params[] = $param;
		} else {
			$params[] = $param;
			$params[] = 'Query String: ' . $qs;
		}

		if (!empty($_SERVER['HTTP_REFERER'])) {
			$params[] = 'HTTP_REFERER: ' . $this->_omit_long_message($server['HTTP_REFERER']);
		}

		if (defined('LSCWP_LOG_MORE')) {
			$params[] = 'User Agent: ' . $this->_omit_long_message($server['HTTP_USER_AGENT']);
			$params[] = 'Accept: ' . $server['HTTP_ACCEPT'];
			$params[] = 'Accept Encoding: ' . $server['HTTP_ACCEPT_ENCODING'];
		}
		// $params[] = 'Cookie: ' . $server['HTTP_COOKIE'];
		if (isset($_COOKIE['_lscache_vary'])) {
			$params[] = 'Cookie _lscache_vary: ' . $_COOKIE['_lscache_vary'];
		}
		if (defined('LSCWP_LOG_MORE')) {
			$params[] = 'X-LSCACHE: ' . (!empty($server['X-LSCACHE']) ? 'true' : 'false');
		}
		if ($server['LSCACHE_VARY_COOKIE']) {
			$params[] = 'LSCACHE_VARY_COOKIE: ' . $server['LSCACHE_VARY_COOKIE'];
		}
		if ($server['LSCACHE_VARY_VALUE']) {
			$params[] = 'LSCACHE_VARY_VALUE: ' . $server['LSCACHE_VARY_VALUE'];
		}
		if ($server['ESI_CONTENT_TYPE']) {
			$params[] = 'ESI_CONTENT_TYPE: ' . $server['ESI_CONTENT_TYPE'];
		}

		$request = array_map(__CLASS__ . '::format_message', $params);

		File::append($log_file, $request);
	}

	/**
	 * Trim long msg to keep log neat
	 *
	 * @since 6.3
	 */
	private function _omit_long_message( $msg ) {
		if (strlen($msg) > 53) {
			$msg = substr($msg, 0, 53) . '...';
		}
		return $msg;
	}

	/**
	 * Formats the log message with a consistent prefix.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param string $msg The log message to write.
	 * @return string The formatted log message.
	 */
	private static function format_message( $msg ) {
		// If call here without calling get_enabled() first, improve compatibility
		if (!defined('LSCWP_LOG_TAG')) {
			return $msg . "\n";
		}

		if (!isset(self::$_prefix)) {
			// address
			if (PHP_SAPI == 'cli') {
				$addr = '=CLI=';
				if (isset($_SERVER['USER'])) {
					$addr .= $_SERVER['USER'];
				} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$addr .= $_SERVER['HTTP_X_FORWARDED_FOR'];
				}
			} else {
				$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
				$port = isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : '';
				$addr = "$addr:$port";
			}

			// Generate a unique string per request
			self::$_prefix = sprintf(' [%s %s %s] ', $addr, LSCWP_LOG_TAG, Str::rrand(3));
		}
		list($usec, $sec) = explode(' ', microtime());
		return date('m/d/y H:i:s', $sec + LITESPEED_TIME_OFFSET) . substr($usec, 1, 4) . self::$_prefix . $msg . "\n";
	}

	/**
	 * Direct call to log a debug message.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function debug( $msg, $backtrace_limit = false ) {
		if (!defined('LSCWP_LOG')) {
			return;
		}

		if (defined('LSCWP_DEBUG_EXC_STRINGS') && Utility::str_hit_array($msg, LSCWP_DEBUG_EXC_STRINGS)) {
			return;
		}

		if ($backtrace_limit !== false) {
			if (!is_numeric($backtrace_limit)) {
				$backtrace_limit = self::trim_longtext($backtrace_limit);
				if (is_array($backtrace_limit) && count($backtrace_limit) == 1 && !empty($backtrace_limit[0])) {
					$msg .= ' --- ' . $backtrace_limit[0];
				} else {
					$msg .= ' --- ' . var_export($backtrace_limit, true);
				}
				self::push($msg);
				return;
			}

			self::push($msg, $backtrace_limit + 1);
			return;
		}

		self::push($msg);
	}

	/**
	 * Trim long string before array dump
	 *
	 * @since  3.3
	 */
	public static function trim_longtext( $backtrace_limit ) {
		if (is_array($backtrace_limit)) {
			$backtrace_limit = array_map(__CLASS__ . '::trim_longtext', $backtrace_limit);
		}
		if (is_string($backtrace_limit) && strlen($backtrace_limit) > 500) {
			$backtrace_limit = substr($backtrace_limit, 0, 1000) . '...';
		}
		return $backtrace_limit;
	}

	/**
	 * Direct call to log an advanced debug message.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function debug2( $msg, $backtrace_limit = false ) {
		if (!defined('LSCWP_LOG_MORE')) {
			return;
		}
		self::debug($msg, $backtrace_limit);
	}

	/**
	 * Logs a debug message.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param string $msg The debug message.
	 * @param int    $backtrace_limit Backtrace depth.
	 */
	private static function push( $msg, $backtrace_limit = false ) {
		// backtrace handler
		if (defined('LSCWP_LOG_MORE') && $backtrace_limit !== false) {
			$msg .= self::_backtrace_info($backtrace_limit);
		}

		File::append(self::$log_path, self::format_message($msg));
	}

	/**
	 * Backtrace info
	 *
	 * @since 2.7
	 */
	private static function _backtrace_info( $backtrace_limit ) {
		$msg = '';

		$trace = version_compare(PHP_VERSION, '5.4.0', '<') ? debug_backtrace() : debug_backtrace(false, $backtrace_limit + 3);
		for ($i = 2; $i <= $backtrace_limit + 2; $i++) {
			// 0st => _backtrace_info(), 1st => push()
			if (empty($trace[$i]['class'])) {
				if (empty($trace[$i]['file'])) {
					break;
				}
				$log = "\n" . $trace[$i]['file'];
			} else {
				if ($trace[$i]['class'] == __CLASS__) {
					continue;
				}

				$args = '';
				if (!empty($trace[$i]['args'])) {
					foreach ($trace[$i]['args'] as $v) {
						if (is_array($v)) {
							$v = 'ARRAY';
						}
						if (is_string($v) || is_numeric($v)) {
							$args .= $v . ',';
						}
					}

					$args = substr($args, 0, strlen($args) > 100 ? 100 : -1);
				}

				$log = str_replace('Core', 'LSC', $trace[$i]['class']) . $trace[$i]['type'] . $trace[$i]['function'] . '(' . $args . ')';
			}
			if (!empty($trace[$i - 1]['line'])) {
				$log .= '@' . $trace[$i - 1]['line'];
			}
			$msg .= " => $log";
		}

		return $msg;
	}

	/**
	 * Clear log file
	 *
	 * @since 1.6.6
	 * @access private
	 */
	private function _clear_log() {
		$logs = array( 'debug', 'purge', 'crawler' );
		foreach ($logs as $log) {
			File::save($this->path($log), '');
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.6.6
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_CLEAR_LOG:
            $this->_clear_log();
				break;

			case self::TYPE_BETA_TEST:
            $this->beta_test();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
