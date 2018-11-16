<?php
if (!defined('WPINC')) die;

?>
	<!-- build_setting_auto_upgrade -->
	<tr>
		<th><?php echo __( 'Automatically Upgrade', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPT_AUTO_UPGRADE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'When enabled, our plugin will be automatically up-to-date when a new version released.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>
