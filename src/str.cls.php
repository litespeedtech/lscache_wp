<?php

/**
 * LiteSpeed String Operator Library Class
 *
 * @since 1.3
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Str {

	/**
	 * Translate QC HTML links from html. Convert `<a href="{#xxx#}">xxxx</a>` to `<a href="xxx">xxxx</a>`
	 *
	 * @since 7.0
	 */
	public static function translate_qc_apis( $html ) {
		preg_match_all('/<a href="{#(\w+)#}"/U', $html, $matches);
		if (!$matches) {
			return $html;
		}

		foreach ($matches[0] as $k => $html_to_be_replaced) {
			$link = '<a href="' . Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_API, false, null, array( 'action2' => $matches[1][$k] )) . '"';
			$html = str_replace($html_to_be_replaced, $link, $html);
		}
		return $html;
	}

	/**
	 * Return safe HTML
	 *
	 * @since 7.0
	 */
	public static function safe_html( $html ) {
		$common_attrs = array(
			'style' => array(),
			'class' => array(),
			'target' => array(),
			'src' => array(),
			'color' => array(),
			'href' => array(),
		);
		$tags         = array( 'hr', 'h3', 'h4', 'h5', 'ul', 'li', 'br', 'strong', 'p', 'span', 'img', 'a', 'div', 'font' );
		$allowed_tags = array();
		foreach ($tags as $tag) {
			$allowed_tags[$tag] = $common_attrs;
		}

		return wp_kses($html, $allowed_tags);
	}

	/**
	 * Generate random string
	 *
	 * @since  1.3
	 * @access public
	 * @param  int $len     Length of string
	 * @param  int $type    1-Number 2-LowerChar 4-UpperChar
	 * @return string
	 */
	public static function rrand( $len, $type = 7 ) {
		switch ($type) {
			case 0:
            $charlist = '012';
				break;

			case 1:
            $charlist = '0123456789';
				break;

			case 2:
            $charlist = 'abcdefghijklmnopqrstuvwxyz';
				break;

			case 3:
            $charlist = '0123456789abcdefghijklmnopqrstuvwxyz';
				break;

			case 4:
            $charlist = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;

			case 5:
            $charlist = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;

			case 6:
            $charlist = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;

			case 7:
            $charlist = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
		}

		$str = '';

		$max = strlen($charlist) - 1;
		for ($i = 0; $i < $len; $i++) {
			$str .= $charlist[random_int(0, $max)];
		}

		return $str;
	}

	/**
	 * Trim double quotes from a string to be used as a preformatted src in HTML.
	 *
	 * @since 6.5.3
	 */
	public static function trim_quotes( $string ) {
		return str_replace('"', '', $string);
	}
}
