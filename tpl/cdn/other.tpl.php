<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
$home_url = home_url( '/' );
$parsed   = parse_url( $home_url );
$home_url = str_replace( $parsed['scheme'] . ':', '', $home_url );

$cdn_mapping = $this->conf( Base::O_CDN_MAPPING );
// Special handler: Append one row if somehow the DB default preset value got deleted
if ( ! $cdn_mapping ) {
	$this->load_default_vals();
	$cdn_mapping = self::$_default_options[ Base::O_CDN_MAPPING ];
}

$this->form_action();
?>
<h3 class="litespeed-title-short">
	<?php echo __( 'CDN Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cdn/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $id = Base::O_CDN; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php printf( __( 'Turn this setting %1$s if you are using a traditional Content Delivery Network (CDN) or a subdomain for static content with QUIC.cloud CDN.', 'litespeed-cache' ), '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cdn/#use-cdn-mapping' ); ?>
				</div>

				<div class="litespeed-desc">
					<?php printf( __( 'NOTE: QUIC.cloud CDN and Cloudflare do not use CDN Mapping. If you are are only using QUIC.cloud or Cloudflare, leave this setting %1$s.', 'litespeed-cache' ), '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th class="litespeed-padding-left"></th>
			<td>
				<?php $this->enroll( Base::O_CDN_MAPPING . '[' . Base::CDN_MAPPING_URL . '][]' ); ?>
				<?php $this->enroll( Base::O_CDN_MAPPING . '[' . Base::CDN_MAPPING_INC_IMG . '][]' ); ?>
				<?php $this->enroll( Base::O_CDN_MAPPING . '[' . Base::CDN_MAPPING_INC_CSS . '][]' ); ?>
				<?php $this->enroll( Base::O_CDN_MAPPING . '[' . Base::CDN_MAPPING_INC_JS . '][]' ); ?>
				<?php $this->enroll( Base::O_CDN_MAPPING . '[' . Base::CDN_MAPPING_FILETYPE . '][]' ); ?>

				<div id="litespeed_cdn_mapping_div"></div>

				<script type="text/babel">
					ReactDOM.render(
					<CDNMapping list={ <?php echo json_encode( $cdn_mapping ); ?> } />,
					document.getElementById( 'litespeed_cdn_mapping_div' )
				);
			</script>

				<div class="litespeed-warning">
					<?php echo __( 'NOTE', 'litespeed-cache' ); ?>:
					<?php echo __( 'To randomize CDN hostname, define multiple hostnames for the same resources.', 'litespeed-cache' ); ?>
				</div>

				<div class="litespeed-desc">
					<b><?php $this->title( Base::CDN_MAPPING_INC_IMG ); ?></b>:
					<?php printf( __( 'Serve all image files through the CDN. This will affect all attachments, HTML %1$s tags, and CSS %2$s attributes.', 'litespeed-cache' ), '<code>&lt;img</code>', '<code>url()</code>' ); ?>

					<br />
					<b><?php $this->title( Base::CDN_MAPPING_INC_CSS ); ?></b>:
					<?php echo __( 'Serve all CSS files through the CDN. This will affect all enqueued WP CSS files.', 'litespeed-cache' ); ?>

					<br />
					<b><?php $this->title( Base::CDN_MAPPING_INC_JS ); ?></b>:
					<?php echo __( 'Serve all JavaScript files through the CDN. This will affect all enqueued WP JavaScript files.', 'litespeed-cache' ); ?>

					<br />
					<b><?php $this->title( Base::CDN_MAPPING_FILETYPE ); ?></b>:
					<?php echo __( 'Static file type links to be replaced by CDN links.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
					<?php printf( __( 'This will affect all tags containing attributes: %1$s %2$s %3$s.', 'litespeed-cache' ), '<code>src=""</code>', '<code>data-src=""</code>', '<code>href=""</code>' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cdn/#include-file-types', __( 'Default value', 'litespeed-cache' ) ); ?>

					<br />
					<?php printf( __( 'If you turn any of the above settings OFF, please remove the related file types from the %s box.', 'litespeed-cache' ), '<b>' . __( 'Include File Types', 'litespeed-cache' ) . '</b>' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cdn/#include-file-types' ); ?>
				</div>

			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_CDN_ATTR; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>

				<div class="litespeed-textarea-recommended">
					<div>
						<?php $this->build_textarea( $id, 40 ); ?>
					</div>
					<div>
						<?php $this->recommended( $id ); ?>
					</div>
				</div>

				<div class="litespeed-desc">
					<?php echo __( 'Specify which HTML element attributes will be replaced with CDN Mapping.', 'litespeed-cache' ); ?>
					<?php echo __( 'Only attributes listed here will be replaced.', 'litespeed-cache' ); ?>
					<br /><?php printf( __( 'Use the format %1$s or %2$s (element is optional).', 'litespeed-cache' ), '<code>element.attribute</code>', '<code>.attribute</code>' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th class="litespeed-padding-left">
				<?php $id = Base::O_CDN_ORI; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $id ); ?>
				<div class="litespeed-desc">
					<?php printf( __( 'Site URL to be served through the CDN. Beginning with %1$s. For example, %2$s.', 'litespeed-cache' ), '<code>//</code>', '<code>' . $home_url . '</code>' ); ?>
					<br /><?php printf( __( 'Wildcard %1$s supported (match zero or more characters). For example, to match %2$s and %3$s, use %4$s.', 'litespeed-cache' ), '<code>*</code>', '<code>//www.aa.com</code>', '<code>//aa.com</code>', '<code>//*aa.com</code>' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th class="litespeed-padding-left">
				<?php $id = Base::O_CDN_ORI_DIR; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<div class="litespeed-textarea-recommended">
					<div>
						<?php $this->build_textarea( $id, 40 ); ?>
					</div>
					<div>
						<?php $this->recommended( $id ); ?>
					</div>
				</div>

				<div class="litespeed-desc">
					<?php echo __( 'Only files within these directories will be pointed to the CDN.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th class="litespeed-padding-left">
				<?php $id = Base::O_CDN_EXC; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Paths containing these strings will not be served from the CDN.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>

<?php
$this->form_end();
