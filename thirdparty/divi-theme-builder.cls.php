<?php
// phpcs:ignoreFile
/**
 * The Third Party integration with DIVI Theme.
 *
 * @since       2.9.0
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class Divi_Theme_Builder {

	// private static $js_comment_box = false;

	/**
	 * Check if is Edit mode in frontend, disable all LSCWP features to avoid breaking page builder
	 *
	 * @since 2.9.7.2 #435538 #581740 #977284
	 * @since  2.9.9.1 Added 'et_pb_preview' for loading image from library in divi page edit
	 */
	public static function preload() {
		if (!function_exists('et_setup_theme')) {
			return;
		}
		if (!empty($_GET['et_fb']) || !empty($_GET['et_pb_preview']) || (!empty($_GET['p']) && !empty($_GET['preview']) && $_GET['preview'] === 'true')) {
			do_action('litespeed_disable_all', 'divi edit mode');
		}
	}

	public static function detect() {
		if (!defined('ET_CORE')) {
			return;
		}

		// As DIVI will set page to non-cacheable for the 1st visit to generate CCSS, will need to ignore that no-cache for crawler
		defined('LITESPEED_CRAWLER_IGNORE_NONCACHEABLE') || define('LITESPEED_CRAWLER_IGNORE_NONCACHEABLE', true);

		/**
		 * Add contact form to nonce
		 *
		 * @since  2.9.7.1 #475461
		 */
		do_action('litespeed_nonce', 'et-pb-contact-form-submit');

		/**
		 * Subscribe module and A/B logging
		 *
		 * @since  3.0 @Robert Staddon
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
	public static function js_comment_box_on() {
		self::$js_comment_box = true;
	}

	public static function js_comment_box_off() {
		self::$js_comment_box = false;
	}

	public static function esi_comment_add_slash( $params )
	{
		if ( self::$js_comment_box ) {
			$params[ 'is_json' ] = 1;
			$params[ '_ls_silence' ] = 1;
		}

		return $params;
	}
	*/
}
