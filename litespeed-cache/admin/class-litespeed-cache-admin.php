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
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$plugin_file = plugin_dir_path(dirname(__FILE__)) . $plugin_name . '.php';
		if (defined('lscache_debug')) {
			require_once(ABSPATH . '/wp-admin/includes/file.php');
			$plugin_file = ABSPATH . '/wp-content/plugins/litespeed-cache/' . $plugin_name . '.php';
		}

		$plugin_base = plugin_basename($plugin_file);

		if (!function_exists('is_plugin_active_for_network')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}

		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('admin_print_styles-settings_page_litespeedcache',
			array($this, 'enqueue_style'));


		//Additional links on the plugin page
		if ((is_network_admin()) && (is_plugin_active_for_network($plugin_base))) {
			add_action('network_admin_menu', array($this, 'register_admin_menu'));
		}
		else {
			add_action('admin_menu', array($this, 'register_admin_menu'));
		}

		add_action('admin_init', array($this, 'admin_init'));
		add_filter('plugin_action_links_' . $plugin_base,
			array($this, 'add_plugin_links'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.14
	 */
	public function enqueue_style()
	{
		wp_enqueue_style($this->plugin_name,
			plugin_dir_url(__FILE__) . 'css/litespeed-cache-admin.css',
			array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_register_script($this->plugin_name,
			plugin_dir_url(__FILE__) . 'js/litespeed-cache-admin.js',
			array('jquery-ui-tabs'), $this->version, false);
		if (get_transient(LiteSpeed_Cache::WHM_TRANSIENT)
			=== LiteSpeed_Cache::WHM_TRANSIENT_VAL
		) {
			$data = array(
				'lscwpctrl' => LiteSpeed_Cache::ADMINQS_DISMISS,
				'nonce' => wp_create_nonce('litespeed-dismiss')
			);
			wp_localize_script($this->plugin_name, 'lscwp_data', $data);

		}
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script($this->plugin_name);
	}

	/**
	 * Register the admin menu display.
	 *
	 * @since    1.0.0
	 */
	public function register_admin_menu()
	{
		$capability = is_network_admin() ? 'manage_network_options' : 'manage_options';
		if (current_user_can($capability)) {
			$display = LiteSpeed_Cache_Admin_Display::get_instance();

			$this->register_dash_menu();

			$lscache_admin_settings_page = add_options_page('LiteSpeed Cache',
				'LiteSpeed Cache', $capability, 'litespeedcache',
				array($display, 'show_menu_settings'));
			// adds help tab
			add_action('load-' . $lscache_admin_settings_page,
				array($display, 'add_help_tabs'));
		}
	}

	/**
	 * Builds an admin url with an action and a nonce.
	 *
	 * Assumes user capabilities are already checked.
	 *
	 * @param string $val The LSCWP_CTRL action to do in the url.
	 * @param string $nonce The nonce to use.
	 * @return string The built url.
	 */
	public static function build_lscwpctrl_url($val, $nonce)
	{
		global $pagenow;
		$prefix = '?';

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

		$combined = $pagenow . $prefix . LiteSpeed_Cache::ADMINQS_KEY
			. '=' . $val;

		if (is_network_admin()) {
			$prenonce = network_admin_url($combined);
		}
		else {
			$prenonce = admin_url($combined);
		}
		$url = wp_nonce_url($prenonce, $nonce);

		return $url;
	}

	/**
	 * Hooked to wp_before_admin_bar_render.
	 * Adds a link to the admin bar so users can quickly purge all.
	 *
	 * @global WP_Admin_Bar $wp_admin_bar
	 * @global string $pagenow
	 */
	public function add_quick_purge()
	{
		global $wp_admin_bar;
		$url = self::build_lscwpctrl_url(LiteSpeed_Cache::ADMINQS_PURGEALL,
			'litespeed-purgeall');

		$wp_admin_bar->add_node(array(
			'id'    => 'lscache-quick-purge',
			'title' => __('LiteSpeed Cache Purge All', 'litespeed-cache'),
			'href'  => $url,
		));
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
		$fn = '';
		$display = LiteSpeed_Cache_Admin_Display::get_instance();
		if (!empty($cb)) {
			$fn = array($display, $cb);
		}
		$submenu_page = add_submenu_page('lscache-dash', $page_title,
			$menu_title, 'manage_options', $menu_slug, $fn);
		add_action('load-' . $submenu_page, array($display, 'add_help_tabs'));
		add_action("admin_print_styles-{$submenu_page}", array($this, 'enqueue_style'));
	}

	/**
	 * Registers management submenu pages.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function register_submenu_manage()
	{
		$this::add_submenu(sprintf(__('%s Manager', 'litespeed-cache'), 'LiteSpeed Cache'),
			__('Manage', 'litespeed-cache'), 'lscache-dash', 'show_menu_select');
	}

	/**
	 * Registers settings submenu pages.
	 *
	 * @since 1.0.4
	 * @access private
	 */
	private function register_submenu_settings()
	{
		$this::add_submenu(sprintf(__('%s Settings', 'litespeed-cache'), 'LiteSpeed Cache'),
			__('Settings', 'litespeed-cache'), 'lscache-settings', 'show_menu_select');

		if ((!is_multisite()) || (is_network_admin())) {
			$this::add_submenu(sprintf(__('%s Edit .htaccess', 'litespeed-cache'), 'LiteSpeed Cache'),
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
		$this::add_submenu(sprintf(__('%s Information', 'litespeed-cache'), 'LiteSpeed Cache'),
			__('Information', 'litespeed-cache'), 'lscache-info', 'show_menu_select');
		if ((!is_multisite()) ||
			((is_network_admin()) && (current_user_can('manage_network_options')))
		) {
			$this::add_submenu(sprintf(__('%s Environment Report', 'litespeed-cache'), 'LiteSpeed Cache'),
				__('Environment Report', 'litespeed-cache'), 'lscache-report', 'show_menu_select');
		}

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
		$svg =
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vV' .
			'zNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvb' .
			'j0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vc' .
			'mcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSIzNjBweCIgaGVpZ2h0PSIzNjBweCIgdmlld0JveD0iMCAwIDM2MCAzNjAiIGVuY' .
			'WJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDM2MCAzNjAiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIGZpbGw9IiMyNzI1MjUiIGQ9Ik0zNTguM' .
			'zA1LDE3OC45MTNjLTAuMDMzLTAuMTY1LTAuMDk1LTAuMzIyLTAuMTQyLTAuNDgzYy0wLjA1NS0wLjE4Ny0wLjEtMC4zNzYtMC4xNzQtMC41NTJjL' .
			'TAuMDc4LTAuMTkzLTAuMTg3LTAuMzczLTAuMjg5LTAuNTUyYy0wLjA3Mi0wLjEzNS0wLjEzNC0wLjI3Ny0wLjIxOS0wLjQwNWMtMC4yMDEtMC4zL' .
			'TAuNDMxLTAuNTg1LTAuNjk1LTAuODUybC0wLjAwNS0wLjAwNEwxODMuOTMyLDMuMjE2bC0wLjAwNS0wLjAwN2MtMC4wMTQtMC4wMTEtMC4wMjktM' .
			'C4wMjItMC4wNDMtMC4wMzNjLTEuMDAxLTAuOTgzLTIuMzctMS41OTMtMy44ODUtMS41OTNjLTEuNTE3LDAtMi44ODUsMC42MS0zLjg4NiwxLjU5M' .
			'2MtMC4wMTUsMC4wMTEtMC4wMzEsMC4wMjItMC4wNDQsMC4wMzNsLTAuMDA0LDAuMDA3TDMuMjE0LDE3Ni4wNjVsLTAuMDAzLDAuMDA0Yy0wLjI2N' .
			'SwwLjI2Ny0wLjQ5NSwwLjU1Mi0wLjY5NiwwLjg1MmMtMC4wODUsMC4xMjctMC4xNDQsMC4yNy0wLjIxOSwwLjQwNWMtMC4xLDAuMTc5LTAuMjEyL' .
			'DAuMzU4LTAuMjg5LDAuNTUyYy0wLjA3NSwwLjE3Ni0wLjExNywwLjM2NS0wLjE3MSwwLjU1MmMtMC4wNDksMC4xNjEtMC4xMTEsMC4zMTgtMC4xN' .
			'DMsMC40ODNjLTAuMDcyLDAuMzU0LTAuMTEsMC43MTYtMC4xMSwxLjA4OGMwLDAuMzczLDAuMDM4LDAuNzM0LDAuMTEsMS4wODZjMC4wMzEsMC4xN' .
			'jQsMC4wOTMsMC4zMiwwLjE0MywwLjQ4MWMwLjA1NSwwLjE4NywwLjA5NywwLjM4MiwwLjE3MSwwLjU1OGMwLjA3NywwLjE4OSwwLjE4OCwwLjM2O' .
			'SwwLjI4OSwwLjU1MmMwLjA3NSwwLjEzNSwwLjEzNCwwLjI3MywwLjIxOSwwLjQwNWMwLjIwMSwwLjI5NiwwLjQzMSwwLjU4NSwwLjY5NiwwLjg0O' .
			'GwwLjAwMywwLjAwNGwxNzIuODUsMTcyLjg1bDAuMDA1LDAuMDA3YzAuMjU5LDAuMjYxLDAuNTQxLDAuNDg2LDAuODQyLDAuNjg4YzAuMDU5LDAuM' .
			'DQsMC4xMjYsMC4wNjYsMC4xODgsMC4xMDVjMC4yNDcsMC4xNDYsMC40OTcsMC4yOTcsMC43NjUsMC40MDZjMC4wOTksMC4wNDQsMC4yMSwwLjA2M' .
			'iwwLjMxNCwwLjA5OWMwLjIzOCwwLjA4NCwwLjQ3MywwLjE2NywwLjcyMiwwLjIyYzAuMzU0LDAuMDY4LDAuNzIyLDAuMTA4LDEuMDk5LDAuMTA4Y' .
			'zAuMzc0LDAsMC43NDMtMC4wNCwxLjA5OC0wLjEwOGMwLjI0Ny0wLjA1MywwLjQ4My0wLjEzNiwwLjcyLTAuMjJjMC4xMDQtMC4wMzcsMC4yMTYtM' .
			'C4wNTUsMC4zMTUtMC4wOTljMC4yNjctMC4xMDksMC41MTgtMC4yNjEsMC43NjctMC40MDZjMC4wNi0wLjAzOSwwLjEyOS0wLjA2NSwwLjE4Ny0wL' .
			'jEwNWMwLjMwMS0wLjIwMSwwLjU4My0wLjQyNywwLjg0Mi0wLjY4OGwwLjAwNS0wLjAwN2wxNzIuODUtMTcyLjg1bDAuMDA1LTAuMDA0YzAuMjY1L' .
			'TAuMjYzLDAuNDk0LTAuNTUyLDAuNjk1LTAuODQ4YzAuMDg1LTAuMTMyLDAuMTQ2LTAuMjcxLDAuMjE5LTAuNDA1YzAuMTAzLTAuMTgzLDAuMjExL' .
			'TAuMzYyLDAuMjg5LTAuNTUyYzAuMDc0LTAuMTc2LDAuMTE5LTAuMzcxLDAuMTc0LTAuNTU4YzAuMDQ3LTAuMTYxLDAuMTA4LTAuMzE3LDAuMTQyL' .
			'TAuNDgxYzAuMDY5LTAuMzUyLDAuMTExLTAuNzEzLDAuMTExLTEuMDg2QzM1OC40MTcsMTc5LjYyOCwzNTguMzc1LDE3OS4yNjYsMzU4LjMwNSwxN' .
			'zguOTEzeiBNMzMwLjg1NCwxODAuMzEzbC0wLjA1OSwwLjE1NmwtMC4wNDgsMC4xN2wtMC4xNzMsMC4zMDljLTAuMDQzLDAuMDU5LTAuMDk3LDAuM' .
			'TIyLTAuMTU2LDAuMTgxTDE4MS4xMzYsMzMwLjQxM2MtMC4wNTMsMC4wNTYtMC4xMDUsMC4xMDItMC4xNjIsMC4xNDJjLTAuMDUsMC4wMzMtMC4yN' .
			'DUsMC4xNDktMC4yOTksMC4xNzhsLTAuNDEsMC4xMzVjLTAuMDQ3LDAuMDExLTAuMTU2LDAuMDIxLTAuMjY3LDAuMDIxYy0wLjExMSwwLTAuMjE4L' .
			'TAuMDExLTAuMzIzLTAuMDM0YzAsMC0wLjI4NS0wLjEwMi0wLjM0OS0wLjEyMmMtMC4wNjYtMC4wMzUtMC4zMDktMC4xOC0wLjMwOS0wLjE4Yy0wL' .
			'jA1Ny0wLjA0Ni0wLjEwNC0wLjA4Ny0wLjE1LTAuMTM0TDI5LjU4NCwxODEuMTM2Yy0wLjA1OS0wLjA2LTAuMTEtMC4xMjEtMC4xNTYtMC4xODVsL' .
			'TAuMTgzLTAuMzM2bC0wLjEyLTAuMzkyYy0wLjAwMi0wLjAxNC0wLjAxNi0wLjExNS0wLjAxNi0wLjIyM2MwLTAuMTA3LDAuMDE0LTAuMjExLDAuM' .
			'DM1LTAuMzEybDAuMDYtMC4xNTZsMC4wNTMtMC4xODFsMC4wNzktMC4xMjVsMC4wOTMtMC4xODFjMC4wNDctMC4wNjMsMC4wOTYtMC4xMjUsMC4xN' .
			'DktMC4xNzhMMTc4LjkwMSwyOS41NTNjMC4xNTgtMC4xNTMsMC41MzYtMC40NDMsMS4wOTktMC40NDNzMC45NDIsMC4yOSwxLjExOCwwLjQ2NGwxN' .
			'DkuMjk3LDE0OS4yODljMC4wNTksMC4wNTksMC4xMDksMC4xMjEsMC4xNTMsMC4xODRsMC4xOCwwLjMyOWwwLjEyNCwwLjM5NmMwLjAwMiwwLjAxO' .
			'CwwLjAxNywwLjEyMiwwLjAxNywwLjIyOUMzMzAuODg4LDE4MC4xMDgsMzMwLjg3NCwxODAuMjA5LDMzMC44NTQsMTgwLjMxM3ogTTI2OC43ODcsM' .
			'Tc5LjQ2MmMtMC4wMjEtMC4wNzktMC4wNTUtMC4xNi0wLjA3NC0wLjI0Yy0wLjAyNS0wLjA5My0wLjA0Ny0wLjE4OC0wLjA4Ni0wLjI3NWMtMC4wM' .
			'zktMC4wOTQtMC4wOTItMC4xODctMC4xNDUtMC4yNzJjLTAuMDM3LTAuMDY5LTAuMDY2LTAuMTQyLTAuMTA5LTAuMjA0Yy0wLjA5OC0wLjE0OC0wL' .
			'jIxOS0wLjI5LTAuMzQyLTAuNDIxbC0wLjAwNi0wLjAwN0wxODEuOTYsOTEuOTc5bC0wLjAwNi0wLjAwNGMtMC4wMDYtMC4wMDQtMC4wMTYtMC4wM' .
			'TMtMC4wMi0wLjAxOWMtMC40OTgtMC40ODgtMS4xNzgtMC43OTMtMS45MzUtMC43OTNjLTAuNzUyLDAtMS40MzcsMC4zMDUtMS45MzUsMC43OTNjL' .
			'TAuMDA3LDAuMDA2LTAuMDE1LDAuMDE1LTAuMDIsMC4wMTlsLTAuMDA0LDAuMDA0bC04Ni4wNjYsODYuMDYzbC0wLjAwMSwwLjAwN2MtMC4xMzIsM' .
			'C4xMzEtMC4yNDgsMC4yNzItMC4zNDcsMC40MjFjLTAuMDQ1LDAuMDYzLTAuMDcxLDAuMTM1LTAuMTA4LDAuMjA0Yy0wLjA1MywwLjA4Ni0wLjEwN' .
			'iwwLjE3OS0wLjE0MywwLjI3MmMtMC4wMzksMC4wODgtMC4wNTksMC4xODMtMC4wODcsMC4yNzVjLTAuMDI2LDAuMDgtMC4wNTgsMC4xNjEtMC4wN' .
			'zMsMC4yNGMtMC4wMzYsMC4xNzctMC4wNTQsMC4zNTgtMC4wNTQsMC41NDZjMCwwLjE4MywwLjAxOCwwLjM2MSwwLjA1NCwwLjUzNmMwLjAxNiwwL' .
			'jA4MiwwLjA0NywwLjE2LDAuMDczLDAuMjQ1YzAuMDI4LDAuMDg5LDAuMDQ4LDAuMTg0LDAuMDg3LDAuMjcxYzAuMDM2LDAuMDk0LDAuMDksMC4xO' .
			'DYsMC4xNDMsMC4yNzdjMC4wMzcsMC4wNjQsMC4wNjMsMC4xMzcsMC4xMDgsMC4xOTljMC4wOTksMC4xNDgsMC4yMTUsMC4yODksMC4zNDcsMC40M' .
			'jJsMC4wMDEsMC4wMDJsODYuMDY1LDg2LjA3bDAuMDA1LDAuMDAyYzAuMTMsMC4xMjksMC4yNzEsMC4yNDIsMC40MTgsMC4zNGMwLjAyOSwwLjAyM' .
			'ywwLjA2NSwwLjAzMywwLjA5NSwwLjA1MWMwLjEyMiwwLjA3NiwwLjI0OSwwLjE1NCwwLjM4MSwwLjIwN2MwLjA0OCwwLjAyLDAuMTA1LDAuMDI1L' .
			'DAuMTU1LDAuMDQzYzAuMTE1LDAuMDQ3LDAuMjMzLDAuMDg2LDAuMzYsMC4xMTNjMC4xNzcsMC4wMzUsMC4zNTgsMC4wNTEsMC41NDUsMC4wNTFjM' .
			'C4xODgsMCwwLjM3Mi0wLjAxNiwwLjU0OS0wLjA1MWMwLjEyMi0wLjAyNywwLjI0LTAuMDY2LDAuMzU3LTAuMTEzYzAuMDUyLTAuMDE4LDAuMTA3L' .
			'TAuMDIzLDAuMTYtMC4wNDNjMC4xMzEtMC4wNTMsMC4yNTYtMC4xMzEsMC4zNzktMC4yMDdjMC4wMjktMC4wMTgsMC4wNjYtMC4wMjcsMC4wOTQtM' .
			'C4wNTFjMC4xNS0wLjA5OCwwLjI5MS0wLjIxMSwwLjQxNi0wLjM0bDAuMDA2LTAuMDAybDg2LjA2NC04Ni4wN2wwLjAwNi0wLjAwMmMwLjEyMy0wL' .
			'jEzMywwLjI0NC0wLjI3MywwLjM0Mi0wLjQyMmMwLjA0My0wLjA2MywwLjA3Mi0wLjEzNSwwLjEwOS0wLjE5OWMwLjA1My0wLjA5MiwwLjEwNS0wL' .
			'jE4NCwwLjE0NS0wLjI3N2MwLjAzOS0wLjA4NywwLjA2MS0wLjE4MiwwLjA4Ni0wLjI3MWMwLjAyLTAuMDg1LDAuMDUzLTAuMTYzLDAuMDc0LTAuM' .
			'jQ1YzAuMDMxLTAuMTc1LDAuMDUxLTAuMzU0LDAuMDUxLTAuNTM2QzI2OC44MzcsMTc5LjgyLDI2OC44MTgsMTc5LjYzOSwyNjguNzg3LDE3OS40N' .
			'jJ6IE0yMTIuNjExLDEzMy4wMmwtMjcuNTQ1LDM0Ljk1MmwxNC45MTIsMjEuNDQ2YzAuMDE4LDAuMDI1LDAuMDM1LDAuMDUzLDAuMDU1LDAuMDhjM' .
			'C4yNSwwLjM4OSwwLjQzMiwwLjc4MSwwLjU2MSwxLjI1NmMwLjg4MSwzLjQzOSwwLjI2Miw2LjE3LTEuNzI1LDcuNjc2bC00NS4yMzksMzQuNzIzY' .
			'y0wLjc3OSwwLjYxNS0xLjg2MywxLjMyNC0zLjMxNSwxLjMyNGMtMS42MTMsMC0zLjEwNC0wLjkzMi0zLjc4Ni0yLjM0OGMtMC44MTktMS42NTItM' .
			'C41MTktMy40LDAuODkxLTUuMTkzbDI3LjUzNy0zNC45NTlsLTE1LjEzLTIxLjc2OWMtMS45MzQtMi43NDYtMS4zMzMtNi42NDUsMS4zMzMtOC42O' .
			'DRsNDUuMjY4LTM0Ljc1MWMwLjc4MS0wLjYwNCwxLjg1OS0xLjI5NiwzLjI4MS0xLjI5NmMxLjYwOSwwLDMuMTAyLDAuOTMsMy44MDUsMi4zNzRDM' .
			'jE0LjMwOCwxMjkuNDg3LDIxNC4wMDcsMTMxLjIyMSwyMTIuNjExLDEzMy4wMnogTTgyLjAzNywxOTAuMDJsODcuODIyLDg3LjgyNnYyMC40NEw2M' .
			'S41OSwxOTAuMDJIODIuMDM3eiBNMTY5Ljg1OSw4Mi4xNjZsLTg3LjgxOCw4Ny44MTVINjEuNTlMMTY5Ljg1OSw2MS43MTNWODIuMTY2eiBNMjc3L' .
			'jk3NiwxOTAuMDA3aDE5LjEzNUwxODguODM0LDI5OC4yODd2LTE5LjEzMUwyNzcuOTc2LDE5MC4wMDd6IE0yNzcuOTY5LDE2OS45OWwtODkuMTM1L' .
			'Tg5LjEzNFY2MS43MTNMMjk4LjQxLDE2OS45OUgyNzcuOTY5eiIvPjwvc3ZnPg==';
		add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', 'manage_options',
			'lscache-dash', '', $svg);
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
		$config = LiteSpeed_Cache::config();
		// check for upgrade
		$config->plugin_upgrade();
		if ((is_network_admin()) && (current_user_can('manage_network_options'))) {
			$config->plugin_site_upgrade();
		}

		// check management action
		if ($config->is_plugin_enabled()) {
			$this->check_cache_mangement_actions();
			if ((defined('WP_CACHE')) && (constant('WP_CACHE') === true)) {
				$this->check_advanced_cache();
			}
		}

		$option_name = LiteSpeed_Cache_Config::OPTION_NAME;
		if (!is_network_admin()) {
			register_setting($option_name, $option_name,
				array($this, 'validate_plugin_settings'));
		}

		if (!is_multisite()) {
			if (!current_user_can('manage_options')) {
				return;
			}
		}
		elseif (!is_network_admin()) {
			if (!current_user_can('manage_options')) {
				return;
			}
			if ((get_current_blog_id() !== BLOG_ID_CURRENT_SITE)) {
				$use_primary = LiteSpeed_Cache_Config::NETWORK_OPID_USE_PRIMARY;
				$site_options = $config->get_site_options();
				if (isset($site_options[$use_primary])
					&& ($site_options[$use_primary])
				) {
					LiteSpeed_Cache_Admin_Display::get_instance()->set_disable_all();
				}
			}

			return;
		}
		elseif (!current_user_can('manage_network_options')) {
			return;
		}

		if (get_transient(LiteSpeed_Cache::WHM_TRANSIENT)
			!== LiteSpeed_Cache::WHM_TRANSIENT_VAL
		) {
			return;
		}

		LiteSpeed_Cache_Admin_Display::get_instance()->show_display_installed();
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
		if (!isset($input[$id])) {
			return false;
		}
		$radio_enabled = intval($input[$id]);
		$options[$id] = $radio_enabled;
		if ($radio_enabled != LiteSpeed_Cache_Config::OPID_ENABLED_NOTSET) {
			return $radio_enabled == LiteSpeed_Cache_Config::OPID_ENABLED_ENABLE;
		}
		if (is_multisite()) {
			return $options[LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED];
		}

		return true;
	}

	/**
	 * Checks the admin selected option for cache tag prefixes.
	 *
	 * Prefixes are only allowed to be alphanumeric. On failure, will
	 * return error message.
	 *
	 * @since 1.0.9
	 * @access private
	 * @param array $input The configurations selected by the admin when
	 *     clicking save.
	 * @param array $options The current configuration options.
	 * @return mixed True on success, error message otherwise.
	 */
	private function validate_tag_prefix($input, &$options)
	{
		$id = LiteSpeed_Cache_Config::OPID_TAG_PREFIX;
		if (!isset($input[$id])) {
			return true;
		}
		$prefix = $input[$id];
		if (($prefix !== '') && (!ctype_alnum($prefix))) {
			$prefix_err = LiteSpeed_Cache_Admin_Display::build_paragraph(
				__('Invalid Tag Prefix input.', 'litespeed-cache'),
				__('Input should only contain letters and numbers.', 'litespeed-cache')
			);

			return $prefix_err;
		}
		if ($options[$id] !== $prefix) {
			$options[$id] = $prefix;
			LiteSpeed_Cache::plugin()->purge_all();
		}

		return true;
	}

	/**
	 * Helper function to validate TTL settings. Will check if it's set,
	 * is an integer, and is greater than 0 and less than INT_MAX.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param array $input Input array
	 * @param string $id Option ID
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_ttl($input, $id)
	{
		if (!isset($input[$id])) {
			return false;
		}

		$val = $input[$id];

		return ((ctype_digit($val)) && ($val >= 0) && ($val < 2147483647));
	}

	/**
	 * Validates the general settings.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 * @param array $errors The errors list.
	 */
	private function validate_general(&$input, &$options, &$errors)
	{
		$err = __('%s TTL must be an integer between %d and 2147483647',
			'litespeed-cache');
		$id = LiteSpeed_Cache_Config::OPID_ENABLED;
		$enabled = $this->validate_enabled($input, $options);
		if ($enabled !== $options[$id]) {
			$options[$id] = $enabled;
			LiteSpeed_Cache_Config::wp_cache_var_setter($enabled);
			if (!$enabled) {
				LiteSpeed_Cache::plugin()->purge_all();
			}
			elseif ($options[LiteSpeed_Cache_Config::OPID_CACHE_FAVICON]) {
				$options[LiteSpeed_Cache_Config::OPID_CACHE_FAVICON] = false;
			}
			$input[$id] = 'changed';
		}
		else {
			$input[$id] = $enabled;
		}

		$id = LiteSpeed_Cache_Config::OPID_PUBLIC_TTL;
		if ((!$this->validate_ttl($input, $id)) || ($input[$id] < 30)) {
			$errors[] = sprintf($err,
				__('Default Public Cache', 'litespeed-cache'), 30);
		}
		else {
			$options[$id] = $input[$id];
		}

		$id = LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL;
		if ((!$this->validate_ttl($input, $id)) || ($input[$id] < 30)) {
			$errors[] = sprintf($err,
				__('Default Front Page', 'litespeed-cache'), 30);
		}
		else {
			$options[$id] = $input[$id];
		}

		$id = LiteSpeed_Cache_Config::OPID_FEED_TTL;
		if (!$this->validate_ttl($input, $id)) {
			$errors[] = sprintf($err, __('Feed', 'litespeed-cache'), 0);
		}
		elseif ($input[$id] < 30) {
			$options[$id] = 0;
		}
		else {
			$options[$id] = intval($input[$id]);
		}

		$id = LiteSpeed_Cache_Config::OPID_404_TTL;
		if (!$this->validate_ttl($input, $id)) {
			$errors[] = sprintf($err, __('404', 'litespeed-cache'), 0);
		}
		elseif ($input[$id] < 30) {
			$options[$id] = 0;
		}
		else {
			$options[$id] = intval($input[$id]);
		}

		$id = LiteSpeed_Cache_Config::OPID_403_TTL;
		if (!$this->validate_ttl($input, $id)) {
			$errors[] = sprintf($err, __('403', 'litespeed-cache'), 0);
		}
		elseif ($input[$id] < 30) {
			$options[$id] = 0;
		}
		else {
			$options[$id] = intval($input[$id]);
		}

		$id = LiteSpeed_Cache_Config::OPID_500_TTL;
		if (!$this->validate_ttl($input, $id)) {
			$errors[] = sprintf($err, __('500', 'litespeed-cache'), 0);
		}
		elseif ($input[$id] < 30) {
			$options[$id] = 0;
		}
		else {
			$options[$id] = intval($input[$id]);
		}

		self::parse_checkbox(LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE,
			$input, $options);

		self::parse_checkbox(LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS,
			$input, $options);

		if (self::parse_checkbox(LiteSpeed_Cache_Config::OPID_CACHE_LOGIN,
				$input, $options) === false
		) {
			LiteSpeed_Cache_Tags::add_purge_tag(
				LiteSpeed_Cache_Tags::TYPE_LOGIN);
		}
	}

	/**
	 * Validates the purge rules settings.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 * @param array $errors The errors list.
	 */
	private function validate_purge($input, &$options, &$errors)
	{

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
			LiteSpeed_Cache_Config::PURGE_POST_TYPE,
		);
		$input_purge_options = array();
		foreach ($pvals as $pval) {
			$input_name = 'purge_' . $pval;
			if (isset($input[$input_name]) && ($pval === $input[$input_name])) {
				$input_purge_options[] = $pval;
			}
		}
		sort($input_purge_options);
		$purge_by_post = implode('.', $input_purge_options);
		if ($purge_by_post !== $options[LiteSpeed_Cache_Config::OPID_PURGE_BY_POST]) {
			$options[LiteSpeed_Cache_Config::OPID_PURGE_BY_POST] = $purge_by_post;
		}
	}

	/**
	 * Validates the exclude settings.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 * @param array $errors The errors list.
	 */
	private function validate_exclude($input, &$options, &$errors)
	{
		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_URI;
		if (isset($input[$id])) {
			$uri_arr = array_map('trim', explode("\n", $input[$id]));
			$options[$id] = implode("\n", array_filter($uri_arr));
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT;
		$options[$id] = '';
		if (isset($input[$id])) {
			$cat_ids = array();
			$cats = explode("\n", $input[$id]);
			foreach ($cats as $cat) {
				$cat_name = trim($cat);
				if ($cat_name == '') {
					continue;
				}
				$cat_id = get_cat_ID($cat_name);
				if ($cat_id == 0) {
					$errors[] = sprintf(__('Removed category "%s" from list, ID does not exist.',
						'litespeed-cache'), $cat_name);
				}
				else {
					$cat_ids[] = $cat_id;
				}
			}
			if (!empty($cat_ids)) {
				$options[$id] = implode(',', $cat_ids);
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG;
		$options[$id] = '';
		if (!isset($input[$id])) {
			return;
		}
		$tag_ids = array();
		$tags = explode("\n", $input[$id]);
		foreach ($tags as $tag) {
			$tag_name = trim($tag);
			if ($tag_name == '') {
				continue;
			}
			$term = get_term_by('name', $tag_name, 'post_tag');
			if ($term == 0) {
				$errors[] = sprintf(__('Removed tag "%s" from list, ID does not exist.',
					'litespeed-cache'), $tag_name);
			}
			else {
				$tag_ids[] = $term->term_id;
			}
		}
		if (!empty($tag_ids)) {
			$options[$id] = implode(',', $tag_ids);
		}
	}

	/**
	 * Validates the single site specific settings.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 * @param array $errors The errors list.
	 */
	private function validate_singlesite($input, &$options, &$errors)
	{
		$rules = LiteSpeed_Cache_Admin_Rules::get_instance();

		$id = LiteSpeed_Cache_Config::OPID_ENABLED;
		if ($input[$id] !== 'changed') {
			$diff = $rules->check_input($options, $input, $errors);
		}
		elseif ($options[$id]) {
			$reset = LiteSpeed_Cache_Config::get_rule_reset_options();
			$added_and_changed = $rules->check_input($reset, $input, $errors);
			// Merge to include the newly disabled options
			$diff = array_merge($reset, $added_and_changed);
		}
		else {
			LiteSpeed_Cache_Admin_Rules::clear_rules();
			$diff = $rules->check_input($options, $input, $errors);
		}

		if ((!empty($diff)) && (($options[$id] === false)
				|| ($rules->validate_common_rewrites($diff, $errors) !== false))
		) {
			$options = array_merge($options, $diff);
		}

		$out = $this->validate_tag_prefix($input, $options);
		if (is_string($out)) {
			$errors[] = $out;
		}

		self::parse_checkbox(LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE,
			$input, $options);

	}

	/**
	 * Validates the debug settings.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 * @param array $errors The errors list.
	 */
	private function validate_debug($input, &$options, &$errors)
	{
		$pattern = "/[\s,]+/";
		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS;
		if (isset($input[$id])) {
			$admin_ips = trim($input[$id]);
			$has_err = false;
			if ($admin_ips) {
				$ips = preg_split($pattern, $admin_ips, null, PREG_SPLIT_NO_EMPTY);
				foreach ($ips as $ip) {
					if (!WP_Http::is_ip_address($ip)) {
						$has_err = true;
						break;
					}
				}
			}

			if ($has_err) {
				$errors[] = __('Invalid data in Admin IPs.', 'litespeed-cache');
			}
			else if ($admin_ips != $options[$id]) {
				$options[$id] = $admin_ips;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_TEST_IPS;
		if (isset($input[$id])) {
			// this feature has not implemented yet
			$test_ips = trim($input[$id]);
			$has_err = false;
			if ($test_ips) {
				$ips = preg_split($pattern, $test_ips, null, PREG_SPLIT_NO_EMPTY);
				foreach ($ips as $ip) {
					if (!WP_Http::is_ip_address($ip)) {
						$has_err = true;
						break;
					}
				}
			}

			if ($has_err) {
				$errors[] = __('Invalid data in Test IPs.', 'litespeed-cache');
			}
			else if ($test_ips != $options[$id]) {
				$options[$id] = $test_ips;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_DEBUG;
		$debug_level = isset($input[$id]) ? intval($input[$id])
			: LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE;
		if (($debug_level != $options[$id])
			&& ($debug_level >= LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE)
			&& ($debug_level <= LiteSpeed_Cache_Config::OPID_ENABLED_NOTSET)
		) {
			$options[$id] = $debug_level;
		}
		elseif ($debug_level > LiteSpeed_Cache_Config::OPID_ENABLED_NOTSET) {
			$options[$id] = LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE;
		}
	}

	/**
	 * Validates the third party settings.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param LiteSpeed_Cache_Config $config The config class.
	 * @param array $input The input options.
	 * @param array $options The current options.
	 */
	private function validate_thirdparty($config, $input, &$options)
	{
		$tp_default_options = $config->get_thirdparty_options();
		if (empty($tp_default_options)) {
			return;
		}
		$tp_input = array_intersect_key($input, $tp_default_options);
		if (empty($tp_input)) {
			return;
		}
		$tp_options = apply_filters('litespeed_cache_save_options',
			array_intersect_key($options, $tp_default_options), $tp_input);
		if ((!empty($tp_options)) && is_array($tp_options)) {
			$options = array_merge($options, $tp_options);
		}
	}

	/**
	 * Callback function that will validate any changes made in the settings
	 * page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $input The configurations selected by the admin when
	 *     clicking save.
	 * @return array The updated configuration options.
	 */
	public function validate_plugin_settings($input)
	{
		$config = LiteSpeed_Cache::config();
		$options = $config->get_options();
		$errors = array();

		if (LiteSpeed_Cache_Admin_Display::get_instance()->get_disable_all()) {
			add_settings_error(LiteSpeed_Cache_Config::OPTION_NAME,
				LiteSpeed_Cache_Config::OPTION_NAME,
				__('\'Use primary site settings\' set by Network Administrator.', 'litespeed-cache'));

			return $options;
		}

		$this->validate_general($input, $options, $errors);

		$this->validate_purge($input, $options, $errors);

		$this->validate_exclude($input, $options, $errors);

		$this->validate_debug($input, $options, $errors);

		if (!is_multisite()) {
			$this->validate_singlesite($input, $options, $errors);
		}

		if (!empty($errors)) {
			add_settings_error(LiteSpeed_Cache_Config::OPTION_NAME,
				LiteSpeed_Cache_Config::OPTION_NAME, implode('<br>', $errors));

			return $options;
		}

		$this->validate_thirdparty($config, $input, $options);

		return $options;
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
			|| (!check_admin_referer('lscwp_manage', 'management_run'))
		) {
			return;
		}
		if (isset($_POST['purgeall'])) {
			LiteSpeed_Cache::plugin()->purge_all();
			$msg = __('Notified LiteSpeed Web Server to purge the public cache.', 'litespeed-cache');
		}
		elseif (isset($_POST['purgefront'])) {
			LiteSpeed_Cache::plugin()->purge_front();
			$msg = __('Notified LiteSpeed Web Server to purge the front page.', 'litespeed-cache');
		}
		elseif (isset($_POST['purgeerrors'])) {
			LiteSpeed_Cache::plugin()->purge_errors();
			$msg = __('Notified LiteSpeed Web Server to purge error pages.', 'litespeed-cache');
		}
		elseif (isset($_POST['purgelist'])) {
			LiteSpeed_Cache::plugin()->purge_list();

			return;
		}
		elseif (isset($_POST['clearcache'])) {
			LiteSpeed_Cache::plugin()->purge_all();
			$msg = __('Notified LiteSpeed Web Server to purge everything.', 'litespeed-cache');
		}
		else {
			return;
		}
		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
			LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg);
	}

	/**
	 * Check to make sure that the advanced-cache.php file is ours.
	 * If it doesn't exist, try to make it ours.
	 *
	 * If it is not ours and the config is set to check, output an error.
	 *
	 * @since 1.0.11
	 * @access private
	 */
	private function check_advanced_cache()
	{

		$capability = is_network_admin() ? 'manage_network_options' : 'manage_options';
		if (((defined('LSCACHE_ADV_CACHE'))
				&& (constant('LSCACHE_ADV_CACHE') === true))
			|| (!current_user_can($capability))
		) {
			if (LiteSpeed_Cache::config(
					LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE) === false
			) {
				// If it exists because I added it at runtime, try to create the file anyway.
				// Result does not matter.
				LiteSpeed_Cache::plugin()->try_copy_advanced_cache();
			}

			return;
		}

		if (LiteSpeed_Cache::plugin()->try_copy_advanced_cache()) {
			return;
		}

		if ((is_multisite()) && ((!is_network_admin())
				|| (!current_user_can('manage_network_options')))
		) {
			$third = __('For this scenario only, the network admin may uncheck "Check Advanced Cache" in LiteSpeed Cache settings.', 'litespeed-cache');
		}
		else {
			$third = __('For this scenario only, please uncheck "Check Advanced Cache" in LiteSpeed Cache settings.', 'litespeed-cache');
		}
		$msg = LiteSpeed_Cache_Admin_Display::build_paragraph(
			__('Please disable/deactivate any other Full Page Cache solutions that are currently being used.', 'litespeed-cache'),
			__('LiteSpeed Cache does work with other cache solutions, but only their non-page caching offerings—such as minifying css/js files.', 'litespeed-cache'),
			$third
		);

		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
			LiteSpeed_Cache_Admin_Display::NOTICE_YELLOW, $msg);
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
	 * Helper function to parse checkbox input.
	 *
	 * @since 1.0.11
	 * @access public
	 * @param string $id The id of the checkbox value.
	 * @param array $input The input array.
	 * @param array $options The config options array.
	 * @return boolean True if checked, false otherwise.
	 */
	public static function parse_checkbox($id, $input, &$options)
	{
		if (isset($input['lscwp_' . $id])) {
			$options[$id] = ($input['lscwp_' . $id] === $id);
		}
		else {
			$options[$id] = false;
		}

		return $options[$id];
	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function validate_network_settings()
	{
		if ((!is_multisite()) || (!is_network_admin())) {
			return;
		}
		if (empty($_POST) || empty($_POST['submit'])) {
			return;
		}
		if ((!isset($_POST['lscwp_settings_save']))
			|| (empty($_POST[LiteSpeed_Cache_Config::OPTION_NAME]))
			|| ($_POST['lscwp_settings_save'] !== 'save_settings')
			|| (!check_admin_referer('lscwp_settings', 'save'))
		) {
			return;
		}

		$input = array_map("LiteSpeed_Cache_Admin::cleanup_text",
			$_POST[LiteSpeed_Cache_Config::OPTION_NAME]);
		$config = LiteSpeed_Cache::config();
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
			$reset = LiteSpeed_Cache_Config::get_rule_reset_options();
		}

		$id = LiteSpeed_Cache_Config::NETWORK_OPID_USE_PRIMARY;
		$orig_primary = $options[$id];
		$ret = self::parse_checkbox($id, $input, $options);
		if ($orig_primary !== $ret) {
			LiteSpeed_Cache::plugin()->purge_all();
		}

		self::parse_checkbox(LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE,
			$input, $options);

		self::parse_checkbox(LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE,
			$input, $options);

		$out = $this->validate_tag_prefix($input, $options);
		if (is_string($out)) {
			$errors[] = $out;
		}

		$rules = LiteSpeed_Cache_Admin_Rules::get_instance();

		if ($input[LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED]
			!== 'changed'
		) {
			$diff = $rules->check_input($options, $input, $errors);
		}
		elseif ($network_enabled) {
			$added_and_changed = $rules->check_input($reset, $input, $errors);
			// Merge to include the newly disabled options
			$diff = array_merge($reset, $added_and_changed);
		}
		else {
			$rules->validate_common_rewrites($reset, $errors);
			$diff = $rules->check_input($options, $input, $errors);
		}

		if ((!empty($diff)) && (($network_enabled === false)
				|| ($rules->validate_common_rewrites($diff, $errors) !== false))
		) {
			$options = array_merge($options, $diff);
		}

		if (!empty($errors)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED, $errors);

			return;
		}
		LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
			LiteSpeed_Cache_Admin_Display::NOTICE_GREEN,
			__('File saved.', 'litespeed-cache'));
		update_site_option(LiteSpeed_Cache_Config::OPTION_NAME, $options);
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
			__('It is recommended that LiteSpeed Cache be purged after updating a plugin.',
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
