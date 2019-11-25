<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$lscache_stats = GUI::get_instance()->lscache_stats();

$_summary = GUI::get_summary() ;
if ( ! empty( $_summary[ 'score.data' ] ) ) {
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
}

// Image related info
$optm_summary = Img_Optm::get_summary() ;
$img_count = Img_Optm::get_instance()->img_count() ;
if ( ! empty( $img_count[ 'groups_all' ] ) ) {
	$img_gathered_percentage = 100 - floor( $img_count[ 'groups_not_gathered' ] * 100 / $img_count[ 'groups_all' ] ) ;
}
else {
	$img_gathered_percentage = 0 ;
}

if ( ! empty( $img_count[ 'imgs_gathered' ] ) ) {
	$img_finished_percentage = 100 - floor( $img_count[ 'img.' . Img_Optm::STATUS_RAW ] * 100 / $img_count[ 'imgs_gathered' ] ) ;
}
else {
	$img_finished_percentage = 0 ;
}

$cloud_summary = Cloud::get_summary();
?>

<div class="litespeed-dashboard">


	<div class="litespeed-dashboard-header">
		<h3 class="litespeed-dashboard-title">
			<?php echo __( 'Usage Statistics', 'litespeed-cache' ); ?>
			<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_SYNC_USAGE ); ?>">
				<span class="dashicons dashicons-update"></span>
				<span class="screen-reader-text"><?php echo __( 'Sync data from Cloud', 'litespeed-cache' ); ?></span>
			</a>
		</h3>
		<hr>
		<a href="#" target="_blank" class="litespeed-learn-more"><?php echo __( 'Learn More', 'litespeed-cache' );?></a>
	</div>

	<div class="litespeed-dashboard-stats-wrapper">
		<?php
		$cat_list = array(
			'img_optm'	=> __( 'Image Optimization', 'litespeed-cache' ),
			'ccss'		=> __( 'CCSS', 'litespeed-cache' ),
			'cdn'		=> __( 'CDN Bandwidth', 'litespeed-cache' ),
			'lqip'		=> __( 'LQIP', 'litespeed-cache' ),
		);
		if ( ! Conf::val( Base::O_MEDIA_PLACEHOLDER_LQIP ) ) {
			$cat_list[ 'placeholder' ] = __( 'Placeholder', 'litespeed-cache' );
		}

		foreach ( $cat_list as $svc => $title ) :
			$finished_percentage = 0;
			$used = $quota = $pag_used = $pag_total = '-';
			$pag_width = 0;
			if ( ! empty( $cloud_summary[ 'usage.' . $svc ] ) ) {
				$finished_percentage = floor( $cloud_summary[ 'usage.' . $svc ][ 'used' ] * 100 / $cloud_summary[ 'usage.' . $svc ][ 'quota' ] );
				$used = $cloud_summary[ 'usage.' . $svc ][ 'used' ];
				$quota = $cloud_summary[ 'usage.' . $svc ][ 'quota' ];
				$pag_used = ! empty( $cloud_summary[ 'usage.' . $svc ][ 'pag_used' ] ) ? $cloud_summary[ 'usage.' . $svc ][ 'pag_used' ] : 0;
				$pag_bal = ! empty( $cloud_summary[ 'usage.' . $svc ][ 'pag_bal' ] ) ? $cloud_summary[ 'usage.' . $svc ][ 'pag_bal' ] : 0;
				$pag_total = $pag_used + $pag_bal;

				if ( $pag_total ) {
					$pag_width = round( $pag_used / $pag_total * 100 ) . '%';
				}

				if ( $svc == 'cdn' ) {
					$used = Utility::real_size( $used * 1024 * 1024 );
					$quota = Utility::real_size( $quota * 1024 * 1024 );
					$pag_used = Utility::real_size( $pag_used * 1024 * 1024 );
					$pag_total = Utility::real_size( $pag_total * 1024 * 1024 );
				}
			}
		?>
			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title"><?php echo $title; ?></h3>

					<div class="litespeed-flex-container">
						<div class="litespeed-icon-vertical-middle">
							<?php echo GUI::pie( $finished_percentage, 70, true ) ; ?>
						</div>
						<div>
							<div class="litespeed-dashboard-stats">
								<h3><?php echo __('Used','litespeed-cache'); ?></h3>
								<p><strong><?php echo $used; ?></strong> <span class="litespeed-desc">of <?php echo $quota; ?></span></p>
								<p class="litespeed-desc" style="background-color: pink;" title="Pay As You Go"><span style="background-color: cyan;width: <?php echo $pag_width; ?>"><?php echo $pag_used; ?> / <?php echo $pag_total; ?><span></p>
							</div>
						</div>
					</div>

				</div>
			</div>
		<?php endforeach; ?>
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
							<?php if ( ! empty( $speed_before_cache ) ) : ?>
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
							<?php endif ; ?>
						</div>
					</div>

				</div>
			</div>

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

							<?php if ( ! empty( $_score[ 'score_before_optm' ] ) ) : ?>
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
							<?php endif ; ?>

							</div>

						</div>
					</div>

				</div>
			</div>

			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Cache Status', 'litespeed-cache' ) ; ?>
					</h3>

				<?php
					$cache_list = array(
						Base::O_CACHE			=> __( 'Public Cache', 'litespeed-cache' ),
						Base::O_CACHE_PRIV		=> __( 'Private Cache', 'litespeed-cache' ),
						Base::O_OBJECT			=> __( 'Object Cache', 'litespeed-cache' ),
						Base::O_CACHE_BROWSER	=> __( 'Browser Cache', 'litespeed-cache' ),
					);
					foreach ( $cache_list as $id => $title ) :
						$v = Conf::val( $id );
				?>
						<p>
							<?php if ( $v ) : ?>
								<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
							<?php else: ?>
								<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
							<?php endif; ?>
							<?php echo $title; ?>
						</p>
					<?php endforeach; ?>
				</div>
				<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
					<div>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-cache' ); ?>">Manage Cache</a>
					</div>
				</div>
			</div>

			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Cache Stats', 'litespeed-cache' ) ; ?>
					</h3>

				<?php if ( $lscache_stats ) : ?>
				<?php foreach ( $lscache_stats as $title => $val ) : ?>
					<p><?php echo $title; ?>: <?php echo $val ? "<code>$val</code>" : '-'; ?></p>
				<?php endforeach; ?>
				<?php endif; ?>

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
					</h3>

					<div class="litespeed-flex-container">
						<div class="litespeed-icon-vertical-middle">
							<?php echo GUI::pie( $img_gathered_percentage, 70, true ) ; ?>
						</div>
						<div>
							<div class="litespeed-dashboard-stats">
								<h3><?php echo __('Image Prepared','litespeed-cache'); ?></h3>
								<p>
									<strong><?php echo Admin_Display::print_plural( $img_count[ 'groups_all' ] - $img_count[ 'groups_not_gathered' ] ) ; ?></strong>
									<span class="litespeed-desc">of <?php echo Admin_Display::print_plural( $img_count[ 'groups_all' ] ) ; ?></span>
								</p>
							</div>
						</div>
					</div>

					<div class="litespeed-flex-container">
						<div class="litespeed-icon-vertical-middle">
							<?php echo GUI::pie( $img_finished_percentage, 70, true ) ; ?>
						</div>
						<div>
							<div class="litespeed-dashboard-stats">
								<h3><?php echo __('Image Requested','litespeed-cache'); ?></h3>
								<p>
									<strong><?php echo Admin_Display::print_plural( $img_count[ 'imgs_gathered' ] - $img_count[ 'img.' . Img_Optm::STATUS_RAW ], 'image' ) ; ?></strong>
									<span class="litespeed-desc">of <?php echo Admin_Display::print_plural( $img_count[ 'imgs_gathered' ], 'image' ) ; ?></span>
								</p>
							</div>
						</div>
					</div>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ) : ?>
					<p class="litespeed-success">
						<?php echo __('Images requested', 'litespeed-cache') ; ?>:
						<code>
							<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ; ?>
							(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ], 'image' ) ; ?>)
						</code>
					</p>
					<?php endif ; ?>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo __('Images notified to pull', 'litespeed-cache') ; ?>:
							<code>
								<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ; ?>
								(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ], 'image' ) ; ?>)
							</code>

						</p>
					<?php endif ; ?>

					<p>
						<?php echo __( 'Last Request', 'litespeed-cache' ) ; ?>: <code><?php echo ! empty( $optm_summary[ 'last_requested' ] ) ? Utility::readable_time( $optm_summary[ 'last_requested' ] ) : '-'  ; ?></code>
					</p>
					<p>
						<?php echo __( 'Last Pull', 'litespeed-cache' ) ; ?>: <code><?php echo ! empty( $optm_summary[ 'last_pull' ] ) ? Utility::readable_time( $optm_summary[ 'last_pull' ] ) : '-'  ; ?></code>
					</p>

					<?php
					$cache_list = array(
						Base::O_IMG_OPTM_AUTO	=> __( 'Auto Request Cron', 'litespeed-cache' ),
						Base::O_IMG_OPTM_CRON	=> __( 'Auto Pull Cron', 'litespeed-cache' ),
					);
					foreach ( $cache_list as $id => $title ) :
						$v = Conf::val( $id );
					?>
						<p>
							<?php if ( $v ) : ?>
								<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
							<?php else: ?>
								<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
							<?php endif; ?>
							<?php echo $title; ?>
						</p>
					<?php endforeach; ?>

				</div>
			</div>


		</div>

	</div>


</div>