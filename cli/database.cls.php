<?php

namespace LiteSpeed\CLI;

defined('WPINC') || exit();

use LiteSpeed\Debug2;
use LiteSpeed\DB_Optm;
use WP_CLI;

/**
 * LiteSpeed Cache Database CLI
 */
class Database
{
	private $__current_blog = false;
	private $__db;

	public function __construct()
	{
		Debug2::debug('CLI_Database init');

		$this->__db = DB_Optm::cls();
	}

	/**
	 * List all site domains and ids on the network.
	 */
	public function network_list()
	{
		if (!is_multisite()) {
			WP_CLI::error('This is not a multisite installation!');

			return;
		}
		$buf = WP_CLI::colorize("%CThe list of installs:%n\n");

		if (version_compare($GLOBALS['wp_version'], '4.6', '<')) {
			$sites = wp_get_sites();
			foreach ($sites as $site) {
				$buf .= WP_CLI::colorize('%Y' . $site['domain'] . $site['path'] . ':%n ID ' . $site['blog_id']) . "\n";
			}
		} else {
			$sites = get_sites();
			foreach ($sites as $site) {
				$buf .= WP_CLI::colorize('%Y' . $site->domain . $site->path . ':%n ID ' . $site->blog_id) . "\n";
			}
		}

		WP_CLI::line($buf);
	}

	/**
	 * Change to blog sent as param.
	 */
	private function change_to_blog($args)
	{
		if (isset($args[0]) && $args[0] === 'blog') {
			$this->__current_blog = get_current_blog_id();
			$blogid = $args[1];
			if (!is_numeric($blogid)) {
				$error = WP_CLI::colorize('%RError: invalid blog id entered.%n');
				WP_CLI::line($error);
				$this->network_list($args);
				return;
			}
			$site = get_blog_details($blogid);
			if ($site === false) {
				$error = WP_CLI::colorize('%RError: invalid blog id entered.%n');
				WP_CLI::line($error);
				$this->network_list($args);
				return;
			}
			switch_to_blog($blogid);
		}
	}

	/**
	 * Change to previous blog.
	 */
	private function change_to_default()
	{
		// Check if previous blog set.
		if ($this->__current_blog) {
			switch_to_blog($this->__current_blog);
			// Switched to previous blog.
			$this->__current_blog = false;
		}
	}

	/**
	 * Show response.
	 */
	private function show_response($result, $action)
	{
		if ($result) {
			WP_CLI::success($result);
		} else {
			WP_CLI::error("Error running optimization: " . $action);
		}
	}

	/**
	 * Show response.
	 */
	private function clean_action($args, $types)
	{
		$this->change_to_blog($args);
		foreach ($types as $type) {
			$result = $this->__db->handler_clean_db_cli($type);
			$this->show_response($result, $type);
		}
		$this->change_to_default();
	}

	/**
	 * Clear posts data(revisions, orphaned, auto drafts, trashed posts).
	 *
	 * 	   # Start clearing posts data.
	 *     $ wp litespeed-database clear_posts
	 *     $ wp litespeed-database clear_posts blog 2
	 */
	public function clear_posts($args)
	{
		$types = [
			'revision',
			'orphaned_post_meta',
			'auto_draft',
			'trash_post'
		];
		$this->clean_action($args, $types);
	}

	/**
	 * Clear comments(spam and trash comments).
	 *
	 * 	   # Start clearing comments.
	 *     $ wp litespeed-database clear_comments
	 *     $ wp litespeed-database clear_comments blog 2
	 */
	public function clear_comments($args)
	{
		$types = [
			'spam_comment',
			'trash_comment'
		];
		$this->clean_action($args, $types);
	}

	/**
	 * Clear trackbacks/pingbacks.
	 *
	 * 	   # Start clearing trackbacks/pingbacks.
	 *     $ wp litespeed-database clear_trackbacks
	 *     $ wp litespeed-database clear_trackbacks blog 2
	 */
	public function clear_trackbacks($args)
	{
		$types = [
			'trackback-pingback'
		];
		$this->clean_action($args, $types);
	}

	/**
	 * Clear transients.
	 *
	 * 	   # Start clearing transients.
	 *     $ wp litespeed-database clear_transients
	 *     $ wp litespeed-database clear_transients blog 2
	 */
	public function clear_transients($args)
	{
		$types = [
			'expired_transient',
			'all_transients'
		];
		$this->clean_action($args, $types);
	}

	/**
	 * Optimize tables.
	 *
	 * 	   # Start optimizing tables.
	 *     $ wp litespeed-database optimize_tables
	 *     $ wp litespeed-database optimize_tables blog 2
	 */
	public function optimize_tables($args)
	{
		$types = [
			'optimize_tables'
		];
		$this->clean_action($args, $types);
	}

	/**
	 * Optimize database by running all possible opreations.
	 *
	 * 	   # Start optimizing all.
	 *     $ wp litespeed-database optimize_all
	 *     $ wp litespeed-database optimize_all blog 2
	 */
	public function optimize_all($args)
	{
		$types = [
			'all'
		];
		$this->clean_action($args, $types);
	}
}
