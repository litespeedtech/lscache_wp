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
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:object_cache" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
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
						<?php echo __( 'How many seconds the default object cache should use.', 'litespeed-cache' ) ; ?>
					</div>
				</div>

				<div class='litespeed-child-col-auto'>
					<h4><?php echo __( 'Status', 'litespeed-cache' ) ; ?></h4>

					<?php echo sprintf( __( '%s Extension', 'litespeed-cache' ), 'Memcached' ) ; ?>: <?php echo $mem_enabled ; ?><br />
					<?php echo __( 'Connection Test', 'litespeed-cache' ) ; ?>: <?php echo $mem_conn_desc ; ?><br />
					<?php echo sprintf( __( 'More settings please go to <a %2$s>%1$s</a>.', 'litespeed-cache' ), '<b>' . __( 'Advanced', 'litespeed-cache' ) . '</b>', 'href="admin.php?page=lscache-settings&#advanced"' ) ; ?>
				</div>

			</div>
		</td>
	</tr>