<?php
/**
 * The Third Party integration with the WooCommerce plugin.
 *
 * @since         1.0.5
 * @since  1.6.6 Added function_exists check for compatibility
 * @package       LiteSpeed
 * @subpackage    LiteSpeed_Cache/thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

use LiteSpeed\API;
use LiteSpeed\Base;
use LiteSpeed\ESI;

/**
 * WooCommerce third-party integration for LiteSpeed Cache.
 *
 * Handles ESI blocks, cache tags, purging behaviors, and settings integration
 * specific to WooCommerce.
 */
class WooCommerce extends Base {

	const O_CACHE_TTL_FRONTPAGE = Base::O_CACHE_TTL_FRONTPAGE;

	const CACHETAG_SHOP     = 'WC_S';
	const CACHETAG_TERM     = 'WC_T.';
	const O_UPDATE_INTERVAL = 'wc_update_interval';
	const O_CART_VARY       = 'wc_cart_vary';
	const O_PQS_CS          = 0; // flush product on quantity + stock change, categories on stock change
	const O_PS_CS           = 1; // flush product and categories on stock change
	const O_PS_CN           = 2; // flush product on stock change, categories no flush
	const O_PQS_CQS         = 3; // flush product and categories on quantity + stock change

	const ESI_PARAM_ARGS    = 'wc_args';
	const ESI_PARAM_POSTID  = 'wc_post_id';
	const ESI_PARAM_NAME    = 'wc_name';
	const ESI_PARAM_PATH    = 'wc_path';
	const ESI_PARAM_LOCATED = 'wc_located';

	/**
	 * Whether ESI is enabled via litespeed_esi_status filter.
	 *
	 * @var bool
	 */
	private $esi_enabled;

	/**
	 * Detects if WooCommerce is installed.
	 *
	 * @since 1.0.5
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if ( ! defined('WOOCOMMERCE_VERSION') ) {
			return;
		}

		self::cls()->add_hooks();
	}

	/**
	 * Add hooks to woo actions.
	 *
	 * @since  1.6.3
	 * @access public
	 * @return void
	 */
	public function add_hooks() {
		$this->_option_append();

		$this->esi_enabled = (bool) apply_filters('litespeed_esi_status', false);

		add_action('litespeed_control_finalize', [ $this, 'set_control' ]);
		add_action('litespeed_tag_finalize', [ $this, 'set_tag' ]);

		// Purging a product on stock change should only occur during product purchase. This function will add the purging callback when an order is complete.
		add_action('woocommerce_product_set_stock', [ $this, 'purge_product' ]);
		add_action('woocommerce_variation_set_stock', [ $this, 'purge_product' ]); // #984479 Update variations stock

		add_action('comment_post', [ $this, 'add_review' ], 10, 3);

		if ( $this->esi_enabled ) {
			if ( function_exists('is_shop') && ! is_shop() ) {
				add_action('litespeed_tpl_normal', [ $this, 'set_block_template' ]);
				// No need for add-to-cart button
				// add_action( 'litespeed_esi_load-wc-add-to-cart-form', [ $this, 'load_add_to_cart_form_block' ] ) ;

				add_action('litespeed_esi_load-storefront-cart-header', [ $this, 'load_cart_header' ]);
				add_action('litespeed_esi_load-widget', [ $this, 'register_post_view' ]);
			}

			if ( function_exists('is_product') && is_product() ) {
				add_filter('litespeed_esi_params', [ $this, 'add_post_id' ], 10, 2);
			}

			// #612331 - remove WooCommerce geolocation redirect on ESI page (PR#708)
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ESI parameter detection, not processing user input.
			if (!empty($_GET[ESI::QS_ACTION]) && !empty($_GET[ESI::QS_PARAMS])) {
				remove_action( 'template_redirect', [ 'WC_Cache_Helper', 'geolocation_ajax_redirect' ], 10 );
			}
		}

		if ( is_admin() ) {
			add_action('litespeed_api_purge_post', [ $this, 'backend_purge' ]); // todo
			add_action('delete_term_relationships', [ $this, 'delete_rel' ], 10, 2);
			add_action('litespeed_settings_tab', [ $this, 'settings_add_tab' ]);
			add_action('litespeed_settings_content', [ $this, 'settings_add_content' ]);
			add_filter('litespeed_widget_default_options', [ $this, 'wc_widget_default' ], 10, 2);
		}

		if ( apply_filters('litespeed_conf', self::O_CART_VARY) ) {
			add_filter(
				'litespeed_vary_cookies',
				/**
				 * Modify list of vary cookies.
				 *
				 * @param string[] $cookie_list List of vary cookies.
				 * @return string[]
				 */
				function ( $cookie_list ) {
					$cookie_list[] = 'woocommerce_cart_hash';
					return array_unique( $cookie_list );
				}
			);
		}
	}

