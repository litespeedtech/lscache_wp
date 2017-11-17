<?php
/**
 * The Third Party integration with the WPLister plugin.
 *
 * @since        1.1.0
 * @package        LiteSpeed_Cache
 * @subpackage    LiteSpeed_Cache/thirdparty
 * @author        LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}

LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_WPLister') ;

class LiteSpeed_Cache_ThirdParty_WPLister
{
	/**
	 * Detects if WooCommerce and WPLister are installed.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function detect()
	{
		if ( defined('WOOCOMMERCE_VERSION') && defined('WPLISTER_VERSION') ) {
			// User reported this will sync correctly.
			add_action('wplister_revise_inventory_status', array( LiteSpeed_Cache_ThirdParty_WooCommerce::get_instance(), 'backend_purge' ) ) ;
			// Added as a safety measure for WPLister Pro only.
			add_action('wplister_inventory_status_changed', array( LiteSpeed_Cache_ThirdParty_WooCommerce::get_instance(), 'backend_purge' ) ) ;
		}
	}

}
