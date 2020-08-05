<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Exclude Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#excludes-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

<?php
	// Cookie
	require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_cookies.tpl.php';

	// User Agent
	require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_useragent.tpl.php';
?>

</tbody></table>

