<?php

/**
 * LiteSpeed Cache Purge Interface
 */
class LiteSpeed_Cache_Cli_Purge
{

	/**
	 * Sends an ajax request to the site. Takes an action and the nonce string
	 * to perform.
	 *
	 * @since 1.0.14
	 * @param string $action The action to perform
	 * @param string $nonce_val The value to use for the nonce.
	 * @param array $extra Any extra parameters needed to be sent.
	 * @return mixed The http request return.
	 */
	private function send_request($action, $nonce_val, $extra = array())
	{
		$nonce = wp_create_nonce($nonce_val);

		$data = array(
			'action' => 'lscache_cli',
			LiteSpeed_Cache::ADMINQS_KEY => $action,
			'_wpnonce' => $nonce
		);
		if (!empty($extra)) {
			$data = array_merge($data, $extra);
		}

		$url = admin_url('admin-ajax.php');
		WP_CLI::debug('url is ' . $url);

		$out = WP_CLI\Utils\http_request('GET', $url, $data);
		return $out;
	}

	/**
	 * Purges all cache entries for the blog (the entire network if multisite).
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge Everything associated with the WordPress install.
	 *     $ wp lscache-purge all
	 *
	 */
	function all($args, $assoc_args)
	{
		if (is_multisite()) {
			$nonce_val = LiteSpeed_Cache::ADMINNONCE_PURGENETWORKALL;
		}
		else {
			$nonce_val = LiteSpeed_Cache::ADMINNONCE_PURGEALL;
		}

		$purge_ret = $this->send_request(LiteSpeed_Cache::ADMINQS_PURGEALL,
			$nonce_val);
		if ($purge_ret->success) {
			WP_CLI::success(__('Purged All!', 'litespeed-cache'));
		}
		else {
			WP_CLI::error('Something went wrong! Got '
				. $purge_ret->status_code);
		}
	}

	/**
	 * List all site domains and ids on the network.
	 *
	 * For use with the blog subcommand.
	 *
	 * ## EXAMPLES
	 *
	 *     # List all the site domains and ids in a table.
	 *     $ wp lscache-purge network_list
	 */
	function network_list($args, $assoc_args)
	{
		if (!is_multisite()) {
			WP_CLI::error('This is not a multisite installation!');

			return;
		}
		$buf = WP_CLI::colorize("%CThe list of installs:%n\n");

		if (version_compare($GLOBALS['wp_version'], '4.6', '<')) {
			$sites = wp_get_sites();
			foreach ($sites as $site) {
				$buf .= WP_CLI::colorize('%Y' . $site['domain'] . $site['path']
					. ':%n ID ' . $site['blog_id']) . "\n";
			}
		}
		else {
			$sites = get_sites();
			foreach ($sites as $site) {
				$buf .= WP_CLI::colorize('%Y' . $site->domain . $site->path
					. ':%n ID ' . $site->blog_id) . "\n";
			}
		}

		WP_CLI::line($buf);
	}

	/**
	 * Purges all cache entries for the blog.
	 *
	 * ## OPTIONS
	 *
	 * <blogid>
	 * : The blog id to purge
	 *
	 * ## EXAMPLES
	 *
	 *     # In a multisite install, purge only the shop.example.com cache (stored as blog id 2).
	 *     $ wp lscache-purge blog 2
	 *
	 */
	function blog($args, $assoc_args)
	{
		if (!is_multisite()) {
			WP_CLI::error('Not a multisite installation.');
			return;
		}
		$blogid = $args[0];
		if (!is_numeric($blogid)) {
			$error = WP_CLI::colorize('%RError: invalid blog id entered.%n');
			WP_CLI::line($error);
			$this->network_list($args, $assoc_args);
			return;
		}
		$site = get_blog_details($blogid);
		if ($site === false) {
			$error = WP_CLI::colorize('%RError: invalid blog id entered.%n');
			WP_CLI::line($error);
			$this->network_list($args, $assoc_args);
			return;
		}
		switch_to_blog($blogid);

		$purge_ret = $this->send_request(LiteSpeed_Cache::ADMINQS_PURGEALL,
			LiteSpeed_Cache::ADMINNONCE_PURGEALL);
		if ($purge_ret->success) {
			WP_CLI::success(__('Purged the blog!', 'litespeed-cache'));
		}
		else {
			WP_CLI::error('Something went wrong! Got '
				. $purge_ret->status_code);
		}
	}

