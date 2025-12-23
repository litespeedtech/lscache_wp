<?php
// phpcs:ignoreFile

/**
 * Image optimization send trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined('WPINC') || exit();

/**
 * Trait Img_Optm_Send
 *
 * Handles image optimization request sending.
 */
trait Img_Optm_Send {

	/**
	 * Gather images auto when update attachment meta
	 * This is to optimize new uploaded images first. Stored in img_optm table.
	 * Later normal process will auto remove these records when trying to optimize these images again
	 *
	 * @since  4.0
	 */
	public function wp_update_attachment_metadata( $meta_value, $post_id ) {
		global $wpdb;

		self::debug2('🖌️ Auto update attachment meta [id] ' . $post_id);
		if (empty($meta_value['file'])) {
			return;
		}

		// Load gathered images
		if (!$this->_existed_src_list) {
			// To aavoid extra query when recalling this function
			self::debug('SELECT src from img_optm table');
			if ($this->__data->tb_exist('img_optm')) {
				$q    = "SELECT src FROM `$this->_table_img_optm` WHERE post_id = %d";
				$list = $wpdb->get_results($wpdb->prepare($q, $post_id));
				foreach ($list as $v) {
					$this->_existed_src_list[] = $post_id . '.' . $v->src;
				}
			}
			if ($this->__data->tb_exist('img_optming')) {
				$q    = "SELECT src FROM `$this->_table_img_optming` WHERE post_id = %d";
				$list = $wpdb->get_results($wpdb->prepare($q, $post_id));
				foreach ($list as $v) {
					$this->_existed_src_list[] = $post_id . '.' . $v->src;
				}
			} else {
				$this->__data->tb_create('img_optming');
			}
		}

		// Prepare images
		$this->tmp_pid  = $post_id;
		$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
		$this->_append_img_queue($meta_value, true);
		if (!empty($meta_value['sizes'])) {
			foreach( $meta_value['sizes'] as $img_size_name => $img_size ){
				$this->_append_img_queue($img_size, false, $img_size_name );
			}
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
	public static function cron_auto_request() {
		if (!wp_doing_cron()) {
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
	public function wet_limit() {
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
	public function new_req() {
		global $wpdb;

		// check if is running
		if (!empty($this->_summary['is_running']) && time() - $this->_summary['is_running'] < apply_filters('litespeed_imgoptm_new_req_interval', 3600)) {
			self::debug('The previous req was in 3600s.');
			return;
		}
		$this->_summary['is_running'] = time();
		self::save_summary();

		// Check if has credit to push
		$err       = false;
		$allowance = Cloud::cls()->allowance(Cloud::SVC_IMG_OPTM, $err);

		$wet_limit = $this->wet_limit();

		self::debug("allowance_max $allowance wet_limit $wet_limit");
		if ($wet_limit && $wet_limit < $allowance) {
			$allowance = $wet_limit;
		}

		if (!$allowance) {
			self::debug('❌ No credit');
			Admin_Display::error(Error::msg($err));
			$this->_finished_running();
			return;
		}

		self::debug('preparing images to push');

		$this->__data->tb_create('img_optming');

		$q               = "SELECT COUNT(1) FROM `$this->_table_img_optming` WHERE optm_status = %d";
		$q               = $wpdb->prepare($q, array( self::STATUS_REQUESTED ));
		$total_requested = $wpdb->get_var($q);
		$max_requested   = $allowance * 1;

		if ($total_requested > $max_requested) {
			self::debug('❌ Too many queued images (' . $total_requested . ' > ' . $max_requested . ')');
			Admin_Display::error(Error::msg('too_many_requested'));
			$this->_finished_running();
			return;
		}

		$allowance -= $total_requested;

		if ($allowance < 1) {
			self::debug('❌ Too many requested images ' . $total_requested);
			Admin_Display::error(Error::msg('too_many_requested'));
			$this->_finished_running();
			return;
		}

		// Limit maximum number of items waiting to be pulled
		$q              = "SELECT COUNT(1) FROM `$this->_table_img_optming` WHERE optm_status = %d";
		$q              = $wpdb->prepare($q, array( self::STATUS_NOTIFIED ));
		$total_notified = $wpdb->get_var($q);
		if ($total_notified > 0) {
			self::debug('❌ Too many notified images (' . $total_notified . ')');
			Admin_Display::error(Error::msg('too_many_notified'));
			$this->_finished_running();
			return;
		}

		$q         = "SELECT COUNT(1) FROM `$this->_table_img_optming` WHERE optm_status IN (%d, %d)";
		$q         = $wpdb->prepare($q, array( self::STATUS_NEW, self::STATUS_RAW ));
		$total_new = $wpdb->get_var($q);
		// $allowance -= $total_new;

		// May need to get more images
		$list = [];
		$more = $allowance - $total_new;
		if ($more > 0) {
			$q    = "SELECT b.post_id, b.meta_value
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
			$q    = $wpdb->prepare($q, array( $this->_summary['next_post_id'], $more ));
			$list = $wpdb->get_results($q);
			foreach ($list as $v) {
				if (!$v->post_id) {
					continue;
				}

				$this->_summary['next_post_id'] = $v->post_id;

				$meta_value = $this->_parse_wp_meta_value($v);
				if (!$meta_value) {
					continue;
				}
				$meta_value['file'] = wp_normalize_path($meta_value['file']);
				$basedir            = $this->wp_upload_dir['basedir'] . '/';
				if (strpos($meta_value['file'], $basedir) === 0) {
					$meta_value['file'] = substr($meta_value['file'], strlen($basedir));
				}

				$this->tmp_pid  = $v->post_id;
				$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
				$this->_append_img_queue($meta_value, true);
				if (!empty($meta_value['sizes'])) {
					foreach( $meta_value['sizes'] as $img_size_name => $img_size ){
						$this->_append_img_queue($img_size, false, $img_size_name );
					}
				}
			}

			self::save_summary();

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
		$accepted_imgs = $this->_send_request($allowance);

		$this->_finished_running();
		if (!$accepted_imgs) {
			return;
		}

		$placeholder1 = Admin_Display::print_plural($accepted_imgs[0], 'image');
		$placeholder2 = Admin_Display::print_plural($accepted_imgs[1], 'image');
		$msg          = sprintf(__('Pushed %1$s to Cloud server, accepted %2$s.', 'litespeed-cache'), $placeholder1, $placeholder2);
		Admin_Display::success($msg);
	}

	/**
	 * Set running to done
	 */
	private function _finished_running() {
		$this->_summary['is_running'] = 0;
		self::save_summary();
	}

	/**
	 * Add a new img to queue which will be pushed to request
	 *
	 * @since 1.6
	 * @since 7.5 Allow to choose which image sizes should be optimized + added parameter $img_size_name.
	 * @access private
	 */
	private function _append_img_queue( $meta_value, $is_ori_file = false, $img_size_name = false ) {
		if (empty($meta_value['file']) || empty($meta_value['width']) || empty($meta_value['height'])) {
			self::debug2('bypass image due to lack of file/w/h: pid ' . $this->tmp_pid, $meta_value);
			return;
		}

		$short_file_path = $meta_value['file'];

		// Test if need to skip image size.
		if (!$is_ori_file) {
			$short_file_path = $this->tmp_path . $short_file_path;
			$skip = false !== array_search( $img_size_name, $this->_sizes_skipped, true );
			if($skip){
				self::debug2( 'bypass image ' . $short_file_path . ' due to skipped size: ' . $img_size_name );
				return;
			}
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

		$extension = pathinfo($short_file_path, PATHINFO_EXTENSION);
		if (!$_img_info || !in_array($extension, array( 'jpg', 'jpeg', 'png', 'gif' ))) {
			self::debug2('bypass image due to file not exist: pid ' . $this->tmp_pid . ' ' . $short_file_path);
			return;
		}

		// Check if optimized file exists or not
		$target_needed = false;
		if ($this->_format) {
			$target_file_path = $short_file_path . '.' . $this->_format;
			if (!$this->__media->info($target_file_path, $this->tmp_pid)) {
				$target_needed = true;
			}
		}
		if ($this->conf(self::O_IMG_OPTM_ORI)) {
			$target_file_path = substr($short_file_path, 0, -strlen($extension)) . 'bk.' . $extension;
			if (!$this->__media->info($target_file_path, $this->tmp_pid)) {
				$target_needed = true;
			}
		}
		if (!$target_needed) {
			self::debug2('bypass image due to optimized file exists: pid ' . $this->tmp_pid . ' ' . $short_file_path);
			return;
		}

		// Debug2::debug2( '[Img_Optm] adding image: pid ' . $this->tmp_pid );

		$this->_img_in_queue[] = array(
			'pid' => $this->tmp_pid,
			'md5' => $_img_info['md5'],
			'url' => $_img_info['url'],
			'src' => $short_file_path, // not needed in LiteSpeed IAPI, just leave for local storage after post
			'mime_type' => !empty($meta_value['mime-type']) ? $meta_value['mime-type'] : '',
		);
	}

	/**
	 * Save gathered image raw data
	 *
	 * @since  3.0
	 */
	private function _save_raw() {
		if (empty($this->_img_in_queue)) {
			return;
		}
		$data     = [];
		$pid_list = [];
		foreach ($this->_img_in_queue as $k => $v) {
			$_img_info = $this->__media->info($v['src'], $v['pid']);

			// attachment doesn't exist, delete the record
			if (empty($_img_info['url']) || empty($_img_info['md5'])) {
				unset($this->_img_in_queue[$k]);
				continue;
			}
			$pid_list[] = (int) $v['pid'];

			$data[] = $v['pid'];
			$data[] = self::STATUS_RAW;
			$data[] = $v['src'];
		}

		global $wpdb;
		$fields = 'post_id, optm_status, src';
		$q      = "INSERT INTO `$this->_table_img_optming` ( $fields ) VALUES ";

		// Add placeholder
		$q .= Utility::chunk_placeholder($data, $fields);

		// Store data
		$wpdb->query($wpdb->prepare($q, $data));

		$count = count($this->_img_in_queue);
		self::debug('Added raw images [total] ' . $count);

		$this->_img_in_queue = [];

		// Save thumbnail groups for future rescan index
		$this->_gen_thumbnail_set();

		$pid_list = array_unique($pid_list);
		self::debug('pid list to append to postmeta', $pid_list);
		$pid_list        = array_diff($pid_list, $this->_pids_set);
		$this->_pids_set = array_merge($this->_pids_set, $pid_list);

		$existed_meta = $wpdb->get_results("SELECT * FROM `$wpdb->postmeta` WHERE post_id IN ('" . implode("','", $pid_list) . "') AND meta_key='" . self::DB_SET . "'");
		$existed_pid  = [];
		if ($existed_meta) {
			foreach ($existed_meta as $v) {
				$existed_pid[] = $v->post_id;
			}
			self::debug('pid list to update postmeta', $existed_pid);
			$wpdb->query(
				$wpdb->prepare("UPDATE `$wpdb->postmeta` SET meta_value=%s WHERE post_id IN ('" . implode("','", $existed_pid) . "') AND meta_key=%s", array(
					$this->_thumbnail_set,
					self::DB_SET,
				))
			);
		}

		// Add new meta
		$new_pids = $existed_pid ? array_diff($pid_list, $existed_pid) : $pid_list;
		if ($new_pids) {
			self::debug('pid list to update postmeta', $new_pids);
			foreach ($new_pids as $v) {
				self::debug('New group set info [pid] ' . $v);
				$q = "INSERT INTO `$wpdb->postmeta` (post_id, meta_key, meta_value) VALUES (%d, %s, %s)";
				$wpdb->query($wpdb->prepare($q, array( $v, self::DB_SET, $this->_thumbnail_set )));
			}
		}
	}

	/**
	 * Generate thumbnail sets of current image group
	 *
	 * @since 5.4
	 */
	private function _gen_thumbnail_set() {
		if ($this->_thumbnail_set) {
			return;
		}
		$set = [];
		foreach (Media::cls()->get_image_sizes() as $size) {
			$curr_size = $size['width'] . 'x' . $size['height'];
			if (in_array($curr_size, $set)) {
				continue;
			}
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
	private function _filter_duplicated_src() {
		global $wpdb;

		$srcpath_list = [];

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
	private function _filter_legacy_src() {
		global $wpdb;

		if (!$this->__data->tb_exist('img_optm')) {
			return;
		}

		if (!$this->_img_in_queue) {
			return;
		}

		$finished_ids = [];

		Utility::compatibility();
		$post_ids = array_unique(array_column($this->_img_in_queue, 'pid'));
		$list     = $wpdb->get_results("SELECT post_id FROM `$this->_table_img_optm` WHERE post_id in (" . implode(',', $post_ids) . ') GROUP BY post_id');
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
		$wpdb->query("DELETE FROM `$this->_table_img_optm` WHERE post_id in (" . implode(',', $post_ids) . ')');
	}

	/**
	 * Filter the invalid src before sending
	 *
	 * @since 3.0.8.3
	 * @access private
	 */
	private function _filter_invalid_src() {
		$img_in_queue_invalid = [];
		foreach ($this->_img_in_queue as $k => $v) {
			if ($v['src']) {
				$extension = pathinfo($v['src'], PATHINFO_EXTENSION);
			}
			if (!$v['src'] || empty($extension) || !in_array($extension, array( 'jpg', 'jpeg', 'png', 'gif' ))) {
				$img_in_queue_invalid[] = $v['id'];
				unset($this->_img_in_queue[$k]);
				continue;
			}
		}

		if (!$img_in_queue_invalid) {
			return;
		}

		$count = count($img_in_queue_invalid);
		$msg   = sprintf(__('Cleared %1$s invalid images.', 'litespeed-cache'), $count);
		Admin_Display::success($msg);

		self::debug('Found invalid src [total] ' . $count);
	}

	/**
	 * Push img request to Cloud server
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _send_request( $allowance ) {
		global $wpdb;

		$q             = "SELECT id, src, post_id FROM `$this->_table_img_optming` WHERE optm_status=%d LIMIT %d";
		$q             = $wpdb->prepare($q, array( self::STATUS_RAW, $allowance ));
		$_img_in_queue = $wpdb->get_results($q);
		if (!$_img_in_queue) {
			return;
		}

		self::debug('Load img in queue [total] ' . count($_img_in_queue));

		$list = [];
		foreach ($_img_in_queue as $v) {
			$_img_info = $this->__media->info($v->src, $v->post_id);
			// If record is invalid, remove from img_optming table
			if (empty($_img_info['url']) || empty($_img_info['md5'])) {
				$wpdb->query($wpdb->prepare("DELETE FROM `$this->_table_img_optming` WHERE id=%d", $v->id));
				continue;
			}

			$img = array(
				'id' => $v->id,
				'url' => $_img_info['url'],
				'md5' => $_img_info['md5'],
			);
			// Build the needed image types for request as we now support soft reset counter
			if ($this->_format) {
				$target_file_path = $v->src . '.' . $this->_format;
				if ($this->__media->info($target_file_path, $v->post_id)) {
					$img['optm_' . $this->_format] = 0;
				}
			}
			if ($this->conf(self::O_IMG_OPTM_ORI)) {
				$extension        = pathinfo($v->src, PATHINFO_EXTENSION);
				$target_file_path = substr($v->src, 0, -strlen($extension)) . 'bk.' . $extension;
				if ($this->__media->info($target_file_path, $v->post_id)) {
					$img['optm_ori'] = 0;
				}
			}

			$list[] = $img;
		}

		if (!$list) {
			$msg = __('No valid image found in the current request.', 'litespeed-cache');
			Admin_Display::error($msg);
			return;
		}

		$data = array(
			'action' => self::CLOUD_ACTION_NEW_REQ,
			'list' => \json_encode($list),
			'optm_ori' => $this->conf(self::O_IMG_OPTM_ORI) ? 1 : 0,
			'optm_lossless' => $this->conf(self::O_IMG_OPTM_LOSSLESS) ? 1 : 0,
			'keep_exif' => $this->conf(self::O_IMG_OPTM_EXIF) ? 1 : 0,
		);
		if ($this->_format) {
			$data['optm_' . $this->_format] = 1;
		}

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

		return array( count($list), count($json['ids']) );
	}

	/**
	 * Parse wp's meta value
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _parse_wp_meta_value( $v ) {
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
}
