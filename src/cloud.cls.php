<?php
/**
 * Cloud service cls
 *
 * @package LiteSpeed
 * @since 3.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Cloud
 *
 * Handles QUIC.cloud communication, node detection, activation, and related utilities.
 */
class Cloud extends Base {

	const LOG_TAG = '❄️';

	/**
	 * Base API server URL.
	 *
	 * @var string
	 */
	private $_cloud_server = 'https://api.quic.cloud';

	/**
	 * Cloud IPs endpoint.
	 *
	 * @var string
	 */
	private $_cloud_ips = 'https://quic.cloud/ips';

	/**
	 * Cloud dashboard URL.
	 *
	 * @var string
	 */
	private $_cloud_server_dash = 'https://my.quic.cloud';

	/**
	 * Cloud WP API server URL.
	 *
	 * @var string
	 */
	private $_cloud_server_wp = 'https://wpapi.quic.cloud';

	const SVC_D_ACTIVATE       = 'd/activate';
	const SVC_U_ACTIVATE       = 'u/wp3/activate';
	const SVC_D_ENABLE_CDN     = 'd/enable_cdn';
	const SVC_D_LINK           = 'd/link';
	const SVC_D_API            = 'd/api';
	const SVC_D_DASH           = 'd/dash';
	const SVC_D_V3UPGRADE      = 'd/v3upgrade';
	const SVC_U_LINK           = 'u/wp3/link';
	const SVC_U_ENABLE_CDN     = 'u/wp3/enablecdn';
	const SVC_D_STATUS_CDN_CLI = 'd/status/cdn_cli';
	const SVC_D_NODES          = 'd/nodes';
	const SVC_D_SYNC_CONF      = 'd/sync_conf';
	const SVC_D_USAGE          = 'd/usage';
	const SVC_D_SETUP_TOKEN    = 'd/get_token';
	const SVC_D_DEL_CDN_DNS    = 'd/del_cdn_dns';
	const SVC_PAGE_OPTM        = 'page_optm';
	const SVC_CCSS             = 'ccss';
	const SVC_UCSS             = 'ucss';
	const SVC_VPI              = 'vpi';
	const SVC_LQIP             = 'lqip';
	const SVC_QUEUE            = 'queue';
	const SVC_IMG_OPTM         = 'img_optm';
	const SVC_HEALTH           = 'health';
	const SVC_CDN              = 'cdn';

	const IMG_OPTM_DEFAULT_GROUP = 200;

	const IMGOPTM_TAKEN = 'img_optm-taken';

	const TTL_NODE       = 3;   // Days before node expired
	const EXPIRATION_REQ = 300; // Seconds of min interval between two unfinished requests
	const TTL_IPS        = 3;   // Days for node ip list cache

	const API_REPORT          = 'wp/report';
	const API_NEWS            = 'news';
	const API_VER             = 'ver_check';
	const API_BETA_TEST       = 'beta_test';
	const API_REST_ECHO       = 'tool/wp_rest_echo';
	const API_SERVER_KEY_SIGN = 'key_sign';

	/**
	 * Center services hosted at the central API server.
	 *
	 * @var string[]
	 */
	private static $center_svc_set = [
		self::SVC_D_ACTIVATE,
		self::SVC_U_ACTIVATE,
		self::SVC_D_ENABLE_CDN,
		self::SVC_D_LINK,
		self::SVC_D_NODES,
		self::SVC_D_SYNC_CONF,
		self::SVC_D_USAGE,
		self::SVC_D_API,
		self::SVC_D_V3UPGRADE,
		self::SVC_D_DASH,
		self::SVC_D_STATUS_CDN_CLI,
		// self::API_NEWS,
		self::API_REPORT,
		// self::API_VER,
		// self::API_BETA_TEST,
		self::SVC_D_SETUP_TOKEN,
		self::SVC_D_DEL_CDN_DNS,
	];

	/**
	 * Services hosted on the WP API server.
	 *
	 * @var string[]
	 */
	private static $wp_svc_set = [ self::API_NEWS, self::API_VER, self::API_BETA_TEST, self::API_REST_ECHO ];

	/**
	 * Public services that do not require an API key.
	 *
	 * @var string[]
	 */
	private static $_pub_svc_set = [ self::API_NEWS, self::API_REPORT, self::API_VER, self::API_BETA_TEST, self::API_REST_ECHO, self::SVC_D_V3UPGRADE, self::SVC_D_DASH ];

	/**
	 * Services that should go through the queue.
	 *
	 * @var string[]
	 */
	private static $_queue_svc_set = [ self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI ];

	/**
	 * Services that need load check.
	 *
	 * @var string[]
	 */
	public static $services_load_check = [
		// self::SVC_CCSS,
		// self::SVC_UCSS,
		// self::SVC_VPI,
		self::SVC_LQIP,
		self::SVC_HEALTH,
	];

	/**
	 * All supported services.
	 *
	 * @var string[]
	 */
	public static $services = [
		self::SVC_IMG_OPTM,
		self::SVC_PAGE_OPTM,
		self::SVC_CCSS,
		self::SVC_UCSS,
		self::SVC_VPI,
		self::SVC_LQIP,
		self::SVC_CDN,
		self::SVC_HEALTH,
		// self::SVC_QUEUE,
	];

	const TYPE_CLEAR_PROMO    = 'clear_promo';
	const TYPE_REDETECT_CLOUD = 'redetect_cloud';
	const TYPE_CLEAR_CLOUD    = 'clear_cloud';
	const TYPE_ACTIVATE       = 'activate';
	const TYPE_LINK           = 'link';
	const TYPE_ENABLE_CDN     = 'enablecdn';
	const TYPE_API            = 'api';
	const TYPE_SYNC_USAGE     = 'sync_usage';
	const TYPE_RESET          = 'reset';
	const TYPE_SYNC_STATUS    = 'sync_status';

	/**
	 * Summary data for cloud interactions.
	 *
	 * @var array<string,mixed>
	 */
	protected $_summary;

