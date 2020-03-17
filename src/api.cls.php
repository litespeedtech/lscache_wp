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

class API extends Base
{
	const VERSION =	Core::VER ;

	const TYPE_FEED 					= Tag::TYPE_FEED ;
	const TYPE_FRONTPAGE 				= Tag::TYPE_FRONTPAGE ;
	const TYPE_HOME 					= Tag::TYPE_HOME ;
	const TYPE_PAGES 					= Tag::TYPE_PAGES ;
	const TYPE_PAGES_WITH_RECENT_POSTS 	= Tag::TYPE_PAGES_WITH_RECENT_POSTS ;
	const TYPE_HTTP 					= Tag::TYPE_HTTP ;
	const TYPE_ARCHIVE_POSTTYPE 		= Tag::TYPE_ARCHIVE_POSTTYPE ;
	const TYPE_ARCHIVE_TERM 			= Tag::TYPE_ARCHIVE_TERM ;
	const TYPE_AUTHOR 					= Tag::TYPE_AUTHOR ;
	const TYPE_ARCHIVE_DATE 			= Tag::TYPE_ARCHIVE_DATE ;
	const TYPE_BLOG 					= Tag::TYPE_BLOG ;
	const TYPE_LOGIN 					= Tag::TYPE_LOGIN ;
	const TYPE_URL 						= Tag::TYPE_URL ;

	const TYPE_ESI 					= Tag::TYPE_ESI ;

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
		/**
		 * Init
		 */
		// API::hook_init( $hook ) -> Action `litespeed_init`

		/**
		 * Conf
		 */
		add_filter( 'litespeed_conf', __NAMESPACE__ . '\Conf::val' ); // API::config($id) -> Filter `litespeed_conf`
		// API::conf_append( $name, $default ) -> Action `litespeed_conf_append`
		add_action( 'litespeed_conf_multi_switch', __CLASS__ . '::conf_multi_switch', 10, 2 ) ;
		// API::force_option( $k, $v ) -> Action ``litespeed_conf_force`

		/**
		 * Cache Control Hooks
		 */
		// API::hook_control($tags) && action `litespeed_api_control` -> Action `litespeed_control_finalize`
		add_action( 'litespeed_control_set_private', __NAMESPACE__ . '\Control::set_private' ); // API::set_cache_private() -> Action `litespeed_control_set_private`
		add_action( 'litespeed_control_set_nocache', __NAMESPACE__ . '\Control::set_nocache' ); // API::set_nocache( $reason = false ) -> Action `litespeed_control_set_nocache`
		add_action( 'litespeed_control_set_cacheable', __NAMESPACE__ . '\Control::set_cacheable' ); // API::set_cacheable( $reason ) -> Action `litespeed_control_set_cacheable` // Might needed if not call hook `wp`
		add_action( 'litespeed_control_force_cacheable', __NAMESPACE__ . '\Control::force_cacheable' ); // API::set_force_cacheable( $reason ) -> Action `litespeed_control_force_cacheable` // Set cache status to force cacheable ( Will ignore most kinds of non-cacheable conditions )
		add_action( 'litespeed_control_force_public', __NAMESPACE__ . '\Control::set_public_forced' ); // API::set_force_public( $reason ) -> Action `litespeed_control_force_public` // Set cache to force public cache if cacheable ( Will ignore most kinds of non-cacheable conditions )
		add_filter( 'litespeed_control_cacheable', __NAMESPACE__ . '\Control::is_cacheable', 3 ); // API::not_cacheable() -> Filter `litespeed_control_cacheable` // Note: Read-Only. Directly append to this filter won't work. Call actions above to set cacheable or not
		add_action( 'litespeed_control_set_ttl', __NAMESPACE__ . '\Control::set_custom_ttl', 10, 2 ); // API::set_ttl( $val ) -> Action `litespeed_control_set_ttl`
		add_filter( 'litespeed_control_ttl', __NAMESPACE__ . '\Control::get_ttl', 3 ); // API::get_ttl() -> Filter `litespeed_control_ttl`

