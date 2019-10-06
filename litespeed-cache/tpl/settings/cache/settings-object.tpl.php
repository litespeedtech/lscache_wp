<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;


$lang_enabled = '<font class="litespeed-success">' . __( 'Enabled', 'litespeed-cache' ) . '</font>' ;
$lang_disabled = '<font class="litespeed-warning">' . __( 'Disabled', 'litespeed-cache' ) . '</font>' ;

$mem_enabled = class_exists( 'Memcached' ) ? $lang_enabled : $lang_disabled ;
$redis_enabled = class_exists( 'Redis' ) ? $lang_enabled : $lang_disabled ;

$mem_conn = Object_Cache::get_instance()->test_connection() ;
if ( $mem_conn === null ) {
	$mem_conn_desc = '<font class="litespeed-desc">' . __( 'Not Available', 'litespeed-cache' ) . '</font>' ;
}
elseif ( $mem_conn ) {
	$mem_conn_desc = '<font class="litespeed-success">' . __( 'Passed', 'litespeed-cache' ) . '</font>' ;
}
else {
	$mem_conn_desc = '<font class="litespeed-warning">' . __( 'Failed', 'litespeed-cache' ) . '</font>' ;
}

$hide_mem_options = ! Core::config( Base::O_OBJECT_KIND ) ? '' : ' litespeed-hide' ;
$hide_redis_options = Core::config( Base::O_OBJECT_KIND ) ? '' : ' litespeed-hide' ;

?>


<h3 class="litespeed-title-short">
	<?php echo __( 'Object Cache Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:object', false, 'litespeed-learn-more' ) ; ?>
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
				<?php echo __( 'Use object cache functionality.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache:object_cache' ) ; ?>
			</div>
			<div class="litespeed-block">
				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Method', 'litespeed-cache' ) ; ?></h4>

					<div class="litespeed-switch">
						<?php $this->build_radio( Base::O_OBJECT_KIND, Base::VAL_OFF, 'Memcached' ) ; ?>
						<?php $this->build_radio( Base::O_OBJECT_KIND, Base::VAL_ON, 'Redis' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Host', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( Base::O_OBJECT_HOST ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Your %s Hostname or IP address.', 'litespeed-cache' ), 'Memcached/<a href="https://www.litespeedtech.com/open-source/litespeed-memcached" target="_blank">LSMCD</a>/Redis' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Port', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( Base::O_OBJECT_PORT, 'litespeed-input-short2' ) ; ?>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Default Object Lifetime', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( Base::O_OBJECT_LIFE, 'litespeed-input-short2' ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Default TTL for cached objects.', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Status', 'litespeed-cache' ) ; ?></h4>

					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Memcached' ) ; ?>: <?php echo $mem_enabled ; ?><br />
					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Redis' ) ; ?>: <?php echo $redis_enabled ; ?><br />
					<?php echo __( 'Connection Test', 'litespeed-cache' ) ; ?>: <?php echo $mem_conn_desc ; ?>
					<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache:object_cache#how_to_debug' ) ; ?>
				</div>

				<div class='litespeed-col-br'></div>

				<div class='litespeed-col-auto <?php echo $hide_mem_options ; ?>' data="litespeed-mem-divs">
					<h4><?php echo __( 'Username', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( Base::O_OBJECT_USER ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Only available when %s is installed.', 'litespeed-cache' ), 'SASL' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Password', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( Base::O_OBJECT_PSWD ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Specify the password used when connecting.', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto <?php echo $hide_redis_options ; ?>' data="litespeed-redis-divs">
					<h4><?php echo __( 'Redis Database ID', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( Base::O_OBJECT_DB_ID, 'litespeed-input-short' ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Database to be used', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-br'></div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Global Groups', 'litespeed-cache' ) ; ?></h4>
					<?php $this->build_textarea( Base::O_OBJECT_GLOBAL_GROUPS, 30 ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Groups cached at the network level.', 'litespeed-cache' ) ; ?>
						<?php Doc::one_per_line() ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Do Not Cache Groups', 'litespeed-cache' ) ; ?></h4>
					<?php $this->build_textarea( Base::O_OBJECT_NON_PERSISTENT_GROUPS, 30 ) ; ?>
					<div class="litespeed-desc">
						<?php Doc::one_per_line() ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Persistent Connection', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( Base::O_OBJECT_PERSISTENT ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo __( 'Use keep-alive connections to speed up cache operations.', 'litespeed-cache' ) ; ?>
					</div>
					<div class="litespeed-row litespeed-top30">
						<div class="litespeed-col-inc"><?php echo __( 'Cache Wp-Admin', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( Base::O_OBJECT_ADMIN ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo __( 'Improve wp-admin speed through caching. (May encounter expired data)', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Store Transients', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( Base::O_OBJECT_TRANSIENTS ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Save transients in database when %1$s is %2$s.', 'litespeed-cache' ), '<code>' . __( 'Cache Wp-Admin', 'litespeed-cache' ) . '</code>', '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ) ; ?>
						<br />
						<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache:object_cache#store_transients' ) ; ?>
					</div>
				</div>

			</div>
		</td>
	</tr>

</tbody></table>
