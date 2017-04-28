<?php
if (!defined('WPINC')) die;

?>

<h3 class="litespeed-title"><?=__('Crawler Settings', 'litespeed-cache')?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?=__('Include Posts', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_POSTS; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_enable" value="1" <?=$_options[$id]?'checked':''?> />
					<label for="conf_<?=$id?>_enable"><?=__('Enable', 'litespeed-cache')?></label>

					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_disable" value="0" <?=$_options[$id]?'':'checked'?> />
					<label for="conf_<?=$id?>_disable"><?=__('Disable', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('Include Posts URL for Crawler', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Include Pages', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_PAGES; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_enable" value="1" <?=$_options[$id]?'checked':''?> />
					<label for="conf_<?=$id?>_enable"><?=__('Enable', 'litespeed-cache')?></label>

					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_disable" value="0" <?=$_options[$id]?'':'checked'?> />
					<label for="conf_<?=$id?>_disable"><?=__('Disable', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('Include Pages URL for Crawler', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Include Categories', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_CATS; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_enable" value="1" <?=$_options[$id]?'checked':''?> />
					<label for="conf_<?=$id?>_enable"><?=__('Enable', 'litespeed-cache')?></label>

					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_disable" value="0" <?=$_options[$id]?'':'checked'?> />
					<label for="conf_<?=$id?>_disable"><?=__('Disable', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('Include Categories URL for Crawler', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Include Tags', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_TAGS; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_enable" value="1" <?=$_options[$id]?'checked':''?> />
					<label for="conf_<?=$id?>_enable"><?=__('Enable', 'litespeed-cache')?></label>

					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_disable" value="0" <?=$_options[$id]?'':'checked'?> />
					<label for="conf_<?=$id?>_disable"><?=__('Disable', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('Include Tags URL for Crawler', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Exclude Custom Post Types', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_EXCLUDES_CPT; ?>
			<?php
				$all_cpt = implode('<br />', array_diff(get_post_types( '', 'names' ), array('post', 'page')));
			?>
			<textarea name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" rows="5" cols="80"><?=esc_textarea($_options[$id])?></textarea>

			<div class="litespeed-desc">
				<?=__('If you want to exlude Custom Post Type URL in Crawler file, Add the Custom Post Types in the box. One per line.', 'litespeed-cache')?>
			</div>

			<div class="litespeed-callout litespeed-callout-warning">
				<h4><?=__('Available Custom Post Type','litespeed-cache')?></h4>
				<?=$all_cpt?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Order links by', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_ORDER_LINKS; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<?php $val = LiteSpeed_Cache_Config::CRWL_DATE_DESC; ?>
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_1" value="<?=$val?>" <?=$_options[$id]==$val?'checked':''?> />
					<label for="conf_<?=$id?>_1"><?=__('Date, descending (Default)', 'litespeed-cache')?></label>

					<?php $val = LiteSpeed_Cache_Config::CRWL_DATE_ASC; ?>
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_2" value="<?=$val?>" <?=$_options[$id]==$val?'checked':''?> />
					<label for="conf_<?=$id?>_2"><?=__('Date, ascending', 'litespeed-cache')?></label>

					<?php $val = LiteSpeed_Cache_Config::CRWL_ALPHA_DESC; ?>
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_3" value="<?=$val?>" <?=$_options[$id]==$val?'checked':''?> />
					<label for="conf_<?=$id?>_3"><?=__('Alphabetical, descending', 'litespeed-cache')?></label>

					<?php $val = LiteSpeed_Cache_Config::CRWL_ALPHA_ASC; ?>
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_3" value="<?=$val?>" <?=$_options[$id]==$val?'checked':''?> />
					<label for="conf_<?=$id?>_3"><?=__('Alphabetical, ascending', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('Please choose one of the above options, to set the order of your links', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Delay', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_USLEEP; ?>
			<input type="text" class="regular-text" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" value="<?=esc_textarea($_options[$id])?>" /> <?=__('microseconds', 'litespeed-cache')?>
			<div class="litespeed-desc">
				<?=__('Specify time in microsends for Crawler delay execution.', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Run Duration', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_RUN_DURATION; ?>
			<input type="text" class="regular-text" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" value="<?=esc_textarea($_options[$id])?>" /> <?=__('seconds', 'litespeed-cache')?>
			<div class="litespeed-desc">
				<?=__('Specify how long for each run duration in seconds', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Cron Interval', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_CRON_INTERVAL; ?>
			<input type="text" class="regular-text" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" value="<?=esc_textarea($_options[$id])?>" /> <?=__('seconds', 'litespeed-cache')?>
			<div class="litespeed-desc">
				<?=__('Specify the interval between each Cron runs in seconds', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Threads', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_THREADS; ?>
			<input type="text" class="regular-text" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" value="<?=esc_textarea($_options[$id])?>" /> <?=__('seconds', 'litespeed-cache')?>
			<div class="litespeed-desc">
				<?=__('Specify Number of Threads to use while crawling', 'litespeed-cache')?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?=__('Sitemap Generation Blacklist', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_BLACKLIST; ?>
			<textarea name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" rows="5" cols="80"><?=esc_textarea($_options[$id])?></textarea>
			<div class="litespeed-desc">
				<?=__('All Urls which has no-cache tags will be added here, After the initial crawl.', 'litespeed-cache')?>
			</div>
		</td>
	</tr>
</tbody></table>