	/**
	 * Purge esi private tag.
	 *
	 * @since  1.6.3
	 * @access public
	 * @return void
	 */
	public function purge_esi() {
		do_action('litespeed_debug', '3rd woo purge ESI in action: ' . current_filter());
		do_action('litespeed_purge_private_esi', 'storefront-cart-header');
	}

	/**
	 * Purge private all.
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function purge_private_all() {
		do_action('litespeed_purge_private_all');
	}

	/**
	 * Check if need to give an ESI block for cart.
	 *
	 * @since  1.7.2
	 * @access public
	 *
	 * @param string $template Template path.
	 * @return string Template path.
	 */
	public function check_if_need_esi( $template ) {
		if ( $this->vary_needed() ) {
			do_action('litespeed_debug', 'API: 3rd woo added ESI');
			add_action('litespeed_tpl_normal', [ $this, 'set_swap_header_cart' ]);
		}

		return $template;
	}

	/**
	 * Keep vary on if cart is not empty.
	 *
	 * @since  1.7.2
	 * @access public
	 *
	 * @param array $vary Vary array.
	 * @return array
	 */
	public function vary_maintain( $vary ) {
		if ( $this->vary_needed() ) {
			do_action('litespeed_debug', 'API: 3rd woo added vary due to cart not empty');
			$vary['woo_cart'] = 1;
		}

		return $vary;
	}

	/**
	 * Check if vary need to be on based on cart.
	 *
	 * @since  1.7.2
	 * @access private
	 * @return bool True when cart has items.
	 */
	private function vary_needed() {
		if ( ! function_exists('WC') ) {
			return false;
		}

		$woocom = WC();
		if ( ! $woocom ) {
			return false;
		}

		if ( is_null( $woocom->cart ) ) {
			return false;
		}
		return $woocom->cart->get_cart_contents_count() > 0;
	}

	/**
	 * Hooked to the litespeed_is_not_esi_template action.
	 * If the request is not an esi request, set a hook in woocommerce_before_template_part to see if it's something we can ESI.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return void
	 */
	public function set_block_template() {
		add_action('woocommerce_before_template_part', [ $this, 'block_template' ], 999, 4);
	}

	/**
	 * Hooked to the litespeed_is_not_esi_template action.
	 * If the request is not an esi request, set a hook in storefront_header to see if it's something we can ESI.
	 *
	 * Will remove storefront_header_cart in storefront_header.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 * @return void
	 */
	public function set_swap_header_cart() {
		$priority = has_action('storefront_header', 'storefront_header_cart');
		if ( false !== $priority ) {
			remove_action('storefront_header', 'storefront_header_cart', $priority);
			add_action('storefront_header', [ $this, 'esi_cart_header' ], $priority);
		}
	}

