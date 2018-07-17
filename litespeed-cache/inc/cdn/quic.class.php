<?php
/**
 * The quic.cloud class.
 *
 * @since      	2.4.1
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_CDN_Quic
{
	private static $_instance ;

	private $_api_key ;

	const TYPE_REG = 'reg' ;

	const DB_API_HASH = 'litespeed_cdn_quic_hash' ;

	/**
	 * Notify CDN new config updated
	 *
	 * @access public
	 */
	public static function sync_config( $options )
	{
		if ( empty( $options[ LiteSpeed_Cache_Config::OPT_CDN_QUIC_EMAIL ] ) || empty( $options[ LiteSpeed_Cache_Config::OPT_CDN_QUIC_KEY ] ) ) {
			return false ;
		}

		// Security: Remove cf key in report
		$secure_fields = array(
			LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_KEY,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PSWD,
		) ;
		foreach ( $secure_fields as $v ) {
			if ( ! empty( $options[ $v ] ) ) {
				$options[ $v ] = str_repeat( '*', strlen( $options[ $v ] ) ) ;
			}
		}

		// Also read data from items
		$item_options = LiteSpeed_Cache_Config::get_instance()->stored_items() ;
		foreach ( $item_options as $v ) {
			// bypass main conf
			if ( $v == LiteSpeed_Cache_Config::OPTION_NAME ) {
				continue ;
			}
			$options[ $v ] = get_option( $v ) ;
		}

		$instance = self::get_instance() ;

		// Get site domain
		$options[ '_domain' ] = home_url() ;

		// Add server env vars
		$options[ '_server' ] = LiteSpeed_Cache_Config::get_instance()->server_vars() ;

		// Append hooks
		$options[ '_tp_cookies' ] = apply_filters( 'litespeed_cache_api_vary', array() ) ;

		$res = $instance->_api( '/sync_config', $options ) ;
		if ( $res != 'ok' ) {
			LiteSpeed_Cache_Log::debug( '[QUIC] sync config failed [err] ' . $res ) ;
		}
		return $res ;
	}

	private function _show_user_guide()
	{
		if ( ! empty( $_POST[ 'step' ] ) ) {
			if ( empty( $_POST[ 'email' ] ) ) {
				exit( 'No email' ) ;
			}

			if ( $_POST[ 'step' ] == 'register' ) {
				$this->_register() ;
			}

			if ( $_POST[ 'step' ] == 'login' ) {
				$this->_login() ;
			}

			if ( $_POST[ 'step' ] == 'check_email' ) {
				$this->_check_email() ;
			}
		}

		// Show user panel welcome page
		$this->_tpl( 'quic.user_welcome', 25 ) ;
		exit;
	}


	private function _check_email()
	{
		$_email = $_POST[ 'email' ] ;

		// Get email status
		$response = $this->_api( '/u/email_status', array( 'email' => $_email ) ) ;
		if ( empty( $response[ 'result' ] ) ) {

			LiteSpeed_Cache_Log::debug( '[QUIC] Query email failed' ) ;

			exit( "QUIC: Query email failed" ) ;
		}

		$data = array( 'email' => $_email ) ;

		if ( $response[ 'result' ] == 'existing' ) {
			$this->_tpl( 'quic.login', 50, $data ) ;
		}
		elseif ( $response[ 'result' ] == 'none' ) {
			$this->_tpl( 'quic.register', 50, $data ) ;
		}
		else {
			exit( 'Unkown result' ) ;
		}

		exit ;
	}

	private function _register()
	{
		$_email = $_POST[ 'email' ] ;

		if ( empty( $_POST[ 'pswd' ] ) ) {
			exit( 'No password' ) ;
		}

		// Register
		$response = $this->_api( '/u/register', array( 'email' => $_email, 'pswd' => $_POST[ 'pswd' ] ) ) ;
		if ( empty( $response[ 'result' ] ) || $response[ 'result' ] !== 'success' ) {

			LiteSpeed_Cache_Log::debug( '[QUIC] Register failed' ) ;

			exit( "QUIC: Register failed" ) ;
		}

		// todo: add domain?

		exit ;

	}

	private function _login()
	{
		$_email = $_POST[ 'email' ] ;

		if ( empty( $_POST[ 'pswd' ] ) ) {
			exit( 'No password' ) ;
		}

		// Login
		$response = $this->_api( '/u/login', array( 'email' => $_email, 'pswd' => $_POST[ 'pswd' ] ) ) ;

		$data = array( 'email' => $_email ) ;

		// for login failed, redirect back to login page
		if ( empty( $response[ 'result' ] ) || $response[ 'result' ] !== 'success' ) {

			LiteSpeed_Cache_Log::debug( '[QUIC] Login failed' ) ;

			$data[ '_err' ] = $response[ 'result' ] ;

			$this->_tpl( 'quic.login', 50, $data ) ;
			exit ;
		}

		// Show domains list
		$this->_show_domains() ;

		exit ;
	}

	private function _tpl( $tpl, $_progress = false, $data = false )
	{
		require LSCWP_DIR . "admin/tpl/inc/modal.header.php" ;
		require LSCWP_DIR . "admin/tpl/api/$tpl.php" ;
		require LSCWP_DIR . "admin/tpl/inc/modal.footer.php" ;
	}

	private function _api( $uri, $data = false, $method = 'POST', $no_hash = false )
	{
		LiteSpeed_Cache_Log::debug( '[QUIC] _api call' ) ;

		$hash = 'no_hash' ;
		if ( ! $no_hash ) {
			$hash = Litespeed_String::rrand( 16 ) ;
			// store hash
			update_option( self::DB_API_HASH, $hash ) ;
		}

		$url = 'https://api.quic.cloud' . $uri ;

		$param = array(
			'_v'	=> LiteSpeed_Cache::PLUGIN_VERSION,
			'_hash'	=> $hash,
			'_data' => $data,
		) ;

		$response = wp_remote_post( $url, array( 'body' => $param, 'timeout' => 15 ) ) ;


		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message() ;
			LiteSpeed_Cache_Log::debug( '[QUIC] failed to post: ' . $error_message ) ;
			return $error_message ;
		}
		LiteSpeed_Cache_Log::debug( '[QUIC] _api call response: ' . $response[ 'body' ] ) ;

		$json = json_decode( $response[ 'body' ], true ) ;

		return $json ;

	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.0
	 * @access public
	 */
	public static function handler()
	{
		LiteSpeed_Cache_Log::debug( '[QUIC] init' ) ;
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {

			default:
				$instance->_show_user_guide() ;
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.8
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