<?php
if (!defined('WPINC')) die;

$menuArr = array(
	'faqs' => __('FAQs', 'litespeed-cache'),
	'config' => __('Configuration', 'litespeed-cache'),
	'compatibility' => __('Plugin Compatibilities', 'litespeed-cache'),
	'common_rewrite' => __('Common Rewrite Rules', 'litespeed-cache'),
	'admin_ip' => __('Admin IP Commands', 'litespeed-cache'),
	'crawler' => __('Crawler', 'litespeed-cache'),
);

?>
<div class="wrap">
	<h2>
		<?php echo __('LiteSpeed Cache Information', 'litespeed-cache'); ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
		</span>
	</h2>
</div>
<div class="litespeed-wrap">
	<h2 class="litespeed-header">
	<?php
		foreach ($menuArr as $tab => $val){
			echo "<a class='litespeed-tab' href='?page=lscache-info#$tab' data-litespeed-tab='$tab'>$val</a>";
		}
	?>
	</h2>
	<div class="litespeed-body">

	<?php
	// include all tpl for faster UE
	foreach ($menuArr as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>";
		require LSWCP_DIR . "admin/tpl/info/info_$tab.php";
		echo "</div>";
	}

	?>
	</div>
</div>
