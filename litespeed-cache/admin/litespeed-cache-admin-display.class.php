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
	private static $_instance ;

	const NOTICE_BLUE = 'notice notice-info' ;
	const NOTICE_GREEN = 'notice notice-success' ;
	const NOTICE_RED = 'notice notice-error' ;
	const NOTICE_YELLOW = 'notice notice-warning' ;
	const TRANSIENT_LITESPEED_MESSAGE = 'litespeed_messages' ;

	const PURGEBY_CAT = '0' ;
	const PURGEBY_PID = '1' ;
	const PURGEBY_TAG = '2' ;
	const PURGEBY_URL = '3' ;

	const PURGEBYOPT_SELECT = 'purgeby' ;
	const PURGEBYOPT_LIST = 'purgebylist' ;

	const DISMISS_MSG = 'litespeed-cache-dismiss' ;
	const RULECONFLICT_ON = 'ExpiresDefault_1' ;
	const RULECONFLICT_DISMISSED = 'ExpiresDefault_0' ;

	private $messages = array() ;
	private $disable_all = false ;
	private $default_settings = array() ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function __construct()
	{
		// load assets
		if( ! empty($_GET['page']) &&
				(substr($_GET['page'], 0, 8) == 'lscache-' || $_GET['page'] == 'litespeedcache') ) {
			add_action('admin_enqueue_scripts', array($this, 'load_assets')) ;
		}

		// main css
		add_action('admin_enqueue_scripts', array($this, 'enqueue_style')) ;
		// Main js
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts')) ;

		$is_network_admin = is_network_admin() ;

		// Quick access menu
		if ( is_multisite() && $is_network_admin ) {
			$manage = 'manage_network_options' ;
		}
		else {
			$manage = 'manage_options' ;
		}
		if ( current_user_can($manage) ) {
			if ( LiteSpeed_Cache_Router::cache_enabled() ) {
				add_action('wp_before_admin_bar_render', array($this, 'add_quick_purge')) ;
			}
			add_action('admin_enqueue_scripts', array($this, 'check_messages')) ;// We can do this cos admin_notices hook is after admin_enqueue_scripts hook in wp-admin/admin-header.php
		}

		// add menus
		if ( $is_network_admin && is_plugin_active_for_network(LSWCP_BASENAME) ) {
			add_action('network_admin_menu', array($this, 'register_admin_menu')) ;
		}
		else {
			add_action('admin_menu', array($this, 'register_admin_menu')) ;
		}

		// get default setting values
		$this->default_settings = LiteSpeed_Cache_Config::get_instance()->get_default_options() ;
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
		// Admin footer
		add_filter('admin_footer_text', array($this, 'admin_footer_text'), 1) ;

		if( LiteSpeed_Cache_Router::cache_enabled() ) {
			// Help tab
			$this->add_help_tabs() ;

			global $pagenow ;
			if ( $pagenow === 'plugins.php' ) {//todo: check if work
				add_action('wp_default_scripts', array($this, 'set_update_text'), 0) ;
				add_action('wp_default_scripts', array($this, 'unset_update_text'), 20) ;
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
		echo '<input type="hidden" name="' . LiteSpeed_Cache::ACTION_KEY . '" value="' . $action . '" />' ;
		wp_nonce_field($action, LiteSpeed_Cache::NONCE_NAME) ;
	}


	/**
	 * Register the admin menu display.
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public function register_admin_menu()
	{
		$capability = is_network_admin() ? 'manage_network_options' : 'manage_options' ;
		if ( current_user_can($capability) ) {
			// root menu
			add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', 'manage_options', 'lscache-dash') ;

			// sub menus
			$this->add_submenu(__('Manage', 'litespeed-cache'), 'lscache-dash', 'show_menu_manage') ;

			$this->add_submenu(__('Settings', 'litespeed-cache'), 'lscache-settings', 'show_menu_settings') ;

			if ( ! is_multisite() || is_network_admin() ) {
				$this->add_submenu(__('Edit .htaccess', 'litespeed-cache'), LiteSpeed_Cache::PAGE_EDIT_HTACCESS, 'show_menu_edit_htaccess') ;
			}

			$this->add_submenu(__('Information', 'litespeed-cache'), 'lscache-info', 'show_info') ;
			if ( ! is_multisite() || is_network_admin() ) {
				$this->add_submenu(__('Environment Report', 'litespeed-cache'), 'lscache-report', 'show_report') ;
			}

			if ( ! is_network_admin() ) {
				$this->add_submenu(__('Crawler', 'litespeed-cache'), 'lscache-crawler', 'show_crawler') ;
			}

			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				$this->add_submenu(__('Debug Log', 'litespeed-cache'), 'lscache-debug', 'show_debug_log') ;
			}

			// sub menus under options
			add_options_page('LiteSpeed Cache', 'LiteSpeed Cache', $capability, 'litespeedcache', array($this, 'show_menu_settings')) ;
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
		add_submenu_page('lscache-dash', $menu_title, $menu_title, 'manage_options', $menu_slug, array($this, $callback)) ;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.14
	 * @access public
	 */
	public function enqueue_style()
	{
		wp_enqueue_style(LiteSpeed_Cache::PLUGIN_NAME, plugin_dir_url(__FILE__) . 'css/litespeed-cache-admin.css', array(), LiteSpeed_Cache::PLUGIN_VERSION, 'all') ;
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public function enqueue_scripts()
	{
		wp_register_script(LiteSpeed_Cache::PLUGIN_NAME, plugin_dir_url(__FILE__) . 'js/litespeed-cache-admin.js', array(), LiteSpeed_Cache::PLUGIN_VERSION, false) ;

		if ( LiteSpeed_Cache_Router::has_whm_msg() ) {
			$ajax_url_dismiss_whm = self::build_url(LiteSpeed_Cache::ACTION_DISMISS_WHM, LiteSpeed_Cache::ACTION_DISMISS_WHM) ;
			wp_localize_script(LiteSpeed_Cache::PLUGIN_NAME, 'litespeed_data', array('ajax_url_dismiss_whm' => $ajax_url_dismiss_whm)) ;
		}

		if ( LiteSpeed_Cache_Router::has_msg_ruleconflict() ) {
			$ajax_url = self::build_url(LiteSpeed_Cache::ACTION_DISMISS_EXPIRESDEFAULT, LiteSpeed_Cache::ACTION_DISMISS_EXPIRESDEFAULT) ;
			wp_localize_script(LiteSpeed_Cache::PLUGIN_NAME, 'litespeed_data', array('ajax_url_dismiss_ruleconflict' => $ajax_url)) ;
		}

		wp_enqueue_script(LiteSpeed_Cache::PLUGIN_NAME) ;
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
		$links[] = '<a href="' . admin_url('options-general.php?page=litespeedcache') . '">' . __('Settings', 'litespeed-cache') . '</a>' ;

		return $links ;
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
		if ( $text !== 'Updated!' ) {
			return $translations ;
		}

		return $translations . ' ' . __('It is recommended that LiteSpeed Cache be purged after updating a plugin.', 'litespeed-cache') ;
	}

	/**
	 * Add the filter to update plugin update text.
	 *
	 * @since 1.0.8.1
	 * @access public
	 */
	public function set_update_text()
	{
		add_filter('gettext', array($this, 'add_update_text'), 10, 2) ;
	}

	/**
	 * Remove the filter to update plugin update text.
	 *
	 * @since 1.0.8.1
	 * @access public
	 */
	public function unset_update_text()
	{
		remove_filter('gettext', array($this, 'add_update_text')) ;
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
		global $wp_admin_bar ;
		$url = self::build_url(LiteSpeed_Cache::ACTION_PURGE_ALL) ;

		$wp_admin_bar->add_node(array(
			'id'    => 'lscache-quick-purge',
			'title' => '<span class="ab-icon"></span><span class="ab-label">' . __('LiteSpeed Cache Purge All', 'litespeed-cache') . '</span>',
			'href'  => $url,
			'meta'  => array('class' => 'litespeed-top-toolbar'),
		)) ;
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
		global $pagenow ;
		$prefix = '?' ;

		if ( $ajax_action === false ) {
			$params = $_GET ;

			if ( ! empty($params) ) {
				if ( isset($params['LSCWP_CTRL']) ) {
					unset($params['LSCWP_CTRL']) ;
				}
				if ( isset($params['_wpnonce']) ) {
					unset($params['_wpnonce']) ;
				}
				if ( ! empty($params) ) {
					$prefix .= http_build_query($params) . '&' ;
				}
			}
			$combined = $pagenow . $prefix . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
		}
		else {
			$combined = 'admin-ajax.php?action=' . $ajax_action . '&' . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
		}

		if ( is_network_admin() ) {
			$prenonce = network_admin_url($combined) ;
		}
		else {
			$prenonce = admin_url($combined) ;
		}
		$url = wp_nonce_url($prenonce, $action, LiteSpeed_Cache::NONCE_NAME) ;

		return $url ;
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
		require_once LSWCP_DIR . 'admin/tpl/admin_footer.php' ;

		return $footer_text ;
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
		return $this->disable_all ;
	}

	/**
	 * Set to disable all settings.
	 *
	 * @since 1.0.13
	 * @access public
	 */
	public function set_disable_all()
	{
		$this->disable_all = true ;
	}

	/**
	 * If show compatibility tab in settings
	 * @since 1.1.0
	 * @return bool True if shows
	 */
	public function show_compatibility_tab()
	{
		return function_exists('the_views') ;
	}

	/**
	 * Displays the help tab in the admin pages.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function add_help_tabs()
	{
		require_once LSWCP_DIR . 'admin/tpl/help_tabs.php' ;
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
		if ( ! LiteSpeed_Cache_Config::get_instance()->is_caching_allowed() ) {
			self::add_error(LiteSpeed_Cache_Admin_Error::E_SERVER) ;
			self::display_messages() ;
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
		return '<div class="' . $color . ' is-dismissible"><p>'. $str . '</p></div>' ;
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
		$error = LiteSpeed_Cache_Admin_Error::get_instance()->convert_code_to_error($err_code) ;
		if ( empty($error) ) {
			return false ;
		}
		$error = 'ERROR ' . $err_code . ': ' . $error ;
		if ( ! is_null($args) ) {
			if ( is_array($args) ) {
				$error = vsprintf($error, $args) ;
			}
			else {
				$error = sprintf($error, $args) ;
			}
		}
		return $error ;
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
		$error = self::get_error($err_code, $args) ;
		if( ! $error ) {
			return false ;
		}
		self::add_notice(self::NOTICE_RED, $error) ;
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
		$messages = (array)get_transient(self::TRANSIENT_LITESPEED_MESSAGE) ;
		if( ! $messages ) {
			$messages = array() ;
		}
		if ( is_array($msg) ) {
			foreach ($msg as $str) {
				$messages[] = self::build_notice($color, $str) ;
			}
		}
		else {
			$messages[] = self::build_notice($color, $msg) ;
		}
		set_transient(self::TRANSIENT_LITESPEED_MESSAGE, $messages, 86400) ;
	}

	/**
	 * Display notices and errors in dashboard
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function display_messages()
	{
		$messages = get_transient(self::TRANSIENT_LITESPEED_MESSAGE) ;
		if( is_array($messages) ) {
			$messages = array_unique($messages) ;
			foreach ($messages as $msg) {
				echo $msg ;
			}
		}
		delete_transient(self::TRANSIENT_LITESPEED_MESSAGE) ;
	}

	/**
	 * Check if has new messages
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function check_messages()
	{
		$messages = get_transient(self::TRANSIENT_LITESPEED_MESSAGE) ;
		if( ! $messages ) {
			return ;
		}
		add_action(is_network_admin() ? 'network_admin_notices' : 'admin_notices', array($this, 'display_messages')) ;
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
		require LSWCP_DIR . 'admin/tpl/esi_widget_edit.php' ;
	}

	/**
	 * Displays the cache management page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_menu_manage()
	{
		require_once LSWCP_DIR . 'admin/tpl/manage.php' ;
	}

	/**
	 * Outputs the LiteSpeed Cache settings page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_menu_settings()
	{
		if ( is_network_admin() ) {
			require_once LSWCP_DIR . 'admin/tpl/network_settings.php' ;
		}
		else {
			if ( $_GET['page'] != 'litespeedcache' ) {// ls settings msg need to display manually
				settings_errors() ;
			}
			require_once LSWCP_DIR . 'admin/tpl/settings.php' ;
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
		require_once LSWCP_DIR . 'admin/tpl/edit_htaccess.php' ;
	}

	/**
	 * Outputs the html for the Environment Report page.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public function show_report()
	{
		require_once LSWCP_DIR . 'admin/tpl/report.php' ;
	}

	/**
	 * Outputs the crawler operation page.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function show_crawler()
	{
		require_once LSWCP_DIR . 'admin/tpl/crawler.php' ;
	}

	/**
	 * Outputs the debug log.
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public function show_debug_log()
	{
		require_once LSWCP_DIR . 'admin/tpl/debug_log.php' ;
	}

	/**
	 * Outputs the html for the info page.
	 *
	 * This page includes three tabs:
	 * - configuration
	 * - third party plugin compatibilities
	 * - common rewrite rules.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function show_info()
	{
		require_once LSWCP_DIR . 'admin/tpl/info.php' ;
	}

	/**
	 * Outputs a notice to the admin panel when ExpiresDefault is detected
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public function show_rule_conflict()
	{
		require_once LSWCP_DIR . 'admin/tpl/show_rule_conflict.php' ;
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
		require_once LSWCP_DIR . 'admin/tpl/show_display_installed.php' ;
	}

	/**
	 * Display error cookie msg.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public static function show_error_cookie()
	{
		require_once LSWCP_DIR . 'admin/tpl/show_error_cookie.php' ;
	}

	/**
	 * Build a textarea
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 * @param  string $val Value of input
	 * @param  boolean $disabled If this input is disabled or not
	 * @param  int $cols The width of textarea
	 */
	public function build_textarea($id, $val = null, $disabled = false, $cols = false)
	{
		if ( $val === null ) {
			global $_options ;
			$val = $_options[$id] ;
		}
		$disabled = $disabled ? ' disabled ' : '' ;

		if ( $cols === false ) {
			$cols = 80 ;
		}

		echo "<textarea name='" . LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' rows='5' cols='$cols' $disabled>" . esc_textarea($val) . "</textarea>" ;
	}

	/**
	 * Build a text input field
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 * @param  string $style     Appending styles
	 * @param  boolean $disabled Disable this field
	 * @param  boolean $readonly If is readonly
	 * @param  string $id_attr   ID for this field
	 * @param  string $val       Field value
	 * @param  string $attrs     Additional attributes
	 * @param  string $type      Input type
	 */
	public function build_input( $id, $style = false, $disabled = false, $readonly = false, $id_attr = null, $val = null, $attrs = '', $type = 'text' )
	{
		if ( $val === null ) {
			global $_options ;
			$val = $_options[ $id ] ;
		}
		$disabled = $disabled ? ' disabled ' : '' ;
		$readonly = $readonly ? ' readonly ' : '' ;
		if ( $id_attr !== null ) {
			$id_attr = " id='$id_attr' " ;
		}

		if ( $type == 'text' ) {
			$style = "regular-text $style" ;
		}

		echo "<input type='$type' class='$style' name='" . LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' value='" . esc_textarea( $val ) ."' $disabled $readonly $id_attr $attrs /> " ;
	}

	/**
	 * Build a switch div html snippet
	 *
	 * @since 1.1.0
	 * @access public
	 * @param  string $id
	 * @param  boolean $disabled Disable this field
	 * @param  boolean $return   Return the html or echo it
	 * @param  boolean $checked  If the value is on
	 * @param  string $id_attr   ID for this field, set to true if want to use a not specified unique value
	 */
	public function build_switch($id, $disabled = false, $return = false, $checked = null, $id_attr = null)
	{
		$id_attr_on = $id_attr === null ? null : $id_attr . '_' . LiteSpeed_Cache_Config::VAL_ON ;
		$id_attr_off = $id_attr === null ? null : $id_attr . '_' . LiteSpeed_Cache_Config::VAL_OFF ;
		$html = '<div class="litespeed-row">
					<div class="litespeed-switch litespeed-label-info">' ;
		$html .= $this->build_radio($id, LiteSpeed_Cache_Config::VAL_ON, null, $checked, $disabled, $id_attr_on) ;
		$html .= $this->build_radio($id, LiteSpeed_Cache_Config::VAL_OFF, null, $checked === null ? null : !$checked, $disabled, $id_attr_off) ;
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
		$checked = $checked ? ' checked ' : '' ;
		$is_mini = $is_mini ? ' litespeed-mini ' : '' ;

		echo "<div class='litespeed-radio $is_mini'>
				<input type='checkbox' name='" . LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' id='conf_$id' value='1' $checked />
				<label for='conf_$id'>$title</label>
			</div>" ;
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
	 * @param  string $id_attr   ID for this field, set to true if want to use a not specified unique value
	 */
	public function build_radio($id, $val, $txt = null, $checked = null, $disabled = false, $id_attr = null)
	{
		if ( $checked === null ) {
			global $_options ;
			$to_be_checked = is_int($val) ? (int)$_options[$id] : $_options[$id] ;

			$checked = $to_be_checked === $val ? true : false ;
		}

		if ( $id_attr === null ) {
			$id_attr = is_int($val) ? "conf_{$id}_$val" : md5($val) ;
		}
		elseif ( $id_attr === true ) {
			$id_attr = md5($val) ;
		}

		if ( $txt === null ){
			if ( $val === LiteSpeed_Cache_Config::VAL_ON ){
				$txt = __('Enable', 'litespeed-cache') ;
			}

			if ( $val === LiteSpeed_Cache_Config::VAL_OFF ){
				$txt = __('Disable', 'litespeed-cache') ;
			}
		}

		$checked = $checked ? ' checked ' : '' ;
		$disabled = $disabled ? ' disabled ' : '' ;

		return "<input type='radio' "
			. " name='". LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' "
			. " id='$id_attr' "
			. " value='$val' "
			. " $checked "
			. " $disabled "
			. " />"
			. " <label for='$id_attr'>$txt</label>" ;
	}

	/**
	 * Display default value
	 *
	 * @since  1.1.1
	 * @access public
	 * @param  string $id The setting tag
	 */
	public function recommended($id) {
		$val = isset($this->default_settings[$id]) ? $this->default_settings[$id] : '' ;
		if ( $val ) {
			echo sprintf(__('Recommended value: %s.', 'litespeed-cache'), $val) ;
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
}
