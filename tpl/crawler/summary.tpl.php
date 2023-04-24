<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$__crawler = Crawler::cls();
$crawler_list = $__crawler->list_crawlers();

$summary = Crawler::get_summary();
if ($summary['curr_crawler'] >= count($crawler_list)) {
	$summary['curr_crawler'] = 0;
}

$is_running = time() - $summary['is_running'] <= $this->conf(Base::O_CRAWLER_RUN_DURATION);

$disabled = Router::can_crawl() ? '' : 'disabled';

$seconds = $this->conf(Base::O_CRAWLER_RUN_INTERVAL);
if ($seconds > 0) :
	$recurrence = '';
	$hours = (int)floor($seconds / 3600);
	if ($hours) {
		if ($hours > 1) {
			$recurrence .= sprintf(__('%d hours', 'litespeed-cache'), $hours);
		} else {
			$recurrence .= sprintf(__('%d hour', 'litespeed-cache'), $hours);
		}
	}
	$minutes = (int)floor(($seconds % 3600) / 60);
	if ($minutes) {
		$recurrence .= ' ';
		if ($minutes > 1) {
			$recurrence .= sprintf(__('%d minutes', 'litespeed-cache'), $minutes);
		} else {
			$recurrence .= sprintf(__('%d minute', 'litespeed-cache'), $minutes);
		}
	}
