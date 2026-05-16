<?php
/**
 * LiteSpeed Cache OptimaX
 *
 * Manages the OptimaX interface for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 8.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
    'summary'  => esc_html__( 'OptimaX Summary', 'litespeed-cache' ),
    'settings' => esc_html__( 'OptimaX Settings', 'litespeed-cache' ),
);

if ( is_network_admin() ) {
    $menu_list = array(
        'network_settings' => esc_html__( 'OptimaX Settings', 'litespeed-cache' ),
    );
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
        <?php esc_html_e( 'LiteSpeed Cache OptimaX', 'litespeed-cache' ); ?>
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
        <?php
        foreach ( $menu_list as $menu_key => $val ) {
            $is_default = ( $menu_key === $default_tab_key );
            echo '<div data-litespeed-layout="' . esc_attr( $menu_key ) . '" id="' . esc_attr( $menu_key ) . '"' . ( $is_default ? ' data-litespeed-default-tab="1"' : '' ) . '>';
            require LSCWP_DIR . 'tpl/optimax/' . $menu_key . '.tpl.php';
            echo '</div>';
        }
        ?>
    </div>

</div>