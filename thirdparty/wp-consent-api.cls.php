<?php
/**
 * The Third Party integration with WP Consent API.
 *
 *
 * @since		7.0.0
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class WP_Consent_API
{
	public static function detect()
	{
		if (!class_exists('WP_CONSENT_API')) {
			return;
		}

		// Remove LSC warning about consent that appear in Site Health API.
		// Topic: https://wordpress.org/support/topic/consent-api/
		// Talk with plugin author: https://wordpress.org/support/topic/litespeed-cache-26
		$plugin = plugin_basename(LSCWP_BASENAME);
		add_filter("wp_consent_api_registered_$plugin", '__return_true');
	}
}
