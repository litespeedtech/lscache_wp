<?php

/**
 * The REST related class.
 *
 * @since       2.9.4
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class REST extends Root {

	const LOG_TAG                  = '☎️';
	private $_internal_rest_status = false;

	/**
	 * Confructor of ESI
	 *
	 * @since    2.9.4
	 */
	public function __construct() {
		// Hook to internal REST call
		add_filter('rest_request_before_callbacks', array( $this, 'set_internal_rest_on' ));
		add_filter('rest_request_after_callbacks', array( $this, 'set_internal_rest_off' ));

		add_action('rest_api_init', array( $this, 'rest_api_init' ));
	}

	/**
	 * Register REST hooks
	 *
	 * @since  3.0
	 * @access public
	 */
	public function rest_api_init() {
		// Activate or deactivate a specific crawler callback
		register_rest_route('litespeed/v1', '/toggle_crawler_state', array(
			'methods' => 'POST',
			'callback' => array( $this, 'toggle_crawler_state' ),
			'permission_callback' => function () {
				return current_user_can('manage_network_options') || current_user_can('manage_options');
			},
		));

		register_rest_route('litespeed/v1', '/tool/check_ip', array(
			'methods' => 'GET',
			'callback' => array( $this, 'check_ip' ),
			'permission_callback' => function () {
				return current_user_can('manage_network_options') || current_user_can('manage_options');
			},
		));

		// IP callback validate
		register_rest_route('litespeed/v3', '/ip_validate', array(
			'methods' => 'POST',
			'callback' => array( $this, 'ip_validate' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		// 1.2. WP REST Dryrun Callback
		register_rest_route('litespeed/v3', '/wp_rest_echo', array(
			'methods' => 'POST',
			'callback' => array( $this, 'wp_rest_echo' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));
		register_rest_route('litespeed/v3', '/ping', array(
			'methods' => 'POST',
			'callback' => array( $this, 'ping' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		// CDN setup callback notification
		register_rest_route('litespeed/v3', '/cdn_status', array(
			'methods' => 'POST',
			'callback' => array( $this, 'cdn_status' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		// Image optm notify_img
		// Need validation
		register_rest_route('litespeed/v1', '/notify_img', array(
			'methods' => 'POST',
			'callback' => array( $this, 'notify_img' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		register_rest_route('litespeed/v1', '/notify_ccss', array(
			'methods' => 'POST',
			'callback' => array( $this, 'notify_ccss' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		register_rest_route('litespeed/v1', '/notify_ucss', array(
			'methods' => 'POST',
			'callback' => array( $this, 'notify_ucss' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		register_rest_route('litespeed/v1', '/notify_vpi', array(
			'methods' => 'POST',
			'callback' => array( $this, 'notify_vpi' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		register_rest_route('litespeed/v3', '/err_domains', array(
			'methods' => 'POST',
			'callback' => array( $this, 'err_domains' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));

		// Image optm check_img
		// Need validation
		register_rest_route('litespeed/v1', '/check_img', array(
			'methods' => 'POST',
			'callback' => array( $this, 'check_img' ),
			'permission_callback' => array( $this, 'is_from_cloud' ),
		));
	}

	/**
	 * Call to freeze or melt the crawler clicked
	 *
	 * @since  4.3
	 */
	public function toggle_crawler_state() {
		if (isset($_POST['crawler_id'])) {
			return $this->cls('Crawler')->toggle_activeness($_POST['crawler_id']) ? 1 : 0;
		}
	}

	/**
	 * Check if the request is from cloud nodes
	 *
	 * @since 4.2
	 * @since 4.4.7 As there is always token/api key validation, ip validation is redundant
	 */
	public function is_from_cloud() {
		// return true;
		return $this->cls('Cloud')->is_from_cloud();
	}

	/**
	 * Ping pong
	 *
	 * @since  3.0.4
	 */
	public function ping() {
		return $this->cls('Cloud')->ping();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function check_ip() {
		return Tool::cls()->check_ip();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function ip_validate() {
		return $this->cls('Cloud')->ip_validate();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function wp_rest_echo() {
		return $this->cls('Cloud')->wp_rest_echo();
	}

	/**
	 * Endpoint for QC to notify plugin of CDN status update.
	 *
	 * @since  7.0
	 */
	public function cdn_status() {
		return $this->cls('Cloud')->update_cdn_status();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function notify_img() {
		return Img_Optm::cls()->notify_img();
	}

	/**
	 * @since  7.1
	 */
	public function notify_ccss() {
		self::debug('notify_ccss');
		return CSS::cls()->notify();
	}

	/**
	 * @since  5.2
	 */
	public function notify_ucss() {
		self::debug('notify_ucss');
		return UCSS::cls()->notify();
	}

	/**
	 * @since  4.7
	 */
	public function notify_vpi() {
		self::debug('notify_vpi');
		return VPI::cls()->notify();
	}

	/**
	 * @since  4.7
	 */
	public function err_domains() {
		self::debug('err_domains');
		return $this->cls('Cloud')->rest_err_domains();
	}

	/**
	 * Launch api call
	 *
	 * @since  3.0
	 */
	public function check_img() {
		return Img_Optm::cls()->check_img();
	}

	/**
	 * Return error
	 *
	 * @since  5.7.0.1
	 */
	public static function err( $code ) {
		return array(
			'_res' => 'err',
			'_msg' => $code,
		);
	}

	/**
	 * Set internal REST tag to ON
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function set_internal_rest_on( $not_used = null ) {
		$this->_internal_rest_status = true;
		Debug2::debug2('[REST] ✅ Internal REST ON [filter] rest_request_before_callbacks');

		return $not_used;
	}

	/**
	 * Set internal REST tag to OFF
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function set_internal_rest_off( $not_used = null ) {
		$this->_internal_rest_status = false;
		Debug2::debug2('[REST] ❎ Internal REST OFF [filter] rest_request_after_callbacks');

		return $not_used;
	}

	/**
	 * Get internal REST tag
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function is_internal_rest() {
		return $this->_internal_rest_status;
	}

	/**
	 * Check if an URL or current page is REST req or not
	 *
	 * @since  2.9.3
	 * @since  2.9.4 Moved here from Utility, dropped static
	 * @access public
	 */
	public function is_rest( $url = false ) {
		// For WP 4.4.0- compatibility
		if (!function_exists('rest_get_url_prefix')) {
			return defined('REST_REQUEST') && REST_REQUEST;
		}

		$prefix = rest_get_url_prefix();

		// Case #1: After WP_REST_Request initialisation
		if (defined('REST_REQUEST') && REST_REQUEST) {
			return true;
		}

		// Case #2: Support "plain" permalink settings
		if (isset($_GET['rest_route']) && strpos(trim($_GET['rest_route'], '\\/'), $prefix, 0) === 0) {
			return true;
		}

		if (!$url) {
			return false;
		}

		// Case #3: URL Path begins with wp-json/ (REST prefix) Safe for subfolder installation
		$rest_url    = wp_parse_url(site_url($prefix));
		$current_url = wp_parse_url($url);
		// Debug2::debug( '[Util] is_rest check [base] ', $rest_url );
		// Debug2::debug( '[Util] is_rest check [curr] ', $current_url );
		// Debug2::debug( '[Util] is_rest check [curr2] ', wp_parse_url( add_query_arg( array( ) ) ) );
		if ($current_url !== false && !empty($current_url['path']) && $rest_url !== false && !empty($rest_url['path'])) {
			return strpos($current_url['path'], $rest_url['path']) === 0;
		}

		return false;
	}
}
