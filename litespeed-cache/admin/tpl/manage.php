<?php
if (!defined('WPINC')) die;

LiteSpeed_Cache_Admin_Display::get_instance()->check_license();

$menu_list = array(
	'purge' => __('Purge', 'litespeed-cache'),
	'db' => __('DB Optimizer', 'litespeed-cache'),
) ;

?>

<div class="wrap">
	<h2>
		<?php echo __('LiteSpeed Cache Management', 'litespeed-cache'); ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
		</span>
	</h2>
</div>
<div class="wrap">
	<h2 class="nav-tab-wrapper">
	<?php
		foreach ($menu_list as $tab => $val){
			echo "<a class='nav-tab litespeed-tab' href='#$tab' data-litespeed-tab='$tab'>$val</a>" ;
		}
	?>
	</h2>
	<div class="litespeed-cache-welcome-panel">

	<?php

	// include all tpl for faster UE
	foreach ($menu_list as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>" ;
		require LSWCP_DIR . "admin/tpl/manage_$tab.php" ;
		echo "</div>" ;
	}

	?>
	</div>
</div>
