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
	private static $_instance ;

	const EDITOR_TEXTAREA_NAME = 'lscwp_ht_editor' ;

	private $frontend_htaccess = null ;
	private $backend_htaccess = null ;
	private $theme_htaccess = null ;
	private $frontend_htaccess_readable = false ;
	private $frontend_htaccess_writable = false ;
	private $backend_htaccess_readable = false ;
	private $backend_htaccess_writable = false ;
	private $theme_htaccess_readable = false ;
	private $theme_htaccess_writable = false ;

	private static $RW_LOOKUP ;
	const RW_LOOKUP_PUBLIC = "CacheLookup Public on" ;
	const RW_LOOKUP_BOTH = "CacheLookup on" ;
	const RW_PRIV_BYPASS_POST_PURGE = "RewriteRule .* - [E=Cache-Control:no-autoflush]" ;

	const LS_MODULE_START = '<IfModule LiteSpeed>' ;
	const LS_MODULE_END = '</IfModule>' ;
	private static $LS_MODULE_REWRITE_ON ;
	const LS_MODULE_DONOTEDIT = "## LITESPEED WP CACHE PLUGIN - Do not edit the contents of this block! ##" ;
	const MARKER = 'LSCACHE' ;
	const MARKER_LOGIN_COOKIE = '### marker LOGIN COOKIE' ;
	const MARKER_MOBILE = '### marker MOBILE' ;
	const MARKER_NOCACHE_COOKIES = '### marker NOCACHE COOKIES' ;
	const MARKER_NOCACHE_USER_AGENTS = '### marker NOCACHE USER AGENTS' ;
	const MARKER_CACHE_RESOURCE = '### marker CACHE RESOURCE' ;
	const MARKER_FAVICON = '### marker FAVICON' ;
	const MARKER_START = ' start ###' ;
	const MARKER_END = ' end ###' ;

	const RW_PATTERN_RES = 'wp-content/.*/[^/]*(responsive|css|js|dynamic|loader|fonts)\.php' ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function __construct()
	{
		$this->path_set() ;
		clearstatcache() ;

		// frontend .htaccess privilege
		$test_permissions = file_exists($this->frontend_htaccess) ? $this->frontend_htaccess : dirname($this->frontend_htaccess) ;
		if ( is_readable($test_permissions) ) {
			$this->frontend_htaccess_readable = true ;
		}
		if ( is_writable($test_permissions) ) {
			$this->frontend_htaccess_writable = true ;
		}
		if ( LITESPEED_SERVER_TYPE == 'LITESPEED_SERVER_OLS' ) {
			self::$RW_LOOKUP = self::RW_LOOKUP_PUBLIC ;
		}
		else {
			self::$RW_LOOKUP = self::RW_LOOKUP_BOTH ;
		}
		self::$LS_MODULE_REWRITE_ON = "RewriteEngine on\n" . self::$RW_LOOKUP . "\n" . self::RW_PRIV_BYPASS_POST_PURGE ;

		// backend .htaccess privilege
		if ( $this->frontend_htaccess === $this->backend_htaccess ) {
			$this->backend_htaccess_readable = $this->frontend_htaccess_readable;
			$this->backend_htaccess_writable = $this->frontend_htaccess_writable;
		}
		else{
			$test_permissions = file_exists($this->backend_htaccess) ? $this->backend_htaccess : dirname($this->backend_htaccess);
			if ( is_readable($test_permissions) ) {
				$this->backend_htaccess_readable = true;
			}
			if ( is_writable($test_permissions) ) {
				$this->backend_htaccess_writable = true;
			}
		}
	}

	/**
	 * Get if htaccess file is readable
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public static function readable($kind = 'frontend')
	{
		if( $kind === 'frontend' ) {
			return self::get_instance()->frontend_htaccess_readable ;
		}
		if( $kind === 'backend' ) {
			return self::get_instance()->backend_htaccess_readable ;
		}
	}

	/**
	 * Get if htaccess file is writable
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public static function writable($kind = 'frontend')
	{
		if( $kind === 'frontend' ) {
			return self::get_instance()->frontend_htaccess_writable ;
		}
		if( $kind === 'backend' ) {
			return self::get_instance()->backend_htaccess_writable ;
		}
	}

	/**
	 * Get frontend htaccess path
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public static function get_frontend_htaccess()
	{
		return self::get_instance()->frontend_htaccess ;
	}

	/**
	 * Get backend htaccess path
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public static function get_backend_htaccess()
	{
		return self::get_instance()->backend_htaccess ;
	}

	/**
	 * Check to see if .htaccess exists starting at $start_path and going up directories until it hits DOCUMENT_ROOT.
	 *
	 * As dirname() strips the ending '/', paths passed in must exclude the final '/'
	 *
	 * If can't find, return false
	 *
	 * @since 1.0.11
	 * @access private
	 * @param string $start_path The first directory level to search.
	 * @return string The deepest path where .htaccess exists, False if not.
	 */
	private function htaccess_search( $start_path )
	{
		while ( ! file_exists( $start_path . '/.htaccess' ) ) {
			if ( $start_path === '/' || ! $start_path ) {
				return false ;
			}
			if ( ! empty( $_SERVER[ 'DOCUMENT_ROOT' ] ) && $start_path === $_SERVER[ 'DOCUMENT_ROOT' ] ) {
				return false ;
			}
			$start_path = dirname( $start_path ) ;
		}

		return $start_path ;
	}

	/**
	 * Set the path class variables.
	 *
	 * @since 1.0.11
	 * @access private
	 */
	private function path_set()
	{
		$this->theme_htaccess = LSWCP_CONTENT_DIR ;

		$frontend = rtrim( get_home_path(), '/' ) ; // /home/user/public_html/frontend
		// get home path failed. Trac ticket #37668 (e.g. frontend:/blog backend:/wordpress)
		if ( ! $frontend ) {
			$frontend = parse_url( get_option( 'home' ) ) ;
			$frontend = ! empty( $frontend[ 'path' ] ) ? $frontend[ 'path' ] : '' ;
			$frontend = $_SERVER["DOCUMENT_ROOT"] . $frontend ;
		}
		$frontend = realpath( $frontend ) ;
		$frontend_htaccess_search = $this->htaccess_search( $frontend ) ;// The existing .htaccess path to be used for frontend .htaccess
		$this->frontend_htaccess = ( $frontend_htaccess_search ?: $frontend ) . '/.htaccess' ;

		$backend = realpath( ABSPATH ) ; // /home/user/public_html/backend/
		if ( $frontend == $backend ) {
			$this->backend_htaccess = $this->frontend_htaccess ;
			return ;
		}

		// Backend is a different path
		$backend_htaccess_search = $this->htaccess_search( $backend ) ;
		// Found affected .htaccess
		if ( $backend_htaccess_search ) {
			$this->backend_htaccess = $backend_htaccess_search . '/.htaccess' ;
			return ;
		}

		// Frontend path is the parent of backend path
		if ( stripos( $backend, $frontend . '/' ) === 0 ) {
			// backend use frontend htaccess
			$this->backend_htaccess = $this->frontend_htaccess ;
			return ;
		}

		$this->backend_htaccess = $backend . '/.htaccess' ;
	}

	/**
	 * Get corresponding htaccess path
	 *
	 * @since 1.1.0
	 * @param  string $kind Frontend or backend
	 * @return string       Path
	 */
	public function htaccess_path($kind = 'frontend')
	{
		switch ( $kind ) {
			case 'frontend':
				$path = $this->frontend_htaccess ;
				break ;

			case 'backend':
				$path = $this->backend_htaccess ;
				break ;

			default:
				$path = $this->frontend_htaccess ;
				break ;
		}
		return $path ;
	}

	/**
	 * Get the content of the rules file.
	 * If can't read, will add error msg to dashboard
	 * Only when need to add error msg, this function is used, otherwise use file_get_contents directly
	 *
	 * @since 1.0.4
	 * @access public
	 * @param string $path The path to get the content from.
	 * @return boolean True if succeeded, false otherwise.
	 */
	public function htaccess_read($kind = 'frontend')
	{
		$path = $this->htaccess_path($kind) ;

		if( ! $path || ! file_exists($path) ) {
			return "\n" ;
		}
		if ( ! self::readable($kind) || ! self::writable($kind) ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_RW) ;
			return false ;
		}

		$content = file_get_contents($path) ;
		if ( $content === false ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_GET) ;
			return false ;
		}

		// Remove ^M characters.
		$content = str_ireplace("\x0D", "", $content) ;
		return $content ;
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
	 * @since 1.0.4
	 * @since 1.0.12 - Introduce $backup parameter and make function public
	 * @access public
	 * @param string $content The new content to put into the rules file.
	 * @param string $kind The htaccess to edit. Default is frontend htaccess file.
	 * @param boolean $backup Whether to create backups or not.
	 * @return boolean true on success, else false.
	 */
	public function htaccess_save($content, $kind = 'frontend', $backup = true)
	{
		$path = $this->htaccess_path($kind) ;

		if ( ! self::readable($kind) ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_R) ;
			return false ;
		}

		if ( ! self::writable($kind) ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_W) ;
			return false ;
		}

		//failed to backup, not good.
		if ( $backup && $this->htaccess_backup($kind) === false ) {
			 LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_BU) ;
			 return false ;
		}

		// File put contents will truncate by default. Will create file if doesn't exist.
		$ret = file_put_contents($path, $content, LOCK_EX) ;
		if ( $ret === false ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_SAVE) ;
			return false ;
		}

		return true ;
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
	 * @param string $kind The htaccess to edit. Default is frontend htaccess file.
	 * @return boolean True on success, else false on failure.
	 */
	private function htaccess_backup($kind = 'frontend')
	{
		$path = $this->htaccess_path($kind) ;
		$bak = '_lscachebak_orig' ;
		$i = 1 ;

		if ( ! file_exists($path) ) {
			return true ;
		}

		if ( file_exists($path . $bak) ) {
			$bak = sprintf("_lscachebak_%02d", $i) ;
			while (file_exists($path . $bak)) {
				$i++ ;
				$bak = sprintf("_lscachebak_%02d", $i) ;
			}
		}

		if ( $i <= 10 || ! class_exists('ZipArchive') ) {
			$ret = copy($path, $path . $bak) ;
			return $ret ;
		}

		$zip = new ZipArchive ;
		$dir = dirname($path) ;
		$arr = scandir($dir) ;
		$parsed = preg_grep('/\.htaccess_lscachebak_[0-9]+/', $arr) ;

		if ( empty($parsed) ) {
			return false ;
		}

		$res = $zip->open($dir . '/lscache_htaccess_bak.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) ;
		if ( $res !== true ) {
			error_log('Warning: Failed to archive wordpress backups in ' . $dir) ;
			$ret = copy($path, $path . $bak) ;
			return $ret ;
		}

		foreach ($parsed as $key => $val) {
			$parsed[$key] = $dir . '/' . $val ;
			if ( ! $zip->addFile($parsed[$key], $val) ) {
				error_log('Warning: Failed to archive backup file ' . $val) ;
				$zip->close() ;
				$ret = copy($path, $path . $bak) ;
				return $ret ;
			}
		}

		$ret = $zip->close() ;
		if ( ! $ret ) {
			error_log('Warning: Failed to close archive.') ;
			return $ret ;
		}
		$bak = '_lscachebak_01' ;

		foreach ($parsed as $delFile) {
			unlink($delFile) ;
		}

		$ret = copy($path, $path . $bak) ;
		return $ret ;
	}

	/**
	 * Get mobile view rule from htaccess file
	 *
	 * @since 1.1.0
	 * @return string Mobile Agents value
	 */
	public function get_rewrite_rule_mobile_agents()
	{
		$rules = $this->get_rewrite_rule(self::MARKER_MOBILE) ;
		if( ! isset($rules[0]) ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_DNF, self::MARKER_MOBILE) ;
			return false ;
		}
		$rule = trim($rules[0]) ;
		$pattern = '/RewriteCond\s%{HTTP_USER_AGENT}\s+([^[\n]*)\s+[[]*/' ;
		$matches = array() ;
		$num_matches = preg_match($pattern, $rule, $matches) ;
		if ( $num_matches === false ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_HTA_DNF, 'a match') ;
			return false ;
		}
		$match = trim($matches[1]) ;
		return $match ;
	}

	/**
	 * Parse rewrites rule from the .htaccess file.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param string $kind The kind of htaccess to search in
	 * @return array
	 */
	public function get_rewrite_rule_login_cookie($kind = 'frontend')
	{
		$rule = $this->get_rewrite_rule(self::MARKER_LOGIN_COOKIE, $kind) ;
		if( substr($rule, 0, strlen('RewriteRule .? - [E=')) !== 'RewriteRule .? - [E=' ) {//todo: use regex
			return false ;
		}

		return substr($rule, strlen('RewriteRule .? - [E='), -1) ;//todo:user trim('"')
	}

	/**
	 * Get rewrite rules based on tags
	 * @param  string $cond The tag to be used
	 * @param  string $kind Frontend or backend .htaccess file
	 * @return mixed       Rules
	 */
	private function get_rewrite_rule($cond, $kind = 'frontend')
	{
		clearstatcache() ;
		$path = $this->htaccess_path($kind) ;
		if ( ! self::readable($kind) ) {
			return false ;
		}

		$rules = Litespeed_File::extract_from_markers($path, self::MARKER) ;
		if( ! in_array($cond . self::MARKER_START, $rules) || ! in_array($cond . self::MARKER_END, $rules) ) {
			return false ;
		}

		$key_start = array_search($cond . self::MARKER_START, $rules) ;
		$key_end = array_search($cond . self::MARKER_END, $rules) ;
		if( $key_start === false || $key_end === false ) {
			return false ;
		}

		$results = array_slice($rules, $key_start+1, $key_end-$key_start-1) ;
		if( ! $results ) {
			return false ;
		}
		if( count($results) == 1 ) {
			return trim($results[0]) ;
		}
		return array_filter($results) ;
	}

	/**
	 * Parses the input to see if there is a need to edit the .htaccess file.
	 *
	 * @since 1.0.8
	 * @access public
	 * @param array $options The current options
	 * @param array $input The input
	 * @param array $errors Errors array to add error messages to.
	 * @return mixed False if there is an error, diff array otherwise.
	 */
	public function check_input_for_rewrite($options, $input, &$errors)
	{
		$diff = array() ;
		$val_check = array(
			LiteSpeed_Cache_Config::OPID_CACHE_MOBILE,
			LiteSpeed_Cache_Config::OPID_CACHE_FAVICON,
			LiteSpeed_Cache_Config::OPID_CACHE_RES
		) ;
		$has_error = false ;

		foreach ($val_check as $opt) {
			$input[$opt] = LiteSpeed_Cache_Admin_Settings::parse_onoff( $input, $opt ) ;
			if ( $input[$opt] || $options[$opt] != $input[$opt] ) {
				$diff[$opt] = $input[$opt] ;
			}
		}

		// check mobile agents
		$id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST ;
		if ( $input[LiteSpeed_Cache_Config::OPID_CACHE_MOBILE] ) {
			$list = $input[$id] ;
			if ( empty($list) || $this->check_rewrite($list) === false ) {
				$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array($id, empty($list) ? 'EMPTY' : esc_html($list))
				) ;
				$has_error = true ;
			}
			$diff[$id] = $list ;
		}
		elseif ( isset($diff[LiteSpeed_Cache_Config::OPID_CACHE_MOBILE]) ) {
			$diff[$id] = false ;
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES ;
		if ( isset($input[$id]) && $input[$id] ) {
			$cookie_list = preg_replace("/[\r\n]+/", '|', $input[$id]) ;
		}
		else {
			$cookie_list = '' ;
		}

		if ( empty($cookie_list) || $this->check_rewrite($cookie_list) ) {
			$diff[$id] = $cookie_list ;
		}
		else {
			$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array($id, esc_html($cookie_list))) ;
			$has_error = true ;
		}

		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS ;
		if ( isset($input[$id]) && $this->check_rewrite($input[$id]) ) {
			$diff[$id] = $input[$id] ;
		}
		else {
			$err_args = array($id) ;
			if ( ! isset($input[$id]) || empty($input[$id]) ) {
				$err_args[] = 'EMPTY' ;
			}
			else {
				$err_args[] = esc_html($input[$id]) ;
			}
			$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, $err_args) ;
			$has_error = true ;
		}

		$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE ;
		$aExceptions = array('-', '_') ;
		if ( isset($input[$id]) ) {
			if ( $input[$id] === '' || (ctype_alnum(str_replace($aExceptions, '', $input[$id])) && $this->check_rewrite($input[$id])) ) {
				$diff[$id] = $input[$id] ;
			}
			else {
				$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_SETTING_LC, esc_html($input[$id])) ;
				$has_error = true ;
			}
		}

		if ( $has_error ) {
			return false ;
		}
		return $diff ;
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
	private function check_rewrite($rule)
	{
		$escaped = str_replace('@', '\@', $rule) ;
		return @preg_match('@' . $escaped . '@', null) !== false ;//todo: improve to try catch
	}

	/**
	 * Validate common rewrite rules configured by the admin.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param array $diff The rules that need to be set.
	 * @param array $errors Returns error messages added if failed.
	 * @return mixed Returns updated options array on success, false otherwise.
	 */
	public function validate_common_rewrites($diff, &$errors)
	{
		if ( ! self::readable() ) {
			$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_HTA_R) ;
			return false ;
		}

		if ( $this->frontend_htaccess !== $this->backend_htaccess ) {
			if ( ! self::readable('backend') ) {
				$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_HTA_R) ;
				return false ;
			}
		}

		// check login cookie
		if ( LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS' ) {
			$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE ;
			if ( $diff[$id] ) {
				$diff[$id] .= ',wp-postpass_' . COOKIEHASH ;
			}
			else {
				$diff[$id] = 'wp-postpass_' . COOKIEHASH ;
			}

			$tp_cookies = apply_filters('litespeed_cache_api_vary', array()) ;
			if ( ! empty($tp_cookies) && is_array($tp_cookies) ) {
				$diff[$id] .= ',' . implode(',', $tp_cookies) ;
			}
		}

		$new_rules = array() ;
		$new_rules_backend = array() ;
		// mobile agents
		$id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST ;
		if ( isset($diff[$id]) && $diff[$id] ) {
			$new_rules[] = self::MARKER_MOBILE . self::MARKER_START ;
			$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} ' . $diff[$id] . ' [NC]' ;
			$new_rules[] = 'RewriteRule .* - [E=Cache-Control:vary=ismobile]' ;
			$new_rules[] = self::MARKER_MOBILE . self::MARKER_END ;
			$new_rules[] = '' ;
		}

		// nocache cookie
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES ;
		if ( isset($diff[$id]) && $diff[$id] ) {
			$new_rules[] = self::MARKER_NOCACHE_COOKIES . self::MARKER_START ;
			$new_rules[] = 'RewriteCond %{HTTP_COOKIE} ' . $diff[$id] ;
			$new_rules[] = 'RewriteRule .* - [E=Cache-Control:no-cache]' ;
			$new_rules[] = self::MARKER_NOCACHE_COOKIES . self::MARKER_END ;
			$new_rules[] = '' ;
		}

		// nocache user agents
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS ;
		if ( isset($diff[$id]) && $diff[$id] ) {
			$new_rules[] = self::MARKER_NOCACHE_USER_AGENTS . self::MARKER_START ;
			$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} ' . $diff[$id] ;
			$new_rules[] = 'RewriteRule .* - [E=Cache-Control:no-cache]' ;
			$new_rules[] = self::MARKER_NOCACHE_USER_AGENTS . self::MARKER_END ;
			$new_rules[] = '' ;
		}

		// caching php resource
		$id = LiteSpeed_Cache_Config::OPID_CACHE_RES ;
		if ( isset($diff[$id]) && $diff[$id] ) {
			$new_rules[] = $new_rules_backend[] = self::MARKER_CACHE_RESOURCE . self::MARKER_START ;
			$new_rules[] = $new_rules_backend[] = 'RewriteRule ' . self::RW_PATTERN_RES . ' - [E=cache-control:max-age=3600]' ;
			$new_rules[] = $new_rules_backend[] = self::MARKER_CACHE_RESOURCE . self::MARKER_END ;
			$new_rules[] = $new_rules_backend[] = '' ;
		}

		// login cookie
		// frontend and backend
		$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE ;
		if ( isset($diff[$id]) && $diff[$id] ) {
			$env = 'Cache-Vary:' . $diff[$id] ;
			if ( LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS' ) {
				$env = '"' . $env . '"' ;
			}
			$new_rules[] = $new_rules_backend[] = self::MARKER_LOGIN_COOKIE . self::MARKER_START ;
			$new_rules[] = $new_rules_backend[] = 'RewriteRule .? - [E=' . $env . ']' ;
			$new_rules[] = $new_rules_backend[] = self::MARKER_LOGIN_COOKIE . self::MARKER_END ;
			$new_rules[] = '' ;
		}

		// favicon
		// frontend and backend
		$id = LiteSpeed_Cache_Config::OPID_CACHE_FAVICON ;
		if ( isset($diff[$id]) && $diff[$id] ) {
			$new_rules[] = $new_rules_backend[] = self::MARKER_FAVICON . self::MARKER_START ;
			$new_rules[] = $new_rules_backend[] = 'RewriteRule favicon\.ico$ - [E=cache-control:max-age=86400]' ;
			$new_rules[] = $new_rules_backend[] = self::MARKER_FAVICON . self::MARKER_END ;
			$new_rules[] = '' ;
		}

		// todo: is this still needed?
