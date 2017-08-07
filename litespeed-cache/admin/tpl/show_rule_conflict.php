<?php
if ( ! defined( 'WPINC' ) ) die ;


$err = sprintf( __( 'In %s file found one unexpected cache rule %s left by other cache plugins. Please remove it to get LiteSpeed Cache Plugin work well. If you are sure it is left by purpose, please dismiss this message. ', 'litespeed-cache' ), '.htaccess', '`ExpiresDefault`' );

// other plugin left cache expired rules in .htaccess which will cause conflicts
self::add_notice(self::NOTICE_YELLOW . ' lscwp-notice-ruleconflict', $err);

