<?php
if ( ! defined( 'WPINC' ) ) die ;

$home_url = home_url( '/' ) ;
$parsed = parse_url( $home_url ) ;
$home_url = str_replace( $parsed[ 'scheme' ] . ':', '', $home_url ) ;
$cdn_url = 'https://cdn.' . substr( $home_url, 2 ) ;

$cdn_mapping = (array) get_option( LiteSpeed_Cache_Config::ITEM_CDN_MAPPING ) ;
if ( ! $cdn_mapping ) {
	// generate one by default
	$cdn_mapping = array( array(
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL => '',
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG => false,
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS => false,
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS => false,
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE => '',
	) ) ;
}

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
		<th><?php echo __( 'CDN Mapping', 'litespeed-cache' ) ; ?></th>
		<td>
		<?php foreach ( $cdn_mapping as $v ) : ?>

			<div style="border: 1px dotted #28a745;border-radius:16px; display: flex;padding: 10px;margin-bottom: 5px;" data-litespeed-cdn-mapping="1">
				<div style="flex: 0 0 35%;">
					<h4 style="position:relative;"><?php echo __( 'CDN URL', 'litespeed-cache' ) ; ?>
						<span style="position: absolute;right:0;top:0;">
							<button class="litespeed-btn-danger litespeed-btn-tiny" style="border-radius: 13px;margin: 0;" data-litespeed-cdn-mapping-del="1">X</button>
						</span>
					</h4>

					<?php
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL ;
						$this->build_input( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", 'litespeed-input-long', $v[ $id ] ) ;
					?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'CDN URL to be used. For example, %s', 'litespeed-cache' ), '<code>' . $cdn_url . '</code>' ) ; ?>
					</div>
				</div>

				<div style="flex: 0 0 45%;">
					<div class="litespeed-row">
					<?php
						echo __( 'Include Images', 'litespeed-cache' ) ;
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ;
						$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", ! empty( $v[ $id ] ) ? true : false ) ;
					?>
					</div>
					<div class="litespeed-row">
					<?php
						echo __( 'Include CSS', 'litespeed-cache' ) ;
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS ;
						$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", ! empty( $v[ $id ] ) ? true : false ) ;
					?>
					</div>
					<div class="litespeed-row">
					<?php
						echo __( 'Include JS', 'litespeed-cache' ) ;
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS ;
						$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", ! empty( $v[ $id ] ) ? true : false ) ;
					?>
					</div>
				</div>

				<div style="flex: 0 0 20%;">
					<?php echo __( 'Include File Types', 'litespeed-cache' ) ; ?>
					<?php $id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ; ?>
					<?php $this->build_textarea( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", 17, $v[ $id ] ) ; ?>
				</div>
			</div>

		<?php endforeach ; ?>

			<p><button class="litespeed-btn-success litespeed-btn-tiny" id="litespeed-cdn-mapping-add">+</button></p>

			<div class="litespeed-warning">
				<?php echo __('NOTE:', 'litespeed-cache'); ?>
				<?php echo __( 'If multiple CDN paths are configured with the same settings, the last one will overwrite the others.', 'litespeed-cache' ) ; ?>
			</div>

			<div class="litespeed-desc">
				<b><?php echo __( 'Include Images', 'litespeed-cache' ) ; ?></b>:
				<?php echo sprintf( __( 'Serve all image files through the CDN. This will affect all attachments, HTML %s tags, and CSS %s attributes.', 'litespeed-cache' ), '<code>&lt;img</code>', '<code>url()</code>' ) ; ?>

				<br />
				<b><?php echo __( 'Include CSS', 'litespeed-cache' ) ; ?></b>:
				<?php echo __( 'Serve all CSS files through the CDN. This will affect all enqueued WP CSS files.', 'litespeed-cache' ) ; ?>

				<br />
				<b><?php echo __( 'Include JS', 'litespeed-cache' ) ; ?></b>:
				<?php echo __( 'Serve all JavaScript files through the CDN. This will affect all enqueued WP JavaScript files.', 'litespeed-cache' ) ; ?>

				<br />
				<b><?php echo __( 'Include File Types', 'litespeed-cache' ) ; ?></b>
				<?php echo __( 'Static file type links to be replaced by CDN links.', 'litespeed-cache' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
				<?php echo sprintf( __( 'This will affect all tags containing attributes: %s %s %s.', 'litespeed-cache' ), '<code>src=""</code>', '<code>data-src=""</code>', '<code>href=""</code>' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cdn#include_file_types" target="_blank"><?php echo __('Default value', 'litespeed-cache') ; ?></a>
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