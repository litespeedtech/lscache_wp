<?php
/**
 * Guest vary handler for LiteSpeed Cache.
 *
 * NOTE: This file is loaded directly without WordPress, so WP functions are NOT available.
 *
 * @package LiteSpeed
 * @since   4.1
 */

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

	/**
	 * Client IP address.
	 *
	 * @var string
	 */
	private static $_ip;

	/**
	 * Vary cookie name.
	 *
	 * @var string
	 */
	private static $_vary_name = '_lscache_vary';

	/**
	 * Configuration array.
	 *
	 * @var array|false
	 */
	private $_conf = false;

	/**
	 * Guest Mode lists cache.
	 *
	 * @var array
	 */
	private $_gm_lists = [
		'ips' => null,
		'uas' => null,
	];

	/**
	 * Constructor
	 *
	 * @since 4.1
	 */
	public function __construct() {
		! defined( 'LSCWP_CONTENT_FOLDER' ) && define( 'LSCWP_CONTENT_FOLDER', dirname( __DIR__, 3 ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- No WP available
		$this->_conf = file_get_contents( LSCWP_CONTENT_FOLDER . '/' . self::CONF_FILE );
		if ( $this->_conf ) {
			$this->_conf = json_decode( $this->_conf, true );
		}

		if ( ! empty( $this->_conf[ self::O_CACHE_LOGIN_COOKIE ] ) ) {
			self::$_vary_name = $this->_conf[ self::O_CACHE_LOGIN_COOKIE ];
		}
	}

	/**
	 * Update Guest vary.
	 *
	 * @since  4.0
	 * @return void
	 */
	public function update_guest_vary() {
		// This process must not be cached
		// @reference https://wordpress.org/support/topic/soft-404-from-google-search-on-litespeed-cache-guest-vary-php/#post-16838583
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

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- No WP available
		echo json_encode( [ 'reload' => 'yes' ] );
		exit;
	}

	/**
	 * WP's is_ssl() func
	 *
	 * @since 4.1
	 * @return bool
	 */
	private function is_ssl() {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- No WP available
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( 'on' === strtolower( $_SERVER['HTTPS'] ) ) {
				return true;
			}

			if ( '1' === $_SERVER['HTTPS'] ) {
				return true;
			}
		} elseif ( isset( $_SERVER['SERVER_PORT'] ) && '443' === $_SERVER['SERVER_PORT'] ) {
			return true;
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput
		return false;
	}

	/**
	 * Check if default vary has a value
	 *
	 * @since 1.1.3
	 * @access public
	 * @return string|false
	 */
	public static function has_vary() {
		if ( empty( $_COOKIE[ self::$_vary_name ] ) ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- No WP available
		return $_COOKIE[ self::$_vary_name ];
	}

	/**
	 * Load Guest Mode list from file.
	 *
	 * Priority: cloud synced file > plugin data file
	 *
	 * @since 7.7
	 * @param string $type 'ips' or 'uas'.
	 * @return array
	 */
	private function _load_gm_list( $type ) {
		if ( null !== $this->_gm_lists[ $type ] ) {
			return $this->_gm_lists[ $type ];
		}

		$this->_gm_lists[ $type ] = [];
		$filename                 = 'gm_' . $type . '.txt';

		// Try cloud synced file first, then fallback to plugin data file
		$files = [
			LSCWP_CONTENT_FOLDER . '/litespeed/cloud/' . $filename,
			dirname( __DIR__ ) . '/data/' . $filename,
		];

		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- No WP available
				$content = file_get_contents( $file );
				if ( $content ) {
					$this->_gm_lists[ $type ] = array_filter( array_map( 'trim', explode( "\n", $content ) ) );
					break;
				}
			}
		}

		return $this->_gm_lists[ $type ];
	}

	/**
	 * Detect if is a guest visitor or not
	 *
	 * @since  4.0
	 * @return bool
	 */
	public function always_guest() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$guest_uas = $this->_load_gm_list( 'uas' );
		if ( $guest_uas ) {
			$quoted_uas = [];
			foreach ( $guest_uas as $v ) {
				$quoted_uas[] = preg_quote( $v, '#' );
			}
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- No WP available
			$match = preg_match( '#' . implode( '|', $quoted_uas ) . '#i', $_SERVER['HTTP_USER_AGENT'] );
			if ( $match ) {
				return true;
			}
		}

		$guest_ips = $this->_load_gm_list( 'ips' );
		if ( $this->ip_access( $guest_ips ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the ip is in the range (supports CIDR notation)
	 *
	 * @since 1.1.0
	 * @since 7.7 Added CIDR support
	 * @access public
	 * @param array $ip_list List of IPs or CIDRs.
	 * @return bool
	 */
	public function ip_access( $ip_list ) {
		if ( ! $ip_list ) {
			return false;
		}
		if ( ! isset( self::$_ip ) ) {
			self::$_ip = self::get_ip();
		}

		foreach ( $ip_list as $ip_entry ) {
			$ip_entry = trim( $ip_entry );
			// Check CIDR format
			if ( strpos( $ip_entry, '/' ) !== false ) {
				if ( $this->_ip_in_cidr( self::$_ip, $ip_entry ) ) {
					return true;
				}
			} elseif ( self::$_ip === $ip_entry ) {
				// Exact match
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if IP is within CIDR range
	 *
	 * @since 7.7
	 * @access private
	 * @param string $ip   IP address to check.
	 * @param string $cidr CIDR notation (e.g., 192.168.1.0/24).
	 * @return bool
	 */
	private function _ip_in_cidr( $ip, $cidr ) {
		list( $subnet, $mask ) = explode( '/', $cidr, 2 );

		// Mask must be numeric and > 0
		if ( ! is_numeric( $mask ) || $mask <= 0 ) {
			return false;
		}
		$mask = (int) $mask;

		// Determine IP version and validate
		$is_ipv6   = filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
		$max_mask  = $is_ipv6 ? 128 : 32;
		$byte_len  = $is_ipv6 ? 16 : 4;
		$ip_filter = $is_ipv6 ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4;

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, $ip_filter ) ) {
			return false;
		}

		if ( $mask > $max_mask ) {
			return false;
		}

		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( $subnet );

		if ( false === $ip_bin || false === $subnet_bin ) {
			return false;
		}

		// Build mask
		$full_bytes = (int) ( $mask / 8 );
		$rem_bits   = $mask % 8;

		$mask_bin = str_repeat( "\xff", $full_bytes );
		if ( $rem_bits > 0 ) {
			$mask_bin .= chr( 0xff << ( 8 - $rem_bits ) );
		}
		$mask_bin = str_pad( $mask_bin, $byte_len, "\x00" );

		return ( $ip_bin & $mask_bin ) === ( $subnet_bin & $mask_bin );
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
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- No WP available
			$_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		}
		return $_ip;
	}
}
