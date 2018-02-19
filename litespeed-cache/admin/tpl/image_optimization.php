<?php
if ( ! defined( 'WPINC' ) ) die ;

$media = LiteSpeed_Cache_Media::get_instance() ;

$img_count = $media->img_count() ;
$optm_summary = $media->summary_info() ;

list( $last_run, $is_running ) = $media->cron_running( false ) ;

$_optm_summary_list = array(
	'level'	=> array(
		'title'	=> __( 'Level', 'litespeed-cache' ),
		'must'	=> true,
	),
	'credit'	=> array(
		'title'	=> __( 'Credit', 'litespeed-cache' ),
		'desc'	=> __( 'Credit recovers with each successful pull.', 'litespeed-cache' ),
		'must'	=> true,
	),
	'reduced'	=> array(
		'title'	=> __( 'Total Reduction', 'litespeed-cache' ),
		'type'	=> 'file_size',
	),
	'img_taken'	=> array(
		'title'	=> __( 'Images pulled', 'litespeed-cache' ),
	),
	'fetch_failed'	=> array(
		'title'	=> __( 'Images failed to fetch', 'litespeed-cache' ),
	),
	'notify_failed'	=> array(
		'title'	=> __( 'Images failed to notify', 'litespeed-cache' ),
	),
	'pull_failed'	=> array(
		'title'	=> __( 'Images failed to pull', 'litespeed-cache' ),
	),
	'last_requested'	=> array(
		'title'	=> __( 'Last Request', 'litespeed-cache' ),
		'type'	=> 'date',
	),
) ;


include_once LSCWP_DIR . "admin/tpl/inc/banner_promo.php" ;
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Image Optimization', 'litespeed-cache') ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
	</span>
	<hr class="wp-header-end">
</div>

<?php include_once LSCWP_DIR . "admin/tpl/inc/check_cache_disabled.php" ; ?>

