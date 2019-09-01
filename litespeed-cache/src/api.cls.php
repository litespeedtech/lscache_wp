<?php
/**
 * The plugin API class.
 *
 * @since      	1.1.3
 * @since  		1.4 Moved into /inc
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class API extends Conf
{
	const VERSION =	Core::PLUGIN_VERSION ;

	const TYPE_FEED =						Tag::TYPE_FEED ;
	const TYPE_FRONTPAGE =					Tag::TYPE_FRONTPAGE ;
	const TYPE_HOME =						Tag::TYPE_HOME ;
	const TYPE_PAGES =						Tag::TYPE_PAGES ;
	const TYPE_PAGES_WITH_RECENT_POSTS =	Tag::TYPE_PAGES_WITH_RECENT_POSTS ;
	const TYPE_ERROR =						Tag::TYPE_ERROR ;
	const TYPE_POST =						Tag::TYPE_POST ;
	const TYPE_ARCHIVE_POSTTYPE =			Tag::TYPE_ARCHIVE_POSTTYPE ;
	const TYPE_ARCHIVE_TERM =				Tag::TYPE_ARCHIVE_TERM ;
	const TYPE_AUTHOR =						Tag::TYPE_AUTHOR ;
	const TYPE_ARCHIVE_DATE =				Tag::TYPE_ARCHIVE_DATE ;
	const TYPE_BLOG =						Tag::TYPE_BLOG ;
	const TYPE_LOGIN =						Tag::TYPE_LOGIN ;
	const TYPE_URL =						Tag::TYPE_URL ;
	const TYPE_WIDGET =						Tag::TYPE_WIDGET ;

	const TYPE_ESI =						Tag::TYPE_ESI ;

	const PARAM_NAME =				ESI::PARAM_NAME ;
	const WIDGET_O_ESIENABLE =	ESI::WIDGET_O_ESIENABLE ;
	const WIDGET_O_TTL =			ESI::WIDGET_O_TTL ;

	/**
	 * Define hooks to be used in other plugins.
	 *
	 * The benefit to use hooks other than functions is no need to detech if LSCWP enabled and function existed or not anymore
	 *
	 * @since  3.0
	 */
	public static function init()
	{
		add_action( 'litespeed_conf_append', __CLASS__ . '::conf_append', 10, 2 ) ;
		add_action( 'litespeed_conf_multi_switch', __CLASS__ . '::conf_multi_switch', 10, 2 ) ;
		add_action( 'litespeed_conf_force', __CLASS__ . '::force_option', 10, 2 ) ;
	}

	/**
	 * Append options API
	 */

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
	 * Append an option to LSCWP options
	 *
	 * @since  3.0
	 */
	public static function conf_append( $name, $default )
	{
		Config::get_instance()->option_append( $name, $default ) ;
	}

	/**
	 * Extend an bool option max value for LSCWP options when save settings.
	 *
	 * @since  3.0
	 */
	public static function conf_multi_switch( $id, $v )
	{
		Conf::set_multi_switch( $id, $v ) ;
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
		Config::get_instance()->force_option( $k, $v ) ;
	}

	/**
	 * Start a to-be-removed html wrapper
	 *
	 * @since 1.4
	 * @access public
	 */
	public static function clean_wrapper_begin( $counter = false )
	{
		return GUI::clean_wrapper_begin( $counter ) ;
	}

	/**
	 * End a to-be-removed html wrapper
	 *
	 * @since 1.4
	 * @access public
	 */
	public static function clean_wrapper_end( $counter = false )
	{
		return GUI::clean_wrapper_end( $counter ) ;
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
		Control::set_mobile() ;
	}

	/**
	 * Set cache status to not cacheable
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_cache_private()
	{
		Control::set_private() ;
	}

	/**
	 * Set cache status to no vary
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function set_cache_no_vary()
	{
		Control::set_no_vary() ;
	}

	/**
	 * Set cache status to not cacheable
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_nocache( $reason = false )
	{
		Control::set_nocache( $reason ?: 'api' ) ;
	}

	/**
	 * Set cache status to cacheable ( By default cacheable status will be set when called WP hook `wp` )
	 *
	 * @since 2.2
	 * @access public
	 */
	public static function set_cacheable( $reason )
	{
		Control::set_cacheable( $reason ) ;
	}

	/**
	 * Set cache status to force cacheable ( Will ignore most kinds of non-cacheable conditions )
	 *
	 * @since 2.2
	 * @access public
	 */
	public static function set_force_cacheable( $reason )
	{
		Control::force_cacheable( $reason ) ;
	}

	/**
	 * Set cache to force public cache if cacheable ( Will ignore most kinds of non-cacheable conditions )
	 *
	 * @since 2.9.7.2
	 * @access public
	 */
	public static function set_force_public( $reason )
	{
		Control::set_public_forced( $reason ) ;
	}

	/**
	 * Get current not cacheable status
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function not_cacheable()
	{
		return ! Control::is_cacheable() ;
	}

	/**
	 * Set cache control ttl to use frontpage ttl
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_use_frontpage_ttl()
	{
		Control::set_custom_ttl( self::config( self::O_CACHE_TTL_FRONTPAGE ) ) ;
	}

	/**
	 * Set cache control ttl
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public static function set_ttl( $val )
	{
		Control::set_custom_ttl( $val ) ;
	}

	/**
	 * Get current cache control ttl
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public static function get_ttl()
	{
		return Control::get_ttl() ;
	}

	/**
	 * Add public tag to cache
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function tag_add($tags)
	{
		Tag::add($tags) ;
	}

	/**
	 * Add public tag to cache
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public static function add_private( $tags )
	{
		Tag::add_private( $tags ) ;
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
	 * @since  2.7.1 Changed to filter hook instead of `Vary::add()`
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
		Vary::append( $k, $v ) ;
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
		Vary::can_ajax_vary() ;
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
		add_filter( 'litespeed_api_vary', $hook ) ;
	}

	/**
	 * @since 3.0
	 */
	public static function vary_append_commenter()
	{
		Vary::get_instance()->append_commenter() ;
	}

	/**
	 * Purge all action
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function purge_all()
	{
		Purge::purge_all() ;
	}

	/**
	 * Purge all private
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public static function purge_private_all()
	{
		Purge::add_private( '*' ) ;
	}

	/**
	 * Purge private tag
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public static function purge_private( $tags )
	{
		Purge::add_private( $tags ) ;
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
		Purge::purge_post( $pid ) ;
	}

	/**
	 * Add purge tags
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function purge($tags)
	{
		Purge::add($tags) ;
	}

	/**
	 * Register an option for settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function enroll( $id )
	{
		Admin_Display::get_instance()->enroll( $id ) ;
	}

	/**
	 * Build a switch div html snippet
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function build_switch( $id )
	{
		Admin_Display::get_instance()->build_switch( $id ) ;
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
		return Admin_Settings::parse_onoff( $input, $id ) ;
	}


	/**
	 * Hook cacheable check to cache control
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_control($hook)
	{
		add_action('litespeed_api_control', $hook) ;
	}

	/**
	 * Hook tag appending to tag
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_tag($hook)
	{
		add_action('litespeed_api_tag', $hook) ;
	}

	/**
	 * Hook purge tags appending to purge
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_purge($hook)
	{
		add_action('litespeed_api_purge', $hook) ;
	}

	/**
	 * Hook purge post action to purge
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_purge_post($hook)
	{
		add_action('litespeed_api_purge_post', $hook) ;
	}

	/**
	 * Hook not ESI template
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_tpl_not_esi($hook)
	{
		add_action('litespeed_is_not_esi_template', $hook) ;
	}

	/**
	 * Hook ESI template block
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_tpl_esi($block, $hook)
	{
		add_action('litespeed_load_esi_block-' . $block, $hook) ;
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
	public static function hook_setting_tab( $hook, $priority = 10, $args = 1 )
	{
		add_action( 'litespeed_settings_tab', $hook, $priority, $args ) ;
	}

	/**
	 * Hook setting content
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function hook_setting_content( $hook, $priority = 10, $args = 1 )
	{
		add_action( 'litespeed_settings_content', $hook, $priority, $args ) ;
	}

	/**
	 * Hook widget default settings value
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_widget_default_options($hook, $priority = 10, $args = 1)
	{
		add_filter('litespeed_widget_default_options', $hook, $priority, $args) ;
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
		return ESI::sub_esi_block( $block_id, $wrapper, $params, $control, $silence, $preserved, $svar, $inline_val ) ;
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
		ESI::get_instance()->nonce_action( $action ) ;
	}

	/**
	 * Log debug info
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function debug( $info, $backtrace_limit = false )
	{
		Log::debug( $info, $backtrace_limit ) ;
	}

	/**
	 * Log debug info ( advanced mode )
	 *
	 * @since 1.6.6.1
	 * @access public
	 */
	public static function debug2( $info, $backtrace_limit = false )
	{
		Log::debug2( $info, $backtrace_limit ) ;
	}

	/**
	 * Get ESI enable setting value
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function esi_enabled()
	{
		return Router::esi_enabled() ;
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
		return Core::config($id) ;
	}

	/**
	 * register 3rd party detect hooks
	 *
	 * @since 1.1.3
	 * @since  3.0 Renamed from `regiter()` to `thirdparty()`
	 * @access public
	 */
	public static function thirdparty( $cls )
	{
		add_action('litespeed_api_load_thirdparty', 'LiteSpeed\Thirdparty\\' . $cls . '::detect') ;
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

	/**
	 * Hook to check if need to bypass CDN or not
	 *
	 * @since  3.0
	 */
	public static function hook_can_cdn( $hook )
	{
		add_filter( 'litespeed_can_cdn', $hook ) ;
	}

}

