<?php
// phpcs:ignoreFile
/**
 * The Third Party integration with the YITH WooCommerce Wishlist plugin.
 *
 * @since       1.1.0
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

use LiteSpeed\Tag;
use LiteSpeed\Conf;
use LiteSpeed\Base;

class Yith_Wishlist {

	const ESI_PARAM_POSTID = 'yith_pid';
	private static $_post_id;

	/**
	 * Detects if YITH WooCommerce Wishlist and WooCommerce are installed.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function detect() {
		if (!defined('WOOCOMMERCE_VERSION') || !defined('YITH_WCWL')) {
			return;
		}
		if (apply_filters('litespeed_esi_status', false)) {
			add_action('litespeed_tpl_normal', __CLASS__ . '::is_not_esi');
			add_action('litespeed_esi_load-yith_wcwl_add', __CLASS__ . '::load_add_to_wishlist');
			add_filter('litespeed_esi_inline-yith_wcwl_add', __CLASS__ . '::inline_add_to_wishlist', 20, 2);

			// hook to add/delete wishlist
			add_action('yith_wcwl_added_to_wishlist', __CLASS__ . '::purge');
			add_action('yith_wcwl_removed_from_wishlist', __CLASS__ . '::purge');
		}
	}

	/**
	 * Purge ESI yith cache when add/remove items
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public static function purge() {
		do_action('litespeed_purge_esi', 'yith_wcwl_add');
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
	public static function is_not_esi() {
		add_filter('yith_wcwl_add_to_wishlist_params', __CLASS__ . '::add_to_wishlist_params', 999, 2);

		add_filter('yith_wcwl_add_to_wishlisth_button_html', __CLASS__ . '::sub_add_to_wishlist', 999);
	}

	/**
	 * Store the post id for later shortcode usage
	 *
	 * @since  3.4.1
	 */
	public static function add_to_wishlist_params( $defaults, $atts ) {
		self::$_post_id = !empty($atts['product_id']) ? $atts['product_id'] : $defaults['product_id'];

		return $defaults;
	}

	/**
	 * Hooked to the yith_wcwl_add_to_wishlisth_button_html filter.
	 *
	 * The add to wishlist button displays a different output when the item is already in the wishlist/cart.
	 * For this reason, the button must be an ESI block. This function replaces the normal html with the ESI block.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function sub_add_to_wishlist( $template ) {
		$params = array(
			self::ESI_PARAM_POSTID => self::$_post_id,
		);

		$inline_tags  = array( '', rtrim(Tag::TYPE_ESI, '.'), Tag::TYPE_ESI . 'yith_wcwl_add' );
		$inline_tags  = implode(
			',',
			array_map(function ( $val ) {
				return 'public:' . LSWCP_TAG_PREFIX . '_' . $val;
			}, $inline_tags)
		);
		$inline_tags .= ',' . LSWCP_TAG_PREFIX . '_tag_priv';

		do_action('litespeed_esi_combine', 'yith_wcwl_add');

		$inline_params = array(
			'val' => $template,
			'tag' => $inline_tags,
			'control' => 'private,no-vary,max-age=' . Conf::cls()->conf(Base::O_CACHE_TTL_PRIV),
		);

		return apply_filters('litespeed_esi_url', 'yith_wcwl_add', 'YITH ADD TO WISHLIST', $params, 'private,no-vary', false, false, false, $inline_params);
	}

	/**
	 * Hooked to the litespeed_esi_load-yith_wcwl_add action.
	 *
	 * This will load the add to wishlist button html for output.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function load_add_to_wishlist( $params ) {
		// global $post, $wp_query;
		// $post = get_post( $params[ self::ESI_PARAM_POSTID ] );
		// $wp_query->setup_postdata( $post );
		echo \YITH_WCWL_Shortcode::add_to_wishlist(array( 'product_id' => $params[self::ESI_PARAM_POSTID] ));
		do_action('litespeed_control_set_private', 'yith wishlist');
		do_action('litespeed_vary_no');
	}

	/**
	 * Generate ESI inline value
	 *
	 * @since  3.4.2
	 */
	public static function inline_add_to_wishlist( $res, $params ) {
		if (!is_array($res)) {
			$res = array();
		}

		$pid = $params[self::ESI_PARAM_POSTID];

		$res['val'] = \YITH_WCWL_Shortcode::add_to_wishlist(array( 'product_id' => $pid ));

		$res['control'] = 'private,no-vary,max-age=' . Conf::cls()->conf(Base::O_CACHE_TTL_PRIV);

		$inline_tags  = array( '', rtrim(Tag::TYPE_ESI, '.'), Tag::TYPE_ESI . 'yith_wcwl_add' );
		$inline_tags  = implode(
			',',
			array_map(function ( $val ) {
				return 'public:' . LSWCP_TAG_PREFIX . '_' . $val;
			}, $inline_tags)
		);
		$inline_tags .= ',' . LSWCP_TAG_PREFIX . '_tag_priv';

		$res['tag'] = $inline_tags;

		return $res;
	}
}
