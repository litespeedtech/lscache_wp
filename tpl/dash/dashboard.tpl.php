<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$health_scores = Health::cls()->scores();

$crawler_summary = Crawler::get_summary();

// Image related info
$img_optm_summary        = Img_Optm::get_summary();
$img_count               = Img_Optm::cls()->img_count();
$img_finished_percentage = 0;
if ( $img_count['groups_all'] ) {
	$img_finished_percentage = 100 - floor( $img_count['groups_new'] * 100 / $img_count['groups_all'] );
}
if ( $img_finished_percentage == 100 && $img_count['groups_new'] ) {
	$img_finished_percentage = 99;
}

$__cloud = Cloud::cls();
$__cloud->finish_qc_activation();

$cloud_summary       = Cloud::get_summary();
$css_summary         = CSS::get_summary();
$ucss_summary        = UCSS::get_summary();
$placeholder_summary = Placeholder::get_summary();
$vpi_summary         = VPI::get_summary();

$ccss_count              = count( $this->load_queue( 'ccss' ) );
$ucss_count              = count( $this->load_queue( 'ucss' ) );
$placeholder_queue_count = count( $this->load_queue( 'lqip' ) );
$vpi_queue_count         = count( $this->load_queue( 'vpi' ) );

$can_page_load_time = defined( 'LITESPEED_SERVER_TYPE' ) && LITESPEED_SERVER_TYPE !== 'NONE';
?>

