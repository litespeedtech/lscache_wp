<?php
if (!defined('WPINC')) die;

$file_writable = LiteSpeed_Cache_Admin_Rules::writable();
?>
	<!-- build_setting_cache_resources -->
	<tr>
		<th><?php echo __('Cache PHP Resources', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_CACHE_RES);/*, !$file_writable*/ ?>
			<div class="litespeed-desc">
				<?php echo __('Some themes and plugins add resources via a PHP request.', 'litespeed-cache'); ?>
				<?php echo __('Caching these pages may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>
