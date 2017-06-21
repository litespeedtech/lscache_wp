<?php
if (!defined('WPINC')) die;

		$screen = get_current_screen();
		$screen->add_help_tab(array(
			'id'      => 'lsc-overview',
			'title'   => __('Overview', 'litespeed-cache'),
			'content' => '<p>'
				. __('LiteSpeed Cache is a page cache built into LiteSpeed Web Server.', 'litespeed-cache') . ' '
				. __('This plugin communicates with LiteSpeed Web Server to let it know which pages are cacheable and when to purge them.', 'litespeed-cache')
				. '</p><p>' . __('A LiteSpeed server (OLS, LSWS, WebADC) and its LSCache module must be installed and enabled.', 'litespeed-cache')
				. '</p>',
		));

//		$screen->add_help_tab(array(
//			'id'      => 'lst-purgerules',
//			'title'   => __('Auto Purge Rules', 'litespeed-cache'),
//			'content' => '<p>' . __('You can set what pages will be purged when a post is published or updated.', 'litespeed-cache') . '</p>',
//		));

		$screen->set_help_sidebar(
			'<p><strong>' . __('For more information:', 'litespeed-cache') . '</strong></p>' .
//				'<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache" rel="noopener noreferrer" target="_blank">' . __('LSCache Documentation', 'litespeed-cache') . '</a></p>' .
			'<p><a href="https://wordpress.org/support/plugin/litespeed-cache" rel="noopener noreferrer" target="_blank">' . __('Support Forum', 'litespeed-cache') . '</a></p>'
		);

