<?php
if ( ! defined( 'WPINC' ) ) die ;

?>
	<tr <?php if ( isset( $_hide_in_basic_mode ) ) echo $_hide_in_basic_mode ; ?>>
		<th><?php echo __( 'Browser Cache', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::O_UTIL_BROWSER_CACHE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Browser caching stores static files locally in the user\'s browser. Turn on this setting to reduce repeated requests for static files.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
				<br /><?php echo sprintf( __( 'You can turn on browser caching in server admin too. <a %s>Learn more about LiteSpeed browser cache setting</a>.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:browser_cache" target="_blank"' ) ; ?>
			</div>
		</td>
	</tr>

	<tr <?php if ( isset( $_hide_in_basic_mode ) ) echo $_hide_in_basic_mode ; ?>>
		<th class="litespeed-padding-left"><?php echo __( 'Browser Cache TTL', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::O_UTIL_BROWSER_CACHE_TTL ; ?>
			<?php $this->build_input( $id ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'The amount of time, in seconds, that files will be stored in browser cache before expiring.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->ttl_validate( $id, 30 ) ; ?>
			</div>
		</td>
	</tr>

