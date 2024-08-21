<?php

namespace LiteSpeed;

defined('WPINC') || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __('Advanced Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/cache/#advanced-tab'); ?>
</h3>

<div class="litespeed-callout notice notice-warning inline">
	<h4><?php echo __('NOTICE:', 'litespeed-cache'); ?></h4>
	<p><?php echo __('These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache'); ?></p>
</div>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $id = Base::O_CACHE_AJAX_TTL; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<div class="litespeed-textarea-recommended">
					<div>
						<?php $this->build_textarea($id, 60); ?>
					</div>
				</div>
				<div class="litespeed-desc">
					<?php echo __('Specify an AJAX action in POST/GET and the number of seconds to cache that request, separated by a space.', 'litespeed-cache'); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<?php
		if (!$this->_is_multisite) :
			require LSCWP_DIR . 'tpl/cache/settings_inc.login_cookie.tpl.php';
		endif;
		?>

		<tr>
			<th>
				<?php $id = Base::O_UTIL_NO_HTTPS_VARY; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Enable this option if you are using both HTTP and HTTPS in the same domain and are noticing cache irregularities.', 'litespeed-cache'); ?>
					<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/cache/#improve-httphttps-compatibility'); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_UTIL_INSTANT_CLICK; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('When a visitor hovers over a page link, preload that page. This will speed up the visit to that link.', 'litespeed-cache'); ?>
					<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/cache/#instant-click'); ?>
					<br />
					<font class="litespeed-danger">
						⚠️
						<?php echo __('This will generate extra requests to the server, which will increase server load.', 'litespeed-cache'); ?>
					</font>

				</div>
			</td>
		</tr>

	</tbody>
</table>