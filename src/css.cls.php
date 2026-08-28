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
class CSS extends Cloud_Queue_Svc {

	const LOG_TAG = '[CCSS]';

	const TYPE_GEN     = 'gen_ccss';
	const TYPE_CLEAR_Q = 'clear_q_ccss';

	/**
	 * Cached CCSS whitelist.
	 *
	 * @var array|null
	 */
	private $_ccss_whitelist;

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
	 * Svc id slug — drives queue type, Cloud::SVC_CCSS, and summary key prefix.
	 *
	 * @return string
	 */
	protected function _svc_id() {
		return 'ccss';
	}

	/**
	 * Response field carrying the generated CSS.
	 *
	 * @return string
	 */
	protected function _data_key() {
		return 'data_ccss';
	}

	/**
	 * Stale legacy queue rows with empty url_tag would consume a QC request
	 * and persist a bogus URL mapping. Drop them silently. (Pre-refactor CSS
	 * had this guard inline in its cron loop.)
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool
	 */
	protected function _valid_queue_item( $queue_k, $v ) {
		if ( empty( $v['url_tag'] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Build the request body for Cloud::post.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return array
	 */
	protected function _build_payload( $queue_k, $v ) {
		if ( ! isset( $this->_ccss_whitelist ) ) {
			$this->_ccss_whitelist = $this->_filter_whitelist();
		}
		return [
			'url'        => $v['url'],
			'queue_k'    => $queue_k,
			'user_agent' => $v['user_agent'],
			'is_mobile'  => ! empty( $v['is_mobile'] ) ? 1 : 0,
			'is_webp'    => ! empty( $v['is_webp'] ) ? 1 : 0,
			'whitelist'  => $this->_ccss_whitelist,
		];
	}

	/**
	 * Persist the generated CCSS to disk. Empty payload is persisted as the
	 * "no critical CSS for this page" sentinel (matches pre-refactor CCSS
	 * behavior) so subsequent loads don't re-queue the same URL forever.
	 *
	 * @param string $data    CSS content (may be empty / null).
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool
	 */
	protected function _save_result( $data, $queue_k, $v ) {
		$css = null === $data ? '' : (string) $data;
		return $this->_save_css_con( 'ccss', $css, $v['url_tag'], $v['vary'], $queue_k, ! empty( $v['is_mobile'] ), ! empty( $v['is_webp'] ) );
	}

	/**
	 * Legacy alias kept for task.cls.php cron hook registration.
	 *
	 * @param bool $should_continue Continue processing multiple items.
	 * @return mixed
	 */
	public static function cron_ccss( $should_continue = false ) {
		return self::cron( $should_continue );
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
		$request_url = Utility::request_url();

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

		if ( count( $this->_queue ) > $this->_max_queue_size() ) {
			self::debug( 'Queue is full - ' . $this->_max_queue_size() );
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
}
