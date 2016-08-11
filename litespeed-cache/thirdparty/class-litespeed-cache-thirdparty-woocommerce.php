<?php

/**
 * The Third Party integration with the WooCommerce plugin.
 *
 * @since		1.0.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_ThirdParty_WooCommerce
{

	const CACHETAG_SHOP = 'WC_S';
	const CACHETAG_TERM = 'WC_T.';

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

			if (is_admin()) {
				add_action('litespeed_cache_on_purge_post',
					'LiteSpeed_Cache_ThirdParty_WooCommerce::backend_purge');
				add_action('delete_term_relationships',
					'LiteSpeed_Cache_ThirdParty_WooCommerce::delete_rel', 10, 2);
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
		// TODO: add configs.
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
		$cats = wc_get_product_cat_ids($post_id);
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

}

add_action('litespeed_cache_detect_thirdparty', 'LiteSpeed_Cache_ThirdParty_WooCommerce::detect');
