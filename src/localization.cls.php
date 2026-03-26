<?php
/**
 * The localization class.
 *
 * @since   3.3
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Localization - serve external resources locally.
 *
 * @since 3.3
 */
class Localization extends Base {

	const LOG_TAG = '🛍️';

	/**
	 * Init optimizer
	 *
	 * @since  3.0
	 * @access protected
	 */
	public function init() {
		add_filter( 'litespeed_buffer_finalize', [ $this, 'finalize' ], 23 ); // After page optm
	}

	/**
	 * Localize Resources
	 *
	 * @since  3.3
	 *
	 * @param string $uri Base64-encoded URL.
	 */
	public function serve_static( $uri ) {
		$url = base64_decode( $uri ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! $this->conf( self::O_OPTM_LOCALIZE ) ) {
			exit( 'Not supported' );
		}

		$match   = false;
		$domains = $this->conf( self::O_OPTM_LOCALIZE_DOMAINS );
		foreach ( $domains as $v ) {
			if ( ! $v || 0 === strpos( $v, '#' ) ) {
				continue;
			}

			$type   = 'js';
			$domain = $v;
			// Try to parse space split value
			if ( strpos( $v, ' ' ) ) {
				$v = explode( ' ', $v );
				if ( ! empty( $v[1] ) ) {
					$type   = strtolower( $v[0] );
					$domain = $v[1];
				}
			}

			if ( 0 !== strpos( $domain, 'https://' ) ) {
				continue;
			}

			if ( 'js' !== $type ) {
				continue;
			}

			if ( $url !== $domain ) {
				continue;
			}

			$match = true;
			break;
		}

		if ( ! $match ) {
			exit( 'Not supported2' );
		}

		header( 'Content-Type: application/javascript' );

		// Generate
		$this->_maybe_mk_cache_folder( 'localres' );

		$file = $this->_realpath( $url );

		self::debug( 'localize [url] ' . $url );
		$response = wp_safe_remote_get( $url, [
			'timeout'  => 180,
			'stream'   => true,
			'filename' => $file,
		] );

		// Parse response data
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			self::debug( 'failed to get: ' . $error_message );
			wp_safe_redirect( $url );
			exit();
		}

		$url = $this->_rewrite( $url );

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * Get the final URL of local avatar
	 *
	 * @since 4.5
	 *
	 * @param string $url Original external URL.
	 * @return string Rewritten local URL.
	 */
	private function _rewrite( $url ) {
		return LITESPEED_STATIC_URL . '/localres/' . $this->_filepath( $url );
	}

	/**
	 * Generate realpath of the cache file
	 *
	 * @since  4.5
	 * @access private
	 *
	 * @param string $url Original external URL.
	 * @return string Absolute file path.
	 */
	private function _realpath( $url ) {
		return LITESPEED_STATIC_DIR . '/localres/' . $this->_filepath( $url );
	}

	/**
	 * Get filepath
	 *
	 * @since 4.5
	 *
	 * @param string $url Original external URL.
	 * @return string Relative file path.
	 */
	private function _filepath( $url ) {
		$filename = md5( $url ) . '.js';
		if ( is_multisite() ) {
			$filename = get_current_blog_id() . '/' . $filename;
		}
		return $filename;
	}

	/**
	 * Localize JS/Fonts
	 *
	 * @since 3.3
	 * @access public
	 *
	 * @param string $content Page HTML content.
	 * @return string Modified content with localized URLs.
	 */
	public function finalize( $content ) {
		if ( is_admin() ) {
			return $content;
		}

		if ( ! $this->conf( self::O_OPTM_LOCALIZE ) ) {
			return $content;
		}

		$domains = $this->conf( self::O_OPTM_LOCALIZE_DOMAINS );
		if ( ! $domains ) {
			return $content;
		}

		foreach ( $domains as $v ) {
			if ( ! $v || 0 === strpos( $v, '#' ) ) {
				continue;
			}

			$type   = 'js';
			$domain = $v;
			// Try to parse space split value
			if ( strpos( $v, ' ' ) ) {
				$v = explode( ' ', $v );
				if ( ! empty( $v[1] ) ) {
					$type   = strtolower( $v[0] );
					$domain = $v[1];
				}
			}

			if ( 0 !== strpos( $domain, 'https://' ) ) {
				continue;
			}

			if ( 'js' !== $type ) {
				continue;
			}

			$content = str_replace( $domain, LITESPEED_STATIC_URL . '/localres/' . base64_encode( $domain ), $content ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		return $content;
	}
}
