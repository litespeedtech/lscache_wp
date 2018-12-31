<?php
if ( ! defined( 'WPINC' ) ) die ;
?>

<h3 class="litespeed-title-short">
	<?php echo __('Exclude Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:excludes', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php $this->cache_disabled_warning() ; ?>

<table><tbody>
	<tr>
		<th><?php echo __( 'Force Cache URIs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_FORCE_CACHE_URI ) ; ?>
			<div class="litespeed-desc">
				<?php echo __('Paths containing these strings will be cached regardless of no-cacheable settings.', 'litespeed-cache'); ?>
				<?php $this->_uri_usage_example() ; ?>
				<br /><?php echo __( 'To define a custom TTL for a URI, add a space followed by the TTL value to the end of the URI.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'For example, %1$s defines a TTL of %2$s seconds for %3$s.', 'litespeed-cache' ), '<code>/mypath/mypage 300</code>', 300, '<code>/mypath/mypage</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Do Not Cache URIs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_EXCLUDES_URI ) ; ?>
			<div class="litespeed-desc">
				<?php echo __('Paths containing these strings will not be cached.', 'litespeed-cache'); ?>
				<?php $this->_uri_usage_example() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Do Not Cache Query Strings', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_EXCLUDES_QS); ?>
			<div class="litespeed-desc">
				<?php echo __('Query string containing these parameters will not be cached.', 'litespeed-cache'); ?>
				<?php echo sprintf( __( 'For example, for %s, %s and %s can be used here.', 'litespeed-cache' ), '<code>?aa=bb&cc=dd</code>', '<code>aa</code>', '<code>cc</code>' ) ; ?>
				<i>
					<?php echo __('One per line.', 'litespeed-cache'); ?>
				</i>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Do Not Cache Categories', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT;
				$excludes_buf = '';
				$cat_ids = $_options[$id];
				if ($cat_ids != '') {
					$id_list = explode(',', $cat_ids);
					$excludes_buf = implode("\n", array_map('get_cat_name', $id_list));
				}
				$this->build_textarea($id, false, $excludes_buf);
			?>
			<div class="litespeed-desc">
				<b><?php echo __('All categories are cached by default.', 'litespeed-cache'); ?></b>
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'categories', 'litespeed-cache') ) ; ?>
				<i>
					<?php echo __('One per line.', 'litespeed-cache'); ?>
				</i>
			</div>
			<div class="litespeed-callout-warning">
				<h4><?php echo __('NOTE', 'litespeed-cache'); ?>:</h4>
				<ol>
					<li><?php echo __('If the category slug is not found, the category will be removed from the list on save.', 'litespeed-cache'); ?></li>
					<li><?php echo sprintf(__('To exclude %1$s, insert %2$s.', 'litespeed-cache'),
							'<code>http://www.example.com/category/category-slug/</code>', '<code>category-slug</code>'); ?></li>
				</ol>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Do Not Cache Tags', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG;
				$excludes_buf = '';
				$ids = $_options[$id];
				if ($ids != '') {
					$id_list = explode(',', $ids);
					$tags_list = array_map('get_tag', $id_list);
					$tag_names = array();
					foreach ($tags_list as $tag) {
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
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'tags', 'litespeed-cache') ) ; ?>
				<i>
					<?php echo __('One per line.', 'litespeed-cache'); ?>
				</i>
			</div>
			<div class="litespeed-callout-warning">
				<h4><?php echo __('NOTE', 'litespeed-cache'); ?>:</h4>
				<ol>
					<li><?php echo __('If the tag slug is not found, the tag will be removed from the list on save.', 'litespeed-cache'); ?></li>
					<li><?php echo sprintf(__('To exclude %1$s, insert %2$s.', 'litespeed-cache'),
							'<code>http://www.example.com/tag/category/tag-slug/</code>', '<code>tag-slug</code>'); ?></li>
				</ol>
			</div>
		</td>
	</tr>

	<?php
		if ( ! is_multisite() ) :
			// Cookie
			require LSCWP_DIR . 'admin/tpl/setting/settings_inc.exclude_cookies.php' ;

			// User Agent
			require LSCWP_DIR . 'admin/tpl/setting/settings_inc.exclude_useragent.php' ;

		endif ;
	?>


	<tr>
		<th><?php echo __('Do Not Cache Roles', 'litespeed-cache'); ?></th>
		<td>
			<?php foreach ( $roles as $role => $title ): ?>
				<?php $this->build_checkbox( LiteSpeed_Cache_Config::EXCLUDE_CACHE_ROLES . "][", $title, $this->config->in_exclude_cache_roles( $role ), $role ) ; ?>
			<?php endforeach; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Selected roles will be excluded from cache.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>



</tbody></table>
