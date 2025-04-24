<?php

/**
 * Cloud service cls
 *
 * @since      3.0
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Cloud extends Base
{
	const LOG_TAG = '❄️';
	const CLOUD_SERVER = 'https://api.quic.cloud';
	const CLOUD_IPS = 'https://quic.cloud/ips';
	const CLOUD_SERVER_DASH = 'https://my.quic.cloud';
	const CLOUD_SERVER_WP = 'https://wpapi.quic.cloud';

	const SVC_D_ACTIVATE = 'd/activate';
	const SVC_U_ACTIVATE = 'u/wp3/activate';
	const SVC_D_ENABLE_CDN = 'd/enable_cdn';
	const SVC_D_LINK = 'd/link';
	const SVC_D_API = 'd/api';
	const SVC_D_DASH = 'd/dash';
	const SVC_D_V3UPGRADE = 'd/v3upgrade';
	const SVC_U_LINK = 'u/wp3/link';
	const SVC_U_ENABLE_CDN = 'u/wp3/enablecdn';
	const SVC_D_STATUS_CDN_CLI = 'd/status/cdn_cli';
	const SVC_D_NODES = 'd/nodes';
	const SVC_D_SYNC_CONF = 'd/sync_conf';
	const SVC_D_USAGE = 'd/usage';
	const SVC_D_SETUP_TOKEN = 'd/get_token';
	const SVC_D_DEL_CDN_DNS = 'd/del_cdn_dns';
	const SVC_PAGE_OPTM = 'page_optm';
	const SVC_CCSS = 'ccss';
	const SVC_UCSS = 'ucss';
	const SVC_VPI = 'vpi';
	const SVC_LQIP = 'lqip';
	const SVC_QUEUE = 'queue';
	const SVC_IMG_OPTM = 'img_optm';
	const SVC_HEALTH = 'health';
	const SVC_CDN = 'cdn';

	const IMG_OPTM_DEFAULT_GROUP = 200;

	const IMGOPTM_TAKEN = 'img_optm-taken';

	const TTL_NODE = 3; // Days before node expired
	const EXPIRATION_REQ = 300; // Seconds of min interval between two unfinished requests
	const TTL_IPS = 3; // Days for node ip list cache

	const API_REPORT = 'wp/report';
	const API_NEWS = 'news';
	const API_VER = 'ver_check';
	const API_BETA_TEST = 'beta_test';
	const API_REST_ECHO = 'tool/wp_rest_echo';
	const API_SERVER_KEY_SIGN = 'key_sign';

	private static $CENTER_SVC_SET = array(
		self::SVC_D_ACTIVATE,
		self::SVC_U_ACTIVATE,
		self::SVC_D_ENABLE_CDN,
		self::SVC_D_LINK,
		self::SVC_D_NODES,
		self::SVC_D_SYNC_CONF,
		self::SVC_D_USAGE,
		self::SVC_D_API,
		self::SVC_D_V3UPGRADE,
		self::SVC_D_DASH,
		self::SVC_D_STATUS_CDN_CLI,
		// self::API_NEWS,
		self::API_REPORT,
		// self::API_VER,
		// self::API_BETA_TEST,
		self::SVC_D_SETUP_TOKEN,
		self::SVC_D_DEL_CDN_DNS,
	);

	private static $WP_SVC_SET = array(self::API_NEWS, self::API_VER, self::API_BETA_TEST, self::API_REST_ECHO);

	// No api key needed for these services
	private static $_PUB_SVC_SET = array(self::API_NEWS, self::API_REPORT, self::API_VER, self::API_BETA_TEST, self::API_REST_ECHO, self::SVC_D_V3UPGRADE, self::SVC_D_DASH);

	private static $_QUEUE_SVC_SET = array(self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI);

	public static $SERVICES_LOAD_CHECK = array(
		// self::SVC_CCSS,
		// self::SVC_UCSS,
		// self::SVC_VPI,
		self::SVC_LQIP,
		self::SVC_HEALTH,
	);

	public static $SERVICES = array(
		self::SVC_IMG_OPTM,
		self::SVC_PAGE_OPTM,
		self::SVC_CCSS,
		self::SVC_UCSS,
		self::SVC_VPI,
		self::SVC_LQIP,
		self::SVC_CDN,
		self::SVC_HEALTH,
		// self::SVC_QUEUE,
	);

	const TYPE_CLEAR_PROMO = 'clear_promo';
	const TYPE_REDETECT_CLOUD = 'redetect_cloud';
	const TYPE_CLEAR_CLOUD = 'clear_cloud';
	const TYPE_ACTIVATE = 'activate';
	const TYPE_LINK = 'link';
	const TYPE_ENABLE_CDN = 'enablecdn';
	const TYPE_API = 'api';
	const TYPE_SYNC_USAGE = 'sync_usage';
	const TYPE_RESET = 'reset';
	const TYPE_SYNC_STATUS = 'sync_status';

	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		$this->_summary = self::get_summary();
	}

	/**
	 * Init QC setup preparation
	 *
	 * @since 7.0
	 */
	public function init_qc_prepare()
	{
		if (empty($this->_summary['sk_b64'])) {
			$keypair = sodium_crypto_sign_keypair();
			$pk = base64_encode(sodium_crypto_sign_publickey($keypair));
			$sk = base64_encode(sodium_crypto_sign_secretkey($keypair));
			$this->_summary['pk_b64'] = $pk;
			$this->_summary['sk_b64'] = $sk;
			$this->save_summary();
			// ATM `qc_activated` = null
			return true;
		}

		return false;
	}

	/**
	 * Init QC setup
	 *
	 * @since 7.0
	 */
	public function init_qc()
	{
		$this->init_qc_prepare();

		$ref = $this->_get_ref_url();

		// WPAPI REST echo dryrun
		$req_data = array(
			'wp_pk_b64' => $this->_summary['pk_b64'],
		);
		$echobox = self::post(self::API_REST_ECHO, $req_data);
		if ($echobox === false) {
			self::debugErr('REST Echo Failed!');
			$msg = __('Your WP REST API seems blocked our QUIC.cloud server calls.', 'litespeed-cache');
			Admin_Display::error($msg);
			wp_redirect($ref);
			return;
		}

		self::debug('echo succeeded');

		// Load separate thread echoed data from storage
		if (empty($echobox['wpapi_ts']) || empty($echobox['wpapi_signature_b64'])) {
			Admin_Display::error(__('Failed to get echo data from WPAPI', 'litespeed-cache'));
			wp_redirect($ref);
			return;
		}

		$data = array(
			'wp_pk_b64' => $this->_summary['pk_b64'],
			'wpapi_ts' => $echobox['wpapi_ts'],
			'wpapi_signature_b64' => $echobox['wpapi_signature_b64'],
		);
		$server_ip = $this->conf(self::O_SERVER_IP);
		if ($server_ip) {
			$data['server_ip'] = $server_ip;
		}

		// Activation redirect
		$param = array(
			'site_url' => home_url(),
			'ver' => Core::VER,
			'data' => $data,
			'ref' => $ref,
		);
		wp_redirect(self::CLOUD_SERVER_DASH . '/' . self::SVC_U_ACTIVATE . '?data=' . urlencode(Utility::arr2str($param)));
		exit();
	}

	/**
	 * Decide the ref
	 */
	private function _get_ref_url($ref = false)
	{
		$link = 'admin.php?page=litespeed';
		if ($ref == 'cdn') {
			$link = 'admin.php?page=litespeed-cdn';
		}
		if ($ref == 'online') {
			$link = 'admin.php?page=litespeed-general';
		}
		if (!empty($_GET['ref']) && $_GET['ref'] == 'cdn') {
			$link = 'admin.php?page=litespeed-cdn';
		}
		if (!empty($_GET['ref']) && $_GET['ref'] == 'online') {
			$link = 'admin.php?page=litespeed-general';
		}
		return get_admin_url(null, $link);
	}

	/**
	 * Init QC setup (CLI)
	 *
	 * @since 7.0
	 */
	public function init_qc_cli()
	{
		$this->init_qc_prepare();

		$server_ip = $this->conf(self::O_SERVER_IP);
		if (!$server_ip) {
			self::debugErr('Server IP needs to be set first!');
			$msg = sprintf(
				__('You need to set the %1$s first. Please use the command %2$s to set.', 'litespeed-cache'),
				'`' . __('Server IP', 'litespeed-cache') . '`',
				'`wp litespeed-option set server_ip __your_ip_value__`'
			);
			Admin_Display::error($msg);
			return;
		}

		// WPAPI REST echo dryrun
		$req_data = array(
			'wp_pk_b64' => $this->_summary['pk_b64'],
		);
		$echobox = self::post(self::API_REST_ECHO, $req_data);
		if ($echobox === false) {
			self::debugErr('REST Echo Failed!');
			$msg = __('Your WP REST API seems blocked our QUIC.cloud server calls.', 'litespeed-cache');
			Admin_Display::error($msg);
			return;
		}

		self::debug('echo succeeded');

		// Load separate thread echoed data from storage
		if (empty($echobox['wpapi_ts']) || empty($echobox['wpapi_signature_b64'])) {
			self::debug('Resp: ', $echobox);
			Admin_Display::error(__('Failed to get echo data from WPAPI', 'litespeed-cache'));
			return;
		}

		$data = array(
			'wp_pk_b64' => $this->_summary['pk_b64'],
			'wpapi_ts' => $echobox['wpapi_ts'],
			'wpapi_signature_b64' => $echobox['wpapi_signature_b64'],
			'server_ip' => $server_ip,
		);

		$res = $this->post(self::SVC_D_ACTIVATE, $data);
		return $res;
	}

	/**
	 * Init QC CDN setup (CLI)
	 *
	 * @since 7.0
	 */
	public function init_qc_cdn_cli($method, $cert = false, $key = false, $cf_token = false)
	{
		if (!$this->activated()) {
			Admin_Display::error(__('You need to activate QC first.', 'litespeed-cache'));
			return;
		}

		$server_ip = $this->conf(self::O_SERVER_IP);
		if (!$server_ip) {
			self::debugErr('Server IP needs to be set first!');
			$msg = sprintf(
				__('You need to set the %1$s first. Please use the command %2$s to set.', 'litespeed-cache'),
				'`' . __('Server IP', 'litespeed-cache') . '`',
				'`wp litespeed-option set server_ip __your_ip_value__`'
			);
			Admin_Display::error($msg);
			return;
		}

		if ($cert) {
			if (!file_exists($cert) || !file_exists($key)) {
				Admin_Display::error(__('Cert or key file does not exist.', 'litespeed-cache'));
				return;
			}
		}

		$data = array(
			'method' => $method,
			'server_ip' => $server_ip,
		);
		if ($cert) {
			$data['cert'] = File::read($cert);
			$data['key'] = File::read($key);
		}
		if ($cf_token) {
			$data['cf_token'] = $cf_token;
		}

		$res = $this->post(self::SVC_D_ENABLE_CDN, $data);
		return $res;
	}

	/**
	 * Link to QC setup
	 *
	 * @since 7.0
	 */
	public function link_qc()
	{
		if (!$this->activated()) {
			Admin_Display::error(__('You need to activate QC first.', 'litespeed-cache'));
			return;
		}

		$data = array(
			'wp_ts' => time(),
		);
		$data['wp_signature_b64'] = $this->_sign_b64($data['wp_ts']);

		// Activation redirect
		$param = array(
			'site_url' => home_url(),
			'ver' => Core::VER,
			'data' => $data,
			'ref' => $this->_get_ref_url(),
		);
		wp_redirect(self::CLOUD_SERVER_DASH . '/' . self::SVC_U_LINK . '?data=' . urlencode(Utility::arr2str($param)));
		exit();
	}

	/**
	 * Show QC Account CDN status
	 *
	 * @since 7.0
	 */
	public function cdn_status_cli()
	{
		if (!$this->activated()) {
			Admin_Display::error(__('You need to activate QC first.', 'litespeed-cache'));
			return;
		}

		$data = array();
		$res = $this->post(self::SVC_D_STATUS_CDN_CLI, $data);
		return $res;
	}

	/**
	 * Link to QC Account for CLI
	 *
	 * @since 7.0
	 */
	public function link_qc_cli($email, $key)
	{
		if (!$this->activated()) {
			Admin_Display::error(__('You need to activate QC first.', 'litespeed-cache'));
			return;
		}

		$data = array(
			'qc_acct_email' => $email,
			'qc_acct_apikey' => $key,
		);
		$res = $this->post(self::SVC_D_LINK, $data);
		return $res;
	}

	/**
	 * API link parsed call to QC
	 *
	 * @since 7.0
	 */
	public function api_link_call($action2)
	{
		if (!$this->activated()) {
			Admin_Display::error(__('You need to activate QC first.', 'litespeed-cache'));
			return;
		}

		$data = array(
			'action2' => $action2,
		);
		$res = $this->post(self::SVC_D_API, $data);
		self::debug('API link call result: ', $res);
	}

	/**
	 * Enable QC CDN
	 *
	 * @since 7.0
	 */
	public function enable_cdn()
	{
		if (!$this->activated()) {
			Admin_Display::error(__('You need to activate QC first.', 'litespeed-cache'));
			return;
		}

		$data = array(
			'wp_ts' => time(),
		);
		$data['wp_signature_b64'] = $this->_sign_b64($data['wp_ts']);

		// Activation redirect
		$param = array(
			'site_url' => home_url(),
			'ver' => Core::VER,
			'data' => $data,
			'ref' => $this->_get_ref_url(),
		);
		wp_redirect(self::CLOUD_SERVER_DASH . '/' . self::SVC_U_ENABLE_CDN . '?data=' . urlencode(Utility::arr2str($param)));
		exit();
	}

	/**
	 * Encrypt data for cloud req
	 *
	 * @since 7.0
	 */
	private function _sign_b64($data)
	{
		if (empty($this->_summary['sk_b64'])) {
			self::debugErr('No sk to sign.');
			return false;
		}
		$sk = base64_decode($this->_summary['sk_b64']);
		if (strlen($sk) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
			self::debugErr('Invalid local sign sk length.');
			// Reset local pk/sk
			unset($this->_summary['pk_b64']);
			unset($this->_summary['sk_b64']);
			$this->save_summary();
			self::debug('Clear local sign pk/sk pair.');

			return false;
		}
		$signature = sodium_crypto_sign_detached((string) $data, $sk);
		return base64_encode($signature);
	}

	/**
	 * Load server pk from cloud
	 *
	 * @since 7.0
	 */
	private function _load_server_pk($from_wpapi = false)
	{
		// Load cloud pk
		$server_key_url = self::CLOUD_SERVER . '/' . self::API_SERVER_KEY_SIGN;
		if ($from_wpapi) {
			$server_key_url = self::CLOUD_SERVER_WP . '/' . self::API_SERVER_KEY_SIGN;
		}
		$resp = wp_safe_remote_get($server_key_url);
		if (is_wp_error($resp)) {
			self::debugErr('Failed to load key: ' . $resp->get_error_message());
			return false;
		}
		$pk = trim($resp['body']);
		self::debug('Loaded key from ' . $server_key_url . ': ' . $pk);
		$cloud_pk = base64_decode($pk);
		if (strlen($cloud_pk) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
			self::debugErr('Invalid cloud public key length.');
			return false;
		}

		$sk = base64_decode($this->_summary['sk_b64']);
		if (strlen($sk) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
			self::debugErr('Invalid local secret key length.');
			// Reset local pk/sk
			unset($this->_summary['pk_b64']);
			unset($this->_summary['sk_b64']);
			$this->save_summary();
			self::debug('Unset local pk/sk pair.');

			return false;
		}

		return $cloud_pk;
	}

	/**
	 * WPAPI echo back to notify the sealed databox
	 *
	 * @since 7.0
	 */
	public function wp_rest_echo()
	{
		self::debug('Parsing echo', $_POST);

		if (empty($_POST['wpapi_ts']) || empty($_POST['wpapi_signature_b64'])) {
			return self::err('No echo data');
		}

		$is_valid = $this->_validate_signature($_POST['wpapi_signature_b64'], $_POST['wpapi_ts'], true);
		if (!$is_valid) {
			return self::err('Data validation from WPAPI REST Echo failed');
		}

		$diff = time() - $_POST['wpapi_ts'];
		if (abs($diff) > 86400) {
			self::debugErr('WPAPI echo data timeout [diff] ' . $diff);
			return self::err('Echo data expired');
		}

		$signature_b64 = $this->_sign_b64($_POST['wpapi_ts']);
		self::debug('Response to echo [signature_b64] ' . $signature_b64);
		return self::ok(array('signature_b64' => $signature_b64));
	}

	/**
	 * Validate cloud data
	 *
	 * @since 7.0
	 */
	private function _validate_signature($signature_b64, $data, $from_wpapi = false)
	{
		// Try validation
		try {
			$cloud_pk = $this->_load_server_pk($from_wpapi);
			if (!$cloud_pk) {
				return false;
			}
			$signature = base64_decode($signature_b64);
			$is_valid = sodium_crypto_sign_verify_detached($signature, $data, $cloud_pk);
		} catch (\SodiumException $e) {
			self::debugErr('Decryption failed: ' . $e->getMessage());
			return false;
		}
		self::debug('Signature validation result: ' . ($is_valid ? 'true' : 'false'));
		return $is_valid;
	}

	/**
	 * Finish qc activation after redirection back from QC
	 *
	 * @since 7.0
	 */
	public function finish_qc_activation($ref = false)
	{
		if (empty($_GET['qc_activated']) || empty($_GET['qc_ts']) || empty($_GET['qc_signature_b64'])) {
			return;
		}

		$data_to_validate_signature = array(
			'wp_pk_b64' => $this->_summary['pk_b64'],
			'qc_ts' => $_GET['qc_ts'],
		);
		$is_valid = $this->_validate_signature($_GET['qc_signature_b64'], implode('', $data_to_validate_signature));
		if (!$is_valid) {
			self::debugErr('Failed to validate qc activation data');
			Admin_Display::error(sprintf(__('Failed to validate %s activation data.', 'litespeed-cache'), 'QUIC.cloud'));
			return;
		}

		self::debug('QC activation status: ' . $_GET['qc_activated']);
		if (!in_array($_GET['qc_activated'], array('anonymous', 'linked', 'cdn'))) {
			self::debugErr('Failed to parse qc activation status');
			Admin_Display::error(sprintf(__('Failed to parse %s activation status.', 'litespeed-cache'), 'QUIC.cloud'));
			return;
		}

		$diff = time() - $_GET['qc_ts'];
		if (abs($diff) > 86400) {
			self::debugErr('QC activation data timeout [diff] ' . $diff);
			Admin_Display::error(sprintf(__('%s activation data expired.', 'litespeed-cache'), 'QUIC.cloud'));
			return;
		}

		$main_domain = !empty($_GET['main_domain']) ? $_GET['main_domain'] : false;
		$this->update_qc_activation($_GET['qc_activated'], $main_domain);

		wp_redirect($this->_get_ref_url($ref));
	}

	/**
	 * Finish qc activation process
	 *
	 * @since 7.0
	 */
	public function update_qc_activation($qc_activated, $main_domain = false, $quite = false)
	{
		$this->_summary['qc_activated'] = $qc_activated;
		if ($main_domain) {
			$this->_summary['main_domain'] = $main_domain;
		}
		$this->save_summary();

		$msg = sprintf(__('Congratulations, %s successfully set this domain up for the anonymous online services.', 'litespeed-cache'), 'QUIC.cloud');
		if ($qc_activated == 'linked') {
			$msg = sprintf(__('Congratulations, %s successfully set this domain up for the online services.', 'litespeed-cache'), 'QUIC.cloud');
			// Sync possible partner info
			$this->sync_usage();
		}
		if ($qc_activated == 'cdn') {
			$msg = sprintf(__('Congratulations, %s successfully set this domain up for the online services with CDN service.', 'litespeed-cache'), 'QUIC.cloud');
			// Turn on CDN option
			$this->cls('Conf')->update_confs(array(self::O_CDN_QUIC => true));
		}
		if (!$quite) {
			Admin_Display::success('🎊 ' . $msg);
		}

		$this->_clear_reset_qc_reg_msg();

		$this->clear_cloud();
	}

	/**
	 * Load QC status for dash usage
	 * Format to translate: `<a href="{#xxx#}" class="button button-primary">xxxx</a><a href="{#xxx#}">xxxx2</a>`
	 *
	 * @since 7.0
	 */
	public function load_qc_status_for_dash($type, $force = false)
	{
		return Str::translate_qc_apis($this->_load_qc_status_for_dash($type, $force));
	}
	private function _load_qc_status_for_dash($type, $force = false)
	{
		if (
			!$force &&
			!empty($this->_summary['mini_html']) &&
			isset($this->_summary['mini_html'][$type]) &&
			!empty($this->_summary['mini_html']['ttl.' . $type]) &&
			$this->_summary['mini_html']['ttl.' . $type] > time()
		) {
			return Str::safe_html($this->_summary['mini_html'][$type]);
		}

		// Try to update dash content
		$data = self::post(self::SVC_D_DASH, array('action2' => $type == 'cdn_dash_mini' ? 'cdn_dash' : $type));
		if (!empty($data['qc_activated'])) {
			// Sync conf as changed
			if (empty($this->_summary['qc_activated']) || $this->_summary['qc_activated'] != $data['qc_activated']) {
				$msg = sprintf(__('Congratulations, %s successfully set this domain up for the online services with CDN service.', 'litespeed-cache'), 'QUIC.cloud');
				Admin_Display::success('🎊 ' . $msg);
				$this->_clear_reset_qc_reg_msg();
				// Turn on CDN option
				$this->cls('Conf')->update_confs(array(self::O_CDN_QUIC => true));
				$this->cls('CDN\Quic')->try_sync_conf(true);
			}

			$this->_summary['qc_activated'] = $data['qc_activated'];
			$this->save_summary();
		}

		// Show the info
		if (isset($this->_summary['mini_html'][$type])) {
			return Str::safe_html($this->_summary['mini_html'][$type]);
		}

		return '';
	}

	/**
	 * Update QC status
	 *
	 * @since 7.0
	 */
	public function update_cdn_status()
	{
		if (empty($_POST['qc_activated']) || !in_array($_POST['qc_activated'], array('anonymous', 'linked', 'cdn', 'deleted'))) {
			return self::err('lack_of_params');
		}

		self::debug('update_cdn_status request hash: ' . $_POST['qc_activated']);

		if ($_POST['qc_activated'] == 'deleted') {
			$this->_reset_qc_reg();
		} else {
			$this->_summary['qc_activated'] = $_POST['qc_activated'];
			$this->save_summary();
		}

		if ($_POST['qc_activated'] == 'cdn') {
			$msg = sprintf(__('Congratulations, %s successfully set this domain up for the online services with CDN service.', 'litespeed-cache'), 'QUIC.cloud');
			Admin_Display::success('🎊 ' . $msg);
			$this->_clear_reset_qc_reg_msg();
			// Turn on CDN option
			$this->cls('Conf')->update_confs(array(self::O_CDN_QUIC => true));
			$this->cls('CDN\Quic')->try_sync_conf(true);
		}

		return self::ok(array('qc_activated' => $_POST['qc_activated']));
	}

	/**
	 * Reset QC setup
	 *
	 * @since 7.0
	 */
	public function reset_qc()
	{
		unset($this->_summary['pk_b64']);
		unset($this->_summary['sk_b64']);
		unset($this->_summary['qc_activated']);
		if (!empty($this->_summary['partner'])) {
			unset($this->_summary['partner']);
		}
		$this->save_summary();
		self::debug('Clear local QC activation.');

		$this->clear_cloud();

		Admin_Display::success(sprintf(__('Reset %s activation successfully.', 'litespeed-cache'), 'QUIC.cloud'));
		wp_redirect($this->_get_ref_url());
		exit();
	}

	/**
	 * Show latest commit version always if is on dev
	 *
	 * @since 3.0
	 */
	public function check_dev_version()
	{
		if (!preg_match('/[^\d\.]/', Core::VER)) {
			return;
		}

		$last_check = empty($this->_summary['last_request.' . self::API_VER]) ? 0 : $this->_summary['last_request.' . self::API_VER];

		if (time() - $last_check > 86400) {
			$auto_v = self::version_check('dev');
			if (!empty($auto_v['dev'])) {
				self::save_summary(array('version.dev' => $auto_v['dev']));
			}
		}

		if (empty($this->_summary['version.dev'])) {
			return;
		}

		self::debug('Latest dev version ' . $this->_summary['version.dev']);

		if (version_compare($this->_summary['version.dev'], Core::VER, '<=')) {
			return;
		}

		// Show the dev banner
		require_once LSCWP_DIR . 'tpl/banner/new_version_dev.tpl.php';
	}

	/**
	 * Check latest version
	 *
	 * @since  2.9
	 * @access public
	 */
	public static function version_check($src = false)
	{
		$req_data = array(
			'v' => defined('LSCWP_CUR_V') ? LSCWP_CUR_V : '',
			'src' => $src,
			'php' => phpversion(),
		);
		if (defined('LITESPEED_ERR')) {
			$req_data['err'] = base64_encode(!is_string(LITESPEED_ERR) ? \json_encode(LITESPEED_ERR) : LITESPEED_ERR);
		}
		$data = self::post(self::API_VER, $req_data);

		return $data;
	}

	/**
	 * Show latest news
	 *
	 * @since 3.0
	 */
	public function news()
	{
		$this->_update_news();

		if (empty($this->_summary['news.new'])) {
			return;
		}

		if (!empty($this->_summary['news.plugin']) && Activation::cls()->dash_notifier_is_plugin_active($this->_summary['news.plugin'])) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_news.tpl.php';
	}

	/**
	 * Update latest news
	 *
	 * @since 2.9.9.1
	 */
	private function _update_news()
	{
		if (!empty($this->_summary['news.utime']) && time() - $this->_summary['news.utime'] < 86400 * 7) {
			return;
		}

		self::save_summary(array('news.utime' => time()));

		$data = self::get(self::API_NEWS);
		if (empty($data['id'])) {
			return;
		}

		// Save news
		if (!empty($this->_summary['news.id']) && $this->_summary['news.id'] == $data['id']) {
			return;
		}

		$this->_summary['news.id'] = $data['id'];
		$this->_summary['news.plugin'] = !empty($data['plugin']) ? $data['plugin'] : '';
		$this->_summary['news.title'] = !empty($data['title']) ? $data['title'] : '';
		$this->_summary['news.content'] = !empty($data['content']) ? $data['content'] : '';
		$this->_summary['news.zip'] = !empty($data['zip']) ? $data['zip'] : '';
		$this->_summary['news.new'] = 1;

		if ($this->_summary['news.plugin']) {
			$plugin_info = Activation::cls()->dash_notifier_get_plugin_info($this->_summary['news.plugin']);
			if ($plugin_info && !empty($plugin_info->name)) {
				$this->_summary['news.plugin_name'] = $plugin_info->name;
			}
		}

		self::save_summary();
	}

	/**
	 * Check if contains a package in a service or not
	 *
	 * @since  4.0
	 */
	public function has_pkg($service, $pkg)
	{
		if (!empty($this->_summary['usage.' . $service]['pkgs']) && $this->_summary['usage.' . $service]['pkgs'] & $pkg) {
			return true;
		}

		return false;
	}

	/**
	 * Get allowance of current service
	 *
	 * @since  3.0
	 * @access private
	 */
	public function allowance($service, &$err = false)
	{
		// Only auto sync usage at most one time per day
		if (empty($this->_summary['last_request.' . self::SVC_D_USAGE]) || time() - $this->_summary['last_request.' . self::SVC_D_USAGE] > 86400) {
			$this->sync_usage();
		}

		if (in_array($service, array(self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI))) {
			// @since 4.2
			$service = self::SVC_PAGE_OPTM;
		}

		if (empty($this->_summary['usage.' . $service])) {
			return 0;
		}
		$usage = $this->_summary['usage.' . $service];

		// Image optm is always free
		$allowance_max = 0;
		if ($service == self::SVC_IMG_OPTM) {
			$allowance_max = self::IMG_OPTM_DEFAULT_GROUP;
		}

		$allowance = $usage['quota'] - $usage['used'];

		$err = 'out_of_quota';

		if ($allowance > 0) {
			if ($allowance_max && $allowance_max < $allowance) {
				$allowance = $allowance_max;
			}

			// Daily limit @since 4.2
			if (isset($usage['remaining_daily_quota']) && $usage['remaining_daily_quota'] >= 0 && $usage['remaining_daily_quota'] < $allowance) {
				$allowance = $usage['remaining_daily_quota'];
				if (!$allowance) {
					$err = 'out_of_daily_quota';
				}
			}

			return $allowance;
		}

		// Check Pay As You Go balance
		if (empty($usage['pag_bal'])) {
			return $allowance_max;
		}

		if ($allowance_max && $allowance_max < $usage['pag_bal']) {
			return $allowance_max;
		}

		return $usage['pag_bal'];
	}

	/**
	 * Sync Cloud usage summary data
	 *
	 * @since  3.0
	 * @access public
	 */
	public function sync_usage()
	{
		$usage = $this->_post(self::SVC_D_USAGE);
		if (!$usage) {
			return;
		}

		self::debug('sync_usage ' . \json_encode($usage));

		foreach (self::$SERVICES as $v) {
			$this->_summary['usage.' . $v] = !empty($usage[$v]) ? $usage[$v] : false;
		}

		self::save_summary();

		return $this->_summary;
	}

	/**
	 * Clear all existing cloud nodes for future reconnect
	 *
	 * @since  3.0
	 * @access public
	 */
	public function clear_cloud()
	{
		foreach (self::$SERVICES as $service) {
			if (isset($this->_summary['server.' . $service])) {
				unset($this->_summary['server.' . $service]);
			}
			if (isset($this->_summary['server_date.' . $service])) {
				unset($this->_summary['server_date.' . $service]);
			}
		}
		self::save_summary();

		self::debug('Cleared all local service node caches');
	}

	/**
	 * ping clouds to find the fastest node
	 *
	 * @since  3.0
	 * @access public
	 */
	public function detect_cloud($service, $force = false)
	{
		if (in_array($service, self::$CENTER_SVC_SET)) {
			return self::CLOUD_SERVER;
		}

		if (in_array($service, self::$WP_SVC_SET)) {
			return self::CLOUD_SERVER_WP;
		}

		// Check if the stored server needs to be refreshed
		if (!$force) {
			if (
				!empty($this->_summary['server.' . $service]) &&
				!empty($this->_summary['server_date.' . $service]) &&
				$this->_summary['server_date.' . $service] > time() - 86400 * self::TTL_NODE
			) {
				$server = $this->_summary['server.' . $service];
				if (!strpos(self::CLOUD_SERVER, 'preview.') && !strpos($server, 'preview.')) {
					return $server;
				}
				if (strpos(self::CLOUD_SERVER, 'preview.') && strpos($server, 'preview.')) {
					return $server;
				}
			}
		}

		if (!$service || !in_array($service, self::$SERVICES)) {
			$msg = __('Cloud Error', 'litespeed-cache') . ': ' . $service;
			Admin_Display::error($msg);
			return false;
		}

		// Send request to Quic Online Service
		$json = $this->_post(self::SVC_D_NODES, array('svc' => $this->_maybe_queue($service)));

		// Check if get list correctly
		if (empty($json['list']) || !is_array($json['list'])) {
			self::debug('request cloud list failed: ', $json);

			if ($json) {
				$msg = __('Cloud Error', 'litespeed-cache') . ": [Service] $service [Info] " . \json_encode($json);
				Admin_Display::error($msg);
			}

			return false;
		}

		// Ping closest cloud
		$valid_clouds = false;
		if (!empty($json['list_preferred'])) {
			$valid_clouds = $this->_get_closest_nodes($json['list_preferred'], $service);
		}
		if (!$valid_clouds) {
			$valid_clouds = $this->_get_closest_nodes($json['list'], $service);
		}
		if (!$valid_clouds) {
			return false;
		}

		// Check server load
		if (in_array($service, self::$SERVICES_LOAD_CHECK)) {
			// TODO
			$valid_cloud_loads = array();
			foreach ($valid_clouds as $k => $v) {
				$response = wp_safe_remote_get($v, array('timeout' => 5));
				if (is_wp_error($response)) {
					$error_message = $response->get_error_message();
					self::debug('failed to do load checker: ' . $error_message);
					continue;
				}

				$curr_load = \json_decode($response['body'], true);
				if (!empty($curr_load['_res']) && $curr_load['_res'] == 'ok' && isset($curr_load['load'])) {
					$valid_cloud_loads[$v] = $curr_load['load'];
				}
			}

			if (!$valid_cloud_loads) {
				$msg = __('Cloud Error', 'litespeed-cache') . ": [Service] $service [Info] " . __('No available Cloud Node after checked server load.', 'litespeed-cache');
				Admin_Display::error($msg);
				return false;
			}

			self::debug('Closest nodes list after load check', $valid_cloud_loads);

			$qualified_list = array_keys($valid_cloud_loads, min($valid_cloud_loads));
		} else {
			$qualified_list = $valid_clouds;
		}

		$closest = $qualified_list[array_rand($qualified_list)];

		self::debug('Chose node: ' . $closest);

		// store data into option locally
		$this->_summary['server.' . $service] = $closest;
		$this->_summary['server_date.' . $service] = time();
		self::save_summary();

		return $this->_summary['server.' . $service];
	}

	/**
	 * Ping to choose the closest nodes
	 * @since 7.0
	 */
	private function _get_closest_nodes($list, $service)
	{
		$speed_list = array();
		foreach ($list as $v) {
			// Exclude possible failed 503 nodes
			if (!empty($this->_summary['disabled_node']) && !empty($this->_summary['disabled_node'][$v]) && time() - $this->_summary['disabled_node'][$v] < 86400) {
				continue;
			}
			$speed_list[$v] = Utility::ping($v);
		}

		if (!$speed_list) {
			self::debug('nodes are in 503 failed nodes');
			return false;
		}

		$min = min($speed_list);

		if ($min == 99999) {
			self::debug('failed to ping all clouds');
			return false;
		}

		// Random pick same time range ip (230ms 250ms)
		$range_len = strlen($min);
		$range_num = substr($min, 0, 1);
		$valid_clouds = array();
		foreach ($speed_list as $node => $speed) {
			if (strlen($speed) == $range_len && substr($speed, 0, 1) == $range_num) {
				$valid_clouds[] = $node;
			}
			// Append the lower speed ones
			elseif ($speed < $min * 4) {
				$valid_clouds[] = $node;
			}
		}

		if (!$valid_clouds) {
			$msg = __('Cloud Error', 'litespeed-cache') . ": [Service] $service [Info] " . __('No available Cloud Node.', 'litespeed-cache');
			Admin_Display::error($msg);
			return false;
		}

		self::debug('Closest nodes list', $valid_clouds);
		return $valid_clouds;
	}

	/**
	 * May need to convert to queue service
	 */
	private function _maybe_queue($service)
	{
		if (in_array($service, self::$_QUEUE_SVC_SET)) {
			return self::SVC_QUEUE;
		}
		return $service;
	}

	/**
	 * Get data from QUIC cloud server
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function get($service, $data = array())
	{
		$instance = self::cls();
		return $instance->_get($service, $data);
	}

	/**
	 * Get data from QUIC cloud server
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _get($service, $data = false)
	{
		$service_tag = $service;
		if (!empty($data['action'])) {
			$service_tag .= '-' . $data['action'];
		}

		$maybe_cloud = $this->_maybe_cloud($service_tag);
		if (!$maybe_cloud || $maybe_cloud === 'svc_hot') {
			return $maybe_cloud;
		}

		$server = $this->detect_cloud($service);
		if (!$server) {
			return;
		}

		$url = $server . '/' . $service;

		$param = array(
			'site_url' => home_url(),
			'main_domain' => !empty($this->_summary['main_domain']) ? $this->_summary['main_domain'] : '',
			'ver' => Core::VER,
		);

		if ($data) {
			$param['data'] = $data;
		}

		$url .= '?' . http_build_query($param);

		self::debug('getting from : ' . $url);

		self::save_summary(array('curr_request.' . $service_tag => time()));

		$response = wp_safe_remote_get($url, array(
			'timeout' => 15,
			'headers' => array('Accept' => 'application/json'),
		));

		return $this->_parse_response($response, $service, $service_tag, $server);
	}

	/**
	 * Check if is able to do cloud request or not
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _maybe_cloud($service_tag)
	{
		$home_url = home_url();
		if (!wp_http_validate_url($home_url)) {
			self::debug('wp_http_validate_url failed: ' . $home_url);
			return false;
		}

		// Deny if is IP
		if (preg_match('#^(([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)\.){3}([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)$#', Utility::parse_url_safe($home_url, PHP_URL_HOST))) {
			self::debug('IP home url is not allowed for cloud service.');
			$msg = __('In order to use QC services, need a real domain name, cannot use an IP.', 'litespeed-cache');
			Admin_Display::error($msg);
			return false;
		}

		/** @since 5.0 If in valid err_domains, bypass request */
		if ($this->_is_err_domain($home_url)) {
			self::debug('home url is in err_domains, bypass request: ' . $home_url);
			return false;
		}

		// we don't want the `img_optm-taken` to fail at any given time
		if ($service_tag == self::IMGOPTM_TAKEN) {
			return true;
		}

		if ($service_tag == self::SVC_D_SYNC_CONF && !$this->activated()) {
			self::debug('Skip sync conf as QC not activated yet.');
			return false;
		}

		// Check TTL
		if (!empty($this->_summary['ttl.' . $service_tag])) {
			$ttl = $this->_summary['ttl.' . $service_tag] - time();
			if ($ttl > 0) {
				self::debug('❌ TTL limit. [srv] ' . $service_tag . ' [TTL cool down] ' . $ttl . ' seconds');
				return 'svc_hot';
			}
		}

		$expiration_req = self::EXPIRATION_REQ;
		// Limit frequent unfinished request to 5min
		$timestamp_tag = 'curr_request.';
		if ($service_tag == self::SVC_IMG_OPTM . '-' . Img_Optm::TYPE_NEW_REQ) {
			$timestamp_tag = 'last_request.';
		} else {
			// For all other requests, if is under debug mode, will always allow
			if ($this->conf(self::O_DEBUG)) {
				return true;
			}
		}

		if (!empty($this->_summary[$timestamp_tag . $service_tag])) {
			$expired = $this->_summary[$timestamp_tag . $service_tag] + $expiration_req - time();
			if ($expired > 0) {
				self::debug("❌ try [$service_tag] after $expired seconds");

				if ($service_tag !== self::API_VER) {
					$msg =
						__('Cloud Error', 'litespeed-cache') .
						': ' .
						sprintf(__('Please try after %1$s for service %2$s.', 'litespeed-cache'), Utility::readable_time($expired, 0, true), '<code>' . $service_tag . '</code>');
					Admin_Display::error(array('cloud_trylater' => $msg));
				}

				return false;
			}
		}

		if (in_array($service_tag, self::$_PUB_SVC_SET)) {
			return true;
		}

		if (!$this->activated() && $service_tag != self::SVC_D_ACTIVATE) {
			Admin_Display::error(Error::msg('qc_setup_required'));
			return false;
		}

		return true;
	}

	/**
	 * Check if a service tag ttl is valid or not
	 * @since 7.1
	 */
	public function service_hot($service_tag)
	{
		if (empty($this->_summary['ttl.' . $service_tag])) {
			return false;
		}

		$ttl = $this->_summary['ttl.' . $service_tag] - time();
		if ($ttl <= 0) {
			return false;
		}

		return $ttl;
	}

	/**
	 * Check if activated QUIC.cloud service or not
	 *
	 * @since  7.0
	 * @access public
	 */
	public function activated()
	{
		return !empty($this->_summary['sk_b64']) && !empty($this->_summary['qc_activated']);
	}

	/**
	 * Show my.qc quick link to the domain page
	 */
	public function qc_link()
	{
		$data = array(
			'site_url' => home_url(),
			'ver' => LSCWP_V,
			'ref' => $this->_get_ref_url(),
		);
		return self::CLOUD_SERVER_DASH . '/u/wp3/manage?data=' . urlencode(Utility::arr2str($data)); // . (!empty($this->_summary['is_linked']) ? '?wplogin=1' : '');
	}

	/**
	 * Post data to QUIC.cloud server
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function post($service, $data = false, $time_out = false)
	{
		$instance = self::cls();
		return $instance->_post($service, $data, $time_out);
	}

	/**
	 * Post data to cloud server
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _post($service, $data = false, $time_out = false)
	{
		$service_tag = $service;
		if (!empty($data['action'])) {
			$service_tag .= '-' . $data['action'];
		}

		$maybe_cloud = $this->_maybe_cloud($service_tag);
		if (!$maybe_cloud || $maybe_cloud === 'svc_hot') {
			self::debug('Maybe cloud failed: ' . var_export($maybe_cloud, true));
			return $maybe_cloud;
		}

		$server = $this->detect_cloud($service);
		if (!$server) {
			return;
		}

		$url = $server . '/' . $this->_maybe_queue($service);

		self::debug('posting to : ' . $url);

		if ($data) {
			$data['service_type'] = $service; // For queue distribution usage
		}

		// Encrypt service as signature
		// $signature_ts = time();
		// $sign_data = array(
		// 	'service_tag' => $service_tag,
		// 	'ts' => $signature_ts,
		// );
		// $data['signature_b64'] = $this->_sign_b64(implode('', $sign_data));
		// $data['signature_ts'] = $signature_ts;

		self::debug('data', $data);
		$param = array(
			'site_url' => home_url(), // Need to use home_url() as WPML case may change it for diff langs, therefore we can do auto alias
			'main_domain' => !empty($this->_summary['main_domain']) ? $this->_summary['main_domain'] : '',
			'wp_pk_b64' => !empty($this->_summary['pk_b64']) ? $this->_summary['pk_b64'] : '',
			'ver' => Core::VER,
			'data' => $data,
		);

		self::save_summary(array('curr_request.' . $service_tag => time()));

		$response = wp_safe_remote_post($url, array(
			'body' => $param,
			'timeout' => $time_out ?: 15,
			'headers' => array('Accept' => 'application/json', 'Expect' => ''),
		));

		return $this->_parse_response($response, $service, $service_tag, $server);
	}

	/**
	 * Parse response JSON
	 * Mark the request successful if the response status is ok
	 *
	 * @since  3.0
	 */
	private function _parse_response($response, $service, $service_tag, $server)
	{
		// If show the error or not if failed
		$visible_err = $service !== self::API_VER && $service !== self::API_NEWS && $service !== self::SVC_D_DASH;

		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			self::debug('failed to request: ' . $error_message);

			if ($visible_err) {
				$msg = __('Failed to request via WordPress', 'litespeed-cache') . ': ' . $error_message . " [server] $server [service] $service";
				Admin_Display::error($msg);

				// Tmp disabled this node from reusing in 1 day
				if (empty($this->_summary['disabled_node'])) {
					$this->_summary['disabled_node'] = array();
				}
				$this->_summary['disabled_node'][$server] = time();
				self::save_summary();

				// Force redetect node
				self::debug('Node error, redetecting node [svc] ' . $service);
				$this->detect_cloud($service, true);
			}
			return false;
		}

		$json = \json_decode($response['body'], true);

		if (!is_array($json)) {
			self::debugErr('failed to decode response json: ' . $response['body']);

			if ($visible_err) {
				$msg = __('Failed to request via WordPress', 'litespeed-cache') . ': ' . $response['body'] . " [server] $server [service] $service";
				Admin_Display::error($msg);

				// Tmp disabled this node from reusing in 1 day
				if (empty($this->_summary['disabled_node'])) {
					$this->_summary['disabled_node'] = array();
				}
				$this->_summary['disabled_node'][$server] = time();
				self::save_summary();

				// Force redetect node
				self::debugErr('Node error, redetecting node [svc] ' . $service);
				$this->detect_cloud($service, true);
			}

			return false;
		}

		// Check and save TTL data
		if (!empty($json['_ttl'])) {
			$ttl = intval($json['_ttl']);
			self::debug('Service TTL to save: ' . $ttl);
			if ($ttl > 0 && $ttl < 86400) {
				self::save_summary(array(
					'ttl.' . $service_tag => $ttl + time(),
				));
			}
		}

		if (!empty($json['_code'])) {
			self::debugErr('Hit err _code: ' . $json['_code']);
			if ($json['_code'] == 'unpulled_images') {
				$msg = __('Cloud server refused the current request due to unpulled images. Please pull the images first.', 'litespeed-cache');
				Admin_Display::error($msg);
				return false;
			}
			if ($json['_code'] == 'blocklisted') {
				$msg = __('Your domain_key has been temporarily blocklisted to prevent abuse. You may contact support at QUIC.cloud to learn more.', 'litespeed-cache');
				Admin_Display::error($msg);
				return false;
			}

			if ($json['_code'] == 'rate_limit') {
				self::debugErr('Cloud server rate limit exceeded.');
				$msg = __('Cloud server refused the current request due to rate limiting. Please try again later.', 'litespeed-cache');
				Admin_Display::error($msg);
				return false;
			}

			if ($json['_code'] == 'heavy_load' || $json['_code'] == 'redetect_node') {
				// Force redetect node
				self::debugErr('Node redetecting node [svc] ' . $service);
				Admin_Display::info(__('Redetected node', 'litespeed-cache') . ': ' . Error::msg($json['_code']));
				$this->detect_cloud($service, true);
			}
		}

		if (!empty($json['_503'])) {
			self::debugErr('service 503 unavailable temporarily. ' . $json['_503']);

			$msg = __(
				'We are working hard to improve your online service experience. The service will be unavailable while we work. We apologize for any inconvenience.',
				'litespeed-cache'
			);
			$msg .= ' ' . $json['_503'] . " [server] $server [service] $service";
			Admin_Display::error($msg);

			// Force redetect node
			self::debugErr('Node error, redetecting node [svc] ' . $service);
			$this->detect_cloud($service, true);

			return false;
		}

		list($json, $return) = $this->extract_msg($json, $service, $server);
		if ($return) {
			return false;
		}

		self::save_summary(array(
			'last_request.' . $service_tag => $this->_summary['curr_request.' . $service_tag],
			'curr_request.' . $service_tag => 0,
		));

		if ($json) {
			self::debug2('response ok', $json);
		} else {
			self::debug2('response ok');
		}

		// Only successful request return Array
		return $json;
	}

	/**
	 * Extract msg from json
	 * @since 5.0
	 */
	public function extract_msg($json, $service, $server = false, $is_callback = false)
	{
		if (!empty($json['_info'])) {
			self::debug('_info: ' . $json['_info']);
			$msg = __('Message from QUIC.cloud server', 'litespeed-cache') . ': ' . $json['_info'];
			$msg .= $this->_parse_link($json);
			Admin_Display::info($msg);
			unset($json['_info']);
		}

		if (!empty($json['_note'])) {
			self::debug('_note: ' . $json['_note']);
			$msg = __('Message from QUIC.cloud server', 'litespeed-cache') . ': ' . $json['_note'];
			$msg .= $this->_parse_link($json);
			Admin_Display::note($msg);
			unset($json['_note']);
		}

		if (!empty($json['_success'])) {
			self::debug('_success: ' . $json['_success']);
			$msg = __('Good news from QUIC.cloud server', 'litespeed-cache') . ': ' . $json['_success'];
			$msg .= $this->_parse_link($json);
			Admin_Display::success($msg);
			unset($json['_success']);
		}

		// Upgrade is required
		if (!empty($json['_err_req_v'])) {
			self::debug('_err_req_v: ' . $json['_err_req_v']);
			$msg =
				sprintf(__('%1$s plugin version %2$s required for this action.', 'litespeed-cache'), Core::NAME, 'v' . $json['_err_req_v'] . '+') .
				" [server] $server [service] $service";

			// Append upgrade link
			$msg2 = ' ' . GUI::plugin_upgrade_link(Core::NAME, Core::PLUGIN_NAME, $json['_err_req_v']);

			$msg2 .= $this->_parse_link($json);
			Admin_Display::error($msg . $msg2);
			return array($json, true);
		}

		// Parse _carry_on info
		if (!empty($json['_carry_on'])) {
			self::debug('Carry_on usage', $json['_carry_on']);
			// Store generic info
			foreach (array('usage', 'promo', 'mini_html', 'partner', '_error', '_info', '_note', '_success') as $v) {
				if (isset($json['_carry_on'][$v])) {
					switch ($v) {
						case 'usage':
							$usage_svc_tag = in_array($service, array(self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI)) ? self::SVC_PAGE_OPTM : $service;
							$this->_summary['usage.' . $usage_svc_tag] = $json['_carry_on'][$v];
							break;

						case 'promo':
							if (empty($this->_summary[$v]) || !is_array($this->_summary[$v])) {
								$this->_summary[$v] = array();
							}
							$this->_summary[$v][] = $json['_carry_on'][$v];
							break;

						case 'mini_html':
							foreach ($json['_carry_on'][$v] as $k2 => $v2) {
								if (strpos($k2, 'ttl.') === 0) {
									$v2 += time();
								}
								$this->_summary[$v][$k2] = $v2;
							}
							break;

						case 'partner':
							$this->_summary[$v] = $json['_carry_on'][$v];
							break;

						case '_error':
						case '_info':
						case '_note':
						case '_success':
							$color_mode = substr($v, 1);
							$msgs = $json['_carry_on'][$v];
							Admin_Display::add_unique_notice($color_mode, $msgs, true);
							break;

						default:
							break;
					}
				}
			}
			self::save_summary();
			unset($json['_carry_on']);
		}

		// Parse general error msg
		if (!$is_callback && (empty($json['_res']) || $json['_res'] !== 'ok')) {
			$json_msg = !empty($json['_msg']) ? $json['_msg'] : 'unknown';
			self::debug('❌ _err: ' . $json_msg, $json);

			$str_translated = Error::msg($json_msg);
			$msg = __('Failed to communicate with QUIC.cloud server', 'litespeed-cache') . ': ' . $str_translated . " [server] $server [service] $service";
			$msg .= $this->_parse_link($json);
			$visible_err = $service !== self::API_VER && $service !== self::API_NEWS && $service !== self::SVC_D_DASH;
			if ($visible_err) {
				Admin_Display::error($msg);
			}

			// QC may try auto alias
			/** @since 5.0 Store the domain as `err_domains` only for QC auto alias feature */
			if ($json_msg == 'err_alias') {
				if (empty($this->_summary['err_domains'])) {
					$this->_summary['err_domains'] = array();
				}
				$home_url = home_url();
				if (!array_key_exists($home_url, $this->_summary['err_domains'])) {
					$this->_summary['err_domains'][$home_url] = time();
				}
				self::save_summary();
			}

			// Site not on QC, delete invalid domain key
			if ($json_msg == 'site_not_registered' || $json_msg == 'err_key') {
				$this->_reset_qc_reg();
			}

			return array($json, true);
		}

		unset($json['_res']);
		if (!empty($json['_msg'])) {
			unset($json['_msg']);
		}

		return array($json, false);
	}

	/**
	 * Clear QC linked status
	 * @since 5.0
	 */
	private function _reset_qc_reg()
	{
		unset($this->_summary['qc_activated']);
		if (!empty($this->_summary['partner'])) {
			unset($this->_summary['partner']);
		}
		self::save_summary();

		$msg = $this->_reset_qc_reg_content();
		Admin_Display::error($msg, false, true);
	}

	private function _reset_qc_reg_content()
	{
		$msg = __('Site not recognized. QUIC.cloud deactivated automatically. Please reactivate your QUIC.cloud account.', 'litespeed-cache');
		$msg .= Doc::learn_more(admin_url('admin.php?page=litespeed'), __('Click here to proceed.', 'litespeed-cache'), true, false, true);
		$msg .= Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/general/', false, false, false, true);
		return $msg;
	}

	private function _clear_reset_qc_reg_msg()
	{
		self::debug('Removed pinned reset QC reg content msg');
		$msg = $this->_reset_qc_reg_content();
		Admin_Display::dismiss_pin_by_content($msg, Admin_Display::NOTICE_RED, true);
	}

	/**
	 * REST call: check if the error domain is valid call for auto alias purpose
	 * @since 5.0
	 */
	public function rest_err_domains()
	{
		if (empty($_POST['main_domain']) || empty($_POST['alias'])) {
			return self::err('lack_of_param');
		}

		$this->extract_msg($_POST, 'Quic.cloud', false, true);

		if ($this->_is_err_domain($_POST['alias'])) {
			if ($_POST['alias'] == home_url()) {
				$this->_remove_domain_from_err_list($_POST['alias']);
			}
			return self::ok();
		}

		return self::err('Not an alias req from here');
	}

	/**
	 * Remove a domain from err domain
	 * @since 5.0
	 */
	private function _remove_domain_from_err_list($url)
	{
		unset($this->_summary['err_domains'][$url]);
		self::save_summary();
	}

	/**
	 * Check if is err domain
	 * @since 5.0
	 */
	private function _is_err_domain($home_url)
	{
		if (empty($this->_summary['err_domains'])) {
			return false;
		}
		if (!array_key_exists($home_url, $this->_summary['err_domains'])) {
			return false;
		}
		// Auto delete if too long ago
		if (time() - $this->_summary['err_domains'][$home_url] > 86400 * 10) {
			$this->_remove_domain_from_err_list($home_url);

			return false;
		}
		if (time() - $this->_summary['err_domains'][$home_url] > 86400) {
			return false;
		}
		return true;
	}

	/**
	 * Show promo from cloud
	 *
	 * @since  3.0
	 * @access public
	 */
	public function show_promo()
	{
		if (empty($this->_summary['promo'])) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_promo.tpl.php';
	}

	/**
	 * Clear promo from cloud
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _clear_promo()
	{
		if (count($this->_summary['promo']) > 1) {
			array_shift($this->_summary['promo']);
		} else {
			$this->_summary['promo'] = array();
		}
		self::save_summary();
	}

	/**
	 * Parse _links from json
	 *
	 * @since  1.6.5
	 * @since  1.6.7 Self clean the parameter
	 * @access private
	 */
	private function _parse_link(&$json)
	{
		$msg = '';

		if (!empty($json['_links'])) {
			foreach ($json['_links'] as $v) {
				$msg .= ' ' . sprintf('<a href="%s" class="%s" target="_blank">%s</a>', $v['link'], !empty($v['cls']) ? $v['cls'] : '', $v['title']);
			}

			unset($json['_links']);
		}

		return $msg;
	}

	/**
	 * Request callback validation from Cloud
	 *
	 * @since  3.0
	 * @access public
	 */
	public function ip_validate()
	{
		if (empty($_POST['hash'])) {
			return self::err('lack_of_params');
		}

		if ($_POST['hash'] != md5(substr($this->_summary['pk_b64'], 0, 4))) {
			self::debug('__callback IP request decryption failed');
			return self::err('err_hash');
		}

		Control::set_nocache('Cloud IP hash validation');

		$resp_hash = md5(substr($this->_summary['pk_b64'], 2, 4));

		self::debug('__callback IP request hash: ' . $resp_hash);

		return self::ok(array('hash' => $resp_hash));
	}

	/**
	 * Check if this visit is from cloud or not
	 *
	 * @since  3.0
	 */
	public function is_from_cloud()
	{
		// return true;
		$check_point = time() - 86400 * self::TTL_IPS;
		if (empty($this->_summary['ips']) || empty($this->_summary['ips_ts']) || $this->_summary['ips_ts'] < $check_point) {
			self::debug('Force updating ip as ips_ts is older than ' . self::TTL_IPS . ' days');
			$this->_update_ips();
		}

		$res = $this->cls('Router')->ip_access($this->_summary['ips']);
		if (!$res) {
			self::debug('❌ Not our cloud IP');

			// Auto check ip list again but need an interval limit safety.
			if (empty($this->_summary['ips_ts_runner']) || time() - $this->_summary['ips_ts_runner'] > 600) {
				self::debug('Force updating ip as ips_ts_runner is older than 10mins');
				// Refresh IP list for future detection
				$this->_update_ips();
				$res = $this->cls('Router')->ip_access($this->_summary['ips']);
				if (!$res) {
					self::debug('❌ 2nd time: Not our cloud IP');
				} else {
					self::debug('✅ Passed Cloud IP verification');
				}
				return $res;
			}
		} else {
			self::debug('✅ Passed Cloud IP verification');
		}

		return $res;
	}

	/**
	 * Update Cloud IP list
	 *
	 * @since 4.2
	 */
	private function _update_ips()
	{
		self::debug('Load remote Cloud IP list from ' . self::CLOUD_IPS);
		// Prevent multiple call in a short period
		self::save_summary(array('ips_ts' => time(), 'ips_ts_runner' => time()));

		$response = wp_safe_remote_get(self::CLOUD_IPS . '?json');
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			self::debug('failed to get ip whitelist: ' . $error_message);
			throw new \Exception('Failed to fetch QUIC.cloud whitelist ' . $error_message);
		}

		$json = \json_decode($response['body'], true);

		self::debug('Load ips', $json);
		self::save_summary(array('ips' => $json));
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
		self::debug("❌ Error response code: $code");
		return array('_res' => 'err', '_msg' => $code);
	}

	/**
	 * Return pong for ping to check PHP function availability
	 * @since 6.5
	 */
	public function ping()
	{
		$resp = array(
			'v_lscwp' => Core::VER,
			'v_php' => PHP_VERSION,
			'v_wp' => $GLOBALS['wp_version'],
			'home_url' => home_url(),
		);
		if (!empty($_POST['funcs'])) {
			foreach ($_POST['funcs'] as $v) {
				$resp[$v] = function_exists($v) ? 'y' : 'n';
			}
		}
		if (!empty($_POST['classes'])) {
			foreach ($_POST['classes'] as $v) {
				$resp[$v] = class_exists($v) ? 'y' : 'n';
			}
		}
		if (!empty($_POST['consts'])) {
			foreach ($_POST['consts'] as $v) {
				$resp[$v] = defined($v) ? 'y' : 'n';
			}
		}
		return self::ok($resp);
	}

	/**
	 * Display a banner for dev env if using preview QC node.
	 * @since 7.0
	 */
	public function maybe_preview_banner()
	{
		if (strpos(self::CLOUD_SERVER, 'preview.')) {
			Admin_Display::note(__('Linked to QUIC.cloud preview environment, for testing purpose only.', 'litespeed-cache'), true, true, 'litespeed-warning-bg');
		}
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
			case self::TYPE_CLEAR_CLOUD:
				$this->clear_cloud();
				break;

			case self::TYPE_REDETECT_CLOUD:
				if (!empty($_GET['svc'])) {
					$this->detect_cloud($_GET['svc'], true);
				}
				break;

			case self::TYPE_CLEAR_PROMO:
				$this->_clear_promo();
				break;

			case self::TYPE_RESET:
				$this->reset_qc();
				break;

			case self::TYPE_ACTIVATE:
				$this->init_qc();
				break;

			case self::TYPE_LINK:
				$this->link_qc();
				break;

			case self::TYPE_ENABLE_CDN:
				$this->enable_cdn();
				break;

			case self::TYPE_API:
				if (!empty($_GET['action2'])) {
					$this->api_link_call($_GET['action2']);
				}
				break;

			case self::TYPE_SYNC_STATUS:
				$this->load_qc_status_for_dash('cdn_dash', true);
				$msg = __('Sync QUIC.cloud status successfully.', 'litespeed-cache');
				Admin_Display::success($msg);
				break;

			case self::TYPE_SYNC_USAGE:
				$this->sync_usage();

				$msg = __('Sync credit allowance with Cloud Server successfully.', 'litespeed-cache');
				Admin_Display::success($msg);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
