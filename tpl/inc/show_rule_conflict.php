<?php
/**
 * LiteSpeed Cache Unexpected Cache Rule Notice
 *
 * Displays a warning notice about conflicting cache rules in .htaccess that may cause stale content.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined('WPINC') || exit();

$err = sprintf(
	esc_html__(
		'Unexpected cache rule %2$s found in %1$s file. This rule may cause visitors to see old versions of pages due to the browser caching HTML pages. If you are sure that HTML pages are not being browser cached, this message can be dismissed. (%3$sLearn More%4$s)',
		'litespeed-cache'
	),
	'.htaccess',
	'<code>ExpiresDefault</code>',
	'<a href="https://docs.litespeedtech.com/lscache/lscwp/troubleshoot/#browser-displays-stale-content" target="_blank">',
	'</a>'
);

// Other plugin left cache expired rules in .htaccess which will cause conflicts
echo wp_kses_post( self::build_notice(self::NOTICE_YELLOW . ' lscwp-notice-ruleconflict', $err) );
