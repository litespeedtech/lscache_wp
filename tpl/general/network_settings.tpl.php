<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/' ); ?>
</h3>

<?php
$this->form_action( Router::ACTION_SAVE_SETTINGS_NETWORK );
?>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php require LSCWP_DIR . 'tpl/general/settings_inc.auto_upgrade.tpl.php'; ?>

	<tr>
		<th><?php echo __('Use Primary Site Configuration', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch( Base::NETWORK_O_USE_PRIMARY ); ?>
			<div class="litespeed-desc">
				<?php echo __("Check this option to use the primary site's configuration for all subsites.", 'litespeed-cache'); ?>
				<?php echo __('This will disable the settings page on all subsites.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<?php require LSCWP_DIR . 'tpl/general/settings_inc.guest.tpl.php'; ?>

</tbody></table>

<?php
$this->form_end( true );
