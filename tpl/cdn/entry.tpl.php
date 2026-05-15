<?php
/**
 * LiteSpeed Cache CDN Settings
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'qc'    => esc_html__( 'QUIC.cloud', 'litespeed-cache' ),
	'cf'    => esc_html__( 'Cloudflare', 'litespeed-cache' ),
	'other' => esc_html__( 'Other Static CDN', 'litespeed-cache' ),
);

/**
 * Initial tab selection — used to render the right panel server-side and
 * avoid the JS-driven flash from default tab to URL-hash tab on first paint.
 */
$cookie_tab      = isset( $_COOKIE['litespeed_tab'] ) ? sanitize_key( wp_unslash( $_COOKIE['litespeed_tab'] ) ) : '';
$default_tab_key = isset( $menu_list[ $cookie_tab ] ) ? $cookie_tab : array_key_first( $menu_list );
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php esc_html_e( 'LiteSpeed Cache CDN', 'litespeed-cache' ); ?>
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
		foreach ( $menu_list as $menu_key => $menu_value ) {
			$default_attr = ( $menu_key === $default_tab_key ) ? ' data-litespeed-default-tab="1"' : '';
			printf(
				'<div data-litespeed-layout="%1$s" id="%1$s"%2$s>',
				esc_attr( $menu_key ),
				$default_attr
			);
			require LSCWP_DIR . "tpl/cdn/$menu_key.tpl.php";
			echo '</div>';
		}
		?>
	</div>
</div>
