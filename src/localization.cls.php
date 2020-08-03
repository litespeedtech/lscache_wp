<?php
/**
 * The localization class.
 *
 * @since      	3.3
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Localization extends Base {
	protected static $_instance;


	/**
	 * Localize Resources
	 *
	 * @since  3.3
	 */
	public function serve_static( $uri ) {
		$url = 'https://' . $uri;

		Control::set_no_vary();
		Control::set_public_forced( 'Localized Resources' );
		Tag::add( Tag::TYPE_LOCALRES );

		header( 'Content-Type: application/javascript' );

		$res = wp_remote_get( $url );
		$content = wp_remote_retrieve_body( $res );

		if ( ! $content ) {
			$content = '/* Failed to load ' . $url . ' */';
		}

		echo $content;

		exit;
	}



	/**
	 * Localize JS/Fonts
	 *
	 * @since 3.3
	 * @access public
	 */
	public function finalize( $content ) {
		if ( ! Conf::val( Base::O_OPTM_LOCALIZE ) ) {
			return $content;
		}

		$domains = Conf::val( Base::O_OPTM_LOCALIZE_DOMAINS );
		if ( ! $domains ) {
			return $content;
		}

		foreach ( $domains as $v ) {
			if ( ! $v || strpos( $v, '#' ) === 0 ) {
				continue;
			}

			$type = 'js';
			$domain = $v;
			// Try to parse space splitted value
			if ( strpos( $v, ' ' ) ) {
				$v = explode( ' ', $v );
				if ( ! empty( $v[ 1 ] ) ) {
					$type = strtolower( $v[ 0 ] );
					$domain = $v[ 1 ];
				}
			}

			if ( strpos( $domain, 'https://' ) !== 0 ) {
				continue;
			}

			if ( $type != 'js' ) {
				continue;
			}

			$content = str_replace( $domain, LITESPEED_STATIC_URL . '/localres/' . substr( $domain, 8 ), $content );
		}

		return $content;
	}

}