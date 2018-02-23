<?php
if (!defined('WPINC')) die;

?>
	<!-- build_setting_cache_resources -->
	<tr>
		<th><?php echo __('Cache PHP Resources', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_CACHE_RES); ?>
			<div class="litespeed-desc">
				<?php echo __('Some themes and plugins add resources via a PHP request.', 'litespeed-cache'); ?>
				<?php echo __('Caching these pages may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache'); ?>
				<br /><font class="litespeed-warning">
					<?php echo __('NOTE', 'litespeed-cache'); ?>:
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>
