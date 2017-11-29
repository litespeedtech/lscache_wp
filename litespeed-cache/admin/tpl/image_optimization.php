<?php
if ( ! defined( 'WPINC' ) ) die ;

$media = LiteSpeed_Cache_Media::get_instance() ;
$img_count = $media->img_count() ;
list( $last_run, $is_running ) = $media->cron_running( false ) ;

include_once LSWCP_DIR . "admin/tpl/inc/banner_promo.php" ;
?>

<div class="wrap">
	<h2>
		<?php echo __('LiteSpeed Cache Image Optimization', 'litespeed-cache') ; ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
		</span>
	</h2>
</div>

<div class="litespeed-wrap">
	<div class="litespeed-body">
		<h3 class="litespeed-title"><?php echo __('Optimization Summary', 'litespeed-cache') ; ?></h3>

		<?php if ( $img_count[ 'reduced' ] ) : ?>
			<p>
				<?php echo __('Total Reduction:', 'litespeed-cache') ; ?>
				<b><?php echo LiteSpeed_Cache_Utility::real_size( $img_count[ 'reduced' ] ) ; ?></b>
			</p>
		<?php endif ; ?>

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_SYNC_DATA ) ; ?>" class="litespeed-btn-success">
			<?php echo __( 'Update Reduction Status', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'This will communicate with LiteSpeed\'s Image Optimization Server and retrieve the most recent status.', 'litespeed-cache' ) ; ?>
		</span>

		<?php include_once LSWCP_DIR . "admin/tpl/inc/api_key.php" ; ?>

		<h3 class="litespeed-title"><?php echo __('Image Information', 'litespeed-cache') ; ?>
			<span class="litespeed-desc"><?php echo __('Beta Version', 'litespeed-cache') ; ?></span>
		</h3>

		<p><?php echo __('Image groups total', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_img' ] ; ?></b></p>
		<p><?php echo __('Image groups not yet requested', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_not_requested' ] ; ?></b></p>
		<?php if ( $img_count[ 'total_not_requested' ] ) : ?>
		<?php if ( $img_count[ 'total_server_finished' ] ) : ?>
			<a href="#" class="litespeed-btn-default disabled">
				<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
			</a>
			<span class="litespeed-desc">
				<?php echo __( 'Please pull the optimized images before send new request.', 'litespeed-cache' ) ; ?>
			</span>
			<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
		<?php else : ?>
			<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_OPTIMIZE ) ; ?>" class="litespeed-btn-success">
				<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
			</a>
			<span class="litespeed-desc">
				<?php echo __( 'This will send the optimization request with the images to LiteSpeed\'s Image Optimization Server.', 'litespeed-cache' ) ; ?>
			</span>
		<?php endif ; ?>
		<?php endif ; ?>

		<hr />

		<p><?php echo __('Image groups requested', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_requested' ] ; ?></b></p>
		<p><?php echo __('Image groups failed to optimize', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_err' ] ; ?></b></p>
		<p>
			<?php echo __('Image groups optimized and waiting to be pulled', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_server_finished' ] ; ?></b>
			<?php if ( $img_count[ 'total_server_finished' ] && ! $is_running ) : ?>
				<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_PULL ) ; ?>" class="litespeed-btn-success">
					<?php echo __( 'Pull Images', 'litespeed-cache' ) ; ?>
				</a>
				<span class="litespeed-desc">
					<?php echo __( 'If you have cron job running already, the optimized images will be pulled automatically.', 'litespeed-cache' ) ; ?>
				</span>
			<?php elseif ( $last_run ) : ?>
				<span class="litespeed-desc">
					<?php echo sprintf( __( 'Last cron running time is %s.', 'litespeed-cache' ), '<code>' . LiteSpeed_Cache_Utility::readable_time( $last_run ) . '</code>' ) ; ?>
				</span>
			<?php endif ; ?>
		</p>
		<p>
			<?php echo __('Images failed to pull', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_pull_failed' ] ; ?></b>
		</p>
		<p><?php echo __('Image groups optimized and pulled', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_pulled' ] ; ?></b></p>
		<p class="litespeed-desc">
			<?php echo __( 'After LiteSpeed\'s Image Optimization Server finishes optimization, it will notify your site to pull the optimized images.', 'litespeed-cache' ) ; ?>
			<?php echo __( 'All these processes are automatic.', 'litespeed-cache' ) ; ?>
		</p>
		<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a></p>

		<hr />

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


	</div>
</div>
