<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$cloud_summarys = array();
$blogs          = Activation::get_network_ids();
foreach ( $blogs as $blog_id ) {
	switch_to_blog( $blog_id );
	$cloud_summarys[ home_url() ] = Cloud::get_summary();
}

?>

<div class="litespeed-dashboard">
<?php foreach ( $cloud_summarys as $home_url => $cloud_summary ) : ?>

	<div class="litespeed-dashboard-header">
		<h3 class="litespeed-dashboard-title">
			<?php echo __( 'Usage Statistics', 'litespeed-cache' ) . ': ' . $home_url; ?>
		</h3>
		<hr>
	</div>

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
				$used                = $quota = $pag_used = $pag_total = '-';
				$pag_width           = 0;
				if ( ! empty( $cloud_summary[ 'usage.' . $svc ] ) ) {
					$finished_percentage = floor( $cloud_summary[ 'usage.' . $svc ]['used'] * 100 / $cloud_summary[ 'usage.' . $svc ]['quota'] );
					$used                = $cloud_summary[ 'usage.' . $svc ]['used'];
					$quota               = $cloud_summary[ 'usage.' . $svc ]['quota'];
					$pag_used            = ! empty( $cloud_summary[ 'usage.' . $svc ]['pag_used'] ) ? $cloud_summary[ 'usage.' . $svc ]['pag_used'] : 0;
					$pag_bal             = ! empty( $cloud_summary[ 'usage.' . $svc ]['pag_bal'] ) ? $cloud_summary[ 'usage.' . $svc ]['pag_bal'] : 0;
					$pag_total           = $pag_used + $pag_bal;

					if ( $pag_total ) {
						$pag_width = round( $pag_used / $pag_total * 100 ) . '%';
					}

					if ( $svc == 'cdn' ) {
						$used      = Utility::real_size( $used * 1024 * 1024 );
						$quota     = Utility::real_size( $quota * 1024 * 1024 );
						$pag_used  = Utility::real_size( $pag_used * 1024 * 1024 );
						$pag_total = Utility::real_size( $pag_total * 1024 * 1024 );
					}
				}

				$percentage_bg = 'success';
				if ( $finished_percentage > 95 ) {
					$percentage_bg = 'danger';
				} elseif ( $finished_percentage > 85 ) {
					$percentage_bg = 'warning';
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
										<strong><?php echo esc_html( $used ); ?></strong>
										<?php if ( $used != $quota ) { ?>
											<span class="litespeed-desc"> of <?php echo esc_html( $quota ); ?></span>
										<?php } ?>
									</p>
								</div>
							</div>
						</div>
						<?php if ( $pag_total > 0 ) { ?>
							<p class="litespeed-dashboard-stats-payg" data-balloon-pos="up" aria-label="<?php echo __( 'Pay as You Go', 'litespeed-cache' ); ?>">
								<?php echo __( 'PAYG Balance', 'litespeed-cache' ); ?>: <strong><?php echo esc_html( $pag_bal ); ?></strong>
								<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo __( 'This Month Usage', 'litespeed-cache' ); ?>: <?php echo esc_html( $pag_used ); ?>">
									<span class="dashicons dashicons-info"></span>
									<span class="screen-reader-text"><?php echo __( 'Pay as You Go Usage Statistics', 'litespeed-cache' ); ?></span>
								</button>
							</p>
						<?php } ?>

						<?php if ( $svc == 'img_optm' ) { ?>
							<p class="litespeed-dashboard-stats-total">
								<?php echo __( 'Total Usage', 'litespeed-cache' ); ?>: <strong><?php echo esc_html( $total_used ); ?> / ∞</strong>
								<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo __( 'Total images optimized in this month', 'litespeed-cache' ); ?>">
									<span class="dashicons dashicons-info"></span>
								</button>
							</p>
							<div class="clear"></div>
						<?php } ?>
					</div>
				</div>

			<?php endforeach; ?>
	</div>

<?php endforeach; ?>
</div>