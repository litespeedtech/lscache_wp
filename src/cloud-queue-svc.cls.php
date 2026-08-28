<?php
/**
 * Shared base for QC queue-driven svcs.
 *
 * Hosts the cron loop, try_later throttling, allowance check, and Cloud::post
 * orchestration that UCSS / CCSS / VPI / Optimax all share. Each concrete
 * svc supplies only the bits that actually differ: svc id, payload
 * builder, response data key, and save action.
 *
 * @since   8.1
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Abstract base for queue-driven QUIC.cloud svcs.
 *
 * @since 8.1
 */
abstract class Cloud_Queue_Svc extends Base {

	const TYPE_GEN     = 'gen';
	const TYPE_CLEAR_Q = 'clear_q';

	/**
	 * In-memory working queue.
	 *
	 * @var array
	 */
	protected $_queue;

	/**
	 * Svc id slug.
	 *
	 * Used as queue type, Cloud::SVC_* suffix, and summary key prefix.
	 *
	 * @return string
	 */
	abstract protected function _svc_id();

	/**
	 * Response field name that carries the generated payload.
	 *
	 * @return string
	 */
	abstract protected function _data_key();

	/**
	 * Build the request body sent to Cloud::post.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return array
	 */
	abstract protected function _build_payload( $queue_k, $v );

	/**
	 * Persist the generated data.
	 *
	 * Returning false signals the caller to treat the item as failed and drop
	 * it from the queue without claiming success.
	 *
	 * @param mixed  $data    Value at the response data key.
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool|void True / void on success, false to abort.
	 */
	abstract protected function _save_result( $data, $queue_k, $v );

	/**
	 * Cloud::post HTTP timeout in seconds.
	 *
	 * @return int
	 */
	protected function _post_timeout() {
		return 30;
	}

	/**
	 * PHP max_execution_time set before issuing the request.
	 *
	 * @return int
	 */
	protected function _php_time_limit() {
		return 120;
	}

	/**
	 * Maximum queue length before refusing further enqueues. Concrete classes
	 * call _max_queue_size() at enqueue sites; the base loop does not enforce.
	 *
	 * @return int
	 */
	protected function _max_queue_size() {
		return 500;
	}

	/**
	 * Summary key for the in-flight request timestamp (300s gate).
	 *
	 * @return string
	 */
	protected function _curr_request_key() {
		return 'curr_request_' . $this->_svc_id();
	}

	/**
	 * Summary key for the last successful request timestamp. Read by admin
	 * templates; concrete classes whose tpl reads a legacy key (e.g. UCSS/VPI
	 * read `last_request`) must override.
	 *
	 * @return string
	 */
	protected function _last_request_key() {
		return 'last_request_' . $this->_svc_id();
	}

	/**
	 * Summary key for the last successful request duration. Mirrors
	 * _last_request_key() naming so UCSS/VPI emit the unsuffixed `last_spent`
	 * the dashboard expects.
	 *
	 * @return string
	 */
	protected function _last_spent_key() {
		return str_replace( 'last_request', 'last_spent', $this->_last_request_key() );
	}

	/**
	 * Summary key for the server-driven try_later deadline.
	 *
	 * @return string
	 */
	protected function _next_run_after_key() {
		return $this->_svc_id() . '_next_run_after';
	}

	/**
	 * Resolve the Cloud::SVC_* constant for this svc.
	 *
	 * @return string
	 */
	protected function _svc_const() {
		return constant( Cloud::class . '::SVC_' . strtoupper( $this->_svc_id() ) );
	}

	/**
	 * Validate a queue item before dispatching to Cloud. Concrete classes
	 * override to guard against malformed legacy rows that would otherwise
	 * burn a QC request and persist a bogus mapping.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool True to dispatch, false to silently drop.
	 */
	protected function _valid_queue_item( $queue_k, $v ) {
		return true;
	}

	/**
	 * Static cron entry point invoked by WP cron and admin actions.
	 *
	 * @param bool $keep_going Process the whole queue when true; otherwise stop
	 *                         after the first item.
	 * @return mixed
	 */
	public static function cron( $keep_going = false ) {
		$_instance = static::cls();
		return $_instance->_cron_handler( $keep_going );
	}

