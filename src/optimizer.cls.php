<?php
/**
 * The optimize4 class.
 *
 * @since      	1.9
 * @package  	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Optimizer extends Instance {
	protected static $_instance;

	private $_conf_css_font_display;

	/**
	 * Init optimizer
	 *
	 * @since  1.9
	 * @access protected
	 */
	protected function __construct() {
		$this->_conf_css_font_display = Conf::val( Base::O_OPTM_CSS_FONT_DISPLAY );
		if ( ! empty( Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ] ) ) {
			$this->_conf_css_font_display = Base::$CSS_FONT_DISPLAY_SET[ $this->_conf_css_font_display ];
		}
	}

	/**
	 * Run HTML minify process and return final content
	 *
	 * @since  1.9
	 * @access public
	 */
	public function html_min( $content, $force_inline_minify = false ) {
		$options = array();
		if ( Conf::val( Base::O_OPTM_CSS_INLINE_MIN ) || $force_inline_minify ) {
			$options[ 'cssMinifier' ] = __CLASS__ . '::minify_css';
		}

		if ( Conf::val( Base::O_OPTM_JS_INLINE_MIN ) || $force_inline_minify ) {
			$options[ 'jsMinifier' ] = __CLASS__ . '::minify_js';
		}

		/**
		 * Added exception capture when minify
		 * @since  2.2.3
		 */
		try {
			$obj = new Lib\HTML_MIN( $content, $options );
			$content_final = $obj->process();
			if ( ! defined( 'LSCACHE_ESI_SILENCE' ) ) {
				$content_final .= "\n" . '<!-- Page optimized by LiteSpeed Cache @' . date('Y-m-d H:i:s') . ' -->';
			}
			return $content_final;

		} catch ( \Exception $e ) {
			Debug2::debug( '******[Optmer] html_min failed: ' . $e->getMessage() );
			error_log( '****** LiteSpeed Optimizer html_min failed: ' . $e->getMessage() );
			return $content;
		}
	}

	/**
	 * Run minify process and return final content
	 *
	 * @since  1.9
	 * @access public
	 */
	public function serve( $filename, $concat_only, $src_list = false, $page_url = false ) {
		$ua = ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '';

		// Search src set in db based on the requested filename
		if ( ! $src_list ) {
			$optm_data = Data::get_instance()->optm_hash2src( $filename );
			if ( empty( $optm_data[ 'src' ] ) || ! is_array( $optm_data[ 'src' ] ) ) {
				return false;
			}
			$src_list = $optm_data[ 'src' ];
			$page_url = $optm_data[ 'refer' ];
		}

		$file_type = substr( $filename, strrpos( $filename, '.' ) + 1 );

		// Check if need to run Unique CSS feature
		if ( $file_type == 'css' ) {
			// CHeck if need to trigger UCSS or not
			$content = false;
			if ( Conf::val( Base::O_OPTM_UCSS ) && ! Conf::val( Base::O_OPTM_UCSS_ASYNC ) ) {
				$content = CSS::get_instance()->gen_ucss( $page_url, $ua );//todo: how to store ua!!!
			}

			$content = apply_filters( 'litespeed_css_serve', $content, $filename, $src_list, $page_url );
			if ( $content ) {
				Debug2::debug( '[Optmer] Content from filter `litespeed_css_serve` for [file] ' . $filename . ' [url] ' . $page_url );
				return $content;
			}
		}

		// Parse real file path
		$real_files = array();
		foreach ( $src_list as $src_info ) {
			$src = ! empty( $src_info[ 'src' ] ) ? $src_info[ 'src' ] : $src_info;
			$postfix = pathinfo( parse_url( $src, PHP_URL_PATH ), PATHINFO_EXTENSION );
			if ( $postfix != $file_type ) {
				Debug2::debug2( '[Optmer] Not static file, will read as remote [src] ' . $src );
				$real_file = $src;
			}
			else {
				$real_file = Utility::is_internal_file( $src );
				$real_file = ! empty( $real_file[ 0 ] ) ? $real_file[ 0 ] : false;
			}

			if ( ! $real_file ) {
				continue;
			}

			if ( ! empty( $src_info[ 'media' ] ) ) {
				$real_files[] = array(
					'src' => $real_file,
					'media' => $src_info[ 'media' ],
				);
			}
			else {
				$real_files[] = $real_file;
			}
		}

		if ( ! $real_files ) {
			return false;
		}

		Debug2::debug2( '[Optmer]    src_list : ', $src_list );

		// set_error_handler( 'litespeed_exception_handler' );

		$content = '';
		// try {
		// Handle CSS
		if ( $file_type === 'css' ) {
			$content = $this->_serve_css( $real_files, $concat_only );
		}
		// Handle JS
		else {
			$content = $this->_serve_js( $real_files, $concat_only );
		}

		// } catch ( \Exception $e ) {
		// 	$tmp = '[url] ' . implode( ', ', $src_list ) . ' [err] ' . $e->getMessage();

		// 	Debug2::debug( '******[Optmer] serve err ' . $tmp );
		// 	error_log( '****** LiteSpeed Optimizer serve err ' . $tmp );
		// 	return false;//todo: return ori data
		// }
		// restore_error_handler();

		/**
		 * Clean comment when minify
		 * @since  1.7.1
		 */
		if ( Conf::val( Base::O_OPTM_RM_COMMENT ) ) {
			$content = $this->_remove_comment( $content, $file_type );
		}

		Debug2::debug2( '[Optmer]    Generated content ' . $file_type );

		// Add filter
		$content = apply_filters( 'litespeed_optm_cssjs', $content, $file_type, $src_list );

		return $content;
	}

	/**
	 * Serve css with/without minify
	 *
	 * @since  1.9
	 * @access private
	 */
	private function _serve_css( $files, $concat_only = false ) {
		$con = array();
		foreach ( $files as $path_info ) {
			$media = false;
			if ( ! empty( $path_info[ 'src' ] ) ) {
				$real_path = $path_info[ 'src' ];
				$media = $path_info[ 'media' ];
			}
			else {
				$real_path = $path_info;
			}
			Debug2::debug2( '[Optmer] [real_path] ' . $real_path );

			// Check if its remote or local path
			if ( strpos( $real_path, 'http' ) === 0 ) {
				$data = wp_remote_retrieve_body( wp_remote_get( $real_path ) );
				$dirname = dirname( parse_url( $real_path, PHP_URL_PATH ) );
			}
			else {
				$data = File::read( $real_path );
				$dirname = dirname( $real_path );
			}

			// Font optimize
			if ( $this->_conf_css_font_display ) {
				$data = preg_replace( '#(@font\-face\s*\{)#isU', '${1}font-display:' . $this->_conf_css_font_display . ';', $data );
			}

			$data = preg_replace( '/@charset[^;]+;\\s*/', '', $data );

			if ( ! $concat_only && ! $this->_is_min( $real_path ) ) {
				$data = self::minify_css( $data );
			}

			$data = Lib\CSS_MIN\UriRewriter::rewrite( $data, $dirname );

			if ( $media ) {
				$data = '@media ' . $media . '{' . $data . "\n}";
			}

			$con[] = $data;
		}

		return implode( '', $con );
	}

	/**
	 * Serve JS with/without minify
	 *
	 * @since  1.9
	 * @access private
	 */
	private function _serve_js( $files, $concat_only ) {
		$con = array();
		foreach ( $files as $real_path ) {
			// Check if its remote or local path
			if ( strpos( $real_path, 'http' ) === 0 ) {
				$data = wp_remote_retrieve_body( wp_remote_get( $real_path ) );
			}
			else {
				$data = File::read( $real_path );
			}

			if ( ! $concat_only && ! $this->_is_min( $real_path ) ) {
				$data = self::minify_js( $data );
			}
			else {
				$data = $this->_null_minifier( $data );
			}

			$con[] = $data;
		}

		return implode( "\n;", $con );
	}

	/**
	 * Minify CSS
	 *
	 * @since  2.2.3
	 * @access private
	 */
	public static function minify_css( $data ) {
		try {
			$obj = new Lib\CSS_MIN\Minifier();
			return $obj->run( $data );

		} catch ( \Exception $e ) {
			Debug2::debug( '******[Optmer] minify_css failed: ' . $e->getMessage() );
			error_log( '****** LiteSpeed Optimizer minify_css failed: ' . $e->getMessage() );
			return $data;
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
	public static function minify_js( $data, $js_type = '' ) {
		// For inline JS optimize, need to check if it's js type
		if ( $js_type ) {
			preg_match( '#type=([\'"])(.+)\g{1}#isU', $js_type, $matches );
			if ( $matches && $matches[ 2 ] != 'text/javascript' ) {
				Debug2::debug( '******[Optmer] minify_js bypass due to type: ' . $matches[ 2 ] );
				return $data;
			}
		}

		try {
			$data = Lib\JSMin::minify( $data );
			return $data;
		} catch ( \Exception $e ) {
			Debug2::debug( '******[Optmer] minify_js failed: ' . $e->getMessage() );
			// error_log( '****** LiteSpeed Optimizer minify_js failed: ' . $e->getMessage() );
			return $data;
		}
	}

	/**
	 * Basic minifier
	 *
	 * @access private
	 */
	private function _null_minifier( $content ) {
		$content = str_replace( "\r\n", "\n", $content );

		return trim( $content );
	}

	/**
	 * Check if the file is already min file
	 *
	 * @since  1.9
	 * @access private
	 */
	private function _is_min( $filename ) {
		$basename = basename( $filename );
		if ( preg_match( '|[-\.]min\.(?:[a-zA-Z]+)$|i', $basename ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Remove comment when minify
	 *
	 * @since  1.7.1
	 * @since  1.9 Moved here from optiize.cls
	 * @access private
	 */
	private function _remove_comment( $content, $type ) {
		$_from = array(
			'|\/\*.*\*\/|U',
			'|\/\*.*\*\/|sU',
			"|\n+|",
			// "|;+\n*;+|",
			// "|\n+;|",
			// "|;\n+|"
		);

		$_to = array(
			'',
			"\n",
			"\n",
			// ';',
			// ';',
			// ';',
		);

		$content = preg_replace( $_from, $_to, $content );
		if ( $type == 'css' ) {
			$content = preg_replace( "|: *|", ':', $content );
			$content = preg_replace( "| */ *|", '/', $content );
		}
		$content = trim( $content );
		return $content;
	}
}


