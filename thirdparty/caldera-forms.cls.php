<?php
/**
 * The Third Party integration with Caldera Forms.
 *
 * @since		3.2.2
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class Caldera_Forms
{
	public static function detect()
	{
		if (!defined('CFCORE_VER')) {
			return;
		}

		// plugins/caldera-forms/classes/render/nonce.php -> class Caldera_Forms_Render_Nonce
		do_action('litespeed_nonce', 'caldera_forms_front_*');
	}
}