//        if (!is_null($haystack)) {
//			if ( LITESPEED_SERVER_TYPE !== 'LITESPEED_SERVER_OLS' ) {
//				$haystack = str_replace(self::RW_LOOKUP_PUBLIC,
//					self::RW_LOOKUP_BOTH, $haystack) ;
//			}
//			$beginning .= $haystack ;
//        }

		$this->deprecated_clear_rules() ;
		if ( ! $this->insert_wrapper($new_rules) ) {
			$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_HTA_W) ;
			$errors[] = $this->rewrite_codes_msg( $this->frontend_htaccess, $new_rules ) ;
			return false ;
		}
		if ( $this->frontend_htaccess !== $this->backend_htaccess ) {
			if ( ! $this->insert_wrapper($new_rules_backend, 'backend') ) {
				$errors[] = LiteSpeed_Cache_Admin_Display::get_error(LiteSpeed_Cache_Admin_Error::E_HTA_W) ;
				$errors[] = $this->rewrite_codes_msg( $this->backend_htaccess, $new_rules ) ;
				return false ;
			}
		}
		return $diff ;
	}

	/**
	 * Output the msg with rules plain data for manual insert
	 *
	 * @since  1.1.5
	 * @param  string  $file
	 * @param  array  $rules
	 * @return string        final msg to output
	 */
	private function rewrite_codes_msg( $file, $rules )
	{
		return sprintf( __( '<p>Please add/replace the following codes into the beginning of %1$s:</p> %2$s' , 'litespeed-cache' ),
				$file,
				'<textarea style="width:100%;" rows="10" readonly>' . htmlspecialchars( $this->wrap_rules_with_marker( $rules ) ) . '</textarea>'
			) ;
	}

	/**
	 * Generate rules plain data for manual insert
	 *
	 * @since  1.1.5
	 * @param  array  $rules
	 * @return array        final rules data for htaccess
	 */
	private function wrap_rules_with_marker( $rules )
	{
		$marker = self::MARKER ;
		$start_marker = "# BEGIN {$marker}" ;
		$end_marker   = "# END {$marker}" ;
		$new_file_data = implode( "\n", array_merge(
			array( $start_marker ),
			$this->wrap_rules($rules),
			array( $end_marker )
		) ) ;

		return $new_file_data ;
	}

	/**
	 * wrap rules with module on info
	 *
	 * @since  1.1.5
	 * @param  array  $rules
	 * @return array        wrapped rules with module info
	 */
	private function wrap_rules( $rules )
	{
		if ( $rules !== false ) {
			$rules = array_merge(
				array(self::LS_MODULE_DONOTEDIT),
				array(self::LS_MODULE_START),
				array(self::$LS_MODULE_REWRITE_ON),
				array(''),
				$rules,
				array(self::LS_MODULE_END),
				array(self::LS_MODULE_DONOTEDIT)
			) ;
		}
		return $rules ;
	}

	/**
	 * Write to htaccess with rules
	 *
	 * @since  1.1.0
	 * @param  array $rules
	 * @param  string $kind  which htaccess
	 */
	public function insert_wrapper( $rules = array(), $kind = 'frontend' )
	{
		$res = $this->htaccess_backup( $kind ) ;
		if ( ! $res ) {
			return false ;
		}

		return Litespeed_File::insert_with_markers( $this->htaccess_path($kind), $this->wrap_rules( $rules ), self::MARKER, true ) ;
	}

	/**
	 * Clear the rules file of any changes added by the plugin specifically.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function clear_rules( $clear_all = false )
	{
		$keep_wrapper = $clear_all === true ? false : array() ;
		$this->deprecated_clear_rules() ;
		$this->insert_wrapper( $keep_wrapper ) ;
		if ( $this->frontend_htaccess !== $this->backend_htaccess ) {
			$this->insert_wrapper( $keep_wrapper, 'backend' ) ;
		}
	}

	/**
	 * Only used to clear old rules when upgrade to v1.1.0
	 */
	public function deprecated_clear_rules()
	{
		$RW_WRAPPER = 'PLUGIN - Do not edit the contents of this block!' ;
		$pattern = '/###LSCACHE START ' . $RW_WRAPPER . '###.*###LSCACHE END ' . $RW_WRAPPER . '###\n?/s' ;
		clearstatcache() ;
		if ( ! file_exists($this->frontend_htaccess) || ! self::writable() ) {
			return ;
		}
		$content = file_get_contents($this->frontend_htaccess) ;
		if( ! $content ) {
			return ;
		}

		$buf = preg_replace($pattern, '', $content) ;
		$buf = preg_replace("|<IfModule LiteSpeed>\s*</IfModule>|isU", '', $buf) ;

		$this->htaccess_save($buf) ;

		// clear backend htaccess
		if ( $this->frontend_htaccess === $this->backend_htaccess ) {
			return ;
		}

		if ( ! file_exists($this->backend_htaccess) || ! self::writable('backend') ) {
			return ;
		}
		$content = file_get_contents($this->backend_htaccess) ;
		if( ! $content ) {
			return ;
		}

		$buf = preg_replace($pattern, '', $content) ;
		$buf = preg_replace("|<IfModule LiteSpeed>\n*</IfModule>|isU", '', $buf) ;
		$this->htaccess_save($buf, 'backend') ;
	}

	/**
	 * Parses the .htaccess buffer when the admin saves changes in the edit .htaccess page.
	 * Only admin can do this
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function htaccess_editor_save()
	{
		if ( isset($_POST[self::EDITOR_TEXTAREA_NAME]) ) {
			$content = LiteSpeed_Cache_Admin::cleanup_text($_POST[self::EDITOR_TEXTAREA_NAME]) ;
			$msg = $this->htaccess_save($content) ;
			if ( $msg === true ) {
				$msg = __('File Saved.', 'litespeed-cache') ;
				$color = LiteSpeed_Cache_Admin_Display::NOTICE_GREEN ;
			}
			else {
				$color = LiteSpeed_Cache_Admin_Display::NOTICE_RED ;
			}
			LiteSpeed_Cache_Admin_Display::add_notice($color, $msg) ;
		}

	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		$cls = get_called_class() ;
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new $cls() ;
		}

		return self::$_instance ;
	}
}

