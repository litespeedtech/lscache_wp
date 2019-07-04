<?php
defined( 'WPINC' ) || exit ;

$this->form_action() ;
?>


<h3 class="litespeed-title-short">
	<?php echo __( 'Image Optimization Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table><tbody>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_AUTO ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Automatically request optimization via cron job.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'Requests can only be sent when recovered credits is %s or more.', 'litespeed-cache' ), '<code>' . LiteSpeed_Cache_Img_Optm::NUM_THRESHOLD_AUTO_REQUEST . '</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_CRON ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Disabling this will stop the cron job responsible for fetching optimized images from LiteSpeed\'s Image Server.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_ORI ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Optimize images and save backups of the originals in the same folder.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_RM_BKUP ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Automatically remove the original image backups after fetching optimized images.', 'litespeed-cache' ) ; ?>

				<br /><font class="litespeed-danger">
					🚨
					<?php echo __( 'This is irreversible.', 'litespeed-cache' ) ; ?>
					<?php echo __( 'You will be unable to Revert Optimization once the backups are deleted!', 'litespeed-cache' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Request WebP versions of original images when doing optimization.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_LOSSLESS ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Optimize images using lossless compression.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve quality but may result in larger images than lossy compression will.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_EXIF ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Preserve EXIF data (copyright, GPS, comments, keywords, etc) when optimizing.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This will increase the size of optimized files.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<?php
		if ( ! is_multisite() ) :
			// webp
			require LSCWP_DIR . 'admin/tpl/img_optm/settings.media_webp.tpl.php' ;

		endif ;
	?>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_ATTR ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 40 ) ; ?>
			<?php $this->recommended( $id, true ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify which element attributes will be replaced with WebP.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Only attributes listed here will be replaced.', 'litespeed-cache' ) ; ?>
				<br /><?php echo sprintf( __( 'Use the format %1$s or %2$s (element is optional).', 'litespeed-cache' ), '<code>element.attribute</code>', '<code>.attribute</code>' ) ; ?>
				<?php LiteSpeed_Cache_Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_REPLACE_SRCSET ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Enable replacement of WebP in %s elements that were generated outside of WordPress logic.', 'litespeed-cache' ), '<code>srcset</code>' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media#webp_for_extra_srcset' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php

$this->form_end() ;



