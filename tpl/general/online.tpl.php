<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$cloud_summary = Cloud::get_summary();

$__cloud = Cloud::cls();
$__cloud->finish_qc_activation( 'online' );

?>

<h3 class="litespeed-title-short">
	<?php echo __( 'QUIC.cloud Online Services', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://www.quic.cloud/quic-cloud-services-and-features/' ); ?>
</h3>

<div class="litespeed-desc"><?php echo __( 'QUIC.cloud provides CDN and online optimization services, and is not required. You may use many features of this plugin without QUIC.cloud.', 'litespeed-cache' ); ?></div>

<?php if ( $__cloud->activated() ) : ?>
	<div class="litespeed-callout notice notice-success inline">
		<h4><?php echo __( 'Current Cloud Nodes in Service', 'litespeed-cache' ); ?>
			<a class="litespeed-right litespeed-redetect" href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CLEAR_CLOUD, false, null, array( 'ref' => 'online' ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo __( 'Click to clear all nodes for further redetection.', 'litespeed-cache' ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to clear all cloud nodes?', 'litespeed-cache' ); ?>"><i class='litespeed-quic-icon'></i> <?php echo __( 'Redetect', 'litespeed-cache' ); ?></a>
		</h4>
		<p>
			<?php
			$has_service = false;
			foreach ( Cloud::$SERVICES as $svc ) {
				if ( isset( $cloud_summary[ 'server.' . $svc ] ) ) {
					$has_service = true;
					echo '<p><strong>Service:</strong> <code>' . $svc . '</code> <strong>Node:</strong> <code>' . $cloud_summary[ 'server.' . $svc ] . '</code> <strong>Connected Date:</strong> <code>' . Utility::readable_time( $cloud_summary[ 'server_date.' . $svc ] ) . '</code></p>';
				}
			}
			if ( ! $has_service ) {
				echo __( 'No cloud services currently in use', 'litespeed-cache' );
			}
			?>
		</p>
	</div>
<?php endif; ?>

<?php if ( ! $__cloud->activated() ) : ?>
	<h4 class="litespeed-text-md litespeed-top30"><span class="dashicons dashicons-no-alt litespeed-danger"></span>&nbsp;<?php echo __( 'QUIC.cloud Integration Disabled', 'litespeed-cache' ); ?></h4>
	<p><?php echo __( 'Speed up your WordPress site even further with QUIC.cloud Online Services and CDN.', 'litespeed-cache' ); ?></p>
	<div class="litespeed-desc"><?php echo __( 'Free monthly quota available.', 'litespeed-cache' ); ?></div>
	<p><a class="button button-primary" href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE, false, null, array( 'ref' => 'online' ) ); ?>">
			<span class="dashicons dashicons-yes"></span>
			<?php _e( 'Enable QUIC.cloud services', 'litespeed-cache' ); ?>
		</a></p>


	<div>
		<h3 class="litespeed-title-section"><?php echo __( 'Online Services', 'litespeed-cache' ); ?></h3>
		<p><?php echo __( 'QUIC.cloud\'s Online Services improve your site in the following ways:', 'litespeed-cache' ); ?></p>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( '<strong>Image Optimization</strong> gives you smaller image file sizes that transmit faster.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( '<strong>Page Optimization</strong> streamlines page styles and visual elements for faster loading.', 'litespeed-cache' ); ?></li>
		</ul>

		<h4 class="litespeed-text-md litespeed-margin-bottom-remove">Image Optimization</h4>
		<p><?php echo __( 'QUIC.cloud\'s Image Optimization service does the following:', 'litespeed-cache' ); ?></p>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( 'Processes your uploaded PNG and JPG images to produce smaller versions that don\'t sacrifice quality.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( 'Optionally creates next-generation WebP or AVIF image files.', 'litespeed-cache' ); ?></li>
		</ul>
		<p><?php echo __( 'Processing for PNG, JPG, and WebP image formats is free. AVIF is available for a fee.', 'litespeed-cache' ); ?> <a href="https://www.quic.cloud/quic-cloud-services-and-features/image-optimization-service/" target="_blank"><?php echo __( 'Learn More', 'litespeed-cache' ); ?></a></p>

		<h4 class="litespeed-text-md litespeed-margin-bottom-remove">Page Optimization</h4>
		<p><?php echo __( 'QUIC.cloud\'s Page Optimization services address CSS bloat, and improve the user experience during page load, which can lead to improved page speed scores.', 'litespeed-cache' ); ?></p>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( '<strong>Critical CSS (CCSS)</strong> loads visible above-the-fold content faster and with full styling.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( '<strong>Unique CSS (UCSS)</strong> removes unused style definitions for a speedier page load overall.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( '<strong>Low Quality Image Placeholder (LQIP)</strong> gives your imagery a more pleasing look as it lazy loads.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( '<strong>Viewport Images (VPI)</strong> provides a well-polished fully-loaded view above the fold.', 'litespeed-cache' ); ?></li>
		</ul>

		<div>
			<a href="https://www.quic.cloud/quic-cloud-services-and-features/page-optimization/"><?php echo __( 'Learn More', 'litespeed-cache' ); ?></a>
		</div>
	</div>

	<div>
		<h3 class="litespeed-title-section"><?php echo __( 'Content Delivery Network', 'litespeed-cache' ); ?></h3>

		<h4 class="litespeed-text-md litespeed-margin-bottom-remove">QUIC.cloud CDN:</h4>
		<ul>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( 'Caches your entire site, including dynamic content and <strong>ESI blocks</strong>.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( 'Delivers global coverage with a growing <strong>network of 80+ PoPs</strong>.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( 'Provides <strong>security at the CDN level</strong>, protecting your server from attack.', 'litespeed-cache' ); ?></li>
			<li><span class="dashicons dashicons-saved litespeed-primary"></span> <?php echo __( 'Offers optional <strong>built-in DNS service</strong> to simplify CDN onboarding.', 'litespeed-cache' ); ?></li>
		</ul>

		<div>
			<a href="https://www.quic.cloud/quic-cloud-services-and-features/quic-cloud-cdn-service/"><?php echo __( 'Learn More', 'litespeed-cache' ); ?></a>
		</div>

		<hr class="litespeed-hr-with-space">

		<p class="litespeed-desc"><?php echo __( 'In order to use most QUIC.cloud services, you need quota. QUIC.cloud gives you free quota every month, but if you need more, you can purchase it.', 'litespeed-cache' ); ?> <a href="https://docs.quic.cloud/billing/services/" target="_blank">Learn More</a></p>

		<div class="litespeed-flex litespeed-flex-align-center">
			<a class="button button-secondary litespeed-right20" href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE, false, null, array( 'ref' => 'online' ) ); ?>">
				<span class="dashicons dashicons-yes"></span>
				<?php _e( 'Enable QUIC.cloud services', 'litespeed-cache' ); ?>
			</a>
		</div>
	</div>


<?php elseif ( ! empty( $cloud_summary['qc_activated'] ) && ( $cloud_summary['qc_activated'] == 'linked' || $cloud_summary['qc_activated'] == 'cdn' ) ) : ?>
	<h4 class="litespeed-text-md litespeed-top30"><span class="dashicons dashicons-saved litespeed-success"></span>&nbsp;<?php echo __( 'QUIC.cloud Integration Enabled', 'litespeed-cache' ); ?></h4>
	<p><?php echo __( 'Your site is connected and ready to use QUIC.cloud Online Services.', 'litespeed-cache' ); ?>
		<?php if ( empty( $cloud_summary['partner'] ) ) : ?>
			<a href="<?php echo $__cloud->qc_link(); ?>" class="litespeed-link-with-icon" target="_blank"><?php echo __( 'Go to QUIC.cloud dashboard', 'litespeed-cache' ); ?> <span class="dashicons dashicons-external"></span></a>
		<?php endif; ?>
	</p>

	<ul>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php echo __( 'Page Optimization', 'litespeed-cache' ); ?></li>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php echo __( 'Image Optimization', 'litespeed-cache' ); ?></li>
		<?php if ( $cloud_summary['qc_activated'] == 'cdn' ) : ?>
			<li><span class="dashicons dashicons-yes litespeed-success"></span> CDN - <?php echo __( 'Enabled', 'litespeed-cache' ); ?></li>
		<?php else : ?>
			<li><span class="dashicons dashicons-no-alt litespeed-default"></span> CDN - <span class="litespeed-default"><?php echo __( 'Disabled', 'litespeed-cache' ); ?></span></li>
		<?php endif; ?>
	</ul>

<?php else : ?>
	<h4 class="litespeed-text-md litespeed-top30"><span class="dashicons dashicons-saved litespeed-success"></span>&nbsp;<?php echo __( 'QUIC.cloud Integration Enabled with limitations', 'litespeed-cache' ); ?></h4>
	<p><?php echo __( 'Your site is connected and using QUIC.cloud Online Services as an <strong>anonymous user</strong>. The CDN function and certain features of optimization services are not available for anonymous users. Link to QUIC.cloud to use the CDN and all available Online Services features.', 'litespeed-cache' ); ?></p>
	<div class="litespeed-desc"><?php echo __( 'Free monthly quota available.', 'litespeed-cache' ); ?></div>

	<ul>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php echo __( 'Page Optimization', 'litespeed-cache' ); ?></li>
		<li><span class="dashicons dashicons-yes litespeed-success"></span> <?php echo __( 'Image Optimization', 'litespeed-cache' ); ?></li>
		<li><span class="dashicons dashicons-no-alt litespeed-danger"></span> CDN - <?php echo __( 'not available for anonymous users', 'litespeed-cache' ); ?></li>
	</ul>

	<p><a class="button button-primary" href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_ACTIVATE, false, null, array( 'ref' => 'online' ) ); ?>"><span class="dashicons dashicons-yes"></span><?php _e( 'Link to QUIC.cloud', 'litespeed-cache' ); ?></a></p>
<?php endif; ?>


<?php if ( $__cloud->activated() ) : ?>
	<div class="litespeed-empty-space-medium"></div>
	<div class="litespeed-column-with-boxes-footer">
		<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_RESET, false, null, array( 'ref' => 'online' ) ); ?>" class="litespeed-link-with-icon litespeed-danger" data-litespeed-cfm="<?php echo __( 'Are you sure you want to disconnect from QUIC.cloud? This will not remove any data from the QUIC.cloud dashboard.', 'litespeed-cache' ); ?>"><span class="dashicons dashicons-dismiss"></span><?php echo __( 'Disconnect from QUIC.cloud', 'litespeed-cache' ); ?></a>
		<div class="litespeed-desc litespeed-margin-bottom-remove"><?php echo __( 'Remove QUIC.cloud integration from this site. Note: QUIC.cloud data will be preserved so you can re-enable services at any time. If you want to fully remove your site from QUIC.cloud, delete the domain through the QUIC.cloud Dashboard first.', 'litespeed-cache' ); ?></div>
	</div>
<?php endif; ?>