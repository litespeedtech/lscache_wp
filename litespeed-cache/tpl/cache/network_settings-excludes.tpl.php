<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Exclude Settings', 'litespeed-cache' ); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:excludes', false, 'litespeed-learn-more' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

<?php
	// Cookie
	require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_cookies.tpl.php';

	// User Agent
	require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_useragent.tpl.php';
?>

</tbody></table>

