<?php
/**
 * The core plugin router class.
 *
 * This generate the valid action.
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Router
{
	private static $_instance ;

	private static $_esi_enabled ;
	private static $_is_ajax ;
	private static $_is_logged_in ;
	private static $_can_crawl ;
	private static $_ip ;
	private static $_action ;
	private static $_is_admin_ip ;
	private static $_frontend_path ;

	/**
	 * Check if can run optimize
	 *
	 * @since  1.3
	 * @since  2.3.1 Relocated from cdn.cls
	 * @access public
	 */
	public static function can_optm()
	{
		$can = true ;

		if ( is_admin() ) {
			$can = false ;
		}

		if ( is_feed() ) {
			$can = false ;
		}

		if ( is_preview() ) {
			$can = false ;
		}

		if ( self::is_ajax() ) {
			$can = false ;
		}

		if ( self::_is_login_page() ) {
			LiteSpeed_Cache_Log::debug( '[Router] Optm bypassed: login/reg page' ) ;
			$can = false ;
		}

		$can_final = apply_filters( 'litespeed_can_optm', $can ) ;

		if ( $can_final != $can ) {
			LiteSpeed_Cache_Log::debug( '[Router] Optm bypassed: filter' ) ;
		}

		return $can_final ;
	}

	/**
	 * Check referer page to see if its from admin
	 *
	 * @since 2.4.2.1
	 * @access public
	 */
	public static function from_admin()
	{
		return ! empty( $_SERVER[ 'HTTP_REFERER' ] ) && strpos( $_SERVER[ 'HTTP_REFERER' ], get_admin_url() ) === 0 ;
	}

	/**
	 * Check if it can use CDN replacement
	 *
	 * @since  1.2.3
	 * @since  2.3.1 Relocated from cdn.cls
	 * @access public
	 */
	public static function can_cdn()
	{
		$can = true ;

		if ( is_admin() ) {
			if ( ! self::is_ajax() ) {
				LiteSpeed_Cache_Log::debug2( '[Router] CDN bypassed: is not ajax call' ) ;
				$can = false ;
			}

			if ( self::from_admin() ) {
				LiteSpeed_Cache_Log::debug2( '[Router] CDN bypassed: ajax call from admin' ) ;
				$can = false ;
			}
		}

		if ( is_feed() ) {
			$can = false ;
		}

		if ( is_preview() ) {
			$can = false ;
		}

		/**
		 * Bypass cron to avoid deregister jq notice `Do not deregister the <code>jquery-core</code> script in the administration area.`
		 * @since  2.7.2
		 */
		if ( defined( 'DOING_CRON' ) ) {
			$can = false ;
		}

		/**
		 * Bypass login/reg page
		 * @since  1.6
		 */
		if ( self::_is_login_page() ) {
			LiteSpeed_Cache_Log::debug( '[Router] CDN bypassed: login/reg page' ) ;
			$can = false ;
		}

		/**
		 * Bypass post/page link setting
		 * @since 2.9.8.5
		 */
		if (
			strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-json/wp/v2/media' ) !== false
			&& strpos( $_SERVER[ 'HTTP_REFERER' ], 'wp-admin') !== false
		) {
			LiteSpeed_Cache_Log::debug( '[Router] CDN bypassed: wp-json on admin page' ) ;
			$can = false ;
		}

		$can_final = apply_filters( 'litespeed_can_cdn', $can ) ;

		if ( $can_final != $can ) {
			LiteSpeed_Cache_Log::debug( '[Router] CDN bypassed: filter' ) ;
		}

		return $can_final ;
	}

	/**
	 * Check if is login page or not
	 *
	 * @since  2.3.1
	 * @access protected
	 */
	protected static function _is_login_page()
	{
		if ( in_array( $GLOBALS[ 'pagenow' ], array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			return true ;
		}

		return false ;
	}

	/**
	 * Crawler simulate role
	 *
	 * @since  1.9.1
	 * @access public
	 */
	public function is_crawler_role_simulation()
	{
		if( is_admin() ) {
			return ;
		}

		if ( empty( $_COOKIE[ 'litespeed_role' ] ) || empty( $_COOKIE[ 'litespeed_hash' ] ) ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Router] starting crawler role validation' ) ;

		// Check if is from crawler
		if ( empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) || strpos( $_SERVER[ 'HTTP_USER_AGENT' ], Litespeed_Crawler::FAST_USER_AGENT ) !== 0 ) {
			LiteSpeed_Cache_Log::debug( '[Router] user agent not match' ) ;
			return ;
		}

		// Hash validation
		$hash = get_option( LiteSpeed_Cache_Config::ITEM_CRAWLER_HASH ) ;
		if ( ! $hash || $_COOKIE[ 'litespeed_hash' ] != $hash ) {
			LiteSpeed_Cache_Log::debug( '[Router] crawler hash not match ' . $_COOKIE[ 'litespeed_hash' ] . ' != ' . $hash ) ;
			return ;
		}

		$role_uid = $_COOKIE[ 'litespeed_role' ] ;
		LiteSpeed_Cache_Log::debug( '[Router] role simulate litespeed_role uid ' . $role_uid ) ;

		wp_set_current_user( $role_uid ) ;
	}

	/**
	 * Get user id
	 *
	 * @since  1.6.2
	 */
	public static function get_uid()
	{
		if ( defined( 'LITESPEED_WP_UID' ) ) {
			return LITESPEED_WP_UID ;
		}

		$user = wp_get_current_user() ;
		$user_id = $user->ID ;

		LiteSpeed_Cache_Log::debug( '[Router] get_uid: ' . $user_id, 3 ) ;

		define( 'LITESPEED_WP_UID', $user_id ) ;

		return LITESPEED_WP_UID ;
	}

	/**
	 * Get user role
	 *
	 * @since  1.6.2
	 */
	public static function get_role( $uid = null )
	{
		if ( defined( 'LITESPEED_WP_ROLE' ) ) {
			return LITESPEED_WP_ROLE ;
		}

		if ( $uid === null ) {
			$uid = self::get_uid() ;
		}

		$role = false ;
		if ( $uid ) {
			$user = get_userdata( $uid ) ;
			if ( isset( $user->roles ) && is_array( $user->roles ) ) {
				$tmp = array_values( $user->roles ) ;
				$role = array_shift( $tmp ) ;
			}
		}
		LiteSpeed_Cache_Log::debug( '[Router] get_role: ' . $role ) ;

		if ( ! $role ) {
			return $role ;
			// Guest user
			LiteSpeed_Cache_Log::debug( '[Router] role: guest' ) ;

			/**
			 * Fix double login issue
			 * The previous user init refactoring didn't fix this bcos this is in login process and the user role could change
			 * @see  https://github.com/litespeedtech/lscache_wp/commit/69e7bc71d0de5cd58961bae953380b581abdc088
			 * @since  2.9.8 Won't assign const if in login process
			 */
			if ( substr_compare( wp_login_url(), $GLOBALS[ 'pagenow' ], -strlen( $GLOBALS[ 'pagenow' ] ) ) === 0 ) {
				return $role ;
			}
		}

		define( 'LITESPEED_WP_ROLE', $role ) ;

		return LITESPEED_WP_ROLE ;
	}

	/**
	 * Get frontend path
	 *
	 * @since 1.2.2
	 * @access public
	 * @return boolean
	 */
	public static function frontend_path()
	{
		if ( ! isset( self::$_frontend_path ) ) {
			$frontend = rtrim( ABSPATH, '/' ) ; // /home/user/public_html/frontend
			// get home path failed. Trac ticket #37668 (e.g. frontend:/blog backend:/wordpress)
			if ( ! $frontend ) {
				LiteSpeed_Cache_Log::debug( '[Router] No ABSPATH, generating from home option' ) ;
				$frontend = parse_url( get_option( 'home' ) ) ;
				$frontend = ! empty( $frontend[ 'path' ] ) ? $frontend[ 'path' ] : '' ;
				$frontend = $_SERVER[ 'DOCUMENT_ROOT' ] . $frontend ;
			}
			$frontend = realpath( $frontend ) ;

			self::$_frontend_path = $frontend ;
		}
		return self::$_frontend_path ;
	}

	/**
	 * Check if ESI is enabled or not
	 *
	 * @since 1.2.0
	 * @access public
	 * @return boolean
	 */
	public static function esi_enabled()
	{
		if ( ! isset( self::$_esi_enabled ) ) {
			self::$_esi_enabled = defined( 'LITESPEED_ON' ) && LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_ESI_ENABLE ) ;
		}
		return self::$_esi_enabled ;
	}

	/**
	 * Check if crawler is enabled on server level
	 *
	 * @since 1.1.1
	 * @access public
	 * @return boolean
	 */
	public static function can_crawl()
	{
		if ( ! isset( self::$_can_crawl ) ) {
			self::$_can_crawl = true ;

			if ( isset( $_SERVER['X-LSCACHE'] ) && strpos( $_SERVER['X-LSCACHE'], 'crawler' ) === false ) {
				self::$_can_crawl = false ;
			}

			// CLI will bypass this check as crawler library can always do the 428 check
			if ( defined( 'LITESPEED_CLI' ) ) {
				self::$_can_crawl = true ;
			}

		}

		return self::$_can_crawl ;
	}

	/**
	 * Check action
	 *
	 * @since 1.1.0
	 * @access public
	 * @return string
	 */
	public static function get_action()
	{
		if ( ! isset( self::$_action ) ) {
            self::$_action = false;
			self::get_instance()->verify_action() ;
			if ( self::$_action ) {
				defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Router] LSCWP_CTRL verified: ' . var_export( self::$_action, true ) ) ;
			}

		}
		return self::$_action ;
	}

	/**
	 * Check if is logged in
	 *
	 * @since 1.1.3
	 * @access public
	 * @return boolean
	 */
	public static function is_logged_in()
	{
		if ( ! isset( self::$_is_logged_in ) ) {
			self::$_is_logged_in = is_user_logged_in() ;
		}
		return self::$_is_logged_in ;
	}

	/**
	 * Check if is ajax call
	 *
	 * @since 1.1.0
	 * @access public
	 * @return boolean
	 */
	public static function is_ajax()
	{
		if ( ! isset( self::$_is_ajax ) ) {
			self::$_is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX ;
		}
		return self::$_is_ajax ;
	}

	/**
	 * Check if is admin ip
	 *
	 * @since 1.1.0
	 * @access public
	 * @return boolean
	 */
	public static function is_admin_ip()
	{
		if ( ! isset( self::$_is_admin_ip ) ) {
			$ips = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_ADMIN_IPS ) ;

			self::$_is_admin_ip = self::get_instance()->ip_access( $ips ) ;
		}
		return self::$_is_admin_ip ;
	}

	/**
	 * Create type value for url
	 *
	 * @since 1.6
	 * @access public
	 */
	public static function build_type( $val )
	{
		return array( 'type' => $val ) ;
	}

	/**
	 * Get type value
	 *
	 * @since 1.6
	 * @access public
	 */
	public static function verify_type()
	{
		if ( empty( $_REQUEST[ 'type' ] ) ) {
			LiteSpeed_Cache_Log::debug( '[Router] no type', 2 ) ;
			return false ;
		}

		LiteSpeed_Cache_Log::debug( '[Router] parsed type: ' . $_REQUEST[ 'type' ], 2 ) ;

		return $_REQUEST[ 'type' ] ;
	}

	/**
	 * Check privilege and nonce for the action
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function verify_action()
	{
		if ( empty( $_REQUEST[ LiteSpeed_Cache::ACTION_KEY ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Router] LSCWP_CTRL bypassed empty' ) ;
			return ;
		}

		$action = $_REQUEST[ LiteSpeed_Cache::ACTION_KEY ] ;
		$_is_public_action = false ;

		// Each action must have a valid nonce unless its from admin ip and is public action
		// Validate requests nonce (from admin logged in page or cli)
		if ( ! $this->verify_nonce( $action ) && ! $this->_verify_sapi_passive( $action ) && ! $this->_verify_sapi_aggressive( $action ) ) {
			// check if it is from admin ip
			if ( ! $this->is_admin_ip() ) {
				LiteSpeed_Cache_Log::debug( '[Router] LSCWP_CTRL query string - did not match admin IP: ' . $action ) ;
				return ;
			}

			// check if it is public action
			if ( ! in_array( $action, array(
					LiteSpeed_Cache::ACTION_QS_NOCACHE,
					LiteSpeed_Cache::ACTION_QS_PURGE,
					LiteSpeed_Cache::ACTION_QS_PURGE_SINGLE,
					LiteSpeed_Cache::ACTION_QS_SHOW_HEADERS,
					LiteSpeed_Cache::ACTION_QS_PURGE_ALL,
					LiteSpeed_Cache::ACTION_QS_PURGE_EMPTYCACHE,
					) ) ) {
				LiteSpeed_Cache_Log::debug( '[Router] LSCWP_CTRL query string - did not match admin IP Actions: ' . $action ) ;
				return ;
			}

			$_is_public_action = true ;
		}

		/* Now it is a valid action, lets log and check the permission */
		LiteSpeed_Cache_Log::debug( '[Router] LSCWP_CTRL: ' . $action ) ;

		// OK, as we want to do something magic, lets check if its allowed
		$_is_multisite = is_multisite() ;
		$_is_network_admin = $_is_multisite && is_network_admin() ;
		$_can_network_option = $_is_network_admin && current_user_can( 'manage_network_options' ) ;
		$_can_option = current_user_can( 'manage_options' ) ;

		switch ( $action ) {
			// Save htaccess
			case LiteSpeed_Cache::ACTION_SAVE_HTACCESS:
				if ( ( ! $_is_multisite && $_can_option ) || $_can_network_option ) {
					self::$_action = $action ;
				}
				return ;

			// Save network settings
			case LiteSpeed_Cache::ACTION_SAVE_SETTINGS_NETWORK:
				if ( $_can_network_option ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_PURGE_BY:
			case LiteSpeed_Cache::ACTION_FRONT_EXCLUDE:
				if ( defined( 'LITESPEED_ON' ) && ( $_can_network_option || $_can_option || self::is_ajax() ) ) {//here may need more security
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_DB_OPTIMIZE:
				if ( $_can_network_option || $_can_option ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_PURGE_EMPTYCACHE:// todo: moved to purge.cls type action
				if ( defined( 'LITESPEED_ON' ) && ( $_can_network_option || ( ! $_is_multisite && $_can_option ) ) ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_QS_NOCACHE:
			case LiteSpeed_Cache::ACTION_QS_PURGE:
			case LiteSpeed_Cache::ACTION_QS_PURGE_SINGLE:
			case LiteSpeed_Cache::ACTION_QS_SHOW_HEADERS:
			case LiteSpeed_Cache::ACTION_QS_PURGE_ALL:
			case LiteSpeed_Cache::ACTION_QS_PURGE_EMPTYCACHE:
				if ( defined( 'LITESPEED_ON' ) && ( $_is_public_action || self::is_ajax() ) ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_CRAWLER_GENERATE_FILE:
			case LiteSpeed_Cache::ACTION_CRAWLER_RESET_POS:
			case LiteSpeed_Cache::ACTION_CRAWLER_CRON_ENABLE:
			case LiteSpeed_Cache::ACTION_DO_CRAWL:
			case LiteSpeed_Cache::ACTION_BLACKLIST_SAVE:
			case LiteSpeed_Cache::ACTION_PURGE:
			case LiteSpeed_Cache::ACTION_MEDIA:
			case LiteSpeed_Cache::ACTION_IMG_OPTM:
			case LiteSpeed_Cache::ACTION_IAPI:
			case LiteSpeed_Cache::ACTION_CDN_CLOUDFLARE:
			case LiteSpeed_Cache::ACTION_CDN_QUIC:
			case LiteSpeed_Cache::ACTION_IMPORT:
			case LiteSpeed_Cache::ACTION_REPORT:
			case LiteSpeed_Cache::ACTION_CSS:
			case LiteSpeed_Cache::ACTION_CFG:
			case LiteSpeed_Cache::ACTION_ACTIVATION:
			case LiteSpeed_Cache::ACTION_UTIL:
				if ( $_can_option && ! $_is_network_admin ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_LOG:
				if ( $_can_network_option || $_can_option ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_SAPI_PASSIVE_CALLBACK :
			case LiteSpeed_Cache::ACTION_SAPI_AGGRESSIVE_CALLBACK :
				self::$_action = $action ;
				return ;

			case LiteSpeed_Cache::ACTION_DISMISS:
				/**
				 * Non ajax call can dismiss too
				 * @since  2.9
				 */
				// if ( self::is_ajax() ) {
				self::$_action = $action ;
				// }
				return ;

			default:
				LiteSpeed_Cache_Log::debug( '[Router] LSCWP_CTRL match falied: ' . $action ) ;
				return ;
		}

	}

	/**
	 * Verify sapi passive callback
	 *
	 * @since 1.5
	 * @access private
	 * @param  string $action
	 * @return bool
	 */
	private function _verify_sapi_passive( $action )
	{
		if ( $action === LiteSpeed_Cache::ACTION_SAPI_PASSIVE_CALLBACK ) {
			if ( LiteSpeed_Cache_Admin_API::sapi_valiate_passive_callback() ) {
				return true ;
			}
			exit( 'wrong passive callback' ) ;
		}

		return false ;
	}

	/**
	 * Verify sapi aggressive callback
	 *
	 * @since 1.6
	 * @access private
	 * @param  string $action
	 * @return bool
	 */
	private function _verify_sapi_aggressive( $action )
	{
		if ( $action === LiteSpeed_Cache::ACTION_SAPI_AGGRESSIVE_CALLBACK ) {
			if ( LiteSpeed_Cache_Admin_API::sapi_validate_aggressive_callback() ) {
				return true ;
			}

			exit( 'wrong aggressive callback' ) ;
		}
		return false ;
	}

	/**
	 * Verify nonce
	 *
	 * @since 1.1.0
	 * @access private
	 * @param  string $action
	 * @return bool
	 */
	private function verify_nonce( $action )
	{
		if ( ! isset( $_REQUEST[LiteSpeed_Cache::NONCE_NAME] ) || ! wp_verify_nonce( $_REQUEST[LiteSpeed_Cache::NONCE_NAME], $action ) ) {
			return false ;
		}
		else{
			return true ;
		}
	}

	/**
	 * Check if the ip is in the range
	 *
	 * @since 1.1.0
	 * @access private
	 * @param  string $ip_list IP list
	 * @return bool
	 */
	private function ip_access( $ip_list )
	{
		if ( ! $ip_list ) {
			return false ;
		}
		if ( ! isset( self::$_ip ) ) {
			self::$_ip = $this->get_ip() ;
		}
		// $uip = explode('.', $_ip) ;
		// if(empty($uip) || count($uip) != 4) Return false ;
		if ( ! is_array( $ip_list ) ) {
			$ip_list = explode( "\n", $ip_list ) ;
		}
		// foreach($ip_list as $key => $ip) $ip_list[$key] = explode('.', trim($ip)) ;
		// foreach($ip_list as $key => $ip) {
		// 	if(count($ip) != 4) continue ;
		// 	for($i = 0 ; $i <= 3 ; $i++) if($ip[$i] == '*') $ip_list[$key][$i] = $uip[$i] ;
		// }
		return in_array( self::$_ip, $ip_list ) ;
	}

	/**
	 * Get client ip
	 *
	 * @since 1.1.0
	 * @since  1.6.5 changed to public
	 * @access public
	 * @return string
	 */
	public static function get_ip()
	{
		$_ip = '' ;
		if ( function_exists( 'apache_request_headers' ) ) {
			$apache_headers = apache_request_headers() ;
			$_ip = ! empty( $apache_headers['True-Client-IP'] ) ? $apache_headers['True-Client-IP'] : false ;
			if ( ! $_ip ) {
				$_ip = ! empty( $apache_headers['X-Forwarded-For'] ) ? $apache_headers['X-Forwarded-For'] : false ;
				$_ip = explode( ", ", $_ip ) ;
				$_ip = array_shift( $_ip ) ;
			}

			if ( ! $_ip ) {
				$_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false ;
			}
		}
		return $_ip ;
	}

	/**
	 * Check if opcode cache is enabled
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public static function opcache_enabled()
	{
		return function_exists( 'opcache_reset' ) && ini_get( 'opcache.enable' ) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
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
