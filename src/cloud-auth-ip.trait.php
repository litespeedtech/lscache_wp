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
 * Handles QUIC.cloud IP validation and ping operations.
 */
trait Cloud_Auth_IP {

	/**
	 * Request callback validation from Cloud
	 *
	 * @since  3.0
	 * @access public
	 */
	public function ip_validate() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$hash = ! empty( $_POST['hash'] ) ? sanitize_text_field( wp_unslash( $_POST['hash'] ) ) : '';
		if ( !$hash ) {
			return self::err( 'lack_of_params' );
		}

		if ( md5( substr( $this->_summary['pk_b64'], 0, 4 ) ) !== $hash ) {
			self::debug( '__callback IP request decryption failed' );
			return self::err( 'err_hash' );
		}

		Control::set_nocache( 'Cloud IP hash validation' );

		$resp_hash = md5( substr( $this->_summary['pk_b64'], 2, 4 ) );

		self::debug( '__callback IP request hash: ' . $resp_hash );

		return self::ok( [ 'hash' => $resp_hash ] );
	}

	/**
	 * Check if this visit is from cloud or not
	 *
	 * @since  3.0
	 */
	public function is_from_cloud() {
		$check_point = time() - 86400 * self::TTL_IPS;
		if ( empty( $this->_summary['ips'] ) || empty( $this->_summary['ips_ts'] ) || $this->_summary['ips_ts'] < $check_point ) {
			self::debug( 'Force updating ip as ips_ts is older than ' . self::TTL_IPS . ' days' );
			$this->_update_ips();
		}

		$res = $this->cls( 'Router' )->ip_access( $this->_summary['ips'] );
		if ( ! $res ) {
			self::debug( '❌ Not our cloud IP' );

			// Auto check ip list again but need an interval limit safety.
			if ( empty( $this->_summary['ips_ts_runner'] ) || time() - (int) $this->_summary['ips_ts_runner'] > 600 ) {
				self::debug( 'Force updating ip as ips_ts_runner is older than 10mins' );
				// Refresh IP list for future detection
				$this->_update_ips();
				$res = $this->cls( 'Router' )->ip_access( $this->_summary['ips'] );
				if ( ! $res ) {
					self::debug( '❌ 2nd time: Not our cloud IP' );
				} else {
					self::debug( '✅ Passed Cloud IP verification' );
				}
				return $res;
			}
		} else {
			self::debug( '✅ Passed Cloud IP verification' );
		}

		return $res;
	}

	/**
	 * Update Cloud IP list
	 *
	 * @since 4.2
	 *
	 * @throws \Exception When fetching whitelist fails.
	 */
	private function _update_ips() {
		self::debug( 'Load remote Cloud IP list from ' . $this->_cloud_ips );
		// Prevent multiple call in a short period
		self::save_summary([
				'ips_ts'        => time(),
				'ips_ts_runner' => time(),
		]);

		$response = wp_safe_remote_get( $this->_cloud_ips . '?json' );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			self::debug( 'failed to get ip whitelist: ' . $error_message );
			throw new \Exception( 'Failed to fetch QUIC.cloud whitelist ' . esc_html($error_message) );
		}

		$json = \json_decode( $response['body'], true );

		self::debug( 'Load ips', $json );
		self::save_summary( [ 'ips' => $json ] );
	}

	/**
	 * Return pong for ping to check PHP function availability
	 *
	 * @since 6.5
	 *
	 * @return array
	 */
	public function ping() {
		$resp = [
			'v_lscwp'     => Core::VER,
			'v_lscwp_db'  => $this->conf( self::_VER ),
			'v_php'       => PHP_VERSION,
			'v_wp'        => $GLOBALS['wp_version'],
			'home_url'    => home_url(),
			'site_url'    => site_url(),
		];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['funcs'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( wp_unslash($_POST['funcs']) as $v ) {
				$resp[ $v ] = function_exists( $v ) ? 'y' : 'n';
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['classes'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( wp_unslash($_POST['classes']) as $v ) {
				$resp[ $v ] = class_exists( $v ) ? 'y' : 'n';
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['consts'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( wp_unslash($_POST['consts']) as $v ) {
				$resp[ $v ] = defined( $v ) ? 'y' : 'n';
			}
		}
		return self::ok( $resp );
	}
}
