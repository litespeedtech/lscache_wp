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
	private $home_path = null;
	private $site_path = null;

	private static $OUT_FILESAVE;

	private static $ERR_BACKUP;
	private static $ERR_DNE; // does not exist or is not readable
	private static $ERR_FILESAVE;
	private static $ERR_GET;
	private static $ERR_INVALID_LOGIN;
	private static $ERR_NO_LIST;
	private static $ERR_NOT_FOUND;
	private static $ERR_OVERWRITE;
	private static $ERR_PARSE_FILE;
	private static $ERR_READWRITE;
	private static $ERR_SUBDIR_MISMATCH_LOGIN;
	private static $ERR_WRONG_ORDER;

	private static $RW_BLOCK_START = '<IfModule LiteSpeed>';
	private static $RW_BLOCK_END = '</IfModule>';
	private static $RW_WRAPPER = 'PLUGIN - Do not edit the contents of this block!';
	private static $RW_PREREQ = "\nRewriteEngine on\nCacheLookup Public on\n";

	private static $RW_PATTERN_COND_START = '/RewriteCond\s%{';
	private static $RW_PATTERN_COND_END = '}\s+([^[\n]*)\s+[[]*/';
	private static $RW_PATTERN_RULE = '/RewriteRule\s+(\S+)\s+(\S+)(?:\s+\[E=([^\]\s]*)\])?/';
	private static $RW_PATTERN_LOGIN = '/(RewriteRule\s+\.[\?\*]\s+-\s+\[E=Cache-Vary:([^\]\s]*)\])/';
	private static $RW_PATTERN_LOGIN_BLOCK = '!(</?IfModule(?:\s+(LiteSpeed))?>)!';
	private static $RW_PATTERN_UPGRADE_BLOCK = '!(<IfModule\s+LiteSpeed>[^<]*)(</IfModule>)!';
	private static $RW_PATTERN_WRAPPERS = '/###LSCACHE START[^#]*###[^#]*###LSCACHE END[^#]*###\n?/';
	static $RW_PATTERN_RES = 'wp-content/.*/[^/]*(loader|fonts|\.css|\.js)\.php';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function __construct()
	{
		$this->setup();
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
		$rules = self::get_instance();
		return $rules->home_path;
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
		$rules = self::get_instance();
		return $rules->site_path;
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
		return $rules->is_subdir_install;
	}

	/**
	 * Checks the .htaccess file(s) permissions. If the file(s) has the given
	 * permissions, it will return so.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param int $permissions The requested permissions. Consts from this class.
	 * @param string $path Optional path to check permissions of. If not given,
	 * will assume it is a .htaccess file.
	 * @return mixed True/non-zero if the file(s) has the given permissions.
	 * False/zero otherwise.
	 */
	public static function is_file_able($permissions, $path = null)
	{
		$home = self::get_home_path();
		$site = self::get_site_path();

		if (($path === null) || ($path === $home) || ($path === $site)) {
			$rules = self::get_instance();
			return (($rules->filerw & $permissions) === $permissions);
		}
		$perms = 0;
		$test_permissions = file_exists($path) ? $path : dirname($path);

		if (is_readable($test_permissions)) {
			$perms |= self::READABLE;
		}
		if (is_writable($test_permissions)) {
			$perms |= self::WRITABLE;
		}
		return (($perms & $permissions) === $permissions);
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
	 * Set up the paths used for searching.
	 *
	 * @since 1.0.11
	 * @access private
	 * @param string $common The common part of the paths.
	 * @param string $install The install path portion. Will contain full path on return.
	 * @param string $access The access path portion. Will contain full path on return.
	 */
	private static function path_search_setup(&$common, &$install, &$access)
	{
		$partial_dir = false;

		if ( substr($common, -1) != '/' ) {

			if ( $install !== '' && $install[0] != '/' ) {
				$partial_dir = true;
			}
			elseif ( $access !== '' && $access[0] != '/' ) {
				$partial_dir = true;
			}
		}
		$install = rtrim($common . $install, '/');
		$access = rtrim($common . $access, '/');

		if ($partial_dir) {
			$common = dirname($common);
		}
		else {
			$common = rtrim($common, '/');
		}
	}

	/**
	 * Check to see if a file exists starting at $start_path and going up
	 * directories until it hits stop_path.
	 *
	 * As dirname() strips the ending '/', paths passed in must exclude the
	 * final '/', and the file must start with a '/'.
	 *
	 * @since 1.0.11
	 * @access private
	 * @param string $stop_path The last directory level to search.
	 * @param string $start_path The first directory level to search.
	 * @param string $file The file to search for.
	 * @return string The deepest path where the file exists,
	 * or the last path used if it does not exist.
	 */
	private static function path_search($stop_path, $start_path, $file)
	{
		while ((!file_exists($start_path . $file))) {
			if ($start_path === $stop_path) {
				break;
			}
			$start_path = dirname($start_path);
		}
		return $start_path . $file;
	}

	/**
	 * Set the path class variables.
	 *
	 * @since 1.0.11
	 * @access private
	 */
	private function path_set()
	{
		$install = ABSPATH;
		$access = get_home_path();

		if ($access === '/') {
			// get home path failed. Trac ticket #37668
			$install = set_url_scheme( get_option( 'siteurl' ), 'http' );
			$access = set_url_scheme( get_option( 'home' ), 'http' );
		}

		/**
		 * Converts the intersection of $access and $install to \0
		 * then counts the number of \0 characters before the first non-\0.
		 */
		$common_count = strspn($access ^ $install, "\0");

		$install_part = substr($install, $common_count);
		$access_part = substr($access, $common_count);
		if ($access_part !== false) {
			// access is longer than install or they are in different dirs.
			if ($install_part === false) {
				$install_part = '';
			}
		}
		elseif ($install_part !== false) {
			// Install is longer than access
			$access_part = '';
			$install_part = rtrim($install_part, '/');
		}
		else {
			// they are equal - no need to find paths.
			$this->home_path = ABSPATH . '.htaccess';
			$this->site_path = ABSPATH . '.htaccess';
			return;
		}
		$common_path = substr(ABSPATH, 0, -(strlen($install_part) + 1));

		self::path_search_setup($common_path, $install_part, $access_part);

		$this->site_path = self::path_search($common_path, $install_part,
			'/.htaccess');
		$this->home_path = self::path_search($common_path, $access_part,
			'/.htaccess');
		return;
	}

	/**
	 * Sets up the class variables.
	 *
	 * @since 1.0.11
	 * @access private
	 */
	private function setup()
	{
		$this->path_set();
		clearstatcache();

		if ($this->home_path === $this->site_path) {
			$this->is_subdir_install = false;
		}
		else {
			$this->is_subdir_install = true;
		}

		$this->filerw = 0;
		$test_permissions = file_exists($this->home_path) ? $this->home_path
			: dirname($this->home_path);

		if (is_readable($test_permissions)) {
			$this->filerw |= self::READABLE;
		}
		if (is_writable($test_permissions)) {
			$this->filerw |= self::WRITABLE;
		}
		if (!$this->is_subdir_install) {
			return;
		}
		elseif (!file_exists($this->site_path)) {
			$ret = file_put_contents($this->site_path, "\n", LOCK_EX);
			if (!$ret) {
				$this->filerw = 0;
				return;
			}
		}

		// If site file is not readable/writable, remove the flag.
		if (!is_readable($this->site_path)) {
			$this->filerw &= ~self::READABLE;
		}
		if (!is_writable($this->site_path)) {
			$this->filerw &= ~self::WRITABLE;
		}
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
			if (!file_exists($path)) {
				$content = "\n";
				return true;
			}
		}
		if (!self::is_file_able(self::RW)) {
			$content = self::$ERR_READWRITE;
			return false;
		}

		$content = file_get_contents($path);
		if ($content === false) {
			$content = self::$ERR_GET;
			return false;
		}
		// Remove ^M characters.
		$content = str_ireplace("\x0D", "", $content);
		return true;
	}

	/**
	 * Get the ifmodule block if it exists in the content.
	 *
	 * @since 1.0.12
	 * @access public
	 * @param string $content The content to search.
	 * @param int $off_begin Will be set to the beginning offset. Starts
	 * just after the opening <IfModule>.
	 * @param int $off_end Will be set to the ending offset. Starts just
	 * before the closing </IfModule>.
	 * @return bool|string False if not found, True if found. Error message if
	 * it failed.
	 */
	public static function file_get_ifmodule_block($content, &$off_begin,
		&$off_end)
	{
		$off_begin = stripos($content, self::$RW_BLOCK_START);
		//if not found
		if ($off_begin === false) {
			return false;
		}
		$off_begin += strlen(self::$RW_BLOCK_START);
		$off_end = stripos($content, self::$RW_BLOCK_END, $off_begin);
		$off_next = stripos($content, '<IfModule', $off_begin);
		if ($off_end === false) {
			$buf = sprintf(self::$ERR_NOT_FOUND, 'IfModule close');
			return $buf;
		}
		elseif (($off_next !== false) && ($off_next < $off_end)) {
			$buf = LiteSpeed_Cache_Admin_Display::build_paragraph(
				self::$ERR_WRONG_ORDER,
				sprintf(__('The .htaccess file is missing a %s.', 'litespeed-cache'),
					'&lt;/IfModule&gt;'));
			return $buf;
		}
		--$off_end; // go to end of previous line.
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
	 * @param string $after The content after the relevant section.
	 * @return mixed False on failure, the haystack on success.
	 * The haystack may be a string or null if it did not exist.
	 */
	private function file_split($content, &$buf, &$after)
	{
		$wrapper_end = '';
		$wrapper_begin = $this->build_wrappers(self::$RW_WRAPPER, $wrapper_end);
		$off_begin = 0;
		$off_end = 0;

		$ret = self::file_get_ifmodule_block($content, $off_begin, $off_end);

		if ($ret === false) {
			$buf = self::$RW_BLOCK_START . "\n" . $wrapper_begin
				. self::$RW_PREREQ;
			$after = $wrapper_end . "\n" . self::$RW_BLOCK_END . "\n" . $content;
			return NULL;
		}
		elseif ($ret !== true) {
			$buf = $ret;
			return false;
		}

		$off_wrapper = stripos($content, $wrapper_begin, $off_begin);
		// If the wrapper exists
		if ($off_wrapper !== false) {
			$off_wrapper += strlen($wrapper_begin);
			$off_wrapper_end = stripos($content, $wrapper_end, $off_wrapper);
			if ($off_wrapper_end === false) {
				$buf = sprintf(self::$ERR_NOT_FOUND, 'Plugin wrapper close');
				return false;
			}
			--$off_wrapper_end;

			$buf = substr($content, 0, $off_wrapper + 1);
			$after = substr($content, $off_wrapper_end);
			$block = substr($content, $off_wrapper,
				$off_wrapper_end - $off_wrapper);
			return $block;
		}
		$buf = substr($content, 0, $off_end) . "\n" . $wrapper_begin
			. self::$RW_PREREQ;
		$after = $wrapper_end . substr($content, $off_end);
		$rules = array();
		$matched = preg_replace_callback(self::$RW_PATTERN_WRAPPERS,
			function ($matches) use (&$rules)
			{
				$rules[] = $matches[0];
				return '';
			},
			$buf);
		if (empty($rules)) {
			return NULL;
		}
		$buf = $matched;
		$block = implode('', $rules);
		return $block;
	}

	/**
	 * Complete the validate changes and save to file.
	 *
	 * @since 1.0.7
	 * @access private
	 * @param string $beginning The portion that includes the edits.
	 * @param string $haystack The source section from the original file.
	 * @param string $after The content after the relevant section.
	 * @param string $path If path is set, use path, else use home path.
	 * @return mixed true on success, else error message on failure.
	 */
	private function file_combine($beginning, $haystack, $after, $path = '')
	{
		if (!is_null($haystack)) {
			$beginning .= $haystack;
		}
		$beginning .= $after;
		return self::file_save($beginning, false, $path);
	}

	/**
	 * Try to backup the .htaccess file.
	 * This function will attempt to create a .htaccess_lscachebak_orig first.
	 * If that is already created, it will attempt to create .htaccess_lscachebak_[1-10]
	 * If 10 are already created, zip the current set of backups (sans _orig).
	 * If a zip already exists, overwrite it.
	 *
	 * @since 1.0.10
	 * @access private
	 * @param String $path The .htaccess file path.
	 * @return boolean True on success, else false on failure.
	 */
	private static function file_backup($path)
	{
		$bak = '_lscachebak_orig';
		$i = 1;

		if ( file_exists($path . $bak) ) {
			$bak = sprintf("_lscachebak_%02d", $i);
			while (file_exists($path . $bak)) {
				$i++;
				$bak = sprintf("_lscachebak_%02d", $i);
			}
		}

		if (($i <= 10) || (!class_exists('ZipArchive'))) {
			$ret = copy($path, $path . $bak);
			return $ret;
		}

		$zip = new ZipArchive;
		$dir = dirname($path);
		$res = $zip->open($dir . '/lscache_htaccess_bak.zip',
			ZipArchive::CREATE | ZipArchive::OVERWRITE);
		if ($res === false) {
			error_log('Warning: Failed to archive wordpress backups in ' . $dir);
			$ret = copy($path, $path . $bak);
			return $ret;
		}
		$archived = $zip->addPattern('/\.htaccess_lscachebak_[0-9]+/', $dir);
		$zip->close();
		$bak = '_lscachebak_01';

		if (!empty($archived)) {
			foreach ($archived as $delFile) {
				unlink($delFile);
			}
		}

		$ret = copy($path, $path . $bak);
		return $ret;
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
	 * @since 1.0.12 - Introduce $backup parameter and make function public
	 * @access private
	 * @param string $content The new content to put into the rules file.
	 * @param boolean $cleanup True to strip extra slashes, false otherwise.
	 * @param string $path The file path to edit.
	 * @param boolean $backup Whether to create backups or not.
	 * @return mixed true on success, else error message on failure.
	 */
	public static function file_save($content, $cleanup = true, $path = '',
		$backup = true)
	{
		while (true) {
			if (empty($path)) {
				$path = self::get_home_path();
				if (!file_exists($path)) {
					break;
				}
			}

			if (self::is_file_able(self::RW, $path) == 0) {
				return self::$ERR_READWRITE;
			}

			//failed to backup, not good.
			if (($backup) && (self::file_backup($path) === false)) {
				return self::$ERR_BACKUP;
			}

			break;
		}

		if ($cleanup) {
			$content = LiteSpeed_Cache_Admin::cleanup_text($content);
		}

		// File put contents will truncate by default. Will create file if doesn't exist.
		$ret = file_put_contents($path, $content, LOCK_EX);
		if (!$ret) {
			$err = sprintf(self::$ERR_OVERWRITE, '.htaccess');
			return $err;
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
		$wrap_begin = stripos($content, $wrapper_begin);
		if ($wrap_begin === false) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_end = stripos($content, $wrapper_end,
			$wrap_begin + strlen($wrapper_begin));

		if ($wrap_end === false) {
			$err = sprintf(self::$ERR_NOT_FOUND, 'wrapper end');
			return array(false, $err);
		}
		elseif ($match != '') {
			$output .= $out;
		}
		$buf = substr($content, 0, $wrap_begin); // Remove everything between wrap_begin and wrap_end
		$buf .= substr($content, $wrap_end + strlen($wrapper_end));
		return array(true, trim($buf));
	}

	/**
	 * Finds a specified common rewrite rule from the .htaccess file.
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
		$off_begin = stripos($match, $prefix);
		if ($off_begin === false) {
			$match = '';
			return true; // It does not exist yet, not an error.
		}
		$off_begin += strlen($prefix);
		$off_end = stripos($match, $suffix, $off_begin);
		if ($off_end === false) {
			$match = sprintf(self::$ERR_NOT_FOUND, 'suffix ' . $suffix);
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
			$match = sprintf(self::$ERR_NOT_FOUND, 'a match');
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
		$wrap_begin = stripos($content, $wrapper_begin);
		if ($wrap_begin === false) {
			if ($match != '') {
				$output .= $out;
			}
			return true;
		}
		$wrap_end = stripos($content, $wrapper_end, $wrap_begin + strlen($wrapper_begin));
		if ($wrap_end === false) {
			$err = sprintf(self::$ERR_NOT_FOUND, 'wrapper end');
			return array(false, $err);
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
		$off_begin = stripos($match, $prefix);
		if ($off_begin === false) {
			$match = '';
			return true; // It does not exist yet, not an error.
		}
		$off_begin += strlen($prefix);
		$off_end = stripos($match, $suffix, $off_begin);
		if ($off_end === false) {
			$match = sprintf(self::$ERR_NOT_FOUND, 'suffix ' . $suffix);
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
			$match = sprintf(self::$ERR_NOT_FOUND, 'a match');
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
		$match = self::$RW_PATTERN_RES;
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
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False if there is an error, diff array otherwise.
	 */
	public function check_input($options, $input, &$errors)
	{
		$diff = array();
		$val_check = array(
			LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED,
			LiteSpeed_Cache_Config::OPID_CACHE_FAVICON,
			LiteSpeed_Cache_Config::OPID_CACHE_RES
		);
		$has_error = false;

		foreach ($val_check as $opt) {
			$ret = LiteSpeed_Cache_Admin::parse_checkbox($opt, $input, $input);
			if ($options[$opt] !== $ret) {
				$diff[$opt] = $ret;
			}
		}

		$id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST;
		if ($input[LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED]) {
			$list = $input[$id];
			if ((empty($list)) || (self::check_rewrite($list) === false)) {
				$errors[] = sprintf(self::$ERR_NO_LIST, esc_html($list));
				$has_error = true;
			}
			elseif ($input[$id] !== $options[$id]) {
				$diff[$id] = $list;
			}
		}
		elseif (isset($diff[LiteSpeed_Cache_Config::OPID_MOBILEVIEW_ENABLED])) {
			$diff[$id] = false;
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;
		if ((isset($input[$id])) && ($input[$id])) {
			$cookie_list = preg_replace("/[\r\n]+/", '|', $input[$id]);
		}
		else {
			$cookie_list = '';
		}

		if ((empty($cookie_list)) || (self::check_rewrite($cookie_list))) {
			if ($options[$id] !== $cookie_list) {
				$diff[$id] = $cookie_list;
			}
		}
		else {
			$errors[] = sprintf(self::$ERR_NO_LIST, esc_html($cookie_list));
			$has_error = true;
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		if ((isset($input[$id])) && (self::check_rewrite($input[$id]))) {
			if ($options[$id] !== $input[$id]) {
				$diff[$id] = $input[$id];
			}
		}
		else {
			$errors[] = sprintf(self::$ERR_NO_LIST, esc_html($input[$id]));
			$has_error = true;
		}

		$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE;
		$aExceptions = array('-', '_');
		if ((isset($input[$id])) && ($input[$id] !== $options[$id])) {
			if (($input[$id] === '')
				|| ((ctype_alnum(str_replace($aExceptions, '', $input[$id])))
					&& (self::check_rewrite($input[$id])))) {
				$diff[$id] = $input[$id];
			}
			else {
				$errors[] = sprintf(self::$ERR_INVALID_LOGIN,
					esc_html($input[$id]));
				$has_error = true;
			}
		}

		if ($has_error) {
			return false;
		}
		return $diff;
	}

	/**
	 * Parse rewrite input to check for possible issues (e.g. unescaped spaces).
	 *
	 * Issues tracked:
	 * Starts with |
	 * Ends with |
	 * Double |
	 * Unescaped space
	 * Invalid character (NOT \w, -, \, |, \s, /, ., +, *, (, ))
	 *
	 * @since 1.0.9
	 * @access private
	 * @param String $rule Input rewrite rule.
	 * @return boolean True for valid rules, false otherwise.
	 */
	private static function check_rewrite($rule)
	{
		$escaped = str_replace('@', '\@', $rule);
		return (@preg_match('@' . $escaped . '@', null) !== false);
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
	 * @param array $diff The rules that need to be set
	 * @param string $haystack The original content in the .htaccess file.
	 * @param string $buf The current output buffer for the HOME PATH file.
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False on failure/do not update,
	 *	original content sans login cookie on success.
	 */
	private function set_subdir_cookie($diff, &$haystack, &$buf, &$errors)
	{
		$login_id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE;
		$res_id = LiteSpeed_Cache_Config::OPID_CACHE_RES;
		if (isset($diff[$login_id])) {
			if ($diff[$login_id] !== '') {
				$match = '.?';
				$sub = '-';
				if (is_openlitespeed()) {
					$env = 'E="Cache-Vary:' . $diff[$login_id] . '"';
				}
				else {
					$env = 'E=Cache-Vary:' . $diff[$login_id];
				}
			}
			else {
				$match = '';
				$sub = '';
				$env = '';
			}

			$ret = $this->set_rewrite_rule($haystack, $buf, 'LOGIN COOKIE',
				$match, $sub, $env);
			$this->parse_ret($ret, $haystack, $errors);
		}

		if (!self::is_subdir()) {
			if (isset($diff[$res_id])) {
				$ret = $this->set_cache_resource($haystack, $diff[$res_id],
					$buf, $errors);
				if ($ret !== false) {
					$haystack = $ret;
				}
			}
			return true;
		}

		$path = self::get_site_path();
		$content2 = '';
		$before2 = '';
		$rule_buf2 = "\n";
		if (self::file_get($content2, $path) === false) {
			$errors[] = $content2;
			return false;
		}

		$haystack2 = $this->file_split($content2, $before2, $after2);
		if ($haystack2 === false) {
			$errors[] = $rule_buf2;
			return false;
		}
		if (isset($diff[$login_id])) {
			$ret = $this->set_rewrite_rule($haystack2, $rule_buf2,
				'LOGIN COOKIE', $match, $sub, $env);
			$this->parse_ret($ret, $haystack2, $errors);
		}
		if (isset($diff[$res_id])) {
			$ret = $this->set_cache_resource($haystack2, $diff[$res_id],
				$rule_buf2, $errors);
			if ($ret !== false) {
				$haystack2 = $ret;
			}
		}

		$ret = $this->file_combine($before2, $haystack2 . $rule_buf2,
			$after2, $path);
		if ($ret !== true) {
			$errors[] = self::$ERR_FILESAVE;
			return false;
		}
		return true;
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
		$ret = $this->set_rewrite_rule($content, $rule_buf, $wrapper, $match, $sub,
			$flag);
		if ($ret !== true) {
			return;
		}
		elseif (count($split_rule) == 1) {
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
		if (strpos($split_rule[0], $prefix) !== false) {
			return $split_rule[2];
		}
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
	 * @param array $diff The rules that need to be set.
	 * @param array $errors Returns error messages added if failed.
	 * @return mixed Returns updated options array on success, false otherwise.
	 */
	public function validate_common_rewrites($diff, &$errors)
	{
		$content = '';
		$buf = "\n";
		$before = '';
		$after = '';

		if (self::file_get($content) === false) {
			$errors[] = $content;
			return false;
		}

		$haystack = $this->file_split($content, $before, $after);
		if ($haystack === false) {
			$errors[] = $buf;
			return false;
		}

		if (is_openlitespeed()) {
			$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE;
			if ($diff[$id]) {
				$diff[$id] .= ',wp-postpass_' . COOKIEHASH;
			}
			else {
				$diff[$id] = 'wp-postpass_' . COOKIEHASH;
			}

			$tp_cookies = apply_filters('litespeed_cache_get_vary', array());
			if ((!empty($tp_cookies)) && (is_array($tp_cookies))) {
				$diff[$id] .= ',' . implode(',', $tp_cookies);
			}
		}

		$id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST;

		if (isset($diff[$id])) {
			if ($diff[$id]) {
				$ret = $this->set_common_rule($haystack, $buf, 'MOBILE VIEW',
					'HTTP_USER_AGENT', $diff[$id],
					'E=Cache-Control:vary=ismobile', 'NC');
			}
			else {
				$ret = $this->set_common_rule($haystack, $buf,
						'MOBILE VIEW', '', '', '');
			}
			$this->parse_ret($ret, $haystack, $errors);
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;

		if (isset($diff[$id])) {
			$ret = $this->set_common_rule($haystack, $buf, 'COOKIE',
					'HTTP_COOKIE', $diff[$id], 'E=Cache-Control:no-cache');
			$this->parse_ret($ret, $haystack, $errors);
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS;
		if (isset($diff[$id])) {
			$ret = $this->set_common_rule($haystack, $buf, 'USER AGENT',
					'HTTP_USER_AGENT', $diff[$id], 'E=Cache-Control:no-cache');
			$this->parse_ret($ret, $haystack, $errors);
		}

		if (((isset($diff[LiteSpeed_Cache_Config::OPID_CACHE_RES]))
				|| (isset($diff[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE])))
			&& ($this->set_subdir_cookie($diff, $haystack, $buf, $errors)
				=== false)) {
			return false;
		}

		$id = LiteSpeed_Cache_Config::OPID_CACHE_FAVICON;

		if (isset($diff[$id])) {
			$ret = $this->set_favicon($haystack, $diff[$id], $buf, $errors);
			if ($ret !== false) {
				$haystack = $ret;
			}

		}

		$ret = $this->file_combine($before, $haystack . $buf, $after);
		if ($ret !== true) {
			$errors[] = self::$ERR_FILESAVE;
			return false;
		}
		return $diff;
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

		clearstatcache();
		if (self::file_get($content) === false) {
			return;
		}
		elseif (!self::is_file_able(self::WRITABLE)) {
			return;
		}

		if (empty($wrapper)) {
			$wrapper = self::$RW_WRAPPER;
		}
		$pattern = '/###LSCACHE START ' . $wrapper
			. '###.*###LSCACHE END ' . $wrapper . '###\n?/s';
		$buf = preg_replace($pattern, '', $content);

		self::file_save($buf, false);

		if (!self::is_subdir()) {
			return;
		}
		$site_path = self::get_site_path();
		if (self::file_get($site_content, $site_path) === false) {
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED, $content);
			return;
		}

		if (empty($wrapper)) {
			$wrapper = self::$RW_WRAPPER;
		}
		$pattern = '/###LSCACHE START ' . $wrapper
			. '###.*###LSCACHE END ' . $wrapper . '###\n?/s';
		$site_buf = preg_replace($pattern, '', $site_content);
		self::file_save($site_buf, false, $site_path);

		return;
	}

	/**
	 * Parses the .htaccess buffer when the admin saves changes in the edit
	 *  .htaccess page.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public static function htaccess_editor_save()
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
		self::$ERR_INVALID_LOGIN = __('Invalid login cookie. Invalid characters found: %s',
					'litespeed-cache');
		self::$ERR_NO_LIST = __('Invalid Rewrite List. Empty or invalid rule. Rule: %s', 'litespeed-cache');
		self::$ERR_NOT_FOUND = __('Could not find %s.', 'litespeed-cache');
		self::$ERR_OVERWRITE = __('Failed to overwrite %s.', 'litespeed-cache');
		self::$ERR_PARSE_FILE =
			LiteSpeed_Cache_Admin_Display::build_paragraph(
			__('Tried to parse for existing login cookie.', 'litespeed-cache'),
			sprintf(__('%s file not valid. Please verify contents.',
						'litespeed-cache'), '.htaccess')
			);
		self::$ERR_READWRITE = sprintf(__('%s file not readable or not writable.', 'litespeed-cache'),
			'.htaccess');
		self::$ERR_SUBDIR_MISMATCH_LOGIN =
			LiteSpeed_Cache_Admin_Display::build_paragraph(
			__('This site is a subdirectory install.', 'litespeed-cache'),
			__('Login cookies do not match.', 'litespeed-cache'),
			__('Please remove both and set the login cookie in LiteSpeed Cache advanced settings.',
				'litespeed-cache'));
		self::$ERR_WRONG_ORDER = __('Prefix was found after suffix.', 'litespeed-cache');
	}
}
