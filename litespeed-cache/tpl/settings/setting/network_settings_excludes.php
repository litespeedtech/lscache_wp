<?php
if (!defined('WPINC')) die;

?>
<h3 class="litespeed-title"><?php echo __('Network Do Not Cache Rules', 'litespeed-cache'); ?></h3>

<table class="wp-list-table striped litespeed-table"><tbody>

	<!-- User Agent List -->
	<?php require LSCWP_DIR . 'tpl/settings/setting/settings_inc.exclude_useragent.php'; ?>

	<!-- Cookie List -->
	<?php require LSCWP_DIR . 'tpl/settings/setting/settings_inc.exclude_cookies.php'; ?>

</tbody></table>
