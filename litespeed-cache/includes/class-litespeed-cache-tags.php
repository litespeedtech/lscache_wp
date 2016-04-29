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
	//const TYPE_FEED = 'FD';
	const TYPE_FRONTPAGE = 'F' ;
	const TYPE_HOME = 'H.' ;
	const TYPE_POST = 'P.' ;
	const TYPE_ARCHIVE_POSTTYPE = 'PT.' ;
	const TYPE_ARCHIVE_TERM = 'T.' ; //for is_category|is_tag|is_tax
	const TYPE_AUTHOR = 'A.' ;
	const TYPE_ARCHIVE_DATE = 'D.' ;
	const TYPE_BLOG = 'B.' ;
	const HEADER_PURGE = 'X-LiteSpeed-Purge' ;
	const HEADER_CACHE_CONTROL = 'X-LiteSpeed-Cache-Control' ;
	const HEADER_CACHE_TAG = 'X-LiteSpeed-Tag' ;
	const HEADER_CACHE_VARY = 'X-LiteSpeed-Vary' ;

	static $thirdparty_purge_tags = array();
	static $thirdparty_cache_tags = array();

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

}


