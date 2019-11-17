<?php
/**
 * The plugin activation class.
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Activation
{
	private static $_instance ;

	const TYPE_UPGRADE = 'upgrade' ;
	const TYPE_INSTALL_3RD = 'install_3rd' ;
	const TYPE_INSTALL_ZIP = 'install_zip' ;
	const TYPE_DISMISS_RECOMMENDED = 'dismiss_recommended' ;

	const NETWORK_TRANSIENT_COUNT = 'lscwp_network_count' ;

	/**
	 * The activation hook callback.
	 *
	 * Attempts to set up the advanced cache file. If it fails for any reason,
	 * the plugin will not activate.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function register_activation()
	{
		$count = 0 ;
		! defined( 'LSCWP_LOG_TAG' ) && define( 'LSCWP_LOG_TAG', 'Activate_' . get_current_blog_id() ) ;

		if ( is_multisite() ) {
			$count = self::get_network_count() ;
			if ( $count !== false ) {
				$count = intval( $count ) + 1 ;
				set_site_transient( self::NETWORK_TRANSIENT_COUNT, $count, DAY_IN_SECONDS ) ;
			}
		}

		do_action( 'litespeed_cache_api_load_thirdparty' ) ;

		$__cfg = LiteSpeed_Cache_Config::get_instance() ;

		// Bcos we may ask clients to deactivate for debug temporarily, we need to keep the current cfg in deactivation, hence we need to only try adding default cfg when activating.
		$res = add_option( LiteSpeed_Cache_Config::OPTION_NAME, $__cfg->get_default_options() ) ;

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( "[Cfg] plugin_activation update option = " . var_export( $res, true ) ) ;

		// Check new version @since 2.9.3
		LiteSpeed_Cache_Utility::version_check( 'new' . ( defined( 'LSCWP_REF' ) ? '_' . LSCWP_REF : '' ) ) ;

		/**
		 * Handle files:
		 * 		1) wp-config.php;
		 * 		2) adv-cache.php;
		 * 		3) object-cache.php;
		 * 		4) .htaccess;
		 */

		/* Network file handler */

		if ( is_multisite() ) {

			if ( ! is_network_admin() ) {
				if ( $count === 1 ) {
					// Only itself is activated, set .htaccess with only CacheLookUp
					LiteSpeed_Cache_Admin_Rules::get_instance()->insert_ls_wrapper() ;
				}
				return ;
			}

			// All .htaccess & OC related options are in site, so only need these options
			$options = $__cfg->get_site_options() ;

			$ids = array(
				LiteSpeed_Cache_Config::ITEM_OBJECT_GLOBAL_GROUPS,
				LiteSpeed_Cache_Config::ITEM_OBJECT_NON_PERSISTENT_GROUPS,
			);
			foreach ( $ids as $id ) {
				$options[ $id ] = $__cfg->get_item( $id ) ;
			}

			if ( ! empty($options[ LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST ]) ) {
				$options[ LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST ] =
					addslashes( $options[ LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST ] );
			}

			LiteSpeed_Cache_Admin_Settings::get_instance()->validate_network_settings( $options, true ) ;
			return ;
		}

		/* Single site file handler */

		$options = $__cfg->get_options() ;

		// Add items
		$cfg_items = $__cfg->stored_items() ;
		foreach ( $cfg_items as $v ) {
			$options[ $v ] = $__cfg->get_item( $v ) ;
		}

		/**
		 * Go through all settings to generate related files
		 * @since 2.7.1
		 */
		LiteSpeed_Cache_Admin_Settings::get_instance()->validate_plugin_settings( $options, true ) ;

		if ( defined( 'LSCWP_REF' ) && LSCWP_REF == 'whm' ) {
			update_option( LiteSpeed_Cache::WHM_MSG, LiteSpeed_Cache::WHM_MSG_VAL ) ;
		}

		// Register crawler cron task
		LiteSpeed_Cache_Task::update() ;
	}

	/**
	 * Uninstall plugin
	 * @since 1.1.0
	 */
	public static function uninstall_litespeed_cache()
	{
		LiteSpeed_Cache_Task::clear() ;
		LiteSpeed_Cache_Admin_Rules::get_instance()->clear_rules() ;
		delete_option( LiteSpeed_Cache_Config::OPTION_NAME ) ;
		if ( is_multisite() ) {
			delete_site_option( LiteSpeed_Cache_Config::OPTION_NAME ) ;
		}

		LiteSpeed_Cache_Utility::version_check( 'uninstall' ) ;
	}

	/**
	 * Get the blog ids for the network. Accepts function arguments.
	 *
	 * Will use wp_get_sites for WP versions less than 4.6
	 *
	 * @since 1.0.12
	 * @access public
	 * @param array $args Arguments to pass into get_sites/wp_get_sites.
	 * @return array The array of blog ids.
	 */
	public static function get_network_ids( $args = array() )
	{
		global $wp_version ;
		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			$blogs = wp_get_sites( $args ) ;
			if ( ! empty( $blogs ) ) {
				foreach ( $blogs as $key => $blog ) {
					$blogs[ $key ] = $blog[ 'blog_id' ] ;
				}
			}
		}
		else {
			$args[ 'fields' ] = 'ids' ;
			$blogs = get_sites( $args ) ;
		}
		return $blogs ;
	}

	/**
	 * Gets the count of active litespeed cache plugins on multisite.
	 *
	 * @since 1.0.12
	 * @access private
	 * @return mixed The count on success, false on failure.
	 */
	private static function get_network_count()
	{
		$count = get_site_transient( self::NETWORK_TRANSIENT_COUNT ) ;
		if ( $count !== false ) {
			return intval( $count ) ;
		}
		// need to update
		$default = array() ;
		$count = 0 ;

		$sites = self::get_network_ids( array( 'deleted' => 0 ) ) ;
		if ( empty( $sites ) ) {
			return false ;
		}

		foreach ( $sites as $site ) {
			$bid = is_object( $site ) && property_exists( $site, 'blog_id' ) ? $site->blog_id : $site ;
			$plugins = get_blog_option( $bid , 'active_plugins', $default ) ;
			if ( in_array( LSCWP_BASENAME, $plugins, true ) ) {
				$count++ ;
			}
		}

		/**
		 * In case this is called outside the admin page
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;
		}

		if ( is_plugin_active_for_network( LSCWP_BASENAME ) ) {
			$count++ ;
		}
		return $count ;
	}

	/**
	 * Is this deactivate call the last active installation on the multisite
	 * network?
	 *
	 * @since 1.0.12
	 * @access private
	 * @return bool True if yes, false otherwise.
	 */
	private static function is_deactivate_last()
	{
		$count = self::get_network_count() ;
		if ( $count === false ) {
			return false ;
		}
		if ( $count !== 1 ) {
			// Not deactivating the last one.
			$count-- ;
			set_site_transient( self::NETWORK_TRANSIENT_COUNT, $count, DAY_IN_SECONDS ) ;
			return false ;
		}

		delete_site_transient( self::NETWORK_TRANSIENT_COUNT ) ;
		return true ;
	}

	/**
	 * The deactivation hook callback.
	 *
	 * Initializes all clean up functionalities.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function register_deactivation()
	{
		LiteSpeed_Cache_Task::clear() ;

		! defined( 'LSCWP_LOG_TAG' ) && define( 'LSCWP_LOG_TAG', 'Deactivate_' . get_current_blog_id() ) ;

		LiteSpeed_Cache_Purge::purge_all() ;

		if ( is_multisite() ) {

			if ( ! self::is_deactivate_last() ) {
				if ( is_network_admin() ) {
					// Still other activated subsite left, set .htaccess with only CacheLookUp
					LiteSpeed_Cache_Admin_Rules::get_instance()->insert_ls_wrapper() ;
				}
				return ;
			}
		}

		$adv_cache_path = LSCWP_CONTENT_DIR . '/advanced-cache.php' ;
		// this file can be generated by other cache plugin like w3tc, we only delete our own file
		if ( file_exists( $adv_cache_path ) && is_writable( $adv_cache_path ) ) {
			if ( strpos( file_get_contents( $adv_cache_path ), 'LSCACHE_ADV_CACHE' ) !== false ) {
				unlink( $adv_cache_path ) ;
			}
			else {
				error_log(' Keep advanced-cache.php as it belongs to other plugins' ) ;
			}
		}
		else {
			error_log( 'Failed to remove advanced-cache.php, file does not exist or is not writable!' ) ;
		}

		/**
		 * Remove object cache file if is us
		 * @since  1.8.2
		 */
		LiteSpeed_Cache_Object::get_instance()->del_file() ;

		if ( ! LiteSpeed_Cache_Config::wp_cache_var_setter( false ) ) {
			error_log('In wp-config.php: WP_CACHE could not be set to false during deactivation!')  ;
		}

		LiteSpeed_Cache_Admin_Rules::get_instance()->clear_rules() ;

		// delete in case it's not deleted prior to deactivation.
		self::dismiss_whm() ;
	}

	/**
	 * Try to copy our advanced-cache.php file to the wordpress directory.
	 *
	 * @since 1.0.11
	 * @access public
	 * @return boolean True on success, false on failure.
	 */
	public static function try_copy_advanced_cache()
	{
		$adv_cache_path = LSCWP_CONTENT_DIR . '/advanced-cache.php' ;
		if ( file_exists( $adv_cache_path ) && ( filesize( $adv_cache_path ) !== 0 || ! is_writable( $adv_cache_path ) ) ) {
			return false ;
		}

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Activation] Copying advanced_cache file' ) ;

		copy( LSCWP_DIR . 'includes/advanced-cache.php', $adv_cache_path ) ;

		include $adv_cache_path ;

		$ret = defined( 'LSCACHE_ADV_CACHE' ) ;

		// Try to enable `LITESPEED_ON`
		LiteSpeed_Cache_Config::get_instance()->define_cache_on() ;

		return $ret ;
	}

	/**
	 * Delete whm msg tag
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function dismiss_whm()
	{
		delete_option( LiteSpeed_Cache::WHM_MSG ) ;
	}

	/**
	 * Handle auto update
	 *
	 * @since 2.7.2
	 * @since 2.9.8 Moved here from ls.cls
	 * @access public
	 */
	public static function auto_update()
	{
		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_AUTO_UPGRADE ) ) {
			return ;
		}

		add_filter( 'auto_update_plugin', function( $update, $item ) {
				if ( $item->slug == 'litespeed-cache' ) {
					$auto_v = LiteSpeed_Cache_Utility::version_check( 'auto_update_plugin' ) ;

					if ( $auto_v && ! empty( $item->new_version ) && $auto_v === $item->new_version ) {
						return true ;
					}
				}

				return $update; // Else, use the normal API response to decide whether to update or not
			}, 10, 2 ) ;
	}

	/**
	 * Upgrade LSCWP
	 *
	 * @since 2.9
	 * @access public
	 */
	public function upgrade()
	{
		$plugin = LiteSpeed_Cache::PLUGIN_FILE ;

		/**
		 * @see wp-admin/update.php
		 */
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' ;
		include_once ABSPATH . 'wp-admin/includes/file.php' ;
		include_once ABSPATH . 'wp-admin/includes/misc.php' ;

		try {
			ob_start() ;
			$skin = new \WP_Ajax_Upgrader_Skin() ;
			$upgrader = new \Plugin_Upgrader( $skin ) ;
			$result = $upgrader->upgrade( $plugin ) ;
			if ( ! is_plugin_active( $plugin ) ) {// todo: upgrade should reactivate the plugin again by WP. Need to check why disabled after upgraded.
				activate_plugin( $plugin ) ;
			}
			ob_end_clean() ;
		} catch ( \Exception $e ) {
			LiteSpeed_Cache_Admin_Display::error( __( 'Failed to upgrade.', 'litespeed-cache' ) ) ;
			return ;
		}

		if ( is_wp_error( $result ) ) {
			LiteSpeed_Cache_Admin_Display::error( __( 'Failed to upgrade.', 'litespeed-cache' ) ) ;
			return ;
		}

		LiteSpeed_Cache_Admin_Display::succeed( __( 'Upgraded successfully.', 'litespeed-cache' ) ) ;
	}

	/**
	 * Detect if the plugin is active or not
	 *
	 * @since  1.0
	 */
	public function dash_notifier_is_plugin_active( $plugin )
	{
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' ) ;

		$plugin_path = $plugin . '/' . $plugin . '.php' ;

		return is_plugin_active( $plugin_path ) ;
	}

	/**
	 * Detect if the plugin is installed or not
	 *
	 * @since  1.0
	 */
	public function dash_notifier_is_plugin_installed( $plugin )
	{
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' ) ;

		$plugin_path = $plugin . '/' . $plugin . '.php' ;

		$valid = validate_plugin( $plugin_path ) ;

		return ! is_wp_error( $valid ) ;
	}

	/**
	 * Grab a plugin info from WordPress
	 *
	 * @since  1.0
	 */
	public function dash_notifier_get_plugin_info( $slug )
	{
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ) ;
		$result = plugins_api( 'plugin_information', array( 'slug' => $slug ) ) ;

		if ( is_wp_error( $result ) ) {
			return false ;
		}

		return $result ;
	}

	/**
	 * Install the 3rd party plugin
	 *
	 * @since  1.0
	 */
	public function dash_notifier_install_3rd()
	{
		! defined( 'SILENCE_INSTALL' ) && define( 'SILENCE_INSTALL', true );

		$slug = ! empty( $_GET[ 'plugin' ] ) ? $_GET[ 'plugin' ] : false;

		// Check if plugin is installed already
		if ( ! $slug || $this->dash_notifier_is_plugin_active( $slug ) ) {
			return ;
		}

		/**
		 * @see wp-admin/update.php
		 */
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' ;
		include_once ABSPATH . 'wp-admin/includes/file.php' ;
		include_once ABSPATH . 'wp-admin/includes/misc.php' ;

		$plugin_path = $slug . '/' . $slug . '.php' ;

		if ( ! $this->dash_notifier_is_plugin_installed( $slug ) ) {
			$plugin_info = $this->dash_notifier_get_plugin_info( $slug ) ;
			if ( ! $plugin_info ) {
				return ;
			}
			// Try to install plugin
			try {
				ob_start() ;
				$skin = new \Automatic_Upgrader_Skin() ;
				$upgrader = new \Plugin_Upgrader( $skin ) ;
				$result = $upgrader->install( $plugin_info->download_link ) ;
				ob_end_clean() ;
			} catch ( \Exception $e ) {
				return ;
			}
		}

		if ( ! is_plugin_active( $plugin_path ) ) {
			activate_plugin( $plugin_path ) ;
		}

	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.9
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_UPGRADE :
				$instance->upgrade() ;
				break ;

			case self::TYPE_INSTALL_3RD :
				$instance->dash_notifier_install_3rd() ;
				break ;

			case self::TYPE_DISMISS_RECOMMENDED :
				$news = get_option( 'litespeed-recommended', array() );
				$news[ 'new' ] = 0;
				update_option( 'litespeed-recommended', $news );
				break ;

			case self::TYPE_INSTALL_ZIP :
				$news = get_option( 'litespeed-recommended', array() );
				if ( ! empty( $news[ 'zip' ] ) ) {
					$news[ 'new' ] = 0;
					update_option( 'litespeed-recommended', $news );
					LiteSpeed_Cache_Log::get_instance()->beta_test( $news[ 'zip' ] );
				}
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 2.9
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}
