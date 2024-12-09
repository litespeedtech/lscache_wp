<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$__cloud = Cloud::cls();

$cloud_summary = Cloud::get_summary();

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __('General Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/general/'); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<?php if (!$this->_is_multisite) : ?>
			<?php require LSCWP_DIR . 'tpl/general/settings_inc.auto_upgrade.tpl.php'; ?>
		<?php endif; ?>

		<?php if (!$this->_is_multisite) : ?>
			<?php require LSCWP_DIR . 'tpl/general/settings_inc.guest.tpl.php'; ?>
		<?php endif; ?>

		<tr>
			<th>
				<?php $id = Base::O_GUEST_OPTM; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<span class="litespeed-danger litespeed-text-bold">
						🚨
						<?php echo __('This option enables maximum optimization for Guest Mode visitors.', 'litespeed-cache'); ?>
						<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/general/#guest-optimization', __('Please read all warnings before enabling this option.', 'litespeed-cache'), false, 'litespeed-danger'); ?>
					</span>

					<?php
					$typeList = array();
					if ($this->conf(Base::O_GUEST) && !$this->conf(Base::O_OPTM_UCSS)) {
						$typeList[] = 'UCSS';
					}
					if ($this->conf(Base::O_GUEST) && !$this->conf(Base::O_OPTM_CSS_ASYNC)) {
						$typeList[] = 'CCSS';
					}
					if (!empty($typeList)) {
						$theType = implode('/', $typeList);
						echo '<br />';
						echo '<font class="litespeed-info">';
						echo '⚠️ ' . sprintf(__('Your %1s quota on %2s will still be in use.', 'litespeed-cache'), $theType, 'QUIC.cloud');
						echo '</font>';
					}
					?>

					<?php if (!$this->conf(Base::O_GUEST)) : ?>
						<br />
						<font class="litespeed-warning litespeed-left10">
							⚠️ <?php echo __('Notice', 'litespeed-cache'); ?>: <?php echo sprintf(__('%s must be turned ON for this setting to work.', 'litespeed-cache'),  '<code>' . Lang::title(Base::O_GUEST) . '</code>'); ?>
						</font>
					<?php endif; ?>

					<?php if (!$this->conf(Base::O_CACHE_MOBILE)) : ?>
						<br />
						<font class="litespeed-primary litespeed-left10">
							⚠️ <?php echo __('Notice', 'litespeed-cache'); ?>: <?php echo sprintf(__('You need to turn %s on to get maximum result.', 'litespeed-cache'),  '<code>' . Lang::title(Base::O_CACHE_MOBILE) . '</code>'); ?>
						</font>
					<?php endif; ?>

					<?php if (!$this->conf(Base::O_IMG_OPTM_WEBP)) : ?>
						<br />
						<font class="litespeed-primary litespeed-left10">
							⚠️ <?php echo __('Notice', 'litespeed-cache'); ?>: <?php echo sprintf(__('You need to turn %s on and finish all WebP generation to get maximum result.', 'litespeed-cache'),  '<code>' . Lang::title(Base::O_IMG_OPTM_WEBP) . '</code>'); ?>
						</font>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_SERVER_IP; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_input($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Enter this site\'s IP address to allow cloud services directly call IP instead of domain name. This eliminates the overhead of DNS and CDN lookups.', 'litespeed-cache'); ?>
					<br /><?php echo __('Your server IP', 'litespeed-cache'); ?>: <code id='litespeed_server_ip'>-</code> <a href="javascript:;" class="button button-link" id="litespeed_get_ip"><?php echo __('Check my public IP from', 'litespeed-cache'); ?> DoAPI.us</a>
					⚠️ <?php echo __('Notice', 'litespeed-cache'); ?>: <?php echo __('the auto-detected IP may not be accurate if you have an additional outgoing IP set, or you have multiple IPs configured on your server.', 'litespeed-cache'); ?>
					<br /><?php echo __('Please make sure this IP is the correct one for visiting your site.', 'litespeed-cache'); ?>

					<?php $this->_validate_ip($id); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_NEWS; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Turn this option ON to show latest news automatically, including hotfixes, new releases, available beta versions, and promotions.', 'litespeed-cache'); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>

<?php $this->form_end(); ?>