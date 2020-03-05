<?php
/**
 * The optimize4 class.
 *
 * @since      	1.9
 * @package  	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Optimizer extends Instance
{
	protected static $_instance ;

	private $_conf_css_font_display ;

	/**
	 * Init optimizer
	 *
	 * @since  1.9
	 * @access protected
	 */
	protected function __construct()
	{
		$this->_conf_css_font_display = Conf::val( Base::O_OPTM_CSS_FONT_DISPLAY ) ;
		if ( ! empty( Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ] ) ) {
			$this->_conf_css_font_display = Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ] ;
		}
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
		if ( Conf::val( Base::O_OPTM_CSS_INLINE_MIN ) ) {
			$options[ 'cssMinifier' ] = __CLASS__ . '::minify_css' ;
		}

		if ( Conf::val( Base::O_OPTM_JS_INLINE_MIN ) ) {
			$options[ 'jsMinifier' ] = __CLASS__ . '::minify_js' ;
		}

		/**
		 * Added exception capture when minify
		 * @since  2.2.3
		 */
		try {
			$obj = new Lib\HTML_MIN( $content, $options ) ;
			$content_final = $obj->process() ;
			if ( ! defined( 'LSCACHE_ESI_SILENCE' ) ) {
				$content_final .= "\n" . '<!-- Page optimized by LiteSpeed Cache @' . date('Y-m-d H:i:s') . ' -->' ;
			}
			return $content_final ;

		} catch ( \Exception $e ) {
			Debug2::debug( '******[Optmer] html_min failed: ' . $e->getMessage() ) ;
			error_log( '****** LiteSpeed Optimizer html_min failed: ' . $e->getMessage() ) ;
			return $content ;
		}
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
			$urls = Data::get_instance()->optm_hash2src( $filename ) ;
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
			$real_file = Utility::is_internal_file( $url ) ;
			if ( ! $real_file ) {
				continue ;
			}
			$real_files[] = $real_file[ 0 ] ;
		}

		if ( ! $real_files ) {
			return false;
		}

		Debug2::debug2( '[Optmer]    urls : ', $urls ) ;

		// set_error_handler( 'litespeed_exception_handler' ) ;

		$content = '' ;
		$tmp = parse_url( $urls[ 0 ], PHP_URL_PATH ) ;
		$file_type = substr( $tmp, strrpos( $tmp, '.' ) + 1 ) ;
		// try {
		// Handle CSS
		if ( $file_type === 'css' ) {
			$content = $this->_serve_css( $real_files, $concat_only ) ;
		}
		// Handle JS
		else {
			$content = $this->_serve_js( $real_files, $concat_only ) ;
		}

		// } catch ( \Exception $e ) {
		// 	$tmp = '[url] ' . implode( ', ', $urls ) . ' [err] ' . $e->getMessage() ;

		// 	Debug2::debug( '******[Optmer] serve err ' . $tmp ) ;
		// 	error_log( '****** LiteSpeed Optimizer serve err ' . $tmp ) ;
		// 	return false ;//todo: return ori data
		// }
		// restore_error_handler() ;

		/**
		 * Clean comment when minify
		 * @since  1.7.1
		 */
		if ( Conf::val( Base::O_OPTM_RM_COMMENT ) ) {
			$content = $this->_remove_comment( $content, $file_type ) ;
		}

		Debug2::debug2( '[Optmer]    Generated content ' . $file_type ) ;

		// Add filter
		$content = apply_filters( 'litespeed_optm_cssjs', $content, $file_type, $urls ) ;

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
			Debug2::debug2( '[Optmer] [real_path] ' . $real_path ) ;
			$data = File::read( $real_path ) ;

			// Font optimize
			if ( $this->_conf_css_font_display ) {
				$data = preg_replace( '#(@font\-face\s*\{)#isU', '${1}font-display:' . $this->_conf_css_font_display . ';', $data ) ;
			}

			$data = preg_replace( '/@charset[^;]+;\\s*/', '', $data ) ;

			if ( ! $concat_only && ! $this->_is_min( $real_path ) ) {
				$data = self::minify_css( $data ) ;
			}

			$data = Lib\CSS_MIN\UriRewriter::rewrite( $data, dirname( $real_path ) ) ;

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
			$data = File::read( $real_path ) ;

			if ( ! $concat_only && ! $this->_is_min( $real_path ) ) {
				$data = self::minify_js( $data ) ;
			}
			else {
				$data = $this->_null_minifier( $data ) ;
			}

			$con[] = $data ;
		}

		return implode( "\n;", $con ) ;
	}

	/**
	 * Minify CSS
	 *
	 * @since  2.2.3
	 * @access private
	 */
	public static function minify_css( $data )
	{
		try {
			$obj = new Lib\CSS_MIN\Minifier() ;
			return $obj->run( $data ) ;

		} catch ( \Exception $e ) {
			Debug2::debug( '******[Optmer] minify_css failed: ' . $e->getMessage() ) ;
			error_log( '****** LiteSpeed Optimizer minify_css failed: ' . $e->getMessage() ) ;
			return $data ;
		}
	}

	/**
	 * Minify JS
	 *
	 * Added exception capture when minify
	 *
	 * @since  2.2.3
	 * @access private
	 */
	public static function minify_js( $data, $js_type = '' )
	{
		// For inline JS optimize, need to check if it's js type
		if ( $js_type ) {
			preg_match( '#type=([\'"])(.+)\g{1}#isU', $js_type, $matches ) ;
			if ( $matches && $matches[ 2 ] != 'text/javascript' ) {
				Debug2::debug( '******[Optmer] minify_js bypass due to type: ' . $matches[ 2 ] ) ;
				return $data ;
			}
		}

		try {
			$data = Lib\JSMin::minify( $data ) ;
			return $data ;
		} catch ( \Exception $e ) {
			Debug2::debug( '******[Optmer] minify_js failed: ' . $e->getMessage() ) ;
			// error_log( '****** LiteSpeed Optimizer minify_js failed: ' . $e->getMessage() ) ;
			return $data ;
		}
	}

	/**
	 * Basic minifier
	 *
	 * @access private
	 */
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
}