	/**
	 * Cron loop. Walks the queue, honors throttling, and dispatches each item
	 * to _send_req().
	 *
	 * @param bool $keep_going Process the whole queue when true.
	 * @return void
	 */
	protected function _cron_handler( $keep_going ) {
		$type         = $this->_svc_id();
		$this->_queue = $this->load_queue( $type );

		if ( empty( $this->_queue ) ) {
			return;
		}

		$next_run_key = $this->_next_run_after_key();
		if ( ! empty( $this->_summary[ $next_run_key ] ) && time() < $this->_summary[ $next_run_key ] ) {
			$wait_seconds = $this->_summary[ $next_run_key ] - time();
			self::debug( 'Waiting for try_later timeout: ' . $wait_seconds . ' seconds remaining' );
			return;
		}

		if ( ! empty( $this->_summary[ $next_run_key ] ) ) {
			unset( $this->_summary[ $next_run_key ] );
			self::save_summary();
			self::debug( 'Cleared try_later flag, resuming ' . $type . ' processing' );
		}

		$curr_key = $this->_curr_request_key();
		if ( ! $keep_going ) {
			if ( ! empty( $this->_summary[ $curr_key ] )
				&& time() - (int) $this->_summary[ $curr_key ] < 300
				&& ! $this->conf( self::O_DEBUG ) ) {
				self::debug( 'Last request not done' );
				return;
			}
		}

		foreach ( $this->_queue as $k => $v ) {
			if ( ! $this->_valid_queue_item( $k, $v ) ) {
				self::debug( 'invalid queue item, dropping [tag] ' . $k );
				$this->_queue = $this->load_queue( $type );
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );
				continue;
			}

			self::debug( 'cron job [request] ' . substr( hash( 'sha256', (string) $k ), 0, 12 ) . ( ! empty( $v['is_mobile'] ) ? ' 📱 ' : '' ) );

			$res = $this->_send_req( $k, $v );

			if ( ! $res ) {
				// Reload to avoid clobbering concurrent writes, then drop the item.
				// Settled decision: a failed result drops. See AGENTS.md "Settled decisions".
				$this->_queue = $this->load_queue( $type );
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );

				if ( ! $keep_going ) {
					return;
				}
				continue;
			}

			if ( 'out_of_quota' === $res || 'svc_hot' === $res ) {
				return;
			}

			if ( is_array( $res ) && ! empty( $res['try_later'] ) ) {
				$ttl                             = (int) $res['try_later'];
				$next_run_time                   = time() + $ttl;
				$this->_summary[ $next_run_key ] = $next_run_time;
				self::save_summary();
				self::debug( 'Set next ' . $type . ' cron run after ' . $ttl . ' seconds (at ' . gmdate( 'Y-m-d H:i:s', $next_run_time ) . ')' );
				return;
			}

			if ( ! $keep_going ) {
				return;
			}
		}
	}

	/**
	 * Dispatch one queue item to Cloud::post and persist the result.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool|string|array True on success, 'out_of_quota'/'svc_hot' to abort, [try_later=>ttl] to throttle, false on error.
	 */
	private function _send_req( $queue_k, $v ) {
		$svc = $this->_svc_const();

		$err       = false;
		$allowance = $this->cls( 'Cloud' )->allowance( $svc, $err );
		if ( ! $allowance ) {
			self::debug( '❌ No credit: ' . $err );
			$err && Admin_Display::error( Error::msg( $err ) );
			return 'out_of_quota';
		}

		set_time_limit( $this->_php_time_limit() );

		$curr_key                    = $this->_curr_request_key();
		$this->_summary[ $curr_key ] = time();
		self::save_summary();

		$data = $this->_build_payload( $queue_k, $v );

		$json = Cloud::post( $svc, $data, $this->_post_timeout() );
		if ( ! is_array( $json ) ) {
			return $json;
		}

		if ( ! empty( $json['try_later'] ) ) {
			$ttl = (int) $json['try_later'];
			self::debug( 'Server requested try later: ' . $ttl . ' seconds' );
			self::save_summary( [ $curr_key => 0 ], true );
			return [ 'try_later' => $ttl ];
		}

		if ( empty( $json['status'] ) ) {
			self::debug( '❌ No status in response' );
			return false;
		}

		$data_key = $this->_data_key();
		$data_val = isset( $json[ $data_key ] ) ? $json[ $data_key ] : null;
		if ( empty( $data_val ) ) {
			self::debug( '❌ No ' . $data_key . ' data [status] ' . $json['status'] );
		}

		self::debug( '✅ Received ' . $data_key . ', saving...' );
		// Concrete _save_result decides whether empty data is a hard error
		// (returns false to drop without success accounting) or a sentinel to
		// persist as-is (returns true to mark the URL as processed).
		if ( false === $this->_save_result( $data_val, $queue_k, $v ) ) {
			self::debug( '❌ _save_result returned false [k] ' . $queue_k );
			return false;
		}

		// Reload to avoid clobbering concurrent writes, then drop the item.
		$type         = $this->_svc_id();
		$this->_queue = $this->load_queue( $type );
		unset( $this->_queue[ $queue_k ] );
		$this->save_queue( $type, $this->_queue );
		self::debug( 'Removed from queue [q_k] ' . $queue_k );

		$curr_time                                    = (int) $this->_summary[ $curr_key ];
		$this->_summary[ $this->_last_spent_key() ]   = time() - $curr_time;
		$this->_summary[ $this->_last_request_key() ] = $curr_time;
		$this->_summary[ $curr_key ]                  = 0;
		self::save_summary();

		return true;
	}

	/**
	 * Admin action handler dispatched by Router.
	 *
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case static::TYPE_GEN:
				static::cron( true );
				break;

			case static::TYPE_CLEAR_Q:
				$this->clear_q( $this->_svc_id() );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
