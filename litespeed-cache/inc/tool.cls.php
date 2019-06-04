<?php
/**
 * The tools
 *
 * @since      	3.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
defined( 'WPINC' ) || exit ;

class LiteSpeed_Cache_Tool
{
	private static $_instance ;

	/**
	 * Init
	 *
	 * @since  3.0
	 * @access private
	 */
	private function __construct()
	{
	}

	/**
	 * Control heartbeat
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function heartbeat()
	{
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_UTIL_HEARTBEAT ) ) {
			return ;
		}

		wp_deregister_script( 'heartbeat' ) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 3.0
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