<?php
/**
 * The Third Party integration with WCML.
 *
 * @since       3.0
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class WCML {

	private static $_currency = '';

	public static function detect() {
		if (!defined('WCML_VERSION')) {
			return;
		}

		add_filter('wcml_client_currency', __CLASS__ . '::apply_client_currency');
		add_action('wcml_set_client_currency', __CLASS__ . '::set_client_currency');
	}

	public static function set_client_currency( $currency ) {
		self::apply_client_currency($currency);

		do_action('litespeed_vary_ajax_force');
	}

	public static function apply_client_currency( $currency ) {
		if ($currency !== wcml_get_woocommerce_currency_option()) {
			self::$_currency = $currency;
			add_filter('litespeed_vary', __CLASS__ . '::apply_vary');
		}

		return $currency;
	}

	public static function apply_vary( $list ) {
		$list['wcml_currency'] = self::$_currency;
		return $list;
	}
}
