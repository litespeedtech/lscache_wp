<?php
/**
 * LiteSpeed Cache Image Optimization Settings
 *
 * Manages image optimization settings for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Image Optimization Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/imageopt/#image-optimization-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_AUTO; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Automatically request optimization via cron job.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_ORI; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Optimize images and save backups of the originals in the same folder.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_RM_BKUP; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Automatically remove the original image backups after fetching optimized images.', 'litespeed-cache' ); ?>

					<br />
					<font class="litespeed-danger">
						🚨
						<?php esc_html_e( 'This is irreversible.', 'litespeed-cache' ); ?>
						<?php esc_html_e( 'You will be unable to Revert Optimization once the backups are deleted!', 'litespeed-cache' ); ?>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_LOSSLESS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Optimize images using lossless compression.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'This can improve quality but may result in larger images than lossy compression will.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php
					$option_id    = Base::O_IMG_OPTM_SIZES_SKIPPED;
					$image_sizes  = Utility::prepare_image_sizes_array(true);
					$option_value = $this->conf( $option_id );
				?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php if ( count($image_sizes) > 0 ) : ?>
					<?php
						foreach ( $image_sizes as $current_size ) {
							$checked = false === array_search( $current_size['file_size'], $option_value, true );
							$this->build_checkbox( $option_id . '[]', esc_html( $current_size['label'] ), $checked, $current_size['file_size'] );
						}
					?>
				<?php else : ?>
					<p><?php esc_html_e( 'No sizes found.', 'litespeed-cache' ); ?></p>
				<?php endif; ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Choose which image sizes to optimize.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_EXIF; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Preserve EXIF data (copyright, GPS, comments, keywords, etc) when optimizing.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'This will increase the size of optimized files.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<?php
		if ( ! is_multisite() ) :
			// webp
			require LSCWP_DIR . 'tpl/img_optm/settings.media_webp.tpl.php';
		endif;
		?>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_WEBP_ATTR; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<div class="litespeed-textarea-recommended">
					<div>
						<?php $this->build_textarea( $option_id, 40 ); ?>
					</div>
					<div>
						<?php $this->recommended( $option_id ); ?>
					</div>
				</div>

				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify which element attributes will be replaced with WebP/AVIF.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'Only attributes listed here will be replaced.', 'litespeed-cache' ); ?>
					<br /><?php printf( esc_html__( 'Use the format %1$s or %2$s (element is optional).', 'litespeed-cache' ), '<code>element.attribute</code>', '<code>.attribute</code>' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_IMG_OPTM_WEBP_REPLACE_SRCSET; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Enable replacement of WebP/AVIF in %s elements that were generated outside of WordPress logic.', 'litespeed-cache' ), '<code>srcset</code>' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/imageopt/#webpavif-for-extra-srcset' ); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>

<?php
$this->form_end();
