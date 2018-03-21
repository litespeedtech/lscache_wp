<?php
/**
 * The quic.cloud class.
 *
 * @since      	2.0
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

	private function _show_user_guide()
	{
		if ( ! empty( $_POST[ 'step' ] ) && $_POST[ 'step' ] == 2 ) {
			if ( empty( $_POST[ 'email' ] ) ) {
				exit( 'No email' ) ;
			}

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

		// Show user panel welcome page
		$this->_tpl( 'quic.user_welcome', 25 ) ;
		exit;
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
			'auth_key'	=> $this->_api_key,
			'v'	=> LiteSpeed_Cache::PLUGIN_VERSION,
			'hash'	=> $hash,
			'data' => $data,
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
			case self::TYPE_REG :
				$instance->_cloudflare_get_devmode() ;
				break ;

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