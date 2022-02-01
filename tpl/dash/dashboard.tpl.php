<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$lscache_stats = GUI::cls()->lscache_stats();

$health_scores = Health::cls()->scores();

$crawler_summary = Crawler::get_summary();

// Image related info
$optm_summary = Img_Optm::get_summary();
$img_count = Img_Optm::cls()->img_count();
if ( ! empty( $img_count[ 'groups_all' ] ) ) {
	$img_gathered_percentage = 100 - floor( $img_count[ 'groups_not_gathered' ] * 100 / $img_count[ 'groups_all' ] );
}
else {
	$img_gathered_percentage = 0;
}

if ( ! empty( $img_count[ 'imgs_gathered' ] ) ) {
	$img_finished_percentage = 100 - floor( $img_count[ 'img.' . Img_Optm::STATUS_RAW ] * 100 / $img_count[ 'imgs_gathered' ] );
}
else {
	$img_finished_percentage = 0;
}

$cloud_summary = Cloud::get_summary();
$css_summary = CSS::get_summary();
$placeholder_summary = Placeholder::get_summary();

$ccss_count = count( $this->load_queue( 'ccss' ) );
$ucss_count = count( $this->load_queue( 'ucss' ) );
$placeholder_queue_count = count( $this->load_queue( 'lqip' ) );
?>

