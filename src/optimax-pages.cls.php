<?php
/**
 * OptimaX builds per page: their fingerprints, expiry, stats and rebuilds.
 *
 * @since   8.0
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * OptimaX pages: a page is its `litespeed_url` row, its versions are its build rows (one per vary) and queue rows.
 *
 * @since 8.0
 */
class Optimax_Pages extends Root {

	const LOG_TAG = '🚀';

	/** `url_file.type` ids (= Data::$_url_file_types). */
	const TYPE_CCSS = 3;
	const TYPE_UCSS = 4;
	const TYPE_HTML = 5;
	const TYPE_SRC  = 6;

	/** Suffix of a build's asset manifest, beside its `<md5>.html`. */
	const ASSETS_EXT = '.assets.json';

	/** How long a finished page-change check stays listed, in seconds. */
	const CHECK_KEEP = 86400;

	/** Most page-change checks listed; the oldest go first. */
	const CHECK_MAX = 100;

	/** How a build was made, kept in its manifest: asked for, built in full after a change, or patched by the plugin or QUIC.cloud. */
	const ACTIONS = [ 'generated', 'rebuilt', 'synced_plugin', 'synced_qc' ];

	/** Why a build was made: its queue row's `why`, or the patch. */
	const CAUSES = [ 'visit', 'missing', 'ttl', 'bulk', 'design', 'sync', 'sync_failed', 'patch' ];

	/**
	 * One spelling per URL: bytes outside a URL's character set percent-encoded, every escape upper-cased.
	 *
	 * @since 8.0
	 * @param string $url URL.
	 * @return string
	 */
	public static function canonical_url( $url ) {
		return preg_replace_callback( '/[^A-Za-z0-9\-._~!$&\'()*+,;=:@\/?#%\[\]]|%[0-9A-Fa-f]{2}/', function ( $m ) {
			return '%' === $m[0][0] ? strtoupper( $m[0] ) : rawurlencode( $m[0] );
		}, (string) $url );
	}

