<?php

/**
 * The optimize4 class.
 *
 * @since      	1.9
 * @package  	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
require_once LSCWP_DIR . 'lib/css_min.cls.php' ;
require_once LSCWP_DIR . 'lib/css_min.colors.cls.php' ;
require_once LSCWP_DIR . 'lib/css_min.utils.cls.php' ;
require_once LSCWP_DIR . 'lib/url_rewritter.cls.php' ;

use tubalmartin\CssMin\Minifier as CSSmin;
use tubalmartin\CssMin\Colors as Colors;
use tubalmartin\CssMin\Utils as Utils;



class LiteSpeed_Cache_Optimizer
{
	private static $_instance ;

	/**
	 * Run minify process and return final content
	 *
	 * @since  1.9
	 * @access public
	 * @return string The final content
	 */
	public function serve( $filename, $concat_only )
	{
		// Search filename in db for src URLs
		$urls = LiteSpeed_Cache_Data::optm_hash2src( $filename ) ;
		if ( ! $urls || ! is_array( $urls ) ) {
			return false;
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


		set_error_handler( 'litespeed_exception_handler' ) ;

		$headers = array() ;
		$content = '' ;
		$file_type = substr( $filename, strrpos( $filename, '.' ) + 1 ) ;
		try {
			// Handle CSS
			if ( $file_type === 'css' ) {
				$content = $this->_serve_css( $files, $concat_only ) ;
				$headers[ 'Content-Encoding' ] = 'text/css; charset=utf-8' ;
			}
			// Handle JS
			else {
			}

		} catch ( ErrorException $e ) {
			LiteSpeed_Cache_Log::debug( 'Error when serving from optimizer: ' . $e->getMessage() ) ;
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

		LiteSpeed_Cache_Log::debug( 'Optm:    Generated content' ) ;

		$headers[ 'Content-Length' ] = strlen( $content ) ;

		foreach ( $headers as $key => $val ) {
			header( $key . ': ' . $val ) ;
		}

		return $content ;
	}

	/**
	 * Serve css with/without minify
	 *
	 * @since  1.9
	 * @access private
	 */
	private function _serve_css( $files, $concat_only )
	{
		$con = array() ;
		foreach ( $files as $real_path ) {
			$data = file_get_contents( $real_path ) ;

			$data = preg_replace( '/@charset[^;]+;\\s*/', '', $data ) ;

			if ( ! $concat_only ) {
				$obj = new CSSmin() ;
				$data = $obj->run( $data ) ;
			}

			$data = Minify_CSS_UriRewriter::rewrite( $data, dirname( $real_path ) ) ;

			$con[] = $data ;
		}

		return implode( '', $con ) ;
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
		if ( $type == 'text/css' ) {
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


