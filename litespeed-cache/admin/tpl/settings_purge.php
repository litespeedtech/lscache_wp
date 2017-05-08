<?php
if (!defined('WPINC')) die;

?>
<h3 class="litespeed-title"><?php echo __('Auto Purge Rules For Publish/Update', 'litespeed-cache'); ?></h3>

<p><?php echo __('Select which pages will be automatically purged when posts are published/updated.', 'litespeed-cache'); ?></p>

<div class="litespeed-callout litespeed-callout-warning">
	<h4><?php echo __('Note:', 'litespeed-cache'); ?></h4>
	<i>
		<?php echo __('Select "All" if there are dynamic widgets linked to posts on pages other than the front or home pages.', 'litespeed-cache'); ?><br />
		<?php echo __('Other checkboxes will be ignored.', 'litespeed-cache'); ?><br />
		<?php echo __('Select only the archive types that are currently used, the others can be left unchecked.', 'litespeed-cache'); ?>
	</i>
</div>

<?php
$purge_options = LiteSpeed_Cache_Config::get_instance()->get_purge_options();
$optionArr = array(
	LiteSpeed_Cache_Config::PURGE_ALL_PAGES => __('All pages', 'litespeed-cache'),
	LiteSpeed_Cache_Config::PURGE_FRONT_PAGE => __('Front page', 'litespeed-cache'),
	LiteSpeed_Cache_Config::PURGE_HOME_PAGE => __('Home page', 'litespeed-cache'),
	LiteSpeed_Cache_Config::PURGE_PAGES => __('Pages', 'litespeed-cache'),

	LiteSpeed_Cache_Config::PURGE_PAGES_WITH_RECENT_POSTS => __('All pages with Recent Posts Widget', 'litespeed-cache'),

	LiteSpeed_Cache_Config::PURGE_AUTHOR => __('Author archive', 'litespeed-cache'),
	LiteSpeed_Cache_Config::PURGE_POST_TYPE => __('Post type archive', 'litespeed-cache'),

	LiteSpeed_Cache_Config::PURGE_YEAR => __('Yearly archive', 'litespeed-cache'),
	LiteSpeed_Cache_Config::PURGE_MONTH => __('Monthly archive', 'litespeed-cache'),
	LiteSpeed_Cache_Config::PURGE_DATE => __('Daily archive', 'litespeed-cache'),

	LiteSpeed_Cache_Config::PURGE_TERM => __('Term archive (include category, tag, and tax)', 'litespeed-cache'),
);

// break line at these ids
$breakArr = array(
	LiteSpeed_Cache_Config::PURGE_PAGES,
	LiteSpeed_Cache_Config::PURGE_PAGES_WITH_RECENT_POSTS,
	LiteSpeed_Cache_Config::PURGE_POST_TYPE,
	LiteSpeed_Cache_Config::PURGE_DATE,
);

?>

<div class="litespeed-row litespeed-top20">
<?php
	foreach ($optionArr as $id => $title){

		$this->build_checkbox("purge_$id", $title, in_array($id, $purge_options));

		if ( in_array($id, $breakArr) ){
			echo '</div><div class="litespeed-row litespeed-top20">';
		}
	}
?>
</div>

