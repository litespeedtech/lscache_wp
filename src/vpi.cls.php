<?php
/**
 * The viewport image (VPI) class.
 *
 * Handles discovering above-the-fold images for posts/pages and stores the
 * viewport image list per post (desktop & mobile). Coordinates with the
 * remote service via queue + cron + webhook notify.
 *
 * @since   4.7
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Generate and manage ViewPort Images (VPI) for pages.
 */
class VPI extends Base {

	/**
	 * Log tag for debug output.
	 *
	 * @var string
	 */
	const LOG_TAG = '[VPI]';

	/**
	 * Action types.
	 *
	 * @var string
	 */
	const TYPE_GEN     = 'gen';
	const TYPE_CLEAR_Q = 'clear_q';

	/**
	 * VPI Desktop Meta name.
	 *
	 * @since  7.6
	 * @var string
	 */
	const POST_META = 'litespeed_vpi_list';
	/**
	 * VPI Mobile Meta name.
	 *
	 * @since  7.6
	 * @var string
	 */
	const POST_META_MOBILE = 'litespeed_vpi_list_mobile';

	/**
	 * Summary values persisted between requests (timings, last runs, etc).
	 *
	 * @var array
	 */
	protected $_summary;

	/**
	 * In-memory working queue for VPI jobs.
	 *
	 * @var array
	 */
	private $_queue;

