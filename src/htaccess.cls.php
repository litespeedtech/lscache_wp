<?php
// phpcs:ignoreFile

/**
 * The htaccess rewrite rule operation class
 *
 * @since      1.0.0
 * @package    LiteSpeed
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Htaccess extends Root {

	private $frontend_htaccess          = null;
	private $_default_frontend_htaccess = null;
	private $backend_htaccess           = null;
	private $_default_backend_htaccess  = null;
	private $theme_htaccess             = null; // Not used yet
	private $frontend_htaccess_readable = false;
	private $frontend_htaccess_writable = false;
	private $backend_htaccess_readable  = false;
	private $backend_htaccess_writable  = false;
	private $theme_htaccess_readable    = false;
	private $theme_htaccess_writable    = false;
	private $__rewrite_on;

	const LS_MODULE_START            = '<IfModule LiteSpeed>';
	const EXPIRES_MODULE_START       = '<IfModule mod_expires.c>';
	const LS_MODULE_END              = '</IfModule>';
	const LS_MODULE_REWRITE_START    = '<IfModule mod_rewrite.c>';
	const REWRITE_ON                 = 'RewriteEngine on';
	const LS_MODULE_DONOTEDIT        = '## LITESPEED WP CACHE PLUGIN - Do not edit the contents of this block! ##';
	const MARKER                     = 'LSCACHE';
	const MARKER_NONLS               = 'NON_LSCACHE';
	const MARKER_LOGIN_COOKIE        = '### marker LOGIN COOKIE';
	const MARKER_ASYNC               = '### marker ASYNC';
	const MARKER_CRAWLER             = '### marker CRAWLER';
	const MARKER_MOBILE              = '### marker MOBILE';
	const MARKER_NOCACHE_COOKIES     = '### marker NOCACHE COOKIES';
	const MARKER_NOCACHE_USER_AGENTS = '### marker NOCACHE USER AGENTS';
	const MARKER_CACHE_RESOURCE      = '### marker CACHE RESOURCE';
	const MARKER_BROWSER_CACHE       = '### marker BROWSER CACHE';
	const MARKER_MINIFY              = '### marker MINIFY';
	const MARKER_CORS                = '### marker CORS';
	const MARKER_WEBP                = '### marker WEBP';
	const MARKER_DROPQS              = '### marker DROPQS';
	const MARKER_START               = ' start ###';
	const MARKER_END                 = ' end ###';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.7
	 */
	public function __construct() {
		$this->_path_set();
		$this->_default_frontend_htaccess = $this->frontend_htaccess;
		$this->_default_backend_htaccess  = $this->backend_htaccess;

		$frontend_htaccess = defined('LITESPEED_CFG_HTACCESS') ? constant('LITESPEED_CFG_HTACCESS') : false;
		if ($frontend_htaccess && substr($frontend_htaccess, -10) === '/.htaccess') {
			$this->frontend_htaccess = $frontend_htaccess;
		}
		$backend_htaccess = defined('LITESPEED_CFG_HTACCESS_BACKEND') ? constant('LITESPEED_CFG_HTACCESS_BACKEND') : false;
		if ($backend_htaccess && substr($backend_htaccess, -10) === '/.htaccess') {
			$this->backend_htaccess = $backend_htaccess;
		}

		// Filter for frontend&backend htaccess path
		$this->frontend_htaccess = apply_filters('litespeed_frontend_htaccess', $this->frontend_htaccess);
		$this->backend_htaccess  = apply_filters('litespeed_backend_htaccess', $this->backend_htaccess);

		clearstatcache();

		// frontend .htaccess privilege
		$test_permissions = file_exists($this->frontend_htaccess) ? $this->frontend_htaccess : dirname($this->frontend_htaccess);
		if (is_readable($test_permissions)) {
			$this->frontend_htaccess_readable = true;
		}
		if (is_writable($test_permissions)) {
			$this->frontend_htaccess_writable = true;
		}

		$this->__rewrite_on = array(
			self::REWRITE_ON,
			'CacheLookup on',
			'RewriteRule .* - [E=Cache-Control:no-autoflush]',
			'RewriteRule ' . preg_quote(LITESPEED_DATA_FOLDER) . '/debug/.*\.log$ - [F,L]',
			'RewriteRule ' . preg_quote(self::CONF_FILE) . ' - [F,L]',
		);

		// backend .htaccess privilege
		if ($this->frontend_htaccess === $this->backend_htaccess) {
			$this->backend_htaccess_readable = $this->frontend_htaccess_readable;
			$this->backend_htaccess_writable = $this->frontend_htaccess_writable;
		} else {
			$test_permissions = file_exists($this->backend_htaccess) ? $this->backend_htaccess : dirname($this->backend_htaccess);
			if (is_readable($test_permissions)) {
				$this->backend_htaccess_readable = true;
			}
			if (is_writable($test_permissions)) {
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
	private function _readable( $kind = 'frontend' ) {
		if ($kind === 'frontend') {
			return $this->frontend_htaccess_readable;
		}
		if ($kind === 'backend') {
			return $this->backend_htaccess_readable;
		}
	}

	/**
	 * Get if htaccess file is writable
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function writable( $kind = 'frontend' ) {
		if ($kind === 'frontend') {
			return $this->frontend_htaccess_writable;
		}
		if ($kind === 'backend') {
			return $this->backend_htaccess_writable;
		}
	}

	/**
	 * Get frontend htaccess path
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public static function get_frontend_htaccess( $show_default = false ) {
		if ($show_default) {
			return self::cls()->_default_frontend_htaccess;
		}
		return self::cls()->frontend_htaccess;
	}

	/**
	 * Get backend htaccess path
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public static function get_backend_htaccess( $show_default = false ) {
		if ($show_default) {
			return self::cls()->_default_backend_htaccess;
		}
		return self::cls()->backend_htaccess;
	}

	/**
	 * Check to see if .htaccess exists starting at $start_path and going up directories until it hits DOCUMENT_ROOT.
	 *
	 * As dirname() strips the ending '/', paths passed in must exclude the final '/'
	 *
	 * @since 1.0.11
	 * @access private
	 */
	private function _htaccess_search( $start_path ) {
		while (!file_exists($start_path . '/.htaccess')) {
			if ($start_path === '/' || !$start_path) {
				return false;
			}

			if (!empty($_SERVER['DOCUMENT_ROOT']) && wp_normalize_path($start_path) === wp_normalize_path($_SERVER['DOCUMENT_ROOT'])) {
				return false;
			}

			if (dirname($start_path) === $start_path) {
				return false;
			}

			$start_path = dirname($start_path);
		}

		return $start_path;
	}

	/**
	 * Set the path class variables.
	 *
	 * @since 1.0.11
	 * @access private
	 */
	private function _path_set() {
		$frontend                 = Router::frontend_path();
		$frontend_htaccess_search = $this->_htaccess_search($frontend); // The existing .htaccess path to be used for frontend .htaccess
		$this->frontend_htaccess  = ($frontend_htaccess_search ?: $frontend) . '/.htaccess';

		$backend = realpath(ABSPATH); // /home/user/public_html/backend/
		if ($frontend == $backend) {
			$this->backend_htaccess = $this->frontend_htaccess;
			return;
		}

		// Backend is a different path
		$backend_htaccess_search = $this->_htaccess_search($backend);
		// Found affected .htaccess
		if ($backend_htaccess_search) {
			$this->backend_htaccess = $backend_htaccess_search . '/.htaccess';
			return;
		}

		// Frontend path is the parent of backend path
		if (stripos($backend, $frontend . '/') === 0) {
			// backend use frontend htaccess
			$this->backend_htaccess = $this->frontend_htaccess;
			return;
		}

		$this->backend_htaccess = $backend . '/.htaccess';
	}

	/**
	 * Get corresponding htaccess path
	 *
	 * @since 1.1.0
	 * @param  string $kind Frontend or backend
	 * @return string       Path
	 */
	public function htaccess_path( $kind = 'frontend' ) {
		switch ($kind) {
			case 'backend':
            $path = $this->backend_htaccess;
				break;

			case 'frontend':
			default:
            $path = $this->frontend_htaccess;
				break;
		}
		return $path;
	}

	/**
	 * Get the content of the rules file.
	 *
	 * NOTE: will throw error if failed
	 *
	 * @since 1.0.4
	 * @since  2.9 Used exception for failed reading
	 * @access public
	 */
	public function htaccess_read( $kind = 'frontend' ) {
		$path = $this->htaccess_path($kind);

		if (!$path || !file_exists($path)) {
			return "\n";
		}

		if (!$this->_readable($kind)) {
			Error::t('HTA_R');
		}

		$content = File::read($path);
		if ($content === false) {
			Error::t('HTA_GET');
		}

		// Remove ^M characters.
		$content = str_ireplace("\x0D", '', $content);
		return $content;
	}

	/**
	 * Try to backup the .htaccess file if we didn't save one before.
	 *
	 * NOTE: will throw error if failed
	 *
	 * @since 1.0.10
	 * @access private
	 */
	private function _htaccess_backup( $kind = 'frontend' ) {
		$path = $this->htaccess_path($kind);

		if (!file_exists($path)) {
			return;
		}

		if (file_exists($path . '.bk')) {
			return;
		}

		$res = copy($path, $path . '.bk');

		// Failed to backup, abort
		if (!$res) {
			Error::t('HTA_BK');
		}
	}

	/**
	 * Get mobile view rule from htaccess file
	 *
	 * NOTE: will throw error if failed
	 *
	 * @since 1.1.0
	 */
	public function current_mobile_agents() {
		$rules = $this->_get_rule_by(self::MARKER_MOBILE);
		if (!isset($rules[0])) {
			Error::t('HTA_DNF', self::MARKER_MOBILE);
		}

		$rule = trim($rules[0]);
		// 'RewriteCond %{HTTP_USER_AGENT} ' . Utility::arr2regex( $cfg[ $id ], true ) . ' [NC]';
		$match = substr($rule, strlen('RewriteCond %{HTTP_USER_AGENT} '), -strlen(' [NC]'));

		if (!$match) {
			Error::t('HTA_DNF', __('Mobile Agent Rules', 'litespeed-cache'));
		}

		return $match;
	}

	/**
	 * Parse rewrites rule from the .htaccess file.
	 *
	 * NOTE: will throw error if failed
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function current_login_cookie( $kind = 'frontend' ) {
		$rule = $this->_get_rule_by(self::MARKER_LOGIN_COOKIE, $kind);

		if (!$rule) {
			Error::t('HTA_DNF', self::MARKER_LOGIN_COOKIE);
		}

		if (strpos($rule, 'RewriteRule .? - [E=') !== 0) {
			Error::t('HTA_LOGIN_COOKIE_INVALID');
		}

		$rule_cookie = substr($rule, strlen('RewriteRule .? - [E='), -1);

		if (LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS') {
			$rule_cookie = trim($rule_cookie, '"');
		}

		// Drop `Cache-Vary:`
		$rule_cookie = substr($rule_cookie, strlen('Cache-Vary:'));

		return $rule_cookie;
	}

	/**
	 * Get rewrite rules based on the marker
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _get_rule_by( $cond, $kind = 'frontend' ) {
		clearstatcache();
		$path = $this->htaccess_path($kind);
		if (!$this->_readable($kind)) {
			return false;
		}

		$rules = File::extract_from_markers($path, self::MARKER);
		if (!in_array($cond . self::MARKER_START, $rules) || !in_array($cond . self::MARKER_END, $rules)) {
			return false;
		}

		$key_start = array_search($cond . self::MARKER_START, $rules);
		$key_end   = array_search($cond . self::MARKER_END, $rules);
		if ($key_start === false || $key_end === false) {
			return false;
		}

		$results = array_slice($rules, $key_start + 1, $key_end - $key_start - 1);
		if (!$results) {
			return false;
		}

		if (count($results) == 1) {
			return trim($results[0]);
		}

		return array_filter($results);
	}

	/**
	 * Generate browser cache rules
	 *
	 * @since  1.3
	 * @access private
	 * @return array Rules set
	 */
	private function _browser_cache_rules( $cfg ) {
		/**
		 * Add ttl setting
		 *
		 * @since 1.6.3
		 */
		$id    = Base::O_CACHE_TTL_BROWSER;
		$ttl   = $cfg[$id];
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
			'ExpiresByType image/avif A' . $ttl,
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
			'ExpiresByType font/otf A' . $ttl,
			'ExpiresByType font/woff A' . $ttl,
			'ExpiresByType font/woff2 A' . $ttl,
			'',
			// '</FilesMatch>',
			self::LS_MODULE_END,
		);
		return $rules;
	}

	/**
	 * Generate CORS rules for fonts
	 *
	 * @since  1.5
	 * @access private
	 * @return array Rules set
	 */
	private function _cors_rules() {
		return array(
			'<FilesMatch "\.(ttf|ttc|otf|eot|woff|woff2|font\.css)$">',
			'<IfModule mod_headers.c>',
			'Header set Access-Control-Allow-Origin "*"',
			'</IfModule>',
			'</FilesMatch>',
		);
	}

	/**
	 * Generate rewrite rules based on settings
	 *
	 * @since  1.3
	 * @access private
	 * @param  array $cfg  The settings to be used for rewrite rule
	 * @return array      Rules array
	 */
	private function _generate_rules( $cfg ) {
		$new_rules               = array();
		$new_rules_nonls         = array();
		$new_rules_backend       = array();
		$new_rules_backend_nonls = array();

		// continual crawler
		// $id = Base::O_CRAWLER;
		// if (!empty($cfg[$id])) {
		$new_rules[] = self::MARKER_ASYNC . self::MARKER_START;
		$new_rules[] = 'RewriteCond %{REQUEST_URI} /wp-admin/admin-ajax\.php';
		$new_rules[] = 'RewriteCond %{QUERY_STRING} action=async_litespeed';
		$new_rules[] = 'RewriteRule .* - [E=noabort:1]';
		$new_rules[] = self::MARKER_ASYNC . self::MARKER_END;
		$new_rules[] = '';
		// }

		// mobile agents
		$id = Base::O_CACHE_MOBILE_RULES;
		if ((!empty($cfg[Base::O_CACHE_MOBILE]) || !empty($cfg[Base::O_GUEST])) && !empty($cfg[$id])) {
			$new_rules[] = self::MARKER_MOBILE . self::MARKER_START;
			$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} ' . Utility::arr2regex($cfg[$id], true) . ' [NC]';
			$new_rules[] = 'RewriteRule .* - [E=Cache-Control:vary=%{ENV:LSCACHE_VARY_VALUE}+ismobile]';
			$new_rules[] = self::MARKER_MOBILE . self::MARKER_END;
			$new_rules[] = '';
		}

		// nocache cookie
		$id = Base::O_CACHE_EXC_COOKIES;
		if (!empty($cfg[$id])) {
			$new_rules[] = self::MARKER_NOCACHE_COOKIES . self::MARKER_START;
			$new_rules[] = 'RewriteCond %{HTTP_COOKIE} ' . Utility::arr2regex($cfg[$id], true);
			$new_rules[] = 'RewriteRule .* - [E=Cache-Control:no-cache]';
			$new_rules[] = self::MARKER_NOCACHE_COOKIES . self::MARKER_END;
			$new_rules[] = '';
		}

		// nocache user agents
		$id = Base::O_CACHE_EXC_USERAGENTS;
		if (!empty($cfg[$id])) {
			$new_rules[] = self::MARKER_NOCACHE_USER_AGENTS . self::MARKER_START;
			$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} ' . Utility::arr2regex($cfg[$id], true) . ' [NC]';
			$new_rules[] = 'RewriteRule .* - [E=Cache-Control:no-cache]';
			$new_rules[] = self::MARKER_NOCACHE_USER_AGENTS . self::MARKER_END;
			$new_rules[] = '';
		}

		// check login cookie
		$vary_cookies = $cfg[Base::O_CACHE_VARY_COOKIES];
		$id           = Base::O_CACHE_LOGIN_COOKIE;
		if (!empty($cfg[$id])) {
			$vary_cookies[] = $cfg[$id];
		}
		if (LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS') {
			// Need to keep this due to different behavior of OLS when handling response vary header @Sep/22/2018
			if (defined('COOKIEHASH')) {
				$vary_cookies[] = ',wp-postpass_' . COOKIEHASH;
			}
		}
		$vary_cookies = apply_filters('litespeed_vary_cookies', $vary_cookies); // todo: test if response vary header can work in latest OLS, drop the above two lines
		// frontend and backend
		if ($vary_cookies) {
			$env = 'Cache-Vary:' . implode(',', $vary_cookies);
			// if (LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS') {
			// }
			$env         = '"' . $env . '"';
			$new_rules[] = $new_rules_backend[] = self::MARKER_LOGIN_COOKIE . self::MARKER_START;
			$new_rules[] = $new_rules_backend[] = 'RewriteRule .? - [E=' . $env . ']';
			$new_rules[] = $new_rules_backend[] = self::MARKER_LOGIN_COOKIE . self::MARKER_END;
			$new_rules[] = '';
		}

		// CORS font rules
		$id = Base::O_CDN;
		if (!empty($cfg[$id])) {
			$new_rules[] = self::MARKER_CORS . self::MARKER_START;
			$new_rules   = array_merge($new_rules, $this->_cors_rules()); // todo: network
			$new_rules[] = self::MARKER_CORS . self::MARKER_END;
			$new_rules[] = '';
		}

		// webp support
		$id = Base::O_IMG_OPTM_WEBP;
		if (!empty($cfg[$id])) {
			$next_gen_format = 'webp';
			if ($cfg[$id] == 2) {
				$next_gen_format = 'avif';
			}
			$new_rules[] = self::MARKER_WEBP . self::MARKER_START;
			// Check for WebP support via HTTP_ACCEPT
			$new_rules[] = 'RewriteCond %{HTTP_ACCEPT} image/' . $next_gen_format . ' [OR]';

			// Check for iPhone browsers (version > 13)
			$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} iPhone\ OS\ (1[4-9]|[2-9][0-9]) [OR]';

			// Check for Firefox (version >= 65)
			$new_rules[] = 'RewriteCond %{HTTP_USER_AGENT} Firefox/([6-9][0-9]|[1-9][0-9]{2,})';

			// Add vary
			$new_rules[] = 'RewriteRule .* - [E=Cache-Control:vary=%{ENV:LSCACHE_VARY_VALUE}+webp]';
			$new_rules[] = self::MARKER_WEBP . self::MARKER_END;
			$new_rules[] = '';
		}

		// drop qs support
		$id = Base::O_CACHE_DROP_QS;
		if (!empty($cfg[$id])) {
			$new_rules[] = self::MARKER_DROPQS . self::MARKER_START;
			foreach ($cfg[$id] as $v) {
				$new_rules[] = 'CacheKeyModify -qs:' . $v;
			}
			$new_rules[] = self::MARKER_DROPQS . self::MARKER_END;
			$new_rules[] = '';
		}

		// Browser cache
		$id = Base::O_CACHE_BROWSER;
		if (!empty($cfg[$id])) {
			$new_rules_nonls[]       = $new_rules_backend_nonls[] = self::MARKER_BROWSER_CACHE . self::MARKER_START;
			$new_rules_nonls         = array_merge($new_rules_nonls, $this->_browser_cache_rules($cfg));
			$new_rules_backend_nonls = array_merge($new_rules_backend_nonls, $this->_browser_cache_rules($cfg));
			$new_rules_nonls[]       = $new_rules_backend_nonls[] = self::MARKER_BROWSER_CACHE . self::MARKER_END;
			$new_rules_nonls[]       = '';
		}

		// Add module wrapper for LiteSpeed rules
		if ($new_rules) {
			$new_rules = $this->_wrap_ls_module($new_rules);
		}

		if ($new_rules_backend) {
			$new_rules_backend = $this->_wrap_ls_module($new_rules_backend);
		}

		return array( $new_rules, $new_rules_backend, $new_rules_nonls, $new_rules_backend_nonls );
	}

	/**
	 * Add LitSpeed module wrapper with rewrite on
	 *
	 * @since  2.1.1
	 * @access private
	 */
	private function _wrap_ls_module( $rules = array() ) {
		return array_merge(array( self::LS_MODULE_START ), $this->__rewrite_on, array( '' ), $rules, array( self::LS_MODULE_END ));
	}

	/**
	 * Insert LitSpeed module wrapper with rewrite on
	 *
	 * @since  2.1.1
	 * @access public
	 */
	public function insert_ls_wrapper() {
		$rules = $this->_wrap_ls_module();
		$this->_insert_wrapper($rules);
	}

	/**
	 * wrap rules with module on info
	 *
	 * @since  1.1.5
	 * @param  array $rules
	 * @return array        wrapped rules with module info
	 */
	private function _wrap_do_no_edit( $rules ) {
		// When to clear rules, don't need DONOTEDIT msg
		if ($rules === false || !is_array($rules)) {
			return $rules;
		}

		$rules = array_merge(array( self::LS_MODULE_DONOTEDIT ), $rules, array( self::LS_MODULE_DONOTEDIT ));

		return $rules;
	}

	/**
	 * Write to htaccess with rules
	 *
	 * NOTE: will throw error if failed
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _insert_wrapper( $rules = array(), $kind = false, $marker = false ) {
		if ($kind != 'backend') {
			$kind = 'frontend';
		}

		// Default marker is LiteSpeed marker `LSCACHE`
		if ($marker === false) {
			$marker = self::MARKER;
		}

		$this->_htaccess_backup($kind);

		File::insert_with_markers($this->htaccess_path($kind), $this->_wrap_do_no_edit($rules), $marker, true);
	}

	/**
	 * Update rewrite rules based on setting
	 *
	 * NOTE: will throw error if failed
	 *
	 * @since 1.3
	 * @access public
	 */
	public function update( $cfg ) {
		list($frontend_rules, $backend_rules, $frontend_rules_nonls, $backend_rules_nonls) = $this->_generate_rules($cfg);

		// Check frontend content
		list($rules, $rules_nonls) = $this->_extract_rules();

		// Check Non-LiteSpeed rules
		if ($this->_wrap_do_no_edit($frontend_rules_nonls) != $rules_nonls) {
			Debug2::debug('[Rules] Update non-ls frontend rules');
			// Need to update frontend htaccess
			try {
				$this->_insert_wrapper($frontend_rules_nonls, false, self::MARKER_NONLS);
			} catch (\Exception $e) {
				$manual_guide_codes = $this->_rewrite_codes_msg($this->frontend_htaccess, $frontend_rules_nonls, self::MARKER_NONLS);
				Debug2::debug('[Rules] Update Failed');
				throw new \Exception($manual_guide_codes);
			}
		}

		// Check LiteSpeed rules
		if ($this->_wrap_do_no_edit($frontend_rules) != $rules) {
			Debug2::debug('[Rules] Update frontend rules');
			// Need to update frontend htaccess
			try {
				$this->_insert_wrapper($frontend_rules);
			} catch (\Exception $e) {
				Debug2::debug('[Rules] Update Failed');
				$manual_guide_codes = $this->_rewrite_codes_msg($this->frontend_htaccess, $frontend_rules);
				throw new \Exception($manual_guide_codes);
			}
		}

		if ($this->frontend_htaccess !== $this->backend_htaccess) {
			list($rules, $rules_nonls) = $this->_extract_rules('backend');

			// Check Non-LiteSpeed rules for backend
			if ($this->_wrap_do_no_edit($backend_rules_nonls) != $rules_nonls) {
				Debug2::debug('[Rules] Update non-ls backend rules');
				// Need to update frontend htaccess
				try {
					$this->_insert_wrapper($backend_rules_nonls, 'backend', self::MARKER_NONLS);
				} catch (\Exception $e) {
					Debug2::debug('[Rules] Update Failed');
					$manual_guide_codes = $this->_rewrite_codes_msg($this->backend_htaccess, $backend_rules_nonls, self::MARKER_NONLS);
					throw new \Exception($manual_guide_codes);
				}
			}

			// Check backend content
			if ($this->_wrap_do_no_edit($backend_rules) != $rules) {
				Debug2::debug('[Rules] Update backend rules');
				// Need to update backend htaccess
				try {
					$this->_insert_wrapper($backend_rules, 'backend');
				} catch (\Exception $e) {
					Debug2::debug('[Rules] Update Failed');
					$manual_guide_codes = $this->_rewrite_codes_msg($this->backend_htaccess, $backend_rules);
					throw new \Exception($manual_guide_codes);
				}
			}
		}

		return true;
	}

	/**
	 * Get existing rewrite rules
	 *
	 * NOTE: will throw error if failed
	 *
	 * @since  1.3
	 * @access private
	 * @param  string $kind Frontend or backend .htaccess file
	 */
	private function _extract_rules( $kind = 'frontend' ) {
		clearstatcache();
		$path = $this->htaccess_path($kind);
		if (!$this->_readable($kind)) {
			Error::t('E_HTA_R');
		}

		$rules       = File::extract_from_markers($path, self::MARKER);
		$rules_nonls = File::extract_from_markers($path, self::MARKER_NONLS);

		return array( $rules, $rules_nonls );
	}

	/**
	 * Output the msg with rules plain data for manual insert
	 *
	 * @since  1.1.5
	 * @param  string $file
	 * @param  array  $rules
	 * @return string        final msg to output
	 */
	private function _rewrite_codes_msg( $file, $rules, $marker = false ) {
		return sprintf(
			__('<p>Please add/replace the following codes into the beginning of %1$s:</p> %2$s', 'litespeed-cache'),
			$file,
			'<textarea style="width:100%;" rows="10" readonly>' . htmlspecialchars($this->_wrap_rules_with_marker($rules, $marker)) . '</textarea>'
		);
	}

	/**
	 * Generate rules plain data for manual insert
	 *
	 * @since  1.1.5
	 */
	private function _wrap_rules_with_marker( $rules, $marker = false ) {
		// Default marker is LiteSpeed marker `LSCACHE`
		if ($marker === false) {
			$marker = self::MARKER;
		}

		$start_marker  = "# BEGIN {$marker}";
		$end_marker    = "# END {$marker}";
		$new_file_data = implode("\n", array_merge(array( $start_marker ), $this->_wrap_do_no_edit($rules), array( $end_marker )));

		return $new_file_data;
	}

	/**
	 * Clear the rules file of any changes added by the plugin specifically.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function clear_rules() {
		$this->_insert_wrapper(false); // Use false to avoid do-not-edit msg
		// Clear non ls rules
		$this->_insert_wrapper(false, false, self::MARKER_NONLS);

		if ($this->frontend_htaccess !== $this->backend_htaccess) {
			$this->_insert_wrapper(false, 'backend');
			$this->_insert_wrapper(false, 'backend', self::MARKER_NONLS);
		}
	}
}
