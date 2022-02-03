<?php
/**
 * The abstract instance
 *
 * @since      	3.0
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

abstract class Root {
	const CONF_FILE = '.litespeed_conf.dat';
	// Instance set
	private static $_instances;

	private static $_options = array();
	private static $_const_options = array();
	private static $_primary_options = array();
	private static $_network_options = array();

	/**
	 * Log a debug message.
	 *
	 * @since  4.4
	 * @access public
	 */
	public static function debug( $msg, $backtrace_limit = false ) {
		if ( ! defined( 'LSCWP_LOG' ) ) {
			return;
		}

		if ( defined( 'static::LOG_TAG' )) {
			$msg = static::LOG_TAG . '  ' . $msg;
		}

		Debug2::debug( $msg, $backtrace_limit );
	}

	/**
	 * Log an advanced debug message.
	 *
	 * @since  4.4
	 * @access public
	 */
	public static function debug2( $msg, $backtrace_limit = false ) {
		if ( ! defined( 'LSCWP_LOG_MORE' ) ) {
			return;
		}

		if ( defined( 'static::LOG_TAG' )) {
			$msg = static::LOG_TAG . '  ' . $msg;
		}
		Debug2::debug2( $msg, $backtrace_limit );
	}

	/**
	 * Check if there is cache folder for that type
	 *
	 * @since  3.0
	 */
	public function has_cache_folder( $type ) {
		$subsite_id = is_multisite() && ! is_network_admin() ? get_current_blog_id() : '';

		if ( file_exists( LITESPEED_STATIC_DIR . '/' . $type . '/' . $subsite_id ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Maybe make the cache folder if not existed
	 *
	 * @since 4.4.2
	 */
	protected function _maybe_mk_cache_folder( $type ) {
		if ( ! $this->has_cache_folder( $type ) ) {
			$subsite_id = is_multisite() && ! is_network_admin() ? get_current_blog_id() : '';
			$path = LITESPEED_STATIC_DIR . '/' . $type . '/' . $subsite_id;
			mkdir( $path, 0755, true );
		}
	}

	/**
	 * Delete file-based cache folder for that type
	 *
	 * @since  3.0
	 */
	public function rm_cache_folder( $type ) {
		if ( ! $this->has_cache_folder( $type ) ) {
			return;
		}

		$subsite_id = is_multisite() && ! is_network_admin() ? get_current_blog_id() : '';

		File::rrmdir( LITESPEED_STATIC_DIR . '/' . $type . '/' . $subsite_id );

		// Clear All summary data
		$this->_summary = array();
		self::save_summary();

		if ( $type == 'ccss' || $type == 'ucss') {
			Debug2::debug( '[CSS] Cleared ' . $type .  ' queue' );
		}
		elseif ( $type == 'avatar' ) {
			Debug2::debug( '[Avatar] Cleared ' . $type .  ' queue' );
		}
		elseif ( $type == 'css' || $type == 'js' ) {
			return;
		}
		else {
			Debug2::debug( '[' . strtoupper( $type ) . '] Cleared ' . $type .  ' queue' );
		}
	}

	/**
	 * Build the static filepath
	 *
	 * @since  4.0
	 */
	protected function _build_filepath_prefix( $type ) {
		$filepath_prefix = '/' . $type . '/';
		if ( is_multisite() ) {
			$filepath_prefix .= get_current_blog_id() . '/';
		}

		return $filepath_prefix;
	}

	/**
	 * Load current queues from data file
	 *
	 * @since  4.1
	 * @since  4.3 Elevated to root.cls
	 */
	public function load_queue( $type ) {
		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_path = LITESPEED_STATIC_DIR . $filepath_prefix . '.litespeed_conf.dat';

		$queue = array();
		if ( file_exists( $static_path ) ) {
			$queue = json_decode( file_get_contents( $static_path ), true ) ?: array();
		}

		return $queue;
	}

	/**
	 * Save current queues to data file
	 *
	 * @since  4.1
	 * @since  4.3 Elevated to root.cls
	 */
	public function save_queue( $type, $list ) {
		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_path = LITESPEED_STATIC_DIR . $filepath_prefix . '.litespeed_conf.dat';

		$data = json_encode( $list );

		File::save( $static_path, $data, true );
	}

	/**
	 * Clear all waiting queues
	 *
	 * @since  3.4
	 * @since  4.3 Elevated to root.cls
	 */
	public function clear_q( $type ) {
		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_path = LITESPEED_STATIC_DIR . $filepath_prefix . '.litespeed_conf.dat';

		if ( file_exists( $static_path ) ) {
			unlink( $static_path );
		}

		$msg = __( 'Queue cleared successfully.', 'litespeed-cache' );
		Admin_Display::succeed( $msg );
	}

	/**
	 * Load an instance or create it if not existed
	 * @since  4.0
	 */
	public static function cls( $cls = false, $unset = false, $data = false ) {
		if ( ! $cls ) {
			$cls = self::ori_cls();
		}
		$cls = __NAMESPACE__ . '\\' . $cls;

		$cls_tag = strtolower( $cls );

		if ( ! isset( self::$_instances[ $cls_tag ] ) ) {
			if ( $unset ) {
				return;
			}

			self::$_instances[ $cls_tag ] = new $cls( $data );
		}
		else {
			if ( $unset ) {
				unset( self::$_instances[ $cls_tag ] );
				return;
			}
		}

		return self::$_instances[ $cls_tag ];
	}

	/**
	 * Set one conf or confs
	 */
	public function set_conf( $id, $val = null ) {
		if ( is_array( $id ) ) {
			foreach ( $id as $k => $v ) {
				$this->set_conf( $k, $v );
			}
			return;
		}
		self::$_options[ $id ] = $val;
	}

	/**
	 * Set one primary conf or confs
	 */
	public function set_primary_conf( $id, $val = null ) {
		if ( is_array( $id ) ) {
			foreach ( $id as $k => $v ) {
				$this->set_primary_conf( $k, $v );
			}
			return;
		}
		self::$_primary_options[ $id ] = $val;
	}

	/**
	 * Set one network conf
	 */
	public function set_network_conf( $id, $val = null ) {
		if ( is_array( $id ) ) {
			foreach ( $id as $k => $v ) {
				$this->set_network_conf( $k, $v );
			}
			return;
		}
		self::$_network_options[ $id ] = $val;
	}

	/**
	 * Set one const conf
	 */
	public function set_const_conf( $id, $val ) {
		self::$_const_options[ $id ] = $val;
	}

	/**
	 * Check if is overwritten by const
	 *
	 * @since  3.0
	 */
	public function const_overwritten( $id ) {
		if ( ! isset( self::$_const_options[ $id ] ) || self::$_const_options[ $id ] == self::$_options[ $id ] ) {
			return null;
		}
		return self::$_const_options[ $id ];
	}

	/**
	 * Check if is overwritten by primary site
	 *
	 * @since  3.2.2
	 */
	public function primary_overwritten( $id ) {
		if ( ! isset( self::$_primary_options[ $id ] ) || self::$_primary_options[ $id ] == self::$_options[ $id ] ) {
			return null;
		}

		// Network admin settings is impossible to be overwritten by primary
		if ( is_network_admin() ) {
			return null;
		}

		return self::$_primary_options[ $id ];
	}

	/**
	 * Get the list of configured options for the blog.
	 *
	 * @since 1.0
	 */
	public function get_options( $ori = false ) {
		if ( ! $ori ) {
			return array_merge( self::$_options, self::$_primary_options, self::$_const_options );
		}

		return self::$_options;
	}

	/**
	 * If has a conf or not
	 */
	public function has_conf( $id ) {
		return array_key_exists( $id, self::$_options );
	}

	/**
	 * If has a primary conf or not
	 */
	public function has_primary_conf( $id ) {
		return array_key_exists( $id, self::$_primary_options );
	}

	/**
	 * If has a network conf or not
	 */
	public function has_network_conf( $id ) {
		return array_key_exists( $id, self::$_network_options );
	}

	/**
	 * Get conf
	 */
	public function conf( $id, $ori = false ) {
		if ( isset( self::$_options[ $id ] ) ) {
			if ( ! $ori ) {
				$val = $this->const_overwritten( $id );
				if ( $val !== null ) {
					defined( 'LSCWP_LOG' ) && Debug2::debug( '[Conf] 🏛️ const option ' . $id . '=' . var_export( $val, true ) );
					return $val;
				}

				$val = $this->primary_overwritten( $id ); // Network Use primary site settings
				if ( $val !== null ) {
					return $val;
				}
			}

			// Network orignal value will be in _network_options
			if ( ! is_network_admin() || ! $this->has_network_conf( $id ) ) {
				return self::$_options[ $id ];
			}

		}

		if ( $this->has_network_conf( $id ) ) {
			if ( ! $ori ) {
				$val = $this->const_overwritten( $id );
				if ( $val !== null ) {
					defined( 'LSCWP_LOG' ) && Debug2::debug( '[Conf] 🏛️ const option ' . $id . '=' . var_export( $val, true ) );
					return $val;
				}
			}

			return $this->network_conf( $id );
		}

		defined( 'LSCWP_LOG' ) && Debug2::debug( '[Conf] Invalid option ID ' . $id );

		return null;
	}

	/**
	 * Get primary conf
	 */
	public function primary_conf( $id ) {
		return self::$_primary_options[ $id ];
	}

	/**
	 * Get network conf
	 */
	public function network_conf( $id ) {
		if ( ! $this->has_network_conf( $id ) ) {
			return null;
		}

		return self::$_network_options[ $id ];
	}

	/**
	 * Get called class short name
	 */
	public static function ori_cls() {
		$cls = new \ReflectionClass( get_called_class() );
		$shortname = $cls->getShortName();
		$namespace = str_replace( __NAMESPACE__ . '\\', '', $cls->getNamespaceName() . '\\' );
		if ( $namespace ) { // the left namespace after dropped LiteSpeed
			$shortname = $namespace . $shortname;
		}

		return $shortname;
	}

	/**
	 * Generate conf name for wp_options record
	 *
	 * @since 3.0
	 */
	public static function name( $id ) {
		$name = strtolower( self::ori_cls() );
		if ( $name == 'conf2' ) { // For a certain 3.7rc correction, can be dropped after v4
			$name = 'conf';
		}
		return 'litespeed.' . $name . '.' . $id;
	}

	/**
	 * Dropin with prefix for WP's get_option
	 *
	 * @since 3.0
	 */
	public static function get_option( $id, $default_v = false ) {
		$v = get_option( self::name( $id ), $default_v );

		// Maybe decode array
		if ( is_array( $default_v ) ) {
			$v = self::_maybe_decode( $v );
		}

		return $v;
	}

	/**
	 * Dropin with prefix for WP's get_site_option
	 *
	 * @since 3.0
	 */
	public static function get_site_option( $id, $default_v = false ) {
		$v = get_site_option( self::name( $id ), $default_v );

		// Maybe decode array
		if ( is_array( $default_v ) ) {
			$v = self::_maybe_decode( $v );
		}

		return $v;
	}

	/**
	 * Dropin with prefix for WP's get_blog_option
	 *
	 * @since 3.0
	 */
	public static function get_blog_option( $blog_id, $id, $default_v = false ) {
		$v = get_blog_option( $blog_id, self::name( $id ), $default_v );

		// Maybe decode array
		if ( is_array( $default_v ) ) {
			$v = self::_maybe_decode( $v );
		}

		return $v;
	}

	/**
	 * Dropin with prefix for WP's add_option
	 *
	 * @since 3.0
	 */
	public static function add_option( $id, $v ) {
		add_option( self::name( $id ), self::_maybe_encode( $v ) );
	}

	/**
	 * Dropin with prefix for WP's add_site_option
	 *
	 * @since 3.0
	 */
	public static function add_site_option( $id, $v ) {
		add_site_option( self::name( $id ), self::_maybe_encode( $v ) );
	}

	/**
	 * Dropin with prefix for WP's update_option
	 *
	 * @since 3.0
	 */
	public static function update_option( $id, $v ) {
		update_option( self::name( $id ), self::_maybe_encode( $v ) );
	}

	/**
	 * Dropin with prefix for WP's update_site_option
	 *
	 * @since 3.0
	 */
	public static function update_site_option( $id, $v ) {
		update_site_option( self::name( $id ), self::_maybe_encode( $v ) );
	}

	/**
	 * Decode an array
	 *
	 * @since  4.0
	 */
	private static function _maybe_decode( $v ) {
		if ( ! is_array( $v ) ) {
			$v2 = json_decode( $v, true );
			if ( $v2 !== null ) {
				$v = $v2;
			}
		}
		return $v;
	}

	/**
	 * Encode an array
	 *
	 * @since  4.0
	 */
	private static function _maybe_encode( $v ) {
		if ( is_array( $v ) ) {
			$v = json_encode( $v ) ?: $v; // Non utf-8 encoded value will get failed, then used ori value
		}
		return $v;
	}

	/**
	 * Dropin with prefix for WP's delete_option
	 *
	 * @since 3.0
	 */
	public static function delete_option( $id ) {
		delete_option( self::name( $id ) );
	}

	/**
	 * Dropin with prefix for WP's delete_site_option
	 *
	 * @since 3.0
	 */
	public static function delete_site_option( $id ) {
		delete_site_option( self::name( $id ) );
	}

	/**
	 * Read summary
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function get_summary( $field = false ) {
		$summary = self::get_option( '_summary', array() );

		if ( ! is_array( $summary ) ) {
			$summary = array();
		}

		if ( ! $field ) {
			return $summary;
		}

		if ( array_key_exists( $field, $summary ) ) {
			return $summary[ $field ];
		}

		return null;
	}

	/**
	 * Save summary
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function save_summary( $data = null ) {
		if ( $data === null ) {
			$data = static::cls()->_summary;
		}

		self::update_option( '_summary', $data );
	}

	/**
	 * Get the current instance object. To be inherited.
	 *
	 * @since 3.0
	 */
	public static function get_instance() {
		return static::cls();
	}

}