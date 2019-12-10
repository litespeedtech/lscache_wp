<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General Settings', 'litespeed-cache' ); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general', false, 'litespeed-learn-more' ); ?>
</h3>

<?php
$this->form_action( Router::ACTION_SAVE_SETTINGS_NETWORK );
?>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php require LSCWP_DIR . 'tpl/general/settings_inc.auto_upgrade.tpl.php'; ?>

</tbody></table>

<?php
$this->form_end( true );
