<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<h3 class="litespeed-title"><?php echo __( 'Advanced Network', 'litespeed-cache' ) ; ?></h3>

<table><tbody>

	<?php require LSCWP_DIR . 'admin/tpl/setting/settings_inc.cache_object.php' ; ?>
	<?php require LSCWP_DIR . 'admin/tpl/setting/settings_inc.cache_browser.php' ; ?>
	<?php require LSCWP_DIR . 'admin/tpl/setting/settings_inc.check_adv_file.php' ; ?>
	<?php require LSCWP_DIR . 'admin/tpl/setting/settings_inc.login_cookie.php' ; ?>

</tbody></table>
