<?php
if (!defined('WPINC')) die;


$rate_us = sprintf(__('Rate <strong>LiteSpeed Cache</strong> with %s on WordPress.org if you like us!', 'litespeed-cache'),
		'<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" rel="noopener noreferer" target="_blank">&#10030;&#10030;&#10030;&#10030;&#10030;</a>'
);
$questions = sprintf(__('If there are any questions that are not answered in the <a %s>FAQs</a>, do not hesitate to ask them on the <a %s>support forum</a>.', 'litespeed-cache'),
			'href="' . get_admin_url() . 'admin.php?page=lscache-info"',
			'href="https://wordpress.org/support/plugin/litespeed-cache" rel="noopener noreferrer" target="_blank"');
// Change the footer text
if ( !is_multisite()
	|| is_network_admin())
{
	$footer_text = $rate_us . ' ' . $questions;
}
else{
	$footer_text = $questions;
}