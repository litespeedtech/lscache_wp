<?php

namespace LiteSpeed;

defined('WPINC') || exit;

?>
<div class="litespeed-dashboard-header">
	<h3 class="litespeed-dashboard-title"><?php echo __('QUIC.cloud', 'litespeed-cache'); ?></h3>
	<hr>
	<?php echo __('To manage QUIC.cloud options, please visit', 'litespeed-cache'); ?>: <a href="<?php echo Cloud::cls()->qc_link(); ?>" target="_blank" class="button litespeed-btn-warning">My QUIC.cloud</a>
</div>

<?php if (isset($setup_summary['cdn_dns_summary'])) { ?>
	<h4>
		<?php echo __('QUIC.cloud Detected Records Summary', 'litespeed-cache'); ?>
	</h4>
	<table class="wp-list-table widefat striped litespeed-width-auto litespeed-table-compact">
		<thead>
			<tr>
				<th>
					<?php echo __('Record Type', 'litespeed-cache'); ?>
				</th>
				<th>
					<?php echo __('Count', 'litespeed-cache'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($setup_summary['cdn_dns_summary']['types'] as $type => $cnt) {
				echo '<tr><td>' . wp_kses_post($type) . '</td><td>' . wp_kses_post($cnt) . '</td></tr>';
			} ?>
		</tbody>
	</table>

	<p>
		<?php echo __('Record names found', 'litespeed-cache') . ': ' . wp_kses_post($setup_summary['cdn_dns_summary']['names']); ?>
	</p>
	<p>
		<?php echo __('Is something missing?', 'litespeed-cache'); ?>
		<?php Doc::learn_more(
			Cloud::CLOUD_SERVER_DASH . '/dns/find/' . $dom,
			__('Review DNS records', 'litespeed-cache') . ' <span class="dashicons dashicons-external"></span>',
			false,
			'litespeed-link-with-icon'
		); ?>
	</p>
	<p>
		<?php echo __('Note: For 15 to 20 minutes after setup completes, browsers may issue a "not secure" warning for your site while QUIC.cloud generates your SSL certificate.', 'litespeed-cache'); ?>
	</p>
<?php } ?>

<?php if ($nameservers) { ?>

	<div>
		<?php Doc::learn_more(($disabled ? '#' : Utility::build_url(Router::ACTION_CDN_SETUP, $apply_btn_type)), $apply_btn_txt, true, 'button button-primary ' . $disabled); ?>
	</div>

	<h3 class="litespeed-title-section">
		<?php echo __('Nameservers', 'litespeed-cache'); ?>
	</h3>

	<p>
		<?php echo __('Please update your domain registrar to use these custom nameservers:', 'litespeed-cache'); ?>
	</p>
	<ul>
		<?php
		foreach ($nameservers as $nameserver) {
			echo '<li><strong>' . $nameserver . '</strong></li>';
		}
		?>
	</ul>
	<p>
		<?php echo __('QUIC.cloud will attempt to verify the DNS update.', 'litespeed-cache'); ?>
		<?php echo __('If it does not verify within 24 hours, the CDN setup will mark the verification as failed.', 'litespeed-cache'); ?>
		<?php echo __('At that stage, you may re-start the verification process by pressing the Run CDN Setup button.', 'litespeed-cache'); ?>
	</p>
<?php } ?>

<p>
	<?php echo __('After you set your nameservers/cname, QUIC.cloud will detect the change and automatically enable the CDN.', 'litespeed-cache'); ?>
</p>