<?php
if (!defined('WPINC')) die;

?>

<!-- URI List -->
<h3 class="litespeed-title"><?php echo __('URI List', 'litespeed-cache'); ?></h3>
<p>
	<?php echo __('Paths containing these strings will not be cached.', 'litespeed-cache'); ?>
	<?php echo __('The URLs will be compared to the REQUEST_URI server variable.', 'litespeed-cache'); ?>
</p>
<div class="litespeed-desc">
	<i>
		<?php echo __('To do an exact match, add \'$\' to the end of the URL.', 'litespeed-cache'); ?>
		<?php echo __('One per line.', 'litespeed-cache'); ?>
	</i>
</div>
<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_EXCLUDES_URI); ?>

<!-- Category List -->
<h3 class="litespeed-title"><?php echo __('Category List', 'litespeed-cache'); ?></h3>
<p><?php echo sprintf( __( 'To prevent %s from being cached, enter it below.', 'litespeed-cache' ), __( 'categories', 'litespeed-cache') ) ; ?></p>
<div class="litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('If the Category ID is not found, the name will be removed on save.', 'litespeed-cache'); ?></li>
		<li><?php echo sprintf(__('To exclude %1$s, insert %2$s.', 'litespeed-cache'),
				'<code>http://www.example.com/category/category-id/</code>', '<code>category-id</code>'); ?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<b><?php echo __('All categories are cached by default.', 'litespeed-cache'); ?></b>
	<i>
		<?php echo __('One per line.', 'litespeed-cache'); ?>
	</i>
</div>
<?php
	$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT;
	$excludes_buf = '';
	$cat_ids = $_options[$id];
	if ($cat_ids != '') {
		$id_list = explode(',', $cat_ids);
		$excludes_buf = implode("\n", array_map('get_cat_name', $id_list));
	}
	$this->build_textarea($id, $excludes_buf);
?>

<!-- Tag List -->
<h3 class="litespeed-title"><?php echo __('Tag List', 'litespeed-cache'); ?></h3>
<p><?php echo sprintf( __( 'To prevent %s from being cached, enter it below.', 'litespeed-cache' ), __( 'tags', 'litespeed-cache') ) ; ?></p>
<div class="litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('If the Tag ID is not found, the name will be removed on save.', 'litespeed-cache'); ?></li>
		<li><?php echo sprintf(__('To exclude %1$s, insert %2$s.', 'litespeed-cache'),
				'<code>http://www.example.com/tag/category/tag-id/</code>', '<code>tag-id</code>'); ?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<b><?php echo __('All tags are cached by default.', 'litespeed-cache'); ?></b>
	<i>
		<?php echo __('One per line.', 'litespeed-cache'); ?>
	</i>
</div>
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
	$this->build_textarea($id, $excludes_buf);
?>

<?php
if (is_multisite()) {
	return;
}
?>

<!-- Cookie List -->
<?php require LSWCP_DIR . 'admin/tpl/settings_inc.exclude_cookies.php'; ?>

<!-- User Agent List -->
<?php require LSWCP_DIR . 'admin/tpl/settings_inc.exclude_useragent.php'; ?>

