<?php
/**
 * The Third Party integration with WCML.
 *
 * @since 3.0
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides compatibility with WCML for currency handling.
 */
class WCML {

	/**
	 * Holds the current WCML currency.
	 *
	 * @var string
	 */
	private static $_currency = '';

	/**
	 * Detect if WCML is active and register hooks.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if (!defined('WCML_VERSION')) {
			return;
		}
		
		add_filter('wcml_user_store_strategy', __CLASS__ . '::set_cookie_strategy', 10, 2);

		add_filter('wcml_client_currency', __CLASS__ . '::apply_client_currency');
		add_action('wcml_set_client_currency', __CLASS__ . '::set_client_currency');

		add_filter('litespeed_vary_curr_cookies', __CLASS__ . '::apply_vay');
		add_filter('litespeed_vary_cookies', __CLASS__ . '::apply_vay');
	}

	/**
	 * Sets the client currency and triggers vary updates.
	 *
	 * @since 3.0
	 * @access public
	 * @param string $currency The currency code to set.
	 * @return void
	 */
	public static function set_client_currency( $currency ) {
		self::apply_client_currency($currency);
		do_action('litespeed_vary_ajax_force');
	}

	/**
	 * Applies the client currency and adjusts vary accordingly.
	 *
	 * @since 3.0
	 * @access public
	 * @param string $currency The currency code to apply.
	 * @return string The applied currency.
	 */
	public static function apply_client_currency( $currency ) {
		self::$_currency = $currency;
		add_filter('litespeed_vary', __CLASS__ . '::apply_vary');

		if (wcml_get_woocommerce_currency_option() !== $currency) {
			self::$_currency = $currency;
			add_filter('litespeed_vary', __CLASS__ . '::apply_vary');
		}
    
		return $currency;
	}

	/**
	 * Appends WCML currency to vary list.
	 *
	 * @since 3.0
	 * @access public
	 * @param array $vary_list The existing vary list.
	 * @return array The updated vary list including WCML currency.
	 */
	public static function apply_vary( $list ) {
		$list['wcml_currency'] = self::$_currency;

		if ( self::$_currency === wcml_get_woocommerce_currency_option() ) {
			unset( $list['wcml_currency'] );
		}
		
		return $list;
	}
}
