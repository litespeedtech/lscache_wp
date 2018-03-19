<?php
/**
 * The plugin logging class.
 *
 * This generate the valid action.
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Log
{
	private static $_instance ;
	private static $log_path ;
	private static $_prefix ;

	const TYPE_CLEAR_LOG = 'clear_log' ;

	/**
	 * Log class Constructor
	 *
	 * NOTE: in this process, until last step ( define const LSCWP_LOG = true ), any usage to WP filter will not be logged to prevent infinite loop with log_filters()
	 *
	 * @since 1.1.2
	 * @access public
	 */
	private function __construct()
	{
		self::$log_path = LSCWP_CONTENT_DIR . '/debug.log' ;
		if ( ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) && $_SERVER[ 'HTTP_USER_AGENT' ] === Litespeed_Crawler::FAST_USER_AGENT ) {
			self::$log_path = LSCWP_CONTENT_DIR . '/crawler.log' ;
		}
		if ( ! defined( 'LSCWP_LOG_TAG' ) ) {
			define( 'LSCWP_LOG_TAG', get_current_blog_id() ) ;
		}

		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_DEBUG_LEVEL ) ) {
			define( 'LSCWP_LOG_MORE', true ) ;
		}

		$this->_init_request() ;
		define( 'LSCWP_LOG', true ) ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.6.6
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_CLEAR_LOG :
				$instance->_clear_log() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Clear log file
	 *
	 * @since 1.6.6
	 * @access private
	 */
	private function _clear_log()
	{
		Litespeed_File::save( self::$log_path, '' ) ;
	}

	/**
	 * Heartbeat control
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public static function disable_heartbeat()
	{
		wp_deregister_script( 'heartbeat' ) ;
	}

	/**
	 * Enable debug log
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function init()
	{
		if ( ! defined( 'LSCWP_LOG' ) ) {// If not initialized, do it now
			self::get_instance() ;
		}

		// Check if hook filters
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_LOG_FILTERS ) ) {
			add_action( 'all', 'LiteSpeed_Cache_Log::log_filters' ) ;
		}
	}

	/**
	 * Log all filters and action hooks
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public static function log_filters()
	{
		$action = current_filter() ;
		if ( $ignore_filters = get_option( LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_FILTERS ) ) {
			$ignore_filters = explode( "\n", $ignore_filters ) ;
			if ( in_array( $action, $ignore_filters ) ) {
				return ;
			}
		}

		if ( $ignore_part_filters = get_option( LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_PART_FILTERS ) ) {
			$ignore_part_filters = explode( "\n", $ignore_part_filters ) ;
			foreach ( $ignore_part_filters as $val ) {
				if ( stripos( $action, $val ) !== false ) {
					return ;
				}
			}
		}

		self::debug( "===log filter: $action" ) ;
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
		// If call here without calling get_enabled() first, improve compatibility
		if ( ! defined( 'LSCWP_LOG_TAG' ) ) {
			return $msg . "\n" ;
		}

		if ( ! isset( self::$_prefix ) ) {
			// address
			if ( PHP_SAPI == 'cli' ) {
				$addr = '=CLI=' ;
				if ( isset( $_SERVER[ 'USER' ] ) ) {
					$addr .= $_SERVER[ 'USER' ] ;
				}
				elseif ( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) {
					$addr .= $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ;
				}
			}
			else {
				$addr = $_SERVER[ 'REMOTE_ADDR' ] . ':' . $_SERVER[ 'REMOTE_PORT' ] ;
			}

			// Generate a unique string per request
			self::$_prefix = sprintf( " [%s %s %s] ", $addr, LSCWP_LOG_TAG, Litespeed_String::rrand( 3 ) ) ;
		}
		list( $usec, $sec ) = explode(' ', microtime() ) ;
		return date( 'm/d/y H:i:s', $sec + LITESPEED_TIME_OFFSET ) . substr( $usec, 1, 4 ) . self::$_prefix . $msg . "\n" ;
	}

	/**
	 * Direct call to log a debug message.
	 *
	 * @since 1.1.3
	 * @since 1.6 Added array dump as 2nd param
	 * @access public
	 * @param string $msg The debug message.
	 * @param int|array $backtrace_limit Backtrace depth, Or the array to dump
	 */
	public static function debug( $msg, $backtrace_limit = false )
	{
		if ( ! defined( 'LSCWP_LOG' ) ) {
			return ;
		}

		if ( $backtrace_limit !== false ) {
			if ( ! is_numeric( $backtrace_limit ) ) {
				$msg .= ' --- ' . var_export( $backtrace_limit, true ) ;
				self::push( $msg ) ;
				return ;
			}

			self::push( $msg, $backtrace_limit + 1 ) ;
			return ;
		}

		self::push( $msg ) ;
	}

	/**
	 * Direct call to log an advanced debug message.
	 *
	 * @since 1.2.0
	 * @access public
	 * @param string $msg The debug message.
	 * @param int $backtrace_limit Backtrace depth.
	 */
	public static function debug2( $msg, $backtrace_limit = false )
	{
		if ( ! defined( 'LSCWP_LOG_MORE' ) ) {
			return ;
		}
		self::debug( $msg, $backtrace_limit ) ;
	}

	/**
	 * Logs a debug message.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param string $msg The debug message.
	 * @param int $backtrace_limit Backtrace depth.
	 */
	private static function push( $msg, $backtrace_limit = false )
	{
		// backtrace handler
		if ( defined( 'LSCWP_LOG_MORE' ) && $backtrace_limit !== false ) {
			$trace = version_compare( PHP_VERSION, '5.4.0', '<' ) ? debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) : debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $backtrace_limit + 2 ) ;
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

		Litespeed_File::append( self::$log_path, self::format_message( $msg ) ) ;
	}

	/**
	 * Create the initial log messages with the request parameters.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _init_request()
	{
		// Check log file size
		$log_file_size = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_LOG_FILE_SIZE ) ;
		if ( file_exists( self::$log_path ) && filesize( self::$log_path ) > $log_file_size*1000000 ) {
			Litespeed_File::save( self::$log_path, '' ) ;
		}

		// For more than 2s's requests, add more break
		if ( file_exists( self::$log_path ) && time() - filemtime( self::$log_path ) > 2 ) {
			Litespeed_File::append( self::$log_path, "\n\n\n\n" ) ;
		}

		if ( PHP_SAPI == 'cli' ) {
			return ;
		}

		$servervars = array(
			'Query String' => '',
			'HTTP_USER_AGENT' => '',
			'HTTP_ACCEPT_ENCODING' => '',
			'HTTP_COOKIE' => '',
			'X-LSCACHE' => '',
			'LSCACHE_VARY_COOKIE' => '',
			'LSCACHE_VARY_VALUE' => ''
		) ;
		$server = array_merge( $servervars, $_SERVER ) ;
		$params = array() ;

		$param = sprintf( '%s %s %s', $server['REQUEST_METHOD'], $server['SERVER_PROTOCOL'], strtok( $server['REQUEST_URI'], '?' ) ) ;

		$qs = ! empty( $server['QUERY_STRING'] ) ? $server['QUERY_STRING'] : '' ;
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_COLLAPS_QS ) ) {
			if ( strlen( $qs ) > 53 ) {
				$qs = substr( $qs, 0, 53 ) . '...' ;
			}
			if ( $qs ) {
				$param .= ' ? ' . $qs ;
			}
			$params[] = $param ;
		}
		else {
			$params[] = $param ;
			$params[] = 'Query String: ' . $qs ;
		}

		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'User Agent: ' . $server[ 'HTTP_USER_AGENT' ] ;
			$params[] = 'Accept Encoding: ' . $server['HTTP_ACCEPT_ENCODING'] ;
		}
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_DEBUG_COOKIE ) ) {
			$params[] = 'Cookie: ' . $server['HTTP_COOKIE'] ;
		}
		if ( isset( $_COOKIE[ '_lscache_vary' ] ) ) {
			$params[] = 'Cookie _lscache_vary: ' . $_COOKIE[ '_lscache_vary' ] ;
		}
		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'X-LSCACHE: ' . ( ! empty( $server[ 'X-LSCACHE' ] ) ? 'true' : 'false' ) ;
		}
		if( $server['LSCACHE_VARY_COOKIE'] ) {
			$params[] = 'LSCACHE_VARY_COOKIE: ' . $server['LSCACHE_VARY_COOKIE'] ;
		}
		if( $server['LSCACHE_VARY_VALUE'] ) {
			$params[] = 'LSCACHE_VARY_VALUE: ' . $server['LSCACHE_VARY_VALUE'] ;
		}

		$request = array_map( 'self::format_message', $params ) ;

		Litespeed_File::append( self::$log_path, $request ) ;
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
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
