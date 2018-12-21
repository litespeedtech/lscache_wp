<?php
if (!defined('WPINC')) die ;

$menu_list = array(
	'general' => __('General', 'litespeed-cache'),
	'cache' => __('Cache', 'litespeed-cache'),
	'purge' => __('Purge', 'litespeed-cache'),
	'excludes' => __('Excludes', 'litespeed-cache'),
	'optimize' => __('Optimize', 'litespeed-cache'),
	'tuning' => __('Tuning', 'litespeed-cache'),
	'media' => __('Media', 'litespeed-cache'),
	'cdn' => __('CDN', 'litespeed-cache'),
	'esi' => __('ESI', 'litespeed-cache'),
	'advanced' => __('Advanced', 'litespeed-cache'),
	'debug' => __('Debug', 'litespeed-cache'),
) ;

if ($this->show_compatibility_tab()){
	$menu_list['compatibilities'] = __('Compatibilities', 'litespeed-cache') ;
}

$menu_list['crawler'] = __('Crawler', 'litespeed-cache') ;

global $_options ;
$_options = LiteSpeed_Cache_Config::get_instance()->get_options() ;


/**
 * This hook allows third party plugins to create litespeed cache
 * specific configuration.
 *
 * Each config should append an array containing the following:
 * 'title' (required) - The tab's title.
 * 'slug' (required) - The slug used for the tab. [a-z][A-Z], [0-9], -, _ permitted.
 * 'content' (required) - The tab's content.
 *
 * Upon saving, only the options with the option group in the input's
 * name will be retrieved.
 * For example, name="litespeed-cache-conf[my-opt]".
 *
 * @see TODO: add option save filter.
 * @since 1.0.9
 * @param array $tabs An array of third party configuration.
 * @param array $options The current configuration options.
 * @param string $option_group The option group to use for options.
 * @param boolean $disableall Whether to disable the settings or not.
 * @return mixed An array of third party configs else false on failure.
 */
$tp_tabs = apply_filters('litespeed_cache_add_config_tab',
	array(),
	$_options,
	LiteSpeed_Cache_Config::OPTION_NAME,
	$this->get_disable_all()
) ;
if ( !empty($tp_tabs) && is_array($tp_tabs) ) {
	foreach ($tp_tabs as $key => $tab) {
		if ( !is_array($tab) || !isset($tab['title']) || !isset($tab['slug']) || !isset($tab['content']) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'WARNING: Third party tab input invalid' ) ;
			unset($tp_tabs[$key]) ;
			continue ;
		}
		if ( preg_match('/[^-\w]/', $tab['slug']) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'WARNING: Third party config slug contains invalid characters' ) ;
			unset($tp_tabs[$key]) ;
			continue ;
		}
	}
}
else {
	$tp_tabs = array() ;
}

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
	update_option( LiteSpeed_Cache_Config::ITEM_SETTING_MODE, $adv_mode ) ;
}
else {
	$adv_mode = get_option( LiteSpeed_Cache_Config::ITEM_SETTING_MODE ) ;
}

$hide_tabs = array() ;
$_hide_in_basic_mode = '' ;

if ( ! $adv_mode ) {
	$hide_tabs = array(
		'optimize',
		'tuning',
		'media',
		'cdn',
		'esi',
		'advanced',
		'debug',
		'crawler',
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
		foreach ($tp_tabs as $val){
			$accesskey = '' ;
			if ( $i <= 9 ) {
				$accesskey = "litespeed-accesskey='$i'" ;
			}
			else {
				$tmp = strtoupper( substr( $val[ 'slug' ], 0, 1 ) ) ;
				if ( ! in_array( $tmp, $accesskey_set ) ) {
					$accesskey_set[] = $tmp ;
					$accesskey = "litespeed-accesskey='$tmp'" ;
				}
			}

			echo "<a class='litespeed-tab' href='#$val[slug]' data-litespeed-tab='$val[slug]' $accesskey>$val[title]</a>" ;
			$i ++ ;
		}
	?>
	<?php if ( $adv_mode ) : ?>
		<a href="admin.php?page=lscache-settings&mode=basic" class="litespeed-tab litespeed-advanced-tab-hide litespeed-right"><?php echo __( 'Hide Advanced Options', 'litespeed-cache' ) ; ?></a>
	<?php else : ?>
		<a href="admin.php?page=lscache-settings&mode=advanced" class="litespeed-tab litespeed-advanced-tab-show litespeed-right"><?php echo __( 'Show Advanced Options', 'litespeed-cache' ) ; ?></a>
	<?php endif ; ?>
	</h2>
	<div class="litespeed-body">
	<form method="post" action="options.php" id="litespeed_form_options" class="litespeed-relative">
		<!--input type="hidden" name="<?php echo LiteSpeed_Cache::ACTION_KEY ; ?>" value="<?php echo LiteSpeed_Cache::ACTION_SAVE_SETTINGS ; ?>" /-->

	<?php if ($this->get_disable_all()): ?>
		<div class="litespeed-callout-danger">
			<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
			<p>
				<?php echo __('The network admin selected use primary site configs for all subsites.', 'litespeed-cache') ; ?>
				<?php echo __('The following options are selected, but are not editable in this settings page.', 'litespeed-cache') ; ?>
			</p>
		</div>
	<?php endif ; ?>

	<?php
	settings_fields(LiteSpeed_Cache_Config::OPTION_NAME) ;

	// include all tpl for faster UE
	foreach ($menu_list as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>" ;
		require LSCWP_DIR . "admin/tpl/setting/settings_$tab.php" ;
		echo "</div>" ;
	}

	foreach ($tp_tabs as $val) {
		echo "<div data-litespeed-layout='$val[slug]'>$val[content]</div>" ;
	}

	echo "<div class='litespeed-top20'></div>" ;

	if ( $this->get_disable_all() ) {
		submit_button(__('Save Changes', 'litespeed-cache'), 'litespeed-btn-success', 'litespeed-submit', true, array('disabled' => true)) ;
	}
	else {
		submit_button(__('Save Changes', 'litespeed-cache'), 'litespeed-btn-success', 'litespeed-submit') ;
	}

	?>

	<a href="admin.php?page=lscache-import" class="litespeed-btn-danger litespeed-float-resetbtn"><?php echo __( 'Reset All Settings', 'litespeed-cache' ) ; ?></a>

	</form>
	</div>
</div>
