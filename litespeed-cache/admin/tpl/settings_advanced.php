<?php
if (!defined('WPINC')) die;

?>
<div class="litespeed-callout litespeed-callout-danger">
	<h4><?php echo __('NOTICE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache'); ?></li>
		<li><?php echo __('Please take great care when changing any of these settings.', 'litespeed-cache'); ?></li>
		<li><?php echo __('If there are any questions, do not hesitate to submit a support thread.', 'litespeed-cache'); ?></li>
	</ol>
</div>

<h3 class="litespeed-title"><?php echo __('Check Advanced Cache', 'litespeed-cache'); ?></h3>
<?php
	$id = LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE;
	$this->build_checkbox($id, __('Include advanced-cache.php', 'litespeed-cache'), $_options[$id]);
?>
<div class="litespeed-row litespeed-top10">
	<?php echo __('The advanced-cache.php file is used by many caching plugins to signal that a cache is active.', 'litespeed-cache'); ?>
	<?php echo __('When this option is checked and this file is detected as belonging to another plugin, LiteSpeed Cache will not cache.', 'litespeed-cache'); ?>
</div>
<div class="litespeed-row litespeed-top10">
	<i><?php echo __('Uncheck this option only if the other plugin is used for non-caching purposes, such as minifying css/js files.', 'litespeed-cache'); ?></i>
</div>

<h3 class="litespeed-title"><?php echo __('Login Cookie', 'litespeed-cache'); ?></h3>
<?php

echo __('SYNTAX: alphanumeric and "_".', 'litespeed-cache')
	. ' ' . __('No spaces and case sensitive.', 'litespeed-cache')
	. ' ' . __('MUST BE UNIQUE FROM OTHER WEB APPLICATIONS.', 'litespeed-cache')
	. '<p>'
		. sprintf(__('The default login cookie is %s.', 'litespeed-cache'), '_lscache_vary')
		. ' ' . __('The server will determine if the user is logged in based on the existance of this cookie.', 'litespeed-cache')
		. ' ' . __('This setting is useful for those that have multiple web applications for the same domain.', 'litespeed-cache')
		. ' ' . __('If every web application uses the same cookie, the server may confuse whether a user is logged in or not.', 'litespeed-cache')
		. ' ' . __('The cookie set here will be used for this WordPress installation.', 'litespeed-cache')
	. '</p>'
	. '<p>'
		. __('Example use case:', 'litespeed-cache')
		. '<br />'
		. sprintf(__('There is a WordPress installed for %s.', 'litespeed-cache'), '<u>www.example.com</u>')
		. '<br />'
		. sprintf(__('Then another WordPress is installed (NOT MULTISITE) at %s', 'litespeed-cache'), '<u>www.example.com/blog/</u>')
		. ' ' . __('The cache needs to distinguish who is logged into which WordPress site in order to cache correctly.', 'litespeed-cache')
	. '</p>';

$cookie_rule = LiteSpeed_Cache_Admin_Rules::get_instance()->get_rewrite_rule_login_cookie();
if ( $cookie_rule && substr($cookie_rule, 0, 11) !== 'Cache-Vary:' ){
	echo '<p class="attention">'
			. sprintf(__('Error: invalid login cookie. Please check the %s file', 'litespeed-cache'), '.htaccess')
		. '</p>';
}

$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE;
if ( $_options[LiteSpeed_Cache_Config::OPID_ENABLED] && $_options[$id] ){

	if (!$cookie_rule){
		echo '<p class="attention">'
				. sprintf(__('Error getting current rules from %s: %s', 'litespeed-cache'), '.htaccess', LiteSpeed_Cache_Admin_Rules::MARKER_LOGIN_COOKIE)
			. '</p>';
	}
	else{
		$cookie_rule = substr($cookie_rule, 11);
		$cookie_arr = explode(',', $cookie_rule);
		if(!in_array($_options[$id], $cookie_arr)) {
			echo '<div class="litespeed-callout litespeed-callout-warning">'.
					__('WARNING: The .htaccess login cookie and Database login cookie do not match.', 'litespeed-cache').
				'</div>';
		}
	}

}

$file_writable = LiteSpeed_Cache_Admin_Rules::writable();
$this->build_input($id, 'litespeed-input-long');// , !$file_writable