<div class="litespeed-dashboard">

	<?php if ( ! $__cloud->activated() && ! Admin_Display::has_qc_hide_banner() ) : ?>
		<div class="litespeed-dashboard-group">
			<div class="litespeed-flex-container">

				<div class="postbox litespeed-postbox litespeed-postbox-cache">
					<div class="inside">
						<h3 class="litespeed-title">
							<?php echo __( 'Cache Status', 'litespeed-cache' ); ?>
							<a href="<?php echo admin_url( 'admin.php?page=litespeed-cache' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
						</h3>

						<?php
						$cache_list = array(
							Base::O_CACHE         => __( 'Public Cache', 'litespeed-cache' ),
							Base::O_CACHE_PRIV    => __( 'Private Cache', 'litespeed-cache' ),
							Base::O_OBJECT        => __( 'Object Cache', 'litespeed-cache' ),
							Base::O_CACHE_BROWSER => __( 'Browser Cache', 'litespeed-cache' ),
						);
						foreach ( $cache_list as $id => $title ) :
							?>
							<p>
								<?php if ( $this->conf( $id ) ) : ?>
									<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
								<?php else : ?>
									<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
								<?php endif; ?>
								<?php echo esc_html( $title ); ?>
							</p>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="postbox litespeed-postbox litespeed-postbox-crawler">
					<div class="inside">
						<h3 class="litespeed-title">
							<?php echo __( 'Crawler Status', 'litespeed-cache' ); ?>
							<a href="<?php echo admin_url( 'admin.php?page=litespeed-crawler' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
						</h3>

						<p>
							<code><?php echo count( Crawler::cls()->list_crawlers() ); ?></code> <?php echo __( 'Crawler(s)', 'litespeed-cache' ); ?>
						</p>
						<p>
							<?php echo __( 'Currently active crawler', 'litespeed-cache' ); ?>: <code><?php echo esc_html( $crawler_summary['curr_crawler'] ); ?></code>
						</p>

						<?php if ( $crawler_summary['curr_crawler_beginning_time'] ) : ?>
							<p>
								<b><?php echo __( 'Current crawler started at', 'litespeed-cache' ); ?>:</b>
								<?php echo Utility::readable_time( $crawler_summary['curr_crawler_beginning_time'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $crawler_summary['last_start_time'] ) : ?>
							<p class='litespeed-desc'>
								<b><?php echo __( 'Last interval', 'litespeed-cache' ); ?>:</b>
								<?php echo Utility::readable_time( $crawler_summary['last_start_time'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $crawler_summary['end_reason'] ) : ?>
							<p class='litespeed-desc'>
								<b><?php echo __( 'Ended reason', 'litespeed-cache' ); ?>:</b>
								<?php echo esc_html( $crawler_summary['end_reason'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $crawler_summary['last_crawled'] ) : ?>
							<p class='litespeed-desc'>
								<?php printf( __( '<b>Last crawled:</b> %d item(s)', 'litespeed-cache' ), $crawler_summary['last_crawled'] ); ?>
							</p>
						<?php endif; ?>

					</div>
				</div>

				<?php $news = $__cloud->load_qc_status_for_dash( 'news_dash_guest' ); ?>
				<?php if ( $news ) : ?>
					<div class="postbox litespeed-postbox">
						<div class="inside litespeed-text-center">
							<h3 class="litespeed-title">
								<?php echo __( 'News', 'litespeed-cache' ); ?>
							</h3>
							<div class="litespeed-top20">
								<?php echo $news; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>

		</div>

	<?php endif; ?>

	<div class="litespeed-dashboard-qc">
		<?php if ( ! $__cloud->activated() && ! Admin_Display::has_qc_hide_banner() ) : ?>
			<div class="litespeed-dashboard-unlock">
				<div>
					<h3 class="litespeed-dashboard-unlock-title">
						<strong class="litespeed-qc-text-gradient">
							<?php _e( 'Accelerate, Optimize, Protect', 'litespeed-cache' ); ?>
						</strong>
					</h3>
					<p class="litespeed-dashboard-unlock-desc"><?php _e( 'Speed up your WordPress site even further with <strong>QUIC.cloud Online Services and CDN</strong>.', 'litespeed-cache' ); ?></p>
					<p>
						<?php _e( 'Free monthly quota available. Can also be used anonymously (no email required).', 'litespeed-cache' ); ?>
					</p>
					<p>
						<a class="button button-primary" href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE ); ?>">
							<span class="dashicons dashicons-yes"></span>
							<?php _e( 'Enable QUIC.cloud services', 'litespeed-cache' ); ?>
						</a>
						<br><a class="button button-link litespeed-top10" href="<?php echo Utility::build_url( Router::ACTION_ADMIN_DISPLAY, Admin_Display::TYPE_QC_HIDE_BANNER ); ?>"><?php _e( 'Do not show this again', 'litespeed-cache' ); ?></a>

					</p>
					<p class="litespeed-dashboard-unlock-footer">
						<?php _e( 'QUIC.cloud provides CDN and online optimization services, and is not required. You may use many features of this plugin without QUIC.cloud.', 'litespeed-cache' ); ?><br>
						<a href="https://www.quic.cloud/" target="_blank">
							<?php _e( 'Learn More about QUIC.cloud', 'litespeed-cache' ); ?>
						</a>
						<br>
					</p>
				</div>
			</div>
		<?php endif; ?>

		<div class="litespeed-dashboard-qc-enable">

			<div class="litespeed-dashboard-header">
				<h3 class="litespeed-dashboard-title litespeed-dashboard-title--w-btn">
					<span class="litespeed-right10"><?php echo __( 'QUIC.cloud Service Usage Statistics', 'litespeed-cache' ); ?></span>
					<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_SYNC_USAGE ); ?>" class="button button-secondary button-small">
						<span class="dashicons dashicons-update"></span> <?php echo __( 'Refresh Usage', 'litespeed-cache' ); ?>
						<span class="screen-reader-text"><?php echo __( 'Sync data from Cloud', 'litespeed-cache' ); ?></span>
					</a>
				</h3>
				<hr>
				<a href="https://docs.litespeedtech.com/lscache/lscwp/dashboard/#usage-statistics" target="_blank" class="litespeed-learn-more"><?php echo __( 'Learn More', 'litespeed-cache' ); ?></a>
			</div>

			<?php if ( ! $__cloud->activated() && Admin_Display::has_qc_hide_banner() ) : ?>
				<p class="litespeed-desc litespeed-margin-top-remove"><?php echo __( 'The features below are provided by', 'litespeed-cache' ); ?> <a href="<?php echo admin_url( 'admin.php?page=litespeed-general' ); ?>">QUIC.cloud</a></p>
			<?php endif; ?>


			<div class="litespeed-dashboard-stats-wrapper">
				<?php
				$cat_list = array(
					'img_optm'  => __( 'Image Optimization', 'litespeed-cache' ),
					'page_optm' => __( 'Page Optimization', 'litespeed-cache' ),
					'cdn'       => __( 'CDN Bandwidth', 'litespeed-cache' ),
					'lqip'      => __( 'Low Quality Image Placeholder', 'litespeed-cache' ),
				);

				foreach ( $cat_list as $svc => $title ) :
					$finished_percentage = 0;
					$total_used          = $used = $quota = $pag_used = $pag_total = '-';
					$pag_width           = 0;
					$percentage_bg       = 'success';
					$pag_txt_color       = '';
					$usage               = false;

					if ( ! empty( $cloud_summary[ 'usage.' . $svc ] ) ) {
						$usage               = $cloud_summary[ 'usage.' . $svc ];
						$finished_percentage = floor( $usage['used'] * 100 / $usage['quota'] );
						$used                = (int) $usage['used'];
						$quota               = (int) $usage['quota'];
						$pag_used            = ! empty( $usage['pag_used'] ) ? (int) $usage['pag_used'] : 0;
						$pag_bal             = ! empty( $usage['pag_bal'] ) ? (int) $usage['pag_bal'] : 0;
						$pag_total           = $pag_used + $pag_bal;
						if ( ! empty( $usage['total_used'] ) ) {
							$total_used = (int) $usage['total_used'];
						}

						if ( $pag_total ) {
							// $finished_percentage = floor( ($pag_used+$usage[ 'used' ]) * 100 / ($usage[ 'quota' ]+$pag_total) );
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
								<div class="litespeed-icon-vertical-middle litespeed-pie-<?php echo $percentage_bg; ?>">
									<?php echo GUI::pie( $finished_percentage, 60, false ); ?>
								</div>
								<div>
									<div class="litespeed-dashboard-stats">
										<h3><?php echo ( $svc == 'img_optm' ? __( 'Fast Queue Usage', 'litespeed-cache' ) : __( 'Usage', 'litespeed-cache' ) ); ?></h3>
										<p>
											<strong><?php echo $used; ?></strong>
											<?php if ( $used != $quota ) { ?>
												<span class="litespeed-desc"> of <?php echo $quota; ?></span>
											<?php } ?>
										</p>
									</div>
								</div>
							</div>

							<?php if ( $pag_total > 0 ) { ?>
								<p class="litespeed-dashboard-stats-payg <?php echo $pag_txt_color; ?>">
									<?php echo __( 'PAYG Balance', 'litespeed-cache' ); ?>: <strong><?php echo $pag_bal; ?></strong>
									<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo __( 'PAYG used this month', 'litespeed-cache' ); ?>: <?php echo $pag_used; ?>. <?php echo __( 'PAYG balance and usage not included in above quota calculation.', 'litespeed-cache' ); ?>">
										<span class="dashicons dashicons-info"></span>
										<span class="screen-reader-text"><?php echo __( 'Pay as You Go Usage Statistics', 'litespeed-cache' ); ?></span>
									</button>
								</p>
							<?php } ?>

							<?php if ( $svc == 'page_optm' ) : ?>
								<?php if ( ! empty( $usage['sub_svc'] ) ) : ?>
									<p class="litespeed-dashboard-stats-total">
										<?php
										$i = 0;
										foreach ( $usage['sub_svc'] as $sub_svc => $sub_usage ) :
											?>
											<span class="
											<?php
											if ( $i++ > 0 ) {
												echo 'litespeed-left10';}
											?>
											"><?php echo strtoupper( esc_html( $sub_svc ) ); ?>: <strong><?php echo (int) $sub_usage; ?></strong></span>
										<?php endforeach; ?>
									</p>
								<?php endif; ?>
							<?php endif; ?>

							<?php if ( $svc == 'img_optm' ) { ?>
								<p class="litespeed-dashboard-stats-total">
									<?php echo __( 'Total Usage', 'litespeed-cache' ); ?>: <strong><?php echo $total_used; ?> / ∞</strong>
									<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo __( 'Total images optimized in this month', 'litespeed-cache' ); ?>">
										<span class="dashicons dashicons-info"></span>
									</button>
								</p>
								<div class="clear"></div>
							<?php } ?>

							<?php if ( isset( $usage['remaining_daily_quota'] ) && $usage['remaining_daily_quota'] >= 0 && isset( $usage['daily_quota'] ) && $usage['daily_quota'] >= 0 ) { ?>
								<p class="litespeed-dashboard-stats-total">
									<?php echo __( 'Remaining Daily Quota', 'litespeed-cache' ); ?>: <strong><?php echo $usage['remaining_daily_quota']; ?> / <?php echo $usage['daily_quota']; ?></strong>
								</p>
								<div class="clear"></div>
							<?php } ?>
						</div>
					</div>
				<?php endforeach; ?>

				<?php if ( ! empty( $cloud_summary['partner'] ) ) : ?>
					<div class="litespeed-postbox litespeed-postbox-partner">
						<div class="inside">
							<h3 class="litespeed-title"><?php echo __( 'Partner Benefits Provided by', 'litespeed-cache' ); ?></h3>
							<div>

								<?php if ( ! empty( $cloud_summary['partner']['logo'] ) ) : ?>
									<?php if ( ! empty( $cloud_summary['partner']['url'] ) ) : ?>
										<a href="<?php echo $cloud_summary['partner']['url']; ?>" target="_blank"><img src="<?php echo $cloud_summary['partner']['logo']; ?>" alt="<?php echo $cloud_summary['partner']['name']; ?>"></a>
									<?php else : ?>
										<img src="<?php echo $cloud_summary['partner']['logo']; ?>" alt="<?php echo $cloud_summary['partner']['name']; ?>">
									<?php endif; ?>
								<?php elseif ( ! empty( $cloud_summary['partner']['name'] ) ) : ?>
									<?php if ( ! empty( $cloud_summary['partner']['url'] ) ) : ?>
										<a href="<?php echo $cloud_summary['partner']['url']; ?>" target="_blank"><span class="postbox-partner-name"><?php echo $cloud_summary['partner']['name']; ?></span></a>
									<?php else : ?>
										<span class="postbox-partner-name"><?php echo $cloud_summary['partner']['name']; ?></span>
									<?php endif; ?>
								<?php endif; ?>

							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<p class="litespeed-right litespeed-qc-dashboard-link">

				<?php if ( ! empty( $cloud_summary['partner'] ) && ! empty( $cloud_summary['partner']['login_title'] ) && ! empty( $cloud_summary['partner']['login_link'] ) ) : ?>
					<?php Doc::learn_more( $cloud_summary['partner']['login_link'], $cloud_summary['partner']['login_title'], true, 'button litespeed-btn-warning' ); ?>
				<?php elseif ( ! empty( $cloud_summary['partner'] ) && ! empty( $cloud_summary['partner']['disable_qc_login'] ) ) : ?>
				<?php else : ?>
					<?php if ( ! $__cloud->activated() ) : ?>
						<?php
						Doc::learn_more(
							Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE ),
							__( 'Enable QUIC.cloud Services', 'litespeed-cache' ),
							true,
							'button litespeed-btn-warning'
						);
						?>
					<?php elseif ( ! empty( $cloud_summary['qc_activated'] ) && $cloud_summary['qc_activated'] != 'anonymous' ) : ?>
						<a href="<?php echo $__cloud->qc_link(); ?>" class="litespeed-link-with-icon" target="qc"><?php echo __( 'Go to QUIC.cloud dashboard', 'litespeed-cache' ); ?> <span class="dashicons dashicons-external"></span></a>
					<?php else : ?>
						<?php
						Doc::learn_more(
							Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_LINK ),
							__( 'Link to QUIC.cloud', 'litespeed-cache' ),
							true,
							'button litespeed-btn-warning'
						);
						?>
					<?php endif; ?>
				<?php endif; ?>
			</p>

			<div class="litespeed-dashboard-group">
				<hr>
				<div class="litespeed-flex-container">

					<div class="postbox litespeed-postbox litespeed-postbox-pagetime">
						<div class="inside">
							<h3 class="litespeed-title">
								<?php echo __( 'Page Load Time', 'litespeed-cache' ); ?>

								<?php if ( $can_page_load_time ) : ?>
									<?php $closest_server = Cloud::get_summary( 'server.' . CLoud::SVC_HEALTH ); ?>
									<?php if ( $closest_server ) : ?>
										<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_HEALTH ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php printf( __( 'Current closest Cloud server is %s.&#10;Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ); ?>" class="litespeed-title-right-icon"><i class='litespeed-quic-icon'></i> <small><?php echo __( 'Redetect', 'litespeed-cache' ); ?></small></a>
									<?php endif; ?>
								<?php endif; ?>
							</h3>

							<div>
								<div class="litespeed-flex-container">
									<?php if ( $can_page_load_time ) : ?>
										<?php if ( $health_scores['speed_before'] ) : ?>
											<div class="litespeed-score-col">
												<p class="litespeed-text-grey">
													<?php echo __( 'Before', 'litespeed-cache' ); ?>
												</p>
												<div class="litespeed-text-md litespeed-text-grey">
													<?php echo $health_scores['speed_before']; ?><span class="litespeed-text-large">s</span>
												</div>

											</div>
											<div class="litespeed-score-col">
												<p class="litespeed-text-grey">
													<?php echo __( 'After', 'litespeed-cache' ); ?>
												</p>
												<div class="litespeed-text-md litespeed-text-success">
													<?php echo $health_scores['speed_after']; ?><span class="litespeed-text-large">s</span>
												</div>
											</div>
											<div class="litespeed-score-col litespeed-score-col--imp">
												<p class="litespeed-text-grey" style="white-space: nowrap;">
													<?php echo __( 'Improved by', 'litespeed-cache' ); ?>
												</p>
												<div class="litespeed-text-jumbo litespeed-text-success">
													<?php echo $health_scores['speed_improved']; ?><span class="litespeed-text-large">%</span>
												</div>
											</div>
										<?php endif; ?>
									<?php else : ?>
										<div>
											<p><?php _e( 'You must be using one of the following products in order to measure Page Load Time:', 'litespeed-cache' ); ?></p>
											<a href="https://www.litespeedtech.com/products/litespeed-web-server" target="_blank">LiteSpeed Web Server</a>
											<br />
											<a href="https://openlitespeed.org/" target="_blank">OpenLiteSpeed Web Server</a>
											<br />
											<a href="https://www.litespeedtech.com/products/litespeed-web-adc" target="_blank">LiteSpeed Web ADC</a>
											<br />
											<a href="https://quic.cloud" target="_blank">QUIC.cloud CDN</a>
										</div>
									<?php endif; ?>

								</div>
							</div>
						</div>

						<?php if ( $can_page_load_time ) : ?>
							<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
								<?php if ( ! empty( $cloud_summary['last_request.health-speed'] ) ) : ?>
									<span class="litespeed-right10">
										<?php echo __( 'Last requested', 'litespeed-cache' ) . ': <span data-balloon-pos="up" aria-label="' . Utility::readable_time( $cloud_summary['last_request.health-speed'] ) . '">' . sprintf( __( ' %s ago', 'litespeed-cache' ), human_time_diff( $cloud_summary['last_request.health-speed'] ) ) . '</span>'; ?>
									</span>
								<?php endif; ?>

								<a href="<?php echo Utility::build_url( Router::ACTION_HEALTH, Health::TYPE_SPEED ); ?>" class="button button-secondary button-small">
									<span class="dashicons dashicons-update"></span>
									<?php echo __( 'Refresh', 'litespeed-cache' ); ?>
									<span class="screen-reader-text"><?php echo __( 'Refresh page load time', 'litespeed-cache' ); ?></span>
								</a>
							</div>
						<?php endif; ?>
					</div>

					<div class="postbox litespeed-postbox litespeed-postbox-pagespeed">
						<div class="inside">
							<h3 class="litespeed-title">
								<?php echo __( 'PageSpeed Score', 'litespeed-cache' ); ?>

								<?php $id = Base::O_GUEST; ?>
								<a href="<?php echo admin_url( 'admin.php?page=litespeed-general' ); ?>" class="litespeed-title-right-icon"><?php echo Lang::title( $id ); ?></a>
								<?php if ( $this->conf( $id ) ) : ?>
									<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
								<?php else : ?>
									<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
								<?php endif; ?>

							</h3>

							<div>

								<div class="litespeed-margin-bottom20">
									<div class="litespeed-row-flex" style="margin-left: -10px;">

										<?php if ( ! empty( $health_scores['score_before'] ) ) : ?>
											<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
												<p class="litespeed-text-grey litespeed-text-center">
													<?php echo __( 'Before', 'litespeed-cache' ); ?>
												</p>
												<div class="litespeed-promo-score">
													<?php echo GUI::pie( $health_scores['score_before'], 45, false, true, 'litespeed-pie-' . GUI::cls()->get_cls_of_pagescore( $health_scores['score_before'] ) ); ?>
												</div>
											</div>
											<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
												<p class="litespeed-text-grey litespeed-text-center">
													<?php echo __( 'After', 'litespeed-cache' ); ?>
												</p>
												<div class="litespeed-promo-score">
													<?php echo GUI::pie( $health_scores['score_after'], 45, false, true, 'litespeed-pie-' . GUI::cls()->get_cls_of_pagescore( $health_scores['score_after'] ) ); ?>
												</div>
											</div>
											<div class="litespeed-width-1-3 litespeed-padding-space litespeed-margin-x5">
												<p class="litespeed-text-grey" style="white-space: nowrap;">
													<?php echo __( 'Improved by', 'litespeed-cache' ); ?>
												</p>
												<div class="litespeed-postbox-score-improve litespeed-text-fern">
													<?php echo $health_scores['score_improved']; ?><span class="litespeed-text-large">%</span>
												</div>
											</div>
										<?php endif; ?>

									</div>

								</div>
							</div>
						</div>

						<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
							<?php if ( ! empty( $cloud_summary['last_request.health-score'] ) ) : ?>
								<span class="litespeed-right10">
									<?php echo __( 'Last requested', 'litespeed-cache' ) . ': <span data-balloon-pos="up" aria-label="' . Utility::readable_time( $cloud_summary['last_request.health-score'] ) . '">' . sprintf( __( ' %s ago', 'litespeed-cache' ), human_time_diff( $cloud_summary['last_request.health-score'] ) ) . '</span>'; ?>
								</span>
							<?php endif; ?>
							<a href="<?php echo Utility::build_url( Router::ACTION_HEALTH, Health::TYPE_SCORE ); ?>" class="button button-secondary button-small">
								<span class="dashicons dashicons-update"></span>
								<?php echo __( 'Refresh', 'litespeed-cache' ); ?>
								<span class="screen-reader-text"><?php echo __( 'Refresh page score', 'litespeed-cache' ); ?></span>
							</a>
						</div>

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
											<?php echo GUI::pie( $img_finished_percentage, 70, true ); ?>
										</div>
										<div>
											<div class="litespeed-dashboard-stats">
												<a data-litespeed-onlyonce class="button button-primary" 
												<?php
												if ( ! empty( $img_count['groups_new'] ) || ! empty( $img_count[ 'groups.' . Img_Optm::STATUS_RAW ] ) ) :
													?>
													href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_NEW_REQ ); ?>" 
													<?php
else :
	?>
													href='javascript:;' disabled <?php endif; ?>>
													<span class="dashicons dashicons-images-alt2"></span>&nbsp;<?php echo __( 'Send Optimization Request', 'litespeed-cache' ); ?>
												</a>
											</div>
										</div>
									</div>
									<p>
										<?php echo __( 'Total Reduction', 'litespeed-cache' ); ?>: <code><?php echo isset( $img_optm_summary['reduced'] ) ? Utility::real_size( $img_optm_summary['reduced'] ) : '-'; ?></code>
									</p>
									<p>
										<?php echo __( 'Images Pulled', 'litespeed-cache' ); ?>: <code><?php echo isset( $img_optm_summary['img_taken'] ) ? $img_optm_summary['img_taken'] : '-'; ?></code>
									</p>


								</div>
								<div class="litespeed-postbox-double-col">
									<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ) : ?>
										<p class="litespeed-success">
											<?php echo __( 'Images requested', 'litespeed-cache' ); ?>:
											<code>
												<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ); ?>
												(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ], 'image' ); ?>)
											</code>
										</p>
									<?php endif; ?>

									<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ) : ?>
										<p class="litespeed-success">
											<?php echo __( 'Images notified to pull', 'litespeed-cache' ); ?>:
											<code>
												<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ); ?>
												(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ], 'image' ); ?>)
											</code>

										</p>
									<?php endif; ?>

									<p>
										<?php echo __( 'Last Request', 'litespeed-cache' ); ?>: <code><?php echo ! empty( $img_optm_summary['last_requested'] ) ? Utility::readable_time( $img_optm_summary['last_requested'] ) : '-'; ?></code>
									</p>
									<p>
										<?php echo __( 'Last Pull', 'litespeed-cache' ); ?>: <code><?php echo ! empty( $img_optm_summary['last_pull'] ) ? Utility::readable_time( $img_optm_summary['last_pull'] ) : '-'; ?></code>
									</p>

									<?php
									$cache_list = array(
										Base::O_IMG_OPTM_AUTO   => Lang::title( Base::O_IMG_OPTM_AUTO ),
									);
									foreach ( $cache_list as $id => $title ) :
										?>
										<p>
											<?php if ( $this->conf( $id ) ) : ?>
												<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
											<?php else : ?>
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
								Base::O_CACHE         => __( 'Public Cache', 'litespeed-cache' ),
								Base::O_CACHE_PRIV    => __( 'Private Cache', 'litespeed-cache' ),
								Base::O_OBJECT        => __( 'Object Cache', 'litespeed-cache' ),
								Base::O_CACHE_BROWSER => __( 'Browser Cache', 'litespeed-cache' ),
							);
							foreach ( $cache_list as $id => $title ) :
								?>
								<p>
									<?php if ( $this->conf( $id ) ) : ?>
										<span class="litespeed-label-success litespeed-label-dashboard">ON</span>
									<?php else : ?>
										<span class="litespeed-label-danger litespeed-label-dashboard">OFF</span>
									<?php endif; ?>
									<?php echo esc_html( $title ); ?>
								</p>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="postbox litespeed-postbox litespeed-postbox-ccss">
						<div class="inside">
							<h3 class="litespeed-title">
								<?php echo __( 'Critical CSS', 'litespeed-cache' ); ?>
								<a href="<?php echo admin_url( 'admin.php?page=litespeed-page_optm#settings_css' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
							</h3>

							<?php if ( ! empty( $css_summary['last_request_ccss'] ) ) : ?>
								<p>
									<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $css_summary['last_request_ccss'] ) . '</code>'; ?>
								</p>
								<p>
									<?php echo __( 'Time to execute previous request', 'litespeed-cache' ) . ': <code>' . esc_html( $css_summary['last_spent_ccss'] ) . 's</code>'; ?>
								</p>
							<?php endif; ?>

							<p>
								<?php echo __( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo $ccss_count ?: '-'; ?></code>
								<a href="<?php echo $ccss_count ? Utility::build_url( Router::ACTION_CSS, CSS::TYPE_GEN_CCSS ) : 'javascript:;'; ?>" class="button button-secondary button-small 
													<?php
													if ( ! $ccss_count ) {
														echo 'disabled';}
													?>
								">
									<?php echo __( 'Force cron', 'litespeed-cache' ); ?>
								</a>
							</p>

						</div>

						<?php if ( ! empty( $cloud_summary['last_request.ccss'] ) ) : ?>
							<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
								<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary['last_request.ccss'] ); ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="postbox litespeed-postbox litespeed-postbox-ucss">
						<div class="inside">
							<h3 class="litespeed-title">
								<?php echo __( 'Unique CSS', 'litespeed-cache' ); ?>
								<a href="<?php echo admin_url( 'admin.php?page=litespeed-page_optm#settings_css' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
							</h3>

							<?php if ( ! empty( $ucss_summary['last_request'] ) ) : ?>
								<p>
									<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $ucss_summary['last_request'] ) . '</code>'; ?>
								</p>
								<p>
									<?php echo __( 'Time to execute previous request', 'litespeed-cache' ) . ': <code>' . esc_html( $ucss_summary['last_spent'] ) . 's</code>'; ?>
								</p>
							<?php endif; ?>

							<p>
								<?php echo __( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo $ucss_count ?: '-'; ?></code>
								<a href="<?php echo $ucss_count ? Utility::build_url( Router::ACTION_UCSS, UCSS::TYPE_GEN ) : 'javascript:;'; ?>" class="button button-secondary button-small 
													<?php
													if ( ! $ucss_count ) {
														echo 'disabled';}
													?>
								">
									<?php echo __( 'Force cron', 'litespeed-cache' ); ?>
								</a>
							</p>

						</div>

						<?php if ( ! empty( $cloud_summary['last_request.ucss'] ) ) : ?>
							<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
								<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary['last_request.ucss'] ); ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="postbox litespeed-postbox litespeed-postbox-lqip">
						<div class="inside">
							<h3 class="litespeed-title">
								<?php echo __( 'Low Quality Image Placeholder', 'litespeed-cache' ); ?>
								<a href="<?php echo admin_url( 'admin.php?page=litespeed-page_optm#settings_media' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
							</h3>

							<?php if ( ! empty( $placeholder_summary['last_request'] ) ) : ?>
								<p>
									<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $placeholder_summary['last_request'] ) . '</code>'; ?>
								</p>
								<p>
									<?php echo __( 'Time to execute previous request', 'litespeed-cache' ) . ': <code>' . esc_html( $placeholder_summary['last_spent'] ) . 's</code>'; ?>
								</p>
							<?php endif; ?>

							<p>
								<?php echo __( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo $placeholder_queue_count ?: '-'; ?></code>
								<a href="<?php echo $placeholder_queue_count ? Utility::build_url( Router::ACTION_PLACEHOLDER, Placeholder::TYPE_GENERATE ) : 'javascript:;'; ?>" class="button button-secondary button-small 
													<?php
													if ( ! $placeholder_queue_count ) {
														echo 'disabled';}
													?>
								">
									<?php echo __( 'Force cron', 'litespeed-cache' ); ?>
								</a>
							</p>

						</div>

						<?php if ( ! empty( $cloud_summary['last_request.lqip'] ) ) : ?>
							<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
								<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary['last_request.lqip'] ); ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="postbox litespeed-postbox litespeed-postbox-vpi">
						<div class="inside">
							<h3 class="litespeed-title">
								<?php echo __( 'Viewport Image', 'litespeed-cache' ); ?> (VPI)
								<a href="<?php echo admin_url( 'admin.php?page=litespeed-page_optm#settings_vpi' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
							</h3>

							<?php if ( ! empty( $vpi_summary['last_request'] ) ) : ?>
								<p>
									<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $vpi_summary['last_request'] ) . '</code>'; ?>
								</p>
								<p>
									<?php echo __( 'Time to execute previous request', 'litespeed-cache' ) . ': <code>' . esc_html( $vpi_summary['last_spent'] ) . 's</code>'; ?>
								</p>
							<?php endif; ?>

							<p>
								<?php echo __( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo $vpi_queue_count ?: '-'; ?></code>
								<a href="<?php echo $vpi_queue_count ? Utility::build_url( Router::ACTION_VPI, VPI::TYPE_GEN ) : 'javascript:;'; ?>" class="button button-secondary button-small 
													<?php
													if ( ! $vpi_queue_count ) {
														echo 'disabled';}
													?>
								">
									<?php echo __( 'Force cron', 'litespeed-cache' ); ?>
								</a>
							</p>

						</div>

						<?php if ( ! empty( $cloud_summary['last_request.vpi'] ) ) : ?>
							<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
								<?php echo __( 'Last requested', 'litespeed-cache' ) . ': ' . Utility::readable_time( $cloud_summary['last_request.vpi'] ); ?>
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
								<code><?php echo count( Crawler::cls()->list_crawlers() ); ?></code> <?php echo __( 'Crawler(s)', 'litespeed-cache' ); ?>
							</p>
							<p>
								<?php echo __( 'Currently active crawler', 'litespeed-cache' ); ?>: <code><?php echo esc_html( $crawler_summary['curr_crawler'] ); ?></code>
							</p>

							<?php if ( $crawler_summary['curr_crawler_beginning_time'] ) : ?>
								<p>
									<b><?php echo __( 'Current crawler started at', 'litespeed-cache' ); ?>:</b>
									<?php echo Utility::readable_time( $crawler_summary['curr_crawler_beginning_time'] ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $crawler_summary['last_start_time'] ) : ?>
								<p class='litespeed-desc'>
									<b><?php echo __( 'Last interval', 'litespeed-cache' ); ?>:</b>
									<?php echo Utility::readable_time( $crawler_summary['last_start_time'] ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $crawler_summary['end_reason'] ) : ?>
								<p class='litespeed-desc'>
									<b><?php echo __( 'Ended reason', 'litespeed-cache' ); ?>:</b>
									<?php echo esc_html( $crawler_summary['end_reason'] ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $crawler_summary['last_crawled'] ) : ?>
								<p class='litespeed-desc'>
									<?php printf( __( '<b>Last crawled:</b> %d item(s)', 'litespeed-cache' ), $crawler_summary['last_crawled'] ); ?>
								</p>
							<?php endif; ?>

						</div>
					</div>

					<div class="postbox litespeed-postbox litespeed-postbox-quiccloud
					<?php
					if ( empty( $cloud_summary['qc_activated'] ) || $cloud_summary['qc_activated'] != 'cdn' ) :
						?>
						litespeed-postbox--quiccloud<?php endif; ?>">
						<div class="inside">
							<h3 class="litespeed-title litespeed-dashboard-title--w-btn">
								<span class="litespeed-quic-icon"></span>QUIC.cloud CDN
								<?php if ( empty( $cloud_summary['qc_activated'] ) || $cloud_summary['qc_activated'] != 'cdn' ) : ?>
									<a href="https://www.quic.cloud/quic-cloud-services-and-features/litespeed-cache-service/" class="litespeed-title-right-icon" target="_blank"><?php echo __( 'Learn More', 'litespeed-cache' ); ?></a>
								<?php else : ?>
									<a href="<?php echo admin_url( 'admin.php?page=litespeed-cdn' ); ?>" class="litespeed-title-right-icon"><?php echo __( 'More', 'litespeed-cache' ); ?></a>
								<?php endif; ?>
							</h3>
							<?php if ( empty( $cloud_summary['qc_activated'] ) || $cloud_summary['qc_activated'] != 'cdn' ) : ?>
								<div class="litespeed-text-center litespeed-empty-space-medium">
									<p class="litespeed-margin-bottom20">
										<?php
										Doc::learn_more(
											Utility::build_url( Router::ACTION_CLOUD, $__cloud->activated() ? Cloud::TYPE_ENABLE_CDN : Cloud::TYPE_ACTIVATE ),
											'<span class="dashicons dashicons-saved"></span>' . __( 'Enable QUIC.cloud CDN', 'litespeed-cache' ),
											true,
											'button button-primary litespeed-button-cta'
										);
										?>
									</p>
									<p class="litespeed-margin-bottom10 litespeed-top20 litespeed-text-md">
										<strong class="litespeed-qc-text-gradient"><?php echo __( 'Best available WordPress performance', 'litespeed-cache' ); ?></strong>
									</p>
									<p class="litespeed-margin-bottom20 litespeed-margin-top-remove">
										<?php printf( __( 'Globally fast TTFB, easy setup, and <a %s>more</a>!', 'litespeed-cache' ), ' href="https://www.quic.cloud/quic-cloud-services-and-features/litespeed-cache-service/" target="_blank"' ); ?>
									</p>
								</div>
							<?php else : ?>
								<?php echo $__cloud->load_qc_status_for_dash( 'cdn_dash_mini' ); ?>
							<?php endif; ?>
						</div>
						<?php if ( $__cloud->activated() ) : ?>
							<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
								<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_SYNC_STATUS ); ?>" class="button button-<?php echo ( $cloud_summary['qc_activated'] != 'cdn' ? 'link' : 'secondary' ); ?> button-small">
									<?php if ( $cloud_summary['qc_activated'] == 'cdn' ) : ?>
										<span class="dashicons dashicons-update"></span>
									<?php endif; ?>
									<?php echo __( 'Refresh Status', 'litespeed-cache' ); ?>
									<span class="screen-reader-text"><?php echo __( 'Refresh QUIC.cloud status', 'litespeed-cache' ); ?></span>
								</a>
							</div>
						<?php endif; ?>
					</div>

					<?php $promo_mini = $__cloud->load_qc_status_for_dash( 'promo_mini' ); ?>
					<?php if ( $promo_mini ) : ?>
						<?php echo $promo_mini; ?>
					<?php endif; ?>

					<?php if ( $__cloud->activated() ) : ?>
						<?php $news = $__cloud->load_qc_status_for_dash( 'news_dash' ); ?>
						<?php if ( $news ) : ?>
							<div class="postbox litespeed-postbox">
								<div class="inside litespeed-text-center">
									<h3 class="litespeed-title">
										<?php echo __( 'News', 'litespeed-cache' ); ?>
									</h3>
									<div class="litespeed-top20">
										<?php echo $news; ?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>

				</div>

			</div>

		</div>
	</div>
</div>