<?php
if (!defined('WPINC')) die;

$menuArr = array(
	'general' => __('General', 'litespeed-cache'),
	'cache' => __('Cache', 'litespeed-cache'),
	'purge' => __('Purge', 'litespeed-cache'),
	'excludes' => __('Excludes', 'litespeed-cache'),
	'media' => __('Media', 'litespeed-cache'),
	'advanced' => __('Advanced', 'litespeed-cache'),
);

global $_options;
$_options = LiteSpeed_Cache_Config::get_instance()->get_site_options();

$_hide_in_basic_mode = '' ;

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Network Settings', 'litespeed-cache'); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
	</span>
	<hr class="wp-header-end">

</div>
<div class="litespeed-wrap">
	<h2 class="litespeed-header">
	<?php
		$i = 1 ;
		foreach ($menuArr as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '' ;
			echo "<a class='litespeed-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>";
			$i ++ ;
		}
	?>
	</h2>
	<div class="litespeed-body">
	<form method="post" action="admin.php?page=lscache-settings" id="litespeed_form_options" class="litespeed-relative">
	<?php
		$this->form_action(LiteSpeed_Cache::ACTION_SAVE_SETTINGS_NETWORK);

	// include all tpl for faster UE
	foreach ($menuArr as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>";
		require LSCWP_DIR . "admin/tpl/setting/network_settings_$tab.php" ;
		echo "</div>";
	}

	echo "<div class='litespeed-top20'></div>";

	submit_button();

	?>
	</form>
	</div>
</div>
