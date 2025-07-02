<?php
/**
 * LiteSpeed Cache Image Optimization Summary
 *
 * Manages the image optimization summary interface for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$closest_server = Cloud::get_summary( 'server.' . Cloud::SVC_IMG_OPTM );
$usage_cloud    = Cloud::get_summary( 'usage.' . Cloud::SVC_IMG_OPTM );
$allowance      = Cloud::cls()->allowance( Cloud::SVC_IMG_OPTM );

$img_optm = Img_Optm::cls();

$wet_limit = $img_optm->wet_limit();
$img_count = $img_optm->img_count();

$optm_summary = Img_Optm::get_summary();

list($last_run, $is_running) = $img_optm->cron_running( false );
$finished_percentage         = 0;
if ( $img_count['groups_all'] ) {
	$finished_percentage = 100 - floor( $img_count['groups_new'] * 100 / $img_count['groups_all'] );
}
if ( 100 === $finished_percentage && $img_count['groups_new'] ) {
	$finished_percentage = 99;
}

$unfinished_num = 0;
if ( ! empty( $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ] ) ) {
	$unfinished_num += $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ];
}
if ( ! empty( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ] ) ) {
	$unfinished_num += $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ];
}
if ( ! empty( $img_count[ 'img.' . Img_Optm::STATUS_ERR_FETCH ] ) ) {
	$unfinished_num += $img_count[ 'img.' . Img_Optm::STATUS_ERR_FETCH ];
}

$imgoptm_service_hot = $this->cls( 'Cloud' )->service_hot( Cloud::SVC_IMG_OPTM . '-' . Img_Optm::CLOUD_ACTION_NEW_REQ );
?>
<div class="litespeed-flex-container litespeed-column-with-boxes">
	<div class="litespeed-width-7-10 litespeed-column-left litespeed-image-optim-summary-wrapper">
		<div class="litespeed-image-optim-summary">

			<h3>
				<?php if ( $closest_server ) : ?>
					<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_IMG_OPTM ) ) ); ?>" class="litespeed-info-button litespeed-redetect" data-balloon-pos="right" data-balloon-break aria-label="<?php printf( esc_html__( 'Current closest Cloud server is %s. Click to redetect.', 'litespeed-cache' ), esc_html( $closest_server ) ); ?>" data-litespeed-cfm="<?php esc_html_e( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ); ?>"><span class="litespeed-quic-icon"></span> <?php esc_html_e( 'Redetect', 'litespeed-cache' ); ?></a>
				<?php else : ?>
					<span class="litespeed-quic-icon"></span> <?php esc_html_e( 'Redetect', 'litespeed-cache' ); ?>
				<?php endif; ?>
				<?php esc_html_e( 'Optimize images with our QUIC.cloud server', 'litespeed-cache' ); ?>
				<a href="https://docs.litespeedtech.com/lscache/lscwp/imageopt/#image-optimization-summary-tab" target="_blank" class="litespeed-right litespeed-learn-more"><?php esc_html_e( 'Learn More', 'litespeed-cache' ); ?></a>
			</h3>

			<p>
				<?php printf( esc_html__( 'You can request a maximum of %s images at once.', 'litespeed-cache' ), '<strong>' . intval( $allowance ) . '</strong>' ); ?>
			</p>

			<?php if ( $wet_limit ) : ?>
				<p class="litespeed-desc">
					<?php esc_html_e( 'To make sure our server can communicate with your server without any issues and everything works fine, for the few first requests the number of image groups allowed in a single request is limited.', 'litespeed-cache' ); ?>
					<?php echo esc_html__( 'Current limit is', 'litespeed-cache' ) . ': <strong>' . esc_html( $wet_limit ) . '</strong>'; ?>
				</p>
			<?php endif; ?>

			<div class="litespeed-img-optim-actions">
				<?php if ( $imgoptm_service_hot ) : ?>
					<button class="button button-secondary" disabled>
						<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'Send Optimization Request', 'litespeed-cache' ); ?>
						- <?php printf( esc_html__( 'Available after %d second(s)', 'litespeed-cache' ), esc_html( $imgoptm_service_hot ) ); ?>
					</button>
				<?php else : ?>
					<a data-litespeed-onlyonce class="button button-primary"
					<?php
					if ( ! empty( $img_count['groups_new'] ) || ! empty( $img_count[ 'group.' . Img_Optm::STATUS_RAW ] ) ) :
						?>
						href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_NEW_REQ ) ); ?>"
						<?php
					else :
						?>
						href="javascript:;" disabled <?php endif; ?>>
						<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'Send Optimization Request', 'litespeed-cache' ); ?>
					</a>
				<?php endif; ?>

				<a data-litespeed-onlyonce class="button button-secondary" data-balloon-length="large" data-balloon-pos="right" aria-label="<?php esc_html_e( 'Only press the button if the pull cron job is disabled.', 'litespeed-cache' ); ?> <?php esc_html_e( 'Images will be pulled automatically if the cron job is running.', 'litespeed-cache' ); ?>"
					<?php
					if ( ! empty( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ] ) && ! $is_running ) :
						?>
						href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_PULL ) ); ?>"
						<?php
					else :
						?>
						href="javascript:;" disabled <?php endif; ?>>
					<?php esc_html_e( 'Pull Images', 'litespeed-cache' ); ?>
				</a>
			</div>

			<div>
				<h3 class="litespeed-title-section">
					<?php esc_html_e( 'Optimization Status', 'litespeed-cache' ); ?>
				</h3>

				<div class="litespeed-light-code">

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_NEW ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo esc_html( Lang::img_status( Img_Optm::STATUS_NEW ) ); ?>:
							<code>
								<?php echo esc_html( Admin_Display::print_plural( $img_count['group_new'] ) ); ?>
							</code>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_RAW ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo esc_html( Lang::img_status( Img_Optm::STATUS_RAW ) ); ?>:
							<code>
								<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_RAW ] ) ); ?>
								(<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_RAW ], 'image' ) ); ?>)
							</code>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo esc_html( Lang::img_status( Img_Optm::STATUS_REQUESTED ) ); ?>:
							<code>
								<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ); ?>
								(<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ], 'image' ) ); ?>)
							</code>
						</p>
						<p class="litespeed-desc">
							<?php esc_html_e( 'After the QUIC.cloud Image Optimization server finishes optimization, it will notify your site to pull the optimized images.', 'litespeed-cache' ); ?>
							<?php esc_html_e( 'This process is automatic.', 'litespeed-cache' ); ?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo esc_html( Lang::img_status( Img_Optm::STATUS_NOTIFIED ) ); ?>:
							<code>
								<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ); ?>
								(<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ], 'image' ) ); ?>)
							</code>
						</p>
						<?php if ( $last_run ) : ?>
							<p class="litespeed-desc">
								<?php printf( esc_html__( 'Last pull initiated by cron at %s.', 'litespeed-cache' ), '<code>' . esc_html( Utility::readable_time( $last_run ) ) . '</code>' ); ?>
							</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_PULLED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo esc_html( Lang::img_status( Img_Optm::STATUS_PULLED ) ); ?>:
							<code>
								<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_PULLED ] ) ); ?>
								(<?php echo esc_html( Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_PULLED ], 'image' ) ); ?>)
							</code>
						</p>
					<?php endif; ?>

					<p>
					<?php
					printf(
						'<a href="%1$s" class="button button-secondary litespeed-btn-warning" data-balloon-pos="right" aria-label="%2$s" %3$s><span class="dashicons dashicons-editor-removeformatting"></span> %4$s</a>',
						( $unfinished_num ? esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CLEAN ) ) : 'javascript:;' ),
						esc_html__( 'Remove all previous unfinished image optimization requests.', 'litespeed-cache' ),
						( $unfinished_num ? '' : ' disabled' ),
						esc_html__( 'Clean Up Unfinished Data', 'litespeed-cache' ) . ( $unfinished_num ? ': ' . esc_html( Admin_Display::print_plural( $unfinished_num, 'image' ) ) : '' )
					);
					?>
					</p>

					<h3 class="litespeed-title-section">
						<?php esc_html_e( 'Storage Optimization', 'litespeed-cache' ); ?>
					</h3>

					<p>
						<?php esc_html_e( 'A backup of each image is saved before it is optimized.', 'litespeed-cache' ); ?>
					</p>

					<?php if ( ! empty( $optm_summary['bk_summary'] ) ) : ?>
						<div>
							<p>
								<?php echo esc_html__( 'Last calculated', 'litespeed-cache' ) . ': <code>' . esc_html( Utility::readable_time( $optm_summary['bk_summary']['date'] ) ) . '</code>'; ?>
							</p>
							<?php if ( $optm_summary['bk_summary']['count'] ) : ?>
								<p>
									<?php echo esc_html__( 'Files', 'litespeed-cache' ) . ': <code>' . intval( $optm_summary['bk_summary']['count'] ) . '</code>'; ?>
								</p>
								<p>
									<?php echo esc_html__( 'Total', 'litespeed-cache' ) . ': <code>' . esc_html( Utility::real_size( $optm_summary['bk_summary']['sum'] ) ) . '</code>'; ?>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div>
						<a class="button button-secondary" data-balloon-pos="up" aria-label="<?php esc_html_e( 'Calculate Original Image Storage', 'litespeed-cache' ); ?>"
							<?php
							if ( $finished_percentage > 0 ) :
								?>
							href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CALC_BKUP ) ); ?>"
							<?php
							else :
								?>
							href="javascript:;" disabled <?php endif; ?>>
							<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Calculate Backups Disk Space', 'litespeed-cache' ); ?>
						</a>
					</div>

				</div>

				<div>
					<h4><?php esc_html_e( 'Image Thumbnail Group Sizes', 'litespeed-cache' ); ?></h4>
					<div class="litespeed-desc litespeed-left20">
						<?php
						foreach ( Media::cls()->get_image_sizes() as $size_title => $size ) {
							printf(
								'<div>%1$s ( %2$s x %3$s )</div>',
								esc_html( $size_title ),
								$size['width'] ? esc_html( $size['width'] ) . 'px' : '*',
								$size['height'] ? esc_html( $size['height'] ) . 'px' : '*'
							);
						}
						?>
					</div>
				</div>

				<hr class="litespeed-hr-with-space">
				<div>
					<h4><?php esc_html_e( 'Delete all backups of the original images', 'litespeed-cache' ); ?></h4>
					<div class="notice notice-error litespeed-callout-bg inline">
						<p>
							🚨 <?php esc_html_e( 'This is irreversible.', 'litespeed-cache' ); ?>
							<?php esc_html_e( 'You will be unable to Revert Optimization once the backups are deleted!', 'litespeed-cache' ); ?>
						</p>
					</div>
				</div>

				<?php if ( ! empty( $optm_summary['rmbk_summary'] ) ) : ?>
					<div>
						<p>
							<?php echo esc_html__( 'Last ran', 'litespeed-cache' ) . ': <code>' . esc_html( Utility::readable_time( $optm_summary['rmbk_summary']['date'] ) ) . '</code>'; ?>
						</p>
						<p>
							<?php echo esc_html__( 'Files', 'litespeed-cache' ) . ': <code>' . esc_html( $optm_summary['rmbk_summary']['count'] ) . '</code>'; ?>
						</p>
						<p>
							<?php echo esc_html__( 'Saved', 'litespeed-cache' ) . ': <code>' . esc_html( Utility::real_size( $optm_summary['rmbk_summary']['sum'] ) ) . '</code>'; ?>
						</p>
					</div>
				<?php endif; ?>
				<div class="litespeed-image-optim-summary-footer">
					<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RM_BKUP ) ); ?>" data-litespeed-cfm="<?php esc_html_e( 'Are you sure you want to remove all image backups?', 'litespeed-cache' ); ?>" class="litespeed-link-with-icon litespeed-danger">
						<span class="dashicons dashicons-trash"></span><?php esc_html_e( 'Remove Original Image Backups', 'litespeed-cache' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<div class="litespeed-width-3-10 litespeed-column-right">
		<div class="postbox litespeed-postbox litespeed-postbox-imgopt-info">
			<div class="inside">
				<h3 class="litespeed-title">
					<?php esc_html_e( 'Image Information', 'litespeed-cache' ); ?>
				</h3>

				<div class="litespeed-flex-container">
					<div class="litespeed-icon-vertical-middle">
						<?php echo wp_kses( GUI::pie( $finished_percentage, 70, true ), GUI::allowed_svg_tags() ); ?>
					</div>
					<div>
						<p>
							<?php esc_html_e( 'Image groups total', 'litespeed-cache' ); ?>:
							<?php if ( $img_count['groups_new'] ) : ?>
								<code><?php echo esc_html( Admin_Display::print_plural( $img_count['groups_new'], 'group' ) ); ?></code>
							<?php else : ?>
								<font class="litespeed-congratulate"><?php esc_html_e( 'Congratulations, all gathered!', 'litespeed-cache' ); ?></font>
							<?php endif; ?>
							<a href="https://docs.litespeedtech.com/lscache/lscwp/imageopt/#what-is-an-image-group" target="_blank" class="litespeed-desc litespeed-help-btn-icon" data-balloon-pos="up" aria-label="<?php esc_html_e( 'What is a group?', 'litespeed-cache' ); ?>">
								<span class="dashicons dashicons-editor-help"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'What is an image group?', 'litespeed-cache' ); ?></span>
							</a>
						</p>
						<p>
							<?php esc_html_e( 'Current image post id position', 'litespeed-cache' ); ?>: <?php echo ! empty( $optm_summary['next_post_id'] ) ? esc_html( $optm_summary['next_post_id'] ) : '-'; ?><br>
							<?php esc_html_e( 'Maximum image post id', 'litespeed-cache' ); ?>: <?php echo esc_html( $img_count['max_id'] ); ?>
						</p>
					</div>
				</div>
			</div>
			<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact" style="display: none;">
				<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RESCAN ) ); ?>" class="" data-balloon-pos="up" data-balloon-length="large" aria-label="<?php esc_html_e( 'Scan for any new unoptimized image thumbnail sizes and resend necessary image optimization requests.', 'litespeed-cache' ); ?>">
					<?php esc_html_e( 'Rescan New Thumbnails', 'litespeed-cache' ); ?>
				</a>
			</div>
		</div>

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title">
					<?php esc_html_e( 'Optimization Summary', 'litespeed-cache' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'Total Reduction', 'litespeed-cache' ); ?>: <code><?php echo isset( $optm_summary['reduced'] ) ? esc_html( Utility::real_size( $optm_summary['reduced'] ) ) : '-'; ?></code>
				</p>
				<p>
					<?php esc_html_e( 'Images Pulled', 'litespeed-cache' ); ?>: <code><?php echo isset( $optm_summary['img_taken'] ) ? esc_html( $optm_summary['img_taken'] ) : '-'; ?></code>
				</p>
				<p>
					<?php esc_html_e( 'Last Request', 'litespeed-cache' ); ?>: <code><?php echo isset( $optm_summary['last_requested'] ) ? esc_html( Utility::readable_time( $optm_summary['last_requested'] ) ) : '-'; ?></code>
				</p>
				<p>
					<?php esc_html_e( 'Last Pulled', 'litespeed-cache' ); ?>: <code><?php echo isset( $optm_summary['last_pulled'] ) ? esc_html( Utility::readable_time( $optm_summary['last_pulled'] ) ) : '-'; ?></code>
					<?php
					if ( isset( $optm_summary['last_pulled_by_cron'] ) && $optm_summary['last_pulled_by_cron'] ) {
						echo '(Cron)';
					}
					?>
				</p>
			</div>
			<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact litespeed-desc">
				<?php
				printf(
					/* translators: %s: Link tags */
					esc_html__( 'Results can be checked in %sMedia Library%s.', 'litespeed-cache' ),
					'<a href="upload.php?mode=list">',
					'</a>'
				);
				?>
			</div>
		</div>

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title"><?php esc_html_e( 'Optimization Tools', 'litespeed-cache' ); ?></h3>

				<p>
					<?php esc_html_e( 'You can quickly switch between using original (unoptimized versions) and optimized image files. It will affect all images on your website, both regular and webp versions if available.', 'litespeed-cache' ); ?>
				</p>

				<div class="litespeed-links-group">
					<span>
						<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_BATCH_SWITCH_ORI ) ); ?>" class="litespeed-link-with-icon" data-balloon-pos="up" aria-label="<?php esc_html_e( 'Use original images (unoptimized) on your site', 'litespeed-cache' ); ?>">
							<span class="dashicons dashicons-undo"></span><?php esc_html_e( 'Use Original Files', 'litespeed-cache' ); ?>
						</a>
					</span><span>
						<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_BATCH_SWITCH_OPTM ) ); ?>" class="litespeed-link-with-icon litespeed-icon-right" data-balloon-pos="up" aria-label="<?php esc_html_e( 'Switch back to using optimized images on your site', 'litespeed-cache' ); ?>">
							<?php esc_html_e( 'Use Optimized Files', 'litespeed-cache' ); ?><span class="dashicons dashicons-redo"></span>
						</a>
					</span>
				</div>
			</div>
			<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
				<p>
					<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RESET_COUNTER ) ); ?>" class="litespeed-link-with-icon litespeed-warning">
						<span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Soft Reset Optimization Counter', 'litespeed-cache' ); ?>
					</a>
				</p>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'This will reset the %1$s. If you changed WebP/AVIF settings and want to generate %2$s for the previously optimized images, use this action.', 'litespeed-cache' ), '<code>' . esc_html__( 'Current image post id position', 'litespeed-cache' ) . '</code>', 'WebP/AVIF' ); ?>
				</div>
			</div>
			<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
				<p>
					<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_DESTROY ) ); ?>" class="litespeed-link-with-icon litespeed-danger" data-litespeed-cfm="<?php esc_html_e( 'Are you sure to destroy all optimized images?', 'litespeed-cache' ); ?>" id="litespeed-imageopt-destroy">
						<span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Destroy All Optimization Data', 'litespeed-cache' ); ?>
					</a>
				</p>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Remove all previous image optimization requests/results, revert completed optimizations, and delete all optimization files.', 'litespeed-cache' ); ?>
				</div>
			</div>
		</div>
	</div>
</div>