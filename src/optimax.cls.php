<?php
/**
 * The Optimax class for full page optimization.
 *
 * Sends entire page (HTML/JS/CSS/Images) to cloud for optimization.
 *
 * @since   8.0
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Optimax - Full Page Optimization class.
 *
 * @since 8.0
 */
class Optimax extends Base {

	const LOG_TAG = '🚀';

	const TYPE_GEN     = 'gen';
	const TYPE_CLEAR_Q = 'clear_q';

	/**
	 * Summary data cache.
	 *
	 * @var array
	 */
	protected $_summary;

	/**
	 * Request queue.
	 *
	 * @var array
	 */
	private $_queue;

	/**
	 * Init.
	 *
	 * @since 8.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Generate URL tag for Optimax.
	 *
	 * @since 8.0
	 *
	 * @param string $request_url Current request URL.
	 * @return string The URL tag.
	 */
	public static function get_url_tag( $request_url ) {
		if ( is_404() ) {
			return '404';
		}

		return $request_url;
	}

	/**
	 * Get User Agent.
	 *
	 * @since 8.0
	 *
	 * @return string The user agent string.
	 */
	private function _get_ua() {
		return ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}

	/**
	 * Cron handler for generation.
	 *
	 * @since 8.0
	 *
	 * @param bool $keep_going Whether to continue processing.
	 * @return mixed The cron handler result.
	 */
	public static function cron( $keep_going = false ) {
		$_instance = self::cls();
		return $_instance->_cron_handler( $keep_going );
	}

