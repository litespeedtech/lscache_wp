<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __('Crawler General Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:crawler', false, 'litespeed-learn-more' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Base::O_CRAWLER; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'This will enable crawler cron.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_USLEEP; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input($id); ?> <?php echo __('microseconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in microseconds for the delay between requests during a crawl.', 'litespeed-cache'); ?>

				<?php if ( ! empty( $_SERVER[ Base::ENV_CRAWLER_USLEEP ] ) ) : ?>
					<font class="litespeed-warning">
						<?php echo __('NOTE', 'litespeed-cache'); ?>:
						<?php echo __( 'Server allowed min value', 'litespeed-cache'); ?>: <code><?php echo $_SERVER[ Base::ENV_CRAWLER_USLEEP ]; ?></code>
					</font>
				<?php else : ?>
					<?php $this->recommended( $id ); ?>
				<?php endif; ?>

				<?php $this->_validate_ttl( $id, false, 30000 ); ?>

				<br />
				<?php $this->_api_env_var( Base::ENV_CRAWLER_USLEEP ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_RUN_DURATION; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in seconds for the duration of the crawl interval.', 'litespeed-cache'); ?>
				<?php $this->recommended($id); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_RUN_INTERVAL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify time in seconds for the time between each run interval.', 'litespeed-cache'); ?>
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 60 ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_CRAWL_INTERVAL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long in seconds before the crawler should initiate crawling the entire sitemap again.', 'litespeed-cache'); ?>
				<?php $this->recommended($id); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_THREADS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify Number of Threads to use while crawling.', 'litespeed-cache'); ?>
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 1, 16 ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_TIMEOUT; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the timeout while crawling each URL.', 'litespeed-cache' ); ?>
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 10, 300 ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_SERVER_IP; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enter this site\'s IP address to allow cloud services directly call IP instead of domain name. This eliminates the overhead of DNS and CDN lookups.', 'litespeed-cache' ); ?>
				<br /><?php echo __('Your server IP is', 'litespeed-cache'); ?>: <code id='litespeed_server_ip'>-</code> <a href="javascript:;" class="button button-link" id="litespeed_get_ip"><?php echo __('Check my public IP from', 'litespeed-cache'); ?> DoAPI.us</a>
				<font class="litespeed-warning litespeed-left10">
					⚠️ <?php echo __( 'Notice', 'litespeed-cache' ); ?>: <?php echo __( 'the auto-detected IP may not be accurate if you have an additional outgoing IP set, or you have multiple IPs configured on your server. Please make sure this IP is the correct one for visiting your site.', 'litespeed-cache' ); ?>
				</font>

				<?php $this->_validate_ip( $id ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_LOAD_LIMIT; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __( 'The maximum average server load allowed while crawling. The number of crawler threads in use will be actively reduced until average server load falls under this limit. If this cannot be achieved with a single thread, the current crawler run will be terminated.', 'litespeed-cache' );
				?>

				<?php if ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ) ) : ?>
					<font class="litespeed-warning">
						<?php echo __('NOTE', 'litespeed-cache'); ?>:
						<?php echo __( 'Server enforced value', 'litespeed-cache'); ?>: <code><?php echo $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ]; ?></code>
					</font>
				<?php elseif ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ] ) ) : ?>
					<font class="litespeed-warning">
						<?php echo __('NOTE', 'litespeed-cache'); ?>:
						<?php echo __( 'Server allowed max value', 'litespeed-cache'); ?>: <code><?php echo $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ]; ?></code>
					</font>
				<?php else : ?>
					<?php $this->recommended($id); ?>

				<?php endif; ?>

				<br />
				<?php $this->_api_env_var( Base::ENV_CRAWLER_LOAD_LIMIT, Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ); ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php
$this->form_end();
