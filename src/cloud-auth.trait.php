<?php
/**
 * Cloud auth trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Cloud_Auth
 *
 * Handles QUIC.cloud activation, authentication, and CDN setup.
 */
trait Cloud_Auth {
	use Cloud_Auth_Callback;
	use Cloud_Auth_IP;

	/**
	 * Init QC setup preparation
	 *
	 * @since 7.0
	 */
	public function init_qc_prepare() {
		if ( empty( $this->_summary['sk_b64'] ) ) {
			$keypair                  = sodium_crypto_sign_keypair();
			$pk                       = base64_encode( sodium_crypto_sign_publickey( $keypair ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$sk                       = base64_encode( sodium_crypto_sign_secretkey( $keypair ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$this->_summary['pk_b64'] = $pk;
			$this->_summary['sk_b64'] = $sk;
			$this->save_summary();
			// ATM `qc_activated` = null
			return true;
		}

		return false;
	}

	/**
	 * Init QC setup
	 *
	 * @since 7.0
	 */
	public function init_qc() {
		$this->init_qc_prepare();

		$ref = $this->_get_ref_url();

		// WPAPI REST echo dryrun
		$echobox = self::post( self::API_REST_ECHO, false, 60 );
		if ( false === $echobox ) {
			self::debugErr( 'REST Echo Failed!' );
			$msg = __( "QUIC.cloud's access to your WP REST API seems to be blocked.", 'litespeed-cache' );
			Admin_Display::error( $msg );
			wp_safe_redirect( $ref );
			exit;
		}

		self::debug( 'echo succeeded' );

		// Load separate thread echoed data from storage
		if ( empty( $echobox['wpapi_ts'] ) || empty( $echobox['wpapi_signature_b64'] ) ) {
			Admin_Display::error( __( 'Failed to get echo data from WPAPI', 'litespeed-cache' ) );
			wp_safe_redirect( $ref );
			exit;
		}

		$data      = [
			'wp_pk_b64'           => $this->_summary['pk_b64'],
			'wpapi_ts'            => $echobox['wpapi_ts'],
			'wpapi_signature_b64' => $echobox['wpapi_signature_b64'],
		];
		$server_ip = $this->conf( self::O_SERVER_IP );
		if ( $server_ip ) {
			$data['server_ip'] = $server_ip;
		}

		// Activation redirect
		$param = [
			'site_url' => site_url(),
			'ver'      => Core::VER,
			'data'     => $data,
			'ref'      => $ref,
		];
		wp_safe_redirect( $this->_cloud_server_dash . '/' . self::SVC_U_ACTIVATE . '?data=' . rawurlencode( Utility::arr2str( $param ) ) );
		exit;
	}

	/**
	 * Decide the ref
	 *
	 * @param string|false $ref Ref slug.
	 * @return string
	 */
	private function _get_ref_url( $ref = false ) {
		$link = 'admin.php?page=litespeed';
		if ( 'cdn' === $ref ) {
			$link = 'admin.php?page=litespeed-cdn';
		}
		if ( 'online' === $ref ) {
			$link = 'admin.php?page=litespeed-general';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref_get = ! empty( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
		if ( $ref_get && 'cdn' === $ref_get ) {
			$link = 'admin.php?page=litespeed-cdn';
		}
		if ( $ref_get && 'online' === $ref_get ) {
			$link = 'admin.php?page=litespeed-general';
		}
		return get_admin_url( null, $link );
	}

	/**
	 * Init QC setup (CLI)
	 *
	 * @since 7.0
	 */
	public function init_qc_cli() {
		$this->init_qc_prepare();

		$server_ip = $this->conf( self::O_SERVER_IP );
		if ( ! $server_ip ) {
			self::debugErr( 'Server IP needs to be set first!' );
			$msg = sprintf(
				__( 'You need to set the %1$s first. Please use the command %2$s to set.', 'litespeed-cache' ),
				'`' . __( 'Server IP', 'litespeed-cache' ) . '`',
				'`wp litespeed-option set server_ip __your_ip_value__`'
			);
			Admin_Display::error( $msg );
			return;
		}

		// WPAPI REST echo dryrun
		$echobox = self::post( self::API_REST_ECHO, false, 60 );
		if ( false === $echobox ) {
			self::debugErr( 'REST Echo Failed!' );
			$msg = __( "QUIC.cloud's access to your WP REST API seems to be blocked.", 'litespeed-cache' );
			Admin_Display::error( $msg );
			return;
		}

		self::debug( 'echo succeeded' );

		// Load separate thread echoed data from storage
		if ( empty( $echobox['wpapi_ts'] ) || empty( $echobox['wpapi_signature_b64'] ) ) {
			self::debug( 'Resp: ', $echobox );
			Admin_Display::error( __( 'Failed to get echo data from WPAPI', 'litespeed-cache' ) );
			return;
		}

		$data = [
			'wp_pk_b64'           => $this->_summary['pk_b64'],
			'wpapi_ts'            => $echobox['wpapi_ts'],
			'wpapi_signature_b64' => $echobox['wpapi_signature_b64'],
			'server_ip'           => $server_ip,
		];

		$res = $this->post( self::SVC_D_ACTIVATE, $data );
		return $res;
	}

	/**
	 * Init QC CDN setup (CLI)
	 *
	 * @since 7.0
	 *
	 * @param string      $method   Method.
	 * @param string|bool $cert     Cert path.
	 * @param string|bool $key      Key path.
	 * @param string|bool $cf_token Cloudflare token.
	 */
	public function init_qc_cdn_cli( $method, $cert = false, $key = false, $cf_token = false ) {
		if ( ! $this->activated() ) {
			Admin_Display::error( __( 'You need to activate QC first.', 'litespeed-cache' ) );
			return;
		}

		$server_ip = $this->conf( self::O_SERVER_IP );
		if ( ! $server_ip ) {
			self::debugErr( 'Server IP needs to be set first!' );
			$msg = sprintf(
				__( 'You need to set the %1$s first. Please use the command %2$s to set.', 'litespeed-cache' ),
				'`' . __( 'Server IP', 'litespeed-cache' ) . '`',
				'`wp litespeed-option set server_ip __your_ip_value__`'
			);
			Admin_Display::error( $msg );
			return;
		}

		if ( $cert ) {
			if ( ! file_exists( $cert ) || ! file_exists( $key ) ) {
				Admin_Display::error( __( 'Cert or key file does not exist.', 'litespeed-cache' ) );
				return;
			}
		}

		$data = [
			'method'    => $method,
			'server_ip' => $server_ip,
		];
		if ( $cert ) {
			$data['cert'] = File::read( $cert );
			$data['key']  = File::read( $key );
		}
		if ( $cf_token ) {
			$data['cf_token'] = $cf_token;
		}

		$res = $this->post( self::SVC_D_ENABLE_CDN, $data );
		return $res;
	}

	/**
	 * Link to QC setup
	 *
	 * @since 7.0
	 */
	public function link_qc() {
		if ( ! $this->activated() ) {
			Admin_Display::error( __( 'You need to activate QC first.', 'litespeed-cache' ) );
			return;
		}

		$data                     = [
			'wp_ts' => time(),
		];
		$data['wp_signature_b64'] = $this->_sign_b64( $data['wp_ts'] );

		// Activation redirect
		$param = [
			'site_url' => site_url(),
			'ver'      => Core::VER,
			'data'     => $data,
			'ref'      => $this->_get_ref_url(),
		];
		wp_safe_redirect( $this->_cloud_server_dash . '/' . self::SVC_U_LINK . '?data=' . rawurlencode( Utility::arr2str( $param ) ) );
		exit;
	}

	/**
	 * Show QC Account CDN status
	 *
	 * @since 7.0
	 */
	public function cdn_status_cli() {
		if ( ! $this->activated() ) {
			Admin_Display::error( __( 'You need to activate QC first.', 'litespeed-cache' ) );
			return;
		}

		$data = [];
		$res  = $this->post( self::SVC_D_STATUS_CDN_CLI, $data );
		return $res;
	}

	/**
	 * Link to QC Account for CLI
	 *
	 * @since 7.0
	 *
	 * @param string $email Account email.
	 * @param string $key   API key.
	 */
	public function link_qc_cli( $email, $key ) {
		if ( ! $this->activated() ) {
			Admin_Display::error( __( 'You need to activate QC first.', 'litespeed-cache' ) );
			return;
		}

		$data = [
			'qc_acct_email' => $email,
			'qc_acct_apikey'=> $key,
		];
		$res  = $this->post( self::SVC_D_LINK, $data );
		return $res;
	}

	/**
	 * API link parsed call to QC
	 *
	 * @since 7.0
	 *
	 * @param string $action2 Action slug.
	 */
	public function api_link_call( $action2 ) {
		if ( ! $this->activated() ) {
			Admin_Display::error( __( 'You need to activate QC first.', 'litespeed-cache' ) );
			return;
		}

		$data = [
			'action2' => $action2,
		];
		$res  = $this->post( self::SVC_D_API, $data );
		self::debug( 'API link call result: ', $res );
	}

	/**
	 * Enable QC CDN
	 *
	 * @since 7.0
	 */
	public function enable_cdn() {
		if ( ! $this->activated() ) {
			Admin_Display::error( __( 'You need to activate QC first.', 'litespeed-cache' ) );
			return;
		}

		$data                     = [
			'wp_ts' => time(),
		];
		$data['wp_signature_b64'] = $this->_sign_b64( $data['wp_ts'] );

		// Activation redirect
		$param = [
			'site_url' => site_url(),
			'ver'      => Core::VER,
			'data'     => $data,
			'ref'      => $this->_get_ref_url(),
		];
		wp_safe_redirect( $this->_cloud_server_dash . '/' . self::SVC_U_ENABLE_CDN . '?data=' . rawurlencode( Utility::arr2str( $param ) ) );
		exit;
	}

	/**
	 * Reset QC setup
	 *
	 * @since 7.0
	 */
	public function reset_qc() {
		unset( $this->_summary['pk_b64'] );
		unset( $this->_summary['sk_b64'] );
		unset( $this->_summary['qc_activated'] );
		if ( ! empty( $this->_summary['partner'] ) ) {
			unset( $this->_summary['partner'] );
		}
		$this->save_summary();
		self::debug( 'Clear local QC activation.' );

		$this->clear_cloud();

		Admin_Display::success( sprintf( __( 'Reset %s activation successfully.', 'litespeed-cache' ), 'QUIC.cloud' ) );
		wp_safe_redirect( $this->_get_ref_url() );
		exit;
	}

	/**
	 * Check if activated QUIC.cloud service or not
	 *
	 * @since  7.0
	 * @access public
	 */
	public function activated() {
		return ! empty( $this->_summary['sk_b64'] ) && ! empty( $this->_summary['qc_activated'] );
	}

	/**
	 * Show my.qc quick link to the domain page
	 *
	 * @return string
	 */
	public function qc_link() {
		$data = [
			'site_url' => site_url(),
			'ver' => LSCWP_V,
			'ref' => $this->_get_ref_url(),
		];
		return $this->_cloud_server_dash . '/u/wp3/manage?data=' . rawurlencode( Utility::arr2str( $data ) ); // . (!empty($this->_summary['is_linked']) ? '?wplogin=1' : '');
	}
}
