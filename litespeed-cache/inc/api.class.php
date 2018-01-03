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
class LiteSpeed_Cache_API
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

	const VAL_OFF	= LiteSpeed_Cache_Config::VAL_OFF ;
	const VAL_ON	= LiteSpeed_Cache_Config::VAL_ON ;
	const VAL_ON2	= LiteSpeed_Cache_Config::VAL_ON2 ;

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
		LiteSpeed_Cache_Control::set_custom_ttl(self::config(LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL)) ;
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
	 * Add vary
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function vary_add($vary)
	{
		LiteSpeed_Cache_Vary::add($vary) ;
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
	 * Hook vary appending to vary
	 *
	 * NOTE: This will add vary to rewrite rule
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function hook_vary($hook)
	{
		add_action('litespeed_cache_api_vary', $hook) ;
	}

	/**
	 * Hook vary tags to vary finialization
	 *
	 * @since 1.7.2
	 * @access public
	 */
	public static function hook_vary_finalize( $hook )
	{
		add_filter( 'litespeed_vary', $hook ) ;
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
	 * @access public
	 */
	public static function hook_esi_param($block, $hook)
	{
		add_filter('litespeed_cache_sub_esi_params-' . $block, $hook) ;
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
	public static function esi_url( $block_id, $wrapper, $params = array(), $control = 'default', $silence = false )
	{
		if ( $control === 'default' ) {
			$control = 'private,no-vary' ;
		}
		return LiteSpeed_Cache_ESI::sub_esi_block( $block_id, $wrapper, $params, $control, $silence ) ;
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
		add_action('litespeed_init', $hook) ;
	}

}
