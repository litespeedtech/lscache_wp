<?php
/**
 * Image optimization pull trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Img_Optm_Pull
 *
 * Handles verified image downloads and publication.
 */
trait Img_Optm_Pull {
	/**
	 * Normalize download fields stored in a working queue row.
	 *
	 * @since 7.9.1
	 *
	 * @param array  $data           Notification or stored server data.
	 * @param string $default_server Default notification server origin.
	 * @return array|false
	 */
	private function _normalize_server_info( $data, $default_server = '' ) {
		if ( ! is_array( $data ) || empty( $data['id'] ) || ( ! is_int( $data['id'] ) && ! is_string( $data['id'] ) ) ) {
			return false;
		}
		$id = (string) $data['id'];
		if ( strlen( $id ) > 255 || preg_match( '/[\x00-\x20\x7f]/', $id ) ) {
			return false;
		}

		$server = ! empty( $data['server'] ) ? $data['server'] : $default_server;
		$server = Img::normalize_cloud_url( $server, true );
		if ( ! $server ) {
			return false;
		}

		$server_info = [
			'server' => $server,
			'id'     => $id,
		];
		if ( ! empty( $data['file_id'] ) && ( is_int( $data['file_id'] ) || is_string( $data['file_id'] ) ) ) {
			$file_id = (string) $data['file_id'];
			if ( strlen( $file_id ) > 255 || preg_match( '/[\x00-\x20\x7f]/', $file_id ) ) {
				return false;
			}
			$server_info['file_id'] = $file_id;
		}

		$has_file = false;
		foreach ( [ 'ori', 'webp', 'avif' ] as $type ) {
			if ( empty( $data[ $type ] ) ) {
				continue;
			}

			$path    = Img::normalize_cloud_path( $data[ $type ] );
			$md5_key = $type . '_md5';
			if ( ! $path || empty( $data[ $md5_key ] ) || ! is_string( $data[ $md5_key ] ) || ! preg_match( '/^[a-f0-9]{32}$/i', $data[ $md5_key ] ) ) {
				return false;
			}

			$server_info[ $type ]    = $path;
			$server_info[ $md5_key ] = strtolower( $data[ $md5_key ] );

			$has_file = true;
		}

		return $has_file ? $server_info : false;
	}

	/**
	 * Resolve a queued image through the shared WordPress image policy.
	 *
	 * @since 7.9.1
	 *
	 * @param object $row Working row.
	 * @return array|false `[ resolved path, bound root ]`, or false.
	 */
	private function _local_pull_file( $row ) {
		if ( ! isset( $row->src, $row->post_id ) || ! is_string( $row->src ) || '' === $row->src || false !== strpos( $row->src, "\0" ) ) {
			return false;
		}

		$src = ltrim( wp_normalize_path( $row->src ), '/' );
		if ( '' === $src || preg_match( '#(^|/)\.{1,2}(/|$)#', $src ) ) {
			return false;
		}

		$file = trailingslashit( $this->wp_upload_dir['basedir'] ) . $src;
		return Img::local_file( apply_filters( 'litespeed_realpath', $file ) );
	}

	/**
	 * Cron start async req
	 *
	 * @since 5.5
	 */
	public static function start_async_cron() {
		Task::async_call( 'imgoptm' );
	}

