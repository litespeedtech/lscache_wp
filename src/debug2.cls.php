<?php
/**
 * The plugin logging class.
 *
 * @package LiteSpeed
 * @since 1.1.2
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Centralized debug logging utilities for LiteSpeed Cache.
 */
class Debug2 extends Root {

	/**
	 * Active log file path.
	 *
	 * @var string
	 */
	private static $log_path;

	/**
	 * Directory prefix for all log files.
	 *
	 * @var string
	 */
	private static $log_path_prefix;

	/**
	 * Request-specific log line prefix.
	 *
	 * @var string
	 */
	private static $_prefix;

	const TYPE_CLEAR_LOG = 'clear_log';
	const TYPE_BETA_TEST = 'beta_test';

	const BETA_TEST_URL = 'beta_test_url';

	const BETA_TEST_URL_WP = 'https://downloads.wordpress.org/plugin/litespeed-cache.zip';

	/**
	 * Constructor.
	 *
	 * NOTE: until LSCWP_LOG is defined, calls to WP filters are not logged to
	 * avoid a recursion loop inside log_filters().
	 *
	 * @since 1.1.2
	 * @access public
	 */
	public function __construct() {
		self::$log_path_prefix = LITESPEED_STATIC_DIR . '/debug/';
		// Maybe move legacy log files
		$this->_maybe_init_folder();

		self::$log_path = $this->path( 'debug' );

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		if ( '' !== $ua && 0 === strpos( $ua, 'lscache_' ) ) {
			self::$log_path = $this->path( 'crawler' );
		}

		! defined( 'LSCWP_LOG_TAG' ) && define( 'LSCWP_LOG_TAG', get_current_blog_id() );

		if ( $this->conf( Base::O_DEBUG_LEVEL ) ) {
			! defined( 'LSCWP_LOG_MORE' ) && define( 'LSCWP_LOG_MORE', true );
		}

		defined( 'LSCWP_DEBUG_EXC_STRINGS' ) || define( 'LSCWP_DEBUG_EXC_STRINGS', $this->conf( Base::O_DEBUG_EXC_STRINGS ) );
	}

	/**
	 * Disable all functionalities temporarily (toggle).
	 *
	 * @since 7.4
	 * @access public
	 *
	 * @param int $time How long (in seconds) to disable LSC functions.
	 */
	public static function tmp_disable( $time = 86400 ) {
		$conf     = Conf::cls();
		$disabled = self::cls()->conf( Base::DEBUG_TMP_DISABLE );

		if ( 0 === $disabled ) {
			$conf->update_confs( [ Base::DEBUG_TMP_DISABLE => time() + (int) $time ] );
			self::debug2( 'LiteSpeed Cache temporary disabled.' );
			return;
		}

		$conf->update_confs( [ Base::DEBUG_TMP_DISABLE => 0 ] );
		self::debug2( 'LiteSpeed Cache reactivated.' );
	}

	/**
	 * Is the temporary disable active? If expired, re-enable.
	 *
	 * @since 7.4
	 * @access public
	 *
	 * @return bool
	 */
	public static function is_tmp_disable() {
		$disabled_time = self::cls()->conf( Base::DEBUG_TMP_DISABLE );

		if ( 0 === $disabled_time ) {
			return false;
		}

		if ( time() < (int) $disabled_time ) {
			return true;
		}

		Conf::cls()->update_confs( [ Base::DEBUG_TMP_DISABLE => 0 ] );
		return false;
	}

