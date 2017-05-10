<?php

/**
 * The plugin cookie class.
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Cookie extends LiteSpeed
{

	/**
	 * Adds the actions used for setting up cookies on log in/out.
	 *
	 * Also checks if the database matches the rewrite rule.
	 *
	 * @since 1.0.4
	 * @access public
	 * @return boolean True if cookies are bad, false otherwise.
	 */
	public function setup_cookies()
	{
		$ret = false;
		// Set vary cookie for logging in user, unset for logging out.
		add_action('set_logged_in_cookie', array( $this, 'set_user_cookie'), 10, 5);
		add_action('clear_auth_cookie', array( $this, 'set_user_cookie'), 10, 5);

		if ( !LiteSpeed_Cache_Config::get_instance()->get_option(
				LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS) )
		{
			// Set vary cookie for commenter.
			add_action('set_comment_cookies', array( $this, 'set_comment_cookie'), 10, 2);
		}
		if ( is_multisite() )
		{
			$options = LiteSpeed_Cache_Config::get_instance()->get_site_options();
			if (is_array($options))
			{
				$db_cookie = $options[LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE];
			}
		}
		else {
			$db_cookie = LiteSpeed_Cache_Config::get_instance()->get_option(LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE);
		}

		if ( !isset($_SERVER[LiteSpeed_Cache::LSCOOKIE_VARY_NAME]) )
		{
			if ( !empty($db_cookie) )
			{
				$ret = true;
				if ( is_multisite() ? is_network_admin() : is_admin() )
				{
					LiteSpeed_Cache_Admin_Display::show_error_cookie();
				}
			}
			LiteSpeed_Cache::get_instance()->set_vary(LiteSpeed_Cache::LSCOOKIE_DEFAULT_VARY);
			return $ret;
		}
		elseif ( empty($db_cookie) )
		{
			LiteSpeed_Cache::get_instance()->set_vary(LiteSpeed_Cache::LSCOOKIE_DEFAULT_VARY);
			return $ret;
		}
		// beyond this point, need to do more processing.
		$vary_arr = explode(',', $_SERVER[LiteSpeed_Cache::LSCOOKIE_VARY_NAME]);

		if ( in_array($db_cookie, $vary_arr) )
		{
			LiteSpeed_Cache::get_instance()->set_vary($db_cookie);
			return $ret;
		}
		elseif ( is_multisite() ? is_network_admin() : is_admin() )
		{
			LiteSpeed_Cache_Admin_Display::show_error_cookie();
		}

		$ret = true;
		LiteSpeed_Cache::get_instance()->set_vary(LiteSpeed_Cache::LSCOOKIE_DEFAULT_VARY);
		return $ret;
	}

	/**
	 * Do the action of setting the vary cookie.
	 *
	 * Since we are using bitwise operations, if the resulting cookie has
	 * value zero, we need to set the expire time appropriately.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param integer $update_val The value to update.
	 * @param integer $expire Expire time.
	 * @param boolean $ssl True if ssl connection, false otherwise.
	 * @param boolean $httponly True if the cookie is for http requests only, false otherwise.
	 */
	public function do_set_cookie($update_val, $expire, $ssl = false, $httponly = false)
	{
		$vary = LiteSpeed_Cache::get_instance()->get_vary();
		$curval = 0;
		if ( isset($_COOKIE[$vary]) )
		{
			$curval = intval($_COOKIE[$vary]);
		}

		// not, remove from curval.
		if ( $update_val < 0 )
		{
			// If cookie will no longer exist, delete the cookie.
			if ( ($curval == 0) || ($curval == (~$update_val)) )
			{
				// Use a year in case of bad local clock.
				$expire = time() - 31536001;
			}
			$curval &= $update_val;
		}
		else
		{ // add to curval.
			$curval |= $update_val;
		}
		setcookie($vary, $curval, $expire, COOKIEPATH, COOKIE_DOMAIN, $ssl, $httponly);
	}

	/**
	 * Sets cookie denoting logged in/logged out.
	 *
	 * This will notify the server on next page request not to serve from cache.
	 *
	 * @since 1.0.1
	 * @access public
	 * @param mixed $logged_in_cookie
	 * @param string $expire Expire time.
	 * @param integer $expiration Expire time.
	 * @param integer $user_id The user's id.
	 * @param string $action Whether the user is logging in or logging out.
	 */
	public function set_user_cookie($logged_in_cookie = false, $expire = ' ',
					$expiration = 0, $user_id = 0, $action = 'logged_out')
	{
		if ($action == 'logged_in')
		{
			$this->do_set_cookie(LiteSpeed_Cache::LSCOOKIE_VARY_LOGGED_IN, $expire, is_ssl(), true);
		}
		else
		{
			$this->do_set_cookie(~LiteSpeed_Cache::LSCOOKIE_VARY_LOGGED_IN,
					time() + apply_filters( 'comment_cookie_lifetime', 30000000 ));
		}
	}

	/**
	 * Sets a cookie that marks the user as a commenter.
	 *
	 * This will notify the server on next page request not to serve
	 * from cache if that setting is enabled.
	 *
	 * @since 1.0.4
	 * @access public
	 * @param mixed $comment Comment object
	 * @param mixed $user The visiting user object.
	 */
	public function set_comment_cookie($comment, $user)
	{
		if ( $user->exists() )
		{
			return;
		}
		$comment_cookie_lifetime = time() + apply_filters( 'comment_cookie_lifetime', 30000000 );
		$this->do_set_cookie(LiteSpeed_Cache::LSCOOKIE_VARY_COMMENTER, $comment_cookie_lifetime);
	}

	/**
	 * Check if the user accessing the page has the commenter cookie.
	 *
	 * If the user does not want to cache commenters, just check if user is commenter.
	 * Otherwise if the vary cookie is set, unset it. This is so that when
	 * the page is cached, the page will appear as if the user was a normal user.
	 * Normal user is defined as not a logged in user and not a commenter.
	 *
	 * @since 1.0.4
	 * @access public
	 * @return boolean True if do not cache for commenters and user is a commenter. False otherwise.
	 */
	public function check_cookies()
	{
		$vary = LiteSpeed_Cache::get_instance()->get_vary();

		if ( !LiteSpeed_Cache_Config::get_instance()->get_option(
				LiteSpeed_Cache_Config::OPID_CACHE_COMMENTERS) )
		{
			// If do not cache commenters, check cookie for commenter value.
			if ( isset($_COOKIE[$vary])
					&& ($_COOKIE[$vary]
						& LiteSpeed_Cache::LSCOOKIE_VARY_COMMENTER))
			{
				$this->user_status |= self::LSCOOKIE_VARY_COMMENTER;
				return true;
			}
			// If wp commenter cookie exists, need to set vary and do not cache.
			foreach( $_COOKIE as $cookie_name => $cookie_value )
			{
				if ( strlen($cookie_name) >= 15
						&& strncmp($cookie_name, 'comment_author_', 15) == 0)
				{
					$user = wp_get_current_user();
					$this->set_comment_cookie(NULL, $user);
					$this->user_status |= self::LSCOOKIE_VARY_COMMENTER;
					return true;
				}
			}
			return false;
		}

		// If vary cookie is set, need to change the value.
		if ( isset($_COOKIE[$vary]) )
		{
			$this->do_set_cookie(~LiteSpeed_Cache::LSCOOKIE_VARY_COMMENTER, 14 * DAY_IN_SECONDS);
			unset($_COOKIE[$vary]);
		}

		// If cache commenters, unset comment cookies for caching.
		foreach( $_COOKIE as $cookie_name => $cookie_value )
		{
			if ( strlen($cookie_name) >= 15
					&& strncmp($cookie_name, 'comment_author_', 15) == 0)
			{
				$this->user_status |= self::LSCOOKIE_VARY_COMMENTER;
				unset($_COOKIE[$cookie_name]);
			}
		}
		return false;
	}

}
