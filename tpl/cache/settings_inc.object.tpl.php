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

$mem_enabled    = class_exists( 'Memcached' ) ? $lang_enabled : $lang_disabled;
$redis_enabled  = class_exists( 'Redis' ) ? $lang_enabled : $lang_disabled;
$socket_enabled = $this->cls( 'Object_Cache' )->has_socket() ? $lang_enabled : $lang_disabled;

$conn_result = $this->cls( 'Object_Cache' )->test_connection();
$conn_ok     = isset( $conn_result['ok'] ) ? $conn_result['ok'] : null;
$conn_source = isset( $conn_result['source'] ) ? $conn_result['source'] : 'unavailable';
$conn_detail = isset( $conn_result['detail'] ) ? $conn_result['detail'] : '';

// Benchmark is opt-in via a query param because the full sweep can issue
// many connect()s with multi-second timeouts per candidate. Three states:
//   - no cache, no action          → "Run Benchmark" CTA only, table hidden
//   - cache present, no action     → "Show benchmarks" + "Re-run" CTAs, table hidden
//   - action=show + cache present  → table visible from cache, "Re-run" CTA
//   - action=run                   → fresh run, table visible, "Re-run" CTA
$oc_cls      = $this->cls( 'Object_Cache' );
$bench_cache = $oc_cls->get_benchmark_cache();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI toggle; benchmark itself is gated by the settings-page capability check upstream.
$bench_action = isset( $_GET['ls_oc_benchmark'] ) ? sanitize_key( wp_unslash( $_GET['ls_oc_benchmark'] ) ) : '';
$bench        = null;

if ( 'run' === $bench_action ) {
	$bench       = $oc_cls->benchmark_candidates();
	$bench_cache = $bench;
} elseif ( 'show' === $bench_action && null !== $bench_cache ) {
	$bench = $bench_cache;
}

$bench_run_url  = esc_url( add_query_arg( 'ls_oc_benchmark', 'run' ) . '#cache-object' );
$bench_show_url = esc_url( add_query_arg( 'ls_oc_benchmark', 'show' ) . '#cache-object' );

