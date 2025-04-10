<?php

/**
 * The core plugin class.
 *
 * Note: Core doesn't allow $this->cls( 'Core' )
 *
 * @since      	1.0.0
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Core extends Root
{
	const NAME = 'LiteSpeed Cache';
	const PLUGIN_NAME = 'litespeed-cache';
	const PLUGIN_FILE = 'litespeed-cache/litespeed-cache.php';
	const VER = LSCWP_V;

	const ACTION_DISMISS = 'dismiss';
	const ACTION_PURGE_BY = 'PURGE_BY';
	const ACTION_PURGE_EMPTYCACHE = 'PURGE_EMPTYCACHE';
	const ACTION_QS_PURGE = 'PURGE';
	const ACTION_QS_PURGE_SINGLE = 'PURGESINGLE'; // This will be same as `ACTION_QS_PURGE` (purge single url only)
	const ACTION_QS_SHOW_HEADERS = 'SHOWHEADERS';
	const ACTION_QS_PURGE_ALL = 'purge_all';
	const ACTION_QS_PURGE_EMPTYCACHE = 'empty_all';
	const ACTION_QS_NOCACHE = 'NOCACHE';

	const HEADER_DEBUG = 'X-LiteSpeed-Debug';

	protected static $_debug_show_header = false;

	private $_footer_comment = '';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		!defined('LSCWP_TS_0') && define('LSCWP_TS_0', microtime(true));
		$this->cls('Conf')->init();

		/**
		 * Load API hooks
		 * @since  3.0
		 */
		$this->cls('API')->init();

		if (defined('LITESPEED_ON')) {
			// Load third party detection if lscache enabled.
			include_once LSCWP_DIR . 'thirdparty/entry.inc.php';
		}

		if ($this->conf(Base::O_DEBUG_DISABLE_ALL)) {
			!defined('LITESPEED_DISABLE_ALL') && define('LITESPEED_DISABLE_ALL', true);
		}

		/**
		 * Register plugin activate/deactivate/uninstall hooks
		 * NOTE: this can't be moved under after_setup_theme, otherwise activation will be bypassed somehow
		 * @since  2.7.1	Disabled admin&CLI check to make frontend able to enable cache too
		 */
		// if( is_admin() || defined( 'LITESPEED_CLI' ) ) {
		$plugin_file = LSCWP_DIR . 'litespeed-cache.php';
		register_activation_hook($plugin_file, array(__NAMESPACE__ . '\Activation', 'register_activation'));
		register_deactivation_hook($plugin_file, array(__NAMESPACE__ . '\Activation', 'register_deactivation'));
		register_uninstall_hook($plugin_file, __NAMESPACE__ . '\Activation::uninstall_litespeed_cache');
		// }

		if (defined('LITESPEED_ON')) {
			// register purge_all actions
			$purge_all_events = $this->conf(Base::O_PURGE_HOOK_ALL);

			// purge all on upgrade
			if ($this->conf(Base::O_PURGE_ON_UPGRADE)) {
				$purge_all_events[] = 'automatic_updates_complete';
				$purge_all_events[] = 'upgrader_process_complete';
				$purge_all_events[] = 'admin_action_do-plugin-upgrade';
			}
			foreach ($purge_all_events as $event) {
				// Don't allow hook to update_option bcos purge_all will cause infinite loop of update_option
				if (in_array($event, array('update_option'))) {
					continue;
				}
				add_action($event, __NAMESPACE__ . '\Purge::purge_all');
			}
			// add_filter( 'upgrader_pre_download', 'Purge::filter_with_purge_all' );

			// Add headers to site health check for full page cache
			// @since 5.4
			add_filter('site_status_page_cache_supported_cache_headers', function ($cache_headers) {
				$is_cache_hit = function ($header_value) {
					return false !== strpos(strtolower($header_value), 'hit');
				};
				$cache_headers['x-litespeed-cache'] = $is_cache_hit;
				$cache_headers['x-lsadc-cache'] = $is_cache_hit;
				$cache_headers['x-qc-cache'] = $is_cache_hit;
				return $cache_headers;
			});
		}

		add_action('after_setup_theme', array($this, 'init'));

		// Check if there is a purge request in queue
		if (!defined('LITESPEED_CLI')) {
			$purge_queue = Purge::get_option(Purge::DB_QUEUE);
			if ($purge_queue && $purge_queue != -1) {
				$this->_http_header($purge_queue);
				Debug2::debug('[Core] Purge Queue found&sent: ' . $purge_queue);
			}
			if ($purge_queue != -1) {
				Purge::update_option(Purge::DB_QUEUE, -1); // Use 0 to bypass purge while still enable db update as WP's update_option will check value===false to bypass update
			}

			$purge_queue = Purge::get_option(Purge::DB_QUEUE2);
			if ($purge_queue && $purge_queue != -1) {
				$this->_http_header($purge_queue);
				Debug2::debug('[Core] Purge2 Queue found&sent: ' . $purge_queue);
			}
			if ($purge_queue != -1) {
				Purge::update_option(Purge::DB_QUEUE2, -1);
			}
		}

		/**
		 * Hook internal REST
		 * @since  2.9.4
		 */
		$this->cls('REST');

		/**
		 * Hook wpnonce function
		 *
		 * Note: ESI nonce won't be available until hook after_setup_theme ESI init due to Guest Mode concern
		 * @since v4.1
		 */
		if ($this->cls('Router')->esi_enabled() && !function_exists('wp_create_nonce')) {
			Debug2::debug('[ESI] Overwrite wp_create_nonce()');
			litespeed_define_nonce_func();
		}
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
		 * 3rd party preload hooks will be fired here too (e.g. Divi disable all in edit mode)
		 * @since  1.6.6
		 * @since  2.6 	Added filter to all config values in Conf
		 */
		do_action('litespeed_init');
		add_action('wp_ajax_async_litespeed', 'LiteSpeed\Task::async_litespeed_handler');
		add_action('wp_ajax_nopriv_async_litespeed', 'LiteSpeed\Task::async_litespeed_handler');

		// in `after_setup_theme`, before `init` hook
		$this->cls('Activation')->auto_update();

		if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
			$this->cls('Admin');
		}

		if (defined('LITESPEED_DISABLE_ALL') && LITESPEED_DISABLE_ALL) {
			Debug2::debug('[Core] Bypassed due to debug disable all setting');
			return;
		}

		do_action('litespeed_initing');

		ob_start(array($this, 'send_headers_force'));
		add_action('shutdown', array($this, 'send_headers'), 0);
		add_action('wp_footer', array($this, 'footer_hook'));

		/**
		 * Check if is non optm simulator
		 * @since  2.9
		 */
		if (!empty($_GET[Router::ACTION]) && $_GET[Router::ACTION] == 'before_optm' && !apply_filters('litespeed_qs_forbidden', false)) {
			Debug2::debug('[Core] ⛑️ bypass_optm due to QS CTRL');
			!defined('LITESPEED_NO_OPTM') && define('LITESPEED_NO_OPTM', true);
		}

		/**
		 * Register vary filter
		 * @since  1.6.2
		 */
		$this->cls('Control')->init();

		// 1. Init vary
		// 2. Init cacheable status
		// $this->cls('Vary')->init();

		// Init Purge hooks
		$this->cls('Purge')->init();

		$this->cls('Tag')->init();

		// Load hooks that may be related to users
		add_action('init', array($this, 'after_user_init'), 5);

		// Load 3rd party hooks
		add_action('wp_loaded', array($this, 'load_thirdparty'), 2);

		// test: Simulate a purge all
		// if (defined( 'LITESPEED_CLI' )) Purge::add('test'.date('Ymd.His'));
	}

	/**
	 * Run hooks after user init
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function after_user_init()
	{
		$this->cls('Router')->is_role_simulation();

		// Detect if is Guest mode or not also
		$this->cls('Vary')->after_user_init();

		/**
		 * Preload ESI functionality for ESI request uri recovery
		 * @since 1.8.1
		 * @since  4.0 ESI init needs to be after Guest mode detection to bypass ESI if is under Guest mode
		 */
		$this->cls('ESI')->init();

		if (!is_admin() && !defined('LITESPEED_GUEST_OPTM') && ($result = $this->cls('Conf')->in_optm_exc_roles())) {
			Debug2::debug('[Core] ⛑️ bypass_optm: hit Role Excludes setting: ' . $result);
			!defined('LITESPEED_NO_OPTM') && define('LITESPEED_NO_OPTM', true);
		}

		// Heartbeat control
		$this->cls('Tool')->heartbeat();

		/**
		 * Backward compatibility for v4.2- @Ruikai
		 * TODO: Will change to hook in future versions to make it revertable
		 */
		if (defined('LITESPEED_BYPASS_OPTM') && !defined('LITESPEED_NO_OPTM')) {
			define('LITESPEED_NO_OPTM', LITESPEED_BYPASS_OPTM);
		}

		if (!defined('LITESPEED_NO_OPTM') || !LITESPEED_NO_OPTM) {
			// Check missing static files
			$this->cls('Router')->serve_static();

			$this->cls('Media')->init();

			$this->cls('Placeholder')->init();

			$this->cls('Router')->can_optm() && $this->cls('Optimize')->init();

			$this->cls('Localization')->init();

			// Hook cdn for attachments
			$this->cls('CDN')->init();

			// load cron tasks
			$this->cls('Task')->init();
		}

		// load litespeed actions
		if ($action = Router::get_action()) {
			$this->proceed_action($action);
		}

		// Load frontend GUI
		if (!is_admin()) {
			$this->cls('GUI')->init();
		}
	}

	/**
	 * Run frontend actions
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function proceed_action($action)
	{
		$msg = false;
		// handle actions
		switch ($action) {
			case self::ACTION_QS_SHOW_HEADERS:
				self::$_debug_show_header = true;
				break;

			case self::ACTION_QS_PURGE:
			case self::ACTION_QS_PURGE_SINGLE:
				Purge::set_purge_single();
				break;

			case self::ACTION_QS_PURGE_ALL:
				Purge::purge_all();
				break;

			case self::ACTION_PURGE_EMPTYCACHE:
			case self::ACTION_QS_PURGE_EMPTYCACHE:
				define('LSWCP_EMPTYCACHE', true); // clear all sites caches
				Purge::purge_all();
				$msg = __('Notified LiteSpeed Web Server to purge everything.', 'litespeed-cache');
				break;

			case self::ACTION_PURGE_BY:
				$this->cls('Purge')->purge_list();
				$msg = __('Notified LiteSpeed Web Server to purge the list.', 'litespeed-cache');
				break;

			case self::ACTION_DISMISS: // Even its from ajax, we don't need to register wp ajax callback function but directly use our action
				GUI::dismiss();
				break;

			default:
				$msg = $this->cls('Router')->handler($action);
				break;
		}
		if ($msg && !Router::is_ajax()) {
			Admin_Display::add_notice(Admin_Display::NOTICE_GREEN, $msg);
			Admin::redirect();
			return;
		}

		if (Router::is_ajax()) {
			exit();
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
		do_action('litespeed_load_thirdparty');
	}

	/**
	 * Mark wp_footer called
	 *
	 * @since 1.3
	 * @access public
	 */
	public function footer_hook()
	{
		Debug2::debug('[Core] Footer hook called');
		if (!defined('LITESPEED_FOOTER_CALLED')) {
			define('LITESPEED_FOOTER_CALLED', true);
		}
	}

	/**
	 * Trigger comment info display hook
	 *
	 * @since 1.3
	 * @access private
	 */
	private function _check_is_html($buffer = null)
	{
		if (!defined('LITESPEED_FOOTER_CALLED')) {
			Debug2::debug2('[Core] CHK html bypass: miss footer const');
			return;
		}

		if (defined('DOING_AJAX')) {
			Debug2::debug2('[Core] CHK html bypass: doing ajax');
			return;
		}

		if (defined('DOING_CRON')) {
			Debug2::debug2('[Core] CHK html bypass: doing cron');
			return;
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
			Debug2::debug2('[Core] CHK html bypass: not get method ' . $_SERVER['REQUEST_METHOD']);
			return;
		}

		if ($buffer === null) {
			$buffer = ob_get_contents();
		}

		// double check to make sure it is a html file
		if (strlen($buffer) > 300) {
			$buffer = substr($buffer, 0, 300);
		}
		if (strstr($buffer, '<!--') !== false) {
			$buffer = preg_replace('/<!--.*?-->/s', '', $buffer);
		}
		$buffer = trim($buffer);

		$buffer = File::remove_zero_space($buffer);

		$is_html = stripos($buffer, '<html') === 0 || stripos($buffer, '<!DOCTYPE') === 0;

		if (!$is_html) {
			Debug2::debug('[Core] Footer check failed: ' . ob_get_level() . '-' . substr($buffer, 0, 100));
			return;
		}

		Debug2::debug('[Core] Footer check passed');

		if (!defined('LITESPEED_IS_HTML')) {
			define('LITESPEED_IS_HTML', true);
		}
	}

	/**
	 * For compatibility with those plugins have 'Bad' logic that forced all buffer output even it is NOT their buffer :(
	 *
	 * Usually this is called after send_headers() if following original WP process
	 *
	 * @since 1.1.5
	 * @access public
	 * @param  string $buffer
	 * @return string
	 */
	public function send_headers_force($buffer)
	{
		$this->_check_is_html($buffer);

		// Hook to modify buffer before
		$buffer = apply_filters('litespeed_buffer_before', $buffer);

		/**
		 * Media: Image lazyload && WebP
		 * GUI: Clean wrapper mainly for esi block NOTE: this needs to be before optimizer to avoid wrapper being removed
		 * Optimize
		 * CDN
		 */
		if (!defined('LITESPEED_NO_OPTM') || !LITESPEED_NO_OPTM) {
			Debug2::debug('[Core] run hook litespeed_buffer_finalize');
			$buffer = apply_filters('litespeed_buffer_finalize', $buffer);
		}

		/**
		 * Replace ESI preserved list
		 * @since  3.3 Replace this in the end to avoid `Inline JS Defer` or other Page Optm features encoded ESI tags wrongly, which caused LSWS can't recognize ESI
		 */
		$buffer = $this->cls('ESI')->finalize($buffer);

		$this->send_headers(true);

		// Log ESI nonce buffer empty issue
		if (defined('LSCACHE_IS_ESI') && strlen($buffer) == 0) {
			// log ref for debug purpose
			error_log('ESI buffer empty ' . $_SERVER['REQUEST_URI']);
		}

		// Init comment info
		$running_info_showing = defined('LITESPEED_IS_HTML') || defined('LSCACHE_IS_ESI');
		if (defined('LSCACHE_ESI_SILENCE')) {
			$running_info_showing = false;
			Debug2::debug('[Core] ESI silence');
		}
		/**
		 * Silence comment for json req
		 * @since 2.9.3
		 */
		if (REST::cls()->is_rest() || Router::is_ajax()) {
			$running_info_showing = false;
			Debug2::debug('[Core] Silence Comment due to REST/AJAX');
		}
		$running_info_showing = apply_filters('litespeed_comment', $running_info_showing);
		if ($running_info_showing) {
			if ($this->_footer_comment) {
				$buffer .= $this->_footer_comment;
			}
		}

		/**
		 * If ESI req is JSON, give the content JSON format
		 * @since  2.9.3
		 * @since  2.9.4 ESI req could be from internal REST call, so moved json_encode out of this cond
		 */
		if (defined('LSCACHE_IS_ESI')) {
			Debug2::debug('[Core] ESI Start 👇');
			if (strlen($buffer) > 500) {
				Debug2::debug(trim(substr($buffer, 0, 500)) . '.....');
			} else {
				Debug2::debug($buffer);
			}
			Debug2::debug('[Core] ESI End 👆');
		}

		if (apply_filters('litespeed_is_json', false)) {
			if (\json_decode($buffer, true) == null) {
				Debug2::debug('[Core] Buffer converting to JSON');
				$buffer = \json_encode($buffer);
				$buffer = trim($buffer, '"');
			} else {
				Debug2::debug('[Core] JSON Buffer');
			}
		}

		// Hook to modify buffer after
		$buffer = apply_filters('litespeed_buffer_after', $buffer);

		Debug2::ended();

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
	public function send_headers($is_forced = false)
	{
		// Make sure header output only run once
		if (!defined('LITESPEED_DID_' . __FUNCTION__)) {
			define('LITESPEED_DID_' . __FUNCTION__, true);
		} else {
			return;
		}

		// Avoid PHP warning for header sent out already
		if (headers_sent()) {
			self::debug('❌ !!! Err: Header sent out already');
			return;
		}

		$this->_check_is_html();

		// NOTE: cache ctrl output needs to be done first, as currently some varies are added in 3rd party hook `litespeed_api_control`.
		$this->cls('Control')->finalize();

		$vary_header = $this->cls('Vary')->finalize();

		// If is not cacheable but Admin QS is `purge` or `purgesingle`, `tag` still needs to be generated
		$tag_header = $this->cls('Tag')->output();
		if (!$tag_header && Control::is_cacheable()) {
			Control::set_nocache('empty tag header');
		}

		// NOTE: `purge` output needs to be after `tag` output as Admin QS may need to send `tag` header
		$purge_header = Purge::output();

		// generate `control` header in the end in case control status is changed by other headers.
		$control_header = $this->cls('Control')->output();

		// Give one more break to avoid ff crash
		if (!defined('LSCACHE_IS_ESI')) {
			$this->_footer_comment .= "\n";
		}

		$cache_support = 'supported';
		if (defined('LITESPEED_ON')) {
			$cache_support = Control::is_cacheable() ? 'cached' : 'uncached';
		}

		$this->_comment(
			sprintf(
				'%1$s %2$s by LiteSpeed Cache %4$s on %3$s',
				defined('LSCACHE_IS_ESI') ? 'Block' : 'Page',
				$cache_support,
				date('Y-m-d H:i:s', time() + LITESPEED_TIME_OFFSET),
				self::VER
			)
		);

		// send Control header
		if (defined('LITESPEED_ON') && $control_header) {
			$this->_http_header($control_header);
			if (!Control::is_cacheable()) {
				$this->_http_header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0'); // @ref: https://wordpress.org/support/topic/apply_filterslitespeed_control_cacheable-returns-false-for-cacheable/
			}
			if (defined('LSCWP_LOG')) {
				$this->_comment($control_header);
			}
		}
		// send PURGE header (Always send regardless of cache setting disabled/enabled)
		if (defined('LITESPEED_ON') && $purge_header) {
			$this->_http_header($purge_header);
			Debug2::log_purge($purge_header);

			if (defined('LSCWP_LOG')) {
				$this->_comment($purge_header);
			}
		}
		// send Vary header
		if (defined('LITESPEED_ON') && $vary_header) {
			$this->_http_header($vary_header);
			if (defined('LSCWP_LOG')) {
				$this->_comment($vary_header);
			}
		}

		if (defined('LITESPEED_ON') && defined('LSCWP_LOG')) {
			$vary = $this->cls('Vary')->finalize_full_varies();
			if ($vary) {
				$this->_comment('Full varies: ' . $vary);
			}
		}

		// Admin QS show header action
		if (self::$_debug_show_header) {
			$debug_header = self::HEADER_DEBUG . ': ';
			if ($control_header) {
				$debug_header .= $control_header . '; ';
			}
			if ($purge_header) {
				$debug_header .= $purge_header . '; ';
			}
			if ($tag_header) {
				$debug_header .= $tag_header . '; ';
			}
			if ($vary_header) {
				$debug_header .= $vary_header . '; ';
			}
			$this->_http_header($debug_header);
		} else {
			// Control header
			if (defined('LITESPEED_ON') && Control::is_cacheable() && $tag_header) {
				$this->_http_header($tag_header);
				if (defined('LSCWP_LOG')) {
					$this->_comment($tag_header);
				}
			}
		}

		// Object cache _comment
		if (defined('LSCWP_LOG') && defined('LSCWP_OBJECT_CACHE') && method_exists('WP_Object_Cache', 'debug')) {
			$this->_comment('Object Cache ' . \WP_Object_Cache::get_instance()->debug());
		}

		if (defined('LITESPEED_GUEST') && LITESPEED_GUEST) {
			$this->_comment('Guest Mode');
		}

		if (!empty($this->_footer_comment)) {
			self::debug('[footer comment] ' . $this->_footer_comment);
		}

		if ($is_forced) {
			Debug2::debug('--forced--');
		}

		/**
		 * If is CLI and contains Purge Header, then issue a HTTP req to Purge
		 * @since v5.3
		 */
		if (defined('LITESPEED_CLI')) {
			$purge_queue = Purge::get_option(Purge::DB_QUEUE);
			if (!$purge_queue || $purge_queue == -1) {
				$purge_queue = Purge::get_option(Purge::DB_QUEUE2);
			}
			if ($purge_queue && $purge_queue != -1) {
				self::debug('[Core] Purge Queue found, issue a HTTP req to purge: ' . $purge_queue);
				// Kick off HTTP req
				$url = admin_url('admin-ajax.php');
				$resp = wp_safe_remote_get($url);
				if (is_wp_error($resp)) {
					$error_message = $resp->get_error_message();
					self::debug('[URL]' . $url);
					self::debug('failed to request: ' . $error_message);
				} else {
					self::debug('HTTP req res: ' . $resp['body']);
				}
			}
		}
	}

	/**
	 * Append one HTML comment
	 * @since 5.5
	 */
	public static function comment($data)
	{
		self::cls()->_comment($data);
	}

	private function _comment($data)
	{
		$this->_footer_comment .= "\n<!-- " . $data . ' -->';
	}

	/**
	 * Send HTTP header
	 * @since 5.3
	 */
	private function _http_header($header)
	{
		if (defined('LITESPEED_CLI')) {
			return;
		}

		@header($header);

		if (!defined('LSCWP_LOG')) {
			return;
		}
		Debug2::debug('💰 ' . $header);
	}
}
