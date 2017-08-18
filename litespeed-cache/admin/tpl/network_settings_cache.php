<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<h3 class="litespeed-title"><?php echo __( 'Cache Control Network Settings', 'litespeed-cache' ) ; ?></h3>

<p>
	<?php echo __( 'Separate Mobile Views should be enabled if any of the network enabled themes require a different view for mobile devices.', 'litespeed-cache' ) ; ?>
	<?php echo __( 'Responsive themes can handle this part automatically.', 'litespeed-cache' ) ; ?>
</p>

<table class="form-table"><tbody>

	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_favicon.php' ; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_resources.php' ; ?>
	<?php require LSWCP_DIR . 'admin/tpl/settings_inc.cache_mobile.php' ; ?>

</tbody></table>

