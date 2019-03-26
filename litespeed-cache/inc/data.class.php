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

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Data
{
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

		$this->_create_tb_img_optm() ;
		$this->_create_tb_html_optm() ;
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
		 * Convert old data from postmeta to img_optm table
		 * @since  2.0
		 */
		if ( ! $ver || version_compare( $ver, '2.0', '<' ) ) {
			// Migrate data from `wp_postmeta` to `wp_litespeed_img_optm`
			$mids_to_del = array() ;
			$q = "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_id" ;
			$meta_value_list = $wpdb->get_results( $wpdb->prepare( $q, array( LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_DATA ) ) ) ;
			if ( $meta_value_list ) {
				$max_k = count( $meta_value_list ) - 1 ;
				foreach ( $meta_value_list as $k => $v ) {
					$md52src_list = maybe_unserialize( $v->meta_value ) ;
					foreach ( $md52src_list as $md5 => $v2 ) {
						$f = array(
							'post_id'	=> $v->post_id,
							'optm_status'		=> $v2[ 1 ],
							'src'		=> $v2[ 0 ],
							'srcpath_md5'		=> md5( $v2[ 0 ] ),
							'src_md5'		=> $md5,
							'server'		=> $v2[ 2 ],
						) ;
						$wpdb->replace( $this->_tb_img_optm, $f ) ;
					}
					$mids_to_del[] = $v->meta_id ;

					// Delete from postmeta
					if ( count( $mids_to_del ) > 100 || $k == $max_k ) {
						$q = "DELETE FROM $wpdb->postmeta WHERE meta_id IN ( " . implode( ',', array_fill( 0, count( $mids_to_del ), '%s' ) ) . " ) " ;
						$wpdb->query( $wpdb->prepare( $q, $mids_to_del ) ) ;

						$mids_to_del = array() ;
					}
				}

				LiteSpeed_Cache_Log::debug( '[Data] img_optm inserted records: ' . $k ) ;
			}

			$q = "DELETE FROM $wpdb->postmeta WHERE meta_key = %s" ;
			$rows = $wpdb->query( $wpdb->prepare( $q, LiteSpeed_Cache_Img_Optm::DB_IMG_OPTIMIZE_STATUS ) ) ;
			LiteSpeed_Cache_Log::debug( '[Data] img_optm delete optm_status records: ' . $rows ) ;
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
					'src'		=> json_encode( $v ),
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

		$src = json_encode( $src ) ;
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

		$res = json_decode( $res, true ) ;

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