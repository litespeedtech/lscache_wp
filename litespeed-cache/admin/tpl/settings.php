<?php
if (!defined('WPINC')) die ;

$menu_list = array(
	'general' => __( 'General', 'litespeed-cache' ),
	'cache' => __( 'Cache', 'litespeed-cache' ),
	'purge' => __( 'Purge', 'litespeed-cache' ),
	'excludes' => __( 'Excludes', 'litespeed-cache' ),
	'optimize' => __( 'Optimize', 'litespeed-cache' ),
	'tuning' => __( 'Tuning', 'litespeed-cache' ),
	'media' => __( 'Media', 'litespeed-cache' ),
	'esi' => __( 'ESI', 'litespeed-cache' ),
	'advanced' => __( 'Advanced', 'litespeed-cache' ),
) ;

global $_options ;
$_options = LiteSpeed_Cache_Config::get_instance()->get_options() ;


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

/**
 * Switch basic/advanced mode
 * @since  1.8.2
 */
if ( ! empty( $_GET[ 'mode' ] ) ) {
	$adv_mode = $_GET[ 'mode' ] == 'advanced' ? true : false ;
	update_option( LiteSpeed_Cache_Config::conf_name( 'mode', 'setting' ), $adv_mode ) ;
}
else {
	$adv_mode = get_option( LiteSpeed_Cache_Config::conf_name( 'mode', 'setting' ) ) ;
}

$hide_tabs = array() ;
$_hide_in_basic_mode = '' ;

if ( ! $adv_mode ) {
	$hide_tabs = array(
		'optimize',
		'tuning',
		'media',
		'esi',
		'advanced',
	) ;

	$_hide_in_basic_mode = 'class="litespeed-hide"' ;
}

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
		foreach ($menu_list as $tab => $val){
			if ( in_array( $tab, $hide_tabs ) ) {
				continue ;
			}

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
		do_action( 'litespeed_settings_tab', $adv_mode ) ;
	?>
	<?php if ( $adv_mode ) : ?>
		<a href="admin.php?page=lscache-settings&mode=basic" class="litespeed-tab litespeed-advanced-tab-hide litespeed-right"><?php echo __( 'Hide Advanced Options', 'litespeed-cache' ) ; ?></a>
	<?php else : ?>
		<a href="admin.php?page=lscache-settings&mode=advanced" class="litespeed-tab litespeed-advanced-tab-show litespeed-right"><?php echo __( 'Show Advanced Options', 'litespeed-cache' ) ; ?></a>
	<?php endif ; ?>
	</h2>

	<div class="litespeed-body">
	<form method="post" action="admin.php?page=lscache-settings" id="litespeed_form_options" class="litespeed-relative">
		<input type="hidden" name="<?php echo LiteSpeed_Cache::ACTION_KEY ; ?>" value="<?php echo LiteSpeed_Cache::ACTION_SAVE_SETTINGS ; ?>" />

	<?php
	require LSCWP_DIR . "admin/tpl/inc/check_if_network_disable_all.php" ;

	settings_fields(LiteSpeed_Cache_Config::OPTION_NAME) ;

	// include all tpl for faster UE
	foreach ($menu_list as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>" ;
		require LSCWP_DIR . "admin/tpl/setting/settings_$tab.php" ;
		echo "</div>" ;
	}

	do_action( 'litespeed_settings_content' ) ;

	echo "<div class='litespeed-top20'></div>" ;

	submit_button(__('Save Changes', 'litespeed-cache'), 'litespeed-btn-success', 'litespeed-submit') ;

	?>

	<a href="admin.php?page=lscache-import" class="litespeed-btn-danger litespeed-float-resetbtn"><?php echo __( 'Reset All Settings', 'litespeed-cache' ) ; ?></a>

	</form>
	</div>
</div>
