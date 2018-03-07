<?php
if ( !defined('WPINC') ) die;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Crawler Settings', 'litespeed-cache'); ?>
	<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:crawler" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
</h3>

<table><tbody>
	<tr>
		<th><?php echo __('Delay', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_USLEEP ; ?>
			<?php $this->build_input($id); ?> <?php echo __('microseconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in microseconds for the delay between requests during a crawl.', 'litespeed-cache'); ?>

				<?php if ( ! empty( $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ] ) ) : ?>
					<font class="litespeed-warning">
						<?php echo __('NOTE', 'litespeed-cache'); ?>:
						<?php echo __( 'Server allowed min value', 'litespeed-cache') ; ?>: <code><?php echo $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ] ; ?></code>
					</font>
				<?php else : ?>
					<?php $this->recommended($id) ; ?>
				<?php endif ; ?>


				<br />
				<?php $this->_api_env_var( LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ) ; ?>
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
			<?php $this->build_input( $id, 'litespeed-input-short' ) ; ?>
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

				<?php if ( ! empty( $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ) ) : ?>
					<font class="litespeed-warning">
						<?php echo __('NOTE', 'litespeed-cache'); ?>:
						<?php echo __( 'Server enforced value', 'litespeed-cache') ; ?>: <code><?php echo $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ; ?></code>
					</font>
				<?php elseif ( ! empty( $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT ] ) ) : ?>
					<font class="litespeed-warning">
						<?php echo __('NOTE', 'litespeed-cache'); ?>:
						<?php echo __( 'Server allowed max value', 'litespeed-cache') ; ?>: <code><?php echo $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT ] ; ?></code>
					</font>
				<?php else : ?>
					<?php $this->recommended($id) ; ?>

				<?php endif ; ?>

				<br />
				<?php $this->_api_env_var( LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT, LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ) ; ?>
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
		<th><?php echo __('Role Simulation', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_CRWL_AS_UIDS, 20 ) ; ?>

			<div class="litespeed-desc">
				<?php echo __('To crawl the site as a logged-in user, enter the user ids to be simulated.', 'litespeed-cache'); ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
			</div>

		</td>
	</tr>

	<tr>
		<th><?php echo __( 'HTTP/2 Crawl', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::CRWL_HTTP2 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Crawl using the HTTP/2 protocal.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Current curl HTTP/2 extension status', 'litespeed-cache' ) ; ?>:
				<?php if ( defined( 'CURL_HTTP_VERSION_2' ) ) : ?>
					<font class="litespeed-warning"><?php echo __( 'Enabled', 'litespeed-cache' ) ; ?></font>
				<?php else : ?>
					<font class="litespeed-warning"><?php echo __( 'Disabled', 'litespeed-cache' ) ; ?></font>
				<?php endif ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Custom Sitemap', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::CRWL_CUSTOM_SITEMAP ; ?>
			<?php $this->build_input( $id, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __('The crawler can use your Google XML Sitemap instead of its own. Enter the full URL to your sitemap here.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Sitemap Generation', 'litespeed-cache'); ?></th>
		<td>
			<div class="litespeed-block">
				<div class='litespeed-cdn-mapping-col2'>
					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Posts', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Cache_Config::CRWL_POSTS ) ;
					?>
					</div>

					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Pages', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Cache_Config::CRWL_PAGES ) ;
					?>
					</div>

					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Categories', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Cache_Config::CRWL_CATS ) ;
					?>
					</div>

					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Tags', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Cache_Config::CRWL_TAGS ) ;
					?>
					</div>

				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __('Exclude Custom Post Types', 'litespeed-cache'); ?></h4>

					<?php $this->build_textarea( LiteSpeed_Cache_Config::CRWL_EXCLUDES_CPT, 40 ) ; ?>

					<div class="litespeed-desc">
						<?php echo __('Exclude certain Custom Post Types in sitemap.', 'litespeed-cache'); ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<div class="litespeed-callout-warning">
						<h4><?php echo __('Available Custom Post Type','litespeed-cache'); ?></h4>
						<p>
							<?php echo implode('<br />', array_diff(get_post_types( '', 'names' ), array('post', 'page'))); ?>
						</p>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __('Order links by', 'litespeed-cache'); ?></h4>

					<div class="litespeed-switch">
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
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'These options will be invalid when using %s.', 'litespeed-cache' ), '<code>' . __( 'Custom Sitemap', 'litespeed-cache' ) . '</code>' ) ; ?>
					</div>
				</div>
			</div>

		</td>
	</tr>

</tbody></table>