<?php
/**
 * The Third Party integration with AMP plugin.
 *
 * @since		2.9.8.6
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

class AMP
{
	/**
	 * CSS async will affect AMP result and
	 * Lazyload will inject JS library which AMP not allowed
	 * need to force set false before load
	 *
	 * @since 2.9.8.6
	 * @access public
	 */
	public static function preload()
	{
		if ( ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) return ;
		API::force_option( API::O_OPTM_CSS_ASYNC, false ) ;
		API::force_option( API::O_MEDIA_LAZY, false ) ;
		API::force_option( API::O_MEDIA_IFRAME_LAZY, false ) ;
	}
}
