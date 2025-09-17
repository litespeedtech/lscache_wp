<?php
// phpcs:ignoreFile
/**
 * The page health
 *
 * @since      3.0
 * @package    LiteSpeed
 */
namespace LiteSpeed;

defined('WPINC') || exit();

class Health extends Base {

	const TYPE_SPEED = 'speed';
	const TYPE_SCORE = 'score';

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
	 * Test latest speed
	 *
	 * @since 3.0
	 */
	private function _ping( $type ) {
		$data = array( 'action' => $type );

		$json = Cloud::post(Cloud::SVC_HEALTH, $data, 600);

		if (empty($json['data']['before']) || empty($json['data']['after'])) {
			Debug2::debug('[Health] ❌ no data');
			return false;
		}

		$this->_summary[$type . '.before'] = $json['data']['before'];
		$this->_summary[$type . '.after']  = $json['data']['after'];

		self::save_summary();

		Debug2::debug('[Health] saved result');
	}

	/**
	 * Generate scores
	 *
	 * @since 3.0
	 */
	public function scores() {
		$speed_before = $speed_after = $speed_improved = 0;
		if (!empty($this->_summary['speed.before']) && !empty($this->_summary['speed.after'])) {
			// Format loading time
			$speed_before = $this->_summary['speed.before'] / 1000;
			if ($speed_before < 0.01) {
				$speed_before = 0.01;
			}
			$speed_before = number_format($speed_before, 2);

			$speed_after = $this->_summary['speed.after'] / 1000;
			if ($speed_after < 0.01) {
				$speed_after = number_format($speed_after, 3);
			} else {
				$speed_after = number_format($speed_after, 2);
			}

			$speed_improved = (($this->_summary['speed.before'] - $this->_summary['speed.after']) * 100) / $this->_summary['speed.before'];
			if ($speed_improved > 99) {
				$speed_improved = number_format($speed_improved, 2);
			} else {
				$speed_improved = number_format($speed_improved);
			}
		}

		$score_before = $score_after = $score_improved = 0;
		if (!empty($this->_summary['score.before']) && !empty($this->_summary['score.after'])) {
			$score_before = $this->_summary['score.before'];
			$score_after  = $this->_summary['score.after'];

			// Format Score
			$score_improved = (($score_after - $score_before) * 100) / $score_after;
			if ($score_improved > 99) {
				$score_improved = number_format($score_improved, 2);
			} else {
				$score_improved = number_format($score_improved);
			}
		}

		return array(
			'speed_before' => $speed_before,
			'speed_after' => $speed_after,
			'speed_improved' => $speed_improved,
			'score_before' => $score_before,
			'score_after' => $score_after,
			'score_improved' => $score_improved,
		);
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_SPEED:
			case self::TYPE_SCORE:
            $this->_ping($type);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
