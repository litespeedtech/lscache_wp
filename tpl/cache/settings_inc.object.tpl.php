<?php
/**
 * LiteSpeed Cache Object Cache Settings
 *
 * Displays the object cache settings section for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$lang_enabled  = '<span class="litespeed-success">' . esc_html__( 'Enabled', 'litespeed-cache' ) . '</span>';
$lang_disabled = '<span class="litespeed-warning">' . esc_html__( 'Disabled', 'litespeed-cache' ) . '</span>';

$mem_enabled   = class_exists( 'Memcached' ) ? $lang_enabled : $lang_disabled;
$redis_enabled = class_exists( 'Redis' ) ? $lang_enabled : $lang_disabled;

$mem_conn = $this->cls( 'Object_Cache' )->test_connection();
if ( null === $mem_conn ) {
	$mem_conn_desc = '<span class="litespeed-desc">' . esc_html__( 'Not Available', 'litespeed-cache' ) . '</span>';
} elseif ( $mem_conn ) {
	$mem_conn_desc = '<span class="litespeed-success">' . esc_html__( 'Passed', 'litespeed-cache' ) . '</span>';
} else {
	$severity      = $this->conf( Base::O_OBJECT, true ) ? 'danger' : 'warning';
	$mem_conn_desc = '<span class="litespeed-' . esc_attr( $severity ) . '">' . esc_html__( 'Failed', 'litespeed-cache' ) . '</span>';
}
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Object Cache Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#object-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Use external object cache functionality.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/admin/#memcached-lsmcd-and-redis-object-cache-support-in-lscwp' ); ?>
				</div>
				<div class="litespeed-block">
					<div class="litespeed-col-auto">
						<h4><?php esc_html_e( 'Status', 'litespeed-cache' ); ?></h4>
					</div>
					<div class="litespeed-col-auto">
						<?php
						printf(
							/* translators: %s: Object cache name */
							esc_html__( '%s Extension', 'litespeed-cache' ),
							'Memcached'
						);
						?>
						: <?php echo wp_kses_post( $mem_enabled ); ?><br>
						<?php
						printf(
							/* translators: %s: Object cache name */
							esc_html__( '%s Extension', 'litespeed-cache' ),
							'Redis'
						);
						?>
						: <?php echo wp_kses_post( $redis_enabled ); ?><br>
						<?php esc_html_e( 'Connection Test', 'litespeed-cache' ); ?>: <?php echo wp_kses_post( $mem_conn_desc ); ?>
						<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/admin/#how-to-debug' ); ?>
					</div>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_KIND; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id, array( 'Memcached', 'Redis' ) ); ?>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_HOST; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id ); ?>
				<div class="litespeed-desc">
					<?php
					printf(
						/* translators: %s: Object cache name */
						esc_html__( 'Your %s Hostname or IP address.', 'litespeed-cache' ),
						'Memcached/<a href="https://docs.litespeedtech.com/products/lsmcd/" target="_blank" rel="noopener">LSMCD</a>/Redis'
					);
					?>
					<br>
					<?php
					printf(
						/* translators: %1$s: Socket name, %2$s: Host field title, %3$s: Example socket path */
						esc_html__( 'If you are using a %1$s socket, %2$s should be set to %3$s', 'litespeed-cache' ),
						'UNIX',
						esc_html( Lang::title( $option_id ) ),
						'<code>/path/to/memcached.sock</code>'
					);
					?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_PORT; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short2' ); ?>
				<div class="litespeed-desc">
					<?php
					printf(
						/* translators: %1$s: Object cache name, %2$s: Port number */
						esc_html__( 'Default port for %1$s is %2$s.', 'litespeed-cache' ),
						'Memcached',
						'<code>11211</code>'
					);
					?>
					<br>
					<?php
					printf(
						/* translators: %1$s: Object cache name, %2$s: Port number */
						esc_html__( 'Default port for %1$s is %2$s.', 'litespeed-cache' ),
						'Redis',
						'<code>6379</code>'
					);
					?>
					<br>
					<?php
					printf(
						/* translators: %1$s: Socket name, %2$s: Port field title, %3$s: Port value */
						esc_html__( 'If you are using a %1$s socket, %2$s should be set to %3$s', 'litespeed-cache' ),
						'UNIX',
						esc_html( Lang::title( $option_id ) ),
						'<code>0</code>'
					);
					?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_LIFE; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short2' ); ?> <?php esc_html_e( 'seconds', 'litespeed-cache' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Default TTL for cached objects.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_USER; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id ); ?>
				<div class="litespeed-desc">
					<?php
					printf(
						/* translators: %s: SASL */
						esc_html__( 'Only available when %s is installed.', 'litespeed-cache' ),
						'SASL'
					);
					?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_PSWD; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify the password used when connecting.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_DB_ID; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Database to be used', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_GLOBAL_GROUPS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id, 30 ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Groups cached at the network level.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_NON_PERSISTENT_GROUPS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id, 30 ); ?>
				<div class="litespeed-desc">
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_PERSISTENT; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Use keep-alive connections to speed up cache operations.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_ADMIN; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Improve wp-admin speed through caching. (May encounter expired data)', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_OBJECT_TRANSIENTS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php
					printf(
						/* translators: %1$s: Object Cache Admin title, %2$s: OFF status */
						esc_html__( 'Save transients in database when %1$s is %2$s.', 'litespeed-cache' ),
						'<code>' . esc_html( Lang::title( Base::O_OBJECT_ADMIN ) ) . '</code>',
						'<code>' . esc_html__( 'OFF', 'litespeed-cache' ) . '</code>'
					);
					?>
					<br>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#store-transients' ); ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<script>
jQuery(document).ready(function($) {
	// Auto-fill port based on object cache type
	$('input[name="object-kind"]').on('change', function() {
		var portInput = $('#input_objectport');
		var selectedKind = $(this).val();

		// Memcached (0) -> 11211, Redis (1) -> 6379
		if (selectedKind === '0') {
			portInput.val('11211');
		} else if (selectedKind === '1') {
			portInput.val('6379');
		}
	});
});
</script>
