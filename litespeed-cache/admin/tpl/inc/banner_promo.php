<?php
if ( ! defined( 'WPINC' ) ) die ;

$last_check = empty( $_summary[ 'score.last_check' ] ) ? 0 : $_summary[ 'score.last_check' ] ;
// Check once per 10 days
if ( time() - $last_check > 864000 ) {
	$_summary[ 'score.last_check' ] = time() ;
	self::get_instance()->_save_summary( $_summary ) ;

	$score = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PAGESCORE, false, true, true, 60 ) ;
	$_summary[ 'score.data' ] = $score ;
	self::get_instance()->_save_summary( $_summary ) ;
	LiteSpeed_Cache_Log::debug( '[GUI] Saved score ', $score ) ;
	// After detect, don't show, just return and show next time
	return ;
}

if ( ! isset( $_summary[ 'score.data' ] ) ) {
	return ;
}

$_score = $_summary[ 'score.data' ] ;

if ( empty( $_score[ 'speed_before_cache' ] ) || empty( $_score[ 'speed_after_cache' ] )  || empty( $_score[ 'score_before_optm' ] )  || empty( $_score[ 'score_after_optm' ] ) ) {
	return ;
}
LiteSpeed_Cache_Log::debug( '[GUI] can show promo score ' ) ;
// If speed is not reduced half or score is larger
if ( $_score[ 'speed_before_cache' ] < $_score[ 'speed_after_cache' ] * 2 || $_score[ 'score_before_optm' ] <= $_score[ 'score_after_optm' ] ) {
	return ;
}

//********** Can show now **********//
$this->_promo_true = true ;

if ( $check_only ) {
	return ;
}

// Format nums
$speed_before_cache = $_score[ 'speed_before_cache' ] / 1000 ;
if ( $speed_before_cache < 0.01 ) {
	$speed_before_cache = 0.01 ;
}
$speed_before_cache = number_format( $speed_before_cache, 2 ) ;

$speed_after_cache = $_score[ 'speed_after_cache' ] / 1000 ;
if ( $speed_after_cache < 0.01 ) {
	$speed_after_cache = number_format( $speed_after_cache, 3 ) ;
}
else {
	$speed_after_cache = number_format( $speed_after_cache, 2 ) ;
}

$speed_improved = $_score[ 'speed_after_cache' ] / $_score[ 'speed_before_cache' ] ;

// todo: Done is done, dismiss should not be saved as done

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
										<?php echo $speed_before_cache ; ?><span class="litespeed-text-large">s</span>
									</div>

							</div>
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove">
										After
									</p>
								</div>
									<div class="litespeed-top10 litespeed-text-jumbo litespeed-success">
										<?php echo $speed_after_cache ; ?><span class="litespeed-text-large">s</span>
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
			<?php $dismiss_url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'banner_promo', 'later' => 1 ) ) ; ?>
			<span class="screen-reader-text">Dismiss this notice.</span>
			<a href="<?php echo $dismiss_url ; ?>" class="litespeed-notice-dismiss">X</a>
		</div>

</div>