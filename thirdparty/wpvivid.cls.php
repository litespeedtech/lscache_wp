<?php
/**
 * The Third Party integration with WPvivid plugin.
 *
 * @since		5.4.0
 */
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class WPvivid {
	public static function preload() {
		$is_pvivid_request = strpos( $_SERVER['REQUEST_URI'], 'wpvivid' ) !== false;
		$is_pvivid_referer = ! empty( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'wpvivid' ) !== false;
		if ( $is_pvivid_request || $is_pvivid_referer ) {
			do_action( 'litespeed_disable_all', 'disable for wpvivid' );
		}
	}
}
