<?php
if (!defined('WPINC')) die;

?>
<h3 class="litespeed-title"><?php echo __('General Network Configuration', 'litespeed-cache'); ?></h3>

<p><?php echo __('These configuration are only available network wide.', 'litespeed-cache'); ?></p>

<p>
	<?php echo __('Separate Mobile Views should be enabled if any of the network enabled themes require a different view for mobile devices.', 'litespeed-cache'); ?>
	<?php echo __('Responsive themes can handle this part automatically.', 'litespeed-cache'); ?>
</p>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Network Enable Cache', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED); ?>
			<div class="litespeed-desc">
				<?php echo __('Enabling LiteSpeed Cache for WordPress here enables the cache for the network.', 'litespeed-cache'); ?><br />
				<?php echo __('It is <b>STRONGLY</b> recommend that the compatibility with other plugins on a single/few sites is tested first.', 'litespeed-cache'); ?>
				<?php echo __('This is to ensure compatibility prior to enabling the cache for all sites.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Use Primary Site Configuration', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::NETWORK_OPID_USE_PRIMARY); ?>
			<div class="litespeed-desc">
				<?php echo __("Check this option to use the primary site's configuration for all subsites.", 'litespeed-cache'); ?>
				<?php echo __('This will disable the settings page on all subsites.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.purge_on_upgrade.php'; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_favicon.php'; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_resources.php'; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.mobile_view.php'; ?>

</tbody></table>

