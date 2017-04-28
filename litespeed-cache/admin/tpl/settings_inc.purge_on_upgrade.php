<?php
if (!defined('WPINC')) die;

?>
	<!-- build_setting_purge_on_upgrade -->
	<tr>
		<th><?=__('Purge All on upgrade', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_enable" value="1" <?=$_options[$id]?'checked':''?> />
					<label for="conf_<?=$id?>_enable"><?=__('Enable', 'litespeed-cache')?></label>

					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_disable" value="0" <?=$_options[$id]?'':'checked'?> />
					<label for="conf_<?=$id?>_disable"><?=__('Disable', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('When enabled, the cache will automatically purge when any plugins, themes, or WordPress core is upgraded.', 'litespeed-cache')?>
			</div>
		</td>
	</tr>
