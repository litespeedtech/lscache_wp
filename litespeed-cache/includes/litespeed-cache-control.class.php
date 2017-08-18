<?php
/**
 * The plugin cache-control class for X-Litespeed-Cache-Control
 *
 * @since      1.1.3
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Control
{
	// private static $_instance ;

	const BM_CACHEABLE = 1 ;
	const BM_PRIVATE = 2 ;
	const BM_SHARED = 4 ;
	const BM_NO_VARY = 8 ;
	const BM_STALE = 128 ;
	const BM_NOTCACHEABLE = 256 ;

	const X_HEADER = 'X-LiteSpeed-Cache-Control' ;

	protected static $_control = 0 ;
	protected static $_custom_ttl = 0 ;
	private static $_mobile = false ;

	/**
	 * 1. Initialize cacheable status for `wp` hook
	 * 2. Hook error page tags for cacheable pages
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function init_cacheable()
	{
		// Hook `wp` to mark default cacheable status
		// NOTE: Any process that does NOT run into `wp` hook will not get cacheable by default
		add_action( 'wp', 'LiteSpeed_Cache_Control::set_cacheable', 5 ) ;

		// Hook WP REST to be cacheable
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_REST ) ) {
			add_action( 'rest_api_init', 'LiteSpeed_Cache_Control::set_cacheable', 5 ) ;
		}

		// Cache resources
		// NOTE: If any strange resource doesn't use normal WP logic `wp_loaded` hook, rewrite rule can handle it
		$cache_res = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_RES ) ;
		if ( $cache_res ) {
			$uri = esc_url( $_SERVER["REQUEST_URI"] ) ;
			$pattern = '!' . LiteSpeed_Cache_Admin_Rules::RW_PATTERN_RES . '!' ;
			if ( preg_match( $pattern, $uri ) ) {
				add_action( 'wp_loaded', 'LiteSpeed_Cache_Control::set_cacheable', 5 ) ;
			}
		}

		// Check error page
		add_filter( 'status_header', 'LiteSpeed_Cache_Tag::check_error_codes', 10, 2 ) ;
	}

	/**
	 * Set no vary setting
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function set_no_vary()
	{
		if ( self::is_no_vary() ) {
			return ;
		}
		self::$_control |= self::BM_NO_VARY ;
		LiteSpeed_Cache_Log::debug('X Cache_control -> no_vary') ;
	}

	/**
	 * Get no vary setting
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_no_vary()
	{
		return self::$_control & self::BM_NO_VARY ;
	}

	/**
	 * Set stale
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function set_stale()
	{
		if ( self::is_stale() ) {
			return ;
		}
		self::$_control |= self::BM_STALE ;
		LiteSpeed_Cache_Log::debug('X Cache_control -> stale') ;
	}

	/**
	 * Get stale
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_stale()
	{
		return self::$_control & self::BM_STALE ;
	}

	/**
	 * Set cache control to shared private
	 *
	 * @access public
	 * @since 1.1.3
	 * @param string $reason The reason to no cache
	 */
	public static function set_shared( $reason = false )
	{
		if ( self::is_shared() ) {
			return ;
		}
		self::$_control |= self::BM_SHARED ;
		self::set_private() ;
		if ( $reason ) {
			$reason = "( $reason )" ;
		}
		LiteSpeed_Cache_Log::debug( 'X Cache_control -> shared ' . $reason ) ;
	}

	/**
	 * Check if is shared private
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_shared()
	{
		return (self::$_control & self::BM_SHARED) && self::is_private() ;
	}

	/**
	 * Set cache control to private
	 *
	 * @access public
	 * @since 1.1.3
	 * @param string $reason The reason to no cache
	 */
	public static function set_private( $reason = false )
	{
		if ( self::is_private() ) {
			return ;
		}
		self::$_control |= self::BM_PRIVATE ;
		if ( $reason ) {
			$reason = "( $reason )" ;
		}
		LiteSpeed_Cache_Log::debug( 'X Cache_control -> private ' . $reason) ;
	}

	/**
	 * Check if is private
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_private()
	{
		return self::$_control & self::BM_PRIVATE ;
	}

	/**
	 * Initialize cacheable status in `wp` hook, if not call this, by default it will be non-cacheable
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function set_cacheable()
	{
		self::$_control |= self::BM_CACHEABLE ;
		LiteSpeed_Cache_Log::debug( 'X Cache_control init on' ) ;
	}

	/**
	 * Switch to nocacheable status
	 *
	 * @access public
	 * @since 1.1.3
	 * @param string $reason The reason to no cache
	 */
	public static function set_nocache( $reason = false )
	{
		self::$_control |= self::BM_NOTCACHEABLE ;
		if ( $reason ) {
			$reason = "( $reason )" ;
		}
		LiteSpeed_Cache_Log::debug( 'X Cache_control -> no Cache ' . $reason, 2 ) ;
	}

	/**
	 * Check current notcacheable bit set
	 *
	 * @access public
	 * @since 1.1.3
	 * @return bool True if notcacheable bit is set, otherwise false.
	 */
	public static function isset_notcacheable()
	{
		return self::$_control & self::BM_NOTCACHEABLE ;
	}

	/**
	 * Check current cacheable status
	 *
	 * @access public
	 * @since 1.1.3
	 * @return bool True if is still cacheable, otherwise false.
	 */
	public static function is_cacheable()
	{
		return ! self::isset_notcacheable() && self::$_control & self::BM_CACHEABLE ;
	}




	/**
	 * Set a custom TTL to use with the request if needed.
	 *
	 * @access public
	 * @since 1.1.3
	 * @param mixed $ttl An integer or string to use as the TTL. Must be numeric.
	 */
	public static function set_custom_ttl($ttl)
	{
		if ( is_numeric($ttl) ) {
			self::$_custom_ttl = $ttl ;
			LiteSpeed_Cache_Log::debug('X Cache_control TTL -> ' . $ttl) ;
		}
	}

	/**
	 * Generate final TTL.
	 *
	 * @access public
	 * @since 1.1.3
	 * @return int $ttl An integer to use as the TTL.
	 */
	public static function get_ttl()
	{
		if ( self::$_custom_ttl != 0 ) {
			return self::$_custom_ttl ;
		}

		// Check if is in timed url list or not
		$timed_urls = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_TIMED_URLS ) ;
		$timed_urls_time = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_TIMED_URLS_TIME ) ;
		if ( $timed_urls && $timed_urls_time ) {
			$timed_urls = explode( "\n", $timed_urls ) ;
			$current_url = LiteSpeed_Cache_Tag::build_uri_tag( true ) ;
			if ( in_array( $current_url, $timed_urls ) ) {
				// Use time limit ttl
				$scheduled_time = strtotime( $timed_urls_time ) ;
				$ttl = $scheduled_time - time() ;
				if ( $ttl < 0 ) {
					$ttl += 86400 ;// add one day
				}
				LiteSpeed_Cache_Log::debug( 'X Cache_control TTL is limited to ' . $ttl ) ;
				return $ttl ;
			}
		}

		if ( is_front_page() ){
			return LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL ) ;
		}

		$feed_ttl = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_FEED_TTL ) ;
		if ( is_feed() && $feed_ttl > 0 ) {
			return $feed_ttl ;
		}

		$ttl_404 = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_404_TTL ) ;
		if ( is_404() && $ttl_404 > 0 ) {
			return $ttl_404 ;
		}

		if ( LiteSpeed_Cache_Tag::get_error_code() === 403 ) {
			$ttl_403 = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_403_TTL ) ;
			return $ttl_403 ;
		}

		$ttl_500 = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_500_TTL ) ;
		if ( LiteSpeed_Cache_Tag::get_error_code() >= 500 ) {
			return $ttl_500 ;
		}

		return LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_PUBLIC_TTL ) ;
	}

	/**
	 * Check if need to set no cache status for redirection or not
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function check_redirect( $location, $status )
	{
		if ( $status == '301' && ! empty( $_SERVER[ 'SCRIPT_URI' ] ) ) {
			LiteSpeed_Cache_Log::debug( "301 from " . $_SERVER['SCRIPT_URI'] ) ;
			LiteSpeed_Cache_Log::debug( "301 to $location" ) ;
			if ( $_SERVER['SCRIPT_URI'] == $location ) {
				self::set_nocache( '301 to same url' ) ;
			}
		}
		return $location ;
	}

	/**
	 * Sets up the Cache Control header.
	 *
	 * @since 1.1.3
	 * @access public
	 * @return string empty string if empty, otherwise the cache control header.
	 */
	public static function output()
	{
		$esi_hdr = '' ;
		if ( LSWCP_ESI_SUPPORT && LiteSpeed_Cache_ESI::has_esi() ) {
			$esi_hdr = ',esi=on' ;
		}

		$hdr = self::X_HEADER . ': ' ;

		if ( ! self::is_cacheable() ) {
			$hdr .= 'no-cache' . $esi_hdr ;
			return $hdr ;
		}

		if ( self::is_shared() ) {
			$hdr .= 'shared,private' ;
		}
		elseif ( self::is_private() ) {
			$hdr .= 'private' ;
		}
		else {
			$hdr .= 'public' ;
		}

		if ( self::is_no_vary() ) {
			$hdr .= ',no-vary' ;
		}

		$hdr .= ',max-age=' . self::get_ttl() . $esi_hdr ;
		return $hdr ;
	}

	/**
	 * Generate all `control` tags before output
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function finalize()
	{
		// if is not cacheable, terminate check
		// Even no need to run 3rd party hook
		if ( ! self::is_cacheable() ) {
			LiteSpeed_Cache_Log::debug( 'not cacheable before ctrl finalize' ) ;
			return ;
		}

		if ( defined('LSCACHE_NO_CACHE') && LSCACHE_NO_CACHE ) {
			self::set_nocache('LSCACHE_NO_CACHE constant defined') ;
			return ;
		}

		// Apply 3rd party filter
		// Parse ESI block id
		$esi_id = false ;
		if ( defined( 'LSCACHE_IS_ESI' ) ) {
			$params = LiteSpeed_Cache_ESI::parse_esi_param() ;
			if ( $params !== false ) {
				$esi_id = $params[LiteSpeed_Cache_ESI::PARAM_BLOCK_ID] ;
			}
		}
		// NOTE: Hook always needs to run asap because some 3rd party set is_mobile in this hook
		do_action('litespeed_cache_api_control', $esi_id) ;

		// if is not cacheable, terminate check
		if ( ! self::is_cacheable() ) {
			LiteSpeed_Cache_Log::debug( 'not cacheable after api_control' ) ;
			return ;
		}

		// Check litespeed setting to set cacheable status
		if ( ! self::_setting_cacheable() ) {
			self::set_nocache() ;
			return ;
		}

		// If user has password cookie, do not cache (moved from vary)
		global $post ;
		if ( ! empty($post->post_password) && isset($_COOKIE['wp-postpass_' . COOKIEHASH]) ) {
			// If user has password cookie, do not cache
			self::set_nocache('pswd cookie') ;
			return ;
		}

		// The following check to the end is ONLY for mobile
		if ( ! LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_CACHE_MOBILE) ) {
			if ( self::is_mobile() ) {
				self::set_nocache('mobile') ;
			}
			return ;
		}

		if ( isset($_SERVER['LSCACHE_VARY_VALUE']) && $_SERVER['LSCACHE_VARY_VALUE'] === 'ismobile' ) {
			if ( ! wp_is_mobile() && ! self::is_mobile() ) {
				self::set_nocache( 'is not mobile' ) ;
				return ;
			}
		}
		elseif ( wp_is_mobile() || self::is_mobile() ) {
			self::set_nocache( 'is mobile' ) ;
			return ;
		}

	}

	/**
	 * Check if a page is cacheable based on litespeed setting.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return boolean True if cacheable, false otherwise.
	 */
	private static function _setting_cacheable()
	{
		// logged_in users already excluded, no hook added

		if( ! empty($_REQUEST[LiteSpeed_Cache::ACTION_KEY]) ) {
			return self::_no_cache_for('Query String Action') ;
		}

		if ( $_SERVER["REQUEST_METHOD"] !== 'GET' ) {
			return self::_no_cache_for('not GET method:' . $_SERVER["REQUEST_METHOD"]) ;
		}

		if ( is_feed() && LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_FEED_TTL) == 0 ) {
			return self::_no_cache_for('feed') ;
		}

		if ( is_trackback() ) {
			return self::_no_cache_for('trackback') ;
		}

		if ( is_404() && LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_404_TTL) == 0 ) {
			return self::_no_cache_for('404 pages') ;
		}

		if ( is_search() ) {
			return self::_no_cache_for('search') ;
		}

