<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$__cloud = Cloud::cls();

// This will drop QS param `qc_res` and `token` also
$__cloud->update_setup_token_status();

$cloud_summary = Cloud::get_summary();

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

?>
<div class="litespeed-flex-container litespeed-column-with-boxes">
	<div class="litespeed-width-7-10 litespeed-image-optim-summary-wrapper">
		<div class="litespeed-image-optim-summary">
			<h3 class="litespeed-title">
				<?php echo __( 'Setup QUIC.cloud Account', 'litespeed-cache' ); ?>
			</h3>

			<?php if ( !$has_setup_token ) : ?>
				<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CDN_SETUP_LINK ), __( 'Link to QUIC.cloud', 'litespeed-cache' ), true, 'button litespeed-btn-warning' ); ?>
			<?php else: ?>
				<?php echo __( 'Account is linked!', 'litespeed-cache' ); ?>
			<?php endif; ?>

			<h3 class="litespeed-title-section">
				<?php echo __( 'Cdn Setup Status', 'litespeed-cache' ); ?>
			</h3>

			<?php
				$curr_status = '<span class="litespeed-label-success litespeed-label-dashboard">' . __('NOT RUNNING', 'litespeed-cache') . '</span>';
				$apply_btn_txt = __( 'Run CDN Setup', 'litespeed-cache' );
				$apply_btn_type = Cloud::TYPE_CDN_SETUP;
				$disabled = '';

				if (!$has_setup_token) {
					$disabled = 'disabled';
				} else if ( ! empty( $cdn_setup_err ) ) {
					$curr_status = '<span class="litespeed-label-danger litespeed-label-dashboard">' . __('PAUSED', 'litespeed-cache') . '</span>' . $cdn_setup_err;
				} else if ( $cdn_setup_ts > 0 ) {
					$curr_status = '<span class="litespeed-label-success litespeed-label-dashboard">' . __('RUNNING', 'litespeed-cache') . '</span>';
					$apply_btn_txt = __( 'Refresh CDN Setup Status', 'litespeed-cache' );
					$apply_btn_type = Cloud::TYPE_CDN_SETUP_STATUS;
				}
			?>
			<p>
				<?php echo __( 'Current Status', 'litespeed-cache' ); ?>
				<?php echo $curr_status; ?>
			</p>
			<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, $apply_btn_type ), $apply_btn_txt, true, 'button litespeed-btn-success ' . $disabled ); ?>

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
			<?php } ?>

			<h3 class="litespeed-title-section">
				<?php echo __( 'Action', 'litespeed-cache' ); ?>
			</h3>


			<?php if ( $has_setup_token ) : ?>
				<?php Doc::learn_more( Cloud::CLOUD_SERVER_DASH, __( 'Visit My Dashboard on QUIC.cloud', 'litespeed-cache' ), false, 'button litespeed-btn-success' ); ?>
				<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CDN_RESET ), __( 'Reset CDN Setup', 'litespeed-cache' ), true, 'button litespeed-btn-success ' ); ?>
			<?php endif; ?>
		</div>

	</div>


</div>
