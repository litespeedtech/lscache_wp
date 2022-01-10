<?php
/**
 * Plugin Name:       LiteSpeed Cache
 * Plugin URI:        https://www.litespeedtech.com/products/cache-plugins/wordpress-acceleration
 * Description:       High-performance page caching and site optimization from LiteSpeed
 * Version:           4.4.7
 * Author:            LiteSpeed Technologies
 * Author URI:        https://www.litespeedtech.com
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl.html
 * Text Domain:       litespeed-cache
 * Domain Path:       /lang
 *
 * Copyright (C) 2015-2017 LiteSpeed Technologies, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
defined( 'WPINC' ) || exit;

if ( defined( 'LSCWP_V' ) ) {
	return;
}

! defined( 'LSCWP_V' ) && define( 'LSCWP_V', '4.4.7' );

! defined( 'LSCWP_CONTENT_DIR' ) && define( 'LSCWP_CONTENT_DIR', WP_CONTENT_DIR ) ;
! defined( 'LSCWP_DIR' ) && define( 'LSCWP_DIR', __DIR__ . '/' ) ;// Full absolute path '/var/www/html/***/wp-content/plugins/litespeed-cache/' or MU
! defined( 'LSCWP_BASENAME' ) && define( 'LSCWP_BASENAME', 'litespeed-cache/litespeed-cache.php' ) ;//LSCWP_BASENAME='litespeed-cache/litespeed-cache.php'

/**
 * This needs to be before activation because admin-rules.class.php need const `LSCWP_CONTENT_FOLDER`
 * This also needs to be before cfg.cls init because default cdn_included_dir needs `LSCWP_CONTENT_FOLDER`
 * @since  1.9.1 Moved up
 * @since  2.2.1 Moved up from core.cls
 */
! defined( 'LSCWP_CONTENT_FOLDER' ) && define( 'LSCWP_CONTENT_FOLDER', str_replace( home_url( '/' ), '', WP_CONTENT_URL ) ) ; // `wp-content`
! defined( 'LSWCP_PLUGIN_URL' ) && define( 'LSWCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) ) ;// Full URL path '//example.com/wp-content/plugins/litespeed-cache/'

/**
 * Static cache files consts
 * @since  3.0
 */
! defined( 'LITESPEED_STATIC_URL' ) && define( 'LITESPEED_STATIC_URL', WP_CONTENT_URL . '/litespeed' ) ;// Full static cache folder URL '//example.com/wp-content/litespeed'
! defined( 'LITESPEED_STATIC_DIR' ) && define( 'LITESPEED_STATIC_DIR', LSCWP_CONTENT_DIR . '/litespeed' ) ;// Full static cache folder path '/var/www/html/***/wp-content/litespeed'

! defined( 'LITESPEED_TIME_OFFSET' ) && define( 'LITESPEED_TIME_OFFSET', get_option( 'gmt_offset' ) * 60 * 60 ) ;

// Placeholder for lazyload img
! defined( 'LITESPEED_PLACEHOLDER' ) && define( 'LITESPEED_PLACEHOLDER', 'data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=' ) ;

// Auto register LiteSpeed classes
require_once LSCWP_DIR . 'autoload.php' ;

// Define CLI
if ( ( defined( 'WP_CLI' ) && WP_CLI ) || PHP_SAPI == 'cli' ) {
	! defined( 'LITESPEED_CLI' ) &&  define( 'LITESPEED_CLI', true );

	// Register CLI cmd
	if ( method_exists( 'WP_CLI', 'add_command' ) ) {
		WP_CLI::add_command( 'litespeed-option', 'LiteSpeed\CLI\Option' );
		WP_CLI::add_command( 'litespeed-purge', 'LiteSpeed\CLI\Purge' );
		WP_CLI::add_command( 'litespeed-online', 'LiteSpeed\CLI\Online' );
		WP_CLI::add_command( 'litespeed-image', 'LiteSpeed\CLI\Image' );
		WP_CLI::add_command( 'litespeed-debug', 'LiteSpeed\CLI\Debug' );
	}
}

// Server type
if ( ! defined( 'LITESPEED_SERVER_TYPE' ) ) {
	if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ADC' );
	}
	elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0 ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_OLS' );
	}
	elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ENT' );
	}
	else {
		define( 'LITESPEED_SERVER_TYPE', 'NONE' );
	}
}

// Checks if caching is allowed via server variable
if ( ! empty ( $_SERVER['X-LSCACHE'] ) ||  LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_ADC' || defined( 'LITESPEED_CLI' ) ) {
	! defined( 'LITESPEED_ALLOWED' ) &&  define( 'LITESPEED_ALLOWED', true );
}

// ESI const defination
if ( ! defined( 'LSWCP_ESI_SUPPORT' ) ) {
	define( 'LSWCP_ESI_SUPPORT', LITESPEED_SERVER_TYPE !== 'LITESPEED_SERVER_OLS' ? true : false );
}

if ( ! defined( 'LSWCP_TAG_PREFIX' ) ) {
	define( 'LSWCP_TAG_PREFIX', substr( md5( LSCWP_DIR ), -3 ) );
}

/**
 * Handle exception
 */
if ( ! function_exists( 'litespeed_exception_handler' ) ) {
	function litespeed_exception_handler( $errno, $errstr, $errfile, $errline ) {
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
}

/**
 * Overwride the WP nonce funcs outside of LiteSpeed namespace
 * @since  3.0
 */
if ( ! function_exists( 'litespeed_define_nonce_func' ) ) {
	function litespeed_define_nonce_func() {
		/**
		 * If the nonce is in none_actions filter, convert it to ESI
		 */
		function wp_create_nonce( $action = -1 ) {
			if ( ! defined( 'LITESPEED_DISABLE_ALL' ) ) {
				$control = \LiteSpeed\ESI::cls()->is_nonce_action( $action );
				if ( $control !== null ) {
					$params = array(
						'action'	=> $action,
					);
					return \LiteSpeed\ESI::cls()->sub_esi_block( 'nonce', 'wp_create_nonce ' . $action, $params, $control, true, true, true );
				}
			}

			return wp_create_nonce_litespeed_esi( $action );

		}

		/**
		 * Ori WP wp_create_nonce
		 */
		function wp_create_nonce_litespeed_esi( $action = -1 ) {
			$uid  = get_current_user_id();
			if ( ! $uid ) {
				/** This filter is documented in wp-includes/pluggable.php */
				$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
			}

			$token = wp_get_session_token();
			$i     = wp_nonce_tick();

			return substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
		}
	}
}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
if ( ! function_exists( 'run_litespeed_cache' ) ) {
	function run_litespeed_cache() {
		//Check minimum PHP requirements, which is 5.3 at the moment.
		if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
			return;
		}

		//Check minimum WP requirements, which is 4.0 at the moment.
		if ( version_compare( $GLOBALS['wp_version'], '4.0', '<' ) ) {
			return;
		}

		\LiteSpeed\Core::cls();
	}

	run_litespeed_cache();
}
