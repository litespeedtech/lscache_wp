<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$__cloud = Cloud::cls();
$cloud_summary = Cloud::get_summary();

?>

<div class="litespeed-dashboard-header">
	<h3 class="litespeed-dashboard-title"><?php echo __('QUIC.cloud', 'litespeed-cache'); ?></h3>
	<hr>
	<a class="button litespeed-btn-success litespeed-right10" href="<?php echo Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_SYNC_STATUS); ?>">
		<span class="dashicons dashicons-update"></span>
		<?php echo __('Sync', 'litespeed-cache'); ?>
		<span class="screen-reader-text"><?php echo __('Refresh QUIC.cloud status', 'litespeed-cache'); ?></span>
	</a>
	<?php echo __('To manage QUIC.cloud options, please visit', 'litespeed-cache'); ?>: <a href="<?php echo $__cloud->qc_link(); ?>" target="_blank" class="button litespeed-btn-warning">My QUIC.cloud</a>
</div>

<?php if (!$__cloud->activated()) : ?>
	<div class="litespeed-top20 litespeed-relative">
		<div class="litespeed-dashboard-unlock">
			<div>
				<h3 class="litespeed-dashboard-unlock-title"><strong class="litespeed-qc-text-gradient">Accelerate, Optimize, Protect</strong></h3>
				<p class="litespeed-dashboard-unlock-desc">Speed up your WordPress site even further with <strong>QUIC.cloud Online Services and CDN</strong>.</p>
				<p>Free monthly quota available.</p>
				<p><a class="button button-primary" href="<?php echo Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE); ?>"><span class="dashicons dashicons-yes"></span>Enable QUIC.cloud services</a></p>
				<p class="litespeed-dashboard-unlock-footer">
					<a href="https://www.quic.cloud/" target="_blank">Learn More about QUIC.cloud</a><br>
					QUIC.cloud services are not required
				</p>
			</div>
		</div>
	</div>

	<div class="litespeed-top20">
		<?php Doc::learn_more(
			Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE),
			__('Activate QUIC.cloud', 'litespeed-cache'),
			true,
			'button litespeed-btn-warning'
		); ?>
	</div>
<?php elseif (empty($cloud_summary['qc_activated']) || $cloud_summary['qc_activated'] != 'cdn') : ?>
	<div class="litespeed-top20">
		<p class="litespeed-text-bold litespeed-margin-bottom20">
			<?php Doc::learn_more(
				Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_ENABLE_CDN),
				__('Enable QUIC.cloud CDN', 'litespeed-cache'),
				true,
				'button litespeed-btn-success'
			); ?>
		</p>
		<p class="litespeed-margin-y5">
			<?php echo __('Best available WordPress performance', 'litespeed-cache'); ?>
		</p>
		<p class="litespeed-margin-y5">
			<?php echo sprintf(__('Globally fast TTFB, easy setup, and <a %s>more</a>!', 'litespeed-cache'), ' href="https://www.quic.cloud/quic-cloud-services-and-features/litespeed-cache-service/" target="_blank"'); ?>
		</p>
		<div class="litespeed-top10">
			<img src="<?php echo LSWCP_PLUGIN_URL; ?>assets/img/quic-cloud-logo.svg" alt="QUIC.cloud" width="45%" height="auto">
		</div>
	</div>
<?php else : ?>
	<?php echo $__cloud->load_qc_status_for_dash('cdn_dash'); ?>
<?php endif; ?>


<?php if ($__cloud->activated()) : ?>
	<div class="litespeed-top20 litespeed-flex">
		<?php Doc::learn_more(
			Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_RESET),
			__('Clear QUIC.cloud activation', 'litespeed-cache'),
			true,
			'button litespeed-btn-danger litespeed-align-right'
		); ?>
	</div>
<?php endif; ?>