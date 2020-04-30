<?php
/**
 * The Third Party integration with the YITH WooCommerce Wishlist plugin.
 *
 * @since		1.1.0
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

class Yith_Wishlist
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
		if ( ! defined( 'WOOCOMMERCE_VERSION' ) || ! defined( 'YITH_WCWL' ) ) {
			return ;
		}
		if ( apply_filters( 'litespeed_esi_status', false ) ) {
			add_action( 'litespeed_tpl_normal', __CLASS__ . '::is_not_esi' );
			add_action( 'litespeed_esi_load-yith_wcwl_add', __CLASS__ . '::load_add_to_wishlist' );

			// hook to add/delete wishlist
			add_action( 'yith_wcwl_added_to_wishlist', __CLASS__ . '::purge' ) ;
			add_action( 'yith_wcwl_removed_from_wishlist', __CLASS__ . '::purge' ) ;
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
		do_action( 'litespeed_purge_esi', 'yith_wcwl_add' );
	}

	/**
	 * Hooked to the litespeed_is_not_esi_template action.
	 *
	 * If the request is not an ESI request, hook to the add to wishlist button
	 * filter to replace it as an esi block.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function is_not_esi()
	{
		add_filter( 'yith_wcwl_add_to_wishlisth_button_html', __CLASS__ . '::sub_add_to_wishlist', 999 ) ;

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
			self::ESI_PARAM_POSTID => $post->ID,
		) ;
		return apply_filters( 'litespeed_esi_url', 'yith_wcwl_add', 'YITH ADD TO WISHLIST', $params );
	}

	/**
	 * Hooked to the litespeed_esi_load-yith_wcwl_add action.
	 *
	 * This will load the add to wishlist button html for output.
	 *
	 * @since 1.1.0
	 * @access public
	 * @global $post, $wp_query
	 * @param array $params The input ESI parameters.
	 */
	public static function load_add_to_wishlist( $params )
	{
		global $post, $wp_query ;
		$post = get_post( $params[ self::ESI_PARAM_POSTID ] ) ;
		$wp_query->setup_postdata( $post ) ;
		echo \YITH_WCWL_Shortcode::add_to_wishlist( /*$params[self::ESI_PARAM_ATTS]*/array() ) ;
		do_action( 'litespeed_control_set_private', 'yith wishlist' );
		do_action( 'litespeed_vary_no' );
	}

}
