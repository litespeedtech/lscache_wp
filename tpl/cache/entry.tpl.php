<?php
/**
 * LiteSpeed Cache Settings
 *
 * Displays the cache settings page with tabbed navigation for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$menu_list = array(
	'cache'    => __( 'Cache', 'litespeed-cache' ),
	'ttl'      => __( 'TTL', 'litespeed-cache' ),
	'purge'    => __( 'Purge', 'litespeed-cache' ),
	'excludes' => __( 'Excludes', 'litespeed-cache' ),
	'esi'      => __( 'ESI', 'litespeed-cache' ),
);

if ( ! $this->_is_multisite ) {
	$menu_list['object']  = __( 'Object', 'litespeed-cache' );
	$menu_list['browser'] = __( 'Browser', 'litespeed-cache' );
}

$menu_list['advanced'] = __( 'Advanced', 'litespeed-cache' );

/**
 * Generate roles for setting usage
 *
 * @since 1.6.2
 */
global $wp_roles;
$wp_orig_roles = $wp_roles;
if ( ! isset( $wp_roles ) ) {
	$wp_orig_roles = new \WP_Roles();
}

$roles = array();
foreach ( $wp_orig_roles->roles as $k => $v ) {
	$roles[ $k ] = $v['name'];
}
ksort( $roles );
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php esc_html_e( 'LiteSpeed Cache Settings', 'litespeed-cache' ); ?>
	</h1>
	<span class="litespeed-desc">
		<?php echo esc_html( 'v' . Core::VER ); ?>
	</span>
	<hr class="wp-header-end">
</div>
<div class="litespeed-wrap">
	<h2 class="litespeed-header nav-tab-wrapper">
		<?php
		$i             = 1;
		$accesskey_set = array();
		foreach ( $menu_list as $k => $val ) {
			$accesskey = '';
			if ( $i <= 9 ) {
				$accesskey = $i;
			} else {
				$tmp = strtoupper( substr( $k, 0, 1 ) );
				if ( ! in_array( $tmp, $accesskey_set, true ) ) {
					$accesskey_set[] = $tmp;
					$accesskey       = esc_attr( $tmp );
				}
			}
			printf('<a class="litespeed-tab nav-tab" href="#%1$s" data-litespeed-tab="%1$s" litespeed-accesskey="%2$s">%3$s</a>', esc_attr( $k ), esc_attr($accesskey), esc_html( $val ));
			++$i;
		}
		do_action( 'litespeed_settings_tab', 'cache' );
		?>
	</h2>

	<div class="litespeed-body">
		<?php $this->cache_disabled_warning(); ?>

		<?php
		$this->form_action();

		require LSCWP_DIR . 'tpl/inc/check_if_network_disable_all.php';
		require LSCWP_DIR . 'tpl/cache/more_settings_tip.tpl.php';

		foreach ( $menu_list as $k => $val ) {
			echo '<div data-litespeed-layout="' . esc_attr( $k ) . '">';
			require LSCWP_DIR . "tpl/cache/settings-$k.tpl.php";
			echo '</div>';
		}

		do_action( 'litespeed_settings_content', 'cache' );

		$this->form_end();
		?>
	</div>
</div>