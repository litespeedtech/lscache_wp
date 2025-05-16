<?php

/**
 * The CDN class.
 *
 * @since       1.2.3
 * @since       1.5 Moved into /inc
 * @package     LiteSpeed
 * @subpackage  LiteSpeed/inc
 * @author      LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class CDN extends Root {

	const BYPASS = 'LITESPEED_BYPASS_CDN';

	private $content;

	private $_cfg_cdn;
	private $_cfg_url_ori;
	private $_cfg_ori_dir;
	private $_cfg_cdn_mapping = array();
	private $_cfg_cdn_exclude;

	private $cdn_mapping_hosts = array();

	/**
	 * Init
	 *
	 * @since  1.2.3
	 */
	public function init() {
		Debug2::debug2('[CDN] init');

		if (defined(self::BYPASS)) {
			Debug2::debug2('CDN bypass');
			return;
		}

		if (!Router::can_cdn()) {
			if (!defined(self::BYPASS)) {
				define(self::BYPASS, true);
			}
			return;
		}

		$this->_cfg_cdn = $this->conf(Base::O_CDN);
		if (!$this->_cfg_cdn) {
			if (!defined(self::BYPASS)) {
				define(self::BYPASS, true);
			}
			return;
		}

		$this->_cfg_url_ori = $this->conf(Base::O_CDN_ORI);
		// Parse cdn mapping data to array( 'filetype' => 'url' )
		$mapping_to_check = array( Base::CDN_MAPPING_INC_IMG, Base::CDN_MAPPING_INC_CSS, Base::CDN_MAPPING_INC_JS );
		foreach ($this->conf(Base::O_CDN_MAPPING) as $v) {
			if (!$v[Base::CDN_MAPPING_URL]) {
				continue;
			}
			$this_url  = $v[Base::CDN_MAPPING_URL];
			$this_host = parse_url($this_url, PHP_URL_HOST);
			// Check img/css/js
			foreach ($mapping_to_check as $to_check) {
				if ($v[$to_check]) {
					Debug2::debug2('[CDN] mapping ' . $to_check . ' -> ' . $this_url);

					// If filetype to url is one to many, make url be an array
					$this->_append_cdn_mapping($to_check, $this_url);

					if (!in_array($this_host, $this->cdn_mapping_hosts)) {
						$this->cdn_mapping_hosts[] = $this_host;
					}
				}
			}
			// Check file types
			if ($v[Base::CDN_MAPPING_FILETYPE]) {
				foreach ($v[Base::CDN_MAPPING_FILETYPE] as $v2) {
					$this->_cfg_cdn_mapping[Base::CDN_MAPPING_FILETYPE] = true;

					// If filetype to url is one to many, make url be an array
					$this->_append_cdn_mapping($v2, $this_url);

					if (!in_array($this_host, $this->cdn_mapping_hosts)) {
						$this->cdn_mapping_hosts[] = $this_host;
					}
				}
				Debug2::debug2('[CDN] mapping ' . implode(',', $v[Base::CDN_MAPPING_FILETYPE]) . ' -> ' . $this_url);
			}
		}

		if (!$this->_cfg_url_ori || !$this->_cfg_cdn_mapping) {
			if (!defined(self::BYPASS)) {
				define(self::BYPASS, true);
			}
			return;
		}

		$this->_cfg_ori_dir = $this->conf(Base::O_CDN_ORI_DIR);
		// In case user customized upload path
		if (defined('UPLOADS')) {
			$this->_cfg_ori_dir[] = UPLOADS;
		}

		// Check if need preg_replace
		$this->_cfg_url_ori = Utility::wildcard2regex($this->_cfg_url_ori);

		$this->_cfg_cdn_exclude = $this->conf(Base::O_CDN_EXC);

		if (!empty($this->_cfg_cdn_mapping[Base::CDN_MAPPING_INC_IMG])) {
			// Hook to srcset
			if (function_exists('wp_calculate_image_srcset')) {
				add_filter('wp_calculate_image_srcset', array( $this, 'srcset' ), 999);
			}
			// Hook to mime icon
			add_filter('wp_get_attachment_image_src', array( $this, 'attach_img_src' ), 999);
			add_filter('wp_get_attachment_url', array( $this, 'url_img' ), 999);
		}

		if (!empty($this->_cfg_cdn_mapping[Base::CDN_MAPPING_INC_CSS])) {
			add_filter('style_loader_src', array( $this, 'url_css' ), 999);
		}

		if (!empty($this->_cfg_cdn_mapping[Base::CDN_MAPPING_INC_JS])) {
			add_filter('script_loader_src', array( $this, 'url_js' ), 999);
		}

		add_filter('litespeed_buffer_finalize', array( $this, 'finalize' ), 30);
	}

	/**
	 * Associate all filetypes with url
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _append_cdn_mapping( $filetype, $url ) {
		// If filetype to url is one to many, make url be an array
		if (empty($this->_cfg_cdn_mapping[$filetype])) {
			$this->_cfg_cdn_mapping[$filetype] = $url;
		} elseif (is_array($this->_cfg_cdn_mapping[$filetype])) {
			// Append url to filetype
			$this->_cfg_cdn_mapping[$filetype][] = $url;
		} else {
			// Convert _cfg_cdn_mapping from string to array
			$this->_cfg_cdn_mapping[$filetype] = array( $this->_cfg_cdn_mapping[$filetype], $url );
		}
	}

	/**
	 * If include css/js in CDN
	 *
	 * @since  1.6.2.1
	 * @return bool true if included in CDN
	 */
	public function inc_type( $type ) {
		if ($type == 'css' && !empty($this->_cfg_cdn_mapping[Base::CDN_MAPPING_INC_CSS])) {
			return true;
		}

		if ($type == 'js' && !empty($this->_cfg_cdn_mapping[Base::CDN_MAPPING_INC_JS])) {
			return true;
		}

		return false;
	}

	/**
	 * Run CDN process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * @since  1.2.3
	 * @access public
	 * @return  string The content that is after optimization
	 */
	public function finalize( $content ) {
		$this->content = $content;

		$this->_finalize();
		return $this->content;
	}

	/**
	 * Replace CDN url
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _finalize() {
		if (defined(self::BYPASS)) {
			return;
		}

		Debug2::debug('CDN _finalize');

		// Start replacing img src
		if (!empty($this->_cfg_cdn_mapping[Base::CDN_MAPPING_INC_IMG])) {
			$this->_replace_img();
			$this->_replace_inline_css();
		}

		if (!empty($this->_cfg_cdn_mapping[Base::CDN_MAPPING_FILETYPE])) {
			$this->_replace_file_types();
		}
	}

	/**
	 * Parse all file types
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _replace_file_types() {
		$ele_to_check = $this->conf(Base::O_CDN_ATTR);

		foreach ($ele_to_check as $v) {
			if (!$v || strpos($v, '.') === false) {
				Debug2::debug2('[CDN] replace setting bypassed: no . attribute ' . $v);
				continue;
			}

			Debug2::debug2('[CDN] replace attribute ' . $v);

			$v    = explode('.', $v);
			$attr = preg_quote($v[1], '#');
			if ($v[0]) {
				$pattern = '#<' . preg_quote($v[0], '#') . '([^>]+)' . $attr . '=([\'"])(.+)\g{2}#iU';
			} else {
				$pattern = '# ' . $attr . '=([\'"])(.+)\g{1}#iU';
			}

			preg_match_all($pattern, $this->content, $matches);

			if (empty($matches[$v[0] ? 3 : 2])) {
				continue;
			}

			foreach ($matches[$v[0] ? 3 : 2] as $k2 => $url) {
				// Debug2::debug2( '[CDN] check ' . $url );
				$postfix = '.' . pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
				if (!array_key_exists($postfix, $this->_cfg_cdn_mapping)) {
					// Debug2::debug2( '[CDN] non-existed postfix ' . $postfix );
					continue;
				}

				Debug2::debug2('[CDN] matched file_type ' . $postfix . ' : ' . $url);

				if (!($url2 = $this->rewrite($url, Base::CDN_MAPPING_FILETYPE, $postfix))) {
					continue;
				}

				$attr          = str_replace($url, $url2, $matches[0][$k2]);
				$this->content = str_replace($matches[0][$k2], $attr, $this->content);
			}
		}
	}

	/**
	 * Parse all images
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _replace_img() {
		preg_match_all('#<img([^>]+?)src=([\'"\\\]*)([^\'"\s\\\>]+)([\'"\\\]*)([^>]*)>#i', $this->content, $matches);
		foreach ($matches[3] as $k => $url) {
			// Check if is a DATA-URI
			if (strpos($url, 'data:image') !== false) {
				continue;
			}

			if (!($url2 = $this->rewrite($url, Base::CDN_MAPPING_INC_IMG))) {
				continue;
			}

			$html_snippet  = sprintf('<img %1$s src=%2$s %3$s>', $matches[1][$k], $matches[2][$k] . $url2 . $matches[4][$k], $matches[5][$k]);
			$this->content = str_replace($matches[0][$k], $html_snippet, $this->content);
		}
	}

	/**
	 * Parse and replace all inline styles containing url()
	 *
	 * @since  1.2.3
	 * @access private
	 */
	private function _replace_inline_css() {
		Debug2::debug2('[CDN] _replace_inline_css', $this->_cfg_cdn_mapping);

		/**
		 * Excludes `\` from URL matching
		 *
		 * @see  #959152 - WordPress LSCache CDN Mapping causing malformed URLS
		 * @see  #685485
		 * @since 3.0
		 */
		preg_match_all('/url\((?![\'"]?data)[\'"]?(.+?)[\'"]?\)/i', $this->content, $matches);
		foreach ($matches[1] as $k => $url) {
			$url = str_replace(array( ' ', '\t', '\n', '\r', '\0', '\x0B', '"', "'", '&quot;', '&#039;' ), '', $url);

			// Parse file postfix
			$parsed_url = parse_url($url, PHP_URL_PATH);
			if (!$parsed_url) {
				continue;
			}

			$postfix = '.' . pathinfo($parsed_url, PATHINFO_EXTENSION);
			if (array_key_exists($postfix, $this->_cfg_cdn_mapping)) {
				Debug2::debug2('[CDN] matched file_type ' . $postfix . ' : ' . $url);
				if (!($url2 = $this->rewrite($url, Base::CDN_MAPPING_FILETYPE, $postfix))) {
					continue;
				}
			} elseif (in_array($postfix, array( 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif' ))) {
				if (!($url2 = $this->rewrite($url, Base::CDN_MAPPING_INC_IMG))) {
					continue;
				}
			} else {
				continue;
			}

			$attr          = str_replace($matches[1][$k], $url2, $matches[0][$k]);
			$this->content = str_replace($matches[0][$k], $attr, $this->content);
		}

		Debug2::debug2('[CDN] _replace_inline_css done');
	}

	/**
	 * Hook to wp_get_attachment_image_src
	 *
	 * @since  1.2.3
	 * @since  1.7 Removed static from function
	 * @access public
	 * @param  array $img The URL of the attachment image src, the width, the height
	 * @return array
	 */
	public function attach_img_src( $img ) {
		if ($img && ($url = $this->rewrite($img[0], Base::CDN_MAPPING_INC_IMG))) {
			$img[0] = $url;
		}
		return $img;
	}

	/**
	 * Try to rewrite one URL with CDN
	 *
	 * @since  1.7
	 * @access public
	 */
	public function url_img( $url ) {
		if ($url && ($url2 = $this->rewrite($url, Base::CDN_MAPPING_INC_IMG))) {
			$url = $url2;
		}
		return $url;
	}

	/**
	 * Try to rewrite one URL with CDN
	 *
	 * @since  1.7
	 * @access public
	 */
	public function url_css( $url ) {
		if ($url && ($url2 = $this->rewrite($url, Base::CDN_MAPPING_INC_CSS))) {
			$url = $url2;
		}
		return $url;
	}

	/**
	 * Try to rewrite one URL with CDN
	 *
	 * @since  1.7
	 * @access public
	 */
	public function url_js( $url ) {
		if ($url && ($url2 = $this->rewrite($url, Base::CDN_MAPPING_INC_JS))) {
			$url = $url2;
		}
		return $url;
	}

	/**
	 * Hook to replace WP responsive images
	 *
	 * @since  1.2.3
	 * @since  1.7 Removed static from function
	 * @access public
	 * @param  array $srcs
	 * @return array
	 */
	public function srcset( $srcs ) {
		if ($srcs) {
			foreach ($srcs as $w => $data) {
				if (!($url = $this->rewrite($data['url'], Base::CDN_MAPPING_INC_IMG))) {
					continue;
				}
				$srcs[$w]['url'] = $url;
			}
		}
		return $srcs;
	}

	/**
	 * Replace URL to CDN URL
	 *
	 * @since  1.2.3
	 * @access public
	 * @param  string $url
	 * @return string        Replaced URL
	 */
	public function rewrite( $url, $mapping_kind, $postfix = false ) {
		Debug2::debug2('[CDN] rewrite ' . $url);
		$url_parsed = parse_url($url);

		if (empty($url_parsed['path'])) {
			Debug2::debug2('[CDN] -rewrite bypassed: no path');
			return false;
		}

		// Only images under wp-cotnent/wp-includes can be replaced
		$is_internal_folder = Utility::str_hit_array($url_parsed['path'], $this->_cfg_ori_dir);
		if (!$is_internal_folder) {
			Debug2::debug2('[CDN] -rewrite failed: path not match: ' . LSCWP_CONTENT_FOLDER);
			return false;
		}

		// Check if is external url
		if (!empty($url_parsed['host'])) {
			if (!Utility::internal($url_parsed['host']) && !$this->_is_ori_url($url)) {
				Debug2::debug2('[CDN] -rewrite failed: host not internal');
				return false;
			}
		}

		$exclude = Utility::str_hit_array($url, $this->_cfg_cdn_exclude);
		if ($exclude) {
			Debug2::debug2('[CDN] -abort excludes ' . $exclude);
			return false;
		}

		// Fill full url before replacement
		if (empty($url_parsed['host'])) {
			$url = Utility::uri2url($url);
			Debug2::debug2('[CDN] -fill before rewritten: ' . $url);

			$url_parsed = parse_url($url);
		}

		$scheme = !empty($url_parsed['scheme']) ? $url_parsed['scheme'] . ':' : '';
		if ($scheme) {
			// Debug2::debug2( '[CDN] -scheme from url: ' . $scheme );
		}

		// Find the mapping url to be replaced to
		if (empty($this->_cfg_cdn_mapping[$mapping_kind])) {
			return false;
		}
		if ($mapping_kind !== Base::CDN_MAPPING_FILETYPE) {
			$final_url = $this->_cfg_cdn_mapping[$mapping_kind];
		} else {
			// select from file type
			$final_url = $this->_cfg_cdn_mapping[$postfix];
		}

		// If filetype to url is one to many, need to random one
		if (is_array($final_url)) {
			$final_url = $final_url[array_rand($final_url)];
		}

		// Now lets replace CDN url
		foreach ($this->_cfg_url_ori as $v) {
			if (strpos($v, '*') !== false) {
				$url = preg_replace('#' . $scheme . $v . '#iU', $final_url, $url);
			} else {
				$url = str_replace($scheme . $v, $final_url, $url);
			}
		}
		Debug2::debug2('[CDN] -rewritten: ' . $url);

		return $url;
	}

	/**
	 * Check if is original URL of CDN or not
	 *
	 * @since  2.1
	 * @access private
	 */
	private function _is_ori_url( $url ) {
		$url_parsed = parse_url($url);

		$scheme = !empty($url_parsed['scheme']) ? $url_parsed['scheme'] . ':' : '';

		foreach ($this->_cfg_url_ori as $v) {
			$needle = $scheme . $v;
			if (strpos($v, '*') !== false) {
				if (preg_match('#' . $needle . '#iU', $url)) {
					return true;
				}
			} elseif (strpos($url, $needle) === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the host is the CDN internal host
	 *
	 * @since  1.2.3
	 */
	public static function internal( $host ) {
		if (defined(self::BYPASS)) {
			return false;
		}

		$instance = self::cls();

		return in_array($host, $instance->cdn_mapping_hosts); // todo: can add $this->_is_ori_url() check in future
	}
}
