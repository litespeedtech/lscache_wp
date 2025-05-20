<?php
/**
 * LiteSpeed Cache Promotion Banner
 *
 * Displays a promotional banner with news and installation options.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<div class="litespeed-wrap notice notice-success litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15"><?php echo esc_html( $this->_summary['news.title'] ); ?></h3>
		<div class="litespeed-banner-description" style="flex-direction: column;">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-description-content">
					<?php echo wp_kses_post( $this->_summary['news.content'] ); ?>
				</p>
			</div>
			<div class="litespeed-inline">
				<div class="litespeed-banner-description-padding-right-15 litespeed-margin-bottom10">
					<?php if ( ! empty( $this->_summary['news.plugin'] ) ) : ?>
						<?php $install_link = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_INSTALL_3RD, false, null, array( 'plugin' => $this->_summary['news.plugin'] ) ); ?>
						<a href="<?php echo esc_url( $install_link ); ?>" class="button litespeed-btn-success">
							<?php esc_html_e( 'Install', 'litespeed-cache' ); ?>
							<?php
							if ( ! empty( $this->_summary['news.plugin_name'] ) ) {
								echo esc_html( $this->_summary['news.plugin_name'] );
							}
							?>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $this->_summary['news.zip'] ) ) : ?>
						<?php $install_link = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_INSTALL_ZIP ); ?>
						<a href="<?php echo esc_url( $install_link ); ?>" class="button litespeed-btn-success">
							<?php esc_html_e( 'Install', 'litespeed-cache' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div>
		<?php $dismiss_url = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_DISMISS_RECOMMENDED ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'litespeed-cache' ); ?></span>
		<a href="<?php echo esc_url( $dismiss_url ); ?>" class="litespeed-notice-dismiss">X</a>
	</div>
</div>