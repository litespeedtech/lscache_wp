<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<h3 class="litespeed-title"><?php echo __( 'Developer Testing', 'litespeed-cache' ) ; ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __( 'Debug Log', 'litespeed-cache' ) ; ?></th>
		<td>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::OPID_DEBUG,
						LiteSpeed_Cache_Config::VAL_OFF,
						__( 'Off', 'litespeed-cache' )
					) ; ?>

					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::OPID_DEBUG,
						LiteSpeed_Cache_Config::VAL_ON,
						__( 'On', 'litespeed-cache' )
					) ; ?>

					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::OPID_DEBUG,
						LiteSpeed_Cache_Config::VAL_NOTSET,
						__( 'Admin IP only', 'litespeed-cache' )
					) ; ?>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __( 'Outputs to WordPress debug log.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This should be set to off once everything is working to prevent filling the disk.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'The Admin IP option will only output log messages on requests from admin IPs.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'The logs will be outputted to the debug.log in the wp-content directory.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Admin IPs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea( LiteSpeed_Cache_Config::OPID_ADMIN_IPS, null, false, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Allows listed IPs (one per line) to perform certain actions from their browsers.', 'litespeed-cache' ) ; ?><br />
				<?php echo sprintf( __( 'More information about the available commands can be found <a href="%s">here</a>.', 'litespeed-cache' ),
					get_admin_url() . 'admin.php?page=lscache-info#admin_ip' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Debug Level', 'litespeed-cache' ) ; ?></th>
		<td>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::OPID_DEBUG_LEVEL,
						LiteSpeed_Cache_Config::VAL_OFF,
						__( 'Basic', 'litespeed-cache' )
					) ; ?>

					<?php echo $this->build_radio(
						LiteSpeed_Cache_Config::OPID_DEBUG_LEVEL,
						LiteSpeed_Cache_Config::VAL_ON,
						__( 'Advanced', 'litespeed-cache' )
					) ; ?>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo __( 'Advanced level will log more details.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Log File Size Limit', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_LOG_FILE_SIZE ; ?>
			<?php $this->build_input( $id, 'small-text' ) ; ?> <?php echo __( 'MB', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the maximum size of the log file. Minimum is 3MB. Maximum is 3000MB.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Heartbeat', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_HEARTBEAT ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Disable WordPress heartbeat to prevent AJAX calls from breaking debug logging. WARNING: Disabling this may cause WordPress tasks triggered by AJAX to stop working.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Log Cookies', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_DEBUG_COOKIE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Log request cookie values.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Collapse Query Strings', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_COLLAPS_QS ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Shorten query strings in the debug log to improve readability.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Log Filters', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_LOG_FILTERS ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Log all WordPress filter hooks. WARNING: Enabling this option will cause log file size to grow quickly.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Exclude Filters', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea( LiteSpeed_Cache_Config::OPID_LOG_IGNORE_FILTERS, null, false, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed filters (one per line) will not be logged.', 'litespeed-cache' ) ; ?><br />
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Exclude Part Filters', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea( LiteSpeed_Cache_Config::OPID_LOG_IGNORE_PART_FILTERS, null, false, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Filters containing these strings (one per line) will not be logged.', 'litespeed-cache' ) ; ?><br />
			</div>
		</td>
	</tr>

</tbody></table>

<?php

/* Maybe add this feature later
  $id = LiteSpeed_Cache_Config::OPID_TEST_IPS;
  $input_test_ips  = $this->input_field_text($id, $options[$id], '', 'regular-text');
  $buf .= $this->display_config_row('Test IPs', $input_test_ips,
  'Enable LiteSpeed Cache only for specified IPs. (Space or comma separated.)
 * Allows testing on a live site. If empty, cache will be served to everyone.');
 *
 */
