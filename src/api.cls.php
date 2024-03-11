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
namespace LiteSpeed;

defined('WPINC') || exit();

class API extends Base
{
	const VERSION = Core::VER;

	const TYPE_FEED = Tag::TYPE_FEED;
	const TYPE_FRONTPAGE = Tag::TYPE_FRONTPAGE;
	const TYPE_HOME = Tag::TYPE_HOME;
	const TYPE_PAGES = Tag::TYPE_PAGES;
	const TYPE_PAGES_WITH_RECENT_POSTS = Tag::TYPE_PAGES_WITH_RECENT_POSTS;
	const TYPE_HTTP = Tag::TYPE_HTTP;
	const TYPE_ARCHIVE_POSTTYPE = Tag::TYPE_ARCHIVE_POSTTYPE;
	const TYPE_ARCHIVE_TERM = Tag::TYPE_ARCHIVE_TERM;
	const TYPE_AUTHOR = Tag::TYPE_AUTHOR;
	const TYPE_ARCHIVE_DATE = Tag::TYPE_ARCHIVE_DATE;
	const TYPE_BLOG = Tag::TYPE_BLOG;
	const TYPE_LOGIN = Tag::TYPE_LOGIN;
	const TYPE_URL = Tag::TYPE_URL;

	const TYPE_ESI = Tag::TYPE_ESI;

	const PARAM_NAME = ESI::PARAM_NAME;
	const WIDGET_O_ESIENABLE = ESI::WIDGET_O_ESIENABLE;
	const WIDGET_O_TTL = ESI::WIDGET_O_TTL;

