<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;
?>

<h3 class="litespeed-title"><?php echo __( 'Advanced Network', 'litespeed-cache' ) ; ?></h3>

<table class="wp-list-table striped litespeed-table"><tbody>

	<?php require LSCWP_DIR . 'tpl/setting/settings_inc.cache_object.php' ; ?>
	<?php require LSCWP_DIR . 'tpl/setting/settings_inc.cache_browser.php' ; ?>
	<?php require LSCWP_DIR . 'tpl/setting/settings_inc.check_adv_file.php' ; ?>
	<?php require LSCWP_DIR . 'tpl/setting/settings_inc.login_cookie.php' ; ?>

</tbody></table>
