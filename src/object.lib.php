<?php
/**
 * LiteSpeed Object Cache Library
 *
 * @since  1.8
 */
defined( 'WPINC' ) || exit;

/**
 * Handle exception
 */
if ( ! function_exists( 'litespeed_exception_handler' ) ) {
	function litespeed_exception_handler( $errno, $errstr, $errfile, $errline ) {
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
}

require_once __DIR__ . '/object-cache.cls.php';

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @since 1.8
 *
 * @global WP_Object_Cache $wp_object_cache
 */
function wp_cache_init() {
	$GLOBALS['wp_object_cache'] = WP_Object_Cache::get_instance();
}

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::add()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The cache key to use for retrieval later.
 * @param mixed      $data   The data to add to the cache.
 * @param string     $group  Optional. The group to add the cache to. Enables the same key
 *                           to be used across groups. Default empty.
 * @param int        $expire Optional. When the cache data should expire, in seconds.
 *                           Default 0 (no expiration).
 * @return bool True on success, false if cache key and group already exist.
 */
function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add( $key, $data, $group, (int) $expire );
}

/**
 * Adds multiple values to the cache in one call.
 *
 * @since 5.4
 *
 * @see WP_Object_Cache::add_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $data   Array of keys and values to be set.
 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
 * @param int    $expire Optional. When to expire the cache contents, in seconds.
 *                       Default 0 (no expiration).
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false if cache key and group already exist.
 */
function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add_multiple( $data, $group, $expire );
}

/**
 * Replaces the contents of the cache with new data.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::replace()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The key for the cache data that should be replaced.
 * @param mixed      $data   The new data to store in the cache.
 * @param string     $group  Optional. The group for the cache data that should be replaced.
 *                           Default empty.
 * @param int        $expire Optional. When to expire the cache contents, in seconds.
 *                           Default 0 (no expiration).
 * @return bool True if contents were replaced, false if original value does not exist.
 */
function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
}

/**
 * Saves the data to the cache.
 *
 * Differs from wp_cache_add() and wp_cache_replace() in that it will always write data.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::set()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The cache key to use for retrieval later.
 * @param mixed      $data   The contents to store in the cache.
 * @param string     $group  Optional. Where to group the cache contents. Enables the same key
 *                           to be used across groups. Default empty.
 * @param int        $expire Optional. When to expire the cache contents, in seconds.
 *                           Default 0 (no expiration).
 * @return bool True on success, false on failure.
 */
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set( $key, $data, $group, (int) $expire );
}

/**
 * Sets multiple values to the cache in one call.
 *
 * @since 5.4
 *
 * @see WP_Object_Cache::set_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $data   Array of keys and values to be set.
 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
 * @param int    $expire Optional. When to expire the cache contents, in seconds.
 *                       Default 0 (no expiration).
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false on failure.
 */
function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set_multiple( $data, $group, $expire );
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::get()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key   The key under which the cache contents are stored.
 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool       $force Optional. Whether to force an update of the local cache
 *                          from the persistent cache. Default false.
 * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
 *                          Disambiguates a return of false, a storable value. Default null.
 * @return mixed|false The cache contents on success, false on failure to retrieve contents.
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;

	return $wp_object_cache->get( $key, $group, $force, $found );
}

/**
 * Retrieves multiple values from the cache in one call.
 *
 * @since 5.4
 *
 * @see WP_Object_Cache::get_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $keys  Array of keys under which the cache contents are stored.
 * @param string $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool   $force Optional. Whether to force an update of the local cache
 *                      from the persistent cache. Default false.
 * @return array Array of return values, grouped by key. Each value is either
 *               the cache contents on success, or false on failure.
 */
function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
	global $wp_object_cache;

	return $wp_object_cache->get_multiple( $keys, $group, $force );
}

/**
 * Removes the cache contents matching key and group.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::delete()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key   What the contents in the cache are called.
 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
 * @return bool True on successful removal, false on failure.
 */
function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete( $key, $group );
}

