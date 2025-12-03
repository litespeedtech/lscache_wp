<?php
/**
 * Guest mode management class.
 *
 * Handles syncing of Guest Mode IP and UA lists from QUIC.cloud.
 *
 * @package LiteSpeed
 * @since   7.7
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Guest
 *
 * Extends Cloud class to provide Guest Mode related functionality.
 */
class Guest extends Cloud {

	const LOG_TAG = '👤';

	const TYPE_SYNC = 'sync';

	/**
	 * Cron handler for daily Guest Mode sync.
	 *
	 * @since 7.7
	 * @return void
	 */
	public static function cron() {
		self::debug( 'Cron: starting daily sync' );
		self::cls()->sync_lists();
	}

	/**
	 * Sync Guest Mode IP and UA lists.
	 *
	 * Fetches the latest IP and UA lists from QUIC.cloud API and saves them locally.
	 *
	 * @since 7.7
	 * @return array{success: bool, message: string}
	 */
	public function sync_lists() {
		self::debug( 'Starting Guest Mode lists sync' );

		$cloud_dir = LITESPEED_STATIC_DIR . '/cloud';

		$results = [
			'ips' => false,
			'uas' => false,
		];

		foreach ( [ 'ips', 'uas' ] as $type ) {
			$data = $this->_fetch_api( $this->_cloud_server_wp . '/gm_' . $type );
			if ( $data && File::save( $cloud_dir . '/gm_' . $type . '.txt', $data, true ) ) {
				self::debug( 'Guest Mode ' . $type . ' synced' );
				$results[ $type ] = true;
			}
		}

		$success = $results['ips'] && $results['uas'];
		$message = $success
			? __( 'Guest Mode lists synced successfully.', 'litespeed-cache' )
			: __( 'Failed to sync Guest Mode lists.', 'litespeed-cache' );

		return [
			'success' => $success,
			'message' => $message,
		];
	}

	/**
	 * Fetch data from API.
	 *
	 * @since 7.7
	 * @param string $url API URL.
	 * @return string|false Data on success, false on failure.
	 */
	private function _fetch_api( $url ) {
		self::debug( 'Fetching: ' . $url );

		$response = wp_remote_get(
			$url,
			[
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			self::debug( 'Fetch error: ' . $response->get_error_message() );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			self::debug( 'Fetch failed with code: ' . $code );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			self::debug( 'Empty response body' );
			return false;
		}

		return $body;
	}

	/**
	 * Handle all request actions from main class.
	 *
	 * @since 7.7
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_SYNC:
				$result = $this->sync_lists();
				if ( Router::is_ajax() ) {
					wp_send_json( $result );
				}
				if ( $result['success'] ) {
					Admin_Display::success( $result['message'] );
				} else {
					Admin_Display::error( $result['message'] );
				}
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
