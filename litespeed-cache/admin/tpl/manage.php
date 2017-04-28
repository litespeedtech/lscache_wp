<?php
if (!defined('WPINC')) die;

if ($error_msg = LiteSpeed_Cache_Admin_Display::get_instance()->check_license() !== true) {
	echo '<div class="error"><p>' . $error_msg . '</p></div>' . "\n";
}

?>

<div class="wrap">
	<h2><?=__('LiteSpeed Cache Management', 'litespeed-cache')?></h2>
</div>
<div class="wrap">
	<div class="litespeed-cache-welcome-panel">
		<p><?=__('From this screen, one can inform the server to purge the selected cached pages or empty the entire cache.', 'litespeed-cache')?></p>


		<table class="form-table"><tbody>
			<tr>
				<th><?=__('Purge the Front Page.', 'litespeed-cache')?></th>
				<td>
					<a href="<?=LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_FRONT)?>" class="litespeed-btn litespeed-btn-success">
						<?=__('Purge Front Page', 'litespeed-cache')?>
					</a>
					<div class="litespeed-desc">
						<?=__('This will Purge Front Page only', 'litespeed-cache')?>
					</div>
				</td>
			</tr>

			<tr>
				<th><?=__('Purge Pages.', 'litespeed-cache')?></th>
				<td>
					<a href="<?=LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_PAGES)?>" class="litespeed-btn litespeed-btn-success">
						<?=__('Purge Pages', 'litespeed-cache')?>
					</a>
					<div class="litespeed-desc">
						<?=__('This will Purge Pages only', 'litespeed-cache')?>
					</div>
				</td>
			</tr>

			<tr>
				<th><?=__('Purge the error pages.', 'litespeed-cache')?></th>
				<td>
					<form method="post" action="admin.php?page=lscache-dash">
						<?php $this->form_action(LiteSpeed_Cache::ACTION_PURGE_ERRORS); ?>

						<div class="litespeed-row">
							<div class="litespeed-radio litespeed-mini">
								<input type="checkbox" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[include_403]" id="include_403" value="1" checked />
								<label for="include_403"><?=__('Include 403', 'litespeed-cache')?></label>
							</div>
							
							<div class="litespeed-radio litespeed-mini">
								<input type="checkbox" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[include_404]" id="include_404" value="1" />
								<label for="include_404"><?=__('Include 404', 'litespeed-cache')?></label>
							</div>
							<div class="litespeed-radio litespeed-mini">
								<input type="checkbox" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[include_500]" id="include_500" value="1" checked />
								<label for="include_500"><?=__('Include 500s', 'litespeed-cache')?></label>
							</div>
						</div>

						<div class="litespeed-row">
							<button type="submit" class="litespeed-btn litespeed-btn-success">
								<?=__('Purge Error Pages', 'litespeed-cache')?>
							</button>
						</div>

						<div class="litespeed-desc">
							<?=__('Purges the error page cache entries created by this plugin.', 'litespeed-cache')?>
						</div>
					</form>
				</td>
			</tr>

			<tr>
				<th><?=__('Purge all WordPress pages.', 'litespeed-cache')?></th>
				<td>
					<a href="<?=LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_ALL)?>" class="litespeed-btn litespeed-btn-warning"
						<?php if (is_multisite() && is_network_admin()): ?>
							data-litespeed-cfm="<?=esc_html(__('This will purge everything for all blogs.', 'litespeed-cache'))?> <?=esc_html(__('Are you sure you want to purge all?', 'litespeed-cache'))?>"
						<?php else: ?>
							data-litespeed-cfm="<?=esc_html(__('Are you sure you want to purge all?', 'litespeed-cache'))?>"
						<?php endif; ?>
					>
						<?=__('Purge All', 'litespeed-cache')?>
					</a>
					<div class="litespeed-desc">
						<?=__('Purge the cache entries created by this plugin.', 'litespeed-cache')?>
					</div>
				</td>
			</tr>

			<?php if (!is_multisite() || is_network_admin()): ?>
			<tr>
				<th><?=__('Clear all cache entries.', 'litespeed-cache')?></th>
				<td>
					<a href="<?=LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_PURGE_EMPTYCACHE)?>" class="litespeed-btn litespeed-btn-danger" data-litespeed-cfm="
