<?php
if ( ! defined( 'WPINC' ) ) die ;

// Update table data for upgrading
LiteSpeed_Cache_Data::get_instance() ;

$img_optm = LiteSpeed_Cache_Img_Optm::get_instance() ;

$img_count = $img_optm->img_count() ;
$optm_summary = $img_optm->summary_info() ;

list( $last_run, $is_running ) = $img_optm->cron_running( false ) ;

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

// Guidance check
$current_step = false ;
if ( empty( $optm_summary[ 'level' ] ) || $optm_summary[ 'level' ] < 2 ) {
	$current_step = $img_optm->get_guidance_pos() ;
}
$guidance_steps = array(
	sprintf( __( 'Click the %s button.', 'litespeed-cache' ), '<font class="litespeed-success">' . __( 'Update Status', 'litespeed-cache' ) . '</font>' ),
	sprintf( __( 'Click the %s button.', 'litespeed-cache' ), '<font class="litespeed-success">' . __( 'Send Optimization Request', 'litespeed-cache' ) . '</font>' ),
	sprintf( __( 'Click the %s button or wait for the cron job to finish the pull action.', 'litespeed-cache' ), '<font class="litespeed-success">' . __( 'Pull Images', 'litespeed-cache' ) . '</font>' ),
	__( 'Repeat the above steps until you have leveled up.', 'litespeed-cache' )
) ;

if ( ! empty( $img_count[ 'total_img' ] ) ) {
	$finished_percentage = 100 - floor( $img_count[ 'total_not_requested' ] * 100 / $img_count[ 'total_img' ] ) ;
}
else {
	$finished_percentage = 0 ;
}

LiteSpeed_Cache_GUI::show_promo() ;
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

