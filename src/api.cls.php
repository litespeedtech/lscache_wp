<?php
/**
 * The plugin API class.
 *
 * @since       1.1.3
 * @package     LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class API
 *
 * Provides API hooks and methods for LiteSpeed Cache integration.
 *
 * @since 1.1.3
 */
class API extends Base {

	const VERSION = Core::VER;

	const TYPE_FEED                    = Tag::TYPE_FEED;
	const TYPE_FRONTPAGE               = Tag::TYPE_FRONTPAGE;
	const TYPE_HOME                    = Tag::TYPE_HOME;
	const TYPE_PAGES                   = Tag::TYPE_PAGES;
	const TYPE_PAGES_WITH_RECENT_POSTS = Tag::TYPE_PAGES_WITH_RECENT_POSTS;
	const TYPE_HTTP                    = Tag::TYPE_HTTP;
	const TYPE_ARCHIVE_POSTTYPE        = Tag::TYPE_ARCHIVE_POSTTYPE;
	const TYPE_ARCHIVE_TERM            = Tag::TYPE_ARCHIVE_TERM;
	const TYPE_AUTHOR                  = Tag::TYPE_AUTHOR;
	const TYPE_ARCHIVE_DATE            = Tag::TYPE_ARCHIVE_DATE;
	const TYPE_BLOG                    = Tag::TYPE_BLOG;
	const TYPE_LOGIN                   = Tag::TYPE_LOGIN;
	const TYPE_URL                     = Tag::TYPE_URL;

	const TYPE_ESI = Tag::TYPE_ESI;

	const PARAM_NAME         = ESI::PARAM_NAME;
	const WIDGET_O_ESIENABLE = ESI::WIDGET_O_ESIENABLE;
	const WIDGET_O_TTL       = ESI::WIDGET_O_TTL;

	/**
	 * Instance
	 *
	 * Initializes the API class.
	 *
	 * @since  3.0
	 */
	public function __construct() {
	}