	/**
	 * Purges all cache tags related to a url.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : The url to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the front page.
	 *     $ wp lscache-purge url https://mysite.com/
	 *
	 */
	function url($args, $assoc_args)
	{
		$data = array(
			LiteSpeed_Cache::ADMINQS_KEY => LiteSpeed_Cache::ADMINQS_PURGE,
		);
		$url = $args[0];
		$deconstructed = wp_parse_url($url);
		if (empty($deconstructed)) {
			WP_CLI::error('url passed in is invalid.');
			return;
		}

		if (is_multisite()) {
			if (get_blog_id_from_url($deconstructed['host'], '/') === 0) {
				WP_CLI::error('Multisite url passed in is invalid.');
				return;
			}
		}
		else {
			$site_url = get_site_url();
			$deconstructed_site = wp_parse_url($site_url);
			if ($deconstructed['host'] !== $deconstructed_site['host']) {
				WP_CLI::error('Single site url passed in is invalid.');
				return;
			}
		}

		WP_CLI::debug('url is ' . $url);

		$purge_ret = WP_CLI\Utils\http_request('GET', $url, $data);
		if ($purge_ret->success) {
			WP_CLI::success(__('Purged the url!', 'litespeed-cache'));
		}
		else {
			WP_CLI::error('Something went wrong! Got '
				. $purge_ret->status_code);
		}
	}

	/**
	 * Helper function for purging by ids.
	 *
	 * @access private
	 * @since 1.0.15
	 * @param array $args The id list to parse.
	 * @param string $prefix
	 * @param function(int $id) $callback The callback function to check the id.
	 */
	private function purgeby_helper($args, $prefix, $callback)
	{
		$filtered = array();
		foreach ($args as $val) {
			if (!ctype_digit($val)) {
				WP_CLI::debug('[LSCACHE] Skip val, not a number. ' . $val);
				continue;
			}
			$term = $callback($val);
			if (!empty($term)) {
				$filtered[] = $prefix . $val;
			}
			else {
				WP_CLI::debug('[LSCACHE] Skip val, not a valid term. ' . $val);
			}
		}

		if (empty($filtered)) {
			WP_CLI::error('Arguments must be integer ids.');
			return;
		}

		$str = implode(',', $filtered);

		WP_CLI::line('Will purge the following cache tags: ' . $str);

		$purge_ret = $this->send_request(LiteSpeed_Cache::ADMINQS_PURGEBY,
			LiteSpeed_Cache::ADMINNONCE_PURGEBY, array('purge_tags' => $str));
		if ($purge_ret->success) {
			WP_CLI::success(__('Purged the tags!', 'litespeed-cache'));
		}
		else {
			WP_CLI::error('Something went wrong! Got '
				. $purge_ret->status_code);
		}

	}

	/**
	 * Purges all cache tags for a WordPress tag
	 *
	 * ## OPTIONS
	 *
	 * <ids>...
	 * : the Term IDs to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the tag ids 1, 3, and 5
	 *     $ wp lscache-purge tag 1 3 5
	 *
	 */
	function tag($args, $assoc_args)
	{
		$this->purgeby_helper($args,
			LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM, 'get_tag');
	}

	/**
	 * Purges all cache tags for a WordPress category
	 *
	 * ## OPTIONS
	 *
	 * <ids>...
	 * : the Term IDs to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the category ids 1, 3, and 5
	 *     $ wp lscache-purge category 1 3 5
	 *
	 */
	function category($args, $assoc_args)
	{
		$this->purgeby_helper($args,
			LiteSpeed_Cache_Tags::TYPE_ARCHIVE_TERM, 'get_category');
	}

	/**
	 * Purges all cache tags for a WordPress Post/Product
	 *
	 * @alias product
	 *
	 * ## OPTIONS
	 *
	 * <ids>...
	 * : the Post IDs to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the post ids 1, 3, and 5
	 *     $ wp lscache-purge post_id 1 3 5
	 *
	 */
	function post_id($args, $assoc_args)
	{
		$this->purgeby_helper($args,
			LiteSpeed_Cache_Tags::TYPE_POST, 'get_post');
	}

}
