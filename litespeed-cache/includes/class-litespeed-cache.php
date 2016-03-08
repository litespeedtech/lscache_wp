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
	const PLUGIN_VERSION = '1.0.1' ;
	//const CACHETAG_TYPE_FEED = 'FD';

	const CACHETAG_TYPE_FRONTPAGE = 'F' ;
	const CACHETAG_TYPE_HOME = 'H.' ;
	const CACHETAG_TYPE_POST = 'P.' ;
	const CACHETAG_TYPE_ARCHIVE_POSTTYPE = 'PT.' ;
	const CACHETAG_TYPE_ARCHIVE_TERM = 'T.' ; //for is_category|is_tag|is_tax
	const CACHETAG_TYPE_AUTHOR = 'A.' ;
	const CACHETAG_TYPE_ARCHIVE_DATE = 'D.' ;
	const LSHEADER_PURGE = 'X-LiteSpeed-Purge' ;
	const LSHEADER_CACHE_CONTROL = 'X-LiteSpeed-Cache-Control' ;
	const LSHEADER_CACHE_TAG = 'X-LiteSpeed-Tag' ;
	const LSHEADER_CACHE_VARY = 'X-LiteSpeed-Vary' ;
	const LSCOOKIE_USER_VARY = '_lscache_vary' ;

	protected $plugin_dir ;
	protected $config ;
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
	public function __construct()
	{
		$cur_dir = dirname(__FILE__) ;
		require_once $cur_dir . '/class-litespeed-cache-config.php' ;

		$this->config = new LiteSpeed_Cache_Config() ;
		$this->plugin_dir = plugin_dir_path($cur_dir) ;

		$plugin_file = $this->plugin_dir . 'litespeed-cache.php' ;
		register_activation_hook($plugin_file, array( $this, 'register_activation' )) ;
		register_deactivation_hook($plugin_file, array( $this, 'register_deactivation' )) ;

		add_action('after_setup_theme', array( $this, 'init' )) ;
	}

	public static function run()
	{
		if ( ! isset(self::$instance) ) {
			self::$instance = new LiteSpeed_Cache() ;
		}
	}

	public static function plugin()
	{
		return self::$instance ;
	}

	public static function config()
	{
		return self::$instance->config ;
	}

	public function debug_log( $mesg, $log_level = LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG )
	{
		if ( true === WP_DEBUG ) {
			$this->config->debug_log($mesg, $log_level) ;
		}
	}

	public function register_activation()
	{
		if ( ! (file_exists(ABSPATH . 'wp-content/advanced-cache.php')) ) {
			copy($this->plugin_dir . '/includes/advanced-cache.php', ABSPATH . 'wp-content/advanced-cache.php') ;
			$this->config->set_wp_cache_var() ;
			$this->config->plugin_activation() ;
		}
		else {
			exit(__("advanced-cache.php detected in wp-content directory! Please disable or uninstall any other cache plugins before enabling LiteSpeed Cache.", 'litespeed-cache')) ;
		}
	}

	public function register_deactivation()
	{
		$this->purge_all() ;
		unlink(ABSPATH . 'wp-content/advanced-cache.php') ;

		if ( ! $this->config->unset_wp_cache_var() ) {
			$this->config->debug_log('In wp-config.php: WP_CACHE could not be set to false during deactivation!') ;
		}

		$this->config->plugin_deactivation() ;
	}

	/* NOTICE: To other plugin developers:
	 * If your plugin does something that may update pages (e.g. a like button),
	 * do one of the actions below to purge the cache of the updated pages.
	 * The example code block must be called prior to any response body output.
	 * This includes any 'echo' outputs.
	 *
	 * Example:
	 * if (defined('LITESPEED_CACHE_ENABLED')) {
	 *		do_action('lscwp_purge_single_post', $post_id);
	 * }
	 */
	public function add_purge_hooks() {
		add_action('lscwp_purge_single_post', array($this, 'purge_single_post'));
		// TODO: private purge?
		// TODO: purge by category, tag?
	}

	public function init()
	{
		$module_enabled = $this->config->module_enabled() ; // force value later

		if ( is_admin() ) {
			$this->load_admin_actions($module_enabled) ;
		}

		if ( ! $module_enabled ) {
			return ;
		}

		define('LITESPEED_CACHE_ENABLED', true);
		$this->add_purge_hooks();
		//TODO: Uncomment this when esi is implemented.
//		add_action('init', array($this, 'check_admin_bar'), 0);

		add_action('set_logged_in_cookie', array( $this, 'set_user_cookie'), 10, 5);
		add_action('clear_auth_cookie', array( $this, 'set_user_cookie'), 10, 5);

		// TODO: uncomment this when esi is implemented.
//		$this->add_actions_esi();

		if ( $this->check_esi_page()) {
			return;
		}

		if ( is_user_logged_in() ) {
			$this->load_logged_in_actions() ;
		}
		else {
			$this->load_logged_out_actions();
		}

		$this->load_public_actions() ;

	}

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
		. sprintf(wp_kses(__('LiteSpeed Cache Plugin requires at least PHP %3$s. You are using PHP %2$s, which is out-dated and insecure. Please ask your web host to update your PHP installation or go to <a href="%1$s">active plugins</a> and deactivate LiteSpeed Cache plugin to hide this message.', 'litespeed-cache'), array( 'a' => array( 'href' => array() ) )), "plugins.php?plugin_status=active", PHP_VERSION, '5.3')
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
		$this->set_locale() ;
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
			add_action($event, array( $this, 'purge_post' ), 10, 2) ;
		}
	}


	public function set_user_cookie($logged_in_cookie = false, $expire = ' ',
					$expiration = 0, $user_id = 0, $action = 'logged_out') {
		if ($action == 'logged_in') {
            setcookie(self::LSCOOKIE_USER_VARY, '1', $expiration, COOKIEPATH,
					COOKIE_DOMAIN, is_ssl(), true);
		}
		else {
			// Use a year in case of bad local clock.
            setcookie(self::LSCOOKIE_USER_VARY, '0', time() - 31536001, COOKIEPATH,
					COOKIE_DOMAIN);
		}
	}

	private function add_purge_tags($tags, $is_public = true) {
		//TODO: implement private tag add
		if (is_array($tags)) {
			$this->pub_purge_tags = array_merge($this->pub_purge_tags, $tags);
		}
		else {
			$this->pub_purge_tags[] = $tags;
		}
		$this->pub_purge_tags = array_unique($this->pub_purge_tags);
	}

	private function send_purge_headers() {
		$cache_purge_header = self::LSHEADER_PURGE;
		if (in_array('*', $this->pub_purge_tags )) {
			$cache_purge_header .= ': *';
		}
		else {
			$cache_purge_header .= ': tag=' . implode(',', $this->pub_purge_tags);
		}
		@header($cache_purge_header);
		$this->debug_log("send purge headers " . $cache_purge_header, LiteSpeed_Cache_Config::LOG_LEVEL_INFO) ;
		// TODO: private cache headers
//		$cache_purge_header = self::LSHEADER_PURGE
//				. ': private,tag=' . implode(',', $this->ext_purge_private_tags);
//		@header($cache_purge_header, false);
	}

	public function purge_all()
	{
		$this->add_purge_tags('*');
		$this->send_purge_headers();
	}

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
		$this->send_purge_headers();
	}

	public function purge_single_post($id) {
		$post_id = intval($id);
		if ( ! in_array(get_post_status($post_id), array( 'publish', 'trash' )) ) {
			return ;
		}
		$this->add_purge_tags(self::CACHETAG_TYPE_POST . $post_id);
		$this->send_purge_headers();
	}

	// Return true if non-cacheable.
	private function is_woocommerce()
	{
		$woocom = WC();
		if (!isset($woocom)) {
			return false;
		}
		$url = wc_get_cart_url();
		// Does cart exist and is it not empty?
		if ((isset($woocom->cart)) && ( !$woocom->cart->is_empty())) {
			return true;
		}
		if (isset($woocom->checkout)) {
			return true;
		}
		return false;
	}

	private function is_uri_excluded($excludes_list)
	{
		$uri = esc_url($_SERVER["REQUEST_URI"]);
		$uri_len = strlen( $uri ) ;
		foreach( $excludes_list as $excludes_rule )
		{
			$rule_len = strlen( $excludes_rule );
			if (( $uri_len >= $rule_len )
				&& ( strncmp( $uri, $excludes_rule, $rule_len ) == 0 ))
			{
				return true ;
			}
		}
		return false;
	}

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

		if ((defined('WOOCOMMERCE_VERSION')) && ($this->is_woocommerce())) {
			return $this->no_cache_for('Cannot cache this woocommerce page with cart') ;
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_URI);
		if (( ! empty($excludes))
			&& ( $this->is_uri_excluded(explode("\n", $excludes))))
		{
			return false;
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT);
		if (( ! empty($excludes))
			&& (has_category(explode(',', $excludes)))) {
			return false;
		}

		$excludes = $this->config->get_option(LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG);
		if (( ! empty($excludes))
			&& (has_tag(explode(',', $excludes)))) {
			return false;
		}

		return true;
	}

	private function no_cache_for( $reason )
	{
		$this->debug_log('Do not cache - ' . $reason) ;
		return false ;
	}

	public function check_cacheable()
	{
		if ( $this->is_cacheable() ) {
			$ttl = $this->config->get_option(LiteSpeed_Cache_Config::OPID_PUBLIC_TTL) ;
			$cache_control_header = self::LSHEADER_CACHE_CONTROL . ': public,max-age=' . $ttl /*. ',esi=on'*/ ;
			@header($cache_control_header) ;

			$cache_tags = $this->get_cache_tags() ;

			if ( ! empty($cache_tags) ) {
				$cache_tag_header = self::LSHEADER_CACHE_TAG . ': ' . implode(',', $cache_tags) ;
				$this->debug_log('cache_control_header: ' . $cache_control_header . "\n tag_header: " . $cache_tag_header) ;
				@header($cache_tag_header) ;
			}
		}
		else {
			$cache_control_header = self::LSHEADER_CACHE_CONTROL . ': no-cache' /*. ',esi=on'*/ ;
			@header($cache_control_header) ;
		}
	}

	private function get_cache_tags()
	{
		$cache_tags = array() ;
		if ( $this->config->purge_by_post(LiteSpeed_Cache_Config::PURGE_ALL_PAGES) ) {
			// if purge all, do not set any tags
			return $cache_tags ;
		}

		global $post ;
		global $wp_query ;

		$queried_obj = get_queried_object() ;
		$queried_obj_id = get_queried_object_id() ;

		if ( is_front_page() ) {
			$cache_tags[] = self::CACHETAG_TYPE_FRONTPAGE ;
		}
		elseif ( is_home() ) {
			$cache_tags[] = self::CACHETAG_TYPE_HOME ;
		}

		if ( is_archive() ) {
			//An Archive is a Category, Tag, Author, Date, Custom Post Type or Custom Taxonomy based pages.

			if ( is_category() || is_tag() || is_tax() ) {
				$cache_tags[] = self::CACHETAG_TYPE_ARCHIVE_TERM . $queried_obj_id ;
			}
			elseif ( is_post_type_archive() ) {
				$post_type = $wp_query->get('post_type') ;
				$cache_tags[] = self::CACHETAG_TYPE_ARCHIVE_POSTTYPE . $post_type ;
			}
			elseif ( is_author() ) {
				$cache_tags[] = self::CACHETAG_TYPE_AUTHOR . $queried_obj_id ;
			}
			elseif ( is_date() ) {
				$date = $post->post_date ;
				$date = strtotime($date) ;
				if ( is_day() ) {
					$cache_tags[] = self::CACHETAG_TYPE_ARCHIVE_DATE . date('Ymd', $date) ;
				}
				elseif ( is_month() ) {
					$cache_tags[] = self::CACHETAG_TYPE_ARCHIVE_DATE . date('Ym', $date) ;
				}
				elseif ( is_year() ) {
					$cache_tags[] = self::CACHETAG_TYPE_ARCHIVE_DATE . date('Y', $date) ;
				}
			}
		}
		elseif ( is_singular() ) {
			//$this->is_singular = $this->is_single || $this->is_page || $this->is_attachment;
			$cache_tags[] = self::CACHETAG_TYPE_POST . $queried_obj_id ;
		}

		return $cache_tags ;
	}

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

		// post
		$purge_tags[] = self::CACHETAG_TYPE_POST . $post_id ;

		$ancestors = get_post_ancestors($post_id);

		// for bbpress forums, topics, replies.
		// If one is updated, the ancestors should be as well.
		if (function_exists('is_bbpress') && is_bbpress()) {
			if ( ! empty($ancestors)) {
				foreach ($ancestors as $ancestor) {
					$purge_tags[] = self::CACHETAG_TYPE_POST . $ancestor ;
				}
			}
		}

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
						$purge_tags[] = self::CACHETAG_TYPE_ARCHIVE_TERM . $term->term_id ;
					}
				}
			}
		}

		// author, for author posts and feed list
		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_AUTHOR) ) {
			$purge_tags[] = self::CACHETAG_TYPE_AUTHOR . get_post_field('post_author', $post_id) ;
		}

		// archive and feed of post type
		// todo: check if type contains space
		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_POST_TYPE) ) {
			if ( get_post_type_archive_link($post_type) ) {
				$purge_tags[] = self::CACHETAG_TYPE_ARCHIVE_POSTTYPE . $post_type ;
			}
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_FRONT_PAGE) ) {
			$purge_tags[] = self::CACHETAG_TYPE_FRONTPAGE ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_HOME_PAGE) ) {
			$purge_tags[] = self::CACHETAG_TYPE_HOME ;
		}

		// if configured to have archived by date
		$date = $post->post_date ;
		$date = strtotime($date) ;

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_DATE) ) {
			$purge_tags[] = self::CACHETAG_TYPE_ARCHIVE_DATE . date('Ymd', $date) ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_MONTH) ) {
			$purge_tags[] = self::CACHETAG_TYPE_ARCHIVE_DATE . date('Ym', $date) ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_YEAR) ) {
			$purge_tags[] = self::CACHETAG_TYPE_ARCHIVE_DATE . date('Y', $date) ;
		}

		return array_unique($purge_tags) ;
	}


