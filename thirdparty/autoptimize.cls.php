<?php
/**
 * The Third Party integration with the Autoptimize plugin.
 *
 * @since		1.0.12
 */
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

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
			add_action( 'litespeed_purge_finalize', __CLASS__ . '::purge' );
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
			do_action( 'litespeed_purge_all', '3rd Autoptimize' );
		}
	}
}
