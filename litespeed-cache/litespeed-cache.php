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
 * Version:           1.1.6
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

if ( class_exists( 'LiteSpeed_Cache' ) || defined( 'LSWCP_DIR' ) ) {
	return ;
}

define( 'LSWCP_CONTENT_DIR', dirname( get_theme_root() ) ) ;
define( 'LSWCP_DIR', plugin_dir_path( __FILE__ ) ) ;// Full absolute path '/usr/local/lsws/***/wp-content/plugins/litespeed-cache/'
define( 'LSWCP_BASENAME', plugin_basename( LSWCP_DIR . 'litespeed-cache.php' ) ) ;//LSWCP_BASENAME='litespeed-cache/litespeed-cache.php'

// Auto register LiteSpeed classes
require_once LSWCP_DIR . 'includes/litespeed.autoload.php' ;

if ( LiteSpeed_Cache_Router::is_cli() ) {
	WP_CLI::add_command( 'lscache-admin', 'LiteSpeed_Cache_Cli_Admin' ) ;
	WP_CLI::add_command( 'lscache-purge', 'LiteSpeed_Cache_Cli_Purge' ) ;
}

if ( ! defined( 'LITESPEED_SERVER_TYPE' ) ) {
	if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ADC' ) ;
	}
	elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strncmp( $_SERVER['LSWS_EDITION'], 'Openlitespeed', 13 ) == 0 ) {
			define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_OLS' ) ;
	}
	elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
		define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ENT' ) ;
	}
	else {
		define( 'LITESPEED_SERVER_TYPE', 'NONE' ) ;
	}
}

// ESI const defination
if ( ! defined( 'LSWCP_ESI_SUPPORT' ) ) {
	define( 'LSWCP_ESI_SUPPORT', LITESPEED_SERVER_TYPE !== 'LITESPEED_SERVER_OLS' ? false : false ) ;
}

if ( ! defined( 'LSWCP_TAG_PREFIX' ) ) {
	define( 'LSWCP_TAG_PREFIX', substr( md5( LSWCP_DIR ), -3 ) ) ;
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
		$post_id = intval( $id ) ;
		if ( ! in_array( get_post_status( $post_id ), array( 'publish', 'trash' ) ) ) {
			return ;
		}
		LiteSpeed_Cache_Purge::add( LiteSpeed_Cache_Tag::TYPE_POST . $post_id ) ;
	}
}
