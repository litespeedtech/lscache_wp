<?php
if (!defined('WPINC')) die;

?>
	<!-- build_setting_purge_on_upgrade -->
	<tr>
		<th><?php echo __('Purge All On Upgrade', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE); ?>
			<div class="litespeed-desc">
				<?php echo __('When enabled, the cache will automatically purge when any plugins, themes, or WordPress core is upgraded.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>
