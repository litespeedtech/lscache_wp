<?php
/**
 * The REST related class.
 *
 * @since      	2.9.4
 */
defined( 'WPINC' ) || exit ;

class LiteSpeed_Cache_REST
{
	private static $_instance ;

	private $_internal_rest_status = false ;

	/**
	 * Constructor of ESI
	 *
	 * @since    2.9.4
	 * @access private
	 */
	private function __construct()
	{
		// Hook to internal REST call
		add_filter( 'rest_request_before_callbacks', array( $this, 'set_internal_rest_on' ) ) ;
		add_filter( 'rest_request_after_callbacks', array( $this, 'set_internal_rest_off' ) ) ;

	}

	/**
	 * Set internal REST tag to ON
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function set_internal_rest_on( $not_used = null )
	{
		$this->_internal_rest_status = true ;
		LiteSpeed_Cache_Log::debug2( '[REST] ✅ Internal REST ON [filter] rest_request_before_callbacks' ) ;

		return $not_used ;
	}

	/**
	 * Set internal REST tag to OFF
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function set_internal_rest_off( $not_used = null )
	{
		$this->_internal_rest_status = false ;
		LiteSpeed_Cache_Log::debug2( '[REST] ❎ Internal REST OFF [filter] rest_request_after_callbacks' ) ;

		return $not_used ;
	}

	/**
	 * Get internal REST tag
	 *
	 * @since  2.9.4
	 * @access public
	 */
	public function is_internal_rest()
	{
		return $this->_internal_rest_status ;
	}

	/**
	 * Check if an URL or current page is REST req or not
	 *
	 * @since  2.9.3
	 * @since  2.9.4 Moved here from LiteSpeed_Cache_Utility, dropped static
	 * @access public
	 */
	public function is_rest( $url = false )
	{
		// For WP 4.4.0- compatibility
		if ( ! function_exists( 'rest_get_url_prefix' ) ) {
			return defined( 'REST_REQUEST' ) && REST_REQUEST ;
		}

		$prefix = rest_get_url_prefix() ;

		// Case #1: After WP_REST_Request initialisation
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true ;
		}

		// Case #2: Support "plain" permalink settings
		if ( isset( $_GET[ 'rest_route' ] ) && strpos( trim( $_GET[ 'rest_route' ], '\\/' ), $prefix , 0 ) === 0 ) {
			return true ;
		}

		if ( ! $url ) {
			return false ;
		}

		// Case #3: URL Path begins with wp-json/ (REST prefix) Safe for subfolder installation
		$rest_url = wp_parse_url( site_url( $prefix ) ) ;
		$current_url = wp_parse_url( $url ) ;
		// LiteSpeed_Cache_Log::debug( '[Util] is_rest check [base] ', $rest_url ) ;
		// LiteSpeed_Cache_Log::debug( '[Util] is_rest check [curr] ', $current_url ) ;
		// LiteSpeed_Cache_Log::debug( '[Util] is_rest check [curr2] ', wp_parse_url( add_query_arg( array( ) ) ) ) ;
		return strpos( $current_url[ 'path' ], $rest_url[ 'path' ] ) === 0 ;
	}


	/**
	 * Get the current instance object.
	 *
	 * @since 2.9
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
