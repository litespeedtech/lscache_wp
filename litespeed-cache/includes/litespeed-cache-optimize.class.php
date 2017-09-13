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

	const DIR_MIN = '/cache/min' ;

	private $content ;
	private $http2_headers = array() ;

	private $http2_css = false ;
	private $http2_js = false ;

	private $concatOnly ;
	private $minify_cache ;
	private $minify_minify ;
	private $minify_env ;
	private $minify_sourceFactory ;
	private $minify_controller ;
	private $minify_options ;

	/**
	 * Run optimize process
	 *
	 * @since  1.2.2
	 * @access public
	 * @return  string The content that is after optimization
	 */
	public static function run( $content )
	{
		if ( ! defined( 'LITESPEED_COMMENT_INFO' ) ) {
			return $content ;
		}
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
		$css_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_MINIFY ) ;
		$css_combine = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_COMBINE ) ;
		$js_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_MINIFY ) ;
		$js_combine = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_COMBINE ) ;
		$html_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_HTML_MINIFY ) ;

		$this->http2_css = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_HTTP2 ) ;
		$this->http2_js = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_HTTP2 ) ;

		// The html codes needed to be added to head
		$html_ae = '';

		if ( $css_minify || $css_combine || $this->http2_css ) {
			litespeed_load_vendor() ;

			$this->concatOnly = ! $css_minify ;

			list( $src_list, $html_list ) = $this->_parse_css() ;

			if ( $src_list ) {
				list( $ignored_html, $src_queue_list, $src2file_queue_list ) = $this->_analyse_links( $src_list, $html_list ) ;

				// IF combine
				if ( $css_combine ) {
					$url = $this->_minify( $src_queue_list, $src2file_queue_list ) ;

					$html_ae .= implode( '', $ignored_html ) . "<link data-minified='1' rel='stylesheet' href='$url' />" ;
					// Move all css to top
					$this->content = str_replace( $html_list, '', $this->content ) ;

					// Add to HTTP2
					$this->append_http2( $url ) ;

				}
				// Only minify
				elseif ( $css_minify ) {
					$this->_src_queue_handler( $src_queue_list, $src2file_queue_list, $html_list ) ;
				}
				// Only HTTP2 push
				else {
					foreach ( $src_queue_list as $val ) {
						$this->append_http2( $val ) ;
					}
				}
			}
		}

		if ( $js_minify || $js_combine ) {
			litespeed_load_vendor() ;

			$this->concatOnly = ! $js_minify ;

			list( $src_list, $html_list ) = $this->_parse_js() ;

			if ( $src_list ) {
				list( $ignored_html, $src_queue_list, $src2file_queue_list ) = $this->_analyse_links( $src_list, $html_list ) ;

				// IF combine
				if ( $js_combine ) {
					$url = $this->_minify( $src_queue_list, $src2file_queue_list, 'js' ) ;
					$html_ae .= implode( '', $ignored_html ) . "<script data-minified='1' src='$url'></script>" ;
					// Move all js to top
					$this->content = str_replace( $html_list, '', $this->content ) ;

					// Add to HTTP2
					$this->append_http2( $url, 'js' ) ;

				}
				// Only minify
				elseif ( $js_minify ) {
					$this->_src_queue_handler( $src_queue_list, $src2file_queue_list, $html_list, 'js' ) ;
				}
				// Only HTTP2 push
				else {
					foreach ( $src_queue_list as $val ) {
						$this->append_http2( $val ) ;
					}
				}
			}
		}

		if ( $html_ae ) {
			$this->content = preg_replace( '#<head([^>]*)>#isU', '<head$1>' . $html_ae , $this->content, 1 ) ;
		}

		if ( $html_minify ) {
			$this->content = Minify_HTML::minify( $this->content ) ;
			$this->content .= '<!-- Page minified by LiteSpeed Cache on '.date('Y-m-d H:i:s').' -->' ;
		}

		if ( $this->http2_headers ) {
			@header( 'Link: ' . implode( ',', $this->http2_headers ) ) ;
		}
	}

	/**
	 * Append to HTTP2 header
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function append_http2( $url, $file_type = 'css' )
	{
		if ( ! ( $file_type === 'css' ? $this->http2_css : $this->http2_js ) ) {
			return ;
		}

		$uri = LiteSpeed_Cache_Utility::url2uri( $url ) ;

		if ( ! $uri ) {
			return ;
		}

		$this->http2_headers[] = '<' . $uri . '>; rel=preload; as=' . ( $file_type === 'css' ? 'style' : 'script' ) ;
	}

	/**
	 * Run minify with src queue list
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _src_queue_handler( $src_queue_list, $src2file_queue_list, $html_list, $file_type = 'css' )
	{
		$tag = $file_type === 'css' ? 'link' : 'script' ;
		foreach ( $src_queue_list as $key => $src ) {
			$url = $this->_minify( $src, $src2file_queue_list[ $key ], $file_type ) ;
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
	 * @return array Array(Ignored raw html, src needed to be handled list, real files of src)
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
		$src2file_queue_list = array() ;

		// Analyse links
		$frontend_url = parse_url( get_option( 'home' ) ) ;
		foreach ( $src_list as $key => $src ) {
			LiteSpeed_Cache_Log::debug( 'Opt: ' . $src ) ;
			// Check if is external URL
			$src_parsed = parse_url( $src ) ;

			if ( isset( $src_parsed[ 'host' ] ) && $src_parsed[ 'host' ] !== $frontend_url[ 'host' ] ) {
				$ignored_html[] = $html_list[ $key ] ;
				LiteSpeed_Cache_Log::debug( 'Opt:    Abort external not ' . $frontend_url[ 'host' ] ) ;
				continue ;
			}

			if ( $excludes ) {
				foreach ( $excludes as $exclude ) {
					if ( stripos( $src, $exclude ) !== false ) {
						$ignored_html[] = $html_list[ $key ] ;
						LiteSpeed_Cache_Log::debug( 'Opt:    Abort excludes ' . $exclude ) ;
						continue 2 ;
					}
				}
			}

			// Parse file path
			if ( substr( $src_parsed[ 'path' ], 0, 1 ) === '/' ) {
				$file_path = $_SERVER[ 'DOCUMENT_ROOT' ] . $src_parsed[ 'path' ] ;
			}
			else {
				$file_path = LiteSpeed_Cache_Router::frontend_path() . '/' . $src_parsed[ 'path' ] ;
			}
			$file_path = realpath( $file_path ) ;
			if ( ! is_file( $file_path ) ) {
				$ignored_html[] = $html_list[ $key ] ;
				LiteSpeed_Cache_Log::debug( 'Opt:    Abort non-exist ' . $file_path ) ;
				continue ;
			}

			$src_queue_list[ $key ] = $src ;
			$src2file_queue_list[ $key ] = $file_path ;
		}

		return array( $ignored_html, $src_queue_list, $src2file_queue_list ) ;
	}

	/**
	 * Run minify process
	 * Save to cache folder
	 * Return final URL
	 *
	 * @since  1.2.2
	 * @access private
	 * @return string The final URL
	 */
	private function _minify( $src, $real_files, $file_type = 'css' )
	{
		if ( ! is_array( $src ) ) {
			$src = array( $src ) ;
		}
		if ( ! is_array( $real_files ) ) {
			$real_files = array( $real_files ) ;
		}

		// Request to minify
		$result = $this->_minify_serve( $real_files ) ;

		if ( empty( $result[ 'success' ] ) ) {
			LiteSpeed_Cache_Log::debug( 'Opt:    Lib serve failed ' . $result[ 'statusCode' ] ) ;
		}

		// Get hash url
		$file_to_save = md5( serialize( $src ) ) . '.' . $file_type ;
		LiteSpeed_Cache_Log::debug( 'Opt:    Added ' . $file_to_save ) ;
		$file_to_save = self::DIR_MIN . '/' . $file_to_save ;

		Litespeed_File::save( LSWCP_CONTENT_DIR . $file_to_save, $result[ 'content' ], true ) ;

		$url = content_url( $file_to_save ) ;

		return $url ;
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

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<script\s+([^>]+)>\s*</script>#isU', $content, $matches, PREG_SET_ORDER ) ;
		$i = 0;
		foreach ( $matches as $match ) {
			$attrs = $this->_parse_attr( $match[ 1 ] ) ;

			if ( ! empty( $attrs[ 'data-minified' ] ) ) {
				continue ;
			}
			if ( empty( $attrs[ 'src' ] ) ) {
				continue ;
			}

			$src_list[] = $attrs[ 'src' ] ;
			$html_list[] = $match[ 0 ] ;
		}

		return array( $src_list, $html_list ) ;
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
	 * Run minify serve
	 *
	 * @since  1.2.2
	 * @access private
	 * @param array|string $files The file(s) to minify/combine
	 * @return  string The string after effect
	 */
	private function _minify_serve( $files )
	{
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
			$this->minify_options = [
				'encodeOutput' => false,
				'quiet' => true,
			] ;
		}

		$this->minify_options[ 'concatOnly' ] = $this->concatOnly ;

		$this->minify_options[ 'files' ] = $files ;

		return $this->minify_minify->serve($this->minify_controller, $this->minify_options) ;
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



