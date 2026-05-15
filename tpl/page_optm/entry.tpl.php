<?php
/**
 * LiteSpeed Cache Page Optimization Interface
 *
 * Renders the page optimization settings interface for LiteSpeed Cache with tabbed navigation.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'settings_css'          => esc_html__( 'CSS Settings', 'litespeed-cache' ),
	'settings_js'           => esc_html__( 'JS Settings', 'litespeed-cache' ),
	'settings_html'         => esc_html__( 'HTML Settings', 'litespeed-cache' ),
	'settings_media'        => esc_html__( 'Media Settings', 'litespeed-cache' ),
	'settings_vpi'          => esc_html__( 'VPI', 'litespeed-cache' ),
	'settings_media_exc'    => esc_html__( 'Media Excludes', 'litespeed-cache' ),
	'settings_localization' => esc_html__( 'Localization', 'litespeed-cache' ),
	'settings_tuning'       => esc_html__( 'Tuning', 'litespeed-cache' ),
	'settings_tuning_css'   => esc_html__( 'Tuning', 'litespeed-cache' ) . ' - CSS',
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
		<?php esc_html_e( 'LiteSpeed Cache Page Optimization', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo esc_html( Core::VER ); ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">

	<div class="litespeed-callout notice notice-warning inline">
		<h4><?php esc_html_e( 'NOTICE', 'litespeed-cache' ); ?></h4>
		<p><?php esc_html_e( 'Please test thoroughly when enabling any option in this list. After changing Minify/Combine settings, please do a Purge All action.', 'litespeed-cache' ); ?></p>
	</div>

	<h2 class="litespeed-header nav-tab-wrapper">
		<?php GUI::display_tab_list( $menu_list ); ?>
	</h2>

	<div class="litespeed-body">
	<?php
		$this->form_action();

		// Include all tpl for faster UE
		foreach ( $menu_list as $tab_key => $tab_val ) {
			$is_default = ( $tab_key === $default_tab_key );
			?>
			<div data-litespeed-layout='<?php echo esc_attr( $tab_key ); ?>' id='<?php echo esc_attr( $tab_key ); ?>'<?php echo $is_default ? ' data-litespeed-default-tab="1"' : ''; ?>>
				<?php require LSCWP_DIR . 'tpl/page_optm/' . $tab_key . '.tpl.php'; ?>
			</div>
			<?php
		}

		$this->form_end();
	?>
	</div>

</div>