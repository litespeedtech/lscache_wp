<?php
if (!defined('WPINC')) die;

// $current_favicon = get_option( LiteSpeed_Cache_Config::ITEM_FAVICON, array() ) ;

?>
<h3 class="litespeed-title"><?php echo __( 'Advanced', 'litespeed-cache' ) ; ?></h3>

<div class="litespeed-callout-danger">
	<h4><?php echo __('NOTICE:', 'litespeed-cache'); ?></h4>
	<?php echo __('These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache'); ?>
</div>

<table><tbody>
	<tr>
		<th><?php echo __( 'Check Advanced Cache', 'litespeed-cache' ) ; ?></th>
		<td>
		<?php
			$id = LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE;
			$this->build_checkbox($id, __('Include advanced-cache.php', 'litespeed-cache'), $_options[$id]);
		?>
			<div class="litespeed-desc">
				<?php echo __('The advanced-cache.php file is used by many caching plugins to signal that a cache is active.', 'litespeed-cache'); ?>
				<?php echo __('When this option is checked and this file is detected as belonging to another plugin, LiteSpeed Cache will not cache.', 'litespeed-cache'); ?>
			</div>
			<p>
				<i><?php echo __('Uncheck this option only if the other plugin is used for non-caching purposes, such as minifying css/js files.', 'litespeed-cache'); ?></i>
			</p>

		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Login Cookie', 'litespeed-cache' ) ; ?></th>
		<td>
		<?php
			$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE;
			$this->build_input( $id ) ;

			echo '<p>' . __('SYNTAX: alphanumeric and "_".', 'litespeed-cache')
				. ' ' . __('No spaces and case sensitive.', 'litespeed-cache')
				. ' ' . __('MUST BE UNIQUE FROM OTHER WEB APPLICATIONS.', 'litespeed-cache')
				. '</p>'
				. '<p>'
					. sprintf(__('The default login cookie is %s.', 'litespeed-cache'), '_lscache_vary')
					. ' ' . __('The server will determine if the user is logged in based on the existance of this cookie.', 'litespeed-cache')
					. ' ' . __('This setting is useful for those that have multiple web applications for the same domain.', 'litespeed-cache')
					. ' ' . __('If every web application uses the same cookie, the server may confuse whether a user is logged in or not.', 'litespeed-cache')
					. ' ' . __('The cookie set here will be used for this WordPress installation.', 'litespeed-cache')
				. '</p>'
				. '<p>'
					. __('Example use case:', 'litespeed-cache')
					. '<br />'
					. sprintf(__('There is a WordPress installed for %s.', 'litespeed-cache'), '<u>www.example.com</u>')
					. '<br />'
					. sprintf(__('Then another WordPress is installed (NOT MULTISITE) at %s', 'litespeed-cache'), '<u>www.example.com/blog/</u>')
					. ' ' . __('The cache needs to distinguish who is logged into which WordPress site in order to cache correctly.', 'litespeed-cache')
				. '</p>';

			$cookie_rule = LiteSpeed_Cache_Admin_Rules::get_instance()->get_rewrite_rule_login_cookie();
			if ( $cookie_rule && substr($cookie_rule, 0, 11) !== 'Cache-Vary:' ){
				echo '<div class="litespeed-callout-danger">'
						. sprintf(__('Error: invalid login cookie. Please check the %s file', 'litespeed-cache'), '.htaccess')
					. '</div>';
			}

			if ( defined( 'LITESPEED_ON' ) && $_options[$id] ){

				if (!$cookie_rule){
					echo '<div class="litespeed-callout-danger">'
							. sprintf(__('Error getting current rules from %s: %s', 'litespeed-cache'), '.htaccess', LiteSpeed_Cache_Admin_Rules::MARKER_LOGIN_COOKIE)
						. '</div>';
				}
				else{
					$cookie_rule = substr($cookie_rule, 11);
					$cookie_arr = explode(',', $cookie_rule);
					if(!in_array($_options[$id], $cookie_arr)) {
						echo '<div class="litespeed-callout-warning">' .
								__( 'WARNING: The .htaccess login cookie and Database login cookie do not match.', 'litespeed-cache' ) .
							'</div>';
					}
				}

			}

		?>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Improve HTTP/HTTPS Compatibility', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_USE_HTTP_FOR_HTTPS_VARY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enable this option if you are using both HTTP and HTTPS in the same domain and are noticing cache irregularities.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#improve_http_https_compatibility" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>

			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Instant Click', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_ADV_INSTANT_CLICK ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'When a vistor hovers over a page link, preload that page. This will speed up the visit to that link.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:advanced#instant_click" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
				<br /><font class="litespeed-danger">
					<?php echo __('NOTE:', 'litespeed-cache'); ?>
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
			<div class="litespeed-cdn-mapping-block">
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
