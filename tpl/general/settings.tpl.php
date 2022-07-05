<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$__cloud = Cloud::cls();

// This will drop QS param `qc_res` and `domain_hash` also
$__cloud->parse_qc_redir();

$cloud_summary = Cloud::get_summary();

$can_token = $__cloud->can_token();

$is_requesting = ! empty( $cloud_summary[ 'token_ts' ] ) && ( empty( $cloud_summary[ 'apikey_ts' ] ) || $cloud_summary[ 'token_ts' ] > $cloud_summary[ 'apikey_ts' ] );

$apply_btn_txt = __( 'Request Domain Key', 'litespeed-cache' );
if ( $this->conf( Base::O_API_KEY ) ) {
	$apply_btn_txt = __( 'Refresh Domain Key', 'litespeed-cache' );
	if ( $is_requesting ) {
		$apply_btn_txt = __( 'Waiting for Refresh', 'litespeed-cache' );
	}
}
elseif ( $is_requesting ) {
	$apply_btn_txt = __( 'Waiting for Approval', 'litespeed-cache' );
}

$apply_ts_txt = '';
if ( ! empty( $cloud_summary[ 'token_ts' ] ) ) {
	$apply_ts_txt .= ' ' . __( 'Requested', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $cloud_summary[ 'token_ts' ] ) . '</code>';
}
if ( ! empty( $cloud_summary[ 'apikey_ts' ] ) ) {
	$apply_ts_txt .= ' ' . __( 'Approved', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $cloud_summary[ 'apikey_ts' ] ) . '</code>';
}
if ( ! $can_token ) {
	$next_available_req = $cloud_summary[ 'token_ts' ] + Cloud::EXPIRATION_TOKEN - time();
	$apply_ts_txt .= ' ' . sprintf( __( 'Next available request time: <code>After %s</code>', 'litespeed-cache' ), Utility::readable_time( $next_available_req, 0, true ) );
}