	/**
	 * Handle Optimax generation cron.
	 *
	 * @since 8.0
	 *
	 * @param bool $keep_going Whether to continue processing.
	 * @return mixed The redirect result or void.
	 */
	private function _cron_handler( $keep_going ) {
		$this->_queue = $this->load_queue( 'optimax' );

		if ( empty( $this->_queue ) ) {
			return;
		}

		// Check if we need to wait due to server's try_later request
		if ( ! empty( $this->_summary[ 'ox_next_run_after' ] ) && time() < $this->_summary['ox_next_run_after'] ) {
			$wait_seconds = $this->_summary['ox_next_run_after'] - time();
			self::debug( 'Waiting for try_later timeout: ' . $wait_seconds . ' seconds remaining' );
			return;
		}

		// Clear try_later flag if wait time has passed
		if ( ! empty( $this->_summary['ox_next_run_after'] ) ) {
			unset( $this->_summary['ox_next_run_after'] );
			self::save_summary();
			self::debug( 'Cleared try_later flag, resuming ox processing' );
		}

		// Check request interval
		if ( ! $keep_going ) {
			if ( ! empty( $this->_summary['curr_request'] ) && time() - $this->_summary['curr_request'] < 300 && ! $this->conf( self::O_DEBUG ) ) {
				self::debug( 'Last request not done' );
				return;
			}
		}

		foreach ( $this->_queue as $k => $v ) {
			self::debug( 'cron job [tag] ' . $k . ' [url] ' . $v['url'] . ( $v['is_mobile'] ? ' 📱 ' : '' ) . ' [UA] ' . $v['user_agent'] );

			$res = $this->_send_req( $v['url'], $k, $v['uid'], $v['user_agent'], $v['vary'], $v['url_tag'], $v['is_mobile'], $v['is_nextgen'] );
			if ( ! $res ) {
				// Status error, remove from queue
				$this->_queue = $this->load_queue( 'optimax' );
				unset( $this->_queue[ $k ] );
				$this->save_queue( 'optimax', $this->_queue );

				if ( ! $keep_going ) {
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
				$ttl                                 = (int) $res['try_later'];
				$next_run_time                       = time() + $ttl;
				$this->_summary['ox_next_run_after'] = $next_run_time;
				self::save_summary();
				self::debug( 'Set next ox cron run after ' . $ttl . ' seconds (at ' . gmdate( 'Y-m-d H:i:s', $next_run_time ) . ')' );
			}

			// Handle completed response (sync mode)
			if ( 'completed' === $res ) {
				self::debug( 'Optimax completed for [k] ' . $k );
			}

			// Only process first one
			if ( ! $keep_going ) {
				return;
			}
		}
	}

	/**
	 * Send request to QC API for optimization.
	 *
	 * @since 8.0
	 *
	 * @param string    $request_url The request URL.
	 * @param string    $queue_k     The queue key.
	 * @param int|false $uid         The user ID.
	 * @param string    $user_agent  The user agent.
	 * @param string    $vary        The vary string.
	 * @param string    $url_tag     The URL tag.
	 * @param bool      $is_mobile   Whether is mobile.
	 * @param string    $is_nextgen  Next-gen image format ('webp', 'avif', or '').
	 * @return string|bool|null The result status.
	 */
	private function _send_req( $request_url, $queue_k, $uid, $user_agent, $vary, $url_tag, $is_mobile, $is_nextgen ) {
		// Check if has credit
		$err       = false;
		$allowance = $this->cls( 'Cloud' )->allowance( Cloud::SVC_OPTIMAX, $err );
		if ( ! $allowance ) {
			self::debug( '❌ No credit: ' . $err );
			$err && Admin_Display::error( Error::msg( $err ) );
			return 'out_of_quota';
		}

		set_time_limit( 1200 );

		// Update request status
		$this->_summary['curr_request'] = time();
		self::save_summary();

		$data = [
			'url'        => $request_url,
			'queue_k'    => $queue_k,
			'user_agent' => $user_agent,
			'is_mobile'  => $is_mobile ? 1 : 0,
			'is_nextgen' => $is_nextgen ? $is_nextgen : '',
		];

		self::debug( 'Generating: ', $data );

		$json = Cloud::post( Cloud::SVC_OPTIMAX, $data, 30 );
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

		// Handle sync response with file data
		if ( empty( $json['data_optimax'] ) ) {
			self::debug( '❌ Unknown status: ' . $json['status'] );
			return false;
		}

		self::debug( '✅ Received Optimax data, processing...' );

		$ox = $json['data_optimax'];

		// 1. Save HTML
		if ( empty( $ox['html'] ) ) {
			self::debug( '❌ No HTML in data_optimax [k] ' . $queue_k );
			return false;
		}
		$this->_save_con( $ox['html'], $queue_k, $is_mobile, $is_nextgen );

		// 2. Save UCSS
		if ( ! empty( $ox['ucss'] ) ) {
			$this->_save_ucss( $ox['ucss'], $queue_k, $is_mobile, $is_nextgen );
		}

		// 3. Save CCSS
		if ( ! empty( $ox['ccss'] ) ) {
			$this->_save_ccss( $ox['ccss'], $queue_k, $is_mobile, $is_nextgen );
		}

		// 4. Save optimized images
		if ( ! empty( $ox['imgs'] ) ) {
			$this->_save_imgs( $ox['imgs'] );
		}

		// Remove from queue
		unset( $this->_queue[ $queue_k ] );
		$this->save_queue( 'optimax', $this->_queue );
		self::debug( 'Removed from queue [q_k] ' . $queue_k );

		// Save summary data
		$this->_summary['last_request'] = $this->_summary['curr_request'];
		$this->_summary['curr_request'] = 0;
		self::save_summary();

		return 'completed';
	}

	/**
	 * Serve optimized page from cache if available.
	 *
	 * Called during buffer finalization as the first priority check.
	 * If ox HTML is found, returns it to skip all other optimization hooks.
	 *
	 * @since 8.0
	 *
	 * @return string|false The optimized HTML content, or false if not available.
	 */
	public function serve() {
		// Check if ox is enabled
		if ( ! $this->conf( self::O_OPTIMAX ) ) {
			return false;
		}

		$request_url = $this->_build_request_url();

		// Check URI exclusions
		$exc = apply_filters( 'litespeed_optimax_exc', $this->conf( self::O_OPTIMAX_EXC ) );
		$hit = $exc ? Utility::str_hit_array( $request_url, $exc ) : false;
		if ( $hit ) {
			self::debug( 'serve() bypassed due to URI Exclude: ' . $hit );
			return false;
		}

		$filepath_prefix = $this->_build_filepath_prefix( 'optimax' );
		$url_tag         = self::get_url_tag( $request_url );
		$vary            = $this->cls( 'Vary' )->finalize_full_varies();
		$filename        = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'optimax' );

		if ( $filename ) {
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.html';

			if ( file_exists( $static_file ) ) {
				$html = File::read( $static_file );
				if ( $html ) {
					self::debug( 'serve() hit: ' . $filepath_prefix . $filename . '.html' );
					Core::comment( 'Optimax served' );
					return $html;
				}
				self::debug( 'serve() empty file: ' . $static_file );
			} else {
				self::debug( 'serve() file missing: ' . $static_file );
			}
		}

		// No cached optimax, add to queue
		$uid = get_current_user_id();
		$ua  = $this->_get_ua();

		$this->_queue = $this->load_queue( 'optimax' );

		if ( count( $this->_queue ) > 500 ) {
			self::debug( 'Queue is full - 500' );
			return false;
		}

		$queue_k                  = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
		$this->_queue[ $queue_k ] = [
			'url'        => apply_filters( 'litespeed_optimax_url', $request_url ),
			'user_agent' => substr( $ua, 0, 200 ),
			'is_mobile'  => $this->_separate_mobile(),
			'is_nextgen' => $this->cls( 'Media' )->webp_support(),
			'uid'        => $uid,
			'vary'       => $vary,
			'url_tag'    => $url_tag,
		];
		$this->save_queue( 'optimax', $this->_queue );
		self::debug( 'Added to queue [url_tag] ' . $url_tag . ' [UA] ' . $ua . ' [vary] ' . $vary . ' [uid] ' . $uid );

		// Prepare cache tag for later purge
		Tag::add( 'OPTIMAX.' . md5( $queue_k ) );
		Core::comment( 'QUIC.cloud Optimax in queue' );

		return false;
	}

