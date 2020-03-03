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
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

class Aelia_CurrencySwitcher
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
			API::hook_vary_add( __CLASS__ . '::check_cookies' ) ;
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
		if ( ! apply_filters( 'litespeed_control_cacheable', false ) ) {
			return;
		}

		// NOTE: is_cart and is_checkout should also be checked, but will be checked by woocommerce anyway.
		if ( ! is_woocommerce() ) {
			return ;
		}

		API::vary_add( self::$_cookies ) ;
	}
}
