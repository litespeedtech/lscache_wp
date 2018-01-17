<?php
if ( ! defined( 'WPINC' ) ) die ;

if ( class_exists( 'Memcached' ) ) {
	$mem_enabled = '<font class="litespeed-success">' . __( 'Enabled', 'litespeed-cache' ) . '</font>' ;
}
else {
	$mem_enabled = '<font class="litespeed-warning">' . __( 'Disabled', 'litespeed-cache' ) . '</font>' ;
}

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

?>


	<tr>
		<th><?php echo __( 'Object Cache', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Use object cache functionality.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache:object_cache" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
			</div>
			<div class="litespeed-cdn-mapping-block">
				<div class='litespeed-child-col-auto'>
					<h4><?php echo __( 'Host', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_HOST ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Your Memcached/<a %s>LSMCD</a> Hostname or IP address.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/open-source/litespeed-memcached" target="_blank"' ) ; ?>
					</div>
				</div>

				<div class='litespeed-child-col-auto'>
					<h4><?php echo __( 'Port', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PORT, 'litespeed-input-short2' ) ; ?>
				</div>

				<div class='litespeed-child-col-auto'>
					<h4><?php echo __( 'Default Object Lifetime', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_LIFE, 'litespeed-input-short2' ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Default TTL for cached objects.', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-child-col-auto'>
					<h4><?php echo __( 'Status', 'litespeed-cache' ) ; ?></h4>

					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Memcached' ) ; ?>: <?php echo $mem_enabled ; ?><br />
					<?php echo __( 'Connection Test', 'litespeed-cache' ) ; ?>: <?php echo $mem_conn_desc ; ?><br />
				</div>

				<div class='litespeed-child-col-br'></div>

				<div class='litespeed-child-col-auto'>
					<h4><?php echo __( 'Global Groups', 'litespeed-cache' ) ; ?></h4>
					<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OBJECT_GLOBAL_GROUPS, 30 ) ; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Groups cached at the network level.', 'litespeed-cache' ) ; ?>
						<?php echo __('One per line.', 'litespeed-cache'); ?>
					</div>
				</div>

				<div class='litespeed-child-col-auto'>
					<h4><?php echo __( 'Do Not Cache Groups', 'litespeed-cache' ) ; ?></h4>
					<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OBJECT_NON_PERSISTENT_GROUPS, 30 ) ; ?>
					<div class="litespeed-desc">
						<?php echo __('One per line.', 'litespeed-cache'); ?>
					</div>
				</div>

				<div class='litespeed-child-col-auto'>
					<div class="litespeed-row">
						<div class="litespeed-child-col-inc"><?php echo __( 'Persistent Connection', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PERSISTENT ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo __( 'Use keep-alive connections to speed up memcached.', 'litespeed-cache' ) ; ?>
					</div>
					<div class="litespeed-row litespeed-top30">
						<div class="litespeed-child-col-inc"><?php echo __( 'Cache Wp-admin', 'litespeed-cache' ) ; ?></div>
						<?php $this->build_toggle( LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_ADMIN ) ; ?>
					</div>
					<div class="litespeed-desc">
						<?php echo __( 'Improve wp-admin speed through caching. (May encounter expired data)', 'litespeed-cache' ) ; ?>
					</div>
				</div>

			</div>
		</td>
	</tr>