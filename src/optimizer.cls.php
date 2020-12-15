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

		if ( $force_inline_minify ) {
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
		$__css = CSS::get_instance();
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
				$content = $__css->gen_ucss( $page_url, $ua );//todo: how to store ua!!!
			}

			$content = apply_filters( 'litespeed_css_serve', $content, $filename, $src_list, $page_url );
			if ( $content ) {
				Debug2::debug( '[Optmer] Content from filter `litespeed_css_serve` for [file] ' . $filename . ' [url] ' . $page_url );
				return $content;
			}
		}

		// Clear if existed
		$static_file = LITESPEED_STATIC_DIR . "/cssjs/$filename";
		File::save( $static_file, '', true ); // TODO: need to lock file too

		// Load content
		$real_files = array();
		foreach ( $src_list as $src_info ) {
			$is_min = false;
			$src = false;
			if ( ! empty( $src_info[ 'inl' ] ) ) { // Load inline
				$content = $src_info[ 'src' ];
			}
			else { // Load file
				$src = ! empty( $src_info[ 'src' ] ) ? $src_info[ 'src' ] : $src_info;
				$content = $__css->load_file( $src, $file_type );

				if ( ! $content ) {
					continue;
				}

				$is_min = $this->_is_min( $src );
			}

			// CSS related features
			if ( $file_type == 'css' ) {
				// Font optimize
				if ( $this->_conf_css_font_display ) {
					$content = preg_replace( '#(@font\-face\s*\{)#isU', '${1}font-display:' . $this->_conf_css_font_display . ';', $content );
				}

				$content = preg_replace( '/@charset[^;]+;\\s*/', '', $content );

				if ( ! empty( $src_info[ 'media' ] ) ) {
					$content = '@media ' . $src_info[ 'media' ] . '{' . $content . "\n}";
				}

				if ( ! $concat_only && ! $is_min ) {
					$content = self::minify_css( $content );
				}

				$content = CDN::finalize( $content );
			}
			else {
				if ( ! $concat_only && ! $is_min ) {
					$content = self::minify_js( $content );
				}
				else {
					$content = $this->_null_minifier( $content );
				}

				$content .= "\n;";
			}

			// Add filter
			$content = apply_filters( 'litespeed_optm_cssjs', $content, $file_type, $src );

			// Write to file
			File::save( $static_file, $content, true, true );

		}

		Debug2::debug2( '[Optmer] Saved static file [path] ' . $static_file );
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

}


