<?php
/**
 * The localization class.
 *
 * @since      	3.3
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Localization extends Base {
	const LOG_TAG = '🛍️';

	/**
	 * Init optimizer
	 *
	 * @since  3.0
	 * @access protected
	 */
	public function init() {
		add_filter( 'litespeed_buffer_finalize', array( $this, 'finalize' ), 23 ); // After page optm
	}

	/**
	 * Localize Resources
	 *
	 * @since  3.3
	 */
	public function serve_static( $uri ) {
		$url = 'https://' . $uri;

		if ( ! $this->conf( self::O_OPTM_LOCALIZE ) ) {
			// wp_redirect( $url );
			exit( 'Not supported' );
		}

		if ( substr( $url, -3 ) !== '.js' ) {
			// wp_redirect( $url );
			exit( 'Not supported' );
		}

		$match = false;
		$domains = $this->conf( self::O_OPTM_LOCALIZE_DOMAINS );
		foreach ( $domains as $v ) {
			if ( ! $v || strpos( $v, '#' ) === 0 ) {
				continue;
			}

			$type = 'js';
			$domain = $v;
			// Try to parse space splitted value
			if ( strpos( $v, ' ' ) ) {
				$v = explode( ' ', $v );
				if ( ! empty( $v[ 1 ] ) ) {
					$type = strtolower( $v[ 0 ] );
					$domain = $v[ 1 ];
				}
			}

			if ( strpos( $domain, 'https://' ) !== 0 ) {
				continue;
			}

			if ( $type != 'js' ) {
				continue;
			}

			if ( strpos( $url, $domain ) !== 0 ) {
				continue;
			}

			$match = true;
			break;
		}

		if ( ! $match ) {
			// wp_redirect( $url );
			exit( 'Not supported' );
		}

		header( 'Content-Type: application/javascript' );

		// Generate
		$this->_maybe_mk_cache_folder( 'localres' );

		$file = $this->_realpath( $url );

		self::debug( 'localize [url] ' . $url );
		$response = wp_remote_get( $url, array( 'timeout' => 180, 'stream' => true, 'filename' => $file ) );

		// Parse response data
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			file_exists( $file ) && unlink( $file );
			self::debug( 'failed to get: ' . $error_message );
			wp_redirect( $url );
			exit;
		}

		$url = $this->_rewrite( $url );

		wp_redirect( $url );
		exit;
	}


	/**
	 * Get the final URL of local avatar
	 *
	 * @since  4.5
	 */
	private function _rewrite( $url ) {
		return LITESPEED_STATIC_URL . '/localres/' . $this->_filepath( $url );
	}

	/**
	 * Generate realpath of the cache file
	 *
	 * @since  4.5
	 * @access private
	 */
	private function _realpath( $url ) {
		return LITESPEED_STATIC_DIR . '/localres/' . $this->_filepath( $url );
	}

	/**
	 * Get filepath
	 *
	 * @since  4.5
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
			if ( ! $v || strpos( $v, '#' ) === 0 ) {
				continue;
			}

			$type = 'js';
			$domain = $v;
			// Try to parse space splitted value
			if ( strpos( $v, ' ' ) ) {
				$v = explode( ' ', $v );
				if ( ! empty( $v[ 1 ] ) ) {
					$type = strtolower( $v[ 0 ] );
					$domain = $v[ 1 ];
				}
			}

			if ( strpos( $domain, 'https://' ) !== 0 ) {
				continue;
			}

			if ( $type != 'js' ) {
				continue;
			}

			$content = str_replace( $domain, LITESPEED_STATIC_URL . '/localres/' . substr( $domain, 8 ), $content );
		}

		return $content;
	}

}