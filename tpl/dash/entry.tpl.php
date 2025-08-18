<?php
/**
 * LiteSpeed Cache Dashboard Wrapper
 *
 * Renders the main dashboard page for the LiteSpeed Cache plugin in the WordPress admin area.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'dashboard' => esc_html__( 'Dashboard', 'litespeed-cache' ),
);

if ( $this->_is_network_admin ) {
	$menu_list = array(
		'network_dash' => esc_html__( 'Network Dashboard', 'litespeed-cache' ),
	);
}

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo esc_html__( 'LiteSpeed Cache Dashboard', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		<?php echo esc_html( 'v' . Core::VER ); ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<?php
	foreach ( $menu_list as $tab_key => $tab_val ) {
		echo '<div data-litespeed-layout="' . esc_attr( $tab_key ) . '">';
		require LSCWP_DIR . 'tpl/dash/' . $tab_key . '.tpl.php';
		echo '</div>';
	}
	?>
</div>