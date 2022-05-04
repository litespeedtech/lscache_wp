<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$__cloud = Cloud::cls();

// This will drop QS param `qc_res` and `token` also
$__cloud->save_setuptoken();

$cloud_summary = Cloud::get_summary();

$cdn_setup_done_ts = 0;
if ( ! empty( $cloud_summary[ 'cdn_setup_done_ts' ] ) ) {
	$cdn_setup_done_ts = $cloud_summary[ 'cdn_setup_done_ts' ];
}

$has_setup_token = $__cloud->has_cdn_setup_token();

if ( ! empty( $cloud_summary[ 'cdn_setup_ts' ] ) ) {
	$cdn_setup_ts = $cloud_summary[ 'cdn_setup_ts' ];

	if ( !empty( $cloud_summary[ 'cdn_setup_err' ] ) ) {
		$cdn_setup_err = $cloud_summary[ 'cdn_setup_err' ];
	}

	if ($this->conf( Base::O_QC_NAMESERVERS )) {
		$nameservers = explode(',', $this->conf( Base::O_QC_NAMESERVERS ));
	}
} else {
	$cdn_setup_ts = 0;
}

$curr_status = '<span class="litespeed-label-warning litespeed-label-dashboard">' . __('NOT RUNNING', 'litespeed-cache') . '</span>';
$apply_btn_txt = __( 'Run CDN Setup', 'litespeed-cache' );
$apply_btn_type = Cloud::TYPE_CDN_SETUP_RUN;
$disabled = '';

if ($cdn_setup_done_ts) {
	$curr_status = '<span class="litespeed-label-success litespeed-label-dashboard">' . __('DONE', 'litespeed-cache') . '</span><p class="litespeed-description">Completed at ' . $cdn_setup_done_ts . '</p>';
	$disabled = 'disabled';
} else if (!$has_setup_token) {
	$disabled = 'disabled';
} else if ( ! empty( $cdn_setup_err ) ) {
	$curr_status = '<span class="litespeed-label-danger litespeed-label-dashboard">' . __('PAUSED', 'litespeed-cache') . '</span>' . $cdn_setup_err;
} else if ( $cdn_setup_ts > 0 ) {
	if ( isset($nameservers) ) {
		$curr_status = '<span class="litespeed-label-info litespeed-label-dashboard">' . __('VERIFYING', 'litespeed-cache') . '</span>';
		if ( isset( $cloud_summary[ 'cdn_verify_msg' ])) {
			$curr_status .= '<p>' .  __( 'Last Verify Result', 'litespeed-cache' ) . ': ' . $cloud_summary[ 'cdn_verify_msg' ] . '</p>';
		}
	} else {
		$curr_status = '<span class="litespeed-label-info litespeed-label-dashboard">' . __('RUNNING', 'litespeed-cache') . '</span>';
	}
	$apply_btn_txt = __( 'Refresh CDN Setup Status', 'litespeed-cache' );
	$apply_btn_type = Cloud::TYPE_CDN_SETUP_STATUS;
}

?>
<h3 class="litespeed-title">
	<?php echo __( 'Auto QUIC.cloud CDN Setup', 'litespeed-cache' ); ?>
</h3>
<p>
<?php echo __( 'This is a three step process for configuring your site to use QUIC.cloud CDN. This setup will perform the following actions', 'litespeed-cache' ) . ':'; ?>
</p>
<ol>
	<li><?php echo __( 'Set up a QUIC.cloud account.', 'litespeed-cache' ); ?></li>
	<li><?php echo __( 'Prepares the site for QUIC.cloud CDN and detects the DNS.', 'litespeed-cache' ); ?></li>
	<li><?php echo __( 'Provide the nameservers to use to enable the CDN.', 'litespeed-cache' ); ?></li>
</ol>

<p>
<?php echo __( 'After you set your nameservers, QUIC.cloud will detect the change and enable the CDN.', 'litespeed-cache' ); ?>
</p>
<br/>

