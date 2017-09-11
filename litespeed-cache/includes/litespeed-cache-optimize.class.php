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

	const DIR_OPTMIZE = '/cache/optimize' ;

	private $content ;

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

		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_MINIFY ) ) {
			$instance->_minify_css() ;
		}
		return $instance->content ;
	}

	/**
	 * Minify css src
	 *
	 * @since  1.2.2
	 * @access private
	 */
	private function _minify_css()
	{
		litespeed_load_vendor() ;
		$list = $this->_parse_css() ;

		$cache = new Minify_Cache_File() ;
		$minify = new Minify( $cache ) ;
		$env = new Minify_Env() ;
		$sourceFactory = new Minify_Source_Factory( $env, array(), $cache ) ;
		$controller = new Minify_Controller_Files( $env, $sourceFactory ) ;
		$options = [
			'encodeOutput' => false,
			'quiet' => true,
		];

		foreach ( $list as $item ) {
			LiteSpeed_Cache_Log::debug( 'Optmizer: Checking ' . $item[ 'src' ] ) ;
			// Check if is external URL
			$frontend_url = parse_url( get_option( 'home' ) ) ;
			$src_parsed = parse_url( $item[ 'src' ] ) ;

			if ( isset( $src_parsed[ 'host' ] ) && $src_parsed[ 'host' ] !== $frontend_url[ 'host' ] ) {
				LiteSpeed_Cache_Log::debug( 'Optmizer:    Abort external not ' . $frontend_url[ 'host' ] ) ;
				continue ;
			}

			// Parse file path
			$file_path = realpath( $_SERVER[ 'DOCUMENT_ROOT' ] . $src_parsed[ 'path' ] ) ;
			if ( ! is_file( $file_path ) ) {
				LiteSpeed_Cache_Log::debug( 'Optmizer:    Abort non-exist ' . $file_path ) ;
				continue ;
			}

			// Request to minify
			$options[ 'files' ] = [ $file_path ] ;

			$result = $minify->serve($controller, $options) ;

			if ( empty( $result[ 'success' ] ) ) {
				LiteSpeed_Cache_Log::debug( 'Optmizer:    Serve from lib failed ' . $result[ 'statusCode' ]. ' ' . $file_path ) ;
				continue ;
			}

			// Get hash url
			$file_to_save = md5( $item[ 'src' ] ) . '.css' ;
			LiteSpeed_Cache_Log::debug( 'Optmizer:    Added ' . $file_to_save ) ;
			$file_to_save = self::DIR_OPTMIZE . '/' . $file_to_save ;

			Litespeed_File::save( LSWCP_CONTENT_DIR . $file_to_save, $result[ 'content' ], true ) ;

			$src_after_effect = content_url( $file_to_save ) ;
			$html_after_effect = str_replace( $item[ 'src' ], $src_after_effect, $item[ 'html' ] ) ;
			$html_after_effect = str_replace( '<link ', '<link data-minified="1" ', $html_after_effect ) ;

			$this->content = str_replace( $item[ 'html' ], $html_after_effect, $this->content ) ;
		}
	}

	/**
	 * Parse css src
	 *
	 * @since  1.2.2
	 * @access private
	 * @return array  All the css src list
	 */
	private function _parse_css()
	{
		$list = array() ;

		// $dom = new PHPHtmlParser\Dom ;
		// $dom->load( $content ) ;return $val;
		// $items = $dom->find( 'link' ) ;

		preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<link\s+([^>]+)/?>#isU', $this->content, $matches, PREG_SET_ORDER ) ;
		$i = 0;
		foreach ( $matches as $match ) {
			$attrs = $this->_parse_attr( $match[ 1 ] ) ;

			if ( empty( $attrs[ 'rel' ] ) || $attrs[ 'rel' ] !== 'stylesheet' ) {
				continue ;
			}
			if ( ! empty( $attrs[ 'media' ] ) && strpos( $attrs[ 'media' ], 'print' ) !== false ) {
				continue ;
			}
			if ( empty( $attrs[ 'href' ] ) ) {
				continue ;
			}

			$list[] = array(
				'src' => $attrs[ 'href' ],
				'html'	=> $match[ 0 ],
			) ;
		}
		return $list ;

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



