<?php defined( 'WPINC' ) || exit ; ?>
<?php

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

$optm_summary_file = ! $optm_summary ? 'initialize' : 'summary' ;

$menu_list = array(
	$optm_summary_file	=> __( 'Image Optimization Summary', 'litespeed-cache' ),
	'settings'			=> __( 'Image Optimization Settings', 'litespeed-cache' ),
) ;

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __( 'LiteSpeed Cache Image Optimization', 'litespeed-cache' ) ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<h2 class="litespeed-header">
	<?php
		$i = 1 ;
		foreach ($menu_list as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '' ;
			echo "<a class='litespeed-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>" ;
			$i ++ ;
		}
	?>
	</h2>

	<div class="litespeed-body">
	<?php

		// include all tpl for faster UE
		foreach ($menu_list as $tab => $val) {
			echo "<div data-litespeed-layout='$tab'>" ;
			require LSCWP_DIR . "admin/tpl/img_optm/$tab.tpl.php" ;
			echo "</div>" ;
		}

	?>
	</div>

</div>
