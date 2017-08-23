<?php
/**
 * The plugin vary class to manage X-LiteSpeed-Vary
 *
 * @since      1.1.3
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Vary
{
	private static $_instance ;

	const X_HEADER = 'X-LiteSpeed-Vary' ;

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
		// logged in user
		if ( LiteSpeed_Cache_Router::is_logged_in() ) {
			// Make sure the cookie value is corrent
			self::add_logged_in() ;

			// If not esi, check cache logged-in user setting
			if ( ! LSWCP_ESI_SUPPORT || ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_ESI_ENABLE ) ) {
				// If cache logged-in, then init cacheable to private
				if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_PRIV ) ) {
					add_action( 'wp_logout', 'LiteSpeed_Cache_Purge::purge_on_logout' ) ;

					LiteSpeed_Cache_Control::init_cacheable() ;
					LiteSpeed_Cache_Control::set_private( 'logged in user' ) ;
				}
				// No cache for logged-in user
				else {
					LiteSpeed_Cache_Control::set_nocache( 'logged in user' ) ;
				}
			}
			// ESI is on, can be public cache
			else {
				LiteSpeed_Cache_Control::init_cacheable() ;
			}

			// register logout hook to clear login status
			add_action( 'clear_auth_cookie', 'LiteSpeed_Cache_Vary::remove_logged_in' ) ;

		}
		else {
			// Make sure the cookie value is corrent
			self::remove_logged_in() ;

			// Set vary cookie for logging in user, otherwise the user will hit public with vary=0 (guest version)
			add_action( 'set_logged_in_cookie', 'LiteSpeed_Cache_Vary::add_logged_in', 10, 2 ) ;
			add_action( 'wp_login', 'LiteSpeed_Cache_Purge::purge_on_logout' ) ;

			LiteSpeed_Cache_Control::init_cacheable() ;

			// Check `login page` cacheable setting because they don't go through main WP logic
			add_action( 'login_init', 'LiteSpeed_Cache_Tag::check_login_cacheable', 5 ) ;

		}

		// Add comment list ESI
		add_filter('comments_array', array( $this, 'check_commenter' ) ) ;

		// Set vary cookie for commenter.
		add_action('set_comment_cookies', array( $this, 'append_commenter' ) ) ;

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
	 * Hooked to the comments_array filter.
	 *
	 * Check if the user accessing the page has the commenter cookie.
	 *
	 * If the user does not want to cache commenters, just check if user is commenter.
	 * Otherwise if the vary cookie is set, unset it. This is so that when the page is cached, the page will appear as if the user was a normal user.
	 * Normal user is defined as not a logged in user and not a commenter.
	 *
	 * @since 1.0.4
	 * @access public
	 * @global type $post
	 * @param array $comments The current comments to output
	 * @return array The comments to output.
	 */
	public function check_commenter( $comments )
	{
		$pending = false ;
		foreach ( $comments as $comment ) {
			if ( ! $comment->comment_approved ) {// current user has pending comment
				$pending = true ;
				break ;
			}
		}

		// No pending comments, don't need to add private cache
		if ( ! $pending ) {
			$this->remove_commenter() ;

			foreach( $_COOKIE as $cookie_name => $cookie_value ) {
				if ( strlen( $cookie_name ) >= 15 && strncmp( $cookie_name, 'comment_author_', 15 ) == 0 ) {
					unset( $_COOKIE[ $cookie_name ] ) ;
				}
			}

			return $comments ;
		}

		// Current user/visitor has pending comments
		// set vary=2 for next time vary lookup
		$this->add_commenter() ;

		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_COMMENTER ) ) {
			LiteSpeed_Cache_Control::set_private( 'existing commenter' ) ;
		}
		else {
			LiteSpeed_Cache_Control::set_nocache( 'existing commenter' ) ;
		}

		return $comments ;
	}

	/**
	 * Check if default vary has a value
	 *
	 * @since 1.1.3
	 * @access private
	 */
	private static function has_vary()
	{
		if ( empty( $_COOKIE[ self::$_vary_name ] ) ) {
			return false ;
		}
		return intval( $_COOKIE[ self::$_vary_name ] ) ;
	}

	/**
	 * Append user status with logged in
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function add_logged_in($logged_in_cookie = false, $expire = false)
	{
		// If the cookie is lost somehow, set it
		if ( ! self::has_vary() ) {
			$_COOKIE[ self::$_vary_name ] = 1 ;

			// save it
			if ( ! $expire ) {
				$expire = time() + 2 * DAY_IN_SECONDS ;
			}
			self::_cookie( $_COOKIE[ self::$_vary_name ], $expire ) ;
			LiteSpeed_Cache_Control::set_nocache( 'adding logged in status' ) ;
		}
	}

	/**
	 * Remove user logged in status
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function remove_logged_in()
	{
		// If the cookie is set, unset it.
		if ( self::has_vary() === 1 ) {
			// remove logged in status from global var
			unset( $_COOKIE[ self::$_vary_name ] ) ;
			// save it
			self::_cookie() ;
			LiteSpeed_Cache_Control::set_nocache( 'removing logged in status' ) ;
		}
	}

	/**
	 * Append user status with commenter
	 *
	 * This is ONLY used when submit a comment
	 *
	 * @since 1.1.6
	 * @access public
	 */
	public function append_commenter()
	{
		$this->add_commenter( true ) ;
	}

	/**
	 * Correct user status with commenter
	 *
	 * @since 1.1.3
	 * @access private
	 * @param  boolean $from_redirect If the request is from redirect page or not
	 */
	private function add_commenter( $from_redirect = false )
	{
		// If the cookie is lost somehow, set it
		if ( self::has_vary() !== 2 ) {
			$_COOKIE[ self::$_vary_name ] = 2 ;
			// save it
			// only set commenter status for current domain path
			self::_cookie( $_COOKIE[ self::$_vary_name ], time() + apply_filters( 'comment_cookie_lifetime', 30000000 ), self::_relative_path( $from_redirect ) ) ;
			LiteSpeed_Cache_Control::set_nocache( 'adding commenter status' ) ;
		}
	}

	/**
	 * Remove user commenter status
	 *
	 * @since 1.1.3
	 * @access private
	 */
	private function remove_commenter()
	{
		if ( self::has_vary() === 2 ) {
			// remove logged in status from global var
			unset( $_COOKIE[ self::$_vary_name ] ) ;
			// save it
			self::_cookie( false, false, self::_relative_path() ) ;
			LiteSpeed_Cache_Control::set_nocache( 'removing commenter status' ) ;
		}
	}

	/**
	 * Generate relative path for cookie
	 *
	 * @since 1.1.3
	 * @access private
	 * @param  boolean $from_redirect If the request is from redirect page or not
	 */
	private static function _relative_path( $from_redirect = false )
	{
		$path = false ;
		$tag = $from_redirect ? 'HTTP_REFERER' : 'SCRIPT_URL' ;
		if ( ! empty( $_SERVER[ $tag ] ) ) {
			$path = parse_url( $_SERVER[ $tag ] ) ;
			$path = ! empty( $path[ 'path' ] ) ? $path[ 'path' ] : false ;
			LiteSpeed_Cache_Log::debug( 'Cookie Vary path: ' . $path ) ;
		}
		return $path ;
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
	 * @param boolean $path False if use wp root path as cookie path
	 */
	private static function _cookie($val = false, $expire = false, $path = false)
	{
		if ( ! $val ) {
			$expire = 1 ;
		}

		setcookie(self::$_vary_name, $val, $expire, $path?: COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.3
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
