<?php
if ( ! defined( 'WPINC' ) ) die ;

?>
	<tr>
		<th><?php echo __( 'Check Advanced Cache', 'litespeed-cache' ) ; ?></th>
		<td>
		<?php
			$id = LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE;
			$this->build_checkbox($id, __('Include advanced-cache.php', 'litespeed-cache'), $_options[$id]);
		?>
			<div class="litespeed-desc">
				<?php echo __('The advanced-cache.php file is used by many caching plugins to signal that a cache is active.', 'litespeed-cache'); ?>
				<?php echo __('When this option is checked and this file is detected as belonging to another plugin, LiteSpeed Cache will not cache.', 'litespeed-cache'); ?>
			</div>
			<p>
				<i><?php echo __('Uncheck this option only if the other plugin is used for non-caching purposes, such as minifying css/js files.', 'litespeed-cache'); ?></i>
			</p>

		</td>
	</tr>

