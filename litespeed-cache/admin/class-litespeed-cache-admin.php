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
	//	if ( is_multisite() ) {
	//		add_action('network_admin_menu', array( $this, 'register_admin_menu' )) ;
	//	}
	//	else {
			add_action('admin_menu', array( $this, 'register_admin_menu' )) ;
	//	}

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
			$config->wp_cache_var_setter($enabled);
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

	public function add_plugin_links( $links )
	{
		//$links[] = '<a href="' . admin_url('admin.php?page=litespeedcache') .'">Settings</a>';
		$links[] = '<a href="' . admin_url('options-general.php?page=litespeedcache') . '">' . __('Settings', 'litespeed-cache') . '</a>' ;
		return $links ;
	}

	public function show_menu_manage()
	{
		$config = LiteSpeed_Cache::config() ;

		if ( ! $this->check_license($config, $error_msg) )
			echo '<div class="error"><p>' . $error_msg . '</p></div>' . "\n" ;
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

	private function show_compatibilities_tab() {
		if (function_exists('the_views')) {
			return true;
		}
		return false;
	}

	public function show_menu_settings()
	{
		$config = LiteSpeed_Cache::config() ;

		if ( ! $this->check_license($config, $error_msg) ) {
			echo '<div class="error"><p>' . $error_msg . '</p></div>' . "\n" ;

		}

		$options = $config->get_options() ;
		$purge_options = $config->get_purge_options() ;

		echo '<div class="wrap">
		<h2>' . __('LiteSpeed Cache Settings', 'litespeed-cache') . '<span style="font-size:0.5em">v' . LiteSpeed_Cache::PLUGIN_VERSION . '</span></h2>
		<form method="post" action="options.php">' ;

		settings_fields(LiteSpeed_Cache_Config::OPTION_NAME) ;

		$compatibilities_tab = '';
		$compatibilities_settings = '';
		if ($this->show_compatibilities_tab()) {
			$compatibilities_tab .= '<li><a href="#wp-compatibilities-settings">'
					. __('Plugin Compatibilities', 'litespeed-cache') . '</a></li>';
			$compatibilities_settings .= '<div id ="wp-compatibilities-settings">'
							. $this->show_settings_compatibilities($options) .
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

	private function check_license( $config, &$error_msg )
	{
		if ($config->is_caching_allowed() == false) {
			$error_msg = __('Notice: Your installation of LiteSpeed Web Server does not have '
						 . 'LSCache enabled. This plugin will NOT work properly.',
						'litespeed-cache');
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
		$buf = $this->input_group_start(__('Auto Purge Rules For Publish/Update', 'litespeed-cache'),
				__('Select which pages will be automatically purged when posts are published/updated.', 'litespeed-cache')
				. '<br>'
				. __('Note: Select "All" if you have dynamic widgets linked to posts on pages other than the front or home pages. (Other checkboxes will be ignored)', 'litespeed-cache')) ;

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

	private function show_settings_excludes( $options )
	{

        $uri_description =
            __('Enter a list of urls that you do not want to have cached.', 'litespeed-cache')
            . '<br>'
            . __('The urls will be compared to the REQUEST_URI server variable.', 'litespeed-cache')
            . '<br>'
            . __('There should only be one url per line.', 'litespeed-cache')
            . '<br><br>
			<b>' . __('NOTE:', 'litespeed-cache') . '</b>' . __('URLs must start with a \'/\' to be correctly matched.', 'litespeed-cache')
            . '<br>'
            . __('Any surrounding whitespaces will be trimmed.', 'litespeed-cache')
            . '<br><br>'
            . sprintf(__('e.g. to exclude %s, I would have:', 'litespeed-cache'),'http://www.example.com/excludethis.php')
            . '<br>
            <input type="text" name="example_exclude" value="/excludethis.php" readonly>
            <br><br>';

		$cat_description =
            '<b>' . __('All categories are cached by default.', 'litespeed-cache') . '</b>
			<br>'
			. __('To prevent a category from being cached, enter it in the text area below,
			one per line.', 'litespeed-cache')
			. '<br>
			<b>' . __('NOTE:', 'litespeed-cache') . '</b>' . __('If the Category ID is not found, the name will be removed on save.', 'litespeed-cache')
            . '<br><br>';

		$tag_description =
            '<b>' . __('All tags are cached by default.', 'litespeed-cache') . '</b>
			<br>'
			. __('To prevent tags from being cached, enter it in the text area below,
			one per line.', 'litespeed-cache')
			. '<br>
			<b>' . __('NOTE:', 'litespeed-cache') . '</b>' . __('If the Tag ID is not found, the name will be removed on save.', 'litespeed-cache')
            . '<br><br>';

        $tr = '<tr><td>' ;
        $endtr = "</td></tr>\n" ;

        $excludes_id = LiteSpeed_Cache_Config::OPID_EXCLUDES_URI;
        $excludes_buf = $options[$excludes_id];
        $buf = $this->input_group_start(
                                __('URI List', 'litespeed-cache'), $uri_description);
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
        $buf .= $this->input_group_start(
                                __('Category List', 'litespeed-cache'), $cat_description);
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
        $buf .= $this->input_group_start(
                                __('Tag List', 'litespeed-cache'), $tag_description);
        $buf .= $tr ;
        $buf .= $this->input_field_textarea($excludes_id, $excludes_buf,
                                                '5', '80', '');
        $buf .= $endtr;

		$buf .= $this->input_group_end();
        return $buf;
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

	private function show_wp_postviews_help() {
		$buf = '';
		$example_src = '&lt;?php if(function_exists(&apos;the_views &apos;)) { the_views(); } ?&gt;';
		$example_div = '&lt;div id=&quot;postviews_lscwp&quot; &gt; &lt;/div&gt;';
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
				<li>' . __('Replace the following calls in your theme\'s template files
				with a div or span with a unique ID.', 'litespeed-cache') . '<br>'
				. printf(__('e.g. Replace <br> %1$s
				<br>with<br> %2$s'),
						$this->input_field_text('EXAMPLE_SRC', $example_src,
						strlen($example_src), '', '', true),
						$this->input_field_text('EXAMPLE_DIV', $example_div,
						strlen($example_div), '', '', true)) .
				'</li>
				<li>' . __('Update the ajax request to output the results to that div.', 'litespeed-cache')
				. '<br><br>'
				. __('Example:', 'litespeed-cache') . '<br>
				<textarea name="example_ajax" rows="12" cols="80" readonly>'
				. $example_ajax . '"</textarea><br>'
				. __('The ajax code can be found at', 'litespeed-cache') . '<br>'
				. $this->input_field_text('EXAMPLE_PATH', $example_ajax_path,
						strlen($example_ajax_path), '', '', true)
				. '</li>
				<li>' . __('After purging the cache, the view count should be updating.', 'litespeed-cache') .'</li>
			</ol>';
		$buf .= $this->input_group_start(
									__('Compatibility with WP-PostViews', 'litespeed-cache'), $wp_postviews_desc);
		$buf .= $this->input_group_end();
		return $buf;
	}

	private function show_settings_compatibilities( $options ) {

		$buf = '';

		if (function_exists('the_views')) {
			$buf .= $this->show_wp_postviews_help();
		}
		return $buf;
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

	private function input_field_text( $id, $value, $size = '', $style = '', $after = '', $readonly = false )
	{
		$buf = '<input name="' . LiteSpeed_Cache_Config::OPTION_NAME . '[' . $id . ']" type="text" id="'
				. $id . '" value="' . $value . '"' ;
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

	private function input_field_textarea( $id, $value, $rows = '', $cols = '', $style = '')
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
		$buf .= '>' . $value . '</textarea>';

		return $buf;
	}

}
