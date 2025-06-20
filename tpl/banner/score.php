<?php
/**
 * LiteSpeed Cache Performance Review Banner
 *
 * Displays a promotional banner showing page load time and PageSpeed score improvements.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$health_scores = Health::cls()->scores();

// Exit if speed is not significantly improved or score is reduced.
if ( $health_scores['speed_before'] <= $health_scores['speed_after'] * 2 || $health_scores['score_before'] >= $health_scores['score_after'] ) {
	return;
}

// Banner can be shown now.
$this->_promo_true = true;

if ( $check_only ) {
	return;
}

$ajax_url_promo = Utility::build_url(Core::ACTION_DISMISS, GUI::TYPE_DISMISS_PROMO, true, null, array( 'promo_tag' => $promo_tag ), true);
?>

<div class="litespeed-wrap notice notice-info litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-banner-promo-content"><?php esc_html_e( 'Thank You for Using the LiteSpeed Cache Plugin!', 'litespeed-cache' ); ?></h3>

		<div class="litespeed-row-flex litespeed-banner-promo-content litespeed-margin-left-remove litespeed-flex-wrap">
			<div class="litespeed-right50 litespeed-margin-bottom20">
				<h2 class="litespeed-text-grey litespeed-margin-bottom-remove litespeed-top10"><?php esc_html_e( 'Page Load Time', 'litespeed-cache' ); ?></h2>
				<hr class="litespeed-margin-bottom-remove" />
				<div class="litespeed-row-flex" style="margin-left: -10px;">
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-margin-y-remove"><?php esc_html_e( 'Before', 'litespeed-cache' ); ?></p>
						</div>
						<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-grey">
							<?php echo esc_html( $health_scores['speed_before'] ); ?><span class="litespeed-text-large">s</span>
						</div>
					</div>
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-margin-y-remove"><?php esc_html_e( 'After', 'litespeed-cache' ); ?></p>
						</div>
						<div class="litespeed-top10 litespeed-text-jumbo litespeed-success">
							<?php echo esc_html( $health_scores['speed_after'] ); ?><span class="litespeed-text-large">s</span>
						</div>
					</div>
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
								<?php esc_html_e( 'Improved by', 'litespeed-cache' ); ?>
							</p>
						</div>
						<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
							<?php echo esc_html( $health_scores['speed_improved'] ); ?><span class="litespeed-text-large">%</span>
						</div>
					</div>
				</div>
			</div>

			<?php if ( $health_scores['score_before'] < $health_scores['score_after'] ) : ?>
				<div class="litespeed-margin-bottom20">
					<h2 class="litespeed-text-grey litespeed-margin-bottom-remove litespeed-top10"><?php esc_html_e( 'PageSpeed Score', 'litespeed-cache' ); ?></h2>
					<hr class="litespeed-margin-bottom-remove" />
					<div class="litespeed-row-flex" style="margin-left: -10px;">
						<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
							<div>
								<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove"><?php esc_html_e( 'Before', 'litespeed-cache' ); ?></p>
							</div>
							<div class="litespeed-promo-score" style="margin-top: -5px;">
								<?php echo wp_kses( GUI::pie( esc_html( $health_scores['score_before'] ), 45, false, true, 'litespeed-pie-' . esc_attr( $this->get_cls_of_pagescore( $health_scores['score_before'] ) ) ), GUI::allowed_svg_tags() ); ?>
							</div>
						</div>
						<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
							<div>
								<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove"><?php esc_html_e( 'After', 'litespeed-cache' ); ?></p>
							</div>
							<div class="litespeed-promo-score" style="margin-top: -5px;">
								<?php echo wp_kses( GUI::pie( esc_html( $health_scores['score_after'] ), 45, false, true, 'litespeed-pie-' . esc_attr( $this->get_cls_of_pagescore( $health_scores['score_after'] ) ) ), GUI::allowed_svg_tags() ); ?>
							</div>
						</div>
						<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
							<div>
								<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
									<?php esc_html_e( 'Improved by', 'litespeed-cache' ); ?>
								</p>
							</div>
							<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
								<?php echo esc_html( $health_scores['score_improved'] ); ?><span class="litespeed-text-large">%</span>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="litespeed-row-flex litespeed-flex-wrap litespeed-margin-y5">
			<div class="litespeed-banner-description-padding-right-15">
				<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" target="_blank" rel="noopener" style="text-decoration: none;">
					<button class="button litespeed-btn-success litespeed-btn-mini">
						<?php esc_html_e( "Sure I'd love to review!", 'litespeed-cache' ); ?>
						⭐⭐⭐⭐⭐
					</button>
				</a>
				<button type="button" class="button litespeed-btn-primary litespeed-btn-mini" id="litespeed-promo-done"><?php esc_html_e( "I've already left a review", 'litespeed-cache' ); ?></button>
				<button type="button" class="button litespeed-btn-warning litespeed-btn-mini" id="litespeed-promo-later"><?php esc_html_e( 'Maybe later', 'litespeed-cache' ); ?></button>
			</div>
			<div>
				<p class="litespeed-text-small">
					<?php esc_html_e( 'Created with ❤️ by LiteSpeed team.', 'litespeed-cache' ); ?>
					<a href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank" rel="noopener"><?php esc_html_e( 'Support forum', 'litespeed-cache' ); ?></a> | <a href="https://www.litespeedtech.com/support" target="_blank" rel="noopener"><?php esc_html_e( 'Submit a ticket', 'litespeed-cache' ); ?></a>
				</p>
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
				'promo_tag' => 'score',
				'later'     => 1,
			)
		);
		?>
		<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'litespeed-cache' ); ?></span>
		<a href="<?php echo esc_url( $dismiss_url ); ?>" class="litespeed-notice-dismiss"><?php esc_html_e( 'Dismiss', 'litespeed-cache' ); ?></a>
	</div>
</div>

<script>
(function ($) {
	jQuery(document).ready(function () {
		/** Promo banner **/
		$('#litespeed-promo-done').on('click', function (event) {
			$('.litespeed-banner-promo-full').slideUp();
			$.get('<?php echo $ajax_url_promo;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>&done=1');
		});
		$('#litespeed-promo-later').on('click', function (event) {
			$('.litespeed-banner-promo-full').slideUp();
			$.get('<?php echo $ajax_url_promo;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>');
		});
	});
})(jQuery);
</script>