<div class="litespeed-dashboard">


	<div class="litespeed-dashboard-header">
		<h3 class="litespeed-dashboard-title">
			<?php echo __( 'QUIC.cloud Service Usage Statistics', 'litespeed-cache' ); ?>
			<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_SYNC_USAGE ); ?>">
				<span class="dashicons dashicons-update"></span>
				<span class="screen-reader-text"><?php echo __( 'Sync data from Cloud', 'litespeed-cache' ); ?></span>
			</a>
		</h3>
		<hr>
		<a href="https://docs.litespeedtech.com/lscache/lscwp/dashboard/#usage-statistics" target="_blank" class="litespeed-learn-more"><?php echo __( 'Learn More', 'litespeed-cache' );?></a>
	</div>

	<div class="litespeed-dashboard-stats-wrapper">
		<?php
		$cat_list = array(
			'img_optm'	=> __( 'Image Optimization', 'litespeed-cache' ),
			'page_optm'	=> __( 'Page Optimization', 'litespeed-cache' ),
			'cdn'		=> __( 'CDN Bandwidth', 'litespeed-cache' ),
			'lqip'		=> __( 'Low Quality Image Placeholder', 'litespeed-cache' ),
		);

		foreach ( $cat_list as $svc => $title ) :
			$finished_percentage = 0;
			$total_used = $used = $quota = $pag_used = $pag_total = '-';
			$pag_width = 0;
			$percentage_bg = 'success';
			$pag_txt_color = '';
			$usage = false;

			if ( ! empty( $cloud_summary[ 'usage.' . $svc ] ) ) {
				$usage = $cloud_summary[ 'usage.' . $svc ];
				$finished_percentage = floor( $usage[ 'used' ] * 100 / $usage[ 'quota' ] );
				$used = (int)$usage[ 'used' ];
				$quota = (int)$usage[ 'quota' ];
				$pag_used = ! empty( $usage[ 'pag_used' ] ) ? (int)$usage[ 'pag_used' ] : 0;
				$pag_bal = ! empty( $usage[ 'pag_bal' ] ) ? (int)$usage[ 'pag_bal' ] : 0;
				$pag_total = $pag_used + $pag_bal;
				if ( ! empty( $usage[ 'total_used' ] ) ) {
					$total_used = (int)$usage[ 'total_used' ];
				}

				if ( $pag_total ) {
					$pag_width = round( $pag_used / $pag_total * 100 ) . '%';
				}

				if ( $finished_percentage > 85 ) {
					$percentage_bg = 'warning';
					if ( $finished_percentage > 95 ) {
						$percentage_bg = 'danger';
						if ( $pag_bal ) { // is using PAG quota
							$percentage_bg = 'warning';
							$pag_txt_color = 'litespeed-success';
						}
					}
				}

				if ( $svc == 'cdn' ) {
					// $used = Utility::real_size( $used * 1000000 * 100, true );
					// $quota = Utility::real_size( $quota * 1000000 * 100, true );
					// $pag_used = Utility::real_size( $pag_used * 1000000 * 100, true );
					// $pag_bal = Utility::real_size( $pag_bal * 1000000 * 100, true );
				}
			}

		?>
			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title"><?php echo $title; ?></h3>

					<div class="litespeed-flex-container">
						<div class="litespeed-icon-vertical-middle litespeed-pie-<?php echo $percentage_bg;?>">
							<?php echo GUI::pie( $finished_percentage, 60, false ); ?>
						</div>
						<div>
							<div class="litespeed-dashboard-stats">
								<h3><?php echo ( $svc == 'img_optm' ? __('Fast Queue Usage','litespeed-cache') : __( 'Usage', 'litespeed-cache' ) ); ?></h3>
								<p>
									<strong><?php echo $used; ?></strong>
									<?php if( $used != $quota ) { ?>
										<span class="litespeed-desc"> of <?php echo $quota; ?></span>
									<?php } ?>
								</p>
							</div>
						</div>
					</div>

					<?php if ( $pag_total > 0 ) { ?>
						<p class="litespeed-dashboard-stats-payg <?php echo $pag_txt_color; ?>">
							<?php echo __('PAYG Balance','litespeed-cache'); ?>: <strong><?php echo $pag_bal; ?></strong>
							<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo __('This Month Usage','litespeed-cache'); ?>: <?php echo $pag_used;?>">
								<span class="dashicons dashicons-info"></span>
								<span class="screen-reader-text"><?php echo __( 'Pay as You Go Usage Statistics', 'litespeed-cache' );?></span>
							</button>
						</p>
					<?php } ?>

					<?php if ( $svc == 'page_optm' ) : ?>
						<?php if ( ! empty( $usage[ 'sub_svc' ] ) ) : ?>
							<p class="litespeed-dashboard-stats-total">
							<?php $i=0;foreach ( $usage[ 'sub_svc' ] as $sub_svc => $sub_usage ) : ?>
								<?php if ($sub_svc=='vpi') continue; ?>
								<span class="<?php if ( $i++>0 ) echo 'litespeed-left10'; ?>"><?php echo strtoupper( esc_html( $sub_svc ) ); ?>: <strong><?php echo (int)$sub_usage; ?></strong></span>
							<?php endforeach; ?>
							</p>
							<div class="clear"></div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $svc == 'img_optm' ) { ?>
						<p class="litespeed-dashboard-stats-total">
							<?php echo __('Total Usage','litespeed-cache'); ?>: <strong><?php echo $total_used; ?> / ∞</strong>
							<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo __('Total images optimized in this month','litespeed-cache'); ?>">
								<span class="dashicons dashicons-info"></span>
							</button>
						</p>
						<div class="clear"></div>
					<?php } ?>

					<?php if ( isset( $usage[ 'remaining_daily_quota' ] ) && $usage[ 'remaining_daily_quota' ] >= 0 && isset( $usage[ 'daily_quota' ] ) && $usage[ 'daily_quota' ] >= 0 ) { ?>
						<p class="litespeed-dashboard-stats-total">
							<?php echo __('Remaining Daily Quota','litespeed-cache'); ?>: <strong><?php echo $usage[ 'remaining_daily_quota' ]; ?> / <?php echo $usage[ 'daily_quota' ]; ?></strong>
						</p>
						<div class="clear"></div>
					<?php } ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<p class="litespeed-right litespeed-qc-dashboard-link"><a href="<?php echo Cloud::CLOUD_SERVER_DASH; ?>" class="litespeed-link-with-icon" target="_blank"><?php echo __( 'Go to QUIC.cloud dashboard', 'litespeed-cache' ) ;?> <span class="dashicons dashicons-external"></span></a></p>

	<div class="litespeed-dashboard-group">
		<hr>
		<div class="litespeed-flex-container">

			<div class="postbox litespeed-postbox litespeed-postbox-pagetime">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Page Load Time', 'litespeed-cache' ); ?>
						<a href="<?php echo Utility::build_url( Router::ACTION_HEALTH, Health::TYPE_SPEED ); ?>">
							<span class="dashicons dashicons-update"></span>
							<span class="screen-reader-text"><?php echo __('Refresh page load time', 'litespeed-cache'); ?></span>
						</a>
					</h3>

					<div>
						<div class="litespeed-flex-container">

							<?php if ( $health_scores[ 'speed_before' ] ) : ?>
								<div class="litespeed-score-col">
									<p class="litespeed-text-grey">
										<?php echo __( 'Before', 'litespeed-cache' ); ?>
									</p>
									<div class="litespeed-text-md litespeed-text-grey">
										<?php echo $health_scores[ 'speed_before' ]; ?><span class="litespeed-text-large">s</span>
									</div>

								</div>
								<div class="litespeed-score-col">
									<p class="litespeed-text-grey">
										<?php echo __( 'After', 'litespeed-cache' ); ?>
									</p>
									<div class="litespeed-text-md litespeed-text-success">
										<?php echo $health_scores[ 'speed_after' ]; ?><span class="litespeed-text-large">s</span>
									</div>
								</div>
								<div class="litespeed-score-col litespeed-score-col--imp">
									<p class="litespeed-text-grey" style="white-space: nowrap;">
										<?php echo __( 'Improved by', 'litespeed-cache' ); ?>
									</p>
									<div class="litespeed-text-jumbo litespeed-text-success">
										<?php echo $health_scores[ 'speed_improved' ]; ?><span class="litespeed-text-large">%</span>
									</div>
								</div>
							<?php endif; ?>

						</div>
					</div>
				</div>

				<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
					<?php if ( ! empty( $cloud_summary[ 'last_request.health-speed' ] ) ) : ?>
						<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary[ 'last_request.health-speed' ] ) ?>
					<?php endif; ?>

					<?php $closest_server = Cloud::get_summary( 'server.' . CLoud::SVC_HEALTH ); ?>
					<?php if ( $closest_server ) : ?>
						<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_HEALTH ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo sprintf( __( 'Current closest Cloud server is %s.&#10;Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ) ; ?>" class="litespeed-right"><i class='litespeed-quic-icon'></i></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="postbox litespeed-postbox litespeed-postbox-pagespeed">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'PageSpeed Score', 'litespeed-cache' ); ?>
						<a href="<?php echo Utility::build_url( Router::ACTION_HEALTH, Health::TYPE_SCORE ); ?>">
							<span class="dashicons dashicons-update"></span>
							<span class="screen-reader-text"><?php echo __('Refresh page score', 'litespeed-cache'); ?></span>
						</a>

						<?php $id = Base::O_GUEST; ?>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-general' ); ?>" class="litespeed-title-right-icon"><?php echo Lang::title( $id ); ?></a>
						<?php if ( $this->conf( $id ) ) : ?>
							<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
						<?php else: ?>
							<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
						<?php endif; ?>

					</h3>

					<div>

						<div class="litespeed-margin-bottom20">
							<div class="litespeed-row-flex" style="margin-left: -10px;">

							<?php if ( ! empty( $health_scores[ 'score_before' ] ) ) : ?>
								<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
									<p class="litespeed-text-grey litespeed-text-center">
										<?php echo __( 'Before', 'litespeed-cache' ); ?>
									</p>
									<div class="litespeed-promo-score">
										<?php echo GUI::pie( $health_scores[ 'score_before' ], 45, false, true, 'litespeed-pie-' . GUI::cls()->get_cls_of_pagescore( $health_scores[ 'score_before' ] ) ); ?>
									</div>
								</div>
								<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
									<p class="litespeed-text-grey litespeed-text-center">
										<?php echo __( 'After', 'litespeed-cache' ); ?>
									</p>
									<div class="litespeed-promo-score">
										<?php echo GUI::pie( $health_scores[ 'score_after' ], 45, false, true, 'litespeed-pie-' . GUI::cls()->get_cls_of_pagescore( $health_scores[ 'score_after' ] ) ); ?>
									</div>
								</div>
								<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
									<p class="litespeed-text-grey" style="white-space: nowrap;">
										<?php echo __( 'Improved by', 'litespeed-cache' ); ?>
									</p>
									<div class="litespeed-postbox-score-improve litespeed-text-fern">
										<?php echo $health_scores[ 'score_improved' ]; ?><span class="litespeed-text-large">%</span>
									</div>
								</div>
							<?php endif; ?>

							</div>

						</div>
					</div>
				</div>

				<?php if ( ! empty( $cloud_summary[ 'last_request.health-score' ] ) ) : ?>
					<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
						<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary[ 'last_request.health-score' ] ) ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="postbox litespeed-postbox litespeed-postbox-double litespeed-postbox-imgopt">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Image Optimization Summary', 'litespeed-cache' ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-img_optm' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
					</h3>
					<div class="litespeed-postbox-double-content">
						<div class="litespeed-postbox-double-col">
							<div class="litespeed-flex-container">
								<div class="litespeed-icon-vertical-middle">
									<?php echo GUI::pie( $img_gathered_percentage, 70, true ); ?>
								</div>
								<div>
									<div class="litespeed-dashboard-stats">
										<h3><?php echo __('Image Groups Prepared','litespeed-cache'); ?></h3>
										<p>
											<strong><?php echo ( $img_count[ 'groups_all' ] - $img_count[ 'groups_not_gathered' ] ); ?></strong>
											<span class="litespeed-desc">of <?php echo $img_count[ 'groups_all' ]; ?></span>
										</p>
									</div>
								</div>
							</div>

							<div class="litespeed-flex-container">
								<div class="litespeed-icon-vertical-middle">
									<?php echo GUI::pie( $img_finished_percentage, 70, true ); ?>
								</div>
								<div>
									<div class="litespeed-dashboard-stats">
										<h3><?php echo __('Images Requested','litespeed-cache'); ?></h3>
										<p>
											<strong><?php echo ( $img_count[ 'imgs_gathered' ] - $img_count[ 'img.' . Img_Optm::STATUS_RAW ]); ?></strong>
											<span class="litespeed-desc">of <?php echo $img_count[ 'imgs_gathered' ]; ?></span>
										</p>
									</div>
								</div>
							</div>
						</div>
						<div class="litespeed-postbox-double-col">
							<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ) : ?>
							<p class="litespeed-success">
								<?php echo __('Images requested', 'litespeed-cache'); ?>:
								<code>
									<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ); ?>
									(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ], 'image' ); ?>)
								</code>
							</p>
							<?php endif; ?>

							<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ) : ?>
								<p class="litespeed-success">
									<?php echo __('Images notified to pull', 'litespeed-cache'); ?>:
									<code>
										<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ); ?>
										(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ], 'image' ); ?>)
									</code>

								</p>
							<?php endif; ?>

							<p>
								<?php echo __( 'Last Request', 'litespeed-cache' ); ?>: <code><?php echo ! empty( $optm_summary[ 'last_requested' ] ) ? Utility::readable_time( $optm_summary[ 'last_requested' ] ) : '-'; ?></code>
							</p>
							<p>
								<?php echo __( 'Last Pull', 'litespeed-cache' ); ?>: <code><?php echo ! empty( $optm_summary[ 'last_pull' ] ) ? Utility::readable_time( $optm_summary[ 'last_pull' ] ) : '-'; ?></code>
							</p>

							<?php
							$cache_list = array(
								Base::O_IMG_OPTM_AUTO	=> Lang::title( Base::O_IMG_OPTM_AUTO ),
								Base::O_IMG_OPTM_CRON	=> Lang::title( Base::O_IMG_OPTM_CRON ),
							);
							foreach ( $cache_list as $id => $title ) :
							?>
								<p>
									<?php if ( $this->conf( $id ) ) : ?>
										<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
									<?php else: ?>
										<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
									<?php endif; ?>
									<a href="<?php echo admin_url( 'admin.php?page=litespeed-img_optm#settings' ); ?>"><?php echo $title; ?></a>
								</p>
							<?php endforeach; ?>
						</div>
					</div>

				</div>
			</div>

			<div class="postbox litespeed-postbox litespeed-postbox-cache">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Cache Status', 'litespeed-cache' ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-cache' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
					</h3>

				<?php
					$cache_list = array(
						Base::O_CACHE			=> __( 'Public Cache', 'litespeed-cache' ),
						Base::O_CACHE_PRIV		=> __( 'Private Cache', 'litespeed-cache' ),
						Base::O_OBJECT			=> __( 'Object Cache', 'litespeed-cache' ),
						Base::O_CACHE_BROWSER	=> __( 'Browser Cache', 'litespeed-cache' ),
					);
					foreach ( $cache_list as $id => $title ) :
				?>
						<p>
							<?php if ( $this->conf( $id ) ) : ?>
								<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
							<?php else: ?>
								<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
							<?php endif; ?>
							<?php echo esc_html( $title ); ?>
						</p>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $lscache_stats ) : ?>
			<div class="postbox litespeed-postbox litespeed-postbox-cache-stats">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Cache Stats', 'litespeed-cache' ); ?>
					</h3>

				<?php foreach ( $lscache_stats as $title => $val ) : ?>
					<p><?php echo $title; ?>: <?php echo $val ? "<code>$val</code>" : '-'; ?></p>
				<?php endforeach; ?>

				</div>
			</div>
			<?php endif; ?>

			<div class="postbox litespeed-postbox litespeed-postbox-ccss">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Critical CSS', 'litespeed-cache' ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-page_optm#settings_css' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
					</h3>

					<?php if ( ! empty( $css_summary[ 'last_request_ccss' ] ) ) : ?>
						<p>
							<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $css_summary[ 'last_request_ccss' ] ) . '</code>'; ?>
						</p>
						<p>
							<?php echo __( 'Time to execute previous request', 'litespeed-cache' ) . ': <code>' . esc_html( $css_summary[ 'last_spent_ccss' ] ) . 's</code>'; ?>
						</p>
					<?php endif; ?>

					<p>
						<?php echo __( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo $ccss_count ?: '-'; ?></code>
						<a href="<?php echo $ccss_count ? Utility::build_url( Router::ACTION_CSS, CSS::TYPE_GEN_CCSS ) : 'javascript:;'; ?>"
							class="button button-secondary button-small <?php if ( ! $ccss_count ) echo 'disabled'; ?>">
							<?php echo __( 'Force cron', 'litespeed-cache' ); ?>
						</a>
					</p>

				</div>

				<?php if ( ! empty( $cloud_summary[ 'last_request.ccss' ] ) ) : ?>
					<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
						<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary[ 'last_request.ccss' ] ) ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="postbox litespeed-postbox litespeed-postbox-ucss">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Unique CSS', 'litespeed-cache' ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-page_optm#settings_css' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
					</h3>

					<?php if ( ! empty( $css_summary[ 'last_request_ucss' ] ) ) : ?>
						<p>
							<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $css_summary[ 'last_request_ucss' ] ) . '</code>'; ?>
						</p>
						<p>
							<?php echo __( 'Time to execute previous request', 'litespeed-cache' ) . ': <code>' . esc_html( $css_summary[ 'last_spent_ucss' ] ) . 's</code>'; ?>
						</p>
					<?php endif; ?>

					<p>
						<?php echo __( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo $ucss_count ?: '-' ?></code>
						<a href="<?php echo $ucss_count ? Utility::build_url( Router::ACTION_CSS, CSS::TYPE_GEN_UCSS ) : 'javascript:;'; ?>"
							class="button button-secondary button-small <?php if ( ! $ucss_count ) echo 'disabled'; ?>">
							<?php echo __( 'Force cron', 'litespeed-cache' ); ?>
						</a>
					</p>

				</div>

				<?php if ( ! empty( $cloud_summary[ 'last_request.ucss' ] ) ) : ?>
					<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
						<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary[ 'last_request.ucss' ] ) ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="postbox litespeed-postbox litespeed-postbox-lqip">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Low Quality Image Placeholder', 'litespeed-cache' ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-page_optm#settings_media' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
					</h3>

					<?php if ( ! empty( $placeholder_summary[ 'last_request' ] ) ) : ?>
						<p>
							<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $placeholder_summary[ 'last_request' ] ) . '</code>'; ?>
						</p>
						<p>
							<?php echo __( 'Time to execute previous request', 'litespeed-cache' ) . ': <code>' . esc_html( $placeholder_summary[ 'last_spent' ] ) . 's</code>'; ?>
						</p>
					<?php endif; ?>

					<p>
						<?php echo __( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo $placeholder_queue_count ?: '-' ?></code>
						<a href="<?php echo $placeholder_queue_count ? Utility::build_url( Router::ACTION_PLACEHOLDER, Placeholder::TYPE_GENERATE ) : 'javascript:;'; ?>" class="button button-secondary button-small <?php if ( ! $placeholder_queue_count ) echo 'disabled'; ?>">
							<?php echo __( 'Force cron', 'litespeed-cache' ); ?>
						</a>
					</p>

				</div>

				<?php if ( ! empty( $cloud_summary[ 'last_request.lqip' ] ) ) : ?>
					<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
						<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary[ 'last_request.lqip' ] ) ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="postbox litespeed-postbox litespeed-postbox-crawler">
				<div class="inside">
					<h3 class="litespeed-title">
						<?php echo __( 'Crawler Status', 'litespeed-cache' ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=litespeed-crawler' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
					</h3>

					<p>
						<code><?php echo count( Crawler::cls()->list_crawlers() );?></code> <?php echo __( 'Crawler(s)', 'litespeed-cache' ); ?>
					</p>
					<p>
						<?php echo __( 'Currently active crawler', 'litespeed-cache' ); ?>: <code><?php echo esc_html( $crawler_summary[ 'curr_crawler' ] ); ?></code>
					</p>

					<?php if ( $crawler_summary[ 'curr_crawler_beginning_time' ] ) : ?>
					<p>
						<b><?php echo __('Current crawler started at', 'litespeed-cache'); ?>:</b>
						<?php echo Utility::readable_time( $crawler_summary[ 'curr_crawler_beginning_time' ] ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $crawler_summary[ 'last_start_time' ] ) : ?>
					<p class='litespeed-desc'>
						<b><?php echo __('Last interval', 'litespeed-cache'); ?>:</b>
						<?php echo Utility::readable_time( $crawler_summary[ 'last_start_time' ] ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $crawler_summary[ 'end_reason' ] ) : ?>
					<p class='litespeed-desc'>
						<b><?php echo __( 'Ended reason', 'litespeed-cache' ); ?>:</b>
						<?php echo esc_html( $crawler_summary[ 'end_reason' ] ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $crawler_summary[ 'last_crawled' ] ) : ?>
					<p class='litespeed-desc'>
						<?php echo sprintf(__('<b>Last crawled:</b> %d item(s)', 'litespeed-cache'), $crawler_summary[ 'last_crawled' ] ); ?>
					</p>
					<?php endif; ?>

				</div>
			</div>

		</div>

	</div>


</div>
