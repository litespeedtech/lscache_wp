<?php
if ( ! defined( 'WPINC' ) ) die ;


$err = sprintf( __( 'Unexpected cache rule %2$s found in %1$s file. This rule may cause visitors to see old versions of pages due to the browser caching html pages. If you are sure that html pages are not being browser cached, this message can be dismissed. (<a %3$s>Learn More</a>)', 'litespeed-cache' ),
		'.htaccess',
		'`ExpiresDefault`',
		'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:troubleshooting:browser_displays_stale_content" target="_blank"'
);

// other plugin left cache expired rules in .htaccess which will cause conflicts
echo self::build_notice( self::NOTICE_YELLOW . ' lscwp-notice-ruleconflict', $err ) ;

