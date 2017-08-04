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
	private static $_instance ;
	private static $_debug ;
	private static $log_path ;
	private static $_enabled = false ;

	/**
	 * Log class Constructor
	 *
	 * @since 1.1.2
	 * @access public
	 */
	private function __construct()
	{
		self::$log_path = LSWCP_CONTENT_DIR . '/debug.log' ;
		if ( ! defined( 'LSCWP_LOG_TAG' ) ) {
			define( 'LSCWP_LOG_TAG', 'LSCACHE_WP_blogid_' . get_current_blog_id() ) ;
		}
		$this->_init_request() ;
		self::$_debug = true ;
	}

	/**
	 * Log all filters and action hooks
	 *
	 * @since 1.1.4.1
	 * @access public
	 */
	public static function log_filters()
	{
		$action = current_filter() ;
		$exclude = array ( 'gettext', 'gettext_with_context', 'get_the_terms', 'get_term', 'wc_get_template', 'pre_kses' ) ;
		if ( in_array( $action, $exclude ) ) {
			return ;
		}
		$exclude_strings = array(
			'order',
			'cart',
			'price',
			'order',
			'_url',
			'query',
			'option',
			'_key',
			'settings',
			'locale',
			'i18n',
			'email',
			'_html',
			'salt',
			'sanitize',
			'_widget',
			'taxonomy',
			'get_post_metadata',
			'post_type',
			'charset',
			'attribute',
		) ;
		foreach ( $exclude_strings as $value ) {
			if ( stripos( $action, $value ) !== false ) {
				return ;
			}
		}

		// if (
		// 	stripos($action, 'wc') === false &&
		// 	stripos($action, 'woo') === false &&
		// 	stripos($action, 'litespeed') === false
		// ) {
		// 	return ;
		// }

		if( $action == 'wp' ) {
			LiteSpeed_Cache_Log::debug( "+++++++++++++" ) ;
		}
		LiteSpeed_Cache_Log::debug( "===log filter: $action" ) ;
	}



	/**
	 * Check if log class finished initialized
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function initialized()
	{
		return isset( self::$_debug ) ;
	}

	/**
	 * Enable debug log
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function set_enabled()
	{
		self::$_enabled = true ;

		// Check if hook filters
		// add_action( 'all', 'LiteSpeed_Cache_Log::log_filters' ) ;
	}

	/**
	 * Get debug log status
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function get_enabled()
	{
		return self::$_enabled ;
	}

	/**
	 * Formats the log message with a consistent prefix.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param string $msg The log message to write.
	 * @return string The formatted log message.
	 */
	private static function format_message( $msg )
	{
		return self::prefix() . $msg . "\n" ;
	}

	/**
	 * Formats the consistent prefix.
	 *
	 * @since 1.1.3
	 * @access private
	 * @return string The formatted log prefix.
	 */
	private static function prefix()
	{
		$port = isset( $_SERVER['REMOTE_PORT'] ) ? $_SERVER['REMOTE_PORT'] : '' ;
		if ( PHP_SAPI == 'cli' ) {
			$addr = '=CLI=' ;
		}
		else {
			$addr = $_SERVER['REMOTE_ADDR'] ;
		}
		$prefix = sprintf( "%s [%s:%s] [%s] ", date( 'r' ), $addr, $port, LSCWP_LOG_TAG ) ;
		return $prefix ;
	}

	/**
	 * Direct call to log a debug message.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param string $msg The debug message.
	 * @param int $backtrace_limit Backtrace depth.
	 */
	public static function debug( $msg, $backtrace_limit = false )
	{
		if ( self::get_enabled() ) {
			self::push( $msg, $backtrace_limit !== false ? $backtrace_limit+1 : false ) ;
		}
	}

	/**
	 * Logs a debug message.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param string $msg The debug message.
	 * @param int $backtrace_limit Backtrace depth.
	 */
	public static function push( $msg, $backtrace_limit = false )
	{
		if ( ! isset( self::$_debug ) ) {// If not initialized, do it now
			self::get_instance() ;
		}

		// backtrace handler
		if ( $backtrace_limit !== false ) {
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $backtrace_limit + 2 ) ;
			for ( $i=1 ; $i <= $backtrace_limit + 1 ; $i++ ) {// the 0st item is push()
				if ( empty( $trace[$i]['class'] ) ) {
					break ;
				}
				if ( $trace[$i]['class'] == 'LiteSpeed_Cache_Log' ) {
					continue ;
				}
				$log = str_replace('LiteSpeed_Cache', 'LSC', $trace[$i]['class']) . $trace[$i]['type'] . $trace[$i]['function'] . '()' ;
				if ( ! empty( $trace[$i-1]['line'] ) ) {
					$log .= '@' . $trace[$i-1]['line'] ;
				}
				$msg .= " \ $log" ;
			}

		}

		file_put_contents( self::$log_path, self::format_message( $msg ), FILE_APPEND ) ;
	}

	/**
	 * Create the initial log messages with the request parameters.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _init_request()
	{
		// For more than 2s's requests, add more break
		if ( file_exists( self::$log_path ) && time() - filemtime( self::$log_path ) > 2 ) {
			file_put_contents( self::$log_path, "\n\n\n\n", FILE_APPEND ) ;
		}

		if ( PHP_SAPI == 'cli' ) {
			return ;
		}

		$SERVERVARS = array(
			'Query String' => '',
			'HTTP_USER_AGENT' => '',
			'HTTP_ACCEPT_ENCODING' => '',
			'HTTP_COOKIE' => '',
			'X-LSCACHE' => '',
			'LSCACHE_VARY_COOKIE' => '',
			'LSCACHE_VARY_VALUE' => ''
		) ;
		$SERVER = array_merge( $SERVERVARS, $_SERVER ) ;
		$params = array() ;

		$params[] = sprintf( '%s %s %s', $SERVER['REQUEST_METHOD'], $SERVER['SERVER_PROTOCOL'], strtok( $SERVER['REQUEST_URI'], '?' ) ) ;

		$qs = ! empty( $SERVER['QUERY_STRING'] ) ? $SERVER['QUERY_STRING'] : '' ;
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_COLLAPS_QS ) && strlen( $qs ) > 53 ) {
			$qs = substr( $qs, 0, 53 ) . '...' ;
		}
		$params[] = 'Query String: ' . $qs ;
		$params[] = 'User Agent: ' . $SERVER['HTTP_USER_AGENT'] ;
		$params[] = 'Accept Encoding: ' . $SERVER['HTTP_ACCEPT_ENCODING'] ;
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_DEBUG_COOKIE ) ) {
			$params[] = 'Cookie: ' . $SERVER['HTTP_COOKIE'] ;
		}
		$params[] = 'X-LSCACHE: ' . ( $SERVER['X-LSCACHE'] ? 'true' : 'false' ) ;
		if( $SERVER['LSCACHE_VARY_COOKIE'] ) {
			$params[] = 'LSCACHE_VARY_COOKIE: ' . $SERVER['LSCACHE_VARY_COOKIE'] ;
		}
		if( $SERVER['LSCACHE_VARY_VALUE'] ) {
			$params[] = 'LSCACHE_VARY_VALUE: ' . $SERVER['LSCACHE_VARY_VALUE'] ;
		}

		$request = array_map( 'self::format_message', $params ) ;

		file_put_contents( self::$log_path, $request, FILE_APPEND ) ;
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
		$cls = get_called_class() ;
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new $cls() ;
		}

		return self::$_instance ;
	}
}
