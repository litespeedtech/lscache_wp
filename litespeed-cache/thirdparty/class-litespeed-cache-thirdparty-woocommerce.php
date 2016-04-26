<?php


class LiteSpeed_Cache_ThirdParty_WooCommerce
{


	public static function detect()
	{
		if (defined('WOOCOMMERCE_VERSION')) {
			add_filter('litespeed_cache_is_cacheable', 'LiteSpeed_Cache_ThirdParty_WooCommerce::is_cacheable');
		}
	}

	public static function is_cacheable($cacheable)
	{
		if (!$cacheable) {
			return false;
		}
		$woocom = WC();
		if (!isset($woocom)) {
			return true;
		}

		// For later versions, DONOTCACHEPAGE should be set.
		// No need to check uri/qs.
		if (version_compare($woocom->version, '1.4.2', '>=')) {
			if ((defined('DONOTCACHEPAGE')) && (DONOTCACHEPAGE)) {
				return false;
			}
			return true;
		}
		$uri = esc_url($_SERVER["REQUEST_URI"]);
		$uri_len = strlen( $uri ) ;

		if ($uri_len < 5) {
			return true;
		}
		$sub = substr($uri, 2);
		$sub_len = $uri_len - 2;
		switch($uri[1]) {
		case 'c':
			if ((($sub_len == 4) && (strncmp($sub, 'art/', 4) == 0))
				|| (($sub_len == 8) && (strncmp($sub, 'heckout/', 8) == 0))) {
				return false;
			}
			break;
		case 'm':
			if (strncmp($sub, 'y-account/', 10) == 0) {
				return false;
			}
			break;
		case 'a':
			if (($sub_len == 6) && (strncmp($sub, 'ddons/', 6) == 0)) {
				return false;
			}
			break;
		case 'l':
			if ((($sub_len == 6) && (strncmp($sub, 'ogout/', 6) == 0))
				|| (($sub_len == 13) && (strncmp($sub, 'ost-password/', 13) == 0))) {
				return false;
			}
			break;
		case 'p':
			if (strncmp($sub, 'roduct/', 7) == 0) {
				return false;
			}
			break;
		}

		$qs = sanitize_text_field($_SERVER["QUERY_STRING"]);
		$qs_len = strlen($qs);
		if ( !empty($qs) && ($qs_len >= 12)
				&& (strncmp($qs, 'add-to-cart=', 12) == 0)) {
			return false;
		}

		return true;
	}

}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_WooCommerce::detect');
