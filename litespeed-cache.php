<?php
/**
 * Plugin Name:       LiteSpeed Cache
 * Plugin URI:        https://www.litespeedtech.com/products/cache-plugins/wordpress-acceleration
 * Description:       High-performance page caching and site optimization from LiteSpeed
 * Version:           7.6.2
 * Author:            LiteSpeed Technologies
 * Author URI:        https://www.litespeedtech.com
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       litespeed-cache
 * Domain Path:       /lang
 *
 * @package           LiteSpeed
 *
 * Copyright (C) 2015-2025 LiteSpeed Technologies, Inc.
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

defined( 'WPINC' ) || exit();

if ( defined( 'LSCWP_V' ) ) {
	return;
}

! defined( 'LSCWP_V' ) && define( 'LSCWP_V', '7.6.2' );

! defined( 'LSCWP_CONTENT_DIR' ) && define( 'LSCWP_CONTENT_DIR', WP_CONTENT_DIR );
! defined( 'LSCWP_DIR' ) && define( 'LSCWP_DIR', __DIR__ . '/' ); // Full absolute path '/var/www/html/***/wp-content/plugins/litespeed-cache/' or MU
! defined( 'LSCWP_BASENAME' ) && define( 'LSCWP_BASENAME', 'litespeed-cache/litespeed-cache.php' ); // LSCWP_BASENAME='litespeed-cache/litespeed-cache.php'

/**
 * This needs to be before activation because admin-rules.class.php need const `LSCWP_CONTENT_FOLDER`
 * This also needs to be before cfg.cls init because default cdn_included_dir needs `LSCWP_CONTENT_FOLDER`
 *
 * @since  5.2 Auto correct protocol for CONTENT URL
 */
