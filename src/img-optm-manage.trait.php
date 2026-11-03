<?php
/**
 * Image optimization management trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Img_Optm_Manage
 *
 * Handles image optimization management operations: clean, destroy, rescan, backup, batch_switch, etc.
 */
trait Img_Optm_Manage {

	/**
	 * Check if an attachment has optimization records
	 *
	 * @since 7.8
	 * @access public
	 * @param int        $post_id  The attachment post ID.
	 * @param array|null $metadata Optional. Attachment metadata for file path check.
	 * @return bool True if has optimization records.
	 */
	public function has_optm_record( $post_id, $metadata = null ) {
		global $wpdb;

		if ( ! $post_id ) {
			return false;
		}

		// Check post meta
		if ( get_post_meta( $post_id, self::DB_SIZE, true ) || get_post_meta( $post_id, self::DB_SET, true ) ) {
			return true;
		}

		// Check img_optming table
		if ( $this->__data->tb_exist( 'img_optming' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM `$this->_table_img_optming` WHERE post_id = %d", $post_id ) );
			if ( $count > 0 ) {
				return true;
			}
		}

		// Check if optimized files exist (.webp, .avif)
		if ( null === $metadata ) {
			$metadata = wp_get_attachment_metadata( $post_id );
		}
		if ( ! empty( $metadata['file'] ) ) {
			$short_file_path = $metadata['file'];
			if ( $this->__media->info( $short_file_path . '.webp', $post_id ) ) {
				return true;
			}
			if ( $this->__media->info( $short_file_path . '.avif', $post_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Clean up all unfinished queue locally and to Cloud server
	 *
	 * @since 2.1.2
	 * @access public
	 * @return bool Whether the unfinished rows were deleted.
	 */
	public function clean() {
		global $wpdb;

		// Reset img_optm table's queue
		if ( $this->__data->tb_exist( 'img_optming' ) ) {
			// Get min post id to mark
			$q = "SELECT MIN(post_id) FROM `$this->_table_img_optming`";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$min_pid = $wpdb->get_var( $q );
			// An empty table returns NULL, which must not rewind the cursor.
			if ( null !== $min_pid && $this->_summary['next_post_id'] > $min_pid - 1 ) {
				$this->_summary['next_post_id'] = $min_pid - 1;
				self::save_summary();
			}

			$q = "DELETE FROM `$this->_table_img_optming`";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $wpdb->query( $q ) ) {
				Admin_Display::error( __( 'Could not delete unfinished image data.', 'litespeed-cache' ) );
				return false;
			}
		}

		$msg = __( 'Cleaned up unfinished data successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
		return true;
	}

	/**
	 * Reset image counter
	 *
	 * @since 7.0
	 * @access private
	 */
	private function _reset_counter() {
		self::debug( 'reset image optm counter' );
		$this->_summary['next_post_id'] = 0;
		self::save_summary();

		if ( ! $this->clean() ) {
			return;
		}

		$msg = __( 'Reset image optimization counter successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Destroy all optimized images
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _destroy() {
		global $wpdb;

		self::debug( 'executing DESTROY process' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = ! empty( $_GET['litespeed_i'] ) ? absint( wp_unslash( $_GET['litespeed_i'] ) ) : 0;
		/**
		 * Limit images each time before redirection to fix Out of memory issue. #665465
		 *
		 * @since  2.9.8
		 */
		// Start deleting files
		$limit = apply_filters( 'litespeed_imgoptm_destroy_max_rows', 500 );

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$q = $wpdb->prepare( $img_q, [ $offset * $limit, $limit ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $q );
		$i    = 0;
		// Failures are counted across redirects so the final run knows whether the global cleanup is safe.
		$failed = $offset ? (int) self::get_summary( self::SUMMARY_DESTROY_FAILED ) : 0;
		foreach ( $list as $v ) {
			if ( ! $v->post_id ) {
				continue;
			}

			$meta_value = $this->_parse_wp_meta_value( $v );
			if ( ! $meta_value ) {
				continue;
			}

			++$i;

			$this->tmp_pid  = $v->post_id;
			$this->tmp_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';
			if ( ! $this->_apply_image_files( $meta_value, '_destroy_optm_file' ) ) {
				// Keep the failed attachment's metadata, backups and queue rows so a rerun can retry it.
				self::debug( 'destroy failed, skipped [pid] ' . $v->post_id );
				++$failed;
				continue;
			}
			// Retire the restored attachment now; leftover queue rows would let pull republish revoked files.
			if ( ! $this->_retire_row( $v->post_id ) ) {
				++$failed;
			}
		}

		self::debug( 'batch destroyed images total: ' . $i . ' [failed so far] ' . $failed );
		self::save_summary( [ self::SUMMARY_DESTROY_FAILED => $failed ] );

		++$offset;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$to_be_continued = $wpdb->get_row( $wpdb->prepare( $img_q, [ $offset * $limit, 1 ] ) );
		if ( $to_be_continued ) {
			// Check if post_id is beyond next_post_id
			self::debug( '[next_post_id] ' . $this->_summary['next_post_id'] . ' [cursor post id] ' . $to_be_continued->post_id );
			if ( $to_be_continued->post_id <= $this->_summary['next_post_id'] ) {
				self::debug( 'redirecting to next' );
				return Router::self_redirect( Router::ACTION_IMG_OPTM, self::TYPE_DESTROY );
			}
			self::debug( '🎊 Finished destroying' );
		}

		// The global cleanup would wipe the failed attachments' state, so it only runs after a fully successful pass.
		if ( $failed ) {
			self::save_summary( [ self::SUMMARY_DESTROY_FAILED => 0 ] );
			/* translators: %d: number of images that could not be restored. */
			Admin_Display::error( sprintf( __( '%d images could not be fully restored, so their optimization data was kept; see the debug log and run Destroy again.', 'litespeed-cache' ), $failed ) );
			return;
		}

		// Delete postmeta info
		$q = "DELETE FROM `$wpdb->postmeta` WHERE meta_key = %s";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $q, self::DB_SIZE ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $q, self::DB_SET ) );

		// Delete img_optming table
		$this->__data->tb_del( 'img_optming' );

		// Clear options table summary info
		self::delete_option( '_summary' );
		self::delete_option( self::DB_NEED_PULL );

		$msg = __( 'Destroy all optimization data successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Destroy optm file
	 *
	 * @since 3.0
	 * @access private
	 * @param array $meta_value The meta value array containing file info.
	 * @param bool  $is_ori_file Whether this is the original file.
	 */
	private function _destroy_optm_file( $meta_value, $is_ori_file = false ) {
		$short_file_path = $meta_value['file'];
		if ( ! $is_ori_file ) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}
		self::debug( 'deleting ' . $short_file_path );

		// del webp
		$this->__media->info( $short_file_path . '.webp', $this->tmp_pid ) && $this->__media->del( $short_file_path . '.webp', $this->tmp_pid );
		$this->__media->info( $short_file_path . '.optm.webp', $this->tmp_pid ) && $this->__media->del( $short_file_path . '.optm.webp', $this->tmp_pid );

		// del avif
		$this->__media->info( $short_file_path . '.avif', $this->tmp_pid ) && $this->__media->del( $short_file_path . '.avif', $this->tmp_pid );
		$this->__media->info( $short_file_path . '.optm.avif', $this->tmp_pid ) && $this->__media->del( $short_file_path . '.optm.avif', $this->tmp_pid );

		$extension      = pathinfo( $short_file_path, PATHINFO_EXTENSION );
		$local_filename = substr( $short_file_path, 0, -strlen( $extension ) - 1 );
		$bk_file        = $local_filename . '.bk.' . $extension;
		$bk_optm_file   = $local_filename . '.bk.optm.' . $extension;

		// del optimized ori
		if ( $this->__media->info( $bk_file, $this->tmp_pid ) ) {
			self::debug( 'deleting optim ori' );
			// Replace fires the delete + rename hooks for offload integrations, only after the local move succeeded.
			if ( ! $this->__media->replace( $bk_file, $short_file_path, $this->tmp_pid ) ) {
				return false;
			}
		}
		$this->__media->info( $bk_optm_file, $this->tmp_pid ) && $this->__media->del( $bk_optm_file, $this->tmp_pid );
		return true;
	}

	/**
	 * Rescan to find new generated images
	 *
	 * @since 1.6.7
	 * @access private
	 */
	private function _rescan() {
		// phpcs:ignore Squiz.PHP.NonExecutableCode
		exit( 'tobedone' );

		// phpcs:disable Squiz.PHP.NonExecutableCode
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = ! empty( $_GET['litespeed_i'] ) ? absint( wp_unslash( $_GET['litespeed_i'] ) ) : 0;
		$limit  = 500;

		self::debug( 'rescan images' );

		// Get images
		$q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a, `$wpdb->postmeta` b
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
				AND a.ID = b.post_id
				AND b.meta_key = '_wp_attachment_metadata'
			ORDER BY a.ID
			LIMIT %d, %d
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $wpdb->prepare( $q, $offset * $limit, $limit + 1 ) ); // last one is the seed for next batch

		if ( ! $list ) {
			$msg = __( 'Rescanned successfully.', 'litespeed-cache' );
			Admin_Display::success( $msg );

			self::debug( 'rescan bypass: no gathered image found' );
			return;
		}

		if ( count( $list ) === $limit + 1 ) {
			$to_be_continued = true;
			array_pop( $list ); // last one is the seed for next round, discard here.
		} else {
			$to_be_continued = false;
		}

		// Prepare scanned images
		$scanned_list = [];
		foreach ( $list as $v ) {
			$meta_value = $this->_parse_wp_meta_value( $v );
			if ( ! $meta_value ) {
				continue;
			}

			$scanned_list[] = [
				'pid'  => $v->post_id,
				'meta' => $meta_value,
			];
		}

		// Find new images
		foreach ( $scanned_list as $v ) {
			$meta_value = $v['meta'];
			// Parse all child src and put them into $this->_img_in_queue, missing ones to $this->_img_in_queue_missed
			$this->tmp_pid  = $v['pid'];
			$this->tmp_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';
			$this->_append_img_queue( $meta_value, true );
			if ( ! empty( $meta_value['sizes'] ) ) {
				foreach ( $meta_value['sizes'] as $img_size_name => $img_size ) {
					$this->_append_img_queue( $img_size, false, $img_size_name );
				}
			}
		}

		self::debug( 'rescanned [img] ' . count( $this->_img_in_queue ) );

		$count = count( $this->_img_in_queue );
		if ( $count > 0 ) {
			// Save to DB
			$this->_save_raw();
		}

		if ( $to_be_continued ) {
			return Router::self_redirect( Router::ACTION_IMG_OPTM, self::TYPE_RESCAN );
		}

		$msg = $count ? sprintf( __( 'Rescanned %d images successfully.', 'litespeed-cache' ), $count ) : __( 'Rescanned successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
		// phpcs:enable Squiz.PHP.NonExecutableCode
	}

	/**
	 * Calculate bkup original images storage
	 *
	 * @since 2.2.6
	 * @access private
	 */
	private function _calc_bkup() {
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = ! empty( $_GET['litespeed_i'] ) ? absint( wp_unslash( $_GET['litespeed_i'] ) ) : 0;
		$limit  = 500;

		if ( ! $offset ) {
			$this->_summary['bk_summary'] = [
				'date'  => time(),
				'count' => 0,
				'sum'   => 0,
			];
		}

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$q = $wpdb->prepare( $img_q, [ $offset * $limit, $limit ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $q );
		foreach ( $list as $v ) {
			if ( ! $v->post_id ) {
				continue;
			}

			$meta_value = $this->_parse_wp_meta_value( $v );
			if ( ! $meta_value ) {
				continue;
			}

			$this->tmp_pid  = $v->post_id;
			$this->tmp_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';
			$this->_get_bk_size( $meta_value, true );
			if ( ! empty( $meta_value['sizes'] ) ) {
				array_map( [ $this, '_get_bk_size' ], $meta_value['sizes'] );
			}
		}

		$this->_summary['bk_summary']['date'] = time();
		self::save_summary();

		self::debug( '_calc_bkup total: ' . $this->_summary['bk_summary']['count'] . ' [size] ' . $this->_summary['bk_summary']['sum'] );

		++$offset;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$to_be_continued = $wpdb->get_row( $wpdb->prepare( $img_q, [ $offset * $limit, 1 ] ) );

		if ( $to_be_continued ) {
			return Router::self_redirect( Router::ACTION_IMG_OPTM, self::TYPE_CALC_BKUP );
		}

		$msg = __( 'Calculated backups successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Calculate single size
	 *
	 * @since 2.2.6
	 * @access private
	 * @param array $meta_value The meta value array containing file info.
	 * @param bool  $is_ori_file Whether this is the original file.
	 */
	private function _get_bk_size( $meta_value, $is_ori_file = false ) {
		$short_file_path = $meta_value['file'];
		if ( ! $is_ori_file ) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		$extension      = pathinfo( $short_file_path, PATHINFO_EXTENSION );
		$local_filename = substr( $short_file_path, 0, -strlen( $extension ) - 1 );
		$bk_file        = $local_filename . '.bk.' . $extension;

		$img_info = $this->__media->info( $bk_file, $this->tmp_pid );
		if ( ! $img_info ) {
			return;
		}

		++$this->_summary['bk_summary']['count'];
		$this->_summary['bk_summary']['sum'] += $img_info['size'];
	}

	/**
	 * Delete bkup original images storage
	 *
	 * @since  2.5
	 * @access public
	 */
	public function rm_bkup() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'img_optming' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = ! empty( $_GET['litespeed_i'] ) ? absint( wp_unslash( $_GET['litespeed_i'] ) ) : 0;
		$limit  = 500;

		if ( empty( $this->_summary['rmbk_summary'] ) ) {
			$this->_summary['rmbk_summary'] = [
				'date'  => time(),
				'count' => 0,
				'sum'   => 0,
			];
		}

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$q = $wpdb->prepare( $img_q, [ $offset * $limit, $limit ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $q );
		foreach ( $list as $v ) {
			if ( ! $v->post_id ) {
				continue;
			}

			$meta_value = $this->_parse_wp_meta_value( $v );
			if ( ! $meta_value ) {
				continue;
			}

			$this->tmp_pid  = $v->post_id;
			$this->tmp_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';
			$this->_del_bk_file( $meta_value, true );
			if ( ! empty( $meta_value['sizes'] ) ) {
				array_map( [ $this, '_del_bk_file' ], $meta_value['sizes'] );
			}
		}

		$this->_summary['rmbk_summary']['date'] = time();
		self::save_summary();

		self::debug( 'rm_bkup total: ' . $this->_summary['rmbk_summary']['count'] . ' [size] ' . $this->_summary['rmbk_summary']['sum'] );

		++$offset;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$to_be_continued = $wpdb->get_row( $wpdb->prepare( $img_q, [ $offset * $limit, 1 ] ) );

		if ( $to_be_continued ) {
			return Router::self_redirect( Router::ACTION_IMG_OPTM, self::TYPE_RM_BKUP );
		}

		$msg = __( 'Removed backups successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Delete single file
	 *
	 * @since 2.5
	 * @access private
	 * @param array $meta_value The meta value array containing file info.
	 * @param bool  $is_ori_file Whether this is the original file.
	 */
	private function _del_bk_file( $meta_value, $is_ori_file = false ) {
		$short_file_path = $meta_value['file'];
		if ( ! $is_ori_file ) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		$extension      = pathinfo( $short_file_path, PATHINFO_EXTENSION );
		$local_filename = substr( $short_file_path, 0, -strlen( $extension ) - 1 );
		$bk_file        = $local_filename . '.bk.' . $extension;

		$img_info = $this->__media->info( $bk_file, $this->tmp_pid );
		if ( ! $img_info ) {
			return;
		}

		++$this->_summary['rmbk_summary']['count'];
		$this->_summary['rmbk_summary']['sum'] += $img_info['size'];

		$this->__media->del( $bk_file, $this->tmp_pid );
	}

	/**
	 * Count images
	 *
	 * @since 1.6
	 * @access public
	 * @return array Image count data.
	 */
	public function img_count() {
		global $wpdb;

		$q = "SELECT count(*)
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$groups_all = $wpdb->get_var( $q );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$groups_new = $wpdb->get_var( $q . ' AND ID>' . (int) $this->_summary['next_post_id'] . ' ORDER BY ID' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$groups_done = $wpdb->get_var( $q . ' AND ID<=' . (int) $this->_summary['next_post_id'] . ' ORDER BY ID' );

		$q = "SELECT b.post_id
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID DESC
			LIMIT 1
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$max_id = $wpdb->get_var( $q );

		$count_list = [
			'max_id'      => $max_id,
			'groups_all'  => $groups_all,
			'groups_new'  => $groups_new,
			'groups_done' => $groups_done,
		];

		// images count from work table
		if ( $this->__data->tb_exist( 'img_optming' ) ) {
			$q               = "SELECT COUNT(DISTINCT post_id),COUNT(*) FROM `$this->_table_img_optming` WHERE optm_status = %d";
			$groups_to_check = [ self::STATUS_RAW, self::STATUS_REQUESTED, self::STATUS_NOTIFIED, self::STATUS_ERR_FETCH ];
			foreach ( $groups_to_check as $v ) {
				$count_list[ 'img.' . $v ]   = 0;
				$count_list[ 'group.' . $v ] = 0;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
				list( $count_list[ 'group.' . $v ], $count_list[ 'img.' . $v ] ) = $wpdb->get_row( $wpdb->prepare( $q, $v ), ARRAY_N );
			}
		}

		return $count_list;
	}

	/**
	 * Check if fetch cron is running
	 *
	 * @since  1.6.2
	 * @access public
	 * @param bool $bool_res Whether to return boolean result.
	 * @return bool|array Boolean result or array with last run time and status.
	 */
	public function cron_running( $bool_res = true ) {
		$last_run = ! empty( $this->_summary['last_pull'] ) ? $this->_summary['last_pull'] : 0;

		$is_running = $last_run && time() - $last_run < 120;

		if ( $bool_res ) {
			return $is_running;
		}

		return [ $last_run, $is_running ];
	}

	/**
	 * Update fetch cron timestamp tag
	 *
	 * @since  1.6.2
	 * @access private
	 * @param bool $done Whether the cron job is done.
	 */
	private function _update_cron_running( $done = false ) {
		$this->_summary['last_pull'] = time();

		if ( $done ) {
			// Only update cron tag when its from the active running cron
			if ( $this->_cron_ran ) {
				// Rollback for next running
				$this->_summary['last_pull'] -= 120;
			} else {
				return;
			}
		}

		self::save_summary();

		$this->_cron_ran = true;
	}

	/**
	 * Batch switch images to ori/optm version
	 *
	 * @since  1.6.2
	 * @access public
	 * @param string $type The switch type (batch_switch_ori or batch_switch_optm).
	 */
	public function batch_switch( $type ) {
		$failed = 0;
		if ( defined( 'LITESPEED_CLI' ) || wp_doing_cron() ) {
			$offset = 0;
			while ( 'done' !== $offset ) {
				Admin_Display::info( "Starting switch to $type [offset] $offset" );
				$offset = $this->_batch_switch( $type, $offset, $failed );
			}
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$offset = ! empty( $_GET['litespeed_i'] ) ? absint( wp_unslash( $_GET['litespeed_i'] ) ) : 0;
			// Failures are counted across redirects; a fresh run (offset 0) starts from zero.
			$failed = $offset ? (int) self::get_summary( self::SUMMARY_SWITCH_FAILED ) : 0;

			$new_offset = $this->_batch_switch( $type, $offset, $failed );
			if ( 'done' !== $new_offset ) {
				self::save_summary( [ self::SUMMARY_SWITCH_FAILED => $failed ] );
				return Router::self_redirect( Router::ACTION_IMG_OPTM, $type );
			}
			if ( $offset ) {
				self::save_summary( [ self::SUMMARY_SWITCH_FAILED => 0 ] );
			}
		}

		if ( $failed ) {
			/* translators: %d: number of images that could not be switched. */
			Admin_Display::error( sprintf( __( '%d images could not be switched; see debug log.', 'litespeed-cache' ), $failed ) );
			return;
		}

		$msg = __( 'Switched images successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Switch images per offset
	 *
	 * @since  1.6.2
	 * @access private
	 * @param string $type   The switch type.
	 * @param int    $offset The current offset.
	 * @param int    $failed Running count of attachments that could not be switched; failed attachments are skipped.
	 * @return int|string Next offset or 'done'.
	 */
	private function _batch_switch( $type, $offset, &$failed ) {
		global $wpdb;
		$limit          = 500;
		$this->tmp_type = $type;

		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->posts` a
			LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.ID
			WHERE b.meta_key = '_wp_attachment_metadata'
				AND a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY a.ID
			LIMIT %d,%d
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$q = $wpdb->prepare( $img_q, [ $offset * $limit, $limit ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $q );
		$i    = 0;
		foreach ( $list as $v ) {
			if ( ! $v->post_id ) {
				continue;
			}

			$meta_value = $this->_parse_wp_meta_value( $v );
			if ( ! $meta_value ) {
				continue;
			}

			++$i;

			$this->tmp_pid  = $v->post_id;
			$this->tmp_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';
			if ( ! $this->_apply_image_files( $meta_value, '_switch_bk_file' ) ) {
				self::debug( 'switch failed, skipped [pid] ' . $v->post_id );
				++$failed;
				continue;
			}
		}

		self::debug( 'batch switched images total: ' . $i . ' [type] ' . $type );

		++$offset;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$to_be_continued = $wpdb->get_row( $wpdb->prepare( $img_q, [ $offset * $limit, 1 ] ) );
		if ( $to_be_continued ) {
			return $offset;
		}
		return 'done';
	}

	/**
	 * Switch backup file between original and optimized
	 *
	 * @since  1.6.2
	 * @access private
	 * @param array $meta_value The meta value array containing file info.
	 * @param bool  $is_ori_file Whether this is the original file.
	 */
	private function _switch_bk_file( $meta_value, $is_ori_file = false ) {
		$short_file_path = $meta_value['file'];
		if ( ! $is_ori_file ) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		$extension      = pathinfo( $short_file_path, PATHINFO_EXTENSION );
		$local_filename = substr( $short_file_path, 0, -strlen( $extension ) - 1 );
		$bk_file        = $local_filename . '.bk.' . $extension;
		$bk_optm_file   = $local_filename . '.bk.optm.' . $extension;

		if ( self::TYPE_BATCH_SWITCH_ORI === $this->tmp_type || 'orig' === $this->tmp_type ) {
			$source = $bk_file;
			$backup = $bk_optm_file;
		} elseif ( self::TYPE_BATCH_SWITCH_OPTM === $this->tmp_type || 'optm' === $this->tmp_type ) {
			$source = $bk_optm_file;
			$backup = $bk_file;
		} else {
			return;
		}
		if ( ! $this->__media->info( $source, $this->tmp_pid ) ) {
			return;
		}
		if ( ! $this->__media->rename( $short_file_path, $backup, $this->tmp_pid ) ) {
			return false;
		}
		if ( ! $this->__media->rename( $source, $short_file_path, $this->tmp_pid ) ) {
			// Restore the served file if publishing its replacement fails.
			$this->__media->rename( $backup, $short_file_path, $this->tmp_pid );
			return false;
		}
	}

	/**
	 * Apply an internal file operation to the main image, then all thumbnails.
	 *
	 * @param array  $meta_value Attachment metadata.
	 * @param string $method     Internal operation name.
	 * @return bool False on any failure; a failed main image skips the thumbnails.
	 */
	private function _apply_image_files( $meta_value, $method ) {
		if ( false === $this->$method( $meta_value, true ) ) {
			return false;
		}
		// Attempt every thumbnail even if an earlier thumbnail failed.
		return empty( $meta_value['sizes'] ) || ! in_array( false, array_map( [ $this, $method ], $meta_value['sizes'] ), true );
	}

	/**
	 * Switch image between original one and optimized one
	 *
	 * The toggle direction is derived from the main file's on-disk state (which is exactly what the Media Library row status reflects) and then applied uniformly to the main file and all sizes, mirroring the batch switch.
	 *
	 * @since 1.6.2
	 * @access private
	 * @param string $type The switch type: origXXX / webpXXX / avifXXX where XXX is the post ID.
	 */
	private function _switch_optm_file( $type ) {
		global $wpdb;

		$switch_type = substr( $type, 0, 4 ); // 'orig' | 'webp' | 'avif'
		$pid         = (int) substr( $type, 4 );
		if ( ! $pid ) {
			return;
		}

		self::debug( 'Switch single image [type] ' . $switch_type . ' [pid] ' . $pid );

		// Load the attachment meta (main file + thumbnail sizes), mirroring reset_row()
		$img_q = "SELECT b.post_id, b.meta_value FROM `$wpdb->postmeta` b WHERE b.post_id = %d AND b.meta_key = '_wp_attachment_metadata'";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$v          = $wpdb->get_row( $wpdb->prepare( $img_q, [ $pid ] ) );
		$meta_value = $this->_parse_wp_meta_value( $v );
		if ( ! $meta_value ) {
			return;
		}

		$this->tmp_pid  = $pid;
		$this->tmp_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';

		if ( 'orig' === $switch_type ) {
			// Detect direction from the main file's backups: bk = backup-of-original (currently optimized), bk.optm = backup-of-optimized (currently original)
			$extension      = pathinfo( $meta_value['file'], PATHINFO_EXTENSION );
			$local_filename = substr( $meta_value['file'], 0, -strlen( $extension ) - 1 );
			if ( $this->__media->info( $local_filename . '.bk.' . $extension, $this->tmp_pid ) ) {
				$this->tmp_type = 'orig';
				$msg            = __( 'Restored original file successfully.', 'litespeed-cache' );
			} elseif ( $this->__media->info( $local_filename . '.bk.optm.' . $extension, $this->tmp_pid ) ) {
				$this->tmp_type = 'optm';
				$msg            = __( 'Switched to optimized file successfully.', 'litespeed-cache' );
			} else {
				self::debug( 'Nothing to switch for original [pid] ' . $pid );
				return;
			}

			if ( ! $this->_apply_image_files( $meta_value, '_switch_bk_file' ) ) {
				return;
			}
		} else {
			// webp/avif: detect direction from the main file's sidecar. Served = <file>.<fmt>, parked/disabled = <file>.optm.<fmt>
			$this->_tmp_switch_format = $switch_type;
			$is_avif                  = 'avif' === $switch_type;
			if ( $this->__media->info( $meta_value['file'] . '.' . $switch_type, $this->tmp_pid ) ) {
				$this->tmp_type = 'orig'; // currently served -> disable
				$msg            = $is_avif ? __( 'Disabled AVIF file successfully.', 'litespeed-cache' ) : __( 'Disabled WebP file successfully.', 'litespeed-cache' );
			} elseif ( $this->__media->info( $meta_value['file'] . '.optm.' . $switch_type, $this->tmp_pid ) ) {
				$this->tmp_type = 'optm'; // currently parked -> enable
				$msg            = $is_avif ? __( 'Enabled AVIF file successfully.', 'litespeed-cache' ) : __( 'Enabled WebP file successfully.', 'litespeed-cache' );
			} else {
				self::debug( 'Nothing to switch for ' . $switch_type . ' [pid] ' . $pid );
				return;
			}

			if ( ! $this->_apply_image_files( $meta_value, '_switch_format_file' ) ) {
				return;
			}
		}

		Admin_Display::success( $msg );
	}

	/**
	 * Switch one file's next-gen sidecar between the served and parked (disabled) name, per `$this->_tmp_switch_format` and direction `$this->tmp_type`. Mirrors `_switch_bk_file()` for webp/avif.
	 *
	 * @since 7.9
	 * @access private
	 * @param array $meta_value  The meta value array containing file info.
	 * @param bool  $is_ori_file Whether this is the original (main) file.
	 */
	private function _switch_format_file( $meta_value, $is_ori_file = false ) {
		$short_file_path = $meta_value['file'];
		if ( ! $is_ori_file ) {
			$short_file_path = $this->tmp_path . $short_file_path;
		}

		$served_file = $short_file_path . '.' . $this->_tmp_switch_format;      // active, served to visitors
		$parked_file = $short_file_path . '.optm.' . $this->_tmp_switch_format; // disabled backup

		if ( 'optm' === $this->tmp_type ) {
			// enable: parked -> served
			if ( $this->__media->info( $parked_file, $this->tmp_pid ) ) {
				return $this->__media->rename( $parked_file, $served_file, $this->tmp_pid );
			}
		} elseif ( $this->__media->info( $served_file, $this->tmp_pid ) ) {
			// disable: served -> parked
			return $this->__media->rename( $served_file, $parked_file, $this->tmp_pid );
		}
	}

	/**
	 * Delete one optm data and recover original file
	 *
	 * @since 2.4.2
	 * @access public
	 * @param int  $post_id The post ID to reset.
	 * @param bool $silent  Whether to suppress success message. Default false.
	 */
	public function reset_row( $post_id, $silent = false ) {
		global $wpdb;

		if ( ! $post_id ) {
			return;
		}

		self::debug( '_reset_row [pid] ' . $post_id );

		// TODO: Load image sub files
		$img_q = "SELECT b.post_id, b.meta_value
			FROM `$wpdb->postmeta` b
			WHERE b.post_id =%d  AND b.meta_key = '_wp_attachment_metadata'";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$q = $wpdb->prepare( $img_q, [ $post_id ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$v = $wpdb->get_row( $q );

		$meta_value = $this->_parse_wp_meta_value( $v );
		if ( $meta_value ) {
			$this->tmp_pid  = $v->post_id;
			$this->tmp_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';
			if ( ! $this->_apply_image_files( $meta_value, '_destroy_optm_file' ) ) {
				return;
			}
		}

		if ( ! $this->_retire_row( $post_id ) ) {
			if ( ! $silent ) {
				Admin_Display::error( __( 'Failed to remove the image optimization queue records; see the debug log and try again.', 'litespeed-cache' ) );
			}
			return;
		}

		if ( ! $silent ) {
			$msg = __( 'Reset the optimized data successfully.', 'litespeed-cache' );
			Admin_Display::success( $msg );
		}
	}

	/**
	 * Remove one attachment's optimization metadata and queue rows after its files were restored.
	 *
	 * @since 7.9.2
	 * @access private
	 * @param int $post_id The attachment post ID.
	 * @return bool False when the queue rows could not be deleted; the metadata is then kept so a rerun retries.
	 */
	private function _retire_row( $post_id ) {
		global $wpdb;

		// Queue rows go first: a NOTIFIED row left behind would let pull republish the revoked optimization.
		if ( $this->__data->tb_exist( 'img_optming' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM `$this->_table_img_optming` WHERE post_id = %d", $post_id ) ) ) {
				self::debug( 'failed to delete img_optming rows, metadata kept [pid] ' . $post_id . ' [error] ' . $wpdb->last_error );
				return false;
			}
		}

		delete_post_meta( $post_id, self::DB_SIZE );
		delete_post_meta( $post_id, self::DB_SET );

		return true;
	}
}