<?=esc_html(__('This will clear EVERYTHING inside the cache.', 'litespeed-cache'))?>
 <?=esc_html(__('This may cause heavy load on the server.', 'litespeed-cache'))?>
 <?=esc_html(__('If only the WordPress site should be purged, use purge all.', 'litespeed-cache'))?>
					">
						<?=__('Empty Entire Cache', 'litespeed-cache')?>
					</a>
					<div class="litespeed-desc">
						<?=__('Clears all cache entries related to this site, <i>including other web applications</i>.', 'litespeed-cache')?>
						<?=__('<b>This action should only be used if things are cached incorrectly.</b>', 'litespeed-cache')?>
					</div>
				</td>
			</tr>
			<?php endif; ?>
		</tbody></table>

	<?php if (!is_multisite() || !is_network_admin()): ?>

		<h3><?=__('Purge By...', 'litespeed-cache')?></h3>
		<hr/>
		<p>
			<?=__('Select below for "Purge by" options.', 'litespeed-cache')?>
			<?=__('Please enter one per line.', 'litespeed-cache')?>
		</p>

		<?php
			$purgeby_option = !empty($_POST['purgeby_option']) ? $_POST['purgeby_option'] : false;
			if(!in_array($purgeby_option, array('postid', 'tag', 'url'))) $purgeby_option = 'category';
		?>

		<div class="litespeed-row">
			<div class="litespeed-switch litespeed-label-info litespeed-mini">
				<input type="radio" name="purgeby_option" id="purgeby_option_category" value="category" <?=$purgeby_option=='category'?'checked':''?>>
				<label for="purgeby_option_category"><?=__('Category', 'litespeed-cache')?></label>

				<input type="radio" name="purgeby_option" id="purgeby_option_postid" value="postid" <?=$purgeby_option=='postid'?'checked':''?>>
				<label for="purgeby_option_postid"><?=__('Post ID', 'litespeed-cache')?></label>

				<input type="radio" name="purgeby_option" id="purgeby_option_tag" value="tag" <?=$purgeby_option=='tag'?'checked':''?>>
				<label for="purgeby_option_tag"><?=__('Tag', 'litespeed-cache')?></label>

				<input type="radio" name="purgeby_option" id="purgeby_option_url" value="url" <?=$purgeby_option=='url'?'checked':''?>>
				<label for="purgeby_option_url"><?=__('URL', 'litespeed-cache')?></label>
			</div>

			<div class="litespeed-cache-purgeby-text">
				<div class="<?=$purgeby_option=='category'?'':'litespeed-hide'?>" data-purgeby="category">
					<?=sprintf(__('Purge pages by category name - e.g. %2$s should be used for the URL %1$s.', "litespeed-cache"),
						'http://example.com/category/category-name/', 'category-name')?>
				</div>
				<div class="<?=$purgeby_option=='postid'?'':'litespeed-hide'?>" data-purgeby="postid">
					<?=__("Purge pages by post ID.", "litespeed-cache")?>
				</div>
				<div class="<?=$purgeby_option=='tag'?'':'litespeed-hide'?>" data-purgeby="tag">
					<?=sprintf(__('Purge pages by tag name - e.g. %2$s should be used for the URL %1$s.', "litespeed-cache"),
						'http://example.com/tag/tag-name/', 'tag-name')?>
				</div>
				<div class="<?=$purgeby_option=='url'?'':'litespeed-hide'?>" data-purgeby="url">
					<?=__('Purge pages by relative URL.', 'litespeed-cache')?>
					<?=__('Must be exact match.', 'litespeed-cache')?>
					<?=sprintf(__('e.g. Use %s for %s.', 'litespeed-cache'),
						'<b><u>/2016/02/24/hello-world/</u></b>',
						'http://www.myexamplesite.com<b><u>/2016/02/24/hello-world/</u></b>')?>
				</div>
			</div>

		</div>

		<p>
			<textarea name="purgeby_content" rows="5" class="code litespeed-cache-purgeby-textarea"></textarea>
		</p>

		<p>
			<button type="submit" class="litespeed-btn litespeed-btn-success"><?=__('Purge List', 'litespeed-cache')?></button>
		</p>
	<?php endif; ?>

	</div>
</div>

