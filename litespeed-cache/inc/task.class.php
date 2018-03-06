<?php

/**
 * The cron task class.
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Task
{
	private static $_instance ;

	const CRON_ACTION_HOOK_CRAWLER = 'litespeed_crawl_trigger' ;
	const CRON_ACTION_HOOK_IMGOPTM = 'litespeed_imgoptm_trigger' ;
	const CRON_FITLER_CRAWLER = 'litespeed_crawl_filter' ;
	const CRON_FITLER_IMGOPTM = 'litespeed_imgoptm_filter' ;

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
		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_OPTM_CRON_OFF ) && LiteSpeed_Cache_Img_Optm::check_need_pull() ) {
			self::schedule_filter_imgoptm() ;

			add_action( self::CRON_ACTION_HOOK_IMGOPTM, 'LiteSpeed_Cache_Img_Optm::pull_optimized_img' ) ;
		}
		else {
			// wp_clear_scheduled_hook( self::CRON_ACTION_HOOK_IMGOPTM ) ;
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

		// If cron setting is on, check cache status
		if ( $is_active ) {
			if ( defined( 'LITESPEED_NEW_OFF' ) ) {
				$is_active = false ;
			}
			elseif ( ! defined( 'LITESPEED_ON' ) && ! defined( 'LITESPEED_NEW_ON' ) ) {
				$is_active = false ;
			}
		}

		if ( ! $is_active ) {
			self::clear() ;
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
		add_filter( 'cron_schedules', 'LiteSpeed_Cache_Task::lscache_cron_filter_imgoptm' ) ;

		// Schedule event here to see if it can lost again or not
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK_IMGOPTM ) ) {
			LiteSpeed_Cache_Log::debug( 'Cron log: ......img optimization cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER_IMGOPTM, self::CRON_ACTION_HOOK_IMGOPTM ) ;
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
	public static function lscache_cron_filter_imgoptm( $schedules )
	{
		if ( ! array_key_exists( self::CRON_FITLER_IMGOPTM, $schedules ) ) {
			$schedules[ self::CRON_FITLER_IMGOPTM ] = array(
				'interval' => 60,
				'display'  => __( 'LiteSpeed Cache Custom Cron ImgOptm', 'litespeed-cache' ),
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