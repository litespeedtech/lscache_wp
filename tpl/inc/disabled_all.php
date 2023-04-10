<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$err = __( 'Disable All Features', 'litespeed-cache' ) ;

// other plugin left cache expired rules in .htaccess which will cause conflicts
echo Admin_Display::build_notice( Admin_Display::NOTICE_RED, $err ) ;

