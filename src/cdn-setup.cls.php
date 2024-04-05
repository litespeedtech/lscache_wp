<?php

/**
 * CDN Setup service cls
 *
 * @since      3.0
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Cdn_Setup extends Base
{
	const LOG_TAG = '👷';

	const TYPE_LINK = 'link';
	const TYPE_NOLINK = 'nolink';
	const TYPE_RUN = 'setup';
	const TYPE_STATUS = 'status';
	const TYPE_RESET = 'reset';
	const TYPE_DELETE = 'delete';

	private $_setup_token;
	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		$this->_setup_token = $this->conf(self::O_QC_TOKEN);
		$this->_summary = self::get_summary();
	}

	/**
	 * Update is_linked status if is a redirected back from QC
	 *
	 * @since  3.0
	 */
	public function maybe_extract_token()
	{
		$params = $this->cls('Cloud')->parse_qc_redir(array('token'));

		if (isset($params['token'])) {
			$this->_setup_token = esc_html($params['token']);
			$this->cls('Conf')->update_confs(array(self::O_QC_TOKEN => $this->_setup_token));
			unset($_GET['token']);
		}
	}

	/**
	 * Callback for updating Auto CDN Setup status after run
	 *
	 * @since  4.7
	 * @access public
	 */
	public function update_cdn_status()
	{
		if (empty($_POST['hash'])) {
			self::debug('Lack of hash param');
			return self::err('lack_of_param');
		}

		if ($_POST['hash'] !== md5(substr($this->conf(self::O_API_KEY), 3, 8))) {
			self::debug('token validate failed: token mismatch hash !== ' . $_POST['hash']);
			return self::err('callback_fail_hash');
		}

		if (!isset($_POST['success']) || !isset($_POST['result'])) {
			self::save_summary(array('cdn_setup_err' => __('Received invalid message from the cloud server. Please submit a ticket.', 'litespeed-cache')));
			return self::err('lack_of_param');
		}
		if (!$_POST['success'] && !empty($_POST['result']['_msg'])) {
			$msg = wp_kses_post($_POST['result']['_msg']);
			self::save_summary(array('cdn_setup_err' => $msg));
			Admin_Display::error(__('There was an error during CDN setup: ', 'litespeed-cache') . $msg);
		} else {
			$this->_process_cdn_status($_POST['result']);
		}

		return self::ok();
	}

	/**
	 * Request an update on Auto CDN Setup status
	 *
	 * @since  4.7
	 * @access private
	 */
	private function _qc_refresh()
	{
		$json = $this->cls('Cloud')->req_rest_api('/user/cdn/status');

		if (!$json) {
			return;
		} elseif (is_string($json)) {
			self::save_summary(array('cdn_setup_err' => $json));
			return;
		}

		$result = array();
		if (isset($json['info']['messages'])) {
			$result['_msg'] = implode('<br>', $json['info']['messages']);
		}
		$this->_process_cdn_status($result);
	}

	/**
	 * Process the returned Auto CDN Setup status
	 *
	 * @since  4.7
	 * @access private
	 */
	private function _process_cdn_status($result)
	{
		if (isset($result['nameservers'])) {
			if (isset($this->_summary['cdn_setup_err'])) {
				unset($this->_summary['cdn_setup_err']);
			}
			if (isset($result['summary'])) {
				$this->_summary['cdn_dns_summary'] = $result['summary'];
			}
			$this->cls('Cloud')->set_linked();
			$nameservers = esc_html($result['nameservers']);
			$this->cls('Conf')->update_confs(array(self::O_QC_NAMESERVERS => $nameservers, self::O_CDN_QUIC => true));
			Admin_Display::succeed(
				'🎊 ' . __('Congratulations, QUIC.cloud successfully set this domain up for the CDN. Please update your nameservers to:', 'litespeed-cache') . $nameservers
			);
		} elseif (isset($result['done'])) {
			if (isset($this->_summary['cdn_setup_err'])) {
				unset($this->_summary['cdn_setup_err']);
			}
			if (isset($this->_summary['cdn_verify_msg'])) {
				unset($this->_summary['cdn_verify_msg']);
			}
			$this->_summary['cdn_setup_done_ts'] = time();

			$this->_setup_token = '';
			$this->cls('Conf')->update_confs(array(self::O_QC_TOKEN => '', self::O_QC_NAMESERVERS => ''));
		} elseif (isset($result['_msg'])) {
			$notice = esc_html($result['_msg']);
			if ($this->conf(Base::O_QC_NAMESERVERS)) {
				$this->_summary['cdn_verify_msg'] = $notice;
				$notice = array('cdn_verify_msg' => $notice);
			}
			Admin_Display::succeed($notice);
		} else {
			Admin_Display::succeed(__('CDN Setup is running.', 'litespeed-cache'));
		}
		self::save_summary();
	}

	/**
	 * Process the returned Auto CDN Setup status
	 *
	 * @since  4.7
	 * @access private
	 */
	private function _qc_reset($delete)
	{
		$data = array(
			'site_url' => home_url(),
		);

		if ($delete) {
			$data['delete'] = 1;
		}

		if (!empty($this->_setup_token)) {
			$data['rest'] = function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : apply_filters('rest_url_prefix', 'wp-json');

			$json = $this->cls('Cloud')->req_rest_api('/user/cdn/reset', $data);

			if (!$json) {
				return;
			} elseif (is_string($json) && $json != 'unauthorized access to REST API.') {
				self::save_summary(array('cdn_setup_err' => $json));
				return;
			}
		} elseif (!isset($this->_summary['cdn_setup_done_ts']) || !$this->_summary['cdn_setup_done_ts']) {
			Admin_Display::info(__('Notice: CDN Setup only reset locally.', 'litespeed-cache'));
		} elseif (!Cloud::get_summary('is_linked')) {
			Admin_Display::error(__('Cannot delete, site is not linked.', 'litespeed-cache'));
			return;
		} else {
			$json = Cloud::post(Cloud::SVC_D_DEL_CDN_DNS, $data);

			if (!is_array($json)) {
				return;
			}
		}

		if (isset($this->_summary['cdn_setup_ts'])) {
			unset($this->_summary['cdn_setup_ts']);
		}
		if (isset($this->_summary['cdn_setup_done_ts'])) {
			unset($this->_summary['cdn_setup_done_ts']);
		}
		if (isset($this->_summary['cdn_setup_err'])) {
			unset($this->_summary['cdn_setup_err']);
		}
		if (isset($this->_summary['cdn_verify_msg'])) {
			unset($this->_summary['cdn_verify_msg']);
		}
		if (isset($this->_summary['cdn_dns_summary'])) {
			unset($this->_summary['cdn_dns_summary']);
		}
		self::save_summary($this->_summary, false, true);

		$this->_setup_token = '';
		$this->cls('Conf')->update_confs(array(self::O_QC_TOKEN => '', self::O_QC_NAMESERVERS => '', self::O_CDN_QUIC => false));
		$msg = '';
		if ($delete) {
			$msg = __(
				'CDN Setup Token and DNS zone deleted. Note: if my.quic.cloud account deletion is desired, that the account still exists and must be deleted separately.',
				'litespeed-cache'
			);
		} else {
			$msg = __(
				'CDN Setup Token reset. Note: if my.quic.cloud account deletion is desired, that the account still exists and must be deleted separately.',
				'litespeed-cache'
			);
		}
		Admin_Display::succeed($msg);
		return self::ok();
	}

	/**
	 * If setup token already exists or not
	 *
	 * @since  4.7
	 */
	public function has_cdn_setup_token()
	{
		return !empty($this->_setup_token);
	}

	/**
	 * Get QC user setup token
	 *
	 * This method initiates a link to a QUIC.cloud account.
	 *
	 * @since  4.7
	 */
	private function _qc_link()
	{
		if ($this->has_cdn_setup_token()) {
			return;
		}

		$data = array(
			'site_url' => home_url(),
			'ref' => get_admin_url(null, 'admin.php?page=litespeed-cdn'),
		);
		$api_key = $this->conf(self::O_API_KEY);
		if ($api_key) {
			$data['domain_hash'] = md5(substr($api_key, 0, 8));
		}

		wp_redirect(Cloud::CLOUD_SERVER_DASH . '/u/wptoken?data=' . Utility::arr2str($data));
		exit();
	}

	/**
	 * Get QC user setup token
	 *
	 * This method is used when the installation is already linked to an account.
	 *
	 * @since  4.7
	 */
	private function _qc_nolink()
	{
		if ($this->has_cdn_setup_token()) {
			return;
		}

		$data = array(
			'site_url' => home_url(),
		);

		$json = Cloud::post(Cloud::SVC_D_SETUP_TOKEN, $data);

		if (isset($json['token'])) {
			self::save_summary(array('cdn_setup_ts' => time()));
			$this->_setup_token = $json['token'];
			$this->cls('Conf')->update_confs(array(self::O_QC_TOKEN => $this->_setup_token));
		}
	}

	/**
	 * Initiate or continue a QC CDN Setup.
	 *
	 * @since  4.7
	 */
	private function _qc_run()
	{
		$data = array(
			'site_url' => home_url(),
			'rest' => function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : apply_filters('rest_url_prefix', 'wp-json'),
			'server_ip' => $this->conf(self::O_SERVER_IP),
		);

		$api_key = $this->conf(self::O_API_KEY);
		if ($api_key) {
			$data['domain_hash'] = md5(substr($api_key, 0, 8));
		}

		$__cloud = $this->cls('Cloud');
		$json = $__cloud->req_rest_api('/user/cdn/', $data);

		if (!$json) {
			return;
		} elseif (is_string($json)) {
			self::save_summary(array('cdn_setup_err' => $json));
			return;
		}

		$this->_summary['cdn_setup_ts'] = time();

		$msg = '';
		if (isset($json['info']['messages'])) {
			$msg = implode('<br>', $json['info']['messages']);
		}

		$json = $json['result'];

		if (isset($this->_summary['cdn_setup_err'])) {
			unset($this->_summary['cdn_setup_err']);
		}

		if (isset($this->_summary['cdn_verify_msg'])) {
			unset($this->_summary['cdn_verify_msg']);
		}
		self::save_summary();

		// Save token option
		if (!empty($json['token'])) {
			$__cloud->set_keygen_token($json['token']);
		}

		// This is a ok msg
		if (!empty($msg)) {
			self::debug('_msg: ' . $msg);

			$msg = __('Message from QUIC.cloud server', 'litespeed-cache') . ': ' . $msg;
			Admin_Display::info($msg);
			return;
		}

		self::debug('✅ Successfully start CDN setup.');
	}

	/**
	 * Return succeeded response
	 *
	 * @since  3.0
	 */
	public static function ok($data = array())
	{
		$data['_res'] = 'ok';
		return $data;
	}

	/**
	 * Return error
	 *
	 * @since  3.0
	 */
	public static function err($code)
	{
		return array('_res' => 'err', '_msg' => $code);
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler()
	{
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_LINK:
				$this->_qc_link();
				break;

			case self::TYPE_NOLINK:
				$this->_qc_nolink();
				break;

			case self::TYPE_RUN:
				$this->_qc_run();
				break;

			case self::TYPE_STATUS:
				$this->_qc_refresh();
				break;

			case self::TYPE_RESET:
				$this->_qc_reset(false);
				break;

			case self::TYPE_DELETE:
				$this->_qc_reset(true);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
