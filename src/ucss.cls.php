<?php
/**
 * The ucss class.
 *
 * @since   5.1
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * UCSS optimization class.
 *
 * @since 5.1
 */
class UCSS extends Cloud_Queue_Svc {

	const LOG_TAG = '[UCSS]';

	/**
	 * UCSS whitelist selectors.
	 *
	 * @var array
	 */
	private $_ucss_whitelist;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();

		add_filter( 'litespeed_ucss_whitelist', [ $this->cls( 'Data' ), 'load_ucss_whitelist' ] );
	}

	/**
	 * Svc id slug — drives queue type, Cloud::SVC_UCSS, and summary key prefix.
	 *
	 * @return string
	 */
	protected function _svc_id() {
		return 'ucss';
	}

	/**
	 * Response field carrying the generated CSS.
	 *
	 * @return string
	 */
	protected function _data_key() {
		return 'data_ucss';
	}

	/**
	 * UI templates read `last_request` directly (settings_css.tpl.php,
	 * dashboard.tpl.php). Keep the legacy unsuffixed key.
	 *
	 * @return string
	 */
	protected function _last_request_key() {
		return 'last_request';
	}

	/**
	 * Build the request body for Cloud::post.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return array
	 */
	protected function _build_payload( $queue_k, $v ) {
		if ( ! isset( $this->_ucss_whitelist ) ) {
			$this->_ucss_whitelist = $this->_filter_whitelist();
		}
		return [
			'url'        => $v['url'],
			'queue_k'    => $queue_k,
			'user_agent' => $v['user_agent'],
			'is_mobile'  => ! empty( $v['is_mobile'] ) ? 1 : 0,
			'is_webp'    => ! empty( $v['is_webp'] ) ? 1 : 0,
			'whitelist'  => $this->_ucss_whitelist,
		];
	}

	/**
	 * Persist the generated UCSS to disk. Empty payload is treated as a
	 * generation error (matches pre-refactor UCSS behavior at
	 * commit b88b7e53): drop the unusable queue item rather than persist a blank.
	 *
	 * @param string $data    UCSS content.
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool
	 */
	protected function _save_result( $data, $queue_k, $v ) {
		if ( empty( $data ) ) {
			return false;
		}
		return $this->_save_css_con( 'ucss', $data, $v['url_tag'], $v['vary'], $queue_k, ! empty( $v['is_mobile'] ), ! empty( $v['is_webp'] ) );
	}

	/**
	 * Uniform url tag for ucss usage
	 *
	 * @since 4.7
	 *
	 * @param string|false $request_url The request URL.
	 * @return string The URL tag.
	 */
	public static function get_url_tag( $request_url = false ) {
		$url_tag = $request_url;
		if ( is_404() ) {
			$url_tag = '404';
		} elseif ( apply_filters( 'litespeed_ucss_per_pagetype', false ) ) {
			$url_tag = Utility::page_type();
			self::debug( 'litespeed_ucss_per_pagetype filter altered url to ' . $url_tag );
		}

		return $url_tag;
	}

	/**
	 * Get UCSS path
	 *
	 * @since  4.0
	 *
	 * @param string $request_url The request URL.
	 * @param bool   $dry_run     Whether to run in dry mode.
	 * @return string|false The UCSS filename or false.
	 */
	public function load( $request_url, $dry_run = false ) {
		// Check UCSS URI excludes
		$ucss_exc = apply_filters( 'litespeed_ucss_exc', $this->conf( self::O_OPTM_UCSS_EXC ) );
		$hit      = $ucss_exc ? Utility::str_hit_array( $request_url, $ucss_exc ) : false;
		if ( $hit ) {
			self::debug( 'UCSS bypassed due to UCSS URI Exclude setting: ' . $hit );
			Core::comment( 'QUIC.cloud UCSS bypassed by setting' );
			return false;
		}

		$filepath_prefix = $this->_build_filepath_prefix( 'ucss' );

		$url_tag = self::get_url_tag( $request_url );

		$vary     = $this->cls( 'Vary' )->finalize_full_varies();
		$filename = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'ucss' );
		if ( $filename ) {
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';

			if ( file_exists( $static_file ) ) {
				self::debug2( 'existing ucss ' . $static_file );
				// Check if is error comment inside only
				$tmp = File::read( $static_file );
				if ( '/*' === substr( $tmp, 0, 2 ) && '*/' === substr( trim( $tmp ), -2 ) ) {
					self::debug2( 'existing ucss is error only: ' . $tmp );
					Core::comment( 'QUIC.cloud UCSS bypassed due to generation error ❌ ' . $filepath_prefix . $filename . '.css' );
					return false;
				}

				Core::comment( 'QUIC.cloud UCSS loaded ✅ ' . $filepath_prefix . $filename . '.css' );

				return $filename . '.css';
			}
		}

		if ( $dry_run ) {
			return false;
		}

		Core::comment( 'QUIC.cloud UCSS in queue' );

		$uid = get_current_user_id();

		$ua = $this->_get_ua();

		// Store it for cron
		$this->_queue = $this->load_queue( 'ucss' );

		if ( count( $this->_queue ) > $this->_max_queue_size() ) {
			self::debug( 'UCSS Queue is full - ' . $this->_max_queue_size() );
			return false;
		}

		$queue_k                  = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
		$this->_queue[ $queue_k ] = [
			'url'        => apply_filters( 'litespeed_ucss_url', $request_url ),
			'user_agent' => substr( $ua, 0, 200 ),
			'is_mobile'  => $this->_separate_mobile(),
			'is_webp'    => $this->cls( 'Media' )->webp_support() ? 1 : 0,
			'uid'        => $uid,
			'vary'       => $vary,
			'url_tag'    => $url_tag,
		]; // Current UA will be used to request
		$this->save_queue( 'ucss', $this->_queue );
		self::debug( 'Added queue_ucss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary . ' [uid] ' . $uid );

		// Prepare cache tag for later purge
		Tag::add( 'UCSS.' . md5( $queue_k ) );

		return false;
	}

	/**
	 * Get User Agent
	 *
	 * @since  5.3
	 *
	 * @return string The user agent string.
	 */
	private function _get_ua() {
		return ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}

	/**
	 * Add rows to q
	 *
	 * @since  5.3
	 *
	 * @param array $url_files Array of URL file data.
	 * @return false|void False if queue is full.
	 */
	public function add_to_q( $url_files ) {
		// Store it for cron
		$this->_queue = $this->load_queue( 'ucss' );

		if ( count( $this->_queue ) > $this->_max_queue_size() ) {
			self::debug( 'UCSS Queue is full - ' . $this->_max_queue_size() );
			return false;
		}

		$ua = $this->_get_ua();
		foreach ( $url_files as $url_file ) {
			$vary        = $url_file['vary'];
			$request_url = $url_file['url'];
			$is_mobile   = $url_file['mobile'];
			$is_webp     = $url_file['webp'];
			$url_tag     = self::get_url_tag( $request_url );

			$queue_k                  = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
			$this->_queue[ $queue_k ] = [
				'url'        => apply_filters( 'litespeed_ucss_url', $request_url ),
				'user_agent' => substr( $ua, 0, 200 ),
				'is_mobile'  => $is_mobile,
				'is_webp'    => $is_webp,
				'uid'        => false,
				'vary'       => $vary,
				'url_tag'    => $url_tag,
			]; // Current UA will be used to request

			self::debug( 'Added queue_ucss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary . ' [uid] false' );
		}
		$this->save_queue( 'ucss', $this->_queue );
	}

	/**
	 * Filter the comment content, add quotes to selector from whitelist. Return the json
	 *
	 * @since 3.3
	 */
	private function _filter_whitelist() {
		$whitelist = [];
		$list      = apply_filters( 'litespeed_ucss_whitelist', $this->conf( self::O_OPTM_UCSS_SELECTOR_WHITELIST ) );
		foreach ( $list as $v ) {
			if ( substr( $v, 0, 2 ) === '//' ) {
				continue;
			}
			$whitelist[] = $v;
		}

		return $whitelist;
	}
}
