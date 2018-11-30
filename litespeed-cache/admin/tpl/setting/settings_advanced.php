<?php
if (!defined('WPINC')) die;

// $current_favicon = get_option( LiteSpeed_Cache_Config::ITEM_FAVICON, array() ) ;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Advanced Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced', false, 'litespeed-learn-more' ) ; ?>
</h3>

<div class="litespeed-callout-danger">
	<h4><?php echo __('NOTICE:', 'litespeed-cache'); ?></h4>
	<?php echo __('These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache'); ?>
</div>

<table><tbody>

	<?php
		if ( ! is_multisite() ) :
			require LSCWP_DIR . 'admin/tpl/setting/settings_inc.cache_object.php' ;
			require LSCWP_DIR . 'admin/tpl/setting/settings_inc.cache_browser.php' ;

			require LSCWP_DIR . 'admin/tpl/setting/settings_inc.check_adv_file.php' ;
			require LSCWP_DIR . 'admin/tpl/setting/settings_inc.login_cookie.php' ;
		endif ;
	?>

	<tr>
		<th><?php echo __( 'Purge All Hooks', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::ITEM_ADV_PURGE_ALL_HOOKS ; ?>
			<?php $this->build_textarea2( $id, 50 ) ; ?>
			<?php $this->recommended( $id, true ) ; ?>

			<div class="litespeed-desc">
				<?php echo __( 'A Purge All will be executed when WordPress runs these hooks.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#hooks_to_purge_all' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Improve HTTP/HTTPS Compatibility', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_USE_HTTP_FOR_HTTPS_VARY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enable this option if you are using both HTTP and HTTPS in the same domain and are noticing cache irregularities.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#improve_http_https_compatibility' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Instant Click', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_ADV_INSTANT_CLICK ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'When a vistor hovers over a page link, preload that page. This will speed up the visit to that link.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#instant_click' ) ; ?>
				<br /><font class="litespeed-danger">
					⚠️
					<?php echo __('This will generate extra requests to the server, which will increase server load.', 'litespeed-cache'); ?>
				</font>

			</div>
		</td>
	</tr>

	<?php /*
	<tr>
		<th><?php echo __( 'Favicon', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_ADV_FAVICON ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enable this option to use uploaded image as favicon.ico.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#favicon" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>

			</div>
			<div class="litespeed-block">
				<div class='litespeed-cdn-mapping-col1'>
					<h4><?php echo __( 'Frontend Favicon File', 'litespeed-cache' ) ; ?></h4>

					<input type="file" name="litespeed-file-favicon_frontend" class="litespeed-input-long">
					<div class="litespeed-desc">
						<a href="https://favicon.io/converter?hello=litespeed" target="_blank"><?php echo __( 'A sample online favicon generator.', 'litespeed-cache' ) ; ?></a>
					</div>
				</div>

				<div class='litespeed-cdn-mapping-col litespeed-abs-center'>
					<?php
					if ( ! empty( $current_favicon[ 'frontend' ] ) ) {
						echo "
							<img src='$current_favicon[frontend]' style='max-height:200px;max-width:200px;' />
						";
					}
					?>
				</div>
				<div class='litespeed-cdn-mapping-col1'>
					<h4><?php echo __( 'Backend Favicon File', 'litespeed-cache' ) ; ?></h4>

					<input type="file" name="litespeed-file-favicon_backend" class="litespeed-input-long">
				</div>

				<div class='litespeed-cdn-mapping-col litespeed-abs-center'>
					<?php
					if ( ! empty( $current_favicon[ 'backend' ] ) ) {
						echo "
							<img src='$current_favicon[backend]' style='max-height:200px;max-width:200px;' />
						";
					}
					?>
				</div>
			</div>

		</td>
	</tr>
	*/ ?>

</tbody></table>
