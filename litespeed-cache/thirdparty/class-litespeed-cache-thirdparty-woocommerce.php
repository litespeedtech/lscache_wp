<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class LiteSpeed_Cache_ThirdParty_WooCommerce
{


	public static function detect()
	{
		if (defined('WOOCOMMERCE_VERSION')) {
			add_filter('litespeed_cache_is_cacheable', 'LiteSpeed_Cache_ThirdParty_WooCommerce::is_cacheable');
		}
	}

	public static function is_cacheable($cache_tags)
	{
		// Check if null. If it is null, means another plugin said not cacheable.
		if (is_null($cache_tags)) {
			return null;
		}
		$woocom = WC();
		if (!isset($woocom)) {
			return $cache_tags;
		}

		// For later versions, DONOTCACHEPAGE should be set.
		// No need to check uri/qs.
		if (version_compare($woocom->version, '1.4.2', '>=')) {
			if ((defined('DONOTCACHEPAGE')) && (DONOTCACHEPAGE)) {
				return null;
			}
			return $cache_tags;
		}
		$uri = esc_url($_SERVER["REQUEST_URI"]);
		$uri_len = strlen( $uri ) ;

		if ($uri_len < 5) {
			return $cache_tags;
		}
		$sub = substr($uri, 2);
		$sub_len = $uri_len - 2;
		switch($uri[1]) {
		case 'c':
			if ((($sub_len == 4) && (strncmp($sub, 'art/', 4) == 0))
				|| (($sub_len == 8) && (strncmp($sub, 'heckout/', 8) == 0))) {
				return null;
			}
			break;
		case 'm':
			if (strncmp($sub, 'y-account/', 10) == 0) {
				return null;
			}
			break;
		case 'a':
			if (($sub_len == 6) && (strncmp($sub, 'ddons/', 6) == 0)) {
				return null;
			}
			break;
		case 'l':
			if ((($sub_len == 6) && (strncmp($sub, 'ogout/', 6) == 0))
				|| (($sub_len == 13) && (strncmp($sub, 'ost-password/', 13) == 0))) {
				return null;
			}
			break;
		case 'p':
			if (strncmp($sub, 'roduct/', 7) == 0) {
				return null;
			}
			break;
		}

		$qs = sanitize_text_field($_SERVER["QUERY_STRING"]);
		$qs_len = strlen($qs);
		if ( !empty($qs) && ($qs_len >= 12)
				&& (strncmp($qs, 'add-to-cart=', 12) == 0)) {
			return null;
		}

		return $cache_tags;
	}



}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_WooCommerce::detect');
