<?php
if ( ! defined( 'WPINC' ) ) die ;

// Update table data for upgrading
LiteSpeed_Cache_Data::get_instance() ;

$img_optm = LiteSpeed_Cache_Img_Optm::get_instance() ;

$img_count = $img_optm->img_count() ;
$optm_summary = $img_optm->summary_info() ;
list( $storage_data, $rm_log ) = $img_optm->storage_data() ;

list( $last_run, $is_running ) = $img_optm->cron_running( false ) ;

if ( ! empty( $img_count[ 'total_img' ] ) ) {
	$finished_percentage = 100 - floor( $img_count[ 'total_not_requested' ] * 100 / $img_count[ 'total_img' ] ) ;
}
else {
	$finished_percentage = 0 ;
}

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
		<div class="litespeed-flex-container">
			<?php if ( ! $optm_summary ) : ?>
				<?php include_once LSCWP_DIR . "admin/tpl/inc/img_optm.initialize.php" ; ?>
			<?php else : ?>
				<?php include_once LSCWP_DIR . "admin/tpl/inc/img_optm.summary.php" ; ?>
			<?php endif ; ?>
		</div>
	</div>

</div>
