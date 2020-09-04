<?php
/**
 * The core plugin class.
 *
 * @since      	1.0.0
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Core extends Instance {
	protected static $_instance;

	const NAME = 'LiteSpeed Cache';
	const PLUGIN_NAME = 'litespeed-cache';
	const PLUGIN_FILE = 'litespeed-cache/litespeed-cache.php';
	const VER = LSCWP_V;

	const ACTION_DISMISS = 'dismiss';
	const ACTION_PURGE_BY = 'PURGE_BY';
	const ACTION_PURGE_EMPTYCACHE = 'PURGE_EMPTYCACHE';
	const ACTION_QS_PURGE = 'PURGE';
	const ACTION_QS_PURGE_SINGLE = 'PURGESINGLE';
	const ACTION_QS_SHOW_HEADERS = 'SHOWHEADERS';
	const ACTION_QS_PURGE_ALL = 'purge_all';
	const ACTION_QS_PURGE_EMPTYCACHE = 'empty_all';
	const ACTION_QS_NOCACHE = 'NOCACHE';

	const HEADER_DEBUG = 'X-LiteSpeed-Debug';

	protected static $_debug_show_header = false;

	private $footer_comment = '';

	private $__cfg;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	protected function __construct() {
		$this->__cfg = Conf::get_instance();
		$this->__cfg->init();

		// Check if debug is on
		if ( Conf::val( Base::O_DEBUG ) ) {
			Debug2::init();
		}

		/**
		 * Load API hooks
		 * @since  3.0
		 */
		API::get_instance()->init();

		if ( defined( 'LITESPEED_ON' ) ) {
			// Load third party detection if lscache enabled.
			include_once LSCWP_DIR . 'thirdparty/entry.inc.php';
		}

		if ( Conf::val( Base::O_DEBUG_DISABLE_ALL ) ) {
			! defined( 'LITESPEED_DISABLE_ALL' ) && define( 'LITESPEED_DISABLE_ALL', true );
		}

		/**
		 * Register plugin activate/deactivate/uninstall hooks
		 * NOTE: this can't be moved under after_setup_theme, otherwise activation will be bypassed somehow
		 * @since  2.7.1	Disabled admin&CLI check to make frontend able to enable cache too
		 */
		// if( is_admin() || defined( 'LITESPEED_CLI' ) ) {
		$plugin_file = LSCWP_DIR . 'litespeed-cache.php';
		register_activation_hook( $plugin_file, array( __NAMESPACE__ . '\Activation', 'register_activation' ) );
		register_deactivation_hook( $plugin_file, array(__NAMESPACE__ . '\Activation', 'register_deactivation' ) );
		register_uninstall_hook( $plugin_file, __NAMESPACE__ . '\Activation::uninstall_litespeed_cache' );
		// }

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'after_setup_theme', array( $this, 'init' ) );

		// Check if there is a purge request in queue
		if ( $purge_queue = Purge::get_option( Purge::DB_QUEUE ) ) {
			@header( $purge_queue );
			Debug2::debug( '[Core] Purge Queue found&sent: ' . $purge_queue );
			Purge::delete_option( Purge::DB_QUEUE );
		}

		/**
		 * Hook internal REST
		 * @since  2.9.4
		 */
		REST::get_instance();

		/**
		 * Preload ESI functionality for ESI request uri recovery
		 * @since 1.8.1
		 */
		ESI::get_instance();
	}

	/**
	 * Plugin loaded hooks
	 * @since 3.0
	 */
	public function plugins_loaded() {
		load_plugin_textdomain( Core::PLUGIN_NAME, false, 'litespeed-cache/lang/' );
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
	public function init() {
		/**
		 * Added hook before init
		 * @since  1.6.6
		 * @since  2.6 	Added filter to all config values in Conf
		 */
		do_action( 'litespeed_init' );

		// in `after_setup_theme`, before `init` hook
		if ( ! defined( 'LITESPEED_BYPASS_AUTO_V' ) ) {
			Activation::auto_update();
		}

		if( is_admin() ) {
			Admin::get_instance();
		}

		if ( defined( 'LITESPEED_DISABLE_ALL' ) ) {
			Debug2::debug( '[Core] Bypassed due to debug disable all setting' );
			return;
		}

		do_action( 'litespeed_initing' );

		ob_start( array( $this, 'send_headers_force' ) );
		add_action( 'shutdown', array( $this, 'send_headers' ), 0 );
		add_action( 'wp_footer', array( $this, 'footer_hook' ) );

		/**
		 * Check if is non optm simulator
		 * @since  2.9
		 */
		if ( ! empty( $_GET[ Router::ACTION ] ) && $_GET[ Router::ACTION ] == 'before_optm' ) {
			Debug2::debug( '[Core] ⛑️ bypass_optm due to QS CTRL' );
			! defined( 'LITESPEED_BYPASS_OPTM' ) && define( 'LITESPEED_BYPASS_OPTM', true );
		}

		/**
		 * Register vary filter
		 * @since  1.6.2
		 */
		Control::get_instance();

		// 1. Init vary
		// 2. Init cacheable status
		Vary::get_instance();

		// Init Purge hooks
		Purge::get_instance()->init();

		Tag::get_instance();

		// Load hooks that may be related to users
		add_action( 'init', array( $this, 'after_user_init' ) );

		// Load 3rd party hooks
		add_action( 'wp_loaded', array( $this, 'load_thirdparty' ), 2 );
	}

	/**
	 * Run hooks after user init
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function after_user_init() {
		Router::get_instance()->is_role_simulation();

		if ( ! is_admin() && $result = $this->__cfg->in_optm_exc_roles() ) {
			Debug2::debug( '[Core] ⛑️ bypass_optm: hit Role Excludes setting: ' . $result );
			! defined( 'LITESPEED_BYPASS_OPTM' ) && define( 'LITESPEED_BYPASS_OPTM', true );
		}

		// Heartbeat control
		Tool::heartbeat();

		$__media = Media::get_instance();

		if ( ! defined( 'LITESPEED_BYPASS_OPTM' ) ) {
			// Check missing static files
			Router::serve_static();

			$__media->init();

			Placeholder::get_instance()->init();

			Optimize::get_instance()->init();

			// Hook cdn for attachements
			CDN::get_instance();

			// load cron tasks
			Task::get_instance()->init();
		}

		// load litespeed actions
		if ( $action = Router::get_action() ) {
			$this->proceed_action( $action );
		}

		// Load frontend GUI
		GUI::get_instance()->frontend_init();

	}

	/**
	 * Run frontend actions
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function proceed_action( $action ) {
		$msg = false;
		// handle actions
		switch ( $action ) {
			case self::ACTION_QS_PURGE:
				Purge::set_purge_related();
				break;

			case self::ACTION_QS_SHOW_HEADERS:
				self::$_debug_show_header = true;
				break;

			case self::ACTION_QS_PURGE_SINGLE:
				Purge::set_purge_single();
				break;

			case self::ACTION_QS_PURGE_ALL:
				Purge::purge_all();
				break;

			case self::ACTION_PURGE_EMPTYCACHE:
			case self::ACTION_QS_PURGE_EMPTYCACHE:
				define( 'LSWCP_EMPTYCACHE', true );// clear all sites caches
				Purge::purge_all();
				$msg = __( 'Notified LiteSpeed Web Server to purge everything.', 'litespeed-cache' );
				break;

			case self::ACTION_PURGE_BY:
				Purge::get_instance()->purge_list();
				$msg = __( 'Notified LiteSpeed Web Server to purge the list.', 'litespeed-cache' );
				break;

			case self::ACTION_DISMISS:// Even its from ajax, we don't need to register wp ajax callback function but directly use our action
				GUI::dismiss();
				break;

			default:
				$msg = Router::handler( $action );
				break;
		}
		if ( $msg && ! Router::is_ajax() ) {
			Admin_Display::add_notice( Admin_Display::NOTICE_GREEN, $msg );
			Admin::redirect();
			return;
		}

		if ( Router::is_ajax() ) {
			exit;
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
	public function load_thirdparty() {
		do_action( 'litespeed_load_thirdparty' );
	}

	/**
	 * Mark wp_footer called
	 *
	 * @since 1.3
	 * @access public
	 */
	public function footer_hook() {
		Debug2::debug( '[Core] Footer hook called' );
		if ( ! defined( 'LITESPEED_FOOTER_CALLED' ) ) {
			define( 'LITESPEED_FOOTER_CALLED', true );
		}
	}

	/**
	 * Tigger coment info display hook
	 *
	 * @since 1.3
	 * @access private
	 */
	private function _check_is_html( $buffer = null ) {
		if ( ! defined( 'LITESPEED_FOOTER_CALLED' ) ) {
			Debug2::debug2( '[Core] CHK html bypass: miss footer const' );
			return;
		}

		if ( defined( 'DOING_AJAX' ) ) {
			Debug2::debug2( '[Core] CHK html bypass: doing ajax' );
			return;
		}

		if ( defined( 'DOING_CRON' ) ) {
			Debug2::debug2( '[Core] CHK html bypass: doing cron' );
			return;
		}

		if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'GET' ) {
			Debug2::debug2( '[Core] CHK html bypass: not get method ' . $_SERVER[ 'REQUEST_METHOD' ] );
			return;
		}

		if ( $buffer === null ) {
			$buffer = ob_get_contents();
		}

		// double check to make sure it is a html file
		if ( strlen( $buffer ) > 300 ) {
			$buffer = substr( $buffer, 0, 300 );
		}
		if ( strstr( $buffer, '<!--' ) !== false ) {
			$buffer = preg_replace( '|<!--.*?-->|s', '', $buffer );
		}
		$buffer = trim( $buffer );

		$buffer = File::remove_zero_space( $buffer );

		$is_html = stripos( $buffer, '<html' ) === 0 || stripos( $buffer, '<!DOCTYPE' ) === 0;

		if ( ! $is_html ) {
			Debug2::debug( '[Core] Footer check failed: ' . ob_get_level() . '-' . substr( $buffer, 0, 100 ) );
			return;
		}

		Debug2::debug( '[Core] Footer check passed' );

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			define( 'LITESPEED_IS_HTML', true );
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
	public function send_headers_force( $buffer ) {
		$this->_check_is_html( $buffer );

		// Hook to modify buffer before
		$buffer = apply_filters('litespeed_buffer_before', $buffer);


		if ( ! defined( 'LITESPEED_BYPASS_OPTM' ) ) {
			// Image lazy load check
			$buffer = Media::finalize( $buffer );
		}

		/**
		 * Clean wrapper mainly for esi block
		 * NOTE: this needs to be before optimizer to avoid wrapper being removed
		 * @since 1.4
		 */
		$buffer = GUI::finalize( $buffer );

		if ( ! defined( 'LITESPEED_BYPASS_OPTM' ) ) {
			$buffer = Optimize::finalize( $buffer );

			$buffer = CDN::finalize( $buffer );
		}

		/**
		 * Replace ESI preserved list
		 * @since  3.3 Replace this in the end to avoid `Inline JS Defer` or other Page Optm features encoded ESI tags wrongly, which caused LSWS can't recognize ESI
		 */
		$buffer = ESI::finalize( $buffer );

		$this->send_headers( true );

		if ( $this->footer_comment ) {
			$buffer .= $this->footer_comment;
		}

		/**
		 * If ESI req is JSON, give the content JSON format
		 * @since  2.9.3
		 * @since  2.9.4 ESI req could be from internal REST call, so moved json_encode out of this cond
		 */
		if ( defined( 'LSCACHE_IS_ESI' ) ) {
			Debug2::debug( '[Core] ESI Start 👇' );
			if ( strlen( $buffer ) > 500 ) {
				Debug2::debug( trim( substr( $buffer, 0, 500 ) ) . '.....' );
			}
			else {
				Debug2::debug( $buffer );
			}
			Debug2::debug( '[Core] ESI End 👆' );
			Debug2::debug( $buffer );
		}

		if ( apply_filters( 'litespeed_is_json', false ) ) {
			if ( json_decode( $buffer, true ) == NULL ) {
				Debug2::debug( '[Core] Buffer converting to JSON' );
				$buffer = json_encode( $buffer );
				$buffer = trim( $buffer, '"' );
			}
			else {
				Debug2::debug( '[Core] JSON Buffer' );
			}
		}

		// Hook to modify buffer after
		$buffer = apply_filters('litespeed_buffer_after', $buffer);

		Debug2::debug( "End response\n--------------------------------------------------------------------------------\n" );

		return $buffer;
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
	public function send_headers( $is_forced = false ) {
		// Make sure header output only run once
		if ( ! defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			define( 'LITESPEED_DID_' . __FUNCTION__, true );
		}
		else {
			return;
		}

		$this->_check_is_html();

		// NOTE: cache ctrl output needs to be done first, as currently some varies are added in 3rd party hook `litespeed_api_control`.
		Control::finalize();

		$vary_header = Vary::finalize();

		// If is not cacheable but Admin QS is `purge` or `purgesingle`, `tag` still needs to be generated
		$tag_header = Tag::output();
		if ( Control::is_cacheable() && ! $tag_header ) {
			Control::set_nocache( 'empty tag header' );
		}

		// NOTE: `purge` output needs to be after `tag` output as Admin QS may need to send `tag` header
		$purge_header = Purge::output();

		// generate `control` header in the end in case control status is changed by other headers.
		$control_header = Control::output();

		// Init comment info
		$running_info_showing = defined( 'LITESPEED_IS_HTML' ) || defined( 'LSCACHE_IS_ESI' );
		if ( defined( 'LSCACHE_ESI_SILENCE' ) ) {
			$running_info_showing = false;
			Debug2::debug( '[Core] ESI silence' );
		}
		/**
		 * Silence comment for json req
		 * @since 2.9.3
		 */
		if ( REST::get_instance()->is_rest() || Router::is_ajax() ) {
			$running_info_showing = false;
			Debug2::debug( '[Core] Silence Comment due to REST/AJAX' );
		}

		$running_info_showing = apply_filters( 'litespeed_comment', $running_info_showing );

		if ( $running_info_showing ) {
			// Give one more break to avoid ff crash
			if ( ! defined( 'LSCACHE_IS_ESI' ) ) {
				$this->footer_comment .= "\n";
			}

			$cache_support = 'supported';
			if ( defined( 'LITESPEED_ON' ) ) {
				$cache_support = Control::is_cacheable() ? 'generated' : 'uncached';
			}

			$this->footer_comment .= sprintf(
				'<!-- %1$s %2$s by LiteSpeed Cache %4$s on %3$s -->',
				defined( 'LSCACHE_IS_ESI' ) ? 'Block' : 'Page',
				$cache_support,
				date( 'Y-m-d H:i:s', time() + LITESPEED_TIME_OFFSET ),
				self::VER
			);
		}

		// send Control header
		if ( defined( 'LITESPEED_ON' ) && $control_header ) {
			@header( $control_header );
			if ( defined( 'LSCWP_LOG' ) ) {
				Debug2::debug( '💰 ' . $control_header );
				if ( $running_info_showing ) {
					$this->footer_comment .= "\n<!-- " . $control_header . " -->";
				}
			}
		}
		// send PURGE header (Always send regardless of cache setting disabled/enabled)
		if ( defined( 'LITESPEED_ON' ) && $purge_header ) {
			@header( $purge_header );
			Debug2::log_purge( $purge_header );

			if ( defined( 'LSCWP_LOG' ) ) {
				Debug2::debug( '💰 ' . $purge_header );
				if ( $running_info_showing ) {
					$this->footer_comment .= "\n<!-- " . $purge_header . " -->";
				}
			}
		}
		// send Vary header
		if ( defined( 'LITESPEED_ON' ) && $vary_header ) {
			@header( $vary_header );
			if ( defined( 'LSCWP_LOG' ) ) {
				Debug2::debug( '💰 ' . $vary_header );
				if ( $running_info_showing ) {
					$this->footer_comment .= "\n<!-- " . $vary_header . " -->";
				}
			}
		}

		// Admin QS show header action
		if ( self::$_debug_show_header ) {
			$debug_header = self::HEADER_DEBUG . ': ';
			if ( $control_header ) {
				$debug_header .= $control_header . '; ';
			}
			if ( $purge_header ) {
				$debug_header .= $purge_header . '; ';
			}
			if ( $tag_header ) {
				$debug_header .= $tag_header . '; ';
			}
			if ( $vary_header ) {
				$debug_header .= $vary_header . '; ';
			}
			@header( $debug_header );
			Debug2::debug( $debug_header );
		}
		else {
			// Control header
			if ( defined( 'LITESPEED_ON' ) && Control::is_cacheable() && $tag_header ) {
				@header( $tag_header );
				if ( defined( 'LSCWP_LOG' ) ) {
					Debug2::debug( '💰 ' . $tag_header );
					if ( $running_info_showing ) {
						$this->footer_comment .= "\n<!-- " . $tag_header . " -->";
					}
				}
			}
		}

		// Object cache comment
		if ( $running_info_showing && defined( 'LSCWP_LOG' ) && defined( 'LSCWP_OBJECT_CACHE' ) && method_exists( 'WP_Object_Cache', 'debug' ) ) {
			$this->footer_comment .= "\n<!-- Object Cache " . \WP_Object_Cache::get_instance()->debug() . " -->";
		}

		if ( $is_forced ) {
			Debug2::debug( '--forced--' );
		}

	}

}
