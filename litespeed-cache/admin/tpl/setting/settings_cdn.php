<?php
if ( ! defined( 'WPINC' ) ) die ;

$home_url = home_url( '/' ) ;
$parsed = parse_url( $home_url ) ;
$home_url = str_replace( $parsed[ 'scheme' ] . ':', '', $home_url ) ;
$cdn_url = 'https://cdn.' . substr( $home_url, 2 ) ;

?>

<h3 class="litespeed-title"><?php echo __( 'CDN Settings', 'litespeed-cache' ) ; ?></h3>

<table><tbody>
	<tr>
		<th><?php echo __( 'Enable CDN', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enable Content Delivery Network use.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'CDN URL', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CDN_URL, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'CDN URL to be used. For example, %s', 'litespeed-cache' ), '<code>' . $cdn_url . '</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Original URL', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CDN_ORI, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Site URL to be served through the CDN. Beginning with %1$s. For example, %2$s.', 'litespeed-cache' ), '<code>//</code>', '<code>' . $home_url . '</code>' ) ; ?>
				<br /><?php echo sprintf( __( 'Wildcard %1$s supported (match zero or more characters). For example, to match %2$s and %3$s, use %4$s.', 'litespeed-cache' ), '<code>*</code>', '<code>//www.aa.com</code>', '<code>//aa.com</code>', '<code>//*aa.com</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Include Images', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_INC_IMG ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Serve all image files through the CDN. This will affect all attachments, HTML %s tags, and CSS %s attributes.', 'litespeed-cache' ), '<code>&lt;img</code>', '<code>url()</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Include CSS', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_INC_CSS ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Serve all CSS files through the CDN. This will affect all enqueued WP CSS files.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Include JS', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_INC_JS ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Serve all JavaScript files through the CDN. This will affect all enqueued WP JavaScript files.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Include File Types', 'litespeed-cache' ) ; ?></th>
		<td>
			<div style="float:left; margin-right: 125px;">
				<?php $id = LiteSpeed_Cache_Config::OPID_CDN_FILETYPE ; ?>
				<?php $this->build_textarea( $id, null, false, 30 ) ; ?>
				<div class="litespeed-desc">
					<?php echo __( 'Static file type links to be replaced by CDN links. One per line.', 'litespeed-cache' ) ; ?>
					<br /><?php echo sprintf( __( 'This will affect all tags containing attributes: %s %s %s.', 'litespeed-cache' ), '<code>src=""</code>', '<code>data-src=""</code>', '<code>href=""</code>' ) ; ?>
				</div>
			</div>
			<div style="float:left; display:flex;">
				<div style="display: flex; margin-right: 50px;">
					<?php $this->recommended($id) ; ?>
				</div>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Exclude Path', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_CDN_EXCLUDE ; ?>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Paths containing these strings will not be served from the CDN.', 'litespeed-cache' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Load JQuery Remotely', 'litespeed-cache' ) ; ?></th>
		<td>
			<div class="litespeed-switch">
				<?php echo $this->build_radio(
					LiteSpeed_Cache_Config::OPID_CDN_REMOTE_JQUERY,
					LiteSpeed_Cache_Config::VAL_OFF,
					__( 'Off', 'litespeed-cache' )
				) ; ?>

				<?php echo $this->build_radio(
					LiteSpeed_Cache_Config::OPID_CDN_REMOTE_JQUERY,
					LiteSpeed_Cache_Config::VAL_ON,
					__( 'Google', 'litespeed-cache' )
				) ; ?>

				<?php echo $this->build_radio(
					LiteSpeed_Cache_Config::OPID_CDN_REMOTE_JQUERY,
					LiteSpeed_Cache_Config::VAL_ON2,
					__( 'cdnjs', 'litespeed-cache' )
				) ; ?>
			</div>
			<div class="litespeed-desc">
				<?php echo __( 'Improve page load time by loading jQuery from a remote CDN service instead of locally.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>