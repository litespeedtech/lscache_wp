<?php
// phpcs:ignoreFile
/**
 * The preset class.
 *
 * @since  5.3.0
 */
namespace LiteSpeed;

defined('WPINC') || exit();

class Preset extends Import {

	const MAX_BACKUPS = 10;

	const TYPE_APPLY   = 'apply';
	const TYPE_RESTORE = 'restore';

	const STANDARD_DIR = LSCWP_DIR . 'data/preset';
	const BACKUP_DIR   = LSCWP_CONTENT_DIR . '/litespeed-preset-backups';
	const BACKUP_GUARD = "<?php exit; ?>\n";
	const BACKUP_EXTENSION = '.php';
	const LEGACY_DIR = LITESPEED_STATIC_DIR . '/auto-backup';

	/**
	 * Preset and backup names become file names, so only this shape is accepted.
	 *
	 * @since 7.9.2
	 */
	const PATTERN_NAME = '/^[A-Za-z0-9][A-Za-z0-9_-]*\z/';
	const PATTERN_BACKUP_NAME = '/^backup-([0-9]+)(?:-[a-f0-9]{32})?-before-([A-Za-z0-9_-]+)\z/';

	/**
	 * Validate a preset or backup name; false when it cannot be used as a file name.
	 *
	 * @since 7.9.2
	 */
	public static function sanitize_name( $name ) {
		return is_string($name) && preg_match(self::PATTERN_NAME, $name) ? $name : false;
	}

	/**
	 * Returns sorted backup names, excluding entries that cannot be restored or pruned.
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public static function get_backups() {
		self::init_filesystem();
		global $wp_filesystem;

		$backups = [];
		$files = $wp_filesystem->dirlist(self::backup_dir());
		foreach ($files ? $files : [] as $file) {
			// Unrelated files and directories must not consume backup retention slots.
			if ('f' !== $file['type'] || self::BACKUP_EXTENSION !== substr($file['name'], -4)) {
				continue;
			}
			$path = path_join( self::backup_dir(), $file['name'] );
			if ( ! is_file( $path ) || is_link( $path ) || ! File::within( $path, self::backup_dir() ) || self::BACKUP_GUARD !== @file_get_contents( $path, false, null, 0, strlen( self::BACKUP_GUARD ) ) ) {
				continue;
			}
			$name = self::sanitize_name(self::basename($file['name']));
			if ( false !== $name && preg_match( self::PATTERN_BACKUP_NAME, $name ) ) {
				$backups[] = $name;
			}
		}
		rsort($backups);

		return $backups;
	}

	/**
	 * Removes extra backup files
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public static function prune_backups() {
		$backups = self::get_backups();
		global $wp_filesystem;

		foreach (array_slice($backups, self::MAX_BACKUPS) as $backup) {
			$path = self::get_backup($backup);
			$wp_filesystem->delete($path);
			Debug2::debug('[Preset] Deleted old backup from ' . $backup);
		}
	}

	/**
	 * Returns a settings file's extensionless basename given its filesystem path
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public static function basename( $path ) {
		return basename(basename($path, '.data'), self::BACKUP_EXTENSION);
	}

	/**
	 * Backups belong to the current blog, even when the plugin is network active.
	 *
	 * @since 7.9.2
	 * @return string
	 */
	public static function backup_dir() {
		$network_id = is_multisite() ? max( 1, (int) get_current_network_id() ) : 1;
		$blog_id    = max( 1, (int) get_current_blog_id() );
		return self::BACKUP_DIR . '/network-' . $network_id . '/blog-' . $blog_id;
	}

	/**
	 * Returns a standard preset's path given its extensionless basename
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public static function get_standard( $name ) {
		$name = self::sanitize_name($name);
		return $name ? path_join(self::STANDARD_DIR, $name . '.data') : false;
	}

	/**
	 * Returns a backup's path given its extensionless basename
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public static function get_backup( $name ) {
		$name = self::sanitize_name($name);
		return $name ? path_join(self::backup_dir(), $name . self::BACKUP_EXTENSION) : false;
	}

	/**
	 * Initializes the global $wp_filesystem object and clears stat cache
	 *
	 * @since  5.3.0
	 */
	static function init_filesystem() {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		\WP_Filesystem();
		clearstatcache();
	}

	/**
	 * Init
	 *
	 * @since  5.3.0
	 */
	public function __construct() {
		Debug2::debug('[Preset] Init');
		$this->_summary = self::get_summary();
	}

	/**
	 * Applies a standard preset's settings given its extensionless basename
	 *
	 * @since  5.3.0
	 * @access public
	 * @return bool Whether the preset was applied successfully.
	 */
	public function apply( $preset ) {
		$path = self::get_standard($preset);
		if (!$path || !is_file($path)) {
			$this->log('error');
			return false;
		}

		if (!$this->make_backup($preset)) {
			$this->log('error');
			return false;
		}

		$result = $this->import_file($path) ? $preset : 'error';

		$this->log($result);
		return 'error' !== $result;
	}

