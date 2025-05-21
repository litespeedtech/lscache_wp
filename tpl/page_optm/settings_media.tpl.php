<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$placeholder_summary = Placeholder::get_summary();

$closest_server = Cloud::get_summary( 'server.' . Cloud::SVC_LQIP );

$lqip_queue = $this->load_queue( 'lqip' );

?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Media Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#media-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $id = Base::O_MEDIA_LAZY; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Load images only when they enter the viewport.', 'litespeed-cache' ); ?>
					<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ); ?>
					<br />
					<font class="litespeed-success">
						💡
						<a href="https://docs.litespeedtech.com/lscache/lscwp/pageopt/#lazy-load-images" target="_blank"><?php echo __( 'Adding Style to Your Lazy-Loaded Images', 'litespeed-cache' ); ?></a>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_LAZY_PLACEHOLDER; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_input( $id, 'litespeed-input-long' ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Specify a base64 image to be used as a simple placeholder while images finish loading.', 'litespeed-cache' ); ?>
					<br /><?php printf( __( 'This can be predefined in %2$s as well using constant %1$s, with this setting taking priority.', 'litespeed-cache' ), '<code>LITESPEED_PLACEHOLDER</code>', '<code>wp-config.php</code>' ); ?>
					<br /><?php printf( __( 'By default a gray image placeholder %s will be used.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=</code>' ); ?>
					<br /><?php printf( __( 'For example, %s can be used for a transparent placeholder.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7</code>' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_PLACEHOLDER_RESP; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<?php Doc::maybe_on_by_gm( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Responsive image placeholders can help to reduce layout reshuffle when images are loaded.', 'litespeed-cache' ); ?>
					<?php echo __( 'This will generate the placeholder with same dimensions as the image if it has the width and height attributes.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_PLACEHOLDER_RESP_SVG; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_input( $id, 'litespeed-input-long' ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Specify an SVG to be used as a placeholder when generating locally.', 'litespeed-cache' ); ?>
					<?php echo __( 'It will be converted to a base64 SVG placeholder on-the-fly.', 'litespeed-cache' ); ?>
					<br /><?php printf( __( 'Variables %s will be replaced with the corresponding image properties.', 'litespeed-cache' ), '<code>{width} {height}</code>' ); ?>
					<br /><?php printf( __( 'Variables %s will be replaced with the configured background color.', 'litespeed-cache' ), '<code>{color}</code>' ); ?>
					<br /><?php $this->recommended( $id ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_PLACEHOLDER_RESP_COLOR; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_input( $id, null, null, 'color' ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Specify the responsive placeholder SVG color.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $id ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_LQIP; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<?php Doc::maybe_on_by_gm( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Use QUIC.cloud LQIP (Low Quality Image Placeholder) generator service for responsive image previews while loading.', 'litespeed-cache' ); ?>
					<br /><?php echo __( 'Keep this off to use plain color placeholders.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#lqip-cloud-generator' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_LQIP_QUAL; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_input( $id, 'litespeed-input-short' ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Specify the quality when generating LQIP.', 'litespeed-cache' ); ?>
					<br /><?php echo __( 'Larger number will generate higher resolution quality placeholder, but will result in larger files which will increase page size and consume more points.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $id ); ?>
					<?php $this->_validate_ttl( $id, 1, 20 ); ?>
					<br />💡 <?php printf( __( 'Changes to this setting do not apply to already-generated LQIPs. To regenerate existing LQIPs, please %s first from the admin bar menu.', 'litespeed-cache' ), '<code>' . __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'LQIP Cache', 'litespeed-cache' ) . '</code>' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_LQIP_MIN_W; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_input( $id, 'litespeed-input-short' ); ?> x
				<?php $this->build_input( Base::O_MEDIA_LQIP_MIN_H, 'litespeed-input-short' ); ?>
				<?php echo __( 'pixels', 'litespeed-cache' ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'LQIP requests will not be sent for images where both width and height are smaller than these dimensions.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $id ); ?>
					<?php $this->_validate_ttl( $id, 10, 800 ); ?>
					<?php $this->_validate_ttl( Base::O_MEDIA_LQIP_MIN_H, 10, 800 ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_PLACEHOLDER_RESP_ASYNC; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Automatically generate LQIP in the background via a cron-based queue.', 'litespeed-cache' ); ?>
					<?php
					printf(
						__( 'If set to %1$s, before the placeholder is localized, the %2$s configuration will be used.', 'litespeed-cache' ),
						'<code>' . __( 'ON', 'litespeed-cache' ) . '</code>',
						'<code>' . Lang::title( Base::O_MEDIA_PLACEHOLDER_RESP_SVG ) . '</code>'
					);
					?>
					<?php printf( __( 'If set to %s this is done in the foreground, which may slow down page load.', 'litespeed-cache' ), '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#generate-lqip-in-background' ); ?>
				</div>

				<div class="litespeed-desc">
					<?php if ( $placeholder_summary ) : ?>
						<?php if ( ! empty( $placeholder_summary['last_request'] ) ) : ?>
							<p>
								<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $placeholder_summary['last_request'] ) . '</code>'; ?>
							</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $closest_server ) : ?>
						<a class="litespeed-redetect" href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_LQIP ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php printf( __( 'Current closest Cloud server is %s.&#10; Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ); ?>"><i class='litespeed-quic-icon'></i> <?php echo __( 'Redetect', 'litespeed-cache' ); ?></a>
					<?php endif; ?>

					<?php if ( ! empty( $lqip_queue ) ) : ?>
						<div class="litespeed-callout notice notice-warning inline">
							<h4>
								<?php echo __( 'Size list in queue waiting for cron', 'litespeed-cache' ); ?> ( <?php echo count( $lqip_queue ); ?> )
								<a href="<?php echo Utility::build_url( Router::ACTION_PLACEHOLDER, Placeholder::TYPE_CLEAR_Q ); ?>" class="button litespeed-btn-warning litespeed-right">Clear</a>
							</h4>
							<p>
								<?php
								$i = 0;
								foreach ( $lqip_queue as $k => $v ) :
									?>
									<?php if ( $i++ > 20 ) : ?>
										<?php echo '...'; ?>
										<?php break; ?>
									<?php endif; ?>

									<?php echo $v; ?>
									<br />
								<?php endforeach; ?>
							</p>
						</div>
						<a href="<?php echo Utility::build_url( Router::ACTION_PLACEHOLDER, Placeholder::TYPE_GENERATE ); ?>" class="button litespeed-btn-success">
							<?php echo __( 'Run Queue Manually', 'litespeed-cache' ); ?>
						</a>
						<?php Doc::queue_issues(); ?>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_IFRAME_LAZY; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Load iframes only when they enter the viewport.', 'litespeed-cache' ); ?>
					<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_MEDIA_ADD_MISSING_SIZES; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Set an explicit width and height on image elements to reduce layout shifts and improve CLS (a Core Web Vitals metric).', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://web.dev/optimize-cls/#images-without-dimensions' ); ?>

					<br />
					<font class="litespeed-warning litespeed-left10">
						⚠️ <?php echo __( 'Notice', 'litespeed-cache' ); ?>: <?php printf( __( '%s must be turned ON for this setting to work.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_MEDIA_LAZY ) . '</code>' ); ?>
					</font>

					<br />
					<font class="litespeed-success">
						<?php echo __( 'API', 'litespeed-cache' ); ?>:
						<?php printf( __( 'Use %1$s to bypass remote image dimension check when %2$s is ON.', 'litespeed-cache' ), '<code>add_filter( "litespeed_media_ignore_remote_missing_sizes", "__return_true" );</code>', '<code>' . Lang::title( Base::O_MEDIA_ADD_MISSING_SIZES ) . '</code>' ); ?>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_JPG_QUALITY; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_input( $id, 'litespeed-input-short' ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'The image compression quality setting of WordPress out of 100.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $id ); ?>
					<?php $this->_validate_ttl( $id, 0, 100 ); ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>