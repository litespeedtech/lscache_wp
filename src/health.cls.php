<?php
/**
 * The page health
 *
 * @since      3.0
 * @package    LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Health check handler.
 *
 * @since 3.0
 */
class Health extends Base {

	const LOG_TAG = '[Health]';

	const TYPE_SPEED = 'speed';
	const TYPE_SCORE = 'score';

	/**
	 * Cached summary data.
	 *
	 * @var array
	 */
	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Cron entry point — called by WP cron to process pending health request.
	 *
	 * @since 8.0
	 */
	public static function cron() {
		$_instance = self::cls();
		if ( empty( $_instance->_summary['pending'] ) ) {
			return;
		}
		$_instance->_run( $_instance->_summary['pending'] );
	}

	/**
	 * Run health check request for given type.
	 *
	 * @since 8.0
	 *
	 * @param string $type TYPE_SPEED or TYPE_SCORE.
	 */
	private function _run( $type ) {
		// If try_later cooldown is still active, defer to cron
		if ( ! empty( $this->_summary['health_next_run_after'] ) && time() < $this->_summary['health_next_run_after'] ) {
			$this->_summary['pending'] = $type;
			self::save_summary();
			self::debug( 'Deferred to cron (try_later active) [type] ' . $type );
			return;
		}

		// Clear expired try_later flag
		unset( $this->_summary['health_next_run_after'] );

		self::debug( 'Running [type] ' . $type );

		$res = $this->_send_req( $type );

		if ( $res ) {
			unset( $this->_summary['pending'] );
		} else {
			// Failed or got try_later — mark pending for cron retry
			$this->_summary['pending'] = $type;
		}
		self::save_summary();
	}

	/**
	 * Send health check request to cloud service.
	 *
	 * @since 6.0
	 *
	 * @param string $type TYPE_SPEED or TYPE_SCORE.
	 * @return bool
	 */
	private function _send_req( $type ) {
		$data = [ 'action' => $type ];

		$json = Cloud::post( Cloud::SVC_HEALTH, $data, 600 );

		if ( ! is_array( $json ) ) {
			self::debug( 'Invalid response' );
			return false;
		}

		// Handle try_later from QC
		if ( ! empty( $json['try_later'] ) ) {
			$ttl                                     = (int) $json['try_later'];
			$this->_summary['health_next_run_after'] = time() + $ttl;
			self::save_summary();
			self::debug( 'Server requested try_later: ' . $ttl . 's' );
			return false;
		}

		if ( empty( $json['data']['before'] ) || empty( $json['data']['after'] ) ) {
			self::debug( 'No data returned from cloud' );
			return false;
		}

		$this->_summary[ $type . '.before' ] = $json['data']['before'];
		$this->_summary[ $type . '.after' ]  = $json['data']['after'];

		self::save_summary();

		self::debug( 'Saved result' );
		return true;
	}

	/**
	 * Generate scores
	 *
	 * @since 3.0
	 */
	public function scores() {
		$speed_before   = 0;
		$speed_after    = 0;
		$speed_improved = 0;
		if ( ! empty( $this->_summary['speed.before'] ) && ! empty( $this->_summary['speed.after'] ) ) {
			// Format loading time
			$speed_before = $this->_summary['speed.before'] / 1000;
			if ( $speed_before < 0.01 ) {
				$speed_before = 0.01;
			}
			$speed_before = number_format( $speed_before, 2 );

			$speed_after = $this->_summary['speed.after'] / 1000;
			if ( $speed_after < 0.01 ) {
				$speed_after = number_format( $speed_after, 3 );
			} else {
				$speed_after = number_format( $speed_after, 2 );
			}

			$speed_improved = ( ( $this->_summary['speed.before'] - $this->_summary['speed.after'] ) * 100 ) / $this->_summary['speed.before'];
			if ( $speed_improved > 99 ) {
				$speed_improved = number_format( $speed_improved, 2 );
			} else {
				$speed_improved = number_format( $speed_improved );
			}
		}

		$score_before   = 0;
		$score_after    = 0;
		$score_improved = 0;
		if ( ! empty( $this->_summary['score.before'] ) && ! empty( $this->_summary['score.after'] ) ) {
			$score_before = $this->_summary['score.before'];
			$score_after  = $this->_summary['score.after'];

			// Format Score
			$score_improved = ( ( $score_after - $score_before ) * 100 ) / $score_after;
			if ( $score_improved > 99 ) {
				$score_improved = number_format( $score_improved, 2 );
			} else {
				$score_improved = number_format( $score_improved );
			}
		}

		return [
			'speed_before'   => $speed_before,
			'speed_after'    => $speed_after,
			'speed_improved' => $speed_improved,
			'score_before'   => $score_before,
			'score_after'    => $score_after,
			'score_improved' => $score_improved,
		];
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_SPEED:
			case self::TYPE_SCORE:
				$this->_run( $type );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
