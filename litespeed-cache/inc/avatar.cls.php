<?php
/**
 * The avatar cache class
 *
 * @since 		3.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
defined( 'WPINC' ) || exit ;

class LiteSpeed_Cache_Avatar
{
	private static $_instance ;

	const TYPE_GENERATE = 'generate' ;
	const DB_SUMMARY = 'avatar' ;

	private $_conf_cache_ttl ;
	private $_tb ;

	private $_avatar_realtime_gen_dict = array() ;

	/**
	 * Init
	 *
	 * @since  1.4
	 * @access private
	 */
	private function __construct()
	{
		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_DISCUSS_AVATAR_CACHE ) ) {
			return ;
		}

		// Create table
		$this->_tb = LiteSpeed_Cache_Data::get_instance()->create_tb_avatar() ;

		if ( ! $this->_tb ) {
			LiteSpeed_Cache_Log::debug( '[Avatar] No table existed!' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug2( '[Avatar] init' ) ;

		$this->_serve_avatar() ;

		$this->_conf_cache_ttl = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_DISCUSS_AVATAR_CACHE_TTL ) ;

		add_filter( 'get_avatar_url', array( $this, 'crawl_avatar' ) ) ;
	}

	/**
	 * Get gravatar URL from DB and regenarate
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _serve_avatar()
	{
		global $wpdb ;

		if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/cache/avatar/' ) === false ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( '[Avatar] is avatar request' ) ;

		$md5 = substr( $_SERVER[ 'REQUEST_URI' ], strrpos( $_SERVER[ 'REQUEST_URI' ], '/' ) + 1 ) ;

		if ( strlen( $md5 ) !== 32 ) {
			LiteSpeed_Cache_Log::debug( '[Avatar] wrong md5 ' . $md5 ) ;
			return ;
		}

		$q = "SELECT url FROM $this->_tb WHERE md5=%s" ;
		$url = $wpdb->get_var( $wpdb->prepare( $q, $md5 ) ) ;

		if ( ! $url ) {
			LiteSpeed_Cache_Log::debug( '[Avatar] no matched url for md5 ' . $md5 ) ;
			return ;
		}

		$url = $this->_generate( $url ) ;

		wp_redirect( $url ) ;
		exit ;
	}

	/**
	 * Localize gravatar
	 *
	 * @since  3.0
	 * @access public
	 */
	public function crawl_avatar( $url )
	{
		if ( ! $url ) {
			return $url ;
		}

		// Check if its already in dict or not
		if ( ! empty( $this->_avatar_realtime_gen_dict[ $url ] ) ) {
			LiteSpeed_Cache_Log::debug2( '[Avatar] already in dict [url] ' . $url ) ;

			return $this->_avatar_realtime_gen_dict[ $url ] ;
		}

		$realpath = $this->_realpath( $url ) ;
		if ( file_exists( $realpath ) && time() - filemtime( $realpath ) <= $this->_conf_cache_ttl ) {
			LiteSpeed_Cache_Log::debug2( '[Avatar] cache file exists [url] ' . $url ) ;
			return $this->_rewrite( $url ) ;
		}

		if ( ! strpos( $url, 'gravatar.com' ) ) {
			return $url ;
		}

		$req_summary = self::get_summary() ;

		// Send request
		if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
			LiteSpeed_Cache_Log::debug2( '[Avatar] Bypass generating due to interval limit [url] ' . $url ) ;
			return $url ;
		}

		// Generate immediately
		$this->_avatar_realtime_gen_dict[ $url ] = $this->_generate( $url ) ;

		return $this->_avatar_realtime_gen_dict[ $url ] ;
	}

	/**
	 * Check if there is a queue for cron or not
	 *
	 * @since  3.0
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
	 * Check if there is a cache folder
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function has_cache()
	{
		return is_dir( LSCWP_CONTENT_DIR . '/cache/avatar' ) ;
	}

	/**
	 * make cache folder
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _mkdir()
	{
		mkdir( LSCWP_CONTENT_DIR . '/cache/avatar', 0755, true ) ;
	}

	/**
	 * Save summary
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _save_summary( $data )
	{
		update_option( LiteSpeed_Cache_Const::conf_name( self::DB_SUMMARY, 'data' ), $data ) ;
	}

	/**
	 * Read last time generated info
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function get_summary()
	{
		global $wpdb ;

		$instance = self::get_instance() ;

		$summary = get_option( LiteSpeed_Cache_Const::conf_name( self::DB_SUMMARY, 'data' ), array() ) ;

		$q = "SELECT count(*) FROM $instance->_tb WHERE dateline < " . ( time() - $instance->_conf_cache_ttl ) ;
		$summary[ 'queue_count' ] = $wpdb->get_var( $q ) ;
	}

	/**
	 * Get the final URL of local avatar
	 *
	 * Check from db also
	 *
	 * @since  3.0
	 */
	private function _rewrite( $url )
	{
		return WP_CONTENT_URL . '/cache/avatar/' . md5( $url ) ;
	}

	/**
	 * Generate realpath of the cache file
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _realpath( $url )
	{
		return LSCWP_CONTENT_DIR . '/cache/avatar/' . md5( $url ) ;
	}

	/**
	 * Delete file-based cache folder
	 *
	 * @since  3.0
	 * @access public
	 */
	public function rm_cache_folder()
	{
		if ( file_exists( LSCWP_CONTENT_DIR . '/cache/avatar' ) ) {
			Litespeed_File::rrmdir( LSCWP_CONTENT_DIR . '/cache/avatar' ) ;
		}

		// Clear avatar summary
		$this->_save_summary( array() ) ;

		LiteSpeed_Cache_Log::debug2( '[Avatar] Cleared avatar queue' ) ;
	}

	/**
	 * Cron generation
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function cron( $continue = false )
	{
		global $wpdb ;

		$req_summary = self::get_summary() ;
		if ( ! $req_summary[ 'queue_count' ] ) {
			return ;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( $req_summary && ! empty( $req_summary[ 'curr_request' ] ) && time() - $req_summary[ 'curr_request' ] < 300 ) {
				return ;
			}
		}

		$q = "SELECT * FROM $this->_tb WHERE dateline<%d LIMIT %d" ;
		$q = $wpdb->prepare( $q, array( time() - $this->_conf_cache_ttl, apply_filters( 'litespeed_avatar_limit', 30 ) ) ) ;

		$list = $wpdb->get_results( $q ) ;

		foreach ( $list as $v ) {
			$url = $v[ 'url' ] ;
			LiteSpeed_Cache_Log::debug( '[Avatar] cron job [url] ' . $url ) ;

			self::get_instance()->_generate( $url ) ;

			// only request first one
			if ( ! $continue ) {
				return ;
			}
		}
	}

	/**
	 * Remote generator
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _generate( $url )
	{
		global $wpdb ;

		// Record the data

		$req_summary = self::get_summary() ;

		$file = $this->_realpath( $url ) ;

		// Update request status
		$req_summary[ 'curr_request' ] = time() ;
		$this->_save_summary( $req_summary ) ;

		// Generate
		if ( ! self::has_cache() ) {
			$this->_mkdir() ;
		}
		$response = wp_remote_get( $url, array( 'timeout' => 180, 'stream' => true, 'filename' => $file ) ) ;

		LiteSpeed_Cache_Log::debug( '[Avatar] _generate [url] ' . $url ) ;

		// Parse response data
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			file_exists( $file ) && unlink( $file ) ;
			LiteSpeed_Cache_Log::debug( '[Avatar] failed to get: ' . $error_message ) ;
			return $url ;
		}

		// Save summary data
		$req_summary[ 'last_spent' ] = time() - $req_summary[ 'curr_request' ] ;
		$req_summary[ 'last_request' ] = $req_summary[ 'curr_request' ] ;
		$req_summary[ 'curr_request' ] = 0 ;

		$this->_save_summary( $req_summary ) ;

		// Update DB
		$md5 = md5( $url ) ;
		$q = "UPDATE $this->_tb SET dateline=%d WHERE md5=%s" ;
		$existed = $wpdb->query( $wpdb->prepare( $q, array( time(), $md5 ) ) ) ;
		if ( ! $existed ) {
			$q = "INSERT INTO $this->_tb SET url=%s, md5=%s, dateline=%d" ;
			$wpdb->query( $wpdb->prepare( $q, array( $url, $md5, time() ) ) ) ;
		}

		LiteSpeed_Cache_Log::debug( '[Avatar] saved avatar ' . $file ) ;

		return $this->_rewrite( $url ) ;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
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