	/**
	 * Hooked to the woocommerce_before_template_part action.
	 * Checks if the template contains 'add-to-cart'. If so, and if we want to ESI the request, block it and build the ESI code block.
	 *
	 * The function parameters will be passed to the ESI request.
	 *
	 * @since 1.1.0
	 * @access public
	 *
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @param string $located       Located template path.
	 * @param array  $args          Template args.
	 * @return void
	 */
	public function block_template( $template_name, $template_path, $located, $args ) {
		if ( false === strpos( $template_name, 'add-to-cart' ) ) {
			if ( false !== strpos( $template_name, 'related.php' ) ) {
				remove_action('woocommerce_before_template_part', [ $this, 'block_template' ], 999);
				add_filter('woocommerce_related_products_args', [ $this, 'add_related_tags' ]);
				add_action('woocommerce_after_template_part', [ $this, 'end_template' ], 999);
			}
			return;
		}

		// Build ESI block for add-to-cart.
		// $post_obj = get_post( get_the_ID() );
		// if ( ! $post_obj ) {
		//  return;
		// }

		// $params = [
		//  self::ESI_PARAM_ARGS    => $args,
		//  self::ESI_PARAM_NAME    => $template_name,
		//  self::ESI_PARAM_POSTID  => $post_obj->ID,
		//  self::ESI_PARAM_PATH    => $template_path,
		//  self::ESI_PARAM_LOCATED => $located,
		// ];
		// add_action('woocommerce_after_add_to_cart_form', [ $this, 'end_form' ]);
		// add_action('woocommerce_after_template_part', [ $this, 'end_form' ], 999);

		// // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filter returns a URL; escaped below.
		// echo esc_url( apply_filters( 'litespeed_esi_url', 'wc-add-to-cart-form', 'WC_CART_FORM', $params ) );
		// // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allow basic wrapper markup.
		// echo wp_kses_post( apply_filters( 'litespeed_clean_wrapper_begin', '' ) );
	}

	/**
	 * Hooked to the woocommerce_after_add_to_cart_form action.
	 * If this is hit first, clean the buffer and remove this function and end_template.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param string $template_name Template name.
	 * @return void
	 */
	public function end_form( $template_name = '' ) {
		if ( ! empty( $template_name ) && false === strpos( $template_name, 'add-to-cart' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allow basic wrapper markup.
		echo wp_kses_post( apply_filters( 'litespeed_clean_wrapper_end', '' ) );
		remove_action('woocommerce_after_add_to_cart_form', [ $this, 'end_form' ]);
		remove_action('woocommerce_after_template_part', [ $this, 'end_form' ], 999);
	}

	/**
	 * If related products are loaded, need to add the extra product ids.
	 *
	 * The page will be purged if any of the products are changed.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param array $args The arguments used to build the related products section.
	 * @return array The unchanged arguments.
	 */
	public function add_related_tags( $args ) {
		if ( empty( $args ) || ! isset( $args['post__in'] ) ) {
			return $args;
		}
		$related_posts = $args['post__in'];
		foreach ( $related_posts as $related ) {
			do_action('litespeed_tag_add_post', $related);
		}
		return $args;
	}

	/**
	 * Hooked to the woocommerce_after_template_part action.
	 * If the template contains 'related.php', restore block hook.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param string $template_name Template name.
	 * @return void
	 */
	public function end_template( $template_name ) {
		if ( false !== strpos( $template_name, 'related.php' ) ) {
			remove_action('woocommerce_after_template_part', [ $this, 'end_template' ], 999);
			$this->set_block_template();
		}
	}

	/**
	 * Hooked to the storefront_header header.
	 * If we want to ESI the request, block it and build the ESI code block.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 * @return void
	 */
	public function esi_cart_header() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filter returns URL.
		echo apply_filters( 'litespeed_esi_url', 'storefront-cart-header', 'STOREFRONT_CART_HEADER' );
	}

	/**
	 * Hooked to the litespeed_esi_load-storefront-cart-header action.
	 * Generates the cart header for ESI display.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 * @return void
	 */
	public function load_cart_header() {
		storefront_header_cart();
	}

	/**
	 * Hooked to the litespeed_esi_load-wc-add-to-cart-form action.
	 * Parses the ESI input parameters and generates the add to cart form for ESI display.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param array $params ESI parameters.
	 * @return void
	 */
	public function load_add_to_cart_form_block( $params ) {
		global $post, $wp_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intentional for WooCommerce template setup.
		$post = get_post($params[self::ESI_PARAM_POSTID]);
		$wp_query->setup_postdata($post);
		function_exists('wc_get_template') && wc_get_template($params[self::ESI_PARAM_NAME], $params[self::ESI_PARAM_ARGS], $params[self::ESI_PARAM_PATH]);
	}

	/**
	 * Update WooCommerce when someone visits a product and has the recently viewed products widget.
	 *
	 * Currently, this widget should not be cached.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param array $params Widget parameter array.
	 * @return void
	 */
	public function register_post_view( $params ) {
		if ( 'WC_Widget_Recently_Viewed' !== $params[ API::PARAM_NAME ] ) {
			return;
		}
		if ( ! isset( $params[ self::ESI_PARAM_POSTID ] ) ) {
			return;
		}
		$id       = (int) $params[ self::ESI_PARAM_POSTID ];
		$esi_post = get_post( $id );
		$product  = function_exists('wc_get_product') ? wc_get_product( $esi_post ) : false;

		if ( empty( $product ) ) {
			return;
		}

		// Setup post data for tracking view without directly overriding $post.
		if ( $esi_post ) {
			setup_postdata( $esi_post );
		}
		if ( function_exists('wc_track_product_view') ) {
			wc_track_product_view();
		}
	}

	/**
	 * Adds the post id to the widget ESI parameters for the Recently Viewed widget.
	 *
	 * This is needed in the ESI request to update the cookie properly.
	 *
	 * @since 1.1.0
	 * @access public
	 *
	 * @param array  $params   Existing ESI params.
	 * @param string $block_id Block identifier.
	 * @return array
	 */
	public function add_post_id( $params, $block_id ) {
		if ( 'widget' === $block_id ) {
			if ( 'WC_Widget_Recently_Viewed' === $params[ API::PARAM_NAME ] ) {
				$params[ self::ESI_PARAM_POSTID ] = get_the_ID();
			}
		}

		return $params;
	}

	/**
	 * Hooked to the litespeed_widget_default_options filter.
	 *
	 * The recently viewed widget must be ESI to function properly.
	 * This function will set it to enable and no cache by default.
	 *
	 * @since 1.1.0
	 * @access public
	 *
	 * @param array $options Widget options.
	 * @param mixed $widget  Widget instance.
	 * @return array
	 */
	public function wc_widget_default( $options, $widget ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}
		$widget_name = get_class( $widget );
		if ( 'WC_Widget_Recently_Viewed' === $widget_name ) {
			$options[ API::WIDGET_O_ESIENABLE ] = API::VAL_ON2;
			$options[ API::WIDGET_O_TTL ]       = 0;
		} elseif ( 'WC_Widget_Recent_Reviews' === $widget_name ) {
			$options[ API::WIDGET_O_ESIENABLE ] = API::VAL_ON;
			$options[ API::WIDGET_O_TTL ]       = 86400;
		}
		return $options;
	}

