<?php

namespace LiteSpeed\CLI;

defined('WPINC') || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Cloud;
use WP_CLI;

/**
 * QUIC.cloud API CLI
 */
class Online
{
	private $__cloud;

	public function __construct()
	{
		Debug2::debug('CLI_Cloud init');

		$this->__cloud = Cloud::cls();
	}

	/**
	 * Generate domain key from QUIC.cloud server (See https://quic.cloud/terms/)
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate domain API key from QUIC.cloud
	 *     $ wp litespeed-online init
	 *
	 */
	public function init()
	{
		$key = $this->__cloud->gen_key();
		if ($key) {
			WP_CLI::success('key = ' . $key);
		}
	}

	/**
	 * Sync usage data from QUIC.cloud
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Sync QUIC.cloud service usage info
	 *     $ wp litespeed-online sync
	 *
	 */
	public function sync($args, $assoc_args)
	{
		$json = $this->__cloud->sync_usage();

		if (!empty($assoc_args['format'])) {
			WP_CLI::print_value($json, $assoc_args);
			return;
		}

		WP_CLI::success('Sync successfully');

		$list = array();
		foreach (Cloud::$SERVICES as $v) {
			$list[] = array(
				'key' => $v,
				'used' => !empty($json['usage.' . $v]['used']) ? $json['usage.' . $v]['used'] : 0,
				'quota' => !empty($json['usage.' . $v]['quota']) ? $json['usage.' . $v]['quota'] : 0,
				'PayAsYouGo_Used' => !empty($json['usage.' . $v]['pag_used']) ? $json['usage.' . $v]['pag_used'] : 0,
				'PayAsYouGo_Balance' => !empty($json['usage.' . $v]['pag_bal']) ? $json['usage.' . $v]['pag_bal'] : 0,
			);
		}

		WP_CLI\Utils\format_items('table', $list, array('key', 'used', 'quota', 'PayAsYouGo_Used', 'PayAsYouGo_Balance'));
	}

	/**
	 * List all QUIC.cloud services
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # List all services tag
	 *     $ wp litespeed-online services
	 *
	 */
	public function services($args, $assoc_args)
	{
		if (!empty($assoc_args['format'])) {
			WP_CLI::print_value(Cloud::$SERVICES, $assoc_args);
			return;
		}

		$list = array();
		foreach (Cloud::$SERVICES as $v) {
			$list[] = array(
				'service' => $v,
			);
		}

		WP_CLI\Utils\format_items('table', $list, array('service'));
	}

	/**
	 * List all QUIC.cloud servers in use
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # List all QUIC.cloud servers in use
	 *     $ wp litespeed-online nodes
	 *
	 */
	public function nodes($args, $assoc_args)
	{
		$json = Cloud::get_summary();

		$list = array();
		$json_output = array();
		foreach (Cloud::$SERVICES as $v) {
			$server = !empty($json['server.' . $v]) ? $json['server.' . $v] : '';
			$list[] = array(
				'service' => $v,
				'server' => $server,
			);
			$json_output[] = array($v => $server);
		}

		if (!empty($assoc_args['format'])) {
			WP_CLI::print_value($json_output, $assoc_args);
			return;
		}

		WP_CLI\Utils\format_items('table', $list, array('service', 'server'));
	}

	/**
	 * Detect closest node server for current service
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Detect closest node for one service
	 *     $ wp litespeed-online ping img_optm
	 *
	 */
	public function ping($param)
	{
		$svc = $param[0];
		$json = $this->__cloud->detect_cloud($svc);
		WP_CLI::success('Updated closest server.');
		WP_CLI::log('svc = ' . $svc);
		WP_CLI::log('node = ' . $json);
	}
}