	/**
	 * Restores settings from an exact backup name or legacy timestamp, then deletes the file.
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public function restore( $timestamp ) {
		$requested = self::sanitize_name( $timestamp );
		$available = self::get_backups();
		$backups   = array();
		if ( $requested && preg_match( self::PATTERN_BACKUP_NAME, $requested ) ) {
			if ( in_array( $requested, $available, true ) ) {
				$backups[] = $requested;
			}
		} else {
			$timestamp = (int) $timestamp;
			foreach ( $available as $backup ) {
				$parts = [];
				if ( $timestamp > 0 && preg_match( self::PATTERN_BACKUP_NAME, $backup, $parts ) && (int) $parts[1] === $timestamp ) {
					$backups[] = $backup;
				}
			}
		}

		if (empty($backups)) {
			$this->log('error');
			return;
		}

		$backup = $backups[0];
		$path   = self::get_backup($backup);

		if (!$path || !$this->import_file($path)) {
			$this->log('error');
			return;
		}

		self::init_filesystem();
		global $wp_filesystem;

		$wp_filesystem->delete($path);
		Debug2::debug('[Preset] Deleted most recent backup from ' . $backup);

		$this->log('backup');
	}

	/**
	 * Saves current settings as a backup file, then prunes extra backup files
	 *
	 * @since  5.3.0
	 * @access public
	 * @return bool Whether the backup was completely saved. Failure prevents applying the preset.
	 */
	public function make_backup( $preset ) {
		try {
			$backup = 'backup-' . time() . '-' . bin2hex( random_bytes( 16 ) ) . '-before-' . $preset;
		} catch ( \Throwable $e ) {
			Debug2::debug( '[Preset] Failed to create a private backup name' );
			return false;
		}
		$data   = $this->export(true, true);

		$path = self::get_backup($backup);
		$index = self::backup_dir() . '/index.php';
		$index_ready = is_file( $index ) ? ! is_link( $index ) && File::read( $index ) === self::BACKUP_GUARD : File::save_atomic( $index, self::BACKUP_GUARD, true );
		if ( ! $path || ! $index_ready || ! File::save_atomic( $path, self::BACKUP_GUARD . $data, true ) ) {
			Debug2::debug('[Preset] Failed to save backup; settings were not changed');
			return false;
		}
		Debug2::debug('[Preset] Backup saved to ' . $backup);

		self::prune_backups();
		return true;
	}

	/**
	 * Tries to import from a given settings file
	 *
	 * @since  5.3.0
	 */
	function import_file( $path ) {
		$debug = function ( $result, $name ) {
			$action = $result ? 'Applied' : 'Failed to apply';
			Debug2::debug('[Preset] ' . $action . ' settings from ' . $name);
			return $result;
		};

		$name     = self::basename($path);
		$is_backup = self::BACKUP_EXTENSION === substr( $path, -4 );
		if ( $is_backup && ( is_link( $path ) || ! File::within( $path, self::backup_dir() ) ) ) {
			return $debug( false, $name );
		}
		$contents = file_get_contents($path);

		if (false === $contents) {
			Debug2::debug('[Preset] ❌ Failed to get file contents');
			return $debug(false, $name);
		}
		if ( $is_backup ) {
			if ( 0 !== strpos( $contents, self::BACKUP_GUARD ) ) {
				return $debug( false, $name );
			}
			$contents = substr( $contents, strlen( self::BACKUP_GUARD ) );
		}

		$parsed = array();
		try {
			// Check if the data is v4+
			if (strpos($contents, '["_version",') === 0) {
				$contents = explode("\n", $contents);
				foreach ($contents as $line) {
					$line = trim($line);
					if (empty($line)) {
						continue;
					}
					$item = \json_decode($line, true);
					if ( ! is_array( $item ) || array_keys( $item ) !== [ 0, 1 ] || ! is_string( $item[0] ) ) {
						return $debug( false, $name );
					}
					list($key, $value) = $item;
					$parsed[$key]      = $value;
				}
			} else {
				$decoded = base64_decode( $contents, true );
				$parsed = false !== $decoded ? \json_decode( $decoded, true ) : false;
			}
		} catch (\Exception $ex) {
			Debug2::debug('[Preset] ❌ Failed to parse serialized data');
			return $debug(false, $name);
		}

		if ( ! is_array( $parsed ) || empty($parsed) ) {
			Debug2::debug('[Preset] ❌ Nothing to apply');
			return $debug(false, $name);
		}

		if ( $is_backup ) {
			foreach ( array_keys( $parsed ) as $id ) {
				if ( $this->_conf_secret( $id ) ) {
					unset( $parsed[ $id ] );
				}
			}
		}
		$this->cls('Conf')->update_confs($parsed);

		return $debug(true, $name);
	}

	/**
	 * Updates the log
	 *
	 * @since  5.3.0
	 */
	function log( $preset ) {
		$this->_summary['preset']           = $preset;
		$this->_summary['preset_timestamp'] = time();
		self::save_summary();
	}

	/**
	 * Handles all request actions from main cls
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_APPLY:
            $this->apply(!empty($_GET['preset']) ? sanitize_text_field(wp_unslash($_GET['preset'])) : false);
				break;

			case self::TYPE_RESTORE:
            $this->restore( ! empty( $_GET['backup'] ) && is_string( $_GET['backup'] ) ? sanitize_text_field( wp_unslash( $_GET['backup'] ) ) : ( ! empty( $_GET['timestamp'] ) ? absint( $_GET['timestamp'] ) : 0 ) );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
