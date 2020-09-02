<?php
/**
 * The tools
 *
 * @since      	3.0
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Tool extends Instance {
	protected static $_instance;

	private $_conf_heartbeat_front;
	private $_conf_heartbeat_front_ttl;
	private $_conf_heartbeat_back;
	private $_conf_heartbeat_back_ttl;
	private $_conf_heartbeat_editor;
	private $_conf_heartbeat_editor_ttl;

	/**
	 * Init
	 *
	 * @since  3.0
	 * @access protected
	 */
	protected function __construct() {
		$this->_conf_heartbeat_front 		= Conf::val( Base::O_MISC_HEARTBEAT_FRONT );
		$this->_conf_heartbeat_front_ttl 	= Conf::val( Base::O_MISC_HEARTBEAT_FRONT_TTL );
		$this->_conf_heartbeat_back 		= Conf::val( Base::O_MISC_HEARTBEAT_BACK );
		$this->_conf_heartbeat_back_ttl 	= Conf::val( Base::O_MISC_HEARTBEAT_BACK_TTL );
		$this->_conf_heartbeat_editor 		= Conf::val( Base::O_MISC_HEARTBEAT_EDITOR );
		$this->_conf_heartbeat_editor_ttl 	= Conf::val( Base::O_MISC_HEARTBEAT_EDITOR_TTL );
	}

	/**
	 * Get public IP
	 *
	 * @since  3.0
	 * @access public
	 */
	public function check_ip() {
		Debug2::debug( '[Tool] ✅ check_ip' );

		$response = wp_remote_get( 'https://www.doapi.us/ip' );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'remote_get_fail', 'Failed to fetch from https://www.doapi.us/ip', array( 'status' => 404 ) );
		}

		$data = $response[ 'body' ];

		Debug2::debug( '[Tool] result [ip] ' . $data );

		return $data;
	}

	/**
	 * Heartbeat Control
	 *
	 * NOTE: since WP4.9, there could be a core bug that sometimes the hook is not working.
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function heartbeat() {
		$instance = self::get_instance();

		add_action( 'wp_enqueue_scripts', array( $instance, 'heartbeat_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $instance, 'heartbeat_backend' ) );
		add_filter( 'heartbeat_settings', array( $instance, 'heartbeat_settings' ) );
	}

	/**
	 * Heartbeat Control frontend control
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_frontend() {
		if ( ! $this->_conf_heartbeat_front ) {
			return;
		}

		if ( ! $this->_conf_heartbeat_front_ttl ) {
			wp_deregister_script( 'heartbeat' );
			Debug2::debug( '[Tool] Deregistered frontend heartbeat' );
		}
	}

	/**
	 * Heartbeat Control backend control
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_backend() {
		if ( $this->_is_editor() ) {
			if ( ! $this->_conf_heartbeat_editor ) {
				return;
			}

			if ( ! $this->_conf_heartbeat_editor_ttl ) {
				wp_deregister_script( 'heartbeat' );
				Debug2::debug( '[Tool] Deregistered editor heartbeat' );
			}
		}
		else {
			if ( ! $this->_conf_heartbeat_back ) {
				return;
			}

			if ( ! $this->_conf_heartbeat_back_ttl ) {
				wp_deregister_script( 'heartbeat' );
				Debug2::debug( '[Tool] Deregistered backend heartbeat' );
			}
		}

	}

	/**
	 * Heartbeat Control settings
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_settings( $settings ) {
		// Check editor first to make frontend editor valid too
		if ( $this->_is_editor() ) {
			if ( $this->_conf_heartbeat_editor ) {
				$settings[ 'interval' ] = $this->_conf_heartbeat_editor_ttl;
				Debug2::debug( '[Tool] Heartbeat interval set to ' . $this->_conf_heartbeat_editor_ttl );
			}
		}
		elseif ( ! is_admin() ) {
			if ( $this->_conf_heartbeat_front ) {
				$settings[ 'interval' ] = $this->_conf_heartbeat_front_ttl;
				Debug2::debug( '[Tool] Heartbeat interval set to ' . $this->_conf_heartbeat_front_ttl );
			}
		}
		else {
			if ( $this->_conf_heartbeat_back ) {
				$settings[ 'interval' ] = $this->_conf_heartbeat_back_ttl;
				Debug2::debug( '[Tool] Heartbeat interval set to ' . $this->_conf_heartbeat_back_ttl );
			}
		}
		return $settings;
	}

	/**
	 * If is in editor
	 *
	 * @since  3.0
	 * @access public
	 */
	private function _is_editor() {
		$res = is_admin() && Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], array( 'post.php', 'post-new.php' ) );

		return apply_filters( 'litespeed_is_editor', $res );
	}

}