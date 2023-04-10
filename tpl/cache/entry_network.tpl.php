<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$menuArr = array(
	'cache' 	=> __( 'Cache', 'litespeed-cache' ),
	'purge' 	=> __( 'Purge', 'litespeed-cache' ),
	'excludes' 	=> __( 'Excludes', 'litespeed-cache' ),
	'object' 	=> __( 'Object', 'litespeed-cache' ),
	'browser'	=> __( 'Browser', 'litespeed-cache' ),
	'advanced' 	=> __( 'Advanced', 'litespeed-cache' ),
);

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Network Cache Settings', 'litespeed-cache'); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo Core::VER; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<h2 class="litespeed-header nav-tab-wrapper">
	<?php
		$i = 1;
		foreach ($menuArr as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '';
			echo "<a class='litespeed-tab nav-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>";
			$i ++;
		}
	?>
	</h2>
	<div class="litespeed-body">
		<?php $this->cache_disabled_warning(); ?>

		<?php
		$this->form_action( Router::ACTION_SAVE_SETTINGS_NETWORK );

		// include all tpl for faster UE
		foreach ($menuArr as $tab => $val) {
			echo "<div data-litespeed-layout='$tab'>";
			require LSCWP_DIR . "tpl/cache/network_settings-$tab.tpl.php";
			echo "</div>";
		}

		$this->form_end( true );

		?>
	</div>
</div>
