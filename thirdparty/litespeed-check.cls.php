<?php
/**
 * Check if any plugins that could conflict with LiteSpeed Cache are active.
 * @since		4.6
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class LiteSpeed_Check {

	public static $_incompatible_plugins =
		array(
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
		);

	private static $_meta_key = 'litespeed.conf_temp_warning_dismiss_plugin';

	public static function on_plugins_changed( $_plugin, $_network_wide ) {
		delete_user_meta( get_current_user_id(), self::$_meta_key );
	}

	public static function detect() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'activated_plugin', __CLASS__ . '::on_plugins_changed', 10, 2 );
		add_action( 'deactivated_plugin', __CLASS__ . '::on_plugins_changed', 10, 2 );

		if ( isset( $_GET[ 'lscwp_incompatible_plugins' ] )
		&& 'dismiss' === $_GET[ 'lscwp_incompatible_plugins' ]
		&& check_admin_referer( 'lscwp_dismiss_incompatible_plugins' ) ) {
			add_user_meta( get_current_user_id(), self::$_meta_key, 'true', true );
			return;
		}

		if ( ! get_user_meta( get_current_user_id(), self::$_meta_key ) ) {
			add_action( 'admin_notices', __CLASS__ . '::incompatible_plugin_notice' );
		}
	}

	public static function incompatible_plugin_notice() {
		$incompatible_plugins =
			array_map(
				function( $plugin ) { return WP_PLUGIN_DIR . '/' . $plugin; },
				self::$_incompatible_plugins
			);

		$active_incompatible_plugins =
			array_map(
				function( $plugin ) {
					$plugin = get_plugin_data( $plugin, false, true );
					return $plugin[ 'Name' ];
				},
				array_intersect( $incompatible_plugins, wp_get_active_and_valid_plugins() )
			);

		if ( empty( $active_incompatible_plugins ) ) {
			return;
		}

		?>
		<div class="notice notice-error litespeed-irremovable">
			<p>
				<?php
					esc_html_e(
						'Please consider disabling the following detected plugins, as they may conflict with LiteSpeed Cache:',
						'litespeed-cache'
					);
				?>
				<br>
				<p style="color: red; font-weight: 700;"><?php
					echo implode( ', ', $active_incompatible_plugins );
				?></p>
				<a href="<?php
					echo wp_nonce_url(
						add_query_arg( 'lscwp_incompatible_plugins', 'dismiss' ),
						'lscwp_dismiss_incompatible_plugins'
					);
				?>" class="button litespeed-btn-primary litespeed-btn-mini"><?php
					esc_html_e( 'Dismiss', 'litespeed-cache' );
				?></a>
			</p>
		</div>
		<?php
	}
}
