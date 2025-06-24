<?php
/**
 * LiteSpeed Cache Heartbeat Control
 *
 * Renders the heartbeat control settings interface for LiteSpeed Cache, allowing configuration of WordPress heartbeat intervals.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Heartbeat Control', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#heartbeat-tab' ); ?>
</h3>

<div class="litespeed-callout notice notice-warning inline">
	<h4><?php esc_html_e( 'NOTICE:', 'litespeed-cache' ); ?></h4>
	<p>
		<?php esc_html_e( 'Disable WordPress interval heartbeat to reduce server load.', 'litespeed-cache' ); ?>
		<span class="litespeed-warning">
			🚨 <?php esc_html_e( 'Disabling this may cause WordPress tasks triggered by AJAX to stop working.', 'litespeed-cache' ); ?>
		</span>
	</p>
</div>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $option_id = Base::O_MISC_HEARTBEAT_FRONT; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Turn ON to control heartbeat on frontend.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MISC_HEARTBEAT_FRONT_TTL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?> <?php $this->readable_seconds(); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'frontend' ); ?>
					<?php printf( esc_html__( 'WordPress valid interval is %s seconds.', 'litespeed-cache' ), '<code>15</code> - <code>120</code>' ); ?><br />
					<?php printf( esc_html__( 'Set to %1$s to forbid heartbeat on %2$s.', 'litespeed-cache' ), '<code>0</code>', 'frontend' ); ?><br />
					<?php $this->recommended( $option_id ); ?>
					<?php $this->_validate_ttl( $option_id, 15, 120, true ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MISC_HEARTBEAT_BACK; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Turn ON to control heartbeat on backend.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_MISC_HEARTBEAT_BACK_TTL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?> <?php $this->readable_seconds(); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'backend' ); ?>
					<?php printf( esc_html__( 'WordPress valid interval is %s seconds.', 'litespeed-cache' ), '<code>15</code> - <code>120</code>' ); ?><br />
					<?php printf( esc_html__( 'Set to %1$s to forbid heartbeat on %2$s.', 'litespeed-cache' ), '<code>0</code>', 'backend' ); ?><br />
					<?php $this->recommended( $option_id ); ?>
					<?php $this->_validate_ttl( $option_id, 15, 120, true ); ?>
</div>
</td>
</tr>

<tr>
		<th>
			<?php $option_id = Base::O_MISC_HEARTBEAT_EDITOR; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( 'Turn ON to control heartbeat in backend editor.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_MISC_HEARTBEAT_EDITOR_TTL; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?> <?php $this->readable_seconds(); ?>
			<div class="litespeed-desc">
		<?php printf( esc_html__( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'backend editor' ); ?>
		<?php printf( esc_html__( 'WordPress valid interval is %s seconds.', 'litespeed-cache' ), '<code>15</code> - <code>120</code>' ); ?><br />
		<?php printf( esc_html__( 'Set to %1$s to forbid heartbeat on %2$s.', 'litespeed-cache' ), '<code>0</code>', 'backend editor' ); ?><br />
		<?php $this->recommended( $option_id ); ?>
		<?php $this->_validate_ttl( $option_id, 15, 120, true ); ?>
	</div>
</td>
</tr>

</tbody>
</table>

<?php $this->form_end(); ?>