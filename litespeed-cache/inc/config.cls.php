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
defined( 'WPINC' ) || exit ;


class LiteSpeed_Cache_Config extends LiteSpeed_Cache_Const
{
	private static $_instance ;

	const TYPE_SET = 'set' ;

	private $_options = array() ;
	private $_site_options = array() ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct()
	{
		// Check if conf exists or not. If not, create them in DB (won't change version if is converting v2.9- data)
		// Conf may be stale, upgrade later
		$this->_conf_db_init() ;

		// Load options first, network sites can override this later
		$this->load_options() ;

		// Override conf if is network subsites and chose `Use Primary Config`
		$this->_try_load_site_options() ;

		/**
		 * Detect if has quic.cloud set
		 * @since  2.9.7
		 */
		if ( $this->_options[ self::O_CDN_QUIC ] ) {
			! defined( 'LITESPEED_ALLOWED' ) &&  define( 'LITESPEED_ALLOWED', true ) ;
		}

		$this->define_cache() ;

		// Hook to options
		add_action( 'litespeed_init', array( $this, 'hook_options' ) ) ;

	}

	/**
	 * Init conf related data
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _conf_db_init()
	{
		$ver = get_option( self::conf_name( self::_VERSION ) ) ;

		/**
		 * Version is less than v3.0, or, is a new installation
		 */
		if ( ! $ver ) {
			// Try upgrade first (network will upgrade inside too)
			LiteSpeed_Cache_Data::get_instance()->try_upgrade_conf_3_0() ;
		}
		else {
			! defined( 'LSCWP_CUR_V' ) && define( 'LSCWP_CUR_V', $ver ) ;
		}

		/**
		 * Upgrade conf
		 */
		if ( $ver && $ver != LiteSpeed_Cache::PLUGIN_VERSION ) {
			// Plugin version will be set inside
			// Site plugin upgrade & version change will do in load_site_conf
			LiteSpeed_Cache_Data::get_instance()->conf_upgrade( $ver ) ;
		}

