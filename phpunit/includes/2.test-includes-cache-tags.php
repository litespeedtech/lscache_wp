<?php
/**
 * Class LiteSpeed_Cache_Tags.
 *
 * @package LiteSpeed_Cache_Tags
 */

/**
 * Class LiteSpeed_Cache_Config test case.
 */
class LiteSpeed_Cache_Tags_Test extends WP_UnitTestCase {


    /**
     * Function for instance.
     */
    public static function get_instance(){
		$instance = new LiteSpeed_Cache_Tags();
		return $instance;
    }


	/**
	 * test case for LiteSpeed_Cache_Config::get_cache_tags.
	 * @return array the updated options
	 */
	public function test_get_cache_tags() 
	{
        $instance  = self::get_instance();
        $tags = array();

        if(method_exists($instance, 'add_cache_tag')){
            $instance->add_cache_tag($tags);
        }
		$array = $instance->get_cache_tags();

        if(count($array) > 0){
		  $this->assertNotEmpty($array);
        }
        else
        {
          $this->assertEmpty($array);
        }
	}

    /**
     * test case for LiteSpeed_Cache_Config::get_purge_tags.
     * @param mixed $tag a string or array of cache tags to add to the current list
     */
    public function test_get_purge_tags() 
    {
        $instance  = self::get_instance();
        $tags = array();

        if(method_exists($instance, 'add_purge_tag')){
            $instance->add_purge_tag($tags);
        }
        $array = $instance->get_purge_tags();

        if(count($array) > 0){
          $this->assertNotEmpty($array);
        }
        else
        {
          $this->assertEmpty($array);
        }
    }

    /**
     * test case for LiteSpeed_Cache_Config::get_vary_cookies.
     * @param mixed $tag a string or array of cache tags to add to the current list
     */
    public function test_get_vary_cookies() 
    {
        $instance  = self::get_instance();
        $cookie = array();

        if(method_exists($instance, 'add_vary_cookie')){
            $instance->add_vary_cookie($cookie);
        }
        $array = $instance->get_vary_cookies();

        if(count($array) > 0){
          $this->assertNotEmpty($array);
        }
        else
        {
          $this->assertEmpty($array);
        }
    }

    /**
     * test case for LiteSpeed_Cache_Config::is_noncacheable.
     * @return bool true if the current page was deemed non-cacheable,
     * false otherwise
     */
    public function test_is_noncacheable() 
    {
        $instance  = self::get_instance();

        if(method_exists($instance, 'set_noncacheable')){
            $instance->set_noncacheable();
        }
        $bool = $instance->is_noncacheable();

        if($bool){
          $this->assertTrue($bool);
        }
        else
        {
          $this->assertFalse($bool);
        }
    }

    /**
     * test case for LiteSpeed_Cache_Config::is_mobile.
     * @return bool true if the current page was deemed mobile,
     * false otherwise
     */
    public function test_is_mobile() 
    {
        $instance  = self::get_instance();

        if(method_exists($instance, 'set_mobile')){
            $instance->set_mobile();
        }
        $bool = $instance->is_mobile();

        if($bool){
          $this->assertTrue($bool);
        }
        else
        {
          $this->assertFalse($bool);
        }
    }

    /**
     * test case for LiteSpeed_Cache_Config::get_use_frontpage_ttl.
     * @return bool true if use front page TTL, false otherwise
     */
    public function test_get_use_frontpage_ttl() 
    {
        $instance  = self::get_instance();

        if(method_exists($instance, 'set_use_frontpage_ttl')){
            $instance->set_use_frontpage_ttl();
        }
        $bool = $instance->get_use_frontpage_ttl();

        if($bool){
          $this->assertTrue($bool);
        }
        else
        {
          $this->assertFalse($bool);
        }
    }

}
