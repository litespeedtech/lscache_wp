<?php
if (!defined('WPINC')) die;

?>

	<!-- build_setting_mobile_view start -->
	<?php $file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(LiteSpeed_Cache_Admin_Rules::WRITABLE); ?>
	<tr>
		<th><?php echo __('Enable Separate Mobile View', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_enable" value="1" <?php echo $_options[$id]?'checked':''; ?> <?php echo $file_writable?'':'disabled'; ?> />
					<label for="conf_<?php echo $id; ?>_enable"><?php echo __('Enable', 'litespeed-cache'); ?></label>

					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_disable" value="0" <?php echo $_options[$id]?'':'checked'; ?> <?php echo $file_writable?'':'disabled'; ?> data-litespeed-cfm="<?php echo esc_html(__('WARNING: Disabling this option will clear the Mobile View List.', 'litespeed-cache')); ?> <?php echo esc_html(__('Press OK to confirm this action.', 'litespeed-cache')); ?>" />
					<label for="conf_<?php echo $id; ?>_disable"><?php echo __('Disable', 'litespeed-cache'); ?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __('When enabled, mobile views will be cached separately.', 'litespeed-cache'); ?>
				<?php echo __('A site built with responsive design does not need to check this.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('List of Mobile View User Agents', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST; ?>
			<?php
				$wp_default_mobile = 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi';
				clearstatcache();
				$enabled = $_options[$id];
				$mv_str = '';
				$cache_enable_id = is_network_admin() ? LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED : LiteSpeed_Cache_Config::OPID_ENABLED;
				if ($_options[$cache_enable_id] && $enabled) {
					$ret = LiteSpeed_Cache_Admin_Rules::get_instance()->get_common_rule('MOBILE VIEW', 'HTTP_USER_AGENT', $mv_str);
				}elseif ($enabled) {
					$ret = true;
					$mv_str = $enabled;
				}elseif ($enabled == false) {
					$ret = true;
					$mv_str = '';
				}else {
					$ret = false;
					$mv_str = sprintf(__('Expected false, got %s', 'litespeed-cache'), $mv_str);
				}
			?>
			<?php if ($ret !== true): ?>
				<p class="attention"><?php echo sprintf(__('Error getting current rules: %s', 'litespeed-cache'), $mv_str); ?></p>
			<?php elseif (($enabled && $mv_str === $enabled) || (!$enabled && $mv_str === '')): ?>
				<input type="text" class="regular-text widget ui-draggable-dragging code" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="litespeed-mobileview-rules" value="<?php echo esc_textarea($mv_str); ?>" <?php echo $enabled?'':'readonly'; ?> />
			<?php else: ?>
				<input type="text" class="regular-text widget ui-draggable-dragging code" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="litespeed-mobileview-rules" value="" <?php echo $enabled?'':'readonly'; ?> />
				<p class="attention">
					<?php echo __('Htaccess did not match configuration option.', 'litespeed-cache'); ?>
					<?php echo __('Please re-enter the mobile view setting.', 'litespeed-cache'); ?>
					<?php echo sprintf(__('List in WordPress database: %s', 'litespeed-cache'), $enabled); ?>
				</p>
			<?php endif; ?>
				<input type="hidden" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>__default]" id="litespeed-mobileview-rules-default" value="<?php echo esc_textarea($mv_str ?: $wp_default_mobile); ?>" />

			<div class="litespeed-desc">
				<strong><?php echo __('NOTICE:', 'litespeed-cache'); ?></strong>
				<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?><br />

				<i>
					<?php echo sprintf(__('SYNTAX: Each entry should be separated with a bar, %s', 'litespeed-cache'), "'|'."); ?>
					<?php echo sprintf(__('Any spaces should be escaped with a backslash before the space, %s', 'litespeed-cache'), "'\\ '."); ?><br />
					<?php echo sprintf(__('The default list WordPress uses is %s', 'litespeed-cache'), $wp_default_mobile); ?>
				</i>
			</div>
		</td>
	</tr>
	<!-- build_setting_mobile_view end -->