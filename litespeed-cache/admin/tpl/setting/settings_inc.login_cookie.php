<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

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
					. sprintf(__('The default login cookie is %s.', 'litespeed-cache'), '<code>_lscache_vary</code>')
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

