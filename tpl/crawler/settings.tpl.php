<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __('Crawler General Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/crawler/#general-settings-tab'); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $id = Base::O_CRAWLER; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('This will enable crawler cron.', 'litespeed-cache'); ?>
					<br /><?php Doc::notice_htaccess(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CRAWLER_CRAWL_INTERVAL; ?>
				<?php $this->title($id); ?>
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
				<?php $id = Base::O_CRAWLER_SITEMAP; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_textarea($id); ?>
				<div class="litespeed-desc">
					<?php echo __('The crawler will use your XML sitemap or sitemap index. Enter the full URL to your sitemap here.', 'litespeed-cache'); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CRAWLER_LOAD_LIMIT; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_input($id); ?>
				<div class="litespeed-desc">
					<?php echo __('The maximum average server load allowed while crawling. The number of crawler threads in use will be actively reduced until average server load falls under this limit. If this cannot be achieved with a single thread, the current crawler run will be terminated.', 'litespeed-cache');
					?>

					<?php if (!empty($_SERVER[Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE])) : ?>
						<font class="litespeed-warning">
							<?php echo __('NOTE', 'litespeed-cache'); ?>:
							<?php echo __('Server enforced value', 'litespeed-cache'); ?>: <code><?php echo $_SERVER[Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE]; ?></code>
						</font>
					<?php elseif (!empty($_SERVER[Base::ENV_CRAWLER_LOAD_LIMIT])) : ?>
						<font class="litespeed-warning">
							<?php echo __('NOTE', 'litespeed-cache'); ?>:
							<?php echo __('Server allowed max value', 'litespeed-cache'); ?>: <code><?php echo $_SERVER[Base::ENV_CRAWLER_LOAD_LIMIT]; ?></code>
						</font>
					<?php endif; ?>

					<br />
					<?php $this->_api_env_var(Base::ENV_CRAWLER_LOAD_LIMIT, Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CRAWLER_ROLES; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_textarea($id, 20); ?>

				<div class="litespeed-desc">
					<?php echo __('To crawl the site as a logged-in user, enter the user ids to be simulated.', 'litespeed-cache'); ?>
					<?php Doc::one_per_line(); ?>

					<?php if (empty($this->conf(Base::O_SERVER_IP))) : ?>
						<div class="litespeed-danger litespeed-text-bold">
							🚨
							<?php echo __('NOTICE', 'litespeed-cache'); ?>:
							<?php echo sprintf(__('You must set %s before using this feature.', 'litespeed-cache'), Lang::title(Base::O_SERVER_IP)); ?>
							<?php echo Doc::learn_more(admin_url('admin.php?page=litespeed-general#settings'), __('Click here to set.', 'litespeed-cache'), true, false, true); ?>
						</div>
					<?php endif; ?>

					<?php if (empty($this->conf(Base::O_ESI))) : ?>
						<div class="litespeed-danger litespeed-text-bold">
							🚨
							<?php echo __('NOTICE', 'litespeed-cache'); ?>:
							<?php echo sprintf(__('You must set %1$s to %2$s before using this feature.', 'litespeed-cache'), Lang::title(Base::O_ESI), __('ON', 'litespeed-cache')); ?>
							<?php echo Doc::learn_more(admin_url('admin.php?page=litespeed-cache#esi'), __('Click here to set.', 'litespeed-cache'), true, false, true); ?>
						</div>
					<?php endif; ?>
				</div>

			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CRAWLER_COOKIES; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->enroll($id . '[name][]'); ?>
				<?php $this->enroll($id . '[vals][]'); ?>

				<div id="litespeed_crawler_simulation_div"></div>

				<script type="text/babel">
					ReactDOM.render(
					<CrawlerSimulate list={ <?php echo json_encode($this->conf($id)); ?> } />,
					document.getElementById( 'litespeed_crawler_simulation_div' )
				);
			</script>

				<div class="litespeed-desc">
					<?php echo __('To crawl for a particular cookie, enter the cookie name, and the values you wish to crawl for. Values should be one per line. There will be one crawler created per cookie value, per simulated role.', 'litespeed-cache'); ?>
					<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/crawler/#cookie-simulation'); ?>
					<p><?php echo sprintf(__('Use %1$s in %2$s to indicate this cookie has not been set.', 'litespeed-cache'), '<code>_null</code>', __('Cookie Values', 'litespeed-cache')); ?></p>
				</div>

			</td>
		</tr>


	</tbody>
</table>

<?php
$this->form_end();