	/**
	 * Init
	 *
	 * @since 3.0
	 */
	public function __construct() {
		$allowed_hosts = [ 'wpapi.quic.cloud' ];
		if ( defined( 'LITESPEED_DEV' ) && constant( 'LITESPEED_DEV' ) ) {
			$allowed_hosts[]          = 'my.preview.quic.cloud';
			$allowed_hosts[]          = 'api.preview.quic.cloud';
			$this->_cloud_server      = 'https://api.preview.quic.cloud';
			$this->_cloud_ips         = 'https://api.preview.quic.cloud/ips';
			$this->_cloud_server_dash = 'https://my.preview.quic.cloud';
			$this->_cloud_server_wp   = 'https://wpapi.quic.cloud';
		} else {
			$allowed_hosts[] = 'my.quic.cloud';
			$allowed_hosts[] = 'api.quic.cloud';
		}
		add_filter( 'allowed_redirect_hosts', function( $hosts ) use ( $allowed_hosts ) {
			return array_merge( $hosts, $allowed_hosts );
		} );
		$this->_summary = self::get_summary();
	}

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

		$diff = time() - $ts;
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
	 * Load QC status for dash usage.
	 * Format to translate: `<a href="{#xxx#}" class="button button-primary">xxxx</a><a href="{#xxx#}">xxxx2</a>`
	 *
	 * @since 7.0
	 *
	 * @param string $type  Type.
	 * @param bool   $force Force refresh.
	 * @return string
	 */
	public function load_qc_status_for_dash( $type, $force = false ) {
		return Str::translate_qc_apis( $this->_load_qc_status_for_dash( $type, $force ) );
	}

