<?php
if ( ! defined( 'WPINC' ) ) die ;

$sapi_key = get_option( LiteSpeed_Cache_Admin_API::DB_IAPI_KEY ) ;
$reduced = get_option( LiteSpeed_Cache_Admin_API::DB_IAPI_IMG_REDUCED ) ;

$media = LiteSpeed_Cache_Media::get_instance() ;
$img_count = $media->img_count() ;

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
		<?php if ( $sapi_key ) : ?>
			<p>
				<?php echo __('Your API key is ', 'litespeed-cache') ; ?>
				<code><?php echo substr( $sapi_key, 0, 5 ) . str_repeat( '*', strlen( $sapi_key ) - 5 ) ; ?></code>
			</p>
		<?php endif ; ?>

		<?php if ( $reduced ) : ?>
			<p>
				<?php echo __('Total Reduction:', 'litespeed-cache') ; ?>
				<b><?php echo LiteSpeed_Cache_Utility::real_size( $reduced ) ; ?></b>
			</p>
		<?php endif ; ?>

		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_SAPI, LiteSpeed_Cache_Admin_API::TYPE_REQUEST_KEY ) ; ?>" class="litespeed-btn-success">
			<?php echo $sapi_key ? __( 'Update Reduction Status', 'litespeed-cache' ) : __( 'Request Key', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php if ( ! $sapi_key ) : ?>
				<?php echo __( 'This will request a key from LiteSpeed\'s Image Optimization Server for later optimization requests.', 'litespeed-cache' ) ; ?>
			<?php else : ?>
				<?php echo __( 'This will communicate with LiteSpeed\'s Image Optimization Server and retrieve the most recent status.', 'litespeed-cache' ) ; ?>
			<?php endif ; ?>
		</span>

		<h3 class="litespeed-title"><?php echo __('Image Information', 'litespeed-cache') ; ?>
			<span class="litespeed-desc"><?php echo __('Beta Version', 'litespeed-cache') ; ?></span>
		</h3>

		<p><?php echo __('Image groups total', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_img' ] ; ?></b></p>
		<p><?php echo __('Image groups not yet requested', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_not_requested' ] ; ?></b></p>
		<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_IMG_OPTIMIZE ) ; ?>" class="litespeed-btn-success">
			<?php echo __( 'Send Optimization Request', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'This will send the optimization request with the images to LiteSpeed\'s Image Optimization Server.', 'litespeed-cache' ) ; ?>
		</span>

		<hr />

		<p><?php echo __('Image groups requested', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_requested' ] ; ?></b></p>
		<p><?php echo __('Image groups optimized and waiting to be pulled', 'litespeed-cache') ; ?>: <b><?php echo $img_count[ 'total_server_finished' ] ; ?></b></p>
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
