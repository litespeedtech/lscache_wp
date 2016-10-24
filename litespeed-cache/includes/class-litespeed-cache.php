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

	private static $instance ;
	private static $log_path = '';

	const PLUGIN_NAME = 'litespeed-cache' ;
	const PLUGIN_VERSION = '1.0.11' ;

	const LSCOOKIE_VARY_NAME = 'LSCACHE_VARY_COOKIE' ;
	const LSCOOKIE_DEFAULT_VARY = '_lscache_vary' ;
	const LSCOOKIE_VARY_LOGGED_IN = 1;
	const LSCOOKIE_VARY_COMMENTER = 2;

	const ADMINQS_KEY = 'LSCWP_CTRL';
	const ADMINQS_DISMISS = 'DISMISS';
	const ADMINQS_PURGE = 'PURGE';
	const ADMINQS_PURGEALL = 'PURGEALL';
	const ADMINQS_PURGESINGLE = 'PURGESINGLE';
	const ADMINQS_SHOWHEADERS = 'SHOWHEADERS';

	const CACHECTRL_NOCACHE = 0;
	const CACHECTRL_PUBLIC = 1;
	const CACHECTRL_PURGE = 2;
	const CACHECTRL_PURGESINGLE = 3;
	const CACHECTRL_PRIVATE = 4;
	const CACHECTRL_SHARED = 5;

	const CACHECTRL_SHOWHEADERS = 128; // (1<<7)

	const WHM_TRANSIENT = 'lscwp_whm_install';
	const WHM_TRANSIENT_VAL = 'whm_install';

	protected $plugin_dir ;
	protected $config ;
	protected $current_vary;
	protected $cachectrl = self::CACHECTRL_NOCACHE;
	protected $pub_purge_tags = array();
	protected $custom_ttl = 0;
	protected $user_status = 0;


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
		$cur_dir = dirname(__FILE__) ;
		require_once $cur_dir . '/class-litespeed-cache-config.php' ;
		include_once $cur_dir . '/class-litespeed-cache-esi.php' ;
		include_once $cur_dir . '/class-litespeed-cache-tags.php';
		// Load third party detection.
		include_once $cur_dir . '/../thirdparty/litespeed-cache-thirdparty-registry.php';

		$theme_root = get_theme_root();
		$content_dir = dirname($theme_root);

		$should_debug = LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE;
		self::$log_path = $content_dir . '/debug.log';
		$this->config = new LiteSpeed_Cache_Config() ;
		if ($this->config->get_option(LiteSpeed_Cache_Config::OPID_ENABLED)) {
			$should_debug = intval($this->config->get_option(
				LiteSpeed_Cache_Config::OPID_DEBUG));
		}

		switch ($should_debug) {
			// NOTSET is used as check admin IP here.
		case LiteSpeed_Cache_Config::OPID_ENABLED_NOTSET:
			$ips = $this->config->get_option(LiteSpeed_Cache_Config::OPID_ADMIN_IPS);
			if (strpos($ips, $_SERVER['REMOTE_ADDR']) === false) {
				break;
			}
			// fall through
		case LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE:
			define ('LSCWP_LOG', true);
			break;
		case LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE:
			break;
		default:
			break;
		}

		$this->plugin_dir = plugin_dir_path($cur_dir) ;
		$plugin_file = $this->plugin_dir . 'litespeed-cache.php' ;
		register_activation_hook($plugin_file, array( $this, 'register_activation' )) ;
		register_deactivation_hook($plugin_file, array( $this, 'register_deactivation' )) ;

		add_action('after_setup_theme', array( $this, 'init' )) ;
	}

	/**
	 * The entry point for LiteSpeed Cache.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function run()
	{
		if ( ! isset(self::$instance) ) {
			self::$instance = new LiteSpeed_Cache() ;
		}
	}

	/**
	 * Get the LiteSpeed_Cache object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return LiteSpeed_Cache Static instance of the LiteSpeed_Cache class.
	 */
	public static function plugin()
	{
		return self::$instance ;
	}

	/**
	 * Get the LiteSpeed_Cache_Config object. Can be called outside of a
	 * LiteSpeed_Cache object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $opt_id An option ID if getting an option.
	 * @return LiteSpeed_Cache_Config The configurations for the accessed page.
	 */
	public static function config($opt_id = '')
	{
		$conf = self::$instance->config;
		if ((empty($opt_id)) || (!is_string($opt_id))) {
			return $conf;
		}
		return $conf->get_option($opt_id);
	}

	private static function setup_debug_log()
	{
		if (!defined('LSCWP_LOG_TAG')) {
			define('LSCWP_LOG_TAG',
				'LSCACHE_WP_blogid_' . get_current_blog_id());
		}
		self::log_request();

	}

	private static function format_message($mesg)
	{
		$tag = defined('LSCWP_LOG_TAG') ? constant('LSCWP_LOG_TAG') : 'LSCACHE_WP';
		$formatted = sprintf("%s [%s:%s] [%s] %s\n", date('r'),
			$_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
			$tag, $mesg);
		return $formatted;
	}

	/**
	 * Logs a debug message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $mesg The debug message.
	 */
	public static function debug_log($mesg)
	{
		$formatted = self::format_message($mesg);
		file_put_contents(self::$log_path, $formatted, FILE_APPEND);
	}

	private static function log_request()
	{
		$params = array(
			sprintf('%s %s %s', $_SERVER['REQUEST_METHOD'],
				$_SERVER['SERVER_PROTOCOL'], strtok($_SERVER['REQUEST_URI'], '?')),
			'Query String: '		. $_SERVER['QUERY_STRING'],
			'User Agent: '			. $_SERVER['HTTP_USER_AGENT'],
			'Accept Encoding: '		. $_SERVER['HTTP_ACCEPT_ENCODING'],
			'Cookie: '				. $_SERVER['HTTP_COOKIE'],
			'X-LSCACHE: '			. ($_SERVER['X-LSCACHE'] ? 'true' : 'false'),
			'LSCACHE_VARY_COOKIE: ' . $_SERVER['LSCACHE_VARY_COOKIE'],
			'LSCACHE_VARY_VALUE: '	. $_SERVER['LSCACHE_VARY_VALUE'],
		);

		$request = array_map('self::format_message', $params);
		file_put_contents(self::$log_path, $request, FILE_APPEND);
	}

	/**
	 * Helper function to build paragraphs out of all the string sentences
	 * passed in.
	 *
	 * @since 1.0.11
	 * @access public
	 * @param string $args,... Variable number of strings to combine to a paragraph.
	 * @return string The built paragraph.
	 */
	public static function build_paragraph()
	{
		$args = func_get_args();
		$para = implode(' ', $args);
		return $para;
	}

	/**
	 * The activation hook callback.
	 *
	 * Attempts to set up the advanced cache file. If it fails for any reason,
	 * the plugin will not activate.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_activation()
	{
		if (!defined('LSCWP_LOG_TAG')) {
			define('LSCWP_LOG_TAG',
				'LSCACHE_WP_activate_' . get_current_blog_id());
		}
		$this->try_copy_advanced_cache();
		LiteSpeed_Cache_Config::wp_cache_var_setter(true);
		flush_rewrite_rules();

		include_once $this->plugin_dir . '/admin/class-litespeed-cache-admin.php';
		require_once $this->plugin_dir . '/admin/class-litespeed-cache-admin-rules.php';
		$this->config->plugin_activation();
		self::generate_environment_report();

		if (defined('LSCWP_PLUGIN_NAME')) {
			set_transient(self::WHM_TRANSIENT, self::WHM_TRANSIENT_VAL);
		}
	}

	/**
	 * The deactivation hook callback.
	 *
	 * Initializes all clean up functionalities.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_deactivation()
	{
		if (!defined('LSCWP_LOG_TAG')) {
			define('LSCWP_LOG_TAG',
				'LSCACHE_WP_deactivate_' . get_current_blog_id());
		}
		$this->purge_all() ;
		if ((is_multisite()) && (!is_network_admin())) {
			return;
		}
		$adv_cache_path = ABSPATH . 'wp-content/advanced-cache.php';
		if (file_exists($adv_cache_path) && is_writable($adv_cache_path)) {
			unlink($adv_cache_path) ;
		}
		else {
			error_log('Failed to remove advanced-cache.php, file does not exist or is not writable!') ;
		}

		if (!LiteSpeed_Cache_Config::wp_cache_var_setter(false)) {
			error_log('In wp-config.php: WP_CACHE could not be set to false during deactivation!') ;
		}
		flush_rewrite_rules();
		require_once $this->plugin_dir . '/admin/class-litespeed-cache-admin-rules.php';
		LiteSpeed_Cache_Admin_Rules::clear_rules();
		// delete in case it's not deleted prior to deactivation.
		delete_transient(self::WHM_TRANSIENT);
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
		$is_ajax = (defined('DOING_AJAX') && DOING_AJAX);
		$module_enabled = $this->config->is_plugin_enabled();

		if (defined('LSCWP_LOG')) {
			self::setup_debug_log();
		}

		if ((is_admin()) && (current_user_can('administrator'))) {
			$this->load_admin_actions($module_enabled) ;
		}
		else {
			$this->load_nonadmin_actions($module_enabled) ;
		}

		if ((!$module_enabled) || (!defined('LSCACHE_ADV_CACHE'))
			|| (constant('LSCACHE_ADV_CACHE') === false)) {
			return;
		}

		define('LITESPEED_CACHE_ENABLED', true);
		ob_start();

		$this->setup_cookies();

		if ( $this->check_user_logged_in() || $this->check_cookies() ) {
			$this->load_logged_in_actions() ;
		}
		else {
			$this->load_logged_out_actions();
		}

		$this->load_public_actions($is_ajax);
		if (defined('DOING_AJAX') && DOING_AJAX) {
			do_action('litespeed_cache_detect_thirdparty');
		}
		elseif ((is_admin()) || (is_network_admin())) {
			add_action('admin_init', array($this, 'detect'), 0);
		}
		else {
			add_action('wp', array($this, 'detect'), 4);
		}
	}

	/**
	 * Callback used to call the detect third party action.
	 *
	 * The detect action is used by third party plugin integration classes
	 * to determine if they should add the rest of their hooks.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function detect()
	{
		do_action('litespeed_cache_detect_thirdparty');
	}

	/**
	 * Get the LiteSpeed_Cache_Config object.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return LiteSpeed_Cache_Config The configurations for the accessed page.
	 */
	public function get_config()
	{
		return $this->config ;
	}

	/**
	 * Try to copy our advanced-cache.php file to the wordpress directory.
	 *
	 * @since 1.0.11
	 * @access public
	 * @return boolean True on success, false on failure.
	 */
	public function try_copy_advanced_cache()
	{
		if ((file_exists(ABSPATH . 'wp-content/advanced-cache.php'))
			&& ((filesize(ABSPATH . 'wp-content/advanced-cache.php') !== 0)
				|| (!is_writable(ABSPATH . 'wp-content/advanced-cache.php')))) {
			return false;
		}
		copy($this->plugin_dir . '/includes/advanced-cache.php',
			ABSPATH . 'wp-content/advanced-cache.php');
		include_once(ABSPATH . 'wp-content/advanced-cache.php');
		$ret = defined('LSCACHE_ADV_CACHE');
		return $ret;
	}

	/**
	 * Adds a notice to the admin interface that the WordPress version is too old for the plugin
	 *
	 * @since 1.0.0
	 */
	public static function show_version_error_wp()
	{
		echo '<div class="error"><p><strong>'
		. __('Your WordPress version is too old for the LiteSpeed Cache Plugin.', 'litespeed-cache')
		. '</strong><br />'
		. sprintf(wp_kses(__('The LiteSpeed Cache Plugin requires at least WordPress %2$s. Please upgrade or go to <a href="%1$s">active plugins</a> and deactivate the LiteSpeed Cache plugin to hide this message.', 'litespeed-cache'), array( 'a' => array( 'href' => array() ) )), 'plugins.php?plugin_status=active', '3.3')
		. '</p></div>' ;
	}

	/**
	 * Adds a notice to the admin interface that the WordPress version is too old for the plugin
	 *
	 * @since 1.0.0
	 */
	public static function show_version_error_php()
	{
		echo '<div class="error"><p><strong>'
		. __('Your PHP version is too old for LiteSpeed Cache Plugin.', 'litespeed-cache')
		. '</strong><br /> '
		. sprintf(wp_kses(__('LiteSpeed Cache Plugin requires at least PHP %3$s. You are using PHP %2$s, which is out-dated and insecure. Please ask your web host to update your PHP installation or go to <a href="%1$s">active plugins</a> and deactivate LiteSpeed Cache plugin to hide this message.', 'litespeed-cache'), array( 'a' => array( 'href' => array() ) )), esc_url("plugins.php?plugin_status=active"), PHP_VERSION, '5.3')
		. '</p></div>' ;
	}

	/**
	 * Adds a notice to the admin interface that WP_CACHE was not set
	 *
	 * @since 1.0.1
	 */
	public static function show_wp_cache_var_set_error()
	{
		echo '<div class="error"><p><strong>'
		. sprintf(__('LiteSpeed Cache was unable to write to your wp-config.php file. Please add the following to your wp-config.php file located under your WordPress root directory: define(\'WP_CACHE\', true);', 'litespeed-cache'))
		. '</p></div>' ;
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the LiteSpeed_Cache_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{
		load_plugin_textdomain(self::PLUGIN_NAME, false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/') ;
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_admin_actions( $module_enabled )
	{
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once $this->plugin_dir . 'admin/class-litespeed-cache-admin.php' ;
		require_once $this->plugin_dir . 'admin/class-litespeed-cache-admin-display.php' ;
		require_once $this->plugin_dir . 'admin/class-litespeed-cache-admin-rules.php' ;

		$admin = new LiteSpeed_Cache_Admin(self::PLUGIN_NAME, self::PLUGIN_VERSION) ;

		//register purge_all actions
		if ( $module_enabled ) {
			$purge_all_events = array(
				'switch_theme',
				'wp_create_nav_menu', 'wp_update_nav_menu', 'wp_delete_nav_menu',
				'create_term', 'edit_terms', 'delete_term',
				'add_link', 'edit_link', 'delete_link'
			) ;
			foreach ( $purge_all_events as $event ) {
				add_action($event, array( $this, 'purge_all' )) ;
			}
			global $pagenow;
			if ($pagenow === 'plugins.php') {
				add_action('wp_default_scripts',
					array($admin, 'set_update_text'), 0);
				add_action('wp_default_scripts',
					array($admin, 'unset_update_text'), 20);

			}
			add_action('admin_init', array($this, 'check_admin_ip'), 6);
			if ($this->config->get_option(LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE)) {
				add_action('upgrader_process_complete', array($this, 'purge_all'));
			}

			//Checks if WP_CACHE is defined and true in the wp-config.php file.
			if ((current_user_can('manage_options'))
				&& ((!defined('WP_CACHE')) || (constant('WP_CACHE') == false))) {

				if ((is_multisite()) && (is_network_admin())) {
					$action = 'network_admin_notices';
				}
				else {
					$action = 'admin_notices';
				}
				add_action($action, 'LiteSpeed_Cache::show_wp_cache_var_set_error');
			}

			add_action('wp_before_admin_bar_render',
				array($admin, 'add_quick_purge'));
		}

		add_action('in_widget_form',
			array(LiteSpeed_Cache_Admin_Display::get_instance(),
				'show_widget_edit'), 100, 3);
		add_filter('widget_update_callback',
			array($admin, 'validate_widget_save'), 10, 4);

		add_action('load-litespeed-cache_page_lscache-edit-htaccess',
				'LiteSpeed_Cache_Admin_Rules::htaccess_editor_save');
		add_action('load-litespeed-cache_page_lscache-settings',
				array($admin, 'parse_settings'));
		if (is_multisite()) {
			add_action('update_site_option_' . LiteSpeed_Cache_Config::OPTION_NAME,
					'LiteSpeed_Cache::update_environment_report', 10, 2);
		}
		else {
			add_action('update_option_' . LiteSpeed_Cache_Config::OPTION_NAME,
					'LiteSpeed_Cache::update_environment_report', 10, 2);
		}
		$this->set_locale() ;
	}

	/**
	 * Register all of the hooks for non admin pages.
	 * of the plugin.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function load_nonadmin_actions( $module_enabled )
	{
		if ($module_enabled) {
			add_action('wp', array($this, 'check_admin_ip'), 6);
		}
	}

	/**
	 * Register all the hooks for logged in users.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_logged_in_actions()
	{
	}

	/**
	 * Register all the hooks for non-logged in users.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_logged_out_actions()
	{
		// user is not logged in
		add_action('wp', array( $this, 'check_cacheable' ), 5) ;
		add_action('login_init', array( $this, 'check_login_cacheable' ), 5) ;

		$cache_res = $this->config->get_option(
			LiteSpeed_Cache_Config::OPID_CACHE_RES);
		if ($cache_res) {
			require_once $this->plugin_dir . 'admin/class-litespeed-cache-admin-rules.php';
			$uri = esc_url($_SERVER["REQUEST_URI"]);
			$pattern = '!' . LiteSpeed_Cache_Admin_Rules::$RW_PATTERN_RES . '!';
			if (preg_match($pattern, $uri)) {
				add_action('wp_loaded', array( $this, 'check_cacheable' ), 5) ;
			}
		}
	}

	/**
	 * Register all of the hooks related to the all users
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param boolean $is_ajax Denotes if the request is an ajax request.
	 */
	private function load_public_actions($is_ajax)
	{
		//register purge actions
		$purge_post_events = array(
			'edit_post',
			'save_post',
			'deleted_post',
			'trashed_post',
			'delete_attachment',
		) ;
		foreach ( $purge_post_events as $event ) {
			// this will purge all related tags
			add_action($event, array( $this, 'purge_post' ), 10, 2) ;
		}

		// The ESI functionality is an enterprise feature.
		// Removing the openlitespeed check will simply break the page.
		if ((!is_openlitespeed()) && (!$is_ajax)) {
			$esi = LiteSpeed_Cache_Esi::get_instance();
			add_action('init', array($esi, 'register_post_type'));
			add_action('template_include', array($esi, 'esi_template'), 100);
		}
		add_action('wp_update_comment_count',
			array($this, 'purge_feeds'));

		add_action('shutdown', array($this, 'send_headers'), 0);
		// purge_single_post will only purge that post by tag
		add_action('lscwp_purge_single_post', array($this, 'purge_single_post'));
		// TODO: private purge?
		// TODO: purge by category, tag?
	}

	/**
	 * Adds the actions used for setting up cookies on log in/out.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function setup_cookies()
	{
		$this->current_vary = isset($_SERVER[self::LSCOOKIE_VARY_NAME])
				? $_SERVER[self::LSCOOKIE_VARY_NAME] : self::LSCOOKIE_DEFAULT_VARY;

		// Set vary cookie for logging in user, unset for logging out.
		add_action('set_logged_in_cookie', array( $this, 'set_user_cookie'), 10, 5);
		add_action('clear_auth_cookie', array( $this, 'set_user_cookie'), 10, 5);

		if ( !$this->config->get_option(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS)) {
			// Set vary cookie for commenter.
			add_action('set_comment_cookies', array( $this, 'set_comment_cookie'), 10, 2);
		}

	}

	/**
	 * Do the action of setting the vary cookie.
	 *
	 * Since we are using bitwise operations, if the resulting cookie has
	 * value zero, we need to set the expire time appropriately.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param integer $update_val The value to update.
	 * @param integer $expire Expire time.
	 * @param boolean $ssl True if ssl connection, false otherwise.
	 * @param boolean $httponly True if the cookie is for http requests only, false otherwise.
	 */
	private function do_set_cookie($update_val, $expire, $ssl = false, $httponly = false)
	{
		$curval = 0;
		if (isset($_COOKIE[$this->current_vary]))
		{
			$curval = intval($_COOKIE[$this->current_vary]);
		}

		// not, remove from curval.
		if ($update_val < 0) {
			// If cookie will no longer exist, delete the cookie.
			if (($curval == 0) || ($curval == (~$update_val))) {
				// Use a year in case of bad local clock.
				$expire = time() - 31536001;
			}
			$curval &= $update_val;
		}
		else { // add to curval.
			$curval |= $update_val;
		}
		setcookie($this->current_vary, $curval, $expire, COOKIEPATH,
				COOKIE_DOMAIN, $ssl, $httponly);
	}

	/**
	 * Sets cookie denoting logged in/logged out.
	 *
	 * This will notify the server on next page request not to serve from cache.
	 *
	 * @since 1.0.1
	 * @access public
	 * @param mixed $logged_in_cookie
	 * @param string $expire Expire time.
	 * @param integer $expiration Expire time.
	 * @param integer $user_id The user's id.
	 * @param string $action Whether the user is logging in or logging out.
	 */
	public function set_user_cookie($logged_in_cookie = false, $expire = ' ',
					$expiration = 0, $user_id = 0, $action = 'logged_out')
	{
		if ($action == 'logged_in') {
			$this->do_set_cookie(self::LSCOOKIE_VARY_LOGGED_IN, $expire, is_ssl(), true);
		}
		else {
			$this->do_set_cookie(~self::LSCOOKIE_VARY_LOGGED_IN,
					time() + apply_filters( 'comment_cookie_lifetime', 30000000 ));
		}
	}

	/**
	 * Sets a cookie that marks the user as a commenter.
	 *
	 * This will notify the server on next page request not to serve
	 * from cache if that setting is enabled.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param mixed $comment Comment object
	 * @param mixed $user The visiting user object.
	 */
	public function set_comment_cookie($comment, $user)
	{
		if ( $user->exists() ) {
			return;
		}
		$comment_cookie_lifetime = time() + apply_filters( 'comment_cookie_lifetime', 30000000 );
		$this->do_set_cookie(self::LSCOOKIE_VARY_COMMENTER, $comment_cookie_lifetime);
	}

	/**
	 * Add purge tags to the current list.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param mixed $tags A string or an array of tags to add to the purge list.
	 * @param boolean $is_public Denotes if the tag is a public purge tag.
	 */
	private function add_purge_tags($tags, $is_public = true)
	{
		//TODO: implement private tag add
		if (is_array($tags)) {
			$this->pub_purge_tags = array_merge($this->pub_purge_tags, $tags);
		}
		else {
			$this->pub_purge_tags[] = $tags;
		}
		$this->pub_purge_tags = array_unique($this->pub_purge_tags);
	}

	/**
	 * Alerts LiteSpeed Web Server to purge all pages.
	 *
	 * For multisite installs, if this is called by a site admin (not network admin),
	 * it will only purge all posts associated with that site.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function purge_all()
	{
		$this->add_purge_tags('*');
	}

	/**
	 * Alerts LiteSpeed Web Server to purge the front page.
	 *
	 * @since    1.0.3
	 * @access   public
	 */
	public function purge_front()
	{
		$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_FRONTPAGE);
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected category pages.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value The category slug.
	 * @param string $key Unused.
	 */
	public function purgeby_cat_cb($value, $key)
	{
		$val = trim($value);
		if (empty($val)) {
			return;
		}
		if (preg_match('/^[a-zA-Z0-9-]+$/', $val) == 0) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				__('Failed to purge by category, invalid category slug.', 'litespeed-cache'));
			return;
		}
		$cat = get_category_by_slug($val);
		if ($cat == false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				sprintf(__('Failed to purge by category, does not exist: %s', 'litespeed-cache'), $val));
			return;
		}

		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				sprintf(__('Purge category %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM . $cat->term_id);
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected post IDs.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value The post ID.
	 * @param string $key Unused.
	 */
	public function purgeby_pid_cb($value, $key)
	{
		$val = trim($value);
		if (empty($val)) {
			return;
		}
		if (!is_numeric($val)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				sprintf(__('Failed to purge by Post ID, given ID is not numeric: %s', 'litespeed-cache'), $val));
			return;
		}
		elseif (get_post_status($val) !== 'publish') {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				sprintf(__('Failed to purge by Post ID, given ID does not exist or is not published: %s',
						'litespeed-cache'), $val));
			return;
		}
		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				sprintf(__('Purge Post ID %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_POST . $val);
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected tag pages.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value The tag slug.
	 * @param string $key Unused.
	 */
	public function purgeby_tag_cb($value, $key)
	{
		$val = trim($value);
		if (empty($val)) {
			return;
		}
		if (preg_match('/^[a-zA-Z0-9-]+$/', $val) == 0) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				__('Failed to purge by tag, invalid tag slug.', 'litespeed-cache'));
			return;
		}
		$term = get_term_by('slug', $val, 'post_tag');
		if ($term == 0) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				sprintf(__('Failed to purge by tag, does not exist: %s', 'litespeed-cache'), $val));
			return;
		}

		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				sprintf(__('Purge tag %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM . $term->term_id);
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected urls.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value A url to purge.
	 * @param string $key Unused.
	 */
	public function purgeby_url_cb($value, $key)
	{
		$val = trim($value);
		if (empty($val)) {
			return;
		}

		if (strpos($val, '<') !== false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				__('Failed to purge by url, contained "<".', 'litespeed-cache'));
			return;
		}

		$hash = md5($val);

		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				sprintf(__('Purge url %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_URL . $hash);
		return;
	}

	/**
	 * Purge a list of pages when selected by admin. This method will
	 * look at the post arguments to determine how and what to purge.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public function purge_list()
	{
		if (!isset($_POST[LiteSpeed_Cache_Config::OPTION_NAME])) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
					LiteSpeed_Cache_Admin_Display::NOTICE_RED,
					__('ERROR: Something went wrong with the form! Please try again.', 'litespeed-cache'));
			return;
		}
		$conf = $_POST[LiteSpeed_Cache_Config::OPTION_NAME];
		$sel =  $conf[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT];
		$list_buf = $conf[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_LIST];
		if (empty($list_buf)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
					LiteSpeed_Cache_Admin_Display::NOTICE_RED,
					__('ERROR: Tried to purge list with empty list.', 'litespeed-cache'));
			return;
		}
		$list = explode("\n", $list_buf);
		switch($sel) {
			case LiteSpeed_Cache_Admin_Display::PURGEBY_CAT:
				$cb = 'purgeby_cat_cb';
				break;
			case LiteSpeed_Cache_Admin_Display::PURGEBY_PID:
				$cb = 'purgeby_pid_cb';
				break;
			case LiteSpeed_Cache_Admin_Display::PURGEBY_TAG:
				$cb = 'purgeby_tag_cb';
				break;
			case LiteSpeed_Cache_Admin_Display::PURGEBY_URL:
				$cb = 'purgeby_url_cb';
				break;
			default:
				LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
						LiteSpeed_Cache_Admin_Display::NOTICE_RED,
						__('ERROR: Bad Purge By selected value.', 'litespeed-cache'));
				return;
		}
		array_walk($list, Array($this, $cb));
	}

	/**
	 * Purges a post on update.
	 *
	 * This function will get the relevant purge tags to add to the response
	 * as well.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param integer $id The post id to purge.
	 */
	public function purge_post( $id )
	{
		$post_id = intval($id);
		// ignore the status we don't care
		if ( ! in_array(get_post_status($post_id), array( 'publish', 'trash' )) ) {
			return ;
		}

		$purge_tags = $this->get_purge_tags($post_id) ;
		if ( empty($purge_tags) ) {
			return;
		}
		if ( in_array('*', $purge_tags) ) {
			$this->add_purge_tags('*');
		}
		else {
			$this->add_purge_tags($purge_tags);
		}
//		$this->send_purge_headers();
	}

	/**
	 * Purge a single post.
	 *
	 * If a third party plugin needs to purge a single post, it can send
	 * a purge tag using this function.
	 *
	 * @since 1.0.1
	 * @access public
	 * @param integer $id The post id to purge.
	 */
	public function purge_single_post($id)
	{
		$post_id = intval($id);
		if ( ! in_array(get_post_status($post_id), array( 'publish', 'trash' )) ) {
			return ;
		}
		$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_POST . $post_id);
//		$this->send_purge_headers();
	}

	/**
	 * Hooked to the load-widgets.php action.
	 * Attempts to purge a single widget from cache.
	 * If no widget id is passed in, the method will attempt to find the
	 * widget id.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param type $widget_id The id of the widget to purge.
	 */
	public function purge_widget($widget_id = null)
	{
		if (is_null($widget_id)) {
			$widget_id = $_POST['widget-id'];
			if (is_null($widget_id)) {
				return;
			}
		}
		$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_WIDGET
			. $widget_id);
	}

	/**
	 * Hooked to the wp_update_comment_count action.
	 * Purges the comment widget when the count is updated.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $wp_widget_factory
	 */
	public function purge_comment_widget()
	{
		global $wp_widget_factory;
		$recent_comments = $wp_widget_factory->widgets['WP_Widget_Recent_Comments'];
		if (!is_null($recent_comments)) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_WIDGET
				. $recent_comments->id);
		}
	}

	/**
	 * Purges feeds on comment count update.
	 *
	 * @since 1.0.9
	 * @access public
	 */
	public function purge_feeds()
	{
		if ($this->config->get_option(LiteSpeed_Cache_Config::OPID_FEED_TTL) > 0) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_FEED);
		}
	}

	/**
	 * Checks if the user is logged in. If the user is logged in, does an
	 * additional check to make sure it's using the correct login cookie.
	 *
	 * @return boolean True if logged in, false otherwise.
	 */
	private function check_user_logged_in()
	{
		$err = self::build_paragraph(
			__('NOTICE: Database login cookie did not match your login cookie.', 'litespeed-cache'),
			__('If you recently changed the cookie in the settings, please log out and back in again.', 'litespeed-cache'),
			__("If not, please verify your LiteSpeed Cache setting's Advanced tab.", 'litespeed-cache'));
		if (is_openlitespeed()) {
			$err .= ' '
				. __('If using OpenLiteSpeed, you may need to restart the server for the changes to take effect.', 'litespeed-cache');
		}

		if (is_multisite()) {
			$options = $this->get_config()->get_site_options();
			$db_cookie = $options[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE];
		}
		else {
			$db_cookie = $this->get_config()
				->get_option(LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE);
		}

		if (empty($db_cookie)) {
			$db_cookie = self::LSCOOKIE_DEFAULT_VARY;
		}

		if (($db_cookie != $this->current_vary)
			&& ((is_multisite() ? is_network_admin() : is_admin()))) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_YELLOW, $err);
		}
		elseif (!is_user_logged_in()) {
			// If the cookie is set, unset it.
			if ((isset($_COOKIE)) && (isset($_COOKIE[$this->current_vary]))
				&& (intval($_COOKIE[$this->current_vary])
					& self::LSCOOKIE_VARY_LOGGED_IN)) {
				$this->do_set_cookie(~self::LSCOOKIE_VARY_LOGGED_IN,
					time() + apply_filters( 'comment_cookie_lifetime', 30000000 ));
				$_COOKIE[$this->current_vary] &= ~self::LSCOOKIE_VARY_LOGGED_IN;
			}
			return false;
		}
		elseif (!isset($_COOKIE[$this->current_vary])) {
			$this->do_set_cookie(self::LSCOOKIE_VARY_LOGGED_IN,
					time() + 2 * DAY_IN_SECONDS, is_ssl(), true);
		}
		$this->user_status |= self::LSCOOKIE_VARY_LOGGED_IN;
		return true;
	}

	/**
	 * Check if the user accessing the page has the commenter cookie.
	 *
	 * If the user does not want to cache commenters, just check if user is commenter.
	 * Otherwise if the vary cookie is set, unset it. This is so that when
	 * the page is cached, the page will appear as if the user was a normal user.
	 * Normal user is defined as not a logged in user and not a commenter.
	 *
	 * @since 1.0.4
	 * @access private
	 * @return boolean True if do not cache for commenters and user is a commenter. False otherwise.
	 */
	private function check_cookies()
	{
		if ($_SERVER["REQUEST_METHOD"] !== 'GET') {
			return false;
		}

		if (is_multisite()) {
			$options = $this->get_config()->get_site_options();
			$db_cookie = $options[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE];
		}
		else {
			$db_cookie = $this->get_config()
				->get_option(LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE);
		}

		if (empty($db_cookie)) {
			$db_cookie = self::LSCOOKIE_DEFAULT_VARY;
		}
		if (($db_cookie != $this->current_vary)
				&& (isset($_COOKIE[$db_cookie]))) {
			if (defined('LSCWP_LOG')) {
				$this->debug_log(self::build_paragraphs(
					__('NOTICE: Database login cookie does not match the cookie used to access the page.', 'litespeed-cache'),
					__('Please have the admin check the LiteSpeed Cache settings.', 'litespeed-cache'),
					__('This error may appear if you are logged into another web application.', 'litespeed-cache')
				));
			}
			return true;
		}
		if (!$this->config->get_option(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS))
		{
			// If do not cache commenters, check cookie for commenter value.
			if ((isset($_COOKIE[$this->current_vary]))
					&& ($_COOKIE[$this->current_vary] & self::LSCOOKIE_VARY_COMMENTER)) {
				$this->user_status |= self::LSCOOKIE_VARY_COMMENTER;
				return true;
			}
			// If wp commenter cookie exists, need to set vary and do not cache.
			foreach($_COOKIE as $cookie_name => $cookie_value) {
				if ((strlen($cookie_name) >= 15)
						&& (strncmp($cookie_name, 'comment_author_', 15) == 0)) {
					$user = wp_get_current_user();
					$this->set_comment_cookie(NULL, $user);
					$this->user_status |= self::LSCOOKIE_VARY_COMMENTER;
					return true;
				}
			}
			return false;
		}

		// If vary cookie is set, need to change the value.
		if (isset($_COOKIE[$this->current_vary])) {
			$this->do_set_cookie(~self::LSCOOKIE_VARY_COMMENTER, 14 * DAY_IN_SECONDS);
			unset($_COOKIE[$this->current_vary]);
		}

		// If cache commenters, unset comment cookies for caching.
		foreach($_COOKIE as $cookie_name => $cookie_value) {
			if ((strlen($cookie_name) >= 15)
					&& (strncmp($cookie_name, 'comment_author_', 15) == 0)) {
				$this->user_status |= self::LSCOOKIE_VARY_COMMENTER;
				unset($_COOKIE[$cookie_name]);
			}
		}
		return false;
	}

	/**
	 * Check admin configuration to see if the uri accessed is excluded from cache.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param array $excludes_list List of excluded URIs
	 * @return boolean True if excluded, false otherwise.
	 */
	private function is_uri_excluded($excludes_list)
	{
		$uri = esc_url($_SERVER["REQUEST_URI"]);
		$uri_len = strlen( $uri ) ;
		if (is_multisite()) {
			$blog_details = get_blog_details(get_current_blog_id());
			$blog_path = $blog_details->path;
			$blog_path_len = strlen($blog_path);
			if (($uri_len >= $blog_path_len)
				&& (strncmp($uri, $blog_path, $blog_path_len) == 0)) {
				$uri = substr($uri, $blog_path_len - 1);
				$uri_len = strlen( $uri ) ;
			}
		}
		foreach( $excludes_list as $excludes_rule )
		{
			$rule_len = strlen( $excludes_rule );
			if (($excludes_rule[$rule_len - 1] == '$')) {
				if ($uri_len != (--$rule_len)) {
					continue;
				}
			}
			elseif ( $uri_len < $rule_len ) {
				continue;
			}

			if ( strncmp( $uri, $excludes_rule, $rule_len ) == 0 ){
				return true ;
			}
		}
		return false;
	}

	/**
	 * Check if a page is cacheable.
	 *
	 * This will check what we consider not cacheable as well as what
	 * third party plugins consider not cacheable.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return boolean True if cacheable, false otherwise.
	 */
	private function is_cacheable()
	{
		// logged_in users already excluded, no hook added
		$method = $_SERVER["REQUEST_METHOD"] ;
		$conf = $this->config;

		if ( 'GET' !== $method ) {
			return $this->no_cache_for('not GET method') ;
		}

		if (($conf->get_option(LiteSpeed_Cache_Config::OPID_FEED_TTL) === 0)
			&& (is_feed())) {
			return $this->no_cache_for('feed') ;
		}

		if ( is_trackback() ) {
			return $this->no_cache_for('trackback') ;
		}

		if (($conf->get_option(LiteSpeed_Cache_Config::OPID_404_TTL) === 0)
			&& (is_404())) {
			return $this->no_cache_for('404 pages') ;
		}

		if ( is_search() ) {
			return $this->no_cache_for('search') ;
		}

//		if ( !defined('WP_USE_THEMES') || !WP_USE_THEMES ) {
//			return $this->no_cache_for('no theme used') ;
//		}

		$cacheable = apply_filters('litespeed_cache_is_cacheable', true);
		if (!$cacheable) {
			global $wp_filter;
			if ((!defined('LSCWP_LOG'))
				|| (empty($wp_filter['litespeed_cache_is_cacheable']))) {
				return $this->no_cache_for(
					'Third Party Plugin determined not cacheable.');
			}
			$funcs = array();
			foreach ($wp_filter['litespeed_cache_is_cacheable'] as $hook_level) {
				foreach ($hook_level as $func=>$params) {
					$funcs[] = $func;
				}
			}
			$this->no_cache_for('One of the following functions '
				. "determined that this page is not cacheable:\n\t\t"
				. implode("\n\t\t", $funcs));
			return false;
		}

		$excludes = $conf->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_URI);
		if (( ! empty($excludes))
			&& ( $this->is_uri_excluded(explode("\n", $excludes))))
		{
			return $this->no_cache_for('Admin configured URI Do not cache: '
					. $_SERVER['REQUEST_URI']);
		}

		$excludes = $conf->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT);
		if (( ! empty($excludes))
			&& (has_category(explode(',', $excludes)))) {
			return $this->no_cache_for('Admin configured Category Do not cache.');
		}

		$excludes = $conf->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG);
		if (( ! empty($excludes))
			&& (has_tag(explode(',', $excludes)))) {
			return $this->no_cache_for('Admin configured Tag Do not cache.');
		}

		$excludes = $conf->get_option(LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES);
		if ((!empty($excludes)) && (!empty($_COOKIE))) {
			$exclude_list = explode('|', $excludes);

			foreach( $_COOKIE as $key=>$val) {
				if (in_array($key, $exclude_list)) {
					return $this->no_cache_for('Admin configured Cookie Do not cache.');
				}
			}
		}

		$excludes = $conf->get_option(LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS);
		if ((!empty($excludes)) && (isset($_SERVER['HTTP_USER_AGENT']))) {
			$pattern = '/' . $excludes . '/';
			$nummatches = preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
			if ($nummatches) {
					return $this->no_cache_for('Admin configured User Agent Do not cache.');
			}
		}

		return true;
	}

	/**
	 * Write a debug message for if a page is not cacheable.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $reason An explanation for why the page is not cacheable.
	 * @return boolean Return false.
	 */
	public function no_cache_for( $reason )
	{
		if (defined('LSCWP_LOG')) {
			$this->debug_log('Do not cache - ' . $reason);
		}
		return false ;
	}

	/**
	 * Check if the post is cacheable. If so, set the cacheable member variable.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function check_cacheable()
	{
		error_log('checking cacheable');
		if ((LiteSpeed_Cache_Tags::is_noncacheable() == false)
			&& ($this->is_cacheable())) {
			$this->set_cachectrl(self::CACHECTRL_PUBLIC);
		}
	}

	public function set_cachectrl($val)
	{
		$this->cachectrl = $val;
	}

	public function set_custom_ttl($ttl)
	{
		if (is_numeric($ttl)) {
			$this->custom_ttl = $ttl;
		}
	}

	/**
	 * Check if the login page is cacheable.
	 * If not, unset the cacheable member variable.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function check_login_cacheable()
	{
		if ($this->config->get_option(LiteSpeed_Cache_Config::OPID_CACHE_LOGIN)
			=== false) {
			return;
		}
		$this->check_cacheable();
		if ($this->cachectrl !== self::CACHECTRL_PUBLIC) {
			return;
		}
		if (!empty($_GET)) {
			if (defined('LSCWP_LOG')) {
				$this->no_cache_for('Not a get request');
			}
			$this->set_cachectrl(self::CACHECTRL_NOCACHE);
			return;
		}

		LiteSpeed_Cache_Tags::add_cache_tag(LiteSpeed_Cache_Tags::TYPE_LOGIN);

		$list = headers_list();
		if (empty($list)) {
			return;
		}
		foreach ($list as $hdr) {
			if (strncasecmp($hdr, 'set-cookie:', 11) == 0) {
				$cookie = substr($hdr, 12);
				@header('lsc-cookie: ' . $cookie, false);
			}
		}
		return;
	}

	private function admin_ctrl_redirect()
	{
		global $pagenow;
		$qs = '';

		if (!empty($_GET)) {
			if (isset($_GET['LSCWP_CTRL'])) {
				unset($_GET['LSCWP_CTRL']);
			}
			if (isset($_GET['_wpnonce'])) {
				unset($_GET['_wpnonce']);
			}
			if (!empty($_GET)) {
				$qs = '?' . http_build_query($_GET);
			}
		}
		if (is_network_admin()) {
			$url = network_admin_url($pagenow . $qs);
		}
		else {
			$url = admin_url($pagenow . $qs);
		}
		wp_redirect($url);
		exit();
	}

	/**
	 * Check the query string to see if it contains a LSCWP_CTRL.
	 * Also will compare IPs to see if it is a valid command.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public function check_admin_ip()
	{
		// Not set, ignore.
		if (!isset($_GET[self::ADMINQS_KEY])) {
			return;
		}
		$action = $_GET[self::ADMINQS_KEY];
		if ((is_admin()) || (is_network_admin())) {
			if ((empty($_GET)) || (empty($_GET['_wpnonce']))
				|| ((wp_verify_nonce($_GET[ '_wpnonce' ], 'litespeed-purgeall') === false)
					&& (wp_verify_nonce($_GET[ '_wpnonce' ], 'litespeed-dismiss') === false))) {
				return;
			}
		}
		else {
			$ips = $this->config->get_option(LiteSpeed_Cache_Config::OPID_ADMIN_IPS);

			if (strpos($ips, $_SERVER['REMOTE_ADDR']) === false) {
				if (defined('LSCWP_LOG')) {
					$this->no_cache_for('LSCWP_CTRL query string - did not match admin IP');
				}
				$this->cachectrl = self::CACHECTRL_NOCACHE;
				return;
			}
		}

		if (defined('LSCWP_LOG')) {
			self::debug_log('LSCWP_CTRL query string action is ' . $action);
		}
		switch ($action[0]) {
			case 'P':
				if ($action == self::ADMINQS_PURGE) {
					$this->set_cachectrl(self::CACHECTRL_PURGE);
				}
				elseif ($action == self::ADMINQS_PURGESINGLE) {
					$this->set_cachectrl(self::CACHECTRL_PURGESINGLE);
				}
				elseif ($action == self::ADMINQS_PURGEALL) {
					$this->cachectrl = self::CACHECTRL_NOCACHE;
					$this->purge_all();
				}
				else {
					break;
				}
				if ((!is_admin()) && (!is_network_admin())) {
					return;
				}
				$this->admin_ctrl_redirect();
			case 'S':
				if ($action == self::ADMINQS_SHOWHEADERS) {
					$this->set_cachectrl($this->cachectrl
						| self::CACHECTRL_SHOWHEADERS);
					return;
				}
				break;
			case 'D':
				if ($action == self::ADMINQS_DISMISS) {
					delete_transient(self::WHM_TRANSIENT);
					$this->admin_ctrl_redirect();
				}
				break;
			default:
				break;
		}

		if (defined('LSCWP_LOG')) {
			$this->no_cache_for('LSCWP_CTRL query string should not cache.');
		}
		$this->set_cachectrl(self::CACHECTRL_NOCACHE);
	}

	/**
	 * Gathers all the purge headers.
	 *
	 * This will collect all site wide purge tags as well as
	 * third party plugin defined purge tags.
	 *
	 * @since 1.0.1
	 * @access private
	 */
	private function build_purge_headers()
	{
		$cache_purge_header = LiteSpeed_Cache_Tags::HEADER_PURGE;
		$purge_tags = array_merge($this->pub_purge_tags,
				LiteSpeed_Cache_Tags::get_purge_tags());
		$purge_tags = array_unique($purge_tags);

		if (empty($purge_tags)) {
			return;
		}

		$prefix = $this->config->get_option(
			LiteSpeed_Cache_Config::OPID_TAG_PREFIX);
		if (empty($prefix)) {
			$prefix = '';
		}

		if (!in_array('*', $purge_tags )) {
			$tags = array_map(array($this,'prefix_apply'), $purge_tags);
		}
		// Would only use multisite and network admin except is_network_admin
		// is false for ajax calls, which is used by wordpress updates v4.6+
		elseif ((is_multisite()) && ((is_network_admin())
			|| ((defined('DOING_AJAX')) && (check_ajax_referer('updates'))))) {
			global $wp_version;
			if (version_compare($wp_version, '4.6', '<')) {
				$blogs = wp_get_sites();
				if (!empty($blogs)) {
					foreach ($blogs as $key => $blog) {
						$blogs[$key] = $blog['blog_id'];
					}
				}
			}
			else {
				$blogs = get_sites(array('fields' => 'ids'));
			}
			if (empty($blogs)) {
				if (defined('LSCWP_LOG')) {
					self::debug_log('blog list is empty');
				}
				return '';
			}
			$tags = array();
			foreach ($blogs as $blog_id) {
				$tags[] = sprintf('%sB%s_', $prefix, $blog_id);
			}
		}
		else {
			$tags = array($prefix . 'B' . get_current_blog_id() . '_');
		}
		if (defined('LSCWP_LOG')) {
			self::debug_log('Purge tags are ' . implode(',', $tags));
		}

		$cache_purge_header .= ': tag=' . implode(',', $tags);
		return $cache_purge_header;
		// TODO: private cache headers
//		$cache_purge_header = LiteSpeed_Cache_Tags::HEADER_PURGE
//				. ': private,tag=' . implode(',', $this->ext_purge_private_tags);
//		@header($cache_purge_header, false);
	}

	/**
	 * The mode determines if the page is cacheable. This function filters
	 * out the possible show header admin control.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param boolean $showhdr Whether the show header command was selected.
	 * @return integer The integer corresponding to the selected
	 * cache control value.
	 */
	private function validate_mode(&$showhdr)
	{
		if ($this->cachectrl & self::CACHECTRL_SHOWHEADERS) {
			$showhdr = true;
			$mode = $this->cachectrl & ~self::CACHECTRL_SHOWHEADERS;
		}
		else {
			$mode = $this->cachectrl;
		}

		if (($mode != self::CACHECTRL_PUBLIC)
			&& ($mode != self::CACHECTRL_PRIVATE)) {
			return $mode;
		}
		elseif ((is_admin()) || (is_network_admin())) {
			return self::CACHECTRL_NOCACHE;
		}

		if (((defined('LSCACHE_NO_CACHE')) && (constant('LSCACHE_NO_CACHE')))
			|| (LiteSpeed_Cache_Tags::is_noncacheable())) {
			return self::CACHECTRL_NOCACHE;
		}

		if ($this->config->get_option(
				LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED) == false) {
			return (LiteSpeed_Cache_Tags::is_mobile() ? self::CACHECTRL_NOCACHE
														: $mode);
		}

		if ((isset($_SERVER['LSCACHE_VARY_VALUE']))
			&& ($_SERVER['LSCACHE_VARY_VALUE'] === 'ismobile')) {
			if ((!wp_is_mobile()) && (!LiteSpeed_Cache_Tags::is_mobile())) {
				return self::CACHECTRL_NOCACHE;
			}
		}
		elseif ((wp_is_mobile()) || (LiteSpeed_Cache_Tags::is_mobile())) {
			return self::CACHECTRL_NOCACHE;
		}

		return $mode;
	}

	/**
	 * Send out the LiteSpeed Cache headers. If show headers is true,
	 * will send out debug header.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param boolean $showhdr True to show debug header, false if real headers.
	 * @param string $cache_ctrl The cache control header to send out.
	 * @param string $purge_hdr The purge tag header to send out.
	 * @param string $cache_hdr The cache tag header to send out.
	 */
	private function header_out($showhdr, $cache_ctrl, $purge_hdr, $cache_hdr = '')
	{
		$hdr_content = array();
		if ((!is_null($cache_ctrl)) && (!empty($cache_ctrl))) {
			$hdr_content[] = $cache_ctrl;
		}
		if ((!is_null($purge_hdr)) && (!empty($purge_hdr))) {
			$hdr_content[] = $purge_hdr;
		}
		if ((!is_null($cache_hdr)) && (!empty($cache_hdr))) {
			$hdr_content[] = $cache_hdr;
		}

		if (!empty($hdr_content)) {
			if ($showhdr) {
				@header(LiteSpeed_Cache_Tags::HEADER_DEBUG . ': '
						. implode('; ', $hdr_content));
			}
			else {
				foreach($hdr_content as $hdr) {
					@header($hdr);
				}
			}
		}

		if (defined('LSCWP_LOG')) {
			self::debug_log('End response.');
		}
	}

	/**
	 * Sends the headers out at the end of processing the request.
	 *
	 * This will send out all LiteSpeed Cache related response headers
	 * needed for the post.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function send_headers()
	{
		$cache_tags = null;
		$cachectrl_val = 'public';
		$showhdr = false;
		$esi_hdr = LiteSpeed_Cache_Esi::get_instance()->has_esi()
			? ',esi=on' : '';
		do_action('litespeed_cache_add_purge_tags');

		$mode = $this->validate_mode($showhdr);

		if ($mode != self::CACHECTRL_NOCACHE) {
			do_action('litespeed_cache_add_cache_tags');
			$cache_tags = $this->get_cache_tags();
			if (($mode !== self::CACHECTRL_PURGE)
				&& ($mode !== self::CACHECTRL_PURGESINGLE)) {
				$cache_tags[] = ''; //add blank entry to add blog tag.
			}
		}

		if (empty($cache_tags)) {
if (defined('lscache_debug')) {
error_log('do not cache page.');
}
			$cache_control_header = LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL
					. ': no-cache' . $esi_hdr;
			$purge_headers = $this->build_purge_headers();
			$this->header_out($showhdr, $cache_control_header, $purge_headers);
			return;
		}
		$prefix_tags = array_map(array($this,'prefix_apply'), $cache_tags);
		if (defined('LSCWP_LOG')) {
			self::debug_log('Cache tags are ' . implode(',', $prefix_tags));
		}

if (defined('lscache_debug')) {
error_log('page is cacheable');
}
		switch ($mode) {
			case self::CACHECTRL_SHARED:
			case self::CACHECTRL_PRIVATE:
				if ($mode === self::CACHECTRL_SHARED) {
					$cachectrl_val = 'shared,';
				}
				else {
					$cachectrl_val = '';
				}
				$cachectrl_val .= 'private';
				// fall through
			case self::CACHECTRL_PUBLIC:
				$feed_ttl = $this->config->get_option(LiteSpeed_Cache_Config::OPID_FEED_TTL);
				$ttl_404 = $this->config->get_option(LiteSpeed_Cache_Config::OPID_404_TTL);

				if ($this->custom_ttl != 0) {
					$ttl = $this->custom_ttl;
				}
				elseif ((LiteSpeed_Cache_Tags::get_use_frontpage_ttl())
					|| (is_front_page())){
					$ttl = $this->config->get_option(LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL);
				}
				elseif ((is_feed()) && ($feed_ttl > 0)) {
					$ttl = $feed_ttl;
				}
				elseif ((is_404()) && ($ttl_404 > 0)) {
					$ttl = $ttl_404;
				}
				else {
					$ttl = $this->config->get_option(LiteSpeed_Cache_Config::OPID_PUBLIC_TTL) ;
				}
				$cache_control_header = LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL
						. ': ' . $cachectrl_val . ',max-age=' . $ttl . $esi_hdr;
				$cache_tag_header = LiteSpeed_Cache_Tags::HEADER_CACHE_TAG
					. ': ' . implode(',', $prefix_tags) ;
				break;
			case self::CACHECTRL_PURGESINGLE:
				$cache_tags = $cache_tags[0];
				// fall through
			case self::CACHECTRL_PURGE:
				$cache_control_header =
					LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL . ': no-cache'
					. $esi_hdr;
				LiteSpeed_Cache_Tags::add_purge_tag($cache_tags);
				break;

		}
		$purge_headers = $this->build_purge_headers();
		$this->header_out($showhdr, $cache_control_header, $purge_headers,
				$cache_tag_header);
	}

	/**
	  * Callback function that applies a prefix to cache/purge tags.
	  *
	  * The first call to this method will build the prefix. Subsequent calls
	  * will use the already set prefix.
	  *
	  * @since 1.0.9
	  * @access private
	  * @staticvar string $prefix The prefix to use for each tag.
	  * @param string $tag The tag to prefix.
	  * @return string The amended tag.
	  */
	private function prefix_apply($tag)
	{
		static $prefix = null;
		if (is_null($prefix)) {
			$prefix = $this->config->get_option(
				LiteSpeed_Cache_Config::OPID_TAG_PREFIX);
			if (empty($prefix)) {
				$prefix = '';
			}
			$prefix .= 'B' . get_current_blog_id() . '_';
		}
		return $prefix . $tag;
	}

	/**
	 * Gets the cache tags to set for the page.
	 *
	 * This includes site wide post types (e.g. front page) as well as
	 * any third party plugin specific cache tags.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array The list of cache tags to set.
	 */
	private function get_cache_tags()
	{
		global $post ;
		global $wp_query ;

		$queried_obj = get_queried_object() ;
		$queried_obj_id = get_queried_object_id() ;
		$cache_tags = array();

		$hash = md5($_SERVER['REQUEST_URI']);

		$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_URL . $hash;

		if ( is_front_page() ) {
			$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_FRONTPAGE ;
		}
		elseif ( is_home() ) {
			$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_HOME ;
		}

		if ( is_archive() ) {
			//An Archive is a Category, Tag, Author, Date, Custom Post Type or Custom Taxonomy based pages.

			if ( is_category() || is_tag() || is_tax() ) {
				$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM . $queried_obj_id ;
			}
			elseif ( is_post_type_archive() ) {
				$post_type = $wp_query->get('post_type') ;
				$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_POSTTYPE . $post_type ;
			}
			elseif ( is_author() ) {
				$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_AUTHOR . $queried_obj_id ;
			}
			elseif ( is_date() ) {
				$date = $post->post_date ;
				$date = strtotime($date) ;
				if ( is_day() ) {
					$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_DATE . date('Ymd', $date) ;
				}
				elseif ( is_month() ) {
					$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_DATE . date('Ym', $date) ;
				}
				elseif ( is_year() ) {
					$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_DATE . date('Y', $date) ;
				}
			}
		}
		elseif ( is_singular() ) {
			//$this->is_singular = $this->is_single || $this->is_page || $this->is_attachment;
			$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_POST . $queried_obj_id ;
		}
		elseif ( is_feed() ) {
			$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_FEED;
		}

		return array_merge($cache_tags, LiteSpeed_Cache_Tags::get_cache_tags());
	}

	/**
	 * Gets all the purge tags correlated with the post about to be purged.
	 *
	 * If the purge all pages configuration is set, all pages will be purged.
	 *
	 * This includes site wide post types (e.g. front page) as well as
	 * any third party plugin specific post tags.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param integer $post_id The id of the post about to be purged.
	 * @return array The list of purge tags correlated with the post.
	 */
	private function get_purge_tags( $post_id )
	{
		// If this is a valid post we want to purge the post, the home page and any associated tags & cats
		// If not, purge everything on the site.

		$purge_tags = array() ;
		$config = $this->config() ;

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_ALL_PAGES) ) {
			// ignore the rest if purge all
			return array( '*' ) ;
		}

		do_action('litespeed_cache_on_purge_post', $post_id);

		// post
		$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_POST . $post_id ;

		// for archive of categories|tags|custom tax
		$post = get_post($post_id) ;
		$post_type = $post->post_type ;

		global $wp_widget_factory;
		$recent_posts = $wp_widget_factory->widgets['WP_Widget_Recent_Posts'];
		if (!is_null($recent_posts)) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_WIDGET
				. $recent_posts->id;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_TERM) ) {
			$taxonomies = get_object_taxonomies($post_type) ;
			//$this->debug_log('purge by post, check tax = ' . print_r($taxonomies, true)) ;
			foreach ( $taxonomies as $tax ) {
				$terms = get_the_terms($post_id, $tax) ;
				if ( ! empty($terms) ) {
					foreach ( $terms as $term ) {
						$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM . $term->term_id ;
					}
				}
			}
		}

		if ($config->get_option(LiteSpeed_Cache_Config::OPID_FEED_TTL) > 0) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_FEED;
		}

		// author, for author posts and feed list
		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_AUTHOR) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_AUTHOR . get_post_field('post_author', $post_id) ;
		}

		// archive and feed of post type
		// todo: check if type contains space
		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_POST_TYPE) ) {
			if ( get_post_type_archive_link($post_type) ) {
				$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_POSTTYPE . $post_type ;
			}
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_FRONT_PAGE) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_FRONTPAGE ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_HOME_PAGE) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_HOME ;
		}

		// if configured to have archived by date
		$date = $post->post_date ;
		$date = strtotime($date) ;

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_DATE) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_DATE . date('Ymd', $date) ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_MONTH) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_DATE . date('Ym', $date) ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_YEAR) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_ARCHIVE_DATE . date('Y', $date) ;
		}

		return array_unique($purge_tags) ;
	}

	private static function format_report_section($section_header, $section)
	{
		$tab = '    '; // four spaces
		$nl = "\n";

		if (empty($section)) {
			return 'No matching ' . $section_header . $nl . $nl;
		}
		$buf = $section_header;
		foreach ($section as $key=>$val) {
			$buf .= $nl . $tab;
			if (!is_numeric($key)) {
				$buf .= $key . ' = ';
			}
			if (!is_string($val)) {
				$buf .= print_r($val, true);
			}
			else {
				$buf .= $val;
			}
		}
		return $buf . $nl . $nl;
	}

	/**
	 *
	 * @param boolean $html - Whether to use html separators or regular string separators
	 * @param array $server - server variables
	 * @param array $options - cms options
	 * @param array $extras - cms specific attributes
	 * @param array $htaccess_paths - htaccess paths to check.
	 */
	public static function build_environment_report($server, $options,
		$extras = array(), $htaccess_paths = array())
	{
		$server_keys = array(
			'DOCUMENT_ROOT'=>'',
			'SERVER_SOFTWARE'=>'',
			'X-LSCACHE'=>''
		);
		$server_vars = array_intersect_key($server, $server_keys);
		$buf = self::format_report_section('Server Variables', $server_vars);

		$buf .= self::format_report_section('LSCache Plugin Options',
			$options);

		$buf .= self::format_report_section('Wordpress Specific Extras',
			$extras);

		if (empty($htaccess_paths)) {
			return $buf;
		}

		foreach ($htaccess_paths as $path) {
			if ((!file_exists($path)) || (!is_readable($path))) {
				$buf .= $path . " does not exist or is not readable.\n";
				continue;
			}
			$content = file_get_contents($path);
			if ($content === false) {
				$buf .= $path . " returned false for file_get_contents.\n";
				continue;
			}
			$buf .= $path . " contents:\n" . $content . "\n\n";
		}
		return $buf;
	}

	public function write_environment_report($content)
	{
		$ret = LiteSpeed_Cache_Admin_Rules::file_save($content, false,
			$this->plugin_dir . '../environment_report.txt', false);
		if (($ret !== true) && (defined('LSCWP_LOG'))) {
			self::debug_log('LSCache wordpress plugin attempted to write '
				. 'env report but did not have permissions.');
		}
	}

	public static function generate_environment_report($options = null)
	{
		global $wp_version, $_SERVER;
		$home = LiteSpeed_Cache_Admin_Rules::get_home_path();
		$site = LiteSpeed_Cache_Admin_Rules::get_site_path();
		$paths = array($home);
		if ($home != $site) {
			$paths[] = $site;
		}

		$active_plugins = get_option('active_plugins');
		if (function_exists('wp_get_theme')) {
			$theme_obj = wp_get_theme();
			$active_theme = $theme_obj->get('Name');
		}
		else {
			$active_theme = get_current_theme();
		}

		$extras = array(
			'wordpress version' => $wp_version,
			'locale' => get_locale(),
			'active theme' => $active_theme,
			'active plugins' => $active_plugins,

		);
		if (is_null($options)) {
			$options = self::config()->get_options();
		}

		$report = self::build_environment_report($_SERVER, $options, $extras,
			$paths);
		self::plugin()->write_environment_report($report);
		return $report;
	}

	public static function update_environment_report($unused, $options)
	{
		self::generate_environment_report($options);
	}



/* BEGIN ESI CODE, not fully implemented for now */

	/**
	 * Check if the requested page has esi elements. If so, return esi on
	 * header.
	 *
	 * @access private
	 * @since 1.1.0
	 * @return string Esi On header if request has esi, empty string otherwise.
	 */
	private function esi_send()
	{
		if ($this->has_esi) {
			return ',esi=on';
		}
		return '';
	}

/*END ESI CODE*/
}
