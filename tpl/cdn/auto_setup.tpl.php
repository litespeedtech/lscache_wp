<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$__cdnsetup = Cdn_Setup::cls();

// This will drop QS param `qc_res` `domain_hash` and `token` also
$__cdnsetup->maybe_extract_token();

$cloud_linked = Cloud::get_summary( 'is_linked' );
$setup_summary = Cdn_Setup::get_summary();

$cdn_setup_done_ts = 0;
if ( ! empty( $setup_summary[ 'cdn_setup_done_ts' ] ) ) {
	$cdn_setup_done_ts = $setup_summary[ 'cdn_setup_done_ts' ];
}

$has_setup_token = $__cdnsetup->has_cdn_setup_token();

if ( ! empty( $setup_summary[ 'cdn_setup_ts' ] ) ) {
	$cdn_setup_ts = $setup_summary[ 'cdn_setup_ts' ];

	if ( !empty( $setup_summary[ 'cdn_setup_err' ] ) ) {
		$cdn_setup_err = $setup_summary[ 'cdn_setup_err' ];
	}

	if ($this->conf( Base::O_QC_NAMESERVERS )) {
		$nameservers = explode(',', $this->conf( Base::O_QC_NAMESERVERS ));
	}
} else {
	$cdn_setup_ts = 0;
}

$curr_status = '<span class="litespeed-desc">' . __('Not running', 'litespeed-cache') . '</span>';
$apply_btn_txt = __( 'Run CDN Setup', 'litespeed-cache' );
$apply_btn_type = Cdn_Setup::TYPE_RUN;
$disabled = '';
$dom = parse_url(home_url(), PHP_URL_HOST);

if ($cdn_setup_done_ts) {
	$curr_status = '<span class="litespeed-success dashicons dashicons-yes"></span> '
					. __('Done', 'litespeed-cache')
					. ' <span class="litespeed-desc litespeed-left10">'
					. sprintf( __('Completed at %s', 'litespeed-cache'), wp_date(get_option( 'date_format' ) . ' ' . get_option( 'time_format'), $cdn_setup_done_ts) )
					. '</span>';
	$disabled = 'disabled';
} else if (!$has_setup_token) {
	$disabled = 'disabled';
} else if ( ! empty( $cdn_setup_err ) ) {
	$curr_status = '<span class="litespeed-warning dashicons dashicons-controls-pause"></span> ' . __('Paused', 'litespeed-cache');
	$curr_status_subline = '<p class="litespeed-desc">' . $cdn_setup_err . '</p>';
} else if ( $cdn_setup_ts > 0 ) {
	if ( isset($nameservers) ) {
		$curr_status = '<span class="litespeed-primary dashicons dashicons-hourglass"></span> ' . __('Verifying, waiting for nameservers to be updated.', 'litespeed-cache') . ' ' . __('Click the refresh button below to refresh status.', 'litespeed-cache');
		if ( isset( $setup_summary[ 'cdn_verify_msg' ])) {
			$curr_status_subline = '<p class="litespeed-desc">' .  __( 'Last Verification Result', 'litespeed-cache' ) . ': ' . $setup_summary[ 'cdn_verify_msg' ] . '</p>';
		}
	} else {
		$curr_status = '<span class="litespeed-primary dashicons dashicons-hourglass"></span> ' . __('In Progress', 'litespeed-cache');
		$curr_status_subline = '<p class="litespeed-desc">' . __( 'You will receive an email upon status update.', 'litespeed-cache' ) . ' ' . __( 'This process may take several minutes.', 'litespeed-cache' ) . '</p>';
	}
	$apply_btn_txt = __( 'Refresh CDN Setup Status', 'litespeed-cache' );
	$apply_btn_type = Cdn_Setup::TYPE_STATUS;
}

?>
<h3 class="litespeed-title">
	<?php echo __( 'Auto QUIC.cloud CDN Setup', 'litespeed-cache' ); ?>
</h3>
<p>
<?php echo __( 'This is a three step process for configuring your site to use QUIC.cloud CDN with QUIC.cloud DNS. This setup will perform the following actions', 'litespeed-cache' ) . ':'; ?>
</p>
<ol>
	<li><?php echo __( 'Set up a QUIC.cloud account.', 'litespeed-cache' ); ?></li>
	<li><?php echo __( 'Prepare the site for QUIC.cloud CDN, detect the DNS, and create a DNS Zone.', 'litespeed-cache' ); ?></li>
	<li><?php echo __( 'Provide the nameservers necessary to enable the CDN.', 'litespeed-cache' ); ?></li>
	<li>
		<?php echo __( 'After successful DNS detection, QUIC.cloud will attempt to generate an SSL certificate and enable the CDN.', 'litespeed-cache' ); ?>
		<?php echo __( 'This last stage could take 15 to 20 minutes.', 'litespeed-cache' ); ?>
		<?php echo __( 'Your site will be available, but browsers may issue a "not secure" warning during this time.', 'litespeed-cache' ); ?>
	</li>
</ol>

