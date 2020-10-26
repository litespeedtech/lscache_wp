<?php
/**
 * The optimize class.
 *
 * @since      	1.2.2
 * @since  		1.5 Moved into /inc
 * @package  	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Optimize extends Base {
	protected static $_instance;

	const LIB_FILE_CSS_ASYNC = 'assets/js/css_async.min.js';
	const LIB_FILE_WEBFONTLOADER = 'assets/js/webfontloader.min.js';

	const ITEM_TIMESTAMP_PURGE_CSS = 'timestamp_purge_css';

	private $content;
	private $content_ori;
	private $http2_headers = array();

	private $cfg_http2_css;
	private $cfg_http2_js;
	private $cfg_css_min;
	private $cfg_css_comb;
	private $cfg_js_min;
	private $cfg_js_comb;
	private $cfg_css_async;
	private $cfg_js_defer;
	private $cfg_js_inline_defer;
	private $cfg_js_defer_exc = false;
	private $cfg_ggfonts_async;
	private $_conf_css_font_display;
	private $cfg_ttl;
	private $cfg_ggfonts_rm;

	private $dns_prefetch;
	private $_ggfonts_urls = array();
	private $__data;

	private $html_foot = ''; // The html info append to <body>
	private $html_head = ''; // The html info prepend to <body>

	private static $_var_i = 0;
	private $_var_preserve_js = array();

	/**
	 *
	 * @since  1.2.2
	 * @access protected
	 */
	protected function __construct() {
		$this->__data = Data::get_instance();
	}

	/**
	 * Init optimizer
	 *
	 * @since  3.0
	 * @access protected
	 */
	public function init() {
		$this->cfg_css_async = Conf::val( Base::O_OPTM_CSS_ASYNC );
		if ( $this->cfg_css_async && ! Conf::val( Base::O_API_KEY ) ) {
			Debug2::debug( '[Optm] ❌ CCSS set to OFF due to lack of domain key' );
			$this->cfg_css_async = false;
		}
		$this->cfg_js_defer = Conf::val( Base::O_OPTM_JS_DEFER );
		$this->cfg_js_inline_defer = Conf::val( Base::O_OPTM_JS_INLINE_DEFER );

		if ( ! Router::can_optm() ) {
			return;
		}

		// To remove emoji from WP
		if ( Conf::val( Base::O_OPTM_EMOJI_RM ) ) {
			$this->_emoji_rm();
		}

		if ( Conf::val( Base::O_OPTM_QS_RM ) ) {
			add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 999 );
			add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 999 );
		}

		/**
		 * Exclude js from deferred setting
		 * @since 1.5
		 */
		if ( $this->cfg_js_defer || $this->cfg_js_inline_defer ) {
			$this->cfg_js_defer_exc = apply_filters( 'litespeed_optm_js_defer_exc', Conf::val( Base::O_OPTM_JS_DEFER_EXC ) );
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
	}

	/**
	 * Exclude role from optimization filter
	 *
	 * @since  1.6
	 * @access public
	 */
	public function vary_add_role_exclude( $vary ) {
		if ( Conf::get_instance()->in_optm_exc_roles() ) {
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
	 * Check if the request is for static file
	 *
	 * @since  1.2.2
	 * @since  3.0 Renamed func. Changed access to public
	 * @access public
	 */
	public function serve_satic( $uri ) {
		$this->cfg_css_min = Conf::val( Base::O_OPTM_CSS_MIN );
		$this->cfg_css_comb = Conf::val( Base::O_OPTM_CSS_COMB );
		$this->cfg_js_min = Conf::val( Base::O_OPTM_JS_MIN );
		$this->cfg_js_comb = Conf::val( Base::O_OPTM_JS_COMB );
		$this->cfg_ttl = Conf::val( Base::O_OPTM_TTL );

		// If not turn on min files
		if ( ! $this->cfg_css_min && ! $this->cfg_css_comb && ! $this->cfg_js_min && ! $this->cfg_js_comb ) {
			return;
		}

		// try to match `xx.css`
		if ( ! preg_match( '#^(\w+)\.(css|js)#U', $uri, $match ) ) {
			return;
		}

		Debug2::debug( '[Optm] start minifying file' );

		// Proceed css/js file generation
		define( 'LITESPEED_MIN_FILE', true );

		$file_type = $match[ 2 ];

		$static_file = LITESPEED_STATIC_DIR . '/cssjs/' . $match[ 0 ];

		// Even if hit PHP, still check if the file is valid to bypass minify process
		if ( ! file_exists( $static_file ) || time() - filemtime( $static_file ) > $this->cfg_ttl ) {
			$concat_only = ! ( $file_type === 'css' ? $this->cfg_css_min : $this->cfg_js_min );

			$content = Optimizer::get_instance()->serve( $match[ 0 ], $concat_only );

			if ( ! $content ) {
				Debug2::debug( '[Optm] Static file generation bypassed due to empty' );
				return;
			}

			// Generate static file
			File::save( $static_file, $content, true );
			Debug2::debug2( '[Optm] Saved cache to file [path] ' . $static_file );

		}
		else {
			// Load file from file based cache if not expired
			Debug2::debug2( '[Optm] Static file available' );
		}

		$url = LITESPEED_STATIC_URL . '/cssjs/' . $match[ 0 ];

		Debug2::debug( '[Optm] Redirect to ' . $url );

		wp_redirect( $url );
		exit;
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  2.1
	 * @access public
	 */
	public function rm_cache_folder() {
		if ( file_exists( LITESPEED_STATIC_DIR . '/cssjs' ) ) {
			File::rrmdir( LITESPEED_STATIC_DIR . '/cssjs' );
		}
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

		if ( strpos( $src, '.js?' ) !== false || strpos( $src, '.css?' ) !== false ) {
			$src = preg_replace( '/\?.*/', '', $src );
		}

		return $src;
	}

	/**
	 * Check if need db table or not
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function need_db() {
		if ( Conf::val( Base::O_OPTM_CSS_MIN ) ) {
			return true;
		}

		if ( Conf::val( Base::O_OPTM_CSS_COMB ) ) {
			return true;
		}

		if ( Conf::val( Base::O_OPTM_JS_MIN ) ) {
			return true;
		}

		if ( Conf::val( Base::O_OPTM_JS_COMB ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Run optimize process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * @since  1.2.2
	 * @access public
	 * @return  string The content that is after optimization
	 */
	public static function finalize( $content ) {
		if ( defined( 'LITESPEED_MIN_FILE' ) ) {// Must have this to avoid css/js from optimization again ( But can be removed as mini file doesn't have LITESPEED_IS_HTML, keep for efficiency)
			return $content;
		}

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			Debug2::debug( '[Optm] bypass: Not frontend HTML type' );
			return $content;
		}

		// Check if hit URI excludes
		$excludes = Conf::val( Base::O_OPTM_EXC );
		if ( ! empty( $excludes ) ) {
			$result = Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $excludes );
			if ( $result ) {
				Debug2::debug( '[Optm] bypass: hit URI Excludes setting: ' . $result );
				return $content;
			}
		}

		// Check if is exclude optm roles ( Need to set Vary too )
		if ( $result = Conf::get_instance()->in_optm_exc_roles() ) {
			Debug2::debug( '[Optm] bypass: hit Role Excludes setting: ' . $result );
			return $content;
		}


		Debug2::debug( '[Optm] start' );

		$instance = self::get_instance();
		$instance->content_ori = $instance->content = $content;

		$instance->_optimize();
		return $instance->content;
	}

	/**
	 * Optimize css src
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _optimize() {
		$this->cfg_http2_css = Conf::val( Base::O_OPTM_CSS_HTTP2 );
		$this->cfg_http2_js = Conf::val( Base::O_OPTM_JS_HTTP2 );
		$this->cfg_css_min = Conf::val( Base::O_OPTM_CSS_MIN );
		$this->cfg_css_comb = Conf::val( Base::O_OPTM_CSS_COMB );
		$this->cfg_js_min = Conf::val( Base::O_OPTM_JS_MIN );
		$this->cfg_js_comb = Conf::val( Base::O_OPTM_JS_COMB );
		$this->cfg_ggfonts_async = Conf::val( Base::O_OPTM_GGFONTS_ASYNC );
		$this->_conf_css_font_display = Conf::val( Base::O_OPTM_CSS_FONT_DISPLAY );
		if ( ! empty( Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ] ) ) {
			$this->_conf_css_font_display = Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ];
		}

		$this->cfg_ttl = Conf::val( Base::O_OPTM_TTL );
		$this->cfg_ggfonts_rm = Conf::val( Base::O_OPTM_GGFONTS_RM );

		if ( ! Router::can_optm() ) {
			Debug2::debug( '[Optm] bypass: admin/feed/preview' );
			return;
		}

		do_action( 'litespeed_optm' );

		// Parse css from content
		if ( $this->cfg_css_min || $this->cfg_css_comb || $this->cfg_http2_css || $this->cfg_ggfonts_rm || $this->cfg_css_async || $this->cfg_ggfonts_async  || $this->_conf_css_font_display ) {
			list( $src_list, $html_list ) = $this->_parse_css();
		}

		// css optimizer
		if ( $this->cfg_css_min || $this->cfg_css_comb || $this->cfg_http2_css ) {

			if ( $src_list ) {
				// IF combine
				if ( $this->cfg_css_comb ) {
					$url = $this->_build_hash_url( $src_list );
					// Handle css async load
					if ( $this->cfg_css_async ) {
						$this->html_head .= '<link rel="preload" data-asynced="1" data-optimized="2" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" href="' . $url . '" />'; // todo: How to use " in attr wrapper "
					}
					else {
						$this->html_head .= '<link data-optimized="2" rel="stylesheet" href="' . $url . '" />';// use 2 as combined
					}

					// Move all css to top
					$this->content = str_replace( $html_list, '', $this->content );

					// Add to HTTP2
					$this->append_http2( $url );

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
						$this->append_http2( $src_info[ 'src' ] );
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
		if ( $this->cfg_js_min || $this->cfg_js_comb || $this->cfg_http2_js || $this->cfg_js_defer || $this->cfg_js_inline_defer ) {
			add_filter( 'litespeed_optimize_js_excludes', array( $this->__data, 'load_js_exc' ) );
			list( $src_list, $html_list ) = $this->_parse_js();
		}

		// js optimizer
		if ( $this->cfg_js_min || $this->cfg_js_comb || $this->cfg_http2_js ) {

			if ( $src_list ) {
				// IF combine
				if ( $this->cfg_js_comb ) {
					$url = $this->_build_hash_url( $src_list, 'js' );
					$this->html_foot .= '<script data-optimized="1" src="' . $url . '" ' . ( $this->cfg_js_defer ? 'defer' : '' ) . '></script>';

					// Add to HTTP2
					$this->append_http2( $url, 'js' );

					// Will move all JS to bottom combined one
					$this->content = str_replace( $html_list, '', $this->content );

				}
				// Only minify
				elseif ( $this->cfg_js_min ) {
					// Will handle js defer inside
					$this->_src_queue_handler( $src_list, $html_list, 'js' );
				}
				// Only HTTP2 push
				else {
					foreach ( $src_list as $src_info ) {
						if ( ! empty( $src_info[ 'inl' ] ) ) {
							continue;
						}
						$this->append_http2( $src_info[ 'src' ], 'js' );
					}
				}
			}
		}

		// Handle js defer if not handled defer yet
		if ( $this->cfg_js_defer && ! $this->cfg_js_min && ! $this->cfg_js_comb ) {
			// defer html
			$html_list2 = $this->_js_defer_list( $html_list, $src_list );

			// Replace async js
			$this->content = str_replace( $html_list, $html_list2, $this->content );
		}

		// Handle Inline JS defer if not combined
		if ( $this->cfg_js_inline_defer && ! $this->cfg_js_comb ) {
			$this->_js_inline_defer_handler( $src_list, $html_list );
		}

		// Append JS inline var for preserved ESI
		if ( $this->_var_preserve_js ) {
			$this->html_head .= '<script>var ' . implode( ',', $this->_var_preserve_js ) . ';</script>';
			Debug2::debug2( '[Optm] Inline JS defer vars', $this->_var_preserve_js );
		}

		// Append async compatibility lib to head
		if ( $this->cfg_css_async ) {
			// Inline css async lib
			if ( Conf::val( Base::O_OPTM_CSS_ASYNC_INLINE ) ) {
				$this->html_head .= '<script id="litespeed-css-async-lib">' . File::read( LSCWP_DIR . self::LIB_FILE_CSS_ASYNC ) . '</script>';
			}
			else {
				$css_async_lib_url = LSWCP_PLUGIN_URL . self::LIB_FILE_CSS_ASYNC;
				$this->html_head .= '<script id="litespeed-css-async-lib" src="' . $css_async_lib_url . '" ' . ( $this->cfg_js_defer ? 'defer' : '' ) . '></script>';// Don't exclude it from defer for now
				$this->append_http2( $css_async_lib_url, 'js' ); // async lib will be http/2 pushed always
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

		/**
		 * Localize GG/FB JS/Fonts
		 * @since  3.3
		 */
		$this->content = Localization::get_instance()->finalize( $this->content );

		// Check if there is any critical css rules setting
		if ( $this->cfg_css_async ) {
			$this->html_head = CSS::prepend_ccss( $this->html_head );
		}

		// Replace html head part
		$this->html_head = apply_filters( 'litespeed_optm_html_head', $this->html_head );
		if ( $this->html_head ) {
			// Put header content to be after charset
			if ( strpos( $this->content, '<meta charset' ) !== false ) {
				$this->content = preg_replace( '#<meta charset([^>]*)>#isU', '<meta charset$1>' . $this->html_head , $this->content, 1 );
			}
			else {
				$this->content = preg_replace( '#<head([^>]*)>#isU', '<head$1>' . $this->html_head , $this->content, 1 );
			}
		}

		// Replace html foot part
		$this->html_foot = apply_filters( 'litespeed_optm_html_foot', $this->html_foot );
		if ( $this->html_foot ) {
			$this->content = str_replace( '</body>', $this->html_foot . '</body>' , $this->content );
		}

		// Drop noscript if enabled
		if ( Conf::val( Base::O_OPTM_NOSCRIPT_RM ) ) {
			// $this->content = preg_replace( '#<noscript>.*</noscript>#isU', '', $this->content );
		}

		// HTML minify
		if ( Conf::val( Base::O_OPTM_HTML_MIN ) ) {
			$this->content = Optimizer::get_instance()->html_min( $this->content );
		}

		if ( $this->http2_headers ) {
			@header( 'Link: ' . implode( ',', $this->http2_headers ), false );
		}

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
		$html .='<script>WebFontConfig={google:{families:[';

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

		$html .= '"' . implode( '","', $families ) . ( $this->_conf_css_font_display ? '&display=' . $this->_conf_css_font_display : '' ) . '"';

		$html .= ']}};';

		// if webfontloader lib was loaded before WebFontConfig variable, call WebFont.load
		$html .= 'if ( typeof WebFont === "object" && typeof WebFont.load === "function" ) { WebFont.load( WebFontConfig ); }';

		$html .= '</script>';

		// https://cdnjs.cloudflare.com/ajax/libs/webfont/1.6.28/webfontloader.js
		$webfont_lib_url = LSWCP_PLUGIN_URL . self::LIB_FILE_WEBFONTLOADER;

		// default async, if js defer set use defer
		// TODO: make defer optional
		$html .= '<script id="litespeed-webfont-lib" src="' . $webfont_lib_url . '" ' . ( $this->cfg_js_defer ? 'defer' : 'async' ) . '></script>';
		$this->append_http2( $webfont_lib_url, 'js' ); // async lib will be http/2 pushed always

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
			$this->html_head = str_replace( $v, $v . '&#038;display=' . $this->_conf_css_font_display, $this->html_head );
			$this->html_foot = str_replace( $v, $v . '&#038;display=' . $this->_conf_css_font_display, $this->html_foot );
			$this->content = str_replace( $v, $v . '&#038;display=' . $this->_conf_css_font_display, $this->content );
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
		if ( Conf::val( Base::O_OPTM_DNS_PREFETCH_CTRL ) ) {
			add_filter( 'litespeed_optm_html_head', array( $this, 'dns_prefetch_xmeta' ), 999 );
		}

		$this->dns_prefetch = Conf::val( Base::O_OPTM_DNS_PREFETCH );
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
	 * Append wide prefetch DNS meta
	 *
	 * @since 3.0
	 * @access public
	 */
	public function dns_prefetch_xmeta( $content ) {
		$content .= '<meta http-equiv="x-dns-prefetch-control" content="on">';
		return $content;
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

		$tag = $file_type == 'css' ? 'link' : 'script';
		foreach ( $src_list as $key => $src_info ) {
			// Minify inline CSS/JS
			if ( ! empty( $src_info[ 'inl' ] ) ) {
				if ( $file_type == 'css' ) {
					$code = Optimizer::minify_css( $src_info[ 'src' ] );
				}
				else {
					$code = Optimizer::minify_js( $src_info[ 'src' ] );
				}
				$snippet = str_replace( $src_info[ 'src' ], $code, $html_list[ $key ] );
			}
			else {
				$url = $this->_build_hash_url( $src_info[ 'src' ], $file_type );
				$snippet = str_replace( $src_info[ 'src' ], $url, $html_list[ $key ] );

				// Handle css async load
				if ( $file_type == 'css' && $this->cfg_css_async ) {
					$snippet = $this->_async_css( $snippet );
				}

				// Handle js defer
				if ( $file_type === 'js' && $this->cfg_js_defer ) {
					$snippet = $this->_js_defer( $snippet, $src_info[ 'src' ] );
				}

				// Add to HTTP2
				$this->append_http2( $url, $file_type );
			}

			$snippet = str_replace( "<$tag ", '<' . $tag . ' data-optimized="1" ', $snippet );
			$html_list[ $key ] = $snippet;
		}

		$this->content = str_replace( $html_list_ori, $html_list, $this->content );
	}

	/**
	 * Generate full URL path with hash for a list of src
	 *
	 * @since  1.2.2
	 * @access private
	 * @return string The final URL
	 */
	private function _build_hash_url( $src, $file_type = 'css', $url_sensitive = false ) {
		// $url_sensitive = Conf::val( Base::O_OPTM_CSS_UNIQUE ) && $file_type == 'css'; // If need to keep unique CSS per URI
		global $wp;
		$request_url = home_url( $wp->request );

		if ( ! is_array( $src ) ) {
			$src = array( $src );
		}

		// Replace preserved ESI (before generating hash)
		if ( $file_type == 'js' ) {
			foreach ( $src as $k => $v ) {
				if ( empty( $v[ 'inl' ] ) ) {
					continue;
				}
				if ( ! empty( $v[ 'src' ] ) ) {
					$src[ $k ][ 'src' ] = $this->_preserve_esi( $v[ 'src' ] );
				}
			}
		}

		// Query string hash
		$qs_hash = substr( md5( json_encode( $src ) . self::get_option( self::ITEM_TIMESTAMP_PURGE_CSS ) ), -5 );

		// Drop query strings
		foreach ( $src as $k => $v ) {
			if ( ! empty( $v[ 'inl' ] ) ) {
				continue;
			}
			if ( ! empty( $v[ 'src' ] ) ) {
				$src[ $k ][ 'src' ] = $this->remove_query_strings( $v[ 'src' ] ); // CSS/JS combine
			}
			else {
				$src[ $k ] = $this->remove_query_strings( $v );
			}
		}

		// $src = array_values( $src );
		$hash = md5( json_encode( $src ) );
		$filename = substr( $hash, -5 ) . '.' . $file_type;

		// Need to check conflicts
		// If short hash exists
		$existed = false;
		if ( $optm_data = $this->__data->optm_hash2src( $filename ) ) {
			// If conflicts
			if ( $optm_data[ 'src' ] === $src && ( ! $url_sensitive || $optm_data[ 'refer' ] === $request_url ) ) {
				$existed = true;
			}
			else {
				$filename = $hash . '.' . $file_type;
			}
		}

		// Need to insert the record
		if ( ! $existed ) {
			$this->__data->optm_save_src( $filename, $src, $request_url );
		}

		// Generate static files
		$static_file = LITESPEED_STATIC_DIR . "/cssjs/$filename";
		// Check if the file is valid to bypass minify process
		if ( ! file_exists( $static_file ) || time() - filemtime( $static_file ) > $this->cfg_ttl ) {
			$concat_only = ! ( $file_type === 'css' ? $this->cfg_css_min : $this->cfg_js_min );

			Optimizer::get_instance()->serve( $filename, $concat_only, $src, $request_url );
		}

		return LITESPEED_STATIC_URL . '/cssjs/' . $filename . '?' . $qs_hash;
	}

	/**
	 * Parse js src
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _parse_js() {
		$excludes = apply_filters( 'litespeed_optimize_js_excludes', Conf::val( Base::O_OPTM_JS_EXC ) );

		$combine_ext_inl = Conf::val( Base::O_OPTM_JS_COMB_EXT_INL );

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
				$js_excluded = $excludes ? Utility::str_hit_array( $attrs[ 'src' ], $excludes ) : false;
				$is_internal = Utility::is_internal_file( $attrs[ 'src' ] );
				$ext_excluded = ! $combine_ext_inl && ! $is_internal;
				if ( $js_excluded || $ext_excluded ) {
					// Maybe defer
					if ( $this->cfg_js_defer ) {
						$deferred = $this->_js_defer( $match[ 0 ], $attrs[ 'src' ] );
						if ( $deferred != $match[ 0 ] ) {
							$this->content = str_replace( $match[ 0 ], $deferred, $this->content );
						}
					}

					if ( $is_internal ) {
						$this->append_http2( $attrs[ 'src' ], 'js' );
					}

					Debug2::debug2( '[Optm] _parse_js bypassed due to ' . ( $js_excluded ? 'js files excluded [hit] ' . $js_excluded : 'external js' ) );
					continue;
				}

				if ( strpos( $attrs[ 'src' ], '/localres/' ) !== false ) {
					if ( $is_internal ) {
						$this->append_http2( $attrs[ 'src' ], 'js' );
					}
					continue;
				}

				if ( strpos( $attrs[ 'src' ], 'instant_click' ) !== false ) {
					if ( $is_internal ) {
						$this->append_http2( $attrs[ 'src' ], 'js' );
					}
					continue;
				}

				$this_src_arr[ 'src' ] = $attrs[ 'src' ];
			}
			// Inline JS
			elseif ( ! empty( $match[ 2 ] ) ) {
				// Debug2::debug( '🌹🌹🌹 ' . $match[2] . '🌹' );
				// Exclude check
				$js_excluded = $excludes ? Utility::str_hit_array( $match[ 2 ], $excludes ) : false;
				if ( $js_excluded || ! $combine_ext_inl ) {
					// Maybe defer
					if ( $this->cfg_js_inline_defer ) {
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

			$src_list[] = $this_src_arr;
			$html_list[] = $match[ 0 ];
		}

		return array( $src_list, $html_list );
	}

	/**
	 * Handle inline JS defer if no combined
	 *
	 * @since  3.5
	 */
	private function _js_inline_defer_handler( $src_list, $html_list ) {
		foreach ( $src_list as $k => $src_info ) {
			if ( empty( $src_info[ 'inl' ] ) ) {
				continue;
			}

			$attrs = ! empty( $src_info[ 'attrs' ] ) ? $src_info[ 'attrs' ] : '';
			$deferred = $this->_js_inline_defer( $src_info[ 'src' ], $attrs );
			if ( $deferred ) {
				$this->content = str_replace( $html_list[ $k ], $deferred, $this->content );
			}
		}
	}

	/**
	 * Inline JS defer
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _js_inline_defer( $con, $attrs ) {
		if ( $this->cfg_js_defer_exc ) {
			$hit = Utility::str_hit_array( $con, $this->cfg_js_defer_exc );
			if ( $hit ) {
				Debug2::debug2( '[Optm] inline js defer excluded [setting] ' . $hit );
				return;
			}
		}

		$con = trim( $con );
		// Minify JS first
		$con = Optimizer::minify_js( $con );

		if ( ! $con ) {
			return;
		}

		if ( $this->cfg_js_inline_defer === 2 ) {
			// Check if the content contains ESI nonce or not
			$con = $this->_preserve_esi( $con );
			return '<script' . $attrs . ' src="data:text/javascript;base64,' . base64_encode( $con ) . '" defer></script>';
		}
		else {
			// Prevent var scope issue
			if ( strpos( $con, 'var ' ) !== false && strpos( $con, '{' ) === false ) {
				return;
			}

			if ( strpos( $con, 'var ' ) !== false && strpos( $con, '{' ) !== false && strpos( $con, '{' ) > strpos( $con, 'var ' ) ) {
				return;
			}

			if ( strpos( $con, 'document.addEventListener' ) !== false ) {
				return;
			}

			// $con = str_replace( 'var ', 'window.', $con );

			return '<script' . $attrs . '>document.addEventListener("DOMContentLoaded",function(){' . $con . '});</script>';
		}
	}

	/**
	 * Replace ESI to JS inline var (mainly used to avoid nonce timeout)
	 *
	 * @since  3.5.1
	 */
	private function _preserve_esi( $con ) {
		$esi_placeholder_list = ESI::get_instance()->contain_preserve_esi( $con );
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
		$excludes = apply_filters( 'litespeed_optimize_css_excludes', Conf::val( Base::O_OPTM_CSS_EXC ) );

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

			if ( $excludes && $exclude = Utility::str_hit_array( $match[ 0 ], $excludes ) ) {
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
				if ( $css_to_be_removed && Utility::str_hit_array( $attrs[ 'href' ], $css_to_be_removed ) ) {
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
					 * @since  3.0 For fotn display optm, need to parse google fonts URL too
					 */
					if ( ! in_array( $attrs[ 'href' ], $this->_ggfonts_urls ) ) {
						$this->_ggfonts_urls[] = $attrs[ 'href' ];
					}

					if ( $this->cfg_ggfonts_rm || $this->cfg_ggfonts_async ) {
						Debug2::debug2( '[Optm] rm css snippet [Google fonts] ' . $attrs[ 'href' ] );
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

				if ( ! empty( $attrs[ 'media' ] ) && $attrs[ 'media' ] !== 'all' ) {
					$this_src_arr[ 'media' ] = $attrs[ 'media' ];
				}

				$this_src_arr[ 'src' ] = $attrs[ 'href' ];
			}
			else { // Inline style
				$attrs = Utility::parse_attr( $match[ 2 ] );

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
		if ( ! Conf::val( Base::O_OPTM_NOSCRIPT_RM ) ) {
			$v .= '<noscript>' . $ori . '</noscript>';
		}

		return $v;
	}

	/**
	 * Add defer to JS
	 *
	 * @since  1.3
	 * @access private
	 */
	private function _js_defer_list( $html_list, $src_list ) {
		foreach ( $html_list as $k => $v ) {
			if ( ! empty( $src_list[ $k ][ 'inl' ] ) ) {
				continue;
			}

			$html_list[ $k ] = $this->_js_defer( $v, $src_list[ $k ][ 'src' ] );
		}

		return $html_list;
	}

	/**
	 * Defer JS snippet
	 *
	 * @since  3.5
	 */
	private function _js_defer( $ori, $src ) {
		if ( strpos( $ori, 'async' ) !== false ) {
			return $ori;
		}
		if ( strpos( $ori, 'defer' ) !== false ) {
			return $ori;
		}
		if ( strpos( $ori, 'data-deferred' ) !== false ) {
			Debug2::debug2( '[Optm] bypass: attr data-deferred exist' );
			return $ori;
		}
		if ( strpos( $ori, 'data-no-defer' ) !== false ) {
			Debug2::debug2( '[Optm] bypass: attr api data-no-defer' );
			return $ori;
		}

		/**
		 * Exclude JS from setting
		 * @since 1.5
		 */
		if ( $this->cfg_js_defer_exc && Utility::str_hit_array( $src, $this->cfg_js_defer_exc ) ) {
			Debug2::debug( '[Optm] js defer exclude ' . $src );
			return $ori;
		}

		$v = str_replace( '></script>', ' defer data-deferred="1"></script>', $ori );
		return $v;
	}

	/**
	 * Append to HTTP2 header
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function append_http2( $url, $file_type = 'css' ) {
		if ( ! ( $file_type === 'css' ? $this->cfg_http2_css : $this->cfg_http2_js ) ) {
			return;
		}

		/**
		 * For CDN enabled ones, bypass http/2 push
		 * @since  1.6.2.1
		 */
		if ( CDN::inc_type( $file_type ) ) {
			return;
		}

		/**
		 * Keep QS for constance by set 2nd param to true
		 * @since  1.6.2.1
		 */
		$uri = Utility::url2uri( $url, true );

		if ( ! $uri ) {
			return;
		}

		$this->http2_headers[] = '<' . $uri . '>; rel=preload; as=' . ( $file_type === 'css' ? 'style' : 'script' );
	}

}
