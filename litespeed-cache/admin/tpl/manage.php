<?php
if (!defined('WPINC')) die;

LiteSpeed_Cache_Admin_Display::get_instance()->check_license();

?>

<div class="wrap">
	<h2>
		<?php echo __('LiteSpeed Cache Management', 'litespeed-cache'); ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
		</span>
	</h2>
</div>
<div class="wrap">
	<div class="litespeed-cache-welcome-panel">
		<p><?php echo __('From this screen, one can inform the server to purge the selected cached pages or empty the entire cache.', 'litespeed-cache'); ?></p>


		<table class="form-table"><tbody>
			<tr>
				<th><?php echo __('Purge the Front Page.', 'litespeed-cache'); ?></th>
				<td>
					<a href="<?php echo LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_FRONT); ?>" class="litespeed-btn litespeed-btn-success">
						<?php echo __('Purge Front Page', 'litespeed-cache'); ?>
					</a>
					<div class="litespeed-desc">
						<?php echo __('This will Purge Front Page only', 'litespeed-cache'); ?>
					</div>
				</td>
			</tr>

			<tr>
				<th><?php echo __('Purge Pages.', 'litespeed-cache'); ?></th>
				<td>
					<a href="<?php echo LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_PAGES); ?>" class="litespeed-btn litespeed-btn-success">
						<?php echo __('Purge Pages', 'litespeed-cache'); ?>
					</a>
					<div class="litespeed-desc">
						<?php echo __('This will Purge Pages only', 'litespeed-cache'); ?>
					</div>
				</td>
			</tr>

			<tr>
				<th><?php echo __('Purge the error pages.', 'litespeed-cache'); ?></th>
				<td>
					<form method="post" action="admin.php?page=lscache-dash">
						<?php $this->form_action(LiteSpeed_Cache::ACTION_PURGE_ERRORS); ?>

						<div class="litespeed-row">
							<?php $this->build_checkbox('include_403', __('Include 403', 'litespeed-cache'), 'checked', 'is_mini'); ?>
							<?php $this->build_checkbox('include_404', __('Include 404', 'litespeed-cache'), false, 'is_mini'); ?>
							<?php $this->build_checkbox('include_500', __('Include 500s', 'litespeed-cache'), 'checked', 'is_mini'); ?>
						</div>

						<div class="litespeed-row">
							<button type="submit" class="litespeed-btn litespeed-btn-success">
								<?php echo __('Purge Error Pages', 'litespeed-cache'); ?>
							</button>
						</div>

						<div class="litespeed-desc">
							<?php echo __('Purges the error page cache entries created by this plugin.', 'litespeed-cache'); ?>
						</div>
					</form>
				</td>
			</tr>

			<tr>
				<th><?php echo __('Purge all WordPress pages.', 'litespeed-cache'); ?></th>
				<td>
					<a href="<?php echo LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_ALL); ?>" class="litespeed-btn litespeed-btn-warning"
						<?php if (is_multisite() && is_network_admin()): ?>
							data-litespeed-cfm="<?php echo esc_html(__('This will purge everything for all blogs.', 'litespeed-cache')); ?> <?php echo esc_html(__('Are you sure you want to purge all?', 'litespeed-cache')); ?>"
						<?php else: ?>
							data-litespeed-cfm="<?php echo esc_html(__('Are you sure you want to purge all?', 'litespeed-cache')); ?>"
						<?php endif; ?>
					>
						<?php echo __('Purge All', 'litespeed-cache'); ?>
					</a>
					<div class="litespeed-desc">
						<?php echo __('Purge the cache entries created by this plugin.', 'litespeed-cache'); ?>
					</div>
				</td>
			</tr>

			<?php if (!is_multisite() || is_network_admin()): ?>
			<tr>
				<th><?php echo __('Clear all cache entries.', 'litespeed-cache'); ?></th>
				<td>
					<a href="<?php echo LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_EMPTYCACHE); ?>" class="litespeed-btn litespeed-btn-danger" data-litespeed-cfm="