?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php if ( ! $this->_is_multisite ) : ?>
		<?php require LSCWP_DIR . 'tpl/general/settings_inc.auto_upgrade.tpl.php'; ?>
	<?php endif; ?>

	<tr>
		<th>
			<?php $id = Base::O_API_KEY; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php if ( ! $is_requesting || $can_token ) : ?>
				<?php $this->build_input( $id ); ?>
			<?php else: ?>
				<?php $this->build_input( $id, null, null, 'text', true ); ?>
			<?php endif; ?>

			<?php if ( $can_token ) : ?>
				<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_GEN_KEY ), $apply_btn_txt, true, 'button litespeed-btn-success' ); ?>
			<?php else: ?>
				<?php Doc::learn_more( 'javascript:;', $apply_btn_txt, true, 'button disabled' ); ?>
			<?php endif; ?>
			<?php if ( $apply_ts_txt ) : ?>
				<span class="litespeed-desc"><?php echo $apply_ts_txt; ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $cloud_summary[ 'is_linked' ] ) ) : ?>
				<?php Doc::learn_more( Cloud::CLOUD_SERVER_DASH, __( 'Visit My Dashboard on QUIC.cloud', 'litespeed-cache' ), false, 'button litespeed-btn-success litespeed-right' ); ?>
			<?php elseif ( $__cloud->can_link_qc() ) : ?>
				<?php Doc::learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_LINK ), __( 'Link to QUIC.cloud', 'litespeed-cache' ), true, 'button litespeed-btn-warning litespeed-right' ); ?>
			<?php else: ?>
				<?php Doc::learn_more( 'javascript:;', __( 'Link to QUIC.cloud', 'litespeed-cache' ), true, 'button disabled litespeed-btn-warning litespeed-right' ); ?>
			<?php endif; ?>

			<?php if ( $is_requesting && $can_token ) : ?>
				<div class="litespeed-callout notice notice-error inline">
					<h4><?php echo __( 'Notice', 'litespeed-cache' ); ?>:</h4>
					<p><?php echo sprintf( __( 'There was a problem with retrieving your Domain Key. Please click the %s button to retry.', 'litespeed-cache' ), '<code>' . $apply_btn_txt . '</code>' ); ?></p>
					<p><?php echo __( 'There are two reasons why we might not be able to communicate with your domain:', 'litespeed-cache' ); ?>:</p>
					<p>1) <?php echo sprintf( __( 'The POST callback to %s failed.', 'litespeed-cache' ), '<code>' . home_url() . '/' . ( function_exists( 'rest_get_url_prefix' ) ? rest_get_url_prefix() : apply_filters( 'rest_url_prefix', 'wp-json' ) ) . '/litespeed/v1/token</code>' ); ?> </p>
					<p>2) <?php echo sprintf( __( 'Our %s was not allowlisted.', 'litespeed-cache' ), __( 'Current Online Server IPs', 'litespeed-cache' ) ); ?></p>
					<p><?php echo __( 'Please verify that your other plugins are not blocking REST API calls, allowlist our server IPs, or contact your server admin for assistance.', 'litespeed-cache' ); ?>:</p>
				</div>
			<?php endif; ?>

			<?php if ( $is_requesting ) : ?>
				<div class="litespeed-callout notice notice-warning inline">
					<h4><?php echo __( 'Notice', 'litespeed-cache' ); ?>:</h4>
					<p><?php echo __( 'Request submitted. Please wait, then refresh the page to see approval notification.', 'litespeed-cache' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( ! $this->conf( Base::O_API_KEY ) ) : ?>
				<div class="litespeed-callout notice notice-error inline">
					<h4><?php echo __( 'Warning', 'litespeed-cache' ); ?>:</h4>
					<p><?php echo sprintf( __( 'You must have %1$s first before linking to QUIC.cloud.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_API_KEY ) . '</code>' ) . ' See <a href="https://quic.cloud/terms/">Terms</a>.'; ?></p>
				</div>
			<?php elseif ( empty( $cloud_summary[ 'is_linked' ] ) ) : ?>
				<div class="litespeed-callout notice notice-warning inline">
					<h4><?php echo __( 'Notice', 'litespeed-cache' ); ?>:</h4>
					<p><?php echo sprintf( __( 'You must click the %s button if you wish to associate this site with a QUIC.cloud account.', 'litespeed-cache' ), '<code>' . __( 'Link to QUIC.cloud', 'litespeed-cache' ) . '</code>' ); ?></p>
					<p><?php Doc::learn_more( 'https://www.quic.cloud/faq/#do-i-need-to-register-on-quic-cloud-to-use-the-online-services', __( 'Benefits of linking to a QUIC.cloud account', 'litespeed-cache' ) ); ?></p>
				</div>
			<?php endif; ?>

			<div class="litespeed-desc">
				<?php echo __( 'A Domain Key is required for QUIC.cloud online services.', 'litespeed-cache' ); ?>

				<br />
				<?php if ( ! empty( $cloud_summary[ 'main_domain' ] ) ) : ?>
					<?php echo __( 'Main domain', 'litespeed-cache' ); ?>: <code><?php echo $cloud_summary[ 'main_domain' ]; ?></code>
				<?php else: ?>
					<font class="litespeed-warning">
						⚠️ <?php echo __( 'Main domain not generated yet', 'litespeed-cache' ); ?>
					</font>
				<?php endif; ?>

				<br />
				<?php Doc::notice_ips(); ?>
				<div class="litespeed-callout notice notice-success inline">
					<h4><?php echo __( 'Current Cloud Nodes in Service','litespeed-cache' ); ?>
						<a class="litespeed-right" href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CLEAR_CLOUD ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo __( 'Click to clear all nodes for further redetection.', 'litespeed-cache' ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to clear all cloud nodes?', 'litespeed-cache' ); ?>"><i class='litespeed-quic-icon'></i></a>
					</h4>
					<p>
						<?php
						$has_service = false;
						foreach ( Cloud::$SERVICES as $svc ) {
							if ( isset( $cloud_summary[ 'server.' . $svc ] ) ) {
								$has_service = true;
								echo '<p><b>Service:</b> <code>' . $svc . '</code> <b>Node:</b> <code>' . $cloud_summary[ 'server.' . $svc ] . '</code> <b>Connected Date:</b> <code>' . Utility::readable_time( $cloud_summary[ 'server_date.' . $svc ] ) . '</code></p>';
							}
						}
						if ( ! $has_service ) {
							echo __( 'No cloud services currently in use', 'litespeed-cache' );
						}
						?>
					</p>
				</div>
			</div>
		</td>
	</tr>

	<?php if ( ! $this->_is_multisite ) : ?>
		<?php require LSCWP_DIR . 'tpl/general/settings_inc.guest.tpl.php'; ?>
	<?php endif; ?>

	<tr>
		<th>
			<?php $id = Base::O_GUEST_OPTM; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'This option enables maximum optimization for Guest Mode visitors.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#guest-optimization', __( 'Please read all warnings before enabling this option.', 'litespeed-cache' ), false, 'litespeed-warning' ); ?>

				<?php
					$typeList = array();
					if ( $this->conf( Base::O_GUEST ) && ! $this->conf( Base::O_OPTM_UCSS ) ) {
						$typeList[] = 'UCSS';
					}
					if ( $this->conf( Base::O_GUEST ) && ! $this->conf( Base::O_OPTM_CSS_ASYNC ) ) {
						$typeList[] = 'CCSS';
					}
					if ( ! empty( $typeList ) ) {
						$theType = implode( '/', $typeList );
						echo '<br />';
						echo '<font class="litespeed-info">';
						echo '⚠️ ' . sprintf( __( 'Your %1s quota on %2s will still be in use.', 'litespeed-cache' ), $theType, 'QUIC.cloud' );
						echo '</font>';
					}
				?>

				<?php if ( ! $this->conf( Base::O_GUEST ) ) : ?>
					<br /><font class="litespeed-warning litespeed-left10">
					⚠️ <?php echo __( 'Notice', 'litespeed-cache' ); ?>: <?php echo sprintf( __( '%s must be turned ON for this setting to work.', 'litespeed-cache' ),  '<code>' . Lang::title( Base::O_GUEST ) . '</code>' ); ?>
					</font>
				<?php endif; ?>

				<?php if ( ! $this->conf( Base::O_CACHE_MOBILE ) ) : ?>
				<br /><font class="litespeed-danger litespeed-left10">
				⚠️ <?php echo __( 'Notice', 'litespeed-cache' ); ?>: <?php echo sprintf( __( 'You need to turn %s on to get maximum result.', 'litespeed-cache' ),  '<code>' . Lang::title( Base::O_CACHE_MOBILE ) . '</code>' ); ?>
				</font>
				<?php endif; ?>

				<?php if ( ! $this->conf( Base::O_IMG_OPTM_WEBP ) ) : ?>
				<br /><font class="litespeed-danger litespeed-left10">
				⚠️ <?php echo __( 'Notice', 'litespeed-cache' ); ?>: <?php echo sprintf( __( 'You need to turn %s on and finish all WebP generation to get maximum result.', 'litespeed-cache' ),  '<code>' . Lang::title( Base::O_IMG_OPTM_WEBP ) . '</code>' ); ?>
				</font>
				<?php endif; ?>

				<?php if ( ! $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE ) ) : ?>
				<br /><font class="litespeed-danger litespeed-left10">
				⚠️ <?php echo __( 'Notice', 'litespeed-cache' ); ?>: <?php echo sprintf( __( 'You need to turn %s on to get maximum result.', 'litespeed-cache' ),  '<code>' . Lang::title( Base::O_IMG_OPTM_WEBP_REPLACE ) . '</code>' ); ?>
				</font>
				<?php endif; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_SERVER_IP; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enter this site\'s IP address to allow cloud services directly call IP instead of domain name. This eliminates the overhead of DNS and CDN lookups.', 'litespeed-cache' ); ?>
				<br /><?php echo __('Your server IP', 'litespeed-cache'); ?>: <code id='litespeed_server_ip'>-</code> <a href="javascript:;" class="button button-link" id="litespeed_get_ip"><?php echo __('Check my public IP from', 'litespeed-cache'); ?> DoAPI.us</a>
				⚠️ <?php echo __( 'Notice', 'litespeed-cache' ); ?>: <?php echo __( 'the auto-detected IP may not be accurate if you have an additional outgoing IP set, or you have multiple IPs configured on your server.', 'litespeed-cache' ); ?>
				<br /><?php echo __( 'Please make sure this IP is the correct one for visiting your site.', 'litespeed-cache' ); ?>

				<?php $this->_validate_ip( $id ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_NEWS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Turn this option ON to show latest news automatically, including hotfixes, new releases, available beta versions, and promotions.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

</tbody></table>
