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
		'auto_draft',
		'trash_post',
		'orphaned_post_meta',
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
	const TYPE_CONV_TB      = 'conv_innodb';
	const DELETE_BATCH_SIZE = 500;

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
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'auto-draft' AND post_date < DATE_SUB( NOW(), INTERVAL 7 DAY )" );

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
				$scope = $this->_table_scope();
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The scope is prepared in _table_scope().
						"SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = %s AND $scope AND ENGINE <> 'InnoDB' AND DATA_FREE > 0",
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
	 * @return string|false Status message or query failure.
	 */
	private function _db_clean( $type ) {
		if ( 'all' === $type ) {
			foreach ( self::$types as $v ) {
				if ( false === $this->_db_clean( $v ) ) {
					return false;
				}
			}
			return __( 'Clean all successfully.', 'litespeed-cache' );
		}

		global $wpdb;

		switch ( $type ) {
			case 'revision':
				$rev_max = (int) $this->conf( Base::O_DB_OPTM_REVISIONS_MAX );
				$rev_age = (int) $this->conf( Base::O_DB_OPTM_REVISIONS_AGE );

				$posts = "`$wpdb->posts`";

				$sql_where = "WHERE $posts.post_type = 'revision'";

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql_add = $rev_age ? $wpdb->prepare( ' AND ' . $posts . '.post_modified < DATE_SUB( NOW(), INTERVAL %d DAY )', $rev_age ) : '';

				if ( ! $rev_max ) {
					$sql_where = "$sql_where $sql_add";
					if ( ! $this->_delete_posts( $sql_where ) ) {
						return false;
					}
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
					$res = (array) $wpdb->get_results( $wpdb->prepare( $sql, $rev_max, $rev_max ) );
					if ( ! empty( $wpdb->last_error ) ) {
						return false;
					}
					$sql_where = "
						$sql_where
						$sql_add
						AND post_parent = %d
					";
					foreach ( $res as $v ) {
						$args = [ (int) $v->post_parent ];
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						if ( ! $this->_delete_posts( $wpdb->prepare( $sql_where, $args ), (int) $v->del_max ) ) {
							return false;
						}
					}
				}

				return __( 'Clean post revisions successfully.', 'litespeed-cache' );

			case 'orphaned_post_meta':
				if ( ! $this->_delete_orphaned_post_meta() ) {
					return false;
				}
				return __( 'Clean orphaned post meta successfully.', 'litespeed-cache' );

			case 'auto_draft':
				if ( ! $this->_delete_posts( "WHERE post_status = 'auto-draft' AND post_date < DATE_SUB( NOW(), INTERVAL 7 DAY )" ) ) {
					return false;
				}
				return __( 'Clean auto drafts successfully.', 'litespeed-cache' );

			case 'trash_post':
				if ( ! $this->_delete_posts( "WHERE post_status = 'trash'" ) ) {
					return false;
				}
				return __( 'Clean trashed posts and pages successfully.', 'litespeed-cache' );

			case 'spam_comment':
				if ( ! $this->_delete_comments( "comment_approved = 'spam'" ) ) {
					return false;
				}
				return __( 'Clean spam comments successfully.', 'litespeed-cache' );

			case 'trash_comment':
				if ( ! $this->_delete_comments( "comment_approved = 'trash'" ) ) {
					return false;
				}
				return __( 'Clean trashed comments successfully.', 'litespeed-cache' );

			case 'trackback-pingback':
				if ( ! $this->_delete_comments( "comment_type = 'trackback' OR comment_type = 'pingback'" ) ) {
					return false;
				}
				return __( 'Clean trackbacks and pingbacks successfully.', 'litespeed-cache' );

			case 'expired_transient':
			$keys_to_delete = [];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$transients = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name FROM `$wpdb->options` WHERE option_name LIKE %s AND option_value < %d",
					$wpdb->esc_like( '_transient_timeout_' ) . '%',
					time()
					)
				);
			foreach ( $transients as $transient ) {
					$keys_to_delete[] = $transient->option_name;
					$keys_to_delete[] = str_replace( '_transient_timeout_', '_transient_', $transient->option_name );
				}
				if ( ! empty( $wpdb->last_error ) ) {
					return false;
				}

				if ( ! empty( $keys_to_delete ) ) {
					$placeholders = implode( ',', array_fill( 0, count( $keys_to_delete ), '%s' )  );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$deleted = $wpdb->query(
						$wpdb->prepare(
							// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							"DELETE FROM `$wpdb->options` WHERE option_name IN ( $placeholders )",
							$keys_to_delete
						)
					);
					if ( false === $deleted ) {
						return false;
					}
				}
				return __( 'Clean expired transients successfully.', 'litespeed-cache' );

			case 'all_transients':
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$deleted = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `$wpdb->options` WHERE option_name LIKE %s",
						$wpdb->esc_like( '_transient_' ) . '%'
					)
				);
				if ( false === $deleted ) {
					return false;
				}
				return __( 'Clean all transients successfully.', 'litespeed-cache' );

			case 'optimize_tables':
				$scope   = $this->_table_scope();
				$skipped = false;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$result = (array) $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The scope is prepared in _table_scope().
						"SELECT table_name, DATA_FREE FROM information_schema.tables WHERE TABLE_SCHEMA = %s AND $scope AND ENGINE <> 'InnoDB' AND DATA_FREE > 0",
						DB_NAME
					)
				);
				if ( ! empty( $wpdb->last_error ) ) {
					return false;
				}
				if ( $result ) {
					foreach ( $result as $row ) {
						$table = str_replace( '`', '``', $row->table_name );
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$statuses = $wpdb->get_results( "OPTIMIZE TABLE `$table`" );
						if ( ! empty( $wpdb->last_error ) || ! $statuses ) {
							return false;
						}
						$completed = false;
						$has_note  = false;
						foreach ( $statuses as $status ) {
							$status = (array) $status;
							if ( isset( $status['Msg_type'] ) && 'error' === $status['Msg_type'] ) {
								return false;
							}
							$completed = $completed || ( isset( $status['Msg_type'] ) && 'status' === $status['Msg_type'] );
							$has_note  = $has_note || ( isset( $status['Msg_type'] ) && 'note' === $status['Msg_type'] );
						}
						if ( ! $completed ) {
							if ( ! $has_note ) {
								return false;
							}
							$skipped = true;
						}
					}
				}
				return $skipped ? __( 'Optimized supported tables. Unsupported tables were skipped.', 'litespeed-cache' ) : __( 'Optimized all tables.', 'litespeed-cache' );
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

		$scope = $this->_table_scope();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT TABLE_NAME as table_name, ENGINE as engine
				 FROM information_schema.tables
				 WHERE TABLE_SCHEMA = %s AND ENGINE = 'myisam' AND $scope", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prepared scope.
				DB_NAME
			)
		);
	}

	/**
	 * Select this site's tables without treating numeric subsite prefixes as main-site tables.
	 *
	 * @return string Prepared SQL condition.
	 */
	private function _table_scope() {
		global $wpdb;
		$scope = $wpdb->prepare( 'TABLE_NAME LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' );
		if ( is_multisite() && $wpdb->prefix === $wpdb->base_prefix ) {
			$subsites = [];
			foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $blog_id ) {
				if ( $wpdb->get_blog_prefix( $blog_id ) !== $wpdb->prefix ) {
					$subsites[] = (int) $blog_id;
				}
			}
			if ( $subsites ) {
				$scope .= $wpdb->prepare( ' AND TABLE_NAME NOT REGEXP %s', '^' . $wpdb->base_prefix . '(' . implode( '|', $subsites ) . ')_' );
			}
		}
		return $scope;
	}

	/**
	 * Delete orphaned metadata and invalidate its cache in bounded batches.
	 *
	 * @return bool Whether all deletions succeeded.
	 */
	private function _delete_orphaned_post_meta() {
		global $wpdb;
		// Include zero-parent metadata, which is also orphaned in WordPress's unsigned post ID column.
		$last_id = -1;
		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT a.post_id FROM `$wpdb->postmeta` a LEFT JOIN `$wpdb->posts` b ON b.ID=a.post_id WHERE b.ID IS NULL AND a.post_id > %d ORDER BY a.post_id LIMIT %d", $last_id, self::DELETE_BATCH_SIZE ) );
			if ( $wpdb->last_error ) {
				return false;
			}
			if ( ! $ids ) {
				return true;
			}
			$next_id = (int) end( $ids );
			if ( $next_id <= $last_id ) {
				return false;
			}
			$list = implode( ',', array_map( 'intval', $ids ) );
			// Recheck that the post is still absent, before deleting this batch's metadata.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$deleted = $wpdb->query( "DELETE a FROM `$wpdb->postmeta` a LEFT JOIN `$wpdb->posts` b ON b.ID=a.post_id WHERE b.ID IS NULL AND a.post_id IN ($list)" );
			foreach ( $ids as $id ) {
				wp_cache_delete( (int) $id, 'post_meta' );
			}
			if ( false === $deleted ) {
				return false;
			}
			$last_id = $next_id;
			$count   = count( $ids );
		} while ( self::DELETE_BATCH_SIZE === $count );
		return true;
	}

	/**
	 * Delete posts in bounded batches and invalidate their original snapshots.
	 *
	 * @param string $where Internal SQL predicate.
	 * @param int    $max_rows Maximum rows, or zero for all matches.
	 * @return bool
	 */
	private function _delete_posts( $where, $max_rows = 0 ) {
		global $wpdb;
		$last_id = 0;
		$count   = 0;
		while ( ! $max_rows || $count < $max_rows ) {
			$limit = $max_rows ? min( self::DELETE_BATCH_SIZE, $max_rows - $count ) : self::DELETE_BATCH_SIZE;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Internal prepared conditions.
			$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_type FROM `$wpdb->posts` $where AND ID > %d ORDER BY ID LIMIT %d", $last_id, $limit ) );
			if ( ! empty( $wpdb->last_error ) ) {
				return false;
			}
			if ( ! $posts ) {
				return true;
			}
			$next_id = (int) end( $posts )->ID;
			if ( $next_id <= $last_id ) {
				return false;
			}
			$ids = implode( ',', array_map( 'intval', wp_list_pluck( $posts, 'ID' ) ) );
			// Recheck the original condition while deleting both tables, so an edited draft cannot lose metadata.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Integer IDs and internal conditions.
			$deleted = $wpdb->query( "DELETE `$wpdb->posts`, `$wpdb->postmeta` FROM `$wpdb->posts` LEFT JOIN `$wpdb->postmeta` ON `$wpdb->postmeta`.post_id = `$wpdb->posts`.ID $where AND `$wpdb->posts`.ID IN ($ids)" );
			foreach ( $posts as $post ) {
				clean_post_cache( $post );
			}
			if ( false === $deleted ) {
				return false;
			}
			$last_id = $next_id;
			$count  += count( $posts );
			if ( count( $posts ) < $limit ) {
				return true;
			}
		}
		return true;
	}

	/**
	 * Delete comments in bounded pages and invalidate persistent comment/query caches.
	 *
	 * @param string $condition Internal SQL predicate.
	 * @return bool Whether all selected deletes succeeded.
	 */
	private function _delete_comments( $condition ) {
		global $wpdb;
		$last_id = 0;
		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Internal predicate and bounded cursor.
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM `$wpdb->comments` WHERE ($condition) AND comment_ID > %d ORDER BY comment_ID LIMIT %d", $last_id, self::DELETE_BATCH_SIZE ) );
			if ( ! empty( $wpdb->last_error ) ) {
				return false;
			}
			if ( ! $ids ) {
				return true;
			}
			$next_id = (int) end( $ids );
			if ( $next_id <= $last_id ) {
				return false;
			}
			$id_list = implode( ',', array_map( 'intval', $ids ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Recheck the internal predicate for selected integer IDs.
			$deleted = $wpdb->query( "DELETE FROM `$wpdb->comments` WHERE ($condition) AND comment_ID IN ($id_list)" );
			// clean_comment_cache() also advances WordPress's comments last_changed value.
			clean_comment_cache( $ids );
			if ( false === $deleted ) {
				return false;
			}
			$last_id = $next_id;
			$count   = count( $ids );
		} while ( self::DELETE_BATCH_SIZE === $count );
		return true;
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

		$db = str_replace( '`', '``', DB_NAME );
		$tb = str_replace( '`', '``', $tb );
		// Identifiers cannot use placeholders on the supported WordPress 6.0 minimum, so escape backticks before quoting them.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( false === $wpdb->query( 'ALTER TABLE `' . $db . '`.`' . $tb . '` ENGINE = InnoDB' ) ) {
			Admin_Display::error( __( 'Could not convert the table to InnoDB.', 'litespeed-cache' ) );
			return;
		}

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
		if ( ! is_object( $summary ) ) {
			Admin_Display::error( __( 'Could not read the autoload summary.', 'litespeed-cache' ) );
			return (object) [ 'autoload_size' => 0, 'autload_entries' => 0, 'autoload_toplist' => [] ];
		}

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
							if ( false === $msg ) {
								break;
							}
						}
					} else {
						$msg = $this->_db_clean( $type );
					}
					if ( false === $msg ) {
						Admin_Display::error( __( 'Database cleanup failed.', 'litespeed-cache' ) );
					} else {
						Admin_Display::success( $msg );
					}
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
