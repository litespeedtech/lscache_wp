<?php
/**
 * The avatar cache class.
 *
 * Caches remote (e.g., Gravatar) avatars locally and rewrites URLs
 * to serve cached copies with a TTL. Supports on-demand generation
 * during page render and batch generation via cron.
 *
 * @since 3.0
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Avatar
 */
class Avatar extends Base {

	const TYPE_GENERATE = 'generate';

	/**
	 * Avatar cache TTL (seconds).
	 *
	 * @var int
	 */
	private $_conf_cache_ttl;

	/**
	 * Avatar DB table name.
	 *
	 * @var string
	 */
	private $_tb;

	/**
	 * In-request map from original URL => rewritten URL to avoid duplicates.
	 *
	 * @var array<string,string>
	 */
	private $_avatar_realtime_gen_dict = array();

	/**
	 * Summary/status data for last requests.
	 *
	 * @var array<string,mixed>
	 */
	protected $_summary;

	/**
	 * Init.
	 *
	 * @since 1.4
	 */
	public function __construct() {
		if ( ! $this->conf( self::O_DISCUSS_AVATAR_CACHE ) ) {
			return;
		}

		self::debug2( '[Avatar] init' );

		$this->_tb = $this->cls( 'Data' )->tb( 'avatar' );

		$this->_conf_cache_ttl = $this->conf( self::O_DISCUSS_AVATAR_CACHE_TTL );

		add_filter( 'get_avatar_url', array( $this, 'crawl_avatar' ) );

		$this->_summary = self::get_summary();
	}