	/**
	 * Define hooks to be used in other plugins.
	 *
	 * The benefit to use hooks other than functions is no need to detach if LSCWP enabled and function existed or not anymore
	 *
	 * @since  3.0
	 */
	public function init() {
		/**
		 * Init
		 */
		// Action `litespeed_init`

		/**
		 * Conf
		 */
		add_filter( 'litespeed_conf', array( $this, 'conf' ) );
		// Action `litespeed_conf_append`
		add_action( 'litespeed_conf_multi_switch', __NAMESPACE__ . '\Base::set_multi_switch', 10, 2 );
		// Action `litespeed_conf_force`
		add_action( 'litespeed_save_conf', array( $this, 'save_conf' ) );

		/**
		 * Cache Control Hooks
		 */
		// Action `litespeed_control_finalize`
		add_action( 'litespeed_control_set_private', __NAMESPACE__ . '\Control::set_private' );
		add_action( 'litespeed_control_set_nocache', __NAMESPACE__ . '\Control::set_nocache' );
		add_action( 'litespeed_control_set_cacheable', array( $this, 'set_cacheable' ) );
		add_action( 'litespeed_control_force_cacheable', __NAMESPACE__ . '\Control::force_cacheable' );
		add_action( 'litespeed_control_force_public', __NAMESPACE__ . '\Control::set_public_forced' );
		add_filter( 'litespeed_control_cacheable', __NAMESPACE__ . '\Control::is_cacheable', 3 );
		add_action( 'litespeed_control_set_ttl', __NAMESPACE__ . '\Control::set_custom_ttl', 10, 2 );
		add_filter( 'litespeed_control_ttl', array( $this, 'get_ttl' ), 3 );

		/**
		 * Tag Hooks
		 */
		// Action `litespeed_tag_finalize`
		add_action( 'litespeed_tag', __NAMESPACE__ . '\Tag::add' );
		add_action( 'litespeed_tag_post', __NAMESPACE__ . '\Tag::add_post' );
		add_action( 'litespeed_tag_widget', __NAMESPACE__ . '\Tag::add_widget' );
		add_action( 'litespeed_tag_private', __NAMESPACE__ . '\Tag::add_private' );
		add_action( 'litespeed_tag_private_esi', __NAMESPACE__ . '\Tag::add_private_esi' );

		add_action( 'litespeed_tag_add', __NAMESPACE__ . '\Tag::add' );
		add_action( 'litespeed_tag_add_post', __NAMESPACE__ . '\Tag::add_post' );
		add_action( 'litespeed_tag_add_widget', __NAMESPACE__ . '\Tag::add_widget' );
		add_action( 'litespeed_tag_add_private', __NAMESPACE__ . '\Tag::add_private' );
		add_action( 'litespeed_tag_add_private_esi', __NAMESPACE__ . '\Tag::add_private_esi' );

		/**
		 * Purge Hooks
		 */
		// Action `litespeed_purge_finalize`
		add_action( 'litespeed_purge', __NAMESPACE__ . '\Purge::add' );
		add_action( 'litespeed_purge_all', __NAMESPACE__ . '\Purge::purge_all' );
		add_action( 'litespeed_purge_post', array( $this, 'purge_post' ) );
		add_action( 'litespeed_purge_posttype', __NAMESPACE__ . '\Purge::purge_posttype' );
		add_action( 'litespeed_purge_url', array( $this, 'purge_url' ) );
		add_action( 'litespeed_purge_widget', __NAMESPACE__ . '\Purge::purge_widget' );
		add_action( 'litespeed_purge_esi', __NAMESPACE__ . '\Purge::purge_esi' );
		add_action( 'litespeed_purge_private', __NAMESPACE__ . '\Purge::add_private' );
		add_action( 'litespeed_purge_private_esi', __NAMESPACE__ . '\Purge::add_private_esi' );
		add_action( 'litespeed_purge_private_all', __NAMESPACE__ . '\Purge::add_private_all' );
		// Action `litespeed_api_purge_post`
		// Action `litespeed_purged_all`
		add_action( 'litespeed_purge_all_object', __NAMESPACE__ . '\Purge::purge_all_object' );
		add_action( 'litespeed_purge_ucss', __NAMESPACE__ . '\Purge::purge_ucss' );

		/**
		 * ESI
		 */
		// Action `litespeed_nonce`
		add_filter( 'litespeed_esi_status', array( $this, 'esi_enabled' ) );
		add_filter( 'litespeed_esi_url', array( $this, 'sub_esi_block' ), 10, 8 ); // Generate ESI block url
		// Filter `litespeed_widget_default_options` // Hook widget default settings value. Currently used in Woo 3rd
		// Filter `litespeed_esi_params`
		// Action `litespeed_tpl_normal`
		// Action `litespeed_esi_load-$block` // @usage add_action( 'litespeed_esi_load-' . $block, $hook )
		add_action( 'litespeed_esi_combine', __NAMESPACE__ . '\ESI::combine' );

		/**
		 * Vary
		 *
		 * To modify default vary, There are two ways: Action `litespeed_vary_append` or Filter `litespeed_vary`
		 */
		add_action( 'litespeed_vary_ajax_force', __NAMESPACE__ . '\Vary::can_ajax_vary' ); // Force finalize vary even if its in an AJAX call
		// Filter `litespeed_vary_curr_cookies` to generate current in use vary, which will be used for response vary header.
		// Filter `litespeed_vary_cookies` to register the final vary cookies, which will be written to rewrite rule. (litespeed_vary_curr_cookies are always equal to or less than litespeed_vary_cookies)
		// Filter `litespeed_vary`
		add_action( 'litespeed_vary_no', __NAMESPACE__ . '\Control::set_no_vary' );

		/**
		 * Cloud
		 */
		add_filter( 'litespeed_is_from_cloud', array( $this, 'is_from_cloud' ) ); // Check if current request is from QC (usually its to check REST access) // @see https://wordpress.org/support/topic/image-optimization-not-working-3/

		/**
		 * Media
		 */
		add_action( 'litespeed_media_reset', __NAMESPACE__ . '\Media::delete_attachment' );

		/**
		 * GUI
		 */
		add_filter( 'litespeed_clean_wrapper_begin', __NAMESPACE__ . '\GUI::clean_wrapper_begin' );
		add_filter( 'litespeed_clean_wrapper_end', __NAMESPACE__ . '\GUI::clean_wrapper_end' );

		/**
		 * Misc
		 */
		add_action( 'litespeed_debug', __NAMESPACE__ . '\Debug2::debug', 10, 2 );
		add_action( 'litespeed_debug2', __NAMESPACE__ . '\Debug2::debug2', 10, 2 );
		add_action( 'litespeed_disable_all', array( $this, 'disable_all' ) );

		add_action( 'litespeed_after_admin_init', array( $this, 'after_admin_init' ) );
	}