/**
 * Deletes multiple values from the cache in one call.
 *
 * @since 5.4
 *
 * @see WP_Object_Cache::delete_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $keys  Array of keys under which the cache to deleted.
 * @param string $group Optional. Where the cache contents are grouped. Default empty.
 * @return bool[] Array of return values, grouped by key. Each value is either
 *                true on success, or false if the contents were not deleted.
 */
function wp_cache_delete_multiple( array $keys, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete_multiple( $keys, $group );
}

/**
 * Increments numeric cache item's value.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::incr()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The key for the cache contents that should be incremented.
 * @param int        $offset Optional. The amount by which to increment the item's value.
 *                           Default 1.
 * @param string     $group  Optional. The group the key is in. Default empty.
 * @return int|false The item's new value on success, false on failure.
 */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->incr( $key, $offset, $group );
}

/**
 * Decrements numeric cache item's value.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::decr()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    The cache key to decrement.
 * @param int        $offset Optional. The amount by which to decrement the item's value.
 *                           Default 1.
 * @param string     $group  Optional. The group the key is in. Default empty.
 * @return int|false The item's new value on success, false on failure.
 */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->decr( $key, $offset, $group );
}

/**
 * Removes all cache items.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::flush()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @return bool True on success, false on failure.
 */
function wp_cache_flush() {
	global $wp_object_cache;

	return $wp_object_cache->flush();
}

/**
 * Removes all cache items from the in-memory runtime cache.
 *
 * @since 5.4
 *
 * @see WP_Object_Cache::flush_runtime()
 *
 * @return bool True on success, false on failure.
 */
function wp_cache_flush_runtime() {
	global $wp_object_cache;

	return $wp_object_cache->flush_runtime();
}

/**
 * Removes all cache items in a group, if the object cache implementation supports it.
 *
 * Before calling this function, always check for group flushing support using the
 * `wp_cache_supports( 'flush_group' )` function.
 *
 * @since 5.4
 *
 * @see WP_Object_Cache::flush_group()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string $group Name of group to remove from cache.
 * @return bool True if group was flushed, false otherwise.
 */
function wp_cache_flush_group( $group ) {
	global $wp_object_cache;

	return $wp_object_cache->flush_group( $group );
}

/**
 * Determines whether the object cache implementation supports a particular feature.
 *
 * @since 5.4
 *
 * @param string $feature Name of the feature to check for. Possible values include:
 *                        'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple',
 *                        'flush_runtime', 'flush_group'.
 * @return bool True if the feature is supported, false otherwise.
 */
function wp_cache_supports( $feature ) {
	switch ( $feature ) {
		case 'add_multiple':
		case 'set_multiple':
		case 'get_multiple':
		case 'delete_multiple':
		case 'flush_runtime':
			return true;

		case 'flush_group':
		default:
			return false;
	}
}

/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache.
 *
 * This does not mean that plugins can't implement this function when they need
 * to make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @since 1.8
 *
 * @return true Always returns true.
 */
function wp_cache_close() {
	return true;
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::add_global_groups()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string|string[] $groups A group or an array of groups to add.
 */
function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_global_groups( $groups );
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @since 1.8
 *
 * @param string|string[] $groups A group or an array of groups to add.
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_non_persistent_groups( $groups );
}

/**
 * Switches the internal blog ID.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::switch_to_blog()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int $blog_id Site ID.
 */
function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;

	$wp_object_cache->switch_to_blog( $blog_id );
}



class WP_Object_Cache {
	protected static $_instance;

	private $_object_cache;

	private $_cache = array();
	private $_cache_404 = array();

	private $cache_total = 0;
	private $count_hit_incall = 0;
	private $count_hit = 0;
	private $count_miss_incall = 0;
	private $count_miss = 0;
	private $count_set = 0;

	protected $global_groups = array();
	private $blog_prefix;
	private $multisite;

