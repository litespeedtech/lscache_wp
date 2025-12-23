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
	use Cloud_Auth;
	use Cloud_Request;
	use Cloud_Node;
	use Cloud_Misc;

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
	protected $_cloud_server_wp = 'https://wpapi.quic.cloud';

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
	const SVC_OPTIMAX          = 'optimax';

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
		self::SVC_OPTIMAX,
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
			if ( ! is_array ( $hosts ) ) {
				$hosts = [];
			}

			return array_merge( $hosts, $allowed_hosts );
		} );
		$this->_summary = self::get_summary();
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
		return [
			'_res' => 'err',
			'_msg' => $code,
		];
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
