<?php
if (!defined('WPINC')) die;

?>

<!-- URI List -->
<h3 class="litespeed-title"><?php echo __('URI List', 'litespeed-cache'); ?></h3>
<ol>
	<li><?php echo __('Enter a list of urls that should not be cached.', 'litespeed-cache'); ?></li>
	<li><?php echo __('The urls will be compared to the REQUEST_URI server variable.', 'litespeed-cache'); ?></li>
	<li><?php echo __('There should only be one url per line.', 'litespeed-cache'); ?></li>
</ol>
<div class="litespeed-callout litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('URLs must start with a \'/\' to be correctly matched.', 'litespeed-cache'); ?></li>
		<li><?php echo __('To do an exact match, add \'$\' to the end of the URL.', 'litespeed-cache'); ?></li>
		<li><?php echo __('Any surrounding whitespaces will be trimmed.', 'litespeed-cache'); ?></li>
		<li><?php echo sprintf(__('e.g. to exclude %1$s, insert %2$s', 'litespeed-cache'),
				'http://www.example.com/excludethis.php', '/excludethis.php'); ?></li>
		<li><?php echo sprintf(__('Similarly, to exclude %1$s(accessed with the /blog), insert %2$s', 'litespeed-cache'),
				'http://www.example.com/blog/excludethis.php', '/blog/excludethis.php'); ?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<i>
		<?php echo __('SYNTAX: URLs must start with a \'/\' to be correctly matched.', 'litespeed-cache'); ?><br />
		<?php echo __('To do an exact match, add \'$\' to the end of the URL. One URL per line.', 'litespeed-cache'); ?>
	</i>
</div>
<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_EXCLUDES_URI); ?>

<!-- Category List -->
<h3 class="litespeed-title"><?php echo __('Category List', 'litespeed-cache'); ?></h3>
<ol>
	<li><b><?php echo __('All categories are cached by default.', 'litespeed-cache'); ?></b></li>
	<li><?php echo __('To prevent a category from being cached, enter it in the text area below, one per line.', 'litespeed-cache'); ?></li>
</ol>
<div class="litespeed-callout litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('If the Category ID is not found, the name will be removed on save.', 'litespeed-cache'); ?></li>
		<li><?php echo sprintf(__('e.g. to exclude %1$s, insert %2$s', 'litespeed-cache'),
				'<code style="font-size: 11px;">http://www.example.com/category/category-id/</code>', 'category-id'); ?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<i>
		<?php echo __('SYNTAX: One category id per line.', 'litespeed-cache'); ?>
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
<ol>
	<li><b><?php echo __('All tags are cached by default.', 'litespeed-cache'); ?></b></li>
	<li><?php echo __('To prevent tags from being cached, enter the tag in the text area below, one per line.', 'litespeed-cache'); ?></li>
</ol>
<div class="litespeed-callout litespeed-callout-warning">
	<h4><?php echo __('NOTE:', 'litespeed-cache'); ?></h4>
	<ol>
		<li><?php echo __('If the Tag ID is not found, the name will be removed on save.', 'litespeed-cache'); ?></li>
		<li><?php echo sprintf(__('e.g. to exclude %1$s, insert %2$s', 'litespeed-cache'),
				'http://www.example.com/tag/category/tag-id/', 'tag-id'); ?></li>
	</ol>
</div>
<div class="litespeed-desc">
	<i>
		<?php echo __('SYNTAX: One tag id per line.', 'litespeed-cache'); ?>
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