	/**
	 * Manually start async req
	 *
	 * @since 5.5
	 */
	public static function start_async() {
		Task::async_call( 'imgoptm_force' );

		$msg = __( 'Started async image optimization request', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Check if need to pull or not
	 *
	 * @since 7.2
	 * @return bool True if need to pull.
	 */
	public static function need_pull() {
		$tag = (int) self::get_option( self::DB_NEED_PULL );
		if ( ! $tag || self::STATUS_NOTIFIED !== $tag ) {
			return false;
		}
		return true;
	}

	/**
	 * Ajax req handler
	 *
	 * @since 5.5
	 * @param bool $force Whether to force pull.
	 */
	public static function async_handler( $force = false ) {
		self::debug( '------------async-------------start_async_handler' );

		if ( ! self::need_pull() ) {
			self::debug( '❌ no need pull' );
			return;
		}

		if ( defined( 'LITESPEED_IMG_OPTM_PULL_CRON' ) && ! constant( 'LITESPEED_IMG_OPTM_PULL_CRON' ) ) {
			self::debug( 'Cron disabled [define] LITESPEED_IMG_OPTM_PULL_CRON' );
			return;
		}

		self::cls()->pull( $force );
	}

	/**
	 * Calculate pull threads
	 *
	 * @since  5.8
	 * @access private
	 * @return int Number of images per request.
	 */
	private function _calc_pull_threads() {
		global $wpdb;

		if ( defined( 'LITESPEED_IMG_OPTM_PULL_THREADS' ) ) {
			return constant( 'LITESPEED_IMG_OPTM_PULL_THREADS' );
		}

		// Tune number of images per request based on number of images waiting and cloud packages
		$imgs_per_req = 1; // base 1, ramp up to ~50 max

		// Ramp up the request rate based on how many images are waiting
		$c = "SELECT count(id) FROM `$this->_table_img_optming` WHERE optm_status = %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$_c = $wpdb->prepare( $c, [ self::STATUS_NOTIFIED ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$images_waiting = $wpdb->get_var( $_c );
		if ( $images_waiting && $images_waiting > 0 ) {
			$imgs_per_req = ceil( $images_waiting / 1000 ); // ie. download 5/request if 5000 images are waiting
		}

		// Cap the request rate at 50 images per request
		$imgs_per_req = min( 50, $imgs_per_req );

		self::debug( 'Pulling images at rate: ' . $imgs_per_req . ' Images per request.' );

		return $imgs_per_req;
	}

	/**
	 * Pull optimized img
	 *
	 * @since  1.6
	 * @access public
	 * @param bool $manual Whether this is a manual pull.
	 */
	public function pull( $manual = false ) {
		global $wpdb;
		self::debug( ( $manual ? 'Manual' : 'Cron' ) . ' image pull started' );
		if ( ! $this->__data->tb_exist( 'img_optming' ) ) {
			return;
		}

		if ( $this->cron_running() ) {
			self::debug( 'Pull cron is running' );

			$msg = __( 'Pull Cron is running', 'litespeed-cache' );
			Admin_Display::note( $msg );
			return;
		}

		$this->_summary['last_pulled']         = time();
		$this->_summary['last_pulled_by_cron'] = ! $manual;
		self::save_summary();

		$imgs_per_req = $this->_calc_pull_threads();
		$q            = "SELECT * FROM `$this->_table_img_optming` WHERE optm_status = %d ORDER BY id LIMIT %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$_q = $wpdb->prepare( $q, [ self::STATUS_NOTIFIED, $imgs_per_req ] );

		$rm_ori_bkup = (bool) $this->conf( self::O_IMG_OPTM_RM_BKUP );
		$pulled      = [
			'ori'  => 0,
			'webp' => 0,
			'avif' => 0,
		];
		$taken       = [];
		$stop_pull   = false;
		try {
			// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			while ( $img_rows = $wpdb->get_results( $_q ) ) {
				if ( function_exists( 'set_time_limit' ) ) {
					set_time_limit( 600 );
				}
				$this->_update_cron_running();
				foreach ( $img_rows as $row_img ) {
					$result = $this->_pull_notified_row( $row_img, $rm_ori_bkup, $pulled, $taken );
					if ( 'stop' === $result ) {
						$stop_pull = true;
						break;
					}
				}
				if ( $stop_pull ) {
					break;
				}
			}
		} catch ( \Throwable $e ) {
			Admin_Display::error( 'Image pull process failure: ' . $e->getMessage() );
		}

		$total_pulled = array_sum( $pulled );
		if ( $total_pulled ) {
			$this->_summary['img_taken'] = ! empty( $this->_summary['img_taken'] ) ? (int) $this->_summary['img_taken'] + $total_pulled : $total_pulled;
			self::save_summary();
		}
		$this->_notify_taken_images( $taken );

		if ( $manual ) {
			$this->_update_cron_running( true );
		}

		$q = "SELECT id FROM `$this->_table_img_optming` WHERE optm_status = %d LIMIT 1";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$to_be_continued = $wpdb->get_row( $wpdb->prepare( $q, self::STATUS_NOTIFIED ) );
		if ( $to_be_continued ) {
			self::update_option( self::DB_NEED_PULL, self::STATUS_NOTIFIED );
			self::debug( 'Task in queue, to be continued...' );
			return;
		}

		self::debug( 'Marked pull status to all pulled' );
		self::update_option( self::DB_NEED_PULL, self::STATUS_PULLED );
	}

	/**
	 * Pull and publish all artifacts owned by one working row.
	 *
	 * @since 7.9.1
	 *
	 * @param object $row_img     Working row.
	 * @param bool   $rm_backup  Whether original backups are disabled.
	 * @param array  $pulled     Artifact counters.
	 * @param array  $taken      Artifact IDs grouped by server for best-effort notification.
	 * @return string `done`, `continue`, or `stop`.
	 */
	private function _pull_notified_row( $row_img, $rm_backup, &$pulled, &$taken ) {
		$server_info = $this->_normalize_server_info( json_decode( $row_img->server_info, true ) );
		$local       = $this->_local_pull_file( $row_img );
		if ( ! $server_info || ! $local ) {
			// Destroyed, not stepped back: STATUS_RAW would re-request optimization every cron round, forever.
			self::debugErr( 'Destroying invalid image pull row [id] ' . $row_img->id );
			return $this->_delete_pulled_image( $row_img->id ) ? 'continue' : 'stop';
		}

		list( $local_file, $root ) = $local;
		// The bound root is re-checked after the download by every staging and publishing step below.
		$staged = [];
		foreach ( [ 'webp', 'avif', 'ori' ] as $type ) {
			if ( empty( $server_info[ $type ] ) ) {
				continue;
			}
			$url    = $server_info['server'] . '/' . $server_info[ $type ];
			$target = 'ori' === $type ? $local_file : $local_file . '.' . $type;
			$file   = Img::fetch( $url, $target, $server_info[ $type . '_md5' ], 'md5', $type, $root );
			if ( ! is_wp_error( $file ) ) {
				$staged[ $type ] = $file;
				continue;
			}

			$this->_delete_staged_images( $staged );
			$err = $file->get_error_code();
			if ( Img::E_NET === $err ) {
				self::debugErr( 'Image pull transport failed [host] ' . wp_parse_url( $url, PHP_URL_HOST ) );
				$this->_defer_pulled_image( 'transport' );
				return 'stop';
			}

			if ( Img::E_HTTP === $err ) {
				$status = (int) $file->get_error_data();
				if ( 408 === $status || 429 === $status || 500 <= $status ) {
					self::debugErr( 'Image pull server unavailable [status] ' . $status . ' [host] ' . wp_parse_url( $url, PHP_URL_HOST ) );
					$this->_defer_pulled_image( 'server' );
					return 'stop';
				}
				Admin_Display::error( __( 'Some optimized image file(s) has expired and was cleared.', 'litespeed-cache' ) );
				return $this->_step_back_image( $row_img->id ) ? 'continue' : 'stop';
			}

			if ( Img::E_DATA === $err ) {
				Admin_Display::error( __( 'A pulled image did not match its verified image data.', 'litespeed-cache' ) );
				return $this->_delete_pulled_image( $row_img->id ) ? 'continue' : 'stop';
			}

			$this->_defer_pulled_image();
			return 'stop';
		}

		foreach ( $staged as $type => $file ) {
			$target = 'ori' === $type ? $local_file : $local_file . '.' . $type;
			$saved  = Img::publish( $file, $target, $root, 'ori' === $type && ! $rm_backup );
			unset( $staged[ $type ] );
			if ( ! $saved ) {
				$this->_delete_staged_images( $staged );
				$this->_defer_pulled_image();
				return 'stop';
			}
		}

		if ( ! $this->_delete_pulled_image( $row_img->id ) ) {
			return 'stop';
		}
		if ( empty( $taken[ $server_info['server'] ] ) ) {
			$taken[ $server_info['server'] ] = [];
		}
		$taken[ $server_info['server'] ][] = ! empty( $server_info['file_id'] ) ? $server_info['file_id'] : $server_info['id'];

		// Literal hook names: these are documented public API and must stay greppable.
		$hooks = [
			'ori'  => 'litespeed_img_pull_ori',
			'webp' => 'litespeed_img_pull_webp',
			'avif' => 'litespeed_img_pull_avif',
		];
		foreach ( $hooks as $type => $hook ) {
			if ( empty( $server_info[ $type ] ) ) {
				continue;
			}
			$target = 'ori' === $type ? $local_file : $local_file . '.' . $type;
			do_action( $hook, $row_img, $target );
			++$pulled[ $type ];
		}

		return 'done';
	}

	/**
	 * Delete any remaining staged files.
	 *
	 * @since 7.9.1
	 *
	 * @param array $staged Staged paths.
	 * @return void
	 */
	private function _delete_staged_images( $staged ) {
		foreach ( $staged as $file ) {
			if ( is_string( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	/**
	 * Report a retryable failure. The row stays NOTIFIED for a later pull.
	 *
	 * @since 7.9.1
	 *
	 * @param string $cause Failure cause: `transport`, `server`, or `save`.
	 * @return void
	 */
	private function _defer_pulled_image( $cause = 'save' ) {
		if ( 'transport' === $cause ) {
			$message = __( 'Failed to download the optimized image. Image pulling will retry later.', 'litespeed-cache' );
		} elseif ( 'server' === $cause ) {
			$message = __( 'The optimized image server is temporarily unavailable. Image pulling will retry later.', 'litespeed-cache' );
		} else {
			$message = __( 'Failed to save the pulled optimized image.', 'litespeed-cache' );
		}
		Admin_Display::error( $message );
	}

	/**
	 * Send best-effort taken notifications grouped by image server.
	 *
	 * @since 7.9.1
	 *
	 * @param array $taken Artifact IDs grouped by server.
	 * @return void
	 */
	private function _notify_taken_images( $taken ) {
		foreach ( $taken as $server => $ids ) {
			Cloud::post(
				Cloud::SVC_IMG_OPTM,
				[
					'action' => self::CLOUD_ACTION_TAKEN,
					'list'   => $ids,
					'server' => $server,
				]
			);
		}
	}

	/**
	 * Delete one notified working row.
	 *
	 * @since 7.9.1
	 *
	 * @param int $id Working-row ID.
	 * @return bool
	 */
	private function _delete_pulled_image( $id ) {
		global $wpdb;
		$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d AND optm_status = %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return 1 === (int) $wpdb->query( $wpdb->prepare( $q, [ $id, self::STATUS_NOTIFIED ] ) );
	}

	/**
	 * Push image back to previous status
	 *
	 * @since  3.0
	 * @access private
	 * @param int $id The image ID.
	 */
	private function _step_back_image( $id ) {
		global $wpdb;

		$q = "UPDATE `$this->_table_img_optming` SET optm_status = %d WHERE id = %d AND optm_status = %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return 1 === (int) $wpdb->query( $wpdb->prepare( $q, [ self::STATUS_RAW, $id, self::STATUS_NOTIFIED ] ) );
	}
}
