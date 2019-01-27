<?php
/**
 * Class  LiteSpeed_Cache_Admin_Display_Test
 *
 * @package LiteSpeed_Cache_Admin_Display
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-display.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-rules.php';
class LiteSpeed_Cache_Admin_Display_Test extends WP_UnitTestCase {

	/**
	 * Function to invoke a Private method
	 */
	protected static function invokeMethod($className, $methodName, array $parameters = array()) 

    {
         $reflectionClass = new ReflectionClass($className);
         $method = $reflectionClass->getMethod($methodName);
         $method->setAccessible(true);

         if(count($parameters) > 0){
         	$instance = LiteSpeed_Cache_Admin_Display::get_instance();
            return $method->invokeArgs($instance, $parameters);
         }else{
            return $method;
         }
    }

    /**
     * Function to check if the passed parameter is String or not.
     */
    protected static function isString($string) {
    	if(!is_string($string)) return false;
    		
    	return true;
  	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Display::admin_footer_text
	 * @param  string $footer_text
	 * @return string
	 */
	public function test_admin_footer_text()
	{
		$footer_text = '';
		$msg = LiteSpeed_Cache_Admin_Display::get_instance()->admin_footer_text($footer_text);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
	}

	/**
	 * Test case for Whether to disable all settings or not LiteSpeed_Cache_Admin_Display::get_disable_all
	 *
	 *  bool True to disable all settings, false otherwise.
	 */
	public function test_get_disable_all()
	{
		$bool = LiteSpeed_Cache_Admin_Display::get_instance()->get_disable_all();
		$this->assertFalse($bool);
	}

	/**
	 * Test case to check Set to disable all settings LiteSpeed_Cache_Admin_Display::set_disable_all
	 */
	public function test_set_disable_all()
	{
		$null = LiteSpeed_Cache_Admin_Display::get_instance()->set_disable_all();
		$this->assertNull($null);
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::check_license
	 * @return String
	 */
	public function test_check_license()
	{
		$config = LiteSpeed_Cache::config();
		$msg = LiteSpeed_Cache_Admin_Display::get_instance()->check_license($config);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_paragraph
	 * @return String
	 */
	public function test_build_paragraph()
	{
		
        $msg = LiteSpeed_Cache_Admin_Display::get_instance()->build_paragraph();
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_list
	 * @return String
	 */
	public function test_build_list()
	{
		$items = array('Item1', 'Item 2');
        $msg = LiteSpeed_Cache_Admin_Display::get_instance()->build_list($items, true, '');
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_notice
	 * @return String
	 */
	public function test_build_notice()
	{
		$parameters = array('Yellow','Hi this is a test message');
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_notice', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_tip
	 * @return String
	 */
	public function test_build_tip()
	{
		$parameters = array('This is tooltip message','','','');
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_tip', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_button
	 * @return String
	 */
	public function test_build_button()
	{
		$parameters = array('','Button Text','','');
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_button', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_expand_collapse
	 * @return String
	 */
	public function test_build_expand_collapse()
	{
		$parameters = array(true);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_expand_collapse', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::add_notice
	 * try Catch as nothing is returned..
	 */
	public function test_add_notice()
	{
        $msg = LiteSpeed_Cache_Admin_Display::get_instance()->add_notice('Blue','Add Notice Message');
		try {
      		self::isString($msg);
   		 } 
   		 catch (InvalidArgumentException $notExpected) 
   		 {
     	 	$this->fail();
    	 }

   		 $this->assertTrue(TRUE);		
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::display_notices
	 * @return String
	 */
	public function test_display_notices()
	{
		ob_start();
        $msg = LiteSpeed_Cache_Admin_Display::get_instance()->display_notices();
		$out = ob_get_clean();
		$bool = self::isString($out);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_menu_select
	 */
	public function test_show_menu_select()
	{

        $msg = LiteSpeed_Cache_Admin_Display::get_instance()->show_menu_select();
		try {
      		self::isString($msg);
   		 } 
   		 catch (InvalidArgumentException $notExpected) 
   		 {
     	 	$this->fail();
    	 }

   		 $this->assertTrue(TRUE);			
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_menu_manage
	 * @return String
	 */
	public function test_show_menu_manage()
	{
		ob_start();
        $msg = LiteSpeed_Cache_Admin_Display::get_instance()->show_menu_manage();
		$out = ob_get_clean();
      	$bool = self::isString($out);
      	$this->assertTrue($bool);				
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_menu_settings
	 * @return String
	 */
	public function test_show_menu_settings()
	{
		ob_start();
        $msg = LiteSpeed_Cache_Admin_Display::get_instance()->show_menu_settings();
        $out = ob_get_clean();
		$bool = self::isString($out);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_menu_network_settings
	 * @return String
	 */
	public function test_show_menu_network_settings()
	{
		ob_start();
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_menu_network_settings');
		$out = ob_get_clean();
		$bool = self::isString($out);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_menu_edit_htaccess
	 * @return String
	 */
	public function test_show_menu_edit_htaccess()
	{
		ob_start();
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_menu_edit_htaccess');
		$out = ob_get_clean();
		$bool = self::isString($out);
      	$this->assertTrue($bool);
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_menu_info
	 * @return String
	 */
	public function test_show_menu_info()
	{
		ob_start();
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_menu_info');
		$out = ob_get_clean();
		$bool = self::isString($out);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_menu_report
	 * @return String
	 */
	public function test_show_menu_report()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_menu_report');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_settings_general
	 * @return String
	 */
	public function test_show_settings_general()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_settings_general');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}


	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_settings_specific
	 * @return String
	 */
	public function test_show_settings_specific()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_settings_specific');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_settings_purge
	 * @return String
	 */
	public function test_show_settings_purge()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_settings_purge');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_settings_excludes
	 * @return String
	 */
	public function test_show_settings_excludes()
	{
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$parameters = array($options);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_settings_excludes', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_settings_advanced
	 * @return String
	 */
	public function test_show_settings_advanced()
	{
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$parameters = array($options);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_settings_advanced', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_settings_test
	 * @return String
	 */
	public function test_show_settings_test()
	{
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$parameters = array($options);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_settings_test', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_settings_compatibilities
	 * @return String
	 */
	public function test_show_settings_compatibilities()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_settings_compatibilities');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}


	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_setting_mobile_view
	 * @return String
	 */
	public function test_build_setting_mobile_view()
	{

		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$parameters = array($options);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_setting_mobile_view', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_setting_exclude_cookies
	 * @param array $options the currently configured options
	 * @param string $cookie_title returns the cookie title string
	 * @param string $cookie_desc returns the cookie description string
	 * @return string Returns the cookie text area on success, error message on
	 */
	public function test_build_setting_exclude_cookies()
	{
		$object = LiteSpeed_Cache::config();
		$cookie_title = '';
		$cookie_desc = '';
		$options = $object->get_options();
		$parameters = array($options, &$cookie_title, &$cookie_desc, false);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_setting_mobile_view', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_setting_exclude_useragent
	 * @param array $options the currently configured options
	 * @param string $ua_title returns the user agent title string
	 * @param string $ua_desc returns the user agent description string
	 * @return string Returns the user agent text field on success,
	 */
	public function test_build_setting_exclude_useragent()
	{
		$object = LiteSpeed_Cache::config();
		$ua_title = '';
		$ua_desc = '';
		$options = $object->get_options();
		$parameters = array($options, &$ua_title, &$ua_desc);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_setting_exclude_useragent', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_setting_login_cookie
	 * @param array $options the currently configured options
	 * @param string $cookie_title returns the cookie title string
	 * @param string $cookie_desc returns the cookie description string
	 * @return string Returns the cookie text field on success,
	 */
	public function test_build_setting_login_cookie()
	{
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$cookie_title = '';
		$cookie_desc = '';
		$parameters = array($options, &$cookie_title, &$cookie_desc);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_setting_login_cookie', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_setting_purge_on_upgrade
	 * @param array $options the currently configured options
	 * @return string the html for caching favicon configurations
	 */
	public function test_build_setting_purge_on_upgrade()
	{
		$object = LiteSpeed_Cache::config();
		$parameters = $object->get_options();
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_setting_purge_on_upgrade', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_setting_cache_favicon
	 * @param array $options the currently configured options
	 * @return string the html for caching favicon configurations
	 */
	public function test_build_setting_cache_favicon()
	{
		$object = LiteSpeed_Cache::config();
		$parameters = $object->get_options();
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_setting_cache_favicon', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::build_compatibility_wp_postviews
	 * @return String
	 */
	public function test_build_compatibility_wp_postviews()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','build_compatibility_wp_postviews');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_info_compatibility
	 * @return String
	 */
	public function test_show_info_compatibility()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_info_compatibility');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_info_admin_ip
	 * @return String
	 */
	public function test_show_info_admin_ip()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_info_admin_ip');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_info_common_rewrite
	 * @return String
	 */
	public function test_show_info_common_rewrite()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_info_common_rewrite');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_info_faqs
	 * @return String
	 */
	public function test_show_info_faqs()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_info_faqs');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_display_installed
	 * @return String
	 */
	public function test_show_display_installed()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_display_installed');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::show_error_cookie
	 * @return String
	 */
	public function test_show_error_cookie()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','show_error_cookie');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_group_start
	 * @param string $title the title of the configuration group
	 * @param string $description the description of the configuration group
	 * @return string the start configuration option table html
	 */
	public function test_input_group_start()
	{
		$title = "test-title";
		$description = "test-desc";
		$parameters = array($title, $description);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_group_start', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_group_end
	 * @return String
	 */
	public function test_input_group_end()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_group_end');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::display_config_row
	 * @param string $label the option name
	 * @param string $input_field the option html
	 * @param string $notes the description to display under the option html
	 * @return string the config row html
	 */
	public function test_display_config_row()
	{
		$label = '';
		$input_field = '';
		$notes = '';
		$parameters = array($label, $input_field, $notes);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','display_config_row', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}


	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_field_checkbox
	 * @param string $id the option ID for the field
	 * @param string $value the value for the field
	 * @param mixed $checked_value the current value
	 * @param string $label the label to display
	 * @param string $on_click the action to do on click
	 * @param boolean $disabled true for disabled check box, false otherwise
	 * @return string the check box html
	 */
	public function test_input_field_checkbox()
	{
		$id = '';
		$checked_value = '';
		$label = '';
		$on_click = '';
		$disabled = true;
		$parameters = array($id, $checked_value, $label, $on_click, $disabled);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_field_checkbox', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_field_radio
	 * @param string $id the option ID for the field
	 * @param array $radiooptions the options available for selection
	 * @param string $checked_value the currently selected option
	 * @return string the select field html
	 */
	public function test_input_field_radio()
	{
		$id = '';
		$radiooptions = array();
		$checked_value = '';

		$parameters = array($id, $radiooptions, $checked_value);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_field_radio', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_field_select
	 * @param string $id the option ID for the field
	 * @param array $seloptions the options available for selection
	 * @param string $selected_value the currently selected option
	 * @return string the select field html
	 */
	public function test_input_field_select()
	{
		$id = '';
		$seloptions = array();
		$selected_value = '';
		$parameters = array($id, $seloptions, $selected_value);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_field_select', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_field_text
	 * @param string $id the option ID for the field
	 * @param string $value the value for the field
	 * @param string $size the length to display
	 * @param string $style the class to format the display
	 * @param string $after the units to display after the text field
	 * @param boolean $readonly true for read only text fields, false otherwise
	 * @return string the input text html
	 */
	public function test_input_field_text()
	{
		$id = '';
		$value = '';
		$size = '';
		$style = '';
		$after = '';
		$readonly = true;
		$parameters = array($id, $value, $size, $style, $after, $readonly);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_field_text', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_field_textarea
	 * @param string $id the option ID for the field
	 * @param string $value the value for the field
	 * @param string $rows number of rows to display
	 * @param string $cols number of columns to display
	 * @param string $style the class to format the display
	 * @param boolean $readonly true for read only text areas, false otherwise
	 * @return string the textarea html
	 */
	public function test_input_field_textarea()
	{
		$id = '';
		$value = '';
		$rows = '';
		$cols = '';
		$style = '';
		$readonly = true;
		$parameters = array($id, $value, $rows, $cols, $style, $readonly);
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_field_textarea', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_field_hidden
	 * @param string $id the option ID for the field
	 * @param string $value the value for the field
	 * @return string the hidden field html
	 */
	public function test_input_field_hidden()
	{
		$id = '';
		$value = '';
		$parameters = array();
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_field_hidden', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_collapsible_start
	 * @return String
	 */
	public function test_input_collapsible_start()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_collapsible_start');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_collapsible_end
	 * @return String
	 */
	public function test_input_collapsible_end()
	{
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_collapsible_end');
		$bool = self::isString($msg);
      	$this->assertFalse($bool);		
    	
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Display::input_field_collapsible
	 * @return String
	 */
	public function test_input_field_collapsible()
	{
		$parameters = array('header'=>'','desc'=>'', 'example'=>'');
        $msg = self::invokeMethod('LiteSpeed_Cache_Admin_Display','input_field_collapsible', $parameters);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
    	
	}
}