	/**
	 * Set WooCommerce cache tags based on page type.
	 *
	 * @since 1.0.9
	 * @since 1.6.3 Removed static
	 * @access public
	 * @return void
	 */
	public function set_tag() {
		$id = get_the_ID();
		if ( false === $id ) {
			return;
		}

		// Check if product has a cache ttl limit or not.
		$sale_from = (int) get_post_meta( $id, '_sale_price_dates_from', true );
		$sale_to   = (int) get_post_meta( $id, '_sale_price_dates_to', true );
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Need local timestamp for WooCommerce sale dates.
		$now = current_time('timestamp');
		$ttl = false;

		if ( $sale_from && $now < $sale_from ) {
			$ttl = $sale_from - $now;
		} elseif ( $sale_to && $now < $sale_to ) {
			$ttl = $sale_to - $now;
		}
		if ( $ttl && $ttl < apply_filters( 'litespeed_control_ttl', 0 ) ) {
			do_action( 'litespeed_control_set_ttl', $ttl, "WooCommerce set scheduled TTL to $ttl" );
		}

		if ( function_exists('is_shop') && is_shop() ) {
			do_action('litespeed_tag_add', self::CACHETAG_SHOP);
		}
		if ( function_exists('is_product_taxonomy') && ! is_product_taxonomy() ) {
			return;
		}
		if ( isset( $GLOBALS['product_cat'] ) && is_string( $GLOBALS['product_cat'] ) ) {
			// todo: need to check previous woo version to find if its from old woo versions or not!
			$term = get_term_by( 'slug', $GLOBALS['product_cat'], 'product_cat' );
		} elseif ( isset( $GLOBALS['product_tag'] ) && is_string( $GLOBALS['product_tag'] ) ) {
			$term = get_term_by( 'slug', $GLOBALS['product_tag'], 'product_tag' );
		} else {
			$term = false;
		}

		if ( false === $term ) {
			return;
		}
		while ( isset( $term ) ) {
			do_action( 'litespeed_tag_add', self::CACHETAG_TERM . $term->term_id );
			if ( 0 === (int) $term->parent ) {
				break;
			}
			$term = get_term( $term->parent );
		}
	}

