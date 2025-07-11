<?php
/**
 * Image Optimization API CLI for LiteSpeed integration.
 *
 * @package LiteSpeed\CLI
 */

namespace LiteSpeed\CLI;

defined( 'WPINC' ) || exit();

use LiteSpeed\Lang;
use LiteSpeed\Debug2;
use LiteSpeed\Img_Optm;
use LiteSpeed\Utility;
use WP_CLI;

/**
 * Image Optimization API CLI
 */
class Image {

	/**
	 * Image optimization instance.
	 *
	 * @var Img_Optm
	 */
	private $img_optm;

	/**
	 * Constructor for Image CLI.
	 */
	public function __construct() {
		Debug2::debug( 'CLI_Cloud init' );

		$this->img_optm = Img_Optm::cls();
	}

	/**
	 * Batch toggle optimized images with original images.
	 *
	 * ## OPTIONS
	 *
	 * [<type>]
	 * : Type to switch to (orig or optm).
	 *
	 * ## EXAMPLES
	 *
	 *     # Switch to original images
	 *     $ wp litespeed-image batch_switch orig
	 *
	 *     # Switch to optimized images
	 *     $ wp litespeed-image batch_switch optm
	 *
	 * @param array $param Positional arguments (type).
	 */
	public function batch_switch( $param ) {
		$type = $param[0];
		$this->img_optm->batch_switch( $type );
	}

	/**
	 * Send image optimization request to QUIC.cloud server.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Send image optimization request
	 *     $ wp litespeed-image push
	 */
	public function push() {
		$this->img_optm->new_req();
	}

	/**
	 * Pull optimized images from QUIC.cloud server.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Pull images back from cloud
	 *     $ wp litespeed-image pull
	 */
	public function pull() {
		$this->img_optm->pull( true );
	}

	/**
	 * Show optimization status based on local data (alias for status).
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Show optimization status
	 *     $ wp litespeed-image s
	 */
	public function s() {
		$this->status();
	}

	/**
	 * Show optimization status based on local data.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Show optimization status
	 *     $ wp litespeed-image status
	 */
	public function status() {
		$summary   = Img_Optm::get_summary();
		$img_count = $this->img_optm->img_count();
		foreach ( Lang::img_status() as $k => $v ) {
			if ( isset( $img_count["img.$k"] ) ) {
				$img_count["$v - images"] = $img_count["img.$k"];
				unset( $img_count["img.$k"] );
			}
			if ( isset( $img_count["group.$k"] ) ) {
				$img_count["$v - groups"] = $img_count["group.$k"];
				unset( $img_count["group.$k"] );
			}
		}

		foreach ( array( 'reduced', 'reduced_webp', 'reduced_avif' ) as $v ) {
			if ( ! empty( $summary[$v] ) ) {
				$summary[$v] = Utility::real_size( $summary[$v] );
			}
		}

		if ( ! empty( $summary['last_requested'] ) ) {
			$summary['last_requested'] = gmdate( 'm/d/y H:i:s', $summary['last_requested'] );
		}

		$list = array();
		foreach ( $summary as $k => $v ) {
			$list[] = array(
				'key'   => $k,
				'value' => $v,
			);
		}

		$list2 = array();
		foreach ( $img_count as $k => $v ) {
			if ( ! $v ) {
				continue;
			}
			$list2[] = array(
				'key'   => $k,
				'value' => $v,
			);
		}

		WP_CLI\Utils\format_items( 'table', $list, array( 'key', 'value' ) );

		WP_CLI::line( WP_CLI::colorize( '%CImages in database summary:%n' ) );
		WP_CLI\Utils\format_items( 'table', $list2, array( 'key', 'value' ) );
	}

	/**
	 * Clean up unfinished image data from QUIC.cloud server.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Clean up unfinished requests
	 *     $ wp litespeed-image clean
	 */
	public function clean() {
		$this->img_optm->clean();

		WP_CLI::line( WP_CLI::colorize( '%CLatest status:%n' ) );

		$this->status();
	}

	/**
	 * Remove original image backups.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Remove original image backups
	 *     $ wp litespeed-image rm_bkup
	 */
	public function rm_bkup() {
		$this->img_optm->rm_bkup();
	}
}
