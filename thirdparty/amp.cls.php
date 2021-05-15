<?php
/**
 * The Third Party integration with AMP plugin.
 *
 * @since		2.9.8.6
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

/**
 * AMP compatibility.
 */
class AMP {

	/***
	 * Disable Optimizations on AMP endpoint.
	 */
	public static function disable_optimizations_on_amp() {

		// Check if for AMP page.
		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {

			// Bypass all optimizations.
			if ( ! defined( 'LITESPEED_BYPASS_OPTM' ) ) {
				define( 'LITESPEED_BYPASS_OPTM', true );
			}

			// Stop adding lazyload scripts.
			if ( ! defined( 'LITESPEED_NO_LAZY' ) ) {
				define( 'LITESPEED_NO_LAZY', true );
			}

			// Stop other optimizations.
			add_filter( 'litespeed_can_optm', '__return_false' );
		}
	}

	/**
	 * CSS async will affect AMP result and
	 * Lazyload will inject JS library which AMP not allowed
	 * need to force set false before load
	 *
	 * @since 2.9.8.6
	 * @access public
	 */
	public static function preload() {

		add_action( 'wp', __CLASS__ . '::disable_optimizations_on_amp' );

	}
}
