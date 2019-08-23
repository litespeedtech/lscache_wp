<?php defined( 'WPINC' ) || exit ; ?>
<?php


// $server_ip = get_option( LiteSpeed_Cache_Const::conf_name( self::DB_SUMMARY, 'data' ), array() ) ;

$this->form_action() ;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php if ( ! is_multisite() ) : ?>
		<?php require LSCWP_DIR . 'admin/tpl/cache/settings_inc.auto_upgrade.tpl.php'; ?>
	<?php endif ; ?>

	<tr>
		<th><?php echo __('Server IP', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::O_SERVER_IP ; ?>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enter this site\'s IP address to allow cloud services directly call IP instead of domain name. This eliminates the overhead of DNS and CDN lookups.', 'litespeed-cache' ) ; ?>
				<br /><?php echo __('Your server IP is', 'litespeed-cache'); ?>: <code id='litespeed_server_ip'>-</code> <button type="button" class="button button-primary button-small" id="litespeed_get_ip"><?php echo __('Check my public IP from', 'litespeed-cache'); ?> ifconfig.co</button>

				<?php $this->_validate_ip( $id ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php
$this->form_end() ;

