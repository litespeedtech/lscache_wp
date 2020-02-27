<?php
/**
 * The import/export class.
 *
 * @since      	1.8.2
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Import extends Base
{
	protected static $_instance ;

	private $__cfg ;
	protected $_summary;

	const TYPE_IMPORT = 'import' ;
	const TYPE_EXPORT = 'export' ;
	const TYPE_RESET = 'reset' ;

	/**
	 * Init
	 *
	 * @since  1.8.2
	 * @access protected
	 */
	protected function __construct()
	{
		Debug2::debug( 'Import init' ) ;

		$this->__cfg = Conf::get_instance() ;
		$this->_summary = self::get_summary();
	}

	/**
	 * Export settings to file
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public function export( $only_data_return = false )
	{

		$data = $this->__cfg->get_options( true );

		$data = base64_encode( json_encode( $data ) ) ;

		if ( $only_data_return ) {
			return $data ;
		}

		$filename = $this->_generate_filename() ;

		// Update log
		$this->_summary[ 'export_file' ] = $filename ;
		$this->_summary[ 'export_time' ] = time() ;
		self::save_summary();

		Debug2::debug( 'Import: Saved to ' . $filename ) ;

		@header( 'Content-Disposition: attachment; filename=' . $filename ) ;
		echo $data ;

		exit ;
	}

	/**
	 * Import settings from file
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public function import( $file = false )
	{
		if ( ! $file ) {
			if ( empty( $_FILES[ 'ls_file' ][ 'name' ] ) || substr( $_FILES[ 'ls_file' ][ 'name' ], -5 ) != '.data' || empty( $_FILES[ 'ls_file' ][ 'tmp_name' ] ) ) {
				Debug2::debug( 'Import: Failed to import, wront ls_file' ) ;

				$msg = __( 'Import failed due to file error.', 'litespeed-cache' ) ;
				Admin_Display::error( $msg ) ;

				return false ;
			}

			$this->_summary[ 'import_file' ] = $_FILES[ 'ls_file' ][ 'name' ] ;

			$data = file_get_contents( $_FILES[ 'ls_file' ][ 'tmp_name' ] ) ;
		}
		else {
			$this->_summary[ 'import_file' ] = $file ;

			$data = file_get_contents( $file ) ;
		}

		// Update log
		$this->_summary[ 'import_time' ] = time() ;
		self::save_summary();

		try {
			$data = json_decode( base64_decode( $data ), true ) ;
		} catch ( \Exception $ex ) {
			Debug2::debug( 'Import: Failed to parse serialized data' ) ;
			return false ;
		}

		if ( ! $data ) {
			Debug2::debug( 'Import: Failed to import, no data' ) ;
			return false ;
		}

		$this->__cfg->update_confs( $data ) ;


		if ( ! $file ) {
			Debug2::debug( 'Import: Imported ' . $_FILES[ 'ls_file' ][ 'name' ] ) ;

			$msg = sprintf( __( 'Imported setting file %s successfully.', 'litespeed-cache' ), $_FILES[ 'ls_file' ][ 'name' ] ) ;
			Admin_Display::succeed( $msg ) ;
		}
		else {
			Debug2::debug( 'Import: Imported ' . $file ) ;
		}

		return true ;

	}

	/**
	 * Reset all configs to default values.
	 *
	 * @since  2.6.3
	 * @access public
	 */
	public function reset()
	{
		$options = $this->__cfg->load_default_vals() ;

		$this->__cfg->update_confs( $options ) ;

		Debug2::debug( '[Import] Reset successfully.' ) ;

		$msg = __( 'Reset successfully.', 'litespeed-cache' ) ;
		Admin_Display::succeed( $msg ) ;

	}

	/**
	 * Generate the filename to export
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function _generate_filename()
	{
		// Generate filename
		$parsed_home = parse_url( get_home_url() ) ;
		$filename = 'LSCWP_cfg-' ;
		if ( ! empty( $parsed_home[ 'host' ] ) ) {
			$filename .= $parsed_home[ 'host' ] . '_' ;
		}

		if ( ! empty( $parsed_home[ 'path' ] ) ) {
			$filename .= $parsed_home[ 'path' ] . '_' ;
		}

		$filename = str_replace( '/', '_', $filename ) ;

		$filename .= '-' . date( 'Ymd_His' ) . '.data' ;

		return $filename ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_IMPORT :
				$instance->import() ;
				break ;

			case self::TYPE_EXPORT :
				$instance->export() ;
				break ;

			case self::TYPE_RESET :
				$instance->reset() ;
				break ;

			default:
				break ;
		}

		Admin::redirect() ;
	}

}
