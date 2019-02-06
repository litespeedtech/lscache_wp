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
defined( 'WPINC' ) || exit ;


class LiteSpeed_Cache_Data
{
	private $_db_updater = array(
		'2.0'	=> array(
			'litespeed_update_2_0',
		),
		'3.0'	=> array(
			'litespeed_update_3_0',
		),
	) ;

	private static $_instance ;

	const TB_OPTIMIZER = 'litespeed_optimizer' ;
	const TB_IMG_OPTM = 'litespeed_img_optm' ;

	private $_charset_collate ;
	private $_tb_optm ;
	private $_tb_img_optm ;

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

		$this->_tb_optm = $wpdb->prefix . self::TB_OPTIMIZER ;
		$this->_tb_img_optm = $wpdb->prefix . self::TB_IMG_OPTM ;

		$this->_create_tb_img_optm() ;xx
		$this->_create_tb_html_optm() ;xx
	}


	/**
	 * Upgrade conf to latest format version from previous versions
	 *
	 * NOTE: Only for v3.0+
	 *
	 * @since 3.0
	 * @access public
	 */
	public function conf_upgrade()
	{
		if ( $this->_options[ self::_VERSION ] == $this->_default_options[ self::_VERSION ] ) ) {
			return ;
		}

		// Skip count check if `Use Primary Site Configurations` is on
		// Deprecated since v3.0 as network primary site didn't override the subsites conf yet
		// if ( ! is_main_site() && ! empty ( $this->_site_options[ self::NETWORK_O_USE_PRIMARY ] ) ) {
		// 	return ;
		// }

		// Update version to v3.0
		update_option( self::conf_name( self::_VERSION ), LiteSpeed_Cache::PLUGIN_VERSION ) ;
		LiteSpeed_Cache_Log::debug( '[Conf] Updated version to ' . LiteSpeed_Cache::PLUGIN_VERSION ) ;

		define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;
	}

	/**
	 * Upgrade the conf to latest version from previous data
	 *
	 * NOTE: Only for v3.0-
	 *
	 * @since 3.0
	 * @access public
	 */
	public function try_upgrade_conf_3_0()
	{
		LiteSpeed_Cache_Log::debug( '[Conf] Upgraded previous v3.0- settings to v3.0' ) ;
	}




	/**
	 * Get img_optm table name
	 *
	 * @since  2.0
	 * @access public
	 */
	public static function get_tb_img_optm()
	{
		global $wpdb ;
		return $wpdb->prefix . self::TB_IMG_OPTM ;
	}

	/**
	 * Get optimizer table
	 *
	 * @since  1.4
	 * @access public
	 */
	public static function get_optm_table()
	{
		global $wpdb ;
		return $wpdb->prefix . self::TB_OPTIMIZER ;
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
	 * Get data structure of one table
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _get_data_structure( $tb )
	{
		return Litespeed_File::read( LSCWP_DIR . 'inc/data_structure/' . $tb . '.sql' ) ;
	}

	/**
	 * Drop table img_optm
	 *
	 * @since  2.0
	 * @access private
	 */
	public function delete_tb_img_optm()
	{
		global $wpdb ;

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$this->_tb_img_optm'" ) ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Data] Deleting img_optm table' ) ;

		$q = "DROP TABLE IF EXISTS $this->_tb_img_optm" ;
		$wpdb->query( $q ) ;

		delete_option( $this->_tb_img_optm ) ;
	}

	/**
	 * Create img optm table and sync data from wp_postmeta
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _create_tb_img_optm()
	{
		if ( defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			return ;
		}
		define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;

		global $wpdb ;

		LiteSpeed_Cache_Log::debug2( '[Data] Checking img_optm table' ) ;

		// Check if table exists first
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->_tb_img_optm'" ) ) {
			LiteSpeed_Cache_Log::debug2( '[Data] Existed' ) ;
			// return ;
		}
		else {
			LiteSpeed_Cache_Log::debug( '[Data] Creating img_optm table' ) ;

			$sql = sprintf(
				'CREATE TABLE IF NOT EXISTS `%1$s` (' . $this->_get_data_structure( 'img_optm' ) . ') %2$s;',
				$this->_tb_img_optm,
				$this->_charset_collate // 'DEFAULT CHARSET=utf8'
			) ;

			$res = $wpdb->query( $sql ) ;
			if ( $res !== true ) {
				LiteSpeed_Cache_Log::debug( '[Data] Warning: Creating img_optm table failed!', $sql ) ;
			}

			// Clear OC to avoid get `_tb_img_optm` from option failed
			if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
				LiteSpeed_Cache_Object::get_instance()->flush() ;
			}

		}

		// Table version only exists after all old data migrated
		// Last modified is v2.4.2
		$ver = get_option( $this->_tb_img_optm ) ;
		if ( $ver && version_compare( $ver, '2.4.2', '>=' ) ) {
			return ;
		}


		/**
		 * Add target_md5 field to table
		 * @since  2.4.2
		 */
		if ( $ver && version_compare( $ver, '2.4.2', '<' ) && version_compare( $ver, '2.0', '>=' ) ) {// NOTE: For new users, need to bypass this section, thats why used the first cond
			$sql = sprintf(
				'ALTER TABLE `%1$s` ADD `server_info` text NOT NULL, DROP COLUMN `server`',
				$this->_tb_img_optm
			) ;

			$res = $wpdb->query( $sql ) ;
			if ( $res !== true ) {
				LiteSpeed_Cache_Log::debug( '[Data] Warning: Alter table img_optm failed!', $sql ) ;
			}
			else {
				LiteSpeed_Cache_Log::debug( '[Data] Successfully upgraded table img_optm.' ) ;
			}

		}

		// Record tb version
		update_option( $this->_tb_img_optm, LiteSpeed_Cache::PLUGIN_VERSION ) ;
	}

	/**
	 * Create optimizer table
	 *
	 * @since  1.3.1
	 * @access private
	 */
	private function _create_tb_html_optm()
	{
		if ( defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			return ;
		}
		define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;

		global $wpdb ;

		LiteSpeed_Cache_Log::debug2( '[Data] Checking html optm table' ) ;

		// Check if table exists first
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->_tb_optm'" ) ) {
			LiteSpeed_Cache_Log::debug2( '[Data] Existed' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Data] Creating html optm table' ) ;

		$sql = sprintf(
			'CREATE TABLE IF NOT EXISTS `%1$s` (' . $this->_get_data_structure( 'optm' ) . ') %2$s;',
			$this->_tb_optm,
			$this->_charset_collate
		) ;

		$res = $wpdb->query( $sql ) ;
		if ( $res !== true ) {
			LiteSpeed_Cache_Log::debug( '[Data] Warning: Creating html optm table failed!' ) ;
		}

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

		LiteSpeed_Cache_Log::debug2( '[Data] Loaded hash2src ' . $res ) ;

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