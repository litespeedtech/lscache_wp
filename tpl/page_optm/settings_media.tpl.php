<?php
/**
 * LiteSpeed Cache Media Settings
 *
 * Renders the media settings interface for LiteSpeed Cache, including lazy loading, placeholders, and image optimization options.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$__admin_display     = Admin_Display::cls();
$placeholder_summary = Placeholder::get_summary();
$closest_server      = Cloud::get_summary( 'server.' . Cloud::SVC_LQIP );

$lqip_queue = $this->load_queue( 'lqip' );

$scaled_size = apply_filters( 'big_image_size_threshold', 2560 ) . 'px';

?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Media Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#media-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_LAZY; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Load images only when they enter the viewport.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ); ?>
					<br />
					<font class="litespeed-success">
						💡
						<a href="https://docs.litespeedtech.com/lscache/lscwp/pageopt/#lazy-load-images" target="_blank"><?php esc_html_e( 'Adding Style to Your Lazy-Loaded Images', 'litespeed-cache' ); ?></a>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_LAZY_PLACEHOLDER; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-long' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify a base64 image to be used as a simple placeholder while images finish loading.', 'litespeed-cache' ); ?>
					<br /><?php printf( esc_html__( 'This can be predefined in %2$s as well using constant %1$s, with this setting taking priority.', 'litespeed-cache' ), '<code>LITESPEED_PLACEHOLDER</code>', '<code>wp-config.php</code>' ); ?>
					<br /><?php printf( esc_html__( 'By default a gray image placeholder %s will be used.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=</code>' ); ?>
					<br /><?php printf( esc_html__( 'For example, %s can be used for a transparent placeholder.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7</code>' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_PLACEHOLDER_RESP; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<?php Doc::maybe_on_by_gm( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Responsive image placeholders can help to reduce layout reshuffle when images are loaded.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'This will generate the placeholder with same dimensions as the image if it has the width and height attributes.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_PLACEHOLDER_RESP_SVG; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-long' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify an SVG to be used as a placeholder when generating locally.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'It will be converted to a base64 SVG placeholder on-the-fly.', 'litespeed-cache' ); ?>
					<br /><?php printf( esc_html__( 'Variables %s will be replaced with the corresponding image properties.', 'litespeed-cache' ), '<code>{width} {height}</code>' ); ?>
					<br /><?php printf( esc_html__( 'Variables %s will be replaced with the configured background color.', 'litespeed-cache' ), '<code>{color}</code>' ); ?>
					<br /><?php $this->recommended( $option_id ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_PLACEHOLDER_RESP_COLOR; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, null, null, 'color' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify the responsive placeholder SVG color.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $option_id ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_LQIP; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<?php Doc::maybe_on_by_gm( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Use QUIC.cloud LQIP (Low Quality Image Placeholder) generator service for responsive image previews while loading.', 'litespeed-cache' ); ?>
					<br /><?php esc_html_e( 'Keep this off to use plain color placeholders.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#lqip-cloud-generator' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_LQIP_QUAL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify the quality when generating LQIP.', 'litespeed-cache' ); ?>
					<br /><?php esc_html_e( 'Larger number will generate higher resolution quality placeholder, but will result in larger files which will increase page size and consume more points.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $option_id ); ?>
					<?php $this->_validate_ttl( $option_id, 1, 20 ); ?>
					<br />💡 <?php printf( esc_html__( 'Changes to this setting do not apply to already-generated LQIPs. To regenerate existing LQIPs, please %s first from the admin bar menu.', 'litespeed-cache' ), '<code>' . esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'LQIP Cache', 'litespeed-cache' ) . '</code>' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_LQIP_MIN_W; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?> x
				<?php $this->build_input( Base::O_MEDIA_LQIP_MIN_H, 'litespeed-input-short' ); ?>
				<?php esc_html_e( 'pixels', 'litespeed-cache' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'LQIP requests will not be sent for images where both width and height are smaller than these dimensions.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $option_id ); ?>
					<?php $this->_validate_ttl( $option_id, 10, 800 ); ?>
					<?php $this->_validate_ttl( Base::O_MEDIA_LQIP_MIN_H, 10, 800 ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_PLACEHOLDER_RESP_ASYNC; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Automatically generate LQIP in the background via a cron-based queue.', 'litespeed-cache' ); ?>
					<?php
					printf(
						esc_html__( 'If set to %1$s, before the placeholder is localized, the %2$s configuration will be used.', 'litespeed-cache' ),
						'<code>' . esc_html__( 'ON', 'litespeed-cache' ) . '</code>',
						'<code>' . esc_html( Lang::title( Base::O_MEDIA_PLACEHOLDER_RESP_SVG ) ) . '</code>'
					);
					?>
					<?php printf( esc_html__( 'If set to %s this is done in the foreground, which may slow down page load.', 'litespeed-cache' ), '<code>' . esc_html__( 'OFF', 'litespeed-cache' ) . '</code>' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#generate-lqip-in-background' ); ?>
				</div>

				<div class="litespeed-desc">
					<?php if ( $placeholder_summary ) : ?>
						<?php if ( ! empty( $placeholder_summary['last_request'] ) ) : ?>
							<p>
								<?php echo esc_html__( 'Last generated', 'litespeed-cache' ) . ': <code>' . esc_html( Utility::readable_time( $placeholder_summary['last_request'] ) ) . '</code>'; ?>
							</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $closest_server ) : ?>
						<a class="litespeed-redetect" href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_LQIP ) ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php printf( esc_html__( 'Current closest Cloud server is %s. Click to redetect.', 'litespeed-cache' ), esc_html( $closest_server ) ); ?>' data-litespeed-cfm="<?php esc_html_e( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ); ?>"><i class='litespeed-quic-icon'></i> <?php esc_html_e( 'Redetect', 'litespeed-cache' ); ?></a>
					<?php endif; ?>

					<?php if ( ! empty( $lqip_queue ) ) : ?>
						<div class="litespeed-callout notice notice-warning inline">
							<h4>
								<?php esc_html_e( 'Size list in queue waiting for cron', 'litespeed-cache' ); ?> ( <?php echo esc_html( count( $lqip_queue ) ); ?> )
								<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_PLACEHOLDER, Placeholder::TYPE_CLEAR_Q ) ); ?>" class="button litespeed-btn-warning litespeed-right"><?php esc_html_e( 'Clear', 'litespeed-cache' ); ?></a>
							</h4>
							<p>
								<?php
								$i = 0;
								foreach ( $lqip_queue as $k => $v ) {
									if ( $i++ > 20 ) {
										echo '...';
										break;
									}
									echo esc_html( $v );
									echo '<br />';
								}
								?>
							</p>
						</div>
						<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_PLACEHOLDER, Placeholder::TYPE_GENERATE ) ); ?>" class="button litespeed-btn-success">
							<?php esc_html_e( 'Run Queue Manually', 'litespeed-cache' ); ?>
						</a>
						<?php Doc::queue_issues(); ?>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_IFRAME_LAZY; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Load iframes only when they enter the viewport.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_ADD_MISSING_SIZES; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Set an explicit width and height on image elements to reduce layout shifts and improve CLS (a Core Web Vitals metric).', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://web.dev/optimize-cls/#images-without-dimensions' ); ?>

					<br />
					<font class="litespeed-warning litespeed-left10">
						⚠️ <?php esc_html_e( 'Notice', 'litespeed-cache' ); ?>: <?php printf( esc_html__( '%s must be turned ON for this setting to work.', 'litespeed-cache' ), '<code>' . esc_html( Lang::title( Base::O_MEDIA_LAZY ) ) . '</code>' ); ?>
					</font>

					<br />
					<font class="litespeed-success">
						<?php esc_html_e( 'API', 'litespeed-cache' ); ?>:
						<?php printf( esc_html__( 'Use %1$s to bypass remote image dimension check when %2$s is ON.', 'litespeed-cache' ), '<code>add_filter( "litespeed_media_ignore_remote_missing_sizes", "__return_true" );</code>', '<code>' . esc_html( Lang::title( Base::O_MEDIA_ADD_MISSING_SIZES ) ) . '</code>' ); ?>
					</font>
					<?php $__admin_display->_check_overwritten( Base::O_MEDIA_ADD_MISSING_SIZES ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_JPG_QUALITY; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'The image compression quality setting of WordPress out of 100.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $option_id ); ?>
					<?php $this->_validate_ttl( $option_id, 0, 100 ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MEDIA_AUTO_RESCALE_ORI; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Automatically replace large images with scaled versions.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'Scaled size threshold', 'litespeed-cache' ); ?>: <code><?php echo wp_kses_post( $scaled_size ); ?></code>
					<br />
					<span class="litespeed-success">
						API:
						<?php
						printf(
							esc_html__( 'Filter %s available to change threshold.', 'litespeed-cache' ),
							'<code>big_image_size_threshold</code>'
						);
						?>
						<a href="https://developer.wordpress.org/reference/hooks/big_image_size_threshold/" target="_blank" class="litespeed-learn-more">
							<?php esc_html_e('Learn More', 'litespeed-cache'); ?>
						</a>
					</span>

					<br />
					<font class="litespeed-danger">
						🚨
						<?php esc_html_e( 'This is irreversible.', 'litespeed-cache' ); ?>
					</font>
				</div>
			</td>
		</tr>
	</tbody>
</table>