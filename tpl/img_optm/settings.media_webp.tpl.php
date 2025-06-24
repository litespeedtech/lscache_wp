<?php
/**
 * LiteSpeed Cache Image Optimization WebP/AVIF Setting
 *
 * Manages the WebP and AVIF optimization settings for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<tr>
	<th>
		<?php $option_id = Base::O_IMG_OPTM_WEBP; ?>
		<?php $this->title( $option_id ); ?>
	</th>
	<td>
		<?php $this->build_switch( $option_id, array( esc_html__( 'OFF', 'litespeed-cache' ), 'WebP', 'AVIF' ) ); ?>
		<?php Doc::maybe_on_by_gm( $option_id ); ?>
		<div class="litespeed-desc">
			<?php esc_html_e( 'Request WebP/AVIF versions of original images when doing optimization.', 'litespeed-cache' ); ?>
			<?php printf( esc_html__( 'Significantly improve load time by replacing images with their optimized %s versions.', 'litespeed-cache' ), '.webp/.avif' ); ?>
			<br /><?php Doc::notice_htaccess(); ?>
			<br /><?php Doc::crawler_affected(); ?>
			<br />
			<font class="litespeed-warning">
				⚠️ <?php printf( esc_html__( '%1$s is a %2$s paid feature.', 'litespeed-cache' ), 'AVIF', 'QUIC.cloud' ); ?></font>
			<br />
			<font class="litespeed-warning">
				⚠️ <?php printf( esc_html__( 'When switching formats, please %1$s or %2$s to apply this new choice to previously optimized images.', 'litespeed-cache' ), esc_html__( 'Destroy All Optimization Data', 'litespeed-cache' ), esc_html__( 'Soft Reset Optimization Counter', 'litespeed-cache' ) ); ?></font>
			<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/imageopt/#soft-reset-optimization-counter' ); ?>
		</div>
	</td>
</tr>