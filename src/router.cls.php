<?php
/**
 * The core plugin router class.
 *
 * This generate the valid action.
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Router extends Base {
	const NONCE = 'LSCWP_NONCE';
	const ACTION = 'LSCWP_CTRL';

	const ACTION_SAVE_SETTINGS_NETWORK = 'save-settings-network';
	const ACTION_DB_OPTM = 'db_optm';
	const ACTION_PLACEHOLDER = 'placeholder';
	const ACTION_AVATAR = 'avatar';
	const ACTION_SAVE_SETTINGS = 'save-settings';
	const ACTION_CLOUD = 'cloud';
	const ACTION_CDN_SETUP = 'cdn_setup';
	const ACTION_IMG_OPTM = 'img_optm';
	const ACTION_HEALTH = 'health';
	const ACTION_CRAWLER = 'crawler';
	const ACTION_PURGE = 'purge';
	const ACTION_CONF = 'conf';
	const ACTION_ACTIVATION = 'activation';
	const ACTION_CSS = 'css';
	const ACTION_VPI = 'vpi';
	const ACTION_IMPORT = 'import';
	const ACTION_REPORT = 'report';
	const ACTION_DEBUG2 = 'debug2';
	const ACTION_CDN_CLOUDFLARE = 'CDN\Cloudflare';

	// List all handlers here
	private static $_HANDLERS = array(
		self::ACTION_ACTIVATION,
		self::ACTION_AVATAR,
		self::ACTION_CDN_CLOUDFLARE,
		self::ACTION_CLOUD,
		self::ACTION_CDN_SETUP,
		self::ACTION_CONF,
		self::ACTION_CRAWLER,
		self::ACTION_CSS,
		self::ACTION_VPI,
		self::ACTION_DB_OPTM,
		self::ACTION_DEBUG2,
		self::ACTION_HEALTH,
		self::ACTION_IMG_OPTM,
		self::ACTION_IMPORT,
		self::ACTION_PLACEHOLDER,
		self::ACTION_PURGE,
		self::ACTION_REPORT,
	);

	const TYPE = 'litespeed_type';

	const ITEM_HASH = 'hash';

	private static $_esi_enabled;
	private static $_is_ajax;
	private static $_is_logged_in;
	private static $_ip;
	private static $_action;
	private static $_is_admin_ip;
	private static $_frontend_path;

	/**
	 * Redirect to self to continue operation
	 *
	 * Note: must return when use this func. CLI/Cron call won't die in this func.
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function self_redirect( $action, $type ) {
		if ( defined( 'LITESPEED_CLI' ) || defined( 'DOING_CRON' ) ) {
			Admin_Display::succeed( 'To be continued' ); // Show for CLI
			return;
		}

		// Add i to avoid browser too many redirected warning
		$i = ! empty( $_GET[ 'litespeed_i' ] ) ? $_GET[ 'litespeed_i' ] : 0;
		$i ++;

		$link = Utility::build_url( $action, $type, false, null, array( 'litespeed_i' => $i ) );

		$url = html_entity_decode( $link );
		exit( "<meta http-equiv='refresh' content='0;url=$url'>" );
	}

	/**
	 * Check if can run optimize
	 *
	 * @since  1.3
	 * @since  2.3.1 Relocated from cdn.cls
	 * @access public
	 */
	public function can_optm() {
		$can = true;

		if ( is_user_logged_in() && $this->conf( self::O_OPTM_GUEST_ONLY ) ) {
			$can = false;
		}
		elseif ( is_admin() ) {
			$can = false;
		}
		elseif ( is_feed() ) {
			$can = false;
		}
		elseif ( is_preview() ) {
			$can = false;
		}
		elseif ( self::is_ajax() ) {
			$can = false;
		}

		if ( self::_is_login_page() ) {
			Debug2::debug( '[Router] Optm bypassed: login/reg page' );
			$can = false;
		}

		$can_final = apply_filters( 'litespeed_can_optm', $can );

		if ( $can_final != $can ) {
			Debug2::debug( '[Router] Optm bypassed: filter' );
		}

		return $can_final;
	}

	/**
	 * Check referer page to see if its from admin
	 *
	 * @since 2.4.2.1
	 * @access public
	 */
	public static function from_admin() {
		return ! empty( $_SERVER[ 'HTTP_REFERER' ] ) && strpos( $_SERVER[ 'HTTP_REFERER' ], get_admin_url() ) === 0;
	}

	/**
	 * Check if it can use CDN replacement
	 *
	 * @since  1.2.3
	 * @since  2.3.1 Relocated from cdn.cls
	 * @access public
	 */
	public static function can_cdn() {
		$can = true;

		if ( is_admin() ) {
			if ( ! self::is_ajax() ) {
				Debug2::debug2( '[Router] CDN bypassed: is not ajax call' );
				$can = false;
			}

			if ( self::from_admin() ) {
				Debug2::debug2( '[Router] CDN bypassed: ajax call from admin' );
				$can = false;
			}
		}
		elseif ( is_feed() ) {
			$can = false;
		}
		elseif ( is_preview() ) {
			$can = false;
		}

		/**
		 * Bypass cron to avoid deregister jq notice `Do not deregister the <code>jquery-core</code> script in the administration area.`
		 * @since  2.7.2
		 */
		if ( defined( 'DOING_CRON' ) ) {
			$can = false;
		}

		/**
		 * Bypass login/reg page
		 * @since  1.6
		 */
		if ( self::_is_login_page() ) {
			Debug2::debug( '[Router] CDN bypassed: login/reg page' );
			$can = false;
		}

		/**
		 * Bypass post/page link setting
		 * @since 2.9.8.5
		 */
		$rest_prefix = function_exists( 'rest_get_url_prefix' ) ? rest_get_url_prefix() : apply_filters( 'rest_url_prefix', 'wp-json' );
		if (
			! empty( $_SERVER[ 'REQUEST_URI' ] ) &&
			strpos( $_SERVER[ 'REQUEST_URI' ], $rest_prefix . '/wp/v2/media' ) !== false &&
			isset( $_SERVER[ 'HTTP_REFERER' ] ) && strpos( $_SERVER[ 'HTTP_REFERER' ], 'wp-admin') !== false
		) {
			Debug2::debug( '[Router] CDN bypassed: wp-json on admin page' );
			$can = false;
		}

		$can_final = apply_filters( 'litespeed_can_cdn', $can );

		if ( $can_final != $can ) {
			Debug2::debug( '[Router] CDN bypassed: filter' );
		}

		return $can_final;
	}

	/**
	 * Check if is login page or not
	 *
	 * @since  2.3.1
	 * @access protected
	 */
	protected static function _is_login_page() {
		if ( in_array( $GLOBALS[ 'pagenow' ], array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * UCSS/Crawler role simulator
	 *
	 * @since  1.9.1
	 * @since  3.3 Renamed from `is_crawler_role_simulation`
	 */
	public function is_role_simulation() {
		if( is_admin() ) {
			return;
		}

		if ( empty( $_COOKIE[ 'litespeed_role' ] ) || empty( $_COOKIE[ 'litespeed_hash' ] ) ) {
			return;
		}

		Debug2::debug( '[Router] starting role validation' );

		// Check if is from crawler
		// if ( empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) || strpos( $_SERVER[ 'HTTP_USER_AGENT' ], Crawler::FAST_USER_AGENT ) !== 0 ) {
		// 	Debug2::debug( '[Router] user agent not match' );
		// 	return;
		// }

		// Hash validation
		$hash = self::get_option( self::ITEM_HASH );
		if ( ! $hash || $_COOKIE[ 'litespeed_hash' ] != $hash ) {
			Debug2::debug( '[Router] hash not match ' . $_COOKIE[ 'litespeed_hash' ] . ' != ' . $hash );
			return;
		}

		$role_uid = $_COOKIE[ 'litespeed_role' ];
		Debug2::debug( '[Router] role simulate litespeed_role uid ' . $role_uid );

		wp_set_current_user( $role_uid );
	}

	/**
	 * Get a security hash
	 *
	 * @since  3.3
	 */
	public static function get_hash() {
		// Reuse previous hash if existed
		$hash = self::get_option( self::ITEM_HASH );
		if ( $hash ) {
			return $hash;
		}

		$hash = Str::rrand( 6 );
		self::update_option( self::ITEM_HASH, $hash );
		return $hash;
	}

	/**
	 * Get user role
	 *
	 * @since  1.6.2
	 */
	public static function get_role( $uid = null ) {
		if ( defined( 'LITESPEED_WP_ROLE' ) ) {
			return LITESPEED_WP_ROLE;
		}

		if ( $uid === null ) {
			$uid = get_current_user_id();
		}

		$role = false;
		if ( $uid ) {
			$user = get_userdata( $uid );
			if ( isset( $user->roles ) && is_array( $user->roles ) ) {
				$tmp = array_values( $user->roles );
				$role = array_shift( $tmp );
			}
		}
		Debug2::debug( '[Router] get_role: ' . $role );

		if ( ! $role ) {
			return $role;
			// Guest user
			Debug2::debug( '[Router] role: guest' );

			/**
			 * Fix double login issue
			 * The previous user init refactoring didn't fix this bcos this is in login process and the user role could change
			 * @see  https://github.com/litespeedtech/lscache_wp/commit/69e7bc71d0de5cd58961bae953380b581abdc088
			 * @since  2.9.8 Won't assign const if in login process
			 */
			if ( substr_compare( wp_login_url(), $GLOBALS[ 'pagenow' ], -strlen( $GLOBALS[ 'pagenow' ] ) ) === 0 ) {
				return $role;
			}
		}

		define( 'LITESPEED_WP_ROLE', $role );

		return LITESPEED_WP_ROLE;
	}

	/**
	 * Get frontend path
	 *
	 * @since 1.2.2
	 * @access public
	 * @return boolean
	 */
	public static function frontend_path() { //todo: move to htaccess.cls ?
		if ( ! isset( self::$_frontend_path ) ) {
			$frontend = rtrim( ABSPATH, '/' ); // /home/user/public_html/frontend
			// get home path failed. Trac ticket #37668 (e.g. frontend:/blog backend:/wordpress)
			if ( ! $frontend ) {
				Debug2::debug( '[Router] No ABSPATH, generating from home option' );
				$frontend = parse_url( get_option( 'home' ) );
				$frontend = ! empty( $frontend[ 'path' ] ) ? $frontend[ 'path' ] : '';
				$frontend = $_SERVER[ 'DOCUMENT_ROOT' ] . $frontend;
			}
			$frontend = realpath( $frontend );

			self::$_frontend_path = $frontend;
		}
		return self::$_frontend_path;
	}

	/**
	 * Check if ESI is enabled or not
	 *
	 * @since 1.2.0
	 * @access public
	 * @return boolean
	 */
	public function esi_enabled() {
		if ( ! isset( self::$_esi_enabled ) ) {
			self::$_esi_enabled = defined( 'LITESPEED_ON' ) && $this->conf( self::O_ESI );
			if( ! empty( $_REQUEST[ self::ACTION ] ) ) {
				self::$_esi_enabled = false;
			}
		}
		return self::$_esi_enabled;
	}

	/**
	 * Check if crawler is enabled on server level
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function can_crawl() {
		if ( isset( $_SERVER[ 'X-LSCACHE' ] ) && strpos( $_SERVER[ 'X-LSCACHE' ], 'crawler' ) === false ) {
			return false;
		}

		// CLI will bypass this check as crawler library can always do the 428 check
		if ( defined( 'LITESPEED_CLI' ) ) {
			return true;
		}

		return true;
	}

	/**
	 * Check action
	 *
	 * @since 1.1.0
	 * @access public
	 * @return string
	 */
	public static function get_action() {
		if ( ! isset( self::$_action ) ) {
			self::$_action = false;
			self::cls()->verify_action();
			if ( self::$_action ) {
				defined( 'LSCWP_LOG' ) && Debug2::debug( '[Router] LSCWP_CTRL verified: ' . var_export( self::$_action, true ) );
			}

		}
		return self::$_action;
	}

	/**
	 * Check if is logged in
	 *
	 * @since 1.1.3
	 * @access public
	 * @return boolean
	 */
	public static function is_logged_in() {
		if ( ! isset( self::$_is_logged_in ) ) {
			self::$_is_logged_in = is_user_logged_in();
		}
		return self::$_is_logged_in;
	}

	/**
	 * Check if is ajax call
	 *
	 * @since 1.1.0
	 * @access public
	 * @return boolean
	 */
	public static function is_ajax() {
		if ( ! isset( self::$_is_ajax ) ) {
			self::$_is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		}
		return self::$_is_ajax;
	}

	/**
	 * Check if is admin ip
	 *
	 * @since 1.1.0
	 * @access public
	 * @return boolean
	 */
	public function is_admin_ip() {
		if ( ! isset( self::$_is_admin_ip ) ) {
			$ips = $this->conf( self::O_DEBUG_IPS );

			self::$_is_admin_ip = $this->ip_access( $ips );
		}
		return self::$_is_admin_ip;
	}

	/**
	 * Get type value
	 *
	 * @since 1.6
	 * @access public
	 */
	public static function verify_type() {
		if ( empty( $_REQUEST[ self::TYPE ] ) ) {
			Debug2::debug( '[Router] no type', 2 );
			return false;
		}

		Debug2::debug( '[Router] parsed type: ' . $_REQUEST[ self::TYPE ], 2 );

		return $_REQUEST[ self::TYPE ];
	}

	/**
	 * Check privilege and nonce for the action
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function verify_action() {
		if ( empty( $_REQUEST[ Router::ACTION ] ) ) {
			Debug2::debug2( '[Router] LSCWP_CTRL bypassed empty' );
			return;
		}

		$action = stripslashes($_REQUEST[ Router::ACTION ]);

		if ( ! $action ) {
		    return;
		}

		$_is_public_action = false;

		// Each action must have a valid nonce unless its from admin ip and is public action
		// Validate requests nonce (from admin logged in page or cli)
		if ( ! $this->verify_nonce( $action ) ) {
			// check if it is from admin ip
			if ( ! $this->is_admin_ip() ) {
				Debug2::debug( '[Router] LSCWP_CTRL query string - did not match admin IP: ' . $action );
				return;
			}

			// check if it is public action
			if ( ! in_array( $action, array(
					Core::ACTION_QS_NOCACHE,
					Core::ACTION_QS_PURGE,
					Core::ACTION_QS_PURGE_SINGLE,
					Core::ACTION_QS_SHOW_HEADERS,
					Core::ACTION_QS_PURGE_ALL,
					Core::ACTION_QS_PURGE_EMPTYCACHE,
					) ) ) {
				Debug2::debug( '[Router] LSCWP_CTRL query string - did not match admin IP Actions: ' . $action );
				return;
			}

			if ( apply_filters( 'litespeed_qs_forbidden', false ) ) {
				Debug2::debug( '[Router] LSCWP_CTRL forbidden by hook litespeed_qs_forbidden' );
				return;
			}

			$_is_public_action = true;
		}

		/* Now it is a valid action, lets log and check the permission */
		Debug2::debug( '[Router] LSCWP_CTRL: ' . $action );

		// OK, as we want to do something magic, lets check if its allowed
		$_is_multisite = is_multisite();
		$_is_network_admin = $_is_multisite && is_network_admin();
		$_can_network_option = $_is_network_admin && current_user_can( 'manage_network_options' );
		$_can_option = current_user_can( 'manage_options' );

		switch ( $action ) {
			// Save network settings
			case self::ACTION_SAVE_SETTINGS_NETWORK:
				if ( $_can_network_option ) {
					self::$_action = $action;
				}
				return;

			case Core::ACTION_PURGE_BY:
				if ( defined( 'LITESPEED_ON' ) && ( $_can_network_option || $_can_option || self::is_ajax() ) ) {//here may need more security
					self::$_action = $action;
				}
				return;

			case self::ACTION_DB_OPTM:
				if ( $_can_network_option || $_can_option ) {
					self::$_action = $action;
				}
				return;

			case Core::ACTION_PURGE_EMPTYCACHE:// todo: moved to purge.cls type action
				if ( defined( 'LITESPEED_ON' ) && ( $_can_network_option || ( ! $_is_multisite && $_can_option ) ) ) {
					self::$_action = $action;
				}
				return;

			case Core::ACTION_QS_NOCACHE:
			case Core::ACTION_QS_PURGE:
			case Core::ACTION_QS_PURGE_SINGLE:
			case Core::ACTION_QS_SHOW_HEADERS:
			case Core::ACTION_QS_PURGE_ALL:
			case Core::ACTION_QS_PURGE_EMPTYCACHE:
				if ( defined( 'LITESPEED_ON' ) && ( $_is_public_action || self::is_ajax() ) ) {
					self::$_action = $action;
				}
				return;

			case self::ACTION_PLACEHOLDER:
			case self::ACTION_AVATAR:
			case self::ACTION_IMG_OPTM:
			case self::ACTION_CLOUD:
			case self::ACTION_CDN_SETUP:
			case self::ACTION_CDN_CLOUDFLARE:
			case self::ACTION_CRAWLER:
			case self::ACTION_IMPORT:
			case self::ACTION_REPORT:
			case self::ACTION_CSS:
			case self::ACTION_VPI:
			case self::ACTION_CONF:
			case self::ACTION_ACTIVATION:
			case self::ACTION_HEALTH:
			case self::ACTION_SAVE_SETTINGS: // Save settings
				if ( $_can_option && ! $_is_network_admin ) {
					self::$_action = $action;
				}
				return;

			case self::ACTION_PURGE:
			case self::ACTION_DEBUG2:
				if ( $_can_network_option || $_can_option ) {
					self::$_action = $action;
				}
				return;

			case Core::ACTION_DISMISS:
				/**
				 * Non ajax call can dismiss too
				 * @since  2.9
				 */
				// if ( self::is_ajax() ) {
				self::$_action = $action;
				// }
				return;

			default:
				Debug2::debug( '[Router] LSCWP_CTRL match falied: ' . $action );
				return;
		}

	}

	/**
	 * Verify nonce
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $action
	 * @return bool
	 */
	public function verify_nonce( $action ) {
		if ( ! isset( $_REQUEST[ Router::NONCE ] ) || ! wp_verify_nonce( $_REQUEST[ Router::NONCE ], $action ) ) {
			return false;
		}
		else{
			return true;
		}
	}

	/**
	 * Check if the ip is in the range
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function ip_access( $ip_list ) {
		if ( ! $ip_list ) {
			return false;
		}
		if ( ! isset( self::$_ip ) ) {
			self::$_ip = self::get_ip();
		}

		if ( ! self::$_ip ) {
			return false;
		}
		// $uip = explode('.', $_ip);
		// if(empty($uip) || count($uip) != 4) Return false;
		// foreach($ip_list as $key => $ip) $ip_list[$key] = explode('.', trim($ip));
		// foreach($ip_list as $key => $ip) {
		// 	if(count($ip) != 4) continue;
		// 	for($i = 0; $i <= 3; $i++) if($ip[$i] == '*') $ip_list[$key][$i] = $uip[$i];
		// }
		return in_array( self::$_ip, $ip_list );
	}

	/**
	 * Get client ip
	 *
	 * @since 1.1.0
	 * @since  1.6.5 changed to public
	 * @access public
	 * @return string
	 */
	public static function get_ip() {
		$_ip = '';
		// if ( function_exists( 'apache_request_headers' ) ) {
		// 	$apache_headers = apache_request_headers();
		// 	$_ip = ! empty( $apache_headers['True-Client-IP'] ) ? $apache_headers['True-Client-IP'] : false;
		// 	if ( ! $_ip ) {
		// 		$_ip = ! empty( $apache_headers['X-Forwarded-For'] ) ? $apache_headers['X-Forwarded-For'] : false;
		// 		$_ip = explode( ',', $_ip );
		// 		$_ip = $_ip[ 0 ];
		// 	}

		// }

		if ( ! $_ip ) {
			$_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false;
		}
		return $_ip;
	}

	/**
	 * Check if opcode cache is enabled
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public static function opcache_enabled() {
		return function_exists( 'opcache_reset' ) && ini_get( 'opcache.enable' );
	}

	/**
	 * Handle static files
	 *
	 * @since  3.0
	 */
	public function serve_static() {
		if ( ! empty( $_SERVER[ 'SCRIPT_URI' ] ) ) {
			if ( strpos( $_SERVER[ 'SCRIPT_URI' ], LITESPEED_STATIC_URL . '/' ) !== 0 ) {
				return;
			}
			$path = substr( $_SERVER[ 'SCRIPT_URI' ], strlen( LITESPEED_STATIC_URL . '/' ) );
		}
		elseif ( ! empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
			$static_path = parse_url( LITESPEED_STATIC_URL, PHP_URL_PATH ) . '/';
			if ( strpos( $_SERVER[ 'REQUEST_URI' ], $static_path ) !== 0 ) {
				return;
			}
			$path = substr( parse_url( $_SERVER[ 'REQUEST_URI' ], PHP_URL_PATH ), strlen( $static_path ) );
		}
		else {
			return;
		}

		$path = explode( '/', $path, 2 );

		if ( empty( $path[ 0 ] ) || empty( $path[ 1 ] ) ) {
			return;
		}

		switch ( $path[ 0 ] ) {
			case 'avatar':
				$this->cls( 'Avatar' )->serve_static( $path[ 1 ] );
				break;

			case 'localres':
				$this->cls( 'Localization' )->serve_static( $path[ 1 ] );
				break;

			default :
				break;
		}

	}

	/**
	 * Handle all request actions from main cls
	 *
	 * This is different than other handlers
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler( $cls ) {
		if ( ! in_array( $cls, self::$_HANDLERS ) ) {
			return;
		}

		return $this->cls( $cls )->handler();
	}

}
