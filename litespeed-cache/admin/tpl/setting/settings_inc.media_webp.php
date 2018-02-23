<?php
if ( ! defined( 'WPINC' ) ) die ;

?>
	<tr>
		<th><?php echo __( 'Image WebP Replacement', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Significantly improve load time by replacing images with their optimized %s versions.', 'litespeed-cache' ), '.webp' ) ; ?>
				<br /><font class="litespeed-warning">
					<?php echo __('NOTE', 'litespeed-cache'); ?>:
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>

