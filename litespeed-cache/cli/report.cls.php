<?php
namespace LiteSpeed\CLI;
defined( 'WPINC' ) || exit;

use LiteSpeed\Log;
use LiteSpeed\Report;
use WP_CLI;

/**
 * Report API CLI
 */
class Report
{
	private $__report;

	public function __construct()
	{
		Log::debug( 'CLI_Report init' );

		$this->__report = Report::get_instance();
	}

	/**
	 * Send report
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Send env report to LiteSpeed
	 *     $ wp litespeed-report send
	 *
	 */
	public function send()
	{
		$this->__report->post_env();
	}

}
