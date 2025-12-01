<?php
/**
 * The admin optimize tool.
 *
 * @package LiteSpeed
 * @since 1.2.1
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Database optimization utilities for LiteSpeed.
 */
class DB_Optm extends Root {

	/**
	 * Whether there are more sites hidden in multisite counts.
	 *
	 * @var bool
	 */
	private static $_hide_more = false;

	/**
	 * Supported cleanup types.
	 *
	 * @var string[]
	 */
	private static $types = [
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
	];

	/**
	 * Convert tables to InnoDB type identifier.
	 */
	const TYPE_CONV_TB = 'conv_innodb';

	/**
	 * Show if there are more sites in hidden.
	 *
	 * @since 3.0
	 * @return bool
	 */
	public static function hide_more() {
		return self::$_hide_more;
	}

	/**
	 * Clean/Optimize WP tables.
	 *
	 * @since 1.2.1
	 * @access public
	 * @param string $type             The type to clean.
	 * @param bool   $ignore_multisite If ignoring multisite check.
	 * @return int|string The rows that will be affected, or '-' on unknown.
	 */
	public function db_count( $type, $ignore_multisite = false ) {
		if ( 'all' === $type ) {
			$num = 0;
			foreach ( self::$types as $v ) {
				$num += (int) $this->db_count( $v );
			}
			return $num;
		}

		if ( ! $ignore_multisite ) {
			if ( is_multisite() && is_network_admin() ) {
				$num   = 0;
				$blogs = Activation::get_network_ids();
				foreach ( $blogs as $k => $blog_id ) {
					if ( $k > 3 ) {
						self::$_hide_more = true;
						break;
					}

					switch_to_blog( $blog_id );
					$num += (int) $this->db_count( $type, true );
					restore_current_blog();
				}
				return $num;
			}
		}

		global $wpdb;

		switch ( $type ) {
			case 'revision':
            $rev_max = (int) $this->conf( Base::O_DB_OPTM_REVISIONS_MAX );
            $rev_age = (int) $this->conf( Base::O_DB_OPTM_REVISIONS_AGE );

            $sql_add = '';
            if ( $rev_age ) {
					$sql_add = $wpdb->prepare( ' AND post_modified < DATE_SUB( NOW(), INTERVAL %d DAY ) ', $rev_age );
				}

            $sql = "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_type = 'revision' $sql_add";
            if ( ! $rev_max ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
					return (int) $wpdb->get_var( $sql );
				}

            // Has count limit.
            $sql = "SELECT COUNT(*) - %d FROM `$wpdb->posts` WHERE post_type = 'revision' $sql_add GROUP BY post_parent HAVING COUNT(*) > %d";
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
            $res = (array) $wpdb->get_results( $wpdb->prepare( $sql, $rev_max, $rev_max ), ARRAY_N );

        Utility::compatibility();
				return array_sum( array_column( $res, 0 ) );

			case 'orphaned_post_meta':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->postmeta` a LEFT JOIN `$wpdb->posts` b ON b.ID=a.post_id WHERE b.ID IS NULL" );

			case 'auto_draft':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'auto-draft'" );

			case 'trash_post':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'trash'" );

			case 'spam_comment':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_approved = 'spam'" );

			case 'trash_comment':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_approved = 'trash'" );

			case 'trackback-pingback':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback'" );

			case 'expired_transient':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE %s AND option_value < %d",
						$wpdb->esc_like( '_transient_timeout_' ) . '%',
						time()
					)
				);

			case 'all_transients':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE %s",
						$wpdb->esc_like( '_transient_' ) . '%'
					)
				);

			case 'optimize_tables':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = %s AND ENGINE <> 'InnoDB' AND DATA_FREE > 0",
						DB_NAME
					)
				);
		}

		return '-';
	}

	/**
	 * Clean/Optimize WP tables.
	 *
	 * @since 1.2.1
	 * @since 3.0 changed to private
	 * @access private
	 * @param string $type Cleanup type.
	 * @return string Status message.
	 */
	private function _db_clean( $type ) {
		if ( 'all' === $type ) {
			foreach ( self::$types as $v ) {
				$this->_db_clean( $v );
			}
			return __( 'Clean all successfully.', 'litespeed-cache' );
		}

		global $wpdb;

		switch ( $type ) {
			case 'revision':
            $rev_max = (int) $this->conf( Base::O_DB_OPTM_REVISIONS_MAX );
            $rev_age = (int) $this->conf( Base::O_DB_OPTM_REVISIONS_AGE );

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

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql_add = $rev_age ? $wpdb->prepare( ' AND ' . $posts . '.post_modified < DATE_SUB( NOW(), INTERVAL %d DAY )', $rev_age ) : '';

				if ( ! $rev_max ) {
					$sql_where    = "$sql_where $sql_add";
					$sql_postmeta = $sql_postmeta_join( $posts );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "DELETE $postmeta FROM $sql_postmeta $sql_where" );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "DELETE FROM $posts $sql_where" );
				} else {
					// Has count limit.
					$sql = "
						SELECT COUNT(*) - %d
						AS del_max, post_parent
						FROM $posts
						WHERE post_type = 'revision'
						$sql_add
						GROUP BY post_parent
						HAVING COUNT(*) > %d
					";
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
					$res          = (array) $wpdb->get_results( $wpdb->prepare( $sql, $rev_max, $rev_max ) );
					$sql_where    = "
						$sql_where
						AND post_parent = %d
						ORDER BY ID
						LIMIT %d
					";
					$sql_postmeta = $sql_postmeta_join( "(SELECT ID FROM $posts $sql_where) AS $posts" );
					foreach ( $res as $v ) {
						$args = [ (int) $v->post_parent, (int) $v->del_max ];
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
						$wpdb->query( $wpdb->prepare( "DELETE $postmeta FROM $sql_postmeta", $args ) );
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
						$wpdb->query( $wpdb->prepare( "DELETE FROM $posts $sql_where", $args ) );
					}
				}

				return __( 'Clean post revisions successfully.', 'litespeed-cache' );

			case 'orphaned_post_meta':
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( "DELETE a FROM `$wpdb->postmeta` a LEFT JOIN `$wpdb->posts` b ON b.ID=a.post_id WHERE b.ID IS NULL" );
				return __( 'Clean orphaned post meta successfully.', 'litespeed-cache' );

			case 'auto_draft':
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( "DELETE FROM `$wpdb->posts` WHERE post_status = 'auto-draft'" );
				return __( 'Clean auto drafts successfully.', 'litespeed-cache' );

			case 'trash_post':
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->query( "DELETE FROM `$wpdb->posts` WHERE post_status = 'trash'" );
				return __( 'Clean trashed posts and pages successfully.', 'litespeed-cache' );

			case 'spam_comment':
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( "DELETE FROM `$wpdb->comments` WHERE comment_approved = 'spam'" );
				return __( 'Clean spam comments successfully.', 'litespeed-cache' );

			case 'trash_comment':
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( "DELETE FROM `$wpdb->comments` WHERE comment_approved = 'trash'" );
				return __( 'Clean trashed comments successfully.', 'litespeed-cache' );

			case 'trackback-pingback':
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( "DELETE FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback'" );
				return __( 'Clean trackbacks and pingbacks successfully.', 'litespeed-cache' );

			case 'expired_transient':
			$keys_to_delete = [];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$transients = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name FROM `$wpdb->options` WHERE option_name LIKE %s AND option_value < %d",
					$wpdb->esc_like( '_transient_timeout_' ) . '%',
					time()
				),
			);
			foreach ( $transients as $transient ) {
				$keys_to_delete[] = $transient->option_name;
				$keys_to_delete[] = str_replace( '_transient_timeout_', '_transient_', $transient->option_name );
			}

			if ( ! empty( $keys_to_delete ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $keys_to_delete ), '%s' )  );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"DELETE FROM `$wpdb->options` WHERE option_name IN ( $placeholders )",
						$keys_to_delete
					)
				);
			}
				return __( 'Clean expired transients successfully.', 'litespeed-cache' );

			case 'all_transients':
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `$wpdb->options` WHERE option_name LIKE %s",
					$wpdb->esc_like( '_transient_' ) . '%'
				)
			);
				return __( 'Clean all transients successfully.', 'litespeed-cache' );

			case 'optimize_tables':
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT table_name, DATA_FREE FROM information_schema.tables WHERE TABLE_SCHEMA = %s AND ENGINE <> 'InnoDB' AND DATA_FREE > 0",
					DB_NAME
				)
			);
			if ( $result ) {
				foreach ( $result as $row ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( 'OPTIMIZE TABLE ' . esc_sql( $row->table_name ) );
				}
			}
				return __( 'Optimized all tables.', 'litespeed-cache' );
		}
	}

	/**
	 * Get all MyISAM tables.
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function list_myisam() {
		global $wpdb;

		$like = $wpdb->esc_like( $wpdb->prefix ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT TABLE_NAME as table_name, ENGINE as engine
				 FROM information_schema.tables
				 WHERE TABLE_SCHEMA = %s AND ENGINE = 'myisam' AND TABLE_NAME LIKE %s",
				DB_NAME,
				$like
			)
		);
	}

	/**
	 * Convert tables to InnoDB.
	 *
	 * @since 3.0
	 * @access private
	 * @return void
	 */
	private function _conv_innodb() {
		global $wpdb;

		$tb_param = isset( $_GET['litespeed_tb'] ) ? sanitize_text_field( wp_unslash( $_GET['litespeed_tb'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $tb_param ) {
			Admin_Display::error( 'No table to convert or invalid nonce' );
			return;
		}

		$tb    = false;
		$list  = $this->list_myisam();
		$names = wp_list_pluck( $list, 'table_name' );

		if ( in_array( $tb_param, $names, true ) ) {
			$tb = $tb_param;
		}

		if ( ! $tb ) {
			Admin_Display::error( 'No existing table' );
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( 'ALTER TABLE ' . esc_sql( DB_NAME ) . '.' . esc_sql( $tb ) . ' ENGINE = InnoDB' );

		Debug2::debug( "[DB] Converted $tb to InnoDB" );

		$msg = __( 'Converted to InnoDB successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Count all autoload size.
	 *
	 * @since 3.0
	 * @access public
	 * @return object Summary with size, entries, and toplist.
	 */
	public function autoload_summary() {
		global $wpdb;

		$autoload_values = function_exists( 'wp_autoload_values_to_autoload' ) ? wp_autoload_values_to_autoload() : [ 'yes', 'on', 'auto-on', 'auto' ];
		$placeholders    = implode( ',', array_fill( 0, count( $autoload_values ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$summary = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(LENGTH(option_value)) AS autoload_size, COUNT(*) AS autload_entries
				 FROM `$wpdb->options`
				 WHERE autoload IN ($placeholders)",
				$autoload_values
			)
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$summary->autoload_toplist = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, LENGTH(option_value) AS option_value_length, autoload
				 FROM `$wpdb->options`
				 WHERE autoload IN ($placeholders)
				 ORDER BY option_value_length DESC
				 LIMIT 20",
				$autoload_values
			)
		);

		return $summary;
	}

	/**
	 * Handle all request actions from main cls.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_CONV_TB:
			$this->_conv_innodb();
				break;

			default:
				if ( 'all' === $type || in_array( $type, self::$types, true ) ) {
					if ( is_multisite() && is_network_admin() ) {
						$blogs = Activation::get_network_ids();
						foreach ( $blogs as $blog_id ) {
							switch_to_blog( $blog_id );
							$msg = $this->_db_clean( $type );
							restore_current_blog();
						}
					} else {
						$msg = $this->_db_clean( $type );
					}
					Admin_Display::success( $msg );
				}
				break;
		}

		Admin::redirect();
	}

	/**
	 * Clean DB via WP-CLI.
	 *
	 * @since 7.0
	 * @access public
	 * @param string $args Cleanup type.
	 * @return string|false
	 */
	public function handler_clean_db_cli( $args ) {
		if ( defined( 'WP_CLI' ) && constant('WP_CLI') ) {
			return $this->_db_clean( $args );
		}

		return false;
	}
}
