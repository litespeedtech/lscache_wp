<?php
/**
 * The optimize4 class.
 *
 * @since      	1.9
 * @package  	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined('WPINC') || exit();

class Optimizer extends Root
{
	private $_conf_css_font_display;

	/**
	 * Init optimizer
	 *
	 * @since  1.9
	 */
	public function __construct()
	{
		$this->_conf_css_font_display = $this->conf(Base::O_OPTM_CSS_FONT_DISPLAY);
	}

	/**
	 * Run HTML minify process and return final content
	 *
	 * @since  1.9
	 * @access public
	 */
	public function html_min($content, $force_inline_minify = false)
	{
		if (!apply_filters('litespeed_html_min', true)) {
			Debug2::debug2('[Optmer] html_min bypassed via litespeed_html_min filter');
			return $content;
		}

		$options = array();

		if ($force_inline_minify) {
			$options['jsMinifier'] = __CLASS__ . '::minify_js';
		}

		$skip_comments = $this->conf(Base::O_OPTM_HTML_SKIP_COMMENTS);
		if ($skip_comments) {
			$options['skipComments'] = $skip_comments;
		}

		/**
		 * Added exception capture when minify
		 * @since  2.2.3
		 */
		try {
			$obj = new Lib\HTML_MIN($content, $options);
			$content_final = $obj->process();
			if (!defined('LSCACHE_ESI_SILENCE')) {
				$content_final .= "\n" . '<!-- Page optimized by LiteSpeed Cache @' . date('Y-m-d H:i:s', time() + LITESPEED_TIME_OFFSET) . ' -->';
			}
			return $content_final;
		} catch (\Exception $e) {
			Debug2::debug('******[Optmer] html_min failed: ' . $e->getMessage());
			error_log('****** LiteSpeed Optimizer html_min failed: ' . $e->getMessage());
			return $content;
		}
	}

	/**
	 * Run minify process and save content
	 *
	 * @since  1.9
	 * @access public
	 */
	public function serve($request_url, $file_type, $minify, $src_list)
	{
		// Try Unique CSS
		if ($file_type == 'css') {
			$content = false;
			if (defined('LITESPEED_GUEST_OPTM') || $this->conf(Base::O_OPTM_UCSS)) {
				$filename = $this->cls('UCSS')->load($request_url);

				if ($filename) {
					return array($filename, 'ucss');
				}
			}
		}

		// Before generated, don't know the contented hash filename yet, so used url hash as tmp filename
		$file_path_prefix = $this->_build_filepath_prefix($file_type);

		$url_tag = $request_url;
		$url_tag_for_file = md5($request_url);
		if (is_404()) {
			$url_tag_for_file = $url_tag = '404';
		} elseif ($file_type == 'css' && apply_filters('litespeed_ucss_per_pagetype', false)) {
			$url_tag_for_file = $url_tag = Utility::page_type();
		}

		$static_file = LITESPEED_STATIC_DIR . $file_path_prefix . $url_tag_for_file . '.' . $file_type;

		// Create tmp file to avoid conflict
		$tmp_static_file = $static_file . '.tmp';
		if (file_exists($tmp_static_file) && time() - filemtime($tmp_static_file) <= 600) {
			// some other request is generating
			return false;
		}
		// File::save( $tmp_static_file, '/* ' . ( is_404() ? '404' : $request_url ) . ' */', true ); // Can't use this bcos this will get filecon md5 changed
		File::save($tmp_static_file, '', true);

		// Load content
		$real_files = array();
		foreach ($src_list as $src_info) {
			$is_min = false;
			if (!empty($src_info['inl'])) {
				// Load inline
				$content = $src_info['src'];
			} else {
				// Load file
				$content = $this->load_file($src_info['src'], $file_type);

				if (!$content) {
					continue;
				}

				$is_min = $this->is_min($src_info['src']);
			}
			$content = $this->optm_snippet($content, $file_type, $minify && !$is_min, $src_info['src'], !empty($src_info['media']) ? $src_info['media'] : false);
			// Write to file
			File::save($tmp_static_file, $content, true, true);
		}

		// validate md5
		$filecon_md5 = md5_file($tmp_static_file);

		$final_file_path = $file_path_prefix . $filecon_md5 . '.' . $file_type;
		$realfile = LITESPEED_STATIC_DIR . $final_file_path;
		if (!file_exists($realfile)) {
			rename($tmp_static_file, $realfile);
			Debug2::debug2('[Optmer] Saved static file [path] ' . $realfile);
		} else {
			unlink($tmp_static_file);
		}

		$vary = $this->cls('Vary')->finalize_full_varies();
		Debug2::debug2("[Optmer] Save URL to file for [file_type] $file_type [file] $filecon_md5 [vary] $vary ");
		$this->cls('Data')->save_url($url_tag, $vary, $file_type, $filecon_md5, dirname($realfile));

		return array($filecon_md5 . '.' . $file_type, $file_type);
	}

	/**
	 * Load a single file
	 * @since  4.0
	 */
	public function optm_snippet($content, $file_type, $minify, $src, $media = false)
	{
		// CSS related features
		if ($file_type == 'css') {
			// Font optimize
			if ($this->_conf_css_font_display) {
				$content = preg_replace('#(@font\-face\s*\{)#isU', '${1}font-display:swap;', $content);
			}

			$content = preg_replace('/@charset[^;]+;\\s*/', '', $content);

			if ($media) {
				$content = '@media ' . $media . '{' . $content . "\n}";
			}

			if ($minify) {
				$content = self::minify_css($content);
			}

			$content = $this->cls('CDN')->finalize($content);

			if ((defined('LITESPEED_GUEST_OPTM') || $this->conf(Base::O_IMG_OPTM_WEBP)) && $this->cls('Media')->webp_support()) {
				$content = $this->cls('Media')->replace_background_webp($content);
			}
		} else {
			if ($minify) {
				$content = self::minify_js($content);
			} else {
				$content = $this->_null_minifier($content);
			}

			$content .= "\n;";
		}

		// Add filter
		$content = apply_filters('litespeed_optm_cssjs', $content, $file_type, $src);

		return $content;
	}

	/**
	 * Load remote resource from cache if existed
	 *
	 * @since  4.7
	 */
	private function load_cached_file($url, $file_type)
	{
		$file_path_prefix = $this->_build_filepath_prefix($file_type);
		$folder_name = LITESPEED_STATIC_DIR . $file_path_prefix;
		$to_be_deleted_folder = $folder_name . date('Ymd', strtotime('-2 days'));
		if (file_exists($to_be_deleted_folder)) {
			Debug2::debug('[Optimizer] ❌ Clearning folder [name] ' . $to_be_deleted_folder);
			File::rrmdir($to_be_deleted_folder);
		}

		$today_file = $folder_name . date('Ymd') . '/' . md5($url);
		if (file_exists($today_file)) {
			return File::read($today_file);
		}

		// Write file
		$res = wp_remote_get($url);
		$res_code = wp_remote_retrieve_response_code($res);
		if (is_wp_error($res) || $res_code != 200) {
			Debug2::debug2('[Optimizer] ❌ Load Remote error [code] ' . $res_code);
			return false;
		}
		$con = wp_remote_retrieve_body($res);
		if (!$con) {
			return false;
		}

		Debug2::debug('[Optimizer] ✅ Save remote file to cache [name] ' . $today_file);
		File::save($today_file, $con, true);

		return $con;
	}

	/**
	 * Load remote/local resource
	 *
	 * @since  3.5
	 */
	public function load_file($src, $file_type = 'css')
	{
		$real_file = Utility::is_internal_file($src);
		$postfix = pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION);
		if (!$real_file || $postfix != $file_type) {
			Debug2::debug2('[CSS] Load Remote [' . $file_type . '] ' . $src);
			$this_url = substr($src, 0, 2) == '//' ? set_url_scheme($src) : $src;
			$con = $this->load_cached_file($this_url, $file_type);

			if ($file_type == 'css') {
				$dirname = dirname($this_url) . '/';

				$con = Lib\CSS_MIN\UriRewriter::prepend($con, $dirname);
			}
		} else {
			Debug2::debug2('[CSS] Load local [' . $file_type . '] ' . $real_file[0]);
			$con = File::read($real_file[0]);

			if ($file_type == 'css') {
				$dirname = dirname($real_file[0]);

				$con = Lib\CSS_MIN\UriRewriter::rewrite($con, $dirname);
			}
		}

		return $con;
	}

	/**
	 * Minify CSS
	 *
	 * @since  2.2.3
	 * @access private
	 */
	public static function minify_css($data)
	{
		try {
			$obj = new Lib\CSS_MIN\Minifier();
			return $obj->run($data);
		} catch (\Exception $e) {
			Debug2::debug('******[Optmer] minify_css failed: ' . $e->getMessage());
			error_log('****** LiteSpeed Optimizer minify_css failed: ' . $e->getMessage());
			return $data;
		}
	}

	/**
	 * Minify JS
	 *
	 * Added exception capture when minify
	 *
	 * @since  2.2.3
	 * @access private
	 */
	public static function minify_js($data, $js_type = '')
	{
		// For inline JS optimize, need to check if it's js type
		if ($js_type) {
			preg_match('#type=([\'"])(.+)\g{1}#isU', $js_type, $matches);
			if ($matches && $matches[2] != 'text/javascript') {
				Debug2::debug('******[Optmer] minify_js bypass due to type: ' . $matches[2]);
				return $data;
			}
		}

		try {
			$data = Lib\JSMin::minify($data);
			return $data;
		} catch (\Exception $e) {
			Debug2::debug('******[Optmer] minify_js failed: ' . $e->getMessage());
			// error_log( '****** LiteSpeed Optimizer minify_js failed: ' . $e->getMessage() );
			return $data;
		}
	}

	/**
	 * Basic minifier
	 *
	 * @access private
	 */
	private function _null_minifier($content)
	{
		$content = str_replace("\r\n", "\n", $content);

		return trim($content);
	}

	/**
	 * Check if the file is already min file
	 *
	 * @since  1.9
	 */
	public function is_min($filename)
	{
		$basename = basename($filename);
		if (preg_match('/[-\.]min\.(?:[a-zA-Z]+)$/i', $basename)) {
			return true;
		}

		return false;
	}
}
