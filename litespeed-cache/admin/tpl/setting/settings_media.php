<?php
defined( 'WPINC' ) || exit ;

$last_responsive_placeholder_generated = LiteSpeed_Cache_Media::get_summary() ;

?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Media Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table><tbody>
	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_LAZY ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load images only when they enter the viewport.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-success">
					💡:
					<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media:lazy-load-style" target="_blank"><?php echo __('Adding Style to Your Lazy-Loaded Images', 'litespeed-cache') ; ?></a>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_LAZY_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed images will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full URLs and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php LiteSpeed_Cache_Doc::one_per_line() ; ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ) ; ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_cache_media_lazy_img_excludes</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-lazy="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_LAZY_CLS_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Images containing these class names will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php LiteSpeed_Cache_Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_LAZY_PLACEHOLDER ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify a base64 image to be used as a placeholder while other images finish loading.', 'litespeed-cache' ) ; ?>
				<br /><?php echo sprintf( __( 'This can be predefined in %2$s as well using constant %1$s, with this setting taking priority.', 'litespeed-cache' ), '<code>LITESPEED_PLACEHOLDER</code>', '<code>wp-config.php</code>' ) ; ?>
				<br /><?php echo sprintf( __( 'By default a gray image placeholder %s will be used.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=</code>' ) ; ?>
				<br /><?php echo sprintf( __( 'For example, %s can be used for a transparent placeholder.', 'litespeed-cache' ), '<code>data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Responsive image placeholders can help to reduce layout reshuffle when images are loaded.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This will generate the placeholder with same dimensions as the image if it has the width and height attributes.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_COLOR ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id, null, null, 'color' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the placeholder color you want to use.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_ASYNC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Automatically generate %s in the background via a cron-based queue.', 'litespeed-cache' ), __( 'Responsive Placeholder', 'litespeed-cache' ) ) ; ?>
				<?php echo sprintf(
					__( 'If set to %1$s, before the placeholder is localized, the %2$s configuration will be used.', 'litespeed-cache' ),
					'<code>' . __('ON', 'litespeed-cache') . '</code>',
					__( 'Lazy Load Image Placeholder', 'litespeed-cache' )
				) ; ?>
				<?php echo sprintf( __( 'If set to %s this is done in the foreground, which may slow down page load.', 'litespeed-cache' ), '<code>' . __('OFF', 'litespeed-cache') . '</code>' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media#generate_responsive_placeholder' ) ; ?>
			</div>

			<?php if ( $last_responsive_placeholder_generated ) : ?>
			<div class="litespeed-desc litespeed-left20">
				<?php if ( ! empty( $last_responsive_placeholder_generated[ 'last_request' ] ) ) : ?>
					<p>
						<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . LiteSpeed_Cache_Utility::readable_time( $last_responsive_placeholder_generated[ 'last_request' ] ) . '</code>' ; ?>
					</p>
				<?php endif ; ?>
				<?php if ( ! empty( $last_responsive_placeholder_generated[ 'queue' ] ) ) : ?>
					<div class="litespeed-callout-warning">
						<h4><?php echo __( 'Size list in queue waiting for cron','litespeed-cache' ) ; ?></h4>
						<p>
							<?php echo implode( ' ', $last_responsive_placeholder_generated[ 'queue' ] ) ; ?>
						</p>
					</p>
					<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_MEDIA, LiteSpeed_Cache_Media::TYPE_GENERATE_PLACEHOLDER ) ; ?>" class="litespeed-btn-success">
						<?php echo __( 'Run Queue Manually', 'litespeed-cache' ) ; ?>
					</a>
				<?php endif ; ?>
			</div>
			<?php endif ; ?>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_IFRAME_LAZY ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load iframes only when they enter the viewport.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve page loading time by reducing initial HTTP requests.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_MEDIA_LAZYJS_INLINE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Include the lazy load image Javascript library inline.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media#inline_lazy_load_images_library' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>
