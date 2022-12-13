<?php
/**
 * The plugin cache-control class for X-Litespeed-Cache-Control
 *
 * @since      	1.1.3
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Control extends Root {
	const LOG_TAG = '💵';

	const BM_CACHEABLE = 1;
	const BM_PRIVATE = 2;
	const BM_SHARED = 4;
	const BM_NO_VARY = 8;
	const BM_FORCED_CACHEABLE = 32;
	const BM_PUBLIC_FORCED = 64;
	const BM_STALE = 128;
	const BM_NOTCACHEABLE = 256;

	const X_HEADER = 'X-LiteSpeed-Cache-Control';

	protected static $_control = 0;
	protected static $_custom_ttl = 0;

	private $_response_header_ttls = array();

	/**
	 * Init cache control
	 *
	 * @since  1.6.2
	 */
	public function init() {
		/**
		 * Add vary filter for Role Excludes
		 * @since  1.6.2
		 */
		add_filter( 'litespeed_vary', array( $this, 'vary_add_role_exclude' ) );

		// 301 redirect hook
		add_filter( 'wp_redirect', array( $this, 'check_redirect' ), 10, 2 );

		// Load response header conf
		$this->_response_header_ttls = $this->conf( Base::O_CACHE_TTL_STATUS );
		foreach ( $this->_response_header_ttls as $k => $v ) {
			$v = explode( ' ', $v );
			if ( empty( $v[ 0 ] ) || empty( $v[ 1 ] ) ) {
				continue;
			}
			$this->_response_header_ttls[ $v[ 0 ] ] = $v[ 1 ];
		}

		if ( $this->conf( Base::O_PURGE_STALE ) ) {
			$this->set_stale();
		}
	}

	/**
	 * Exclude role from optimization filter
	 *
	 * @since  1.6.2
	 * @access public
	 */
	public function vary_add_role_exclude( $vary ) {
		if ( $this->in_cache_exc_roles() ) {
			$vary[ 'role_exclude_cache' ] = 1;
		}

		return $vary;
	}

	/**
	 * Check if one user role is in exclude cache group settings
	 *
	 * @since 1.6.2
	 * @since 3.0 Moved here from conf.cls
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_cache_exc_roles( $role = null ) {
		// Get user role
		if ( $role === null ) {
			$role = Router::get_role();
		}

		if ( ! $role ) {
			return false;
		}

		$roles = explode( ',', $role );
		$found = array_intersect( $roles, $this->conf( Base::O_CACHE_EXC_ROLES ) );

		return $found ? implode( ',', $found ) : false;
	}

	/**
	 * 1. Initialize cacheable status for `wp` hook
	 * 2. Hook error page tags for cacheable pages
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public function init_cacheable() {
		// Hook `wp` to mark default cacheable status
		// NOTE: Any process that does NOT run into `wp` hook will not get cacheable by default
		add_action( 'wp', array( $this, 'set_cacheable' ), 5 );

		// Hook WP REST to be cacheable
		if ( $this->conf( Base::O_CACHE_REST ) ) {
			add_action( 'rest_api_init', array( $this, 'set_cacheable' ), 5 );
		}

		// Cache resources
		// NOTE: If any strange resource doesn't use normal WP logic `wp_loaded` hook, rewrite rule can handle it
		$cache_res = $this->conf( Base::O_CACHE_RES );
		if ( $cache_res ) {
			$uri = esc_url( $_SERVER["REQUEST_URI"] );// todo: check if need esc_url()
			$pattern = '!' . LSCWP_CONTENT_FOLDER . Htaccess::RW_PATTERN_RES . '!';
			if ( preg_match( $pattern, $uri ) ) {
				add_action( 'wp_loaded', array( $this, 'set_cacheable' ), 5 );
			}
		}

		// Check error page
		add_filter( 'status_header', array( $this, 'check_error_codes' ), 10, 2 );
	}


	/**
	 * Check if the page returns any error code.
	 *
	 * @since 1.0.13.1
	 * @access public
	 * @param $status_header
	 * @param $code
	 * @return $eror_status
	 */
	public function check_error_codes( $status_header, $code ) {
		if ( array_key_exists( $code, $this->_response_header_ttls ) ) {
			if ( self::is_cacheable() && ! $this->_response_header_ttls[ $code ] ) {
				self::set_nocache( '[Ctrl] TTL is set to no cache [status_header] ' . $code );
			}

			// Set TTL
			self::set_custom_ttl( $this->_response_header_ttls[ $code ] );
		}
		elseif (self::is_cacheable()) {
			if ( substr($code, 0, 1)==4 || substr($code, 0, 1)==5 ) {
				self::set_nocache( '[Ctrl] 4xx/5xx default to no cache [status_header] ' . $code );
			}
		}

		// Set cache tag
		Tag::add( Tag::TYPE_HTTP . $code );

		// Give the default status_header back
		return $status_header;
	}

	/**
	 * Set no vary setting
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function set_no_vary() {
		if ( self::is_no_vary() ) {
			return;
		}
		self::$_control |= self::BM_NO_VARY;
		Debug2::debug( '[Ctrl] X Cache_control -> no-vary', 3 );
	}

	/**
	 * Get no vary setting
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_no_vary() {
		return self::$_control & self::BM_NO_VARY;
	}

	/**
	 * Set stale
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function set_stale() {
		if ( self::is_stale() ) {
			return;
		}
		self::$_control |= self::BM_STALE;
		Debug2::debug('[Ctrl] X Cache_control -> stale');
	}

	/**
	 * Get stale
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_stale() {
		return self::$_control & self::BM_STALE;
	}

	/**
	 * Set cache control to shared private
	 *
	 * @access public
	 * @since 1.1.3
	 * @param string $reason The reason to no cache
	 */
	public static function set_shared( $reason = false ) {
		if ( self::is_shared() ) {
			return;
		}
		self::$_control |= self::BM_SHARED;
		self::set_private();

		if ( ! is_string( $reason ) ) {
			$reason = false;
		}

		if ( $reason ) {
			$reason = "( $reason )";
		}
		Debug2::debug( '[Ctrl] X Cache_control -> shared ' . $reason );
	}

	/**
	 * Check if is shared private
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_shared() {
		return (self::$_control & self::BM_SHARED) && self::is_private();
	}

	/**
	 * Set cache control to forced public
	 *
	 * @access public
	 * @since 1.7.1
	 */
	public static function set_public_forced( $reason = false ) {
		if ( self::is_public_forced() ) {
			return;
		}
		self::$_control |= self::BM_PUBLIC_FORCED;

		if ( ! is_string( $reason ) ) {
			$reason = false;
		}

		if ( $reason ) {
			$reason = "( $reason )";
		}
		Debug2::debug( '[Ctrl] X Cache_control -> public forced ' . $reason );
	}

	/**
	 * Check if is public forced
	 *
	 * @access public
	 * @since 1.7.1
	 */
	public static function is_public_forced() {
		return self::$_control & self::BM_PUBLIC_FORCED;
	}

	/**
	 * Set cache control to private
	 *
	 * @access public
	 * @since 1.1.3
	 * @param string $reason The reason to no cache
	 */
	public static function set_private( $reason = false ) {
		if ( self::is_private() ) {
			return;
		}
		self::$_control |= self::BM_PRIVATE;

		if ( ! is_string( $reason ) ) {
			$reason = false;
		}

		if ( $reason ) {
			$reason = "( $reason )";
		}
		Debug2::debug( '[Ctrl] X Cache_control -> private ' . $reason );
	}

	/**
	 * Check if is private
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function is_private() {
		if ( defined( 'LITESPEED_GUEST' ) && LITESPEED_GUEST ) {
			// return false;
		}

		return self::$_control & self::BM_PRIVATE && ! self::is_public_forced();
	}

	/**
	 * Initialize cacheable status in `wp` hook, if not call this, by default it will be non-cacheable
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function set_cacheable( $reason = false ) {
		self::$_control |= self::BM_CACHEABLE;

		if ( ! is_string( $reason ) ) {
			$reason = false;
		}

		if ( $reason ) {
			$reason = ' [reason] ' . $reason;
		}
		Debug2::debug( '[Ctrl] X Cache_control init on' . $reason );
	}

	/**
	 * This will disable non-cacheable BM
	 *
	 * @access public
	 * @since 2.2
	 */
	public static function force_cacheable( $reason = false ) {
		self::$_control |= self::BM_FORCED_CACHEABLE;

		if ( ! is_string( $reason ) ) {
			$reason = false;
		}

		if ( $reason ) {
			$reason = ' [reason] ' . $reason;
		}
		Debug2::debug( '[Ctrl] Forced cacheable' . $reason );
	}

	/**
	 * Switch to nocacheable status
	 *
	 * @access public
	 * @since 1.1.3
	 * @param string $reason The reason to no cache
	 */
	public static function set_nocache( $reason = false ) {
		self::$_control |= self::BM_NOTCACHEABLE;

		if ( ! is_string( $reason ) ) {
			$reason = false;
		}

		if ( $reason ) {
			$reason = "( $reason )";
		}
		Debug2::debug( '[Ctrl] X Cache_control -> no Cache ' . $reason, 5 );
	}

	/**
	 * Check current notcacheable bit set
	 *
	 * @access public
	 * @since 1.1.3
	 * @return bool True if notcacheable bit is set, otherwise false.
	 */
	public static function isset_notcacheable() {
		return self::$_control & self::BM_NOTCACHEABLE;
	}

	/**
	 * Check current force cacheable bit set
	 *
	 * @access public
	 * @since 	2.2
	 */
	public static function is_forced_cacheable() {
		return self::$_control & self::BM_FORCED_CACHEABLE;
	}

	/**
	 * Check current cacheable status
	 *
	 * @access public
	 * @since 1.1.3
	 * @return bool True if is still cacheable, otherwise false.
	 */
	public static function is_cacheable() {
		if ( defined( 'LSCACHE_NO_CACHE' ) && LSCACHE_NO_CACHE ) {
			Debug2::debug( '[Ctrl] LSCACHE_NO_CACHE constant defined' );
			return false;
		}

		// Guest mode always cacheable
		if ( defined( 'LITESPEED_GUEST' ) && LITESPEED_GUEST ) {
			// return true;
		}

		// If its forced public cacheable
		if ( self::is_public_forced() ) {
			return true;
		}

		// If its forced cacheable
		if ( self::is_forced_cacheable() ) {
			return true;
		}

		return ! self::isset_notcacheable() && self::$_control & self::BM_CACHEABLE;
	}

	/**
	 * Set a custom TTL to use with the request if needed.
	 *
	 * @access public
	 * @since 1.1.3
	 * @param mixed $ttl An integer or string to use as the TTL. Must be numeric.
	 */
	public static function set_custom_ttl( $ttl, $reason = false ) {
		if ( is_numeric( $ttl ) ) {
			self::$_custom_ttl = $ttl;
			Debug2::debug( '[Ctrl] X Cache_control TTL -> ' . $ttl . ( $reason ? ' [reason] ' . $ttl : '' ) );
		}
	}

	/**
	 * Generate final TTL.
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function get_ttl() {
		if ( self::$_custom_ttl != 0 ) {
			return self::$_custom_ttl;
		}

		// Check if is in timed url list or not
		$timed_urls = Utility::wildcard2regex( $this->conf( Base::O_PURGE_TIMED_URLS ) );
		$timed_urls_time = $this->conf( Base::O_PURGE_TIMED_URLS_TIME );
		if ( $timed_urls && $timed_urls_time ) {
			$current_url = Tag::build_uri_tag( true );
			// Use time limit ttl
			$scheduled_time = strtotime( $timed_urls_time );
			$ttl = $scheduled_time - time();
			if ( $ttl < 0 ) {
				$ttl += 86400;// add one day
			}
			foreach ( $timed_urls as $v ) {
				if ( strpos( $v, '*' ) !== false ) {
					if( preg_match( '#' . $v . '#iU', $current_url ) ) {
						Debug2::debug( '[Ctrl] X Cache_control TTL is limited to ' . $ttl . ' due to scheduled purge regex ' . $v );
						return $ttl;
					}
				}
				else {
					if ( $v == $current_url ) {
						Debug2::debug( '[Ctrl] X Cache_control TTL is limited to ' . $ttl . ' due to scheduled purge rule ' . $v );
						return $ttl;
					}
				}
			}
		}

		// Private cache uses private ttl setting
		if ( self::is_private() ) {
			return $this->conf( Base::O_CACHE_TTL_PRIV );
		}

		if ( is_front_page() ){
			return $this->conf( Base::O_CACHE_TTL_FRONTPAGE );
		}

		$feed_ttl = $this->conf( Base::O_CACHE_TTL_FEED );
		if ( is_feed() && $feed_ttl > 0 ) {
			return $feed_ttl;
		}

		if ( $this->cls( 'REST' )->is_rest() || $this->cls( 'REST' )->is_internal_rest() ) {
			return $this->conf( Base::O_CACHE_TTL_REST );
		}

		return $this->conf( Base::O_CACHE_TTL_PUB );
	}

	/**
	 * Check if need to set no cache status for redirection or not
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function check_redirect( $location, $status ) { // TODO: some env don't have SCRIPT_URI but only REQUEST_URI, need to be compatible
		if ( ! empty( $_SERVER[ 'SCRIPT_URI' ] ) ) { // dont check $status == '301' anymore
			self::debug( "301 from " . $_SERVER[ 'SCRIPT_URI' ] );
			self::debug( "301 to $location" );

			$to_check = array(
				PHP_URL_SCHEME,
				PHP_URL_HOST,
				PHP_URL_PATH,
				PHP_URL_QUERY,
			);

			$is_same_redirect = true;

			foreach ( $to_check as $v ) {
				$url_parsed = $v == PHP_URL_QUERY ? $_SERVER[ 'QUERY_STRING' ] : parse_url( $_SERVER[ 'SCRIPT_URI' ], $v );
				$target = parse_url( $location, $v );

				self::debug("Compare [from] $url_parsed [to] $target");

				if($v==PHP_URL_QUERY) {
					$url_parsed = urldecode($url_parsed);
					$target = urldecode($target);
				}

				if ( $url_parsed != $target ) {
					$is_same_redirect = false;
					self::debug( "301 different redirection" );
					break;
				}
			}

			if ( $is_same_redirect ) {
				self::set_nocache( '301 to same url' );
			}
		}

		return $location;
	}

	/**
	 * Sets up the Cache Control header.
	 *
	 * @since 1.1.3
	 * @access public
	 * @return string empty string if empty, otherwise the cache control header.
	 */
	public function output() {
		$esi_hdr = '';
		if ( ESI::has_esi() ) {
			$esi_hdr = ',esi=on';
		}

		$hdr = self::X_HEADER . ': ';

		if ( defined( 'DONOTCACHEPAGE' ) && apply_filters( 'litespeed_const_DONOTCACHEPAGE', DONOTCACHEPAGE ) ) {
			Debug2::debug( "[Ctrl] ❌ forced no cache [reason] DONOTCACHEPAGE const" );
			$hdr .= 'no-cache' . $esi_hdr;
			return $hdr;
		}

		// Guest mode directly return cacheable result
		// if ( defined( 'LITESPEED_GUEST' ) && LITESPEED_GUEST ) {
		// 	// If is POST, no cache
		// 	if ( defined( 'LSCACHE_NO_CACHE' ) && LSCACHE_NO_CACHE ) {
		// 		Debug2::debug( "[Ctrl] ❌ forced no cache [reason] LSCACHE_NO_CACHE const" );
		// 		$hdr .= 'no-cache';
		// 	}
		// 	else if( $_SERVER[ 'REQUEST_METHOD' ] !== 'GET' ) {
		// 		Debug2::debug( "[Ctrl] ❌ forced no cache [reason] req not GET" );
		// 		$hdr .= 'no-cache';
		// 	}
		// 	else {
		// 		$hdr .= 'public';
		// 		$hdr .= ',max-age=' . $this->get_ttl();
		// 	}

		// 	$hdr .= $esi_hdr;

		// 	return $hdr;
		// }

		// Fix cli `uninstall --deactivate` fatal err

		if ( ! self::is_cacheable() ) {
			$hdr .= 'no-cache' . $esi_hdr;
			return $hdr;
		}

		if ( self::is_shared() ) {
			$hdr .= 'shared,private';
		}
		elseif ( self::is_private() ) {
			$hdr .= 'private';
		}
		else {
			$hdr .= 'public';
		}

		if ( self::is_no_vary() ) {
			$hdr .= ',no-vary';
		}

		$hdr .= ',max-age=' . $this->get_ttl() . $esi_hdr;
		return $hdr;
	}

	/**
	 * Generate all `control` tags before output
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function finalize() {
		if ( defined( 'LITESPEED_GUEST' ) && LITESPEED_GUEST ) {
			// return;
		}

		if ( is_preview() ) {
			self::set_nocache( 'preview page' );
			return;
		}

		// Check if has metabox non-cacheable setting or not
		if ( file_exists( LSCWP_DIR . 'src/metabox.cls.php' ) && $this->cls( 'Metabox' )->setting( 'litespeed_no_cache' ) ) {
			self::set_nocache( 'per post metabox setting' );
			return;
		}

		// Check if URI is forced public cache
		$excludes = $this->conf( Base::O_CACHE_FORCE_PUB_URI );
		$hit =  Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $excludes, true );
		if ( $hit ) {
			list( $result, $this_ttl ) = $hit;
			self::set_public_forced( 'Setting: ' . $result );
			Debug2::debug( '[Ctrl] Forced public cacheable due to setting: ' . $result );
			if ( $this_ttl ) {
				self::set_custom_ttl( $this_ttl );
			}
		}

		if ( self::is_public_forced() ) {
			return;
		}

		// Check if URI is forced cache
		$excludes = $this->conf( Base::O_CACHE_FORCE_URI );
		$hit =  Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $excludes, true );
		if ( $hit ) {
			list( $result, $this_ttl ) = $hit;
			self::force_cacheable();
			Debug2::debug( '[Ctrl] Forced cacheable due to setting: ' . $result );
			if ( $this_ttl ) {
				self::set_custom_ttl( $this_ttl );
			}
		}

		// if is not cacheable, terminate check
		// Even no need to run 3rd party hook
		if ( ! self::is_cacheable() ) {
			Debug2::debug( '[Ctrl] not cacheable before ctrl finalize' );
			return;
		}

		// Apply 3rd party filter
		// NOTE: Hook always needs to run asap because some 3rd party set is_mobile in this hook
		do_action('litespeed_control_finalize', defined( 'LSCACHE_IS_ESI' ) ? LSCACHE_IS_ESI : false ); // Pass ESI block id

		// if is not cacheable, terminate check
		if ( ! self::is_cacheable() ) {
			Debug2::debug( '[Ctrl] not cacheable after api_control' );
			return;
		}

		// Check litespeed setting to set cacheable status
		if ( ! $this->_setting_cacheable() ) {
			self::set_nocache();
			return;
		}

		// If user has password cookie, do not cache (moved from vary)
		global $post;
		if ( ! empty($post->post_password) && isset($_COOKIE['wp-postpass_' . COOKIEHASH]) ) {
			// If user has password cookie, do not cache
			self::set_nocache('pswd cookie');
			return;
		}

		// The following check to the end is ONLY for mobile
		$is_mobile = apply_filters( 'litespeed_is_mobile', false );
		if ( ! $this->conf( Base::O_CACHE_MOBILE ) ) {
			if ( $is_mobile ) {
				self::set_nocache( 'mobile' );
			}
			return;
		}

		$env_vary = isset( $_SERVER[ 'LSCACHE_VARY_VALUE' ] ) ? $_SERVER[ 'LSCACHE_VARY_VALUE' ] : false;
		if ( ! $env_vary ) {
			$env_vary = isset( $_SERVER[ 'HTTP_X_LSCACHE_VARY_VALUE' ] ) ? $_SERVER[ 'HTTP_X_LSCACHE_VARY_VALUE' ] : false;
		}
		if ( $env_vary && strpos( $env_vary, 'ismobile' ) !== false ) {
			if ( ! wp_is_mobile() && ! $is_mobile ) {
				self::set_nocache( 'is not mobile' ); // todo: no need to uncache, it will correct vary value in vary finalize anyways
				return;
			}
		}
		elseif ( wp_is_mobile() || $is_mobile ) {
			self::set_nocache( 'is mobile' );
			return;
		}

	}

	/**
	 * Check if is mobile for filter `litespeed_is_mobile` in API
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function is_mobile() {
		return wp_is_mobile();
	}

	/**
	 * Check if a page is cacheable based on litespeed setting.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return boolean True if cacheable, false otherwise.
	 */
	private function _setting_cacheable() {
		// logged_in users already excluded, no hook added

		if( ! empty( $_REQUEST[ Router::ACTION ] ) ) {
			return $this->_no_cache_for( 'Query String Action' );
		}

		if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'GET' ) {
			return $this->_no_cache_for('not GET method:' . $_SERVER["REQUEST_METHOD"]);
		}

		if ( is_feed() && $this->conf( Base::O_CACHE_TTL_FEED ) == 0 ) {
			return $this->_no_cache_for('feed');
		}

		if ( is_trackback() ) {
			return $this->_no_cache_for('trackback');
		}

		if ( is_search() ) {
			return $this->_no_cache_for('search');
		}

