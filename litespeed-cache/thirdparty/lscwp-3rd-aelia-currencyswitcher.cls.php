<?php

/**
 * The Third Party integration with the Aelia CurrencySwitcher plugin.
 *
 * @since		1.0.13
 * @since  		2.6 	Removed hook_vary as OLS supports vary header already
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
			// Not all pages need to add vary, so need to use this API to set conditions
			LiteSpeed_Cache_API::hook_vary_add( 'LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher::check_cookies' ) ;
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

		LiteSpeed_Cache_API::vary_add( self::$_cookies ) ;
	}
}
