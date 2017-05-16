<?php
if ( !defined('WPINC') ) die;

?>

<h3 class="litespeed-title"><?php echo __('Crawler Settings', 'litespeed-cache'); ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Delay', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_input(LiteSpeed_Cache_Config::CRWL_USLEEP); ?> <?php echo __('microseconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in microseconds for Crawler delay execution.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Run Duration', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_input(LiteSpeed_Cache_Config::CRWL_RUN_DURATION); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long for each run duration in seconds', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Cron Interval', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_input(LiteSpeed_Cache_Config::CRWL_CRON_INTERVAL); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify the interval between each Cron runs in seconds', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Threads', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_input(LiteSpeed_Cache_Config::CRWL_THREADS); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify Number of Threads to use while crawling', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Server Load Limit', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_input(LiteSpeed_Cache_Config::CRWL_LOAD_LIMIT); ?>
			<div class="litespeed-desc">
				<?php echo __('Set the max server load limit to use crawler', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Sitemap Generation Blacklist', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::CRWL_BLACKLIST); ?>
			<div class="litespeed-desc">
				<?php echo __('All Urls which has no-cache tags will be added here, After the initial crawling.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Include Posts', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_POSTS); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Posts URL for Crawler', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Include Pages', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_PAGES); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Pages URL for Crawler', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Include Categories', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_CATS); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Categories URL for Crawler', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Include Tags', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_TAGS); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Tags URL for Crawler', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Exclude Custom Post Types', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::CRWL_EXCLUDES_CPT); ?>

			<div class="litespeed-desc">
				<?php echo __('If you want to exlude Custom Post Type URL in Crawler file, Add the Custom Post Types in the box. One per line.', 'litespeed-cache'); ?>
			</div>

			<div class="litespeed-callout litespeed-callout-warning">
				<h4><?php echo __('Available Custom Post Type','litespeed-cache'); ?></h4>
				<?php echo implode('<br />', array_diff(get_post_types( '', 'names' ), array('post', 'page'))); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Order links by', 'litespeed-cache'); ?></th>
		<td>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<?php $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS, 
						LiteSpeed_Cache_Config::CRWL_DATE_DESC,
						__('Date, descending (Default)', 'litespeed-cache')
					); ?>

					<?php $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS, 
						LiteSpeed_Cache_Config::CRWL_DATE_ASC,
						__('Date, ascending', 'litespeed-cache')
					); ?>

					<?php $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS, 
						LiteSpeed_Cache_Config::CRWL_ALPHA_DESC,
						__('Alphabetical, descending', 'litespeed-cache')
					); ?>

					<?php $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS, 
						LiteSpeed_Cache_Config::CRWL_ALPHA_ASC,
						__('Alphabetical, ascending', 'litespeed-cache')
					); ?>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __('Please choose one of the above options, to set the order of your links', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

</tbody></table>