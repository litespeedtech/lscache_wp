<?php
if ( ! defined( 'WPINC' ) ) die ;


$err = sprintf( __( 'Unexpected cache rule %2$s found in %1$s file. This rule may cause visitors to see old versions of pages due to the browser caching html pages. If you are sure that html pages are not being browser cached, this message can be dismissed.', 'litespeed-cache' ), '.htaccess', '`ExpiresDefault`' );

// other plugin left cache expired rules in .htaccess which will cause conflicts
self::add_notice(self::NOTICE_YELLOW . ' lscwp-notice-ruleconflict', $err);