	/**
	 * Ensure log directory exists and move legacy logs into it.
	 *
	 * @since 6.5
	 * @access private
	 */
	private function _maybe_init_folder() {
		if ( file_exists( self::$log_path_prefix . 'index.php' ) ) {
			return;
		}

		File::save( self::$log_path_prefix . 'index.php', '<?php // Silence is golden.', true );

		$logs = [ 'debug', 'debug.purge', 'crawler' ];
		foreach ( $logs as $log ) {
			$old_path = LSCWP_CONTENT_DIR . '/' . $log . '.log';
			$new_path = $this->path( $log );
			if ( file_exists( $old_path ) && ! file_exists( $new_path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Moving legacy log files during migration
				rename( $old_path, $new_path );
			}
		}
	}

	/**
	 * Get absolute path for a log type.
	 *
	 * @since 6.5
	 * @param string $type Log type (debug|purge|crawler).
	 * @return string
	 */
	public function path( $type ) {
		return self::$log_path_prefix . self::FilePath( $type );
	}

	/**
	 * Get fixed filename for a log type.
	 *
	 * @since 6.5
	 * @param string $type Log type (debug|debug.purge|crawler).
	 * @return string
	 */
	public static function FilePath( $type ) {
		if ( 'debug.purge' === $type ) {
			$type = 'purge';
		}
		$key  = defined( 'AUTH_KEY' ) ? AUTH_KEY : md5( __FILE__ );
		$rand = substr( md5( substr( $key, -16 ) ), -16 );
		return $type . $rand . '.log';
	}

	/**
	 * Write end-of-request markers and response timing.
	 *
	 * @since 4.7
	 * @access public
	 * @return void
	 */
	public static function ended() {
		$headers = headers_list();
		foreach ( $headers as $key => $header ) {
			if ( 0 === stripos( $header, 'Set-Cookie' ) ) {
				unset( $headers[ $key ] );
			}
		}
		self::debug( 'Response headers', $headers );

		$elapsed_time = number_format( ( microtime( true ) - LSCWP_TS_0 ) * 1000, 2 );
		self::debug( "End response\n--------------------------------------------------Duration: " . $elapsed_time . " ms------------------------------\n" );
	}

	/**
	 * Run beta test upgrade. Accepts a direct ZIP URL or attempts to derive one.
	 *
	 * @since 2.9.5
	 * @access public
	 *
	 * @param string|false $zip ZIP URL or false to read from request.
	 * @return void
	 */
	public function beta_test( $zip = false ) {
		if ( ! $zip ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_REQUEST[ self::BETA_TEST_URL ] ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$zip = sanitize_text_field( wp_unslash( $_REQUEST[ self::BETA_TEST_URL ] ) );
			if ( self::BETA_TEST_URL_WP !== $zip ) {
				if ( 'latest' === $zip ) {
					$zip = self::BETA_TEST_URL_WP;
				} else {
					// Generate zip url
					$zip = $this->_package_zip( $zip );
				}
			}
		}

		if ( ! $zip ) {
			self::debug( '[Debug2] ❌  No ZIP file' );
			return;
		}

		self::debug( '[Debug2] ZIP file ' . $zip );

		$update_plugins = get_site_transient( 'update_plugins' );
		if ( ! is_object( $update_plugins ) ) {
			$update_plugins = new \stdClass();
		}

		$plugin_info              = new \stdClass();
		$plugin_info->new_version = Core::VER;
		$plugin_info->slug        = Core::PLUGIN_NAME;
		$plugin_info->plugin      = Core::PLUGIN_FILE;
		$plugin_info->package     = $zip;
		$plugin_info->url         = 'https://wordpress.org/plugins/litespeed-cache/';

		$update_plugins->response[ Core::PLUGIN_FILE ] = $plugin_info;

		set_site_transient( 'update_plugins', $update_plugins );

		Activation::cls()->upgrade();
	}

	/**
	 * Resolve a GitHub commit-ish into a downloadable ZIP URL via QC API.
	 *
	 * @since 2.9.5
	 * @access private
	 *
	 * @param string $commit Commit hash/branch/tag.
	 * @return string|false
	 */
	private function _package_zip( $commit ) {
		$data = [
			'commit' => $commit,
		];
		$res  = Cloud::get( Cloud::API_BETA_TEST, $data );

		if ( empty( $res['zip'] ) ) {
			return false;
		}

		return $res['zip'];
	}

	/**
	 * Write purge headers into a dedicated purge log.
	 *
	 * @since 2.7
	 * @access public
	 *
	 * @param string $purge_header The Purge header value.
	 * @return void
	 */
	public static function log_purge( $purge_header ) {
		if ( ! defined( 'LSCWP_LOG' ) && ! defined( 'LSCWP_LOG_BYPASS_NOTADMIN' ) ) {
			return;
		}

		$purge_file = self::cls()->path( 'purge' );

		self::cls()->_init_request( $purge_file );

		$msg = $purge_header . self::_backtrace_info( 6 );

		File::append( $purge_file, self::format_message( $msg ) );
	}

	/**
	 * Initialize logging for current request if enabled.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return void
	 */
	public function init() {
		if ( defined( 'LSCWP_LOG' ) ) {
			return;
		}

		$debug = $this->conf( Base::O_DEBUG );
		if ( Base::VAL_ON2 === $debug ) {
			if ( ! $this->cls( 'Router' )->is_admin_ip() ) {
				defined( 'LSCWP_LOG_BYPASS_NOTADMIN' ) || define( 'LSCWP_LOG_BYPASS_NOTADMIN', true );
				return;
			}
		}

		/**
		 * Check if hit URI includes/excludes
		 * This is after LSCWP_LOG_BYPASS_NOTADMIN to make `log_purge()` still work
		 *
		 * @since  3.0
		 */
		$list = $this->conf( Base::O_DEBUG_INC );
		if ( $list ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$result      = Utility::str_hit_array( $request_uri, $list );
			if ( ! $result ) {
				return;
			}
		}

		$list = $this->conf( Base::O_DEBUG_EXC );
		if ( $list ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$result      = Utility::str_hit_array( $request_uri, $list );
			if ( $result ) {
				return;
			}
		}

		if ( ! defined( 'LSCWP_LOG' ) ) {
			$this->_init_request();
			define( 'LSCWP_LOG', true );
		}
	}

	/**
	 * Create the initial log record with request context.
	 *
	 * @since 1.0.12
	 * @access private
	 *
	 * @param string|null $log_file Optional specific log file path.
	 * @return void
	 */
	private function _init_request( $log_file = null ) {
		if ( ! $log_file ) {
			$log_file = self::$log_path;
		}

		// Rotate if exceeding configured size (MiB).
		$log_file_size = (int) $this->conf( Base::O_DEBUG_FILESIZE );
		if ( file_exists( $log_file ) && filesize( $log_file ) > $log_file_size * 1000000 ) {
			File::save( $log_file, '' );
		}

		// Add extra spacing if last write was > 2 seconds ago.
		if ( file_exists( $log_file ) && ( time() - filemtime( $log_file ) ) > 2 ) {
			File::append( $log_file, "\n\n\n\n" );
		}

		if ( 'cli' === PHP_SAPI ) {
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

		if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
			$server['SERVER_PROTOCOL'] .= ' (HTTPS) ';
		}

		$param = sprintf('💓 ------%s %s %s', $server['REQUEST_METHOD'], $server['SERVER_PROTOCOL'], strtok($server['REQUEST_URI'], '?'));

		$qs = !empty($server['QUERY_STRING']) ? $server['QUERY_STRING'] : '';
		if ( $this->conf( Base::O_DEBUG_COLLAPSE_QS ) ) {
			$qs = $this->_omit_long_message( $qs );
			if ( $qs ) {
				$param .= ' ? ' . $qs;
			}
			$params[] = $param;
		} else {
			$params[] = $param;
			$params[] = 'Query String: ' . $qs;
		}

		if ( ! empty( $server['HTTP_REFERER'] ) ) {
			$params[] = 'HTTP_REFERER: ' . $this->_omit_long_message( $server['HTTP_REFERER'] );
		}

		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'User Agent: ' . $this->_omit_long_message( $server['HTTP_USER_AGENT'] );
			$params[] = 'Accept: ' . $server['HTTP_ACCEPT'];
			$params[] = 'Accept Encoding: ' . $server['HTTP_ACCEPT_ENCODING'];
		}

		if ( isset( $_COOKIE['_lscache_vary'] ) ) {
			$params[] = 'Cookie _lscache_vary: ' . sanitize_text_field( wp_unslash( $_COOKIE['_lscache_vary'] ) );
		}

		if ( defined( 'LSCWP_LOG_MORE' ) ) {
			$params[] = 'X-LSCACHE: ' . ( ! empty( $server['X-LSCACHE'] ) ? 'true' : 'false' );
		}
		if ( $server['LSCACHE_VARY_COOKIE'] ) {
			$params[] = 'LSCACHE_VARY_COOKIE: ' . $server['LSCACHE_VARY_COOKIE'];
		}
		if ( $server['LSCACHE_VARY_VALUE'] ) {
			$params[] = 'LSCACHE_VARY_VALUE: ' . $server['LSCACHE_VARY_VALUE'];
		}
		if ( $server['ESI_CONTENT_TYPE'] ) {
			$params[] = 'ESI_CONTENT_TYPE: ' . $server['ESI_CONTENT_TYPE'];
		}

		$request = array_map( __CLASS__ . '::format_message', $params );

		File::append( $log_file, $request );
	}

