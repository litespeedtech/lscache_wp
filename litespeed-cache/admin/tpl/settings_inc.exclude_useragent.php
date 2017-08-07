<?php
if (!defined('WPINC')) die;

?>

<!-- build_setting_exclude_useragent -->
<h3 class="litespeed-title"><?php echo __('User Agent List', 'litespeed-cache'); ?></h3>
<p><?php echo __('To prevent user agents from being cached, enter it in the text field below.', 'litespeed-cache'); ?></p>
<div class="litespeed-callout litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<i>
		<?php echo sprintf(__('SYNTAX: Separate each user agent with a bar, <font style="font-style:normal">%s</font>.', 'litespeed-cache'), "'|'"); ?>
		<?php echo sprintf(__('Spaces should have a backslash in front of them, %s.', 'litespeed-cache'), "'\'"); ?>
	</i>
</div>

<?php

$file_writable = LiteSpeed_Cache_Admin_Rules::writable();

$this->build_input(LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS, 'litespeed-input-long');//, !$file_writable
