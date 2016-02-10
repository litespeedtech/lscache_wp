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
		if ( is_multisite() ) {
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

	public function register_admin_menu()
	{
		$capability = is_multisite() ? 'manage_network_options' : 'manage_options' ;
		if ( current_user_can($capability) ) {

			$lscache_admin_manage_page = add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', $capability, 'lscachemgr', array( $this, 'show_menu_manage' ), 'dashicons-performance') ;
			add_action('load-' . $lscache_admin_manage_page, array( $this, 'add_help_tabs' )) ;

			$lscache_admin_settings_page = add_options_page('LiteSpeed Cache', 'LiteSpeed Cache', $capability, 'litespeedcache', array( $this, 'show_menu_settings' )) ;
			// adds help tab
			add_action('load-' . $lscache_admin_settings_page, array( $this, 'add_help_tabs' )) ;
		}
	}

	public function admin_init()
	{
		// check for upgrade
		LiteSpeed_Cache::config()->plugin_upgrade() ;

		// check management action
		$this->check_cache_mangement_actions() ;

		$option_name = LiteSpeed_Cache_Config::OPTION_NAME ;
		register_setting($option_name, $option_name, array( $this, 'validate_plugin_settings' )) ;
	}

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

	public function validate_plugin_settings( $input )
	{
		$config = LiteSpeed_Cache::config() ;
		$options = $config->get_options() ;
		$pattern = "/[\s,]+/" ;
		$errors = array() ;

		$id = LiteSpeed_Cache_Config::OPID_ENABLED ;
		$enabled = isset($input[$id]) && ('1' === $input[$id]) ;
		if ( $enabled !== $options[$id] ) {
			$options[$id] = $enabled ;
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

	public function add_plugin_links( $links )
	{
		//$links[] = '<a href="' . admin_url('admin.php?page=litespeedcache') .'">Settings</a>';
		$links[] = '<a href="' . admin_url('options-general.php?page=litespeedcache') . '">' . __('Settings', 'litespeed-cache') . '</a>' ;
		return $links ;
	}

	public function show_menu_manage()
	{
		$config = LiteSpeed_Cache::config() ;

		if ( ! $this->check_license($config) )
			return ;

		if ( $this->messages ) {
			echo '<div class="success"><p>' . $this->messages . ' </p></div>' . "\n" ;
		}

		echo '<div class="wrap"><h2>' . __('LiteSpeed Cache Management', 'litespeed-cache') . '</h2>'
		. '<p>' . __('LiteSpeed Cache is maintained and managed by LiteSpeed Web Server. You can inform LiteSpeed Web Server to purge cached contents from this screen.', 'litespeed-cache') . '</p>'
		. '<p>' . __('More options will be added here in future releases.', 'litespeed-cache') . '</p>' ;

		echo '<form method="post">' ;
		wp_nonce_field(LiteSpeed_Cache_Config::OPTION_NAME) ;

		submit_button(__('Purge All', 'litespeed-cache'), 'primary', 'purgeall') ;
		echo "</form></div>\n" ;
	}

	private function check_cache_mangement_actions()
	{
		if ( isset($_POST['purgeall']) ) {
			LiteSpeed_Cache::plugin()->purge_all() ;
			$this->messages = __('Notified LiteSpeed Web Server to purge the public cache.', 'litespeed-cache') ;
		}
	}

	public function show_menu_settings()
	{
		$config = LiteSpeed_Cache::config() ;

		if ( ! $this->check_license($config) )
			return ;

		$options = $config->get_options() ;
		$purge_options = $config->get_purge_options() ;

		echo '<div class="wrap">
		<h2>' . __('LiteSpeed Cache Settings', 'litespeed-cache') . '<span style="font-size:0.5em">v' . LiteSpeed_Cache::PLUGIN_VERSION . '</span></h2>
		<form method="post" action="options.php">' ;

		settings_fields(LiteSpeed_Cache_Config::OPTION_NAME) ;

		echo '
		 <div id="lsc-tabs">
		 <ul>
		 <li><a href="#basic-settings">' . __('Basic', 'litespeed-cache') . '</a></li>
		 <li><a href="#advanced-settings">' . __('Advanced', 'litespeed-cache') . '</a></li>
		<li><a href="#debug-settings">' . __('Debug', 'litespeed-cache') . '</a></li>
		</ul>
		 <div id="basic-settings">'
		. $this->show_settings_general($options) .
		'</div>
		<div id="advanced-settings">'
		. $this->show_settings_purge($config->get_purge_options()) .
		'</div>
		<div id ="debug-settings">'
		. $this->show_settings_test($options) .
		'</div></div>' ;

		submit_button() ;
		echo "</form></div>\n" ;
	}

	private function check_license( $config )
	{
		$enabled = $config->module_enabled() ;

		if ( 0 == ($enabled & 1) ) {
			echo '<div class="error"><p>' . __('Notice: Your installation of LiteSpeed Web Server does not have LSCache enabled. This plugin will NOT work properly.', 'litespeed-cache') . '</p></div>' . "\n" ;
			return false ;
		}
		return true ;
	}

	private function show_settings_general( $options )
	{
		$buf = $this->input_group_start(__('General', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_ENABLED ;
		$input_enabled = $this->input_field_checkbox($id, '1', $options[$id]) ;
		$buf .= $this->display_config_row(__('Enable LiteSpeed Cache', 'litespeed-cache'), $input_enabled) ;

		$id = LiteSpeed_Cache_Config::OPID_PUBLIC_TTL ;
		$input_public_ttl = $this->input_field_text($id, $options[$id], 10, 'regular-text', __('seconds', 'litespeed-cache')) ;
		$buf .= $this->display_config_row(__('Default Public Cache TTL', 'litespeed-cache'), $input_public_ttl, __('Required number in seconds, minimum is 30.', 'litespeed-cache')) ;

		$buf .= $this->input_group_end() ;
		return $buf ;
	}

	private function show_settings_purge( $purge_options )
	{
		$buf = $this->input_group_start(__('Auto Purge Rules For Publish/Update', 'litespeed-cache'), __('Select which pages will be automatically purged when posts are published/updated.', 'litespeed-cache') . '<br>' . __('Note: Select "All" if you have dynamic widgets linked to posts on pages other than the front or home pages. (Other checkboxes will be ignored)', 'litespeed-cache')) ;

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

	private function show_settings_test( $options )
	{
		$buf = $this->input_group_start(__('Developer Testing', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS ;
		$input_admin_ips = $this->input_field_text($id, $options[$id], '', 'regular-text') ;
		$buf .= $this->display_config_row(__('Admin IPs', 'litespeed-cache'), $input_admin_ips, __('Allows listed IPs (space or comma separated) to perform certain actions from their browsers.', 'litespeed-cache')) ;

		$id = LiteSpeed_Cache_Config::OPID_DEBUG ;
		$debug_levels = array(
			LiteSpeed_Cache_Config::LOG_LEVEL_NONE => __('None', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_ERROR => __('Error', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_NOTICE => __('Notice', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_INFO => __('Info', 'litespeed-cache'),
			LiteSpeed_Cache_Config::LOG_LEVEL_DEBUG => __('Debug', 'litespeed-cache') ) ;
		$input_debug = $this->input_field_select($id, $debug_levels, $options[$id]) ;
		$buf .= $this->display_config_row(__('Debug Level', 'litespeed-cache'), $input_debug, __('Outputs to WordPress debug log.', 'litespeed-cache')) ;

		/* Maybe add this feature later
		  $id = LiteSpeed_Cache_Config::OPID_TEST_IPS;
		  $input_test_ips  = $this->input_field_text($id, $options[$id], '', 'regular-text');
		  $buf .= $this->display_config_row('Test IPs', $input_test_ips,
		  'Enable LiteSpeed Cache only for specified IPs. (Space or comma separated.) Allows testing on a live site. If empty, cache will be served to everyone.');
		 *
		 */

		$buf .= $this->input_group_end() ;
		return $buf ;
	}

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

	private function input_group_end()
	{
		return "</table>\n" ;
	}

	private function display_config_row( $label, $input_field, $notes = '' )
	{
		$buf = '<tr><th scope="row">' . $label . '</th><td>' . $input_field ;
		if ( $notes ) {
			$buf .= '<p class="description">' . $notes . '</p>' ;
		}
		$buf .= '</td></tr>' . "\n" ;
		return $buf ;
	}

	private function input_field_checkbox( $id, $value, $checked_value, $label = '' )
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="checkbox" id="'
				. $id . '" value="' . $value . '"' ;
		if ( ($checked_value === $value) || (true === $checked_value) ) {
			$buf .= ' checked="checked"' ;
		}
		$buf .= '/>' ;
		if ( $label ) {
			$buf .= '<label for="' . $id . '">' . $label . '</label>' ;
		}
		return $buf ;
	}

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

	private function input_field_text( $id, $value, $size = '', $style = '', $after = '' )
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="text" id="'
				. $id . '" value="' . $value . '"' ;
		if ( $size ) {
			$buf .= ' size="' . $size . '"' ;
		}
		if ( $style ) {
			$buf .= ' class="' . $style . '"' ;
		}
		$buf .= '/>' ;
		if ( $after ) {
			$buf .= ' ' . $after ;
		}
		return $buf ;
	}

}