?>

	<h3 class="litespeed-title litespeed-relative">
		<?php echo __('Crawler Cron', 'litespeed-cache'); ?>
		<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/crawler/'); ?>
	</h3>

	<?php if (!Router::can_crawl()) : ?>
		<div class="litespeed-callout notice notice-error inline">
			<h4><?php echo __('WARNING', 'litespeed-cache'); ?></h4>
			<p><?php echo __('The crawler feature is not enabled on the LiteSpeed server. Please consult your server admin or hosting provider.', 'litespeed-cache'); ?></p>
			<p><?php echo sprintf(__('See <a %s>Introduction for Enabling the Crawler</a> for detailed information.', 'litespeed-cache'), 'href="https://docs.litespeedtech.com/lscache/lscwp/admin/#enabling-and-limiting-the-crawler" target="_blank"'); ?></p>
		</div>
	<?php endif; ?>


	<?php if ($summary['this_full_beginning_time']) : ?>
		<p>
			<b><?php echo __('Current sitemap crawl started at', 'litespeed-cache'); ?>:</b>
			<?php echo Utility::readable_time($summary['this_full_beginning_time']); ?>
		</p>

		<?php if (!$is_running) : ?>
			<p>
				<b><?php echo __('The next complete sitemap crawl will start at', 'litespeed-cache'); ?>:</b>
				<?php echo date('m/d/Y H:i:s', $summary['this_full_beginning_time'] + LITESPEED_TIME_OFFSET + $summary['last_full_time_cost'] + $this->conf(Base::O_CRAWLER_CRAWL_INTERVAL)); ?>
			<?php endif; ?>
			</p>

		<?php endif; ?>
		<?php if ($summary['last_full_time_cost']) : ?>
			<p>
				<b><?php echo __('Last complete run time for all crawlers', 'litespeed-cache'); ?>:</b>
				<?php echo sprintf(__('%d seconds', 'litespeed-cache'), $summary['last_full_time_cost']); ?>
			</p>
		<?php endif; ?>

		<?php if ($summary['last_crawler_total_cost']) : ?>
			<p>
				<b><?php echo __('Run time for previous crawler', 'litespeed-cache'); ?>:</b>
				<?php echo sprintf(__('%d seconds', 'litespeed-cache'), $summary['last_crawler_total_cost']); ?>
			</p>
		<?php endif; ?>

		<?php if ($summary['curr_crawler_beginning_time']) : ?>
			<p>
				<b><?php echo __('Current crawler started at', 'litespeed-cache'); ?>:</b>
				<?php echo Utility::readable_time($summary['curr_crawler_beginning_time']); ?>
			</p>
		<?php endif; ?>

		<p>
			<b><?php echo __('Current server load', 'litespeed-cache'); ?>:</b>
			<?php echo $__crawler->get_server_load(); ?>
		</p>

		<?php if ($summary['last_start_time']) : ?>
			<p class='litespeed-desc'>
				<b><?php echo __('Last interval', 'litespeed-cache'); ?>:</b>
				<?php echo Utility::readable_time($summary['last_start_time']); ?>
			</p>
		<?php endif; ?>

		<?php if ($summary['end_reason']) : ?>
			<p class='litespeed-desc'>
				<b><?php echo __('Ended reason', 'litespeed-cache'); ?>:</b>
				<?php echo esc_html($summary['end_reason']); ?>
			</p>
		<?php endif; ?>

		<?php if ($summary['last_crawled']) : ?>
			<p class='litespeed-desc'>
				<?php echo sprintf(__('<b>Last crawled:</b> %s item(s)', 'litespeed-cache'), $summary['last_crawled']); ?>
			</p>
		<?php endif; ?>

		<p>
			<?php echo " <a href='" . Utility::build_url(Router::ACTION_CRAWLER, Crawler::TYPE_RESET) . "' class='button litespeed-btn-warning'>" . __('Reset position', 'litespeed-cache') . "</a>";

			$href = Router::can_crawl() ? Utility::build_url(Router::ACTION_CRAWLER, Crawler::TYPE_START) : 'javascript:;';
			echo " <a href='$href' id='litespeed_manual_trigger' class='button litespeed-btn-success' litespeed-accesskey='R' $disabled>" . __('Manually run', 'litespeed-cache') . "</a>";
			?>
		</p>


		<table class="wp-list-table widefat striped" data-crawler-list>
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col"><?php echo __('Cron Name', 'litespeed-cache'); ?></th>
					<th scope="col"><?php echo __('Run Frequency', 'litespeed-cache'); ?></th>
					<th scope="col"><?php echo __('Status', 'litespeed-cache'); ?></th>
					<th scope="col"><?php echo __('Activate', 'litespeed-cache'); ?></th>
					<th scope="col"><?php echo __('Running', 'litespeed-cache'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($crawler_list as $i => $v) :
					$hit = !empty($summary['crawler_stats'][$i]['H']) ? $summary['crawler_stats'][$i]['H'] : 0;
					$miss = !empty($summary['crawler_stats'][$i]['M']) ? $summary['crawler_stats'][$i]['M'] : 0;

					$blacklisted = !empty($summary['crawler_stats'][$i]['B']) ? $summary['crawler_stats'][$i]['B'] : 0;
					$blacklisted += !empty($summary['crawler_stats'][$i]['N']) ? $summary['crawler_stats'][$i]['N'] : 0;

					if (isset($summary['crawler_stats'][$i]['W'])) {
						$waiting = $summary['crawler_stats'][$i]['W'] ?: 0;
					} else {
						$waiting = $summary['list_size'] - $hit - $miss - $blacklisted;
					}
				?>
					<tr>
						<td>
							<?php
							echo $i + 1;
							if ($i == $summary['curr_crawler']) {
								echo "<img class='litespeed-crawler-curr' src='" . LSWCP_PLUGIN_URL . "assets/img/Litespeed.icon.svg' />";
							}
							?>
						</td>
						<td>
							<?php echo $v['title']; ?>
						</td>
						<td><?php echo $recurrence; ?></td>
						<td>
							<?php echo '<i class="litespeed-badge litespeed-bg-default" data-balloon-pos="up" aria-label="' . __('Waiting', 'litespeed-cache') . '">' . ($waiting ?: '-') . '</i> '; ?>
							<?php echo '<i class="litespeed-badge litespeed-bg-success" data-balloon-pos="up" aria-label="' . __('Hit', 'litespeed-cache') . '">' . ($hit ?: '-') . '</i> '; ?>
							<?php echo '<i class="litespeed-badge litespeed-bg-primary" data-balloon-pos="up" aria-label="' . __('Miss', 'litespeed-cache') . '">' . ($miss ?: '-') . '</i> '; ?>
							<?php echo '<i class="litespeed-badge litespeed-bg-danger" data-balloon-pos="up" aria-label="' . __('Blocklisted', 'litespeed-cache') . '">' . ($blacklisted ?: '-') . '</i> '; ?>
						</td>
						<td>
							<?php $this->build_toggle('litespeed-crawler-' . $i,  $__crawler->is_active($i)); ?>
						</td>
						<td>
							<?php
							if ($i == $summary['curr_crawler']) {
								echo "Position: " . ($summary['last_pos'] + 1);
								if ($is_running) {
									echo " <span class='litespeed-label-success'>" . __('running', 'litespeed-cache') . "</span>";
								}
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<i class="litespeed-badge litespeed-bg-default"></i> = <?php echo __('Waiting to be Crawled', 'litespeed-cache'); ?><br>
			<i class="litespeed-badge litespeed-bg-success"></i> = <?php echo __('Already Cached', 'litespeed-cache'); ?><br>
			<i class="litespeed-badge litespeed-bg-primary"></i> = <?php echo __('Successfully Crawled', 'litespeed-cache'); ?><br>
			<i class="litespeed-badge litespeed-bg-danger"></i> = <?php echo __('Blocklisted', 'litespeed-cache'); ?><br>
		</p>

		<div class="litespeed-desc">
			<div><?php echo __('Run frequency is set by the Interval Between Runs setting.', 'litespeed-cache'); ?></div>
			<div><?php echo __('Crawlers cannot run concurrently.', 'litespeed-cache')
						. __('&nbsp;If both the cron and a manual run start at similar times, the first to be started will take precedence.', 'litespeed-cache'); ?></div>
			<div><?php echo sprintf(__('Please see <a %s>Hooking WP-Cron Into the System Task Scheduler</a> to learn how to create the system cron task.', 'litespeed-cache'), ' href="https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/" target="_blank" '); ?></div>
		</div>
	<?php endif; ?>


	<h3 class="litespeed-title"><?php echo __('Watch Crawler Status', 'litespeed-cache'); ?></h3>

	<?php
	$ajaxUrl = $__crawler->json_path();
	if ($ajaxUrl) :
	?>

		<input type="button" id="litespeed-crawl-url-btn" value="<?php echo __('Show crawler status', 'litespeed-cache'); ?>" class="button button-secondary" data-url="<?php echo $ajaxUrl; ?>" />

		<div class="litespeed-shell litespeed-hide">
			<div class="litespeed-shell-header-bar"></div>
			<div class="litespeed-shell-header">
				<div class="litespeed-shell-header-bg"></div>
				<div class="litespeed-shell-header-icon-container">
					<img id="litespeed-shell-icon" src="<?php echo LSWCP_PLUGIN_URL . 'assets/img/Litespeed.icon.svg'; ?>" />
				</div>
			</div>
			<ul class="litespeed-shell-body">
				<li>Start watching...</li>
				<li id="litespeed-loading-dot"></li>
			</ul>
		</div>

	<?php else : ?>
		<p>
			<?php echo __('No crawler meta file generated yet', 'litespeed-cache'); ?>
		</p>
	<?php endif; ?>