	/**
	 * API for admin related
	 *
	 * Registers hooks for admin settings and UI elements.
	 *
	 * @since  3.0
	 * @access public
	 */
	public function after_admin_init() {
		/**
		 * GUI
		 */
		add_action( 'litespeed_setting_enroll', array( $this->cls( 'Admin_Display' ), 'enroll' ), 10, 4 );
		add_action( 'litespeed_build_switch', array( $this->cls( 'Admin_Display' ), 'build_switch' ) );
		// Action `litespeed_settings_content`
		// Action `litespeed_settings_tab`
	}

	/**
	 * Disable All
	 *
	 * Disables all LiteSpeed Cache features with a given reason.
	 *
	 * @since 2.9.7.2
	 * @access public
	 * @param string $reason The reason for disabling all features.
	 */
	public function disable_all( $reason ) {
		do_action( 'litespeed_debug', '[API] Disabled_all due to ' . $reason );

		! defined( 'LITESPEED_DISABLE_ALL' ) && define( 'LITESPEED_DISABLE_ALL', true );
	}

	/**
	 * Append commenter vary
	 *
	 * Adds commenter vary to the cache vary cookies.
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function vary_append_commenter() {
		Vary::cls()->append_commenter();
	}

	/**
	 * Check if is from Cloud
	 *
	 * Checks if the current request originates from QUIC.cloud.
	 *
	 * @since 4.2
	 * @access public
	 * @return bool True if from QUIC.cloud, false otherwise.
	 */
	public function is_from_cloud() {
		return $this->cls( 'Cloud' )->is_from_cloud();
	}

	/**
	 * Purge post
	 *
	 * Purges the cache for a specific post.
	 *
	 * @since 3.0
	 * @access public
	 * @param int $pid Post ID to purge.
	 */
	public function purge_post( $pid ) {
		$this->cls( 'Purge' )->purge_post( $pid );
	}

	/**
	 * Purge URL
	 *
	 * Purges the cache for a specific URL.
	 *
	 * @since 3.0
	 * @access public
	 * @param string $url URL to purge.
	 */
	public function purge_url( $url ) {
		$this->cls( 'Purge' )->purge_url( $url );
	}

	/**
	 * Set cacheable
	 *
	 * Marks the current request as cacheable.
	 *
	 * @since 3.0
	 * @access public
	 * @param string|bool $reason Optional reason for setting cacheable.
	 */
	public function set_cacheable( $reason = false ) {
		$this->cls( 'Control' )->set_cacheable( $reason );
	}

	/**
	 * Check ESI enabled
	 *
	 * Returns whether ESI is enabled.
	 *
	 * @since 3.0
	 * @access public
	 * @return bool True if ESI is enabled, false otherwise.
	 */
	public function esi_enabled() {
		return $this->cls( 'Router' )->esi_enabled();
	}

	/**
	 * Get TTL
	 *
	 * Retrieves the cache TTL (time to live).
	 *
	 * @since 3.0
	 * @access public
	 * @return int Cache TTL value.
	 */
	public function get_ttl() {
		return $this->cls( 'Control' )->get_ttl();
	}

	/**
	 * Generate ESI block URL
	 *
	 * Generates a URL for an ESI block.
	 *
	 * @since 3.0
	 * @access public
	 * @param string $block_id    ESI block ID.
	 * @param string $wrapper     Wrapper identifier.
	 * @param array  $params      Parameters for the ESI block.
	 * @param string $control     Cache control settings.
	 * @param bool   $silence     Silence output flag.
	 * @param bool   $preserved   Preserved flag.
	 * @param bool   $svar        Server variable flag.
	 * @param array  $inline_param Inline parameters.
	 * @return string ESI block URL.
	 */
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
		return $this->cls( 'ESI' )->sub_esi_block( $block_id, $wrapper, $params, $control, $silence, $preserved, $svar, $inline_param );
	}

	/**
	 * Set and sync conf
	 *
	 * Updates and synchronizes configuration settings.
	 *
	 * @since 7.2
	 * @access public
	 * @param bool|array $the_matrix Configuration data to update.
	 */
	public function save_conf( $the_matrix = false ) {
		$this->cls( 'Conf' )->update_confs( $the_matrix );
	}
}
