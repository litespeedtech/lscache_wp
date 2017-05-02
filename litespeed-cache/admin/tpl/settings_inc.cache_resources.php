<?php
if (!defined('WPINC')) die;
?>
	<!-- build_setting_cache_resources -->
	<?php $file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(LiteSpeed_Cache_Admin_Rules::WRITABLE); ?>
	<tr>
		<th><?php echo __('Enable Cache for PHP Resources', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_CACHE_RES; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_enable" value="1" <?php if( $_options[$id] ) echo 'checked'; ?> <?php if( !$file_writable ) echo 'disabled'; ?> />
					<label for="conf_<?php echo $id; ?>_enable"><?php echo __('Enable', 'litespeed-cache'); ?></label>

					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_disable" value="0" <?php if( !$_options[$id]) echo 'checked'; ?> <?php if( !$file_writable ) echo 'disabled'; ?> />
					<label for="conf_<?php echo $id; ?>_disable"><?php echo __('Disable', 'litespeed-cache'); ?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __('Some themes and plugins add resources via a PHP request.', 'litespeed-cache'); ?>
				<?php echo __('Caching these pages may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>
