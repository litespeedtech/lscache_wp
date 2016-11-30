<?php

/**
 * The Third Party integration with the Aelia CurrencySwitcher plugin.
 *
 * @since		1.0.13
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
	die();
}

class LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher
{
	/**
	 * Detects if WooCommerce is installed.
	 *
	 * @since 1.0.13
	 * @access public
	 * @global $GLOBALS;
	 */
	public static function detect()
	{
		global $GLOBALS;
		if ((defined('WOOCOMMERCE_VERSION'))
			&& (isset($GLOBALS['woocommerce-aelia-currencyswitcher']))
			&& (is_object($GLOBALS['woocommerce-aelia-currencyswitcher']))) {
			add_filter('litespeed_cache_is_cacheable',
				'LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher::check_cookies');
		}
	}

	/**
	 * If the page is not a woocommerce page, ignore the logic.
	 * Else check cookies. If cookies are set, set the vary headers, else
	 * do not cache the page.
	 *
	 * @since 1.0.13
	 * @access public
	 * @param boolean $is_cacheable Previous filter's result.
	 * @return bool $is_cacheable if cacheable, false otherwise.
	 */
	public static function check_cookies($is_cacheable)
	{
		if (!$is_cacheable) {
			return false;
		}
		global $_COOKIE;
		$cookies = array(
			'aelia_cs_selected_currency',
			'aelia_customer_country',
			'aelia_customer_state',
			'aelia_tax_exempt',
		);

		// NOTE: is_cart and is_checkout should also be checked, but will
		// be checked by woocommerce anyway.
		if (!is_woocommerce()) {
			return $is_cacheable;
		}

		if ((isset($_COOKIE)) && (!empty($_COOKIE))) {
			foreach ($cookies as $cookie) {
				if (!empty($_COOKIE[$cookie])) {
					LiteSpeed_Cache_Tags::add_vary_cookie($cookies);
					return $is_cacheable;
				}
			}
		}

		return false;
	}
}

add_action('litespeed_cache_detect_thirdparty',
	'LiteSpeed_Cache_ThirdParty_Aelia_CurrencySwitcher::detect');



