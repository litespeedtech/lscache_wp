<?php

/**
 * The frontend GUI class.
 *
 * @since      1.2.4
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_GUI
{
	private static $_instance ;

	/**
	 * Init
	 *
	 * @since  1.2.4
	 * @access private
	 */
	private function __construct()
	{
		if ( ! is_admin() && is_admin_bar_showing() && current_user_can( 'manage_options' ) ) {
			LiteSpeed_Cache_Log::debug( 'GUI init' ) ;
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_style' ) ) ;
			add_action( 'admin_bar_menu', array( $this, 'frontend_shortcut' ), 95 ) ;
		}
	}

	/**
	 * Load frontend menu shortcut
	 *
	 * @since  1.2.4
	 * @access private
	 */
	public function frontend_enqueue_style()
	{
		wp_enqueue_style( LiteSpeed_Cache::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'css/litespeed.css', array(), LiteSpeed_Cache::PLUGIN_VERSION, 'all' ) ;
	}

	/**
	 * Load frontend menu shortcut
	 *
	 * @since  1.2.4
	 * @access private
	 */
	public function frontend_shortcut()
	{

		global $wp_admin_bar ;
		$wp_admin_bar->add_menu( array(
			'id'	=> 'litespeed-menu',
			'title'	=> '<span class="ab-icon"></span>',
			'href'	=> get_admin_url( null, 'admin.php?page=lscache-settings' ),
			'meta'	=> array( 'tabindex' => 0, 'class' => 'litespeed-top-toolbar' ),
		) ) ;
		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-single',
			'title'		=> __( 'Purge this page', 'litespeed-cache' ),
			'href'		=> LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_FRONT_PURGE, false, false, true ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );


	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.2.4
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}


