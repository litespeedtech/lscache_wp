<?php
if (!defined('WPINC')) die;

?>
<h3 class="litespeed-title"><?php echo __('General Network Configurations', 'litespeed-cache'); ?></h3>

<p><?php echo __('These configurations are only available network wide.', 'litespeed-cache'); ?></p>

<p>
	<?php echo __('Separate Mobile Views should be enabled if any of the network enabled themes require a different view for mobile devices.', 'litespeed-cache'); ?>
	<?php echo __('Responsive themes can handle this part automatically.', 'litespeed-cache'); ?>
</p>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Network Enable Cache', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_enable" value="1" <?php if($_options[$id]) echo 'checked'; ?> />
					<label for="conf_<?php echo $id; ?>_enable"><?php echo __('Enable', 'litespeed-cache'); ?></label>

					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_disable" value="0" <?php if(!$_options[$id]) echo 'checked'; ?> />
					<label for="conf_<?php echo $id; ?>_disable"><?php echo __('Disable', 'litespeed-cache'); ?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __('Enabling LiteSpeed Cache for WordPress here enables the cache for the network.', 'litespeed-cache'); ?><br />
				<?php echo __('It is <b>STRONGLY</b> recommend that the compatibility with other plugins on a single/few sites is tested first.', 'litespeed-cache'); ?>
				<?php echo __('This is to ensure compatibility prior to enabling the cache for all sites.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Use Primary Site Configurations', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::NETWORK_OPID_USE_PRIMARY; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_enable" value="1" <?php if($_options[$id]) echo 'checked'; ?> />
					<label for="conf_<?php echo $id; ?>_enable"><?php echo __('Enable', 'litespeed-cache'); ?></label>

					<input type="radio" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>]" id="conf_<?php echo $id; ?>_disable" value="0" <?php if(!$_options[$id]) echo 'checked'; ?> />
					<label for="conf_<?php echo $id?>_disable"><?php echo __('Disable', 'litespeed-cache'); ?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __("Check this option to use the primary site's configurations for all subsites.", 'litespeed-cache'); ?>
				<?php echo __('This will disable the settings page on all subsites.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.purge_on_upgrade.php'; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_favicon.php'; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_resources.php'; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.mobile_view.php'; ?>

</tbody></table>

