<?php
if (!defined('WPINC')) die;

?>
	<?php $file_writable = LiteSpeed_Cache_Admin_Rules::writable(); ?>
	<tr>
		<th><?php echo __('Cache favicon.ico', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_CACHE_FAVICON; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_enable" value="1" <?php if( $_options[$id] ) echo 'checked'; ?> <?php if( !$file_writable) echo 'disabled'; ?> />
					<label for="conf_<?php echo $id; ?>_enable"><?php echo __('Enable', 'litespeed-cache'); ?></label>

					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_disable" value="0" <?php if( !$_options[$id] ) echo 'checked'; ?> <?php if( !$file_writable ) echo 'disabled'; ?> />
					<label for="conf_<?php echo $id; ?>_disable"><?php echo __('Disable', 'litespeed-cache'); ?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __('favicon.ico is requested on most pages.', 'litespeed-cache'); ?>
				<?php echo __('Caching this recource may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>
