<?php

/**
 * LiteSpeed String Operator Library Class
 *
 * @since 1.3
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Str
{
	/**
	 * Generate random string
	 *
	 * @since  1.3
	 * @access public
	 * @param  int  $len  	 Length of string
	 * @param  int  $type    1-Number 2-LowerChar 4-UpperChar
	 * @return string
	 */
	public static function rrand($len, $type = 7)
	{
		// mt_srand((int) ((float) microtime() * 1000000));

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
			$str .= $charlist[mt_rand(0, $max)];
		}

		return $str;
	}

	/**
	 * Trim double quotes from a string to be used as a preformatted src in HTML.
	 * @since 6.5.3
	 */
	public static function trim_quotes($string)
	{
		return str_replace('"', '', $string);
	}
}
