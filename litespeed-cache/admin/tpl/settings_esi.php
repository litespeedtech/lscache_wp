<?php
if ( !defined('WPINC') ) die;
		// comments
		// comment form
		// admin bar

?>

<h3 class="litespeed-title"><?php echo __('ESI Settings', 'litespeed-cache'); ?></h3>

<p><?php echo __('ESI enables the capability to cache pages for logged in users/commenters.', 'litespeed-cache'); ?></p>
<p><?php echo __('ESI functions by replacing the private information blocks with an ESI include.', 'litespeed-cache'); ?></p>
<p><?php echo __('When the server sees an ESI include, a sub request is created, containing the private information.', 'litespeed-cache'); ?></p>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Enable ESI', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_ENABLE); ?>
			<div class="litespeed-desc">
				<?php echo __('Enabling ESI will cache the public page for logged in users.', 'litespeed-cache'); ?>
				<?php echo __('The Admin Bar, comments, and comment form will be served via ESI blocks.', 'litespeed-cache'); ?>
				<?php echo __('The ESI blocks will not be cached until Cache ESI is checked.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Cache ESI', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_CACHE); ?>
			<div class="litespeed-desc">
				<?php echo __('Cache the ESI blocks.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

</tbody></table>