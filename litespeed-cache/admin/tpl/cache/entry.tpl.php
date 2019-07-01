<?php defined( 'WPINC' ) || exit ; ?>

<?php

$menu_list = array(
	'general' 	=> __( 'General', 'litespeed-cache' ),
	'cache' 	=> __( 'Cache', 'litespeed-cache' ),
	'ttl' 		=> __( 'TTL', 'litespeed-cache' ),
	'purge' 	=> __( 'Purge', 'litespeed-cache' ),
	'excludes' 	=> __( 'Excludes', 'litespeed-cache' ),
	'esi' 		=> __( 'ESI', 'litespeed-cache' ),
	'object' 	=> __( 'Object', 'litespeed-cache' ),
	'browser' 	=> __( 'Browser', 'litespeed-cache' ),
	'advanced' 	=> __( 'Advanced', 'litespeed-cache' ),
) ;

/**
 * Generate rules for setting usage
 * @since 1.6.2
 */
global $wp_roles ;
if ( !isset( $wp_roles ) ) {
	$wp_roles = new WP_Roles() ;
}

$roles = array() ;
foreach ( $wp_roles->roles as $k => $v ) {
	$roles[ $k ] = $v[ 'name' ] ;
}
ksort( $roles ) ;

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Settings', 'litespeed-cache') ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
	</span>
	<hr class="wp-header-end">
</div>
<div class="litespeed-wrap">
	<h2 class="litespeed-header">
	<?php
		$i = 1 ;
		$accesskey_set = array() ;
		foreach ( $menu_list as $tab => $val ) {
			$accesskey = '' ;
			if ( $i <= 9 ) {
				$accesskey = "litespeed-accesskey='$i'" ;
			}
			else {
				$tmp = strtoupper( substr( $tab, 0, 1 ) ) ;
				if ( ! in_array( $tmp, $accesskey_set ) ) {
					$accesskey_set[] = $tmp ;
					$accesskey = "litespeed-accesskey='$tmp'" ;
				}
			}

			echo "<a class='litespeed-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>" ;
			$i ++ ;
		}
		do_action( 'litespeed_settings_tab' ) ;
	?>
	</h2>

	<div class="litespeed-body">

	<?php
	$this->form_action() ;

	require LSCWP_DIR . "admin/tpl/inc/check_if_network_disable_all.php" ;

	// include all tpl for faster UE
	foreach ( $menu_list as $tab => $val ) {
		echo "<div data-litespeed-layout='$tab'>" ;
		require LSCWP_DIR . "admin/tpl/cache/settings-$tab.tpl.php" ;
		echo "</div>" ;
	}

	do_action( 'litespeed_settings_content' ) ;

	$this->form_end() ;

	?>
	</div>
</div>
