<?php
/**
 * The profiles class.
 *
 * @since  5.1.0
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Profiles extends Base {
	protected $_summary;

	const TYPE_APPLY = 'apply';
	const TYPE_REVERT = 'revert';

	/**
	 * Init
	 *
	 * @since  5.1.0
	 */
	public function __construct() {
		Debug2::debug( 'Profiles init' );

		$this->_summary = self::get_summary();
	}

	/**
	 * Apply a profile's settings from file
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function apply( $profile = false ) {
		if ( false === $profile ) {
			return;
		}

		$this->_summary[ 'profile' ] = $profile;

		// Update log
		$this->_summary[ 'profile_time' ] = time();
		self::save_summary();
	}	

	/**
	 * Revert to the previous settings saved in the database
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function revert() {
		$this->_summary[ 'profile' ] = esc_html__( 'Previous', 'litespeed-cache' );

		// Update log
		$this->_summary[ 'profile_time' ] = time();
		self::save_summary();
	}	



	/**
	 * Export settings to file
	 *
	 * @since  1.8.2
	 * @access public
	 */
/*
	public function export( $only_data_return = false ) {
		$raw_data = $this->get_options( true );

		$data = array();
		foreach ( $raw_data as $k => $v ) {
			$data[] = json_encode( array( $k, $v ) );
		}

		$data = implode( "\n\n", $data );

		if ( $only_data_return ) {
			return $data;
		}

		$filename = $this->_generate_filename();

		// Update log
		$this->_summary[ 'export_file' ] = $filename;
		$this->_summary[ 'export_time' ] = time();
		self::save_summary();

		Debug2::debug( 'Import: Saved to ' . $filename );

		@header( 'Content-Disposition: attachment; filename=' . $filename );
		echo $data;

		exit;
	}
*/

	/**
	 * Import settings from file
	 *
	 * @since  1.8.2
	 * @access public
	 */
/*
	public function import( $file = false ) {
		if ( ! $file ) {
			if ( empty( $_FILES[ 'ls_file' ][ 'name' ] ) || substr( $_FILES[ 'ls_file' ][ 'name' ], -5 ) != '.data' || empty( $_FILES[ 'ls_file' ][ 'tmp_name' ] ) ) {
				Debug2::debug( 'Import: Failed to import, wront ls_file' );

				$msg = __( 'Import failed due to file error.', 'litespeed-cache' );
				Admin_Display::error( $msg );

				return false;
			}

			$this->_summary[ 'import_file' ] = $_FILES[ 'ls_file' ][ 'name' ];

			$data = file_get_contents( $_FILES[ 'ls_file' ][ 'tmp_name' ] );
		}
		else {
			$this->_summary[ 'import_file' ] = $file;

			$data = file_get_contents( $file );
		}

		// Update log
		$this->_summary[ 'import_time' ] = time();
		self::save_summary();

		$ori_data = array();
		try {
			// Check if the data is v4+ or not
			if ( strpos( $data, '["_version",' ) === 0 ) {
				Debug2::debug( '[Import] Data version: v4+' );
				$data = explode( "\n", $data );
				foreach ( $data as $v ) {
					$v = trim( $v );
					if ( ! $v ) {
						continue;
					}
					list( $k, $v ) = json_decode( $v, true );
					$ori_data[ $k ] = $v;
				}
			}
			else {
				$ori_data = json_decode( base64_decode( $data ), true );
			}
		} catch ( \Exception $ex ) {
			Debug2::debug( '[Import] ❌ Failed to parse serialized data' );
			return false;
		}

		if ( ! $ori_data ) {
			Debug2::debug( '[Import] ❌ Failed to import, no data' );
			return false;
		}
		else {
			Debug2::debug( '[Import] Importing data', $ori_data );
		}

		$this->cls( 'Conf' )->update_confs( $ori_data );


		if ( ! $file ) {
			Debug2::debug( 'Import: Imported ' . $_FILES[ 'ls_file' ][ 'name' ] );

			$msg = sprintf( __( 'Imported setting file %s successfully.', 'litespeed-cache' ), $_FILES[ 'ls_file' ][ 'name' ] );
			Admin_Display::succeed( $msg );
		}
		else {
			Debug2::debug( 'Import: Imported ' . $file );
		}

		return true;

	}
*/

	/**
	 * Reset all configs to default values.
	 *
	 * @since  2.6.3
	 * @access public
	 */
/*
	public function reset() {
		$options = $this->cls( 'Conf' )->load_default_vals();

		$this->cls( 'Conf' )->update_confs( $options );

		Debug2::debug( '[Import] Reset successfully.' );

		$msg = __( 'Reset successfully.', 'litespeed-cache' );
		Admin_Display::succeed( $msg );

	}
*/

	/**
	 * Generate the filename to export
	 *
	 * @since  1.8.2
	 * @access private
	 */
/*
	private function _generate_filename() {
		// Generate filename
		$parsed_home = parse_url( get_home_url() );
		$filename = 'LSCWP_cfg-';
		if ( ! empty( $parsed_home[ 'host' ] ) ) {
			$filename .= $parsed_home[ 'host' ] . '_';
		}

		if ( ! empty( $parsed_home[ 'path' ] ) ) {
			$filename .= $parsed_home[ 'path' ] . '_';
		}

		$filename = str_replace( '/', '_', $filename );

		$filename .= '-' . date( 'Ymd_His' ) . '.data';

		return $filename;
	}
*/

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_APPLY:
				$this->apply( ! empty( $_GET['profile'] ) ? $_GET['profile'] : false );
				break;

			case self::TYPE_REVERT:
				$this->revert();
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}
