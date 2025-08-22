<?php
// phpcs:ignoreFile

namespace LiteSpeed\Lib;

/**
 * Update guest vary
 *
 * @since 4.1
 */
class Guest {

	const CONF_FILE            = '.litespeed_conf.dat';
	const HASH                 = 'hash'; // Not set-able
	const O_CACHE_LOGIN_COOKIE = 'cache-login_cookie';
	const O_DEBUG              = 'debug';
	const O_DEBUG_IPS          = 'debug-ips';
	const O_UTIL_NO_HTTPS_VARY = 'util-no_https_vary';
	const O_GUEST_UAS          = 'guest_uas';
	const O_GUEST_IPS          = 'guest_ips';

	private static $_ip;
	private static $_vary_name = '_lscache_vary'; // this default vary cookie is used for logged in status check
	private $_conf             = false;

	/**
	 * Constructor
	 *
	 * @since 4.1
	 */
	public function __construct() {
		! defined( 'LSCWP_CONTENT_FOLDER' ) && define( 'LSCWP_CONTENT_FOLDER', dirname( __DIR__, 3 ) );
		// Load config
		$this->_conf = file_get_contents( LSCWP_CONTENT_FOLDER . '/' . self::CONF_FILE );
		if ( $this->_conf ) {
			$this->_conf = json_decode( $this->_conf, true );
		}

		if ( ! empty( $this->_conf[ self::O_CACHE_LOGIN_COOKIE ] ) ) {
			self::$_vary_name = $this->_conf[ self::O_CACHE_LOGIN_COOKIE ];
		}
	}

	/**
	 * Update Guest vary
	 *
	 * @since  4.0
	 */
	public function update_guest_vary() {
		// This process must not be cached
		/**
		 * @reference https://wordpress.org/support/topic/soft-404-from-google-search-on-litespeed-cache-guest-vary-php/#post-16838583
		 */
		header( 'X-Robots-Tag: noindex' );
		header( 'X-LiteSpeed-Cache-Control: no-cache' );

		if ( $this->always_guest() ) {
			echo '[]';
			exit;
		}

		// If contains vary already, don't reload to avoid infinite loop when parent page having browser cache
		if ( $this->_conf && self::has_vary() ) {
			echo '[]';
			exit;
		}

		// Send vary cookie
		$vary = 'guest_mode:1';
		if ( $this->_conf && empty( $this->_conf[ self::O_DEBUG ] ) ) {
			$vary = md5( $this->_conf[ self::HASH ] . $vary );
		}

		$expire = time() + 2 * 86400;
		$is_ssl = ! empty( $this->_conf[ self::O_UTIL_NO_HTTPS_VARY ] ) ? false : $this->is_ssl();
		setcookie( self::$_vary_name, $vary, $expire, '/', false, $is_ssl, true );

		// return json
		echo json_encode( array( 'reload' => 'yes' ) );
		exit;
	}

	/**
	 * WP's is_ssl() func
	 *
	 * @since 4.1
	 */
	private function is_ssl() {
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( 'on' === strtolower( $_SERVER['HTTPS'] ) ) {
				return true;
			}

			if ( '1' == $_SERVER['HTTPS'] ) {
				return true;
			}
		} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if default vary has a value
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function has_vary() {
		if ( empty( $_COOKIE[ self::$_vary_name ] ) ) {
			return false;
		}
		return $_COOKIE[ self::$_vary_name ];
	}

	/**
	 * Detect if is a guest visitor or not
	 *
	 * @since  4.0
	 */
	public function always_guest() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		if ( $this->_conf[ self::O_GUEST_UAS ] ) {
			$quoted_uas = array();
			foreach ( $this->_conf[ self::O_GUEST_UAS ] as $v ) {
				$quoted_uas[] = preg_quote( $v, '#' );
			}
			$match = preg_match( '#' . implode( '|', $quoted_uas ) . '#i', $_SERVER['HTTP_USER_AGENT'] );
			if ( $match ) {
				return true;
			}
		}

		if ( $this->ip_access( $this->_conf[ self::O_GUEST_IPS ] ) ) {
			return true;
		}

		return false;
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
		// $uip = explode('.', $_ip);
		// if(empty($uip) || count($uip) != 4) Return false;
		// foreach($ip_list as $key => $ip) $ip_list[$key] = explode('.', trim($ip));
		// foreach($ip_list as $key => $ip) {
		// if(count($ip) != 4) continue;
		// for($i = 0; $i <= 3; $i++) if($ip[$i] == '*') $ip_list[$key][$i] = $uip[$i];
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
		if ( function_exists( 'apache_request_headers' ) ) {
			$apache_headers = apache_request_headers();
			$_ip            = ! empty( $apache_headers['True-Client-IP'] ) ? $apache_headers['True-Client-IP'] : false;
			if ( ! $_ip ) {
				$_ip = ! empty( $apache_headers['X-Forwarded-For'] ) ? $apache_headers['X-Forwarded-For'] : false;
				$_ip = explode( ',', $_ip );
				$_ip = $_ip[0];
			}
		}

		if ( ! $_ip ) {
			$_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false;
		}
		return $_ip;
	}
}
