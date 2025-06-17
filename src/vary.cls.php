<?php

/**
 * The plugin vary class to manage X-LiteSpeed-Vary
 *
 * @since       1.1.3
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Vary extends Root {

	const LOG_TAG  = '🔱';
	const X_HEADER = 'X-LiteSpeed-Vary';

	private static $_vary_name       = '_lscache_vary'; // this default vary cookie is used for logged in status check
	private static $_can_change_vary = false; // Currently only AJAX used this

	/**
	 * Adds the actions used for setting up cookies on log in/out.
	 *
	 * Also checks if the database matches the rewrite rule.
	 *
	 * @since 1.0.4
	 */
	// public function init()
	// {
	// $this->_update_vary_name();
	// }

	/**
	 * Update the default vary name if changed
	 *
	 * @since  4.0
	 * @since 7.0 Moved to after_user_init to allow ESI no-vary no conflict w/ LSCACHE_VARY_COOKIE/O_CACHE_LOGIN_COOKIE
	 */
	private function _update_vary_name() {
		$db_cookie = $this->conf(Base::O_CACHE_LOGIN_COOKIE); // [3.0] todo: check if works in network's sites
		// If no vary set in rewrite rule
		if (!isset($_SERVER['LSCACHE_VARY_COOKIE'])) {
			if ($db_cookie) {
				// Check if is from ESI req or not. If from ESI no-vary, no need to set no-cache
				$something_wrong = true;
				if (!empty($_GET[ESI::QS_ACTION]) && !empty($_GET['_control'])) {
					// Have to manually build this checker bcoz ESI is not init yet.
					$control = explode(',', $_GET['_control']);
					if (in_array('no-vary', $control)) {
						self::debug('no-vary control existed, bypass vary_name update');
						$something_wrong  = false;
						self::$_vary_name = $db_cookie;
					}
				}

				if (defined('LITESPEED_CLI') || wp_doing_cron()) {
					$something_wrong = false;
				}

				if ($something_wrong) {
					// Display cookie error msg to admin
					if (is_multisite() ? is_network_admin() : is_admin()) {
						Admin_Display::show_error_cookie();
					}
					Control::set_nocache('❌❌ vary cookie setting error');
				}
			}
			return;
		}
		// If db setting does not exist, skip checking db value
		if (!$db_cookie) {
			return;
		}

		// beyond this point, need to make sure db vary setting is in $_SERVER env.
		$vary_arr = explode(',', $_SERVER['LSCACHE_VARY_COOKIE']);

		if (in_array($db_cookie, $vary_arr)) {
			self::$_vary_name = $db_cookie;
			return;
		}

		if (is_multisite() ? is_network_admin() : is_admin()) {
			Admin_Display::show_error_cookie();
		}
		Control::set_nocache('vary cookie setting lost error');
	}

	/**
	 * Hooks after user init
	 *
	 * @since  4.0
	 */
	public function after_user_init() {
		$this->_update_vary_name();

		// logged in user
		if (Router::is_logged_in()) {
			// If not esi, check cache logged-in user setting
			if (!$this->cls('Router')->esi_enabled()) {
				// If cache logged-in, then init cacheable to private
				if ($this->conf(Base::O_CACHE_PRIV) && !is_admin()) {
					add_action('wp_logout', __NAMESPACE__ . '\Purge::purge_on_logout');

					$this->cls('Control')->init_cacheable();
					Control::set_private('logged in user');
				}
				// No cache for logged-in user
				else {
					Control::set_nocache('logged in user');
				}
			}
			// ESI is on, can be public cache
			elseif (!is_admin()) {
				// Need to make sure vary is using group id
				$this->cls('Control')->init_cacheable();
			}

			// register logout hook to clear login status
			add_action('clear_auth_cookie', array( $this, 'remove_logged_in' ));
		} else {
			// Only after vary init, can detect if is Guest mode or not
			// Here need `self::$_vary_name` to be set first.
			$this->_maybe_guest_mode();

			// Set vary cookie for logging in user, otherwise the user will hit public with vary=0 (guest version)
			add_action('set_logged_in_cookie', array( $this, 'add_logged_in' ), 10, 4);
			add_action('wp_login', __NAMESPACE__ . '\Purge::purge_on_logout');

			$this->cls('Control')->init_cacheable();

			// Check `login page` cacheable setting because they don't go through main WP logic
			add_action('login_init', array( $this->cls('Tag'), 'check_login_cacheable' ), 5);

			if (!empty($_GET['litespeed_guest'])) {
				add_action('wp_loaded', array( $this, 'update_guest_vary' ), 20);
			}
		}

		// Add comment list ESI
		add_filter('comments_array', array( $this, 'check_commenter' ));

		// Set vary cookie for commenter.
		add_action('set_comment_cookies', array( $this, 'append_commenter' ));

		/**
		 * Don't change for REST call because they don't carry on user info usually
		 *
		 * @since 1.6.7
		 */
		add_action('rest_api_init', function () {
			// this hook is fired in `init` hook
			self::debug('Rest API init disabled vary change');
			add_filter('litespeed_can_change_vary', '__return_false');
		});
	}

	/**
	 * Check if is Guest mode or not
	 *
	 * @since  4.0
	 */
	private function _maybe_guest_mode() {
		if (defined('LITESPEED_GUEST')) {
			self::debug('👒👒 Guest mode ' . (LITESPEED_GUEST ? 'predefined' : 'turned off'));
			return;
		}

		if (!$this->conf(Base::O_GUEST)) {
			return;
		}

		// If vary is set, then not a guest
		if (self::has_vary()) {
			return;
		}

		// If has admin QS, then no guest
		if (!empty($_GET[Router::ACTION])) {
			return;
		}

		if (wp_doing_ajax()) {
			return;
		}

		if (wp_doing_cron()) {
			return;
		}

		// If is the request to update vary, then no guest
		// Don't need anymore as it is always ajax call
		// Still keep it in case some WP blocked the lightweight guest vary update script, WP can still update the vary
		if (!empty($_GET['litespeed_guest'])) {
			return;
		}

		/* @ref https://wordpress.org/support/topic/checkout-add-to-cart-executed-twice/ */
		if (!empty($_GET['litespeed_guest_off'])) {
			return;
		}

		self::debug('👒👒 Guest mode');

		!defined('LITESPEED_GUEST') && define('LITESPEED_GUEST', true);

		if ($this->conf(Base::O_GUEST_OPTM)) {
			!defined('LITESPEED_GUEST_OPTM') && define('LITESPEED_GUEST_OPTM', true);
		}
	}

	/**
	 * Update Guest vary
	 *
	 * @since  4.0
	 * @deprecated 4.1 Use independent lightweight guest.vary.php as a replacement
	 */
	public function update_guest_vary() {
		// This process must not be cached
		!defined('LSCACHE_NO_CACHE') && define('LSCACHE_NO_CACHE', true);

		$_guest = new Lib\Guest();
		if ($_guest->always_guest() || self::has_vary()) {
			// If contains vary already, don't reload to avoid infinite loop when parent page having browser cache
			!defined('LITESPEED_GUEST') && define('LITESPEED_GUEST', true); // Reuse this const to bypass set vary in vary finalize
			self::debug('🤠🤠 Guest');
			echo '[]';
			exit();
		}

		self::debug('Will update guest vary in finalize');

		// return json
		echo \json_encode(array( 'reload' => 'yes' ));
		exit();
	}

	/**
	 * Hooked to the comments_array filter.
	 *
	 * Check if the user accessing the page has the commenter cookie.
	 *
	 * If the user does not want to cache commenters, just check if user is commenter.
	 * Otherwise if the vary cookie is set, unset it. This is so that when the page is cached, the page will appear as if the user was a normal user.
	 * Normal user is defined as not a logged in user and not a commenter.
	 *
	 * @since 1.0.4
	 * @access public
	 * @global type $post
	 * @param array $comments The current comments to output
	 * @return array The comments to output.
	 */
	public function check_commenter( $comments ) {
		/**
		 * Hook to bypass pending comment check for comment related plugins compatibility
		 *
		 * @since 2.9.5
		 */
		if (apply_filters('litespeed_vary_check_commenter_pending', true)) {
			$pending = false;
			foreach ($comments as $comment) {
				if (!$comment->comment_approved) {
					// current user has pending comment
					$pending = true;
					break;
				}
			}

			// No pending comments, don't need to add private cache
			if (!$pending) {
				self::debug('No pending comment');
				$this->remove_commenter();

				// Remove commenter prefilled info if exists, for public cache
				foreach ($_COOKIE as $cookie_name => $cookie_value) {
					if (strlen($cookie_name) >= 15 && strpos($cookie_name, 'comment_author_') === 0) {
						unset($_COOKIE[$cookie_name]);
					}
				}

				return $comments;
			}
		}

		// Current user/visitor has pending comments
		// set vary=2 for next time vary lookup
		$this->add_commenter();

		if ($this->conf(Base::O_CACHE_COMMENTER)) {
			Control::set_private('existing commenter');
		} else {
			Control::set_nocache('existing commenter');
		}

		return $comments;
	}

	/**
	 * Check if default vary has a value
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function has_vary() {
		if (empty($_COOKIE[self::$_vary_name])) {
			return false;
		}
		return $_COOKIE[self::$_vary_name];
	}

	/**
	 * Append user status with logged in
	 *
	 * @since 1.1.3
	 * @since 1.6.2 Removed static referral
	 * @access public
	 */
	public function add_logged_in( $logged_in_cookie = false, $expire = false, $expiration = false, $uid = false ) {
		self::debug('add_logged_in');

		/**
		 * NOTE: Run before `$this->_update_default_vary()` to make vary changeable
		 *
		 * @since  2.2.2
		 */
		self::can_ajax_vary();

		// If the cookie is lost somehow, set it
		$this->_update_default_vary($uid, $expire);
	}

	/**
	 * Remove user logged in status
	 *
	 * @since 1.1.3
	 * @since 1.6.2 Removed static referral
	 * @access public
	 */
	public function remove_logged_in() {
		self::debug('remove_logged_in');

		/**
		 * NOTE: Run before `$this->_update_default_vary()` to make vary changeable
		 *
		 * @since  2.2.2
		 */
		self::can_ajax_vary();

		// Force update vary to remove login status
		$this->_update_default_vary(-1);
	}

	/**
	 * Allow vary can be changed for ajax calls
	 *
	 * @since 2.2.2
	 * @since 2.6 Changed to static
	 * @access public
	 */
	public static function can_ajax_vary() {
		self::debug('_can_change_vary -> true');
		self::$_can_change_vary = true;
	}

	/**
	 * Check if can change default vary
	 *
	 * @since 1.6.2
	 * @access private
	 */
	private function can_change_vary() {
		// Don't change for ajax due to ajax not sending webp header
		if (Router::is_ajax()) {
			if (!self::$_can_change_vary) {
				self::debug('can_change_vary bypassed due to ajax call');
				return false;
			}
		}

		/**
		 * POST request can set vary to fix #820789 login "loop" guest cache issue
		 *
		 * @since 1.6.5
		 */
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
			self::debug('can_change_vary bypassed due to method not get/post');
			return false;
		}

		/**
		 * Disable vary change if is from crawler
		 *
		 * @since  2.9.8 To enable woocommerce cart not empty warm up (@Taba)
		 */
		if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], Crawler::FAST_USER_AGENT) === 0) {
			self::debug('can_change_vary bypassed due to crawler');
			return false;
		}

		if (!apply_filters('litespeed_can_change_vary', true)) {
			self::debug('can_change_vary bypassed due to litespeed_can_change_vary hook');
			return false;
		}

		return true;
	}

	/**
	 * Update default vary
	 *
	 * @since 1.6.2
	 * @since  1.6.6.1 Add ran check to make it only run once ( No run multiple times due to login process doesn't have valid uid )
	 * @access private
	 */
	private function _update_default_vary( $uid = false, $expire = false ) {
		// Make sure header output only run once
		if (!defined('LITESPEED_DID_' . __FUNCTION__)) {
			define('LITESPEED_DID_' . __FUNCTION__, true);
		} else {
			self::debug2('_update_default_vary bypassed due to run already');
			return;
		}

		// ESI shouldn't change vary (Let main page do only)
		if (defined('LSCACHE_IS_ESI') && LSCACHE_IS_ESI) {
			self::debug2('_update_default_vary bypassed due to ESI');
			return;
		}

		// If the cookie is lost somehow, set it
		$vary         = $this->finalize_default_vary($uid);
		$current_vary = self::has_vary();
		if ($current_vary !== $vary && $current_vary !== 'commenter' && $this->can_change_vary()) {
			// $_COOKIE[ self::$_vary_name ] = $vary; // not needed

			// save it
			if (!$expire) {
				$expire = time() + 2 * DAY_IN_SECONDS;
			}
			$this->_cookie($vary, $expire);
			// Control::set_nocache( 'changing default vary' . " $current_vary => $vary" );
		}
	}

	/**
	 * Get vary name
	 *
	 * @since 1.9.1
	 * @access public
	 */
	public function get_vary_name() {
		return self::$_vary_name;
	}

	/**
	 * Check if one user role is in vary group settings
	 *
	 * @since 1.2.0
	 * @since  3.0 Moved here from conf.cls
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_vary_group( $role ) {
		$group       = 0;
		$vary_groups = $this->conf(Base::O_CACHE_VARY_GROUP);

		$roles = explode(',', $role);
		if ($found = array_intersect($roles, array_keys($vary_groups))) {
			$groups = array();
			foreach ($found as $curr_role) {
				$groups[] = $vary_groups[$curr_role];
			}
			$group = implode(',', array_unique($groups));
		} elseif (in_array('administrator', $roles)) {
			$group = 99;
		}

		if ($group) {
			self::debug2('role in vary_group [group] ' . $group);
		}

		return $group;
	}

	/**
	 * Finalize default Vary Cookie
	 *
	 *  Get user vary tag based on admin_bar & role
	 *
	 * NOTE: Login process will also call this because it does not call wp hook as normal page loading
	 *
	 * @since 1.6.2
	 * @access public
	 */
	public function finalize_default_vary( $uid = false ) {
		// Must check this to bypass vary generation for guests
		// Must check this to avoid Guest page's CSS/JS/CCSS/UCSS get non-guest vary filename
		if (defined('LITESPEED_GUEST') && LITESPEED_GUEST) {
			return false;
		}

		$vary = array();

		if ($this->conf(Base::O_GUEST)) {
			$vary['guest_mode'] = 1;
		}

		if (!$uid) {
			$uid = get_current_user_id();
		} else {
			self::debug('uid: ' . $uid);
		}

		// get user's group id
		$role = Router::get_role($uid);

		if ($uid > 0 && $role) {
			$vary['logged-in'] = 1;

			// parse role group from settings
			if ($role_group = $this->in_vary_group($role)) {
				$vary['role'] = $role_group;
			}

			// Get admin bar set
			// see @_get_admin_bar_pref()
			$pref = get_user_option('show_admin_bar_front', $uid);
			self::debug2('show_admin_bar_front: ' . $pref);
			$admin_bar = $pref === false || $pref === 'true';

			if ($admin_bar) {
				$vary['admin_bar'] = 1;
				self::debug2('admin bar : true');
			}
		} else {
			// Guest user
			self::debug('role id: failed, guest');
		}

		/**
		 * Add filter
		 *
		 * @since 1.6 Added for Role Excludes for optimization cls
		 * @since 1.6.2 Hooked to webp (checked in v4, no webp anymore)
		 * @since 3.0 Used by 3rd hooks too
		 */
		$vary = apply_filters('litespeed_vary', $vary);

		if (!$vary) {
			return false;
		}

		ksort($vary);
		$res = array();
		foreach ($vary as $key => $val) {
			$res[] = $key . ':' . $val;
		}

		$res = implode(';', $res);
		if (defined('LSCWP_LOG')) {
			return $res;
		}
		// Encrypt in production
		return md5($this->conf(Base::HASH) . $res);
	}

	/**
	 * Get the hash of all vary related values
	 *
	 * @since  4.0
	 */
	public function finalize_full_varies() {
		$vary  = $this->_finalize_curr_vary_cookies(true);
		$vary .= $this->finalize_default_vary(get_current_user_id());
		$vary .= $this->get_env_vary();
		return $vary;
	}

	/**
	 * Get request environment Vary
	 *
	 * @since  4.0
	 */
	public function get_env_vary() {
		$env_vary = isset($_SERVER['LSCACHE_VARY_VALUE']) ? $_SERVER['LSCACHE_VARY_VALUE'] : false;
		if (!$env_vary) {
			$env_vary = isset($_SERVER['HTTP_X_LSCACHE_VARY_VALUE']) ? $_SERVER['HTTP_X_LSCACHE_VARY_VALUE'] : false;
		}
		return $env_vary;
	}

	/**
	 * Append user status with commenter
	 *
	 * This is ONLY used when submit a comment
	 *
	 * @since 1.1.6
	 * @access public
	 */
	public function append_commenter() {
		$this->add_commenter(true);
	}

	/**
	 * Correct user status with commenter
	 *
	 * @since 1.1.3
	 * @access private
	 * @param  boolean $from_redirect If the request is from redirect page or not
	 */
	private function add_commenter( $from_redirect = false ) {
		// If the cookie is lost somehow, set it
		if (self::has_vary() !== 'commenter') {
			self::debug('Add commenter');
			// $_COOKIE[ self::$_vary_name ] = 'commenter'; // not needed

			// save it
			// only set commenter status for current domain path
			$this->_cookie('commenter', time() + apply_filters('comment_cookie_lifetime', 30000000), self::_relative_path($from_redirect));
			// Control::set_nocache( 'adding commenter status' );
		}
	}

	/**
	 * Remove user commenter status
	 *
	 * @since 1.1.3
	 * @access private
	 */
	private function remove_commenter() {
		if (self::has_vary() === 'commenter') {
			self::debug('Remove commenter');
			// remove logged in status from global var
			// unset( $_COOKIE[ self::$_vary_name ] ); // not needed

			// save it
			$this->_cookie(false, false, self::_relative_path());
			// Control::set_nocache( 'removing commenter status' );
		}
	}

	/**
	 * Generate relative path for cookie
	 *
	 * @since 1.1.3
	 * @access private
	 * @param  boolean $from_redirect If the request is from redirect page or not
	 */
	private static function _relative_path( $from_redirect = false ) {
		$path = false;
		$tag  = $from_redirect ? 'HTTP_REFERER' : 'SCRIPT_URL';
		if (!empty($_SERVER[$tag])) {
			$path = parse_url($_SERVER[$tag]);
			$path = !empty($path['path']) ? $path['path'] : false;
			self::debug('Cookie Vary path: ' . $path);
		}
		return $path;
	}

	/**
	 * Builds the vary header.
	 *
	 * NOTE: Non caccheable page can still set vary ( for logged in process )
	 *
	 * Currently, this only checks post passwords and 3rd party.
	 *
	 * @since 1.0.13
	 * @access public
	 * @global $post
	 * @return mixed false if the user has the postpass cookie. Empty string if the post is not password protected. Vary header otherwise.
	 */
	public function finalize() {
		// Finalize default vary
		if (!defined('LITESPEED_GUEST') || !LITESPEED_GUEST) {
			$this->_update_default_vary();
		}

		$tp_cookies = $this->_finalize_curr_vary_cookies();

		if (!$tp_cookies) {
			self::debug2('no custimzed vary');
			return;
		}

		self::debug('finalized 3rd party cookies', $tp_cookies);

		return self::X_HEADER . ': ' . implode(',', $tp_cookies);
	}

	/**
	 * Gets vary cookies or their values unique hash that are already added for the current page.
	 *
	 * @since 1.0.13
	 * @access private
	 * @return array List of all vary cookies currently added.
	 */
	private function _finalize_curr_vary_cookies( $values_json = false ) {
		global $post;

		$cookies = array(); // No need to append default vary cookie name

		if (!empty($post->post_password)) {
			$postpass_key = 'wp-postpass_' . COOKIEHASH;
			if ($this->_get_cookie_val($postpass_key)) {
				self::debug('finalize bypassed due to password protected vary ');
				// If user has password cookie, do not cache & ignore existing vary cookies
				Control::set_nocache('password protected vary');
				return false;
			}

			$cookies[] = $values_json ? $this->_get_cookie_val($postpass_key) : $postpass_key;
		}

		$cookies = apply_filters('litespeed_vary_curr_cookies', $cookies);
		if ($cookies) {
			$cookies = array_filter(array_unique($cookies));
			self::debug('vary cookies changed by filter litespeed_vary_curr_cookies', $cookies);
		}

		if (!$cookies) {
			return false;
		}
		// Format cookie name data or value data
		sort($cookies); // This is to maintain the cookie val orders for $values_json=true case.
		foreach ($cookies as $k => $v) {
			$cookies[$k] = $values_json ? $this->_get_cookie_val($v) : 'cookie=' . $v;
		}

		return $values_json ? \json_encode($cookies) : $cookies;
	}

	/**
	 * Get one vary cookie value
	 *
	 * @since  4.0
	 */
	private function _get_cookie_val( $key ) {
		if (!empty($_COOKIE[$key])) {
			return $_COOKIE[$key];
		}

		return false;
	}

	/**
	 * Set the vary cookie.
	 *
	 * If vary cookie changed, must set non cacheable.
	 *
	 * @since 1.0.4
	 * @access private
	 * @param integer $val The value to update.
	 * @param integer $expire Expire time.
	 * @param boolean $path False if use wp root path as cookie path
	 */
	private function _cookie( $val = false, $expire = false, $path = false ) {
		if (!$val) {
			$expire = 1;
		}

		/**
		 * Add HTTPS bypass in case clients use both HTTP and HTTPS version of site
		 *
		 * @since 1.7
		 */
		$is_ssl = $this->conf(Base::O_UTIL_NO_HTTPS_VARY) ? false : is_ssl();

		setcookie(self::$_vary_name, $val, $expire, $path ?: COOKIEPATH, COOKIE_DOMAIN, $is_ssl, true);
		self::debug('set_cookie ---> [k] ' . self::$_vary_name . " [v] $val [ttl] " . ($expire - time()));
	}
}
