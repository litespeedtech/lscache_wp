<?php
/**
 * Presets CLI for LiteSpeed Cache.
 *
 * @package LiteSpeed\CLI
 */

namespace LiteSpeed\CLI;

defined( 'WPINC' ) || exit();

use LiteSpeed\Debug2;
use LiteSpeed\Preset;
use WP_CLI;

/**
 * Presets CLI
 */
class Presets {

	/**
	 * Preset instance.
	 *
	 * @var Preset
	 */
	private $preset;

	/**
	 * Constructor for Presets CLI.
	 */
	public function __construct() {
		Debug2::debug( 'CLI_Presets init' );

		$this->preset = Preset::cls();
	}

	/**
	 * Applies a standard preset's settings.
	 *
	 * ## OPTIONS
	 *
	 * <preset>
	 * : The preset name to apply (e.g., basic).
	 *
	 * ## EXAMPLES
	 *
	 *     # Apply the preset called "basic"
	 *     $ wp litespeed-presets apply basic
	 *
	 * @param array $args Positional arguments (preset).
	 */
	public function apply( $args ) {
		$preset = $args[0];

		if ( empty( $preset ) ) {
			WP_CLI::error( 'Please specify a preset to apply.' );
			return;
		}

		if ( ! $this->preset->apply( $preset ) ) {
			WP_CLI::error( 'Failed to apply the preset. Check the preset name and backup directory permissions.' );
		}
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
		$backups = $this->preset->get_backups();

		foreach ( $backups as $backup ) {
			WP_CLI::line( $backup );
		}
	}

	/**
	 * Restores settings from a backup name or legacy timestamp, then deletes the file.
	 *
	 * ## OPTIONS
	 *
	 * <backup>
	 * : The name returned by get_backups, or a legacy backup timestamp.
	 *
	 * ## EXAMPLES
	 *
	 *     # Restore one exact backup
	 *     $ wp litespeed-presets restore backup-1667485245-before-basic
	 *
	 * @param array $args Positional arguments (backup name or timestamp).
	 */
	public function restore( $args ) {
		$backup = $args[0];

		if ( empty( $backup ) ) {
			WP_CLI::error( 'Please specify a backup name or timestamp to restore.' );
			return;
		}

		return $this->preset->restore( $backup );
	}
}
