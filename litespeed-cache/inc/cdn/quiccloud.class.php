<?php
/**
 * The quic.cloud class.
 *
 * @since      	2.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_CDN_Quiccloud
{
	private static $_instance ;

	const TYPE_REG = 'reg' ;

	private function _show_user_guide()
	{
		if ( ! empty( $_POST[ 'step' ] ) && $_POST[ 'step' ] == 2 ) {
			if ( empty( $_POST[ 'email' ] ) ) {
				exit( 'No email' ) ;
			}
			// Get email status
			$data = $this->_api( '/u/query', array( 'email' => $_POST[ 'email' ] ) ) ;
			if ( empty( $data[ 'result' ] ) ) {
				LiteSpeed_Cache_Log::debug( "QUIC: Query email failed" ) ;
				exit( "QUIC: Query email failed" ) ;
			}

			$_email = $_POST[ 'email' ] ;

			if ( $data[ 'result' ] == 'existing' ) {
				$this->_tpl( 'quic.login', 50 ) ;
			}
			elseif ( $data[ 'result' ] == 'none' ) {
				$this->_tpl( 'quic.register', 50 ) ;
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

	private function _tpl( $tpl, $_progress = false )
	{
		require LSCWP_DIR . "admin/tpl/inc/modal.header.php" ;
		require LSCWP_DIR . "admin/tpl/api/$tpl.php" ;
		require LSCWP_DIR . "admin/tpl/inc/modal.footer.php" ;
	}

	private function _api( $uri, $data = false, $method = 'POST' )
	{
		LiteSpeed_Cache_Log::debug( "QUIC: _api call" ) ;

		$url = 'https://api.quic.cloud' . $uri ;

		$header = array(
			'Content-Type: application/json',
		) ;

		$ch = curl_init() ;
		curl_setopt( $ch, CURLOPT_URL, $url ) ;
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method ) ;
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header ) ;
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ;
		if ( $data ) {
			if ( is_array( $data ) ) {
				$data = json_encode( $data ) ;
			}
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data ) ;
		}
		$result = curl_exec( $ch ) ;

		LiteSpeed_Cache_Log::debug( "QUIC: _api call result: " . $result ) ;

		$json = json_decode( $result, true ) ;

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
		LiteSpeed_Cache_Log::debug( 'QUIC_CLOUD: init' ) ;
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