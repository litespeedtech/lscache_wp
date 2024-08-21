<?php

namespace LiteSpeed;

defined('WPINC') || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __('Exclude Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/cache/#excludes-tab'); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $id = Base::O_CACHE_EXC; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_textarea($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Paths containing these strings will not be cached.', 'litespeed-cache'); ?>
					<?php $this->_uri_usage_example(); ?>
					<br /><?php echo __('Predefined list will also be combined w/ the above settings', 'litespeed-cache'); ?>: <a href="https://github.com/litespeedtech/lscache_wp/blob/dev/data/cache_nocacheable.txt" target="_blank">https://github.com/litespeedtech/lscache_wp/blob/dev/data/cache_nocacheable.txt</a>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CACHE_EXC_QS; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_textarea($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Query strings containing these parameters will not be cached.', 'litespeed-cache'); ?>
					<?php echo sprintf(__('For example, for %s, %s and %s can be used here.', 'litespeed-cache'), '<code>?aa=bb&cc=dd</code>', '<code>aa</code>', '<code>cc</code>'); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CACHE_EXC_CAT; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php
				$excludes_buf = '';
				if ($this->conf($id)) {
					$excludes_buf = implode("\n", array_map('get_cat_name', $this->conf($id)));
				}
				$this->build_textarea($id, false, $excludes_buf);
				?>
				<div class="litespeed-desc">
					<b><?php echo __('All categories are cached by default.', 'litespeed-cache'); ?></b>
					<?php echo sprintf(__('To prevent %s from being cached, enter them here.', 'litespeed-cache'), __('categories', 'litespeed-cache')); ?>
					<?php Doc::one_per_line(); ?>
				</div>
				<div class="litespeed-callout notice notice-warning inline">
					<h4><?php echo __('NOTE', 'litespeed-cache'); ?>:</h4>
					<ol>
						<li><?php echo __('If the category name is not found, the category will be removed from the list on save.', 'litespeed-cache'); ?></li>
					</ol>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CACHE_EXC_TAG; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php
				$excludes_buf = '';
				if ($this->conf($id)) {
					$tag_names = array();
					foreach (array_map('get_tag', $this->conf($id)) as $tag) {
						$tag_names[] = $tag->name;
					}
					if (!empty($tag_names)) {
						$excludes_buf = implode("\n", $tag_names);
					}
				}
				$this->build_textarea($id, false, $excludes_buf);
				?>
				<div class="litespeed-desc">
					<b><?php echo __('All tags are cached by default.', 'litespeed-cache'); ?></b>
					<?php echo sprintf(__('To prevent %s from being cached, enter them here.', 'litespeed-cache'), __('tags', 'litespeed-cache')); ?>
					<?php Doc::one_per_line(); ?>
				</div>
				<div class="litespeed-callout notice notice-warning inline">
					<h4><?php echo __('NOTE', 'litespeed-cache'); ?>:</h4>
					<ol>
						<li><?php echo __('If the tag slug is not found, the tag will be removed from the list on save.', 'litespeed-cache'); ?></li>
						<li><?php echo sprintf(
								__('To exclude %1$s, insert %2$s.', 'litespeed-cache'),
								'<code>http://www.example.com/tag/category/tag-slug/</code>',
								'<code>tag-slug</code>'
							); ?></li>
					</ol>
				</div>
			</td>
		</tr>

		<?php
		if (!$this->_is_multisite) :
			// Cookie
			require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_cookies.tpl.php';

			// User Agent
			require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_useragent.tpl.php';

		endif;
		?>

		<tr>
			<th>
				<?php $id = Base::O_CACHE_EXC_ROLES; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<div class="litespeed-desc">
					<?php echo __('Selected roles will be excluded from cache.', 'litespeed-cache'); ?>
				</div>
				<div class="litespeed-tick-list">
					<?php foreach ($roles as $role => $title) : ?>
						<?php $this->build_checkbox($id . '[]', $title, Control::cls()->in_cache_exc_roles($role), $role); ?>
					<?php endforeach; ?>
				</div>

			</td>
		</tr>

	</tbody>
</table>