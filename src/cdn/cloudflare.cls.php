<?php
/**
 * The cloudflare CDN class.
 *
 * @since       2.1
 * @package     LiteSpeed
 * @subpackage  LiteSpeed/src/cdn
 * @author      LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed\CDN;

use LiteSpeed\Base;
use LiteSpeed\Debug2;
use LiteSpeed\Router;
use LiteSpeed\Admin;
use LiteSpeed\Admin_Display;

defined('WPINC') || exit();

/**
 * Class Cloudflare
 *
 * @since 2.1
 */
class Cloudflare extends Base {

	const TYPE_PURGE_ALL       = 'purge_all';
	const TYPE_GET_DEVMODE     = 'get_devmode';
	const TYPE_SET_DEVMODE_ON  = 'set_devmode_on';
	const TYPE_SET_DEVMODE_OFF = 'set_devmode_off';

	const ITEM_STATUS = 'status';

	/**
	 * Update zone&name based on latest settings
	 *
	 * @since  3.0
	 * @access public
	 */
	public function try_refresh_zone() {
		if (!$this->conf(self::O_CDN_CLOUDFLARE)) {
			return;
		}

		$zone = $this->fetch_zone();
		if ($zone) {
			$this->cls('Conf')->update(self::O_CDN_CLOUDFLARE_NAME, $zone['name']);

			$this->cls('Conf')->update(self::O_CDN_CLOUDFLARE_ZONE, $zone['id']);

			Debug2::debug("[Cloudflare] Get zone successfully \t\t[ID] " . $zone['id']);
		} else {
			$this->cls('Conf')->update(self::O_CDN_CLOUDFLARE_ZONE, '');
			Debug2::debug('[Cloudflare] ❌ Get zone failed, clean zone');
		}
	}

	/**
	 * Get Cloudflare development mode
	 *
	 * @since  1.7.2
	 * @access private
	 * @param bool $show_msg Whether to show success/error message.
	 */
	private function get_devmode( $show_msg = true ) {
		Debug2::debug('[Cloudflare] get_devmode');

		$zone = $this->zone();
		if (!$zone) {
			return;
		}

		$url = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/settings/development_mode';
		$res = $this->cloudflare_call($url, 'GET', false, $show_msg);

		if (!$res) {
			return;
		}
		Debug2::debug('[Cloudflare] get_devmode result ', $res);

		// Make sure is array: #992174
		$curr_status = self::get_option(self::ITEM_STATUS, array());
		if ( ! is_array( $curr_status ) ) {
			$curr_status = array();
		}
		$curr_status['devmode']         = $res['value'];
		$curr_status['devmode_expired'] = (int) $res['time_remaining'] + time();

		// update status
		self::update_option(self::ITEM_STATUS, $curr_status);
	}

	/**
	 * Set Cloudflare development mode
	 *
	 * @since  1.7.2
	 * @access private
	 * @param string $type The type of development mode to set (on/off).
	 */
	private function set_devmode( $type ) {
		Debug2::debug('[Cloudflare] set_devmode');

		$zone = $this->zone();
		if (!$zone) {
			return;
		}

		$url     = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/settings/development_mode';
		$new_val = self::TYPE_SET_DEVMODE_ON === $type ? 'on' : 'off';
		$data    = array( 'value' => $new_val );
		$res     = $this->cloudflare_call($url, 'PATCH', $data);

		if (!$res) {
			return;
		}

		$res = $this->get_devmode(false);

		if ($res) {
			$msg = sprintf(__('Notified Cloudflare to set development mode to %s successfully.', 'litespeed-cache'), strtoupper($new_val));
			Admin_Display::success($msg);
		}
	}

	/**
	 * Shortcut to purge Cloudflare
	 *
	 * @since  7.1
	 * @access public
	 * @param string|bool $reason The reason for purging, or false if none.
	 */
	public static function purge_all( $reason = false ) {
		if ($reason) {
			Debug2::debug('[Cloudflare] purge call because: ' . $reason);
		}
		self::cls()->purge_all_private();
	}

	/**
	 * Purge Cloudflare cache
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function purge_all_private() {
		Debug2::debug('[Cloudflare] purge_all_private');

		$cf_on = $this->conf(self::O_CDN_CLOUDFLARE);
		if (!$cf_on) {
			$msg = __('Cloudflare API is set to off.', 'litespeed-cache');
			Admin_Display::error($msg);
			return;
		}

		$zone = $this->zone();
		if (!$zone) {
			return;
		}

		$url  = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/purge_cache';
		$data = array( 'purge_everything' => true );

		$res = $this->cloudflare_call($url, 'DELETE', $data);

		if ($res) {
			$msg = __('Notified Cloudflare to purge all successfully.', 'litespeed-cache');
			Admin_Display::success($msg);
		}
	}

	/**
	 * Purge Cloudflare cache for a specific post by purging its related URLs
	 *
	 * @since  7.x
	 * @access public
	 * @param int $post_id The post ID to purge.
	 */
	public static function purge_post( $post_id ) {
		self::cls()->purge_post_private( $post_id );
	}

