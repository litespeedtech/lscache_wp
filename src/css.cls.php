<?php
/**
 * Optimize CSS handler.
 *
 * @package LiteSpeed
 * @since   2.3
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Optimize CSS handler class.
 */
class CSS extends Base {

	const LOG_TAG = '[CSS]';

	const TYPE_GEN_CCSS     = 'gen_ccss';
	const TYPE_CLEAR_Q_CCSS = 'clear_q_ccss';

	/**
	 * Summary cache.
	 *
	 * @var array
	 */
	protected $_summary;

	/**
	 * Cached CCSS whitelist.
	 *
	 * @var array|null
	 */
	private $_ccss_whitelist;

	/**
	 * Request queue.
	 *
	 * @var array
	 */
	private $_queue;

	/**
	 * Init.
	 *
	 * @since  3.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();

		add_filter( 'litespeed_ccss_whitelist', [ $this->cls( 'Data' ), 'load_ccss_whitelist' ] );
	}

	/**
	 * HTML lazyload CSS.
	 *
	 * @since 4.0
	 * @return string
	 */
	public function prepare_html_lazy() {
		return '<style>' . implode( ',', $this->conf( self::O_OPTM_HTML_LAZY ) ) . '{content-visibility:auto;contain-intrinsic-size:1px 1000px;}</style>';
	}

	/**
	 * Output critical CSS.
	 *
	 * @since  1.3
	 * @access public
	 * @return string|null
	 */
	public function prepare_ccss() {
		// Get critical css for current page
		// Note: need to consider mobile
		$rules = $this->_ccss();
		if ( ! $rules ) {
			return null;
		}

		$error_tag = '';
		if ( substr( $rules, 0, 2 ) === '/*' && substr( $rules, -2 ) === '*/' ) {
			Core::comment( 'QUIC.cloud CCSS bypassed due to generation error ❌' );
			$error_tag = ' data-error="failed to generate"';
		}

		// Append default critical css
		$rules .= $this->conf( self::O_OPTM_CCSS_CON );

		return '<style id="litespeed-ccss"' . $error_tag . '>' . $rules . '</style>';
	}

	/**
	 * Generate CCSS url tag.
	 *
	 * @since 4.0
	 * @param string $request_url Current request URL.
	 * @return string
	 */
	private function _gen_ccss_file_tag( $request_url ) {
		if ( is_404() ) {
			return '404';
		}

		if ( $this->conf( self::O_OPTM_CCSS_PER_URL ) ) {
			return $request_url;
		}

		$sep_uri = $this->conf( self::O_OPTM_CCSS_SEP_URI );
		$hit     = false;
		if ( $sep_uri ) {
			$hit = Utility::str_hit_array( $request_url, $sep_uri );
		}
		if ( $sep_uri && $hit ) {
			Debug2::debug( '[CCSS] Separate CCSS due to separate URI setting: ' . $hit );
			return $request_url;
		}

		$pt = Utility::page_type();

		$sep_pt = $this->conf( self::O_OPTM_CCSS_SEP_POSTTYPE );
		if ( in_array( $pt, $sep_pt, true ) ) {
			Debug2::debug( '[CCSS] Separate CCSS due to posttype setting: ' . $pt );
			return $request_url;
		}

		// Per posttype
		return $pt;
	}

	/**
	 * The critical css content of the current page.
	 *
	 * @since  2.3
	 * @return string|null
	 */
	private function _ccss() {
		global $wp;

		// get current request url
		$permalink_structure = get_option( 'permalink_structure' );
		if ( ! empty( $permalink_structure ) ) {
			$request_url = trailingslashit( home_url( $wp->request ) );
		} else {
			$qs_add      = $wp->query_string ? '?' . (string) $wp->query_string : '' ;
			$request_url = home_url( $wp->request ) . $qs_add;
		}

		$filepath_prefix = $this->_build_filepath_prefix( 'ccss' );
		$url_tag         = $this->_gen_ccss_file_tag( $request_url );
		$vary            = $this->cls( 'Vary' )->finalize_full_varies();
		$filename        = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'ccss' );
		if ( $filename ) {
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.css';

			if ( file_exists( $static_file ) ) {
				Debug2::debug2( '[CSS] existing ccss ' . $static_file );
				Core::comment( 'QUIC.cloud CCSS loaded ✅ ' . $filepath_prefix . $filename . '.css' );
				return File::read( $static_file );
			}
		}

