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

	const CLOUD_SERVER = 'https://api.dev.quic.cloud';
	const CLOUD_SERVER_DASH = 'https://my.dev.quic.cloud';

	const SVC_D_NODES 			= 'd/nodes';
	const SVC_D_SYNC_CONF 		= 'd/sync_conf';
	const SVC_D_USAGE 			= 'd/usage';
	const SVC_CCSS 				= 'ccss' ;
	const SVC_PLACEHOLDER 		= 'placeholder' ;
	const SVC_LQIP 				= 'lqip' ;
	const SVC_IMG_OPTM			= 'img_optm' ;
	const SVC_HEALTH			= 'health' ;
	const SVC_CDN				= 'cdn' ;

	const BM_IMG_OPTM_JUMBO_GROUP = 32;
	const IMG_OPTM_JUMBO_GROUP = 1000;
	const IMG_OPTM_DEFAULT_GROUP = 200;

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
		self::SVC_HEALTH,
	);

	const TYPE_CLEAR_PROMO 	= 'clear_promo';
	const TYPE_REDETECT_CLOUD 	= 'redetect_cloud';
	const TYPE_CLEAR_CLOUD 		= 'clear_cloud';
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
	 * Show latest commit version always if is on dev
	 *
	 * @since 3.0
	 */
	public function check_dev_version()
	{
		if ( ! preg_match( '/[^\d\.]/', Core::VER ) ) {
			return;
		}

		$last_check = empty( $this->_summary[ 'last_request.' . self::API_VER ] ) ? 0 : $this->_summary[ 'last_request.' . self::API_VER ] ;

		if ( time() - $last_check > 600 ) {
			$auto_v = self::version_check( 'dev' );
			if ( ! empty( $auto_v[ 'dev' ] ) ) {
				$this->_summary[ 'version.dev' ] = $auto_v[ 'dev' ];
				self::save_summary( $this->_summary );
			}
		}

		if ( empty( $this->_summary[ 'version.dev' ] ) ) {
			return;
		}

		Debug2::debug( '🌤️  Latest dev version ' . $this->_summary[ 'version.dev' ] );

		if ( version_compare( $this->_summary[ 'version.dev' ], Core::VER, '<=' ) ) {
			return;
		}

		// Show the dev banner
		require_once LSCWP_DIR . 'tpl/banner/new_version_dev.tpl.php';
	}

	/**
	 * Check latest version
	 *
	 * @since  2.9
	 * @access public
	 */
	public static function version_check( $src = false )
	{
		$req_data = array(
			'v'		=> defined( 'LSCWP_CUR_V' ) ? LSCWP_CUR_V : '',
			'src'	=> $src,
		);
		if ( defined( 'LITESPEED_ERR' ) ) {
			$req_data[ 'err' ] = base64_encode( ! is_string( LITESPEED_ERR ) ? json_encode( LITESPEED_ERR ) : LITESPEED_ERR ) ;
		}
		$data = self::get( self::API_VER, $req_data );

		return $data;
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

		// Image optm is always free
		$allowance_max = 0;
		if ( $service == self::SVC_IMG_OPTM ) {
			$allowance_max = self::IMG_OPTM_DEFAULT_GROUP;
			if ( ! empty( $this->_summary[ 'usage.' . $service ][ 'pkgs' ] ) && $this->_summary[ 'usage.' . $service ][ 'pkgs' ] & self::BM_IMG_OPTM_JUMBO_GROUP ) {
				$allowance_max = self::IMG_OPTM_JUMBO_GROUP;
			}
		}

		$allowance = $this->_summary[ 'usage.' . $service ][ 'quota' ] - $this->_summary[ 'usage.' . $service ][ 'used' ];

		if ( $allowance > 0 ) {
			if ( $allowance_max && $allowance_max < $allowance ) {
				return $allowance_max;
			}
			return $allowance;
		}

		// Check Pay As You Go balance
		if ( empty( $this->_summary[ 'usage.' . $service ][ 'pag_bal' ] ) ) {
			return $allowance_max;
		}

		if ( $allowance_max && $allowance_max < $this->_summary[ 'usage.' . $service ][ 'pag_bal' ] ) {
			return $allowance_max;
		}

		return $this->_summary[ 'usage.' . $service ][ 'pag_bal' ];
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

		Debug2::debug( '🌤️  sync_usage ' . json_encode( $usage ) );

		foreach ( self::$SERVICES as $v ) {
			$this->_summary[ 'usage.' . $v ] = ! empty( $usage[ $v ] ) ? $usage[ $v ] : false;
		}

		self::save_summary();

		return $this->_summary;
	}

	/**
	 * Clear all existing cloud nodes for future reconnect
	 *
	 * @since  3.0
	 * @access public
	 */
	public function clear_cloud()
	{
		foreach ( self::$SERVICES as $service ) {
			if ( isset( $this->_summary[ 'server.' . $service ] ) ) {
				unset( $this->_summary[ 'server.' . $service ] );
			}
			if ( isset( $this->_summary[ 'server_date.' . $service ] ) ) {
				unset( $this->_summary[ 'server_date.' . $service ] );
			}
		}
		self::save_summary();
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
			Debug2::debug( '🌤️  request cloud list failed: ', $json );

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
			Debug2::debug( '🌤️  failed to ping all clouds' );
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

		Debug2::debug( '🌤️  Closest nodes list', $valid_clouds );

		$closest = $valid_clouds[ array_rand( $valid_clouds ) ];

		Debug2::debug( '🌤️  Chose node: ' . $closest );

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

		Debug2::debug( '🌤️  getting from : ' . $url );

		$this->_summary[ 'curr_request.' . $service_tag ] = time();
		self::save_summary();

		$response = wp_remote_get( $url, array( 'timeout' => 15, 'sslverify' => false ) );

		return $this->_parse_response( $response, $service, $service_tag, $server );
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
			$expired = $this->_summary[ 'curr_request.' . $service_tag ] + 30 - time(); // todo: 300
			if ( $expired > 0 ) {
				Debug2::debug( "[Cloud] ❌ try [$service_tag] after $expired seconds" );

				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . sprintf( __( 'Please try after %1$s for service %2$s.', 'litespeed-cache' ), Utility::readable_time( $expired, 0, 0 ), '<code>' . $service_tag . '</code>' );
				Admin_Display::error( $msg );
				return false;
			}
		}

		if ( in_array( $service_tag, self::$_PUB_SVC_SET ) ) {
			return true;
		}

		if ( ! $this->_api_key ) {
			Admin_Display::error( Error::msg( 'lack_of_api_key' ) );
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

		Debug2::debug( '🌤️  posting to : ' . $url );

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

		return $this->_parse_response( $response, $service, $service_tag, $server );
	}

	/**
	 * Parse response JSON
	 * Mark the request successful if the response status is ok
	 *
	 * @since  3.0
	 */
	private function _parse_response( $response, $service, $service_tag, $server )
	{
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			Debug2::debug( '🌤️  failed to request: ' . $error_message );

			if ( $service !== self::API_VER ) {
				$msg = __( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . $error_message . " [server] $server [service] $service";
				Admin_Display::error( $msg );
			}
			return;
		}

		$json = json_decode( $response[ 'body' ], true );

		if ( ! is_array( $json ) ) {
			Debug2::debug( '🌤️  failed to decode response json: ' . $response[ 'body' ] );

			if ( $service !== self::API_VER ) {
				$msg = __( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . $response[ 'body' ] . " [server] $server [service] $service";
				Admin_Display::error( $msg );
			}

			return;
		}

		if ( ! empty( $json[ '_503' ] ) ) {
			Debug2::debug( '🌤️  service 503 unavailable temporarily. ' . $json[ '_503' ] );

			$msg = __( 'We are working hard to improve your online service experience. The service will be unavailable while we work. We apologize for any inconvenience.', 'litespeed-cache' );
			$msg .= ' ' . $json[ '_503' ] . " [server] $server [service] $service";
			Admin_Display::error( $msg );

			return;
		}

		if ( ! empty( $json[ '_info' ] ) ) {
			Debug2::debug( '🌤️  _info: ' . $json[ '_info' ] );
			$msg = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json[ '_info' ];
			$msg .= $this->_parse_link( $json );
			Admin_Display::info( $msg );
			unset( $json[ '_info' ] );
		}

		if ( ! empty( $json[ '_note' ] ) ) {
			Debug2::debug( '🌤️  _note: ' . $json[ '_note' ] );
			$msg = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json[ '_note' ];
			$msg .= $this->_parse_link( $json );
			Admin_Display::note( $msg );
			unset( $json[ '_note' ] );
		}

		if ( ! empty( $json[ '_success' ] ) ) {
			Debug2::debug( '🌤️  _success: ' . $json[ '_success' ] );
			$msg = __( 'Good news from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json[ '_success' ];
			$msg .= $this->_parse_link( $json );
			Admin_Display::succeed( $msg );
			unset( $json[ '_success' ] );
		}

		// Upgrade is required
		if ( ! empty( $json[ '_err_req_v' ] ) ) {
			Debug2::debug( '🌤️  _err_req_v: ' . $json[ '_err_req_v' ] );
			$msg = sprintf( __( '%1$s plugin version %2$s required for this action.', 'litespeed-cache' ), Core::NAME, 'v' . $json[ '_err_req_v' ] . '+' ) . " [server] $server [service] $service";

			// Append upgrade link
			$msg2 = ' ' . GUI::plugin_upgrade_link( Core::NAME, Core::PLUGIN_NAME, $json[ '_err_req_v' ] );

			$msg2 .= $this->_parse_link( $json );
			Admin_Display::error( $msg . $msg2 );
			return;
		}

		// Parse _carry_on info
		if ( ! empty( $json[ '_carry_on' ] ) ) {
			Debug2::debug( '🌤️  Carry_on usage', $json[ '_carry_on' ] );
			// Store generic info
			foreach ( array( 'usage', 'promo' ) as $v ) {
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
			Debug2::debug( '🌤️  ❌ _err: ' . $json_msg );

			$msg = __( 'Failed to communicate with QUIC.cloud server', 'litespeed-cache' ) . ': ' . Error::msg( $json_msg ) . " [server] $server [service] $service";
			$msg .= $this->_parse_link( $json );
			Admin_Display::error( $msg );

			return;
		}

		unset( $json[ '_res' ] );
		if ( ! empty( $json[ '_msg' ] ) ) {
			unset( $json[ '_msg' ] );
		}

		$this->_summary[ 'last_request.' . $service_tag ] = $this->_summary[ 'curr_request.' . $service_tag ];
		$this->_summary[ 'curr_request.' . $service_tag ] = 0;
		self::save_summary();

		if ( $json ) {
			Debug2::debug2( '[Cloud] response ok', $json );
		}
		else {
			Debug2::debug2( '[Cloud] response ok' );
		}

		// Only successful request return Array
		return $json;
	}

	/**
	 * Show promo from cloud
	 *
	 * @since  3.0
	 * @access public
	 */
	public function show_promo()
	{
		if ( ! $this->_api_key ) {
			Admin_Display::error( Error::msg( 'lack_of_api_key' ), true );
		}

		if ( empty( $this->_summary[ 'promo' ] ) ) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_promo.tpl.php' ;
	}

	/**
	 * Clear promo from cloud
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _clear_promo()
	{
		if ( count( $this->_summary[ 'promo' ] ) > 1 ) {
			array_shift( $this->_summary[ 'promo' ] );
		}
		else {
			$this->_summary[ 'promo' ] = array();
		}
		self::save_summary();
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
	 * @since  3.0
	 * @access public
	 */
	public function ip_validate()
	{
		if ( empty( $_POST[ 'hash' ] ) ) {
			Debug2::debug( '🌤️  Lack of hash param' );
			return self::err( 'lack_of_param' );
		}

		if ( empty( $this->_api_key ) ) {
			Debug2::debug( '🌤️  Lack of API key' );
			return self::err( 'lack_of_api_key' );
		}

		$to_validate = substr( $this->_api_key, 0, 4 );
		if ( $_POST[ 'hash' ] !== md5( $to_validate ) ) {
			Debug2::debug( '🌤️  __callback IP request hash wrong: md5(' . $to_validate . ') !== ' . $_POST[ 'hash' ] );
			return self::err( 'err_hash' );
		}

		Control::set_nocache( 'Cloud IP hash validation' );

		$res_hash = substr( $this->_api_key, 2, 4 );

		Debug2::debug( '🌤️  __callback IP request hash: md5(' . $res_hash . ')' );

		return self::ok( array( 'hash' => md5( $res_hash ) ) );
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
			Debug2::debug( '🌤️  Lack of hash param' );
			return self::err( 'lack_of_param' );
		}

		$key_hash = self::get_option( self::DB_HASH );
		if ( $key_hash ) { // One time usage only
			self::delete_option( self::DB_HASH );
		}

		if ( ! $key_hash || $_POST[ 'hash' ] !== md5( $key_hash ) ) {
			Debug2::debug( '🌤️  __callback request hash wrong: md5(' . $key_hash . ') !== ' . $_POST[ 'hash' ] );
			return self::err( 'Error hash code' );
		}

		Control::set_nocache( 'Cloud hash validation' );

		Debug2::debug( '🌤️  __callback request hash: ' . $key_hash );

		return self::ok( array( 'hash' => $key_hash ) );
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
			Debug2::debug( '[CLoud] failed to gen_key: ' . $error_message );
			Admin_Display::error( __( 'CLoud Error', 'litespeed-cache' ) . ': ' . $error_message );
			return;
		}

		$json = json_decode( $response[ 'body' ], true );
		if ( empty( $json[ '_res' ] ) || $json[ '_res' ] != 'ok' ) {
			Debug2::debug( '[CLoud] error to gen_key: ', $json );
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
		self::delete_option( self::DB_HASH );
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

		Debug2::debug( '🌤️  saved auth_key' );
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
			case self::TYPE_CLEAR_CLOUD:
				$instance->clear_cloud();
				break;

			case self::TYPE_REDETECT_CLOUD:
				if ( ! empty( $_GET[ 'svc' ] ) ) {
					$instance->detect_cloud( $_GET[ 'svc' ], true );
				}
				break;

			case self::TYPE_CLEAR_PROMO:
				$instance->_clear_promo();
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