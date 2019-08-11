<?php defined( 'WPINC' ) || exit ; ?>


<div class="litespeed-callout notice notice-warning inline">
	<h4><?php echo __( 'NOTICE:', 'litespeed-cache' ); ?></h4>
	<p><?php echo __( 'Disable WordPress interval heartbeat to reduce server load.', 'litespeed-cache' ) ; ?>
	<span class="litespeed-warning">
		🚨
		<?php echo __( 'Disabling this may cause WordPress tasks triggered by AJAX to stop working.', 'litespeed-cache' ) ; ?>
</span></p>
</div>

<?php $this->form_action() ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Heartbeat Control', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:heartbeat', false, 'litespeed-learn-more' ) ; ?>
</h3>


<table class="wp-list-table widefat striped"><tbody>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_FRONT ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">

				<?php echo __( 'Control if use heartbeat on frontend or not.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_FRONT_TTL ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short') ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'frontend' ) ; ?>
				<?php echo sprintf( __( 'WordPress valid interval is %s seconds', 'litespeed-cache' ), '<code>15</code> ~ <code>120</code>' ) ; ?>
				<?php echo sprintf( __( 'Set to %1$s to disable %2$s heartbeat.', 'litespeed-cache' ), '<code>0</code>', 'frontend' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 15, 120, true ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_BACK ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Control if use heartbeat on backend or not.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_BACK_TTL ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short') ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'backend' ) ; ?>
				<?php echo sprintf( __( 'WordPress valid interval is %s seconds', 'litespeed-cache' ), '<code>15</code> ~ <code>120</code>' ) ; ?>
				<?php echo sprintf( __( 'Set to %1$s to disable %2$s heartbeat.', 'litespeed-cache' ), '<code>0</code>', 'backend' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 15, 120, true ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_EDITOR ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Control if use heartbeat on backend editor or not.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_EDITOR_TTL ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short') ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify the %s heartbeat interval in seconds.', 'litespeed-cache' ), 'backend editor' ) ; ?>
				<?php echo sprintf( __( 'WordPress valid interval is %s seconds', 'litespeed-cache' ), '<code>15</code> ~ <code>120</code>' ) ; ?>
				<?php echo sprintf( __( 'Set to %1$s to disable %2$s heartbeat.', 'litespeed-cache' ), '<code>0</code>', 'backend editor' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 15, 120, true ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php $this->form_end() ; ?>
