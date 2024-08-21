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
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

use LiteSpeed\API;

class Aelia_CurrencySwitcher
{
	private static $_cookies = array('aelia_cs_selected_currency', 'aelia_customer_country', 'aelia_customer_state', 'aelia_tax_exempt');

	/**
	 * Detects if WooCommerce is installed.
	 *
	 * @since 1.0.13
	 * @access public
	 */
	public static function detect()
	{
		if (defined('WOOCOMMERCE_VERSION') && isset($GLOBALS['woocommerce-aelia-currencyswitcher']) && is_object($GLOBALS['woocommerce-aelia-currencyswitcher'])) {
			// Not all pages need to add vary, so need to use this API to set conditions
			self::$_cookies = apply_filters('litespeed_3rd_aelia_cookies', self::$_cookies);
			add_filter('litespeed_vary_curr_cookies', __CLASS__ . '::check_cookies'); // this is for vary response headers, only add when needed
			add_filter('litespeed_vary_cookies', __CLASS__ . '::register_cookies'); // this is for rewrite rules, so always add
		}
	}

	public static function register_cookies($list)
	{
		return array_merge($list, self::$_cookies);
	}

	/**
	 * If the page is not a woocommerce page, ignore the logic.
	 * Else check cookies. If cookies are set, set the vary headers, else do not cache the page.
	 *
	 * @since 1.0.13
	 * @access public
	 */
	public static function check_cookies($list)
	{
		// NOTE: is_cart and is_checkout should also be checked, but will be checked by woocommerce anyway.
		if (!is_woocommerce()) {
			return $list;
		}

		return array_merge($list, self::$_cookies);
	}
}
