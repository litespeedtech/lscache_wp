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
 * Plugin URI:        https://www.litespeedtech.com/products/litespeed-web-cache/lscwp
 * Description:       WordPress plugin to connect to LSCache on LiteSpeed Web Server.
 * Version:           1.0.13.1
 * Author:            LiteSpeed Technologies
 * Author URI:        https://www.litespeedtech.com
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl.html
 * Text Domain:       litespeed-cache
 * Domain Path:       /languages
 *
 * Copyright (C) 2015 LiteSpeed Technologies, Inc.
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
if ( ! defined('WPINC') ) {
	die ;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-litespeed-cache.php' ;

if (!function_exists('is_openlitespeed')) {
	function is_openlitespeed()
	{
		return ((isset($_SERVER['LSWS_EDITION']))
				&& (strncmp($_SERVER['LSWS_EDITION'], 'Openlitespeed', 13) == 0));
	}
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
if (!function_exists('run_litespeed_cache')) {
	function run_litespeed_cache()
	{
		$version_supported = true ;

		//Check minimum PHP requirements, which is 5.3 at the moment.
		if ( version_compare(PHP_VERSION, '5.3.0', '<') ) {
			add_action('admin_notices', 'LiteSpeed_Cache::show_version_error_php') ;
			$version_supported = false ;
		}

		//Check minimum WP requirements, which is 4.0 at the moment.
		if ( version_compare($GLOBALS['wp_version'], '4.0', '<') ) {
			add_action('admin_notices', 'LiteSpeed_Cache::show_version_error_wp') ;
			$version_supported = false ;
		}

		if ( $version_supported ) {
			LiteSpeed_Cache::run() ;
		}
		else {
			return false ;
		}
		return true;
	}

	run_litespeed_cache() ;
}
