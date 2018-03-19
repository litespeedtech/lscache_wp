<?php
if (!defined('WPINC')) die;

$menu_list = array(
	'purge' => __('Purge', 'litespeed-cache'),
	'db' => __('DB Optimizer', 'litespeed-cache'),
) ;

if ( ! is_network_admin() ) {
	$menu_list[ 'cdn' ] = __( 'CDN', 'litespeed-cache' ) ;
}

LiteSpeed_Cache_GUI::show_promo() ;
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php
			if ( is_network_admin() ) {
				echo __('LiteSpeed Cache Network Management', 'litespeed-cache');
			}
			else {
				echo __('LiteSpeed Cache Management', 'litespeed-cache');
			}
		?>
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
			require LSCWP_DIR . "admin/tpl/manage/manage_$tab.php" ;
			echo "</div>" ;
		}

	?>
	</div>
</div>
