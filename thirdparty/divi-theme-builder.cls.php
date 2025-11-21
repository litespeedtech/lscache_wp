<?php
/**
 * The Third Party integration with DIVI Theme.
 *
 * Ensures Divi Builder edit/preview modes don't conflict with LiteSpeed Cache features,
 * and registers required nonces for Divi modules.
 *
 * @since      2.9.0
 * @package    LiteSpeed
 * @subpackage LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Handles Divi Theme Builder compatibility.
 */
class Divi_Theme_Builder {

	// private static $js_comment_box = false;

	/**
	 * If Divi front-end edit/preview mode is active, disable LSCWP features to avoid conflicts.
	 *
	 * Note: This reads query vars only to detect Divi edit states. Nonce verification
	 * is not applicable here because no privileged action is performed.
	 *
	 * @since 2.9.7.2 #435538 #581740 #977284
	 * @since 2.9.9.1 Added 'et_pb_preview' for loading image from library in Divi page edit
	 * @return void
	 */
	public static function preload() {
		if (!function_exists('et_setup_theme')) {
			return;
		}

		// Sanitize incoming query params before use.
		$et_fb         = isset($_GET['et_fb']) ? sanitize_text_field(wp_unslash($_GET['et_fb'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$et_pb_preview = isset($_GET['et_pb_preview']) ? sanitize_text_field(wp_unslash($_GET['et_pb_preview'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$p             = isset($_GET['p']) ? absint(wp_unslash($_GET['p'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preview       = isset($_GET['preview']) ? sanitize_text_field(wp_unslash($_GET['preview'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' !== $et_fb || '' !== $et_pb_preview || ( $p && 'true' === $preview ) ) {
			do_action( 'litespeed_disable_all', 'divi edit mode' );
		}
	}

	/**
	 * Detect Divi and register required integrations.
	 *
	 * - Allows the crawler to ignore Divi's first-visit "no-cache" for CCSS generation.
	 * - Adds nonces used by Divi modules so cached pages still validate requests.
	 *
	 * @since 2.9.0
	 * @return void
	 */
	public static function detect() {
		if (!defined('ET_CORE')) {
			return;
		}

		// As DIVI may set first visit to non-cacheable to generate CCSS,
		// instruct the crawler to ignore that flag.
		if (!defined('LITESPEED_CRAWLER_IGNORE_NONCACHEABLE')) {
			define('LITESPEED_CRAWLER_IGNORE_NONCACHEABLE', true);
		}

		/**
		 * Add contact form to nonce.
		 *
		 * @since 2.9.7.1 #475461
		 */
		do_action('litespeed_nonce', 'et-pb-contact-form-submit');

		/**
		 * Subscribe module and A/B logging.
		 *
		 * @since 3.0
		 */
		do_action('litespeed_nonce', 'et_frontend_nonce');
		do_action('litespeed_nonce', 'et_ab_log_nonce');

		/*
		// the comment box fix is for user using theme builder, ESI will load the wrong json string
		// As we disabled all for edit mode, this is no more needed
		add_action( 'et_fb_before_comments_template', 'Divi_Theme_Builder::js_comment_box_on' );
		add_action( 'et_fb_after_comments_template', 'Divi_Theme_Builder::js_comment_box_off' );
		add_filter( 'litespeed_esi_params-comment-form', 'Divi_Theme_Builder::esi_comment_add_slash' );// Note: this is changed in v2.9.8.1
		*/
	}

	/*
	 * Enable comment box JS mode (legacy code - currently disabled).
	 *
	public static function js_comment_box_on() {
		self::$js_comment_box = true;
	}

	public static function js_comment_box_off() {
		self::$js_comment_box = false;
	}

	public static function esi_comment_add_slash( $params ) {
		if ( self::$js_comment_box ) {
			$params['is_json'] = 1;
			$params['_ls_silence'] = 1;
		}
		return $params;
	}
	*/
}