/* BEGIN ESI CODE, not fully implemented for now */
	public function esi_admin_bar_render() {
		echo '<!-- lscwp admin esi start -->'
				. '<esi:include src="/lscwp_admin_bar.php" onerror=\"continue\"/>'
				. '<!-- lscwp admin esi end -->';
	}

	public function check_admin_bar() {
		if (is_admin_bar_showing()) {
			remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
			remove_action( 'in_admin_header', 'wp_admin_bar_render', 0 );
			add_action('wp_footer', array($this, 'esi_admin_bar_render'), 1000);
		}
	}

	public function check_storefront_cart() {
		if (has_action('storefront_header', 'storefront_header_cart')) {
			remove_action('storefront_header', 'storefront_header_cart', 60);
			echo '<!-- lscwp cart esi start -->'
					. '<esi:include src="/lscwp_cart.php" onerror=\"continue\"/>'
					. '<!-- lscwp cart esi end -->';
		}
	}

	public function check_sidebar() {
		if (has_action('storefront_sidebar', 'storefront_get_sidebar')) {
			remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
			echo '<!-- lscwp sidebar esi start -->'
					. '<esi:include src="/lscwp_sidebar.php" onerror=\"continue\"/>'
					. '<!-- lscwp sidebar esi end -->';
		}
	}

	private function add_actions_esi() {
		add_action('storefront_header',
					array($this, 'check_storefront_cart'), 59);
		add_action('storefront_sidebar', array($this, 'check_sidebar'), 0);
	}

	public function send_esi() {
		status_header(200);
		die();
	}

	private function is_esi_admin_bar($uri, $urilen) {
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

	private function is_esi_cart($uri, $urilen) {
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

	public function load_sidebar_widgets() {
		do_action('widgets_init');
		do_action('register_sidebar');
		do_action('wp_register_sidebar_widget');
	}

	private function is_esi_sidebar($uri, $urilen) {
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
