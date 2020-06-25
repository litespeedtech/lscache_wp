<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$health_scores = Health::get_instance()->scores();

// If speed is not reduced half or score is larger
if ( $health_scores[ 'speed_before' ] < $health_scores[ 'speed_after' ] * 2 || $health_scores[ 'score_before' ] > $health_scores[ 'score_after' ] ) {
	return;
}

//********** Can show now **********//
$this->_promo_true = true;

if ( $check_only ) {
	return;
}

?>
<div class="litespeed-wrap notice notice-info litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-banner-promo-content"><?php echo __( 'Thank You for Using the LiteSpeed Cache Plugin!', 'litespeed-cache' ); ?></h3>

		<div class="litespeed-row-flex litespeed-banner-promo-content litespeed-margin-left-remove litespeed-flex-wrap">
			<div class="litespeed-right50 litespeed-margin-bottom20">
				<h2 class="litespeed-text-grey litespeed-margin-bottom-remove litespeed-top10"><?php echo __( 'Page Load Time', 'litespeed-cache' ); ?></h2>
				<hr class="litespeed-margin-bottom-remove" />
				<div class="litespeed-row-flex" style="margin-left: -10px;">
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-margin-y-remove">
                                <?php echo __( 'Before', 'litespeed-cache' ); ?>
							</p>
						</div>
						<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-grey">
							<?php echo $health_scores[ 'speed_before' ]; ?><span class="litespeed-text-large">s</span>
						</div>

					</div>
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-margin-y-remove">
                                <?php echo __( 'After', 'litespeed-cache' ); ?>
							</p>
						</div>
						<div class="litespeed-top10 litespeed-text-jumbo litespeed-success">
							<?php echo $health_scores[ 'speed_after' ]; ?><span class="litespeed-text-large">s</span>
						</div>
					</div>
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
                                <?php echo __( 'Improved by', 'litespeed-cache' ); ?>
							</p>
						</div>
						<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
							<?php echo $health_scores[ 'speed_improved' ]; ?><span class="litespeed-text-large">%</span>
						</div>
					</div>
				</div>

			</div>

			<?php if ( $health_scores[ 'score_before' ] < $health_scores[ 'score_after' ] ) : ?>
			<div class="litespeed-margin-bottom20">
				<h2 class="litespeed-text-grey litespeed-margin-bottom-remove litespeed-top10"><?php echo __( 'PageSpeed Score', 'litespeed-cache' ); ?></h2>
				<hr class="litespeed-margin-bottom-remove" />
				<div class="litespeed-row-flex" style="margin-left: -10px;">
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove">
                                <?php echo __( 'Before', 'litespeed-cache' ); ?>
							</p>
						</div>
						<div class="litespeed-promo-score" style="margin-top:-5px;">
							<?php echo GUI::pie( $health_scores[ 'score_before' ], 45, false, true, 'litespeed-pie-' . $this->get_cls_of_pagescore( $health_scores[ 'score_before' ] ) ); ?>
						</div>
					</div>
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove">
                                <?php echo __( 'After', 'litespeed-cache' ); ?>
							</p>
						</div>
						<div class="litespeed-promo-score" style="margin-top:-5px;">
							<?php echo GUI::pie( $health_scores[ 'score_after' ], 45, false, true, 'litespeed-pie-' . $this->get_cls_of_pagescore( $health_scores[ 'score_after' ] ) ); ?>
						</div>
					</div>
					<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
						<div>
							<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
                                <?php echo __( 'Improved by', 'litespeed-cache' ); ?>
							</p>
						</div>
						<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
							<?php echo $health_scores[ 'score_improved' ]; ?><span class="litespeed-text-large">%</span>
						</div>
					</div>
				</div>

			</div>
			<?php endif; ?>

		</div>

		<div class="litespeed-row-flex litespeed-flex-wrap litespeed-margin-y5">
			<div class="litespeed-banner-description-padding-right-15">

				<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" target="_blank" style="text-decoration: none;">
					<button class="button litespeed-btn-success litespeed-btn-mini">
						<?php echo __( 'Sure I\'d love to review!', 'litespeed-cache' ); ?>
						⭐⭐⭐⭐⭐
					</button>
				</a>
				<button type="button" class="button litespeed-btn-primary litespeed-btn-mini" id="litespeed-promo-done"> <?php echo __( 'I\'ve already left a review', 'litespeed-cache' ); ?></button>
				<button type="button" class="button litespeed-btn-warning litespeed-btn-mini" id="litespeed-promo-later"> <?php echo __( 'Maybe later', 'litespeed-cache' ); ?></button>
			</div>
			<div>
				<p class="litespeed-text-small">
					<?php echo __( 'Created with ❤️  by LiteSpeed team.', 'litespeed-cache' ); ?>
					<?php echo sprintf(
						__( '<a %s>Support forum</a> | <a %s>Submit a ticket</a>', 'litespeed-cache' ),
						'href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank"',
						'href="https://www.litespeedtech.com/support" target="_blank"'
					); ?>
				</p>
			</div>
		</div>
	</div>

	<div>
		<?php $dismiss_url = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'score', 'later' => 1 ) ); ?>
		<span class="screen-reader-text">Dismiss this notice.</span>
		<a href="<?php echo $dismiss_url; ?>" class="litespeed-notice-dismiss">X</a>
	</div>

</div>