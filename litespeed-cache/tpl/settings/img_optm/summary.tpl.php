<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$closest_server = Cloud::get_summary( 'server.' . Cloud::SVC_IMG_OPTM );
$usage_cloud = Cloud::get_summary( 'usage.' . Cloud::SVC_IMG_OPTM );
$credit_left = '-';
if ( ! empty( $usage_cloud[ 'quota' ] ) ) {
	$credit_left = $usage_cloud[ 'quota' ] - $usage_cloud[ 'used' ];
}

$optm_summary = Img_Optm::get_summary() ;

$img_count = Img_Optm::get_instance()->img_count() ;

list( $last_run, $is_running ) = Img_Optm::get_instance()->cron_running( false ) ;

if ( ! empty( $img_count[ 'groups_all' ] ) ) {
	$gathered_percentage = 100 - floor( $img_count[ 'groups_not_gathered' ] * 100 / $img_count[ 'groups_all' ] ) ;
}
else {
	$gathered_percentage = 0 ;
}

if ( ! empty( $img_count[ 'imgs_gathered' ] ) ) {
	$finished_percentage = 100 - floor( $img_count[ 'imgs_raw' ] * 100 / $img_count[ 'imgs_gathered' ] ) ;
}
else {
	$finished_percentage = 0 ;
}
?>
<div class="litespeed-flex-container litespeed-column-with-boxes">
	<div class="litespeed-width-7-10">
		<div class="litespeed-empty-space-small"></div>
		<div class="litespeed-text-center">
			<a data-litespeed-onlyonce class="button button-primary litespeed-btn-large"
				<?php if ( ! empty( $img_count[ 'groups_not_gathered' ] ) || ! empty( $img_count[ 'imgs_raw' ] ) ) : ?>
					href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_NEW_REQ ) ; ?>"
				<?php else : ?>
					href='javascript:;' disabled
				<?php endif ; ?>
				>
				<span class="dashicons dashicons-images-alt2"></span>&nbsp;<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
			</a>
		</div>

		<div class="litespeed-empty-space-small"></div>

		<div class="litespeed-desc">
			<?php if ( $closest_server ) : ?>
				<i title="<?php echo $closest_server ; ?>" class='litespeed-quic-icon'></i>
			<?php endif ; ?>
			<?php echo __( 'This will send the optimization request to QUIC.cloud\'s Image Optimization Server.', 'litespeed-cache' ) ; ?>
			<?php echo sprintf( __( 'You have %s points left this month.', 'litespeed-cache' ), '<code>' . $credit_left . '</code>' ) ; ?>
			<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
		</div>

		<div>
			<a data-litespeed-onlyonce class="button litespeed-btn-success" title="<?php echo __( 'Only press the button if the pull cron job is disabled.', 'litespeed-cache' ) ; ?> <?php echo __( 'Images will be pulled automatically if the cron job is running.', 'litespeed-cache' ) ; ?>"
				<?php if ( ! empty( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ] ) && ! $is_running ) : ?>
					href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_PULL ) ; ?>"
				<?php else : ?>
					href='javascript:;' disabled
				<?php endif ; ?>
				>
				<?php echo __( 'Pull Images', 'litespeed-cache' ) ; ?>
			</a>
		</div>

		<div class="litespeed-empty-space-medium"></div>

		<div>
			<h3 class="litespeed-title-short">
				<?php echo __( 'Current Stage Status', 'litespeed-cache' ) ; ?>
				<?php if ( $img_count[ 'groups_raw' ] ) : ?>
					<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
				<?php endif; ?>
			</h3>
			<div class="litespeed-empty-space-medium"></div>

			<hr class="litespeed-hr-dotted">

			<div class="litespeed-empty-space-small"></div>

			<div class="litespeed-light-code">

				<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ) : ?>
				<p class="litespeed-success">
					<?php echo __('Images requested', 'litespeed-cache') ; ?>:
					<code>
						<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_REQUESTED ] ) ; ?>
						(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ], 'image' ) ; ?>)
					</code>
				</p>
				<p class="litespeed-desc">
					<?php echo __( 'After LiteSpeed\'s Image Optimization Server finishes optimization, it will notify your site to pull the optimized images.', 'litespeed-cache' ) ; ?>
					<?php echo __( 'This process is automatic.', 'litespeed-cache' ) ; ?>
				</p>
				<?php endif ; ?>

				<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ) : ?>
					<p class="litespeed-success">
						<?php echo __('Images notified to pull', 'litespeed-cache') ; ?>:
						<code>
							<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_NOTIFIED ] ) ; ?>
							(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ], 'image' ) ; ?>)
						</code>

					</p>
					<?php if ( $last_run ) : ?>
						<p class="litespeed-desc">
							<?php echo sprintf( __( 'Last pull initiated by cron at %s.', 'litespeed-cache' ), '<code>' . Utility::readable_time( $last_run ) . '</code>' ) ; ?>
						</p>
					<?php endif ; ?>
				<?php endif ; ?>

				<div class="litespeed-empty-space-small"></div>

				<div class="litespeed-flex-container">

					<div class="litespeed-width-1-2">

						<?php if ( ! empty( $img_count[ 'group.' . Img_Optm::STATUS_PULLED ] ) ) : ?>
						<p class="litespeed-success">
							<?php echo __('Images optimized and pulled', 'litespeed-cache') ; ?>:
							<code>
								<?php echo Admin_Display::print_plural( $img_count[ 'group.' . Img_Optm::STATUS_PULLED ] ) ; ?>
								(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . Img_Optm::STATUS_PULLED ], 'image' ) ; ?>)
							</code>
						</p>
						<?php endif ; ?>

						<div class="litespeed-silence">
							<?php
								$list = array(
									Img_Optm::STATUS_ERR_FETCH	=> __('Images failed to fetch', 'litespeed-cache'),
									Img_Optm::STATUS_ERR_OPTM	=> __('Images previously optimized', 'litespeed-cache'),
									Img_Optm::STATUS_ERR			=> __('Images failed with other errors', 'litespeed-cache'),
									Img_Optm::STATUS_MISS		=> __('Image files missing', 'litespeed-cache'),
									Img_Optm::STATUS_DUPLICATED	=> __('Image files duplicated', 'litespeed-cache'),
									Img_Optm::STATUS_XMETA		=> __('Images with wrong meta', 'litespeed-cache'),
								);
							?>
							<?php foreach ( $list as $k => $v ): ?>
							<?php if ( ! empty( $img_count[ 'group.' . $k ] ) ) : ?>
							<p>
								<?php echo $v; ?>:
								<code>
									<?php echo Admin_Display::print_plural( $img_count[ 'group.' . $k ] ); ?>
									(<?php echo Admin_Display::print_plural( $img_count[ 'img.' . $k ], 'image' ); ?>)
								</code>
							</p>
							<?php endif; ?>
							<?php endforeach; ?>

							<?php if ( ! empty( $optm_summary[ 'fetch_failed' ] ) ) : ?>
								<p>
									<?php echo __( 'Images failed to fetch', 'litespeed-cache' ) ; ?>: <code><?php echo $optm_summary[ 'fetch_failed' ] ; ?></code>
								</p>
							<?php endif ; ?>

						</div>

					</div>

					<div class="litespeed-width-1-2">
						<?php $unfinished_num = $img_count[ 'img.' . Img_Optm::STATUS_REQUESTED ] + $img_count[ 'img.' . Img_Optm::STATUS_NOTIFIED ] + $img_count[ 'img.' . Img_Optm::STATUS_ERR_FETCH ]; ?>
						<?php echo sprintf(
								'<a href="%1$s" class="button litespeed-btn-warning" title="%2$s"><span class="dashicons dashicons-editor-removeformatting"></span>&nbsp;%3$s</a>',
								Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CLEAN ),
								__( 'Remove all previous unfinished image optimization requests.', 'litespeed-cache' ),
								__( 'Clean Up Unfinished Data', 'litespeed-cache' ) . ( $unfinished_num ? ': ' . Admin_Display::print_plural( $unfinished_num, 'image' ) : '')
							);
						?>
					</div>

				</div>



				<div class="litespeed-empty-space-small"></div>

				<h3 class="litespeed-title-short">
					<?php echo __( 'Storage Optimization', 'litespeed-cache' ) ; ?>

					<a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CALC_BKUP ) ; ?>" class="dashicons dashicons-update litepseed-dash-icon-success" title="<?php echo __( 'Calculate Original Image Storage', 'litespeed-cache' ) ; ?>">
					</a>
				</h3>

				<div class="litespeed-desc">
					<?php echo __( 'A backup of each image is saved before it is optimized.', 'litespeed-cache' ) ; ?>
					<?php echo __( 'The refresh button will calculate the total amount of disk space used by these backups.', 'litespeed-cache' ) ; ?>
				</div>

				<?php if ( ! empty( $optm_summary[ 'bk_summary' ] ) ) : ?>
					<div class="">
					<p>
						<?php echo __( 'Last calculated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $optm_summary[ 'bk_summary' ][ 'date' ] ) . '</code>' ; ?>
					</p>
					<?php if ( $optm_summary[ 'bk_summary' ][ 'count' ] ) : ?>
						<p>
							<?php echo __( 'Files', 'litespeed-cache' ) . ': <code>' . $optm_summary[ 'bk_summary' ][ 'count' ] . '</code>' ; ?>
						</p>
						<p>
							<?php echo __( 'Total', 'litespeed-cache' ) . ': <code>' . Utility::real_size( $optm_summary[ 'bk_summary' ][ 'sum' ] ) . '</code>' ; ?>
						</p>
					<?php endif ; ?>
					</div>
				<?php endif ; ?>
				<hr class="litespeed-hr-with-space" />
				<div><a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RM_BKUP ) ; ?>" data-litespeed-cfm="<?php echo __( 'Are you sure to remove all image backups?', 'litespeed-cache' ) ; ?>" class="button litespeed-btn-danger">
					<span class="dashicons dashicons-trash"></span>&nbsp;<?php echo __( 'Remove Original Image Backups', 'litespeed-cache' ) ; ?>
				</a></div>
				<div class="litespeed-desc">
					<?php echo __( 'This will delete all of the backups of the original images.', 'litespeed-cache' ) ; ?>
					<div class="litespeed-danger">
						🚨
						<?php echo __( 'This is irreversible.', 'litespeed-cache' ) ; ?>
						<?php echo __( 'You will be unable to Revert Optimization once the backups are deleted!', 'litespeed-cache' ) ; ?>
					</div>
				</div>
				<?php if ( ! empty( $optm_summary[ 'rmbk_summary' ] ) ) : ?>
					<div class="">
					<p>
						<?php echo __( 'Last ran', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $optm_summary[ 'rmbk_summary' ][ 'date' ] ) . '</code>' ; ?>
					</p>
					<p>
						<?php echo __( 'Files', 'litespeed-cache' ) . ': <code>' . $optm_summary[ 'rmbk_summary' ][ 'count' ] . '</code>' ; ?>
					</p>
					<p>
						<?php echo __( 'Saved', 'litespeed-cache' ) . ': <code>' . Utility::real_size( $optm_summary[ 'rmbk_summary' ][ 'sum' ] ) . '</code>' ; ?>
					</p>
					</div>
				<?php endif ; ?>

			</div>
		</div>
	</div>

	<div class="litespeed-width-3-10">
		<div class="postbox litespeed-postbox"><div class="inside">

			<h3 class="litespeed-title">
				<?php echo __( 'Image Information', 'litespeed-cache' ) ; ?>
			</h3>

			<div class="litespeed-flex-container">
				<div class="litespeed-icon-vertical-middle">
					<?php echo GUI::pie( $gathered_percentage, 100, true ) ; ?>
				</div>
				<div>
					<p>
						<?php echo __( 'Images total', 'litespeed-cache') ; ?>:

						<code><?php echo Admin_Display::print_plural( $img_count[ 'groups_all' ] ) ; ?></code>

						<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization:image-groups" target="_blank" class="litespeed-desc litespeed-left20" title="<?php echo __( 'What is a group?', 'litespeed-cache') ; ?>">?</a>
					</p>
					<p>
						<?php if ( ! empty( $img_count[ 'groups_not_gathered' ] ) ) : ?>
							<?php echo __('Images not yet gathered', 'litespeed-cache') ; ?>:
							<code><?php echo Admin_Display::print_plural( $img_count[ 'groups_not_gathered' ] ) ; ?></code>
						<?php else : ?>
							<font class="litespeed-congratulate"><?php echo __('Congratulations, all gathered!', 'litespeed-cache') ; ?></font>
						<?php endif ; ?>
					</p>
				</div>
			</div>

			<div class="litespeed-flex-container">
				<div class="litespeed-icon-vertical-middle">
					<?php echo GUI::pie( $finished_percentage, 100, true ) ; ?>
				</div>
				<div>
					<p>
						<?php echo __( 'Images total', 'litespeed-cache') ; ?>:

						<code><?php echo Admin_Display::print_plural( $img_count[ 'imgs_gathered' ], 'image' ) ; ?></code>

						<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization:image-groups" target="_blank" class="litespeed-desc litespeed-left20" title="<?php echo __( 'What is a group?', 'litespeed-cache') ; ?>">?</a>
					</p>
					<p>
						<?php if ( ! empty( $img_count[ 'imgs_raw' ] ) ) : ?>
							<?php echo __('Images not yet requested', 'litespeed-cache') ; ?>:
							<code><?php echo Admin_Display::print_plural( $img_count[ 'imgs_raw' ], 'image' ) ; ?></code>
						<?php else : ?>
							<font class="litespeed-congratulate"><?php echo __('Congratulations, all requested!', 'litespeed-cache') ; ?></font>
						<?php endif ; ?>
					</p>
				</div>
			</div>

		</div></div>

		<div class="postbox litespeed-postbox"><div class="inside">
			<h3 class="litespeed-title">
				<?php echo __( 'Optimization Summary', 'litespeed-cache' ) ; ?>
			</h3>
			<p>
				<?php echo __( 'Total Reduction', 'litespeed-cache' ) ; ?>: <code><?php echo isset( $optm_summary[ 'reduced' ] ) ? Utility::real_size( $optm_summary[ 'reduced' ] ) : '-'; ?></code>
			</p>
			<p>
				<?php echo __( 'Images Pulled', 'litespeed-cache' ) ; ?>: <code><?php echo isset( $optm_summary[ 'img_taken' ] ) ? $optm_summary[ 'img_taken' ] : '-'; ?></code>
			</p>
			<p>
				<?php echo __( 'Last Request', 'litespeed-cache' ) ; ?>: <code><?php echo isset( $optm_summary[ 'last_requested' ] ) ? Utility::readable_time( $optm_summary[ 'last_requested' ] ) : '-'; ?></code>
			</p>
		</div></div>

		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title"><?php echo __('Revert Optimization', 'litespeed-cache') ; ?></h3>

				<div class="litespeed-desc">
					<?php echo __( 'Switch all images in the media library back to their original unoptimized versions.', 'litespeed-cache' ) ; ?>
				</div>

				<div>
					<a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_IMG_BATCH_SWITCH_ORI ) ; ?>" class="button litespeed-btn-success" title="<?php echo __( 'Revert all optimized images back to their original versions.', 'litespeed-cache' ) ; ?>">
						<span class="dashicons dashicons-undo"></span>&nbsp;<?php echo __( 'Undo Optimization', 'litespeed-cache' ) ; ?>
					</a>

					<a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_IMG_BATCH_SWITCH_OPTM ) ; ?>" class="button litespeed-btn-success" title="<?php echo __( 'Switch back to using optimized images.', 'litespeed-cache' ) ; ?>">
						<span class="dashicons dashicons-redo"></span>&nbsp;<?php echo __( 'Re-do Optimization', 'litespeed-cache' ) ; ?>
					</a>

					<a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RESCAN ) ; ?>" class="button litespeed-btn-success" title="<?php echo __( 'Scan for any new unoptimized image thumbnail sizes and resend necessary image optimization requests.', 'litespeed-cache' ) ; ?>">
						<?php echo __( 'Rescan New Thumbnails', 'litespeed-cache' ) ; ?>
					</a>

					<p class="litespeed-desc">
						<?php echo sprintf( __( 'Results can be checked in <a %s>Media Library</a>.', 'litespeed-cache' ), 'href="upload.php?mode=list"' ) ; ?>
					</p>

				</div>

			</div>
			<div class="inside litespeed-postbox-footer">

				<div><a href="<?php echo Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_DESTROY ) ; ?>" class="button litespeed-btn-danger" data-litespeed-cfm="<?php echo __( 'Are you sure to destroy all optimized images?', 'litespeed-cache' ) ; ?>" >
					<span class="dashicons dashicons-dismiss"></span>&nbsp;<?php echo __( 'Destroy All Optimization Data!', 'litespeed-cache' ) ; ?>
				</a></div>

				<div class="litespeed-desc">
					<?php echo __( 'Remove all previous image optimization requests/results, revert completed optimizations, and delete all optimization files.', 'litespeed-cache' ) ; ?>
				</div>
				</div>
		</div>
	</div>
</div>

