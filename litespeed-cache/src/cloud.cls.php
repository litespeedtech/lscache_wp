<?php
/**
 * Cloud service cls
 *
 * @since      3.0
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Cloud extends Conf
{
	protected static $_instance;
	const DB_PREFIX = 'cloud';

	const DB_HASH = 'hash' ;

	const CLOUD_SERVER = 'https://apidev.quic.cloud';

	const ACTION_IPS = 'ips';
	const ACTION_SYNC_CONF = 'd/sync_conf';

	const SERVICES = array(
		'img_optm',
		'ccss',
		'lqip',
		'placeholder',
		'sitehealth',
	);

	const TYPE_GEN_KEY = 'gen_key';

	private $_api_key;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	protected function __construct()
	{
		$this->_api_key = Core::config( Conf::O_API_KEY );
	}


	/**
	 * ping clouds to find the fastest node
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _detect_cloud( $service )
	{
		if ( ! $service || ! in_array( $service, self::SERVICES ) ) {
			$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . $service;
			Admin_Display::error( $msg );
			return;
		}

		$summary = self::get_summary();

		// Send request to Quic Online Service
		$json = $this->_post( self::ACTION_IPS, array( 'svc' => $service ) );

		// Check if get list correctly
		if ( empty( $json[ 'list' ] ) || ! is_array( $json[ 'list' ] ) ) {
			Log::debug( '[Cloud] request cloud list failed: ', $json );

			if ( $json ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . $json );
				Admin_Display::error( $msg );
			}
			return;
		}

		// Ping closest cloud
		$speed_list = array();
		foreach ( $json[ 'list' ] as $v ) {
			$speed_list[ $v ] = Utility::ping( $v );
		}
		$min = min( $speed_list );

		if ( $min == 99999 ) {
			Log::debug( '[Cloud] failed to ping all clouds' );
			return;
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

		Log::debug( '[Cloud] Closest nodes list', $valid_clouds );

		$closest = $valid_clouds[ array_rand( $valid_clouds ) ];

		Log::debug( '[Cloud] Chose node: ' . $closest );

		// store data into option locally
		$summary[ 'server.' . $service ] = $closest;
		$summary[ 'server_date.' . $service ] = time();
		self::save_summary( $summary );
	}

	/**
	 * Post data to QUIC.cloud server
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function post( $action, $data = false, $time_out = false )
	{
		$instance = self::get_instance() ;
		return $instance->_post( $action, $data, $time_out ) ;
	}

	/**
	 * Post data to cloud server
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _post( $service, $data = false, $time_out = false )
	{
		if ( ! $this->_api_key ) {
			$msg = sprintf( __( 'The Cloud API key need to be set first to use online service. <a %s>Click here to Setting page</a>.', 'litespeed-cache' ), ' href="' . admin_url('admin.php?page=litespeed-general') . '" ' );
			Admin_Display::error( $msg );
			return;
		}

		$summary = self::get_summary();

		// Limit frequent unfinished request to 5min
		if ( ! empty( $summary[ 'curr_request.' . $service ] ) ) {
			$expired = $summary[ 'curr_request.' . $service ] + 300 - time();
			if ( $expired > 0 ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . sprintf( __( 'Please try after %s.', 'litespeed-cache' ), $expired . 's' );
				Admin_Display::error( $msg );
				return;
			}
		}

		if ( $service === self::ACTION_IPS || $service === self::ACTION_SYNC_CONF ) {
			$server = self::CLOUD_SERVER;
		}
		else {
			// Check if the stored server needs to be refreshed
			if ( empty( $summary[ 'server.' . $service ] ) || empty( $summary[ 'server_date.' . $service ] ) || $summary[ 'server_date.' . $service ] > time() + 86400 * 7 ) {
				// Request node server first
				$this->_detect_cloud( $service );
				$summary = self::get_summary();
			}

			if ( empty( $summary[ 'server.' . $service ] ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . __( 'No available cloud node.', 'litespeed-cache' ) );
				Admin_Display::error( $msg );
				return;
			}
			$server = $summary[ 'server.' . $service ];
		}

		$url = $server . '/' . $service;

		Log::debug( '[Cloud] posting to : ' . $url );

		$param = array(
			'domain'		=> home_url(),
			'domain_key'	=> $this->_api_key,
			'svc'			=> $service,
			'v'				=> Core::PLUGIN_VERSION,
			'data' 			=> $data, // TODO : check if need to encode: is_array( $data ) ? json_encode( $data ) : $data,
		) ;
		/**
		 * Extended timeout to avoid cUrl 28 timeout issue as we need callback validation
		 * @since 1.6.4
		 */
		$summary[ 'curr_request.' . $service ] = time();
		self::save_summary( $summary );
		$response = wp_remote_post( $url, array( 'body' => $param, 'timeout' => $time_out ?: 15 ) ) ;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			Log::debug( '[Cloud] failed to post: ' . $error_message );
			return $error_message;
		}

		// parse data from server
		$json = json_decode( $response[ 'body' ], true );

		if ( ! is_array( $json ) ) {
			Log::debug( '[Cloud] failed to decode post json: ' . $response[ 'body' ] );

			$msg = __( 'Failed to post via WordPress', 'litespeed-cache' ) . ': ' . $response[ 'body' ];
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

		// Parse general error msg
		if ( empty( $json[ '_res' ] ) || $json[ '_res' ] !== 'ok' ) {
			$json_msg = ! empty( $json[ '_msg' ] ) ? $json[ '_msg' ] : 'Unknown';
			Log::debug( '[Cloud] _err: ' . $json_msg );

			$msg = __( 'Failed to communicate with QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json_msg;
			$msg .= $this->_parse_link( $json );
			Admin_Display::error( $msg );

			return false;
		}

		$summary[ 'last_request.' . $service ] = $summary[ 'curr_request.' . $service ];
		$summary[ 'curr_request.' . $service ] = 0;
		self::save_summary( $summary );

		return $json;
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
			Log::debug( '[Cloud] Lack of hash param' ) ;
			return array( '_res' => 'err', '_msg' => 'lack_of_param' ) ;
		}

		$key_hash = self::get_option( self::DB_HASH ) ;

		if ( ! $key_hash || $_POST[ 'hash' ] !== md5( $key_hash ) ) {
			Log::debug( '[Cloud] __callback request hash wrong: md5(' . $key_hash . ') !== ' . $_POST[ 'hash' ] ) ;
			return array( '_res' => 'err', '_msg' => 'Error hash code' ) ;
		}

		Control::set_nocache( 'Cloud hash validation' ) ;

		Log::debug( '[Cloud] __callback request hash: ' . $key_hash ) ;

		self::delete_option( self::DB_HASH ) ;

		return array( 'hash' => $key_hash ) ;
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
			'domain'	=> home_url(),
			'email'		=> get_bloginfo( 'admin_email' ),
			'rest'		=> rest_get_url_prefix(),
			'src'		=> defined( 'LITESPEED_CLI' ) ? 'CLI' : 'web',
		);

		if ( ! defined( 'LITESPEED_CLI' ) ) {
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
		if ( $json[ '_res' ] != 'ok' ) {
			Log::debug( '[CLoud] error to gen_key: ' . $json[ '_msg' ] );
			Admin_Display::error( __( 'CLoud Error', 'litespeed-cache' ) . ': ' . $json[ '_msg' ] );
			return;
		}

		// Save domain_key option
		$this->_save_api_key( $json[ 'domain_key' ] );

		Admin_Display::succeed( __( 'Generate API key successfully.', 'litespeed-cache' ) );
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
		Config::get_instance()->update( Conf::O_API_KEY, $api_key ) ;

		Log::debug( '[Cloud] saved auth_key' ) ;
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
			case self::TYPE_GEN_KEY :
				$instance->gen_key();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}