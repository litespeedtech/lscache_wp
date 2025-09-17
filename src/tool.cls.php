<?php
// phpcs:ignoreFile
/**
 * The tools
 *
 * @since       3.0
 * @package     LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Tool
 *
 * Provides utility functions for LiteSpeed Cache, including IP detection and heartbeat control.
 *
 * @since 3.0
 */
class Tool extends Root {

	const LOG_TAG = '[Tool]';

	/**
	 * Get public IP
	 *
	 * Retrieves the public IP address of the server.
	 *
	 * @since  3.0
	 * @access public
	 * @return string The public IP address or an error message.
	 */
	public function check_ip() {
		self::debug( '✅ check_ip' );

		$response = wp_safe_remote_get( 'https://cyberpanel.sh/?ip', array(
			'headers' => array(
				'User-Agent' => 'curl/8.7.1',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return esc_html__( 'Failed to detect IP', 'litespeed-cache' );
		}

		$ip = trim( $response['body'] );

		self::debug( 'result [ip] ' . $ip );

		if ( Utility::valid_ipv4( $ip ) ) {
			return $ip;
		}

		return esc_html__( 'Failed to detect IP', 'litespeed-cache' );
	}

	/**
	 * Heartbeat Control
	 *
	 * Configures WordPress heartbeat settings for frontend, backend, and editor.
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat() {
		add_action( 'wp_enqueue_scripts', array( $this, 'heartbeat_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'heartbeat_backend' ) );
		add_filter( 'heartbeat_settings', array( $this, 'heartbeat_settings' ) );
	}

	/**
	 * Heartbeat Control frontend control
	 *
	 * Manages heartbeat settings for the frontend.
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_frontend() {
		if ( ! $this->conf( Base::O_MISC_HEARTBEAT_FRONT ) ) {
			return;
		}

		if ( ! $this->conf( Base::O_MISC_HEARTBEAT_FRONT_TTL ) ) {
			wp_deregister_script( 'heartbeat' );
			Debug2::debug( '[Tool] Deregistered frontend heartbeat' );
		}
	}

	/**
	 * Heartbeat Control backend control
	 *
	 * Manages heartbeat settings for the backend and editor.
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_backend() {
		if ( $this->is_editor() ) {
			if ( ! $this->conf( Base::O_MISC_HEARTBEAT_EDITOR ) ) {
				return;
			}

			if ( ! $this->conf( Base::O_MISC_HEARTBEAT_EDITOR_TTL ) ) {
				wp_deregister_script( 'heartbeat' );
				Debug2::debug( '[Tool] Deregistered editor heartbeat' );
			}
		} else {
			if ( ! $this->conf( Base::O_MISC_HEARTBEAT_BACK ) ) {
				return;
			}

			if ( ! $this->conf( Base::O_MISC_HEARTBEAT_BACK_TTL ) ) {
				wp_deregister_script( 'heartbeat' );
				Debug2::debug( '[Tool] Deregistered backend heartbeat' );
			}
		}
	}

	/**
	 * Heartbeat Control settings
	 *
	 * Adjusts heartbeat interval settings based on configuration.
	 *
	 * @since  3.0
	 * @access public
	 * @param array $settings Existing heartbeat settings.
	 * @return array Modified heartbeat settings.
	 */
	public function heartbeat_settings( $settings ) {
		// Check editor first to make frontend editor valid too
		if ( $this->is_editor() ) {
			if ( $this->conf( Base::O_MISC_HEARTBEAT_EDITOR ) ) {
				$settings['interval'] = $this->conf( Base::O_MISC_HEARTBEAT_EDITOR_TTL );
				Debug2::debug( '[Tool] Heartbeat interval set to ' . $this->conf( Base::O_MISC_HEARTBEAT_EDITOR_TTL ) );
			}
		} elseif ( ! is_admin() ) {
			if ( $this->conf( Base::O_MISC_HEARTBEAT_FRONT ) ) {
				$settings['interval'] = $this->conf( Base::O_MISC_HEARTBEAT_FRONT_TTL );
				Debug2::debug( '[Tool] Heartbeat interval set to ' . $this->conf( Base::O_MISC_HEARTBEAT_FRONT_TTL ) );
			}
		} elseif ( $this->conf( Base::O_MISC_HEARTBEAT_BACK ) ) {
			$settings['interval'] = $this->conf( Base::O_MISC_HEARTBEAT_BACK_TTL );
			Debug2::debug( '[Tool] Heartbeat interval set to ' . $this->conf( Base::O_MISC_HEARTBEAT_BACK_TTL ) );
		}
		return $settings;
	}

	/**
	 * Check if in editor
	 *
	 * Determines if the current request is within the WordPress editor.
	 *
	 * @since  3.0
	 * @access public
	 * @return bool True if in editor, false otherwise.
	 */
	public function is_editor() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$res         = is_admin() && Utility::str_hit_array( $request_uri, array( 'post.php', 'post-new.php' ) );

		return apply_filters( 'litespeed_is_editor', $res );
	}
}
