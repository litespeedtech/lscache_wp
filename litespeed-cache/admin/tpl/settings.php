<?php
if (!defined('WPINC')) die;

if ($error_msg = LiteSpeed_Cache_Admin_Display::get_instance()->check_license() !== true) {
	echo '<div class="error"><p>' . $error_msg . '</p></div>' . "\n";
}

$menuArr = array(
	'general' => __('General', 'litespeed-cache'),
	'specific' => __('Specific Pages', 'litespeed-cache'),
	'purge' => __('Purge Rules', 'litespeed-cache'),
	'excludes' => __('Do Not Cache Rules', 'litespeed-cache'),
);

if (!is_multisite()) {
	$menuArr['advanced'] = __('Advanced Settings', 'litespeed-cache');
}

$menuArr['debug'] = __('Debug', 'litespeed-cache');

if (LiteSpeed_Cache_Admin_Display::get_instance()->show_compatibility_tab()){
	$menuArr['compatibilities'] = __('Plugin Compatibilities', 'litespeed-cache');
}

$menuArr['crawler'] = __('Crawler', 'litespeed-cache');

$_options = LiteSpeed_Cache_Config::get_instance()->get_options();

?>

<div class="wrap">
	<h2>
		<?=__('LiteSpeed Cache Settings', 'litespeed-cache')?>
		<span class="litespeed-desc">
			v<?=LiteSpeed_Cache::PLUGIN_VERSION?>
		</span>
	</h2>
</div>
<div class="wrap">
	<h2 class="nav-tab-wrapper">
	<?php
		foreach ($menuArr as $tab => $val){
			echo "<a class='nav-tab litespeed-tab' href='?page=lscache-settings#$tab' data-litespeed-tab='$tab'>$val</a>";
		}
	?>
	</h2>
	<div class="litespeed-cache-welcome-panel">
		<form method="post" action="options.php" id="litespeed_form_options">
			<input type="hidden" name="<?=LiteSpeed_Cache::ACTION_KEY?>" value="<?=LiteSpeed_Cache::ACTION_SAVE_SETTINGS?>" />

	<?php if (LiteSpeed_Cache_Admin_Display::get_instance()->get_disable_all()): ?>
			<p>
				<?=__('The network admin selected use primary site configs for all subsites.', 'litespeed-cache')?>
				<?=__('The following options are selected, but are not editable in this settings page.', 'litespeed-cache')?>
			</p>
	<?php endif; ?>

	<?php
	settings_fields(LiteSpeed_Cache_Config::OPTION_NAME);

	// include all tpl for faster UE
	foreach ($menuArr as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>";
		require LSWCP_DIR . "admin/tpl/settings_$tab.php";
		echo "</div>";
	}

	echo "<div class='litespeed-top20'></div>";

	if ($this->get_disable_all()) {
		submit_button(__('Save Changes', 'litespeed-cache'), 'primary', 'litespeed-submit', true, array('disabled' => true));
	}
	else {
		submit_button(__('Save Changes', 'litespeed-cache'), 'primary', 'litespeed-submit');
	}

	?>
	</div>
</div>
