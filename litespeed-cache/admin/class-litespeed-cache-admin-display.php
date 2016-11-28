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
	private $disable_all = false;

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
	 * Displays the help tab in the admin pages.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function add_help_tabs()
	{
		$content_para = LiteSpeed_Cache::build_paragraph(
			__('LiteSpeed Cache is a page cache built into LiteSpeed Web Server.', 'litespeed-cache'),
			__('This plugin communicates with LiteSpeed Web Server to let it know which pages are cache-able and when to purge them.', 'litespeed-cache')
		);

		$screen = get_current_screen() ;
		$screen->add_help_tab(array(
			'id' => 'lsc-overview',
			'title' => __('Overview', 'litespeed-cache'),
			'content' => '<p>' . $content_para . '</p>' .
			'<p>' . __('You must have the LSCache module installed and enabled in your LiteSpeed Web Server setup.', 'litespeed-cache') . '</p>',
		)) ;

		$screen->add_help_tab(array(
			'id' => 'lst-purgerules',
			'title' => __('Auto Purge Rules', 'litespeed-cache'),
			'content' => '<p>' . __('You can set what pages will be purged when a post is published or updated.', 'litespeed-cache') . '</p>',
		)) ;

		$screen->set_help_sidebar(
				'<p><strong>' . __('For more information:', 'litespeed-cache') . '</strong></p>' .
				'<p><a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache" rel="noopener noreferrer" target="_blank">' . __('LSCache Documentation', 'litespeed-cache') . '</a></p>' .
				'<p><a href="https://wordpress.org/support/plugin/litespeed-cache" rel="noopener noreferrer" target="_blank">' . __('Support Forum', 'litespeed-cache') . '</a></p>'
		) ;
	}

	/**
	 * Check to make sure that caching is enabled.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param LiteSpeed_Cache_Config $config The current configurations object.
	 * @return mixed True if enabled, error message otherwise.
	 */
	public function check_license($config)
	{
		if ($config->is_caching_allowed() == false) {
			$sentences = LiteSpeed_Cache::build_paragraph(
				__('Notice: Your installation of LiteSpeed Web Server does not have LSCache enabled.', 'litespeed-cache'),
				__('This plugin will NOT work properly.', 'litespeed-cache')
			);
			return $sentences;
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
					if (is_network_admin()) {
						$this->show_menu_network_settings();
					}
					else {
						settings_errors();
						$this->show_menu_settings();
					}
				}
				break;
			case 'e':
				if (($selection_len == 13)
						&& (strncmp($selection, 'edit-htaccess', $selection_len) == 0)) {
					$this->show_menu_edit_htaccess();
				}
				break;
			case 'r':
				if (($selection_len == 6)
						&& (strncmp($selection, 'report', $selection_len) == 0)) {
					$this->show_menu_report();
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
		$config = LiteSpeed_Cache::config();
		$intro_sentences = LiteSpeed_Cache::build_paragraph(
			__('LiteSpeed Cache is maintained and managed by LiteSpeed Web Server.', 'litespeed-cache'),
			__('You can inform LiteSpeed Web Server to purge cached contents from this screen.', 'litespeed-cache')
		);
		$purgeall_confirm_para = LiteSpeed_Cache::build_paragraph(
			__('This will purge everything for all blogs.', 'litespeed-cache'),
			__('Are you sure you want to purge all?', 'litespeed-cache')
		);
		$purgeby_desc_para = LiteSpeed_Cache::build_paragraph(
			__('You may purge selected pages here.', 'litespeed-cache'),
			__('Currently, the available options are:', 'litespeed-cache')
		);
		$clearcache_confirm_para = LiteSpeed_Cache::build_paragraph(
			__('This will clear EVERYTHING inside the cache.', 'litespeed-cache'),
			__('This may cause heavy load on your server.', 'litespeed-cache'),
			__('If you only want to purge the wordpress site, use purge all.', 'litespeed-cache')
		);
		$clearcache_desc_para = LiteSpeed_Cache::build_paragraph(
			wp_kses(__('Clears all cache entries related to this site, <i>including other web applications</i>.', 'litespeed-cache'),
					array('i' => array())),
			wp_kses(__('<b>This action should only be used if things are cached incorrectly.</b>', 'litespeed-cache'),
					array('b' => array()))
		);

		if ( ($error_msg = $this->check_license($config)) !== true ) {
			echo '<div class="error"><p>' . $error_msg . '</p></div>' . "\n" ;
		}

		$purgeby_options = array(
			__('Category', 'litespeed-cache'),
			__('Post ID', 'litespeed-cache'),
			__('Tag', 'litespeed-cache'),
			__('URL', 'litespeed-cache')
		);

		// Page intro
		$buf = '<div class="wrap"><h2>' . __('LiteSpeed Cache Management', 'litespeed-cache') . '</h2>'
		. '<div class="welcome-panel"><p>' . $intro_sentences . '</p>';

		// Begin form
		$buf .= '<form method="post">'
		. '<input type="hidden" name="lscwp_management" value="manage_lscwp" />'
		. wp_nonce_field('lscwp_manage', 'management_run') ;

		// Form entries purge front, purge all
		$buf .= '<input type="submit" class="button button-primary" '
		. 'name="purgefront" value="' . __('Purge Front Page', 'litespeed-cache')
		. '" /><span>&nbsp;'
		. __('Purges the front page only.', 'litespeed-cache')
		. '</span><br><br>'
		. '<input type="submit" class="button button-primary" name="purgeall"'
		. 'id="litespeedcache-purgeall" value="' . __('Purge All', 'litespeed-cache')
		. '" /><span>&nbsp;'
		. __('Purges the cache entries created by this plugin.', 'litespeed-cache')
		. '<br>';

		if ((!is_multisite()) || (is_network_admin())) {
			$buf .=
				'<br><input type="submit" class="wp-ui-notification" name="clearcache"'
				. 'id="litespeedcache-clearcache" value="'
				. __('Empty Entire Cache', 'litespeed-cache')
				. '" /><span>&nbsp;'
				. $clearcache_desc_para
				. '</span><br>'
				. $this->input_field_hidden('litespeedcache-clearcache-confirm',
					$clearcache_confirm_para);
		}

		if ((is_multisite()) && (is_network_admin())) {
			echo $buf
			. $this->input_field_hidden('litespeedcache-purgeall-confirm',
				$purgeall_confirm_para)
			. "<br><br></form></div></div>\n";
			return;
		}

		$buf .= $this->input_field_hidden('litespeedcache-purgeall-confirm',
			__('Are you sure you want to purge all?', 'litespeed-cache'));


		// Purge by description.
		$buf .= '<h3>' . __('Purge By...', 'litespeed-cache') . '</h3>'
		. '<p>' . $purgeby_desc_para . '</p>'
		. '<ul>'
		. '<li><p>' . __('Category: Purge category pages by name.', 'litespeed-cache')
		. '</p></li>'
		. '<li><p>' . __('Post ID: Purge pages by post ID.', 'litespeed-cache')
		. '</p></li>'
		. '<li><p>' . __('Tag: Purge tag pages by name.', 'litespeed-cache')
		. '</p></li>'
		. '<li><p>' . __('URL: Purge pages by locator. Must be exact match.', 'litespeed-cache')
		. ' Ex: http://www.myexamplesite.com<b><u>/2016/02/24/hello-world/</u></b>'
		. '</p></li>'
		. '</ul>';

		if (($_POST) && (isset($_POST[LiteSpeed_Cache_Config::OPTION_NAME]))) {
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

		$options = $config->get_options();

		/**
		 * This hook allows third party plugins to create litespeed cache
		 * specific configurations.
		 *
		 * Each config should append an array containing the following:
		 * 'title' (required) - The tab's title.
		 * 'slug' (required) - The slug used for the tab. [a-z][A-Z], [0-9], -, _ permitted.
		 * 'content' (required) - The tab's content.
		 *
		 * Upon saving, only the options with the option group in the input's
		 * name will be retrieved.
		 * For example, name="litespeed-cache-conf[my-opt]".
		 *
		 * @see TODO: add option save filter.
		 * @since 1.0.9
		 * @param array $tabs An array of third party configurations.
		 * @param array $options The current configuration options.
		 * @param string $option_group The option group to use for options.
		 * @param boolean $disableall Whether to disable the settings or not.
		 * @return mixed An array of third party configs else false on failure.
		 */
		$tp_tabs = apply_filters('litespeed_cache_add_config_tab', array(),
			$options, LiteSpeed_Cache_Config::OPTION_NAME,
			$this->get_disable_all());

		echo '<div class="wrap">
		<h2>' . __('LiteSpeed Cache Settings', 'litespeed-cache')
		. '<span style="font-size:0.5em">v' . LiteSpeed_Cache::PLUGIN_VERSION . '</span></h2>
		<form method="post" action="options.php">' ;

		if ($this->get_disable_all()) {
			$desc = LiteSpeed_Cache::build_paragraph(
				__('The network admin selected use primary site configs for all subsites.', 'litespeed-cache'),
				__('The following options are selected, but are not editable in this settings page.', 'litespeed-cache')
			);
			echo '<p>' . $desc . '</p>';
		}

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
					. $this->show_settings_advanced($options)
					. '</div>';
		}

		echo '
		 <div id="lsc-tabs">
		 <ul>
		 <li><a href="#general-settings">' . __('General', 'litespeed-cache') . '</a></li>
		 <li><a href="#specific-settings">' . __('Specific Pages', 'litespeed-cache') . '</a></li>
		 <li><a href="#purge-settings">' . __('Purge Rules', 'litespeed-cache') . '</a></li>
		 <li><a href="#exclude-settings">' . __('Do Not Cache Rules', 'litespeed-cache') . '</a></li>
	 	 ' . $advanced_tab . '
		 <li><a href="#debug-settings">' . __('Debug', 'litespeed-cache') . '</a></li>'
		. $compatibilities_tab;

		if ((!empty($tp_tabs)) && (is_array($tp_tabs))) {
			foreach ($tp_tabs as $key=>$tab) {
				if ((!is_array($tab))
					|| (!isset($tab['title']))
					|| (!isset($tab['slug']))
					|| (!isset($tab['content']))) {
					if (defined('LSCWP_LOG')) {
						LiteSpeed_Cache::debug_log(
							__('WARNING: Third party tab input invalid.',
								'litespeed-cache'));
					}
					unset($tp_tabs[$key]);
					continue;
				}
				elseif (preg_match('/[^-\w]/', $tab['slug'])) {
					if (defined('LSCWP_LOG')) {
						LiteSpeed_Cache::debug_log(
							__('WARNING: Third party config slug contains invalid characters.',
								'litespeed-cache'));
					}
					unset($tp_tabs[$key]);
					continue;
				}
				echo '
					<li><a href="#' . $tab['slug'] . '-settings">'
					. $tab['title'] . '</a></li>';
			}
		}
		else {
			$tp_tabs = false;
		}

		echo '
		</ul>
		 <div id="general-settings">'
		. $this->show_settings_general($options) .
		'</div>
		 <div id="specific-settings">'
		. $this->show_settings_specific($options) .
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
		. $compatibilities_settings;

		if (!empty($tp_tabs)) {
			foreach ($tp_tabs as $tab) {
				echo '
				<div id ="' . $tab['slug'] . '-settings">'
				. $tab['content'] .
				'</div>';
			}
		}

		echo '</div>';

		if ($this->get_disable_all()) {
			submit_button(__('Save Changes', 'litespeed-cache'),
				'primary', 'submit', true, array('disabled' => true));
		}
		else {
			submit_button();
		}
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
		. LiteSpeed_Cache::build_paragraph(
			__('Separate Mobile Views should be enabled if any of the network enabled themes require a different view for mobile devices.', 'litespeed-cache'),
			__('Responsive themes can handle this part automatically.', 'litespeed-cache'));

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


		$id = LiteSpeed_Cache_Config::NETWORK_OPID_USE_PRIMARY;
		$use_primary = $this->input_field_checkbox('lscwp_' . $id, $id,
			$site_options[$id]);

		$use_primary_desc = LiteSpeed_Cache::build_paragraph(
			__("Check this option to use the primary site's configurations for all subsites.",
				'litespeed-cache'),
			__('This will disable the settings page on all subsites.', 'litespeed-cache')
		);

		$buf .= $this->display_config_row(
			__('Use Primary Site Configurations', 'litespeed-cache'),
			$use_primary,
			$use_primary_desc
		);

		$buf .= $this->build_setting_purge_on_upgrade($site_options);
		$buf .= $this->build_setting_cache_favicon($site_options);
		$buf .= $this->build_setting_cache_resources($site_options);
		$buf .= $this->build_setting_mobile_view($site_options);
		$buf .= $this->input_group_end() . '</div>';

		$buf .= '<div id="exclude">'
		. $this->input_group_start(__('Network Do Not Cache Rules', 'litespeed-cache'));
		$ua_title = '';
		$ua_desc = '';
		$ua_buf = $this->build_setting_exclude_useragent($site_options, $ua_title, $ua_desc);
		$buf .= $this->display_config_row(__('Do Not Cache User Agents', 'litespeed-cache'), $ua_buf, $ua_desc);

		$cookie_title = '';
		$cookie_desc = '';
		$cookie_buf = $this->build_setting_exclude_cookies($site_options, $cookie_title, $cookie_desc);
		$buf .= $this->display_config_row(__('Do Not Cache Cookies', 'litespeed-cache'), $cookie_buf, $cookie_desc);

		$buf .= $this->input_group_end() . '</div>';

		$buf .= '<div id="advanced">'
			. $this->show_settings_advanced($site_options) . '</div></div>';

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
		$warning_para = LiteSpeed_Cache::build_paragraph(
			__('Any changes made to the .htaccess file may break your site.', 'litespeed-cache'),
			__('Please consult your host/server admin before making any changes you are unsure about.', 'litespeed-cache')
		);
		$buf = '<div class="wrap"><h2>' . __('LiteSpeed Cache Edit .htaccess', 'litespeed-cache') . '</h2>';
		$buf .= '<div class="welcome-panel">';
		$contents = '';
		$rules = LiteSpeed_Cache_Admin_Rules::get_instance();
		if (defined('DISALLOW_FILE_EDIT') && (constant('DISALLOW_FILE_EDIT'))) {
			$buf .= '<h3>'
				. __('File editing is disabled in configuration.', 'litespeed-cache')
				. '</h3></div>';
			echo $buf;
			return;
		}
		elseif (LiteSpeed_Cache_Admin_Rules::file_get($contents) === false) {
			$buf .= '<h3>' . $contents . '</h3></div>';
			echo $buf;
			return;
		}
		$file_writable = $rules->is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);

		$buf .= '<p><span class="attention">'
			. __('WARNING: This page is meant for advanced users.', 'litespeed-cache')
			. '</span><br>' . $warning_para . '</p>';

		$buf .= $this->show_info_common_rewrite();

		$buf .= '<form method="post" action="admin.php?page=lscache-edit-htaccess">';
		$buf .= '<input type="hidden" name="'
			. LiteSpeed_Cache_Admin_Rules::EDITOR_INPUT_NAME . '" value="'
			. LiteSpeed_Cache_Admin_Rules::EDITOR_INPUT_VAL . '" />';
		$buf .= wp_nonce_field(LiteSpeed_Cache_Admin_Rules::EDITOR_NONCE_NAME,
			LiteSpeed_Cache_Admin_Rules::EDITOR_NONCE_VAL);

		$buf .= '<h3>' . sprintf(__('Current %s contents:', 'litespeed-cache'), '.htaccess') . '</h3>';

		$buf .= '<p><span class="attention">'
		. sprintf(__('DO NOT EDIT ANYTHING WITHIN %s', 'litespeed-cache'),
			'###LSCACHE START/END XXXXXX###')
		. '</span><br>'
		. __('These are added by the LS Cache plugin and may cause problems if they are changed.', 'litespeed-cache')
		. '</p>';

		$buf .= '<textarea id="wpwrap" name="'
			. LiteSpeed_Cache_Admin_Rules::EDITOR_TEXTAREA_NAME
			. '" wrap="off" rows="20" class="code" ';
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
		$trial_para = LiteSpeed_Cache::build_paragraph(
			__('Make sure that your license has the LSCache module enabled.', 'litespeed-cache'),
			sprintf(wp_kses(
				__('You can <a href="%1$s"  rel="%2$s" target="%3$s">try our 2-CPU trial license with LSCache module</a> free for %4$d days.',
					'litespeed-cache'),
				array('a' => array('href' => array(), 'rel' => array(),
					'target' => array()))),
				'https://www.litespeedtech.com/products/litespeed-web-server/download/get-a-trial-license',
				'noopener noreferrer', '_blank', 15)
		);

		$caching_para = LiteSpeed_Cache::build_paragraph(
			__('Your server must be configured to have caching enabled.', 'litespeed-cache'),
			sprintf(wp_kses(
				__('If you are the server admin, <a href="%s" rel="%s" target="%s">click here.</a>',
					'litespeed-cache'),
				array('a' => array('href' => array(), 'rel' => array(),
					'target' => array()))),
				'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:common_installation#web_server_configuration',
				'noopener noreferrer', '_blank'),
			__('Otherwise request that your server admin configure the cache root for your server.', 'litespeed-cache')
		);

		$ols_para = LiteSpeed_Cache::build_paragraph(
			__('Our OLS integration is currently in beta.', 'litespeed-cache'),
			__('This integration utilizes OLS\'s cache module.', 'litespeed-cache'),
			sprintf(wp_kses(
				__('Please follow the instructions <a href="%s" rel="%s" target="%s">here.</a>',
					'litespeed-cache'),
				array( 'a' => array( 'href' => array(), 'rel' => array(),
					'target' => array() ) )),
				'http://open.litespeedtech.com/mediawiki/index.php/Help:How_To_Set_Up_LSCache_For_WordPress',
				'noopener noreferrer', '_blank')
		);

		$test_para = LiteSpeed_Cache::build_paragraph(
			sprintf(__('Subsequent requests should have the %s response header until the page is updated, expired, or purged.',
					'litespeed-cache'), '<code>X-LiteSpeed-Cache-Control:hit</code><br>'),
			sprintf(wp_kses(
				__('Please visit <a href="%s" rel="%s" target="%s">this page</a> for more information.',
					'litespeed-cache'),
				array( 'a' => array( 'href' => array(), 'rel' => array(),
					'target' => array() ) )),
				'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation#testing',
				'noopener noreferrer', '_blank')
		);

		$footer_para = LiteSpeed_Cache::build_paragraph(
			sprintf(__('If your questions are not answered here, try the %s', 'litespeed-cache'),
				'<a href=' . get_admin_url() . 'admin.php?page=lscache-faqs>FAQ.</a>'),
			sprintf(wp_kses(
				__('If your questions are still not answered, do not hesitate to ask them on the <a href="%s" rel="%s" target="%s">support forum</a>.',
					'litespeed-cache'),
				array( 'a' =>array( 'href' => array(), 'rel' => array(),
					'target' => array() ) )),
				'https://wordpress.org/support/plugin/litespeed-cache',
				'noopener noreferrer', '_blank')
		);

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
		. '<h4>' . __('Instructions for LiteSpeed Web Server Enterprise', 'litespeed-cache') . '</h4>';

		$buf .= '<ol><li>'
			. $trial_para
			. '</li><li>'
			. $caching_para
			. '</li><li>'
			. __('In the .htaccess file for your WordPress installation, add the following:',
				'litespeed-cache')
			. '<textarea id="wpwrap" rows="3" readonly>&lt;IfModule LiteSpeed&gt;
   CacheLookup public on
&lt;/IfModule&gt;</textarea></ol>';

		$buf .= '<h4>' . __('Instructions for OpenLiteSpeed', 'litespeed-cache') . '</h4>';
		$buf .= '<p>' . $ols_para . '</p>';

		$buf .= '<h3>' . __('How to test the plugin', 'litespeed-cache') . '</h3>';
		$buf .= '<p>' . __('The LiteSpeed Cache Plugin utilizes LiteSpeed specific response headers.', 'litespeed-cache')
			. '<br>'
			. sprintf(__('Visiting a page for the first time should result in a %s or %s response header for the page.',
					'litespeed-cache'), '<br><code>X-LiteSpeed-Cache-Control:miss</code><br>',
					'<br><code>X-LiteSpeed-Cache-Control:no-cache</code><br>')
			. '<br>'
			. $test_para
			. '</p>';

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
		$buf .= '<h4>' . $footer_para . '</h4></div>'; // class=wrap
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
		$plugin_free = LiteSpeed_Cache::build_paragraph(
			__('Yes, the plugin itself will remain free and open source.', 'litespeed-cache'),
			__('You are required to have a LiteSpeed Web Server Enterprise 5.0.10+ license with the LSCache module enabled.', 'litespeed-cache'),
			__('An alternative to Enterprise is OpenLiteSpeed v 1.4.17+, but the functionality is currently in beta.', 'litespeed-cache')
		);

		$files_stored = LiteSpeed_Cache::build_paragraph(
			__('This plugin only instructs LiteSpeed Web Server on what pages to cache and when to purge.', 'litespeed-cache'),
			__('The actual cached pages are stored and managed by LiteSpeed Web Server.', 'litespeed-cache'),
			__('Nothing is stored on the PHP side.', 'litespeed-cache')
		);

		$ols = LiteSpeed_Cache::build_paragraph(
			__('The support is currently in beta.', 'litespeed-cache'),
			__('It should work, but has not been fully tested.', 'litespeed-cache'),
			__('As well, any settings changes that require modifying the .htaccess file will require a server restart.', 'litespeed-cache')
		);

		$wc_support = LiteSpeed_Cache::build_paragraph(
			__('In short, yes.', 'litespeed-cache'),
			__('However, for some woocommerce themes, the cart may not be updated correctly.', 'litespeed-cache')
		);

		$wc_themes = LiteSpeed_Cache::build_paragraph(
			__('We tested a couple of themes like Storefront and Shop Isle and found that the cart works without the rule.', 'litespeed-cache'),
			__('That said, we found that some may not, like the E-Commerce theme, so please verify your theme.', 'litespeed-cache')
		);

		$buf =  '<div class="wrap"><h2>LiteSpeed Cache FAQs</h2>';

		$buf .= '<div class="welcome-panel"><h4>'
		. __('Is the LiteSpeed Cache Plugin for WordPress free?', 'litespeed-cache') . '</h4>'
		. '<p>' . $plugin_free . '</p>';

		$buf .= '<h4>' . __('Where are the cached files stored?', 'litespeed-cache') . '</h4>'
		. '<p>' . $files_stored . '</p>';

		$buf .= '<h4>'
		. __('Does LiteSpeed Cache for WordPress work with OpenLiteSpeed?', 'litespeed-cache')
		. '</h4><p>' . $ols . '</p>';

		$buf .= '<h4>' . __('Is WooCommerce supported?', 'litespeed-cache') . '</h4>'
		. '<p>' . $wc_support
		. '<br><b>'
		. __('To test the cart:', 'litespeed-cache')
		. '</b></p><ol><li>'
		. __('On a non-logged-in browser, visit and cache a page, then visit and cache a product page.', 'litespeed-cache')
		. '</li><li>'
		. __('The first page should be accessible from the product page (e.g. the shop).', 'litespeed-cache')
		. '</li><li>'
		. __('Once both pages are confirmed cached, add the product to your cart.', 'litespeed-cache')
		. '</li><li>'
		. __('After adding to the cart, visit the first page.', 'litespeed-cache')
		. '</li><li>'
		. __('The page should still be cached, and the cart should be up to date.', 'litespeed-cache')
		. '</li><li>'
		. __('If that is not the case, please add woocommerce_items_in_cart to the do not cache cookie list.', 'litespeed-cache')
		. '</li></ol><p>' . $wc_themes . '</p>';

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
	 * Outputs the html for the Environment Report page.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function show_menu_report()
	{
		$report = LiteSpeed_Cache::generate_environment_report();
		$desc = LiteSpeed_Cache::build_paragraph(
			__('The environment report contains detailed information about your configuration.', 'litespeed-cache'),
			__('If you run into any issues, please include the contents of this text area in your support message.', 'litespeed-cache'),
			__('To easily grab the content, click into the text area and enter ctrl + a to select all and ctrl + c to copy to your clipboard.', 'litespeed-cache'),
			sprintf(__('Alternatively, this information is also saved in %s.', 'litespeed-cache'),
				'wp-content/plugins/litespeed-cache/environment_report.txt')
			)
			. '<br><br>'
			. __('This text area contains the following content:', 'litespeed-cache')
			. '<br>'
			. __('Server Variables, Plugin Options, WordPress information (version, locale, active plugins, etc.), and .htaccess file content.', 'litespeed-cache');


		$buf = '<div class="wrap"><h2>LiteSpeed Cache Report</h2>';
		$buf .= '<div class="welcome-panel"><h4>' . $desc . '</h4>';
		$buf .= $this->input_field_textarea('litespeed-report', $report, '20',
			'80', '', true);

		$buf .= '</div></div>';

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
	private function show_settings_general($options)
	{
		$feed_ttl_desc = LiteSpeed_Cache::build_paragraph(
			__('Specify how long, in seconds, feeds are cached.', 'litespeed-cache'),
			__('If this is set to a number less than 30, feeds will not be cached.', 'litespeed-cache')
		);
		$notfound_ttl_desc = LiteSpeed_Cache::build_paragraph(
			__('Specify how long, in seconds, 404 pages are cached.', 'litespeed-cache'),
			__('If this is set to a number less than 30, 404 pages will not be cached.', 'litespeed-cache')
		);
		$cache_commenters_desc = LiteSpeed_Cache::build_paragraph(
			__('When checked, commenters will not be able to see their comments awaiting moderation.', 'litespeed-cache'),
			__('Disabling this option will display those types of comments, but the cache will not perform as well.', 'litespeed-cache')
		);
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
		elseif(intval($options[$id]) === 2) {
			$options[$id] = 1;
		}

		$input_enable = $this->input_field_radio($id, $enable_levels, intval($options[$id])) ;

		$enable_desc = '<strong>' . __('NOTICE', 'litespeed-cache') . ': </strong>'
		. __('When disabling the cache, all cached entries for this blog will be purged.', 'litespeed-cache')
		. '<br>'
		. sprintf(wp_kses(
			__('Please visit the <a href="%sadmin.php?page=lscache-info">information</a> page on how to test the cache.',
				'litespeed-cache'),
				array( 'a' => array( 'href' => array() ) )), get_admin_url());

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
				__('Specify how long, in seconds, public pages are cached. Minimum is 30 seconds.', 'litespeed-cache'));

		$id = LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL ;
		$input_front_ttl = $this->input_field_text($id, $options[$id], 10, 'regular-text',
				__('seconds', 'litespeed-cache')) ;
		$buf .= $this->display_config_row(__('Default Front Page TTL', 'litespeed-cache'), $input_front_ttl,
				__('Specify how long, in seconds, the front page is cached. Minimum is 30 seconds.', 'litespeed-cache'));

		$id = LiteSpeed_Cache_Config::OPID_FEED_TTL ;
		$input_feed_ttl = $this->input_field_text($id, $options[$id], 10, 'regular-text',
				__('seconds', 'litespeed-cache')) ;
		$buf .= $this->display_config_row(__('Default Feed TTL', 'litespeed-cache'), $input_feed_ttl,
				$feed_ttl_desc);

		$id = LiteSpeed_Cache_Config::OPID_404_TTL ;
		$input_404_ttl = $this->input_field_text($id, $options[$id], 10, 'regular-text',
				__('seconds', 'litespeed-cache')) ;
		$buf .= $this->display_config_row(__('Default 404 Page TTL', 'litespeed-cache'),
				$input_404_ttl, $notfound_ttl_desc);

		$id = LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS ;
		$cache_commenters = $this->input_field_checkbox('lscwp_' . $id, $id, $options[$id]) ;
		$buf .= $this->display_config_row(
			__('Enable Cache for Commenters', 'litespeed-cache'),
			$cache_commenters, $cache_commenters_desc);

		if (!is_multisite()) {
			$buf .= $this->build_setting_purge_on_upgrade($options);
			$buf .= $this->build_setting_mobile_view($options);
		}

		$buf .= $this->input_group_end() ;
		return $buf ;
	}

	/**
	 * Builds the html for the specific pages settings tab.
	 *
	 * @since 1.0.10
	 * @access private
	 * @param array $options The current configuration options.
	 * @return string The html for the specific pages tab.
	 */
	private function show_settings_specific($options)
	{
		$buf = $this->input_group_start(__('Specific Pages', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_CACHE_LOGIN;
		$cache_login = $this->input_field_checkbox('lscwp_' . $id, $id, $options[$id]) ;
		$buf .= $this->display_config_row(__('Enable Cache for Login Page', 'litespeed-cache'), $cache_login,
			__('Unchecking this option may negatively affect performance.', 'litespeed-cache'));

		if (!is_multisite()) {
			$buf .= $this->build_setting_cache_favicon($options);
			$buf .= $this->build_setting_cache_resources($options);
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
	private function show_settings_purge($purge_options)
	{
		$select_all_desc = LiteSpeed_Cache::build_paragraph(
			__('Select "All" if you have dynamic widgets linked to posts on pages other than the front or home pages.', 'litespeed-cache'),
			__('Other checkboxes will be ignored.', 'litespeed-cache')
		);
		$buf = $this->input_group_start(__('Auto Purge Rules For Publish/Update', 'litespeed-cache'),
		__('Select which pages will be automatically purged when posts are published/updated.', 'litespeed-cache')
		. '<br>'
		. '<b>' . __('Note', 'litespeed-cache') . ': </b>' . $select_all_desc
		. '<br>'
		. '<b>' . __('Note', 'litespeed-cache') . ': </b>'
		. __('Select only the archive types that you are currently using, the others can be left unchecked.', 'litespeed-cache'));

		$tr = '<tr><th scope="row" colspan="2" class="th-full">';
		$endtr = "</th></tr>\n";
		$buf .= $tr;

		$spacer = '&nbsp;&nbsp;&nbsp;';

		$pval = LiteSpeed_Cache_Config::PURGE_ALL_PAGES;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('All pages', 'litespeed-cache'));

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_FRONT_PAGE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Front page', 'litespeed-cache'));

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_HOME_PAGE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Home page', 'litespeed-cache'));

		$buf .= $endtr . $tr ;

		$pval = LiteSpeed_Cache_Config::PURGE_AUTHOR;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Author archive', 'litespeed-cache'));

		$buf .= $spacer ;

		$pval = LiteSpeed_Cache_Config::PURGE_POST_TYPE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Post type archive', 'litespeed-cache'));

		$buf .= $endtr . $tr;

		$pval = LiteSpeed_Cache_Config::PURGE_YEAR;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Yearly archive', 'litespeed-cache'));

		$buf .= $spacer;

		$pval = LiteSpeed_Cache_Config::PURGE_MONTH;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Monthly archive', 'litespeed-cache'));

		$buf .= $spacer;

		$pval = LiteSpeed_Cache_Config::PURGE_DATE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Daily archive', 'litespeed-cache'));

		$buf .= $endtr . $tr;

		$pval = LiteSpeed_Cache_Config::PURGE_TERM;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				__('Term archive (include category, tag, and tax)', 'litespeed-cache'));

		$buf .= $endtr;
		$buf .= $this->input_group_end();
		return $buf;
	}

	/**
	 * Builds the html for the do not cache settings tab.
	 *
	 * @since 1.0.1
	 * @access private
	 * @param array $options The current configuration options.
	 * @return string The html for the do not cache tab.
	 */
	private function show_settings_excludes($options)
	{

		$uri_description =
			__('Enter a list of urls that you do not want to have cached.', 'litespeed-cache')
			. '<br>'
			. __('The urls will be compared to the REQUEST_URI server variable.', 'litespeed-cache')
			. '<br>'
			. __('There should only be one url per line.', 'litespeed-cache')
			. '<br><br>
			<b>' . __('NOTE:', 'litespeed-cache') . ' </b>'
			. __('URLs must start with a \'/\' to be correctly matched.', 'litespeed-cache')
			. '<br>'
			. __('To do an exact match, add \'$\' to the end of the URL.', 'litespeed-cache')
			. '<br>'
			. __('Any surrounding whitespaces will be trimmed.', 'litespeed-cache')
			. '<br><br>'
			. sprintf(__('e.g. to exclude %s, you would have:', 'litespeed-cache'),'http://www.example.com/excludethis.php')
			. '<br>
			<pre>/excludethis.php</pre>
			<br>'
			. sprintf(__('Similarly, to exclude %s(accessed with the /blog), you would have:', 'litespeed-cache'),
				'http://www.example.com/blog/excludethis.php')
			. '<br>
			<pre>/blog/excludethis.php</pre>
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
			$excludes_buf = implode("\n", array_map('get_cat_name', $id_list));
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
			$tags_list = array_map('get_tag', $id_list);
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
		$cookie_buf = $this->build_setting_exclude_cookies($options, $cookie_title, $cookie_desc);

		$buf .= $this->input_group_start($cookie_title, $cookie_desc);
		$buf .= $tr . $cookie_buf . $endtr;
		$buf .= $this->input_group_end();

		$ua_title = '';
		$ua_desc = '';
		$ua_buf = $this->build_setting_exclude_useragent($options, $ua_title, $ua_desc);

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
	private function show_settings_advanced($options)
	{
		$cookie_title = '';
		$cookie_desc = '';
		$advanced_desc = LiteSpeed_Cache::build_paragraph(
			'<strong>' . __('NOTICE', 'litespeed-cache') . ':</strong>',
			__('These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache'),
			__('Please take great care when changing any of these settings.', 'litespeed-cache'),
			__("If you have any questions, do not hesitate to submit a support thread.", 'litespeed-cache')
		);
		$adv_cache_desc = LiteSpeed_Cache::build_paragraph(
			__('The advanced-cache.php file is used by many caching plugins to signal that a cache is active.', 'litespeed-cache'),
			__('When this option is checked and this file is detected as belonging to another plugin, LiteSpeed Cache will not cache.', 'litespeed-cache')
		);

		$tag_prefix_desc = LiteSpeed_Cache::build_paragraph(
			__('Add an alpha-numeric prefix to cache and purge tags.', 'litespeed-cache'),
			__('This can be used to prevent issues when using multiple LiteSpeed caching extensions on the same server.', 'litespeed-cache')
		);

		$buf = $this->input_group_start(__('Advanced Settings', 'litespeed-cache'),
										$advanced_desc);
		$buf .= $this->input_group_end();

		$id = LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE;
		$check_adv = $this->input_field_checkbox('lscwp_' . $id, $id, $options[$id]);
		$buf .= $this->input_group_start(
			__('Check advanced-cache.php', 'litespeed-cache')
			. '&nbsp;' . $check_adv,
			$adv_cache_desc
			. '<br><br>'
			. __('Uncheck this option only if the other plugin is used for non-caching purposes, such as minifying css/js files.', 'litespeed-cache'));
		$buf .= $this->input_group_end();

		$cookie_buf = $this->build_setting_login_cookie($options,
				$cookie_title, $cookie_desc);
		$buf .= $this->input_group_start($cookie_title, $cookie_desc);
		$buf .= $cookie_buf;
		$buf .= $this->input_group_end();

		$id = LiteSpeed_Cache_Config::OPID_TAG_PREFIX;
		$buf .= $this->input_group_start(
			__('Cache Tag Prefix', 'litespeed-cache'), $tag_prefix_desc);
		$buf .= $this->input_field_text($id, $options[$id]);
		$buf .= $this->input_group_end();

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
	private function show_settings_test($options)
	{
		$buf = $this->input_group_start(__('Developer Testing', 'litespeed-cache')) ;

		$debug_desc = LiteSpeed_Cache::build_paragraph(
			__('Outputs to WordPress debug log.', 'litespeed-cache'),
			__('This should be set to off once everything is working to prevent filling the disk.', 'litespeed-cache'),
			__('The Admin IP option will only output log messages on requests from admin IPs.', 'litespeed-cache'),
			__('The logs will be outputted to the debug.log in your wp-content directory.', 'litespeed-cache')
		);

		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS ;
		$input_admin_ips = $this->input_field_text($id, $options[$id], '', 'regular-text') ;
		$buf .= $this->display_config_row(__('Admin IPs', 'litespeed-cache'), $input_admin_ips,
		__('Allows listed IPs (space or comma separated) to perform certain actions from their browsers.', 'litespeed-cache')
		. '<br>'
		. sprintf(wp_kses(__('More information about the available commands can be found <a href="%s">here</a>.', 'litespeed-cache'),
				array( 'a' => array( 'href' => array() ))),
				get_admin_url() . 'admin.php?page=lscache-info#adminip'));

		$id = LiteSpeed_Cache_Config::OPID_DEBUG ;
		$debug_levels = array(
			LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE => __('Off', 'litespeed-cache'),
			LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE => __('On', 'litespeed-cache'),
			LiteSpeed_Cache_Config::OPID_ENABLED_NOTSET => __('Admin IP only', 'litespeed-cache'),
		);
		$input_debug = $this->input_field_select($id, $debug_levels, $options[$id]) ;
		$buf .= $this->display_config_row(__('Debug Log', 'litespeed-cache'),
			$input_debug, $debug_desc) ;

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
		$checkbox_desc = LiteSpeed_Cache::build_paragraph(
			__('When checked, mobile views will be cached separately.', 'litespeed-cache'),
			__('A site built with responsive design does not need to check this.', 'litespeed-cache')
		);
		$list_para = LiteSpeed_Cache::build_paragraph(
			sprintf(__('SYNTAX: Each entry should be separated with a bar, %s', 'litespeed-cache'), "'|'."),
			sprintf(__('Any spaces should be escaped with a backslash before the space, %s', 'litespeed-cache'), "'\\ '.")
		);
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$wp_default_mobile = 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi';

		$id = LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED ;
		$list_id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST;
		$default_id = 'lscwp_' . $id . '_default';
		$warning_id = 'lscwp_' . $id . '_warning';
		$cache_enable_id = is_network_admin()
			? LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED
			: LiteSpeed_Cache_Config::OPID_ENABLED;
		$enabled = $options[$id];

		clearstatcache();
		$buf = $this->input_field_hidden($warning_id,
		__('WARNING: Unchecking this option will clear the Mobile View List. Press OK to confirm this action.', 'litespeed-cache'));
		$mv_enabled = $this->input_field_checkbox('lscwp_' . $id, $id, $enabled, '',
				'lscwpCheckboxConfirm(this, \'' . $list_id . '\')', !$file_writable) ;

		$buf .= $this->display_config_row(
			__('Enable Separate Mobile View', 'litespeed-cache'), $mv_enabled,
			$checkbox_desc);

		$mv_list_desc = $list_para . '<br>'
		. sprintf(__('The default list WordPress uses is %s', 'litespeed-cache'), $wp_default_mobile)
		. '<br><strong>' . __('NOTICE:', 'litespeed-cache') . ' </strong>'
		. __('This setting will edit the .htaccess file.', 'litespeed-cache');

		$mv_str = '';
		if (($options[$cache_enable_id]) && ($enabled)) {
			$ret = LiteSpeed_Cache_Admin_Rules::get_instance()->get_common_rule(
				'MOBILE VIEW', 'HTTP_USER_AGENT', $mv_str);
		}
		elseif ($enabled) {
			$ret = true;
			$mv_str = $options[LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST];
		}
		elseif ($options[LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST] == false) {
			$ret = true;
			$mv_str = '';
		}
		else {
			$ret = false;
			$mv_str = sprintf(__('Expected false, got %s', 'litespeed-cache'),
				$mv_str);
		}
		if ($ret !== true) {
			$mv_list = '<p class="attention">'
			. sprintf(__('Error getting current rules: %s', 'litespeed-cache'),
				$mv_str) . '</p>';
		}
		elseif ((($enabled) && ($mv_str === $options[LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST]))
			|| ((!$enabled) && ($mv_str === ''))) {
			// can also use class 'mejs-container' for 100% width.
			$mv_list = $this->input_field_text($list_id, $mv_str, '', 'widget ui-draggable-dragging code', '',
					($options[$id] ? false : true)) ;

			$default_fill = (($mv_str == '') ? $wp_default_mobile : $mv_str);
			$buf .= $this->input_field_hidden($default_id, $default_fill);
		}
		else {
			$list_error = LiteSpeed_Cache::build_paragraph(
				__('Htaccess did not match configuration option.', 'litespeed-cache'),
				__('Please re-enter the mobile view setting.', 'litespeed-cache'),
				sprintf(__('List in WordPress database: %s', 'litespeed-cache'),
					$options[LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST])
			);
			$mv_list = $this->input_field_text($list_id, '', '',
				'widget ui-draggable-dragging code', '', ($options[$id] ? false : true))
				. '<p class="attention">' . $list_error . '</p>';

			$default_fill = (($mv_str == '') ? $wp_default_mobile : $mv_str);
			$buf .= $this->input_field_hidden($default_id, $default_fill);
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
	 * @param array $options The currently configured options.
	 * @param string $cookie_title Returns the cookie title string.
	 * @param string $cookie_desc Returns the cookie description string.
	 * @return string Returns the cookie text area on success, error message on failure.
	 */
	private function build_setting_exclude_cookies($options, &$cookie_title,
			&$cookie_desc)
	{
		$desc_para = LiteSpeed_Cache::build_paragraph(
			__('SYNTAX: Cookies should be listed one per line.', 'litespeed-cache'),
			sprintf(__('Spaces should have a backslash in front of them, %s', 'litespeed-cache'), "'\ '.")
		);
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;
		$cookie_title = __('Cookie List', 'litespeed-cache');
		$cookie_desc = __('To prevent cookies from being cached, enter it in the text area below.', 'litespeed-cache')
				. '<br>' . $desc_para
				. '<br><strong>' . __('NOTICE:', 'litespeed-cache') . ' </strong>'
				. __('This setting will edit the .htaccess file.', 'litespeed-cache');

		$excludes_buf = str_replace('|', "\n", $options[$id]);
		return $this->input_field_textarea($id, $excludes_buf, '5', '80', '',
				!$file_writable);
	}

	/**
	 * Builds the html for the user agent excludes configuration.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param array $options The currently configured options.
	 * @param string $ua_title Returns the user agent title string.
	 * @param string $ua_desc Returns the user agent description string.
	 * @return string Returns the user agent text field on success,
	 * error message on failure.
	 */
	private function build_setting_exclude_useragent($options, &$ua_title,
		&$ua_desc)
	{
		$desc_para = LiteSpeed_Cache::build_paragraph(
			sprintf(__('SYNTAX: Separate each user agent with a bar, %s.', 'litespeed-cache'), "'|'"),
			sprintf(__('Spaces should have a backslash in front of them, %s.', 'litespeed-cache'), "")
		);
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
				LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		$ua_title = __('User Agent List', 'litespeed-cache');
		$ua_desc = __('To prevent user agents from being cached, enter it in the text field below.', 'litespeed-cache')
				. '<br>' . $desc_para
				. '<br><strong>' . __('NOTICE:', 'litespeed-cache') . ' </strong>'
				. __('This setting will edit the .htaccess file.', 'litespeed-cache');
		$ua_list = $this->input_field_text($id, $options[$id], '',
					'widget ui-draggable-dragging', '', !$file_writable);
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
		$match = '';
		$sub = '';

		$cookie_title = __('Login Cookie', 'litespeed-cache');
		$cookie_desc = LiteSpeed_Cache::build_paragraph(
			__('SYNTAX: alphanumeric and "_".', 'litespeed-cache'),
			__('No spaces and case sensitive.', 'litespeed-cache'),
			__('MUST BE UNIQUE FROM OTHER WEB APPLICATIONS.', 'litespeed-cache'),
			'<br>'
			. sprintf(__('The default login cookie is %s.', 'litespeed-cache'),
				'_lscache_vary'),
			__('The server will determine if the user is logged in based on the existance of this cookie.', 'litespeed-cache'),
			__('This setting is useful for those that have multiple web applications for the same domain.', 'litespeed-cache'),
			__('If every web application uses the same cookie, the server may confuse whether a user is logged in or not.', 'litespeed-cache'),
			__('The cookie set here will be used for this WordPress installation.', 'litespeed-cache'),
			'<br><br>' . __('Example use case:', 'litespeed-cache'),
			'<br>' . sprintf(__('There is a WordPress install for %s.', 'litespeed-cache'),
				'<u>www.example.com</u>'),
			'<br>'
			. sprintf(__('Then there is another WordPress install (NOT MULTISITE) at %s', 'litespeed-cache'),
				'<u>www.example.com/blog/</u>'),
			'<br>'
			. __('The cache needs to distinguish who is logged into which WordPress site in order to cache correctly.', 'litespeed-cache')
		);

		if (LiteSpeed_Cache_Admin_Rules::get_instance()->get_rewrite_rule('LOGIN COOKIE',
				$match, $sub, $cookie) === false) {
			return '<p class="attention">'
			. sprintf(__('Error getting current rules: %s', 'litespeed-cache'), $match) . '</p>';
		}
		if (!empty($cookie)) {
			if (strncasecmp($cookie, 'Cache-Vary:', 11)) {
				return '<p class="attention">'
					. sprintf(__('Error: invalid login cookie. Please check the %s file', 'litespeed-cache'), '.htaccess')
					. '</p>';
			}
			$cookie = substr($cookie, 11);
		}
		if (($options[LiteSpeed_Cache_Config::OPID_ENABLED])
			&& ($cookie != $options[$id])) {
			echo $this->build_notice(self::NOTICE_YELLOW,
					__('WARNING: The .htaccess login cookie and Database login cookie do not match.', 'litespeed-cache'));
		}
		return $this->input_field_text($id, $options[$id], '', '', '',
			!$file_writable);
	}

	/**
	 * Builds the html for the purge on upgrade configurations.
	 *
	 * @since 1.0.10
	 * @access private
	 * @param array $options The currently configured options.
	 * @return string The html for purging on upgrade configurations.
	 */
	private function build_setting_purge_on_upgrade($options)
	{
		$id = LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE;
		$purge_upgrade = $this->input_field_checkbox('lscwp_' . $id, $id, $options[$id]);
		return $this->display_config_row(__('Purge All on upgrade', 'litespeed-cache'), $purge_upgrade,
		__('When checked, the cache will automatically purge when any plugins, themes, or WordPress core is upgraded.', 'litespeed-cache'));
	}

	/**
	 * Builds the html for the cache favicon configurations.
	 *
	 * @since 1.0.8
	 * @access private
	 * @param array $options The currently configured options.
	 * @return string The html for caching favicon configurations.
	 */
	private function build_setting_cache_favicon($options)
	{
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
			LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$title = __('Cache favicon.ico', 'litespeed-cache');
		$desc = LiteSpeed_Cache::build_paragraph(
			__('favicon.ico is requested on most pages.', 'litespeed-cache'),
			__('Caching this recource may improve server performance by avoiding unnecessary php calls.', 'litespeed-cache')
		);
		$id = LiteSpeed_Cache_Config::OPID_CACHE_FAVICON ;
		$cache_favicon = $this->input_field_checkbox('lscwp_' . $id, $id,
			$options[$id], '', '', !$file_writable);
		return $this->display_config_row($title, $cache_favicon, $desc);
	}

	/**
	 * Builds the html for the cache PHP resources configurations.
	 *
	 * @since 1.0.8
	 * @access private
	 * @param array $options The currently configured options.
	 * @return string The html for caching resource configurations.
	 */
	private function build_setting_cache_resources($options)
	{
		$file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(
			LiteSpeed_Cache_Admin_Rules::WRITABLE);
		$title = __('Enable Cache for PHP Resources', 'litespeed-cache');
		$desc = LiteSpeed_Cache::build_paragraph(
			__('Some themes and plugins add resources via a PHP request.', 'litespeed-cache'),
			__('Caching these pages may improve server performance by avoiding unnecessary php calls.', 'litespeed-cache'));
		$id = LiteSpeed_Cache_Config::OPID_CACHE_RES;
		$cache_res = $this->input_field_checkbox('lscwp_' . $id, $id,
			$options[$id], '', '', !$file_writable);
		return $this->display_config_row($title, $cache_res, $desc);
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
			'Yoast SEO',
			'Wordfence Security',
			'NextGen Gallery'
		);

		$known_uncompat = array(

		);

		$compat_desc = LiteSpeed_Cache::build_paragraph(
			__('Please comment listing the plugins that you are using and how they are functioning on the support thread.', 'litespeed-cache'),
			__('With your help, we can provide the best WordPress caching solution.', 'litespeed-cache')
		);


		$buf = '<h3>' . __('LiteSpeed Cache Plugin Compatibility', 'litespeed-cache') . '</h3>'
		. '<h4>'
		. $compat_desc
		. '<br /><a href="https://wordpress.org/support/topic/known-supported-plugins?replies=1" rel="noopener noreferrer" target="_blank">'
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
		$nocache_desc = LiteSpeed_Cache::build_paragraph(
			__('This is used to display a page without caching it.', 'litespeed-cache'),
			__('An example use case is to compare a cached version of a page with an uncached version.', 'litespeed-cache')
		);
		$purge_desc = LiteSpeed_Cache::build_paragraph(
			__('This is used to purge most cache tags associated with the page.', 'litespeed-cache'),
			__('The lone exception is the blog ID tag.', 'litespeed-cache'),
			__('Note that this means that pages with the same cache tag will be purged as well.', 'litespeed-cache')
		);
		$showheaders_desc = LiteSpeed_Cache::build_paragraph(
			__('This is used to show all the cache headers associated with a page.', 'litespeed-cache'),
			__('This may be useful for debugging purposes.', 'litespeed-cache')
		);
		$buf = '<h3>'
		. __('Admin IP Query String Actions', 'litespeed-cache') . '</h3>';

		$buf .= '<h4>'
		. __('The following commands are available to the admin and do not require log-in, providing quick access to actions on the various pages.', 'litespeed-cache')
		. '</h4>';

		$buf .= '<h4>' . __('Action List:', 'litespeed-cache') . '</h4>';
		$buf .= '<ul><li>NOCACHE - ' . $nocache_desc
		. '</li>'
		. '<li>PURGE - ' . $purge_desc
		. '</li>'
		. '<li>PURGEALL - '
		. __('This is used to purge all entries in the cache.', 'litespeed-cache')
		. '</li>'
		. '<li>PURGESINGLE - '
		. __('This is used to purge the first cache tag associated with the page.', 'litespeed-cache')
		. '</li>'
		. '<li>SHOWHEADERS - ' . $showheaders_desc
		. '</li></ul>';


		$buf .= '<h5>'
		. sprintf(__('To trigger the action for a page, access the page with the query string %s', 'litespeed-cache'),
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
		$mv_header = __('Mobile Views:', 'litespeed-cache');
		$mv_desc = LiteSpeed_Cache::build_paragraph(
			__('Some sites have adaptive views, meaning the page sent will adapt to the browser type (desktop vs mobile).', 'litespeed-cache'),
			__('This rewrite rule is used for sites that load a different page for each type.', 'litespeed-cache'))
		. '<br>'
		. __('This configuration can be added on the settings page in the General tab.', 'litespeed-cache');
		$mv_example = 'RewriteCond %{HTTP_USER_AGENT} Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi [NC]
RewriteRule .* - [E=Cache-Control:vary=ismobile]';


		$cookie_header = __('Do Not Cache Cookies:', 'litespeed-cache');
		$cookie_desc =
		__('Another common rewrite rule is to notify the cache not to cache when it sees a specified cookie name.', 'litespeed-cache')
		. '<br>'
		. __('This configuration can be added on the settings page in the Do Not Cache tab.', 'litespeed-cache');
		$cookie_example = 'RewriteCond %{HTTP_COOKIE} dontcachecookie
RewriteRule .* - [E=Cache-Control:no-cache]';


		$ua_header = __('Do Not Cache User Agent:', 'litespeed-cache');
		$ua_desc =
		__('A not so commonly used rewrite rule is to notify the cache not to cache when it sees a specified User Agent.', 'litespeed-cache')
		. '<br>'
		. __('This configuration can be added on the settings page in the Do Not Cache tab.', 'litespeed-cache');
		$ua_example = 'RewriteCond %{HTTP_USER_AGENT} dontcacheuseragent
RewriteRule .* - [E=Cache-Control:no-cache]';


		// begin buffer

		$buf = '<h3>' . __('LiteSpeed Cache Common Rewrite Rules', 'litespeed-cache') . '</h2>';

		if ((is_multisite()) && (!is_network_admin())) {

			$buf .= '<p><span style="color: black;font-weight: bold">' . __('NOTE:', 'litespeed-cache')
			. ' </span>'
			. __('The following configurations can only be changed by the network admin.', 'litespeed-cache')
			. '<br>'
			. __('If you need to make changes to them, please contact the network admin to make the changes.', 'litespeed-cache')
			. '</p>';
		}
		else {
			$buf .= '<p><span style="color: black;font-weight: bold">' . __('NOTICE:', 'litespeed-cache')
			. ' </span>'
			. __('The following rewrite rules can be configured in the LiteSpeed Cache settings page.', 'litespeed-cache')
			. '<br>'
			. LiteSpeed_Cache::build_paragraph(
				__('If you need to make changes to them, please do so on that page.', 'litespeed-cache'),
				__('It will automatically generate the correct rules in the htaccess file.', 'litespeed-cache')
			)
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
	 * Outputs a notice to the admin panel when the plugin is installed
	 * via the WHM plugin.
	 *
	 * @since 1.0.12
	 * @access public
	 */
	public function show_display_installed()
	{
		$url = LiteSpeed_Cache_Admin::build_lscwpctrl_url(
			LiteSpeed_Cache::ADMINQS_DISMISS, 'litespeed-dismiss');
		$buf = LiteSpeed_Cache::build_paragraph(
			'<h3>'
			. __('LiteSpeed Cache plugin is installed!', 'litespeed-cache')
			. '</h3>',
			__('This message indicates that the plugin was installed by your server admin.', 'litespeed-cache'),
			__('Our plugin is used to cache pages - a simple way to improve the performance of your site.', 'litespeed-cache'),
			__('However, we have no way of knowing all the possible customizations that you may have done.', 'litespeed-cache'),
			__('For that reason, we ask that you test your site to make sure everything still functions properly.', 'litespeed-cache')
		);
		$buf .= '<br><br>'
			. __('Examples of test cases include:', 'litespeed-cache')
			. '<ul><li>'
			. __('Visit your site while logged out', 'litespeed-cache')
			. '</li><li>'
			. __('Create a post, make sure the front page is accurate', 'litespeed-cache')
			. '</li></ul>';

		$buf .=
			sprintf(wp_kses(
				__('If you have any questions, we are always happy to answer any questions on our <a href="%s" rel="%s" target="%s">support forum</a>.',
					'litespeed-cache'),
				array('a' =>array('href' => array(), 'rel' => array(),
					'target' => array()))),
				'https://wordpress.org/support/plugin/litespeed-cache',
				'noopener noreferrer', '_blank');
		$buf .= '<br>'
			. __('If you would rather not move at litespeed, you can deactivate this plugin.',
				'litespeed-cache')
			. '<br><br>'
			. sprintf(wp_kses(__(
				'<a href="%s">OK, got it (dismiss)</a>', 'litespeed-cache'),
				array('a' =>array('href' => array()))), $url);


		$this->add_notice(self::NOTICE_BLUE, $buf);
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
		if ($this->get_disable_all()) {
			$disabled = true;
		}
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
	 * @param string $checked_value The currently selected option.
	 * @return string The select field html.
	 */
	private function input_field_radio( $id, $radiooptions, $checked_value)
	{
		$buf = '<fieldset>';
		foreach ( $radiooptions as $val => $label ) {
			$buf .= '<label>';
			$buf .= '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME
				. '[' . $id . ']" type="radio" id="'
				. LiteSpeed_Cache_Config::OPTION_NAME . '[' . $label . ']" value="' . $val . '"' ;
			if (($checked_value === $val)) {
				$buf .= ' checked="checked"' ;
			}
			if ($this->get_disable_all()) {
				$buf .= ' disabled="true"' ;
			}
			$buf .= '><span>' . $label . '&nbsp;&nbsp;</span></label>';
			$buf .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		return $buf . '</fieldset>';
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
				. $id . '" ';

		if ($this->get_disable_all()) {
			$buf .= 'disabled="true" ';
		}

		$buf .= '>' ;
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
		if ($this->get_disable_all()) {
			$readonly = true;
		}
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
		if ($this->get_disable_all()) {
			$readonly = true;
		}
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
