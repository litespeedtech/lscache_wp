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
class UCSS extends Base {

	const LOG_TAG = '[UCSS]';

	const TYPE_GEN     = 'gen';
	const TYPE_CLEAR_Q = 'clear_q';

	/**
	 * Summary data.
	 *
	 * @var array
	 */
	protected $_summary;

	/**
	 * UCSS whitelist selectors.
	 *
	 * @var array
	 */
	private $_ucss_whitelist;

	/**
	 * Queue for UCSS generation.
	 *
	 * @var array
	 */
	private $_queue;

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
	 * Uniform url tag for ucss usage
	 *
	 * @since 4.7
	 *
	 * @param string|false $request_url The request URL.
	 * @return string The URL tag.
	 */
	public static function get_url_tag( $request_url = false ) {
		$url_tag = $request_url;
		if (is_404()) {
			$url_tag = '404';
		} elseif (apply_filters('litespeed_ucss_per_pagetype', false)) {
			$url_tag = Utility::page_type();
			self::debug('litespeed_ucss_per_pagetype filter altered url to ' . $url_tag);
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

		$filepath_prefix = $this->_build_filepath_prefix('ucss');

		$url_tag = self::get_url_tag($request_url);

		$vary     = $this->cls('Vary')->finalize_full_varies();
		$filename = $this->cls('Data')->load_url_file($url_tag, $vary, 'ucss');
		if ($filename) {
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';

			if (file_exists($static_file)) {
				self::debug2('existing ucss ' . $static_file);
				// Check if is error comment inside only
				$tmp = File::read($static_file);
				if ( '/*' === substr( $tmp, 0, 2 ) && '*/' === substr( trim( $tmp ), -2 ) ) {
					self::debug2('existing ucss is error only: ' . $tmp);
					Core::comment('QUIC.cloud UCSS bypassed due to generation error ❌ ' . $filepath_prefix . $filename . '.css');
					return false;
				}

				Core::comment('QUIC.cloud UCSS loaded ✅ ' . $filepath_prefix . $filename . '.css' );

				return $filename . '.css';
			}
		}

		if ($dry_run) {
			return false;
		}

		Core::comment('QUIC.cloud UCSS in queue');

		$uid = get_current_user_id();

		$ua = $this->_get_ua();

		// Store it for cron
		$this->_queue = $this->load_queue('ucss');

		if (count($this->_queue) > 500) {
			self::debug('UCSS Queue is full - 500');
			return false;
		}

		$queue_k                  = (strlen($vary) > 32 ? md5($vary) : $vary) . ' ' . $url_tag;
		$this->_queue[ $queue_k ] = [
			'url'        => apply_filters( 'litespeed_ucss_url', $request_url ),
			'user_agent' => substr( $ua, 0, 200 ),
			'is_mobile'  => $this->_separate_mobile(),
			'is_webp'    => $this->cls( 'Media' )->webp_support() ? 1 : 0,
			'uid'        => $uid,
			'vary'       => $vary,
			'url_tag'    => $url_tag,
		]; // Current UA will be used to request
		$this->save_queue('ucss', $this->_queue);
		self::debug('Added queue_ucss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary . ' [uid] ' . $uid);

		// Prepare cache tag for later purge
		Tag::add('UCSS.' . md5($queue_k));

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
		$this->_queue = $this->load_queue('ucss');

		if (count($this->_queue) > 500) {
			self::debug('UCSS Queue is full - 500');
			return false;
		}

		$ua = $this->_get_ua();
		foreach ($url_files as $url_file) {
			$vary        = $url_file['vary'];
			$request_url = $url_file['url'];
			$is_mobile   = $url_file['mobile'];
			$is_webp     = $url_file['webp'];
			$url_tag     = self::get_url_tag($request_url);

			$queue_k = (strlen($vary) > 32 ? md5($vary) : $vary) . ' ' . $url_tag;
			$q       = [
				'url'        => apply_filters( 'litespeed_ucss_url', $request_url ),
				'user_agent' => substr( $ua, 0, 200 ),
				'is_mobile'  => $is_mobile,
				'is_webp'    => $is_webp,
				'uid'        => false,
				'vary'       => $vary,
				'url_tag'    => $url_tag,
			]; // Current UA will be used to request

			self::debug('Added queue_ucss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary . ' [uid] false');
			$this->_queue[$queue_k] = $q;
		}
		$this->save_queue('ucss', $this->_queue);
	}

	/**
	 * Generate UCSS
	 *
	 * @since  4.0
	 *
	 * @param bool $keep_going Whether to continue processing.
	 * @return mixed The cron handler result.
	 */
	public static function cron( $keep_going = false ) {
		$_instance = self::cls();
		return $_instance->_cron_handler( $keep_going );
	}

	/**
	 * Handle UCSS cron
	 *
	 * @since 4.2
	 *
	 * @param bool $keep_going Whether to continue processing.
	 * @return mixed The redirect result or void.
	 */
	private function _cron_handler( $keep_going ) {
		$this->_queue = $this->load_queue( 'ucss' );

		if ( empty( $this->_queue ) ) {
			return;
		}

		// Check if we need to wait due to server's try_later request
		if ( ! empty( $this->_summary['ucss_next_run_after'] ) && time() < $this->_summary['ucss_next_run_after'] ) {
			$wait_seconds = $this->_summary['ucss_next_run_after'] - time();
			self::debug( 'Waiting for try_later timeout: ' . $wait_seconds . ' seconds remaining' );
			return;
		}

		// Clear try_later flag if wait time has passed
		if ( ! empty( $this->_summary['ucss_next_run_after'] ) ) {
			unset( $this->_summary['ucss_next_run_after'] );
			self::save_summary();
			self::debug( 'Cleared try_later flag, resuming UCSS processing' );
		}

		// For cron, need to check request interval too
		if ( ! $keep_going ) {
			if (!empty($this->_summary['curr_request']) && time() - $this->_summary['curr_request'] < 300 && !$this->conf(self::O_DEBUG)) {
				self::debug('Last request not done');
				return;
			}
		}

		$i = 0;
		foreach ($this->_queue as $k => $v) {
			if (!empty($v['_status'])) {
				continue;
			}

			self::debug('cron job [tag] ' . $k . ' [url] ' . $v['url'] . ($v['is_mobile'] ? ' 📱 ' : '') . ' [UA] ' . $v['user_agent']);

			if (!isset($v['is_webp'])) {
				$v['is_webp'] = false;
			}

			++$i;
			$res = $this->_send_req($v['url'], $k, $v['uid'], $v['user_agent'], $v['vary'], $v['url_tag'], $v['is_mobile'], $v['is_webp']);
			if (!$res) {
				// Status is wrong, drop this this->_queue
				$this->_queue = $this->load_queue('ucss');
				unset($this->_queue[$k]);
				$this->save_queue('ucss', $this->_queue);

				if ( ! $keep_going ) {
					return;
				}

				if ( $i > 3 ) {
					GUI::print_loading( count( $this->_queue ), 'UCSS' );
					return Router::self_redirect( Router::ACTION_UCSS, self::TYPE_GEN );
				}

				continue;
			}

			// Exit queue if out of quota or service is hot
			if ( 'out_of_quota' === $res || 'svc_hot' === $res ) {
				return;
			}

			// Handle try_later response from server
			if ( is_array( $res ) && ! empty( $res['try_later'] ) ) {
				$ttl                                   = (int) $res['try_later'];
				$next_run_time                         = time() + $ttl;
				$this->_summary['ucss_next_run_after'] = $next_run_time;
				self::save_summary();
				self::debug( 'Set next UCSS cron run after ' . $ttl . ' seconds (at ' . gmdate( 'Y-m-d H:i:s', $next_run_time ) . ')' );
				return;
			}

			// Handle completed response (sync mode)
			if ( 'completed' === $res ) {
				self::debug( 'UCSS completed for [k] ' . $k );
			}

			// only request first one
			if ( ! $keep_going ) {
				return;
			}

			if ($i > 3) {
				GUI::print_loading(count($this->_queue), 'UCSS');
				return Router::self_redirect(Router::ACTION_UCSS, self::TYPE_GEN);
			}
		}
	}

	/**
	 * Send to QC API to generate UCSS
	 *
	 * @since  2.3
	 * @access private
	 *
	 * @param string    $request_url The request URL.
	 * @param string    $queue_k     The queue key.
	 * @param int|false $uid         The user ID.
	 * @param string    $user_agent  The user agent.
	 * @param string    $vary        The vary string.
	 * @param string    $url_tag     The URL tag.
	 * @param bool      $is_mobile   Whether is mobile.
	 * @param bool      $is_webp     Whether supports webp.
	 * @return string|bool|null The result status.
	 */
	private function _send_req( $request_url, $queue_k, $uid, $user_agent, $vary, $url_tag, $is_mobile, $is_webp ) {
		// Check if has credit to push or not
		$err       = false;
		$allowance = $this->cls('Cloud')->allowance(Cloud::SVC_UCSS, $err);
		if (!$allowance) {
			self::debug('❌ No credit: ' . $err);
			$err && Admin_Display::error(Error::msg($err));
			return 'out_of_quota';
		}

		set_time_limit(120);

		// Update css request status
		$this->_summary['curr_request'] = time();
		self::save_summary();

		$data = [
			'url'        => $request_url,
			'queue_k'    => $queue_k,
			'user_agent' => $user_agent,
			'is_mobile'  => $is_mobile ? 1 : 0, // todo:compatible w/ tablet
			'is_webp'    => $is_webp ? 1 : 0,
		];
		if (!isset($this->_ucss_whitelist)) {
			$this->_ucss_whitelist = $this->_filter_whitelist();
		}
		$data['whitelist'] = $this->_ucss_whitelist;

		self::debug('Generating: ', $data);

		$json = Cloud::post(Cloud::SVC_UCSS, $data, 30);
		if (!is_array($json)) {
			return $json;
		}

		// Check if server asks to try later
		if ( ! empty( $json['try_later'] ) ) {
			$ttl = (int) $json['try_later'];
			self::debug( 'Server requested try later: ' . $ttl . ' seconds' );
			return [ 'try_later' => $ttl ];
		}

		// Check response status
		if ( empty( $json['status'] ) ) {
			self::debug( '❌ No status in response' );
			return false;
		}

		// Handle sync response with data
		if ( ! empty( $json['data_ucss'] ) ) {
			self::debug( '✅ Received UCSS data, saving...' );
			$this->_save_con( 'ucss', $json['data_ucss'], $queue_k, $is_mobile, $is_webp );

			// Remove from queue
			unset( $this->_queue[ $queue_k ] );
			$this->save_queue( 'ucss', $this->_queue );
			self::debug( 'Removed from queue [q_k] ' . $queue_k );

			// Save summary data
			$this->_summary['last_request'] = $this->_summary['curr_request'];
			$this->_summary['curr_request'] = 0;
			self::save_summary();

			return 'completed';
		}

		// Unknown status
		self::debug( '❌ Unknown status: ' . $json['status'] );
		return false;
	}

	/**
	 * Save UCSS content
	 *
	 * @since 4.2
	 *
	 * @param string $type      The content type.
	 * @param string $css       The CSS content.
	 * @param string $queue_k   The queue key.
	 * @param bool   $is_mobile Whether is mobile.
	 * @param bool   $is_webp   Whether supports webp.
	 */
	private function _save_con( $type, $css, $queue_k, $is_mobile, $is_webp ) {
		// Add filters
		$css = apply_filters('litespeed_' . $type, $css, $queue_k);
		self::debug2('con: ', $css);

		if ( '/*' === substr( $css, 0, 2 ) && '*/' === substr( $css, -2 ) ) {
			self::debug('❌ empty ' . $type . ' [content] ' . $css);
			// continue; // Save the error info too
		}

		// Write to file
		$filecon_md5 = md5($css);

		$filepath_prefix = $this->_build_filepath_prefix($type);
		$static_file     = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.css';

		File::save($static_file, $css, true);

		$url_tag = $this->_queue[$queue_k]['url_tag'];
		$vary    = $this->_queue[$queue_k]['vary'];
		self::debug2("Save URL to file [file] $static_file [vary] $vary");

		$this->cls('Data')->save_url($url_tag, $vary, $type, $filecon_md5, dirname($static_file), $is_mobile, $is_webp);

		Purge::add(strtoupper($type) . '.' . md5($queue_k));
	}

	/**
	 * Filter the comment content, add quotes to selector from whitelist. Return the json
	 *
	 * @since 3.3
	 */
	private function _filter_whitelist() {
		$whitelist = [];
		$list      = apply_filters('litespeed_ucss_whitelist', $this->conf(self::O_OPTM_UCSS_SELECTOR_WHITELIST));
		foreach ($list as $k => $v) {
			if (substr($v, 0, 2) === '//') {
				continue;
			}
			$whitelist[] = $v;
		}

		return $whitelist;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.3
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_GEN:
            self::cron(true);
				break;

			case self::TYPE_CLEAR_Q:
            $this->clear_q('ucss');
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
