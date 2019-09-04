<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;


// $server_ip = get_option( Conf::conf_name( self::DB_SUMMARY, 'data' ), array() ) ;

$this->form_action() ;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php if ( ! is_multisite() ) : ?>
		<?php require LSCWP_DIR . 'tpl/settings/general/settings_inc.auto_upgrade.tpl.php'; ?>
	<?php endif ; ?>

	<tr>
		<th>
			<?php $id = Conf::O_API_KEY ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __( 'To use online services, an API key is necessary to increase security when communicating with cloud servers.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Conf::O_SERVER_IP ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input($id); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enter this site\'s IP address to allow cloud services directly call IP instead of domain name. This eliminates the overhead of DNS and CDN lookups.', 'litespeed-cache' ) ; ?>
				<br /><?php echo __('Your server IP is', 'litespeed-cache'); ?>: <code id='litespeed_server_ip'>-</code> <button type="button" class="button button-link" id="litespeed_get_ip"><?php echo __('Check my public IP from', 'litespeed-cache'); ?> ifconfig.co</button>

				<?php $this->_validate_ip( $id ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php
$this->form_end() ;