	/**
	 * Init.
	 *
	 * @since  1.8
	 */
	public function __construct() {
		$this->_object_cache = \LiteSpeed\Object_Cache::cls();

		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';

		/**
		 * Fix multiple instance using same oc issue
		 * @since  1.8.2
		 */
		! defined( 'LSOC_PREFIX' ) && define( 'LSOC_PREFIX', substr( md5( __FILE__ ), -5 ) );
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
		return $this->$name = $value;
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

		if ( is_string( $key ) && trim( $key ) !== '' ) {
			return true;
		}

		$type = gettype( $key );

		if ( ! function_exists( '__' ) ) {
			wp_load_translations_early();
		}

		$message = is_string( $key )
			? __( 'Cache key must not be an empty string.' )
			/* translators: %s: The type of the given cache key. */
			: sprintf( __( 'Cache key must be integer or non-empty string, %s given.' ), $type );

		_doing_it_wrong(
			sprintf( '%s::%s', __CLASS__, debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[1]['function'] ),
			$message,
			'6.1.0'
		);

		return false;
	}

	/**
	 * Get the final key.
	 *
	 * @since 1.8
	 * @access private
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
	 * @since  1.8
	 * @access public
	 */
	public function debug() {
		return ' [total] ' . $this->cache_total
			. ' [hit_incall] ' . $this->count_hit_incall
			. ' [hit] ' . $this->count_hit
			. ' [miss_incall] ' . $this->count_miss_incall
			. ' [miss] ' . $this->count_miss
			. ' [set] ' . $this->count_set;
	}

	/**
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @uses WP_Object_Cache::_exists() Checks to see if the cache already has data.
	 * @uses WP_Object_Cache::set()     Sets the data after the checking the cache
	 *                                  contents existence.
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
	 *
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
// error_log("oc: set \t\t\t[key] " . $id );
		$this->_cache[ $id ] = $data;

		if( array_key_exists( $id, $this->_cache_404 ) ) {
// error_log("oc: unset404\t\t\t[key] " . $id );
			unset( $this->_cache_404[ $id ] );
		}

		if ( ! $this->_object_cache->is_non_persistent( $group ) ) {
			$this->_object_cache->set( $id, serialize( array( 'data' => $data ) ), (int) $expire );
			$this->count_set ++;
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

// error_log('');
// error_log("oc: get \t\t\t[key] " . $id . ( $force ? "\t\t\t [forced] " : '' ) );
		$found = false;
		$found_in_oc = false;
		$cache_val = false;
		if ( array_key_exists( $id, $this->_cache ) && ! $force ) {
			$found = true;
			$cache_val = $this->_cache[ $id ];
			$this->count_hit_incall ++;
		}
		elseif ( ! array_key_exists( $id, $this->_cache_404 ) && ! $this->_object_cache->is_non_persistent( $group ) ) {
			$v = $this->_object_cache->get( $id );

			if ( $v !== null ) {
				$v = @maybe_unserialize( $v );
			}

			// To be compatible with false val
			if ( is_array( $v ) && array_key_exists( 'data', $v ) ) {
				$this->count_hit ++;
				$found = true;
				$found_in_oc = true;
				$cache_val = $v[ 'data' ];
			}
			else { // Can't find key, cache it to 404
// error_log("oc: add404\t\t\t[key] " . $id );
				$this->_cache_404[ $id ] = 1;
				$this->count_miss ++;
			}
		}
		else {
			$this->count_miss_incall ++;
		}

		if ( is_object( $cache_val ) ) {
			$cache_val = clone $cache_val;
		}

		// If not found but has `Store Transients` cfg on, still need to follow WP's get_transient() logic
		if ( ! $found && $this->_object_cache->store_transients( $group ) ) {
			$cache_val = $this->_transient_get( $key, $group );
			if ( $cache_val ) {
				$found = true; // $found not used for now (v1.8.3)
			}
		}

		if ( $found_in_oc ) {
			$this->_cache[ $id ] = $cache_val;
		}

		$this->cache_total ++;

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
	 * @param int|string $key        What the contents in the cache are called.
	 * @param string     $group      Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool       $deprecated Optional. Unused. Default false.
	 * @return bool True on success, false if the contents were not deleted.
	 */
	public function delete( $key, $group = 'default', $deprecated = false ) {
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
// error_log("oc: delete \t\t\t[key] " . $id );

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
		}
		else {
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
		$this->_cache = array();
		$this->_cache_404 = array();

		return true;
	}

