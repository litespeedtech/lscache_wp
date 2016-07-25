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

	const EDITOR_INPUT_NAME = 'lscwp_htaccess_save';
	const EDITOR_INPUT_VAL = 'save_htaccess';
	const EDITOR_NONCE_NAME = 'lscwp_edit_htaccess';
	const EDITOR_NONCE_VAL = 'save';
	const EDITOR_TEXTAREA_NAME = 'lscwp_ht_editor';

	const READABLE = 1;
	const WRITABLE = 2;
	const RW = 3; // Readable and writable.

	private $filerw = null;
	private $is_subdir_install = null;

	private static $OUT_FILESAVE;

	private static $ERR_BACKUP;
	private static $ERR_DNE; // does not exist or is not readable
	private static $ERR_FILESAVE;
	private static $ERR_GET;
	private static $ERR_INVALID_LOGIN;
	private static $ERR_NOT_FOUND;
	private static $ERR_OVERWRITE;
	private static $ERR_PARSE_FILE;
	private static $ERR_READWRITE;
	private static $ERR_SUBDIR_MISMATCH_LOGIN;
	private static $ERR_WRONG_ORDER;

	private static $RW_BLOCK_START = '<IfModule LiteSpeed>';
	private static $RW_BLOCK_END = '</IfModule>';
	private static $RW_ENGINEON = 'RewriteEngine on';

	private static $RW_PATTERN_COND_START = '/RewriteCond\s%{';
	private static $RW_PATTERN_COND_END = '}\s+([^[\n]*)\s+[[]*/';
	private static $RW_PATTERN_RULE = '/RewriteRule\s+(\S+)\s+(\S+)(?:\s+\[E=([^\]\s]*)\])?/';
	private static $RW_PATTERN_LOGIN = '/(RewriteRule\s+\.[\?\*]\s+-\s+\[E=Cache-Vary:([^\]\s]*)\])/';
	private static $RW_PATTERN_LOGIN_BLOCK = '!(</?IfModule(?:\s+(LiteSpeed))?>)!';
	private static $RW_PATTERN_UPGRADE_BLOCK = '!(<IfModule\s+LiteSpeed>[^<]*)(</IfModule>)!';
	private static $RW_PATTERN_WRAPPERS = '/###LSCACHE START[^#]*###[^#]*###LSCACHE END[^#]*###\n?/';

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
			self::set_translations();
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
	public static function file_get(&$content, $path = '')
	{
		if (empty($path)) {
			$path = self::get_home_path();
		}
		if (!self::is_file_able(self::READABLE)) {
			$content = self::$ERR_DNE;
			return false;
		}

		$content = file_get_contents($path);
		if ($content == false) {
			$content = self::$ERR_GET;
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
		$off_begin = strpos($content, self::$RW_BLOCK_START);
		//if not found
		if ($off_begin === false) {
			$buf = self::$RW_BLOCK_START . "\n" . self::$RW_ENGINEON . "\n";
			return NULL;
		}
		$off_begin += strlen(self::$RW_BLOCK_START);
		$off_end = strpos($content, self::$RW_BLOCK_END, $off_begin);
		if ($off_end === false) {
			$buf = self::$ERR_NOT_FOUND . 'IfModule close';
			return false;
		}
		--$off_end; // go to end of previous line.
		$off_engine = stripos($content, self::$RW_ENGINEON, $off_begin);
		if ($off_engine !== false) {
			$off_begin = $off_engine + strlen(self::$RW_ENGINEON) + 1;
			$buf = substr($content, 0, $off_begin);
		}
		else {
			$buf = substr($content, 0, $off_begin) . "\n"
				. self::$RW_ENGINEON . "\n";
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
		if (!is_null($haystack)) {
			$beginning .= $haystack . substr($orig_content, $off_end);
		}
		else {
			$beginning .= self::$RW_BLOCK_END . "\n\n" . $orig_content;
		}
		return self::file_save($beginning, false, $path);
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
	private static function file_save($content, $cleanup = true, $path = '')
	{
		if (empty($path)) {
			$path = self::get_home_path();
		}

		if (self::is_file_able(self::RW) == 0) {
			return self::$ERR_READWRITE;
		}
		//failed to backup, not good.
		if (!copy($path, $path . '_lscachebak')) {
			return self::$ERR_BACKUP;
		}

		if ($cleanup) {
			$content = LiteSpeed_Cache_Admin::cleanup_text($content);
		}

		// File put contents will truncate by default. Will create file if doesn't exist.
		$ret = file_put_contents($path, $content, LOCK_EX);
		if (!$ret) {
			return self::$ERR_OVERWRITE . '.htaccess';
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
		$wrap_end = strpos($content, $wrapper_end,
			$wrap_begin + strlen($wrapper_begin));

		if ($wrap_end === false) {
			return array(false, self::$ERR_NOT_FOUND . 'wrapper end');
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

		if (self::file_get($match) === false) {
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
			$match = self::$ERR_NOT_FOUND . 'suffix ' . $suffix;
			return false;
		}
		elseif ($off_begin >= $off_end) {
			$match = self::$ERR_WRONG_ORDER;
			return false;
		}

		$subject = substr($match, $off_begin, $off_end - $off_begin);
		$pattern = self::$RW_PATTERN_COND_START . $cond
			. self::$RW_PATTERN_COND_END;
		$matches = array();
		$num_matches = preg_match($pattern, $subject, $matches);
		if ($num_matches === false) {
			$match = self::$ERR_NOT_FOUND . 'a match.';
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
			return array(false, self::$ERR_NOT_FOUND . 'wrapper end');
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

		if (self::file_get($match) === false) {
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
			$match = self::$ERR_NOT_FOUND . 'suffix ' . $suffix;
			return false;
		}
		elseif ($off_begin >= $off_end) {
			$match = self::$ERR_WRONG_ORDER;
			return false;
		}

		$subject = substr($match, $off_begin, $off_end - $off_begin);
		$pattern = self::$RW_PATTERN_RULE;
		$matches = array();
		$num_matches = preg_match($pattern, $subject, $matches);
		if ($num_matches === false) {
			$match = self::$ERR_NOT_FOUND . 'a match.';
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
	 * Do the setting cache favicon logic.
	 *
	 * @since 1.0.8
	 * @access private
	 * @param string $haystack The original content in the .htaccess file.
	 * @param boolean $action Whether to add or remove the rule.
	 * @param string $output The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans favicon on success.
	 */
	private function set_favicon($haystack, $action, &$output, &$errors)
	{
		$match = 'favicon\.ico$';
		$sub = '-';
		$env = 'E=cache-control:max-age=86400';
		$rule_buf = '';
		if ($action == 0) {
			$match = '';
			$sub = '';
			$env = '';
		}
		$ret = $this->set_rewrite_rule($haystack, $rule_buf, 'FAVICON',
				$match, $sub, $env);

		if ($this->parse_ret($ret, $haystack, $errors) === false) {
			return false;
		}
		$output .= $rule_buf;
		return $haystack;
	}

	/**
	 * Do the setting cache resource logic.
	 *
	 * @since 1.0.8
	 * @access private
	 * @param string $haystack The original content in the .htaccess file.
	 * @param boolean $set Whether to add or remove the rule.
	 * @param string $output The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans favicon on success.
	 */
	private function set_cache_resource($haystack, $set, &$output, &$errors)
	{
		$match = 'wp-content/.*/(loader|fonts)\.php';
		$sub = '-';
		$env = 'E=cache-control:max-age=3600';
		$rule_buf = '';
		if ($set == 0) {
			$match = '';
			$sub = '';
			$env = '';
		}
		$ret = $this->set_rewrite_rule($haystack, $rule_buf, 'RESOURCE',
				$match, $sub, $env);

		if ($this->parse_ret($ret, $haystack, $errors) === false) {
			return false;
		}
		$output .= $rule_buf;
		return $haystack;
	}

	/**
	 * Parses the input to see if there is a need to edit the .htaccess file.
	 *
	 * @since 1.0.8
	 * @access private
	 * @param array $options The current options
	 * @param array $input The input
	 * @return boolean True if no need to edit, false otherwise.
	 */
	private function check_input($options, &$input)
	{
		$enable_key = ((is_multisite())
			? LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED
			: LiteSpeed_Cache_Config::OPID_ENABLED);
		$val_check = array(
			LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED,
			LiteSpeed_Cache_Config::OPID_CACHE_FAVICON,
			LiteSpeed_Cache_Config::OPID_CACHE_RES
		);

		$ids = array(
			$enable_key,
			LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED,
			LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES,
			LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS,
			LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE,
			LiteSpeed_Cache_Config::OPID_CACHE_LOGIN,
			LiteSpeed_Cache_Config::OPID_CACHE_FAVICON,
			LiteSpeed_Cache_Config::OPID_CACHE_RES
		);

		foreach ($val_check as $opt) {
			if (isset($input['lscwp_' . $opt])) {
				$input[$opt] = ($input['lscwp_' . $opt] === $opt);
			}
			else {
				$input[$opt] = false;
			}
		}

		foreach ($ids as $id) {
			if ((isset($input[$id]))
					&& ($input[$id]
					!== $options[$id])) {
				return false;
			}
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
	 * Do the setting subdir cookies logic. If it is a subdirectory install,
	 * will write to both .htaccess files.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param string $haystack The original content in the .htaccess file.
	 * @param array $input The input array from the Post request.
	 * @param array $options The array currently used in the database.
	 * @param string $output The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans login cookie on success.
	 */
	private function set_subdir_cookie($haystack, $input, $options, &$output,
			&$errors)
	{
		$enable_key = ((is_multisite())
			? LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED
			: LiteSpeed_Cache_Config::OPID_ENABLED);
		$id = LiteSpeed_Cache_Config::OPID_CACHE_RES;
		$res = (isset($input[$id]) ? ($input[$id] && $options[$enable_key])
			: false);
		$login = (isset($input[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE])
			? $input[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE] : '');

		$aExceptions = array('-', '_');
		$match = '.?';
		$sub = '-';
		$env = 'E=Cache-Vary:' . $login;
		$rule_buf = '';
		$rule_buf2 = '';
		$off_end = 0;
		$ret = false;

		if (!self::is_subdir()) {
			$ret = $this->set_cache_resource($haystack, $res, $rule_buf,
				$errors);
			if ($ret === false) {
				return false;
			}
			$haystack = $ret;
			$output .= $rule_buf;
			$rule_buf = '';
		}

		if ($login == '') {
			if ($options == '') {
				return $ret;
			}
			$match = '';
			$sub = '';
			$env = '';
		}
		elseif (!ctype_alnum(str_replace($aExceptions, '', $login))) {
			$errors[] = self::$ERR_INVALID_LOGIN;
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
		if (self::file_get($content, $path) === false) {
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

		$ret = $this->set_cache_resource($haystack2, $res, $rule_buf2, $errors);
		if ($ret === false) {
			return false;
		}

		$haystack2 = $ret;
		$ret = $this->file_combine($rule_buf2, $haystack2, $content,
				$off_end, $path);
		if ($ret !== true) {
			$errors[] = self::$ERR_FILESAVE;
			return false;
		}

		$output .= $rule_buf;
		return $haystack;
	}

	/**
	 * Helper function to set rewrite rules on upgrade.
	 *
	 * @since 1.0.8
	 * @access private
	 * @param string $wrapper A wrapper to a specific rule
	 * @param string $match The match to set the rewrite rule to search for.
	 * @param string $sub The substitution to set the rewrite rule to replace with.
	 * @param string $flag The flag the rewrite rule should set.
	 * @param string $content The original content/new content after replacement.
	 */
	private function set_on_upgrade($wrapper, $match, $sub, $flag, &$content)
	{
		$split_rule = preg_split(self::$RW_PATTERN_UPGRADE_BLOCK, $content, -1,
				PREG_SPLIT_DELIM_CAPTURE);
		$rule_buf = '';
		$this->set_rewrite_rule(null, $rule_buf, $wrapper, $match, $sub,
			$flag);
		if (count($split_rule) == 1) {
			//not found
			$content = self::$RW_BLOCK_START . "\n" . $rule_buf
				. self::$RW_BLOCK_END . "\n" . $content;
		}
		else {
			//else found
			// split_rule[0] = pre match
			// split_rule[1] = contents of IfModule
			// split_rule[2] = closing IfModule
			// split_rule[3] = post match
			$split_rule[2] = $rule_buf . "\n" . $split_rule[2];
			$content = implode('', $split_rule);
		}
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
		// If match found, split_rule will look like:
		// $split_rule[0] = pre match content.
		// $split_rule[1] = matching rule.
		// $split_rule[2] = login cookie
		// $split_rule[3] = post match content.
		$split_rule = preg_split(self::$RW_PATTERN_LOGIN, $content, -1,
				PREG_SPLIT_DELIM_CAPTURE);
		if (count($split_rule) == 1) {
			return '';
		}
		$suffix = '';
		$prefix = $this->build_wrappers('LOGIN COOKIE', $suffix);
		$replacement = $prefix . "\n" . $split_rule[1] . "\n" . $suffix . "\n";
		$without_rule = $split_rule[0] . $split_rule[3];
		$split_blocks = preg_split(self::$RW_PATTERN_LOGIN_BLOCK,
			$without_rule, -1, PREG_SPLIT_DELIM_CAPTURE);
		$index = array_search('LiteSpeed', $split_blocks);
		if ($index === false) {
			// IfModule LiteSpeed not found.
			$content = self::$RW_BLOCK_START . "\n" . $replacement
				. self::$RW_BLOCK_END . "\n" . $without_rule;
			return $split_rule[2];
		}
		elseif ($index + 2 > count($split_blocks)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_YELLOW,
				self::$ERR_PARSE_FILE);
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
	public function scan_upgrade()
	{
		$config = LiteSpeed_Cache::plugin()->get_config();
		if (is_multisite()) {
			$options = $config->get_site_options();
			$enabled = $options[LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED];
		}
		else {
			$options = $config->get_options();
			$enabled = $options[LiteSpeed_Cache_Config::OPID_ENABLED];
		}
		$content = '';
		$site_content = '';
		if (!self::is_file_able(self::RW)) {
			return '';
		}
		if (self::file_get($content) === false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED, $content);
			return '';
		}

		if ($enabled) {
			$this->set_on_upgrade('FAVICON', '^favicon\.ico$', '-',
				'E=cache-control:max-age=86400', $content);
		}

		$home_cookie = $this->parse_existing_login_cookie($content);
		if (!self::is_subdir()) {
			if ($enabled) {
				$this->set_on_upgrade('RESOURCE', 'wp-content/.*/(loader|fonts)\.php',
					'-', 'E=cache-control:max-age=3600', $content);
			}
			self::file_save($content, false);
			return $home_cookie;
		}
		$site_path = self::get_site_path();

		if (self::file_get($site_content, $site_path) === false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED, $content);
			return '';
		}

		if ($enabled) {
			$this->set_on_upgrade('RESOURCE', 'wp-content/.*/(loader|fonts)\.php',
				'-', 'E=cache-control:max-age=3600', $site_content);
		}

		$site_cookie = $this->parse_existing_login_cookie($site_content);
		if ((empty($home_cookie) && !empty($site_cookie))
				|| (!empty($home_cookie) && empty($site_cookie))
				|| ($home_cookie != $site_cookie)) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_YELLOW,
				self::$ERR_SUBDIR_MISMATCH_LOGIN
				. '<br>' . self::get_home_path() . ': ' . $home_cookie
				. '<br>' . $site_path . ': ' . $site_cookie);
			return 'err';
		}
		self::file_save($content, false);
		self::file_save($site_content, false, $site_path);
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

		if ($this->check_input($options, $input)) {
			return $options;
		}

		if (self::file_get($content) === false) {
			$errors[] = $content;
			return false;
		}
		elseif (!self::is_file_able(self::WRITABLE)) {
			$errors[] = self::$ERR_READWRITE;
			return false;
		}

		$haystack = $this->file_split($content, $buf, $off_end);
		if ($haystack === false) {
			$errors[] = $buf;
			return false;
		}

		$id = LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED;

		if ((isset($input[$id])) && ($input[$id])) {
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
		if ((isset($input[$id])) && ($input[$id])) {
			$cookie_list = preg_replace("/[\r\n]+/", '|', $input[$id]);
		}
		else {
			$cookie_list = '';
		}

		$ret = $this->set_common_rule($haystack, $buf, 'COOKIE',
				'HTTP_COOKIE', $cookie_list, 'E=Cache-Control:no-cache');
		if ($this->parse_ret($ret, $haystack, $errors)) {
			$options[$id] = $cookie_list;
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		if (isset($input[$id])) {
			$ret = $this->set_common_rule($haystack, $buf, 'USER AGENT',
					'HTTP_USER_AGENT', $input[$id], 'E=Cache-Control:no-cache');
			if ($this->parse_ret($ret, $haystack, $errors)) {
				$options[$id] = $input[$id];
			}
		}

		$ret = $this->set_subdir_cookie($haystack, $input,
				$options, $buf, $errors);
		if ($ret !== false) {
			$haystack = $ret;
			$options[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE] =
				$input[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE];
			$options[LiteSpeed_Cache_Config::OPID_CACHE_RES] =
				$input[LiteSpeed_Cache_Config::OPID_CACHE_RES];
		}

		$id = LiteSpeed_Cache_Config::OPID_CACHE_FAVICON;
		$enable_key = ((is_multisite())
			? LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED
			: LiteSpeed_Cache_Config::OPID_ENABLED);

		$ret = $this->set_favicon($haystack,
			($input[$id] && $options[$enable_key]), $buf, $errors);
		if ($ret !== false) {
			$haystack = $ret;
			$options[$id] = $input[$id];
		}

		$ret = $this->file_combine($buf, $haystack, $content, $off_end);
		if ($ret !== true) {
			$errors[] = self::$ERR_FILESAVE;
			return false;
		}
		return $options;
	}

	/**
	 * Clear the rules file of any changes added by the plugin specifically.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param string $wrapper A wrapper to a specific rule to match.
	 */
	public static function clear_rules($wrapper = '')
	{
		$content = '';
		$site_content = '';
		$pattern = self::$RW_PATTERN_WRAPPERS;

		clearstatcache();
		if (self::file_get($content) === false) {
			return;
		}
		elseif (!self::is_file_able(self::WRITABLE)) {
			return;
		}

		if (!empty($wrapper)) {
			$pattern = '/###LSCACHE START ' . $wrapper
				. '###[^#]*###LSCACHE END ' . $wrapper . '###\n?/';
		}
		$buf = preg_replace($pattern, '', $content);

		self::file_save($buf);

		if (!self::is_subdir()) {
			return;
		}
		$site_path = self::get_site_path();
		if (self::file_get($site_content, $site_path) === false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED, $content);
			return '';
		}

		if (!empty($wrapper)) {
			$pattern = '/###LSCACHE START ' . $wrapper
				. '###[^#]*###LSCACHE END ' . $wrapper . '###\n?/';
		}
		$site_buf = preg_replace($pattern, '', $site_content);
		self::file_save($site_buf, true, $site_path);

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
		if ((isset($_POST[self::EDITOR_INPUT_NAME]))
				&& ($_POST[self::EDITOR_INPUT_NAME] === self::EDITOR_INPUT_VAL)
				&& (check_admin_referer(self::EDITOR_NONCE_NAME,
					self::EDITOR_NONCE_VAL))
				&& (isset($_POST[self::EDITOR_TEXTAREA_NAME]))) {
			$msg = self::file_save($_POST[self::EDITOR_TEXTAREA_NAME]);
			if ($msg === true) {
				$msg = self::$OUT_FILESAVE;
				$color = LiteSpeed_Cache_Admin_Display::NOTICE_GREEN;
			}
			else {
				$color = LiteSpeed_Cache_Admin_Display::NOTICE_RED;
			}
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice($color, $msg);
		}

	}

	/**
	 * Set up the translations for the error messages and outputs.
	 *
	 * @access private
	 * @since 1.0.8
	 */
	private static function set_translations()
	{
		self::$OUT_FILESAVE = __('File Saved.', 'litespeed-cache');

		self::$ERR_BACKUP = __('Failed to back up file, aborted changes.', 'litespeed-cache');
		self::$ERR_DNE = __('.htaccess file does not exist or is not readable.', 'litespeed-cache');
		self::$ERR_FILESAVE = sprintf(__('Failed to put contents into %s', 'litespeed-cache'),
			'.htaccess');
		self::$ERR_GET = sprintf(__('Failed to get %s file contents.', 'litespeed-cache'),
			'.htaccess');
		self::$ERR_INVALID_LOGIN = __('Invalid login cookie. Invalid characters found.',
					'litespeed-cache');
		self::$ERR_NOT_FOUND = __('Could not find ', 'litespeed-cache');
		self::$ERR_OVERWRITE = __('Failed to overwrite ', 'litespeed-cache');
		self::$ERR_PARSE_FILE = __('Tried to parse for existing login cookie.', 'litespeed-cache')
				. sprintf(__(' %s file not valid. Please verify the contents.',
						'litespeed-cache'), '.htaccess');
		self::$ERR_READWRITE = __('File not readable or not writable.', 'litespeed-cache');
		self::$ERR_SUBDIR_MISMATCH_LOGIN =
			__('This site is a subdirectory install.', 'litespeed-cache')
			. __(' Login cookies do not match.', 'litespeed-cache')
			. __(' Please remove both and set the login cookie in LiteSpeed Cache advanced settings.',
				'litespeed-cache');
		self::$ERR_WRONG_ORDER = __('Prefix was found after suffix.', 'litespeed-cache');
	}
}
