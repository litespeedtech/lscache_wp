<?php
if (!defined('WPINC')) die;

?>
	<!-- build_setting_purge_on_upgrade -->
	<tr>
		<th><?php echo __('Purge All on upgrade', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_enable" value="1" <?php echo $_options[$id]?'checked':''; ?> />
					<label for="conf_<?php echo $id; ?>_enable"><?php echo __('Enable', 'litespeed-cache'); ?></label>

					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_disable" value="0" <?php echo $_options[$id]?'':'checked'; ?> />
					<label for="conf_<?php echo $id; ?>_disable"><?php echo __('Disable', 'litespeed-cache'); ?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __('When enabled, the cache will automatically purge when any plugins, themes, or WordPress core is upgraded.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>
