<?php
/**
 * LiteSpeed Cache Network Dashboard
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$cloud_summaries = array();
$blogs           = Activation::get_network_ids();
foreach ( $blogs as $network_blog_id ) {
	switch_to_blog( $network_blog_id );
	$cloud_summaries[ home_url() ] = Cloud::get_summary();
	// May need restore_current_blog();
}

?>

<div class="litespeed-dashboard">
<?php foreach ( $cloud_summaries as $home_url => $cloud_summary ) : ?>

	<div class="litespeed-dashboard-header">
		<h3 class="litespeed-dashboard-title">
			<?php echo esc_html( sprintf( __( 'Usage Statistics: %s', 'litespeed-cache' ), $home_url ) ); ?>
		</h3>
		<hr>
	</div>

	<div class="litespeed-dashboard-stats-wrapper">
		<?php
		$cat_list = array(
			'img_optm'  => esc_html__( 'Image Optimization', 'litespeed-cache' ),
			'page_optm' => esc_html__( 'Page Optimization', 'litespeed-cache' ),
			'cdn'       => esc_html__( 'CDN Bandwidth', 'litespeed-cache' ),
			'lqip'      => esc_html__( 'Low Quality Image Placeholder', 'litespeed-cache' ),
		);

		foreach ( $cat_list as $svc => $svc_title ) :
			$finished_percentage = 0;
			$total_used          = '-';
			$used                = '-';
			$quota               = '-';
			$pag_used            = '-';
			$pag_total           = '-';
			$pag_width           = 0;
			$pag_bal             = 0;

			if ( ! empty( $cloud_summary[ 'usage.' . $svc ] ) ) {
				$usage               = $cloud_summary[ 'usage.' . $svc ];
				$finished_percentage = floor( $usage['used'] * 100 / $usage['quota'] );
				$used                = $usage['used'];
				$quota               = $usage['quota'];
				$pag_used            = ! empty( $usage['pag_used'] ) ? $usage['pag_used'] : 0;
				$pag_bal             = ! empty( $usage['pag_bal'] ) ? $usage['pag_bal'] : 0;
				$pag_total           = $pag_used + $pag_bal;

				if ( $pag_total ) {
					$pag_width = round( $pag_used / $pag_total * 100 ) . '%';
				}

				if ( 'cdn' === $svc ) {
					$used      = Utility::real_size( $used * 1024 * 1024 );
					$quota     = Utility::real_size( $quota * 1024 * 1024 );
					$pag_used  = Utility::real_size( $pag_used * 1024 * 1024 );
					$pag_total = Utility::real_size( $pag_total * 1024 * 1024 );
				}

				if ( ! empty( $usage['total_used'] ) ) {
					$total_used = $usage['total_used'];
				}
			}

			$percentage_bg = 'success';
			if ( 95 < $finished_percentage ) {
				$percentage_bg = 'danger';
			} elseif ( 85 < $finished_percentage ) {
				$percentage_bg = 'warning';
			}
			?>

			<div class="postbox litespeed-postbox">
				<div class="inside">
					<h3 class="litespeed-title"><?php echo esc_html( $svc_title ); ?></h3>

					<div class="litespeed-flex-container">
						<div class="litespeed-icon-vertical-middle litespeed-pie-<?php echo esc_attr( $percentage_bg ); ?>">
							<?php echo wp_kses( GUI::pie( $finished_percentage, 60, false ), GUI::allowed_svg_tags() ); ?>
						</div>
						<div>
							<div class="litespeed-dashboard-stats">
								<h3><?php echo esc_html( 'img_optm' === $svc ? __( 'Fast Queue Usage', 'litespeed-cache' ) : __( 'Usage', 'litespeed-cache' ) ); ?></h3>
								<p>
									<strong><?php echo esc_html( $used ); ?></strong>
									<?php if ( $quota !== $used ) : ?>
										<span class="litespeed-desc"> / <?php echo esc_html( $quota ); ?></span>
									<?php endif; ?>
								</p>
							</div>
						</div>
					</div>

					<?php if ( 0 < $pag_total ) : ?>
						<p class="litespeed-dashboard-stats-payg" data-balloon-pos="up" aria-label="<?php echo esc_attr__( 'Pay as You Go', 'litespeed-cache' ); ?>">
							<?php esc_html_e( 'PAYG Balance', 'litespeed-cache' ); ?>: <strong><?php echo esc_html( $pag_bal ); ?></strong>
							<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo esc_attr( sprintf( __( 'This Month Usage: %s', 'litespeed-cache' ), esc_html( $pag_used ) ) ); ?>">
								<span class="dashicons dashicons-info"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Pay as You Go Usage Statistics', 'litespeed-cache' ); ?></span>
							</button>
						</p>
					<?php endif; ?>

					<?php if ( 'img_optm' === $svc ) : ?>
						<p class="litespeed-dashboard-stats-total">
							<?php esc_html_e( 'Total Usage', 'litespeed-cache' ); ?>: <strong><?php echo esc_html( $total_used ); ?> / ∞</strong>
							<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo esc_attr__( 'Total images optimized in this month', 'litespeed-cache' ); ?>">
								<span class="dashicons dashicons-info"></span>
							</button>
						</p>
						<div class="clear"></div>
					<?php endif; ?>
				</div>
			</div>

		<?php endforeach; ?>
	</div>

<?php endforeach; ?>
</div>