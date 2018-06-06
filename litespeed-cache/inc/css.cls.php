<?php

/**
 * The optimize css class.
 *
 * @since      	2.3
 * @package  	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_CSS
{
	private static $_instance ;

	const TYPE_GENERATE_CRITICAL = 'generate_critical' ;

	const DB_CSS_GENERATED_SUMMARY = 'litespeed-css-generated-summary' ;

	/**
	 * Read last time generated info
	 *
	 * @since  2.3
	 * @access public
	 */
	public function last_generated()
	{
		$summary = get_option( self::DB_CSS_GENERATED_SUMMARY, array() ) ;

		return $summary ;
	}

	/**
	 * Output critical css
	 *
	 * @since  1.3
	 * @since  2.3 Migrated from optimize.cls
	 * @access public
	 */
	public static function prepend_critical_css( $html_head )
	{
		// Get critical css for current page
		// Note: need to consider mobile
		$rules = self::get_instance()->_critical_css() ;

		// Append default critical css
		$rules .= get_option( LiteSpeed_Cache_Config::ITEM_OPTM_CSS ) ;

		$html_head = '<style id="litespeed-optm-css-rules">' . $rules . '</style>' . $html_head ;

		return $html_head ;
	}

	/**
	 * The critical css content of the current page
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _critical_css()
	{
		$ccss_file = $this->_which_css() ;

		if ( file_exists( $ccss_file ) ) {
			return Litespeed_File::read( $ccss_file ) ;
		}

		// Generate critical css
		$url = 'http://ccss.api.litespeedtech.com' ;
		LiteSpeed_Cache_Log::debug( '[CSS] posting to : ' . $url ) ;

		$data = array(
			'home_url' => home_url(),
			'url'	=> $_SERVER[ 'REQUEST_URI' ],
		) ;

		$param = array(
			'v'	=> LiteSpeed_Cache::PLUGIN_VERSION,
			'data' => $data,
		) ;

		$response = wp_remote_post( $url, array( 'body' => $param, 'timeout' => 15 ) ) ;

		// Parse response data
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			LiteSpeed_Cache_Log::debug( '[CSS] failed to post: ' . $error_message ) ;
			return false ;
		}

		$json = json_decode( $response[ 'body' ], true ) ;
		if ( ! is_array( $json ) ) {
			LiteSpeed_Cache_Log::debug( '[CSS] failed to decode post json: ' . $response[ 'body' ] ) ;
			return false ;
		}

		if ( ! empty( $json[ '_err' ] ) ) {
			LiteSpeed_Cache_Log::debug( '[CSS] _err: ' . $json[ '_err' ] ) ;
			return false ;
		}

		if ( empty( $json[ 'ccss' ] ) ) {
			LiteSpeed_Cache_Log::debug( '[CSS] empty ccss ' ) ;
			return false ;
		}

		// Write to file
		Litespeed_File::save( $ccss_file, $json[ 'ccss' ], true ) ;
		return $json[ 'ccss' ] ;
	}

	/**
	 * The critical css file for current page
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _which_css()
	{
		$css = 'default' ;
		if ( is_singular() ) {
			$css = get_post_type() ;
		}
		elseif ( is_home() && get_option( 'show_on_front' ) == 'page' ) {
			$css = 'home' ;
		}
		elseif ( is_front_page() ) {
			$css = 'front' ;
		}
		elseif ( is_tax() ) {
			$css = get_queried_object()->taxonomy ;
		}
		elseif ( is_category() ) {
			$css = 'category' ;
		}
		elseif ( is_tag() ) {
			$css = 'tag' ;
		}

		return LSCWP_CONTENT_DIR . "/cache/ccss/$css.css" ;
	}

	/**
	 * Send to LiteSpeed CSS API to generate CSS
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _generate_critical_css()
	{
		$url = 'https://ccss.api.litespeedtech.com' ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.3
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_GENERATE_CRITICAL :
				$instance->_generate_critical_css() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 2.3
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
