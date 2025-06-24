<?php
/**
 * LiteSpeed Cache Upgrade Notice
 *
 * Displays a notice informing the user that the LiteSpeed Cache plugin has been upgraded and a page refresh is needed to complete the configuration data upgrade.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$message = esc_html__( 'LiteSpeed cache plugin upgraded. Please refresh the page to complete the configuration data upgrade.', 'litespeed-cache' );

echo wp_kses_post( self::build_notice( self::NOTICE_BLUE, $message ) );
