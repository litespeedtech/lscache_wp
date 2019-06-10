<?php defined( 'WPINC' ) || exit ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Advanced Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced', false, 'litespeed-learn-more' ) ; ?>
</h3>

<div class="litespeed-callout-danger">
	<h4><?php echo __( 'NOTICE:', 'litespeed-cache' ); ?></h4>
	<?php echo __( 'These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache' ) ; ?>
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
			<?php $id = LiteSpeed_Cache_Config::O_PURGE_HOOK_ALL ; ?>
			<?php $this->build_textarea( $id, 50 ) ; ?>
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
			<?php $this->build_switch( LiteSpeed_Cache_Config::O_UTIL_NO_HTTPS_VARY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enable this option if you are using both HTTP and HTTPS in the same domain and are noticing cache irregularities.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#improve_http_https_compatibility' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Instant Click', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::O_UTIL_INSTANT_CLICK ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'When a vistor hovers over a page link, preload that page. This will speed up the visit to that link.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#instant_click' ) ; ?>
				<br /><font class="litespeed-danger">
					⚠️
					<?php echo __( 'This will generate extra requests to the server, which will increase server load.', 'litespeed-cache' ) ; ?>
				</font>

			</div>
		</td>
	</tr>

</tbody></table>
