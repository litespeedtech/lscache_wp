<?php
/**
 * The optimize css class.
 *
 * @since      	2.3
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class CSS extends Base {
	const LOG_TAG = '[CSS]';

	const TYPE_GEN_CCSS = 'gen_ccss';
	const TYPE_CLEAR_Q_CCSS = 'clear_q_ccss';

	protected $_summary;
	private $_queue;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * HTML lazyload CSS
	 * @since 4.0
	 */
	public function prepare_html_lazy() {
		return '<style>' . implode( ',', $this->conf( self::O_OPTM_HTML_LAZY ) ) . '{content-visibility:auto;contain-intrinsic-size:1px 1000px;}</style>';
	}

	/**
	 * Output critical css
	 *
	 * @since  1.3
	 * @access public
	 */
	public function prepare_ccss() {
		// Get critical css for current page
		// Note: need to consider mobile
		$rules = $this->_ccss();
		if ( ! $rules ) {
			return null;
		}

		$error_tag = '';
		if ( substr( $rules, 0, 2 ) == '/*' && substr( $rules, -2 ) == '*/' ) {
			$error_tag = ' data-error="failed to generate"';
		}

		// Append default critical css
		$rules .= $this->conf( self::O_OPTM_CCSS_CON );

		return '<style id="litespeed-ccss"' . $error_tag . '>' . $rules . '</style>';
	}

	/**
	 * Generate CCSS url tag
	 *
	 * @since 4.0
	 */
	private function _gen_ccss_file_tag( $request_url ) {
		if ( is_404() ) {
			return '404';
		}

		if ( $this->conf( self::O_OPTM_CCSS_PER_URL ) ) {
			return $request_url;
		}

		$sep_uri = $this->conf( self::O_OPTM_CCSS_SEP_URI );
		if ( $sep_uri && $hit = Utility::str_hit_array( $request_url, $sep_uri ) ) {
			Debug2::debug( '[CCSS] Separate CCSS due to separate URI setting: ' . $hit );
			return $request_url;
		}

		$pt = Utility::page_type();

		$sep_pt = $this->conf( self::O_OPTM_CCSS_SEP_POSTTYPE );
		if ( in_array( $pt, $sep_pt ) ) {
			Debug2::debug( '[CCSS] Separate CCSS due to posttype setting: ' . $pt );
			return $request_url;
		}

		// Per posttype
		return $pt;
	}

	/**
	 * The critical css content of the current page
	 *
	 * @since  2.3
	 */
	private function _ccss() {
		global $wp;
		$request_url = home_url( $wp->request );

		$filepath_prefix = $this->_build_filepath_prefix( 'ccss' );
		$url_tag = $this->_gen_ccss_file_tag( $request_url );
		$vary = $this->cls( 'Vary' )->finalize_full_varies();
		$filename = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'ccss' );
		if ( $filename ) {
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';

			if ( file_exists( $static_file ) ) {
				Debug2::debug2( '[CSS] existing ccss ' . $static_file );
				return File::read( $static_file );
			}
		}

		$uid = get_current_user_id();

		$ua = ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '';


		// Store it to prepare for cron
		$this->_queue = $this->load_queue( 'ccss' );

		if ( count( $this->_queue ) > 500 ) {
			self::debug( 'CCSS Queue is full - 500' );
			return null;
		}

		$queue_k = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
		$this->_queue[ $queue_k ] = array(
			'url'			=> apply_filters( 'litespeed_ccss_url', $request_url ),
			'user_agent'	=> substr( $ua, 0, 200 ),
			'is_mobile'		=> $this->_separate_mobile(),
			'is_webp'		=> $this->cls( 'Media' )->webp_support() ? 1 : 0,
			'uid'			=> $uid,
			'vary'			=> $vary,
			'url_tag'		=> $url_tag,
		); // Current UA will be used to request
		$this->save_queue( 'ccss', $this->_queue );
		self::debug( 'Added queue_ccss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary  . ' [uid] ' . $uid );

		// Prepare cache tag for later purge
		Tag::add( 'CCSS.' . md5( $queue_k ) );

		// For v4.1- clean up
		if ( isset( $this->_summary[ 'ccss_type_history' ] ) || isset( $this->_summary[ 'ccss_history' ] ) || isset( $this->_summary[ 'queue_ccss' ] ) ) {
			if ( isset( $this->_summary[ 'ccss_type_history' ] ) ) {
				unset( $this->_summary[ 'ccss_type_history' ] );
			}
			if ( isset( $this->_summary[ 'ccss_history' ] ) ) {
				unset( $this->_summary[ 'ccss_history' ] );
			}
			if ( isset( $this->_summary[ 'queue_ccss' ] ) ) {
				unset( $this->_summary[ 'queue_ccss' ] );
			}
			self::save_summary();
		}

		return null;
	}

	/**
	 * Cron ccss generation
	 *
	 * @since  2.3
	 * @access private
	 */
	public static function cron_ccss( $continue = false ) {
		$_instance = self::cls();
		return $_instance->_cron_handler( 'ccss', $continue );
	}

	/**
	 * Handle UCSS/CCSS cron
	 *
	 * @since 4.2
	 */
	private function _cron_handler( $type, $continue ) {
		$this->_queue = $this->load_queue( $type );

		if ( empty( $this->_queue ) ) {
			return;
		}

		$type_tag = strtoupper( $type );

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( ! empty( $this->_summary[ 'curr_request_' . $type ] ) && time() - $this->_summary[ 'curr_request_' . $type ] < 300 && ! $this->conf( self::O_DEBUG ) ) {
				Debug2::debug( '[' . $type_tag . '] Last request not done' );
				return;
			}
		}

		$i = 0;
		foreach ( $this->_queue as $k => $v ) {
			if ( ! empty( $v[ '_status' ] ) ) {
				continue;
			}

			Debug2::debug( '[' . $type_tag . '] cron job [tag] ' . $k . ' [url] ' . $v[ 'url' ] . ( $v[ 'is_mobile' ] ? ' 📱 ' : '' ) . ' [UA] ' . $v[ 'user_agent' ] );

			if ( $type == 'ccss' && empty( $v[ 'url_tag' ] ) ) {
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );
				Debug2::debug( '[CCSS] wrong queue_ccss format' );
				continue;
			}

			if ( ! isset( $v[ 'is_webp' ] ) ) {
				$v[ 'is_webp' ] = false;
			}

			$i ++;
			$res = $this->_send_req( $v[ 'url' ], $k, $v[ 'uid' ], $v[ 'user_agent' ], $v[ 'vary' ], $v[ 'url_tag' ], $type, $v[ 'is_mobile' ], $v[ 'is_webp' ] );
			if ( ! $res ) { // Status is wrong, drop this this->_queue
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );

				if ( ! $continue ) {
					return;
				}

				if ( $i > 3 ) {
					GUI::print_loading( count( $this->_queue ), $type_tag );
					return Router::self_redirect( Router::ACTION_CSS, CSS::TYPE_GEN_CCSS );
				}

				continue;
			}

			// Exit queue if out of quota
			if ( $res === 'out_of_quota' ) {
				return;
			}

			$this->_queue[ $k ][ '_status' ] = 'requested';
			$this->save_queue( $type, $this->_queue );

			// only request first one
			if ( ! $continue ) {
				return;
			}

			if ( $i > 3 ) {
				GUI::print_loading( count( $this->_queue ), $type_tag );
				return Router::self_redirect( Router::ACTION_CSS, CSS::TYPE_GEN_CCSS );
			}
		}
	}

	/**
	 * Send to QC API to generate CCSS/UCSS
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _send_req( $request_url, $queue_k, $uid, $user_agent, $vary, $url_tag, $type, $is_mobile, $is_webp ) {
		// Check if has credit to push or not
		$err = false;
		$allowance = $this->cls( 'Cloud' )->allowance( Cloud::SVC_CCSS, $err );
		if ( ! $allowance ) {
			Debug2::debug( '[CCSS] ❌ No credit: ' . $err );
			$err && Admin_Display::error( Error::msg( $err ) );
			return 'out_of_quota';
		}

		set_time_limit( 120 );

		// Update css request status
		$this->_summary[ 'curr_request_' . $type ] = time();
		self::save_summary();

		// Gather guest HTML to send
		$html = $this->prepare_html( $request_url, $user_agent, $uid );

		if ( ! $html ) {
			return false;
		}

		// Parse HTML to gather all CSS content before requesting
		$css = false;
		if ( $type == 'ccss' ) {
			list( $css, $html ) = $this->prepare_css( $html, $is_webp );
		}
		else {
			list( , $html ) = $this->prepare_css( $html, $is_webp, true ); // Use this to drop CSS from HTML as we don't need those CSS to generate UCSS
			$filename = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'css' );
			$filepath_prefix = $this->_build_filepath_prefix( 'css' );
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';
			Debug2::debug( '[UCSS] Checking combined file ' . $static_file );
			if ( file_exists( $static_file ) ) {
				$css = File::read( $static_file );
			}
		}

		if ( ! $css ) {
			Debug2::debug( '[UCSS] ❌ No combined css' );
			return false;
		}

		// Generate critical css
		$data = array(
			'url'			=> $request_url,
			'queue_k'		=> $queue_k,
			'user_agent'	=> $user_agent,
			'is_mobile'		=> $is_mobile ? 1 : 0, // todo:compatible w/ tablet
			'is_webp'		=> $is_webp ? 1 : 0,
			'html'			=> $html,
			'css'			=> $css,
		);

		self::debug( 'Generating: ', $data );

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 30 );
		if ( ! is_array( $json ) ) {
			return false;
		}

		// Old version compatibility
		if ( empty( $json[ 'status' ] ) ) {
			if ( ! empty( $json[ $type ] ) ) {
				$this->_save_con( $type, $json[ $type ], $queue_k );
			}

			// Delete the row
			return false;
		}

		// Unknown status, remove this line
		if ( $json[ 'status' ] != 'queued' ) {
			return false;
		}

		// Save summary data
		$this->_summary[ 'last_spent_' . $type ] = time() - $this->_summary[ 'curr_request_' . $type ];
		$this->_summary[ 'last_request_' . $type ] = $this->_summary[ 'curr_request_' . $type ];
		$this->_summary[ 'curr_request_' . $type ] = 0;
		self::save_summary();

		return true;
	}

	/**
	 * Save CCSS/UCSS content
	 *
	 * @since 4.2
	 */
	private function _save_con( $type, $css, $queue_k ) {
		// Add filters
		$css = apply_filters( 'litespeed_' . $type, $css, $queue_k );
		Debug2::debug2( '[CSS] con: ' . $css );

		if ( substr( $css, 0, 2 ) == '/*' && substr( $css, -2 ) == '*/' ) {
			self::debug( '❌ empty ' . $type . ' [content] ' . $css );
			// continue; // Save the error info too
		}

		// Write to file
		$filecon_md5 = md5( $css );

		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.css';

		File::save( $static_file, $css, true );

		$url_tag = $this->_queue[ $queue_k ][ 'url_tag' ];
		$vary = $this->_queue[ $queue_k ][ 'vary' ];
		Debug2::debug2( "[CSS] Save URL to file [file] $static_file [vary] $vary" );

		$this->cls( 'Data' )->save_url( $url_tag, $vary, $type, $filecon_md5, dirname( $static_file ) );

		Purge::add( strtoupper( $type ) . '.' . md5( $queue_k ) );
	}

	/**
	 * Play for fun
	 *
	 * @since  3.4.3
	 */
	public function test_url( $request_url ) {
		$user_agent = $_SERVER[ 'HTTP_USER_AGENT' ];
		$html = $this->prepare_html( $request_url, $user_agent );
		list( $css, $html ) = $this->prepare_css( $html, true, true );
		// var_dump( $css );
// 		$html = <<<EOT

// EOT;

// 		$css = <<<EOT

// EOT;
		$data = array(
			'url'			=> $request_url,
			'ccss_type'		=> 'test',
			'user_agent'	=> $user_agent,
			'is_mobile'		=> 0,
			'html'			=> $html,
			'css'			=> $css,
			'type'			=> 'CCSS',
		);

		// self::debug( 'Generating: ', $data );

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 180 );

		var_dump($json);
	}

	/**
	 * Prepare HTML from URL
	 *
	 * @since  3.4.3
	 */
	public function prepare_html( $request_url, $user_agent, $uid = false ) {
		$html = $this->cls( 'Crawler' )->self_curl( add_query_arg( 'LSCWP_CTRL', 'before_optm', $request_url ), $user_agent, $uid );
		Debug2::debug2( '[CSS] self_curl result....', $html );


		$html = $this->cls( 'Optimizer' )->html_min( $html, true );
		// Drop <noscript>xxx</noscript>
		$html = preg_replace( '#<noscript>.*</noscript>#isU', '', $html );

		return $html;
	}

	/**
	 * Prepare CSS from HTML for CCSS generation only. UCSS will used combined CSS directly.
	 * Prepare refined HTML for both CCSS and UCSS.
	 *
	 * @since  3.4.3
	 */
	public function prepare_css( $html, $is_webp = false, $dryrun = false ) {
		$css = '';
		preg_match_all( '#<link ([^>]+)/?>|<style([^>]*)>([^<]+)</style>#isU', $html, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$debug_info = '';
			if ( strpos( $match[ 0 ], '<link' ) === 0 ) {
				$attrs = Utility::parse_attr( $match[ 1 ] );

				if ( empty( $attrs[ 'rel' ] ) ) {
					continue;
				}

				if ( $attrs[ 'rel' ] != 'stylesheet' ) {
					if ( $attrs[ 'rel' ] != 'preload' || empty( $attrs[ 'as' ] ) || $attrs[ 'as' ] != 'style' ) {
						continue;
					}
				}

				if ( ! empty( $attrs[ 'media' ] ) && strpos( $attrs[ 'media' ], 'print' ) !== false ) {
					continue;
				}

				if ( empty( $attrs[ 'href' ] ) ) {
					continue;
				}

				// Check Google fonts hit
				if ( strpos( $attrs[ 'href' ], 'fonts.googleapis.com' ) !== false ) {
					$html = str_replace( $match[ 0 ], '', $html );
					continue;
				}

				$debug_info = $attrs[ 'href' ];

				// Load CSS content
				if ( ! $dryrun ) { // Dryrun will not load CSS but just drop them
					$con = $this->cls( 'Optimizer' )->load_file( $attrs[ 'href' ] );
					if ( ! $con ) {
						continue;
					}
				}
				else {
					$con = '';
				}
			}
			else { // Inline style
				$attrs = Utility::parse_attr( $match[ 2 ] );

				if ( ! empty( $attrs[ 'media' ] ) && strpos( $attrs[ 'media' ], 'print' ) !== false ) {
					continue;
				}

				Debug2::debug2( '[CSS] Load inline CSS ' . substr( $match[ 3 ], 0, 100 ) . '...', $attrs );
				$con = $match[ 3 ];

				$debug_info = '__INLINE__';
			}

			$con = Optimizer::minify_css( $con );
			if ( $is_webp && $this->cls( 'Media' )->webp_support() ) {
				$con = $this->cls( 'Media' )->replace_background_webp( $con );
			}

			if ( ! empty( $attrs[ 'media' ] ) && $attrs[ 'media' ] !== 'all' ) {
				$con = '@media ' . $attrs[ 'media' ] . '{' . $con . "}\n";
			}
			else {
				$con = $con . "\n";
			}

			$con = '/* ' . $debug_info . ' */' . $con;
			$css .= $con;

			$html = str_replace( $match[ 0 ], '', $html );
		}

		return array( $css, $html );
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.3
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_GEN_CCSS:
				self::cron_ccss( true );
				break;

			case self::TYPE_CLEAR_Q_CCSS:
				$this->clear_q( 'ccss' );
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}
