<?php
/**
 * The error class.
 *
 * @since      	3.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
defined( 'WPINC' ) || exit ;


class LiteSpeed_Error
{

	const HTA_LOGIN_COOKIE_INVALID = 4300 ; // .htaccess did not find.
	const HTA_DNF = 4500 ; // .htaccess did not find.
	const HTA_BK = 9010 ; // backup
	const HTA_R = 9041 ; // read htaccess
	const HTA_W = 9042 ; // write
	const HTA_GET = 9030 ; // failed to get

	/**
	 * Throw an error with msg
	 *
	 * @since  3.0
	 */
	public static function t( $code, $args = null )
	{
		switch ( $code ) {
			case 'HTA_DNF' :
				$args[] = '.htaccess' ;
				$error = __( 'Could not find %1$s in %2$s.', 'litespeed-cache' ) ;
				break;

			case 'HTA_LOGIN_COOKIE_INVALID' :
				$error = sprintf( __( 'Invalid login cookie. Please check the %s file.', 'litespeed-cache' ), '.htaccess' ) ;
				break;

			case 'HTA_BK' :
				$error = sprintf( __( 'Failed to back up %s file, aborted changes.', 'litespeed-cache' ), '.htaccess' ) ;
				break;

			case 'HTA_R' :
				$error = sprintf( __( '%s file not readable.', 'litespeed-cache' ), '.htaccess' ) ;
				break;

			case 'HTA_W' :
				$error = sprintf( __( '%s file not writable.', 'litespeed-cache' ), '.htaccess' ) ;
				break;

			case 'HTA_GET' :
				$error = sprintf( __( 'Failed to get %s file contents.', 'litespeed-cache' ), '.htaccess' ) ;
				break;

			default:
				$error = 'Unknown error' ;
				break;
		}

		if ( $args !== null ) {
			$error = is_array( $args ) ? vsprintf( $error, $args ) : sprintf( $error, $args ) ;
		}

		if ( defined( 'self::' . $code ) ) {
			$error = 'ERROR ' . constant( 'self::' . $code ) . ': ' . $error ;
		}

		throw new Exception( $error ) ;
	}
}