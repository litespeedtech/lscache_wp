<?php
if ( ! defined( 'WPINC' ) ) die ;

$home_url = home_url( '/' ) ;
$parsed = parse_url( $home_url ) ;
$home_url = str_replace( $parsed[ 'scheme' ] . ':', '', $home_url ) ;
$cdn_url = 'https://cdn.' . substr( $home_url, 2 ) ;

$cdn_mapping = (array) get_option( LiteSpeed_Cache_Config::ITEM_CDN_MAPPING ) ;


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
					<h4><?php echo __( 'CDN URL', 'litespeed-cache' ) ; ?></h4>
					<?php
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL ;
						$this->build_input( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", 'litespeed-input-long', $v[ $id ] ) ;
					?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'CDN URL to be used. For example, %s', 'litespeed-cache' ), '<code>' . $cdn_url . '</code>' ) ; ?>
					</div>
				</div>
				<div style="flex: 0 0 65%;position:relative;">
					<div class="litespeed-row">
						<?php
							$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ;
							$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", __( 'Include Images', 'litespeed-cache' ), $v[ $id ] ) ;
						?>
						<span class="litespeed-desc">
							<?php echo sprintf( __( 'Serve all image files through the CDN. This will affect all attachments, HTML %s tags, and CSS %s attributes.', 'litespeed-cache' ), '<code>&lt;img</code>', '<code>url()</code>' ) ; ?>
						</span>
					</div>
					<div class="litespeed-row">
						<?php
							$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS ;
							$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", __( 'Include CSS', 'litespeed-cache' ), $v[ $id ] ) ;
						?>
						<span class="litespeed-desc">
							<?php echo __( 'Serve all CSS files through the CDN. This will affect all enqueued WP CSS files.', 'litespeed-cache' ) ; ?>
						</span>
					</div>
					<div class="litespeed-row">
						<?php
							$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS ;
							$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", __( 'Include JS', 'litespeed-cache' ), $v[ $id ] ) ;
						?>
						<span class="litespeed-desc">
							<?php echo __( 'Serve all JavaScript files through the CDN. This will affect all enqueued WP JavaScript files.', 'litespeed-cache' ) ; ?>
						</span>
					</div>
					<div class="litespeed-row" style="display: flex;">
						<?php $id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ; ?>
						<?php $this->build_textarea( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", 17, $v[ $id ] ) ; ?>
						<span class="litespeed-desc">
							<?php echo __( 'Static file type links to be replaced by CDN links.', 'litespeed-cache' ) ; ?>
							<?php echo __('One per line.', 'litespeed-cache'); ?>
							<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cdn#enable_cdn" target="_blank"><?php echo __('Default value', 'litespeed-cache') ; ?></a>
							<br /><?php echo sprintf( __( 'This will affect all tags containing attributes: %s %s %s.', 'litespeed-cache' ), '<code>src=""</code>', '<code>data-src=""</code>', '<code>href=""</code>' ) ; ?>
						</span>
					</div>


					<div style="position: absolute;right:0;bottom:0;">
						<a class="litespeed-btn-danger litespeed-btn-tiny" style="border-radius: 13px;margin: 0;">X</a>
					</div>

				</div>
			</div>

		<?php endforeach ; ?>

		<p><a class="litespeed-btn-success litespeed-btn-tiny" id="litespeed-cdn-mapping-add">+</a></p>

		<div class="litespeed-desc">
			<?php echo __( 'If there are multiple URL with same setting on, the last one will overwrite the others.', 'litespeed-cache' ) ; ?>
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