//		if ( !defined('WP_USE_THEMES') || !WP_USE_THEMES ) {
//			return $this->_no_cache_for('no theme used');
//		}

		// Check private cache URI setting
		$excludes = $this->conf( Base::O_CACHE_PRIV_URI );
		$result = Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $excludes );
		if ( $result ) {
			self::set_private( 'Admin cfg Private Cached URI: ' . $result );
		}

		if ( ! self::is_forced_cacheable() ) {

			// Check if URI is excluded from cache
			$excludes = $this->conf( Base::O_CACHE_EXC );
			$result =  Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $excludes );
			if ( $result ) {
				return $this->_no_cache_for( 'Admin configured URI Do not cache: ' . $result );
			}

			// Check QS excluded setting
			$excludes = $this->conf( Base::O_CACHE_EXC_QS );
			if ( ! empty( $excludes ) && $qs = $this->_is_qs_excluded( $excludes ) ) {
				return $this->_no_cache_for( 'Admin configured QS Do not cache: ' . $qs );
			}

			$excludes = $this->conf( Base::O_CACHE_EXC_CAT );
			if ( ! empty( $excludes ) && has_category( $excludes ) ) {
				return $this->_no_cache_for( 'Admin configured Category Do not cache.' );
			}

			$excludes = $this->conf( Base::O_CACHE_EXC_TAG );
			if ( ! empty( $excludes ) && has_tag( $excludes ) ) {
				return $this->_no_cache_for( 'Admin configured Tag Do not cache.' );
			}

			$excludes = $this->conf( Base::O_CACHE_EXC_COOKIES );
			if ( ! empty( $excludes ) && ! empty( $_COOKIE ) ) {
				$cookie_hit = array_intersect( array_keys( $_COOKIE ), $excludes );
				if ( $cookie_hit ) {
					return $this->_no_cache_for( 'Admin configured Cookie Do not cache.' );
				}
			}

			$excludes = $this->conf( Base::O_CACHE_EXC_USERAGENTS );
			if ( ! empty( $excludes ) && isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
				$nummatches = preg_match( Utility::arr2regex( $excludes ), $_SERVER[ 'HTTP_USER_AGENT' ] );
				if ( $nummatches ) {
						return $this->_no_cache_for('Admin configured User Agent Do not cache.');
				}
			}

			// Check if is exclude roles ( Need to set Vary too )
			if ( $result = $this->in_cache_exc_roles() ) {
				return $this->_no_cache_for( 'Role Excludes setting ' . $result );
			}
		}

		return true;
	}

	/**
	 * Write a debug message for if a page is not cacheable.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $reason An explanation for why the page is not cacheable.
	 * @return boolean Return false.
	 */
	private function _no_cache_for( $reason ) {
		Debug2::debug('[Ctrl] X Cache_control off - ' . $reason);
		return false;
	}

	/**
	 * Check if current request has qs excluded setting
	 *
	 * @since  1.3
	 * @access private
	 * @param  array  $excludes QS excludes setting
	 * @return boolean|string False if not excluded, otherwise the hit qs list
	 */
	private function _is_qs_excluded( $excludes ) {
		if ( ! empty( $_GET ) && $intersect = array_intersect( array_keys( $_GET ), $excludes ) ) {
			return implode( ',', $intersect );
		}
		return false;
	}

}