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
	private static $_instance;
	private $config;

	const PLUGIN_NAME = 'litespeed-cache';
	const PLUGIN_VERSION = '1.1.0.1';

	const LSCOOKIE_VARY_NAME = 'LSCACHE_VARY_COOKIE' ;
	const LSCOOKIE_DEFAULT_VARY = '_lscache_vary' ;
	const LSCOOKIE_VARY_LOGGED_IN = 1;
	const LSCOOKIE_VARY_COMMENTER = 2;

	const PAGE_EDIT_HTACCESS = 'lscache-edit-htaccess';

	const NONCE_NAME = 'LSCWP_NONCE';
	const ACTION_KEY = 'LSCWP_CTRL';
	const ACTION_SAVE_HTACCESS = 'save-htaccess';
	const ACTION_SAVE_SETTINGS = 'save-settings';
	const ACTION_SAVE_SETTINGS_NETWORK = 'save-settings-network';
	const ACTION_PURGE = 'PURGE';
	const ACTION_PURGE_ERRORS = 'PURGE_ERRORS';
	const ACTION_PURGE_PAGES = 'PURGE_PAGES';
	const ACTION_PURGE_BY = 'PURGE_BY';
	const ACTION_PURGE_FRONT = 'PURGE_FRONT';
	const ACTION_PURGE_ALL = 'PURGE_ALL';
	const ACTION_PURGE_EMPTYCACHE = 'PURGE_EMPTYCACHE';
	const ACTION_PURGE_SINGLE = 'PURGESINGLE';
	const ACTION_SHOW_HEADERS = 'SHOWHEADERS';
	const ACTION_NOCACHE = 'NOCACHE';
	const ACTION_CRAWLER_GENERATE_FILE = 'crawler-generate-file';
	const ACTION_CRAWLER_RESET_POS = 'crawler-reset-pos';
	const ACTION_CRAWLER_CRON_ENABLE = 'crawler-cron-enable';
	const ACTION_DO_CRAWL = 'do-crawl';
	const ACTION_BLACKLIST_SAVE = 'blacklist-save';

	const CACHECTRL_NOCACHE = 0;
	const CACHECTRL_PUBLIC = 1;
	const CACHECTRL_PURGE = 2;
	const CACHECTRL_PURGESINGLE = 3;
	const CACHECTRL_PRIVATE = 4;
	const CACHECTRL_SHARED = 5;

	const CACHECTRL_SHOWHEADERS = 128; // (1<<7)
	const CACHECTRL_STALE = 64; // (1<<6)
	const CACHECTRL_NO_VARY = 32; // (1<<5)

	const WHM_TRANSIENT = 'lscwp_whm_install';
	const WHM_TRANSIENT_VAL = 'whm_install';

	protected $plugin_dir ;
	protected $current_vary;
	protected $cachectrl = self::CACHECTRL_NOCACHE;
	protected $pub_purge_tags = array();
	protected $priv_purge_tags = array();
	protected $custom_ttl = 0;
	protected $user_status = 0;
	protected $error_status = false;

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
		$this->config = LiteSpeed_Cache_Config::get_instance();

		// Check if debug is on
		if ($this->config(LiteSpeed_Cache_Config::OPID_ENABLED)) {
			$should_debug = intval($this->config(LiteSpeed_Cache_Config::OPID_DEBUG));
            if (($should_debug == LiteSpeed_Cache_Config::VAL_ON)
                    || ($should_debug == LiteSpeed_Cache_Config::VAL_NOTSET && LiteSpeed_Cache_Router::is_admin_ip())) {
                LiteSpeed_Cache_Log::set_enabled();
            }

            // Load third party detection if lscache enabled.
            include_once LSWCP_DIR . 'thirdparty/litespeed-cache-thirdparty-registry.php';
		}

		// Register plugin activate/deactivate/uninstall hooks
		// NOTE: this can't be moved under after_setup_theme, otherwise activation will be bypassed somehow
		if( is_admin() || LiteSpeed_Cache_Router::is_cli() ) {
			$plugin_file = LSWCP_DIR . 'litespeed-cache.php';
			register_activation_hook($plugin_file, array('LiteSpeed_Cache_Activation', 'register_activation' ));
			register_deactivation_hook($plugin_file, array('LiteSpeed_Cache_Activation', 'register_deactivation' ));
			register_uninstall_hook($plugin_file, 'LiteSpeed_Cache_Activation::uninstall_litespeed_cache');
		}

		add_action('after_setup_theme', array( $this, 'init' )) ;
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

		if ( !$this->config->is_plugin_enabled() || !defined('LSCACHE_ADV_CACHE') || !LSCACHE_ADV_CACHE ) {
			return ;
		}

		define('LITESPEED_CACHE_ENABLED', true);
		ob_start();
		add_action('shutdown', array($this, 'send_headers'), 0);

		$bad_cookies = LiteSpeed_Cache_Cookie::get_instance()->setup_cookies() ;

		// if ( $this->check_esi_page()) {
		// 	return ;
		// }

		if ( !$bad_cookies && !$this->check_user_logged_in() && !LiteSpeed_Cache_Cookie::get_instance()->check_cookies() ) {
			$this->load_logged_out_actions() ;
		}
        else {
            $this->load_logged_in_actions();
        }

		// Load public hooks
		$this->load_public_actions() ;

		// load cron task for crawler
		if ( $this->config(LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE) ) {
			// keep cron intval filter
			$this->config->cron_schedule() ;

			// cron hook
			add_action(LiteSpeed_Cache_Config::CRON_ACTION_HOOK, 'LiteSpeed_Cache_Crawler::crawl_data') ;
		}

		if ( LiteSpeed_Cache_Router::is_ajax() ) {
			add_action('init', array($this, 'detect'), 4) ;
		}
		elseif ( is_admin() || is_network_admin() ) {
			add_action('admin_init', array($this, 'detect'), 0) ;
		}
		else {
			add_action('wp', array($this, 'detect'), 4) ;
		}

		// load litespeed actions
		if ( $action = LiteSpeed_Cache_Router::get_action() ) {
			$this->proceed_action($action) ;
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
		switch ( $action ) {
			case LiteSpeed_Cache::ACTION_PURGE:
				$this->cachectrl = LiteSpeed_Cache::CACHECTRL_PURGE;
				break;

			case self::ACTION_SHOW_HEADERS:
				$this->cachectrl |= self::CACHECTRL_SHOWHEADERS;
				break;

			case LiteSpeed_Cache::ACTION_PURGE_SINGLE:
				$this->cachectrl = LiteSpeed_Cache::CACHECTRL_PURGESINGLE;
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
				$this->config->cron_enable();
				break;

			// Handle the ajax request to proceed crawler manually by admin
			case LiteSpeed_Cache::ACTION_DO_CRAWL:
				LiteSpeed_Cache_Crawler::crawl_data(true) ;
				break ;

			case LiteSpeed_Cache::ACTION_BLACKLIST_SAVE:
				LiteSpeed_Cache_Crawler::get_instance()->save_blacklist() ;
				$msg = __('Crawler blacklist is saved.', 'litespeed-cache');
				break ;

			case LiteSpeed_Cache::ACTION_PURGE_FRONT:
				$this->purge_front();
				$msg = __('Notified LiteSpeed Web Server to purge the front page.', 'litespeed-cache');
				break;

			case LiteSpeed_Cache::ACTION_PURGE_PAGES:
				$this->purge_pages();
				$msg = __('Notified LiteSpeed Web Server to purge pages.', 'litespeed-cache');
				break;

			case LiteSpeed_Cache::ACTION_PURGE_ERRORS:
				$this->purge_errors();
				$msg = __('Notified LiteSpeed Web Server to purge error pages.', 'litespeed-cache');
				break;

			case LiteSpeed_Cache::ACTION_PURGE_ALL://todo: for cli, move this to ls->proceed_action()
				$this->purge_all();
				$msg = __('Notified LiteSpeed Web Server to purge the public cache.', 'litespeed-cache');
				break;

			case LiteSpeed_Cache::ACTION_PURGE_EMPTYCACHE:
				$this->purge_all();
				$msg = __('Notified LiteSpeed Web Server to purge everything.', 'litespeed-cache');
				break;

			case LiteSpeed_Cache::ACTION_PURGE_BY:
				$this->purge_list();
				$msg = __('Notified LiteSpeed Web Server to purge the list.', 'litespeed-cache');
				break;

			default:
				break;
		}
		if ( $msg && ! LiteSpeed_Cache_Router::is_ajax() ) {
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg);
			LiteSpeed_Cache_Admin::redirect();
			return;
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
	 * Register all the hooks for logged in users.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_logged_in_actions()
	{
		if (!is_openlitespeed()) {
			add_action('wp_logout', array($this, 'purge_on_logout'));
			if ($this->config->get_option(
				LiteSpeed_Cache_Config::OPID_ESI_ENABLE)) {
				$this->load_logged_out_actions();
				define('LSCACHE_ESI_LOGGEDIN', true);
			}
		}
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
		add_filter('status_header', array($this, 'check_error_codes'), 10, 2);

		$cache_res = $this->config(LiteSpeed_Cache_Config::OPID_CACHE_RES);
		if ( $cache_res ) {
			$uri = esc_url($_SERVER["REQUEST_URI"]);
			$pattern = '!' . LiteSpeed_Cache_Admin_Rules::RW_PATTERN_RES . '!';
			if ( preg_match($pattern, $uri) )
			{
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

		// The ESI functionality is an enterprise feature.
		// Removing the openlitespeed check will simply break the page.
		//todo: make a constant for esiEnable included is_openlitespeed&cfg esi eanbled
		if ((!is_openlitespeed()) && (!$is_ajax)
			&& $this->config->get_option(LiteSpeed_Cache_Config::OPID_ESI_ENABLE)) {
			$esi = LiteSpeed_Cache_Esi::get_instance();
			add_action('init', array($esi, 'register_post_type'));
			add_action('template_include', array($esi, 'esi_template'), 100);
			add_action('load-widgets.php', array($this, 'purge_widget'));
			add_action('wp_update_comment_count',
				array($this, 'purge_comment_widget'));

			// backend add rewrite rule
			if(is_admin() || is_network_admin()){// always add rewrite rule to backend in case any other plugin flush rules
				add_action('init', array($esi, 'add_rewrite_rule_esi'));
			}
		}
		add_action('wp_update_comment_count',
			array($this, 'purge_feeds'));

		// purge_single_post will only purge that post by tag
		add_action('lscwp_purge_single_post', array($this, 'purge_single_post'));

		// register recent posts widget tag before theme renders it to make it work
		add_filter('widget_posts_args', array($this, 'register_tag_widget_recent_posts'));

		// TODO: private purge?
		// TODO: purge by category, tag?
	}

	/**
	 * A shortcut to get the LiteSpeed_Cache_Config config value
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $opt_id An option ID if getting an option.
	 * @return the option value
	 */
	public static function config($opt_id)
	{
		return LiteSpeed_Cache_Config::get_instance()->get_option($opt_id);
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the LiteSpeed_Cache_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function set_locale()
	{
		load_plugin_textdomain(self::PLUGIN_NAME, false, 'litespeed-cache/languages/') ;
	}


	/**
	 * Register purge tag for pages with recent posts widget
	 * of the plugin.
	 *
	 * @since    1.0.15
	 * @access   public
	 * @param array $params [wordpress params for widget_posts_args]
	 */
	public function register_tag_widget_recent_posts($params)
	{
		LiteSpeed_Cache_Tags::add_cache_tag(LiteSpeed_Cache_Tags::TYPE_PAGES_WITH_RECENT_POSTS);
		return $params;
	}


	/**
	 * Adds new purge tags to the array of purge tags for the request.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param mixed $tags Tags to add to the list.
	 * @param boolean $is_public Whether to add public or private purge tags.
	 */
	private function add_purge_tags($tags, $is_public = true)
	{
		if ($is_public) {
			$merge = &$this->pub_purge_tags;
		}
		else {
			$merge = &$this->priv_purge_tags;
		}
		if (is_array($tags)) {
			$merge = array_merge($merge, $tags);
		}
		else {
			$merge[] = $tags;
		}
		$merge = array_unique($merge);
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
		if (!is_openlitespeed()) {
			$this->add_purge_tags('*', false);
		}
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
		if (!is_openlitespeed()) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_FRONTPAGE, false);
		}
	}

	/**
	 * Alerts LiteSpeed Web Server to purge pages.
	 *
	 * @since    1.0.15
	 * @access   public
	 */
	public function purge_pages()
	{
		$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_PAGES);
	}

	/**
	 * Alerts LiteSpeed Web Server to purge error pages.
	 *
	 * @since    1.0.14
	 * @access   public
	 */
	public function purge_errors()
	{
		$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_ERROR);
		if ( ! isset($_POST[LiteSpeed_Cache_Config::OPTION_NAME]) ) {
			return;
		}
		$input = $_POST[LiteSpeed_Cache_Config::OPTION_NAME];
		if ( isset($input['include_403']) ) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_ERROR . '403');
		}
		if ( isset($input['include_404']) ) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_ERROR . '404');
		}
		if ( isset($input['include_500']) ) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_ERROR . '500');
		}
	}

	/**
	 * The purge by callback used to purge a list of tags.
	 *
	 * @access public
	 * @since 1.0.15
	 * @param string $tags A comma delimited list of tags.
	 */
	public function purgeby_cb($tags)
	{
		$tag_arr = explode(',', $tags);
		self::add_purge_tags($tag_arr);
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
		if ( empty($val) ) {
			return;
		}
		if ( preg_match('/^[a-zA-Z0-9-]+$/', $val) == 0 ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_CAT_INV);
			return;
		}
		$cat = get_category_by_slug($val);
		if ( $cat == false ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_CAT_DNE, $val);
			return;
		}

		LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf(__('Purge category %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM . $cat->term_id);
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
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_PID_NUM, $val);
			return;
		}
		elseif (get_post_status($val) !== 'publish') {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_PID_DNE, $val);
			return;
		}
		LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf(__('Purge Post ID %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(LiteSpeed_Cache_Tags::TYPE_POST . $val);
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
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_TAG_INV);
			return;
		}
		$term = get_term_by('slug', $val, 'post_tag');
		if ($term == 0) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_TAG_DNE, $val);
			return;
		}

		LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf(__('Purge tag %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM . $term->term_id);
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
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_URL_BAD);
			return;
		}

		$hash = self::get_uri_hash($val);

		if ($hash === false) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_URL_INV, $val);
			return;
		}

		LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf(__('Purge url %s', 'litespeed-cache'), $val));

		LiteSpeed_Cache_Tags::add_purge_tag(LiteSpeed_Cache_Tags::TYPE_URL . $hash);
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
		if ( !isset($_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT])
				|| !isset($_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_LIST]) ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGE_FORM);
			return;
		}
		$sel =  $_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT];
		$list_buf = $_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_LIST];
		if (empty($list_buf)) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_EMPTY);
			return;
		}
		$list_buf = str_replace(",", "\n", $list_buf);// for cli
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
				LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_BAD);
				return;
		}
		array_walk($list, Array($this, $cb));

		// for redirection
		$_GET[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT] = $sel;
	}

	/**
	 * Purge a post on update.
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
		if ( ! in_array(get_post_status($post_id), array( 'publish', 'trash', 'private' )) ) {
			return ;
		}

		$purge_tags = $this->get_purge_tags($post_id) ;
		if ( empty($purge_tags) ) {
			return;
		}
		if ( in_array('*', $purge_tags) ) {
			$this->add_purge_tags('*');
			$this->add_purge_tags('*', false);
		}
		else {
			$this->add_purge_tags($purge_tags);
		}
		$this->cachectrl |= self::CACHECTRL_STALE;
		// $this->send_purge_headers();
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
		// $this->send_purge_headers();
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
		$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_WIDGET
			. $widget_id, false);
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
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_WIDGET
				. $recent_comments->id, false);
		}
	}

	/**
	 * Purges feeds on comment count update.
	 *
	 * @since 1.0.9
	 * @access public
	 */
	public function purge_feeds(){
		if ($this->config->get_option(LiteSpeed_Cache_Config::OPID_FEED_TTL) > 0) {
			$this->add_purge_tags(LiteSpeed_Cache_Tags::TYPE_FEED);
		}
	}

	/**
	 * Purges all private cache entries when the user logs out.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function purge_on_logout()
	{
		$this->add_purge_tags('*', false);
	}

	/**
	 * Checks if the user is logged in. If the user is logged in, does an
	 * additional check to make sure it's using the correct login cookie.
	 *
	 * @access private
	 * @return boolean True if logged in, false otherwise.
	 */
	private function check_user_logged_in()
	{
		if (!is_user_logged_in()) {
			// If the cookie is set, unset it.
			if ((isset($_COOKIE)) && (isset($_COOKIE[$this->current_vary]))
				&& (intval($_COOKIE[$this->current_vary])
					& self::LSCOOKIE_VARY_LOGGED_IN)) {

				LiteSpeed_Cache_Cookie::get_instance()->do_set_cookie(
					~self::LSCOOKIE_VARY_LOGGED_IN,
					time() + apply_filters( 'comment_cookie_lifetime', 30000000 ));
				$_COOKIE[$this->current_vary] &= ~self::LSCOOKIE_VARY_LOGGED_IN;
			}
			return false;
		}
		elseif (!isset($_COOKIE[$this->current_vary])) {
			LiteSpeed_Cache_Cookie::get_instance()->do_set_cookie(self::LSCOOKIE_VARY_LOGGED_IN,
					time() + 2 * DAY_IN_SECONDS, is_ssl(), true);
		}
		$this->user_status |= self::LSCOOKIE_VARY_LOGGED_IN;
		return true;
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
		foreach( $excludes_list as $excludes_rule ){
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

		if( !empty($_REQUEST[self::ACTION_KEY]) ) {
			return $this->no_cache_for('Query String Action') ;
		}

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
			if ((!LiteSpeed_Cache_Log::get_enabled())
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
		if ( ! empty($excludes) && has_category(explode(',', $excludes)) ) {
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
	 * Check if the page returns 403 and 500 errors.
	 *
	 * @since 1.0.13.1
	 * @access public
	 * @param $header, $code.
	 * @return $eeror_status.
	 */
	public function check_error_codes($header, $code)
	{
		$ttl_403 = $this->config->get_option(LiteSpeed_Cache_Config::OPID_403_TTL);
		$ttl_500 = $this->config->get_option(LiteSpeed_Cache_Config::OPID_500_TTL);
		if ($code == 403) {
			if ($ttl_403 <= 30) {
				LiteSpeed_Cache_Tags::set_noncacheable();
			}
			else {
				$this->error_status = $code;
			}
		}
		elseif ($code >= 500 && $code < 600) {
			if ($ttl_500 <= 30) {
				LiteSpeed_Cache_Tags::set_noncacheable();
			}
		}
		elseif ($code > 400) {
			$this->error_status = $code;
		}
		return $this->error_status;
	}

	/**
	 * Write a debug message for if a page is not cacheable.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $reason An explanation for why the page is not cacheable.
	 * @return boolean Return false.
	 */
	private function no_cache_for( $reason )
	{
		if (LiteSpeed_Cache_Log::get_enabled()) {
			LiteSpeed_Cache_Log::push('Do not cache - ' . $reason);
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
		if ((LiteSpeed_Cache_Tags::is_noncacheable() == false)
			&& ($this->is_cacheable())) {
			if (defined('LSCACHE_ESI_LOGGEDIN')) {
				$this->set_cachectrl(self::CACHECTRL_SHARED);
			}
			else {
				$this->set_cachectrl(self::CACHECTRL_PUBLIC);
			}
		}
	}

	/**
	 * Sets the request's cache control. If the request should not parse the
	 * vary, the optional $novary parameter should be used to set the flag.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param int $val The value to set the cache control to.
	 * @param bool $novary Optional. Whether to allow varies or not.
	 */
	public function set_cachectrl($val, $novary = false)
	{
		$this->cachectrl = $val;
		if ($novary) {
			$this->cachectrl |= self::CACHECTRL_NO_VARY;
		}
	}

	/**
	 * Sets a custom TTL to use with the request if needed.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param mixed $ttl An integer or string to use as the TTL. Must be numeric.
	 */
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
		if ($this->config->get_option(LiteSpeed_Cache_Config::OPID_CACHE_LOGIN) === false) {
			return;
		}
		$this->check_cacheable();
		if ($this->cachectrl !== self::CACHECTRL_PUBLIC) {
			return;
		}
		if (!empty($_GET)) {
			if (LiteSpeed_Cache_Log::get_enabled()) {
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


	/**
	 * Gathers all the purge headers.
	 *
	 * This will collect all site wide purge tags as well as
	 * third party plugin defined purge tags.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param boolean $stale Whether the public tags should stale the entries.
	 * @return string the built purge header
	 */
	private function get_purge_header($stale)
	{
		$cache_purge_header = '';
		$purge_tags = array_merge($this->pub_purge_tags,
			LiteSpeed_Cache_Tags::get_purge_tags());
		$purge_tags = array_unique($purge_tags);

		$priv_purge_tags = array_merge($this->priv_purge_tags,
			LiteSpeed_Cache_Tags::get_private_purge_tags());
		$priv_purge_tags = array_unique($priv_purge_tags);
		$private_prefix = LiteSpeed_Cache_Tags::HEADER_PURGE . 'private,';

		if (empty($purge_tags) && (empty($priv_purge_tags))) {
			return '';
		}

		$prefix = $this->config->get_option(
			LiteSpeed_Cache_Config::OPID_TAG_PREFIX);
		if (empty($prefix)) {
			$prefix = '';
		}

		if (!empty($purge_tags)) {
			$public_tags = $this->build_purge_headers($prefix, $purge_tags);
			if (empty($public_tags)) {
				// If this ends up empty, private will also end up empty
				return '';
			}
			$cache_purge_header = LiteSpeed_Cache_Tags::HEADER_PURGE
				. 'public,';
			if ($stale) {
				$cache_purge_header .= 'stale,';
			}
			$cache_purge_header .= 'tag=' . implode(',', $public_tags);
			$private_prefix = ';private,';
		}
		if (empty($priv_purge_tags)) {
			return $cache_purge_header;
		}
		elseif (in_array('*', $priv_purge_tags)) {
			$cache_purge_header .= $private_prefix . '*';
		}
		else {
			$private_tags = $this->build_purge_headers($prefix,
				$priv_purge_tags);
			if (!empty($private_tags)) {
				$cache_purge_header .= $private_prefix . 'tag='
					. implode(',', $private_tags);
			}
		}

		return $cache_purge_header;
	}

	/**
	 * Builds an array of purge headers with the given prefix.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param string $prefix The prefix to apply to each tag
	 * @param array @purge_tags The purge tags to apply the prefix to.
	 * @return array The array of built purge tags.
	 */
	private function build_purge_headers($prefix, $purge_tags)
	{
		$tags = array();
		if (!in_array('*', $purge_tags )) {
			$prefix .= 'B' . get_current_blog_id() . '_';
			foreach ($purge_tags as $val) {
				$tags[] = $prefix . $val;
			}
		}
		elseif (isset($_POST['clearcache'])) {
			$tags = array('*');
		}
		// Would only use multisite and network admin except is_network_admin
		// is false for ajax calls, which is used by wordpress updates v4.6+
		elseif ((is_multisite()) && ((is_network_admin())
			|| ((defined('DOING_AJAX'))
					&& ((check_ajax_referer('updates', false, false))
						|| (check_ajax_referer('litespeed-purgeall-network',
							false, false))))))
		{
			$blogs = LiteSpeed_Cache_Activation::get_network_ids();
			if (empty($blogs)) {
				if (LiteSpeed_Cache_Log::get_enabled()) {
					LiteSpeed_Cache_Log::push('blog list is empty');
				}
				return '';
			}
			foreach ($blogs as $blog_id) {
				$tags[] = sprintf('%sB%s_', $prefix, $blog_id);
			}
		}
		else {
			$tags[] = $prefix . 'B' . get_current_blog_id() . '_';
		}

		return $tags;
	}

	/**
	 * Builds the vary header.
	 *
	 * Currently, this only checks post passwords.
	 *
	 * @since 1.0.13
	 * @access private
	 * @global $post
	 * @return mixed false if the user has the postpass cookie. Empty string
	 * if the post is not password protected. Vary header otherwise.
	 */
	private function build_vary_headers($mode)
	{
		global $post;
		if (($mode != self::CACHECTRL_PUBLIC)
			&& ($mode != self::CACHECTRL_PRIVATE)
			&& ($mode != self::CACHECTRL_SHARED)) {
			return '';
		}
		$tp_cookies = LiteSpeed_Cache_Tags::get_vary_cookies();
		if (!empty($post->post_password)) {
			if (isset($_COOKIE['wp-postpass_' . COOKIEHASH])) {
				// If user has password cookie, do not cache
				return false;
			}
			else {
				$tp_cookies[] = 'cookie=wp-postpass_' . COOKIEHASH;
			}
		}

		if (empty($tp_cookies)) {
			return '';
		}
		return LiteSpeed_Cache_Tags::HEADER_CACHE_VARY
		. ': ' . implode(',', $tp_cookies);
	}

	/**
	 * The mode determines if the page is cacheable. This function filters
	 * out the possible show header admin control.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param boolean $showhdr Whether the show header command was selected.
	 * @param boolean $stale Whether to make the purge headers stale.
	 * @return integer The integer corresponding to the selected
	 * cache control value.
	 */
	private function validate_mode(&$showhdr, &$stale, &$novary)
	{
		$mode = $this->cachectrl;
		if ($mode & self::CACHECTRL_SHOWHEADERS) {
			$showhdr = true;
			$mode &= ~self::CACHECTRL_SHOWHEADERS;
		}

		if ($mode & self::CACHECTRL_STALE) {
			$stale = true;
			$mode &= ~self::CACHECTRL_STALE;
		}

		if ($mode & self::CACHECTRL_NO_VARY) {
			$novary = true;
			$mode &= ~self::CACHECTRL_NO_VARY;
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
	 * @param string $vary_hdr The cache vary header to send out.
	 */
	private function header_out($showhdr, $cache_ctrl, $purge_hdr, $cache_hdr = '', $vary_hdr = '')
	{
		$hdr_content = array() ;
		if ( !is_null($cache_ctrl) && !empty($cache_ctrl) ) {
			$hdr_content[] = $cache_ctrl ;
		}
		if ( !is_null($purge_hdr) && !empty($purge_hdr) ) {
			$hdr_content[] = $purge_hdr ;
		}
		if ( !is_null($cache_hdr) && !empty($cache_hdr) ) {
			$hdr_content[] = $cache_hdr ;
		}
		if ( !is_null($vary_hdr) && !empty($vary_hdr) ) {
			$hdr_content[] = $vary_hdr ;
		}

		if ( !empty($hdr_content) ) {
			if ( $showhdr ) {
				@header(LiteSpeed_Cache_Tags::HEADER_DEBUG . ': ' . implode('; ', $hdr_content));
			}
			else {
				foreach($hdr_content as $hdr) {
					@header($hdr) ;
				}
			}
		}

		if (LiteSpeed_Cache_Log::get_enabled()) {
			$buffer = ob_get_contents() ;
			if ( strlen($buffer) > 300 ) {
				$buffer = substr($buffer, 0, 300);
			}
			if ( strstr($buffer, '<!--') !== false ) {
				$buffer = preg_replace('|<!--.*?-->|s', '', $buffer);
			}
			$is_html = stripos($buffer, '<html') === 0 || stripos($buffer, '<!DOCTYPE') === 0 ;
			$running_info_showing = ! defined('DOING_AJAX') && ! defined('DOING_CRON') && $is_html ;
			if ( $running_info_showing ) {
				echo '<!-- Page generated by LiteSpeed Cache on '.date('Y-m-d H:i:s').' -->' ;
			}
			if($cache_hdr){
				LiteSpeed_Cache_Log::push($cache_hdr) ;
				if( $running_info_showing ) {
					echo "\n<!-- ".$cache_hdr." -->" ;
				}
			}
			if($cache_ctrl) {
				LiteSpeed_Cache_Log::push($cache_ctrl) ;
				if( $running_info_showing ) {
					echo "\n<!-- ".$cache_ctrl." -->" ;
				}
			}

			if (!empty($hdr_content)) {
				foreach ($hdr_content as $hdr) {
                    LiteSpeed_Cache_Log::push('Response header: ' . $hdr);
					if(!defined('DOING_AJAX')){
						echo "<!-- ".$hdr." -->\n";
					}
				}
			}

			LiteSpeed_Cache_Log::push("End response.\n");
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
		$tags_header = '';
		$showhdr = false;
		$stale = false;
		$novary = false;
		do_action('litespeed_cache_add_purge_tags');

		$mode = $this->validate_mode($showhdr, $stale, $novary);

		$vary_headers = $this->build_vary_headers($mode);
		if ($vary_headers === false) {
			$mode = self::CACHECTRL_NOCACHE;
		}

		if ($mode != self::CACHECTRL_NOCACHE) {
			$tags_header = $this->setup_tags_hdr($mode);
			if (empty($tags_header)) {
				$mode = self::CACHECTRL_NOCACHE;
			}
		}

		$ctrl_header = $this->setup_ctrl_hdr($mode, $novary);

		$purge_header = $this->get_purge_header($stale);

		$this->header_out($showhdr, $ctrl_header, $purge_header, $tags_header,
			$vary_headers);
	}

	/**
	 * Sets up the Cache Tags header depending on the mode.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param int $mode The type of response to return.
	 * @return string empty string if empty, otherwise the cache tags header.
	 */
	private function setup_tags_hdr($mode)
	{
		do_action('litespeed_cache_add_cache_tags');
		$cache_tags = $this->get_cache_tags();
		if (($mode !== self::CACHECTRL_PURGE)
			&& ($mode !== self::CACHECTRL_PURGESINGLE)) {
			$cache_tags[] = ''; //add blank entry to add blog tag.
		}
		if (empty($cache_tags)) {
			return '';
		}

		$prefix_tags = array();
		$prefix = $this->config->get_option(
			LiteSpeed_Cache_Config::OPID_TAG_PREFIX);
		if (empty($prefix)) {
			$prefix = '';
		}
		$prefix .= 'B' . get_current_blog_id() . '_';

		switch ($mode) {
			case self::CACHECTRL_PURGESINGLE:
				$cache_tags = $cache_tags[0];
			// fall through
			case self::CACHECTRL_PURGE:
				LiteSpeed_Cache_Tags::add_purge_tag($cache_tags);
				return ''; // If purge/purgesingle, cache tags header is empty.
			case self::CACHECTRL_SHARED:
			case self::CACHECTRL_PRIVATE:
				$priv_cache_tags = LiteSpeed_Cache_Tags::get_private_cache_tags();
				foreach ($priv_cache_tags as $priv_tag) {
					$prefix_tags[] = $prefix . $priv_tag;
				}
				$prefix = 'public: ' . $prefix;
				break;
			case self::CACHECTRL_PUBLIC:
				break;
			default:
				return '';
		}

		foreach ($cache_tags as $tag) {
			$prefix_tags[] = $prefix . $tag;
		}
		$hdr = LiteSpeed_Cache_Tags::HEADER_CACHE_TAG . ': '
			. implode(',', $prefix_tags);

		return $hdr;
	}

	/**
	 * Sets up the Cache Control header depending on the mode and novary.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param int $mode The type of response to return.
	 * @param boolean $novary Whether to add the no-vary part or not.
	 * @return string empty string if empty, otherwise the cache control header.
	 */
	private function setup_ctrl_hdr($mode, $novary)
	{
		if ((!is_openlitespeed())
			&& (LiteSpeed_Cache_Esi::get_instance()->has_esi())) {
			$esi_hdr = ',esi=on';
		}
		else {
			$esi_hdr = '';
		}
		$hdr = LiteSpeed_Cache_Tags::HEADER_CACHE_CONTROL . ': ';
		switch ($mode)
		{
			case self::CACHECTRL_NOCACHE:
			case self::CACHECTRL_PURGE:
			case self::CACHECTRL_PURGESINGLE:
				$hdr .= 'no-cache' . $esi_hdr;
				return $hdr;
			case self::CACHECTRL_SHARED:
				$cachectrl_val = 'shared,private';
				break;
			case self::CACHECTRL_PRIVATE:
				$cachectrl_val = 'private';
				break;
			case self::CACHECTRL_PUBLIC:
				$cachectrl_val = 'public';
				break;
		}
		if ($novary) {
			$cachectrl_val .= ',no-vary';
		}
		$options = $this->config->get_options();
		$feed_ttl = $options[LiteSpeed_Cache_Config::OPID_FEED_TTL];
		$ttl_403 = $this->config->get_option(LiteSpeed_Cache_Config::OPID_403_TTL);
		$ttl_404 = $this->config->get_option(LiteSpeed_Cache_Config::OPID_404_TTL);
		$ttl_500 = $this->config->get_option(LiteSpeed_Cache_Config::OPID_500_TTL);

		if ($this->custom_ttl != 0) {
			$ttl = $this->custom_ttl;
		}
		elseif ((LiteSpeed_Cache_Tags::get_use_frontpage_ttl())
			|| (is_front_page())){
			$ttl = $options[LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL];
		}
		elseif ((is_feed()) && ($feed_ttl > 0)) {
			$ttl = $feed_ttl;
		}
		elseif ((is_404()) && ($ttl_404 > 0)) {
			$ttl = $ttl_404;
		}
		elseif ($this->error_status === 403) {
			$ttl = $ttl_403;
		}
		elseif ($this->error_status >= 500) {
			$ttl = $ttl_500;
		}
		else {
			$ttl = $options[LiteSpeed_Cache_Config::OPID_PUBLIC_TTL];
		}
		$hdr .= $cachectrl_val . ',max-age=' . $ttl . $esi_hdr;
		return $hdr;
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

		$queried_obj_id = get_queried_object_id() ;
		$cache_tags = array();

		$hash = self::get_uri_hash(urldecode($_SERVER['REQUEST_URI']));

		$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_URL . $hash;

		if (!defined('LSCACHE_IS_ESI')) {
			if (is_front_page()) {
				$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_FRONTPAGE;
			} elseif (is_home()) {
				$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_HOME;
			}
		}

		if ($this->error_status !== false) {
			$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_ERROR . $this->error_status;
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

			if ( is_page() ) {
				$cache_tags[] = LiteSpeed_Cache_Tags::TYPE_PAGES;
			}
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
		$config = $this->config ;

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_ALL_PAGES) ) {
			// ignore the rest if purge all
			return array( '*' ) ;
		}

		do_action('litespeed_cache_on_purge_post', $post_id);

		// post
		$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_POST . $post_id ;
		$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_URL
			. self::get_uri_hash(wp_make_link_relative(get_post_permalink($post_id)));

		// for archive of categories|tags|custom tax
		global $post;
		$post = get_post($post_id) ;
		$post_type = $post->post_type ;

		global $wp_widget_factory;
		$recent_posts = $wp_widget_factory->widgets['WP_Widget_Recent_Posts'];
		if (!is_null($recent_posts)) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_WIDGET
				. $recent_posts->id;
		}

		// get adjacent posts id as related post tag
		if($post_type == 'post'){
			$prev_post = get_previous_post();
			$next_post = get_next_post();
			if(!empty($prev_post->ID)) {
				$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_POST . $prev_post->ID;
				if(LiteSpeed_Cache_Log::get_enabled()){
					LiteSpeed_Cache_Log::push('--------purge_tags prev is: '.$prev_post->ID);
				}
			}
			if(!empty($next_post->ID)) {
				$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_POST . $next_post->ID;
				if(LiteSpeed_Cache_Log::get_enabled()){
					LiteSpeed_Cache_Log::push('--------purge_tags next is: '.$next_post->ID);
				}
			}
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_TERM) ) {
			$taxonomies = get_object_taxonomies($post_type) ;
			//LiteSpeed_Cache_Log::push('purge by post, check tax = ' . print_r($taxonomies, true)) ;
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

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_PAGES) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_PAGES ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_PAGES_WITH_RECENT_POSTS) ) {
			$purge_tags[] = LiteSpeed_Cache_Tags::TYPE_PAGES_WITH_RECENT_POSTS ;
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

	/**
	 * Will get a hash of the URI. Removes query string and appends a '/' if
	 * it is missing.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param string $uri The uri to get the hash of.
	 * @return bool|string False on input error, hash otherwise.
	 */
	private static function get_uri_hash($uri)
	{
		$no_qs = strtok($uri, '?');
		if (empty($no_qs)) {
			return false;
		}
		$slashed = trailingslashit($no_qs);
		return md5($slashed);
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
		$cls = get_called_class();
		if (!isset(self::$_instance)) {
			self::$_instance = new $cls();
		}

		return self::$_instance;
	}
}
