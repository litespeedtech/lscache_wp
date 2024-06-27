<?php

/**
 * The core plugin config class.
 *
 * This maintains all the options and settings for this plugin.
 *
 * @since      	1.0.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Conf extends Base
{
	const TYPE_SET = 'set';

	private $_updated_ids = array();
	private $_is_primary = false;

	/**
	 * Specify init logic to avoid infinite loop when calling conf.cls instance
	 *
	 * @since  3.0
	 * @access public
	 */
	public function init()
	{
		// Check if conf exists or not. If not, create them in DB (won't change version if is converting v2.9- data)
		// Conf may be stale, upgrade later
		$this->_conf_db_init();

		/**
		 * Detect if has quic.cloud set
		 * @since  2.9.7
		 */
		if ($this->conf(self::O_CDN_QUIC)) {
			!defined('LITESPEED_ALLOWED') && define('LITESPEED_ALLOWED', true);
		}

		add_action('litespeed_conf_append', array($this, 'option_append'), 10, 2);
		add_action('litespeed_conf_force', array($this, 'force_option'), 10, 2);

		$this->define_cache();
	}

	/**
	 * Init conf related data
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _conf_db_init()
	{
		/**
		 * Try to load options first, network sites can override this later
		 *
		 * NOTE: Load before run `conf_upgrade()` to avoid infinite loop when getting conf in `conf_upgrade()`
		 */
		$this->load_options();

		$ver = $this->conf(self::_VER);

		/**
		 * Don't upgrade or run new installations other than from backend visit at the 2nd time (delay the update)
		 * In this case, just use default conf
		 */
		$has_delay_conf_tag = self::get_option('__activation');
		if (!$ver || $ver != Core::VER) {
			if ((!is_admin() && !defined('LITESPEED_CLI')) || (!$has_delay_conf_tag || $has_delay_conf_tag == -1)) {
				// Reuse __activation to control the delay conf update
				if (!$has_delay_conf_tag || $has_delay_conf_tag == -1) {
					self::update_option('__activation', Core::VER);
				}

				$this->set_conf($this->load_default_vals());
				$this->_try_load_site_options();

				// Disable new installation auto upgrade to avoid overwritten to customized data.ini
				if (!$ver) {
					defined('LITESPEED_BYPASS_AUTO_V') || define('LITESPEED_BYPASS_AUTO_V', true);
				}
				return;
			}
		}

		/**
		 * Version is less than v3.0, or, is a new installation
		 */
		if (!$ver) {
			// Try upgrade first (network will upgrade inside too)
			Data::cls()->try_upgrade_conf_3_0();
		} else {
			defined('LSCWP_CUR_V') || define('LSCWP_CUR_V', $ver);

			/**
			 * Upgrade conf
			 */
			if ($ver != Core::VER) {
				// Plugin version will be set inside
				// Site plugin upgrade & version change will do in load_site_conf
				Data::cls()->conf_upgrade($ver);
			}
		}

		/**
		 * Sync latest new options
		 */
		if (!$ver || $ver != Core::VER) {
			// Load default values
			$this->load_default_vals();
			if (!$ver) {
				// New install
				$this->set_conf(self::$_default_options);
			}

			// Init new default/missing options
			foreach (self::$_default_options as $k => $v) {
				// If the option existed, bypass updating
				// Bcos we may ask clients to deactivate for debug temporarily, we need to keep the current cfg in deactivation, hence we need to only try adding default cfg when activating.
				self::add_option($k, $v);
			}

			// Force correct version in case a rare unexpected case that `_ver` exists but empty
			self::update_option(Base::_VER, Core::VER);
		}

		/**
		 * Network sites only
		 *
		 * Override conf if is network subsites and chose `Use Primary Config`
		 */
		$this->_try_load_site_options();

		// Mark as conf loaded
		defined('LITESPEED_CONF_LOADED') || define('LITESPEED_CONF_LOADED', true);

		/**
		 * Activation delayed file update
		 * Pros: This is to avoid file correction script changed in new versions
		 * Cons: Conf upgrade won't get file correction if there is new values that are used in file
		 */
		if ($has_delay_conf_tag && $has_delay_conf_tag != -1) {
			// Check new version @since 2.9.3
			Cloud::version_check('activate' . (defined('LSCWP_REF') ? '_' . LSCWP_REF : ''));

			$this->update_confs(); // Files only get corrected in activation or saving settings actions.
		}
		if ($has_delay_conf_tag != -1) {
			self::update_option('__activation', -1);
		}
	}

	/**
	 * Load all latest options from DB
	 *
	 * @since  3.0
	 * @access public
	 */
	public function load_options($blog_id = null, $dry_run = false)
	{
		$options = array();
		foreach (self::$_default_options as $k => $v) {
			if (!is_null($blog_id)) {
				$options[$k] = self::get_blog_option($blog_id, $k, $v);
			} else {
				$options[$k] = self::get_option($k, $v);
			}

			// Correct value type
			$options[$k] = $this->type_casting($options[$k], $k);
		}

		if ($dry_run) {
			return $options;
		}

		// Bypass site special settings
		if ($blog_id !== null) {
			// This is to load the primary settings ONLY
			// These options are the ones that can be overwritten by primary
			$options = array_diff_key($options, array_flip(self::$SINGLE_SITE_OPTIONS));

			$this->set_primary_conf($options);
		} else {
			$this->set_conf($options);
		}

		// Append const options
		if (defined('LITESPEED_CONF') && LITESPEED_CONF) {
			foreach (self::$_default_options as $k => $v) {
				$const = Base::conf_const($k);
				if (defined($const)) {
					$this->set_const_conf($k, $this->type_casting(constant($const), $k));
				}
			}
		}
	}

	/**
	 * For multisite installations, the single site options need to be updated with the network wide options.
	 *
	 * @since 1.0.13
	 * @access private
	 */
	private function _try_load_site_options()
	{
		if (!$this->_if_need_site_options()) {
			return;
		}

		$this->_conf_site_db_init();

		$this->_is_primary = get_current_blog_id() == BLOG_ID_CURRENT_SITE;

		// If network set to use primary setting
		if ($this->network_conf(self::NETWORK_O_USE_PRIMARY) && !$this->_is_primary) {
			// subsites or network admin
			// Get the primary site settings
			// If it's just upgraded, 2nd blog is being visited before primary blog, can just load default config (won't hurt as this could only happen shortly)
			$this->load_options(BLOG_ID_CURRENT_SITE);
		}

		// Overwrite single blog options with site options
		foreach (self::$_default_options as $k => $v) {
			if (!$this->has_network_conf($k)) {
				continue;
			}
			// $this->_options[ $k ] = $this->_network_options[ $k ];

			// Special handler to `Enable Cache` option if the value is set to OFF
			if ($k == self::O_CACHE) {
				if ($this->_is_primary) {
					if ($this->conf($k) != $this->network_conf($k)) {
						if ($this->conf($k) != self::VAL_ON2) {
							continue;
						}
					}
				} else {
					if ($this->network_conf(self::NETWORK_O_USE_PRIMARY)) {
						if ($this->has_primary_conf($k) && $this->primary_conf($k) != self::VAL_ON2) {
							// This case will use primary_options override always
							continue;
						}
					} else {
						if ($this->conf($k) != self::VAL_ON2) {
							continue;
						}
					}
				}
			}

			// primary_options will store primary settings + network settings, OR, store the network settings for subsites
			$this->set_primary_conf($k, $this->network_conf($k));
		}
		// var_dump($this->_options);
	}

	/**
	 * Check if needs to load site_options for network sites
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _if_need_site_options()
	{
		if (!is_multisite()) {
			return false;
		}

		// Check if needs to use site_options or not
		// todo: check if site settings are separate bcos it will affect .htaccess

		/**
		 * In case this is called outside the admin page
		 * @see  https://codex.wordpress.org/Function_Reference/is_plugin_active_for_network
		 * @since  2.0
		 */
		if (!function_exists('is_plugin_active_for_network')) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		// If is not activated on network, it will not have site options
		if (!is_plugin_active_for_network(Core::PLUGIN_FILE)) {
			if ((int) $this->conf(self::O_CACHE) == self::VAL_ON2) {
				// Default to cache on
				$this->set_conf(self::_CACHE, true);
			}
			return false;
		}

		return true;
	}

	/**
	 * Init site conf and upgrade if necessary
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _conf_site_db_init()
	{
		$this->load_site_options();

		$ver = $this->network_conf(self::_VER);

		/**
		 * Don't upgrade or run new installations other than from backend visit
		 * In this case, just use default conf
		 */
		if (!$ver || $ver != Core::VER) {
			if (!is_admin() && !defined('LITESPEED_CLI')) {
				$this->set_network_conf($this->load_default_site_vals());
				return;
			}
		}

		/**
		 * Upgrade conf
		 */
		if ($ver && $ver != Core::VER) {
			// Site plugin version will change inside
			Data::cls()->conf_site_upgrade($ver);
		}

		/**
		 * Is a new installation
		 */
		if (!$ver || $ver != Core::VER) {
			// Load default values
			$this->load_default_site_vals();

			// Init new default/missing options
			foreach (self::$_default_site_options as $k => $v) {
				// If the option existed, bypass updating
				self::add_site_option($k, $v);
			}
		}
	}

	/**
	 * Get the plugin's site wide options.
	 *
	 * If the site wide options are not set yet, set it to default.
	 *
	 * @since 1.0.2
	 * @access public
	 */
	public function load_site_options()
	{
		if (!is_multisite()) {
			return null;
		}

		// Load all site options
		foreach (self::$_default_site_options as $k => $v) {
			$val = self::get_site_option($k, $v);
			$val = $this->type_casting($val, $k, true);
			$this->set_network_conf($k, $val);
		}
	}

	/**
	 * Append a 3rd party option to default options
	 *
	 * This will not be affected by network use primary site setting.
	 *
	 * NOTE: If it is a multi switch option, need to call `_conf_multi_switch()` first
	 *
	 * @since  3.0
	 * @access public
	 */
	public function option_append($name, $default)
	{
		self::$_default_options[$name] = $default;
		$this->set_conf($name, self::get_option($name, $default));
		$this->set_conf($name, $this->type_casting($this->conf($name), $name));
	}

	/**
	 * Force an option to a certain value
	 *
	 * @since  2.6
	 * @access public
	 */
	public function force_option($k, $v)
	{
		if (!$this->has_conf($k)) {
			return;
		}

		$v = $this->type_casting($v, $k);

		if ($this->conf($k) === $v) {
			return;
		}

		Debug2::debug("[Conf] ** $k forced from " . var_export($this->conf($k), true) . ' to ' . var_export($v, true));

		$this->set_conf($k, $v);
	}

	/**
	 * Define `_CACHE` const in options ( for both single and network )
	 *
	 * @since  3.0
	 * @access public
	 */
	public function define_cache()
	{
		// Init global const cache on setting
		$this->set_conf(self::_CACHE, false);
		if ((int) $this->conf(self::O_CACHE) == self::VAL_ON || $this->conf(self::O_CDN_QUIC)) {
			$this->set_conf(self::_CACHE, true);
		}

		// Check network
		if (!$this->_if_need_site_options()) {
			// Set cache on
			$this->_define_cache_on();
			return;
		}

		// If use network setting
		if ((int) $this->conf(self::O_CACHE) == self::VAL_ON2 && $this->network_conf(self::O_CACHE)) {
			$this->set_conf(self::_CACHE, true);
		}

		$this->_define_cache_on();
	}

	/**
	 * Define `LITESPEED_ON`
	 *
	 * @since 2.1
	 * @access private
	 */
	private function _define_cache_on()
	{
		if (!$this->conf(self::_CACHE)) {
			return;
		}

		defined('LITESPEED_ALLOWED') && !defined('LITESPEED_ON') && define('LITESPEED_ON', true);
	}

	/**
	 * Get an option value
	 *
	 * @since  3.0
	 * @access public
	 * @deprecated 4.0 Use $this->conf() instead
	 */
	public static function val($id, $ori = false)
	{
		error_log('Called deprecated function \LiteSpeed\Conf::val(). Please use API call instead.');
		return self::cls()->conf($id, $ori);
	}

	/**
	 * Save option
	 *
	 * @since  3.0
	 * @access public
	 */
	public function update_confs($the_matrix = false)
	{
		if ($the_matrix) {
			foreach ($the_matrix as $id => $val) {
				$this->update($id, $val);
			}
		}

		if ($this->_updated_ids) {
			foreach ($this->_updated_ids as $id) {
				// Special handler for QUIC.cloud domain key to clear all existing nodes
				if ($id == self::O_API_KEY) {
					$this->cls('Cloud')->clear_cloud();
				}

				// Special handler for crawler: reset sitemap when drop_domain setting changed
				if ($id == self::O_CRAWLER_DROP_DOMAIN) {
					$this->cls('Crawler_Map')->empty_map();
				}

				// Check if need to do a purge all or not
				if ($this->_conf_purge_all($id)) {
					Purge::purge_all('conf changed [id] ' . $id);
				}

				// Check if need to purge a tag
				if ($tag = $this->_conf_purge_tag($id)) {
					Purge::add($tag);
				}

				// Update cron
				if ($this->_conf_cron($id)) {
					$this->cls('Task')->try_clean($id);
				}

				// Reset crawler bypassed list when any of the options WebP replace, guest mode, or cache mobile got changed
				if ($id == self::O_IMG_OPTM_WEBP || $id == self::O_GUEST || $id == self::O_CACHE_MOBILE) {
					$this->cls('Crawler')->clear_disabled_list();
				}
			}
		}

		do_action('litespeed_update_confs', $the_matrix);

		// Update related tables
		$this->cls('Data')->correct_tb_existence();

		// Update related files
		$this->cls('Activation')->update_files();

		/**
		 * CDN related actions - Cloudflare
		 */
		$this->cls('CDN\Cloudflare')->try_refresh_zone();

		/**
		 * CDN related actions - QUIC.cloud
		 * @since 2.3
		 */
		$this->cls('CDN\Quic')->try_sync_conf();
	}

	/**
	 * Save option
	 *
	 * Note: this is direct save, won't trigger corresponding file update or data sync. To save settings normally, always use `Conf->update_confs()`
	 *
	 * @since  3.0
	 * @access public
	 */
	public function update($id, $val)
	{
		// Bypassed this bcos $this->_options could be changed by force_option()
		// if ( $this->_options[ $id ] === $val ) {
		// 	return;
		// }

		if ($id == self::_VER) {
			return;
		}

		if ($id == self::O_SERVER_IP) {
			if ($val && !Utility::valid_ipv4($val)) {
				$msg = sprintf(__('Saving option failed. IPv4 only for %s.', 'litespeed-cache'), Lang::title(Base::O_SERVER_IP));
				Admin_Display::error($msg);
				return;
			}
		}

		if (!array_key_exists($id, self::$_default_options)) {
			defined('LSCWP_LOG') && Debug2::debug('[Conf] Invalid option ID ' . $id);
			return;
		}

		if ($val && $this->_conf_pswd($id) && !preg_match('/[^\*]/', $val)) {
			return;
		}

		// Special handler for CDN Original URLs
		if ($id == self::O_CDN_ORI && !$val) {
			$home_url = home_url('/');
			$parsed = parse_url($home_url);
			$home_url = str_replace($parsed['scheme'] . ':', '', $home_url);

			$val = $home_url;
		}

		// Validate type
		$val = $this->type_casting($val, $id);

		// Save data
		self::update_option($id, $val);

		// Handle purge if setting changed
		if ($this->conf($id) != $val) {
			$this->_updated_ids[] = $id;

			// Check if need to fire a purge or not (Here has to stay inside `update()` bcos need comparing old value)
			if ($this->_conf_purge($id)) {
				$diff = array_diff($val, $this->conf($id));
				$diff2 = array_diff($this->conf($id), $val);
				$diff = array_merge($diff, $diff2);
				// If has difference
				foreach ($diff as $v) {
					$v = ltrim($v, '^');
					$v = rtrim($v, '$');
					$this->cls('Purge')->purge_url($v);
				}
			}
		}

		// Update in-memory data
		$this->set_conf($id, $val);
	}

	/**
	 * Save network option
	 *
	 * @since  3.0
	 * @access public
	 */
	public function network_update($id, $val)
	{
		if (!array_key_exists($id, self::$_default_site_options)) {
			defined('LSCWP_LOG') && Debug2::debug('[Conf] Invalid network option ID ' . $id);
			return;
		}

		if ($val && $this->_conf_pswd($id) && !preg_match('/[^\*]/', $val)) {
			return;
		}

		// Validate type
		if (is_bool(self::$_default_site_options[$id])) {
			$max = $this->_conf_multi_switch($id);
			if ($max && $val > 1) {
				$val %= $max + 1;
			} else {
				$val = (bool) $val;
			}
		} elseif (is_array(self::$_default_site_options[$id])) {
			// from textarea input
			if (!is_array($val)) {
				$val = Utility::sanitize_lines($val, $this->_conf_filter($id));
			}
		} elseif (!is_string(self::$_default_site_options[$id])) {
			$val = (int) $val;
		} else {
			// Check if the string has a limit set
			$val = $this->_conf_string_val($id, $val);
		}

		// Save data
		self::update_site_option($id, $val);

		// Handle purge if setting changed
		if ($this->network_conf($id) != $val) {
			// Check if need to do a purge all or not
			if ($this->_conf_purge_all($id)) {
				Purge::purge_all('[Conf] Network conf changed [id] ' . $id);
			}

			// Update in-memory data
			$this->set_network_conf($id, $val);
		}

		// No need to update cron here, Cron will register in each init

		if ($this->has_conf($id)) {
			$this->set_conf($id, $val);
		}
	}

	/**
	 * Check if one user role is in exclude optimization group settings
	 *
	 * @since 1.6
	 * @access public
	 * @param  string $role The user role
	 * @return int       The set value if already set
	 */
	public function in_optm_exc_roles($role = null)
	{
		// Get user role
		if ($role === null) {
			$role = Router::get_role();
		}

		if (!$role) {
			return false;
		}

		$roles = explode(',', $role);
		$found = array_intersect($roles, $this->conf(self::O_OPTM_EXC_ROLES));

		return $found ? implode(',', $found) : false;
	}

	/**
	 * Set one config value directly
	 *
	 * @since  2.9
	 * @access private
	 */
	private function _set_conf()
	{
		/**
		 * NOTE: For URL Query String setting,
		 * 		1. If append lines to an array setting e.g. `cache-force_uri`, use `set[cache-force_uri][]=the_url`.
		 *   	2. If replace the array setting with one line, use `set[cache-force_uri]=the_url`.
		 *   	3. If replace the array setting with multi lines value, use 2 then 1.
		 */
		if (empty($_GET[self::TYPE_SET]) || !is_array($_GET[self::TYPE_SET])) {
			return;
		}

		$the_matrix = array();
		foreach ($_GET[self::TYPE_SET] as $id => $v) {
			if (!$this->has_conf($id)) {
				continue;
			}

			// Append new item to array type settings
			if (is_array($v) && is_array($this->conf($id))) {
				$v = array_merge($this->conf($id), $v);

				Debug2::debug('[Conf] Appended to settings [' . $id . ']: ' . var_export($v, true));
			} else {
				Debug2::debug('[Conf] Set setting [' . $id . ']: ' . var_export($v, true));
			}

			$the_matrix[$id] = $v;
		}

		if (!$the_matrix) {
			return;
		}

		$this->update_confs($the_matrix);

		$msg = __('Changed setting successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);

		// Redirect if changed frontend URL
		if (!empty($_GET['redirect'])) {
			wp_redirect($_GET['redirect']);
			exit();
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.9
	 * @access public
	 */
	public function handler()
	{
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_SET:
				$this->_set_conf();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
