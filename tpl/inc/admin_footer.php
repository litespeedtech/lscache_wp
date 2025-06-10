<?php
/**
 * LiteSpeed Cache Admin Footer
 *
 * Customizes the admin footer text for LiteSpeed Cache with links to rate, documentation, support forum, and community.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$stars = '<span class="wporg-ratings rating-stars"><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span></span>';

$rate_us = '<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" rel="noopener noreferrer" target="_blank">' . sprintf( esc_html__( 'Rate %1$s on %2$s', 'litespeed-cache' ), '<strong>' . esc_html__( 'LiteSpeed Cache', 'litespeed-cache' ) . $stars . '</strong>', 'WordPress.org' ) . '</a>';

$wiki = '<a href="https://docs.litespeedtech.com/lscache/lscwp/" target="_blank">' . esc_html__( 'Read LiteSpeed Documentation', 'litespeed-cache' ) . '</a>';

$forum = '<a href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank">' . esc_html__( 'Visit LSCWP support forum', 'litespeed-cache' ) . '</a>';

$community = '<a href="https://litespeedtech.com/slack" target="_blank">' . esc_html__( 'Join LiteSpeed Slack community', 'litespeed-cache' ) . '</a>';

// Change the footer text
if ( ! is_multisite() || is_network_admin() ) {
	$footer_text = $rate_us . ' | ' . $wiki . ' | ' . $forum . ' | ' . $community;
} else {
	$footer_text = $wiki . ' | ' . $forum . ' | ' . $community;
}
