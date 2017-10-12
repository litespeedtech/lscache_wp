<?php

/**
 * The frontend GUI class.
 *
 * @since      	1.3
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_GUI
{
	private static $_instance ;

	private static $_clean_counter = 0 ;

	/**
	 * Init
	 *
	 * @since  1.3
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
	 * @since  1.3
	 * @access private
	 */
	public function frontend_enqueue_style()
	{
		wp_enqueue_style( LiteSpeed_Cache::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'css/litespeed.css', array(), LiteSpeed_Cache::PLUGIN_VERSION, 'all' ) ;
	}

	/**
	 * Load frontend menu shortcut
	 *
	 * @since  1.3
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

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-single-action',
			'title'		=> __( 'Mark this page as ', 'litespeed-cache' ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-single-action',
			'id'		=> 'litespeed-single-noncache',
			'title'		=> __( 'Non cacheable', 'litespeed-cache' ),
			'href'		=> LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_FRONT_EXCLUDE, false, 'type=nocache', true ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-single-action',
			'id'		=> 'litespeed-single-private',
			'title'		=> __( 'Private cache', 'litespeed-cache' ),
			'href'		=> LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_FRONT_EXCLUDE, false, 'type=private', true ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-single-action',
			'id'		=> 'litespeed-single-nonoptimize',
			'title'		=> __( 'No optimization', 'litespeed-cache' ),
			'href'		=> LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_FRONT_EXCLUDE, false, 'type=nonoptimize', true ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-single-action',
			'id'		=> 'litespeed-single-more',
			'title'		=> __( 'More settings', 'litespeed-cache' ),
			'href'		=> get_admin_url( null, 'admin.php?page=lscache-settings#excludes' ),
		) );
	}

	/**
	 * Clean wrapper from buffer
	 *
	 * @since  1.4
	 * @access public
	 */
	public static function clean_wrapper( $buffer )
	{
		if ( self::$_clean_counter < 1 ) {
			LiteSpeed_Cache_Log::debug2( "GUI bypassed by no counter" ) ;
			return $buffer ;
		}

		LiteSpeed_Cache_Log::debug2( "GUI start cleaning counter " . self::$_clean_counter ) ;

		for ( $i = 1 ; $i <= self::$_clean_counter ; $i ++ ) {
			// If miss beginning
			$start = strpos( $buffer, self::clean_wrapper_begin( $i ) ) ;
			if ( $start === false ) {
				$buffer = str_replace( self::clean_wrapper_end( $i ), '', $buffer ) ;
				LiteSpeed_Cache_Log::debug2( "GUI lost beginning wrapper $i" ) ;
				continue;
			}

			// If miss end
			$end_wrapper = self::clean_wrapper_end( $i ) ;
			$end = strpos( $buffer, $end_wrapper ) ;
			if ( $end === false ) {
				$buffer = str_replace( self::clean_wrapper_begin( $i ), '', $buffer ) ;
				LiteSpeed_Cache_Log::debug2( "GUI lost ending wrapper $i" ) ;
				continue;
			}

			// Now replace wrapped content
			$buffer = substr_replace( $buffer, '', $start, $end - $start + strlen( $end_wrapper ) ) ;
			LiteSpeed_Cache_Log::debug2( "GUI cleaned wrapper $i" ) ;
		}

		return $buffer ;
	}

	/**
	 * Display a to-be-removed html wrapper
	 *
	 * @since  1.4
	 * @access public
	 */
	public static function clean_wrapper_begin( $counter = false )
	{
		if ( $counter === false ) {
			self::$_clean_counter ++ ;
			$counter = self::$_clean_counter ;
			LiteSpeed_Cache_Log::debug( "GUI clean wrapper $counter begin" ) ;
		}
		return '<!-- LiteSpeed To Be Removed begin ' . $counter . ' -->' ;
	}

	/**
	 * Display a to-be-removed html wrapper
	 *
	 * @since  1.4
	 * @access public
	 */
	public static function clean_wrapper_end( $counter = false )
	{
		if ( $counter === false ) {
			$counter = self::$_clean_counter ;
			LiteSpeed_Cache_Log::debug( "GUI clean wrapper $counter end" ) ;
		}
		return '<!-- LiteSpeed To Be Removed end ' . $counter . ' -->' ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.3
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


