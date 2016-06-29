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
	private static $instance;

	const NOTICE_BLUE = 'notice notice-info';
	const NOTICE_GREEN = 'notice notice-success';
	const NOTICE_RED = 'notice notice-error';
	const NOTICE_YELLOW = 'notice notice-warning';

	const PURGEBY_CAT = '0';
	const PURGEBY_PID = '1';
	const PURGEBY_TAG = '2';
	const PURGEBY_URL = '3';

	const PURGEBYOPT_SELECT = 'purgeby';
	const PURGEBYOPT_LIST = 'purgebylist';

	private $notices = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function __construct()
	{
	}

	/**
	 * Get the LiteSpeed_Cache_Admin_Display object.
	 *
	 * @since 1.0.7
	 * @access public
	 * @return LiteSpeed_Cache_Admin_Display Static instance of the
	 *  LiteSpeed_Cache_Admin_Display class.
	 */
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new LiteSpeed_Cache_Admin_Display();
		}
		return self::$instance;
	}

	/**
	 * Displays the help tab in the admin pages.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function add_help_tabs()
	{
		$screen = get_current_screen() ;
		$screen->add_help_tab(array(
			'id' => 'lsc-overview',
			'title' => __('Overview', 'litespeed-cache'),
			'content' => '<p>' . __('LiteSpeed Cache is a page cache built into LiteSpeed Web Server. This plugin communicates with LiteSpeed Web Server to let it know which pages are cache-able and when to purge them.', 'litespeed-cache') . '</p>' .
			'<p>' . __('You must have the LSCache module installed and enabled in your LiteSpeed Web Server setup.', 'litespeed-cache') . '</p>',
		)) ;

		$screen->add_help_tab(array(
			'id' => 'lst-purgerules',
			'title' => __('Auto Purge Rules', 'litespeed-cache'),
			'content' => '<p>' . __('You can set what pages will be purged when a post is published or updated.', 'litespeed-cache') . '</p>',
		)) ;

		$screen->set_help_sidebar(
				'<p><strong>' . __('For more information:', 'litespeed-cache') . '</strong></p>' .
				'<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache" target="_blank">' . __('LSCache Documentation', 'litespeed-cache') . '</a></p>' .
				'<p><a href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank">' . __('Support Forum', 'litespeed-cache') . '</a></p>'
		) ;
	}

	/**
	 * Check to make sure that caching is enabled.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $config The current configurations.
	 * @return mixed True if enabled, error message otherwise.
	 */
	private function check_license($config)
	{
		if ($config->is_caching_allowed() == false) {
			return __('Notice: Your installation of LiteSpeed Web Server does not have LSCache enabled.', 'litespeed-cache')
			. __(' This plugin will NOT work properly.', 'litespeed-cache');
		}
		return true ;
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
		return '<div class="' . $color . ' is-dismissible"><p>'
					. $str . '</p></div>';
	}

	/**
	 * Adds a notice to display on the admin page. Multiple messages of the same
	 * color may be added in a single call. If the list is empty, this method
	 * will add the action to display notices.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $color One of the available constants provided by this class.
	 * @param mixed $msg May be a string for a single message or an array for multiple.
	 */
	public function add_notice($color, $msg)
	{
		if (empty($this->notices)) {
			add_action(
				(is_network_admin() ? 'network_admin_notices' : 'admin_notices'),
				array($this, 'display_notices'));
		}
		if (!is_array($msg)) {
			$this->notices[] = self::build_notice($color, $msg);
			return;
		}
		foreach ($msg as $str) {
			$this->notices[] = self::build_notice($color, $str);
		}
	}

	/**
	 * Callback function to display any notices from editing cache settings.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public function display_notices()
	{
		foreach ($this->notices as $msg) {
			echo $msg;
		}
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
		if (!is_numeric($widget->number) && (!isset($_REQUEST['editwidget']))) {
			return;
		}
		$enable_levels = array(
			LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE => __('Disable', 'litespeed-cache'),
			LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE => __('Enable', 'litespeed-cache'));

		$options = LiteSpeed_Cache::get_widget_option($widget);
		if (empty($options)) {
			$esi = LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE;
			$ttl = '300'; // 5 minutes default for widgets.
		}
		else {
			$esi = $options[LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE];
			$ttl = $options[LiteSpeed_Cache_Config::WIDGET_OPID_TTL];
		}

		$buf = '<h4>LiteSpeed Cache:</h4>';

		$buf .= '<label for="' . LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE
			. '">' . __('Enable ESI for this Widget:', 'litespeed-cache')
			. '&nbsp;&nbsp;&nbsp;</label>';

		$buf .= $this->input_field_radio(LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE,
			$enable_levels, $esi);

		$buf .= '<br><br>';

		$buf .= '<label for="' . LiteSpeed_Cache_Config::WIDGET_OPID_TTL
			. '">' . __('Widget Cache TTL:', 'litespeed-cache')
			. '&nbsp;&nbsp;&nbsp;</label>';

		$buf .= $this->input_field_text(LiteSpeed_Cache_Config::WIDGET_OPID_TTL,
			$ttl, '7', '', __('seconds', 'litespeed-cache'));

		$buf .= '<p class="install-help">'
			. __('Default value 300 seconds (5 minutes).', 'litespeed-cache')
			. __(' A TTL of 0 indicates do not cache.', 'litespeed-cache')
			. '</p>';

		$buf .= '<br><br>';
		echo $buf;
	}

	/**
	 * add_submenu_page callback to determine which submenu page to display
	 * if the admin selected a LiteSpeed Cache dashboard page.
	 *
	 * @since 1.0.4
	 */
	public function show_menu_select()
	{
		$page = $_REQUEST['page'];
		if (strncmp($page, 'lscache-', 8) != 0) {
			// either I messed up writing the slug, or someone entered this function elsewhere.
			die();
		}
		$selection = substr($page, 8);
		$selection_len = strlen($selection);

		//install, faqs
		switch($selection[0]) {
			case 'f':
				if (($selection_len == 4)
						&& (strncmp($selection, 'faqs', $selection_len) == 0)) {
					$this->show_menu_faqs();
				}
				break;
			case 'i':
				if (($selection_len == 4)
						&& (strncmp($selection, 'info', $selection_len) == 0)) {
					$this->show_menu_info();
				}
				break;
			case 's':
				if (($selection_len == 8)
						&& (strncmp($selection, 'settings', $selection_len) == 0)) {
					$this->show_menu_network_settings();
				}
				break;
			case 'e':
				if (($selection_len == 13)
						&& (strncmp($selection, 'edit-htaccess', $selection_len) == 0)) {
					$this->show_menu_edit_htaccess();
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Displays the cache management page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_menu_manage()
	{
		$config = LiteSpeed_Cache::config() ;

		if ( ($error_msg = $this->check_license($config)) !== true ) {
			echo '<div class="error"><p>' . $error_msg . '</p></div>' . "\n" ;
			return ;
		}

		$purgeby_options = array(
			__('Category', 'litespeed-cache'),
			__('Post ID', 'litespeed-cache'),
			__('Tag', 'litespeed-cache'),
			__('URL', 'litespeed-cache')
		);

		// Page intro
		$buf = '<div class="wrap"><h2>' . __('LiteSpeed Cache Management', 'litespeed-cache') . '</h2>'
		. '<div class="welcome-panel"><p>'
		. __('LiteSpeed Cache is maintained and managed by LiteSpeed Web Server.', 'litespeed-cache')
		. __(' You can inform LiteSpeed Web Server to purge cached contents from this screen.', 'litespeed-cache')
		. '</p>'
		. '<p>' . __('More options will be added here in future releases.', 'litespeed-cache') . '</p>' ;

		// Begin form
		$buf .= '<form method="post">'
		. '<input type="hidden" name="lscwp_management" value="manage_lscwp" />'
		. wp_nonce_field('lscwp_manage', 'management_run') ;

		// Form entries purge front, purge all
		$buf .= '<input type="submit" class="button button-primary" '
		. 'name="purgefront" value="' . __('Purge Front Page', 'litespeed-cache')
		. '" /><br><br>'
		. '<input type="submit" class="button button-primary" name="purgeall"'
		. 'id="litespeedcache-purgeall" value="' . __('Purge All', 'litespeed-cache')
		. '" /><br>';

		if ((is_multisite()) && (is_network_admin())) {
			echo $buf
			. $this->input_field_hidden('litespeedcache-purgeall-confirm',
				__('This will purge everything for all blogs.', 'litespeed-cache')
					. __(' Are you sure you want to purge all?', 'litespeed-cache'))
			. "<br><br></form></div></div>\n";
			return;
		}

		$buf .= $this->input_field_hidden('litespeedcache-purgeall-confirm',
			__('Are you sure you want to purge all?', 'litespeed-cache'));


		// Purge by description.
		$buf .= '<h3>' . __('Purge By...', 'litespeed-cache') . '</h3>'
		. '<p>' . __('You may purge selected pages here.', 'litespeed-cache')
		. __(' Currently, the available options are:', 'litespeed-cache')
		. '</p>'
		. '<ul>'
		. '<li><p>' . __('Category: Purge category pages by name.', 'litespeed-cache')
		. '</p></li>'
		. '<li><p>' . __('Post ID: Purge pages by post ID.', 'litespeed-cache')
		. '</p></li>'
		. '<li><p>' . __('Tag: Purge tag pages by name.', 'litespeed-cache')
		. '</p></li>'
		. '<li><p>' . __('URL: Purge pages by locator.', 'litespeed-cache')
		. ' Ex: http://www.myexamplesite.com<b><u>/2016/02/24/hello-world/</u></b>'
		. '</p></li>'
		. '</ul>';

		if (($_POST) && ($_POST[LiteSpeed_Cache_Config::OPTION_NAME])) {
			$selected = $_POST[LiteSpeed_Cache_Config::OPTION_NAME][self::PURGEBYOPT_SELECT];
			if ((intval($selected) < 0) || (intval($selected) > 3)) {
				$selected = 0;
			}
		}
		else {
			$selected = 0;
		}
		$buf .= $this->input_field_select(self::PURGEBYOPT_SELECT,
				$purgeby_options, $selected)
		. '<br><br>' . $this->input_field_textarea(self::PURGEBYOPT_LIST, '', '5', '80')
		. '<br><br>'
		. '<input type="submit" class="button button-primary" '
		. 'name="purgelist" value="' . __('Purge List', 'litespeed-cache')
		. '" />';

		// End form
		$buf .= "<br><br></form></div></div>";

		echo $buf;
	}

	/**
	 * Outputs the LiteSpeed Cache settings page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_menu_settings()
	{
		$config = LiteSpeed_Cache::config() ;

		if ( ($error_msg = $this->check_license($config)) !== true ) {
			echo '<div class="error"><p>' . $error_msg . '</p></div>' . "\n" ;

		}

		$options = $config->get_options() ;
		$purge_options = $config->get_purge_options() ;

		echo '<div class="wrap">
		<h2>' . __('LiteSpeed Cache Settings', 'litespeed-cache')
		. '<span style="font-size:0.5em">v' . LiteSpeed_Cache::PLUGIN_VERSION . '</span></h2>
		<form method="post" action="options.php">' ;

		settings_fields(LiteSpeed_Cache_Config::OPTION_NAME) ;

		$compatibilities_tab = '';
		$compatibilities_settings = '';
		$compatibilities_buf = $this->show_settings_compatibilities();
		if (!empty($compatibilities_buf)) {
			$compatibilities_tab .= '<li><a href="#wp-compatibilities-settings">'
					. __('Plugin Compatibilities', 'litespeed-cache') . '</a></li>';
			$compatibilities_settings .= '<div id ="wp-compatibilities-settings">'
							. $compatibilities_buf .
							'</div>';
		}

		$advanced_tab = '';
		$advanced_settings = '';
		if (!is_multisite()) {
			$advanced_tab = '<li><a href="#advanced-settings">'
					. __('Advanced Settings', 'litespeed-cache') . '</a></li>';
			$advanced_settings = '<div id="advanced-settings">'
					. $this->show_settings_advanced($options) . '</div>';
		}

		echo '
		 <div id="lsc-tabs">
		 <ul>
		 <li><a href="#general-settings">' . __('General', 'litespeed-cache') . '</a></li>
		 <li><a href="#purge-settings">' . __('Purge Rules', 'litespeed-cache') . '</a></li>
		 <li><a href="#exclude-settings">' . __('Do Not Cache Rules', 'litespeed-cache') . '</a></li>
	 	 ' . $advanced_tab . '
		 <li><a href="#debug-settings">' . __('Debug', 'litespeed-cache') . '</a></li>'
		. $compatibilities_tab . '
		</ul>
		 <div id="general-settings">'
		. $this->show_settings_general($options) .
		'</div>
		<div id="purge-settings">'
		. $this->show_settings_purge($config->get_purge_options()) .
		'</div>
		<div id="exclude-settings">'
		. $this->show_settings_excludes($options) .
		'</div>'
		. $advanced_settings .
		'<div id ="debug-settings">'
		. $this->show_settings_test($options) .
		'</div>'
		. $compatibilities_settings . '</div>' ;

		submit_button() ;
		echo "</form></div>\n" ;
	}

	/**
	 * Display the network admin settings page.
	 *
	 * Since multisite setups only have one .htaccess file, these settings
	 * are only available for the network admin in multisite setups.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function show_menu_network_settings()
	{
		$network_desc = __('These configurations are only available network wide.', 'litespeed-cache')
		. '<br>'
		. __('Separate Mobile Views should be enabled if any of the network enabled themes require a different view for mobile devices.', 'litespeed-cache')
		. __(' Responsive themes can handle this part automatically.', 'litespeed-cache');

		$buf = '<div class="wrap"><h2>' . __('LiteSpeed Cache Settings', 'litespeed-cache') . '</h2>';

		$config = LiteSpeed_Cache::config();

		$buf .= '<form method="post" action="admin.php?page=lscache-settings">'
		. '<input type="hidden" name="lscwp_settings_save" value="save_settings" />'
		. wp_nonce_field('lscwp_settings', 'save');

		$buf .= '<div id="lsc-tabs">'
		. '<ul>'
		. '<li><a href="#general">' . __('General', 'litespeed-cache') . '</a></li>'
		. '<li><a href="#exclude">' . __('Do Not Cache Rules', 'litespeed-cache') . '</a></li>'
		. '<li><a href="#advanced">' . __('Advanced', 'litespeed-cache') . '</a></li>'
		. '</ul>';

		$buf .= '<div id="general">'
		. $this->input_group_start(__('General Network Configurations',
				'litespeed-cache'), $network_desc);
		$id = LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED;

		$site_options = $config->get_site_options();

		$network_enable = $this->input_field_checkbox('lscwp_' . $id, $id,
				$site_options[$id]) ;
		$buf .= $this->display_config_row(
		__('Network Enable Cache', 'litespeed-cache'), $network_enable,
		__('Enabling LiteSpeed Cache for WordPress here enables the cache for the network.', 'litespeed-cache')
		. '<br>'
		. wp_kses(__('We <b>STRONGLY</b> recommend that you test the compatibility with other plugins on a single/few sites.', 'litespeed-cache'),
				array('b' => array()))
		. __('This is to ensure compatibility prior to enabling the cache for all sites.', 'litespeed-cache'));

		$buf .= $this->build_setting_mobile_view($site_options);
		$buf .= $this->input_group_end() . '</div>';

		$buf .= '<div id="exclude">'
		. $this->input_group_start(__('Network Do Not Cache Rules', 'litespeed-cache'));
		$ua_title = '';
		$ua_desc = '';
		$ua_buf = $this->build_setting_exclude_useragent($ua_title, $ua_desc);
		$buf .= $this->display_config_row(__('Do Not Cache User Agents', 'litespeed-cache'), $ua_buf, $ua_desc);

		$cookie_title = '';
		$cookie_desc = '';
		$cookie_buf = $this->build_setting_exclude_cookies($cookie_title, $cookie_desc);
		$buf .= $this->display_config_row(__('Do Not Cache Cookies', 'litespeed-cache'), $cookie_buf, $cookie_desc);

		$buf .= $this->input_group_end() . '</div>';

		$buf .= '<div id="advanced">'
		. $this->input_group_start(__('Advanced Network Settings', 'litespeed-cache'));

		$login_cookie_title = '';
		$login_cookie_desc = '';
		$login_cookie_buf = $this->build_setting_login_cookie($site_options,
				$login_cookie_title, $login_cookie_desc);
		$buf .= $this->display_config_row($login_cookie_title,
				$login_cookie_buf, $login_cookie_desc);
		$buf .= $this->input_group_end() . '</div></div>';

		$buf .= '<br><br>'
		. '<input type="submit" class="button button-primary" name="submit" value="'
		. __('Save', 'litespeed-cache') . '" /></td></tr>';
		$buf .= '</form><br><br></div>';
		echo $buf;
	}

	/**
	 * Displays the edit_htaccess admin page.
	 *
	 * This function will try to load the .htaccess file contents.
	 * If it fails, it will echo the error message.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function show_menu_edit_htaccess()
	{
		$buf = '<div class="wrap"><h2>' . __('LiteSpeed Cache Edit .htaccess', 'litespeed-cache') . '</h2>';
		$buf .= '<div class="welcome-panel">';
		$contents = '';
		$rules = LiteSpeed_Cache_Admin_Rules::get_instance();
		if ($rules->file_get($contents) === false) {
			$buf .= '<h3>' . $contents . '</h3></div>';
			echo $buf;
			return;
		}
		$file_writable = $rules->is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);

		$buf .= '<p><span class="attention">' . __('WARNING: This page is meant for advanced users.', 'litespeed-cache')
		. '</span><br>'
		. __(' Any changes made to the .htaccess file may break your site.', 'litespeed-cache')
		. __(' Please consult your host/server admin before making any changes you are unsure about.', 'litespeed-cache')
		. '</p>';

		$buf .= $this->show_info_common_rewrite();

		$buf .= '<form method="post" action="admin.php?page=lscache-edit-htaccess">';
		$buf .= '<input type="hidden" name="lscwp_htaccess_save" value="save_htaccess" />';
		$buf .= wp_nonce_field('lscwp_edit_htaccess', 'save');

		$buf .= '<h3>' . sprintf(__('Current %s contents:', 'litespeed-cache'), '.htaccess') . '</h3>';

		$buf .= '<p><span class="attention">'
		. __('DO NOT EDIT ANYTHING WITHIN ', 'litespeed-cache') . '###LSCACHE START/END XXXXXX###'
		. '</span><br>'
		. __('These are added by the LS Cache plugin and may cause problems if they are changed.', 'litespeed-cache')
		. '</p>';

		$buf .= '<textarea id="wpwrap" name="lscwp_ht_editor" wrap="off" rows="20" class="code" ';
		if (!$file_writable) {
			$buf .= 'readonly';
		}
		$buf .= '>' . esc_textarea($contents) . '</textarea>';
		unset($contents);

		$buf .= '<input type="submit" class="button button-primary" name="submit" value="'
				. __('Save', 'litespeed-cache') . '" /></form><br><br>';

		$buf .= '</div></div>';
		echo $buf;
	}

	/**
	 * Outputs the html for the info page.
	 *
	 * This page includes three tabs:
	 * - configurations
	 * - third party plugin compatibilities
	 * - common rewrite rules.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function show_menu_info()
	{
		// Configurations help.
		$buf = '<div class="wrap"><h2>'
		. __('LiteSpeed Cache Information', 'litespeed-cache') . '</h2>';

		$buf .= '<div id="lsc-tabs">'
		. '<ul>'
		. '<li><a href="#config">' . __('Configurations', 'litespeed-cache') . '</a></li>'
		. '<li><a href="#compat">' . __('Plugin Compatibilities', 'litespeed-cache') . '</a></li>'
		. '<li><a href="#commonrw">' . __('Common Rewrite Rules', 'litespeed-cache') . '</a></li>'
		. '<li><a href="#adminip">' . __('Admin IP Commands', 'litespeed-cache') . '</a></li>'
		. '</ul>';

		$buf .= '<div id="config"><h3>'
		. __('LiteSpeed Cache Configurations', 'litespeed-cache') . '</h3>'
		. '<h4>' . wp_kses(__('Please check to make sure that your <b>web server cache configurations</b> are set to the following:', 'litespeed-cache'), array('b'=>array())) . '</h4>';

		$buf .= '<ul><li>Enable Public Cache - No</li>'
		. '<li>Check Public Cache - Yes</li></ul>';

		$buf .= '<h4>' . __('The following are also recommended to be set:', 'litespeed-cache') . '</h4>';

		$buf .= '<ul><li>Cache Request with Query String - Yes</li>'
		. '<li>Cache Request with Cookie - Yes</li>'
		. '<li>Cache Response with Cookie - Yes</li>'
		. '<li>Ignore Request Cache-Control - Yes</li>'
		. '<li>Ignore Response Cache-Control - Yes</li></ul>';

		$buf .= '</div>'; // id=config

		// Compatibility with other plugins.
		$buf .= '<div id="compat">';
		$buf .= $this->show_info_compatibility();
		$buf .= '</div>'; // id=compat

		$buf .= '<div id="commonrw">';
		$buf .= $this->show_info_common_rewrite();
		$buf .= '</div>'; // id=commonrw

		$buf .= '<div id="adminip">';
		$buf .= $this->show_info_admin_ip();
		$buf .= '</div>'; // id=adminip

		$buf .= '</div>'; // id=lsc_tabs
		$buf .= '<h4>'
		. sprintf(__('If your questions are not answered, try the %s', 'litespeed-cache'),
				'<a href=' . get_admin_url() . 'admin.php?page=lscache-faqs>FAQ.</a>');
		$buf .=
		sprintf(__(" If your questions are still not answered, don't hesitate to ask them on the %s", 'litespeed-cache'),
				'<a href=https://wordpress.org/support/plugin/litespeed-cache>support forum.</a>')
		. '</h4></div>'; // class=wrap
		echo $buf;
	}

	/**
	 * Outputs the html for the FAQs page.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function show_menu_faqs()
	{
		$buf =  '<div class="wrap"><h2>LiteSpeed Cache FAQs</h2>';

		$buf .= '<div class="welcome-panel"><h4>'
		. __('Is the LiteSpeed Cache Plugin for WordPress free?', 'litespeed-cache') . '</h4>'
		. '<p>'
		. __('Yes, the plugin itself will remain free and open source.', 'litespeed-cache')
		. __(' You are required to have a LiteSpeed Web Server Enterprise 5.0.10+ license with the LSCache module enabled.', 'litespeed-cache')
		. __(' An alternative to Enterprise is OpenLiteSpeed v 1.4.17+, but the functionality is currently in beta.', 'litespeed-cache')
		. '</p>';

		$buf .= '<h4>' . __('Where are the cached files stored?', 'litespeed-cache') . '</h4>'
		. '<p>' . __('This plugin only instructs LiteSpeed Web Server on what pages to cache and when to purge. ', 'litespeed-cache')
		. __('The actual cached pages are stored and managed by LiteSpeed Web Server. Nothing is stored on the PHP side.', 'litespeed-cache') . '</p>';

		$buf .= '<h4>'
		. __('Does LiteSpeed Cache for WordPress work with OpenLiteSpeed?', 'litespeed-cache')
		. '</h4><p>'
		. __('The support is currently in beta. It should work, but is not fully tested. ', 'litespeed-cache')
		. __('As well, any settings changes that require modifying the .htaccess file requires a server restart.', 'litespeed-cache')
		. '</p>';

		$buf .= '<h4>' . __('Is WooCommerce supported?', 'litespeed-cache') . '</h4>'
		. '<p>'
		. __('In short, yes. For WooCommerce versions 1.4.2 and above, this plugin will not cache the pages that WooCommerce deems non-cacheable.', 'litespeed-cache')
		. __(' For versions below 1.4.2, we do extra checks to make sure that pages are cacheable.', 'litespeed-cache')
		. __(' We are always looking for feedback, so if you encounter any problems, be sure to send us a support question.', 'litespeed-cache') . '</p>';

		$buf .= '<h4>' . __('How do I get WP-PostViews to display an updating view count?', 'litespeed-cache') . '</h4>'
		. '<ol><li>' . sprintf(__('Use %1$s to replace %2$s', 'litespeed-cache'),
				'<code>&lt;div id="postviews_lscwp"&gt;&lt;/div&gt;</code>',
				'<code>&lt;?php if(function_exists(\'the_views\')) { the_views(); } ?&gt;</code>');

		$buf .= '<ul><li>'
		. __('NOTE: The id can be changed, but the div id and the ajax function must match.', 'litespeed-cache')
		. '</li></ul>';

		$buf .= '<li>' . sprintf(__('Replace the ajax query in %1$s with %2$s', 'litespeed-cache'),
				'<code>wp-content/plugins/wp-postviews/postviews-cache.js</code>',
		'<textarea id="wpwrap" rows="11" readonly>jQuery.ajax({
    type:"GET",
    url:viewsCacheL10n.admin_ajax_url,
    data:"postviews_id="+viewsCacheL10n.post_id+"&amp;action=postviews",
    cache:!1,
    success:function(data) {
        if(data) {
            jQuery(\'#postviews_lscwp\').html(data+\' views\');
        }
   }
});</textarea>')
		. '</li>';


		$buf .= '<li>'
		. __('Purge the cache to use the updated pages.', 'litespeed-cache')
		. '</li></ul></div></div>';

		echo $buf;
	}

	/**
	 * Builds the html for the general settings tab.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $options The current configuration options.
	 * @return string The html for the general tab.
	 */
	private function show_settings_general( $options )
	{
		$buf = $this->input_group_start(__('General', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_ENABLED_RADIO;

		$enable_levels = array(
			LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE => __('Enable', 'litespeed-cache'),
			LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE => __('Disable', 'litespeed-cache')) ;

		//IF multisite: Add 'Use Network Admin' option,
		//ELSE: Change 'Enable LiteSpeed Cache' selection to 'Enabled' if the 'Use Network Admin' option was previously selected.
		//		Selection will not actually be changed unless settings are saved.
		if (is_multisite()){
			$enable_levels[LiteSpeed_Cache_Config::OPID_ENABLED_NOTSET] = __('Use Network Admin Setting', 'litespeed-cache');
		}
		else{
			if(intval($options[$id]) === 2)
				$options[$id] = 1;
		}

		$input_enable = $this->input_field_radio($id, $enable_levels, intval($options[$id])) ;

		$enable_desc = '<strong>' . __('NOTICE', 'litespeed-cache') . ':</strong>'
		. __(' When disabling the cache, all cached entries for this blog will be purged.', 'litespeed-cache');
		if( is_multisite() ){
			$enable_desc .= '<br>'
			. __('You can override network admin settings here.', 'litespeed-cache');
		}

		$buf .= $this->display_config_row(__('Enable LiteSpeed Cache', 'litespeed-cache'),
				$input_enable, $enable_desc);

		$id = LiteSpeed_Cache_Config::OPID_PUBLIC_TTL ;
		$input_public_ttl = $this->input_field_text($id, $options[$id], 10, 'regular-text',
											__('seconds', 'litespeed-cache')) ;

		$buf .= $this->display_config_row(__('Default Public Cache TTL', 'litespeed-cache'), $input_public_ttl,
				__('Required number in seconds, minimum is 30.', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL ;
		$input_public_ttl = $this->input_field_text($id, $options[$id], 10, 'regular-text',
				__('seconds', 'litespeed-cache')) ;
		$buf .= $this->display_config_row(__('Default Front Page TTL', 'litespeed-cache'), $input_public_ttl,
				__('Required number in seconds, minimum is 30.', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS ;
		$cache_commenters = $this->input_field_checkbox('check_' . $id, $id, $options[$id]) ;
		$buf .= $this->display_config_row(__('Enable Cache for Commenters', 'litespeed-cache'), $cache_commenters,
		__('When checked, commenters will not be able to see their comment awaiting moderation. ', 'litespeed-cache')
		. __('Disabling this option will display those types of comments, but the cache will not perform as well.', 'litespeed-cache'));

		if (!is_multisite()) {
			$buf .= $this->build_setting_mobile_view($options);
		}

		$buf .= $this->input_group_end() ;
		return $buf ;
	}

	/**
	 * Builds the html for the purge settings tab.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $purge_options The current configuration purge options.
	 * @return string The html for the purge tab.
	 */
	private function show_settings_purge( $purge_options )
	{
		$buf = $this->input_group_start(__('Auto Purge Rules For Publish/Update', 'litespeed-cache'),
		__('Select which pages will be automatically purged when posts are published/updated.', 'litespeed-cache')
		. '<br>'
		. '<b>' . __('Note: ', 'litespeed-cache') . '</b>'
		. __('Select "All" if you have dynamic widgets linked to posts on pages other than the front or home pages.', 'litespeed-cache')
		. __(' (Other checkboxes will be ignored)', 'litespeed-cache')
		. '<br>'
		. '<b>' . __('Note: ', 'litespeed-cache') . '</b>'
		. __('Select only the archive types that you are currently using, the others can be left unchecked.', 'litespeed-cache')) ;

		$tr = '<tr><th scope="row" colspan="2" class="th-full">' ;
		$endtr = "</th></tr>\n" ;
		$buf .= $tr ;

		$spacer = '&nbsp;&nbsp;&nbsp;' ;

		$pval = LiteSpeed_Cache_Config::PURGE_ALL_PAGES ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('All pages', 'litespeed-cache')) ;

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_FRONT_PAGE ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Front page', 'litespeed-cache')) ;

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_HOME_PAGE ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Home page', 'litespeed-cache')) ;

		$buf .= $endtr . $tr ;

		$pval = LiteSpeed_Cache_Config::PURGE_AUTHOR ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Author archive', 'litespeed-cache')) ;

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_POST_TYPE ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Post type archive', 'litespeed-cache')) ;

		$buf .= $endtr . $tr ;

		$pval = LiteSpeed_Cache_Config::PURGE_YEAR ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Yearly archive', 'litespeed-cache')) ;

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_MONTH ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Monthly archive', 'litespeed-cache')) ;

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_DATE ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Daily archive', 'litespeed-cache')) ;

		$buf .= $endtr . $tr ;

		$pval = LiteSpeed_Cache_Config::PURGE_TERM ;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options), __('Term archive (include category, tag, and tax)', 'litespeed-cache')) ;

		$buf .= $endtr ;
		$buf .= $this->input_group_end() ;
		return $buf ;
	}

	/**
	 * Builds the html for the do not cache settings tab.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param array $options The current configuration options.
	 * @return string The html for the do not cache tab.
	 */
	private function show_settings_excludes( $options )
	{

		$uri_description =
			__('Enter a list of urls that you do not want to have cached.', 'litespeed-cache')
			. '<br>'
			. __('The urls will be compared to the REQUEST_URI server variable.', 'litespeed-cache')
			. '<br>'
			. __('There should only be one url per line.', 'litespeed-cache')
			. '<br><br>
			<b>' . __('NOTE: ', 'litespeed-cache') . '</b>' . __('URLs must start with a \'/\' to be correctly matched.', 'litespeed-cache')
			. '<br>'
			. __('To do an exact match, add \'$\' to the end of the URL.', 'litespeed-cache')
			. '<br>'
			. __('Any surrounding whitespaces will be trimmed.', 'litespeed-cache')
			. '<br><br>'
			. sprintf(__('e.g. to exclude %s, I would have:', 'litespeed-cache'),'http://www.example.com/excludethis.php')
			. '<br>
			<pre>/excludethis.php</pre>
			<br>';

		$cat_description =
			'<b>' . __('All categories are cached by default.', 'litespeed-cache') . '</b>
			<br>'
			. __('To prevent a category from being cached, enter it in the text area below, one per line.', 'litespeed-cache')
			. '<br>
			<b>' . __('NOTE:', 'litespeed-cache') . '</b>' . __('If the Category ID is not found, the name will be removed on save.', 'litespeed-cache')
			. '<br><br>';

		$tag_description =
			'<b>' . __('All tags are cached by default.', 'litespeed-cache') . '</b>
			<br>'
			. __('To prevent tags from being cached, enter it in the text area below, one per line.', 'litespeed-cache')
			. '<br>
			<b>' . __('NOTE:', 'litespeed-cache') . '</b>' . __('If the Tag ID is not found, the name will be removed on save.', 'litespeed-cache')
			. '<br><br>';

		$tr = '<tr><td>' ;
		$endtr = "</td></tr>\n" ;

		$excludes_id = LiteSpeed_Cache_Config::OPID_EXCLUDES_URI;
		$excludes_buf = $options[$excludes_id];
		$buf = $this->input_group_start(__('URI List', 'litespeed-cache'),
										$uri_description);
		$buf .= $tr ;
		$buf .= $this->input_field_textarea($excludes_id, $excludes_buf,
											'10', '80', '');
		$buf .= $endtr;

		$buf .= $this->input_group_end();

		$excludes_id = LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT;
		$excludes_buf = '';
		$cat_ids = $options[$excludes_id];
		if ($cat_ids != '') {
			$id_list = explode( ',', $cat_ids);
			$excludes_buf = implode("\n", array_map(get_cat_name, $id_list));
		}
		$buf .= $this->input_group_start(__('Category List', 'litespeed-cache'),
										$cat_description);
		$buf .= $tr ;
		$buf .= $this->input_field_textarea($excludes_id, $excludes_buf,
											'5', '80', '');
		$buf .= $endtr;

		$buf .= $this->input_group_end();

		$excludes_id = LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG;
		$excludes_buf = '';
		$tag_ids = $options[$excludes_id];
		if ($tag_ids != '') {
			$id_list = explode( ',', $tag_ids);
			$tags_list = array_map(get_tag, $id_list);
			$tag_names = array();
			foreach( $tags_list as $tag) {
				$tag_names[] = $tag->name;
			}
			if (!empty($tag_names)) {
				$excludes_buf = implode("\n", $tag_names);
			}
		}
		$buf .= $this->input_group_start(__('Tag List', 'litespeed-cache'),
										$tag_description);
		$buf .= $tr ;
		$buf .= $this->input_field_textarea($excludes_id, $excludes_buf,
											'5', '80', '');
		$buf .= $endtr;

		$buf .= $this->input_group_end();

		if (is_multisite()) {
			return $buf;
		}
		$cookie_title = '';
		$cookie_desc = '';
		$cookie_buf = $this->build_setting_exclude_cookies($cookie_title, $cookie_desc);

		$buf .= $this->input_group_start($cookie_title, $cookie_desc);
		$buf .= $tr . $cookie_buf . $endtr;
		$buf .= $this->input_group_end();

		$ua_title = '';
		$ua_desc = '';
		$ua_buf = $this->build_setting_exclude_useragent($ua_title, $ua_desc);

		$buf .= $this->input_group_start($ua_title, $ua_desc);
		$buf .= $tr . $ua_buf . $endtr;
		$buf .= $this->input_group_end();

		return $buf;
	}

	/**
	 * Builds the html for the advanced settings tab.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param array $options The current configuration options.
	 * @return string The html for the advanced settings tab.
	 */
	private function show_settings_advanced( $options )
	{
		$cookie_title = '';
		$cookie_desc = '';
		$advanced_desc = '<strong>' . __('NOTICE', 'litespeed-cache') . ':</strong>'
			. __(' These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache')
			. __(' Please take great care when changing any of these settings.', 'litespeed-cache')
			. __(" If you have any questions, don't hesitate to submit a support thread.", 'litespeed-cache');

		$buf = $this->input_group_start(__('Advanced Settings', 'litespeed-cache'),
										$advanced_desc);
		$buf .= $this->input_group_end();

		if (!is_multisite()) {
			$cookie_buf .= $this->build_setting_login_cookie($options,
					$cookie_title, $cookie_desc);
			$buf .= $this->input_group_start($cookie_title, $cookie_desc);
			$buf .= $cookie_buf;
			$buf .= $this->input_group_end();
		}

		return $buf;

	}

	/**
	 * Builds the html for the debug settings tab.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $options The current configuration options.
	 * @return string The html for the debug settings tab.
	 */
	private function show_settings_test( $options )
	{
		$buf = $this->input_group_start(__('Developer Testing', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS ;
		$input_admin_ips = $this->input_field_text($id, $options[$id], '', 'regular-text') ;
		$buf .= $this->display_config_row(__('Admin IPs', 'litespeed-cache'), $input_admin_ips,
		__('Allows listed IPs (space or comma separated) to perform certain actions from their browsers.', 'litespeed-cache')
		. '<br>'
		. __('More information about the available commands can be found here: ', 'litespeed-cache')
		. '<a href=' . get_admin_url() . 'admin.php?page=lscache-info#adminip>link</a>'		) ;

		$id = LiteSpeed_Cache_Config::OPID_DEBUG ;
		$debug_levels = array(
			LiteSpeed_Cache_Config::LOG_LEVEL_NONE => __('None', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_ERROR => __('Error', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_NOTICE => __('Notice', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_INFO => __('Info', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG => __('Debug', 'litespeed-cache') ) ;
		$input_debug = $this->input_field_select($id, $debug_levels, $options[$id]) ;
		$buf .= $this->display_config_row(__('Debug Level', 'litespeed-cache'), $input_debug,
				__('Outputs to WordPress debug log.', 'litespeed-cache')) ;

		/* Maybe add this feature later
		  $id = LiteSpeed_Cache_Config::OPID_TEST_IPS;
		  $input_test_ips  = $this->input_field_text($id, $options[$id], '', 'regular-text');
		  $buf .= $this->display_config_row('Test IPs', $input_test_ips,
		  'Enable LiteSpeed Cache only for specified IPs. (Space or comma separated.)
		 * Allows testing on a live site. If empty, cache will be served to everyone.');
		 *
		 */

		$buf .= $this->input_group_end() ;
		return $buf ;
	}

	/**
	 * Checks if wp_postviews is installed. If so, show this tab.
	 *
	 * @since 1.0.4
	 * @access private
	 * @return string The html for the compatibility tab.
	 */
	private function show_settings_compatibilities()
	{

		$buf = '';

		if (function_exists('the_views')) {
			$buf .= $this->build_compatibility_wp_postviews();
		}
		return $buf;
	}

	/**
	 * Builds the html for the mobile views configurations.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param array $options The currently configured options.
	 * @return string The html for mobile views configurations.
	 */
	private function build_setting_mobile_view($options)
	{
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$wp_default_mobile = 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi';
		$id = LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED ;
		$list_id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST;
		$default_id = 'lscwp_' . $id . '_default';
		$warning_id = 'lscwp_' . $id . '_warning';
		clearstatcache();
		$buf = $this->input_field_hidden($warning_id,
		__('WARNING: Unchecking this option will clear the Mobile View List. Press OK to confirm this action.', 'litespeed-cache'));
		$mv_enabled = $this->input_field_checkbox('lscwp_' . $id, $id, $options[$id], '',
				'lscwpCheckboxConfirm(this, \'' . $list_id . '\')', !$file_writable) ;

		$buf .= $this->display_config_row(__('Enable Separate Mobile View', 'litespeed-cache'), $mv_enabled,
		__('When checked, mobile views will be cached separately. ', 'litespeed-cache')
		. __('A site built with responsive design does not need to check this.', 'litespeed-cache'));

		$mv_list_desc = __('SYNTAX: Each entry should be separated with a bar, \'|\'.', 'litespeed-cache')
		. __(' Any spaces should be escaped with a backslash before it, \'\\ \'.')
		. '<br>'
		. __('The default list WordPress uses is ', 'litespeed-cache')
		. $wp_default_mobile
		. '<br><strong>' . __('NOTICE: ', 'litespeed-cache') . '</strong>'
		. __('This setting will edit the .htaccess file.', 'litespeed-cache');

		$mv_str = '';
		if (LiteSpeed_Cache_Admin_Rules::get_instance()->get_common_rule('MOBILE VIEW', 'HTTP_USER_AGENT', $mv_str) === true) {
			// can also use class 'mejs-container' for 100% width.
			$mv_list = $this->input_field_text($list_id, $mv_str, '', 'widget ui-draggable-dragging code', '',
					($options[$id] ? false : true)) ;

			$default_fill = (($mv_str == '') ? $wp_default_mobile : $mv_str);
			$buf .= $this->input_field_hidden($default_id, $default_fill);
		}
		else {
			$mv_list = '<p class="attention">'
			. __('Error getting current rules: ', 'litespeed-cache') . $mv_str . '</p>';
		}
		$buf .= $this->display_config_row(__('List of Mobile View User Agents', 'litespeed-cache'),
				$mv_list, $mv_list_desc);
		return $buf;
	}

	/**
	 * Builds the html for the cookie excludes configuration.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $cookie_titlee Returns the cookie title string.
	 * @param string $cookie_desc Returns the cookie description string.
	 * @return string Returns the cookie text area on success, error message on failure.
	 */
	private function build_setting_exclude_cookies(&$cookie_title,
			&$cookie_desc)
	{
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;
		$cookies_rule = '';
		$cookie_title = __('Cookie List', 'litespeed-cache');
		$cookie_desc = __('To prevent cookies from being cached, enter it in the text area below.', 'litespeed-cache')
				. '<br>' . __('SYNTAX: Cookies should be listed one per line.', 'litespeed-cache')
				. __(' Spaces should have a backslash in front of them, \'\ \'.', 'litespeed-cache')
				. '<br><strong>' . __('NOTICE: ', 'litespeed-cache') . '</strong>'
				. __('This setting will edit the .htaccess file.', 'litespeed-cache');

		if (LiteSpeed_Cache_Admin_Rules::get_instance()->get_common_rule('COOKIE',
				'HTTP_COOKIE', $cookies_rule) !== true) {
			return '<p class="attention">'
			. __('Error getting current rules: ', 'litespeed-cache') . $cookies_rule . '</p>';
		}
		$excludes_buf = str_replace('|', "\n", $cookies_rule);
		return $this->input_field_textarea($id, $excludes_buf, '5', '80', '',
				!$file_writable);
	}

	/**
	 * Builds the html for the user agent excludes configuration.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $ua_title Returns the user agent title string.
	 * @param string $ua_desc Returns the user agent description string.
	 * @return string Returns the user agent text field on success,
	 * error message on failure.
	 */
	private function build_setting_exclude_useragent(&$ua_title, &$ua_desc)
	{
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		$ua_rule = '';
		$ua_title = __('User Agent List', 'litespeed-cache');
		$ua_desc = __('To prevent user agents from being cached, enter it in the text field below.', 'litespeed-cache')
				. '<br>' . __('SYNTAX: Separate each user agent with a bar, \'|\'.', 'litespeed-cache')
				. __(' Spaces should have a backslash in front of them, \'\ \'.', 'litespeed-cache')
				. '<br><strong>' . __('NOTICE: ', 'litespeed-cache') . '</strong>'
				. __('This setting will edit the .htaccess file.', 'litespeed-cache');
		if (LiteSpeed_Cache_Admin_Rules::get_instance()->get_common_rule('USER AGENT', 'HTTP_USER_AGENT', $ua_rule) === true) {
			// can also use class 'mejs-container' for 100% width.
			$ua_list = $this->input_field_text($id, $ua_rule, '',
					'widget ui-draggable-dragging', '', !$file_writable);
		}
		else {
			$ua_list = '<p class="attention">'
			. __('Error getting current rules: ', 'litespeed-cache') . $ua_rule . '</p>';
		}
		return $ua_list;
	}

	/**
	 * Builds the html for the user agent excludes configuration.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param array $options The currently configured options.
	 * @param string $cookie_title Returns the cookie title string.
	 * @param string $cookie_desc Returns the cookie description string.
	 * @return string Returns the cookie text field on success,
	 * error message on failure.
	 */
	private function build_setting_login_cookie($options, &$cookie_title,
			&$cookie_desc)
	{
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE;
		$cookie = '';

		$cookie_title = __('Login Cookie', 'litespeed-cache');
		$cookie_desc =
			__('SYNTAX: alphanumeric and "_".', 'litespeed-cache')
			. __(' No spaces and case sensitive. ', 'litespeed-cache')
			. __('MUST BE UNIQUE FROM OTHER WEB APPLICATIONS.', 'litespeed-cache')
			. '<br>'
			. sprintf(__('The default login cookie is %s. ', 'litespeed-cache'),'_lscache_vary')
			. __('The server will determine if the user is logged in based on this cookie. ', 'litespeed-cache')
			. __('This setting is useful for those that have multiple web applications for the same domain. ', 'litespeed-cache')
			. __('If every web application uses the same cookie, the server may confuse whether a user is logged in or not.', 'litespeed-cache')
			. __(' The cookie set here will be used for this WordPress installation.', 'litespeed-cache')
			. '<br><br>'
			. __('Example use case:', 'litespeed-cache') . '<br>'
			. sprintf(__('There is a WordPress install for %s.', 'litespeed-cache'), '<u>www.example.com</u>')
			. '<br>'
			. sprintf(__('Then there is another WordPress install (NOT MULTISITE) at %s', 'litespeed-cache'), '<u>www.example.com/blog/</u>')
			.'<br>'
			. __('The cache needs to distinguish who is logged into which WordPress in order to cache correctly.', 'litespeed-cache');

		if (LiteSpeed_Cache_Admin_Rules::get_instance()->get_rewrite_rule('LOGIN COOKIE',
				$match, $sub, $cookie) === false) {
			return '<p class="attention">'
			. __('Error getting current rules: ', 'litespeed-cache') . $cookie . '</p>';
		}
		if (!empty($cookie)) {
			if (strncmp($cookie, 'Cache-Vary:', 11)) {
				return '<p class="attention">'
					. sprintf(__('Error: invalid login cookie. Please check the %s file', 'litespeed-cache'), '.htaccess')
					. '</p>';
			}
			$cookie = substr($cookie, 11);
		}
		if ($cookie != $options[$id]) {
			echo $this->build_notice(self::NOTICE_YELLOW,
					__('WARNING: The .htaccess login cookie and Database login cookie do not match.', 'litespeed-cache'));
		}
		return $this->input_field_text($id, $cookie, '','', '', !$file_writable);
	}

	/**
	 * Builds the html for the wp_postviews help page.
	 *
	 * @since 1.0.1
	 * @access private
	 * @return string The html for the wp_postviews help page.
	 */
	private function build_compatibility_wp_postviews()
	{
		$buf = '';
		$example_src = htmlspecialchars('<?php if(function_exists(\'the_views\' )) { the_views(); } ?>');
		$example_div = htmlspecialchars('<div id="postviews_lscwp" > </div>');
		$example_ajax_path = '/wp-content/plugins/wp-postviews/postviews-cache.js';
		$example_ajax = 'jQuery.ajax({
	type:"GET",
	url:viewsCacheL10n.admin_ajax_url,
	data:"postviews_id="+viewsCacheL10n.post_id+"&action=postviews",
	cache:!1,
	success:function(data) {
		if(data) {
			jQuery(\'#postviews_lscwp\').html(data+\' views\');
		}
	}
});';
		$wp_postviews_desc = __('To make LiteSpeed Cache compatible with WP-PostViews:', 'litespeed-cache') . '<br>
			<ol>
				<li>'
				. __('Replace the following calls in your theme\'s template files with a div or span with a unique ID.', 'litespeed-cache')
				. '<br>'
				. sprintf(wp_kses(__('e.g. Replace <br> <pre>%1$s</pre> with<br> <pre>%2$s</pre>', 'litespeed-cache'),
						array( 'br' => array(), 'pre' => array() )),
						$example_src,
						$example_div)
				. '</li><li>'
				. __('Update the ajax request to output the results to that div.', 'litespeed-cache')
				. '<br><br>'
				. __('Example:', 'litespeed-cache')
				. '<br>'
				. '<pre>' .  $example_ajax . '</pre><br>'
				. __('The ajax code can be found at', 'litespeed-cache') . '<br>'
				. '<pre>' . $example_ajax_path . '</pre></li>
				<li>' . __('After purging the cache, the view count should be updating.', 'litespeed-cache') .'</li>
			</ol>';
		$buf .= $this->input_group_start(
									__('Compatibility with WP-PostViews', 'litespeed-cache'), $wp_postviews_desc);
		$buf .= $this->input_group_end();
		return $buf;
	}

	/**
	 * Builds the html for the third party compatibilities tab.
	 *
	 * @since 1.0.4
	 * @access private
	 * @return string The html for the compatibilities tab.
	 */
	private function show_info_compatibility()
	{
		$known_compat = array(
			'bbPress',
			'WooCommerce',
			'Contact Form 7',
			'Google XML Sitemaps',
			'Yoast SEO'
		);

		$known_uncompat = array(

		);


		$buf = '<h3>' . __('LiteSpeed Cache Plugin Compatibility', 'litespeed-cache') . '</h3>'
		. '<h4>'
		. __('Please comment on the support thread listing the plugins that you are using and how they are functioning.', 'litespeed-cache')
		. __(' With your help, we can provide the best WordPress caching solution.', 'litespeed-cache')
		. '<br /><a href="https://wordpress.org/support/topic/known-supported-plugins?replies=1" target="_blank">'
		. __('Link Here', 'litespeed-cache') . '</a>'
		. '</h4>'
		. '<h4>'
		. __('This is a list of plugins that are confirmed to be compatible with LiteSpeed Cache Plugin:', 'litespeed-cache')
		. '</h4>'
		. '<ul>';
		foreach ($known_compat as $plugin_name) {
			$buf .= '<li>' . $plugin_name . '</li>';
		}
		$buf .= '</ul><br><br>'
		. '<h4>' . __('This is a list of known UNSUPPORTED plugins:', 'litespeed-cache') . '</h4>'
		. '<ul>';
		foreach ($known_uncompat as $plugin_name) {
			$buf .= '<li>' . $plugin_name . '</li>';
		}

		$buf .= '</ul><br><br>';
		return $buf;
	}

	/**
	 * Builds the html for the admin ip tab.
	 *
	 * @since 1.0.7
	 * @access private
	 * @return string The html for the admin ip tab.
	 */
	private function show_info_admin_ip()
	{
		$buf = '<h3>'
		. __('Admin IP Query String Actions', 'litespeed-cache') . '</h3>';

		$buf .= '<h4>'
		. __('The following commands are available to the admin and do not require log-in, providing quick access to actions on the various pages.', 'litespeed-cache')
		. '</h4>';

		$buf .= '<h4>' . __('Action List:', 'litespeed-cache') . '</h4>';
		$buf .= '<ul><li>NOCACHE - '
		. __('This is used to display a page without caching it. ', 'litespeed-cache')
		. __('An example use case is to compare a cached version with an uncached version.', 'litespeed-cache')
		. '</li>'
		. '<li>PURGE - '
		. __('This is used to purge most cache tags associated with the page.', 'litespeed-cache')
		. __(' The lone exception is the blog ID tag. ', 'litespeed-cache')
		. __('Note that this means that pages with the same cache tag will be purged as well.', 'litespeed-cache')
		. '</li>'
		. '<li>PURGESINGLE - '
		. __('This is used to purge the first cache tag associated with the page.', 'litespeed-cache')
		. '</li>'
		. '<li>SHOWHEADERS - '
		. __('This is used to show all the cache headers associated with the page.', 'litespeed-cache')
		. __(' This may be useful for debugging purposes.', 'litespeed-cache')
		. '</li></ul>';


		$buf .= '<h5>' . sprintf(__('To run the action, just access the page with the query string %s and '
				. 'the action will trigger for the accessed page.', 'litespeed-cache'),
					'<code>?LSCWP_CTRL=ACTION</code>')
		. '</h5>';

		return $buf;
	}

	/**
	 * Builds the html for the common rewrite rules tab.
	 *
	 * @since 1.0.4
	 * @access private
	 * @return string The html for the common rewrites tab.
	 */
	private function show_info_common_rewrite()
	{
		$mv_header = __('Mobile Views: ', 'litespeed-cache');
		$mv_desc = __('Some sites have adaptive views, meaning the page sent will adapt to the browser type (desktop vs mobile).', 'litespeed-cache')
		. __(' This rewrite rule is used for sites that load a different page for each type.', 'litespeed-cache')
		. '<br>'
		. __(' This configuration can be added on the settings page in the General tab.', 'litespeed-cache');
		$mv_example = 'RewriteCond %{HTTP_USER_AGENT} Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi [NC]
RewriteRule .* - [E=Cache-Control:vary=ismobile]';


		$cookie_header = __('Do Not Cache Cookies: ', 'litespeed-cache');
		$cookie_desc =
		__('Another common rewrite rule is to notify the cache not to cache when it sees a specified cookie name.', 'litespeed-cache')
		. '<br>'
		. __(' This configuration can be added on the settings page in the Do Not Cache tab.', 'litespeed-cache');
		$cookie_example = 'RewriteCond %{HTTP_COOKIE} dontcachecookie
RewriteRule .* - [E=Cache-Control:no-cache]';


		$ua_header = __('Do Not Cache User Agent: ', 'litespeed-cache');
		$ua_desc =
		__('A not so commonly used rewrite rule is to notify the cache not to cache when it sees a specified User Agent.', 'litespeed-cache')
		. '<br>'
		. __(' This configuration can be added on the settings page in the Do Not Cache tab.', 'litespeed-cache');
		$ua_example = 'RewriteCond %{HTTP_USER_AGENT} dontcacheuseragent
RewriteRule .* - [E=Cache-Control:no-cache]';


		// begin buffer

		$buf = '<h3>' . __('LiteSpeed Cache Common Rewrite Rules', 'litespeed-cache') . '</h2>';

		if ((is_multisite()) && (!is_network_admin())) {

			$buf .= '<p><span style="color: black;font-weight: bold">' . __('NOTE: ', 'litespeed-cache')
			. '</span>'
			. __('The following configurations can only be changed by the network admin.', 'litespeed-cache')
			. '<br>'
			. __('If you need to make changes to them, please contact the network admin to make the changes.', 'litespeed-cache')
			. '</p>';
		}
		else {
			$buf .= '<p><span style="color: black;font-weight: bold">' . __('NOTICE: ', 'litespeed-cache')
			. '</span>'
			. __('The following rewrite rules can be configured in the LiteSpeed Cache settings page.', 'litespeed-cache')
			. '<br>'
			. __('If you need to make changes to them, please do so on that page.', 'litespeed-cache')
			. __(' It will automatically generate the correct rules in the htaccess file.', 'litespeed-cache')
			. '</p>';

		}

		$buf .= $this->input_collapsible_start();

		$buf .= $this->input_field_collapsible($mv_header, $mv_desc, $mv_example);
		$buf .= $this->input_field_collapsible($cookie_header, $cookie_desc, $cookie_example);
		$buf .= $this->input_field_collapsible($ua_header, $ua_desc, $ua_example);

		$buf .= $this->input_collapsible_end();

		return $buf;
	}

	/**
	 * Generates the HTMl to start a configuration options table.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $title The title of the configuration group.
	 * @param string $description The description of the configuration group.
	 * @return string The start configuration option table html.
	 */
	private function input_group_start( $title = '', $description = '' )
	{
		$buf = '' ;
		if ( $title ) {
			$buf .= '<hr/><h3 class="title">' . $title . "</h3>\n" ;
		}
		if ( $description ) {
			$buf .= '<p>' . $description . "</p>\n" ;
		}
		$buf .= '<table class="form-table">' . "\n" ;
		return $buf ;
	}

	/**
	 * Generates the HTML to end the configuration options table.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return string The end table html.
	 */
	private function input_group_end()
	{
		return "</table>\n" ;
	}

	/**
	 * Generates the HTML for an entry in the configuration options table.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $label The option name.
	 * @param string $input_field The option html.
	 * @param string $notes The description to display under the option html.
	 * @return string The config row html.
	 */
	private function display_config_row( $label, $input_field, $notes = '' )
	{
		$buf = '<tr><th scope="row">' . $label . '</th><td>' . $input_field ;
		if ( $notes ) {
			$buf .= '<p class="description">' . $notes . '</p>' ;
		}
		$buf .= '</td></tr>' . "\n" ;
		return $buf ;
	}

	/**
	 * Generates the HTML for a check box.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $id The option ID for the field.
	 * @param string $value The value for the field.
	 * @param mixed $checked_value The current value.
	 * @param string $label The label to display.
	 * @param string $on_click The action to do on click.
	 * @param boolean $disabled True for disabled check box, false otherwise.
	 * @return string The check box html.
	 */
	private function input_field_checkbox( $id, $value, $checked_value,
			$label = '', $on_click = '', $disabled = false)
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="checkbox" id="'
				. $id . '" value="' . $value . '"' ;
		if ( ($checked_value === $value) || (true === $checked_value) ) {
			$buf .= ' checked="checked" ' ;
		}
		if ($on_click != '') {
			$buf .= 'onclick="' . $on_click . '"';
		}
		if ($disabled) {
			$buf .= ' disabled ';
		}
		$buf .= '/>' ;
		if ( $label ) {
			$buf .= '<label for="' . $id . '">' . $label . '</label>' ;
		}
		return $buf ;
	}

	/**
	 * Generates the HTML for a radio group.
	 *
	 * @since 1.0.3
	 * @access private
	 * @param string $id The option ID for the field.
	 * @param array $radiooptions The options available for selection.
	 * @param string checked_value The currently selected option.
	 * @return string The select field html.
	 */
	private function input_field_radio( $id, $radiooptions, $checked_value)
	{
		$buf = '';
		foreach ( $radiooptions as $val => $label ) {
			$buf .= '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="radio" id="'
				. $id . '" value="' . $val . '"' ;
			if (($checked_value === $val)) {
				$buf .= ' checked="checked"' ;
			}
			$buf .= '>' . $label . '</input>';
			$buf .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		return $buf ;
	}

	/**
	 * Generates the HTML for a drop down select.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $id The option ID for the field.
	 * @param array $seloptions The options available for selection.
	 * @param string $selected_value The currently selected option.
	 * @return string The select field html.
	 */
	private function input_field_select( $id, $seloptions, $selected_value )
	{
		$buf = '<select name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" id="'
				. $id . '">' ;
		foreach ( $seloptions as $val => $label ) {
			$buf .= '<option value="' . $val . '"' ;
			if ( $selected_value == $val ) {
				$buf .= ' selected="selected"' ;
			}
			$buf .= '>' . $label . '</option>' ;
		}
		$buf .= '</select>' ;
		return $buf ;
	}

	/**
	 * Generates the HTML for a text input field.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $id The option ID for the field.
	 * @param string $value The value for the field.
	 * @param string $size The length to display.
	 * @param string $style The class to format the display.
	 * @param string $after The units to display after the text field.
	 * @param boolean $readonly True for read only text fields, false otherwise.
	 * @return string The input text html.
	 */
	private function input_field_text( $id, $value, $size = '', $style = '',
			$after = '', $readonly = false )
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="text" id="'
				. $id . '" value="' . esc_textarea($value) . '"' ;
		if ( $size ) {
			$buf .= ' size="' . $size . '"' ;
		}
		if ( $style ) {
			$buf .= ' class="' . $style . '"' ;
		}
		if ( $readonly ) {
			$buf .= ' readonly';
		}
		$buf .= '/>' ;
		if ( $after ) {
			$buf .= ' ' . $after ;
		}
		return $buf ;
	}

	/**
	 * Generates the HTML for a textarea.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param string $id The option ID for the field.
	 * @param string $value The value for the field.
	 * @param string $rows Number of rows to display.
	 * @param string $cols Number of columns to display.
	 * @param string $style The class to format the display.
	 * @param boolean $readonly True for read only text areas, false otherwise.
	 * @return string The textarea html.
	 */
	private function input_field_textarea( $id, $value, $rows = '', $cols = '',
			$style = '', $readonly = false)
	{
		$buf = '<textarea name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="text"
				id="' . $id . '"';
		if ( $rows ) {
			$buf .= ' rows="' . $rows . '"';
		}
		if ( $cols ) {
			$buf .= ' cols="' . $cols . '"';
		}
		if ( $style ) {
			$buf .= ' class="' . $style . '"';
		}
		if ( $readonly ) {
			$buf .= ' readonly ';
		}
		$buf .= '>' . esc_textarea($value) . '</textarea>';

		return $buf;
	}

	/**
	 * Generates the HTML for a hidden input field.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $id The option ID for the field.
	 * @param string $value The value for the field.
	 * @return string The hidden field html.
	 */
	private function input_field_hidden( $id, $value)
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="hidden" id="'
				. $id . '" value="' . esc_html($value) . '"' ;
		$buf .= '/>' ;
		return $buf ;
	}

	/**
	 * Generates the HTMl to start a collapsible group.
	 *
	 * @since 1.0.7
	 * @access private
	 * @return string The start collapsible group html.
	 */
	private function input_collapsible_start()
	{
		return '<div class="metabox-holder">'
		. '<div class="meta-box-sortables ui-sortable">';
	}

	/**
	 * Generates the HTMl to end the collapsible group.
	 *
	 * @since 1.0.7
	 * @access private
	 * @return string The end collapsible group html.
	 */
	private function input_collapsible_end()
	{
		return '</div></div>';
	}

	/**
	 * Helper function to build the html for collapsible content.
	 *
	 * @since 1.0.5
	 * @access private
	 * @param string $header The title of the collapsible content.
	 * @param string $desc A description inside the collapsible content.
	 * @param string $example An example to display after the description.
	 * @return string The html of the collapsible content.
	 */
	private function input_field_collapsible($header, $desc, $example = '')
	{
		$buf = '<div class="postbox closed">'
		. '<button type="button" class="handlediv button-link litespeedcache-postbox-button" aria-expanded="false">'
		. '<span class="toggle-indicator" aria-hidden="true"></span></button>'
		. '<h2 class="hndle ui-sortable-handle"><span>' . $header . '</span></h2>';


		$buf .= '<div class="welcome-panel-content"><div class="inside"><p>'
				. $desc . '</p>';

		if ($example !== '') {
			$buf .= '<textarea id="wpwrap" readonly>' . $example . '</textarea>';
		}
		$buf .= '</div></div></div>';
		return $buf;
	}

}
