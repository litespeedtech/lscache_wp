<?php
/**
 * The Third Party integration with DIVI Theme.
 *
 * @since		2.9.0
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	die() ;
}

LiteSpeed_Cache_API::hook_init( 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder::pre_load' ) ;
LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder' ) ;

class LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder
{
	// private static $js_comment_box = false ;

	/**
	 * Check if is Edit mode in frontend, disable all LSCWP features to avoid breaking page builder
	 *
	 * @since 2.9.7.2 #435538 #581740 #977284
	 * @since  2.9.9.1 Added 'et_pb_preview' for loading image from library in divi page edit
	 */
	public static function pre_load()
	{
		if ( ! function_exists( 'et_setup_theme' ) ) return ;
		if ( ! empty( $_GET[ 'et_fb' ] ) || ! empty( $_GET[ 'et_pb_preview' ] ) ) {
			LiteSpeed_Cache_API::disable_all( 'divi edit mode' ) ;
		}
	}

	public static function detect()
	{
		if ( ! defined( 'ET_CORE' ) ) return ;
		/**
		 * Add contact form to nonce
		 * @since  2.9.7.1 #475461
		 */
		LiteSpeed_Cache_API::nonce_action( 'et-pb-contact-form-submit' ) ;

		/*
		// the comment box fix is for user using theme builder, ESI will load the wrong json string
		// As we disabled all for edit mode, this is no more needed
		add_action( 'et_fb_before_comments_template', 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder::js_comment_box_on' ) ;
		add_action( 'et_fb_after_comments_template', 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder::js_comment_box_off' ) ;
		add_filter( 'litespeed_cache_sub_esi_params-comment-form', 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder::esi_comment_add_slash' ) ;// Note: this is changed in v2.9.8.1
		*/
	}

	/*
	public static function js_comment_box_on() {
		self::$js_comment_box = true ;
	}

	public static function js_comment_box_off() {
		self::$js_comment_box = false ;
	}

	public static function esi_comment_add_slash( $params )
	{
		if ( self::$js_comment_box ) {
			$params[ 'is_json' ] = 1 ;
			$params[ '_ls_silence' ] = 1 ;
		}

		return $params ;
	}
	*/
}