	/**
	 * Purge Cloudflare cache for a specific post's related URLs
	 *
	 * @since  7.x
	 * @access private
	 * @param int $post_id The post ID to purge.
	 */
	private function purge_post_private( $post_id ) {
		$cf_on = $this->conf( self::O_CDN_CLOUDFLARE );
		if ( ! $cf_on ) {
			return;
		}

		$zone = $this->zone();
		if ( ! $zone ) {
			return;
		}

		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type_object( get_post_type( $post_id ) );
		if ( ! is_post_type_viewable( $post_type ) ) {
			return;
		}

		$urls = $this->get_post_related_urls( $post_id );
		if ( empty( $urls ) ) {
			return;
		}

		// CF API allows max 30 URLs per request
		$chunks = array_chunk( $urls, 30 );
		foreach ( $chunks as $chunk ) {
			$this->purge_urls_private( $zone, $chunk );
		}
	}

	/**
	 * Purge specific URLs from Cloudflare cache
	 *
	 * @since  7.x
	 * @access private
	 * @param string $zone  The Cloudflare zone ID.
	 * @param array  $urls  Array of URLs to purge.
	 */
	private function purge_urls_private( $zone, $urls ) {
		Debug2::debug( '[Cloudflare] purge_urls_private: ' . count( $urls ) . ' URLs' );

		$url  = 'https://api.cloudflare.com/client/v4/zones/' . $zone . '/purge_cache';
		$data = array( 'files' => array_values( $urls ) );

		$res = $this->cloudflare_call( $url, 'DELETE', $data, false );

		if ( $res ) {
			Debug2::debug( '[Cloudflare] purge_urls_private succeeded' );
		} else {
			Debug2::debug( '[Cloudflare] purge_urls_private failed' );
		}
	}

