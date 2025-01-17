<?php
namespace LiteSpeed;
defined('WPINC') || exit();

// &#10030;&#10030;&#10030;&#10030;&#10030;
$stars =
	'<span class="wporg-ratings rating-stars"><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span><span class="dashicons dashicons-star-filled" style="color:#ffb900 !important;"></span></span>';
$rate_us =
	'<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" rel="noopener noreferer" target="_blank">' .
	sprintf(__('Rate %s on %s', 'litespeed-cache'), '<strong>' . __('LiteSpeed Cache', 'litespeed-cache') . $stars . '</strong>', 'WordPress.org') .
	'</a>';

$wiki = '<a href="https://docs.litespeedtech.com/lscache/lscwp/" target="_blank">' . __('Read LiteSpeed Documentation', 'litespeed-cache') . '</a>';

$forum = '<a href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank">' . __('Visit LSCWP support forum', 'litespeed-cache') . '</a>';

$community = '<a href="https://litespeedtech.com/slack" target="_blank">' . __('Join LiteSpeed Slack community', 'litespeed-cache') . '</a>';

// Change the footer text
if (!is_multisite() || is_network_admin()) {
	$footer_text = $rate_us . ' | ' . $wiki . ' | ' . $forum . ' | ' . $community;
} else {
	$footer_text = $wiki . ' | ' . $forum . ' | ' . $community;
}
