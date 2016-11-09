<?php

/**
 * The Third Party integration with the WooCommerce plugin.
 *
 * @since		1.0.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
if (!defined('ABSPATH')) {
    die();
}

class LiteSpeed_Cache_ThirdParty_WooCommerce
{

	const CACHETAG_SHOP = 'WC_S';
	const CACHETAG_TERM = 'WC_T.';
	const OPTION_UPDATE_INTERVAL = 'wc_update_interval';
	const OPTION_SHOP_FRONT_TTL = 'wc_shop_use_front_ttl';
	const OPT_PQS_CS = 0; // flush product on quantity + stock change, categories on stock change
	CONST OPT_PS_CS = 1; // flush product and categories on stock change
	CONST OPT_PS_CN = 2; // flush product on stock change, categories no flush
	CONST OPT_PQS_CQS = 3; // flush product and categories on quantity + stock change

	/**
	 * Detects if WooCommerce is installed.
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public static function detect()
	{
		if (defined('WOOCOMMERCE_VERSION')) {
			add_filter('litespeed_cache_is_cacheable', 'LiteSpeed_Cache_ThirdParty_WooCommerce::is_cacheable');

			add_action('woocommerce_after_checkout_validation',
				'LiteSpeed_Cache_ThirdParty_WooCommerce::add_purge');
			add_filter('litespeed_cache_get_options',
				'LiteSpeed_Cache_ThirdParty_WooCommerce::get_config');

			if (is_admin()) {
				add_action('litespeed_cache_on_purge_post',
					'LiteSpeed_Cache_ThirdParty_WooCommerce::backend_purge');
				add_action('delete_term_relationships',
					'LiteSpeed_Cache_ThirdParty_WooCommerce::delete_rel', 10, 2);
				add_filter('litespeed_cache_add_config_tab',
					'LiteSpeed_Cache_ThirdParty_WooCommerce::add_config', 10, 3);
				add_filter('litespeed_cache_save_options',
					'LiteSpeed_Cache_ThirdParty_WooCommerce::save_config', 10, 2);
			}
		}
	}

	/**
	 * Set WooCommerce cache tags based on page type.
	 *
	 * @access private
	 * @since 1.0.9
	 */
	private static function set_cache_tags()
	{
		$id = get_the_ID();
		if ($id === false) {
			return;
		}
		if (is_shop()) {
			LiteSpeed_Cache_Tags::add_cache_tag(self::CACHETAG_SHOP);
			if (LiteSpeed_Cache::config(self::OPTION_SHOP_FRONT_TTL) !== false) {
				LiteSpeed_Cache_Tags::set_use_frontpage_ttl();
			}
		}
		if (!is_product_taxonomy()) {
			return;
		}
		if (isset($GLOBALS['product_cat'])) {
			$term = get_term_by('slug', $GLOBALS['product_cat'], 'product_cat');
		}
		elseif (isset($GLOBALS['product_tag'])) {
			$term = get_term_by('slug', $GLOBALS['product_tag'], 'product_tag');
		}
		else {
			$term = false;
		}

		if ($term === false) {
			return;
		}
		while(isset($term)) {
			LiteSpeed_Cache_Tags::add_cache_tag(
				self::CACHETAG_TERM . $term->term_id);
			if ($term->parent == 0) {
				break;
			}
			$term = get_term($term->parent);
		}
	}

	/**
	 * Check if the page is cacheable according to WooCommerce.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param boolean $cacheable True if previous filter determined the page is cacheable.
	 * @return boolean True if cacheable, false if not.
	 */
	public static function is_cacheable($cacheable)
	{
		if (!$cacheable) {
			return false;
		}
		$woocom = WC();
		if (!isset($woocom)) {
			return true;
		}

		// For later versions, DONOTCACHEPAGE should be set.
		// No need to check uri/qs.
		if (version_compare($woocom->version, '1.4.2', '>=')) {
			if ((defined('DONOTCACHEPAGE')) && (DONOTCACHEPAGE)) {
				return false;
			}
			elseif ((version_compare($woocom->version, '2.1.0', '>='))
				&& (($woocom->cart->get_cart_contents_count() !== 0)
				|| (wc_notice_count() > 0))) {
				return false;
			}
			self::set_cache_tags();
			return true;
		}
		$uri = esc_url($_SERVER["REQUEST_URI"]);
		$uri_len = strlen( $uri ) ;

		if ($uri_len < 5) {
			self::set_cache_tags();
			return true;
		}
		$sub = substr($uri, 2);
		$sub_len = $uri_len - 2;
		switch($uri[1]) {
		case 'c':
			if ((($sub_len == 4) && (strncmp($sub, 'art/', 4) == 0))
				|| (($sub_len == 8) && (strncmp($sub, 'heckout/', 8) == 0))) {
				return false;
			}
			break;
		case 'm':
			if (strncmp($sub, 'y-account/', 10) == 0) {
				return false;
			}
			break;
		case 'a':
			if (($sub_len == 6) && (strncmp($sub, 'ddons/', 6) == 0)) {
				return false;
			}
			break;
		case 'l':
			if ((($sub_len == 6) && (strncmp($sub, 'ogout/', 6) == 0))
				|| (($sub_len == 13) && (strncmp($sub, 'ost-password/', 13) == 0))) {
				return false;
			}
			break;
		case 'p':
			if (strncmp($sub, 'roduct/', 7) == 0) {
				return false;
			}
			break;
		}

		$qs = sanitize_text_field($_SERVER["QUERY_STRING"]);
		$qs_len = strlen($qs);
		if ( !empty($qs) && ($qs_len >= 12)
				&& (strncmp($qs, 'add-to-cart=', 12) == 0)) {
			return false;
		}

		self::set_cache_tags();
		return true;
	}

	/**
	 * Purging a product on stock change should only occur during
	 * product purchase. This function will add the purging callback
	 * when an order is complete.
	 *
	 * @access public
	 * @since 1.0.9
	 */
	public static function add_purge()
	{
		add_action('woocommerce_product_set_stock',
			'LiteSpeed_Cache_ThirdParty_WooCommerce::purge_product');
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
		$config = LiteSpeed_Cache::config(self::OPTION_UPDATE_INTERVAL);
		if (is_null($config)) {
			$config = self::OPT_PQS_CS;
		}

		if ($config === self::OPT_PQS_CQS) {
			self::backend_purge($product->get_id());
		}
		elseif (($config !== self::OPT_PQS_CS) && ($product->is_in_stock())) {
			return;
		}
		elseif (($config !== self::OPT_PS_CN) && (!$product->is_in_stock())) {
			self::backend_purge($product->get_id());
		}

		LiteSpeed_Cache_Tags::add_purge_tag(
			LiteSpeed_Cache_Tags::TYPE_POST . $product->get_id());
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
		if ((empty($term_ids)) || (wc_get_product($post_id) === false)) {
			return;
		}
		foreach($term_ids as $term_id) {
			LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_TERM . $term_id);
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
		if ((!isset($post_id)) || (wc_get_product($post_id) === false)) {
			return;
		}

		$cats = self::get_cats($post_id);
		if (!empty($cats)) {
			foreach ($cats as $cat) {
				LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_TERM . $cat);
			}
		}

		$tags = wc_get_product_terms($post_id, 'product_tag',
			array('fields'=>'ids'));
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_TERM . $tag);
			}
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
		if (!is_array($configs)) {
			return $configs;
		}
		$configs[self::OPTION_UPDATE_INTERVAL] = self::OPT_PQS_CS;
		$configs[self::OPTION_SHOP_FRONT_TTL] = self::OPTION_SHOP_FRONT_TTL;
		return $configs;
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
		$title = __('WooCommerce Configurations', 'litespeed-cache');
		$slug = 'woocom';
		$selected_value = self::OPT_PQS_CS;
		$seloptions = array(
			__('Purge product on changes to the quantity or stock status.', 'litespeed-cache')
			. __('Purge categories only when stock status changes.', 'litespeed-cache'),
			__('Purge product and categories only when the stock status changes.', 'litespeed-cache'),
			__('Purge product only when the stock status changes.', 'litespeed-cache')
			. __('Do not purge categories on changes to the quantity or stock status.', 'litespeed-cache'),
			__('Always purge both product and categories on changes to the quantity or stock status.', 'litespeed-cache'),
		);
		$update_desc =
			__('Determines how changes in product quantity and product stock status affect product pages and their associated category pages.', 'litespeed-cache');
		$ttl_desc = __('Checking this option will force the shop page to use the front page TTL setting.', 'litespeed-cache')
		. __('For example, if the homepage for your site is located at https://www.example.com, your shop page may be located at https://www.example.com/shop.', 'litespeed-cache');

		if ($tabs === false) {
			return $tabs;
		}

		if (isset($options)) {
			if (isset($options[self::OPTION_UPDATE_INTERVAL])) {
				$selected_value = $options[self::OPTION_UPDATE_INTERVAL];
			}
			if (isset($options[self::OPTION_SHOP_FRONT_TTL])) {
				$checked_value = $options[self::OPTION_SHOP_FRONT_TTL];
			}
		}

		$content = '<hr/><h3 class="title">'
			. $title . "</h3>\n"
			. '<table class="form-table">' . "\n"
			. '<tr><th scope="row">'
			. __('Product Update Interval', 'litespeed-cache') . '</th><td>';

		$content .= '<select name="' . $option_group . '['
			. self::OPTION_UPDATE_INTERVAL . ']" id="'
			. self::OPTION_UPDATE_INTERVAL . '" style="width:100%;max-width:90%;">';
		foreach ( $seloptions as $val => $label ) {
			$content .= '<option value="' . $val . '"' ;
			if ( $selected_value == $val ) {
				$content .= ' selected="selected"' ;
			}
			$content .= '>' . $label . '</option>' ;
		}
		$content .= '</select><p class="description">' . $update_desc
			. "</p></td></tr>\n";
		$content .= '<tr><th scope="row">'
			. __('Use Front Page TTL for the Shop Page', 'litespeed-cache') . '</th><td>';
		$content .= '<input name="' . $option_group . '['
			. self::OPTION_SHOP_FRONT_TTL . ']" type="checkbox" id="'
			. self::OPTION_SHOP_FRONT_TTL . '" value="'
			. self::OPTION_SHOP_FRONT_TTL . '"' ;
		if ( ($checked_value === self::OPTION_SHOP_FRONT_TTL)) {
			$content .= ' checked="checked" ' ;
		}
		$content .= '/><p class="description">' . $ttl_desc;
		$content .= "</p></td></tr>\n";
		$content .= "</table>\n";

		$content .= '<h3>' . __('NOTE:', 'litespeed-cache') . '</h3><p>'
			. __('After verifying that the cache works in general, please test the cart.', 'litespeed-cache')
			. sprintf(__('To test the cart, visit the %s.', 'litespeed-cache'),
				'<a href=' . get_admin_url() . 'admin.php?page=lscache-faqs>FAQ</a>')
			. '</p>';
		$content .= "\n";

		$tab = array(
			'title' => $title,
			'slug' => $slug,
			'content' => $content
		);

		$tabs[] = $tab;

		return $tabs;
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
		if (!isset($options)) {
			return $options;
		}
		if (isset($input[self::OPTION_UPDATE_INTERVAL])) {
			$update_val_in = $input[self::OPTION_UPDATE_INTERVAL];
			switch ($update_val_in) {
				case self::OPT_PQS_CS:
				case self::OPT_PS_CS:
				case self::OPT_PS_CN:
				case self::OPT_PQS_CQS:
					$options[self::OPTION_UPDATE_INTERVAL] = intval($update_val_in);
					break;
				default:
					// add error message?
					break;
			}
		}

		if ((isset($input[self::OPTION_SHOP_FRONT_TTL]))
			&& ($input[self::OPTION_SHOP_FRONT_TTL]
				=== self::OPTION_SHOP_FRONT_TTL)) {
			$options[self::OPTION_SHOP_FRONT_TTL] =
				self::OPTION_SHOP_FRONT_TTL;
		}
		else {
			$options[self::OPTION_SHOP_FRONT_TTL] = false;
		}

		return $options;
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
		$woocom = WC();
		if ((isset($woocom)) &&
			(version_compare($woocom->version, '2.5.0', '>='))) {
			return wc_get_product_cat_ids($product_id);
		}
		$product_cats = wp_get_post_terms( $product_id, 'product_cat',
			array( "fields" => "ids" ) );
		foreach ( $product_cats as $product_cat ) {
			$product_cats = array_merge( $product_cats, get_ancestors( $product_cat, 'product_cat' ) );
		}

		return $product_cats;
	}

}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_WooCommerce::detect');
