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
	const PLUGIN_VERSION = '1.0.7' ;

	const LSCOOKIE_VARY_NAME = 'LSCACHE_VARY_COOKIE' ;
	const LSCOOKIE_DEFAULT_VARY = '_lscache_vary' ;
	const LSCOOKIE_VARY_LOGGED_IN = 1;
	const LSCOOKIE_VARY_COMMENTER = 2;

	const ADMINQS_KEY = 'LSCWP_CTRL';
	const ADMINQS_PURGE = 'PURGE';
	const ADMINQS_PURGESINGLE = 'PURGESINGLE';
	const ADMINQS_SHOWHEADERS = 'SHOWHEADERS';

	const CACHECTRL_NOCACHE = 0;
	const CACHECTRL_CACHE = 1;
	const CACHECTRL_PURGE = 2;
	const CACHECTRL_PURGESINGLE = 3;

	const CACHECTRL_SHOWHEADERS = 128; // (1<<7)

	protected $plugin_dir ;
	protected $config ;
	protected $current_vary;
	protected $cachectrl = self::CACHECTRL_NOCACHE;
	protected $pub_purge_tags = array();

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
	 * @return LiteSpeed_Cache_Config The configurations for the accessed page.
	 */
	public static function config()
	{
		return self::$instance->config ;
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
		if ( (!file_exists(ABSPATH . 'wp-content/advanced-cache.php'))
			|| (filesize(ABSPATH . 'wp-content/advanced-cache.php') === 0) ) {
			copy($this->plugin_dir . '/includes/advanced-cache.php', ABSPATH . 'wp-content/advanced-cache.php') ;
			$this->config->wp_cache_var_setter(true) ;
			$this->config->plugin_activation() ;
		}
		elseif ( !defined('LSCACHE_ADV_CACHE')) {
			exit(__("advanced-cache.php detected in wp-content directory! Please disable or uninstall any other cache plugins before enabling LiteSpeed Cache.", 'litespeed-cache')) ;
		}
		if (is_multisite()) {
			$this->config->incr_multi_enabled();
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
		$this->purge_all() ;
		if (is_multisite()) {
			$count = $this->config->decr_multi_enabled();
			if ($count) {
				$this->config->plugin_deactivation() ;
				return;
			}
		}
		$adv_cache_path = ABSPATH . 'wp-content/advanced-cache.php';
		if (file_exists($adv_cache_path)) {
			unlink($adv_cache_path) ;
		}

		if ( ! $this->config->wp_cache_var_setter(false) ) {
			$this->config->debug_log('In wp-config.php: WP_CACHE could not be set to false during deactivation!') ;
		}

		$this->config->plugin_deactivation() ;
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
		$module_enabled = $this->config->is_plugin_enabled();

		if ( is_admin() ) {
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
			&& ((!defined('WP_CACHE')) || (defined('WP_CACHE') && constant('WP_CACHE') == false))) {
			add_action('admin_notices', 'LiteSpeed_Cache::show_wp_cache_var_set_error') ;
		}

		define('LITESPEED_CACHE_ENABLED', true);
		ob_start();
		//TODO: Uncomment this when esi is implemented.
//		add_action('init', array($this, 'check_admin_bar'), 0);
//		$this->add_actions_esi();

		$this->setup_cookies();

		if ( $this->check_esi_page()) {
			return;
		}

		if ( is_user_logged_in() || $this->check_cookies() ) {
			$this->load_logged_in_actions() ;
		}
		else {
			$this->load_logged_out_actions();
		}

		$this->load_public_actions() ;
		if (defined('DOING_AJAX') && DOING_AJAX) {
			do_action('litespeed_cache_detect_thirdparty');
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
		}

		if (!is_network_admin()) {
			add_action('load-litespeed-cache_page_lscache-settings',
					'LiteSpeed_Cache_Admin::redir_settings');
		}
		add_action('load-litespeed-cache_page_lscache-edit-htaccess',
				array(LiteSpeed_Cache_Admin_Rules::get_instance(), 'parse_edit_htaccess'));
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
			add_filter('query_vars', array($this, 'query_vars'));
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
		) ;
		foreach ( $purge_post_events as $event ) {
			// this will purge all related tags
			add_action($event, array( $this, 'purge_post' ), 10, 2) ;
		}

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
		$curval = intval($_COOKIE[$this->current_vary]);

		// not, remove from curval.
		if ($update_val < 0) {
			// If cookie will no longer exist, delete the cookie.
			if (($curval == 0) || ($curval == $update_val)) {
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
	 *
	 *
	 * @since 1.0.1
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
		if (is_multisite() && (!is_network_admin())) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_BLOG . get_current_blog_id());
		}
		else {
			$this->add_purge_tags('*');
		}
//		$this->send_purge_headers();
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
//		$this->send_purge_headers();
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
		elseif (get_post_status($val) !== 'published') {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				__('Failed to purge by Post ID, given ID does not exist or is not published: ',
						'litespeed-cache') . $val);
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
		$conf = $_POST[LiteSpeed_Cache_Config::OPTION_NAME];
		if (is_null($conf)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
					LiteSpeed_Cache_Admin_Display::NOTICE_RED,
					__('ERROR: Something went wrong with the form! Please try again.', 'litespeed-cache'));
			return;
		}
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
		if (!$this->config->get_option(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS))
		{
			// If do not cache commenters, check cookie for commenter value.
			return ((isset($_COOKIE[$this->current_vary]))
					&& ($_COOKIE[$this->current_vary] & self::LSCOOKIE_VARY_COMMENTER));
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

		if ( 'GET' !== $method ) {
			return $this->no_cache_for('not GET method') ;
		}

		if ( is_feed() ) {
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

		if ( ! WP_USE_THEMES ) {
			return $this->no_cache_for('no theme used') ;
		}

		$cacheable = apply_filters('litespeed_cache_is_cacheable', true);
		if (!$cacheable) {
			return $this->no_cache_for('Third Party Plugin determined not cacheable.');
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_URI);
		if (( ! empty($excludes))
			&& ( $this->is_uri_excluded(explode("\n", $excludes))))
		{
			return $this->no_cache_for('Admin configured URI Do not cache: '
					. $_SERVER['REQUEST_URI']);
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT);
		if (( ! empty($excludes))
			&& (has_category(explode(',', $excludes)))) {
			return $this->no_cache_for('Admin configured Category Do not cache.');
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG);
		if (( ! empty($excludes))
			&& (has_tag(explode(',', $excludes)))) {
			return $this->no_cache_for('Admin configured Tag Do not cache.');
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_COOKIE);
		if ( ! empty($excludes) && $_COOKIE) {
			$exclude_list = explode('|', $excludes);

			foreach( $_COOKIE as $key=>$val) {
				if (in_array($key, $exclude_list)) {
					return $this->no_cache_for('Admin configured Cookie Do not cache.');
				}
			}
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_USERAGENT);
		if ( ! empty($excludes) && $_SERVER['HTTP_USER_AGENT']) {
			$pattern = '/' . $excludes . '/';
			$nummatches = preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
			if ($nummatches) {
					return $this->no_cache_for('Admin configured User Agent Do not cache.');
			}
		}

		if ($this->config->get_option(LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED)) {
			if ($_SERVER['LSCACHE_VARY_VALUE'] === 'ismobile') {
				if (!wp_is_mobile()) {
					@header(LiteSpeed_Cache_Tags::HEADER_CACHE_VARY . ': value=');
				}
			}
			elseif (wp_is_mobile()) {
				@header(LiteSpeed_Cache_Tags::HEADER_CACHE_VARY . ': value=ismobile');
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
		if ((LiteSpeed_Cache_Tags::is_noncacheable() == false)
			&& ($this->is_cacheable())) {
			$this->cachectrl = self::CACHECTRL_CACHE;
		}
	}

	/**
	 * Adds admin IP query string key to query vars list.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param array $qvars Already added query vars.
	 * @return array Newly appended query vars.
	 */
	public function query_vars($qvars)
	{
		$qvars[] = self::ADMINQS_KEY;
		return $qvars;
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
		$action = get_query_var(self::ADMINQS_KEY);
		// Not set, ignore.
		if (empty($action)) {
			return;
		}
		$ips = $this->config->get_option(LiteSpeed_Cache_Config::OPID_ADMIN_IPS);

		if (strpos($ips, $_SERVER['REMOTE_ADDR']) === false) {
			$this->cachectrl = self::CACHECTRL_NOCACHE;
			return;
		}

		switch ($action[0]) {
			case 'P':
				if (strcmp($action, self::ADMINQS_PURGE) == 0) {
					$this->cachectrl = self::CACHECTRL_PURGE;
				}
				elseif (strcmp($action, self::ADMINQS_PURGESINGLE) == 0) {
					$this->cachectrl = self::CACHECTRL_PURGESINGLE;
				}
				else {
					break;
				}
				return;
			case 'S':
				if (strcmp($action, self::ADMINQS_SHOWHEADERS) == 0) {
					$this->cachectrl |= self::CACHECTRL_SHOWHEADERS;
					return;
				}
				break;
			default:
				break;
		}
		$this->cachectrl = self::CACHECTRL_NOCACHE;
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

		if (in_array('*', $purge_tags )) {
			$cache_purge_header .= ': *';
		}
		else {
			$cache_purge_header .= ': tag=' . implode(',', $purge_tags);
		}
		return $cache_purge_header;
		// TODO: private cache headers
//		$cache_purge_header = LiteSpeed_Cache_Tags::HEADER_PURGE
//				. ': private,tag=' . implode(',', $this->ext_purge_private_tags);
//		@header($cache_purge_header, false);
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
		$showhdr = false;
		do_action('litespeed_cache_add_purge_tags');
		if ($this->cachectrl & self::CACHECTRL_SHOWHEADERS) {
			$showhdr = true;
			$mode = $this->cachectrl & ~self::CACHECTRL_SHOWHEADERS;
		}
		else {
			$mode = $this->cachectrl;
		}

		if (($mode == self::CACHECTRL_CACHE)
				&& (LiteSpeed_Cache_Tags::is_noncacheable())) {
			$mode = self::CACHECTRL_NOCACHE;
		}

		if ($mode != self::CACHECTRL_NOCACHE) {
			do_action('litespeed_cache_add_cache_tags');
			$cache_tags = $this->get_cache_tags();
		}

		if ((is_null($cache_tags)) || (empty($cache_tags))) {
			$cache_control_header =
					LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL . ': no-cache' /*. ',esi=on'*/ ;
			$purge_headers = $this->build_purge_headers();
			$this->header_out($showhdr, $cache_control_header, $purge_headers);
			return;
		}

		switch ($mode) {
			case self::CACHECTRL_CACHE:
				if ( is_front_page() ){
					$ttl = $this->config->get_option(LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL);
				}
				else{
					$ttl = $this->config->get_option(LiteSpeed_Cache_Config::OPID_PUBLIC_TTL) ;
				}
				$cache_control_header = LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL
						. ': public,max-age=' . $ttl /*. ',esi=on'*/ ;
				$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_BLOG . get_current_blog_id();
				$cache_tag_header = LiteSpeed_Cache_Tags::HEADER_CACHE_TAG
					. ': ' . implode(',', $cache_tags) ;
				break;
			case self::CACHECTRL_PURGESINGLE:
				$cache_tags = $cache_tags[0];
				// fall through
			case self::CACHECTRL_PURGE:
				$cache_control_header =
					LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL . ': no-cache' /*. ',esi=on'*/ ;
				LiteSpeed_Cache_Tags::add_purge_tag($cache_tags);
				break;

		}
		$purge_headers = $this->build_purge_headers();
		$this->header_out($showhdr, $cache_control_header, $purge_headers,
				$cache_tag_header);
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
		if ( $this->config->purge_by_post(LiteSpeed_Cache_Config::PURGE_ALL_PAGES) ) {
			// if purge all, do not set any tags
			return array();
		}

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
	 *
	 *
	 * @since 1.0.1
	 */
	public function esi_admin_bar_render()
	{
		echo '<!-- lscwp admin esi start -->'
				. '<esi:include src="/lscwp_admin_bar.php" onerror=\"continue\"/>'
				. '<!-- lscwp admin esi end -->';
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	public function check_admin_bar()
	{
		if (is_admin_bar_showing()) {
			remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
			remove_action( 'in_admin_header', 'wp_admin_bar_render', 0 );
			add_action('wp_footer', array($this, 'esi_admin_bar_render'), 1000);
		}
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	public function check_storefront_cart()
	{
		if (has_action('storefront_header', 'storefront_header_cart')) {
			remove_action('storefront_header', 'storefront_header_cart', 60);
			echo '<!-- lscwp cart esi start -->'
					. '<esi:include src="/lscwp_cart.php" onerror=\"continue\"/>'
					. '<!-- lscwp cart esi end -->';
		}
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	public function check_sidebar()
	{
		if (has_action('storefront_sidebar', 'storefront_get_sidebar')) {
			remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
			echo '<!-- lscwp sidebar esi start -->'
					. '<esi:include src="/lscwp_sidebar.php" onerror=\"continue\"/>'
					. '<!-- lscwp sidebar esi end -->';
		}
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	private function add_actions_esi()
	{
		add_action('storefront_header',
					array($this, 'check_storefront_cart'), 59);
		add_action('storefront_sidebar', array($this, 'check_sidebar'), 0);
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	public function send_esi()
	{
		status_header(200);
		die();
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	private function is_esi_admin_bar($uri, $urilen)
	{
		$admin = 'admin_bar.php';
		$adminlen = strlen($admin);

		if (($urilen != $adminlen)
				|| (strncmp($uri, $admin, $adminlen) != 0)) {
			return false;
		}
		add_action( 'init', '_wp_admin_bar_init', 0 );
		add_action( 'init', 'wp_admin_bar_render', 0 );
		add_action('init', array($this, 'send_esi'), 0);
		return true;
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	private function is_esi_cart($uri, $urilen)
	{
		$cart = 'cart.php';
		$cartlen = strlen($cart);

		if (($urilen != $cartlen)
				|| (strncmp($uri, $cart, $cartlen) != 0)) {
			return false;
		}
		register_widget( 'WC_Widget_Cart' );
		add_action('init', 'storefront_cart_link_fragment', 0);
		add_action('init', 'storefront_header_cart', 0);
		add_action('init', array($this, 'send_esi'), 0);
		return true;
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	public function load_sidebar_widgets()
	{
		do_action('widgets_init');
		do_action('register_sidebar');
		do_action('wp_register_sidebar_widget');
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	private function is_esi_sidebar($uri, $urilen)
	{
		$sidebar = 'sidebar.php';
		$sidebarlen = strlen($sidebar);

		if (($urilen != $sidebarlen)
				|| (strncmp($uri, $sidebar, $sidebarlen) != 0)) {
			return false;
		}
		add_action('widgets_init', 'storefront_widgets_init', 10);
		add_action('wp_loaded', array($this, 'load_sidebar_widgets'), 0);
		add_action('wp_loaded', 'storefront_get_sidebar', 0);
		add_action('wp_loaded', array($this, 'send_esi'), 0);
		return true;
	}

	/**
	 *
	 *
	 * @since 1.0.1
	 */
	private function check_esi_page()
	{
		$prefix = '/lscwp_';
		$prefixlen = 7;
		$uri = esc_url($_SERVER['REQUEST_URI']);
		$urilen = strlen($uri);

		if (($urilen <= $prefixlen) || (strncmp($uri, $prefix, $prefixlen) != 0 )) {
			return false;
		}

		$uri = substr($uri, $prefixlen);
		$urilen -= $prefixlen;

		switch ($uri[0]) {
			case 'a':
				return $this->is_esi_admin_bar($uri, $urilen);
			case 'c':
				return $this->is_esi_cart($uri, $urilen);
			case 's':
				return $this->is_esi_sidebar($uri, $urilen);
			default:
				return false;
		}
		return false;
	}
/*END ESI CODE*/
}
