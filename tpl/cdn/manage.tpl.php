<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$cf_on = $this->conf(Base::O_CDN_CLOUDFLARE);
$cf_domain = $this->conf(Base::O_CDN_CLOUDFLARE_NAME) ?: '-';
$cf_zone = $this->conf(Base::O_CDN_CLOUDFLARE_ZONE) ?: '-';

$curr_status = CDN\Cloudflare::get_option(CDN\Cloudflare::ITEM_STATUS, array());

?>
<h3 class="litespeed-title"><?php echo __('QUIC.cloud', 'litespeed-cache'); ?></h3>

<p><?php echo __('To manage QUIC.cloud options, please visit', 'litespeed-cache'); ?>: <a href="<?php echo Cloud::cls()->qc_link(); ?>" target="_blank"><?php echo Cloud::cls()->qc_link(); ?></a></p>


<h3 class="litespeed-title"><?php echo __('Cloudflare', 'litespeed-cache'); ?></h3>

<?php if (!$cf_on) : ?>
	<div class="litespeed-callout notice notice-error inline">
		<h4><?php echo __('WARNING', 'litespeed-cache'); ?></h4>
		<p>
			<?php echo __('To enable the following functionality, turn ON Cloudflare API in CDN Settings.', 'litespeed-cache'); ?>
		</p>
	</div>
<?php endif; ?>

<p><?php echo __('Cloudflare Domain', 'litespeed-cache'); ?>: <code><?php echo $cf_domain; ?></code></p>
<p><?php echo __('Cloudflare Zone', 'litespeed-cache'); ?>: <code><?php echo $cf_zone; ?></code></p>

<p>
	<b><?php echo __('Development Mode', 'litespeed-cache'); ?>:</b>
	<a href="<?php echo Utility::build_url(Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_SET_DEVMODE_ON); ?>" class="button litespeed-btn-warning">
		<?php echo __('Turn ON', 'litespeed-cache'); ?>
	</a>
	<a href="<?php echo Utility::build_url(Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_SET_DEVMODE_OFF); ?>" class="button litespeed-btn-warning">
		<?php echo __('Turn OFF', 'litespeed-cache'); ?>
	</a>
	<a href="<?php echo Utility::build_url(Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_GET_DEVMODE); ?>" class="button litespeed-btn-success">
		<?php echo __('Check Status', 'litespeed-cache'); ?>
	</a>

	<?php if ($curr_status) : ?>
		<span class="litespeed-desc">
			<?php
			if (time() >= $curr_status['devmode_expired']) {
				$expired_at = date('m/d/Y H:i:s', $curr_status['devmode_expired'] + LITESPEED_TIME_OFFSET);
				$curr_status['devmode'] = 'OFF';
				echo sprintf(__('Current status is %1$s since %2$s.', 'litespeed-cache'), '<code>' . strtoupper($curr_status['devmode']) . '</code>', '<code>' . $expired_at . '</code>');
			} else {
				$expired_at = $curr_status['devmode_expired'] - time();
				$expired_at = Utility::readable_time($expired_at, 3600 * 3, true);
			?>
				<?php echo sprintf(__('Current status is %s.', 'litespeed-cache'), '<code>' . strtoupper($curr_status['devmode']) . '</code>'); ?>
				<?php echo sprintf(__('Development mode will be automatically turned off in %s.', 'litespeed-cache'), '<code>' . $expired_at . '</code>'); ?>
			<?php
			}
			?>
		</span>
	<?php endif; ?>

<p class="litespeed-desc">
	<?php echo __('Temporarily bypass Cloudflare cache. This allows changes to the origin server to be seen in realtime.', 'litespeed-cache'); ?>
	<?php echo __('Development Mode will be turned off automatically after three hours.', 'litespeed-cache'); ?>
	<a href="https://support.cloudflare.com/hc/en-us/articles/200168246" target="_blank"><?php echo __('Learn More', 'litespeed-cache'); ?></a>
</p>
</p>

<p>
	<b><?php echo __('Cloudflare Cache', 'litespeed-cache'); ?>:</b>
	<?php if (!$cf_on) : ?>
		<a href="#" class="button button-secondary disabled">
		<?php else : ?>
			<a href="<?php echo Utility::build_url(Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_PURGE_ALL); ?>" class="button litespeed-btn-danger">
			<?php endif; ?>
			<?php echo __('Purge Everything', 'litespeed-cache'); ?>
			</a>
</p>