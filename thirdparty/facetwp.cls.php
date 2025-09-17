<?php
// phpcs:ignoreFile
/**
 * The Third Party integration with FacetWP.
 *
 * @since       2.9.9
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class Facetwp {

	public static function detect() {
		if (!defined('FACETWP_VERSION')) {
			return;
		}
		/**
		 * For Facetwp, if the template is "wp", return the buffered HTML
		 * So marked as rest call to put is_json to ESI
		 */
		if (!empty($_POST['action']) && !empty($_POST['data']) && !empty($_POST['data']['template']) && $_POST['data']['template'] === 'wp') {
			add_filter('litespeed_esi_params', __CLASS__ . '::set_is_json');
		}
	}

	public static function set_is_json( $params ) {
		$params['is_json'] = 1;
		return $params;
	}
}
