<?php
/**
 * LiteSpeed Cache Database Login Cookie Notice
 *
 * Displays a notice about mismatched login cookies for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined('WPINC') || exit();

$err =
	esc_html__('NOTICE: Database login cookie did not match your login cookie.', 'litespeed-cache') .
	' ' .
	esc_html__('If the login cookie was recently changed in the settings, please log out and back in.', 'litespeed-cache') .
	' ' .
	sprintf(
		esc_html__('If not, please verify the setting in the %sAdvanced tab%s.', 'litespeed-cache'),
		"<a href='" . esc_url(admin_url('admin.php?page=litespeed-cache#advanced')) . '">',
		'</a>'
	);

if (LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS') {
	$err .= ' ' . esc_html__('If using OpenLiteSpeed, the server must be restarted once for the changes to take effect.', 'litespeed-cache');
}

self::add_notice(self::NOTICE_YELLOW, $err);
