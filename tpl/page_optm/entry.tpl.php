<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$menu_list = array(
	'settings_css' 				=> __( 'CSS Settings', 'litespeed-cache' ),
	'settings_js'				=> __( 'JS Settings', 'litespeed-cache' ),
	'settings_html' 			=> __( 'Optimization', 'litespeed-cache' ),
	'settings_media' 			=> __( 'Media Settings', 'litespeed-cache' ),
	'settings_media_exc'		=> __( 'Media Excludes', 'litespeed-cache' ),
	'settings_localization'		=> __( 'Localization', 'litespeed-cache' ),
	'settings_tuning' 			=> __( 'Tuning', 'litespeed-cache' ),
) ;

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __( 'LiteSpeed Cache Page Optimization', 'litespeed-cache' ) ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo Core::VER ; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">

	<?php if ( Optimize::need_db() && ! Data::get_instance()->tb_exist( 'cssjs' ) ) : ?>
	<div class="litespeed-callout notice notice-error inline">
		<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
		<p><?php echo sprintf( __( 'Failed to create Optimizer table. Please follow <a %s>Table Creation guidance from LiteSpeed Wiki</a> to finish setup.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation" target="_blank"' ) ; ?></p>
	</div>
	<?php endif; ?>

	<div class="litespeed-callout notice notice-warning inline">
		<h4><?php echo __( 'NOTICE', 'litespeed-cache' ) ; ?></h4>
		<p><?php echo __( 'Please test thoroughly when enabling any option in this list. After changing Minify/Combine settings, please do a Purge All action.', 'litespeed-cache' ) ; ?></p>
	</div>

	<h2 class="litespeed-header nav-tab-wrapper">
	<?php
		$i = 1 ;
		foreach ($menu_list as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '' ;
			echo "<a class='litespeed-tab nav-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>" ;
			$i ++ ;
		}
	?>
	</h2>

	<div class="litespeed-body">
	<?php
		$this->form_action() ;

		// include all tpl for faster UE
		foreach ($menu_list as $tab => $val) {
			echo "<div data-litespeed-layout='$tab'>" ;
			require LSCWP_DIR . "tpl/page_optm/$tab.tpl.php" ;
			echo "</div>" ;
		}

		$this->form_end() ;

	?>
	</div>

</div>