	/**
	 * Check if the page is cacheable according to WooCommerce.
	 *
	 * @since 1.0.5
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param string $esi_id The ESI block id if a request is an ESI request.
	 * @return void
	 */
	public function set_control( $esi_id ) {
		if ( ! apply_filters( 'litespeed_control_cacheable', false ) ) {
			return;
		}

		/**
		 * Avoid possible 500 issue.
		 *
		 * @since 1.6.2.1
		 */
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		$woocom = WC();
		if ( ! $woocom || empty( $woocom->session ) ) {
			return;
		}

		// For later versions, DONOTCACHEPAGE should be set.
		// No need to check uri/qs.
		if ( version_compare( $woocom->version, '1.4.2', '>=' ) ) {
			if ( version_compare( $woocom->version, '3.2.0', '<' ) && defined('DONOTCACHEPAGE') && DONOTCACHEPAGE ) {
				do_action( 'litespeed_control_set_nocache', '3rd party woocommerce not cache by constant' );
				return;
			} elseif ( version_compare( $woocom->version, '2.1.0', '>=' ) ) {
				$err = false;

				if ( ! function_exists( 'wc_get_page_id' ) ) {
					return;
				}
				/**
				 * From woo/inc/class-wc-cache-helper.php:prevent_caching()
				 *
				 * @since  1.4
				 */
				$page_ids = array_filter( [ wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ), wc_get_page_id( 'myaccount' ) ] );
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['download_file'] ) || isset( $_GET['add-to-cart'] ) || is_page( $page_ids ) ) {
					$err = 'woo non cacheable pages';
				} elseif ( function_exists( 'wc_notice_count' ) && wc_notice_count() > 0 ) {
					$err = 'has wc notice';
				}

