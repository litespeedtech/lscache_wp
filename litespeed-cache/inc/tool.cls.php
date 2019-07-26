<?php
/**
 * The tools
 *
 * @since      	3.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
defined( 'WPINC' ) || exit ;

class LiteSpeed_Cache_Tool
{
	private static $_instance ;

	private $_conf_heartbeat_front ;
	private $_conf_heartbeat_front_ttl ;
	private $_conf_heartbeat_back ;
	private $_conf_heartbeat_back_ttl ;
	private $_conf_heartbeat_editor ;
	private $_conf_heartbeat_editor_ttl ;

	/**
	 * Init
	 *
	 * @since  3.0
	 * @access private
	 */
	private function __construct()
	{
		$this->_conf_heartbeat_front 		= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_FRONT ) ;
		$this->_conf_heartbeat_front_ttl 	= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_FRONT_TTL ) ;
		$this->_conf_heartbeat_back 		= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_BACK ) ;
		$this->_conf_heartbeat_back_ttl 	= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_BACK_TTL ) ;
		$this->_conf_heartbeat_editor 		= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_EDITOR ) ;
		$this->_conf_heartbeat_editor_ttl 	= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MISC_HEARTBEAT_EDITOR_TTL ) ;
	}

	/**
	 * Heartbeat Control
	 *
	 * NOTE: since WP4.9, there could be a core bug that sometimes the hook is not working.
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function heartbeat()
	{
		$instance = self::get_instance() ;

		add_action( 'wp_enqueue_scripts', array( $instance, 'heartbeat_frontend' ) ) ;
		add_action( 'admin_enqueue_scripts', array( $instance, 'heartbeat_backend' ) ) ;
		add_filter( 'heartbeat_settings', array( $instance, 'heartbeat_settings' ) ) ;
	}

	/**
	 * Heartbeat Control frontend control
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_frontend()
	{
		if ( ! $this->_conf_heartbeat_front ) {
			return ;
		}

		if ( ! $this->_conf_heartbeat_front_ttl ) {
			wp_deregister_script( 'heartbeat' ) ;
			LiteSpeed_Cache_Log::debug( '[Tool] Deregistered frontend heartbeat' ) ;
		}
	}

	/**
	 * Heartbeat Control backend control
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_backend()
	{
		if ( $this->_is_editor() ) {
			if ( ! $this->_conf_heartbeat_editor ) {
				return ;
			}

			if ( ! $this->_conf_heartbeat_editor_ttl ) {
				wp_deregister_script( 'heartbeat' ) ;
				LiteSpeed_Cache_Log::debug( '[Tool] Deregistered editor heartbeat' ) ;
			}
		}
		else {
			if ( ! $this->_conf_heartbeat_back ) {
				return ;
			}

			if ( ! $this->_conf_heartbeat_back_ttl ) {
				wp_deregister_script( 'heartbeat' ) ;
				LiteSpeed_Cache_Log::debug( '[Tool] Deregistered backend heartbeat' ) ;
			}
		}

	}

	/**
	 * Heartbeat Control settings
	 *
	 * @since  3.0
	 * @access public
	 */
	public function heartbeat_settings( $settings )
	{
		// Check editor first to make frontend editor valid too
		if ( $this->_is_editor() ) {
			if ( $this->_conf_heartbeat_editor ) {
				$settings[ 'interval' ] = $this->_conf_heartbeat_editor_ttl ;
				LiteSpeed_Cache_Log::debug( '[Tool] Heartbeat interval set to ' . $this->_conf_heartbeat_editor_ttl ) ;
			}
		}
		elseif ( ! is_admin() ) {
			if ( $this->_conf_heartbeat_front ) {
				$settings[ 'interval' ] = $this->_conf_heartbeat_front_ttl ;
				LiteSpeed_Cache_Log::debug( '[Tool] Heartbeat interval set to ' . $this->_conf_heartbeat_front_ttl ) ;
			}
		}
		else {
			if ( $this->_conf_heartbeat_back ) {
				$settings[ 'interval' ] = $this->_conf_heartbeat_back_ttl ;
				LiteSpeed_Cache_Log::debug( '[Tool] Heartbeat interval set to ' . $this->_conf_heartbeat_back_ttl ) ;
			}
		}
		return $settings ;
	}

	/**
	 * If is in editor
	 *
	 * @since  3.0
	 * @access public
	 */
	private function _is_editor()
	{
		$res = is_admin() && LiteSpeed_Cache_Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], array( 'post.php', 'post-new.php' ) ) ;

		return apply_filters( 'litespeed_is_editor', $res ) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 3.0
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