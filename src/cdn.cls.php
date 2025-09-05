<?php
/**
 * CDN handling for LiteSpeed Cache.
 *
 * Rewrites eligible asset URLs to configured CDN endpoints and integrates with WordPress filters.
 *
 * @since 1.2.3
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class CDN
 *
 * Processes page content and WordPress asset URLs to map to CDN domains according to settings.
 */
class CDN extends Root {
	const LOG_TAG = '[CDN]';

	const BYPASS = 'LITESPEED_BYPASS_CDN';

	/**
	 * The working HTML/content buffer being processed.
	 *
	 * @var string
	 */
	private $content;

	/**
	 * Whether CDN feature is enabled.
	 *
	 * @var bool
	 */
	private $_cfg_cdn;

	/**
	 * List of original site URLs (may include wildcards) to be replaced.
	 *
	 * @var string[]
	 */
	private $_cfg_url_ori;

	/**
	 * List of directories considered internal/original for CDN rewriting.
	 *
	 * @var string[]
	 */
	private $_cfg_ori_dir;

	/**
	 * CDN mapping rules; keys include mapping kinds or file extensions, values are URL(s).
	 *
	 * @var array<string,string|string[]>
	 */
	private $_cfg_cdn_mapping = [];

	/**
	 * List of URL substrings/regex used to exclude items from CDN.
	 *
	 * @var string[]
	 */
	private $_cfg_cdn_exclude;

	/**
	 * Hosts used by CDN mappings for quick membership checks.
	 *
	 * @var string[]
	 */
	private $cdn_mapping_hosts = [];

