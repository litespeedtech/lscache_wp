<?php

/**
 * The admin-panel specific functionality of the plugin.
 *
 *
 * @since      1.0.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Admin_Display
{
	private static $_instance;

	const NOTICE_BLUE = 'notice notice-info';
	const NOTICE_GREEN = 'notice notice-success';
	const NOTICE_RED = 'notice notice-error';
	const NOTICE_YELLOW = 'notice notice-warning';
	const TRANSIENT_LITESPEED_MESSAGE = 'litespeed_messages';

	const PURGEBY_CAT = '0';
	const PURGEBY_PID = '1';
	const PURGEBY_TAG = '2';
	const PURGEBY_URL = '3';

	const PURGEBYOPT_SELECT = 'purgeby';
	const PURGEBYOPT_LIST = 'purgebylist';

	private $messages = array();
	private $disable_all = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function __construct()
	{
		// load assets
		if(!empty($_GET['page']) &&
				(substr($_GET['page'], 0, 8) == 'lscache-' || $_GET['page'] == 'litespeedcache')){
			add_action('admin_enqueue_scripts', array($this, 'load_assets'));
		}

		// main css
		add_action('admin_enqueue_scripts', array($this, 'enqueue_style'));

		$is_network_admin = is_network_admin() ;

		// Quick access menu
		if (is_multisite() && $is_network_admin) {
			$manage = 'manage_network_options';
		}
		else {
			$manage = 'manage_options';
		}
		if (current_user_can($manage)) {
			add_action('wp_before_admin_bar_render', array($this, 'add_quick_purge'));
		}

		// add menus
		if ($is_network_admin && is_plugin_active_for_network(LSWCP_BASENAME)) {
			add_action('network_admin_menu', array($this, 'register_admin_menu'));
		}
		else {
			add_action('admin_menu', array($this, 'register_admin_menu'));
		}

	}

	/**
	 * Load LiteSpeed assets
	 *
	 * @since    1.1.0
	 * @access public
	 * @param  array $hook WP hook
	 */
	public function load_assets($hook)
	{
		$this->check_messages();// We can do this cos admin_notices hook is after admin_enqueue_scripts hook in wp-admin/admin-header.php

		// Main js
		$this->enqueue_scripts();

		// Admin footer
		add_filter('admin_footer_text', array($this, 'admin_footer_text'), 1);

		// add link to plugin list page
		add_filter('plugin_action_links_' . LSWCP_BASENAME, array($this, 'add_plugin_links'));// todo:check if work

		if(LiteSpeed_Cache_Config::get_instance()->is_plugin_enabled()){
			// Help tab
			$this->add_help_tabs();

			global $pagenow;
			if ($pagenow === 'plugins.php') {//todo: check if work
				add_action('wp_default_scripts', array($this, 'set_update_text'), 0);
				add_action('wp_default_scripts', array($this, 'unset_update_text'), 20);
			}
		}
	}

	/**
	 * Output litespeed form info
	 *
	 * @since    1.1.0
	 * @access public
	 * @param  string $action
	 */
	public function form_action($action)
	{
		echo '<input type="hidden" name="' . LiteSpeed_Cache::ACTION_KEY . '" value="' . $action . '" />';
		wp_nonce_field($action, LiteSpeed_Cache::NONCE_NAME);
	}


	/**
	 * Register the admin menu display.
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public function register_admin_menu()
	{
		$capability = is_network_admin() ? 'manage_network_options' : 'manage_options';
		if (current_user_can($capability)) {
			// root menu
			add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', 'manage_options', 'lscache-dash');

			// sub menus
			$this->add_submenu(__('Manage', 'litespeed-cache'), 'lscache-dash', 'show_menu_manage');

			$this->add_submenu(__('Settings', 'litespeed-cache'), 'lscache-settings', 'show_menu_settings');

			if (!is_multisite() || is_network_admin()) {
				$this->add_submenu(__('Edit .htaccess', 'litespeed-cache'), LiteSpeed_Cache::PAGE_EDIT_HTACCESS, 'show_menu_edit_htaccess');
			}

			$this->add_submenu(__('Information', 'litespeed-cache'), 'lscache-info', 'show_info');
			if (!is_multisite() || is_network_admin()) {
				$this->add_submenu(__('Environment Report', 'litespeed-cache'), 'lscache-report', 'show_report');
			}

			if (!is_network_admin()) {
				$this->add_submenu(__('Crawler', 'litespeed-cache'), 'lscache-crawler', 'show_crawler');
			}

			// sub menus under options
			add_options_page('LiteSpeed Cache', 'LiteSpeed Cache', $capability, 'litespeedcache', array($this, 'show_menu_settings'));
		}
	}

	/**
	 * Helper function to set up a submenu page.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $menu_title The title that appears on the menu.
	 * @param string $menu_slug The slug of the page.
	 * @param string $callback The callback to call if selected.
	 */
	private function add_submenu($menu_title, $menu_slug, $callback)
	{
		add_submenu_page('lscache-dash', $menu_title, $menu_title, 'manage_options', $menu_slug, array($this, $callback));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.14
	 * @access public
	 */
	public function enqueue_style()
	{
		wp_enqueue_style(LiteSpeed_Cache::PLUGIN_NAME,
			plugin_dir_url(__FILE__) . 'css/litespeed-cache-admin.css',
			array(), LiteSpeed_Cache::PLUGIN_VERSION, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public function enqueue_scripts()
	{
		wp_register_script(LiteSpeed_Cache::PLUGIN_NAME,
			plugin_dir_url(__FILE__) . 'js/litespeed-cache-admin.js',
			array(), LiteSpeed_Cache::PLUGIN_VERSION, false);

		wp_enqueue_script(LiteSpeed_Cache::PLUGIN_NAME);
	}

	/**
	 * Callback that adds LiteSpeed Cache's action links.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $links Previously added links from other plugins.
	 * @return array Links array with the litespeed cache one appended.
	 */
	public function add_plugin_links($links)
	{
		//$links[] = '<a href="' . admin_url('admin.php?page=litespeedcache') .'">Settings</a>';
		$links[] = '<a href="' . admin_url('options-general.php?page=litespeedcache') . '">' . __('Settings', 'litespeed-cache') . '</a>';

		return $links;
	}

	/**
	 * Add text to recommend updating upon update success.
	 *
	 * @since 1.0.8.1
	 * @access public
	 * @param string $translations
	 * @param string $text
	 * @return string
	 */
	public function add_update_text($translations, $text)
	{
		if ($text !== 'Updated!') {
			return $translations;
		}

		return $translations . ' ' .
			__('It is recommended that LiteSpeed Cache be purged after updating a plugin.', 'litespeed-cache');
	}

	/**
	 * Add the filter to update plugin update text.
	 *
	 * @since 1.0.8.1
	 * @access public
	 */
	public function set_update_text()
	{
		add_filter('gettext', array($this, 'add_update_text'), 10, 2);
	}

	/**
	 * Remove the filter to update plugin update text.
	 *
	 * @since 1.0.8.1
	 * @access public
	 */
	public function unset_update_text()
	{
		remove_filter('gettext', array($this, 'add_update_text'));
	}

	/**
	 * Hooked to wp_before_admin_bar_render.
	 * Adds a link to the admin bar so users can quickly purge all.
	 *
	 * @access public
	 * @global WP_Admin_Bar $wp_admin_bar
	 * @global string $pagenow
	 */
	public function add_quick_purge()
	{
		global $wp_admin_bar;
		$url = $this->build_url(LiteSpeed_Cache::ACTION_PURGE_ALL);

		$wp_admin_bar->add_node(array(
			'id'    => 'lscache-quick-purge',
			'title' => '<span class="ab-icon"></span><span class="ab-label">' . __('LiteSpeed Cache Purge All', 'litespeed-cache') . '</span>',
			'href'  => $url,
			'meta'  => array('class' => 'litespeed-top-toolbar'),
		));
	}

	/**
	 * Builds an admin url with an action and a nonce.
	 *
	 * Assumes user capabilities are already checked.
	 *
	 * @access public
	 * @param string $action The LSCWP_CTRL action to do in the url.
	 * @param string $ajax_action AJAX call's action
	 * @return string The built url.
	 */
	public static function build_url($action, $ajax_action = false)
	{
		global $pagenow;
		$prefix = '?';

		if ( $ajax_action === false) {

			$params = $_GET;

			if (!empty($params)) {
				if (isset($params['LSCWP_CTRL'])) {
					unset($params['LSCWP_CTRL']);
				}
				if (isset($params['_wpnonce'])) {
					unset($params['_wpnonce']);
				}
				if (!empty($params)) {
					$prefix .= http_build_query($params) . '&';
				}
			}
			$combined = $pagenow . $prefix . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
		}
		else {
			$combined = 'admin-ajax.php?action=' . $ajax_action . '&' . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
		}


		if (is_network_admin()) {
			$prenonce = network_admin_url($combined);
		}
		else {
			$prenonce = admin_url($combined);
		}
		$url = wp_nonce_url($prenonce, $action, LiteSpeed_Cache::NONCE_NAME);

		return $url;
	}

	/**
	 * Change the admin footer text on LiteSpeed Cache admin pages.
	 *
	 * @since  1.0.13
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text($footer_text)
	{
		require_once LSWCP_DIR . 'admin/tpl/admin_footer.php';

		return $footer_text;
	}

	/**
	 * Whether to disable all settings or not.
	 *
	 * Currently used for 'use primary subsite settings'
	 *
	 * @since 1.0.13
	 * @access public
	 * @return bool True to disable all settings, false otherwise.
	 */
	public function get_disable_all()
	{
		return $this->disable_all;
	}

	/**
	 * Set to disable all settings.
	 *
	 * @since 1.0.13
	 * @access public
	 */
	public function set_disable_all()
	{
		$this->disable_all = true;
	}

	/**
	 * If show compatibility tab in settings
	 * @since 1.1.0
	 * @return bool True if shows
	 */
	public function show_compatibility_tab()
	{
		return function_exists('the_views');
	}

	/**
	 * Displays the help tab in the admin pages.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function add_help_tabs()
	{
		$screen = get_current_screen();
		$screen->add_help_tab(array(
			'id'      => 'lsc-overview',
			'title'   => __('Overview', 'litespeed-cache'),
			'content' => '<p>'
				. __('LiteSpeed Cache is a page cache built into LiteSpeed Web Server.', 'litespeed-cache') . ' '
				. __('This plugin communicates with LiteSpeed Web Server to let it know which pages are cacheable and when to purge them.', 'litespeed-cache')
				. '</p><p>' . __('A LiteSpeed server (OLS, LSWS, WebADC) and its LSCache module must be installed and enabled.', 'litespeed-cache')
				. '</p>',
		));

//		$screen->add_help_tab(array(
//			'id'      => 'lst-purgerules',
//			'title'   => __('Auto Purge Rules', 'litespeed-cache'),
//			'content' => '<p>' . __('You can set what pages will be purged when a post is published or updated.', 'litespeed-cache') . '</p>',
//		));

		$screen->set_help_sidebar(
			'<p><strong>' . __('For more information:', 'litespeed-cache') . '</strong></p>' .
//				'<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache" rel="noopener noreferrer" target="_blank">' . __('LSCache Documentation', 'litespeed-cache') . '</a></p>' .
			'<p><a href="https://wordpress.org/support/plugin/litespeed-cache" rel="noopener noreferrer" target="_blank">' . __('Support Forum', 'litespeed-cache') . '</a></p>'
		);
	}

	/**
	 * Check to make sure that caching is enabled.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return mixed True if enabled, error message otherwise.
	 */
	public function check_license()
	{
		if (!LiteSpeed_Cache_Config::get_instance()->is_caching_allowed()) {
			self::add_error(LiteSpeed_Cache_Admin_Error::E_SERVER);
			self::display_messages();
		}
	}

	/**
	 * Builds the html for a single notice.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param string $color The color to use for the notice.
	 * @param string $str The notice message.
	 * @return string The built notice html.
	 */
	private static function build_notice($color, $str)
	{
		return '<div class="' . $color . ' is-dismissible"><p>'. $str . '</p></div>';
	}

	/**
	 * Get the error description
	 *
	 * @since 1.1.0
	 * @param  init $err_code
	 * @param  mixed $args
	 * @return mixed String or false
	 */
	public static function get_error($err_code, $args = null)
	{
		$error = LiteSpeed_Cache_Admin_Error::get_instance()->convert_code_to_error($err_code);
		if (empty($error)) {
			return false;
		}
		$error = 'ERROR ' . $err_code . ': ' . $error;
		if (!is_null($args)) {
			if (is_array($args)) {
				$error = vsprintf($error, $args);
			}else{
				$error = sprintf($error, $args);
			}
		}
		return $error;
	}

	/**
	 * Adds an error to the admin notice system.
	 *
	 * This function will get the error message by error code and arguments
	 * and append it to the list of outgoing errors.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param int $err_code The error code to retrieve.
	 * @param mixed $args Null if no arguments, an array if multiple arguments,
	 * else a single argument.
	 */
	public static function add_error($err_code, $args = null)
	{
		$error = self::get_error($err_code, $args);
		if(!$error){
			return false;
		}
		self::add_notice(self::NOTICE_RED, $error);
	}

	/**
	 * Adds a notice to display on the admin page. Multiple messages of the
	 * same color may be added in a single call. If the list is empty, this
	 * method will add the action to display notices.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $color One of the available constants provided by this
	 *     class.
	 * @param mixed $msg May be a string for a single message or an array for
	 *     multiple.
	 */
	public static function add_notice($color, $msg)
	{
		$messages = (array)get_transient(self::TRANSIENT_LITESPEED_MESSAGE);
		if(!$messages){
			$messages = array();
		}
		if (is_array($msg)) {
			foreach ($msg as $str) {
				$messages[] = self::build_notice($color, $str);
			}
		}else{
			$messages[] = self::build_notice($color, $msg);
		}
		set_transient(self::TRANSIENT_LITESPEED_MESSAGE, $messages, 86400);
	}

	/**
	 * Display notices and errors in dashboard
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function display_messages()
	{
		$messages = get_transient(self::TRANSIENT_LITESPEED_MESSAGE);
		if(is_array($messages)){
			$messages = array_unique($messages);
			foreach ($messages as $msg) {
				echo $msg;
			}
		}
		delete_transient(self::TRANSIENT_LITESPEED_MESSAGE);
	}

	/**
	 * Check if has new messages
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function check_messages()
	{
		$messages = get_transient(self::TRANSIENT_LITESPEED_MESSAGE);
		if(!$messages){
			return;
		}
		add_action(is_network_admin() ? 'network_admin_notices' : 'admin_notices', array($this, 'display_messages'));
	}

	/**
	 * Hooked to the in_widget_form action.
	 * Appends LiteSpeed Cache settings to the widget edit settings screen.
	 * This will append the esi on/off selector and ttl text.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param type $widget
	 * @param type $return
	 * @param type $instance
	 */
	public function show_widget_edit($widget, $return, $instance)
	{
		$options = null;
		$enable_levels = array(
			LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE => __('Disable', 'litespeed-cache'),
			LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE => __('Enable', 'litespeed-cache'));

		$options = LiteSpeed_Cache_Esi::widget_load_get_options($widget);
		if (empty($options)) {
			$options = array(
				LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE
					=> false,
				LiteSpeed_Cache_Esi::WIDGET_OPID_TTL => '300'
			);
			$options = apply_filters('litespeed_cache_widget_default_options',
				$options, $widget);
		}
		if (empty($options)) {
			$esi = false;
			$ttl = '300';
		}
		else {
			$esi = $options[LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE]
				? LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE
				: LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE;
			$ttl = $options[LiteSpeed_Cache_Esi::WIDGET_OPID_TTL];
		}

		$buf = '<h4>LiteSpeed Cache:</h4>';

		$buf .= '<label for="' . LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE
			. '">' . __('Enable ESI for this Widget:', 'litespeed-cache')
			. '&nbsp;&nbsp;&nbsp;</label>';

		$buf .= $this->input_field_radio(LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE,
			$enable_levels, $esi);

		$buf .= '<br><br>';

		$buf .= '<label for="' . LiteSpeed_Cache_Esi::WIDGET_OPID_TTL
			. '">' . __('Widget Cache TTL:', 'litespeed-cache')
			. '&nbsp;&nbsp;&nbsp;</label>';

		$buf .= $this->input_field_text(LiteSpeed_Cache_Esi::WIDGET_OPID_TTL,
			$ttl, '7', '', __('seconds', 'litespeed-cache'));

		$buf .= '<p class="install-help">'
			. __('Default value 300 seconds (5 minutes).', 'litespeed-cache')
			. __(' A TTL of 0 indicates do not cache.', 'litespeed-cache')
			. '</p>';

		$buf .= '<br><br>';
		echo $buf;
	}

	/**
	 * Displays the cache management page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_menu_manage()
	{
		require_once LSWCP_DIR . 'admin/tpl/manage.php';
	}

	/**
	 * Outputs the LiteSpeed Cache settings page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_menu_settings()
	{
		if (is_network_admin()) {
			require_once LSWCP_DIR . 'admin/tpl/network_settings.php';
		}
		else {
			settings_errors();
			require_once LSWCP_DIR . 'admin/tpl/settings.php';
                // TODO: move this
		$esi_tab = '';
		$esi_settings = '';
		if (!is_openlitespeed()) {
			$esi_tab = '<li><a href="#esi-settings">'
				. __('ESI Settings', 'litespeed-cache') . '</a></li>';
			$esi_settings = '<div id="esi-settings">'
				. $this->show_settings_esi($options) . '</div>';
			++$tab_count;
		}
		}
	}

	/**
	 * Displays the edit_htaccess admin page.
	 *
	 * This function will try to load the .htaccess file contents.
	 * If it fails, it will echo the error message.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function show_menu_edit_htaccess()
	{
		require_once LSWCP_DIR . 'admin/tpl/edit_htaccess.php';
	}

	/**
	 * Outputs the html for the Environment Report page.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public function show_report()
	{
		require_once LSWCP_DIR . 'admin/tpl/report.php';
	}

	/**
	 * Outputs the crawler operation page.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function show_crawler()
	{
		require_once LSWCP_DIR . 'admin/tpl/crawler.php';
	}

	/**
	 * Outputs the html for the info page.
	 *
	 * This page includes three tabs:
	 * - configurations
	 * - third party plugin compatibilities
	 * - common rewrite rules.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function show_info()
	{
		require_once LSWCP_DIR . 'admin/tpl/info.php';
	}

	/**
	 * Outputs a notice to the admin panel when the plugin is installed
	 * via the WHM plugin.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public function show_display_installed()
	{
		$buf = '<h3>'. __('LiteSpeed Cache plugin is installed!', 'litespeed-cache'). '</h3>' . ' '
			. __('This message indicates that the plugin was installed by the server admin.', 'litespeed-cache') . ' '
			. __('The LiteSpeed Cache plugin is used to cache pages - a simple way to improve the performance of the site.', 'litespeed-cache') . ' '
			. __('However, there is no way of knowing all the possible customizations that were implemented.', 'litespeed-cache') . ' '
			. __('For that reason, please test the site to make sure everything still functions properly.', 'litespeed-cache')
			. '<br /><br />'
			. __('Examples of test cases include:', 'litespeed-cache')
			. '<ul>'
				. '<li>' . __('Visit the site while logged out.', 'litespeed-cache') . '</li>'
				. '<li>' . __('Create a post, make sure the front page is accurate.', 'litespeed-cache') . '</li>'
			. '</ul>'
			. sprintf(__('If there are any questions, the team is always happy to answer any questions on the <a %s>support forum</a>.', 'litespeed-cache'),
				'href="https://wordpress.org/support/plugin/litespeed-cache" rel="noopener noreferrer" target="_blank"')
			. '<br />'
			. __('If you would rather not move at litespeed, you can deactivate this plugin.', 'litespeed-cache');

		self::add_notice(self::NOTICE_BLUE . ' lscwp-whm-notice', $buf);
	}

	public static function show_error_cookie()
	{
		$err = __('NOTICE: Database login cookie did not match your login cookie.', 'litespeed-cache') . ' '
			. __('If the login cookie was recently changed in the settings, please log out and back in.', 'litespeed-cache') . ' '
			. sprintf(__('If not, please verify the setting in the <a href="%1$s">Advanced tab</a>.', 'litespeed-cache'),
				admin_url('admin.php?page=lscache-settings#advanced'));

		if (LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS') {
			$err .= ' ' . __('If using OpenLiteSpeed, the server must be restarted once for the changes to take effect.', 'litespeed-cache');
		}

		self::add_notice(self::NOTICE_YELLOW, $err);
	}

        // TODO: move this
	/**
         * Builds the html for the esi settings tab.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param array $options The current configuration options.
	 * @return string The html for the esi settings tab.
	 */
	private function show_settings_esi($options)
	{
		// comments
		// comment form
		// admin bar


		$esi_desc = self::build_paragraph(
			__('ESI enables the capability to cache pages for logged in users/commenters.', 'litespeed-cache'),
			__('ESI functions by replacing the private information blocks with an ESI include.', 'litespeed-cache'),
			__('When the server sees an ESI include, a sub request is created, containing the private information.', 'litespeed-cache')
		);

		$enable_esi_desc = self::build_paragraph(
			__('Enabling ESI will cache the public page for logged in users.', 'litespeed-cache'),
			__('The Admin Bar, comments, and comment form will be served via ESI blocks.', 'litespeed-cache'),
			__('The ESI blocks will not be cached until Cache ESI is checked.', 'litespeed-cache')
		);

		$cache_esi_desc = self::build_paragraph(
			__('Cache the ESI blocks.', 'litespeed-cache')
		);

		$buf = $this->input_group_start(__('ESI Settings', 'litespeed-cache'),
			$esi_desc);

		$esi_ids = array(
			'lscwp_' . LiteSpeed_Cache_Config::OPID_ESI_CACHE
		);

		$id = LiteSpeed_Cache_Config::OPID_ESI_ENABLE;
		$enable_esi = $this->input_field_checkbox('lscwp_' . $id, $id,
			$options[$id], '', 'lscwpEsiEnabled(this, [' . implode(',', $esi_ids) . '])');
		$buf .= $this->display_config_row(__('Enable ESI', 'litespeed-cache'),
			$enable_esi, $enable_esi_desc);

		$readonly = ($options[$id] === false);

		$id = LiteSpeed_Cache_Config::OPID_ESI_CACHE;
		$cache_esi = $this->input_field_checkbox('lscwp_' . $id, $id,
			$options[$id], '', '', $readonly);
		$buf .= $this->display_config_row(__('Cache ESI', 'litespeed-cache'),
			$cache_esi, $cache_esi_desc);

		$buf .= $this->input_group_end();
		return $buf;
	}

	/**
	 * Build a textarea
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 * @param  string $val Value of input
	 * @param  boolean $disabled If this input is disabled or not
	 */
	public function build_textarea($id, $val = null, $disabled = false)
	{
		if ( $val === null ){
			global $_options;
			$val = $_options[$id];
		}
		$disabled = $disabled ? ' disabled ' : '';

		echo "<textarea name='" . LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' rows='5' cols='80' $disabled>" . esc_textarea($val) . "</textarea>";
	}

	/**
	 * Build a text input field
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 * @param  string $style Appending styles
	 */
	public function build_input($id, $style = false, $disabled = false, $readonly = false, $id_attr = null, $val = null)
	{
		if ( $val === null ){
			global $_options;
			$val = $_options[$id];
		}
		$disabled = $disabled ? ' disabled ' : '';
		$readonly = $readonly ? ' readonly ' : '';
		if ( $id_attr !== null ){
			$id_attr = " id='$id_attr' ";
		}

		echo "<input type='text' class='regular-text $style' name='" . LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' value='" . esc_textarea($val) ."' $disabled $readonly $id_attr /> ";
	}

	/**
	 * Build a switch div html snippet
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 */
	public function build_switch($id, $disabled = false, $return = false)
	{
		$html = '<div class="litespeed-row">
					<div class="litespeed-switch litespeed-label-info">' ;
		$html .= $this->build_radio($id, LiteSpeed_Cache_Config::VAL_ON, null, null, $disabled) ;
		$html .= $this->build_radio($id, LiteSpeed_Cache_Config::VAL_OFF, null, null, $disabled) ;
		$html .= '	</div>
				</div>' ;

		if ( $return ) {
			return $html ;
		}
		else {
			echo $html ;
		}
	}

	/**
	 * Build a checkbox html snippet
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 * @param  string $title
	 * @param  bool $checked
	 */
	public function build_checkbox($id, $title, $checked, $is_mini = false)
	{
		$checked = $checked ? ' checked ' : '';
		$is_mini = $is_mini ? ' litespeed-mini ' : '';

		echo "<div class='litespeed-radio $is_mini'>
				<input type='checkbox' name='" . LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' id='conf_$id' value='1' $checked />
				<label for='conf_$id'>$title</label>
			</div>";
	}

	/**
	 * Build a radio input html codes and output
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 * @param  string $val     Default value of this input
	 * @param  string $txt     Title of this input
	 * @param  bool $checked   If checked or not
	 */
	public function build_radio($id, $val, $txt = null, $checked = null, $disabled = false)
	{
		if ( $checked === null ){
			global $_options;
			$to_be_checked = is_int($val) ? (int)$_options[$id] : $_options[$id];

			$checked = $to_be_checked === $val ? true : false;
		}

		$id_attr = is_int($val) ? "conf_{$id}_$val" : md5($val);

		if ( $txt === null ){
			if ( $val === LiteSpeed_Cache_Config::VAL_ON ){
				$txt = __('Enable', 'litespeed-cache');
			}

			if ( $val === LiteSpeed_Cache_Config::VAL_OFF ){
				$txt = __('Disable', 'litespeed-cache');
			}
		}

		$checked = $checked ? ' checked ' : '';
		$disabled = $disabled ? ' disabled ' : '';

		return "<input type='radio' "
			. " name='". LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' "
			. " id='$id_attr' "
			. " value='$val' "
			. " $checked "
			. " $disabled "
			. " />"
			. " <label for='$id_attr'>$txt</label>";
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
