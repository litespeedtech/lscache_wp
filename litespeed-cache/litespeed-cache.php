<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.litespeedtech.com
 * @since             1.0.0
 * @package           LiteSpeed_Cache
 *
 * @wordpress-plugin
 * Plugin Name:       LiteSpeed Cache
 * Plugin URI:        https://www.litespeedtech.com/products/cache-plugins/wordpress-acceleration
 * Description:       WordPress plugin to connect to LSCache on LiteSpeed Web Server.
 * Version:           2.1.2
 * Author:            LiteSpeed Technologies
 * Author URI:        https://www.litespeedtech.com
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl.html
 * Text Domain:       litespeed-cache
 * Domain Path:       /languages
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
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die ;
}

if ( class_exists( 'LiteSpeed_Cache' ) || defined( 'LSCWP_DIR' ) ) {
	return ;
}

! defined( 'LSCWP_CONTENT_DIR' ) && define( 'LSCWP_CONTENT_DIR', WP_CONTENT_DIR ) ;
! defined( 'LSCWP_DIR' ) && define( 'LSCWP_DIR', dirname( __FILE__ ) . '/' ) ;// Full absolute path '/usr/local/lsws/***/wp-content/plugins/litespeed-cache/' or MU
! defined( 'LSCWP_BASENAME' ) && define( 'LSCWP_BASENAME', 'litespeed-cache/litespeed-cache.php' ) ;//LSCWP_BASENAME='litespeed-cache/litespeed-cache.php'

! defined( 'LITESPEED_TIME_OFFSET' ) && define( 'LITESPEED_TIME_OFFSET', get_option( 'gmt_offset' ) * 60 * 60 ) ;

// Placeholder for lazyload img
! defined( 'LITESPEED_PLACEHOLDER' ) && define( 'LITESPEED_PLACEHOLDER', 'data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=' ) ;

// Auto register LiteSpeed classes
require_once LSCWP_DIR . 'inc/litespeed.autoload.php' ;

// Define CLI
if ( ( defined( 'WP_CLI' ) && WP_CLI ) || PHP_SAPI == 'cli' ) {
	! defined( 'LITESPEED_CLI' ) &&  define( 'LITESPEED_CLI', true ) ;

	// Register CLI cmd
	if ( method_exists( 'WP_CLI', 'add_command' ) ) {
		WP_CLI::add_command( 'lscache-admin', 'LiteSpeed_Cache_Cli_Admin' ) ;
		WP_CLI::add_command( 'lscache-purge', 'LiteSpeed_Cache_Cli_Purge' ) ;
	}
}

// Server type
if ( ! defined( 'LITESPEED_SERVER_TYPE' ) ) {
	if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ADC' ) ;
	}
	elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0 ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_OLS' ) ;
	}
	elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ENT' ) ;
	}
	else {
		define( 'LITESPEED_SERVER_TYPE', 'NONE' ) ;
	}
}

// Checks if caching is allowed via server variable
if ( ! empty ( $_SERVER['X-LSCACHE'] ) ||  LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_ADC' || defined( 'LITESPEED_CLI' ) ) {
	! defined( 'LITESPEED_ALLOWED' ) &&  define( 'LITESPEED_ALLOWED', true ) ;
}

// ESI const defination
if ( ! defined( 'LSWCP_ESI_SUPPORT' ) ) {
	define( 'LSWCP_ESI_SUPPORT', LITESPEED_SERVER_TYPE !== 'LITESPEED_SERVER_OLS' ? true : false ) ;
}

if ( ! defined( 'LSWCP_TAG_PREFIX' ) ) {
	define( 'LSWCP_TAG_PREFIX', substr( md5( LSCWP_DIR ), -3 ) ) ;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if ( ! function_exists( 'run_litespeed_cache' ) ) {
	function run_litespeed_cache()
	{
		$version_supported = true ;

		//Check minimum PHP requirements, which is 5.3 at the moment.
		if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
			error_log( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_PHP_VER ) ) ;
			$version_supported = false ;
		}

		//Check minimum WP requirements, which is 4.0 at the moment.
		if ( version_compare( $GLOBALS['wp_version'], '4.0', '<' ) ) {
			error_log( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_WP_VER ) ) ;
			$version_supported = false ;
		}

		if ( $version_supported ) {
			LiteSpeed_Cache::get_instance() ;
		}
	}

	run_litespeed_cache() ;
}

/**
 * Easier API for Purging a single post.
 *
 * If a third party plugin needs to purge a single post, it can send
 * a purge tag using this function.
 *
 * @since 1.0.1
 * @access public
 * @param integer $id The post id to purge.
 */
if ( ! function_exists( 'litespeed_purge_single_post' ) ) {
	function litespeed_purge_single_post( $id )
	{
		LiteSpeed_Cache_Purge::purge_post( $id ) ;
	}
}

/**
 * Handle exception
 */
if ( ! function_exists( 'litespeed_exception_handler' ) ) {
	function litespeed_exception_handler( $errno, $errstr, $errfile, $errline )
	{
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline) ;
	}
}