<p>
<?php echo __( 'After you set your nameservers, QUIC.cloud will detect the change and automatically enable the CDN.', 'litespeed-cache' ); ?>
</p>

<p class="litespeed-desc">
<?php echo __( 'Notes', 'litespeed-cache' ) . ':'; ?>
</p>
<ul class="litespeed-desc">
	<li>
		<?php echo __( 'QUIC.cloud CDN/DNS does not support DNSSEC.', 'litespeed-cache' ); ?>
		<?php echo __( 'If you have this enabled for your domain, you must disable DNSSEC to continue.', 'litespeed-cache' ); ?>
	</li>
	<li>
		<?php echo __( 'This setup process will create a DNS zone on QUIC.cloud if one does not currently exist.', 'litespeed-cache' ); ?>
		<?php printf(__( 'If you prefer to use the CNAME setup, please <a %s>set up the CDN manually at QUIC.cloud</a>.', 'litespeed-cache' ),
					'href="https://quic.cloud/docs/onboarding/" target="_blank" class="litespeed-learn-more"'); ?>
	</li>
	<li>
		<?php echo __( 'QUIC.cloud will detect most normal DNS entries.', 'litespeed-cache' ); ?>
		<?php echo __( 'If you have custom DNS records, it is possible that they are not detected.', 'litespeed-cache' ); ?>
		<?php echo __( 'Visit your QUIC.cloud dashboard after the DNS Zone is set up to confirm your DNS zone.', 'litespeed-cache' ); ?>
	</li>
</ul>

<h3 class="litespeed-title-section">
	<?php echo __( 'Set up QUIC.cloud Account', 'litespeed-cache' ); ?>
</h3>

<?php if ( $cdn_setup_done_ts ) : ?>
	<p>
		<?php echo '<span class="litespeed-right10"><span class="litespeed-success dashicons dashicons-yes"></span> ' . __( 'Account is linked!', 'litespeed-cache' ) . '</span>'; ?>
		<p>
			<?php Doc::learn_more( Cloud::CLOUD_SERVER_DASH . '/dm/' . $dom . '/cdn/',
									__( 'Manage CDN', 'litespeed-cache' ) . ' <span class="dashicons dashicons-external"></span>',
									false,
									'litespeed-link-with-icon' ); ?>
			<?php Doc::learn_more( Cloud::CLOUD_SERVER_DASH . '/dns/find/' . $dom,
									__( 'Manage DNS Zone', 'litespeed-cache' ) . ' <span class="dashicons dashicons-external"></span>',
									false,
									'litespeed-link-with-icon' ); ?>
		</p>
	</p>
<?php elseif ( $has_setup_token ) : ?>
	<?php echo '<span class="litespeed-right10"><span class="litespeed-success dashicons dashicons-yes"></span> ' . __( 'Ready to run CDN setup.', 'litespeed-cache' ) . '</span>'; ?>
<?php elseif ( $cloud_linked ) : ?>
	<p><?php echo __( 'Domain key and QUIC.cloud link detected.', 'litespeed-cache' ); ?></p>
	<div><?php Doc::learn_more( Utility::build_url( Router::ACTION_CDN_SETUP, Cdn_Setup::TYPE_NOLINK ), __( 'Begin QUIC.cloud CDN Setup', 'litespeed-cache' ), true, 'button button-primary' ); ?></div>
<?php else: ?>
	<div><?php Doc::learn_more( Utility::build_url( Router::ACTION_CDN_SETUP, Cdn_Setup::TYPE_LINK ), __( 'Link to QUIC.cloud', 'litespeed-cache' ), true, 'button button-primary' ); ?></div>
<?php endif; ?>

<h3 class="litespeed-title-section">
	<?php echo __( 'CDN Setup Status', 'litespeed-cache' ); ?>
</h3>

<p>
	<span class="litespeed-inline"><?php echo $curr_status; ?></span>
</p>

<?php if ( isset ( $curr_status_subline ) ) { ?>
	<?php echo $curr_status_subline; ?>
<?php } ?>

<?php if ( !$cdn_setup_done_ts ) { ?>
	<?php if ( isset( $setup_summary[ 'cdn_dns_summary' ] ) ) { ?>
		<h4>
			<?php echo __( 'QUIC.cloud Detected Records Summary', 'litespeed-cache' ); ?>
		</h4>
		<table class="wp-list-table widefat striped litespeed-width-auto litespeed-table-compact">
			<thead>
				<tr>
					<th>
						<?php echo __( 'Record Type', 'litespeed-cache' ); ?>
					</th>
					<th>
						<?php echo __( 'Count', 'litespeed-cache' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $setup_summary[ 'cdn_dns_summary' ]['types'] as $type => $cnt ) {
					echo '<tr><td>' . $type . '</td><td>' . $cnt . '</td></tr>';
				} ?>
			</tbody>
		</table>

		<p>
			<?php echo __( 'Record names found', 'litespeed-cache' ) . ': ' . $setup_summary[ 'cdn_dns_summary' ]['names'] ; ?>
		</p>
		<p>
			<?php echo __( 'Is something missing?', 'litespeed-cache' ) ; ?>
			<?php Doc::learn_more( Cloud::CLOUD_SERVER_DASH . '/dns/find/' . $dom,
									__( 'Review DNS records', 'litespeed-cache' ) . ' <span class="dashicons dashicons-external"></span>',
									false,
									'litespeed-link-with-icon' ); ?>
		</p>
		<p>
		<?php echo __('Note: For 15 to 20 minutes after setup completes, browsers may issue a "not secure" warning for your site while QUIC.cloud generates your SSL certificate.', 'litespeed-cache'); ?>
		</p>
	<?php } ?>
<?php } ?>

