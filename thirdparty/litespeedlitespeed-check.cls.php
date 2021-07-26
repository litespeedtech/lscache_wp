<?php
/**
 * Check if certain plugin that could conflict with LiteSpeed Cache is activated.
 * @since		4.2
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class LiteSpeed_Check {

	public static function detect() {

	    if ( ! is_admin()) {
		return;
	    }

	    $user_id = get_current_user_id();

	    if ( isset( $_GET['lscwp_temp_warning_plugin'] ) ) {
            	add_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_plugin', 'true', true );
            }

	    if ( isset( $_GET['lscwp_temp_warning_ssl'] ) ) {
            	add_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_ssl', 'true', true );
            }

	    if ( ! get_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_plugin' ) ) {
		session_start();
		$_SESSION['lscwp_temp_warning'] = array();
		$plugin_list = array(
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
            		'wp-super-cache/wp-cache.php'
			);

		foreach ($plugin_list as $plugin_check) {
    			if ( file_exists(WP_PLUGIN_DIR. '/' . $plugin_check) && is_plugin_active( $plugin_check )) {
			#check file existance before is_plugin_active() to save resources.
    	    			$plugin_name = get_plugin_data ( WP_PLUGIN_DIR. '/' . $plugin_check );
			}
			if (!empty($plugin_name)) {
				array_push($_SESSION['lscwp_temp_warning'], $plugin_name['Name'] );
			}
		}

		$_SESSION['lscwp_temp_warning']=array_unique($_SESSION['lscwp_temp_warning']);

		if (!empty($_SESSION['lscwp_temp_warning'])) {
			add_action( 'admin_notices', function( ) {
				?>
				<div class="notice notice-error is-dismissible">
   		    		<p><?php
   		    		_e( 'Please consider disabling the following detected plugins, as they may conflict with LiteSpeed Cache: <br><p style="color:red;">'
   		    		. implode(', ', $_SESSION['lscwp_temp_warning']) .
   		    		'</p><a href="?lscwp_temp_warning_plugin">Dismiss</a>',
   		    		'my-text-domain' );
   		    		?></p>
				</div>
				<?php
				}
			);
		}
	    }

		if ( ! get_user_meta( $user_id, 'litespeed.conf_temp_warning_dismiss_ssl' ) ) {
			if ( strpos( WP_CONTENT_URL , get_site_url() ) === false) {
			#have to use WP_CONTENT_URL to get true scheme 
		    		add_action( 'admin_notices', function( ) {
				?>
				<div class="notice notice-error is-dismissible">
   		    		<p><?php
   		    		_e( 'Detected WordPress URL: <p style="color:red;">'. site_url() .'</p><br>Detected Wordpress Content URL:<p style="color:red;">' . WP_CONTENT_URL .
   		    		'</p><br>It appears that you are using <b>HTTP</b> and <b>HTTPS</b> inconsistently. Please visit <a href="'. get_admin_url() .'options-general.php">Settings -> General</a>
				 and update the WordPress Address or Site Address to avoid a mixed content error.<br><br><a href="?lscwp_temp_warning_ssl">Dismiss</a>',
   		    		'my-text-domain' );
   		    		?></p>
				</div>
				<?php
				}
				);
			}
		}
	}
}
