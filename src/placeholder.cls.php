<?php

/**
 * The PlaceHolder class
 *
 * @since 		3.0
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Placeholder extends Base
{
	const TYPE_GENERATE = 'generate';
	const TYPE_CLEAR_Q = 'clear_q';

	private $_conf_placeholder_resp;
	private $_conf_placeholder_resp_svg;
	private $_conf_lqip;
	private $_conf_lqip_qual;
	private $_conf_lqip_min_w;
	private $_conf_lqip_min_h;
	private $_conf_placeholder_resp_color;
	private $_conf_placeholder_resp_async;
	private $_conf_ph_default;
	private $_placeholder_resp_dict = array();
	private $_ph_queue = array();

	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		$this->_conf_placeholder_resp = defined('LITESPEED_GUEST_OPTM') || $this->conf(self::O_MEDIA_PLACEHOLDER_RESP);
		$this->_conf_placeholder_resp_svg = $this->conf(self::O_MEDIA_PLACEHOLDER_RESP_SVG);
		$this->_conf_lqip = !defined('LITESPEED_GUEST_OPTM') && $this->conf(self::O_MEDIA_LQIP);
		$this->_conf_lqip_qual = $this->conf(self::O_MEDIA_LQIP_QUAL);
		$this->_conf_lqip_min_w = $this->conf(self::O_MEDIA_LQIP_MIN_W);
		$this->_conf_lqip_min_h = $this->conf(self::O_MEDIA_LQIP_MIN_H);
		$this->_conf_placeholder_resp_async = $this->conf(self::O_MEDIA_PLACEHOLDER_RESP_ASYNC);
		$this->_conf_placeholder_resp_color = $this->conf(self::O_MEDIA_PLACEHOLDER_RESP_COLOR);
		$this->_conf_ph_default = $this->conf(self::O_MEDIA_LAZY_PLACEHOLDER) ?: LITESPEED_PLACEHOLDER;

		$this->_summary = self::get_summary();
	}

	/**
	 * Init Placeholder
	 */
	public function init()
	{
		Debug2::debug2('[LQIP] init');

		add_action('litspeed_after_admin_init', array($this, 'after_admin_init'));
	}

	/**
	 * Display column in Media
	 *
	 * @since  3.0
	 * @access public
	 */
	public function after_admin_init()
	{
		if ($this->_conf_lqip) {
			add_filter('manage_media_columns', array($this, 'media_row_title'));
			add_filter('manage_media_custom_column', array($this, 'media_row_actions'), 10, 2);
			add_action('litespeed_media_row_lqip', array($this, 'media_row_con'));
		}
	}

	/**
	 * Media Admin Menu -> LQIP col
	 *
	 * @since 3.0
	 * @access public
	 */
	public function media_row_title($posts_columns)
	{
		$posts_columns['lqip'] = __('LQIP', 'litespeed-cache');

		return $posts_columns;
	}

	/**
	 * Media Admin Menu -> LQIP Column
	 *
	 * @since 3.0
	 * @access public
	 */
	public function media_row_actions($column_name, $post_id)
	{
		if ($column_name !== 'lqip') {
			return;
		}

		do_action('litespeed_media_row_lqip', $post_id);
	}

	/**
	 * Display LQIP column
	 *
	 * @since  3.0
	 * @access public
	 */
	public function media_row_con($post_id)
	{
		$meta_value = wp_get_attachment_metadata($post_id);

		if (empty($meta_value['file'])) {
			return;
		}

		$total_files = 0;

		// List all sizes
		$all_sizes = array($meta_value['file']);
		$size_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
		foreach ($meta_value['sizes'] as $v) {
			$all_sizes[] = $size_path . $v['file'];
		}

		foreach ($all_sizes as $short_path) {
			$lqip_folder = LITESPEED_STATIC_DIR . '/lqip/' . $short_path;

			if (is_dir($lqip_folder)) {
				Debug2::debug('[LQIP] Found folder: ' . $short_path);

				// List all files
				foreach (scandir($lqip_folder) as $v) {
					if ($v == '.' || $v == '..') {
						continue;
					}

					if ($total_files == 0) {
						echo '<div class="litespeed-media-lqip"><img src="' .
							File::read($lqip_folder . '/' . $v) .
							'" alt="' .
							sprintf(__('LQIP image preview for size %s', 'litespeed-cache'), $v) .
							'"></div>';
					}

					echo '<div class="litespeed-media-size"><a href="' . File::read($lqip_folder . '/' . $v) . '" target="_blank">' . $v . '</a></div>';

					$total_files++;
				}
			}
		}

		if ($total_files == 0) {
			echo '—';
		}
	}

	/**
	 * Replace image with placeholder
	 *
	 * @since  3.0
	 * @access public
	 */
	public function replace($html, $src, $size)
	{
		// Check if need to enable responsive placeholder or not
		$this_placeholder = $this->_placeholder($src, $size) ?: $this->_conf_ph_default;

		$additional_attr = '';
		if ($this->_conf_lqip && $this_placeholder != $this->_conf_ph_default) {
			Debug2::debug2('[LQIP] Use resp LQIP [size] ' . $size);
			$additional_attr = ' data-placeholder-resp="' . $size . '"';
		}

		$snippet = defined('LITESPEED_GUEST_OPTM') || $this->conf(self::O_OPTM_NOSCRIPT_RM) ? '' : '<noscript>' . $html . '</noscript>';
		$html = str_replace(array(' src=', ' srcset=', ' sizes='), array(' data-src=', ' data-srcset=', ' data-sizes='), $html);
		$html = str_replace('<img ', '<img data-lazyloaded="1"' . $additional_attr . ' src="' . $this_placeholder . '" ', $html);
		$snippet = $html . $snippet;

		return $snippet;
	}

	/**
	 * Generate responsive placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder($src, $size)
	{
		// Low Quality Image Placeholders
		if (!$size) {
			Debug2::debug2('[LQIP] no size ' . $src);
			return false;
		}

		if (!$this->_conf_placeholder_resp) {
			return false;
		}

		// If use local generator
		if (!$this->_conf_lqip || !$this->_lqip_size_check($size)) {
			return $this->_generate_placeholder_locally($size);
		}

		Debug2::debug2('[LQIP] Resp LQIP process [src] ' . $src . ' [size] ' . $size);

		$arr_key = $size . ' ' . $src;

		// Check if its already in dict or not
		if (!empty($this->_placeholder_resp_dict[$arr_key])) {
			Debug2::debug2('[LQIP] already in dict');

			return $this->_placeholder_resp_dict[$arr_key];
		}

		// Need to generate the responsive placeholder
		$placeholder_realpath = $this->_placeholder_realpath($src, $size); // todo: give offload API
		if (file_exists($placeholder_realpath)) {
			Debug2::debug2('[LQIP] file exists');
			$this->_placeholder_resp_dict[$arr_key] = File::read($placeholder_realpath);

			return $this->_placeholder_resp_dict[$arr_key];
		}

		// Add to cron queue

		// Prevent repeated requests
		if (in_array($arr_key, $this->_ph_queue)) {
			Debug2::debug2('[LQIP] file bypass generating due to in queue');
			return $this->_generate_placeholder_locally($size);
		}

		if ($hit = Utility::str_hit_array($src, $this->conf(self::O_MEDIA_LQIP_EXC))) {
			Debug2::debug2('[LQIP] file bypass generating due to exclude setting [hit] ' . $hit);
			return $this->_generate_placeholder_locally($size);
		}

		$this->_ph_queue[] = $arr_key;

		// Send request to generate placeholder
		if (!$this->_conf_placeholder_resp_async) {
			// If requested recently, bypass
			if ($this->_summary && !empty($this->_summary['curr_request']) && time() - $this->_summary['curr_request'] < 300) {
				Debug2::debug2('[LQIP] file bypass generating due to interval limit');
				return false;
			}
			// Generate immediately
			$this->_placeholder_resp_dict[$arr_key] = $this->_generate_placeholder($arr_key);

			return $this->_placeholder_resp_dict[$arr_key];
		}

		// Prepare default svg placeholder as tmp placeholder
		$tmp_placeholder = $this->_generate_placeholder_locally($size);

		// Store it to prepare for cron
		$queue = $this->load_queue('lqip');
		if (in_array($arr_key, $queue)) {
			Debug2::debug2('[LQIP] already in queue');

			return $tmp_placeholder;
		}

		if (count($queue) > 500) {
			Debug2::debug2('[LQIP] queue is full');

			return $tmp_placeholder;
		}

		$queue[] = $arr_key;
		$this->save_queue('lqip', $queue);
		Debug2::debug('[LQIP] Added placeholder queue');

		return $tmp_placeholder;
	}

	/**
	 * Generate realpath of placeholder file
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder_realpath($src, $size)
	{
		// Use LQIP Cloud generator, each image placeholder will be separately stored

		// Compatibility with WebP
		if (substr($src, -5) === '.webp') {
			$src = substr($src, 0, -5);
		}

		$filepath_prefix = $this->_build_filepath_prefix('lqip');

		// External images will use cache folder directly
		$domain = parse_url($src, PHP_URL_HOST);
		if ($domain && !Utility::internal($domain)) {
			// todo: need to improve `util:internal()` to include `CDN::internal()`
			$md5 = md5($src);

			return LITESPEED_STATIC_DIR . $filepath_prefix . 'remote/' . substr($md5, 0, 1) . '/' . substr($md5, 1, 1) . '/' . $md5 . '.' . $size;
		}

		// Drop domain
		$short_path = Utility::att_short_path($src);

		return LITESPEED_STATIC_DIR . $filepath_prefix . $short_path . '/' . $size;
	}

	/**
	 * Cron placeholder generation
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function cron($continue = false)
	{
		$_instance = self::cls();

		$queue = $_instance->load_queue('lqip');

		if (empty($queue)) {
			return;
		}

		// For cron, need to check request interval too
		if (!$continue) {
			if (!empty($_instance->_summary['curr_request']) && time() - $_instance->_summary['curr_request'] < 300) {
				Debug2::debug('[LQIP] Last request not done');
				return;
			}
		}

		foreach ($queue as $v) {
			Debug2::debug('[LQIP] cron job [size] ' . $v);

			$res = $_instance->_generate_placeholder($v, true);

			// Exit queue if out of quota
			if ($res === 'out_of_quota') {
				return;
			}

			// only request first one
			if (!$continue) {
				return;
			}
		}
	}

	/**
	 * Generate placeholder locally
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _generate_placeholder_locally($size)
	{
		Debug2::debug2('[LQIP] _generate_placeholder local [size] ' . $size);

		$size = explode('x', $size);

		$svg = str_replace(array('{width}', '{height}', '{color}'), array($size[0], $size[1], $this->_conf_placeholder_resp_color), $this->_conf_placeholder_resp_svg);

		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	/**
	 * Send to LiteSpeed API to generate placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _generate_placeholder($raw_size_and_src, $from_cron = false)
	{
		// Parse containing size and src info
		$size_and_src = explode(' ', $raw_size_and_src, 2);
		$size = $size_and_src[0];

		if (empty($size_and_src[1])) {
			$this->_popup_and_save($raw_size_and_src);
			Debug2::debug('[LQIP] ❌ No src [raw] ' . $raw_size_and_src);
			return $this->_generate_placeholder_locally($size);
		}

		$src = $size_and_src[1];

		$file = $this->_placeholder_realpath($src, $size);

		// Local generate SVG to serve ( Repeatly doing this here to remove stored cron queue in case the setting _conf_lqip is changed )
		if (!$this->_conf_lqip || !$this->_lqip_size_check($size)) {
			$data = $this->_generate_placeholder_locally($size);
		} else {
			$err = false;
			$allowance = Cloud::cls()->allowance(Cloud::SVC_LQIP, $err);
			if (!$allowance) {
				Debug2::debug('[LQIP] ❌ No credit: ' . $err);
				$err && Admin_Display::error(Error::msg($err));

				if ($from_cron) {
					return 'out_of_quota';
				}

				return $this->_generate_placeholder_locally($size);
			}

			// Generate LQIP
			list($width, $height) = explode('x', $size);
			$req_data = array(
				'width' => $width,
				'height' => $height,
				'url' => substr($src, -5) === '.webp' ? substr($src, 0, -5) : $src,
				'quality' => $this->_conf_lqip_qual,
			);

			// CHeck if the image is 404 first
			if (File::is_404($req_data['url'])) {
				$this->_popup_and_save($raw_size_and_src, true);
				$this->_append_exc($src);
				Debug2::debug('[LQIP] 404 before request [src] ' . $req_data['url']);
				return $this->_generate_placeholder_locally($size);
			}

			// Update request status
			$this->_summary['curr_request'] = time();
			self::save_summary();

			$json = Cloud::post(Cloud::SVC_LQIP, $req_data, 120);
			if (!is_array($json)) {
				return $this->_generate_placeholder_locally($size);
			}

			if (empty($json['lqip']) || strpos($json['lqip'], 'data:image/svg+xml') !== 0) {
				// image error, pop up the current queue
				$this->_popup_and_save($raw_size_and_src, true);
				$this->_append_exc($src);
				Debug2::debug('[LQIP] wrong response format', $json);

				return $this->_generate_placeholder_locally($size);
			}

			$data = $json['lqip'];

			Debug2::debug('[LQIP] _generate_placeholder LQIP');
		}

		// Write to file
		File::save($file, $data, true);

		// Save summary data
		$this->_summary['last_spent'] = time() - $this->_summary['curr_request'];
		$this->_summary['last_request'] = $this->_summary['curr_request'];
		$this->_summary['curr_request'] = 0;
		self::save_summary();
		$this->_popup_and_save($raw_size_and_src);

		Debug2::debug('[LQIP] saved LQIP ' . $file);

		return $data;
	}

	/**
	 * Check if the size is valid to send LQIP request or not
	 *
	 * @since  3.0
	 */
	private function _lqip_size_check($size)
	{
		$size = explode('x', $size);
		if ($size[0] >= $this->_conf_lqip_min_w || $size[1] >= $this->_conf_lqip_min_h) {
			return true;
		}

		Debug2::debug2('[LQIP] Size too small');

		return false;
	}

	/**
	 * Add to LQIP exclude list
	 *
	 * @since  3.4
	 */
	private function _append_exc($src)
	{
		$val = $this->conf(self::O_MEDIA_LQIP_EXC);
		$val[] = $src;
		$this->cls('Conf')->update(self::O_MEDIA_LQIP_EXC, $val);
		Debug2::debug('[LQIP] Appended to LQIP Excludes [URL] ' . $src);
	}

	/**
	 * Pop up the current request and save
	 *
	 * @since  3.0
	 */
	private function _popup_and_save($raw_size_and_src, $append_to_exc = false)
	{
		$queue = $this->load_queue('lqip');
		if (!empty($queue) && in_array($raw_size_and_src, $queue)) {
			unset($queue[array_search($raw_size_and_src, $queue)]);
		}

		if ($append_to_exc) {
			$size_and_src = explode(' ', $raw_size_and_src, 2);
			$this_src = $size_and_src[1];

			// Append to lqip exc setting first
			$this->_append_exc($this_src);

			// Check if other queues contain this src or not
			if ($queue) {
				foreach ($queue as $k => $raw_size_and_src) {
					$size_and_src = explode(' ', $raw_size_and_src, 2);
					if (empty($size_and_src[1])) {
						continue;
					}

					if ($size_and_src[1] == $this_src) {
						unset($queue[$k]);
					}
				}
			}
		}

		$this->save_queue('lqip', $queue);
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public function handler()
	{
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_GENERATE:
				self::cron(true);
				break;

			case self::TYPE_CLEAR_Q:
				$this->clear_q('lqip');
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
