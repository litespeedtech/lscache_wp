<?php defined( 'WPINC' ) || exit ; ?>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_FAVICON ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'favicon.ico is requested on most pages.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Caching this resource may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache' ) ; ?>
				<br /><?php LiteSpeed_Cache_Doc::notice_htaccess() ; ?>
			</div>
		</td>
	</tr>
