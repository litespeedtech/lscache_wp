<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$api_key_val = Core::config( Conf::O_API_KEY );
if ( ! empty( $_GET[ 'apikey_data' ] ) ) {
	$apikey_data = json_decode( base64_decode( $_GET[ 'apikey_data' ] ), true );
	if ( ! empty( $apikey_data[ 'domain_key' ] ) ) {
		$api_key_val = $apikey_data[ 'domain_key' ];
		! defined( 'LITESPEED_NEW_API_KEY' ) && define( 'LITESPEED_NEW_API_KEY', true );
	}
}

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
			<?php $this->build_input( $id, null, $api_key_val ); ?>
			<?php if ( defined( 'LITESPEED_NEW_API_KEY' ) ) : ?>
				<span class="litespeed-warning"><?php echo sprintf( __( 'Not saved yet! You need to click %s to save this option.', 'litespeed-cache' ), __( 'Save Changes', 'litespeed-cache' ) ); ?></span>
			<?php endif; ?>
			<div class="litespeed-desc">
				<?php echo __( 'To use online services, an API key is necessary to increase security when communicating with cloud servers.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_GEN_KEY ), __( 'Generate Key', 'litespeed-cache' ), 'button button-link' ) ; ?>
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

