<?php
/**
 * LiteSpeed Cache QUIC.cloud Online Services
 *
 * Manages QUIC.cloud online services integration for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$cloud_summary = Cloud::get_summary();

$cloud_instance = Cloud::cls();
$cloud_instance->finish_qc_activation( 'online' );
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'QUIC.cloud Online Services', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://www.quic.cloud/quic-cloud-services-and-features/' ); ?>
</h3>

<div class="litespeed-desc"><?php esc_html_e( 'QUIC.cloud provides CDN and online optimization services, and is not required. You may use many features of this plugin without QUIC.cloud.', 'litespeed-cache' ); ?></div>

<?php if ( $cloud_instance->activated() ) : ?>
	<div class="litespeed-callout notice notice-success inline">
		<h4><?php esc_html_e( 'Current Cloud Nodes in Service', 'litespeed-cache' ); ?>
			<a class="litespeed-right litespeed-redetect" href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CLEAR_CLOUD, false, null, array( 'ref' => 'online' ) ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php esc_html_e( 'Click to clear all nodes for further redetection.', 'litespeed-cache' ); ?>' data-litespeed-cfm="<?php esc_html_e( 'Are you sure you want to clear all cloud nodes?', 'litespeed-cache' ); ?>"><i class='litespeed-quic-icon'></i> <?php esc_html_e( 'Redetect', 'litespeed-cache' ); ?></a>
		</h4>
		<p>
			<?php
			$has_service = false;
			foreach ( Cloud::$services as $svc ) {
				if ( isset( $cloud_summary[ 'server.' . $svc ] ) ) {
					$has_service = true;
					printf(
						'<p><strong>%1$s</strong> <code>%2$s</code> <strong>%3$s</strong> <code>%4$s</code> <strong>%5$s</strong> <code>%6$s</code></p>',
						esc_html__( 'Service:', 'litespeed-cache' ),
						esc_html( $svc ),
						esc_html__( 'Node:', 'litespeed-cache' ),
						esc_html( $cloud_summary[ 'server.' . $svc ] ),
						esc_html__( 'Connected Date:', 'litespeed-cache' ),
						esc_html( Utility::readable_time( $cloud_summary[ 'server_date.' . $svc ] ) )
					);
				}
			}
			if ( ! $has_service ) {
				esc_html_e( 'No cloud services currently in use', 'litespeed-cache' );
			}
			?>
		</p>
	</div>
<?php endif; ?>

<?php if ( ! $cloud_instance->activated() ) : ?>
	<h4 class="litespeed-text-md litespeed-top30"><span class="dashicons dashicons-no-alt litespeed-danger"></span> <?php esc_html_e( 'QUIC.cloud Integration Disabled', 'litespeed-cache' ); ?></h4>
	<p><?php esc_html_e( 'Speed up your WordPress site even further with QUIC.cloud Online Services and CDN.', 'litespeed-cache' ); ?></p>
	<div class="litespeed-desc"><?php esc_html_e( 'Free monthly quota available.', 'litespeed-cache' ); ?></div>
	<p><a class="button button-primary" href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE, false, null, array( 'ref' => 'online' ) ) ); ?>">
			<span class="dashicons dashicons-yes"></span>
			<?php esc_html_e( 'Enable QUIC.cloud services', 'litespeed-cache' ); ?>
		</a></p>

	<div>
		<h3 class="litespeed-title-section"><?php esc_html_e( 'Online Services', 'litespeed-cache' ); ?></h3>
		<p><?php esc_html_e( "QUIC.cloud's Online Services improve your site in the following ways:", 'litespeed-cache' ); ?></p>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( '<strong>Image Optimization</strong> gives you smaller image file sizes that transmit faster.', 'litespeed-cache' ) ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( '<strong>Page Optimization</strong> streamlines page styles and visual elements for faster loading.', 'litespeed-cache' ) ); ?></li>
		</ul>

		<h4 class="litespeed-text-md litespeed-margin-bottom-remove"><?php esc_html_e( 'Image Optimization', 'litespeed-cache' ); ?></h4>
		<p><?php esc_html_e( "QUIC.cloud's Image Optimization service does the following:", 'litespeed-cache' ); ?></p>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php esc_html_e( "Processes your uploaded PNG and JPG images to produce smaller versions that don't sacrifice quality.", 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php esc_html_e( 'Optionally creates next-generation WebP or AVIF image files.', 'litespeed-cache' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Processing for PNG, JPG, and WebP image formats is free. AVIF is available for a fee.', 'litespeed-cache' ); ?> <a href="https://www.quic.cloud/quic-cloud-services-and-features/image-optimization-service/" target="_blank"><?php esc_html_e( 'Learn More', 'litespeed-cache' ); ?></a></p>

		<h4 class="litespeed-text-md litespeed-margin-bottom-remove"><?php esc_html_e( 'Page Optimization', 'litespeed-cache' ); ?></h4>
		<p><?php esc_html_e( "QUIC.cloud's Page Optimization services address CSS bloat, and improve the user experience during page load, which can lead to improved page speed scores.", 'litespeed-cache' ); ?></p>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( '<strong>Critical CSS (CCSS)</strong> loads visible above-the-fold content faster and with full styling.', 'litespeed-cache' ) ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( '<strong>Unique CSS (UCSS)</strong> removes unused style definitions for a speedier page load overall.', 'litespeed-cache' ) ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( '<strong>Low Quality Image Placeholder (LQIP)</strong> gives your imagery a more pleasing look as it lazy loads.', 'litespeed-cache' ) ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( '<strong>Viewport Images (VPI)</strong> provides a well-polished fully-loaded view above the fold.', 'litespeed-cache' ) ); ?></li>
		</ul>

		<div>
			<a href="https://www.quic.cloud/quic-cloud-services-and-features/page-optimization/"><?php esc_html_e( 'Learn More', 'litespeed-cache' ); ?></a>
		</div>
	</div>

	<div>
		<h3 class="litespeed-title-section"><?php esc_html_e( 'Content Delivery Network', 'litespeed-cache' ); ?></h3>

		<h4 class="litespeed-text-md litespeed-margin-bottom-remove"><?php esc_html_e( 'QUIC.cloud CDN:', 'litespeed-cache' ); ?></h4>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( 'Caches your entire site, including dynamic content and <strong>ESI blocks</strong>.', 'litespeed-cache' ) ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( 'Delivers global coverage with a growing <strong>network of 80+ PoPs</strong>.', 'litespeed-cache' ) ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( 'Provides <strong>security at the CDN level</strong>, protecting your server from attack.', 'litespeed-cache' ) ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo wp_kses_post( __( 'Offers optional <strong>built-in DNS service</strong> to simplify CDN onboarding.', 'litespeed-cache' ) ); ?></li>
		</ul>

		<div>
			<a href="https://www.quic.cloud/quic-cloud-services-and-features/quic-cloud-cdn-service/"><?php esc_html_e( 'Learn More', 'litespeed-cache' ); ?></a>
		</div>

		<hr class="litespeed-hr-with-space">

		<p class="litespeed-desc"><?php esc_html_e( 'In order to use most QUIC.cloud services, you need quota. QUIC.cloud gives you free quota every month, but if you need more, you can purchase it.', 'litespeed-cache' ); ?> <a href="https://docs.quic.cloud/billing/services/" target="_blank"><?php esc_html_e( 'Learn More', 'litespeed-cache' ); ?></a></p>

		<div class="litespeed-flex litespeed-flex-align-center">
			<a class="button button-secondary litespeed-right20" href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE, false, null, array( 'ref' => 'online' ) ) ); ?>">
				<span class="dashicons dashicons-yes"></span>
				<?php esc_html_e( 'Enable QUIC.cloud services', 'litespeed-cache' ); ?>
			</a>
		</div>
	</div>

<?php elseif ( ! empty( $cloud_summary['qc_activated'] ) && ( 'linked' === $cloud_summary['qc_activated'] || 'cdn' === $cloud_summary['qc_activated'] ) ) : ?>
	<h4 class="litespeed-text-md litespeed-top30"><span class="dashicons dashicons-saved litespeed-success"></span> <?php esc_html_e( 'QUIC.cloud Integration Enabled', 'litespeed-cache' ); ?></h4>
	<p><?php esc_html_e( 'Your site is connected and ready to use QUIC.cloud Online Services.', 'litespeed-cache' ); ?>
		<?php if ( empty( $cloud_summary['partner'] ) ) : ?>
			<a href="<?php echo esc_url( $cloud_instance->qc_link() ); ?>" class="litespeed-link-with-icon" target="_blank"><?php esc_html_e( 'Go to QUIC.cloud dashboard', 'litespeed-cache' ); ?> <span class="dashicons dashicons-external"></span></a>
		<?php endif; ?>
	</p>

	<ul>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php esc_html_e( 'Page Optimization', 'litespeed-cache' ); ?></li>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php esc_html_e( 'Image Optimization', 'litespeed-cache' ); ?></li>
		<?php if ( 'cdn' === $cloud_summary['qc_activated'] ) : ?>
			<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php esc_html_e( 'CDN - Enabled', 'litespeed-cache' ); ?></li>
		<?php else : ?>
			<li><span class="dashicons dashicons-no-alt litespeed-default"></span> <span class="litespeed-default"><?php esc_html_e( 'CDN - Disabled', 'litespeed-cache' ); ?></span></li>
		<?php endif; ?>
	</ul>

<?php else : ?>
	<h4 class="litespeed-text-md litespeed-top30"><span class="dashicons dashicons-saved litespeed-success"></span> <?php esc_html_e( 'QUIC.cloud Integration Enabled with limitations', 'litespeed-cache' ); ?></h4>
	<p><?php echo wp_kses_post( __( 'Your site is connected and using QUIC.cloud Online Services as an <strong>anonymous user</strong>. The CDN function and certain features of optimization services are not available for anonymous users. Link to QUIC.cloud to use the CDN and all available Online Services features.', 'litespeed-cache' ) ); ?></p>
	<div class="litespeed-desc"><?php esc_html_e( 'Free monthly quota available.', 'litespeed-cache' ); ?></div>

	<ul>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php esc_html_e( 'Page Optimization', 'litespeed-cache' ); ?></li>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php esc_html_e( 'Image Optimization', 'litespeed-cache' ); ?></li>
		<li><span class="dashicons dashicons-no-alt litespeed-danger"></span> <?php esc_html_e( 'CDN - not available for anonymous users', 'litespeed-cache' ); ?></li>
	</ul>

	<p><a class="button button-primary" href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE, false, null, array( 'ref' => 'online' ) ) ); ?>"><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Link to QUIC.cloud', 'litespeed-cache' ); ?></a></p>
<?php endif; ?>

<?php if ( $cloud_instance->activated() ) : ?>
	<div class="litespeed-empty-space-medium"></div>
	<div class="litespeed-column-with-boxes-footer">
		<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_RESET, false, null, array( 'ref' => 'online' ) ) ); ?>" class="litespeed-link-with-icon litespeed-danger" data-litespeed-cfm="<?php esc_html_e( 'Are you sure you want to disconnect from QUIC.cloud? This will not remove any data from the QUIC.cloud dashboard.', 'litespeed-cache' ); ?>"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Disconnect from QUIC.cloud', 'litespeed-cache' ); ?></a>
		<div class="litespeed-desc litespeed-margin-bottom-remove"><?php esc_html_e( 'Remove QUIC.cloud integration from this site. Note: QUIC.cloud data will be preserved so you can re-enable services at any time. If you want to fully remove your site from QUIC.cloud, delete the domain through the QUIC.cloud Dashboard first.', 'litespeed-cache' ); ?></div>
	</div>
<?php endif; ?>