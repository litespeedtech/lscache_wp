<?php
/**
 * The object cache class.
 *
 * @since       1.8
 * @package     LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

require_once dirname( __DIR__ ) . '/autoload.php';

/**
 * Object cache handler using Redis or Memcached.
 *
 * NOTE: this class may be included without initialized core.
 *
 * @since 1.8
 */
class Object_Cache extends Root {
	const LOG_TAG = '[Object_Cache]';

	/**
	 * Debug option key.
	 *
	 * @var string
	 */
	const O_DEBUG = 'debug';

	/**
	 * Object cache enable key.
	 *
	 * @var string
	 */
	const O_OBJECT = 'object';

	/**
	 * Object kind (Redis/Memcached).
	 *
	 * @var string
	 */
	const O_OBJECT_KIND = 'object-kind';

	/**
	 * Object host.
	 *
	 * @var string
	 */
	const O_OBJECT_HOST = 'object-host';

	/**
	 * Object port.
	 *
	 * @var string
	 */
	const O_OBJECT_PORT = 'object-port';

	/**
	 * Object life/TTL.
	 *
	 * @var string
	 */
	const O_OBJECT_LIFE = 'object-life';

	/**
	 * Persistent connection flag.
	 *
	 * @var string
	 */
	const O_OBJECT_PERSISTENT = 'object-persistent';

	/**
	 * Admin cache flag.
	 *
	 * @var string
	 */
	const O_OBJECT_ADMIN = 'object-admin';

	/**
	 * DB index for Redis.
	 *
	 * @var string
	 */
	const O_OBJECT_DB_ID = 'object-db_id';

	/**
	 * Username for auth.
	 *
	 * @var string
	 */
	const O_OBJECT_USER = 'object-user';

	/**
	 * Password for auth.
	 *
	 * @var string
	 */
	const O_OBJECT_PSWD = 'object-pswd';

	/**
	 * Global groups list.
	 *
	 * @var string
	 */
	const O_OBJECT_GLOBAL_GROUPS = 'object-global_groups';

	/**
	 * Non-persistent groups list.
	 *
	 * @var string
	 */
	const O_OBJECT_NON_PERSISTENT_GROUPS = 'object-non_persistent_groups';

	/**
	 * O_OBJECT_KIND values. KIND_AUTO is persisted as 2 and resolved to a
	 * concrete backend at runtime so the choice tracks the live environment.
	 */
	const KIND_MEMCACHED = 0;
	const KIND_REDIS     = 1;
	const KIND_AUTO      = 2;

	// Caches the runtime Auto-method resolution so each page load skips the candidate scan.
	const TRANS_AUTO_RESOLVED = 'litespeed_oc_auto_resolved';

	// Caches the connection-test result rendered on the Object Cache settings tab.
	const TRANS_CONN_TEST = 'litespeed_oc_conn_test';

	// Caches the last backend benchmark run; busted when Object Cache settings change.
	const TRANS_BENCHMARK = 'litespeed_oc_benchmark';

	/**
	 * Connection instance.
	 *
	 * @var \Redis|\Memcached|null
	 */
	private $_conn;

	/**
	 * Debug config.
	 *
	 * @var bool
	 */
	private $_cfg_debug;

	/**
	 * Whether OC is enabled.
	 *
	 * @var bool
	 */
	private $_cfg_enabled;

	/**
	 * True => Redis, false => Memcached.
	 *
	 * @var bool
	 */
	private $_cfg_method;

	/**
	 * Host name.
	 *
	 * @var string
	 */
	private $_cfg_host;

	/**
	 * Port number.
	 *
	 * @var int|string
	 */
	private $_cfg_port;

	/**
	 * Use persistent connection.
	 *
	 * @var bool
	 */
	private $_cfg_persistent;

	/**
	 * Cache admin pages.
	 *
	 * @var bool
	 */
	private $_cfg_admin;

	/**
	 * Redis DB index.
	 *
	 * @var int
	 */
	private $_cfg_db;

	/**
	 * Auth username.
	 *
	 * @var string
	 */
	private $_cfg_user;

	/**
	 * Auth password.
	 *
	 * @var string
	 */
	private $_cfg_pswd;

	/**
	 * 'Redis' or 'Memcached'.
	 *
	 * @var string
	 */
	private $_oc_driver = 'Memcached'; // Redis or Memcached.

	/**
	 * Global groups.
	 *
	 * @var array
	 */
	private $_global_groups = [];

	/**
	 * Non-persistent groups.
	 *
	 * @var array
	 */
	private $_non_persistent_groups = [];

