<?php

/**
 * The optimize class.
 *
 * @since      1.2.2
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_Optimize
{
	private static $_instance ;

	const OPTION_OPTIMIZED = 'litespeed-cache-optimized' ;
	const DIR_MIN = '/min' ;

	private $content ;
	private $http2_headers = array() ;

	private $cfg_http2_css ;
	private $cfg_http2_js ;
	private $cfg_css_minify ;
	private $cfg_css_combine ;
	private $cfg_js_minify ;
	private $cfg_js_combine ;
	private $cfg_html_minify ;
	private $cfg_optm_qs_trim ;

	private $minify_cache ;
	private $minify_minify ;
	private $minify_env ;
	private $minify_sourceFactory ;
	private $minify_controller ;
	private $minify_options ;

	/**
	 * Init optimizer
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function __construct()
	{
		$this->cfg_http2_css = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_HTTP2 ) ;
		$this->cfg_http2_js = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_HTTP2 ) ;
		$this->cfg_css_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_MINIFY ) ;
		$this->cfg_css_combine = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_COMBINE ) ;
		$this->cfg_js_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_MINIFY ) ;
		$this->cfg_js_combine = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_COMBINE ) ;
		$this->cfg_html_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_HTML_MINIFY ) ;
		$this->cfg_optm_qs_trim = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_OPTM_QS_TRIM ) ;

		if ( $this->_can_optm() && $this->cfg_optm_qs_trim ) {
			// To make sure minify&combine always be new filename when version changed
			if ( ! $this->cfg_css_minify && ! $this->cfg_css_combine ) {
				add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 999 ) ;
			}

			if ( ! $this->cfg_js_minify && ! $this->cfg_js_combine ) {
				add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 999 ) ;
			}
		}

		$this->_request_check() ;
	}

	/**
	 * Check if the request is for static file
	 *
	 * @since  1.2.2
	 * @access private
	 * @return  string The static file content
	 */
	private function _request_check()
	{
		// If not turn on min files
		if ( ! $this->cfg_css_minify && ! $this->cfg_css_combine && ! $this->cfg_js_minify && ! $this->cfg_js_combine ) {
			return ;
		}

		if ( empty( $_SERVER[ 'REQUEST_URI' ] ) || strpos( $_SERVER[ 'REQUEST_URI' ], self::DIR_MIN . '/' ) === false ) {
			return ;
		}

		// try to match `http://home_url/min/xx.css
		if ( ! preg_match( '#' . self::DIR_MIN . '/(\w+\.(css|js))#U', $_SERVER[ 'REQUEST_URI' ], $match ) ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'Optimizer start minifying file' ) ;

		// Proceed css/js file generation
		define( 'LITESPEED_MIN_FILE', true ) ;

		$result = $this->_minify( $match[ 1 ] ) ;

		if ( ! $result ) {
			LiteSpeed_Cache_Control::set_nocache( 'Empty content from optimizer' ) ;
			exit ;
		}

		foreach ( $result[ 'headers' ] as $key => $val ) {
			if ( in_array( $key, array( 'Content-Length', 'Content-Type' ) ) ) {
				header( $key . ': ' . $val ) ;
			}
		}

		LiteSpeed_Cache_Control::set_cacheable() ;
		LiteSpeed_Cache_Control::set_no_vary() ;
		LiteSpeed_Cache_Control::set_custom_ttl( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_OPTIMIZE_TTL ) ) ;
		LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_MIN ) ;

		echo $result[ 'content' ] ;
		exit ;
	}

	/**
	 * Remove QS
	 *
	 * @since  1.2.4
	 * @access public
	 */
	public function remove_query_strings( $src )
	{
		if ( strpos( $src, 'ver=' ) !== false ) {
			$src = preg_replace( '#[&\?]+(ver=([\w\-\.]+))#i', '', $src ) ;
		}
		return $src ;
	}

	/**
	 * Check if can run optimize
	 *
	 * @since  1.2.4
	 * @access private
	 */
	private function _can_optm()
	{
		if ( is_admin() ) {
			return false ;
		}

		if ( is_feed() ) {
			return false ;
		}

		if ( is_preview() ) {
			return false ;
		}

		return true ;
	}

	/**
	 * Run optimize process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * @since  1.2.2
	 * @access public
	 * @return  string The content that is after optimization
	 */
	public static function run( $content )
	{
		if ( ! defined( 'LITESPEED_COMMENT_INFO' ) ) {
			LiteSpeed_Cache_Log::debug( 'Optimizer bypass: No footer info' ) ;
			return $content ;
		}

		LiteSpeed_Cache_Log::debug( 'Optimizer start' ) ;

		$instance = self::get_instance() ;
		$instance->content = $content ;

		$instance->_optimize() ;
		return $instance->content ;
	}

	/**
	 * Optimize css src
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _optimize()
	{
		if ( ! $this->_can_optm() ) {
			LiteSpeed_Cache_Log::debug( 'Optimizer bypass: admin/feed/preview' ) ;
			return ;
		}

		// The html codes needed to be added to head
		$html_head = '';
		$html_foot = '';

		if ( $this->cfg_css_minify || $this->cfg_css_combine || $this->cfg_http2_css ) {
			list( $src_list, $html_list ) = $this->_parse_css() ;

			if ( $src_list ) {
				list( $ignored_html, $src_queue_list ) = $this->_analyse_links( $src_list, $html_list ) ;

				// IF combine
				if ( $this->cfg_css_combine ) {
					$url = $this->_build_hash_url( $src_queue_list ) ;

					$html_head .= implode( '', $ignored_html ) . "<link data-minified='1' rel='stylesheet' href='$url' />" ;
					// Move all css to top
					$this->content = str_replace( $html_list, '', $this->content ) ;

					// Add to HTTP2
					$this->append_http2( $url ) ;

				}
				// Only minify
				elseif ( $this->cfg_css_minify ) {
					$this->_src_queue_handler( $src_queue_list, $html_list ) ;
				}
				// Only HTTP2 push
				else {
					foreach ( $src_queue_list as $val ) {
						$this->append_http2( $val ) ;
					}
				}
			}
		}

		if ( $this->cfg_js_minify || $this->cfg_js_combine || $this->cfg_http2_js ) {
			list( $src_list, $html_list, $head_src_list ) = $this->_parse_js() ;

			if ( $src_list ) {
				list( $ignored_html, $src_queue_list ) = $this->_analyse_links( $src_list, $html_list, 'js' ) ;

				// IF combine
				if ( $this->cfg_js_combine ) {
					// separate head/foot js/raw html
					$head_js = array() ;
					$head_ignored_html = array() ;
					$foot_js = array() ;
					$foot_ignored_html = array() ;
					foreach ( $src_queue_list as $src ) {
						if ( in_array( $src, $head_src_list ) ) {
							$head_js[] = $src ;
						}
						else {
							$foot_js[] = $src ;
						}
					}
					foreach ( $ignored_html as $src => $html ) {
						if ( in_array( $src, $head_src_list ) ) {
							$head_ignored_html[] = $html ;
						}
						else {
							$foot_ignored_html[] = $html ;
						}
					}

					$url = $this->_build_hash_url( $head_js, 'js' ) ;
					if ( $url ) {
						$html = "<script data-minified='1' src='$url'></script>" ;
					}
					$html_head .= implode( '', $head_ignored_html ) . $html ;

					$url = $this->_build_hash_url( $foot_js, 'js' ) ;
					if ( $url ) {
						$html = "<script data-minified='1' src='$url'></script>" ;
					}
					$html_foot .= implode( '', $foot_ignored_html ) . $html ;

					// Will move all js to top/bottom
					$this->content = str_replace( $html_list, '', $this->content ) ;

					// Add to HTTP2
					$this->append_http2( $url, 'js' ) ;

				}
				// Only minify
				elseif ( $this->cfg_js_minify ) {
					$this->_src_queue_handler( $src_queue_list, $html_list, 'js' ) ;
				}
				// Only HTTP2 push
				else {
					foreach ( $src_queue_list as $val ) {
						$this->append_http2( $val ) ;
					}
				}
			}
		}

		if ( $html_head ) {
			$this->content = preg_replace( '#<head([^>]*)>#isU', '<head$1>' . $html_head , $this->content, 1 ) ;
		}

		if ( $html_foot ) {
			$this->content = str_replace( '</body>', $html_foot . '</body>' , $this->content ) ;
		}

		// HTML minify
		if ( $this->cfg_html_minify ) {
			$ori = $this->content ;

			set_error_handler( 'litespeed_exception_handler' ) ;
			try {
				litespeed_load_vendor() ;
				$this->content = Minify_HTML::minify( $this->content ) ;
				$this->content .= '<!-- Page minified by LiteSpeed Cache on '.date('Y-m-d H:i:s').' -->' ;

			} catch ( ErrorException $e ) {
				LiteSpeed_Cache_Control::debug( 'Error when optimizing HTML: ' . $e->getMessage() ) ;
				error_log( 'LiteSpeed Optimizer optimizing HTML Error: ' . $e->getMessage() ) ;
				// If failed to minify HTML, restore original content
				$this->content = $ori ;
			}
			restore_error_handler() ;

		}

		if ( $this->http2_headers ) {
			@header( 'Link: ' . implode( ',', $this->http2_headers ), false ) ;
		}
	}

	/**
	 * Run minify with src queue list
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _src_queue_handler( $src_queue_list, $html_list, $file_type = 'css' )
	{
		$tag = $file_type === 'css' ? 'link' : 'script' ;
		foreach ( $src_queue_list as $key => $src ) {
			$url = $this->_build_hash_url( $src, $file_type ) ;
			$html = str_replace( $src, $url, $html_list[ $key ] ) ;
			$html = str_replace( "<$tag ", "<$tag data-minified='1' ", $html ) ;

			$this->content = str_replace( $html_list[ $key ], $html, $this->content ) ;

			// Add to HTTP2
			$this->append_http2( $url, $file_type ) ;
		}
	}

	/**
	 * Check if links are internal or external
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array Array(Ignored raw html, src needed to be handled list)
	 */
	private function _analyse_links( $src_list, $html_list, $file_type = 'css' )
	{
		if ( $file_type == 'css' ) {
			$excludes = apply_filters( 'litespeed_cache_optimize_css_excludes', LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_EXCLUDES ) ) ;
		}
		else {
			$excludes = apply_filters( 'litespeed_cache_optimize_js_excludes', LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_EXCLUDES ) ) ;
		}
		if ( $excludes ) {
			$excludes = explode( "\n", $excludes ) ;
		}

		$ignored_html = array() ;
		$src_queue_list = array() ;

		// Analyse links
		foreach ( $src_list as $key => $src ) {
			LiteSpeed_Cache_Log::debug2( 'Optm: ' . $src ) ;

			if ( $excludes ) {
				foreach ( $excludes as $exclude ) {
					if ( stripos( $src, $exclude ) !== false ) {
						$ignored_html[] = $html_list[ $key ] ;
						LiteSpeed_Cache_Log::debug2( 'Optm:    Abort excludes ' . $exclude ) ;
						continue 2 ;
					}
				}
			}

			// Check if is external URL
			$url_parsed = parse_url( $src ) ;
			if ( ! $this->_is_file_url( $src ) ) {
				$ignored_html[ $src ] = $html_list[ $key ] ;
				LiteSpeed_Cache_Log::debug2( 'Optm:    Abort external/non-exist ' ) ;
				continue ;
			}

			$src_queue_list[ $key ] = $src ;
		}

		return array( $ignored_html, $src_queue_list ) ;
	}

	/**
	 * Check if an URL is a internal existing file
	 *
	 * @since  1.2.2
	 * @access private
	 * @return string|bool The real path of file OR false
	 */
	private function _is_file_url( $url )
	{
		$url_parsed = parse_url( $url ) ;
		if ( isset( $url_parsed[ 'host' ] ) && ! LiteSpeed_Cache_Utility::internal( $url_parsed[ 'host' ] ) ) {
			// Check if is cdn path
			if ( ! LiteSpeed_Cache_CDN::internal( $url_parsed[ 'host' ] ) ) {
				return false ;
			}
		}

		if ( empty( $url_parsed[ 'path' ] ) ) {
			return false ;
		}

		// Need to replace child blog path for assets, ref: .htaccess
		if ( is_multisite() && defined( 'PATH_CURRENT_SITE' ) ) {
			$pattern = '#^' . PATH_CURRENT_SITE . '([_0-9a-zA-Z-]+/)(wp-(content|admin|includes))#U' ;
			$replacement = PATH_CURRENT_SITE . '$2' ;
			$url_parsed[ 'path' ] = preg_replace( $pattern, $replacement, $url_parsed[ 'path' ] ) ;
			// $current_blog = (int) get_current_blog_id() ;
			// $main_blog_id = (int) get_network()->site_id ;
			// if ( $current_blog === $main_blog_id ) {
			// 	define( 'LITESPEED_IS_MAIN_BLOG', true ) ;
			// }
			// else {
			// 	define( 'LITESPEED_IS_MAIN_BLOG', false ) ;
			// }
		}

		// Parse file path
		if ( substr( $url_parsed[ 'path' ], 0, 1 ) === '/' ) {
			$file_path = $_SERVER[ 'DOCUMENT_ROOT' ] . $url_parsed[ 'path' ] ;
		}
		else {
			$file_path = LiteSpeed_Cache_Router::frontend_path() . '/' . $url_parsed[ 'path' ] ;
		}
		$file_path = realpath( $file_path ) ;
		if ( ! is_file( $file_path ) ) {
			return false ;
		}

		return $file_path ;
	}

	/**
	 * Run minify process and return final content
	 *
	 * @since  1.2.2
	 * @access private
	 * @return string The final content
	 */
	private function _minify( $filename )
	{
		// Search filename in db for src URLs
		$hashes = get_option( self::OPTION_OPTIMIZED ) ;
		if ( ! $hashes || ! is_array( $hashes ) || empty( $hashes[ $filename ] ) ) {
			return false;
		}

		$urls = $hashes[ $filename ] ;
		$file_type = substr( $filename, strrpos( $filename, '.' ) + 1 ) ;

		// Parse real file path
		$real_files = array() ;
		foreach ( $urls as $url ) {
			$real_file = $this->_is_file_url( $url ) ;
			if ( ! $real_file ) {
				continue ;
			}
			$real_files[] = $real_file ;
		}

		if ( ! $real_files ) {
			return false;
		}

		// Request to minify
		$result = $this->_minify_serve( $real_files, $file_type ) ;

		if ( empty( $result[ 'success' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'Optm:    Lib serve failed ' . $result[ 'statusCode' ] ) ;
			return false ;
		}

		LiteSpeed_Cache_Log::debug( 'Optm:    Generated content' ) ;

		return $result ;
	}

	/**
	 * Generate full URL path with hash for a list of src
	 *
	 * @since  1.2.2
	 * @access private
	 * @return string The final URL
	 */
	private function _build_hash_url( $src, $file_type = 'css' )
	{
		if ( ! $src ) {
			return false ;
		}

		if ( ! is_array( $src ) ) {
			$src = array( $src ) ;
		}
		$src = array_values( $src ) ;

		$hash = md5( serialize( $src ) ) ;

		$short = substr( $hash, -5 ) ;

		$filename = $short ;

		// Need to check conflicts
		$hashes = get_option( self::OPTION_OPTIMIZED ) ;
		if ( ! is_array( $hashes ) ) {
			$hashes = array() ;
		}
		// If short hash exists
		if ( $hashes && ! empty( $hashes[ $short . '.' . $file_type ] ) ) {
			// If conflicts
			if ( $hashes[ $short . '.' . $file_type ] !== $src ) {
				$hashes[ $hash . '.' . $file_type ] = $src ;
				update_option( self::OPTION_OPTIMIZED, $hashes ) ;
				$filename = $hash ;
			}
		}
		else {
			// Short hash is safe now
			$hashes[ $short . '.' . $file_type ] = $src ;
			update_option( self::OPTION_OPTIMIZED, $hashes ) ;
		}

		$file_to_save = self::DIR_MIN . '/' . $filename . '.' . $file_type ;

		return $GLOBALS[ 'wp_rewrite' ]->using_permalinks() ? home_url( $file_to_save ) : home_url() . '/?' . $file_to_save ;
	}

	/**
	 * Parse js src
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_js()
	{
		$src_list = array() ;
		$html_list = array() ;
		$head_src_list = array() ;

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<script\s+([^>]+)>\s*</script>|</head>#isU', $content, $matches, PREG_SET_ORDER ) ;
		$i = 0;
		$is_head = true ;
		foreach ( $matches as $match ) {
			if ( $match[ 0 ] === '</head>' ) {
				$is_head = false ;
				continue ;
			}
			$attrs = $this->_parse_attr( $match[ 1 ] ) ;

			if ( ! empty( $attrs[ 'data-minified' ] ) ) {
				continue ;
			}
			if ( empty( $attrs[ 'src' ] ) ) {
				continue ;
			}

			$src_list[] = $attrs[ 'src' ] ;
			$html_list[] = $match[ 0 ] ;

			if ( $is_head ) {
				$head_src_list[] = $attrs[ 'src' ] ;
			}
		}

		return array( $src_list, $html_list, $head_src_list ) ;
	}

	/**
	 * Parse css src
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_css()
	{
		$src_list = array() ;
		$html_list = array() ;

		// $dom = new PHPHtmlParser\Dom ;
		// $dom->load( $content ) ;return $val;
		// $items = $dom->find( 'link' ) ;

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<link\s+([^>]+)/?>#isU', $content, $matches, PREG_SET_ORDER ) ;
		$i = 0;
		foreach ( $matches as $match ) {
			$attrs = $this->_parse_attr( $match[ 1 ] ) ;

			if ( empty( $attrs[ 'rel' ] ) || $attrs[ 'rel' ] !== 'stylesheet' ) {
				continue ;
			}
			if ( ! empty( $attrs[ 'data-minified' ] ) ) {
				continue ;
			}
			if ( ! empty( $attrs[ 'media' ] ) && strpos( $attrs[ 'media' ], 'print' ) !== false ) {
				continue ;
			}
			if ( empty( $attrs[ 'href' ] ) ) {
				continue ;
			}

			$src_list[] = $attrs[ 'href' ] ;
			$html_list[] = $match[ 0 ] ;
		}

		return array( $src_list, $html_list ) ;
	}

	/**
	 * Parse attributes from string
	 *
	 * @since  1.2.2
	 * @access private
	 * @param  string $str
	 * @return array  All the attributes
	 */
	private function _parse_attr( $str )
	{
		$attrs = array() ;
		preg_match_all( '#(\w+)=["\']([^"\']*)["\']#isU', $str, $matches, PREG_SET_ORDER ) ;
		foreach ( $matches as $match ) {
			$attrs[ $match[ 1 ] ] = trim( $match[ 2 ] ) ;
		}
		return $attrs ;
	}

	/**
	 * Append to HTTP2 header
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function append_http2( $url, $file_type = 'css' )
	{
		if ( ! ( $file_type === 'css' ? $this->cfg_http2_css : $this->cfg_http2_js ) ) {
			return ;
		}

		$uri = LiteSpeed_Cache_Utility::url2uri( $url ) ;

		if ( ! $uri ) {
			return ;
		}

		$this->http2_headers[] = '<' . $uri . '>; rel=preload; as=' . ( $file_type === 'css' ? 'style' : 'script' ) ;
	}

	/**
	 * Run minify serve
	 *
	 * @since  1.2.2
	 * @access private
	 * @param array|string $files The file(s) to minify/combine
	 * @return  string The string after effect
	 */
	private function _minify_serve( $files, $file_type )
	{
		set_error_handler( 'litespeed_exception_handler' ) ;
		try {
			litespeed_load_vendor() ;
			if ( ! isset( $this->minify_cache ) ) {
				$this->minify_cache = new Minify_Cache_File() ;
			}
			if ( ! isset( $this->minify_minify ) ) {
				$this->minify_minify = new Minify( $this->minify_cache ) ;
			}
			if ( ! isset( $this->minify_env ) ) {
				$this->minify_env = new Minify_Env() ;
			}
			if ( ! isset( $this->minify_sourceFactory ) ) {
				$this->minify_sourceFactory = new Minify_Source_Factory( $this->minify_env, array(), $this->minify_cache ) ;
			}
			if ( ! isset( $this->minify_controller ) ) {
				$this->minify_controller = new Minify_Controller_Files( $this->minify_env, $this->minify_sourceFactory ) ;
			}
			if ( ! isset( $this->minify_options ) ) {
				$this->minify_options = array(
					'encodeOutput' => false,
					'quiet' => true,
				) ;
			}

			$this->minify_options[ 'concatOnly' ] =  ! ( $file_type === 'css' ? $this->cfg_css_minify : $this->cfg_js_minify ) ;

			$this->minify_options[ 'files' ] = $files ;

			$content = $this->minify_minify->serve( $this->minify_controller, $this->minify_options ) ;

		} catch ( ErrorException $e ) {
			LiteSpeed_Cache_Control::debug( 'Error when serving from optimizer: ' . $e->getMessage() ) ;
			error_log( 'LiteSpeed Optimizer serving Error: ' . $e->getMessage() ) ;
			return false ;
		}
		restore_error_handler() ;

		return $content ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.2.2
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}



