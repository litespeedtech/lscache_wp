<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

/**
 * NOTE: Only show for single site
 */
if ( is_multisite() ) {
	return;
}

if ( $this->conf( Base::O_AUTO_UPGRADE ) ) {
	return;
}

$current = get_site_transient( 'update_plugins' );
if ( ! isset( $current->response[ Core::PLUGIN_FILE ] ) ) {
	return;
}

$last_check = empty( $this->_summary[ 'new_version.last_check' ] ) ? 0 : $this->_summary[ 'new_version.last_check' ];
// Check once in a half day
if ( time() - $last_check > 43200 ) {
	Admin_Display::save_summary( array( 'new_version.last_check' => time() ) );

	// Detect version
	$auto_v = Cloud::version_check( 'new_version_banner' );
	if ( ! empty( $auto_v[ 'latest' ] ) ) {
		Admin_Display::save_summary( array( 'new_version.v' => $auto_v[ 'latest' ] ) );
	}
	// After detect, don't show, just return and show next time
	return;
}

if ( ! isset( $this->_summary[ 'new_version.v' ] ) ) {
	return;
}

// Check if current version is newer than auto_v or not
if ( version_compare( Core::VER, $this->_summary[ 'new_version.v' ], '>=' ) ) {
	return;
}

//********** Can show now **********//

$this->_promo_true = true;

if ( $check_only ) {
	return;
}

?>
<div class="litespeed-wrap notice notice-success litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15"><?php echo __( 'LiteSpeed Cache', 'litespeed-cache' ); ?>: <?php echo __( 'New Version Available!', 'litespeed-cache' ); ?></h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-desciption-content">
					<?php echo sprintf( __( 'New release %s is available now.', 'litespeed-cache' ), 'v' . $this->_summary[ 'new_version.v' ] ); ?>
				</p>
			</div>
			<div class="litespeed-row-flex litespeed-banner-description">
				<div class="litespeed-banner-description-padding-right-15">
					<?php $url = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_UPGRADE ); ?>
					<a href="<?php echo $url; ?>" class="button litespeed-btn-success litespeed-btn-mini">
						<i class="dashicons dashicons-image-rotate">&nbsp;</i>
						 <?php echo __( 'Upgrade', 'litespeed-cache' ); ?>
					</a>
				</div>
				<div class="litespeed-banner-description-padding-right-15">
					<?php
						$cfg = array( Conf::TYPE_SET . '[' . Base::O_AUTO_UPGRADE . ']' => 1 );
						$url = Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, null, $cfg );
					?>
					<a href="<?php echo $url; ?>" class="button litespeed-btn-primary litespeed-btn-mini">
						<i class="dashicons dashicons-update">&nbsp;</i>
						<?php echo __( 'Turn On Auto Upgrade', 'litespeed-cache' ); ?>
					</a>
				</div>
				<div class="litespeed-banner-description-padding-right-15">
					<?php $url = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'new_version' ) ); ?>
					<a href="<?php echo $url; ?>" class="button litespeed-btn-warning litespeed-btn-mini">
						 <?php echo __( 'Maybe Later', 'litespeed-cache' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<div>
		<?php $dismiss_url = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'new_version', 'later' => 1 ) ); ?>
		<span class="screen-reader-text">Dismiss this notice.</span>
		<a href="<?php echo $dismiss_url; ?>" class="litespeed-notice-dismiss">X</a>
	</div>
</div>
