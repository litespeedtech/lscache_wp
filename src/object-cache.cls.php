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
	 * Transients store flag.
	 *
	 * @var string
	 */
	const O_OBJECT_TRANSIENTS = 'object-transients';

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
	 * TTL in seconds.
	 *
	 * @var int
	 */
	private $_cfg_life;

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
	 * Store transients.
	 *
	 * @var bool
	 */
	private $_cfg_transients;

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
	 * Default TTL in seconds.
	 *
	 * @var int
	 */
	private $_default_life = 360;

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
	private $_global_groups = array();

	/**
	 * Non-persistent groups.
	 *
	 * @var array
	 */
	private $_non_persistent_groups = array();

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
			$this->_cfg_method            = $cfg[ Base::O_OBJECT_KIND ] ? true : false;
			$this->_cfg_host              = $cfg[ Base::O_OBJECT_HOST ];
			$this->_cfg_port              = $cfg[ Base::O_OBJECT_PORT ];
			$this->_cfg_life              = $cfg[ Base::O_OBJECT_LIFE ];
			$this->_cfg_persistent        = $cfg[ Base::O_OBJECT_PERSISTENT ];
			$this->_cfg_admin             = $cfg[ Base::O_OBJECT_ADMIN ];
			$this->_cfg_transients        = $cfg[ Base::O_OBJECT_TRANSIENTS ];
			$this->_cfg_db                = $cfg[ Base::O_OBJECT_DB_ID ];
			$this->_cfg_user              = $cfg[ Base::O_OBJECT_USER ];
			$this->_cfg_pswd              = $cfg[ Base::O_OBJECT_PSWD ];
			$this->_global_groups         = $cfg[ Base::O_OBJECT_GLOBAL_GROUPS ];
			$this->_non_persistent_groups = $cfg[ Base::O_OBJECT_NON_PERSISTENT_GROUPS ];

			if ( $this->_cfg_method ) {
				$this->_oc_driver = 'Redis';
			}
			$this->_cfg_enabled = $cfg[ Base::O_OBJECT ] && class_exists( $this->_oc_driver ) && $this->_cfg_host;
		} elseif ( defined( 'LITESPEED_CONF_LOADED' ) ) { // If OC is OFF, will hit here to init OC after conf initialized
			$this->_cfg_debug             = $this->conf( Base::O_DEBUG ) ? $this->conf( Base::O_DEBUG ) : false;
			$this->_cfg_method            = $this->conf( Base::O_OBJECT_KIND ) ? true : false;
			$this->_cfg_host              = $this->conf( Base::O_OBJECT_HOST );
			$this->_cfg_port              = $this->conf( Base::O_OBJECT_PORT );
			$this->_cfg_life              = $this->conf( Base::O_OBJECT_LIFE );
			$this->_cfg_persistent        = $this->conf( Base::O_OBJECT_PERSISTENT );
			$this->_cfg_admin             = $this->conf( Base::O_OBJECT_ADMIN );
			$this->_cfg_transients        = $this->conf( Base::O_OBJECT_TRANSIENTS );
			$this->_cfg_db                = $this->conf( Base::O_OBJECT_DB_ID );
			$this->_cfg_user              = $this->conf( Base::O_OBJECT_USER );
			$this->_cfg_pswd              = $this->conf( Base::O_OBJECT_PSWD );
			$this->_global_groups         = $this->conf( Base::O_OBJECT_GLOBAL_GROUPS );
			$this->_non_persistent_groups = $this->conf( Base::O_OBJECT_NON_PERSISTENT_GROUPS );

			if ( $this->_cfg_method ) {
				$this->_oc_driver = 'Redis';
			}
			$this->_cfg_enabled = $this->conf( Base::O_OBJECT ) && class_exists( $this->_oc_driver ) && $this->_cfg_host;
		} elseif ( defined( 'self::CONF_FILE' ) && file_exists( WP_CONTENT_DIR . '/' . self::CONF_FILE ) ) {
			// Get cfg from _data_file.
			// Use self::const to avoid loading more classes.
			$cfg = \json_decode( file_get_contents( WP_CONTENT_DIR . '/' . self::CONF_FILE ), true );
			if ( ! empty( $cfg[ self::O_OBJECT_HOST ] ) ) {
				$this->_cfg_debug             = ! empty( $cfg[ Base::O_DEBUG ] ) ? $cfg[ Base::O_DEBUG ] : false;
				$this->_cfg_method            = ! empty( $cfg[ self::O_OBJECT_KIND ] ) ? $cfg[ self::O_OBJECT_KIND ] : false;
				$this->_cfg_host              = $cfg[ self::O_OBJECT_HOST ];
				$this->_cfg_port              = $cfg[ self::O_OBJECT_PORT ];
				$this->_cfg_life              = ! empty( $cfg[ self::O_OBJECT_LIFE ] ) ? $cfg[ self::O_OBJECT_LIFE ] : $this->_default_life;
				$this->_cfg_persistent        = ! empty( $cfg[ self::O_OBJECT_PERSISTENT ] ) ? $cfg[ self::O_OBJECT_PERSISTENT ] : false;
				$this->_cfg_admin             = ! empty( $cfg[ self::O_OBJECT_ADMIN ] ) ? $cfg[ self::O_OBJECT_ADMIN ] : false;
				$this->_cfg_transients        = ! empty( $cfg[ self::O_OBJECT_TRANSIENTS ] ) ? $cfg[ self::O_OBJECT_TRANSIENTS ] : false;
				$this->_cfg_db                = ! empty( $cfg[ self::O_OBJECT_DB_ID ] ) ? $cfg[ self::O_OBJECT_DB_ID ] : 0;
				$this->_cfg_user              = ! empty( $cfg[ self::O_OBJECT_USER ] ) ? $cfg[ self::O_OBJECT_USER ] : '';
				$this->_cfg_pswd              = ! empty( $cfg[ self::O_OBJECT_PSWD ] ) ? $cfg[ self::O_OBJECT_PSWD ] : '';
				$this->_global_groups         = ! empty( $cfg[ self::O_OBJECT_GLOBAL_GROUPS ] ) ? $cfg[ self::O_OBJECT_GLOBAL_GROUPS ] : array();
				$this->_non_persistent_groups = ! empty( $cfg[ self::O_OBJECT_NON_PERSISTENT_GROUPS ] ) ? $cfg[ self::O_OBJECT_NON_PERSISTENT_GROUPS ] : array();

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
	 * Get `Store Transients` setting value.
	 *
	 * @since  1.8.3
	 * @access public
	 *
	 * @param string $group Group name.
	 * @return bool
	 */
	public function store_transients( $group ) {
		return $this->_cfg_transients && $this->_is_transients_group( $group );
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
		return in_array( $group, array( 'transient', 'site-transient' ), true );
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
	 * Try to build connection.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @return bool|null False on failure, true on success, null if unsupported.
	 */
	public function test_connection() {
		return $this->_connect();
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
			return null;
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
						$this->_conn->auth( array( $this->_cfg_user, $this->_cfg_pswd ) );
					} else {
						$this->_conn->auth( $this->_cfg_pswd );
					}
				}

				if (defined('Redis::OPT_REPLY_LITERAL')) {
					$this->debug_oc( 'Redis set OPT_REPLY_LITERAL' );
					$this->_conn->setOption(\Redis::OPT_REPLY_LITERAL, true);
				}

				if ( $this->_cfg_db ) {
					$this->_conn->select( $this->_cfg_db );
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
			// error_log( 'Object: false!' );
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
	 * @param string $key Cache key.
	 * @return mixed|null
	 */
	public function get( $key ) {
		if ( ! $this->_cfg_enabled ) {
			return null;
		}

		if ( ! $this->_can_cache() ) {
			return null;
		}

		if ( ! $this->_connect() ) {
			return null;
		}

		$res = $this->_conn->get( $key );

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
	 * @return bool|null
	 */
	public function set( $key, $data, $expire ) {
		if ( ! $this->_cfg_enabled ) {
			return null;
		}

		/**
		 * To fix the Cloud callback cached as its frontend call but the hash is generated in backend
		 * Bug found by Stan at Jan/10/2020
		 */
		// if ( ! $this->_can_cache() ) {
		// return null;
		// }

		if ( ! $this->_connect() ) {
			return null;
		}

		$ttl = $expire ? $expire : $this->_cfg_life;

		if ( 'Redis' === $this->_oc_driver ) {
			try {
				$res = $this->_conn->setEx( $key, $ttl, $data );
			} catch ( \RedisException $ex ) {
				$res = false;
				$msg = sprintf( __( 'Redis encountered a fatal error: %1$s (code: %2$d)', 'litespeed-cache' ), $ex->getMessage(), $ex->getCode() );
				$this->debug_oc( $msg );
				Admin_Display::error( $msg );
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
	 * @return bool
	 */
	private function _can_cache() {
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
	 * @return bool|null
	 */
	public function delete( $key ) {
		if ( ! $this->_cfg_enabled ) {
			return null;
		}

		if ( ! $this->_connect() ) {
			return null;
		}

		if ( 'Redis' === $this->_oc_driver ) {
			$res = $this->_conn->del( $key );
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
	 * @return bool|null
	 */
	public function flush() {
		if ( ! $this->_cfg_enabled ) {
			$this->debug_oc( 'bypass flushing' );
			return null;
		}

		if ( ! $this->_connect() ) {
			return null;
		}

		$this->debug_oc( 'flush!' );

		if ( 'Redis' === $this->_oc_driver ) {
			$res = $this->_conn->flushDb();
		} else {
			$res = $this->_conn->flush();
			$this->_conn->resetServerList();
		}

		return $res;
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
			$groups = array( $groups );
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
			$groups = array( $groups );
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
