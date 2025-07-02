<?php
/**
 * LiteSpeed Cache Installation Notice
 *
 * Displays a notice informing users that the LiteSpeed Cache plugin was installed by the server admin.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$buf  = sprintf(
	'<h3>%s</h3>
	<p>%s</p>
	<p>%s</p>
	<p>%s</p>
	<p>%s</p>
	<p>%s</p>
	<ul>
		<li>%s</li>
		<li>%s</li>
	</ul>',
	esc_html__( 'LiteSpeed Cache plugin is installed!', 'litespeed-cache' ),
	esc_html__( 'This message indicates that the plugin was installed by the server admin.', 'litespeed-cache' ),
	esc_html__( 'The LiteSpeed Cache plugin is used to cache pages - a simple way to improve the performance of the site.', 'litespeed-cache' ),
	esc_html__( 'However, there is no way of knowing all the possible customizations that were implemented.', 'litespeed-cache' ),
	esc_html__( 'For that reason, please test the site to make sure everything still functions properly.', 'litespeed-cache' ),
	esc_html__( 'Examples of test cases include:', 'litespeed-cache' ),
	esc_html__( 'Visit the site while logged out.', 'litespeed-cache' ),
	esc_html__( 'Create a post, make sure the front page is accurate.', 'litespeed-cache' )
);
$buf .= sprintf(
	/* translators: %s: Link tags */
	esc_html__( 'If there are any questions, the team is always happy to answer any questions on the %ssupport forum%s.', 'litespeed-cache' ),
	'<a href="https://wordpress.org/support/plugin/litespeed-cache" rel="noopener noreferrer" target="_blank">',
	'</a>'
);
$buf .= '<p>' . esc_html__( 'If you would rather not move at litespeed, you can deactivate this plugin.', 'litespeed-cache' ) . '</p>';

self::add_notice( self::NOTICE_BLUE . ' lscwp-whm-notice', $buf );
