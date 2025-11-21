<?php
/**
 * The Third Party integration with the WPLister plugin.
 *
 * Hooks WPLister inventory status updates to LiteSpeed WooCommerce backend purging.
 *
 * @since 1.1.0
 * @package LiteSpeed
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

/**
 * WPLister integration for LiteSpeed Cache.
 */
class WPLister {

	/**
	 * Detects if WooCommerce and WPLister are installed and registers hooks.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function detect() {
		if ( defined( 'WOOCOMMERCE_VERSION' ) && defined( 'WPLISTER_VERSION' ) ) {
			// User reported this will sync correctly.
			add_action( 'wplister_revise_inventory_status', [ WooCommerce::cls(), 'backend_purge' ] );
			// Added as a safety measure for WPLister Pro only.
			add_action( 'wplister_inventory_status_changed', [ WooCommerce::cls(), 'backend_purge' ] );
		}
	}
}
