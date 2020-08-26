<?php
/**
 * The plugin activation class.
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Activation extends Instance {
	protected static $_instance;

	const TYPE_UPGRADE = 'upgrade';
	const TYPE_INSTALL_3RD = 'install_3rd';
	const TYPE_INSTALL_ZIP = 'install_zip';
	const TYPE_DISMISS_RECOMMENDED = 'dismiss_recommended';

	const NETWORK_TRANSIENT_COUNT = 'lscwp_network_count';

	/**
	 * The activation hook callback.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function register_activation() {
		$count = 0;
		! defined( 'LSCWP_LOG_TAG' ) && define( 'LSCWP_LOG_TAG', 'Activate_' . get_current_blog_id() );

		if ( is_multisite() ) {
			$count = self::get_network_count();
			if ( $count !== false ) {
				$count = intval( $count ) + 1;
				set_site_transient( self::NETWORK_TRANSIENT_COUNT, $count, DAY_IN_SECONDS );
			}
		}

		do_action( 'litespeed_load_thirdparty' );

		// Check new version @since 2.9.3
		Cloud::version_check( 'activate' . ( defined( 'LSCWP_REF' ) ? '_' . LSCWP_REF : '' ) );

		/* Network file handler */

		if ( is_multisite() ) {

			if ( ! is_network_admin() ) {
				if ( $count === 1 ) {
					// Only itself is activated, set .htaccess with only CacheLookUp
					try {
						Htaccess::get_instance()->insert_ls_wrapper();
					} catch ( \Exception $ex ) {
						Admin_Display::error( $ex->getMessage() );
					}
				}
				return;
			}

			Conf::get_instance()->update_confs();

			return;
		}

		/* Single site file handler */
		Conf::get_instance()->update_confs();

		if ( defined( 'LSCWP_REF' ) && LSCWP_REF == 'whm' ) {
			GUI::update_option( GUI::WHM_MSG, GUI::WHM_MSG_VAL );
		}
	}

	/**
	 * Uninstall plugin
	 * @since 1.1.0
	 */
	public static function uninstall_litespeed_cache() {
		Task::destroy();

		// Delete options
		foreach ( Conf::get_instance()->load_default_vals() as $k => $v ) {
			Base::delete_option( $k );
		}

		// Delete site options
		if ( is_multisite() ) {
			foreach ( Conf::get_instance()->load_default_site_vals() as $k => $v ) {
				Base::delete_site_option( $k );
			}
		}

		// Delete avatar table
		Data::get_instance()->tables_del();

		if ( file_exists( LITESPEED_STATIC_DIR ) ) {
			File::rrmdir( LITESPEED_STATIC_DIR );
		}

		Cloud::version_check( 'uninstall' );

		// Files has been deleted when deactivated
	}

	/**
	 * Get the blog ids for the network. Accepts function arguments.
	 *
	 * Will use wp_get_sites for WP versions less than 4.6
	 *
	 * @since 1.0.12
	 * @access public
	 * @return array The array of blog ids.
	 */
	public static function get_network_ids( $args = array() ) {
		global $wp_version;
		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			$blogs = wp_get_sites( $args );
			if ( ! empty( $blogs ) ) {
				foreach ( $blogs as $key => $blog ) {
					$blogs[ $key ] = $blog[ 'blog_id' ];
				}
			}
		}
		else {
			$args[ 'fields' ] = 'ids';
			$blogs = get_sites( $args );
		}
		return $blogs;
	}

	/**
	 * Gets the count of active litespeed cache plugins on multisite.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private static function get_network_count() {
		$count = get_site_transient( self::NETWORK_TRANSIENT_COUNT );
		if ( $count !== false ) {
			return intval( $count );
		}
		// need to update
		$default = array();
		$count = 0;

		$sites = self::get_network_ids( array( 'deleted' => 0 ) );
		if ( empty( $sites ) ) {
			return false;
		}

		foreach ( $sites as $site ) {
			$bid = is_object( $site ) && property_exists( $site, 'blog_id' ) ? $site->blog_id : $site;
			$plugins = get_blog_option( $bid , 'active_plugins', $default );
			if ( in_array( LSCWP_BASENAME, $plugins, true ) ) {
				$count++;
			}
		}

		/**
		 * In case this is called outside the admin page
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active_for_network( LSCWP_BASENAME ) ) {
			$count++;
		}
		return $count;
	}

	/**
	 * Is this deactivate call the last active installation on the multisite network?
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private static function is_deactivate_last() {
		$count = self::get_network_count();
		if ( $count === false ) {
			return false;
		}
		if ( $count !== 1 ) {
			// Not deactivating the last one.
			$count--;
			set_site_transient( self::NETWORK_TRANSIENT_COUNT, $count, DAY_IN_SECONDS );
			return false;
		}

		delete_site_transient( self::NETWORK_TRANSIENT_COUNT );
		return true;
	}

	/**
	 * The deactivation hook callback.
	 *
	 * Initializes all clean up functionalities.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function register_deactivation() {
		Task::destroy();

		! defined( 'LSCWP_LOG_TAG' ) && define( 'LSCWP_LOG_TAG', 'Deactivate_' . get_current_blog_id() );

		Purge::purge_all();

		if ( is_multisite() ) {

			if ( ! self::is_deactivate_last() ) {
				if ( is_network_admin() ) {
					// Still other activated subsite left, set .htaccess with only CacheLookUp
					try {
						Htaccess::get_instance()->insert_ls_wrapper();
					} catch ( \Exception $ex ) {
						Admin_Display::error( $ex->getMessage() );
					}
				}
				return;
			}
		}

		/* 1) wp-config.php; */

		try {
			self::get_instance()->_manage_wp_cache_const( false );
		} catch ( \Exception $ex ) {
			error_log('In wp-config.php: WP_CACHE could not be set to false during deactivation!') ;

			Admin_Display::error( $ex->getMessage() );
		}

		/* 2) adv-cache.php; Dropped in v3.0.4 */

		/* 3) object-cache.php; */

		Object_Cache::get_instance()->del_file();

		/* 4) .htaccess; */

		try {
			Htaccess::get_instance()->clear_rules();
		} catch ( \Exception $ex ) {
			Admin_Display::error( $ex->getMessage() );
		}

		// delete in case it's not deleted prior to deactivation.
		GUI::dismiss_whm();
	}

	/**
	 * Manage related files based on plugin latest conf
	 *
	 * NOTE: Only trigger this in backend admin access for efficiency concern
	 *
	 * Handle files:
	 * 		1) wp-config.php;
	 * 		2) adv-cache.php;
	 * 		3) object-cache.php;
	 * 		4) .htaccess;
	 *
	 * @since 3.0
	 * @access public
	 */
	public function update_files() {
		// Update cache setting `_CACHE`
		Conf::get_instance()->define_cache();

		// Site options applied already
		$options = Conf::get_instance()->get_options();

		/* 1) wp-config.php; */

		try {
			$this->_manage_wp_cache_const( $options[ Base::_CACHE ] );
		} catch ( \Exception $ex ) {
			// Add msg to admin page or CLI
			Admin_Display::error( $ex->getMessage() );
		}

		/* 2) adv-cache.php; Dropped in v3.0.4 */

		/* 3) object-cache.php; */

		if ( $options[ Base::O_OBJECT ] && ( ! $options[ Base::O_DEBUG_DISABLE_ALL ] || is_multisite() ) ) {
			Object_Cache::get_instance()->update_file( $options );
		}
		else {
			Object_Cache::get_instance()->del_file();
		}

		/* 4) .htaccess; */

		try {
			Htaccess::get_instance()->update( $options );
		} catch ( \Exception $ex ) {
			Admin_Display::error( $ex->getMessage() );
		}
	}

	/**
	 * Update the WP_CACHE variable in the wp-config.php file.
	 *
	 * If enabling, check if the variable is defined, and if not, define it.
	 * Vice versa for disabling.
	 *
	 * @since 1.0.0
	 * @since  3.0 Refactored
	 * @access private
	 */
	private function _manage_wp_cache_const( $enable ) {
		if ( $enable ) {
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				return false;
			}
		}
		elseif ( ! defined( 'WP_CACHE' ) || ( defined( 'WP_CACHE' ) && ! WP_CACHE ) ) {
				return false;
		}

		/**
		 * Follow WP's logic to locate wp-config file
		 * @see wp-load.php
		 */
		$conf_file = ABSPATH . 'wp-config.php';
		if ( ! file_exists( $conf_file ) ) {
			$conf_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		$content = File::read( $conf_file );
		if ( ! $content ) {
			throw new \Exception( 'wp-config file content is empty: ' . $conf_file );

		}

		// Remove the line `define('WP_CACHE', true/false);` first
		if ( defined( 'WP_CACHE' ) ) {
			$content = preg_replace( '|define\(\s*(["\'])WP_CACHE\1\s*,\s*\w+\)\s*;|sU', '', $content );
		}

		// Insert const
		if ( $enable ) {
			$content = preg_replace( '|^<\?php|', "<?php\ndefine( 'WP_CACHE', true );", $content );
		}

		$res = File::save( $conf_file, $content, false, false, false );

		if ( $res !== true ) {
			throw new \Exception( 'wp-config.php operation failed when changing `WP_CACHE` const: ' . $res );
		}

		return true;
	}

	/**
	 * Handle auto update
	 *
	 * @since 2.7.2
	 * @since 2.9.8 Moved here from ls.cls
	 * @access public
	 */
	public static function auto_update() {
		if ( ! Conf::val( Base::O_AUTO_UPGRADE ) ) {
			return;
		}

		add_filter( 'auto_update_plugin', array( self::get_instance(), 'auto_update_hook' ), 10, 2 );
	}

	/**
	 * Auto upgrade hook
	 *
	 * @since  3.0
	 * @access public
	 */
	public function auto_update_hook( $update, $item ) {
		if ( $item->slug == 'litespeed-cache' ) {
			$auto_v = Cloud::version_check( 'auto_update_plugin' );

			if ( ! empty( $auto_v[ 'latest' ] ) && ! empty( $item->new_version ) && $auto_v[ 'latest' ] === $item->new_version ) {
				return true;
			}
		}

		return $update; // Else, use the normal API response to decide whether to update or not
	}

	/**
	 * Upgrade LSCWP
	 *
	 * @since 2.9
	 * @access public
	 */
	public function upgrade() {
		$plugin = Core::PLUGIN_FILE;

		/**
		 * @see wp-admin/update.php
		 */
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/misc.php';

		try {
			ob_start();
			$skin = new \WP_Ajax_Upgrader_Skin();
			$upgrader = new \Plugin_Upgrader( $skin );
			$result = $upgrader->upgrade( $plugin );
			if ( ! is_plugin_active( $plugin ) ) {// todo: upgrade should reactivate the plugin again by WP. Need to check why disabled after upgraded.
				activate_plugin( $plugin );
			}
			ob_end_clean();
		} catch ( \Exception $e ) {
			Admin_Display::error( __( 'Failed to upgrade.', 'litespeed-cache' ) );
			return;
		}

		if ( is_wp_error( $result ) ) {
			Admin_Display::error( __( 'Failed to upgrade.', 'litespeed-cache' ) );
			return;
		}

		Admin_Display::succeed( __( 'Upgraded successfully.', 'litespeed-cache' ) );
	}

	/**
	 * Detect if the plugin is active or not
	 *
	 * @since  1.0
	 */
	public function dash_notifier_is_plugin_active( $plugin ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugin_path = $plugin . '/' . $plugin . '.php';

		return is_plugin_active( $plugin_path );
	}

	/**
	 * Detect if the plugin is installed or not
	 *
	 * @since  1.0
	 */
	public function dash_notifier_is_plugin_installed( $plugin ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugin_path = $plugin . '/' . $plugin . '.php';

		$valid = validate_plugin( $plugin_path );

		return ! is_wp_error( $valid );
	}

	/**
	 * Grab a plugin info from WordPress
	 *
	 * @since  1.0
	 */
	public function dash_notifier_get_plugin_info( $slug ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		$result = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Install the 3rd party plugin
	 *
	 * @since  1.0
	 */
	public function dash_notifier_install_3rd() {
		! defined( 'SILENCE_INSTALL' ) && define( 'SILENCE_INSTALL', true );

		$slug = ! empty( $_GET[ 'plugin' ] ) ? $_GET[ 'plugin' ] : false;

		// Check if plugin is installed already
		if ( ! $slug || $this->dash_notifier_is_plugin_active( $slug ) ) {
			return;
		}

		/**
		 * @see wp-admin/update.php
		 */
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/misc.php';

		$plugin_path = $slug . '/' . $slug . '.php';

		if ( ! $this->dash_notifier_is_plugin_installed( $slug ) ) {
			$plugin_info = $this->dash_notifier_get_plugin_info( $slug );
			if ( ! $plugin_info ) {
				return;
			}
			// Try to install plugin
			try {
				ob_start();
				$skin = new \Automatic_Upgrader_Skin();
				$upgrader = new \Plugin_Upgrader( $skin );
				$result = $upgrader->install( $plugin_info->download_link );
				ob_end_clean();
			} catch ( \Exception $e ) {
				return;
			}
		}

		if ( ! is_plugin_active( $plugin_path ) ) {
			activate_plugin( $plugin_path );
		}

	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.9
	 * @access public
	 */
	public static function handler() {
		$instance = self::get_instance();

		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_UPGRADE :
				$instance->upgrade();
				break;

			case self::TYPE_INSTALL_3RD :
				$instance->dash_notifier_install_3rd();
				break;

			case self::TYPE_DISMISS_RECOMMENDED :
				$news = get_option( 'litespeed-recommended', array() );
				$news[ 'new' ] = 0;
				update_option( 'litespeed-recommended', $news );
				break;

			case self::TYPE_INSTALL_ZIP :
				$news = get_option( 'litespeed-recommended', array() );
				if ( ! empty( $news[ 'zip' ] ) ) {
					$news[ 'new' ] = 0;
					update_option( 'litespeed-recommended', $news );
					Debug2::get_instance()->beta_test( $news[ 'zip' ] );
				}
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}
