<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __('Image Optimization Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/imageopt/#image-optimization-settings-tab'); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_AUTO; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Automatically request optimization via cron job.', 'litespeed-cache'); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_ORI; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Optimize images and save backups of the originals in the same folder.', 'litespeed-cache'); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_RM_BKUP; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Automatically remove the original image backups after fetching optimized images.', 'litespeed-cache'); ?>

					<br />
					<font class="litespeed-danger">
						🚨
						<?php echo __('This is irreversible.', 'litespeed-cache'); ?>
						<?php echo __('You will be unable to Revert Optimization once the backups are deleted!', 'litespeed-cache'); ?>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_LOSSLESS; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Optimize images using lossless compression.', 'litespeed-cache'); ?>
					<?php echo __('This can improve quality but may result in larger images than lossy compression will.', 'litespeed-cache'); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_EXIF; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo __('Preserve EXIF data (copyright, GPS, comments, keywords, etc) when optimizing.', 'litespeed-cache'); ?>
					<?php echo __('This will increase the size of optimized files.', 'litespeed-cache'); ?>
				</div>
			</td>
		</tr>

		<?php
		if (!is_multisite()) :
			// webp
			require LSCWP_DIR . 'tpl/img_optm/settings.media_webp.tpl.php';

		endif;
		?>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_WEBP_ATTR; ?>
				<?php $this->title($id); ?>
			</th>
			<td>

				<div class="litespeed-textarea-recommended">
					<div>
						<?php $this->build_textarea($id, 40); ?>
					</div>
					<div>
						<?php $this->recommended($id); ?>
					</div>
				</div>

				<div class="litespeed-desc">
					<?php echo __('Specify which element attributes will be replaced with WebP/AVIF.', 'litespeed-cache'); ?>
					<?php echo __('Only attributes listed here will be replaced.', 'litespeed-cache'); ?>
					<br /><?php echo sprintf(__('Use the format %1$s or %2$s (element is optional).', 'litespeed-cache'), '<code>element.attribute</code>', '<code>.attribute</code>'); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_IMG_OPTM_WEBP_REPLACE_SRCSET; ?>
				<?php $this->title($id); ?>
			</th>
			<td>
				<?php $this->build_switch($id); ?>
				<div class="litespeed-desc">
					<?php echo sprintf(__('Enable replacement of WebP/AVIF in %s elements that were generated outside of WordPress logic.', 'litespeed-cache'), '<code>srcset</code>'); ?>
					<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/imageopt/#webp-for-extra-srcset'); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>

<?php

$this->form_end();
