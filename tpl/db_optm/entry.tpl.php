<?php
/**
 * LiteSpeed Cache Database Optimization
 *
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
    'manage'   => esc_html__( 'Manage', 'litespeed-cache' ),
);

if ( ! is_network_admin() ) {
    $menu_list['settings'] = esc_html__( 'DB Optimization Settings', 'litespeed-cache' );
}

?>

<div class="wrap">
    <h1 class="litespeed-h1">
        <?php esc_html_e( 'LiteSpeed Cache Database Optimization', 'litespeed-cache' ); ?>
    </h1>
    <span class="litespeed-desc">
        <?php echo esc_html( 'v' . Core::VER ); ?>
    </span>
    <hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
    <h2 class="litespeed-header nav-tab-wrapper">
        <?php GUI::display_tab_list( $menu_list ); ?>
    </h2>

    <div class="litespeed-body">
    <?php
        foreach ( $menu_list as $tab_key => $tab_val ) {
			echo '<div data-litespeed-layout="' . esc_attr( $tab_key ) . '">';
			require LSCWP_DIR . 'tpl/db_optm/' . $tab_key . '.tpl.php';
			echo '</div>';
        }
    ?>
    </div>

</div>