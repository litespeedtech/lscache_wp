<?php
/**
 * The Third Party integration with the Autoptimize plugin.
 *
 * @since		1.0.12
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

class Autoptimize
{
	/**
	 * Detects if Autoptimize is active.
	 *
	 *@since 1.0.12
	 *@access public
	 */
	public static function detect()
	{
		if ( defined( 'AUTOPTIMIZE_PLUGIN_DIR' ) ) {
			API::hook_purge( __CLASS__ . '::purge' ) ;
		}
	}

	/**
	 * Purges the cache when Autoptimize's cache is purged.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public static function purge()
	{
		if ( defined( 'AUTOPTIMIZE_PURGE' ) || has_action( 'shutdown', 'autoptimize_do_cachepurged_action', 11 ) ) {
			API::purge_all() ;
		}
	}
}
