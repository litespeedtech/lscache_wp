<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<h3 class="litespeed-title"><?php echo __( 'Specific Pages', 'litespeed-cache' ) ; ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __( 'Enable Cache for Login Page', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_LOGIN ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Disabling this option may negatively affect performance.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<?php
		if ( ! is_multisite() ) {
			require LSWCP_DIR . 'admin/tpl/settings_inc.cache_favicon.php' ;
			require LSWCP_DIR . 'admin/tpl/settings_inc.cache_resources.php' ;
		}
	?>

	<tr>
		<th><?php echo __( 'Scheduled Purge URLs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea( LiteSpeed_Cache_Config::OPID_TIMED_URLS, null, false, 80 ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'The URLs here (one per line) will be purged automatically at the time set in the option "%s".', 'litespeed-cache' ), __( 'Scheduled Purge Time', 'litespeed-cache' ) ) ; ?><br />
				<?php echo sprintf( __( 'Both %1$s and %2$s are acceptable.', 'litespeed-cache' ), '<i>http://www.example.com/path/url.php</i>', '<i>/path/url.php</i>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Scheduled Purge Time', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_TIMED_URLS_TIME ; ?>
			<?php $this->build_input( $id, '', false, false, null, null, '', 'time' ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify the time to purge the "%s" list.', 'litespeed-cache' ), __( 'Scheduled Purge URLs', 'litespeed-cache' ) ) ; ?>
				<?php echo sprintf( __( 'Current server time is %s.', 'litespeed-cache' ), '<i>' . date( 'H:i:s' ) . '</i>' ) ; ?>
			</div>
		</td>
	</tr>



</tbody></table>

