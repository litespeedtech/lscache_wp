<?php
/**
 * Cloud service cls
 *
 * @since      3.0
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Cloud extends Base
{
	protected static $_instance;

	const DB_HASH = 'hash';

	const CLOUD_SERVER = 'https://apidev.quic.cloud';

	const SVC_D_NODES 			= 'd/nodes';
	const SVC_D_SYNC_CONF 		= 'd/sync_conf';
	const SVC_D_USAGE 			= 'd/usage';
	const SVC_CCSS 				= 'ccss' ;
	const SVC_PLACEHOLDER 		= 'placeholder' ;
	const SVC_LQIP 				= 'lqip' ;
	const SVC_IMG_OPTM			= 'img_optm' ;
	const SVC_PAGESCORE			= 'pagescore' ;
	const SVC_CDN				= 'cdn' ;

	const API_NEWS 			= 'wp/news';
	const API_REPORT		= 'wp/report' ;
	const API_VER			= 'wp/ver' ;
	const API_BETA_TEST		= 'wp/beta_test' ;

	private static $CENTER_SVC_SET = array(
		self::SVC_D_NODES,
		self::SVC_D_SYNC_CONF,
		self::SVC_D_USAGE,
		self::API_NEWS,
		self::API_REPORT,
		self::API_VER,
		self::API_BETA_TEST,
	);

	// No api key needed for these services
	private static $_PUB_SVC_SET = array(
		self::API_NEWS,
		self::API_REPORT,
		self::API_VER,
		self::API_BETA_TEST,
	);

	public static $SERVICES = array(
		self::SVC_IMG_OPTM,
		self::SVC_CCSS,
		self::SVC_LQIP,
		self::SVC_CDN,
		self::SVC_PLACEHOLDER,
		self::SVC_PAGESCORE,
		'sitehealth',
	);

	const TYPE_REDETECT_CLOUD 	= 'redetect_cloud';
	const TYPE_GEN_KEY 			= 'gen_key';
	const TYPE_SYNC_USAGE 		= 'sync_usage';

	private $_api_key;
	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	protected function __construct()
	{
		$this->_api_key = Conf::val( Base::O_API_KEY );
		$this->_summary = self::get_summary();
	}

	/**
	 * Show latest news
	 *
	 * @since 3.0
	 */
	public function news()
	{
		$this->_update_news();

		if ( empty( $this->_summary[ 'news.new' ] ) ) {
			return;
		}

		if ( ! empty( $this->_summary[ 'news.plugin' ] ) && Activation::get_instance()->dash_notifier_is_plugin_active( $this->_summary[ 'news.plugin' ] ) ) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_news.tpl.php' ;
	}

	/**
	 * Update latest news
	 *
	 * @since 2.9.9.1
	 */
	private function _update_news()
	{
		if ( ! empty( $this->_summary[ 'news.utime' ] ) && time() - $this->_summary[ 'news.utime' ] < 86400 * 3 ) {
			return;
		}

		$this->_summary[ 'news.utime' ] = time();
		self::save_summary();

		$data = self::get( self::API_NEWS );
		if ( empty( $data[ 'id' ] ) ) {
			return;
		}

		// Save news
		if ( ! empty( $this->_summary[ 'news.id' ] ) && $this->_summary[ 'news.id' ] == $data[ 'id' ] ) {
			return;
		}

		$this->_summary[ 'news.id' ] = $data[ 'id' ];
		$this->_summary[ 'news.plugin' ] = ! empty( $data[ 'plugin' ] ) ? $data[ 'plugin' ] : '';
		$this->_summary[ 'news.title' ] = ! empty( $data[ 'title' ] ) ? $data[ 'title' ] : '';
		$this->_summary[ 'news.content' ] = ! empty( $data[ 'content' ] ) ? $data[ 'content' ] : '';
		$this->_summary[ 'news.zip' ] = ! empty( $data[ 'zip' ] ) ? $data[ 'zip' ] : '';
		$this->_summary[ 'news.new' ] = 1;

		if ( $this->_summary[ 'news.plugin' ] ) {
			$plugin_info = Activation::get_instance()->dash_notifier_get_plugin_info( $this->_summary[ 'news.plugin' ] );
			if ( $plugin_info && ! empty( $plugin_info->name ) ) {
				$this->_summary[ 'news.plugin_name' ] = $plugin_info->name;
			}
		}

		self::save_summary();
	}

	/**
	 * Get allowance of current service
	 *
	 * @since  3.0
	 * @access private
	 */
	public function allowance( $service )
	{
		// Only auto sync usage at most one time per day
		if ( empty( $this->_summary[ 'last_request.' . self::SVC_D_USAGE ] ) || time() - $this->_summary[ 'last_request.' . self::SVC_D_USAGE ] > 86400 ) {
			$this->sync_usage();
		}

		if ( empty( $this->_summary[ 'usage.' . $service ] ) ) {
			return 0;
		}

		$allowance = $this->_summary[ 'usage.' . $service ][ 'quota' ] - $this->_summary[ 'usage.' . $service ][ 'used' ];

		if ( $allowance > 0 ) {
			return $allowance;
		}

		// Check if to use account level credit or not
		if ( empty( $this->_summary[ 'credit_quota' ] ) ) {
			return 0;
		}

		$allowance = $this->_summary[ 'credit_quota' ];

		// Check domain cap limit
		if ( $this->_summary[ 'domain_cap' ] > 0 ) {
			$cap_allowance = $this->_summary[ 'domain_cap' ];
			if ( ! empty( $this->_summary[ 'credit_used' ] ) ) {
				$cap_allowance -= $this->_summary[ 'credit_used' ];
			}
			if ( $allowance > $cap_allowance ) {
				$allowance = $cap_allowance;
			}
		}

		return $allowance;
	}

	/**
	 * Sync Cloud usage summary data
	 *
	 * @since  3.0
	 * @access public
	 */
	public function sync_usage()
	{
		$usage = $this->_post( self::SVC_D_USAGE );
		if ( ! $usage ) {
			return;
		}

		Log::debug( '[Cloud] sync_usage ' . json_encode( $usage ) );

		foreach ( self::$SERVICES as $v ) {
			$this->_summary[ 'usage.' . $v ] = ! empty( $usage[ $v ] ) ? $usage[ $v ] : false;
		}

		self::save_summary();

		return $this->_summary;
	}

	/**
	 * ping clouds to find the fastest node
	 *
	 * @since  3.0
	 * @access public
	 */
	public function detect_cloud( $service, $force = false )
	{
		if ( in_array( $service, self::$CENTER_SVC_SET ) ) {
			return self::CLOUD_SERVER;
		}

		// Check if the stored server needs to be refreshed
		if ( ! $force ) {
			if ( ! empty( $this->_summary[ 'server.' . $service ] ) && ! empty( $this->_summary[ 'server_date.' . $service ] ) && $this->_summary[ 'server_date.' . $service ] < time() + 86400 * 30 ) {
				return $this->_summary[ 'server.' . $service ];
			}
		}

		if ( ! $service || ! in_array( $service, self::$SERVICES ) ) {
			$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . $service;
			Admin_Display::error( $msg );
			return false;
		}

		// Send request to Quic Online Service
		$json = $this->_post( self::SVC_D_NODES, array( 'svc' => $service ) );

		// Check if get list correctly
		if ( empty( $json[ 'list' ] ) || ! is_array( $json[ 'list' ] ) ) {
			Log::debug( '[Cloud] request cloud list failed: ', $json );

			if ( $json ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . $json;
				Admin_Display::error( $msg );
			}
			return false;
		}

		// Ping closest cloud
		$speed_list = array();
		foreach ( $json[ 'list' ] as $v ) {
			$speed_list[ $v ] = Utility::ping( $v );
		}
		$min = min( $speed_list );

		if ( $min == 99999 ) {
			Log::debug( '[Cloud] failed to ping all clouds' );
			return false;
		}

		// Random pick same time range ip (230ms 250ms)
		$range_len = strlen( $min );
		$range_num = substr( $min, 0, 1 );
		$valid_clouds = array();
		foreach ($speed_list as $node => $speed ) {
			if ( strlen( $speed ) == $range_len && substr( $speed, 0, 1 ) == $range_num ) {
				$valid_clouds[] = $node;
			}
		}

		if ( ! $valid_clouds ) {
			$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . __( 'No available Cloud Node.', 'litespeed-cache' );
			Admin_Display::error( $msg );
			return false;
		}

		Log::debug( '[Cloud] Closest nodes list', $valid_clouds );

		$closest = $valid_clouds[ array_rand( $valid_clouds ) ];

		Log::debug( '[Cloud] Chose node: ' . $closest );

		// store data into option locally
		$this->_summary[ 'server.' . $service ] = $closest;
		$this->_summary[ 'server_date.' . $service ] = time();
		self::save_summary();

		return $this->_summary[ 'server.' . $service ];
	}

	/**
	 * Get data from QUIC cloud server
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function get( $service, $data = array() )
	{
		$instance = self::get_instance();
		return $instance->_get( $service, $data );
	}

	/**
	 * Get data from QUIC cloud server
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _get( $service, $data = false )
	{
		$service_tag = $service;
		if ( ! empty( $data[ 'action' ] ) ) {
			$service_tag .= '-' . $data[ 'action' ];
		}

		if ( ! $this->_maybe_cloud( $service_tag ) ) {
			return;
		}

		$server = $this->detect_cloud( $service );
		if ( ! $server ) {
			return;
		}

		$url = $server . '/' . $service;

		$param = array(
			'site_url'		=> home_url(),
			'domain_key'	=> $this->_api_key,
			'ver'			=> Core::VER,
		);

		if ( $data ) {
			$param[ 'data' ] = $data;
		}

		$url .= '?' . http_build_query( $param );

		Log::debug( '[Cloud] getting from : ' . $url );

		$this->_summary[ 'curr_request.' . $service_tag ] = time();
		self::save_summary();

		$response = wp_remote_get( $url, array( 'timeout' => 15, 'sslverify' => false ) );

		return $this->_parse_response( $response, $service, $service_tag );
	}

	/**
	 * Check if is able to do cloud request or not
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _maybe_cloud( $service_tag )
	{
		// Limit frequent unfinished request to 5min
		if ( ! empty( $this->_summary[ 'curr_request.' . $service_tag ] ) ) {
			$expired = $this->_summary[ 'curr_request.' . $service_tag ] + 300 - time();
			if ( $expired > 0 ) {
				Log::debug( "[Cloud] ❌ try [$service_tag] after $expired seconds" );

				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . sprintf( __( 'Please try after %1$s for service %2$s.', 'litespeed-cache' ), Utility::readable_time( $expired, 0, 0 ), '<code>' . $service_tag . '</code>' );
				Admin_Display::error( $msg );
				return false;
			}
		}

		if ( in_array( $service_tag, self::$_PUB_SVC_SET ) ) {
			return true;
		}

		if ( ! $this->_api_key ) {
			$msg = sprintf( __( 'The Cloud API key need to be set first to use online service. <a %s>Click here to Setting page</a>.', 'litespeed-cache' ), ' href="' . admin_url('admin.php?page=litespeed-general') . '" ' );
			Admin_Display::error( $msg );
			return false;
		}

		return true;
	}

	/**
	 * Post data to QUIC.cloud server
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function post( $service, $data = false, $time_out = false, $need_hash = false )
	{
		$instance = self::get_instance();
		return $instance->_post( $service, $data, $time_out, $need_hash );
	}

	/**
	 * Post data to cloud server
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _post( $service, $data = false, $time_out = false, $need_hash = false )
	{
		$service_tag = $service;
		if ( ! empty( $data[ 'action' ] ) ) {
			$service_tag .= '-' . $data[ 'action' ];
		}

		if ( ! $this->_maybe_cloud( $service_tag ) ) {
			return;
		}

		$server = $this->detect_cloud( $service );
		if ( ! $server ) {
			return;
		}

		$url = $server . '/' . $service;

		Log::debug( '[Cloud] posting to : ' . $url );

		$param = array(
			'site_url'		=> home_url(),
			'domain_key'	=> $this->_api_key,
			'ver'			=> Core::VER,
			'data' 			=> $data,
		);
		if ( $need_hash ) {
			$param[ 'hash' ] = $this->_hash_make();
		}
		/**
		 * Extended timeout to avoid cUrl 28 timeout issue as we need callback validation
		 * @since 1.6.4
		 */
		$this->_summary[ 'curr_request.' . $service_tag ] = time();
		self::save_summary();

		$response = wp_remote_post( $url, array( 'body' => $param, 'timeout' => $time_out ?: 15, 'sslverify' => false ) );

		return $this->_parse_response( $response, $service, $service_tag );
	}

	/**
	 * Parse response JSON
	 * Mark the request successful if the response status is ok
	 *
	 * @since  3.0
	 */
	private function _parse_response( $response, $service, $service_tag )
	{
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			Log::debug( '[Cloud] failed to request: ' . $error_message );

			$msg = __( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . $error_message;
			Admin_Display::error( $msg );
			return false;
		}

		$json = json_decode( $response[ 'body' ], true );

		if ( ! is_array( $json ) ) {
			Log::debug( '[Cloud] failed to decode response json: ' . $response[ 'body' ] );

			$msg = __( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . $response[ 'body' ];
			Admin_Display::error( $msg );

			return false;
		}

		if ( ! empty( $json[ '_503' ] ) ) {
			Log::debug( '[Cloud] service 503 unavailable temporarily. ' . $json[ '_503' ] );

			$msg = __( 'We are working hard to improve your online service experience. The service will be unavailable while we work. We apologize for any inconvenience.', 'litespeed-cache' );
			$msg .= ' ' . $json[ '_503' ];
			Admin_Display::error( $msg );

			return false;
		}

		if ( ! empty( $json[ '_info' ] ) ) {
			Log::debug( '[Cloud] _info: ' . $json[ '_info' ] );
			$msg = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json[ '_info' ];
			$msg .= $this->_parse_link( $json );
			Admin_Display::info( $msg );
			unset( $json[ '_info' ] );
		}

		if ( ! empty( $json[ '_note' ] ) ) {
			Log::debug( '[Cloud] _note: ' . $json[ '_note' ] );
			$msg = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json[ '_note' ];
			$msg .= $this->_parse_link( $json );
			Admin_Display::note( $msg );
			unset( $json[ '_note' ] );
		}

		if ( ! empty( $json[ '_success' ] ) ) {
			Log::debug( '[Cloud] _success: ' . $json[ '_success' ] );
			$msg = __( 'Good news from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json[ '_success' ];
			$msg .= $this->_parse_link( $json );
			Admin_Display::succeed( $msg );
			unset( $json[ '_success' ] );
		}

		// Upgrade is required
		if ( ! empty( $json[ '_err_req_v' ] ) ) {
			Log::debug( '[Cloud] _err_req_v: ' . $json[ '_err_req_v' ] );
			$msg = sprintf( __( '%1$s plugin version %2$s required for this action.', 'litespeed-cache' ), Core::NAME, 'v' . $json[ '_err_req_v' ] . '+' );

			// Append upgrade link
			$msg2 = ' ' . GUI::plugin_upgrade_link( Core::NAME, Core::PLUGIN_NAME, $json[ '_err_req_v' ] );

			$msg2 .= $this->_parse_link( $json );
			Admin_Display::error( $msg . $msg2 );
			return false;
		}

		// Parse _carry_on info
		if ( ! empty( $json[ '_carry_on' ] ) ) {
			// Store generic info
			foreach ( array( 'usage', 'domain_cap', 'credit_used', 'credit_quota', 'promo' ) as $v ) {
				if ( ! empty( $json[ '_carry_on' ][ $v ] ) ) {
					switch ( $v ) {
						case 'usage':
							$this->_summary[ 'usage.' . $service ] = $json[ '_carry_on' ][ $v ];
							break;

						case 'promo':
							if ( empty( $this->_summary[ $v ] ) || ! is_array( $this->_summary[ $v ] ) ) {
								$this->_summary[ $v ] = array();
							}
							$this->_summary[ $v ][] = $json[ '_carry_on' ][ $v ];
							break;

						case 'domain_cap':
						case 'credit_used':
						case 'credit_quota':
							$this->_summary[ $v ] = $json[ '_carry_on' ][ $v ];
							break;

						default:
							break;
					}
				}
			}
			self::save_summary();
			unset( $json[ '_carry_on' ] );
		}

		// Parse general error msg
		if ( empty( $json[ '_res' ] ) || $json[ '_res' ] !== 'ok' ) {
			$json_msg = ! empty( $json[ '_msg' ] ) ? $json[ '_msg' ] : 'unknown';
			Log::debug( '[Cloud] ❌ _err: ' . $json_msg );

			$msg = __( 'Failed to communicate with QUIC.cloud server', 'litespeed-cache' ) . ': ' . Error::msg( $json_msg );
			$msg .= $this->_parse_link( $json );
			Admin_Display::error( $msg );

			return false;
		}

		unset( $json[ '_res' ] );
		if ( ! empty( $json[ '_msg' ] ) ) {
			unset( $json[ '_msg' ] );
		}

		$this->_summary[ 'last_request.' . $service_tag ] = $this->_summary[ 'curr_request.' . $service_tag ];
		$this->_summary[ 'curr_request.' . $service_tag ] = 0;
		self::save_summary();

		if ( $json ) {
			Log::debug2( '[Cloud] response ok', $json );
		}
		else {
			Log::debug2( '[Cloud] response ok' );
		}

		return $json;

	}

	/**
	 * Parse _links from json
	 *
	 * @since  1.6.5
	 * @since  1.6.7 Self clean the parameter
	 * @access private
	 */
	private function _parse_link( &$json )
	{
		$msg = '';

		if ( ! empty( $json[ '_links' ] ) ) {
			foreach ( $json[ '_links' ] as $v ) {
				$msg .= ' ' . sprintf( '<a href="%s" class="%s" target="_blank">%s</a>', $v[ 'link' ], ! empty( $v[ 'cls' ] ) ? $v[ 'cls' ] : '', $v[ 'title' ] );
			}

			unset( $json[ '_links' ] );
		}

		return $msg;
	}

	/**
	 * Request callback validation from Cloud
	 *
	 * @since  1.5
	 * @access public
	 */
	public function hash()
	{
		if ( empty( $_POST[ 'hash' ] ) ) {
			Log::debug( '[Cloud] Lack of hash param' );
			return self::err( 'lack_of_param' );
		}

		$key_hash = self::get_option( self::DB_HASH );
		if ( $key_hash ) { // One time usage only
			self::delete_option( self::DB_HASH );
		}

		if ( ! $key_hash || $_POST[ 'hash' ] !== md5( $key_hash ) ) {
			Log::debug( '[Cloud] __callback request hash wrong: md5(' . $key_hash . ') !== ' . $_POST[ 'hash' ] );
			return self::err( 'Error hash code' );
		}

		Control::set_nocache( 'Cloud hash validation' );

		Log::debug( '[Cloud] __callback request hash: ' . $key_hash );


		return array( 'hash' => $key_hash );
	}

	/**
	 * Redirect to QUIC to get key, if is CLI, get json [ 'domain_key' => 'asdfasdf' ]
	 *
	 * @since  3.0
	 * @access public
	 */
	public function gen_key()
	{
		$data = array(
			'hash'		=> $this->_hash_make(),
			'site_url'	=> home_url(),
			'email'		=> get_bloginfo( 'admin_email' ),
			'rest'		=> rest_get_url_prefix(),
			'src'		=> defined( 'LITESPEED_CLI' ) ? 'CLI' : 'web',
		);

		if ( ! defined( 'LITESPEED_CLI' ) ) {
			$data[ 'ref' ] = $_SERVER[ 'HTTP_REFERER' ];
			wp_redirect( self::CLOUD_SERVER . '/d/req_key?data=' . Utility::arr2str( $data ) );
			exit;
		}

		// CLI handler
		$response = wp_remote_get( self::CLOUD_SERVER . '/d/req_key?data=' . Utility::arr2str( $data ), array( 'timeout' => 300 ) );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			Log::debug( '[CLoud] failed to gen_key: ' . $error_message );
			Admin_Display::error( __( 'CLoud Error', 'litespeed-cache' ) . ': ' . $error_message );
			return;
		}

		$json = json_decode( $response[ 'body' ], true );
		if ( empty( $json[ '_res' ] ) || $json[ '_res' ] != 'ok' ) {
			Log::debug( '[CLoud] error to gen_key: ', $json );
			Admin_Display::error( __( 'CLoud Error', 'litespeed-cache' ) . ': ' . ( ! empty( $json[ '_msg' ] ) ? $json[ '_msg' ] : var_export( $json, true ) ) );
			return;
		}

		// Save domain_key option
		$this->_save_api_key( $json[ 'domain_key' ] );

		Admin_Display::succeed( __( 'Generate API key successfully.', 'litespeed-cache' ) );

		return $json[ 'domain_key' ];
	}

	/**
	 * Make a hash for callback validation
	 *
	 * @since  3.0
	 */
	private function _hash_make()
	{
		$hash = Str::rrand( 16 );
		// store hash
		self::update_option( self::DB_HASH, $hash );

		return $hash;
	}

	/**
	 * Callback after generated key from QUIC.cloud
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _save_api_key( $api_key )
	{
		// This doesn't need to sync QUIC conf
		Conf::get_instance()->update( Base::O_API_KEY, $api_key );

		Log::debug( '[Cloud] saved auth_key' );
	}

	/**
	 * Return succeeded response
	 *
	 * @since  3.0
	 */
	public static function ok( $data = array() )
	{
		$data[ '_res' ] = 'ok';
		return $data;
	}

	/**
	 * Return error
	 *
	 * @since  3.0
	 */
	public static function err( $code )
	{
		return array( '_res' => 'err', '_msg' => $code );
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance();

		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_REDETECT_CLOUD :
				if ( ! empty( $_GET[ 'svc' ] ) ) {
					$instance->detect_cloud( $_GET[ 'svc' ], true );
				}
				break;

			case self::TYPE_GEN_KEY :
				$instance->gen_key();
				break;

			case self::TYPE_SYNC_USAGE :
				$instance->sync_usage();

				$msg = __( 'Sync credit allowance with Cloud Server successfully.', 'litespeed-cache' ) ;
				Admin_Display::succeed( $msg ) ;
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}