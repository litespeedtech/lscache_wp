<?php
/**
 * The object cache class
 *
 * @since      	1.8
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;
defined('WPINC') || exit();

require_once dirname(__DIR__) . '/autoload.php';

class Object_Cache extends Root
{
	const O_OBJECT = 'object';
	const O_OBJECT_KIND = 'object-kind';
	const O_OBJECT_HOST = 'object-host';
	const O_OBJECT_PORT = 'object-port';
	const O_OBJECT_LIFE = 'object-life';
	const O_OBJECT_PERSISTENT = 'object-persistent';
	const O_OBJECT_ADMIN = 'object-admin';
	const O_OBJECT_TRANSIENTS = 'object-transients';
	const O_OBJECT_DB_ID = 'object-db_id';
	const O_OBJECT_USER = 'object-user';
	const O_OBJECT_PSWD = 'object-pswd';
	const O_OBJECT_GLOBAL_GROUPS = 'object-global_groups';
	const O_OBJECT_NON_PERSISTENT_GROUPS = 'object-non_persistent_groups';

	private $_conn;
	private $_cfg_enabled;
	private $_cfg_method;
	private $_cfg_host;
	private $_cfg_port;
	private $_cfg_life;
	private $_cfg_persistent;
	private $_cfg_admin;
	private $_cfg_transients;
	private $_cfg_db;
	private $_cfg_user;
	private $_cfg_pswd;
	private $_default_life = 360;

	private $_oc_driver = 'Memcached'; // Redis or Memcached

	private $_global_groups = array();
	private $_non_persistent_groups = array();

	/**
	 * Init
	 *
	 * NOTE: this class may be included without initialized  core
	 *
	 * @since  1.8
	 */
	public function __construct($cfg = false)
	{
		defined('LSCWP_LOG') && Debug2::debug2('[Object] init');

		if ($cfg) {
			if (!is_array($cfg[Base::O_OBJECT_GLOBAL_GROUPS])) {
				$cfg[Base::O_OBJECT_GLOBAL_GROUPS] = explode("\n", $cfg[Base::O_OBJECT_GLOBAL_GROUPS]);
			}
			if (!is_array($cfg[Base::O_OBJECT_NON_PERSISTENT_GROUPS])) {
				$cfg[Base::O_OBJECT_NON_PERSISTENT_GROUPS] = explode("\n", $cfg[Base::O_OBJECT_NON_PERSISTENT_GROUPS]);
			}
			$this->_cfg_method = $cfg[Base::O_OBJECT_KIND] ? true : false;
			$this->_cfg_host = $cfg[Base::O_OBJECT_HOST];
			$this->_cfg_port = $cfg[Base::O_OBJECT_PORT];
			$this->_cfg_life = $cfg[Base::O_OBJECT_LIFE];
			$this->_cfg_persistent = $cfg[Base::O_OBJECT_PERSISTENT];
			$this->_cfg_admin = $cfg[Base::O_OBJECT_ADMIN];
			$this->_cfg_transients = $cfg[Base::O_OBJECT_TRANSIENTS];
			$this->_cfg_db = $cfg[Base::O_OBJECT_DB_ID];
			$this->_cfg_user = $cfg[Base::O_OBJECT_USER];
			$this->_cfg_pswd = $cfg[Base::O_OBJECT_PSWD];
			$this->_global_groups = $cfg[Base::O_OBJECT_GLOBAL_GROUPS];
			$this->_non_persistent_groups = $cfg[Base::O_OBJECT_NON_PERSISTENT_GROUPS];

			if ($this->_cfg_method) {
				$this->_oc_driver = 'Redis';
			}
			$this->_cfg_enabled = $cfg[Base::O_OBJECT] && class_exists($this->_oc_driver) && $this->_cfg_host;

			defined('LSCWP_LOG') && Debug2::debug('[Object] init with cfg result : ', $this->_cfg_enabled);
		}
		// If OC is OFF, will hit here to init OC after conf initialized
		elseif (defined('LITESPEED_CONF_LOADED')) {
			$this->_cfg_method = $this->conf(Base::O_OBJECT_KIND) ? true : false;
			$this->_cfg_host = $this->conf(Base::O_OBJECT_HOST);
			$this->_cfg_port = $this->conf(Base::O_OBJECT_PORT);
			$this->_cfg_life = $this->conf(Base::O_OBJECT_LIFE);
			$this->_cfg_persistent = $this->conf(Base::O_OBJECT_PERSISTENT);
			$this->_cfg_admin = $this->conf(Base::O_OBJECT_ADMIN);
			$this->_cfg_transients = $this->conf(Base::O_OBJECT_TRANSIENTS);
			$this->_cfg_db = $this->conf(Base::O_OBJECT_DB_ID);
			$this->_cfg_user = $this->conf(Base::O_OBJECT_USER);
			$this->_cfg_pswd = $this->conf(Base::O_OBJECT_PSWD);
			$this->_global_groups = $this->conf(Base::O_OBJECT_GLOBAL_GROUPS);
			$this->_non_persistent_groups = $this->conf(Base::O_OBJECT_NON_PERSISTENT_GROUPS);

			if ($this->_cfg_method) {
				$this->_oc_driver = 'Redis';
			}
			$this->_cfg_enabled = $this->conf(Base::O_OBJECT) && class_exists($this->_oc_driver) && $this->_cfg_host;
		} elseif (defined('self::CONF_FILE') && file_exists(WP_CONTENT_DIR . '/' . self::CONF_FILE)) {
			// Get cfg from _data_file
			// Use self::const to avoid loading more classes
			$cfg = json_decode(file_get_contents(WP_CONTENT_DIR . '/' . self::CONF_FILE), true);
			if (!empty($cfg[self::O_OBJECT_HOST])) {
				$this->_cfg_method = !empty($cfg[self::O_OBJECT_KIND]) ? $cfg[self::O_OBJECT_KIND] : false;
				$this->_cfg_host = $cfg[self::O_OBJECT_HOST];
				$this->_cfg_port = $cfg[self::O_OBJECT_PORT];
				$this->_cfg_life = !empty($cfg[self::O_OBJECT_LIFE]) ? $cfg[self::O_OBJECT_LIFE] : $this->_default_life;
				$this->_cfg_persistent = !empty($cfg[self::O_OBJECT_PERSISTENT]) ? $cfg[self::O_OBJECT_PERSISTENT] : false;
				$this->_cfg_admin = !empty($cfg[self::O_OBJECT_ADMIN]) ? $cfg[self::O_OBJECT_ADMIN] : false;
				$this->_cfg_transients = !empty($cfg[self::O_OBJECT_TRANSIENTS]) ? $cfg[self::O_OBJECT_TRANSIENTS] : false;
				$this->_cfg_db = !empty($cfg[self::O_OBJECT_DB_ID]) ? $cfg[self::O_OBJECT_DB_ID] : 0;
				$this->_cfg_user = !empty($cfg[self::O_OBJECT_USER]) ? $cfg[self::O_OBJECT_USER] : '';
				$this->_cfg_pswd = !empty($cfg[self::O_OBJECT_PSWD]) ? $cfg[self::O_OBJECT_PSWD] : '';
				$this->_global_groups = !empty($cfg[self::O_OBJECT_GLOBAL_GROUPS]) ? $cfg[self::O_OBJECT_GLOBAL_GROUPS] : array();
				$this->_non_persistent_groups = !empty($cfg[self::O_OBJECT_NON_PERSISTENT_GROUPS]) ? $cfg[self::O_OBJECT_NON_PERSISTENT_GROUPS] : array();

				if ($this->_cfg_method) {
					$this->_oc_driver = 'Redis';
				}
				$this->_cfg_enabled = class_exists($this->_oc_driver) && $this->_cfg_host;
			} else {
				$this->_cfg_enabled = false;
			}
		} else {
			$this->_cfg_enabled = false;
		}
	}

	/**
	 * Get `Store Transients` setting value
	 *
	 * @since  1.8.3
	 * @access public
	 */
	public function store_transients($group)
	{
		return $this->_cfg_transients && $this->_is_transients_group($group);
	}

	/**
	 * Check if the group belongs to transients or not
	 *
	 * @since  1.8.3
	 * @access private
	 */
	private function _is_transients_group($group)
	{
		return in_array($group, array('transient', 'site-transient'));
	}

	/**
	 * Update WP object cache file config
	 *
	 * @since  1.8
	 * @access public
	 */
	public function update_file($options)
	{
		$changed = false;

		// NOTE: When included in oc.php, `LSCWP_DIR` will show undefined, so this must be assigned/generated when used
		$_oc_ori_file = LSCWP_DIR . 'lib/object-cache.php';
		$_oc_wp_file = WP_CONTENT_DIR . '/object-cache.php';

		// Update cls file
		if (!file_exists($_oc_wp_file) || md5_file($_oc_wp_file) !== md5_file($_oc_ori_file)) {
			defined('LSCWP_LOG') && Debug2::debug('[Object] copying object-cache.php file to ' . $_oc_wp_file);
			copy($_oc_ori_file, $_oc_wp_file);

			$changed = true;
		}

		/**
		 * Clear object cache
		 */
		if ($changed) {
			$this->_reconnect($options);
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
		$_oc_ori_file = LSCWP_DIR . 'lib/object-cache.php';
		$_oc_wp_file = WP_CONTENT_DIR . '/object-cache.php';

		if (file_exists($_oc_wp_file) && md5_file($_oc_wp_file) === md5_file($_oc_ori_file)) {
			defined('LSCWP_LOG') && Debug2::debug('[Object] removing ' . $_oc_wp_file);
			unlink($_oc_wp_file);
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
		return $this->_connect();
	}

	/**
	 * Force to connect with this setting
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _reconnect($cfg)
	{
		defined('LSCWP_LOG') && Debug2::debug('[Object] Reconnecting');
		// error_log( 'Object: reconnect !' );
		if (isset($this->_conn)) {
			// error_log( 'Object: Quiting existing connection!' );
			defined('LSCWP_LOG') && Debug2::debug('[Object] Quiting existing connection');
			$this->flush();
			$this->_conn = null;
			$this->cls(false, true);
		}

		$cls = $this->cls(false, false, $cfg);
		$cls->_connect();
		if (isset($cls->_conn)) {
			$cls->flush();
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
		if (isset($this->_conn)) {
			// error_log( 'Object: _connected' );
			return true;
		}

		if (!class_exists($this->_oc_driver) || !$this->_cfg_host) {
			return null;
		}

		if (defined('LITESPEED_OC_FAILURE')) {
			return false;
		}

		defined('LSCWP_LOG') && Debug2::debug('[Object] connecting to ' . $this->_cfg_host . ':' . $this->_cfg_port);

		$failed = false;
		/**
		 * Connect to Redis
		 *
		 * @since  1.8.1
		 * @see https://github.com/phpredis/phpredis/#example-1
		 */
		if ($this->_oc_driver == 'Redis') {
			defined('LSCWP_LOG') && Debug2::debug('[Object] Init ' . $this->_oc_driver . ' connection');

			set_error_handler('litespeed_exception_handler');
			try {
				$this->_conn = new \Redis();
				// error_log( 'Object: _connect Redis' );

				if ($this->_cfg_persistent) {
					if ($this->_cfg_port) {
						$this->_conn->pconnect($this->_cfg_host, $this->_cfg_port);
					} else {
						$this->_conn->pconnect($this->_cfg_host);
					}
				} else {
					if ($this->_cfg_port) {
						$this->_conn->connect($this->_cfg_host, $this->_cfg_port);
					} else {
						$this->_conn->connect($this->_cfg_host);
					}
				}

				if ($this->_cfg_pswd) {
					if ($this->_cfg_user) {
						$this->_conn->auth(array($this->_cfg_user, $this->_cfg_pswd));
					} else {
						$this->_conn->auth($this->_cfg_pswd);
					}
				}

				if ($this->_cfg_db) {
					$this->_conn->select($this->_cfg_db);
				}

				$res = $this->_conn->ping();

				if ($res != '+PONG') {
					$failed = true;
				}
			} catch (\Exception $e) {
				error_log($e->getMessage());
				$failed = true;
			} catch (\ErrorException $e) {
				error_log($e->getMessage());
				$failed = true;
			}
			restore_error_handler();
		} /**
		 * Connect to Memcached
		 */ else {
			defined('LSCWP_LOG') && Debug2::debug('[Object] Init ' . $this->_oc_driver . ' connection');
			if ($this->_cfg_persistent) {
				$this->_conn = new \Memcached($this->_get_mem_id());

				// Check memcached persistent connection
				if ($this->_validate_mem_server()) {
					// error_log( 'Object: _validate_mem_server' );
					defined('LSCWP_LOG') && Debug2::debug('[Object] Got persistent ' . $this->_oc_driver . ' connection');
					return true;
				}

				defined('LSCWP_LOG') && Debug2::debug('[Object] No persistent ' . $this->_oc_driver . ' server list!');
			} else {
				// error_log( 'Object: new memcached!' );
				$this->_conn = new \Memcached();
			}

			$this->_conn->addServer($this->_cfg_host, (int) $this->_cfg_port);

			/**
			 * Add SASL auth
			 * @since  1.8.1
			 * @since  2.9.6 Fixed SASL connection @see https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:lsmcd:new_sasl
			 */
			if ($this->_cfg_user && $this->_cfg_pswd && method_exists($this->_conn, 'setSaslAuthData')) {
				$this->_conn->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
				$this->_conn->setOption(\Memcached::OPT_COMPRESSION, false);
				$this->_conn->setSaslAuthData($this->_cfg_user, $this->_cfg_pswd);
			}

			// Check connection
			if (!$this->_validate_mem_server()) {
				$failed = true;
			}
		}

		// If failed to connect
		if ($failed) {
			defined('LSCWP_LOG') && Debug2::debug('[Object] Failed to connect ' . $this->_oc_driver . ' server!');
			$this->_conn = null;
			$this->_cfg_enabled = false;
			!defined('LITESPEED_OC_FAILURE') && define('LITESPEED_OC_FAILURE', true);
			// error_log( 'Object: false!' );
			return false;
		}

		defined('LSCWP_LOG') && Debug2::debug2('[Object] Connected');

		return true;
	}

	/**
	 * Check if the connected memcached host is the one in cfg
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _validate_mem_server()
	{
		$mem_list = $this->_conn->getStats();
		if (empty($mem_list)) {
			return false;
		}

		foreach ($mem_list as $k => $v) {
			if (substr($k, 0, strlen($this->_cfg_host)) != $this->_cfg_host) {
				continue;
			}
			if (!empty($v['pid']) || !empty($v['curr_connections'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get memcached unique id to be used for connecting
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _get_mem_id()
	{
		$mem_id = 'litespeed';
		if (is_multisite()) {
			$mem_id .= '_' . get_current_blog_id();
		}

		return $mem_id;
	}

	/**
	 * Get cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function get($key)
	{
		if (!$this->_cfg_enabled) {
			return null;
		}

		if (!$this->_can_cache()) {
			return null;
		}

		if (!$this->_connect()) {
			return null;
		}

		// defined( 'LSCWP_LOG' ) && Debug2::debug2( '[Object] get ' . $key );

		$res = $this->_conn->get($key);

		return $res;
	}

	/**
	 * Set cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function set($key, $data, $expire)
	{
		if (!$this->_cfg_enabled) {
			return null;
		}

		/**
		 * To fix the Cloud callback cached as its frontend call but the hash is generated in backend
		 * Bug found by Stan at Jan/10/2020
		 */
		// if ( ! $this->_can_cache() ) {
		// 	return null;
		// }

		if (!$this->_connect()) {
			return null;
		}

		// defined( 'LSCWP_LOG' ) && Debug2::debug2( '[Object] set ' . $key );

		// error_log( 'Object: set ' . $key );

		$ttl = $expire ?: $this->_cfg_life;

		if ($this->_oc_driver == 'Redis') {
			try {
				$res = $this->_conn->setEx($key, $ttl, $data);
			} catch (\RedisException $ex) {
				$res = false;
				$msg = sprintf(__('Redis encountered a fatal error: %s (code: %d)', 'litespeed-cache'), $ex->getMessage(), $ex->getCode());
				Debug2::debug('[Object] ' . $msg);
				Admin_Display::error($msg);
			}
		} else {
			$res = $this->_conn->set($key, $data, $ttl);
		}

		return $res;
	}

	/**
	 * Check if can cache or not
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _can_cache()
	{
		if (!$this->_cfg_admin && defined('WP_ADMIN')) {
			return false;
		}
		return true;
	}

	/**
	 * Delete cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function delete($key)
	{
		if (!$this->_cfg_enabled) {
			return null;
		}

		if (!$this->_connect()) {
			return null;
		}

		// defined( 'LSCWP_LOG' ) && Debug2::debug2( '[Object] delete ' . $key );

		if ($this->_oc_driver == 'Redis') {
			$res = $this->_conn->del($key);
		} else {
			$res = $this->_conn->delete($key);
		}

		return (bool) $res;
	}

	/**
	 * Clear all cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function flush()
	{
		if (!$this->_cfg_enabled) {
			defined('LSCWP_LOG') && Debug2::debug('[Object] bypass flushing');
			return null;
		}

		if (!$this->_connect()) {
			return null;
		}

		defined('LSCWP_LOG') && Debug2::debug('[Object] flush!');

		if ($this->_oc_driver == 'Redis') {
			$res = $this->_conn->flushDb();
		} else {
			$res = $this->_conn->flush();
			$this->_conn->resetServerList();
		}

		return $res;
	}

	/**
	 * Add global groups
	 *
	 * @since 1.8
	 * @access public
	 */
	public function add_global_groups($groups)
	{
		if (!is_array($groups)) {
			$groups = array($groups);
		}

		$this->_global_groups = array_merge($this->_global_groups, $groups);
		$this->_global_groups = array_unique($this->_global_groups);
	}

	/**
	 * Check if is in global groups or not
	 *
	 * @since 1.8
	 * @access public
	 */
	public function is_global($group)
	{
		return in_array($group, $this->_global_groups);
	}

	/**
	 * Add non persistent groups
	 *
	 * @since 1.8
	 * @access public
	 */
	public function add_non_persistent_groups($groups)
	{
		if (!is_array($groups)) {
			$groups = array($groups);
		}

		$this->_non_persistent_groups = array_merge($this->_non_persistent_groups, $groups);
		$this->_non_persistent_groups = array_unique($this->_non_persistent_groups);
	}

	/**
	 * Check if is in non persistent groups or not
	 *
	 * @since 1.8
	 * @access public
	 */
	public function is_non_persistent($group)
	{
		return in_array($group, $this->_non_persistent_groups);
	}
}
