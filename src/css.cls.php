<?php
/**
 * The optimize css class.
 *
 * @since      	2.3
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class CSS extends Trunk {
	const TYPE_GENERATE_CRITICAL = 'generate_critical';
	const TYPE_CLEAR_Q = 'clear_q';

	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
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

		// Append default critical css
		$rules .= $this->conf( self::O_OPTM_CCSS_CON );

		return '<style id="litespeed-optm-css-rules">' . $rules . '</style>';
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  2.3
	 * @access public
	 */
	public function rm_cache_folder( $subsite_id = false ) {
		if ( $subsite_id ) {
			file_exists( LITESPEED_STATIC_DIR . '/ccss/' . $subsite_id ) && File::rrmdir( LITESPEED_STATIC_DIR . '/ccss/' . $subsite_id );
		}
		else {
			file_exists( LITESPEED_STATIC_DIR . '/ccss' ) && File::rrmdir( LITESPEED_STATIC_DIR . '/ccss' );
		}

		// Clear CCSS in queue too
		$this->_summary[ 'queue' ] = array();
		$this->_summary[ 'curr_request' ] = 0;
		self::save_summary();

		Debug2::debug2( '[CSS] Cleared ccss queue' );
	}

	/**
	 * The critical css content of the current page
	 *
	 * @since  2.3
	 */
	private function _ccss() {
		global $wp;
		$request_url = home_url( $wp->request );

		$file_path_prefix = '/ccss/';
		if ( is_multisite() ) {
			$file_path_prefix .= get_current_blog_id() . '/';
		}

		$req_url_tag = is_404() ? '404' : $request_url;

		$vary = $this->cls( 'Vary' )->finalize_full_varies();
		$filename = $this->cls( 'Data' )->load_url_file( $req_url_tag, $vary, 'ccss' );
		if ( $filename ) {
			$static_file = LITESPEED_STATIC_DIR . $file_path_prefix . $filename . '.css';

			if ( file_exists( $static_file ) ) {
				Debug2::debug2( '[CSS] existing ccss ' . $static_file );
				return File::read( $static_file );
			}
		}

		$uid = get_current_user_id();

		// Store it to prepare for cron
		if ( empty( $this->_summary[ 'queue' ] ) ) {
			$this->_summary[ 'queue' ] = array();
		}
		$queue_k = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $req_url_tag;
		$this->_summary[ 'queue' ][ $queue_k ] = array(
			'url'			=> $request_url,
			'user_agent'	=> $_SERVER[ 'HTTP_USER_AGENT' ],
			'is_mobile'		=> $this->_separate_mobile_ccss(),
			'uid'			=> $uid,
			'vary'			=> $vary,
			'url_tag'		=> $req_url_tag,
		); // Current UA will be used to request
		Debug2::debug( '[CSS] Added queue [req_url_tag] ' . $req_url_tag . ' [UA] ' . $_SERVER[ 'HTTP_USER_AGENT' ] . ' [vary] ' . $vary  . ' [uid] ' . $uid );

		// Prepare cache tag for later purge
		Tag::add( 'CCSS.' . md5( $queue_k ) );

		self::save_summary();
		return null;
	}

	/**
	 * Check if need to separate ccss for mobile
	 *
	 * @since  2.6.4
	 * @access private
	 */
	private function _separate_mobile_ccss() {
		return ( wp_is_mobile() || apply_filters( 'litespeed_is_mobile', false ) ) && $this->conf( self::O_CACHE_MOBILE );
	}

	/**
	 * Cron ccss generation
	 *
	 * @since  2.3
	 * @access private
	 */
	public static function cron_ccss( $continue = false ) {
		$_instance = self::cls();
		if ( empty( $_instance->_summary[ 'queue' ] ) ) {
			return;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( ! empty( $_instance->_summary[ 'curr_request' ] ) && time() - $_instance->_summary[ 'curr_request' ] < 300 && ! $_instance->conf( self::O_DEBUG ) ) {
				Debug2::debug( '[CCSS] Last request not done' );
				return;
			}
		}

		foreach ( $_instance->_summary[ 'queue' ] as $k => $v ) {
			Debug2::debug( '[CSS] cron job [type] ' . $k . ' [url] ' . $v[ 'url' ] . ( $v[ 'is_mobile' ] ? ' 📱 ' : '' ) . ' [UA] ' . $v[ 'user_agent' ] );

			if ( empty( $v[ 'url_tag' ] ) ) {
				Debug2::debug( '[CSS] wrong queue format' );
				$this->_popup_and_save( $k, $v[ 'url' ] );
				continue;
			}

			$_instance->_generate_ccss( $v[ 'url' ], $k, $v[ 'uid' ], $v[ 'user_agent' ], $v[ 'vary' ], $v[ 'url_tag' ], $v[ 'is_mobile' ] );

			Purge::add( 'CCSS.' . md5( $k ) );

			// only request first one
			if ( ! $continue ) {
				return;
			}
		}
	}

	/**
	 * Send to LiteSpeed CCSS API to generate CCSS
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _generate_ccss( $request_url, $queue_k, $uid, $user_agent, $vary, $url_tag, $is_mobile ) {
		// Check if has credit to push
		$allowance = $this->cls( 'Cloud' )->allowance( Cloud::SVC_CCSS );
		if ( ! $allowance ) {
			Debug2::debug( '[CCSS] ❌ No credit' );
			Admin_Display::error( Error::msg( 'lack_of_quota' ) );
			return;
		}

		// Update css request status
		$this->_summary[ 'curr_request' ] = time();
		self::save_summary();

		// Gather guest HTML to send
		$html = $this->_prepare_html( $request_url, $user_agent, $uid );

		if ( ! $html ) {
			return;
		}

		// Parse HTML to gather all CSS content before requesting
		list( $css, $html ) = $this->_prepare_css( $html );

		if ( ! $css ) {
			return;
		}

		// Generate critical css
		$data = array(
			'url'			=> $request_url,
			'ccss_type'		=> $queue_k,
			'user_agent'	=> $user_agent,
			'is_mobile'		=> $is_mobile ? 1 : 0, // todo:compatible w/ tablet
			'html'			=> $html,
			'css'			=> $css,
			'type'			=> 'CCSS',
		);

		Debug2::debug( '[CSS] Generating: ', $data );

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 30 );
		if ( ! is_array( $json ) ) {
			return;
		}

		if ( empty( $json[ 'ccss' ] ) ) {
			Debug2::debug( '[CSS] ❌ empty ccss' );
			$this->_popup_and_save( $queue_k, $request_url );
			return;
		}

		// Add filters
		$ccss = apply_filters( 'litespeed_ccss', $json[ 'ccss' ], $queue_k );
		Debug2::debug2( '[CSS] ccss con: ' . $ccss );

		// Write to file
		$filecon_md5 = md5( $ccss );

		$file_path_prefix = '/ccss/';
		if ( is_multisite() ) {
			$file_path_prefix .= get_current_blog_id() . '/';
		}
		$static_file = LITESPEED_STATIC_DIR . $file_path_prefix . $filecon_md5 . '.css';

		File::save( $static_file, $ccss, true );
		Debug2::debug2( "[CCSS] Save URL to file [file] $static_file [vary] $vary" );

		$this->cls( 'Data' )->save_url( $url_tag, $vary, 'ccss', $filecon_md5, dirname( $static_file ) );

		// Save summary data
		$this->_summary[ 'last_spent' ] = time() - $this->_summary[ 'curr_request' ];
		$this->_summary[ 'last_request' ] = $this->_summary[ 'curr_request' ];
		$this->_summary[ 'curr_request' ] = 0;
		$this->_popup_and_save( $queue_k, $request_url );
	}

	/**
	 * Play for fun
	 *
	 * @since  3.4.3
	 */
	public function test_url( $request_url ) {
		$user_agent = $_SERVER[ 'HTTP_USER_AGENT' ];
		$html = $this->_prepare_html( $request_url, $user_agent );
		list( $css, $html ) = $this->_prepare_css( $html, true );
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

		// Debug2::debug( '[CSS] Generating: ', $data );

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 180 );

		var_dump($json);
	}

	/**
	 * Prepare HTML from URL
	 *
	 * @since  3.4.3
	 */
	private function _prepare_html( $request_url, $user_agent, $uid = false ) {
		$html = $this->cls( 'Crawler' )->self_curl( add_query_arg( 'LSCWP_CTRL', 'before_optm', $request_url ), $user_agent, $uid );
		Debug2::debug2( '[CSS] self_curl result....', $html );


		$html = $this->cls( 'Optimizer' )->html_min( $html, true );
		// Drop <noscript>xxx</noscript>
		$html = preg_replace( '#<noscript>.*</noscript>#isU', '', $html );

		return $html;
	}

	/**
	 * Prepare CSS from HTML
	 *
	 * @since  3.4.3
	 */
	private function _prepare_css( $html, $dryrun =false ) {
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
					// continue; // Lets allow print to reuse this func in UCSS
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
				Debug2::debug2( '[CSS] Load inline CSS ' . substr( $match[ 3 ], 0, 100 ) . '...', $attrs );
				$con = $match[ 3 ];

				$debug_info = '__INLINE__';
			}

			$con = Optimizer::minify_css( $con );

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

	public function gen_ucss( $page_url, $ua ) {
		return $this->_generate_ucss( $page_url, $ua );
	}

	/**
	 * Send to QC API to generate UCSS
	 *
	 * @since  3.3
	 * @access private
	 */
	private function _generate_ucss( $request_url, $user_agent ) {
		// Check if has credit to push
		$allowance = Cloud::cls()->allowance( Cloud::SVC_CCSS );
		if ( ! $allowance ) {
			Debug2::debug( '[UCSS] ❌ No credit' );
			Admin_Display::error( Error::msg( 'lack_of_quota' ) );
			return;
		}

		// Update UCSS request status
		$this->_summary[ 'curr_request_ucss' ] = time();
		self::save_summary();

		// Generate UCSS
		$data = array(
			'type'			=> 'UCSS',
			'url'			=> $request_url,
			'whitelist'		=> $this->_filter_whitelist(),
			'user_agent'	=> $user_agent,
			'is_mobile'		=> $this->_separate_mobile_ccss(),
		);

		// Append cookie for roles auth
		if ( $uid = get_current_user_id() ) {
			// Get role simulation vary name
			$vary_name = $this->cls( 'Vary' )->get_vary_name();
			$vary_val = $this->cls( 'Vary' )->finalize_default_vary( $uid );
			$data[ 'cookies' ] = array();
			$data[ 'cookies' ][ $vary_name ] = $vary_val;
			$data[ 'cookies' ][ 'litespeed_role' ] = $uid;
			$data[ 'cookies' ][ 'litespeed_hash' ] = Router::get_hash();
		}

		Debug2::debug( '[UCSS] Generating UCSS: ', $data );

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 180 );
		if ( ! is_array( $json ) ) {
			return false;
		}

		if ( empty( $json[ 'ucss' ] ) ) {
			Debug2::debug( '[UCSS] ❌ empty ucss' );
			// $this->_popup_and_save( $ccss_type, $request_url );
			return false;
		}

		$ucss = $json[ 'ucss' ];
		Debug2::debug2( '[UCSS] ucss con: ' . $ucss );

		if ( substr( $ucss, 0, 2 ) == '/*' && substr( $ucss, -2 ) == '*/' ) {
			$ucss = '';
		}
		// Add filters
		$ucss = apply_filters( 'litespeed_ucss', $ucss, $request_url );

		// Write to file
		// File::save( $ucss_file, $ucss, true );

		// Save summary data
		$this->_summary[ 'last_spent_ucss' ] = time() - $this->_summary[ 'curr_request_ucss' ];
		$this->_summary[ 'last_request_ucss' ] = $this->_summary[ 'curr_request_ucss' ];
		$this->_summary[ 'curr_request_ucss' ] = 0;
		self::save_summary();
		// $this->_popup_and_save( $ccss_type, $request_url );

		// Debug2::debug( '[UCSS] saved ucss ' . $ucss_file );

		return $ucss;
	}

	/**
	 * Filter the comment content, add quotes to selector from whitelist. Return the json
	 *
	 * @since 3.3
	 */
	private function _filter_whitelist() {
		$whitelist = array();
		$val = $this->conf( self::O_OPTM_UCSS_WHITELIST );
		foreach ( $val as $k => $v ) {
			if ( substr( $v, 0, 2 ) === '//' ) {
				continue;
			}
			// Wrap in quotes for selectors
			if ( substr( $v, 0, 1 ) !== '/' && strpos( $v, '"' ) === false && strpos( $v, "'" ) === false ) {
				// $v = "'$v'";
			}
			$whitelist[] = $v;
		}

		return $whitelist;
	}

	/**
	 * Pop up the current request and save
	 *
	 * @since  3.0
	 */
	private function _popup_and_save( $queue_k, $request_url )
	{
		if ( empty( $this->_summary[ 'ccss_type_history' ] ) ) {
			$this->_summary[ 'ccss_type_history' ] = array();
		}
		$this->_summary[ 'ccss_type_history' ][ $queue_k ] = $request_url;

		while ( count( $this->_summary[ 'ccss_type_history' ] ) > 100 ) {
			array_shift( $this->_summary[ 'ccss_type_history' ] );
		}

		unset( $this->_summary[ 'queue' ][ $queue_k ] );

		self::save_summary();
	}

	/**
	 * Clear all waiting queues
	 *
	 * @since  3.4
	 */
	public function clear_q() {
		if ( empty( $this->_summary[ 'queue' ] ) ) {
			return;
		}

		$this->_summary[ 'queue' ] = array();
		self::save_summary();

		$msg = __( 'Queue cleared successfully.', 'litespeed-cache' );
		Admin_Display::succeed( $msg );
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
			case self::TYPE_GENERATE_CRITICAL :
				self::cron_ccss( true );
				break;

			case self::TYPE_CLEAR_Q :
				$this->clear_q();
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}
