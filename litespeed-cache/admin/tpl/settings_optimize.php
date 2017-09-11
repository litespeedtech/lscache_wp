<?php
if ( ! defined( 'WPINC' ) ) die ;


?>

<h3 class="litespeed-title"><?php echo __('Optimization Settings', 'litespeed-cache'); ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('CSS Minifiy', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_CSS_MINIFY); ?>
			<div class="litespeed-desc">
				<?php echo __('Minify CSS files.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('CSS Combine', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_CSS_COMBINE); ?>
			<div class="litespeed-desc">
				<?php echo __('Combine CSS files.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('JS Minifiy', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_JS_MINIFY); ?>
			<div class="litespeed-desc">
				<?php echo __('Minify JS files.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('JS Combine', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_JS_COMBINE); ?>
			<div class="litespeed-desc">
				<?php echo __('Combine JS files.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('HTML Minify', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_HTML_MINIFY); ?>
			<div class="litespeed-desc">
				<?php echo __('Minify HTML content.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

</tbody></table>