	/**
	 * Init.
	 *
	 * NOTE: this class may be included without initialized core.
	 *
	 * @since  1.8
	 *
	 * @param array|false $cfg Optional configuration to bootstrap without core.
	 */
	public function __construct( $cfg = false ) {
		if ( $cfg ) {
			if ( ! is_array( $cfg[ Base::O_OBJECT_GLOBAL_GROUPS ] ) ) {
				$cfg[ Base::O_OBJECT_GLOBAL_GROUPS ] = explode( "\n", $cfg[ Base::O_OBJECT_GLOBAL_GROUPS ] );
			}
			if ( ! is_array( $cfg[ Base::O_OBJECT_NON_PERSISTENT_GROUPS ] ) ) {
				$cfg[ Base::O_OBJECT_NON_PERSISTENT_GROUPS ] = explode( "\n", $cfg[ Base::O_OBJECT_NON_PERSISTENT_GROUPS ] );
			}
			$this->_cfg_debug             = $cfg[ Base::O_DEBUG ] ? $cfg[ Base::O_DEBUG ] : false;
			$this->_cfg_host              = $cfg[ Base::O_OBJECT_HOST ];
			$this->_cfg_port              = $cfg[ Base::O_OBJECT_PORT ];
			$this->_cfg_persistent        = $cfg[ Base::O_OBJECT_PERSISTENT ];
			$this->_cfg_admin             = $cfg[ Base::O_OBJECT_ADMIN ];
			$this->_cfg_db                = $cfg[ Base::O_OBJECT_DB_ID ];
			$this->_cfg_user              = $cfg[ Base::O_OBJECT_USER ];
			$this->_cfg_pswd              = $cfg[ Base::O_OBJECT_PSWD ];
			$this->_global_groups         = $cfg[ Base::O_OBJECT_GLOBAL_GROUPS ];
			$this->_non_persistent_groups = $cfg[ Base::O_OBJECT_NON_PERSISTENT_GROUPS ];

			$this->_resolve_method( (int) $cfg[ Base::O_OBJECT_KIND ] );

			if ( $this->_cfg_method ) {
				$this->_oc_driver = 'Redis';
			}
			$this->_cfg_enabled = $cfg[ Base::O_OBJECT ] && class_exists( $this->_oc_driver ) && $this->_cfg_host;
		} elseif ( defined( 'LITESPEED_CONF_LOADED' ) ) { // If OC is OFF, will hit here to init OC after conf initialized
			$this->_cfg_debug             = $this->conf( Base::O_DEBUG ) ? $this->conf( Base::O_DEBUG ) : false;
			$this->_cfg_host              = $this->conf( Base::O_OBJECT_HOST );
			$this->_cfg_port              = $this->conf( Base::O_OBJECT_PORT );
			$this->_cfg_persistent        = $this->conf( Base::O_OBJECT_PERSISTENT );
			$this->_cfg_admin             = $this->conf( Base::O_OBJECT_ADMIN );
			$this->_cfg_db                = $this->conf( Base::O_OBJECT_DB_ID );
			$this->_cfg_user              = $this->conf( Base::O_OBJECT_USER );
			$this->_cfg_pswd              = $this->conf( Base::O_OBJECT_PSWD );
			$this->_global_groups         = $this->conf( Base::O_OBJECT_GLOBAL_GROUPS );
			$this->_non_persistent_groups = $this->conf( Base::O_OBJECT_NON_PERSISTENT_GROUPS );

			$this->_resolve_method( (int) $this->conf( Base::O_OBJECT_KIND ) );

			if ( $this->_cfg_method ) {
				$this->_oc_driver = 'Redis';
			}
			$this->_cfg_enabled = $this->conf( Base::O_OBJECT ) && class_exists( $this->_oc_driver ) && $this->_cfg_host;
		} elseif ( defined( 'self::CONF_FILE' ) && file_exists( WP_CONTENT_DIR . '/' . self::CONF_FILE ) ) {
			// Get cfg from _data_file.
			// Use self::const to avoid loading more classes.
			$cfg       = \json_decode( file_get_contents( WP_CONTENT_DIR . '/' . self::CONF_FILE ), true );
			$file_kind = isset( $cfg[ self::O_OBJECT_KIND ] ) ? (int) $cfg[ self::O_OBJECT_KIND ] : self::KIND_MEMCACHED;
			// Auto walks its own candidate chain, so don't gate on a saved host.
			if ( ! empty( $cfg[ self::O_OBJECT_HOST ] ) || self::KIND_AUTO === $file_kind ) {
				$this->_cfg_debug             = ! empty( $cfg[ Base::O_DEBUG ] ) ? $cfg[ Base::O_DEBUG ] : false;
				$this->_cfg_host              = isset( $cfg[ self::O_OBJECT_HOST ] ) ? $cfg[ self::O_OBJECT_HOST ] : '';
				$this->_cfg_port              = isset( $cfg[ self::O_OBJECT_PORT ] ) ? $cfg[ self::O_OBJECT_PORT ] : 0;
				$this->_cfg_persistent        = ! empty( $cfg[ self::O_OBJECT_PERSISTENT ] ) ? $cfg[ self::O_OBJECT_PERSISTENT ] : false;
				$this->_cfg_admin             = ! empty( $cfg[ self::O_OBJECT_ADMIN ] ) ? $cfg[ self::O_OBJECT_ADMIN ] : false;
				$this->_cfg_db                = ! empty( $cfg[ self::O_OBJECT_DB_ID ] ) ? $cfg[ self::O_OBJECT_DB_ID ] : 0;
				$this->_cfg_user              = ! empty( $cfg[ self::O_OBJECT_USER ] ) ? $cfg[ self::O_OBJECT_USER ] : '';
				$this->_cfg_pswd              = ! empty( $cfg[ self::O_OBJECT_PSWD ] ) ? $cfg[ self::O_OBJECT_PSWD ] : '';
				$this->_global_groups         = ! empty( $cfg[ self::O_OBJECT_GLOBAL_GROUPS ] ) ? $cfg[ self::O_OBJECT_GLOBAL_GROUPS ] : [];
				$this->_non_persistent_groups = ! empty( $cfg[ self::O_OBJECT_NON_PERSISTENT_GROUPS ] ) ? $cfg[ self::O_OBJECT_NON_PERSISTENT_GROUPS ] : [];

				$this->_resolve_method( $file_kind );

				if ( $this->_cfg_method ) {
					$this->_oc_driver = 'Redis';
				}
				$this->_cfg_enabled = class_exists( $this->_oc_driver ) && $this->_cfg_host;
			} else {
				$this->_cfg_enabled = false;
			}
		} else {
			$this->_cfg_enabled = false;
		}

		// If OC not available, mark failure so OC methods return false early.
		// NOTE: Do NOT call wp_using_ext_object_cache(false) here — it causes
		// "Cannot redeclare wp_cache_init()" fatal on multisite (second call
		// to wp_start_object_cache() would load cache.php again).
		if ( ! $this->_cfg_enabled ) {
			! defined( 'LITESPEED_OC_FAILURE' ) && define( 'LITESPEED_OC_FAILURE', true );
		}
	}

	/**
	 * Resolve O_OBJECT_KIND into $_cfg_method (true=Redis, false=Memcached),
	 * running auto-detection when kind is KIND_AUTO. May overwrite host/port
	 * with the detected values. The result is cached in a short transient
	 * where available; the dropin bootstrap runs inline.
	 *
	 * @since 7.8.1
	 * @access private
	 *
	 * @param int $kind Saved O_OBJECT_KIND value (0/1/2).
	 * @return void
	 */
	private function _resolve_method( $kind ) {
		if ( self::KIND_REDIS === $kind ) {
			$this->_cfg_method = true;
			return;
		}
		if ( self::KIND_AUTO !== $kind ) {
			$this->_cfg_method = false;
			return;
		}

		// $wp_object_cache is null while wp_cache_init() is still building it
		// (and the dropin calls us from inside that constructor), so transient
		// access would fatal. Fall through to inline detection.
		$transients_safe = function_exists( 'get_transient' ) && ! empty( $GLOBALS['wp_object_cache'] );

		if ( $transients_safe ) {
			$cached = get_transient( self::TRANS_AUTO_RESOLVED );
			if ( is_array( $cached ) && isset( $cached['kind'], $cached['host'] ) ) {
				$this->_cfg_method = self::KIND_REDIS === (int) $cached['kind'];
				$this->_cfg_host   = $cached['host'];
				$this->_cfg_port   = (int) $cached['port'];
				return;
			}
		}

		$detected = $this->auto_detect( [
			'host' => $this->_cfg_host,
			'port' => $this->_cfg_port,
		] );

		if ( $detected ) {
			$this->_cfg_method = self::KIND_REDIS === (int) $detected['kind'];
			$this->_cfg_host   = $detected['host'];
			$this->_cfg_port   = (int) $detected['port'];

			if ( $transients_safe && function_exists( 'set_transient' ) ) {
				set_transient( self::TRANS_AUTO_RESOLVED, [
					'kind' => (int) $detected['kind'],
					'host' => $detected['host'],
					'port' => (int) $detected['port'],
				], 5 * MINUTE_IN_SECONDS );
			}
			return;
		}

		// Detection failed — keep host/port and default to Memcached.
		$this->_cfg_method = false;
	}

	/**
	 * Add debug.
	 *
	 * @since  6.3
	 * @access private
	 *
	 * @param string $text Log text.
	 * @return void
	 */
	private function debug_oc( $text ) {
		if ( defined( 'LSCWP_LOG' ) ) {
			self::debug( $text );
			return;
		}

		if ( Base::VAL_ON2 !== $this->_cfg_debug ) {
			return;
		}

		$litespeed_data_folder = defined( 'LITESPEED_DATA_FOLDER' ) ? LITESPEED_DATA_FOLDER : 'litespeed';
		$lscwp_content_dir     = defined( 'LSCWP_CONTENT_DIR' ) ? LSCWP_CONTENT_DIR : WP_CONTENT_DIR;
		$litespeed_static_dir  = $lscwp_content_dir . '/' . $litespeed_data_folder;
		$log_path_prefix       = $litespeed_static_dir . '/debug/';
		$log_file              = $log_path_prefix . Debug2::FilePath( 'debug' );

		if ( file_exists( $log_path_prefix . 'index.php' ) && file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(gmdate('m/d/y H:i:s') . ' - OC - ' . $text . PHP_EOL, 3, $log_file);
		}
	}

	/**
	 * Check if the group belongs to transients or not.
	 *
	 * @since  1.8.3
	 * @access private
	 *
	 * @param string $group Group name.
	 * @return bool
	 */
	private function _is_transients_group( $group ) {
		return in_array( $group, [ 'transient', 'site-transient' ], true );
	}

	/**
	 * Update WP object cache file config.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @param array $options Options to apply after update.
	 * @return void
	 */
	public function update_file( $options ) {
		$changed = false;

		// NOTE: When included in oc.php, `LSCWP_DIR` will show undefined, so this must be assigned/generated when used.
		$_oc_ori_file = LSCWP_DIR . 'lib/object-cache.php';
		$_oc_wp_file  = WP_CONTENT_DIR . '/object-cache.php';

		// Update cls file.
		if ( ! file_exists( $_oc_wp_file ) || md5_file( $_oc_wp_file ) !== md5_file( $_oc_ori_file ) ) {
			$this->debug_oc( 'copying object-cache.php file to ' . $_oc_wp_file );
			copy( $_oc_ori_file, $_oc_wp_file );
			$changed = true;
		}

		/**
		 * Clear object cache.
		 */
		if ( $changed ) {
			$this->_reconnect( $options );
		}
	}

