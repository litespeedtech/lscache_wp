<?php
namespace LiteSpeed;
defined('WPINC') || exit();

$err =
	__('NOTICE: Database login cookie did not match your login cookie.', 'litespeed-cache') .
	' ' .
	__('If the login cookie was recently changed in the settings, please log out and back in.', 'litespeed-cache') .
	' ' .
	sprintf(__('If not, please verify the setting in the <a href="%1$s">Advanced tab</a>.', 'litespeed-cache'), admin_url('admin.php?page=litespeed-cache#advanced'));

if (LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS') {
	$err .= ' ' . __('If using OpenLiteSpeed, the server must be restarted once for the changes to take effect.', 'litespeed-cache');
}

self::add_notice(self::NOTICE_YELLOW, $err);
