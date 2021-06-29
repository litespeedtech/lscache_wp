<?php
/**
 * The optimize css class.
 *
 * @since      	2.3
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class CSS extends Base {
	const TYPE_GEN_CCSS = 'gen_ccss';
	const TYPE_GEN_UCSS = 'gen_ucss';
	const TYPE_CLEAR_Q_CCSS = 'clear_q_ccss';
	const TYPE_CLEAR_Q_UCSS = 'clear_q_ucss';

	protected $_summary;
	private $_ucss_whitelist;

	/**
	 * Init
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();

		add_filter( 'litespeed_ucss_whitelist', array( $this->cls( 'Data' ), 'load_ucss_whitelist' ) );
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

		// Append default critical css
		$rules .= $this->conf( self::O_OPTM_CCSS_CON );

		return '<style id="litespeed-optm-css-rules">' . $rules . '</style>';
	}

	/**
	 * Detect if there is ccss/ucss folder or not
	 *
	 * @since  4.0
	 */
	public function has_ccss_folder() {
		$subsite_id = is_multisite() && ! is_network_admin() ? get_current_blog_id() : '';
		if ( file_exists( LITESPEED_STATIC_DIR . '/ccss/' . $subsite_id ) ) {
			return true;
		}
		if ( file_exists( LITESPEED_STATIC_DIR . '/ucss/' . $subsite_id ) ) {
			return true;
		}
		return false;
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
			file_exists( LITESPEED_STATIC_DIR . '/ucss/' . $subsite_id ) && File::rrmdir( LITESPEED_STATIC_DIR . '/ucss/' . $subsite_id );
		}
		else {
			file_exists( LITESPEED_STATIC_DIR . '/ccss' ) && File::rrmdir( LITESPEED_STATIC_DIR . '/ccss' );
			file_exists( LITESPEED_STATIC_DIR . '/ucss' ) && File::rrmdir( LITESPEED_STATIC_DIR . '/ucss' );
		}

		// Clear All summary data
		$this->_summary = array();
		self::save_summary();

		Debug2::debug2( '[CSS] Cleared ccss/ucss queue' );
	}

	/**
	 * Build the static filepath
	 *
	 * @since  4.0
	 */
	private function _build_filepath_prefix( $type ) {
		$filepath_prefix = '/' . $type . '/';
		if ( is_multisite() ) {
			$filepath_prefix .= get_current_blog_id() . '/';
		}

		return $filepath_prefix;
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
		$queue = $this->load_queue( 'ccss' );

		if ( count( $queue ) > 500 ) {
			Debug2::debug( '[CSS] CCSS Queue is full - 500' );
			return null;
		}

		$queue_k = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
		$queue[ $queue_k ] = array(
			'url'			=> $request_url,
			'user_agent'	=> substr( $ua, 0, 200 ),
			'is_mobile'		=> $this->_separate_mobile_ccss(),
			'is_webp'		=> $this->cls( 'Media' )->webp_support() ? 1 : 0,
			'uid'			=> $uid,
			'vary'			=> $vary,
			'url_tag'		=> $url_tag,
		); // Current UA will be used to request
		$this->save_queue( 'ccss', $queue );
		Debug2::debug( '[CSS] Added queue_ccss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary  . ' [uid] ' . $uid );

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
	 * Load current queues from data file
	 *
	 * @since 4.1
	 */
	public function load_queue( $type ) {
		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_path = LITESPEED_STATIC_DIR . $filepath_prefix . '.litespeed_conf.dat';

		$queue = array();
		if ( file_exists( $static_path ) ) {
			$queue = json_decode( file_get_contents( $static_path ), true );
		}

		return $queue;
	}

	/**
	 * Save current queues to data file
	 *
	 * @since 4.1
	 */
	public function save_queue( $type, $list ) {
		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_path = LITESPEED_STATIC_DIR . $filepath_prefix . '.litespeed_conf.dat';

		$data = json_encode( $list );

		File::save( $static_path, $data, true );
	}

	/**
	 * Get UCSS path
	 *
	 * @since  4.0
	 */
	public function load_ucss( $request_url ) {
		$filepath_prefix = $this->_build_filepath_prefix( 'ucss' );
		$url_tag = is_404() ? '404' : $request_url;

		$vary = $this->cls( 'Vary' )->finalize_full_varies();
		$filename = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'ucss' );
		if ( $filename ) {
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';

			if ( file_exists( $static_file ) ) {
				Debug2::debug2( '[UCSS] existing ucss ' . $static_file );
				return $filepath_prefix . $filename . '.css';
			}
		}

		$uid = get_current_user_id();

		$ua = ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '';

		// Store it for cron
		$queue = $this->load_queue( 'ucss' );

		if ( count( $queue ) > 500 ) {
			Debug2::debug( '[CSS] UCSS Queue is full - 500' );
			return false;
		}

		$queue_k = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
		$queue[ $queue_k ] = array(
			'url'			=> $request_url,
			'user_agent'	=> substr( $ua, 0, 200 ),
			'is_mobile'		=> $this->_separate_mobile_ccss(),
			'is_webp'		=> $this->cls( 'Media' )->webp_support() ? 1 : 0,
			'uid'			=> $uid,
			'vary'			=> $vary,
			'url_tag'		=> $url_tag,
		); // Current UA will be used to request
		$this->save_queue( 'ucss', $queue );
		Debug2::debug( '[CSS] Added queue_ucss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary  . ' [uid] ' . $uid );

		// Prepare cache tag for later purge
		Tag::add( 'UCSS.' . md5( $queue_k ) );

		// For v4.1- clean up
		if ( isset( $this->_summary[ 'ucss_history' ] ) || isset( $this->_summary[ 'queue_ucss' ] ) ) {
			if ( isset( $this->_summary[ 'ucss_history' ] ) ) {
				unset( $this->_summary[ 'ucss_history' ] );
			}
			if ( isset( $this->_summary[ 'queue_ucss' ] ) ) {
				unset( $this->_summary[ 'queue_ucss' ] );
			}
			self::save_summary();
		}

		return false;
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

		$queue = $_instance->load_queue( 'ccss' );

		if ( empty( $queue ) ) {
			return;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( ! empty( $_instance->_summary[ 'curr_request_ccss' ] ) && time() - $_instance->_summary[ 'curr_request_ccss' ] < 300 && ! $_instance->conf( self::O_DEBUG ) ) {
				Debug2::debug( '[CCSS] Last request not done' );
				return;
			}
		}

		$i = 0;
		foreach ( $queue as $k => $v ) {
			unset( $queue[ $k ] );
			$_instance->save_queue( 'ccss', $queue );
			Debug2::debug( '[CCSS] cron job [tag] ' . $k . ' [url] ' . $v[ 'url' ] . ( $v[ 'is_mobile' ] ? ' 📱 ' : '' ) . ' [UA] ' . $v[ 'user_agent' ] );

			if ( empty( $v[ 'url_tag' ] ) ) {
				Debug2::debug( '[CCSS] wrong queue_ccss format' );
				continue;
			}

			if ( ! isset( $v[ 'is_webp' ] ) ) {
				$v[ 'is_webp' ] = false;
			}

			$i ++;
			$res = $_instance->_generate( $v[ 'url' ], $k, $v[ 'uid' ], $v[ 'user_agent' ], $v[ 'vary' ], $v[ 'url_tag' ], 'ccss', $v[ 'is_mobile' ], $v[ 'is_webp' ] );

			if ( $res ) {
				Purge::add( 'CCSS.' . md5( $k ) );
			}

			// only request first one
			if ( ! $continue ) {
				return;
			}

			if ( $i > 3 ) {
				$_instance->_print_loading( count( $queue ), 'CCSS' );
				return Router::self_redirect( Router::ACTION_CSS, CSS::TYPE_GEN_CCSS );
			}
		}
	}

	/**
	 * Generate UCSS
	 *
	 * @since  4.0
	 */
	public static function cron_ucss( $continue = false ) {
		$_instance = self::cls();

		$queue = $_instance->load_queue( 'ucss' );

		if ( empty( $queue ) ) {
			return;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( ! empty( $_instance->_summary[ 'curr_request_ucss' ] ) && time() - $_instance->_summary[ 'curr_request_ucss' ] < 300 && ! $_instance->conf( self::O_DEBUG ) ) {
				Debug2::debug( '[UCSS] Last request not done' );
				return;
			}
		}

		$i = 0;
		foreach ( $queue as $k => $v ) {
			unset( $queue[ $k ] );
			$_instance->save_queue( 'ucss', $queue );
			Debug2::debug( '[UCSS] cron job [tag] ' . $k . ' [url] ' . $v[ 'url' ] . ( $v[ 'is_mobile' ] ? ' 📱 ' : '' ) . ' [UA] ' . $v[ 'user_agent' ] );

			if ( ! isset( $v[ 'is_webp' ] ) ) {
				$v[ 'is_webp' ] = false;
			}

			$i ++;
			$res = $_instance->_generate( $v[ 'url' ], $k, $v[ 'uid' ], $v[ 'user_agent' ], $v[ 'vary' ], $v[ 'url_tag' ], 'ucss', $v[ 'is_mobile' ], $v[ 'is_webp' ] );

			if ( $res ) {
				Purge::add( 'UCSS.' . md5( $k ) );
			}

			// only request first one
			if ( ! $continue ) {
				return;
			}

			if ( $i > 3 ) {
				$_instance->_print_loading( count( $queue ), 'UCSS' );
				return Router::self_redirect( Router::ACTION_CSS, CSS::TYPE_GEN_UCSS );
			}
		}
	}

	/**
	 * Clear all waiting queues
	 *
	 * @since  3.4
	 */
	public function clear_q( $type ) {
		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_path = LITESPEED_STATIC_DIR . $filepath_prefix . '.litespeed_conf.dat';

		if ( file_exists( $static_path ) ) {
			unlink( $static_path );
		}

		$msg = __( 'Queue cleared successfully.', 'litespeed-cache' );
		Admin_Display::succeed( $msg );
	}

	/**
	* Print a loading message when redirecting CCSS/UCSS page to aviod whiteboard confusion
	*/
	private function _print_loading( $counter, $type ) {
		echo '<div style="font-size: 25px; text-align: center; padding-top: 150px; width: 100%; position: absolute;">';
		echo "<img width='35' src='" . LSWCP_PLUGIN_URL . "assets/img/Litespeed.icon.svg' />   ";
		echo sprintf( __( '%1$s %2$s files left in queue', 'litespeed-cache' ), $counter, $type );
		echo '</div>';
	}

	/**
	 * Send to QC API to generate CCSS/UCSS
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _generate( $request_url, $queue_k, $uid, $user_agent, $vary, $url_tag, $type, $is_mobile, $is_webp ) {
		set_time_limit( 120 );

		// Check if has credit to push
		$allowance = $this->cls( 'Cloud' )->allowance( Cloud::SVC_CCSS );
		if ( ! $allowance ) {
			Debug2::debug( '[CCSS] ❌ No credit' );
			Admin_Display::error( Error::msg( 'lack_of_quota' ) );
			return false;
		}

		// Update css request status
		$this->_summary[ 'curr_request_' . $type ] = time();
		self::save_summary();

		// Gather guest HTML to send
		$html = $this->_prepare_html( $request_url, $user_agent, $uid );

		if ( ! $html ) {
			return false;
		}

		// Parse HTML to gather all CSS content before requesting
		$css = false;
		if ( $type == 'ccss' ) {
			list( $css, $html ) = $this->_prepare_css( $html, $is_webp );
		}
		else {
			list( , $html ) = $this->_prepare_css( $html, $is_webp, true ); // Use this to drop CSS from HTML as we don't need those CSS to generate UCSS
			$filename = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'css' );
			$filepath_prefix = $this->_build_filepath_prefix( 'css' );
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';
			if ( file_exists( $static_file ) ) {
				$css = File::read( $static_file );
			}
		}

		if ( ! $css ) {
			Debug2::debug( '[UCSS] no combined css' );
			return false;
		}

		// Generate critical css
		$data = array(
			'type'			=> strtoupper( $type ),
			'url'			=> $request_url,
			'ccss_type'		=> $queue_k,
			'user_agent'	=> $user_agent,
			'is_mobile'		=> $is_mobile ? 1 : 0, // todo:compatible w/ tablet
			'is_webp'		=> $is_webp ? 1 : 0,
			'html'			=> $html,
			'css'			=> $css,
		);
		if ( $type == 'ucss' ) {
			if ( ! isset( $this->_ucss_whitelist ) ) {
				$this->_ucss_whitelist = $this->_filter_whitelist();
			}
			$data[ 'whitelist' ] = $this->_ucss_whitelist;
		}

		Debug2::debug( '[CSS] Generating: ', $data );

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 30 );
		if ( ! is_array( $json ) ) {
			return false;
		}

		if ( empty( $json[ $type ] ) ) {
			Debug2::debug( '[CSS] ❌ empty ' . $type );
			return false;
		}

		// Add filters
		$css = apply_filters( 'litespeed_' . $type, $json[ $type ], $queue_k );
		Debug2::debug2( '[CSS] con: ' . $css );

		if ( substr( $css, 0, 2 ) == '/*' && substr( $css, -2 ) == '*/' ) {
			Debug2::debug( '[CSS] ❌ empty ' . $type . ' [content] ' . $css );
			return false;
		}

		// Write to file
		$filecon_md5 = md5( $css );

		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.css';

		File::save( $static_file, $css, true );
		Debug2::debug2( "[CSS] Save URL to file [file] $static_file [vary] $vary" );

		$this->cls( 'Data' )->save_url( $url_tag, $vary, $type, $filecon_md5, dirname( $static_file ) );

		// Save summary data
		$this->_summary[ 'last_spent_' . $type ] = time() - $this->_summary[ 'curr_request_' . $type ];
		$this->_summary[ 'last_request_' . $type ] = $this->_summary[ 'curr_request_' . $type ];
		$this->_summary[ 'curr_request_' . $type ] = 0;
		self::save_summary();

		return true;
	}

	/**
	 * Play for fun
	 *
	 * @since  3.4.3
	 */
	public function test_url( $request_url ) {
		$user_agent = $_SERVER[ 'HTTP_USER_AGENT' ];
		$html = $this->_prepare_html( $request_url, $user_agent );
		list( $css, $html ) = $this->_prepare_css( $html, true, true );
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
	 * Prepare CSS from HTML for CCSS generation only. UCSS will used combined CSS directly.
	 * Prepare refined HTML for both CCSS and UCSS.
	 *
	 * @since  3.4.3
	 */
	private function _prepare_css( $html, $is_webp = false, $dryrun = false ) {
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
			if ( $is_webp ) {
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
	 * Filter the comment content, add quotes to selector from whitelist. Return the json
	 *
	 * @since 3.3
	 */
	private function _filter_whitelist() {
		$whitelist = array();
		$list = apply_filters( 'litespeed_ucss_whitelist', $this->conf( self::O_OPTM_UCSS_WHITELIST ) );
		foreach ( $list as $k => $v ) {
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
	 * Handle all request actions from main cls
	 *
	 * @since  2.3
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_GEN_UCSS:
				self::cron_ucss( true );
				break;

			case self::TYPE_GEN_CCSS:
				self::cron_ccss( true );
				break;

			case self::TYPE_CLEAR_Q_UCSS:
				$this->clear_q( 'ucss' );
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
