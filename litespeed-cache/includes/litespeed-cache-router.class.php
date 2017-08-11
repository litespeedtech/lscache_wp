<?php

/**
 * The core plugin router class.
 *
 * This generate the valid action.
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Router
{
	private static $_instance ;
	private static $_is_enabled ;
	private static $_is_ajax ;
	private static $_is_logged_in ;
	private static $_is_cli ;
	private static $_can_crawl ;
	private static $_ip ;
	private static $_action ;
	private static $_is_admin_ip ;
	private static $_siteurl ;
	private static $_has_whm_msg ;
	private static $_has_msg_ruleconflict ;

	/**
	 * Check if crawler is enabled on server level
	 *
	 * @since 1.1.1
	 * @access public
	 * @return string
	 */
	public static function get_siteurl()
	{
		if ( ! isset( self::$_siteurl ) ) {
			if ( is_multisite() ) {
				$blogID = get_current_blog_id() ;
				self::$_siteurl = get_site_url( $blogID ) ;
			}
			else{
				self::$_siteurl = get_option( 'siteurl' ) ;
			}
		}
		return self::$_siteurl ;
	}

	/**
	 * Check if cache is enabled or not
	 *
	 * @since 1.1.5
	 * @access public
	 * @return boolean
	 */
	public static function cache_enabled()
	{
		if ( ! isset( self::$_is_enabled ) ) {
			if ( ! LiteSpeed_Cache_Config::get_instance()->is_caching_allowed() ) {
				self::$_is_enabled = false ;
			}
			elseif ( is_multisite() && is_network_admin() && current_user_can( 'manage_network_options' ) ) {
				self::$_is_enabled = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED ) ;
			}
			else {
				self::$_is_enabled = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_ENABLED ) ;
			}
		}
		return self::$_is_enabled ;
	}

	/**
	 * Check if has rule conflict notice
	 *
	 * @since 1.1.5
	 * @access public
	 * @return boolean
	 */
	public static function has_msg_ruleconflict()
	{
		if ( ! isset( self::$_has_msg_ruleconflict ) ) {
			self::$_has_msg_ruleconflict = get_option( LiteSpeed_Cache_Admin_Display::DISMISS_MSG ) == LiteSpeed_Cache_Admin_Display::RULECONFLICT_ON ;
		}
		return self::$_has_msg_ruleconflict ;
	}

	/**
	 * Check if has whm notice
	 *
	 * @since 1.1.1
	 * @access public
	 * @return boolean
	 */
	public static function has_whm_msg()
	{
		if ( ! isset( self::$_has_whm_msg ) ) {
			self::$_has_whm_msg = get_transient( LiteSpeed_Cache::WHM_TRANSIENT ) == LiteSpeed_Cache::WHM_TRANSIENT_VAL ;
		}
		return self::$_has_whm_msg ;
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
			self::$_can_crawl = false ;
			if ( isset( $_SERVER['X-LSCACHE'] ) && strpos( $_SERVER['X-LSCACHE'], 'crawler' ) !== false ) {
				self::$_can_crawl = true ;
			}
			// CLI will bypass this check as crawler library can always do the 428 check
			if ( PHP_SAPI == 'cli' ) {
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
			if ( self::$_action && LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push( 'LSCWP_CTRL verified: ' . var_export( self::$_action, true ) ) ;
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
	 * Check if is cli usage
	 *
	 * @since 1.1.0
	 * @access public
	 * @return boolean
	 */
	public static function is_cli()
	{
		if ( ! isset( self::$_is_cli ) ) {
			self::$_is_cli = defined( 'WP_CLI' ) && WP_CLI ;
		}
		return self::$_is_cli ;
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
	 * Check privilege and nonce for the action
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function verify_action()
	{
		if( empty( $_REQUEST[LiteSpeed_Cache::ACTION_KEY] ) ) {
			return ;
		}

		$action = $_REQUEST[LiteSpeed_Cache::ACTION_KEY] ;
		$_is_public_action = false ;

		// Each action must have a valid nonce unless its from admin ip and is public action
		// Validate requests nonce (from admin logged in page or cli)
		if ( ! $this->verify_nonce( $action ) ) {
			// check if it is from admin ip
			if ( ! $this->is_admin_ip() ) {
				LiteSpeed_Cache_Log::debug( 'LSCWP_CTRL query string - did not match admin IP: ' . $action ) ;
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
				LiteSpeed_Cache_Log::debug( 'LSCWP_CTRL query string - did not match admin IP Actions: ' . $action ) ;
				return ;
			}

			$_is_public_action = true ;
		}

		/* Now it is a valid action, lets log and check the permission */
		LiteSpeed_Cache_Log::debug( 'LSCWP_CTRL: ' . $action ) ;

		// OK, as we want to do something magic, lets check if its allowed
		$_is_enabled = self::cache_enabled() ;
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

			case LiteSpeed_Cache::ACTION_PURGE_FRONT:
			case LiteSpeed_Cache::ACTION_PURGE_PAGES:
			case LiteSpeed_Cache::ACTION_PURGE_ERRORS:
			case LiteSpeed_Cache::ACTION_PURGE_ALL:
			case LiteSpeed_Cache::ACTION_PURGE_BY:
				if ( $_is_enabled
						&& ( $_can_network_option || $_can_option || self::is_ajax() ) ) {//here may need more security
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_PURGE_EMPTYCACHE:
				if ( $_is_enabled
						&& ( $_can_network_option
							|| ( ! $_is_multisite && $_can_option ) ) ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_QS_NOCACHE:
			case LiteSpeed_Cache::ACTION_QS_PURGE:
			case LiteSpeed_Cache::ACTION_QS_PURGE_SINGLE:
			case LiteSpeed_Cache::ACTION_QS_SHOW_HEADERS:
			case LiteSpeed_Cache::ACTION_QS_PURGE_ALL:
			case LiteSpeed_Cache::ACTION_QS_PURGE_EMPTYCACHE:
				if ( $_is_enabled && ( $_is_public_action || self::is_ajax() ) ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_CRAWLER_GENERATE_FILE:
			case LiteSpeed_Cache::ACTION_CRAWLER_RESET_POS:
			case LiteSpeed_Cache::ACTION_CRAWLER_CRON_ENABLE:
			case LiteSpeed_Cache::ACTION_DO_CRAWL:
			case LiteSpeed_Cache::ACTION_BLACKLIST_SAVE:
				if ( $_is_enabled && $_can_option && ! $_is_network_admin ) {
					self::$_action = $action ;
				}
				return ;

			case LiteSpeed_Cache::ACTION_DISMISS_WHM:
			case LiteSpeed_Cache::ACTION_DISMISS_EXPIRESDEFAULT:
				if ( self::is_ajax() ) {
					self::$_action = $action ;
				}
				return ;

			default:
				LiteSpeed_Cache_Log::debug( 'LSCWP_CTRL match falied: ' . $action ) ;
				return ;
		}

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
	 * @access private
	 * @return string
	 */
	private function get_ip()
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
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		$cls = get_called_class() ;
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new $cls() ;
		}

		return self::$_instance ;
	}
}