	/**
	 * Init.
	 *
	 * @since 4.7
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Queue the current page for VPI generation.
	 *
	 * @since 4.7
	 * @return void
	 */
	public function add_to_queue() {
		$is_mobile = $this->_separate_mobile();

		global $wp;
		$request_url = home_url( $wp->request );

		if ( ! apply_filters( 'litespeed_vpi_should_queue', true, $request_url ) ) {
			return;
		}

		// Sanitize user agent coming from the server superglobal.
		$ua = ! empty( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		// Store it to prepare for cron.
		$this->_queue = $this->load_queue( 'vpi' );

		if ( count( $this->_queue ) > 500 ) {
			self::debug( 'Queue is full - 500' );
			return;
		}

		$home_id = (int) get_option( 'page_for_posts' );

		if ( ! is_singular() && ! ( $home_id > 0 && is_home() ) ) {
			self::debug( 'not single post ID' );
			return;
		}

		$post_id = is_home() ? $home_id : get_the_ID();

		$queue_k = ( $is_mobile ? 'mobile' : '' ) . ' ' . $request_url;
		if ( ! empty( $this->_queue[ $queue_k ] ) ) {
			self::debug( 'queue k existed ' . $queue_k );
			return;
		}

		$this->_queue[ $queue_k ] = [
			'url'        => apply_filters( 'litespeed_vpi_url', $request_url ),
			'post_id'    => $post_id,
			'user_agent' => substr( $ua, 0, 200 ),
			'is_mobile'  => $is_mobile,
		]; // Current UA will be used to request.
		$this->save_queue( 'vpi', $this->_queue );
		self::debug( 'Added queue_vpi [url] ' . $queue_k . ' [UA] ' . $ua );

		// Prepare cache tag for later purge.
		Tag::add( 'VPI.' . md5( $queue_k ) );
	}

	/**
	 * Cron entry point.
	 *
	 * @since 4.7
	 *
	 * @param bool $do_continue Continue processing multiple queue items within one cron tick.
	 * @return mixed Result of the handler.
	 */
	public static function cron( $do_continue = false ) {
		$_instance = self::cls();
		return $_instance->_cron_handler( $do_continue );
	}

	/**
	 * Cron queue processor.
	 *
	 * @since 4.7
	 *
	 * @param bool $do_continue Continue processing multiple queue items within one cron tick.
	 * @return void
	 */
	private function _cron_handler( $do_continue = false ) {
		self::debug( 'cron start' );
		$this->_queue = $this->load_queue( 'vpi' );

		if ( empty( $this->_queue ) ) {
			return;
		}

		// TODO: uniformize with CCSS/UCSS cron
		// Check if we need to wait due to server's try_later request
		if ( ! empty( $this->_summary[ 'vpi_next_run_after' ] ) && time() < $this->_summary['vpi_next_run_after'] ) {
			$wait_seconds = $this->_summary['vpi_next_run_after'] - time();
			self::debug( 'Waiting for try_later timeout: ' . $wait_seconds . ' seconds remaining' );
			return;
		}

		// Clear try_later flag if wait time has passed
		if ( ! empty( $this->_summary['vpi_next_run_after'] ) ) {
			unset( $this->_summary['vpi_next_run_after'] );
			self::save_summary();
			self::debug( 'Cleared try_later flag, resuming vpi processing' );
		}

		// For cron, need to check request interval too.
		if ( ! $do_continue ) {
			if ( ! empty( $this->_summary['curr_request_vpi'] ) && time() - (int) $this->_summary['curr_request_vpi'] < 300 && ! $this->conf( self::O_DEBUG ) ) {
				self::debug( 'Last request not done' );
				return;
			}
		}

		foreach ( $this->_queue as $k => $v ) {
			self::debug( 'cron job [tag] ' . $k . ' [url] ' . $v['url'] . ( $v['is_mobile'] ? ' 📱 ' : '' ) . ' [UA] ' . $v['user_agent'] );

			$res = $this->_send_req( $v['url'], $k, $v['user_agent'], $v['is_mobile'], (int) $v['post_id'] );
			if ( ! $res ) {
				// Status is wrong, drop this item from queue.
				$this->_queue = $this->load_queue( 'vpi' );
				unset( $this->_queue[ $k ] );
				$this->save_queue( 'vpi', $this->_queue );

				if ( ! $do_continue ) {
					return;
				}
			}

			// Exit queue if out of quota or service is hot.
			if ( 'out_of_quota' === $res || 'svc_hot' === $res ) {
				return;
			}

			// Handle try_later response from server
			if ( is_array( $res ) && ! empty( $res['try_later'] ) ) {
				$ttl                                  = (int) $res['try_later'];
				$next_run_time                        = time() + $ttl;
				$this->_summary['vpi_next_run_after'] = $next_run_time;
				self::save_summary();
				self::debug( 'Set next VPI cron run after ' . $ttl . ' seconds (at ' . gmdate( 'Y-m-d H:i:s', $next_run_time ) . ')' );
			}

			// only request first one if not continuing.
			if ( ! $do_continue ) {
				return;
			}
		}
	}

	/**
	 * Send request to QUIC.cloud API to generate VPI.
	 *
	 * @since 4.7
	 * @access private
	 *
	 * @param string $request_url The URL to analyze for VPI.
	 * @param string $queue_k     Queue key for this job.
	 * @param string $user_agent  Sanitized User-Agent string (<=200 chars).
	 * @param bool   $is_mobile   Whether the job is for mobile viewport.
	 * @return bool|string True on queued successfully, 'out_of_quota'/'svc_hot' on throttling, or false on error.
	 */
	private function _send_req( $request_url, $queue_k, $user_agent, $is_mobile, $post_id ) {
		$svc = Cloud::SVC_VPI;

		// Check if has credit to push or not.
		$err       = false;
		$allowance = $this->cls( 'Cloud' )->allowance( $svc, $err );
		if ( ! $allowance ) {
			self::debug( '❌ No credit: ' . $err );
			$err && Admin_Display::error( Error::msg( $err ) );
			return 'out_of_quota';
		}

		set_time_limit( 120 );

		// Update request status.
		self::save_summary( [ 'curr_request_vpi' => time() ], true );

		// Parse HTML to gather CSS content before requesting.
		$data = [
			'url'        => $request_url,
			'queue_k'    => $queue_k,
			'user_agent' => $user_agent,
			'is_mobile'  => $is_mobile ? 1 : 0, // todo: compatible w/ tablet.
		];
		self::debug( 'Generating: ', $data );

		$json = Cloud::post( $svc, $data, 30 );
		if ( ! is_array( $json ) ) {
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

		if ( empty( $json['data_vpi'] ) ) {
			self::debug( '❌ No VPI data [status] ' . $json['status'] );
		}

		// Save data.
		self::debug( '✅ Received VPI data, saving...' );
		$name      = $is_mobile ? self::POST_META_MOBILE : self::POST_META;
		$urldecode = is_array( $json['data_vpi'] ) ? array_map( 'urldecode', $json['data_vpi'] ) : urldecode( $json['data_vpi'] );
		self::debug( 'save data_vpi', $urldecode );
		$this->cls( 'Metabox' )->save( $post_id, $name, $urldecode );

		// Remove from queue
		unset( $this->_queue[ $queue_k ] );
		$this->save_queue( 'vpi', $this->_queue );
		self::debug( 'Removed from queue [q_k] ' . $queue_k );

		// Save summary data
		$this->_summary['last_request'] = $this->_summary['curr_request'];
		$this->_summary['curr_request'] = 0;

		$this->_summary['curr_request_vpi'] = 0;
		self::save_summary();

		return true;
	}

	/**
	 * Handle all request actions from main controller.
	 *
	 * @since 4.7
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_GEN:
            self::cron( true );
				break;

			case self::TYPE_CLEAR_Q:
            $this->clear_q( 'vpi' );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
