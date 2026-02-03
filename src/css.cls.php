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

	const LOG_TAG = '[CCSS]';

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
			self::debug( 'Separate CCSS due to separate URI setting: ' . $hit );
			return $request_url;
		}

		$pt = Utility::page_type();

		$sep_pt = $this->conf( self::O_OPTM_CCSS_SEP_POSTTYPE );
		if ( in_array( $pt, $sep_pt, true ) ) {
			self::debug( 'Separate CCSS due to posttype setting: ' . $pt );
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
				self::debug2( 'existing ccss ' . $static_file );
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
			self::debug( 'Queue is full - 500' );
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

		// Check if we need to wait due to server's try_later request
		if ( ! empty( $this->_summary[ 'ccss_next_run_after' ] ) && time() < $this->_summary['ccss_next_run_after'] ) {
			$wait_seconds = $this->_summary['ccss_next_run_after'] - time();
			self::debug( 'Waiting for try_later timeout: ' . $wait_seconds . ' seconds remaining' );
			return;
		}

		// Clear try_later flag if wait time has passed
		if ( ! empty( $this->_summary['ccss_next_run_after'] ) ) {
			unset( $this->_summary['ccss_next_run_after'] );
			self::save_summary();
			self::debug( 'Cleared try_later flag, resuming CCSS processing' );
		}

		// For cron, need to check request interval too
		if ( ! $should_continue ) {
			if ( ! empty( $this->_summary[ 'curr_request_' . $type ] ) && time() - (int) $this->_summary[ 'curr_request_' . $type ] < 300 && ! $this->conf( self::O_DEBUG ) ) {
				self::debug( 'Last request not done' );
				return;
			}
		}

		foreach ( $this->_queue as $k => $v ) {
			self::debug( 'cron job [tag] ' . $k . ' [url] ' . $v['url'] . ( $v['is_mobile'] ? ' 📱 ' : '' ) . ' [UA] ' . $v['user_agent'] );

			if ( 'ccss' === $type && empty( $v['url_tag'] ) ) {
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );
				self::debug( 'wrong queue_ccss format' );
				continue;
			}

			if ( ! isset( $v['is_webp'] ) ) {
				$v['is_webp'] = false;
			}

			$res = $this->_send_req( $v['url'], $k, $v['uid'], $v['user_agent'], $v['vary'], $v['url_tag'], $type, $v['is_mobile'], $v['is_webp'] );
			if ( ! $res ) {
				// Status is wrong, drop this this->_queue
				unset( $this->_queue[ $k ] );
				$this->save_queue( $type, $this->_queue );

				if ( ! $should_continue ) {
					return;
				}

				continue;
			}

			// Exit queue if out of quota or service is hot
			if ( 'out_of_quota' === $res || 'svc_hot' === $res ) {
				return;
			}

			// Handle try_later response from server
			if ( is_array( $res ) && ! empty( $res['try_later'] ) ) {
				$ttl                                   = (int) $res['try_later'];
				$next_run_time                         = time() + $ttl;
				$this->_summary['ccss_next_run_after'] = $next_run_time;
				self::save_summary();
				self::debug( 'Set next CCSS cron run after ' . $ttl . ' seconds (at ' . gmdate( 'Y-m-d H:i:s', $next_run_time ) . ')' );
			}

			// only request first one
			if ( ! $should_continue ) {
				return;
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
			self::debug( '❌ No credit: ' . $err );
			$err && Admin_Display::error( Error::msg( $err ) );
			return 'out_of_quota';
		}

		set_time_limit( 120 );

		// Update css request status
		$this->_summary[ 'curr_request_' . $type ] = time();
		self::save_summary();

		// Generate critical css
		$data = [
			'url'        => $request_url,
			'queue_k'    => $queue_k,
			'user_agent' => $user_agent,
			'is_mobile'  => $is_mobile ? 1 : 0, // todo:compatible w/ tablet
			'is_webp'    => $is_webp ? 1 : 0,
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

		// Check if server asks to try later
		if ( ! empty( $json['try_later'] ) ) {
			$ttl = (int) $json['try_later'];
			self::debug( 'Server requested try later: ' . $ttl . ' seconds' );
			return [ 'try_later' => $ttl ];
		}

		// Check response status
		if ( empty( $json['status'] ) ) {
			self::debug( '❌ No status in response' );
			return false;
		}

		if ( empty( $json['data_ccss'] ) ) {
			self::debug( '❌ No CCSS data [status] ' . $json['status'] );
		}

		self::debug( '✅ Received CCSS data, saving...' );
		$this->_save_con( $type, $json['data_ccss'], $queue_k, $is_mobile, $is_webp );

		// Remove from queue
		unset( $this->_queue[ $queue_k ] );
		$this->save_queue( $type, $this->_queue );
		self::debug( 'Removed from queue [q_k] ' . $queue_k );

		// Save summary data
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
		self::debug2( 'con: ' . $css );

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
		self::debug2( "Save URL to file [file] $static_file [vary] $vary" );

		$this->cls( 'Data' )->save_url( $url_tag, $vary, $type, $filecon_md5, dirname( $static_file ), $mobile, $webp );

		Purge::add( strtoupper( $type ) . '.' . md5( $queue_k ) );
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
