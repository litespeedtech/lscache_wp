<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$this->form_action( $this->_is_network_admin ? Router::ACTION_SAVE_SETTINGS_NETWORK : false );
?>

<h3 class="litespeed-title-short">
	<?php echo __('Debug Helpers', 'litespeed-cache'); ?>
</h3>

<a href="<?php echo home_url( '/' ) . '?' . Router::ACTION . '=before_optm'; ?>" class="button button-success" target="_blank">
	<?php echo __( 'View Site Before Optimization', 'litespeed-cache' ); ?>
</a>

<a href="<?php echo home_url( '/' ) . '?' . Router::ACTION . '=' . Core::ACTION_QS_NOCACHE; ?>" class="button button-success" target="_blank">
	<?php echo __( 'View Site Before Cache', 'litespeed-cache' ); ?>
</a>


<h3 class="litespeed-title-short">
	<?php echo __('Debug Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#debug-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Base::O_DEBUG_DISABLE_ALL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'This will disable LSCache and all optimization features for debug purpose.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id, array( __( 'OFF', 'litespeed-cache' ), __( 'ON', 'litespeed-cache' ), __( 'Admin IP Only', 'litespeed-cache' ) ) ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Outputs to WordPress debug log.', 'litespeed-cache' ); ?>
				<?php echo __( 'To prevent filling up the disk, this setting should be OFF when everything is working.', 'litespeed-cache' ); ?>
				<?php echo __( 'The Admin IP option will only output log messages on requests from admin IPs.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'The logs will be output to %s.', 'litespeed-cache' ), '<code>wp-content/debug.log</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_IPS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 50 ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Allows listed IPs (one per line) to perform certain actions from their browsers.', 'litespeed-cache' ); ?>
				<?php echo __( 'Your IP', 'litespeed-cache' ); ?>: <code><?php echo Router::get_ip(); ?></code>
				<?php $this->_validate_ip( $id ); ?>
				<br />
				<?php Doc::learn_more(
					'https://docs.litespeedtech.com/lscache/lscwp/admin/#admin-ip-commands',
					__( 'More information about the available commands can be found here.', 'litespeed-cache' )
				); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_LEVEL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id, array( __( 'Basic', 'litespeed-cache' ), __( 'Advanced', 'litespeed-cache' ) ) ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Advanced level will log more details.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_FILESIZE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?> <?php echo __( 'MB', 'litespeed-cache' ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the maximum size of the log file.', 'litespeed-cache' ); ?>
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 3, 3000 ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_COOKIE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Log requested cookie values.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_COLLAPSE_QS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Shorten query strings in the debug log to improve readability.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_INC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Only log listed pages.', 'litespeed-cache' ); ?>
				<?php $this->_uri_usage_example(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent any debug log of listed pages.', 'litespeed-cache' ); ?>
				<?php $this->_uri_usage_example(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_DEBUG_EXC_STRINGS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent writing log entries that include listed strings.', 'litespeed-cache' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php

$this->form_end();

