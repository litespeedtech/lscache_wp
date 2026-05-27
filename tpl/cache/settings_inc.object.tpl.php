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

// Benchmark is opt-in via a query param — the full sweep is expensive.
// States: no cache → "Run"; cache present → "Show" + "Re-run"; action=run → fresh sweep.
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

// Badge reflects whether the *configured* host answered — a 'detected' fallback
// is still Failed for the saved config (the detail line below hints at the fix).
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

						<?php
						$render_bench         = null !== $bench ? $bench : $bench_cache;
						$bench_initial_hidden = null === $bench && null !== $bench_cache;
						?>
						<div class="litespeed-desc" style="margin-top:0.75em;">
							<?php if ( $bench_initial_hidden ) : ?>
								<a href="#" class="litespeed-oc-bench-show"><?php esc_html_e( 'Show benchmarks', 'litespeed-cache' ); ?></a>
								&nbsp;|&nbsp;
								<a href="<?php echo esc_url( $bench_run_url ); ?>" class="litespeed-oc-bench-run"><?php esc_html_e( 'Re-run Benchmarks', 'litespeed-cache' ); ?></a>
								&mdash; <?php esc_html_e( 'walks every host/port/socket combination and surfaces the fastest one.', 'litespeed-cache' ); ?>
							<?php elseif ( null !== $render_bench ) : ?>
								<a href="<?php echo esc_url( $bench_run_url ); ?>" class="litespeed-oc-bench-run"><?php esc_html_e( 'Re-run Benchmarks', 'litespeed-cache' ); ?></a>
								&mdash; <?php esc_html_e( 'walks every host/port/socket combination and surfaces the fastest one.', 'litespeed-cache' ); ?>
							<?php else : ?>
								<a href="<?php echo esc_url( $bench_run_url ); ?>" class="litespeed-oc-bench-run"><?php esc_html_e( 'Run Benchmark', 'litespeed-cache' ); ?></a>
								&mdash; <?php esc_html_e( 'walks every host/port/socket combination and surfaces the fastest one.', 'litespeed-cache' ); ?>
							<?php endif; ?>
						</div>

						<div class="litespeed-oc-bench-results"<?php echo $bench_initial_hidden ? ' hidden' : ''; ?>>
						<?php
						if ( null !== $render_bench ) :
							$bench_visible = array_values( array_filter( $render_bench['results'], function ( $r ) {
								return ! empty( $r['ok'] );
							} ) );
						?>
							<?php if ( $bench_visible ) : ?>
								<table class="litespeed-table litespeed-table-compact litespeed-oc-benchmark-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Backend', 'litespeed-cache' ); ?></th>
											<th><?php esc_html_e( 'Host', 'litespeed-cache' ); ?></th>
											<th><?php esc_html_e( 'Port', 'litespeed-cache' ); ?></th>
											<th><?php esc_html_e( 'Avg latency', 'litespeed-cache' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $bench_visible as $row ) : ?>
											<?php
											$is_fastest = $render_bench['fastest']
												&& $row['host'] === $render_bench['fastest']['host']
												&& (int) $row['port'] === (int) $render_bench['fastest']['port']
												&& $row['kind_token'] === $render_bench['fastest']['kind_token'];
											?>
											<tr<?php echo $is_fastest ? ' class="litespeed-oc-benchmark-fastest"' : ''; ?>>
												<td>
													<?php echo esc_html( $row['kind'] ); ?>
													<?php if ( $is_fastest ) : ?>
														<span class="litespeed-success"><?php esc_html_e( '★ fastest', 'litespeed-cache' ); ?></span>
													<?php endif; ?>
												</td>
												<td><code><?php echo esc_html( $row['host'] ); ?></code></td>
												<td><code><?php echo (int) $row['port']; ?></code></td>
												<td><?php echo esc_html( number_format( $row['latency_ms'], 2 ) ); ?> ms</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
							<?php if ( $render_bench['fastest'] ) : ?>
								<div class="litespeed-desc litespeed-oc-bench-fastest-line">
									<?php
									$fastest_is_socket = '/' === substr( (string) $render_bench['fastest']['host'], 0, 1 );
									$fastest_location  = $fastest_is_socket
										? sprintf(
											/* translators: %s: socket path */
											esc_html__( 'socket %s', 'litespeed-cache' ),
											'<code>' . esc_html( $render_bench['fastest']['host'] ) . '</code>'
										)
										: sprintf(
											/* translators: 1: hostname, 2: port number */
											esc_html__( 'host %1$s on port %2$s', 'litespeed-cache' ),
											'<code>' . esc_html( $render_bench['fastest']['host'] ) . '</code>',
											'<code>' . (int) $render_bench['fastest']['port'] . '</code>'
										);
									printf(
										/* translators: 1: backend name, 2: host/port or socket descriptor, 3: latency in milliseconds */
										esc_html__( 'Fastest working object cache: %1$s at %2$s (%3$s ms). Set the method below to Automatic and save to apply these settings.', 'litespeed-cache' ),
										'<strong>' . esc_html( $render_bench['fastest']['kind'] ) . '</strong>',
										$fastest_location,
										esc_html( number_format( $render_bench['fastest']['latency_ms'], 2 ) )
									);
									?>
								</div>
							<?php else : ?>
								<div class="litespeed-desc litespeed-warning litespeed-oc-bench-empty-line">
									<?php esc_html_e( 'No candidate connected successfully.', 'litespeed-cache' ); ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>
						</div>

						<script>
						(function () {
							var ajaxUrl       = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
							var nonce         = <?php echo wp_json_encode( wp_create_nonce( 'litespeed_oc_benchmark' ) ); ?>;
							var runningLabel  = <?php echo wp_json_encode( __( 'Running benchmarks', 'litespeed-cache' ) ); ?>;
							var probingLabel  = <?php echo wp_json_encode( __( 'Probing…', 'litespeed-cache' ) ); ?>;
							var failedLabel   = <?php echo wp_json_encode( __( 'Failed', 'litespeed-cache' ) ); ?>;
							var msLabel       = <?php echo wp_json_encode( __( 'ms', 'litespeed-cache' ) ); ?>;
							var headBackend   = <?php echo wp_json_encode( __( 'Backend', 'litespeed-cache' ) ); ?>;
							var headHost      = <?php echo wp_json_encode( __( 'Host', 'litespeed-cache' ) ); ?>;
							var headPort      = <?php echo wp_json_encode( __( 'Port', 'litespeed-cache' ) ); ?>;
							var headLatency   = <?php echo wp_json_encode( __( 'Avg latency', 'litespeed-cache' ) ); ?>;
							var fastestBadge  = <?php echo wp_json_encode( __( '★ fastest', 'litespeed-cache' ) ); ?>;
							var emptyMsg      = <?php echo wp_json_encode( __( 'No candidate connected successfully.', 'litespeed-cache' ) ); ?>;
							var fastestPrefix = <?php echo wp_json_encode( __( 'Fastest working object cache:', 'litespeed-cache' ) ); ?>;
							var socketLabel   = <?php echo wp_json_encode( __( 'socket', 'litespeed-cache' ) ); ?>;
							var hostLabel     = <?php echo wp_json_encode( __( 'host', 'litespeed-cache' ) ); ?>;
							var onPortLabel   = <?php echo wp_json_encode( __( 'on port', 'litespeed-cache' ) ); ?>;
							var fastestTail   = <?php echo wp_json_encode( __( 'Set the method below to Automatic and save to apply these settings.', 'litespeed-cache' ) ); ?>;

							var runLinks  = document.querySelectorAll('.litespeed-oc-bench-run');
							var showLink  = document.querySelector('.litespeed-oc-bench-show');
							var container = document.querySelector('.litespeed-oc-bench-results');
							var originalRunHTML = [];
							for (var i = 0; i < runLinks.length; i++) {
								originalRunHTML.push(runLinks[i].textContent);
							}

							if (showLink && container) {
								showLink.addEventListener('click', function (e) {
									e.preventDefault();
									container.removeAttribute('hidden');
									showLink.style.display = 'none';
								});
							}

							function postForm(data) {
								var body = [];
								body.push('action=litespeed_oc_benchmark');
								body.push('nonce=' + encodeURIComponent(nonce));
								Object.keys(data).forEach(function (k) {
									body.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
								});
								return fetch(ajaxUrl, {
									method: 'POST',
									credentials: 'same-origin',
									headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
									body: body.join('&')
								}).then(function (r) { return r.json(); });
							}

							function setLinkRunning(link) {
								while (link.firstChild) {
									link.removeChild(link.firstChild);
								}
								link.appendChild(document.createTextNode(runningLabel + ' '));
								var sp = document.createElement('span');
								sp.className = 'litespeed-oc-bench-spinner';
								sp.setAttribute('aria-hidden', 'true');
								link.appendChild(sp);
								link.style.pointerEvents = 'none';
								link.style.opacity = '0.7';
							}

							function restoreLinks() {
								for (var i = 0; i < runLinks.length; i++) {
									while (runLinks[i].firstChild) {
										runLinks[i].removeChild(runLinks[i].firstChild);
									}
									runLinks[i].appendChild(document.createTextNode(originalRunHTML[i]));
									runLinks[i].style.pointerEvents = '';
									runLinks[i].style.opacity = '';
								}
							}

							function isSocket(host) {
								return typeof host === 'string' && host.charAt(0) === '/';
							}

							function buildPlaceholderTable(candidates) {
								while (container.firstChild) {
									container.removeChild(container.firstChild);
								}
								container.removeAttribute('hidden');

								var table = document.createElement('table');
								table.className = 'litespeed-table litespeed-table-compact litespeed-oc-benchmark-table';

								var thead = document.createElement('thead');
								var headRow = document.createElement('tr');
								[headBackend, headHost, headPort, headLatency].forEach(function (h) {
									var th = document.createElement('th');
									th.textContent = h;
									headRow.appendChild(th);
								});
								thead.appendChild(headRow);
								table.appendChild(thead);

								var tbody = document.createElement('tbody');
								candidates.forEach(function (c, idx) {
									var tr = document.createElement('tr');
									tr.setAttribute('data-bench-row', String(idx));

									var tdKind = document.createElement('td');
									tdKind.textContent = c.kind;
									tr.appendChild(tdKind);

									var tdHost = document.createElement('td');
									var codeHost = document.createElement('code');
									codeHost.textContent = c.host;
									tdHost.appendChild(codeHost);
									tr.appendChild(tdHost);

									var tdPort = document.createElement('td');
									var codePort = document.createElement('code');
									codePort.textContent = String(c.port);
									tdPort.appendChild(codePort);
									tr.appendChild(tdPort);

									var tdLatency = document.createElement('td');
									tdLatency.className = 'litespeed-oc-bench-pending';
									tdLatency.textContent = probingLabel;
									tr.appendChild(tdLatency);

									tbody.appendChild(tr);
								});
								table.appendChild(tbody);
								container.appendChild(table);
								return tbody;
							}

							function updateRow(tbody, idx, result) {
								var tr = tbody.querySelector('tr[data-bench-row="' + idx + '"]');
								if (!tr) return;
								var cells = tr.querySelectorAll('td');
								// Backend label may refine (Redis → Valkey) on success.
								if (result.ok && result.product) {
									cells[0].textContent = result.product;
								}
								var latencyCell = cells[3];
								latencyCell.className = '';
								while (latencyCell.firstChild) {
									latencyCell.removeChild(latencyCell.firstChild);
								}
								if (result.ok && result.latency_ms !== null) {
									latencyCell.textContent = result.latency_ms.toFixed(2) + ' ' + msLabel;
								} else {
									tr.classList.add('litespeed-oc-bench-failed-row');
									latencyCell.textContent = failedLabel;
								}
							}

							function markFastestAndDescriptor(results) {
								var fastest = null;
								results.forEach(function (r) {
									if (r.ok && r.latency_ms !== null) {
										if (!fastest || r.latency_ms < fastest.latency_ms) {
											fastest = r;
										}
									}
								});

								// Decorate fastest row.
								if (fastest) {
									var tbody = container.querySelector('tbody');
									if (tbody) {
										var rows = tbody.querySelectorAll('tr');
										for (var i = 0; i < rows.length; i++) {
											if (i === results.indexOf(fastest)) {
												rows[i].classList.add('litespeed-oc-benchmark-fastest');
												var kindCell = rows[i].querySelector('td');
												var badge = document.createElement('span');
												badge.className = 'litespeed-success';
												badge.style.marginLeft = '0.4em';
												badge.textContent = fastestBadge;
												kindCell.appendChild(badge);
											}
										}
									}
								}

								// Hide failed rows to match server-rendered behaviour.
								var failedRows = container.querySelectorAll('.litespeed-oc-bench-failed-row');
								for (var f = 0; f < failedRows.length; f++) {
									failedRows[f].parentNode.removeChild(failedRows[f]);
								}

								// Append the descriptor line.
								var descriptor = document.createElement('div');
								descriptor.className = 'litespeed-desc litespeed-oc-bench-fastest-line';
								if (fastest) {
									var locText = isSocket(fastest.host)
										? socketLabel + ' '
										: hostLabel + ' ';
									descriptor.appendChild(document.createTextNode(fastestPrefix + ' '));
									var kindStrong = document.createElement('strong');
									kindStrong.textContent = fastest.product || fastest.kind;
									descriptor.appendChild(kindStrong);
									descriptor.appendChild(document.createTextNode(' at ' + locText));
									var locCode = document.createElement('code');
									locCode.textContent = fastest.host;
									descriptor.appendChild(locCode);
									if (!isSocket(fastest.host)) {
										descriptor.appendChild(document.createTextNode(' ' + onPortLabel + ' '));
										var portCode = document.createElement('code');
										portCode.textContent = String(fastest.port);
										descriptor.appendChild(portCode);
									}
									descriptor.appendChild(document.createTextNode(' (' + fastest.latency_ms.toFixed(2) + ' ms). ' + fastestTail));
								} else {
									descriptor.className = 'litespeed-desc litespeed-warning litespeed-oc-bench-empty-line';
									descriptor.textContent = emptyMsg;
								}
								container.appendChild(descriptor);

								return fastest;
							}

							function startAsyncBenchmark(link, e) {
								e.preventDefault();
								for (var i = 0; i < runLinks.length; i++) {
									setLinkRunning(runLinks[i]);
								}

								postForm({ step: 'list' }).then(function (res) {
									if (!res || !res.success || !res.data || !res.data.candidates) {
										// AJAX failed — fall back to the server-rendered path.
										window.location.href = link.href;
										return;
									}
									var candidates = res.data.candidates;
									var tbody = buildPlaceholderTable(candidates);
									var results = new Array(candidates.length);
									var idx = 0;

									function next() {
										if (idx >= candidates.length) {
											var fastest = markFastestAndDescriptor(results);
											postForm({
												step: 'commit',
												payload: JSON.stringify({
													results: results,
													fastest: fastest,
													ran_at: Math.floor(Date.now() / 1000)
												})
											});
											restoreLinks();
											return;
										}
										var c = candidates[idx];
										postForm({
											step: 'run',
											kind: c.kind_token,
											host: c.host,
											port: c.port
										}).then(function (r) {
											var data = (r && r.success) ? r.data : {
												ok: false,
												latency_ms: null,
												runs: 0,
												product: null,
												error: 'ajax_failed',
												kind: c.kind,
												kind_token: c.kind_token,
												host: c.host,
												port: c.port
											};
											results[idx] = data;
											updateRow(tbody, idx, data);
											idx++;
											next();
										}).catch(function () {
											results[idx] = { ok: false, latency_ms: null, kind: c.kind, kind_token: c.kind_token, host: c.host, port: c.port };
											updateRow(tbody, idx, results[idx]);
											idx++;
											next();
										});
									}
									next();
								}).catch(function () {
									window.location.href = link.href;
								});
							}

							for (var k = 0; k < runLinks.length; k++) {
								runLinks[k].addEventListener('click', function (e) {
									startAsyncBenchmark(this, e);
								});
							}
						})();
						</script>
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
				// Array keys preserve stored values: Memcached=0, Redis/Valkey=1, Auto=2.
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
	// Auto-fill port when toggling Method. Memcached (0) -> 11211, Redis/Valkey (1) -> 6379.
	// Auto (2) leaves the port alone so detection can keep whatever the user typed.
	$('input[name="object-kind"]').on('change', function() {
		var portInput = $('#input_objectport');
		var selectedKind = $(this).val();
		if (selectedKind === '0') {
			portInput.val('11211');
		} else if (selectedKind === '1') {
			portInput.val('6379');
		}
	});
});
</script>
