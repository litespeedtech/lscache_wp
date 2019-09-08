<?php
/**
 * Auto registration for LiteSpeed classes
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @since  		3.0 Moved into /
 * @package    	LiteSpeed
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
defined( 'WPINC' ) || exit ;

if ( ! function_exists( '_litespeed_autoload' ) ) {
	function _litespeed_autoload( $cls )
	{
		if ( strpos( $cls, 'LiteSpeed' ) !== 0 ) {
			return ;
		}

		$file = explode( '\\', $cls ) ;
		array_shift( $file ) ;
		$file = implode( '/', $file ) ;
		$file = str_replace( '_', '-', strtolower( $file ) ) ;

		if ( strpos( $file, 'lib/' ) === 0 || strpos( $file, 'cli/' ) === 0 || strpos( $file, 'thirdparty/' ) === 0 ) {
			$file = LSCWP_DIR . $file . '.cls.php' ;
		}
		else {
			$file = LSCWP_DIR . 'src/' . $file . '.cls.php' ;
		}

		if ( file_exists( $file ) ) {
			require_once $file ;
		}
	}
}

spl_autoload_register( '_litespeed_autoload' ) ;

