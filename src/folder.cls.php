<?php
/**
 * Cache folder size guard.
 *
 * @since      8.0
 * @package    LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Enforce a maximum size for the regenerable cache-asset subdirs under
 * wp-content/litespeed/. Triggered by WP cron via Task; the actual check runs
 * oldest-first across the asset subdirs and prunes until the directory is
 * under a 90% hysteresis target.
 *
 * @since 8.0
 */
class Folder extends Base {

	const LOG_TAG = '[Folder]';

	/**
	 * Subdirs that hold regenerable cache assets and are safe to prune.
	 *
	 * @var string[]
	 */
	const ASSET_SUBDIRS = [ 'css', 'js', 'ucss', 'ccss', 'optimax', 'lqip', 'avatar', 'localres' ];

	/**
	 * Per-run cap to avoid hogging a single cron tick on pathological dirs.
	 *
	 * @var int
	 */
	const PER_RUN_DELETE_LIMIT = 5000;

	/**
	 * Cron entry point — measure asset subdirs and prune oldest-first when
	 * over the configured size budget.
	 *
	 * @since 8.0
	 */
	public static function cron() {
		$max_mb = (int) self::cls()->conf( self::O_MISC_MAX_FOLDER_SIZE );
		if ( $max_mb <= 0 ) {
			return;
		}

		if ( ! defined( 'LITESPEED_STATIC_DIR' ) || ! is_dir( LITESPEED_STATIC_DIR ) ) {
			return;
		}

		$max_bytes = $max_mb * 1000000;

		$entries = self::_collect_files();
		if ( empty( $entries ) ) {
			return;
		}

		$total = 0;
		foreach ( $entries as $e ) {
			$total += $e['size'];
		}

		$target = (int) ( $max_bytes * 0.9 );
		if ( $total <= $target ) {
			return;
		}

		usort( $entries, function ( $a, $b ) {
			return $a['mtime'] <=> $b['mtime'];
		} );

		$over      = $total - $target;
		$deleted   = 0;
		$reclaimed = 0;
		foreach ( $entries as $e ) {
			if ( $over <= 0 || $deleted >= self::PER_RUN_DELETE_LIMIT ) {
				break;
			}
			if ( ! is_file( $e['path'] ) ) {
				continue;
			}

			wp_delete_file( $e['path'] );
			if ( ! is_file( $e['path'] ) ) {
				$over      -= $e['size'];
				$reclaimed += $e['size'];
				++$deleted;
			}
		}

		if ( $deleted > 0 ) {
			self::debug( sprintf(
				'Pruned %d file(s) to enforce %d MB limit (reclaimed %s, total before %s).',
				$deleted,
				$max_mb,
				Utility::real_size( $reclaimed ),
				Utility::real_size( $total )
			) );

			// Recreate .htaccess if all asset files were wiped.
			File::ensure_static_protection();
		}
	}

	/**
	 * Walk the regenerable asset subdirs and collect file metadata for
	 * size accounting and prune ordering. Multisite-safe (walks per-blog
	 * subfolders via the recursive iterator).
	 *
	 * @since 8.0
	 *
	 * @return array<int,array{path:string,mtime:int,size:int}>
	 */
	private static function _collect_files() {
		$entries = [];

		foreach ( self::ASSET_SUBDIRS as $subdir ) {
			$base = LITESPEED_STATIC_DIR . '/' . $subdir;
			if ( ! is_dir( $base ) ) {
				continue;
			}

			try {
				$iter = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ),
					\RecursiveIteratorIterator::LEAVES_ONLY,
					\RecursiveIteratorIterator::CATCH_GET_CHILD
				);
			} catch ( \Throwable $e ) {
				self::debug( 'Skip unreadable subdir [subdir] ' . $subdir );
				continue;
			}

			try {
				foreach ( $iter as $file ) {
					if ( ! $file->isFile() ) {
						continue;
					}
					$entries[] = [
						'path'  => $file->getPathname(),
						'mtime' => $file->getMTime(),
						'size'  => (int) $file->getSize(),
					];
				}
			} catch ( \Throwable $e ) {
				self::debug( 'Aborted walking subdir [subdir] ' . $subdir );
			}
		}

		return $entries;
	}
}