	/**
	 * Internal: load QC status HTML for dash.
	 *
	 * @param string $type  Type.
	 * @param bool   $force Force refresh.
	 * @return string
	 */
	private function _load_qc_status_for_dash( $type, $force = false ) {
		if (
			! $force &&
			! empty( $this->_summary['mini_html'] ) &&
			isset( $this->_summary['mini_html'][ $type ] ) &&
			! empty( $this->_summary['mini_html'][ 'ttl.' . $type ] ) &&
			$this->_summary['mini_html'][ 'ttl.' . $type ] > time()
		) {
			return Str::safe_html( $this->_summary['mini_html'][ $type ] );
		}

		// Try to update dash content
		$data = self::post( self::SVC_D_DASH, [ 'action2' => ( 'cdn_dash_mini' === $type ? 'cdn_dash' : $type ) ] );
		if ( ! empty( $data['qc_activated'] ) ) {
			// Sync conf as changed
			if ( empty( $this->_summary['qc_activated'] ) || $this->_summary['qc_activated'] !== $data['qc_activated'] ) {
				$msg = sprintf( __( 'Congratulations, %s successfully set this domain up for the online services with CDN service.', 'litespeed-cache' ), 'QUIC.cloud' );
				Admin_Display::success( '🎊 ' . $msg );
				$this->_clear_reset_qc_reg_msg();
				// Turn on CDN option
				$this->cls( 'Conf' )->update_confs( [ self::O_CDN_QUIC => true ] );
				$this->cls( 'CDN\Quic' )->try_sync_conf( true );
			}

			$this->_summary['qc_activated'] = $data['qc_activated'];
			$this->save_summary();
		}

		// Show the info
		if ( isset( $this->_summary['mini_html'][ $type ] ) ) {
			return Str::safe_html( $this->_summary['mini_html'][ $type ] );
		}

		return '';
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
	 * Show latest commit version always if is on dev
	 *
	 * @since 3.0
	 */
	public function check_dev_version() {
		if ( ! preg_match( '/[^\d\.]/', Core::VER ) ) {
			return;
		}

		$last_check = empty( $this->_summary[ 'last_request.' . self::API_VER ] ) ? 0 : $this->_summary[ 'last_request.' . self::API_VER ];

		if ( time() - $last_check > 86400 ) {
			$auto_v = self::version_check( 'dev' );
			if ( ! empty( $auto_v['dev'] ) ) {
				self::save_summary( [ 'version.dev' => $auto_v['dev'] ] );
			}
		}

		if ( empty( $this->_summary['version.dev'] ) ) {
			return;
		}

		self::debug( 'Latest dev version ' . $this->_summary['version.dev'] );

		if ( version_compare( $this->_summary['version.dev'], Core::VER, '<=' ) ) {
			return;
		}

		// Show the dev banner
		require_once LSCWP_DIR . 'tpl/banner/new_version_dev.tpl.php';
	}

	/**
	 * Check latest version
	 *
	 * @since 2.9
	 * @access public
	 *
	 * @param string|false $src Source.
	 * @return mixed
	 */
	public static function version_check( $src = false ) {
		$req_data = [
			'v'   => defined( 'LSCWP_CUR_V' ) ? LSCWP_CUR_V : '',
			'src' => $src,
			'php' => phpversion(),
		];
		// If code ver is smaller than db ver, bypass
		if ( ! empty( $req_data['v'] ) && version_compare( Core::VER, $req_data['v'], '<' ) ) {
			return;
		}
		if ( defined( 'LITESPEED_ERR' ) ) {
			$litespeed_err   = constant( 'LITESPEED_ERR' );
			$req_data['err'] = base64_encode( ! is_string( $litespeed_err ) ? wp_json_encode( $litespeed_err ) : $litespeed_err ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}
		$data = self::post( self::API_VER, $req_data );

		return $data;
	}

	/**
	 * Show latest news
	 *
	 * @since 3.0
	 */
	public function news() {
		$this->_update_news();

		if ( empty( $this->_summary['news.new'] ) ) {
			return;
		}

		if ( ! empty( $this->_summary['news.plugin'] ) && Activation::cls()->dash_notifier_is_plugin_active( $this->_summary['news.plugin'] ) ) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_news.tpl.php';
	}

	/**
	 * Update latest news
	 *
	 * @since 2.9.9.1
	 */
	private function _update_news() {
		if ( ! empty( $this->_summary['news.utime'] ) && time() - $this->_summary['news.utime'] < 86400 * 7 ) {
			return;
		}

		self::save_summary( [ 'news.utime' => time() ] );

		$data = self::get( self::API_NEWS );
		if ( empty( $data['id'] ) ) {
			return;
		}

		// Save news
		if ( ! empty( $this->_summary['news.id'] ) && (string) $this->_summary['news.id'] === (string) $data['id'] ) {
			return;
		}

		$this->_summary['news.id']      = $data['id'];
		$this->_summary['news.plugin']  = ! empty( $data['plugin'] ) ? $data['plugin'] : '';
		$this->_summary['news.title']   = ! empty( $data['title'] ) ? $data['title'] : '';
		$this->_summary['news.content'] = ! empty( $data['content'] ) ? $data['content'] : '';
		$this->_summary['news.zip']     = ! empty( $data['zip'] ) ? $data['zip'] : '';
		$this->_summary['news.new']     = 1;

		if ( $this->_summary['news.plugin'] ) {
			$plugin_info = Activation::cls()->dash_notifier_get_plugin_info( $this->_summary['news.plugin'] );
			if ( $plugin_info && ! empty( $plugin_info->name ) ) {
				$this->_summary['news.plugin_name'] = $plugin_info->name;
			}
		}

		self::save_summary();
	}

	/**
	 * Check if contains a package in a service or not
	 *
	 * @since 4.0
	 *
	 * @param string $service Service.
	 * @param int    $pkg     Package flag.
	 * @return bool
	 */
	public function has_pkg( $service, $pkg ) {
		if ( ! empty( $this->_summary[ 'usage.' . $service ]['pkgs'] ) && ( $this->_summary[ 'usage.' . $service ]['pkgs'] & $pkg ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get allowance of current service
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string      $service Service.
	 * @param string|bool $err    Error code by ref.
	 * @return int
	 */
	public function allowance( $service, &$err = false ) {
		// Only auto sync usage at most one time per day
		if ( empty( $this->_summary[ 'last_request.' . self::SVC_D_USAGE ] ) || time() - $this->_summary[ 'last_request.' . self::SVC_D_USAGE ] > 86400 ) {
			$this->sync_usage();
		}

		if ( in_array( $service, [ self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI ], true ) ) {
			// @since 4.2
			$service = self::SVC_PAGE_OPTM;
		}

		if ( empty( $this->_summary[ 'usage.' . $service ] ) ) {
			return 0;
		}
		$usage = $this->_summary[ 'usage.' . $service ];

		// Image optm is always free
		$allowance_max = 0;
		if ( self::SVC_IMG_OPTM === $service ) {
			$allowance_max = self::IMG_OPTM_DEFAULT_GROUP;
		}

		$allowance = $usage['quota'] - $usage['used'];

		$err = 'out_of_quota';

		if ( $allowance > 0 ) {
			if ( $allowance_max && $allowance_max < $allowance ) {
				$allowance = $allowance_max;
			}

			// Daily limit @since 4.2
			if ( isset( $usage['remaining_daily_quota'] ) && $usage['remaining_daily_quota'] >= 0 && $usage['remaining_daily_quota'] < $allowance ) {
				$allowance = $usage['remaining_daily_quota'];
				if ( ! $allowance ) {
					$err = 'out_of_daily_quota';
				}
			}

			return $allowance;
		}

		// Check Pay As You Go balance
		if ( empty( $usage['pag_bal'] ) ) {
			return $allowance_max;
		}

		if ( $allowance_max && $allowance_max < $usage['pag_bal'] ) {
			return $allowance_max;
		}

		return (int) $usage['pag_bal'];
	}

	/**
	 * Sync Cloud usage summary data
	 *
	 * @since 3.0
	 * @access public
	 */
	public function sync_usage() {
		$usage = $this->_post( self::SVC_D_USAGE );
		if ( ! $usage ) {
			return;
		}

		self::debug( 'sync_usage ' . wp_json_encode( $usage ) );

		foreach ( self::$services as $v ) {
			$this->_summary[ 'usage.' . $v ] = ! empty( $usage[ $v ] ) ? $usage[ $v ] : false;
		}

		self::save_summary();

		return $this->_summary;
	}

	/**
	 * Clear all existing cloud nodes for future reconnect
	 *
	 * @since 3.0
	 * @access public
	 */
	public function clear_cloud() {
		foreach ( self::$services as $service ) {
			if ( isset( $this->_summary[ 'server.' . $service ] ) ) {
				unset( $this->_summary[ 'server.' . $service ] );
			}
			if ( isset( $this->_summary[ 'server_date.' . $service ] ) ) {
				unset( $this->_summary[ 'server_date.' . $service ] );
			}
		}
		self::save_summary();

		self::debug( 'Cleared all local service node caches' );
	}

	/**
	 * Ping clouds to find the fastest node
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $service Service.
	 * @param bool   $force   Force redetect.
	 * @return string|false
	 */
	public function detect_cloud( $service, $force = false ) {
		if ( in_array( $service, self::$center_svc_set, true ) ) {
			return $this->_cloud_server;
		}

		if ( in_array( $service, self::$wp_svc_set, true ) ) {
			return $this->_cloud_server_wp;
		}

		// Check if the stored server needs to be refreshed
		if ( ! $force ) {
			if (
				! empty( $this->_summary[ 'server.' . $service ] ) &&
				! empty( $this->_summary[ 'server_date.' . $service ] ) &&
				$this->_summary[ 'server_date.' . $service ] > time() - 86400 * self::TTL_NODE
			) {
				$server = $this->_summary[ 'server.' . $service ];
				if ( false === strpos( $this->_cloud_server, 'preview.' ) && false === strpos( $server, 'preview.' ) ) {
					return $server;
				}
				if ( false !== strpos( $this->_cloud_server, 'preview.' ) && false !== strpos( $server, 'preview.' ) ) {
					return $server;
				}
			}
		}

		if ( ! $service || ! in_array( $service, self::$services, true ) ) {
			$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . $service;
			Admin_Display::error( $msg );
			return false;
		}

		// Send request to Quic Online Service
		$json = $this->_post( self::SVC_D_NODES, [ 'svc' => $this->_maybe_queue( $service ) ] );

		// Check if get list correctly
		if ( empty( $json['list'] ) || ! is_array( $json['list'] ) ) {
			self::debug( 'request cloud list failed: ', $json );

			if ( $json ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . wp_json_encode( $json );
				Admin_Display::error( $msg );
			}

			return false;
		}

		// Ping closest cloud
		$valid_clouds = false;
		if ( ! empty( $json['list_preferred'] ) ) {
			$valid_clouds = $this->_get_closest_nodes( $json['list_preferred'], $service );
		}
		if ( ! $valid_clouds ) {
			$valid_clouds = $this->_get_closest_nodes( $json['list'], $service );
		}
		if ( ! $valid_clouds ) {
			return false;
		}

		// Check server load
		if ( in_array( $service, self::$services_load_check, true ) ) {
			// TODO
			$valid_cloud_loads = [];
			foreach ( $valid_clouds as $v ) {
				$response = wp_safe_remote_get( $v, [ 'timeout' => 5 ] );
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					self::debug( 'failed to do load checker: ' . $error_message );
					continue;
				}

				$curr_load = \json_decode( $response['body'], true );
				if ( ! empty( $curr_load['_res'] ) && 'ok' === $curr_load['_res'] && isset( $curr_load['load'] ) ) {
					$valid_cloud_loads[ $v ] = $curr_load['load'];
				}
			}

			if ( ! $valid_cloud_loads ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . __( 'No available Cloud Node after checked server load.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}

			self::debug( 'Closest nodes list after load check', $valid_cloud_loads );

			$qualified_list = array_keys( $valid_cloud_loads, min( $valid_cloud_loads ), true );
		} else {
			$qualified_list = $valid_clouds;
		}

		$closest = $qualified_list[ array_rand( $qualified_list ) ];

		self::debug( 'Chose node: ' . $closest );

		// store data into option locally
		$this->_summary[ 'server.' . $service ]      = $closest;
		$this->_summary[ 'server_date.' . $service ] = time();
		self::save_summary();

		return $this->_summary[ 'server.' . $service ];
	}

	/**
	 * Ping to choose the closest nodes
	 *
	 * @since 7.0
	 *
	 * @param array  $nodes_list    Node list.
	 * @param string $service Service.
	 * @return array|false
	 */
	private function _get_closest_nodes( $nodes_list, $service ) {
		$speed_list = [];
		foreach ( $nodes_list as $v ) {
			// Exclude possible failed 503 nodes
			if ( ! empty( $this->_summary['disabled_node'] ) && ! empty( $this->_summary['disabled_node'][ $v ] ) && time() - $this->_summary['disabled_node'][ $v ] < 86400 ) {
				continue;
			}
			$speed_list[ $v ] = Utility::ping( $v );
		}

		if ( ! $speed_list ) {
			self::debug( 'nodes are in 503 failed nodes' );
			return false;
		}

		$min = min( $speed_list );

		if ( 99999 === (int) $min ) {
			self::debug( 'failed to ping all clouds' );
			return false;
		}

		// Random pick same time range ip (230ms 250ms)
		$range_len    = strlen( $min );
		$range_num    = substr( $min, 0, 1 );
		$valid_clouds = [];
		foreach ( $speed_list as $node => $speed ) {
			if ( strlen( $speed ) === $range_len && substr( $speed, 0, 1 ) === $range_num ) {
				$valid_clouds[] = $node;
			} elseif ( $speed < $min * 4 ) { // Append the lower speed ones
				$valid_clouds[] = $node;
			}
		}

		if ( ! $valid_clouds ) {
			$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . __( 'No available Cloud Node.', 'litespeed-cache' );
			Admin_Display::error( $msg );
			return false;
		}

		self::debug( 'Closest nodes list', $valid_clouds );
		return $valid_clouds;
	}

	/**
	 * May need to convert to queue service
	 *
	 * @param string $service Service.
	 * @return string
	 */
	private function _maybe_queue( $service ) {
		if ( in_array( $service, self::$_queue_svc_set, true ) ) {
			return self::SVC_QUEUE;
		}
		return $service;
	}

	/**
	 * Get data from QUIC cloud server
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $service Service.
	 * @param array  $data    Data.
	 * @return mixed
	 */
	public static function get( $service, $data = [] ) {
		$instance = self::cls();
		return $instance->_get( $service, $data );
	}

	/**
	 * Get data from QUIC cloud server (private)
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string     $service Service.
	 * @param array|bool $data    Data array or false to omit.
	 * @return mixed
	 */
	private function _get( $service, $data = false ) {
		$service_tag = $service;
		if ( ! empty( $data['action'] ) ) {
			$service_tag .= '-' . $data['action'];
		}

		$maybe_cloud = $this->_maybe_cloud( $service_tag );
		if ( ! $maybe_cloud || 'svc_hot' === $maybe_cloud ) {
			return $maybe_cloud;
		}

		$server = $this->detect_cloud( $service );
		if ( ! $server ) {
			return;
		}

		$url = $server . '/' . $service;

		$param = [
			'site_url'   => site_url(),
			'main_domain'=> ! empty( $this->_summary['main_domain'] ) ? $this->_summary['main_domain'] : '',
			'ver'        => Core::VER,
		];

		if ( $data ) {
			$param['data'] = $data;
		}

		$url .= '?' . http_build_query( $param );

		self::debug( 'getting from : ' . $url );

		self::save_summary( [ 'curr_request.' . $service_tag => time() ] );
		File::save( $this->_qc_time_file( $service_tag, 'curr' ), time(), true );

		$response = wp_safe_remote_get(
			$url,
			[
				'timeout' => 15,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		return $this->_parse_response( $response, $service, $service_tag, $server );
	}

	/**
	 * Check if is able to do cloud request or not
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string $service_tag Service tag.
	 * @return bool|string
	 */
	private function _maybe_cloud( $service_tag ) {
		$site_url = site_url();
		if ( ! wp_http_validate_url( $site_url ) ) {
			self::debug( 'wp_http_validate_url failed: ' . $site_url );
			return false;
		}

		// Deny if is IP
		if ( preg_match( '#^(([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)\.){3}([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)$#', Utility::parse_url_safe( $site_url, PHP_URL_HOST ) ) ) {
			self::debug( 'IP home url is not allowed for cloud service.' );
			$msg = __( 'In order to use QC services, need a real domain name, cannot use an IP.', 'litespeed-cache' );
			Admin_Display::error( $msg );
			return false;
		}

		// If in valid err_domains, bypass request
		if ( $this->_is_err_domain( $site_url ) ) {
			self::debug( 'home url is in err_domains, bypass request: ' . $site_url );
			return false;
		}

		// we don't want the `img_optm-taken` to fail at any given time
		if ( self::IMGOPTM_TAKEN === $service_tag ) {
			return true;
		}

		if ( self::SVC_D_SYNC_CONF === $service_tag && ! $this->activated() ) {
			self::debug( 'Skip sync conf as QC not activated yet.' );
			return false;
		}

		// Check TTL
		if ( ! empty( $this->_summary[ 'ttl.' . $service_tag ] ) ) {
			$ttl = $this->_summary[ 'ttl.' . $service_tag ] - time();
			if ( $ttl > 0 ) {
				self::debug( '❌ TTL limit. [srv] ' . $service_tag . ' [TTL cool down] ' . $ttl . ' seconds' );
				return 'svc_hot';
			}
		}

		$expiration_req = self::EXPIRATION_REQ;
		// Limit frequent unfinished request to 5min
		$timestamp_tag = 'curr';
		if ( self::SVC_IMG_OPTM . '-' . Img_Optm::TYPE_NEW_REQ === $service_tag ) {
			$timestamp_tag = 'last';
		}

		// For all other requests, if is under debug mode, will always allow
		if ( ! $this->conf( self::O_DEBUG ) ) {
			if ( ! empty( $this->_summary[ $timestamp_tag . '_request.' . $service_tag ] ) ) {
				$expired = $this->_summary[ $timestamp_tag . '_request.' . $service_tag ] + $expiration_req - time();
				if ( $expired > 0 ) {
					self::debug( '❌ try [' . $service_tag . '] after ' . $expired . ' seconds' );

					if ( self::API_VER !== $service_tag ) {
						$msg =
							__( 'Cloud Error', 'litespeed-cache' ) .
							': ' .
							sprintf(
								__( 'Please try after %1$s for service %2$s.', 'litespeed-cache' ),
								Utility::readable_time( $expired, 0, true ),
								'<code>' . $service_tag . '</code>'
							);
						Admin_Display::error( [ 'cloud_trylater' => $msg ] );
					}

					return false;
				}
			} else {
				// May fail to store to db if db is oc cached/dead/locked/readonly. Need to store to file to prevent from duplicate calls
				$file_path = $this->_qc_time_file( $service_tag, $timestamp_tag );
				if ( file_exists( $file_path ) ) {
					$last_request = File::read( $file_path );
					$expired      = $last_request + $expiration_req * 10 - time();
					if ( $expired > 0 ) {
						self::debug( '❌ try [' . $service_tag . '] after ' . $expired . ' seconds' );
						return false;
					}
				}
				// For ver check, additional check to prevent frequent calls as old DB ver may be cached
				if ( self::API_VER === $service_tag ) {
					$file_path = $this->_qc_time_file( $service_tag );
					if ( file_exists( $file_path ) ) {
						$last_request = File::read( $file_path );
						$expired      = $last_request + $expiration_req * 10 - time();
						if ( $expired > 0 ) {
							self::debug( '❌❌ Unusual req! try [' . $service_tag . '] after ' . $expired . ' seconds' );
							return false;
						}
					}
				}
			}
		}

		if ( in_array( $service_tag, self::$_pub_svc_set, true ) ) {
			return true;
		}

		if ( ! $this->activated() && self::SVC_D_ACTIVATE !== $service_tag ) {
			Admin_Display::error( Error::msg( 'qc_setup_required' ) );
			return false;
		}

		return true;
	}

	/**
	 * Get QC req ts file path
	 *
	 * @since 7.5
	 *
	 * @param string $service_tag Service tag.
	 * @param string $type        Type: 'last' or 'curr'.
	 * @return string
	 */
	private function _qc_time_file( $service_tag, $type = 'last' ) {
		if ( 'curr' !== $type ) {
			$type = 'last';
		}
		$legacy_file = LITESPEED_STATIC_DIR . '/qc_' . $type . '_request' . md5( $service_tag );
		if ( file_exists( $legacy_file ) ) {
			wp_delete_file( $legacy_file );
		}
		$service_tag = preg_replace( '/[^a-zA-Z0-9]/', '', $service_tag );
		return LITESPEED_STATIC_DIR . '/qc.' . $type . '.' . $service_tag;
	}

	/**
	 * Check if a service tag ttl is valid or not
	 *
	 * @since 7.1
	 *
	 * @param string $service_tag Service tag.
	 * @return int|false Seconds remaining or false if not hot.
	 */
	public function service_hot( $service_tag ) {
		if ( empty( $this->_summary[ 'ttl.' . $service_tag ] ) ) {
			return false;
		}

		$ttl = $this->_summary[ 'ttl.' . $service_tag ] - time();
		if ( $ttl <= 0 ) {
			return false;
		}

		return $ttl;
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
		$data = array(
			'site_url' => site_url(),
			'ver' => LSCWP_V,
			'ref' => $this->_get_ref_url(),
		);
		return $this->_cloud_server_dash . '/u/wp3/manage?data=' . rawurlencode( Utility::arr2str( $data ) ); // . (!empty($this->_summary['is_linked']) ? '?wplogin=1' : '');
	}

	/**
	 * Post data to QUIC.cloud server
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param string     $service  Service name/route.
	 * @param array|bool $data     Payload data or false to omit.
	 * @param int|false  $time_out Timeout seconds or false for default.
	 * @return mixed Response payload or false on failure.
	 */
	public static function post( $service, $data = false, $time_out = false ) {
		$instance = self::cls();
		return $instance->_post( $service, $data, $time_out );
	}

	/**
	 * Post data to cloud server
	 *
	 * @since  3.0
	 * @access private
	 *
	 * @param string     $service  Service name/route.
	 * @param array|bool $data     Payload data or false to omit.
	 * @param int|false  $time_out Timeout seconds or false for default.
	 * @return mixed Response payload or false on failure.
	 */
	private function _post( $service, $data = false, $time_out = false ) {
		$service_tag = $service;
		if ( ! empty( $data['action'] ) ) {
			$service_tag .= '-' . $data['action'];
		}

		$maybe_cloud = $this->_maybe_cloud( $service_tag );
		if ( ! $maybe_cloud || 'svc_hot' === $maybe_cloud ) {
			self::debug( 'Maybe cloud failed: ' . wp_json_encode( $maybe_cloud ) );
			return $maybe_cloud;
		}

		$server = $this->detect_cloud( $service );
		if ( ! $server ) {
			return;
		}

		$url = $server . '/' . $this->_maybe_queue( $service );

		self::debug( 'posting to : ' . $url );

		if ( $data ) {
			$data['service_type'] = $service; // For queue distribution usage
		}

		// Encrypt service as signature
		// $signature_ts = time();
		// $sign_data = [
		// 'service_tag' => $service_tag,
		// 'ts' => $signature_ts,
		// ];
		// $data['signature_b64'] = $this->_sign_b64(implode('', $sign_data));
		// $data['signature_ts'] = $signature_ts;

		self::debug( 'data', $data );
		$param = [
			'site_url'    => site_url(), // Need to use site_url() as WPML case may change home_url() for diff langs (no need to treat as alias for multi langs)
			'main_domain' => ! empty( $this->_summary['main_domain'] ) ? $this->_summary['main_domain'] : '',
			'wp_pk_b64'   => ! empty( $this->_summary['pk_b64'] ) ? $this->_summary['pk_b64'] : '',
			'ver'         => Core::VER,
			'data'        => $data,
		];

		self::save_summary( [ 'curr_request.' . $service_tag => time() ] );
		File::save( $this->_qc_time_file( $service_tag, 'curr' ), time(), true );

		$response = wp_safe_remote_post(
			$url,
			[
				'body'    => $param,
				'timeout' => $time_out ? $time_out : 30,
				'headers' => [
					'Accept' => 'application/json',
					'Expect' => '',
				],
			]
		);

		return $this->_parse_response( $response, $service, $service_tag, $server );
	}

	/**
	 * Parse response JSON
	 * Mark the request successful if the response status is ok
	 *
	 * @since  3.0
	 *
	 * @param array|mixed $response    WP HTTP API response.
	 * @param string      $service     Service name.
	 * @param string      $service_tag Service tag including action.
	 * @param string      $server      Server URL.
	 * @return array|false Parsed JSON array or false on failure.
	 */
	private function _parse_response( $response, $service, $service_tag, $server ) {
		// If show the error or not if failed
		$visible_err = self::API_VER !== $service && self::API_NEWS !== $service && self::SVC_D_DASH !== $service;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			self::debug( 'failed to request: ' . $error_message );

			if ( $visible_err ) {
				$msg = esc_html__( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . esc_html( $error_message ) . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
				Admin_Display::error( $msg );

				// Tmp disabled this node from reusing in 1 day
				if ( empty( $this->_summary['disabled_node'] ) ) {
					$this->_summary['disabled_node'] = [];
				}
				$this->_summary['disabled_node'][ $server ] = time();
				self::save_summary();

				// Force redetect node
				self::debug( 'Node error, redetecting node [svc] ' . $service );
				$this->detect_cloud( $service, true );
			}
			return false;
		}

		$json = \json_decode( $response['body'], true );

		if ( ! is_array( $json ) ) {
			self::debugErr( 'failed to decode response json: ' . $response['body'] );

			if ( $visible_err ) {
				$msg = esc_html__( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . esc_html( $response['body'] ) . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
				Admin_Display::error( $msg );

				// Tmp disabled this node from reusing in 1 day
				if ( empty( $this->_summary['disabled_node'] ) ) {
					$this->_summary['disabled_node'] = [];
				}
				$this->_summary['disabled_node'][ $server ] = time();
				self::save_summary();

				// Force redetect node
				self::debugErr( 'Node error, redetecting node [svc] ' . $service );
				$this->detect_cloud( $service, true );
			}

			return false;
		}

		// Check and save TTL data
		if ( ! empty( $json['_ttl'] ) ) {
			$ttl = (int) $json['_ttl'];
			self::debug( 'Service TTL to save: ' . $ttl );
			if ( $ttl > 0 && $ttl < 86400 ) {
				self::save_summary([
					'ttl.' . $service_tag => $ttl + time(),
				]);
			}
		}

		if ( ! empty( $json['_code'] ) ) {
			self::debugErr( 'Hit err _code: ' . $json['_code'] );
			if ( 'unpulled_images' === $json['_code'] ) {
				$msg = __( 'Cloud server refused the current request due to unpulled images. Please pull the images first.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}
			if ( 'blocklisted' === $json['_code'] ) {
				$msg = __( 'Your domain_key has been temporarily blocklisted to prevent abuse. You may contact support at QUIC.cloud to learn more.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}

			if ( 'rate_limit' === $json['_code'] ) {
				self::debugErr( 'Cloud server rate limit exceeded.' );
				$msg = __( 'Cloud server refused the current request due to rate limiting. Please try again later.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}

			if ( 'heavy_load' === $json['_code'] || 'redetect_node' === $json['_code'] ) {
				// Force redetect node
				self::debugErr( 'Node redetecting node [svc] ' . $service );
				Admin_Display::info( __( 'Redetected node', 'litespeed-cache' ) . ': ' . Error::msg( $json['_code'] ) );
				$this->detect_cloud( $service, true );
			}
		}

		if ( ! empty( $json['_503'] ) ) {
			self::debugErr( 'service 503 unavailable temporarily. ' . $json['_503'] );

			$msg  = __(
				'We are working hard to improve your online service experience. The service will be unavailable while we work. We apologize for any inconvenience.',
				'litespeed-cache'
			);
			$msg .= ' ' . $json['_503'] . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
			Admin_Display::error( $msg );

			// Force redetect node
			self::debugErr( 'Node error, redetecting node [svc] ' . $service );
			$this->detect_cloud( $service, true );

			return false;
		}

		list( $json, $return ) = $this->extract_msg( $json, $service, $server );
		if ( $return ) {
			return false;
		}

		$curr_request = $this->_summary[ 'curr_request.' . $service_tag ];
		self::save_summary([
			'last_request.' . $service_tag => $curr_request,
			'curr_request.' . $service_tag => 0,
		]);
		File::save( $this->_qc_time_file( $service_tag ), $curr_request, true );
		File::save( $this->_qc_time_file( $service_tag, 'curr' ), 0, true );

		if ( $json ) {
			self::debug2( 'response ok', $json );
		} else {
			self::debug2( 'response ok' );
		}

		// Only successful request return Array
		return $json;
	}

	/**
	 * Extract msg from json
	 *
	 * @since 5.0
	 *
	 * @param array       $json        Response JSON.
	 * @param string      $service     Service name.
	 * @param string|bool $server      Server URL or false.
	 * @param bool        $is_callback Whether called from callback context.
	 * @return array Array with [json array, bool should_return_false]
	 */
	public function extract_msg( $json, $service, $server = false, $is_callback = false ) {
		if ( ! empty( $json['_info'] ) ) {
			self::debug( '_info: ' . $json['_info'] );
			$msg  = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json['_info'];
			$msg .= $this->_parse_link( $json );
			Admin_Display::info( $msg );
			unset( $json['_info'] );
		}

		if ( ! empty( $json['_note'] ) ) {
			self::debug( '_note: ' . $json['_note'] );
			$msg  = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json['_note'];
			$msg .= $this->_parse_link( $json );
			Admin_Display::note( $msg );
			unset( $json['_note'] );
		}

		if ( ! empty( $json['_success'] ) ) {
			self::debug( '_success: ' . $json['_success'] );
			$msg  = __( 'Good news from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json['_success'];
			$msg .= $this->_parse_link( $json );
			Admin_Display::success( $msg );
			unset( $json['_success'] );
		}

		// Upgrade is required
		if ( ! empty( $json['_err_req_v'] ) ) {
			self::debug( '_err_req_v: ' . $json['_err_req_v'] );
			$msg = sprintf( __( '%1$s plugin version %2$s required for this action.', 'litespeed-cache' ), Core::NAME, 'v' . $json['_err_req_v'] . '+' ) .
				' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );

			// Append upgrade link
			$msg2 = ' ' . GUI::plugin_upgrade_link( Core::NAME, Core::PLUGIN_NAME, $json['_err_req_v'] );

			$msg2 .= $this->_parse_link( $json );
			Admin_Display::error( $msg . $msg2 );
			return [ $json, true ];
		}

		// Parse _carry_on info
		if ( ! empty( $json['_carry_on'] ) ) {
			self::debug( 'Carry_on usage', $json['_carry_on'] );
			// Store generic info
			foreach ( [ 'usage', 'promo', 'mini_html', 'partner', '_error', '_info', '_note', '_success' ] as $v ) {
				if ( isset( $json['_carry_on'][ $v ] ) ) {
					switch ( $v ) {
						case 'usage':
                        $usage_svc_tag                               = in_array( $service, [ self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI ], true ) ? self::SVC_PAGE_OPTM : $service;
                        $this->_summary[ 'usage.' . $usage_svc_tag ] = $json['_carry_on'][ $v ];
							break;

						case 'promo':
                        if ( empty( $this->_summary[ $v ] ) || ! is_array( $this->_summary[ $v ] ) ) {
								$this->_summary[ $v ] = [];
							}
                        $this->_summary[ $v ][] = $json['_carry_on'][ $v ];
							break;

						case 'mini_html':
                        foreach ( $json['_carry_on'][ $v ] as $k2 => $v2 ) {
								if ( 0 === strpos( $k2, 'ttl.' ) ) {
                                $v2 += time();
									}
								$this->_summary[ $v ][ $k2 ] = $v2;
							}
							break;

						case 'partner':
                        $this->_summary[ $v ] = $json['_carry_on'][ $v ];
							break;

						case '_error':
						case '_info':
						case '_note':
						case '_success':
                        $color_mode = substr( $v, 1 );
                        $msgs       = $json['_carry_on'][ $v ];
                        Admin_Display::add_unique_notice( $color_mode, $msgs, true );
							break;

						default:
							break;
					}
				}
			}
			self::save_summary();
			unset( $json['_carry_on'] );
		}

		// Parse general error msg
		if ( ! $is_callback && ( empty( $json['_res'] ) || 'ok' !== $json['_res'] ) ) {
			$json_msg = ! empty( $json['_msg'] ) ? $json['_msg'] : 'unknown';
			self::debug( '❌ _err: ' . $json_msg, $json );

			$str_translated = Error::msg( $json_msg );
			$msg            = __( 'Failed to communicate with QUIC.cloud server', 'litespeed-cache' ) . ': ' . $str_translated . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
			$msg           .= $this->_parse_link( $json );
			$visible_err    = self::API_VER !== $service && self::API_NEWS !== $service && self::SVC_D_DASH !== $service;
			if ( $visible_err ) {
				Admin_Display::error( $msg );
			}

			// QC may try auto alias
			// Store the domain as `err_domains` only for QC auto alias feature
			if ( 'err_alias' === $json_msg ) {
				if ( empty( $this->_summary['err_domains'] ) ) {
					$this->_summary['err_domains'] = [];
				}
				$site_url = site_url();
				if ( ! array_key_exists( $site_url, $this->_summary['err_domains'] ) ) {
					$this->_summary['err_domains'][ $site_url ] = time();
				}
				self::save_summary();
			}

			// Site not on QC, reset QC connection registration
			if ( 'site_not_registered' === $json_msg || 'err_key' === $json_msg ) {
				$this->_reset_qc_reg();
			}

			return array( $json, true );
		}

		unset( $json['_res'] );
		if ( ! empty( $json['_msg'] ) ) {
			unset( $json['_msg'] );
		}

		return array( $json, false );
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

	/**
	 * REST call: check if the error domain is valid call for auto alias purpose
	 *
	 * @since 5.0
	 */
	public function rest_err_domains() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$alias = !empty( $_POST['alias'] ) ? sanitize_text_field( wp_unslash( $_POST['alias'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['main_domain'] ) || !$alias ) {
			return self::err( 'lack_of_param' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->extract_msg( $_POST, 'Quic.cloud', false, true );

		if ( $this->_is_err_domain( $alias ) ) {
			if ( site_url() === $alias ) {
				$this->_remove_domain_from_err_list( $alias );
			}
			return self::ok();
		}

		return self::err( 'Not an alias req from here' );
	}

	/**
	 * Remove a domain from err domain
	 *
	 * @since 5.0
	 *
	 * @param string $url URL to remove.
	 */
	private function _remove_domain_from_err_list( $url ) {
		unset( $this->_summary['err_domains'][ $url ] );
		self::save_summary();
	}

	/**
	 * Check if is err domain
	 *
	 * @since 5.0
	 *
	 * @param string $site_url Site URL.
	 * @return bool
	 */
	private function _is_err_domain( $site_url ) {
		if ( empty( $this->_summary['err_domains'] ) ) {
			return false;
		}
		if ( ! array_key_exists( $site_url, $this->_summary['err_domains'] ) ) {
			return false;
		}
		// Auto delete if too long ago
		if ( time() - $this->_summary['err_domains'][ $site_url ] > 86400 * 10 ) {
			$this->_remove_domain_from_err_list( $site_url );

			return false;
		}
		if ( time() - $this->_summary['err_domains'][ $site_url ] > 86400 ) {
			return false;
		}
		return true;
	}

	/**
	 * Show promo from cloud
	 *
	 * @since  3.0
	 * @access public
	 */
	public function show_promo() {
		if ( empty( $this->_summary['promo'] ) ) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_promo.tpl.php';
	}

	/**
	 * Clear promo from cloud
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _clear_promo() {
		if ( count( $this->_summary['promo'] ) > 1 ) {
			array_shift( $this->_summary['promo'] );
		} else {
			$this->_summary['promo'] = [];
		}
		self::save_summary();
	}

	/**
	 * Parse _links from json
	 *
	 * @since  1.6.5
	 * @since  1.6.7 Self clean the parameter
	 * @access private
	 *
	 * @param array $json JSON array (passed by reference).
	 * @return string HTML link string.
	 */
	private function _parse_link( &$json ) {
		$msg = '';

		if ( ! empty( $json['_links'] ) ) {
			foreach ( $json['_links'] as $v ) {
				$msg .= ' ' . sprintf( '<a href="%s" class="%s" target="_blank">%s</a>', esc_url( $v['link'] ), ! empty( $v['cls'] ) ? esc_attr( $v['cls'] ) : '', esc_html( $v['title'] ) );
			}

			unset( $json['_links'] );
		}

		return $msg;
	}

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

		return self::ok( array( 'hash' => $resp_hash ) );
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
			if ( empty( $this->_summary['ips_ts_runner'] ) || time() - $this->_summary['ips_ts_runner'] > 600 ) {
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
		self::save_summary( array( 'ips' => $json ) );
	}

	/**
	 * Return succeeded response
	 *
	 * @since  3.0
	 *
	 * @param array $data Additional data.
	 * @return array
	 */
	public static function ok( $data = [] ) {
		$data['_res'] = 'ok';
		return $data;
	}

	/**
	 * Return error
	 *
	 * @since  3.0
	 *
	 * @param string $code Error code.
	 * @return array
	 */
	public static function err( $code ) {
		self::debug( '❌ Error response code: ' . $code );
		return array(
			'_res' => 'err',
			'_msg' => $code,
		);
	}

	/**
	 * Return pong for ping to check PHP function availability
	 *
	 * @since 6.5
	 *
	 * @return array
	 */
	public function ping() {
		$resp = array(
			'v_lscwp'     => Core::VER,
			'v_lscwp_db'  => $this->conf( self::_VER ),
			'v_php'       => PHP_VERSION,
			'v_wp'        => $GLOBALS['wp_version'],
			'home_url'    => home_url(),
			'site_url'    => site_url(),
		);
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

	/**
	 * Display a banner for dev env if using preview QC node.
	 *
	 * @since 7.0
	 */
	public function maybe_preview_banner() {
		if ( false !== strpos( $this->_cloud_server, 'preview.' ) ) {
			Admin_Display::note( __( 'Linked to QUIC.cloud preview environment, for testing purpose only.', 'litespeed-cache' ), true, true, 'litespeed-warning-bg' );
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_CLEAR_CLOUD:
            $this->clear_cloud();
				break;

			case self::TYPE_REDETECT_CLOUD:
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $svc = ! empty( $_GET['svc'] ) ? sanitize_text_field( wp_unslash( $_GET['svc'] ) ) : '';
            if ( $svc ) {
					$this->detect_cloud( $svc, true );
				}
				break;

			case self::TYPE_CLEAR_PROMO:
            $this->_clear_promo();
				break;

			case self::TYPE_RESET:
            $this->reset_qc();
				break;

			case self::TYPE_ACTIVATE:
            $this->init_qc();
				break;

			case self::TYPE_LINK:
            $this->link_qc();
				break;

			case self::TYPE_ENABLE_CDN:
            $this->enable_cdn();
				break;

			case self::TYPE_API:
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $action2 = ! empty( $_GET['action2'] ) ? sanitize_text_field( wp_unslash( $_GET['action2'] ) ) : '';
            if ( $action2 ) {
					$this->api_link_call( $action2 );
				}
				break;

			case self::TYPE_SYNC_STATUS:
            $this->load_qc_status_for_dash( 'cdn_dash', true );
            $msg = __( 'Sync QUIC.cloud status successfully.', 'litespeed-cache' );
            Admin_Display::success( $msg );
				break;

			case self::TYPE_SYNC_USAGE:
            $this->sync_usage();

            $msg = __( 'Sync credit allowance with Cloud Server successfully.', 'litespeed-cache' );
            Admin_Display::success( $msg );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
