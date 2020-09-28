<?php
/**
 * Third Party integration with the VillaTheme WooCommerce Multi Currency plugin.
 *
 * @see         https://villatheme.com/extensions/woo-multi-currency/
 * @since		3.4.4
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		Peter Bowyer, Maple Design Ltd
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

class VillaTheme_Multi_Currency
{
	private static $_cookies = [
		'wmc_current_currency',
    ];

	/**
	 * Detects if WooCommerce is installed.
	 *
	 * @since 1.0.13
	 * @access public
	 * @global $GLOBALS;
	 */
	public static function detect()
	{
		if ( defined('WOOCOMMERCE_VERSION') && defined('WOOMULTI_CURRENCY_VERSION') ) {
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
