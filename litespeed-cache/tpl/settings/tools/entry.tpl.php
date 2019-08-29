<?php defined( 'WPINC' ) || exit ; ?>
<?php

$menu_list = array(
	'purge'				=> __( 'Purge', 'litespeed-cache' ),
	'import_export'		=> __( 'Import / Export', 'litespeed-cache' ),
) ;

if ( ! is_multisite() || $is_network_admin ) {
	$menu_list[ 'edit_htaccess' ] = __( 'Edit .htaccess', 'litespeed-cache' ) ;
}

$menu_list[ 'heartbeat' ] = __( 'Heartbeat', 'litespeed-cache' ) ;

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __( 'LiteSpeed Cache Tools', 'litespeed-cache' ) ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo Core::PLUGIN_VERSION ; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<h2 class="litespeed-header nav-tab-wrapper">
	<?php
		$i = 1 ;
		foreach ($menu_list as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '' ;
			echo "<a class='litespeed-tab nav-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>" ;
			$i ++ ;
		}
	?>
	</h2>

	<div class="litespeed-body">
	<?php

		// include all tpl for faster UE
		foreach ($menu_list as $tab => $val) {
			echo "<div data-litespeed-layout='$tab'>" ;
			require LSCWP_DIR . "tpl/settings/tools/$tab.tpl.php" ;
			echo "</div>" ;
		}

	?>
	</div>

</div>
