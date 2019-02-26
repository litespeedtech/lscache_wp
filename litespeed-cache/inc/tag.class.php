<?php
/**
 * The plugin cache-tag class for X-LiteSpeed-Tag
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Tag
{
	private static $_instance ;

	const TYPE_FEED = 'FD' ;
	const TYPE_FRONTPAGE = 'F' ;
	const TYPE_HOME = 'H' ;
	const TYPE_PAGES = 'PGS' ;
	const TYPE_PAGES_WITH_RECENT_POSTS = 'PGSRP' ;
	const TYPE_ERROR = 'ERR.' ;
	const TYPE_POST = 'Po.' ; // Post. Cannot use P, reserved for litemage.
	const TYPE_ARCHIVE_POSTTYPE = 'PT.' ;
	const TYPE_ARCHIVE_TERM = 'T.' ; //for is_category|is_tag|is_tax
	const TYPE_AUTHOR = 'A.' ;
	const TYPE_ARCHIVE_DATE = 'D.' ;
	const TYPE_BLOG = 'B.' ;
	const TYPE_LOGIN = 'L' ;
	const TYPE_URL = 'URL.' ;
	const TYPE_WIDGET = 'W.' ;
	const TYPE_ESI = 'ESI.' ;
	const TYPE_REST = 'REST' ;
	const TYPE_LIST = 'LIST' ;
	const TYPE_MIN = 'MIN' ;

	const X_HEADER = 'X-LiteSpeed-Tag' ;

	private static $_tags = array() ;
	private static $_tags_priv = array( 'tag_priv' ) ;
	protected static $_error_status = false ;

	/**
	 * Initialize
	 *
	 * @since    2.2.3
	 */
	private function __construct()
	{
		// register recent posts widget tag before theme renders it to make it work
		add_filter( 'widget_posts_args', 'LiteSpeed_Cache_Tag::add_widget_recent_posts' ) ;

	}

	/**
	 * Check if the login page is cacheable.
	 * If not, unset the cacheable member variable.
	 *
	 * NOTE: This is checked separately because login page doesn't go through WP logic.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function check_login_cacheable()
	{
		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_PAGE_LOGIN ) ) {
			return ;
		}
		if ( LiteSpeed_Cache_Control::isset_notcacheable() ) {
			return ;
		}

		if ( ! empty( $_GET ) ) {
			LiteSpeed_Cache_Control::set_nocache( 'has GET request' ) ;
			return ;
		}

		LiteSpeed_Cache_Control::set_cacheable() ;

		self::add( self::TYPE_LOGIN ) ;

		// we need to send lsc-cookie manually to make it be sent to all other users when is cacheable
		$list = headers_list() ;
		if ( empty( $list ) ) {
			return ;
		}
		foreach ( $list as $hdr ) {
			if ( strncasecmp( $hdr, 'set-cookie:', 11 ) == 0 ) {
				$cookie = substr( $hdr, 12 ) ;
				@header( 'lsc-cookie: ' . $cookie, false ) ;
			}
		}
	}

	/**
	 * Check if the page returns 403 and 500 errors.
	 *
	 * @since 1.0.13.1
	 * @access public
	 * @param $status_header
	 * @param $code
	 * @return $eror_status
	 */
	public static function check_error_codes( $status_header, $code )
	{
		$ttl_403 = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_403_TTL ) ;
		$ttl_500 = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_500_TTL ) ;
		if ( $code == 403 ) {
			if ( $ttl_403 <= 30 && LiteSpeed_Cache_Control::is_cacheable() ) {
				LiteSpeed_Cache_Control::set_nocache( '403 TTL is less than 30s' ) ;
			}
			else {
				self::$_error_status = $code ;
			}
		}
		elseif ( $code >= 500 && $code < 600 ) {
			if ( $ttl_500 <= 30 && LiteSpeed_Cache_Control::is_cacheable() ) {
				LiteSpeed_Cache_Control::set_nocache( 'TTL is less than 30s' ) ;
			}
		}
		elseif ( $code > 400 ) {
			self::$_error_status = $code ;
		}

		// Give the default status_header back
		return $status_header ;
	}

	/**
	 * Get error code.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function get_error_code()
	{
		return self::$_error_status ;
	}

	/**
	 * Register purge tag for pages with recent posts widget
	 * of the plugin.
	 *
	 * @since    1.0.15
	 * @access   public
	 * @param array $params [wordpress params for widget_posts_args]
	 */
	public static function add_widget_recent_posts( $params )
	{
		self::add( self::TYPE_PAGES_WITH_RECENT_POSTS ) ;
		return $params ;
	}

	/**
	 * Adds cache tags to the list of cache tags for the current page.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param mixed $tags A string or array of cache tags to add to the current list.
	 */
	public static function add( $tags )
	{
		if ( ! is_array( $tags ) ) {
			$tags = array( $tags ) ;
		}

		self::$_tags = array_merge( self::$_tags, $tags ) ;
	}

	/**
	 * Adds private cache tags to the list of cache tags for the current page.
	 *
	 * @since 1.6.3
	 * @access public
	 * @param mixed $tags A string or array of cache tags to add to the current list.
	 */
	public static function add_private( $tags )
	{
		if ( ! is_array( $tags ) ) {
			$tags = array( $tags ) ;
		}

		self::$_tags_priv = array_merge( self::$_tags_priv, $tags ) ;
	}

	/**
	 * Return tags for Admin QS
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function output_tags()
	{
		return self::$_tags ;
	}

	/**
	 * Will get a hash of the URI. Removes query string and appends a '/' if it is missing.
	 *
	 * @since 1.0.12
	 * @access public
	 * @param string $uri The uri to get the hash of.
	 * @param boolean $ori Return the original url or not
	 * @return bool|string False on input error, hash otherwise.
	 */
	public static function get_uri_tag( $uri, $ori = false )
	{
		$no_qs = strtok( $uri, '?' ) ;
		if ( empty( $no_qs ) ) {
			return false ;
		}
		$slashed = trailingslashit( $no_qs ) ;

		// If only needs uri tag
		if ( $ori ) {
			return $slashed ;
		}
		// return self::TYPE_URL . ( $slashed ) ;
		return self::TYPE_URL . md5( $slashed ) ;
	}

	/**
	 * Get the unique tag based on self url.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param boolean $ori Return the original url or not
	 */
	public static function build_uri_tag( $ori = false )
	{
		return self::get_uri_tag( urldecode( $_SERVER['REQUEST_URI'] ), $ori ) ;
	}

	/**
	 * Gets the cache tags to set for the page.
	 *
	 * This includes site wide post types (e.g. front page) as well as
	 * any third party plugin specific cache tags.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array The list of cache tags to set.
	 */
	private static function _build_type_tags()
	{
		$tags = array() ;

		$tags[] = LiteSpeed_Cache_Utility::page_type() ;

		$tags[] = self::build_uri_tag() ;

		if ( is_front_page() ) {
			$tags[] = self::TYPE_FRONTPAGE ;
		}
		elseif ( is_home() ) {
			$tags[] = self::TYPE_HOME ;
		}

		$err = self::get_error_code() ;
		if ( $err !== false ) {
			$tags[] = self::TYPE_ERROR . $err ;
		}

		$queried_obj_id = get_queried_object_id() ;
		if ( is_archive() ) {
			//An Archive is a Category, Tag, Author, Date, Custom Post Type or Custom Taxonomy based pages.
			if ( is_category() || is_tag() || is_tax() ) {
				$tags[] = self::TYPE_ARCHIVE_TERM . $queried_obj_id ;
			}
			elseif ( is_post_type_archive() ) {
				global $wp_query ;
				$post_type = $wp_query->get( 'post_type' ) ;
				$tags[] = self::TYPE_ARCHIVE_POSTTYPE . $post_type ;
			}
			elseif ( is_author() ) {
				$tags[] = self::TYPE_AUTHOR . $queried_obj_id ;
			}
			elseif ( is_date() ) {
				global $post ;
				$date = $post->post_date ;
				$date = strtotime( $date ) ;
				if ( is_day() ) {
					$tags[] = self::TYPE_ARCHIVE_DATE . date( 'Ymd', $date ) ;
				}
				elseif ( is_month() ) {
					$tags[] = self::TYPE_ARCHIVE_DATE . date( 'Ym', $date ) ;
				}
				elseif ( is_year() ) {
					$tags[] = self::TYPE_ARCHIVE_DATE . date( 'Y', $date ) ;
				}
			}
		}
		elseif ( is_singular() ) {
			//$this->is_singular = $this->is_single || $this->is_page || $this->is_attachment;
			$tags[] = self::TYPE_POST . $queried_obj_id ;

			if ( is_page() ) {
				$tags[] = self::TYPE_PAGES ;
			}
		}
		elseif ( is_feed() ) {
			$tags[] = self::TYPE_FEED ;
		}

		// Check REST API
		if ( LiteSpeed_Cache_REST::get_instance()->is_rest() ) {
			$tags[] = self::TYPE_REST ;

			$path = ! empty( $_SERVER[ 'SCRIPT_URL' ] ) ? $_SERVER[ 'SCRIPT_URL' ] : false ;
			if ( $path ) {
				// posts collections tag
				if ( substr( $path, -6 ) == '/posts' ) {
					$tags[] = self::TYPE_LIST ;// Not used for purge yet
				}

				// single post tag
				global $post;
				if ( ! empty( $post->ID ) && substr( $path, - strlen( $post->ID ) - 1 ) === '/' . $post->ID ) {
					$tags[] = self::TYPE_POST . $post->ID ;
				}

				// pages collections & single page tag
				if ( stripos( $path, '/pages' ) !== false ) {
					$tags[] = self::TYPE_PAGES ;
				}
			}

		}

		return $tags ;
	}

	/**
	 * Generate all cache tags before output
	 *
	 * @access private
	 * @since 1.1.3
	 */
	private static function _finalize()
	{
		// run 3rdparty hooks to tag
		do_action( 'litespeed_cache_api_tag' ) ;
		// generate wp tags
		if ( ! defined( 'LSCACHE_IS_ESI' ) ) {
			$type_tags = self::_build_type_tags() ;
			self::$_tags = array_merge( self::$_tags, $type_tags ) ;
		}
		// append blog main tag
		self::$_tags[] = '' ;
		// removed duplicates
		self::$_tags = array_unique( self::$_tags ) ;
	}

	/**
	 * Sets up the Cache Tags header.
	 * ONLY need to run this if is cacheable
	 *
	 * @since 1.1.3
	 * @access public
	 * @return string empty string if empty, otherwise the cache tags header.
	 */
	public static function output()
	{
		self::_finalize() ;

		$prefix_tags = array() ;
		/**
		 * Only append blog_id when is multisite
		 * @since 2.9.3
		 */
		$prefix = LSWCP_TAG_PREFIX . ( is_multisite() ? get_current_blog_id() : '' ) . '_' ;

		// If is_private and has private tags, append them first, then specify prefix to `public` for public tags
		if ( LiteSpeed_Cache_Control::is_private() ) {
			foreach ( self::$_tags_priv as $priv_tag ) {
				$prefix_tags[] = $prefix . $priv_tag ;
			}
			$prefix = 'public:' . $prefix ;
		}

		foreach ( self::$_tags as $tag ) {
			$prefix_tags[] = $prefix . $tag ;
		}

		$hdr = self::X_HEADER . ': ' . implode( ',', $prefix_tags ) ;

		return $hdr ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 2.2.3
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}