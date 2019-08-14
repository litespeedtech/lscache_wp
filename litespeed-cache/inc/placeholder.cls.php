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
	private $_conf_placeholder_lqip ;
	private $_conf_placeholder_lqip_qual ;
	private $_conf_placeholder_resp_color ;
	private $_conf_placeholder_resp_async ;
	private $_placeholder_resp_dict = array() ;
	private $_ph_queue = array() ;
	private $_wp_upload_dir ;

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
		$this->_conf_placeholder_lqip 		= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_LQIP ) ;
		$this->_conf_placeholder_lqip_qual	= LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_LQIP_QUAL ) ;
		$this->_conf_placeholder_resp_async = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_ASYNC ) ;
		$this->_conf_placeholder_resp_color = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_COLOR ) ;
		$this->_conf_ph_default = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_MEDIA_LAZY_PLACEHOLDER ) ?: LITESPEED_PLACEHOLDER ;

		$this->_wp_upload_dir = wp_upload_dir() ;
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
		if ( $this->_conf_placeholder_resp_generator && $this_placeholder != $this->_conf_ph_default ) {
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

		// If use local generator
		if ( ! $this->_conf_placeholder_resp_generator ) {
			return $this->_generate_placeholder_locally( $size ) ;
		}

		LiteSpeed_Cache_Log::debug2( '[Placeholder] Resp placeholder process [src] ' . $src . ' [size] ' . $size ) ;

		// Only LQIP needs $src
		$arr_key = $this->_conf_placeholder_lqip ? $size . ' ' . $src : $size ;

		// Check if its already in dict or not
		if ( ! empty( $this->_placeholder_resp_dict[ $arr_key ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] already in dict' ) ;

			return $this->_placeholder_resp_dict[ $arr_key ] ;
		}

		// Need to generate the responsive placeholder
		$placeholder_realpath = $this->_placeholder_realpath( $src, $size ) ; // todo: give offload API
		if ( file_exists( $placeholder_realpath ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] file exists' ) ;
			$this->_placeholder_resp_dict[ $arr_key ] = Litespeed_File::read( $placeholder_realpath ) ;

			return $this->_placeholder_resp_dict[ $arr_key ] ;
		}

		// Add to cron queue

		// Prevent repeated requests
		if ( in_array( $arr_key, $this->_ph_queue ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] file bypass generating due to in queue' ) ;
			return $this->_generate_placeholder_locally( $size ) ;
		}

		$this->_ph_queue[] = $arr_key ;

		$req_summary = self::get_summary() ;

		// Send request to generate placeholder
		if ( ! $this->_conf_placeholder_resp_async ) {
			// If requested recently, bypass
			if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
				LiteSpeed_Cache_Log::debug2( '[Placeholder] file bypass generating due to interval limit' ) ;
				return false ;
			}
			// Generate immediately
			$this->_placeholder_resp_dict[ $arr_key ] = $this->_generate_placeholder( $arr_key ) ;

			return $this->_placeholder_resp_dict[ $arr_key ] ;
		}

		// Prepare default svg placeholder as tmp placeholder
		$tmp_placeholder = $this->_generate_placeholder_locally( $size ) ;

		// Store it to prepare for cron
		if ( empty( $req_summary[ 'queue' ] ) ) {
			$req_summary[ 'queue' ] = array() ;
		}
		if ( in_array( $arr_key, $req_summary[ 'queue' ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Placeholder] already in queue' ) ;

			return $tmp_placeholder ;
		}

		$req_summary[ 'queue' ][] = $arr_key ;

		LiteSpeed_Cache_Log::debug( '[Placeholder] Added placeholder queue' ) ;

		$this->_save_summary( $req_summary ) ;
		return $tmp_placeholder ;

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
	private function _placeholder_realpath( $src, $size )
	{
		// Use plain color placholder
		if ( ! $this->_conf_placeholder_lqip ) {
			return LITESPEED_STATIC_DIR . "/placeholder/$size." . md5( $this->_conf_placeholder_resp_color ) ;
		}

		// Use LQIP Cloud generator, each image placeholder will be separately stored

		// External images will use cache folder directly
		$domain = parse_url( $src, PHP_URL_HOST ) ;
		if ( $domain && ! LiteSpeed_Cache_Utility::internal( $domain ) ) { // todo: need to improve `util:internal()` to include `CDN::internal()`
			$md5 = md5( $src ) ;

			return LITESPEED_STATIC_DIR . '/lqip/remote/' . substr( $md5, 0, 1 ) . '/' . substr( $md5, 1, 1 ) . '/' . $md5 . '.' . $size ;
		}

		// Drop domain
		$local_file = LiteSpeed_Cache_Utility::url2uri( $src ) ; // full path, equals get_attached_file( $post_id ) ;

		$local_file = substr( $local_file, strlen( $this->_wp_upload_dir[ 'basedir' ] ) ) ;
LiteSpeed_Cache_Log::debug( '[Placeholder] local file path:' . $local_file ) ;

		return LITESPEED_STATIC_DIR . '/lqip/' . $local_file . '/' . $size ;

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
	 * Generate placeholder locally
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _generate_placeholder_locally( $size )
	{
		LiteSpeed_Cache_Log::debug2( '[Placeholder] _generate_placeholder local [size] ' . $size ) ;

		$size = explode( 'x', $size ) ;

		$svg = str_replace( array( '{width}', '{height}', '{color}' ), array( $size[ 0 ], $size[ 1 ], $this->_conf_placeholder_resp_color ), $this->_conf_placeholder_resp_svg ) ;

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ) ;
	}

	/**
	 * Send to LiteSpeed API to generate placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _generate_placeholder( $raw_size_and_src )
	{
		// Parse containing size and src info
		$size_and_src = explode( ' ', $raw_size_and_src, 2 ) ;
		$size = $size_and_src[ 0 ] ;
		$src = false ;
		if ( ! empty( $size_and_src[ 1 ] ) ) {
			$src = $size_and_src[ 1 ] ;
		}

		$req_summary = self::get_summary() ;

		$file = $this->_placeholder_realpath( $src, $size ) ;

		// Local generate SVG to serve ( Repeatly doing this here to remove stored cron queue in case the setting _conf_placeholder_resp_generator is changed )
		if ( ! $this->_conf_placeholder_resp_generator ) {
			$data = $this->_generate_placeholder_locally( $size ) ;
		}
		else {
			// Update request status
			$req_summary[ 'curr_request' ] = time() ;
			$this->_save_summary( $req_summary ) ;

			// Generate LQIP
			if ( $this->_conf_placeholder_lqip ) {
				$req_data = array(
					'size'		=> $size,
					'src'		=> base64_encode( $src ),
					'quality'	=> $this->_conf_placeholder_lqip_qual,
				) ;
				$data = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_LQIP, $req_data, true ) ;

				LiteSpeed_Cache_Log::debug( '[Placeholder] _generate_placeholder LQIP' ) ;

				if ( strpos( $data, 'data:image/svg+xml' ) !== 0 ) {
					LiteSpeed_Cache_Log::debug( '[Placeholder] failed to decode response: ' . $data ) ;
					return false ;
				}
			}
			else {

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
		}

		// Write to file
		Litespeed_File::save( $file, $data, true ) ;

		// Save summary data
		$req_summary[ 'last_spent' ] = time() - $req_summary[ 'curr_request' ] ;
		$req_summary[ 'last_request' ] = $req_summary[ 'curr_request' ] ;
		$req_summary[ 'curr_request' ] = 0 ;
		if ( ! empty( $req_summary[ 'queue' ] ) && in_array( $raw_size_and_src, $req_summary[ 'queue' ] ) ) {
			unset( $req_summary[ 'queue' ][ array_search( $raw_size_and_src, $req_summary[ 'queue' ] ) ] ) ;
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