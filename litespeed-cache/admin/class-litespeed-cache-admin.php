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
		$plugin_file = plugin_dir_path(dirname(__FILE__)) . $plugin_name . '.php';
if (defined('lscache_debug')) {
			require_once(ABSPATH . '/wp-admin/includes/file.php');
	$plugin_file = ABSPATH . '/wp-content/plugins/litespeed-cache/' . $plugin_name . '.php';
}

		$plugin_base = plugin_basename($plugin_file);

		if (!function_exists('is_plugin_active_for_network')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}

		add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' )) ;

		//Additional links on the plugin page
		if ((is_network_admin()) && (is_plugin_active_for_network($plugin_base))) {
			add_action('network_admin_menu', array( $this, 'register_admin_menu' )) ;
		}
		else {
			add_action('admin_menu', array( $this, 'register_admin_menu' )) ;
		}

		add_action('admin_init', array( $this, 'admin_init' )) ;
		add_filter('plugin_action_links_' . $plugin_base,
			array( $this, 'add_plugin_links' )) ;
	}

	/**
	 * Register the stylesheets and JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_style($this->plugin_name,
				plugin_dir_url(__FILE__) . 'css/litespeed-cache-admin.css',
				array(), $this->version, 'all') ;
		wp_enqueue_script('jquery-ui-tabs') ;
		wp_enqueue_script($this->plugin_name,
				plugin_dir_url(__FILE__) . 'js/litespeed-cache-admin.js',
				array( 'jquery' ), $this->version, false) ;
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
			$display = LiteSpeed_Cache_Admin_Display::get_instance();

			$this->register_dash_menu();

			$lscache_admin_settings_page = add_options_page('LiteSpeed Cache',
					'LiteSpeed Cache', $capability, 'litespeedcache',
					array( $display, 'show_menu_settings' )) ;
			// adds help tab
			add_action('load-' . $lscache_admin_settings_page,
					array( $display, 'add_help_tabs' )) ;
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
		$display = LiteSpeed_Cache_Admin_Display::get_instance();
		if (!empty($cb)) {
			$fn = array($display, $cb);
		}
		$submenu_page = add_submenu_page('lscache-dash', $page_title,
				$menu_title, 'manage_options', $menu_slug, $fn);
		add_action('load-' . $submenu_page, array( $display, 'add_help_tabs' ));
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
				__('Settings', 'litespeed-cache'), 'lscache-settings', 'show_menu_select');

		if ((!is_multisite()) || (is_network_admin())) {
			$this::add_submenu(sprintf(__('%s Edit .htaccess', 'litespeed-cache'),'LiteSpeed Cache'),
					sprintf(__('Edit %s', 'litespeed-cache'), '.htaccess'), 'lscache-edit-htaccess', 'show_menu_select');
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
				__('Information', 'litespeed-cache'), 'lscache-info', 'show_menu_select');
		$this::add_submenu(sprintf(__('%s FAQs', 'litespeed-cache'),'LiteSpeed Cache'),
				__('FAQs', 'litespeed-cache'), 'lscache-faqs', 'show_menu_select');

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
			LiteSpeed_Cache_Config::wp_cache_var_setter($enabled);
			if (!$enabled) {
				LiteSpeed_Cache::plugin()->purge_all() ;
			}
			elseif ($options[LiteSpeed_Cache_Config::OPID_CACHE_FAVICON]) {
				$options[LiteSpeed_Cache_Config::OPID_CACHE_FAVICON] = false;
			}
			$input[$id] = 'changed';
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
		if (isset($input['lscwp_' . $id])) {
			$options[$id] = ( $input['lscwp_' . $id] === $id );
		}
		else {
			$options[$id] = false;
		}

		$id = LiteSpeed_Cache_Config::OPID_CACHE_LOGIN;
		if (isset($input['lscwp_' . $id])) {
			$login = ( $input['lscwp_' . $id] === $id );
			if ($options[$id] != $login) {
				$options[$id] = $login;
				if (!$login) {
					LiteSpeed_Cache_Tags::add_purge_tag(
						LiteSpeed_Cache_Tags::TYPE_LOGIN);
				}
			}
		}
		else {
			$options[$id] = false;
		}

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

		if (!is_multisite()) {
			$newopt = LiteSpeed_Cache_Admin_Rules::get_instance()
				->validate_common_rewrites($input, $options, $errors);
			if ($newopt) {
				$options = $newopt;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_URI ;
		if ( isset($input[$id]) ) {
			$uri_arr = array_map('trim', explode("\n", $input[$id]));
			$options[$id] = implode("\n", array_filter($uri_arr));
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
		$debug_level = isset($input[$id]) ? intval($input[$id])
				: LiteSpeed_Cache_Config::LOG_LEVEL_NONE ;
		if (($debug_level != $options[$id])
				&& ($debug_level >= LiteSpeed_Cache_Config::LOG_LEVEL_NONE)
				&& ($debug_level <= LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG)) {
			$options[$id] = $debug_level ;
		}

		if ( ! empty($errors) ) {
			add_settings_error(LiteSpeed_Cache_Config::OPTION_NAME,
					LiteSpeed_Cache_Config::OPTION_NAME, implode('<br>', $errors)) ;
		}

		return $options ;
	}

	/**
	 * Hooked to the wp_redirect filter.
	 * This will only hook if there was a problem when saving the widget.
	 *
	 * @param string $location The location string.
	 * @return string the updated location string.
	 */
	public function widget_save_err($location)
	{
		return str_replace('?message=0', '?error=0', $location);
	}

	/**
	 * Hooked to the widget_update_callback filter.
	 * Validate the LiteSpeed Cache settings on edit widget save.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param array $instance The new settings.
	 * @param array $new_instance
	 * @param array $old_instance The original settings.
	 * @param WP_Widget $widget The widget
	 * @return mixed Updated settings on success, false on error.
	 */
	public function validate_widget_save($instance, $new_instance,
		$old_instance, $widget)
	{
		$current = $old_instance[LiteSpeed_Cache_Config::OPTION_NAME];
		$input = $_POST[LiteSpeed_Cache_Config::OPTION_NAME];
		if (empty($input)) {
			return $instance;
		}
		$esistr = $input[LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE];
		$ttlstr = $input[LiteSpeed_Cache_Config::WIDGET_OPID_TTL];

		if ((!is_numeric($ttlstr)) || (!is_numeric($esistr))) {
			add_filter('wp_redirect', array($this, 'widget_save_err'));
			return false;
		}

		$esi = intval($esistr);
		$ttl = intval($ttlstr);

		if (($ttl != 0) && ($ttl < 30)) {
			add_filter('wp_redirect', array($this, 'widget_save_err'));
			return false; // invalid ttl.
		}

		if (is_null($instance[LiteSpeed_Cache_Config::OPTION_NAME])) {
			$instance[LiteSpeed_Cache_Config::OPTION_NAME] = array(
				LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE => $esi,
				LiteSpeed_Cache_Config::WIDGET_OPID_TTL => $ttl
			);
		}
		else {
			$instance[LiteSpeed_Cache_Config::OPTION_NAME]
				[LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE] = $esi;
			$instance[LiteSpeed_Cache_Config::OPTION_NAME]
				[LiteSpeed_Cache_Config::WIDGET_OPID_TTL] = $ttl;
		}

		if ((!isset($current))
			|| ($esi
				!= $current[LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE])) {
			LiteSpeed_Cache_Tags::add_purge_tag('*');
		}
		elseif (($ttl != 0)
			&& ($ttl != $current[LiteSpeed_Cache_Config::WIDGET_OPID_TTL])) {
			LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_WIDGET . $widget->id);
		}

		return $instance;
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
	 * Check if the admin pressed a button in the management page.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function check_cache_mangement_actions()
	{
		if ((empty($_POST))
				|| (!isset($_POST['lscwp_management']))
				|| ($_POST['lscwp_management'] !== 'manage_lscwp')
				|| (!check_admin_referer('lscwp_manage', 'management_run'))) {
			return;
		}
		if ( isset($_POST['purgeall']) ) {
			LiteSpeed_Cache::plugin()->purge_all() ;
			$msg = __('Notified LiteSpeed Web Server to purge the public cache.', 'litespeed-cache');
		}
		elseif ( isset($_POST['purgefront'])){
			LiteSpeed_Cache::plugin()->purge_front();
			$msg = __('Notified LiteSpeed Web Server to purge the front page.', 'litespeed-cache');
		}
		elseif ( isset($_POST['purgelist'])) {
			LiteSpeed_Cache::plugin()->purge_list();
			return;
		}
		else {
			return;
		}
		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
							LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg);
	}

	/**
	 * Clean up the input string of any extra slashes/spaces.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param string $input The input string to clean.
	 * @return string The cleaned up input.
	 */
	public static function cleanup_text($input)
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
		if ((!isset($_POST['lscwp_settings_save']))
				|| (empty($_POST[LiteSpeed_Cache_Config::OPTION_NAME]))
				|| ($_POST['lscwp_settings_save'] !== 'save_settings')
				|| (!check_admin_referer('lscwp_settings', 'save'))) {
			return;
		}

		$input = array_map("LiteSpeed_Cache_Admin::cleanup_text",
			$_POST[LiteSpeed_Cache_Config::OPTION_NAME]);
		$config = LiteSpeed_Cache::config() ;
		$options = $config->get_site_options();
		$errors = array();

		$id = LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED;
		$network_enabled = (is_null($input['lscwp_' . $id])
				? false : ($input['lscwp_' . $id] === $id));
		if ($options[$id] !== $network_enabled) {
			$options[$id] = $network_enabled;
			if ($network_enabled) {
				LiteSpeed_Cache_Config::wp_cache_var_setter(true);
			}
			else {
				LiteSpeed_Cache::plugin()->purge_all();
			}
			$input[$id] = 'changed';
		}

		$rules = LiteSpeed_Cache_Admin_Rules::get_instance();
		$newopt = $rules->validate_common_rewrites($input, $options, $errors);
		if ($newopt) {
			$options = $newopt;
		}

		if (!empty($errors)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
							LiteSpeed_Cache_Admin_Display::NOTICE_RED, $errors);
			return;
		}
		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
				__('File saved.', 'litespeed-cache'));
		$ret = update_site_option(LiteSpeed_Cache_Config::OPTION_NAME, $options);
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
		return $translations .
			__(' Purging LiteSpeed Cache is recommended after updating a plugin.',
				'litespeed-cache');
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

}
