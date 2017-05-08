<!-- build_setting_exclude_cookies -->
<h3 class="litespeed-title"><?php echo __('Cookie List', 'litespeed-cache'); ?></h3>
<p><?php echo __('To prevent cookies from being cached, enter it in the text area below.', 'litespeed-cache'); ?></p>
<div class="litespeed-callout litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<i>
		<?php echo __('SYNTAX: Cookies should be listed one per line.', 'litespeed-cache'); ?>
		<?php echo sprintf(__('Spaces should have a backslash in front of them, %s', 'litespeed-cache'), "'\ '."); ?>
	</i>
</div>
<?php $id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES; ?>
<?php $file_writable = LiteSpeed_Cache_Admin_Rules::writable(); ?>
<textarea name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']'; ?>" rows="5" cols="80" <?php if( !$file_writable ) echo 'disabled'; ?>><?php echo esc_textarea(str_replace('|', "\n", $_options[$id])); ?></textarea>