	/**
	 * Instance
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
	}

	/**
	 * Define hooks to be used in other plugins.
	 *
	 * The benefit to use hooks other than functions is no need to detech if LSCWP enabled and function existed or not anymore
	 *
	 * @since  3.0
	 */
	public function init()
	{
		/**
		 * Init
		 */
		// Action `litespeed_init` // @previous API::hook_init( $hook )

		/**
		 * Conf
		 */
		add_filter('litespeed_conf', array($this, 'conf')); // @previous API::config($id)
		// Action `litespeed_conf_append` // @previous API::conf_append( $name, $default )
		add_action('litespeed_conf_multi_switch', __NAMESPACE__ . '\Base::set_multi_switch', 10, 2);
		// Action ``litespeed_conf_force` // @previous API::force_option( $k, $v )

		/**
		 * Cache Control Hooks
		 */
		// Action `litespeed_control_finalize` // @previous API::hook_control($tags) && action `litespeed_api_control`
		add_action('litespeed_control_set_private', __NAMESPACE__ . '\Control::set_private'); // @previous  API::set_cache_private()
		add_action('litespeed_control_set_nocache', __NAMESPACE__ . '\Control::set_nocache'); // @previous  API::set_nocache( $reason = false )
		add_action('litespeed_control_set_cacheable', array($this, 'set_cacheable')); // Might needed if not call hook `wp` // @previous API::set_cacheable( $reason )
		add_action('litespeed_control_force_cacheable', __NAMESPACE__ . '\Control::force_cacheable'); // Set cache status to force cacheable ( Will ignore most kinds of non-cacheable conditions ) // @previous API::set_force_cacheable( $reason )
		add_action('litespeed_control_force_public', __NAMESPACE__ . '\Control::set_public_forced'); // Set cache to force public cache if cacheable ( Will ignore most kinds of non-cacheable conditions ) // @previous API::set_force_public( $reason )
		add_filter('litespeed_control_cacheable', __NAMESPACE__ . '\Control::is_cacheable', 3); // Note: Read-Only. Directly append to this filter won't work. Call actions above to set cacheable or not // @previous API::not_cacheable()
		add_action('litespeed_control_set_ttl', __NAMESPACE__ . '\Control::set_custom_ttl', 10, 2); // @previous API::set_ttl( $val )
		add_filter('litespeed_control_ttl', array($this, 'get_ttl'), 3); // @previous API::get_ttl()

		/**
		 * Tag Hooks
		 */
		// Action `litespeed_tag_finalize` // @previous API::hook_tag( $hook )
		add_action('litespeed_tag', __NAMESPACE__ . '\Tag::add'); // Shorter alias of `litespeed_tag_add`
		add_action('litespeed_tag_post', __NAMESPACE__ . '\Tag::add_post'); // Shorter alias of `litespeed_tag_add_post`
		add_action('litespeed_tag_widget', __NAMESPACE__ . '\Tag::add_widget'); // Shorter alias of `litespeed_tag_add_widget`
		add_action('litespeed_tag_private', __NAMESPACE__ . '\Tag::add_private'); // Shorter alias of `litespeed_tag_add_private`
		add_action('litespeed_tag_private_esi', __NAMESPACE__ . '\Tag::add_private_esi'); // Shorter alias of `litespeed_tag_add_private_esi`

		add_action('litespeed_tag_add', __NAMESPACE__ . '\Tag::add'); // @previous API::tag_add( $tag )
		add_action('litespeed_tag_add_post', __NAMESPACE__ . '\Tag::add_post');
		add_action('litespeed_tag_add_widget', __NAMESPACE__ . '\Tag::add_widget');
		add_action('litespeed_tag_add_private', __NAMESPACE__ . '\Tag::add_private'); // @previous API::tag_add_private( $tags )
		add_action('litespeed_tag_add_private_esi', __NAMESPACE__ . '\Tag::add_private_esi');

		/**
		 * Purge Hooks
		 */
		// Action `litespeed_purge_finalize` // @previous API::hook_purge($tags)
		add_action('litespeed_purge', __NAMESPACE__ . '\Purge::add'); // @previous API::purge($tags)
		add_action('litespeed_purge_all', __NAMESPACE__ . '\Purge::purge_all');
		add_action('litespeed_purge_post', array($this, 'purge_post')); // @previous API::purge_post( $pid )
		add_action('litespeed_purge_posttype', __NAMESPACE__ . '\Purge::purge_posttype');
		add_action('litespeed_purge_url', array($this, 'purge_url'));
		add_action('litespeed_purge_widget', __NAMESPACE__ . '\Purge::purge_widget');
		add_action('litespeed_purge_esi', __NAMESPACE__ . '\Purge::purge_esi');
		add_action('litespeed_purge_private', __NAMESPACE__ . '\Purge::add_private'); // @previous API::purge_private( $tags )
		add_action('litespeed_purge_private_esi', __NAMESPACE__ . '\Purge::add_private_esi');
		add_action('litespeed_purge_private_all', __NAMESPACE__ . '\Purge::add_private_all'); // @previous API::purge_private_all()
		// Action `litespeed_api_purge_post` // Triggered when purge a post // @previous API::hook_purge_post($hook)
		// Action `litespeed_purged_all` // Triggered after purged all.
		add_action('litespeed_purge_all_object', __NAMESPACE__ . '\Purge::purge_all_object');
		add_action('litespeed_purge_ucss', __NAMESPACE__ . '\Purge::purge_ucss');

		/**
		 * ESI
		 */
		// Action `litespeed_nonce` // @previous API::nonce_action( $action ) & API::nonce( $action = -1, $defence_for_html_filter = true ) // NOTE: only available after `init` hook
		add_filter('litespeed_esi_status', array($this, 'esi_enabled')); // Get ESI enable status // @previous API::esi_enabled()
		add_filter('litespeed_esi_url', array($this, 'sub_esi_block'), 10, 8); // Generate ESI block url // @previous API::esi_url( $block_id, $wrapper, $params = array(), $control = 'private,no-vary', $silence = false, $preserved = false, $svar = false, $inline_val = false )
		// Filter `litespeed_widget_default_options` // Hook widget default settings value. Currently used in Woo 3rd // @previous API::hook_widget_default_options( $hook )
		// Filter `litespeed_esi_params` // @previous API::hook_esi_param( $hook )
		// Action `litespeed_tpl_normal` // @previous API::hook_tpl_not_esi($hook) && Action `litespeed_is_not_esi_template`
		// Action `litespeed_esi_load-$block` // @usage add_action( 'litespeed_esi_load-' . $block, $hook ) // @previous API::hook_tpl_esi($block, $hook)
		add_action('litespeed_esi_combine', __NAMESPACE__ . '\ESI::combine');

		/**
		 * Vary
		 *
		 * To modify default vary, There are two ways: Action `litespeed_vary_append` or Filter `litespeed_vary`
		 */
		add_action('litespeed_vary_ajax_force', __NAMESPACE__ . '\Vary::can_ajax_vary'); // API::force_vary() -> Action `litespeed_vary_ajax_force` // Force finalize vary even if its in an AJAX call
		// Filter `litespeed_vary_curr_cookies` to generate current in use vary, which will be used for response vary header.
		// Filter `litespeed_vary_cookies` to register the final vary cookies, which will be written to rewrite rule. (litespeed_vary_curr_cookies are always equal to or less than litespeed_vary_cookies)
		// Filter `litespeed_vary` // Previous API::hook_vary_finalize( $hook )
		add_action('litespeed_vary_no', __NAMESPACE__ . '\Control::set_no_vary'); // API::set_cache_no_vary() -> Action `litespeed_vary_no` // Set cache status to no vary

		// add_filter( 'litespeed_is_mobile', __NAMESPACE__ . '\Control::is_mobile' ); // API::set_mobile() -> Filter `litespeed_is_mobile`

		/**
		 * Cloud
		 */
		add_filter('litespeed_is_from_cloud', array($this, 'is_from_cloud')); // Check if current request is from QC (usally its to check REST access) // @see https://wordpress.org/support/topic/image-optimization-not-working-3/

		/**
		 * Media
		 */
		add_action('litespeed_media_reset', __NAMESPACE__ . '\Media::delete_attachment'); // Reset one media row

		/**
		 * GUI
		 */
		// API::clean_wrapper_begin( $counter = false ) -> Filter `litespeed_clean_wrapper_begin` // Start a to-be-removed html wrapper
		add_filter('litespeed_clean_wrapper_begin', __NAMESPACE__ . '\GUI::clean_wrapper_begin');
		// API::clean_wrapper_end( $counter = false ) -> Filter `litespeed_clean_wrapper_end` // End a to-be-removed html wrapper
		add_filter('litespeed_clean_wrapper_end', __NAMESPACE__ . '\GUI::clean_wrapper_end');

		/**
		 * Mist
		 */
		add_action('litespeed_debug', __NAMESPACE__ . '\Debug2::debug', 10, 2); // API::debug()-> Action `litespeed_debug`
		add_action('litespeed_debug2', __NAMESPACE__ . '\Debug2::debug2', 10, 2); // API::debug2()-> Action `litespeed_debug2`
		add_action('litespeed_disable_all', array($this, '_disable_all')); // API::disable_all( $reason ) -> Action `litespeed_disable_all`

		add_action('litspeed_after_admin_init', array($this, '_after_admin_init'));
	}

