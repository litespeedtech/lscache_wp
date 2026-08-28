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
	 * Reject malformed legacy queue rows before dispatch.
	 *
	 * @since 7.9.1
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool
	 */
	protected function _valid_queue_item( $queue_k, $v ) {
		foreach ( [ 'url', 'user_agent', 'url_tag', 'vary' ] as $key ) {
			if ( ! is_array( $v ) || ! isset( $v[ $key ] ) || ! is_string( $v[ $key ] ) ) {
				return false;
			}
		}

		return '' !== $queue_k && '' !== $v['url'] && '' !== $v['url_tag'] &&
			( empty( $v['is_nextgen'] ) || in_array( $v['is_nextgen'], [ 'webp', 'avif' ], true ) );
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
			'optm_ori'   => $this->conf( self::O_IMG_OPTM_ORI ) ? 1 : 0,
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
		if ( ! is_array( $ox ) || empty( $ox['html'] ) || ! is_string( $ox['html'] ) ) {
			self::debug( '❌ No HTML in data_optimax.' );
			return false;
		}
		if ( isset( $ox['imgs'] ) && ! is_array( $ox['imgs'] ) ) {
			return false;
		}
		foreach ( [ 'ucss', 'ccss' ] as $field ) {
			if ( isset( $ox[ $field ] ) && ! is_string( $ox[ $field ] ) ) {
				return false;
			}
		}

		$is_mobile  = ! empty( $v['is_mobile'] );
		$is_nextgen = ! empty( $v['is_nextgen'] ) ? $v['is_nextgen'] : '';

		if ( ! empty( $ox['imgs'] ) && ! $this->_save_imgs( $ox['imgs'] ) ) {
			return false;
		}

		if ( ! empty( $ox['ucss'] ) && ! $this->_save_css_con( 'ucss', $ox['ucss'], $v['url_tag'], $v['vary'], $queue_k, $is_mobile, $is_nextgen ) ) {
			return false;
		}

		if ( ! empty( $ox['ccss'] ) && ! $this->_save_css_con( 'ccss', $ox['ccss'], $v['url_tag'], $v['vary'], $queue_k, $is_mobile, $is_nextgen ) ) {
			return false;
		}

		return $this->_save_con( $ox['html'], $queue_k, $is_mobile, $is_nextgen, $v );
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

		if ( apply_filters( 'litespeed_optimax_per_pagetype', false ) ) {
			return Utility::page_type();
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

		$request_url = Utility::request_url();

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

		$queue_k = ( strlen( $vary ) > 32 ? md5( $vary ) : $vary ) . ' ' . $url_tag;
		if ( ! isset( $this->_queue[ $queue_k ] ) && count( $this->_queue ) >= $this->_max_queue_size() ) {
			self::debug( 'Queue is full - ' . $this->_max_queue_size() );
			return false;
		}
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
		self::debug( 'Added Optimax queue item [request] ' . substr( hash( 'sha256', $queue_k ), 0, 12 ) );

		// Prepare cache tag for later purge
		Tag::add( 'OPTIMAX.' . md5( $queue_k ) );
		Core::comment( 'QUIC.cloud Optimax in queue' );

		return false;
	}

	/**
	 * Download and save optimized images locally.
	 *
	 * Each image entry has src and any requested ori/webp/avif artifact.
	 * Optimized images are saved beside their WordPress image targets.
	 *
	 * @since 8.0
	 *
	 * @param array $imgs Array of image optimization data.
	 * @return bool
	 */
	private function _save_imgs( $imgs ) {
		if ( ! is_array( $imgs ) ) {
			return false;
		}

		$hooks        = [
			'ori'  => 'litespeed_img_pull_ori',
			'webp' => 'litespeed_img_pull_webp',
			'avif' => 'litespeed_img_pull_avif',
		];
		$types        = [ 'webp', 'avif' ];
		$optm_ori     = (bool) $this->conf( self::O_IMG_OPTM_ORI );
		$preserve_ori = $optm_ori && ! $this->conf( self::O_IMG_OPTM_RM_BKUP );
		if ( $optm_ori ) {
			$types[] = 'ori';
		}

		foreach ( $imgs as $img ) {
			if ( ! is_array( $img ) ) {
				return false;
			}

			$artifacts = [];
			foreach ( $types as $type ) {
				$url_key    = $type . '_url';
				$digest_key = $type . '_sha256';
				if ( empty( $img[ $url_key ] ) ) {
					continue;
				}
				$url = Img::normalize_cloud_url( $img[ $url_key ] );
				if (
					! $url || empty( $img[ $digest_key ] ) || ! is_string( $img[ $digest_key ] ) || ! preg_match( '/^[a-f0-9]{64}$/iD', $img[ $digest_key ] )
				) {
					return false;
				}
				$artifacts[ $type ] = [
					'url'    => $url,
					'digest' => strtolower( $img[ $digest_key ] ),
				];
			}

			if ( empty( $artifacts ) ) {
				continue;
			}
			if ( empty( $img['src'] ) || ! is_string( $img['src'] ) || 2048 < strlen( $img['src'] ) ) {
				self::debug( 'Skip Optimax image entry without a usable local source.' );
				continue;
			}
			$local = $this->_image_target( $img );
			if ( ! $local ) {
				self::debug( 'Skip Optimax image entry without a WordPress image target.' );
				continue;
			}
			list( $local_path, $local_root, $row ) = $local;

			$published = [];
			foreach ( $artifacts as $type => $artifact ) {
				$target = 'ori' === $type ? $local_path : $local_path . '.' . $type;
				$res    = Img::save( $artifact['url'], $target, $artifact['digest'], 'sha256', $type, $local_root, 'ori' === $type && $preserve_ori );
				if ( is_wp_error( $res ) ) {
					// Log the URL without its query string: it may carry a token.
					$parts  = wp_parse_url( $artifact['url'] );
					$label  = $parts['host'] . ( ! empty( $parts['path'] ) ? $parts['path'] : '/' );
					$detail = $res->get_error_data();
					self::debug( '❌ Failed to save img [url] ' . $label . ' [error] ' . $res->get_error_code() . ( '' !== (string) $detail ? ':' . $detail : '' ) );
					return false;
				}
				$published[ $type ] = $target;
			}
			if ( $row ) {
				foreach ( $hooks as $type => $hook ) {
					if ( isset( $published[ $type ] ) ) {
						do_action( $hook, $row, $published[ $type ] );
					}
				}
			}
		}

		return true;
	}

	/**
	 * Resolve an image to a local target and optional attachment hook context.
	 *
	 * @param array $img Cloud image entry.
	 * @return array|false `[ path, bound root, hook row ]`, or false.
	 */
	private function _image_target( $img ) {
		$post_id = attachment_url_to_postid( $img['src'] );
		if ( 0 < $post_id ) {
			$uploads  = wp_upload_dir();
			$base     = ! empty( $uploads['basedir'] ) ? trailingslashit( wp_normalize_path( $uploads['basedir'] ) ) : '';
			$attached = get_attached_file( $post_id, true );
			$attached = is_string( $attached ) ? wp_normalize_path( $attached ) : '';
			$url_path = wp_parse_url( $img['src'], PHP_URL_PATH );
			$filename = is_string( $url_path ) ? rawurldecode( basename( $url_path ) ) : '';
			if ( $base && 0 === strpos( $attached, $base ) && $filename ) {
				$dir   = dirname( substr( $attached, strlen( $base ) ) );
				$short = ( '.' === $dir ? '' : trailingslashit( $dir ) ) . $filename;
				$local = Img::normalize_cloud_path( $short ) === $short ? Img::local_file( apply_filters( 'litespeed_realpath', $base . $short ) ) : false;
				if ( $local && ( file_exists( $local[0] ) || $this->cls( 'Media' )->info( $short, $post_id ) ) ) {
					return [ $local[0], $local[1], (object) [ 'post_id' => $post_id, 'src' => $short ] ];
				}
			}
		}

		$local = Utility::is_internal_file( $img['src'] );
		$local = $local && ! empty( $local[0] ) ? Img::local_file( $local[0] ) : false;
		return $local ? [ $local[0], $local[1], false ] : false;
	}

	/**
	 * Save optimized HTML content.
	 *
	 * @param string $content    The optimized content.
	 * @param string $queue_k    The queue key.
	 * @param bool   $is_mobile  Whether is mobile.
	 * @param string $is_nextgen Next-gen image format ('webp', 'avif', or '').
	 * @param array  $v          Queue item.
	 * @return bool
	 */
	private function _save_con( $content, $queue_k, $is_mobile, $is_nextgen, $v ) {
		$content = apply_filters( 'litespeed_optimax', $content, $queue_k );
		if ( ! is_string( $content ) ) {
			return false;
		}
		$content = File::remove_zero_space( $content );

		// Write to file
		$filecon_md5 = md5( $content );

		$filepath_prefix = $this->_build_filepath_prefix( 'optimax' );
		$static_file     = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.html';

		if ( ! File::save_atomic( $static_file, $content ) ) {
			return false;
		}

		$url_tag = $v['url_tag'];
		$vary    = $v['vary'];
		self::debug2( "Save URL to file [file] $static_file [vary] $vary" );

		$data = $this->cls( 'Data' );
		$data->save_url( $url_tag, $vary, 'optimax', $filecon_md5, dirname( $static_file ), $is_mobile, $is_nextgen );
		if ( $filecon_md5 !== $data->load_url_file( $url_tag, $vary, 'optimax' ) ) {
			return false;
		}

		Purge::add( 'OPTIMAX.' . md5( $queue_k ) );
		return true;
	}
}
