<!-- build_setting_exclude_cookies -->
<h3 class="litespeed-title"><?php echo __('Cookie List', 'litespeed-cache'); ?></h3>
<p><?php echo sprintf( __( 'To prevent %s from being cached, enter it below.', 'litespeed-cache' ), __( 'cookies', 'litespeed-cache') ) ; ?></p>
<div class="litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<p><?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?></p>
</div>
<div class="litespeed-desc">
	<i>
		<?php echo sprintf(__('Spaces should have a backslash in front of them, %s.', 'litespeed-cache'), '<code>\ </code>'); ?>
		<?php echo __('One per line.', 'litespeed-cache'); ?>
	</i>
</div>

<?php

$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;

$file_writable = LiteSpeed_Cache_Admin_Rules::writable();

$this->build_textarea($id, str_replace('|', "\n", $_options[$id]));//, !$file_writable