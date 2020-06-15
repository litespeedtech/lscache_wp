<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Purge Settings', 'litespeed-cache' ); ?>
	<?php $this->learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#purge-tab', false, 'litespeed-learn-more' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

<?php
	require LSCWP_DIR . 'tpl/cache/settings_inc.purge_on_upgrade.tpl.php';
?>

</tbody></table>

