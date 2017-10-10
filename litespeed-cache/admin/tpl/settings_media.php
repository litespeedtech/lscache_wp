<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<h3 class="litespeed-title"><?php echo __( 'Media Settings', 'litespeed-cache' ) ; ?></h3>

<table><tbody>
	<tr>
		<th><?php echo __( 'Lazyload Images', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load images only when they enter the viewport.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Lazyload Iframes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IFRAME_LAZY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load iframes only when they enter the viewport.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>


</tbody></table>