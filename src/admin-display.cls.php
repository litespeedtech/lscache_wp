<?php
/**
 * The admin-panel specific functionality of the plugin.
 *
 * Provides admin page rendering, notices, enqueueing of assets,
 * menu registrations, and various admin utilities.
 *
 * @since      1.0.0
 * @package    LiteSpeed
 * @subpackage LiteSpeed/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Admin_Display
 *
 * Handles WP-Admin UI for LiteSpeed Cache.
 */
class Admin_Display extends Base {

	/**
	 * Log tag for Admin_Display.
	 *
	 * @var string
	 */
	const LOG_TAG = '👮‍♀️';

	/**
	 * Notice class (info/blue).
	 *
	 * @var string
	 */
	const NOTICE_BLUE = 'notice notice-info';
	/**
	 * Notice class (success/green).
	 *
	 * @var string
	 */
	const NOTICE_GREEN = 'notice notice-success';
	/**
	 * Notice class (error/red).
	 *
	 * @var string
	 */
	const NOTICE_RED = 'notice notice-error';
	/**
	 * Notice class (warning/yellow).
	 *
	 * @var string
	 */
	const NOTICE_YELLOW = 'notice notice-warning';
	/**
	 * Option key for one-time messages.
	 *
	 * @var string
	 */
	const DB_MSG = 'messages';
	/**
	 * Option key for pinned messages.
	 *
	 * @var string
	 */
	const DB_MSG_PIN = 'msg_pin';

	/**
	 * Purge by: category.
	 *
	 * @var string
	 */
	const PURGEBY_CAT = '0';
	/**
	 * Purge by: post ID.
	 *
	 * @var string
	 */
	const PURGEBY_PID = '1';
	/**
	 * Purge by: tag.
	 *
	 * @var string
	 */
	const PURGEBY_TAG = '2';
	/**
	 * Purge by: URL.
	 *
	 * @var string
	 */
	const PURGEBY_URL = '3';

	/**
	 * Purge selection field name.
	 *
	 * @var string
	 */
	const PURGEBYOPT_SELECT = 'purgeby';
	/**
	 * Purge list field name.
	 *
	 * @var string
	 */
	const PURGEBYOPT_LIST = 'purgebylist';

	/**
	 * Dismiss key for messages.
	 *
	 * @var string
	 */
	const DB_DISMISS_MSG = 'dismiss';
	/**
	 * Rule conflict flag (on).
	 *
	 * @var string
	 */
	const RULECONFLICT_ON = 'ExpiresDefault_1';
	/**
	 * Rule conflict dismissed flag.
	 *
	 * @var string
	 */
	const RULECONFLICT_DISMISSED = 'ExpiresDefault_0';

	/**
	 * Router type for QC hide banner.
	 *
	 * @var string
	 */
	const TYPE_QC_HIDE_BANNER = 'qc_hide_banner';
	/**
	 * Cookie name for QC hide banner.
	 *
	 * @var string
	 */
	const COOKIE_QC_HIDE_BANNER = 'litespeed_qc_hide_banner';

	/**
	 * Internal messages cache.
	 *
	 * @var array<string,string>
	 */
	protected $messages = array();

	/**
	 * Cached default settings.
	 *
	 * @var array<string,mixed>
	 */
	protected $default_settings = array();

	/**
	 * Whether current context is network admin.
	 *
	 * @var bool
	 */
	protected $_is_network_admin = false;

	/**
	 * Whether multisite is enabled.
	 *
	 * @var bool
	 */
	protected $_is_multisite = false;

	/**
	 * Incremental form submit button index.
	 *
	 * @var int
	 */
	private $_btn_i = 0;

	/**
	 * List of settings with filters and return type.
	 *
	 * @since 7.4
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected static $settings_filters = [
		// Crawler - Blocklist.
		'crawler-blocklist' => [
			'filter' => 'litespeed_crawler_disable_blocklist',
			'type'   => 'boolean',
		],
		// Crawler - Settings.
		self::O_CRAWLER_LOAD_LIMIT => [
			'filter' => [ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE, Base::ENV_CRAWLER_LOAD_LIMIT ],
			'type'   => 'input',
		],
		// Cache - ESI.
		self::O_ESI_NONCE => [
			'filter' => 'litespeed_esi_nonces',
		],
		// Page Optimization - CSS.
		'optm-ucss_per_pagetype' => [
			'filter' => 'litespeed_ucss_per_pagetype',
			'type'   => 'boolean',
		],
		// Page Optimization - Media.
		self::O_MEDIA_ADD_MISSING_SIZES => [
			'filter' => 'litespeed_media_ignore_remote_missing_sizes',
			'type'   => 'boolean',
		],
		// Page Optimization - Media Exclude.
		self::O_MEDIA_LAZY_EXC => [
			'filter' => 'litespeed_media_lazy_img_excludes',
		],
		// Page Optimization - Tuning (JS).
		self::O_OPTM_JS_DELAY_INC => [
			'filter' => 'litespeed_optm_js_delay_inc',
		],
		self::O_OPTM_JS_EXC => [
			'filter' => 'litespeed_optimize_js_excludes',
		],
		self::O_OPTM_JS_DEFER_EXC => [
			'filter' => 'litespeed_optm_js_defer_exc',
		],
		self::O_OPTM_GM_JS_EXC => [
			'filter' => 'litespeed_optm_gm_js_exc',
		],
		self::O_OPTM_EXC => [
			'filter' => 'litespeed_optm_uri_exc',
		],
		// Page Optimization - Tuning (CSS).
		self::O_OPTM_CSS_EXC => [
			'filter' => 'litespeed_optimize_css_excludes',
		],
		self::O_OPTM_UCSS_EXC => [
			'filter' => 'litespeed_ucss_exc',
		],
	];

	/**
	 * Flat pages map: menu slug to template metadata.
	 *
	 * @var array<string,array{title:string,tpl:string,network?:bool}>
	 */
	private $_pages = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.7
	 */
	public function __construct() {
		$this->_pages = [
			// Site-level pages
			'litespeed'               => [ 'title' => __( 'Dashboard', 'litespeed-cache' ), 'tpl' => 'dash/entry.tpl.php' ],
			'litespeed-optimax'       => [ 'title' => __( 'OptimaX', 'litespeed-cache' ), 'tpl' => 'optimax/entry.tpl.php', 'scope' => 'site' ],
			'litespeed-presets'       => [ 'title' => __( 'Presets', 'litespeed-cache' ), 'tpl' => 'presets/entry.tpl.php', 'scope' => 'site' ],
			'litespeed-general'       => [ 'title' => __( 'General', 'litespeed-cache' ), 'tpl' => 'general/entry.tpl.php' ],
			'litespeed-cache'         => [ 'title' => __( 'Cache', 'litespeed-cache' ), 'tpl' => 'cache/entry.tpl.php' ],
			'litespeed-cdn'           => [ 'title' => __( 'CDN', 'litespeed-cache' ), 'tpl' => 'cdn/entry.tpl.php', 'scope' => 'site' ],
			'litespeed-img_optm'      => [ 'title' => __( 'Image Optimization', 'litespeed-cache'), 'tpl' => 'img_optm/entry.tpl.php' ],
			'litespeed-page_optm'     => [ 'title' => __( 'Page Optimization', 'litespeed-cache' ), 'tpl' => 'page_optm/entry.tpl.php', 'scope' => 'site' ],
			'litespeed-db_optm'       => [ 'title' => __( 'Database', 'litespeed-cache' ), 'tpl' => 'db_optm/entry.tpl.php' ],
			'litespeed-crawler'       => [ 'title' => __( 'Crawler', 'litespeed-cache' ), 'tpl' => 'crawler/entry.tpl.php', 'scope' => 'site' ],
			'litespeed-toolbox'       => [ 'title' => __( 'Toolbox', 'litespeed-cache' ), 'tpl' => 'toolbox/entry.tpl.php' ],
		];

		// main css
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );
		// Main js
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->_is_network_admin = is_network_admin();
		$this->_is_multisite     = is_multisite();

