<?php
if ( ! defined( 'WPINC' ) ) die ;


?>

<h3 class="litespeed-title"><?php echo __('Optimization Settings', 'litespeed-cache'); ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Text', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_ENABLE); ?>
			<div class="litespeed-desc">
				<?php echo __('test.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

</tbody></table>