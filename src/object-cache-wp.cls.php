<?php
/**
 * WP Object Cache wrapper for LiteSpeed Cache.
 *
 * Provides a drop-in-compatible object cache implementation that proxies to
 * LiteSpeed's persistent cache while keeping a local runtime cache.
 *
 * @package LiteSpeed
 * @since   1.8
 */

/**
 * Class WP_Object_Cache
 *
 * Implements the WordPress object cache for LiteSpeed Cache.
 *
 * @since 1.8
 * @package LiteSpeed
 */
class WP_Object_Cache {

	/**
	 * Singleton instance
	 *
	 * @since 1.8
	 * @access protected
	 * @var WP_Object_Cache|null
	 */
	protected static $_instance;

	/**
	 * Object cache instance
	 *
	 * @since 1.8
	 * @access private
	 * @var \LiteSpeed\Object_Cache
	 */
	private $_object_cache;

	/**
	 * Cache storage
	 *
	 * @since 1.8
	 * @access private
	 * @var array
	 */
	private $_cache = array();

	/**
	 * Cache for 404 keys
	 *
	 * @since 1.8
	 * @access private
	 * @var array
	 */
	private $_cache_404 = array();

	/**
	 * Total cache operations
	 *
	 * @since 1.8
	 * @access private
	 * @var int
	 */
	private $cache_total = 0;

	/**
	 * Cache hits within call
	 *
	 * @since 1.8
	 * @access private
	 * @var int
	 */
	private $count_hit_incall = 0;

	/**
	 * Cache hits
	 *
	 * @since 1.8
	 * @access private
	 * @var int
	 */
	private $count_hit = 0;

	/**
	 * Cache misses within call
	 *
	 * @since 1.8
	 * @access private
	 * @var int
	 */
	private $count_miss_incall = 0;

	/**
	 * Cache misses
	 *
	 * @since 1.8
	 * @access private
	 * @var int
	 */
	private $count_miss = 0;

	/**
	 * Cache set operations
	 *
	 * @since 1.8
	 * @access private
	 * @var int
	 */
	private $count_set = 0;

	/**
	 * Global cache groups
	 *
	 * @since 1.8
	 * @access protected
	 * @var array
	 */
	protected $global_groups = array();

	/**
	 * Blog prefix for cache keys
	 *
	 * @since 1.8
	 * @access private
	 * @var string
	 */
	private $blog_prefix;

	/**
	 * Multisite flag
	 *
	 * @since 1.8
	 * @access private
	 * @var bool
	 */
	private $multisite;

	/**
	 * Init.
	 *
	 * Initializes the object cache with LiteSpeed settings.
	 *
	 * @since  1.8
	 * @access public
	 */
	public function __construct() {
		$this->_object_cache = \LiteSpeed\Object_Cache::cls();

		$this->multisite   = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';

		/**
		 * Fix multiple instance using same oc issue
		 *
		 * @since  1.8.2
		 */
		if ( ! defined( 'LSOC_PREFIX' ) ) {
			define( 'LSOC_PREFIX', substr( md5( __FILE__ ), -5 ) );
		}
	}

	/**
	 * Makes private properties readable for backward compatibility.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param string $name Property to get.
	 * @return mixed Property.
	 */
	public function __get( $name ) {
		return $this->$name;
	}

	/**
	 * Makes private properties settable for backward compatibility.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param string $name  Property to set.
	 * @param mixed  $value Property value.
	 * @return mixed Newly-set property.
	 */
	public function __set( $name, $value ) {
		$this->$name = $value;

		return $this->$name;
	}

	/**
	 * Makes private properties checkable for backward compatibility.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param string $name Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Makes private properties un-settable for backward compatibility.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param string $name Property to unset.
	 */
	public function __unset( $name ) {
		unset( $this->$name );
	}