	/**
	 * Trim long message to keep logs compact.
	 *
	 * @since 6.3
	 * @param string $msg Message.
	 * @return string
	 */
	private function _omit_long_message( $msg ) {
		if ( strlen( $msg ) > 53 ) {
			$msg = substr( $msg, 0, 53 ) . '...';
		}
		return $msg;
	}

	/**
	 * Format a single log line with timestamp and prefix.
	 *
	 * @since 1.0.12
	 * @access private
	 *
	 * @param string $msg Message to log.
	 * @return string Formatted line.
	 */
	private static function format_message( $msg ) {
		if ( ! defined( 'LSCWP_LOG_TAG' ) ) {
			return $msg . "\n";
		}

		if ( ! isset( self::$_prefix ) ) {
			// address/identity.
			if ( 'cli' === PHP_SAPI ) {
				$addr = '=CLI=';
				if ( isset( $_SERVER['USER'] ) ) {
					$addr .= sanitize_text_field( wp_unslash( $_SERVER['USER'] ) );
				} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
					$addr .= sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
				}
			} else {
				$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
				$port = isset( $_SERVER['REMOTE_PORT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_PORT'] ) ) : '';
				$addr = "$ip:$port";
			}

			self::$_prefix = sprintf( ' [%s %s %s] ', $addr, LSCWP_LOG_TAG, Str::rrand( 3 ) );
		}

		list( $usec, $sec ) = explode( ' ', microtime() );

		// Use gmdate to avoid tz-related warnings; apply offset if defined.
		$ts = gmdate( 'm/d/y H:i:s', (int) $sec + ( defined( 'LITESPEED_TIME_OFFSET' ) ? (int) LITESPEED_TIME_OFFSET : 0 ) );

		return $ts . substr( $usec, 1, 4 ) . self::$_prefix . $msg . "\n";
	}

	/**
	 * Log a debug message.
	 *
	 * @since 1.1.3
	 * @access public
	 *
	 * @param string    $msg             Message to write.
	 * @param int|array $backtrace_limit Depth for backtrace or payload to append.
	 * @return void
	 */
	public static function debug( $msg, $backtrace_limit = false ) {
		if ( ! defined( 'LSCWP_LOG' ) ) {
			return;
		}

		if ( defined( 'LSCWP_DEBUG_EXC_STRINGS' ) && Utility::str_hit_array( $msg, LSCWP_DEBUG_EXC_STRINGS ) ) {
			return;
		}

		if ( false !== $backtrace_limit ) {
			if ( ! is_numeric( $backtrace_limit ) ) {
				$backtrace_limit = self::trim_longtext( $backtrace_limit );
				if ( is_array( $backtrace_limit ) && 1 === count( $backtrace_limit ) && ! empty( $backtrace_limit[0] ) ) {
					$msg .= ' --- ' . $backtrace_limit[0];
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
					$msg .= ' --- ' . var_export( $backtrace_limit, true );
				}
				self::push( $msg );
				return;
			}

			self::push( $msg, (int) $backtrace_limit + 1 );
			return;
		}

		self::push( $msg );
	}

	/**
	 * Trim strings inside arrays/object dumps to reasonable length.
	 *
	 * @since 3.3
	 * @param mixed $backtrace_limit Data to trim.
	 * @return mixed
	 */
	public static function trim_longtext( $backtrace_limit ) {
		if ( is_array( $backtrace_limit ) ) {
			$backtrace_limit = array_map( __CLASS__ . '::trim_longtext', $backtrace_limit );
		}
		if ( is_string( $backtrace_limit ) && strlen( $backtrace_limit ) > 500 ) {
			$backtrace_limit = substr( $backtrace_limit, 0, 1000 ) . '...';
		}
		return $backtrace_limit;
	}

	/**
	 * Log a verbose debug message (requires O_DEBUG_LEVEL).
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @param string    $msg             Message.
	 * @param int|array $backtrace_limit Backtrace depth or payload to append.
	 * @return void
	 */
	public static function debug2( $msg, $backtrace_limit = false ) {
		if ( ! defined( 'LSCWP_LOG_MORE' ) ) {
			return;
		}
		self::debug( $msg, $backtrace_limit );
	}

	/**
	 * Append a message to the active log file.
	 *
	 * @since 1.1.0
	 * @access private
	 *
	 * @param string   $msg             Message.
	 * @param int|bool $backtrace_limit Backtrace depth.
	 * @return void
	 */
	private static function push( $msg, $backtrace_limit = false ) {
		if ( defined( 'LSCWP_LOG_MORE' ) && false !== $backtrace_limit ) {
			$msg .= self::_backtrace_info( (int) $backtrace_limit );
		}

		File::append( self::$log_path, self::format_message( $msg ) );
	}

	/**
	 * Create a compact backtrace string.
	 *
	 * @since 2.7
	 * @access private
	 *
	 * @param int $backtrace_limit Depth.
	 * @return string
	 */
	private static function _backtrace_info( $backtrace_limit ) {
		$msg   = '';
		$limit = (int) $backtrace_limit;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$trace = debug_backtrace( false, $limit + 3 );

		for ( $i = 2; $i <= $limit + 2; $i++ ) {
			// 0 => _backtrace_info(), 1 => push().
			if ( empty( $trace[ $i ]['class'] ) ) {
				if ( empty( $trace[ $i ]['file'] ) ) {
					break;
				}
				$log = "\n" . $trace[ $i ]['file'];
			} else {
				if ( __CLASS__ === $trace[ $i ]['class'] ) {
					continue;
				}

				$args = '';
				if ( ! empty( $trace[ $i ]['args'] ) ) {
					foreach ( $trace[ $i ]['args'] as $v ) {
						if ( is_array( $v ) ) {
							$v = 'ARRAY';
						}
						if ( is_string( $v ) || is_numeric( $v ) ) {
							$args .= $v . ',';
						}
					}
					$args = substr( $args, 0, strlen( $args ) > 100 ? 100 : -1 );
				}

				$log = str_replace( 'Core', 'LSC', $trace[ $i ]['class'] ) . $trace[ $i ]['type'] . $trace[ $i ]['function'] . '(' . $args . ')';
			}

			if ( ! empty( $trace[ $i - 1 ]['line'] ) ) {
				$log .= '@' . $trace[ $i - 1 ]['line'];
			}
			$msg .= " => $log";
		}

		return $msg;
	}

	/**
	 * Clear all log files (debug|purge|crawler).
	 *
	 * @since 1.6.6
	 * @access private
	 * @return void
	 */
	private function _clear_log() {
		$logs = [ 'debug', 'purge', 'crawler' ];
		foreach ( $logs as $log ) {
			File::save( $this->path( $log ), '' );
		}
	}

	/**
	 * Handle requests routed to this class.
	 *
	 * @since 1.6.6
	 * @access public
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
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
