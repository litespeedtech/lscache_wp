<?php
/**
 * LiteSpeed Cache Crawler Settings
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'summary'   => esc_html__( 'Summary', 'litespeed-cache' ),
	'map'       => esc_html__( 'Map', 'litespeed-cache' ),
	'blacklist' => esc_html__( 'Blocklist', 'litespeed-cache' ),
	'settings'  => esc_html__( 'Settings', 'litespeed-cache' ),
);
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php esc_html_e( 'LiteSpeed Cache Crawler', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		<?php echo esc_html( 'v' . Core::VER ); ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<h2 class="litespeed-header nav-tab-wrapper">
		<?php
		$i = 1;
		foreach ( $menu_list as $menu_key => $menu_value ) {
			$accesskey = $i <= 9 ? $i : '';
			printf(
				'<a class="litespeed-tab nav-tab" href="#%1$s" data-litespeed-tab="%1$s" litespeed-accesskey="%2$s">%3$s</a>',
				esc_attr( $menu_key ),
				esc_attr( $accesskey ),
				esc_html( $menu_value )
			);
			++$i;
		}
		?>
	</h2>

	<div class="litespeed-body">
		<?php
		foreach ( $menu_list as $menu_key => $menu_value ) {
			printf(
				'<div data-litespeed-layout="%s">',
				esc_attr( $menu_key )
			);
			require LSCWP_DIR . "tpl/crawler/$menu_key.tpl.php";
			echo '</div>';
		}
		?>
	</div>
</div>

<iframe name="litespeedHiddenIframe" src="" width="0" height="0" frameborder="0"></iframe>
