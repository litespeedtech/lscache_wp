<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$summary = VPI::get_summary();
$closest_server = Cloud::get_summary('server.' . Cloud::SVC_VPI);

$queue = $this->load_queue('vpi');
$vpi_service_hot = $this->cls('Cloud')->service_hot(Cloud::SVC_VPI);
?>

<h3 class="litespeed-title-short">
	<?php echo __('Viewport Images', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/pageopt/#vpi-settings-tab'); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_VPI; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('When you use Lazy Load, it will delay the loading of all images on a page.', 'litespeed-cache'); ?>
					<br /><?php echo __('The Viewport Images service detects which images appear above the fold, and excludes them from lazy load.', 'litespeed-cache'); ?>
					<br /><?php echo __('This enables the page\'s initial screenful of imagery to be fully displayed without delay.', 'litespeed-cache'); ?>

					<?php if (!$this->conf(Base::O_MEDIA_LAZY)) : ?>
						<br />
						<font class="litespeed-warning litespeed-left10">
							⚠️ <?php echo __('Notice', 'litespeed-cache'); ?>: <?php echo sprintf(__('%s must be turned ON for this setting to work.', 'litespeed-cache'),  '<code>' . Lang::title(Base::O_MEDIA_LAZY) . '</code>'); ?>
						</font>
					<?php endif; ?>

				</div>

				<div class="litespeed-desc litespeed-left20">
					<?php if ($summary) : ?>
						<?php if (!empty($summary['last_request'])) : ?>
							<p>
								<?php echo __('Last generated', 'litespeed-cache') . ': <code>' . Utility::readable_time($summary['last_request']) . '</code>'; ?>
							</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ($closest_server) : ?>
						<a class='litespeed-redetect' href="<?php echo Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array('svc' => Cloud::SVC_VPI)); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo sprintf(__('Current closest Cloud server is %s.&#10; Click to redetect.', 'litespeed-cache'), $closest_server); ?>' data-litespeed-cfm="<?php echo __('Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache'); ?>"><i class='litespeed-quic-icon'></i> <?php echo __('Redetect', 'litespeed-cache'); ?></a>
					<?php endif; ?>

					<?php if (!empty($queue)) : ?>
						<div class="litespeed-callout notice notice-warning inline">
							<h4>
								<?php echo sprintf(__('URL list in %s queue waiting for cron', 'litespeed-cache'), 'VPI'); ?> ( <?php echo count($queue); ?> )
								<a href="<?php echo Utility::build_url(Router::ACTION_VPI, VPI::TYPE_CLEAR_Q); ?>" class="button litespeed-btn-warning litespeed-right">Clear</a>
							</h4>
							<p>
								<?php $i = 0;
								foreach ($queue as $k => $v) : ?>
									<?php if ($i++ > 20) : ?>
										<?php echo '...'; ?>
										<?php break; ?>
									<?php endif; ?>
									<?php if (!is_array($v)) continue; ?>
									<?php if (!empty($v['_status'])) : ?><span class="litespeed-success"><?php endif; ?>
										<?php echo esc_html($v['url']); ?>
										<?php if (!empty($v['_status'])) : ?></span><?php endif; ?>
									<?php if ($pos = strpos($k, ' ')) echo ' (' . __('Vary Group', 'litespeed-cache') . ':' . esc_html(substr($k, 0, $pos)) . ')'; ?>
									<?php if ($v['is_mobile']) echo ' <span data-balloon-pos="up" aria-label="mobile">📱</span>'; ?>
									<br />
								<?php endforeach; ?>
							</p>
						</div>
						<?php if ($vpi_service_hot) : ?>
							<button class="button button-secondary" disabled>
								<?php echo sprintf(__('Run %s Queue Manually', 'litespeed-cache'), 'VPI'); ?>
								- <?php echo sprintf(__('Available after %d second(s)', 'litespeed-cache'), $vpi_service_hot); ?>
							</button>
						<?php else : ?>
							<a href="<?php echo Utility::build_url(Router::ACTION_VPI, VPI::TYPE_GEN); ?>" class="button litespeed-btn-success">
								<?php echo sprintf(__('Run %s Queue Manually', 'litespeed-cache'), 'VPI'); ?>
							</a>
						<?php endif; ?>
						<?php Doc::queue_issues(); ?>
					<?php endif; ?>
				</div>

			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_VPI_CRON; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Enable Viewport Images auto generation cron.', 'litespeed-cache'); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>