<?php
/**
 * The optimize class.
 *
 * @since      	1.2.2
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Optimize extends Base {
	const LIB_FILE_CSS_ASYNC = 'assets/js/css_async.min.js';
	const LIB_FILE_WEBFONTLOADER = 'assets/js/webfontloader.min.js';
	const LIB_FILE_JS_DELAY = 'assets/js/js_delay.min.js';

	const ITEM_TIMESTAMP_PURGE_CSS = 'timestamp_purge_css';

	private $content;
	private $content_ori;

	private $cfg_css_min;
	private $cfg_css_comb;
	private $cfg_js_min;
	private $cfg_js_comb;
	private $cfg_css_async;
	private $cfg_js_defer;
	private $cfg_js_defer_exc = false;
	private $cfg_ggfonts_async;
	private $_conf_css_font_display;
	private $cfg_ggfonts_rm;

	private $dns_prefetch;
	private $_ggfonts_urls = array();
	private $_ccss;
	private $_ucss = false;

	private $__optimizer;

	private $html_foot = ''; // The html info append to <body>
	private $html_head = ''; // The html info prepend to <body>

	private static $_var_i = 0;
	private $_var_preserve_js = array();
	private $_request_url;

	private $i2 = 0;

	/**
	 * Constructor
	 * @since  4.0
	 */
	public function __construct() {
		Debug2::debug( '[Optm] init' );
		$this->__optimizer = $this->cls( 'Optimizer' );
	}

	/**
	 * Init optimizer
	 *
	 * @since  3.0
	 * @access protected
	 */
	public function init() {
		$this->cfg_css_async = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_CSS_ASYNC );
		if ( $this->cfg_css_async ) {
			if ( ! $this->conf( self::O_API_KEY ) ) {
				Debug2::debug( '[Optm] ❌ CCSS set to OFF due to missing domain key' );
				$this->cfg_css_async = false;
			}
			if ( ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_UCSS ) ) && $this->conf( self::O_OPTM_UCSS_INLINE ) ) {
				Debug2::debug( '[Optm] ❌ CCSS set to OFF due to UCSS Inline' );
				$this->cfg_css_async = false;
			}
		}
		$this->cfg_js_defer = $this->conf( self::O_OPTM_JS_DEFER );
		if ( defined( 'LITESPEED_GUEST_OPTM' ) ) {
			$this->cfg_js_defer = 2;
		}

		// To remove emoji from WP
		if ( $this->conf( self::O_OPTM_EMOJI_RM ) ) {
			$this->_emoji_rm();
		}

		if ( $this->conf( self::O_OPTM_QS_RM ) ) {
			add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 999 );
			add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 999 );
		}

		// GM JS exclude @since 4.1
		if ( defined( 'LITESPEED_GUEST_OPTM' ) ) {
			$this->cfg_js_defer_exc = apply_filters( 'litespeed_optm_gm_js_exc', $this->conf( self::O_OPTM_GM_JS_EXC ) );
		}
		else {
			/**
			 * Exclude js from deferred setting
			 * @since 1.5
			 */
			if ( $this->cfg_js_defer ) {
				add_filter( 'litespeed_optm_js_defer_exc', array( $this->cls( 'Data' ), 'load_js_defer_exc' ) );
				$this->cfg_js_defer_exc = apply_filters( 'litespeed_optm_js_defer_exc', $this->conf( self::O_OPTM_JS_DEFER_EXC ) );
			}
		}

		/**
		 * Add vary filter for Role Excludes
		 * @since  1.6
		 */
		add_filter( 'litespeed_vary', array( $this, 'vary_add_role_exclude' ) );

		/**
		 * Prefetch DNS
		 * @since 1.7.1
		 */
		$this->_dns_prefetch_init();

		add_filter( 'litespeed_buffer_finalize', array( $this, 'finalize' ), 20 );
	}

	/**
	 * Exclude role from optimization filter
	 *
	 * @since  1.6
	 * @access public
	 */
	public function vary_add_role_exclude( $vary ) {
		if ( $this->cls( 'Conf' )->in_optm_exc_roles() ) {
			$vary[ 'role_exclude_optm' ] = 1;
		}

		return $vary;
	}

	/**
	 * Remove emoji from WP
	 *
	 * @since  1.4
	 * @since  2.9.8 Changed to private
	 * @access private
	 */
	private function _emoji_rm() {
		remove_action( 'wp_head' , 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts' , 'print_emoji_detection_script' );
		remove_filter( 'the_content_feed' , 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss' , 'wp_staticize_emoji' );
		/**
		 * Added for better result
		 * @since  1.6.2.1
		 */
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  2.1
	 * @access public
	 */
	public function rm_cache_folder( $subsite_id = false ) {
		if ( $subsite_id ) {
			file_exists( LITESPEED_STATIC_DIR . '/css/' . $subsite_id ) && File::rrmdir( LITESPEED_STATIC_DIR . '/css/' . $subsite_id );
			file_exists( LITESPEED_STATIC_DIR . '/js/' . $subsite_id ) && File::rrmdir( LITESPEED_STATIC_DIR . '/js/' . $subsite_id );
			return;
		}

		file_exists( LITESPEED_STATIC_DIR . '/css' ) && File::rrmdir( LITESPEED_STATIC_DIR . '/css' );
		file_exists( LITESPEED_STATIC_DIR . '/js' ) && File::rrmdir( LITESPEED_STATIC_DIR . '/js' );
	}

	/**
	 * Remove QS
	 *
	 * @since  1.3
	 * @access public
	 */
	public function remove_query_strings( $src ) {
		if ( strpos( $src, '_litespeed_rm_qs=0' ) || strpos( $src, '/recaptcha' ) ) {
			return $src;
		}

		if ( ! Utility::is_internal_file( $src ) ) {
			return $src;
		}

		if ( strpos( $src, '.js?' ) !== false || strpos( $src, '.css?' ) !== false ) {
			$src = preg_replace( '/\?.*/', '', $src );
		}

		return $src;
	}

	/**
	 * Run optimize process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * @since  1.2.2
	 * @access public
	 * @return  string The content that is after optimization
	 */
	public function finalize( $content ) {
		if ( defined( 'LITESPEED_NO_PAGEOPTM' ) ) {
			Debug2::debug2( '[Optm] bypass: NO_PAGEOPTM const' );
			return $content;
		}

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			Debug2::debug( '[Optm] bypass: Not frontend HTML type' );
			return $content;
		}

		if ( ! defined( 'LITESPEED_GUEST_OPTM' ) ) {
			if ( ! Control::is_cacheable() ) {
				Debug2::debug( '[Optm] bypass: Not cacheable' );
				return $content;
			}

			// Check if hit URI excludes
			$result = Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $this->conf( self::O_OPTM_EXC ) );
			if ( $result ) {
				Debug2::debug( '[Optm] bypass: hit URI Excludes setting: ' . $result );
				return $content;
			}
		}

		Debug2::debug( '[Optm] start' );

		$this->content_ori = $this->content = $content;

		$this->_optimize();
		return $this->content;
	}

	/**
	 * Optimize css src
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _optimize() {
		global $wp;
		$this->_request_url = home_url( $wp->request );

		$this->cfg_css_min = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_CSS_MIN );
		$this->cfg_css_comb = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_CSS_COMB );
		$this->cfg_js_min = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_JS_MIN );
		$this->cfg_js_comb = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_JS_COMB );
		$this->cfg_ggfonts_rm = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_GGFONTS_RM );
		$this->cfg_ggfonts_async = ! defined( 'LITESPEED_GUEST_OPTM' ) && $this->conf( self::O_OPTM_GGFONTS_ASYNC ); // forced rm already
		$this->_conf_css_font_display = ! defined( 'LITESPEED_GUEST_OPTM' ) && $this->conf( self::O_OPTM_CSS_FONT_DISPLAY );

		if ( ! $this->cls( 'Router' )->can_optm() ) {
			Debug2::debug( '[Optm] bypass: admin/feed/preview' );
			return;
		}

		if ( $this->cfg_css_async ) {
			$this->_ccss = $this->cls( 'CSS' )->prepare_ccss();
			if ( ! $this->_ccss ) {
				Debug2::debug( '[Optm] ❌ CCSS set to OFF due to CCSS not generated yet' );
				$this->cfg_css_async = false;
			}
			else if ( strpos( $this->_ccss, '<style id="litespeed-ccss" data-error' ) === 0 ) {
				Debug2::debug( '[Optm] ❌ CCSS set to OFF due to CCSS failed to generate' );
				$this->cfg_css_async = false;
			}
		}

		do_action( 'litespeed_optm' );

		// Parse css from content
		$src_list = false;
		if ( $this->cfg_css_min || $this->cfg_css_comb || $this->cfg_ggfonts_rm || $this->cfg_css_async || $this->cfg_ggfonts_async  || $this->_conf_css_font_display ) {
			add_filter( 'litespeed_optimize_css_excludes', array( $this->cls( 'Data' ), 'load_css_exc' ) );
			list( $src_list, $html_list ) = $this->_parse_css();
		}

		// css optimizer
		if ( $this->cfg_css_min || $this->cfg_css_comb ) {

			if ( $src_list ) {
				// IF combine
				if ( $this->cfg_css_comb ) {
					// Check if has inline UCSS enabled or not
					if ( ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_UCSS ) ) && $this->conf( self::O_OPTM_UCSS_INLINE ) ) {
						$filename = $this->cls( 'CSS' )->load_ucss( $this->_request_url, true );
						if ( $filename ) {
							$filepath_prefix = $this->_build_filepath_prefix( 'ucss' );
							$this->_ucss = File::read( LITESPEED_STATIC_DIR . $filepath_prefix . $filename );

							// Drop all css
							$this->content = str_replace( $html_list, '', $this->content );
						}
					}

					if ( ! $this->_ucss ) {
						$url = $this->_build_hash_url( $src_list );

						if ( $url ) {
							// Handle css async load
							if ( $this->cfg_css_async ) {
								$this->html_head .= '<link rel="preload" data-asynced="1" data-optimized="2" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" href="' . $url . '" />'; // todo: How to use " in attr wrapper "
							}
							else {
								$this->html_head .= '<link data-optimized="2" rel="stylesheet" href="' . $url . '" />';// use 2 as combined
							}

							// Move all css to top
							$this->content = str_replace( $html_list, '', $this->content );
						}
					}
				}
				// Only minify
				elseif ( $this->cfg_css_min ) {
					// will handle async css load inside
					$this->_src_queue_handler( $src_list, $html_list );
				}
				// Only HTTP2 push
				else {
					foreach ( $src_list as $src_info ) {
						if ( ! empty( $src_info[ 'inl' ] ) ) {
							continue;
						}
					}
				}
			}
		}

		// Handle css lazy load if not handled async loaded yet
		if ( $this->cfg_css_async && ! $this->cfg_css_min && ! $this->cfg_css_comb ) {
			// async html
			$html_list_async = $this->_async_css_list( $html_list, $src_list );

			// Replace async css
			$this->content = str_replace( $html_list, $html_list_async, $this->content );

		}

		// Parse js from buffer as needed
		$src_list = false;
		if ( $this->cfg_js_min || $this->cfg_js_comb || $this->cfg_js_defer ) {
			add_filter( 'litespeed_optimize_js_excludes', array( $this->cls( 'Data' ), 'load_js_exc' ) );
			list( $src_list, $html_list ) = $this->_parse_js();
		}

		// js optimizer
		if ( $src_list ) {
			// IF combine
			if ( $this->cfg_js_comb ) {
				$url = $this->_build_hash_url( $src_list, 'js' );
				if ( $url ) {
					$this->html_foot .= $this->_build_js_tag( $url );

					// Will move all JS to bottom combined one
					$this->content = str_replace( $html_list, '', $this->content );
				}
			}
			// Only minify
			elseif ( $this->cfg_js_min ) {
				// Will handle js defer inside
				$this->_src_queue_handler( $src_list, $html_list, 'js' );
			}
			// Only HTTP2 push and Defer
			else {
				foreach ( $src_list as $k => $src_info ) {
					// Inline JS
					if ( ! empty( $src_info[ 'inl' ] ) ) {
						if ( $this->cfg_js_defer ) {
							$attrs = ! empty( $src_info[ 'attrs' ] ) ? $src_info[ 'attrs' ] : '';
							$deferred = $this->_js_inline_defer( $src_info[ 'src' ], $attrs );
							if ( $deferred ) {
								$this->content = str_replace( $html_list[ $k ], $deferred, $this->content );
							}
						}
					}
					// JS files
					else {
						if ( $this->cfg_js_defer ) {
							$deferred = $this->_js_defer( $html_list[ $k ], $src_info[ 'src' ] );
							if ( $deferred ) {
								$this->content = str_replace( $html_list[ $k ], $deferred, $this->content );
							}
						}
					}
				}
			}
		}

		// Append JS inline var for preserved ESI
		// Shouldn't give any optm (defer/delay) @since 4.4
		if ( $this->_var_preserve_js ) {
			$this->html_head .= '<script>var ' . implode( ',', $this->_var_preserve_js ) . ';</script>';
			Debug2::debug2( '[Optm] Inline JS defer vars', $this->_var_preserve_js );
		}

		// Append async compatibility lib to head
		if ( $this->cfg_css_async ) {
			// Inline css async lib
			if ( $this->conf( self::O_OPTM_CSS_ASYNC_INLINE ) ) {
				$this->html_head .= $this->_build_js_inline( File::read( LSCWP_DIR . self::LIB_FILE_CSS_ASYNC ), true );
			}
			else {
				$css_async_lib_url = LSWCP_PLUGIN_URL . self::LIB_FILE_CSS_ASYNC;
				$this->html_head .= $this->_build_js_tag( $css_async_lib_url, 'litespeed-css-async-lib' ); // Don't exclude it from defer for now
			}
		}

		/**
		 * Handle google fonts async
		 * This will result in a JS snippet in head, so need to put it in the end to avoid being replaced by JS parser
		 */
		$this->_async_ggfonts();

		/**
		 * Font display optm
		 * @since  3.0
		 */
		$this->_font_optm();

		// Inject JS Delay lib
		$this->_maybe_js_delay();

		/**
		 * HTML Lazyload
		 */
		if ( $this->conf( self::O_OPTM_HTML_LAZY ) ) {
			$this->html_head = $this->cls( 'CSS' )->prepare_html_lazy() . $this->html_head;
		}

		// Maybe prepend inline UCSS
		if ( $this->_ucss ) {
			$this->html_head = '<style id="litespeed-ucss">' . $this->_ucss . '</style>' . $this->html_head;
		}

		// Check if there is any critical css rules setting
		if ( $this->cfg_css_async && $this->_ccss ) {
			$this->html_head = $this->_ccss . $this->html_head;
		}

		// Replace html head part
		$this->html_head = apply_filters( 'litespeed_optm_html_head', $this->html_head );
		if ( $this->html_head ) {
			if ( apply_filters( 'litespeed_optm_html_after_head', false ) ) {
				$this->content = str_replace( '</head>', $this->html_head . '</head>', $this->content );
			}
			else {
				// Put header content to be after charset
				if ( strpos( $this->content, '<meta charset' ) !== false ) {
					$this->content = preg_replace( '#<meta charset([^>]*)>#isU', '<meta charset$1>' . $this->html_head , $this->content, 1 );
				}
				else {
					$this->content = preg_replace( '#<head([^>]*)>#isU', '<head$1>' . $this->html_head , $this->content, 1 );
				}
			}
		}

		// Replace html foot part
		$this->html_foot = apply_filters( 'litespeed_optm_html_foot', $this->html_foot );
		if ( $this->html_foot ) {
			$this->content = str_replace( '</body>', $this->html_foot . '</body>' , $this->content );
		}

		// Drop noscript if enabled
		if ( $this->conf( self::O_OPTM_NOSCRIPT_RM ) ) {
			// $this->content = preg_replace( '#<noscript>.*</noscript>#isU', '', $this->content );
		}

		// HTML minify
		if ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_HTML_MIN ) ) {
			$this->content = $this->__optimizer->html_min( $this->content );
		}
	}

	/**
	 * Build a full JS tag
	 *
	 * @since  4.0
	 */
	private function _build_js_tag( $src ) {
		if ( $this->cfg_js_defer === 2 ) {
			return '<script data-optimized="1" type="litespeed/javascript" data-i="' . ++$this->i2 . '" data-src="' . $src . '"></script>';
		}

		if ( $this->cfg_js_defer ) {
			return '<script data-optimized="1" src="' . $src . '" defer></script>';
		}

		return '<script data-optimized="1" src="' . $src . '"></script>';
	}

	/**
	 * Build a full inline JS snippet
	 *
	 * @since  4.0
	 */
	private function _build_js_inline( $script, $minified = false ) {
		if ( $this->cfg_js_defer ) {
			$deferred = $this->_js_inline_defer( $script, false, $minified );
			if ( $deferred ) {
				return $deferred;
			}
		}

		return '<script>' . $script . '</script>';
	}

	/**
	 * Load JS delay lib
	 *
	 * @since 4.0
	 */
	private function _maybe_js_delay() {
		if ( $this->cfg_js_defer !== 2 ) {
			return;
		}

		$this->html_foot .= '<script>' . File::read( LSCWP_DIR . self::LIB_FILE_JS_DELAY ) . '</script>';
	}

	/**
	 * Google font async
	 *
	 * @since 2.7.3
	 * @access private
	 */
	private function _async_ggfonts() {
		if ( ! $this->cfg_ggfonts_async || ! $this->_ggfonts_urls ) {
			return;
		}

		Debug2::debug2( '[Optm] google fonts async found: ', $this->_ggfonts_urls );

		$html = '<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />';

		/**
		 * Append fonts
		 *
		 * Could be multiple fonts
		 *
		 * 	<link rel='stylesheet' href='//fonts.googleapis.com/css?family=Open+Sans%3A400%2C600%2C700%2C800%2C300&#038;ver=4.9.8' type='text/css' media='all' />
		 *	<link rel='stylesheet' href='//fonts.googleapis.com/css?family=PT+Sans%3A400%2C700%7CPT+Sans+Narrow%3A400%7CMontserrat%3A600&#038;subset=latin&#038;ver=4.9.8' type='text/css' media='all' />
		 *		-> family: PT Sans:400,700|PT Sans Narrow:400|Montserrat:600
		 *	<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,300,300italic,400italic,600,700,900&#038;subset=latin%2Clatin-ext' />
		 */
		$script ='WebFontConfig={google:{families:[';

		$families = array();
		foreach ( $this->_ggfonts_urls as $v ) {
			$qs = wp_specialchars_decode( $v );
			$qs = urldecode( $qs );
			$qs = parse_url( $qs, PHP_URL_QUERY );
			parse_str( $qs, $qs );

			if ( empty( $qs[ 'family' ] ) ) {
				Debug2::debug( '[Optm] ERR ggfonts failed to find family: ' . $v );
				continue;
			}

			$subset = empty( $qs[ 'subset' ] ) ? '' : ':' . $qs[ 'subset' ];

			foreach ( array_filter( explode( '|', $qs[ 'family' ] ) ) as $v2 ) {
				$families[] = $v2 . $subset;
			}
		}

		$script .= '"' . implode( '","', $families ) . ( $this->_conf_css_font_display ? '&display=swap' : '' ) . '"';

		$script .= ']}};';

		// if webfontloader lib was loaded before WebFontConfig variable, call WebFont.load
		$script .= 'if ( typeof WebFont === "object" && typeof WebFont.load === "function" ) { WebFont.load( WebFontConfig ); }';

		$html .= $this->_build_js_inline( $script );

		// https://cdnjs.cloudflare.com/ajax/libs/webfont/1.6.28/webfontloader.js
		$webfont_lib_url = LSWCP_PLUGIN_URL . self::LIB_FILE_WEBFONTLOADER;

		// default async, if js defer set use defer
		$html .= $this->_build_js_tag( $webfont_lib_url );

		// Put this in the very beginning for preconnect
		$this->html_head = $html . $this->html_head;
	}

	/**
	 * Font optm
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _font_optm() {
		if ( ! $this->_conf_css_font_display || ! $this->_ggfonts_urls ) {
			return;
		}

		Debug2::debug2( '[Optm] google fonts optm ', $this->_ggfonts_urls );

		foreach ( $this->_ggfonts_urls as $v ) {
			if ( strpos( $v, 'display=' ) ) {
				continue;
			}
			$this->html_head = str_replace( $v, $v . '&#038;display=swap', $this->html_head );
			$this->html_foot = str_replace( $v, $v . '&#038;display=swap', $this->html_foot );
			$this->content = str_replace( $v, $v . '&#038;display=swap', $this->content );
		}
	}

	/**
	 * Prefetch DNS
	 *
	 * @since 1.7.1
	 * @access private
	 */
	private function _dns_prefetch_init() {
		// Widely enable link DNS prefetch
		if ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_DNS_PREFETCH_CTRL ) ) {
			@header( 'X-DNS-Prefetch-Control: on' );
		}

		$this->dns_prefetch = $this->conf( self::O_OPTM_DNS_PREFETCH );
		if ( ! $this->dns_prefetch ) {
			return;
		}

		if ( function_exists( 'wp_resource_hints' ) ) {
			add_filter( 'wp_resource_hints', array( $this, 'dns_prefetch_filter' ), 10, 2 );
		}
		else {
			add_action( 'litespeed_optm', array( $this, 'dns_prefetch_output' ) );
		}
	}

	/**
	 * Prefetch DNS hook for WP
	 *
	 * @since 1.7.1
	 * @access public
	 */
	public function dns_prefetch_filter( $urls, $relation_type ) {
		if ( $relation_type !== 'dns-prefetch' ) {
			return $urls;
		}

		foreach ( $this->dns_prefetch as $v ) {
			if ( $v ) {
				$urls[] = $v;
			}
		}

		return $urls;
	}

	/**
	 * Prefetch DNS
	 *
	 * @since 1.7.1
	 * @access public
	 */
	public function dns_prefetch_output() {
		foreach ( $this->dns_prefetch as $v ) {
			if ( $v ) {
				$this->html_head .= '<link rel="dns-prefetch" href="' . $v . '" />';
			}
		}
	}

	/**
	 * Run minify with src queue list
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _src_queue_handler( $src_list, $html_list, $file_type = 'css' ) {
		$html_list_ori = $html_list;

		$can_webp = ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE ) ) && $this->cls( 'Media' )->webp_support();

		$tag = $file_type == 'css' ? 'link' : 'script';
		foreach ( $src_list as $key => $src_info ) {
			// Minify inline CSS/JS
			if ( ! empty( $src_info[ 'inl' ] ) ) {
				if ( $file_type == 'css' ) {
					$code = Optimizer::minify_css( $src_info[ 'src' ] );
					$can_webp && $code = $this->cls( 'Media' )->replace_background_webp( $code );
					$snippet = str_replace( $src_info[ 'src' ], $code, $html_list[ $key ] );
				}
				else {
					// Inline defer JS
					if ( $this->cfg_js_defer ) {
						$attrs = ! empty( $src_info[ 'attrs' ] ) ? $src_info[ 'attrs' ] : '';
						$snippet = $this->_js_inline_defer( $src_info[ 'src' ], $attrs ) ?: $html_list[ $key ];
					}
					else {
						$code = Optimizer::minify_js( $src_info[ 'src' ] );
						$snippet = str_replace( $src_info[ 'src' ], $code, $html_list[ $key ] );
					}
				}

			}
			// CSS/JS files
			else {
				$url = $this->_build_single_hash_url( $src_info[ 'src' ], $file_type );
				if ( $url ) {
					$snippet = str_replace( $src_info[ 'src' ], $url, $html_list[ $key ] );
				}

				// Handle css async load
				if ( $file_type == 'css' && $this->cfg_css_async ) {
					$snippet = $this->_async_css( $snippet );
				}

				// Handle js defer
				if ( $file_type === 'js' && $this->cfg_js_defer ) {
					$snippet = $this->_js_defer( $snippet, $src_info[ 'src' ] ) ?: $snippet;
				}
			}

			$snippet = str_replace( "<$tag ", '<' . $tag . ' data-optimized="1" ', $snippet );
			$html_list[ $key ] = $snippet;
		}

		$this->content = str_replace( $html_list_ori, $html_list, $this->content );
	}

	/**
	 * Build a single URL mapped filename (This will not save in DB)
	 * @since  4.0
	 */
	private function _build_single_hash_url( $src, $file_type = 'css' ) {
		$content = $this->__optimizer->load_file( $src, $file_type );

		$is_min = $this->__optimizer->is_min( $src );

		$content = $this->__optimizer->optm_snippet( $content, $file_type, ! $is_min, $src );

		// Save to file
		$filename = $file_type . '/' . md5( $this->remove_query_strings( $src ) ) . '.' . $file_type;
		$static_file = LITESPEED_STATIC_DIR . '/' . $filename;
		File::save( $static_file, $content, true );

		// QS is required as $src may contains version info
		$qs_hash = substr( md5( $src ), -5 );
		return LITESPEED_STATIC_URL . "/$filename?ver=$qs_hash";
	}

	/**
	 * Generate full URL path with hash for a list of src
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _build_hash_url( $src_list, $file_type = 'css' ) {
		// $url_sensitive = $this->conf( self::O_OPTM_CSS_UNIQUE ) && $file_type == 'css'; // If need to keep unique CSS per URI

		// Replace preserved ESI (before generating hash)
		if ( $file_type == 'js' ) {
			foreach ( $src_list as $k => $v ) {
				if ( empty( $v[ 'inl' ] ) ) {
					continue;
				}
				$src_list[ $k ][ 'src' ] = $this->_preserve_esi( $v[ 'src' ] );
			}
		}

		$minify = $file_type === 'css' ? $this->cfg_css_min : $this->cfg_js_min;
		$filename_info = $this->__optimizer->serve( $this->_request_url, $file_type, $minify, $src_list );

		if ( ! $filename_info ) {
			return false; // Failed to generate
		}

		list( $filename, $type ) = $filename_info;

		// Add cache tag in case later file deleted to avoid lscache served stale non-existed files @since 4.4.1
		Tag::add( Tag::TYPE_MIN . '.' . $filename );

		$qs_hash = substr( md5( self::get_option( self::ITEM_TIMESTAMP_PURGE_CSS ) ), -5 );
		// As filename is alreay realted to filecon md5, no need QS anymore
		$filepath_prefix = $this->_build_filepath_prefix( $type );
		return LITESPEED_STATIC_URL . $filepath_prefix . $filename . '?ver=' . $qs_hash;
	}

	/**
	 * Parse js src
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _parse_js() {
		$excludes = apply_filters( 'litespeed_optimize_js_excludes', $this->conf( self::O_OPTM_JS_EXC ) );

		$combine_ext_inl = $this->conf( self::O_OPTM_JS_COMB_EXT_INL );

		$src_list = array();
		$html_list = array();

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content );
		preg_match_all( '#<script([^>]*)>(.*)</script>#isU', $content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs = empty( $match[ 1 ] ) ? array() : Utility::parse_attr( $match[ 1 ] );

			if ( isset( $attrs[ 'data-optimized' ] ) ) {
				continue;
			}
			if ( ! empty( $attrs[ 'data-no-optimize' ] ) ) {
				continue;
			}
			if ( ! empty( $attrs[ 'data-cfasync' ] ) && $attrs[ 'data-cfasync' ] === 'false'  ) {
				continue;
			}
			if ( ! empty( $attrs[ 'type' ] ) && $attrs[ 'type' ] != 'text/javascript' ) {
				continue;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue;
			}

			$this_src_arr = array();
			// JS files
			if ( ! empty( $attrs[ 'src' ] ) ) {
				// Exclude check
				$js_excluded = Utility::str_hit_array( $attrs[ 'src' ], $excludes );
				$is_internal = Utility::is_internal_file( $attrs[ 'src' ] );
				$is_file = substr( $attrs[ 'src' ], 0, 5 ) != 'data:';
				$ext_excluded = ! $combine_ext_inl && ! $is_internal;
				if ( $js_excluded || $ext_excluded || ! $is_file ) {
					// Maybe defer
					if ( $this->cfg_js_defer ) {
						$deferred = $this->_js_defer( $match[ 0 ], $attrs[ 'src' ] ); // todo: this can't follow the i2 order
						if ( $deferred ) {
							$this->content = str_replace( $match[ 0 ], $deferred, $this->content );
						}
					}

					Debug2::debug2( '[Optm] _parse_js bypassed due to ' . ( $js_excluded ? 'js files excluded [hit] ' . $js_excluded : 'external js' ) );
					continue;
				}

				if ( strpos( $attrs[ 'src' ], '/localres/' ) !== false ) {
					continue;
				}

				if ( strpos( $attrs[ 'src' ], 'instant_click' ) !== false ) {
					continue;
				}

				$this_src_arr[ 'src' ] = $attrs[ 'src' ];
			}
			// Inline JS
			elseif ( ! empty( $match[ 2 ] ) ) {
				// Debug2::debug( '🌹🌹🌹 ' . $match[2] . '🌹' );
				// Exclude check
				$js_excluded = Utility::str_hit_array( $match[ 2 ], $excludes );
				if ( $js_excluded || ! $combine_ext_inl ) {
					// Maybe defer
					if ( $this->cfg_js_defer ) {
						$deferred = $this->_js_inline_defer( $match[ 2 ], $match[ 1 ] );
						if ( $deferred ) {
							$this->content = str_replace( $match[ 0 ], $deferred, $this->content );
						}
					}
					Debug2::debug2( '[Optm] _parse_js bypassed due to ' . ( $js_excluded ? 'js excluded [hit] ' . $js_excluded : 'inline js' ) );
					continue;
				}

				$this_src_arr[ 'inl' ] = true;
				$this_src_arr[ 'src' ] = $match[ 2 ];
				if ( $match[ 1 ] ) {
					$this_src_arr[ 'attrs' ] = $match[ 1 ];
				}
			}
			else { // Compatibility to those who changed src to data-src already
				Debug2::debug2( '[Optm] No JS src or inline JS content' );
				continue;
			}

			$src_list[] = $this_src_arr;
			$html_list[] = $match[ 0 ];
		}

		return array( $src_list, $html_list );
	}

	/**
	 * Inline JS defer
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _js_inline_defer( $con, $attrs = false, $minified = false ) {
		if ( strpos( $attrs, 'data-no-defer' ) !== false ) {
			Debug2::debug2( '[Optm] bypass: attr api data-no-defer' );
			return false;
		}

		$hit = Utility::str_hit_array( $con, $this->cfg_js_defer_exc );
		if ( $hit ) {
			Debug2::debug2( '[Optm] inline js defer excluded [setting] ' . $hit );
			return false;
		}

		$con = trim( $con );
		// Minify JS first
		if ( ! $minified ) { // && $this->cfg_js_defer !== 2
			$con = Optimizer::minify_js( $con );
		}

		if ( ! $con ) {
			return false;
		}

		// Check if the content contains ESI nonce or not
		$con = $this->_preserve_esi( $con );

		if ( $this->cfg_js_defer === 2 ) {
			// Drop type attribute from $attrs
			if ( strpos( $attrs, ' type=' ) !== false ) {
				$attrs = preg_replace( '# type=([\'"])([^\1]+)\1#isU', '', $attrs );
			}
			return '<script' . $attrs . ' type="litespeed/javascript" data-i="' . ++$this->i2 . '">' . $con . '</script>';
			// return '<script' . $attrs . ' type="litespeed/javascript" data-i="' . $this->i2 . '" src="data:text/javascript;base64,' . base64_encode( $con ) . '"></script>';
			// return '<script' . $attrs . ' type="litespeed/javascript">' . $con . '</script>';
		}

		return '<script' . $attrs . ' src="data:text/javascript;base64,' . base64_encode( $con ) . '" defer></script>';
	}

	/**
	 * Replace ESI to JS inline var (mainly used to avoid nonce timeout)
	 *
	 * @since  3.5.1
	 */
	private function _preserve_esi( $con ) {
		$esi_placeholder_list = $this->cls( 'ESI' )->contain_preserve_esi( $con );
		if ( ! $esi_placeholder_list ) {
			return $con;
		}

		foreach ( $esi_placeholder_list as $esi_placeholder ) {
			$js_var = '__litespeed_var_' . ( self::$_var_i ++ ) . '__';
			$con = str_replace( $esi_placeholder, $js_var, $con );
			$this->_var_preserve_js[] = $js_var . '=' . $esi_placeholder;
		}

		return $con;
	}

	/**
	 * Parse css src and remove to-be-removed css
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_css() {
		$excludes = apply_filters( 'litespeed_optimize_css_excludes', $this->conf( self::O_OPTM_CSS_EXC ) );

		$combine_ext_inl = $this->conf( self::O_OPTM_CSS_COMB_EXT_INL );

		$css_to_be_removed = apply_filters( 'litespeed_optm_css_to_be_removed', array() );

		$src_list = array();
		$html_list = array();

		// $dom = new \PHPHtmlParser\Dom;
		// $dom->load( $content );return $val;
		// $items = $dom->find( 'link' );

		$content = preg_replace( array( '#<!--.*-->#sU', '#<script([^>]*)>.*</script>#isU', '#<noscript([^>]*)>.*</noscript>#isU' ), '', $this->content );
		preg_match_all( '#<link ([^>]+)/?>|<style([^>]*)>([^<]+)</style>#isU', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue;
			}

			if ( $exclude = Utility::str_hit_array( $match[ 0 ], $excludes ) ) {
				Debug2::debug2( '[Optm] _parse_css bypassed exclude ' . $exclude );
				continue;
			}

			$this_src_arr = array();
			if ( strpos( $match[ 0 ], '<link' ) === 0 ) {
				$attrs = Utility::parse_attr( $match[ 1 ] );
				if ( empty( $attrs[ 'rel' ] ) || $attrs[ 'rel' ] !== 'stylesheet' ) {
					continue;
				}
				if ( empty( $attrs[ 'href' ] ) ) {
					continue;
				}

				// Check if need to remove this css
				if ( Utility::str_hit_array( $attrs[ 'href' ], $css_to_be_removed ) ) {
					Debug2::debug( '[Optm] rm css snippet ' . $attrs[ 'href' ] );
					// Delete this css snippet from orig html
					$this->content = str_replace( $match[ 0 ], '', $this->content );

					continue;
				}

				// Check Google fonts hit
				if ( strpos( $attrs[ 'href' ], 'fonts.googleapis.com' ) !== false ) {
					/**
					 * For async gg fonts, will add webfont into head, hence remove it from buffer and store the matches to use later
					 * @since  2.7.3
					 * @since  3.0 For font display optm, need to parse google fonts URL too
					 */
					if ( ! in_array( $attrs[ 'href' ], $this->_ggfonts_urls ) ) {
						$this->_ggfonts_urls[] = $attrs[ 'href' ];
					}

					if ( $this->cfg_ggfonts_rm || $this->cfg_ggfonts_async ) {
						Debug2::debug( '[Optm] rm css snippet [Google fonts] ' . $attrs[ 'href' ] );
						$this->content = str_replace( $match[ 0 ], '', $this->content );

						continue;
					}
				}

				if ( isset( $attrs[ 'data-optimized' ] ) ) {
					// $this_src_arr[ 'exc' ] = true;
					continue;
				}
				elseif ( ! empty( $attrs[ 'data-no-optimize' ] ) ) {
					// $this_src_arr[ 'exc' ] = true;
					continue;
				}

				$is_internal = Utility::is_internal_file( $attrs[ 'href' ] );
				$ext_excluded = ! $combine_ext_inl && ! $is_internal;
				if ( $ext_excluded ) {
					Debug2::debug2( '[Optm] Bypassed due to external link' );
					// Maybe defer
					if ( $this->cfg_css_async ) {
						$snippet = $this->_async_css( $match[ 0 ] );
						if ( $snippet != $match[ 0 ] ) {
							$this->content = str_replace( $match[ 0 ], $snippet, $this->content );
						}
					}

					continue;
				}

				if ( ! empty( $attrs[ 'media' ] ) && $attrs[ 'media' ] !== 'all' ) {
					$this_src_arr[ 'media' ] = $attrs[ 'media' ];
				}

				$this_src_arr[ 'src' ] = $attrs[ 'href' ];
			}
			else { // Inline style
				if ( ! $combine_ext_inl ) {
					Debug2::debug2( '[Optm] Bypassed due to inline' );
					continue;
				}

				$attrs = Utility::parse_attr( $match[ 2 ] );

				if ( ! empty( $attrs[ 'data-no-optimize' ] ) ) {
					continue;
				}

				if ( ! empty( $attrs[ 'media' ] ) && $attrs[ 'media' ] !== 'all' ) {
					$this_src_arr[ 'media' ] = $attrs[ 'media' ];
				}

				$this_src_arr[ 'inl' ] = true;
				$this_src_arr[ 'src' ] = $match[ 3 ];
			}

			$src_list[] = $this_src_arr;

			$html_list[] = $match[ 0 ];
		}

		return array( $src_list, $html_list );
	}

	/**
	 * Replace css to async loaded css
	 *
	 * @since  1.3
	 * @access private
	 */
	private function _async_css_list( $html_list, $src_list ) {
		foreach ( $html_list as $k => $ori ) {
			if ( ! empty( $src_list[ $k ][ 'inl' ] ) ) {
				continue;
			}

			$html_list[ $k ] = $this->_async_css( $ori );
		}
		return $html_list;
	}

	/**
	 * Async CSS snippet
	 * @since 3.5
	 */
	private function _async_css( $ori ) {
		if ( strpos( $ori, 'data-asynced' ) !== false ) {
			Debug2::debug2( '[Optm] bypass: attr data-asynced exist' );
			return $ori;
		}

		if ( strpos( $ori, 'data-no-async' ) !== false ) {
			Debug2::debug2( '[Optm] bypass: attr api data-no-async' );
			return $ori;
		}

		// async replacement
		$v = str_replace( 'stylesheet', 'preload', $ori );
		$v = str_replace( '<link', '<link data-asynced="1" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" ', $v );
		// Append to noscript content
		if ( ! defined( 'LITESPEED_GUEST_OPTM' ) && ! $this->conf( self::O_OPTM_NOSCRIPT_RM ) ) {
			$v .= '<noscript>' . preg_replace( '/ id=\'[\w-]+\' /U', ' ', $ori ) . '</noscript>';
		}

		return $v;
	}

	/**
	 * Defer JS snippet
	 *
	 * @since  3.5
	 */
	private function _js_defer( $ori, $src ) {
		if ( strpos( $ori, ' async' ) !== false ) {
			$ori = str_replace( ' async', '', $ori );
		}

		if ( strpos( $ori, 'defer' ) !== false ) {
			return false;
		}
		if ( strpos( $ori, 'data-deferred' ) !== false ) {
			Debug2::debug2( '[Optm] bypass: attr data-deferred exist' );
			return false;
		}
		if ( strpos( $ori, 'data-no-defer' ) !== false ) {
			Debug2::debug2( '[Optm] bypass: attr api data-no-defer' );
			return false;
		}

		/**
		 * Exclude JS from setting
		 * @since 1.5
		 */
		if ( Utility::str_hit_array( $src, $this->cfg_js_defer_exc ) ) {
			Debug2::debug( '[Optm] js defer exclude ' . $src );
			return false;
		}

		if ( $this->cfg_js_defer === 2 ) {
			if ( strpos( $ori, ' type=' ) !== false ) {
				$ori = preg_replace( '# type=([\'"])([^\1]+)\1#isU', '', $ori );
			}
			return str_replace( ' src=', ' type="litespeed/javascript" data-i="' . ++$this->i2 . '" data-src=', $ori );
		}

		return str_replace( '></script>', ' defer data-deferred="1"></script>', $ori );
	}

}