		/**
		 * Sync latest new options
		 */
		if ( ! $ver || $ver != LiteSpeed_Cache::PLUGIN_VERSION ) {
			// Load default values
			$this->_default_options = $this->default_vals() ;

			// Init new default/missing options
			foreach ( $this->_default_options as $k => $v ) {
				// If the option existed, bypass updating
				// Bcos we may ask clients to deactivate for debug temporarily, we need to keep the current cfg in deactivation, hence we need to only try adding default cfg when activating.
				add_option( self::conf_name( $k ), $v ) ;
			}
		}
	}

	/**
	 * Load all latest options from DB
	 *
	 * @since  3.0
	 * @access public
	 */
	public function load_options( $blog_id = null, $dry_run = false )
	{
		$options = array() ;
		foreach ( $this->_default_options as $k => $v ) {
			if ( ! is_null( $blog_id ) ) {
				$options[ $k ] = get_blog_option( $blog_id, self::conf_name( $k ), $v ) ;
			}
			else {
				$options[ $k ] = get_option( self::conf_name( $k ), $v ) ;
			}
		}

		if ( $dry_run ) {
			return $options ;
		}

		// Bypass site special settings
		if ( $blog_id !== null ) {
			$options = array_diff_key( $options, array_flip( self::SINGLE_SITE_OPTIONS ) ) ;
		}

		$this->_options = $options ;
	}

	/**
	 * For multisite installations, the single site options need to be updated with the network wide options.
	 *
	 * @since 1.0.13
	 * @access private
	 * @return array The updated options.
	 */
	private function _try_load_site_options()
	{
		if ( ! $this->_if_need_site_options() ) {
			return ;
		}

		$this->_conf_site_db_init() ;

		$this->load_site_options() ;

		// If network set to use primary setting
		if ( ! empty ( $this->_site_options[ self::NETWORK_O_USE_PRIMARY ] ) ) {
			// Get the primary site settings
			// If it's just upgraded, 2nd blog is being visited before primary blog, can just load default config (won't hurt as this could only happen shortly)
			$this->load_options( BLOG_ID_CURRENT_SITE ) ;
		}

		// Overwrite single blog options with site options
		foreach ( $this->_default_options as $k => $v ) {
			if ( isset( $this->_site_options[ $k ] ) ) {
				$this->_options[ $k ] = $this->_site_options[ $k ] ;
			}
		}
	}

	/**
	 * Check if needs to load site_options for network sites
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _if_need_site_options()
	{
		if ( ! is_multisite() ) {
			return false ;
		}

		// Check if needs to use site_options or not
		// todo: check if site settings are separate bcos it will affect .htaccess

		/**
		 * In case this is called outside the admin page
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;
		}
		// If is not activated on network, it will not have site options
		if ( ! is_plugin_active_for_network( LiteSpeed_Cache::PLUGIN_FILE ) ) {
			if ( $this->_options[ self::O_CACHE ] == self::VAL_ON2 ) { // Default to cache on
				$this->_options[ self::_CACHE ] = true ;
			}
			return false ;
		}

		return true ;
	}

	/**
	 * Init site conf and upgrade if necessary
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _conf_site_db_init()
	{
		$ver = get_site_option( self::conf_name( self::_VERSION ) ) ;

		/**
		 * Upgrade conf
		 */
		if ( $ver && $ver != LiteSpeed_Cache::PLUGIN_VERSION ) {
			// Site plugin versin will change inside
			LiteSpeed_Cache_Data::get_instance()->conf_site_upgrade( $ver ) ;
		}

		/**
		 * Is a new installation
		 */
		if ( ! $ver || $ver != LiteSpeed_Cache::PLUGIN_VERSION ) {
			// Load default values
			$this->_default_site_options = $this->default_site_vals() ;

			// Init new default/missing options
			foreach ( $this->_default_site_options as $k => $v ) {
				// If the option existed, bypass updating
				add_site_option( self::conf_name( $k ), $v ) ;
			}
		}
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
	public function load_site_options()
	{
		if ( ! is_multisite() || $this->_site_options ) {
			return $this->_site_options ;
		}

		// Load all site options
		foreach ( $this->_default_site_options as $k => $v ) {
			$this->_site_options[ $k ] = get_site_option( self::conf_name( $k ), $v ) ;
		}

		return $this->_site_options ;
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
		foreach ( $this->_options as $k => $v ) {
			$new_v = apply_filters( "litespeed_option_$k", $v ) ;

			if ( $new_v === $v ) {
				continue ;
			}

			LiteSpeed_Cache_Log::debug( "[Conf] ** $k changed by hook [litespeed_option_$k] from " . var_export( $v, true ) . ' to ' . var_export( $new_v, true ) ) ;
			$this->_options[ $k ] = $new_v ;
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
		if ( ! array_key_exists( $k, $this->_options ) ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( "[Conf] ** $k forced value to " . var_export( $v, true ) ) ;
		$this->_options[ $k ] = $v ;
	}

	/**
	 * Define `_CACHE` const in options ( for both single and network )
	 *
	 * @since  3.0
	 * @access public
	 */
	public function define_cache()
	{
		// Check advanced_cache setting (compatible for both network and single site)
		if ( ! $this->_options[ self::O_UTIL_CHECK_ADVCACHE ] ) {
			! defined( 'LSCACHE_ADV_CACHE' ) && define( 'LSCACHE_ADV_CACHE', true ) ;
		}

		// Init global const cache on setting
		$this->_options[ self::_CACHE ] = false ;
		if ( $this->_options[ self::O_CACHE ] == self::VAL_ON || $this->_options[ self::O_CDN_QUIC ] ) {
			$this->_options[ self::_CACHE ] = true ;
		}

		// Check network
		if ( ! $this->_if_need_site_options() ) {
			// Set cache on
			$this->_define_cache_on() ;
			return ;
		}

		// If use network setting
		if ( $this->_options[ self::O_CACHE ] == self::VAL_ON2 && $this->_site_options[ self::NETWORK_O_ENABLED ] ) {
			$this->_options[ self::_CACHE ] = true ;
		}

		$this->_define_cache_on() ;
	}

	/**
	 * Define `LITESPEED_ON`
	 *
	 * @since 2.1
	 * @access private
	 */
	private function _define_cache_on()
	{
		if ( ! $this->_options[ self::_CACHE ] ) {
			return ;
		}

		defined( 'LITESPEED_ALLOWED' ) && defined( 'LSCACHE_ADV_CACHE' ) && ! defined( 'LITESPEED_ON' ) && define( 'LITESPEED_ON', true ) ;

		// Use this for cache enabled setting check
		! defined( 'LITESPEED_ON_IN_SETTING' ) && define( 'LITESPEED_ON_IN_SETTING', true ) ;
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
		return $this->_options ;
	}

	/**
	 * Get the selected configuration option.
	 *
	 * @since 2.9.8
	 * @access public
	 * @param string $id Configuration ID.
	 * @return mixed Selected option if set, NULL if not.
	 */
	public function option( $id )
	{
		if ( isset( $this->_options[ $id ] ) ) {
			return $this->_options[ $id ] ;
		}

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Conf] Invalid option ID ' . $id ) ;

		return NULL ;
	}

	/**
	 * Save option
	 *
	 * @since  3.0
	 * @access public
	 */
	public function update_confs( $the_matrix = false )
	{
		if ( $the_matrix ) {
			foreach ( $the_matrix as $id => $val ) {
				$this->update( $id, $val ) ;
			}
		}

		// Update related files
		LiteSpeed_Cache_Activation::get_instance()->update_files() ;

		/**
		 * CDN related actions - Cloudflare
		 */
		LiteSpeed_Cache_CDN_Cloudflare::get_instance()->try_refresh_zone() ;

		/**
		 * CDN related actions - QUIC.cloud
		 * @since 2.3
		 */
		LiteSpeed_Cache_CDN_Quic::try_sync_config() ;

	}

	/**
	 * Save option
	 *
	 * @since  3.0
	 * @access public
	 */
	public function update( $id, $val )
	{
		// Bypassed this bcos $this->_options could be changed by force_option()
		// if ( $this->_options[ $id ] === $val ) {
		// 	return ;
		// }

		if ( $id == self::_VERSION ) {
			return ;
		}

		if ( ! array_key_exists( $id, $this->_default_options ) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Conf] Invalid option ID ' . $id ) ;
			return ;
		}

		// Validate type
		if ( is_bool( $this->_default_options[ $id ] ) ) {
			$max = $this->_conf_multi_switch( $id ) ;
			if ( $max && $val > 1 ) {
				$val %= $max + 1 ;
			}
			else {
				$val = (bool) $val ;
			}
		}
		elseif ( is_array( $this->_default_options[ $id ] ) ) {
			// from textarea input
			if ( ! is_array( $val ) ) {
				$val = LiteSpeed_Cache_Utility::sanitize_lines( $val, $this->_conf_filter( $id ) ) ;
			}
		}
		elseif ( ! is_string( $this->_default_options[ $id ] ) ) {
			$val = (int) $val ;
		}
		else {
			// Check if the string has a limit set
			$val = $this->_conf_string_val( $id, $val ) ;
		}

		// Save data
		update_option( $this->conf_name( $id ), $val ) ;

		// Handle purge if setting changed
		if ( $this->_options[ $id ] != $val ) {

			// Check if need to fire a purge or not
			if ( $this->_conf_purge( $id ) ) {
				$diff = array_diff( $val, $this->_options[ $id ] ) ;
				$diff2 = array_diff( $this->_options[ $id ], $val ) ;
				$diff = array_merge( $diff, $diff2 ) ;
				// If has difference
				foreach ( $diff as $v ) {
					$v = ltrim( $v, '^' ) ;
					$v = rtrim( $v, '$' ) ;
					LiteSpeed_Cache_Purge::get_instance()->purgeby_url_cb( $v ) ;
				}
			}

			// Check if need to do a purge all or not
			if ( $this->_conf_purge_all( $id ) ) {
				LiteSpeed_Cache_Purge::purge_all( 'conf changed [id] ' . $id ) ;
			}

			// Check if need to purge a tag
			if ( $tag = $this->_conf_purge_tag( $id ) ) {
				LiteSpeed_Cache_Purge::add( $tag ) ;
			}
		}

		// No need to update cron here, Cron will register in each init

		// Update in-memory data
		$this->_options[ $id ] = $val ;
	}

	/**
	 * Save network option
	 *
	 * @since  3.0
	 * @access public
	 */
	public function network_update( $id, $val )
	{

		if ( ! array_key_exists( $id, $this->_default_site_options ) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Conf] Invalid network option ID ' . $id ) ;
			return ;
		}

		// Validate type
		if ( is_bool( $this->_default_site_options[ $id ] ) ) {
			$max = $this->_conf_multi_switch( $id ) ;
			if ( $max && $val > 1 ) {
				$val %= $max + 1 ;
			}
			else {
				$val = (bool) $val ;
			}
		}
		elseif ( is_array( $this->_default_site_options[ $id ] ) ) {
			// from textarea input
			if ( ! is_array( $val ) ) {
				$val = LiteSpeed_Cache_Utility::sanitize_lines( $val, $this->_conf_filter( $id ) ) ;
			}
		}
		elseif ( ! is_string( $this->_default_site_options[ $id ] ) ) {
			$val = (int) $val ;
		}
		else {
			// Check if the string has a limit set
			$val = $this->_conf_string_val( $id, $val ) ;
		}

		// Save data
		update_site_option( $this->conf_name( $id ), $val ) ;

		// Handle purge if setting changed
		if ( $this->_site_options[ $id ] != $val ) {
			// Check if need to do a purge all or not
			if ( $this->_conf_purge_all( $id ) ) {
				LiteSpeed_Cache_Purge::purge_all( '[Conf] Network conf changed [id] ' . $id ) ;
			}
		}

		// No need to update cron here, Cron will register in each init

		// Update in-memory data
		$this->_site_options[ $id ] = $val ;

		if ( isset( $this->_options[ $id ] ) ) {
			$this->_options[ $id ] = $val ;
		}
	}

	/**
	 * Check if one user role is in exclude optimization group settings
	 *
	 * @since 1.6
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_optm_exc_roles( $role = null )
	{
		// Get user role
		if ( $role === null ) {
			$role = LiteSpeed_Cache_Router::get_role() ;
		}

		if ( ! $role ) {
			return false ;
		}

		return in_array( $role, $this->option( self::O_OPTM_EXC_ROLES ) ) ? $role : false ;
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
				LiteSpeed_Cache_Log::debug( "[Conf] option_diff $log" ) ;
			}
		}
		$retiredkeys = array_diff($keys, $dkeys)  ;
		if ( ! empty($retiredkeys) ) {
			foreach ( $retiredkeys as $retired ) {
				unset($options[$retired])  ;

				$log = '[Removed] ' . $retired  ;
				LiteSpeed_Cache_Log::debug( "[Conf] option_diff $log" ) ;
			}
		}
		$options[self::_VERSION] = LiteSpeed_Cache::PLUGIN_VERSION ;

		return $options ;
	}

	/**
	 * Set one config value directly
	 *
	 * @since  2.9
	 * @access private
	 */
	private function _set_conf()
	{exit('');
		if ( empty( $_GET[ self::TYPE_SET ] ) || ! is_array( $_GET[ self::TYPE_SET ] ) ) {
			return ;
		}

		$options = $this->_options ;
		// Get items
		foreach ( $this->stored_items() as $v ) {//xxx
			$options[ $v ] = $this->get_item( $v ) ;
		}

		$changed = false ;
		foreach ( $_GET[ self::TYPE_SET ] as $k => $v ) {
			if ( ! isset( $options[ $k ] ) ) {
				continue ;
			}

			if ( is_bool( $options[ $k ] ) ) {//xx
				$v = (bool) $v ;
			}

			// Change for items
			if ( is_array( $v ) && is_array( $options[ $k ] ) ) {
				$changed = true ;

				$options[ $k ] = array_merge( $options[ $k ], $v ) ;

				LiteSpeed_Cache_Log::debug( '[Conf] Appended to item [' . $k . ']: ' . var_export( $v, true ) ) ;
			}

			// Chnage for single option
			if ( ! is_array( $v ) ) {
				$changed = true ;

				$options[ $k ] = $v ;

				LiteSpeed_Cache_Log::debug( '[Conf] Changed [' . $k . '] to ' . var_export( $v, true ) ) ;
			}

		}

		if ( ! $changed ) {
			return ;
		}

		$output = LiteSpeed_Cache_Admin_Settings::get_instance()->validate_plugin_settings( $options, true ) ; // Purge will be auto run in validating items when found diff
		// Save settings now (options & items)
		foreach ( $output as $k => $v ) {
			update_option( self::conf_name( $k ), $v ) ;
		}

		$msg = __( 'Changed setting successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		// Redirect if changed frontend URL
		if ( ! empty( $_GET[ 'redirect' ] ) ) {
			wp_redirect( $_GET[ 'redirect' ] ) ;
			exit() ;
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
			case self::TYPE_SET :
				$instance->_set_conf() ;
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
