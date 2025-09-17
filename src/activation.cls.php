<?php
/**
 * The plugin activation class.
 *
 * @since       1.1.0
 * @since       1.5 Moved into /inc
 * @package     LiteSpeed
 * @subpackage  LiteSpeed/inc
 * @author      LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Activation
 *
 * Handles plugin activation, deactivation, and related file management.
 *
 * @since 1.1.0
 */
class Activation extends Base {

	const TYPE_UPGRADE             = 'upgrade';
	const TYPE_INSTALL_3RD         = 'install_3rd';
	const TYPE_INSTALL_ZIP         = 'install_zip';
	const TYPE_DISMISS_RECOMMENDED = 'dismiss_recommended';

	const NETWORK_TRANSIENT_COUNT = 'lscwp_network_count';

	/**
	 * Data file path for configuration.
	 *
	 * @since 4.1
	 * @var string
	 */
	private static $data_file;

	/**
	 * Construct
	 *
	 * Initializes the data file path.
	 *
	 * @since 4.1
	 */
	public function __construct() {
		self::$data_file = LSCWP_CONTENT_DIR . '/' . self::CONF_FILE;
	}

	/**
	 * The activation hook callback.
	 *
	 * Handles plugin activation tasks, including file creation and multisite setup.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function register_activation() {
		$count = 0;
		! defined( 'LSCWP_LOG_TAG' ) && define( 'LSCWP_LOG_TAG', 'Activate_' . get_current_blog_id() );

		/* Network file handler */
		if ( is_multisite() ) {
			$count = self::get_network_count();
			if ( false !== $count ) {
				$count = (int) $count + 1;
				set_site_transient( self::NETWORK_TRANSIENT_COUNT, $count, DAY_IN_SECONDS );
			}

			if ( ! is_network_admin() ) {
				if ( 1 === $count ) {
					// Only itself is activated, set .htaccess with only CacheLookUp
					try {
						Htaccess::cls()->insert_ls_wrapper();
					} catch ( \Exception $ex ) {
						Admin_Display::error( $ex->getMessage() );
					}
				}
			}
		}
		self::cls()->update_files();

