<?php
/**
 * Class  LiteSpeed_Cache_Admin_Rules_Test
 *
 * @package LiteSpeed_Cache_Admin_Rules
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-display.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin-rules.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . '/admin/class-litespeed-cache-admin.php';
class LiteSpeed_Cache_Admin_Rules_Test extends WP_UnitTestCase {
	/**
	 * Function to invoke a Private method
	 */
	protected static function invokeMethod($className, $methodName, array $parameters = array()) 

    {
         $reflectionClass = new ReflectionClass($className);
         $method = $reflectionClass->getMethod($methodName);
         $method->setAccessible(true);

         if(count($parameters) > 0){
         	$instance = LiteSpeed_Cache_Admin_Rules::get_instance();

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
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::get_home_path
	 * @return string
	 */
	public function test_get_home_path()
	{
		$msg = LiteSpeed_Cache_Admin_Rules::get_instance()->get_home_path();
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::get_site_path
	 * @return string
	 */
	public function test_get_site_path()
	{
		$msg = LiteSpeed_Cache_Admin_Rules::get_instance()->get_site_path();
		$bool = self::isString($msg);
      	$this->assertTrue($bool);		
	}

	/**
	 * test case for LiteSpeed_Cache_Admin_Rules::is_subdir
	 * @return Boolean
	 */
	/*public function test_is_subdir()
	{
        $bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','is_subdir');
      	$this->assertTrue($bool);		
    	
	}*/

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::is_file_able
	 * @return string
	 */
	public function test_is_file_able()
	{
		$path = '/root/tests/wp-content/plugins/';
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->is_file_able(3, $path);
      	$this->assertTrue($bool);		
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::build_wrappers
	 * @param string $wrapper The common rule wrapper.
	 * @param string $end Returns the ending wrapper.
	 * @return string Returns the opening wrapper.
	 */
	public function test_build_wrappers()
	{
		$wrapper = 'Test Wrapper';
		$end = '>';
		$parameters = array($wrapper, &$end);
		$msg = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','build_wrappers', $parameters);
      	$bool = self::isString($msg);
      	$this->assertTrue($bool);				
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::path_search
	 * @param string $stop_path The last directory level to search.
	 * @param string $start_path The first directory level to search.
	 * @param string $file The file to search for.
	 * @return string The deepest path where the file exists,
	 */
	public function test_path_search()
	{
		$stop_path = '';
		$start_path = '';
		$file = '';
		$parameters = array($stop_path, $start_path, $file);
		$msg = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','path_search', $parameters);
      	$bool = self::isString($msg);
      	$this->assertTrue($bool);				
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::path_set
	 * @return null,
	 */
	public function test_path_set()
	{
		
		$null = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','path_set');
      	$this->assertNotNull($null);				
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::file_get
	 * @param string $content Returns the content of the file or an error description.
	 * @param string $path The path to get the content from.
	 * @return boolean True if succeeded, false otherwise.
	 */
	public function test_file_get()
	{
		$content = '';
		$path = '';
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->file_get( $content, $path);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::file_get_ifmodule_block
	 * @param string $content The content to search.
	 * @param int $off_begin Will be set to the beginning offset. Starts
	 * just after the opening <IfModule>.
	 * @param int $off_end Will be set to the ending offset. Starts just
	 * before the closing </IfModule>.
	 * @return bool|string False if not found, True if found. Error message if
	 * it failed.
	 */
	public function test_file_get_ifmodule_block()
	{
		$content = '';
		$off_begin = '';
		$off_end = '';
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->file_get_ifmodule_block( $content, $off_begin, $off_end);
      	$this->assertNotNull($bool);						
	}	

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::file_split
	 * @param string $content The content to search
	 * @param string $buf The portion before and including the beginning of
	 * the section.
	 * @param string $after The content after the relevant section.
	 * @return mixed False on failure, the haystack on success.
	 */
	public function test_file_split()
	{
		$content = '';
		$buf = '';
		$after = '';
		$parameters = array($content, &$buf, &$after);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','file_split', $parameters);
      	$this->assertNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::file_combine
	 * @param string $beginning The portion that includes the edits.
	 * @param string $haystack The source section from the original file.
	 * @param string $after The content after the relevant section.
	 * @param string $path If path is set, use path, else use home path.
	 * @return mixed true on success, else error message on failure.
	 */
	public function test_file_combine()
	{
		$beginning = '';
		$haystack = '';
		$after = '';
		$path = '';
		$parameters = array($beginning, $haystack, $after, $path);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','file_combine', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::file_backup
 	 * @param String $path The .htaccess file path.
	 * @return boolean True on success, else false on failure.
	 */
	public function h_test_file_backup()
	{
		$path = '/root/tests/readme.html';
		$parameters = array($path);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','file_backup', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::file_save
	 * @param string $content The new content to put into the rules file.
	 * @param boolean $cleanup True to strip extra slashes, false otherwise.
	 * @param string $path The file path to edit.
	 * @param boolean $backup Whether to create backups or not.
	 * @return mixed true on success, else error message on failure.
	 */
	public function test_file_save()
	{
		$content = "Some sample content";
		$cleanup = false;
		$path = '/root/tests/readme.html';
		$backup = false;
		$parameters = array($content, $cleanup, $path, $backup);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','file_save', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::set_common_rule
	 * @param string $content The original content in the .htaccess file.
	 * @param string $output Returns the added rule if success.
	 * @param string $wrapper The wrapper that surrounds the rule.
	 * @param string $cond The rewrite condition to use with the rule.
	 * @param string $match The rewrite rule to match against the condition.
	 * @param string $env The environment change to do if the rule matches.
	 * @param string $flag The flags to use with the rewrite condition.
	 * @return mixed Explained above.
	 */
	public function test_set_common_rule()
	{
		$content = "Some sample content";
		$output = '';
		$wrapper = '';
		$cond = '';
		$match = '';
		$env = '';
		$flag = '';
		$parameters = array($content, &$output, $wrapper, $cond, $match, $env, $flag);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','set_common_rule', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::get_common_rule
	 * @param string $wrapper The wrapper to look for.
	 * @param string $cond The condition to look for.
	 * @param string $match Returns the rewrite rule on success, error message on failure.
	 * @return boolean True on success, false otherwise.
	 */
	public function test_get_common_rule()
	{
		$wrapper = "Some sample content";
		$cond = '';
		$match = '';
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->get_common_rule( $wrapper, $cond, $match);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::set_rewrite_rule
	 * @param string $content The original content in the .htaccess file.
	 * @param string $output Returns the added rule if success.
	 * @param string $wrapper The wrapper that surrounds the rule.
	 * @param string $match The rewrite rule to match against.
	 * @param string $sub The substitute for the rule match.
	 * @param string $env The environment change to do if the rule matches.
	 * @return mixed Explained above.
	 */
	public function test_set_rewrite_rule()
	{
		$content = "Some sample content";
		$output = '';
		$wrapper = '';
		$match = '';
		$sub = '';
		$env = '';
		$parameters = array($content, &$output, $wrapper, $match, $sub, $env);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','set_rewrite_rule', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::get_rewrite_rule
	 * @param string $wrapper The wrapper to look for.
	 * @param string $match Returns the rewrite rule on success, error message on failure.
	 * @param string $sub Returns the substitute on success, error message on failure.
	 * @param string $env Returns the environment on success, error message on failure.
	 * @return boolean True on success, false otherwise.
	 */
	public function test_get_rewrite_rule()
	{
		$wrapper = "Some sample content";
		$match = '';
		$sub = '';
		$env = '';
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->get_rewrite_rule( $wrapper, $match, $sub, $env);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::set_favicon
	 * @param string $haystack The original content in the .htaccess file.
	 * @param boolean $action Whether to add or remove the rule.
	 * @param string $output The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans favicon on success.
	 */
	public function test_set_favicon()
	{
		$haystack = '';
		$action = true;
		$output = '';
		$errors = '';
		$parameters = array($haystack, $action, &$output, &$errors);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','set_favicon', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::set_cache_resource
	 * @param string $haystack The original content in the .htaccess file.
	 * @param boolean $set Whether to add or remove the rule.
	 * @param string $output The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans favicon on success.
	 */
	public function test_set_cache_resource()
	{
		$haystack = '';
		$set = true;
		$output = '';
		$errors = '';
		$parameters = array($haystack, $set, &$output, &$errors);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','set_cache_resource', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::check_input
	 * @param array $options The current options
	 * @param array $input The input
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False if there is an error, diff array otherwise.
	 */
	public function test_check_input()
	{
		$object = LiteSpeed_Cache::config();
		$options = $object->get_options();
		$input = array();
		$errors = array();
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->check_input( $options, $input, $errors);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::check_rewrite
	 * @param string $haystack The original content in the .htaccess file.
	 * @param boolean $set Whether to add or remove the rule.
	 * @param string $output The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans favicon on success.
	 */
	public function test_check_rewrite()
	{
		$rules = '';
		$parameters = array($rules);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','check_rewrite', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::parse_ret
	 * @param mixed $ret The return value from the called function.
	 * @param string $haystack Where to start the next search.
	 * @param string $errors Errors array in case of error.
	 * @return boolean False on function failure, true otherwise.
	 */
	public function test_parse_ret()
	{
		$ret = '';
		$haystack = '';
		$errors = '';
		$parameters = array($ret, &$haystack, &$errors);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','parse_ret', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::set_subdir_cookie
	 * @param array $diff The rules that need to be set
	 * @param string $haystack The original content in the .htaccess file.
	 * @param string $buf The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans login cookie on success.
	 */
	public function test_set_subdir_cookie()
	{
		$diff = '';
		$haystack = '';
		$buf = '';
		$errors = '';
		$parameters = array($diff, &$haystack, &$buf, &$errors);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','set_subdir_cookie', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::set_on_upgrade
	 * @param string $wrapper A wrapper to a specific rule
	 * @param string $match The match to set the rewrite rule to search for.
	 * @param string $sub The substitution to set the rewrite rule to replace with.
	 * @param string $flag The flag the rewrite rule should set.
	 * @param string $content The original content/new content after replacement.
	 */
	public function test_set_on_upgrade()
	{
		$wrapper = '';
		$match = '';
		$sub = '';
		$flag = '';
		$content = '';
		$parameters = array($wrapper, $match, $sub, $flag, &$content);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','set_on_upgrade', $parameters);
      	$this->assertNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::parse_existing_login_cookie
	 * @param string $wrapper A wrapper to a specific rule
	 * @param string $match The match to set the rewrite rule to search for.
	 * @param string $sub The substitution to set the rewrite rule to replace with.
	 * @param string $flag The flag the rewrite rule should set.
	 * @param string $content The original content/new content after replacement.
	 */
	public function test_parse_existing_login_cookie()
	{
		$content = '';
		$parameters = array(&$content);
		$bool = self::invokeMethod('LiteSpeed_Cache_Admin_Rules','parse_existing_login_cookie', $parameters);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::scan_upgrade
	 * @return string The login cookie if found, empty string otherwise.
	 */
	public function test_scan_upgrade()
	{
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->scan_upgrade();
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::validate_common_rewrites
	 * @param array $diff The rules that need to be set.
	 * @param array $errors Returns error messages added if failed.
	 * @return mixed Returns updated options array on success, false otherwise.
	 */
	public function test_validate_common_rewrites()
	{
		$diff = '';
		$errors = '';
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->validate_common_rewrites($diff, $errors);
      	$this->assertNotNull($bool);						
	}

	/**
	 * Test case for Change the admin footer text on LiteSpeed Cache admin pages LiteSpeed_Cache_Admin_Rules::clear_rules
	  * @param string $wrapper A wrapper to a specific rule to match.
	 */
	public function test_clear_rules()
	{
		$wrapper = '';
		$bool = LiteSpeed_Cache_Admin_Rules::get_instance()->clear_rules($wrapper);
      	$this->assertNull($bool);						
	}
}
