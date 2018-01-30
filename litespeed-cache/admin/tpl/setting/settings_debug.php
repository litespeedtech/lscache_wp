<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Developer Testing', 'litespeed-cache'); ?>
	<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:debug" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
</h3>

<table><tbody>
	<tr>
		<th><?php echo __( 'Debug Log', 'litespeed-cache' ) ; ?></th>
		<td>
			<div class="litespeed-switch">
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
					LiteSpeed_Cache_Config::VAL_ON2,
					__( 'Admin IP only', 'litespeed-cache' )
				) ; ?>
			</div>
			<div class="litespeed-desc">
				<?php echo __( 'Outputs to WordPress debug log.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This should be set to off once everything is working to prevent filling the disk.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'The Admin IP option will only output log messages on requests from admin IPs.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'The logs will be outputted to %s.', 'litespeed-cache' ), '<code>wp-content/debug.log</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Admin IPs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea( LiteSpeed_Cache_Config::OPID_ADMIN_IPS, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Allows listed IPs (one per line) to perform certain actions from their browsers.', 'litespeed-cache' ) ; ?><br />
				<?php echo sprintf( __( 'More information about the available commands can be found <a %s>here</a>.', 'litespeed-cache' ),
					'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:information:admin-ip-commands" target="_blank"' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Debug Level', 'litespeed-cache' ) ; ?></th>
		<td>
			<div class="litespeed-switch">
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
			<div class="litespeed-desc">
				<?php echo __( 'Advanced level will log more details.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Log File Size Limit', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_LOG_FILE_SIZE ; ?>
			<?php $this->build_input( $id, 'litespeed-input-short' ) ; ?> <?php echo __( 'MB', 'litespeed-cache' ) ; ?>
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
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_FILTERS, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed filters (one per line) will not be logged.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:debug#exclude_filters" target="_blank"><?php echo __('Recommended default value', 'litespeed-cache') ; ?></a>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Exclude Part Filters', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_PART_FILTERS, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Filters containing these strings (one per line) will not be logged.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:debug#exclude_part_filters" target="_blank"><?php echo __('Recommended default value', 'litespeed-cache') ; ?></a>
			</div>
		</td>
	</tr>

</tbody></table>
