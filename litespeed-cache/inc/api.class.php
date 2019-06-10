<?php
/**
 * The plugin API class.
 *
 * @since      	1.1.3
 * @since  		1.4 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_API extends LiteSpeed_Cache_Const
{
	const VERSION =	LiteSpeed_Cache::PLUGIN_VERSION ;

	const TYPE_FEED =						LiteSpeed_Cache_Tag::TYPE_FEED ;
	const TYPE_FRONTPAGE =					LiteSpeed_Cache_Tag::TYPE_FRONTPAGE ;
	const TYPE_HOME =						LiteSpeed_Cache_Tag::TYPE_HOME ;
	const TYPE_PAGES =						LiteSpeed_Cache_Tag::TYPE_PAGES ;
	const TYPE_PAGES_WITH_RECENT_POSTS =	LiteSpeed_Cache_Tag::TYPE_PAGES_WITH_RECENT_POSTS ;
	const TYPE_ERROR =						LiteSpeed_Cache_Tag::TYPE_ERROR ;
	const TYPE_POST =						LiteSpeed_Cache_Tag::TYPE_POST ;
	const TYPE_ARCHIVE_POSTTYPE =			LiteSpeed_Cache_Tag::TYPE_ARCHIVE_POSTTYPE ;
	const TYPE_ARCHIVE_TERM =				LiteSpeed_Cache_Tag::TYPE_ARCHIVE_TERM ;
	const TYPE_AUTHOR =						LiteSpeed_Cache_Tag::TYPE_AUTHOR ;
	const TYPE_ARCHIVE_DATE =				LiteSpeed_Cache_Tag::TYPE_ARCHIVE_DATE ;
	const TYPE_BLOG =						LiteSpeed_Cache_Tag::TYPE_BLOG ;
	const TYPE_LOGIN =						LiteSpeed_Cache_Tag::TYPE_LOGIN ;
	const TYPE_URL =						LiteSpeed_Cache_Tag::TYPE_URL ;
	const TYPE_WIDGET =						LiteSpeed_Cache_Tag::TYPE_WIDGET ;

	const PARAM_NAME =				LiteSpeed_Cache_ESI::PARAM_NAME ;
	const WIDGET_OPID_ESIENABLE =	LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE ;
	const WIDGET_OPID_TTL =			LiteSpeed_Cache_ESI::WIDGET_OPID_TTL ;

	/**
	 * Disable All
	 *
	 * @since 2.9.7.2
	 * @access public
	 */
	public static function disable_all( $reason )
	{
		self::debug( '[API] Disabled_all due to ' . $reason ) ;

		! defined( 'LITESPEED_DISABLE_ALL' ) && define( 'LITESPEED_DISABLE_ALL', true ) ;
	}

	/**
	 * Force to set an option
	 * Note: it will only affect the AFTER usage of that option
	 *
	 * @since 2.6
	 * @access public
	 */
	public static function force_option( $k, $v )
	{
		LiteSpeed_Cache_Config::get_instance()->force_option( $k, $v ) ;
	}

	/**
	 * Start a to-be-removed html wrapper
	 *
	 * @since 1.4
	 * @access public
	 */
	public static function clean_wrapper_begin( $counter = false )
	{
		return LiteSpeed_Cache_GUI::clean_wrapper_begin( $counter ) ;
	}

	/**
	 * End a to-be-removed html wrapper
	 *
	 * @since 1.4
	 * @access public
	 */
	public static function clean_wrapper_end( $counter = false )
	{
		return LiteSpeed_Cache_GUI::clean_wrapper_end( $counter ) ;
	}

	/**
	 * Compare version
	 *
	 * @since 1.3
	 * @access public
	 */
	public static function v( $v )
	{
		return version_compare( self::VERSION, $v, '>=' ) ;
	}

	/**
	 * Set mobile
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_mobile()
	{
		LiteSpeed_Cache_Control::set_mobile() ;
	}

	/**
	 * Set cache status to not cacheable
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_cache_private()
	{
		LiteSpeed_Cache_Control::set_private() ;
	}

	/**
	 * Set cache status to no vary
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function set_cache_no_vary()
	{
		LiteSpeed_Cache_Control::set_no_vary() ;
	}

	/**
	 * Set cache status to not cacheable
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_nocache( $reason = false )
	{
		LiteSpeed_Cache_Control::set_nocache( $reason ?: 'api' ) ;
	}

	/**
	 * Set cache status to cacheable ( By default cacheable status will be set when called WP hook `wp` )
	 *
	 * @since 2.2
	 * @access public
	 */
	public static function set_cacheable( $reason )
	{
		LiteSpeed_Cache_Control::set_cacheable( $reason ) ;
	}

	/**
	 * Set cache status to force cacheable ( Will ignore most kinds of non-cacheable conditions )
	 *
	 * @since 2.2
	 * @access public
	 */
	public static function set_force_cacheable( $reason )
	{
		LiteSpeed_Cache_Control::force_cacheable( $reason ) ;
	}

	/**
	 * Set cache to force public cache if cacheable ( Will ignore most kinds of non-cacheable conditions )
	 *
	 * @since 2.9.7.2
	 * @access public
	 */
	public static function set_force_public( $reason )
	{
		LiteSpeed_Cache_Control::set_public_forced( $reason ) ;
	}

	/**
	 * Get current not cacheable status
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function not_cacheable()
	{
		return ! LiteSpeed_Cache_Control::is_cacheable() ;
	}

	/**
	 * Set cache control ttl to use frontpage ttl
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_use_frontpage_ttl()
	{
		LiteSpeed_Cache_Control::set_custom_ttl( self::config( self::OPID_FRONT_PAGE_TTL ) ) ;
	}

	/**
	 * Set cache control ttl
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public static function set_ttl( $val )
	{
		LiteSpeed_Cache_Control::set_custom_ttl( $val ) ;
	}

	/**
	 * Get current cache control ttl
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public static function get_ttl()
	{
		return LiteSpeed_Cache_Control::get_ttl() ;
	}

	/**
	 * Add public tag to cache
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function tag_add($tags)
	{
		LiteSpeed_Cache_Tag::add($tags) ;
	}

	/**
	 * Add public tag to cache
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public static function add_private( $tags )
	{
		LiteSpeed_Cache_Tag::add_private( $tags ) ;
	}

	/**
	 * Hook new vary cookies to vary finialization
	 *
	 * @since 2.6
	 * @access public
	 */
	public static function hook_vary_add( $hook )
	{
		add_action( 'litespeed_vary_add', $hook ) ;
	}

	/**
	 * Add a new vary cookie
	 *
	 * @since 1.1.3
	 * @since  2.7.1 Changed to filter hook instead of `LiteSpeed_Cache_Vary::add()`
	 * @access public
	 */
	public static function vary_add( $vary, $priority = 10 )
	{
		add_filter( 'litespeed_vary_cookies', function( $cookies ) use( $vary ) {
			if ( ! is_array( $vary ) ) {
				$vary = array( $vary ) ;
			}
			$cookies = array_merge( $cookies, $vary ) ;
			return $cookies ;
		}, $priority ) ;
	}

	/**
	 * Hook vary cookies to vary finialization
	 *
	 * @since 2.7.1
	 * @access public
	 */
	public static function filter_vary_cookies( $hook, $priority = 10 )
	{
		add_filter( 'litespeed_vary_cookies', $hook, $priority ) ;
	}

	/**
	 * Alter default vary cookie value
	 *
	 * Default vary cookie is an array before finalization, after that it will be combined to a string and store as default vary cookie name
	 *
	 * @since 2.6
	 * @access public
	 */
	public static function vary( $k, $v, $default = null )
	{
		if ( $v === $default ) {
			return ;
		}
		LiteSpeed_Cache_Vary::append( $k, $v ) ;
	}

	/**
	 * Hook vary tags to default vary finialization
	 *
	 * @since 1.7.2
	 * @access public
	 */
	public static function hook_vary_finalize( $hook )
	{
		add_filter( 'litespeed_vary', $hook ) ;
	}

	/**
	 * Force finalize vary even if its in an AJAX call
	 *
	 * @since 2.6
	 * @access public
	 */
	public static function force_vary()
	{
		LiteSpeed_Cache_Vary::can_ajax_vary() ;
	}

	/**
	 * Hook vary appending to vary
	 *
	 * NOTE: This will add vary to rewrite rule
	 *
	 * @since 1.1.3
	 * @since  2.7.1 This didn't work in 2.7- due to used add_action not filter
	 * @access public
	 */
	public static function hook_vary( $hook )
	{
		add_filter( 'litespeed_cache_api_vary', $hook ) ;
	}

	/**
	 * Purge all action
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function purge_all()
	{
		LiteSpeed_Cache_Purge::purge_all() ;
	}

	/**
	 * Purge all private
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public static function purge_private_all()
	{
		LiteSpeed_Cache_Purge::add_private( '*' ) ;
	}

	/**
	 * Purge private tag
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public static function purge_private( $tags )
	{
		LiteSpeed_Cache_Purge::add_private( $tags ) ;
	}

	/**
	 * Purge single action
	 *
	 * @since 1.3
	 * @access public
	 * @param  int $pid The ID of a post
	 */
	public static function purge_post( $pid )
	{
		LiteSpeed_Cache_Purge::purge_post( $pid ) ;
	}

	/**
	 * Add purge tags
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function purge($tags)
	{
		LiteSpeed_Cache_Purge::add($tags) ;
	}

	/**
	 * Build a switch div html snippet
	 *
	 * @since 1.1.3
	 * @access public
	 * @param  string $id
	 * @param  boolean $return   Return the html or echo it
	 */
	public static function build_switch( $id, $checked = null, $return = false )
	{
		return LiteSpeed_Cache_Admin_Display::get_instance()->build_switch( $id, $checked, $return ) ;
	}

	/**
	 * Filter the value for checkbox via input and id (enabled/disabled)
	 *
	 * @since  1.1.6
	 * @access public
	 * @param int $input The whole input array
	 * @param string $id The ID of the option
	 * @return bool Filtered value
	 */
	public static function parse_onoff( $input, $id )
	{
		return LiteSpeed_Cache_Admin_Settings::parse_onoff( $input, $id ) ;
	}


	/**
	 * Hook cacheable check to cache control
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_control($hook)
	{
		add_action('litespeed_cache_api_control', $hook) ;
	}

	/**
	 * Hook tag appending to tag
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_tag($hook)
	{
		add_action('litespeed_cache_api_tag', $hook) ;
	}

	/**
	 * Hook purge tags appending to purge
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_purge($hook)
	{
		add_action('litespeed_cache_api_purge', $hook) ;
	}

	/**
	 * Hook purge post action to purge
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_purge_post($hook)
	{
		add_action('litespeed_cache_api_purge_post', $hook) ;
	}

	/**
	 * Hook not ESI template
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_tpl_not_esi($hook)
	{
		add_action('litespeed_cache_is_not_esi_template', $hook) ;
	}

	/**
	 * Hook ESI template block
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_tpl_esi($block, $hook)
	{
		add_action('litespeed_cache_load_esi_block-' . $block, $hook) ;
	}

	/**
	 * Hook ESI params
	 *
	 * @since 1.1.3
	 * @since  2.9.8.1 Changed hook name and params
	 * @access public
	 */
	public static function hook_esi_param( $hook, $priority = 10, $args = 2 )
	{
		add_filter( 'litespeed_esi_params', $hook, $priority, $args ) ;
	}

	/**
	 * Hook setting tab
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_setting_tab($hook, $priority = 10, $args = 1)
	{
		add_filter('litespeed_cache_add_config_tab', $hook, $priority, $args) ;
	}

	/**
	 * Hook setting saving
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_setting_save($hook, $priority = 10, $args = 1)
	{
		add_filter('litespeed_cache_save_options', $hook, $priority, $args) ;
	}

	/**
	 * Hook widget default settings value
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_widget_default_options($hook, $priority = 10, $args = 1)
	{
		add_filter('litespeed_cache_widget_default_options', $hook, $priority, $args) ;
	}

	/**
	 * Hook get options value
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_get_options($hook)
	{
		add_filter('litespeed_cache_get_options', $hook) ;
	}

	/**
	 * Generate ESI block url
	 *
	 * @since 1.1.3
	 * @access public
	 * @param string $control Cache control tag
	 */
	public static function esi_url( $block_id, $wrapper, $params = array(), $control = 'default', $silence = false, $preserved = false, $svar = false, $inline_val = false )
	{
		if ( $control === 'default' ) {
			$control = 'private,no-vary' ;
		}
		return LiteSpeed_Cache_ESI::sub_esi_block( $block_id, $wrapper, $params, $control, $silence, $preserved, $svar, $inline_val ) ;
	}

	/**
	 * Easiest way to replace WP nonce to an ESI widget
	 *
	 * @since 2.6
	 * @deprecated 2.9.5 Dropped-in wp_create_nonce replacement
	 * @access public
	 */
	public static function nonce( $action = -1, $defence_for_html_filter = true )
	{
		if ( ! self::esi_enabled() ) {
			return wp_create_nonce( $action ) ;
		}

		// Replace it to ESI
		return self::esi_url( 'nonce', 'LSCWP Nonce ESI ' . $action, array( 'action' => $action ), '', true, $defence_for_html_filter, true ) ;
	}

	/**
	 * Append an action to nonce to convert it to ESI
	 *
	 * @since  2.9.5
	 */
	public static function nonce_action( $action )
	{
		LiteSpeed_Cache_ESI::get_instance()->nonce_action( $action ) ;
	}

	/**
	 * Log debug info
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function debug( $info, $backtrace_limit = false )
	{
		LiteSpeed_Cache_Log::debug( $info, $backtrace_limit ) ;
	}

	/**
	 * Log debug info ( advanced mode )
	 *
	 * @since 1.6.6.1
	 * @access public
	 */
	public static function debug2( $info, $backtrace_limit = false )
	{
		LiteSpeed_Cache_Log::debug2( $info, $backtrace_limit ) ;
	}

	/**
	 * Get ESI enable setting value
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function esi_enabled()
	{
		return LiteSpeed_Cache_Router::esi_enabled() ;
	}

	/**
	 * Get cache enable setting value
	 *
	 * @since 1.3
	 * @access public
	 */
	public static function cache_enabled()
	{
		return defined( 'LITESPEED_ON' ) ;
	}

	/**
	 * Get cfg setting value
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function config($id)
	{
		return LiteSpeed_Cache::config($id) ;
	}

	/**
	 * register 3rd party detect hooks
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function register($cls)
	{
		add_action('litespeed_cache_api_load_thirdparty', $cls . '::detect') ;
	}

	/**
	 * Hook to litespeed init
	 *
	 * @since 1.6.6
	 * @access public
	 */
	public static function hook_init( $hook )
	{
		add_action( 'litespeed_init', $hook ) ;
	}

}
