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
	 * Clean up all unfinished queue locally and to Cloud server
	 *
	 * @since 2.1.2
	 * @access public
	 */
	public function clean() {
		global $wpdb;

		// Reset img_optm table's queue
		if ( $this->__data->tb_exist( 'img_optming' ) ) {
			// Get min post id to mark
			$q = "SELECT MIN(post_id) FROM `$this->_table_img_optming`";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$min_pid = $wpdb->get_var( $q ) - 1;
			if ( $this->_summary['next_post_id'] > $min_pid ) {
				$this->_summary['next_post_id'] = $min_pid;
				self::save_summary();
			}

			$q = "DELETE FROM `$this->_table_img_optming`";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $q );
		}

		$msg = __( 'Cleaned up unfinished data successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
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

		$this->clean();

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
			$this->_destroy_optm_file( $meta_value, true );
			if ( ! empty( $meta_value['sizes'] ) ) {
				array_map( [ $this, '_destroy_optm_file' ], $meta_value['sizes'] );
			}
		}

		self::debug( 'batch switched images total: ' . $i );

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

		// Delete postmeta info
		$q = "DELETE FROM `$wpdb->postmeta` WHERE meta_key = %s";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $q, self::DB_SIZE ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $q, self::DB_SET ) );

		// Delete img_optm table
		$this->__data->tb_del( 'img_optm' );
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
			$this->__media->del( $short_file_path, $this->tmp_pid );
			$this->__media->rename( $bk_file, $short_file_path, $this->tmp_pid );
		}
		$this->__media->info( $bk_optm_file, $this->tmp_pid ) && $this->__media->del( $bk_optm_file, $this->tmp_pid );
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

		// Prepare post_ids to inquery gathered images
		$pid_set      = [];
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

			$pid_set[] = $v->post_id;
		}

		// Build gathered images
		$q = "SELECT src, post_id FROM `$this->_table_img_optm` WHERE post_id IN (" . implode( ',', array_fill( 0, count( $pid_set ), '%d' ) ) . ')';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $wpdb->prepare( $q, $pid_set ) );
		foreach ( $list as $v ) {
			$this->_existed_src_list[] = $v->post_id . '.' . $v->src;
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
		if ( defined( 'LITESPEED_CLI' ) || wp_doing_cron() ) {
			$offset = 0;
			while ( 'done' !== $offset ) {
				Admin_Display::info( "Starting switch to $type [offset] $offset" );
				$offset = $this->_batch_switch( $type, $offset );
			}
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$offset = ! empty( $_GET['litespeed_i'] ) ? absint( wp_unslash( $_GET['litespeed_i'] ) ) : 0;

			$new_offset = $this->_batch_switch( $type, $offset );
			if ( 'done' !== $new_offset ) {
				return Router::self_redirect( Router::ACTION_IMG_OPTM, $type );
			}
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
	 * @return int|string Next offset or 'done'.
	 */
	private function _batch_switch( $type, $offset ) {
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
			$this->_switch_bk_file( $meta_value, true );
			if ( ! empty( $meta_value['sizes'] ) ) {
				array_map( [ $this, '_switch_bk_file' ], $meta_value['sizes'] );
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

		// self::debug('_switch_bk_file ' . $bk_file . ' [type] ' . $this->tmp_type);
		// switch to ori
		if ( self::TYPE_BATCH_SWITCH_ORI === $this->tmp_type || 'orig' === $this->tmp_type ) {
			// self::debug('switch to orig ' . $bk_file);
			if ( ! $this->__media->info( $bk_file, $this->tmp_pid ) ) {
				return;
			}
			$this->__media->rename( $local_filename . '.' . $extension, $bk_optm_file, $this->tmp_pid );
			$this->__media->rename( $bk_file, $local_filename . '.' . $extension, $this->tmp_pid );
		} elseif ( self::TYPE_BATCH_SWITCH_OPTM === $this->tmp_type || 'optm' === $this->tmp_type ) {
			// switch to optm
			// self::debug('switch to optm ' . $bk_file);
			if ( ! $this->__media->info( $bk_optm_file, $this->tmp_pid ) ) {
				return;
			}
			$this->__media->rename( $local_filename . '.' . $extension, $bk_file, $this->tmp_pid );
			$this->__media->rename( $bk_optm_file, $local_filename . '.' . $extension, $this->tmp_pid );
		}
	}

	/**
	 * Switch image between original one and optimized one
	 *
	 * @since 1.6.2
	 * @access private
	 * @param string $type The switch type (webpXXX, avifXXX, or origXXX where XXX is the post ID).
	 */
	private function _switch_optm_file( $type ) {
		Admin_Display::success( __( 'Switched to optimized file successfully.', 'litespeed-cache' ) );
		return;

		// phpcs:disable Squiz.PHP.NonExecutableCode
		global $wpdb;

		$pid         = substr( $type, 4 );
		$switch_type = substr( $type, 0, 4 );

		$q = "SELECT src,post_id FROM `$this->_table_img_optm` WHERE post_id = %d AND optm_status = %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $wpdb->prepare( $q, [ $pid, self::STATUS_PULLED ] ) );

		$msg = 'Unknown Msg';

		foreach ( $list as $v ) {
			// to switch webp file
			if ( 'webp' === $switch_type ) {
				if ( $this->__media->info( $v->src . '.webp', $v->post_id ) ) {
					$this->__media->rename( $v->src . '.webp', $v->src . '.optm.webp', $v->post_id );
					self::debug( 'Disabled WebP: ' . $v->src );

					$msg = __( 'Disabled WebP file successfully.', 'litespeed-cache' );
				} elseif ( $this->__media->info( $v->src . '.optm.webp', $v->post_id ) ) {
					$this->__media->rename( $v->src . '.optm.webp', $v->src . '.webp', $v->post_id );
					self::debug( 'Enable WebP: ' . $v->src );

					$msg = __( 'Enabled WebP file successfully.', 'litespeed-cache' );
				}
			} elseif ( 'avif' === $switch_type ) {
				// to switch avif file
				if ( $this->__media->info( $v->src . '.avif', $v->post_id ) ) {
					$this->__media->rename( $v->src . '.avif', $v->src . '.optm.avif', $v->post_id );
					self::debug( 'Disabled AVIF: ' . $v->src );

					$msg = __( 'Disabled AVIF file successfully.', 'litespeed-cache' );
				} elseif ( $this->__media->info( $v->src . '.optm.avif', $v->post_id ) ) {
					$this->__media->rename( $v->src . '.optm.avif', $v->src . '.avif', $v->post_id );
					self::debug( 'Enable AVIF: ' . $v->src );

					$msg = __( 'Enabled AVIF file successfully.', 'litespeed-cache' );
				}
			} else {
				// to switch original file
				$extension      = pathinfo( $v->src, PATHINFO_EXTENSION );
				$local_filename = substr( $v->src, 0, -strlen( $extension ) - 1 );
				$bk_file        = $local_filename . '.bk.' . $extension;
				$bk_optm_file   = $local_filename . '.bk.optm.' . $extension;

				// revert ori back
				if ( $this->__media->info( $bk_file, $v->post_id ) ) {
					$this->__media->rename( $v->src, $bk_optm_file, $v->post_id );
					$this->__media->rename( $bk_file, $v->src, $v->post_id );
					self::debug( 'Restore original img: ' . $bk_file );

					$msg = __( 'Restored original file successfully.', 'litespeed-cache' );
				} elseif ( $this->__media->info( $bk_optm_file, $v->post_id ) ) {
					$this->__media->rename( $v->src, $bk_file, $v->post_id );
					$this->__media->rename( $bk_optm_file, $v->src, $v->post_id );
					self::debug( 'Switch to optm img: ' . $v->src );

					$msg = __( 'Switched to optimized file successfully.', 'litespeed-cache' );
				}
			}
		}

		Admin_Display::success( $msg );
		// phpcs:enable Squiz.PHP.NonExecutableCode
	}

	/**
	 * Delete one optm data and recover original file
	 *
	 * @since 2.4.2
	 * @access public
	 * @param int $post_id The post ID to reset.
	 */
	public function reset_row( $post_id ) {
		global $wpdb;

		if ( ! $post_id ) {
			return;
		}

		// Gathered image don't have DB_SIZE info yet
		// $size_meta = get_post_meta( $post_id, self::DB_SIZE, true );

		// if ( ! $size_meta ) {
		// return;
		// }

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
			$this->_destroy_optm_file( $meta_value, true );
			if ( ! empty( $meta_value['sizes'] ) ) {
				array_map( [ $this, '_destroy_optm_file' ], $meta_value['sizes'] );
			}
		}

		delete_post_meta( $post_id, self::DB_SIZE );
		delete_post_meta( $post_id, self::DB_SET );

		$msg = __( 'Reset the optimized data successfully.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Show an image's optm status
	 *
	 * @since  1.6.5
	 * @access public
	 * @return array Response data with image info.
	 */
	public function check_img() {
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$pid = isset( $_POST['data'] ) ? absint( wp_unslash( $_POST['data'] ) ) : 0;

		self::debug( 'Check image [ID] ' . $pid );

		$data = [];

		$data['img_count']    = $this->img_count();
		$data['optm_summary'] = self::get_summary();

		$data['_wp_attached_file']       = get_post_meta( $pid, '_wp_attached_file', true );
		$data['_wp_attachment_metadata'] = get_post_meta( $pid, '_wp_attachment_metadata', true );

		// Get img_optm data
		$q = "SELECT * FROM `$this->_table_img_optm` WHERE post_id = %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list     = $wpdb->get_results( $wpdb->prepare( $q, $pid ) );
		$img_data = [];
		if ( $list ) {
			foreach ( $list as $v ) {
				$img_data[] = [
					'id'          => $v->id,
					'optm_status' => $v->optm_status,
					'src'         => $v->src,
					'srcpath_md5' => $v->srcpath_md5,
					'src_md5'     => $v->src_md5,
					'server_info' => $v->server_info,
				];
			}
		}
		$data['img_data'] = $img_data;

		return [
			'_res' => 'ok',
			'data' => $data,
		];
	}
}
