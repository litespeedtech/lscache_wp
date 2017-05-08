<?php
if (!defined('WPINC')) die;
?>
<h3 class="litespeed-title"><?php echo __('Specific Pages', 'litespeed-cache'); ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Enable Cache for Login Page', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_CACHE_LOGIN); ?>
			<div class="litespeed-desc">
				<?php echo __('Disabling this option may negatively affect performance.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<?php
		if ( !is_multisite() ){
			require LSWCP_DIR . 'admin/tpl/settings_inc.cache_favicon.php';
			require LSWCP_DIR . 'admin/tpl/settings_inc.cache_resources.php';
		}
	?>
</tbody></table>

