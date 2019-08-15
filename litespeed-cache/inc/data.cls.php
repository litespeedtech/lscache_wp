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
		// Example
		// '2.0'	=> array(
		// 	'litespeed_update_2_0',
		// ),
	) ;

	private $_db_site_updater = array(
		// Example
		// '2.0'	=> array(
		// 	'litespeed_update_site_2_0',
		// ),
	) ;

	private static $_instance ;

	const TB_CSSJS = 'litespeed_cssjs' ;
	const TB_IMG_OPTM = 'litespeed_img_optm' ;
	const TB_AVATAR = 'litespeed_avatar' ;

	private $_charset_collate ;
	private $_tb_cssjs ;
	private $_tb_img_optm ;
	private $_tb_avatar ;

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

		$this->_tb_cssjs = $wpdb->prefix . self::TB_CSSJS ;
		$this->_tb_img_optm = $wpdb->prefix . self::TB_IMG_OPTM ;
		$this->_tb_avatar = self::tb_avatar() ;
	}

	/**
	 * Correct table existance
	 *
	 * Call when activate -> upadte_confs()
	 * Call when upadte_confs()
	 *
	 * @since  3.0
	 * @access public
	 */
	public function correct_tb_existance()
	{
		// CSS JS optm
		if ( LiteSpeed_Cache_Optimize::need_db() ) {
			$this->_create_tb_cssjs() ;
		}

		// Gravatar
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_DISCUSS_AVATAR_CACHE ) ) {
			$this->_create_tb_avatar() ;
		}

		// Image optm is a bit different. Only trigger creation when sending requests. Drop when destroying.
		// if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_IMG_OPTM_AUTO ) ) {
		// 	$this->create_tb_img_optm() ;
		// }
	}

	/**
	 * Upgrade conf to latest format version from previous versions
	 *
	 * NOTE: Only for v3.0+
	 *
	 * @since 3.0
	 * @access public
	 */
	public function conf_upgrade( $ver )
	{
		// Skip count check if `Use Primary Site Configurations` is on
		// Deprecated since v3.0 as network primary site didn't override the subsites conf yet
		// if ( ! is_main_site() && ! empty ( $this->_site_options[ self::NETWORK_O_USE_PRIMARY ] ) ) {
		// 	return ;
		// }

		require_once LSCWP_DIR . 'inc/data.upgrade.func.php' ;

		foreach ( $this->_db_updater as $k => $v ) {
			if ( version_compare( $ver, $k, '<' ) ) {
				// run each callback
				foreach ( $v as $v2 ) {
					LiteSpeed_Cache_Log::debug( "[Data] Updating [ori_v] $ver \t[to] $k \t[func] $v2" ) ;
					call_user_func( $v2 ) ;
				}
			}
		}

		// Reload options
		LiteSpeed_Cache_Config::get_instance()->load_options() ;

		$this->correct_tb_existance() ;

		// Update version to latest
		delete_option( LiteSpeed_Cache_Const::conf_name( LiteSpeed_Cache_Const::_VERSION ) ) ;
		add_option( LiteSpeed_Cache_Const::conf_name( LiteSpeed_Cache_Const::_VERSION ), LiteSpeed_Cache::PLUGIN_VERSION ) ;

		LiteSpeed_Cache_Log::debug( '[Data] Updated version to ' . LiteSpeed_Cache::PLUGIN_VERSION ) ;

		! defined( 'LSWCP_EMPTYCACHE') && define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;

		LiteSpeed_Cache_Utility::version_check( 'upgrade' ) ;
	}

	/**
	 * Upgrade site conf to latest format version from previous versions
	 *
	 * NOTE: Only for v3.0+
	 *
	 * @since 3.0
	 * @access public
	 */
	public function conf_site_upgrade( $ver )
	{
		require_once LSCWP_DIR . 'inc/data.upgrade.func.php' ;

		foreach ( $this->_db_site_updater as $k => $v ) {
			if ( version_compare( $ver, $k, '<' ) ) {
				// run each callback
				foreach ( $v as $v2 ) {
					LiteSpeed_Cache_Log::debug( "[Data] Updating site [ori_v] $ver \t[to] $k \t[func] $v2" ) ;
					call_user_func( $v2 ) ;
				}
			}
		}

		// Reload options
		LiteSpeed_Cache_Config::get_instance()->load_site_options() ;

		delete_site_option( LiteSpeed_Cache_Const::conf_name( LiteSpeed_Cache_Const::_VERSION ) ) ;
		add_site_option( LiteSpeed_Cache_Const::conf_name( LiteSpeed_Cache_Const::_VERSION ), LiteSpeed_Cache::PLUGIN_VERSION ) ;

		LiteSpeed_Cache_Log::debug( '[Data] Updated site_version to ' . LiteSpeed_Cache::PLUGIN_VERSION ) ;

		! defined( 'LSWCP_EMPTYCACHE') && define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
		LiteSpeed_Cache_Purge::purge_all() ;
	}

	/**
	 * Upgrade the conf to v3.0 from previous v3.0- data
	 *
	 * NOTE: Only for v3.0-
	 *
	 * @since 3.0
	 * @access public
	 */
	public function try_upgrade_conf_3_0()
	{
		$previous_options = get_option( 'litespeed-cache-conf' ) ;
		if ( ! $previous_options ) {
			return ;
		}

		$ver = $previous_options[ 'version' ] ;

		! defined( 'LSCWP_CUR_V' ) && define( 'LSCWP_CUR_V', $ver ) ;

		LiteSpeed_Cache_Log::debug( '[Data] Upgrading previous settings [from] ' . $ver . ' [to] v3.0' ) ;

		require_once LSCWP_DIR . 'inc/data.upgrade.func.php' ;

		// Here inside will update the version to v3.0
		litespeed_update_3_0( $ver ) ;

		LiteSpeed_Cache_Log::debug( '[Data] Upgraded to v3.0' ) ;

		// Upgrade from 3.0 to latest version
		$ver = '3.0' ;
		if ( LiteSpeed_Cache::PLUGIN_VERSION != $ver ) {
			$this->conf_upgrade( $ver ) ;
		}
		else {
			// Reload options
			LiteSpeed_Cache_Config::get_instance()->load_options() ;

			$this->correct_tb_existance() ;

			! defined( 'LSWCP_EMPTYCACHE') && define( 'LSWCP_EMPTYCACHE', true ) ;// clear all sites caches
			LiteSpeed_Cache_Purge::purge_all() ;

			LiteSpeed_Cache_Utility::version_check( 'upgrade' ) ;
		}
	}

	/**
	 * Get img_optm table name
	 *
	 * @since  2.0
	 * @access public
	 */
	public static function tb_img_optm()
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
	public static function tb_cssjs()
	{
		global $wpdb ;
		return $wpdb->prefix . self::TB_CSSJS ;
	}

	/**
	 * Get avatar table
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function tb_avatar()
	{
		global $wpdb ;
		return $wpdb->prefix . self::TB_AVATAR ;
	}

	/**
	 * Check if optimizer table exists or not
	 *
	 * @since  1.3.1.1
	 * @access public
	 */
	public static function tb_cssjs_exist()
	{
		global $wpdb ;

		$instance = self::get_instance() ;

		return $wpdb->get_var( "SHOW TABLES LIKE '$instance->_tb_cssjs'" ) ;
	}

	/**
	 * Check if image optm table existed or not
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function tb_img_optm_exist()
	{
		global $wpdb ;

		$instance = self::get_instance() ;

		return $wpdb->get_var( "SHOW TABLES LIKE '$instance->_tb_img_optm'" ) ;
	}

	/**
	 * Check if avatar table existed or not
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function tb_avatar_exist()
	{
		global $wpdb ;

		$instance = self::get_instance() ;

		return $wpdb->get_var( "SHOW TABLES LIKE '$instance->_tb_avatar'" ) ;
	}

	/**
	 * Get data structure of one table
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _tb_structure( $tb )
	{
		return Litespeed_File::read( LSCWP_DIR . 'inc/data_structure/' . $tb . '.sql' ) ;
	}

	/**
	 * Create img optm table and sync data from wp_postmeta
	 *
	 * @since  2.0
	 * @access public
	 */
	public function create_tb_img_optm()
	{
		if ( defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			return ;
		}
		define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;

		global $wpdb ;

		LiteSpeed_Cache_Log::debug2( '[Data] Checking img_optm table' ) ;

		// Check if table exists first
		if ( self::tb_img_optm_exist() ) {
			LiteSpeed_Cache_Log::debug2( '[Data] Existed' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Data] Creating img_optm table' ) ;

		$sql = sprintf(
			'CREATE TABLE IF NOT EXISTS `%1$s` (' . $this->_tb_structure( 'img_optm' ) . ') %2$s;',
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

	/**
	 * Create table cssjs
	 *
	 * @since  1.3.1
	 * @access private
	 */
	private function _create_tb_cssjs()
	{
		if ( defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			return ;
		}
		define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;

		global $wpdb ;

		LiteSpeed_Cache_Log::debug2( '[Data] Checking html optm table' ) ;

		// Check if table exists first
		if ( self::tb_cssjs_exist() ) {
			LiteSpeed_Cache_Log::debug2( '[Data] Existed' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Data] Creating html optm table' ) ;

		$sql = sprintf(
			'CREATE TABLE IF NOT EXISTS `%1$s` (' . $this->_tb_structure( 'optm' ) . ') %2$s;',
			$this->_tb_cssjs,
			$this->_charset_collate
		) ;

		$res = $wpdb->query( $sql ) ;
		if ( $res !== true ) {
			LiteSpeed_Cache_Log::debug( '[Data] Warning: Creating html_optm table failed!' ) ;
		}

	}

	/**
	 * Create avatar table
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _create_tb_avatar()
	{
		if ( defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			return ;
		}
		define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;

		global $wpdb ;

		LiteSpeed_Cache_Log::debug2( '[Data] Checking avatar table' ) ;

		// Check if table exists first
		if ( self::tb_avatar_exist() ) {
			LiteSpeed_Cache_Log::debug2( '[Data] Existed' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Data] Creating avatar table' ) ;

		$sql = sprintf(
			'CREATE TABLE IF NOT EXISTS `%1$s` (' . $this->_tb_structure( 'avatar' ) . ') %2$s;',
			$this->_tb_avatar,
			$this->_charset_collate
		) ;

		$res = $wpdb->query( $sql ) ;
		if ( $res !== true ) {
			LiteSpeed_Cache_Log::debug( '[Data] Warning: Creating avatar table failed!' ) ;
		}

	}

	/**
	 * Drop table img_optm
	 *
	 * @since  2.0
	 * @access public
	 */
	public function del_table_img_optm()
	{
		global $wpdb ;

		if ( ! self::tb_img_optm_exist() ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Data] Deleting img_optm table' ) ;

		$q = "DROP TABLE IF EXISTS $this->_tb_img_optm" ;
		$wpdb->query( $q ) ;

		delete_option( $this->_tb_img_optm ) ;
	}

	/**
	 * Drop generated tables
	 *
	 * @since  3.0
	 * @access public
	 */
	public function del_tables()
	{
		global $wpdb ;

		if ( self::tb_cssjs_exist() ) {
			LiteSpeed_Cache_Log::debug( '[Data] Deleting cssjs table' ) ;

			$q = "DROP TABLE IF EXISTS $this->_tb_cssjs" ;
			$wpdb->query( $q ) ;
		}

		// Deleting only can be done when destroy all optm images
		// $this->del_table_img_optm() ;

		if ( self::tb_avatar_exist() ) {
			LiteSpeed_Cache_Log::debug( '[Data] Deleting avatar table' ) ;

			$q = "DROP TABLE IF EXISTS $this->_tb_avatar" ;
			$wpdb->query( $q ) ;
		}

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

		$res = $wpdb->replace( $this->_tb_cssjs, $f ) ;

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

		$res = $wpdb->get_var( $wpdb->prepare( 'SELECT src FROM `' . $this->_tb_cssjs . '` WHERE `hash_name`=%s', $filename ) ) ;

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