	/**
	 * Removes all cache items in a group.
	 *
	 * @since 5.4
	 * @access public
	 *
	 * @param string $group Name of group to remove from cache.
	 * @return true Always returns true.
	 */
	public function flush_group( $group ) {
		// unset( $this->cache[ $group ] );

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
		$blog_id = (int) $blog_id;
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
	}

	/**
	 * Get transient from wp table
	 *
	 * @since 1.8.3
	 * @access private
	 * @see `wp-includes/option.php` function `get_transient`/`set_site_transient`
	 */
	private function _transient_get( $transient, $group ) {
		if ( $group == 'transient' ) {
			/**** Ori WP func start ****/
			$transient_option = '_transient_' . $transient;
			if ( ! wp_installing() ) {
				// If option is not in alloptions, it is not autoloaded and thus has a timeout
				$alloptions = wp_load_alloptions();
				if ( !isset( $alloptions[$transient_option] ) ) {
					$transient_timeout = '_transient_timeout_' . $transient;
					$timeout = get_option( $transient_timeout );
					if ( false !== $timeout && $timeout < time() ) {
						delete_option( $transient_option  );
						delete_option( $transient_timeout );
						$value = false;
					}
				}
			}

			if ( ! isset( $value ) )
				$value = get_option( $transient_option );
			/**** Ori WP func end ****/
		}
		elseif ( $group == 'site-transient' ) {
			/**** Ori WP func start ****/
			$no_timeout = array('update_core', 'update_plugins', 'update_themes');
			$transient_option = '_site_transient_' . $transient;
			if ( ! in_array( $transient, $no_timeout ) ) {
				$transient_timeout = '_site_transient_timeout_' . $transient;
				$timeout = get_site_option( $transient_timeout );
				if ( false !== $timeout && $timeout < time() ) {
					delete_site_option( $transient_option  );
					delete_site_option( $transient_timeout );
					$value = false;
				}
			}

			if ( ! isset( $value ) )
				$value = get_site_option( $transient_option );
			/**** Ori WP func end ****/
		}
		else {
			$value = false;
		}

		return $value;
	}

	/**
	 * Set transient to WP table
	 *
	 * @since 1.8.3
	 * @access private
	 * @see `wp-includes/option.php` function `set_transient`/`set_site_transient`
	 */
	private function _transient_set( $transient, $value, $group, $expiration ) {
		if ( $group == 'transient' ) {
			/**** Ori WP func start ****/
			$transient_timeout = '_transient_timeout_' . $transient;
			$transient_option = '_transient_' . $transient;
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
			/**** Ori WP func end ****/
		}
		elseif ( $group == 'site-transient' ) {
			/**** Ori WP func start ****/
			$transient_timeout = '_site_transient_timeout_' . $transient;
			$option = '_site_transient_' . $transient;
			if ( false === get_site_option( $option ) ) {
				if ( (int) $expiration )
					add_site_option( $transient_timeout, time() + (int) $expiration );
				$result = add_site_option( $option, $value );
			} else {
				if ( (int) $expiration )
					update_site_option( $transient_timeout, time() + (int) $expiration );
				$result = update_site_option( $option, $value );
			}
			/**** Ori WP func end ****/
		}
		else {
			$result = null;
		}

		return $result;
	}

	/**
	 * Delete transient from WP table
	 *
	 * @since 1.8.3
	 * @access private
	 * @see `wp-includes/option.php` function `delete_transient`/`delete_site_transient`
	 */
	private function _transient_del( $transient, $group ) {
		if ( $group == 'transient' ) {
			/**** Ori WP func start ****/
			$option_timeout = '_transient_timeout_' . $transient;
			$option = '_transient_' . $transient;
			$result = delete_option( $option );
			if ( $result )
				delete_option( $option_timeout );
			/**** Ori WP func end ****/
		}
		elseif ( $group == 'site-transient' ) {
			/**** Ori WP func start ****/
			$option_timeout = '_site_transient_timeout_' . $transient;
			$option = '_site_transient_' . $transient;
			$result = delete_site_option( $option );
			if ( $result )
				delete_site_option( $option_timeout );
			/**** Ori WP func end ****/
		}
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.8
	 * @access public
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}