		/**
		 * Tag Hooks
		 */
		// API::hook_tag( $hook ) -> Action `litespeed_tag_finalize`
		add_action( 'litespeed_tag_add', __NAMESPACE__ . '\Tag::add' ); // API::tag_add( $tag ) -> Action `litespeed_tag_add`
		add_action( 'litespeed_tag_add_post', __NAMESPACE__ . '\Tag::add_post' );
		add_action( 'litespeed_tag_add_widget', __NAMESPACE__ . '\Tag::add_widget' );
		add_action( 'litespeed_tag_add_private', __NAMESPACE__ . '\Tag::add_private' ); // API::tag_add_private( $tags ) -> Action `litespeed_tag_add_private`
		add_action( 'litespeed_tag_add_private_esi', __NAMESPACE__ . '\Tag::add_private_esi' );

		/**
		 * Purge Hooks
		 */
		// API::hook_purge($tags) -> Action `litespeed_purge_finalize`
		add_action( 'litespeed_purge', __NAMESPACE__ . '\Purge::add' ); // API::purge($tags) -> Action `litespeed_purge`
		add_action( 'litespeed_purge_all', __NAMESPACE__ . '\Purge::purge_all' );
		add_action( 'litespeed_purge_post', __NAMESPACE__ . '\Purge::purge_post' ); // API::purge_post( $pid ) -> Action `litespeed_purge_post`
		add_action( 'litespeed_purge_posttype', __NAMESPACE__ . '\Purge::purge_posttype' );
		add_action( 'litespeed_purge_url', __NAMESPACE__ . '\Purge::purge_url' );
		add_action( 'litespeed_purge_widget', __NAMESPACE__ . '\Purge::purge_widget' );
		add_action( 'litespeed_purge_esi', __NAMESPACE__ . '\Purge::purge_esi' );
		add_action( 'litespeed_purge_private', __NAMESPACE__ . '\Purge::add_private' ); // API::purge_private( $tags ) -> Action `litespeed_purge_private`
		add_action( 'litespeed_purge_private_esi', __NAMESPACE__ . '\Purge::add_private_esi' );
		add_action( 'litespeed_purge_private_all', __NAMESPACE__ . '\Purge::add_private_all' ); // API::purge_private_all() -> Action `litespeed_purge_private_all`

		/**
		 * ESI
		 */
		// API::nonce_action( $action ) & API::nonce( $action = -1, $defence_for_html_filter = true ) -> Action `litespeed_nonce`


		/**
		 * Vary
		 *
		 * To modify default vary, There are two ways: Action `litespeed_vary_append` or Filter `litespeed_vary`
		 */
		add_action( 'litespeed_vary_ajax_force', __NAMESPACE__ . '\Vary::can_ajax_vary' ); // API::force_vary() -> Action `litespeed_vary_ajax_force` // Force finalize vary even if its in an AJAX call
		add_action( 'litespeed_vary_append', __NAMESPACE__ . '\Vary::append', 10, 2 ); // API::vary( $k, $v, $default = null ) -> Action `litespeed_vary_append // Alter default vary cookie value // Default vary cookie is an array before finalization, after that it will be combined to a string and store as default vary cookie name
		// API::hook_vary_finalize( $hook ) -> Filter `litespeed_vary`

		add_filter( 'litespeed_is_mobile', __NAMESPACE__ . '\Control::is_mobile' ); // API::set_mobile() -> Filter `litespeed_is_mobile`

		/**
		 * Mist
		 */
		add_action( 'litespeed_debug', __NAMESPACE__ . '\Debug2::debug' ); // API::debug()-> action `litespeed_debug`
		add_action( 'litespeed_debug2', __NAMESPACE__ . '\Debug2::debug2' ); // API::debug2()-> action `litespeed_debug2`

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
		do_action( 'litespeed_debug', '[API] Disabled_all due to ' . $reason );

		! defined( 'LITESPEED_DISABLE_ALL' ) && define( 'LITESPEED_DISABLE_ALL', true ) ;
	}

	/**
	 * Extend an bool option max value for LSCWP options when save settings.
	 *
	 * @since  3.0
	 */
	public static function conf_multi_switch( $id, $v )
	{
		Base::set_multi_switch( $id, $v ) ;
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
	 * Set cache control ttl to use frontpage ttl
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_use_frontpage_ttl()
	{
		Control::set_custom_ttl( Conf::val( self::O_CACHE_TTL_FRONTPAGE ) ) ;
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
	 * Hook to check if need to bypass CDN or not
	 *
	 * @since  3.0
	 */
	public static function hook_can_cdn( $hook )
	{
		add_filter( 'litespeed_can_cdn', $hook ) ;
	}

}