				if ( $err ) {
					do_action( 'litespeed_control_set_nocache', '3rd party woocommerce not cache due to ' . $err );
					return;
				}
			}
			return;
		}

		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$uri     = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$uri_len = strlen( $uri );
		if ( $uri_len < 5 ) {
			return;
		}

		if ( in_array( $uri, [ 'cart/', 'checkout/', 'my-account/', 'addons/', 'logout/', 'lost-password/', 'product/' ], true ) ) {
			// why contains `product`?
			do_action( 'litespeed_control_set_nocache', 'uri in cart/account/user pages' );
			return;
		}

		if ( ! isset( $_SERVER['QUERY_STRING'] ) ) {
			return;
		}
		$qs     = sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
		$qs_len = strlen( $qs );
		if ( ! empty( $qs ) && $qs_len >= 12 && 0 === strpos( $qs, 'add-to-cart=' ) ) {
			do_action( 'litespeed_control_set_nocache', 'qs contains add-to-cart' );
		}
	}

	/**
	 * Purge a product page and related pages (based on settings) on checkout.
	 *
	 * @since 1.0.9
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param \WC_Product $product Product object.
	 * @return void
	 */
	public function purge_product( $product ) {
		do_action( 'litespeed_debug', '[3rd] Woo Purge [pid] ' . $product->get_id() );

		$do_purge = function ( $action, $debug = '' ) use ( $product ) {
			$config = apply_filters( 'litespeed_conf', self::O_UPDATE_INTERVAL );
			if ( is_null( $config ) ) {
				$config = self::O_PQS_CS;
			}

			if ( self::O_PQS_CQS === $config ) {
				$action();
				if ( $debug ) {
					do_action( 'litespeed_debug', $debug );
				}
			} elseif ( self::O_PQS_CS !== $config && $product->is_in_stock() ) {
				do_action( 'litespeed_debug', '[3rd] Woo No purge needed [option] ' . $config );
				return false;
			} elseif ( self::O_PS_CN !== $config && ! $product->is_in_stock() ) {
				$action();
				if ( $debug ) {
					do_action( 'litespeed_debug', $debug );
				}
			}
			return true;
		};

		if (
			! $do_purge(
				function () use ( $product ) {
					$this->backend_purge( $product->get_id() );
				}
			)
		) {
			return;
		}

		do_action( 'litespeed_purge_post', $product->get_id() );

		// Check if is variation, purge stock too #984479.
		if ( $product->is_type( 'variation' ) ) {
			do_action( 'litespeed_purge_post', $product->get_parent_id() );
		}

		// Check if WPML is enabled ##972971.
		if ( defined( 'WPML_PLUGIN_BASENAME' ) ) {
			// Check if it is a variable product and get post/parent ID.
			$wpml_purge_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$type          = apply_filters( 'wpml_element_type', get_post_type( $wpml_purge_id ) );
			$trid          = apply_filters( 'wpml_element_trid', false, $wpml_purge_id, $type );
			$translations  = apply_filters( 'wpml_get_element_translations', [], $trid, $type );
			foreach ( $translations as $lang => $translation ) {
				do_action( 'litespeed_debug', '[3rd] Woo WPML purge language: ' . $translation->language_code . ' , post ID: ' . $translation->element_id );
				do_action( 'litespeed_purge_post', $translation->element_id );
				// use the $translation->element_id as it is post ID of other languages.
			}

			// Check other languages category and purge if configured.
			// wp_get_post_terms() only returns default language category ID.
			$default_cats = wp_get_post_terms( $wpml_purge_id, 'product_cat' );
			$languages    = apply_filters( 'wpml_active_languages', null );

			foreach ( $default_cats as $default_cat ) {
				foreach ( $languages as $language ) {
					$tr_cat_id = icl_object_id( $default_cat->term_id, 'product_cat', false, $language['code'] );
					$do_purge(
						function () use ( $tr_cat_id ) {
							do_action( 'litespeed_purge', self::CACHETAG_TERM . $tr_cat_id );
						},
						'[3rd] Woo Purge WPML category [language] ' . $language['code'] . ' [cat] ' . $tr_cat_id
					);
				}
			}
		}
	}

	/**
	 * Delete object-term relationship. If the post is a product and the term ids array is not empty,
	 * will add purge tags to the deleted terms.
	 *
	 * @since 1.0.9
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param int   $post_id  Object ID.
	 * @param array $term_ids An array of term taxonomy IDs.
	 * @return void
	 */
	public function delete_rel( $post_id, $term_ids ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		if ( empty( $term_ids ) || false === wc_get_product( $post_id ) ) {
			return;
		}
		foreach ( $term_ids as $term_id ) {
			do_action( 'litespeed_purge', self::CACHETAG_TERM . $term_id );
		}
	}

	/**
	 * Purge a product's categories and tags pages in case they are affected.
	 *
	 * @since 1.0.9
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param int $post_id Post id that is about to be purged.
	 * @return void
	 */
	public function backend_purge( $post_id ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		if ( ! isset( $post_id ) || false === wc_get_product( $post_id ) ) {
			return;
		}

		$cats = $this->get_cats( $post_id );
		if ( ! empty( $cats ) ) {
			foreach ( $cats as $cat ) {
				do_action( 'litespeed_purge', self::CACHETAG_TERM . $cat );
			}
		}

		if ( ! function_exists( 'wc_get_product_terms' ) ) {
			return;
		}

		$tags = wc_get_product_terms( $post_id, 'product_tag', [ 'fields' => 'ids' ] );
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				do_action( 'litespeed_purge', self::CACHETAG_TERM . $tag );
			}
		}
	}

	/**
	 * When a product has a new review added, purge the recent reviews widget.
	 *
	 * @since 1.1.0
	 * @since 1.6.3 Removed static
	 * @access public
	 *
	 * @param mixed   $unused            Unused.
	 * @param integer $comment_approved  Whether the comment is approved or not.
	 * @param array   $commentdata       Information about the comment.
	 * @return void
	 */
	public function add_review( $unused, $comment_approved, $commentdata ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$post_id = $commentdata['comment_post_ID'];
		if ( 1 !== (int) $comment_approved || ! isset( $post_id ) || false === wc_get_product( $post_id ) ) {
			return;
		}

		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WC_Widget_Recent_Reviews'] ) ) {
			return;
		}

		$recent_reviews = $wp_widget_factory->widgets['WC_Widget_Recent_Reviews'];
		if ( ! is_null( $recent_reviews ) ) {
			do_action( 'litespeed_tag_add_widget', $recent_reviews->id );
		}
	}

	/**
	 * Append new options.
	 *
	 * @since 1.6.3 Removed static
	 * @since  3.0 new API
	 * @return void
	 */
	private function _option_append() {
		// Append option save value filter.
		do_action( 'litespeed_conf_multi_switch', self::O_UPDATE_INTERVAL, 3 ); // This need to be before conf_append.

		do_action( 'litespeed_conf_append', self::O_UPDATE_INTERVAL, false );
		do_action( 'litespeed_conf_append', self::O_CART_VARY, false );
	}

	/**
	 * Hooked to `litespeed_settings_tab` action.
	 * Adds the integration configuration options (currently, to determine purge rules).
	 *
	 * @since 1.6.3 Removed static
	 *
	 * @param string $setting_page Setting page slug.
	 * @return void
	 */
	public function settings_add_tab( $setting_page ) {
		if ( 'cache' !== $setting_page ) {
			return;
		}

		require 'woocommerce.tab.tpl.php';
	}

	/**
	 * Hook to show config content.
	 *
	 * @since  3.0
	 *
	 * @param string $setting_page Setting page slug.
	 * @return void
	 */
	public function settings_add_content( $setting_page ) {
		if ( 'cache' !== $setting_page ) {
			return;
		}

		require 'woocommerce.content.tpl.php';
	}

	/**
	 * Helper function to select the function(s) to use to get the product category ids.
	 *
	 * @since 1.0.10
	 * @since 1.6.3 Removed static
	 * @access private
	 *
	 * @param int $product_id The product id.
	 * @return array An array of category ids.
	 */
	private function get_cats( $product_id ) {
		if ( ! function_exists( 'WC' ) ) {
			return [];
		}

		$woocom = WC();
		if ( isset( $woocom ) && version_compare( $woocom->version, '2.5.0', '>=' ) && function_exists( 'wc_get_product_cat_ids' ) ) {
			return wc_get_product_cat_ids( $product_id );
		}
		$product_cats = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );
		foreach ( $product_cats as $product_cat ) {
			$product_cats = array_merge( $product_cats, get_ancestors( $product_cat, 'product_cat' ) );
		}

		return $product_cats;
	}

	/**
	 * 3rd party preload.
	 *
	 * @since  2.9.8.4
	 * @return void
	 */
	public static function preload() {
		/**
		 * Auto purge for WooCommerce Advanced Bulk Edit plugin,
		 * Bulk edit hook needs to be added to preload as it will die before detect.
		 */
		add_action( 'wp_ajax_wpmelon_adv_bulk_edit', __CLASS__ . '::bulk_edit_purge', 1 );
	}

	/**
	 * Auto purge for WooCommerce Advanced Bulk Edit plugin.
	 *
	 * @since  2.9.8.4
	 * @return void
	 */
	public static function bulk_edit_purge() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Third-party plugin action; cannot enforce here.
		if ( empty( $_POST['type'] ) || 'saveproducts' !== $_POST['type'] || empty( $_POST['data'] ) ) {
			return;
		}

		/*
		 * admin-ajax form-data structure
		 * array(
		 *      "type" => "saveproducts",
		 *      "data" => array(
		 *          "column1" => "464$###0$###2#^#463$###0$###4#^#462$###0$###6#^#",
		 *          "column2" => "464$###0$###2#^#463$###0$###4#^#462$###0$###6#^#"
		 *      )
		 *  )
		 */
		$stock_string_arr = [];

		// Ensure data is unslashed before processing.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Third-party plugin may not send nonce. Data format defined by third-party plugin.
		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : [];

		foreach ( (array) $data as $stock_value ) {
			$stock_string_arr = array_merge( $stock_string_arr, explode( '#^#', (string) $stock_value ) );
		}

		$lscwp_3rd_woocommerce = new self();

		if ( count( $stock_string_arr ) < 1 ) {
			return;
		}

		foreach ( $stock_string_arr as $edited_stock ) {
			$product_id = (int) strtok( (string) $edited_stock, '$' );
			$product    = wc_get_product( $product_id );

			if ( empty( $product ) ) {
				do_action( 'litespeed_debug', '3rd woo purge: ' . $product_id . ' not found.' );
				continue;
			}

			$lscwp_3rd_woocommerce->purge_product( $product );
		}
	}
}