	/**
	 * API for admin related
	 *
	 * @since  3.0
	 * @access public
	 */
	public function _after_admin_init()
	{
		/**
		 * GUI
		 */
		add_action('litespeed_setting_enroll', array($this->cls('Admin_Display'), 'enroll'), 10, 4); // API::enroll( $id ) // Register a field in setting form to save
		add_action('litespeed_build_switch', array($this->cls('Admin_Display'), 'build_switch')); // API::build_switch( $id ) // Build a switch div html snippet
		// API::hook_setting_content( $hook, $priority = 10, $args = 1 ) -> Action `litespeed_settings_content`
		// API::hook_setting_tab( $hook, $priority = 10, $args = 1 ) -> Action `litespeed_settings_tab`
	}

	/**
	 * Disable All (Note: Not for direct call, always use Hooks)
	 *
	 * @since 2.9.7.2
	 * @access public
	 */
	public function _disable_all($reason)
	{
		do_action('litespeed_debug', '[API] Disabled_all due to ' . $reason);

		!defined('LITESPEED_DISABLE_ALL') && define('LITESPEED_DISABLE_ALL', true);
	}

	/**
	 * @since 3.0
	 */
	public static function vary_append_commenter()
	{
		Vary::cls()->append_commenter();
	}

	/**
	 * Check if is from Cloud
	 *
	 * @since 4.2
	 */
	public function is_from_cloud()
	{
		return $this->cls('Cloud')->is_from_cloud();
	}

	public function purge_post($pid)
	{
		$this->cls('Purge')->purge_post($pid);
	}

	public function purge_url($url)
	{
		$this->cls('Purge')->purge_url($url);
	}

	public function set_cacheable($reason = false)
	{
		$this->cls('Control')->set_cacheable($reason);
	}

	public function esi_enabled()
	{
		return $this->cls('Router')->esi_enabled();
	}

	public function get_ttl()
	{
		return $this->cls('Control')->get_ttl();
	}

	public function sub_esi_block(
		$block_id,
		$wrapper,
		$params = array(),
		$control = 'private,no-vary',
		$silence = false,
		$preserved = false,
		$svar = false,
		$inline_param = array()
	) {
		return $this->cls('ESI')->sub_esi_block($block_id, $wrapper, $params, $control, $silence, $preserved, $svar, $inline_param);
	}
}
