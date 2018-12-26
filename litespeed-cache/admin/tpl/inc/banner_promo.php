<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<div class="litespeed-wrap notice notice-info litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title"><?php echo __( 'Thank You for Using the LiteSpeed Cache for WordPress Plugin!', 'litespeed-cache' ) ; ?></h3>

	<div class="litespeed-row-flex">
		<div>
			<h3 class="litespeed-text-lead litespeed-text-grey litespeed-text-center litespeed-margin-bottom-remove">Page Load</h3>
				<div class="litespeed-row-flex">
					<div class="litespeed-width-1-2 litespeed-padding-space">
						<div>
							<p class="litespeed-text-lead litespeed-text-center litespeed-text-grey litespeed-margin-y-remove">
								Before
							</p>
						</div>
							<h1 class="litespeed-margin-y-remove litespeed-text-center litespeed-text-grey">
								1.5<span class="litespeed-text-lead">s</span>
							</h1>

					</div>
					<div class="litespeed-width-1-2 litespeed-padding-space">
						<div>
							<p class="litespeed-text-lead litespeed-text-center litespeed-success litespeed-margin-y-remove">
								After
							</p>
						</div>
							<h1 class="litespeed-margin-y-remove litespeed-text-center litespeed-success">
								0.05<span class="litespeed-text-lead">s</span>
							</h1>
					</div>
				</div>
				<div>
					<p class="litespeed-text-small litespeed-text-center">
						Improved By: <span class="litespeed-success">50%</span>
					</p>
				</div>

		</div>

		<div>
			<h3 class="litespeed-text-lead litespeed-text-grey litespeed-text-center litespeed-margin-bottom-remove">Page Score</h3>
				<div class="litespeed-row-flex">
					<div class="litespeed-width-1-2 litespeed-padding-space">
						<div>
							<p class="litespeed-text-lead litespeed-text-center litespeed-text-grey litespeed-margin-y-remove">
								Before
							</h3>
						</div>
							<?php echo LiteSpeed_Cache_GUI::pie( 51, 50, false, true, 'litespeed-pie-success' ) ; ?>
					</div>
					<div class="litespeed-padding-space litespeed-width-1-2">
						<div>
							<p class="litespeed-text-lead litespeed-text-center litespeed-success litespeed-margin-y-remove">
								After
							</h3>
						</div>
							<?php echo LiteSpeed_Cache_GUI::pie( 51, 50, false, true, 'litespeed-pie-success' ) ; ?>
					</div>
				</div>
					<div>
						<p class="litespeed-text-small litespeed-text-center">
							Improved By: <span class="litespeed-success">75%</span>
						</p>
					</div>

		</div>

	</div>

		<div class="litespeed-row-flex">
			<div class="litespeed-banner-description-padding-right-15">
				<a class="litespeed-btn-success litespeed-btn-mini" href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" target="_blank">
					👍
					<?php echo __( 'Sure I\'d love to!', 'litespeed-cache' ) ; ?>
					⭐⭐⭐⭐⭐
				</a>
				<button type="button" class="litespeed-btn-primary litespeed-btn-mini" id="litespeed-promo-done">👌 <?php echo __( 'I\'ve already left a review', 'litespeed-cache' ) ; ?></button>
				<button type="button" class="litespeed-btn-warning litespeed-btn-mini" id="litespeed-promo-later">❤️ <?php echo __( 'Maybe later', 'litespeed-cache' ) ; ?></button>
			</div>
			<div>
				<p class="litespeed-text-small">
					<?php echo __( 'This plugin is created with ❤️ by LiteSpeed.', 'litespeed-cache' ) ; ?>
					<?php echo sprintf(
						__( '<a %s>Our support forum</a> | <a %s>Submitting a ticket with us</a>.', 'litespeed-cache' ),
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
	                    Dismiss
	                </a>
	    </div>

	</div>
</div>