<?php echo esc_html(__('This will clear EVERYTHING inside the cache.', 'litespeed-cache')); ?>
 <?php echo esc_html(__('This may cause heavy load on the server.', 'litespeed-cache')); ?>
 <?php echo esc_html(__('If only the WordPress site should be purged, use purge all.', 'litespeed-cache')); ?>
					">
						<?php echo __('Empty Entire Cache', 'litespeed-cache'); ?>
					</a>
					<div class="litespeed-desc">
						<?php echo __('Clears all cache entries related to this site, <i>including other web applications</i>.', 'litespeed-cache'); ?>
						<?php echo __('<b>This action should only be used if things are cached incorrectly.</b>', 'litespeed-cache'); ?>
					</div>
				</td>
			</tr>
			<?php endif; ?>
		</tbody></table>

	<?php if (!is_multisite() || !is_network_admin()): ?>

		<h3><?php echo __('Purge By...', 'litespeed-cache'); ?></h3>
		<hr/>
		<p>
			<?php echo __('Select below for "Purge by" options.', 'litespeed-cache'); ?>
			<?php echo __('Please enter one per line.', 'litespeed-cache'); ?>
		</p>

		<?php
			$purgeby_option = false;
			$_option_field = LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT;
			if(!empty($_REQUEST[$_option_field])){
				$purgeby_option = $_REQUEST[$_option_field];
			}
			if( !in_array($purgeby_option, array(
				LiteSpeed_Cache_Admin_Display::PURGEBY_CAT,
				LiteSpeed_Cache_Admin_Display::PURGEBY_PID,
				LiteSpeed_Cache_Admin_Display::PURGEBY_TAG,
				LiteSpeed_Cache_Admin_Display::PURGEBY_URL,
			)) ) {
				$purgeby_option = LiteSpeed_Cache_Admin_Display::PURGEBY_CAT;
			}
		?>

		<form method="post" action="admin.php?page=lscache-dash">
			<?php $this->form_action(LiteSpeed_Cache::ACTION_PURGE_BY); ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info litespeed-mini">
					<?php $val = LiteSpeed_Cache_Admin_Display::PURGEBY_CAT;?>
					<input type="radio" name="<?php echo $_option_field; ?>" id="purgeby_option_category"
						value="<?php echo $val; ?>" <?php if( $purgeby_option == $val ) echo 'checked'; ?>
					/>
					<label for="purgeby_option_category"><?php echo __('Category', 'litespeed-cache'); ?></label>

					<?php $val = LiteSpeed_Cache_Admin_Display::PURGEBY_PID;?>
					<input type="radio" name="<?php echo $_option_field; ?>" id="purgeby_option_postid"
						value="<?php echo $val; ?>" <?php if( $purgeby_option == $val ) echo 'checked'; ?>
					/>
					<label for="purgeby_option_postid"><?php echo __('Post ID', 'litespeed-cache'); ?></label>

					<?php $val = LiteSpeed_Cache_Admin_Display::PURGEBY_TAG;?>
					<input type="radio" name="<?php echo $_option_field; ?>" id="purgeby_option_tag"
						value="<?php echo $val; ?>" <?php if( $purgeby_option == $val ) echo 'checked'; ?>
					/>
					<label for="purgeby_option_tag"><?php echo __('Tag', 'litespeed-cache'); ?></label>

					<?php $val = LiteSpeed_Cache_Admin_Display::PURGEBY_URL;?>
					<input type="radio" name="<?php echo $_option_field; ?>" id="purgeby_option_url"
						value="<?php echo $val; ?>" <?php if( $purgeby_option == $val ) echo 'checked'; ?>
					/>
					<label for="purgeby_option_url"><?php echo __('URL', 'litespeed-cache'); ?></label>
				</div>

				<div class="litespeed-cache-purgeby-text">
					<div class="<?php if($purgeby_option != LiteSpeed_Cache_Admin_Display::PURGEBY_CAT) echo 'litespeed-hide'; ?>"
						data-purgeby="<?php echo LiteSpeed_Cache_Admin_Display::PURGEBY_CAT; ?>">
						<?php echo sprintf(__('Purge pages by category name - e.g. %2$s should be used for the URL %1$s.', "litespeed-cache"),
							'http://example.com/category/category-name/', 'category-name'); ?>
					</div>
					<div class="<?php if($purgeby_option != LiteSpeed_Cache_Admin_Display::PURGEBY_PID) echo 'litespeed-hide'; ?>"
						data-purgeby="<?php echo LiteSpeed_Cache_Admin_Display::PURGEBY_PID; ?>">
						<?php echo __("Purge pages by post ID.", "litespeed-cache"); ?>
					</div>
					<div class="<?php if($purgeby_option != LiteSpeed_Cache_Admin_Display::PURGEBY_TAG) echo 'litespeed-hide'; ?>"
						data-purgeby="<?php echo LiteSpeed_Cache_Admin_Display::PURGEBY_TAG; ?>">
						<?php echo sprintf(__('Purge pages by tag name - e.g. %2$s should be used for the URL %1$s.', "litespeed-cache"),
							'http://example.com/tag/tag-name/', 'tag-name'); ?>
					</div>
					<div class="<?php if($purgeby_option != LiteSpeed_Cache_Admin_Display::PURGEBY_URL) echo 'litespeed-hide'; ?>"
						data-purgeby="<?php echo LiteSpeed_Cache_Admin_Display::PURGEBY_URL; ?>">
						<?php echo __('Purge pages by relative or full URL.', 'litespeed-cache'); ?>
						<?php echo sprintf(__('e.g. Use %s or %s.', 'litespeed-cache'),
							'<b><u>/2016/02/24/hello-world/</u></b>',
							'<b><u>http://www.myexamplesite.com/2016/02/24/hello-world/</u></b>'); ?>
					</div>
				</div>

			</div>

			<p>
				<textarea name="<?php echo LiteSpeed_Cache_Admin_Display::PURGEBYOPT_LIST; ?>" rows="5" class="code litespeed-cache-purgeby-textarea"></textarea>
			</p>

			<p>
				<button type="submit" class="litespeed-btn litespeed-btn-success"><?php echo __('Purge List', 'litespeed-cache'); ?></button>
			</p>
		</form>
	<?php endif; ?>

	</div>
</div>

