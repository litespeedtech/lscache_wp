<?php
/**
 * LiteSpeed persistent data manager.
 *
 * Handles DB tables, schema upgrades, URL-to-file mappings, and list loaders.
 *
 * @package LiteSpeed
 * @since   1.3.1
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Data layer for LiteSpeed Cache.
 */
class Data extends Root {

	const LOG_TAG = '🚀';

	/**
	 * Versioned DB updaters for network-wide options.
	 *
	 * @var array<string,array<string>>
	 */
	private $_db_updater = [
		'5.3-a5'    => [ 'litespeed_update_5_3' ],
		'7.0-b26'   => [ 'litespeed_update_7' ],
		'7.0.1-b1'  => [ 'litespeed_update_7_0_1' ],
		'7.7-b28'   => [ 'litespeed_update_7_7' ],
	];

	/**
	 * Versioned DB updaters for per-site options in multisite.
	 *
	 * @var array<string,array<string>>
	 */
	private $_db_site_updater = [
		// '2.0' => [ 'litespeed_update_site_2_0' ],
	];

	/**
	 * Map from URL-file type to integer code.
	 *
	 * @var array<string,int>
	 */
	private $_url_file_types = [
		'css'  => 1,
		'js'   => 2,
		'ccss' => 3,
		'ucss' => 4,
	];

	/** Table: image optimization results. */
	const TB_IMG_OPTM = 'litespeed_img_optm';
	/** Table: image optimization working queue. */
	const TB_IMG_OPTMING = 'litespeed_img_optming';
	/** Table: cached avatars. */
	const TB_AVATAR = 'litespeed_avatar';
	/** Table: crawler URLs. */
	const TB_CRAWLER = 'litespeed_crawler';
	/** Table: crawler blacklist. */
	const TB_CRAWLER_BLACKLIST = 'litespeed_crawler_blacklist';
	/** Table: logical URLs. */
	const TB_URL = 'litespeed_url';
	/** Table: URL → generated file mapping. */
	const TB_URL_FILE = 'litespeed_url_file';

	/**
	 * Constructor.
	 *
	 * @since 1.3.1
	 */
	public function __construct() {}

	/**
	 * Ensure required tables exist based on current configuration.
	 *
	 * Called on activation and when options are (re)loaded.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public function correct_tb_existence() {
		// Gravatar.
		if ( $this->conf( Base::O_DISCUSS_AVATAR_CACHE ) ) {
			$this->tb_create( 'avatar' );
		}

		// Crawler.
		if ( $this->conf( Base::O_CRAWLER ) ) {
			$this->tb_create( 'crawler' );
			$this->tb_create( 'crawler_blacklist' );
		}

		// URL mapping.
		$this->tb_create( 'url' );
		$this->tb_create( 'url_file' );

		// Image optm tables are managed on-demand.
	}

	/**
	 * Upgrade global configuration/data to match plugin version.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $ver Currently stored version string.
	 * @return string|void 'upgrade' on success, or void if no-op.
	 */
	public function conf_upgrade( $ver ) {
		// Skip count check if `Use Primary Site Configurations` is on (deprecated note kept intentionally).

		if ( $this->_get_upgrade_lock() ) {
			return;
		}

		$this->_set_upgrade_lock( true );

		require_once LSCWP_DIR . 'src/data.upgrade.func.php';

		// Init log manually.
		if ( $this->conf( Base::O_DEBUG ) ) {
			$this->cls( 'Debug2' )->init();
		}

		foreach ( $this->_db_updater as $k => $v ) {
			if ( version_compare( $ver, $k, '<' ) ) {
				foreach ( $v as $v2 ) {
					self::debug( "Updating [ori_v] $ver \t[to] $k \t[func] $v2" );
					call_user_func( $v2 );
				}
			}
		}

		// Reload options.
		$this->cls( 'Conf' )->load_options();

		$this->correct_tb_existence();

		// Update related files.
		$this->cls( 'Activation' )->update_files();

		// Update version to latest.
		Conf::delete_option( Base::_VER );
		Conf::add_option( Base::_VER, Core::VER );

		self::debug( 'Updated version to ' . Core::VER );

		$this->_set_upgrade_lock( false );

		if ( ! defined( 'LSWCP_EMPTYCACHE' ) ) {
			define( 'LSWCP_EMPTYCACHE', true );
		}
		Purge::purge_all();

		return 'upgrade';
	}

