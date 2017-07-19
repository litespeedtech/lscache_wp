<?php
/**
 * The plugin vary class to manage X-LiteSpeed-Vary
 *
 * @since      1.2.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Vary
{
	private static $_instance ;

	const X_HEADER = 'X-LiteSpeed-Vary' ;

	const BM_LOGGED_IN = 1 ;
	const BM_COMMENTER = 2 ;

	private static $_vary_name = '_lscache_vary' ; // this default vary cookie is used for logged in status check
	private static $_vary_cookies = array() ; // vary header only!

	/**
	 * Adds the actions used for setting up cookies on log in/out.
	 *
	 * Also checks if the database matches the rewrite rule.
	 *
	 * @since 1.0.4
	 */
	private function __construct()
	{
		// logged in user doesn't cache
		if ( LiteSpeed_Cache_Router::is_logged_in() ) {
			// Make sure the cookie value is corrent
			self::add_logged_in() ;

			// register logout hook to clear login status
			add_action('clear_auth_cookie', 'LiteSpeed_Cache_Vary::remove_logged_in') ;
		}
		else {
			// Make sure the cookie value is corrent
			self::remove_logged_in() ;
			// Set vary cookie for logging in user
			add_action('set_logged_in_cookie', 'LiteSpeed_Cache_Vary::add_logged_in', 10, 2) ;

			// Commenter won't cache
			self::check_commenter() ;
		}

		if ( ! LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS) ) {// If don't cache pending comments
			// Set vary cookie for commenter.
			add_action('set_comment_cookies', 'LiteSpeed_Cache_Vary::add_commenter') ;
		}


		/******** Below to the end is only for cookie name setting check ********/
		// Get specific cookie name
		$db_cookie = false ;
		if ( is_multisite() ) {
			$options = LiteSpeed_Cache_Config::get_instance()->get_site_options() ;
			if ( is_array($options) ) {
				$db_cookie = $options[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE] ;
			}
		}
		else {
			$db_cookie = LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE) ;
		}

		// If no vary set in rewrite rule
		if ( ! isset($_SERVER['LSCACHE_VARY_COOKIE']) ) {
			if ( $db_cookie ) {
				// Display cookie error msg to admin
				if ( is_multisite() ? is_network_admin() : is_admin() ) {
					LiteSpeed_Cache_Admin_Display::show_error_cookie() ;
				}
				LiteSpeed_Cache_Control::set_nocache('vary cookie setting error') ;
				return ;
			}
			return ;
		}
		// If db setting does not exist, skip checking db value
		if ( ! $db_cookie ) {
			return ;
		}

		// beyond this point, need to make sure db vary setting is in $_SERVER env.
		$vary_arr = explode(',', $_SERVER['LSCACHE_VARY_COOKIE']) ;

		if ( in_array($db_cookie, $vary_arr) ) {
			self::$_vary_name = $db_cookie ;
			return ;
		}

		if ( is_multisite() ? is_network_admin() : is_admin() ) {
			LiteSpeed_Cache_Admin_Display::show_error_cookie() ;
		}
		LiteSpeed_Cache_Control::set_nocache('vary cookie setting lost error') ;
	}

	/**
	 * Check if cookie has a curtain bitmask
	 *
	 * @since 1.2.0
	 * @access private
	 */
	private static function cookie_has_bm($bm)
	{
		if ( empty($_COOKIE[self::$_vary_name]) ) {
			return false ;
		}
		if ( ! (intval($_COOKIE[self::$_vary_name]) & $bm) ) {
			return false ;
		}
		return true ;
	}

	/**
	 * Append user status with logged in
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function add_logged_in($logged_in_cookie = false, $expire = false)
	{
		if ( ! $expire ) {
			$expire = time() + 2 * DAY_IN_SECONDS ;
		}
		// If the cookie is lost somehow, set it
		if ( ! self::cookie_has_bm(self::BM_LOGGED_IN) ) {
			if ( empty($_COOKIE[self::$_vary_name]) ) {
				$_COOKIE[self::$_vary_name] = 0 ;
			}
			$_COOKIE[self::$_vary_name] |= self::BM_LOGGED_IN ;
			// save it
			self::cookie($_COOKIE[self::$_vary_name], $expire, is_ssl(), true) ;
		}
		LiteSpeed_Cache_Control::set_nocache('is logged in') ;
	}

	/**
	 * Remove user logged in status
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function remove_logged_in()
	{
		// If the cookie is set, unset it.
		if ( self::cookie_has_bm(self::BM_LOGGED_IN) ) {
			// remove logged in status from global var
			$_COOKIE[self::$_vary_name] &= ~self::BM_LOGGED_IN ;
			// save it
			self::cookie($_COOKIE[self::$_vary_name], time() + apply_filters('comment_cookie_lifetime', 30000000)) ;
			LiteSpeed_Cache_Control::set_nocache('removing logged in status') ;
		}
	}

	/**
	 * Append user status with commenter
	 *
	 * This is ONLY used when submit a comment
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function add_commenter()
	{
		// If the cookie is lost somehow, set it
		if ( ! self::cookie_has_bm(self::BM_COMMENTER) ) {
			if ( empty($_COOKIE[self::$_vary_name]) ) {
				$_COOKIE[self::$_vary_name] = 0 ;
			}
			$_COOKIE[self::$_vary_name] |= self::BM_COMMENTER ;
			// save it
			// only set commenter status for current domain path
			self::cookie($_COOKIE[self::$_vary_name], time() + apply_filters('comment_cookie_lifetime', 30000000), false, false, false) ;
		}
		LiteSpeed_Cache_Control::set_nocache('new commenter') ;
	}

	/**
	 * Remove user commenter status
	 *
	 * @since 1.2.0
	 * @access private
	 */
	private static function remove_commenter()
	{
		if ( self::cookie_has_bm(self::BM_COMMENTER) ) {
			// remove logged in status from global var
			$_COOKIE[self::$_vary_name] &= ~self::BM_COMMENTER ;
			// save it
			self::cookie($_COOKIE[self::$_vary_name], time() + apply_filters('comment_cookie_lifetime', 30000000), false, false, false) ;
			LiteSpeed_Cache_Control::set_nocache('removing commenter status') ;
		}
	}

	/**
	 * Check if the user accessing the page has the commenter cookie.
	 *
	 * If the user does not want to cache commenters, just check if user is commenter.
	 * Otherwise if the vary cookie is set, unset it. This is so that when the page is cached, the page will appear as if the user was a normal user.
	 * Normal user is defined as not a logged in user and not a commenter.
	 *
	 * @since 1.0.4
	 * @access public
	 * @return boolean True if do not cache for commenters and user is a commenter. False otherwise.
	 */
	public static function check_commenter()
	{
		// ONLY when user has the specific commenter status cookie, it is considered as a commenter
		// Otherwise we need to remove WP cookie to avoid pending comment been cached
		if ( ! LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS) && self::cookie_has_bm(self::BM_COMMENTER) ) {
			LiteSpeed_Cache_Control::set_nocache('existing commenter') ;
			return ;
		}

		// Now, we need to cache commenter or the current visitor, that means:
		// 1. We need to remove commenter status from vary.
		// 2. We need to remove WP comment $_COOKIE value temporarily

		// If vary cookie is set, need to change the value.
		self::remove_commenter() ;

		// Unset comment cookies for caching.
		foreach( $_COOKIE as $cookie_name => $cookie_value ) {
			if ( strlen($cookie_name) >= 15 && strncmp($cookie_name, 'comment_author_', 15) == 0 ) {
				unset($_COOKIE[$cookie_name]) ;
			}
		}
	}

	/**
	 * Gets the current request's user status.
	 *
	 * Helper function for other class' usage.
	 *
	 * @since 1.2.0
	 * @access public
	 * @return int The user status.
	 */
	public static function get_user_status()
	{
		return !empty($_COOKIE[self::$_vary_name]) ? $_COOKIE[self::$_vary_name] : false ;
	}

	/**
	 * Gets vary cookies that are already added for the current page.
	 *
	 * @since 1.0.13
	 * @access private
	 * @return array An array of all vary cookies currently added.
	 */
	private static function _format_vary_cookies()
	{
		if ( empty(self::$_vary_cookies) ) {
			return false ;
		}
		$cookies = array_filter(array_unique(self::$_vary_cookies)) ;
		if ( empty($cookies) ) {
			return false ;
		}
		foreach ($cookies as $key => $val) {
			$cookies[$key] = 'cookie=' . $val ;
		}
		return $cookies ;
	}

	/**
	 * Builds the vary header.
	 *
	 * Currently, this only checks post passwords.
	 *
	 * @since 1.0.13
	 * @access public
	 * @global $post
	 * @return mixed false if the user has the postpass cookie. Empty string
	 * if the post is not password protected. Vary header otherwise.
	 */
	public static function output()
	{
		if ( ! LiteSpeed_Cache_Control::is_cacheable() ) {
			return ;
		}
		$tp_cookies = self::_format_vary_cookies() ;
		global $post ;
		if ( ! empty($post->post_password) ) {
			if ( isset($_COOKIE['wp-postpass_' . COOKIEHASH]) ) {
				// If user has password cookie, do not cache
				LiteSpeed_Cache_Control::set_nocache('password protected vary') ;
				return ;
			}

			$tp_cookies[] = 'cookie=wp-postpass_' . COOKIEHASH ;
		}

		if ( empty($tp_cookies) ) {
			return ;
		}
		return self::X_HEADER . ': ' . implode(',', $tp_cookies) ;
	}

	/**
	 * Adds vary to the list of vary cookies for the current page.
	 *
	 * @since 1.0.13
	 * @access public
	 * @param mixed $vary A string or array of vary cookies to add to the current list.
	 */
	public static function add($vary)
	{
		if ( ! is_array($vary) ) {
			$vary = array($vary) ;
		}

		self::$_vary_cookies = array_merge(self::$_vary_cookies, $vary) ;
	}

	/**
	 * Set the vary cookie.
	 *
	 * If vary cookie changed, must set non cacheable.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param integer $val The value to update.
	 * @param integer $expire Expire time.
	 * @param boolean $ssl True if ssl connection, false otherwise.
	 * @param boolean $httponly True if the cookie is for http requests only, false otherwise.
	 * @param boolean $use_root_path True if use wp root path as cookie path
	 */
	private static function cookie($val = false, $expire = false, $ssl = false, $httponly = false, $use_root_path = true)
	{
		if ( ! $val ) {
			$expire = 1 ;
		}
		$path = $use_root_path ? COOKIEPATH : '' ;
		setcookie(self::$_vary_name, $val, $expire, $path, COOKIE_DOMAIN, $ssl, $httponly) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.2.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		$cls = get_called_class() ;
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new $cls() ;
		}

		return self::$_instance ;
	}
}
