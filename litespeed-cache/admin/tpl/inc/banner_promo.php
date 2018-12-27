<?php
if ( ! defined( 'WPINC' ) ) die ;


! defined( 'LITESPEED_DID_PROMO' ) && define( 'LITESPEED_DID_PROMO', true ) ;

if ( $check_only ) {
	return ;
}

?>
<div class="litespeed-wrap notice notice-info litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-banner-promo-content"><?php echo __( 'Thank You for Using the LiteSpeed Cache Plugin!', 'litespeed-cache' ) ; ?></h3>

			<div class="litespeed-row-flex litespeed-banner-promo-content litespeed-margin-left-remove litespeed-flex-wrap">
				<div class="litespeed-right50 litespeed-margin-bottom20">
					<h2 class="litespeed-text-grey litespeed-margin-bottom-remove litespeed-top10">Page Load Time</h2>
					<hr class="litespeed-margin-bottom-remove" />
						<div class="litespeed-row-flex" style="margin-left: -10px;">
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove">
										Before
									</p>
								</div>
									<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-grey">
										1.5<span class="litespeed-text-large">s</span>
									</div>

							</div>
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove">
										After
									</p>
								</div>
									<div class="litespeed-top10 litespeed-text-jumbo litespeed-success">
										0.05<span class="litespeed-text-large">s</span>
									</div>
							</div>
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
										Improved by
									</p>
								</div>
									<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
										50<span class="litespeed-text-large">%</span>
									</div>
							</div>
						</div>

				</div>

				<div class="litespeed-margin-bottom20">
					<h2 class="litespeed-text-grey litespeed-margin-bottom-remove litespeed-top10">PageSpeed Score</h2>
					<hr class="litespeed-margin-bottom-remove" />
						<div class="litespeed-row-flex" style="margin-left: -10px;">
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove">
										Before
									</h3>
								</div>
									<div style="margin-top:-5px;">
										<?php echo LiteSpeed_Cache_GUI::pie( 45, 45, false, true, 'litespeed-pie-warning' ) ; ?>
									</div>
							</div>
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove">
										After
									</h3>
								</div>
									<div style="margin-top:-5px;">
										<?php echo LiteSpeed_Cache_GUI::pie( 51, 45, false, true, 'litespeed-pie-success' ) ; ?>
									</div>
							</div>
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
										Improved by
									</p>
								</div>
									<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
										75<span class="litespeed-text-large">%</span>
									</div>
							</div>
						</div>

				</div>
			</div>

				<div class="litespeed-row-flex litespeed-flex-wrap litespeed-margin-y5">
					<div class="litespeed-banner-description-padding-right-15">

						<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" target="_blank" style="text-decoration: none;">
							<button class="litespeed-btn-success litespeed-btn-mini">
								<?php echo __( 'Sure I\'d love to review!', 'litespeed-cache' ) ; ?>
								⭐⭐⭐⭐⭐
							</button>
						</a>
							<button type="button" class="litespeed-btn-primary litespeed-btn-mini" id="litespeed-promo-done"> <?php echo __( 'I\'ve already left a review', 'litespeed-cache' ) ; ?></button>
							<button type="button" class="litespeed-btn-warning litespeed-btn-mini" id="litespeed-promo-later"> <?php echo __( 'Maybe later', 'litespeed-cache' ) ; ?></button>
					</div>
					<div>
						<p class="litespeed-text-small">
							<?php echo __( 'Created with ❤️ by LiteSpeed team.', 'litespeed-cache' ) ; ?>
							<?php echo sprintf(
								__( '<a %s>Support forum</a> | <a %s>Submit a ticket</a>', 'litespeed-cache' ),
								'href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank"',
								'href="https://www.litespeedtech.com/support" target="_blank"'
							) ; ?>
						</p>
					</div>
				</div>

		</div>

		<div>
	        <?php $dismiss_url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'banner_promo' ) ) ; ?>
	            <span class="screen-reader-text">Dismiss this notice.</span>
	                <a href="<?php echo $dismiss_url ; ?>" class="litespeed-notice-dismiss">
	                    X
	                </a>
	    </div>

</div>