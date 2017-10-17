<?php
if ( ! defined( 'WPINC' ) ) die ;

?>
	<tr>
		<th><?php echo __( 'Browser Cache', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_BROWSER ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Browser caching stores static files locally in the user\'s browser. Turn on this setting to reduce repeated requests for static files.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-warning">
					<?php echo __('NOTE:', 'litespeed-cache'); ?>
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
				<br /><?php echo sprintf( __( 'You can turn on browser caching in server admin too. <a %s>Learn more about LiteSpeed browser cache setting</a>.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:browser_cache" target="_blank"' ) ; ?>
			</div>
		</td>
	</tr>

