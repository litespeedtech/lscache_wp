<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$__cloud = Cloud::cls();
$__cloud->finish_qc_activation('cdn');
$cloud_summary = Cloud::get_summary();
?>

<div class="litespeed-flex-container litespeed-column-with-boxes">

	<div class="litespeed-width-7-10 litespeed-column-left litespeed-cdn-summary-wrapper">
		<div class="litespeed-column-left-inside">

			<h3>
				<?php if ($__cloud->activated()) : ?>
					<a class="button button-small litespeed-right litespeed-learn-more" href="<?php echo Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_SYNC_STATUS); ?>">
						<span class="dashicons dashicons-update"></span> <?php echo __('Refresh Status', 'litespeed-cache'); ?>
					</a>
				<?php endif; ?>
				<span class="litespeed-quic-icon"></span> <?php echo __('QUIC.cloud CDN Status Overview', 'litespeed-cache'); ?>
			</h3>
			<p class="litespeed-desc"><?php echo __('Check the status of your most important settings and the health of your CDN setup here.', 'litespeed-cache'); ?></p>

			<?php if (!$__cloud->activated()) : ?>
				<div class="litespeed-dashboard-unlock litespeed-dashboard-unlock--inline">
					<div>
						<h3 class="litespeed-dashboard-unlock-title"><strong class="litespeed-qc-text-gradient"><?php echo __('Accelerate, Optimize, Protect', 'litespeed-cache'); ?></strong></h3>
						<p class="litespeed-dashboard-unlock-desc"><?php echo __('Speed up your WordPress site even further with <strong>QUIC.cloud Online Services and CDN</strong>.', 'litespeed-cache'); ?></p>
						<p><?php echo __('Free monthly quota available.', 'litespeed-cache'); ?></p>
						<p><a class="button button-primary" href="<?php echo Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE, false, null, array('ref' => 'cdn')); ?>"><span class="dashicons dashicons-yes"></span><?php echo __('Enable QUIC.cloud services', 'litespeed-cache'); ?></a></p>
						<p class="litespeed-dashboard-unlock-footer">
							<?php echo __('QUIC.cloud provides CDN and online optimization services, and is not required. You may use many features of this plugin without QUIC.cloud.', 'litespeed-cache'); ?><br>
							<a href="https://www.quic.cloud/" target="_blank"><?php echo __('Learn More about QUIC.cloud', 'litespeed-cache'); ?></a>
						</p>
					</div>
				</div>
			<?php elseif (empty($cloud_summary['qc_activated']) || $cloud_summary['qc_activated'] != 'cdn') : ?>
				<div class="litespeed-top20">
					<?php if (!empty($cloud_summary['qc_activated']) && $cloud_summary['qc_activated'] == 'linked') : ?>
						<p><?php echo __('QUIC.cloud CDN is currently <strong>fully disabled</strong>.', 'litespeed-cache'); ?></p>
					<?php else : ?>
						<p><?php echo __('QUIC.cloud CDN is <strong>not available</strong> for anonymous (unlinked) users.', 'litespeed-cache'); ?></p>
					<?php endif; ?>
					<p>
						<?php
						$btn_title = __('Link & Enable QUIC.cloud CDN', 'litespeed-cache');
						if (!empty($cloud_summary['qc_activated']) && $cloud_summary['qc_activated'] == 'linked') {
							$btn_title = __('Enable QUIC.cloud CDN', 'litespeed-cache');
						}
						?>
						<?php Doc::learn_more(
							Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_ENABLE_CDN, false, null, array('ref' => 'cdn')),
							'<span class="dashicons dashicons-yes"></span>' . $btn_title,
							true,
							'button button-primary litespeed-button-cta'
						); ?>
					</p>
					<h3 class="litespeed-title-section"><?php echo __('Content Delivery Network Service', 'litespeed-cache'); ?></h3>
					<p class="litespeed-text-md">Serve your visitors fast <strong class="litespeed-qc-text-gradient"><?php echo __('no matter where they live.', 'litespeed-cache'); ?></strong>
					<p>
						<?php echo sprintf(__('Best available WordPress performance, globally fast TTFB, easy setup, and <a %s>more</a>!', 'litespeed-cache'), ' href="https://www.quic.cloud/quic-cloud-services-and-features/litespeed-cache-service/" target="_blank"'); ?>
					</p>
				</div>
			<?php else : ?>
				<?php echo $__cloud->load_qc_status_for_dash('cdn_dash'); ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="litespeed-width-3-10 litespeed-column-right">

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title">
					<?php echo __('QUIC.cloud CDN Options', 'litespeed-cache'); ?>
				</h3>
				<?php if (!empty($cloud_summary['partner']) && !empty($cloud_summary['partner']['disable_qc_login'])) : ?>
					<?php if (!empty($cloud_summary['partner']['logo'])) : ?>
						<?php if (!empty($cloud_summary['partner']['url'])) : ?>
							<a href="<?php echo $cloud_summary['partner']['url']; ?>" target="_blank"><img src="<?php echo $cloud_summary['partner']['logo']; ?>" alt="<?php echo $cloud_summary['partner']['name']; ?>"></a>
						<?php else : ?>
							<img src="<?php echo $cloud_summary['partner']['logo']; ?>" alt="<?php echo $cloud_summary['partner']['name']; ?>">
						<?php endif; ?>
					<?php elseif (!empty($cloud_summary['partner']['name'])) : ?>
						<?php if (!empty($cloud_summary['partner']['url'])) : ?>
							<a href="<?php echo $cloud_summary['partner']['url']; ?>" target="_blank"><span class="postbox-partner-name"><?php echo $cloud_summary['partner']['name']; ?></span></a>
						<?php else : ?>
							<span class="postbox-partner-name"><?php echo $cloud_summary['partner']['name']; ?></span>
						<?php endif; ?>
					<?php endif; ?>

					<?php if (!$__cloud->activated()) : ?>
						<p><?php echo __('To manage your QUIC.cloud options, go to your hosting provider\'s portal.', 'litespeed-cache'); ?></p>
					<?php else : ?>
						<p><?php echo __('To manage your QUIC.cloud options, please contact your hosting provider.', 'litespeed-cache'); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<?php if (!$__cloud->activated()) : ?>
						<p><?php echo __('To manage your QUIC.cloud options, go to QUIC.cloud Dashboard.', 'litespeed-cache'); ?></p>
						<p class="litespeed-top20"><button type="button" class="button button-primary disabled"><?php echo __('Link to QUIC.cloud', 'litespeed-cache'); ?> <span class="dashicons dashicons-external"></span></button></p>
					<?php elseif ($cloud_summary['qc_activated'] == 'anonymous') : ?>
						<p><?php echo __('You are currently using services as an anonymous user. To manage your QUIC.cloud options, use the button below to create an account and link to the QUIC.cloud Dashboard.', 'litespeed-cache'); ?></p>
						<p class="litespeed-top20"><a href="<?php echo $__cloud->qc_link(); ?>" target="qc" class="button button-<?php echo ((empty($cloud_summary['qc_activated']) || $cloud_summary['qc_activated'] != 'cdn') ? 'secondary' : 'primary'); ?>"><?php echo __('Link to QUIC.cloud', 'litespeed-cache'); ?> <span class="dashicons dashicons-external"></span></a></p>
					<?php elseif ($cloud_summary['qc_activated'] == 'linked') : ?>
						<p class="litespeed-top20"><a href="<?php echo $__cloud->qc_link(); ?>" target="qc" class="button button-<?php echo ((empty($cloud_summary['qc_activated']) || $cloud_summary['qc_activated'] != 'cdn') ? 'secondary' : 'primary'); ?>"><?php echo __('My QUIC.cloud Dashboard', 'litespeed-cache'); ?> <span class="dashicons dashicons-external"></span></a></p>
					<?php else : ?>
						<p><?php echo __('To manage your QUIC.cloud options, go to QUIC.cloud Dashboard.', 'litespeed-cache'); ?></p>
						<p class="litespeed-top20"><a href="<?php echo $__cloud->qc_link(); ?>" target="qc" class="button button-<?php echo ((empty($cloud_summary['qc_activated']) || $cloud_summary['qc_activated'] != 'cdn') ? 'secondary' : 'primary'); ?>"><?php echo __('My QUIC.cloud Dashboard', 'litespeed-cache'); ?> <span class="dashicons dashicons-external"></span></a></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>


		<?php $promo_mini = $__cloud->load_qc_status_for_dash('promo_mini'); ?>
		<?php if ($promo_mini) : ?>
			<?php echo $promo_mini; ?>
		<?php endif; ?>


	</div>

</div>