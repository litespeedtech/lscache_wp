<?php

/**
 * The cron task class.
 *
 * @since      1.1.3
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Task
{
	const CRON_ACTION_HOOK = 'litespeed_crawl_trigger' ;
	const CRON_FITLER = 'litespeed_crawl_filter' ;

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
		$id_task_enabled = LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ;
		$id_plugin_enabled = LiteSpeed_Cache_Config::OPID_ENABLED ;
		if ( $options && isset( $options[$id_task_enabled] ) && isset( $options[$id_plugin_enabled] ) ) {
			$is_active = $options[$id_task_enabled] && $options[$id_plugin_enabled] ;
		}
		else {
			$is_active = LiteSpeed_Cache::config( $id_task_enabled ) && LiteSpeed_Cache::config( $id_plugin_enabled ) ;
		}

		if ( $is_active ) {
			self::schedule_filter() ;
			self::register() ;
		}
		else {
			self::clear() ;
		}

	}

	/**
	 * Schedule cron
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function schedule_filter()
	{
		add_filter( 'cron_schedules', 'LiteSpeed_Cache_Task::lscache_cron_filter' ) ;
	}

	/**
	 * Register cron interval
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $schedules WP Hook
	 */
	public static function lscache_cron_filter( $schedules )
	{
		$interval = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::CRWL_RUN_INTERVAL ) ;
		// $wp_schedules = wp_get_schedules() ;
		if ( ! array_key_exists( self::CRON_FITLER, $schedules ) ) {
			// if ( LiteSpeed_Cache_Log::get_enabled() ) {
			// 	LiteSpeed_Cache_Log::push('Crawler cron log: ......cron filter '.$interval.' added......') ;
			// }
			$schedules[self::CRON_FITLER] = array(
				'interval' => $interval,
				'display'  => __( 'LiteSpeed Cache Custom Cron', 'litespeed-cache' ),
			) ;
		}
		return $schedules ;
	}

	/**
	 * Register the cron task to WP
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function register()
	{
		if( ! wp_next_scheduled( self::CRON_ACTION_HOOK ) ) {
			LiteSpeed_Cache_Log::debug( 'Crawler cron log: ......cron hook register......' ) ;
			wp_schedule_event( time(), self::CRON_FITLER, self::CRON_ACTION_HOOK ) ;
		}
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
		wp_clear_scheduled_hook( self::CRON_ACTION_HOOK ) ;
	}
}