<p>
<?php echo __( 'Notes', 'litespeed-cache' ) . ':'; ?>
</p>
<ul>
	<li>
		<?php echo __( 'QUIC.cloud CDN/DNS does not support DNSSEC.', 'litespeed-cache' ); ?>
		<?php echo __( 'If you have this enabled for your domain, you must disable DNSSEC to continue.', 'litespeed-cache' ); ?>
	</li>
	<li>
		<?php echo __( 'QUIC.cloud will detect most normal DNS entries.', 'litespeed-cache' ); ?>
		<?php echo __( 'If you have custom DNS records, it is possible that they are not detected.', 'litespeed-cache' ); ?>
		<?php echo __( 'Visit the my.quic.cloud dashboard to confirm your DNS Zone.', 'litespeed-cache' ); ?>
	</li>
</ul>

<h3 class="litespeed-title-section">
	<?php echo __( 'Setup QUIC.cloud Account', 'litespeed-cache' ); ?>
</h3>

<?php if ( $cdn_setup_done_ts || $has_setup_token ) : ?>
	<?php echo '<span class="litespeed-label-success litespeed-label-dashboard">' . __( 'Account is linked!', 'litespeed-cache' ) . '</span>'; ?>
<?php elseif ( ! empty( $cloud_summary[ 'is_linked' ] ) ) : ?>
	<p><?php echo __( 'Domain key and QUIC.cloud link detected.', 'litespeed-cache' ); ?></p>
	<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CDN_SETUP_NOLINK ), __( 'Begin QUIC.cloud CDN Setup', 'litespeed-cache' ), true, 'button litespeed-btn-warning' ); ?>
<?php else: ?>
	<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CDN_SETUP_LINK ), __( 'Link to QUIC.cloud', 'litespeed-cache' ), true, 'button litespeed-btn-warning' ); ?>
<?php endif; ?>

<h3 class="litespeed-title-section">
	<?php echo __( 'Cdn Setup Status', 'litespeed-cache' ); ?>
</h3>

<?php
?>
<h4>
	<?php echo __( 'Current Status', 'litespeed-cache' ); ?>
</h4>
<p>
	<?php echo $curr_status; ?>
</p>

<?php if ( !$cdn_setup_done_ts ) { ?>

	<h3 class="litespeed-title-section">
		<?php echo __( 'Nameservers', 'litespeed-cache' ); ?>
	</h3>

	<?php if ( isset( $nameservers ) ) { ?>
		<p>
			<?php echo __( 'Please update your domain registrar to use these custom nameservers:', 'litespeed-cache' ); ?>
		</p>
		<ul>
			<?php
			foreach ( $nameservers as $nameserver ) {
				echo '<li>' . $nameserver . '</li>';
			}
			?>
		</ul>
		<p>
			<?php echo __( 'QUIC.cloud will attempt to verify the DNS update.', 'litespeed-cache' ); ?>
			<?php echo __( 'If it does not verify in 24 hours time, the CDN setup will mark the verification as failed.', 'litespeed-cache' ); ?>
			<?php echo __( 'At that stage, you may re-start the verification process by pressing the Run CDN Setup button.', 'litespeed-cache' ); ?>
		</p>
	<?php } else { ?>
		<p>
			<?php echo __( 'This section will automatically populate once nameservers are configured for the site.', 'litespeed-cache' ); ?>
		</p>
	<?php } ?>

<?php } ?>

<h3 class="litespeed-title-section">
	<?php echo __( 'Action', 'litespeed-cache' ); ?>
</h3>

<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, $apply_btn_type ), $apply_btn_txt, true, 'button litespeed-btn-primary ' . $disabled ); ?>
<?php if ( $has_setup_token || $cdn_setup_done_ts ) : ?>
	<?php Doc::learn_more( Cloud::CLOUD_SERVER_DASH, __( 'Visit My Dashboard on QUIC.cloud', 'litespeed-cache' ), false, 'button litespeed-btn-success' ); ?>
	<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CDN_SETUP_RESET ), __( 'Reset CDN Setup', 'litespeed-cache' ), true, 'button litespeed-btn-success ' ); ?>
<?php endif; ?>
