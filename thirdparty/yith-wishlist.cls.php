<?php
/**
 * The Third Party integration with the YITH WooCommerce Wishlist plugin.
 *
 * Hooks YITH Wishlist UI into LiteSpeed ESI and purges appropriately.
 *
 * @since 1.1.0
 * @package LiteSpeed
 */

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit();

use LiteSpeed\Tag;
use LiteSpeed\Conf;
use LiteSpeed\Base;

/**
 * YITH WooCommerce Wishlist integration for LiteSpeed Cache.
 */
class Yith_Wishlist {

	const ESI_PARAM_POSTID = 'yith_pid';

	/**
	 * Current product ID captured for ESI rendering.
	 *
	 * @var int
	 */
	private static $_post_id;

	/**
	 * Detects if YITH WooCommerce Wishlist and WooCommerce are installed.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function detect() {
		if ( ! defined( 'WOOCOMMERCE_VERSION' ) || ! defined( 'YITH_WCWL' ) ) {
			return;
		}
		if ( apply_filters( 'litespeed_esi_status', false ) ) {
			add_action( 'litespeed_tpl_normal', __CLASS__ . '::is_not_esi' );
			add_action( 'litespeed_esi_load-yith_wcwl_add', __CLASS__ . '::load_add_to_wishlist' );
			add_filter( 'litespeed_esi_inline-yith_wcwl_add', __CLASS__ . '::inline_add_to_wishlist', 20, 2 );

			// Hook to add/delete wishlist.
			add_action( 'yith_wcwl_added_to_wishlist', __CLASS__ . '::purge' );
			add_action( 'yith_wcwl_removed_from_wishlist', __CLASS__ . '::purge' );
		}
	}

	/**
	 * Purge ESI YITH cache when add/remove items.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function purge() {
		do_action( 'litespeed_purge_esi', 'yith_wcwl_add' );
	}

	/**
	 * Hooked to the litespeed_is_not_esi_template action.
	 *
	 * If the request is not an ESI request, hook to the add to wishlist button
	 * filter to replace it as an ESI block.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function is_not_esi() {
		add_filter( 'yith_wcwl_add_to_wishlist_params', __CLASS__ . '::add_to_wishlist_params', 999, 2 );
		add_filter( 'yith_wcwl_add_to_wishlisth_button_html', __CLASS__ . '::sub_add_to_wishlist', 999 );
	}

	/**
	 * Store the post id for later shortcode usage.
	 *
	 * @since 3.4.1
	 *
	 * @param array $defaults Default parameters provided by YITH.
	 * @param array $atts     Shortcode attributes for add-to-wishlist.
	 * @return array Unmodified defaults.
	 */
	public static function add_to_wishlist_params( $defaults, $atts ) {
		self::$_post_id = ! empty( $atts['product_id'] ) ? (int) $atts['product_id'] : (int) $defaults['product_id'];
		return $defaults;
	}

	/**
	 * Replace the native button HTML with an ESI block.
	 *
	 * The add to wishlist button displays a different output when the item is already
	 * in the wishlist/cart. For this reason, the button must be an ESI block.
	 *
	 * @since 1.1.0
	 *
	 * @param string $template Original button HTML.
	 * @return string ESI URL placeholder for rendering.
	 */
	public static function sub_add_to_wishlist( $template ) {
		$params = [
			self::ESI_PARAM_POSTID => self::$_post_id,
		];

		$inline_tags  = [ '', rtrim( Tag::TYPE_ESI, '.' ), Tag::TYPE_ESI . 'yith_wcwl_add' ];
		$inline_tags  = implode(
			',',
			array_map(
				function ( $val ) {
					return 'public:' . LSWCP_TAG_PREFIX . '_' . $val;
				},
				$inline_tags
			)
		);
		$inline_tags .= ',' . LSWCP_TAG_PREFIX . '_tag_priv';

		do_action( 'litespeed_esi_combine', 'yith_wcwl_add' );

		$inline_params = [
			'val'     => $template,
			'tag'     => $inline_tags,
			'control' => 'private,no-vary,max-age=' . Conf::cls()->conf( Base::O_CACHE_TTL_PRIV ),
		];

		return apply_filters( 'litespeed_esi_url', 'yith_wcwl_add', 'YITH ADD TO WISHLIST', $params, 'private,no-vary', false, false, false, $inline_params );
	}

	/**
	 * Load the add to wishlist button HTML for ESI output.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params ESI parameters, expects product id under ESI_PARAM_POSTID.
	 * @return void
	 */
	public static function load_add_to_wishlist( $params ) {
		$pid = isset( $params[ self::ESI_PARAM_POSTID ] ) ? (int) $params[ self::ESI_PARAM_POSTID ] : 0;

		// Output the rendered shortcode safely.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post handles allowed HTML.
		echo wp_kses_post( \YITH_WCWL_Shortcode::add_to_wishlist( [ 'product_id' => $pid ] ) );

		do_action( 'litespeed_control_set_private', 'yith wishlist' );
		do_action( 'litespeed_vary_no' );
	}

	/**
	 * Generate ESI inline value.
	 *
	 * @since 3.4.2
	 *
	 * @param mixed $res    Current response (array or anything); will be normalized to array.
	 * @param array $params ESI parameters that include product id.
	 * @return array Inline ESI payload with value, control and tags.
	 */
	public static function inline_add_to_wishlist( $res, $params ) {
		if ( ! is_array( $res ) ) {
			$res = [];
		}

		$pid = isset( $params[ self::ESI_PARAM_POSTID ] ) ? (int) $params[ self::ESI_PARAM_POSTID ] : 0;

		$res['val']     = \YITH_WCWL_Shortcode::add_to_wishlist( [ 'product_id' => $pid ] );
		$res['control'] = 'private,no-vary,max-age=' . Conf::cls()->conf( Base::O_CACHE_TTL_PRIV );

		$inline_tags  = [ '', rtrim( Tag::TYPE_ESI, '.' ), Tag::TYPE_ESI . 'yith_wcwl_add' ];
		$inline_tags  = implode(
			',',
			array_map(
				function ( $val ) {
					return 'public:' . LSWCP_TAG_PREFIX . '_' . $val;
				},
				$inline_tags
			)
		);
		$inline_tags .= ',' . LSWCP_TAG_PREFIX . '_tag_priv';

		$res['tag'] = $inline_tags;

		return $res;
	}
}
