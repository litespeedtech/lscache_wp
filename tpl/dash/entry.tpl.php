<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'dashboard' => __( 'Dashboard', 'litespeed-cache' ),
);

if ( $this->_is_network_admin ) {
	$menu_list = array(
		'network_dash' => __( 'Network Dashboard', 'litespeed-cache' ),
	);
}


?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __( 'LiteSpeed Cache Dashboard', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo Core::VER; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<?php
	foreach ( $menu_list as $tab => $val ) {
		echo "<div data-litespeed-layout='$tab'>";
		require LSCWP_DIR . "tpl/dash/$tab.tpl.php";
		echo '</div>';
	}
	?>
</div>
