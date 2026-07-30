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
class Optimax extends Cloud_Queue_Svc {

	const LOG_TAG = '🚀';

	/**
	 * Init.
	 *
	 * @since 8.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Svc id slug — drives queue type, Cloud::SVC_OPTIMAX, and summary key prefix.
	 *
	 * @return string
	 */
	protected function _svc_id() {
		return 'optimax';
	}

	/**
	 * Response field carrying the optimization payload (nested object).
	 *
	 * @return string
	 */
	protected function _data_key() {
		return 'data_optimax';
	}

	/**
	 * Optimax processes whole pages — needs a longer PHP execution window.
	 *
	 * @return int
	 */
	protected function _php_time_limit() {
		return 1200;
	}

	/**
	 * Legacy summary key for the try_later deadline; kept across upgrades.
	 *
	 * @return string
	 */
	protected function _next_run_after_key() {
		return 'ox_next_run_after';
	}

	/**
	 * Build the request body for Cloud::post.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return array
	 */
	protected function _build_payload( $queue_k, $v ) {
		return [
			'url'        => $v['url'],
			'queue_k'    => $queue_k,
			'user_agent' => $v['user_agent'],
			'is_mobile'  => ! empty( $v['is_mobile'] ) ? 1 : 0,
			'is_nextgen' => ! empty( $v['is_nextgen'] ) ? $v['is_nextgen'] : '',
		];
	}

	/**
	 * Fan out the nested optimization payload to four save targets.
	 *
	 * @param array  $ox      data_optimax payload.
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool False when HTML is missing (abort), true otherwise.
	 */
	protected function _save_result( $ox, $queue_k, $v ) {
		if ( empty( $ox['html'] ) ) {
			self::debug( '❌ No HTML in data_optimax [k] ' . $queue_k );
			return false;
		}

		$is_mobile  = ! empty( $v['is_mobile'] );
		$is_nextgen = ! empty( $v['is_nextgen'] ) ? $v['is_nextgen'] : '';

		// 1. Save HTML.
		$this->_save_con( $ox['html'], $queue_k, $is_mobile, $is_nextgen, $v );

		// 2. Save UCSS.
		if ( ! empty( $ox['ucss'] ) ) {
			$this->_save_css_con( 'ucss', $ox['ucss'], $v['url_tag'], $v['vary'], $queue_k, $is_mobile, $is_nextgen );
		}

		// 3. Save CCSS.
		if ( ! empty( $ox['ccss'] ) ) {
			$this->_save_css_con( 'ccss', $ox['ccss'], $v['url_tag'], $v['vary'], $queue_k, $is_mobile, $is_nextgen );
		}

		// 4. Save optimized images.
		if ( ! empty( $ox['imgs'] ) ) {
			$this->_save_imgs( $ox['imgs'] );
		}

		return true;
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

		if ( count( $this->_queue ) > $this->_max_queue_size() ) {
			self::debug( 'Queue is full - ' . $this->_max_queue_size() );
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
	 * Save optimized HTML content.
	 *
	 * @param string $content    The optimized content.
	 * @param string $queue_k    The queue key.
	 * @param bool   $is_mobile  Whether is mobile.
	 * @param string $is_nextgen Next-gen image format ('webp', 'avif', or '').
	 * @param array  $v          Queue item.
	 * @return void
	 */
	private function _save_con( $content, $queue_k, $is_mobile, $is_nextgen, $v ) {
		$content = apply_filters( 'litespeed_optimax', $content, $queue_k );
		self::debug2( 'con: ', $content );

		// Write to file
		$filecon_md5 = md5( $content );

		$filepath_prefix = $this->_build_filepath_prefix( 'optimax' );
		$static_file     = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.html';

		File::save( $static_file, $content, true );

		$url_tag = $v['url_tag'];
		$vary    = $v['vary'];
		self::debug2( "Save URL to file [file] $static_file [vary] $vary" );

		$this->cls( 'Data' )->save_url( $url_tag, $vary, 'optimax', $filecon_md5, dirname( $static_file ), $is_mobile, $is_nextgen );

		Purge::add( 'OPTIMAX.' . md5( $queue_k ) );
	}
}
