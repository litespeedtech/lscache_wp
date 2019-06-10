<?php defined( 'WPINC' ) || exit ; ?>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Image WebP Replacement', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_REPLACE ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Significantly improve load time by replacing images with their optimized %s versions.', 'litespeed-cache' ), '.webp' ) ; ?>
				<br /><?php LiteSpeed_Cache_Doc::notice_htaccess() ; ?>
			</div>
		</td>
	</tr>
