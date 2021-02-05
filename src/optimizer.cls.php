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

class Optimizer extends Root {
	private $_conf_css_font_display;

	/**
	 * Init optimizer
	 *
	 * @since  1.9
	 */
	public function __construct() {
		$this->_conf_css_font_display = $this->conf( Base::O_OPTM_CSS_FONT_DISPLAY );
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
	 * Run minify process and save content
	 *
	 * @since  1.9
	 * @access public
	 */
	public function serve( $static_file, $file_type, $concat_only, $src_list = false, $page_url = false ) {
		// Check if need to run Unique CSS feature
		if ( $file_type == 'css' ) {
			// CHeck if need to trigger UCSS or not
			$content = false;
			if ( $this->conf( Base::O_OPTM_UCSS ) && ! $this->conf( Base::O_OPTM_UCSS_ASYNC ) ) {
				$ua = ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '';
				$content = $this->cls( 'CSS' )->gen_ucss( $page_url, $ua );//todo: how to store ua!!!
			}

			$content = apply_filters( 'litespeed_css_serve', $content, $static_file, $src_list, $page_url );
			if ( $content ) {
				Debug2::debug( '[Optmer] Content from filter `litespeed_css_serve` for [file] ' . $static_file . ' [url] ' . $page_url );
				File::save( $static_file, $content, true ); // todo: UCSS CDN and CSS font display setting
				return true;
			}
		}

		// Create tmp file to avoid conflict
		$tmp_static_file = $static_file . '.tmp';
		if ( file_exists( $tmp_static_file ) && time() - filemtime( $tmp_static_file ) <= 600 ) { // some other request is generating
			return false;
		}
		File::save( $tmp_static_file, '/* ' . ( is_array( $src_list ) ? $page_url : $src_list ) . ' */', true );

		// Load content
		$real_files = array();
		if ( ! is_array( $src_list ) ) {
			$src_list = array( array( 'src' => $src_list ) );
		}
		foreach ( $src_list as $src_info ) {
			$is_min = false;
			$src = false;
			if ( ! empty( $src_info[ 'inl' ] ) ) { // Load inline
				$content = $src_info[ 'src' ];
			}
			else { // Load file
				$content = $this->cls( 'CSS' )->load_file( $src_info[ 'src' ], $file_type );

				if ( ! $content ) {
					continue;
				}

				$is_min = $this->_is_min( $src_info[ 'src' ] );
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

				$content = $this->cls( 'CDN' )->finalize( $content );
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
			$content = apply_filters( 'litespeed_optm_cssjs', $content, $file_type, $src_info[ 'src' ] );

			// Write to file
			File::save( $tmp_static_file, $content, true, true );
		}

		rename( $tmp_static_file, $static_file );

		Debug2::debug2( '[Optmer] Saved static file [path] ' . $static_file );
		return true;
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


