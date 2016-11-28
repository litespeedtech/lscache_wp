<?php


/**
 * The LiteSpeed Web Server response header tags.
 *
 * The constants listed here are used by the web server to determine actions
 * that need to take place.
 *
 * The TYPE_* constants are used to tag a cache entry with site-wide page types.
 *
 * The HEADER_* constants are used to notify the web server to do some action.
 *
 * @since      1.0.5
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Tags
{
	// G P C E tags reserved for litemage.

	const TYPE_FEED = 'FD';
	const TYPE_FRONTPAGE = 'F' ;
	const TYPE_HOME = 'H' ;
	const TYPE_POST = 'Po.' ; // Post. Cannot use P, reserved for litemage.
	const TYPE_ARCHIVE_POSTTYPE = 'PT.' ;
	const TYPE_ARCHIVE_TERM = 'T.' ; //for is_category|is_tag|is_tax
	const TYPE_AUTHOR = 'A.' ;
	const TYPE_ARCHIVE_DATE = 'D.' ;
	const TYPE_BLOG = 'B.' ;
	const TYPE_LOGIN = 'L';
	const TYPE_URL = 'URL.';
	const HEADER_PURGE = 'X-LiteSpeed-Purge' ;
	const HEADER_CACHE_CONTROL = 'X-LiteSpeed-Cache-Control' ;
	const HEADER_CACHE_TAG = 'X-LiteSpeed-Tag' ;
	const HEADER_CACHE_VARY = 'X-LiteSpeed-Vary' ;
	const HEADER_DEBUG = 'X-LiteSpeed-Debug' ;

	static $thirdparty_purge_tags = array();
	static $thirdparty_cache_tags = array();
	static $thirdparty_vary_cookies = array(); // vary header only!
	static $thirdparty_noncacheable = false;
	static $thirdparty_mobile = false;
	static $thirdparty_use_front_ttl = false;

	/**
	 * Gets cache tags that are already added for the current page.
	 *
	 * @since 1.0.5
	 * @access public
	 * @return array An array of all cache tags currently added.
	 */
	public static function get_cache_tags()
	{
		if (empty(self::$thirdparty_cache_tags)) {
			return self::$thirdparty_cache_tags;
		}
		return array_unique(self::$thirdparty_cache_tags);
	}

	/**
	 * Adds cache tags to the list of cache tags for the current page.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param mixed $tag A string or array of cache tags to add to the current list.
	 */
	public static function add_cache_tag($tag)
	{
		if (is_array($tag)) {
			self::$thirdparty_cache_tags = array_merge(self::$thirdparty_cache_tags, $tag);
		}
		else {
			self::$thirdparty_cache_tags[] = $tag;
		}
	}

	/**
	 * Gets purge tags that are already added for the current page.
	 *
	 * @since 1.0.5
	 * @access public
	 * @return array An array of all purge tags currently added.
	 */
	public static function get_purge_tags()
	{
		if (empty(self::$thirdparty_purge_tags)) {
			return self::$thirdparty_purge_tags;
		}
		return array_unique(self::$thirdparty_purge_tags);
	}

	/**
	 * Adds purge tags to the list of purge tags for the current page.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param mixed $tag A string or array of purge tags to add to the current list.
	 */
	public static function add_purge_tag($tag)
	{
		if (is_array($tag)) {
			self::$thirdparty_purge_tags = array_merge(self::$thirdparty_purge_tags, $tag);
		}
		else {
			self::$thirdparty_purge_tags[] = $tag;
		}
	}

	/**
	 * Gets vary cookies that are already added for the current page.
	 *
	 * @since 1.0.13
	 * @access public
	 * @return array An array of all vary cookies currently added.
	 */
	public static function get_vary_cookies()
	{
		if (empty(self::$thirdparty_vary_cookies)) {
			return self::$thirdparty_vary_cookies;
		}
		$cookies = array_unique(self::$thirdparty_vary_cookies);
		if (empty($cookies)) {
			return $cookies;
		}
		foreach ($cookies as $key => $val) {
			$cookies[$key] = 'cookie=' . $val;
		}
		return $cookies;
	}

	/**
	 * Adds vary cookie(s) to the list of vary cookies for the current page.
	 *
	 * @since 1.0.13
	 * @access public
	 * @param mixed $cookie A string or array of vary cookies to add to the
	 * current list.
	 */
	public static function add_vary_cookie($cookie)
	{
		if (is_array($cookie)) {
			self::$thirdparty_vary_cookies =
				array_merge(self::$thirdparty_vary_cookies, $cookie);
		}
		else {
			self::$thirdparty_vary_cookies[] = $cookie;
		}
	}

	/**
	 * Gets whether any plugins determined that the current page is
	 * non-cacheable.
	 *
	 * @return boolean True if the current page was deemed non-cacheable,
	 * false otherwise.
	 */
	public static function is_noncacheable()
	{
		return self::$thirdparty_noncacheable;
	}

	/**
	 * Mark the current page as non cacheable. This may be useful for
	 * if the litespeed_cache_is_cacheable hook point triggers too soon
	 * for the third party plugin to know if the page is cacheable.
	 *
	 * Must be called before the shutdown hook point.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public static function set_noncacheable()
	{
		if (defined('LSCWP_LOG')) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			LiteSpeed_Cache::debug_log('Thirdparty called set_noncacheable(): '
				. $trace[1]['class']);
		}
		self::$thirdparty_noncacheable = true;
	}

	/**
	 * Gets whether any plugins determined that the current page is
	 * mobile.
	 *
	 * @return boolean True if the current page was deemed mobile,
	 * false otherwise.
	 */
	public static function is_mobile()
	{
		return self::$thirdparty_mobile;
	}

	/**
	 * Mark the current page as mobile. This may be useful for
	 * if the plugin does not override wp_is_mobile.
	 *
	 * Must be called before the shutdown hook point.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public static function set_mobile()
	{
		self::$thirdparty_mobile = true;
	}

	/**
	 * Gets whether any plugins determined that the current page should use
	 * the front page TTL setting.
	 *
	 * @since 1.0.9
	 * @access public
	 * @return boolean True if use front page TTL, false otherwise.
	 */
	public static function get_use_frontpage_ttl()
	{
		return self::$thirdparty_use_front_ttl;
	}

	/**
	 * Mark the current page to use the front page ttl.
	 *
	 * @since 1.0.9
	 * @access public
	 */
	public static function set_use_frontpage_ttl()
	{
		self::$thirdparty_use_front_ttl = true;
	}

}