<div class="litespeed-wrap">
	<div class="litespeed-body">
		<?php if ( $current_step ) : ?>
			<?php echo LiteSpeed_Cache_Admin_Display::guidance( __( 'How to Level Up', 'litespeed-cache' ), $guidance_steps, $current_step ) ; ?>
		<?php endif ; ?>

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

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_SYNC_DATA ) ; ?>" class="litespeed-btn-success">
			<?php echo __( 'Update Status', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'This will communicate with LiteSpeed\'s Image Optimization Server and retrieve the most recent status.', 'litespeed-cache' ) ; ?>
		</span>

		<?php include_once LSCWP_DIR . "admin/tpl/inc/api_key.php" ; ?>

		<h3 class="litespeed-title"><?php echo __('Image Information', 'litespeed-cache') ; ?></h3>

		<div class="litespeed-block-tiny">
			<div class="litespeed-col-auto">
				<?php echo LiteSpeed_Cache_GUI::pie( $finished_percentage, 100, true ) ; ?>
			</div>

			<div class="litespeed-col-auto">
				<p>
					<?php echo __( 'Images total', 'litespeed-cache') ; ?>:
					<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'total_img' ] ) ; ?></b>
					<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization:image-groups" target="_blank" class="litespeed-desc litespeed-left20"><?php echo __( 'What is a group?', 'litespeed-cache') ; ?></a>
				</p>
				<p>
					<?php echo __('Images not yet requested', 'litespeed-cache') ; ?>:
					<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'total_not_requested' ] ) ; ?></b>
				</p>
			</div>
		</div>

		<?php if ( $img_count[ 'total_not_requested' ] ) : ?>
		<?php if ( empty( $optm_summary[ 'level' ] ) ) : ?>
			<a href="#" class="litespeed-btn-default disabled">
				<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
			</a>
			<span class="litespeed-desc">
				<?php echo sprintf( __( 'Please press the %s button before sending a new request.', 'litespeed-cache' ), __( 'Update Status', 'litespeed-cache' ) ) ; ?>
			</span>
			<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
		<?php else : ?>
			<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_OPTIMIZE ) ; ?>" class="litespeed-btn-success">
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
			<?php echo __('Images requested', 'litespeed-cache') ; ?>:
			<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_REQUESTED ] ) ; ?></b>
			(<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_REQUESTED ], 'image' ) ; ?></b>)
		</p>
		<p class="litespeed-desc">
			<?php echo __( 'After LiteSpeed\'s Image Optimization Server finishes optimization, it will notify your site to pull the optimized images.', 'litespeed-cache' ) ; ?>
			<?php echo __( 'This process is automatic.', 'litespeed-cache' ) ; ?>
		</p>
		<p>
			<?php echo __('Images notified to pull', 'litespeed-cache') ; ?>:
			<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ] ) ; ?></b>
			(<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ], 'image' ) ; ?></b>)

			<?php if ( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_NOTIFIED ] && ! $is_running ) : ?>
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_PULL ) ; ?>" class="litespeed-btn-success">
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
		<p>
			<?php echo __('Images optimized and pulled', 'litespeed-cache') ; ?>:
			<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_PULLED ] ) ; ?></b>
			(<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_PULLED ], 'image' ) ; ?></b>)
		</p>

		<div class="litespeed-desc litespeed-left20">
			<p>
				<?php echo __('Images failed to fetch', 'litespeed-cache') ; ?>:
				<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH ] ) ; ?></b>
				(<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_FETCH ], 'image' ) ; ?></b>)
			</p>
			<p>
				<?php echo __('Images failed to optimize', 'litespeed-cache') ; ?>:
				<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_OPTM ] ) ; ?></b>
				(<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR_OPTM ], 'image' ) ; ?></b>)
			</p>
			<p>
				<?php echo __('Images failed with other errors', 'litespeed-cache') ; ?>:
				<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR ] ) ; ?></b>
				(<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_ERR ], 'image' ) ; ?></b>)
			</p>
			<p>
				<?php echo __('Image files missing', 'litespeed-cache') ; ?>:
				<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'group.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_MISS ] ) ; ?></b>
				(<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_MISS ], 'image' ) ; ?></b>)
			</p>
			<p>
				<?php echo __('Images with wrong meta', 'litespeed-cache') ; ?>:
				<b><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'img.' . LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS_XMETA ] ) ; ?></b>
			</p>
		</div>

		<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a></p>

		<hr />

	<?php if ( ! empty( $optm_summary[ 'level' ] ) ) : ?>
		<h3 class="litespeed-title"><?php echo __('Revert Optimization', 'litespeed-cache') ; ?></h3>

		<span class="litespeed-desc">
			<?php echo __( 'Switch all images in the media library back to their original unoptimized versions.', 'litespeed-cache' ) ; ?>
		</span>

		<br />

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_BATCH_SWITCH_ORI ) ; ?>" class="litespeed-btn-danger">
			<?php echo __( 'Undo Optimization', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Revert all optimized images back to their original versions.', 'litespeed-cache' ) ; ?>
		</span>

		<br />

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_BATCH_SWITCH_OPTM ) ; ?>" class="litespeed-btn-warning">
			<?php echo __( 'Re-do Optimization', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Switch back to using optimized images.', 'litespeed-cache' ) ; ?>
		</span>

		<br />
		<p>
			<?php echo sprintf( __( 'Results can be checked in <a %s>Media Library</a>.', 'litespeed-cache' ), 'href="upload.php?mode=list"' ) ; ?>
		</p>

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_OPTIMIZE_RESCAN ) ; ?>" class="litespeed-btn-success">
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
		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_OPTM_DESTROY_UNFINISHED ) ; ?>" class="litespeed-btn-warning">
			<?php echo __( 'Clean Up Unfinished Data', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Remove all previous unfinished image optimization requests.', 'litespeed-cache' ) ; ?>
		</span>

		<br />
		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_IMG_OPTM_DESTROY ) ; ?>" class="litespeed-btn-danger">
			<?php echo __( 'Destroy All Optimization Data!', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'Remove all previous image optimization requests/results, revert completed optimizations, and delete all optimization files.', 'litespeed-cache' ) ; ?>
			<font class="litespeed-warning">
				<?php echo __('NOTE', 'litespeed-cache'); ?>:
				<?php echo __( 'This will also reset the credit level.', 'litespeed-cache' ) ; ?>
			</font>
		</span>

	<?php endif ; ?>


	</div>
</div>
