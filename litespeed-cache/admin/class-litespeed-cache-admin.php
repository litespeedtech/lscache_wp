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

class LiteSpeed_Cache_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $messages;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));

		//Additional links on the plugin page
		if (is_multisite()) {
			add_action('network_admin_menu', array($this, 'register_admin_menu'));
		}
		else {
			add_action('admin_menu', array($this, 'register_admin_menu'));
		}

		add_action('admin_init', array($this, 'admin_init'));
		$plugin_dir = plugin_dir_path(dirname(__FILE__));
		add_filter('plugin_action_links_' . plugin_basename($plugin_dir .'/'. $plugin_name . '.php'), array($this, 'add_plugin_links'));

	}

	/**
	 * Register the stylesheets and JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		// not used js css
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/litespeed-cache-admin.css', array(), $this->version, 'all' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/litespeed-cache-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function register_admin_menu()
	{
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		if (current_user_can($capability)) {

			$lscache_admin_manage_page = add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', $capability,  'lscachemgr', array($this, 'show_menu_manage'), 'dashicons-performance');
			add_action('load-' . $lscache_admin_manage_page, array($this, 'add_help_tabs'));

			$lscache_admin_settings_page = add_options_page('LiteSpeed Cache', 'LiteSpeed Cache', $capability,  'litespeedcache', array($this, 'show_menu_settings'));
			// adds help tab
			add_action('load-' . $lscache_admin_settings_page, array($this, 'add_help_tabs'));
        }

	}

	public function admin_init()
	{
		// check for upgrade
		LiteSpeed_Cache::config()->plugin_upgrade();

		// check management action
		$this->check_cache_mangement_actions();

		$option_name = LiteSpeed_Cache_Config::OPTION_NAME;
		register_setting($option_name, $option_name, array($this, 'validate_plugin_settings'));
	}

	public function add_help_tabs()
	{
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'      => 'lsc-overview',
			'title'   => __('Overview'),
			'content' => '<p>' . __('LiteSpeed Cache is a page cache built into LiteSpeed Web Server. This plugin communicates with LiteSpeed Web Server to let it know which pages are cache-able and when to purge them.') . '</p>' .
				'<p>' . __('You must have the LSCache module installed and enabled in your LiteSpeed Web Server setup.') . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'lst-purgerules',
			'title'   => __( 'Auto Purge Rules' ),
			'content' => '<p>' . __( 'You can set what pages will be purged when a post is published or updated. ' ) . '</p>',
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache" target="_blank">Documentation on LSCache</a>') . '</p>' .
			'<p>' . __('<a href="https://www.litespeedtech.com/support/forum/" target="_blank">Support Forums</a>') . '</p>'
		);
	}

	public function validate_plugin_settings($input)
	{
		$config = LiteSpeed_Cache::config();
		$options = $config->get_options();
		$pattern = "/[\s,]+/" ;
		$errors = array();

		$id = LiteSpeed_Cache_Config::OPID_ENABLED;
		$enabled = isset($input[$id]) && ('1' === $input[$id]);
		if ($enabled !== $options[$id]) {
			$options[$id] = $enabled;
		}

		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS;
		if (isset($input[$id])) {
			$admin_ips = trim($input[$id]);
			$has_err = false;
			if ($admin_ips) {
				$ips = preg_split($pattern, $admin_ips, NULL, PREG_SPLIT_NO_EMPTY);
				foreach ($ips as $ip) {
					if (!WP_Http::is_ip_address($ip)) {
						$has_err = true;
						break;
					}
				}
			}

			if ($has_err) {
				$errors[] = 'Invalid data in Admin IPs.';
			}
			else if ($admin_ips != $options[$id]) {
				$options[$id] = $admin_ips;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_PUBLIC_TTL;
		if (!isset($input[$id])
				|| !ctype_digit($input[$id])
				|| $input[$id] < 30) {
			$errors[] = 'Require numeric number, minimum is 30';
		}
		else {
			$options[$id] = $input[$id];
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

		$id = LiteSpeed_Cache_Config::OPID_TEST_IPS;
		if (isset($input[$id])) {
			// this feature has not implemented yet
			$test_ips = trim($input[$id]);
			$has_err = false;
			if ($test_ips) {
				$ips = preg_split($pattern, $test_ips, NULL, PREG_SPLIT_NO_EMPTY);
				foreach ($ips as $ip) {
					if (!WP_Http::is_ip_address($ip)) {
						$has_err = true;
						break;
					}
				}
			}

			if ($has_err) {
				$errors[] = 'Invalid data in Test IPs.';
			}
			else if ($test_ips != $options[$id]) {
				$options[$id] = $test_ips;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_DEBUG;
		$debug_level = isset($input[$id]) ? intval($input[$id]) : LiteSpeed_Cache_Config::LOG_LEVEL_NONE;
		if (($debug_level != $options[$id])
				&& ($debug_level >= LiteSpeed_Cache_Config::LOG_LEVEL_NONE)
				&& ($debug_level <= LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG) ) {
			$options[$id] = $debug_level;
		}

		if (!empty($errors)) {
			add_settings_error(LiteSpeed_Cache_Config::OPTION_NAME,
					LiteSpeed_Cache_Config::OPTION_NAME,
					implode('<br>', $$errors));
		}

		return $options;
	}

	public function add_plugin_links($links)
	{
		//$links[] = '<a href="' . admin_url('admin.php?page=litespeedcache') .'">Settings</a>';
		$links[] = '<a href="' . admin_url('options-general.php?page=litespeedcache') .'">Settings</a>';
		return $links;
	}

	public function show_menu_manage()
	{
		$config = LiteSpeed_Cache::config();

		if (!$this->check_license($config))
			return;

		if ($this->messages) {
			echo '<div class="success"><p>' . $this->messages . ' </p></div>' . "\n";
		}

		echo '<div class="wrap"><h2>LiteSpeed Cache Management</h2>'
		. '<p>LiteSpeed Cache is maintained and managed by LiteSpeed Web Server. You can inform LiteSpeed Web Server to purge cached contents from this screen.</p>'
				. '<p>More options will be added here in future releases. </p>';

		echo '<form method="post">';
		wp_nonce_field(LiteSpeed_Cache_Config::OPTION_NAME);

		submit_button('Purge All LiteSpeed Cache', 'primary', 'purgeall');
		echo "</form></div>\n";

	}

	private function check_cache_mangement_actions()
	{
		if (isset($_POST['purgeall'])) {
			LiteSpeed_Cache::plugin()->purge_all();
			$this->messages = 'Notified LiteSpeed Web Server to purge all the public cache.';
		}
	}

	public function show_menu_settings()
	{
		$config = LiteSpeed_Cache::config();

		if (!$this->check_license($config))
			return;

		$options = $config->get_options();
		$purge_options = $config->get_purge_options();

		echo '<div class="wrap">
  <h2>LiteSpeed Cache Settings <span style="font-size:0.5em">v' . LiteSpeed_Cache::PLUGIN_VERSION . '</span></h2>
<form method="post" action="options.php">';
		settings_fields(LiteSpeed_Cache_Config::OPTION_NAME);

		$this->show_settings_general($options);
		$this->show_settings_purge($config->get_purge_options());
		$this->show_settings_test($options);

		submit_button();
		echo "</form></div>\n";
	}

	private function check_license($config)
	{
		$enabled = $config->module_enabled();

		if (0 == ($enabled & 1)) {
			echo '<div class="error"><p>Notice: Your installation of LiteSpeed Web Server does not have LSCache enabled. This plugin will NOT work properly. </p></div>' . "\n";
			return false;
		}
		return true;
	}

	private function show_settings_general($options)
	{
		$buf = $this->input_group_start('General');

		$id = LiteSpeed_Cache_Config::OPID_ENABLED;
		$input_enabled = $this->input_field_checkbox($id, '1', $options[$id]);
		$buf .= $this->display_config_row('Enable LiteSpeed Cache', $input_enabled);

		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS;
		$input_admin_ips  = $this->input_field_text($id, $options[$id], '', 'regular-text');
		$buf .= $this->display_config_row('Admin IPs', $input_admin_ips,
				'Allows listed IPs (space or comma separated) to perform certain actions from their browsers.');

		$id = LiteSpeed_Cache_Config::OPID_PUBLIC_TTL;
		$input_public_ttl = $this->input_field_text($id, $options[$id], 10, 'Require number in seconds, minimum is 30', 'seconds');
		$buf .= $this->display_config_row('Default Public Cache TTL', $input_public_ttl);

		$buf .= $this->input_group_end();
		echo $buf;
	}

	private function show_settings_purge($purge_options)
	{
		$buf = $this->input_group_start('Auto Purge Rules For Publish/Update', 'Select which pages will be automatically purged when posts are published/updated. <br>Note: Select "All" if you have dynamic widgets linked to posts on pages other than the front or home pages. (Other checkboxes will be ignored)');

		$tr = '<tr><th scope="row" colspan="2" class="th-full">';
		$endtr = "</th></tr>\n";
		$buf .= $tr;

		$spacer = '&nbsp;&nbsp;&nbsp;';

		$pval = LiteSpeed_Cache_Config::PURGE_ALL_PAGES;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'All pages');

		$buf .= $spacer;

		$pval = LiteSpeed_Cache_Config::PURGE_FRONT_PAGE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Front page');

		$buf .= $spacer;

		$pval = LiteSpeed_Cache_Config::PURGE_HOME_PAGE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Home page');

		$buf .= $endtr . $tr;

		$pval = LiteSpeed_Cache_Config::PURGE_AUTHOR;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Author archive');

		$buf .= $spacer;

		$pval = LiteSpeed_Cache_Config::PURGE_POST_TYPE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Post type archive');

		$buf .= $endtr . $tr;

		$pval = LiteSpeed_Cache_Config::PURGE_YEAR;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Yearly archive');

		$buf .= $spacer;

		$pval = LiteSpeed_Cache_Config::PURGE_MONTH;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Monthly archive');

		$buf .= $spacer;

		$pval = LiteSpeed_Cache_Config::PURGE_DATE;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Daily archive');

		$buf .= $endtr . $tr;

		$pval = LiteSpeed_Cache_Config::PURGE_TERM;
		$buf .= $this->input_field_checkbox(
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Term archive (include category, tag and tax)');

		$buf .= $endtr;
		$buf .= $this->input_group_end();
		echo $buf;
	}

	private function show_settings_test($options)
	{
		$buf = $this->input_group_start('Developer Testing');

		$id = LiteSpeed_Cache_Config::OPID_DEBUG;
		$debug_levels = array(
			LiteSpeed_Cache_Config::LOG_LEVEL_NONE => 'None',
			LiteSpeed_Cache_Config::LOG_LEVEL_ERROR => 'Error',
			LiteSpeed_Cache_Config::LOG_LEVEL_NOTICE => 'Notice',
			LiteSpeed_Cache_Config::LOG_LEVEL_INFO => 'Info',
			LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG => 'Debug');
		$input_debug = $this->input_field_select($id, $debug_levels, $options[$id]);
		$buf .= $this->display_config_row('Debug Level', $input_debug);

		/* Maybe add this feature later
		$id = LiteSpeed_Cache_Config::OPID_TEST_IPS;
		$input_test_ips  = $this->input_field_text($id, $options[$id], '', 'regular-text');
		$buf .= $this->display_config_row('Test IPs', $input_test_ips,
				'Enable LiteSpeed Cache only for specified IPs. (Space or comma separated.) Allows testing on a live site. If empty, cache will be served to everyone.');
		 *
		 */

		$buf .= $this->input_group_end();
		echo $buf;
	}

	private function input_group_start($title='', $description='')
	{
		$buf = '';
		if ($title) {
			$buf .= '<hr/><h3 class="title">' . $title . "</h3>\n";
		}
		if ($description) {
			$buf .= '<p>' . $description . "</p>\n";
		}
		$buf .= '<table class="form-table">' . "\n";
		return $buf;
	}

	private function input_group_end()
	{
		return "</table>\n";
	}

	private function display_config_row($label, $input_field, $notes = '')
	{
		$buf = '<tr><th scope="row">' . $label . '</th><td>' . $input_field;
		if ($notes) {
			$buf .= '<p class="description">' . $notes . '</p>';
		}
		$buf .= '</td></tr>' . "\n";
		return $buf;
	}

	private function input_field_checkbox($id, $value, $checked_value, $label='')
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="checkbox" id="'
				. $id . '" value="' . $value . '"';
		if (($checked_value === $value) || (true === $checked_value)) {
			$buf .= ' checked="checked"';
		}
		$buf .= '/>';
		if ($label) {
			$buf .= '<label for="' . $id . '">' . $label . '</label>';
		}
		return $buf;
	}

	private function input_field_select($id, $seloptions, $selected_value)
	{
		$buf = '<select name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" id="'
				. $id . '">';
		foreach ($seloptions as $val => $label) {
			$buf .= '<option value="' . $val . '"';
			if ($selected_value == $val) {
				$buf .= ' selected="selected"';
			}
			$buf .= '>' . $label . '</option>';
		}
		$buf .= '</select>';
		return $buf;
	}

	private function input_field_text($id, $value, $size='', $style='', $after='')
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="text" id="'
				. $id . '" value="' . $value . '"';
		if ($size) {
			$buf .= ' size="' . $size . '"';
		}
		if ($style) {
			$buf .= ' class="' . $style . '"';
		}
		$buf .= '/>';
		if ($after) {
			$buf .= ' ' . $after;
		}
		return $buf;
	}
}