	/**
	 * Initialize CDN integration and register filters if enabled.
	 *
	 * @since 1.2.3
	 * @return void
	 */
	public function init() {
		self::debug2( 'init' );

		if ( defined( self::BYPASS ) ) {
			self::debug2( 'CDN bypass' );
			return;
		}

		if ( ! Router::can_cdn() ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true );
			}
			return;
		}

		$this->_cfg_cdn = $this->conf( Base::O_CDN );
		if ( ! $this->_cfg_cdn ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true );
			}
			return;
		}

		$this->_cfg_url_ori = $this->conf( Base::O_CDN_ORI );
		// Parse cdn mapping data to array( 'filetype' => 'url' )
		$mapping_to_check = [ Base::CDN_MAPPING_INC_IMG, Base::CDN_MAPPING_INC_CSS, Base::CDN_MAPPING_INC_JS ];
		foreach ( $this->conf( Base::O_CDN_MAPPING ) as $v ) {
			if ( ! $v[ Base::CDN_MAPPING_URL ] ) {
				continue;
			}
			$this_url  = $v[ Base::CDN_MAPPING_URL ];
			$this_host = wp_parse_url( $this_url, PHP_URL_HOST );
			// Check img/css/js
			foreach ( $mapping_to_check as $to_check ) {
				if ( $v[ $to_check ] ) {
					self::debug2( 'mapping ' . $to_check . ' -> ' . $this_url );

					// If filetype to url is one to many, make url be an array
					$this->_append_cdn_mapping( $to_check, $this_url );

					if ( ! in_array( $this_host, $this->cdn_mapping_hosts, true ) ) {
						$this->cdn_mapping_hosts[] = $this_host;
					}
				}
			}
			// Check file types
			if ( $v[ Base::CDN_MAPPING_FILETYPE ] ) {
				foreach ( $v[ Base::CDN_MAPPING_FILETYPE ] as $v2 ) {
					$this->_cfg_cdn_mapping[ Base::CDN_MAPPING_FILETYPE ] = true;

					// If filetype to url is one to many, make url be an array
					$this->_append_cdn_mapping( $v2, $this_url );

					if ( ! in_array( $this_host, $this->cdn_mapping_hosts, true ) ) {
						$this->cdn_mapping_hosts[] = $this_host;
					}
				}
				self::debug2( 'mapping ' . implode( ',', $v[ Base::CDN_MAPPING_FILETYPE ] ) . ' -> ' . $this_url );
			}
		}

		if ( ! $this->_cfg_url_ori || ! $this->_cfg_cdn_mapping ) {
			if ( ! defined( self::BYPASS ) ) {
				define( self::BYPASS, true );
			}
			return;
		}

		$this->_cfg_ori_dir = $this->conf( Base::O_CDN_ORI_DIR );
		// In case user customized upload path
		if ( defined( 'UPLOADS' ) ) {
			$this->_cfg_ori_dir[] = UPLOADS;
		}

		// Check if need preg_replace
		$this->_cfg_url_ori = Utility::wildcard2regex( $this->_cfg_url_ori );

		$this->_cfg_cdn_exclude = $this->conf( Base::O_CDN_EXC );

		if ( ! empty( $this->_cfg_cdn_mapping[ Base::CDN_MAPPING_INC_IMG ] ) ) {
			// Hook to srcset
			if ( function_exists( 'wp_calculate_image_srcset' ) ) {
				add_filter( 'wp_calculate_image_srcset', [ $this, 'srcset' ], 999 );
			}
			// Hook to mime icon
			add_filter( 'wp_get_attachment_image_src', [ $this, 'attach_img_src' ], 999 );
			add_filter( 'wp_get_attachment_url', [ $this, 'url_img' ], 999 );
		}

		if ( ! empty( $this->_cfg_cdn_mapping[ Base::CDN_MAPPING_INC_CSS ] ) ) {
			add_filter( 'style_loader_src', [ $this, 'url_css' ], 999 );
		}

		if ( ! empty( $this->_cfg_cdn_mapping[ Base::CDN_MAPPING_INC_JS ] ) ) {
			add_filter( 'script_loader_src', [ $this, 'url_js' ], 999 );
		}

		add_filter( 'litespeed_buffer_finalize', [ $this, 'finalize' ], 30 );
	}

	/**
	 * Associate all filetypes with CDN URL.
	 *
	 * @since 2.0
	 * @access private
	 *
	 * @param string $filetype Mapping key (e.g., extension or mapping constant).
	 * @param string $url      CDN base URL to use for this mapping.
	 * @return void
	 */
	private function _append_cdn_mapping( $filetype, $url ) {
		// If filetype to url is one to many, make url be an array
		if ( empty( $this->_cfg_cdn_mapping[ $filetype ] ) ) {
			$this->_cfg_cdn_mapping[ $filetype ] = $url;
		} elseif ( is_array( $this->_cfg_cdn_mapping[ $filetype ] ) ) {
			// Append url to filetype
			$this->_cfg_cdn_mapping[ $filetype ][] = $url;
		} else {
			// Convert _cfg_cdn_mapping from string to array
			$this->_cfg_cdn_mapping[ $filetype ] = [ $this->_cfg_cdn_mapping[ $filetype ], $url ];
		}
	}

	/**
	 * Whether the given type is included in CDN mappings.
	 *
	 * @since 1.6.2.1
	 *
	 * @param string $type 'css' or 'js'.
	 * @return bool True if included in CDN.
	 */
	public function inc_type( $type ) {
		if ( 'css' === $type && ! empty( $this->_cfg_cdn_mapping[ Base::CDN_MAPPING_INC_CSS ] ) ) {
			return true;
		}

		if ( 'js' === $type && ! empty( $this->_cfg_cdn_mapping[ Base::CDN_MAPPING_INC_JS ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Run CDN processing on finalized buffer.
	 * NOTE: After cache finalized, cannot change cache control.
	 *
	 * @since 1.2.3
	 * @access public
	 *
	 * @param string $content The HTML/content buffer.
	 * @return string The processed content.
	 */
	public function finalize( $content ) {
		$this->content = $content;

		$this->_finalize();
		return $this->content;
	}

	/**
	 * Replace eligible URLs with CDN URLs in the working buffer.
	 *
	 * @since 1.2.3
	 * @access private
	 * @return void
	 */
	private function _finalize() {
		if ( defined( self::BYPASS ) ) {
			return;
		}

		self::debug( 'CDN _finalize' );

		// Start replacing img src
		if ( ! empty( $this->_cfg_cdn_mapping[ Base::CDN_MAPPING_INC_IMG ] ) ) {
			$this->_replace_img();
			$this->_replace_inline_css();
		}

		if ( ! empty( $this->_cfg_cdn_mapping[ Base::CDN_MAPPING_FILETYPE ] ) ) {
			$this->_replace_file_types();
		}
	}

	/**
	 * Parse all file types and replace according to configured attributes.
	 *
	 * @since 1.2.3
	 * @access private
	 * @return void
	 */
	private function _replace_file_types() {
		$ele_to_check = $this->conf( Base::O_CDN_ATTR );

		foreach ( $ele_to_check as $v ) {
			if ( ! $v || false === strpos( $v, '.' ) ) {
				self::debug2( 'replace setting bypassed: no . attribute ' . $v );
				continue;
			}

			self::debug2( 'replace attribute ' . $v );

			$v    = explode( '.', $v );
			$attr = preg_quote( $v[1], '#' );
			if ( $v[0] ) {
				$pattern = '#<' . preg_quote( $v[0], '#' ) . '([^>]+)' . $attr . '=([\'"])(.+)\g{2}#iU';
			} else {
				$pattern = '# ' . $attr . '=([\'"])(.+)\g{1}#iU';
			}

			preg_match_all( $pattern, $this->content, $matches );

			if (empty($matches[$v[0] ? 3 : 2])) {
				continue;
			}

			foreach ($matches[$v[0] ? 3 : 2] as $k2 => $url) {
				// self::debug2( 'check ' . $url );
				$postfix = '.' . pathinfo((string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
				if (!array_key_exists($postfix, $this->_cfg_cdn_mapping)) {
					// self::debug2( 'non-existed postfix ' . $postfix );
					continue;
				}

				self::debug2( 'matched file_type ' . $postfix . ' : ' . $url );

				$url2 = $this->rewrite( $url, Base::CDN_MAPPING_FILETYPE, $postfix );
				if ( ! $url2 ) {
					continue;
				}

				$attr_str      = str_replace( $url, $url2, $matches[0][ $k2 ] );
				$this->content = str_replace( $matches[0][ $k2 ], $attr_str, $this->content );
			}
		}
	}

	/**
	 * Parse all images and replace their src attributes.
	 *
	 * @since 1.2.3
	 * @access private
	 * @return void
	 */
	private function _replace_img() {
		preg_match_all( '#<img([^>]+?)src=([\'"\\\]*)([^\'"\s\\\>]+)([\'"\\\]*)([^>]*)>#i', $this->content, $matches );
		foreach ( $matches[3] as $k => $url ) {
			// Check if is a DATA-URI
			if ( false !== strpos( $url, 'data:image' ) ) {
				continue;
			}

			$url2 = $this->rewrite( $url, Base::CDN_MAPPING_INC_IMG );
			if ( ! $url2 ) {
				continue;
			}

			$html_snippet  = sprintf( '<img %1$s src=%2$s %3$s>', $matches[1][ $k ], $matches[2][ $k ] . $url2 . $matches[4][ $k ], $matches[5][ $k ] );
			$this->content = str_replace( $matches[0][ $k ], $html_snippet, $this->content );
		}
	}

	/**
	 * Parse and replace all inline styles containing url().
	 *
	 * @since 1.2.3
	 * @access private
	 * @return void
	 */
	private function _replace_inline_css() {
		self::debug2( '_replace_inline_css', $this->_cfg_cdn_mapping );

		/**
		 * Excludes `\` from URL matching
		 *
		 * @see  #959152 - WordPress LSCache CDN Mapping causing malformed URLS
		 * @see  #685485
		 * @since 3.0
		 */
		preg_match_all( '/url\((?![\'"]?data)[\'"]?(.+?)[\'"]?\)/i', $this->content, $matches );
		foreach ( $matches[1] as $k => $url ) {
			$url = str_replace( [ ' ', '\t', '\n', '\r', '\0', '\x0B', '"', "'", '&quot;', '&#039;' ], '', $url );

			// Parse file postfix
			$parsed_url = wp_parse_url( $url, PHP_URL_PATH );
			if ( ! $parsed_url ) {
				continue;
			}

			$postfix = '.' . pathinfo( $parsed_url, PATHINFO_EXTENSION );
			if ( array_key_exists( $postfix, $this->_cfg_cdn_mapping ) ) {
				self::debug2( 'matched file_type ' . $postfix . ' : ' . $url );
				$url2 = $this->rewrite( $url, Base::CDN_MAPPING_FILETYPE, $postfix );
				if ( ! $url2 ) {
					continue;
				}
			} elseif ( in_array( $postfix, [ 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif' ], true ) ) {
				$url2 = $this->rewrite( $url, Base::CDN_MAPPING_INC_IMG );
				if ( ! $url2 ) {
					continue;
				}
			} else {
				continue;
			}

			$attr          = str_replace( $matches[1][ $k ], $url2, $matches[0][ $k ] );
			$this->content = str_replace( $matches[0][ $k ], $attr, $this->content );
		}

		self::debug2( '_replace_inline_css done' );
	}

	/**
	 * Filter: wp_get_attachment_image_src.
	 *
	 * @since 1.2.3
	 * @since 1.7 Removed static from function.
	 * @access public
	 *
	 * @param array{0:string,1:int,2:int} $img The URL of the attachment image src, the width, the height.
	 * @return array{0:string,1:int,2:int}
	 */
	public function attach_img_src( $img ) {
		if ( $img ) {
			$url = $this->rewrite( $img[0], Base::CDN_MAPPING_INC_IMG );
			if ( $url ) {
				$img[0] = $url;
			}
		}
		return $img;
	}

	/**
	 * Try to rewrite one image URL with CDN.
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @param string $url Original URL.
	 * @return string URL after rewriting, or original if not applicable.
	 */
	public function url_img( $url ) {
		if ( $url ) {
			$url2 = $this->rewrite( $url, Base::CDN_MAPPING_INC_IMG );
			if ( $url2 ) {
				$url = $url2;
			}
		}
		return $url;
	}

	/**
	 * Try to rewrite one CSS URL with CDN.
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @param string $url Original URL.
	 * @return string URL after rewriting, or original if not applicable.
	 */
	public function url_css( $url ) {
		if ( $url ) {
			$url2 = $this->rewrite( $url, Base::CDN_MAPPING_INC_CSS );
			if ( $url2 ) {
				$url = $url2;
			}
		}
		return $url;
	}

	/**
	 * Try to rewrite one JS URL with CDN.
	 *
	 * @since 1.7
	 * @access public
	 *
	 * @param string $url Original URL.
	 * @return string URL after rewriting, or original if not applicable.
	 */
	public function url_js( $url ) {
		if ( $url ) {
			$url2 = $this->rewrite( $url, Base::CDN_MAPPING_INC_JS );
			if ( $url2 ) {
				$url = $url2;
			}
		}
		return $url;
	}

	/**
	 * Filter responsive image sources for CDN.
	 *
	 * @since 1.2.3
	 * @since 1.7 Removed static from function.
	 * @access public
	 *
	 * @param array<int,array{url:string}> $srcs Srcset array.
	 * @return array<int,array{url:string}>
	 */
	public function srcset( $srcs ) {
		if ( $srcs ) {
			foreach ( $srcs as $w => $data ) {
				$url = $this->rewrite( $data['url'], Base::CDN_MAPPING_INC_IMG );
				if ( ! $url ) {
					continue;
				}
				$srcs[ $w ]['url'] = $url;
			}
		}
		return $srcs;
	}

	/**
	 * Replace an URL with mapped CDN URL, if applicable.
	 *
	 * @since 1.2.3
	 * @access public
	 *
	 * @param string       $url          Target URL.
	 * @param string       $mapping_kind Mapping kind (e.g., Base::CDN_MAPPING_INC_IMG or Base::CDN_MAPPING_FILETYPE).
	 * @param string|false $postfix      File extension (with dot) when mapping by file type.
	 * @return string|false Replaced URL on success, false when not applicable.
	 */
	public function rewrite( $url, $mapping_kind, $postfix = false ) {
		self::debug2( 'rewrite ' . $url );
		$url_parsed = wp_parse_url( $url );

		if ( empty( $url_parsed['path'] ) ) {
			self::debug2( '-rewrite bypassed: no path' );
			return false;
		}

		// Only images under wp-content/wp-includes can be replaced
		$is_internal_folder = Utility::str_hit_array( $url_parsed['path'], $this->_cfg_ori_dir );
		if ( ! $is_internal_folder ) {
			self::debug2( '-rewrite failed: path not match: ' . LSCWP_CONTENT_FOLDER );
			return false;
		}

		// Check if is external url
		if ( ! empty( $url_parsed['host'] ) ) {
			if ( ! Utility::internal( $url_parsed['host'] ) && ! $this->_is_ori_url( $url ) ) {
				self::debug2( '-rewrite failed: host not internal' );
				return false;
			}
		}

		$exclude = Utility::str_hit_array( $url, $this->_cfg_cdn_exclude );
		if ( $exclude ) {
			self::debug2( '-abort excludes ' . $exclude );
			return false;
		}

		// Fill full url before replacement
		if ( empty( $url_parsed['host'] ) ) {
			$url = Utility::uri2url( $url );
			self::debug2( '-fill before rewritten: ' . $url );

			$url_parsed = wp_parse_url( $url );
		}

		$scheme = ! empty( $url_parsed['scheme'] ) ? $url_parsed['scheme'] . ':' : '';

		// Find the mapping url to be replaced to
		if ( empty( $this->_cfg_cdn_mapping[ $mapping_kind ] ) ) {
			return false;
		}
		if ( Base::CDN_MAPPING_FILETYPE !== $mapping_kind ) {
			$final_url = $this->_cfg_cdn_mapping[ $mapping_kind ];
		} else {
			// select from file type
			$final_url = $this->_cfg_cdn_mapping[ $postfix ];
			if ( ! $final_url ) {
				return false;
			}
		}

		// If filetype to url is one to many, need to random one
		if ( is_array( $final_url ) ) {
			$final_url = $final_url[ array_rand( $final_url ) ];
		}

		// Now lets replace CDN url
		foreach ( $this->_cfg_url_ori as $v ) {
			if ( false !== strpos( $v, '*' ) ) {
				$url = preg_replace( '#' . $scheme . $v . '#iU', $final_url, $url );
			} else {
				$url = str_replace( $scheme . $v, $final_url, $url );
			}
		}
		self::debug2( '-rewritten: ' . $url );

		return $url;
	}

	/**
	 * Check if the given URL matches any configured "original" URLs for CDN.
	 *
	 * @since 2.1
	 * @access private
	 *
	 * @param string $url URL to test.
	 * @return bool True if URL is one of the originals.
	 */
	private function _is_ori_url( $url ) {
		$url_parsed = wp_parse_url( $url );

		$scheme = ! empty( $url_parsed['scheme'] ) ? $url_parsed['scheme'] . ':' : '';

		foreach ( $this->_cfg_url_ori as $v ) {
			$needle = $scheme . $v;
			if ( false !== strpos( $v, '*' ) ) {
				if ( preg_match( '#' . $needle . '#iU', $url ) ) {
					return true;
				}
			} elseif ( 0 === strpos( $url, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the host is one of the CDN mapping hosts.
	 *
	 * @since 1.2.3
	 *
	 * @param string $host Hostname to check.
	 * @return bool False when bypassed, otherwise true if internal CDN host.
	 */
	public static function internal( $host ) {
		if ( defined( self::BYPASS ) ) {
			return false;
		}

		$instance = self::cls();

		return in_array( $host, $instance->cdn_mapping_hosts, true ); // todo: can add $this->_is_ori_url() check in future
	}
}
