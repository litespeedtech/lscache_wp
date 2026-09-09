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
	const BACKUP_DIR   = LITESPEED_STATIC_DIR . '/auto-backup';

	/**
	 * Preset and backup names become file names, so only this shape is accepted.
	 *
	 * @since 7.9.2
	 */
	const PATTERN_NAME = '/^[A-Za-z0-9][A-Za-z0-9_-]*\z/';

	/**
	 * Validate a preset or backup name; false when it cannot be used as a file name.
	 *
	 * @since 7.9.2
	 */
	public static function sanitize_name( $name ) {
		return is_string($name) && preg_match(self::PATTERN_NAME, $name) ? $name : false;
	}

	/**
	 * Returns sorted backup names
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public static function get_backups() {
		self::init_filesystem();
		global $wp_filesystem;

		$backups = array_map(
			function ( $path ) {
				return self::basename($path['name']);
			},
			$wp_filesystem->dirlist(self::BACKUP_DIR) ?: array()
		);
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
		return basename($path, '.data');
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
		return $name ? path_join(self::BACKUP_DIR, $name . '.data') : false;
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
	 */
	public function apply( $preset ) {
		$path = self::get_standard($preset);
		if (!$path || !is_file($path)) {
			$this->log('error');
			return;
		}

		$this->make_backup($preset);

		$result = $this->import_file($path) ? $preset : 'error';

		$this->log($result);
	}

	/**
	 * Restores settings from the backup file with the given timestamp, then deletes the file
	 *
	 * @since  5.3.0
	 * @access public
	 */
	public function restore( $timestamp ) {
		$timestamp = (int) $timestamp;
		$backups   = array();
		foreach (self::get_backups() as $backup) {
			if ($timestamp > 0 && preg_match('/^backup-' . $timestamp . '(-|$)/', $backup) === 1) {
				$backups[] = $backup;
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
	 */
	public function make_backup( $preset ) {
		$backup = 'backup-' . time() . '-before-' . $preset;
		$data   = $this->export(true, true);

		$path = self::get_backup($backup);
		File::save($path, $data, true);
		Debug2::debug('[Preset] Backup saved to ' . $backup);

		self::prune_backups();
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
		$contents = file_get_contents($path);

		if (false === $contents) {
			Debug2::debug('[Preset] ❌ Failed to get file contents');
			return $debug(false, $name);
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
					list($key, $value) = \json_decode($line, true);
					$parsed[$key]      = $value;
				}
			} else {
				$parsed = \json_decode(base64_decode($contents), true);
			}
		} catch (\Exception $ex) {
			Debug2::debug('[Preset] ❌ Failed to parse serialized data');
			return $debug(false, $name);
		}

		if (empty($parsed)) {
			Debug2::debug('[Preset] ❌ Nothing to apply');
			return $debug(false, $name);
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
            $this->restore(!empty($_GET['timestamp']) ? absint($_GET['timestamp']) : 0);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
