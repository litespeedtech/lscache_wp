<?php
/**
 * LiteSpeed Cache Toolbox
 *
 * Renders the toolbox interface for LiteSpeed Cache, providing access to various administrative tools and settings.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'purge' => esc_html__( 'Purge', 'litespeed-cache' ),
);

if ( ! $this->_is_network_admin ) {
	$menu_list['import_export'] = esc_html__( 'Import / Export', 'litespeed-cache' );
}

if ( ! $this->_is_multisite || $this->_is_network_admin ) {
	$menu_list['edit_htaccess'] = esc_html__( 'View .htaccess', 'litespeed-cache' );
}

if ( ! $this->_is_network_admin ) {
	$menu_list['heartbeat'] = esc_html__( 'Heartbeat', 'litespeed-cache' );
	$menu_list['report']    = esc_html__( 'Report', 'litespeed-cache' );
}

if ( ! $this->_is_multisite || $this->_is_network_admin ) {
	$menu_list['settings-debug'] = esc_html__( 'Debug Settings', 'litespeed-cache' );
	$menu_list['log_viewer']     = esc_html__( 'Log View', 'litespeed-cache' );
	$menu_list['beta_test']      = esc_html__( 'Beta Test', 'litespeed-cache' );
}
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php esc_html_e( 'LiteSpeed Cache Toolbox', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo esc_html( Core::VER ); ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<h2 class="litespeed-header nav-tab-wrapper">
		<?php GUI::display_tab_list( $menu_list ); ?>
	</h2>

	<div class="litespeed-body">
		<?php foreach ( $menu_list as $curr_tab => $val ) : ?>
			<div data-litespeed-layout="<?php echo esc_attr( $curr_tab ); ?>">
				<?php require LSCWP_DIR . "tpl/toolbox/$curr_tab.tpl.php"; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>