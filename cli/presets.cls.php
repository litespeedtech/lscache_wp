<?php
namespace LiteSpeed\CLI;

defined('WPINC') || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Preset;
use WP_CLI;

/**
 * Presets CLI
 */

class Presets {

	private $__preset;

	public function __construct() {
		Debug2::debug('CLI_Presets init');

		$this->__preset = Preset::cls();
	}

	/**
	 * Applies a standard preset's settings.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Apply the preset called "basic"
	 *     $ wp litespeed-presets apply basic
	 */
	public function apply( $args ) {
		$preset = $args[0];

		if (!isset($preset)) {
			WP_CLI::error('Please specify a preset to apply.');
			return;
		}

		return $this->__preset->apply($preset);
	}

	/**
	 * Returns sorted backup names.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Get all backups
	 *     $ wp litespeed-presets get_backups
	 */
	public function get_backups() {
		$backups = $this->__preset->get_backups();

		foreach ($backups as $backup) {
			WP_CLI::line($backup);
		}
	}

	/**
	 * Restores settings from the backup file with the given timestamp, then deletes the file.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Restore the backup with the timestamp 1667485245
	 *     $ wp litespeed-presets restore 1667485245
	 */
	public function restore( $args ) {
		$timestamp = $args[0];

		if (!isset($timestamp)) {
			WP_CLI::error('Please specify a timestamp to restore.');
			return;
		}

		return $this->__preset->restore($timestamp);
	}
}
