<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$menu_list = array(
	'settings'	=> __( 'General Settings', 'litespeed-cache' ),
);

if ( $this->_is_network_admin ) {
	$menu_list = array(
		'network_settings' => __( 'General Settings', 'litespeed-cache' ),
	);
}

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __( 'LiteSpeed Cache General Settings', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo Core::VER; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<h2 class="litespeed-header nav-tab-wrapper">
	<?php
		$i = 1;
		foreach ($menu_list as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '';
			echo "<a class='litespeed-tab nav-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>";
			$i ++;
		}
	?>
	</h2>

	<div class="litespeed-body">
	<?php
		// include all tpl for faster UE
		foreach ($menu_list as $tab => $val) {
			echo "<div data-litespeed-layout='$tab'>";
			require LSCWP_DIR . "tpl/general/$tab.tpl.php";
			echo "</div>";
		}
	?>
	</div>

</div>
