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

	private $config ;
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
			add_action( 'wp_before_admin_bar_render', array( LiteSpeed_Cache_GUI::get_instance(), 'backend_shortcut' ) ) ;

			add_action('admin_enqueue_scripts', array($this, 'check_messages')) ;// We can do this bcos admin_notices hook is after admin_enqueue_scripts hook in wp-admin/admin-header.php
		}

		/**
		 * In case this is called outside the admin page
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;
		}

		// add menus ( Also check for mu-plugins)
		if ( $is_network_admin && ( is_plugin_active_for_network( LSCWP_BASENAME ) || defined( 'LSCWP_MU_PLUGIN' ) ) ) {
			add_action('network_admin_menu', array($this, 'register_admin_menu')) ;
		}
		else {
			add_action('admin_menu', array($this, 'register_admin_menu')) ;
		}

		$this->config = LiteSpeed_Cache_Config::get_instance() ;

		// get default setting values
		$this->default_settings = $this->config->get_default_options() ;
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

		if( defined( 'LITESPEED_ON' ) ) {
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
	public function form_action( $action, $type = false )
	{
		echo '<input type="hidden" name="' . LiteSpeed_Cache::ACTION_KEY . '" value="' . $action . '" />' ;
		if ( $type ) {
			echo '<input type="hidden" name="type" value="' . $type . '" />' ;
		}
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

			if ( ! is_network_admin() ) {
				$this->add_submenu(__('Image Optimization', 'litespeed-cache'), 'lscache-optimization', 'show_optimization') ;
				$this->add_submenu(__('Crawler', 'litespeed-cache'), 'lscache-crawler', 'show_crawler') ;
				$this->add_submenu(__('Report', 'litespeed-cache'), 'lscache-report', 'show_report') ;
				$this->add_submenu(__('Import / Export', 'litespeed-cache'), 'lscache-import', 'show_import_export') ;
			}

			defined( 'LSCWP_LOG' ) && $this->add_submenu(__('Debug Log', 'litespeed-cache'), 'lscache-debug', 'show_debug_log') ;

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
		wp_enqueue_style(LiteSpeed_Cache::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'css/litespeed.css', array(), LiteSpeed_Cache::PLUGIN_VERSION, 'all') ;
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public function enqueue_scripts()
	{
		wp_register_script( LiteSpeed_Cache::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'js/litespeed-cache-admin.js', array(), LiteSpeed_Cache::PLUGIN_VERSION, false ) ;

		$localize_data = array() ;
		if ( LiteSpeed_Cache_GUI::has_whm_msg() ) {
			$ajax_url_dismiss_whm = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_WHM, true ) ;
			$localize_data[ 'ajax_url_dismiss_whm' ] = $ajax_url_dismiss_whm ;
		}

		if ( LiteSpeed_Cache_GUI::has_msg_ruleconflict() ) {
			$ajax_url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_EXPIRESDEFAULT, true ) ;
			$localize_data[ 'ajax_url_dismiss_ruleconflict' ] = $ajax_url ;
		}

		if ( LiteSpeed_Cache_GUI::has_promo_msg() || LiteSpeed_Cache_GUI::has_promo_msg( 'slack' ) ) {
			$ajax_url_promo = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_PROMO, true ) ;
			$localize_data[ 'ajax_url_promo' ] = $ajax_url_promo ;
		}

		if ( $localize_data ) {
			wp_localize_script(LiteSpeed_Cache::PLUGIN_NAME, 'litespeed_data', $localize_data ) ;
		}

		wp_enqueue_script( LiteSpeed_Cache::PLUGIN_NAME ) ;
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
	 * Change the admin footer text on LiteSpeed Cache admin pages.
	 *
	 * @since  1.0.13
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text($footer_text)
	{
		require_once LSCWP_DIR . 'admin/tpl/inc/admin_footer.php' ;

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
		require_once LSCWP_DIR . 'admin/tpl/inc/help_tabs.php' ;
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
	 * Display info notice
	 *
	 * @since 1.6.5
	 * @access public
	 */
	public static function info( $msg )
	{
		self::add_notice( self::NOTICE_BLUE, $msg ) ;
	}

	/**
	 * Display note notice
	 *
	 * @since 1.6.5
	 * @access public
	 */
	public static function note( $msg )
	{
		self::add_notice( self::NOTICE_YELLOW, $msg ) ;
	}

	/**
	 * Display success notice
	 *
	 * @since 1.6
	 * @access public
	 */
	public static function succeed( $msg )
	{
		self::add_notice( self::NOTICE_GREEN, $msg ) ;
	}

	/**
	 * Display error notice
	 *
	 * @since 1.6
	 * @access public
	 */
	public static function error( $msg )
	{
		self::add_notice( self::NOTICE_RED, $msg ) ;
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
		require LSCWP_DIR . 'admin/tpl/esi_widget_edit.php' ;
	}

	/**
	 * Displays the cache management page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_menu_manage()
	{
		require_once LSCWP_DIR . 'admin/tpl/manage.php' ;
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
			require_once LSCWP_DIR . 'admin/tpl/network_settings.php' ;
		}
		else {
			if ( $_GET['page'] != 'litespeedcache' ) {// ls settings msg need to display manually
				settings_errors() ;
			}
			require_once LSCWP_DIR . 'admin/tpl/settings.php' ;
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
		require_once LSCWP_DIR . 'admin/tpl/edit_htaccess.php' ;
	}

	/**
	 * Outputs the html for the Environment Report page.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public function show_report()
	{
		require_once LSCWP_DIR . 'admin/tpl/report.php' ;
	}

	/**
	 * Outputs the html for the Import/Export page.
	 *
	 * @since 1.8.2
	 * @access public
	 */
	public function show_import_export()
	{
		require_once LSCWP_DIR . 'admin/tpl/import_export.php' ;
	}

	/**
	 * Outputs the crawler operation page.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function show_crawler()
	{
		require_once LSCWP_DIR . 'admin/tpl/crawler.php' ;
	}

	/**
	 * Outputs the optimization operation page.
	 *
	 * @since 1.6
	 * @access public
	 */
	public function show_optimization()
	{
		require_once LSCWP_DIR . 'admin/tpl/image_optimization.php' ;
	}

	/**
	 * Outputs the debug log.
	 *
	 * @since 1.1.5
	 * @access public
	 */
	public function show_debug_log()
	{
		require_once LSCWP_DIR . 'admin/tpl/debug_log.php' ;
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
		require_once LSCWP_DIR . 'admin/tpl/inc/show_display_installed.php' ;
	}

	/**
	 * Display error cookie msg.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public static function show_error_cookie()
	{
		require_once LSCWP_DIR . 'admin/tpl/inc/show_error_cookie.php' ;
	}

	/**
	 * Display warning if lscache is disabled
	 *
	 * @since 2.1
	 * @access public
	 */
	public function cache_disabled_warning()
	{
		include LSCWP_DIR . "admin/tpl/inc/check_cache_disabled.php" ;
	}

	/**
	 * Build a textarea
	 *
	 * @since 1.1.0
	 * @since  1.7 Changed cols param order to be the 2nd from 4th
	 * @access public
	 * @param  string $id
	 * @param  string $val Value of input
	 * @param  boolean $disabled If this input is disabled or not
	 * @param  int $cols The width of textarea
	 */
	public function build_textarea( $id, $cols = false, $val = null, $disabled = false )
	{
		if ( strpos( $id, '[' ) === false ) {
			if ( $val === null ) {
				global $_options ;
				$val = $_options[$id] ;
			}

			$id = "[$id]" ;
		}

		$disabled = $disabled ? ' disabled ' : '' ;

		if ( ! $cols ) {
			$cols = 80 ;
		}

		echo "<textarea name='" . LiteSpeed_Cache_Config::OPTION_NAME . "$id' rows='5' cols='$cols' $disabled>" . esc_textarea($val) . "</textarea>" ;
	}

	/**
	 * Build a textarea based on separate stored option data
	 *
	 * @since 1.5
	 * @since  1.7 Changed cols param order to be the 2nd from 4th
	 * @access public
	 * @param  string $id
	 * @param  int $cols The width of textarea
	 */
	public function build_textarea2( $id, $cols = false )
	{
		// Get default val for separate item
		$default_val = $this->config->default_item( $id ) ;

		$val = get_option( $id, $default_val ) ;

		if ( is_array( $val ) ) {
			$val = implode( "\n", $val ) ;
		}

		$this->build_textarea( $id, $cols, $val ) ;
	}

	/**
	 * Build a text input field
	 *
	 * @since 1.1.0
	 * @since 1.7 Added [] check and wrapper to $id, moved $readonly/$id_attr
	 * @access public
	 * @param  string $id
	 * @param  string $style     Appending styles
	 * @param  boolean $readonly If is readonly
	 * @param  string $id_attr   ID for this field
	 * @param  string $val       Field value
	 * @param  string $attrs     Additional attributes
	 * @param  string $type      Input type
	 */
	public function build_input( $id, $style = false, $val = null, $id_attr = null, $attrs = '', $type = 'text', $readonly = false )
	{
		if ( strpos( $id, '[' ) === false ) {
			if ( $val === null ) {
				global $_options ;
				$val = $_options[ $id ] ;
			}

			$id = "[$id]" ;
		}

		$readonly = $readonly ? ' readonly ' : '' ;
		if ( $id_attr !== null ) {
			$id_attr = " id='$id_attr' " ;
		}

		if ( $type == 'text' ) {
			$style = "litespeed-regular-text $style" ;
		}

		echo "<input type='$type' class='$style' name='" . LiteSpeed_Cache_Config::OPTION_NAME . "$id' value='" . esc_textarea( $val ) ."' $readonly $id_attr $attrs /> " ;
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
	public function build_checkbox($id, $title, $checked, $value = 1 )
	{
		$checked = $checked ? ' checked ' : '' ;

		$label_id = str_replace( array( '[', ']' ), '_', $id ) ;

		if ( $value !== 1 ) {
			$label_id .= '_' . $value ;
		}

		echo "<div class='litespeed-tick'>
				<label for='conf_$label_id'>$title</label>
				<input type='checkbox' name='" . LiteSpeed_Cache_Config::OPTION_NAME . "[$id]' id='conf_$label_id' value='$value' $checked />
			</div>" ;
	}

	/**
	 * Build a toggle checkbox html snippet
	 *
	 * @since 1.7
	 */
	public function build_toggle( $id, $checked = null, $title_on = null, $title_off = null )
	{
		if ( strpos( $id, '[' ) === false ) {
			if ( $checked === null ) {
				global $_options ;
				$to_be_checked = null ;
				if ( isset( $_options[ $id ] ) ) {
					$to_be_checked = $_options[ $id ] ;
				}
				$checked = $to_be_checked ? true : false ;
			}
			$id = "[$id]" ;
		}
		$checked = $checked ? 1 : 0 ;

		if ( $title_on === null ) {
			$title_on = __( 'ON', 'litespeed-cache' ) ;
			$title_off = __( 'OFF', 'litespeed-cache' ) ;
		}

		if ( $checked ) {
			$cls = 'primary' ;
		}
		else {
			$cls = 'default litespeed-toggleoff' ;
		}

		echo "<div class='litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-$cls' data-litespeed-toggle-on='primary' data-litespeed-toggle-off='default'>
				<input name='" . LiteSpeed_Cache_Config::OPTION_NAME . "$id' type='hidden' value='$checked' />
				<div class='litespeed-toggle-group'>
					<label class='litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on'>$title_on</label>
					<label class='litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off'>$title_off</label>
					<span class='litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default'></span>
				</div>
			</div>" ;
	}

	/**
	 * Build a switch div html snippet
	 *
	 * @since 1.1.0
	 * @since 1.7 removed param $disable
	 * @access public
	 * @param  string $id
	 * @param  boolean $return   Return the html or echo it
	 * @param  boolean $checked  If the value is on
	 * @param  string $id_attr   ID for this field, set to true if want to use a not specified unique value
	 */
	public function build_switch($id, $checked = null, $return = false, $id_attr = null)
	{
		$id_attr_on = $id_attr === null ? null : $id_attr . '_' . LiteSpeed_Cache_Config::VAL_ON ;
		$id_attr_off = $id_attr === null ? null : $id_attr . '_' . LiteSpeed_Cache_Config::VAL_OFF ;
		$html = '<div class="litespeed-switch">' ;
		$html .= $this->build_radio($id, LiteSpeed_Cache_Config::VAL_OFF, null, $checked === null ? null : !$checked, $id_attr_off) ;
		$html .= $this->build_radio($id, LiteSpeed_Cache_Config::VAL_ON, null, $checked, $id_attr_on) ;
		$html .= '</div>' ;

		if ( $return ) {
			return $html ;
		}
		else {
			echo $html ;
		}
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
	public function build_radio($id, $val, $txt = null, $checked = null, $id_attr = null)
	{
		if ( strpos( $id, '[' ) === false ) {
			if ( $checked === null ) {
				global $_options ;
				$to_be_checked = null ;
				if ( isset( $_options[ $id ] ) ) {
					$to_be_checked = is_int( $val ) ? (int)$_options[ $id ] : $_options[ $id ] ;
				}

				$checked = $to_be_checked === $val ? true : false ;
			}

			$id = "[$id]" ;
		}

		if ( $id_attr === null ) {
			$id_attr = is_int($val) ? "conf_" . str_replace( array( '[', ']' ), '_', $id ) . "_$val" : md5($val) ;
		}
		elseif ( $id_attr === true ) {
			$id_attr = md5($val) ;
		}

		if ( $txt === null ){
			if ( $val === LiteSpeed_Cache_Config::VAL_ON ){
				$txt = __( 'ON', 'litespeed-cache' ) ;
			}

			if ( $val === LiteSpeed_Cache_Config::VAL_OFF ){
				$txt = __( 'OFF', 'litespeed-cache' ) ;
			}
		}

		$checked = $checked ? ' checked ' : '' ;

		return "<input type='radio' name='". LiteSpeed_Cache_Config::OPTION_NAME . "$id' id='$id_attr' value='$val' $checked /> <label for='$id_attr'>$txt</label>" ;
	}

	/**
	 * Display default value
	 *
	 * @since  1.1.1
	 * @access public
	 * @param  string $id The setting tag
	 */
	public function recommended( $id )
	{
		$val = isset($this->default_settings[$id]) ? $this->default_settings[$id] : '' ;
		if ( $val ) {
			if ( ! is_numeric( $val ) && strpos( $val, "\n" ) !== false ) {
				$val = "<textarea readonly rows='5' class='litespeed-left10'>$val</textarea>" ;
			}
			else {
				$val = "<code>$val</code>" ;
			}
			echo sprintf( __( 'Recommended value: %s', 'litespeed-cache' ), $val ) ;
		}
	}

	/**
	 * Display API environment variable support
	 *
	 * @since  1.8.3
	 * @access private
	 */
	private function _api_env_var()
	{
		$args = func_get_args() ;
		$s = '<code>' . implode( '</code>, <code>', $args ) . '</code>' ;

		echo '<font class="litespeed-success"> '
			. __( 'API', 'litespeed-cache' ) . ': '
			. sprintf( __( 'Server variable(s) %s available to override this setting.', 'litespeed-cache' ), $s )
			. ' <a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:server_variables" target="_blank">'
				. __( 'Learn More', 'litespeed-cache' )
			. '</a>' ;
	}

	/**
	 * Return groups string
	 *
	 * @since  2.0
	 * @access public
	 */
	public static function print_plural( $num, $kind = 'group' )
	{
		if ( $num > 1 ) {
			switch ( $kind ) {
				case 'group' :
					return sprintf( __( '%s groups', 'litespeed-cache' ), $num ) ;

				case 'image' :
					return sprintf( __( '%s images', 'litespeed-cache' ), $num ) ;

				default:
					return $num ;
			}

		}

		switch ( $kind ) {
			case 'group' :
				return sprintf( __( '%s group', 'litespeed-cache' ), $num ) ;

			case 'image' :
				return sprintf( __( '%s image', 'litespeed-cache' ), $num ) ;

			default:
				return $num ;
		}
	}

	/**
	 * Return guidance html
	 *
	 * @since  2.0
	 * @access public
	 */
	public static function guidance( $title, $steps, $current_step )
	{
		if ( $current_step === 'done' ) {
			$current_step = count( $steps ) + 1 ;
		}

		$percentage = ' (' . floor( ( $current_step - 1 ) * 100 / count( $steps ) ) . '%)' ;

		$html = '<div class="litespeed-guide">'
					. '<h2>' . $title . $percentage . '</h2>'
					. '<ol>' ;
		foreach ( $steps as $k => $v ) {
			$step = $k + 1 ;
			if ( $current_step > $step ) {
				$html .= '<li class="litespeed-guide-done">' ;
			}
			else {
				$html .= '<li>' ;
			}
			$html .= $v . '</li>' ;
		}

		$html .= '</ol></div>' ;

		return $html ;
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
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
