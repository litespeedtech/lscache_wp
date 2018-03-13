<?php
if ( ! defined( 'WPINC' ) ) die ;

$home_url = home_url( '/' ) ;
$parsed = parse_url( $home_url ) ;
$home_url = str_replace( $parsed[ 'scheme' ] . ':', '', $home_url ) ;
$cdn_url = 'https://cdn.' . substr( $home_url, 2 ) ;

$cdn_mapping = get_option( LiteSpeed_Cache_Config::ITEM_CDN_MAPPING, array() ) ;
if ( ! $cdn_mapping ) {
	// generate one by default
	$cdn_mapping = array( array(
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL => '',
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG => false,
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS => false,
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS => false,
		LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE =>  ".aac\n.css\n.eot\n.gif\n.jpeg\n.js\n.jpg\n.less\n.mp3\n.mp4\n.ogg\n.otf\n.pdf\n.png\n.svg\n.ttf\n.woff",
	) ) ;
}

?>

<h3 class="litespeed-title-short">
	<?php echo __('CDN Settings', 'litespeed-cache'); ?>
	<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cdn" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
</h3>

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

			<div class="litespeed-block" data-litespeed-cdn-mapping="1">
				<div class='litespeed-cdn-mapping-col1'>
					<h4><?php echo __( 'CDN URL', 'litespeed-cache' ) ; ?>
						<button type="button" class="litespeed-btn-danger" data-litespeed-cdn-mapping-del="1">X</button>
					</h4>

					<?php
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL ;
						$this->build_input( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", 'litespeed-input-long', $v[ $id ] ) ;
					?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'CDN URL to be used. For example, %s', 'litespeed-cache' ), '<code>' . $cdn_url . '</code>' ) ; ?>
					</div>
				</div>

				<div class='litespeed-cdn-mapping-col2'>
					<div class="litespeed-row">
						<div class="litespeed-cdn-mapping-inc"><?php echo __( 'Include Images', 'litespeed-cache' ) ; ?></div>
					<?php
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG ;
						$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", ! empty( $v[ $id ] ) ? true : false ) ;
					?>
					</div>
					<div class="litespeed-row">
						<div class="litespeed-cdn-mapping-inc"><?php echo __( 'Include CSS', 'litespeed-cache' ) ; ?></div>
					<?php
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS ;
						$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", ! empty( $v[ $id ] ) ? true : false ) ;
					?>
					</div>
					<div class="litespeed-row">
						<div class="litespeed-cdn-mapping-inc"><?php echo __( 'Include JS', 'litespeed-cache' ) ; ?></div>
					<?php
						$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS ;
						$this->build_toggle( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", ! empty( $v[ $id ] ) ? true : false ) ;
					?>
					</div>
				</div>

				<div class='litespeed-cdn-mapping-col3'>
					<div class="litespeed-row">
						<div class="litespeed-cdn-mapping-col3-title"><?php echo __( 'Include File Types', 'litespeed-cache' ) ; ?></div>
						<?php $id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ; ?>
						<?php $this->build_textarea( "[" . LiteSpeed_Cache_Config::ITEM_CDN_MAPPING . "][$id][]", 17, $v[ $id ] ) ; ?>
					</div>
				</div>
			</div>

		<?php endforeach ; ?>

			<p><button type="button" class="litespeed-btn-success litespeed-btn-tiny" id="litespeed-cdn-mapping-add">+</button></p>

			<div class="litespeed-warning">
				<?php echo __('NOTE', 'litespeed-cache'); ?>:
				<?php echo __( 'To randomize CDN hostname, define multiple hostnames for the same resources.', 'litespeed-cache' ) ; ?>
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
				<b><?php echo __( 'Include File Types', 'litespeed-cache' ) ; ?></b>:
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
				<br /><?php echo sprintf( __( 'Separate multiple original URLs with a %s.', 'litespeed-cache' ), '<code>,</code>' ) ; ?>
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
					'Google'
				) ; ?>

				<?php echo $this->build_radio(
					LiteSpeed_Cache_Config::OPID_CDN_REMOTE_JQUERY,
					LiteSpeed_Cache_Config::VAL_ON2,
					'Cdnjs'
				) ; ?>
			</div>
			<div class="litespeed-desc">
				<?php echo __( 'Improve page load time by loading jQuery from a remote CDN service instead of locally.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr class="litespeed-hide">
		<th><?php echo __( 'Quic Cloud API', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_QUIC ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Use %s API functionality.', 'litespeed-cache' ), 'Quic Cloud' ) ; ?>

				<a id='litespeed_modal_href' href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_CDN_QUICCLOUD ) ; ?>">
					<?php if ( ! empty( $_options[ LiteSpeed_Cache_Config::OPID_CDN_QUIC_EMAIL ] ) ) : ?>
						Login API
					<?php else : ?>
						Free Register!
					<?php endif ; ?>
				</a>

				<link rel="stylesheet" href="<?php echo LSWCP_PLUGIN_URL ; ?>css/iziModal.min.css">
				<script type="text/javascript" src="<?php echo LSWCP_PLUGIN_URL ; ?>js/iziModal.min.js"></script>
				<div id="litespeed_modal"></div>
				<script type="text/javascript">
					var litespeed_modal = jQuery("#litespeed_modal").iziModal({iframe: true});
					jQuery("#litespeed_modal_href").click(function(event) {
						event.preventDefault();
						litespeed_modal.iziModal('open', event);
					});;
				</script>
				<?php

				?>
				<?php echo sprintf( __( 'This can be managed from <a %2$s>%1$s</a>.', 'litespeed-cache' ), '<b>' . __( 'Manage', 'litespeed-cache' ) . '</b> -&gt; <b>' . __( 'CDN', 'litespeed-cache' ) . '</b>', 'href="admin.php?page=lscache-dash#cdn"' ) ; ?>
			</div>
			<div class="litespeed-block">
				<div class='litespeed-col'>
					<h4><?php echo __( 'Email Address', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CDN_QUIC_EMAIL ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Your Email address on %s.', 'litespeed-cache' ), 'Quic Cloud' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col'>
					<h4><?php echo __( 'User API Key', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CDN_QUIC_KEY ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Your API key is used to access %s APIs.', 'litespeed-cache' ), 'Quic Cloud' ) ; ?>
						<?php echo sprintf( __( 'Get it from <a %1$s>%2$s</a>.', 'litespeed-cache' ), 'href="https://quic.cloud/dashboard" target="_blank"', 'Quic Cloud' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col'>
					<h4><?php echo __( 'Site Domain', 'litespeed-cache' ) ; ?></h4>

				<?php
					$this->build_input( LiteSpeed_Cache_Config::OPID_CDN_QUIC_SITE ) ;
				?>
					<div class="litespeed-desc">
						<?php echo __( 'You can just type part of the domain.', 'litespeed-cache' ) ; ?>
						<?php echo __( 'Once saved, it will be matched with the current list and completed automatically.', 'litespeed-cache' ) ; ?>
					</div>
				</div>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Cloudflare API', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Use %s API functionality.', 'litespeed-cache' ), 'Cloudflare' ) ; ?>
				<?php echo sprintf( __( 'This can be managed from <a %2$s>%1$s</a>.', 'litespeed-cache' ), '<b>' . __( 'Manage', 'litespeed-cache' ) . '</b> -&gt; <b>' . __( 'CDN', 'litespeed-cache' ) . '</b>', 'href="admin.php?page=lscache-dash#cdn"' ) ; ?>
			</div>
			<div class="litespeed-block">
				<div class='litespeed-col'>
					<h4><?php echo __( 'Email Address', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_EMAIL ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Your Email address on %s.', 'litespeed-cache' ), 'Cloudflare' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col'>
					<h4><?php echo __( 'Global API Key', 'litespeed-cache' ) ; ?></h4>

					<?php $this->build_input( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_KEY ) ; ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'Your API key is used to access %s APIs.', 'litespeed-cache' ), 'Cloudflare' ) ; ?>
						<?php echo sprintf( __( 'Get it from <a %1$s>%2$s</a>.', 'litespeed-cache' ), 'href="https://www.cloudflare.com/a/profile" target="_blank"', 'Cloudflare' ) ; ?>
					</div>
				</div>

				<div class='litespeed-col'>
					<h4><?php echo __( 'Domain', 'litespeed-cache' ) ; ?></h4>

				<?php
					$cf_zone = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_ZONE ) ;
					$cls = 	$cf_zone ? ' litespeed-input-success' : ' litespeed-input-warning' ;
					$this->build_input( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_NAME, $cls ) ;
				?>
					<div class="litespeed-desc">
						<?php echo __( 'You can just type part of the domain.', 'litespeed-cache' ) ; ?>
						<?php echo __( 'Once saved, it will be matched with the current list and completed automatically.', 'litespeed-cache' ) ; ?>
					</div>
				</div>
			</div>
		</td>
	</tr>

</tbody></table>