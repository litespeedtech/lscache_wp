<?php
if ( !defined('WPINC') ) die;

?>

<h3 class="litespeed-title"><?php echo __('Crawler Settings', 'litespeed-cache'); ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Delay', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_USLEEP ; ?>
			<?php $this->build_input($id); ?> <?php echo __('microseconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in microseconds for the delay between requests during a crawl.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Run Duration', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_RUN_DURATION ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in seconds for the duration of the crawl interval.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Interval Between Runs', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_RUN_INTERVAL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in seconds for the time between each run interval. Must be greater than 60.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Crawl Interval', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_CRAWL_INTERVAL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long in seconds before the crawler should initiate crawling the entire sitemap again.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Threads', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_THREADS ; ?>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify Number of Threads to use while crawling.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Server Load Limit', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_LOAD_LIMIT ; ?>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __( 'The maximum average server load allowed while crawling. The number of crawler threads in use will be actively reduced until average server load falls under this limit. If this cannot be achieved with a single thread, the current crawler run will be terminated.', 'litespeed-cache' ) ;
				?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Site IP', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_DOMAIN_IP ; ?>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __('Enter this site\'s IP address to crawl by IP instead of domain name. This eliminates the overhead of DNS and CDN lookups. (optional)', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Custom Sitemap', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_CUSTOM_SITEMAP ; ?>
			<?php $this->build_input($id, false, false, false, 'litespeed_custom_sitemap'); ?>
			<div class="litespeed-desc">
				<?php echo __('The crawler can use your Google XML Sitemap instead of its own. Enter the full URL to your sitemap here.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr data-litespeed-selfsitemap="1">
		<th><?php echo __('Include Posts', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_POSTS); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Posts in crawler sitemap generation.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr data-litespeed-selfsitemap="1">
		<th><?php echo __('Include Pages', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_PAGES); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Pages in crawler sitemap generation.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr data-litespeed-selfsitemap="1">
		<th><?php echo __('Include Categories', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_CATS); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Categories pages in crawler sitemap generation.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr data-litespeed-selfsitemap="1">
		<th><?php echo __('Include Tags', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::CRWL_TAGS); ?>
			<div class="litespeed-desc">
				<?php echo __('Include Tags pages in crawler sitemap generation.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr data-litespeed-selfsitemap="1">
		<th><?php echo __('Exclude Custom Post Types', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::CRWL_EXCLUDES_CPT); ?>

			<div class="litespeed-desc">
				<?php echo __('If you want to exclude certain Custom Post Types in sitemap, add the Custom Post Types in the box, one per line.', 'litespeed-cache'); ?>
			</div>

			<div class="litespeed-callout litespeed-callout-warning">
				<h4><?php echo __('Available Custom Post Type','litespeed-cache'); ?></h4>
				<?php echo implode('<br />', array_diff(get_post_types( '', 'names' ), array('post', 'page'))); ?>
			</div>
		</td>
	</tr>

	<tr data-litespeed-selfsitemap="1">
		<th><?php echo __('Order links by', 'litespeed-cache'); ?></th>
		<td>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS,
						LiteSpeed_Cache_Config::CRWL_DATE_DESC,
						__('Date, descending (Default)', 'litespeed-cache')
					); ?>

					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS,
						LiteSpeed_Cache_Config::CRWL_DATE_ASC,
						__('Date, ascending', 'litespeed-cache')
					); ?>

					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS,
						LiteSpeed_Cache_Config::CRWL_ALPHA_DESC,
						__('Alphabetical, descending', 'litespeed-cache')
					); ?>

					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::CRWL_ORDER_LINKS,
						LiteSpeed_Cache_Config::CRWL_ALPHA_ASC,
						__('Alphabetical, ascending', 'litespeed-cache')
					); ?>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __('Please choose one of the above options to set the order in which the sitemap will be parsed.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

</tbody></table>