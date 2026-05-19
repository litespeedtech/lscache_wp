<?php
/**
 * LiteSpeed Cache Configuration Presets
 *
 * Renders the configuration presets interface for LiteSpeed Cache, including standard presets and import/export functionality.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'standard'      => esc_html__( 'Standard Presets', 'litespeed-cache' ),
	'import_export' => esc_html__( 'Import / Export', 'litespeed-cache' ),
);

// Pick the active tab server-side so the right panel paints before JS runs.
$cookie_tab      = isset( $_COOKIE['litespeed_tab'] ) ? sanitize_key( wp_unslash( $_COOKIE['litespeed_tab'] ) ) : '';
$default_tab_key = isset( $menu_list[ $cookie_tab ] ) ? $cookie_tab : array_key_first( $menu_list );
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php esc_html_e( 'LiteSpeed Cache Configuration Presets', 'litespeed-cache' ); ?>
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
		foreach ( $menu_list as $curr_tab => $val ) :
			$is_default = ( $curr_tab === $default_tab_key );
			?>
			<div data-litespeed-layout="<?php echo esc_attr( $curr_tab ); ?>" id="<?php echo esc_attr( $curr_tab ); ?>"<?php echo $is_default ? ' data-litespeed-default-tab="1"' : ''; ?>>
				<?php
				if ( 'import_export' === $curr_tab ) {
					require LSCWP_DIR . "tpl/toolbox/$curr_tab.tpl.php";
				} else {
					require LSCWP_DIR . "tpl/presets/$curr_tab.tpl.php";
				}
				?>
			</div>
			<?php
		endforeach;
		?>
	</div>
</div>