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

	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 * @access protected
	 */
	protected function __construct()
	{
		$this->_summary = self::get_summary();
	}

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
		$rules .= Conf::val( Base::O_OPTM_CCSS_CON ) ;

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
		$this->_summary[ 'queue' ] = array() ;
		$this->_summary[ 'curr_request' ] = 0 ;
		self::save_summary();

		Debug2::debug2( '[CSS] Cleared ccss queue' ) ;
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
		if ( ! Conf::val( Base::O_OPTM_CCSS_GEN ) ) {
			Debug2::debug( '[CSS] bypassed ccss due to setting' ) ;
			return '' ;
		}

		$ccss_type = $this->_which_css() ;
		$ccss_file = $this->_ccss_realpath( $ccss_type ) ;

		if ( file_exists( $ccss_file ) ) {
			Debug2::debug2( '[CSS] existing ccss ' . $ccss_file ) ;
			return File::read( $ccss_file ) ;
		}

		// Check if is already in a request, bypass current one
		if ( ! empty( $this->_summary[ 'curr_request' ] ) && time() - $this->_summary[ 'curr_request' ] < 300 ) {
			Debug2::debug( '[CCSS] Last request not done' );
			return '' ;
		}

		global $wp ;
		$request_url = home_url( $wp->request ) ;

		// If generate in backend, log it and bypass
		if ( Conf::val( Base::O_OPTM_CCSS_ASYNC ) ) {
			// Store it to prepare for cron
			if ( empty( $this->_summary[ 'queue' ] ) ) {
				$this->_summary[ 'queue' ] = array() ;
			}
			$this->_summary[ 'queue' ][ $ccss_type ] = array(
				'url'			=> $request_url,
				'user_agent'	=> $_SERVER[ 'HTTP_USER_AGENT' ],
				'is_mobile'		=> $this->_separate_mobile_ccss(),
			) ;// Current UA will be used to request
			Debug2::debug( '[CSS] Added queue [type] ' . $ccss_type . ' [url] ' . $request_url . ' [UA] ' . $_SERVER[ 'HTTP_USER_AGENT' ] ) ;

			self::save_summary();
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
		return apply_filters( 'litespeed_is_mobile', false ) && Conf::val( Base::O_CACHE_MOBILE ) ;
	}

	/**
	 * Cron ccss generation
	 *
	 * @since  2.3
	 * @access private
	 */
	public static function cron_ccss( $continue = false )
	{
		$_instance = self::get_instance();
		if ( empty( $_instance->_summary[ 'queue' ] ) ) {
			return ;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( ! empty( $_instance->_summary[ 'curr_request' ] ) && time() - $_instance->_summary[ 'curr_request' ] < 300 ) {
				Debug2::debug( '[CCSS] Last request not done' );
				return ;
			}
		}

		foreach ( $_instance->_summary[ 'queue' ] as $k => $v ) {
			Debug2::debug( '[CSS] cron job [type] ' . $k . ' [url] ' . $v[ 'url' ] . ( $v[ 'is_mobile' ] ? ' 📱 ' : '' ) . ' [UA] ' . $v[ 'user_agent' ] ) ;

			$_instance->_generate_ccss( $v[ 'url' ], $k, $v[ 'user_agent' ], $v[ 'is_mobile' ] ) ;

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
		// Check if has credit to push
		$allowance = Cloud::get_instance()->allowance( Cloud::SVC_CCSS );
		if ( ! $allowance ) {
			Debug2::debug( '[CCSS] ❌ No credit' );
			Admin_Display::error( Error::msg( 'lack_of_quota' ) );
			return;
		}

		$ccss_file = $this->_ccss_realpath( $ccss_type ) ;

		// Update css request status
		$this->_summary[ 'curr_request' ] = time() ;
		self::save_summary();

		// Generate critical css
		$data = array(
			'url'			=> $request_url,
			'ccss_type'		=> $ccss_type,
			'user_agent'	=> $user_agent,
			'is_mobile'		=> $is_mobile ? 1 : 0,
		) ;

		Debug2::debug( '[CSS] Generating: ', $data ) ;

		$json = Cloud::post( Cloud::SVC_CCSS, $data, 180 ) ;
		if ( ! is_array( $json ) ) {
			return false;
		}

		if ( empty( $json[ 'ccss' ] ) ) {
			Debug2::debug( '[CSS] ❌ empty ccss' ) ;
			$this->_popup_and_save( $ccss_type, $request_url );
			return false ;
		}

		// Add filters
		$ccss = apply_filters( 'litespeed_ccss', $json[ 'ccss' ], $ccss_type ) ;

		// Write to file
		File::save( $ccss_file, $ccss, true ) ;

		// Save summary data
		$this->_summary[ 'last_spent' ] = time() - $this->_summary[ 'curr_request' ] ;
		$this->_summary[ 'last_request' ] = $this->_summary[ 'curr_request' ] ;
		$this->_summary[ 'curr_request' ] = 0 ;
		$this->_popup_and_save( $ccss_type, $request_url );

		Debug2::debug( '[CSS] saved ccss ' . $ccss_file ) ;

		Debug2::debug2( '[CSS] ccss con: ' . $ccss ) ;

		return $ccss ;
	}

	/**
	 * Pop up the current request and save
	 *
	 * @since  3.0
	 */
	private function _popup_and_save( $ccss_type, $request_url )
	{
		if ( empty( $this->_summary[ 'ccss_type_history' ] ) ) {
			$this->_summary[ 'ccss_type_history' ] = array() ;
		}
		$this->_summary[ 'ccss_type_history' ][ $ccss_type ] = $request_url ;
		unset( $this->_summary[ 'queue' ][ $ccss_type ] ) ;

		self::save_summary();
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
		$separate_posttypes = Conf::val( Base::O_OPTM_CCSS_SEP_POSTTYPE ) ;
		if ( ! empty( $separate_posttypes ) && in_array( $css, $separate_posttypes ) ) {
			Debug2::debug( '[CSS] Hit separate posttype setting [type] ' . $css ) ;
			$unique = true ;
		}

		$separate_uri = Conf::val( Base::O_OPTM_CCSS_SEP_URI ) ;
		if ( ! empty( $separate_uri ) ) {
			$result =  Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $separate_uri ) ;
			if ( $result ) {
				Debug2::debug( '[CSS] Hit separate URI setting: ' . $result ) ;
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
