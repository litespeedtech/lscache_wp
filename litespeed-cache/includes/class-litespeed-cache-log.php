<?php

/**
 * The plugin logging class.
 *
 * This generate the valid action.
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Log
{
	private static $_instance;
	private static $_debug;
	private static $log_path;
	private static $enabled = false;

	private function __construct()
	{
		self::$log_path = LSWCP_CONTENT_DIR . '/debug.log';
		if (!defined('LSCWP_LOG_TAG')) {
			define('LSCWP_LOG_TAG', 'LSCACHE_WP_blogid_' . get_current_blog_id());
		}
		$this->log_request();
		self::$_debug = true;
	}

	/**
	 * Enable debug log
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function set_enabled()
	{
		self::$enabled = true;
	}

	/**
	 * Get debug log status
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function get_enabled()
	{
		return self::$enabled;
	}

	/**
	 * Formats the log message with a consistent prefix.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param string $msg The log message to write.
	 * @return string The formatted log message.
	 */
	private static function format_message($msg)
	{
		$port = isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : '' ;
		$formatted = sprintf("%s [%s:%s] [%s] %s\n", date('r'), $_SERVER['REMOTE_ADDR'], $port, LSCWP_LOG_TAG, $msg);
		return $formatted;
	}

	/**
	 * Logs a debug message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $msg The debug message.
	 */
	public static function push($msg)
	{
		if ( !isset(self::$_debug) ) {// If not initialized, do it now
			self::get_instance();
		}
		$formatted = self::format_message($msg);
		file_put_contents(self::$log_path, $formatted, FILE_APPEND);
	}

	/**
	 * Create the initial log messages with the request parameters.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function log_request()
	{
		$SERVERVARS = array(
			'Query String' => '',
			'HTTP_USER_AGENT' => '',
			'HTTP_ACCEPT_ENCODING' => '',
			'HTTP_COOKIE' => '',
			'X-LSCACHE' => '',
			'LSCACHE_VARY_COOKIE' => '',
			'LSCACHE_VARY_VALUE' => ''
		);
		$SERVER = array_merge($SERVERVARS, $_SERVER);
		$params = array(
			sprintf('%s %s %s', $SERVER['REQUEST_METHOD'], $SERVER['SERVER_PROTOCOL'], strtok($SERVER['REQUEST_URI'], '?')),
			'Query String: '		. $SERVER['QUERY_STRING'],
			'User Agent: '			. $SERVER['HTTP_USER_AGENT'],
			'Accept Encoding: '		. $SERVER['HTTP_ACCEPT_ENCODING'],
			'Cookie: '				. $SERVER['HTTP_COOKIE'],
			'X-LSCACHE: '			. ($SERVER['X-LSCACHE'] ? 'true' : 'false'),
		);
		if($SERVER['LSCACHE_VARY_COOKIE']){
			$params[] = 'LSCACHE_VARY_COOKIE: ' . $SERVER['LSCACHE_VARY_COOKIE'];
		}
		if($SERVER['LSCACHE_VARY_VALUE']){
			$params[] = 'LSCACHE_VARY_VALUE: ' . $SERVER['LSCACHE_VARY_VALUE'];
		}

		$request = array_map('self::format_message', $params);
		file_put_contents(self::$log_path, $request, FILE_APPEND);
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
		$cls = get_called_class();
		if (!isset(self::$_instance)) {
			self::$_instance = new $cls();
		}

		return self::$_instance;
	}
}
