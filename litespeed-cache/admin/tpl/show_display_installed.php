<?php
if (!defined('WPINC')) die;


$buf = '<h3>'. __('LiteSpeed Cache plugin is installed!', 'litespeed-cache'). '</h3>' . ' '
	. __('This message indicates that the plugin was installed by the server admin.', 'litespeed-cache') . ' '
	. __('The LiteSpeed Cache plugin is used to cache pages - a simple way to improve the performance of the site.', 'litespeed-cache') . ' '
	. __('However, there is no way of knowing all the possible customizations that were implemented.', 'litespeed-cache') . ' '
	. __('For that reason, please test the site to make sure everything still functions properly.', 'litespeed-cache')
	. '<br /><br />'
	. __('Examples of test cases include:', 'litespeed-cache')
	. '<ul>'
		. '<li>' . __('Visit the site while logged out.', 'litespeed-cache') . '</li>'
		. '<li>' . __('Create a post, make sure the front page is accurate.', 'litespeed-cache') . '</li>'
	. '</ul>'
	. sprintf(__('If there are any questions, the team is always happy to answer any questions on the <a %s>support forum</a>.', 'litespeed-cache'),
		'href="https://wordpress.org/support/plugin/litespeed-cache" rel="noopener noreferrer" target="_blank"')
	. '<br />'
	. __('If you would rather not move at litespeed, you can deactivate this plugin.', 'litespeed-cache');

self::add_notice(self::NOTICE_BLUE . ' lscwp-whm-notice', $buf);
