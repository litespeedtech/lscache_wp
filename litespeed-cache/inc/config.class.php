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

	private $_options = array() ;
	private $_site_options = array() ;
	private $_default_options = array() ;

	protected $vary_groups ;
	protected $optm_exc_roles ;
	protected $cache_exc_roles ;
	protected $purge_options ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct()
	{
		$this->_default_options = $this->default_keys() ;

		// Check if conf exists or not. If not, create them in DB (won't change version if is converting v2.9- data)
		// Conf may be stale, upgrade later
		$this->_conf_db_init() ;

		// Load options first, network sites can override this later
		$this->load_options() ;

		/**
		 * Check if needs to upgrade conf version or not
		 * @since  3.0
		 */
		$this->_conf_upgrade() ;

		// Override conf if is network subsites and chose `Use Primary Config`
		$this->_try_load_site_options() ;

		// Check advanced_cache set (compabible for both network and single site)
		$this->_define_adv_cache() ;

		// Init global const cache on set
		if ( $this->_options[ self::O_CACHE ] === self::VAL_ON ) {
			$this->_options[ self::_CACHE ] = true ;
		}

		// Set cache on
		if ( $this->_options[ self::_CACHE ] ) {
			$this->define_cache_on() ;
		}

		$this->purge_options = explode('.', $this->_options[ self::O_PURGE_BY_POST xx ] ) ;

		// Vary group settings
		$this->vary_groups = $this->get_item( self::O_CACHE_VARY_GROUP ) ;

		// Exclude optimization role setting
		$this->optm_exc_roles = $this->get_item( self::O_OPTM_EXC_ROLES ) ;

		// Exclude cache role setting
		$this->cache_exc_roles = $this->get_item( self::O_CACHE_EXC_ROLES ) ;

		// Hook to options
		add_action( 'litespeed_init', array( $this, 'hook_options' ) ) ;

	}

	/**
	 * Load all latest options from DB
	 *
	 * Already load the lacking options with default values, won't insert them into DB. Inserting will be done on setting saving.
	 *
	 * @since  3.0
	 * @access public
	 */
	public function load_options( $blog_id = null, $dry_run = false )
	{
		$options = array() ;
		// No need to consider items yet as they won't be gotten directly from $this->_options but used in $this->get_item()
		foreach ( $this->_default_options as $k => $v ) {
			if ( ! is_null( $blog_id ) ) {
				$options[ $k ] = get_blog_option( $blog_id, self::conf_name( $k ), $v ) ;
			}
			else {
				$options[ $k ] = get_option( self::conf_name( $k ), $v ) ;
			}
		}

		// Set security key if not initialized yet
		todo: move this to init options
		$k = self::HASH ;
		if ( isset( $options[ $k ] ) && empty( $options[ $k ] ) ) {
			$options[ $k ] = Litespeed_String::rrand( 32 ) ;
			update_option( self::conf_name( $k ), $options[ $k ] ) ;
		}

		if ( ! $dry_run ) {
			$this->_options = $options ;
		}

		return $options ;
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

		$this->get_site_options() ;

		// $this->_define_adv_cache( $this->_site_options ) ;

		// If network set to use primary setting
		if ( ! empty ( $this->_site_options[ self::NETWORK_O_USE_PRIMARY ] ) ) {

			// save temparary cron setting as cron settings are per site
			$CRWL_CRON_ACTIVE = $this->_options[ self::O_CRWL ] ;

			// Get the primary site settings
			// If it's just upgraded, 2nd blog is being visited before primary blog, can just load default config (won't hurt as this could only happen shortly)
			$this->_options = $this->load_options( BLOG_ID_CURRENT_SITE ) ;

			// crawler cron activation is separated
			$this->_options[ self::O_CRWL ] = $CRWL_CRON_ACTIVE ;
		}

		// If use network setting
		if ( $this->_options[ self::O_CACHE ] === self::VAL_ON2 && $this->_site_options[ self::NETWORK_O_ENABLED ] ) {
			$this->_options[ self::_CACHE ] = true ;
		}
		// Set network eanble to on
		if ( $this->_site_options[ self::NETWORK_O_ENABLED ] ) {
			! defined( 'LITESPEED_NETWORK_ON' ) && define( 'LITESPEED_NETWORK_ON', true ) ;
		}

		// These two are not for single blog options
		// unset( $this->_site_options[ self::NETWORK_O_ENABLED ] ) ;
		// unset( $this->_site_options[ self::NETWORK_O_USE_PRIMARY ] ) ;

		// Append site options to single blog options
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
			if ( $this->_options[ self::O_CACHE ] === self::VAL_ON2 ) { // Default to cache on
				$this->_options[ self::_CACHE ] = true ;
			}
			return false ;
		}

		return true ;
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

			if ( $new_v !== $v ) {
				LiteSpeed_Cache_Log::debug( "[Conf] ** $k changed by hook [litespeed_option_$k] from " . var_export( $v, true ) . ' to ' . var_export( $new_v, true ) ) ;
				$this->_options[ $k ] = $new_v ;
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
		if ( array_key_exists( $k, $this->_options ) ) {
			LiteSpeed_Cache_Log::debug( "[Conf] ** $k forced value to " . var_export( $v, true ) ) ;
			$this->_options[ $k ] = $v ;
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
	private function _define_adv_cache()
	{
		if ( isset( $this->_options[ self::O_UTIL_CHECK_ADVCACHE ] ) && ! $this->_options[ self::O_UTIL_CHECK_ADVCACHE ] ) {
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
	 * @since 1.0.0
	 * @access public
	 * @param string $id Configuration ID.
	 * @return mixed Selected option if set, NULL if not.
	 */
	public function get_option( $id )
	{
		if ( isset( $this->_options[ $id ] ) ) {
			return $this->_options[ $id ] ;
		}

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( '[Conf] Invalid option ID ' . $id ) ;

		return NULL ;
	}

	/**
	 * Set the configured options.
	 *
	 * NOTE: No validation here. Do validate before use this function with LiteSpeed_Cache_Admin_Settings->validate_plugin_settings().
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array $new_cfg The new settings to update, which will be update $this->_options too.
	 * @return array The result of update.
	 */
	public function update_options( $new_cfg = array() )xx
	{
		if ( ! empty( $new_cfg ) ) {
			$this->_options = array_merge( $this->_options, $new_cfg ) ;
		}
		return update_option( self::OPTION_NAME, $this->_options ) ;
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
			LiteSpeed_Cache_Log::debug2( '[Conf] role in vary_group [group] ' . $group ) ;
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
	public function in_optm_exc_roles( $role = null )
	{
		// Get user role
		if ( $role === null ) {
			$role = LiteSpeed_Cache_Router::get_role() ;
		}

		if ( ! $role ) {
			return false ;
		}

		return in_array( $role, $this->optm_exc_roles ) ? $role : false ;
	}

	/**
	 * Check if one user role is in exclude cache group settings
	 *
	 * @since 1.6.2
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_cache_exc_roles( $role = null )
	{
		// Get user role
		if ( $role === null ) {
			$role = LiteSpeed_Cache_Router::get_role() ;
		}

		if ( ! $role ) {
			return false ;
		}

		return in_array( $role, $this->cache_exc_roles ) ? $role : false ;
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

		if ( $this->_site_options ) {
			return $this->_site_options ;
		}

		// Get site options
		$site_options = get_site_option( self::OPTION_NAME ) ;

		if ( isset( $site_options ) && is_array( $site_options ) ) {
			$this->_site_options = $site_options ;
			return $this->_site_options ;
		}

		$default_site_options = $this->get_default_site_options() ;
		add_site_option( self::OPTION_NAME, $default_site_options ) ;

		$this->_site_options = $default_site_options ;

		return $this->_site_options ;
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
		if ( isset($options[self::O_PURGE_BY_POST xx]) ) {
			$purge_opts = explode('.', $options[self::O_PURGE_BY_POST xx]) ;

			foreach ($purge_opts as $purge_opt) {
				$options['purge_' . $purge_opt] = self::VAL_ON ;
			}
		}

		// Convert CDN settings
		$mapping_fields = array(
			LiteSpeed_Cache_Config::CDN_MAPPING_URL,
			LiteSpeed_Cache_Config::CDN_MAPPING_INC_IMG,
			LiteSpeed_Cache_Config::CDN_MAPPING_INC_CSS,
			LiteSpeed_Cache_Config::CDN_MAPPING_INC_JS,
			LiteSpeed_Cache_Config::CDN_MAPPING_FILETYPE
		) ;
		$cdn_mapping = array() ;
		if ( isset( $options[ self::O_CDN_MAPPING ] ) && is_array( $options[ self::O_CDN_MAPPING ] ) ) {
			foreach ( $options[ self::O_CDN_MAPPING ] as $k => $v ) {// $k is numeric
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
		$options[ self::O_CDN_MAPPING ] = $cdn_mapping ;

		/**
		 * Convert Cookie Simulation in Crawler settings
		 * @since 2.8.1 Fixed warning and lost cfg when deactivate->reactivate in v2.8
		 */
		$id = self::O_CRWL_COOKIES ;
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
	 * Upgrade conf to latest format version from previous versions
	 *
	 * NOTE: Only for v3.0+
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _conf_upgrade()
	{
		if ( $this->_options[ self::_VERSION ] == $this->_default_options[ self::_VERSION ] ) ) {
			return ;
		}

		// Skip count check if `Use Primary Site Configurations` is on
		// Deprecated since v3.0 as network primary site didn't override the subsites conf yet
		// if ( ! is_main_site() && ! empty ( $this->_site_options[ self::NETWORK_O_USE_PRIMARY ] ) ) {
		// 	return ;
		// }

		// Update version to v3.0
		update_option( self::conf_name( self::_VERSION ), LiteSpeed_Cache::PLUGIN_VERSION ) ;
		LiteSpeed_Cache_Log::debug( '[Conf] Updated version to ' . LiteSpeed_Cache::PLUGIN_VERSION ) ;

		define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;
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

		if ( $options[ self::_VERSION ] == $default_options[ self::_VERSION ] && count( $default_options ) == count( $options ) ) {
			return ;
		}

		$options = self::option_diff( $default_options, $options ) ;

		$res = update_site_option( self::OPTION_NAME, $options ) ;

		LiteSpeed_Cache_Log::debug( "[Conf] plugin_upgrade option changed = $res\n" ) ;
	}

	/**
	 * Move all options in litespeed-cache-conf from v2.9- to separate records
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _conf_db_init()
	{
		$v = get_option( self::conf_name( self::_VERSION ) ) ;

		/**
		 * Previous version v2.9- doesn't have this record
		 * Or, its a new installation
		 */
		if ( $v ) {//  && version_compare( $v, '2.9', '>' )
			return ;
		}

		// Upgrade first
		$this->_conf_upgrade_stale( $previous_options ) ;xx


		// Init default options
		foreach ( $this->_default_options as $k => $v ) {
			// If the option existed, bypass updating
			add_option( self::conf_name( $k ), $v ) ;
		}

		LiteSpeed_Cache_Log::debug( '[Conf] Upgraded to separate items' ) ;

		// v3.0- version data converter
		if ( $previous_options ) {

			// Keep the previous conf in case client downgrades to previous v2.9- version
			// update_option( '_litespeed-cache-conf', $previous_options ) ;
			// delete_option( 'litespeed-cache-conf' ) ;
			// LiteSpeed_Cache_Log::debug( '[Conf] Deleted&Backup previous option item' ) ;
		}

		define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;
	}

	/**
	 * Upgrade the conf to latest version from previous data
	 *
	 * NOTE: Only for v2.9-
	 *
	 * @since 1.0.0
	 * @since  3.0 Run in frontend process due to auto upgrade being in frontend; Renamed from public `plugin_upgrade()` to private `_conf_upgrade_stale()`
	 * @access private
	 */
	private function _conf_upgrade_stale( $previous_options )
	{
		$previous_options = get_option( 'litespeed-cache-conf', array() ) ;
		foreach ( $this->_default_options as $k => $v ) {
			if ( ! isset( $previous_options[ $k ] ) ) {
				continue ;
			}

			// Convert previous O_ENABLED_RADIO to new O_CACHE
			if ( $k == self::O_CACHE ) {
				$v2 = isset( $previous_options[ 'radio_select' ] ) ? $previous_options[ 'radio_select' ] : $v ;
			}

			update_option( self::conf_name( $k ), $previous_options[ $k ] ) ;
		}

		LiteSpeed_Cache_Log::debug( '[Conf] Upgrading previous settings to separate items' ) ;

		conv to litespeed.conf.*

		litespeed-cache-exclude-cache-roles 		-> cache.exc_roles
		litespeed-cache-drop_qs 					-> cache.drop_qs
		litespeed-forced_cache_uri 					-> cache.force_uri
		litespeed-cache_uri_priv 					-> cache.priv_uri
		litespeed-excludes_uri 						-> cache.exc
		litespeed-cache-vary-group 					-> cache.vary_group
		litespeed-adv-purge_all_hooks 				-> purge.hook_all
		litespeed-object_global_groups 				-> object.global_groups
		litespeed-object_non_persistent_groups 		-> object.non_persistent_groups
		litespeed-media-lazy-img-excludes 			-> media.lazy_exc
		litespeed-media-lazy-img-cls-excludes 		-> media.lazy_cls_exc
		litespeed-media-webp_attribute 				-> img_optm.webp_attr
		litespeed-optm-css 							-> optm.ccss_con
		litespeed-optm_excludes 					-> optm.exc
		litespeed-optm-ccss-separate_posttype 		-> optm.ccss_sep_posttype
		litespeed-optm-css-separate_uri 			-> optm.ccss_sep_uri
		litespeed-optm-js-defer-excludes 			-> optm.js_defer_exc
		litespeed-cache-dns_prefetch 				-> optm.dns_prefetch
		litespeed-cache-exclude-optimization-roles 	-> optm.exc_roles
		litespeed-log_ignore_filters 				-> debug.log_no_filters
		litespeed-log_ignore_part_filters 			-> debug.log_no_part_filters
		litespeed-cdn-ori_dir 						-> cdn.ori_dir
		litespeed-cache-cdn_mapping 				-> cdn.mapping
		litespeed-crawler-as-uids 					-> crawler.roles
		litespeed-crawler-cookies 					-> crawler.cookies


		litespeed-setting-mode -> litespeed.setting.mode
		litespeed-media-need-pull -> litespeed.img_optm.need_pull
		litespeed-env-ref -> litespeed.env.ref
		litespeed-cache-cloudflare_status -> litespeed.cdn.cloudflare.status

		conv from old litespeed.conf.* to new litespeed.conf.*
		version -> _version

		esi_enabled			-> esi
		esi_cached_admbar	-> esi.cache_admbar
		esi_cached_commform	-> esi.cache_commform

		heartbeat			-> util.heartbeat
		cache_browser		-> util.browser_cache
		cache_browser_ttl	-> util.browser_cache_ttl
		instant_click		-> util.instant_click
		check_advancedcache	-> util.check_advcache
		use_http_for_https_vary -> util.no_https_vary

		purge_upgrade		-> purge.upgrade
		purge_by_post -		-> purge.post_all
		purge_by_post F		-> purge.post_f
		purge_by_post H		-> purge.post_h
		purge_by_post PGS	-> purge.post_p
		purge_by_post PGSRP	-> purge.post_pwrp
		purge_by_post A		-> purge.post_a
		purge_by_post Y		-> purge.post_y
		purge_by_post M		-> purge.post_m
		purge_by_post D		-> purge.post_d
		purge_by_post T		-> purge.post_t
		purge_by_post PT	-> purge.post_pt
		timed_urls 			-> purge.timed_urls
		timed_urls_time 	-> purge.timed_urls_time

		cache_priv 			-> cache.priv
		cache_commenter		-> cache.commenter
		cache_rest 			-> cache.rest
		cache_page_login	-> cache.page_login
		cache_favicon		-> cache.favicon
		cache_resources		-> cache.resources
		mobileview_enabled	-> cache.mobile
		mobileview_rules	-> cache.mobile_rules
		nocache_useragents 	-> cache.exc_useragents
		nocache_cookies 	-> cache.exc_cookies
		excludes_qs 		-> cache.exc_qs
		excludes_cat 		-> cache.exc_cat
		excludes_tag 		-> cache.exc_tag
		public_ttl			-> cache.ttl_pub
		private_ttl			-> cache.ttl_priv
		front_page_ttl		-> cache.ttl_frontpage
		feed_ttl			-> cache.ttl_feed
		login_cookie		-> cache.login_cookie
		404_ttl				-> cache.ttl_status 404=>xx
		403_ttl				-> cache.ttl_status 403=>xx
		500_ttl				-> cache.ttl_status 500=>xx

		debug_disable_all	-> debug.disable_all
		admin_ips 			-> debug.ips
		debug_level 		-> debug.level
		log_file_size		-> debug.filesize
		debug_cookie		-> debug.cookie
		collaps_qs			-> debug.collaps_qs
		log_filters 		-> debug.log_filters

		crawler_cron_active 	-> crawler
		crawler_include_posts 	-> crawler.inc_posts
		crawler_include_pages 	-> crawler.inc_pages
		crawler_include_cats 	-> crawler.inc_cats
		crawler_include_tags 	-> crawler.inc_tags
		crawler_excludes_cpt 	-> crawler.exc_cpt
		crawler_order_links 	-> crawler.order_links
		crawler_usleep 			-> crawler.usleep
		crawler_run_duration 	-> crawler.run_duration
		crawler_run_interval 	-> crawler.run_interval
		crawler_crawl_interval 	-> crawler.crawl_interval
		crawler_threads 		-> crawler.threads
		crawler_load_limit 		-> crawler.load_limit
		crawler_domain_ip 		-> crawler.domain_ip
		crawler_custom_sitemap 	-> crawler.custom_sitemap

		cache_object			-> object
		cache_object_kind		-> object.kind
		cache_object_host		-> object.host
		cache_object_port		-> object.port
		cache_object_life		-> object.life
		cache_object_persistent	-> object.persistent
		cache_object_admin		-> object.admin
		cache_object_transients	-> object.transients
		cache_object_db_id		-> object.db_id
		cache_object_user		-> object.user
		cache_object_pswd		-> object.psw

		cdn_ori				-> cdn.ori
		cdn_exclude 		-> cdn.exc
		cdn_remote_jquery	-> cdn.remote_jq
		cdn_cloudflare		-> cdn.cloudflare
		cdn_cloudflare_email-> cdn.cloudflare_email
		cdn_cloudflare_key	-> cdn.cloudflare_key
		cdn_cloudflare_name	-> cdn.cloudflare_name
		cdn_cloudflare_zone	-> cdn.cloudflare_zone

		media_img_lazy				-> media.lazy
		media_img_lazy_placeholder	-> media.lazy_placeholder
		media_placeholder_resp		-> media.placeholder_resp
		media_placeholder_resp_color-> media.placeholder_resp_color
		media_placeholder_resp_async-> media.placeholder_resp_async
		media_iframe_lazy			-> media.iframe_lazy
		media_img_lazyjs_inline		-> media.lazyjs_inline

		media_optm_auto			-> img_optm.auto
		media_optm_cron			-> img_optm.cron
		media_optm_ori			-> img_optm.ori
		media_rm_ori_bkup		-> img_optm.rm_bkup
		media_optm_webp			-> img_optm.webp
		media_optm_lossless		-> img_optm.lossless
		media_optm_exif			-> img_optm.exif
		media_webp_replace		-> img_optm.webp_replace
		media_webp_replace_srcset-> img_optm.webp_replace_srcset

		css_minify			-> optm.css_min
		css_inline_minify	-> optm.css_inline_min
		css_combine			-> optm.css_comb
		css_combined_priority-> optm.css_comb_priority
		css_http2			-> optm.css_http2
		css_exclude 		-> optm.css_exc
		js_minify			-> optm.js_min
		js_inline_minify	-> optm.js_inline_min
		js_combine			-> optm.js_comb
		js_combined_priority-> optm.js_comb_priority
		js_http2			-> optm.js_http2
		js_exclude 			-> optm.js_exc
		optimize_ttl		-> optm.ttl
		html_minify			-> optm.html_min
		optm_qs_rm			-> optm.qs_rm
		optm_ggfonts_rm		-> optm.ggfonts_rm
		optm_css_async		-> optm.css_async
		optm_ccss_gen		-> optm.ccss_gen
		optm_ccss_async		-> optm.ccss_async
		optm_css_async_inline-> optm.css_async_inline
		optm_js_defer		-> optm.js_defer
		optm_emoji_rm		-> optm.emoji_rm
		optm_exclude_jquery	-> optm.exc_jq
		optm_ggfonts_async	-> optm.ggfonts_async
		optm_max_size		-> optm.max_size
		optm_rm_comment		-> optm.rm_comment


		/**
		 * Resave cdn cfg from lscfg to separate cfg when upgrade to v1.7
		 * @since 1.7
		 */
		if ( isset( $previous_options[ 'cdn_url' ] ) ) {
			$cdn_mapping = array(
				self::CDN_MAPPING_URL 		=> $previous_options[ 'cdn_url' ],
				self::CDN_MAPPING_INC_IMG 	=> $previous_options[ 'cdn_inc_img' ],
				self::CDN_MAPPING_INC_CSS 	=> $previous_options[ 'cdn_inc_css' ],
				self::CDN_MAPPING_INC_JS 	=> $previous_options[ 'cdn_inc_js' ],
				self::CDN_MAPPING_FILETYPE => $previous_options[ 'cdn_filetype' ],
			) ;
			add_option( LiteSpeed_Cache_Config::O_CDN_MAPPING, array( $cdn_mapping ) ) ;
			LiteSpeed_Cache_Log::debug( "[Conf] plugin_upgrade option adding CDN map" ) ;
		}

		/**
		 * Move Exclude settings to separate item
		 * @since  2.3
		 */
		if ( isset( $previous_options[ 'forced_cache_uri' ] ) ) {
			add_option( LiteSpeed_Cache_Config::O_CACHE_FORCE_URI, $previous_options[ 'forced_cache_uri' ] ) ;
		}
		if ( isset( $previous_options[ 'cache_uri_priv' ] ) ) {
			add_option( LiteSpeed_Cache_Config::O_CACHE_PRIV_URI, $previous_options[ 'cache_uri_priv' ] ) ;
		}
		if ( isset( $previous_options[ 'optm_excludes' ] ) ) {
			add_option( LiteSpeed_Cache_Config::O_OPTM_EXC, $previous_options[ 'optm_excludes' ] ) ;
		}
		if ( isset( $previous_options[ 'excludes_uri' ] ) ) {
			add_option( LiteSpeed_Cache_Config::O_CACHE_EXC, $previous_options[ 'excludes_uri' ] ) ;
		}

		// Update img_optm table data for upgrading
		// NOTE: no new change since v3.0 yet
		LiteSpeed_Cache_Data::get_instance() ;
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
	private function _set_conf()
	{
		if ( empty( $_GET[ self::TYPE_SET ] ) || ! is_array( $_GET[ self::TYPE_SET ] ) ) {
			return ;
		}

		$options = $this->_options ;
		// Get items
		foreach ( $this->stored_items() xx as $v ) {
			$options[ $v ] = $this->get_item( $v ) ;
		}

		$changed = false ;
		foreach ( $_GET[ self::TYPE_SET ] as $k => $v ) {
			if ( ! isset( $options[ $k ] ) ) {
				continue ;
			}

			if ( is_bool( $options[ $k ] ) ) {
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