	/**
	 * Whether a query has an arg other than those in Drop Query String (`*` matching any characters, as there).
	 *
	 * @since 8.0
	 * @param array $keys    Query arg names.
	 * @param array $drop_qs Drop Query String patterns.
	 * @return bool
	 */
	public static function has_other_query( $keys, $drop_qs ) {
		foreach ( (array) $keys as $key ) {
			foreach ( (array) $drop_qs as $pattern ) {
				if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( (string) $pattern, '/' ) ) . '\z/', (string) $key ) ) {
					continue 2;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * A version's queue key: stored vary, then page identity.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Vary, raw or stored.
	 * @return string
	 */
	public static function queue_key( $url_tag, $vary ) {
		return self::vary_key( $vary ) . ' ' . $url_tag;
	}

	/**
	 * A vary in the form build rows store it (md5 when longer than 32 chars).
	 *
	 * @since 8.0
	 * @param string $vary Raw vary.
	 * @return string
	 */
	public static function vary_key( $vary ) {
		$vary = (string) $vary;
		return strlen( $vary ) > 32 ? md5( $vary ) : $vary;
	}

	/**
	 * Which visitor groups a version's vary stands for, read from its `ismobile`/`webp`/`avif` tokens.
	 *
	 * @since 8.0
	 * @param string    $vary   Raw vary (queue row) or stored vary (build row).
	 * @param bool|null $mobile Build row `mobile` column, used only for a hashed vary.
	 * @param bool|null $webp   Build row `webp` column, used only for a hashed vary.
	 * @return array{mobile:bool,nextgen:bool}
	 */
	public static function version_groups( $vary, $mobile = null, $webp = null ) {
		$tokens = explode( '+', (string) $vary );
		// A long vary is stored as its md5, which has no tokens.
		$hashed = preg_match( '/^[a-f0-9]{32}$/', (string) $vary );
		return [
			'mobile'  => $hashed ? (bool) $mobile : in_array( 'ismobile', $tokens, true ),
			'nextgen' => $hashed ? (bool) $webp : (bool) array_intersect( [ 'webp', 'avif' ], $tokens ),
		];
	}

	/**
	 * Whether a version is one OptimaX serves under the Next-Gen Image Format setting: next-gen when on, plain when off.
	 *
	 * @since 8.0
	 * @param string    $vary Raw vary (queue row) or stored vary (build row).
	 * @param bool|null $webp Build row `webp` column, used only for a hashed vary.
	 * @return bool
	 */
	public static function is_served_version( $vary, $webp = null ) {
		return ( 0 !== (int) Optimax::cls()->conf( Base::O_IMG_OPTM_WEBP ) ) === self::version_groups( $vary, null, $webp )['nextgen'];
	}

	/**
	 * The vary of the same version on the other device; `+ismobile` goes before `+webp`, as the server's rewrite rules append them.
	 *
	 * @since 8.0
	 * @param string $vary Raw vary.
	 * @return string
	 */
	public static function device_sibling( $vary ) {
		$vary = (string) $vary;
		if ( false !== strpos( $vary, '+ismobile' ) ) {
			return str_replace( '+ismobile', '', $vary );
		}
		$pos = strpos( $vary, '+webp' );
		$pos = false === $pos ? strpos( $vary, '+avif' ) : $pos;
		return substr_replace( $vary, '+ismobile', false === $pos ? strlen( $vary ) : $pos, 0 );
	}

	/**
	 * Add a version's queue row, and its other device's when that one's builds expired with the page's design.
	 *
	 * @since 8.0
	 * @param array $queue Queue, changed in place.
	 * @param array $row   Queue row: `url`, `is_mobile`, `is_nextgen`, `vary`, `url_tag`, `why` (a CAUSES value), `why_detail`.
	 * @param int   $max   Queue size limit.
	 * @return string|false Queue key of the version asked for; false when the queue is full.
	 */
	public function add_versions( &$queue, $row, $max ) {
		$queue_k = self::queue_key( $row['url_tag'], $row['vary'] );
		if ( ! isset( $queue[ $queue_k ] ) ) {
			if ( count( $queue ) >= $max ) {
				self::debug( 'Queue is full - ' . $max );
				return false;
			}
			$queue[ $queue_k ] = $row;
		}

		if ( $this->conf( Base::O_CACHE_MOBILE ) ) {
			$sibling = array_merge( $row, [ 'vary' => self::device_sibling( $row['vary'] ), 'is_mobile' => ! $row['is_mobile'], 'reuse' => 0, 'sync' => 0 ] );
			// Its builds expired with this page's design: a full build, not a sync of its own.
			if ( 'sync' === ( $row['why'] ?? '' ) ) {
				$sibling['why'] = 'design';
			}
			$sibling_k = self::queue_key( $row['url_tag'], $sibling['vary'] );
			if ( ! isset( $queue[ $sibling_k ] ) && count( $queue ) < $max && 'design' === $this->unbuilt_cause( $row['url_tag'], $sibling['vary'] ) ) {
				$queue[ $sibling_k ] = $sibling;
				self::debug( 'Queued the other device too [vary] ' . $sibling['vary'] );
			}
		}

		return $queue_k;
	}

	/**
	 * Why a version without a build in use is queued: never built, every build expired (design changed), or its build lost files.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Vary, raw or stored.
	 * @return string `visit`, `design` or `missing` (CAUSES).
	 */
	public function unbuilt_cause( $url_tag, $vary ) {
		global $wpdb;

		$url_id = $this->url_id( $url_tag );
		$tb     = Data::cls()->tb( 'url_file' );
		$min    = $url_id ? $wpdb->get_var( $wpdb->prepare( "SELECT MIN(expired) FROM `$tb` WHERE url_id = %d AND vary = %s AND type = %d", $url_id, self::vary_key( $vary ), self::TYPE_HTML ) ) : null; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return null === $min ? 'visit' : ( (int) $min > 0 ? 'design' : 'missing' );
	}

	/**
	 * The `litespeed_url` row id of a page, or 0.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @return int
	 */
	public function url_id( $url_tag ) {
		global $wpdb;

		$tb = Data::cls()->tb( 'url' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$tb` WHERE url = %s", $url_tag ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Stop serving a page's builds, its design changed: load_url_file() skips them at once, clean_expired() deletes them later.
	 *
	 * @since 8.0
	 * @param int $url_id Page row id.
	 * @return void
	 */
	public function expire( $url_id ) {
		$n = $this->_expire_rows( [ self::TYPE_HTML, self::TYPE_SRC ], 'url_id = %d', [ (int) $url_id ] );
		if ( $n ) {
			self::debug( 'Expired OptimaX builds [url_id] ' . (int) $url_id . ' [rows] ' . $n );
		}
	}

	/**
	 * Stop serving one version's build, like expire(): its render changed, and a sync for it is queued.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return void
	 */
	public function expire_version( $url_tag, $vary ) {
		$url_id = $this->url_id( $url_tag );
		$n      = $url_id ? $this->_expire_rows( [ self::TYPE_HTML, self::TYPE_SRC ], 'url_id = %d AND vary = %s', [ $url_id, self::vary_key( $vary ) ] ) : 0;
		if ( $n ) {
			self::debug( 'Expired an OptimaX version for a sync [url_id] ' . $url_id . ' [rows] ' . $n );
		}
	}

	/**
	 * Start the grace period of the live rows of some file types, as Data::save_url() does for a replaced file.
	 *
	 * @since 8.0
	 * @param array  $types File type ids.
	 * @param string $where SQL condition on the url_file table; default: every row.
	 * @param array  $args  Values for its wpdb::prepare() placeholders; none: `$where` is used as it is.
	 * @return int Rows expired.
	 */
	private function _expire_rows( $types, $where = '1=1', $args = [] ) {
		global $wpdb;

		$tb    = Data::cls()->tb( 'url_file' );
		$ids   = implode( ',', $types );
		$until = (int) ( time() + 86400 * apply_filters( 'litespeed_url_file_expired_days', 20 ) );
		$where = $args ? $wpdb->prepare( $where, $args ) : $where; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return (int) $wpdb->query( "UPDATE `$tb` SET expired = $until WHERE expired = 0 AND type IN ($ids) AND $where" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Delete a page's builds whose grace period is over: Data::save_url() only rotates them out when it replaces a live one.
	 *
	 * @since 8.0
	 * @param int $url_id Page row id.
	 * @return void
	 */
	public function clean_expired( $url_id ) {
		$n = $this->_delete_builds( 'url_id = %d AND expired BETWEEN 1 AND %d', [ (int) $url_id, time() ] );
		if ( $n ) {
			self::debug( 'Deleted expired OptimaX builds [url_id] ' . (int) $url_id . ' [rows] ' . $n );
		}
	}

	/**
	 * Delete OptimaX build rows, and each file no remaining row needs: named by content md5, another page or version may share one.
	 *
	 * @since 8.0
	 * @param string $where SQL condition on the url_file table.
	 * @param array  $args  Values for its wpdb::prepare() placeholders.
	 * @return int Rows deleted.
	 */
	private function _delete_builds( $where, $args ) {
		global $wpdb;

		$tb_file     = Data::cls()->tb( 'url_file' );
		$tb_url      = Data::cls()->tb( 'url' );
		$ext_by_type = [ self::TYPE_HTML => 'html', self::TYPE_SRC => 'json' ];
		$where       = 'type IN (' . implode( ',', array_keys( $ext_by_type ) ) . ') AND ' . $wpdb->prepare( $where, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$rows = $wpdb->get_results( "SELECT filename, type, url_id, vary FROM `$tb_file` WHERE $where", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where is prepared above.
		if ( ! $rows ) {
			return 0;
		}
		$wpdb->query( "DELETE FROM `$tb_file` WHERE $where" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where is prepared above.

		// Each build's HTML or fingerprint file, and an HTML's asset manifest with the JS bundle and used CSS it names.
		$dir    = $this->_ox_dir();
		$q      = "SELECT COUNT(*) FROM `$tb_file` WHERE filename = %s AND type = %d";
		$assets = [];
		foreach ( $rows as $f ) {
			if ( $wpdb->get_var( $wpdb->prepare( $q, $f['filename'], (int) $f['type'] ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
				continue;
			}
			$ext = $ext_by_type[ $f['type'] ];
			self::_rm( $dir . $f['filename'] . '.' . $ext );
			if ( 'html' === $ext ) {
				$manifest = $this->load_assets( $f['filename'] );
				$assets   = array_merge( $assets, $manifest ? [ $manifest['js'], $manifest['css'] ] : [] );
				self::_rm( $dir . $f['filename'] . self::ASSETS_EXT );
			}
		}

		// Those JS bundles and used CSS go when no remaining build's manifest names them.
		$assets = array_unique( array_filter( $assets ) );
		$live   = $assets ? (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT filename FROM `$tb_file` WHERE type = %d", self::TYPE_HTML ) ) : []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $live as $filename ) {
			$manifest = $assets ? $this->load_assets( $filename ) : null;
			$assets   = $manifest ? array_diff( $assets, [ $manifest['js'], $manifest['css'] ] ) : $assets;
		}
		foreach ( $assets as $file ) {
			if ( self::_rm( $dir . $file ) ) {
				self::debug( 'Deleted unused OptimaX asset ' . $file );
			}
		}

		// A version whose last fingerprint row went loses its builder stylesheet copies: they belonged to that build.
		$q = "SELECT url FROM `$tb_url` u WHERE id = %d AND NOT EXISTS ( SELECT 1 FROM `$tb_file` WHERE url_id = u.id AND vary = %s AND type = %d )";
		foreach ( $rows as $f ) {
			$url_tag = 'json' === $ext_by_type[ $f['type'] ] ? $wpdb->get_var( $wpdb->prepare( $q, (int) $f['url_id'], $f['vary'], self::TYPE_SRC ) ) : ''; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			if ( $url_tag ) {
				$this->drop_sheet_copies( $url_tag, $f['vary'] );
			}
		}

		return count( $rows );
	}

	/**
	 * The post or page a URL shows, for its VPI post meta; 0 for any other page.
	 *
	 * @since 8.0
	 * @param string $url Page URL.
	 * @return int
	 */
	public static function post_id( $url ) {
		$post_id = url_to_postid( $url );
		if ( ! $post_id && 'page' === get_option( 'show_on_front' ) && untrailingslashit( $url ) === untrailingslashit( home_url() ) ) {
			$post_id = get_option( 'page_on_front' );
		}
		return (int) $post_id;
	}

	/**
	 * Drop a page's UCSS and CCSS: its design changed, so they no longer fit and no rebuild may send them back.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @return void
	 */
	public function clear_kept( $url_tag ) {
		$url_id = $this->url_id( $url_tag );
		if ( $url_id ) {
			$this->_expire_rows( [ self::TYPE_UCSS, self::TYPE_CCSS ], 'url_id = %d', [ $url_id ] );
		}
		// VPI stays (the post's own setting), but a version queued before the change must not send it back.
		$this->_stop_reuse( $url_tag );
		self::debug( 'Cleared the page UCSS/CCSS: design changed [url] ' . $url_tag );
	}

	/**
	 * Stop queued versions from sending their kept UCSS, CCSS and VPI (`reuse`).
	 *
	 * @since 8.0
	 * @param string|null $url_tag Page identity; null for every page.
	 * @return void
	 */
	private function _stop_reuse( $url_tag = null ) {
		if ( ! $this->load_queue( 'optimax' ) ) {
			return;
		}
		Optimax::cls()->update_queue( 'optimax', function ( &$queue ) use ( $url_tag ) {
			$changed = false;
			foreach ( $queue as $k => $row ) {
				if ( ! empty( $row['reuse'] ) && ( null === $url_tag || ( $row['url_tag'] ?? null ) === $url_tag ) ) {
					$queue[ $k ]['reuse'] = 0;
					$changed              = true;
				}
			}
			return $changed;
		} );
	}

	/**
	 * Treat every page as redesigned: a next-gen format switch or destroying the optimized images changed the files every build links.
	 *
	 * @since 8.0
	 * @param string $why What changed, for the log and the purge.
	 * @return void
	 */
	public function all_designs_changed( $why ) {
		$tb = Data::cls()->tb( 'url_file' );
		$ox = self::TYPE_HTML . ',' . self::TYPE_SRC;

		// The UCSS/CCSS of the pages OptimaX built, known by their build rows. Derived table: MySQL can't select from the table it updates.
		$n_css = $this->_expire_rows( [ self::TYPE_UCSS, self::TYPE_CCSS ], "url_id IN ( SELECT url_id FROM ( SELECT DISTINCT url_id FROM `$tb` WHERE type IN ($ox) ) AS p )" );
		$n     = $this->_expire_rows( [ self::TYPE_HTML, self::TYPE_SRC ] );
		$this->_stop_reuse();
		self::debug( 'All pages redesigned: ' . $why . ' [build rows] ' . $n . ' [css rows] ' . $n_css );

		// Cached pages still carry the old OptimaX HTML.
		if ( $n ) {
			Purge::purge_all( 'OptimaX: ' . $why );
		}
	}

	/**
	 * Delete everything OptimaX made: builds (HTML, JS bundle, used CSS copy, asset manifest) and fingerprints.
	 *
	 * @since 8.0
	 * @return void
	 */
	public function purge_builds() {
		// Every site's builds only for a network admin; anyone else purges this site's.
		$network = is_multisite() && is_network_admin() && current_user_can( 'manage_network_options' );
		$dir     = LITESPEED_STATIC_DIR . '/optimax/' . ( is_multisite() && ! $network ? get_current_blog_id() : '' );

		$n = 0;
		if ( is_dir( $dir ) ) {
			foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {
				if ( preg_match( '/\.(html|js|css|json)$/', $file->getFilename() ) ) {
					wp_delete_file( $file->getPathname() );
					++$n;
				}
			}
		}

		// From network admin every site's files went: clean every site's rows.
		foreach ( $network ? get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) : [ 0 ] as $blog_id ) {
			if ( $blog_id ) {
				switch_to_blog( $blog_id );
			}
			Data::cls()->url_file_clean( 'optimax' );
			Data::cls()->url_file_clean( 'optimax_src' );
			// Fingerprints and snapshots are gone, so no check can go on.
			self::update_option( 'checks', [] );
			if ( $blog_id ) {
				restore_current_blog();
			}
		}
		self::debug( 'Purged all OptimaX builds and fingerprints [files] ' . $n );
	}

	/**
	 * Save a build's asset manifest beside its HTML: the files the HTML depends on, and how the build was made.
	 *
	 * @since 8.0
	 * @param string $html_md5 Build HTML filename, without extension.
	 * @param array  $assets   `js` and `css` file names (or ''), `imgs` local paths; when known `action`, `cause` (ACTIONS, CAUSES) and `detail`.
	 * @return bool
	 */
	public function save_assets( $html_md5, $assets ) {
		$base = self::_uploads_base();
		$imgs = [];
		foreach ( (array) $assets['imgs'] as $img ) {
			// Relative to this site's uploads, so the public .json holds no server path.
			$img    = wp_normalize_path( (string) $img );
			$imgs[] = '/' !== $base && 0 === strpos( $img, $base ) ? substr( $img, strlen( $base ) ) : $img;
		}
		$manifest = [
			'js'   => (string) $assets['js'],
			'css'  => (string) $assets['css'],
			'imgs' => array_values( array_unique( $imgs ) ),
			'at'   => time(),
		] + array_filter(
			[
				'action' => in_array( $assets['action'] ?? '', self::ACTIONS, true ) ? $assets['action'] : '',
				'cause'  => in_array( $assets['cause'] ?? '', self::CAUSES, true ) ? $assets['cause'] : '',
				'detail' => is_string( $assets['detail'] ?? 0 ) ? substr( $assets['detail'], 0, 200 ) : '',
			]
		);

		$file  = $this->_ox_dir() . $html_md5 . self::ASSETS_EXT;
		$saved = self::_write_json( $file, $manifest );
		if ( ! $saved ) {
			self::debug( '❌ Failed to save asset manifest [file] ' . $file );
		}
		return $saved;
	}

	/**
	 * A build's asset manifest.
	 *
	 * @since 8.0
	 * @param string $html_md5 Build HTML filename, without extension.
	 * @return array|null `js`, `css`, `imgs` and, when known, `action`, `cause`, `detail` and `at`; null when it is missing or broken.
	 */
	public function load_assets( $html_md5 ) {
		$manifest = self::_read_json( $this->_ox_dir() . $html_md5 . self::ASSETS_EXT );
		// The public .json may have been edited: only our own file names, no path leaving its folder.
		if ( ! isset( $manifest['js'], $manifest['css'], $manifest['imgs'] ) || ! is_array( $manifest['imgs'] ) || ! is_string( $manifest['js'] ) || ! is_string( $manifest['css'] )
			|| ! preg_match( '/^([a-f0-9]{32}\.js)?$/', $manifest['js'] ) || ! preg_match( '/^([a-f0-9]{32}\.css)?$/', $manifest['css'] ) ) {
			return null;
		}

		// Relative to this site's uploads, unless saved absolute (outside uploads).
		$base = self::_uploads_base();
		foreach ( $manifest['imgs'] as $k => $img ) {
			if ( ! is_string( $img ) || preg_match( '#(^|[\\\\/])\.\.([\\\\/]|$)#', $img ) ) {
				unset( $manifest['imgs'][ $k ] );
			} elseif ( ! path_is_absolute( $img ) ) {
				$manifest['imgs'][ $k ] = $base . $img;
			}
		}
		return $manifest;
	}

	/**
	 * This site's uploads folder, resolved, with a trailing slash.
	 *
	 * @since 8.0
	 * @return string
	 */
	private static function _uploads_base() {
		$uploads = wp_upload_dir( null, false );
		$dir     = ! empty( $uploads['basedir'] ) ? $uploads['basedir'] : '';
		$real    = '' !== $dir ? realpath( $dir ) : false;
		return trailingslashit( wp_normalize_path( $real ? $real : $dir ) );
	}

	/**
	 * Versions whose builds all expired (the design changed) and the Next-Gen Image Format setting serves: newest build row of each.
	 *
	 * @since 8.0
	 * @return array Rows with `url_id`, `vary`, `file_id`.
	 */
	private function _refresh_rows() {
		global $wpdb;

		$tb   = Data::cls()->tb( 'url_file' );
		$q    = "SELECT url_id, vary, MAX(id) AS file_id, MAX(webp) AS webp FROM `$tb` WHERE type = %d GROUP BY url_id, vary HAVING MIN(expired) > 0";
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $q, self::TYPE_HTML ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return array_filter( $rows, function ( $row ) {
			return self::is_served_version( $row['vary'], $row['webp'] );
		} );
	}

	/**
	 * Counts for the Summary tab.
	 *
	 * @since 8.0
	 * @return array `pages` with an OptimaX build in use, `versions` in use, `refresh` versions waiting for a rebuild.
	 */
	public function stats() {
		global $wpdb;

		$tb   = Data::cls()->tb( 'url_file' );
		$q    = "SELECT COUNT(DISTINCT url_id) AS pages, COUNT(*) AS versions FROM `$tb` WHERE type = %d AND expired = 0";
		$live = $wpdb->get_row( $wpdb->prepare( $q, self::TYPE_HTML ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return [ 'pages' => (int) ( $live['pages'] ?? 0 ), 'versions' => (int) ( $live['versions'] ?? 0 ), 'refresh' => count( $this->_refresh_rows() ) ];
	}

	/**
	 * Pages with an OptimaX build in use, each with its versions.
	 *
	 * @since 8.0
	 * @param int $limit Pages to return.
	 * @return array Page URL => list of version groups (`mobile`, `nextgen`), each with its `key` (check_key()), `action`, `cause`, `detail` and `at`.
	 */
	public function optimized( $limit ) {
		global $wpdb;

		// Derived table: MySQL can't use LIMIT in an IN subquery.
		$tb_url  = Data::cls()->tb( 'url' );
		$tb_file = Data::cls()->tb( 'url_file' );
		$q       = "SELECT u.url, f.vary, f.mobile, f.webp, f.filename FROM `$tb_file` f JOIN `$tb_url` u ON u.id = f.url_id
			WHERE f.type = %d AND f.expired = 0 AND f.url_id IN ( SELECT url_id FROM ( SELECT DISTINCT url_id FROM `$tb_file` WHERE type = %d AND expired = 0 ORDER BY url_id LIMIT %d ) AS p )
			ORDER BY u.url, f.vary";
		$rows    = $wpdb->get_results( $wpdb->prepare( $q, self::TYPE_HTML, self::TYPE_HTML, (int) $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		$pages = [];
		foreach ( (array) $rows as $r ) {
			$manifest = $this->load_assets( $r['filename'] );

			$pages[ $r['url'] ][] = self::version_groups( $r['vary'], $r['mobile'], $r['webp'] ) + [
				'key'    => self::check_key( $r['url'], $r['vary'] ),
				'action' => in_array( $manifest['action'] ?? '', self::ACTIONS, true ) ? $manifest['action'] : 'generated',
				'cause'  => empty( $manifest['cause'] ) ? '' : $manifest['cause'],
				'detail' => empty( $manifest['detail'] ) ? '' : $manifest['detail'],
				'at'     => (int) ( $manifest['at'] ?? 0 ),
			];
		}
		return $pages;
	}

	/**
	 * Queue every version whose design changed for a rebuild now, instead of on its next visit.
	 *
	 * @since 8.0
	 * @return int Versions queued.
	 */
	public function rebuild_all() {
		return count( array_filter( array_map( [ $this, 'queue_version' ], array_column( $this->_refresh_rows(), 'file_id' ) ) ) );
	}

	/**
	 * Queue a version again from its build row. No user agent is sent: a build row keeps none.
	 *
	 * @since 8.0
	 * @param int $file_id An `optimax` build row id.
	 * @return string|false The queue key.
	 */
	public function queue_version( $file_id ) {
		global $wpdb;

		$tb_url  = Data::cls()->tb( 'url' );
		$tb_file = Data::cls()->tb( 'url_file' );
		$q       = "SELECT f.vary, f.mobile, f.webp, u.url FROM `$tb_file` f JOIN `$tb_url` u ON u.id = f.url_id WHERE f.id = %d AND f.type = %d";
		$row     = $wpdb->get_row( $wpdb->prepare( $q, (int) $file_id, self::TYPE_HTML ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) {
			return false;
		}

		// The stored vary is already in its 32-char-at-most form, so this is the key serve() would build.
		$version = [
			'url'        => apply_filters( 'litespeed_optimax_url', $row['url'] ),
			'is_mobile'  => (bool) $row['mobile'],
			'is_nextgen' => $row['webp'] ? $this->cls( 'Media' )->webp_support( true ) : '',
			'vary'       => $row['vary'],
			'url_tag'    => $row['url'],
			'why'        => 'bulk',
		];
		$queue_k = false;
		Optimax::cls()->update_queue( 'optimax', function ( &$queue ) use ( $version, &$queue_k ) {
			$queue_k = $this->add_versions( $queue, $version, Optimax::cls()->max_queue_size() );
		} );
		self::debug( 'Re-queued from build row [file_id] ' . (int) $file_id );

		return $queue_k;
	}

	/**
	 * Whether a queue row is still waiting, i.e. not yet handed to QUIC.cloud.
	 *
	 * @since 8.0
	 * @param mixed $row Queue row.
	 * @return bool
	 */
	public static function is_waiting( $row ) {
		return ! is_array( $row ) || 'queued' !== ( $row['_status'] ?? '' );
	}

	/**
	 * Drop the waiting queue rows; one QUIC.cloud is already building stays, or its result would never be collected.
	 *
	 * @since 8.0
	 * @return int Rows kept.
	 */
	public function clear_waiting() {
		$queue = Optimax::cls()->update_queue( 'optimax', function ( &$queue ) {
			$queue = array_diff_key( $queue, array_filter( $queue, [ __CLASS__, 'is_waiting' ] ) );
		} );
		return count( $queue );
	}

	/**
	 * The fingerprint stored beside a version's live build.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Vary, raw or stored.
	 * @return array|null `design`, `lines`, `items`, `patches`, `built`, `pending`, `base`; null when there is none.
	 */
	public function load_fingerprint( $url_tag, $vary ) {
		$filename = Data::cls()->load_url_file( $url_tag, $vary, 'optimax_src' );
		$fp       = $filename ? self::_read_json( $this->_ox_dir() . $filename . '.json' ) : null;
		if ( ! isset( $fp['v'], $fp['design'], $fp['lines'], $fp['items'] ) || Optimax_Sync::VERSION !== (int) $fp['v'] || ! is_array( $fp['lines'] ) || ! is_array( $fp['items'] ) ) {
			return null;
		}
		return $fp + [ 'patches' => 0, 'built' => 0, 'pending' => '', 'base' => '' ];
	}

	/**
	 * Store the fingerprint of the render a version's live build matches.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param array  $fp      Optimax_Sync::fingerprint().
	 * @param int    $patches Times the build had its text updated.
	 * @param int    $built   When the build was first served (0: now). Patches keep it, so the Rebuild Interval counts from the build.
	 * @return bool
	 */
	public function save_fingerprint( $url_tag, $vary, $fp, $patches, $built = 0 ) {
		// The page's URL and vary are part of the content, so two versions never share a file.
		$con = wp_json_encode(
			[
				'v'       => Optimax_Sync::VERSION,
				'url_tag' => $url_tag,
				'vary'    => $vary,
				'design'  => $fp['design'],
				'lines'   => $fp['lines'],
				'items'   => $fp['items'],
				'patches' => (int) $patches,
				'built'   => $built ? (int) $built : time(),
				'pending' => empty( $fp['pending'] ) ? '' : (string) $fp['pending'],
				// The render itself (pack_base()), for a sync on QUIC.cloud: a fingerprint without it compares, but never syncs.
				'base'    => empty( $fp['base'] ) ? '' : (string) $fp['base'],
			]
		);

		$filecon_md5 = md5( (string) $con );
		$static_file = $this->_ox_dir() . $filecon_md5 . '.json';
		if ( ! is_string( $con ) || ! File::save_atomic( $static_file, $con ) ) {
			self::debug( '❌ Failed to save fingerprint [file] ' . $static_file );
			return false;
		}

		$groups = self::version_groups( $vary );
		Data::cls()->save_url( $url_tag, $vary, 'optimax_src', $filecon_md5, dirname( $static_file ), $groups['mobile'], $groups['nextgen'] );

		// Nothing reads a replaced fingerprint: delete it now, not after the grace period.
		$url_id = $this->url_id( $url_tag );
		if ( $url_id ) {
			$this->_delete_builds( 'url_id = %d AND vary = %s AND type = %d AND expired > 0', [ $url_id, self::vary_key( $vary ), self::TYPE_SRC ] );
		}
		return true;
	}

	/**
	 * Drop a version's fingerprint, and the stylesheet copies of the build it matched: a new build matches a render not seen yet.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return void
	 */
	public function drop_fingerprint( $url_tag, $vary ) {
		$url_id = $this->url_id( $url_tag );
		if ( $url_id ) {
			$this->_delete_builds( 'url_id = %d AND vary = %s AND type = %d', [ $url_id, self::vary_key( $vary ), self::TYPE_SRC ] );
		}
		$this->drop_sheet_copies( $url_tag, $vary );
	}

	/**
	 * Keep the builder stylesheets a version's live build was cut from (Optimax_Sync::same_sheets()), each with its md5.
	 *
	 * Beside the fingerprint, not in it: a patch writes the fingerprint again, while the build's CSS stays the one it was cut from.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param array  $sheets  URL path => pack_base() of the CSS. Empty drops the copies.
	 * @return void
	 */
	public function save_sheet_copies( $url_tag, $vary, $sheets ) {
		$md5  = array_map( 'md5', array_filter( array_map( [ __CLASS__, 'unpack_sheet' ], $sheets ), 'is_string' ) );
		$kept = [ 'url_tag' => $url_tag, 'vary' => $vary, 'sheets' => array_intersect_key( $sheets, $md5 ), 'md5' => $md5 ];
		// Without copies every builder stylesheet change counts.
		if ( ! $md5 || ! self::_write_json( $this->_side_file( 'sheets', $url_tag, $vary ), $kept ) ) {
			$this->drop_sheet_copies( $url_tag, $vary );
		}
	}

	/**
	 * The builder stylesheets a version's live build was cut from, and the verdicts on them remembered for that build.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $build   The build's HTML md5: verdicts of another build are left out.
	 * @return array `md5`: URL path => md5 of the copy; `packed`: URL path => pack_base() of the copy; `known`: Optimax_Sync::same_sheets() verdicts.
	 */
	public function load_sheet_copies( $url_tag, $vary, $build ) {
		$kept = self::_read_json( $this->_side_file( 'sheets', $url_tag, $vary ) );
		$out  = [ 'md5' => [], 'packed' => [], 'known' => [] ];
		if ( ! isset( $kept['sheets'], $kept['md5'] ) || ! is_array( $kept['sheets'] ) || ! is_array( $kept['md5'] ) ) {
			return $out;
		}

		foreach ( $kept['md5'] as $path => $md5 ) {
			if ( is_string( $md5 ) && preg_match( '/^[a-f0-9]{32}$/', $md5 ) && is_string( $kept['sheets'][ $path ] ?? 0 ) ) {
				$out['md5'][ $path ]    = $md5;
				$out['packed'][ $path ] = $kept['sheets'][ $path ];
			}
		}
		if ( isset( $kept['verdicts']['build'], $kept['verdicts']['known'] ) && $build === $kept['verdicts']['build'] && is_array( $kept['verdicts']['known'] ) ) {
			$out['known'] = array_filter( $kept['verdicts']['known'], 'is_bool' );
		}
		return $out;
	}

	/**
	 * One kept builder stylesheet copy, read back.
	 *
	 * @since 8.0
	 * @param string $packed pack_base() of the copy.
	 * @return string|null Null when it cannot be read, or is over the copy limit.
	 */
	public static function unpack_sheet( $packed ) {
		$css = self::unpack_base( $packed, Optimax_Sync::SHEET_COPY_MAX );
		return '' === $css ? null : $css;
	}

	/**
	 * Remember the newest 16 verdicts on a version's builder stylesheets, for one build, so a repeat render does not parse them again.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $build   The build's HTML md5.
	 * @param array  $known   Verdicts, `path|copy md5|file md5` => bool.
	 * @return void
	 */
	public function save_sheet_verdicts( $url_tag, $vary, $build, $known ) {
		$file = $this->_side_file( 'sheets', $url_tag, $vary );
		$kept = self::_read_json( $file );
		if ( ! empty( $kept['sheets'] ) ) {
			$kept['verdicts'] = [ 'build' => (string) $build, 'known' => array_slice( $known, -16, null, true ) ];
			self::_write_json( $file, $kept );
		}
	}

	/**
	 * Forget the builder stylesheets a version's build was cut from.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return void
	 */
	public function drop_sheet_copies( $url_tag, $vary ) {
		self::_rm( $this->_side_file( 'sheets', $url_tag, $vary ) );
	}

	/**
	 * A render as a fingerprint keeps it: compressed, base64.
	 *
	 * @since 8.0
	 * @param string $html Render.
	 * @return string Empty when it cannot be packed.
	 */
	public static function pack_base( $html ) {
		$packed = function_exists( 'gzdeflate' ) ? gzdeflate( (string) $html, 6 ) : false;
		return false === $packed ? '' : base64_encode( $packed ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * A render pack_base() kept.
	 *
	 * @since 8.0
	 * @param string $packed pack_base() output.
	 * @param int    $max    Most bytes it may inflate to; 0: no limit.
	 * @return string Empty when there is none, it cannot be read, or it is over `$max`.
	 */
	public static function unpack_base( $packed, $max = 0 ) {
		$raw  = '' === (string) $packed ? false : base64_decode( (string) $packed, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$html = false === $raw || ! function_exists( 'gzinflate' ) ? false : @gzinflate( $raw, $max ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- warns when over $max.
		return false === $html ? '' : $html;
	}

	/**
	 * A render as a side file keeps it: packed, with its fingerprint (tagged with its format) and its builder stylesheets.
	 *
	 * Taken in the request that rendered it: a stylesheet counts by its file's content, which may change before the result comes back.
	 *
	 * @since 8.0
	 * @param string $key    Key the packed render goes under.
	 * @param string $render The render.
	 * @return array `$key`, `fp` (null when the render cannot be read) and `css` (URL path => pack_base() of the CSS).
	 */
	private static function _pack_render( $key, $render ) {
		$has = '' !== (string) $render;
		$fp  = $has ? Optimax_Sync::fingerprint( $render ) : null;
		return [
			$key  => self::pack_base( $render ),
			'fp'  => $fp ? array_merge( $fp, [ 'v' => Optimax_Sync::VERSION ] ) : null,
			'css' => array_filter( array_map( [ __CLASS__, 'pack_base' ], $has ? Optimax_Sync::sheet_copies( $render ) : [] ) ),
		];
	}

	/**
	 * A render _pack_render() kept. Its fingerprint and stylesheets count only in the current format.
	 *
	 * @since 8.0
	 * @param array|null $kept The stored record.
	 * @param string     $key  Key the packed render is under.
	 * @return array `render` (empty when there is none), `fp` (null when not kept) and `css` (URL path => pack_base() of the CSS).
	 */
	private static function _unpack_render( $kept, $key ) {
		$fp = $kept['fp'] ?? null;
		$ok = is_array( $fp ) && isset( $fp['v'], $fp['design'], $fp['lines'], $fp['items'] ) && Optimax_Sync::VERSION === (int) $fp['v'];
		return [
			'render' => isset( $kept[ $key ] ) ? self::unpack_base( $kept[ $key ] ) : '',
			'fp'     => $ok ? array_diff_key( $fp, [ 'v' => 0 ] ) : null,
			'css'    => $ok && isset( $kept['css'] ) && is_array( $kept['css'] ) ? array_filter( $kept['css'], 'is_string' ) : [],
		];
	}

	/**
	 * Keep what a sync of one version needs until its result is stored.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $base    pack_base() of the build's render.
	 * @param string $now     The render that showed the change.
	 * @param string $file    The build's filename (its HTML without `.html`); expire_version() stops serving it, clean_expired() keeps it.
	 * @param array  $same    Optimax_Sync::same_sheets() of the render: a synced result reads those sheets as its copies.
	 * @return bool
	 */
	public function save_sync( $url_tag, $vary, $base, $now, $file, $same ) {
		$snap = [ 'url_tag' => $url_tag, 'vary' => $vary, 'base' => (string) $base ] + self::_pack_render( 'now', $now ) + [ 'same' => $same, 'file' => (string) $file ];
		return self::_write_json( $this->_side_file( 'sync', $url_tag, $vary ), $snap );
	}

	/**
	 * What a queued sync of one version sends: `base`, `now` and the build's `html`.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return array|null Null when any part is gone: the version is then built as usual.
	 */
	public function load_sync( $url_tag, $vary ) {
		$snap = $this->_sync( $url_tag, $vary );
		if ( '' === $snap['file'] ) {
			return null;
		}
		$sync = [
			'base' => self::unpack_base( $snap['base'] ?? '' ),
			'now'  => self::unpack_base( $snap['now'] ?? '' ),
			'html' => (string) File::read( $this->_ox_dir() . $snap['file'] . '.html' ),
		];
		return in_array( '', $sync, true ) ? null : $sync;
	}

	/**
	 * The build a queued sync of one version was taken from: a synced result takes over its asset manifest.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return string The build's filename; empty when there is no snapshot.
	 */
	public function sync_source( $url_tag, $vary ) {
		return $this->_sync( $url_tag, $vary )['file'];
	}

	/**
	 * Forget one version's sync: a result was stored, or it is no longer wanted.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return void
	 */
	public function drop_sync( $url_tag, $vary ) {
		self::_rm( $this->_side_file( 'sync', $url_tag, $vary ) );
	}


	/**
	 * One version's sync snapshot, its `file` '' unless it names a build.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return array Only `file` when there is none or it is broken.
	 */
	private function _sync( $url_tag, $vary ) {
		$snap = (array) self::_read_json( $this->_side_file( 'sync', $url_tag, $vary ) );
		$file = $snap['file'] ?? '';
		return [ 'file' => is_string( $file ) && preg_match( '/^[a-f0-9]{32}$/', $file ) ? $file : '' ] + $snap;
	}

	/**
	 * Keep the render a version's build was requested with, until its result is stored. A later request replaces it.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $render  The request's render.
	 * @return bool
	 */
	public function save_queued_render( $url_tag, $vary, $render ) {
		return self::_write_json( $this->_side_file( 'queued', $url_tag, $vary ), [ 'url_tag' => $url_tag, 'vary' => $vary ] + self::_pack_render( 'render', $render ) );
	}

	/**
	 * The render a new build is compared with, its fingerprint and builder stylesheets: the one that showed a queued sync's change, else the one the build was requested with (forgotten once read).
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return array [ `render` (empty when there is none), `fp` (null when not kept), `css` and `same` (save_sync(), sync only) ; whether it is the sync's ].
	 */
	public function kept_render( $url_tag, $vary ) {
		$file   = $this->_side_file( 'queued', $url_tag, $vary );
		$kept   = self::_read_json( $file );
		$queued = self::_unpack_render( $kept, 'render' );
		self::_rm( $file );

		$snap = $this->_sync( $url_tag, $vary );
		$sync = self::_unpack_render( $snap, 'now' );
		if ( '' === $sync['render'] ) {
			return [ $queued, false ];
		}
		$same = $sync['fp'] && isset( $snap['same'] ) && is_array( $snap['same'] ) ? $snap['same'] : [];

		$sync['same'] = array_filter( $same, function ( $e ) {
			return is_string( $e['from'] ?? 0 ) && is_string( $e['to'] ?? 0 ) && preg_match( '/^[a-f0-9]{32}$/', $e['from'] ) && preg_match( '/^[a-f0-9]{32}$/', $e['to'] );
		} );
		return [ $sync, true ];
	}

	/**
	 * Where one version's side file lives. A `.json` beside the builds, so Purge All OptimaX takes it with them.
	 *
	 * @since 8.0
	 * @param string $prefix  `queued` render, `sync` snapshot or builder `sheets` copies.
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return string
	 */
	private function _side_file( $prefix, $url_tag, $vary ) {
		return $this->_ox_dir() . $prefix . '-' . self::check_key( $url_tag, $vary ) . '.json';
	}

	/**
	 * This site's OptimaX folder, with a trailing slash.
	 *
	 * @since 8.0
	 * @return string
	 */
	private function _ox_dir() {
		return LITESPEED_STATIC_DIR . $this->_build_filepath_prefix( 'optimax' );
	}

	/**
	 * A JSON file beside the builds, decoded.
	 *
	 * @since 8.0
	 * @param string $file Path.
	 * @return array|null Null when it is missing or holds no array.
	 */
	private static function _read_json( $file ) {
		$data = json_decode( (string) File::read( $file ), true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Save data as a JSON file beside the builds.
	 *
	 * @since 8.0
	 * @param string $file Path.
	 * @param mixed  $data Data.
	 * @return bool
	 */
	private static function _write_json( $file, $data ) {
		$con = wp_json_encode( $data );
		return is_string( $con ) && File::save_atomic( $file, $con );
	}

	/**
	 * Delete a file, if it is there.
	 *
	 * @since 8.0
	 * @param string $file Path.
	 * @return bool Whether it was there.
	 */
	private static function _rm( $file ) {
		$exists = file_exists( $file );
		if ( $exists ) {
			wp_delete_file( $file );
		}
		return $exists;
	}

	/**
	 * Record where one version's page-change check stands, for the OptimaX page.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $stage   `confirming` (the next render confirms the change), `sync` (sent to QUIC.cloud) or `rebuild` (a full build queued).
	 * @param string $what    What changed, for the admin page (`design: ...`, `content: ...`).
	 * @return void
	 */
	public function note_check( $url_tag, $vary, $stage, $what = '' ) {
		$checks = $this->_load_checks();
		$key    = self::check_key( $url_tag, $vary );
		$now    = time();
		$groups = self::version_groups( $vary );
		// A check already finished starts over: this is a new change.
		$open = isset( $checks[ $key ] ) && empty( $checks[ $key ]['done'] ) ? $checks[ $key ] : [];

		$checks[ $key ] = [
			'url'     => $url_tag,
			'mobile'  => (int) $groups['mobile'],
			'nextgen' => (int) $groups['nextgen'],
			'stage'   => $stage,
			'what'    => '' !== $what ? substr( $what, 0, 200 ) : ( $open['what'] ?? '' ),
			'since'   => empty( $open['since'] ) ? $now : (int) $open['since'],
			'at'      => $now,
		];
		$this->_save_checks( $checks );
	}

	/**
	 * End one version's page-change check, if it has one.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $outcome An ACTIONS value, listed for CHECK_KEEP; empty when the change was a one-off, which forgets the check.
	 * @return void
	 */
	public function end_check( $url_tag, $vary, $outcome = '' ) {
		$checks = $this->_load_checks();
		$key    = self::check_key( $url_tag, $vary );
		if ( ! isset( $checks[ $key ] ) || ! empty( $checks[ $key ]['done'] ) ) {
			return;
		}

		if ( '' === $outcome ) {
			unset( $checks[ $key ] );
		} else {
			$checks[ $key ] = array_merge( $checks[ $key ], [ 'stage' => $outcome, 'done' => 1, 'at' => time() ] );
		}
		$this->_save_checks( $checks );
	}

	/**
	 * Page-change checks under way, and those finished in the last CHECK_KEEP, newest first.
	 *
	 * @since 8.0
	 * @return array check_key() => `url`, `mobile`, `nextgen`, `stage`, `what`, `since`, `at` and, once finished, `done`.
	 */
	public function checks() {
		$checks = $this->_load_checks();
		uasort( $checks, function ( $a, $b ) {
			return $b['at'] - $a['at'];
		} );
		return $checks;
	}

	/**
	 * What changed, as one version's open check recorded it ('' when none).
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return string
	 */
	public function check_what( $url_tag, $vary ) {
		$check = $this->_load_checks()[ self::check_key( $url_tag, $vary ) ] ?? [];
		return empty( $check['done'] ) ? (string) ( $check['what'] ?? '' ) : '';
	}

	/**
	 * Which version a check belongs to.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity.
	 * @param string $vary    Vary, raw or stored.
	 * @return string
	 */
	public static function check_key( $url_tag, $vary ) {
		return md5( self::queue_key( $url_tag, $vary ) );
	}

	/**
	 * The stored checks, without those finished more than CHECK_KEEP ago.
	 *
	 * @return array
	 */
	private function _load_checks() {
		$cutoff = time() - self::CHECK_KEEP;
		return array_filter( (array) self::get_option( 'checks', [] ), function ( $check ) use ( $cutoff ) {
			return is_array( $check ) && isset( $check['url'], $check['stage'], $check['at'] ) && ( empty( $check['done'] ) || $check['at'] > $cutoff );
		} );
	}

	/**
	 * Store the checks, the newest CHECK_MAX of them.
	 *
	 * @param array $checks check_key() => check.
	 * @return void
	 */
	private function _save_checks( $checks ) {
		if ( count( $checks ) > self::CHECK_MAX ) {
			uasort( $checks, function ( $a, $b ) {
				return $b['at'] - $a['at'];
			} );
			$checks = array_slice( $checks, 0, self::CHECK_MAX, true );
		}
		self::update_option( 'checks', $checks );
	}
}
