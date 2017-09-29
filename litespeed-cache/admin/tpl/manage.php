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
		<?php
			if ( is_network_admin() ) {
				echo __('LiteSpeed Cache Network Management', 'litespeed-cache');
			}
			else {
				echo __('LiteSpeed Cache Management', 'litespeed-cache');
			}
		?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
		</span>
	</h2>
</div>
<div class="litespeed-wrap">
	<h2 class="litespeed-header">
	<?php
		$i = 1 ;
		foreach ($menu_list as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '' ;
			echo "<a class='litespeed-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>" ;
			$i ++ ;
		}
	?>
	</h2>

	<?php if ( ! defined( 'LITESPEED_ON' ) ) : ?>
		<div class="litespeed-callout-warning">
			<h4><?php echo __('WARNING: LiteSpeed cache is disabled. The functionalities here can not work.', 'litespeed-cache'); ?></h4>
		</div>
	<?php endif ; ?>

	<div class="litespeed-body">
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