// Badge severity is driven by whether the *configured* host actually answered.
// 'detected' means the configured value failed (or was empty) and we fell back
// to scanning candidates — that is still a Failed state for the user's saved
// config, even though the detail line below will surface the working backend
// as a hint to apply via Method = Auto.
if ( null === $conn_ok ) {
	$mem_conn_desc = '<span class="litespeed-desc">' . esc_html__( 'Not Available', 'litespeed-cache' ) . '</span>';
} elseif ( $conn_ok && 'configured' === $conn_source ) {
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
						<?php esc_html_e( 'UNIX Socket', 'litespeed-cache' ); ?>: <?php echo wp_kses_post( $socket_enabled ); ?><br>
						<?php
						printf(
							/* translators: %s: Object cache name */
							esc_html__( '%s Extension', 'litespeed-cache' ),
							'Redis/Valkey'
						);
						?>
						: <?php echo wp_kses_post( $redis_enabled ); ?><br>
						<?php
						printf(
							/* translators: %s: Object cache name */
							esc_html__( '%s Extension', 'litespeed-cache' ),
							'Memcached'
						);
						?>
						: <?php echo wp_kses_post( $mem_enabled ); ?><br>
						<?php esc_html_e( 'Connection Test', 'litespeed-cache' ); ?>: <?php echo wp_kses_post( $mem_conn_desc ); ?>
						<?php if ( $conn_detail ) : ?>
							<div class="litespeed-desc"><?php echo esc_html( $conn_detail ); ?></div>
						<?php endif; ?>

						<div class="litespeed-desc" style="margin-top:0.75em;">
							<?php if ( null !== $bench ) : ?>
								<a href="<?php echo esc_url( $bench_run_url ); ?>"><?php esc_html_e( 'Re-run Benchmarks', 'litespeed-cache' ); ?></a>
							<?php elseif ( null !== $bench_cache ) : ?>
								<a href="<?php echo esc_url( $bench_show_url ); ?>"><?php esc_html_e( 'Show benchmarks', 'litespeed-cache' ); ?></a>
								&nbsp;|&nbsp;
								<a href="<?php echo esc_url( $bench_run_url ); ?>"><?php esc_html_e( 'Re-run Benchmarks', 'litespeed-cache' ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( $bench_run_url ); ?>"><?php esc_html_e( 'Run Benchmark', 'litespeed-cache' ); ?></a>
								&mdash; <?php esc_html_e( 'walk every host/port/socket combination, time them, and show the fastest.', 'litespeed-cache' ); ?>
							<?php endif; ?>
						</div>

						<?php
						if ( null !== $bench ) :
							// Hide failed attempts — only successful candidates are useful.
							$bench_visible = array_values( array_filter( $bench['results'], function ( $r ) {
								return ! empty( $r['ok'] );
							} ) );
						?>
							<?php if ( $bench_visible ) : ?>
								<table class="litespeed-table litespeed-table-compact litespeed-oc-benchmark-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Backend', 'litespeed-cache' ); ?></th>
											<th><?php esc_html_e( 'Endpoint', 'litespeed-cache' ); ?></th>
											<th><?php esc_html_e( 'Avg latency', 'litespeed-cache' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $bench_visible as $row ) : ?>
											<?php
											$is_fastest = $bench['fastest']
												&& $row['host'] === $bench['fastest']['host']
												&& (int) $row['port'] === (int) $bench['fastest']['port']
												&& $row['kind_token'] === $bench['fastest']['kind_token'];
											$endpoint   = '/' === substr( (string) $row['host'], 0, 1 )
												? $row['host']
												: $row['host'] . ':' . (int) $row['port'];
											?>
											<tr<?php echo $is_fastest ? ' class="litespeed-oc-benchmark-fastest"' : ''; ?>>
												<td>
													<?php echo esc_html( $row['kind'] ); ?>
													<?php if ( $is_fastest ) : ?>
														<span class="litespeed-success"><?php esc_html_e( '★ fastest', 'litespeed-cache' ); ?></span>
													<?php endif; ?>
												</td>
												<td><code><?php echo esc_html( $endpoint ); ?></code></td>
												<td><?php echo esc_html( number_format( $row['latency_ms'], 2 ) ); ?> ms</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
							<?php if ( $bench['fastest'] ) : ?>
								<div class="litespeed-desc">
									<?php
									printf(
										/* translators: 1: backend name, 2: endpoint (host:port or socket path), 3: latency in milliseconds */
										esc_html__( 'Fastest working object cache: %1$s at %2$s (%3$s ms). If you set the method below to Automatic this is what will be used when you save settings.', 'litespeed-cache' ),
										'<strong>' . esc_html( $bench['fastest']['kind'] ) . '</strong>',
										'<code>' . esc_html( '/' === substr( (string) $bench['fastest']['host'], 0, 1 ) ? $bench['fastest']['host'] : $bench['fastest']['host'] . ':' . (int) $bench['fastest']['port'] ) . '</code>',
										esc_html( number_format( $bench['fastest']['latency_ms'], 2 ) )
									);
									?>
								</div>
							<?php else : ?>
								<div class="litespeed-desc litespeed-warning">
									<?php esc_html_e( 'No candidate connected successfully.', 'litespeed-cache' ); ?>
								</div>
							<?php endif; ?>
							<?php if ( 'run' === $bench_action ) : ?>
								<script>
								// Swap ?ls_oc_benchmark=run → =show in the address bar so a
								// browser refresh hits the cached read path instead of
								// re-running the full sweep. The settings transient still
								// holds the results we just rendered.
								(function () {
									try {
										var u = new URL(window.location.href);
										u.searchParams.set('ls_oc_benchmark', 'show');
										var hash = window.location.hash || '#cache-object';
										window.history.replaceState({}, '', u.pathname + u.search + hash);
									} catch (e) {}
								})();
								</script>
							<?php endif; ?>
						<?php endif; ?>
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
				<?php
				// Display order: Auto first (recommended default), then Redis/Valkey (LiteSpeed's
				// recommended backend), then Memcached. Array keys keep the stored values stable
				// for existing installs: Memcached=0, Redis/Valkey=1, Auto=2.
				$this->build_switch( $option_id, array(
					2 => esc_html__( 'Automatic', 'litespeed-cache' ),
					1 => 'Redis / Valkey',
					0 => 'Memcached',
				) );
				?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Automatic will find the best available backend and adjust your connection settings when you click Save Changes.', 'litespeed-cache' ); ?>
				</div>
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
						'Memcached/LSMCD/<a href="https://www.litespeedtech.com/products/litespeed-web-server/control-panel-support/redis-cache-management" target="_blank" rel="noopener">Redis/Valkey</a>'
					);
					?>
					<br>
					<?php
					printf(
						/* translators: %1$s: Socket name, %2$s: Host field title, %3$s: Example socket path */
						esc_html__( 'If you are using a %1$s socket, %2$s should be set to the socket path on your server, e.g. %3$s', 'litespeed-cache' ),
						'UNIX',
						esc_html( Lang::title( $option_id ) ),
						'<code>/tmp/redis.sock</code>'
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

	</tbody>
</table>

<script>
// Force the Method radios to match the server-rendered `checked` attribute
// on every pageshow (including bfcache restores from back/forward). Browsers
// otherwise preserve the most-recently-clicked radio across navigation/F5
// and override the server's `checked='checked'` markup — which makes the
// sliding pill end up over Memcached when the DB actually says Automatic.
window.addEventListener('pageshow', function () {
	document.querySelectorAll('input[type="radio"][name="object-kind"]').forEach(function (r) {
		r.checked = r.hasAttribute('checked');
	});
});

jQuery(document).ready(function($) {
	// Auto-fill port based on object cache type.
	// Memcached (0) -> 11211, Redis/Valkey (1) -> 6379, Auto (2) -> leave the
	// port untouched so detection can pick a socket on save without the user
	// losing whatever they last typed.
	//
	// Skip the TCP-port auto-fill entirely when the Host field looks like a
	// UNIX socket path (starts with '/'). For sockets the correct port is 0,
	// and the user shouldn't get a Redis-socket-with-Memcached-port hybrid
	// just because they toggled the Method radio.
	$('input[name="object-kind"]').on('change', function() {
		var portInput = $('#input_objectport');
		var hostInput = $('#input_objecthost');
		var hostVal = hostInput.length ? String(hostInput.val() || '').trim() : '';
		var selectedKind = $(this).val();

		if (hostVal.charAt(0) === '/') {
			portInput.val('0');
			return;
		}

		if (selectedKind === '0') {
			portInput.val('11211');
		} else if (selectedKind === '1') {
			portInput.val('6379');
		}
	});
});
</script>
