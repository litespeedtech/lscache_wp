<?php
// phpcs:ignoreFile

/**
 * The import/export class.
 *
 * @since       1.8.2
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Import extends Base {

	const TYPE_IMPORT = 'import';
	const TYPE_EXPORT = 'export';
	const TYPE_RESET  = 'reset';

	/**
	 * Decode exported settings shared by manual imports and presets.
	 *
	 * @param string $data Export contents, without a backup's PHP guard.
	 * @return array|false Settings, or false for an invalid record.
	 */
	public static function parse_settings( $data ) {
		if ( 0 !== strpos( $data, '["_version",' ) ) {
			$settings = json_decode( base64_decode( $data ), true );
			return is_array( $settings ) ? $settings : false;
		}
		$settings = [];
		foreach ( explode( "\n", $data ) as $line ) {
			$line = trim( $line );
			if ( ! $line ) {
				continue;
			}
			$entry = json_decode( $line, true );
			if ( ! is_array( $entry ) || array_keys( $entry ) !== [ 0, 1 ] || ! is_string( $entry[0] ) ) {
				return false;
			}
			$settings[ $entry[0] ] = $entry[1];
		}
		return $settings;
	}

	/**
	 * Init
	 *
	 * @since  1.8.2
	 */
	public function __construct() {
		Debug2::debug('Import init');

		$this->_summary = self::get_summary();
	}

	/**
	 * Export settings to file
	 *
	 * @since 1.8.2
	 * @since 7.3 added download content type
	 * @access public
	 */
	public function export( $only_data_return = false, $mask_secrets = false ) {
		$raw_data = $this->get_options(true);

		$data = array();
		foreach ($raw_data as $k => $v) {
			if ($mask_secrets && $this->_conf_secret($k)) {
				continue;
			}
			$data[] = \json_encode(array( $k, $v ));
		}

		$data = implode("\n\n", $data);

		if ($only_data_return) {
			return $data;
		}

		$filename = $this->_generate_filename();

		// Update log
		$this->_summary['export_file'] = $filename;
		$this->_summary['export_time'] = time();
		self::save_summary();

		Debug2::debug('Import: Saved to ' . $filename);

		@header('Content-Type: application/octet-stream');
		@header('Content-Disposition: attachment; filename=' . $filename);
		echo $data;

		exit();
	}

	/**
	 * Import settings from file
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public function import( $file = false ) {
		if (!$file) {
			if (empty($_FILES['ls_file']['name']) || substr($_FILES['ls_file']['name'], -5) != '.data' || empty($_FILES['ls_file']['tmp_name'])) {
				Debug2::debug('Import: Failed to import, wrong ls_file');

				$msg = __('Import failed due to file error.', 'litespeed-cache');
				Admin_Display::error($msg);

				return false;
			}

			$this->_summary['import_file'] = $_FILES['ls_file']['name'];

			$data = file_get_contents($_FILES['ls_file']['tmp_name']);
		} else {
			$this->_summary['import_file'] = $file;

			$data = file_get_contents($file);
		}

		// Update log
		$this->_summary['import_time'] = time();
		self::save_summary();

		$ori_data = self::parse_settings( $data );

		if ( ! is_array( $ori_data ) || ! $ori_data ) {
			Debug2::debug('[Import] ❌ Failed to import, no data');
			return false;
		} else {
			Debug2::debug('[Import] Importing settings');
		}

		// Imports are portable performance profiles, not a channel for debug trust or object-cache credentials/endpoints; Conf::update() already refuses HASH and _VER.
		foreach ( array_keys( $ori_data ) as $id ) {
			if ( in_array( $id, [ Base::O_DEBUG_IPS, Base::O_SERVER_IP ], true ) || 0 === strpos( $id, 'object-' ) ) {
				unset( $ori_data[ $id ] );
			}
		}

		$ori_data = $this->_strip_unwritable_shared( $ori_data );
		$this->cls('Conf')->update_confs($ori_data);

		if (!$file) {
			Debug2::debug('Import: Imported ' . $_FILES['ls_file']['name']);

			$msg = sprintf(__('Imported setting file %s successfully.', 'litespeed-cache'), $_FILES['ls_file']['name']);
			Admin_Display::success($msg);
		} else {
			Debug2::debug('Import: Imported ' . $file);
		}

		return true;
	}

	/**
	 * Reset all configs to default values.
	 *
	 * @since  2.6.3
	 * @access public
	 */
	public function reset() {
		$options = $this->cls('Conf')->load_default_vals();
		$options = $this->_strip_unwritable_shared( $options );

		$this->cls('Conf')->update_confs($options);

		Debug2::debug('[Import] Reset successfully.');

		$msg = __('Reset successfully.', 'litespeed-cache');
		Admin_Display::success($msg);
	}

	/**
	 * Remove network-managed shared settings this site cannot write and report them once, instead of Conf::update() emitting a per-option error.
	 *
	 * @since 7.9.2
	 * @param array<string,mixed> $matrix Option-value map.
	 * @return array<string,mixed>
	 */
	protected function _strip_unwritable_shared( array $matrix ) {
		$conf    = $this->cls( 'Conf' );
		$skipped = [];
		foreach ( $matrix as $id => $val ) {
			if ( $conf->can_update_shared( $id ) ) {
				continue;
			}
			if ( $conf->type_casting( $val, $id ) !== $conf->conf( $id ) ) {
				$skipped[] = $id;
			}
			unset( $matrix[ $id ] );
		}
		if ( $skipped ) {
			Debug2::debug( '[Import] Skipped network-managed shared settings: ' . implode( ', ', $skipped ) );
			Admin_Display::info( __( 'Network-managed shared settings (object cache, Guest Mode, login cookie, HTTPS vary) were left unchanged; a network administrator can change them in the primary site or network settings.', 'litespeed-cache' ) );
		}
		return $matrix;
	}

	/**
	 * Generate the filename to export
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function _generate_filename() {
		// Generate filename
		$parsed_home = parse_url(get_home_url());
		$filename    = 'LSCWP_cfg-';
		if (!empty($parsed_home['host'])) {
			$filename .= $parsed_home['host'] . '_';
		}

		if (!empty($parsed_home['path'])) {
			$filename .= $parsed_home['path'] . '_';
		}

		$filename = str_replace('/', '_', $filename);

		$filename .= '-' . date('Ymd_His') . '.data';

		return $filename;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_IMPORT:
            $this->import();
				break;

			case self::TYPE_EXPORT:
            $this->export();
				break;

			case self::TYPE_RESET:
            $this->reset();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
