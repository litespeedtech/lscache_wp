<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Purge Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#purge-tab' ); ?>
</h3>

<?php
$option_list = array(
	Base::O_PURGE_POST_ALL => __( 'All pages', 'litespeed-cache' ),
	Base::O_PURGE_POST_FRONTPAGE => __( 'Front page', 'litespeed-cache' ),
	Base::O_PURGE_POST_HOMEPAGE => __( 'Home page', 'litespeed-cache' ),
	Base::O_PURGE_POST_PAGES => __( 'Pages', 'litespeed-cache' ),

	Base::O_PURGE_POST_PAGES_WITH_RECENT_POSTS => __( 'All pages with Recent Posts Widget', 'litespeed-cache' ),

	Base::O_PURGE_POST_AUTHOR => __( 'Author archive', 'litespeed-cache' ),
	Base::O_PURGE_POST_POSTTYPE => __( 'Post type archive', 'litespeed-cache' ),

	Base::O_PURGE_POST_YEAR => __( 'Yearly archive', 'litespeed-cache' ),
	Base::O_PURGE_POST_MONTH => __( 'Monthly archive', 'litespeed-cache' ),
	Base::O_PURGE_POST_DATE => __( 'Daily archive', 'litespeed-cache' ),

	Base::O_PURGE_POST_TERM => __( 'Term archive (include category, tag, and tax)', 'litespeed-cache' ),
);

// break line at these ids
$break_arr = array(
	Base::O_PURGE_POST_PAGES,
	Base::O_PURGE_POST_PAGES_WITH_RECENT_POSTS,
	Base::O_PURGE_POST_POSTTYPE,
	Base::O_PURGE_POST_DATE,
);

?>

<table class="wp-list-table striped litespeed-table"><tbody>

	<?php if ( ! $this->_is_multisite ) : ?>
		<?php require LSCWP_DIR . 'tpl/cache/settings_inc.purge_on_upgrade.tpl.php'; ?>
	<?php endif; ?>

	<tr>
		<th><?php echo __( 'Auto Purge Rules For Publish/Update', 'litespeed-cache' ); ?></th>
		<td>
			<div class="litespeed-callout notice notice-warning inline">
				<h4><?php echo __( 'Note', 'litespeed-cache' ); ?></h4>
				<p>
					<?php echo __( 'Select "All" if there are dynamic widgets linked to posts on pages other than the front or home pages.', 'litespeed-cache' ); ?><br />
					<?php echo __( 'Other checkboxes will be ignored.', 'litespeed-cache' ); ?><br />
					<?php echo __( 'Select only the archive types that are currently used, the others can be left unchecked.', 'litespeed-cache' ); ?>
				</p>
			</div>
			<div class="litespeed-top20">
				<div class="litespeed-tick-wrapper">
					<?php
						foreach ( $option_list as $id => $title ) {

							$this->build_checkbox( $id, $title );

							if ( in_array( $id, $break_arr ) ) {
								echo '</div><div class="litespeed-tick-wrapper litespeed-top10">';
							}
						}
					?>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __( 'Select which pages will be automatically purged when posts are published/updated.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_PURGE_STALE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'If ON, the stale copy of a cached page will be shown to visitors until a new cache copy is available. Reduces the server load for following visits. If OFF, the page will be dynamically generated while visitors wait.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#serve-stale' ); ?>
			</div>
			<div class="litespeed-callout notice notice-warning inline">
				<h4><?php echo __( 'Note', 'litespeed-cache' ); ?></h4>
				<p>
					<?php echo __( 'By design, this option may serve stale content. Do not enable this option, if that is not OK with you.', 'litespeed-cache' ); ?><br />
				</p>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_PURGE_TIMED_URLS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 80 ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'The URLs here (one per line) will be purged automatically at the time set in the option "%s".', 'litespeed-cache' ), __( 'Scheduled Purge Time', 'litespeed-cache' ) ); ?><br />
				<?php echo sprintf( __( 'Both %1$s and %2$s are acceptable.', 'litespeed-cache' ), '<code>http://www.example.com/path/url.php</code>', '<code>/path/url.php</code>' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Wildcard %1$s supported (match zero or more characters). For example, to match %2$s and %3$s, use %4$s.', 'litespeed-cache' ), '<code>*</code>', '<code>/path/u-1.html</code>', '<code>/path/u-2.html</code>', '<code>/path/u-*.html</code>' ); ?>
			</div>
			<div class="litespeed-callout notice notice-warning inline">
				<h4><?php echo __( 'Note', 'litespeed-cache' ); ?></h4>
				<p>
					<?php echo __( 'For URLs with wildcards, there may be a delay in initiating scheduled purge.', 'litespeed-cache' ); ?><br />
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#scheduled-purge-urls' ); ?>
				</p>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_PURGE_TIMED_URLS_TIME; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, null, null, 'time' ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify the time to purge the "%s" list.', 'litespeed-cache' ), __( 'Scheduled Purge URLs', 'litespeed-cache' ) ); ?>
				<?php echo sprintf( __( 'Current server time is %s.', 'litespeed-cache' ), '<code>' . date( 'H:i:s' ) . '</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_PURGE_HOOK_ALL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>

			<div class="litespeed-textarea-recommended">
				<div>
					<?php $this->build_textarea( $id, 50 ); ?>
				</div>
				<div>
					<?php $this->recommended( $id ); ?>
				</div>
			</div>

			<div class="litespeed-desc">
				<?php echo __( 'A Purge All will be executed when WordPress runs these hooks.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#purge-all-hooks' ); ?>
			</div>
		</td>
	</tr>


</tbody></table>

