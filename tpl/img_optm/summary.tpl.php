<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$closest_server = Cloud::get_summary( 'server.' . Cloud::SVC_IMG_OPTM );
$usage_cloud = Cloud::get_summary( 'usage.' . Cloud::SVC_IMG_OPTM );
$allowance = Cloud::cls()->allowance( Cloud::SVC_IMG_OPTM );

$__img_optm = Img_Optm::cls();

$wet_limit = $__img_optm->wet_limit();
$img_count = $__img_optm->img_count();
$need_gather = $__img_optm->need_gather();

$optm_summary = Img_Optm::get_summary();

list( $last_run, $is_running ) = $__img_optm->cron_running( false );

if ( ! empty( $img_count[ 'groups_all' ] ) ) {
	$gathered_percentage = 100 - floor( $img_count[ 'groups_not_gathered' ] * 100 / $img_count[ 'groups_all' ] );
	if ( $gathered_percentage == 100 && $img_count[ 'groups_not_gathered' ] ) {
		$gathered_percentage = 99;
	}
}
else {
	$gathered_percentage = 0;
}

if ( ! empty( $img_count[ 'imgs_gathered' ] ) ) {
	$finished_percentage = 100 - floor( $img_count[ 'img.' . Img_Optm::STATUS_RAW ] * 100 / $img_count[ 'imgs_gathered' ] );
	if ( $finished_percentage == 100 && $img_count[ 'img.' . Img_Optm::STATUS_RAW ] ) {
		$finished_percentage = 99;
	}
}
else {
	$finished_percentage = 0;
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

?>
<div class="litespeed-flex-container litespeed-column-with-boxes">
	<div class="litespeed-width-7-10 litespeed-image-optim-summary-wrapper">
		<div class="litespeed-image-optim-summary">

			<h3>
				<?php if ( $closest_server ) : ?>
					<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_IMG_OPTM ) ); ?>" class="litespeed-info-button" data-balloon-pos="right" data-balloon-break aria-label="<?php echo sprintf( __( 'Current closest Cloud server is %s.&#10; Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>" data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ); ?>"><span class="litespeed-quic-icon"></span></a>
				<?php else : ?>
					<span class="litespeed-quic-icon"></span>
				<?php endif; ?>
				<?php echo __('Optimize images with our QUIC.cloud server', 'litespeed-cache' );?>
				<a href="https://docs.litespeedtech.com/lscache/lscwp/imageopt/#image-optimization-summary-tab" target="_blank" class="litespeed-right litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache'); ?></a>
			</h3>

			<p>
				<?php echo sprintf( __( 'You can request a maximum of %s images at once.', 'litespeed-cache' ), '<strong>' . intval( $allowance ) . '</strong>' ); ?>
			</p>

			<?php if ( $wet_limit ) : ?>
			<p class="litespeed-desc">
				<?php echo __( 'To make sure our server can communicate with your server without any issues and everything works fine, for the few first requests the number of images allowed in a single request is limited.', 'litespeed-cache' ); ?>
				<?php echo __( 'Current limit is', 'litespeed-cache' ) . ': <strong>' . $wet_limit . '</strong>'; ?>
			</p>
			<?php endif; ?>

			<div class="litespeed-img-optim-actions">
				<a data-litespeed-onlyonce class="button button-primary"
					<?php if ( ! empty( $img_count[ 'groups_not_gathered' ] ) || ! empty( $img_count[ 'img.' . Img_Optm::STATUS_RAW ] ) ) : ?>
						href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_NEW_REQ ); ?>"
					<?php else : ?>
						href='javascript:;' disabled
					<?php endif; ?>
					>
					<span class="dashicons dashicons-images-alt2"></span>&nbsp;<?php echo $need_gather ? __( 'Gather Image Data', 'litespeed-cache' ) : __( 'Send Optimization Request', 'litespeed-cache' ); ?>
				</a>

				<a data-litespeed-onlyonce class="button button-secondary" data-balloon-length="large" data-balloon-pos="right" aria-label="<?php echo __( 'Only press the button if the pull cron job is disabled.', 'litespeed-cache' ); ?> <?php echo __( 'Images will be pulled automatically if the cron job is running.', 'litespeed-cache' ); ?>"
					<?php if ( ! empty( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ] ) && ! $is_running ) : ?>
						href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_PULL ); ?>"
					<?php else : ?>
						href='javascript:;' disabled
					<?php endif; ?>
					>
					<?php echo __( 'Pull Images', 'litespeed-cache' ); ?>
				</a>
			</div>

			<div>
				<h3 class="litespeed-title-section">
					<?php echo __( 'Optimization Status', 'litespeed-cache' ); ?>
					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_RAW ] ) ) : ?>
						<a href="https://docs.litespeedtech.com/lscache/lscwp/imageopt/#optimization-summary" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache'); ?></a>
					<?php endif; ?>
				</h3>

				<div class="litespeed-light-code">

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo Lang::img_status( Img_Optm::STATUS_REQUESTED ); ?>:
							<code>
								<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ); ?>
								(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ], 'image' ); ?>)
							</code>
						</p>
						<p class="litespeed-desc">
							<?php echo __( 'After the QUIC.cloud Image Optimization server finishes optimization, it will notify your site to pull the optimized images.', 'litespeed-cache' ); ?>
							<?php echo __( 'This process is automatic.', 'litespeed-cache' ); ?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo Lang::img_status( Img_Optm::STATUS_NOTIFIED ); ?>:
							<code>
								<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ); ?>
								(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ], 'image' ); ?>)
							</code>

						</p>
						<?php if ( $last_run ) : ?>
							<p class="litespeed-desc">
								<?php echo sprintf( __( 'Last pull initiated by cron at %s.', 'litespeed-cache' ), '<code>' . Utility::readable_time( $last_run ) . '</code>' ); ?>
							</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_PULLED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo Lang::img_status( Img_Optm::STATUS_PULLED ); ?>:
							<code>
								<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_PULLED ] ); ?>
								(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_PULLED ], 'image' ); ?>)
							</code>
						</p>
					<?php endif; ?>

					<div class="litespeed-silence">
						<?php
							$list = array(
								Img_Optm::STATUS_ERR_FETCH,
								Img_Optm::STATUS_ERR_404,
								Img_Optm::STATUS_ERR_OPTM,
								Img_Optm::STATUS_ERR,
								Img_Optm::STATUS_MISS,
								Img_Optm::STATUS_DUPLICATED,
								Img_Optm::STATUS_XMETA,
							);
						?>
						<?php foreach ( $list as $v ): ?>
							<?php if ( empty( $img_count[ 'group.' . $v ] ) ) continue; ?>
							<p>
								<?php echo Lang::img_status( $v ); ?>:
								<code>
									<?php echo Admin_Display::print_plural( $img_count[ 'group.' . $v ] ); ?>
									(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . $v ], 'image' ); ?>)
								</code>
							</p>
						<?php endforeach; ?>
					</div>

					<p><?php echo sprintf(
							'<a href="%1$s" class="button button-secondary" data-balloon-pos="right" aria-label="%2$s" %3$s><span class="dashicons dashicons-editor-removeformatting"></span>&nbsp;%4$s</a>',
							($unfinished_num ? Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CLEAN ) : 'javascript:;'),
							__( 'Remove all previous unfinished image optimization requests.', 'litespeed-cache' ),
							($unfinished_num ? '' : ' disabled'),
							__( 'Clean Up Unfinished Data', 'litespeed-cache' ) . ( $unfinished_num ? ': ' . Admin_Display::print_plural( $unfinished_num, 'image' ) : '')
						);
					?></p>

					<h3 class="litespeed-title-section">
						<?php echo __( 'Storage Optimization', 'litespeed-cache' ); ?>
					</h3>

					<p>
						<?php echo __( 'A backup of each image is saved before it is optimized.', 'litespeed-cache' ); ?>
					</p>


					<?php if ( ! empty( $optm_summary[ 'bk_summary' ] ) ) : ?>
						<div class="">
							<p>
								<?php echo __( 'Last calculated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $optm_summary[ 'bk_summary' ][ 'date' ] ) . '</code>'; ?>
							</p>
							<?php if ( $optm_summary[ 'bk_summary' ][ 'count' ] ) : ?>
								<p>
									<?php echo __( 'Files', 'litespeed-cache' ) . ': <code>' . intval( $optm_summary[ 'bk_summary' ][ 'count' ] ) . '</code>'; ?>
								</p>
								<p>
									<?php echo __( 'Total', 'litespeed-cache' ) . ': <code>' . Utility::real_size( $optm_summary[ 'bk_summary' ][ 'sum' ] ) . '</code>'; ?>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div>

						<a class="button button-secondary" data-balloon-pos="up" aria-label="<?php echo __( 'Calculate Original Image Storage', 'litespeed-cache' ); ?>"
							<?php if ( $finished_percentage > 0 ) : ?>
								href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CALC_BKUP ); ?>"
							<?php else : ?>
								href='javascript:;' disabled
							<?php endif; ?>
							>
								<span class="dashicons dashicons-update"></span> <?php echo __( 'Calculate Backups Disk Space', 'litespeed-cache' ); ?>
						</a>
					</div>

				</div>

				<div>
					<h4><?php echo __( 'Image Thumbnail Group Sizes', 'litespeed-cache' ); ?></h4>
					<div class="litespeed-desc litespeed-left20">
						<?php foreach ( Media::cls()->get_image_sizes() as $title => $size ) {
							echo "<div>$title ( " . ( $size[ 'width' ] ? $size[ 'width' ] . 'px' : '*' ) . ' x ' . ( $size[ 'height' ] ? $size[ 'height' ] . 'px' : '*' ) . ' )</div>';
						}; ?>
					</div>

				</div>

				<hr class="litespeed-hr-with-space">
				<div>
					<h4><?php echo __( 'Delete all backups of the original images', 'litespeed-cache' ); ?></h4>
					<div class="notice notice-error litespeed-callout-bg inline">
						<p>
							🚨&nbsp;<?php echo __( 'This is irreversible.', 'litespeed-cache' ); ?>
							<?php echo __( 'You will be unable to Revert Optimization once the backups are deleted!', 'litespeed-cache' ); ?>
						</p>
					</div>

				</div>
				<?php if ( ! empty( $optm_summary[ 'rmbk_summary' ] ) ) : ?>
					<div class="">
					<p>
						<?php echo __( 'Last ran', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $optm_summary[ 'rmbk_summary' ][ 'date' ] ) . '</code>'; ?>
					</p>
					<p>
						<?php echo __( 'Files', 'litespeed-cache' ) . ': <code>' . $optm_summary[ 'rmbk_summary' ][ 'count' ] . '</code>'; ?>
					</p>
					<p>
						<?php echo __( 'Saved', 'litespeed-cache' ) . ': <code>' . Utility::real_size( $optm_summary[ 'rmbk_summary' ][ 'sum' ] ) . '</code>'; ?>
					</p>
					</div>
				<?php endif; ?>
				<div class="litespeed-image-optim-summary-footer"><a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RM_BKUP ); ?>" data-litespeed-cfm="<?php echo __( 'Are you sure you want to remove all image backups?', 'litespeed-cache' ); ?>" class="litespeed-link-with-icon litespeed-danger">
					<span class="dashicons dashicons-trash"></span><?php echo __( 'Remove Original Image Backups', 'litespeed-cache' ); ?>
				</a></div>
			</div>
		</div>
	</div>

	<div class="litespeed-width-3-10">
		<div class="postbox litespeed-postbox litespeed-postbox-imgopt-info">
			<div class="inside">

				<h3 class="litespeed-title">
					<?php echo __( 'Image Information', 'litespeed-cache' ); ?>
				</h3>

				<div class="litespeed-flex-container">
					<div class="litespeed-icon-vertical-middle">
						<?php echo GUI::pie( $gathered_percentage, 70, true ); ?>
					</div>
					<div>
						<p>
							<?php echo __( 'Images total', 'litespeed-cache'); ?>:

							<code><?php echo Admin_Display::print_plural( $img_count[ 'groups_all' ] ); ?></code>

							<a href="https://docs.litespeedtech.com/lscache/lscwp/imageopt/#what-is-an-image-group" target="_blank" class="litespeed-desc litespeed-help-btn-icon" data-balloon-pos="up" aria-label="<?php echo __( 'What is a group?', 'litespeed-cache'); ?>">
								<span class="dashicons dashicons-editor-help"></span>
								<span class="screen-reader-text"><?php echo __( 'What is an image group?', 'litespeed-cache' );?></span>
							</a>
						</p>
						<p>
							<?php if ( ! empty( $img_count[ 'groups_not_gathered' ] ) ) : ?>
								<?php echo __('Images not yet gathered', 'litespeed-cache'); ?>:
								<code><?php echo Admin_Display::print_plural( $img_count[ 'groups_not_gathered' ] ); ?></code>
							<?php else : ?>
								<font class="litespeed-congratulate"><?php echo __('Congratulations, all gathered!', 'litespeed-cache'); ?></font>
							<?php endif; ?>
						</p>

					</div>
				</div>

				<div class="litespeed-flex-container">
					<div class="litespeed-icon-vertical-middle">
						<?php echo GUI::pie( $finished_percentage, 70, true ); ?>
					</div>
					<div>
						<p>
							<?php echo __( 'Images total', 'litespeed-cache'); ?>:

							<code><?php echo Admin_Display::print_plural( $img_count[ 'imgs_gathered' ], 'image' ); ?></code>

							<a href="https://docs.litespeedtech.com/lscache/lscwp/imageopt/#what-is-an-image-group" target="_blank" class="litespeed-desc litespeed-help-btn-icon" data-balloon-pos="up" aria-label="<?php echo __( 'What is a group?', 'litespeed-cache'); ?>">
								<span class="dashicons dashicons-editor-help"></span>
								<span class="screen-reader-text"><?php echo __( 'What is an image group?', 'litespeed-cache' );?></span>
							</a>
						</p>
						<p>
							<?php if ( ! empty( $img_count[ 'img.' . Img_Optm::STATUS_RAW ] ) ) : ?>
								<?php echo __('Images not yet requested', 'litespeed-cache'); ?>:
								<code><?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_RAW ], 'image' ); ?></code>
							<?php else : ?>
								<font class="litespeed-congratulate"><?php echo __('Congratulations, all requested!', 'litespeed-cache'); ?></font>
							<?php endif; ?>
						</p>
					</div>
				</div>
			</div>
			<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">
				<a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RESCAN ); ?>" class="" data-balloon-pos="up" data-balloon-length="large" aria-label="<?php echo __( 'Scan for any new unoptimized image thumbnail sizes and resend necessary image optimization requests.', 'litespeed-cache' ); ?>">
					<?php echo __( 'Rescan New Thumbnails', 'litespeed-cache' ); ?>
				</a>
			</div>
		</div>

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title">
					<?php echo __( 'Optimization Summary', 'litespeed-cache' ); ?>
				</h3>
				<p>
					<?php echo __( 'Total Reduction', 'litespeed-cache' ); ?>: <code><?php echo isset( $optm_summary[ 'reduced' ] ) ? Utility::real_size( $optm_summary[ 'reduced' ] ) : '-'; ?></code>
				</p>
				<p>
					<?php echo __( 'Images Pulled', 'litespeed-cache' ); ?>: <code><?php echo isset( $optm_summary[ 'img_taken' ] ) ? $optm_summary[ 'img_taken' ] : '-'; ?></code>
				</p>
				<p>
					<?php echo __( 'Last Request', 'litespeed-cache' ); ?>: <code><?php echo isset( $optm_summary[ 'last_requested' ] ) ? Utility::readable_time( $optm_summary[ 'last_requested' ] ) : '-'; ?></code>
				</p>
			</div>
			<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact litespeed-desc">
				<?php echo sprintf( __( 'Results can be checked in <a %s>Media Library</a>.', 'litespeed-cache' ), 'href="upload.php?mode=list"' ); ?>
			</div>
		</div>

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title"><?php echo __('Optimization Tools', 'litespeed-cache'); ?></h3>

				<p>
					<?php echo __( 'You can quickly switch between using original (unoptimized versions) and optimized image files. It will affect all images on your website, both regular and webp versions if available.', 'litespeed-cache' ); ?>
				</p>

				<div class="litespeed-links-group">
					<span>
						<a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_BATCH_SWITCH_ORI ); ?>" class="litespeed-link-with-icon" data-balloon-pos="up" aria-label="<?php echo __( 'Use original images (unoptimized) on your site', 'litespeed-cache' ); ?>">
							<span class="dashicons dashicons-undo"></span><?php echo __( 'Use Original Files', 'litespeed-cache' ); ?>
						</a>
					</span><span>
						<a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_BATCH_SWITCH_OPTM ); ?>" class="litespeed-link-with-icon litespeed-icon-right" data-balloon-pos="up" aria-label="<?php echo __( 'Switch back to using optimized images on your site', 'litespeed-cache' ); ?>">
							<?php echo __( 'Use Optimized Files', 'litespeed-cache' ); ?><span class="dashicons dashicons-redo"></span>
						</a>
					</span>
				</div>

			</div>
			<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact">

				<p><a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_DESTROY ); ?>" class="litespeed-link-with-icon litespeed-danger" data-litespeed-cfm="<?php echo __( 'Are you sure to destroy all optimized images?', 'litespeed-cache' ); ?>" >
					<span class="dashicons dashicons-dismiss"></span><?php echo __( 'Destroy All Optimization Data', 'litespeed-cache' ); ?>
				</a></p>

				<div class="litespeed-desc">
					<?php echo __( 'Remove all previous image optimization requests/results, revert completed optimizations, and delete all optimization files.', 'litespeed-cache' ); ?>
				</div>
			</div>
		</div>
	</div>
</div>

