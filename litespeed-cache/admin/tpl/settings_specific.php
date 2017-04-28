<?php
if (!defined('WPINC')) die;
?>
<h3 class="litespeed-title"><?=__('Specific Pages', 'litespeed-cache')?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?=__('Enable Cache for Login Page', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_CACHE_LOGIN; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_enable" value="1" <?=$_options[$id]?'checked':''?> />
					<label for="conf_<?=$id?>_enable"><?=__('Enable', 'litespeed-cache')?></label>

					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_disable" value="0" <?=$_options[$id]?'':'checked'?> />
					<label for="conf_<?=$id?>_disable"><?=__('Disable', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('Disabling this option may negatively affect performance.', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<?php if (!is_multisite()): ?>
		<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_favicon.php'; ?>
		<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_resources.php'; ?>

	<?php endif; ?>
</tbody></table>

