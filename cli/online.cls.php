<?php

namespace LiteSpeed\CLI;

defined('WPINC') || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Cloud;
use WP_CLI;

/**
 * QUIC.cloud API CLI
 */
class Online {

	private $__cloud;

	public function __construct() {
		Debug2::debug('CLI_Cloud init');

		$this->__cloud = Cloud::cls();
	}

	/**
	 * Init domain on QUIC.cloud server (See https://quic.cloud/terms/)
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate domain on QUIC.cloud (! Require SERVER IP setting to be set first)
	 *     $ wp litespeed-online init
	 */
	public function init() {
		$resp = $this->__cloud->init_qc_cli();
		if (!empty($resp['qc_activated'])) {
			$main_domain = !empty($resp['main_domain']) ? $resp['main_domain'] : false;
			$this->__cloud->update_qc_activation($resp['qc_activated'], $main_domain);
			WP_CLI::success('Init successfully. Activated type: ' . $resp['qc_activated']);
		} else {
			WP_CLI::error('Init failed!');
		}
	}

	/**
	 * Init domain CDN service on QUIC.cloud server (See https://quic.cloud/terms/)
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate domain CDN on QUIC.cloud (support --format=json)
	 *     $ wp litespeed-online cdn_init --method=cname|ns
	 *     $ wp litespeed-online cdn_init --method=cname|ns --ssl-cert=xxx.pem --ssl-key=xxx
	 *     $ wp litespeed-online cdn_init --method=cfi --cf-token=xxxxxxxx
	 *     $ wp litespeed-online cdn_init --method=cfi --cf-token=xxxxxxxx  --ssl-cert=xxx.pem --ssl-key=xxx
	 */
	public function cdn_init( $args, $assoc_args ) {
		if (empty($assoc_args['method'])) {
			WP_CLI::error('Init CDN failed! Missing parameters `--method`.');
			return;
		}
		if ((!empty($assoc_args['ssl-cert']) && empty($assoc_args['ssl-key'])) || (empty($assoc_args['ssl-cert']) && !empty($assoc_args['ssl-key']))) {
			WP_CLI::error('Init CDN failed! SSL cert must be present together w/ SSL key.');
			return;
		}

		if ($assoc_args['method'] == 'cfi' && empty($assoc_args['cf-token'])) {
			WP_CLI::error('Init CDN failed! CFI must set `--cf-token`.');
			return;
		}

		$cert     = !empty($assoc_args['ssl-cert']) ? $assoc_args['ssl-cert'] : '';
		$key      = !empty($assoc_args['ssl-key']) ? $assoc_args['ssl-key'] : '';
		$cf_token = !empty($assoc_args['cf-token']) ? $assoc_args['cf-token'] : '';

		$resp = $this->__cloud->init_qc_cdn_cli($assoc_args['method'], $cert, $key, $cf_token);
		if (!empty($resp['qc_activated'])) {
			$main_domain = !empty($resp['main_domain']) ? $resp['main_domain'] : false;
			$this->__cloud->update_qc_activation($resp['qc_activated'], $main_domain, true);
		}
		if (!empty($assoc_args['format']) && $assoc_args['format'] == 'json') {
			WP_CLI::log(json_encode($resp));
			return;
		}
		if (!empty($resp['qc_activated'])) {
			WP_CLI::success('Init QC CDN successfully. Activated type: ' . $resp['qc_activated']);
		} else {
			WP_CLI::error('Init QC CDN failed!');
		}

		if (!empty($resp['cname'])) {
			WP_CLI::success('cname: ' . $resp['cname']);
		}
		if (!empty($resp['msgs'])) {
			WP_CLI::success('msgs: ' . var_export($resp['msgs'], true));
		}
	}

	/**
	 * Link user account by api key
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Link user account by api key
	 *     $ wp litespeed-online link --email=xxx@example.com --api-key=xxxx
	 */
	public function link( $args, $assoc_args ) {
		if (empty($assoc_args['email']) || empty($assoc_args['api-key'])) {
			WP_CLI::error('Link to QUIC.cloud failed! Missing parameters `--email` or `--api-key`.');
			return;
		}

		$resp = $this->__cloud->link_qc_cli($assoc_args['email'], $assoc_args['api-key']);
		if (!empty($resp['qc_activated'])) {
			$main_domain = !empty($resp['main_domain']) ? $resp['main_domain'] : false;
			$this->__cloud->update_qc_activation($resp['qc_activated'], $main_domain, true);
			WP_CLI::success('Link successfully!');
			WP_CLI::log(json_encode($resp));
		} else {
			WP_CLI::error('Link failed!');
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
	 */
	public function sync( $args, $assoc_args ) {
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

		WP_CLI\Utils\format_items('table', $list, array( 'key', 'used', 'quota', 'PayAsYouGo_Used', 'PayAsYouGo_Balance' ));
	}

	/**
	 * Check QC account status
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Check QC account status
	 *     $ wp litespeed-online cdn_status
	 */
	public function cdn_status( $args, $assoc_args ) {
		$resp = $this->__cloud->cdn_status_cli();
		WP_CLI::log(json_encode($resp));
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
	 */
	public function services( $args, $assoc_args ) {
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

		WP_CLI\Utils\format_items('table', $list, array( 'service' ));
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
	 */
	public function nodes( $args, $assoc_args ) {
		$json = Cloud::get_summary();

		$list        = array();
		$json_output = array();
		foreach (Cloud::$SERVICES as $v) {
			$server        = !empty($json['server.' . $v]) ? $json['server.' . $v] : '';
			$list[]        = array(
				'service' => $v,
				'server' => $server,
			);
			$json_output[] = array( $v => $server );
		}

		if (!empty($assoc_args['format'])) {
			WP_CLI::print_value($json_output, $assoc_args);
			return;
		}

		WP_CLI\Utils\format_items('table', $list, array( 'service', 'server' ));
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
	 *     $ wp litespeed-online ping img_optm --force
	 */
	public function ping( $param, $assoc_args ) {
		$svc   = $param[0];
		$force = !empty($assoc_args['force']);

		$json = $this->__cloud->detect_cloud($svc, $force);
		if ($json) {
			WP_CLI::success('Updated closest server.');
		}
		WP_CLI::log('svc = ' . $svc);
		WP_CLI::log('node = ' . ($json ?: '-'));
	}
}
