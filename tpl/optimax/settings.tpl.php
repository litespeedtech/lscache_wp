<?php
/**
 * LiteSpeed Cache OptimaX Settings
 *
 * Manages OptimaX settings for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 8.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$optimax_queue       = $this->load_queue( 'optimax' );
$optimax_service_hot = $this->cls( 'Cloud' )->service_hot( Cloud::SVC_OPTIMAX );
$closest_server      = Cloud::get_summary( 'server.' . Cloud::SVC_OPTIMAX );
$next_gen            = '<code class="litespeed-success">' . $this->cls( 'Media' )->next_gen_image_title() . '</code>';

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'OptimaX Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/imageopt/#image-optimization-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTIMAX; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Turn on OptimaX. This will automatically request your pages OptimaX result via cron job.', 'litespeed-cache' ); ?>
				</div>

				<?php if ( $closest_server ) : ?>
					<a class="litespeed-redetect" href="<?php echo esc_url( Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_OPTIMAX ) ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label="<?php printf( esc_html__( 'Current closest Cloud server is %s. Click to redetect.', 'litespeed-cache' ), esc_html( $closest_server ) ); ?>" data-litespeed-cfm="<?php esc_html_e( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ); ?>"><i class="litespeed-quic-icon"></i> <?php esc_html_e( 'Redetect', 'litespeed-cache' ); ?></a>
				<?php endif; ?>

				<?php if ( ! empty( $optimax_queue ) ) : ?>
					<div class="litespeed-callout notice notice-warning inline">
						<h4>
							<?php printf( esc_html__( 'URL list in %s queue waiting for cron', 'litespeed-cache' ), 'OptimaX' ); ?> ( <?php echo esc_html( count( $optimax_queue ) ); ?> )
							<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_OPTIMAX, Optimax::TYPE_CLEAR_Q ) ); ?>" class="button litespeed-btn-warning litespeed-right"><?php esc_html_e( 'Clear', 'litespeed-cache' ); ?></a>
						</h4>
						<p>
							<?php
							$i = 0;
							foreach ( $optimax_queue as $queue_key => $queue_val ) :
								if ( ! is_array( $queue_val ) || empty( $queue_val['url'] ) || ! is_string( $queue_val['url'] ) ) {
									continue;
								}
								// Count only rows that render, so a malformed entry cannot eat a slot.
								if ( $i++ > 20 ) :
									echo '...';
									break;
								endif;
								echo esc_html( $queue_val['url'] );
								$pos = strpos( $queue_key, ' ' );
								if ( $pos ) {
									echo ' (' . esc_html__( 'Vary Group', 'litespeed-cache' ) . ':' . esc_html( substr( $queue_key, 0, $pos ) ) . ')';
								}
								if ( ! empty( $queue_val['is_mobile'] ) ) {
									echo ' <span data-balloon-pos="up" aria-label="mobile">📱</span>';
								}
								if ( ! empty( $queue_val['is_nextgen'] ) ) {
									echo ' ' . wp_kses_post( $next_gen );
								}
								echo '<br />';
							endforeach;
							?>
						</p>
					</div>
					<?php if ( $optimax_service_hot ) : ?>
						<button class="button button-secondary" disabled>
							<?php printf( esc_html__( 'Run %s Queue Manually', 'litespeed-cache' ), 'OptimaX' ); ?>
							- <?php printf( esc_html__( 'Available after %d second(s)', 'litespeed-cache' ), esc_html( $optimax_service_hot ) ); ?>
						</button>
					<?php else : ?>
						<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_OPTIMAX, Optimax::TYPE_GEN ) ); ?>" class="button litespeed-btn-success">
							<?php printf( esc_html__( 'Run %s Queue Manually', 'litespeed-cache' ), 'OptimaX' ); ?>
						</a>
					<?php endif; ?>
					<?php Doc::queue_issues(); ?>
				<?php endif; ?>
			</td>
		</tr>

	</tbody>
</table>

<?php
$this->form_end();
