<?php
/**
 * LiteSpeed Cache Developer Version Banner
 *
 * Displays a promotional banner for a new developer version of LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<div class="litespeed-wrap notice notice-warning litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15">
			<?php esc_html_e( 'LiteSpeed Cache', 'litespeed-cache' ); ?>:
			<?php esc_html_e( 'New Developer Version Available!', 'litespeed-cache' ); ?>
		</h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-description-content">
					<?php
					/* translators: %s: Developer version number */
					printf(
						esc_html__( 'New developer version %s is available now.', 'litespeed-cache' ),
						'v' . esc_html( $this->_summary['version.dev'] )
					);
					?>
				</p>
			</div>
			<div class="litespeed-row-flex litespeed-banner-description">
				<div class="litespeed-banner-description-padding-right-15">
					<?php $url = Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_BETA_TEST, false, null, array( Debug2::BETA_TEST_URL => 'dev' ) ); ?>
					<a href="<?php echo esc_url( $url ); ?>" class="button litespeed-btn-success litespeed-btn-mini">
						<span class="dashicons dashicons-image-rotate"></span>
						<?php esc_html_e( 'Upgrade', 'litespeed-cache' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>