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
	 * Handle finish notifications from remote service.
	 *
	 * Expects JSON body; falls back to $_POST for legacy callers.
	 *
	 * @since 4.7
	 * @return array Response object for the cloud layer.
	 */
	public function notify() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_data = \json_decode( file_get_contents( 'php://input' ), true );
		if ( is_null( $post_data ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$post_data = $_POST;
		}
		self::debug( 'notify() data', $post_data );

		$this->_queue = $this->load_queue( 'vpi' );

		list( $post_data ) = $this->cls( 'Cloud' )->extract_msg( $post_data, 'vpi' );

		$notified_data = $post_data['data'];
		if ( empty( $notified_data ) || ! is_array( $notified_data ) ) {
			self::debug( '❌ notify exit: no notified data' );
			return Cloud::err( 'no notified data' );
		}

		// Check if it's in queue or not.
		$valid_i = 0;
		foreach ( $notified_data as $v ) {
			if ( empty( $v['request_url'] ) ) {
				self::debug( '❌ notify bypass: no request_url', $v );
				continue;
			}
			if ( empty( $v['queue_k'] ) ) {
				self::debug( '❌ notify bypass: no queue_k', $v );
				continue;
			}

			$queue_k = $v['queue_k'];

			if ( empty( $this->_queue[ $queue_k ] ) ) {
				self::debug( '❌ notify bypass: no this queue [q_k]' . $queue_k );
				continue;
			}

			// Save data.
			if ( ! empty( $v['data_vpi'] ) ) {
				$post_id   = (int) $this->_queue[ $queue_k ]['post_id'];
				$name      = ! empty( $v['is_mobile'] ) ? self::POST_META_MOBILE : self::POST_META;
				$urldecode = is_array( $v['data_vpi'] ) ? array_map( 'urldecode', $v['data_vpi'] ) : urldecode( $v['data_vpi'] );
				self::debug( 'save data_vpi', $urldecode );
				$this->cls( 'Metabox' )->save( $post_id, $name, $urldecode );

				++$valid_i;
			}

			unset( $this->_queue[ $queue_k ] );
			self::debug( 'notify data handled, unset queue [q_k] ' . $queue_k );
		}
		$this->save_queue( 'vpi', $this->_queue );

		self::debug( 'notified' );

		return Cloud::ok( [ 'count' => $valid_i ] );
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

		// For cron, need to check request interval too.
		if ( ! $do_continue ) {
			if ( ! empty( $this->_summary['curr_request_vpi'] ) && time() - $this->_summary['curr_request_vpi'] < 300 && ! $this->conf( self::O_DEBUG ) ) {
				self::debug( 'Last request not done' );
				return;
			}
		}

		$i = 0;
		foreach ( $this->_queue as $k => $v ) {
			if ( ! empty( $v['_status'] ) ) {
				continue;
			}

			self::debug( 'cron job [tag] ' . $k . ' [url] ' . $v['url'] . ( $v['is_mobile'] ? ' 📱 ' : '' ) . ' [UA] ' . $v['user_agent'] );

			++$i;
			$res = $this->_send_req( $v['url'], $k, $v['user_agent'], $v['is_mobile'] );
			if ( ! $res ) {
				// Status is wrong, drop this item from queue.
				$this->_queue = $this->load_queue( 'vpi' );
				unset( $this->_queue[ $k ] );
				$this->save_queue( 'vpi', $this->_queue );

				if ( ! $do_continue ) {
					return;
				}

				GUI::print_loading( count( $this->_queue ), 'VPI' );
				Router::self_redirect( Router::ACTION_VPI, self::TYPE_GEN );
				return;
			}

			// Exit queue if out of quota or service is hot.
			if ( 'out_of_quota' === $res || 'svc_hot' === $res ) {
				return;
			}

			$this->_queue                  = $this->load_queue( 'vpi' );
			$this->_queue[ $k ]['_status'] = 'requested';
			$this->save_queue( 'vpi', $this->_queue );
			self::debug( 'Saved to queue [k] ' . $k );

			// only request first one if not continuing.
			if ( ! $do_continue ) {
				return;
			}

			GUI::print_loading( count( $this->_queue ), 'VPI' );
			Router::self_redirect( Router::ACTION_VPI, self::TYPE_GEN );
			return;
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
	private function _send_req( $request_url, $queue_k, $user_agent, $is_mobile ) {
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

		// Gather guest HTML to send.
		$html = $this->cls( 'CSS' )->prepare_html( $request_url, $user_agent );

		if ( ! $html ) {
			return false;
		}

		// Parse HTML to gather CSS content before requesting.
		$css                = false;
		list( $css, $html ) = $this->cls( 'CSS' )->prepare_css( $html );

		if ( ! $css ) {
			self::debug( '❌ No css' );
			return false;
		}

		$data = [
			'url'        => $request_url,
			'queue_k'    => $queue_k,
			'user_agent' => $user_agent,
			'is_mobile'  => $is_mobile ? 1 : 0, // todo: compatible w/ tablet.
			'html'       => $html,
			'css'        => $css,
		];
		self::debug( 'Generating: ', $data );

		$json = Cloud::post( $svc, $data, 30 );
		if ( ! is_array( $json ) ) {
			return $json;
		}

		// Unknown status, remove this line.
		if ( 'queued' !== $json['status'] ) {
			return false;
		}

		// Save summary data.
		self::reload_summary();
		$this->_summary['last_spent_vpi']   = time() - $this->_summary['curr_request_vpi'];
		$this->_summary['last_request_vpi'] = $this->_summary['curr_request_vpi'];
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
