<?php
/**
 * OptimaX box on the LiteSpeed Cache dashboard, shown where its menu is (LITESPEED_OX).
 *
 * @package LiteSpeed
 * @since 8.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

if ( ! defined( 'LITESPEED_OX' ) ) {
	return;
}

$optimax_summary     = Optimax::get_summary();
$optimax_queue_count = count( $this->load_queue( 'optimax' ) );
?>
<div class="postbox litespeed-postbox litespeed-postbox-optimax">
	<div class="inside">
		<h3 class="litespeed-title">OptimaX <a href="<?php echo esc_url( admin_url( 'admin.php?page=litespeed-optimax' ) ); ?>" class="litespeed-title-right-icon"><?php esc_html_e( 'More', 'litespeed-cache' ); ?></a></h3>
		<?php if ( ! empty( $optimax_summary['last_request_optimax'] ) ) : ?>
			<p><?php printf( esc_html__( 'Last generated: %s', 'litespeed-cache' ), '<code>' . esc_html( Utility::readable_time( $optimax_summary['last_request_optimax'] ) ) . '</code>' ); ?></p>
			<?php if ( ! empty( $optimax_summary['last_took_ms_optimax'] ) || ! empty( $optimax_summary['last_spent_optimax'] ) ) : ?>
				<p><?php printf( esc_html__( 'Time to execute previous request: %s', 'litespeed-cache' ), '<code>' . esc_html( ! empty( $optimax_summary['last_took_ms_optimax'] ) ? number_format( $optimax_summary['last_took_ms_optimax'] / 1000, 2 ) : $optimax_summary['last_spent_optimax'] ) . 's</code>' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<p>
			<?php esc_html_e( 'Requests in queue', 'litespeed-cache' ); ?>: <code><?php echo ! empty( $optimax_queue_count ) ? esc_html( $optimax_queue_count ) : '-'; ?></code>
			<a href="<?php echo ! empty( $optimax_queue_count ) ? esc_url( Utility::build_url( Router::ACTION_OPTIMAX, Optimax::TYPE_GEN ) ) : 'javascript:;'; ?>" class="button button-secondary button-small <?php echo empty( $optimax_queue_count ) ? 'disabled' : ''; ?>"><?php esc_html_e( 'Force cron', 'litespeed-cache' ); ?></a>
		</p>
	</div>
	<?php if ( ! empty( $cloud_summary['last_request.optimax'] ) ) : ?>
		<div class="inside litespeed-postbox-footer litespeed-postbox-footer--compact"><?php printf( esc_html__( 'Last requested: %s', 'litespeed-cache' ), esc_html( Utility::readable_time( $cloud_summary['last_request.optimax'] ) ) ); ?></div>
	<?php endif; ?>
</div>
