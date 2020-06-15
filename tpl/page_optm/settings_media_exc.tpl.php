<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Media Excludes', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#media-excludes-tab', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

	<tr>
		<th>
			<?php $id = Base::O_MEDIA_LAZY_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed images will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full URLs and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php Doc::one_per_line() ; ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ) ; ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_media_lazy_img_excludes</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-lazy="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MEDIA_LAZY_CLS_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<div class="litespeed-textarea-recommended">
				<div>
					<?php $this->build_textarea( $id ) ; ?>
				</div>
				<div>
					<?php $this->recommended( $id, true ); ?>
				</div>
			</div>

			<div class="litespeed-desc">
				<?php echo __( 'Images containing these class names will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MEDIA_LAZY_PARENT_CLS_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Images having these parent class names will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MEDIA_IFRAME_LAZY_CLS_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Iframes containing these class names will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Iframes having these parent class names will not be lazy loaded.', 'litespeed-cache' ) ; ?>
				<?php Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_MEDIA_LAZY_URI_EXC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent any lazy load of listed pages.', 'litespeed-cache' ) ; ?>
				<?php $this->_uri_usage_example() ; ?>
			</div>
		</td>
	</tr>

</tbody></table>