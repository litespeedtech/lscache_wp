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

/**
 * Initial tab selection — used to render the right panel server-side and
 * avoid the JS-driven flash from default tab to URL-hash tab on first paint.
 */
$cookie_tab      = isset( $_COOKIE['litespeed_tab'] ) ? sanitize_key( wp_unslash( $_COOKIE['litespeed_tab'] ) ) : '';
$default_tab_key = isset( $menu_list[ $cookie_tab ] ) ? $cookie_tab : array_key_first( $menu_list );

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
			$is_default = ( $tab_key === $default_tab_key );
			echo '<div data-litespeed-layout="' . esc_attr( $tab_key ) . '" id="' . esc_attr( $tab_key ) . '"' . ( $is_default ? ' data-litespeed-default-tab="1"' : '' ) . '>';
			require LSCWP_DIR . 'tpl/db_optm/' . $tab_key . '.tpl.php';
			echo '</div>';
        }
    ?>
    </div>

</div>