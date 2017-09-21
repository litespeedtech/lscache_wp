<?php
if (!defined('WPINC')) die;

?>

<!-- build_setting_exclude_useragent -->
<h3 class="litespeed-title"><?php echo __('User Agent List', 'litespeed-cache'); ?></h3>
<p><?php echo sprintf( __( 'To prevent %s from being cached, enter it below.', 'litespeed-cache' ), __( 'user agents', 'litespeed-cache') ) ; ?></p>
<div class="litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<p><?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?></p>
</div>
<div class="litespeed-desc">
	<i>
		<?php echo sprintf( __( 'SYNTAX: Separate each user agent with a bar, %s.', 'litespeed-cache' ), '<code>|</code>' ) ; ?>
		<?php echo sprintf( __( 'Spaces should have a backslash in front of them, %s.', 'litespeed-cache' ), '<code>\</code>' ) ; ?>
	</i>
</div>

<?php

$file_writable = LiteSpeed_Cache_Admin_Rules::writable();

$this->build_input(LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS, 'litespeed-input-long');//, !$file_writable