		$uid = get_current_user_id();

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		// Store it to prepare for cron
		Core::comment( 'QUIC.cloud CCSS in queue' );
		$this->_queue = $this->load_queue( 'ccss' );

		if ( count( $this->_queue ) > 500 ) {
			self::debug( 'CCSS Queue is full - 500' );
			return null;
		}

		$queue_k                  = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
		$this->_queue[ $queue_k ] = [
			'url'        => apply_filters( 'litespeed_ccss_url', $request_url ),
			'user_agent' => substr( $ua, 0, 200 ),
			'is_mobile'  => $this->_separate_mobile(),
			'is_webp'    => $this->cls( 'Media' )->webp_support() ? 1 : 0,
			'uid'        => $uid,
			'vary'       => $vary,
			'url_tag'    => $url_tag,
		]; // Current UA will be used to request
		$this->save_queue( 'ccss', $this->_queue );
		self::debug( 'Added queue_ccss [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary . ' [uid] ' . $uid );

		// Prepare cache tag for later purge
		Tag::add( 'CCSS.' . md5( $queue_k ) );

		return null;
	}

	/**
	 * Cron ccss generation.
	 *
	 * @since  2.3
	 * @access private
	 *
	 * @param bool $should_continue Continue processing multiple items.
	 * @return mixed
	 */
	public static function cron_ccss( $should_continue = false ) {
		$_instance = self::cls();
		return $_instance->_cron_handler( 'ccss', $should_continue );
	}

	/**
	 * Handle UCSS/CCSS cron.
	 *
	 * @since 4.2
	 *
	 * @param string $type            Job type: 'ccss' or 'ucss'.
	 * @param bool   $should_continue Continue processing multiple items.
	 * @return void
	 */
	private function _cron_handler( $type, $should_continue ) {
		$this->_queue = $this->load_queue( $type );

		if ( empty( $this->_queue ) ) {
			return;
		}

		$type_tag = strtoupper( $type );

		// For cron, need to check request interval too
		if ( ! $should_continue ) {
			if ( ! empty( $this->_summary[ 'curr_request_' . $type ] ) && time() - (int) $this->_summary[ 'curr_request_' . $type ] < 300 && ! $this->conf( self::O_DEBUG ) ) {
				Debug2::debug( '[' . $type_tag . '] Last request not done' );
				return;
			}
		}

		$i = 0;
		foreach ( $this->_queue as $k => $v ) {
			if ( ! empty( $v['_status'] ) ) {
				continue;
			}

			Debug2::debug( '[' . $type_tag . '] cron job [tag] ' . $k . ' [url] ' . $v['url'] . ( $v['is_mobile'] ? ' 📱 ' : '' ) . ' [UA] ' . $v['user_agent'] );

			if ( 'ccss' === $type && empty( $v['url_tag'] ) ) {
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );
				Debug2::debug( '[CCSS] wrong queue_ccss format' );
				continue;
			}

			if ( ! isset( $v['is_webp'] ) ) {
				$v['is_webp'] = false;
			}

			++$i;
			$res = $this->_send_req( $v['url'], $k, $v['uid'], $v['user_agent'], $v['vary'], $v['url_tag'], $type, $v['is_mobile'], $v['is_webp'] );
			if ( ! $res ) {
				// Status is wrong, drop this this->_queue
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );

				if ( ! $should_continue ) {
					return;
				}

				if ( $i > 3 ) {
					GUI::print_loading( count( $this->_queue ), $type_tag );
					return Router::self_redirect( Router::ACTION_CSS, self::TYPE_GEN_CCSS );
				}

				continue;
			}

