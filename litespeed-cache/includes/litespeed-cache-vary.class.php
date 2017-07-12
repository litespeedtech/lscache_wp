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

	const HEADER_CACHE_VARY = 'X-LiteSpeed-Vary' ;
	const LSCOOKIE_VARY_NAME = 'LSCACHE_VARY_COOKIE' ;
	const LSCOOKIE_DEFAULT_VARY = '_lscache_vary' ;
	const LSCOOKIE_VARY_LOGGED_IN = 1;
	const LSCOOKIE_VARY_COMMENTER = 2 ;

	protected static $user_status = 0 ;
	protected static $current_vary ;
	private static $_vary_cookies = array() ; // vary header only!

	/**
	 * Checks if the user is logged in. If the user is logged in, does an
	 * additional check to make sure it's using the correct login cookie.
	 *
	 * @access public
	 * @return boolean True if logged in, false otherwise.
	 */
	public static function check_user_logged_in()
	{
		if ( ! is_user_logged_in() ) {
			// If the cookie is set, unset it.
			if ( isset($_COOKIE) && isset($_COOKIE[self::$current_vary]) && (intval($_COOKIE[self::$current_vary]) & self::LSCOOKIE_VARY_LOGGED_IN) ) {
				self::cookie(~self::LSCOOKIE_VARY_LOGGED_IN, time() + apply_filters( 'comment_cookie_lifetime', 30000000 )) ;
				$_COOKIE[self::$current_vary] &= ~self::LSCOOKIE_VARY_LOGGED_IN ;
			}
			return false ;
		}
		elseif ( ! isset($_COOKIE[self::$current_vary]) ) {
			self::cookie(self::LSCOOKIE_VARY_LOGGED_IN, time() + 2 * DAY_IN_SECONDS, is_ssl(), true) ;
		}
		self::$user_status |= self::LSCOOKIE_VARY_LOGGED_IN ;
		return true ;
	}

	/**
	 * Append user status with commenter
	 *
	 * @access public
	 * @since 1.2.0
	 */
	public static function set_commenter()
	{
		self::$user_status |= self::LSCOOKIE_VARY_COMMENTER ;
	}

	public static function get_user_loggedin()
	{
		return self::$user_status & self::LSCOOKIE_VARY_LOGGED_IN ;
	}

	/**
	 * Gets the current request's user status.
	 *
	 * Helper function for other class' usage.
	 *
	 * @access public
	 * @since 1.2.0
	 * @return int The user status.
	 */
	public static function get_user_status()
	{
		return self::$user_status ;
	}

	/**
	 * Adds the actions used for setting up cookies on log in/out.
	 *
	 * Also checks if the database matches the rewrite rule.
	 *
	 * @since 1.0.4
	 * @access public
	 * @return boolean True if cookies are bad, false otherwise.
	 */
	public static function setup_cookies()
	{
		$ret = false ;
		// Set vary cookie for logging in user, unset for logging out.
		add_action('set_logged_in_cookie', 'LiteSpeed_Cache_Vary::set_user_cookie', 10, 5) ;
		add_action('clear_auth_cookie', 'LiteSpeed_Cache_Vary::set_user_cookie', 10, 5) ;

		if ( ! LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS) ) {
			// Set vary cookie for commenter.
			add_action('set_comment_cookies', 'LiteSpeed_Cache_Vary::set_comment_cookie', 10, 2) ;
		}
		if ( is_multisite() ) {
			$options = LiteSpeed_Cache_Config::get_instance()->get_site_options() ;
			if ( is_array($options) ) {
				$db_cookie = $options[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE] ;
			}
		}
		else {
			$db_cookie = LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE) ;
		}

		if ( ! isset($_SERVER[self::LSCOOKIE_VARY_NAME]) ) {
			if ( ! empty($db_cookie) ) {
				$ret = true ;
				if ( is_multisite() ? is_network_admin() : is_admin() ) {
					LiteSpeed_Cache_Admin_Display::show_error_cookie() ;
				}
			}
			self::$current_vary = self::LSCOOKIE_DEFAULT_VARY ;
			return $ret ;
		}
		elseif ( empty($db_cookie) ) {
			self::$current_vary = self::LSCOOKIE_DEFAULT_VARY ;
			return $ret ;
		}

		// beyond this point, need to do more processing.
		$vary_arr = explode(',', $_SERVER[self::LSCOOKIE_VARY_NAME]) ;

		if ( in_array($db_cookie, $vary_arr) ) {
			self::$current_vary = $db_cookie ;
			return $ret ;
		}
		elseif ( is_multisite() ? is_network_admin() : is_admin() ) {
			LiteSpeed_Cache_Admin_Display::show_error_cookie() ;
		}

		$ret = true ;
		self::$current_vary = self::LSCOOKIE_DEFAULT_VARY ;
		return $ret ;
	}

	/**
	 * Do the action of setting the vary cookie.
	 *
	 * Since we are using bitwise operations, if the resulting cookie has
	 * value zero, we need to set the expire time appropriately.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param integer $update_val The value to update.
	 * @param integer $expire Expire time.
	 * @param boolean $ssl True if ssl connection, false otherwise.
	 * @param boolean $httponly True if the cookie is for http requests only, false otherwise.
	 */
	public static function cookie($update_val, $expire, $ssl = false, $httponly = false)
	{
		$curval = 0 ;
		if ( isset($_COOKIE[self::$current_vary]) ) {
			$curval = intval($_COOKIE[self::$current_vary]) ;
		}

		// not, remove from curval.
		if ( $update_val < 0 ) {
			// If cookie will no longer exist, delete the cookie.
			if ( $curval == 0 || $curval == (~$update_val) ) {
				// Use a year in case of bad local clock.
				$expire = time() - 31536001 ;
			}
			$curval &= $update_val ;
		}
		else { // add to curval.
			$curval |= $update_val ;
		}
		setcookie(self::$current_vary, $curval, $expire, COOKIEPATH, COOKIE_DOMAIN, $ssl, $httponly) ;
	}

	/**
	 * Sets cookie denoting logged in/logged out.
	 *
	 * This will notify the server on next page request not to serve from cache.
	 *
	 * @since 1.0.1
	 * @access public
	 * @param mixed $logged_in_cookie
	 * @param string $expire Expire time.
	 * @param integer $expiration Expire time.
	 * @param integer $user_id The user's id.
	 * @param string $action Whether the user is logging in or logging out.
	 */
	public static function set_user_cookie($logged_in_cookie = false, $expire = ' ', $expiration = 0, $user_id = 0, $action = 'logged_out')
	{
		if ( $action == 'logged_in' ) {
			self::cookie(self::LSCOOKIE_VARY_LOGGED_IN, $expire, is_ssl(), true) ;
		}
		else {
			self::cookie(~self::LSCOOKIE_VARY_LOGGED_IN, time() + apply_filters('comment_cookie_lifetime', 30000000)) ;
		}
	}

	/**
	 * Sets a cookie that marks the user as a commenter.
	 *
	 * This will notify the server on next page request not to serve
	 * from cache if that setting is enabled.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param mixed $comment Comment object
	 * @param mixed $user The visiting user object.
	 */
	public static function set_comment_cookie($comment, $user)
	{
		if ( $user->exists() ) {
			return ;
		}
		$comment_cookie_lifetime = time() + apply_filters( 'comment_cookie_lifetime', 30000000 ) ;
		self::cookie(self::LSCOOKIE_VARY_COMMENTER, $comment_cookie_lifetime) ;
	}

	/**
	 * Check if the user accessing the page has the commenter cookie.
	 *
	 * If the user does not want to cache commenters, just check if user is commenter.
	 * Otherwise if the vary cookie is set, unset it. This is so that when
	 * the page is cached, the page will appear as if the user was a normal user.
	 * Normal user is defined as not a logged in user and not a commenter.
	 *
	 * @since 1.0.4
	 * @access public
	 * @return boolean True if do not cache for commenters and user is a commenter. False otherwise.
	 */
	public static function check_cookies()
	{
		if ( ! LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS) ) {
			// If do not cache commenters, check cookie for commenter value.
			if ( isset($_COOKIE[self::$current_vary]) && ($_COOKIE[self::$current_vary] & self::LSCOOKIE_VARY_COMMENTER) ) {
				self::set_commenter() ;
				return true ;
			}
			// If wp commenter cookie exists, need to set vary and do not cache.
			foreach( $_COOKIE as $cookie_name => $cookie_value ) {
				if ( strlen($cookie_name) >= 15 && strncmp($cookie_name, 'comment_author_', 15) == 0 ) {
					$user = wp_get_current_user() ;
					self::set_comment_cookie(NULL, $user) ;
					self::set_commenter() ;
					return true ;
				}
			}
			return false ;
		}

		// If vary cookie is set, need to change the value.
		if ( isset($_COOKIE[self::$current_vary]) ) {
			self::cookie(~self::LSCOOKIE_VARY_COMMENTER, 14 * DAY_IN_SECONDS) ;
			unset($_COOKIE[self::$current_vary]) ;
		}

		// If cache commenters, unset comment cookies for caching.
		foreach( $_COOKIE as $cookie_name => $cookie_value ) {
			if ( strlen($cookie_name) >= 15 && strncmp($cookie_name, 'comment_author_', 15) == 0 ) {
				self::set_commenter() ;
				unset($_COOKIE[$cookie_name]) ;
			}
		}
		return false ;
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
		if ( empty(self::$_vary_cookies) ) {
			return self::$_vary_cookies ;
		}
		$cookies = array_unique(self::$_vary_cookies) ;
		if ( empty($cookies) ) {
			return $cookies ;
		}
		foreach ($cookies as $key => $val) {
			$cookies[$key] = 'cookie=' . $val ;
		}
		return $cookies ;
	}

	/**
	 * Adds vary cookie(s) to the list of vary cookies for the current page.
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
		if ( ! LiteSpeed_Cache_Control::get_cacheable() ) {
			return '' ;
		}
		$tp_cookies = self::get_vary_cookies() ;
		global $post ;
		if ( ! empty($post->post_password) ) {
			if ( ! isset($_COOKIE['wp-postpass_' . COOKIEHASH]) ) {
				$tp_cookies[] = 'cookie=wp-postpass_' . COOKIEHASH ;
			}
		}

		if ( empty($tp_cookies) ) {
			return '' ;
		}
		return self::HEADER_CACHE_VARY . ': ' . implode(',', $tp_cookies) ;
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
