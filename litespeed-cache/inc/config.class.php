<?php
/**
 * The core plugin config class.
 *
 * This maintains all the options and settings for this plugin.
 *
 * @since      	1.0.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Config extends LiteSpeed_Cache_Const
{
	private static $_instance ;

	const TYPE_SET = 'set' ;

	protected $options ;
	protected $vary_groups ;
	protected $exclude_optimization_roles ;
	protected $exclude_cache_roles ;
	protected $purge_options ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct()
	{
		if ( is_multisite() ) {
			$options = $this->construct_multisite_options() ;
		}
		else {
			$options = get_option( self::OPTION_NAME ) ;
			if ( ! $options ) {
				$options = $this->get_default_options() ;
			}

			// Check advanced_cache set
			$this->_define_adv_cache( $options ) ;
		}

		$this->options = $options ;
		$this->purge_options = explode('.', $options[ self::OPID_PURGE_BY_POST ] ) ;

		/**
		 * Detect if has quic.cloud set
		 * @since  2.9.7
		 */
		if ( $this->options[ self::OPT_CDN_QUIC ] ) {
			! defined( 'LITESPEED_ALLOWED' ) &&  define( 'LITESPEED_ALLOWED', true ) ;
		}

		// Init global const cache on set
		if ( $this->options[ self::OPID_ENABLED_RADIO ] === self::VAL_ON
		//	 || ( is_multisite() && is_network_admin() && current_user_can( 'manage_network_options' ) && $this->options[ LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED ] ) todo: need to check when primary is off and network is on, if can manage
		) {
			$this->define_cache_on() ;
		}

		// Vary group settings
		$this->vary_groups = $this->get_item( self::VARY_GROUP ) ;

		// Exclude optimization role setting
		$this->exclude_optimization_roles = $this->get_item( self::EXCLUDE_OPTIMIZATION_ROLES ) ;

		// Exclude cache role setting
		$this->exclude_cache_roles = $this->get_item( self::EXCLUDE_CACHE_ROLES ) ;

		// Set security key if not initialized yet
		if ( isset( $this->options[ self::HASH ] ) && empty( $this->options[ self::HASH ] ) ) {
			$this->update_options( array( self::HASH => Litespeed_String::rrand( 32 ) ) ) ;
		}

		// Hook to options
		add_action( 'litespeed_init', array( $this, 'hook_options' ) ) ;

	}

	/**
	 * Give an API to change all options val
	 * All hooks need to be added before `after_setup_theme`
	 *
	 * @since  2.6
	 * @access public
	 */
	public function hook_options()
	{
		foreach ( $this->options as $k => $v ) {
			$new_v = apply_filters( "litespeed_option_$k", $v ) ;

			if ( $new_v !== $v ) {
				LiteSpeed_Cache_Log::debug( "[Conf] ** $k changed by hook [litespeed_option_$k] from " . var_export( $v, true ) . ' to ' . var_export( $new_v, true ) ) ;
				$this->options[ $k ] = $new_v ;
			}
		}
	}

	/**
	 * Force an option to a certain value
	 *
	 * @since  2.6
	 * @access public
	 */
	public function force_option( $k, $v )
	{
		if ( array_key_exists( $k, $this->options ) ) {
			LiteSpeed_Cache_Log::debug( "[Conf] ** $k forced value to " . var_export( $v, true ) ) ;
			$this->options[ $k ] = $v ;
		}
	}

	/**
	 * Define `LSCACHE_ADV_CACHE` based on options setting
	 *
	 * NOTE: this must be before `LITESPEED_ON` defination
	 *
	 * @since 2.1
	 * @access private
	 */
	private function _define_adv_cache( $options )
	{
		if ( isset( $options[ self::OPID_CHECK_ADVANCEDCACHE ] ) && ! $options[ self::OPID_CHECK_ADVANCEDCACHE ] ) {
			! defined( 'LSCACHE_ADV_CACHE' ) && define( 'LSCACHE_ADV_CACHE', true ) ;
		}
	}

	/**
	 * Define `LITESPEED_ON`
	 *
	 * @since 2.1
	 * @access public
	 */
	public function define_cache_on()
	{
		defined( 'LITESPEED_ALLOWED' ) && defined( 'LSCACHE_ADV_CACHE' ) && ! defined( 'LITESPEED_ON' ) && define( 'LITESPEED_ON', true ) ;

		// Use this for cache enabled setting check
		! defined( 'LITESPEED_ON_IN_SETTING' ) && define( 'LITESPEED_ON_IN_SETTING', true ) ;
	}

	/**
	 * For multisite installations, the single site options need to be updated with the network wide options.
	 *
	 * @since 1.0.13
	 * @access private
	 * @return array The updated options.
	 */
	private function construct_multisite_options()
	{
		$site_options = get_site_option( self::OPTION_NAME ) ;

		$this->_define_adv_cache( $site_options ) ;

		$options = get_option( self::OPTION_NAME ) ;
		if ( ! $options ) {
			$options = $this->get_default_options() ;
		}

		/**
		 * In case this is called outside the admin page
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;
		}

		// If don't have site options
		if ( ! $site_options || ! is_array( $site_options ) || ! is_plugin_active_for_network( 'litespeed-cache/litespeed-cache.php' ) ) {
			if ( $options[ self::OPID_ENABLED_RADIO ] === self::VAL_ON2 ) { // Default to cache on
				$this->define_cache_on() ;
			}
			return $options ;
		}

		// If network set to use primary setting
		if ( ! empty ( $site_options[ self::NETWORK_OPID_USE_PRIMARY ] ) ) {

			// save temparary cron setting
			$CRWL_CRON_ACTIVE = $options[ self::CRWL_CRON_ACTIVE ] ;

			// Get the primary site settings
			$options = get_blog_option( BLOG_ID_CURRENT_SITE, LiteSpeed_Cache_Config::OPTION_NAME, array() ) ;

			// crawler cron activation is separated
			$options[ self::CRWL_CRON_ACTIVE ] = $CRWL_CRON_ACTIVE ;
		}

		// If use network setting
		if ( $options[ self::OPID_ENABLED_RADIO ] === self::VAL_ON2 && $site_options[ self::NETWORK_OPID_ENABLED ] ) {
			$this->define_cache_on() ;
		}
		// Set network eanble to on
		if ( $site_options[ self::NETWORK_OPID_ENABLED ] ) {
			! defined( 'LITESPEED_NETWORK_ON' ) && define( 'LITESPEED_NETWORK_ON', true ) ;
		}

		// These two are not for single blog options
		unset( $site_options[ self::NETWORK_OPID_ENABLED ] ) ;
		unset( $site_options[ self::NETWORK_OPID_USE_PRIMARY ] ) ;

		// Append site options to single blog options
		$options = array_merge( $options, $site_options ) ;

		return $options ;
	}

	/**
	 * Get the list of configured options for the blog.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The list of configured options.
	 */
	public function get_options()
	{
		return $this->options ;
	}

	/**
	 * Get the selected configuration option.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $id Configuration ID.
	 * @return mixed Selected option if set, NULL if not.
	 */
	public function get_option( $id )
	{
		if ( isset( $this->options[$id] ) ) {
			return $this->options[$id] ;
		}

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Cfg] Invalid option ID ' . $id ) ;

		return NULL ;
	}

	/**
	 * Set the configured options.
	 *
	 * NOTE: No validation here. Do validate before use this function with LiteSpeed_Cache_Admin_Settings->validate_plugin_settings().
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array $new_cfg The new settings to update, which will be update $this->options too.
	 * @return array The result of update.
	 */
	public function update_options( $new_cfg = array() )
	{
		if ( ! empty( $new_cfg ) ) {
			$this->options = array_merge( $this->options, $new_cfg ) ;
		}
		return update_option( self::OPTION_NAME, $this->options ) ;
	}

	/**
	 * Save frontend url to private cached uri/no cache uri
	 *
	 * @since 1.3
	 * @access public
	 */
	public static function frontend_save()
	{
		if ( empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
			exit( 'no referer' ) ;
		}

		if ( ! $type = LiteSpeed_Cache_Router::verify_type() ) {
			exit( 'no type' ) ;
		}

		switch ( $type ) {
			case 'forced_cache' :
				$id = self::ITEM_FORCE_CACHE_URI ;
				break ;

			case 'private' :
				$id = self::ITEM_CACHE_URI_PRIV ;
				break ;

			case 'nonoptimize' :
				$id = self::ITEM_OPTM_EXCLUDES ;
				break ;

			case 'nocache' :
			default:
				$id = self::ITEM_EXCLUDES_URI ;
				break ;
		}

		$instance = self::get_instance() ;
		$list = $instance->get_item( $id ) ;

		$list[] = $_SERVER[ 'HTTP_REFERER' ] . '$' ;
		$list = LiteSpeed_Cache_Utility::sanitize_lines( $list, 'relative' ) ;

		update_option( $id, $list ) ;

		// Purge this page & redirect
		LiteSpeed_Cache_Purge::purge_front() ;
		exit() ;
	}

	/**
	 * Check if one user role is in vary group settings
	 *
	 * @since 1.2.0
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_vary_group( $role )
	{
		$group = 0 ;
		if ( array_key_exists( $role, $this->vary_groups ) ) {
			$group = $this->vary_groups[ $role ] ;
		}
		elseif ( $role === 'administrator' ) {
			$group = 99 ;
		}

		if ( $group ) {
			LiteSpeed_Cache_Log::debug2( '[Cfg] role in vary_group [group] ' . $group ) ;
		}

		return $group ;
	}

	/**
	 * Check if one user role is in exclude optimization group settings
	 *
	 * @since 1.6
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_exclude_optimization_roles( $role = null )
	{
		// Get user role
		if ( $role === null ) {
			$role = LiteSpeed_Cache_Router::get_role() ;
		}

		if ( ! $role ) {
			return false ;
		}

		return in_array( $role, $this->exclude_optimization_roles ) ? $role : false ;
	}

	/**
	 * Check if one user role is in exclude cache group settings
	 *
	 * @since 1.6.2
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_exclude_cache_roles( $role = null )
	{
		// Get user role
		if ( $role === null ) {
			$role = LiteSpeed_Cache_Router::get_role() ;
		}

		if ( ! $role ) {
			return false ;
		}

		return in_array( $role, $this->exclude_cache_roles ) ? $role : false ;
	}

	/**
	 * Get the configured purge options.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The list of purge options.
	 */
	public function get_purge_options()
	{
		return $this->purge_options ;
	}

	/**
	 * Check if the flag type of posts should be purged on updates.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $flag Post type. Refer to LiteSpeed_Cache_Config::PURGE_*
	 * @return boolean True if the post type should be purged, false otherwise.
	 */
	public function purge_by_post( $flag )
	{
		return in_array( $flag, $this->purge_options ) ;
	}

	/**
	 * Get item val
	 *
	 * @since 2.2.1
	 * @access public
	 */
	public function get_item( $k, $return_string = false )
	{
		$val = get_option( $k ) ;
		// Separately call default_item() to improve performance
		if ( ! $val ) {
			$val = $this->default_item( $k ) ;
		}

		if ( ! $return_string && ! is_array( $val ) ) {
			$val = $val ? explode( "\n", $val ) : array() ;
		}
		elseif ( $return_string && is_array( $val ) ) {
			$val = implode( "\n", $val ) ;
		}

		return $val ;
	}

	/**
	 * Get the plugin's site wide options.
	 *
	 * If the site wide options are not set yet, set it to default.
	 *
	 * @since 1.0.2
	 * @access public
	 * @return array Returns the current site options.
	 */
	public function get_site_options()
	{
		if ( ! is_multisite() ) {
			return null ;
		}
		$site_options = get_site_option( self::OPTION_NAME ) ;

		if ( isset( $site_options ) && is_array( $site_options ) ) {
			return $site_options ;
		}

		$default_site_options = $this->get_default_site_options() ;
		add_site_option( self::OPTION_NAME, $default_site_options ) ;

		return $default_site_options ;
	}


	/**
	 * Helper function to convert the options to replicate the input format.
	 *
	 * The only difference is the checkboxes.
	 *
	 * @since 1.0.15
	 * @access public
	 * @param array $options The options array to port to input format.
	 * @return array $options The options array with input format.
	 */
	public static function convert_options_to_input($options)
	{
		foreach ( $options as $key => $val ) {
			if ( $val === true ) {
				$options[$key] = self::VAL_ON ;
			}
			elseif ( $val === false ) {
				$options[$key] = self::VAL_OFF ;
			}
		}
		if ( isset($options[self::OPID_PURGE_BY_POST]) ) {
			$purge_opts = explode('.', $options[self::OPID_PURGE_BY_POST]) ;

			foreach ($purge_opts as $purge_opt) {
				$options['purge_' . $purge_opt] = self::VAL_ON ;
			}
		}

		// Convert CDN settings
		$mapping_fields = array(
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE
		) ;
		$cdn_mapping = array() ;
		if ( isset( $options[ self::ITEM_CDN_MAPPING ] ) && is_array( $options[ self::ITEM_CDN_MAPPING ] ) ) {
			foreach ( $options[ self::ITEM_CDN_MAPPING ] as $k => $v ) {// $k is numeric
				foreach ( $mapping_fields as $v2 ) {
					if ( empty( $cdn_mapping[ $v2 ] ) ) {
						$cdn_mapping[ $v2 ] = array() ;
					}
					$cdn_mapping[ $v2 ][ $k ] = ! empty( $v[ $v2 ] ) ? $v[ $v2 ] : false ;
				}
			}
		}
		if ( empty( $cdn_mapping ) ) {
			// At least it has one item same as in setting page
			foreach ( $mapping_fields as $v2 ) {
				$cdn_mapping[ $v2 ] = array( 0 => false ) ;
			}
		}
		$options[ self::ITEM_CDN_MAPPING ] = $cdn_mapping ;

		/**
		 * Convert Cookie Simulation in Crawler settings
		 * @since 2.8.1 Fixed warning and lost cfg when deactivate->reactivate in v2.8
		 */
		$id = self::ITEM_CRWL_COOKIES ;
		$crawler_cookies = array() ;
		if ( isset( $options[ $id ] ) && is_array( $options[ $id ] ) ) {
			$i = 0 ;
			foreach ( $options[ $id ] as $k => $v ) {
				$crawler_cookies[ 'name' ][ $i ] = $k ;
				$crawler_cookies[ 'vals' ][ $i ] = $v ;
				$i ++ ;
			}
		}
		$options[ $id ] = $crawler_cookies ;

		return $options ;
	}

	/**
	 * Get the difference between the current options and the default options.
	 *
	 * @since 1.0.11
	 * @access public
	 * @param array $default_options The default options.
	 * @param array $options The current options.
	 * @return array New options.
	 */
	public static function option_diff($default_options, $options)
	{
		$dkeys = array_keys($default_options) ;
		$keys = array_keys($options) ;
		$newkeys = array_diff($dkeys, $keys) ;
		if ( ! empty($newkeys) ) {
			foreach ( $newkeys as $newkey ) {
				$options[$newkey] = $default_options[$newkey]  ;

				$log = '[Added] ' . $newkey . ' = ' . $default_options[$newkey]  ;
				LiteSpeed_Cache_Log::debug( "[Cfg] option_diff $log" ) ;
			}
		}
		$retiredkeys = array_diff($keys, $dkeys)  ;
		if ( ! empty($retiredkeys) ) {
			foreach ( $retiredkeys as $retired ) {
				unset($options[$retired])  ;

				$log = '[Removed] ' . $retired  ;
				LiteSpeed_Cache_Log::debug( "[Cfg] option_diff $log" ) ;
			}
		}
		$options[self::OPID_VERSION] = LiteSpeed_Cache::PLUGIN_VERSION ;

		return $options ;
	}

	/**
	 * Verify that the options are still valid.
	 *
	 * This is used only when upgrading the plugin versions.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function plugin_upgrade()
	{
		$default_options = $this->get_default_options() ;
		$site_options = $this->get_site_options() ;

		// Skip count check if Use Primary Site Configurations is on
		if (
			$this->options[ self::OPID_VERSION ] == $default_options[ self::OPID_VERSION ] &&
			(
				( ! is_main_site() && ! empty ( $site_options[ self::NETWORK_OPID_USE_PRIMARY ] ) ) ||
				count( $default_options ) == count( $this->options )
			)
		) {
			return ;
		}

		/**
		 * Resave cdn cfg from lscfg to separate cfg when upgrade to v1.7
		 * @since 1.7
		 */
		if ( isset( $this->options[ 'cdn_url' ] ) ) {
			$cdn_mapping = array(
				self::ITEM_CDN_MAPPING_URL 		=> $this->options[ 'cdn_url' ],
				self::ITEM_CDN_MAPPING_INC_IMG 	=> $this->options[ 'cdn_inc_img' ],
				self::ITEM_CDN_MAPPING_INC_CSS 	=> $this->options[ 'cdn_inc_css' ],
				self::ITEM_CDN_MAPPING_INC_JS 	=> $this->options[ 'cdn_inc_js' ],
				self::ITEM_CDN_MAPPING_FILETYPE => $this->options[ 'cdn_filetype' ],
			) ;
			update_option( LiteSpeed_Cache_Config::ITEM_CDN_MAPPING, array( $cdn_mapping ) ) ;
			LiteSpeed_Cache_Log::debug( "[Cfg] plugin_upgrade option adding CDN map" ) ;
		}

		/**
		 * Move Exclude settings to separate item
		 * @since  2.3
		 */
		if ( isset( $this->options[ 'forced_cache_uri' ] ) ) {
			update_option( LiteSpeed_Cache_Config::ITEM_FORCE_CACHE_URI, $this->options[ 'forced_cache_uri' ] ) ;
		}
		if ( isset( $this->options[ 'cache_uri_priv' ] ) ) {
			update_option( LiteSpeed_Cache_Config::ITEM_CACHE_URI_PRIV, $this->options[ 'cache_uri_priv' ] ) ;
		}
		if ( isset( $this->options[ 'optm_excludes' ] ) ) {
			update_option( LiteSpeed_Cache_Config::ITEM_OPTM_EXCLUDES, $this->options[ 'optm_excludes' ] ) ;
		}
		if ( isset( $this->options[ 'excludes_uri' ] ) ) {
			update_option( LiteSpeed_Cache_Config::ITEM_EXCLUDES_URI, $this->options[ 'excludes_uri' ] ) ;
		}

		$this->options = self::option_diff( $default_options, $this->options ) ;

		$this->update_options() ;
		define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;

		LiteSpeed_Cache_Log::debug( "[Cfg] plugin_upgrade option changed" ) ;

		// Update img_optm table data for upgrading
		LiteSpeed_Cache_Data::get_instance() ;
	}

	/**
	 * Upgrade network options when the plugin is upgraded.
	 *
	 * @since 1.0.11
	 * @access public
	 */
	public function plugin_site_upgrade()
	{
		$default_options = $this->get_default_site_options() ;
		$options = $this->get_site_options() ;

		if ( $options[ self::OPID_VERSION ] == $default_options[ self::OPID_VERSION ] && count( $default_options ) == count( $options ) ) {
			return ;
		}

		$options = self::option_diff( $default_options, $options ) ;

		$res = update_site_option( self::OPTION_NAME, $options ) ;

		LiteSpeed_Cache_Log::debug( "[Cfg] plugin_upgrade option changed = $res\n" ) ;
	}

	/**
	 * Update the WP_CACHE variable in the wp-config.php file.
	 *
	 * If enabling, check if the variable is defined, and if not, define it.
	 * Vice versa for disabling.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param boolean $enable True if enabling, false if disabling.
	 * @return boolean True if the variable is the correct value, false if something went wrong.
	 */
	public static function wp_cache_var_setter( $enable )
	{
		if ( $enable ) {
			if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
				return true ;
			}
		}
		elseif ( ! defined( 'WP_CACHE' ) || ( defined( 'WP_CACHE' ) && ! WP_CACHE ) ) {
				return true ;
		}

		$file = ABSPATH . 'wp-config.php' ;

		if ( ! is_writeable( $file ) ) {
			$file = dirname( ABSPATH ) . '/wp-config.php' ; // todo: is the path correct?
			if ( ! is_writeable( $file ) ) {
				error_log( 'wp-config file not writable for \'WP_CACHE\'' ) ;
				return LiteSpeed_Cache_Admin_Error::E_CONF_WRITE ;
			}
		}

		$file_content = file_get_contents( $file ) ;

		if ( $enable ) {
			$count = 0 ;

			$new_file_content = preg_replace( '/[\/]*define\(.*\'WP_CACHE\'.+;/', "define('WP_CACHE', true);", $file_content, -1, $count ) ;
			if ( $count == 0 ) {
				$new_file_content = preg_replace( '/(\$table_prefix)/', "define('WP_CACHE', true);\n$1", $file_content ) ;
				if ( $count == 0 ) {
					$new_file_content = preg_replace( '/(\<\?php)/', "$1\ndefine('WP_CACHE', true);", $file_content, -1, $count ) ;
				}

				if ( $count == 0 ) {
					error_log( 'wp-config file did not find a place to insert define.' ) ;
					return LiteSpeed_Cache_Admin_Error::E_CONF_FIND ;
				}
			}
		}
		else {
			$new_file_content = preg_replace( '/define\(.*\'WP_CACHE\'.+;/', "define('WP_CACHE', false);", $file_content ) ;
		}

		file_put_contents( $file, $new_file_content ) ;
		return true ;
	}

	/**
	 * On plugin activation, load the default options.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int $count The count of blogs active in multisite.
	 */
	public function plugin_activation( $count )
	{


	}

	/**
	 * Set one config value directly
	 *
	 * @since  2.9
	 * @access private
	 */
	private function _set_cfg()
	{
		if ( empty( $_GET[ self::TYPE_SET ] ) || ! is_array( $_GET[ self::TYPE_SET ] ) ) {
			return ;
		}

		$cfg = $cfg_v = false ;
		foreach ( $_GET[ self::TYPE_SET ] as $k => $v ) {
			if ( ! isset( $this->options[ $k ] ) ) {
				continue ;
			}

			if ( is_bool( $this->options[ $k ] ) ) {
				$v = (bool) $v ;
			}

			$cfg = $k ;
			$cfg_v = $v ;
			break ;// only allow one
		}

		if ( ! $cfg ) {
			return ;
		}

		$options = $this->options ;
		// Get items
		foreach ( $this->stored_items() as $v ) {
			$options[ $v ] = $this->get_item( $v ) ;
		}

		// Change value
		$options[ $cfg ] = $cfg_v ;

		$output = LiteSpeed_Cache_Admin_Settings::get_instance()->validate_plugin_settings( $options, true ) ;
		$this->update_options( $output ) ;

		LiteSpeed_Cache_Log::debug( '[Cfg] Changed cfg ' . $cfg . ' to ' . var_export( $cfg_v, true ) ) ;

		$msg = __( 'Changed setting successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
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
			case self::TYPE_SET :
				$instance->_set_cfg() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
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
