<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      	1.0.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Core
{
	private static $_instance ;

	const NAME = 'LiteSpeed Cache' ;
	const PLUGIN_NAME = 'litespeed-cache' ;
	const PLUGIN_FILE = 'litespeed-cache/litespeed-cache.php' ;
	const PLUGIN_VERSION = '3.0' ;

	const PAGE_EDIT_HTACCESS = 'litespeed-edit-htaccess' ;

	const ACTION_DISMISS = 'dismiss' ;
	const ACTION_SAVE_HTACCESS = 'save-htaccess' ;
	const ACTION_SAVE_SETTINGS_NETWORK = 'save-settings-network' ;
	const ACTION_PURGE_BY = 'PURGE_BY' ;
	const ACTION_PURGE_EMPTYCACHE = 'PURGE_EMPTYCACHE' ;
	const ACTION_QS_PURGE = 'PURGE' ;
	const ACTION_QS_PURGE_SINGLE = 'PURGESINGLE' ;
	const ACTION_QS_SHOW_HEADERS = 'SHOWHEADERS' ;
	const ACTION_QS_PURGE_ALL = 'purge_all' ;
	const ACTION_QS_PURGE_EMPTYCACHE = 'empty_all' ;
	const ACTION_QS_NOCACHE = 'NOCACHE' ;
	const ACTION_CRAWLER_GENERATE_FILE = 'crawler-generate-file' ;
	const ACTION_CRAWLER_RESET_POS = 'crawler-reset-pos' ;
	const ACTION_CRAWLER_CRON_ENABLE = 'crawler-cron-enable' ;
	const ACTION_DO_CRAWL = 'do-crawl' ;
	const ACTION_BLACKLIST_SAVE = 'blacklist-save' ;
	const ACTION_CDN_CLOUDFLARE = 'cdn_cloudflare' ;
	const ACTION_CDN_QUIC = 'cdn_quic' ;
	const ACTION_CONF = 'conf' ;
	const ACTION_ACTIVATION = 'activate' ;
	const ACTION_UTIL = 'util' ;

	const ACTION_LOG = 'log' ;

	const ACTION_IMPORT = 'import' ;
	const ACTION_PURGE = 'purge' ;
	const ACTION_IMG_OPTM = 'img_optm' ;
	const ACTION_IAPI = 'iapi' ;
	const ACTION_CDN = 'cdn' ;
	const ACTION_REPORT = 'report' ;
	const ACTION_CSS = 'css' ;
	const ACTION_SAPI_PASSIVE_CALLBACK = 'sapi_passive_callback' ;
	const ACTION_SAPI_AGGRESSIVE_CALLBACK = 'sapi_aggressive_callback' ;

	const WHM_MSG = 'lscwp_whm_install' ;
	const WHM_MSG_VAL = 'whm_install' ;

	const HEADER_DEBUG = 'X-LiteSpeed-Debug' ;

	protected static $_debug_show_header = false ;

	private $footer_comment = '' ;

	private $__cfg ;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct()
	{
		$this->__cfg = Config::get_instance() ;
		$this->__cfg->init() ;

		// Check if debug is on
		if ( $this->__cfg->option( Conf::O_DEBUG ) ) {
			Log::init() ;
		}

		/**
		 * Load API hooks
		 *
		 * @since  3.0
		 */
		API::init() ;

		if ( defined( 'LITESPEED_ON' ) ) {
			// Load third party detection if lscache enabled.
			include_once LSCWP_DIR . 'thirdparty/entry.inc.php' ;
		}

		if ( self::config( Conf::O_DEBUG_DISABLE_ALL ) ) {
			! defined( 'LITESPEED_DISABLE_ALL' ) && define( 'LITESPEED_DISABLE_ALL', true ) ;
		}

		/**
		 * Register plugin activate/deactivate/uninstall hooks
		 * NOTE: this can't be moved under after_setup_theme, otherwise activation will be bypassed somehow
		 *
		 * @since  2.7.1	Disabled admin&CLI check to make frontend able to enable cache too
		 */
		// if( is_admin() || defined( 'LITESPEED_CLI' ) ) {
		$plugin_file = LSCWP_DIR . 'litespeed-cache.php' ;
		register_activation_hook( $plugin_file, array( __NAMESPACE__ . '\Activation', 'register_activation' ) ) ;
		register_deactivation_hook( $plugin_file, array(__NAMESPACE__ . '\Activation', 'register_deactivation' ) ) ;
		register_uninstall_hook( $plugin_file, __NAMESPACE__ . '\Activation::uninstall_litespeed_cache' ) ;
		// }

		add_action( 'after_setup_theme', array( $this, 'init' ) ) ;

		// Check if there is a purge request in queue
		if ( $purge_queue = get_option( Purge::PURGE_QUEUE ) ) {
			@header( $purge_queue ) ;
			Log::debug( '[Core] Purge Queue found&sent: ' . $purge_queue ) ;
			delete_option( Purge::PURGE_QUEUE ) ;
		}

		/**
		 * Hook internal REST
		 * @since  2.9.4
		 */
		REST::get_instance() ;

		/**
		 * Preload ESI functionality for ESI request uri recovery
		 * @since 1.8.1
		 */
		ESI::get_instance() ;
	}

	/**
	 * The plugin initializer.
	 *
	 * This function checks if the cache is enabled and ready to use, then determines what actions need to be set up based on the type of user and page accessed. Output is buffered if the cache is enabled.
	 *
	 * NOTE: WP user doesn't init yet
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init()
	{
		/**
		 * Added hook before init
		 * @since  1.6.6
		 * @since  2.6 	Added filter to all config values in Config
		 */
		do_action( 'litespeed_init' ) ;

		// in `after_setup_theme`, before `init` hook
		Activation::auto_update() ;

		if( is_admin() ) {
			Admin::get_instance() ;
		}

		if ( defined( 'LITESPEED_DISABLE_ALL' ) ) {
			Log::debug( '[Core] Bypassed due to debug disable all setting' ) ;
			return ;
		}

		do_action( 'litespeed_initing' ) ;

		ob_start( array( $this, 'send_headers_force' ) ) ;
		add_action( 'shutdown', array( $this, 'send_headers' ), 0 ) ;
		add_action( 'wp_footer', array( $this, 'footer_hook' ) ) ;

		/**
		 * Check if is non optm simulator
		 * @since  2.9
		 */
		if ( ! empty( $_GET[ Router::ACTION_KEY ] ) && $_GET[ Router::ACTION_KEY ] == 'before_optm' ) {
			! defined( 'LITESPEED_BYPASS_OPTM' ) && define( 'LITESPEED_BYPASS_OPTM', true ) ;
		}

		/**
		 * Register vary filter
		 * @since  1.6.2
		 */
		Control::get_instance() ;

		// 1. Init vary
		// 2. Init cacheable status
		Vary::get_instance() ;

		// Init Purge hooks
		Purge::get_instance() ;

		Tag::get_instance() ;

		// Load hooks that may be related to users
		add_action( 'init', array( $this, 'after_user_init' ) ) ;

		// Load 3rd party hooks
		add_action( 'wp_loaded', array( $this, 'load_thirdparty' ), 2 ) ;
	}

	/**
	 * Run hooks after user init
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function after_user_init()
	{
		Router::get_instance()->is_crawler_role_simulation() ;

		if ( $result = $this->__cfg->in_optm_exc_roles() ) {
			Log::debug( '[Core] ⛑️ bypass_optm: hit Role Excludes setting: ' . $result ) ;
			! defined( 'LITESPEED_BYPASS_OPTM' ) && define( 'LITESPEED_BYPASS_OPTM', true ) ;
		}

		// Heartbeat control
		Tool::heartbeat() ;

		if ( ! defined( 'LITESPEED_BYPASS_OPTM' ) ) {
			// Check missing static files
			Router::serve_static() ;

			Media::get_instance() ;

			Optimize::get_instance() ;

			// Hook cdn for attachements
			CDN::get_instance() ;

			// load cron tasks
			Task::get_instance() ;
		}

		// load litespeed actions
		if ( $action = Router::get_action() ) {
			$this->proceed_action( $action ) ;
		}

		// Load frontend GUI
		GUI::get_instance() ;

	}

	/**
	 * Run frontend actions
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function proceed_action( $action )
	{
		$msg = false ;
		// handle actions
		switch ( $action ) {
			case self::ACTION_QS_PURGE:
				Purge::set_purge_related() ;
				break;

			case self::ACTION_QS_SHOW_HEADERS:
				self::$_debug_show_header = true ;
				break;

			case self::ACTION_QS_PURGE_SINGLE:
				Purge::set_purge_single() ;
				break;

			case self::ACTION_CRAWLER_GENERATE_FILE:
				Crawler::get_instance()->generate_sitemap() ;
				Admin::redirect() ;
				break;

			case self::ACTION_CRAWLER_RESET_POS:
				Crawler::get_instance()->reset_pos() ;
				Admin::redirect() ;
				break;

			case self::ACTION_CRAWLER_CRON_ENABLE:
				Task::enable() ;
				break;

			// Handle the ajax request to proceed crawler manually by admin
			case self::ACTION_DO_CRAWL:
				Crawler::crawl_data( true ) ;
				break ;

			case self::ACTION_BLACKLIST_SAVE:
				Crawler::get_instance()->save_blacklist() ;
				$msg = __( 'Crawler blacklist is saved.', 'litespeed-cache' ) ;
				break ;

			case self::ACTION_QS_PURGE_ALL:
				Purge::purge_all() ;
				break;

			case self::ACTION_PURGE_EMPTYCACHE:
			case self::ACTION_QS_PURGE_EMPTYCACHE:
				define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
				Purge::purge_all() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge everything.', 'litespeed-cache' ) ;
				break;

			case self::ACTION_PURGE_BY:
				Purge::get_instance()->purge_list() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge the list.', 'litespeed-cache' ) ;
				break;

			case self::ACTION_DISMISS:// Even its from ajax, we don't need to register wp ajax callback function but directly use our action
				GUI::dismiss() ;
				break ;

			case Router::ACTION_DB:
				DB_Optm::handler() ;
				break ;

			case self::ACTION_SAPI_PASSIVE_CALLBACK:
				Admin_API::sapi_passive_callback() ;
				break ;

			case self::ACTION_SAPI_AGGRESSIVE_CALLBACK:
				Admin_API::sapi_aggressive_callback() ;
				break ;

			case Router::ACTION_PLACEHOLDER:
				Placeholder::handler() ;
				break ;

			case Router::ACTION_AVATAR:
				Avatar::handler() ;
				break ;

			case self::ACTION_IMG_OPTM:
				$msg = Img_Optm::handler() ;
				break ;

			case self::ACTION_PURGE:
				$msg = Purge::handler() ;
				break ;

			case self::ACTION_IAPI:
				$msg = Admin_API::handler() ;
				break ;

			case self::ACTION_LOG:
				$msg = Log::handler() ;
				break ;

			case self::ACTION_REPORT:
				$msg = Report::handler() ;
				break ;

			case self::ACTION_IMPORT:
				$msg = Import::handler() ;
				break ;

			case self::ACTION_CSS:
				$msg = CSS::handler() ;
				break ;

			case self::ACTION_CDN_CLOUDFLARE:
				$msg = CDN\Cloudflare::handler() ;
				break ;

			case self::ACTION_CDN_QUIC:
				$msg = CDN\Quic::handler() ;
				break ;

			case self::ACTION_CONF :
				$msg = Config::handler() ;
				break ;

			case self::ACTION_ACTIVATION :
				$msg = Activation::handler() ;
				break ;

			case self::ACTION_UTIL :
				$msg = Utility::handler() ;
				break ;

			default:
				break ;
		}
		if ( $msg && ! Router::is_ajax() ) {
			Admin_Display::add_notice( Admin_Display::NOTICE_GREEN, $msg ) ;
			Admin::redirect() ;
			return ;
		}

		if ( Router::is_ajax() ) {
			exit ;
		}
	}

	/**
	 * Callback used to call the detect third party action.
	 *
	 * The detect action is used by third party plugin integration classes to determine if they should add the rest of their hooks.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function load_thirdparty()
	{
		do_action( 'litespeed_api_load_thirdparty' ) ;
	}

	/**
	 * A shortcut to get the Config config value
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $opt_id An option ID if getting an option.
	 * @return the option value
	 */
	public static function config( $opt_id )
	{
		return Config::get_instance()->option( $opt_id ) ;
	}

	/**
	 * Mark wp_footer called
	 *
	 * @since 1.3
	 * @access public
	 */
	public function footer_hook()
	{
		Log::debug( '[Core] Footer hook called' ) ;
		if ( ! defined( 'LITESPEED_FOOTER_CALLED' ) ) {
			define( 'LITESPEED_FOOTER_CALLED', true ) ;
		}
	}

	/**
	 * Tigger coment info display hook
	 *
	 * @since 1.3
	 * @access private
	 */
	private function _check_is_html( $buffer = null )
	{
		if ( ! defined( 'LITESPEED_FOOTER_CALLED' ) ) {
			Log::debug2( '[Core] CHK html bypass: miss footer const' ) ;
			return ;
		}

		if ( defined( 'DOING_AJAX' ) ) {
			Log::debug2( '[Core] CHK html bypass: doing ajax' ) ;
			return ;
		}

		if ( defined( 'DOING_CRON' ) ) {
			Log::debug2( '[Core] CHK html bypass: doing cron' ) ;
			return ;
		}

		if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'GET' ) {
			Log::debug2( '[Core] CHK html bypass: not get method ' . $_SERVER[ 'REQUEST_METHOD' ] ) ;
			return ;
		}

		if ( $buffer === null ) {
			$buffer = ob_get_contents() ;
		}

		// double check to make sure it is a html file
		if ( strlen( $buffer ) > 300 ) {
			$buffer = substr( $buffer, 0, 300 ) ;
		}
		if ( strstr( $buffer, '<!--' ) !== false ) {
			$buffer = preg_replace( '|<!--.*?-->|s', '', $buffer ) ;
		}
		$buffer = trim( $buffer ) ;

		$buffer = File::remove_zero_space( $buffer ) ;

		$is_html = stripos( $buffer, '<html' ) === 0 || stripos( $buffer, '<!DOCTYPE' ) === 0 ;

		if ( ! $is_html ) {
			Log::debug( '[Core] Footer check failed: ' . ob_get_level() . '-' . substr( $buffer, 0, 100 ) ) ;
			return ;
		}

		Log::debug( '[Core] Footer check passed' ) ;

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			define( 'LITESPEED_IS_HTML', true ) ;
		}
	}

	/**
	 * For compatibility with those plugins have 'Bad' logic that forced all buffer output even it is NOT their buffer :(
	 *
	 * Usually this is called after send_headers() if following orignal WP process
	 *
	 * @since 1.1.5
	 * @access public
	 * @param  string $buffer
	 * @return string
	 */
	public function send_headers_force( $buffer )
	{
		$this->_check_is_html( $buffer ) ;

		// Replace ESI preserved list
		$buffer = ESI::finalize( $buffer ) ;

		if ( ! defined( 'LITESPEED_BYPASS_OPTM' ) ) {
			// Image lazy load check
			$buffer = Media::finalize( $buffer ) ;
		}

		/**
		 * Clean wrapper mainly for esi block
		 * NOTE: this needs to be before optimizer to avoid wrapper being removed
		 * @since 1.4
		 */
		$buffer = GUI::finalize( $buffer ) ;

		if ( ! defined( 'LITESPEED_BYPASS_OPTM' ) ) {
			$buffer = Optimize::finalize( $buffer ) ;

			$buffer = CDN::finalize( $buffer ) ;
		}

		$this->send_headers( true ) ;

		if ( $this->footer_comment ) {
			$buffer .= $this->footer_comment ;
		}

		/**
		 * If ESI req is JSON, give the content JSON format
		 * @since  2.9.3
		 * @since  2.9.4 ESI req could be from internal REST call, so moved json_encode out of this cond
		 */
		if ( defined( 'LSCACHE_IS_ESI' ) ) {
			Log::debug( '[Core] ESI Start 👇' ) ;
			if ( strlen( $buffer ) > 100 ) {
				Log::debug( trim( substr( $buffer, 0, 100 ) ) . '.....' ) ;
			}
			else {
				Log::debug( $buffer ) ;
			}
			Log::debug( '[Core] ESI End 👆' ) ;
		}

		if ( apply_filters( 'litespeed_is_json', false ) ) {
			if ( json_decode( $buffer, true ) == NULL ) {
				Log::debug( '[Core] Buffer converting to JSON' ) ;
				$buffer = json_encode( $buffer ) ;
				$buffer = trim( $buffer, '"' ) ;
			}
			else {
				Log::debug( '[Core] JSON Buffer' ) ;
			}
		}

		Log::debug( "End response\n--------------------------------------------------------------------------------\n" ) ;

		return $buffer ;
	}

	/**
	 * Sends the headers out at the end of processing the request.
	 *
	 * This will send out all LiteSpeed Cache related response headers needed for the post.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param boolean $is_forced If the header is sent following our normal finalizing logic
	 */
	public function send_headers( $is_forced = false )
	{
		// Make sure header output only run once
		if ( ! defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;
		}
		else {
			return ;
		}

		$this->_check_is_html() ;

		// NOTE: cache ctrl output needs to be done first, as currently some varies are added in 3rd party hook `litespeed_api_control`.
		Control::finalize() ;

		$vary_header = Vary::finalize() ;

		// If is not cacheable but Admin QS is `purge` or `purgesingle`, `tag` still needs to be generated
		$tag_header = Tag::output() ;
		if ( Control::is_cacheable() && ! $tag_header ) {
			Control::set_nocache( 'empty tag header' ) ;
		}

		// NOTE: `purge` output needs to be after `tag` output as Admin QS may need to send `tag` header
		$purge_header = Purge::output() ;

		// generate `control` header in the end in case control status is changed by other headers.
		$control_header = Control::output() ;

		// Init comment info
		$running_info_showing = defined( 'LITESPEED_IS_HTML' ) || defined( 'LSCACHE_IS_ESI' ) ;
		if ( defined( 'LSCACHE_ESI_SILENCE' ) ) {
			$running_info_showing = false ;
			Log::debug( '[Core] ESI silence' ) ;
		}
		/**
		 * Silence comment for json req
		 * @since 2.9.3
		 */
		if ( REST::get_instance()->is_rest() || Router::is_ajax() ) {
			$running_info_showing = false ;
			Log::debug( '[Core] Silence Comment due to REST/AJAX' ) ;
		}

		$running_info_showing = apply_filters( 'litespeed_comment', $running_info_showing ) ;

		if ( $running_info_showing ) {
			// Give one more break to avoid ff crash
			if ( ! defined( 'LSCACHE_IS_ESI' ) ) {
				$this->footer_comment .= "\n" ;
			}

			$cache_support = 'supported' ;
			if ( defined( 'LITESPEED_ON' ) ) {
				$cache_support = Control::is_cacheable() ? 'generated' : 'uncached' ;
			}

			$this->footer_comment .= sprintf(
				'<!-- %1$s %2$s by LiteSpeed Cache %4$s on %3$s -->',
				defined( 'LSCACHE_IS_ESI' ) ? 'Block' : 'Page',
				$cache_support,
				date( 'Y-m-d H:i:s', time() + LITESPEED_TIME_OFFSET ),
				self::PLUGIN_VERSION
			) ;
		}

		// send Control header
		if ( defined( 'LITESPEED_ON' ) && $control_header ) {
			@header( $control_header ) ;
			if ( defined( 'LSCWP_LOG' ) ) {
				Log::debug( $control_header ) ;
				if ( $running_info_showing ) {
					$this->footer_comment .= "\n<!-- " . $control_header . " -->" ;
				}
			}
		}
		// send PURGE header (Always send regardless of cache setting disabled/enabled)
		if ( defined( 'LITESPEED_ON' ) && $purge_header ) {
			@header( $purge_header ) ;
			Log::log_purge( $purge_header ) ;

			if ( defined( 'LSCWP_LOG' ) ) {
				Log::debug( $purge_header ) ;
				if ( $running_info_showing ) {
					$this->footer_comment .= "\n<!-- " . $purge_header . " -->" ;
				}
			}
		}
		// send Vary header
		if ( defined( 'LITESPEED_ON' ) && $vary_header ) {
			@header( $vary_header ) ;
			if ( defined( 'LSCWP_LOG' ) ) {
				Log::debug( $vary_header ) ;
				if ( $running_info_showing ) {
					$this->footer_comment .= "\n<!-- " . $vary_header . " -->" ;
				}
			}
		}

		// Admin QS show header action
		if ( self::$_debug_show_header ) {
			$debug_header = self::HEADER_DEBUG . ': ' ;
			if ( $control_header ) {
				$debug_header .= $control_header . '; ' ;
			}
			if ( $purge_header ) {
				$debug_header .= $purge_header . '; ' ;
			}
			if ( $tag_header ) {
				$debug_header .= $tag_header . '; ' ;
			}
			if ( $vary_header ) {
				$debug_header .= $vary_header . '; ' ;
			}
			@header( $debug_header ) ;
			Log::debug( $debug_header ) ;
		}
		else {
			// Control header
			if ( defined( 'LITESPEED_ON' ) && Control::is_cacheable() && $tag_header ) {
				@header( $tag_header ) ;
				if ( defined( 'LSCWP_LOG' ) ) {
					Log::debug( $tag_header ) ;
					if ( $running_info_showing ) {
						$this->footer_comment .= "\n<!-- " . $tag_header . " -->" ;
					}
				}
			}
		}

		// Object cache comment
		if ( $running_info_showing && defined( 'LSCWP_LOG' ) && defined( 'LSCWP_OBJECT_CACHE' ) && method_exists( 'WP_Object_Cache', 'debug' ) ) {
			$this->footer_comment .= "\n<!-- Object Cache " . \WP_Object_Cache::get_instance()->debug() . " -->" ;
		}

		if ( $is_forced ) {
			Log::debug( '--forced--' ) ;
		}

	}

	/**
	 * Deprecated calls for backward compatibility to v1.1.2.2
	 */
	public function purge_post( $id )
	{
		litespeed_purge_single_post( $id ) ;
	}

	/**
	 * Deprecated calls for backward compatibility to v1.1.2.2
	 */
	public function purge_all()
	{
		API::purge_all() ;
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
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}
