<?php
if ( ! defined( 'WPINC' ) ) die ;

$lang_enabled = '<font class="litespeed-success">' . __( 'Enabled', 'litespeed-cache' ) . '</font>' ;
$lang_disabled = '<font class="litespeed-warning">' . __( 'Disabled', 'litespeed-cache' ) . '</font>' ;

$mem_enabled = class_exists( 'Memcached' ) ? $lang_enabled : $lang_disabled ;
$redis_enabled = class_exists( 'Redis' ) ? $lang_enabled : $lang_disabled ;

$mem_conn = LiteSpeed_Cache_Object::get_instance()->test_connection() ;
if ( $mem_conn === null ) {
	$mem_conn_desc = '<font class="litespeed-desc">' . __( 'Not Available', 'litespeed-cache' ) . '</font>' ;
}
elseif ( $mem_conn ) {
	$mem_conn_desc = '<font class="litespeed-success">' . __( 'Passed', 'litespeed-cache' ) . '</font>' ;
}
else {
	$mem_conn_desc = '<font class="litespeed-warning">' . __( 'Failed', 'litespeed-cache' ) . '</font>' ;
}

$hide_mem_options = ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_KIND ) ? '' : ' litespeed-hide' ;
$hide_redis_options = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_KIND ) ? '' : ' litespeed-hide' ;

?>


	<tr <?php echo $_hide_in_basic_mode ; ?>>
		<th><?php echo __( 'Object Cache', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Use object cache functionality.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache:object_cache" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
			</div>
			<div class="litespeed-block">
				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Method', 'litespeed-cache' ) ; ?></h4>

					<div class="litespeed-switch">
						<?php echo $this->build_radio( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_KIND, LiteSpeed_Cache_Config::VAL_OFF, 'Memcached', null, 'litespeed-oc-mem' ) ; ?>
						<?php echo $this->build_radio( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_KIND, LiteSpeed_Cache_Config::VAL_ON, 'Redis', null, 'litespeed-oc-redis' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Host', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_HOST ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Your %s Hostname or IP address.', 'litespeed-cache' ), 'Memcached/<a href="https://www.litespeedtech.com/open-source/litespeed-memcached" target="_blank">LSMCD</a>/Redis' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Port', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PORT, 'litespeed-input-short2' ) ; ?>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Default Object Lifetime', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_LIFE, 'litespeed-input-short2' ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Default TTL for cached objects.', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Status', 'litespeed-cache' ) ; ?></h4>

					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Memcached' ) ; ?>: <?php echo $mem_enabled ; ?><br />
					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Redis' ) ; ?>: <?php echo $redis_enabled ; ?><br />
					<?php echo __( 'Connection Test', 'litespeed-cache' ) ; ?>: <?php echo $mem_conn_desc ; ?>
					<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache:object_cache#how_to_debug" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
				</div>

				<div class='litespeed-col-br'></div>

				<div class='litespeed-col-auto <?php echo $hide_mem_options ; ?>' data="litespeed-mem-divs">
					<h4><?php echo __( 'Username', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_USER ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Only available when %s is installed.', 'litespeed-cache' ), 'SASL' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Password', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PSWD ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Specify the password used when connecting.', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto <?php echo $hide_redis_options ; ?>' data="litespeed-redis-divs">
					<h4><?php echo __( 'Redis Database ID', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_DB_ID, 'litespeed-input-short' ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Database to be used', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-br'></div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Global Groups', 'litespeed-cache' ) ; ?></h4>
					<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OBJECT_GLOBAL_GROUPS, 30 ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Groups cached at the network level.', 'litespeed-cache' ) ; ?>
						<?php echo __('One per line.', 'litespeed-cache'); ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __( 'Do Not Cache Groups', 'litespeed-cache' ) ; ?></h4>
					<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OBJECT_NON_PERSISTENT_GROUPS, 30 ) ; ?>
					<div class="litespeed-desc">
						<?php echo __('One per line.', 'litespeed-cache'); ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Persistent Connection', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PERSISTENT ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo __( 'Use keep-alive connections to speed up cache operations.', 'litespeed-cache' ) ; ?>
					</div>
					<div class="litespeed-row litespeed-top30">
						<div class="litespeed-col-inc"><?php echo __( 'Cache Wp-Admin', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_ADMIN ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo __( 'Improve wp-admin speed through caching. (May encounter expired data)', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Store Transients', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_TRANSIENTS ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Save transients in database when %1$s is %2$s.', 'litespeed-cache' ), '<code>' . __( 'Cache Wp-Admin', 'litespeed-cache' ) . '</code>', '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ) ; ?>
						<br /><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache:object_cache#store_transients" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
					</div>
				</div>

			</div>
		</td>
	</tr>