<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$menu_list = array(
	'auto_setup'	=> __( 'Auto QUIC.cloud CDN Setup', 'litespeed-cache' ),
);

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __( 'Auto QUIC.cloud CDN Setup', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo Core::VER; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<?php
	foreach ($menu_list as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>";
		require LSCWP_DIR . "tpl/auto_cdn_setup/$tab.tpl.php";
		echo "</div>";
	}
	?>
</div>
