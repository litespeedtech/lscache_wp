<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Media Settings', 'litespeed-cache'); ?>
	<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
</h3>

<table><tbody>
	<tr>
		<th><?php echo __( 'Lazy Load Images', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load images only when they enter the viewport.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-success">
					<?php echo __('Tip', 'litespeed-cache'); ?>:
					<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media:lazy-load-style" target="_blank"><?php echo __('Adding Style to Your Lazy-Loaded Images', 'litespeed-cache') ; ?></a>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Lazy Load Image Excludes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_MEDIA_LAZY_IMG_EXC ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed images will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full URLs and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
				<br /><font class="litespeed-success">
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_cache_media_lazy_img_excludes</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-lazy="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Lazy Load Image Placeholder', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_input( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY_PLACEHOLDER, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify a base64 image to be used as a placeholder while other images finish loading.', 'litespeed-cache' ) ; ?>
				<br /><?php echo sprintf( __( 'This can be predefined in %2$s as well using constant %1$s, with this setting taking priority.', 'litespeed-cache' ), '<code>LITESPEED_PLACEHOLDER</code>', '<code>wp-config.php</code>' ) ; ?>
				<br /><?php echo sprintf( __( 'By default a gray image placeholder %s will be used.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=</code>' ) ; ?>
				<br /><?php echo sprintf( __( 'For example, %s can be used for a transparent placeholder.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Lazy Load Iframes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IFRAME_LAZY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load iframes only when they enter the viewport.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Disable Optimization Pull', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_OPTM_CRON_OFF ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Set this option to %s to disable the cron job responsible for fetching optimized images from LiteSpeed\'s Image Server.', 'litespeed-cache' ), __( 'ON', 'litespeed-cache' ) ) ; ?>
			</div>
		</td>
	</tr>

	<?php
		if ( ! is_multisite() ) :
			// webp
			require LSCWP_DIR . 'admin/tpl/setting/settings_inc.media_webp.php' ;

		endif ;
	?>

	<tr>
		<th><?php echo __( 'Only Request WebP', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_ONLY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent images from being replaced with optimized versions when optimizing. WebP versions will still be generated.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Preserve EXIF data', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_EXIF ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Preserve EXIF data (copyright, GPS, comments, keywords, etc) when optimizing.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This will increase the size of optimized files.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr class="litespeed-hide">
		<th><?php echo __( 'WebP Lossless Compression', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_LOSSLESS ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Generate WebP images using lossless compression.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve quality at the cost of larger images.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>


</tbody></table>