		// Quick access menu
		$manage = ( $this->_is_multisite && $this->_is_network_admin ) ? 'manage_network_options' : 'manage_options';

		if ( current_user_can( $manage ) ) {
			add_action( 'wp_before_admin_bar_render', array( GUI::cls(), 'backend_shortcut' ) );

			// `admin_notices` is after `admin_enqueue_scripts`.
			add_action( $this->_is_network_admin ? 'network_admin_notices' : 'admin_notices', array( $this, 'display_messages' ) );
		}

		/**
		 * In case this is called outside the admin page.
		 *
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// add menus (Also check for mu-plugins)
		if ( $this->_is_network_admin && ( is_plugin_active_for_network( LSCWP_BASENAME ) || defined( 'LSCWP_MU_PLUGIN' ) ) ) {
			add_action( 'network_admin_menu', array( $this, 'register_admin_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		}

		$this->cls( 'Metabox' )->register_settings();
	}

	/**
	 * Echo a translated section title.
	 *
	 * @since 3.0
	 *
	 * @param string $id Language key.
	 * @return void
	 */
	public function title( $id ) {
		echo wp_kses_post( Lang::title( $id ) );
	}

	/**
	 * Bind per-page admin hooks for a given page hook.
	 *
	 * Adds footer text filter and preview banner when loading the page.
	 *
	 * @param string $hook Page hook suffix returned by add_*_page().
	 * @return void
	 */
	private function bind_page( $hook ) {
		add_action( "load-$hook", function () {
			add_filter(
				'admin_footer_text',
				function ( $footer_text ) {
					$this->cls( 'Cloud' )->maybe_preview_banner();
					require_once LSCWP_DIR . 'tpl/inc/admin_footer.php';
					return $footer_text;
				},
				1
			);
			// Add unified body class for settings page and top-level page
			add_filter( 'admin_body_class', function ( $classes ) {
				$screen = get_current_screen();
				if ( $screen && in_array( $screen->id, [ 'settings_page_litespeed-cache-options', 'toplevel_page_litespeed' ], true ) ) {
					$classes .= ' litespeed-cache_page_litespeed';
				}
				return $classes;
			} );
		} );
	}

	/**
	 * Render an admin page by slug using its mapped template file.
	 *
	 * @param string $slug The menu slug registered in $_pages.
	 * @return void
	 */
	private function render_page( $slug ) {
		$tpl = LSCWP_DIR . 'tpl/' . $this->_pages[ $slug ]['tpl'];
		is_file( $tpl ) ? require $tpl : wp_die( 'Template not found' );
	}

	/**
	 * Register the admin menu display.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_admin_menu() {
		$capability = $this->_is_network_admin ? 'manage_network_options' : 'manage_options';
		$scope      = $this->_is_network_admin ? 'network' : 'site';

		add_menu_page(
			'LiteSpeed Cache',
			'LiteSpeed Cache',
			$capability,
			'litespeed'
		);

		foreach ( $this->_pages as $slug => $meta ) {
			if ( 'litespeed-optimax' === $slug && !defined( 'LITESPEED_OX' ) ) {
				continue;
			}
			if ( ! empty( $meta['scope'] ) && $meta['scope'] !== $scope ) {
				continue;
			}
			$hook = add_submenu_page(
				'litespeed',
				$meta['title'],
				$meta['title'],
				$capability,
				$slug,
				function () use ( $slug ) {
					$this->render_page( $slug );
				}
			);
			$this->bind_page( $hook );
		}

		// sub menus under options.
		$hook = add_options_page(
			'LiteSpeed Cache',
			'LiteSpeed Cache',
			$capability,
			'litespeed-cache-options',
			function () {
				$this->render_page( 'litespeed-cache' );
			}
		);
		$this->bind_page( $hook );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.14
	 * @return void
	 */
	public function enqueue_style() {
		wp_enqueue_style( Core::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'assets/css/litespeed.css', array(), Core::VER, 'all' );
        wp_enqueue_style( Core::PLUGIN_NAME . '-dark-mode', LSWCP_PLUGIN_URL . 'assets/css/litespeed-dark-mode.css', array(), Core::VER, 'all' );
	}

