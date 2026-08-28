<?php
/**
 * Shared image fetcher.
 *
 * @package LiteSpeed
 * @since 7.9.1
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Fetch and stage verified image artifacts.
 *
 * @since 7.9.1
 */
class Img {

	/**
	 * Maximum artifact size in bytes.
	 *
	 * @since 7.9.1
	 */
	const MAX_BYTES = File::REMOTE_MAX_BYTES;

	/**
	 * Fetch failure codes.
	 *
	 * @since 7.9.1
	 */
	const E_NET  = File::E_NET;
	const E_HTTP = File::E_HTTP;
	const E_DATA = File::E_DATA;
	const E_FILE = File::E_FILE;

	/**
	 * POSIX `stat()` mode bits: the file-type field and the regular-file value.
	 *
	 * @since 7.9.1
	 */
	const STAT_TYPE_MASK = 0170000;
	const STAT_REGULAR   = 0100000;

	/**
	 * Normalize a QUIC.cloud artifact URL or origin.
	 *
	 * @since 7.9.1
	 *
	 * @param mixed $url         Artifact URL or origin.
	 * @param bool  $origin_only Whether paths and queries are forbidden.
	 * @return string|false
	 */
	public static function normalize_cloud_url( $url, $origin_only = false ) {
		if ( ! is_string( $url ) || '' === $url || 2048 < strlen( $url ) || preg_match( '/[\x00-\x20\x7f]/', $url ) ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		if (
			! is_array( $parts ) || empty( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] ) || empty( $parts['host'] ) ||
			isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) || ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) ||
			( $origin_only && ( isset( $parts['query'] ) || ( ! empty( $parts['path'] ) && '/' !== $parts['path'] ) ) )
		) {
			return false;
		}
		$host = strtolower( rtrim( $parts['host'], '.' ) );
		if ( ! filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) ) {
			return false;
		}
		$valid_host =
			( 11 < strlen( $host ) && '.quic.cloud' === substr( $host, -11 ) ) ||
			( 15 < strlen( $host ) && '.quicserver.com' === substr( $host, -15 ) );
		if ( ! $valid_host ) {
			return false;
		}
		return $origin_only ? 'https://' . $host : $url;
	}

	/**
	 * Normalize a relative path returned by an image service.
	 *
	 * @since 7.9.1
	 *
	 * @param mixed $path Service-relative path.
	 * @return string|false
	 */
	public static function normalize_cloud_path( $path ) {
		if ( ! is_string( $path ) || '' === $path || preg_match( '/[\x00-\x20\x7f]/', $path ) ) {
			return false;
		}

		$decoded_path  = rawurldecode( $path );
		$relative_path = ltrim( $path, '/' );
		if (
			'' === $relative_path || preg_match( '/[\x00-\x1f\x7f]/', $decoded_path ) || '//' === substr( $decoded_path, 0, 2 ) ||
			false !== strpos( $decoded_path, '\\' ) || false !== strpos( $decoded_path, '?' ) || false !== strpos( $decoded_path, '#' ) ||
			preg_match( '#^[a-z][a-z0-9+.-]*://#i', $decoded_path ) || preg_match( '#(^|/)\.{1,2}(/|$)#', $decoded_path )
		) {
			return false;
		}

		return $relative_path;
	}

	/**
	 * Resolve a WordPress image target and bind its closest trusted root.
	 *
	 * @since 7.9.1
	 *
	 * @param mixed $file Local file path after `litespeed_realpath` filtering.
	 * @return array|false `[ resolved path, bound root ]`, or false.
	 */
	public static function local_file( $file ) {
		if ( ! is_string( $file ) || '' === $file || false !== strpos( $file, "\0" ) || is_link( $file ) ) {
			return false;
		}
		$file = wp_normalize_path( $file );
		if ( ! in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), [ 'jpg', 'jpeg', 'png', 'gif' ], true ) ) {
			return false;
		}
		if ( file_exists( $file ) ) {
			$file = realpath( $file );
			if ( ! $file || ! is_file( $file ) ) {
				return false;
			}
			$file  = wp_normalize_path( $file );
			$probe = $file;
		} else {
			$parent = realpath( dirname( $file ) );
			if ( ! $parent ) {
				return false;
			}
			$file  = trailingslashit( wp_normalize_path( $parent ) ) . basename( $file );
			$probe = dirname( $file );
		}

		$upload_dir = wp_upload_dir();
		$roots      = ! empty( $upload_dir['basedir'] ) ? [ $upload_dir['basedir'] ] : [];
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$roots[] = constant( 'WP_CONTENT_DIR' );
		}
		$roots[] = ABSPATH;
		foreach ( $roots as $root ) {
			$bound = File::bind_root( $root );
			if ( $bound && File::within( $probe, $bound ) ) {
				return [ $file, $bound ];
			}
		}

		return false;
	}

	/**
	 * Fetch and stage an image beside its destination.
	 *
	 * @param string       $url  Remote image URL.
	 * @param string       $file Destination path.
	 * @param string       $sum  Expected checksum.
	 * @param string       $algo Checksum algorithm.
	 * @param string       $type Image type.
	 * @param string|array $root Directory the destination must still resolve inside; empty skips the check.
	 * @return string|\WP_Error Temporary file path or failure.
	 */
	public static function fetch( $url, $file, $sum, $algo, $type, $root = '' ) {
		$tmp = File::download( $url, $file, 60, 0, $root );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		if ( ! self::_valid( $tmp, $sum, $algo, $type ) ) {
			wp_delete_file( $tmp );
			return new \WP_Error( self::E_DATA );
		}

		return $tmp;
	}

	/**
	 * Fetch and publish an image.
	 *
	 * @param string       $url  Remote image URL.
	 * @param string       $file Destination path.
	 * @param string       $sum  Expected checksum.
	 * @param string       $algo Checksum algorithm.
	 * @param string       $type Image type.
	 * @param string|array $root   Directory the destination must still resolve inside; empty skips the check.
	 * @param bool         $preserve Whether to preserve an original-image backup.
	 * @return true|\WP_Error True on success, otherwise the failure.
	 */
	public static function save( $url, $file, $sum, $algo, $type, $root = '', $preserve = false ) {
		if ( is_link( $file ) ) {
			return new \WP_Error( self::E_FILE );
		}

		$tmp = self::fetch( $url, $file, $sum, $algo, $type, $root );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		if ( ! self::publish( $tmp, $file, $root, $preserve ) ) {
			return new \WP_Error( self::E_FILE );
		}

		return true;
	}

	/**
	 * Atomically publish an image, preserving the original once when requested.
	 *
	 * @since 7.9.1
	 *
	 * @param string       $staged Staged optimized image.
	 * @param string       $target Destination image.
	 * @param string|array $root   Bound destination root.
	 * @param bool         $preserve Whether to preserve the current original.
	 * @return bool
	 */
	public static function publish( $staged, $target, $root = '', $preserve = false ) {
		if ( $preserve && file_exists( $target ) ) {
			$extension = pathinfo( $target, PATHINFO_EXTENSION );
			$backup    = $extension ? substr( $target, 0, -strlen( $extension ) ) . 'bk.' . $extension : '';
			if ( ! $backup || ( file_exists( $backup ) && ( is_link( $backup ) || ! is_file( $backup ) ) ) ) {
				wp_delete_file( $staged );
				return false;
			}
			if ( ! file_exists( $backup ) ) {
				$source     = self::open_regular( $target, $root );
				$stat       = $source ? fstat( $source ) : false;
				$tmp_backup = $stat ? File::temp_file( dirname( $target ), '.lscwp-bk-', $root ) : false;
				$copied     = $tmp_backup && self::copy_stream( $source, $tmp_backup, $stat['size'] );
				if ( $source ) {
					fclose( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				}
				if ( ! $copied || ! File::publish_temp_file( $tmp_backup, $backup, $root ) ) {
					if ( $tmp_backup ) {
						wp_delete_file( $tmp_backup );
					}
					wp_delete_file( $staged );
					return false;
				}
			}
		}

		return File::publish_temp_file( $staged, $target, $root );
	}

	/**
	 * Open a regular file and prove the handle is the inode the path named.
	 *
	 * Guards a check-then-use gap that spans a network round trip; see `__func.md`.
	 *
	 * @since 7.9.1
	 *
	 * @param string       $file File path.
	 * @param string|array $root Directory the file must still resolve inside; empty skips the check.
	 * @return resource|false
	 */
	public static function open_regular( $file, $root = '' ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$before = @lstat( $file );
		if ( ! $before || self::STAT_REGULAR !== ( $before['mode'] & self::STAT_TYPE_MASK ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.PHP.NoSilencedErrors.Discouraged
		$handle = @fopen( $file, 'rb' );
		if ( ! $handle ) {
			return false;
		}

		$after = fstat( $handle );
		// Identity alone is not containment: a swapped parent makes lstat and fstat agree on the wrong file.
		if ( ! $after || $after['dev'] !== $before['dev'] || $after['ino'] !== $before['ino'] || self::STAT_REGULAR !== ( $after['mode'] & self::STAT_TYPE_MASK ) || ! File::within( $file, $root ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
			return false;
		}

		return $handle;
	}

	/**
	 * Copy an open handle into a path, verifying the byte count.
	 *
	 * @since 7.9.1
	 *
	 * @param resource $source   Open read handle.
	 * @param string   $file     Destination path.
	 * @param int      $expected Expected byte count.
	 * @return bool
	 */
	public static function copy_stream( $source, $file, $expected ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.PHP.NoSilencedErrors.Discouraged
		$dest = @fopen( $file, 'wb' );
		if ( ! $dest ) {
			return false;
		}

		$copied = stream_copy_to_stream( $source, $dest );
		$ok     = false !== $copied && $copied === $expected && fflush( $dest );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $dest );

		return $ok;
	}

	/**
	 * Validate a downloaded image file.
	 *
	 * @param string $file Temporary image file.
	 * @param string $sum  Expected checksum.
	 * @param string $algo Checksum algorithm.
	 * @param string $type Image type.
	 * @return bool
	 */
	private static function _valid( $file, $sum, $algo, $type ) {
		$size = is_string( $file ) && is_file( $file ) ? filesize( $file ) : false;
		if ( ! $size || self::MAX_BYTES < $size || ! is_string( $sum ) ) {
			return false;
		}
		if ( 'md5' === $algo ) {
			$valid_sum = (bool) preg_match( '/^[a-f0-9]{32}$/iD', $sum );
		} elseif ( 'sha256' === $algo ) {
			$valid_sum = (bool) preg_match( '/^[a-f0-9]{64}$/iD', $sum );
		} else {
			return false;
		}

		$actual = hash_file( $algo, $file );
		return $valid_sum && is_string( $actual ) && hash_equals( strtolower( $sum ), $actual ) && self::_is_img( $file, $type );
	}

	/**
	 * Validate image magic bytes.
	 *
	 * @param string $file Image file.
	 * @param string $type Image type.
	 * @return bool
	 */
	private static function _is_img( $file, $type ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged
		$head = @file_get_contents( $file, false, null, 0, 32 );
		if ( ! is_string( $head ) ) {
			return false;
		}
		if ( 'webp' === $type ) {
			return 12 <= strlen( $head ) && 'RIFF' === substr( $head, 0, 4 ) && 'WEBP' === substr( $head, 8, 4 );
		}
		if ( 'avif' === $type ) {
			$brands = substr( $head, 4, 28 );
			return 12 <= strlen( $head ) && 'ftyp' === substr( $head, 4, 4 ) && ( false !== strpos( $brands, 'avif' ) || false !== strpos( $brands, 'avis' ) );
		}

		return 'ori' === $type && false !== @getimagesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}
