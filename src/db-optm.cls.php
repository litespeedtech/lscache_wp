<?php

/**
 * The admin optimize tool
 *
 * @since      1.2.1
 * @package    LiteSpeed
 * @subpackage LiteSpeed/src
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class DB_Optm extends Root {

	private static $_hide_more = false;

	private static $TYPES = array(
		'revision',
		'orphaned_post_meta',
		'auto_draft',
		'trash_post',
		'spam_comment',
		'trash_comment',
		'trackback-pingback',
		'expired_transient',
		'all_transients',
		'optimize_tables',
	);
	const TYPE_CONV_TB    = 'conv_innodb';

	/**
	 * Show if there are more sites in hidden
	 *
	 * @since  3.0
	 */
	public static function hide_more() {
		return self::$_hide_more;
	}

	/**
	 * Clean/Optimize WP tables
	 *
	 * @since  1.2.1
	 * @access public
	 * @param  string $type The type to clean
	 * @param  bool   $ignore_multisite If ignore multisite check
	 * @return  int The rows that will be affected
	 */
	public function db_count( $type, $ignore_multisite = false ) {
		if ($type === 'all') {
			$num = 0;
			foreach (self::$TYPES as $v) {
				$num += $this->db_count($v);
			}
			return $num;
		}

		if (!$ignore_multisite) {
			if (is_multisite() && is_network_admin()) {
				$num   = 0;
				$blogs = Activation::get_network_ids();
				foreach ($blogs as $k => $blog_id) {
					if ($k > 3) {
						self::$_hide_more = true;
						break;
					}

					switch_to_blog($blog_id);
					$num += $this->db_count($type, true);
					restore_current_blog();
				}
				return $num;
			}
		}

		global $wpdb;

		switch ($type) {
			case 'revision':
            $rev_max = (int) $this->conf(Base::O_DB_OPTM_REVISIONS_MAX);
            $rev_age = (int) $this->conf(Base::O_DB_OPTM_REVISIONS_AGE);
            $sql_add = '';
            if ($rev_age) {
					$sql_add = " and post_modified < DATE_SUB( NOW(), INTERVAL $rev_age DAY ) ";
				}
            $sql = "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_type = 'revision' $sql_add";
            if (!$rev_max) {
					return $wpdb->get_var($sql);
				}
            // Has count limit
            $sql = "SELECT COUNT(*)-$rev_max FROM `$wpdb->posts` WHERE post_type = 'revision' $sql_add GROUP BY post_parent HAVING count(*)>$rev_max";
            $res = $wpdb->get_results($sql, ARRAY_N);

            Utility::compatibility();
				return array_sum(array_column($res, 0));

			case 'orphaned_post_meta':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->postmeta` a LEFT JOIN `$wpdb->posts` b ON b.ID=a.post_id WHERE b.ID IS NULL");

			case 'auto_draft':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'auto-draft'");

			case 'trash_post':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'trash'");

			case 'spam_comment':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_approved = 'spam'");

			case 'trash_comment':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_approved = 'trash'");

			case 'trackback-pingback':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback'");

			case 'expired_transient':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE '_transient_timeout%' AND option_value < " . time());

			case 'all_transients':
				return $wpdb->get_var("SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE '%_transient_%'");

			case 'optimize_tables':
				return $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = '" . DB_NAME . "' and ENGINE <> 'InnoDB' and DATA_FREE > 0");
		}

		return '-';
	}

	/**
	 * Clean/Optimize WP tables
	 *
	 * @since  1.2.1
	 * @since 3.0 changed to private
	 * @access private
	 */
	private function _db_clean( $type ) {
		if ($type === 'all') {
			foreach (self::$TYPES as $v) {
				$this->_db_clean($v);
			}
			return __('Clean all successfully.', 'litespeed-cache');
		}

		global $wpdb;
		switch ($type) {
			case 'revision':
            $rev_max = (int) $this->conf(Base::O_DB_OPTM_REVISIONS_MAX);
            $rev_age = (int) $this->conf(Base::O_DB_OPTM_REVISIONS_AGE);

            $postmeta = "`$wpdb->postmeta`";
            $posts    = "`$wpdb->posts`";

            $sql_postmeta_join = function ( $table ) use ( $postmeta, $posts ) {
					return "
						$postmeta
						CROSS JOIN $table
						ON $posts.ID = $postmeta.post_id
					";
				};

				$sql_where = "WHERE $posts.post_type = 'revision'";

				$sql_add = $rev_age ? "AND $posts.post_modified < DATE_SUB( NOW(), INTERVAL $rev_age DAY )" : '';

				if (!$rev_max) {
					$sql_where    = "$sql_where $sql_add";
					$sql_postmeta = $sql_postmeta_join($posts);
					$wpdb->query("DELETE $postmeta FROM $sql_postmeta $sql_where");
					$wpdb->query("DELETE FROM $posts $sql_where");
				} else {
					// Has count limit
					$sql          = "
						SELECT COUNT(*) - $rev_max
						AS del_max, post_parent
						FROM $posts
						WHERE post_type = 'revision'
						$sql_add
						GROUP BY post_parent
						HAVING COUNT(*) > $rev_max
					";
					$res          = $wpdb->get_results($sql);
					$sql_where    = "
						$sql_where
						AND post_parent = %d
						ORDER BY ID
						LIMIT %d
					";
					$sql_postmeta = $sql_postmeta_join("(SELECT ID FROM $posts $sql_where) AS $posts");
					foreach ($res as $v) {
						$args = array( $v->post_parent, $v->del_max );
						$sql  = $wpdb->prepare("DELETE $postmeta FROM $sql_postmeta", $args);
						$wpdb->query($sql);
						$sql = $wpdb->prepare("DELETE FROM $posts $sql_where", $args);
						$wpdb->query($sql);
					}
				}

				return __('Clean post revisions successfully.', 'litespeed-cache');

			case 'orphaned_post_meta':
            $wpdb->query("DELETE a FROM `$wpdb->postmeta` a LEFT JOIN `$wpdb->posts` b ON b.ID=a.post_id WHERE b.ID IS NULL");
				return __('Clean orphaned post meta successfully.', 'litespeed-cache');

			case 'auto_draft':
            $wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_status = 'auto-draft'");
				return __('Clean auto drafts successfully.', 'litespeed-cache');

			case 'trash_post':
            $wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_status = 'trash'");
				return __('Clean trashed posts and pages successfully.', 'litespeed-cache');

			case 'spam_comment':
            $wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_approved = 'spam'");
				return __('Clean spam comments successfully.', 'litespeed-cache');

			case 'trash_comment':
            $wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_approved = 'trash'");
				return __('Clean trashed comments successfully.', 'litespeed-cache');

			case 'trackback-pingback':
            $wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback'");
				return __('Clean trackbacks and pingbacks successfully.', 'litespeed-cache');

			case 'expired_transient':
            $wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '_transient_timeout%' AND option_value < " . time());
				return __('Clean expired transients successfully.', 'litespeed-cache');

			case 'all_transients':
            $wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '%\\_transient\\_%'");
				return __('Clean all transients successfully.', 'litespeed-cache');

			case 'optimize_tables':
            $sql    = "SELECT table_name, DATA_FREE FROM information_schema.tables WHERE TABLE_SCHEMA = '" . DB_NAME . "' and ENGINE <> 'InnoDB' and DATA_FREE > 0";
            $result = $wpdb->get_results($sql);
            if ($result) {
					foreach ($result as $row) {
                    $wpdb->query('OPTIMIZE TABLE ' . $row->table_name);
						}
				}
				return __('Optimized all tables.', 'litespeed-cache');
		}
	}

	/**
	 * Get all myisam tables
	 *
	 * @since 3.0
	 * @access public
	 */
	public function list_myisam() {
		global $wpdb;
		$q = "SELECT * FROM information_schema.tables WHERE TABLE_SCHEMA = '" . DB_NAME . "' and ENGINE = 'myisam' AND TABLE_NAME LIKE '{$wpdb->prefix}%'";
		return $wpdb->get_results($q);
	}

	/**
	 * Convert tables to InnoDB
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _conv_innodb() {
		global $wpdb;

		if (empty($_GET['tb'])) {
			Admin_Display::error('No table to convert');
			return;
		}

		$tb = false;

		$list = $this->list_myisam();
		foreach ($list as $v) {
			if ($v->TABLE_NAME == $_GET['tb']) {
				$tb = $v->TABLE_NAME;
				break;
			}
		}

		if (!$tb) {
			Admin_Display::error('No existing table');
			return;
		}

		$q = 'ALTER TABLE ' . DB_NAME . '.' . $tb . ' ENGINE = InnoDB';
		$wpdb->query($q);

		Debug2::debug("[DB] Converted $tb to InnoDB");

		$msg = __('Converted to InnoDB successfully.', 'litespeed-cache');
		Admin_Display::success($msg);
	}

	/**
	 * Count all autoload size
	 *
	 * @since  3.0
	 * @access public
	 */
	public function autoload_summary() {
		global $wpdb;

		$autoloads = function_exists('wp_autoload_values_to_autoload') ? wp_autoload_values_to_autoload() : array( 'yes', 'on', 'auto-on', 'auto' );
		$autoloads = '("' . implode('","', $autoloads) . '")';

		$summary = $wpdb->get_row("SELECT SUM(LENGTH(option_value)) AS autoload_size,COUNT(*) AS autload_entries FROM `$wpdb->options` WHERE autoload IN " . $autoloads);

		$summary->autoload_toplist = $wpdb->get_results(
			"SELECT option_name, LENGTH(option_value) AS option_value_length, autoload FROM `$wpdb->options` WHERE autoload IN " .
				$autoloads .
				' ORDER BY option_value_length DESC LIMIT 20'
		);

		return $summary;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case 'all':
			case in_array($type, self::$TYPES):
            if (is_multisite() && is_network_admin()) {
					$blogs = Activation::get_network_ids();
					foreach ($blogs as $blog_id) {
                    switch_to_blog($blog_id);
                    $msg = $this->_db_clean($type);
                    restore_current_blog();
						}
				} else {
                $msg = $this->_db_clean($type);
				}
            Admin_Display::success($msg);
				break;

			case self::TYPE_CONV_TB:
            $this->_conv_innodb();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
