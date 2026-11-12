<?php
/**
 * LiteSpeed Cache OptimaX Summary
 *
 * Manages the OX summary interface for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 8.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$summary        = Optimax::get_summary();
$closest_server = Cloud::get_summary( 'server.' . Cloud::SVC_OPTIMAX );
$queue          = $this->load_queue( 'optimax' );
$ox_service_hot = $this->cls( 'Cloud' )->service_hot( Cloud::SVC_OPTIMAX );
$ox_on          = (bool) $this->conf( Base::O_OPTIMAX );
$ox_cron        = (bool) $this->conf( Base::O_OPTIMAX_CRON );
$wp_cron_off    = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
$ox_stats       = $this->cls( 'Optimax_Pages' )->stats();
$nextgen_title  = $this->cls( 'Media' )->next_gen_image_title();

// Recommended settings that are off, as links.
$ox_tips = [];
if ( ! $this->conf( Base::O_CACHE_MOBILE ) ) {
	$ox_tips[] = '<a href="' . esc_url( ( is_multisite() ? network_admin_url( 'admin.php?page=litespeed-cache' ) : admin_url( 'admin.php?page=litespeed-cache' ) ) . '#cache' ) . '">' . esc_html__( 'Mobile Cache', 'litespeed-cache' ) . '</a>';
}
if ( ! $this->conf( Base::O_IMG_OPTM_WEBP ) ) {
	$ox_tips[] = '<a href="' . esc_url( admin_url( 'admin.php?page=litespeed-img_optm#settings' ) ) . '">' . esc_html__( 'Next-Gen Image Format', 'litespeed-cache' ) . '</a>';
}
?>
<div class="litespeed-flex-container litespeed-column-with-boxes">
	<div class="litespeed-width-7-10 litespeed-column-left">
		<h3 class="litespeed-title-short"><?php esc_html_e( 'OptimaX', 'litespeed-cache' ); ?></h3>
		<?php if ( $ox_on && $ox_tips ) : ?>
			<div class="litespeed-callout notice notice-warning inline">
				<p>
					<?php /* translators: %s: list of settings, each a link (Mobile Cache, Next-Gen Image Format) */ echo wp_kses_post( sprintf( __( 'For best performance we recommend enabling %s.', 'litespeed-cache' ), wp_sprintf( '%l', $ox_tips ) ) ); ?>
				</p>
			</div>
		<?php endif; ?>
		<?php if ( ! $ox_on ) : ?>
			<div class="litespeed-callout notice notice-error inline">
				<h4><?php esc_html_e( 'OptimaX is disabled', 'litespeed-cache' ); ?></h4>
				<p><?php /* translators: %s: link to the OptimaX Settings tab */ printf( esc_html__( 'Turn on OptimaX in the %s.', 'litespeed-cache' ), '<a href="' . esc_url( admin_url( 'admin.php?page=litespeed-optimax#settings' ) ) . '">' . esc_html__( 'OptimaX Settings tab', 'litespeed-cache' ) . '</a>' ); ?></p>
			</div>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'Pages optimized', 'litespeed-cache' ); ?>: <code><?php echo esc_html( $ox_stats['pages'] ); ?></code>
				<span class="litespeed-desc"><?php /* translators: %d: number of page versions (desktop, mobile, next-gen) */ echo esc_html( sprintf( _n( '(%d version)', '(%d versions)', $ox_stats['versions'], 'litespeed-cache' ), $ox_stats['versions'] ) ); ?></span>
			</p>
			<p>
				<?php esc_html_e( 'Pages to rebuild after a design change', 'litespeed-cache' ); ?>: <code><?php echo esc_html( $ox_stats['refresh'] ); ?></code>
				<?php if ( $ox_stats['refresh'] ) : ?>
					<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_OPTIMAX, Optimax::TYPE_REBUILD ) ); ?>" class="button button-small litespeed-left10"><?php esc_html_e( 'Rebuild now', 'litespeed-cache' ); ?></a>
					<br /><span class="litespeed-desc"><?php esc_html_e( 'Each is also queued again on its next visit. Visitors get it without OptimaX until it is rebuilt.', 'litespeed-cache' ); ?></span>
				<?php endif; ?>
			</p>
			<?php
			$ox_optimized = $ox_stats['pages'] ? $this->cls( 'Optimax_Pages' )->optimized( 50 ) : [];
			// A change under way shows instead of the last action; a version whose build it dropped is still listed, with it.
			$ox_listed = array_column( array_merge( [], ...array_values( $ox_optimized ) ), 'key', 'key' );
			$ox_open   = [];
			foreach ( $this->cls( 'Optimax_Pages' )->checks() as $ox_key => $ox_check ) {
				if ( empty( $ox_check['done'] ) ) {
					$ox_open[ $ox_key ] = $ox_check;
					if ( ! isset( $ox_listed[ $ox_key ] ) ) {
						$ox_optimized[ $ox_check['url'] ][] = [ 'mobile' => $ox_check['mobile'], 'nextgen' => $ox_check['nextgen'], 'key' => $ox_key ];
					}
				}
			}
			// The versions QUIC.cloud already has: their check is past the queue.
			$ox_sent = [];
			foreach ( $queue as $queue_val ) {
				if ( is_array( $queue_val ) && ! empty( $queue_val['url_tag'] ) && isset( $queue_val['vary'] ) && ! Optimax_Pages::is_waiting( $queue_val ) ) {
					$ox_sent[ Optimax_Pages::check_key( $queue_val['url_tag'], $queue_val['vary'] ) ] = true;
				}
			}
			$ox_action_labels = [
				'generated'     => __( 'Generated', 'litespeed-cache' ),
				'rebuilt'       => __( 'Rebuilt', 'litespeed-cache' ),
				'synced_plugin' => __( 'Synced (plugin)', 'litespeed-cache' ),
				'synced_qc'     => __( 'Synced (QC)', 'litespeed-cache' ),
			];
			$ox_cause_labels  = [
				'visit'       => __( 'first visit to this version of the page', 'litespeed-cache' ),
				'missing'     => __( 'the previous build had missing files', 'litespeed-cache' ),
				'ttl'         => __( 'Rebuild Interval reached', 'litespeed-cache' ),
				'bulk'        => __( 'requested with Rebuild now', 'litespeed-cache' ),
				'design'      => __( 'the page changed and the change could not be synced', 'litespeed-cache' ),
				'sync'        => __( 'a small change carried into the build by QUIC.cloud', 'litespeed-cache' ),
				'sync_failed' => __( 'QUIC.cloud could not sync the change', 'litespeed-cache' ),
				'patch'       => __( 'the text changed and the plugin updated the build', 'litespeed-cache' ),
			];
			$ox_stage_labels  = [
				'confirming' => __( 'Change detected; confirming on the next visit', 'litespeed-cache' ),
				'sync'       => __( 'Queued: QUIC.cloud decides between a small change and a full build', 'litespeed-cache' ),
				'sync_sent'  => __( 'At QUIC.cloud: deciding between a small change and a full build', 'litespeed-cache' ),
				'rebuild'    => __( 'Design changed: full build queued', 'litespeed-cache' ),
			];
			?>
			<?php if ( $ox_optimized ) : ?>
				<div class="litespeed-callout notice notice-success inline">
					<h4><?php esc_html_e( 'Optimized pages', 'litespeed-cache' ); ?> ( <?php echo esc_html( $ox_stats['pages'] ); ?> )</h4>
					<p>
						<?php foreach ( $ox_optimized as $ox_url => $ox_versions ) : ?>
							<a href="<?php echo esc_url( $ox_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $ox_url ); ?></a><br />
							<?php foreach ( $ox_versions as $ox_ver ) : ?>
								<span class="litespeed-left10"><?php echo esc_html( ( $ox_ver['mobile'] ? '📱' : '🖥️' ) . ( $ox_ver['nextgen'] ? ' ' . $nextgen_title : '' ) ); ?></span>
								<?php if ( isset( $ox_open[ $ox_ver['key'] ] ) ) : ?>
									<?php $ox_check = $ox_open[ $ox_ver['key'] ]; ?>
									<?php $ox_stage = 'sync' === $ox_check['stage'] && isset( $ox_sent[ $ox_ver['key'] ] ) ? 'sync_sent' : $ox_check['stage']; ?>
									— <span class="litespeed-warning"><?php echo esc_html( isset( $ox_stage_labels[ $ox_stage ] ) ? $ox_stage_labels[ $ox_stage ] : $ox_stage ); ?></span>
									<span class="litespeed-desc"><?php /* translators: %s: time since, e.g. "5 mins" */ echo esc_html( sprintf( __( '%s ago', 'litespeed-cache' ), human_time_diff( (int) $ox_check['since'] ) ) ); ?></span>
									<?php if ( '' !== $ox_check['what'] ) : ?>
										<br /><span class="litespeed-desc litespeed-left20"><?php echo esc_html( $ox_check['what'] ); ?></span>
									<?php endif; ?>
								<?php elseif ( isset( $ox_action_labels[ $ox_ver['action'] ] ) ) : ?>
									— <span class="litespeed-success"><?php echo esc_html( $ox_action_labels[ $ox_ver['action'] ] ); ?></span>
									<?php if ( $ox_ver['at'] ) : ?>
										<span class="litespeed-desc"><?php echo esc_html( trim( Utility::readable_time( $ox_ver['at'] ) ) ); ?></span>
									<?php endif; ?>
									<?php if ( isset( $ox_cause_labels[ $ox_ver['cause'] ] ) ) : ?>
										<br /><span class="litespeed-desc litespeed-left20"><?php /* translators: %s: why the build was made */ echo esc_html( sprintf( __( 'Reason: %s', 'litespeed-cache' ), $ox_cause_labels[ $ox_ver['cause'] ] ) . ( '' !== $ox_ver['detail'] ? ' (' . $ox_ver['detail'] . ')' : '' ) ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
								<br />
							<?php endforeach; ?>
						<?php endforeach; ?>
						<?php echo $ox_stats['pages'] > count( $ox_optimized ) ? '...' : ''; ?>
					</p>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $queue ) ) : ?>
				<div class="litespeed-callout notice notice-warning inline">
					<h4>
						<?php printf( esc_html__( 'URL list in %s queue waiting for cron', 'litespeed-cache' ), 'OptimaX' ); ?> ( <?php echo esc_html( count( $queue ) ); ?> )
						<?php if ( array_filter( $queue, [ 'LiteSpeed\Optimax_Pages', 'is_waiting' ] ) ) : ?>
							<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_OPTIMAX, Optimax::TYPE_CLEAR_Q ) ); ?>" class="button litespeed-btn-warning litespeed-right" data-litespeed-cfm="<?php esc_attr_e( 'Remove the pages waiting in the OptimaX queue? Pages QUIC.cloud is already optimizing are kept.', 'litespeed-cache' ); ?>"><?php esc_html_e( 'Clear', 'litespeed-cache' ); ?></a>
						<?php endif; ?>
					</h4>
					<p>
						<?php
						foreach ( array_slice( $queue, 0, 21 ) as $queue_val ) :
							if ( ! is_array( $queue_val ) || empty( $queue_val['url'] ) ) {
								continue;
							}
							// Sent to QUIC.cloud: highlighted.
							echo Optimax_Pages::is_waiting( $queue_val ) ? esc_html( $queue_val['url'] ) : '<span class="litespeed-success">' . esc_html( $queue_val['url'] ) . '</span>';
							Optimax::vary_group( ! empty( $queue_val['is_mobile'] ), ! empty( $queue_val['is_nextgen'] ) ? '<code>' . esc_html( $nextgen_title ) . '</code>' : '' );
							if ( ! empty( $queue_val['_tries'] ) && (int) $queue_val['_tries'] >= Optimax::MAX_TRIES ) {
								echo ' <span class="litespeed-danger">' . esc_html( sprintf( __( 'Failed %d times, not retried', 'litespeed-cache' ), Optimax::MAX_TRIES ) ) . '</span>';
							} elseif ( ! empty( $queue_val['_status'] ) && 'failed' === $queue_val['_status'] ) {
								echo ' <span class="litespeed-danger">' . esc_html__( 'Failed, will retry', 'litespeed-cache' ) . '</span>';
							}
							echo '<br />';
						endforeach;
						echo count( $queue ) > 21 ? '...' : '';
						?>
					</p>
				</div>
				<p>
					<?php if ( $ox_service_hot ) : ?>
						<button class="button button-secondary" disabled><?php printf( esc_html__( 'Run %s Queue Manually', 'litespeed-cache' ), 'OptimaX' ); ?> - <?php printf( esc_html__( 'Available after %d second(s)', 'litespeed-cache' ), esc_html( $ox_service_hot ) ); ?></button>
					<?php else : ?>
						<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_OPTIMAX, Optimax::TYPE_GEN ) ); ?>" class="button litespeed-btn-success"><?php printf( esc_html__( 'Run %s Queue Manually', 'litespeed-cache' ), 'OptimaX' ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<div class="litespeed-callout notice notice-warning inline">
				<h4><?php esc_html_e( 'OptimaX can make mistakes', 'litespeed-cache' ); ?></h4>
				<p><?php /* translators: 1: link to the support forum, 2: link to submit a ticket */ printf( esc_html__( 'Please test each page after OptimaX runs on it. If you find errors, contact us on the %1$s or %2$s.', 'litespeed-cache' ), '<a href="https://wordpress.org/support/plugin/litespeed-cache/" rel="noopener noreferrer" target="_blank">' . esc_html__( 'support forum', 'litespeed-cache' ) . '</a>', '<a href="https://store.litespeedtech.com/store/submitticket.php" rel="noopener noreferrer" target="_blank">' . esc_html__( 'submit a ticket', 'litespeed-cache' ) . '</a>' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<div class="litespeed-width-3-10 litespeed-column-right">
		<div class="postbox litespeed-postbox">
			<div class="inside">
				<h3 class="litespeed-title">
					<?php esc_html_e( 'OptimaX Status', 'litespeed-cache' ); ?>
				</h3>

				<p>
					<span class="<?php echo $ox_on ? 'litespeed-label-success' : 'litespeed-label-danger'; ?> litespeed-label-dashboard"><?php echo $ox_on ? esc_html__( 'ON', 'litespeed-cache' ) : esc_html__( 'OFF', 'litespeed-cache' ); ?></span>
					OptimaX
				</p>
				<p>
					<span class="<?php echo $ox_cron ? esc_attr( $wp_cron_off ? 'litespeed-label-warning' : 'litespeed-label-success' ) : 'litespeed-label-danger'; ?> litespeed-label-dashboard"><?php echo $ox_cron ? esc_html__( 'ON', 'litespeed-cache' ) : esc_html__( 'OFF', 'litespeed-cache' ); ?></span>
					<?php esc_html_e( 'OptimaX Cron', 'litespeed-cache' ); ?>
					<?php if ( $ox_cron && $wp_cron_off ) : ?>
						<span class="litespeed-warning"> - <?php esc_html_e( 'WP Cron is not running', 'litespeed-cache' ); ?></span>
						<?php if ( $ox_on ) : ?>
							<br /><br /><a href="<?php echo esc_url( Utility::build_url( Router::ACTION_OPTIMAX, Optimax::TYPE_RUN_CRON ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Run Cron now', 'litespeed-cache' ); ?></a>
						<?php endif; ?>
					<?php endif; ?>
				</p>
				<p><?php esc_html_e( 'Queued URLs', 'litespeed-cache' ); ?>: <code><?php echo esc_html( count( $queue ) ); ?></code></p>
				<p><?php esc_html_e( 'Images optimized by OptimaX', 'litespeed-cache' ); ?>: <code><?php echo esc_html( (int) Img_Optm::get_summary( Optimax::SUMMARY_OX_IMGS ) ); ?></code></p>
				<?php if ( ! empty( $summary['last_request_optimax'] ) ) : ?>
					<p><?php esc_html_e( 'Last Request', 'litespeed-cache' ); ?>: <code><?php echo esc_html( Utility::readable_time( $summary['last_request_optimax'] ) ); ?></code></p>
				<?php endif; ?>
				<?php if ( ! empty( $summary['last_took_ms_optimax'] ) || ! empty( $summary['last_spent_optimax'] ) ) : ?>
					<p><?php esc_html_e( 'Last Request Cost', 'litespeed-cache' ); ?>: <code><?php echo esc_html( ! empty( $summary['last_took_ms_optimax'] ) ? number_format( $summary['last_took_ms_optimax'] / 1000, 2 ) : $summary['last_spent_optimax'] ); ?>s</code></p>
				<?php endif; ?>
				<?php if ( $closest_server ) : ?>
					<p><?php esc_html_e( 'Cloud Server', 'litespeed-cache' ); ?>: <a class='litespeed-redetect' href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_OPTIMAX ) ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label="<?php printf( esc_attr__( 'Current closest Cloud server is %s. Click to redetect.', 'litespeed-cache' ), esc_attr( $closest_server ) ); ?>"><i class='litespeed-quic-icon'></i> <?php esc_html_e( 'Redetect', 'litespeed-cache' ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>