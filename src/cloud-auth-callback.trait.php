<?php
/**
 * Cloud auth callback trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Cloud_Auth_Callback
 *
 * Handles QUIC.cloud activation callbacks, status updates, and cryptographic operations.
 */
trait Cloud_Auth_Callback {

	/**
	 * Encrypt data for cloud req
	 *
	 * @since 7.0
	 *
	 * @param string|int $data Data to sign.
	 * @return string|false
	 */
	private function _sign_b64( $data ) {
		if ( empty( $this->_summary['sk_b64'] ) ) {
			self::debugErr( 'No sk to sign.' );
			return false;
		}
		$sk = base64_decode( $this->_summary['sk_b64'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( strlen( $sk ) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES ) {
			self::debugErr( 'Invalid local sign sk length.' );
			// Reset local pk/sk
			unset( $this->_summary['pk_b64'] );
			unset( $this->_summary['sk_b64'] );
			$this->save_summary();
			self::debug( 'Clear local sign pk/sk pair.' );

			return false;
		}
		$signature = sodium_crypto_sign_detached( (string) $data, $sk );
		return base64_encode( $signature ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Load server pk from cloud
	 *
	 * @since 7.0
	 *
	 * @param bool $from_wpapi Load from WP API server.
	 * @return string|false Binary public key or false.
	 */
	private function _load_server_pk( $from_wpapi = false ) {
		// Load cloud pk
		$server_key_url = $this->_cloud_server . '/' . self::API_SERVER_KEY_SIGN;
		if ( $from_wpapi ) {
			$server_key_url = $this->_cloud_server_wp . '/' . self::API_SERVER_KEY_SIGN;
		}
		$resp = wp_safe_remote_get( $server_key_url );
		if ( is_wp_error( $resp ) ) {
			self::debugErr( 'Failed to load key: ' . $resp->get_error_message() );
			return false;
		}
		$pk = trim( $resp['body'] );
		self::debug( 'Loaded key from ' . $server_key_url . ': ' . $pk );
		$cloud_pk = base64_decode( $pk ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( strlen( $cloud_pk ) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ) {
			self::debugErr( 'Invalid cloud public key length.' );
			return false;
		}

		$sk = base64_decode( $this->_summary['sk_b64'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( strlen( $sk ) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES ) {
			self::debugErr( 'Invalid local secret key length.' );
			// Reset local pk/sk
			unset( $this->_summary['pk_b64'] );
			unset( $this->_summary['sk_b64'] );
			$this->save_summary();
			self::debug( 'Unset local pk/sk pair.' );

			return false;
		}

		return $cloud_pk;
	}

	/**
	 * WPAPI echo back to notify the sealed databox
	 *
	 * @since 7.0
	 */
	public function wp_rest_echo() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		self::debug( 'Parsing echo', $_POST );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ts = !empty( $_POST['wpapi_ts'] ) ? sanitize_text_field( wp_unslash( $_POST['wpapi_ts'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sig = !empty( $_POST['wpapi_signature_b64'] ) ? sanitize_text_field( wp_unslash( $_POST['wpapi_signature_b64'] ) ) : '';

		if ( empty( $ts ) || empty( $sig ) ) {
			return self::err( 'No echo data' );
		}

		$is_valid = $this->_validate_signature( $sig, $ts, true );
		if ( ! $is_valid ) {
			return self::err( 'Data validation from WPAPI REST Echo failed' );
		}

		$diff = time() - (int) $ts;
		if ( abs( $diff ) > 86400 ) {
			self::debugErr( 'WPAPI echo data timeout [diff] ' . $diff );
			return self::err( 'Echo data expired' );
		}

		$signature_b64 = $this->_sign_b64( $ts );
		self::debug( 'Response to echo [signature_b64] ' . $signature_b64 );
		return self::ok( [ 'signature_b64' => $signature_b64 ] );
	}

	/**
	 * Validate cloud data
	 *
	 * @since 7.0
	 *
	 * @param string $signature_b64 Base64 signature.
	 * @param string $data          Data to validate.
	 * @param bool   $from_wpapi    Whether the signature is from WP API server.
	 * @return bool
	 */
	private function _validate_signature( $signature_b64, $data, $from_wpapi = false ) {
		// Try validation
		try {
			$cloud_pk = $this->_load_server_pk( $from_wpapi );
			if ( ! $cloud_pk ) {
				return false;
			}
			$signature = base64_decode( $signature_b64 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$is_valid  = sodium_crypto_sign_verify_detached( $signature, (string) $data, $cloud_pk );
		} catch ( \SodiumException $e ) {
			self::debugErr( 'Decryption failed: ' . esc_html( $e->getMessage() ) );
			return false;
		}
		self::debug( 'Signature validation result: ' . ( $is_valid ? 'true' : 'false' ) );
		return $is_valid;
	}

	/**
	 * Finish qc activation after redirection back from QC
	 *
	 * @since 7.0
	 *
	 * @param string|false $ref Ref slug.
	 */
	public function finish_qc_activation( $ref = false ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$qc_activated = !empty( $_GET['qc_activated'] ) ? sanitize_text_field( wp_unslash( $_GET['qc_activated'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$qc_ts = !empty( $_GET['qc_ts'] ) ? sanitize_text_field( wp_unslash( $_GET['qc_ts'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$qc_sig = !empty( $_GET['qc_signature_b64'] ) ? sanitize_text_field( wp_unslash( $_GET['qc_signature_b64'] ) ) : '';

		if ( ! $qc_activated || ! $qc_ts || ! $qc_sig ) {
			return;
		}

		$data_to_validate_signature = [
			'wp_pk_b64' => $this->_summary['pk_b64'],
			'qc_ts'     => $qc_ts,
		];
		$is_valid                   = $this->_validate_signature( $qc_sig, implode( '', $data_to_validate_signature ) );
		if ( ! $is_valid ) {
			self::debugErr( 'Failed to validate qc activation data' );
			Admin_Display::error( sprintf( __( 'Failed to validate %s activation data.', 'litespeed-cache' ), 'QUIC.cloud' ) );
			return;
		}

		self::debug( 'QC activation status: ' . $qc_activated );
		if ( ! in_array( $qc_activated, [ 'anonymous', 'linked', 'cdn' ], true ) ) {
			self::debugErr( 'Failed to parse qc activation status' );
			Admin_Display::error( sprintf( __( 'Failed to parse %s activation status.', 'litespeed-cache' ), 'QUIC.cloud' ) );
			return;
		}

		$diff = time() - (int) $qc_ts;
		if ( abs( $diff ) > 86400 ) {
			self::debugErr( 'QC activation data timeout [diff] ' . $diff );
			Admin_Display::error( sprintf( __( '%s activation data expired.', 'litespeed-cache' ), 'QUIC.cloud' ) );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$main_domain = ! empty( $_GET['main_domain'] ) ? sanitize_text_field( wp_unslash( $_GET['main_domain'] ) ) : false;
		$this->update_qc_activation( $qc_activated, $main_domain );

		wp_safe_redirect( $this->_get_ref_url( $ref ) );
		exit;
	}

	/**
	 * Finish qc activation process
	 *
	 * @since 7.0
	 *
	 * @param string      $qc_activated Activation status.
	 * @param string|bool $main_domain  Main domain.
	 * @param bool        $quite        Quiet flag.
	 */
	public function update_qc_activation( $qc_activated, $main_domain = false, $quite = false ) {
		$this->_summary['qc_activated'] = $qc_activated;
		if ( $main_domain ) {
			$this->_summary['main_domain'] = $main_domain;
		}
		$this->save_summary();

		$msg = sprintf( __( 'Congratulations, %s successfully set this domain up for the anonymous online services.', 'litespeed-cache' ), 'QUIC.cloud' );
		if ( 'linked' === $qc_activated ) {
			$msg = sprintf( __( 'Congratulations, %s successfully set this domain up for the online services.', 'litespeed-cache' ), 'QUIC.cloud' );
			// Sync possible partner info
			$this->sync_usage();
		}
		if ( 'cdn' === $qc_activated ) {
			$msg = sprintf( __( 'Congratulations, %s successfully set this domain up for the online services with CDN service.', 'litespeed-cache' ), 'QUIC.cloud' );
			// Turn on CDN option
			$this->cls( 'Conf' )->update_confs( [ self::O_CDN_QUIC => true ] );
		}
		if ( ! $quite ) {
			Admin_Display::success( '🎊 ' . $msg );
		}

		$this->_clear_reset_qc_reg_msg();

		$this->clear_cloud();
	}

	/**
	 * Update QC status
	 *
	 * @since 7.0
	 */
	public function update_cdn_status() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$qc_activated = !empty( $_POST['qc_activated'] ) ? sanitize_text_field( wp_unslash( $_POST['qc_activated'] ) ) : '';

		if ( !$qc_activated || ! in_array( $qc_activated, [ 'anonymous', 'linked', 'cdn', 'deleted' ], true ) ) {
			return self::err( 'lack_of_params' );
		}

		self::debug( 'update_cdn_status request hash: ' . $qc_activated );

		if ( 'deleted' === $qc_activated ) {
			$this->_reset_qc_reg();
		} else {
			$this->_summary['qc_activated'] = $qc_activated;
			$this->save_summary();
		}

		if ( 'cdn' === $qc_activated ) {
			$msg = sprintf( __( 'Congratulations, %s successfully set this domain up for the online services with CDN service.', 'litespeed-cache' ), 'QUIC.cloud' );
			Admin_Display::success( '🎊 ' . $msg );
			$this->_clear_reset_qc_reg_msg();
			// Turn on CDN option
			$this->cls( 'Conf' )->update_confs( [ self::O_CDN_QUIC => true ] );
			$this->cls( 'CDN\Quic' )->try_sync_conf( true );
		}

		return self::ok( [ 'qc_activated' => $qc_activated ] );
	}

	/**
	 * Clear QC linked status
	 *
	 * @since 5.0
	 */
	private function _reset_qc_reg() {
		unset( $this->_summary['qc_activated'] );
		if ( ! empty( $this->_summary['partner'] ) ) {
			unset( $this->_summary['partner'] );
		}
		self::save_summary();

		$msg = $this->_reset_qc_reg_content();
		Admin_Display::error( $msg, false, true );
	}

	/**
	 * Build reset QC registration content.
	 *
	 * @since 7.0
	 * @return string
	 */
	private function _reset_qc_reg_content() {
		$msg  = __( 'Site not recognized. QUIC.cloud deactivated automatically. Please reactivate your QUIC.cloud account.', 'litespeed-cache' );
		$msg .= Doc::learn_more( admin_url( 'admin.php?page=litespeed' ), __( 'Click here to proceed.', 'litespeed-cache' ), true, false, true );
		$msg .= Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/', false, false, false, true );
		return $msg;
	}

	/**
	 * Clear reset QC reg msg if exist
	 *
	 * @since 7.0
	 */
	private function _clear_reset_qc_reg_msg() {
		self::debug( 'Removed pinned reset QC reg content msg' );
		$msg = $this->_reset_qc_reg_content();
		Admin_Display::dismiss_pin_by_content( $msg, Admin_Display::NOTICE_RED, true );
	}
}
