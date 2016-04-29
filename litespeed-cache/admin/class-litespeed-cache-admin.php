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
class LiteSpeed_Cache_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name ;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version ;
	private $messages ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{

		$this->plugin_name = $plugin_name ;
		$this->version = $version ;

		add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' )) ;

		//Additional links on the plugin page
		if ( is_network_admin() ) {
			add_action('network_admin_menu', array( $this, 'register_admin_menu' )) ;
		}
		else {
			add_action('admin_menu', array( $this, 'register_admin_menu' )) ;
		}

		add_action('admin_init', array( $this, 'admin_init' )) ;
		$plugin_dir = plugin_dir_path(dirname(__FILE__)) ;
		add_filter('plugin_action_links_' . plugin_basename($plugin_dir . '/' . $plugin_name . '.php'), array( $this, 'add_plugin_links' )) ;
	}

	/**
	 * Register the stylesheets and JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/litespeed-cache-admin.css', array(), $this->version, 'all') ;
		wp_enqueue_script('jquery-ui-tabs') ;
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/litespeed-cache-admin.js', array( 'jquery' ), $this->version, false) ;
	}

	/**
	 * Register the admin menu display.
	 *
	 * @since	1.0.0
	 */
	public function register_admin_menu()
	{
		$capability = is_network_admin() ? 'manage_network_options' : 'manage_options' ;
		if ( current_user_can($capability) ) {

			$this->register_dash_menu();

			$lscache_admin_settings_page = add_options_page('LiteSpeed Cache', 'LiteSpeed Cache', $capability, 'litespeedcache', array( $this, 'show_menu_settings' )) ;
			// adds help tab
			add_action('load-' . $lscache_admin_settings_page, array( $this, 'add_help_tabs' )) ;
		}
	}

	/**
	 * add_submenu_page callback to determine which submenu page to display
	 * if the admin selected a LiteSpeed Cache dashboard page.
	 *
	 * @since 1.0.4
	 */
	public function dash_select()
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
					$this->show_info_faqs();
				}
				break;
			case 'i':
				if (($selection_len == 4)
						&& (strncmp($selection, 'info', $selection_len) == 0)) {
					$this->show_info_info();
				}
				break;
			case 's':
				if (($selection_len == 8)
						&& (strncmp($selection, 'settings', $selection_len) == 0)) {
					$this->show_info_settings();
				}
				break;
			case 'e':
				if (($selection_len == 13)
						&& (strncmp($selection, 'edit-htaccess', $selection_len) == 0)) {
					$this->show_edit_htaccess();
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Redirects the page access to the settings page when the settings
	 * submenu page is selected.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public static function redir_settings()
	{
		wp_redirect(admin_url('options-general.php?page=litespeedcache'), 301);
		exit;
	}

	/**
	 * Helper function to set up a submenu page.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $page_title The page title.
	 * @param string $menu_title The title that appears on the menu.
	 * @param string $menu_slug The slug of the page.
	 * @param string $cb The callback to call if selected.
	 */
	private function add_submenu($page_title, $menu_title, $menu_slug, $cb = '')
	{
		if (!empty($cb)) {
			$fn = array($this, $cb);
		}
		$submenu_page = add_submenu_page('lscache-dash', $page_title,
				$menu_title, 'manage_options', $menu_slug, $fn);
		add_action('load-' . $submenu_page, array( $this, 'add_help_tabs' ));
	}

	/**
	 * Registers management submenu pages.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function register_submenu_manage()
	{
		$this::add_submenu(sprintf(__('%s Manager', 'litespeed-cache'),'LiteSpeed Cache'),
				__('Manage', 'litespeed-cache'), 'lscache-dash', 'show_menu_manage');
	}

	/**
	 * Registers settings submenu pages.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function register_submenu_settings()
	{
		$this::add_submenu(sprintf(__('%s Settings', 'litespeed-cache'),'LiteSpeed Cache'),
				__('Settings', 'litespeed-cache'), 'lscache-settings', 'dash_select');

		if ((!is_multisite()) || (is_network_admin())) {
			$this::add_submenu(sprintf(__('%s Edit .htaccess', 'litespeed-cache'),'LiteSpeed Cache'),
					__('Edit ', 'litespeed-cache') . '.htaccess', 'lscache-edit-htaccess', 'dash_select');
		}

	}

	/**
	 * Registers informational submenu pages.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function register_submenu_info()
	{
		$this::add_submenu(sprintf(__('%s Information', 'litespeed-cache'),'LiteSpeed Cache'),
				__('Information', 'litespeed-cache'), 'lscache-info', 'dash_select');
		$this::add_submenu(sprintf(__('%s FAQs', 'litespeed-cache'),'LiteSpeed Cache'),
				__('FAQs', 'litespeed-cache'), 'lscache-faqs', 'dash_select');

	}

	/**
	 * Registers all the submenu page types.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function register_submenus()
	{
		$this->register_submenu_manage();
		$this->register_submenu_settings();
		$this->register_submenu_info();

	}

	/**
	 * Registers the submenu options for the LiteSpeed Cache menu option.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function register_dash_menu()
	{
		$check = add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', 'manage_options',
				'lscache-dash', '', 'dashicons-performance');
		$this->register_submenus();
	}

	/**
	 * Callback that initializes the admin options for LiteSpeed Cache.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_init()
	{
		// check for upgrade
		LiteSpeed_Cache::config()->plugin_upgrade() ;

		// check management action
		$this->check_cache_mangement_actions() ;

		$option_name = LiteSpeed_Cache_Config::OPTION_NAME ;
		if (!is_network_admin()) {
			register_setting($option_name, $option_name, array( $this, 'validate_plugin_settings' )) ;
		}
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
				'<p><a href="https://www.litespeedtech.com/support/forum/" target="_blank">' . __('Support Forum', 'litespeed-cache') . '</a></p>'
		) ;
	}

	/**
	 * Checks the admin selected option for enabling the cache.
	 *
	 * The actual value depends on the type of site.
	 *
	 * If not set is selected, the default action on multisite is to use
	 * the network selection, on singlesite it is enabled.
	 *
	 * @since 1.0.2
	 * @access private
	 * @param array $input The input configurations.
	 * @param array $options Returns the up to date options array.
	 * @return boolean True if enabled, false otherwise.
	 */
	private function validate_enabled($input, &$options)
	{
		$id = LiteSpeed_Cache_Config::OPID_ENABLED_RADIO;
		if ( !isset($input[$id])) {
			return false;
		}
		$radio_enabled = intval($input[$id]);
		$options[$id] = $radio_enabled;
		if ( $radio_enabled != LiteSpeed_Cache_Config::OPID_ENABLED_NOTSET ) {
			return $radio_enabled == LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE;
		}
		if (is_multisite()) {
			return $options[LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED];
		}
		return true;
	}

	/**
	 * Validate common rewrite rules configured by the admin.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param array $input The configurations selected.
	 * @param array $options The current configurations.
	 * @param array $errors Returns error messages added if failed.
	 * @return mixed Returns updated options array on success, false otherwise.
	 */
	private function validate_common_rewrites($input, $options, &$errors)
	{
		$content = '';
		$prefix = '<IfModule LiteSpeed>';
		$engine = 'RewriteEngine on';
		$suffix = '</IfModule>';
		$path = self::get_htaccess_path();

		if (($input[LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED] === false)
			&& ($options[LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED] === false)
			&& ($input[LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES] === $options[LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES])
			&& ($input[LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS] === $options[LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS])) {
			return $options;
		}

		clearstatcache();
		if (self::get_htaccess_contents($content) === false) {
			$errors[] = $content;
			return false;
		}
		elseif (!is_writable($path)) {
			$errors[] = __('File is not writable.', 'litespeed-cache');
			return false;
		}
		$off_begin = strpos($content, $prefix);
		//if not found
		if ($off_begin === false) {
			$output = $prefix . "\n" . $engine . "\n";
			$start_search = NULL;
		}
		else {
			$off_begin += strlen($prefix);
			$off_end = strpos($content, $suffix, $off_begin);
			if ($off_end === false) {
				$errors[] = sprintf(__('Could not find %s close.', 'litespeed-cache'),'IfModule');
				return false;
			}
			--$off_end; // go to end of previous line.
			$off_engine = stripos($content, $engine, $off_begin);
			if ($off_engine !== false) {
				$off_begin = $off_engine + strlen($engine) + 1;
				$output = substr($content, 0, $off_begin);
			}
			else {
				$output = substr($content, 0, $off_begin) . "\n" . $engine . "\n";
			}
			$start_search = substr($content, $off_begin, $off_end - $off_begin);
		}

		$id = LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED;
		if ($input['lscwp_' . $id] === $id) {
			$options[$id] = true;
			$ret = self::set_common_rule($start_search, $output,
					'MOBILE VIEW', 'HTTP_USER_AGENT',
					$input[LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST],
					'E=Cache-Control:vary=ismobile', 'NC');

			if (is_array($ret)) {
				if ($ret[0]) {
					$start_search = $ret[1];
				}
				else {
					// failed.
					$errors[] = $ret[1];
				}
			}

		}
		elseif ($options[$id] === true) {
			$options[$id] = false;
			$ret = self::set_common_rule($start_search, $output,
					'MOBILE VIEW', '', '', '');
			if (is_array($ret)) {
				if ($ret[0]) {
					$start_search = $ret[1];
				}
				else {
					// failed.
					$errors[] = $ret[1];
				}
			}

		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;
		if ($input[$id]) {
			$cookie_list = preg_replace("/[\r\n]+/", '|', $input[$id]);
		}
		else {
			$cookie_list = '';
		}

		$ret = self::set_common_rule($start_search, $output,
				'COOKIE', 'HTTP_COOKIE', $cookie_list, 'E=Cache-Control:no-cache');
		if (is_array($ret)) {
			if ($ret[0]) {
				$start_search = $ret[1];
			}
			else {
				// failed.
				$errors[] = $ret[1];
			}
		}


		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		$ret = self::set_common_rule($start_search, $output,
				'USER AGENT', 'HTTP_USER_AGENT', $input[$id], 'E=Cache-Control:no-cache');
		if (is_array($ret)) {
			if ($ret[0]) {
				$start_search = $ret[1];
			}
			else {
				// failed.
				$errors[] = $ret[1];
			}
		}


		if (!is_null($start_search)) {
			$output .= $start_search . substr($content, $off_end);
		}
		else {
			$output .= $suffix . "\n\n" . $content;
		}
		$ret = self::do_edit_htaccess($output, false);
		if ($ret === false) {
			$errors[] = sprintf(__('Failed to put contents into %s', 'litespeed-cache'), '.htaccess');
			return false;
		}
		return $options;
	}

	/**
	 * Callback function that will validate any changes made in the settings page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $input The configurations selected by the admin when clicking save.
	 * @return array The updated configuration options.
	 */
	public function validate_plugin_settings( $input )
	{
		$config = LiteSpeed_Cache::config() ;
		$options = $config->get_options() ;
		$pattern = "/[\s,]+/" ;
		$errors = array() ;

		$id = LiteSpeed_Cache_Config::OPID_ENABLED ;
		$enabled = $this->validate_enabled($input, $options);
		if ( $enabled !== $options[$id] ) {
			$options[$id] = $enabled;
			$config->wp_cache_var_setter($enabled);
			if (!$enabled) {
				LiteSpeed_Cache::plugin()->purge_all() ;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS ;
		if ( isset($input[$id]) ) {
			$admin_ips = trim($input[$id]) ;
			$has_err = false ;
			if ( $admin_ips ) {
				$ips = preg_split($pattern, $admin_ips, NULL, PREG_SPLIT_NO_EMPTY) ;
				foreach ( $ips as $ip ) {
					if ( ! WP_Http::is_ip_address($ip) ) {
						$has_err = true ;
						break ;
					}
				}
			}

			if ( $has_err ) {
				$errors[] = __('Invalid data in Admin IPs.', 'litespeed-cache') ;
			}
			else if ( $admin_ips != $options[$id] ) {
				$options[$id] = $admin_ips ;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_PUBLIC_TTL ;
		if ( ! isset($input[$id]) || ! ctype_digit($input[$id]) || $input[$id] < 30 ) {
			$errors[] = __('Default Public Cache TTL must be set to 30 seconds or more', 'litespeed-cache') ;
		}
		else {
			$options[$id] = $input[$id] ;
		}

		$id = LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL ;
		if ( ! isset($input[$id]) || ! ctype_digit($input[$id]) || $input[$id] < 30 ) {
			$errors[] = __('Default Front Page TTL must be set to 30 seconds or more', 'litespeed-cache') ;
		}
		else {
			$options[$id] = $input[$id] ;
		}

		$id = LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS;
		$options[$id] = ( $input['check_' . $id] === $id );

		// get purge options
		$pvals = array(
			LiteSpeed_Cache_Config::PURGE_ALL_PAGES,
			LiteSpeed_Cache_Config::PURGE_FRONT_PAGE,
			LiteSpeed_Cache_Config::PURGE_HOME_PAGE,
			LiteSpeed_Cache_Config::PURGE_AUTHOR,
			LiteSpeed_Cache_Config::PURGE_YEAR,
			LiteSpeed_Cache_Config::PURGE_MONTH,
			LiteSpeed_Cache_Config::PURGE_DATE,
			LiteSpeed_Cache_Config::PURGE_TERM,
			LiteSpeed_Cache_Config::PURGE_POST_TYPE
				) ;
		$input_purge_options = array() ;
		foreach ( $pvals as $pval ) {
			$input_name = 'purge_' . $pval ;
			if ( isset($input[$input_name]) && ($pval === $input[$input_name]) ) {
				$input_purge_options[] = $pval ;
			}
		}
		sort($input_purge_options) ;
		$purge_by_post = implode('.', $input_purge_options) ;
		if ( $purge_by_post !== $options[LiteSpeed_Cache_Config::OPID_PURGE_BY_POST] ) {
			$options[LiteSpeed_Cache_Config::OPID_PURGE_BY_POST] = $purge_by_post ;
		}

		$newopt = $this->validate_common_rewrites($input, $options, $errors);
		if ($newopt) {
			$options = $newopt;
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_URI ;
		if ( isset($input[$id]) ) {
			$options[$id] = implode("\n", array_map('trim', explode("\n", $input[$id])));
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT ;
		$options[$id] = '';
		if ( isset($input[$id]) ) {
			$cat_ids = array();
			$cats = explode("\n", $input[$id]);
			foreach ( $cats as $cat ) {
				$cat_name = trim($cat);
				if ( $cat_name == '') {
					continue;
				}
				$cat_id = get_cat_ID($cat_name);
				if ($cat_id == 0) {
					$errors[] = sprintf(__('Removed category "%s" from list, ID does not exist.',
									'litespeed-cache'),$cat_name) ;
				}
				else {
					$cat_ids[] = $cat_id;
				}
			}
			if ( !empty($cat_ids)) {
				$options[$id] = implode(',', $cat_ids);
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG ;
		$options[$id] = '';
		if ( isset($input[$id]) ) {
			$tag_ids = array();
			$tags = explode("\n", $input[$id]);
			foreach ( $tags as $tag ) {
				$tag_name = trim($tag);
				if ( $tag_name == '') {
					continue;
				}
				$term = get_term_by('name', $tag_name, 'post_tag');
				if ($term == 0) {
					$errors[] = sprintf(__('Removed tag "%s" from list, ID does not exist.',
									'litespeed-cache'), $tag_name) ;
				}
				else {
					$tag_ids[] =  $term->term_id;
				}
			}
			if ( !empty($tag_ids)) {
				$options[$id] = implode(',', $tag_ids);
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_TEST_IPS ;
		if ( isset($input[$id]) ) {
			// this feature has not implemented yet
			$test_ips = trim($input[$id]) ;
			$has_err = false ;
			if ( $test_ips ) {
				$ips = preg_split($pattern, $test_ips, NULL, PREG_SPLIT_NO_EMPTY) ;
				foreach ( $ips as $ip ) {
					if ( ! WP_Http::is_ip_address($ip) ) {
						$has_err = true ;
						break ;
					}
				}
			}

			if ( $has_err ) {
				$errors[] = __('Invalid data in Test IPs.', 'litespeed-cache') ;
			}
			else if ( $test_ips != $options[$id] ) {
				$options[$id] = $test_ips ;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_DEBUG ;
		$debug_level = isset($input[$id]) ? intval($input[$id]) : LiteSpeed_Cache_Config::LOG_LEVEL_NONE ;
		if ( ($debug_level != $options[$id]) && ($debug_level >= LiteSpeed_Cache_Config::LOG_LEVEL_NONE) && ($debug_level <= LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG) ) {
			$options[$id] = $debug_level ;
		}

		if ( ! empty($errors) ) {
			add_settings_error(LiteSpeed_Cache_Config::OPTION_NAME, LiteSpeed_Cache_Config::OPTION_NAME, implode('<br>', $errors)) ;
		}

		return $options ;
	}

	/**
	 * Callback that adds LiteSpeed Cache's action links.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $links Previously added links from other plugins.
	 * @return array Links array with the litespeed cache one appended.
	 */
	public function add_plugin_links( $links )
	{
		//$links[] = '<a href="' . admin_url('admin.php?page=litespeedcache') .'">Settings</a>';
		$links[] = '<a href="' . admin_url('options-general.php?page=litespeedcache') . '">' . __('Settings', 'litespeed-cache') . '</a>' ;
		return $links ;
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

		if ( $this->messages ) {
			echo '<div class="success"><p>' . $this->messages . ' </p></div>' . "\n" ;
		}

		echo '<div class="wrap"><h2>' . __('LiteSpeed Cache Management', 'litespeed-cache') . '</h2>'
		. '<div class="welcome-panel"><p>'
		. __('LiteSpeed Cache is maintained and managed by LiteSpeed Web Server.', 'litespeed-cache')
		. __(' You can inform LiteSpeed Web Server to purge cached contents from this screen.', 'litespeed-cache')
		. '</p>'
		. '<p>' . __('More options will be added here in future releases.', 'litespeed-cache') . '</p>' ;

		echo '<form method="post">' ;
		wp_nonce_field(LiteSpeed_Cache_Config::OPTION_NAME) ;

		submit_button(__('Purge Front Page', 'litespeed-cache'), 'primary', 'purgefront') ;
		submit_button(__('Purge All', 'litespeed-cache'), 'primary', 'purgeall') ;
		echo "</form></div></div>\n" ;
	}

	/**
	 * Check if the admin pressed a button in the management page.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function check_cache_mangement_actions()
	{
		if ( isset($_POST['purgeall']) ) {
			LiteSpeed_Cache::plugin()->purge_all() ;
			$this->messages = __('Notified LiteSpeed Web Server to purge the public cache.', 'litespeed-cache') ;
		}
		if ( isset($_POST['purgefront'])){
			LiteSpeed_Cache::plugin()->purge_front();
			$this->messages = __('Notified LiteSpeed Web Server to purge the front page.', 'litespeed-cache') ;
		}
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

		echo '
		 <div id="lsc-tabs">
		 <ul>
		 <li><a href="#general-settings">' . __('General', 'litespeed-cache') . '</a></li>
		 <li><a href="#purge-settings">' . __('Purge Rules', 'litespeed-cache') . '</a></li>
		 <li><a href="#exclude-settings">' . __('Do Not Cache Rules', 'litespeed-cache') . '</a></li>
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
		'</div>
		<div id ="debug-settings">'
		. $this->show_settings_test($options) .
		'</div>'
		. $compatibilities_settings . '</div>' ;

		submit_button() ;
		echo "</form></div>\n" ;
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
	 * Builds the html for the mobile views configurations.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param array $options The currently configured options.
	 * @return string The html for mobile views configurations.
	 */
	private function show_mobile_view($options)
	{

		$wp_default_mobile = 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi';
		$id = LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED ;
		$list_id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST;
		$default_id = 'lscwp_' . $id . '_default';
		$warning_id = 'lscwp_' . $id . '_warning';
		clearstatcache();
		$buf = $this->input_field_hidden($warning_id,
		__('WARNING: Unchecking this option will clear the Mobile View List. Press OK to confirm this action.', 'litespeed-cache'));
		$mv_enabled = $this->input_field_checkbox('lscwp_' . $id, $id, $options[$id], '',
				'lscwpCheckboxConfirm(this, \'' . $list_id . '\')',
				!is_writable(self::get_htaccess_path())) ;

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
		if ($this->get_common_rule('MOBILE VIEW', 'HTTP_USER_AGENT', $mv_str) === true) {
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
	private function show_cookies_exclude(&$cookie_title, &$cookie_desc)
	{
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;
		$cookies_rule = '';
		$cookie_title = __('Cookie List', 'litespeed-cache');
		$cookie_desc = __('To prevent cookies from being cached, enter it in the text area below.', 'litespeed-cache')
				. '<br>' . __('SYNTAX: Cookies should be listed one per line.', 'litespeed-cache')
				. __(' Spaces should have a backslash in front of them, \'\ \'.', 'litespeed-cache')
				. '<br><strong>' . __('NOTICE: ', 'litespeed-cache') . '</strong>'
				. __('This setting will edit the .htaccess file.', 'litespeed-cache');

		if ($this->get_common_rule('COOKIE', 'HTTP_COOKIE', $cookies_rule) === true) {
			// can also use class 'mejs-container' for 100% width.
			$excludes_buf = str_replace('|', "\n", $cookies_rule);
		}
		else {
			$excludes_buf = '<p class="attention">'
			. __('Error getting current rules: ', 'litespeed-cache') . $cookies_rule . '</p>';
		}
		return $this->input_field_textarea($id, $excludes_buf, '5', '80', '',
				!is_writable(self::get_htaccess_path()));
	}

	/**
	 * Builds the html for the user agent excludes configuration.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $ua_title Returns the user agent title string.
	 * @param string $ua_desc Returns the user agent description string.
	 * @return string Returns the user agent text field on success, error message on failure.
	 */
	private function show_useragent_exclude(&$ua_title, &$ua_desc)
	{
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		$ua_rule = '';
		$ua_title = __('User Agent List', 'litespeed-cache');
		$ua_desc = __('To prevent user agents from being cached, enter it in the text field below.', 'litespeed-cache')
				. '<br>' . __('SYNTAX: Separate each user agent with a bar, \'|\'.', 'litespeed-cache')
				. __(' Spaces should have a backslash in front of them, \'\ \'.', 'litespeed-cache')
				. '<br><strong>' . __('NOTICE: ', 'litespeed-cache') . '</strong>'
				. __('This setting will edit the .htaccess file.', 'litespeed-cache');
		if ($this->get_common_rule('USER AGENT', 'HTTP_USER_AGENT', $ua_rule) === true) {
			// can also use class 'mejs-container' for 100% width.
			$ua_list = $this->input_field_text($id, $ua_rule, '', 'widget ui-draggable-dragging', '',
				!is_writable(self::get_htaccess_path())) ;
		}
		else {
			$ua_list = '<p class="attention">'
			. __('Error getting current rules: ', 'litespeed-cache') . $ua_rule . '</p>';
		}
		return $ua_list;
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
		if (  is_multisite() ){
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
			$buf .= $this->show_mobile_view($options);
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
		$cookie_buf = $this->show_cookies_exclude($cookie_title, $cookie_desc);

		$buf .= $this->input_group_start($cookie_title, $cookie_desc);
		$buf .= $tr . $cookie_buf . $endtr;
		$buf .= $this->input_group_end();

		$ua_title = '';
		$ua_desc = '';
		$ua_buf = $this->show_useragent_exclude($ua_title, $ua_desc);

		$buf .= $this->input_group_start($ua_title, $ua_desc);
		$buf .= $tr . $ua_buf . $endtr;
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
	private function show_settings_test( $options )
	{
		$buf = $this->input_group_start(__('Developer Testing', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS ;
		$input_admin_ips = $this->input_field_text($id, $options[$id], '', 'regular-text') ;
		$buf .= $this->display_config_row(__('Admin IPs', 'litespeed-cache'), $input_admin_ips,
				__('Allows listed IPs (space or comma separated) to perform certain actions from their browsers.', 'litespeed-cache')) ;

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
	 * Builds the html for the wp_postviews help page.
	 *
	 * @since 1.0.1
	 * @access private
	 * @return string The html for the wp_postviews help page.
	 */
	private function show_wp_postviews_help()
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
			$buf .= $this->show_wp_postviews_help();
		}
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
	private function show_info_info()
	{

		// Configurations help.
		$buf = '<div class="wrap"><h2>'
		. __('LiteSpeed Cache Information', 'litespeed-cache') . '</h2>';



		$buf .= '<div id="lsc-tabs">'
		. '<ul>'
		. '<li><a href="#config">' . __('Configurations', 'litespeed-cache') . '</a></li>'
		. '<li><a href="#compat">' . __('Plugin Compatibilities', 'litespeed-cache') . '</a></li>'
		. '<li><a href="#commonrw">' . __('Common Rewrite Rules', 'litespeed-cache') . '</a></li>'
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

		$buf .= '</div>'; // id=lsc_tabs
		$buf .= '<h4>'
		. __('If your questions are not answered, try the ', 'litespeed-cache')
		. '<a href=' . get_admin_url() . 'admin.php?page=lscache-faqs>FAQ</a>';
		$buf .= '</div>'; // class=wrap
		echo $buf;
	}

	/**
	 * Outputs the html for the FAQs page.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function show_info_faqs()
	{
		$buf =  '<div class="wrap"><h2>LiteSpeed Cache FAQs</h2>';

		$buf .= '<div class="welcome-panel"><h4>'
		. __('Is the LiteSpeed Cache Plugin for WordPress free?', 'litespeed-cache') . '</h4>'
		. '<p>' . __('Yes, the plugin itself will remain free and open source, but only works with LiteSpeed Web Server 5.0.10+.', 'litespeed-cache')
		. __('You are required to have a LiteSpeed Web Server license with the LSCache module enabled.', 'litespeed-cache') . '</p>';

		$buf .= '<h4>' . __('Where are the cached files stored?', 'litespeed-cache') . '</h4>'
		. '<p>' . __('This plugin only instructs LiteSpeed Web Server on what pages to cache and when to purge. ', 'litespeed-cache')
		. __('The actual cached pages are stored and managed by LiteSpeed Web Server. Nothing is stored on the PHP side.', 'litespeed-cache') . '</p>';

		$buf .= '<h4>' . __('Does LiteSpeed Cache for WordPress work with OpenLiteSpeed?', 'litespeed-cache') . '</h4>'
		. '<p>' . __('LiteSpeed Cache for WordPress currently only works for LiteSpeed Web Server enterprise edition.', 'litespeed-cache')
		. __(' There are plans to have OpenLiteSpeed support it later down the line.', 'litespeed-cache') . '</p>';

		$buf .= '<h4>' . __('Is WooCommerce supported?', 'litespeed-cache') . '</h4>'
		. '<p>'
		. __('In short, yes. For WooCommerce versions 1.4.2 and above, this plugin will not cache the pages that WooCommerce deems non-cacheable.', 'litespeed-cache')
		. __(' For versions below 1.4.2, we do extra checks to make sure that pages are cacheable.', 'litespeed-cache')
		. __(' We are always looking for feedback, so if you encounter any problems, be sure to send us a support question.', 'litespeed-cache') . '</p>';

		$buf .= '<h4>' . __('How do I get WP-PostViews to display an updating view count?', 'litespeed-cache') . '</h4>'
		. '<ol><li>' . __('Use ', 'litespeed-cache')
		. '<code>&lt;div id="postviews_lscwp"&gt;&lt;/div&gt;</code>'
		. __(' to replace ', 'litespeed-cache')
		. '<code>&lt;?php if(function_exists(\'the_views\')) { the_views(); } ?&gt;</code>';

		$buf .= '<ul><li>'
		. __('NOTE: The id can be changed, but the div id and the ajax function must match.', 'litespeed-cache')
		. '</li></ul>';

		$buf .= '<li>' . __('Replace the ajax query in ', 'litespeed-cache')
		. '<code>wp-content/plugins/wp-postviews/postviews-cache.js</code>'
		. __(' with', 'litespeed-cache')
		. '<textarea id="wpwrap" rows="11" readonly>jQuery.ajax({
    type:"GET",
    url:viewsCacheL10n.admin_ajax_url,
    data:"postviews_id="+viewsCacheL10n.post_id+"&amp;action=postviews",
    cache:!1,
    success:function(data) {
        if(data) {
            jQuery(\'#postviews_lscwp\').html(data+\' views\');
        }
   }
});</textarea>'
		. '</li>';


		$buf .= '<li>'
		. __('Purge the cache to use the updated pages.', 'litespeed-cache')
		. '</li></ul></div></div>';

		echo $buf;
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

		$buf .= '<div class="metabox-holder">';
		$buf .= '<div class="meta-box-sortables ui-sortable">';

		$buf .= $this->build_collapsible($mv_header, $mv_desc, $mv_example);
		$buf .= $this->build_collapsible($cookie_header, $cookie_desc, $cookie_example);
		$buf .= $this->build_collapsible($ua_header, $ua_desc, $ua_example);

		$buf .= '</div></div>';

		return $buf;
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
	private function build_collapsible($header, $desc, $example)
	{
		$buf = '<div class="postbox closed">'
		. '<button type="button" class="handlediv button-link" aria-expanded="false">'
		. '<span class="toggle-indicator" aria-hidden="true"></span></button>'
		. '<h2 class="hndle ui-sortable-handle"><span>' . $header . '</span></h2>';


		$buf .= '<div class="welcome-panel-content"><div class="inside"><p>'
				. $desc . '</p>';

		$buf .= '<textarea id="wpwrap" readonly>' . $example . '</textarea>';
		$buf .= '</div></div></div>';
		return $buf;
	}

	/**
	 * Clean up the input string of any extra slashes/spaces.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $input The input string to clean.
	 * @return string The cleaned up input.
	 */
	private static function cleanup_input($input)
	{
		return stripslashes(trim($input));
	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function parse_settings()
	{
		if ((is_multisite()) && (!is_network_admin())) {
			return;
		}
		if (empty($_POST) || empty($_POST['submit'])) {
			return;
		}
		if ((!$_POST['lscwp_settings_save'])
				|| ($_POST['lscwp_settings_save'] !== 'save_settings')
				|| (!check_admin_referer('lscwp_settings', 'save'))) {
			return;
		}
		$input = $_POST[LiteSpeed_Cache_Config::OPTION_NAME];

		if (!$input) {
			return;
		}
		$input = array_map("self::cleanup_input", $input);
		$config = LiteSpeed_Cache::config() ;
		$options = $config->get_site_options();
		$errors = array();

		$id = LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED;
		$network_enabled = (is_null($input['lscwp_' . $id])
				? false : ($input['lscwp_' . $id] === $id));
		if ($options[$id] !== $network_enabled) {
			$options[$id] = $network_enabled;
			if ($network_enabled) {
				$config->wp_cache_var_setter(true);
			}
		}

		$newopt = $this->validate_common_rewrites($input, $options, $errors);
		if ($newopt) {
			$options = $newopt;
		}

		add_action('network_admin_notices', array($this, 'edit_htaccess_res'));
		if (!empty($errors)) {
			$this->messages = implode('<br>', $errors);
			return;
		}
		$this->messages = true;
		$ret = update_site_option(LiteSpeed_Cache_Config::OPTION_NAME, $options);
		if ($ret) {

		}
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
	private function show_info_settings()
	{

		$buf = '<div class="wrap"><h2>' . __('LiteSpeed Cache Settings', 'litespeed-cache') . '</h2>';

		$network_desc = __('These configurations are only available network wide.', 'litespeed-cache')
		. '<br>'
		. __('Separate Mobile Views should be enabled if any of the network enabled themes require a different view for mobile devices.', 'litespeed-cache')
		. __(' Responsive themes can handle this part automatically.', 'litespeed-cache');

		$config = LiteSpeed_Cache::config();
		$buf .= $this->input_group_start(__('Network-wide Config', 'litespeed-cache')) ;
		$buf .= $network_desc;
		$buf .= '<form method="post" action="admin.php?page=lscache-settings">';
		$buf .= '<input type="hidden" name="lscwp_settings_save" value="save_settings" />';
		$buf .= wp_nonce_field('lscwp_settings', 'save');

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

		$buf .= $this->show_mobile_view($config->get_site_options());

		$ua_title = '';
		$ua_desc = '';
		$ua_buf = $this->show_useragent_exclude($ua_title, $ua_desc);
		$buf .= $this->display_config_row(__('Do Not Cache User Agents', 'litespeed-cache'), $ua_buf, $ua_desc);

		$cookie_title = '';
		$cookie_desc = '';
		$cookie_buf = $this->show_cookies_exclude($cookie_title, $cookie_desc);
		$buf .= $this->display_config_row(__('Do Not Cache Cookies', 'litespeed-cache'), $cookie_buf, $cookie_desc);

		$buf .= '<tr><td>';
		$buf .= '<input type="submit" class="button button-primary" name="submit" value="'
				. __('Save', 'litespeed-cache') . '" /></td></tr>';
		$buf .= '</form>';
		$buf .= $this->input_group_end();
		$buf .= '</div>';
		echo $buf;
	}

	/**
	 * This callback will display admin notices denoting if the .htaccess file
	 * edit succeeded.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function edit_htaccess_res()
	{
		if (!$this->messages) {
			return;
		}
		$buf = '<div class="';
		if ($this->messages === true) {
			$buf .= 'updated';
			$err = __('File saved.', 'litespeed-cache');
		}
		else {
			$buf .= 'error';
			$err = $this->messages;
		}
		$buf .= '"><p>' . $err . '</p></div>';
		echo $buf;
	}

	/**
	 * Gets the currently used .htaccess file path.
	 *
	 * @since 1.0.4
	 * @access private
	 * @return string The .htaccess file path.
	 */
	private static function get_htaccess_path()
	{
		return get_home_path() . '.htaccess';
	}

	/**
	 * Clear the .htaccess file of any changes added by the plugin specifically.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public static function clear_htaccess()
	{
		$prefix = '<IfModule LiteSpeed>';
		$engine = 'RewriteEngine on';
		$suffix = '</IfModule>';
		$path = self::get_htaccess_path();

		clearstatcache();
		if (self::get_htaccess_contents($content) === false) {
			return;
		}
		elseif (!is_writable($path)) {
			return;
		}

		$off_begin = strpos($content, $prefix);
		//if not found
		if ($off_begin === false) {
			return;
		}
		$off_begin += strlen($prefix);
		$off_end = strpos($content, $suffix, $off_begin);
		if ($off_end === false) {
			return;
		}
		--$off_end; // go to end of previous line.
		$output = substr($content, 0, $off_begin);
		$off_engine = strpos($content, $engine, $off_begin);
		$output .= "\n" . $engine . "\n";
		if ($off_engine !== false) {
			$off_begin = $off_engine + strlen($engine);
		}
		$start_search = substr($content, $off_begin, $off_end - $off_begin);

		$ret = self::set_common_rule($start_search, $output,
				'MOBILE VIEW', '', '', '');

		if ((is_array($ret)) && ($ret[0])) {
			$start_search = $ret[1];
		}
		$ret = self::set_common_rule($start_search, $output,
				'COOKIE', '', '', '');

		if ((is_array($ret)) && ($ret[0])) {
			$start_search = $ret[1];
		}
		$ret = self::set_common_rule($start_search, $output,
				'USER AGENT', '', '', '');

		if ((is_array($ret)) && ($ret[0])) {
			$start_search = $ret[1];
		}

		if (!is_null($start_search)) {
			$output .= $start_search . substr($content, $off_end);
		}
		else {
			$output .= $suffix . "\n\n" . $content;
		}
		self::do_edit_htaccess($output);
		return;
	}

	/**
	 * Try to save the .htaccess file changes.
	 *
	 * This function is used by both the edit .htaccess admin page and
	 * the common rewrite rule configuration options.
	 *
	 * This function will create a backup named .htaccess_lscachebak prior
	 * to making any changese. If creating the backup fails, an error is returned.
	 *
	 * If $cleanup is true, this function strip extra slashes.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $content The new content to put into the .htaccess file.
	 * @param boolean $cleanup True to strip exxtra slashes, false otherwise.
	 * @return mixed true on success, else error message on failure.
	 */
	private static function do_edit_htaccess($content, $cleanup = true)
	{
		$path = self::get_htaccess_path();

		clearstatcache();
		if (!is_writable($path) || !is_readable($path)) {
			unnset($path);
			return __('File not readable or not writable.', 'litespeed-cache'); // maybe return error string?
		}
		if (file_exists($path)) {
			//failed to backup, not good.
			if (!copy($path, $path . '_lscachebak')) {
				return __('Failed to back up file, abort changes.', 'litespeed-cache');
			}
		}

		if ($cleanup) {
			$content = self::cleanup_input($content);
		}

		// File put contents will truncate by default. Will create file if doesn't exist.
		$ret = file_put_contents($path, $content, LOCK_EX);
		unset($path);
		if (!$ret) {
			return __('Failed to overwrite ', 'litespeed-cache') . '.htaccess';
		}
		return true;
	}

	/**
	 * Parses the .htaccess buffer when the admin saves changes in the edit .htaccess page.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function parse_edit_htaccess()
	{
		if ((is_multisite()) && (!is_network_admin())) {
			return;
		}
		if (empty($_POST) || empty($_POST['submit'])) {
			return;
		}
		if (($_POST['lscwp_htaccess_save'])
				&& ($_POST['lscwp_htaccess_save'] === 'save_htaccess')
				&& (check_admin_referer('lscwp_edit_htaccess', 'save'))
				&& ($_POST['lscwp_ht_editor'])) {
			$this->messages = self::do_edit_htaccess($_POST['lscwp_ht_editor']);
			if (is_multisite()) {
				add_action('network_admin_notices', array($this, 'edit_htaccess_res'));
			}
			else {
				add_action('admin_notices', array($this, 'edit_htaccess_res'));
			}
		}

	}

	/**
	 * Gets the contents of the .htaccess file.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $content Returns the content of the file or an error description.
	 * @return boolean True if succeeded, false otherwise.
	 */
	private static function get_htaccess_contents(&$content)
	{
		$path = self::get_htaccess_path();
		if (!file_exists($path)) {
			$content = __('.htaccess file does not exist.', 'litespeed-cache');
			return false;
		}
		else if (!is_readable($path)) {
			$content = __('.htaccess file is not readable.', 'litespeed-cache');
			return false;
		}

		$content = file_get_contents($path);
		if ($content == false) {
			$content = __('Failed to get .htaccess file contents.', 'litespeed-cache');
			return false;
		}
		// Remove ^M characters.
		$content = str_ireplace("\x0D", "", $content);
		return true;
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
	private function show_edit_htaccess()
	{
		$buf = '<div class="wrap"><h2>' . __('LiteSpeed Cache Edit .htaccess', 'litespeed-cache') . '</h2>';
		$buf .= '<div class="welcome-panel">';

		$path = self::get_htaccess_path();
		$contents = '';
		if (self::get_htaccess_contents($contents) === false) {
			$buf .= '<h3>' . $contents . '</h3></div>';
			echo $buf;
			return;
		}

		$buf .= '<p><span class="attention">' . __('WARNING: This page is meant for advanced users.', 'litespeed-cache')
		. '</span><br>'
		. __(' Any changes made to the .htaccess file may break your site.', 'litespeed-cache')
		. __(' Please consult your host/server admin before making any changes you are unsure about.', 'litespeed-cache')
		. '</p>';

		$buf .= $this->show_info_common_rewrite();

		$buf .= '<form method="post" action="admin.php?page=lscache-edit-htaccess">';
		$buf .= '<input type="hidden" name="lscwp_htaccess_save" value="save_htaccess" />';
		$buf .= wp_nonce_field('lscwp_edit_htaccess', 'save');

		$buf .= '<h3>' . __('Current .htaccess contents:', 'litespeed-cache') . '</h3>';

		$buf .= '<p><span class="attention">'
		. __('DO NOT EDIT ANYTHING WITHIN ', 'litespeed-cache') . '###LSCACHE START/END XXXXXX###'
		. '</span><br>'
		. __('These are added by the LS Cache plugin and may cause problems if they are changed.', 'litespeed-cache')
		. '</p>';

		$buf .= '<textarea id="wpwrap" name="lscwp_ht_editor" wrap="off" rows="20" class="code" ';
		if (!is_writable($path)) {
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
	 * Build the wrapper string for common rewrite rules.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $wrapper The common rule wrapper.
	 * @param string $end Returns the ending wrapper.
	 * @return string Returns the opening wrapper.
	 */
	private static function build_wrappers($wrapper, &$end)
	{
		$end = '###LSCACHE END ' . $wrapper . '###';
		return '###LSCACHE START ' . $wrapper . '###';
	}

	/**
	 * Updates the specified common rewrite rule based on original content.
	 *
	 * If the specified rule is not found, just return the rule.
	 * Else if it IS found, need to keep the content surrounding the rule.
	 *
	 * The return value is mixed.
	 * Returns true if the rule is not found in the content.
	 * Returns an array (false, error_msg) on error.
	 * Returns an array (true, new_content) if the rule is found.
	 *
	 * new_content is the original content minus the matched rule. This is
	 * to prevent losing any of the original content.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $content The original content in the .htaccess file.
	 * @param string $output Returns the added rule if success.
	 * @param string $wrapper The wrapper that surrounds the rule.
	 * @param string $cond The rewrite condition to use with the rule.
	 * @param string $match The rewrite rule to match against the condition.
	 * @param string $env The environment change to do if the rule matches.
	 * @param string $flag The flags to use with the rewrite condition.
	 * @return mixed Explained above.
	 */
	private static function set_common_rule($content, &$output, $wrapper, $cond,
			$match, $env, $flag = '')
	{

		$wrapper_end = '';
		$wrapper_begin = self::build_wrappers($wrapper, $wrapper_end);
		$rw_cond = 'RewriteCond %{' . $cond . '} ' . $match;
		if ($flag != '') {
			$rw_cond .= ' [' . $flag . ']';
		}
		$out = $wrapper_begin . "\n" . $rw_cond .  "\n"
			. 'RewriteRule .* - [' . $env . ']' . "\n" . $wrapper_end . "\n";

		// just create the whole buffer.
		if (is_null($content)) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_begin = strpos($content, $wrapper_begin);
		if ($wrap_begin === false) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_end = strpos($content, $wrapper_end, $wrap_begin + strlen($wrapper_begin));
		if ($wrap_end === false) {
			return array(false, __('Could not find wrapper end', 'litespeed-cache'));
		}
		elseif ($match != '') {
			$output .= $out;
		}
		$buf = substr($content, 0, $wrap_begin); // Remove everything between wrap_begin and wrap_end
		$buf .= substr($content, $wrap_end + strlen($wrapper_end));
		return array(true, trim($buf));
	}

	/**
	 * FInds a specified common rewrite rule from the .htaccess file.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $wrapper The wrapper to look for.
	 * @param string $cond The condition to look for.
	 * @param string $match Returns the rewrite rule on success, error message on failure.
	 * @return boolean True on success, false otherwise.
	 */
	private function get_common_rule($wrapper, $cond, &$match)
	{

		if (self::get_htaccess_contents($match) === false) {
			return false;
		}
		$suffix = '';
		$prefix = self::build_wrappers($wrapper, $suffix);
		$off_begin = strpos($match, $prefix);
		if ($off_begin === false) {
			$match = '';
			return true; // It does not exist yet, not an error.
		}
		$off_begin += strlen($prefix);
		$off_end = strpos($match, $suffix, $off_begin);
		if ($off_end === false) {
			$match = __('Could not find suffix ', 'litespeed-cache') . $suffix;
			return false;
		}
		elseif ($off_begin >= $off_end) {
			$match = __('Prefix was found after suffix.', 'litespeed-cache');
			return false;
		}

		$subject = substr($match, $off_begin, $off_end - $off_begin);
		$pattern = '/RewriteCond\s%{' . $cond . '}\s+([^[\n]*)\s+[[]*/';
		$matches = array();
		$num_matches = preg_match($pattern, $subject, $matches);
		if ($num_matches === false) {
			$match = __('Did not find a match.', 'litespeed-cache');
			return false;
		}
		$match = trim($matches[1]);
		return true;
	}

	/**
	 * Generates the HTMl to start a configuration options table.
	 *
	 * @since 1.0.0
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
	private function input_field_checkbox( $id, $value, $checked_value, $label = '',
											$on_click = '', $disabled = false)
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
	private function input_field_text( $id, $value, $size = '', $style = '', $after = '', $readonly = false )
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
	private function input_field_textarea( $id, $value, $rows = '', $cols = '', $style = '', $readonly = false)
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

}
