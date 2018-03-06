<?php
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

	private $_cfg_items ;

	const TYPE_IMPORT = 'import' ;
	const TYPE_EXPORT = 'export' ;

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

		$this->_cfg_items = array(
			LiteSpeed_Cache_Config::OPTION_NAME,
			LiteSpeed_Cache_Config::VARY_GROUP,
			LiteSpeed_Cache_Config::EXCLUDE_OPTIMIZATION_ROLES,
			LiteSpeed_Cache_Config::EXCLUDE_CACHE_ROLES,
			LiteSpeed_Cache_Config::ITEM_OPTM_CSS,
			LiteSpeed_Cache_Config::ITEM_OPTM_JS_DEFER_EXC,
			LiteSpeed_Cache_Config::ITEM_MEDIA_LAZY_IMG_EXC,
			LiteSpeed_Cache_Config::ITEM_IMG_OPTM_NEED_PULL,
			LiteSpeed_Cache_Config::ITEM_ENV_REF,
			LiteSpeed_Cache_Config::ITEM_CACHE_DROP_QS,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING,
			LiteSpeed_Cache_Config::ITEM_DNS_PREFETCH,
			LiteSpeed_Cache_Config::ITEM_CLOUDFLARE_STATUS,
			LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_FILTERS,
			LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_PART_FILTERS,
			LiteSpeed_Cache_Config::ITEM_OBJECT_GLOBAL_GROUPS,
			LiteSpeed_Cache_Config::ITEM_OBJECT_NON_PERSISTENT_GROUPS,
			LiteSpeed_Cache_Config::ITEM_CRWL_AS_UIDS,
		) ;
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

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Export settings to file
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function _export()
	{

		$data = array() ;
		foreach ( $this->_cfg_items as $v ) {
			$data[ $v ] = get_option( $v ) ;
		}

		$data = base64_encode( serialize( $data ) ) ;

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
	 * Import settings from file
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function _import()
	{
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
		$data = unserialize( base64_decode( $data ) ) ;

		if ( ! $data ) {
			LiteSpeed_Cache_Log::debug( 'Import: Failed to import, no data' ) ;
			return false ;
		}

		foreach ( $this->_cfg_items as $v ) {
			if ( ! empty( $data[ $v ] ) ) {
				update_option( $v, $data[ $v ] ) ;
			}
		}

		LiteSpeed_Cache_Log::debug( 'Import: Imported ' . $_FILES[ 'ls_file' ][ 'name' ] ) ;

		$msg = sprintf( __( 'Imported setting file %s successfully.', 'litespeed-cache' ), $_FILES[ 'ls_file' ][ 'name' ] ) ;
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
