<?php
if ( ! defined( 'WPINC' ) ) die ;

$err = __( 'Disable All Features', 'litespeed-cache' ) ;

// other plugin left cache expired rules in .htaccess which will cause conflicts
echo LiteSpeed_Cache_Admin_Display::build_notice( LiteSpeed_Cache_Admin_Display::NOTICE_RED, $err ) ;