	/**
	 * Get all related URLs for a post
	 *
	 * @since  7.x
	 * @access private
	 * @param int $post_id The post ID.
	 * @return array List of URLs.
	 */
	private function get_post_related_urls( $post_id ) {
		$urls      = array();
		$post_type = get_post_type( $post_id );

		// Post URL
		$permalink = get_permalink( $post_id );
		if ( $permalink ) {
			$urls[] = $permalink;
		}

		// Trashed post URL
		if ( get_post_status( $post_id ) === 'trash' && $permalink ) {
			$trash_url = str_replace( '__trashed', '', $permalink );
			$urls[] = $trash_url;
		}

		// Taxonomy terms and their feeds
		$taxonomies = get_object_taxonomies( $post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy_data = get_taxonomy( $taxonomy );
			if ( $taxonomy_data instanceof \WP_Taxonomy && false === $taxonomy_data->public ) {
				continue;
			}

			$terms = get_the_terms( $post_id, $taxonomy );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );
				if ( ! is_wp_error( $term_link ) ) {
					$urls[] = $term_link;
				}
				$term_feed_link = get_term_feed_link( $term->term_id, $term->taxonomy );
				if ( ! is_wp_error( $term_feed_link ) ) {
					$urls[] = $term_feed_link;
				}
			}
		}

		// Author URL
		$urls[] = get_author_posts_url( get_post_field( 'post_author', $post_id ) );
		$urls[] = get_author_feed_link( get_post_field( 'post_author', $post_id ) );

		// Post type archive
		if ( get_post_type_archive_link( $post_type ) ) {
			$urls[] = get_post_type_archive_link( $post_type );
			$urls[] = get_post_type_archive_feed_link( $post_type );
		}

		// Feeds
		$urls[] = get_bloginfo_rss( 'rdf_url' );
		$urls[] = get_bloginfo_rss( 'rss_url' );
		$urls[] = get_bloginfo_rss( 'rss2_url' );
		$urls[] = get_bloginfo_rss( 'atom_url' );
		$urls[] = get_bloginfo_rss( 'comments_rss2_url' );
		$urls[] = get_post_comments_feed_link( $post_id );

		// Home page
		$urls[] = home_url( '/' );

		// Posts page
		$page_for_posts = get_option( 'page_for_posts' );
		if ( $page_for_posts && get_option( 'show_on_front' ) === 'page' ) {
			$page_link = get_permalink( $page_for_posts );
			if ( $page_link ) {
				$urls[] = $page_link;
			}
		}

		// Pagination (first 3 pages)
		$total_posts  = wp_count_posts()->publish;
		$per_page     = get_option( 'posts_per_page' );
		$max_pages    = min( 3, ceil( $total_posts / $per_page ) );
		for ( $i = 2; $i <= $max_pages; $i++ ) {
			$urls[] = home_url( sprintf( '/page/%d/', $i ) );
		}

		// Clean: remove empty, duplicates
		$urls = array_values( array_filter( array_unique( $urls ) ) );

		Debug2::debug( '[Cloudflare] get_post_related_urls: ' . count( $urls ) . ' URLs for post ' . $post_id );

		return $urls;
	}

	/**
	 * Get current Cloudflare zone from cfg
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function zone() {
		$zone = $this->conf(self::O_CDN_CLOUDFLARE_ZONE);
		if (!$zone) {
			$msg = __('No available Cloudflare zone', 'litespeed-cache');
			Admin_Display::error($msg);
			return false;
		}

		return $zone;
	}

	/**
	 * Get Cloudflare zone settings
	 *
	 * @since  1.7.2
	 * @access private
	 */
	private function fetch_zone() {
		$kw = $this->conf(self::O_CDN_CLOUDFLARE_NAME);

		$url = 'https://api.cloudflare.com/client/v4/zones?status=active&match=all';

		// Try exact match first
		if ($kw && false !== strpos($kw, '.')) {
			$zones = $this->cloudflare_call($url . '&name=' . $kw, 'GET', false, false);
			if ($zones) {
				Debug2::debug('[Cloudflare] fetch_zone exact matched');
				return $zones[0];
			}
		}

		// Can't find, try to get default one
		$zones = $this->cloudflare_call($url, 'GET', false, false);

		if (!$zones) {
			Debug2::debug('[Cloudflare] fetch_zone no zone');
			return false;
		}

		if (!$kw) {
			Debug2::debug('[Cloudflare] fetch_zone no set name, use first one by default');
			return $zones[0];
		}

		foreach ($zones as $v) {
			if (false !== strpos($v['name'], $kw)) {
				Debug2::debug('[Cloudflare] fetch_zone matched ' . $kw . ' [name] ' . $v['name']);
				return $v;
			}
		}

		// Can't match current name, return default one
		Debug2::debug('[Cloudflare] fetch_zone failed match name, use first one by default');
		return $zones[0];
	}

	/**
	 * Cloudflare API
	 *
	 * @since  1.7.2
	 * @access private
	 * @param string     $url      The API URL to call.
	 * @param string     $method   The HTTP method to use (GET, POST, etc.).
	 * @param array|bool $data     The data to send with the request, or false if none.
	 * @param bool       $show_msg Whether to show success/error message.
	 */
	private function cloudflare_call( $url, $method = 'GET', $data = false, $show_msg = true ) {
		Debug2::debug("[Cloudflare] cloudflare_call \t\t[URL] $url");

		/**
		 * Detect key type: Global API Key (37-char hex) vs API Token (Bearer)
		 *
		 * @since 1.9.0
		 */
		$cf_key = $this->conf( self::O_CDN_CLOUDFLARE_KEY );
		if ( strlen( $cf_key ) === 37 && preg_match( '/^[0-9a-f]+$/', $cf_key ) ) {
			$headers = [
				'Content-Type' => 'application/json',
				'X-Auth-Email' => $this->conf( self::O_CDN_CLOUDFLARE_EMAIL ),
				'X-Auth-Key'   => $cf_key,
			];
		} else {
			$headers = [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $cf_key,
			];
		}

		$wp_args = array(
			'method'  => $method,
			'headers' => $headers,
		);

		if ($data) {
			if (is_array($data)) {
				$data = wp_json_encode($data);
			}
			$wp_args['body'] = $data;
		}
		add_filter( 'http_api_curl', $fn = function ( $handle ) {
			defined( 'CURLOPT_SSL_ENABLE_ALPN' ) && \curl_setopt( $handle, CURLOPT_SSL_ENABLE_ALPN, false ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- http_api_curl filter requires direct curl handle manipulation; wp_remote_get() is not applicable here.
			return $handle;
		}, 9999 );
		$resp = wp_remote_request( $url, $wp_args );
		remove_filter( 'http_api_curl', $fn, 9999 );
		if (is_wp_error($resp)) {
			Debug2::debug('[Cloudflare] error in response');
			if ($show_msg) {
				$msg = __('Failed to communicate with Cloudflare', 'litespeed-cache');
				Admin_Display::error($msg);
			}
			return false;
		}

		$result = wp_remote_retrieve_body($resp);

		$json = \json_decode($result, true);

		if ($json && $json['success'] && $json['result']) {
			Debug2::debug('[Cloudflare] cloudflare_call called successfully');
			if ($show_msg) {
				$msg = __('Communicated with Cloudflare successfully.', 'litespeed-cache');
				Admin_Display::success($msg);
			}

			return $json['result'];
		}

		Debug2::debug("[Cloudflare] cloudflare_call called failed: $result");
		if ($show_msg) {
			$msg = __('Failed to communicate with Cloudflare', 'litespeed-cache');
			Admin_Display::error($msg);
		}

		return false;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.7.2
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_PURGE_ALL:
            $this->purge_all_private();
				break;

			case self::TYPE_GET_DEVMODE:
            $this->get_devmode();
				break;

			case self::TYPE_SET_DEVMODE_ON:
			case self::TYPE_SET_DEVMODE_OFF:
            $this->set_devmode($type);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
