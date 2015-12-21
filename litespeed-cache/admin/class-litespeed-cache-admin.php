<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    LSCache_WPConnector
 * @subpackage LSCache_WPConnector/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LSCache_WPConnector
 * @subpackage LSCache_WPConnector/admin
 * @author     Your Name <email@example.com>
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
		if (current_user_can('install_plugins')) {
            //add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', 'manage_options',  'litespeedcache', array($this, 'show_menu_options'), 'dashicons-performance');
			$lscache_admin_page = add_options_page('LiteSpeed Cache', 'LiteSpeed Cache', 'manage_options',  'litespeedcache', array($this, 'show_menu_options'));
			// adds help tab
			add_action('load-' . $lscache_admin_page, array($this, 'add_help_tabs'));
        }

	}

	public function admin_init()
	{
		//this will save the option in the wp_options table
		//the third parameter is a function that will validate your input values
		$option_name = LiteSpeed_Cache_Config::OPTION_NAME;
		register_setting($option_name, $option_name, array($this, 'validate_plugin_settings'));
	}

	public function add_help_tabs()
	{
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'      => 'lsc-overview',
			'title'   => __('Overview'),
			'content' => '<p>' . __('LiteSpeed Cache is a page cache built into LiteSpeed Web Server. This plugin will communicate with LiteSpeed Web Server and inform what page can be cached and when to purge them.') . '</p>' .
				'<p>' . __('You need to have LSCache module enabled in your LiteSpeed Web Server set up.') . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'lst-purgerules',
			'title'   => __( 'Auto Purge Rules' ),
			'content' => '<p>' . __( 'You can set what pages are purged when a post is published or updated. ' ) . '</p>',
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache" target="_blank">Documentation on LSCache</a>') . '</p>' .
			'<p>' . __('<a href="https://www.litespeedtech.com/support/forum/" target="_blank">Support Forums</a>') . '</p>'
		);
	}

	public function validate_plugin_settings($input)
	{
		error_log("validate settings args = " . print_r($input, true));

		$config = LiteSpeed_Cache::config();
		$options = $config->get_options();

		$enabled = isset($input[$config::OPID_ENABLED]) && ('1' === $input[$config::OPID_ENABLED]);
		if ($enabled !== $options[$config::OPID_ENABLED]) {
			$options[$config::OPID_ENABLED] = $enabled;
		}

		if (!isset($input[$config::OPID_PUBLIC_TTL])
				|| !ctype_digit($input[$config::OPID_PUBLIC_TTL])
				|| $input[$config::OPID_PUBLIC_TTL] < 30) {
			add_settings_error(LiteSpeed_Cache_Config::OPTION_NAME, 'public_ttl_invalid', 'Require numeric number, minimum is 30', 'error');
		}
		else {
			$options[$config::OPID_PUBLIC_TTL] = $input[$config::OPID_PUBLIC_TTL];
		}

		// get purge options
		$pvals = array( $config::PURGE_FRONT_PAGE, $config::PURGE_HOME_PAGE,
			$config::PURGE_AUTHOR,
			$config::PURGE_YEAR, $config::PURGE_MONTH, $config::PURGE_DATE,
			$config::PURGE_TERM,
			$config::PURGE_POST_TYPE
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
		if ($purge_by_post !== $options[$config::OPID_PURGE_BY_POST]) {
			$options[$config::OPID_PURGE_BY_POST] = $purge_by_post;
		}


		return $options;
	}

	public function add_plugin_links($links)
	{
		//$links[] = '<a href="' . admin_url('admin.php?page=litespeedcache') .'">Settings</a>';
		$links[] = '<a href="' . admin_url('options-general.php?page=litespeedcache') .'">Settings</a>';
		return $links;
	}

	public function show_menu_options()
	{
		$config = LiteSpeed_Cache::config();

		$options = $config->get_options();
		$enabled = $config->module_enabled();
		$option_name = $config::OPTION_NAME;

		if (0 == ($enabled & 1)) {
			echo '<div class="error"><p>Notice: Your installation of LiteSpeed Web Server does not have LSCache enabled. This plugin will NOT work properly. </p></div>' . "\n";
		}

		echo '<div class="wrap">
  <h2>LiteSpeed Cache Settings <span style="font-size:0.5em">v' . LiteSpeed_Cache::PLUGIN_VERSION . '</span></h2>
<form method="post" action="options.php">';
		settings_fields($option_name);

		$this->show_settings_general($config, $options);
		$this->show_settings_purge($config, $options);

		submit_button();
		echo "</form></div>\n";
	}

	private function show_settings_general($config, $options)
	{
		$buf = $this->input_group_start();

		$input_enabled = $this->input_field_checkbox($config::OPTION_NAME, $config::OPID_ENABLED, '1', $options[$config::OPID_ENABLED]);
		$buf .= $this->display_config_row('Enable LiteSpeed Cache', $input_enabled);

		$input_public_ttl = $this->input_field_text($config::OPTION_NAME, $config::OPID_PUBLIC_TTL, $options[$config::OPID_PUBLIC_TTL], 10, '', 'seconds');
		$buf .= $this->display_config_row('Default Public Cache TTL', $input_public_ttl);

		$buf .= $this->input_group_end();
		echo $buf;
	}

	private function show_settings_purge($config, $options)
	{
		$buf = $this->input_group_start('Auto Purge Rules', 'Select below what archive pages will be automatically purged when posts are published/updated');

		$tr = '<tr><th scope="row" colspan="2" class="th-full">';
		$endtr = "</th></tr>\n";
		$buf .= $tr;

		$purge_options = $config->get_purge_options();
		$name = $config::OPTION_NAME;
		$spacer = '&nbsp;&nbsp;&nbsp;';

		$pval = $config::PURGE_FRONT_PAGE;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Front page');

		$buf .= $spacer;

		$pval = $config::PURGE_HOME_PAGE;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Home page');

		$buf .= $endtr . $tr;

		$pval = $config::PURGE_AUTHOR;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Author archive');

		$buf .= $endtr . $tr;

		$pval = $config::PURGE_YEAR;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Yearly archive');

		$buf .= $spacer;

		$pval = $config::PURGE_MONTH;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Monthly archive');

		$buf .= $spacer;

		$pval = $config::PURGE_DATE;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Daily archive');

		$buf .= $endtr . $tr;

		$pval = $config::PURGE_TERM;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Term archive (include category, tag and tax)');

		$buf .= $endtr . $tr;

		$pval = $config::PURGE_POST_TYPE;
		$buf .= $this->input_field_checkbox($name,
				'purge_' . $pval, $pval, in_array($pval, $purge_options),
				'Post type archive');

		$buf .= $endtr;
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

	private function input_field_checkbox($option_name, $id, $value, $checked_value, $label='')
	{
		$buf = '<input name="' . $option_name . '[' . $id . ']" type="checkbox" id="'
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

	private function input_field_text($option_name, $id, $value, $size='', $style='', $after='')
	{
		$buf = '<input name="' . $option_name . '[' . $id . ']" type="text" id="'
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
