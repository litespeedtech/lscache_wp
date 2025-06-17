<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Heartbeat Control', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#heartbeat-tab' ); ?>
</h3>

<div class="litespeed-callout notice notice-warning inline">
	<h4><?php echo __( 'NOTICE:', 'litespeed-cache' ); ?></h4>
	<p><?php echo __( 'Disable WordPress interval heartbeat to reduce server load.', 'litespeed-cache' ); ?>
	<span class="litespeed-warning">
		🚨
		<?php echo __( 'Disabling this may cause WordPress tasks triggered by AJAX to stop working.', 'litespeed-cache' ); ?>
</span></p>
</div>


<table class="wp-list-table striped litespeed-table"><tbody>

	<tr>
		<th>
			<?php $id = Base::O_MISC_HEARTBEAT_FRONT; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">

				<?php echo __( 'Turn ON to control heartbeat on frontend.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MISC_HEARTBEAT_FRONT_TTL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?> <?php $this->readable_seconds(); ?>
			<div class="litespeed-desc">
				<?php printf( __( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'frontend' ); ?>
				<?php printf( __( 'WordPress valid interval is %s seconds.', 'litespeed-cache' ), '<code>15</code> - <code>120</code>' ); ?><br />
				<?php printf( __( 'Set to %1$s to forbid heartbeat on %2$s.', 'litespeed-cache' ), '<code>0</code>', 'frontend' ); ?><br />
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 15, 120, true ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MISC_HEARTBEAT_BACK; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Turn ON to control heartbeat on backend.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MISC_HEARTBEAT_BACK_TTL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?> <?php $this->readable_seconds(); ?>
			<div class="litespeed-desc">
				<?php printf( __( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'backend' ); ?>
				<?php printf( __( 'WordPress valid interval is %s seconds', 'litespeed-cache' ), '<code>15</code> ~ <code>120</code>' ); ?><br />
				<?php printf( __( 'Set to %1$s to forbid heartbeat on %2$s.', 'litespeed-cache' ), '<code>0</code>', 'backend' ); ?><br />
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 15, 120, true ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MISC_HEARTBEAT_EDITOR; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Turn ON to control heartbeat in backend editor.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MISC_HEARTBEAT_EDITOR_TTL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?> <?php $this->readable_seconds(); ?>
			<div class="litespeed-desc">
				<?php printf( __( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'backend editor' ); ?>
				<?php printf( __( 'WordPress valid interval is %s seconds', 'litespeed-cache' ), '<code>15</code> ~ <code>120</code>' ); ?><br />
				<?php printf( __( 'Set to %1$s to forbid heartbeat on %2$s.', 'litespeed-cache' ), '<code>0</code>', 'frontend' ); ?><br />
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 15, 120, true ); ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php $this->form_end(); ?>
