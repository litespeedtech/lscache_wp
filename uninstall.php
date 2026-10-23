<?php
/**
 * Uninstall entry point, included by WordPress when the plugin is deleted.
 *
 * Replaces register_uninstall_hook(), which read the non-autoloaded `uninstall_plugins`
 * option on every request. The main file is included first, as WordPress did for the
 * hook, so the cleanup finds its classes and constants.
 *
 * @see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 * @package LiteSpeed
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit();

require_once __DIR__ . '/litespeed-cache.php';

\LiteSpeed\Activation::uninstall_litespeed_cache();
