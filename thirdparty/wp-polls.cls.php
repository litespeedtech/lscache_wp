<?php
/**
 * The Third Party integration with the WP-Polls plugin.
 *
 * @since		1.0.7
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

// todo: need test

class Wp_Polls
{
	public static function detect()
	{
		add_filter('wp_polls_display_pollvote', __CLASS__ . '::set_control');
		add_filter('wp_polls_display_pollresult', __CLASS__ . '::set_control');
	}

	public static function set_control()
	{
		do_action('litespeed_control_set_nocache', 'wp polls');
	}
}
