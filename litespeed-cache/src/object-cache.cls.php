<?php
/**
 * The object cache class
 *
 * @since      	1.8
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Object_Cache
{
	protected static $_instance ;

	private $_oc_data_file ;
	private $_conn ;
	private $_cfg_enabled ;
	private $_cfg_method ;
	private $_cfg_host ;
	private $_cfg_port ;
	private $_cfg_persistent ;
	private $_cfg_admin ;
	private $_cfg_transients ;
	private $_cfg_db ;
	private $_cfg_user ;
	private $_cfg_pswd ;
	private $_default_life = 360 ;

	private $_oc_driver = 'Memcached' ; // Redis or Memcached

	private $_global_groups ;
	private $_non_persistent_groups ;

	/**
	 * Init
	 *
	 * NOTE: this class may be included without initialized  core
	 *
	 * @since  1.8
	 * @access protected
	 */
	protected function __construct( $cfg = false )
	{
		defined( 'LSCWP_LOG' ) && Log::debug2( '[Object] init' ) ;

		$this->_oc_data_file = WP_CONTENT_DIR . '/.object-cache.ini' ;

		if ( $cfg ) {
			if ( ! is_array( $cfg[ Conf::O_OBJECT_GLOBAL_GROUPS ] ) ) {
				$cfg[ Conf::O_OBJECT_GLOBAL_GROUPS ] = explode( "\n", $cfg[ Conf::O_OBJECT_GLOBAL_GROUPS ] ) ;
			}
			if ( ! is_array( $cfg[ Conf::O_OBJECT_NON_PERSISTENT_GROUPS ] ) ) {
				$cfg[ Conf::O_OBJECT_NON_PERSISTENT_GROUPS ] = explode( "\n", $cfg[ Conf::O_OBJECT_NON_PERSISTENT_GROUPS ] ) ;
			}
			$this->_cfg_method = $cfg[ Conf::O_OBJECT_KIND ] ? true : false ;
			$this->_cfg_host = $cfg[ Conf::O_OBJECT_HOST ] ;
			$this->_cfg_port = $cfg[ Conf::O_OBJECT_PORT ] ;
			$this->_cfg_life = $cfg[ Conf::O_OBJECT_LIFE ] ;
			$this->_cfg_persistent = $cfg[ Conf::O_OBJECT_PERSISTENT ] ;
			$this->_cfg_admin = $cfg[ Conf::O_OBJECT_ADMIN ] ;
			$this->_cfg_transients = $cfg[ Conf::O_OBJECT_TRANSIENTS ] ;
			$this->_cfg_db = $cfg[ Conf::O_OBJECT_DB_ID ] ;
			$this->_cfg_user = $cfg[ Conf::O_OBJECT_USER ] ;
			$this->_cfg_pswd = $cfg[ Conf::O_OBJECT_PSWD ] ;
			$this->_global_groups = $cfg[ Conf::O_OBJECT_GLOBAL_GROUPS ] ;
			$this->_non_persistent_groups = $cfg[ Conf::O_OBJECT_NON_PERSISTENT_GROUPS ] ;

			if ( $this->_cfg_method ) {
				$this->_oc_driver = 'Redis' ;
			}
			$this->_cfg_enabled = $cfg[ Conf::O_OBJECT ] && class_exists( $this->_oc_driver ) && $this->_cfg_host ;

			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] init with cfg result : ', $this->_cfg_enabled ) ;
		}
		elseif ( class_exists( __NAMESPACE__ . '\Core' ) ) {
			$this->_cfg_method = Core::config( Conf::O_OBJECT_KIND ) ? true : false ;
			$this->_cfg_host = Core::config( Conf::O_OBJECT_HOST ) ;
			$this->_cfg_port = Core::config( Conf::O_OBJECT_PORT ) ;
			$this->_cfg_life = Core::config( Conf::O_OBJECT_LIFE ) ;
			$this->_cfg_persistent = Core::config( Conf::O_OBJECT_PERSISTENT ) ;
			$this->_cfg_admin = Core::config( Conf::O_OBJECT_ADMIN ) ;
			$this->_cfg_transients = Core::config( Conf::O_OBJECT_TRANSIENTS ) ;
			$this->_cfg_db = Core::config( Conf::O_OBJECT_DB_ID ) ;
			$this->_cfg_user = Core::config( Conf::O_OBJECT_USER ) ;
			$this->_cfg_pswd = Core::config( Conf::O_OBJECT_PSWD ) ;
			$this->_global_groups = Core::config( Conf::O_OBJECT_GLOBAL_GROUPS ) ;
			$this->_non_persistent_groups = Core::config( Conf::O_OBJECT_NON_PERSISTENT_GROUPS ) ;

			if ( $this->_cfg_method ) {
				$this->_oc_driver = 'Redis' ;
			}
			$this->_cfg_enabled = Core::config( Conf::O_OBJECT ) && class_exists( $this->_oc_driver ) && $this->_cfg_host ;
		}
		elseif ( file_exists( $this->_oc_data_file ) ) { // Get cfg from oc_data_file
			$cfg = parse_ini_file( $this->_oc_data_file, true ) ;
			$this->_cfg_method = ! empty( $cfg[ 'object_cache' ][ 'method' ] ) ? $cfg[ 'object_cache' ][ 'method' ] : false ;
			$this->_cfg_host = $cfg[ 'object_cache' ][ 'host' ] ;
			$this->_cfg_port = $cfg[ 'object_cache' ][ 'port' ] ;
			$this->_cfg_life = ! empty( $cfg[ 'object_cache' ][ 'life' ] ) ? $cfg[ 'object_cache' ][ 'life' ] : $this->_default_life ;
			$this->_cfg_persistent = ! empty( $cfg[ 'object_cache' ][ 'persistent' ] ) ? $cfg[ 'object_cache' ][ 'persistent' ] : false ;
			$this->_cfg_admin = ! empty( $cfg[ 'object_cache' ][ 'cache_admin' ] ) ? $cfg[ 'object_cache' ][ 'cache_admin' ] : false ;
			$this->_cfg_transients = ! empty( $cfg[ 'object_cache' ][ 'cache_transients' ] ) ? $cfg[ 'object_cache' ][ 'cache_transients' ] : false ;
			$this->_cfg_db = ! empty( $cfg[ 'object_cache' ][ 'db' ] ) ? $cfg[ 'object_cache' ][ 'db' ] : 0 ;
			$this->_cfg_user = ! empty( $cfg[ 'object_cache' ][ 'user' ] ) ? $cfg[ 'object_cache' ][ 'user' ] : '' ;
			$this->_cfg_pswd = ! empty( $cfg[ 'object_cache' ][ 'pswd' ] ) ? $cfg[ 'object_cache' ][ 'pswd' ] : '' ;
			$this->_global_groups = ! empty( $cfg[ 'object_cache' ][ 'global_groups' ] ) ? explode( ',', $cfg[ 'object_cache' ][ 'global_groups' ] ) : array() ;
			$this->_non_persistent_groups = ! empty( $cfg[ 'object_cache' ][ 'non_persistent_groups' ] ) ? explode( ',', $cfg[ 'object_cache' ][ 'non_persistent_groups' ] ) : array() ;

			if ( $this->_cfg_method ) {
				$this->_oc_driver = 'Redis' ;
			}
			$this->_cfg_enabled = class_exists( $this->_oc_driver ) && $this->_cfg_host ;
		}
		else {
			$this->_cfg_enabled = false ;
		}
	}

	/**
	 * Get `Store Transients` setting value
	 *
	 * @since  1.8.3
	 * @access public
	 */
	public function store_transients( $group )
	{
		return $this->_cfg_transients && $this->_is_transients_group( $group ) ;
	}

	/**
	 * Check if the group belongs to transients or not
	 *
	 * @since  1.8.3
	 * @access private
	 */
	private function _is_transients_group( $group )
	{
		return in_array( $group, array( 'transient', 'site-transient' ) ) ;
	}

	/**
	 * Update WP object cache file config
	 *
	 * @since  1.8
	 * @access public
	 */
	public function update_file( $options )
	{
		$changed = false ;

		// Update data file
		$data = "[object_cache]"
			. "\nmethod = " . $options[ Conf::O_OBJECT_KIND ]
			. "\nhost = " . $options[ Conf::O_OBJECT_HOST ]
			. "\nport = " . (int) $options[ Conf::O_OBJECT_PORT ]
			. "\nlife = " . $options[ Conf::O_OBJECT_LIFE ]
			. "\nuser = '" . $options[ Conf::O_OBJECT_USER ] . "'"
			. "\npswd = '" . $options[ Conf::O_OBJECT_PSWD ] . "'"
			. "\ndb = " . (int) $options[ Conf::O_OBJECT_DB_ID ]
			. "\npersistent = " . ( $options[ Conf::O_OBJECT_PERSISTENT ] ? 1 : 0 )
			. "\ncache_admin = " . ( $options[ Conf::O_OBJECT_ADMIN ] ? 1 : 0 )
			. "\ncache_transients = " . ( $options[ Conf::O_OBJECT_TRANSIENTS ] ? 1 : 0 )
			. "\nglobal_groups = " . implode( ',', $options[ Conf::O_OBJECT_GLOBAL_GROUPS ] )
			. "\nnon_persistent_groups = " . implode( ',', $options[ Conf::O_OBJECT_NON_PERSISTENT_GROUPS ] )
			;

		$old_data = File::read( $this->_oc_data_file ) ;
		if ( $old_data != $data ) {
			defined( 'LSCWP_LOG' ) && Log::debug( '[Settings] Update .object_cache.ini and flush object cache' ) ;
			File::save( $this->_oc_data_file, $data ) ;

			$changed = true ;
		}

		// NOTE: When included in oc.php, `LSCWP_DIR` will show undefined, so this must be assigned/generated when used
		$_oc_ori_file = LSCWP_DIR . 'lib/object-cache.php' ;
		$_oc_wp_file = WP_CONTENT_DIR . '/object-cache.php' ;

		// Update cls file
		if ( ! file_exists( $_oc_wp_file ) || md5_file( $_oc_wp_file ) !== md5_file( $_oc_ori_file ) ) {
			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] copying object-cache.php file to ' . $_oc_wp_file ) ;
			copy( $_oc_ori_file, $_oc_wp_file ) ;

			$changed = true ;
		}

		/**
		 * Clear object cache
		 */
		if ( $changed ) {
			$this->_reconnect( $options ) ;
		}
	}

	/**
	 * Remove object cache file
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public function del_file()
	{
		// NOTE: When included in oc.php, `LSCWP_DIR` will show undefined, so this must be assigned/generated when used
		$_oc_ori_file = LSCWP_DIR . 'lib/object-cache.php' ;
		$_oc_wp_file = WP_CONTENT_DIR . '/object-cache.php' ;

		if ( file_exists( $_oc_wp_file ) && md5_file( $_oc_wp_file ) === md5_file( $_oc_ori_file ) ) {
			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] removing ' . $_oc_wp_file ) ;
			unlink( $_oc_wp_file ) ;
		}

		if ( file_exists( $this->_oc_data_file ) ) {
			Log::debug( '[Object] Removing ' . $this->_oc_data_file ) ;
			unlink( $this->_oc_data_file ) ;
		}
	}

	/**
	 * Try to build connection
	 *
	 * @since  1.8
	 * @access public
	 */
	public function test_connection()
	{
		return $this->_connect() ;
	}

	/**
	 * Force to connect with this setting
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _reconnect( $cfg )
	{
		defined( 'LSCWP_LOG' ) && Log::debug( '[Object] Reconnecting' ) ;
		// error_log( 'Object: reconnect !' ) ;
		if ( isset( $this->_conn ) ) {
			// error_log( 'Object: Quiting existing connection!' ) ;
			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] Quiting existing connection' ) ;
			$this->flush() ;
			$this->_conn = null ;
			self::$_instance = null ;
		}

		self::$_instance = new self( $cfg ) ;
		self::$_instance->_connect() ;
		if ( isset( self::$_instance->_conn ) ) {
			self::$_instance->flush() ;
		}

	}

	/**
	 * Connect to Memcached/Redis server
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _connect()
	{
		if ( isset( $this->_conn ) ) {
			// error_log( 'Object: _connected' ) ;
			return true ;
		}

		if ( ! class_exists( $this->_oc_driver ) || ! $this->_cfg_host ) {
			return null ;
		}

		defined( 'LSCWP_LOG' ) && Log::debug( '[Object] connecting to ' . $this->_cfg_host . ':' . $this->_cfg_port ) ;

		$failed = false ;
		/**
		 * Connect to Redis
		 *
		 * @since  1.8.1
		 * @see https://github.com/phpredis/phpredis/#example-1
		 */
		if ( $this->_oc_driver == 'Redis' ) {
			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] Init ' . $this->_oc_driver . ' connection' ) ;

			set_error_handler( 'litespeed_exception_handler' ) ;
			try {
				$this->_conn = new \Redis() ;
				 // error_log( 'Object: _connect Redis' ) ;

				if ( $this->_cfg_persistent ) {
					if ( $this->_cfg_port ) {
						$this->_conn->pconnect( $this->_cfg_host, $this->_cfg_port ) ;
					}
					else {
						$this->_conn->pconnect( $this->_cfg_host ) ;
					}
				}
				else {
					if ( $this->_cfg_port ) {
						$this->_conn->connect( $this->_cfg_host, $this->_cfg_port ) ;
					}
					else {
						$this->_conn->connect( $this->_cfg_host ) ;
					}
				}

				if ( $this->_cfg_pswd ) {
					$this->_conn->auth( $this->_cfg_pswd ) ;
				}

				if ( $this->_cfg_db ) {
					$this->_conn->select( $this->_cfg_db ) ;
				}

				$res = $this->_conn->ping() ;

				if ( $res != '+PONG' ) {
					$failed = true ;
				}
			}
			catch ( \Exception $e ) {
				error_log( $e->getMessage() ) ;
				$failed = true ;
			}
			catch ( \ErrorException $e ) {
				error_log( $e->getMessage() ) ;
				$failed = true ;
			}
			restore_error_handler() ;

		}
		/**
		 * Connect to Memcached
		 */
		else {
			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] Init ' . $this->_oc_driver . ' connection' ) ;
			if ( $this->_cfg_persistent ) {
				$this->_conn = new \Memcached( $this->_get_mem_id() ) ;

				// Check memcached persistent connection
				if ( $this->_validate_mem_server() ) {
					// error_log( 'Object: _validate_mem_server' ) ;
					defined( 'LSCWP_LOG' ) && Log::debug( '[Object] Got persistent ' . $this->_oc_driver . ' connection' ) ;
					return true ;
				}

				defined( 'LSCWP_LOG' ) && Log::debug( '[Object] No persistent ' . $this->_oc_driver . ' server list!' ) ;
			}
			else {
				// error_log( 'Object: new memcached!' ) ;
				$this->_conn = new \Memcached ;
			}

			$this->_conn->addServer( $this->_cfg_host, (int) $this->_cfg_port ) ;

			/**
			 * Add SASL auth
			 * @since  1.8.1
			 * @since  2.9.6 Fixed SASL connection @see https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:lsmcd:new_sasl
			 */
			if ( $this->_cfg_user && $this->_cfg_pswd && method_exists( $this->_conn, 'setSaslAuthData' ) ) {
				$this->_conn->setOption( \Memcached::OPT_BINARY_PROTOCOL, true ) ;
				$this->_conn->setOption( \Memcached::OPT_COMPRESSION, false ) ;
				$this->_conn->setSaslAuthData( $this->_cfg_user, $this->_cfg_pswd ) ;
			}

			// Check connection
			if ( ! $this->_validate_mem_server() ) {
				$failed = true ;
			}
		}

		// If failed to connect
		if ( $failed ) {
			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] Failed to connect ' . $this->_oc_driver . ' server!' ) ;
			$this->_conn = null ;
			$this->_cfg_enabled = false ;
			// error_log( 'Object: false!' ) ;
			return false ;
		}

		defined( 'LSCWP_LOG' ) && Log::debug2( '[Object] Connected' ) ;

		return true ;
	}

	/**
	 * Check if the connected memcached host is the one in cfg
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _validate_mem_server()
	{
		$mem_list = $this->_conn->getStats() ;
		if ( empty( $mem_list ) ) {
			return false ;
		}

		foreach ( $mem_list as $k => $v ) {
			if ( substr( $k, 0, strlen( $this->_cfg_host ) ) != $this->_cfg_host ) {
				continue ;
			}
			if ( $v[ 'pid' ] > 0 ) {
				return true ;
			}
		}

		return false ;
	}

	/**
	 * Get memcached unique id to be used for connecting
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _get_mem_id()
	{
		$mem_id = 'litespeed' ;
		if ( is_multisite() ) {
			$mem_id .= '_' . get_current_blog_id() ;
		}

		return $mem_id ;
	}

	/**
	 * Get cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function get( $key )
	{
		if ( ! $this->_cfg_enabled ) {
			return null ;
		}

		if ( ! $this->_can_cache() ) {
			return null ;
		}

		if( ! $this->_connect() ) {
			return null ;
		}

		// defined( 'LSCWP_LOG' ) && Log::debug2( '[Object] get ' . $key ) ;

		$res = $this->_conn->get( $key ) ;

		return $res ;
	}

	/**
	 * Set cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function set( $key, $data, $expire )
	{
		if ( ! $this->_cfg_enabled ) {
			return null ;
		}

		if ( ! $this->_can_cache() ) {
			return null ;
		}

		if( ! $this->_connect() ) {
			return null ;
		}

		// defined( 'LSCWP_LOG' ) && Log::debug2( '[Object] set ' . $key ) ;

		// error_log( 'Object: set ' . $key ) ;

		$ttl = $expire ?: $this->_cfg_life ;

		if ( $this->_oc_driver == 'Redis' ) {
			$res = $this->_conn->setEx( $key, $ttl, $data ) ;
		}
		else {
			$res = $this->_conn->set( $key, $data, $ttl ) ;
		}

		return $res ;
	}

	/**
	 * Check if can cache or not
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _can_cache()
	{
		if ( ! $this->_cfg_admin && defined( 'WP_ADMIN' ) ) {
			return false ;
		}
		return true ;
	}

	/**
	 * Delete cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function delete( $key )
	{
		if ( ! $this->_cfg_enabled ) {
			return null ;
		}

		if( ! $this->_connect() ) {
			return null ;
		}

		// defined( 'LSCWP_LOG' ) && Log::debug2( '[Object] delete ' . $key ) ;

		if ( $this->_oc_driver == 'Redis' ) {
			$res = $this->_conn->del( $key ) ;
		}
		else {
			$res = $this->_conn->delete( $key ) ;
		}

		return $res ;
	}

	/**
	 * Clear all cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function flush()
	{
		if ( ! $this->_cfg_enabled ) {
			defined( 'LSCWP_LOG' ) && Log::debug( '[Object] bypass flushing' ) ;
			return null ;
		}

		if( ! $this->_connect() ) {
			return null ;
		}

		defined( 'LSCWP_LOG' ) && Log::debug( '[Object] flush!' ) ;

		if ( $this->_oc_driver == 'Redis' ) {
			$res = $this->_conn->flushDb() ;
		}
		else {
			$res = $this->_conn->flush() ;
			$this->_conn->resetServerList() ;
		}

		return $res ;
	}

	/**
	 * Add global groups
	 *
	 * @since 1.8
	 * @access public
	 */
	public function add_global_groups( $groups )
	{
		if ( ! is_array( $groups ) ) {
			$groups = array( $groups ) ;
		}

		$this->_global_groups = array_merge( $this->_global_groups, $groups ) ;
		$this->_global_groups = array_unique( $this->_global_groups ) ;
	}

	/**
	 * Check if is in global groups or not
	 *
	 * @since 1.8
	 * @access public
	 */
	public function is_global( $group )
	{
		return in_array( $group, $this->_global_groups ) ;
	}

	/**
	 * Add non persistent groups
	 *
	 * @since 1.8
	 * @access public
	 */
	public function add_non_persistent_groups( $groups )
	{
		if ( ! is_array( $groups ) ) {
			$groups = array( $groups ) ;
		}

		$this->_non_persistent_groups = array_merge( $this->_non_persistent_groups, $groups ) ;
		$this->_non_persistent_groups = array_unique( $this->_non_persistent_groups ) ;
	}

	/**
	 * Check if is in non persistent groups or not
	 *
	 * @since 1.8
	 * @access public
	 */
	public function is_non_persistent( $group )
	{
		return in_array( $group, $this->_non_persistent_groups ) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.8
	 * @access public
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}