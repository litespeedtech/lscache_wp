<?php
if (!defined('WPINC')) die;

?>
	<!-- build_setting_auto_upgrade -->
	<tr>
		<th><?php echo __( 'Automatically Upgrade', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPT_AUTO_UPGRADE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Turn this option ON to have LiteSpeed Cache updated automatically, whenever a new version is released. If OFF, update manually as usual.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>