	/**
	 * Serves as a utility function to determine whether a key is valid.
	 *
	 * @since 5.4
	 * @access protected
	 *
	 * @param int|string $key Cache key to check for validity.
	 * @return bool Whether the key is valid.
	 */
	protected function is_valid_key( $key ) {
		if ( is_int( $key ) ) {
			return true;
		}

		if ( is_string( $key ) && '' !== trim( $key ) ) {
			return true;
		}

		$type = gettype( $key );

		if ( ! function_exists( '__' ) ) {
			wp_load_translations_early();
		}

		$message = is_string( $key )
			? __( 'Cache key must not be an empty string.' )
			: sprintf(
				/* translators: %s: The type of the given cache key. */
				__( 'Cache key must be integer or non-empty string, %s given.' ),
				$type
			);

		_doing_it_wrong(
			esc_html( __METHOD__ ),
			esc_html( $message ),
			'6.1.0'
		);

		return false;
	}

	/**
	 * Get the final key.
	 *
	 * Generates a unique cache key based on group and prefix.
	 *
	 * @since 1.8
	 * @access private
	 * @param int|string $key   Cache key.
	 * @param string     $group Optional. Cache group. Default 'default'.
	 * @return string The final cache key.
	 */
	private function _key( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$prefix = $this->_object_cache->is_global( $group ) ? '' : $this->blog_prefix;

		return LSOC_PREFIX . $prefix . $group . '.' . $key;
	}

	/**
	 * Output debug info.
	 *
	 * Returns cache statistics for debugging purposes.
	 *
	 * @since  1.8
	 * @access public
	 * @return string Cache statistics.
	 */
	public function debug() {
		return ' [total] ' .
			$this->cache_total .
			' [hit_incall] ' .
			$this->count_hit_incall .
			' [hit] ' .
			$this->count_hit .
			' [miss_incall] ' .
			$this->count_miss_incall .
			' [miss] ' .
			$this->count_miss .
			' [set] ' .
			$this->count_set;
	}

	/**
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @since 1.8
	 * @access public
	 * @see WP_Object_Cache::set()
	 *
	 * @param int|string $key    What to call the contents in the cache.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True on success, false if cache key and group already exist.
	 */
	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( wp_suspend_cache_addition() ) {
			return false;
		}

		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$id = $this->_key( $key, $group );

		if ( array_key_exists( $id, $this->_cache ) ) {
			return false;
		}

