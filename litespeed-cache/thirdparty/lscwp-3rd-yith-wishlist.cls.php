<?php

/**
 * The Third Party integration with the YITH WooCommerce Wishlist plugin.
 *
 * @since		1.1.0
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
    die() ;
}

LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_Yith_Wishlist') ;

class LiteSpeed_Cache_ThirdParty_Yith_Wishlist
{
	const ESI_PARAM_ATTS = 'yith_wcwl_atts' ;
	const ESI_PARAM_POSTID = 'yith_wcwl_post_id' ;
	private static $atts = null ; // Not currently used. Depends on how YITH adds attributes

	/**
	 * Detects if YITH WooCommerce Wishlist and WooCommerce are installed.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function detect()
	{
		if ( ! defined('WOOCOMMERCE_VERSION') || ! defined('YITH_WCWL') ) {
			return ;
		}
		if ( LiteSpeed_Cache_API::esi_enabled() ) {
			LiteSpeed_Cache_API::hook_tpl_not_esi('LiteSpeed_Cache_ThirdParty_Yith_Wishlist::is_not_esi') ;
			LiteSpeed_Cache_API::hook_tpl_esi('yith-wcwl-add', 'LiteSpeed_Cache_ThirdParty_Yith_Wishlist::load_add_to_wishlist') ;

			// hook to add/delete wishlist
			add_action( 'yith_wcwl_added_to_wishlist', 'LiteSpeed_Cache_ThirdParty_Yith_Wishlist::purge' ) ;
			add_action( 'yith_wcwl_removed_from_wishlist', 'LiteSpeed_Cache_ThirdParty_Yith_Wishlist::purge' ) ;
		}
	}

	/**
	 * Purge ESI yith cache when add/remove items
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function purge()
	{
		LiteSpeed_Cache_API::purge( LiteSpeed_Cache_Tag::TYPE_ESI . 'yith-wcwl-add' ) ;
	}

	/**
	 * Hooked to the litespeed_cache_is_not_esi_template action.
	 *
	 * If the request is not an ESI request, hook to the add to wishlist button
	 * filter to replace it as an esi block.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function is_not_esi()
	{
		add_filter('yith_wcwl_add_to_wishlisth_button_html', 'LiteSpeed_Cache_ThirdParty_Yith_Wishlist::sub_add_to_wishlist', 999) ;

	}

	/**
	 * Hooked to the yith_wcwl_add_to_wishlisth_button_html filter.
	 *
	 * The add to wishlist button displays a different output when the item
	 * is already in the wishlist/cart. For this reason, the button must be
	 * an ESI block. This function replaces the normal html with the ESI
	 * block.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param $template unused
	 * @return string The html for future callbacks to filter.
	 */
	public static function sub_add_to_wishlist( $template )
	{
		global $post ;
		$params = array(
			self::ESI_PARAM_POSTID => $post->ID
		) ;
		return LiteSpeed_Cache_API::esi_url( 'yith-wcwl-add', 'YITH ADD TO WISHLIST', $params ) ;
	}

	/**
	 * Hooked to the litespeed_cache_load_esi_block-yith-wcwl-add action.
	 *
	 * This will load the add to wishlist button html for output.
	 *
	 * @since 1.1.0
	 * @access public
	 * @global $post, $wp_query
	 * @param array $params The input ESI parameters.
	 */
	public static function load_add_to_wishlist($params)
	{
		global $post, $wp_query ;
		$post = get_post($params[self::ESI_PARAM_POSTID]) ;
		$wp_query->setup_postdata($post) ;
		echo YITH_WCWL_Shortcode::add_to_wishlist(/*$params[self::ESI_PARAM_ATTS]*/array()) ;
		LiteSpeed_Cache_API::set_cache_private();
		LiteSpeed_Cache_API::set_cache_no_vary();
	}

}
