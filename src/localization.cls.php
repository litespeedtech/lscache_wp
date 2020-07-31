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
	 * Localize JS/Fonts
	 *
	 * @since 3.3
	 * @access public
	 */
	public function finalize( $content ) {
		if ( ! Conf::val( Base::O_OPTM_LOCALIZE ) ) {
			return;
		}

		$domains = Conf::val( Base::O_OPTM_LOCALIZE_DOMAINS );
		if ( ! $domains ) {
			return;
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

			$content = str_replace( $domain, LITESPEED_STATIC_URL . '/localres/' . substr( $domain, 8 ), $content );
		}

		return $content;
	}

}