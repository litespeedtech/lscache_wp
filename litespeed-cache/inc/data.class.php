<?php

/**
 * The class to store and manage litespeed db data.
 *
 * @since      	1.3.1
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_Data
{
	private static $_instance ;

	const TB_OPTIMIZER = 'litespeed_optimizer' ;

	private $_charset_collate ;
	private $_tb_optm ;

	/**
	 * Init
	 *
	 * @since  1.3.1
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( 'Data init' ) ;
		global $wpdb ;

		$this->_charset_collate = $wpdb->get_charset_collate() ;

		$this->_tb_optm = $wpdb->base_prefix . self::TB_OPTIMIZER ;

		$this->_optm_sync() ;
	}

	/**
	 * Get optimizer table
	 *
	 * @since  1.4
	 * @access public
	 */
	public static function get_optm_table()
	{
		return self::get_instance()->_tb_optm ;
	}

	/**
	 * Check if optimizer table exists or not
	 *
	 * @since  1.3.1.1
	 * @access public
	 */
	public static function optm_available()
	{
		global $wpdb ;
		$instance = self::get_instance() ;
		return $wpdb->get_var( "SHOW TABLES LIKE '$instance->_tb_optm'" ) ;
	}

	/**
	 * Create optimizer table
	 *
	 * @since  1.3.1
	 * @access private
	 */
	private function _optm_sync()
	{
		if ( defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			return ;
		}
		define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;

		global $wpdb ;

		LiteSpeed_Cache_Log::debug2( 'Data: Checking optm table' ) ;

		// Check if table exists first
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->_tb_optm'" ) ) {
			LiteSpeed_Cache_Log::debug2( 'Data: Existed' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'Data: Creating optm table' ) ;

		$sql = sprintf(
			'CREATE TABLE IF NOT EXISTS `%1$s` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`hash_name` varchar(60) NOT NULL COMMENT "hash.filetype",
				`src` text NOT NULL COMMENT "full url array set",
				`dateline` int(11) NOT NULL,
				`refer` varchar(255) NOT NULL COMMENT "The container page url",
				PRIMARY KEY (`id`),
				UNIQUE KEY `hash_name` (`hash_name`),
				KEY `dateline` (`dateline`)
			) %2$s;',
			$this->_tb_optm,
			$this->_charset_collate
		) ;

		$wpdb->query( $sql ) ;

		// Move data from wp_options to here
		$hashes = get_option( 'litespeed-cache-optimized' ) ;
		if ( $hashes ) {
			foreach ( $hashes as $k => $v ) {
				$f = array(
					'hash_name'	=> $k,
					'src'		=> serialize( $v ),
					'dateline'	=> time(),
					'refer' 	=> '',
				) ;
				$wpdb->replace( $this->_tb_optm, $f ) ;
			}
		}
		delete_option( 'litespeed-cache-optimized' ) ;

		// Record tb version
		update_option( $this->_tb_optm, LiteSpeed_Cache::PLUGIN_VERSION ) ;

	}

	/**
	 * save optimizer src to db
	 *
	 * @since  1.3.1
	 * @access public
	 */
	public static function optm_save_src( $filename, $src )
	{
		$instance = self::get_instance() ;
		return $instance->_optm_save_src( $filename, $src ) ;
	}
	private function _optm_save_src( $filename, $src )
	{
		global $wpdb ;

		$src = serialize( $src ) ;
		$f = array(
			'hash_name'	=> $filename,
			'src'		=> $src,
			'dateline'	=> time(),
			'refer' 	=> ! empty( $_SERVER[ 'SCRIPT_URI' ] ) ? $_SERVER[ 'SCRIPT_URI' ] : '',
		) ;

		$res = $wpdb->replace( $this->_tb_optm, $f ) ;

		return $res ;
	}

	/**
	 * Get src set from hash in optimizer
	 *
	 * @since  1.3.1
	 * @access public
	 */
	public static function optm_hash2src( $filename )
	{
		$instance = self::get_instance() ;
		return $instance->_optm_hash2src( $filename ) ;
	}
	private function _optm_hash2src( $filename )
	{
		global $wpdb ;

		$sql = $wpdb->prepare( 'SELECT src FROM `' . $this->_tb_optm . '` WHERE `hash_name` = %s', $filename ) ;
		$res = $wpdb->get_var( $sql ) ;

		LiteSpeed_Cache_Log::debug2( 'Data: Loaded hash2src ' . $res ) ;

		$res = unserialize( $res ) ;

		return $res ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.3.1
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