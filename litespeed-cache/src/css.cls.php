<?php
/**
 * The optimize css class.
 *
 * @since      	2.3
 * @package  	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class CSS extends Base
{
	protected static $_instance ;

	const TYPE_GENERATE_CRITICAL = 'generate_critical' ;

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
		$rules .= Core::config( Base::O_OPTM_CCSS_CON ) ;

		$html_head = '<style id="litespeed-optm-css-rules">' . $rules . '</style>' . $html_head ;

		return $html_head ;
	}

	/**
	 * Check if there is a ccss cache folder
	 *
	 * @since  2.3
	 * @access public
	 */
	public static function has_ccss_cache()
	{
		return is_dir( LITESPEED_STATIC_DIR . '/ccss' ) ;
	}

	/**
	 * Generate realpath of ccss
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _ccss_realpath( $ccss_type )
	{
		return LITESPEED_STATIC_DIR . "/ccss/$ccss_type.css" ;
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  2.3
	 * @access public
	 */
	public function rm_cache_folder()
	{
		if ( file_exists( LITESPEED_STATIC_DIR . '/ccss' ) ) {
			File::rrmdir( LITESPEED_STATIC_DIR . '/ccss' ) ;
		}

		// Clear CCSS in queue too
		$req_summary = self::get_summary() ;
		$req_summary[ 'queue' ] = array() ;
		$req_summary[ 'curr_request' ] = 0 ;
		self::save_summary( $req_summary ) ;

		Log::debug2( '[CSS] Cleared ccss queue' ) ;
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
		if ( ! Core::config( Base::O_OPTM_CCSS_GEN ) ) {
			Log::debug( '[CSS] bypassed ccss due to setting' ) ;
			return '' ;
		}

		$ccss_type = $this->_which_css() ;
		$ccss_file = $this->_ccss_realpath( $ccss_type ) ;

		if ( file_exists( $ccss_file ) ) {
			Log::debug2( '[CSS] existing ccss ' . $ccss_file ) ;
			return File::read( $ccss_file ) ;
		}

		// Check if is already in a request, bypass current one
		$summary = self::get_summary() ;
		if ( ! empty( $summary[ 'curr_request' ] ) && time() - $summary[ 'curr_request' ] < 300 ) {
			return '' ;
		}

		global $wp ;
		$request_url = home_url( $wp->request ) ;

		// If generate in backend, log it and bypass
		if ( Core::config( Base::O_OPTM_CCSS_ASYNC ) ) {
			// Store it to prepare for cron
			if ( empty( $summary[ 'queue' ] ) ) {
				$summary[ 'queue' ] = array() ;
			}
			$summary[ 'queue' ][ $ccss_type ] = array(
				'url'			=> $request_url,
				'user_agent'	=> $_SERVER[ 'HTTP_USER_AGENT' ],
				'is_mobile'		=> $this->_separate_mobile_ccss(),
			) ;// Current UA will be used to request
			Log::debug( '[CSS] Added queue [type] ' . $ccss_type . ' [url] ' . $request_url . ' [UA] ' . $_SERVER[ 'HTTP_USER_AGENT' ] ) ;

			self::save_summary( $summary ) ;
			return '' ;
		}

		// generate on the fly
		return $this->_generate_ccss( $request_url, $ccss_type, $_SERVER[ 'HTTP_USER_AGENT' ], $this->_separate_mobile_ccss() ) ;
	}

	/**
	 * Check if need to separate ccss for mobile
	 *
	 * @since  2.6.4
	 * @access private
	 */
	private function _separate_mobile_ccss()
	{
		return wp_is_mobile() && Core::config( Base::O_CACHE_MOBILE ) ;
	}

	/**
	 * Cron ccss generation
	 *
	 * @since  2.3
	 * @access private
	 */
	public static function cron_ccss( $continue = false )
	{
		$summary = self::get_summary() ;
		if ( empty( $summary[ 'queue' ] ) ) {
			return ;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( $summary && ! empty( $summary[ 'curr_request' ] ) && time() - $summary[ 'curr_request' ] < 300 ) {
				return ;
			}
		}

		foreach ( $summary[ 'queue' ] as $k => $v ) {
			if ( ! is_array( $v ) ) {// Backward compatibility for v2.6.4-
				Log::debug( '[CSS] previous v2.6.4- data' ) ;
				return ;
			}

			Log::debug( '[CSS] cron job [type] ' . $k . ' [url] ' . $v[ 'url' ] . ( $v[ 'is_mobile' ] ? ' 📱 ' : '' ) . ' [UA] ' . $v[ 'user_agent' ] ) ;

			self::get_instance()->_generate_ccss( $v[ 'url' ], $k, $v[ 'user_agent' ], $v[ 'is_mobile' ] ) ;

			// only request first one
			if ( ! $continue ) {
				return ;
			}
		}
	}

	/**
	 * Send to LiteSpeed CCSS API to generate CCSS
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _generate_ccss( $request_url, $ccss_type, $user_agent, $is_mobile )
	{
		$summary = self::get_summary() ;

		$ccss_file = $this->_ccss_realpath( $ccss_type ) ;

		// Update css request status
		$summary[ 'curr_request' ] = time() ;
		self::save_summary( $summary ) ;

		// Generate critical css
		$data = array(
			'url'			=> $request_url,
			'ccss_type'		=> $ccss_type,
			'user_agent'	=> $user_agent,
			'is_mobile'		=> $is_mobile ? 1 : 0,
		) ;

		Log::debug( '[CSS] Generating: ', $data ) ;

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 180 ) ;

		if ( empty( $json[ 'ccss' ] ) ) {
			Log::debug( '[CSS] empty ccss' ) ;
			return false ;
		}

		// Add filters
		$ccss = apply_filters( 'litespeed_ccss', $json[ 'ccss' ], $ccss_type ) ;

		// Write to file
		File::save( $ccss_file, $ccss, true ) ;

		// Save summary data
		$summary[ 'last_spent' ] = time() - $summary[ 'curr_request' ] ;
		$summary[ 'last_request' ] = $summary[ 'curr_request' ] ;
		$summary[ 'curr_request' ] = 0 ;
		if ( empty( $summary[ 'ccss_type_history' ] ) ) {
			$summary[ 'ccss_type_history' ] = array() ;
		}
		$summary[ 'ccss_type_history' ][ $ccss_type ] = $request_url ;
		unset( $summary[ 'queue' ][ $ccss_type ] ) ;

		self::save_summary( $summary ) ;

		Log::debug( '[CSS] saved ccss ' . $ccss_file ) ;

		Log::debug2( '[CSS] ccss con: ' . $ccss ) ;

		return $ccss ;
	}

	/**
	 * The critical css file for current page
	 *
	 * @since  2.3
	 * @access private
	 */
	private function _which_css()
	{
		$css = Utility::page_type() ;

		$unique = false ;

		// Check if in separate css type option
		$separate_posttypes = Core::config( Base::O_OPTM_CCSS_SEP_POSTTYPE ) ;
		if ( ! empty( $separate_posttypes ) && in_array( $css, $separate_posttypes ) ) {
			Log::debug( '[CSS] Hit separate posttype setting [type] ' . $css ) ;
			$unique = true ;
		}

		$separate_uri = Core::config( Base::O_OPTM_CCSS_SEP_URI ) ;
		if ( ! empty( $separate_uri ) ) {
			$result =  Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $separate_uri ) ;
			if ( $result ) {
				Log::debug( '[CSS] Hit separate URI setting: ' . $result ) ;
				$unique = true ;
			}
		}

		if ( $unique ) {
			$css .= '-' . md5( $_SERVER[ 'REQUEST_URI' ] ) ;
		}

		if ( $this->_separate_mobile_ccss() ) {
			$css .= '.mobile' ;
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

		$type = Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_GENERATE_CRITICAL :
				self::cron_ccss( true ) ;
				break ;

			default:
				break ;
		}

		Admin::redirect() ;
	}

}
