<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;


$lang_enabled = '<font class="litespeed-success">' . __( 'Enabled', 'litespeed-cache' ) . '</font>' ;
$lang_disabled = '<font class="litespeed-warning">' . __( 'Disabled', 'litespeed-cache' ) . '</font>' ;

$mem_enabled = class_exists( 'Memcached' ) ? $lang_enabled : $lang_disabled ;
$redis_enabled = class_exists( 'Redis' ) ? $lang_enabled : $lang_disabled ;

$mem_conn = $this->cls( 'Object_Cache' )->test_connection();
if ( $mem_conn === null ) {
	$mem_conn_desc = '<font class="litespeed-desc">' . __( 'Not Available', 'litespeed-cache' ) . '</font>' ;
}
elseif ( $mem_conn ) {
	$mem_conn_desc = '<font class="litespeed-success">' . __( 'Passed', 'litespeed-cache' ) . '</font>' ;
}
else {
	$mem_conn_desc = '<font class="litespeed-warning">' . __( 'Failed', 'litespeed-cache' ) . '</font>' ;
}

?>


<h3 class="litespeed-title-short">
	<?php echo __( 'Object Cache Settings', 'litespeed-cache' ) ; ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#object-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Use external object cache functionality.', 'litespeed-cache' ) ; ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/admin/#memcached-lsmcd-and-redis-object-cache-support-in-lscwp' ); ?>
			</div>
			<div class="litespeed-block">

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Status', 'litespeed-cache' ) ; ?></h4>
				</div>
				<div class='litespeed-col-auto'>
					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Memcached' ) ; ?>: <?php echo $mem_enabled ; ?><br />
					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Redis' ) ; ?>: <?php echo $redis_enabled ; ?><br />
					<?php echo __( 'Connection Test', 'litespeed-cache' ) ; ?>: <?php echo $mem_conn_desc ; ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/admin/#how-to-debug' ); ?>
				</div>

			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_KIND ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id, array( 'Memcached', 'Redis' ) ); ?>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_HOST; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Your %s Hostname or IP address.', 'litespeed-cache' ), 'Memcached/<a href="https://docs.litespeedtech.com/products/lsmcd/" target="_blank">LSMCD</a>/Redis' ) ; ?>
				<br /><?php echo sprintf( __( 'If you are using a %1$s socket, %2$s should be set to %3$s', 'litespeed-cache' ), 'UNIX', Lang::title( $id ), '<code>/path/to/memcached.sock</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_PORT; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short2' ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Default port for %1$s is %2$s.', 'litespeed-cache' ), 'Memcached', '<code>11211</code>' ) ; ?>
				<?php echo sprintf( __( 'Default port for %1$s is %2$s.', 'litespeed-cache' ), 'Redis', '<code>6379</code>' ) ; ?>
				<br /><?php echo sprintf( __( 'If you are using a %1$s socket, %2$s should be set to %3$s', 'litespeed-cache' ), 'UNIX', Lang::title( $id ), '<code>0</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_LIFE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short2' ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Default TTL for cached objects.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_USER; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Only available when %s is installed.', 'litespeed-cache' ), 'SASL' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_PSWD; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the password used when connecting.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_DB_ID; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Database to be used', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_GLOBAL_GROUPS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Groups cached at the network level.', 'litespeed-cache' ) ; ?>
				<?php Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_NON_PERSISTENT_GROUPS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 30 ) ; ?>
			<div class="litespeed-desc">
				<?php Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_PERSISTENT; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Use keep-alive connections to speed up cache operations.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_ADMIN; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Improve wp-admin speed through caching. (May encounter expired data)', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OBJECT_TRANSIENTS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Save transients in database when %1$s is %2$s.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_OBJECT_ADMIN ) . '</code>', '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ) ; ?>
				<br />
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#store-transients' ); ?>
			</div>
		</td>
	</tr>


</tbody></table>