	/**
	 * Build the current request URL from WP globals.
	 *
	 * @since 8.0
	 *
	 * @return string The current request URL.
	 */
	private function _build_request_url() {
		global $wp;

		$permalink_structure = get_option( 'permalink_structure' );
		if ( ! empty( $permalink_structure ) ) {
			return trailingslashit( home_url( $wp->request ) );
		}

		$qs_add = $wp->query_string ? '?' . (string) $wp->query_string : '';
		return home_url( $wp->request ) . $qs_add;
	}

	/**
	 * Save UCSS content to ucss/ directory.
	 *
	 * @since 8.0
	 *
	 * @param string $ucss      The UCSS content.
	 * @param string $queue_k   The queue key.
	 * @param bool   $is_mobile Whether is mobile.
	 * @param string $is_nextgen Next-gen image format ('webp', 'avif', or '').
	 * @return void
	 */
	private function _save_ucss( $ucss, $queue_k, $is_mobile, $is_nextgen ) {
		$url_tag = $this->_queue[ $queue_k ]['url_tag'];
		$vary    = $this->_queue[ $queue_k ]['vary'];
		$this->_save_css_con( 'ucss', $ucss, $url_tag, $vary, $queue_k, $is_mobile, $is_nextgen );
	}

	/**
	 * Save CCSS content to ccss/ directory.
	 *
	 * @since 8.0
	 *
	 * @param string $ccss      The CCSS content.
	 * @param string $queue_k   The queue key.
	 * @param bool   $is_mobile Whether is mobile.
	 * @param string $is_nextgen Next-gen image format ('webp', 'avif', or '').
	 * @return void
	 */
	private function _save_ccss( $ccss, $queue_k, $is_mobile, $is_nextgen ) {
		$url_tag = $this->_queue[ $queue_k ]['url_tag'];
		$vary    = $this->_queue[ $queue_k ]['vary'];
		$this->_save_css_con( 'ccss', $ccss, $url_tag, $vary, $queue_k, $is_mobile, $is_nextgen );
	}

	/**
	 * Download and save optimized images locally.
	 *
	 * Each image entry has src (original path), webp_url, and avif_url.
	 * Optimized images are saved next to original files.
	 *
	 * @since 8.0
	 *
	 * @param array $imgs Array of image optimization data.
	 * @return void
	 */
	private function _save_imgs( $imgs ) {
		foreach ( $imgs as $img ) {
			if ( empty( $img['src'] ) ) {
				continue;
			}

			// Convert src to local file path
			$local = Utility::is_internal_file( $img['src'] );
			if ( ! $local ) {
				self::debug( 'Skip external img: ' . $img['src'] );
				continue;
			}

			$local_path = $local[0];

			// Fetch and save WebP
			if ( ! empty( $img['webp_url'] ) ) {
				$this->_fetch_img( $img['webp_url'], $local_path . '.webp' );
			}

			// Fetch and save AVIF
			if ( ! empty( $img['avif_url'] ) ) {
				$this->_fetch_img( $img['avif_url'], $local_path . '.avif' );
			}
		}
	}

	/**
	 * Fetch a remote image and save it locally.
	 *
	 * @since 8.0
	 *
	 * @param string $url       The remote image URL.
	 * @param string $save_path The local path to save the image.
	 * @return bool Whether fetch and save succeeded.
	 */
	private function _fetch_img( $url, $save_path ) {
		$response = wp_remote_get(
			$url,
			[
				'timeout'   => 60,
				'sslverify' => false,
			]
		);

		if ( is_wp_error( $response ) ) {
			self::debug( 'Failed to fetch img ' . $url . ': ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			self::debug( 'Empty img response: ' . $url );
			return false;
		}

		File::save( $save_path, $body, true );
		self::debug( 'Saved img: ' . $save_path );

		return true;
	}

	/**
	 * Save optimized content.
	 *
	 * @since 8.0
	 *
	 * @param string $content   The optimized content.
	 * @param string $queue_k   The queue key.
	 * @param bool   $is_mobile Whether is mobile.
	 * @param string $is_nextgen Next-gen image format ('webp', 'avif', or '').
	 */
	private function _save_con( $content, $queue_k, $is_mobile, $is_nextgen ) {
		$content = apply_filters( 'litespeed_optimax', $content, $queue_k );
		self::debug2( 'con: ', $content );

		// Write to file
		$filecon_md5 = md5( $content );

		$filepath_prefix = $this->_build_filepath_prefix( 'optimax' );
		$static_file     = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.html';

		File::save( $static_file, $content, true );

		$url_tag = $this->_queue[ $queue_k ]['url_tag'];
		$vary    = $this->_queue[ $queue_k ]['vary'];
		self::debug2( "Save URL to file [file] $static_file [vary] $vary" );

		$this->cls( 'Data' )->save_url( $url_tag, $vary, 'optimax', $filecon_md5, dirname( $static_file ), $is_mobile, $is_nextgen );

		Purge::add( 'OPTIMAX.' . md5( $queue_k ) );
	}

	/**
	 * Handle all request actions from main cls.
	 *
	 * @since 8.0
	 *
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_GEN:
				self::cron( true );
				break;

			case self::TYPE_CLEAR_Q:
				$this->clear_q( 'optimax' );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
