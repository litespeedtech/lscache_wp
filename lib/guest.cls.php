<?php
namespace LiteSpeed\Lib;

/**
 * Update guest vary
 *
 * @since 4.1
 */
class Guest {
	const CONF_FILE = '.litespeed_conf.dat';
	const HASH 					= 'hash'; // Not set-able
	const O_CACHE_LOGIN_COOKIE 	= 'cache-login_cookie';
	const O_DEBUG 				= 'debug';
	const O_DEBUG_IPS 			= 'debug-ips';
	const O_UTIL_NO_HTTPS_VARY 		= 'util-no_https_vary';

	private static $_ip;
	private static $_vary_name = '_lscache_vary'; // this default vary cookie is used for logged in status check
	private $_conf = false;

	/**
	 * Construtor
	 *
	 * @since 4.1
	 */
	public function __construct() {
		! defined( 'LSCWP_CONTENT_FOLDER' ) && define( 'LSCWP_CONTENT_FOLDER', dirname( dirname( dirname( __DIR__ ) ) ) );error_log(LSCWP_CONTENT_FOLDER);
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
		if ( empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
			return false;
		}

		$match = preg_match( '#Page Speed|Lighthouse|GTmetrix|Google|Pingdom|bot#i', $_SERVER[ 'HTTP_USER_AGENT' ] );
		if ( $match ) {
			return true;
		}

		$ips = [
			'208.70.247.157',
			'172.255.48.130',
			'172.255.48.131',
			'172.255.48.132',
			'172.255.48.133',
			'172.255.48.134',
			'172.255.48.135',
			'172.255.48.136',
			'172.255.48.137',
			'172.255.48.138',
			'172.255.48.139',
			'172.255.48.140',
			'172.255.48.141',
			'172.255.48.142',
			'172.255.48.143',
			'172.255.48.144',
			'172.255.48.145',
			'172.255.48.146',
			'172.255.48.147',
			'52.229.122.240',
			'104.214.72.101',
			'13.66.7.11',
			'13.85.24.83',
			'13.85.24.90',
			'13.85.82.26',
			'40.74.242.253',
			'40.74.243.13',
			'40.74.243.176',
			'104.214.48.247',
			'157.55.189.189',
			'104.214.110.135',
			'70.37.83.240',
			'65.52.36.250',
			'13.78.216.56',
			'52.162.212.163',
			'23.96.34.105',
			'65.52.113.236',
			'172.255.61.34',
			'172.255.61.35',
			'172.255.61.36',
			'172.255.61.37',
			'172.255.61.38',
			'172.255.61.39',
			'172.255.61.40',
			'104.41.2.19',
			'191.235.98.164',
			'191.235.99.221',
			'191.232.194.51',
			'52.237.235.185',
			'52.237.250.73',
			'52.237.236.145',
			'104.211.143.8',
			'104.211.165.53',
			'52.172.14.87',
			'40.83.89.214',
			'52.175.57.81',
			'20.188.63.151',
			'20.52.36.49',
			'52.246.165.153',
			'51.144.102.233',
			'13.76.97.224',
			'102.133.169.66',
			'52.231.199.170',
			'13.53.162.7',
			'40.123.218.94',
		];

		if ( $this->ip_access( $ips ) ) {
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
		if ( function_exists( 'apache_request_headers' ) ) {
			$apache_headers = apache_request_headers();
			$_ip = ! empty( $apache_headers['True-Client-IP'] ) ? $apache_headers['True-Client-IP'] : false;
			if ( ! $_ip ) {
				$_ip = ! empty( $apache_headers['X-Forwarded-For'] ) ? $apache_headers['X-Forwarded-For'] : false;
				$_ip = explode( ',', $_ip );
				$_ip = $_ip[ 0 ];
			}

		}

		if ( ! $_ip ) {
			$_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false;
		}
		return $_ip;
	}


}