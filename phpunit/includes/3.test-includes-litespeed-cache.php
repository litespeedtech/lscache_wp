<?php
/**
 * Class LiteSpeed_Cache
 *
 * @package LiteSpeed_Cache
 */

/**
 * Class LiteSpeed_Cache test case.
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-display.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-rules.php';
class LiteSpeed_Cache_Test extends WP_UnitTestCase {

    /**
     * Function to invoke a Private method
     */
    protected static function invokeMethod($className, $methodName, array $parameters = array()) 

    {
         $reflectionClass = new ReflectionClass($className);
         $method = $reflectionClass->getMethod($methodName);
         $method->setAccessible(true);

         if(count($parameters) > 0){
            $instance = LiteSpeed_Cache::plugin();
            return $method->invokeArgs($instance, $parameters);
         }else{
            return $method;
         }
    }

    /**
     * Function for configurations
     */
    public static function converttoArray($object){
        
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
     * Test case for  LiteSpeed_Cache::config
     * @param string $opt_id an option ID if getting an option
     * @return LiteSpeed_Cache_Config the configurations for the accessed page
     */
    public function test_config()
    {
        $opt_id = '';
        $array = LiteSpeed_Cache::config($opt_id);
        $this->assertNotEmpty($array);       
    }

    /**
     * Test case for  LiteSpeed_Cache::format_message
     * @param string $mesg the log message to write
     * @return string the formatted log message
     */
    public function test_format_message()
    {
        $msg = '';
        $parameters = array($msg);
        $string = self::invokeMethod('LiteSpeed_Cache', 'format_message', $parameters);
        $bool = self::isString($string);

        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }
    }

    /**
     * Test case for  LiteSpeed_Cache::get_network_ids 
     * Should be test in multiside mode
     * @param array $args arguments to pass into get_sites/wp_get_sites
     * @return array the array of blog ids
     */
    /*public function test_get_network_ids()
    {
        $args = array();
        $parameters = array($args);
        $array = self::invokeMethod('LiteSpeed_Cache', 'get_network_ids', $parameters);
        if( count($array) > 0 ){
            $this->assertNotEmpty($array);
        }
        else
        {
            $this->assertEmpty($array);
        }
    }*/

    /**
     * Test case for  LiteSpeed_Cache::get_network_count
     * @return mixed the count on success, false on failure
     */
    public function test_get_network_count()
    {
        $bool = self::invokeMethod('LiteSpeed_Cache', 'get_network_count');
        if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }
    }

    /**
     * Test case for  LiteSpeed_Cache::is_deactivate_last
     * @return bool true if yes, false otherwise
     */
    public function test_is_deactivate_last()
    {
        $bool = self::invokeMethod('LiteSpeed_Cache', 'is_deactivate_last');
        if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }
    }

    /**
     * Test case for  LiteSpeed_Cache::get_config
     * @return LiteSpeed_Cache_Config the configurations for the accessed page
     */
    public function test_get_config()
    {
        $array = self::invokeMethod('LiteSpeed_Cache', 'get_config');
        $this->assertNotEmpty($array);       
    }

    /**
     * Test case for  LiteSpeed_Cache::try_copy_advanced_cache
     * @return bool true on success, false on failure
     */
    public function test_try_copy_advanced_cache()
    {
        $bool = self::invokeMethod('LiteSpeed_Cache', 'try_copy_advanced_cache');
         if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::setup_cookies
     * @return bool true if cookies are bad, false otherwise
     */
    public function test_setup_cookies()
    {
        $bool = self::invokeMethod('LiteSpeed_Cache', 'setup_cookies');
         if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::check_user_logged_in
     * @return bool true if logged in, false otherwise
     */
    public function test_check_user_logged_in()
    {
        $bool = self::invokeMethod('LiteSpeed_Cache', 'check_user_logged_in');
         if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::check_cookies
     * @return bool True if do not cache for commenters and user is a commenter. False otherwise.
     */
    public function test_check_cookies()
    {
        $bool = self::invokeMethod('LiteSpeed_Cache', 'check_cookies');
         if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::is_uri_excluded
     * @param array $excludes_list List of excluded URIs
     * @return bool true if excluded, false otherwise
     */
    public function test_is_uri_excluded()
    {
        $excludes_list = array();
        $parameters = array($excludes_list);   
        $bool = self::invokeMethod('LiteSpeed_Cache', 'is_uri_excluded', $parameters);
         if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::is_cacheable
     * @return bool true if cacheable, false otherwise
     */
    public function test_is_cacheable()
    {
        $bool = self::invokeMethod('LiteSpeed_Cache', 'is_cacheable');
         if($bool){
            $this->assertTrue(TRUE);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::check_error_codes
     * @param $header, $code
     * @return $eeror_status
     */
    public function test_check_error_codes()
    {
        $OPID_403_TTL = '403_ttl';
        $OPID_404_TTL = '404_ttl';
        $OPID_500_TTL = '500_ttl';

        $header = '';
        $code = $OPID_404_TTL;
        $parameters = array($header, $code);
        $msg = self::invokeMethod('LiteSpeed_Cache', 'check_error_codes', $parameters);
        $this->assertEquals( $OPID_404_TTL, $msg);
    }

    /**
     * Test case for  LiteSpeed_Cache::no_cache_for
     * @param string $reason an explanation for why the page is not cacheable
     * @return bool return false
     */
    public function test_no_cache_for()
    {
        $reason = "for test reason";
        $parameters = array($reason);
        $bool = self::invokeMethod('LiteSpeed_Cache', 'no_cache_for', $parameters);
        $this->assertFalse( $bool );
    }

    /**
     * Test case for  LiteSpeed_Cache::build_purge_headers
     * @param bool $stale whether to add header as a stale header or not
     * @return string The purge header
     */
    public function test_build_purge_headers()
    {
        $stale = true;
        $parameters = array($stale);
        $string = self::invokeMethod('LiteSpeed_Cache', 'build_purge_headers', $parameters);
        $bool = self::isString( $string );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::build_vary_headers
     * @global $post
     * @return mixed false if the user has the postpass cookie. Empty string
     * if the post is not password protected. Vary header otherwise.
     */
    public function test_build_vary_headers()
    {
        $string = self::invokeMethod('LiteSpeed_Cache', 'build_vary_headers');
        $bool = self::isString( $string );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::validate_mode
     * @param bool $showhdr whether the show header command was selected
     * @param bool $stale whether to make the purge headers stale
     * @return int the integer corresponding to the selected
     * cache control value
     */
    public function test_validate_mode()
    {
        $showhdr = true;
        $stale = true;
        $parameters = array(&$showhdr, &$stale);
        $int = self::invokeMethod('LiteSpeed_Cache', 'validate_mode', $parameters);
        $this->assertInternalType('int',$int);
    }

    /**
     * Test case for  LiteSpeed_Cache::prefix_apply
     * @staticvar string $prefix The prefix to use for each tag.
     * @param string $tag the tag to prefix
     * @return string the amended tag
     */
    public function test_prefix_apply()
    {
        $tag = 'test-tag';
        $parameters = array($tag);
        $string = self::invokeMethod('LiteSpeed_Cache', 'prefix_apply', $parameters);
        $bool = self::isString( $string );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::get_cache_tags
     * @return array the list of cache tags to set
     */
    public function test_get_cache_tags()
    {
        $array = self::invokeMethod('LiteSpeed_Cache', 'get_cache_tags');
        if(count($array)>0){
            $this->assertNotEmpty($array);
        }
        else
        {
            $this->assertEmpty($array);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::get_purge_tags
     * @param int $post_id the id of the post about to be purged
     * @return array the list of purge tags correlated with the post
     */
    public function test_get_purge_tags()
    {
        $post_id = $this->factory->post->create( array( 'post_title' => 'Test Post' ) );

        $parameters = array($post_id);
        $array = self::invokeMethod('LiteSpeed_Cache', 'get_purge_tags', $parameters);
        if(count($array)>0){
            $this->assertNotEmpty($array);
        }
        else
        {
            $this->assertEmpty($array);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::get_uri_hash
     * @param string $uri the uri to get the hash of
     * @return bool|string false on input error, hash otherwise
     */
    public function test_get_uri_hash()
    {
        
        $uri = '';
        $parameters = array($uri);
        $string = self::invokeMethod('LiteSpeed_Cache', 'get_uri_hash', $parameters);
        $bool = self::isString( $string );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::format_report_section
     * @param string $section_header The section heading
     * @param array $section An array of information to output
     * @return string the created report block
     */
    public function test_format_report_section()
    {
        
        $section_header = '';
        $section = array();
        $parameters = array($section_header, $section);
        $string = self::invokeMethod('LiteSpeed_Cache', 'format_report_section', $parameters);
        $bool = self::isString( $string );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::build_environment_report
     * @param array $server - server variables
     * @param array $options - cms options
     * @param array $extras - cms specific attributes
     * @param array $htaccess_paths - htaccess paths to check
     * @return string the Environment Report buffer
     */
    public function test_build_environment_report()
    {
        
        $server = array();
        $object = LiteSpeed_Cache::config();
        $options = $object->get_options();
        $extras = array();
        $htaccess_paths = array();
        $parameters = array($server, $options, $extras, $htaccess_paths);
        $string = self::invokeMethod('LiteSpeed_Cache', 'build_environment_report', $parameters);
        $bool = self::isString( $string );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::generate_environment_report
     * @param array $server - server variables
     * @param array $options - cms options
     * @param array $extras - cms specific attributes
     * @param array $htaccess_paths - htaccess paths to check
     * @return string the Environment Report buffer
     */
    public function test_generate_environment_report()
    { 
        $object = LiteSpeed_Cache::config();
        $options = $object->get_options();
        $parameters = array($options);
        $string = self::invokeMethod('LiteSpeed_Cache', 'generate_environment_report', $parameters);
        $bool = self::isString( $string );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::esi_admin_bar_render
     * @echo string ESI.
     */
    public function test_esi_admin_bar_render()
    { 
        ob_start();
        $string = self::invokeMethod('LiteSpeed_Cache', 'esi_admin_bar_render');
        $out = ob_get_clean();
        $bool = self::isString( $out );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::check_admin_bar
     * @echo string ESI.
     */
    public function test_check_admin_bar()
    { 
        $instance = LiteSpeed_Cache::plugin();
        //send priority by 1000
        if(method_exists($instance, 'check_admin_bar')){
            $wp_footer = has_action( 'wp_footer', array( $instance,'check_admin_bar' ) );
            $this->assertFalse( $wp_footer );
        }
               
    }

    /**
     * Test case for  LiteSpeed_Cache::check_storefront_cart
     * @echo string StoreFront Cart.
     */
    public function test_check_storefront_cart()
    { 
        ob_start();
        $string = self::invokeMethod('LiteSpeed_Cache', 'check_storefront_cart');
        $out = ob_get_clean();
        $bool = self::isString( $out );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::check_sidebar
     * @echo string check_sidebar.
     */
    public function test_check_sidebar()
    { 
        ob_start();
        $string = self::invokeMethod('LiteSpeed_Cache', 'check_sidebar');
        $out = ob_get_clean();
        $bool = self::isString( $out );
        if($bool){
            $this->assertTrue($bool);
        }
        else
        {
            $this->assertFalse($bool);
        }       
    }

    /**
     * Test case for  LiteSpeed_Cache::add_actions_esi
     * @echo string ESI.
     */
    public function test_add_actions_esi()
    { 
        $instance = LiteSpeed_Cache::plugin();
        //send priority by 1000
        if(method_exists($instance, 'add_actions_esi')){
            $storefront_header = has_action( 'storefront_header', array( $instance,'check_storefront_cart' ) );
            $this->assertFalse( $storefront_header );
            $storefront_sidebar = has_action( 'storefront_sidebar', array( $instance,'check_sidebar' ) );
            $this->assertFalse( $storefront_sidebar );
        }
               
    }

    /**
     * Test case for  LiteSpeed_Cache::is_esi_admin_bar
     * @return bool
     */
    public function test_is_esi_admin_bar()
    { 
        $instance = LiteSpeed_Cache::plugin();
        $uri = '';
        $urilen = '';
        $parameters = array($uri, $urilen);
        //send priority by 1000
        if(method_exists($instance, 'is_esi_admin_bar')){
            $init = has_action( 'init', '_wp_admin_bar_init' );
            $this->assertFalse( $init );
            $init = has_action( 'init', 'wp_admin_bar_render' );
            $this->assertFalse( $init );
            $init = has_action( 'init', array( $instance,'send_esi' ) );
            $this->assertFalse( $init );
            $bool = self::invokeMethod('LiteSpeed_Cache', 'is_esi_admin_bar', $parameters);
            if($bool){
                $this->assertTrue($bool);
            }
            else
            {
                $this->assertFalse($bool);
            }       
        }
               
    }

    /**
     * Test case for  LiteSpeed_Cache::is_esi_cart
     * @return bool
     */
    public function test_is_esi_cart()
    { 
        $instance = LiteSpeed_Cache::plugin();
        $uri = '';
        $urilen = '';
        $parameters = array($uri, $urilen);
        //send priority by 1000
        if(method_exists($instance, 'is_esi_cart')){
            $init = has_action( 'init', 'storefront_cart_link_fragment' );
            $this->assertFalse( $init );
            $init = has_action( 'init','storefront_header_cart' );
            $this->assertFalse( $init );
            $init = has_action( 'init', array( $instance,'send_esi' ) );
            $this->assertFalse( $init );
            $bool = self::invokeMethod('LiteSpeed_Cache', 'is_esi_cart', $parameters);
            if($bool){
                $this->assertTrue($bool);
            }
            else
            {
                $this->assertFalse($bool);
            }       
        }
               
    }

    /**
     * Test case for  LiteSpeed_Cache::is_esi_sidebar
     * @return bool
     */
    public function test_is_esi_sidebar()
    { 
        $instance = LiteSpeed_Cache::plugin();
        $uri = '';
        $urilen = '';
        $parameters = array($uri, $urilen);
        //send priority by 1000
        if(method_exists($instance, 'is_esi_sidebar')){
            $widgets_init = has_action( 'widgets_init', 'storefront_widgets_init' );
            $this->assertFalse( $widgets_init );
            $wp_loaded = has_action( 'wp_loaded', array( $instance,'load_sidebar_widgets' ) );
            $this->assertFalse( $wp_loaded );
            $wp_loaded = has_action( 'wp_loaded', 'storefront_get_sidebar' );
            $this->assertFalse( $wp_loaded );
            $wp_loaded = has_action( 'wp_loaded', array( $instance,'send_esi' ) );
            $this->assertFalse( $wp_loaded );
            $bool = self::invokeMethod('LiteSpeed_Cache', 'is_esi_sidebar', $parameters);
            if($bool){
                $this->assertTrue($bool);
            }
            else
            {
                $this->assertFalse($bool);
            }       
        }
               
    }

    /**
     * Test case for  LiteSpeed_Cache::is_esi_sidebar
     * @return bool
     */
    public function test_check_esi_page()
    { 
         $bool = self::invokeMethod('LiteSpeed_Cache', 'check_esi_page');
            if($bool){
                $this->assertTrue(TRUE);
            }
            else
            {
                $this->assertFalse($bool);
            }       
    }
}
