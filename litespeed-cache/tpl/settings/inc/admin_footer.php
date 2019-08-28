<?php
if (!defined('WPINC')) die;

// &#10030;&#10030;&#10030;&#10030;&#10030;
$rate_us = '<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" rel="noopener noreferer" target="_blank">'
				. sprintf( __( 'Rate %s on %s', 'litespeed-cache' ), '<strong>' . __( 'LiteSpeed Cache', 'litespeed-cache' ) . '</strong>', 'WordPress.org' )
			. '</a>' ;

$wiki = '<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp" target="_blank">' . __( 'Read LiteSpeed Wiki', 'litespeed-cache' ) . '</a>' ;

$forum = '<a href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank">' . __( 'Visit LSCWP support forum', 'litespeed-cache' ) . '</a>' ;

$community = '<a href="https://goo.gl/FG9S4N" target="_blank">' . __( 'Join LiteSpeed Slack community', 'litespeed-cache' ) . '</a>' ;

// Change the footer text
if ( ! is_multisite() || is_network_admin() ) {
	$footer_text = $rate_us . ' | ' . $wiki . ' | ' . $forum . ' | ' . $community ;
}
else {
	$footer_text = $wiki . ' | ' . $forum . ' | ' . $community ;
}