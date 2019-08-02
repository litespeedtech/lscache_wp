<?php
/**
 * The PlaceHolder class
 *
 * @since 		3.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
defined( 'WPINC' ) || exit ;

class LiteSpeed_Cache_Placeholder
{
	private static $_instance ;

	const TYPE_GENERATE = 'generate' ;
	const DB_SUMMARY = 'placeholder' ;

	private $_conf_placeholder_resp ;
	private $_conf_placeholder_resp_generator ;
	private $_conf_placeholder_resp_svg ;
	private $_conf_placeholder_resp_color ;
	private $_conf_placeholder_resp_async ;
	private $_placeholder_resp_dict = array() ;
	private $_ph_queue = array() ;

	/**
	 * Init
	 *
	 * @since  3.0
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( '[Placeholder] init' ) ;

		$this->_conf_placeholder_resp = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP ) ;
		$this->_conf_placeholder_resp_generator = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_GENERATOR ) ;
		$this->_conf_placeholder_resp_svg 	= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_SVG ) ;
		$this->_conf_placeholder_resp_async = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_ASYNC ) ;
		$this->_conf_placeholder_resp_color = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_COLOR ) ;
		$this->_conf_ph_default = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_LAZY_PLACEHOLDER ) ?: LITESPEED_PLACEHOLDER ;
	}

	/**
	 * Replace image with placeholder
	 *
	 * @since  3.0
	 * @access public
	 */
	public function replace( $html, $src, $size )
	{
		// Check if need to enable responsive placeholder or not
		$this_placeholder = $this->_placeholder( $src, $size ) ?: $this->_conf_ph_default ;

		$additional_attr = '' ;
		if ( $this_placeholder != $this->_conf_ph_default ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] Use resp placeholder [size] ' . $size ) ;
			$additional_attr = ' data-placeholder-resp="' . $size . '"' ;
		}

		$snippet = '<noscript>' . $html . '</noscript>' ;
		$html = str_replace( array( ' src=', ' srcset=', ' sizes=' ), array( ' data-src=', ' data-srcset=', ' data-sizes=' ), $html ) ;
		$html = str_replace( '<img ', '<img data-lazyloaded="1"' . $additional_attr . ' src="' . $this_placeholder . '" ', $html ) ;
		$snippet = $html . $snippet ;

		return $snippet ;
	}

	/**
	 * Generate responsive placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder( $src, $size )
	{
		// Low Quality Image Placeholders
		if ( ! $size ) {
			return false ;
		}

		if ( ! $this->_conf_placeholder_resp ) {
			return false ;
		}

		// Check if its already in dict or not
		if ( ! empty( $this->_placeholder_resp_dict[ $size ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] Resp placeholder already in dict [size] ' . $size ) ;

			return $this->_placeholder_resp_dict[ $size ] ;
		}

		// Need to generate the responsive placeholder
		$placeholder_realpath = $this->_placeholder_realpath( $size ) ;
		if ( file_exists( $placeholder_realpath ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] Resp placeholder file exists [size] ' . $size ) ;
			$this->_placeholder_resp_dict[ $size ] = Litespeed_File::read( $placeholder_realpath ) ;

			return $this->_placeholder_resp_dict[ $size ] ;
		}

		// Add to cron queue

		// Prevent repeated requests
		if ( in_array( $size, $this->_ph_queue ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] Resp placeholder file bypass generating due to in queue [size] ' . $size ) ;
			return false ;
		}
		$this->_ph_queue[] = $size ;

		$req_summary = self::get_summary() ;

		// Send request to generate placeholder
		if ( ! $this->_conf_placeholder_resp_async ) {
			// If requested recently, bypass
			if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
				LiteSpeed_Cache_Log::debug2( '[Placeholder] Resp placeholder file bypass generating due to interval limit [size] ' . $size ) ;
				return false ;
			}
			// Generate immediately
			$this->_placeholder_resp_dict[ $size ] = $this->_generate_placeholder( $size ) ;

			return $this->_placeholder_resp_dict[ $size ] ;
		}

		// Store it to prepare for cron
		if ( empty( $req_summary[ 'queue' ] ) ) {
			$req_summary[ 'queue' ] = array() ;
		}
		if ( in_array( $size, $req_summary[ 'queue' ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] Resp placeholder already in queue [size] ' . $size ) ;

			return false ;
		}

		$req_summary[ 'queue' ][] = $size ;

		LiteSpeed_Cache_Log::debug( '[Placeholder] Added placeholder queue [size] ' . $size ) ;

		$this->_save_summary( $req_summary ) ;
		return false ;

	}

	/**
	 * Check if there is a placeholder cache folder
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function has_placehoder_cache()
	{
		return is_dir( LITESPEED_STATIC_DIR . '/placeholder' ) ;
	}

	/**
	 * Save image placeholder summary
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _save_summary( $data )
	{
		update_option( LiteSpeed_Cache_Const::conf_name( self::DB_SUMMARY, 'data' ), $data ) ;
	}

	/**
	 * Read last time generated info
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function get_summary()
	{
		return get_option( LiteSpeed_Cache_Const::conf_name( self::DB_SUMMARY, 'data' ), array() ) ;
	}

	/**
	 * Generate realpath of placeholder file
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder_realpath( $size )
	{
		return LITESPEED_STATIC_DIR . "/placeholder/$size." . md5( $this->_conf_placeholder_resp_color ) ;
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public function rm_cache_folder()
	{
		if ( file_exists( LITESPEED_STATIC_DIR . '/placeholder' ) ) {
			Litespeed_File::rrmdir( LITESPEED_STATIC_DIR . '/placeholder' ) ;
		}

		// Clear placeholder in queue too
		$this->_save_summary( array() ) ;

		LiteSpeed_Cache_Log::debug2( '[Placeholder] Cleared placeholder queue' ) ;
	}

	/**
	 * Cron placeholder generation
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function cron( $continue = false )
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

		foreach ( $req_summary[ 'queue' ] as $v ) {
			LiteSpeed_Cache_Log::debug( '[Placeholder] cron job [size] ' . $v ) ;

			self::get_instance()->_generate_placeholder( $v ) ;

			// only request first one
			if ( ! $continue ) {
				return ;
			}
		}
	}

	/**
	 * Send to LiteSpeed API to generate placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _generate_placeholder( $size )
	{
		$req_summary = self::get_summary() ;

		$file = $this->_placeholder_realpath( $size ) ;

		// Local generate SVG to serve
		if ( ! $this->_conf_placeholder_resp_generator ) {
			$size = explode( 'x', $size ) ;
			$svg = str_replace( array( '{width}', '{height}', '{color}' ), array( $size[ 0 ], $size[ 1 ], $this->_conf_placeholder_resp_color ), $this->_conf_placeholder_resp_svg ) ;
			LiteSpeed_Cache_Log::debug2( '[Placeholder] _generate_placeholder local ' . $svg ) ;
			$data = 'data:image/svg+xml;base64,' . base64_encode( $svg ) ;
		}
		else {

			// Update request status
			$req_summary[ 'curr_request' ] = time() ;
			$this->_save_summary( $req_summary ) ;

			// Generate placeholder
			$req_data = array(
				'size'	=> $size,
				'color'	=> base64_encode( $this->_conf_placeholder_resp_color ), // Encode the color
			) ;
			$data = LiteSpeed_Cache_Admin_API::get( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PLACEHOLDER, $req_data, true ) ;

			LiteSpeed_Cache_Log::debug( '[Placeholder] _generate_placeholder ' ) ;

			if ( strpos( $data, 'data:image/png;base64,' ) !== 0 ) {
				LiteSpeed_Cache_Log::debug( '[Placeholder] failed to decode response: ' . $data ) ;
				return false ;
			}
		}

		// Write to file
		Litespeed_File::save( $file, $data, true ) ;

		// Save summary data
		$req_summary[ 'last_spent' ] = time() - $req_summary[ 'curr_request' ] ;
		$req_summary[ 'last_request' ] = $req_summary[ 'curr_request' ] ;
		$req_summary[ 'curr_request' ] = 0 ;
		if ( ! empty( $req_summary[ 'queue' ] ) && in_array( $size, $req_summary[ 'queue' ] ) ) {
			unset( $req_summary[ 'queue' ][ array_search( $size, $req_summary[ 'queue' ] ) ] ) ;
		}

		$this->_save_summary( $req_summary ) ;

		LiteSpeed_Cache_Log::debug( '[Placeholder] saved placeholder ' . $file ) ;

		LiteSpeed_Cache_Log::debug2( '[Placeholder] placeholder con: ' . $data ) ;

		return $data ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_GENERATE :
				self::cron( true ) ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
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