<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<tr>
	<th>
		<?php $id = Base::O_IMG_OPTM_WEBP; ?>
		<?php $this->title( $id ); ?>
	</th>
	<td>
		<?php $this->build_switch( $id, array( __( 'OFF', 'litespeed-cache' ), 'WebP', 'AVIF' ) ); ?>
		<?php Doc::maybe_on_by_gm( $id ); ?>
		<div class="litespeed-desc">
			<?php echo __( 'Request WebP/AVIF versions of original images when doing optimization.', 'litespeed-cache' ); ?>
			<?php printf( __( 'Significantly improve load time by replacing images with their optimized %s versions.', 'litespeed-cache' ), '.webp/.avif' ); ?>
			<br /><?php Doc::notice_htaccess(); ?>
			<br /><?php Doc::crawler_affected(); ?>
			<br />
			<font class="litespeed-warning">
				⚠️ <?php printf( __( '%1$s is a %2$s paid feature.', 'litespeed-cache' ), 'AVIF', 'QUIC.cloud' ); ?></font>
			<br />
			<font class="litespeed-warning">
				⚠️ <?php printf( __( 'When switching formats, please %1$s or %2$s to apply this new choice to previously optimized images.', 'litespeed-cache' ), __( 'Destroy All Optimization Data', 'litespeed-cache' ), __( 'Soft Reset Optimization Counter', 'litespeed-cache' ) ); ?></font>
			<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/imageopt/#soft-reset-optimization-counter' ); ?>
		</div>
	</td>
</tr>