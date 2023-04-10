<?php
/**
 * Auto registration for LiteSpeed classes
 *
 * @since      	1.1.0
 */
defined( 'WPINC' ) || exit;

// Force define for object cache usage before plugin init
! defined( 'LSCWP_DIR' ) && define( 'LSCWP_DIR', __DIR__ . '/' ) ;// Full absolute path '/var/www/html/***/wp-content/plugins/litespeed-cache/' or MU

if ( ! function_exists( 'litespeed_autoload' ) ) {
	function litespeed_autoload( $cls ) {
		if ( strpos( $cls, '.' ) !== false ) {
			return;
		}

		if ( strpos( $cls, 'LiteSpeed' ) !== 0 ) {
			return;
		}

		$file = explode( '\\', $cls );
		array_shift( $file );
		$file = implode( '/', $file );
		$file = str_replace( '_', '-', strtolower( $file ) );

		if ( strpos( $file, 'lib/' ) === 0 || strpos( $file, 'cli/' ) === 0 || strpos( $file, 'thirdparty/' ) === 0 ) {
			$file = LSCWP_DIR . $file . '.cls.php';
		}
		else {
			$file = LSCWP_DIR . 'src/' . $file . '.cls.php';
		}

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

spl_autoload_register( 'litespeed_autoload' );

