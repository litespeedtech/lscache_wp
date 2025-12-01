<?php
/**
 * Manage the X-LiteSpeed-Vary behavior and vary cookie.
 *
 * @since   1.1.3
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Handles detection of user state (guest, logged-in, commenter, etc.)
 * and builds the X-LiteSpeed-Vary header and vary cookie accordingly.
 */
class Vary extends Root {

	/**
	 * Log tag used in debug output.
	 *
	 * @var string
	 */
	const LOG_TAG = '🔱';

	/**
	 * Vary header name.
	 *
	 * @var string
	 */
	const X_HEADER = 'X-LiteSpeed-Vary';

	/**
	 * Default vary cookie name (used for logged-in/commenter state).
	 *
	 * @var string
	 */
	private static $_vary_name = '_lscache_vary';

	/**
	 * Whether Ajax calls are permitted to change the vary cookie.
	 *
	 * @var bool
	 */
	private static $_can_change_vary = false;

	/**
	 * Update the default vary cookie name if site settings require it.
	 *
	 * @since 4.0
	 * @since 7.0 Moved to after_user_init to allow ESI no-vary no conflict.
	 * @return void
	 */
	private function _update_vary_name() {
		$db_cookie = $this->conf( Base::O_CACHE_LOGIN_COOKIE ); // network aware in v3.0.

		// If no vary set in rewrite rule.
		if ( ! isset( $_SERVER['LSCACHE_VARY_COOKIE'] ) ) {
			if ( $db_cookie ) {
				// Check for ESI no-vary control.
				$something_wrong = true;

				if ( ! empty( $_GET[ ESI::QS_ACTION ] ) && ! empty( $_GET['_control'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$control_raw = wp_unslash( (string) $_GET['_control'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$control     = array_map( 'sanitize_text_field', explode( ',', $control_raw ) );
					if ( in_array( 'no-vary', $control, true ) ) {
						self::debug( 'no-vary control existed, bypass vary_name update' );
						$something_wrong  = false;
						self::$_vary_name = $db_cookie;
					}
				}

				if ( defined( 'LITESPEED_CLI' ) || wp_doing_cron() ) {
					$something_wrong = false;
				}

				if ( $something_wrong ) {
					// Display cookie error msg to admin.
					if ( is_multisite() ? is_network_admin() : is_admin() ) {
						Admin_Display::show_error_cookie();
					}
					Control::set_nocache( '❌❌ vary cookie setting error' );
				}
			}
			return;
		}

		// DB setting does not exist – nothing to check.
		if ( ! $db_cookie ) {
			return;
		}

		// Beyond this point, ensure DB vary is present in $_SERVER env.
		$server_raw = wp_unslash( (string) $_SERVER['LSCACHE_VARY_COOKIE'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$vary_arr   = array_map( 'trim', explode( ',', $server_raw ) );

		if ( in_array( $db_cookie, $vary_arr, true ) ) {
			self::$_vary_name = $db_cookie;
			return;
		}

		if ( is_multisite() ? is_network_admin() : is_admin() ) {
			Admin_Display::show_error_cookie();
		}
		Control::set_nocache( 'vary cookie setting lost error' );
	}

	/**
	 * Run after user init to set up vary/caching for current request.
	 *
	 * @since 4.0
	 * @return void
	 */
	public function after_user_init() {
		$this->_update_vary_name();

		// Logged-in user.
		if ( Router::is_logged_in() ) {
			// If not ESI, check cache logged-in user setting.
			if ( ! $this->cls( 'Router' )->esi_enabled() ) {
				// Cache logged-in => private cache.
				if ( $this->conf( Base::O_CACHE_PRIV ) && ! is_admin() ) {
					add_action( 'wp_logout', __NAMESPACE__ . '\Purge::purge_on_logout' );

					$this->cls( 'Control' )->init_cacheable();
					Control::set_private( 'logged in user' );
				} else {
					// No cache for logged-in user.
					Control::set_nocache( 'logged in user' );
				}
			} elseif ( ! is_admin() ) {
				// ESI is on; can be public cache, but ensure cacheable is initialized.
				$this->cls( 'Control' )->init_cacheable();
			}

			// Clear login state on logout.
			add_action( 'clear_auth_cookie', [ $this, 'remove_logged_in' ] );
		} else {
			// Only after vary init we can detect guest mode.
			$this->_maybe_guest_mode();

			// Set vary cookie when user logs in (to avoid guest vary).
			add_action( 'set_logged_in_cookie', [ $this, 'add_logged_in' ], 10, 4 );
			add_action( 'wp_login', __NAMESPACE__ . '\Purge::purge_on_logout' );

			$this->cls( 'Control' )->init_cacheable();

			// Check login-page cacheable setting — login page doesn't go through main WP logic.
			add_action( 'login_init', [ $this->cls( 'Tag' ), 'check_login_cacheable' ], 5 );

			// Optional lightweight guest vary updater.
			if ( ! empty( $_GET['litespeed_guest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_action( 'wp_loaded', [ $this, 'update_guest_vary' ], 20 );
			}
		}

		// Commenter checks.
		add_filter( 'comments_array', [ $this, 'check_commenter' ] );

		// Set vary cookie for commenter.
		add_action( 'set_comment_cookies', [ $this, 'append_commenter' ] );

		// REST: don't change vary because they don't carry on user info usually.
		add_action(
			'rest_api_init',
			function () {
				self::debug( 'Rest API init disabled vary change' );
				add_filter( 'litespeed_can_change_vary', '__return_false' );
			}
		);
	}

	/**
	 * Mark request as Guest mode when applicable.
	 *
	 * @since 4.0
	 * @return void
	 */
	private function _maybe_guest_mode() {
		if ( defined( 'LITESPEED_GUEST' ) ) {
			self::debug( '👒👒 Guest mode ' . ( LITESPEED_GUEST ? 'predefined' : 'turned off' ) );
			return;
		}

		if ( ! $this->conf( Base::O_GUEST ) ) {
			return;
		}

		// If vary is set, then not a guest.
		if ( self::has_vary() ) {
			return;
		}

		// Admin QS present? not a guest.
		if ( ! empty( $_GET[ Router::ACTION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		if ( wp_doing_cron() ) {
			return;
		}

		// Request to update vary? not a guest.
		if ( ! empty( $_GET['litespeed_guest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// User explicitly turned guest off.
		if ( ! empty( $_GET['litespeed_guest_off'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		self::debug( '👒👒 Guest mode' );

		! defined( 'LITESPEED_GUEST' ) && define( 'LITESPEED_GUEST', true );

		if ( $this->conf( Base::O_GUEST_OPTM ) ) {
			! defined( 'LITESPEED_GUEST_OPTM' ) && define( 'LITESPEED_GUEST_OPTM', true );
		}
	}

	/**
	 * Update Guest vary
	 *
	 * @since      4.0
	 * @deprecated 4.1 Use independent lightweight guest.vary.php instead.
	 * @return void
	 */
	public function update_guest_vary() {
		// Must not be cached.
		! defined( 'LSCACHE_NO_CACHE' ) && define( 'LSCACHE_NO_CACHE', true );

		$_guest = new Lib\Guest();
		if ( $_guest->always_guest() || self::has_vary() ) {
			// If contains vary already, don't reload (avoid loops).
			! defined( 'LITESPEED_GUEST' ) && define( 'LITESPEED_GUEST', true );
			self::debug( '🤠🤠 Guest' );
			echo '[]';
			exit;
		}

		self::debug( 'Will update guest vary in finalize' );

		// Return JSON to trigger reload.
		echo wp_json_encode( [ 'reload' => 'yes' ] );
		exit;
	}

	/**
	 * Filter callback on `comments_array` to mark commenter state.
	 *
	 * @since 1.0.4
	 *
	 * @param array $comments The comments to output.
	 * @return array Filtered comments.
	 */
	public function check_commenter( $comments ) {
		/**
		 * Allow bypassing pending comment check for comment plugins.
		 *
		 * @since 2.9.5
		 */
		if ( apply_filters( 'litespeed_vary_check_commenter_pending', true ) ) {
			$pending = false;
			foreach ( $comments as $comment ) {
				if ( ! $comment->comment_approved ) {
					$pending = true;
					break;
				}
			}

			// No pending comments => ensure public cache state.
			if ( ! $pending ) {
				self::debug( 'No pending comment' );
				$this->remove_commenter();

				// Remove commenter prefilled info for public cache.
				foreach ( $_COOKIE as $cookie_name => $cookie_value ) {
					if ( strlen( $cookie_name ) >= 15 && 0 === strpos( $cookie_name, 'comment_author_' ) ) {
						unset( $_COOKIE[ $cookie_name ] );
					}
				}

				return $comments;
			}
		}

		// Pending comments present — set commenter vary.
		$this->add_commenter();

		if ( $this->conf( Base::O_CACHE_COMMENTER ) ) {
			Control::set_private( 'existing commenter' );
		} else {
			Control::set_nocache( 'existing commenter' );
		}

		return $comments;
	}

	/**
	 * Check if default vary has a value
	 *
	 * @since 1.1.3
	 *
	 * @return false|string Cookie value or false if missing.
	 */
	public static function has_vary() {
		if ( empty( $_COOKIE[ self::$_vary_name ] ) ) {
			return false;
		}
		// Cookie values are not user-displayed; unslash only.
		return wp_unslash( (string) $_COOKIE[ self::$_vary_name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Append user status with logged-in.
	 *
	 * @since 1.1.3
	 * @since 1.6.2 Removed static referral.
	 *
	 * @param string|false $logged_in_cookie The logged-in cookie value.
	 * @param int|false    $expire           Expiration timestamp.
	 * @param int|false    $expiration       Unused (WordPress signature).
	 * @param int|false    $uid              User ID.
	 * @return void
	 */
	public function add_logged_in( $logged_in_cookie = false, $expire = false, $expiration = false, $uid = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		self::debug( 'add_logged_in' );

		// Allow Ajax vary change during login flow.
		// NOTE: Run before `$this->_update_default_vary()` to make vary changeable
		self::can_ajax_vary();

		// Ensure vary cookie exists/updated.
		$this->_update_default_vary( $uid, $expire );
	}

	/**
	 * Remove user logged-in status.
	 *
	 * @since 1.1.3
	 * @since 1.6.2 Removed static referral.
	 * @return void
	 */
	public function remove_logged_in() {
		self::debug( 'remove_logged_in' );

		// Allow Ajax vary change during logout flow.
		self::can_ajax_vary();

		// Force update vary to remove login status.
		$this->_update_default_vary( -1 );
	}

	/**
	 * Allow vary to be changed for Ajax calls.
	 *
	 * @since 2.2.2
	 * @since 2.6 Changed to static.
	 * @return void
	 */
	public static function can_ajax_vary() {
		self::debug( '_can_change_vary -> true' );
		self::$_can_change_vary = true;
	}

	/**
	 * Whether we can change the default vary right now.
	 *
	 * @since 1.6.2
	 * @return bool
	 */
	private function can_change_vary() {
		// Don't change on Ajax unless explicitly allowed (no webp header).
		if ( Router::is_ajax() && ! self::$_can_change_vary ) {
			self::debug( 'can_change_vary bypassed due to ajax call' );
			return false;
		}

		// Allow only GET/POST.
		// POST request can set vary to fix #820789 login "loop" guest cache issue.
		if (
			isset( $_SERVER['REQUEST_METHOD'] )
			&& 'GET' !== $_SERVER['REQUEST_METHOD']
			&& 'POST' !== $_SERVER['REQUEST_METHOD']
		) {
			self::debug( 'can_change_vary bypassed due to method not get/post' );
			return false;
		}

		// Disable when crawler is making the request.
		if (
			! empty( $_SERVER['HTTP_USER_AGENT'] )
			&& 0 === strpos( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ), Crawler::FAST_USER_AGENT ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		) {
			self::debug( 'can_change_vary bypassed due to crawler' );
			return false;
		}

		if ( ! apply_filters( 'litespeed_can_change_vary', true ) ) {
			self::debug( 'can_change_vary bypassed due to litespeed_can_change_vary hook' );
			return false;
		}

		return true;
	}

	/**
	 * Update default vary cookie (idempotent within a request).
	 *
	 * @since 1.6.2
	 * @since 1.6.6.1 Guard to ensure single run.
	 *
	 * @param int|false $uid    User ID or false.
	 * @param int|false $expire Expiration timestamp (default: +2 days).
	 * @return void
	 */
	private function _update_default_vary( $uid = false, $expire = false ) {
		// Ensure header output only runs once.
		if ( ! defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			define( 'LITESPEED_DID_' . __FUNCTION__, true );
		} else {
			self::debug2( '_update_default_vary bypassed due to run already' );
			return;
		}

		// ESI shouldn't change vary (main page only).
		if ( defined( 'LSCACHE_IS_ESI' ) && LSCACHE_IS_ESI ) {
			self::debug2( '_update_default_vary bypassed due to ESI' );
			return;
		}

		$vary         = $this->finalize_default_vary( $uid );
		$current_vary = self::has_vary();

		if ( $current_vary !== $vary && 'commenter' !== $current_vary && $this->can_change_vary() ) {
			if ( ! $expire ) {
				$expire = time() + 2 * DAY_IN_SECONDS;
			}
			$this->_cookie( $vary, (int) $expire );
		}
	}

	/**
	 * Get the current vary cookie name.
	 *
	 * @since 1.9.1
	 * @return string
	 */
	public function get_vary_name() {
		return self::$_vary_name;
	}

	/**
	 * Check if a user role is in a configured vary group.
	 *
	 * @since 1.2.0
	 * @since 3.0 Moved here from conf.cls.
	 *
	 * @param string $role User role(s), comma-separated.
	 * @return int|string Group ID or 0.
	 */
	public function in_vary_group( $role ) {
		$group       = 0;
		$vary_groups = $this->conf( Base::O_CACHE_VARY_GROUP );

		$roles = explode( ',', $role );
		$found = array_intersect( $roles, array_keys( (array) $vary_groups ) );

		if ( $found ) {
			$groups = [];
			foreach ( $found as $curr_role ) {
				$groups[] = $vary_groups[ $curr_role ];
			}
			$group = implode( ',', array_unique( $groups ) );
		} elseif ( in_array( 'administrator', $roles, true ) ) {
			$group = 99;
		}

		if ( $group ) {
			self::debug2( 'role in vary_group [group] ' . $group );
		}

		return $group;
	}

	/**
	 * Finalize default vary cookie value for current user.
	 * NOTE: Login process will also call this because it does not call wp hook as normal page loading.
	 *
	 * @since 1.6.2
	 *
	 * @param int|false $uid Optional user ID.
	 * @return false|string False for guests when no vary needed, or hashed vary.
	 */
	public function finalize_default_vary( $uid = false ) {
		// Bypass vary for guests where applicable (avoid non-guest filenames for assets).
		if ( defined( 'LITESPEED_GUEST' ) && LITESPEED_GUEST ) {
			return false;
		}

		$vary = [];

		if ( $this->conf( Base::O_GUEST ) ) {
			$vary['guest_mode'] = 1;
		}

		if ( ! $uid ) {
			$uid = get_current_user_id();
		} else {
			self::debug( 'uid: ' . $uid );
		}

		// Get user role/group.
		$role = Router::get_role( $uid );

		if ( $uid > 0 ) {
			$vary['logged-in'] = 1;

			if ( $role ) {
				// Parse role group from settings.
				$role_group = $this->in_vary_group( $role );
				if ( $role_group ) {
					$vary['role'] = $role_group;
				}
			}

			// Admin bar preference.
			$pref = get_user_option( 'show_admin_bar_front', $uid );
			self::debug2( 'show_admin_bar_front: ' . var_export( $pref, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			$admin_bar = ( false === $pref || 'true' === $pref );

			if ( $admin_bar ) {
				$vary['admin_bar'] = 1;
				self::debug2( 'admin bar : true' );
			}
		} else {
			self::debug( 'role id: failed, guest' );
		}

		/**
		 * Filter vary entries before hashing.
		 *
		 * @since 1.6 Added for Role Excludes for optimization cls
		 * @since 1.6.2 Hooked to webp (legacy)
		 * @since 3.0 Used by 3rd hooks too
		 */
		$vary = apply_filters( 'litespeed_vary', $vary );

		if ( ! $vary ) {
			return false;
		}

		ksort( $vary );
		$list = [];
		foreach ( $vary as $key => $val ) {
			$list[] = $key . ':' . $val;
		}

		$res = implode( ';', $list );
		if ( defined( 'LSCWP_LOG' ) ) {
			return $res;
		}
		// Encrypt in production.
		return md5( $this->conf( Base::HASH ) . $res );
	}

	/**
	 * Get hash of all varies that affect caching (current cookies + default + env).
	 *
	 * @since 4.0
	 * @return string
	 */
	public function finalize_full_varies() {
		$vary  = $this->_finalize_curr_vary_cookies( true );
		$vary .= $this->finalize_default_vary( get_current_user_id() );
		$vary .= $this->get_env_vary();
		return $vary;
	}

	/**
	 * Get request environment vary value (from server variables).
	 *
	 * @since 4.0
	 * @return string|false
	 */
	public function get_env_vary() {
		$env_vary = isset( $_SERVER['LSCACHE_VARY_VALUE'] ) ? wp_unslash( (string) $_SERVER['LSCACHE_VARY_VALUE'] ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! $env_vary ) {
			$env_vary = isset( $_SERVER['HTTP_X_LSCACHE_VARY_VALUE'] ) ? wp_unslash( (string) $_SERVER['HTTP_X_LSCACHE_VARY_VALUE'] ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		return $env_vary;
	}

	/**
	 * Mark current user as commenter (called on comment submit).
	 *
	 * @since 1.1.6
	 * @return void
	 */
	public function append_commenter() {
		$this->add_commenter( true );
	}

	/**
	 * Add commenter vary (optionally from redirect).
	 *
	 * @since 1.1.3
	 *
	 * @param bool $from_redirect Whether request is from redirect page.
	 * @return void
	 */
	private function add_commenter( $from_redirect = false ) {
		// If the cookie is lost somehow, set it.
		if ( 'commenter' !== self::has_vary() ) {
			self::debug( 'Add commenter' );

			// Save commenter status only for current domain path.
			$this->_cookie(
				'commenter',
				time() + (int) apply_filters( 'comment_cookie_lifetime', 30000000 ),
				self::_relative_path( $from_redirect )
			);
		}
	}

	/**
	 * Remove commenter vary if set.
	 *
	 * @since 1.1.3
	 * @return void
	 */
	private function remove_commenter() {
		if ( 'commenter' === self::has_vary() ) {
			self::debug( 'Remove commenter' );
			$this->_cookie( false, false, self::_relative_path() );
		}
	}

	/**
	 * Generate a relative cookie path from current request.
	 *
	 * @since 1.1.3
	 *
	 * @param bool $from_redirect When true, uses HTTP_REFERER; otherwise SCRIPT_URL.
	 * @return string|false Path or false.
	 */
	private static function _relative_path( $from_redirect = false ) {
		$path = false;
		$tag  = $from_redirect ? 'HTTP_REFERER' : 'SCRIPT_URL';
		if ( ! empty( $_SERVER[ $tag ] ) ) {
			$parsed = wp_parse_url( wp_unslash( (string) $_SERVER[ $tag ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$path   = ! empty( $parsed['path'] ) ? $parsed['path'] : false;
			self::debug( 'Cookie Vary path: ' . ( $path ? $path : 'false' ) );
		}
		return $path;
	}

	/**
	 * Build the final X-LiteSpeed-Vary header for current request.
	 * NOTE: Non caccheable page can still set vary ( for logged in process ).
	 *
	 * @since 1.0.13
	 *
	 * @return string|void Header string or nothing when not needed.
	 */
	public function finalize() {
		// Finalize default vary for non-guest.
		if ( ! defined( 'LITESPEED_GUEST' ) || ! LITESPEED_GUEST ) {
			$this->_update_default_vary();
		}

		$tp_cookies = $this->_finalize_curr_vary_cookies();

		if ( ! $tp_cookies ) {
			self::debug2( 'no customized vary' );
			return;
		}

		self::debug( 'finalized 3rd party cookies', $tp_cookies );

		return self::X_HEADER . ': ' . implode( ',', $tp_cookies );
	}

	/**
	 * Get vary cookies (names or values JSON) added for current page.
	 *
	 * @since 1.0.13
	 *
	 * @param bool $values_json When true, returns JSON array of cookie values; else cookie=name items.
	 * @return array|string|false List of vary cookie items, JSON string, or false when none.
	 */
	private function _finalize_curr_vary_cookies( $values_json = false ) {
		global $post;

		$cookies = []; // No need to append default vary cookie name.

		if ( ! empty( $post->post_password ) ) {
			$postpass_key = 'wp-postpass_' . COOKIEHASH;
			if ( $this->_get_cookie_val( $postpass_key ) ) {
				self::debug( 'finalize bypassed due to password protected vary ' );
				// If user has password cookie, do not cache & ignore existing vary cookies.
				Control::set_nocache( 'password protected vary' );
				return false;
			}

			$cookies[] = $values_json ? $this->_get_cookie_val( $postpass_key ) : $postpass_key;
		}

		$cookies = apply_filters( 'litespeed_vary_curr_cookies', $cookies );
		if ( $cookies ) {
			$cookies = array_filter( array_unique( $cookies ) );
			self::debug( 'vary cookies changed by filter litespeed_vary_curr_cookies', $cookies );
		}

		if ( ! $cookies ) {
			return false;
		}

		// Format cookie name data or value data.
		sort( $cookies ); // Maintain stable order for $values_json=true.
		foreach ( $cookies as $k => $v ) {
			$cookies[ $k ] = $values_json ? $this->_get_cookie_val( $v ) : 'cookie=' . $v;
		}

		return $values_json ? wp_json_encode( $cookies ) : $cookies;
	}

	/**
	 * Get a cookie value safely.
	 *
	 * @since 4.0
	 *
	 * @param string $key Cookie name.
	 * @return false|string Cookie value or false.
	 */
	private function _get_cookie_val( $key ) {
		if ( ! empty( $_COOKIE[ $key ] ) ) {
			return wp_unslash( (string) $_COOKIE[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		return false;
	}

	/**
	 * Set or clear the vary cookie.
	 *
	 * If the vary cookie changed, mark page as non-cacheable for this response.
	 *
	 * @since 1.0.4
	 *
	 * @param int|false $val    Cookie value to set, or false to clear.
	 * @param int       $expire Expiration timestamp (ignored when $val is false).
	 * @param string    $path   Cookie path (false to use COOKIEPATH).
	 * @return void
	 */
	private function _cookie( $val = false, $expire = 0, $path = false ) {
		if ( ! $val ) {
			$expire = 1;
		}

		// HTTPS bypass toggle for clients using both HTTP/HTTPS.
		$is_ssl = $this->conf( Base::O_UTIL_NO_HTTPS_VARY ) ? false : is_ssl();

		setcookie( self::$_vary_name, $val, (int) $expire, $path ? $path : COOKIEPATH, COOKIE_DOMAIN, $is_ssl, true );
		self::debug( 'set_cookie ---> [k] ' . self::$_vary_name . ' [v] ' . ( false === $val ? 'false' : $val ) . ' [ttl] ' . ( (int) $expire - time() ) );
	}
}