		if ( defined( 'LSCWP_REF' ) && 'whm' === LSCWP_REF ) {
			GUI::update_option( GUI::WHM_MSG, GUI::WHM_MSG_VAL );
		}
	}

	/**
	 * Uninstall plugin
	 *
	 * Removes all LiteSpeed Cache settings and data.
	 *
	 * @since 1.1.0
	 * @since 7.3 Updated to remove all settings.
	 * @access public
	 */
	public static function uninstall_litespeed_cache() {
		Task::destroy();

		if ( is_multisite() ) {
			// Save main site id
			$current_blog = get_current_blog_id();

			// get all sites
			$sub_sites = get_sites();

			// clear foreach site
			foreach ( $sub_sites as $sub_site ) {
				$sub_blog_id = (int) $sub_site->blog_id;
				if ( $sub_blog_id !== $current_blog ) {
					// Switch to blog
					switch_to_blog( $sub_blog_id );

					// Delete site options
					self::delete_settings();

					// Delete site tables
					Data::cls()->tables_del();
				}
			}

			// Return to main site
			switch_to_blog( $current_blog );
		}

		// Delete current blog/site
		// Delete options
		self::delete_settings();

		// Delete site tables
		Data::cls()->tables_del();

		if ( file_exists( LITESPEED_STATIC_DIR ) ) {
			File::rrmdir( LITESPEED_STATIC_DIR );
		}

		Cloud::version_check( 'uninstall' );
	}

	/**
	 * Remove all litespeed settings.
	 *
	 * Deletes all LiteSpeed Cache options from the database.
	 *
	 * @since 7.3
	 * @access private
	 */
	private static function delete_settings() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query($wpdb->prepare("DELETE FROM `$wpdb->options` WHERE option_name LIKE %s", 'litespeed.%'));
	}

	/**
	 * Get the blog ids for the network. Accepts function arguments.
	 *
	 * @since 1.0.12
	 * @access public
	 * @param array $args Arguments for get_sites().
	 * @return array The array of blog ids.
	 */
	public static function get_network_ids( $args = array() ) {
		$args['fields'] = 'ids';
		$blogs          = get_sites( $args );

		return $blogs;
	}

	/**
	 * Gets the count of active litespeed cache plugins on multisite.
	 *
	 * @since 1.0.12
	 * @access private
	 * @return int|false Number of active LSCWP or false if none.
	 */
	private static function get_network_count() {
		$count = get_site_transient( self::NETWORK_TRANSIENT_COUNT );
		if ( false !== $count ) {
			return (int) $count;
		}
		// need to update
		$default = array();
		$count   = 0;

		$sites = self::get_network_ids( array( 'deleted' => 0 ) );
		if ( empty( $sites ) ) {
			return false;
		}

		foreach ( $sites as $site ) {
			$bid     = is_object( $site ) && property_exists( $site, 'blog_id' ) ? $site->blog_id : $site;
			$plugins = get_blog_option( $bid, 'active_plugins', $default );
			if ( ! empty( $plugins ) && in_array( LSCWP_BASENAME, $plugins, true ) ) {
				++$count;
			}
		}

		/**
		 * In case this is called outside the admin page
		 *
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active_for_network( LSCWP_BASENAME ) ) {
			++$count;
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
		if ( false === $count ) {
			return false;
		}
		if ( 1 !== $count ) {
			// Not deactivating the last one.
			--$count;
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
						Htaccess::cls()->insert_ls_wrapper();
					} catch ( \Exception $ex ) {
						Admin_Display::error($ex->getMessage());
					}
				}
				return;
			}
		}

		/* 1) wp-config.php; */

		try {
			self::cls()->manage_wp_cache_const( false );
		} catch ( \Exception $ex ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'In wp-config.php: WP_CACHE could not be set to false during deactivation!' );

			Admin_Display::error( $ex->getMessage() );
		}

		/* 2) adv-cache.php; Dropped in v3.0.4 */

		/* 3) object-cache.php; */

		Object_Cache::cls()->del_file();

		/* 4) .htaccess; */

		try {
			Htaccess::cls()->clear_rules();
		} catch ( \Exception $ex ) {
			Admin_Display::error( $ex->getMessage() );
		}

		/* 5) .litespeed_conf.dat; */

		self::del_conf_data_file();

		/* 6) delete option lscwp_whm_install */

		// delete in case it's not deleted prior to deactivation.
		GUI::dismiss_whm();
	}

	/**
	 * Manage related files based on plugin latest conf
	 *
	 * Handle files:
	 *      1) wp-config.php;
	 *      2) adv-cache.php;
	 *      3) object-cache.php;
	 *      4) .htaccess;
	 *      5) .litespeed_conf.dat;
	 *
	 * @since 3.0
	 * @access public
	 */
	public function update_files() {
		Debug2::debug( '🗂️ [Activation] update_files' );

		// Update cache setting `_CACHE`
		$this->cls( 'Conf' )->define_cache();

		// Site options applied already
		$options = $this->get_options();

		/* 1) wp-config.php; */

		try {
			$this->manage_wp_cache_const( $options[ self::_CACHE ] );
		} catch ( \Exception $ex ) {
			// Add msg to admin page or CLI
			Admin_Display::error( wp_kses_post( $ex->getMessage() ) );
		}

		/* 2) adv-cache.php; Dropped in v3.0.4 */

		/* 3) object-cache.php; */

		if ( $options[ self::O_OBJECT ] && ( ! $options[ self::O_DEBUG_DISABLE_ALL ] || is_multisite() ) ) {
			$this->cls( 'Object_Cache' )->update_file( $options );
		} else {
			$this->cls( 'Object_Cache' )->del_file(); // Note: because it doesn't reconnect, which caused setting page OC option changes delayed, thus may meet Connect Test Failed issue (Next refresh will correct it). Not a big deal, will keep as is.
		}

		/* 4) .htaccess; */

		try {
			$this->cls( 'Htaccess' )->update( $options );
		} catch ( \Exception $ex ) {
			Admin_Display::error( wp_kses_post( $ex->getMessage() ) );
		}

		/* 5) .litespeed_conf.dat; */

		if ( ( $options[ self::O_GUEST ] || $options[ self::O_OBJECT ] ) && ( ! $options[ self::O_DEBUG_DISABLE_ALL ] || is_multisite() ) ) {
			$this->update_conf_data_file( $options );
		}
	}

	/**
	 * Delete data conf file
	 *
	 * Removes the .litespeed_conf.dat file.
	 *
	 * @since  4.1
	 * @access private
	 */
	private static function del_conf_data_file() {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( $wp_filesystem->exists( self::$data_file ) ) {
			$wp_filesystem->delete( self::$data_file );
		}
	}

	/**
	 * Update data conf file for guest mode & object cache
	 *
	 * Updates the .litespeed_conf.dat file with relevant settings.
	 *
	 * @since  4.1
	 * @access private
	 * @param array $options Plugin options.
	 */
	private function update_conf_data_file( $options ) {
		$ids = array();
		if ( $options[ self::O_OBJECT ] ) {
			$this_ids = array(
				self::O_DEBUG,
				self::O_OBJECT_KIND,
				self::O_OBJECT_HOST,
				self::O_OBJECT_PORT,
				self::O_OBJECT_LIFE,
				self::O_OBJECT_USER,
				self::O_OBJECT_PSWD,
				self::O_OBJECT_DB_ID,
				self::O_OBJECT_PERSISTENT,
				self::O_OBJECT_ADMIN,
				self::O_OBJECT_TRANSIENTS,
				self::O_OBJECT_GLOBAL_GROUPS,
				self::O_OBJECT_NON_PERSISTENT_GROUPS,
			);
			$ids      = array_merge( $ids, $this_ids );
		}

		if ( $options[ self::O_GUEST ] ) {
			$this_ids = array(
				self::HASH,
				self::O_CACHE_LOGIN_COOKIE,
				self::O_DEBUG_IPS,
				self::O_UTIL_NO_HTTPS_VARY,
				self::O_GUEST_UAS,
				self::O_GUEST_IPS,
			);
			$ids      = array_merge( $ids, $this_ids );
		}

		$data = array();
		foreach ( $ids as $v ) {
			$data[ $v ] = $options[ $v ];
		}
		$data = wp_json_encode( $data );

		$old_data = File::read( self::$data_file );
		if ( $old_data !== $data ) {
			defined( 'LSCWP_LOG' ) && Debug2::debug( '[Activation] Updating .litespeed_conf.dat' );
			File::save( self::$data_file, $data );
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
	 * @param bool $enable Whether to enable WP_CACHE.
	 * @throws \Exception If wp-config.php cannot be modified.
	 * @return bool True if updated, false if no change needed.
	 */
	public function manage_wp_cache_const( $enable ) {
		if ( $enable ) {
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				return false;
			}
		} elseif ( ! defined( 'WP_CACHE' ) || ( defined( 'WP_CACHE' ) && ! WP_CACHE ) ) {
			return false;
		}

		if ( apply_filters( 'litespeed_wpconfig_readonly', false ) ) {
			throw new \Exception( 'wp-config file is forbidden to modify due to API hook: litespeed_wpconfig_readonly' );
		}

		/**
		 * Follow WP's logic to locate wp-config file
		 *
		 * @see wp-load.php
		 */
		$conf_file = ABSPATH . 'wp-config.php';
		if ( ! file_exists( $conf_file ) ) {
			$conf_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		$content = File::read( $conf_file );
		if ( ! $content ) {
			throw new \Exception( 'wp-config file content is empty: ' . wp_kses_post( $conf_file ) );
		}

		// Remove the line `define('WP_CACHE', true/false);` first
		if ( defined( 'WP_CACHE' ) ) {
			$content = preg_replace( '/define\(\s*([\'"])WP_CACHE\1\s*,\s*\w+\s*\)\s*;/sU', '', $content );
		}

		// Insert const
		if ( $enable ) {
			$content = preg_replace( '/^<\?php/', "<?php\ndefine( 'WP_CACHE', true );", $content );
		}

		$res = File::save( $conf_file, $content, false, false, false );

		if ( true !== $res ) {
			throw new \Exception( 'wp-config.php operation failed when changing `WP_CACHE` const: ' . wp_kses_post( $res ) );
		}

		return true;
	}

	/**
	 * Handle auto update
	 *
	 * Enables auto-updates for the plugin if configured.
	 *
	 * @since 2.7.2
	 * @access public
	 */
	public function auto_update() {
		if ( ! $this->conf( Base::O_AUTO_UPGRADE ) ) {
			return;
		}

		add_filter( 'auto_update_plugin', array( $this, 'auto_update_hook' ), 10, 2 );
	}

	/**
	 * Auto upgrade hook
	 *
	 * Determines whether to auto-update the plugin.
	 *
	 * @since  3.0
	 * @access public
	 * @param bool   $update Whether to update.
	 * @param object $item   Plugin data.
	 * @return bool Whether to update.
	 */
	public function auto_update_hook( $update, $item ) {
		if ( ! empty( $item->slug ) && 'litespeed-cache' === $item->slug ) {
			$auto_v = Cloud::version_check( 'auto_update_plugin' );

			if ( ! empty( $auto_v['latest'] ) && ! empty( $item->new_version ) && $auto_v['latest'] === $item->new_version ) {
				return true;
			}
		}

		return $update; // Else, use the normal API response to decide whether to update or not
	}

	/**
	 * Upgrade LSCWP
	 *
	 * Upgrades the LiteSpeed Cache plugin.
	 *
	 * @since 2.9
	 * @access public
	 */
	public function upgrade() {
		$plugin = Core::PLUGIN_FILE;

		/**
		 * Load upgrade cls
		 *
		 * @see wp-admin/update.php
		 */
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/misc.php';

		try {
			ob_start();
			$skin     = new \WP_Ajax_Upgrader_Skin();
			$upgrader = new \Plugin_Upgrader( $skin );
			$result   = $upgrader->upgrade( $plugin );
			if ( ! is_plugin_active( $plugin ) ) {
				// todo: upgrade should reactivate the plugin again by WP. Need to check why disabled after upgraded.
				activate_plugin( $plugin, '', is_multisite() );
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

		Admin_Display::success( __( 'Upgraded successfully.', 'litespeed-cache' ) );
	}

	/**
	 * Detect if the plugin is active or not
	 *
	 * @since  1.0
	 * @access public
	 * @param string $plugin Plugin slug.
	 * @return bool True if active, false otherwise.
	 */
	public function dash_notifier_is_plugin_active( $plugin ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugin_path = $plugin . '/' . $plugin . '.php';

		return is_plugin_active( $plugin_path );
	}

	/**
	 * Detect if the plugin is installed or not
	 *
	 * @since  1.0
	 * @access public
	 * @param string $plugin Plugin slug.
	 * @return bool True if installed, false otherwise.
	 */
	public function dash_notifier_is_plugin_installed( $plugin ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugin_path = $plugin . '/' . $plugin . '.php';

		$valid = validate_plugin( $plugin_path );

		return ! is_wp_error( $valid );
	}

	/**
	 * Grab a plugin info from WordPress
	 *
	 * @since  1.0
	 * @access public
	 * @param string $slug Plugin slug.
	 * @return object|false Plugin info or false on failure.
	 */
	public function dash_notifier_get_plugin_info( $slug ) {
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		$result = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Install the 3rd party plugin
	 *
	 * Installs and activates a third-party plugin.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function dash_notifier_install_3rd() {
		! defined( 'SILENCE_INSTALL' ) && define( 'SILENCE_INSTALL', true );

		// phpcs:ignore
		$slug = ! empty( $_GET['plugin'] ) ? wp_unslash( sanitize_text_field( $_GET['plugin'] ) ) : false;

		// Check if plugin is installed already
		if ( ! $slug || $this->dash_notifier_is_plugin_active( $slug ) ) {
			return;
		}

		/**
		 * Load upgrade cls
		 *
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
				$skin     = new \Automatic_Upgrader_Skin();
				$upgrader = new \Plugin_Upgrader( $skin );
				$result   = $upgrader->install( $plugin_info->download_link );
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
	 * Processes various activation-related actions.
	 *
	 * @since  2.9
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_UPGRADE:
				$this->upgrade();
				break;

			case self::TYPE_INSTALL_3RD:
				$this->dash_notifier_install_3rd();
				break;

			case self::TYPE_DISMISS_RECOMMENDED:
				Cloud::reload_summary();
				Cloud::save_summary( array( 'news.new' => 0 ) );
				break;

			case self::TYPE_INSTALL_ZIP:
				Cloud::reload_summary();
				$summary = Cloud::get_summary();
				if ( ! empty( $summary['news.zip'] ) ) {
					Cloud::save_summary( array( 'news.new' => 0 ) );

					$this->cls( 'Debug2' )->beta_test( $summary['zip'] );
				}
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
