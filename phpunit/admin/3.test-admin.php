<?php
/**
 * Class  LiteSpeed_Cache_Admin
 *
 * @package LiteSpeed_Cache_Admin
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-display.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-rules.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin.php';
class LiteSpeed_Cache_Admin_Test extends WP_UnitTestCase {
	/**
	 * Function to invoke a Private method
	 */
	protected static function invokeMethod($className, $methodName, array $parameters = array()) 
    {
    	
         $reflectionClass = new ReflectionClass($className);
         $method = $reflectionClass->getMethod($methodName);
         $method->setAccessible(true);

         if(count($parameters) > 0){
         	$instance = self::get_instance();
            return $method->invokeArgs($instance, $parameters);
         }else{
            return $method;
         }
    }

    /**
	 * Function for configurations
	 */
    public static function get_instance(){
    	$plugin_name = "litespeed-cache";
		$version = "v1.0.14.1";
		$instance = new LiteSpeed_Cache_Admin($plugin_name, $version);

		return $instance;
    }

    /**
	 * Function to check if the passed parameter is String or not.
	 */
    protected static function isString($string) {
    	if(!is_string($string)) return false;
    		
    	return true;
  	}

  	/**
	 * Test case for enqueue style LiteSpeed_Cache_Admin::__construct
	 */
	public function test_construct()
	{
		$instance = self::get_instance();
		
		//send priority by default 10
		$enqueue_scripts = has_action( 'admin_enqueue_scripts', array( $instance,'enqueue_scripts' ) );
		$this->assertEquals( 10, $enqueue_scripts );

		//send priority by default 10
		$enqueue_style = has_action( 'admin_print_styles-settings_page_litespeedcache', array( $instance,'enqueue_style' ) );
		$this->assertEquals( 10, $enqueue_style );

		//should return false as its not in network mode
		$network_admin_menu = has_action( 'network_admin_menu', array( $instance,'register_admin_menu' ) );
		$this->assertFalse($network_admin_menu);

		//send priority by default 10
		$admin_menu = has_action( 'admin_menu', array( $instance,'register_admin_menu' ));
		$this->assertEquals( 10, $admin_menu );

		//send priority by default 10
		$admin_init = has_action( 'admin_init', array( $instance,'admin_init' ));
		$this->assertEquals( 10, $admin_init );

		//send priority by default 10
		$plugin_action_links = has_filter( 'plugin_action_links_root/tests/wp-content/plugins/litespeed-cache/litespeed-cache.php', array( $instance,'add_plugin_links' ));
		$this->assertEquals( 10, $plugin_action_links );
	}

  	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::build_lscwpctrl_url
	 * @param string $val The LSCWP_CTRL action to do in the url.
	 * @param string $nonce The nonce to use.
	 * @return string The built url.
	 */
	public function test_build_lscwpctrl_url()
	{
		$val = '';
		$nounce = '';
		$msg = LiteSpeed_Cache_Admin::build_lscwpctrl_url($val, $nounce);
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::validate_enabled
	 * @access private
	 * @param array $input The input configurations.
	 * @param array $options Returns the up to date options array.
	 * @return boolean True if enabled, false otherwise.
	 */
	public function test_validate_enabled()
	{
		$input = '';
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$parameters = array($input, &$options);
        $bool = self::invokeMethod('LiteSpeed_Cache_Admin','validate_enabled', $parameters);
      	$this->assertFalse($bool);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::validate_tag_prefix
	 * @access private
	 * @param array $input The configurations selected by the admin when
	 *     clicking save.
	 * @param array $options The current configuration options.
	 * @return mixed True on success, error message otherwise.
	 */
	public function test_validate_tag_prefix()
	{
		$input = '';
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$parameters = array($input, &$options);
        $bool = self::invokeMethod('LiteSpeed_Cache_Admin','validate_tag_prefix', $parameters);
      	$this->assertTrue($bool);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::validate_ttl
	 * @access private
	 * @param array $input Input array
	 * @param string $id Option ID
	 * @return bool True if valid, false otherwise.
	 */
	public function test_validate_ttl()
	{
		$input = array();
		$id = '';
		$parameters = array($input, $id);
        $bool = self::invokeMethod('LiteSpeed_Cache_Admin','validate_ttl', $parameters);
      	$this->assertFalse($bool);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::validate_plugin_settings
	 * @access private
	 * @param array $input The configurations selected by the admin when
	 *     clicking save.
	 * @return array The updated configuration options.
	 */
	public function test_validate_plugin_settings()
	{
		$input = '';
		$instance = self::get_instance();
        $array = $instance->validate_plugin_settings($input);
      	$this->assertNotEmpty($array);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::add_plugin_links
	 * @access public
	 * @param array $links Previously added links from other plugins.
	 * @return array Links array with the litespeed cache one appended.
	 */
	public function test_add_plugin_links()
	{
		$links = array();
		$instance = self::get_instance();
        $array = $instance->add_plugin_links($links);
      	$this->assertNotEmpty($array);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::cleanup_text
	 * @access public
	 * @param string $input The input string to clean.
	 * @return string The cleaned up input.
	 */
	public function test_cleanup_text()
	{
		$input = '';
        $msg = LiteSpeed_Cache_Admin::cleanup_text($input);
      	$bool = self::isString($msg);
      	$this->assertTrue($bool);	
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::parse_checkbox
	 * @access public
	 * @param string $id The id of the checkbox value.
	 * @param array $input The input array.
	 * @param array $options The config options array.
	 * @return boolean True if checked, false otherwise.
	 */
	public function test_parse_checkbox()
	{
		$id = '';
		$input = array();
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
        $bool = LiteSpeed_Cache_Admin::parse_checkbox($id, $input, $options);
      	$this->assertFalse($bool);	
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin::add_update_text
	 * @access public
	 * @param string $translations
	 * @param string $text
	 * @return string
	 */
	public function test_add_update_text()
	{
		$translations = '';
		$text = '';
		$instance = self::get_instance();
        $msg = $instance->add_update_text($translations, $text);
      	$bool = self::isString($msg);
      	$this->assertTrue($bool);		
	}
}
