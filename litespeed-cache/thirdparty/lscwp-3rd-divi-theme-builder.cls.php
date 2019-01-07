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
LiteSpeed_Cache_API::register( 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder' ) ;

class LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder
{
	private static $js_comment_box = false ;

	public static function detect()
	{
		if ( ! defined( 'ET_CORE' ) ) return ;
        
		add_action( 'et_fb_before_comments_template', 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder::js_comment_box_on' ) ;
		add_action( 'et_fb_after_comments_template', 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder::js_comment_box_off' ) ;
		add_filter( 'litespeed_cache_sub_esi_params-comment-form', 'LiteSpeed_Cache_ThirdParty_Divi_Theme_Builder::esi_comment_add_slash' ) ;
	}

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
}
