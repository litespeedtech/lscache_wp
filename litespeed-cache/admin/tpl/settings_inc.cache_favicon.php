<?php
if (!defined('WPINC')) die;

$file_writable = LiteSpeed_Cache_Admin_Rules::writable();
?>
	<tr>
		<th><?php echo __('Cache favicon.ico', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_CACHE_FAVICON);/*, !$file_writable*/ ?>
			<div class="litespeed-desc">
				<?php echo __('favicon.ico is requested on most pages.', 'litespeed-cache'); ?>
				<?php echo __('Caching this recource may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>