	/**
	 * Remove object cache file.
	 *
	 * @since  1.8.2
	 * @access public
	 *
	 * @return void
	 */
	public function del_file() {
		// NOTE: When included in oc.php, `LSCWP_DIR` will show undefined, so this must be assigned/generated when used.
		$_oc_ori_file = LSCWP_DIR . 'lib/object-cache.php';
		$_oc_wp_file  = WP_CONTENT_DIR . '/object-cache.php';

		if ( file_exists( $_oc_wp_file ) && md5_file( $_oc_wp_file ) === md5_file( $_oc_ori_file ) ) {
			$this->debug_oc( 'removing ' . $_oc_wp_file );
			wp_delete_file( $_oc_wp_file );
		}
	}

	/**
	 * Run the connection test rendered on the Object Cache settings tab.
	 *
	 * Tries the configured host/port first. If that fails (or no host is set)
	 * and the user has at least one backend extension loaded, it falls through
	 * the priority chain — localhost, 127.0.0.1, then the auto-detected socket
	 * candidates — and reports the first working backend.
	 *
	 * Result shape:
	 *   [
	 *     'ok'     => bool|null,                     // null => no extension installed
	 *     'source' => 'configured'|'detected'|'unavailable',
	 *     'kind'   => 'Redis / Valkey'|'Memcached'|null,  // user-facing label
	 *     'host'   => string|null,
	 *     'port'   => int|null,
	 *     'detail' => string,                        // short human-readable summary
	 *   ]
	 *
	 * Results are cached in a short transient so reloading the settings tab
	 * doesn't keep scanning sockets. The transient is cleared whenever the
	 * Object Cache settings are saved.
	 *
	 * @since  1.8
	 * @since  7.8.1 Returns a structured array (was bool|null) and runs the
	 *               full multi-candidate fallback chain.
	 * @access public
	 *
	 * @return array
	 */
	public function test_connection() {
		$cached = get_transient( self::TRANS_CONN_TEST );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = $this->_run_connection_test();
		set_transient( self::TRANS_CONN_TEST, $result, 5 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * Build the connection-test result without touching the transient cache.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @return array
	 */
	private function _run_connection_test() {
		$redis_loaded = class_exists( 'Redis' );
		$mem_loaded   = class_exists( 'Memcached' );

		if ( ! $redis_loaded && ! $mem_loaded ) {
			return [
				'ok'     => null,
				'source' => 'unavailable',
				'kind'   => null,
				'host'   => null,
				'port'   => null,
				'detail' => '',
			];
		}

		// 1. Configured connection. Catch the socket+port misconfig explicitly
		// so the user sees a useful error instead of a generic connect failure.
		if ( $this->_cfg_host ) {
			$kind = $this->_cfg_method ? 'Redis' : 'Memcached';

			if ( $this->_is_socket_path( $this->_cfg_host ) && (int) $this->_cfg_port > 0 ) {
				return [
					'ok'     => false,
					'source' => 'configured',
					'kind'   => $this->_kind_label( $kind ),
					'host'   => $this->_cfg_host,
					'port'   => (int) $this->_cfg_port,
					'detail' => sprintf(
						/* translators: %s: socket path */
						__( 'Host %s is a Unix socket but Port is not 0. Set Port to 0 when using a socket.', 'litespeed-cache' ),
						$this->_cfg_host
					),
				];
			}

			if ( $this->_attempt( $kind, $this->_cfg_host, (int) $this->_cfg_port ) ) {
				return [
					'ok'     => true,
					'source' => 'configured',
					'kind'   => $this->_kind_label( $kind ),
					'host'   => $this->_cfg_host,
					'port'   => (int) $this->_cfg_port,
					'detail' => sprintf(
						/* translators: 1: backend name, 2: host:port or socket path */
						__( 'Connected to %1$s at %2$s using the configured settings.', 'litespeed-cache' ),
						$this->_kind_label( $kind ),
						$this->_format_endpoint( $this->_cfg_host, (int) $this->_cfg_port )
					),
				];
			}
		}

		// 2. Fall back to auto-detection across both backends. We don't surface
		// a detail line here because the Benchmark panel right below the
		// Status block already shows the same information in richer form
		// (full latency-ranked table + named fastest). Duplicating the hint
		// just adds noise.
		$detected = $this->auto_detect();
		if ( $detected ) {
			return [
				'ok'     => true,
				'source' => 'detected',
				'kind'   => $this->_kind_label( $detected['kind_label'] ),
				'host'   => $detected['host'],
				'port'   => $detected['port'],
				'detail' => '',
			];
		}

		return [
			'ok'     => false,
			'source' => 'unavailable',
			'kind'   => null,
			'host'   => null,
			'port'   => null,
			'detail' => __( 'No working Redis/Valkey or Memcached connection was found.', 'litespeed-cache' ),
		];
	}

	/**
	 * Walk the full candidate chain for every loaded backend, attempt to
	 * connect to each, measure round-trip latency, then rank the survivors.
	 *
	 * For each candidate that succeeds on the first pass we re-measure 2 more
	 * times and average the 3 runs, so a single laggy probe (cold caches,
	 * GC pause, etc.) doesn't dominate the ranking. Candidates that fail the
	 * first attempt are not retried — connection failures don't get faster
	 * with repeat attempts and the UI hides them anyway.
	 *
	 * This is an explicit-action benchmark — it runs only when the admin
	 * clicks the link in the Status panel, never automatically, because the
	 * full sweep can issue many connection attempts with 1.5 s connect
	 * timeouts in the worst case. The result is cached for an hour in a
	 * transient so the UI can offer "Show benchmarks" without re-running.
	 *
	 * @since 7.8.1
	 * @access public
	 *
	 * @param int $samples_per_run Probes per measurement pass (each pass
	 *                             also includes a fresh connect()).
	 * @return array{
	 *     results: array<int,array{kind:string,kind_token:string,host:string,port:int,ok:bool,latency_ms:?float,runs:int,error:?string}>,
	 *     fastest: array{kind:string,kind_token:string,host:string,port:int,latency_ms:float,runs:int}|null,
	 *     ran_at:  int
	 * }
	 */
	public function benchmark_candidates( $samples_per_run = 5 ) {
		$results          = [];
		$samples_per_run  = (int) $samples_per_run;
		$averaging_passes = 3;

		foreach ( [ 'Redis', 'Memcached' ] as $kind ) {
			if ( ! class_exists( $kind ) ) {
				continue;
			}

			foreach ( $this->_candidate_endpoints( $kind, (string) $this->_cfg_host, (int) $this->_cfg_port ) as $candidate ) {
				$first = $this->_measure_latency( $kind, $candidate['host'], $candidate['port'], $samples_per_run );

				if ( ! $first['ok'] ) {
					$results[] = [
						'kind'       => $this->_kind_label( $kind ),
						'kind_token' => $kind,
						'host'       => $candidate['host'],
						'port'       => $candidate['port'],
						'ok'         => false,
						'latency_ms' => null,
						'runs'       => 1,
						'error'      => $first['error'],
					];
					continue;
				}

				// Successful first pass — re-measure to dampen jitter.
				$latencies = [ $first['latency_ms'] ];
				for ( $i = 1; $i < $averaging_passes; $i++ ) {
					$extra = $this->_measure_latency( $kind, $candidate['host'], $candidate['port'], $samples_per_run );
					if ( $extra['ok'] && null !== $extra['latency_ms'] ) {
						$latencies[] = $extra['latency_ms'];
					}
				}

				// Prefer the specific product detected by INFO server
				// ('Redis' vs 'Valkey') over the joint "Redis / Valkey" label.
				// Falls back to the joint label if the INFO sniff was
				// inconclusive (Memcached always returns 'Memcached').
				$product_label = isset( $first['product'] ) && $first['product']
					? $first['product']
					: $this->_kind_label( $kind );

				$results[] = [
					'kind'       => $product_label,
					'kind_token' => $kind,
					'host'       => $candidate['host'],
					'port'       => $candidate['port'],
					'ok'         => true,
					'latency_ms' => array_sum( $latencies ) / count( $latencies ),
					'runs'       => count( $latencies ),
					'error'      => null,
				];
			}
		}

		$fastest = null;
		foreach ( $results as $r ) {
			if ( ! $r['ok'] || null === $r['latency_ms'] ) {
				continue;
			}
			if ( null === $fastest || $r['latency_ms'] < $fastest['latency_ms'] ) {
				$fastest = [
					'kind'       => $r['kind'],
					'kind_token' => $r['kind_token'],
					'host'       => $r['host'],
					'port'       => $r['port'],
					'latency_ms' => $r['latency_ms'],
					'runs'       => $r['runs'],
				];
			}
		}

		$payload = [ 'results' => $results, 'fastest' => $fastest, 'ran_at' => time() ];
		set_transient( self::TRANS_BENCHMARK, $payload, HOUR_IN_SECONDS );

		return $payload;
	}

	/**
	 * Return the most-recent cached benchmark payload, or null if nothing
	 * has been run within the transient TTL.
	 *
	 * @since 7.8.1
	 * @access public
	 *
	 * @return array|null
	 */
	public function get_benchmark_cache() {
		$cached = get_transient( self::TRANS_BENCHMARK );
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Return the flat list of candidates to benchmark, in display order.
	 * Used by the async UI to render placeholder rows before any probe runs.
	 *
	 * @since 7.8.1
	 * @access public
	 *
	 * @return array<int,array{kind:string,kind_token:string,host:string,port:int}>
	 */
	public function ajax_candidate_list() {
		$out = [];
		foreach ( [ 'Redis', 'Memcached' ] as $kind ) {
			if ( ! class_exists( $kind ) ) {
				continue;
			}
			foreach ( $this->_candidate_endpoints( $kind, (string) $this->_cfg_host, (int) $this->_cfg_port ) as $candidate ) {
				$out[] = [
					'kind'       => $this->_kind_label( $kind ),
					'kind_token' => $kind,
					'host'       => $candidate['host'],
					'port'       => (int) $candidate['port'],
				];
			}
		}
		return $out;
	}

	/**
	 * Benchmark a single candidate. Runs 3 averaging passes of 5 probes each;
	 * a failed first pass returns immediately without retry.
	 *
	 * @since 7.8.1
	 * @access public
	 *
	 * @param string $kind_token 'Redis' or 'Memcached'.
	 * @param string $host       Host name, IP, or Unix socket path.
	 * @param int    $port       Port number; 0 for sockets.
	 * @return array{ok:bool,latency_ms:?float,runs:int,product:?string,error:?string,kind:string,kind_token:string,host:string,port:int}
	 */
	public function benchmark_one( $kind_token, $host, $port ) {
		$samples          = 5;
		$averaging_passes = 3;

		$first = $this->_measure_latency( $kind_token, $host, $port, $samples );

		if ( ! $first['ok'] ) {
			return [
				'kind'       => $this->_kind_label( $kind_token ),
				'kind_token' => $kind_token,
				'host'       => $host,
				'port'       => (int) $port,
				'ok'         => false,
				'latency_ms' => null,
				'runs'       => 1,
				'product'    => null,
				'error'      => $first['error'],
			];
		}

		$latencies = [ $first['latency_ms'] ];
		for ( $i = 1; $i < $averaging_passes; $i++ ) {
			$extra = $this->_measure_latency( $kind_token, $host, $port, $samples );
			if ( $extra['ok'] && null !== $extra['latency_ms'] ) {
				$latencies[] = $extra['latency_ms'];
			}
		}

		$product_label = isset( $first['product'] ) && $first['product']
			? $first['product']
			: $this->_kind_label( $kind_token );

		return [
			'kind'       => $product_label,
			'kind_token' => $kind_token,
			'host'       => $host,
			'port'       => (int) $port,
			'ok'         => true,
			'latency_ms' => array_sum( $latencies ) / count( $latencies ),
			'runs'       => count( $latencies ),
			'product'    => $product_label,
			'error'      => null,
		];
	}

	/**
	 * Persist an aggregated benchmark payload to the transient cache so the
	 * Status panel can render the results across page reloads.
	 *
	 * @since 7.8.1
	 * @access public
	 *
	 * @param array $payload Full payload with results + fastest + ran_at.
	 * @return void
	 */
	public function commit_benchmark( $payload ) {
		if ( ! is_array( $payload ) || ! isset( $payload['results'] ) ) {
			return;
		}
		set_transient( self::TRANS_BENCHMARK, $payload, HOUR_IN_SECONDS );
	}

	/**
	 * Admin-AJAX entry point for the async benchmark UI.
	 *
	 * Steps:
	 *   list    -> returns the candidate list (rendered as placeholder rows).
	 *   run     -> benchmarks one candidate (kind, host, port) and returns timing.
	 *   commit  -> stores the JS-aggregated payload in the benchmark transient.
	 *
	 * @since 7.8.1
	 * @access public
	 *
	 * @return void
	 */
	public static function ajax_benchmark() {
		check_ajax_referer( 'litespeed_oc_benchmark', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
		}

		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
		$oc   = self::cls();

		if ( 'list' === $step ) {
			wp_send_json_success( [ 'candidates' => $oc->ajax_candidate_list() ] );
		}

		if ( 'run' === $step ) {
			$kind = isset( $_POST['kind'] ) ? sanitize_text_field( wp_unslash( $_POST['kind'] ) ) : '';
			if ( 'Redis' !== $kind && 'Memcached' !== $kind ) {
				wp_send_json_error( [ 'message' => 'Invalid kind.' ], 400 );
			}
			$host = isset( $_POST['host'] ) ? sanitize_text_field( wp_unslash( $_POST['host'] ) ) : '';
			$port = isset( $_POST['port'] ) ? (int) $_POST['port'] : 0;
			wp_send_json_success( $oc->benchmark_one( $kind, $host, $port ) );
		}

		if ( 'commit' === $step ) {
			$raw     = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
			$payload = is_string( $raw ) ? json_decode( $raw, true ) : null;
			if ( is_array( $payload ) ) {
				$oc->commit_benchmark( $payload );
			}
			wp_send_json_success();
		}

		wp_send_json_error( [ 'message' => 'Unknown step.' ], 400 );
	}

	/**
	 * Connect to a candidate, time N round-trips, return the average.
	 * Connection failures, auth failures, plus probe-protocol mismatches all
	 * collapse to ok=false with a short reason string the UI can show.
	 *
	 * @since 7.8.1
	 * @access private
	 *
	 * @param string $kind    'Redis' or 'Memcached'.
	 * @param string $host    Host name, IP, or Unix socket path.
	 * @param int    $port    Port; coerced to 0 for socket paths.
	 * @param int    $samples Round-trips to average.
	 * @return array{ok:bool, latency_ms:?float, product:?string, error:?string}
	 *         product: detected server product ('Redis' | 'Valkey' | 'Memcached')
	 *         on success; null on failure.
	 */
	private function _measure_latency( $kind, $host, $port, $samples ) {
		if ( ! class_exists( $kind ) ) {
			return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => $kind . ' extension missing' ];
		}
		if ( '' === (string) $host ) {
			return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => 'empty host' ];
		}

		$is_socket = $this->_is_socket_path( $host );
		if ( $is_socket && ! @file_exists( $host ) ) {
			return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => 'socket file missing' ];
		}

		$probe_port = (int) $port;
		$latencies  = [];
		$product    = null;

		try {
			if ( 'Redis' === $kind ) {
				$conn = new \Redis();
				$ok   = @$conn->connect( $host, $probe_port, 1.5 );
				if ( ! $ok ) {
					$err = error_get_last();
					return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => isset( $err['message'] ) ? $err['message'] : 'connect refused' ];
				}
				if ( $this->_cfg_pswd ) {
					$auth_ok = $this->_cfg_user
						? @$conn->auth( [ $this->_cfg_user, $this->_cfg_pswd ] )
						: @$conn->auth( $this->_cfg_pswd );
					if ( ! $auth_ok ) {
						@$conn->close();
						return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => 'auth failed' ];
					}
				}

				// Valkey >= 7.2 still reports redis_version but adds server_name:valkey / valkey_version:.
				$product = 'Redis';
				try {
					$info = @$conn->rawCommand( 'INFO', 'server' );
					if ( is_string( $info ) && ( false !== stripos( $info, 'server_name:valkey' ) || false !== stripos( $info, 'valkey_version:' ) ) ) {
						$product = 'Valkey';
					}
				} catch ( \Throwable $e ) {
					unset( $e ); // Stay with 'Redis' on INFO failure.
				}

				for ( $i = 0; $i < $samples; $i++ ) {
					$started = microtime( true );
					$reply   = @$conn->rawCommand( 'PING' );
					$elapsed = ( microtime( true ) - $started ) * 1000.0;
					if ( 'PONG' !== $reply && true !== $reply ) {
						@$conn->close();
						return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => 'PING returned unexpected value' ];
					}
					$latencies[] = $elapsed;
				}
				@$conn->close();
			} else {
				$conn = new \Memcached();
				@$conn->setOption( \Memcached::OPT_CONNECT_TIMEOUT, 1500 );
				@$conn->setOption( \Memcached::OPT_SEND_TIMEOUT, 1500000 );
				@$conn->setOption( \Memcached::OPT_RECV_TIMEOUT, 1500000 );
				if ( ! @$conn->addServer( $host, $probe_port ) ) {
					return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => 'addServer failed' ];
				}
				if ( $this->_cfg_user && $this->_cfg_pswd && method_exists( $conn, 'setSaslAuthData' ) ) {
					@$conn->setOption( \Memcached::OPT_BINARY_PROTOCOL, true );
					@$conn->setOption( \Memcached::OPT_COMPRESSION, false );
					@$conn->setSaslAuthData( $this->_cfg_user, $this->_cfg_pswd );
				}
				$probe_key = 'litespeed_oc_bench_' . wp_generate_password( 8, false );
				for ( $i = 0; $i < $samples; $i++ ) {
					$started = microtime( true );
					$set_ok  = @$conn->set( $probe_key, $i, 30 );
					$got     = @$conn->get( $probe_key );
					$elapsed = ( microtime( true ) - $started ) * 1000.0;
					if ( ! $set_ok || (int) $got !== $i ) {
						@$conn->delete( $probe_key );
						return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => 'set/get probe failed (protocol mismatch?)' ];
					}
					$latencies[] = $elapsed;
				}
				@$conn->delete( $probe_key );
				$product = 'Memcached';
			}
		} catch ( \Throwable $e ) {
			return [ 'ok' => false, 'latency_ms' => null, 'product' => null, 'error' => $e->getMessage() ];
		}

		return [ 'ok' => true, 'latency_ms' => array_sum( $latencies ) / count( $latencies ), 'product' => $product, 'error' => null ];
	}

	/**
	 * Pretty-print a host/port pair for status messages.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string $host Host name, IP, or Unix socket path.
	 * @param int    $port Port number; ignored for socket paths.
	 * @return string
	 */
	private function _format_endpoint( $host, $port ) {
		if ( $this->_is_socket_path( $host ) ) {
			return $host;
		}
		return $host . ( $port ? ':' . $port : '' );
	}

	/**
	 * User-facing label for an internal backend kind. Internal tokens stay as
	 * the PHP class names ('Redis' / 'Memcached'); the UI shows "Redis / Valkey".
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string $kind Internal kind token ('Redis' or 'Memcached').
	 * @return string
	 */
	private function _kind_label( $kind ) {
		return 'Redis' === $kind ? 'Redis / Valkey' : $kind;
	}

	/**
	 * Detect a working Redis/Valkey or Memcached backend by walking the
	 * full priority chain for each loaded extension:
	 *
	 *   1. UNIX sockets that exist on disk
	 *   2. Configured host (TCP or socket the admin typed)
	 *   3. localhost on the backend's default port
	 *   4. 127.0.0.1 on the backend's default port
	 *   5. Common conventional hostnames (redis|valkey for Redis,
	 *      memcached|memcache for Memcached) on the default port — these
	 *      resolve via /etc/hosts, LAN DNS, or container DNS depending on
	 *      the environment
	 *
	 * Redis is tried before Memcached because LiteSpeed's recommended Object
	 * Cache backend is Redis/Valkey. Sockets are tried before TCP because
	 * they are the high-performance path on LSWS / cPanel hosts.
	 *
	 * @since  7.8.1
	 * @access public
	 *
	 * @param array $cfg Optional configured host/port to seed the priority
	 *                   chain. Defaults to the current instance config.
	 * @return array|false Detected configuration on success, false otherwise.
	 *                     Shape: [
	 *                       'kind'       => self::KIND_REDIS|self::KIND_MEMCACHED,
	 *                       'kind_label' => 'Redis'|'Memcached',
	 *                       'host'       => string,
	 *                       'port'       => int,
	 *                     ]
	 */
	public function auto_detect( $cfg = [] ) {
		$cfg_host = isset( $cfg['host'] ) ? (string) $cfg['host'] : (string) $this->_cfg_host;
		$cfg_port = isset( $cfg['port'] ) ? (int) $cfg['port'] : (int) $this->_cfg_port;

		// Try Redis/Valkey first (LSCache convention) then Memcached.
		foreach ( [ 'Redis', 'Memcached' ] as $kind ) {
			if ( ! class_exists( $kind ) ) {
				continue;
			}

			foreach ( $this->_candidate_endpoints( $kind, $cfg_host, $cfg_port ) as $candidate ) {
				if ( $this->_attempt( $kind, $candidate['host'], $candidate['port'] ) ) {
					return [
						'kind'       => 'Redis' === $kind ? self::KIND_REDIS : self::KIND_MEMCACHED,
						'kind_label' => $kind,
						'host'       => $candidate['host'],
						'port'       => $candidate['port'],
					];
				}
			}
		}

		return false;
	}

	/**
	 * Build the ordered candidate list for one backend.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string $kind     'Redis' or 'Memcached'.
	 * @param string $cfg_host Configured host (may be empty or a socket path).
	 * @param int    $cfg_port Configured port.
	 * @return array<int,array{host:string,port:int}>
	 */
	private function _candidate_endpoints( $kind, $cfg_host, $cfg_port ) {
		$default_port = 'Redis' === $kind ? 6379 : 11211;
		$candidates   = [];
		$seen         = [];

		$push = function ( $host, $port ) use ( &$candidates, &$seen ) {
			if ( '' === $host ) {
				return;
			}
			$key = $host . '|' . (int) $port;
			if ( isset( $seen[ $key ] ) ) {
				return;
			}
			$seen[ $key ] = true;
			$candidates[] = [ 'host' => $host, 'port' => (int) $port ];
		};

		// 1. UNIX sockets first — the high-performance path on LSWS / cPanel.
		$paths = 'Redis' === $kind
			? $this->_redis_socket_candidates()
			: $this->_memcached_socket_candidates();
		foreach ( $paths as $path ) {
			if ( @file_exists( $path ) ) {
				$push( $path, 0 );
			}
		}

		// 2. Configured host — but only for the saved backend, so a Redis port
		// doesn't leak into the Memcached probe chain.
		$cfg_kind_matches = ( 'Redis' === $kind ) === (bool) $this->_cfg_method;
		if ( $cfg_host && $cfg_kind_matches ) {
			if ( $this->_is_socket_path( $cfg_host ) ) {
				$push( $cfg_host, 0 );
			} else {
				$push( $cfg_host, $cfg_port > 0 ? $cfg_port : $default_port );
			}
		}

		// 3-4. Localhost / 127.0.0.1 on default port ($push de-dupes vs step 2).
		$push( 'localhost', $default_port );
		$push( '127.0.0.1', $default_port );

		// 5. Conventional hostnames (resolved via /etc/hosts, LAN DNS, or container DNS).
		if ( 'Redis' === $kind ) {
			$push( 'redis', $default_port );
			$push( 'valkey', $default_port );
		} else {
			$push( 'memcached', $default_port );
			$push( 'memcache', $default_port );
		}

		return $candidates;
	}

	/**
	 * Whether a string looks like a Unix socket path (absolute path).
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string $host Host string from the config.
	 * @return bool
	 */
	private function _is_socket_path( $host ) {
		return is_string( $host ) && '' !== $host && '/' === $host[0];
	}

	/**
	 * Whether the host environment exposes a Unix socket we could plausibly
	 * connect to. Used by the status panel — surfaces a quick yes/no without
	 * actually opening the socket. Walks the same candidate paths the
	 * auto-detector tries so the status mirrors detection behaviour.
	 *
	 * @since  7.8.1
	 * @access public
	 *
	 * @return bool True if at least one Redis/Valkey or Memcached socket file
	 *              exists on disk.
	 */
	public function has_socket() {
		foreach ( array_merge( $this->_redis_socket_candidates(), $this->_memcached_socket_candidates() ) as $path ) {
			if ( @file_exists( $path ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Best-effort lookup of the current process's home directory. cPanel and
	 * most shared hosts give each account its own /home/{user} tree, which is
	 * where per-user Redis/Memcached sockets live. We deliberately never scan
	 * other users' homes.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @return string Absolute home directory path, or '' if unknown.
	 */
	private function _current_home_dir() {
		if ( function_exists( 'posix_geteuid' ) && function_exists( 'posix_getpwuid' ) ) {
			$info = @posix_getpwuid( posix_geteuid() );
			if ( ! empty( $info['dir'] ) ) {
				return rtrim( $info['dir'], '/' );
			}
		}

		$env_home = getenv( 'HOME' );
		if ( $env_home ) {
			return rtrim( $env_home, '/' );
		}

		// Fall back to deriving from ABSPATH: /home/USER/public_html/... → /home/USER.
		if ( defined( 'ABSPATH' ) && preg_match( '#^(/home/[^/]+)/#', ABSPATH, $m ) ) {
			return $m[1];
		}

		return '';
	}

	/**
	 * Candidate Redis/Valkey socket paths in cPanel-likelihood order.
	 * /tmp/redis.sock comes first because that is the LiteSpeed Web Server /
	 * LSCWP convention. After that we walk the current user's home tree, then
	 * common cPanel container layouts, then system-wide locations.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @return string[]
	 */
	private function _redis_socket_candidates() {
		$paths = [ '/tmp/redis.sock' ];

		$home = $this->_current_home_dir();
		if ( $home ) {
			$paths = array_merge( $paths, [
				$home . '/.redis/redis.sock',
				$home . '/redis.sock',
				$home . '/tmp/redis.sock',
				$home . '/redis/redis.sock',
				$home . '/.applicationmanager/redis.sock',
				$home . '/.kxcache/redis.sock',
				$home . '/etc/redis/redis.sock',
				$home . '/cache/redis.sock',
			] );
			// Container-style: /home/{user}/{container}/redis.sock — shallow wildcards only.
			$paths = array_merge( $paths, $this->_glob_paths( [
				$home . '/*/redis.sock',
				$home . '/*/*/redis.sock',
			] ) );
		}

		// System-wide fallbacks last.
		$paths[] = '/run/redis/redis.sock';
		$paths[] = '/var/run/redis/redis.sock';

		return $paths;
	}

	/**
	 * Candidate Memcached socket paths in cPanel-likelihood order.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @return string[]
	 */
	private function _memcached_socket_candidates() {
		$paths = [ '/tmp/memcached.sock', '/tmp/memcache.sock' ];

		$home = $this->_current_home_dir();
		if ( $home ) {
			$paths = array_merge( $paths, [
				$home . '/.memcached/memcached.sock',
				$home . '/.memcached/memcache.sock',
				$home . '/memcached.sock',
				$home . '/memcache.sock',
				$home . '/tmp/memcached.sock',
				$home . '/tmp/memcache.sock',
				$home . '/memcached/memcached.sock',
				$home . '/memcached/memcache.sock',
				$home . '/.applicationmanager/memcached.sock',
				$home . '/.applicationmanager/memcache.sock',
				$home . '/.kxcache/memcached.sock',
				$home . '/.kxcache/memcache.sock',
				$home . '/etc/memcached/memcached.sock',
				$home . '/etc/memcache/memcache.sock',
				$home . '/cache/memcached.sock',
				$home . '/cache/memcache.sock',
			] );
			$paths = array_merge( $paths, $this->_glob_paths( [
				$home . '/*/memcached.sock',
				$home . '/*/memcache.sock',
				$home . '/*/*/memcached.sock',
				$home . '/*/*/memcache.sock',
			] ) );
		}

		$paths[] = '/run/memcached/memcached.sock';
		$paths[] = '/var/run/memcached/memcached.sock';

		return $paths;
	}

	/**
	 * Resolve a list of glob patterns to actual paths. Silently returns an
	 * empty array if glob() is disabled or the patterns match nothing — we
	 * never want auto-detection to surface error_log noise.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string[] $patterns Glob patterns to expand.
	 * @return string[]
	 */
	private function _glob_paths( $patterns ) {
		if ( ! function_exists( 'glob' ) ) {
			return [];
		}
		$out = [];
		foreach ( $patterns as $pattern ) {
			$matches = @glob( $pattern, GLOB_NOSORT );
			if ( is_array( $matches ) ) {
				$out = array_merge( $out, $matches );
			}
		}
		return $out;
	}

	/**
	 * Attempt to connect to a single backend + endpoint and run real
	 * validation. Errors and exceptions are swallowed silently — the only
	 * signal callers receive is the boolean return value, plus debug logs
	 * when debug mode is on.
	 *
	 * Validation:
	 *  - Redis: rawCommand('PING') must return 'PONG'.
	 *  - Memcached: set/get/delete a short-lived test key.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string $kind 'Redis' or 'Memcached'.
	 * @param string $host Host name, IP, or Unix socket path.
	 * @param int    $port Port number; ignored for socket paths.
	 * @return bool
	 */
	private function _attempt( $kind, $host, $port ) {
		if ( ! class_exists( $kind ) ) {
			return false;
		}
		if ( '' === (string) $host ) {
			return false;
		}
		$is_socket = $this->_is_socket_path( $host );
		if ( $is_socket && ! @file_exists( $host ) ) {
			return false;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		$has_handler = function_exists( 'set_error_handler' );
		if ( $has_handler && function_exists( 'litespeed_exception_handler' ) ) {
			set_error_handler( 'litespeed_exception_handler' );
		}

		$ok = false;
		try {
			if ( 'Redis' === $kind ) {
				$ok = $this->_attempt_redis( $host, (int) $port );
			} else {
				$ok = $this->_attempt_memcached( $host, (int) $port );
			}
		} catch ( \Throwable $e ) {
			$this->debug_oc( '[auto] ' . $kind . ' attempt threw: ' . $e->getMessage() );
			$ok = false;
		}

		if ( $has_handler && function_exists( 'litespeed_exception_handler' ) ) {
			restore_error_handler();
		}

		$this->debug_oc( '[auto] ' . $kind . ' @ ' . $this->_format_endpoint( $host, $port ) . ' => ' . ( $ok ? 'OK' : 'fail' ) );
		return $ok;
	}

	/**
	 * Attempt a Redis/Valkey connection and validate with PING.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string $host Host name, IP, or Unix socket path.
	 * @param int    $port Port number; pass 0 for sockets.
	 * @return bool
	 */
	private function _attempt_redis( $host, $port ) {
		$conn = new \Redis();
		// Short timeout so a misconfigured remote doesn't stall the settings page.
		if ( $port ) {
			$ok = @$conn->connect( $host, $port, 1.5 );
		} else {
			$ok = @$conn->connect( $host, 0, 1.5 );
		}
		if ( ! $ok ) {
			return false;
		}

		if ( $this->_cfg_pswd ) {
			try {
				if ( $this->_cfg_user ) {
					@$conn->auth( [ $this->_cfg_user, $this->_cfg_pswd ] );
				} else {
					@$conn->auth( $this->_cfg_pswd );
				}
			} catch ( \Throwable $e ) {
				@$conn->close();
				return false;
			}
		}

		try {
			$pong = @$conn->rawCommand( 'PING' );
		} catch ( \Throwable $e ) {
			@$conn->close();
			return false;
		}
		@$conn->close();

		return 'PONG' === $pong || true === $pong;
	}

	/**
	 * Attempt a Memcached connection and validate with set/get/delete.
	 *
	 * @since  7.8.1
	 * @access private
	 *
	 * @param string $host Host name, IP, or Unix socket path.
	 * @param int    $port Port number; pass 0 for sockets.
	 * @return bool
	 */
	private function _attempt_memcached( $host, $port ) {
		$conn = new \Memcached();
		@$conn->setOption( \Memcached::OPT_CONNECT_TIMEOUT, 1500 ); // ms.
		@$conn->setOption( \Memcached::OPT_SEND_TIMEOUT, 1500000 ); // µs.
		@$conn->setOption( \Memcached::OPT_RECV_TIMEOUT, 1500000 );

		if ( ! @$conn->addServer( $host, $port ) ) {
			return false;
		}

		if ( $this->_cfg_user && $this->_cfg_pswd && method_exists( $conn, 'setSaslAuthData' ) ) {
			@$conn->setOption( \Memcached::OPT_BINARY_PROTOCOL, true );
			@$conn->setOption( \Memcached::OPT_COMPRESSION, false );
			@$conn->setSaslAuthData( $this->_cfg_user, $this->_cfg_pswd );
		}

		$probe_key = 'litespeed_oc_probe_' . wp_generate_password( 6, false );
		$set_ok    = @$conn->set( $probe_key, 1, 30 );
		if ( ! $set_ok ) {
			return false;
		}
		$get_val = @$conn->get( $probe_key );
		@$conn->delete( $probe_key );

		return 1 === $get_val || '1' === $get_val;
	}

	/**
	 * Force to connect with this setting.
	 *
	 * @since  1.8
	 * @access private
	 *
	 * @param array $cfg Reconnect configuration.
	 * @return void
	 */
	private function _reconnect( $cfg ) {
		$this->debug_oc( 'Reconnecting' );
		if ( isset( $this->_conn ) ) {
			// error_log( 'Object: Quitting existing connection!' );
			$this->debug_oc( 'Quitting existing connection' );
			$this->flush();
			$this->_conn = null;
			$this->cls( false, true );
		}

		$cls = $this->cls( false, false, $cfg );
		$cls->_connect();
		if ( isset( $cls->_conn ) ) {
			$cls->flush();
		}
	}

	/**
	 * Connect to Memcached/Redis server.
	 *
	 * @since  1.8
	 * @access private
	 *
	 * @return bool|null False on failure, true on success, null if driver missing.
	 */
	private function _connect() {
		if ( isset( $this->_conn ) ) {
			// error_log( 'Object: _connected' );
			return true;
		}

		if ( ! class_exists( $this->_oc_driver ) || ! $this->_cfg_host ) {
			$this->debug_oc( '_oc_driver cls non existed or _cfg_host missed: ' . $this->_oc_driver . ' [_cfg_host] ' . $this->_cfg_host . ':' . $this->_cfg_port );
			return false;
		}

		if ( defined( 'LITESPEED_OC_FAILURE' ) ) {
			$this->debug_oc( 'LITESPEED_OC_FAILURE const defined' );
			return false;
		}

		$this->debug_oc( 'Init ' . $this->_oc_driver . ' connection to ' . $this->_cfg_host . ':' . $this->_cfg_port );

		$failed = false;

		/**
		 * Connect to Redis.
		 *
		 * @since  1.8.1
		 * @see https://github.com/phpredis/phpredis/#example-1
		 */
		if ( 'Redis' === $this->_oc_driver ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			set_error_handler( 'litespeed_exception_handler' );
			try {
				$this->_conn = new \Redis();
				// error_log( 'Object: _connect Redis' );

				if ( $this->_cfg_persistent ) {
					if ( $this->_cfg_port ) {
						$this->_conn->pconnect( $this->_cfg_host, $this->_cfg_port );
					} else {
						$this->_conn->pconnect( $this->_cfg_host );
					}
				} elseif ( $this->_cfg_port ) {
					$this->_conn->connect( $this->_cfg_host, $this->_cfg_port );
				} else {
					$this->_conn->connect( $this->_cfg_host );
				}

				if ( $this->_cfg_pswd ) {
					if ( $this->_cfg_user ) {
						$this->_conn->auth( [ $this->_cfg_user, $this->_cfg_pswd ] );
					} else {
						$this->_conn->auth( $this->_cfg_pswd );
					}
				}

				if ( defined( 'Redis::OPT_REPLY_LITERAL' ) ) {
					$this->debug_oc( 'Redis set OPT_REPLY_LITERAL' );
					$this->_conn->setOption( \Redis::OPT_REPLY_LITERAL, true );
				}

				// Enable phpredis-level zstd compression to cut Redis memory use. Payload-level only: do NOT set OPT_SERIALIZER, LSCWP already runs maybe_serialize upstream and a second serializer would corrupt reads.
				if ( defined( 'Redis::OPT_COMPRESSION' ) && defined( 'Redis::COMPRESSION_ZSTD' ) ) {
					$this->debug_oc( 'Redis set OPT_COMPRESSION to ZSTD' );
					$this->_conn->setOption( \Redis::OPT_COMPRESSION, \Redis::COMPRESSION_ZSTD );
				}

				if ( $this->_cfg_db ) {
					if ( ! $this->_conn->select( $this->_cfg_db ) ) {
						$this->debug_oc( 'Database ID is invalid' );
						$failed = true;
					}
				}

				$res = $this->_conn->rawCommand('PING');

				if ( 'PONG' !== $res ) {
					$this->debug_oc( 'Redis resp is wrong: ' . $res );
					$failed = true;
				}
			} catch ( \Exception $e ) {
				$this->debug_oc( 'Redis connect exception: ' . $e->getMessage() );
				$failed = true;
			} catch ( \ErrorException $e ) {
				$this->debug_oc( 'Redis connect error: ' . $e->getMessage() );
				$failed = true;
			}
			restore_error_handler();
		} else {
			// Connect to Memcached.
			if ( $this->_cfg_persistent ) {
				$this->_conn = new \Memcached( $this->_get_mem_id() );

				// Check memcached persistent connection.
				if ( $this->_validate_mem_server() ) {
					// error_log( 'Object: _validate_mem_server' );
					$this->debug_oc( 'Got persistent ' . $this->_oc_driver . ' connection' );
					return true;
				}

				$this->debug_oc( 'No persistent ' . $this->_oc_driver . ' server list!' );
			} else {
				// error_log( 'Object: new memcached!' );
				$this->_conn = new \Memcached();
			}

			$this->_conn->addServer( $this->_cfg_host, (int) $this->_cfg_port );

			/**
			 * Add SASL auth.
			 *
			 * @since  1.8.1
			 * @since  2.9.6 Fixed SASL connection @see https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:lsmcd:new_sasl
			 */
			if ( $this->_cfg_user && $this->_cfg_pswd && method_exists( $this->_conn, 'setSaslAuthData' ) ) {
				$this->_conn->setOption( \Memcached::OPT_BINARY_PROTOCOL, true );
				$this->_conn->setOption( \Memcached::OPT_COMPRESSION, false );
				$this->_conn->setSaslAuthData( $this->_cfg_user, $this->_cfg_pswd );
			}

			// Check connection.
			if ( ! $this->_validate_mem_server() ) {
				$failed = true;
			}
		}

		// If failed to connect.
		if ( $failed ) {
			$this->debug_oc( '❌ Failed to connect ' . $this->_oc_driver . ' server!' );
			$this->_conn        = null;
			$this->_cfg_enabled = false;
			! defined( 'LITESPEED_OC_FAILURE' ) && define( 'LITESPEED_OC_FAILURE', true );

			// Disable ext OC flag so WP transients fall back to wp_options table.
			// After muplugins_loaded, all wp_start_object_cache() calls are done — safe to call directly.
			// Before that (early bootstrap), defer via hook to avoid multisite "Cannot redeclare" fatal.
			if ( function_exists( 'did_action' ) && did_action( 'muplugins_loaded' ) ) {
				wp_using_ext_object_cache( false );
			} elseif ( function_exists( 'add_action' ) ) {
				add_action(
					'muplugins_loaded',
					function () {
						wp_using_ext_object_cache( false );
					},
					-999
				);
			}

			return false;
		}

		$this->debug_oc( '✅ Connected to ' . $this->_oc_driver . ' server.' );

		return true;
	}

	/**
	 * Check if the connected memcached host is the one in cfg.
	 *
	 * @since  1.8
	 * @access private
	 *
	 * @return bool
	 */
	private function _validate_mem_server() {
		$mem_list = $this->_conn->getStats();
		if ( empty( $mem_list ) ) {
			return false;
		}

		foreach ( $mem_list as $k => $v ) {
			if ( substr( $k, 0, strlen( $this->_cfg_host ) ) !== $this->_cfg_host ) {
				continue;
			}
			if ( ! empty( $v['pid'] ) || ! empty( $v['curr_connections'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get memcached unique id to be used for connecting.
	 *
	 * @since  1.8
	 * @access private
	 *
	 * @return string
	 */
	private function _get_mem_id() {
		$mem_id = 'litespeed';
		if ( is_multisite() ) {
			$mem_id .= '_' . get_current_blog_id();
		}

		return $mem_id;
	}

	/**
	 * Get cache.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @param string $key   Cache key.
	 * @param string $group Optional. Cache group name.
	 * @return mixed|false
	 */
	public function get( $key, $group = '' ) {
		if ( ! $this->_cfg_enabled ) {
			return false;
		}

		if ( ! $this->_can_cache( $group ) ) {
			return false;
		}

		if ( ! $this->_connect() ) {
			return false;
		}

		if ( 'Redis' === $this->_oc_driver ) {
			try {
				$res = $this->_conn->get( $key );
			} catch ( \RedisException $ex ) {
				$this->_redis_error( $ex );
				return false;
			}
		} else {
			$res = $this->_conn->get( $key );
		}

		return $res;
	}

	/**
	 * Set cache.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $data   Data to store.
	 * @param int    $expire TTL seconds.
	 * @return bool
	 */
	public function set( $key, $data, $expire ) {
		if ( ! $this->_cfg_enabled ) {
			return false;
		}

		/**
		 * To fix the Cloud callback cached as its frontend call but the hash is generated in backend
		 * Bug found by Stan at Jan/10/2020
		 */
		// if ( ! $this->_can_cache() ) {
		// return false;
		// }

		if ( ! $this->_connect() ) {
			return false;
		}

		// Per WP Object Cache API, expire=0 means "no expiration".
		// Key eviction is handled by the cache backend (Redis maxmemory / Memcached LRU).
		$ttl = (int) $expire;

		if ( 'Redis' === $this->_oc_driver ) {
			try {
				$options = ( $ttl > 0 ) ? [ 'ex' => $ttl ] : [];
				$res     = $this->_conn->set( $key, $data, $options );
			} catch ( \RedisException $ex ) {
				$res = false;
				$this->_redis_error( $ex );
			}
		} else {
			$res = $this->_conn->set( $key, $data, $ttl );
		}

		return $res;
	}

	/**
	 * Check if can cache or not.
	 *
	 * @since  1.8
	 * @access private
	 *
	 * @param string $group Optional. Cache group name.
	 * @return bool
	 */
	private function _can_cache( $group = '' ) {
		// Transients always use OC regardless of Cache WP-Admin setting
		if ( $this->_is_transients_group( $group ) ) {
			return true;
		}
		if ( ! $this->_cfg_admin && defined( 'WP_ADMIN' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Delete cache.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( $key ) {
		if ( ! $this->_cfg_enabled ) {
			return false;
		}

		if ( ! $this->_connect() ) {
			return false;
		}

		if ( 'Redis' === $this->_oc_driver ) {
			try {
				$res = $this->_conn->del( $key );
			} catch ( \RedisException $ex ) {
				$this->_redis_error( $ex );
				return false;
			}
		} else {
			$res = $this->_conn->delete( $key );
		}

		return (bool) $res;
	}

	/**
	 * Clear all cache.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @return bool
	 */
	public function flush() {
		if ( ! $this->_cfg_enabled ) {
			$this->debug_oc( 'bypass flushing' );
			return false;
		}

		if ( ! $this->_connect() ) {
			return false;
		}

		$this->debug_oc( 'flush!' );

		if ( 'Redis' === $this->_oc_driver ) {
			try {
				$res = $this->_conn->flushDb();
			} catch ( \RedisException $ex ) {
				$this->_redis_error( $ex );
				return false;
			}
		} else {
			$res = $this->_conn->flush();
			$this->_conn->resetServerList();
		}

		return $res;
	}

	/**
	 * Log a Redis exception and surface it as an admin notice.
	 *
	 * @since 7.9
	 * @access private
	 *
	 * @param \RedisException $ex Exception raised by phpredis.
	 * @return void
	 */
	private function _redis_error( $ex ) {
		$this->debug_oc( sprintf( 'Redis op failed: %s (code: %d)', $ex->getMessage(), $ex->getCode() ) );

		$this->_cfg_enabled = false;

		if ( did_action( 'plugins_loaded' ) ) {
			Admin_Display::error( 'LiteSpeed Object Cache: Redis is unavailable. Check Redis server status (memory, connectivity) and the plugin debug log for details.' );
		}
	}

	/**
	 * Add global groups.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param string|string[] $groups Group(s) to add.
	 * @return void
	 */
	public function add_global_groups( $groups ) {
		if ( ! is_array( $groups ) ) {
			$groups = [ $groups ];
		}

		$this->_global_groups = array_merge( $this->_global_groups, $groups );
		$this->_global_groups = array_unique( $this->_global_groups );
	}

	/**
	 * Check if is in global groups or not.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param string $group Group name.
	 * @return bool
	 */
	public function is_global( $group ) {
		return in_array( $group, $this->_global_groups, true );
	}

	/**
	 * Add non persistent groups.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param string|string[] $groups Group(s) to add.
	 * @return void
	 */
	public function add_non_persistent_groups( $groups ) {
		if ( ! is_array( $groups ) ) {
			$groups = [ $groups ];
		}

		$this->_non_persistent_groups = array_merge( $this->_non_persistent_groups, $groups );
		$this->_non_persistent_groups = array_unique( $this->_non_persistent_groups );
	}

	/**
	 * Check if is in non persistent groups or not.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param string $group Group name.
	 * @return bool
	 */
	public function is_non_persistent( $group ) {
		return in_array( $group, $this->_non_persistent_groups, true );
	}
}
