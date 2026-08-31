<?php
/**
 * Cloud auth IP validation trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Cloud_Auth_IP
 *
 * Handles authenticated QUIC.cloud enrollment and diagnostics.
 */
trait Cloud_Auth_IP {

	/**
	 * Confirm the signed Cloud callback path.
	 *
	 * @since  3.0
	 * @access public
	 * @return array
	 */
	public function ip_validate() {
		$site_pk = isset( $this->_summary['pk_b64'] ) && is_string( $this->_summary['pk_b64'] ) ? $this->_summary['pk_b64'] : '';
		if ( '' === $site_pk ) {
			return self::err( 'lack_of_params' );
		}

		Control::set_nocache( 'Cloud IP validation' );

		return self::ok( [ 'hash' => md5( substr( $site_pk, 2, 4 ) ) ] );
	}

	/**
	 * Return pong for ping to check PHP function availability
	 *
	 * @since 6.5
	 *
	 * @param string|null $raw_body Verified request body.
	 * @return array
	 */
	public function ping( $raw_body = null ) {
		$payload = is_string( $raw_body ) ? json_decode( $raw_body, true, 32 ) : false;
		if ( ! is_array( $payload ) ) {
			return self::err( 'invalid data' );
		}

		$resp   = [
			'v_lscwp'     => Core::VER,
			'v_lscwp_db'  => $this->conf( self::_VER ),
			'v_php'       => PHP_VERSION,
			'v_wp'        => $GLOBALS['wp_version'],
			'home_url'    => home_url(),
			'site_url'    => site_url(),
		];
		$checks = [
			'funcs'   => 'function_exists',
			'classes' => 'class_exists',
			'consts'  => 'defined',
		];
		foreach ( $checks as $field => $checker ) {
			if ( empty( $payload[ $field ] ) ) {
				continue;
			}
			if ( ! is_array( $payload[ $field ] ) || count( $payload[ $field ] ) > 64 ) {
				return self::err( 'invalid data' );
			}
			foreach ( $payload[ $field ] as $name ) {
				if ( ! is_string( $name ) || strlen( $name ) > 128 || ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $name ) ) {
					return self::err( 'invalid data' );
				}
				$exists        = 'classes' === $field ? class_exists( $name, false ) : $checker( $name );
				$resp[ $name ] = $exists ? 'y' : 'n';
			}
		}
		return self::ok( $resp );
	}
}
