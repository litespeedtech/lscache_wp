<?php

/**
 * The Third Party integration with AMP plugin.
 *
 * @since		2.9.8.6
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}

LiteSpeed_Cache_API::hook_init( 'LiteSpeed_Cache_ThirdParty_AMP_Bypass::pre_load' ) ;
LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_AMP_Bypass' ) ;

class LiteSpeed_Cache_ThirdParty_AMP_Bypass
{
	/**
	 * Just CSS async on Optm page will affect AMP result, need to force set false before load
	 *
	 * @since 2.9.8.6
	 * @access public
	 */
	public static function pre_load()
	{
		if ( ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) return ;
		LiteSpeed_Cache_API::force_option( LiteSpeed_Cache_API::config(), false ) ;
	}

	/**
	 * Lazyload script inject in not allow on AMP
	 *
	 * @since 2.9.8.6
	 * @access public
	 */
	public static function detect()
	{
		if ( ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) return ;
		define( 'LITESPEED_NO_LAZY', true ) ;
	}
}
