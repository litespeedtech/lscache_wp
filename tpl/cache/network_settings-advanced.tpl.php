<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Advanced Settings', 'litespeed-cache' ); ?>
	<?php $this->learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#advanced-tab', false, 'litespeed-learn-more' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

<?php
	require LSCWP_DIR . 'tpl/cache/settings_inc.login_cookie.tpl.php';
?>

</tbody></table>

