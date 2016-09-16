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

	const PLUGIN_NAME = 'litespeed-cache' ;
	const PLUGIN_VERSION = '1.0.10' ;

	const LSCOOKIE_VARY_NAME = 'LSCACHE_VARY_COOKIE' ;
	const LSCOOKIE_DEFAULT_VARY = '_lscache_vary' ;
	const LSCOOKIE_VARY_LOGGED_IN = 1;
	const LSCOOKIE_VARY_COMMENTER = 2;

	const ADMINQS_KEY = 'LSCWP_CTRL';
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

	const ESI_URL = '/lscacheesi/';
	const ESI_POSTTYPE = 'lscacheesi';

	const ESI_PARAM_ACTION = 'action=lscache';
	const ESI_PARAM_PARAMS = 'lscache';
	const ESI_PARAM_TYPE = 'type';
	const ESI_PARAM_NAME = 'name';
	const ESI_PARAM_ID = 'id';
	const ESI_PARAM_INSTANCE = 'instance';
	const ESI_PARAM_ARGS = 'args';

	const ESI_TYPE_WIDGET = 1;
	const ESI_TYPE_ADMINBAR = 2;
	const ESI_TYPE_COMMENTFORM = 3;
	const ESI_TYPE_COMMENT = 4;
	const ESI_TYPE_WC_CART_FORM = 5;

	const ESI_CACHECTRL_PRIV = 'no-vary,private';

	protected $plugin_dir ;
	protected $config ;
	protected $current_vary;
	protected $cachectrl = self::CACHECTRL_NOCACHE;
	protected $pub_purge_tags = array();
	protected $has_esi = false;
	protected $custom_ttl = 0;
	protected $user_status = 0;

	private $esi_args = null;

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
		include_once $cur_dir . '/class-litespeed-cache-tags.php';
		// Load third party detection.
		include_once $cur_dir . '/../thirdparty/litespeed-cache-thirdparty-registry.php';

		$this->config = new LiteSpeed_Cache_Config() ;
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
		$tp_keys = $conf->get_thirdparty_options();
		if ((isset($tp_keys[$opt_id]))
			|| (array_key_exists($opt_id, $tp_keys))) {
			return $conf->get_option($opt_id);
		}
		return NULL;
	}

	/**
	 * Logs a debug message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $mesg The debug message.
	 * @param string $log_level Optional. The log level of the message.
	 */
	public function debug_log( $mesg, $log_level = LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG )
	{
		if ( true === WP_DEBUG ) {
			$this->config->debug_log($mesg, $log_level) ;
		}
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
		if ((!file_exists(ABSPATH . 'wp-content/advanced-cache.php'))
			|| (filesize(ABSPATH . 'wp-content/advanced-cache.php') === 0)
				&& (is_writable(ABSPATH . 'wp-content/advanced-cache.php'))) {
			copy($this->plugin_dir . '/includes/advanced-cache.php', ABSPATH . 'wp-content/advanced-cache.php') ;
		}
		include_once(ABSPATH . 'wp-content/advanced-cache.php');
		if ( !defined('LSCACHE_ADV_CACHE')) {
			exit(__("advanced-cache.php detected in wp-content directory! Please disable or uninstall any other cache plugins before enabling LiteSpeed Cache.", 'litespeed-cache')) ;
		}
		LiteSpeed_Cache_Config::wp_cache_var_setter(true);

		require_once $this->plugin_dir . '/admin/class-litespeed-cache-admin-rules.php';
		$this->config->plugin_activation();
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
		require_once $this->plugin_dir . '/admin/class-litespeed-cache-admin-rules.php';
		LiteSpeed_Cache_Admin_Rules::clear_rules();
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

		if ((is_admin()) && (current_user_can('administrator'))) {
			$this->load_admin_actions($module_enabled) ;
		}
		else {
			$this->load_nonadmin_actions($module_enabled) ;
		}

		if ( ! $module_enabled ) {
			return ;
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

		define('LITESPEED_CACHE_ENABLED', true);
		ob_start();

		$this->setup_cookies();

		if ( $this->check_user_logged_in() || $this->check_cookies() ) {
			$this->load_logged_in_actions() ;
		}
		else {
			$this->load_logged_out_actions();
		}

		$this->load_public_actions($is_ajax) ;

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

			add_action('wp_before_admin_bar_render',
				array($admin, 'add_quick_purge'));
		}

		add_action('in_widget_form',
			array(LiteSpeed_Cache_Admin_Display::get_instance(),
				'show_widget_edit'), 100, 3);
		add_filter('widget_update_callback',
			array($admin, 'validate_widget_save'), 10, 4);

		if (!is_network_admin()) {
			add_action('load-litespeed-cache_page_lscache-settings',
					'LiteSpeed_Cache_Admin::redir_settings');
		}
		add_action('load-litespeed-cache_page_lscache-edit-htaccess',
				array(LiteSpeed_Cache_Admin_Rules::get_instance(),
					'htaccess_editor_save'));
		add_action('load-litespeed-cache_page_lscache-settings',
				array($admin, 'parse_settings'));
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

		if ($is_ajax) {
			do_action('litespeed_cache_detect_thirdparty');
		}

		// The ESI functionality is an enterprise feature.
		// Removing the openlitespeed check will simply break the page.
		if (!is_openlitespeed()) {
			$this->load_esi_actions($is_ajax);
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
	 * Register all of the hooks related to the esi logic of the plugin.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @param boolean $is_ajax Denotes if the request is an ajax request.
	 */
	private function load_esi_actions($is_ajax)
	{
		add_action('load-widgets.php', array( $this, 'purge_widget'));
		add_action('wp_update_comment_count',
			array($this, 'purge_comment_widget'));

		if ($is_ajax) {
			return;
		}
		add_action('init', array($this, 'register_post_type'));
		add_action('template_include', array($this, 'esi_template'), 100);
		add_action('wp', array($this, 'detect'), 4);
		add_filter('widget_display_callback',
			array($this, 'esi_widget'), 0, 3);

		if ($this->user_status & self::LSCOOKIE_VARY_LOGGED_IN) {
			remove_action('wp_footer', 'wp_admin_bar_render', 1000);
			add_action( 'wp_footer', array($this, 'esi_admin_bar'), 1000 );
		}

		if ($this->user_status) {
			add_filter('comment_form_defaults',
				array($this, 'esi_comment_form_check'));
		}
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
				__('Failed to purge by category, does not exist: ', 'litespeed-cache') . $val);
			return;
		}

		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				__('Purge category ', 'litespeed-cache') . $val);

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
				__('Failed to purge by Post ID, given ID is not numeric: ', 'litespeed-cache') . $val);
			return;
		}
		elseif (get_post_status($val) !== 'publish') {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				__('Failed to purge by Post ID, given ID does not exist or is not published: ',
						'litespeed-cache') . $val);
			return;
		}
                elseif ($this->config->purge_by_post(LiteSpeed_Cache_Config::PURGE_ALL_PAGES))
                {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				sprintf(__('Failed to purge by Post ID, Auto Purge All pages on update is enabled. Please use the Purge All button on the LiteSpeed Cache Management screen or navigate to the post you wish to purge and add %s to the url.', 'litespeed-cache'), '?LSCWP_CTRL=PURGESINGLE'));
			return;
                }
		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				__('Purge Post ID ', 'litespeed-cache') . $val);

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
				__('Failed to purge by tag, does not exist: ', 'litespeed-cache') . $val);
			return;
		}

		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				__('Purge tag ', 'litespeed-cache') . $val);

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
		$id = url_to_postid($val);
		if ($id == 0) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				__('Failed to purge by url, does not exist: ', 'litespeed-cache') . $val);
			return;
		}

		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				__('Purge url ', 'litespeed-cache') . $val);

		LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_POST . $id);
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
		$err = __('NOTICE: Database login cookie did not match your login cookie.', 'litespeed-cache')
		. __(' If you just changed the cookie in the settings, please log out and back in.', 'litespeed-cache')
		. __(" If not, please verify your LiteSpeed Cache setting's Advanced tab.", 'litespeed-cache');
		if (is_openlitespeed()) {
			$err .= __(' If using OpenLiteSpeed, you may need to restart the server for the changes to take effect.', 'litespeed-cache');
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
			$this->debug_log(
				__('NOTICE: Database login cookie does not match the cookie used to access the page.', 'litespeed-cache')
				. __(' Please have the admin check the LiteSpeed Cache settings.', 'litespeed-cache')
				. __(' This error may appear if you are logged into another web application.', 'litespeed-cache'));
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

		if ( is_404() ) {
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
			return $this->no_cache_for('Third Party Plugin determined not cacheable.');
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
	 * @access private
	 * @param string $reason An explanation for why the page is not cacheable.
	 * @return boolean Return false.
	 */
	private function no_cache_for( $reason )
	{
		$this->debug_log('Do not cache - ' . $reason) ;
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
				|| (wp_verify_nonce($_GET[ '_wpnonce' ], 'litespeed-purgeall') === false)) {
				return;
			}
		}
		else {
			$ips = $this->config->get_option(LiteSpeed_Cache_Config::OPID_ADMIN_IPS);

			if (strpos($ips, $_SERVER['REMOTE_ADDR']) === false) {
				$this->cachectrl = self::CACHECTRL_NOCACHE;
				return;
			}
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
				wp_redirect(admin_url($pagenow . $qs));
				exit();
			case 'S':
				if ($action == self::ADMINQS_SHOWHEADERS) {
					$this->set_cachectrl($this->cachectrl
						| self::CACHECTRL_SHOWHEADERS);
					return;
				}
				break;
			default:
				break;
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
				error_log('blog list is empty');
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

		if (empty($hdr_content)) {
			return;
		}

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
		do_action('litespeed_cache_add_purge_tags');

		$mode = $this->validate_mode($showhdr);

		if ($mode != self::CACHECTRL_NOCACHE) {
			do_action('litespeed_cache_add_cache_tags');
			$cache_tags = $this->get_cache_tags();
			if ($mode === self::CACHECTRL_PUBLIC) {
				$cache_tags[] = ''; //add blank entry to add blog tag.
			}
		}

		if (empty($cache_tags)) {
if (defined('lscache_debug')) {
error_log('do not cache page.');
}
			$cache_control_header = LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL
					. ': no-cache' . $this->esi_send();
			$purge_headers = $this->build_purge_headers();
			$this->header_out($showhdr, $cache_control_header, $purge_headers);
			return;
		}
		$prefix_tags = array_map(array($this,'prefix_apply'), $cache_tags);

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
				else {
					$ttl = $this->config->get_option(LiteSpeed_Cache_Config::OPID_PUBLIC_TTL) ;
				}
				$cache_control_header = LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL
						. ': ' . $cachectrl_val . ',max-age=' . $ttl . $this->esi_send();
				$cache_tag_header = LiteSpeed_Cache_Tags::HEADER_CACHE_TAG
					. ': ' . implode(',', $prefix_tags) ;
				break;
			case self::CACHECTRL_PURGESINGLE:
				$cache_tags = $cache_tags[0];
				// fall through
			case self::CACHECTRL_PURGE:
				$cache_control_header =
					LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL . ': no-cache'
					. $this->esi_send();
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

	/**
	 * Build the esi url. This method will build the html comment wrapper
	 * as well as serialize and encode the parameter array.
	 *
	 * If echo is false *HAS_ESI WILL NOT BE SET TO TRUE*!
	 *
	 * @access private
	 * @since 1.1.0
	 * @param array $params The esi parameters.
	 * @param string $wrapper The wrapper for the esi comments.
	 * @param string $cachectrl The cache control attribute if any.
	 * @param boolean $echo Whether to echo the output or return it.
	 * @return mixed Nothing if echo is true, the output otherwise.
	 */
	public function esi_build_url($params, $wrapper, $cachectrl = '', $echo = true)
	{
		$qs = '';
		if (!empty($params)) {
			$qs = '?' . self::ESI_PARAM_ACTION . '&' . self::ESI_PARAM_PARAMS
				. '=' . urlencode(base64_encode(serialize($params)));
		}
		$url = self::ESI_URL . $qs;
		$output = '<!-- lscwp ' . $wrapper . ' -->'
			. '<esi:include src="' . $url . '"';
		if (!empty($cachectrl)) {
			$output .= ' cache-control="' . $cachectrl . '"';
		}
		$output .= ' />'
			. '<!-- lscwp ' . $wrapper . ' esi end -->';
		if ($echo == false) {
			return $output;
		}
		echo $output;
		$this->has_esi = true;
	}

	/**
	 * Parses the request parameters on an ESI request and selects the correct
	 * esi output based on the parameters.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public static function esi_get()
	{
		if (!isset($_REQUEST[self::ESI_PARAM_PARAMS])) {
			return;
		}
		$cache = self::plugin();
		$req_params = $_REQUEST[self::ESI_PARAM_PARAMS];
		$unencrypted = base64_decode($req_params);
		if ($unencrypted === false) {
			return;
		}
		$unencoded = urldecode($unencrypted);
		$params = unserialize($unencoded);
		if ($params === false) {
			return;
		}

if (defined('lscache_debug')) {
error_log('Got an esi request. Type: ' . $params[self::ESI_PARAM_TYPE]);
}

		switch ($params[self::ESI_PARAM_TYPE]) {
			case self::ESI_TYPE_WIDGET:
				$cache->esi_widget_get($params);
				break;
			case self::ESI_TYPE_ADMINBAR:
				wp_admin_bar_render();
				$cache->set_cachectrl(self::CACHECTRL_PRIVATE);
				break;
			case self::ESI_TYPE_COMMENTFORM:
				remove_filter('comment_form_defaults',
					array($cache, 'esi_comment_form_check'));
				comment_form($params[self::ESI_PARAM_ARGS],
					$params[self::ESI_PARAM_ID]);
				$cache->set_cachectrl(self::CACHECTRL_PRIVATE);
				break;
			case self::ESI_TYPE_COMMENT:
				$cache->esi_comments_get($params);
				break;
			case self::ESI_TYPE_WC_CART_FORM:
				global $post, $wp_query;
				$post = get_post($params[self::ESI_PARAM_ID]);
				$wp_query->setup_postdata($post);
				wc_get_template(
					$params[self::ESI_PARAM_NAME], $params['path'],
					$params[self::ESI_PARAM_INSTANCE], $params[self::ESI_PARAM_ARGS]);
				break;
			default:
				break;
		}
	}

	/**
	 * Get the configuration option for the current widget.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param WP_Widget $widget The widget to get the options for.
	 * @return mixed null if not found, an array of the options otherwise.
	 */
	public static function esi_widget_get_option($widget)
	{
		if ($widget->updated) {
			$settings = get_option($widget->option_name);
		}
		else {
			$settings = $widget->get_settings();
		}

		if (!isset($settings)) {
			return null;
		}

		$instance = $settings[$widget->number];

		if (!isset($instance)) {
			return null;
		}

		return $instance[LiteSpeed_Cache_Config::OPTION_NAME];
	}

	/**
	 * Parses the esi input parameters and generates the widget for esi display.
	 *
	 * @access private
	 * @since 1.1.0
	 * @param array $params Input parameters needed to correctly display widget
	 */
	public function esi_widget_get($params)
	{
		global $wp_widget_factory;
		$widget = $wp_widget_factory->widgets[$params[self::ESI_PARAM_NAME]];
		$option = self::esi_widget_get_option($widget);
		// Since we only reach here via esi, safe to assume setting exists.
		$ttl = $option[LiteSpeed_Cache_Config::WIDGET_OPID_TTL];
if (defined('lscache_debug')) {
error_log('Esi widget render: name ' . $params[self::ESI_PARAM_NAME]
	. ', id ' . $params[self::ESI_PARAM_ID] . ', ttl ' . $ttl);
}
		if ($ttl == 0) {
			$this->no_cache_for(__('Widget time to live set to 0.',
				'litespeed-cache'));
			LiteSpeed_Cache_Tags::set_noncacheable();
		}
		else {
			$this->custom_ttl = $ttl;
			LiteSpeed_Cache_Tags::add_cache_tag(
				LiteSpeed_Cache_Tags::TYPE_WIDGET . $params[self::ESI_PARAM_ID]);
		}
		the_widget($params[self::ESI_PARAM_NAME],
			$params[self::ESI_PARAM_INSTANCE], $params[self::ESI_PARAM_ARGS]);
	}

	/**
	 * Hooked to the widget_display_callback filter.
	 * If the admin configured the widget to display via esi, this function
	 * will set up the esi request and cancel the widget display.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param array $instance Parameter used to build the widget.
	 * @param WP_Widget $widget The widget to build.
	 * @param array $args Parameter used to build the widget.
	 * @return mixed Return false if display through esi, instance otherwise.
	 */
	public function esi_widget(array $instance, WP_Widget $widget, array $args)
	{
		$name = get_class($widget);
		$options = $instance[LiteSpeed_Cache_Config::OPTION_NAME];
		if ((!isset($options)) ||
			($options[LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE]
				== LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE)) {
if (defined('lscache_debug')) {
error_log('Do not esi widget ' . $name . ' because '
	. ((!isset($options)) ? 'options not set' : 'esi disabled for widget'));
}
			return $instance;
		}
		$params = array(
			self::ESI_PARAM_TYPE => self::ESI_TYPE_WIDGET,
			self::ESI_PARAM_NAME => $name,
			self::ESI_PARAM_ID => $widget->id,
			self::ESI_PARAM_INSTANCE => $instance,
			self::ESI_PARAM_ARGS => $args
		);

		$this->esi_build_url($params, 'widget ' . $name);
		return false;
	}

	/**
	 * Hooked to the wp_footer action.
	 * Sets up the ESI request for the admin bar.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $wp_admin_bar
	 */
	public function esi_admin_bar()
	{
		global $wp_admin_bar;

		if ((!is_admin_bar_showing()) || (!is_object($wp_admin_bar))) {
			return;
		}

		$params = array(self::ESI_PARAM_TYPE => self::ESI_TYPE_ADMINBAR);

		$this->esi_build_url($params, 'adminbar', self::ESI_CACHECTRL_PRIV);
	}

	/**
	 * Hooked to the template_include action.
	 * Selects the esi template file when the post type is a LiteSpeed ESI page.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $post_type
	 * @param string $template The template path filtered.
	 * @return string The new template path.
	 */
	public function esi_template($template)
	{
		global $post_type;
		if ($post_type == self::ESI_POSTTYPE) {
			return $this->plugin_dir . 'includes/litespeed-cache-esi.php';
		}
		else {
			add_filter('comments_array', array($this, 'esi_comments'));
		}
		return $template;
	}

	/**
	 * Hooked to the init action.
	 * Registers the LiteSpeed ESI post type.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function register_post_type()
	{
		register_post_type(
			self::ESI_POSTTYPE,
			array(
				'labels' => array(
					'name' => __('Lscacheesi', 'litespeed-cache')
				),
				'description' => __('Description of post type', 'litespeed-cache'),
				'public' => false,
				'publicly_queryable' => true,
				'supports' => false,
				'rewrite' => array('slug' => 'lscacheesi'),
				'query_var' => true
			)
		);
		add_rewrite_rule('lscacheesi/?',
			'index.php?post_type=lscacheesi', 'top');
	}

	/**
	 * Hooked to the comment_form_defaults filter.
	 * Stores the default comment form settings.
	 * This method initializes an output buffer and adds two hook functions
	 * to the WP process.
	 * If esi_comment_form_cancel is triggered, the output buffer is flushed
	 * because there is no need to make the comment form ESI.
	 * Else if esi_comment_form is triggered, the output buffer is cleared
	 * and an esi block is added. The remaining comment form is also buffered
	 * and cleared.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param array $defaults The default comment form settings.
	 * @return array The default comment form settings.
	 */
	public function esi_comment_form_check($defaults)
	{
		$this->esi_args = $defaults;
		ob_start();
		add_action('comment_form_must_log_in_after',
			array($this, 'esi_comment_form_cancel'));
		add_action('comment_form_comments_closed',
			array($this, 'esi_comment_form_cancel'));
		add_filter('comment_form_submit_button',
			array($this, 'esi_comment_form'), 1000, 2);
		return $defaults;
	}

	/**
	 * Hooked to the comment_form_must_log_in_after and
	 * comment_form_comments_closed actions.
	 * @see esi_comment_form_check
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function esi_comment_form_cancel()
	{
		ob_flush();
	}

	/**
	 * Hooked to the comment_form_submit_button filter.
	 * @see esi_comment_form_check
	 * This method will compare the used comment form args against the default
	 * args. The difference will be passed to the esi request.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $post
	 * @param $unused
	 * @param array $args The used comment form args.
	 * @return unused.
	 */
	public function esi_comment_form($unused, $args)
	{
		if (empty($args) || empty($this->esi_args)) {
			error_log('comment form args empty?');
			return $unused;
		}
		$esi_args = array_diff_assoc($args, $this->esi_args);
		ob_clean();
		global $post;
		$params = array(
			self::ESI_PARAM_TYPE => self::ESI_TYPE_COMMENTFORM,
			self::ESI_PARAM_ID => $post->ID,
			self::ESI_PARAM_ARGS => $esi_args,
			);

		$this->esi_build_url($params, 'comment form', self::ESI_CACHECTRL_PRIV);
		ob_start();
		add_action('comment_form_after',
			array($this, 'esi_comment_form_clean'));
		return $unused;
	}

	/**
	 * Hooked to the comment_form_after action.
	 * Cleans up the remaining comment form output.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function esi_comment_form_clean()
	{
		ob_clean();
	}

	/**
	 * Hooked to the comments_array filter.
	 * If there are pending comments, the whole comments section should be an
	 * ESI block.
	 * Else the comments do not need to be ESI.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $post
	 * @param type $comments The current comments to output
	 * @return array The comments to output.
	 */
	public function esi_comments($comments)
	{
		global $post;
		$args = array(
			'status' => 'hold',
			'number' => '1',
			'post_id' => $post->ID,
		);

		$on_hold = get_comments($args);

		if (empty($on_hold)) {
			// No comments on hold, comments section can be skipped
			return $comments;
		}
		// Else need to ESI comments.

		$params = array(
			self::ESI_PARAM_TYPE => self::ESI_TYPE_COMMENT,
			self::ESI_PARAM_ID => $post->ID,
			self::ESI_PARAM_ARGS => get_query_var( 'cpage' ),
		);
		$this->esi_build_url($params, 'comments', self::ESI_CACHECTRL_PRIV);
		add_filter('comments_template',
			array($this, 'esi_comments_dummy_template'), 1000);
		return array();
	}

	/**
	 * Hooked to the comments_template filter.
	 * Loads a dummy comments template file so that no extra processing is done.
	 * This will only be used if the comments section are to be displayed
	 * via ESI.
	 *
	 * @access public
	 * @since 1.1.0
	 * @return string Dummy template file.
	 */
	public function esi_comments_dummy_template()
	{
		return $this->plugin_dir .
			'includes/litespeed-cache-esi-dummy-template.php';
	}

	public function esi_comments_cache_type($comments)
	{
		if (empty($comments)) {
			$this->set_cachectrl(self::CACHECTRL_SHARED);
			return $comments;
		}

		foreach ($comments as $comment) {
			if (!$comment->comment_approved) {
				$this->set_cachectrl(self::CACHECTRL_PRIVATE);
				return $comments;
			}
		}
		$this->set_cachectrl(self::CACHECTRL_SHARED);
		return $comments;
	}

	/**
	 * Outputs the ESI comments block.
	 *
	 * @access private
	 * @since 1.1.0
	 * @global type $post
	 * @global type $wp_query
	 * @param array $params The parameters used to help display the comments.
	 */
	private function esi_comments_get($params)
	{
		global $post, $wp_query;
		$wp_query->is_singular = true;
		$wp_query->is_single = true;
		if (!empty($params[self::ESI_PARAM_ARGS])) {
			$wp_query->set('cpage', $params[self::ESI_PARAM_ARGS]);
		}
		$post = get_post($params[self::ESI_PARAM_ID]);
		$wp_query->setup_postdata($post);
		add_filter('comments_array', array($this, 'esi_comments_cache_type'));
		comments_template();
	}

/*END ESI CODE*/
}