<div class="litespeed-wrap">
	<div class="litespeed-body">
		<h3 class="litespeed-title"><?php echo __('Optimization Summary', 'litespeed-cache') ; ?></h3>

		<?php foreach ( $_optm_summary_list as $k => $v ) : ?>
			<?php if ( isset( $optm_summary[ $k ] ) && ( $optm_summary[ $k ] || ! empty( $v[ 'must' ] ) ) ) : ?>
			<p>
				<?php echo $v[ 'title' ] ; ?>:
				<b>
					<?php
					if ( ! empty( $v[ 'type' ] ) ) {
						if ( $v[ 'type' ] == 'file_size' ) {
							echo LiteSpeed_Cache_Utility::real_size( $optm_summary[ $k ] ) ;
						}
						if ( $v[ 'type' ] == 'date' ) {
							echo LiteSpeed_Cache_Utility::readable_time( $optm_summary[ $k ] ) ;
						}
					}
					else {
						echo $optm_summary[ $k ] ;
					}

					if ( ! empty( $v[ 'desc' ] ) ) {
						echo '<span class="litespeed-desc">' . $v[ 'desc' ] . '</span>' ;
					}
					?>
				</b>
			</p>
			<?php endif ; ?>
		<?php endforeach ; ?>

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_SYNC_DATA ) ; ?>" class="litespeed-btn-success">
			<?php echo __( 'Update Reduction Status', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'This will communicate with LiteSpeed\'s Image Optimization Server and retrieve the most recent status.', 'litespeed-cache' ) ; ?>
		</span>

		<?php include_once LSCWP_DIR . "admin/tpl/inc/api_key.php" ; ?>

		<h3 class="litespeed-title"><?php echo __('Image Information', 'litespeed-cache') ; ?>
			<span class="litespeed-desc"><?php echo __('Beta Version', 'litespeed-cache') ; ?></span>
		</h3>

		<p><?php echo sprintf( __( '<a %s>Image groups</a> total', 'litespeed-cache'), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization:image-groups" target="_blank"' ) ; ?>: <b><?php echo $img_count[ 'total_img' ] ; ?></b></p>
		<p><?php echo __('Image groups not yet requested', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_not_requested' ] ; ?></b></p>
		<?php if ( $img_count[ 'total_not_requested' ] ) : ?>
		<?php if ( empty( $optm_summary[ 'level' ] ) ) : ?>
			<a href="#" class="litespeed-btn-default disabled">
				<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
			</a>
			<span class="litespeed-desc">
				<?php echo sprintf( __( 'Please press the %s button before sending a new request.', 'litespeed-cache' ), __( 'Update Reduction Status', 'litespeed-cache' ) ) ; ?>
			</span>
			<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
		<?php else : ?>
			<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_OPTIMIZE ) ; ?>" class="litespeed-btn-success">
				<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
			</a>
			<span class="litespeed-desc">
				<?php echo __( 'This will send the optimization request and the images to LiteSpeed\'s Image Optimization Server.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'You can send at most %s images at once.', 'litespeed-cache' ), '<code>' . $optm_summary[ 'credit' ] . '</code>' ) ; ?>
			</span>
		<?php endif ; ?>
		<?php endif ; ?>

		<hr />

		<p>
			<?php echo __('Image groups requested', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_requested' ] ; ?></b>
		</p>
		<p><?php echo __('Image groups failed to optimize', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_err' ] ; ?></b></p>
		<p class="litespeed-desc">
			<?php echo __( 'After LiteSpeed\'s Image Optimization Server finishes optimization, it will notify your site to pull the optimized images.', 'litespeed-cache' ) ; ?>
			<?php echo __( 'This process is automatic.', 'litespeed-cache' ) ; ?>
		</p>
		<p>
			<?php echo __('Image groups notified to pull', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_server_finished' ] ; ?></b>
			<?php if ( $img_count[ 'total_server_finished' ] && ! $is_running ) : ?>
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_PULL ) ; ?>" class="litespeed-btn-success">
					<?php echo __( 'Pull Images', 'litespeed-cache' ) ; ?>
				</a>
				<span class="litespeed-desc">
					<?php echo __( 'Only press the button if the pull cron job is disabled.', 'litespeed-cache' ) ; ?>
					<?php echo __( 'Images will be pulled automatically if the cron job is running.', 'litespeed-cache' ) ; ?>
				</span>
			<?php elseif ( $last_run ) : ?>
				<span class="litespeed-desc">
					<?php echo sprintf( __( 'Last pull initiated by cron at %s.', 'litespeed-cache' ), '<code>' . LiteSpeed_Cache_Utility::readable_time( $last_run ) . '</code>' ) ; ?>
				</span>
			<?php endif ; ?>
		</p>
		<p><?php echo __('Image groups optimized and pulled', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_pulled' ] ; ?></b></p>
		<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a></p>

		<hr />

	<?php if ( ! empty( $optm_summary[ 'level' ] ) ) : ?>
		<h3 class="litespeed-title"><?php echo __('Revert Optimization', 'litespeed-cache') ; ?></h3>

		<span class="litespeed-desc">
			<?php echo __( 'Switch all images in the media library back to their original unoptimized versions.', 'litespeed-cache' ) ; ?>
		</span>

		<br />

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_BATCH_SWITCH_ORI ) ; ?>" class="litespeed-btn-danger">
			<?php echo __( 'Undo Optimization', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Revert all optimized images back to their original versions.', 'litespeed-cache' ) ; ?>
		</span>

		<br />

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_BATCH_SWITCH_OPTM ) ; ?>" class="litespeed-btn-warning">
			<?php echo __( 'Re-do Optimization', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Switch back to using optimized images.', 'litespeed-cache' ) ; ?>
		</span>

		<br />
		<p>
			<?php echo sprintf( __( 'Results can be checked in <a %s>Media Library</a>.', 'litespeed-cache' ), 'href="upload.php?mode=list"' ) ; ?>
		</p>

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_OPTIMIZE_RESCAN ) ; ?>" class="litespeed-btn-success">
			<?php echo __( 'Send New Thumbnail Requests', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Scan for any new unoptimized image thumbnail sizes and resend necessary image optimization requests.', 'litespeed-cache' ) ; ?>
		</span>

		<br />
		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IAPI, LiteSpeed_Cache_Admin_API::TYPE_RESET_KEY ) ; ?>" class="litespeed-btn-warning">
			<?php echo __( 'Reset IAPI Key', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'The current IAPI key must be reset after changing home URL or domain before making any further optimization requests.', 'litespeed-cache' ) ; ?>
		</span>

		<br />
		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_OPTIMIZE_DESTROY ) ; ?>" class="litespeed-btn-danger">
			<?php echo __( 'Destroy All Optimization Data!', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Remove all previous image optimization requests/results, revert completed optimizations, and delete all optimization files.', 'litespeed-cache' ) ; ?>
			<font class="litespeed-warning">
				<?php echo __('NOTE:', 'litespeed-cache'); ?>
				<?php echo sprintf( __( 'If there are unfinished requests in progress, the requests\' credits will NOT be recovered.', 'litespeed-cache' ), 'jQuery', __( 'JS Combine', 'litespeed-cache' ) ) ; ?>
			</font>

		</span>
	<?php endif ; ?>


	</div>
</div>
