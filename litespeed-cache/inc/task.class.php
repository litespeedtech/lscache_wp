<?php
/**
 * The cron task class.
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Task
{
	private static $_instance ;

	const CRON_ACTION_HOOK_CRAWLER = 'litespeed_crawl_trigger' ;
	const CRON_ACTION_HOOK_IMGOPTM = 'litespeed_imgoptm_trigger' ;
	const CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST = 'litespeed_imgoptm_auto_request_trigger' ;
	const CRON_ACTION_HOOK_CCSS = 'litespeed_ccss_trigger' ;
	const CRON_ACTION_HOOK_IMG_PLACEHOLDER = 'litespeed_img_placeholder_trigger' ;
	const CRON_FITLER_CRAWLER = 'litespeed_crawl_filter' ;
	const CRON_FITLER = 'litespeed_filter' ;

	/**
	 * Init
	 *
	 * @since  1.6
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( 'Task init' ) ;

		// Register crawler cron
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ) && LiteSpeed_Cache_Router::can_crawl() ) {
			// keep cron intval filter
			self::schedule_filter_crawler() ;

			// cron hook
			add_action( self::CRON_ACTION_HOOK_CRAWLER, 'LiteSpeed_Cache_Crawler::crawl_data' ) ;
		}

		// Register img optimization fetch ( always fetch immediately )
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_CRON ) ) {
			self::schedule_filter_imgoptm() ;

			add_action( self::CRON_ACTION_HOOK_IMGOPTM, 'LiteSpeed_Cache_Img_Optm::cron_pull_optimized_img' ) ;
		}

		// Image optm auto request
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_MEDIA_OPTM_AUTO ) ) {
			self::schedule_filter_imgoptm_auto_request() ;

			add_action( self::CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST, 'LiteSpeed_Cache_Img_Optm::cron_auto_request' ) ;
		}

		// Register ccss generation
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_OPTM_CCSS_ASYNC ) && LiteSpeed_Cache_CSS::has_queue() ) {
			self::schedule_filter_ccss() ;

			add_action( self::CRON_ACTION_HOOK_CCSS, 'LiteSpeed_Cache_CSS::cron_ccss' ) ;
		}

		// Register image placeholder generation
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_PLACEHOLDER_RESP_ASYNC ) && LiteSpeed_Cache_Media::has_queue() ) {
			self::schedule_filter_placeholder() ;

			add_action( self::CRON_ACTION_HOOK_IMG_PLACEHOLDER, 'LiteSpeed_Cache_Media::cron_placeholder' ) ;
		}
	}

	/**
	 * Enable/Disable cron task
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function enable()
	{
		$id = LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ;

		// get new setting
		$is_enabled = ! LiteSpeed_Cache::config( $id ) ;

		// log
		LiteSpeed_Cache_Log::debug( 'Crawler log: Crawler is ' . ( $is_enabled ? 'enabled' : 'disabled' ) ) ;

		// update config
		LiteSpeed_Cache_Config::get_instance()->update_options( array( $id => $is_enabled ) ) ;

		self::update() ;

		echo json_encode( array( 'enable' => $is_enabled ) ) ;
		wp_die() ;
	}

	/**
	 * Update cron status
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $options The options to check if cron should be enabled
	 */
	public static function update( $options = false )
	{
		$id = LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ;
		if ( $options && isset( $options[ $id ] ) ) {
			$is_active = $options[$id] ;
		}
		else {
			$is_active = LiteSpeed_Cache::config( $id ) ;
		}

		if ( ! $is_active ) {
			self::clear() ;
		}

	}

	/**
	 * Schedule cron img optm auto request
	 *
	 * @since 2.4.1
	 * @access public
	 */
	public static function schedule_filter_imgoptm_auto_request()
	{
		add_filter( 'cron_schedules', 'LiteSpeed_Cache_Task::lscache_cron_filter' ) ;

		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST ) ) {
			LiteSpeed_Cache_Log::debug( 'Cron log: ......img optm auto request cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_IMGOPTM_AUTO_REQUEST ) ;
		}
	}

	/**
	 * Schedule cron img optimization
	 *
	 * @since 1.6.1
	 * @access public
	 */
	public static function schedule_filter_imgoptm()
	{
		add_filter( 'cron_schedules', 'LiteSpeed_Cache_Task::lscache_cron_filter' ) ;

		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_IMGOPTM ) ) {
			LiteSpeed_Cache_Log::debug( 'Cron log: ......img optimization cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_IMGOPTM ) ;
		}
	}

	/**
	 * Schedule cron ccss generation
	 *
	 * @since 2.3
	 * @access public
	 */
	public static function schedule_filter_ccss()
	{
		add_filter( 'cron_schedules', 'LiteSpeed_Cache_Task::lscache_cron_filter' ) ;

		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_CCSS ) ) {
			LiteSpeed_Cache_Log::debug( 'Cron log: ......ccss cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_CCSS ) ;
		}
	}

	/**
	 * Schedule cron image placeholder generation
	 *
	 * @since 2.5.1
	 * @access public
	 */
	public static function schedule_filter_placeholder()
	{
		add_filter( 'cron_schedules', 'LiteSpeed_Cache_Task::lscache_cron_filter' ) ;

		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_IMG_PLACEHOLDER ) ) {
			LiteSpeed_Cache_Log::debug( 'Cron log: ......image placeholder cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK_IMG_PLACEHOLDER ) ;
		}
	}

	/**
	 * Schedule cron crawler
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function schedule_filter_crawler()
	{
		add_filter( 'cron_schedules', 'LiteSpeed_Cache_Task::lscache_cron_filter_crawler' ) ;

		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_CRAWLER ) ) {
			LiteSpeed_Cache_Log::debug( 'Crawler cron log: ......cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER_CRAWLER, self::CRON_ACTION_HOOK_CRAWLER ) ;
		}
	}

	/**
	 * Register cron interval imgoptm
	 *
	 * @since 1.6.1
	 * @access public
	 * @param array $schedules WP Hook
	 */
	public static function lscache_cron_filter( $schedules )
	{
		if ( ! array_key_exists( self::CRON_FITLER, $schedules ) ) {
			$schedules[ self::CRON_FITLER ] = array(
				'interval' => 60,
				'display'  => __( 'LiteSpeed Cache Custom Cron Common', 'litespeed-cache' ),
			) ;
		}
		return $schedules ;
	}

	/**
	 * Register cron interval
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $schedules WP Hook
	 */
	public static function lscache_cron_filter_crawler( $schedules )
	{
		$interval = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::CRWL_RUN_INTERVAL ) ;
		// $wp_schedules = wp_get_schedules() ;
		if ( ! array_key_exists( self::CRON_FITLER_CRAWLER, $schedules ) ) {
			// 	LiteSpeed_Cache_Log::debug('Crawler cron log: ......cron filter '.$interval.' added......') ;
			$schedules[ self::CRON_FITLER_CRAWLER ] = array(
				'interval' => $interval,
				'display'  => __( 'LiteSpeed Cache Custom Cron Crawler', 'litespeed-cache' ),
			) ;
		}
		return $schedules ;
	}

	/**
	 * Clear cron
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function clear()
	{
		LiteSpeed_Cache_Log::debug( 'Crawler cron log: ......cron hook cleared......' ) ;
		wp_clear_scheduled_hook( self::CRON_ACTION_HOOK_CRAWLER ) ;
	}


	/**
	 * Get the current instance object.
	 *
	 * @since 1.6
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}