	/**
	 * Register/enqueue the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 * @since 7.3 Added deactivation modal code.
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_script( Core::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'assets/js/litespeed-cache-admin.js', array(), Core::VER, false );

		$localize_data = array();
		if ( GUI::has_whm_msg() ) {
			$ajax_url_dismiss_whm                  = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_WHM, true );
			$localize_data['ajax_url_dismiss_whm'] = $ajax_url_dismiss_whm;
		}

		if ( GUI::has_msg_ruleconflict() ) {
			$ajax_url                                       = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_EXPIRESDEFAULT, true );
			$localize_data['ajax_url_dismiss_ruleconflict'] = $ajax_url;
		}

		// Injection to LiteSpeed pages
		global $pagenow;
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'admin.php' === $pagenow && $page && ( 0 === strpos( $page, 'litespeed-' ) || 'litespeed' === $page ) ) {
			if ( in_array( $page, array( 'litespeed-crawler', 'litespeed-cdn' ), true ) ) {
				// Babel JS type correction
				add_filter( 'script_loader_tag', array( $this, 'babel_type' ), 10, 3 );

				wp_enqueue_script( Core::PLUGIN_NAME . '-lib-react', LSWCP_PLUGIN_URL . 'assets/js/react.min.js', array(), Core::VER, false );
				wp_enqueue_script( Core::PLUGIN_NAME . '-lib-babel', LSWCP_PLUGIN_URL . 'assets/js/babel.min.js', array(), Core::VER, false );
			}

			// Crawler Cookie Simulation
			if ( 'litespeed-crawler' === $page ) {
				wp_enqueue_script( Core::PLUGIN_NAME . '-crawler', LSWCP_PLUGIN_URL . 'assets/js/component.crawler.js', array(), Core::VER, false );

				$localize_data['lang']                              = array();
				$localize_data['lang']['cookie_name']               = __( 'Cookie Name', 'litespeed-cache' );
				$localize_data['lang']['cookie_value']              = __( 'Cookie Values', 'litespeed-cache' );
				$localize_data['lang']['one_per_line']              = Doc::one_per_line( true );
				$localize_data['lang']['remove_cookie_simulation']  = __( 'Remove cookie simulation', 'litespeed-cache' );
				$localize_data['lang']['add_cookie_simulation_row'] = __( 'Add new cookie to simulate', 'litespeed-cache' );
				if ( empty( $localize_data['ids'] ) ) {
					$localize_data['ids'] = array();
				}
				$localize_data['ids']['crawler_cookies'] = self::O_CRAWLER_COOKIES;
			}

			// CDN mapping
			if ( 'litespeed-cdn' === $page ) {
				$home_url = home_url( '/' );
				$parsed   = wp_parse_url( $home_url );
				if ( ! empty( $parsed['scheme'] ) ) {
					$home_url = str_replace( $parsed['scheme'] . ':', '', $home_url );
				}
				$cdn_url = 'https://cdn.' . substr( $home_url, 2 );

				wp_enqueue_script( Core::PLUGIN_NAME . '-cdn', LSWCP_PLUGIN_URL . 'assets/js/component.cdn.js', array(), Core::VER, false );
				$localize_data['lang']                         = array();
				$localize_data['lang']['cdn_mapping_url']      = Lang::title( self::CDN_MAPPING_URL );
				$localize_data['lang']['cdn_mapping_inc_img']  = Lang::title( self::CDN_MAPPING_INC_IMG );
				$localize_data['lang']['cdn_mapping_inc_css']  = Lang::title( self::CDN_MAPPING_INC_CSS );
				$localize_data['lang']['cdn_mapping_inc_js']   = Lang::title( self::CDN_MAPPING_INC_JS );
				$localize_data['lang']['cdn_mapping_filetype'] = Lang::title( self::CDN_MAPPING_FILETYPE );
				$localize_data['lang']['cdn_mapping_url_desc'] = sprintf( __( 'CDN URL to be used. For example, %s', 'litespeed-cache' ), '<code>' . esc_html( $cdn_url ) . '</code>' );
				$localize_data['lang']['one_per_line']         = Doc::one_per_line( true );
				$localize_data['lang']['cdn_mapping_remove']   = __( 'Remove CDN URL', 'litespeed-cache' );
				$localize_data['lang']['add_cdn_mapping_row']  = __( 'Add new CDN URL', 'litespeed-cache' );
				$localize_data['lang']['on']                   = __( 'ON', 'litespeed-cache' );
				$localize_data['lang']['off']                  = __( 'OFF', 'litespeed-cache' );
				if ( empty( $localize_data['ids'] ) ) {
					$localize_data['ids'] = array();
				}
				$localize_data['ids']['cdn_mapping'] = self::O_CDN_MAPPING;
			}
		}

		// Load iziModal JS and CSS
		$show_deactivation_modal = ( is_multisite() && ! is_network_admin() ) ? false : true;
		if ( $show_deactivation_modal && 'plugins.php' === $pagenow ) {
			wp_enqueue_script( Core::PLUGIN_NAME . '-iziModal', LSWCP_PLUGIN_URL . 'assets/js/iziModal.min.js', array(), Core::VER, true );
			wp_enqueue_style( Core::PLUGIN_NAME . '-iziModal', LSWCP_PLUGIN_URL . 'assets/css/iziModal.min.css', array(), Core::VER, 'all' );
			add_action( 'admin_footer', array( $this, 'add_deactivation_html' ) );
		}

		if ( $localize_data ) {
			wp_localize_script( Core::PLUGIN_NAME, 'litespeed_data', $localize_data );
		}

		wp_enqueue_script( Core::PLUGIN_NAME );
	}

	/**
	 * Add modal HTML on Plugins screen.
	 *
	 * @since 7.3
	 * @return void
	 */
	public function add_deactivation_html() {
		require LSCWP_DIR . 'tpl/inc/modal.deactivation.php';
	}

	/**
	 * Filter the script tag for specific handles to set Babel type.
	 *
	 * @since 3.6
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string The filtered script tag.
	 */
	public function babel_type( $tag, $handle, $src ) {
		if ( Core::PLUGIN_NAME . '-crawler' !== $handle && Core::PLUGIN_NAME . '-cdn' !== $handle ) {
			return $tag;
		}

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		return '<script src="' . Str::trim_quotes( $src ) . '" type="text/babel"></script>';
	}

	/**
	 * Callback that adds LiteSpeed Cache's action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $links Previously added links from other plugins.
	 * @return array<string> Links with the LiteSpeed Cache one appended.
	 */
	public function add_plugin_links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=litespeed-cache' ) ) . '">' . esc_html__( 'Settings', 'litespeed-cache' ) . '</a>';

