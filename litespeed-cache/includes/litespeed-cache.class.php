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
 * @since      1.0.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache
{
	private static $_instance ;

	const PLUGIN_NAME = 'litespeed-cache' ;
	const PLUGIN_VERSION = '1.1.6' ;

	const PAGE_EDIT_HTACCESS = 'lscache-edit-htaccess' ;

	const NONCE_NAME = 'LSCWP_NONCE' ;
	const ACTION_KEY = 'LSCWP_CTRL' ;
	const ACTION_DISMISS_WHM = 'dismiss-whm' ;
	const ACTION_DISMISS_EXPIRESDEFAULT = 'dismiss-ExpiresDefault' ;
	const ACTION_SAVE_HTACCESS = 'save-htaccess' ;
	const ACTION_SAVE_SETTINGS = 'save-settings' ;
	const ACTION_SAVE_SETTINGS_NETWORK = 'save-settings-network' ;
	const ACTION_PURGE_ERRORS = 'PURGE_ERRORS' ;
	const ACTION_PURGE_PAGES = 'PURGE_PAGES' ;
	const ACTION_PURGE_BY = 'PURGE_BY' ;
	const ACTION_PURGE_FRONT = 'PURGE_FRONT' ;
	const ACTION_PURGE_ALL = 'PURGE_ALL' ;
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

	const WHM_TRANSIENT = 'lscwp_whm_install' ;
	const WHM_TRANSIENT_VAL = 'whm_install' ;

	const HEADER_DEBUG = 'X-LiteSpeed-Debug' ;

	protected static $_debug_show_header = false ;

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
		// Check if debug is on
		if ( self::config(LiteSpeed_Cache_Config::OPID_ENABLED) ) {
			$should_debug = intval(self::config(LiteSpeed_Cache_Config::OPID_DEBUG)) ;
			if ( $should_debug == LiteSpeed_Cache_Config::VAL_ON || ($should_debug == LiteSpeed_Cache_Config::VAL_NOTSET && LiteSpeed_Cache_Router::is_admin_ip()) ) {
				LiteSpeed_Cache_Log::set_enabled() ;
			}

			// Load third party detection if lscache enabled.
			include_once LSWCP_DIR . 'thirdparty/lscwp-registry-3rd.php' ;
		}

		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_HEARTBEAT ) ) {
			add_action( 'init', 'LiteSpeed_Cache_Log::disable_heartbeat', 1 ) ;
		}

		// Register plugin activate/deactivate/uninstall hooks
		// NOTE: this can't be moved under after_setup_theme, otherwise activation will be bypassed somehow
		if( is_admin() || LiteSpeed_Cache_Router::is_cli() ) {
			$plugin_file = LSWCP_DIR . 'litespeed-cache.php' ;
			register_activation_hook($plugin_file, array('LiteSpeed_Cache_Activation', 'register_activation' )) ;
			register_deactivation_hook($plugin_file, array('LiteSpeed_Cache_Activation', 'register_deactivation' )) ;
			register_uninstall_hook($plugin_file, 'LiteSpeed_Cache_Activation::uninstall_litespeed_cache') ;
		}

		add_action( 'after_setup_theme', array( $this, 'init' ) ) ;

		// Check if there is a purge request in queue
		if ( $purge_queue = get_option( LiteSpeed_Cache_Purge::PURGE_QUEUE ) ) {
			@header( $purge_queue ) ;
			LiteSpeed_Cache_Log::debug( 'Purge Queue found&sent: ' . $purge_queue ) ;
			delete_option( LiteSpeed_Cache_Purge::PURGE_QUEUE ) ;
		}
	}

	/**
	 * The plugin initializer.
	 *
	 * This function checks if the cache is enabled and ready to use, then
	 * determines what actions need to be set up based on the type of user
	 * and page accessed. Output is buffered if the cache is enabled.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init()
	{
		if( is_admin() ) {
			LiteSpeed_Cache_Admin::get_instance() ;
		}

		if ( ! LiteSpeed_Cache_Router::cache_enabled() || ! defined( 'LSCACHE_ADV_CACHE' ) || ! LSCACHE_ADV_CACHE ) {
			return ;
		}

		define( 'LITESPEED_CACHE_ENABLED', true ) ;
		ob_start( array( $this, 'send_headers_force' ) ) ;
		add_action( 'shutdown', array( $this, 'send_headers' ), 0 ) ;
		add_action( 'wp_footer', 'LiteSpeed_Cache::litespeed_comment_info' ) ;

		// 1. Init vary
		// 2. Init cacheable status
		LiteSpeed_Cache_Vary::get_instance() ;

		// Load public hooks
		$this->load_public_actions() ;

		// load cron task for crawler
		if ( self::config( LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ) && LiteSpeed_Cache_Router::can_crawl() ) {
			// keep cron intval filter
			LiteSpeed_Cache_Task::schedule_filter() ;

			// cron hook
			add_action( LiteSpeed_Cache_Task::CRON_ACTION_HOOK, 'LiteSpeed_Cache_Crawler::crawl_data' ) ;
		}

		// Load 3rd party hooks
		add_action( 'wp_loaded', array( $this, 'load_thirdparty' ), 2 ) ;

		// load litespeed actions
		if ( $action = LiteSpeed_Cache_Router::get_action() ) {
			$this->proceed_action( $action ) ;
		}
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
			case LiteSpeed_Cache::ACTION_QS_PURGE:
				LiteSpeed_Cache_Purge::set_purge_related() ;
				break;

			case self::ACTION_QS_SHOW_HEADERS:
				self::$_debug_show_header = true ;
				break;

			case LiteSpeed_Cache::ACTION_QS_PURGE_SINGLE:
				LiteSpeed_Cache_Purge::set_purge_single() ;
				break;

			case LiteSpeed_Cache::ACTION_CRAWLER_GENERATE_FILE:
				LiteSpeed_Cache_Crawler::get_instance()->generate_sitemap() ;
				LiteSpeed_Cache_Admin::redirect() ;
				break;

			case LiteSpeed_Cache::ACTION_CRAWLER_RESET_POS:
				LiteSpeed_Cache_Crawler::get_instance()->reset_pos() ;
				LiteSpeed_Cache_Admin::redirect() ;
				break;

			case LiteSpeed_Cache::ACTION_CRAWLER_CRON_ENABLE:
				LiteSpeed_Cache_Task::enable() ;
				break;

			// Handle the ajax request to proceed crawler manually by admin
			case LiteSpeed_Cache::ACTION_DO_CRAWL:
				LiteSpeed_Cache_Crawler::crawl_data( true ) ;
				break ;

			case LiteSpeed_Cache::ACTION_BLACKLIST_SAVE:
				LiteSpeed_Cache_Crawler::get_instance()->save_blacklist() ;
				$msg = __( 'Crawler blacklist is saved.', 'litespeed-cache' ) ;
				break ;

			case LiteSpeed_Cache::ACTION_PURGE_FRONT:
				LiteSpeed_Cache_Purge::purge_front() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge the front page.', 'litespeed-cache' ) ;
				break ;

			case LiteSpeed_Cache::ACTION_PURGE_PAGES:
				LiteSpeed_Cache_Purge::purge_pages() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge pages.', 'litespeed-cache' ) ;
				break ;

			case LiteSpeed_Cache::ACTION_PURGE_ERRORS:
				LiteSpeed_Cache_Purge::purge_errors() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge error pages.', 'litespeed-cache' ) ;
				break ;

			case LiteSpeed_Cache::ACTION_PURGE_ALL:
			case LiteSpeed_Cache::ACTION_QS_PURGE_ALL:
				LiteSpeed_Cache_Purge::purge_all() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge all caches.', 'litespeed-cache' ) ;
				break;

			case LiteSpeed_Cache::ACTION_PURGE_EMPTYCACHE:
			case LiteSpeed_Cache::ACTION_QS_PURGE_EMPTYCACHE:
				define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
				LiteSpeed_Cache_Purge::purge_all() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge everything.', 'litespeed-cache' ) ;
				break;

			case LiteSpeed_Cache::ACTION_PURGE_BY:
				LiteSpeed_Cache_Purge::get_instance()->purge_list() ;
				$msg = __( 'Notified LiteSpeed Web Server to purge the list.', 'litespeed-cache' ) ;
				break;

			case LiteSpeed_Cache::ACTION_DISMISS_WHM:// Even its from ajax, we don't need to register wp ajax callback function but directly use our action
				LiteSpeed_Cache_Activation::dismiss_whm() ;
				break ;

			case LiteSpeed_Cache::ACTION_DISMISS_EXPIRESDEFAULT:
				update_option( LiteSpeed_Cache_Admin_Display::DISMISS_MSG, LiteSpeed_Cache_Admin_Display::RULECONFLICT_DISMISSED ) ;
				break ;

			default:
				break ;
		}
		if ( $msg && ! LiteSpeed_Cache_Router::is_ajax() ) {
			LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg ) ;
			LiteSpeed_Cache_Admin::redirect() ;
			return ;
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
		do_action( 'litespeed_cache_api_load_thirdparty' ) ;
	}

	/**
	 * Register all of the hooks related to the all users
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_public_actions()
	{
		//register purge actions
		$purge_post_events = array(
			'edit_post',
			'save_post',
			'deleted_post',
			'trashed_post',
			'delete_attachment',
			// 'clean_post_cache', // This will disable wc's not purge product when stock status not change setting
		) ;
		foreach ( $purge_post_events as $event ) {
			// this will purge all related tags
			add_action( $event, 'LiteSpeed_Cache_Purge::purge_post', 10, 2 ) ;
		}

		// The ESI functionality is an enterprise feature.
		// Removing the openlitespeed check will simply break the page.
		//todo: make a constant for esiEnable included cfg esi eanbled
		if ( LSWCP_ESI_SUPPORT ) {
			if ( ! LiteSpeed_Cache_Router::is_ajax() && self::config( LiteSpeed_Cache_Config::OPID_ESI_ENABLE ) ) {
				add_action( 'template_include', 'LiteSpeed_Cache_ESI::esi_template', 100 ) ;
				add_action( 'load-widgets.php', 'LiteSpeed_Cache_Purge::purge_widget' ) ;
				add_action( 'wp_update_comment_count', 'LiteSpeed_Cache_Purge::purge_comment_widget' ) ;
			}
		}
		add_action( 'wp_update_comment_count', 'LiteSpeed_Cache_Purge::purge_feeds' ) ;

		// register recent posts widget tag before theme renders it to make it work
		add_filter( 'widget_posts_args', 'LiteSpeed_Cache_Tag::add_widget_recent_posts' ) ;

		// 301 redirect hook
		add_filter( 'wp_redirect', 'LiteSpeed_Cache_Control::check_redirect', 10, 2 ) ;
	}

	/**
	 * A shortcut to get the LiteSpeed_Cache_Config config value
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $opt_id An option ID if getting an option.
	 * @return the option value
	 */
	public static function config( $opt_id )
	{
		return LiteSpeed_Cache_Config::get_instance()->get_option( $opt_id ) ;
	}

	/**
	 * Tigger coment info display for wp_footer hook
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function litespeed_comment_info()
	{
		// double check to make sure it is a html file
		$buffer = ob_get_contents() ;
		if ( strlen( $buffer ) > 300 ) {
			$buffer = substr( $buffer, 0, 300 ) ;
		}
		if ( strstr( $buffer, '<!--' ) !== false ) {
			$buffer = preg_replace( '|<!--.*?-->|s', '', $buffer ) ;
		}
		$is_html = stripos( $buffer, '<html' ) === 0 || stripos( $buffer, '<!DOCTYPE' ) === 0 ;
		if ( defined( 'DOING_AJAX' ) ) {
			return ;
		}
		if ( defined( 'DOING_CRON' ) ) {
			return ;
		}
		if ( ! $is_html ) {
			return ;
		}

		if ( ! defined( 'LITESPEED_COMMENT_INFO' ) ) {
			define( 'LITESPEED_COMMENT_INFO', true ) ;
		}
	}

	/**
	 * For compatibility with those plugins have 'Bad' logic that forced all buffer output even it is NOT their buffer :(
	 *
	 * @since 1.1.5
	 * @access public
	 * @param  string $buffer
	 * @return string
	 */
	public function send_headers_force( $buffer )
	{
		$buffer .= $this->send_headers( true ) ;
		return $buffer ;
	}

	/**
	 * Sends the headers out at the end of processing the request.
	 *
	 * This will send out all LiteSpeed Cache related response headers
	 * needed for the post.
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

		// NOTE: cache ctrl output needs to be done first, as currently some varies are added in 3rd party hook `litespeed_cache_api_control`.
		LiteSpeed_Cache_Control::finalize() ;

		$vary_header = LiteSpeed_Cache_Vary::output() ;

		// If is not cacheable but Admin QS is `purge` or `purgesingle`, `tag` still needs to be generated
		$tag_header = LiteSpeed_Cache_Tag::output() ;
		if ( LiteSpeed_Cache_Control::is_cacheable() && ! $tag_header ) {
			LiteSpeed_Cache_Control::set_nocache( 'empty tag header' ) ;
		}

		// NOTE: `purge` output needs to be after `tag` output as Admin QS may need to send `tag` header
		$purge_header = LiteSpeed_Cache_Purge::output() ;

		// generate `control` header in the end in case control status is changed by other headers.
		$control_header = LiteSpeed_Cache_Control::output() ;

		// Init comment info
		$running_info_showing = defined( 'LITESPEED_COMMENT_INFO' ) || defined( 'LSCACHE_IS_ESI' ) ;
		$comment = '' ;
		if ( $running_info_showing ) {
			if ( LiteSpeed_Cache_Control::is_cacheable() ) {
				$comment .= '<!-- ' . ( defined( 'LSCACHE_IS_ESI' ) ? 'Block' : 'Page' ) . ' generated by LiteSpeed Cache on '.date('Y-m-d H:i:s').' -->' ;
			}
			else {
				$comment .= '<!-- LiteSpeed Cache on '.date('Y-m-d H:i:s').' -->' ;
			}
		}

		// send Control header
		if ( $control_header ) {
			@header( $control_header ) ;
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push( $control_header ) ;
				if ( $running_info_showing ) {
					$comment .= "\n<!-- " . $control_header . " -->" ;
				}
			}
		}
		// send PURGE header
		if ( $purge_header ) {
			@header( $purge_header ) ;
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push( $purge_header ) ;
				if ( $running_info_showing ) {
					$comment .= "\n<!-- " . $purge_header . " -->" ;
				}
			}
		}
		// send Vary header
		if ( $vary_header ) {
			@header( $vary_header ) ;
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push( $vary_header ) ;
				if ( $running_info_showing ) {
					$comment .= "\n<!-- " . $vary_header . " -->" ;
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
			LiteSpeed_Cache_Log::debug( $debug_header ) ;
		}
		else {
			// Control header
			if ( LiteSpeed_Cache_Control::is_cacheable() && $tag_header ) {
				@header( $tag_header ) ;
				if ( LiteSpeed_Cache_Log::get_enabled() ) {
					LiteSpeed_Cache_Log::push( $tag_header ) ;
					if ( $running_info_showing ) {
						$comment .= "\n<!-- " . $tag_header . " -->" ;
					}
				}
			}
		}

		LiteSpeed_Cache_Log::debug(
			'End response' . ( $is_forced ? '(forced)' : '' ) . ".\n--------------------------------------------------------------------------------\n"
		) ;

		if ( $comment ) {
			if ( $is_forced ) {
				return $comment ;
			}
			else {
				echo $comment ;
			}
		}
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
		$cls = get_called_class() ;
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new $cls() ;
		}

		return self::$_instance ;
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
		LiteSpeed_Cache_API::purge_all() ;
	}

}