	/**
	 * Upgrade per-site configuration/data to match plugin version (multisite).
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $ver Currently stored version string.
	 * @return void
	 */
	public function conf_site_upgrade( $ver ) {
		if ( $this->_get_upgrade_lock() ) {
			return;
		}

		$this->_set_upgrade_lock( true );

		require_once LSCWP_DIR . 'src/data.upgrade.func.php';

		foreach ( $this->_db_site_updater as $k => $v ) {
			if ( version_compare( $ver, $k, '<' ) ) {
				foreach ( $v as $v2 ) {
					self::debug( "Updating site [ori_v] $ver \t[to] $k \t[func] $v2" );
					call_user_func( $v2 );
				}
			}
		}

		// Reload options.
		$this->cls( 'Conf' )->load_site_options();

		Conf::delete_site_option( Base::_VER );
		Conf::add_site_option( Base::_VER, Core::VER );

		self::debug( 'Updated site_version to ' . Core::VER );

		$this->_set_upgrade_lock( false );

		if ( ! defined( 'LSWCP_EMPTYCACHE' ) ) {
			define( 'LSWCP_EMPTYCACHE', true );
		}
		Purge::purge_all();
	}

	/**
	 * Whether an upgrade lock is in effect.
	 *
	 * @since 3.0.1
	 * @return int|false Timestamp if locked and recent, false otherwise.
	 */
	private function _get_upgrade_lock() {
		$is_upgrading = (int) get_option( 'litespeed.data.upgrading' );
		if ( ! $is_upgrading ) {
			$this->_set_upgrade_lock( false ); // Seed option to avoid repeated DB reads later.
		}
		if ( $is_upgrading && ( time() - $is_upgrading ) < 3600 ) {
			return $is_upgrading;
		}

		return false;
	}

	/**
	 * Show the upgrading banner if upgrade script is running.
	 *
	 * @since 3.0.1
	 * @return void
	 */
	public function check_upgrading_msg() {
		$is_upgrading = $this->_get_upgrade_lock();
		if ( ! $is_upgrading ) {
			return;
		}

		Admin_Display::info(
			sprintf(
				/* translators: %s: time string */
				__( 'The database has been upgrading in the background since %s. This message will disappear once upgrade is complete.', 'litespeed-cache' ),
				'<code>' . Utility::readable_time( $is_upgrading ) . '</code>'
			) . ' [LiteSpeed]',
			true
		);
	}

	/**
	 * Set/clear the upgrade process lock.
	 *
	 * @since 3.0.1
	 *
	 * @param bool $lock True to set, false to clear.
	 * @return void
	 */
	private function _set_upgrade_lock( $lock ) {
		if ( ! $lock ) {
			update_option( 'litespeed.data.upgrading', -1 );
		} else {
			update_option( 'litespeed.data.upgrading', time() );
		}
	}

	/**
	 * Get a fully-qualified table name by slug.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $tb Table slug (e.g., 'url_file').
	 * @return string|null
	 */
	public function tb( $tb ) {
		global $wpdb;

		switch ( $tb ) {
			case 'img_optm':
				return $wpdb->prefix . self::TB_IMG_OPTM;

			case 'img_optming':
				return $wpdb->prefix . self::TB_IMG_OPTMING;

			case 'avatar':
				return $wpdb->prefix . self::TB_AVATAR;

			case 'crawler':
				return $wpdb->prefix . self::TB_CRAWLER;

			case 'crawler_blacklist':
				return $wpdb->prefix . self::TB_CRAWLER_BLACKLIST;

			case 'url':
				return $wpdb->prefix . self::TB_URL;

			case 'url_file':
				return $wpdb->prefix . self::TB_URL_FILE;

			default:
				return null;
		}
	}

	/**
	 * Check if a table exists.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $tb Table slug.
	 * @return bool
	 */
	public function tb_exist( $tb ) {
		global $wpdb;

		$save_state = $wpdb->suppress_errors;
		$wpdb->suppress_errors( true );
		$describe = $wpdb->get_var( 'DESCRIBE `' . $this->tb( $tb ) . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->suppress_errors( $save_state );

		return null !== $describe;
	}

	/**
	 * Get the SQL structure (columns/indexes) for a given table slug.
	 *
	 * @since 2.0
	 * @access private
	 *
	 * @param string $tb Table slug.
	 * @return string SQL columns/indexes definition.
	 */
	private function _tb_structure( $tb ) {
		return File::read( LSCWP_DIR . 'src/data_structure/' . $tb . '.sql' );
	}

	/**
	 * Create a table by slug if it doesn't exist.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $tb Table slug.
	 * @return void
	 */
	public function tb_create( $tb ) {
		global $wpdb;

		self::debug2( '[Data] Checking table ' . $tb );

		// Check if table exists first.
		if ( $this->tb_exist( $tb ) ) {
			self::debug2( '[Data] Existed' );
			return;
		}

		self::debug( 'Creating ' . $tb );

		$sql = sprintf(
			'CREATE TABLE IF NOT EXISTS `%1$s` (%2$s) %3$s;',
			$this->tb( $tb ),
			$this->_tb_structure( $tb ),
			$wpdb->get_charset_collate()
		);
		$res = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $res ) {
			self::debug( 'Warning! Creating table failed!', $sql );
			Admin_Display::error( Error::msg( 'failed_tb_creation', [ '<code>' . $tb . '</code>', '<code>' . $sql . '</code>' ] ) );
		}
	}