	/**
	 * Check whether DB table is needed.
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function need_db() {
		return (bool) $this->conf( self::O_DISCUSS_AVATAR_CACHE );
	}

	/**
	 * Serve static avatar by md5 (used by local static route).
	 *
	 * @since 3.0
	 * @access public
	 * @param string $md5 MD5 hash of original avatar URL.
	 * @return void
	 */
	public function serve_static( $md5 ) {
		global $wpdb;

		self::debug( '[Avatar] is avatar request' );

		if ( strlen( $md5 ) !== 32 ) {
			self::debug( '[Avatar] wrong md5 ' . $md5 );
			return;
		}

		$url = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT url FROM `' . $this->_tb . '` WHERE md5 = %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$md5
			)
		);

		if ( ! $url ) {
			self::debug( '[Avatar] no matched url for md5 ' . $md5 );
			return;
		}

		$url = $this->_generate( $url );

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Localize/replace avatar URL with cached one (filter callback).
	 *
	 * @since 3.0
	 * @access public
	 * @param string $url Original avatar URL.
	 * @return string Rewritten/cached avatar URL (or original).
	 */
	public function crawl_avatar( $url ) {
		if ( ! $url ) {
			return $url;
		}

		// Check if already generated in this request.
		if ( ! empty( $this->_avatar_realtime_gen_dict[ $url ] ) ) {
			self::debug2( '[Avatar] already in dict [url] ' . $url );
			return $this->_avatar_realtime_gen_dict[ $url ];
		}

		$realpath = $this->_realpath( $url );
		$mtime    = file_exists( $realpath ) ? filemtime( $realpath ) : false;

		if ( $mtime && time() - $mtime <= $this->_conf_cache_ttl ) {
			self::debug2( '[Avatar] cache file exists [url] ' . $url );
			return $this->_rewrite( $url, $mtime );
		}

		// Only handle gravatar or known remote avatar providers; keep generic check for "gravatar.com".
		if ( strpos( $url, 'gravatar.com' ) === false ) {
			return $url;
		}

		// Throttle generation.
		if ( ! empty( $this->_summary['curr_request'] ) && time() - $this->_summary['curr_request'] < 300 ) {
			self::debug2( '[Avatar] Bypass generating due to interval limit [url] ' . $url );
			return $url;
		}

		// Generate immediately and track for this request.
		$this->_avatar_realtime_gen_dict[ $url ] = $this->_generate( $url );

		return $this->_avatar_realtime_gen_dict[ $url ];
	}

	/**
	 * Count queued avatars (expired ones) for cron.
	 *
	 * @since 3.0
	 * @access public
	 * @return int|false
	 */
	public function queue_count() {
		global $wpdb;

		// If var not exists, means table not exists // todo: not true.
		if ( ! $this->_tb ) {
			return false;
		}

		$cnt = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . $this->_tb . '` WHERE dateline < %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				time() - $this->_conf_cache_ttl
			)
		);

		return (int) $cnt;
	}

	/**
	 * Build final local URL for cached avatar.
	 *
	 * @since 3.0
	 * @param string   $url  Original URL.
	 * @param int|null $time Optional filemtime for cache busting.
	 * @return string Local URL.
	 */
	private function _rewrite( $url, $time = null ) {
		$qs = $time ? '?ver=' . $time : '';
		return LITESPEED_STATIC_URL . '/avatar/' . $this->_filepath( $url ) . $qs;
	}

	/**
	 * Generate filesystem realpath for cache file.
	 *
	 * @since 3.0
	 * @access private
	 * @param string $url Original URL.
	 * @return string Absolute filesystem path.
	 */
	private function _realpath( $url ) {
		return LITESPEED_STATIC_DIR . '/avatar/' . $this->_filepath( $url );
	}

	/**
	 * Get relative filepath for cached avatar.
	 *
	 * @since 4.0
	 * @param string $url Original URL.
	 * @return string Relative path under avatar/ (may include blog id).
	 */
	private function _filepath( $url ) {
		$filename = md5( $url ) . '.jpg';
		if ( is_multisite() ) {
			$filename = get_current_blog_id() . '/' . $filename;
		}
		return $filename;
	}

	/**
	 * Cron generation for expired avatars.
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $force Bypass throttle.
	 * @return void
	 */
	public static function cron( $force = false ) {
		global $wpdb;

		$_instance = self::cls();
		if ( ! $_instance->queue_count() ) {
			self::debug( '[Avatar] no queue' );
			return;
		}

		// For cron, need to check request interval too.
		if ( ! $force ) {
			if ( ! empty( $_instance->_summary['curr_request'] ) && time() - $_instance->_summary['curr_request'] < 300 ) {
				self::debug( '[Avatar] curr_request too close' );
				return;
			}
		}

		$list = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT url FROM `' . $_instance->_tb . '` WHERE dateline < %d ORDER BY id DESC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				time() - $_instance->_conf_cache_ttl,
				(int) apply_filters( 'litespeed_avatar_limit', 30 )
			)
		);
		self::debug( '[Avatar] cron job [count] ' . ( $list ? count( $list ) : 0 ) );

		if ( $list ) {
			foreach ( $list as $v ) {
				self::debug( '[Avatar] cron job [url] ' . $v->url );
				$_instance->_generate( $v->url );
			}
		}
	}

	/**
	 * Download and store the avatar locally, then update DB row.
	 *
	 * @since 3.0
	 * @access private
	 * @param string $url Original avatar URL.
	 * @return string Rewritten local URL (fallback to original on failure).
	 */
	private function _generate( $url ) {
		global $wpdb;

		$file = $this->_realpath( $url );

		// Mark request start
		self::save_summary(
			array(
				'curr_request' => time(),
			)
		);

		// Ensure cache directory exists
		$this->_maybe_mk_cache_folder( 'avatar' );

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'  => 180,
				'stream'   => true,
				'filename' => $file,
			)
		);

		self::debug( '[Avatar] _generate [url] ' . $url );

		// Parse response data
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			self::debug( '[Avatar] failed to get: ' . $error_message );
			return $url;
		}

		// Save summary data
		self::save_summary(
			array(
				'last_spent'   => time() - $this->_summary['curr_request'],
				'last_request' => $this->_summary['curr_request'],
				'curr_request' => 0,
			)
		);

		// Update/insert DB record
		$md5 = md5( $url );

		$existed = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'UPDATE `' . $this->_tb . '` SET dateline = %d WHERE md5 = %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				time(),
				$md5
			)
		);

		if ( ! $existed ) {
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'INSERT INTO `' . $this->_tb . '` (url, md5, dateline) VALUES (%s, %s, %d)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$url,
					$md5,
					time()
				)
			);
		}

		self::debug( '[Avatar] saved avatar ' . $file );

		return $this->_rewrite( $url );
	}

	/**
	 * Handle all request actions from main cls.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_GENERATE:
				self::cron( true );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
