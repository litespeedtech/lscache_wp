<?php
if ( ! defined( 'WPINC' ) ) {
	die ;
}

$closet_server = get_option( LiteSpeed_Cache_Admin_API::DB_API_CLOUD ) ;
?>

	<div class="litespeed-width-7-10">
		<div class="litespeed-empty-space-small"></div>

		<?php if ( $img_count[ 'total_not_requested' ] ) : ?>
			<div class="litespeed-text-center">
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_OPTIMIZE ) ; ?>" class="litespeed-btn-success litespeed-btn-large">
					<span class="dashicons dashicons-images-alt2"></span>&nbsp;<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
				</a>

			</div>

			<div class="litespeed-empty-space-small"></div>

			<div class="litespeed-desc">
				<?php if ( $closet_server ) : ?>
					<font title="<?php echo $closet_server ; ?>">☁️</font>
				<?php endif ; ?>
				<?php echo __( 'This will send the optimization request and the images to LiteSpeed\'s Image Optimization Server.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'You can send at most %s images at once.', 'litespeed-cache' ), '<code>' . $optm_summary[ 'credit' ] . '</code>' ) ; ?>
			</div>
		<?php endif ; ?>

		<?php if ( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ] && ! $is_running ) : ?>
		<div>
			<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_PULL ) ; ?>" class="litespeed-btn-success" title="<?php echo __( 'Only press the button if the pull cron job is disabled.', 'litespeed-cache' ) ; ?> <?php echo __( 'Images will be pulled automatically if the cron job is running.', 'litespeed-cache' ) ; ?>">
				<?php echo __( 'Pull Images', 'litespeed-cache' ) ; ?>
			</a>
		</div>
		<?php endif ; ?>

		<div class="litespeed-empty-space-medium"></div>

		<div>
			<h2 Class="litespeed-title">
				<?php echo __( 'Current Stage Status', 'litespeed-cache' ) ; ?>
			</h2>
			<div class="litespeed-empty-space-medium"></div>
			<?php include_once LSCWP_DIR . "admin/tpl/inc/img_optm.level_info.php" ; ?>

			<hr class="litespeed-hr-dotted">

			<div class="litespeed-empty-space-small"></div>

			<div class="litespeed-light-code">

				<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_REQUESTED ] ) ) : ?>
				<p class="litespeed-success">
					<?php echo __('Images requested', 'litespeed-cache') ; ?>:
					<code>
						<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_REQUESTED ] ) ; ?>
						(<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_REQUESTED ], 'image' ) ; ?>)
					</code>
				</p>
				<p class="litespeed-desc">
					<?php echo __( 'After LiteSpeed\'s Image Optimization Server finishes optimization, it will notify your site to pull the optimized images.', 'litespeed-cache' ) ; ?>
					<?php echo __( 'This process is automatic.', 'litespeed-cache' ) ; ?>
				</p>
				<?php endif ; ?>

				<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ] ) ) : ?>
					<p class="litespeed-success">
						<?php echo __('Images notified to pull', 'litespeed-cache') ; ?>:
						<code>
							<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ] ) ; ?>
							(<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ], 'image' ) ; ?>)
						</code>

					</p>
					<?php if ( $last_run ) : ?>
						<p class="litespeed-desc">
							<?php echo sprintf( __( 'Last pull initiated by cron at %s.', 'litespeed-cache' ), '<code>' . LiteSpeed_Cache_Utility::readable_time( $last_run ) . '</code>' ) ; ?>
						</p>
					<?php endif ; ?>
				<?php endif ; ?>

				<div class="litespeed-empty-space-small"></div>

				<div class="litespeed-flex-container">

					<div class="litespeed-width-1-2">

						<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_PULLED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo __('Images optimized and pulled', 'litespeed-cache') ; ?>:
							<code>
								<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_PULLED ] ) ; ?>
								(<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_PULLED ], 'image' ) ; ?>)
							</code>
						</p>
						<?php endif ; ?>

						<div class="litespeed-silence">
							<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH ] ) ) : ?>
							<p>
								<?php echo __('Images failed to fetch', 'litespeed-cache') ; ?>:
								<code>
									<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH ] ) ; ?>
									(<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH ], 'image' ) ; ?>)
								</code>
							</p>
							<?php endif ; ?>

							<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_OPTM ] ) ) : ?>
							<p>
								<?php echo __('Images previously optimized', 'litespeed-cache') ; ?>:
								<code>
									<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_OPTM ] ) ; ?>
									(<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_OPTM ], 'image' ) ; ?>)
								</code>
							</p>
							<?php endif ; ?>

							<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR ] ) ) : ?>
							<p>
								<?php echo __('Images failed with other errors', 'litespeed-cache') ; ?>:
								<code>
									<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR ] ) ; ?>
									(<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR ], 'image' ) ; ?>)
								</code>
							</p>
							<?php endif ; ?>

							<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_MISS ] ) ) : ?>
							<p>
								<?php echo __('Image files missing', 'litespeed-cache') ; ?>:
								<code>
									<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_MISS ] ) ; ?>
									(<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_MISS ], 'image' ) ; ?>)
								</code>
							</p>
							<?php endif ; ?>

							<?php if ( ! empty( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_XMETA ] ) ) : ?>
							<p>
								<?php echo __('Images with wrong meta', 'litespeed-cache') ; ?>:
								<code>
									<?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_XMETA ] ) ; ?>
								</code>
							</p>
							<?php endif ; ?>

							<?php if ( ! empty( $optm_summary[ 'fetch_failed' ] ) ) : ?>
								<p>
									<?php echo __( 'Images failed to fetch', 'litespeed-cache' ) ; ?>: <code><?php echo $optm_summary[ 'fetch_failed' ] ; ?></code>
								</p>
							<?php endif ; ?>

							<?php if ( ! empty( $optm_summary[ 'notify_failed' ] ) ) : ?>
								<p>
									<?php echo __( 'Images failed to notify', 'litespeed-cache' ) ; ?>: <code><?php echo $optm_summary[ 'notify_failed' ] ; ?></code>
								</p>
							<?php endif ; ?>

						</div>

						<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a></p>

					</div>

					<div class="litespeed-width-1-2">
						<?php echo LiteSpeed_Cache_GUI::img_optm_clean_up_unfinished() ; ?>
					</div>

				</div>

				<hr />

				<div class="litespeed-empty-space-small"></div>

				<h3 class="litespeed-title">
					<?php echo __( 'Storage Optimization', 'litespeed-cache' ) ; ?>

					<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_CALC_BKUP ) ; ?>" class="dashicons dashicons-update litepseed-dash-icon-success" title="<?php echo __( 'Calculate Original Image Storage', 'litespeed-cache' ) ; ?>">
					</a>

				</h3>

				<?php if ( $storage_data ) : ?>
					<div class="">
					<p>
						<?php echo __( 'Last calculated', 'litespeed-cache' ) . ': <code>' . LiteSpeed_Cache_Utility::readable_time( $storage_data[ 'date' ] ) . '</code>' ; ?>
					</p>
					<?php if ( $storage_data[ 'count' ] ) : ?>
						<p>
							<?php echo __( 'Files', 'litespeed-cache' ) . ': <code>' . $storage_data[ 'count' ] . '</code>' ; ?>
						</p>
						<p>
							<?php echo __( 'Total', 'litespeed-cache' ) . ': <code>' . LiteSpeed_Cache_Utility::real_size( $storage_data[ 'sum' ] ) . '</code>' ; ?>
						</p>
					<?php endif ; ?>
					</div>
				<?php endif ; ?>

				<br />
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_RM_BKUP ) ; ?>" data-litespeed-cfm="<?php echo __( 'Are you sure to remove all image backups?', 'litespeed-cache' ) ; ?>" class="litespeed-btn-danger">
					<span class="dashicons dashicons-trash"></span>&nbsp;<?php echo __( 'Remove Original Image Backups', 'litespeed-cache' ) ; ?>
				</a>
				<div class="litespeed-desc">
					<?php echo __( 'This will delete all of the backups of the original images.', 'litespeed-cache' ) ; ?>
					<div class="litespeed-danger">
						🚨
						<?php echo __( 'This is irreversible.', 'litespeed-cache' ) ; ?>
						<?php echo __( 'You will be unable to Revert Optimization once the backups are deleted!', 'litespeed-cache' ) ; ?>
					</div>
				</div>
				<?php if ( $rm_log ) : ?>
					<div class="">
					<p>
						<?php echo __( 'Last ran', 'litespeed-cache' ) . ': <code>' . LiteSpeed_Cache_Utility::readable_time( $rm_log[ 'date' ] ) . '</code>' ; ?>
					</p>
					<p>
						<?php echo __( 'Files', 'litespeed-cache' ) . ': <code>' . $rm_log[ 'count' ] . '</code>' ; ?>
					</p>
					<p>
						<?php echo __( 'Saved', 'litespeed-cache' ) . ': <code>' . LiteSpeed_Cache_Utility::real_size( $rm_log[ 'sum' ] ) . '</code>' ; ?>
					</p>
					</div>
				<?php endif ; ?>

				<div class="litespeed-desc">
					<?php echo __( 'A backup of each image is saved before it is optimized.', 'litespeed-cache' ) ; ?>
					<?php echo __( 'The refresh button will calculate the total amount of disk space used by these backups.', 'litespeed-cache' ) ; ?>
				</div>


			</div>
		</div>
	</div>

	<div class="litespeed-width-3-10 litespeed-column-java litespeed-contrast" style="display: flex; flex-direction: column;">
		<div class="litespeed-hr">
			<?php include_once LSCWP_DIR . "admin/tpl/inc/img_optm.percentage_summary.php" ; ?>
		</div>

		<div class="litespeed-hr">
			<h3 class="litespeed-title">
				<?php echo __( 'Optimization Summary', 'litespeed-cache' ) ; ?>
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_SYNC_DATA ) ; ?>" class="dashicons dashicons-update litepseed-dash-icon-success" title="<?php echo __( 'Update Status', 'litespeed-cache' ) ; ?>">
				</a>
			</h3>
			<p>
				<?php echo __( 'Total Reduction', 'litespeed-cache' ) ; ?>: <code><?php echo LiteSpeed_Cache_Utility::real_size( $optm_summary[ 'reduced' ] ) ; ?></code>
			</p>
			<p>
				<?php echo __( 'Images Pulled', 'litespeed-cache' ) ; ?>: <code><?php echo $optm_summary[ 'img_taken' ] ; ?></code>
			</p>
			<p>
				<?php echo __( 'Last Request', 'litespeed-cache' ) ; ?>: <code><?php echo LiteSpeed_Cache_Utility::readable_time( $optm_summary[ 'last_requested' ] ) ; ?></code>
			</p>
		</div>

		<div class="litespeed-hr">
			<h3 class="litespeed-title"><?php echo __('Revert Optimization', 'litespeed-cache') ; ?></h3>

			<div class="litespeed-desc">
				<?php echo __( 'Switch all images in the media library back to their original unoptimized versions.', 'litespeed-cache' ) ; ?>
			</div>

			<div>
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IAPI, LiteSpeed_Cache_Admin_API::TYPE_RESET_KEY ) ; ?>" class="litespeed-btn-warning" title="<?php echo __( 'The current IAPI key must be reset after changing home URL or domain before making any further optimization requests.', 'litespeed-cache' ) ; ?>">
					<span class="dashicons dashicons-image-rotate"></span>&nbsp;<?php echo __( 'Reset IAPI Key', 'litespeed-cache' ) ; ?>
				</a>
					<br />
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_BATCH_SWITCH_ORI ) ; ?>" class="litespeed-btn-success" title="<?php echo __( 'Revert all optimized images back to their original versions.', 'litespeed-cache' ) ; ?>">
					<span class="dashicons dashicons-undo"></span>&nbsp;<?php echo __( 'Undo Optimization', 'litespeed-cache' ) ; ?>
				</a>

				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_BATCH_SWITCH_OPTM ) ; ?>" class="litespeed-btn-success" title="<?php echo __( 'Switch back to using optimized images.', 'litespeed-cache' ) ; ?>">
					<span class="dashicons dashicons-redo"></span>&nbsp;<?php echo __( 'Re-do Optimization', 'litespeed-cache' ) ; ?>
				</a>

				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_OPTIMIZE_RESCAN ) ; ?>" class="litespeed-btn-success litespeed-hide" title="<?php echo __( 'Scan for any new unoptimized image thumbnail sizes and resend necessary image optimization requests.', 'litespeed-cache' ) ; ?>">
					<?php echo __( 'Send New Thumbnail Requests', 'litespeed-cache' ) ; ?>
				</a>

				<p>
					<?php echo sprintf( __( 'Results can be checked in <a %s>Media Library</a>.', 'litespeed-cache' ), 'href="upload.php?mode=list"' ) ; ?>
				</p>

			</div>

		</div>

		<div style="flex-grow: 1;"></div>

	<!--    <div class="litespeed-empty-space-xlarge">
		</div>-->
		<div class="">

			<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_OPTM_DESTROY ) ; ?>" class="litespeed-btn-danger">
				<span class="dashicons dashicons-dismiss"></span>&nbsp;<?php echo __( 'Destroy All Optimization Data!', 'litespeed-cache' ) ; ?>
			</a>

			<div class="litespeed-desc">
				<?php echo __( 'Remove all previous image optimization requests/results, revert completed optimizations, and delete all optimization files.', 'litespeed-cache' ) ; ?>
				<div class="litespeed-warning">
					⚠️
					<?php echo __( 'This will also reset the credit level.', 'litespeed-cache' ) ; ?>
				</div>
			</div>



		</div>
