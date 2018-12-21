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

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Admin_Rules
{
	private static $_instance ;

	const EDITOR_TEXTAREA_NAME = 'lscwp_ht_editor' ;

	private $frontend_htaccess = null ;
	private $backend_htaccess = null ;
	private $theme_htaccess = null ;// Not used yet
	private $frontend_htaccess_readable = false ;
	private $frontend_htaccess_writable = false ;
	private $backend_htaccess_readable = false ;
	private $backend_htaccess_writable = false ;
	private $theme_htaccess_readable = false ;
	private $theme_htaccess_writable = false ;

	const LS_MODULE_START = '<IfModule LiteSpeed>' ;
	const EXPIRES_MODULE_START = '<IfModule mod_expires.c>' ;
	const LS_MODULE_END = '</IfModule>' ;
	const LS_MODULE_REWRITE_START = '<IfModule mod_rewrite.c>' ;
	const REWRITE_ON = 'RewriteEngine on' ;
	private static $LS_MODULE_REWRITE_ON ;
	const LS_MODULE_DONOTEDIT = "## LITESPEED WP CACHE PLUGIN - Do not edit the contents of this block! ##" ;
	const MARKER = 'LSCACHE' ;
	const MARKER_NONLS = 'NON_LSCACHE' ;
	const MARKER_LOGIN_COOKIE = '### marker LOGIN COOKIE' ;
	const MARKER_MOBILE = '### marker MOBILE' ;
	const MARKER_NOCACHE_COOKIES = '### marker NOCACHE COOKIES' ;
	const MARKER_NOCACHE_USER_AGENTS = '### marker NOCACHE USER AGENTS' ;
	const MARKER_CACHE_RESOURCE = '### marker CACHE RESOURCE' ;
	const MARKER_FAVICON = '### marker FAVICON' ;
	const MARKER_BROWSER_CACHE = '### marker BROWSER CACHE' ;
	const MARKER_MINIFY = '### marker MINIFY' ;
	const MARKER_CORS = '### marker CORS' ;
	const MARKER_WEBP = '### marker WEBP' ;
	const MARKER_DROPQS = '### marker DROPQS' ;
	const MARKER_START = ' start ###' ;
	const MARKER_END = ' end ###' ;

	const RW_PATTERN_RES = '/.*/[^/]*(responsive|css|js|dynamic|loader|fonts)\.php' ;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 * @access   private
	 */
	private function __construct()
	{
		$this->path_set() ;
		// Filter for frontend&backend htaccess path
		$this->frontend_htaccess = apply_filters( 'litespeed_frontend_htaccess', $this->frontend_htaccess ) ;
		$this->backend_htaccess = apply_filters( 'litespeed_backend_htaccess', $this->backend_htaccess ) ;

		clearstatcache() ;

		// frontend .htaccess privilege
		$test_permissions = file_exists($this->frontend_htaccess) ? $this->frontend_htaccess : dirname($this->frontend_htaccess) ;
		if ( is_readable($test_permissions) ) {
			$this->frontend_htaccess_readable = true ;
		}
		if ( is_writable($test_permissions) ) {
			$this->frontend_htaccess_writable = true ;
		}

		self::$LS_MODULE_REWRITE_ON = array(
			self::REWRITE_ON,
			"CacheLookup on",
			"RewriteRule .* - [E=Cache-Control:no-autoflush]",
			"RewriteRule ^min/\w+\.(css|js) - [E=cache-control:no-vary]",
		) ;

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
		$frontend = LiteSpeed_Cache_Router::frontend_path() ;
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
	 * @since  2.9 Used exception for failed reading
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
			throw new Exception( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_RW ) ) ;
		}

		$content = file_get_contents($path) ;
		if ( $content === false ) {
			throw new Exception( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_GET ) ) ;
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
			throw new Exception( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_R ) ) ;
		}

		if ( ! self::writable($kind) ) {
			throw new Exception( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_W ) ) ;
		}

		//failed to backup, not good.
		if ( $backup && $this->htaccess_backup($kind) === false ) {
			 throw new Exception( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_BU ) ) ;
		}

		// File put contents will truncate by default. Will create file if doesn't exist.
		$ret = file_put_contents($path, $content, LOCK_EX) ;
		if ( $ret === false ) {
			throw new Exception( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_SAVE ) ) ;
		}
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

		$res = $zip->open($dir . '/.lscache_htaccess_bak.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) ;
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
		$rules = $this->_get_rule_by(self::MARKER_MOBILE) ;
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
		$rule = $this->_get_rule_by(self::MARKER_LOGIN_COOKIE, $kind) ;
		if( substr($rule, 0, strlen('RewriteRule .? - [E=')) !== 'RewriteRule .? - [E=' ) {//todo: use regex
			return false ;
		}

		$rule_cookie = substr( $rule, strlen( 'RewriteRule .? - [E=' ), -1 ) ;

		if ( LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS' ) {
			return trim( $rule_cookie, '"' ) ;
		}

		return $rule_cookie ;
	}

	/**
	 * Get rewrite rules based on tags
	 * @param  string $cond The tag to be used
	 * @param  string $kind Frontend or backend .htaccess file
	 * @return mixed       Rules
	 */
	private function _get_rule_by($cond, $kind = 'frontend')
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
	 * Generate browser cache rules
	 *
	 * @since  1.3
	 * @access private
	 * @return array Rules set
	 */
	private function _browser_cache_rules( $cfg )
	{
		/**
		 * Add ttl setting
		 * @since 1.6.3
		 */
		$id = LiteSpeed_Cache_Config::OPID_CACHE_BROWSER_TTL ;
		$ttl = $cfg[ $id ] ;
		$rules = array(
			self::EXPIRES_MODULE_START,
			// '<FilesMatch "\.(pdf|ico|svg|xml|jpg|jpeg|png|gif|webp|ogg|mp4|webm|js|css|woff|woff2|ttf|eot)(\.gz)?$">',
				'ExpiresActive on',
				'ExpiresByType application/pdf A' . $ttl,
				'ExpiresByType image/x-icon A' . $ttl,
				'ExpiresByType image/vnd.microsoft.icon A' . $ttl,
				'ExpiresByType image/svg+xml A' . $ttl,
				'',
				'ExpiresByType image/jpg A' . $ttl,
				'ExpiresByType image/jpeg A' . $ttl,
				'ExpiresByType image/png A' . $ttl,
				'ExpiresByType image/gif A' . $ttl,
				'ExpiresByType image/webp A' . $ttl,
				'',
				'ExpiresByType video/ogg A' . $ttl,
				'ExpiresByType audio/ogg A' . $ttl,
				'ExpiresByType video/mp4 A' . $ttl,
				'ExpiresByType video/webm A' . $ttl,
				'',
				'ExpiresByType text/css A' . $ttl,
				'ExpiresByType text/javascript A' . $ttl,
				'ExpiresByType application/javascript A' . $ttl,
				'ExpiresByType application/x-javascript A' . $ttl,
				'',
				'ExpiresByType application/x-font-ttf A' . $ttl,
				'ExpiresByType application/x-font-woff A' . $ttl,
				'ExpiresByType application/font-woff A' . $ttl,
				'ExpiresByType application/font-woff2 A' . $ttl,
				'ExpiresByType application/vnd.ms-fontobject A' . $ttl,
				'ExpiresByType font/ttf A' . $ttl,
				'ExpiresByType font/woff A' . $ttl,
				'ExpiresByType font/woff2 A' . $ttl,
				'',
			// '</FilesMatch>',
			self::LS_MODULE_END,
		) ;
		return $rules ;
	}

	/**
	 * Generate minify rules
	 *
	 * @since  2.1.2
	 * @access private
	 * @return array Rules set
	 */
	private function _minify_rules()
	{
		$rules = array(
			self::LS_MODULE_REWRITE_START,
				self::REWRITE_ON,
				'RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI} ^(.*)/min/(\w+)\.(css|js)$',
				'RewriteCond %1/' . basename( LSCWP_CONTENT_DIR ) . '/cache/$2/$1.$2 -f',
				'RewriteRule min/(\w+)\.(css|js) ' . basename( LSCWP_CONTENT_DIR ) . '/cache/$2/$1.$2 [L]',
			self::LS_MODULE_END,
		) ;
		return $rules ;
	}

	/**
	 * Generate CORS rules for fonts
	 *
	 * @since  1.5
	 * @access private
	 * @return array Rules set
	 */
	private function _cors_rules()
	{
		return array(
			'<FilesMatch "\.(ttf|ttc|otf|eot|woff|woff2|font\.css)$">',
				'<IfModule mod_headers.c>',
					'Header set Access-Control-Allow-Origin "*"',
				'</IfModule>',
			'</FilesMatch>',
		) ;
	}

	/**
	 * Generate rewrite rules based on settings
	 *
	 * @since  1.3
	 * @access private
	 * @param  array $cfg  The settings to be used for rewrite rule
	 * @return array      Rules array
	 */
	private function _generate_rules( $cfg, $disable_lscache_detail_rules = false )
	{
		$new_rules = array() ;
		$new_rules_nonls = array() ;
		$new_rules_backend = array() ;
		$new_rules_backend_nonls = array() ;

		if ( ! $disable_lscache_detail_rules ) {
			// mobile agents
			$id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST ;
			if ( ! empty( $cfg[ LiteSpeed_Cache_Config::OPID_CACHE_MOBILE ] ) && ! empty( $cfg[ $id ] ) ) {
				$new_rules[] = self::MARKER_MOBILE . self::MARKER_START ;
				$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} ' . $cfg[ $id ] . ' [NC]' ;
				$new_rules[] = 'RewriteRule .* - [E=Cache-Control:vary=ismobile]' ;
				$new_rules[] = self::MARKER_MOBILE . self::MARKER_END ;
				$new_rules[] = '' ;
			}

			// nocache cookie
			$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES ;
			if ( ! empty( $cfg[ $id ] ) ) {
				$new_rules[] = self::MARKER_NOCACHE_COOKIES . self::MARKER_START ;
				$new_rules[] = 'RewriteCond %{HTTP_COOKIE} ' . $cfg[ $id ] ;
				$new_rules[] = 'RewriteRule .* - [E=Cache-Control:no-cache]' ;
				$new_rules[] = self::MARKER_NOCACHE_COOKIES . self::MARKER_END ;
				$new_rules[] = '' ;
			}

			// nocache user agents
			$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS ;
			if ( ! empty( $cfg[ $id ] ) ) {
				$new_rules[] = self::MARKER_NOCACHE_USER_AGENTS . self::MARKER_START ;
				$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} ' . $cfg[ $id ] ;
				$new_rules[] = 'RewriteRule .* - [E=Cache-Control:no-cache]' ;
				$new_rules[] = self::MARKER_NOCACHE_USER_AGENTS . self::MARKER_END ;
				$new_rules[] = '' ;
			}

			// caching php resource
			$id = LiteSpeed_Cache_Config::OPID_CACHE_RES ;
			if ( ! empty( $cfg[ $id ] ) ) {
				$new_rules[] = $new_rules_backend[] = self::MARKER_CACHE_RESOURCE . self::MARKER_START ;
				$new_rules[] = $new_rules_backend[] = 'RewriteRule ' . LSCWP_CONTENT_FOLDER . self::RW_PATTERN_RES . ' - [E=cache-control:max-age=3600]' ;
				$new_rules[] = $new_rules_backend[] = self::MARKER_CACHE_RESOURCE . self::MARKER_END ;
				$new_rules[] = $new_rules_backend[] = '' ;
			}

			// check login cookie
			$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE ;

			// Need to keep this due to different behavior of OLS when handling response vary header @Sep/22/2018
			if ( LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS' ) {
				if ( ! empty( $cfg[ $id ] ) ) {
					$cfg[ $id ] .= ',wp-postpass_' . COOKIEHASH ;
				}
				else {
					$cfg[ $id ] = 'wp-postpass_' . COOKIEHASH ;
				}
			}

			$tp_cookies = apply_filters( 'litespeed_cache_api_vary', array() ) ;
			if ( ! empty( $tp_cookies ) && is_array( $tp_cookies ) ) {
				if ( ! empty( $cfg[ $id ] ) ) {
					$cfg[ $id ] .= ',' . implode( ',', $tp_cookies ) ;
				}
				else {
					$cfg[ $id ] = implode( ',', $tp_cookies ) ;
				}
			}
			// frontend and backend
			if ( ! empty( $cfg[ $id ] ) ) {
				$env = 'Cache-Vary:' . $cfg[ $id ] ;
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
			if ( ! empty( $cfg[ $id ] ) ) {
				$new_rules[] = $new_rules_backend[] = self::MARKER_FAVICON . self::MARKER_START ;
				$new_rules[] = $new_rules_backend[] = 'RewriteRule favicon\.ico$ - [E=cache-control:max-age=86400]' ;
				$new_rules[] = $new_rules_backend[] = self::MARKER_FAVICON . self::MARKER_END ;
				$new_rules[] = '' ;
			}

			// CORS font rules
			$id = LiteSpeed_Cache_Config::OPID_CDN ;
			if ( ! empty( $cfg[ $id ] ) ) {
				$new_rules[] = self::MARKER_CORS . self::MARKER_START ;
				$new_rules = array_merge( $new_rules, $this->_cors_rules() ) ;
				$new_rules[] = self::MARKER_CORS . self::MARKER_END ;
				$new_rules[] = '' ;
			}

			// webp support
			$id = LiteSpeed_Cache_Config::OPT_MEDIA_WEBP_REPLACE ;
			if ( ! empty( $cfg[ $id ] ) ) {
				$new_rules[] = self::MARKER_WEBP . self::MARKER_START ;
				$new_rules[] = 'RewriteCond %{HTTP_ACCEPT} "image/webp" [or]' ;
				$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} "Page Speed"' ;
				$new_rules[] = 'RewriteRule .* - [E=Cache-Control:vary=%{ENV:LSCACHE_VARY_VALUE}+webp]' ;
				$new_rules[] = self::MARKER_WEBP . self::MARKER_END ;
				$new_rules[] = '' ;
			}

			// drop qs support
			$id = LiteSpeed_Cache_Config::ITEM_CACHE_DROP_QS ;
			if ( $cfg_info = get_option( $id ) ) {
				$new_rules[] = self::MARKER_DROPQS . self::MARKER_START ;
				foreach ( explode( "\n", $cfg_info ) as $v ) {
					$new_rules[] = 'CacheKeyModify -qs:' . $v ;
				}
				$new_rules[] = self::MARKER_DROPQS . self::MARKER_END ;
				$new_rules[] = '' ;
			}
		}

		// Browser cache
		$id = LiteSpeed_Cache_Config::OPID_CACHE_BROWSER ;
		if ( ! empty( $cfg[ $id ] ) ) {
			$new_rules_nonls[] = $new_rules_backend_nonls[] = self::MARKER_BROWSER_CACHE . self::MARKER_START ;
			$new_rules_nonls = array_merge( $new_rules_nonls, $this->_browser_cache_rules( $cfg ) ) ;
			$new_rules_backend_nonls = array_merge( $new_rules_backend_nonls, $this->_browser_cache_rules( $cfg ) ) ;
			$new_rules_nonls[] = $new_rules_backend_nonls[] = self::MARKER_BROWSER_CACHE . self::MARKER_END ;
			$new_rules_nonls[] = '' ;
		}

		// CSS/JS static file rewrite
		$new_rules_nonls[] = $new_rules_backend_nonls[] = self::MARKER_MINIFY . self::MARKER_START ;
		$new_rules_nonls = array_merge( $new_rules_nonls, $this->_minify_rules() ) ;
		$new_rules_backend_nonls = array_merge( $new_rules_backend_nonls, $this->_minify_rules() ) ;
		$new_rules_nonls[] = $new_rules_backend_nonls[] = self::MARKER_MINIFY . self::MARKER_END ;
		$new_rules_nonls[] = '' ;

		// Add module wrapper for LiteSpeed rules
		if ( $new_rules || $disable_lscache_detail_rules ) {
			$new_rules = $this->_wrap_ls_module( $new_rules ) ;
		}

		if ( $new_rules_backend || $disable_lscache_detail_rules ) {
			$new_rules_backend = $this->_wrap_ls_module( $new_rules_backend ) ;
		}

		return array( $new_rules, $new_rules_backend, $new_rules_nonls, $new_rules_backend_nonls ) ;
	}

	/**
	 * Add LitSpeed module wrapper with rewrite on
	 *
	 * @since  2.1.1
	 * @access private
	 */
	private function _wrap_ls_module( $rules = array() )
	{
		return array_merge(
			array( self::LS_MODULE_START ),
			self::$LS_MODULE_REWRITE_ON,
			array( '' ),
			$rules,
			array( self::LS_MODULE_END )
		) ;
	}

	/**
	 * Insert LitSpeed module wrapper with rewrite on
	 *
	 * @since  2.1.1
	 * @access public
	 */
	public function insert_ls_wrapper()
	{
		$rules = $this->_wrap_ls_module() ;
		return $this->_insert_wrapper( $rules ) ;
	}

	/**
	 * wrap rules with module on info
	 *
	 * @since  1.1.5
	 * @param  array  $rules
	 * @return array        wrapped rules with module info
	 */
	private function _wrap_do_no_edit( $rules )
	{
		// When to clear rules, don't need DONOTEDIT msg
		if ( $rules === false || ! is_array( $rules ) ) {
			return $rules ;
		}

		$rules = array_merge(
			array( self::LS_MODULE_DONOTEDIT ),
			$rules,
			array( self::LS_MODULE_DONOTEDIT )
		) ;

		return $rules ;
	}

	/**
	 * Write to htaccess with rules
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $rules
	 * @param  string $kind  which htaccess
	 */
	private function _insert_wrapper( $rules = array(), $kind = false, $marker = false )
	{
		if ( $kind === false ) {
			$kind = 'frontend' ;
		}

		// Default marker is LiteSpeed marker `LSCACHE`
		if ( $marker === false ) {
			$marker = self::MARKER ;
		}

		$res = $this->htaccess_backup( $kind ) ;
		if ( ! $res ) {
			return false ;
		}

		return Litespeed_File::insert_with_markers( $this->htaccess_path($kind), $this->_wrap_do_no_edit( $rules ), $marker, true ) ;
	}

	/**
	 * Update rewrite rules based on setting
	 *
	 * @since 1.3
	 * @access public
	 * @param array $cfg The rules that need to be set.
	 */
	public function update( $cfg, $disable_lscache_detail_rules = false )
	{
		if ( ! LiteSpeed_Cache_Admin_Rules::readable() ) {
			return LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_R ) ;
		}

		if ( $this->frontend_htaccess !== $this->backend_htaccess ) {
			if ( ! LiteSpeed_Cache_Admin_Rules::readable( 'backend' ) ) {
				return LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_R ) ;
			}
		}

		list( $frontend_rules, $backend_rules, $frontend_rules_nonls, $backend_rules_nonls ) = $this->_generate_rules( $cfg, $disable_lscache_detail_rules ) ;

		// Check frontend content
		list( $rules, $rules_nonls ) = $this->_extract_rules() ;

		// Check Non-LiteSpeed rules
		if ( $this->_wrap_do_no_edit( $frontend_rules_nonls ) != $rules_nonls ) {
			LiteSpeed_Cache_Log::debug( '[Rules] Update non-ls frontend rules' ) ;
			// Need to update frontend htaccess
			if ( ! $this->_insert_wrapper( $frontend_rules_nonls, false, self::MARKER_NONLS ) ) {
				$manual_guide_codes = $this->_rewrite_codes_msg( $this->frontend_htaccess, $frontend_rules_nonls, self::MARKER_NONLS ) ;
				LiteSpeed_Cache_Log::debug( '[Rules] Update Failed' ) ;
				return array( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_W ), $manual_guide_codes ) ;
			}
		}

		// Check LiteSpeed rules
		if ( $this->_wrap_do_no_edit( $frontend_rules ) != $rules ) {
			LiteSpeed_Cache_Log::debug( '[Rules] Update frontend rules' ) ;
			// Need to update frontend htaccess
			if ( ! $this->_insert_wrapper( $frontend_rules ) ) {
				LiteSpeed_Cache_Log::debug( '[Rules] Update Failed' ) ;
				$manual_guide_codes = $this->_rewrite_codes_msg( $this->frontend_htaccess, $frontend_rules ) ;
				return array( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_W ), $manual_guide_codes ) ;
			}
		}

		if ( $this->frontend_htaccess !== $this->backend_htaccess ) {
			list( $rules, $rules_nonls ) = $this->_extract_rules( 'backend' ) ;

			// Check Non-LiteSpeed rules for backend
			if ( $this->_wrap_do_no_edit( $backend_rules_nonls ) != $rules_nonls ) {
				LiteSpeed_Cache_Log::debug( '[Rules] Update non-ls backend rules' ) ;
				// Need to update frontend htaccess
				if ( ! $this->_insert_wrapper( $backend_rules_nonls, 'backend', self::MARKER_NONLS ) ) {
					LiteSpeed_Cache_Log::debug( '[Rules] Update Failed' ) ;
					$manual_guide_codes = $this->_rewrite_codes_msg( $this->backend_htaccess, $backend_rules_nonls, self::MARKER_NONLS ) ;
					return array( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_W ), $manual_guide_codes ) ;
				}
			}

			// Check backend content
			if ( $this->_wrap_do_no_edit( $backend_rules ) != $rules ) {
				LiteSpeed_Cache_Log::debug( '[Rules] Update backend rules' ) ;
				// Need to update backend htaccess
				if ( ! $this->_insert_wrapper( $backend_rules, 'backend' ) ) {
					LiteSpeed_Cache_Log::debug( '[Rules] Update Failed' ) ;
					$manual_guide_codes = $this->_rewrite_codes_msg( $this->backend_htaccess, $backend_rules ) ;
					return array( LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_HTA_W ), $manual_guide_codes ) ;
				}
			}
		}

		return true ;
	}

	/**
	 * Get existing rewrite rules
	 *
	 * @since  1.3
	 * @access private
	 * @param  string $kind Frontend or backend .htaccess file
	 * @return bool|array       False if failed to read, rules array otherwise
	 */
	private function _extract_rules( $kind = 'frontend' )
	{
		clearstatcache() ;
		$path = $this->htaccess_path( $kind ) ;
		if ( ! self::readable( $kind ) ) {
			return false ;
		}

		$rules = Litespeed_File::extract_from_markers( $path, self::MARKER ) ;
		$rules_nonls = Litespeed_File::extract_from_markers( $path, self::MARKER_NONLS ) ;

		return array( $rules, $rules_nonls ) ;
	}

	/**
	 * Output the msg with rules plain data for manual insert
	 *
	 * @since  1.1.5
	 * @param  string  $file
	 * @param  array  $rules
	 * @return string        final msg to output
	 */
	private function _rewrite_codes_msg( $file, $rules, $marker = false )
	{
		return sprintf( __( '<p>Please add/replace the following codes into the beginning of %1$s:</p> %2$s' , 'litespeed-cache' ),
				$file,
				'<textarea style="width:100%;" rows="10" readonly>' . htmlspecialchars( $this->_wrap_rules_with_marker( $rules, $marker ) ) . '</textarea>'
			) ;
	}

	/**
	 * Generate rules plain data for manual insert
	 *
	 * @since  1.1.5
	 * @param  array  $rules
	 * @return array        final rules data for htaccess
	 */
	private function _wrap_rules_with_marker( $rules, $marker = false )
	{
		// Default marker is LiteSpeed marker `LSCACHE`
		if ( $marker === false ) {
			$marker = self::MARKER ;
		}

		$start_marker = "# BEGIN {$marker}" ;
		$end_marker   = "# END {$marker}" ;
		$new_file_data = implode( "\n", array_merge(
			array( $start_marker ),
			$this->_wrap_do_no_edit($rules),
			array( $end_marker )
		) ) ;

		return $new_file_data ;
	}

	/**
	 * Clear the rules file of any changes added by the plugin specifically.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param  string  $clear_all	Deactivation will give true, so clear all rules, otherwise only clear lscache related rules.
	 */
	public function clear_rules()
	{
		$this->_insert_wrapper( false ) ;// Use false to avoid do-not-edit msg
		// Clear non ls rules
		$this->_insert_wrapper( false, false, self::MARKER_NONLS ) ;

		if ( $this->frontend_htaccess !== $this->backend_htaccess ) {
			$this->_insert_wrapper( false, 'backend' ) ;
			$this->_insert_wrapper( false, 'backend', self::MARKER_NONLS ) ;
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
	 * @since  2.9 Used exception when saving
	 * @access public
	 */
	public function htaccess_editor_save()
	{
		if ( ! isset( $_POST[ self::EDITOR_TEXTAREA_NAME ] ) ) {
			return ;
		}

		$content = LiteSpeed_Cache_Admin::cleanup_text($_POST[self::EDITOR_TEXTAREA_NAME]) ;

		try {
			$this->htaccess_save($content) ;
		} catch( \Exception $e ) {
			LiteSpeed_Cache_Admin_Display::error( $e->getMessage() ) ;
			return ;
		}

		LiteSpeed_Cache_Admin_Display::succeed( __( 'File Saved.', 'litespeed-cache' ) ) ;

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
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}

