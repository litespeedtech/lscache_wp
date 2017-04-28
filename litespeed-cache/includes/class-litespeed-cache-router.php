<?php

/**
 * The core plugin router class.
 *
 * This generate the valid action.
 *
 * @since      1.0.16
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Router extends LiteSpeed{
	protected static $_instance;
	private $_action = false;
	private $_ip = false;
	private $_is_ajax = false;

	protected function __construct(){
	}

	/**
	 * Generate action
	 * @since 1.0.16
	 */
	public static function init(){
		self::get_instance()->_is_ajax = defined('DOING_AJAX') && DOING_AJAX;
		self::get_instance()->verify_action();
	}

	/**
	 * Check action
	 * @since 1.0.16
	 * @return string
	 */
	public static function action(){
		return self::get_instance()->_action;
	}

	/**
	 * Check if is ajax call
	 * @return boolean
	 */
	public static function is_ajax(){
		return self::get_instance()->_is_ajax;
	}

	/**
	 * Check privilege and nonce for the action
	 * @since 1.0.16
	 */
	private function verify_action(){
		if(empty($_REQUEST[LiteSpeed_Cache::ACTION_KEY])) return;

		$action = $_REQUEST[LiteSpeed_Cache::ACTION_KEY];
		$_is_public_action = false;

		// Each action must have a valid nonce unless its from admin ip and is public action
		// Validate requests nonce (from admin logged in page or cli)
		if(!$this->verify_nonce($action)){

			// check if it is from admin ip
			$ips = LiteSpeed_Cache_Config::get_instance()->get_option(LiteSpeed_Cache_Config::OPID_ADMIN_IPS);
			if(!$this->ip_access($ips)){
				if (defined('LSCWP_LOG')) {
					LiteSpeed_Cache::debug_log('LSCWP_CTRL query string - did not match admin IP');
				}
				return;
			}

			// check if it is public action
			if(!in_array($action, array(
					LiteSpeed_Cache::ACTION_NOCACHE,
					LiteSpeed_Cache::ACTION_PURGE,
					LiteSpeed_Cache::ACTION_PURGE_SINGLE,
					LiteSpeed_Cache::ACTION_SHOW_HEADERS,
			))) {
				if (defined('LSCWP_LOG')) {
					LiteSpeed_Cache::debug_log('LSCWP_CTRL query string - did not match admin IP Actions');
				}
				return;
			}

			$_is_public_action = true;
		}

		/* Now it is a valid action, lets log and check the permission */
		if (defined('LSCWP_LOG')) {
			LiteSpeed_Cache::debug_log('LSCWP_CTRL query string action is ' . $action);
		}

		// OK, as we want to do something magic, lets check if its allowed
		$_is_enabled = LiteSpeed_Cache_Config::get_instance()->is_plugin_enabled();
		$_is_multisite = is_multisite();
		$_is_network_admin = $_is_multisite && is_network_admin();
		$_can_network_option = $_is_network_admin && current_user_can('manage_network_options');
		$_can_option = current_user_can('manage_options');

		//todo: check if is cli

		switch ($action) {

			// Save htaccess
			case LiteSpeed_Cache::ACTION_SAVE_HTACCESS:
				if((!$_is_multisite && $_can_option) || $_can_network_option){
					$this->_action = $action;
				}
				return;

			// Save network settings
			case LiteSpeed_Cache::ACTION_SAVE_SETTINGS_NETWORK:
				if ($_can_network_option) {
					$this->_action = $action;
				}
				return;

			case LiteSpeed_Cache::ACTION_PURGE_FRONT:
			case LiteSpeed_Cache::ACTION_PURGE_PAGES:
			case LiteSpeed_Cache::ACTION_PURGE_ERRORS:
			case LiteSpeed_Cache::ACTION_PURGE_ALL:
			case LiteSpeed_Cache::ACTION_PURGE_BY:
				if($_is_enabled && ($_can_network_option || $_can_option)){
					$this->_action = $action;
				}
				return;

			case LiteSpeed_Cache::ACTION_PURGE_EMPTYCACHE:
				if($_is_enabled && ($_can_network_option || (!$_is_multisite && $_can_option))){
					$this->_action = $action;
				}
				return;

			case LiteSpeed_Cache::ACTION_NOCACHE:
			case LiteSpeed_Cache::ACTION_PURGE:
			case LiteSpeed_Cache::ACTION_PURGE_SINGLE:
			case LiteSpeed_Cache::ACTION_SHOW_HEADERS:
				if($_is_enabled && $_is_public_action){
					$this->_action = $action;
				}
				return;

			// Handle the ajax request to proceed crawler manually by admin
			case LiteSpeed_Cache::ACTION_DO_CRAWL:
				if ($_is_enabled && self::is_ajax()) {
					$this->_action = $action;
				}
				return;

			default:
				return;
		}

	}

	/**
	 * Verify nonce
	 * @since 1.0.16
	 * @param  string $action
	 * @return bool
	 */
	private function verify_nonce($action){
		if(!isset($_REQUEST[LiteSpeed_Cache::NONCE_NAME]) || !wp_verify_nonce($_REQUEST[LiteSpeed_Cache::NONCE_NAME], $action)){
			return false;
		}else{
			return true;
		}
	}

	/**
	 * Check if the ip is in the range
	 * @since 1.0.16
	 * @param  string $ial IP list
	 * @return bool
	 */
	private function ip_access($ial){
		if(!$ial) Return false;
		if(!$this->_ip){
			$this->_ip = $this->get_ip();
		}
		$uip = explode('.', $this->_ip);
		if(empty($uip) || count($uip) != 4) Return false;
		if(!is_array($ial)) $ial = explode("\n", $ial);
		foreach($ial as $key => $ip) $ial[$key] = explode('.', trim($ip));
		foreach($ial as $key => $ip) {
			if(count($ip) != 4) continue;
			for($i = 0; $i <= 3; $i++) if($ip[$i] == '*') $ial[$key][$i] = $uip[$i];
		}
		return in_array($uip, $ial);
	}

	/**
	 * Get client ip
	 * @since 1.0.16
	 * @return string
	 */
	private function get_ip(){
		$_ip = '';
		if(function_exists('apache_request_headers')) {
			$apache_headers = apache_request_headers();
			$_ip = !empty($apache_headers['True-Client-IP']) ? $apache_headers['True-Client-IP'] : false;
			if (!$_ip) {
				$_ip = !empty($apache_headers['X-Forwarded-For']) ? $apache_headers['X-Forwarded-For'] : false;
				$_ip = explode(", ", $_ip);
				$_ip = array_shift($_ip);
			}
			if (!$_ip) {
				$_ip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
			}
		}
		return $_ip;
	}

}