//		if ( !defined('WP_USE_THEMES') || !WP_USE_THEMES ) {
//			return self::_no_cache_for('no theme used') ;
//		}

		$excludes = LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_EXCLUDES_URI) ;
		if ( ! empty($excludes) && self::_is_uri_excluded(explode("\n", $excludes)) ) {
			return self::_no_cache_for('Admin configured URI Do not cache: ' . $_SERVER['REQUEST_URI']) ;
		}

		$excludes = LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT) ;
		if ( ! empty($excludes) && has_category(explode(',', $excludes)) ) {
			return self::_no_cache_for('Admin configured Category Do not cache.') ;
		}

		$excludes = LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG) ;
		if ( ! empty($excludes) && has_tag(explode(',', $excludes)) ) {
			return self::_no_cache_for('Admin configured Tag Do not cache.') ;
		}

		$excludes = LiteSpeed_Cache::config(LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES) ;
		if ( ! empty($excludes) && ! empty($_COOKIE) ) {
			$exclude_list = explode('|', $excludes) ;

			foreach( $_COOKIE as $key=>$val) {
				if ( in_array($key, $exclude_list) ) {
					return self::_no_cache_for('Admin configured Cookie Do not cache.') ;
				}
			}
		}

		$excludes = LiteSpeed_Cache::config(LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS) ;
		if ( ! empty($excludes) && isset($_SERVER['HTTP_USER_AGENT']) ) {
			$pattern = '/' . $excludes . '/' ;
			$nummatches = preg_match($pattern, $_SERVER['HTTP_USER_AGENT']) ;
			if ( $nummatches ) {
					return self::_no_cache_for('Admin configured User Agent Do not cache.') ;
			}
		}

		return true ;
	}

	/**
	 * Write a debug message for if a page is not cacheable.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $reason An explanation for why the page is not cacheable.
	 * @return boolean Return false.
	 */
	private static function _no_cache_for( $reason )
	{
		LiteSpeed_Cache_Log::debug('X Cache_control off - ' . $reason) ;
		return false ;
	}

	/**
	 * Check admin configuration to see if the uri accessed is excluded from cache.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param array $excludes_list List of excluded URIs
	 * @return boolean True if excluded, false otherwise.
	 */
	private static function _is_uri_excluded($excludes_list)
	{
		$uri = esc_url($_SERVER["REQUEST_URI"]);
		$uri_len = strlen( $uri ) ;
		if (is_multisite()) {
			$blog_details = get_blog_details(get_current_blog_id());
			$blog_path = $blog_details->path;
			$blog_path_len = strlen($blog_path);
			if (($uri_len >= $blog_path_len)
				&& (strncmp($uri, $blog_path, $blog_path_len) == 0)) {
				$uri = substr($uri, $blog_path_len - 1);
				$uri_len = strlen( $uri ) ;
			}
		}
		foreach( $excludes_list as $excludes_rule ){
			$rule_len = strlen( $excludes_rule );
			if (($excludes_rule[$rule_len - 1] == '$')) {
				if ($uri_len != (--$rule_len)) {
					continue;
				}
			}
			elseif ( $uri_len < $rule_len ) {
				continue;
			}

			if ( strncmp( $uri, $excludes_rule, $rule_len ) == 0 ){
				return true ;
			}
		}
		return false;
	}

	/**
	 * Gets whether any plugins determined that the current page is mobile.
	 *
	 * @access public
	 * @return boolean True if the current page was deemed mobile, false otherwise.
	 */
	public static function is_mobile()
	{
		return self::$_mobile ;
	}

	/**
	 * Mark the current page as mobile. This may be useful for if the plugin does not override wp_is_mobile.
	 *
	 * Must be called before the shutdown hook point.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public static function set_mobile()
	{
		self::$_mobile = true ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.3
	 * @access public
	 * @return Current class instance.
	 */
	// public static function get_instance()
	// {
	// 	$cls = get_called_class() ;
	// 	if ( ! isset(self::$_instance) ) {
	// 		self::$_instance = new $cls() ;
	// 	}

	// 	return self::$_instance ;
	// }
}