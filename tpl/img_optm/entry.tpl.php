<?php
/**
 * LiteSpeed Cache Image Optimization
 *
 * Manages the image optimization interface for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
    'summary'  => esc_html__( 'Image Optimization Summary', 'litespeed-cache' ),
    'settings' => esc_html__( 'Image Optimization Settings', 'litespeed-cache' ),
);

if ( is_network_admin() ) {
    $menu_list = array(
        'network_settings' => esc_html__( 'Image Optimization Settings', 'litespeed-cache' ),
    );
}

?>

<div class="wrap">
    <h1 class="litespeed-h1">
        <?php esc_html_e( 'LiteSpeed Cache Image Optimization', 'litespeed-cache' ); ?>
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
            echo '<div data-litespeed-layout="' . esc_attr( $menu_key ) . '">';
            require LSCWP_DIR . 'tpl/img_optm/' . sanitize_file_name( $menu_key ) . '.tpl.php';
            echo '</div>';
        }
        ?>
    </div>

</div>