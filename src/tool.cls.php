<?php
/**
 * The tools
 *
 * @since      	3.0
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined('WPINC') || exit();

class Tool extends Root
{
	/**
	 * Get public IP
	 *
	 * @since  3.0
	 * @access public
	 */
	public function check_ip()
	{
		Debug2::debug('[Tool] ✅ check_ip');

		$response = wp_remote_get('https://www.doapi.us/ip');

		if (is_wp_error($response)) {
			return new \WP_Error('remote_get_fail', 'Failed to fetch from https://www.doapi.us/ip', array('status' => 404));
		}

		$data = $response['body'];

		Debug2::debug('[Tool] result [ip] ' . $data);

		return $data;
	}

	/**
	 * Heartbeat Control
	 *
	 * NOTE: since WP4.9, there could be a core bug that sometimes the hook is not working.
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat()
	{
		add_action('wp_enqueue_scripts', array($this, 'heartbeat_frontend'));
		add_action('admin_enqueue_scripts', array($this, 'heartbeat_backend'));
		add_filter('heartbeat_settings', array($this, 'heartbeat_settings'));
	}

	/**
	 * Heartbeat Control frontend control
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_frontend()
	{
		if (!$this->conf(Base::O_MISC_HEARTBEAT_FRONT)) {
			return;
		}

		if (!$this->conf(Base::O_MISC_HEARTBEAT_FRONT_TTL)) {
			wp_deregister_script('heartbeat');
			Debug2::debug('[Tool] Deregistered frontend heartbeat');
		}
	}

	/**
	 * Heartbeat Control backend control
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_backend()
	{
		if ($this->_is_editor()) {
			if (!$this->conf(Base::O_MISC_HEARTBEAT_EDITOR)) {
				return;
			}

			if (!$this->conf(Base::O_MISC_HEARTBEAT_EDITOR_TTL)) {
				wp_deregister_script('heartbeat');
				Debug2::debug('[Tool] Deregistered editor heartbeat');
			}
		} else {
			if (!$this->conf(Base::O_MISC_HEARTBEAT_BACK)) {
				return;
			}

			if (!$this->conf(Base::O_MISC_HEARTBEAT_BACK_TTL)) {
				wp_deregister_script('heartbeat');
				Debug2::debug('[Tool] Deregistered backend heartbeat');
			}
		}
	}

	/**
	 * Heartbeat Control settings
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_settings($settings)
	{
		// Check editor first to make frontend editor valid too
		if ($this->_is_editor()) {
			if ($this->conf(Base::O_MISC_HEARTBEAT_EDITOR)) {
				$settings['interval'] = $this->conf(Base::O_MISC_HEARTBEAT_EDITOR_TTL);
				Debug2::debug('[Tool] Heartbeat interval set to ' . $this->conf(Base::O_MISC_HEARTBEAT_EDITOR_TTL));
			}
		} elseif (!is_admin()) {
			if ($this->conf(Base::O_MISC_HEARTBEAT_FRONT)) {
				$settings['interval'] = $this->conf(Base::O_MISC_HEARTBEAT_FRONT_TTL);
				Debug2::debug('[Tool] Heartbeat interval set to ' . $this->conf(Base::O_MISC_HEARTBEAT_FRONT_TTL));
			}
		} else {
			if ($this->conf(Base::O_MISC_HEARTBEAT_BACK)) {
				$settings['interval'] = $this->conf(Base::O_MISC_HEARTBEAT_BACK_TTL);
				Debug2::debug('[Tool] Heartbeat interval set to ' . $this->conf(Base::O_MISC_HEARTBEAT_BACK_TTL));
			}
		}
		return $settings;
	}

	/**
	 * If is in editor
	 *
	 * @since  3.0
	 * @access public
	 */
	private function _is_editor()
	{
		$res = is_admin() && Utility::str_hit_array($_SERVER['REQUEST_URI'], array('post.php', 'post-new.php'));

		return apply_filters('litespeed_is_editor', $res);
	}
}
