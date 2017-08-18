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
	private static $_prefix ;
	private static $_enabled = false ;

	/**
	 * Log class Constructor
	 *
	 * NOTE: in this process, until last step ( self::$_debug = true ), any usage to WP filter should not be used to prevent infinite loop with log_filters()
	 *
	 * @since 1.1.2
	 * @access public
	 */
	private function __construct()
	{
		self::$log_path = LSWCP_CONTENT_DIR . '/debug.log' ;
		if ( ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) && $_SERVER[ 'HTTP_USER_AGENT' ] === Litespeed_Crawler::FAST_USER_AGENT ) {
			self::$log_path = LSWCP_CONTENT_DIR . '/crawler.log' ;
		}
		if ( ! defined( 'LSCWP_LOG_TAG' ) ) {
			define( 'LSCWP_LOG_TAG', get_current_blog_id() ) ;
		}

		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_DEBUG_LEVEL ) ) {
			define( 'LSCWP_LOG_MORE', true ) ;
		}

		$this->_init_request() ;
		self::$_debug = true ;
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

		if ( ! isset( self::$_debug ) ) {// If not initialized, do it now
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
		if ( $ignore_filters = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_LOG_IGNORE_FILTERS ) ) {
			$ignore_filters = explode( "\n", $ignore_filters ) ;
			if ( in_array( $action, $ignore_filters ) ) {
				return ;
			}
		}

		if ( $ignore_part_filters = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_LOG_IGNORE_PART_FILTERS ) ) {
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
			$unique = '' ;
			$_random_list = '0123456789abcdefghijklmnopqrstuvwxyz' ;
			$max = strlen( $_random_list ) - 1 ;
			for( $i = 0 ; $i < 3 ; $i++ ) {
				$unique .= $_random_list[ mt_rand( 0, $max ) ] ;
			}
			self::$_prefix = sprintf( " [%s %s %s] ", $addr, LSCWP_LOG_TAG, $unique ) ;
		}
		return date( 'm/d/y H:i:s' ) . self::$_prefix . $msg . "\n" ;
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
		// backtrace handler
		if ( defined( 'LSCWP_LOG_MORE' ) && $backtrace_limit !== false ) {
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
		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'User Agent: ' . $SERVER[ 'HTTP_USER_AGENT' ] ;
			$params[] = 'Accept Encoding: ' . $SERVER['HTTP_ACCEPT_ENCODING'] ;
		}
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_DEBUG_COOKIE ) ) {
			$params[] = 'Cookie: ' . $SERVER['HTTP_COOKIE'] ;
		}
		if ( isset( $_COOKIE[ '_lscache_vary' ] ) ) {
			$params[] = 'Cookie _lscache_vary: ' . $_COOKIE[ '_lscache_vary' ] ;
		}
		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'X-LSCACHE: ' . ( $SERVER[ 'X-LSCACHE' ] ? 'true' : 'false' ) ;
		}
		if( $SERVER['LSCACHE_VARY_COOKIE'] ) {
			$params[] = 'LSCACHE_VARY_COOKIE: ' . $SERVER['LSCACHE_VARY_COOKIE'] ;
		}
		if( $SERVER['LSCACHE_VARY_VALUE'] ) {
			$params[] = 'LSCACHE_VARY_VALUE: ' . $SERVER['LSCACHE_VARY_VALUE'] ;
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
		$cls = get_called_class() ;
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new $cls() ;
		}

		return self::$_instance ;
	}
}
