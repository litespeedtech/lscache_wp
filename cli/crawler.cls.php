<?php

namespace LiteSpeed\CLI;

defined('WPINC') || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Base;
use LiteSpeed\Task;
use LiteSpeed\Crawler as Crawler2;
use WP_CLI;

/**
 * Crawler
 */
class Crawler extends Base
{
	private $__crawler;

	public function __construct()
	{
		Debug2::debug('CLI_Crawler init');

		$this->__crawler = Crawler2::cls();
	}

	/**
	 * List all crawler
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # List all crawlers
	 *     $ wp litespeed-crawler l
	 *
	 */
	public function l()
	{
		$this->list();
	}

	/**
	 * List all crawler
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # List all crawlers
	 *     $ wp litespeed-crawler list
	 *
	 */
	public function list()
	{
		$crawler_list = $this->__crawler->list_crawlers();
		$summary = Crawler2::get_summary();
		if ($summary['curr_crawler'] >= count($crawler_list)) {
			$summary['curr_crawler'] = 0;
		}
		$is_running = time() - $summary['is_running'] <= $this->conf(Base::O_CRAWLER_RUN_DURATION);

		$seconds = $this->conf(Base::O_CRAWLER_RUN_INTERVAL);
		if ($seconds > 0) {
			$recurrence = '';
			$hours = (int) floor($seconds / 3600);
			if ($hours) {
				if ($hours > 1) {
					$recurrence .= sprintf(__('%d hours', 'litespeed-cache'), $hours);
				} else {
					$recurrence .= sprintf(__('%d hour', 'litespeed-cache'), $hours);
				}
			}
			$minutes = (int) floor(($seconds % 3600) / 60);
			if ($minutes) {
				$recurrence .= ' ';
				if ($minutes > 1) {
					$recurrence .= sprintf(__('%d minutes', 'litespeed-cache'), $minutes);
				} else {
					$recurrence .= sprintf(__('%d minute', 'litespeed-cache'), $minutes);
				}
			}
		}

		$list = array();
		foreach ($crawler_list as $i => $v) {
			$hit = !empty($summary['crawler_stats'][$i]['H']) ? $summary['crawler_stats'][$i]['H'] : 0;
			$miss = !empty($summary['crawler_stats'][$i]['M']) ? $summary['crawler_stats'][$i]['M'] : 0;

			$blacklisted = !empty($summary['crawler_stats'][$i]['B']) ? $summary['crawler_stats'][$i]['B'] : 0;
			$blacklisted += !empty($summary['crawler_stats'][$i]['N']) ? $summary['crawler_stats'][$i]['N'] : 0;

			if (isset($summary['crawler_stats'][$i]['W'])) {
				$waiting = $summary['crawler_stats'][$i]['W'] ?: 0;
			} else {
				$waiting = $summary['list_size'] - $hit - $miss - $blacklisted;
			}

			$analytics = 'Waiting: ' . $waiting;
			$analytics .= '     Hit: ' . $hit;
			$analytics .= '     Miss: ' . $miss;
			$analytics .= '     Blocked: ' . $blacklisted;

			$running = '';
			if ($i == $summary['curr_crawler']) {
				$running = 'Pos: ' . ($summary['last_pos'] + 1);
				if ($is_running) {
					$running .= '(Running)';
				}
			}

			$status = $this->__crawler->is_active($i) ? '✅' : '❌';

			$list[] = array(
				'ID' => $i + 1,
				'Name' => wp_strip_all_tags($v['title']),
				'Frequency' => $recurrence,
				'Status' => $status,
				'Analytics' => $analytics,
				'Running' => $running,
			);
		}

		WP_CLI\Utils\format_items('table', $list, array('ID', 'Name', 'Frequency', 'Status', 'Analytics', 'Running'));
	}

	/**
	 * Enable one crawler
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Turn on 2nd crawler
	 *     $ wp litespeed-crawler enable 2
	 *
	 */
	public function enable($args)
	{
		$id = $args[0] - 1;
		if ($this->__crawler->is_active($id)) {
			WP_CLI::error('ID #' . $id . ' had been enabled');
			return;
		}

		$this->__crawler->toggle_activeness($id);
		WP_CLI::success('Enabled crawler #' . $id);
	}

	/**
	 * Disable one crawler
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Turn off 1st crawler
	 *     $ wp litespeed-crawler disable 1
	 *
	 */
	public function disable($args)
	{
		$id = $args[0] - 1;
		if (!$this->__crawler->is_active($id)) {
			WP_CLI::error('ID #' . $id . ' has been disabled');
			return;
		}

		$this->__crawler->toggle_activeness($id);
		WP_CLI::success('Disabled crawler #' . $id);
	}

	/**
	 * Run crawling
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Start crawling
	 *     $ wp litespeed-crawler r
	 *
	 */
	public function r()
	{
		$this->run();
	}

	/**
	 * Run crawling
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Start crawling
	 *     $ wp litespeed-crawler run
	 *
	 */
	public function run()
	{
		Task::async_call('crawler');

		$summary = Crawler2::get_summary();

		WP_CLI::success('Start crawling. Current crawler #' . ($summary['curr_crawler'] + 1) . ' [position] ' . $summary['last_pos'] . ' [total] ' . $summary['list_size']);
	}

	/**
	 * Reset position
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Reset crawler position
	 *     $ wp litespeed-crawler reset
	 *
	 */
	public function reset()
	{
		$this->__crawler->reset_pos();

		$summary = Crawler2::get_summary();

		WP_CLI::success('Reset position. Current crawler #' . ($summary['curr_crawler'] + 1) . ' [position] ' . $summary['last_pos'] . ' [total] ' . $summary['list_size']);
	}
}
