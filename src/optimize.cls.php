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
	private $cfg_js_defer_exc = false;
	private $cfg_exc_jquery;
	private $cfg_ggfonts_async;
	private $_conf_css_font_display;
	private $cfg_optm_max_size;
	private $cfg_ttl;
	private $cfg_ggfonts_rm;

	private $dns_prefetch;
	private $_ggfonts_urls = array();

	private $html_foot = ''; // The html info append to <body>
	private $html_head = ''; // The html info prepend to <body>

	private static $_var_i = 0;

	/**
	 *
	 * @since  1.2.2
	 * @access protected
	 */
	protected function __construct() {
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
		if ( $this->cfg_js_defer ) {
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
		$this->cfg_exc_jquery = Conf::val( Base::O_OPTM_EXC_JQ );
		$this->cfg_ggfonts_async = Conf::val( Base::O_OPTM_GGFONTS_ASYNC );
		$this->_conf_css_font_display = Conf::val( Base::O_OPTM_CSS_FONT_DISPLAY );
		if ( ! empty( Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ] ) ) {
			$this->_conf_css_font_display = Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ];
		}

		$this->cfg_ttl = Conf::val( Base::O_OPTM_TTL );
		$this->cfg_optm_max_size = Conf::val( Base::O_OPTM_MAX_SIZE ) * 1000000;
		$this->cfg_ggfonts_rm = Conf::val( Base::O_OPTM_GGFONTS_RM );

		if ( ! Router::can_optm() ) {
			Debug2::debug( '[Optm] bypass: admin/feed/preview' );
			return;
		}

		do_action( 'litespeed_optm' );

		// Parse css from content
		if ( $this->cfg_css_min || $this->cfg_css_comb || $this->cfg_http2_css || $this->cfg_ggfonts_rm || $this->cfg_css_async || $this->cfg_ggfonts_async  || $this->_conf_css_font_display ) {
			list( $src_list, $html_list ) = $this->_handle_css();
		}

		// css optimizer
		if ( $this->cfg_css_min || $this->cfg_css_comb || $this->cfg_http2_css ) {

			if ( $src_list ) {
				// Analyze local file
				list( $ignored_html, $src_queue_list, $file_size_list ) = $this->_analyse_links( $src_list, $html_list );

				// IF combine
				if ( $this->cfg_css_comb ) {
					$enqueue_first = Conf::val( Base::O_OPTM_CSS_COMB_PRIO );

					$urls = $this->_limit_size_build_hash_url( $src_queue_list, $file_size_list );

					$snippet = '';
					foreach ( $urls as $url ) {
						$snippet .= '<link data-optimized="2" rel="stylesheet" href="' . $url . '" />';// use 2 as combined
					}

					// Handle css async load
					if ( $this->cfg_css_async ) {
						// Only ignored html snippet needs async
						$ignored_html_async = $this->_async_css_list( $ignored_html );

						$snippet = '';
						foreach ( $urls as $url ) {
							$snippet .= '<link rel="preload" data-asynced="1" data-optimized="2" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" href="' . $url . '" />'; // todo: How to use " in attr wrapper "
						}

						// enqueue combined file first
						if ( $enqueue_first ) {
							$this->html_head .= $snippet . implode( '', $ignored_html_async );
						}
						else {
							$this->html_head .= implode( '', $ignored_html_async ) . $snippet;
						}

					}
					else {
						// enqueue combined file first
						if ( $enqueue_first ) {
							$this->html_head .= $snippet . implode( '', $ignored_html );
						}
						else {
							$this->html_head .= implode( '', $ignored_html ) . $snippet;
						}
					}

					// Move all css to top
					$this->content = str_replace( $html_list, '', $this->content );// todo: need to keep position for certain files

					// Add to HTTP2
					foreach ( $urls as $url ) {
						$this->append_http2( $url );
					}

				}
				// Only minify
				elseif ( $this->cfg_css_min ) {
					// will handle async css load inside
					$this->_src_queue_handler( $src_queue_list, $html_list );
				}
				// Only HTTP2 push
				else {
					foreach ( $src_queue_list as $src ) {
						if ( ! empty( $src[ 'src' ] ) ) {
							$src = $src[ 'src' ];
						}
						$this->append_http2( $src );
					}
				}
			}
		}

		// Handle css lazy load if not handled async loaded yet
		if ( $this->cfg_css_async && ! $this->cfg_css_min && ! $this->cfg_css_comb ) {
			// async html
			$html_list_async = $this->_async_css_list( $html_list );

			// Replace async css
			$this->content = str_replace( $html_list, $html_list_async, $this->content );

		}

		// Parse js from buffer as needed
		if ( $this->cfg_js_min || $this->cfg_js_comb || $this->cfg_http2_js || $this->cfg_js_defer ) {
			list( $src_list, $html_list, $head_src_list ) = $this->_parse_js();
		}

		// js optimizer
		if ( $this->cfg_js_min || $this->cfg_js_comb || $this->cfg_http2_js ) {

			if ( $src_list ) {
				list( $ignored_html, $src_queue_list, $file_size_list ) = $this->_analyse_links( $src_list, $html_list, 'js' );

				// IF combine
				if ( $this->cfg_js_comb ) {
					$enqueue_first = Conf::val( Base::O_OPTM_JS_COMB_PRIO );

					// separate head/foot js/raw html
					$head_js = array();
					$head_ignored_html = array();
					$foot_js = array();
					$foot_ignored_html = array();
					foreach ( $src_queue_list as $k => $src ) {
						if ( in_array( $src, $head_src_list ) ) {
							$head_js[ $k ] = $src;
						}
						else {
							$foot_js[ $k ] = $src;
						}
					}
					foreach ( $ignored_html as $src => $html ) {
						if ( in_array( $src, $head_src_list ) ) {
							$head_ignored_html[ $src ] = $html;
						}
						else {
							$foot_ignored_html[] = $html;
						}
					}

					$snippet = '';
					if ( $head_js ) {
						$urls = $this->_limit_size_build_hash_url( $head_js, $file_size_list, 'js' );
						foreach ( $urls as $url ) {
							$snippet .= '<script data-optimized="1" src="' . $url . '" ' . ( $this->cfg_js_defer ? 'defer' : '' ) . '></script>';

							// Add to HTTP2
							$this->append_http2( $url, 'js' );
						}
					}
					if ( $this->cfg_js_defer ) {
						$head_ignored_html = $this->_js_defer( $head_ignored_html );
					}

					/**
					 * Enqueue combined file first
					 * @since  1.6
					 */
					if ( $enqueue_first ) {
						// Make jQuery to be the first one
						// Suppose jQuery is in header
						foreach ( $head_ignored_html as $src => $html ) {
							if ( $this->_is_jquery( $src ) ) {
								// jQuery should be always the first one
								$this->html_head .= $html;
								unset( $head_ignored_html[ $src ] );
								break;
							}
						}
						$this->html_head .= $snippet . implode( '', $head_ignored_html );
					}
					else {
						$this->html_head .= implode( '', $head_ignored_html ) . $snippet;
					}

					$snippet = '';
					if ( $foot_js ) {
						$urls = $this->_limit_size_build_hash_url( $foot_js, $file_size_list, 'js' );
						foreach ( $urls as $url ) {
							$snippet .= '<script data-optimized="1" src="' . $url . '" ' . ( $this->cfg_js_defer ? 'defer' : '' ) . '></script>';

							// Add to HTTP2
							$this->append_http2( $url, 'js' );
						}
					}
					if ( $this->cfg_js_defer ) {
						$foot_ignored_html = $this->_js_defer( $foot_ignored_html );
					}

					// enqueue combined file first
					if ( $enqueue_first ) {
						$this->html_foot .= $snippet . implode( '', $foot_ignored_html );
					}
					else {
						$this->html_foot .= implode( '', $foot_ignored_html ) . $snippet;
					}

					// Will move all js to top/bottom
					$this->content = str_replace( $html_list, '', $this->content );

				}
				// Only minify
				elseif ( $this->cfg_js_min ) {
					// Will handle js defer inside
					$this->_src_queue_handler( $src_queue_list, $html_list, 'js' );
				}
				// Only HTTP2 push
				else {
					foreach ( $src_queue_list as $val ) {
						$this->append_http2( $val, 'js' );
					}
				}
			}
		}

		// Handle js defer if not handled defer yet
		if ( $this->cfg_js_defer && ! $this->cfg_js_min && ! $this->cfg_js_comb ) {
			// defer html
			$html_list2 = $this->_js_defer( $html_list );

			// Replace async js
			$this->content = str_replace( $html_list, $html_list2, $this->content );
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

		/**
		 * Inline script manipulated until document is ready
		 * @since  3.0
		 */
		$this->_js_inline_defer();

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
	 * Inline JS defer
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _js_inline_defer() {
		$optm_js_inline = Conf::val( Base::O_OPTM_JS_INLINE_DEFER );
		if ( ! $optm_js_inline ) {
			return;
		}

		$optm_js_inline_exc = Conf::val( Base::O_OPTM_JS_INLINE_DEFER_EXC );

		Debug2::debug( '[Optm] Inline JS defer ' . $optm_js_inline );

		preg_match_all( '#<script([^>]*)>(.*)</script>#isU', $this->content, $matches, PREG_SET_ORDER );

		$script_ori = array();
		$script_deferred = array();

		$js_var_preserve = array();
		foreach ( $matches as $match ) {

			if ( ! empty( $match[ 1 ] ) ) {
				$attrs = Utility::parse_attr( $match[ 1 ] );

				if ( ! empty( $attrs[ 'src' ] ) ) {
					continue;
				}

				if ( ! empty( $attrs[ 'data-no-optimize' ] ) ) {
					continue;
				}

				if ( ! empty( $attrs[ 'type' ] ) && $attrs[ 'type' ] != 'text/javascript' ) {
					continue;
				}
			}

			$con = $match[ 2 ];

			if ( $optm_js_inline_exc ) {
				$hit = Utility::str_hit_array( $con, $optm_js_inline_exc );
				if ( $hit ) {
					Debug2::debug2( '[Optm] inline js defer excluded [setting] ' . $hit );
					continue;
				}
			}

			$con = trim( $con );
			// Minify JS first
			$con = Optimizer::minify_js( $con );

			if ( ! $con ) {
				continue;
			}

			if ( $optm_js_inline === 2 ) {
				$script_ori[] = $match[ 0 ];
				// Check if the content contains ESI nonce or not
				if ( $esi_placeholder_list = ESI::get_instance()->contain_preserve_esi( $con ) ) {
					foreach ( $esi_placeholder_list as $esi_placeholder ) {
						$js_var = '__litespeed_var_' . ( self::$_var_i ++ ) . '__';
						$con = str_replace( $esi_placeholder, $js_var, $con );
						$js_var_preserve[] = $js_var . '=' . $esi_placeholder;
					}
				}
				$script_deferred[] = '<script src="data:text/javascript;base64, ' . base64_encode( $con ) . '" defer ' . $match[ 1 ] . '></script>';
			}
			else {
				// Prevent var scope issue
				if ( strpos( $con, 'var ' ) !== false && strpos( $con, '{' ) === false ) {
					continue;
				}

				if ( strpos( $con, 'var ' ) !== false && strpos( $con, '{' ) !== false && strpos( $con, '{' ) > strpos( $con, 'var ' ) ) {
					continue;
				}

				if ( strpos( $con, 'document.addEventListener' ) !== false ) {
					continue;
				}

				// $con = str_replace( 'var ', 'window.', $con );

				$script_ori[] = $match[ 0 ];

				$deferred = 'document.addEventListener("DOMContentLoaded",function(){' . $con . '});';

				$script_deferred[] = '<script' . $match[ 1 ] . '>' . $deferred . '</script>';
			}

		}

		if ( $js_var_preserve ) {
			$this->html_head .= '<script>var ' . implode( ',', $js_var_preserve ) . ';</script>';
			Debug2::debug2( '[Optm] Inline JS defer vars', $js_var_preserve );
		}

		$this->content = str_replace( $script_ori, $script_deferred, $this->content );

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
	 * Limit combined filesize when build hash url
	 *
	 * @since  1.3
	 * @access private
	 */
	private function _limit_size_build_hash_url( $src_queue_list, $file_size_list, $file_type = 'css' ) {
		$total = 0;
		$i = 0;
		$src_arr = array();
		$url_sensitive = Conf::val( Base::O_OPTM_CSS_UNIQUE ) && $file_type == 'css'; // If need to keep unique CSS per URI
		foreach ( $src_queue_list as $k => $src ) {
			empty( $src_arr[ $i ] ) && $src_arr[ $i ] = array();

			$src_arr[ $i ][] = $src;

			$total += $file_size_list[ $k ];

			if ( $total > $this->cfg_optm_max_size && ! $url_sensitive ) { // If larger than 1M, separate them
				$total = 0;
				$i ++;
			}
		}
		if ( count( $src_arr ) > 1 ) {
			Debug2::debug( '[Optm] separate ' . $file_type . ' to ' . count( $src_arr ) );
		}

		// group build
		$hashed_arr = array();
		foreach ( $src_arr as $src_list ) {
			$hashed_arr[] = $this->_build_hash_url( $src_list, $file_type, $url_sensitive );
		}

		return $hashed_arr;
	}

	/**
	 * Run minify with src queue list
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _src_queue_handler( $src_queue_list, $html_list, $file_type = 'css' ) {
		$html_list_ori = $html_list;

		$tag = $file_type === 'css' ? 'link' : 'script';
		foreach ( $src_queue_list as $key => $src ) {
			if ( ! empty( $src[ 'src' ] ) ) {
				$src = $src[ 'src' ];
			}
			$url = $this->_build_hash_url( $src, $file_type );
			$snippet = str_replace( $src, $url, $html_list[ $key ] );
			$snippet = str_replace( "<$tag ", '<' . $tag . ' data-optimized="1" ', $snippet );

			$html_list[ $key ] = $snippet;

			// Add to HTTP2
			$this->append_http2( $url, $file_type );
		}

		// Handle css async load
		if ( $file_type === 'css' && $this->cfg_css_async ) {
			$html_list = $this->_async_css_list( $html_list );
		}

		// Handle js defer
		if ( $file_type === 'js' && $this->cfg_js_defer ) {
			$html_list = $this->_js_defer( $html_list );
		}

		$this->content = str_replace( $html_list_ori, $html_list, $this->content );
	}

	/**
	 * Check that links are internal or external
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array Array(Ignored raw html, src needed to be handled list, filesize for param 2nd )
	 */
	private function _analyse_links( $src_list, $html_list, $file_type = 'css' ) {
		// if ( $file_type == 'css' ) {
		// 	$excludes = apply_filters( 'litespeed_optimize_css_excludes', Conf::val( Base::O_OPTM_CSS_EXC ) );
		// }
		// else {
		// 	$excludes = apply_filters( 'litespeed_optimize_js_excludes', Conf::val( Base::O_OPTM_JS_EXC ) );
		// }
		// if ( $excludes ) {
		// 	$excludes = explode( "\n", $excludes );
		// }

		$ignored_html = array();
		$src_queue_list = array();
		$file_size_list = array();

		// Analyse links
		foreach ( $src_list as $key => $src_info ) {
			// CSS has different format when having media='' conditional attribute
			if ( ! empty( $src_info[ 'src' ] ) ) {
				$src = $src_info[ 'src' ];
			}
			else {
				$src = $src_info;
			}

			Debug2::debug2( '[Optm] ' . $src );

			/**
			 * Excluded links won't be done any optm
			 * @since 1.7
			 */
			// if ( $excludes && $exclude = Utility::str_hit_array( $src, $excludes ) ) {
			// 	$ignored_html[] = $html_list[ $key ];
			// 	Debug2::debug2( '[Optm]:    Abort excludes: ' . $exclude );
			// 	continue;
			// }

			// Check if has no-optimize attr
			if ( strpos( $html_list[ $key ], 'data-ignore-optimize' ) !== false ) {
				$ignored_html[] = $html_list[ $key ];
				Debug2::debug2( '[Optm]    Abort excludes: attr data-ignore-optimize' );
				continue;
			}

			// Check if is external URL
			$url_parsed = parse_url( $src );
			if ( ! $file_info = Utility::is_internal_file( $src ) ) {
				$ignored_html[ $src ] = $html_list[ $key ];
				Debug2::debug2( '[Optm]    Abort external/non-exist' );
				continue;
			}

			/**
			 * Check if exclude jQuery or not
			 * Exclude from minify/combine
			 * @since  1.5
			 */
			if ( $this->cfg_exc_jquery && $this->_is_jquery( $src ) ) {
				$ignored_html[ $src ] = $html_list[ $key ];
				Debug2::debug2( '[Optm]    Abort jQuery by setting' );

				// Add to HTTP2 as its ignored but still internal src
				$this->append_http2( $src, 'js' );

				continue;
			}

			// Note: some CSS may have different format
			$src_queue_list[ $key ] = $src_info;

			$file_size_list[ $key ] = $file_info[ 1 ];
		}

		return array( $ignored_html, $src_queue_list, $file_size_list );
	}

	/**
	 * Generate full URL path with hash for a list of src
	 *
	 * @since  1.2.2
	 * @access private
	 * @return string The final URL
	 */
	private function _build_hash_url( $src, $file_type = 'css', $url_sensitive = false ) {
		if ( ! $src ) {
			return false;
		}

		global $wp;
		$request_url = home_url( $wp->request );

		if ( ! is_array( $src ) ) {
			$src = array( $src );
		}

		// Query string hash
		$qs_hash = substr( md5( json_encode( $src ) . self::get_option( self::ITEM_TIMESTAMP_PURGE_CSS ) ), -5 );

		// Drop query strings
		foreach ( $src as $k => $v ) {
			if ( ! empty( $v[ 'src' ] ) ) {
				$src[ $k ][ 'src' ] = $this->remove_query_strings( $v[ 'src' ] ); // CSS w/ cond `media=`
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
		if ( $optm_data = Data::get_instance()->optm_hash2src( $filename ) ) {
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
			Data::get_instance()->optm_save_src( $filename, $src, $request_url );
		}

		// Generate static files
		$static_file = LITESPEED_STATIC_DIR . "/cssjs/$filename";
		// Check if the file is valid to bypass minify process
		if ( ! file_exists( $static_file ) || time() - filemtime( $static_file ) > $this->cfg_ttl ) {
			$concat_only = ! ( $file_type === 'css' ? $this->cfg_css_min : $this->cfg_js_min );

			$content = Optimizer::get_instance()->serve( $filename, $concat_only, $src, $request_url );

			// Generate static file
			File::save( $static_file, $content, true );

			Debug2::debug2( '[Optm] Saved static file [path] ' . $static_file );

		}

		return LITESPEED_STATIC_URL . '/cssjs/' . $filename . '?' . $qs_hash;
	}

	/**
	 * Parse js src
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_js() {
		$excludes = apply_filters( 'litespeed_optimize_js_excludes', Conf::val( Base::O_OPTM_JS_EXC ) );

		$src_list = array();
		$html_list = array();
		$head_src_list = array();

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content );
		preg_match_all( '#<script ([^>]+)>\s*</script>|</head>#isU', $content, $matches, PREG_SET_ORDER ); // v3.3 Changed `<script \s*(` to `<script (`
		$is_head = true;
		foreach ( $matches as $match ) {
			if ( $match[ 0 ] === '</head>' ) {
				$is_head = false;
				continue;
			}
			$attrs = Utility::parse_attr( $match[ 1 ] );

			if ( isset( $attrs[ 'data-optimized' ] ) ) {
				continue;
			}
			if ( ! empty( $attrs[ 'data-no-optimize' ] ) ) {
				continue;
			}
			if ( empty( $attrs[ 'src' ] ) ) {
				continue;
			}

			if ( strpos( $attrs[ 'src' ], '/localres/' ) !== false ) {
				continue;
			}

			$url_parsed = parse_url( $attrs[ 'src' ], PHP_URL_PATH );
			if ( substr( $url_parsed, -3 ) !== '.js' ) {
				Debug2::debug2( '[Optm] _parse_js bypassed due to not js file ' . $url_parsed );
				continue;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue;
			}

			if ( $excludes && $exclude = Utility::str_hit_array( $attrs[ 'src' ], $excludes ) ) {
				// Maybe defer
				if ( $this->cfg_js_defer ) {
					$deferred = $this->_js_defer( array( $match[ 0 ] ) );
					$deferred = $deferred[ 0 ];
					if ( $deferred != $match[ 0 ] ) {
						$this->content = str_replace( $match[ 0 ], $deferred, $this->content );
					}
				}

				Debug2::debug2( '[Optm] _parse_js bypassed exclude ' . $exclude );
				continue;
			}

			$src_list[] = $attrs[ 'src' ];
			$html_list[] = $match[ 0 ];

			if ( $is_head ) {
				$head_src_list[] = $attrs[ 'src' ];
			}
		}

		return array( $src_list, $html_list, $head_src_list );
	}

	/**
	 * Parse css src and remove to-be-removed css
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _handle_css() {
		$excludes = apply_filters( 'litespeed_optimize_css_excludes', Conf::val( Base::O_OPTM_CSS_EXC ) );

		$css_to_be_removed = apply_filters( 'litespeed_optm_css_to_be_removed', array() );

		$src_list = array();
		$html_list = array();

		// $dom = new \PHPHtmlParser\Dom;
		// $dom->load( $content );return $val;
		// $items = $dom->find( 'link' );

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content );
		preg_match_all( '#<link ([^>]+)/?>#isU', $content, $matches, PREG_SET_ORDER ); // Changed in v3.3 `<link \s*` to `<link ` and see if css can parse w/o issue
		foreach ( $matches as $match ) {
			$attrs = Utility::parse_attr( $match[ 1 ] );

			if ( empty( $attrs[ 'rel' ] ) || $attrs[ 'rel' ] !== 'stylesheet' ) {
				continue;
			}
			if ( isset( $attrs[ 'data-optimized' ] ) ) {
				continue;
			}
			if ( ! empty( $attrs[ 'data-no-optimize' ] ) ) {
				continue;
			}
			if ( ! empty( $attrs[ 'media' ] ) && strpos( $attrs[ 'media' ], 'print' ) !== false ) {
				// continue;
			}
			if ( empty( $attrs[ 'href' ] ) ) {
				continue;
			}

			if ( $excludes && $exclude = Utility::str_hit_array( $attrs[ 'href' ], $excludes ) ) {
				Debug2::debug2( '[Optm] _handle_css bypassed exclude ' . $exclude );
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

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue;
			}

			if ( ! empty( $attrs[ 'media' ] ) && $attrs[ 'media' ] !== 'all' ) {
				$src_list[] = array(
					'src' => $attrs[ 'href' ],
					'media' => $attrs[ 'media' ],
				);
			}
			else {
				$src_list[] = $attrs[ 'href' ];
			}

			$html_list[] = $match[ 0 ];
		}

		return array( $src_list, $html_list );
	}

	/**
	 * Replace css to async loaded css
	 *
	 * @since  1.3
	 * @access private
	 * @param  array $html_list Orignal css array
	 * @return array            (array)css_async_list
	 */
	private function _async_css_list( $html_list ) {
		foreach ( $html_list as $k => $ori ) {
			if ( strpos( $ori, 'data-asynced' ) !== false ) {
				Debug2::debug2( '[Optm] bypass: attr data-asynced exist' );
				continue;
			}

			if ( strpos( $ori, 'data-no-async' ) !== false ) {
				Debug2::debug2( '[Optm] bypass: attr api data-no-async' );
				continue;
			}

			// async replacement
			$v = str_replace( 'stylesheet', 'preload', $ori );
			$v = str_replace( '<link', '<link data-asynced="1" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" ', $v );
			// Append to noscript content
			if ( ! Conf::val( Base::O_OPTM_NOSCRIPT_RM ) ) {
				$v .= '<noscript>' . $ori . '</noscript>';
			}
			$html_list[ $k ] = $v;
		}
		return $html_list;
	}

	/**
	 * Add defer to js
	 *
	 * @since  1.3
	 * @access private
	 */
	private function _js_defer( $html_list ) {
		foreach ( $html_list as $k => $v ) {
			if ( strpos( $v, 'async' ) !== false ) {
				continue;
			}
			if ( strpos( $v, 'defer' ) !== false ) {
				continue;
			}
			if ( strpos( $v, 'data-deferred' ) !== false ) {
				Debug2::debug2( '[Optm] bypass: attr data-deferred exist' );
				continue;
			}
			if ( strpos( $v, 'data-no-defer' ) !== false ) {
				Debug2::debug2( '[Optm] bypass: attr api data-no-defer' );
				continue;
			}

			/**
			 * Parse src for excluding js from setting
			 * @since 1.5
			 */
			if ( $this->cfg_js_defer_exc || $this->cfg_exc_jquery ) {
				// parse js src
				preg_match( '#<script \s*([^>]+)>#isU', $v, $matches );
				if ( empty( $matches[ 1 ] ) ) {
					Debug2::debug( '[Optm] js defer parse html failed: ' . $v );
					continue;
				}

				$attrs = Utility::parse_attr( $matches[ 1 ] );

				if ( empty( $attrs[ 'src' ] ) ) {
					Debug2::debug( '[Optm] js defer parse src failed: ' . $matches[ 1 ] );
					continue;
				}

				$src = $attrs[ 'src' ];
			}

			/**
			 * Exclude js from setting
			 * @since 1.5
			 */
			if ( $this->cfg_js_defer_exc && Utility::str_hit_array( $src, $this->cfg_js_defer_exc ) ) {
				Debug2::debug( '[Optm] js defer exclude ' . $src );
				continue;
			}

			/**
			 * Check if exclude jQuery
			 * @since  1.5
			 */
			if ( $this->cfg_exc_jquery && $this->_is_jquery( $src ) ) {
				Debug2::debug2( '[Optm]   js defer Abort jQuery by setting' );
				continue;
			}

			$html_list[ $k ] = str_replace( '></script>', ' defer data-deferred="1"></script>', $v );
		}

		return $html_list;
	}

	/**
	 * Check if is jq lib
	 *
	 * @since  1.5
	 * @access private
	 */
	private function _is_jquery( $src ) {
		return stripos( $src, 'jquery.js' ) !== false || stripos( $src, 'jquery.min.js' ) !== false;
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