<?php if ( !$cdn_setup_done_ts ) { ?>

	<div>
		<?php Doc::learn_more( ( $disabled ? '#' : Utility::build_url( Router::ACTION_CDN_SETUP, $apply_btn_type ) ), $apply_btn_txt, true, 'button button-primary ' . $disabled ); ?>
	</div>

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
				echo '<li><strong>' . $nameserver . '</strong></li>';
			}
			?>
		</ul>
		<p>
			<?php echo __( 'QUIC.cloud will attempt to verify the DNS update.', 'litespeed-cache' ); ?>
			<?php echo __( 'If it does not verify within 24 hours, the CDN setup will mark the verification as failed.', 'litespeed-cache' ); ?>
			<?php echo __( 'At that stage, you may re-start the verification process by pressing the Run CDN Setup button.', 'litespeed-cache' ); ?>
		</p>
	<?php } else { ?>
		<p>
			<?php echo __( 'This section will automatically populate once nameservers are configured for the site.', 'litespeed-cache' ); ?>
		</p>
	<?php } ?>

<?php } ?>

<?php if ( $has_setup_token || $cdn_setup_done_ts ) { ?>
	<?php $disabled = $cdn_setup_done_ts && ! $cloud_linked ? 'disabled' : ''; ?>
	<h3 class="litespeed-title-section">
		<?php echo __( 'Action', 'litespeed-cache' ); ?>
	</h3>
	<div>
		<p><?php echo __( 'The following actions are available:', 'litespeed-cache' ); ?></p>
		<p>
			<strong><?php echo __('Reset CDN Setup', 'litespeed-cache') . ': '; ?></strong>
			<?php echo __( 'Resets all LiteSpeed Cache plugin settings related to CDN setup back to the initial state and disables the CDN.', 'litespeed-cache' ); ?>
			<?php echo __( 'QUIC.cloud DNS settings are not changed.', 'litespeed-cache' ); ?>
			<?php echo __( 'This allows you to try Auto CDN setup again.', 'litespeed-cache' ); ?>
			<?php if ( $cdn_setup_done_ts ) : ?>
				<br/>
				<span class="litespeed-desc">
					<?php echo __( 'NOTE', 'litespeed-cache' ) . ': '; ?>
					<?php echo __( 'This action will not update anything on the QUIC.cloud servers.', 'litespeed-cache' ); ?>
				</span>
			<?php endif; ?>
		</p>
		<p>
			<strong><?php echo __('Delete QUIC.cloud data', 'litespeed-cache') . ': '; ?></strong>
			<?php echo __( 'Resets all LiteSpeed Cache plugin settings related to CDN setup back to the initial state and deletes the DNS Zone, if one exists for the domain.', 'litespeed-cache' ); ?>
			<?php echo __( 'This allows you to try Auto CDN setup again, or abandon the setup entirely.', 'litespeed-cache' ); ?>
			<br/>
			<span class="litespeed-desc">
				<?php echo __( 'NOTE', 'litespeed-cache' ) . ': '; ?>
				<?php echo __( 'This action is not available if there is no domain key, the domain is not linked, or the DNS Zone is in active use.', 'litespeed-cache' ); ?>
				<?php echo __( 'If you have not yet done so, please replace the QUIC.cloud nameservers at your domain registrar before proceeding. ', 'litespeed-cache' ); ?>
			</span>
		</p>
		<div>
			<a href="<?php echo Utility::build_url( Router::ACTION_CDN_SETUP, Cdn_Setup::TYPE_RESET ); ?>" data-litespeed-cfm="<?php echo __( 'Are you sure you want to reset CDN Setup?', 'litespeed-cache' ); ?>" class="button litespeed-btn-warning">
			<?php echo __( 'Reset CDN Setup', 'litespeed-cache' ); ?>
			</a>
			<a href="<?php echo ( $disabled ? '#' : Utility::build_url( Router::ACTION_CDN_SETUP, Cdn_Setup::TYPE_DELETE ) ); ?>" <?php if (empty($disabled)) : ?> data-litespeed-cfm="<?php echo __( 'Are you sure you want to delete QUIC.cloud data?', 'litespeed-cache' ); ?>"<?php endif; ?> class="button litespeed-btn-danger <?php echo $disabled; ?>" >
				<?php echo __( 'Delete QUIC.cloud data', 'litespeed-cache' ); ?>
			</a>
		</div>
	</div>
<?php } ?>
