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

			$this->_queue                  = $this->load_queue( 'ucss' );
			$this->_queue[ $k ]['_status'] = 'requested';
			$this->save_queue( 'ucss', $this->_queue );
			self::debug( 'Saved to queue [k] ' . $k );

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

		// Gather guest HTML to send
		$html = $this->cls('CSS')->prepare_html($request_url, $user_agent, $uid);

		if (!$html) {
			return false;
		}

		// Parse HTML to gather all CSS content before requesting
		$css             = false;
		list(, $html)    = $this->prepare_css($html, $is_webp, true); // Use this to drop CSS from HTML as we don't need those CSS to generate UCSS
		$filename        = $this->cls('Data')->load_url_file($url_tag, $vary, 'css');
		$filepath_prefix = $this->_build_filepath_prefix('css');
		$static_file     = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';
		self::debug('Checking combined file ' . $static_file);
		if (file_exists($static_file)) {
			$css = File::read($static_file);
		}

		if (!$css) {
			self::debug('❌ No combined css');
			return false;
		}

		$data = [
			'url'        => $request_url,
			'queue_k'    => $queue_k,
			'user_agent' => $user_agent,
			'is_mobile'  => $is_mobile ? 1 : 0, // todo:compatible w/ tablet
			'is_webp'    => $is_webp ? 1 : 0,
			'html'       => $html,
			'css'        => $css,
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

		// Old version compatibility
		if (empty($json['status'])) {
			if (!empty($json['ucss'])) {
				$this->_save_con('ucss', $json['ucss'], $queue_k, $is_mobile, $is_webp);
			}

			// Delete the row
			return false;
		}

		// Unknown status, remove this line
		if ( 'queued' !== $json['status'] ) {
			return false;
		}

		// Save summary data
		$this->_summary['last_spent']   = time() - $this->_summary['curr_request'];
		$this->_summary['last_request'] = $this->_summary['curr_request'];
		$this->_summary['curr_request'] = 0;
		self::save_summary();

		return true;
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
	 * Prepare CSS from HTML for CCSS generation only. UCSS will used combined CSS directly.
	 * Prepare refined HTML for both CCSS and UCSS.
	 *
	 * @since  3.4.3
	 *
	 * @param string $html    The HTML content.
	 * @param bool   $is_webp Whether supports webp.
	 * @param bool   $dryrun  Whether to run in dry mode.
	 * @return array Array of CSS and HTML.
	 */
	public function prepare_css( $html, $is_webp = false, $dryrun = false ) {
		$css = '';
		preg_match_all('#<link ([^>]+)/?>|<style([^>]*)>([^<]+)</style>#isU', $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$debug_info = '';
			if (strpos($match[0], '<link') === 0) {
				$attrs = Utility::parse_attr($match[1]);

				if (empty($attrs['rel'])) {
					continue;
				}

				if ( 'stylesheet' !== $attrs['rel'] ) {
					if ( 'preload' !== $attrs['rel'] || empty( $attrs['as'] ) || 'style' !== $attrs['as'] ) {
						continue;
					}
				}

				if (!empty($attrs['media']) && strpos($attrs['media'], 'print') !== false) {
					continue;
				}

				if (empty($attrs['href'])) {
					continue;
				}

				// Check Google fonts hit
				if (strpos($attrs['href'], 'fonts.googleapis.com') !== false) {
					$html = str_replace($match[0], '', $html);
					continue;
				}

				$debug_info = $attrs['href'];

				// Load CSS content
				if (!$dryrun) {
					// Dryrun will not load CSS but just drop them
					$con = $this->cls('Optimizer')->load_file($attrs['href']);
					if (!$con) {
						continue;
					}
				} else {
					$con = '';
				}
			} else {
				// Inline style
				$attrs = Utility::parse_attr($match[2]);

				if (!empty($attrs['media']) && strpos($attrs['media'], 'print') !== false) {
					continue;
				}

				Debug2::debug2('[CSS] Load inline CSS ' . substr($match[3], 0, 100) . '...', $attrs);
				$con = $match[3];

				$debug_info = '__INLINE__';
			}

			$con = Optimizer::minify_css($con);
			if ($is_webp && $this->cls('Media')->webp_support()) {
				$con = $this->cls('Media')->replace_background_webp($con);
			}

			if ( ! empty( $attrs['media'] ) && 'all' !== $attrs['media'] ) {
				$con = '@media ' . $attrs['media'] . '{' . $con . "}\n";
			} else {
				$con = $con . "\n";
			}

			$con  = '/* ' . $debug_info . ' */' . $con;
			$css .= $con;

			$html = str_replace($match[0], '', $html);
		}

		return [ $css, $html ];
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
	 * Notify finished from server
	 *
	 * @since 5.1
	 */
	public function notify() {
		$post_data = \json_decode( file_get_contents( 'php://input' ), true );
		if ( is_null( $post_data ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a callback from QUIC.cloud, verified by extract_msg()
			$post_data = $_POST;
		}
		self::debug('notify() data', $post_data);

		$this->_queue = $this->load_queue('ucss');

		list($post_data) = $this->cls('Cloud')->extract_msg($post_data, 'ucss');

		$notified_data = $post_data['data'];
		if (empty($notified_data) || !is_array($notified_data)) {
			self::debug('❌ notify exit: no notified data');
			return Cloud::err('no notified data');
		}

		// Check if its in queue or not
		$valid_i = 0;
		foreach ($notified_data as $v) {
			if (empty($v['request_url'])) {
				self::debug('❌ notify bypass: no request_url', $v);
				continue;
			}
			if (empty($v['queue_k'])) {
				self::debug('❌ notify bypass: no queue_k', $v);
				continue;
			}

			if (empty($this->_queue[$v['queue_k']])) {
				self::debug('❌ notify bypass: no this queue [q_k]' . $v['queue_k']);
				continue;
			}

			// Save data
			if (!empty($v['data_ucss'])) {
				$is_mobile = $this->_queue[$v['queue_k']]['is_mobile'];
				$is_webp   = $this->_queue[$v['queue_k']]['is_webp'];
				$this->_save_con('ucss', $v['data_ucss'], $v['queue_k'], $is_mobile, $is_webp);

				++$valid_i;
			}

			unset($this->_queue[$v['queue_k']]);
			self::debug('notify data handled, unset queue [q_k] ' . $v['queue_k']);
		}
		$this->save_queue('ucss', $this->_queue);

		self::debug('notified');

		return Cloud::ok( [ 'count' => $valid_i ] );
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
