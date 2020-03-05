<?php
/**
 * The Third Party integration with WCML.
 *
 * @since		3.0
 */
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class WCML
{

	public static function detect()
	{
		if ( ! defined( 'WPML_PLUGIN_BASENAME' ) ) return;

		add_filter( 'litespeed_internal_domains', __CLASS__ . '::append_domains' );
	}

	/**
	 * Take language domains as internal domains
	 */
	public static function append_domains( $domains )
	{
		$wpml_domains = apply_filters( 'wpml_setting', false, 'language_domains' );
		if ( $wpml_domains ) {
			$domains = array_merge( $domains, array_values( $wpml_domains ) );
		}

		return $domains;
	}

}