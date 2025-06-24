<?php
namespace LiteSpeed\CLI;

defined('WPINC') || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Report;
use WP_CLI;

/**
 * Debug API CLI
 */
class Debug {

	private $__report;

	public function __construct() {
		Debug2::debug('CLI_Debug init');

		$this->__report = Report::cls();
	}

	/**
	 * Send report
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Send env report to LiteSpeed
	 *     $ wp litespeed-debug send
	 */
	public function send() {
		$num = $this->__report->post_env();
		WP_CLI::success('Report Number = ' . $num);
	}
}
