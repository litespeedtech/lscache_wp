<?php
if (!defined('WPINC')) die;

?>

<!-- build_setting_exclude_useragent -->
<?php $file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(LiteSpeed_Cache_Admin_Rules::WRITABLE); ?>
<h3 class="litespeed-title"><?=__('User Agent List', 'litespeed-cache')?></h3>
<p><?=__('To prevent user agents from being cached, enter it in the text field below.', 'litespeed-cache')?></p>
<div class="litespeed-callout litespeed-callout-warning">
	<h4><?=__('NOTE:', 'litespeed-cache')?></h4>
	<ol>
		<li><?=__('This setting will edit the .htaccess file.', 'litespeed-cache')?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<i>
		<?=sprintf(__('SYNTAX: Separate each user agent with a bar, <font style="font-style:normal">%s</font>.', 'litespeed-cache'), "'|'")?>
		<?=sprintf(__('Spaces should have a backslash in front of them, %s.', 'litespeed-cache'), "'\'")?>
	</i>
</div>
<?php $id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS; ?>
<input type="text" class="regular-text litespeed-input-long" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" value="<?=esc_textarea($_options[$id])?>" <?=$file_writable?'':'disabled'?> />
