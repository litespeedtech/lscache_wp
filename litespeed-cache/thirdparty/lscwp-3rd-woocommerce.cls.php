<?php
/**
 * The Third Party integration with the WooCommerce plugin.
 *
 * @since         1.0.5
 * @package       LiteSpeed_Cache
 * @subpackage    LiteSpeed_Cache/thirdparty
 * @author        LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}
LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_WooCommerce') ;

class LiteSpeed_Cache_ThirdParty_WooCommerce
{
	const CACHETAG_SHOP = 'WC_S' ;
	const CACHETAG_TERM = 'WC_T.' ;
	const OPTION_UPDATE_INTERVAL = 'wc_update_interval' ;
	const OPTION_SHOP_FRONT_TTL = 'wc_shop_use_front_ttl' ;
	const OPT_PQS_CS = 0 ; // flush product on quantity + stock change, categories on stock change
	const OPT_PS_CS = 1 ; // flush product and categories on stock change
	const OPT_PS_CN = 2 ; // flush product on stock change, categories no flush
	const OPT_PQS_CQS = 3 ; // flush product and categories on quantity + stock change

	const ESI_PARAM_ARGS = 'wc_args' ;
	const ESI_PARAM_POSTID = 'wc_post_id' ;
	const ESI_PARAM_NAME = 'wc_name' ;
	const ESI_PARAM_PATH = 'wc_path' ;
	const ESI_PARAM_LOCATED = 'wc_located' ;

	/**
	 * Detects if WooCommerce is installed.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public static function detect()
	{
		if ( ! defined('WOOCOMMERCE_VERSION') ) {
			return ;
		}
		LiteSpeed_Cache_API::hook_control('LiteSpeed_Cache_ThirdParty_WooCommerce::set_control') ;
		LiteSpeed_Cache_API::hook_tag('LiteSpeed_Cache_ThirdParty_WooCommerce::set_tag') ;

		// Purging a product on stock change should only occur during product purchase. This function will add the purging callback when an order is complete.
		add_action('woocommerce_product_set_stock', 'LiteSpeed_Cache_ThirdParty_WooCommerce::purge_product') ;

		LiteSpeed_Cache_API::hook_get_options('LiteSpeed_Cache_ThirdParty_WooCommerce::get_config') ;
		add_action('comment_post', 'LiteSpeed_Cache_ThirdParty_WooCommerce::add_review', 10, 3) ;

		if ( LSWCP_ESI_SUPPORT && LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_ESI_ENABLE) ) {
			if ( !is_shop() ) {
				LiteSpeed_Cache_API::hook_tpl_not_esi('LiteSpeed_Cache_ThirdParty_WooCommerce::set_block_template') ;
				LiteSpeed_Cache_API::hook_tpl_esi('wc-add-to-cart-form', 'LiteSpeed_Cache_ThirdParty_WooCommerce::load_add_to_cart_form_block') ;
				LiteSpeed_Cache_API::hook_tpl_esi('storefront-cart-header', 'LiteSpeed_Cache_ThirdParty_WooCommerce::load_cart_header') ;
				LiteSpeed_Cache_API::hook_tpl_esi('widget', 'LiteSpeed_Cache_ThirdParty_WooCommerce::register_post_view') ;
			}

			if ( is_product() ) {
				LiteSpeed_Cache_API::hook_esi_param('widget', 'LiteSpeed_Cache_ThirdParty_WooCommerce::add_post_id') ;
			}

			LiteSpeed_Cache_API::hook_tpl_not_esi('LiteSpeed_Cache_ThirdParty_WooCommerce::set_swap_header_cart') ;
		}

		if ( is_admin() ) {
			LiteSpeed_Cache_API::hook_purge_post('LiteSpeed_Cache_ThirdParty_WooCommerce::backend_purge') ;
			add_action('delete_term_relationships', 'LiteSpeed_Cache_ThirdParty_WooCommerce::delete_rel', 10, 2) ;
			LiteSpeed_Cache_API::hook_setting_tab('LiteSpeed_Cache_ThirdParty_WooCommerce::add_config', 10, 3) ;
			LiteSpeed_Cache_API::hook_setting_save('LiteSpeed_Cache_ThirdParty_WooCommerce::save_config', 10, 2) ;
			LiteSpeed_Cache_API::hook_widget_default_options('LiteSpeed_Cache_ThirdParty_WooCommerce::wc_widget_default', 10, 2) ;
		}
	}

	/**
	 * Hooked to the litespeed_cache_is_not_esi_template action.
	 * If the request is not an esi request, I want to set my own hook
	 * in woocommerce_before_template_part to see if it's something I can ESI.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function set_block_template()
	{
		add_action('woocommerce_before_template_part', 'LiteSpeed_Cache_ThirdParty_WooCommerce::block_template', 999, 4) ;
	}

	/**
	 * Hooked to the litespeed_cache_is_not_esi_template action.
	 * If the request is not an esi request, I want to set my own hook
	 * in storefront_header to see if it's something I can ESI.
	 *
	 * Will remove storefront_header_cart in storefront_header.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function set_swap_header_cart()
	{
		$priority = has_action('storefront_header', 'storefront_header_cart') ;
		if ( $priority !== false ) {
			remove_action('storefront_header', 'storefront_header_cart', $priority) ;
			add_action('storefront_header', 'LiteSpeed_Cache_ThirdParty_WooCommerce::esi_cart_header', $priority) ;
		}
	}

	/**
	 * Hooked to the woocommerce_before_template_part action.
	 * Checks if the template contains 'add-to-cart'. If so, and if I
	 * want to ESI the request, block it and build my esi code block.
	 *
	 * The function parameters will be passed to the esi request.
	 *
	 * @since 1.1.0
	 * @access public
	 * @global type $post Needed for post id
	 * @param type $template_name
	 * @param type $template_path
	 * @param type $located
	 * @param type $args
	 */
	public static function block_template($template_name, $template_path, $located, $args)
	{
		if ( strpos($template_name, 'add-to-cart') === false ) {
			if ( strpos($template_name, 'related.php') !== false ) {
				remove_action('woocommerce_before_template_part', 'LiteSpeed_Cache_ThirdParty_WooCommerce::block_template', 999) ;
				add_filter('woocommerce_related_products_args', 'LiteSpeed_Cache_ThirdParty_WooCommerce::add_related_tags') ;
				add_action('woocommerce_after_template_part', 'LiteSpeed_Cache_ThirdParty_WooCommerce::end_template', 999) ;
			}
			return ;
		}
		global $post ;
		$params = array(
			self::ESI_PARAM_ARGS => $args,
			self::ESI_PARAM_NAME => $template_name,
			self::ESI_PARAM_POSTID => $post->ID,
			self::ESI_PARAM_PATH => $template_path,
			self::ESI_PARAM_LOCATED => $located
		) ;
		add_action('woocommerce_after_add_to_cart_form', 'LiteSpeed_Cache_ThirdParty_WooCommerce::end_form') ;
		add_action('woocommerce_after_template_part', 'LiteSpeed_Cache_ThirdParty_WooCommerce::end_form', 999) ;
		LiteSpeed_Cache_API::esi_url('wc-add-to-cart-form', 'WC_CART_FORM', $params) ;
		ob_start() ;
	}

	/**
	 * Hooked to the woocommerce_after_add_to_cart_form action.
	 * If this is hit first, clean the buffer and remove this function and
	 * end_template.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function end_form($template_name = '')
	{
		if ( ! empty($template_name) && strpos($template_name, 'add-to-cart') === false ) {
			return ;
		}
		ob_clean() ;
		remove_action('woocommerce_after_add_to_cart_form', 'LiteSpeed_Cache_ThirdParty_WooCommerce::end_form') ;
		remove_action('woocommerce_after_template_part', 'LiteSpeed_Cache_ThirdParty_WooCommerce::end_form', 999) ;
	}

	/**
	 * If related products are loaded, need to add the extra product ids.
	 *
	 * The page will be purged if any of the products are changed.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $args The arguments used to build the related products section.
	 * @return array The unchanged arguments.
	 */
	public static function add_related_tags($args)
	{
		if ( empty($args) || ! isset($args['post__in']) ) {
			return $args ;
		}
		$related_posts = $args['post__in'] ;
		foreach ( $related_posts as $related ) {
			LiteSpeed_Cache_API::tag_add(LiteSpeed_Cache_API::TYPE_POST . $related) ;
		}
		return $args ;
	}

	/**
	 * Hooked to the woocommerce_after_template_part action.
	 * If the template contains 'add-to-cart', clean the buffer.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param type $template_name
	 */
	public static function end_template($template_name)
	{
		if ( strpos($template_name, 'related.php') !== false ) {
			remove_action('woocommerce_after_template_part', 'LiteSpeed_Cache_ThirdParty_WooCommerce::end_template', 999) ;
			self::set_block_template() ;
		}
	}

	/**
	 * Hooked to the storefront_header header.
	 * If I want to ESI the request, block it and build my esi code block.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function esi_cart_header()
	{
		LiteSpeed_Cache_API::esi_url('storefront-cart-header', 'STOREFRONT_CART_HEADER') ;
	}

	/**
	 * Hooked to the litespeed_cache_load_esi_block-storefront-cart-header action.
	 * Generates the cart header for esi display.
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public static function load_cart_header()
	{
		storefront_header_cart() ;
	}

	/**
	 * Hooked to the litespeed_cache_load_esi_block-wc-add-to-cart-form action.
	 * Parses the esi input parameters and generates the add to cart form
	 * for esi display.
	 *
	 * @since 1.1.0
	 * @access public
	 * @global type $post
	 * @global type $wp_query
	 * @param type $params
	 */
	public static function load_add_to_cart_form_block($params)
	{
		global $post, $wp_query ;
		$post = get_post($params[self::ESI_PARAM_POSTID]) ;
		$wp_query->setup_postdata($post) ;
		wc_get_template($params[self::ESI_PARAM_NAME], $params[self::ESI_PARAM_ARGS], $params[self::ESI_PARAM_PATH]) ;
	}

	/**
	 * Update woocommerce when someone visits a product and has the
	 * recently viewed products widget.
	 *
	 * Currently, this widget should not be cached.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $params Widget parameter array
	 */
	public static function register_post_view($params)
	{
		if ( $params[LiteSpeed_Cache_API::PARAM_NAME] !== 'WC_Widget_Recently_Viewed' ) {
			return ;
		}
		if ( ! isset($params[self::ESI_PARAM_POSTID]) ) {
			return ;
		}
		$id = $params[self::ESI_PARAM_POSTID] ;
		$esi_post = get_post($id) ;
		$product = wc_get_product($esi_post) ;

		if ( empty($product) ) {
			return ;
		}

		global $post ;
		$post = $esi_post ;
		wc_track_product_view() ;
	}

	/**
	 * Adds the post id to the widget ESI parameters for the Recently Viewed widget.
	 *
	 * This is needed in the esi request to update the cookie properly.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $params The current ESI parameters.
	 * @return array The updated esi parameters.
	 */
	public static function add_post_id($params)
	{
		if ( ! isset($params) || ! isset($params[LiteSpeed_Cache_API::PARAM_NAME]) || $params[LiteSpeed_Cache_API::PARAM_NAME] !== 'WC_Widget_Recently_Viewed' ) {
			return $params ;
		}
		$params[self::ESI_PARAM_POSTID] = get_the_ID() ;
		return $params ;
	}

	/**
	 * Hooked to the litespeed_cache_widget_default_options filter.
	 *
	 * The recently viewed widget must be esi to function properly.
	 * This function will set it to enable and no cache by default.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $options The current default widget options.
	 * @param type $widget The current widget to configure.
	 * @return array The updated default widget options.
	 */
	public static function wc_widget_default($options, $widget)
	{
		if ( ! is_array($options) ) {
			return $options ;
		}
		$widget_name = get_class($widget) ;
		if ( $widget_name === 'WC_Widget_Recently_Viewed' ) {
			$options[LiteSpeed_Cache_API::WIDGET_OPID_ESIENABLE] = true ;
			$options[LiteSpeed_Cache_API::WIDGET_OPID_TTL] = 0 ;
		}
		elseif ( $widget_name === 'WC_Widget_Recent_Reviews' ) {
			$options[LiteSpeed_Cache_API::WIDGET_OPID_ESIENABLE] = true ;
			$options[LiteSpeed_Cache_API::WIDGET_OPID_TTL] = 86400 ;
		}
		return $options ;
	}

	/**
	 * Check and set shop front page ttl
	 *
	 * @access private
	 * @since 1.1.3
	 */
	private static function set_ttl()
	{
		if ( is_shop() ) {
			if ( LiteSpeed_Cache_API::config( self::OPTION_SHOP_FRONT_TTL ) ) {
				LiteSpeed_Cache_API::set_use_frontpage_ttl() ;
			}
		}
	}

	/**
	 * Set WooCommerce cache tags based on page type.
	 *
	 * @access public
	 * @since 1.0.9
	 */
	public static function set_tag()
	{
		$id = get_the_ID() ;
		if ( $id === false ) {
			return ;
		}

		// Check if product has a cache ttl limit or not
		$sale_from = get_post_meta( $id, '_sale_price_dates_from', true ) ;
		$sale_to = get_post_meta( $id, '_sale_price_dates_to', true ) ;
		$now = current_time( 'timestamp' ) ;
		$ttl = false ;
		if ( $sale_from && $now < $sale_from ) {
			$ttl = $sale_from - $now ;
		}
		elseif ( $sale_to && $now < $sale_to ) {
			$ttl = $sale_to - $now ;
		}
		if ( $ttl && $ttl < LiteSpeed_Cache_API::get_ttl() ) {
			LiteSpeed_Cache_API::debug( "WooCommerce set scheduled TTL to $ttl" ) ;
			LiteSpeed_Cache_API::set_ttl( $ttl ) ;
		}

		if ( is_shop() ) {
			LiteSpeed_Cache_API::tag_add(self::CACHETAG_SHOP) ;
		}
		if ( ! is_product_taxonomy() ) {
			return ;
		}
		if ( isset($GLOBALS['product_cat']) ) {
			$term = get_term_by('slug', $GLOBALS['product_cat'], 'product_cat') ;
		}
		elseif ( isset($GLOBALS['product_tag']) ) {
			$term = get_term_by('slug', $GLOBALS['product_tag'], 'product_tag') ;
		}
		else {
			$term = false ;
		}

		if ( $term === false ) {
			return ;
		}
		while ( isset($term) ) {
			LiteSpeed_Cache_API::tag_add(self::CACHETAG_TERM . $term->term_id) ;
			if ( $term->parent == 0 ) {
				break ;
			}
			$term = get_term($term->parent) ;
		}
	}

	/**
	 * Check if the page is cacheable according to WooCommerce.
	 *
	 * @since 1.0.5
	 * @access public
     * @param string $esi_id 		The ESI block id if a request is an ESI request.
	 * @return boolean           	True if cacheable, false if not.
	 */
	public static function set_control($esi_id)
	{
		if ( LiteSpeed_Cache_API::not_cacheable() ) {
			return ;
		}

		$woocom = WC() ;
		if ( ! isset($woocom) ) {
			return ;
		}
		self::set_ttl() ;

		// For later versions, DONOTCACHEPAGE should be set.
		// No need to check uri/qs.
		if ( version_compare($woocom->version, '1.4.2', '>=') ) {
			if ( defined('DONOTCACHEPAGE') && DONOTCACHEPAGE ) {
				LiteSpeed_Cache_API::debug('3rd party woocommerce not cache by constant') ;
				LiteSpeed_Cache_API::set_nocache() ;
				return ;
			}
			elseif ( version_compare($woocom->version, '2.1.0', '>=') ) {
				$err = false ;
				if ( is_null($woocom->cart) ) {
					$err = 'null cart' ;
				}
				elseif ( $woocom->cart->get_cart_contents_count() !== 0 ) {//$esi_id === 'storefront-cart-header' &&
					$err = 'cart is not empty' ;
				}
				elseif ( wc_notice_count() > 0 ) {
					$err = 'has wc notice' ;
				}

				if ( $err ) {
					LiteSpeed_Cache_API::debug('3rd party woocommerce not cache due to ' . $err) ;
					LiteSpeed_Cache_API::set_nocache() ;
					return ;
				}
			}
			return ;
		}

		$uri = esc_url($_SERVER["REQUEST_URI"]) ;
		$uri_len = strlen($uri) ;
		if ( $uri_len < 5 ) {
			return ;
		}

		if ( in_array($uri, array('cart/', 'checkout/', 'my-account/', 'addons/', 'logout/', 'lost-password/', 'product/')) ) {
			LiteSpeed_Cache_API::set_nocache() ;
			return ;
		}

		$qs = sanitize_text_field($_SERVER["QUERY_STRING"]) ;
		$qs_len = strlen($qs) ;
		if ( ! empty($qs) && $qs_len >= 12 && strncmp($qs, 'add-to-cart=', 12) == 0 ) {
			LiteSpeed_Cache_API::set_nocache() ;
			return ;
		}
	}

	/**
	 * Purge a product page and related pages (based on settings) on checkout.
	 *
	 * @access public
	 * @since 1.0.9
	 * @param WC_Product $product
	 */
	public static function purge_product($product)
	{
		$config = LiteSpeed_Cache_API::config(self::OPTION_UPDATE_INTERVAL) ;
		if ( is_null($config) ) {
			$config = self::OPT_PQS_CS ;
		}

		if ( $config === self::OPT_PQS_CQS ) {
			self::backend_purge($product->get_id()) ;
		}
		elseif ( $config !== self::OPT_PQS_CS && $product->is_in_stock() ) {
			return ;
		}
		elseif ( $config !== self::OPT_PS_CN && ! $product->is_in_stock() ) {
			self::backend_purge($product->get_id()) ;
		}

		LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_POST . $product->get_id()) ;
	}

	/**
	 * Delete object-term relationship. If the post is a product and
	 * the term ids array is not empty, will add purge tags to the deleted
	 * terms.
	 *
	 * @access public
	 * @since 1.0.9
	 * @param int $post_id Object ID.
	 * @param array $term_ids An array of term taxonomy IDs.
	 */
	public static function delete_rel($post_id, $term_ids)
	{
		if ( empty($term_ids) || wc_get_product($post_id) === false ) {
			return ;
		}
		foreach ( $term_ids as $term_id ) {
			LiteSpeed_Cache_API::purge(self::CACHETAG_TERM . $term_id) ;
		}
	}

	/**
	 * Purge a product's categories and tags pages in case they are affected.
	 *
	 * @access public
	 * @since 1.0.9
	 * @param int $post_id Post id that is about to be purged
	 */
	public static function backend_purge($post_id)
	{
		if ( ! isset($post_id) || wc_get_product($post_id) === false ) {
			return ;
		}

		$cats = self::get_cats($post_id) ;
		if ( ! empty($cats) ) {
			foreach ( $cats as $cat ) {
				LiteSpeed_Cache_API::purge(self::CACHETAG_TERM . $cat) ;
			}
		}

		$tags = wc_get_product_terms($post_id, 'product_tag', array('fields' => 'ids')) ;
		if ( ! empty($tags) ) {
			foreach ( $tags as $tag ) {
				LiteSpeed_Cache_API::purge(self::CACHETAG_TERM . $tag) ;
			}
		}
	}

	/**
	 * When a product has a new review added, purge the recent reviews widget.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param $unused
	 * @param integer $comment_approved Whether the comment is approved or not.
	 * @param array $commentdata Information about the comment.
	 */
	public static function add_review($unused, $comment_approved, $commentdata)
	{
		$post_id = $commentdata['comment_post_ID'] ;
		if ( $comment_approved !== 1 || ! isset($post_id) || wc_get_product($post_id) === false ) {
			return ;
		}
		global $wp_widget_factory ;
		$recent_reviews = $wp_widget_factory->widgets['WC_Widget_Recent_Reviews'] ;
		if ( ! is_null($recent_reviews) ) {
			LiteSpeed_Cache_API::tag_add(LiteSpeed_Cache_API::TYPE_WIDGET . $recent_reviews->id) ;
		}
	}

	/**
	 * Hooked to the litespeed_cache_get_options filter.
	 * This will return the option names needed as well as the default options.
	 *
	 * @param array $configs
	 * @return array
	 */
	public static function get_config($configs)
	{
		if ( ! is_array($configs) ) {
			return $configs ;
		}
		$configs[self::OPTION_UPDATE_INTERVAL] = self::OPT_PQS_CS ;
		$configs[self::OPTION_SHOP_FRONT_TTL] = true ;

		return $configs ;
	}

	/**
	 * Hooked to the litespeed_cache_add_config_tab filter.
	 * Adds the integration configuration options (currently, to determine
	 * purge rules)
	 *
	 * @param array $tabs Third party tabs added.
	 * @param array $options Current options used.
	 * @param string $option_group The option group to surround the option id.
	 * @return mixed False on failure, updated tabs otherwise.
	 */
	public static function add_config($tabs, $options, $option_group)
	{
		$_title = __('WooCommerce', 'litespeed-cache') ;
		$_slug = 'woocom' ;
		$seloptions = array(
			__('Purge product on changes to the quantity or stock status.', 'litespeed-cache')
				. ' ' . __('Purge categories only when stock status changes.', 'litespeed-cache'),
			__('Purge product and categories only when the stock status changes.', 'litespeed-cache'),
			__('Purge product only when the stock status changes.', 'litespeed-cache')
				. ' ' . __('Do not purge categories on changes to the quantity or stock status.', 'litespeed-cache'),
			__('Always purge both product and categories on changes to the quantity or stock status.', 'litespeed-cache'),
		) ;
		$update_desc =
			__('Determines how changes in product quantity and product stock status affect product pages and their associated category pages.', 'litespeed-cache') ;
		$ttl_desc = __('Checking this option will force the shop page to use the front page TTL setting.', 'litespeed-cache')
					. ' ' . sprintf(__('For example, if the homepage for the site is located at %1$s, the shop page may be located at %2$s.', 'litespeed-cache'),
						'https://www.example.com', 'https://www.example.com/shop') ;

		if ($tabs === false) {
			return false ;
		}

		$selected_value = self::OPT_PQS_CS ;
		if (isset($options)) {
			if (isset($options[self::OPTION_UPDATE_INTERVAL])) {
				$selected_value = $options[self::OPTION_UPDATE_INTERVAL] ;
			}
		}

		$update_intval_html = '' ;
		$id = self::OPTION_UPDATE_INTERVAL ;
		foreach ($seloptions as $val => $title) {
			$checked = $selected_value == $val ? ' checked="checked" ' : '';
			$update_intval_html .= "<div class='litespeed-radio cross litespeed-top10'>
										<input type='radio' name='{$option_group}[$id]' id='conf_{$id}_$val' value='$val' $checked />
										<label for='conf_{$id}_$val'>$title</label>
									</div>" ;
		}

		$content = "<h3 class='litespeed-title'>{$_title}</h3>
					<table class='form-table'><tbody>
						<tr>
							<th>" . __('Product Update Interval', 'litespeed-cache') . "</th>
							<td>
								$update_intval_html
								<div class='litespeed-desc'>$update_desc</div>
							</td>
						</tr>
						<tr>
							<th>" . __('Use Front Page TTL for the Shop Page', 'litespeed-cache') . "</th>
							<td>
								" . LiteSpeed_Cache_API::build_switch(self::OPTION_SHOP_FRONT_TTL, false, true) . "
								<div class='litespeed-desc'>$ttl_desc</div>
							</td>
						</tr>
					</tbody></table>
					<div class='litespeed-callout litespeed-callout-warning'>
						<h4>" . __('Note:', 'litespeed-cache') . "</h4>
						<i>
							" . __('After verifying that the cache works in general, please test the cart.', 'litespeed-cache') . "
							" . sprintf(__('To test the cart, visit the %s.', 'litespeed-cache'), '<a href=' . get_admin_url() . 'admin.php?page=lscache-info>FAQ</a>') . "
						</i>
					</div>

					" ;

		$tab = array(
			'title'   => $_title,
			'slug'    => $_slug,
			'content' => $content,
		) ;

		$tabs[] = $tab ;

		return $tabs ;
	}

	/**
	 * Hooked to the litespeed_cache_save_options filter.
	 * Parses the input for this integration's options and updates
	 * the options array accordingly.
	 *
	 * @param array $options The saved options array.
	 * @param array $input The input options array.
	 * @return mixed false on failure, updated $options otherwise.
	 */
	public static function save_config($options, $input)
	{
		if ( ! isset($options) ) {
			return $options ;
		}
		if ( isset($input[self::OPTION_UPDATE_INTERVAL]) ) {
			$update_val_in = $input[self::OPTION_UPDATE_INTERVAL] ;
			switch ($update_val_in) {
				case self::OPT_PQS_CS:
				case self::OPT_PS_CS:
				case self::OPT_PS_CN:
				case self::OPT_PQS_CQS:
					$options[self::OPTION_UPDATE_INTERVAL] = intval($update_val_in) ;
					break ;
				default:
					// add error message?
					break ;
			}
		}

		$options[ self::OPTION_SHOP_FRONT_TTL ] = LiteSpeed_Cache_API::parse_onoff( $input, self::OPTION_SHOP_FRONT_TTL ) ;

		return $options ;
	}

	/**
	 * Helper function to select the function(s) to use to get the product
	 * category ids.
	 *
	 * @since 1.0.10
	 * @access private
	 * @param int $product_id The product id
	 * @return array An array of category ids.
	 */
	private static function get_cats($product_id)
	{
		$woocom = WC() ;
		if ( isset($woocom) && version_compare($woocom->version, '2.5.0', '>=') ) {
			return wc_get_product_cat_ids($product_id) ;
		}
		$product_cats = wp_get_post_terms($product_id, 'product_cat', array("fields" => "ids")) ;
		foreach ( $product_cats as $product_cat ) {
			$product_cats = array_merge($product_cats, get_ancestors($product_cat, 'product_cat')) ;
		}

		return $product_cats ;
	}

}

