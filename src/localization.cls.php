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
	 * @since 3.3
	 * @since 7.9 Added support for css/js/fonts localization
	 *
	 * @param string $uri Base64-encoded URL.
	 */
	public function serve_static( $uri ) {
		if ( ! $this->conf( self::O_OPTM_LOCALIZE ) ) {
			exit( 'Not supported' );
		}
		
		$url = base64_decode( $uri ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		// validate url
		if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_safe_redirect( $url );
			wp_die();
		}

		$file = $this->_realpath( $url );
		// Test if resource is already localized
		foreach ( [ 'js', 'css', 'woff2', 'woff', 'ttf' ] as $ext ) {
        	if ( file_exists( $file . '.' . $ext ) ) {
				$file_url = $this->_rewrite( $url );
				wp_safe_redirect( $file_url . '.' . $ext );
				exit;
			}
		}

		// Save to local
		$match   = false;
		$domains = $this->conf( self::O_OPTM_LOCALIZE_DOMAINS );
		foreach ( $domains as $v ) {
			if ( ! $v || 0 === strpos( $v, '#' ) ) {
				continue;
			}

			$domain = $v;
			// Try to parse space split value
			if ( strpos( $v, ' ' ) ) {
				$v = explode( ' ', $v );
				if ( ! empty( $v[1] ) ) {
					$domain = $v[1];
				}
			}

			if ( 0 !== strpos( $domain, 'https://' ) ) {
				continue;
			}

			if ( $url !== $domain ) {
				continue;
			}

			$match = true;
			break;
		}

		if ( ! $match ) {
			exit( 'Not supported' );
		}

		// Create localize folder(if it does not exist)
		$this->_maybe_mk_cache_folder('localres');

		self::debug( 'localize [url] ' . $url );

		// Save data to server
		$tmp_file = $file . '.tmp';
		$response = $this->save_url_to_path( $url, $tmp_file );

		// Stop if request is error
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$error_message = $response->get_error_message();
			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}
			self::debug( 'failed to get: ' . $error_message );
			wp_safe_redirect( $url );
			exit();
		}

		// Save to local
		$content_type     = wp_remote_retrieve_header( $response, 'content-type' );
		$file_ext         = $this->get_file_ext( $content_type ); // Get file extension from header(not all links has extension)
		$file_w_extension = $this->_realpath( $url, $file_ext );

		// Process specific file content
		if ( file_exists( $tmp_file ) ) {
			// CSS - look into file and localize inner font-face
			if ( 'css' === $file_ext ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$body     = file_get_contents( $tmp_file );
				$new_body = $this->process_fontface( $body );
				$new_body = $this->cls('Optimizer')->optm_font_face( $new_body );

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $tmp_file, $new_body );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			rename( $tmp_file, $file_w_extension );
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}

		header('Content-Type: ' . $content_type );
		$url = $this->_rewrite($url, $file_ext);

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * Download and save url to file
	 *
	 * @since 7.8
	 * 
	 * @param string $url File content.
	 * @param string $file File location.
	 * @return array|\WP_Error
	 */
	public function save_url_to_path( $url, $file ) {
		return wp_safe_remote_get(
			$url,
			[
				'timeout' => 180,
				'stream' => true,
				'filename' => $file,
			]
		);
	}

	/**
	 * Process inner font files from main resource url
	 *
	 * @since 7.9
	 * 
	 * @param string $content Content to process.
	 * @return string Content updated with localized link
	 */
	public function process_fontface( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		if ( ! preg_match_all( '/url\(\s*[\'"]?([^\'")]*)[\'"]?\s*\)/i', $content, $matches ) ) {
			return $content;
		}

		$replacements = null;

		// Get font-face declarations. Support for multiple src +  multiple links in src
		preg_match_all('/@font-face\s*\{([^}]+)\}/i', $content, $font_blocks);
		if ( 0 < count( $font_blocks[1] ) ) {
			foreach ( $font_blocks[1] as $block ) {
				// Get fonts url's
				preg_match_all('/src\s*:\s*url\(([^)]+)\)/', $block, $matches);
				if ( 0 < count( $matches[1] ) ) {
					// Change all fonts
					foreach ( $matches[1] as $i => $match ) {
						// Save link to file with no extension
						$match_name = $this->_realpath( $match );
						$match_url  = $this->_rewrite( $match );

						// Test if file is already saved.
						$localized_ext = $this->search_file_extension( LITESPEED_STATIC_DIR . '/localres/', $match );
						if ( $localized_ext ) {
							$replacements[ $match ] = $match_url . '.' . $localized_ext;
						} else {
							$response         = $this->save_url_to_path( $match, $match_name );
							$content_type     = wp_remote_retrieve_header( $response, 'content-type' );
							$file_type        = $this->get_file_ext( $content_type ); // Get file extension from header(not all links has extension)
							$path_w_extension = $match_name . '.' . $file_type;
							$url_w_extension  = $match_url . '.' . $file_type;

							// Rename the file with no extension link to file with extension
							// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
							file_exists( $match_name ) && rename( $match_name, $path_w_extension );
							// Save as replacement
							$replacements[ $match ] = $url_w_extension;
						}
					}
				}
			}
		}

		// Do all replacements
		if ( ! empty( $replacements ) ) {
			$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
		}
		
		return $content;
	}

	/**
	 * Get file extension from content-type
	 *
	 * @since 7.9
	 * 
	 * @param string $content_type Resource content type.
	 * @return bool|string
	 */
	public function get_file_ext( $content_type ) {
		if ( str_contains( $content_type, 'text/css' ) ) {
			return 'css';
		} elseif ( str_contains( $content_type, 'application/javascript' ) || str_contains( $content_type, 'application/x-javascript' ) ) { 
			return 'js';
		} elseif ( str_contains( $content_type, 'font/woff' ) ) {
			return 'woff';
		} elseif ( str_contains( $content_type, 'font/woff2' ) ) {
			return 'woff2';
		} elseif ( str_contains( $content_type, 'font/otf' ) ) {
			return 'otf';
		} elseif ( str_contains( $content_type, 'font/ttf' ) ) {
			return 'ttf';
		}

		return false;
	}

	/**
	 * Get content-type from file extension
	 *
	 * @since 7.9
	 * 
	 * @param string $ext Resource extension.
	 * @return bool|string
	 */
	public function get_file_type( $ext ) {
		if ( 'css' === $ext ) {
			return 'text/css' ;
		} elseif ( 'js' === $ext ) {
			return 'application/javascript';
		} elseif ( 'woff' === $ext ) {
			return 'font/woff';
		} elseif ( 'woff2' === $ext ) {
			return 'font/woff2';
		} elseif ( 'otf' === $ext ) {
			return 'font/otf';
		} elseif ( 'ttf' === $ext ) {
			return 'font/ttf';
		}

		return false;
	}

	/**
	 * Get the final URL of local avatar
	 *
	 * @since 4.5
	 * @since 7.9 Added resource type
	 *
	 * @param string $url Original external URL.
	 * @param string $type Resource type. Empty if type is unknown.
	 * @return string Rewritten local URL.
	 */
	private function _rewrite( $url, $type = '' ) {
		return LITESPEED_STATIC_URL . '/localres/' . $this->_filepath( $url, $type );
	}

	/**
	 * Generate realpath of the cache file
	 *
	 * @since  4.5
	 * @since 7.9 Added resource type
	 * @access private
	 *
	 * @param string $url Original external URL.
	 * @param string $type Resource type. Empty if type is unknown.
	 * @return string Absolute file path.
	 */
	private function _realpath( $url, $type = '' ) {
		return LITESPEED_STATIC_DIR . '/localres/' . $this->_filepath( $url, $type );
	}

	/**
	 * Get filepath
	 *
	 * @since 4.5
	 * @since 7.9 Added resource type. Type can be empty(no extension in link): example Google Fonts
	 *
	 * @param string $url Original external URL.
	 * @param string $type Resource type. Empty if type is unknown.
	 * @return string Relative file path.
	 */
	private function _filepath( $url, $type = '' ) {
		// Prepare data: type and filename
		$type     = ( !empty( $type ) ?  '.' . $type : '' );
		$filename = md5($url) . $type;
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

		$font_display_setting = $this->conf(self::O_OPTM_CSS_FONT_DISPLAY);

		foreach ( $domains as $v ) {
			if ( ! $v || 0 === strpos( $v, '#' ) ) {
				continue;
			}

			$domain = $v;
			// Try to parse space split value
			if ( strpos( $v, ' ' ) ) {
				$v = explode( ' ', $v );
				if ( ! empty( $v[1] ) ) {
					$domain = $v[1];
				}
			}

			if ( 0 !== strpos( $domain, 'https://' ) ) {
				continue;
			}
			
			if ( true === $font_display_setting ) {
				// Strip display=swap appended earlier when Font Display Optimization is enabled
				$content = str_replace(
					array( $domain . '&#038;display=swap', $domain . '&display=swap' ),
					$domain,
					$content
				);
			}

			$content = str_replace( $domain, LITESPEED_STATIC_URL . '/localres/' . base64_encode( $domain ), $content ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		return $content;
	}

	/**
	 * Get file extension from filename.
	 *
	 * @since 7.9
	 * @access public
	 * 
	 * @param string $dir Directory to traverse.
	 * @param string $file File name.
	 * @return bool|string
	 */
	public function search_file_extension( $dir, $file ) {
		$dir = new \DirectoryIterator( $dir );
		foreach ( $dir as $fileinfo ) {
			if (
				! $fileinfo->isDot() && 
				str_starts_with( $fileinfo->getFilename(), md5( $file ) )
			) {
				return $fileinfo->getExtension();
			}
		}

		return false;
	}

	/**
	 * Delete all localization files from folder.
	 *
	 * @since 7.9
	 * @return void
	 */
	public function clear_resources() {
		$folder = LITESPEED_STATIC_DIR . '/localres';
		if ( is_multisite() ) {
			$folder .= get_current_blog_id();
		}
		
		try {
			$dir   = new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS );
			$files = new \RecursiveIteratorIterator( $dir, \RecursiveIteratorIterator::CHILD_FIRST );
			foreach ( $files as $file ) {
				if ( !$file->isDir() ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					unlink( $file->getRealPath() );
				}
			}
		} catch ( \UnexpectedValueException $e ) {
			self::debug( 'Localisation folder not found: ' . $folder );
		}
	}
}
