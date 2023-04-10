<?php
/**
 * The Third Party integration with the Perfmatters plugin.
 *
 * @since		4.4.5
 */
namespace LiteSpeed\Thirdparty;
defined( 'WPINC' ) || exit;

class Perfmatters {
	public static function preload() {
		if ( ! defined( 'PERFMATTERS_VERSION' ) ) return;

		if ( is_admin() ) return;

		if ( has_action( 'shutdown','perfmatters_script_manager' ) !== false ) {
			add_action( 'init', __CLASS__ . '::disable_litespeed_esi', 4 );
		}
	}

	public static function disable_litespeed_esi() {
		defined( 'LITESPEED_ESI_OFF' ) || define( 'LITESPEED_ESI_OFF', true );
	}
}
