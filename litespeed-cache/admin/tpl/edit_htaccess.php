<?php
if (!defined('WPINC')) die;

$readonly = LiteSpeed_Cache_Admin_Rules::get_instance()->is_file_able(LiteSpeed_Cache_Admin_Rules::WRITABLE) ? '' : 'readonly';

?>

<div class="wrap">
	<h2><?=__('LiteSpeed Cache Edit .htaccess', 'litespeed-cache')?></h2>
</div>
<div class="wrap">
	<div class="litespeed-cache-welcome-panel">
		<div class="litespeed-callout litespeed-callout-danger">
			<p><span class="attention"><?=__('WARNING: This page is meant for advanced users.', 'litespeed-cache')?></span></p>
			<?=__('Any changes made to the .htaccess file may break the site.', 'litespeed-cache')?>
			<?=__('Please consult the host/server admin before making any changes.', 'litespeed-cache')?>
		</div>

		<?php if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT): ?>
		<h3><?=__('File editing is disabled in configuration.', 'litespeed-cache')?></h3>

		<?php elseif(LiteSpeed_Cache_Admin_Rules::file_get($contents) === false): ?>
		<h3><?=$contents?></h3>

		<?php else: ?>

		<?php require LSWCP_DIR . 'admin/tpl/info_common_rewrite.php'; ?>

		<form method="post" action="admin.php?page=<?=LiteSpeed_Cache::PAGE_EDIT_HTACCESS?>">
			<?php $this->form_action(LiteSpeed_Cache::ACTION_SAVE_HTACCESS); ?>

			<h3><?=sprintf(__('Current %s contents:', 'litespeed-cache'), '.htaccess')?></h3>

			<p><span class="attention"><?=sprintf(__('DO NOT EDIT ANYTHING WITHIN %s', 'litespeed-cache'), '###LSCACHE START/END XXXXXX###')?></span></p>

			<p><?=__('These are added by the LS Cache plugin and may cause problems if they are changed.', 'litespeed-cache')?></p>

			<textarea id="wpwrap" name="<?=LiteSpeed_Cache_Admin_Rules::EDITOR_TEXTAREA_NAME?>" wrap="off" rows="20" class="code" <?=$readonly?> ><?=esc_textarea($contents)?></textarea>

			<button type="submit" class="litespeed-btn litespeed-btn-success"><?=__('Save', 'litespeed-cache')?></button>
		</form>

		<?php endif; ?>
	</div>
</div>
