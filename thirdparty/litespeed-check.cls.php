<?php
/**
 * Check if certain plugin that could conflict with LiteSpeed Cache is activated.
 * @since		4.2
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class LiteSpeed_Check {

	public static function detect() {

		if ( ! is_admin() ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( isset( $_GET[ 'lscwp_temp_warning_plugin' ] ) ) {
			add_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_plugin', 'true', true );
		}

		if ( isset( $_GET[ 'lscwp_temp_warning_ssl' ] ) ) {
			add_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_ssl', 'true', true );
		}

		if ( ! get_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_plugin' ) ) {
			session_start();

			$plugin_list =
				array_map(
					function( $plugin ) { return WP_PLUGIN_DIR . '/' . $plugin; },
					[
						'autoptimize/autoptimize.php',
						'breeze/breeze.php',
						'cache-enabler/cache-enabler.php',
						'cachify/cachify.php',
						'cloudflare/cloudflare.php',
						'comet-cache/comet-cache.php',
						'docket-cache/docket-cache.php',
						'fast-velocity-minify/fvm.php',
						'hummingbird-performance/wp-hummingbird.php',
						'nginx-cache/nginx-cache.php',
						'nitropack/main.php',
						'pantheon-advanced-page-cache/pantheon-advanced-page-cache.php',
						'powered-cache/powered-cache.php',
						'sg-cachepress/sg-cachepress.php',
						'simple-cache/simple-cache.php',
						'redis-cache/redis-cache.php',
						'w3-total-cache/w3-total-cache.php',
						'wp-cloudflare-page-cache/wp-cloudflare-page-cache.php',
						'wp-fastest-cache/wpFastestCache.php',
						'wp-meteor/wp-meteor.php',
						'wp-optimize/wp-optimize.php',
						'wp-performance-score-booster/wp-performance-score-booster.php',
						'wp-rocket/wp-rocket.php',
						'wp-super-cache/wp-cache.php',
					]
				);

			$_SESSION[ 'lscwp_temp_warning' ] =
				array_map(
					function( $plugin ) {
						return get_plugin_data( $plugin, false, true )[ 'Name' ];
					},
					array_intersect( $plugin_list, wp_get_active_and_valid_plugins() )
				);

			if ( ! empty( $_SESSION[ 'lscwp_temp_warning' ] ) ) {
				add_action( 'admin_notices', function() {
					?>
					<div class="notice notice-error is-dismissible">
						<p><?php
							esc_html_e(
								'Please consider disabling the following detected plugins, as they may conflict with LiteSpeed Cache:',
								'litespeed-cache'
							);
						?>
							<br>
							<p style="color: red;"><?php
								echo implode( ', ', $_SESSION[ 'lscwp_temp_warning' ] );
							?></p><a href="?lscwp_temp_warning_plugin"><?php
								esc_html_e( 'Dismiss', 'litespeed-cache' );
						?></a></p>
					</div>
					<?php
				} );
			}
		}

		if ( ! get_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_ssl' ) ) {
			if ( strpos( WP_CONTENT_URL , get_site_url() ) === false ) {
				# have to use WP_CONTENT_URL to get true scheme 
				add_action( 'admin_notices', function() {
					?>
					<div class="notice notice-error is-dismissible">
						<p><?php
							printf(
								esc_html__( 'Detected WordPress URL: %s', 'litespeed-cache' ),
								'<p style="color: red;">' . site_url() . '</p>'
							);
							?>
							<br>
							<?php
							printf(
								esc_html__( 'Detected Wordpress Content URL: %s', 'litespeed-cache' ),
								'<p style="color: red;">' . WP_CONTENT_URL . '</p>'
							);
							?>
							<br>
							<?php
							printf(
								esc_html__(
									'It appears that you are using %1$s and %2$s inconsistently. Please visit %3$s and update the WordPress Address or Site Address to avoid a mixed content error.',
									'litespeed-cache'
								),
								'<strong>' . esc_html__( 'HTTP', 'litespeed-cache' ) . '</strong>',
								'<strong>' . esc_html__( 'HTTPS', 'litespeed-cache' ) . '</strong>',
								'<a href="' . get_admin_url() . 'options-general.php">'
								. esc_html__( 'Settings -> General', 'litespeed-cache' )
								. '</a>'
							);
							?>
							<br><br><a href="?lscwp_temp_warning_ssl"><?php
								esc_html_e( 'Dismiss', 'litespeed-cache' );
						?></a></p>
					</div>
					<?php
				} );
			}
		}
	}
}
