<?php
namespace LiteSpeed\CLI;
defined( 'WPINC' ) || exit;

use LiteSpeed\Log;
use LiteSpeed\Cloud;
use LiteSpeed\Utility;
use WP_CLI;

/**
 * QUIC.cloud API CLI
 */
class Online
{
	private $__cloud ;

	public function __construct()
	{
		Log::debug( 'CLI_Cloud init' ) ;

		$this->__cloud = Cloud::get_instance() ;
	}

	/**
	 * Sync data from cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Sync online service usage info
	 *     $ wp litespeed-online sync
	 *
	 */
	public function sync()
	{
		$json = $this->__cloud->sync_usage() ;

		WP_CLI::success( 'Sync successfully' ) ;

	}

	/**
	 * Gen key from cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate domain API key from Cloud server
	 *     $ wp litespeed-online gen_key
	 *
	 */
	public function gen_key()
	{
		$json = $this->__cloud->gen_key() ;
	}

	/**
	 * Detect closest Node server for current service
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Detect closest Node for one service
	 *     $ wp litespeed-online detect_cloud img_optm
	 *
	 */
	public function detect_cloud()
	{
		$json = $this->__cloud->detect_cloud() ;
	}

	/**
	 * Send image optimization request to cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Send image optimization request
	 *     $ wp litespeed-online push
	 *
	 */
	public function push()
	{
		$this->_img_optm_instance->new_req() ;
	}

	/**
	 * Pull optimized images from cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Pull images back from cloud
	 *     $ wp litespeed-online pull
	 *
	 */
	public function pull()
	{
		$this->_img_optm_instance->pull( true ) ;
	}

	/**
	 * Show optimization status based on local data
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Show optimization status
	 *     $ wp litespeed-online status
	 *
	 */
	public function status()
	{xx
		$summary = Img_Optm::get_summary() ;
		$img_count = $this->_img_optm_instance->img_count() ;

		if ( ! empty( $summary[ '_level_data' ] ) ) {
			unset( $summary[ '_level_data' ] ) ;
		}

		foreach ( array( 'reduced', 'reduced_webp' ) as $v ) {
			if ( ! empty( $summary[ $v ] ) ) {
				$summary[ $v ] = Utility::real_size( $summary[ $v ] ) ;
			}
		}

		if ( ! empty( $summary[ 'last_requested' ] ) ) {
			$summary[ 'last_requested' ] = date( 'm/d/y H:i:s', $summary[ 'last_requested' ] ) ;
		}

		$list = array() ;
		foreach ( $summary as $k => $v ) {
			$list[] = array( 'key' => $k, 'value' => $v ) ;
		}

		$list2 = array() ;
		foreach ( $img_count as $k => $v ) {
			$list2[] = array( 'key' => $k, 'value' => $v ) ;
		}

		WP_CLI\Utils\format_items( 'table', $list, array( 'key', 'value' ) ) ;

		WP_CLI::line( WP_CLI::colorize( "%CImages in database summary:%n" ) ) ;
		WP_CLI\Utils\format_items( 'table', $list2, array( 'key', 'value' ) ) ;
	}

	/**
	 * Show optimization status based on local data
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Show optimization status
	 *     $ wp litespeed-online s
	 *
	 */
	public function s()
	{
		$this->status() ;
	}


	/**
	 * Clean up unfinished image data from cloud server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Clean up unfinished requests
	 *     $ wp litespeed-online clean
	 *
	 */
	public function clean()
	{
		$this->_img_optm_instance->clean() ;

		WP_CLI::line( WP_CLI::colorize( "%CLatest status:%n" ) ) ;

		$this->status() ;
	}

	/**
	 * Remove original image backups
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Remove original image backups
	 *     $ wp litespeed-online rm_bkup
	 *
	 */
	public function rm_bkup()
	{
		$this->_img_optm_instance->rm_bkup() ;
	}


}
