<?php
/**
 * The import/export class.
 *
 * @since      	1.8.2
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Import
{
	private static $_instance ;

	private $_cfg_items ;
	private $__cfg ;

	const TYPE_IMPORT = 'import' ;
	const TYPE_EXPORT = 'export' ;
	const TYPE_RESET = 'reset' ;

	const DB_IMPORT_LOG = 'litespeed_import_log' ;

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

		$this->_cfg_items = $this->__cfg->stored_items() ;
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

		$data = array() ;
		foreach ( $this->_cfg_items as $v ) {
			$data[ $v ] = get_option( $v ) ;// Here doesn't need the default_item value so no need to call `LiteSpeed_Cache_Config::get_instance()->get_item()`
		}

		$data = base64_encode( json_encode( $data ) ) ;

		if ( $only_data_return ) {
			return $data ;
		}

		$filename = $this->_generate_filename() ;

		// Update log
		$log = get_option( self::DB_IMPORT_LOG, array() ) ;
		if ( empty( $log[ 'export' ] ) ) {
			$log[ 'export' ] = array() ;
		}
		$log[ 'export' ][ 'file' ] = $filename ;
		$log[ 'export' ][ 'time' ] = time() ;

		update_option( self::DB_IMPORT_LOG, $log ) ;

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
			$log = get_option( self::DB_IMPORT_LOG, array() ) ;
			if ( empty( $log[ 'import' ] ) ) {
				$log[ 'import' ] = array() ;
			}
			$log[ 'import' ][ 'file' ] = $_FILES[ 'ls_file' ][ 'name' ] ;
			$log[ 'import' ][ 'time' ] = time() ;

			update_option( self::DB_IMPORT_LOG, $log ) ;

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

		$options = $data[ LiteSpeed_Cache_Config::OPTION_NAME ] ;
		foreach ( $this->_cfg_items as $v ) {
			$options[ $v ] = $data[ $v ] ;
		}

		$output = LiteSpeed_Cache_Admin_Settings::get_instance()->validate_plugin_settings( $options, true ) ;

		global $wp_settings_errors ;
		if ( ! empty( $wp_settings_errors ) ) {
			foreach ( $wp_settings_errors as $err ) {
				LiteSpeed_Cache_Admin_Display::error( $err[ 'message' ] ) ;
				LiteSpeed_Cache_Log::debug( '[Import] err ' . $err[ 'message' ] ) ;
			}
			return false ;
		}

		if ( ! $file ) {
			LiteSpeed_Cache_Log::debug( 'Import: Imported ' . $_FILES[ 'ls_file' ][ 'name' ] ) ;

			$msg = sprintf( __( 'Imported setting file %s successfully.', 'litespeed-cache' ), $_FILES[ 'ls_file' ][ 'name' ] ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
		}
		else {
			LiteSpeed_Cache_Log::debug( 'Import: Imported ' . $file ) ;
		}

		$ret = $this->__cfg->update_options( $output ) ;

		return true ;

	}

	/**
	 * Reset all settings
	 *
	 * @since  2.6.3
	 */
	public function reset()
	{
		return $this->_reset() ;
	}

	/**
	 * Reset all configs to default values.
	 *
	 * @since  2.6.3
	 * @access private
	 */
	private function _reset()
	{
		$options = $this->__cfg->get_default_options() ;
		// Get items
		foreach ( $this->_cfg_items as $v ) {
			$options[ $v ] = $this->__cfg->default_item( $v ) ;
		}

		$output = LiteSpeed_Cache_Admin_Settings::get_instance()->validate_plugin_settings( $options, true ) ;

		$ret = $this->__cfg->update_options( $output ) ;

		LiteSpeed_Cache_Log::debug( '[Import] Reset successfully.' ) ;

		$msg = __( 'Reset successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		return true ;
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
