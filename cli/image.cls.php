<?php
namespace LiteSpeed\CLI;

defined( 'WPINC' ) || exit;

use LiteSpeed\Lang;
use LiteSpeed\Debug2;
use LiteSpeed\Img_Optm;
use LiteSpeed\Utility;
use WP_CLI;

/**
 * Image Optm API CLI
 */
class Image
{
	private $__img_optm;

	public function __construct()
	{
		Debug2::debug( 'CLI_Cloud init' );

		$this->__img_optm = Img_Optm::cls();
	}

	/**
	 * Send image optimization request to QUIC.cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Send image optimization request
	 *     $ wp litespeed-image push
	 *
	 */
	public function push()
	{
		$this->__img_optm->new_req();
	}

	/**
	 * Pull optimized images from QUIC.cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Pull images back from cloud
	 *     $ wp litespeed-image pull
	 *
	 */
	public function pull()
	{
		$this->__img_optm->pull( true );
	}

	/**
	 * Show optimization status based on local data
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Show optimization status
	 *     $ wp litespeed-image s
	 *
	 */
	public function s()
	{
		$this->status();
	}

	/**
	 * Show optimization status based on local data
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Show optimization status
	 *     $ wp litespeed-image status
	 *
	 */
	public function status()
	{
		$summary = Img_Optm::get_summary();
		$img_count = $this->__img_optm->img_count();
		foreach ( Lang::img_status() as $k => $v ) {
			if ( isset( $img_count[ "img.$k" ] )) {
				$img_count[ "$v - images" ] = $img_count[ "img.$k" ];
				unset( $img_count[ "img.$k" ] );
			}
			if ( isset( $img_count[ "group.$k" ] )) {
				$img_count[ "$v - groups" ] = $img_count[ "group.$k" ];
				unset( $img_count[ "group.$k" ] );
			}
		}

		foreach ( array( 'reduced', 'reduced_webp' ) as $v ) {
			if ( ! empty( $summary[ $v ] ) ) {
				$summary[ $v ] = Utility::real_size( $summary[ $v ] );
			}
		}

		if ( ! empty( $summary[ 'last_requested' ] ) ) {
			$summary[ 'last_requested' ] = date( 'm/d/y H:i:s', $summary[ 'last_requested' ] );
		}

		$list = array();
		foreach ( $summary as $k => $v ) {
			$list[] = array( 'key' => $k, 'value' => $v );
		}

		$list2 = array();
		foreach ( $img_count as $k => $v ) {
			if ( ! $v ) {
				continue;
			}
			$list2[] = array( 'key' => $k, 'value' => $v );
		}

		WP_CLI\Utils\format_items( 'table', $list, array( 'key', 'value' ) );

		WP_CLI::line( WP_CLI::colorize( "%CImages in database summary:%n" ) );
		WP_CLI\Utils\format_items( 'table', $list2, array( 'key', 'value' ) );
	}

	/**
	 * Clean up unfinished image data from QUIC.cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Clean up unfinished requests
	 *     $ wp litespeed-image clean
	 *
	 */
	public function clean()
	{
		$this->__img_optm->clean();

		WP_CLI::line( WP_CLI::colorize( "%CLatest status:%n" ) );

		$this->status();
	}

	/**
	 * Remove original image backups
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Remove original image backups
	 *     $ wp litespeed-image rm_bkup
	 *
	 */
	public function rm_bkup()
	{
		$this->__img_optm->rm_bkup();
	}


}
