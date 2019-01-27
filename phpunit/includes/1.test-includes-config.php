<?php
/**
 * Class LiteSpeed_Cache_Config
 *
 * @package LiteSpeed_Cache_Config
 */

/**
 * Class LiteSpeed_Cache_Config test case.
 */
class LiteSpeed_Cache_Config_Test extends WP_UnitTestCase {

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
	 * Function for instance
	 */
    public static function get_instance() {
		$instance = new LiteSpeed_Cache_Config();
		return $instance;
    }

    /**
	 * Function for configurations
	 */
    public static function converttoArray($object) {
    	
    	if(is_object($object)) $array = (array) $object;
    	return $array;
    }

    /**
	 * Function to check if the passed parameter is String or not.
	 */
    protected static function isString($string) {
    	if(!is_string($string)) return false;
    		
    	return true;
  	}

  	/**
	 * Test case for enqueue style LiteSpeed_Cache_Config::__construct
	 */
	public function test_construct()
	{
		$instance = self::get_instance();
		$this->assertTrue( TRUE );
	}

	/**
	 * test case for LiteSpeed_Cache_Config::construct_multisite_options
	 * @access private
	 * @return array The updated options.
	 */
	public function test_construct_multisite_options()
	{
		$array = self::invokeMethod('LiteSpeed_Cache_Config','construct_multisite_options');
		$this->assertNotEmpty($array);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::get_options
	 * @access public
	 * @return array The updated options.
	 */
	public function test_get_options() 
	{
		$array = self::get_instance()->get_options();
		$this->assertNotEmpty($array);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::get_option
	 * @access public
	 * @param string $id Configuration ID.
	 * @return mixed Selected option if set, NULL if not.
	 */
	public function test_get_option() 
	{
		$id = '';
		$null = self::get_instance()->get_option($id);
		$this->assertNull($null);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::get_purge_options
	 * @access public
	 * @return array The list of purge options.
	 */
	public function test_get_purge_options() 
	{
		$array = self::get_instance()->get_purge_options();
		$this->assertNotEmpty($array);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::purge_by_post
	 * @access public
	 * @param string $flag Post type. Refer to LiteSpeed_Cache_Config::PURGE_*
	 * @return boolean True if the post type should be purged, false otherwise.
	 */
	public function test_purge_by_post() 
	{
		$flag = '';
		$bool = self::get_instance()->purge_by_post($flag);
		$this->assertFalse($bool);
	}


	/**
	 * test case for LiteSpeed_Cache_Config::get_default_options
	 * @access protected
	 * @param bool $include_thirdparty Whether to include the thirdparty options.
	 * @return array An array of the default options.
	 */
	public function test_get_default_options()
	{
		$include_thirdparty = true;
		$parameters = array($include_thirdparty);
		$array = self::invokeMethod('LiteSpeed_Cache_Config','get_default_options', $parameters);
		$this->assertNotEmpty($array);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::get_default_site_options
	 * @access protected
	 * @return array An array of the default options.
	 */
	public function test_get_default_site_options()
	{
		$array = self::invokeMethod('LiteSpeed_Cache_Config','get_default_site_options');
		$this->assertNotEmpty($array);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::get_rule_reset_options
	 * @access public
	 * @return array The list of options to reset.
	 */
	public function test_get_rule_reset_options()
	{
		$array = self::get_instance()->get_rule_reset_options();
		$this->assertNotEmpty($array);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::get_site_options
	 * @access public
	  * @return array Returns the current site options.
	 */
	public function test_get_site_options()
	{
		$array = self::get_instance()->get_site_options();
		if (!is_multisite()) {
			$this->assertNull($array);
		}
		else
		{
			$this->assertNotEmpty($array);
		}
	}

	/**
	 * test case for LiteSpeed_Cache_Config::get_thirdparty_options
	 * @param array $options Optional. The default options to compare against.
	 * @return mixed boolean on failure, array of keys on success.
	 */
	public function test_get_thirdparty_options()
	{
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$array = self::get_instance()->get_thirdparty_options($options);
		if(is_array($array)){
			$this->assertNotEmpty($array);
		}
		else
		{
			$this->assertFalse($array);
		}
	}


	/**
	 * test case for LiteSpeed_Cache_Config::wp_cache_var_setter
	 * @access public
	 * @param boolean $enable True if enabling, false if disabling.
	 * @return boolean True if the variable is the correct value, false if something went wrong.
	 */
	public function test_wp_cache_var_setter()
	{
		$enable = true;
		$bool = self::get_instance()->wp_cache_var_setter($enable);
		$this->assertFalse($bool);
	}

	/**
	 * test case for LiteSpeed_Cache_Config::is_caching_allowed
	 * @access public
	 * @return boolean True if enabled, false otherwise.
	 */
	public function test_is_caching_allowed()
	{
		$bool = self::get_instance()->is_caching_allowed();
		$this->assertFalse($bool);
	}
}