$wp_content_url = WP_CONTENT_URL;
$site_url       = site_url( '/' );
if ( 'http:' === substr( $wp_content_url, 0, 5 ) && 'https' === substr( $site_url, 0, 5 ) ) {
	$wp_content_url = str_replace( 'http://', 'https://', $wp_content_url );
}
! defined( 'LSCWP_CONTENT_FOLDER' ) && define( 'LSCWP_CONTENT_FOLDER', str_replace( $site_url, '', $wp_content_url ) ); // `wp-content`
unset( $site_url );
! defined( 'LSWCP_PLUGIN_URL' ) && define( 'LSWCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // Full URL path '//example.com/wp-content/plugins/litespeed-cache/'

/**
 * Static cache files consts
 *
 * @since  3.0
 */
! defined( 'LITESPEED_DATA_FOLDER' ) && define( 'LITESPEED_DATA_FOLDER', 'litespeed' );
! defined( 'LITESPEED_STATIC_URL' ) && define( 'LITESPEED_STATIC_URL', $wp_content_url . '/' . LITESPEED_DATA_FOLDER ); // Full static cache folder URL '//example.com/wp-content/litespeed'
unset( $wp_content_url );
! defined( 'LITESPEED_STATIC_DIR' ) && define( 'LITESPEED_STATIC_DIR', LSCWP_CONTENT_DIR . '/' . LITESPEED_DATA_FOLDER ); // Full static cache folder path '/var/www/html/***/wp-content/litespeed'

! defined( 'LITESPEED_TIME_OFFSET' ) && define( 'LITESPEED_TIME_OFFSET', get_option( 'gmt_offset' ) * 60 * 60 );

// Placeholder for lazyload img
! defined( 'LITESPEED_PLACEHOLDER' ) && define( 'LITESPEED_PLACEHOLDER', 'data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=' );

// Auto register LiteSpeed classes
require_once LSCWP_DIR . 'autoload.php';

// Define CLI
if ( ( defined( 'WP_CLI' ) && constant('WP_CLI') ) || 'cli' === PHP_SAPI ) {
	! defined( 'LITESPEED_CLI' ) && define( 'LITESPEED_CLI', true );

	// Register CLI cmd
	if ( method_exists( 'WP_CLI', 'add_command' ) ) {
		WP_CLI::add_command( 'litespeed-option', 'LiteSpeed\CLI\Option' );
		WP_CLI::add_command( 'litespeed-purge', 'LiteSpeed\CLI\Purge' );
		WP_CLI::add_command( 'litespeed-online', 'LiteSpeed\CLI\Online' );
		WP_CLI::add_command( 'litespeed-image', 'LiteSpeed\CLI\Image' );
		WP_CLI::add_command( 'litespeed-debug', 'LiteSpeed\CLI\Debug' );
		WP_CLI::add_command( 'litespeed-presets', 'LiteSpeed\CLI\Presets' );
		WP_CLI::add_command( 'litespeed-crawler', 'LiteSpeed\CLI\Crawler' );
		WP_CLI::add_command( 'litespeed-database', 'LiteSpeed\CLI\Database' );
	}
}

// Server type
if ( ! defined( 'LITESPEED_SERVER_TYPE' ) ) {
	$http_x_lscache  = isset( $_SERVER['HTTP_X_LSCACHE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_LSCACHE'] ) ) : '';
	$lsws_edition    = isset( $_SERVER['LSWS_EDITION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['LSWS_EDITION'] ) ) : '';
	$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';

	if ( $http_x_lscache ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ADC' );
	} elseif ( 0 === strpos( $lsws_edition, 'Openlitespeed' ) ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_OLS' );
	} elseif ( 'LiteSpeed' === $server_software ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ENT' );
	} else {
		define( 'LITESPEED_SERVER_TYPE', 'NONE' );
	}
}

// Checks if caching is allowed via server variable
if ( ! empty( $_SERVER['X-LSCACHE'] ) || 'LITESPEED_SERVER_ADC' === LITESPEED_SERVER_TYPE || defined( 'LITESPEED_CLI' ) ) {
	! defined( 'LITESPEED_ALLOWED' ) && define( 'LITESPEED_ALLOWED', true );
}

// ESI const definition
if ( ! defined( 'LSWCP_ESI_SUPPORT' ) ) {
	define( 'LSWCP_ESI_SUPPORT', LITESPEED_SERVER_TYPE !== 'LITESPEED_SERVER_OLS' );
}

if ( ! defined( 'LSWCP_TAG_PREFIX' ) ) {
	define( 'LSWCP_TAG_PREFIX', substr( md5( LSCWP_DIR ), -3 ) );
}

if ( ! function_exists( 'litespeed_exception_handler' ) ) {
	/**
	 * Handle exception
	 *
	 * @param int    $errno   Error number.
	 * @param string $errstr  Error string.
	 * @param string $errfile Error file.
	 * @param int    $errline Error line.
	 * @throws \ErrorException When an error is encountered.
	 */
	function litespeed_exception_handler( $errno, $errstr, $errfile, $errline ) {
		throw new \ErrorException(
			esc_html( $errstr ),
			0,
			absint( $errno ),
			esc_html( $errfile ),
			absint( $errline )
		);
	}
}

if ( ! function_exists( 'litespeed_define_nonce_func' ) ) {
	/**
	 * Overwrite the WP nonce funcs outside of LiteSpeed namespace
	 *
	 * @since  3.0
	 */
	function litespeed_define_nonce_func() {
		/**
		 * If the nonce is in none_actions filter, convert it to ESI
		 *
		 * @param mixed $action Action name or -1.
		 * @return string
		 */
		function wp_create_nonce( $action = -1 ) {
			if ( ! defined( 'LITESPEED_DISABLE_ALL' ) || ! LITESPEED_DISABLE_ALL ) {
				$control = \LiteSpeed\ESI::cls()->is_nonce_action( $action );
				if ( null !== $control ) {
					$params = array(
						'action' => $action,
					);
					return \LiteSpeed\ESI::cls()->sub_esi_block( 'nonce', 'wp_create_nonce ' . $action, $params, $control, true, true, true );
				}
			}

			return wp_create_nonce_litespeed_esi( $action );
		}

		/**
		 * Ori WP wp_create_nonce
		 *
		 * @param mixed $action Action name or -1.
		 * @return string
		 */
		function wp_create_nonce_litespeed_esi( $action = -1 ) {
			$uid = get_current_user_id();
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

if ( ! function_exists( 'run_litespeed_cache' ) ) {
	/**
	 * Begins execution of the plugin.
	 *
	 * @since    1.0.0
	 */
	function run_litespeed_cache() {
		// Check minimum PHP requirements, which is 7.2 at the moment.
		if ( version_compare( PHP_VERSION, '7.2.0', '<' ) ) {
			return;
		}

		// Check minimum WP requirements, which is 5.3 at the moment.
		if ( version_compare( $GLOBALS['wp_version'], '5.3', '<' ) ) {
			return;
		}

		\LiteSpeed\Core::cls();
	}

	run_litespeed_cache();
}
