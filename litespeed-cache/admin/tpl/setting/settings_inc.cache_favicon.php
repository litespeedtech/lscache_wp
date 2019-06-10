<?php defined( 'WPINC' ) || exit ; ?>

	<tr>
		<th><?php echo __( 'Cache favicon.ico', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::O_CACHE_FAVICON ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'favicon.ico is requested on most pages.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Caching this resource may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache' ) ; ?>
				<br /><?php LiteSpeed_Cache_Doc::notice_htaccess() ; ?>
			</div>
		</td>
	</tr>