	/**
	 * Drop a table by slug.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $tb Table slug.
	 * @return void
	 */
	public function tb_del( $tb ) {
		global $wpdb;

		if ( ! $this->tb_exist( $tb ) ) {
			return;
		}

		self::debug( 'Deleting table ' . $tb );

		$q = 'DROP TABLE IF EXISTS ' . $this->tb( $tb );
		$wpdb->query( $q ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Drop all generated tables (except image optimization working tables).
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public function tables_del() {
		$this->tb_del( 'avatar' );
		$this->tb_del( 'crawler' );
		$this->tb_del( 'crawler_blacklist' );
		$this->tb_del( 'url' );
		$this->tb_del( 'url_file' );

		// Deleting img_optm only can be done when destroy all optm images
	}

	/**
	 * TRUNCATE a table by slug.
	 *
	 * @since 4.0
	 * @access public
	 *
	 * @param string $tb Table slug.
	 * @return void
	 */
	public function table_truncate( $tb ) {
		global $wpdb;
		$q = 'TRUNCATE TABLE ' . $this->tb( $tb );
		$wpdb->query( $q ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Clean URL-file rows for a given file type and prune orphaned URLs.
	 *
	 * @since 4.0
	 * @access public
	 *
	 * @param string $file_type One of 'css','js','ccss','ucss'.
	 * @return void
	 */
	public function url_file_clean( $file_type ) {
		global $wpdb;

		if ( ! $this->tb_exist( 'url_file' ) ) {
			return;
		}

		if ( ! isset( $this->_url_file_types[ $file_type ] ) ) {
			return;
		}

		$type        = $this->_url_file_types[ $file_type ];
		$tb_url      = $this->tb( 'url' );
		$tb_url_file = $this->tb( 'url_file' );

		// Delete all of this type.
		$q = "DELETE FROM `$tb_url_file` WHERE `type` = %d";
		$wpdb->query( $wpdb->prepare( $q, $type ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		// Prune orphaned rows in URL table.
		$sql = "DELETE d
				FROM `{$tb_url}` AS d
				LEFT JOIN `{$tb_url_file}` AS f ON d.`id` = f.`url_id`
				WHERE f.`url_id` IS NULL";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Persist (or rotate) the mapping from URL+vary to a generated file.
	 *
	 * @since 4.0
	 * @access public
	 *
	 * @param string $request_url  Full request URL.
	 * @param string $vary         Vary string (may be long; will be md5 if >32).
	 * @param string $file_type    One of 'css','js','ccss','ucss'.
	 * @param string $filecon_md5  MD5 of the generated file content.
	 * @param string $path         Base path where files live.
	 * @param bool   $mobile       Whether mapping is for mobile.
	 * @param bool   $webp         Whether mapping is for webp.
	 * @return void
	 */
	public function save_url( $request_url, $vary, $file_type, $filecon_md5, $path, $mobile = false, $webp = false ) {
		global $wpdb;

		if ( strlen( $vary ) > 32 ) {
			$vary = md5( $vary );
		}

		if ( ! isset( $this->_url_file_types[ $file_type ] ) ) {
			return;
		}

		$type = $this->_url_file_types[ $file_type ];

		$tb_url      = $this->tb( 'url' );
		$tb_url_file = $this->tb( 'url_file' );

		// Ensure URL row exists.
		$q       = "SELECT * FROM `$tb_url` WHERE url=%s";
		$url_row = $wpdb->get_row( $wpdb->prepare( $q, $request_url ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $url_row ) {
			$q = "INSERT INTO `$tb_url` SET url=%s";
			$wpdb->query( $wpdb->prepare( $q, $request_url ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			$url_id = (int) $wpdb->insert_id;
		} else {
			$url_id = (int) $url_row['id'];
		}

		// Active mapping (not expired).
		$q        = "SELECT * FROM `$tb_url_file` WHERE url_id=%d AND vary=%s AND type=%d AND expired=0";
		$file_row = $wpdb->get_row( $wpdb->prepare( $q, [ $url_id, $vary, $type ] ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		// No change needed if filename matches.
		if ( $file_row && $file_row['filename'] === $filecon_md5 ) {
			return;
		}

		// If the new file MD5 is currently marked expired elsewhere, clear those records.
		$q = "DELETE FROM `$tb_url_file` WHERE filename = %s AND expired > 0";
		$wpdb->query( $wpdb->prepare( $q, $filecon_md5 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		// If another live row already uses the same filename, switch current row to that filename.
		if ( $file_row ) {
			$q         = "SELECT id FROM `$tb_url_file` WHERE filename = %s AND expired = 0 AND id != %d LIMIT 1";
			$exists_id = $wpdb->get_var( $wpdb->prepare( $q, [ $file_row['filename'], (int) $file_row['id'] ] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			if ( $exists_id ) {
				$q = "UPDATE `$tb_url_file` SET filename=%s WHERE id=%d";
				$wpdb->query( $wpdb->prepare( $q, [ $filecon_md5, (int) $file_row['id'] ] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
				return;
			}
		}

		// Insert a new mapping row.
		$q = "INSERT INTO `$tb_url_file` SET url_id=%d, vary=%s, filename=%s, type=%d, mobile=%d, webp=%d, expired=0";
		$wpdb->query( $wpdb->prepare( $q, [ $url_id, $vary, $filecon_md5, $type, $mobile ? 1 : 0, $webp ? 1 : 0 ] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		// Mark previous mapping as expiring (to be deleted later).
		if ( $file_row ) {
			$q       = "UPDATE `$tb_url_file` SET expired=%d WHERE id=%d";
			$expired = time() + ( 86400 * apply_filters( 'litespeed_url_file_expired_days', 20 ) );
			$wpdb->query( $wpdb->prepare( $q, [ $expired, (int) $file_row['id'] ] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

			// Delete already-expired files for this URL.
			$q    = "SELECT * FROM `$tb_url_file` WHERE url_id = %d AND expired BETWEEN 1 AND %d";
			$q    = $wpdb->prepare( $q, [ $url_id, time() ] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$list = $wpdb->get_results( $q, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			if ( $list ) {
				foreach ( $list as $v ) {
					$ext         = 'js' === $file_type ? 'js' : 'css';
					$file_to_del = trailingslashit( $path ) . $v['filename'] . '.' . $ext;
					if ( file_exists( $file_to_del ) ) {
						self::debug( 'Delete expired unused file: ' . $file_to_del );
						wp_delete_file( $file_to_del );
					}
				}
				$q = "DELETE FROM `$tb_url_file` WHERE url_id = %d AND expired BETWEEN 1 AND %d";
				$wpdb->query( $wpdb->prepare( $q, [ $url_id, time() ] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			}
		}
	}

	/**
	 * Load the stored filename (md5) for a given URL/vary/type, if active.
	 *
	 * @since 4.0
	 * @access public
	 *
	 * @param string $request_url Full request URL or tag.
	 * @param string $vary        Vary string (may be md5 if previously stored).
	 * @param string $file_type   One of 'css','js','ccss','ucss'.
	 * @return string|false Filename md5 (without extension) or false if none.
	 */
	public function load_url_file( $request_url, $vary, $file_type ) {
		global $wpdb;

		if ( strlen( $vary ) > 32 ) {
			$vary = md5( $vary );
		}

		if ( ! isset( $this->_url_file_types[ $file_type ] ) ) {
			return false;
		}
		$type = $this->_url_file_types[ $file_type ];

		self::debug2( 'load url file: ' . $request_url );

		$tb_url  = $this->tb( 'url' );
		$q       = "SELECT * FROM `$tb_url` WHERE url=%s";
		$url_row = $wpdb->get_row( $wpdb->prepare( $q, $request_url ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $url_row ) {
			return false;
		}

		$url_id      = (int) $url_row['id'];
		$tb_url_file = $this->tb( 'url_file' );
		$q           = "SELECT * FROM `$tb_url_file` WHERE url_id=%d AND vary=%s AND type=%d AND expired=0";
		$file_row    = $wpdb->get_row( $wpdb->prepare( $q, [ $url_id, $vary, $type ] ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $file_row ) {
			return false;
		}

		return $file_row['filename'];
	}

	/**
	 * Mark all UCSS entries of one URL as expired (optionally return existing rows).
	 *
	 * @since 4.5
	 * @access public
	 *
	 * @param string $request_url Target URL.
	 * @param bool   $auto_q      If true, return existing active rows before expiring.
	 * @return array Existing rows if $auto_q, otherwise empty array.
	 */
	public function mark_as_expired( $request_url, $auto_q = false ) {
		global $wpdb;
		$tb_url = $this->tb( 'url' );

		self::debug( 'Try to mark as expired: ' . $request_url );
		$q       = "SELECT * FROM `$tb_url` WHERE url=%s";
		$url_row = $wpdb->get_row( $wpdb->prepare( $q, $request_url ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $url_row ) {
			return [];
		}

		self::debug( 'Mark url_id=' . $url_row['id'] . ' as expired' );

		$tb_url_file = $this->tb( 'url_file' );

		$existing_url_files = [];
		if ( $auto_q ) {
			$q                  = "SELECT a.*, b.url FROM `$tb_url_file` a LEFT JOIN `$tb_url` b ON b.id=a.url_id WHERE a.url_id=%d AND a.type=%d AND a.expired=0";
			$q                  = $wpdb->prepare( $q, [ (int) $url_row['id'], $this->_url_file_types['ucss'] ] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$existing_url_files = $wpdb->get_results( $q, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		}

		$q       = "UPDATE `$tb_url_file` SET expired=%d WHERE url_id=%d AND type=%d AND expired=0";
		$expired = time() + 86400 * apply_filters( 'litespeed_url_file_expired_days', 20 );
		$wpdb->query( $wpdb->prepare( $q, [ $expired, (int) $url_row['id'], $this->_url_file_types['ucss'] ] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		return $existing_url_files;
	}

	/**
	 * Merge CSS excludes from file into the given list.
	 *
	 * @since 3.6
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_css_exc( $list_in ) {
		$data = $this->_load_per_line( 'css_excludes.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Merge CCSS selector whitelist from file into the given list.
	 *
	 * @since 7.1
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_ccss_whitelist( $list_in ) {
		$data = $this->_load_per_line( 'ccss_whitelist.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Merge UCSS whitelist from file into the given list.
	 *
	 * @since 4.0
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_ucss_whitelist( $list_in ) {
		$data = $this->_load_per_line( 'ucss_whitelist.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Merge JS excludes from file into the given list.
	 *
	 * @since 3.5
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_js_exc( $list_in ) {
		$data = $this->_load_per_line( 'js_excludes.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Merge JS defer excludes from file into the given list.
	 *
	 * @since 3.6
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_js_defer_exc( $list_in ) {
		$data = $this->_load_per_line( 'js_defer_excludes.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Merge OPTM URI excludes from file into the given list.
	 *
	 * @since 5.4
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_optm_uri_exc( $list_in ) {
		$data = $this->_load_per_line( 'optm_uri_exc.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Merge ESI nonces from file into the given list.
	 *
	 * @since 3.5
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_esi_nonces( $list_in ) {
		$data = $this->_load_per_line( 'esi.nonces.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Merge "nocacheable" cache keys from file into the given list.
	 *
	 * @since 6.3.0.1
	 *
	 * @param array $list_in Existing list.
	 * @return array
	 */
	public function load_cache_nocacheable( $list_in ) {
		$data = $this->_load_per_line( 'cache_nocacheable.txt' );
		if ( $data ) {
			$list_in = array_unique( array_filter( array_merge( $list_in, $data ) ) );
		}

		return $list_in;
	}

	/**
	 * Load a data file and return non-empty lines, stripping comments.
	 *
	 * Supports:
	 *  - `# comment`
	 *  - `##comment`
	 *
	 * @since 3.5
	 * @access private
	 *
	 * @param string $file Relative filename under the plugin /data directory.
	 * @return array<int,string>
	 */
	private function _load_per_line( $file ) {
		$data = File::read( LSCWP_DIR . 'data/' . $file );
		$data = explode( PHP_EOL, $data );
		$list = [];
		foreach ( $data as $v ) {
			// Drop two kinds of comments.
			if ( false !== strpos( $v, '##' ) ) {
				$v = trim( substr( $v, 0, strpos( $v, '##' ) ) );
			}
			if ( false !== strpos( $v, '# ' ) ) {
				$v = trim( substr( $v, 0, strpos( $v, '# ' ) ) );
			}

			if ( ! $v ) {
				continue;
			}

			$list[] = $v;
		}

		return $list;
	}
}
