<?php

/**
 * LiteSpeed Cache Image Optm Interface
 */
class LiteSpeed_Cache_CLI_IAPI
{
	private $_img_optm_instance ;

	public function __construct()
	{
		LiteSpeed_Cache_Log::debug( 'CLI_IAPI init' ) ;

		$this->_img_optm_instance = LiteSpeed_Cache_Img_Optm::get_instance() ;
	}

	/**
	 * Sync data from IAPI server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Sync or initialize image optimization service
	 *     $ wp lscache-iapi sync
	 *
	 */
	public function sync()
	{
		$optm_summary = $this->_img_optm_instance->summary_info() ;

		$json = $this->_img_optm_instance->sync_data() ;

		if ( ! $json || empty( $json[ 'level' ] ) ) {
			return ;
		}

		WP_CLI::success('[Level] ' . $json[ 'level' ] . ' [Credit] ' . $json[ 'credit' ] ) ;

		if ( empty( $optm_summary[ 'level' ] ) || empty( $optm_summary[ 'credit_recovered' ] ) || empty( $optm_summary[ '_level_data' ] ) ) {
			return ;
		}

		if ( $json[ 'level' ] > $optm_summary[ 'level' ] ) {

			LiteSpeed_Cache_Log::debug( "[Img_Optm] Upgraded to level $json[level] !" ) ;

			WP_CLI::success('Upgraded to level ' . $json[ 'level' ] ) ;
		}
	}

	/**
	 * Send image optimization request to IAPI server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Send image optimization request
	 *     $ wp lscache-iapi push
	 *
	 */
	public function push()
	{
		$msg = $this->_img_optm_instance->request_optm() ;

		if ( ! is_array( $msg ) ) {
			WP_CLI::error( $msg ) ;
		}
		else {
			WP_CLI::success( $msg[ 'ok' ] ) ;
		}
	}

	/**
	 * Pull optimized images from IAPI server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Pull images back from IAPI
	 *     $ wp lscache-iapi pull
	 *
	 */
	public function pull()
	{
		$msg = $this->_img_optm_instance->pull_img() ;

		if ( ! is_array( $msg ) ) {
			WP_CLI::error( $msg ) ;
		}
		else {
			WP_CLI::success( $msg[ 'ok' ] ) ;
		}
	}

	/**
	 * Show optimization status based on local data
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Show optimization status
	 *     $ wp lscache-iapi status
	 *
	 */
	public function status()
	{
		$summary = $this->_img_optm_instance->summary_info() ;
		$img_count = $this->_img_optm_instance->img_count() ;

		if ( ! empty( $summary[ '_level_data' ] ) ) {
			unset( $summary[ '_level_data' ] ) ;
		}

		foreach ( array( 'reduced', 'reduced_webp' ) as $v ) {
			if ( ! empty( $summary[ $v ] ) ) {
				$summary[ $v ] = LiteSpeed_Cache_Utility::real_size( $summary[ $v ] ) ;
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
	 *     $ wp lscache-iapi s
	 *
	 */
	public function s()
	{
		$this->status() ;
	}


	/**
	 * Clean up unfinished image data from IAPI server
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Clean up unfinished requests
	 *     $ wp lscache-iapi clean
	 *
	 */
	public function clean()
	{
		$msg = $this->_img_optm_instance->destroy_unfinished() ;
		WP_CLI::success( $msg ) ;

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
	 *     $ wp lscache-iapi rm_bkup
	 *
	 */
	public function rm_bkup()
	{
		$msg = $this->_img_optm_instance->rm_bkup() ;
		WP_CLI::success( $msg ) ;
	}


}
