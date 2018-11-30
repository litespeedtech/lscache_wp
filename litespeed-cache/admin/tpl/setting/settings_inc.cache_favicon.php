<?php
if (!defined('WPINC')) die;

?>
	<tr>
		<th><?php echo __('Cache favicon.ico', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_CACHE_FAVICON); ?>
			<div class="litespeed-desc">
				<?php echo __('favicon.ico is requested on most pages.', 'litespeed-cache'); ?>
				<?php echo __('Caching this resource may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache'); ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>
