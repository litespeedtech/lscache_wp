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

class LiteSpeed_Cache_ThirdParty_AMP_Bypass
{
	/**
	 * CSS async will affect AMP result and
	 * Lazyload will inject JS library which AMP not allowed
	 * need to force set false before load
	 *
	 * @since 2.9.8.6
	 * @access public
	 */
	public static function pre_load()
	{
		if ( ! function_exists( 'is_amp_endpoint' ) || is_admin() || ! isset( $_GET[ 'amp' ] ) ) return ;

		LiteSpeed_Cache_API::force_option( LiteSpeed_Cache_API::OPID_OPTM_CSS_ASYNC, false ) ;
		LiteSpeed_Cache_API::force_option( LiteSpeed_Cache_API::OPID_MEDIA_IMG_LAZY, false ) ;
		LiteSpeed_Cache_API::force_option( LiteSpeed_Cache_API::OPID_MEDIA_IFRAME_LAZY, false ) ;
	}
}