		return $links;
	}

	/**
	 * Build a single notice HTML string.
	 *
	 * @since 1.0.7
	 *
	 * @param string $color              The color CSS class for the notice.
	 * @param string $str                The notice message.
	 * @param bool   $irremovable        If true, the notice cannot be dismissed.
	 * @param string $additional_classes Additional classes to add to the wrapper.
	 * @return string The built notice HTML.
	 */
	public static function build_notice( $color, $str, $irremovable = false, $additional_classes = '' ) {
		$cls = $color;
		if ( $irremovable ) {
			$cls .= ' litespeed-irremovable';
		} else {
			$cls .= ' is-dismissible';
		}
		if ( $additional_classes ) {
			$cls .= ' ' . $additional_classes;
		}

		// possible translation
		$str = Lang::maybe_translate( $str );

		return '<div class="litespeed_icon ' . esc_attr( $cls ) . '"><p>' . wp_kses_post( $str ) . '</p></div>';
	}

	/**
	 * Display info notice.
	 *
	 * @since 1.6.5
	 *
	 * @param string|array<string> $msg                Message or list of messages.
	 * @param bool                 $do_echo            Echo immediately instead of storing.
	 * @param bool                 $irremovable        If true, cannot be dismissed.
	 * @param string               $additional_classes Extra CSS classes.
	 * @return void
	 */
	public static function info( $msg, $do_echo = false, $irremovable = false, $additional_classes = '' ) {
		self::add_notice( self::NOTICE_BLUE, $msg, $do_echo, $irremovable, $additional_classes );
	}

	/**
	 * Display note (warning) notice.
	 *
	 * @since 1.6.5
	 *
	 * @param string|array<string> $msg                Message or list of messages.
	 * @param bool                 $do_echo            Echo immediately instead of storing.
	 * @param bool                 $irremovable        If true, cannot be dismissed.
	 * @param string               $additional_classes Extra CSS classes.
	 * @return void
	 */
	public static function note( $msg, $do_echo = false, $irremovable = false, $additional_classes = '' ) {
		self::add_notice( self::NOTICE_YELLOW, $msg, $do_echo, $irremovable, $additional_classes );
	}

	/**
	 * Display success notice.
	 *
	 * @since 1.6
	 *
	 * @param string|array<string> $msg                Message or list of messages.
	 * @param bool                 $do_echo            Echo immediately instead of storing.
	 * @param bool                 $irremovable        If true, cannot be dismissed.
	 * @param string               $additional_classes Extra CSS classes.
	 * @return void
	 */
	public static function success( $msg, $do_echo = false, $irremovable = false, $additional_classes = '' ) {
		self::add_notice( self::NOTICE_GREEN, $msg, $do_echo, $irremovable, $additional_classes );
	}

	/**
	 * Deprecated alias for success().
	 *
	 * @deprecated 4.7 Will drop in v7.5. Use success().
	 *
	 * @param string|array<string> $msg                Message or list of messages.
	 * @param bool                 $do_echo            Echo immediately instead of storing.
	 * @param bool                 $irremovable        If true, cannot be dismissed.
	 * @param string               $additional_classes Extra CSS classes.
	 * @return void
	 */
	public static function succeed( $msg, $do_echo = false, $irremovable = false, $additional_classes = '' ) {
		self::success( $msg, $do_echo, $irremovable, $additional_classes );
	}

	/**
	 * Display error notice.
	 *
	 * @since 1.6
	 *
	 * @param string|array<string> $msg                Message or list of messages.
	 * @param bool                 $do_echo            Echo immediately instead of storing.
	 * @param bool                 $irremovable        If true, cannot be dismissed.
	 * @param string               $additional_classes Extra CSS classes.
	 * @return void
	 */
	public static function error( $msg, $do_echo = false, $irremovable = false, $additional_classes = '' ) {
		self::add_notice( self::NOTICE_RED, $msg, $do_echo, $irremovable, $additional_classes );
	}

	/**
	 * Add unique (irremovable optional) messages.
	 *
	 * @since 4.7
	 *
	 * @param string               $color_mode  One of info|note|success|error.
	 * @param string|array<string> $msgs        Message(s).
	 * @param bool                 $irremovable If true, cannot be dismissed.
	 * @return void
	 */
	public static function add_unique_notice( $color_mode, $msgs, $irremovable = false ) {
		if ( ! is_array( $msgs ) ) {
			$msgs = array( $msgs );
		}

		$color_map = array(
			'info'    => self::NOTICE_BLUE,
			'note'    => self::NOTICE_YELLOW,
			'success' => self::NOTICE_GREEN,
			'error'   => self::NOTICE_RED,
		);
		if ( empty( $color_map[ $color_mode ] ) ) {
			self::debug( 'Wrong admin display color mode!' );
			return;
		}
		$color = $color_map[ $color_mode ];

		// Go through to make sure unique.
		$filtered_msgs = array();
		foreach ( $msgs as $k => $str ) {
			if ( is_numeric( $k ) ) {
				$k = md5( $str );
			} // Use key to make it overwritable to previous same msg.
			$filtered_msgs[ $k ] = $str;
		}

		self::add_notice( $color, $filtered_msgs, false, $irremovable );
	}

	/**
	 * Add a notice to display on the admin page (store or echo).
	 *
	 * @since 1.0.7
	 *
	 * @param string               $color              Notice color CSS class.
	 * @param string|array<string> $msg                Message(s).
	 * @param bool                 $do_echo            Echo immediately instead of storing.
	 * @param bool                 $irremovable        If true, cannot be dismissed.
	 * @param string               $additional_classes Extra classes for wrapper.
	 * @return void
	 */
	public static function add_notice( $color, $msg, $do_echo = false, $irremovable = false, $additional_classes = '' ) {
		// Bypass adding for CLI or cron
		if ( defined( 'LITESPEED_CLI' ) || wp_doing_cron() ) {
			// WP CLI will show the info directly
			if ( defined( 'WP_CLI' ) && constant('WP_CLI') ) {
				if ( ! is_array( $msg ) ) {
					$msg = array( $msg );
				}
				foreach ( $msg as $v ) {
					$v = wp_strip_all_tags( $v );
					if ( self::NOTICE_RED === $color ) {
						\WP_CLI::error( $v, false );
					} else {
						\WP_CLI::success( $v );
					}
				}
			}
			return;
		}

		if ( $do_echo ) {
			echo self::build_notice( $color, $msg, $irremovable, $additional_classes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$msg_name = $irremovable ? self::DB_MSG_PIN : self::DB_MSG;

		$messages = self::get_option( $msg_name, array() );
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		if ( is_array( $msg ) ) {
			foreach ( $msg as $k => $str ) {
				$messages[ $k ] = self::build_notice( $color, $str, $irremovable, $additional_classes );
			}
		} else {
			$messages[] = self::build_notice( $color, $msg, $irremovable, $additional_classes );
		}
		$messages = array_unique( $messages );
		self::update_option( $msg_name, $messages );
	}

	/**
	 * Display notices and errors in dashboard.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function display_messages() {
		if ( ! defined( 'LITESPEED_CONF_LOADED' ) ) {
			$this->_in_upgrading();
		}

		if ( GUI::has_whm_msg() ) {
			$this->show_display_installed();
		}

		Data::cls()->check_upgrading_msg();

		// If is in dev version, always check latest update
		Cloud::cls()->check_dev_version();

		// One time msg
		$messages       = self::get_option( self::DB_MSG, array() );
		$added_thickbox = false;
		if ( is_array( $messages ) ) {
			foreach ( $messages as $msg ) {
				// Added for popup links
				if ( strpos( $msg, 'TB_iframe' ) && ! $added_thickbox ) {
					add_thickbox();
					$added_thickbox = true;
				}
				echo wp_kses_post( $msg );
			}
		}
		if ( -1 !== $messages ) {
			self::update_option( self::DB_MSG, -1 );
		}

		// Pinned msg
		$messages = self::get_option( self::DB_MSG_PIN, array() );
		if ( is_array( $messages ) ) {
			foreach ( $messages as $k => $msg ) {
				// Added for popup links
				if ( strpos( $msg, 'TB_iframe' ) && ! $added_thickbox ) {
					add_thickbox();
					$added_thickbox = true;
				}

				// Append close btn
				if ( '</div>' === substr( $msg, -6 ) ) {
					$link = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_PIN, false, null, array( 'msgid' => $k ) );
					$msg  =
						substr( $msg, 0, -6 ) .
						'<p><a href="' .
						esc_url( $link ) .
						'" class="button litespeed-btn-primary litespeed-btn-mini">' .
						esc_html__( 'Dismiss', 'litespeed-cache' ) .
						'</a>' .
						'</p></div>';
				}
				echo wp_kses_post( $msg );
			}
		}

		if ( empty( $_GET['page'] ) || 0 !== strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'litespeed' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			global $pagenow;
			if ( 'plugins.php' !== $pagenow ) {
				return;
			}
		}

		if ( ! $this->conf( self::O_NEWS ) ) {
			return;
		}

		// Show promo from cloud
		Cloud::cls()->show_promo();

		/**
		 * Check promo msg first
		 *
		 * @since 2.9
		 */
		GUI::cls()->show_promo();

		// Show version news
		Cloud::cls()->news();
	}

	/**
	 * Dismiss pinned msg.
	 *
	 * @since 3.5.2
	 * @return void
	 */
	public static function dismiss_pin() {
		if ( ! isset( $_GET['msgid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$messages = self::get_option( self::DB_MSG_PIN, array() );
		$msgid    = sanitize_text_field( wp_unslash( $_GET['msgid'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! is_array( $messages ) || empty( $messages[ $msgid ] ) ) {
			return;
		}

		unset( $messages[ $msgid ] );
		if ( ! $messages ) {
			$messages = -1;
		}
		self::update_option( self::DB_MSG_PIN, $messages );
	}

	/**
	 * Dismiss pinned msg by msg content.
	 *
	 * @since 7.0
	 *
	 * @param string $content     Message content.
	 * @param string $color       Color CSS class.
	 * @param bool   $irremovable Is irremovable.
	 * @return void
	 */
	public static function dismiss_pin_by_content( $content, $color, $irremovable ) {
		$content  = self::build_notice( $color, $content, $irremovable );
		$messages = self::get_option( self::DB_MSG_PIN, array() );
		$hit      = false;
		if ( -1 !== $messages ) {
			foreach ( $messages as $k => $v ) {
				if ( $v === $content ) {
					unset( $messages[ $k ] );
					$hit = true;
					self::debug( '✅ pinned msg content hit. Removed' );
					break;
				}
			}
		}
		if ( $hit ) {
			if ( ! $messages ) {
				$messages = -1;
			}
			self::update_option( self::DB_MSG_PIN, $messages );
		} else {
			self::debug( '❌ No pinned msg content hit' );
		}
	}

	/**
	 * Hooked to the in_widget_form action.
	 * Appends LiteSpeed Cache settings to the widget edit settings screen.
	 * This will append the esi on/off selector and ttl text.
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Widget $widget     The widget instance (passed by reference).
	 * @param mixed      $return_val Return param (unused).
	 * @param array      $instance   The widget instance's settings.
	 * @return void
	 */
	public function show_widget_edit( $widget, $return_val, $instance ) {
		require LSCWP_DIR . 'tpl/esi_widget_edit.php';
	}

	/**
	 * Outputs a notice when the plugin is installed via WHM.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function show_display_installed() {
		require_once LSCWP_DIR . 'tpl/inc/show_display_installed.php';
	}

	/**
	 * Display error cookie msg.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public static function show_error_cookie() {
		require_once LSCWP_DIR . 'tpl/inc/show_error_cookie.php';
	}

	/**
	 * Display warning if lscache is disabled.
	 *
	 * @since 2.1
	 * @return void
	 */
	public function cache_disabled_warning() {
		include LSCWP_DIR . 'tpl/inc/check_cache_disabled.php';
	}

	/**
	 * Display conf data upgrading banner.
	 *
	 * @since 2.1
	 * @access private
	 * @return void
	 */
	private function _in_upgrading() {
		include LSCWP_DIR . 'tpl/inc/in_upgrading.php';
	}

	/**
	 * Output LiteSpeed form open tag and hidden fields.
	 *
	 * @since 3.0
	 *
	 * @param string|false $action     Router action.
	 * @param string|false $type       Router type.
	 * @param bool         $has_upload Whether form has file uploads.
	 * @return void
	 */
	public function form_action( $action = false, $type = false, $has_upload = false ) {
		if ( ! $action ) {
			$action = Router::ACTION_SAVE_SETTINGS;
		}

		if ( ! defined( 'LITESPEED_CONF_LOADED' ) ) {
			echo '<div class="litespeed-relative">';
		} else {
			$current = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			if ( $has_upload ) {
				echo '<form method="post" action="' . esc_url( $current ) . '" class="litespeed-relative" enctype="multipart/form-data">';
			} else {
				echo '<form method="post" action="' . esc_url( $current ) . '" class="litespeed-relative">';
			}
		}

		echo '<input type="hidden" name="' . esc_attr( Router::ACTION ) . '" value="' . esc_attr( $action ) . '" />';
		if ( $type ) {
			echo '<input type="hidden" name="' . esc_attr( Router::TYPE ) . '" value="' . esc_attr( $type ) . '" />';
		}
		wp_nonce_field( $action, Router::NONCE );
	}

	/**
	 * Output LiteSpeed form end (submit + closing tags).
	 *
	 * @since 3.0
	 *
	 * @return void
	 */
	public function form_end() {
		echo "<div class='litespeed-top20'></div>";

		if ( ! defined( 'LITESPEED_CONF_LOADED' ) ) {
			submit_button( __( 'Save Changes', 'litespeed-cache' ), 'secondary litespeed-duplicate-float', 'litespeed-submit', true, array( 'disabled' => 'disabled' ) );

			echo '</div>';
		} else {
			submit_button(
				__( 'Save Changes', 'litespeed-cache' ),
				'primary litespeed-duplicate-float',
				'litespeed-submit',
				true,
				array(
					'id' => 'litespeed-submit-' . $this->_btn_i++,
				)
			);

			echo '</form>';
		}
	}

	/**
	 * Register a setting for saving.
	 *
	 * @since 3.0
	 *
	 * @param string $id Setting ID.
	 * @return void
	 */
	public function enroll( $id ) {
		echo '<input type="hidden" name="' . esc_attr( Admin_Settings::ENROLL ) . '[]" value="' . esc_attr( $id ) . '" />';
	}

	/**
	 * Build a textarea input.
	 *
	 * @since 1.1.0
	 *
	 * @param string     $id   Setting ID.
	 * @param int|false  $cols Columns count.
	 * @param string|nil $val  Pre-set value.
	 * @return void
	 */
	public function build_textarea( $id, $cols = false, $val = null ) {
		if ( null === $val ) {
			$val = $this->conf( $id, true );

			if ( is_array( $val ) ) {
				$val = implode( "\n", $val );
			}
		}

		if ( ! $cols ) {
			$cols = 80;
		}

		$rows = $this->get_textarea_rows( $val );

		$this->enroll( $id );

		echo "<textarea name='" . esc_attr( $id ) . "' rows='" . (int) $rows . "' cols='" . (int) $cols . "'>" . esc_textarea( $val ) . '</textarea>';

		$this->_check_overwritten( $id );
	}

	/**
	 * Calculate textarea rows.
	 *
	 * @since 7.4
	 *
	 * @param string $val Text area value.
	 * @return int Number of rows to use.
	 */
	public function get_textarea_rows( $val ) {
		$rows  = 5;
		$lines = substr_count( (string) $val, "\n" ) + 2;
		if ( $lines > $rows ) {
			$rows = $lines;
		}
		if ( $rows > 40 ) {
			$rows = 40;
		}

		return $rows;
	}

	/**
	 * Build a text input field.
	 *
	 * @since 1.1.0
	 *
	 * @param string      $id       Setting ID.
	 * @param string|null $cls      CSS class.
	 * @param string|null $val      Value.
	 * @param string      $type     Input type.
	 * @param bool        $disabled Whether disabled.
	 * @return void
	 */
	public function build_input( $id, $cls = null, $val = null, $type = 'text', $disabled = false ) {
		if ( null === $val ) {
			$val = $this->conf( $id, true );

			// Mask passwords.
			if ( $this->_conf_pswd( $id ) && $val ) {
				$val = str_repeat( '*', strlen( $val ) );
			}
		}

		$label_id = preg_replace( '/\W/', '', $id );

		if ( 'text' === $type ) {
			$cls = "regular-text $cls";
		}

		if ( $disabled ) {
			echo "<input type='" . esc_attr( $type ) . "' class='" . esc_attr( $cls ) . "' value='" . esc_attr( $val ) . "' id='input_" . esc_attr( $label_id ) . "' disabled /> ";
		} else {
			$this->enroll( $id );
			echo "<input type='" . esc_attr( $type ) . "' class='" . esc_attr( $cls ) . "' name='" . esc_attr( $id ) . "' value='" . esc_attr( $val ) . "' id='input_" . esc_attr( $label_id ) . "' /> ";
		}

		$this->_check_overwritten( $id );
	}

	/**
	 * Build a checkbox HTML snippet.
	 *
	 * @since 1.1.0
	 *
	 * @param string     $id      Setting ID.
	 * @param string     $title   Checkbox label (HTML allowed).
	 * @param bool|null  $checked Whether checked.
	 * @param int|string $value   Checkbox value.
	 * @return void
	 */
	public function build_checkbox( $id, $title, $checked = null, $value = 1 ) {
		if ( null === $checked && $this->conf( $id, true ) ) {
			$checked = true;
		}

		$label_id = preg_replace( '/\W/', '', $id );

		if ( 1 !== $value ) {
			$label_id .= '_' . $value;
		}

		$this->enroll( $id );

		echo "<div class='litespeed-tick'>
			<input type='checkbox' name='" . esc_attr( $id ) . "' id='input_checkbox_" . esc_attr( $label_id ) . "' value='" . esc_attr( $value ) . "' " . checked( (bool) $checked, true, false ) . " />
			<label for='input_checkbox_" . esc_attr( $label_id ) . "'>" . wp_kses_post( $title ) . '</label>
		</div>';

		$this->_check_overwritten( $id );
	}

	/**
	 * Build a toggle checkbox snippet.
	 *
	 * @since 1.7
	 *
	 * @param string      $id        Setting ID.
	 * @param bool|null   $checked   Whether enabled.
	 * @param string|null $title_on  Label when on.
	 * @param string|null $title_off Label when off.
	 * @return void
	 */
	public function build_toggle( $id, $checked = null, $title_on = null, $title_off = null ) {
		if ( null === $checked && $this->conf( $id, true ) ) {
			$checked = true;
		}
		if ( null === $title_on ) {
			$title_on  = __( 'ON', 'litespeed-cache' );
			$title_off = __( 'OFF', 'litespeed-cache' );
		}
		$cls = $checked ? 'primary' : 'default litespeed-toggleoff';
		echo "<div class='litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-" . esc_attr( $cls ) . "' data-litespeed-toggle-on='primary' data-litespeed-toggle-off='default' data-litespeed_toggle_id='" . esc_attr( $id ) . "' >
				<input name='" . esc_attr( $id ) . "' type='hidden' value='" . esc_attr( $checked ) . "' />
				<div class='litespeed-toggle-group'>
					<label class='litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on'>" . esc_html( $title_on ) . "</label>
					<label class='litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off'>" . esc_html( $title_off ) . "</label>
					<span class='litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default'></span>
				</div>
			</div>";
	}

	/**
	 * Build a switch (radio) field.
	 *
	 * @since 1.1.0
	 * @since 1.7 Removed $disable param.
	 *
	 * @param string                 $id         Setting ID.
	 * @param array<int,mixed>|false $title_list Labels for options (OFF/ON).
	 * @return void
	 */
	public function build_switch( $id, $title_list = false ) {
		$this->enroll( $id );

		echo '<div class="litespeed-switch">';

		if ( ! $title_list ) {
			$title_list = array( __( 'OFF', 'litespeed-cache' ), __( 'ON', 'litespeed-cache' ) );
		}

		foreach ( $title_list as $k => $v ) {
			$this->_build_radio( $id, $k, $v );
		}

		echo '</div>';

		$this->_check_overwritten( $id );
	}

	/**
	 * Build a radio input and echo it.
	 *
	 * @since 1.1.0
	 * @access private
	 *
	 * @param string     $id  Setting ID.
	 * @param int|string $val Value for the radio.
	 * @param string     $txt Label HTML.
	 * @return void
	 */
	private function _build_radio( $id, $val, $txt ) {
		$id_attr = 'input_radio_' . preg_replace( '/\W/', '', $id ) . '_' . $val;

		$default = isset( self::$_default_options[ $id ] ) ? self::$_default_options[ $id ] : self::$_default_site_options[ $id ];

		$is_checked = ! is_string( $default )
			? ( (int) $this->conf( $id, true ) === (int) $val )
			: ( $this->conf( $id, true ) === $val );

		echo "<input type='radio' autocomplete='off' name='" . esc_attr( $id ) . "' id='" . esc_attr( $id_attr ) . "' value='" . esc_attr( $val ) . "' " . checked( $is_checked, true, false ) . " /> <label for='" . esc_attr( $id_attr ) . "'>" . wp_kses_post( $txt ) . '</label>';
	}

	/**
	 * Show overwritten info if value comes from const/primary/filter/server.
	 *
	 * @since 3.0
	 * @since 7.4 Show value from filters. Added type parameter.
	 *
	 * @param string $id Setting ID.
	 * @return void
	 */
	protected function _check_overwritten( $id ) {
		$const_val   = $this->const_overwritten( $id );
		$primary_val = $this->primary_overwritten( $id );
		$filter_val  = $this->filter_overwritten( $id );
		$server_val  = $this->server_overwritten( $id );

		if ( null === $const_val && null === $primary_val && null === $filter_val && null === $server_val ) {
			return;
		}

		// Get value to display.
		$val = null !== $const_val ? $const_val : $primary_val;
		// If we have filter_val will set as new val.
		if ( null !== $filter_val ) {
			$val = $filter_val;
		}
		// If we have server_val will set as new val.
		if ( null !== $server_val ) {
			$val = $server_val;
		}

		// Get type (used for display purpose).
		$type = ( isset( self::$settings_filters[ $id ] ) && isset( self::$settings_filters[ $id ]['type'] ) ) ? self::$settings_filters[ $id ]['type'] : 'textarea';
		if ( ( null !== $const_val || null !== $primary_val ) && null === $filter_val ) {
			$type = 'setting';
		}

		// Get default setting: if settings exist, use default setting, otherwise use filter/server value.
		$default = '';
		if ( isset( self::$_default_options[ $id ] ) || isset( self::$_default_site_options[ $id ] ) ) {
			$default = isset( self::$_default_options[ $id ] ) ? self::$_default_options[ $id ] : self::$_default_site_options[ $id ];
		}
		if ( null !== $filter_val || null !== $server_val ) {
			$default = null !== $filter_val ? $filter_val : $server_val;
		}

		// Set value to display, will be a string.
		if ( is_bool( $default ) ) {
			$val = $val ? __( 'ON', 'litespeed-cache' ) : __( 'OFF', 'litespeed-cache' );
		} else {
			if ( is_array( $val ) ) {
				$val = implode( "\n", $val );
			}
			$val = esc_textarea( $val );
		}

		// Show warning for all types except textarea.
		if ( 'textarea' !== $type ) {
			echo '<div class="litespeed-desc litespeed-warning litespeed-overwrite">⚠️ ';

			if ( null !== $server_val ) {
				// Show $_SERVER value.
				printf( esc_html__( 'This value is overwritten by the %s variable.', 'litespeed-cache' ), '$_SERVER' );
				$val = '$_SERVER["' . $server_val[0] . '"] = ' . $server_val[1];
			} elseif ( null !== $filter_val ) {
				// Show filter value.
				echo esc_html__( 'This value is overwritten by the filter.', 'litespeed-cache' );
			} elseif ( null !== $const_val ) {
				// Show CONSTANT value.
				printf( esc_html__( 'This value is overwritten by the PHP constant %s.', 'litespeed-cache' ), '<code>' . esc_html( Base::conf_const( $id ) ) . '</code>' );
			} elseif ( is_multisite() ) {
				// Show multisite overwrite.
				if ( get_current_blog_id() !== BLOG_ID_CURRENT_SITE && $this->conf( self::NETWORK_O_USE_PRIMARY ) ) {
					echo esc_html__( 'This value is overwritten by the primary site setting.', 'litespeed-cache' );
				} else {
					echo esc_html__( 'This value is overwritten by the Network setting.', 'litespeed-cache' );
				}
			}

			echo ' ' . sprintf( esc_html__( 'Currently set to %s', 'litespeed-cache' ), '<code>' . esc_html( $val ) . '</code>' ) . '</div>';
		} elseif ( 'textarea' === $type && null !== $filter_val ) {
			// Show warning for textarea.
			// Textarea sizes.
			$cols             = 30;
			$rows             = $this->get_textarea_rows( $val );
			$rows_current_val = $this->get_textarea_rows( implode( "\n", $this->conf( $id, true ) ) );
			// If filter rows is bigger than textarea size, equalize them.
			if ( $rows > $rows_current_val ) {
				$rows = $rows_current_val;
			}
			?>
			<div class="litespeed-desc-wrapper">
				<div class="litespeed-desc"><?php echo esc_html__( 'Value from filter applied', 'litespeed-cache' ); ?>:</div>
				<textarea readonly rows="<?php echo (int) $rows; ?>" cols="<?php echo (int) $cols; ?>"><?php echo esc_textarea( $val ); ?></textarea>
			</div>
			<?php
		}
	}

	/**
	 * Display seconds label and readable span.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function readable_seconds() {
		echo esc_html__( 'seconds', 'litespeed-cache' );
		echo ' <span data-litespeed-readable=""></span>';
	}

	/**
	 * Display default value for a setting.
	 *
	 * @since 1.1.1
	 *
	 * @param string $id Setting ID.
	 * @return void
	 */
	public function recommended( $id ) {
		if ( ! $this->default_settings ) {
			$this->default_settings = $this->load_default_vals();
		}

		$val = $this->default_settings[ $id ];

		if ( ! $val ) {
			return;
		}

		if ( ! is_array( $val ) ) {
			printf(
				'%s: <code>%s</code>',
				esc_html__( 'Default value', 'litespeed-cache' ),
				esc_html( $val )
			);
			return;
		}

		$rows = 5;
		$cols = 30;
		// Flexible rows/cols.
		$lines = count( $val ) + 1;
		$rows  = min( max( $lines, $rows ), 40 );
		foreach ( $val as $v ) {
			$cols = max( strlen( $v ), $cols );
		}
		$cols = min( $cols, 150 );

		$val = implode( "\n", $val );
		printf(
			'<div class="litespeed-desc">%s:</div><textarea readonly rows="%d" cols="%d">%s</textarea>',
			esc_html__( 'Default value', 'litespeed-cache' ),
			(int) $rows,
			(int) $cols,
			esc_textarea( $val )
		);
	}

	/**
	 * Validate rewrite rules regex syntax.
	 *
	 * @since 3.0
	 *
	 * @param string $id Setting ID.
	 * @return void
	 */
	protected function _validate_syntax( $id ) {
		$val = $this->conf( $id, true );

		if ( ! $val ) {
			return;
		}

		if ( ! is_array( $val ) ) {
			$val = array( $val );
		}

		foreach ( $val as $v ) {
			if ( ! Utility::syntax_checker( $v ) ) {
				echo '<br /><span class="litespeed-warning"> ❌ ' . esc_html__( 'Invalid rewrite rule', 'litespeed-cache' ) . ': <code>' . wp_kses_post( $v ) . '</code></span>';
			}
		}
	}

	/**
	 * Validate if the .htaccess path is valid.
	 *
	 * @since 3.0
	 *
	 * @param string $id Setting ID.
	 * @return void
	 */
	protected function _validate_htaccess_path( $id ) {
		$val = $this->conf( $id, true );
		if ( ! $val ) {
			return;
		}

		if ( '/.htaccess' !== substr( $val, -10 ) ) {
			echo '<br /><span class="litespeed-warning"> ❌ ' . sprintf( esc_html__( 'Path must end with %s', 'litespeed-cache' ), '<code>/.htaccess</code>' ) . '</span>';
		}
	}

	/**
	 * Check TTL ranges and show tips.
	 *
	 * @since 3.0
	 *
	 * @param string   $id         Setting ID.
	 * @param int|bool $min        Minimum value (or false).
	 * @param int|bool $max        Maximum value (or false).
	 * @param bool     $allow_zero Whether zero is allowed.
	 * @return void
	 */
	protected function _validate_ttl( $id, $min = false, $max = false, $allow_zero = false ) {
		$val = $this->conf( $id, true );

		$tip = array();
		if ( $min && $val < $min && ( ! $allow_zero || 0 !== $val ) ) {
			$tip[] = esc_html__( 'Minimum value', 'litespeed-cache' ) . ': <code>' . $min . '</code>.';
		}
		if ( $max && $val > $max ) {
			$tip[] = esc_html__( 'Maximum value', 'litespeed-cache' ) . ': <code>' . $max . '</code>.';
		}

		echo '<br />';

		if ( $tip ) {
			echo '<span class="litespeed-warning"> ❌ ' . wp_kses_post( implode( ' ', $tip ) ) . '</span>';
		}

		$range = '';

		if ( $allow_zero ) {
			$range .= esc_html__( 'Zero, or', 'litespeed-cache' ) . ' ';
		}

		if ( $min && $max ) {
			$range .= $min . ' - ' . $max;
		} elseif ( $min ) {
			$range .= esc_html__( 'Larger than', 'litespeed-cache' ) . ' ' . $min;
		} elseif ( $max ) {
			$range .= esc_html__( 'Smaller than', 'litespeed-cache' ) . ' ' . $max;
		}

		echo esc_html__( 'Value range', 'litespeed-cache' ) . ': <code>' . esc_html( $range ) . '</code>';
	}

	/**
	 * Validate IPs in a list.
	 *
	 * @since 3.0
	 *
	 * @param string $id Setting ID.
	 * @return void
	 */
	protected function _validate_ip( $id ) {
		$val = $this->conf( $id, true );
		if ( ! $val ) {
			return;
		}

		if ( ! is_array( $val ) ) {
			$val = array( $val );
		}

		$tip = array();
		foreach ( $val as $v ) {
			if ( ! $v ) {
				continue;
			}

			if ( ! \WP_Http::is_ip_address( $v ) ) {
				$tip[] = esc_html__( 'Invalid IP', 'litespeed-cache' ) . ': <code>' . esc_html( $v ) . '</code>.';
			}
		}

		if ( $tip ) {
			echo '<br /><span class="litespeed-warning"> ❌ ' . wp_kses_post( implode( ' ', $tip ) ) . '</span>';
		}
	}

	/**
	 * Display API environment variable support.
	 *
	 * @since 1.8.3
	 * @access protected
	 *
	 * @param string ...$args Server variable names.
	 * @return void
	 */
	protected function _api_env_var( ...$args ) {
		echo '<span class="litespeed-success"> ' .
			esc_html__( 'API', 'litespeed-cache' ) . ': ' .
			sprintf(
				/* translators: %s: list of server variables in <code> tags */
				esc_html__( 'Server variable(s) %s available to override this setting.', 'litespeed-cache' ),
				'<code>' . implode( '</code>, <code>', array_map( 'esc_html', $args ) ) . '</code>'
			) .
			'</span>';

		Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/admin/#limiting-the-crawler' );
	}

	/**
	 * Display URI setting example.
	 *
	 * @since 2.6.1
	 * @access protected
	 * @return void
	 */
	protected function _uri_usage_example() {
		echo esc_html__( 'The URLs will be compared to the REQUEST_URI server variable.', 'litespeed-cache' );
		/* translators: 1: example URL, 2: pattern example */
		echo ' ' . sprintf( esc_html__( 'For example, for %1$s, %2$s can be used here.', 'litespeed-cache' ), '<code>/mypath/mypage?aa=bb</code>', '<code>mypage?aa=</code>' );
		echo '<br /><i>';
		/* translators: %s: caret symbol */
		printf( esc_html__( 'To match the beginning, add %s to the beginning of the item.', 'litespeed-cache' ), '<code>^</code>' );
		/* translators: %s: dollar symbol */
		echo ' ' . sprintf( esc_html__( 'To do an exact match, add %s to the end of the URL.', 'litespeed-cache' ), '<code>$</code>' );
		echo ' ' . esc_html__( 'One per line.', 'litespeed-cache' );
		echo '</i>';
	}

	/**
	 * Return pluralized strings.
	 *
	 * @since 2.0
	 *
	 * @param int    $num  Number.
	 * @param string $kind Kind of item (group|image).
	 * @return string
	 */
	public static function print_plural( $num, $kind = 'group' ) {
		if ( $num > 1 ) {
			switch ( $kind ) {
				case 'group':
					return sprintf( esc_html__( '%s groups', 'litespeed-cache' ), $num );

				case 'image':
					return sprintf( esc_html__( '%s images', 'litespeed-cache' ), $num );

				default:
					return $num;
			}
		}

		switch ( $kind ) {
			case 'group':
				return sprintf( esc_html__( '%s group', 'litespeed-cache' ), $num );

			case 'image':
				return sprintf( esc_html__( '%s image', 'litespeed-cache' ), $num );

			default:
				return $num;
		}
	}

	/**
	 * Return guidance HTML.
	 *
	 * @since 2.0
	 *
	 * @param string            $title        Title HTML.
	 * @param array<int,string> $steps        Steps list (HTML allowed).
	 * @param int|string        $current_step Current step number or 'done'.
	 * @return string HTML for guidance widget.
	 */
	public static function guidance( $title, $steps, $current_step ) {
		if ( 'done' === $current_step ) {
			$current_step = count( $steps ) + 1;
		}

		$percentage = ' (' . floor( ( ( $current_step - 1 ) * 100 ) / count( $steps ) ) . '%)';

		$html = '<div class="litespeed-guide"><h2>' . $title . $percentage . '</h2><ol>';
		foreach ( $steps as $k => $v ) {
			$step = $k + 1;
			if ( $current_step > $step ) {
				$html .= '<li class="litespeed-guide-done">';
			} else {
				$html .= '<li>';
			}
			$html .= $v . '</li>';
		}

		$html .= '</ol></div>';

		return $html;
	}

	/**
	 * Check whether has QC hide banner cookie.
	 *
	 * @since 7.1
	 *
	 * @return bool
	 */
	public static function has_qc_hide_banner() {
		return isset( $_COOKIE[ self::COOKIE_QC_HIDE_BANNER ] ) && ( time() - (int) $_COOKIE[ self::COOKIE_QC_HIDE_BANNER ] ) < 86400 * 90;
	}

	/**
	 * Set QC hide banner cookie.
	 *
	 * @since 7.1
	 * @return void
	 */
	public static function set_qc_hide_banner() {
		$expire = time() + 86400 * 365;
		self::debug( 'Set qc hide banner cookie' );
		setcookie( self::COOKIE_QC_HIDE_BANNER, time(), $expire, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Handle all request actions from main cls.
	 *
	 * @since 7.1
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_QC_HIDE_BANNER:
				self::set_qc_hide_banner();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
