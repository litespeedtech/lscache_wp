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
class LiteSpeed_Cache_Admin_Rules
{
	private static $instance;

	const READABLE = 1;
	const WRITABLE = 2;
	const RW = 3; // Readable and writable.

	private $filerw = null;
	private $is_subdir_install = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function __construct()
	{
	}

	/**
	 * Get the LiteSpeed_Cache_Admin_Rules object.
	 *
	 * @since 1.0.7
	 * @access public
	 * @return LiteSpeed_Cache_Admin_Rules Static instance of the LiteSpeed_Cache_Admin_Rules class.
	 */
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new LiteSpeed_Cache_Admin_Rules();
		}
		return self::$instance;
	}

	/**
	 * Gets the currently used rules file path.
	 *
	 * @since 1.0.4
	 * @access private
	 * @return string The rules file path.
	 */
	public static function get_home_path()
	{
		return get_home_path() . '.htaccess';
	}

	/**
	 * Gets the site .htaccess path. Useful if subdirectory install.
	 *
	 * @since 1.0.7
	 * @access private
	 * @return string The site path.
	 */
	public static function get_site_path()
	{
		return ABSPATH . '.htaccess';
	}

	/**
	 * Checks if the WP install is a subdirectory install. If so, need to test
	 * multiple .htaccess files.
	 *
	 * @since 1.0.7
	 * @access private
	 * @return boolean True if it is a subdirectory install, false otherwise.
	 */
	private static function is_subdir()
	{
		$rules = self::get_instance();
		if (!isset($rules->is_subdir_install)) {
			$rules->is_subdir_install = (get_option('siteurl') !== get_option('home'));
		}
		return $rules->is_subdir_install;
	}

	/**
	 * Checks the .htaccess file(s) permissions. If the file(s) has the given
	 * permissions, it will return so.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param type $permissions The requested permissions. Consts from this class.
	 * @return mixed True/non-zero if the file(s) has the given permissions.
	 * False/zero otherwise.
	 */
	public static function is_file_able($permissions)
	{
		$rules = self::get_instance();
		if (isset($rules->filerw)) {
			return $rules->filerw & $permissions;
		}
		$rules->filerw = 0;

		$home_path = self::get_home_path();

		clearstatcache();
		if (!file_exists($home_path)) {
			return false;
		}
		if (is_readable($home_path)) {
			$rules->filerw |= self::READABLE;
		}
		if (is_writable($home_path)) {
			$rules->filerw |= self::WRITABLE;
		}

		if (!self::is_subdir()) {
			return $rules->filerw & $permissions;
		}
		$site_path = self::get_site_path();
		if (!file_exists($site_path)) {
			$rules->filerw = 0;
			return false;
		}
		if (!is_readable($site_path)) {
			$rules->filerw &= ~self::READABLE;
		}
		if (!is_writable($site_path)) {
			$rules->filerw &= ~self::WRITABLE;
		}
		return $rules->filerw & $permissions;
	}

	/**
	 * Build the wrapper string for common rewrite rules.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $wrapper The common rule wrapper.
	 * @param string $end Returns the ending wrapper.
	 * @return string Returns the opening wrapper.
	 */
	private function build_wrappers($wrapper, &$end)
	{
		$end = '###LSCACHE END ' . $wrapper . '###';
		return '###LSCACHE START ' . $wrapper . '###';
	}

	/**
	 * Gets the contents of the rules file.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $content Returns the content of the file or an error description.
	 * @param string $path The path to get the content from.
	 * @return boolean True if succeeded, false otherwise.
	 */
	public function file_get(&$content, $path = '')
	{
		if (empty($path)) {
			$path = self::get_home_path();
		}
		if (!self::is_file_able(self::READABLE)) {
			$content = __('.htaccess file does not exist or is not readable.', 'litespeed-cache');
			return false;
		}

		$content = file_get_contents($path);
		if ($content == false) {
			$content = __('Failed to get .htaccess file contents.', 'litespeed-cache');
			return false;
		}
		// Remove ^M characters.
		$content = str_ireplace("\x0D", "", $content);
		return true;
	}

	/**
	 * Searches for the LiteSpeed section in contents.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param string $content The content to search
	 * @param string $buf The portion before and including the beginning of
	 * the section.
	 * @param integer $off_end Offset denoting the beginning of the content
	 * after the section.
	 * @return mixed False on failure, the haystack on success.
	 * The haystack may be a string or null if it did not exist.
	 */
	private function file_split($content, &$buf, &$off_end)
	{
		$prefix = '<IfModule LiteSpeed>';
		$engine = 'RewriteEngine on';
		$suffix = '</IfModule>';

		$off_begin = strpos($content, $prefix);
		//if not found
		if ($off_begin === false) {
			$buf = $prefix . "\n" . $engine . "\n";
			return NULL;
		}
		$off_begin += strlen($prefix);
		$off_end = strpos($content, $suffix, $off_begin);
		if ($off_end === false) {
			$buf = sprintf(__('Could not find %s close.', 'litespeed-cache'), 'IfModule');
			return false;
		}
		--$off_end; // go to end of previous line.
		$off_engine = stripos($content, $engine, $off_begin);
		if ($off_engine !== false) {
			$off_begin = $off_engine + strlen($engine) + 1;
			$buf = substr($content, 0, $off_begin);
		}
		else {
			$buf = substr($content, 0, $off_begin) . "\n" . $engine . "\n";
		}
		if ($off_begin == $off_end + 1) {
			++$off_end;
		}
		return substr($content, $off_begin, $off_end - $off_begin);
	}

	/**
	 * Complete the validate changes and save to file.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param string $beginning The portion that includes the edits.
	 * @param string $haystack The source section from the original file.
	 * @param string $orig_content The part of the source file that remains.
	 * @param integer $off_end The offset to the end of the LiteSpeed section.
	 * @param string $path If path is set, use path, else use home path.
	 * @return mixed true on success, else error message on failure.
	 */
	private function file_combine($beginning, $haystack, $orig_content,
			$off_end, $path = '')
	{
		$suffix = '</IfModule>';
		if (!is_null($haystack)) {
			$beginning .= $haystack . substr($orig_content, $off_end);
		}
		else {
			$beginning .= $suffix . "\n\n" . $orig_content;
		}
		return $this->file_save($beginning, false, $path);
	}

	/**
	 * Try to save the rules file changes.
	 *
	 * This function is used by both the edit .htaccess admin page and
	 * the common rewrite rule configuration options.
	 *
	 * This function will create a backup with _lscachebak appended to the file name
	 * prior to making any changese. If creating the backup fails, an error is returned.
	 *
	 * If $cleanup is true, this function strip extra slashes.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $content The new content to put into the rules file.
	 * @param boolean $cleanup True to strip extra slashes, false otherwise.
	 * @param string $path The file path to edit.
	 * @return mixed true on success, else error message on failure.
	 */
	private function file_save($content, $cleanup = true, $path = '')
	{
		if (empty($path)) {
			$path = self::get_home_path();
		}

		if (self::is_file_able(self::RW) == 0) {
			return __('File not readable or not writable.', 'litespeed-cache');
		}
		//failed to backup, not good.
		if (!copy($path, $path . '_lscachebak')) {
			return __('Failed to back up file, abort changes.', 'litespeed-cache');
		}

		if ($cleanup) {
			$content = LiteSpeed_Cache_Admin::cleanup_text($content);
		}

		// File put contents will truncate by default. Will create file if doesn't exist.
		$ret = file_put_contents($path, $content, LOCK_EX);
		if (!$ret) {
			return __('Failed to overwrite ', 'litespeed-cache') . '.htaccess';
		}

		return true;
	}

	/**
	 * Updates the specified common rewrite rule based on original content.
	 *
	 * If the specified rule is not found, just return the rule.
	 * Else if it IS found, need to keep the content surrounding the rule.
	 *
	 * The return value is mixed.
	 * Returns true if the rule is not found in the content.
	 * Returns an array (false, error_msg) on error.
	 * Returns an array (true, new_content) if the rule is found.
	 *
	 * new_content is the original content minus the matched rule. This is
	 * to prevent losing any of the original content.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $content The original content in the .htaccess file.
	 * @param string $output Returns the added rule if success.
	 * @param string $wrapper The wrapper that surrounds the rule.
	 * @param string $cond The rewrite condition to use with the rule.
	 * @param string $match The rewrite rule to match against the condition.
	 * @param string $env The environment change to do if the rule matches.
	 * @param string $flag The flags to use with the rewrite condition.
	 * @return mixed Explained above.
	 */
	private function set_common_rule($content, &$output, $wrapper, $cond,
			$match, $env, $flag = '')
	{

		$wrapper_end = '';
		$wrapper_begin = $this->build_wrappers($wrapper, $wrapper_end);
		$rw_cond = 'RewriteCond %{' . $cond . '} ' . $match;
		if ($flag != '') {
			$rw_cond .= ' [' . $flag . ']';
		}
		$out = $wrapper_begin . "\n" . $rw_cond .  "\n"
			. 'RewriteRule .* - [' . $env . ']' . "\n" . $wrapper_end . "\n";

		// just create the whole buffer.
		if (is_null($content)) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_begin = strpos($content, $wrapper_begin);
		if ($wrap_begin === false) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_end = strpos($content, $wrapper_end, $wrap_begin + strlen($wrapper_begin));
		if ($wrap_end === false) {
			return array(false, __('Could not find wrapper end', 'litespeed-cache'));
		}
		elseif ($match != '') {
			$output .= $out;
		}
		$buf = substr($content, 0, $wrap_begin); // Remove everything between wrap_begin and wrap_end
		$buf .= substr($content, $wrap_end + strlen($wrapper_end));
		return array(true, trim($buf));
	}

	/**
	 * FInds a specified common rewrite rule from the .htaccess file.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $wrapper The wrapper to look for.
	 * @param string $cond The condition to look for.
	 * @param string $match Returns the rewrite rule on success, error message on failure.
	 * @return boolean True on success, false otherwise.
	 */
	public function get_common_rule($wrapper, $cond, &$match)
	{

		if ($this->file_get($match) === false) {
			return false;
		}
		$suffix = '';
		$prefix = $this->build_wrappers($wrapper, $suffix);
		$off_begin = strpos($match, $prefix);
		if ($off_begin === false) {
			$match = '';
			return true; // It does not exist yet, not an error.
		}
		$off_begin += strlen($prefix);
		$off_end = strpos($match, $suffix, $off_begin);
		if ($off_end === false) {
			$match = __('Could not find suffix ', 'litespeed-cache') . $suffix;
			return false;
		}
		elseif ($off_begin >= $off_end) {
			$match = __('Prefix was found after suffix.', 'litespeed-cache');
			return false;
		}

		$subject = substr($match, $off_begin, $off_end - $off_begin);
		$pattern = '/RewriteCond\s%{' . $cond . '}\s+([^[\n]*)\s+[[]*/';
		$matches = array();
		$num_matches = preg_match($pattern, $subject, $matches);
		if ($num_matches === false) {
			$match = __('Did not find a match.', 'litespeed-cache');
			return false;
		}
		$match = trim($matches[1]);
		return true;
	}

	/**
	 * Updates the specified rewrite rule based on original content.
	 *
	 * If the specified rule is not found, just return the rule.
	 * Else if it IS found, need to keep the content surrounding the rule.
	 *
	 * The return value is mixed.
	 * Returns true if the rule is not found in the content.
	 * Returns an array (false, error_msg) on error.
	 * Returns an array (true, new_content) if the rule is found.
	 *
	 * new_content is the original content minus the matched rule. This is
	 * to prevent losing any of the original content.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $content The original content in the .htaccess file.
	 * @param string $output Returns the added rule if success.
	 * @param string $wrapper The wrapper that surrounds the rule.
	 * @param string $match The rewrite rule to match against.
	 * @param string $sub The substitute for the rule match.
	 * @param string $env The environment change to do if the rule matches.
	 * @return mixed Explained above.
	 */
	private function set_rewrite_rule($content, &$output, $wrapper, $match,
			$sub, $env)
	{

		$wrapper_end = '';
		$wrapper_begin = $this->build_wrappers($wrapper, $wrapper_end);
		$out = $wrapper_begin . "\nRewriteRule " . $match . ' ' . $sub
				. ' [' . $env . ']' . "\n" . $wrapper_end . "\n";

		// just create the whole buffer.
		if (is_null($content)) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_begin = strpos($content, $wrapper_begin);
		if ($wrap_begin === false) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_end = strpos($content, $wrapper_end, $wrap_begin + strlen($wrapper_begin));
		if ($wrap_end === false) {
			return array(false, __('Could not find wrapper end', 'litespeed-cache'));
		}
		elseif ($match != '') {
			$output .= $out;
		}
		$buf = substr($content, 0, $wrap_begin); // Remove everything between wrap_begin and wrap_end
		$buf .= substr($content, $wrap_end + strlen($wrapper_end));
		return array(true, trim($buf));
	}

	/**
	 * FInds a specified rewrite rule from the .htaccess file.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param string $wrapper The wrapper to look for.
	 * @param string $match Returns the rewrite rule on success, error message on failure.
	 * @param string $sub Returns the substitute on success, error message on failure.
	 * @param string $env Returns the environment on success, error message on failure.
	 * @return boolean True on success, false otherwise.
	 */
	public function get_rewrite_rule($wrapper, &$match, &$sub, &$env)
	{

		if ($this->file_get($match) === false) {
			return false;
		}
		$suffix = '';
		$prefix = $this->build_wrappers($wrapper, $suffix);
		$off_begin = strpos($match, $prefix);
		if ($off_begin === false) {
			$match = '';
			return true; // It does not exist yet, not an error.
		}
		$off_begin += strlen($prefix);
		$off_end = strpos($match, $suffix, $off_begin);
		if ($off_end === false) {
			$match = __('Could not find suffix ', 'litespeed-cache') . $suffix;
			return false;
		}
		elseif ($off_begin >= $off_end) {
			$match = __('Prefix was found after suffix.', 'litespeed-cache');
			return false;
		}

		$subject = substr($match, $off_begin, $off_end - $off_begin);
		$pattern = '/RewriteRule\s+(\S+)\s+(\S+)(?:\s+\[E=([^\]\s]*)\])?/';
		$matches = array();
		$num_matches = preg_match($pattern, $subject, $matches);
		if ($num_matches === false) {
			$match = __('Did not find a match.', 'litespeed-cache');
			return false;
		}
		$match = trim($matches[1]);
		$sub = trim($matches[2]);
		if (isset($matches[3])) {
			$env = trim($matches[3]);
		}
		else {
			$env = '';
		}
		return true;
	}

	/**
	 * Parse the return value from set_common_rule and set_rewrite_rule.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param mixed $ret The return value from the called function.
	 * @param string $haystack Where to start the next search.
	 * @param string $errors Errors array in case of error.
	 * @return boolean False on function failure, true otherwise.
	 */
	private function parse_ret($ret, &$haystack, &$errors)
	{
		if (!is_array($ret)) {
			return true;
		}
		if ($ret[0]) {
			$haystack = $ret[1];
		}
		else {
			// failed.
			$errors[] = $ret[1];
			return false;
		}
		return true;
	}

	/**
	 * Do the setting login cookie logic. If it is a subdirectory install,
	 * will write to both .htaccess files.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param string $haystack The original content in the .htaccess file.
	 * @param string $input The input string from the Post request.
	 * @param string $option The string currently used in the database.
	 * @param string $output The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans login cookie on success.
	 */
	private function set_login_cookie($haystack, $input, $option, &$output,
			&$errors)
	{
		$aExceptions = array('-', '_');
		$match = '.?';
		$sub = '-';
		$env = 'E=Cache-Vary:' . $input;
		$rule_buf = '';
		$rule_buf2 = '';
		$off_end = 0;

		if ($input == '') {
			if ($option == '') {
				return false;
			}
			$match = '';
			$sub = '';
			$env = '';
		}
		elseif (!ctype_alnum(str_replace($aExceptions, '', $input))) {
			$errors[] = __('Invalid login cookie. Invalid characters found.',
					'litespeed-cache');
			return false;
		}

		$ret = $this->set_rewrite_rule($haystack, $rule_buf, 'LOGIN COOKIE',
				$match, $sub, $env);

		if ($this->parse_ret($ret, $haystack, $errors) === false) {
			return false;
		}
		if (!self::is_subdir()) {
			$output .= $rule_buf;
			return $haystack;
		}

		$path = self::get_site_path();
		$content = '';
		if ($this->file_get($content, $path) === false) {
			$errors[] = $content;
			return false;
		}

		$haystack2 = $this->file_split($content, $rule_buf2, $off_end);
		if ($haystack2 === false) {
			$errors[] = $rule_buf2;
			return false;
		}
		$ret = $this->set_rewrite_rule($haystack2, $rule_buf2, 'LOGIN COOKIE',
			$match, $sub, $env);

		if ($this->parse_ret($ret, $haystack2, $errors) === false) {
			return false;
		}

		$ret = $this->file_combine($rule_buf2, $haystack2, $content,
				$off_end, $path);
		if ($ret !== true) {
			$errors[] = sprintf(__('Failed to put contents into %s', 'litespeed-cache'), '.htaccess');
			return false;
		}

		$output .= $rule_buf;
		return $haystack;
	}

	/**
	 * Given a file's contents, search for an existing login cookie.
	 * If the login cookie exists, this function will modify the content to
	 * correct the cookie's placement.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param string $content The content to parse through.
	 * @return string The login cookie if found, empty string otherwise.
	 */
	private function parse_existing_login_cookie(&$content)
	{
		$rule_pattern = '/(RewriteRule\s+\.[\?\*]\s+-\s+\[E=Cache-Vary:([^\]\s]*)\])/';
		$block_pattern = '!(</?IfModule(?:\s+(LiteSpeed))?>)!';
		// If match found, split_rule will look like:
		// $split_rule[0] = pre match content.
		// $split_rule[1] = matching rule.
		// $split_rule[2] = login cookie
		// $split_rule[3] = post match content.
		$split_rule = preg_split($rule_pattern, $content, -1,
				PREG_SPLIT_DELIM_CAPTURE);
		if (count($split_rule) == 1) {
			return '';
		}
		$suffix = '';
		$prefix = $this->build_wrappers('LOGIN COOKIE', $suffix);
		$replacement = $prefix . "\n" . $split_rule[1] . "\n" . $suffix . "\n";
		$without_rule = $split_rule[0] . $split_rule[3];
		$split_blocks = preg_split($block_pattern, $without_rule, -1,
				PREG_SPLIT_DELIM_CAPTURE);
		$index = array_search('LiteSpeed', $split_blocks);
		if ($index === false) {
			// IfModule LiteSpeed not found.
			$content = "<IfModule LiteSpeed>\n" . $replacement . "</IfModule\n"
					. $without_rule;
			return $split_rule[2];
		}
		elseif ($index + 2 > count($split_blocks)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_YELLOW,
				__('Tried to parse for existing login cookie.', 'litespeed-cache')
				. __(' .htaccess file not valid. Please verify the contents.',
						'litespeed-cache'));
			return '';
		}
		$split_blocks[$index + 1] .= $replacement;
		array_splice($split_blocks, $index, 1);
		$content = implode('', $split_blocks);
		return $split_rule[2];
	}

	/**
	 * Scans the .htaccess file(s) for existing login cookie rewrite rule.
	 *
	 * @since 1.0.7
	 * @access public
	 * @return string The login cookie if found, empty string otherwise.
	 */
	public function scan_login_cookie()
	{
		$content = '';
		$site_content = '';
		if (!self::is_file_able(self::RW)) {
			return '';
		}
		if ($this->file_get($content) === false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED, $content);
			return '';
		}

		$home_cookie = $this->parse_existing_login_cookie($content);
		if (!self::is_subdir()) {
			if (!empty($home_cookie)) {
				$this->file_save($content, false);
			}
			return $home_cookie;
		}
		$site_path = self::get_site_path();

		if ($this->file_get($site_content, $site_path) === false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED, $content);
			return '';
		}

		$site_cookie = $this->parse_existing_login_cookie($site_content);
		if ((empty($home_cookie) && !empty($site_cookie))
				|| (!empty($home_cookie) && empty($site_cookie))
				|| (strcmp($home_cookie, $site_cookie))) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_YELLOW,
				__('This site is a subdirectory install.', 'litespeed-cache')
				. __(' Login cookies do not match:', 'litespeed-cache')
				. '<br>' . self::get_home_path() . ': ' . $home_cookie
				. '<br>' . $site_path . ': ' . $site_cookie . '<br>'
				. __('Please remove both and set the login cookie in the LiteSpeed Cache advanced settings.', 'litespeed-cache'));
			return 'err';
		}
		if (!empty($home_cookie)) {
			$this->file_save($content, false);
			$this->file_save($site_content, false, $site_path);
		}
		return $home_cookie;
	}

	/**
	 * Validate common rewrite rules configured by the admin.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param array $input The configurations selected.
	 * @param array $options The current configurations.
	 * @param array $errors Returns error messages added if failed.
	 * @return mixed Returns updated options array on success, false otherwise.
	 */
	public function validate_common_rewrites($input, &$options, &$errors)
	{
		$content = '';
		$buf = '';
		$off_end = 0;

		if (($input[LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED] === false)
			&& ($options[LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED] === false)
			&& ($input[LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES] === $options[LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES])
			&& ($input[LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS] === $options[LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS])) {
			return $options;
		}

		if ($this->file_get($content) === false) {
			$errors[] = $content;
			return false;
		}
		elseif (!self::is_file_able(self::WRITABLE)) {
			$errors[] = __('File is not writable.', 'litespeed-cache');
			return false;
		}

		$haystack = $this->file_split($content, $buf, $off_end);
		if ($haystack === false) {
			$errors[] = $buf;
			return false;
		}

		$id = LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED;
		if ($input['lscwp_' . $id] === $id) {
			$options[$id] = true;
			$ret = $this->set_common_rule($haystack, $buf,
					'MOBILE VIEW', 'HTTP_USER_AGENT',
					$input[LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST],
					'E=Cache-Control:vary=ismobile', 'NC');

			$this->parse_ret($ret, $haystack, $errors);
		}
		elseif ($options[$id] === true) {
			$options[$id] = false;
			$ret = $this->set_common_rule($haystack, $buf,
					'MOBILE VIEW', '', '', '');
			$this->parse_ret($ret, $haystack, $errors);
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;
		if ($input[$id]) {
			$cookie_list = preg_replace("/[\r\n]+/", '|', $input[$id]);
		}
		else {
			$cookie_list = '';
		}

		$ret = $this->set_common_rule($haystack, $buf, 'COOKIE',
				'HTTP_COOKIE', $cookie_list, 'E=Cache-Control:no-cache');
		$this->parse_ret($ret, $haystack, $errors);

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		$ret = $this->set_common_rule($haystack, $buf, 'USER AGENT',
				'HTTP_USER_AGENT', $input[$id], 'E=Cache-Control:no-cache');
		$this->parse_ret($ret, $haystack, $errors);

		$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE;
		$ret = $this->set_login_cookie($haystack, $input[$id],
				$options[$id], $buf, $errors);
		if ($ret !== false) {
			$haystack = $ret;
			$options[$id] = $input[$id];
		}

		$ret = $this->file_combine($buf, $haystack, $content, $off_end);
		if ($ret !== true) {
			$errors[] = sprintf(__('Failed to put contents into %s', 'litespeed-cache'), '.htaccess');
			return false;
		}
		return $options;
	}

	/**
	 * Clear the rules file of any changes added by the plugin specifically.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function clear_rules()
	{
		$content = '';
		$buf = '';
		$suffix = '</IfModule>';
		$off_end = 0;
		$errors = array();

		clearstatcache();
		if ($this->file_get($content) === false) {
			return;
		}
		elseif (!self::is_file_able(self::WRITABLE)) {
			return;
		}

		$haystack = $this->file_split($content, $buf, $off_end);
		$ret = $this->set_common_rule($haystack, $buf, 'MOBILE VIEW',
				'', '', '');
		if ((is_array($ret)) && ($ret[0])) {
			$haystack = $ret[1];
		}

		$ret = $this->set_common_rule($haystack, $buf, 'COOKIE', '', '', '');
		if ((is_array($ret)) && ($ret[0])) {
			$haystack = $ret[1];
		}

		$ret = $this->set_common_rule($haystack, $buf, 'USER AGENT',
				'', '', '');
		if ((is_array($ret)) && ($ret[0])) {
			$haystack = $ret[1];
		}

		$ret = $this->set_login_cookie($haystack, '', 'not', $buf, $errors);
		if ($ret !== false) {
			$haystack = $ret;
		}

		if (!is_null($haystack)) {
			$buf .= $haystack . substr($content, $off_end);
		}
		else {
			$buf .= $suffix . "\n\n" . $content;
		}
		$this->file_save($buf);
		return;
	}

	/**
	 * Parses the .htaccess buffer when the admin saves changes in the edit
	 *  .htaccess page.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function htaccess_editor_save()
	{
		if ((is_multisite()) && (!is_network_admin())) {
			return;
		}
		if (empty($_POST) || empty($_POST['submit'])) {
			return;
		}
		if (($_POST['lscwp_htaccess_save'])
				&& ($_POST['lscwp_htaccess_save'] === 'save_htaccess')
				&& (check_admin_referer('lscwp_edit_htaccess', 'save'))
				&& ($_POST['lscwp_ht_editor'])) {
			$msg = $this->file_save($_POST['lscwp_ht_editor']);
			if ($msg === true) {
				$msg = __('File Saved.', 'litespeed-cache');
				$color = LiteSpeed_Cache_Admin_Display::NOTICE_GREEN;
			}
			else {
				$color = LiteSpeed_Cache_Admin_Display::NOTICE_RED;
			}
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice($color, $msg);
		}

	}
}