			// Exit queue if out of quota or service is hot
			if ( 'out_of_quota' === $res || 'svc_hot' === $res ) {
				return;
			}

			$this->_queue[ $k ]['_status'] = 'requested';
			$this->save_queue( $type, $this->_queue );

			// only request first one
			if ( ! $should_continue ) {
				return;
			}

			if ( $i > 3 ) {
				GUI::print_loading( count( $this->_queue ), $type_tag );
				return Router::self_redirect( Router::ACTION_CSS, self::TYPE_GEN_CCSS );
			}
		}
	}

	/**
	 * Send to QC API to generate CCSS/UCSS.
	 *
	 * @since  2.3
	 * @access private
	 *
	 * @param string $request_url Request URL.
	 * @param string $queue_k     Queue key.
	 * @param int    $uid         WP User ID.
	 * @param string $user_agent  User agent string.
	 * @param string $vary        Vary string.
	 * @param string $url_tag     URL tag.
	 * @param string $type        Type: 'ccss' or 'ucss'.
	 * @param bool   $is_mobile   Is mobile.
	 * @param bool   $is_webp     Has webp support.
	 * @return bool|string True on success, 'out_of_quota' / 'svc_hot' on special cases, false on failure.
	 */
	private function _send_req( $request_url, $queue_k, $uid, $user_agent, $vary, $url_tag, $type, $is_mobile, $is_webp ) {
		// Check if has credit to push or not
		$err       = false;
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
		list( $css, $html ) = $this->prepare_css( $html, $is_webp );

		if ( ! $css ) {
			$type_tag = strtoupper( $type );
			Debug2::debug( '[' . $type_tag . '] ❌ No combined css' );
			return false;
		}

		// Generate critical css
		$data = [
			'url'        => $request_url,
			'queue_k'    => $queue_k,
			'user_agent' => $user_agent,
			'is_mobile'  => $is_mobile ? 1 : 0, // todo:compatible w/ tablet
			'is_webp'    => $is_webp ? 1 : 0,
			'html'       => $html,
			'css'        => $css,
		];
		if ( ! isset( $this->_ccss_whitelist ) ) {
			$this->_ccss_whitelist = $this->_filter_whitelist();
		}
		$data['whitelist'] = $this->_ccss_whitelist;

		self::debug( 'Generating: ', $data );

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 30 );
		if ( ! is_array( $json ) ) {
			return $json;
		}

		// Old version compatibility
		if ( empty( $json['status'] ) ) {
			if ( ! empty( $json[ $type ] ) ) {
				$this->_save_con( $type, $json[ $type ], $queue_k, $is_mobile, $is_webp );
			}

			// Delete the row
			return false;
		}

		// Unknown status, remove this line
		if ( 'queued' !== $json['status'] ) {
			return false;
		}

		// Save summary data
		$this->_summary[ 'last_spent_' . $type ]   = time() - (int) $this->_summary[ 'curr_request_' . $type ];
		$this->_summary[ 'last_request_' . $type ] = $this->_summary[ 'curr_request_' . $type ];
		$this->_summary[ 'curr_request_' . $type ] = 0;
		self::save_summary();

		return true;
	}

	/**
	 * Save CCSS/UCSS content.
	 *
	 * @since 4.2
	 *
	 * @param string $type    Type: 'ccss' or 'ucss'.
	 * @param string $css     CSS content.
	 * @param string $queue_k Queue key.
	 * @param bool   $mobile  Is mobile.
	 * @param bool   $webp    Has webp support.
	 * @return void
	 */
	private function _save_con( $type, $css, $queue_k, $mobile, $webp ) {
		// Add filters
		$css = apply_filters( 'litespeed_' . $type, $css, $queue_k );
		Debug2::debug2( '[CSS] con: ' . $css );

		if ( substr( $css, 0, 2 ) === '/*' && substr( $css, -2 ) === '*/' ) {
			self::debug( '❌ empty ' . $type . ' [content] ' . $css );
			// continue; // Save the error info too
		}

		// Write to file
		$filecon_md5 = md5( $css );

		$filepath_prefix = $this->_build_filepath_prefix( $type );
		$static_file     = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.css';

		File::save( $static_file, $css, true );

		$url_tag = $this->_queue[ $queue_k ]['url_tag'];
		$vary    = $this->_queue[ $queue_k ]['vary'];
		Debug2::debug2( "[CSS] Save URL to file [file] $static_file [vary] $vary" );

		$this->cls( 'Data' )->save_url( $url_tag, $vary, $type, $filecon_md5, dirname( $static_file ), $mobile, $webp );

		Purge::add( strtoupper( $type ) . '.' . md5( $queue_k ) );
	}

	/**
	 * Play for fun.
	 *
	 * @since  3.4.3
	 *
	 * @param string $request_url URL to test.
	 * @return void
	 */
	public function test_url( $request_url ) {
		$user_agent         = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$html               = $this->prepare_html( $request_url, $user_agent );
		list( $css, $html ) = $this->prepare_css( $html, true, true );

		$data = [
			'url'        => $request_url,
			'ccss_type'  => 'test',
			'user_agent' => $user_agent,
			'is_mobile'  => 0,
			'html'       => $html,
			'css'        => $css,
			'type'       => 'CCSS',
		];

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 180 );

		Debug2::debug2( '[CSS][test_url] response', $json );
	}

	/**
	 * Prepare HTML from URL.
	 *
	 * @since  3.4.3
	 *
	 * @param string   $request_url URL to fetch.
	 * @param string   $user_agent  User agent to use.
	 * @param int|bool $uid         Optional user ID for simulation.
	 * @return string|false
	 */
	public function prepare_html( $request_url, $user_agent, $uid = false ) {
		$html = $this->cls( 'Crawler' )->self_curl( add_query_arg( 'LSCWP_CTRL', 'before_optm', $request_url ), $user_agent, $uid );
		Debug2::debug2( '[CSS] self_curl result....', $html );

		if ( ! $html ) {
			return false;
		}

		$html = $this->cls( 'Optimizer' )->html_min( $html, true );
		// Drop <noscript>xxx</noscript>
		$html = preg_replace( '#<noscript>.*</noscript>#isU', '', $html );

		return $html;
	}

	/**
	 * Prepare CSS from HTML for CCSS generation only. UCSS will use combined CSS directly.
	 * Prepare refined HTML for both CCSS and UCSS.
	 *
	 * @since  3.4.3
	 *
	 * @param string $html    HTML content.
	 * @param bool   $is_webp Convert backgrounds to WebP when supported.
	 * @param bool   $dryrun  If true, do not fetch external CSS files.
	 * @return array{0:string,1:string} [combined CSS, refined HTML]
	 */
	public function prepare_css( $html, $is_webp = false, $dryrun = false ) {
		$css = '';
		preg_match_all( '#<link ([^>]+)/?>|<style([^>]*)>([^<]+)</style>#isU', $html, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$debug_info = '';
			if ( strpos( $match[0], '<link' ) === 0 ) {
				$attrs = Utility::parse_attr( $match[1] );

				if ( empty( $attrs['rel'] ) ) {
					continue;
				}

				if ( 'stylesheet' !== $attrs['rel'] ) {
					if ( 'preload' !== $attrs['rel'] || empty( $attrs['as'] ) || 'style' !== $attrs['as'] ) {
						continue;
					}
				}

				if ( ! empty( $attrs['media'] ) && false !== strpos( $attrs['media'], 'print' ) ) {
					continue;
				}

				if ( empty( $attrs['href'] ) ) {
					continue;
				}

				// Check Google fonts hit
				if ( false !== strpos( $attrs['href'], 'fonts.googleapis.com' ) ) {
					$html = str_replace( $match[0], '', $html );
					continue;
				}

				$debug_info = $attrs['href'];

				// Load CSS content
				if ( ! $dryrun ) {
					// Dryrun will not load CSS but just drop them
					$con = $this->cls( 'Optimizer' )->load_file( $attrs['href'] );
					if ( ! $con ) {
						continue;
					}
				} else {
					$con = '';
				}
			} else {
				// Inline style
				$attrs = Utility::parse_attr( $match[2] );

				if ( ! empty( $attrs['media'] ) && false !== strpos( $attrs['media'], 'print' ) ) {
					continue;
				}

				Debug2::debug2( '[CSS] Load inline CSS ' . substr( $match[3], 0, 100 ) . '...', $attrs );
				$con = $match[3];

				$debug_info = '__INLINE__';
			}

			$con = Optimizer::minify_css( $con );
			if ( $is_webp && $this->cls( 'Media' )->webp_support() ) {
				$con = $this->cls( 'Media' )->replace_background_webp( $con );
			}

			if ( ! empty( $attrs['media'] ) && 'all' !== $attrs['media'] ) {
				$con = '@media ' . $attrs['media'] . '{' . $con . "}\n";
			} else {
				$con = $con . "\n";
			}

			$con  = '/* ' . $debug_info . ' */' . $con;
			$css .= $con;

			$html = str_replace( $match[0], '', $html );
		}

		return [ $css, $html ];
	}

	/**
	 * Filter the comment content, add quotes to selector from whitelist. Return the json.
	 *
	 * @since 7.1
	 * @return array
	 */
	private function _filter_whitelist() {
		$whitelist = [];
		$list      = apply_filters( 'litespeed_ccss_whitelist', $this->conf( self::O_OPTM_CCSS_SELECTOR_WHITELIST ) );
		foreach ( $list as $v ) {
			if ( substr( $v, 0, 2 ) === '//' ) {
				continue;
			}
			$whitelist[] = $v;
		}

		return $whitelist;
	}

	/**
	 * Notify finished from server.
	 *
	 * @since 7.1
	 * @return array
	 */
	public function notify() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_data = \json_decode( file_get_contents( 'php://input' ), true );
		if ( is_null( $post_data ) ) {
			// Fallback for form-encoded payloads
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$post_data = $_POST;
		}
		self::debug( 'notify() data', $post_data );

		$this->_queue = $this->load_queue( 'ccss' );

		list( $post_data ) = $this->cls( 'Cloud' )->extract_msg( $post_data, 'ccss' );

		$notified_data = $post_data['data'];
		if ( empty( $notified_data ) || ! is_array( $notified_data ) ) {
			self::debug( '❌ notify exit: no notified data' );
			return Cloud::err( 'no notified data' );
		}

		// Check if its in queue or not
		$valid_i = 0;
		foreach ( $notified_data as $v ) {
			if ( empty( $v['request_url'] ) ) {
				self::debug( '❌ notify bypass: no request_url', $v );
				continue;
			}
			if ( empty( $v['queue_k'] ) ) {
				self::debug( '❌ notify bypass: no queue_k', $v );
				continue;
			}

			if ( empty( $this->_queue[ $v['queue_k'] ] ) ) {
				self::debug( '❌ notify bypass: no this queue [q_k]' . $v['queue_k'] );
				continue;
			}

			// Save data
			if ( ! empty( $v['data_ccss'] ) ) {
				$is_mobile = $this->_queue[ $v['queue_k'] ]['is_mobile'];
				$is_webp   = $this->_queue[ $v['queue_k'] ]['is_webp'];
				$this->_save_con( 'ccss', $v['data_ccss'], $v['queue_k'], $is_mobile, $is_webp );

				++$valid_i;
			}

			unset( $this->_queue[ $v['queue_k'] ] );
			self::debug( 'notify data handled, unset queue [q_k] ' . $v['queue_k'] );
		}
		$this->save_queue( 'ccss', $this->_queue );

		self::debug( 'notified' );

		return Cloud::ok( [ 'count' => $valid_i ] );
	}

	/**
	 * Handle all request actions from main cls.
	 *
	 * @since  2.3
	 * @access public
	 * @return void
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
