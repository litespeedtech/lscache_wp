<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$cloud_summarys = array();
$blogs = Activation::get_network_ids();
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
				'img_optm'	=> __( 'Image Optimization', 'litespeed-cache' ),
				'ccss'		=> __( 'CCSS', 'litespeed-cache' ),
				'cdn'		=> __( 'CDN Bandwidth', 'litespeed-cache' ),
				'lqip'		=> __( 'LQIP', 'litespeed-cache' ),
			);

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

<?php endforeach; ?>
</div>