<?php
/**
 * The Third Party integration with the WPLister plugin.
 *
 * @since        1.1.0
 * @package        LiteSpeed_Cache
 * @subpackage    LiteSpeed_Cache/thirdparty
 * @author        LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

class WPLister
{
	/**
	 * Detects if WooCommerce and WPLister are installed.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function detect()
	{
		if ( defined( 'WOOCOMMERCE_VERSION' ) && defined( 'WPLISTER_VERSION' ) ) {
			// User reported this will sync correctly.
			add_action( 'wplister_revise_inventory_status', array( WooCommerce::get_instance(), 'backend_purge' ) ) ;
			// Added as a safety measure for WPLister Pro only.
			add_action( 'wplister_inventory_status_changed', array( WooCommerce::get_instance(), 'backend_purge' ) ) ;
		}
	}

}
