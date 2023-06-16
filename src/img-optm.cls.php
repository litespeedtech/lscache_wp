<?php

/**
 * The class to optimize image.
 *
 * @since 		2.0
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/src
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined('WPINC') || exit;

class Img_Optm extends Base
{
	const LOG_TAG = '🗜️';

	const CLOUD_ACTION_NEW_REQ = 'new_req';
	const CLOUD_ACTION_TAKEN = 'taken';
	const CLOUD_ACTION_REQUEST_DESTROY = 'imgoptm_destroy';
	const CLOUD_ACTION_CLEAN = 'clean';

	const TYPE_NEW_REQ = 'new_req';
	const TYPE_RESCAN = 'rescan';
	const TYPE_DESTROY = 'destroy';
	const TYPE_CLEAN = 'clean';
	const TYPE_PULL = 'pull';
	const TYPE_BATCH_SWITCH_ORI = 'batch_switch_ori';
	const TYPE_BATCH_SWITCH_OPTM = 'batch_switch_optm';
	const TYPE_CALC_BKUP = 'calc_bkup';
	const TYPE_RESET_ROW = 'reset_row';
	const TYPE_RM_BKUP = 'rm_bkup';

	const STATUS_NEW 		= 0; // 'new';
	const STATUS_RAW 		= 1; // 'raw';
	const STATUS_REQUESTED 	= 3; // 'requested';
	const STATUS_NOTIFIED 	= 6; // 'notified';
	const STATUS_DUPLICATED 	= 8; // 'duplicated';
	const STATUS_PULLED 		= 9; // 'pulled';
	const STATUS_FAILED 		= -1; //'failed';
	const STATUS_MISS 		= -3; // 'miss';
	const STATUS_ERR_FETCH 	= -5; // 'err_fetch';
	const STATUS_ERR_404 	= -6; // 'err_404';
	const STATUS_ERR_OPTM 	= -7; // 'err_optm';
	const STATUS_XMETA 		= -8; // 'xmeta';
	const STATUS_ERR 		= -9; // 'err';
	const DB_SIZE = 'litespeed-optimize-size';
	const DB_SET = 'litespeed-optimize-set';

	const DB_NEED_PULL = 'need_pull';

	private $wp_upload_dir;
	private $tmp_pid;
	private $tmp_type;
	private $tmp_path;
	private $_img_in_queue = array();
	private $_existed_src_list = array();
	private $_pids_set = array();
	private $_thumbnail_set = '';
	private $_table_img_optm;
	private $_table_img_optming;
	private $_cron_ran = false;

	private $__media;
	private $__data;
	protected $_summary;

	/**
	 * Init
	 *
	 * @since  2.0
	 */
	public function __construct()
	{
		Debug2::debug2('[ImgOptm] init');

		$this->wp_upload_dir = wp_upload_dir();
		$this->__media = $this->cls('Media');
		$this->__data = $this->cls('Data');
		$this->_table_img_optm = $this->__data->tb('img_optm');
		$this->_table_img_optming = $this->__data->tb('img_optming');

		$this->_summary = self::get_summary();
		if (empty($this->_summary['next_post_id'])) $this->_summary['next_post_id'] = 0;
	}

	/**
	 * Gather images auto when update attachment meta
	 * This is to optimize new uploaded images first. Stored in img_optm table.
	 * Later normal process will auto remove these records when trying to optimize these images again
	 *
	 * @since  4.0
	 */
	public function wp_update_attachment_metadata($meta_value, $post_id)
	{
		global $wpdb;

		self::debug2('🖌️ Auto update attachment meta [id] ' . $post_id);
		if (empty($meta_value['file'])) {
			return;
		}

		// Load gathered images
		if (!$this->_existed_src_list) { // To aavoid extra query when recalling this function
			self::debug('SELECT src from img_optm table');
			if ($this->__data->tb_exist('img_optm')) {
				$q = "SELECT src FROM `$this->_table_img_optm` WHERE post_id = %d";
				$list = $wpdb->get_results($wpdb->prepare($q, $post_id));
				foreach ($list as $v) {
					$this->_existed_src_list[] = $post_id . '.' . $v->src;
				}
			}
			if ($this->__data->tb_exist('img_optming')) {
				$q = "SELECT src FROM `$this->_table_img_optming` WHERE post_id = %d";
				$list = $wpdb->get_results($wpdb->prepare($q, $post_id));
				foreach ($list as $v) {
					$this->_existed_src_list[] = $post_id . '.' . $v->src;
				}
			} else {
				$this->__data->tb_create('img_optming');
			}
		}

		// Prepare images
		$this->tmp_pid = $post_id;
		$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
		$this->_append_img_queue($meta_value, true);
		if (!empty($meta_value['sizes'])) {
			array_map(array($this, '_append_img_queue'), $meta_value['sizes']);
		}

		if (!$this->_img_in_queue) {
			self::debug('auto update attachment meta 2 bypass: empty _img_in_queue');
			return;
		}

		// Save to DB
		$this->_save_raw();

		// $this->_send_request();
	}

	/**
	 * Auto send optm request
	 *
	 * @since  2.4.1
	 * @access public
	 */
	public static function cron_auto_request()
	{
		if (!defined('DOING_CRON')) {
			return false;
		}

		$instance = self::cls();
		$instance->new_req();
	}

	/**
	 * Calculate wet run allowance
	 *
	 * @since 3.0
	 */
	public function wet_limit()
	{
		$wet_limit = 1;
		if (!empty($this->_summary['img_taken'])) {
			$wet_limit = pow($this->_summary['img_taken'], 2);
		}

		if ($wet_limit == 1 && !empty($this->_summary['img_status.' . self::STATUS_ERR_OPTM])) {
			$wet_limit = pow($this->_summary['img_status.' . self::STATUS_ERR_OPTM], 2);
		}

		if ($wet_limit < Cloud::IMG_OPTM_DEFAULT_GROUP) {
			return $wet_limit;
		}

		// No limit
		return false;
	}

	/**
	 * Push raw img to image optm server
	 *
	 * @since 1.6
	 * @access public
	 */
	public function new_req()
	{
		global $wpdb;

		// check if is running
		if (!empty($this->_summary['is_running']) && time() - $this->_summary['is_running'] < apply_filters('litespeed_imgoptm_new_req_interval', 3600)) {
			self::debug('The previous req was in 3600s.');
			return;
		}
		$this->_summary['is_running'] = time();
		self::save_summary();

		// Check if has credit to push
		$allowance = Cloud::cls()->allowance(Cloud::SVC_IMG_OPTM);

		$wet_limit = $this->wet_limit();

		self::debug("allowance_max $allowance wet_limit $wet_limit");
		if ($wet_limit && $wet_limit < $allowance) {
			$allowance = $wet_limit;
		}

		if (!$allowance) {
			self::debug('❌ No credit');
			Admin_Display::error(Error::msg('out_of_quota'));
			$this->_finished_running();
			return;
		}

		self::debug('preparing images to push');

		$this->__data->tb_create('img_optming');

		$q = "SELECT COUNT(1) FROM `$this->_table_img_optming` WHERE optm_status = %d";
		$q = $wpdb->prepare($q, array(self::STATUS_REQUESTED));
		$total_requested = $wpdb->get_var($q);
		$max_requested = $allowance * 1;

		if ($total_requested > $max_requested) {
			self::debug('❌ Too many queued images (' . $total_requested . ' > ' . $max_requested . ')');
			Admin_Display::error(Error::msg('too_many_requested'));
			$this->_finished_running();
			return;
		}

		// Limit maximum number of items waiting to be pulled
		$q = "SELECT COUNT(1) FROM `$this->_table_img_optming` WHERE optm_status = %d";
		$q = $wpdb->prepare($q, array(self::STATUS_NOTIFIED));
		$total_notified = $wpdb->get_var($q);
		$max_notified = $allowance * 5;

		if ($total_notified > $max_notified) {
			self::debug('❌ Too many notified images (' . $total_notified . ' > ' . $max_notified . ')');
			Admin_Display::error(Error::msg('too_many_notified'));
			$this->_finished_running();
			return;
		}

		$q = "SELECT COUNT(1) FROM `$this->_table_img_optming` WHERE optm_status IN (%d, %d)";
		$q = $wpdb->prepare($q, array(self::STATUS_NEW, self::STATUS_RAW));
		$total_new = $wpdb->get_var($q);
		$allowance -= $total_new;

		// Get images
		$list = array();
		if ($allowance > 0) {
			$q = "SELECT b.post_id, b.meta_value
				FROM `$wpdb->posts` a
				LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
				WHERE b.meta_key = '_wp_attachment_metadata'
					AND a.post_type = 'attachment'
					AND a.post_status = 'inherit'
					AND a.ID>%d
					AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
				ORDER BY a.ID
				LIMIT %d
				";
			$q = $wpdb->prepare($q, array($this->_summary['next_post_id'], $allowance));
			$list = $wpdb->get_results($q);
		}

		if (!$list) {
			// $msg = __('No new image to send.', 'litespeed-cache');
			// Admin_Display::succeed($msg);

			// self::debug('new_req() bypass: no new image found');
			// $this->_finished_running();
			// return;
		}

		if ($list) {
			foreach ($list as $v) {
				if (!$v->post_id) continue;

				$this->_summary['next_post_id'] = $v->post_id;

				$meta_value = $this->_parse_wp_meta_value($v);
				if (!$meta_value) {
					continue;
				}
				$meta_value['file'] = wp_normalize_path($meta_value['file']);
				$basedir = $this->wp_upload_dir['basedir'] . '/';
				if (strpos($meta_value['file'], $basedir) === 0) {
					$meta_value['file'] = substr($meta_value['file'], strlen($basedir));
				}

				$this->tmp_pid = $v->post_id;
				$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
				$this->_append_img_queue($meta_value, true);
				if (!empty($meta_value['sizes'])) {
					array_map(array($this, '_append_img_queue'), $meta_value['sizes']);
				}
			}

			self::save_summary();

			if (!$this->_img_in_queue) {
				self::debug('gather_images bypass: empty _img_in_queue');
				$this->_finished_running();
				return;
			}

			$num_a = count($this->_img_in_queue);
			self::debug('Images found: ' . $num_a);
			$this->_filter_duplicated_src();
			self::debug('Images after duplicated: ' . count($this->_img_in_queue));
			$this->_filter_invalid_src();
			self::debug('Images after invalid: ' . count($this->_img_in_queue));
			// Check w/ legacy imgoptm table, bypass finished images
			$this->_filter_legacy_src();

			$num_b = count($this->_img_in_queue);
			if ($num_b != $num_a) {
				self::debug('Images after filtered duplicated/invalid/legacy src: ' . $num_b);
			}

			// Save to DB
			$this->_save_raw();
		}

		// Push to Cloud server
		$accepted_imgs = $this->_send_request();

		$this->_finished_running();
		if (!$accepted_imgs) {
			return;
		}

		$placeholder1 = Admin_Display::print_plural($accepted_imgs[0], 'image');
		$placeholder2 = Admin_Display::print_plural($accepted_imgs[1], 'image');
		$msg = sprintf(__('Pushed %1$s to Cloud server, accepted %2$s.', 'litespeed-cache'), $placeholder1, $placeholder2);
		Admin_Display::succeed($msg);
	}

	/**
	 * Set running to done
	 */
	private function _finished_running()
	{
		$this->_summary['is_running'] = 0;
		self::save_summary();
	}

	/**
	 * Add a new img to queue which will be pushed to request
	 *
	 * @since 1.6
	 * @access private
	 */
	private function _append_img_queue($meta_value, $is_ori_file = false)
	{
		if (empty($meta_value['file']) || empty($meta_value['width']) || empty($meta_value['height'])) {
			self::debug2('bypass image due to lack of file/w/h: pid ' . $this->tmp_pid, $meta_value);
			return;
		}

		$short_file_path = $meta_value['file'];

		if (!$is_ori_file) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		// Check if src is gathered already or not
		if (in_array($this->tmp_pid . '.' . $short_file_path, $this->_existed_src_list)) {
			// Debug2::debug2( '[Img_Optm] bypass image due to gathered: pid ' . $this->tmp_pid . ' ' . $short_file_path );
			return;
		} else {
			// Append handled images
			$this->_existed_src_list[] = $this->tmp_pid . '.' . $short_file_path;
		}

		// check file exists or not
		$_img_info = $this->__media->info($short_file_path, $this->tmp_pid);

		if (!$_img_info || !in_array(pathinfo($short_file_path, PATHINFO_EXTENSION), array('jpg', 'jpeg', 'png', 'gif'))) {
			self::debug2('bypass image due to file not exist: pid ' . $this->tmp_pid . ' ' . $short_file_path);
			return;
		}

		// Debug2::debug2( '[Img_Optm] adding image: pid ' . $this->tmp_pid );

		$this->_img_in_queue[] = array(
			'pid'	=> $this->tmp_pid,
			'md5'	=> $_img_info['md5'],
			'url'	=> $_img_info['url'],
			'src'	=> $short_file_path, // not needed in LiteSpeed IAPI, just leave for local storage after post
			'mime_type'	=> !empty($meta_value['mime-type']) ? $meta_value['mime-type'] : '',
		);
	}

	/**
	 * Save gathered image raw data
	 *
	 * @since  3.0
	 */
	private function _save_raw()
	{
		if (empty($this->_img_in_queue)) return;
		$data = array();
		$pid_list = array();
		foreach ($this->_img_in_queue as $k => $v) {
			$_img_info = $this->__media->info($v['src'], $v['pid']);

			// attachment doesn't exist, delete the record
			if (empty($_img_info['url']) || empty($_img_info['md5'])) {
				unset($this->_img_in_queue[$k]);
				continue;
			}
			$pid_list[] = (int)$v['pid'];

			$data[] = $v['pid'];
			$data[] = self::STATUS_RAW;
			$data[] = $v['src'];
		}

		global $wpdb;
		$fields = 'post_id, optm_status, src';
		$q = "INSERT INTO `$this->_table_img_optming` ( $fields ) VALUES ";

		// Add placeholder
		$q .= Utility::chunk_placeholder($data, $fields);

		// Store data
		$wpdb->query($wpdb->prepare($q, $data));

		$count = count($this->_img_in_queue);
		self::debug('Added raw images [total] ' . $count);

		$this->_img_in_queue = array();

		// Save thumbnail groups for future rescan index
		$this->_gen_thumbnail_set();

		$pid_list = array_unique($pid_list);
		self::debug('pid list to append to postmeta', $pid_list);
		$pid_list = array_diff($pid_list, $this->_pids_set);
		$this->_pids_set = array_merge($this->_pids_set, $pid_list);

		$existed_meta = $wpdb->get_results("SELECT * FROM `$wpdb->postmeta` WHERE post_id IN ('" . implode("','", $pid_list) . "') AND meta_key='" . self::DB_SET . "'");
		$existed_pid = array();
		if ($existed_meta) {
			foreach ($existed_meta as $v) {
				$existed_pid[] = $v->post_id;
			}
			self::debug('pid list to update postmeta', $existed_pid);
			$wpdb->query($wpdb->prepare("UPDATE `$wpdb->postmeta` SET meta_value=%s WHERE post_id IN ('" . implode("','", $existed_pid) . "') AND meta_key=%s", array($this->_thumbnail_set, self::DB_SET)));
		}

		# Add new meta
		$new_pids = $existed_pid ? array_diff($pid_list, $existed_pid) : $pid_list;
		if ($new_pids) {
			self::debug('pid list to update postmeta', $new_pids);
			foreach ($new_pids as $v) {
				self::debug('New group set info [pid] ' . $v);
				$q = "INSERT INTO `$wpdb->postmeta` (post_id, meta_key, meta_value) VALUES (%d, %s, %s)";
				$wpdb->query($wpdb->prepare($q, array($v, self::DB_SET, $this->_thumbnail_set)));
			}
		}
	}

	/**
	 * Generate thumbnail sets of current image group
	 *
	 * @since 5.4
	 */
	private function _gen_thumbnail_set()
	{
		if ($this->_thumbnail_set) {
			return;
		}
		$set = array();
		foreach (Media::cls()->get_image_sizes() as $size) {
			$curr_size = $size['width'] . 'x' . $size['height'];
			if (in_array($curr_size, $set)) continue;
			$set[] = $curr_size;
		}
		$this->_thumbnail_set = implode(PHP_EOL, $set);
	}

	/**
	 * Filter duplicated src in work table and $this->_img_in_queue, then mark them as duplicated
	 *
	 * @since 2.0
	 * @access private
	 */
	private function _filter_duplicated_src()
	{
		global $wpdb;

		$srcpath_list = array();

		$list = $wpdb->get_results("SELECT src FROM `$this->_table_img_optming`");
		foreach ($list as $v) {
			$srcpath_list[] = $v->src;
		}

		foreach ($this->_img_in_queue as $k => $v) {
			if (in_array($v['src'], $srcpath_list)) {
				unset($this->_img_in_queue[$k]);
				continue;
			}

			$srcpath_list[] = $v['src'];
		}
	}

	/**
	 * Filter legacy finished ones
	 *
	 * @since 5.4
	 */
	private function _filter_legacy_src()
	{
		global $wpdb;

		if (!$this->__data->tb_exist('img_optm')) return;

		if (!$this->_img_in_queue) return;

		$finished_ids = array();

		Utility::compatibility();
		$post_ids = array_unique(array_column($this->_img_in_queue, 'pid'));
		$list = $wpdb->get_results("SELECT post_id FROM `$this->_table_img_optm` WHERE post_id in (" . implode(',', $post_ids) . ") GROUP BY post_id");
		foreach ($list as $v) {
			$finished_ids[] = $v->post_id;
		}

		foreach ($this->_img_in_queue as $k => $v) {
			if (in_array($v['pid'], $finished_ids)) {
				self::debug('Legacy image optimized [pid] ' . $v['pid']);
				unset($this->_img_in_queue[$k]);
				continue;
			}
		}

		// Drop all existing legacy records
		$wpdb->query("DELETE FROM `$this->_table_img_optm` WHERE post_id in (" . implode(',', $post_ids) . ")");
	}

	/**
	 * Filter the invalid src before sending
	 *
	 * @since 3.0.8.3
	 * @access private
	 */
	private function _filter_invalid_src()
	{
		$img_in_queue_invalid = array();
		foreach ($this->_img_in_queue as $k => $v) {
			if ($v['src']) {
				$extension = pathinfo($v['src'], PATHINFO_EXTENSION);
			}
			if (!$v['src'] || empty($extension) || !in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
				$img_in_queue_invalid[] = $v['id'];
				unset($this->_img_in_queue[$k]);
				continue;
			}
		}

		if (!$img_in_queue_invalid) {
			return;
		}

		$count = count($img_in_queue_invalid);
		$msg = sprintf(__('Cleared %1$s invalid images.', 'litespeed-cache'), $count);
		Admin_Display::succeed($msg);

		self::debug('Found invalid src [total] ' . $count);
	}

	/**
	 * Push img request to Cloud server
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _send_request()
	{
		global $wpdb;

		$_img_in_queue = $wpdb->get_results("SELECT id,src,post_id FROM `$this->_table_img_optming` WHERE optm_status=" . self::STATUS_RAW);
		if (!$_img_in_queue) return;

		self::debug('Load img in queue [total] ' . count($_img_in_queue));

		$list = array();
		foreach ($_img_in_queue as $v) {
			/**
			 * Filter `litespeed_img_optm_options_per_image`
			 * @since 2.4.2
			 */
			/**
			 * 				$optm_options |= API::IMG_OPTM_BM_ORI;
			 * 				$optm_options |= API::IMG_OPTM_BM_WEBP;
			 * 				$optm_options |= API::IMG_OPTM_BM_LOSSLESS;
			 * 				$optm_options |= API::IMG_OPTM_BM_EXIF;
			 */
			$optm_options = apply_filters('litespeed_img_optm_options_per_image', 0, $v->src);

			$_img_info = $this->__media->info($v->src, $v->post_id);
			$img = array(
				'id'	=> $v->id,
				'url'	=> $_img_info['url'],
				'md5'	=> $_img_info['md5'],
			);
			if ($optm_options) {
				$img['optm_options'] = $optm_options;
			}

			$list[] = $img;
		}

		if (!$list) {
			$msg = __('No valid image found in the current request.', 'litespeed-cache');
			Admin_Display::error($msg);
			return;
		}

		$data = array(
			'action'		=> self::CLOUD_ACTION_NEW_REQ,
			'list' 			=> json_encode($list),
			'optm_ori'		=> $this->conf(self::O_IMG_OPTM_ORI) ? 1 : 0,
			'optm_webp'		=> $this->conf(self::O_IMG_OPTM_WEBP) ? 1 : 0,
			'optm_lossless'	=> $this->conf(self::O_IMG_OPTM_LOSSLESS) ? 1 : 0,
			'keep_exif'		=> $this->conf(self::O_IMG_OPTM_EXIF) ? 1 : 0,
		);

		// Push to Cloud server
		$json = Cloud::post(Cloud::SVC_IMG_OPTM, $data);
		if (!$json) {
			return;
		}

		// Check data format
		if (empty($json['ids'])) {
			self::debug('Failed to parse response data from Cloud server ', $json);
			$msg = __('No valid image found by Cloud server in the current request.', 'litespeed-cache');
			Admin_Display::error($msg);
			return;
		}

		self::debug('Returned data from Cloud server count: ' . count($json['ids']));

		$ids = implode(',', array_map('intval', $json['ids']));
		// Update img table
		$q = "UPDATE `$this->_table_img_optming` SET optm_status = '" . self::STATUS_REQUESTED . "' WHERE id IN ( $ids )";
		$wpdb->query($q);

		$this->_summary['last_requested'] = time();
		self::save_summary();

		return array(count($list), count($json['ids']));
	}

	/**
	 * Cloud server notify Client img status changed
	 *
	 * @access public
	 */
	public function notify_img()
	{
		// Interval validation to avoid hacking domain_key
		if (!empty($this->_summary['notify_ts_err']) && time() - $this->_summary['notify_ts_err'] < 3) {
			return Cloud::err('too_often');
		}

		$post_data = json_decode(file_get_contents('php://input'), true);
		if (is_null($post_data)) {
			$post_data = $_POST;
		}

		// Validate key
		if (empty($post_data['domain_key']) || $post_data['domain_key'] !== md5($this->conf(self::O_API_KEY))) {
			$this->_summary['notify_ts_err'] = time();
			self::save_summary();
			return Cloud::err('wrong_key');
		}

		global $wpdb;

		$notified_data = $post_data['data'];
		if (empty($notified_data) || !is_array($notified_data)) {
			self::debug('❌ notify exit: no notified data');
			return Cloud::err('no notified data');
		}

		if (empty($post_data['server']) || substr($post_data['server'], -11) !== '.quic.cloud') {
			self::debug('notify exit: no/wrong server');
			return Cloud::err('no/wrong server');
		}

		if (empty($post_data['status'])) {
			self::debug('notify missing status');
			return Cloud::err('no status');
		}

		$status = $post_data['status'];
		self::debug('notified status=' . $status);

		$last_log_pid = 0;

		if (empty($this->_summary['reduced'])) {
			$this->_summary['reduced'] = 0;
		}

		if ($status == self::STATUS_NOTIFIED) {
			// Notified data format: [ img_optm_id => [ id=>, src_size=>, ori=>, ori_md5=>, ori_reduced=>, webp=>, webp_md5=>, webp_reduced=> ] ]
			$q = "SELECT a.*, b.meta_id as b_meta_id, b.meta_value AS b_optm_info
					FROM `$this->_table_img_optming` a
					LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.post_id AND b.meta_key = %s
					WHERE a.id IN ( " . implode(',', array_fill(0, count($notified_data), '%d')) . " )";
			$list = $wpdb->get_results($wpdb->prepare($q, array_merge(array(self::DB_SIZE), array_keys($notified_data))));
			$ls_optm_size_row_exists_postids = array();
			foreach ($list as $v) {
				$json = $notified_data[$v->id];

				$server = !empty($json['server']) ? $json['server'] : $post_data['server'];

				$server_info = array(
					'server'	=> $server,
				);

				// Save server side ID to send taken notification after pulled
				$server_info['id'] = $json['id'];
				if (!empty($json['file_id'])) {
					$server_info['file_id'] = $json['file_id'];
				}

				// Optm info array
				$postmeta_info =  array(
					'ori_total' => 0,
					'ori_saved' => 0,
					'webp_total' => 0,
					'webp_saved' => 0,
				);
				// Init postmeta_info for the first one
				if (!empty($v->b_meta_id)) {
					foreach (maybe_unserialize($v->b_optm_info) as $k2 => $v2) {
						$postmeta_info[$k2] += $v2;
					}
				}

				if (!empty($json['ori'])) {
					$server_info['ori_md5'] = $json['ori_md5'];
					$server_info['ori'] = $json['ori'];

					// Append meta info
					$postmeta_info['ori_total'] += $json['src_size'];
					$postmeta_info['ori_saved'] += $json['ori_reduced']; // optimized image size info in img_optm tb will be updated when pull

					$this->_summary['reduced'] += $json['ori_reduced'];
				}

				if (!empty($json['webp'])) {
					$server_info['webp_md5'] = $json['webp_md5'];
					$server_info['webp'] = $json['webp'];

					// Append meta info
					$postmeta_info['webp_total'] += $json['src_size'];
					$postmeta_info['webp_saved'] += $json['webp_reduced'];

					$this->_summary['reduced'] += $json['webp_reduced'];
				}

				// Update status and data in working table
				$q = "UPDATE `$this->_table_img_optming` SET optm_status = %d, server_info = %s WHERE id = %d ";
				$wpdb->query($wpdb->prepare($q, array($status, json_encode($server_info), $v->id)));

				// Update postmeta for optm summary
				$postmeta_info = serialize($postmeta_info);
				if (empty($v->b_meta_id) && !in_array($v->post_id, $ls_optm_size_row_exists_postids)) {
					self::debug('New size info [pid] ' . $v->post_id);
					$q = "INSERT INTO `$wpdb->postmeta` ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )";
					$wpdb->query($wpdb->prepare($q, array($v->post_id, self::DB_SIZE, $postmeta_info)));
					$ls_optm_size_row_exists_postids[] = $v->post_id;
				} else {
					$q = "UPDATE `$wpdb->postmeta` SET meta_value = %s WHERE meta_id = %d ";
					$wpdb->query($wpdb->prepare($q, array($postmeta_info, $v->b_meta_id)));
				}


				// write log
				$pid_log = $last_log_pid == $v->post_id ? '.' : $v->post_id;
				self::debug('notify_img [status] ' . $status . " \t\t[pid] " . $pid_log . " \t\t[id] " . $v->id);
				$last_log_pid = $v->post_id;
			}

			self::save_summary();

			// Mark need_pull tag for cron
			self::update_option(self::DB_NEED_PULL, self::STATUS_NOTIFIED);
		} else { // Other errors will directly remove the working records
			// Delete from working table
			$q = "DELETE FROM `$this->_table_img_optming` WHERE id IN ( " . implode(',', array_fill(0, count($notified_data), '%d')) . " ) ";
			$wpdb->query($wpdb->prepare($q, $notified_data));
		}

		return Cloud::ok(array('count' => count($notified_data)));
	}

	/**
	 * Cron start async req
	 *
	 * @since 5.5
	 */
	public static function start_async_cron()
	{
		Task::async_call('imgoptm');
	}

	/**
	 * Manually start async req
	 *
	 * @since 5.5
	 */
	public static function start_async()
	{
		Task::async_call('imgoptm_force');

		$msg = __('Started async image optimization request', 'litespeed-cache');
		Admin_Display::success($msg);
	}

	/**
	 * Ajax req handler
	 *
	 * @since 5.5
	 */
	public static function async_handler($force = false)
	{
		self::debug('------------async-------------start_async_handler');

		$tag = self::get_option(self::DB_NEED_PULL);
		if (!$tag || $tag != self::STATUS_NOTIFIED) {
			self::debug('❌ no need pull [tag] ' . $tag);
			return;
		}

		self::cls()->pull($force);
	}

	/**
	 * Pull optimized img
	 *
	 * @since  1.6
	 * @access public
	 */
	public function pull($manual = false)
	{
		global $wpdb;

		self::debug('' . ($manual ? 'Manually' : 'Cron') . ' pull started');

		if ($this->cron_running()) {
			self::debug('Pull cron is running');

			$msg = __('Pull Cron is running', 'litespeed-cache');
			Admin_Display::note($msg);
			return;
		}

		$q = "SELECT * FROM `$this->_table_img_optming` WHERE optm_status = %d ORDER BY id LIMIT 1";
		$_q = $wpdb->prepare($q, self::STATUS_NOTIFIED);

		$optm_ori = $this->conf(self::O_IMG_OPTM_ORI);
		$rm_ori_bkup = $this->conf(self::O_IMG_OPTM_RM_BKUP);
		$optm_webp = $this->conf(self::O_IMG_OPTM_WEBP);

		$total_pulled_ori = 0;
		$total_pulled_webp = 0;
		$beginning = time();

		$server_list = array();

		while ($row_img = $wpdb->get_row($_q)) {
			/**
			 * Update cron timestamp to avoid duplicated running
			 * @since  1.6.2
			 */
			$this->_update_cron_running();

			$local_file = $this->wp_upload_dir['basedir'] . '/' . $row_img->src;

			$server_info = json_decode($row_img->server_info, true);
			if (empty($server_info['ori'])) {
				// Delete working table
				$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
				$wpdb->query($wpdb->prepare($q, $row_img->id));

				continue;
			}
			/**
			 * Use wp orignal get func to avoid allow_url_open off issue
			 * @since  1.6.5
			 */
			$image_url = $server_info['server'] . '/' . $server_info['ori'];
			self::debug('Pulling image: ' . $image_url);
			$response = wp_remote_get($image_url, array('timeout' => 60));
			if (is_wp_error($response)) {
				$error_message = $response->get_error_message();
				self::debug('❌ failed to pull image: ' . $error_message);
				return;
			}

			if ($response['response']['code'] == 404) {
				$this->_step_back_image($row_img->id);

				$msg = __('Some optimized image file(s) has expired and was cleared.', 'litespeed-cache');
				Admin_Display::error($msg);
				continue;
			}

			file_put_contents($local_file . '.tmp', $response['body']);

			if (!file_exists($local_file . '.tmp') || !filesize($local_file . '.tmp') || md5_file($local_file . '.tmp') !== $server_info['ori_md5']) {
				self::debug('❌ Failed to pull optimized img: file md5 mismatch [url] ' . $server_info['server'] . '/' . $server_info['ori'] . ' [server_md5] ' . $server_info['ori_md5']);

				// Delete working table
				$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
				$wpdb->query($wpdb->prepare($q, $row_img->id));

				$msg = __('One or more pulled images does not match with the notified image md5', 'litespeed-cache');
				Admin_Display::error($msg);
				continue;
			}

			// Backup ori img
			if (!$rm_ori_bkup) {
				$extension = pathinfo($local_file, PATHINFO_EXTENSION);
				$bk_file = substr($local_file, 0, -strlen($extension)) . 'bk.' . $extension;
				file_exists($local_file) && rename($local_file, $bk_file);
			}

			// Replace ori img
			rename($local_file . '.tmp', $local_file);

			self::debug('Pulled optimized img: ' . $local_file);

			/**
			 * API Hook
			 * @since  2.9.5
			 * @since  3.0 $row_img has less elements now. Most useful ones are `post_id`/`src`
			 */
			do_action('litespeed_img_pull_ori', $row_img, $local_file);

			$total_pulled_ori++;
			// }

			// Save webp image
			$webp_size = 0;

			if (!empty($server_info['webp'])) {
				// Fetch
				$response = wp_remote_get($server_info['server'] . '/' . $server_info['webp'], array('timeout' => 60));
				if (is_wp_error($response)) {
					$error_message = $response->get_error_message();
					self::debug('failed to pull webp image: ' . $error_message);
					return;
				}

				if ($response['response']['code'] == 404) {
					$this->_step_back_image($row_img->id);

					$msg = __('Optimized WebP file expired and was cleared.', 'litespeed-cache');
					Admin_Display::error($msg);
					return;
				}

				file_put_contents($local_file . '.webp', $response['body']);

				if (!file_exists($local_file . '.webp') || !filesize($local_file . '.webp') || md5_file($local_file . '.webp') !== $server_info['webp_md5']) {
					self::debug('❌ Failed to pull optimized webp img: file md5 mismatch, server md5: ' . $server_info['webp_md5']);

					// Delete working table
					$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
					$wpdb->query($wpdb->prepare($q, $row_img->id));

					$msg = __('Pulled WebP image md5 does not match the notified WebP image md5.', 'litespeed-cache');
					Admin_Display::error($msg);
					return;
				}

				self::debug('Pulled optimized img WebP: ' . $local_file . '.webp');

				$webp_size = filesize($local_file . '.webp');

				/**
				 * API for WebP
				 * @since 2.9.5
				 * @since  3.0 $row_img less elements (see above one)
				 * @see #751737  - API docs for WEBP generation
				 */
				do_action('litespeed_img_pull_webp', $row_img, $local_file . '.webp');

				$total_pulled_webp++;
			}

			self::debug2('Remove _table_img_optming record [id] ' . $row_img->id);

			// Delete working table
			$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
			$wpdb->query($wpdb->prepare($q, $row_img->id));

			// Save server_list to notify taken
			if (empty($server_list[$server_info['server']])) {
				$server_list[$server_info['server']] = array();
			}

			$server_info_id = !empty($server_info['file_id']) ? $server_info['file_id'] : $server_info['id'];
			$server_list[$server_info['server']][] = $server_info_id;
		}

		// Notify IAPI images taken
		foreach ($server_list as $server => $img_list) {
			$data = array(
				'action'	=> self::CLOUD_ACTION_TAKEN,
				'list' 		=> $img_list,
				'server'	=> $server,
			);
			// TODO: improve this so we do not call once per server, but just once and then filter on the server side
			Cloud::post(Cloud::SVC_IMG_OPTM, $data);
		}

		if (empty($this->_summary['img_taken'])) {
			$this->_summary['img_taken'] = 0;
		}
		$this->_summary['img_taken'] += $total_pulled_ori + $total_pulled_webp;
		self::save_summary();

		// Manually running needs to roll back timestamp for next running
		if ($manual) {
			$this->_update_cron_running(true);
		}

		// $msg = sprintf(__('Pulled %d image(s)', 'litespeed-cache'), $total_pulled_ori + $total_pulled_webp);
		// Admin_Display::succeed($msg);

		// Check if there is still task in queue
		$q = "SELECT * FROM `$this->_table_img_optming` WHERE optm_status = %d LIMIT 1";
		$to_be_continued = $wpdb->get_row($wpdb->prepare($q, self::STATUS_NOTIFIED));
		if ($to_be_continued) {
			self::debug('Task in queue, to be continued...');
			return;
			// return Router::self_redirect(Router::ACTION_IMG_OPTM, self::TYPE_PULL);
		}

		// If all pulled, update tag to done
		self::debug('Marked pull status to all pulled');
		self::update_option(self::DB_NEED_PULL, self::STATUS_PULLED);
	}

	/**
	 * Push image back to previous status
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _step_back_image($id)
	{
		global $wpdb;

		// Reset the image to gathered status
		$q = "UPDATE `$this->_table_img_optming` SET optm_status = %d WHERE id = %d ";
		$wpdb->query($wpdb->prepare($q, array(self::STATUS_RAW, $id)));
	}

	/**
	 * Parse wp's meta value
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _parse_wp_meta_value($v)
	{
		if (empty($v)) {
			self::debug('bypassed parsing meta due to null value');
			return false;
		}

		if (!$v->meta_value) {
			self::debug('bypassed parsing meta due to no meta_value: pid ' . $v->post_id);
			return false;
		}

		$meta_value = @maybe_unserialize($v->meta_value);
		if (!is_array($meta_value)) {
			self::debug('bypassed parsing meta due to meta_value not json: pid ' . $v->post_id);
			return false;
		}

		if (empty($meta_value['file'])) {
			self::debug('bypassed parsing meta due to no ori file: pid ' . $v->post_id);
			return false;
		}

		return $meta_value;
	}

	/**
	 * Clean up all unfinished queue locally and to Cloud server
	 *
	 * @since 2.1.2
	 * @access public
	 */
	public function clean()
	{
		global $wpdb;

		// Reset img_optm table's queue
		if ($this->__data->tb_exist('img_optming')) {
			// Get min post id to mark
			$q = "SELECT MIN(post_id) FROM `$this->_table_img_optming`";
			$min_pid = $wpdb->get_var($q) - 1;
			if ($this->_summary['next_post_id'] > $min_pid) {
				$this->_summary['next_post_id'] = $min_pid;
				self::save_summary();
			}

			$q = "DELETE FROM `$this->_table_img_optming`";
			$wpdb->query($q);
		}

		$msg = __('Cleaned up unfinished data successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Destroy all optimized images
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _destroy()
	{
		global $wpdb;

		self::debug('excuting DESTROY process');

		$offset = !empty($_GET['litespeed_i']) ? $_GET['litespeed_i'] : 0;
		/**
		 * Limit images each time before redirection to fix Out of memory issue. #665465
		 * @since  2.9.8
		 */
		// Start deleting files
		$limit = apply_filters('litespeed_imgoptm_destroy_max_rows', 500);

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		$q = $wpdb->prepare($img_q, array($offset * $limit, $limit));
		$list = $wpdb->get_results($q);
		$i = 0;
		foreach ($list as $v) {
			if (!$v->post_id) continue;

			$meta_value = $this->_parse_wp_meta_value($v);
			if (!$meta_value) {
				continue;
			}

			$i++;

			$this->tmp_pid = $v->post_id;
			$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
			$this->_destroy_optm_file($meta_value, true);
			if (!empty($meta_value['sizes'])) {
				array_map(array($this, '_destroy_optm_file'), $meta_value['sizes']);
			}
		}

		self::debug('batch switched images total: ' . $i);

		$offset++;
		$to_be_continued = $wpdb->get_row($wpdb->prepare($img_q, array($offset * $limit, 1)));
		if ($to_be_continued) {
			return Router::self_redirect(Router::ACTION_IMG_OPTM, self::TYPE_DESTROY);
		}

		// Delete postmeta info
		$q = "DELETE FROM `$wpdb->postmeta` WHERE meta_key = %s";
		$wpdb->query($wpdb->prepare($q, self::DB_SIZE));
		$wpdb->query($wpdb->prepare($q, self::DB_SET));

		// Delete img_optm table
		$this->__data->tb_del('img_optm');
		$this->__data->tb_del('img_optming');

		// Clear options table summary info
		self::delete_option('_summary');
		self::delete_option(self::DB_NEED_PULL);

		$msg = __('Destroy all optimization data successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Destroy optm file
	 */
	private function _destroy_optm_file($meta_value, $is_ori_file = false)
	{
		$short_file_path = $meta_value['file'];
		if (!$is_ori_file) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		// del webp
		$this->__media->info($short_file_path . '.webp', $this->tmp_pid) && $this->__media->del($short_file_path . '.webp', $this->tmp_pid);
		$this->__media->info($short_file_path . '.optm.webp', $this->tmp_pid) && $this->__media->del($short_file_path . '.optm.webp', $this->tmp_pid);

		$extension = pathinfo($short_file_path, PATHINFO_EXTENSION);
		$local_filename = substr($short_file_path, 0, -strlen($extension) - 1);
		$bk_file = $local_filename . '.bk.' . $extension;
		$bk_optm_file = $local_filename . '.bk.optm.' . $extension;

		// del optimized ori
		if ($this->__media->info($bk_file, $this->tmp_pid)) {
			$this->__media->del($short_file_path, $this->tmp_pid);
			$this->__media->rename($bk_file, $short_file_path, $this->tmp_pid);
		}
		$this->__media->info($bk_optm_file, $this->tmp_pid) && $this->__media->del($bk_optm_file, $this->tmp_pid);
	}

	/**
	 * Rescan to find new generated images
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _rescan()
	{
		global $wpdb;
		exit('tobedone');

		$offset = !empty($_GET['litespeed_i']) ? $_GET['litespeed_i'] : 0;
		$limit = 500;

		self::debug('rescan images');

		// Get images
		$q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a, `$wpdb->postmeta` b
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
				AND a.ID = b.post_id
				AND b.meta_key = '_wp_attachment_metadata'
			ORDER BY a.ID
			LIMIT %d, %d
			";
		$list = $wpdb->get_results($wpdb->prepare($q, $offset * $limit, $limit + 1)); // last one is the seed for next batch

		if (!$list) {
			$msg = __('Rescanned successfully.', 'litespeed-cache');
			Admin_Display::succeed($msg);

			self::debug('rescan bypass: no gathered image found');
			return;
		}

		if (count($list) == $limit + 1) {
			$to_be_continued = true;
			array_pop($list); // last one is the seed for next round, discard here.
		} else {
			$to_be_continued = false;
		}

		// Prepare post_ids to inquery gathered images
		$pid_set = array();
		$scanned_list = array();
		foreach ($list as $v) {
			$meta_value = $this->_parse_wp_meta_value($v);
			if (!$meta_value) {
				continue;
			}

			$scanned_list[] = array(
				'pid' => $v->post_id,
				'meta' => $meta_value,
			);

			$pid_set[] = $v->post_id;
		}

		// Build gathered images
		$q = "SELECT src, post_id FROM `$this->_table_img_optm` WHERE post_id IN (" . implode(',', array_fill(0, count($pid_set), '%d')) . ")";
		$list = $wpdb->get_results($wpdb->prepare($q, $pid_set));
		foreach ($list as $v) {
			$this->_existed_src_list[] = $v->post_id . '.' . $v->src;
		}

		// Find new images
		foreach ($scanned_list as $v) {
			$meta_value = $v['meta'];
			// Parse all child src and put them into $this->_img_in_queue, missing ones to $this->_img_in_queue_missed
			$this->tmp_pid = $v['pid'];
			$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
			$this->_append_img_queue($meta_value, true);
			if (!empty($meta_value['sizes'])) {
				array_map(array($this, '_append_img_queue'), $meta_value['sizes']);
			}
		}

		self::debug('rescaned [img] ' . count($this->_img_in_queue));

		$count = count($this->_img_in_queue);
		if ($count > 0) {
			// Save to DB
			$this->_save_raw();
		}

		if ($to_be_continued) {
			return Router::self_redirect(Router::ACTION_IMG_OPTM, self::TYPE_RESCAN);
		}

		$msg = $count ? sprintf(__('Rescanned %d images successfully.', 'litespeed-cache'), $count) : __('Rescanned successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Calculate bkup original images storage
	 *
	 * @since 2.2.6
	 * @access private
	 */
	private function _calc_bkup()
	{
		global $wpdb;

		$offset = !empty($_GET['litespeed_i']) ? $_GET['litespeed_i'] : 0;
		$limit = 500;

		if (!$offset) {
			$this->_summary['bk_summary'] = array(
				'date' => time(),
				'count' => 0,
				'sum' => 0,
			);
		}

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		$q = $wpdb->prepare($img_q, array($offset * $limit, $limit));
		$list = $wpdb->get_results($q);
		foreach ($list as $v) {
			if (!$v->post_id) continue;

			$meta_value = $this->_parse_wp_meta_value($v);
			if (!$meta_value) {
				continue;
			}

			$this->tmp_pid = $v->post_id;
			$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
			$this->_get_bk_size($meta_value, true);
			if (!empty($meta_value['sizes'])) {
				array_map(array($this, '_get_bk_size'), $meta_value['sizes']);
			}
		}

		$this->_summary['bk_summary']['date'] = time();
		self::save_summary();

		self::debug('_calc_bkup total: ' . $this->_summary['bk_summary']['count'] . ' [size] ' . $this->_summary['bk_summary']['sum']);

		$offset++;
		$to_be_continued = $wpdb->get_row($wpdb->prepare($img_q, array($offset * $limit, 1)));

		if ($to_be_continued) {
			return Router::self_redirect(Router::ACTION_IMG_OPTM, self::TYPE_CALC_BKUP);
		}

		$msg = __('Calculated backups successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Calculate single size
	 */
	private function _get_bk_size($meta_value, $is_ori_file = false)
	{
		$short_file_path = $meta_value['file'];
		if (!$is_ori_file) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		$extension = pathinfo($short_file_path, PATHINFO_EXTENSION);
		$local_filename = substr($short_file_path, 0, -strlen($extension) - 1);
		$bk_file = $local_filename . '.bk.' . $extension;

		$img_info = $this->__media->info($bk_file, $this->tmp_pid);
		if (!$img_info) {
			return;
		}

		$this->_summary['bk_summary']['count']++;
		$this->_summary['bk_summary']['sum'] += $img_info['size'];
	}

	/**
	 * Delete bkup original images storage
	 *
	 * @since  2.5
	 * @access public
	 */
	public function rm_bkup()
	{
		global $wpdb;

		if (!$this->__data->tb_exist('img_optming')) {
			return;
		}

		$offset = !empty($_GET['litespeed_i']) ? $_GET['litespeed_i'] : 0;
		$limit = 500;

		if (empty($this->_summary['rmbk_summary'])) {
			$this->_summary['rmbk_summary'] = array(
				'date' => time(),
				'count' => 0,
				'sum' => 0,
			);
		}

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		$q = $wpdb->prepare($img_q, array($offset * $limit, $limit));
		$list = $wpdb->get_results($q);
		foreach ($list as $v) {
			if (!$v->post_id) continue;

			$meta_value = $this->_parse_wp_meta_value($v);
			if (!$meta_value) {
				continue;
			}

			$this->tmp_pid = $v->post_id;
			$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
			$this->_del_bk_file($meta_value, true);
			if (!empty($meta_value['sizes'])) {
				array_map(array($this, '_del_bk_file'), $meta_value['sizes']);
			}
		}

		$this->_summary['rmbk_summary']['date'] = time();
		self::save_summary();

		self::debug('rm_bkup total: ' . $this->_summary['rmbk_summary']['count'] . ' [size] ' . $this->_summary['rmbk_summary']['sum']);

		$offset++;
		$to_be_continued = $wpdb->get_row($wpdb->prepare($img_q, array($offset * $limit, 1)));

		if ($to_be_continued) {
			return Router::self_redirect(Router::ACTION_IMG_OPTM, self::TYPE_RM_BKUP);
		}

		$msg = __('Removed backups successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Delete single file
	 */
	private function _del_bk_file($meta_value, $is_ori_file = false)
	{
		$short_file_path = $meta_value['file'];
		if (!$is_ori_file) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		$extension = pathinfo($short_file_path, PATHINFO_EXTENSION);
		$local_filename = substr($short_file_path, 0, -strlen($extension) - 1);
		$bk_file = $local_filename . '.bk.' . $extension;

		$img_info = $this->__media->info($bk_file, $this->tmp_pid);
		if (!$img_info) {
			return;
		}

		$this->_summary['rmbk_summary']['count']++;
		$this->_summary['rmbk_summary']['sum'] += $img_info['size'];

		$this->__media->del($bk_file, $this->tmp_pid);
	}

	/**
	 * Count images
	 *
	 * @since 1.6
	 * @access public
	 */
	public function img_count()
	{
		global $wpdb;

		$q = "SELECT count(*)
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			";
		$groups_all = $wpdb->get_var($q);
		$groups_new = $wpdb->get_var($q . ' AND ID>' . (int)$this->_summary['next_post_id'] . ' ORDER BY ID');
		$groups_done = $wpdb->get_var($q . ' AND ID<' . (int)$this->_summary['next_post_id'] . ' ORDER BY ID');

		$q = "SELECT b.post_id
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID DESC
			LIMIT 1
			";
		$max_id = $wpdb->get_var($q);

		$count_list = array(
			'max_id'	=> $max_id,
			'groups_all'	=> $groups_all,
			'groups_new'	=> $groups_new,
			'groups_done'	=> $groups_done,
		);

		// images count from work table
		if ($this->__data->tb_exist('img_optming')) {
			$q = "SELECT COUNT(DISTINCT post_id),COUNT(*) FROM `$this->_table_img_optming` WHERE optm_status = %d";
			$groups_to_check = array(
				self::STATUS_RAW,
				self::STATUS_REQUESTED,
				self::STATUS_NOTIFIED,
				self::STATUS_ERR_FETCH,
			);
			foreach ($groups_to_check as $v) {
				$count_list['img.' . $v] = $count_list['group.' . $v] = 0;
				list($count_list['group.' . $v], $count_list['img.' . $v]) = $wpdb->get_row($wpdb->prepare($q, $v), ARRAY_N);
			}
		}

		return $count_list;
	}

	/**
	 * Check if fetch cron is running
	 *
	 * @since  1.6.2
	 * @access public
	 */
	public function cron_running($bool_res = true)
	{
		$last_run = !empty($this->_summary['last_pull']) ? $this->_summary['last_pull'] : 0;

		$is_running = $last_run && time() - $last_run < 120;

		if ($bool_res) {
			return $is_running;
		}

		return array($last_run, $is_running);
	}

	/**
	 * Update fetch cron timestamp tag
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _update_cron_running($done = false)
	{
		$this->_summary['last_pull'] = time();

		if ($done) {
			// Only update cron tag when its from the active running cron
			if ($this->_cron_ran) {
				// Rollback for next running
				$this->_summary['last_pull'] -= 120;
			} else {
				return;
			}
		}

		self::save_summary();

		$this->_cron_ran = true;
	}

	/**
	 * Batch switch images to ori/optm version
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _batch_switch($type)
	{
		global $wpdb;

		$offset = !empty($_GET['litespeed_i']) ? $_GET['litespeed_i'] : 0;
		$limit = 500;
		$this->tmp_type = $type;

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		$q = $wpdb->prepare($img_q, array($offset * $limit, $limit));
		$list = $wpdb->get_results($q);
		$i = 0;
		foreach ($list as $v) {
			if (!$v->post_id) continue;

			$meta_value = $this->_parse_wp_meta_value($v);
			if (!$meta_value) {
				continue;
			}

			$i++;

			$this->tmp_pid = $v->post_id;
			$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
			$this->_switch_bk_file($meta_value, true);
			if (!empty($meta_value['sizes'])) {
				array_map(array($this, '_switch_bk_file'), $meta_value['sizes']);
			}
		}

		self::debug('batch switched images total: ' . $i);

		$offset++;
		$to_be_continued = $wpdb->get_row($wpdb->prepare($img_q, array($offset * $limit, 1)));
		if ($to_be_continued) {
			return Router::self_redirect(Router::ACTION_IMG_OPTM, $type);
		}

		$msg = __('Switched images successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Delete single file
	 */
	private function _switch_bk_file($meta_value, $is_ori_file = false)
	{
		$short_file_path = $meta_value['file'];
		if (!$is_ori_file) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		$extension = pathinfo($short_file_path, PATHINFO_EXTENSION);
		$local_filename = substr($short_file_path, 0, -strlen($extension) - 1);
		$bk_file = $local_filename . '.bk.' . $extension;
		$bk_optm_file = $local_filename . '.bk.optm.' . $extension;

		// switch to ori
		if ($this->tmp_type === self::TYPE_BATCH_SWITCH_ORI) {
			if (!$this->__media->info($bk_file, $this->tmp_pid)) {
				return;
			}
			$this->__media->rename($local_filename . '.' . $extension, $bk_optm_file, $this->tmp_pid);
			$this->__media->rename($bk_file, $local_filename . '.' . $extension, $this->tmp_pid);
		}
		// switch to optm
		elseif ($this->tmp_type === self::TYPE_BATCH_SWITCH_OPTM) {
			if (!$this->__media->info($bk_optm_file, $this->tmp_pid)) {
				return;
			}
			$this->__media->rename($local_filename . '.' . $extension, $bk_file, $this->tmp_pid);
			$this->__media->rename($bk_optm_file, $local_filename . '.' . $extension, $this->tmp_pid);
		}
	}

	/**
	 * Switch image between original one and optimized one
	 *
	 * @since 1.6.2
	 * @access private
	 */
	private function _switch_optm_file($type)
	{
		Admin_Display::succeed(__('Switched to optimized file successfully.', 'litespeed-cache'));
		return;
		global $wpdb;

		$pid = substr($type, 4);
		$switch_type = substr($type, 0, 4);

		$q = "SELECT src,post_id FROM `$this->_table_img_optm` WHERE post_id = %d AND optm_status = %d";
		$list = $wpdb->get_results($wpdb->prepare($q, array($pid, self::STATUS_PULLED)));

		$msg = 'Unknown Msg';

		foreach ($list as $v) {
			// to switch webp file
			if ($switch_type === 'webp') {
				if ($this->__media->info($v->src . '.webp', $v->post_id)) {
					$this->__media->rename($v->src . '.webp', $v->src . '.optm.webp', $v->post_id);
					self::debug('Disabled WebP: ' . $v->src);

					$msg = __('Disabled WebP file successfully.', 'litespeed-cache');
				} elseif ($this->__media->info($v->src . '.optm.webp', $v->post_id)) {
					$this->__media->rename($v->src . '.optm.webp', $v->src . '.webp', $v->post_id);
					self::debug('Enable WebP: ' . $v->src);

					$msg = __('Enabled WebP file successfully.', 'litespeed-cache');
				}
			}
			// to switch original file
			else {
				$extension = pathinfo($v->src, PATHINFO_EXTENSION);
				$local_filename = substr($v->src, 0, -strlen($extension) - 1);
				$bk_file = $local_filename . '.bk.' . $extension;
				$bk_optm_file = $local_filename . '.bk.optm.' . $extension;

				// revert ori back
				if ($this->__media->info($bk_file, $v->post_id)) {
					$this->__media->rename($v->src, $bk_optm_file, $v->post_id);
					$this->__media->rename($bk_file, $v->src, $v->post_id);
					self::debug('Restore original img: ' . $bk_file);

					$msg = __('Restored original file successfully.', 'litespeed-cache');
				} elseif ($this->__media->info($bk_optm_file, $v->post_id)) {
					$this->__media->rename($v->src, $bk_file, $v->post_id);
					$this->__media->rename($bk_optm_file, $v->src, $v->post_id);
					self::debug('Switch to optm img: ' . $v->src);

					$msg = __('Switched to optimized file successfully.', 'litespeed-cache');
				}
			}
		}

		Admin_Display::succeed($msg);
	}

	/**
	 * Delete one optm data and recover original file
	 *
	 * @since 2.4.2
	 * @access public
	 */
	public function reset_row($post_id)
	{
		global $wpdb;

		if (!$post_id) {
			return;
		}

		// Gathered image don't have DB_SIZE info yet
		// $size_meta = get_post_meta( $post_id, self::DB_SIZE, true );

		// if ( ! $size_meta ) {
		// 	return;
		// }

		self::debug('_reset_row [pid] ' . $post_id);

		# TODO: Load image sub files
		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->postmeta` b
			WHERE b.post_id =%d  AND b.meta_key = '_wp_attachment_metadata'";
		$q = $wpdb->prepare($img_q, array($post_id));
		$v = $wpdb->get_row($q);

		$meta_value = $this->_parse_wp_meta_value($v);
		if ($meta_value) {
			$this->tmp_pid = $v->post_id;
			$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
			$this->_destroy_optm_file($meta_value, true);
			if (!empty($meta_value['sizes'])) {
				array_map(array($this, '_destroy_optm_file'), $meta_value['sizes']);
			}
		}

		delete_post_meta($post_id, self::DB_SIZE);
		delete_post_meta($post_id, self::DB_SET);

		$msg = __('Reset the optimized data successfully.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Show an image's optm status
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public function check_img()
	{
		global $wpdb;

		$pid = $_POST['data'];

		self::debug('Check image [ID] ' . $pid);

		$data = array();

		$data['img_count'] = $this->img_count();
		$data['optm_summary'] = self::get_summary();

		$data['_wp_attached_file'] = get_post_meta($pid, '_wp_attached_file', true);
		$data['_wp_attachment_metadata'] = get_post_meta($pid, '_wp_attachment_metadata', true);

		// Get img_optm data
		$q = "SELECT * FROM `$this->_table_img_optm` WHERE post_id = %d";
		$list = $wpdb->get_results($wpdb->prepare($q, $pid));
		$img_data = array();
		if ($list) {
			foreach ($list as $v) {
				$img_data[] = array(
					'id'	=> $v->id,
					'optm_status'	=> $v->optm_status,
					'src'	=> $v->src,
					'srcpath_md5'	=> $v->srcpath_md5,
					'src_md5'	=> $v->src_md5,
					'server_info'	=> $v->server_info,
				);
			}
		}
		$data['img_data'] = $img_data;

		return array('_res' => 'ok', 'data' => $data);
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.0
	 * @access public
	 */
	public function handler()
	{
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_RESET_ROW:
				$this->reset_row(!empty($_GET['id']) ? $_GET['id'] : false);
				break;

			case self::TYPE_CALC_BKUP:
				$this->_calc_bkup();
				break;

			case self::TYPE_RM_BKUP:
				$this->rm_bkup();
				break;

			case self::TYPE_NEW_REQ:
				$this->new_req();
				break;

			case self::TYPE_RESCAN:
				$this->_rescan();
				break;

			case self::TYPE_DESTROY:
				$this->_destroy();
				break;

			case self::TYPE_CLEAN:
				$this->clean();
				break;

			case self::TYPE_PULL:
				self::start_async();
				break;

				/**
				 * Batch switch
				 * @since 1.6.3
				 */
			case self::TYPE_BATCH_SWITCH_ORI:
			case self::TYPE_BATCH_SWITCH_OPTM:
				$this->_batch_switch($type);
				break;

			case substr($type, 0, 4) === 'webp':
			case substr($type, 0, 4) === 'orig':
				$this->_switch_optm_file($type);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
