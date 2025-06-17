<?php
/**
 * LiteSpeed Cache Network Cache Settings
 *
 * Displays the network cache settings page with tabbed navigation for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_arr = array(
	'cache'    => __( 'Cache', 'litespeed-cache' ),
	'purge'    => __( 'Purge', 'litespeed-cache' ),
	'excludes' => __( 'Excludes', 'litespeed-cache' ),
	'object'   => __( 'Object', 'litespeed-cache' ),
	'browser'  => __( 'Browser', 'litespeed-cache' ),
	'advanced' => __( 'Advanced', 'litespeed-cache' ),
);
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php esc_html_e( 'LiteSpeed Cache Network Cache Settings', 'litespeed-cache' ); ?>
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
		foreach ( $menu_arr as $k => $val ) {
			$accesskey = $i <= 9 ? $i : '';
			printf('<a class="litespeed-tab nav-tab" href="#%1$s" data-litespeed-tab="%1$s" litespeed-accesskey="%2$s">%3$s</a>', esc_attr( $k ), esc_attr( $accesskey ), esc_html( $val ));
			++$i;
		}
		?>
	</h2>
	<div class="litespeed-body">
		<?php $this->cache_disabled_warning(); ?>

		<?php
		$this->form_action( Router::ACTION_SAVE_SETTINGS_NETWORK );

		foreach ( $menu_arr as $k => $val ) {
			$k_escaped = esc_attr( $k );
			?>
			<div data-litespeed-layout="<?php echo esc_html( $k_escaped ); ?>">
			<?php
			require LSCWP_DIR . "tpl/cache/network_settings-$k.tpl.php";
			?>
			</div>
			<?php
		}

		$this->form_end( true );
		?>
	</div>
</div>