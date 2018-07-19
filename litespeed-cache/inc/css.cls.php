<?php
/**
 * The optimize css class.
 *
 * @since      	2.3
 * @package  	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_CSS
{
	private static $_instance ;

	const TYPE_GENERATE_CRITICAL = 'generate_critical' ;

	const DB_CCSS_SUMMARY = 'litespeed-ccss-summary' ;

	/**
	 * Output critical css
	 *
	 * @since  1.3
	 * @since  2.3 Migrated from optimize.cls
	 * @access public
	 */
	public static function prepend_ccss( $html_head )
	{
		// Get critical css for current page
		// Note: need to consider mobile
		$rules = self::get_instance()->_ccss() ;

		// Append default critical css
		$rules .= get_option( LiteSpeed_Cache_Config::ITEM_OPTM_CSS ) ;

		$html_head = '<style id="litespeed-optm-css-rules">' . $rules . '</style>' . $html_head ;

		return $html_head ;
	}

	/**
	 * Check if there is a queue for cron or not
	 *
	 * @since  2.3
	 * @access public
	 */
	public static function has_queue()
	{
		$req_summary = self::get_summary() ;
		if ( ! empty( $req_summary[ 'queue' ] ) ) {
			return true ;
		}

		return false ;
	}

	/**
	 * Check if there is a ccss cache folder
	 *
	 * @since  2.3
	 * @access public
	 */
	public static function has_ccss_cache()
	{
		return is_dir( LSCWP_CONTENT_DIR . '/cache/ccss' ) ;
	}

	/**
	 * Save ccss summary
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _save_summary( $data )
	{
		update_option( self::DB_CCSS_SUMMARY, $data ) ;
	}

	/**
	 * Read last time generated info
	 *
	 * @since  2.3
	 * @access public
	 */
	public static function get_summary()
	{
		return get_option( self::DB_CCSS_SUMMARY, array() ) ;
	}

	/**
	 * Generate realpath of ccss
	 *
	 * @since  2.3
	 * @access public
	 */
	public static function ccss_realpath( $ccss_type )
	{
		return LSCWP_CONTENT_DIR . "/cache/ccss/$ccss_type.css" ;
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  2.3
	 * @access public
	 */
	public function rm_cache_folder()
	{
		if ( file_exists( LSCWP_CONTENT_DIR . '/cache/ccss' ) ) {
			Litespeed_File::rrmdir( LSCWP_CONTENT_DIR . '/cache/ccss' ) ;
		}
	}

	/**
	 * The critical css content of the current page
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _ccss()
	{
		// If don't need to generate CCSS, bypass
		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_OPTM_CCSS_GEN ) ) {
			LiteSpeed_Cache_Log::debug( '[CSS] bypassed ccss due to setting' ) ;
			return '' ;
		}

		$ccss_type = $this->_which_css() ;
		$ccss_file = self::ccss_realpath( $ccss_type ) ;

		if ( file_exists( $ccss_file ) ) {
			LiteSpeed_Cache_Log::debug2( '[CSS] existing ccss ' . $ccss_file ) ;
			return Litespeed_File::read( $ccss_file ) ;
		}

		// Check if is already in a request, bypass current one
		$req_summary = self::get_summary() ;
		if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
			return '' ;
		}

		global $wp ;
		$request_url = home_url( $wp->request ) ;

		// If generate in backend, log it and bypass
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_OPTM_CCSS_ASYNC ) ) {
			// Store it to prepare for cron
			if ( empty( $req_summary[ 'queue' ] ) ) {
				$req_summary[ 'queue' ] = array() ;
			}
			$req_summary[ 'queue' ][ $ccss_type ] = $request_url ;
			LiteSpeed_Cache_Log::debug( '[CSS] Added queue [type] ' . $ccss_type . ' [url] ' . $request_url ) ;

			$this->_save_summary( $req_summary ) ;
			return '' ;
		}

		// generate on the fly
		return $this->_generate_ccss( $request_url, $ccss_type ) ;
	}

	/**
	 * Cron ccss generation
	 *
	 * @since  2.3
	 * @access private
	 */
	public static function cron_ccss( $continue = false )
	{
		$req_summary = self::get_summary() ;
		if ( empty( $req_summary[ 'queue' ] ) ) {
			return ;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
				return ;
			}
		}

		foreach ( $req_summary[ 'queue' ] as $k => $v ) {
			LiteSpeed_Cache_Log::debug( '[CSS] cron job [type] ' . $k . ' [url] ' . $v ) ;

			self::get_instance()->_generate_ccss( $v, $k ) ;

			// only request first one
			if ( ! $continue ) {
				return ;
			}
		}
	}

	/**
	 * Send to LiteSpeed CSS API to generate CSS
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _generate_ccss( $request_url, $ccss_type )
	{
		$req_summary = self::get_summary() ;

		$ccss_file = self::ccss_realpath( $ccss_type ) ;

		// Update css request status
		$req_summary[ 'curr_request' ] = time() ;
		$this->_save_summary( $req_summary ) ;

		// Generate critical css
		$url = 'http://ccss.api.litespeedtech.com' ;

		$data = array(
			'home_url'	=> home_url(),
			'url'		=> $request_url,
			'ccss_type'	=> $ccss_type,
		) ;

		LiteSpeed_Cache_Log::debug( '[CSS] posting to : ' . $url, $data ) ;

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

		// Save summary data
		$req_summary[ 'last_spent' ] = time() - $req_summary[ 'curr_request' ] ;
		$req_summary[ 'last_request' ] = $req_summary[ 'curr_request' ] ;
		$req_summary[ 'curr_request' ] = 0 ;
		if ( empty( $req_summary[ 'ccss_type_history' ] ) ) {
			$req_summary[ 'ccss_type_history' ] = array() ;
		}
		$req_summary[ 'ccss_type_history' ][ $ccss_type ] = $request_url ;
		unset( $req_summary[ 'queue' ][ $ccss_type ] ) ;

		$this->_save_summary( $req_summary ) ;

		LiteSpeed_Cache_Log::debug( '[CSS] saved ccss ' . $ccss_file ) ;

		LiteSpeed_Cache_Log::debug2( '[CSS] ccss con: ' . $json[ 'ccss' ] ) ;

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
		if ( is_404() ) {
			$css = '404' ;
		}
		elseif ( is_singular() ) {
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

		return $css ;
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
				self::cron_ccss( true ) ;
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
