<?php
defined( 'WPINC' ) || exit ;
/**
 * The import/export class.
 *
 * @since      	1.8.2
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_Import
{
	private static $_instance ;

	private $__cfg ;
	private $_log_name ;

	const TYPE_IMPORT = 'import' ;
	const TYPE_EXPORT = 'export' ;
	const TYPE_RESET = 'reset' ;

	/**
	 * Init
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug( 'Import init' ) ;

		$this->__cfg = LiteSpeed_Cache_Config::get_instance() ;
		$this->_log_name = LiteSpeed_Cache_Const::conf_name( 'import', 'log' ) ;
	}

	/**
	 * Show summary of history
	 *
	 * @since  3.0
	 * @access public
	 */
	public function summary()
	{
		$log = get_option( $this->_log_name, array() ) ;

		return $log ;
	}

	/**
	 * Export settings
	 *
	 * @since  2.4.1
	 * @return string All settings data
	 */
	public function export()
	{
		return $this->_export( true ) ;
	}

	/**
	 * Export settings to file
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function _export( $only_data_return = false )
	{

		$data = $this->__cfg->get_options() ;

		$data = base64_encode( json_encode( $data ) ) ;

		if ( $only_data_return ) {
			return $data ;
		}

		$filename = $this->_generate_filename() ;

		// Update log
		$log = $this->summary() ;
		if ( empty( $log[ 'export' ] ) ) {
			$log[ 'export' ] = array() ;
		}
		$log[ 'export' ][ 'file' ] = $filename ;
		$log[ 'export' ][ 'time' ] = time() ;

		update_option( $this->_log_name, $log ) ;

		LiteSpeed_Cache_Log::debug( 'Import: Saved to ' . $filename ) ;

		@header( 'Content-Disposition: attachment; filename=' . $filename ) ;
		echo $data ;

		exit ;
	}

	/**
	 * Import settings
	 *
	 * @since  2.4.1
	 */
	public function import( $file )
	{
		return $this->_import( $file ) ;
	}

	/**
	 * Import settings from file
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function _import( $file = false )
	{
		if ( ! $file ) {
			if ( empty( $_FILES[ 'ls_file' ][ 'name' ] ) || substr( $_FILES[ 'ls_file' ][ 'name' ], -5 ) != '.data' || empty( $_FILES[ 'ls_file' ][ 'tmp_name' ] ) ) {
				LiteSpeed_Cache_Log::debug( 'Import: Failed to import, wront ls_file' ) ;

				$msg = __( 'Import failed due to file error.', 'litespeed-cache' ) ;
				LiteSpeed_Cache_Admin_Display::error( $msg ) ;

				return false ;
			}

			// Update log
			$log = $this->summary() ;
			if ( empty( $log[ 'import' ] ) ) {
				$log[ 'import' ] = array() ;
			}
			$log[ 'import' ][ 'file' ] = $_FILES[ 'ls_file' ][ 'name' ] ;
			$log[ 'import' ][ 'time' ] = time() ;

			update_option( $this->_log_name, $log ) ;

			$data = file_get_contents( $_FILES[ 'ls_file' ][ 'tmp_name' ] ) ;
		}
		else {
			$data = file_get_contents( $file ) ;
		}

		try {
			$data = json_decode( base64_decode( $data ), true ) ;
		} catch ( \Exception $ex ) {
			LiteSpeed_Cache_Log::debug( 'Import: Failed to parse serialized data' ) ;
			return false ;
		}

		if ( ! $data ) {
			LiteSpeed_Cache_Log::debug( 'Import: Failed to import, no data' ) ;
			return false ;
		}

		$this->__cfg->update_confs( $data ) ;


		if ( ! $file ) {
			LiteSpeed_Cache_Log::debug( 'Import: Imported ' . $_FILES[ 'ls_file' ][ 'name' ] ) ;

			$msg = sprintf( __( 'Imported setting file %s successfully.', 'litespeed-cache' ), $_FILES[ 'ls_file' ][ 'name' ] ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
		}
		else {
			LiteSpeed_Cache_Log::debug( 'Import: Imported ' . $file ) ;
		}

		return true ;

	}

	/**
	 * Reset all configs to default values.
	 *
	 * @since  2.6.3
	 * @access private
	 */
	private function _reset()
	{
		$options = $this->__cfg->default_vals() ;

		$this->__cfg->update_confs( $options ) ;

		LiteSpeed_Cache_Log::debug( '[Import] Reset successfully.' ) ;

		$msg = __( 'Reset successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

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

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_IMPORT :
				$instance->_import() ;
				break ;

			case self::TYPE_EXPORT :
				$instance->_export() ;
				break ;

			case self::TYPE_RESET :
				$instance->_reset() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
