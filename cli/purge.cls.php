<?php
/**
 * LiteSpeed Cache Purge Interface CLI.
 *
 * @package LiteSpeed\CLI
 */

namespace LiteSpeed\CLI;

defined( 'WPINC' ) || exit();

use LiteSpeed\Core;
use LiteSpeed\Admin_Display;
use WP_CLI;

/**
 * LiteSpeed Cache Purge Interface
 */
class Purge {

	/**
	 * List all site domains and ids on the network.
	 *
	 * For use with the blog subcommand.
	 *
	 * ## EXAMPLES
	 *
	 *     # List all the site domains and ids in a table.
	 *     $ wp litespeed-purge network_list
	 */
	public function network_list() {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation!' );
			return;
		}

		$buf = WP_CLI::colorize( '%CThe list of installs:%n' ) . PHP_EOL;

		$sites = get_sites();
		foreach ( $sites as $site ) {
			$buf .= WP_CLI::colorize( '%Y' . $site->domain . $site->path . ':%n ID ' . $site->blog_id ) . PHP_EOL;
		}

		WP_CLI::line( $buf );
	}

	/**
	 * Dispatch a purge through the loaded plugin instance.
	 *
	 * @param string $action The action to perform.
	 * @param array  $extra  Additional data to include in the request.
	 * @return void
	 * @since 1.0.14
	 */
	private function dispatch( $action, $extra = [] ) {
		// WP-CLI invokes this method directly; no browser request or nonce is involved.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_REQUEST = array_merge( $_REQUEST, $extra );
		Core::cls()->proceed_action( $action );
	}

	/**
	 * Purges all cache entries for the blog (the entire network if multisite).
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge Everything associated with the WordPress install.
	 *     $ wp litespeed-purge all
	 */
	public function all() {
		$action = is_multisite() ? Core::ACTION_QS_PURGE_EMPTYCACHE : Core::ACTION_QS_PURGE_ALL;

		$this->dispatch( $action );
		$this->kick_or_fail();
		WP_CLI::success( __( 'Purge all sent to the web server.', 'litespeed-cache' ) );
	}

	/**
	 * Deliver the queued purge to the web server now; a failed delivery keeps the queue and exits with an error.
	 *
	 * @since 7.9.2
	 */
	private function kick_or_fail() {
		if ( ! Core::kick_purge_queue() ) {
			WP_CLI::error( 'Could not notify the web server to purge.' );
		}
	}

	/**
	 * Purges all cache entries for the blog.
	 *
	 * ## OPTIONS
	 *
	 * <blogid>
	 * : The blog id to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # In a multisite install, purge only the shop.example.com cache (stored as blog id 2).
	 *     $ wp litespeed-purge blog 2
	 *
	 * @param array $args Positional arguments (blogid).
	 */
	public function blog( $args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'Not a multisite installation.' );
			return;
		}

		$blogid = $args[0];
		if ( ! is_numeric( $blogid ) ) {
			$error = WP_CLI::colorize( '%RError: invalid blog id entered.%n' );
			WP_CLI::line( $error );
			$this->network_list( $args );
			return;
		}

		$site = get_blog_details( $blogid );
		if ( false === $site ) {
			$error = WP_CLI::colorize( '%RError: invalid blog id entered.%n' );
			WP_CLI::line( $error );
			$this->network_list( $args );
			return;
		}

		switch_to_blog( $blogid );

		try {
			$this->dispatch( Core::ACTION_QS_PURGE_ALL );
			// Kick on the target blog so its own queue and endpoint are used before restoring.
			$this->kick_or_fail();
		} finally {
			restore_current_blog();
		}
		WP_CLI::success( __( 'Blog purge sent to the web server.', 'litespeed-cache' ) );
	}

	/**
	 * Purges all cache tags related to a URL.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : The URL to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the front page.
	 *     $ wp litespeed-purge url https://mysite.com/
	 *
	 * @param array $args Positional arguments (URL).
	 */
	public function url( $args ) {
		$url           = $args[0];
		$deconstructed = wp_parse_url( $url );
		if ( empty( $deconstructed['host'] ) ) {
			WP_CLI::error( 'URL passed in is invalid.' );
			return;
		}

		$switched = false;
		if ( is_multisite() ) {
			$site = get_site_by_path( $deconstructed['host'], isset( $deconstructed['path'] ) ? $deconstructed['path'] : '/' );
			if ( ! $site ) {
				WP_CLI::error( 'Multisite URL passed in is invalid.' );
				return;
			}
			if ( get_current_blog_id() !== (int) $site->blog_id ) {
				switch_to_blog( $site->blog_id );
				$switched = true;
			}
		} else {
			$deconstructed_site = wp_parse_url( get_home_url() );
			if ( $deconstructed['host'] !== $deconstructed_site['host'] ) {
				WP_CLI::error( 'Single site URL passed in is invalid.' );
				return;
			}
		}

		WP_CLI::debug( 'URL is ' . $url );

		try {
			if ( ! \LiteSpeed\Purge::cls()->purge_url( $url ) || ! Core::kick_purge_queue() ) {
				WP_CLI::error( 'Could not purge the URL.' );
				return;
			}
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
		WP_CLI::success( __( 'Purged the URL!', 'litespeed-cache' ) );
	}

	/**
	 * Helper function for purging by IDs.
	 *
	 * @param array    $args     The ID list to parse.
	 * @param string   $select   The purge by kind.
	 * @param callable $callback The callback function to check the ID.
	 */
	private function purgeby( $args, $select, $callback ) {
		$filtered = array();
		foreach ( $args as $val ) {
			if ( ! ctype_digit( $val ) ) {
				WP_CLI::debug( '[LSCACHE] Skip val, not a number. ' . $val );
				continue;
			}
			$term = $callback( $val );
			if ( ! empty( $term ) ) {
				WP_CLI::line( $term->name );
				$filtered[] = in_array( $callback, [ 'get_tag', 'get_category' ], true ) ? $term->slug : $val;
			} else {
				WP_CLI::debug( '[LSCACHE] Skip val, not a valid term. ' . $val );
			}
		}

		if ( empty( $filtered ) ) {
			WP_CLI::error( 'Arguments must be integer IDs.' );
			return;
		}

		$str = implode( ',', $filtered );

		$purge_titles = array(
			Admin_Display::PURGEBY_CAT => 'Category',
			Admin_Display::PURGEBY_PID => 'Post ID',
			Admin_Display::PURGEBY_TAG => 'Tag',
			Admin_Display::PURGEBY_URL => 'URL',
		);

		WP_CLI::line( 'Will purge the following: [' . $purge_titles[ $select ] . '] ' . $str );

		$data = array(
			Admin_Display::PURGEBYOPT_SELECT => $select,
			Admin_Display::PURGEBYOPT_LIST   => $str,
		);

		$this->dispatch( Core::ACTION_PURGE_BY, $data );
		$this->kick_or_fail();
		WP_CLI::success( __( 'Purge sent to the web server.', 'litespeed-cache' ) );
	}

	/**
	 * Purges cache tags for a WordPress tag.
	 *
	 * ## OPTIONS
	 *
	 * <ids>...
	 * : The Term IDs to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the tag IDs 1, 3, and 5
	 *     $ wp litespeed-purge tag 1 3 5
	 *
	 * @param array $args Positional arguments (IDs).
	 */
	public function tag( $args ) {
		$this->purgeby( $args, Admin_Display::PURGEBY_TAG, 'get_tag' );
	}

	/**
	 * Purges cache tags for a WordPress category.
	 *
	 * ## OPTIONS
	 *
	 * <ids>...
	 * : The Term IDs to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the category IDs 1, 3, and 5
	 *     $ wp litespeed-purge category 1 3 5
	 *
	 * @param array $args Positional arguments (IDs).
	 */
	public function category( $args ) {
		$this->purgeby( $args, Admin_Display::PURGEBY_CAT, 'get_category' );
	}

	/**
	 * Purges cache tags for a WordPress Post/Product.
	 *
	 * @alias product
	 *
	 * ## OPTIONS
	 *
	 * <ids>...
	 * : The Post IDs to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the post IDs 1, 3, and 5
	 *     $ wp litespeed-purge post_id 1 3 5
	 *
	 * @param array $args Positional arguments (IDs).
	 */
	public function post_id( $args ) {
		$this->purgeby( $args, Admin_Display::PURGEBY_PID, 'get_post' );
	}
}
