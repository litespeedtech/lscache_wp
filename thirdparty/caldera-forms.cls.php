<?php
/**
 * The Third Party integration with Caldera Forms.
 *
 * @since      3.2.2
 * @package    LiteSpeed
 * @subpackage LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Integration with Caldera Forms to ensure proper nonce handling for cached pages.
 */
class Caldera_Forms {

	/**
	 * Detects if Caldera Forms is active and registers nonces accordingly.
	 *
	 * Hooks the plugin's frontend nonce pattern into LiteSpeed so cached pages
	 * still validate form submissions.
	 *
	 * @since 3.2.2
	 * @return void
	 */
	public static function detect() {
		if (!defined('CFCORE_VER')) {
			return;
		}

		// plugins/caldera-forms/classes/render/nonce.php -> class Caldera_Forms_Render_Nonce
		do_action('litespeed_nonce', 'caldera_forms_front_*');
	}
}
