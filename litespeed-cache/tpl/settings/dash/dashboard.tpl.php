<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$finished_percentage = 10;

$_summary = GUI::get_instance()->get_summary() ;
$_score = $_summary[ 'score.data' ] ;

// Format loading time
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

$speed_improved = ( $_score[ 'speed_before_cache' ] - $_score[ 'speed_after_cache' ] ) * 100 / $_score[ 'speed_before_cache' ] ;
if ( $speed_improved > 99 ) {
	$speed_improved = number_format( $speed_improved, 2 ) ;
}
else {
	$speed_improved = number_format( $speed_improved ) ;
}

// Format PageSpeed Score
$score_improved = ( $_score[ 'score_after_optm' ] - $_score[ 'score_before_optm' ] ) * 100 / $_score[ 'score_after_optm' ] ;
if ( $score_improved > 99 ) {
	$score_improved = number_format( $score_improved, 2 ) ;
}
else {
	$score_improved = number_format( $score_improved ) ;
}

$optm_summary = Img_Optm::get_instance()->summary_info() ;

?>

<div class="litespeed-dashboard">


	<div class="litespeed-dashboard-header">
		<h3 class="litespeed-dashboard-title"><?php echo __( 'Usage Statistics', 'litespeed-cache' ) ; ?></h3>
		<hr>
		<a href="#" target="_blank" class="litespeed-learn-more"><?php echo __( 'Learn More', 'litespeed-cache' );?></a>
	</div>

	<div class="litespeed-dashboard-stats-wrapper">

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title"><?php echo __( 'Image Optimization', 'litespeed-cache' ) ; ?></h3>

				<div class="litespeed-flex-container">
					<div class="litespeed-icon-vertical-middle">
						<?php echo GUI::pie( $finished_percentage, 70, true ) ; ?>
					</div>
					<div>
						<div class="litespeed-dashboard-stats">
							<h3><?php echo __('Used','litespeed-cache'); ?></h3>
							<p><strong>1234</strong> <span class="litespeed-desc"><?php echo sprintf( __( 'of %s', 'litespeed-cache' ), 3000 ) ; ?></span></p>
						</div>
					</div>
				</div>

			</div>
		</div>

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title"><?php echo __( 'CCSS', 'litespeed-cache' ) ; ?></h3>

				<div class="litespeed-flex-container">
					<div class="litespeed-icon-vertical-middle">
						<?php echo GUI::pie( $finished_percentage, 70, true ) ; ?>
					</div>
					<div>
						<div class="litespeed-dashboard-stats">
							<h3><?php echo __('Used','litespeed-cache'); ?></h3>
							<p><strong>1234</strong> <span class="litespeed-desc"><?php echo sprintf( __( 'of %s', 'litespeed-cache' ), 3000 ) ; ?></span></p>
						</div>
					</div>
				</div>

			</div>
		</div>

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title"><?php echo __( 'CDN Bandwidth', 'litespeed-cache' ) ; ?></h3>

				<div class="litespeed-flex-container">
					<div class="litespeed-icon-vertical-middle">
						<?php echo GUI::pie( $finished_percentage, 70, true ) ; ?>
					</div>
					<div>
						<div class="litespeed-dashboard-stats">
							<h3><?php echo __('Used','litespeed-cache'); ?></h3>
							<p><strong>1234 GB</strong> <span class="litespeed-desc"><?php echo sprintf( __( 'of %s', 'litespeed-cache' ), '3000 GB' ) ; ?></span></p>
						</div>
					</div>
				</div>
			</div>

		</div>

	</div>



	<div class="litespeed-dashboard-group">

		<hr>

		<div class="litespeed-flex-container">

			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Page Load Time', 'litespeed-cache' ) ; ?>
						<button type="button" class="button button-link litespeed-postbox-refresh" title="Update Page Load Time">
							<span class="dashicons dashicons-update"></span>
							<span class="screen-reader-text"><?php echo __('Refresh page load time', 'litespeed-cache'); ?></span>
						</button>
					</h3>

					<div>
						<div class="litespeed-row-flex" style="margin-left: -10px;">
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove">
										<?php echo __( 'Before', 'litespeed-cache' ) ; ?>
									</p>
								</div>
								<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-grey">
									<?php echo $speed_before_cache ; ?><span class="litespeed-text-large">s</span>
								</div>

							</div>
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove">
										<?php echo __( 'After', 'litespeed-cache' ) ; ?>
									</p>
								</div>
								<div class="litespeed-top10 litespeed-text-jumbo litespeed-success">
									<?php echo $speed_after_cache ; ?><span class="litespeed-text-large">s</span>
								</div>
							</div>
							<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
								<div>
									<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
										<?php echo __( 'Improved by', 'litespeed-cache' ) ; ?>
									</p>
								</div>
								<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
									<?php echo $speed_improved ; ?><span class="litespeed-text-large">%</span>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>

			<?php if ($_score[ 'score_before_optm' ] < $_score[ 'score_after_optm' ] ) : ?>
				<div class="postbox litespeed-postbox">
					<div class="inside">
						<h3 class="litespeed-title">
							<?php echo __( 'PageSpeed Score', 'litespeed-cache' ) ; ?>
							<button type="button" class="button button-link litespeed-postbox-refresh" title="Update Page Score">
								<span class="dashicons dashicons-update"></span>
								<span class="screen-reader-text"><?php echo __('Refresh page score', 'litespeed-cache'); ?></span>
							</button>
						</h3>

						<div>

							<div class="litespeed-margin-bottom20">
								<div class="litespeed-row-flex" style="margin-left: -10px;">
									<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
										<div>
											<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove">
												<?php echo __( 'Before', 'litespeed-cache' ) ; ?>
											</p>
										</div>
										<div class="litespeed-promo-score" style="margin-top:-5px;">
											<?php echo GUI::pie( $_score[ 'score_before_optm' ], 45, false, true, 'litespeed-pie-' . $this->get_cls_of_pagescore( $_score[ 'score_before_optm' ] ) ) ; ?>
										</div>
									</div>
									<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
										<div>
											<p class="litespeed-text-grey litespeed-text-center litespeed-margin-y-remove">
												<?php echo __( 'After', 'litespeed-cache' ) ; ?>
											</p>
										</div>
										<div class="litespeed-promo-score" style="margin-top:-5px;">
											<?php echo GUI::pie( $_score[ 'score_after_optm' ], 45, false, true, 'litespeed-pie-' . $this->get_cls_of_pagescore( $_score[ 'score_after_optm' ] ) ) ; ?>
										</div>
									</div>
									<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
										<div>
											<p class="litespeed-text-grey litespeed-margin-y-remove" style="white-space: nowrap;">
												<?php echo __( 'Improved by', 'litespeed-cache' ) ; ?>
											</p>
										</div>
										<div class="litespeed-top10 litespeed-text-jumbo litespeed-text-fern">
											<?php echo $score_improved ; ?><span class="litespeed-text-large">%</span>
										</div>
									</div>
								</div>

							</div>
						</div>

					</div>
				</div>
			<?php endif ; ?>

			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Cache Status', 'litespeed-cache' ) ; ?>
					</h3>


					<p>
						<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
						<?php echo __( 'cache', 'litespeed-cache' ) ; ?>
					</p>
					<p>
						<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
						<?php echo __( 'object cache', 'litespeed-cache' ) ; ?>
					</p>

				</div>
				<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
					<div>
						<a href="#">Manage Cache</a>
					</div>
				</div>
			</div>

			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Crawler Status', 'litespeed-cache' ) ; ?>
					</h3>

					<p>
						<code>3</code> <?php echo __( 'crawler crons', 'litespeed-cache' ) ; ?>
					</p>
					<p>
						<?php echo __( 'Current on crawler', 'litespeed-cache' ) ; ?>: <code>2</code>
					</p>
					<p>
						<?php echo __( 'Position' ); ?> <code>300/500</code>
					</p>

				</div>
				<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
					<div><a href="#">More details
					</a></div>
				</div>
			</div>

			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'LQIP Placeholder', 'litespeed-cache' ) ; ?>
					</h3>

					<p>
						<?php echo __( 'Placeholder generated', 'litespeed-cache' ) ; ?>: <code>300</code>
					</p>
					<p>
						<?php echo __( 'Last cron time', 'litespeed-cache' ) ; ?>: <code>08/16/19 12:53</code>
					</p>
					<p>
						<code>150</code> <?php echo __( 'is in queue', 'litespeed-cache' ); ?> <button type="button" class="button button-secondary button-small" title="<?php echo __( 'Click to trigger the cron manually', 'litespeed-cache' ); ?>"><?php echo __( 'Force cron', 'litespeed-cache' ); ?></button>
					</p>

				</div>
			</div>


			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Image Optimization Summary', 'litespeed-cache' ) ; ?>
						<a href="<?php echo Utility::build_url( Core::ACTION_IMG_OPTM, Img_Optm::TYPE_SYNC_DATA ) ; ?>" class="litespeed-postbox-refresh" title="<?php echo __( 'Update Status', 'litespeed-cache' ) ; ?>">
							<span class="dashicons dashicons-update"></span>
							<span class="screen-reader-text"><?php echo __('Update image optimization status', 'litespeed-cache'); ?></span>
						</a>
					</h3>


					<div class="litespeed-flex-container">
						<div class="litespeed-icon-vertical-middle">
							<?php echo GUI::pie( $finished_percentage, 70, true ) ; ?>
						</div>
						<div>
							<div class="litespeed-dashboard-stats">
								<h3><?php echo __('Used','litespeed-cache'); ?></h3>
								<p><strong>1234</strong> <span class="litespeed-desc"><?php echo sprintf( __( 'of %s', 'litespeed-cache' ), 3000 ) ; ?></span></p>
							</div>
						</div>
					</div>

					<p>
						<?php echo __( 'Total Reduction', 'litespeed-cache' ) ; ?>: <code><?php echo Utility::real_size( $optm_summary[ 'reduced' ] ) ; ?></code>
					</p>
					<p>
						<?php echo __( 'Images Pulled', 'litespeed-cache' ) ; ?>: <code><?php echo $optm_summary[ 'img_taken' ] ; ?></code>
					</p>
					<p>
						<?php echo __( 'Last Request', 'litespeed-cache' ) ; ?>: <code><?php echo Utility::readable_time( $optm_summary[ 'last_requested' ] ) ; ?></code>
					</p>
				</div>
			</div>


		</div>

	</div>


</div>