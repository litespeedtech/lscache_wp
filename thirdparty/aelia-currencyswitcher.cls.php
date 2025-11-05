<?php
/**
 * The Third Party integration with the Aelia CurrencySwitcher plugin.
 *
 * @since      1.0.13
 * @since      2.6     Removed hook_vary as OLS supports vary header already
 * @package    LiteSpeed
 * @subpackage LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

/**
 * Integration layer for Aelia Currency Switcher.
 *
 * Registers the plugin cookies as Vary drivers so cached pages can differ by
 * selected currency / location. Hooks both the runtime vary list (headers)
 * and the rewrite-rules vary list (always needed).
 */
class Aelia_CurrencySwitcher {

	/**
	 * Cookie names used by Aelia Currency Switcher to determine currency & geo.
	 *
	 * @var string[]
	 */
	private static $_cookies = array( 'aelia_cs_selected_currency', 'aelia_customer_country', 'aelia_customer_state', 'aelia_tax_exempt' );

	/**
	 * Detects if WooCommerce + Aelia Currency Switcher are present and registers hooks.
	 *
	 * @since 1.0.13
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if ( defined( 'WOOCOMMERCE_VERSION' ) && isset( $GLOBALS['woocommerce-aelia-currencyswitcher'] ) && is_object( $GLOBALS['woocommerce-aelia-currencyswitcher'] ) ) {
			// Not all pages need to add vary, so allow sites to restrict via filter.
			self::$_cookies = apply_filters( 'litespeed_3rd_aelia_cookies', self::$_cookies );

			// Add cookies to the active vary header list (conditionally used at runtime).
			add_filter( 'litespeed_vary_curr_cookies', __CLASS__ . '::check_cookies' );

			// Ensure rewrite rules are aware of these cookies (always include).
			add_filter( 'litespeed_vary_cookies', __CLASS__ . '::register_cookies' );
		}
	}

	/**
	 * Ensure Aelia cookies are part of the global vary cookie registry.
	 *
	 * @since 1.0.13
	 *
	 * @param string[] $cookies Current list of vary cookies.
	 * @return string[] Updated list including Aelia cookies.
	 */
	public static function register_cookies( $cookies ) {
		return array_merge( $cookies, self::$_cookies );
	}

	/**
	 * Conditionally append Aelia cookies to the vary header set for WooCommerce pages.
	 *
	 * If the page is not a WooCommerce page, leave the list unchanged.
	 * Otherwise, append Aelia's cookies so responses vary correctly.
	 *
	 * @since 1.0.13
	 * @access public
	 *
	 * @param string[] $cookies Current list of vary cookies for the response.
	 * @return string[] Potentially augmented list of vary cookies.
	 */
	public static function check_cookies( $cookies ) {
		// NOTE: is_cart and is_checkout are handled by WooCommerce itself.
		if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
			return $cookies;
		}

		return array_merge( $cookies, self::$_cookies );
	}
}