		return $this->set( $key, $data, $group, (int) $expire );
	}

	/**
	 * Adds multiple values to the cache in one call.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param array  $data   Array of keys and values to be added.
	 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
	 * @param int    $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if cache key and group already exist.
	 */
	public function add_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();

		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->add( $key, $value, $group, $expire );
		}

		return $values;
	}

	/**
	 * Replaces the contents in the cache, if contents already exist.
	 *
	 * @since 1.8
	 * @access public
	 * @see WP_Object_Cache::set()
	 *
	 * @param int|string $key    What to call the contents in the cache.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True if contents were replaced, false if original value does not exist.
	 */
	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$id = $this->_key( $key, $group );

		if ( ! array_key_exists( $id, $this->_cache ) ) {
			return false;
		}

		return $this->set( $key, $data, $group, (int) $expire );
	}

	/**
	 * Sets the data contents into the cache.
	 *
	 * The cache contents are grouped by the $group parameter followed by the
	 * $key. This allows for duplicate IDs in unique groups. Therefore, naming of
	 * the group should be used with care and should follow normal function
	 * naming guidelines outside of core WordPress usage.
	 *
	 * The $expire parameter is not used, because the cache will automatically
	 * expire for each time a page is accessed and PHP finishes. The method is
	 * more for cache plugins which use files.
	 *
	 * @since 1.8
	 * @since 5.4 Returns false if cache key is invalid.
	 * @access public
	 *
	 * @param int|string $key    What to call the contents in the cache.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True if contents were set, false if key is invalid.
	 */
	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$id = $this->_key( $key, $group );

		if ( is_object( $data ) ) {
			$data = clone $data;
		}

		$this->_cache[ $id ] = $data;

		if ( array_key_exists( $id, $this->_cache_404 ) ) {
			unset( $this->_cache_404[ $id ] );
		}

		if ( ! $this->_object_cache->is_non_persistent( $group ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			$this->_object_cache->set( $id, serialize( array( 'data' => $data ) ), (int) $expire );
			++$this->count_set;
		}

		if ( $this->_object_cache->store_transients( $group ) ) {
			$this->_transient_set( $key, $data, $group, (int) $expire );
		}

		return true;
	}

	/**
	 * Sets multiple values to the cache in one call.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param array  $data   Array of key and value to be set.
	 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
	 * @param int    $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is always true.
	 */
	public function set_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();

		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->set( $key, $value, $group, $expire );
		}

		return $values;
	}

	/**
	 * Retrieves the cache contents, if it exists.
	 *
	 * The contents will be first attempted to be retrieved by searching by the
	 * key in the cache group. If the cache is hit (success) then the contents
	 * are returned.
	 *
	 * On failure, the number of cache misses will be incremented.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param int|string $key   The key under which the cache contents are stored.
	 * @param string     $group Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool       $force Optional. Unused. Whether to force an update of the local cache
	 *                          from the persistent cache. Default false.
	 * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
	 *                          Disambiguates a return of false, a storable value. Default null.
	 * @return mixed|false The cache contents on success, false on failure to retrieve contents.
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$id = $this->_key( $key, $group );

		$found       = false;
		$found_in_oc = false;
		$cache_val   = false;

		if ( array_key_exists( $id, $this->_cache ) && ! $force ) {
			$found     = true;
			$cache_val = $this->_cache[ $id ];
			++$this->count_hit_incall;
		} elseif ( ! array_key_exists( $id, $this->_cache_404 ) && ! $this->_object_cache->is_non_persistent( $group ) ) {
			$v = $this->_object_cache->get( $id );

			if ( null !== $v ) {
				$v = maybe_unserialize( $v );
			}

			// To be compatible with false val.
			if ( is_array( $v ) && array_key_exists( 'data', $v ) ) {
				++$this->count_hit;
				$found       = true;
				$found_in_oc = true;
				$cache_val   = $v['data'];
			} else {
				// Can't find key, cache it to 404.
				$this->_cache_404[ $id ] = 1;
				++$this->count_miss;
			}
		} else {
			++$this->count_miss_incall;
		}

		if ( is_object( $cache_val ) ) {
			$cache_val = clone $cache_val;
		}

		// If not found but has `Store Transients` cfg on, still need to follow WP's get_transient() logic.
		if ( ! $found && $this->_object_cache->store_transients( $group ) ) {
			$cache_val = $this->_transient_get( $key, $group );
			if ( $cache_val ) {
				$found = true;
			}
		}

		if ( $found_in_oc ) {
			$this->_cache[ $id ] = $cache_val;
		}

		++$this->cache_total;

		return $cache_val;
	}

	/**
	 * Retrieves multiple values from the cache in one call.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param array  $keys  Array of keys under which the cache contents are stored.
	 * @param string $group Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool   $force Optional. Whether to force an update of the local cache
	 *                      from the persistent cache. Default false.
	 * @return array Array of return values, grouped by key. Each value is either
	 *               the cache contents on success, or false on failure.
	 */
	public function get_multiple( $keys, $group = 'default', $force = false ) {
		$values = array();

		foreach ( $keys as $key ) {
			$values[ $key ] = $this->get( $key, $group, $force );
		}

		return $values;
	}

	/**
	 * Removes the contents of the cache key in the group.
	 *
	 * If the cache key does not exist in the group, then nothing will happen.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param int|string $key   What the contents in the cache are called.
	 * @param string     $group Optional. Where the cache contents are grouped. Default 'default'.
	 * @return bool True on success, false if the contents were not deleted.
	 */
	public function delete( $key, $group = 'default' ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$id = $this->_key( $key, $group );

		if ( $this->_object_cache->store_transients( $group ) ) {
			$this->_transient_del( $key, $group );
		}

		if ( array_key_exists( $id, $this->_cache ) ) {
			unset( $this->_cache[ $id ] );
		}

		if ( $this->_object_cache->is_non_persistent( $group ) ) {
			return false;
		}

		return $this->_object_cache->delete( $id );
	}

	/**
	 * Deletes multiple values from the cache in one call.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param array  $keys  Array of keys to be deleted.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if the contents were not deleted.
	 */
	public function delete_multiple( array $keys, $group = '' ) {
		$values = array();

		foreach ( $keys as $key ) {
			$values[ $key ] = $this->delete( $key, $group );
		}

		return $values;
	}

	/**
	 * Increments numeric cache item's value.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param int|string $key    The cache key to increment.
	 * @param int        $offset Optional. The amount by which to increment the item's value.
	 *                           Default 1.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @return int|false The item's new value on success, false on failure.
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		return $this->incr_desr( $key, $offset, $group, true );
	}

	/**
	 * Decrements numeric cache item's value.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param int|string $key    The cache key to decrement.
	 * @param int        $offset Optional. The amount by which to decrement the item's value.
	 *                           Default 1.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @return int|false The item's new value on success, false on failure.
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		return $this->incr_desr( $key, $offset, $group, false );
	}

	/**
	 * Increments or decrements numeric cache item's value.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param int|string $key    The cache key to increment or decrement.
	 * @param int        $offset The amount by which to adjust the item's value.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @param bool       $incr   True to increment, false to decrement.
	 * @return int|false The item's new value on success, false on failure.
	 */
	public function incr_desr( $key, $offset = 1, $group = 'default', $incr = true ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$cache_val = $this->get( $key, $group );

		if ( false === $cache_val ) {
			return false;
		}

		if ( ! is_numeric( $cache_val ) ) {
			$cache_val = 0;
		}

		$offset = (int) $offset;

		if ( $incr ) {
			$cache_val += $offset;
		} else {
			$cache_val -= $offset;
		}

		if ( $cache_val < 0 ) {
			$cache_val = 0;
		}

		$this->set( $key, $cache_val, $group );

		return $cache_val;
	}

	/**
	 * Clears the object cache of all data.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @return true Always returns true.
	 */
	public function flush() {
		$this->flush_runtime();

		$this->_object_cache->flush();

		return true;
	}

	/**
	 * Removes all cache items from the in-memory runtime cache.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @return true Always returns true.
	 */
	public function flush_runtime() {
		$this->_cache     = array();
		$this->_cache_404 = array();

		return true;
	}

	/**
	 * Removes all cache items in a group.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @return true Always returns true.
	 */
	public function flush_group() {
		return true;
	}

	/**
	 * Sets the list of global cache groups.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param string|string[] $groups List of groups that are global.
	 */
	public function add_global_groups( $groups ) {
		$groups = (array) $groups;

		$this->_object_cache->add_global_groups( $groups );
	}

	/**
	 * Sets the list of non-persistent cache groups.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param string|string[] $groups A group or an array of groups to add.
	 */
	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;

		$this->_object_cache->add_non_persistent_groups( $groups );
	}

	/**
	 * Switches the internal blog ID.
	 *
	 * This changes the blog ID used to create keys in blog specific groups.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function switch_to_blog( $blog_id ) {
		$blog_id           = (int) $blog_id;
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
	}

	/**
	 * Get transient from wp table
	 *
	 * Retrieves transient data from WordPress options table.
	 *
	 * @since 1.8.3
	 * @access private
	 * @see `wp-includes/option.php` function `get_transient`/`set_site_transient`
	 *
	 * @param string $transient Transient name.
	 * @param string $group     Transient group ('transient' or 'site-transient').
	 * @return mixed Transient value or false if not found.
	 */
	private function _transient_get( $transient, $group ) {
		if ( 'transient' === $group ) {
			/**** Ori WP func start */
			$transient_option = '_transient_' . $transient;
			if ( ! wp_installing() ) {
				// If option is not in alloptions, it is not autoloaded and thus has a timeout
				$alloptions = wp_load_alloptions();
				if ( ! isset( $alloptions[ $transient_option ] ) ) {
					$transient_timeout = '_transient_timeout_' . $transient;
					$timeout           = get_option( $transient_timeout );
					if ( false !== $timeout && $timeout < time() ) {
						delete_option( $transient_option );
						delete_option( $transient_timeout );
						$value = false;
					}
				}
			}

			if ( ! isset( $value ) ) {
				$value = get_option( $transient_option );
			}
			/**** Ori WP func end */
		} elseif ( 'site-transient' === $group ) {
			/**** Ori WP func start */
			$no_timeout       = array( 'update_core', 'update_plugins', 'update_themes' );
			$transient_option = '_site_transient_' . $transient;
			if ( ! in_array( $transient, $no_timeout, true ) ) {
				$transient_timeout = '_site_transient_timeout_' . $transient;
				$timeout           = get_site_option( $transient_timeout );
				if ( false !== $timeout && $timeout < time() ) {
					delete_site_option( $transient_option );
					delete_site_option( $transient_timeout );
					$value = false;
				}
			}

			if ( ! isset( $value ) ) {
				$value = get_site_option( $transient_option );
			}
			/**** Ori WP func end */
		} else {
			$value = false;
		}

		return $value;
	}

	/**
	 * Set transient to WP table
	 *
	 * Stores transient data in WordPress options table.
	 *
	 * @since 1.8.3
	 * @access private
	 * @see `wp-includes/option.php` function `set_transient`/`set_site_transient`
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Transient value.
	 * @param string $group      Transient group ('transient' or 'site-transient').
	 * @param int    $expiration Time until expiration in seconds.
	 * @return bool True on success, false on failure.
	 */
	private function _transient_set( $transient, $value, $group, $expiration ) {
		if ( 'transient' === $group ) {
			/**** Ori WP func start */
			$transient_timeout = '_transient_timeout_' . $transient;
			$transient_option  = '_transient_' . $transient;
			if ( false === get_option( $transient_option ) ) {
				$autoload = 'yes';
				if ( (int) $expiration ) {
					$autoload = 'no';
					add_option( $transient_timeout, time() + (int) $expiration, '', 'no' );
				}
				$result = add_option( $transient_option, $value, '', $autoload );
			} else {
				// If expiration is requested, but the transient has no timeout option,
				// delete, then re-create transient rather than update.
				$update = true;
				if ( (int) $expiration ) {
					if ( false === get_option( $transient_timeout ) ) {
						delete_option( $transient_option );
						add_option( $transient_timeout, time() + (int) $expiration, '', 'no' );
						$result = add_option( $transient_option, $value, '', 'no' );
						$update = false;
					} else {
						update_option( $transient_timeout, time() + (int) $expiration );
					}
				}
				if ( $update ) {
					$result = update_option( $transient_option, $value );
				}
			}
			/**** Ori WP func end */
		} elseif ( 'site-transient' === $group ) {
			/**** Ori WP func start */
			$transient_timeout = '_site_transient_timeout_' . $transient;
			$option            = '_site_transient_' . $transient;
			if ( false === get_site_option( $option ) ) {
				if ( (int) $expiration ) {
					add_site_option( $transient_timeout, time() + (int) $expiration );
				}
				$result = add_site_option( $option, $value );
			} else {
				if ( (int) $expiration ) {
					update_site_option( $transient_timeout, time() + (int) $expiration );
				}
				$result = update_site_option( $option, $value );
			}
			/**** Ori WP func end */
		} else {
			$result = null;
		}

		return $result;
	}

	/**
	 * Delete transient from WP table
	 *
	 * Removes transient data from WordPress options table.
	 *
	 * @since 1.8.3
	 * @access private
	 * @see `wp-includes/option.php` function `delete_transient`/`delete_site_transient`
	 *
	 * @param string $transient Transient name.
	 * @param string $group     Transient group ('transient' or 'site-transient').
	 */
	private function _transient_del( $transient, $group ) {
		if ( 'transient' === $group ) {
			/**** Ori WP func start */
			$option_timeout = '_transient_timeout_' . $transient;
			$option         = '_transient_' . $transient;
			$result         = delete_option( $option );
			if ( $result ) {
				delete_option( $option_timeout );
			}
			/**** Ori WP func end */
		} elseif ( 'site-transient' === $group ) {
			/**** Ori WP func start */
			$option_timeout = '_site_transient_timeout_' . $transient;
			$option         = '_site_transient_' . $transient;
			$result         = delete_site_option( $option );
			if ( $result ) {
				delete_site_option( $option_timeout );
			}
			/**** Ori WP func end */
		}
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @return WP_Object_Cache The current instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
