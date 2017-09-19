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
				<?php echo __( 'Enabling this option will enable Content Delivery Network for your site.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Original URL', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CDN_ORI, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'The URL of your site to be replaced. Beginning with %1$s. For example, %2$s', 'litespeed-cache' ), '<code>//</code>', '<code>' . $home_url . '</code>' ) ; ?>
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
		<th><?php echo __( 'Include Images', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_INC_IMG ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Include all images in CDN. This will affect all attachements, and HTML tag %s or CSS attribute %s.', 'litespeed-cache' ), '<code>&lt;img</code>', '<code>url()</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Include CSS', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_INC_CSS ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Include all CSS in CDN. This will affect all enqueued WP CSS.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Include JS', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_INC_JS ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Include all JavaScript in CDN. This will affect all enqueued WP JavaScript.', 'litespeed-cache' ) ; ?>
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
					<p><?php echo __( 'The static file types that will be replaced to CDN links. One per line.', 'litespeed-cache' ) ; ?></p>
					<p><?php echo sprintf( __( 'This will affect all tags containing attributes : %s %s %s.', 'litespeed-cache' ), '<code>src=""</code>', '<code>data-src=""</code>', '<code>href=""</code>' ) ; ?></p>
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
				<?php echo __( 'The pathes containing these strings will be excluded from CDN.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>