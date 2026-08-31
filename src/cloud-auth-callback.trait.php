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
	 * Successfully authorized REST requests retained for WordPress's repeated permission check.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $_validated_callback_requests = [];

	/**
	 * Encrypt data for cloud req
	 *
	 * @since 7.0
	 *
	 * @param string|int $data Data to sign.
	 * @return string|false
	 */
	private function _sign_b64( $data ) {
		$sk = $this->_local_sign_sk();
		if ( false === $sk ) {
			self::debugErr( 'No usable local sign sk.' );
			if ( ! empty( $this->_summary['pk_b64'] ) || ! empty( $this->_summary['sk_b64'] ) ) {
				$this->save_summary(
					[
						'pk_b64' => '',
						'sk_b64' => '',
					],
					true
				);
				self::debug( 'Cleared the local sign pk/sk pair.' );
			}
			return false;
		}
		$signature = sodium_crypto_sign_detached( (string) $data, $sk );
		return base64_encode( $signature ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decode a base64 value of an exact byte length.
	 *
	 * Every key and signature here is fixed-length, so strict decode plus length is the whole validation.
	 *
	 * @since 7.9.1
	 *
	 * @param mixed $value          Base64 value.
	 * @param int   $expected_bytes Required decoded length.
	 * @return string|false Binary value or false.
	 */
	private function _decode_b64( $value, $expected_bytes ) {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return false;
		}

		$decoded = base64_decode( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return false !== $decoded && strlen( $decoded ) === $expected_bytes ? $decoded : false;
	}

	/**
	 * Load a matching local signing key pair.
	 *
	 * @since 7.9.1
	 * @return string|false Binary secret key or false.
	 */
	private function _local_sign_sk() {
		$secret_key = $this->_decode_b64( isset( $this->_summary['sk_b64'] ) ? $this->_summary['sk_b64'] : '', SODIUM_CRYPTO_SIGN_SECRETKEYBYTES );
		$public_key = $this->_decode_b64( isset( $this->_summary['pk_b64'] ) ? $this->_summary['pk_b64'] : '', SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES );
		if ( false === $secret_key || false === $public_key ) {
			return false;
		}

		try {
			return hash_equals( $public_key, sodium_crypto_sign_publickey_from_secretkey( $secret_key ) ) ? $secret_key : false;
		} catch ( \SodiumException $e ) {
			return false;
		}
	}

	/**
	 * Binary public keys a server signature may be verified against.
	 *
	 * @since 7.9.1
	 *
	 * @param bool   $from_wpapi Use the WP API ring instead of the QUIC.cloud ring.
	 * @param string $key_id     Optional pinned key ID.
	 * @return array<int,string> Binary public keys.
	 */
	private function _trusted_server_pks( $from_wpapi = false, $key_id = '' ) {
		$environment = false !== strpos( $this->_cloud_server, 'preview.' ) ? 'preview' : 'prod';
		$source      = $from_wpapi ? 'wpapi' : 'qc';
		$ring        = isset( self::SERVER_SIGN_KEYS[ $environment ][ $source ] ) ? self::SERVER_SIGN_KEYS[ $environment ][ $source ] : [];
		if ( '' !== $key_id ) {
			$ring = isset( $ring[ $key_id ] ) ? [ $ring[ $key_id ] ] : [];
		}

		$keys = [];
		foreach ( $ring as $key_b64 ) {
			$public_key = $this->_decode_b64( $key_b64, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES );
			if ( $public_key ) {
				$keys[] = $public_key;
			}
		}

		return $keys;
	}

	/**
	 * WPAPI echo back to notify the sealed databox
	 *
	 * @since 7.0
	 */
	public function wp_rest_echo() {
		$site_pk = isset( $this->_summary['pk_b64'] ) && is_string( $this->_summary['pk_b64'] ) ? $this->_summary['pk_b64'] : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ts_input = isset( $_POST['wpapi_ts'] ) ? wp_unslash( $_POST['wpapi_ts'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sig_input = isset( $_POST['wpapi_signature_b64'] ) ? wp_unslash( $_POST['wpapi_signature_b64'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$key_id_input = isset( $_POST['wpapi_key_id'] ) ? wp_unslash( $_POST['wpapi_key_id'] ) : '';
		$ts           = is_string( $ts_input ) ? sanitize_text_field( $ts_input ) : '';
		$sig          = is_string( $sig_input ) ? sanitize_text_field( $sig_input ) : '';
		$key_id       = is_string( $key_id_input ) ? sanitize_text_field( $key_id_input ) : '';

		if (
			'' === $site_pk ||
			! preg_match( '/^\d{10}$/D', $ts ) ||
			'' === $sig ||
			( '' !== $key_id && ! preg_match( '/^[a-z0-9-]{1,64}$/D', $key_id ) )
		) {
			return self::err( 'No echo data' );
		}

		$echo_data = "echo\n" . $site_pk . "\n" . $ts;
		$diff      = time() - (int) $ts;
		if ( abs( $diff ) > self::SIGN_MAX_AGE ) {
			self::debugErr( 'WPAPI echo data timeout [diff] ' . $diff );
			return self::err( 'Echo data expired' );
		}

		if ( ! $this->_validate_signature( $sig, $echo_data, true, $key_id ) ) {
			return self::err( 'Data validation from WPAPI REST Echo failed' );
		}

		$signature_b64 = $this->_sign_b64( $echo_data );
		if ( false === $signature_b64 ) {
			return self::err( 'Failed to sign echo data' );
		}
		return self::ok( [ 'signature_b64' => $signature_b64 ] );
	}

	/**
	 * Validate cloud data
	 *
	 * @since 7.0
	 * @since 7.9.1 Uses a source-specific pinned key ring.
	 *
	 * @param string $signature_b64 Base64 signature.
	 * @param string $data          Data to validate.
	 * @param bool   $from_wpapi    Whether the signature is from WP API server.
	 * @param string $key_id        Optional pinned key ID.
	 * @return bool
	 */
	private function _validate_signature( $signature_b64, $data, $from_wpapi = false, $key_id = '' ) {
		$signature = $this->_decode_b64( $signature_b64, SODIUM_CRYPTO_SIGN_BYTES );
		if ( false === $signature ) {
			return false;
		}

		// Both operands are already length-checked by _decode_b64(), so sodium cannot raise on argument size here.
		try {
			foreach ( $this->_trusted_server_pks( $from_wpapi, $key_id ) as $cloud_pk ) {
				if ( sodium_crypto_sign_verify_detached( $signature, (string) $data, $cloud_pk ) ) {
					return true;
				}
			}
		} catch ( \SodiumException $e ) {
			self::debugErr( 'Signature validation failed: ' . esc_html( $e->getMessage() ) );
		}

		return false;
	}

	/**
	 * Build a stable REST authorization error for a signed callback.
	 *
	 * @since 7.9.1
	 *
	 * @param string $code      Machine-readable error code.
	 * @param string $log       Private debug-log detail.
	 * @param bool   $retryable Whether a sender may retry with fresh signature metadata.
	 * @param int    $status    HTTP status.
	 * @return \WP_Error
	 */
	private function _callback_error( $code, $log, $retryable = false, $status = 403 ) {
		self::debugErr( $log );
		return new \WP_Error(
			$code,
			__( 'QUIC.cloud callback authorization failed.', 'litespeed-cache' ),
			[
				'status'    => $status,
				'retryable' => (bool) $retryable,
			]
		);
	}

	/**
	 * Atomically claim a callback nonce in the current site's options table.
	 *
	 * @since 7.9.1
	 *
	 * @param string $nonce Callback nonce.
	 * @param int    $qc_ts Signed timestamp.
	 * @return true|\WP_Error
	 */
	private function _claim_callback_nonce( $nonce, $qc_ts ) {
		global $wpdb;
		$prefix      = self::name( self::ITEM_SIGN_NONCE );
		$option_name = $prefix . hash( 'sha256', $this->_summary['pk_b64'] . "\n" . $nonce );
		$now         = time();
		$expires_at  = (int) $qc_ts + self::SIGN_MAX_AGE + 1;

		$cleanup = "DELETE FROM `$wpdb->options` WHERE option_name LIKE %s AND CAST( option_value AS UNSIGNED ) < %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$cleaned = $wpdb->query( $wpdb->prepare( $cleanup, [ $wpdb->esc_like( $prefix ) . '%', $now ] ) );
		if ( false === $cleaned ) {
			return $this->_callback_error( self::CALLBACK_ERR_STORAGE, 'Failed to clean the callback replay cache: ' . (string) $wpdb->last_error, true, 503 );
		}

		$q = "INSERT INTO `$wpdb->options` ( option_name, option_value, autoload ) VALUES ( %s, %d, 'no' )
			ON DUPLICATE KEY UPDATE option_value = IF( CAST( option_value AS UNSIGNED ) < %d, VALUES( option_value ), option_value )";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$claimed = $wpdb->query( $wpdb->prepare( $q, [ $option_name, $expires_at, $now ] ) );
		if ( false === $claimed ) {
			return $this->_callback_error( self::CALLBACK_ERR_STORAGE, 'Failed to write the callback replay cache: ' . (string) $wpdb->last_error, true, 503 );
		}
		if ( 0 === $claimed ) {
			return $this->_callback_error( self::CALLBACK_ERR_REPLAY, 'Replayed callback signature.' );
		}

		$count_q = "SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE %s AND CAST( option_value AS UNSIGNED ) >= %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $wpdb->prepare( $count_q, [ $wpdb->esc_like( $prefix ) . '%', $now ] ) );
		if ( null === $count && ! empty( $wpdb->last_error ) ) {
			return $this->_callback_error( self::CALLBACK_ERR_STORAGE, 'Failed to read the callback replay cache: ' . (string) $wpdb->last_error, true, 503 );
		}
		$count = (int) $count;
		if ( $count > self::SIGN_NONCE_MAX ) {
			$delete_q = "DELETE FROM `$wpdb->options` WHERE option_name = %s AND option_value = %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$deleted = $wpdb->query( $wpdb->prepare( $delete_q, [ $option_name, $expires_at ] ) );
			if ( false === $deleted ) {
				return $this->_callback_error( self::CALLBACK_ERR_STORAGE, 'Failed to roll back an over-capacity callback nonce: ' . (string) $wpdb->last_error, true, 503 );
			}
			return $this->_callback_error( self::CALLBACK_ERR_CAPACITY, 'The callback replay cache is full.', true, 429 );
		}

		return true;
	}

	/**
	 * Verify a signature and claim its nonce.
	 *
	 * Shared tail of every signed surface: freshness, signature, and single use. Callers own field extraction and site binding.
	 *
	 * @since 7.9.1
	 *
	 * @param string $signed_bytes  Exact bytes the signature covers.
	 * @param string $signature_b64 Base64 detached signature.
	 * @param mixed  $qc_ts         Callback timestamp.
	 * @param mixed  $qc_nonce      Callback nonce.
	 * @param bool   $from_wpapi    True when the WP API server signed it.
	 * @param string $key_id        Optional pinned key ID.
	 * @return true|\WP_Error
	 */
	private function _verify_signed( $signed_bytes, $signature_b64, $qc_ts, $qc_nonce, $from_wpapi, $key_id = '' ) {
		if (
			empty( $this->_summary['pk_b64'] ) || ! is_string( $this->_summary['pk_b64'] ) ||
			! is_string( $signed_bytes ) || ! is_string( $signature_b64 ) || '' === $signature_b64 ||
			! is_string( $qc_nonce ) || ! preg_match( '/^[a-zA-Z0-9_-]{16,128}$/D', $qc_nonce ) ||
			! is_string( $key_id ) || ( '' !== $key_id && ! preg_match( '/^[a-z0-9-]{1,64}$/D', $key_id ) )
		) {
			return $this->_callback_error( self::CALLBACK_ERR_METADATA, 'Invalid callback signature metadata.' );
		}

		if ( ! is_string( $qc_ts ) || ! preg_match( '/^\d{10}$/D', $qc_ts ) ) {
			return $this->_callback_error( self::CALLBACK_ERR_METADATA, 'Invalid callback timestamp metadata.' );
		}
		$diff = time() - (int) $qc_ts;
		if ( abs( $diff ) > self::SIGN_MAX_AGE ) {
			return $this->_callback_error( self::CALLBACK_ERR_TIMESTAMP, 'Invalid or expired callback timestamp.', true );
		}

		$site_pk = (string) $this->_summary['pk_b64'];
		if ( ! $this->_validate_signature( $signature_b64, $signed_bytes, $from_wpapi, $key_id ) ) {
			return $this->_callback_error( self::CALLBACK_ERR_SIGNATURE, 'Invalid callback signature.' );
		}

		self::reload_summary();
		if ( empty( $this->_summary['pk_b64'] ) || ! hash_equals( (string) $this->_summary['pk_b64'], $site_pk ) ) {
			return $this->_callback_error( self::CALLBACK_ERR_SITE, 'The site key changed during signature validation.' );
		}

		$claimed = $this->_claim_callback_nonce( $qc_nonce, $qc_ts );
		if ( true === $claimed ) {
			self::debug( 'Validated callback [request] ' . substr( hash( 'sha256', $qc_nonce ), 0, 12 ) );
		}
		return $claimed;
	}

	/**
	 * Validate and claim a signed REST callback.
	 *
	 * QUIC.cloud signs the exact raw JSON body and sends the detached signature in `X-QC-Signature-B64`.
	 *
	 * @since 7.9.1
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @param string           $action  Expected self::SIGN_ACTION_* value.
	 * @return true|\WP_Error
	 */
	public function validate_signed_callback( $request, $action ) {
		if (
			! is_object( $request ) || ! method_exists( $request, 'get_body' ) || ! method_exists( $request, 'get_header' ) || ! is_string( $action )
		) {
			return $this->_callback_error( self::CALLBACK_ERR_REQUEST, 'Invalid signed callback request.' );
		}

		$body             = $request->get_body();
		$content_type     = $request->get_header( 'content-type' );
		$content_encoding = $request->get_header( 'content-encoding' );
		$signature_b64    = $request->get_header( 'x-qc-signature-b64' );
		$content_encoding = null === $content_encoding ? '' : $content_encoding;
		$content_type_log = is_string( $content_type ) ? strtolower( trim( (string) strtok( $content_type, ';' ) ) ) : gettype( $content_type );
		$content_type_log = substr( str_replace( [ "\r", "\n" ], '', $content_type_log ), 0, 128 );
		$transport_error  = '';
		if ( ! is_string( $body ) ) {
			$transport_error = 'Request body is not a string.';
		} elseif ( '' === $body ) {
			$transport_error = 'Request body is empty.';
		} elseif ( self::SIGN_BODY_MAX_BYTES < strlen( $body ) ) {
			$transport_error = 'Request body is too large [bytes] ' . strlen( $body );
		} elseif ( ! is_string( $content_type ) ) {
			$transport_error = 'Content-Type header is missing or invalid.';
		} elseif ( ! is_string( $content_encoding ) ) {
			$transport_error = 'Content-Encoding header is invalid.';
		} elseif ( ! is_string( $signature_b64 ) ) {
			$transport_error = 'X-QC-Signature-B64 header is missing or invalid.';
		} elseif ( 'application/json' !== strtolower( trim( (string) strtok( $content_type, ';' ) ) ) ) {
			$transport_error = 'Content-Type is not application/json.';
		} elseif ( '' !== $content_encoding && 'identity' !== strtolower( trim( $content_encoding ) ) ) {
			$transport_error = 'Content-Encoding is not identity.';
		}
		if ( $transport_error ) {
			return $this->_callback_error( self::CALLBACK_ERR_REQUEST, 'Invalid signed callback transport [action] ' . $action . ' [content-type] ' . $content_type_log . ' [reason] ' . $transport_error );
		}

		$payload = json_decode( $body, true, 32 );
		if (
			! is_array( $payload ) ||
			! isset( $payload['qc_sig_v'] ) || ! is_int( $payload['qc_sig_v'] ) || self::SIGN_VERSION !== $payload['qc_sig_v'] ||
			empty( $payload['qc_action'] ) || ! is_string( $payload['qc_action'] ) ||
			! isset( $payload['wp_pk_sha256'] ) || ! is_string( $payload['wp_pk_sha256'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $payload['wp_pk_sha256'] ) ||
			! isset( $payload['qc_ts'] ) || ! is_string( $payload['qc_ts'] ) ||
			! isset( $payload['qc_nonce'] ) || ! is_string( $payload['qc_nonce'] ) ||
			( isset( $payload['qc_key_id'] ) && ! is_string( $payload['qc_key_id'] ) )
		) {
			return $this->_callback_error( self::CALLBACK_ERR_ENVELOPE, 'Invalid callback envelope [action] ' . $action );
		}

		if ( ! hash_equals( $action, $payload['qc_action'] ) ) {
			return $this->_callback_error( self::CALLBACK_ERR_ACTION, 'Callback action does not match its route.' );
		}

		$request_key = spl_object_hash( $request ) . ':' . hash( 'sha256', $action );
		$fingerprint = hash( 'sha256', $body ) . hash( 'sha256', $content_type ) . hash( 'sha256', $content_encoding ) . hash( 'sha256', $signature_b64 );
		if (
			isset( $this->_validated_callback_requests[ $request_key ] ) &&
			$this->_validated_callback_requests[ $request_key ]['request'] === $request &&
			hash_equals( $this->_validated_callback_requests[ $request_key ]['fingerprint'], $fingerprint )
		) {
			return true;
		}

		if ( empty( $this->_summary['pk_b64'] ) || ! is_string( $this->_summary['pk_b64'] ) ) {
			return $this->_callback_error( self::CALLBACK_ERR_REQUEST, 'Invalid signed callback request.' );
		}
		if ( ! hash_equals( hash( 'sha256', (string) $this->_summary['pk_b64'] ), $payload['wp_pk_sha256'] ) ) {
			return $this->_callback_error( self::CALLBACK_ERR_SITE, 'Callback site-key digest does not match this site.' );
		}

		$key_id   = isset( $payload['qc_key_id'] ) ? $payload['qc_key_id'] : '';
		$verified = $this->_verify_signed(
			$body,
			trim( $signature_b64 ),
			$payload['qc_ts'],
			$payload['qc_nonce'],
			self::SIGN_ACTION_NOTIFY_IMG === $action,
			$key_id
		);
		if ( true === $verified ) {
			$this->_validated_callback_requests[ $request_key ] = [
				'request'     => $request,
				'fingerprint' => $fingerprint,
			];
		}
		return $verified;
	}

	/**
	 * Read a sanitized query parameter.
	 *
	 * @since 7.9.1
	 *
	 * @param string $key Parameter name.
	 * @return string
	 */
	private function _get_param( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = isset( $_GET[ $key ] ) ? wp_unslash( $_GET[ $key ] ) : '';
		return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Validate a signed activation redirect from QUIC.cloud.
	 *
	 * The signature covers a newline-delimited canonical string and binds the callback to the local site key.
	 *
	 * @since 7.9.1
	 *
	 * @return array|false Validated fields or false.
	 */
	private function _validate_signed_redirect() {
		if ( empty( $this->_summary['pk_b64'] ) || ! is_string( $this->_summary['pk_b64'] ) ) {
			return false;
		}

		$qc_activated = $this->_get_param( 'qc_activated' );
		$qc_ts        = $this->_get_param( 'qc_ts' );
		$qc_nonce     = $this->_get_param( 'qc_nonce' );
		$qc_sig       = $this->_get_param( 'qc_signature_v2_b64' );
		$qc_key_id    = $this->_get_param( 'qc_key_id' );
		$main_domain  = $this->_get_param( 'main_domain' );

		if ( ! in_array( $qc_activated, [ 'anonymous', 'linked', 'cdn' ], true ) ) {
			self::debugErr( 'Failed to parse qc activation status' );
			return false;
		}

		if ( '' !== $main_domain && ! preg_match( '/^[A-Za-z0-9.-]{1,253}$/D', $main_domain ) ) {
			self::debugErr( 'Invalid main domain in the activation redirect.' );
			return false;
		}

		$signed = implode(
			"\n",
			[
				'main_domain=' . $main_domain,
				'qc_activated=' . $qc_activated,
				'qc_nonce=' . $qc_nonce,
				'qc_ts=' . $qc_ts,
				'wp_pk_b64=' . $this->_summary['pk_b64'],
			]
		);

		if ( true !== $this->_verify_signed( $signed, strtr( $qc_sig, '-_', '+/' ), $qc_ts, $qc_nonce, false, $qc_key_id ) ) {
			return false;
		}

		return [
			'qc_activated' => $qc_activated,
			'main_domain'  => '' !== $main_domain ? $main_domain : false,
		];
	}

	/**
	 * Finish qc activation after redirection back from QC
	 *
	 * @since 7.0
	 *
	 * @param string|false $ref Ref slug.
	 */
	public function finish_qc_activation( $ref = false ) {
		if ( '' === $this->_get_param( 'qc_activated' ) ) {
			return;
		}

		$activation = $this->_validate_signed_redirect();
		if ( ! $activation ) {
			Admin_Display::error( sprintf( __( 'Failed to validate %s activation data.', 'litespeed-cache' ), 'QUIC.cloud' ) );
			return;
		}

		self::debug( 'QC activation status: ' . $activation['qc_activated'] );
		$this->update_qc_activation( $activation['qc_activated'], $activation['main_domain'] );

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
	 * @since 7.9.1 Reads the status from the verified raw body instead of $_POST.
	 *
	 * @param string|null $raw_body Verified raw JSON body from the REST request.
	 * @return array
	 */
	public function update_cdn_status( $raw_body = null ) {
		// Read the status from the signed body, never $_POST.
		$payload = is_string( $raw_body ) ? json_decode( $raw_body, true, 32 ) : false;
		if ( ! is_array( $payload ) ) {
			return self::err( 'invalid data' );
		}

		$qc_activated = ! empty( $payload['qc_activated'] ) && is_string( $payload['qc_activated'] ) ? $payload['qc_activated'] : '';
		if ( ! in_array( $qc_activated, [ 'anonymous', 'linked', 'cdn', 'deleted' ], true ) ) {
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
		$this->save_summary(
			[
				'qc_activated' => '',
				'partner'      => '',
			],
			true
		);

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
