<?php

/**
 * The Third Party integration with the Aelia CurrencySwitcher plugin.
 *
 * @since		1.0.13
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}

LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher') ;

class LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher
{
	private static $_cookies = array(
		'aelia_cs_selected_currency',
		'aelia_customer_country',
		'aelia_customer_state',
		'aelia_tax_exempt',
	) ;

	/**
	 * Detects if WooCommerce is installed.
	 *
	 * @since 1.0.13
	 * @access public
	 * @global $GLOBALS;
	 */
	public static function detect()
	{
		if ( defined('WOOCOMMERCE_VERSION') && isset($GLOBALS['woocommerce-aelia-currencyswitcher']) && is_object($GLOBALS['woocommerce-aelia-currencyswitcher']) ) {
			LiteSpeed_Cache_API::hook_control('LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher::check_cookies') ;
			LiteSpeed_Cache_API::hook_vary('LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher::get_vary') ;
		}
	}

	/**
	 * If the page is not a woocommerce page, ignore the logic.
	 * Else check cookies. If cookies are set, set the vary headers, else do not cache the page.
	 *
	 * @since 1.0.13
	 * @access public
	 */
	public static function check_cookies()
	{
		if ( LiteSpeed_Cache_API::not_cacheable() ) {
			return ;
		}

		// NOTE: is_cart and is_checkout should also be checked, but will be checked by woocommerce anyway.
		if ( ! is_woocommerce() ) {
			return ;
		}

		if ( isset($_COOKIE) && ! empty($_COOKIE) ) {
			foreach (self::$_cookies as $cookie) {
				if ( ! empty($_COOKIE[$cookie]) ) {
					LiteSpeed_Cache_API::vary_add(self::$_cookies) ;
					return ;
				}
			}
		}

		LiteSpeed_Cache_API::set_nocache() ;
	}

	/**
	 * Hooked to the litespeed_cache_get_vary filter.
	 *
	 * If Aelia Currency Switcher is enabled, will need to add their cookies
	 * to the vary array.
	 *
	 * @since 1.0.14
	 * @access public
	 * @param array $vary_arr The current list of vary cookies.
	 * @return array The updated list of vary cookies.
	 */
	public static function get_vary($vary_arr)
	{
		if ( ! is_array($vary_arr) ) {
			return $vary_arr ;
		}
		return array_merge($vary_arr, self::$_cookies) ;
	}
}
