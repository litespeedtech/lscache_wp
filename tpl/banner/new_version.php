<?php
/**
 * LiteSpeed Cache New Version Banner
 *
 * Displays a promotional banner for a new version of LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 * @note Only shown for single site installations.
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

// Exit if multisite or auto-upgrade is enabled.
if ( is_multisite() || $this->conf( Base::O_AUTO_UPGRADE ) ) {
	return;
}

$current = get_site_transient( 'update_plugins' );
if ( ! isset( $current->response[ Core::PLUGIN_FILE ] ) ) {
	return;
}

// Check for new version every 12 hours.
$last_check = empty( $this->_summary['new_version.last_check'] ) ? 0 : $this->_summary['new_version.last_check'];
if ( time() - $last_check > 43200 ) {
	GUI::save_summary( array( 'new_version.last_check' => time() ) );

	// Detect version
	$auto_v = Cloud::version_check( 'new_version_banner' );
	if ( ! empty( $auto_v['latest'] ) ) {
		GUI::save_summary( array( 'new_version.v' => $auto_v['latest'] ) );
	}
	// After detect, don't show, just return and show next time
	return;
}

if ( ! isset( $this->_summary['new_version.v'] ) || version_compare( Core::VER, $this->_summary['new_version.v'], '>=' ) ) {
	return;
}

// Banner can be shown now.
$this->_promo_true = true;

if ( $check_only ) {
	return;
}
?>

<div class="litespeed-wrap notice notice-success litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15">
			<?php esc_html_e( 'LiteSpeed Cache', 'litespeed-cache' ); ?>:
			<?php esc_html_e( 'New Version Available!', 'litespeed-cache' ); ?>
		</h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-description-content">
					<?php
					/* translators: %s: New version number */
					printf(
						esc_html__( 'New release %s is available now.', 'litespeed-cache' ),
						'v' . esc_html( $this->_summary['new_version.v'] )
					);
					?>
				</p>
			</div>
			<div class="litespeed-row-flex litespeed-banner-description">
				<div class="litespeed-banner-description-padding-right-15">
					<?php $url = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_UPGRADE ); ?>
					<a href="<?php echo esc_url( $url ); ?>" class="button litespeed-btn-success litespeed-btn-mini">
						<span class="dashicons dashicons-image-rotate"></span>
						<?php esc_html_e( 'Upgrade', 'litespeed-cache' ); ?>
					</a>
				</div>
				<div class="litespeed-banner-description-padding-right-15">
					<?php
					$cfg = array( Conf::TYPE_SET . '[' . Base::O_AUTO_UPGRADE . ']' => 1 );
					$url = Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, null, $cfg );
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="button litespeed-btn-primary litespeed-btn-mini">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Turn On Auto Upgrade', 'litespeed-cache' ); ?>
					</a>
				</div>
				<div class="litespeed-banner-description-padding-right-15">
					<?php $url = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'new_version' ) ); ?>
					<a href="<?php echo esc_url( $url ); ?>" class="button litespeed-btn-warning litespeed-btn-mini">
						<?php esc_html_e( 'Maybe Later', 'litespeed-cache' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<div>
		<?php
		$dismiss_url = Utility::build_url(
			Core::ACTION_DISMISS,
			GUI::TYPE_DISMISS_PROMO,
			false,
			null,
			array(
				'promo_tag' => 'new_version',
				'later'     => 1,
			)
		);
		?>
		<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'litespeed-cache' ); ?></span>
		<a href="<?php echo esc_url( $dismiss_url ); ?>" class="litespeed-notice-dismiss"><?php esc_html_e( 'Dismiss', 'litespeed-cache' ); ?></a>
	</div>
</div>