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

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'OptimaX Settings', 'litespeed-cache' ); ?>
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
					<?php esc_html_e( 'Turn on OptimaX.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTIMAX_CRON; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Automatically process the OptimaX queue via cron job, one page version per run. When off, run the queue manually from the OptimaX Summary tab.', 'litespeed-cache' ); ?>
					<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
						<br /><font class="litespeed-warning"><?php esc_html_e( 'WP Cron is not running', 'litespeed-cache' ); ?></font>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTIMAX_EXC; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Pages OptimaX should never optimize. They keep the other optimizations.', 'litespeed-cache' ); ?>
					<?php $this->_uri_usage_example(); ?>
					<br /><span class="litespeed-success">
						<?php esc_html_e( 'API', 'litespeed-cache' ); ?>:
						<?php printf( esc_html__( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optimax_uri_exc</code>' ); ?>
					</span>
				</div>
			</td>
		</tr>

	</tbody>
</table>

<?php
$this->form_end();
