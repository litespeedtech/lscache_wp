<?php
/**
 * LiteSpeed Cache Disable All Features Notice
 *
 * Displays a warning notice about conflicting .htaccess rules from other plugins that may interfere with LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$error_message = esc_html__( 'Disable All Features', 'litespeed-cache' );

// Other plugin left cache expired rules in .htaccess which will cause conflicts
echo wp_kses_post( Admin_Display::build_notice( Admin_Display::NOTICE_RED, $error_message ) );
