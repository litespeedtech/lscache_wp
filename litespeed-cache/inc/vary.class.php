<?php
/**
 * The plugin vary class to manage X-LiteSpeed-Vary
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Vary
{
	private static $_instance ;

	const X_HEADER = 'X-LiteSpeed-Vary' ;

	private static $_vary_name = '_lscache_vary' ; // this default vary cookie is used for logged in status check
	private static $_vary_cookies = array() ; // vary header only!
	private static $_default_vary_val = array() ;

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
			// If not esi, check cache logged-in user setting
			if ( ! LiteSpeed_Cache_Router::esi_enabled() ) {
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
				// Need to make sure vary is using group id
				LiteSpeed_Cache_Control::init_cacheable() ;
			}

			// register logout hook to clear login status
			add_action( 'clear_auth_cookie', array( $this, 'remove_logged_in' ) ) ;

		}
		else {
			// Set vary cookie for logging in user, otherwise the user will hit public with vary=0 (guest version)
			add_action( 'set_logged_in_cookie', array( $this, 'add_logged_in' ), 10, 4 ) ;
			add_action( 'wp_login', 'LiteSpeed_Cache_Purge::purge_on_logout' ) ;

			LiteSpeed_Cache_Control::init_cacheable() ;

			// Check `login page` cacheable setting because they don't go through main WP logic
			add_action( 'login_init', 'LiteSpeed_Cache_Tag::check_login_cacheable', 5 ) ;

		}

		// Add comment list ESI
		add_filter('comments_array', array( $this, 'check_commenter' ) ) ;

		// Set vary cookie for commenter.
		add_action('set_comment_cookies', array( $this, 'append_commenter' ) ) ;

		/**
		 * Don't change for REST call because they don't carry on user info usually
		 * @since 1.6.7
		 */
		add_action( 'rest_api_init', function(){
			LiteSpeed_Cache_Log::debug( '[Vary] Rest API init disabled vary change' ) ;
			add_filter( 'litespeed_can_change_vary', '__return_false' ) ;
		} ) ;

		/******** Below to the end is only for cookie name setting check ********/
		// Get specific cookie name
		$db_cookie = false ;
		if ( is_multisite() ) {
			$options = LiteSpeed_Cache_Config::get_instance()->get_site_options() ;
			$db_cookie = $options[ LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE ] ;
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
		/**
		 * Hook to bypass pending comment check for comment related plugins compatibility
		 * @since 2.9.5
		 */
		if ( apply_filters( 'litespeed_vary_check_commenter_pending', true ) ) {
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

				// Remove commenter prefilled info if exists, for public cache
				foreach( $_COOKIE as $cookie_name => $cookie_value ) {
					if ( strlen( $cookie_name ) >= 15 && strpos( $cookie_name, 'comment_author_' ) === 0 ) {
						unset( $_COOKIE[ $cookie_name ] ) ;
					}
				}

				return $comments ;
			}
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
	 * @access public
	 */
	public static function has_vary()
	{
		if ( empty( $_COOKIE[ self::$_vary_name ] ) ) {
			return false ;
		}
		return $_COOKIE[ self::$_vary_name ] ;
	}

	/**
	 * Append user status with logged in
	 *
	 * @since 1.1.3
	 * @since 1.6.2 Removed static referral
	 * @access public
	 */
	public function add_logged_in( $logged_in_cookie = false, $expire = false, $expiration = false, $uid = false )
	{
		LiteSpeed_Cache_Log::debug( '[Vary] add_logged_in' ) ;

		/**
		 * NOTE: Run before `$this->_update_default_vary()` to make vary changeable
		 * @since  2.2.2
		 */
		self::can_ajax_vary() ;

		// If the cookie is lost somehow, set it
		$this->_update_default_vary( $uid, $expire ) ;
	}

	/**
	 * Remove user logged in status
	 *
	 * @since 1.1.3
	 * @since 1.6.2 Removed static referral
	 * @access public
	 */
	public function remove_logged_in()
	{
		LiteSpeed_Cache_Log::debug( '[Vary] remove_logged_in' ) ;

		/**
		 * NOTE: Run before `$this->_update_default_vary()` to make vary changeable
		 * @since  2.2.2
		 */
		self::can_ajax_vary() ;

		// Force update vary to remove login status
		$this->_update_default_vary( -1 ) ;
	}

	/**
	 * Allow vary can be changed for ajax calls
	 *
	 * @since 2.2.2
	 * @since 2.6 Changed to static
	 * @access public
	 */
	public static function can_ajax_vary()
	{
		LiteSpeed_Cache_Log::debug( '[Vary] litespeed_ajax_vary -> true' ) ;
		add_filter( 'litespeed_ajax_vary', '__return_true' ) ;
	}

	/**
	 * Check if can change default vary
	 *
	 * @since 1.6.2
	 * @access private
	 */
	private function can_change_vary()
	{
		// Don't change for ajax due to ajax not sending webp header
		/**
		 * Added `litespeed_ajax_vary` hook for 3rd party to set vary when doing ajax call ( Login With Ajax )
		 * @since  1.6.6
		 */
		if ( LiteSpeed_Cache_Router::is_ajax() && ! apply_filters( 'litespeed_ajax_vary', false ) ) {
			LiteSpeed_Cache_Log::debug( '[Vary] can_change_vary bypassed due to ajax call' ) ;
			return false ;
		}

		/**
		 * POST request can set vary to fix #820789 login "loop" guest cache issue
		 * @since 1.6.5
		 */
		if ( $_SERVER["REQUEST_METHOD"] !== 'GET' && $_SERVER["REQUEST_METHOD"] !== 'POST' ) {
			LiteSpeed_Cache_Log::debug( '[Vary] can_change_vary bypassed due to method not get/post' ) ;
			return false ;
		}

		/**
		 * Disable vary change if is from crawler
		 * @since  2.9.8 To enable woocommerce cart not empty warm up (@Taba)
		 */
		if ( ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) && strpos( $_SERVER[ 'HTTP_USER_AGENT' ], Litespeed_Crawler::FAST_USER_AGENT ) === 0 ) {
			LiteSpeed_Cache_Log::debug( '[Vary] can_change_vary bypassed due to crawler' ) ;
			return false ;
		}

		if ( ! apply_filters( 'litespeed_can_change_vary', true ) ) {
			LiteSpeed_Cache_Log::debug( '[Vary] can_change_vary bypassed due to litespeed_can_change_vary hook' ) ;
			return false ;
		}

		return true ;
	}

	/**
	 * Update default vary
	 *
	 * @since 1.6.2
	 * @since  1.6.6.1 Add ran check to make it only run once ( No run multiple times due to login process doesn't have valid uid from router::get_uid )
	 * @access private
	 */
	private function _update_default_vary( $uid = false, $expire = false )
	{
		// Make sure header output only run once
		if ( ! defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;
		}
		else {
			LiteSpeed_Cache_Log::debug2( "[Vary] _update_default_vary bypassed due to run already" ) ;
			return ;
		}

		// If the cookie is lost somehow, set it
		$vary = $this->finalize_default_vary( $uid ) ;
		$current_vary = self::has_vary() ;
		if ( $current_vary !== $vary && $current_vary !== 'commenter' && $this->can_change_vary() ) {
			// $_COOKIE[ self::$_vary_name ] = $vary ; // not needed

			// save it
			if ( ! $expire ) {
				$expire = time() + 2 * DAY_IN_SECONDS ;
			}
			self::_cookie( $vary, $expire ) ;
			LiteSpeed_Cache_Log::debug( "[Vary] set_cookie ---> $vary" ) ;
			LiteSpeed_Cache_Control::set_nocache( 'changing default vary' . " $current_vary => $vary" ) ;
		}
	}

	/**
	 * Get vary name
	 *
	 * @since 1.9.1
	 * @access public
	 */
	public function get_vary_name()
	{
		return self::$_vary_name ;
	}

	/**
	 * Finalize default vary
	 *
	 *  Get user vary tag based on admin_bar & role
	 *
	 * NOTE: Login process will also call this because it does not call wp hook as normal page loading
	 *
	 * @since 1.6.2
	 * @access public
	 */
	public function finalize_default_vary( $uid = false )
	{
		$vary = self::$_default_vary_val ;

		if ( ! $uid ) {
			$uid = LiteSpeed_Cache_Router::get_uid() ;
		}
		else {
			LiteSpeed_Cache_Log::debug( '[Vary] uid: ' . $uid ) ;
		}

		// get user's group id
		$role = LiteSpeed_Cache_Router::get_role( $uid ) ;

		if ( $uid > 0 && $role ) {
			$vary[ 'logged-in' ] = 1 ;

			// parse role group from settings
			if ( $role_group = LiteSpeed_Cache_Config::get_instance()->in_vary_group( $role ) ) {
				$vary[ 'role' ] = $role_group ;
			}

			// Get admin bar set
			// see @_get_admin_bar_pref()
			$pref = get_user_option( 'show_admin_bar_front', $uid ) ;
			LiteSpeed_Cache_Log::debug2( '[Vary] show_admin_bar_front: ' . $pref ) ;
			$admin_bar = $pref === false || $pref === 'true' ;

			if ( $admin_bar ) {
				$vary[ 'admin_bar' ] = 1 ;
				LiteSpeed_Cache_Log::debug2( '[Vary] admin bar : true' ) ;
			}

		}
		else {
			// Guest user
			LiteSpeed_Cache_Log::debug( '[Vary] role id: failed, guest' ) ;

		}

		/**
		 * Add filter
		 * @since 1.6 Added for Role Excludes for optimization cls
		 * @since 1.6.2 Hooked to webp
		 */
		$vary = apply_filters( 'litespeed_vary', $vary ) ;

		if ( ! $vary ) {
			return false ;
		}

		ksort( $vary ) ;
		$res = array() ;
		foreach ( $vary as $key => $val ) {
			$res[] = $key . ':' . $val ;
		}

		$res = implode( ';', $res ) ;
		if ( defined( 'LSCWP_LOG' ) ) {
			return $res ;
		}
		// Encrypt in production
		return md5( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::HASH ) . $res ) ;

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
		if ( self::has_vary() !== 'commenter' ) {
			// $_COOKIE[ self::$_vary_name ] = 'commenter' ; // not needed

			// save it
			// only set commenter status for current domain path
			self::_cookie( 'commenter', time() + apply_filters( 'comment_cookie_lifetime', 30000000 ), self::_relative_path( $from_redirect ) ) ;
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
		if ( self::has_vary() === 'commenter' ) {
			// remove logged in status from global var
			// unset( $_COOKIE[ self::$_vary_name ] ) ; // not needed

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
			LiteSpeed_Cache_Log::debug( '[Vary] Cookie Vary path: ' . $path ) ;
		}
		return $path ;
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
	public static function finalize()
	{
		return self::get_instance()->_finalize() ;

	}

	private function _finalize()
	{
		// Finalize default vary
		$this->_update_default_vary() ;

		/**
		 * Non caccheable page can still set vary ( for logged in process )
		 * @since  1.6.6.1
		 */
		// if ( ! LiteSpeed_Cache_Control::is_cacheable() ) {
		// 	LiteSpeed_Cache_Log::debug2( 'Vary: bypass finalize due to not cacheable' ) ;
		// 	return false;
		// }

		$tp_cookies = $this->_format_vary_cookies() ;
		global $post ;
		if ( ! empty($post->post_password) ) {
			if ( isset($_COOKIE['wp-postpass_' . COOKIEHASH]) ) {
				LiteSpeed_Cache_Log::debug( '[Vary] finalize bypassed due to password protected vary ' ) ;
				// If user has password cookie, do not cache
				LiteSpeed_Cache_Control::set_nocache('password protected vary') ;
				return ;
			}

			$tp_cookies[] = 'cookie=wp-postpass_' . COOKIEHASH ;
		}

		if ( empty($tp_cookies) ) {
			LiteSpeed_Cache_Log::debug2( '[Vary] no custimzed vary ' ) ;
			return ;
		}

		return self::X_HEADER . ': ' . implode(',', $tp_cookies) ;

	}

	/**
	 * Gets vary cookies that are already added for the current page.
	 *
	 * @since 1.0.13
	 * @access private
	 * @return array An array of all vary cookies currently added.
	 */
	private function _format_vary_cookies()
	{
		/**
		 * To add new varys, use hook `API::filter_vary_cookies()` before here
		 */
		do_action( 'litespeed_vary_add' ) ;

		/**
		 * Give a filter to manipulate vary
		 * @since 2.7.1
		 */
		$cookies = apply_filters( 'litespeed_vary_cookies', self::$_vary_cookies ) ;
		if ( $cookies !== self::$_vary_cookies ) {
			LiteSpeed_Cache_Log::debug( '[Vary] vary changed by filter [Old] ' . var_export( self::$_vary_cookies, true ) . ' [New] ' . var_export( $cookies, true )  ) ;
		}

		if ( ! empty( $cookies ) ) {
			$cookies = array_filter( array_unique( $cookies ) ) ;
		}

		if ( empty($cookies) ) {
			return false ;
		}

		foreach ($cookies as $key => $val) {
			$cookies[$key] = 'cookie=' . $val ;
		}

		return $cookies ;
	}

	/**
	 * Adds vary to the list of vary cookies for the current page.
	 * This is to add a new vary cookie
	 *
	 * @since 1.0.13
	 * @deprecated 2.7.1 Use filter `litespeed_vary_cookies` instead.
	 * @access public
	 * @param mixed $vary A string or array of vary cookies to add to the current list.
	 */
	public static function add( $vary )
	{
		if ( ! is_array( $vary ) ) {
			$vary = array( $vary ) ;
		}

		error_log( 'Deprecated since LSCWP 2.7.1! [Vary] Add new vary ' . var_export( $vary, true ) ) ;

		self::$_vary_cookies = array_merge(self::$_vary_cookies, $vary) ;
	}

	/**
	 * Append child value to default vary
	 *
	 * @since 2.6
	 * @access public
	 */
	public static function append( $name, $val )
	{
		self::$_default_vary_val[ $name ] = $val ;
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

		/**
		 * Add HTTPS bypass in case clients use both HTTP and HTTPS version of site
		 * @since 1.7
		 */
		$is_ssl = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_USE_HTTP_FOR_HTTPS_VARY ) ? false : is_ssl() ;

		setcookie(self::$_vary_name, $val, $expire, $path?: COOKIEPATH, COOKIE_DOMAIN, $is_ssl, true) ;
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
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
