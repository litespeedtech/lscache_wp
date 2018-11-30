<?php
if (!defined('WPINC')) die;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Purge Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:purge', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php $this->cache_disabled_warning() ; ?>

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

<table><tbody>

	<?php if (!is_multisite()): ?>
		<?php require LSCWP_DIR . 'admin/tpl/setting/settings_inc.purge_on_upgrade.php'; ?>
	<?php endif; ?>

	<tr>
		<th><?php echo __('Auto Purge Rules For Publish/Update', 'litespeed-cache'); ?></th>
		<td>
			<div class="litespeed-callout-warning">
				<h4><?php echo __('Note', 'litespeed-cache'); ?></h4>:
				<i>
					<?php echo __('Select "All" if there are dynamic widgets linked to posts on pages other than the front or home pages.', 'litespeed-cache'); ?><br />
					<?php echo __('Other checkboxes will be ignored.', 'litespeed-cache'); ?><br />
					<?php echo __('Select only the archive types that are currently used, the others can be left unchecked.', 'litespeed-cache'); ?>
				</i>
			</div>
			<div class="litespeed-top20">
			<?php
				foreach ($optionArr as $id => $title){

					$this->build_checkbox("purge_$id", $title, in_array($id, $purge_options));

					if ( in_array($id, $breakArr) ){
						echo '</div><div class="litespeed-top20">';
					}
				}
			?>
			</div>
			<div class="litespeed-desc">
				<?php echo __('Select which pages will be automatically purged when posts are published/updated.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Scheduled Purge URLs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea( LiteSpeed_Cache_Config::OPID_TIMED_URLS, 80 ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'The URLs here (one per line) will be purged automatically at the time set in the option "%s".', 'litespeed-cache' ), __( 'Scheduled Purge Time', 'litespeed-cache' ) ) ; ?><br />
				<?php echo sprintf( __( 'Both %1$s and %2$s are acceptable.', 'litespeed-cache' ), '<code>http://www.example.com/path/url.php</code>', '<code>/path/url.php</code>' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Scheduled Purge Time', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_TIMED_URLS_TIME ; ?>
			<?php $this->build_input( $id, false, null, null, '', 'time' ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify the time to purge the "%s" list.', 'litespeed-cache' ), __( 'Scheduled Purge URLs', 'litespeed-cache' ) ) ; ?>
				<?php echo sprintf( __( 'Current server time is %s.', 'litespeed-cache' ), '<code>' . date( 'H:i:s' ) . '</code>' ) ; ?>
			</div>
		</td>
	</tr>
</tbody></table>

