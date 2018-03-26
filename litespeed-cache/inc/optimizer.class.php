<?php

/**
 * The optimize4 class.
 *
 * @since      	1.9
 * @package  	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
require_once LSCWP_DIR . 'lib/js_min.class.php' ;
require_once LSCWP_DIR . 'lib/css_min.class.php' ;
require_once LSCWP_DIR . 'lib/css_min.colors.class.php' ;
require_once LSCWP_DIR . 'lib/css_min.utils.class.php' ;
require_once LSCWP_DIR . 'lib/url_rewritter.class.php' ;

use tubalmartin\CssMin\Minifier as CSSmin;
use tubalmartin\CssMin\Colors as Colors;
use tubalmartin\CssMin\Utils as Utils;


class LiteSpeed_Cache_Optimizer
{
	private static $_instance ;

	/**
	 * Init optimizer
	 *
	 * @since  1.9
	 * @access private
	 */
	private function __construct()
	{
		$this->cfg_css_inline_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CSS_INLINE_MINIFY ) ;
		$this->cfg_js_inline_minify = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_JS_INLINE_MINIFY ) ;
	}

	/**
	 * Run HTML minify process and return final content
	 *
	 * @since  1.9
	 * @access public
	 */
	public function html_min( $content )
	{
		$options = array() ;
		if ( $this->cfg_css_inline_minify ) {
			$options[ 'cssMinifier' ] = array( new CSSmin(), 'run' ) ;
		}

		if ( $this->cfg_js_inline_minify ) {
			$options[ 'jsMinifier' ] = 'JSMin\JSMin::minify' ;
		}

		$obj = new Minify_HTML( $content, $options ) ;
		return $obj->process() ;
	}

	/**
	 * Run minify process and return final content
	 *
	 * @since  1.9
	 * @access public
	 * @return string The final content
	 */
	public function serve( $filename, $concat_only )
	{
		if ( ! is_array( $filename ) ) {
			// Search filename in db for src URLs
			$urls = LiteSpeed_Cache_Data::optm_hash2src( $filename ) ;
			if ( ! $urls || ! is_array( $urls ) ) {
				return false;
			}
		}
		else {
			$urls = $filename ;
		}

		// Parse real file path
		$real_files = array() ;
		foreach ( $urls as $url ) {
			$real_file = LiteSpeed_Cache_Utility::is_internal_file( $url ) ;
			if ( ! $real_file ) {
				continue ;
			}
			$real_files[] = $real_file[ 0 ] ;
		}

		if ( ! $real_files ) {
			return false;
		}

		LiteSpeed_Cache_Log::debug2( '[Optmer]    urls : ', $urls ) ;

		set_error_handler( 'litespeed_exception_handler' ) ;

		$content = '' ;
		$tmp = parse_url( $urls[ 0 ], PHP_URL_PATH ) ;
		$file_type = substr( $tmp, strrpos( $tmp, '.' ) + 1 ) ;
		try {
			// Handle CSS
			if ( $file_type === 'css' ) {
				$content = $this->_serve_css( $real_files, $concat_only ) ;
			}
			// Handle JS
			else {
				$content = $this->_serve_js( $real_files, $concat_only ) ;
			}

		} catch ( ErrorException $e ) {
			LiteSpeed_Cache_Log::debug( '[Optmer] Error when serving from optimizer: ' . $e->getMessage() ) ;
			error_log( 'LiteSpeed Optimizer serving Error: ' . $e->getMessage() ) ;
			return false ;
		}
		restore_error_handler() ;

		/**
		 * Clean comment when minify
		 * @since  1.7.1
		 */
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_OPTM_RM_COMMENT ) ) {
			$content = $this->_remove_comment( $content, $file_type ) ;
		}

		LiteSpeed_Cache_Log::debug( '[Optmer]    Generated content ' . $file_type ) ;

		return $content ;
	}

	/**
	 * Serve css with/without minify
	 *
	 * @since  1.9
	 * @access private
	 */
	private function _serve_css( $files, $concat_only = false )
	{
		$con = array() ;
		foreach ( $files as $real_path ) {
			LiteSpeed_Cache_Log::debug( '[Optmer] [real_path] ' . $real_path ) ;
			$data = Litespeed_File::read( $real_path ) ;

			$data = preg_replace( '/@charset[^;]+;\\s*/', '', $data ) ;

			if ( ! $concat_only && ! $this->_is_min( $real_path ) ) {
				$obj = new CSSmin() ;
				$data = $obj->run( $data ) ;
			}

			$data = Minify_CSS_UriRewriter::rewrite( $data, dirname( $real_path ) ) ;

			$con[] = $data ;
		}

		return implode( '', $con ) ;
	}

	/**
	 * Serve JS with/without minify
	 *
	 * @since  1.9
	 * @access private
	 */
	private function _serve_js( $files, $concat_only )
	{
		$con = array() ;
		foreach ( $files as $real_path ) {
			$data = Litespeed_File::read( $real_path ) ;

			if ( ! $concat_only && ! $this->_is_min( $real_path ) ) {
				$data = JSMin\JSMin::minify( $data ) ;
			}
			else {
				$data = $this->_null_minifier( $data ) ;
			}

			$con[] = $data ;
		}

		return implode( "\n;", $con ) ;
	}

	private function _null_minifier( $content )
	{
		$content = str_replace( "\r\n", "\n", $content ) ;

		return trim( $content ) ;
	}

	/**
	 * Check if the file is already min file
	 *
	 * @since  1.9
	 * @access private
	 */
	private function _is_min( $filename )
	{
		$basename = basename( $filename ) ;
		if ( preg_match( '|[-\.]min\.(?:[a-zA-Z]+)$|i', $basename ) ) {
			return true ;
		}

		return false ;
	}

	/**
	 * Remove comment when minify
	 *
	 * @since  1.7.1
	 * @since  1.9 Moved here from optiize.cls
	 * @access private
	 */
	private function _remove_comment( $content, $type )
	{
		$_from = array(
			'|\/\*.*\*\/|U',
			'|\/\*.*\*\/|sU',
			"|\n+|",
			// "|;+\n*;+|",
			// "|\n+;|",
			// "|;\n+|"
		) ;

		$_to = array(
			'',
			"\n",
			"\n",
			// ';',
			// ';',
			// ';',
		) ;

		$content = preg_replace( $_from, $_to, $content ) ;
		if ( $type == 'css' ) {
			$content = preg_replace( "|: *|", ':', $content ) ;
			$content = preg_replace( "| */ *|", '/', $content ) ;
		}
		$content = trim( $content ) ;
		return $content ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.9
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


