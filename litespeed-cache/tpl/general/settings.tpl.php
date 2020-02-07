<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$api_key_val = Conf::val( Base::O_API_KEY );
if ( ! empty( $_GET[ 'apikey_data' ] ) ) {
	$apikey_data = json_decode( base64_decode( $_GET[ 'apikey_data' ] ), true );
	if ( ! empty( $apikey_data[ 'domain_key' ] ) && $api_key_val != $apikey_data[ 'domain_key' ] ) {
		$api_key_val = $apikey_data[ 'domain_key' ];
		! defined( 'LITESPEED_NEW_API_KEY' ) && define( 'LITESPEED_NEW_API_KEY', true );
	}
	unset( $_GET[ 'apikey_data' ] );
	?>
	<script>window.history.pushState( 'remove_gen_link', document.title, window.location.href.replace( '&apikey_data=', '&' ) );</script>
	<?php
}

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General Settings', 'litespeed-cache' ); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general', false, 'litespeed-learn-more' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php if ( ! $this->_is_multisite ) : ?>
		<?php require LSCWP_DIR . 'tpl/general/settings_inc.auto_upgrade.tpl.php'; ?>
	<?php endif; ?>

	<tr>
		<th>
			<?php $id = Base::O_API_KEY; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, null, defined( 'LITESPEED_NEW_API_KEY' ) ? $api_key_val : null ); ?>
			<?php if ( defined( 'LITESPEED_NEW_API_KEY' ) ) : ?>
				<span class="litespeed-danger"><?php echo sprintf( __( 'Not saved yet! You need to click %s to save this option.', 'litespeed-cache' ), __( 'Save Changes', 'litespeed-cache' ) ); ?></span>
			<?php endif; ?>
			<div class="litespeed-desc">
				<?php echo __( 'An API key is necessary for security when communicating with our QUIC.cloud servers. Required for online services.', 'litespeed-cache' ); ?>
				<?php $this->learn_more( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_GEN_KEY ), __( 'Generate Key', 'litespeed-cache' ), '', true ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_NEWS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Turn this option ON to show latest news automatically, including hotfixes, new releases, available beta versions, and promotions.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php
$this->form_end();

