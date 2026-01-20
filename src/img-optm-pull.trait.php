<?php
/**
 * Image optimization pull trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

use WpOrg\Requests\Autoload;
use WpOrg\Requests\Requests;

defined( 'WPINC' ) || exit();

/**
 * Trait Img_Optm_Pull
 *
 * Handles image optimization pull and notification.
 */
trait Img_Optm_Pull {

	/**
	 * Cloud server notify Client img status changed
	 *
	 * @access public
	 * @return array Response array.
	 */
	public function notify_img() {
		// Interval validation to avoid hacking domain_key
		if ( ! empty( $this->_summary['notify_ts_err'] ) && time() - $this->_summary['notify_ts_err'] < 3 ) {
			return Cloud::err( 'too_often' );
		}

		$post_data = \json_decode( file_get_contents( 'php://input' ), true );
		if ( is_null( $post_data ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$post_data = $_POST;
		}

		global $wpdb;

		$notified_data = $post_data['data'];
		if ( empty( $notified_data ) || ! is_array( $notified_data ) ) {
			self::debug( '❌ notify exit: no notified data' );
			return Cloud::err( 'no notified data' );
		}

		if ( empty( $post_data['server'] ) || ( substr( $post_data['server'], -11 ) !== '.quic.cloud' && substr( $post_data['server'], -15 ) !== '.quicserver.com' ) ) {
			self::debug( 'notify exit: no/wrong server' );
			return Cloud::err( 'no/wrong server' );
		}

		if ( empty( $post_data['status'] ) ) {
			self::debug( 'notify missing status' );
			return Cloud::err( 'no status' );
		}

		$status = $post_data['status'];
		self::debug( 'notified status=' . $status );

		$last_log_pid = 0;

		if ( empty( $this->_summary['reduced'] ) ) {
			$this->_summary['reduced'] = 0;
		}

		if ( self::STATUS_NOTIFIED === $status ) {
			// Notified data format: [ img_optm_id => [ id=>, src_size=>, ori=>, ori_md5=>, ori_reduced=>, webp=>, webp_md5=>, webp_reduced=> ] ]
			$q =
				"SELECT a.*, b.meta_id as b_meta_id, b.meta_value AS b_optm_info
					FROM `$this->_table_img_optming` a
					LEFT JOIN `$wpdb->postmeta` b ON b.post_id = a.post_id AND b.meta_key = %s
					WHERE a.id IN ( " .
				implode( ',', array_fill( 0, count( $notified_data ), '%d' ) ) .
				' )';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$list                            = $wpdb->get_results( $wpdb->prepare( $q, array_merge( [ self::DB_SIZE ], array_keys( $notified_data ) ) ) );
			$ls_optm_size_row_exists_postids = [];
			foreach ( $list as $v ) {
				$json = $notified_data[ $v->id ];
				// self::debug('Notified data for [id] ' . $v->id, $json);

				$server = ! empty( $json['server'] ) ? $json['server'] : $post_data['server'];

				$server_info = [
					'server' => $server,
				];

				// Save server side ID to send taken notification after pulled
				$server_info['id'] = $json['id'];
				if ( ! empty( $json['file_id'] ) ) {
					$server_info['file_id'] = $json['file_id'];
				}

				// Optm info array
				$postmeta_info = [
					'ori_total'  => 0,
					'ori_saved'  => 0,
					'webp_total' => 0,
					'webp_saved' => 0,
					'avif_total' => 0,
					'avif_saved' => 0,
				];
				// Init postmeta_info for the first one
				if ( ! empty( $v->b_meta_id ) ) {
					foreach ( maybe_unserialize( $v->b_optm_info ) as $k2 => $v2 ) {
						$postmeta_info[ $k2 ] += $v2;
					}
				}

				if ( ! empty( $json['ori'] ) ) {
					$server_info['ori_md5'] = $json['ori_md5'];
					$server_info['ori']     = $json['ori'];

					// Append meta info
					$postmeta_info['ori_total'] += $json['src_size'];
					$postmeta_info['ori_saved'] += $json['ori_reduced']; // optimized image size info in img_optm tb will be updated when pull

					$this->_summary['reduced'] += $json['ori_reduced'];
				}

				if ( ! empty( $json['webp'] ) ) {
					$server_info['webp_md5'] = $json['webp_md5'];
					$server_info['webp']     = $json['webp'];

					// Append meta info
					$postmeta_info['webp_total'] += $json['src_size'];
					$postmeta_info['webp_saved'] += $json['webp_reduced'];

					$this->_summary['reduced'] += $json['webp_reduced'];
				}

				if ( ! empty( $json['avif'] ) ) {
					$server_info['avif_md5'] = $json['avif_md5'];
					$server_info['avif']     = $json['avif'];

					// Append meta info
					$postmeta_info['avif_total'] += $json['src_size'];
					$postmeta_info['avif_saved'] += $json['avif_reduced'];

					$this->_summary['reduced'] += $json['avif_reduced'];
				}

				// Update status and data in working table
				$q = "UPDATE `$this->_table_img_optming` SET optm_status = %d, server_info = %s WHERE id = %d ";
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( $wpdb->prepare( $q, [ $status, wp_json_encode( $server_info ), $v->id ] ) );

				// Update postmeta for optm summary
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				$postmeta_info = serialize( $postmeta_info );
				if ( empty( $v->b_meta_id ) && ! in_array( $v->post_id, $ls_optm_size_row_exists_postids, true ) ) {
					self::debug( 'New size info [pid] ' . $v->post_id );
					$q = "INSERT INTO `$wpdb->postmeta` ( post_id, meta_key, meta_value ) VALUES ( %d, %s, %s )";
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->query( $wpdb->prepare( $q, [ $v->post_id, self::DB_SIZE, $postmeta_info ] ) );
					$ls_optm_size_row_exists_postids[] = $v->post_id;
				} else {
					$q = "UPDATE `$wpdb->postmeta` SET meta_value = %s WHERE meta_id = %d ";
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->query( $wpdb->prepare( $q, [ $postmeta_info, $v->b_meta_id ] ) );
				}

				// write log
				$pid_log = $last_log_pid === $v->post_id ? '.' : $v->post_id;
				self::debug( 'notify_img [status] ' . $status . " \t\t[pid] " . $pid_log . " \t\t[id] " . $v->id );
				$last_log_pid = $v->post_id;
			}

			self::save_summary();

			// Mark need_pull tag for cron
			self::update_option( self::DB_NEED_PULL, self::STATUS_NOTIFIED );
		} else {
			// Other errors will directly remove the working records
			// Delete from working table
			$q = "DELETE FROM `$this->_table_img_optming` WHERE id IN ( " . implode( ',', array_fill( 0, count( $notified_data ), '%d' ) ) . ' ) ';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $wpdb->prepare( $q, $notified_data ) );
		}

		return Cloud::ok( [ 'count' => count( $notified_data ) ] );
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
		$tag = (int)self::get_option( self::DB_NEED_PULL );
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
		$timeout_limit = ini_get( 'max_execution_time' );
		$endts         = time() + $timeout_limit;

		self::debug( '' . ( $manual ? 'Manually' : 'Cron' ) . ' pull started [timeout: ' . $timeout_limit . 's]' );

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

		$rm_ori_bkup = $this->conf( self::O_IMG_OPTM_RM_BKUP );

		$total_pulled_ori  = 0;
		$total_pulled_webp = 0;
		$total_pulled_avif = 0;

		$server_list = [];

		try {
			// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			while ( $img_rows = $wpdb->get_results( $_q ) ) {
				self::debug( 'timeout left: ' . ( $endts - time() ) . 's' );
				if ( function_exists( 'set_time_limit' ) ) {
					$endts += 600;
					self::debug( 'Endtime extended to ' . gmdate( 'Ymd H:i:s', $endts ) );
					set_time_limit( 600 ); // This will be no more important as we use noabort now
				}
				// Disabled as we use noabort
				// if ($endts - time() < 10) {
				// self::debug("🚨 End loop due to timeout limit reached " . $timeout_limit . "s");
				// break;
				// }

				/**
				 * Update cron timestamp to avoid duplicated running
				 *
				 * @since  1.6.2
				 */
				$this->_update_cron_running();

				// Run requests in parallel
				$requests    = []; // store each request URL for Requests::request_multiple()
				$imgs_by_req = []; // store original request data so that we can reference it in the response
				$req_counter = 0;
				foreach ( $img_rows as $row_img ) {
					// request original image
					$server_info = \json_decode( $row_img->server_info, true );
					if ( ! empty( $server_info['ori'] ) ) {
						$image_url = $server_info['server'] . '/' . $server_info['ori'];
						self::debug( 'Queueing pull: ' . $image_url );
						$requests[ $req_counter ]      = [
							'url'  => $image_url,
							'type' => 'GET',
						];
						$imgs_by_req[ $req_counter++ ] = [
							'type' => 'ori',
							'data' => $row_img,
						];
					}

					// request webp image
					$webp_size = 0;
					if ( ! empty( $server_info['webp'] ) ) {
						$image_url = $server_info['server'] . '/' . $server_info['webp'];
						self::debug( 'Queueing pull WebP: ' . $image_url );
						$requests[ $req_counter ]      = [
							'url'  => $image_url,
							'type' => 'GET',
						];
						$imgs_by_req[ $req_counter++ ] = [
							'type' => 'webp',
							'data' => $row_img,
						];
					}

					// request avif image
					$avif_size = 0;
					if ( ! empty( $server_info['avif'] ) ) {
						$image_url = $server_info['server'] . '/' . $server_info['avif'];
						self::debug( 'Queueing pull AVIF: ' . $image_url );
						$requests[ $req_counter ]      = [
							'url'  => $image_url,
							'type' => 'GET',
						];
						$imgs_by_req[ $req_counter++ ] = [
							'type' => 'avif',
							'data' => $row_img,
						];
					}
				}
				self::debug( 'Loaded images count: ' . $req_counter );

				$complete_action = function ( $response, $req_count ) use ( $imgs_by_req, $rm_ori_bkup, &$total_pulled_ori, &$total_pulled_webp, &$total_pulled_avif, &$server_list ) {
					global $wpdb;
					$row_data = isset( $imgs_by_req[ $req_count ] ) ? $imgs_by_req[ $req_count ] : false;
					if ( false === $row_data ) {
						self::debug( '❌ failed to pull image: Request not found in lookup variable.' );
						return;
					}
					$row_type    = isset( $row_data['type'] ) ? $row_data['type'] : 'ori';
					$row_img     = $row_data['data'];
					$local_file  = $this->wp_upload_dir['basedir'] . '/' . $row_img->src;
					$server_info = \json_decode( $row_img->server_info, true );

					// Handle status_code 404/5xx too as its success=true
					if ( empty( $response->success ) || empty( $response->status_code ) || 200 !== $response->status_code ) {
						self::debug( '❌ Failed to pull optimized img: HTTP error [status_code] ' . ( empty( $response->status_code ) ? 'N/A' : $response->status_code ) );
						$this->_step_back_image( $row_img->id );

						$msg = __( 'Some optimized image file(s) has expired and was cleared.', 'litespeed-cache' );
						Admin_Display::error( $msg );
						return;
					}

					if ( 'webp' === $row_type ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
						file_put_contents( $local_file . '.webp', $response->body );

						if ( ! file_exists( $local_file . '.webp' ) || ! filesize( $local_file . '.webp' ) || md5_file( $local_file . '.webp' ) !== $server_info['webp_md5'] ) {
							self::debug( '❌ Failed to pull optimized webp img: file md5 mismatch, server md5: ' . $server_info['webp_md5'] );

							// Delete working table
							$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
							$wpdb->query( $wpdb->prepare( $q, $row_img->id ) );

							$msg = __( 'Pulled WebP image md5 does not match the notified WebP image md5.', 'litespeed-cache' );
							Admin_Display::error( $msg );
							return;
						}

						self::debug( 'Pulled optimized img WebP: ' . $local_file . '.webp' );

						$webp_size = filesize( $local_file . '.webp' );

						/**
						 * API for WebP
						 *
						 * @since 2.9.5
						 * @since  3.0 $row_img less elements (see above one)
						 * @see #751737  - API docs for WEBP generation
						 */
						do_action( 'litespeed_img_pull_webp', $row_img, $local_file . '.webp' );

						++$total_pulled_webp;
					} elseif ( 'avif' === $row_type ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
						file_put_contents( $local_file . '.avif', $response->body );

						if ( ! file_exists( $local_file . '.avif' ) || ! filesize( $local_file . '.avif' ) || md5_file( $local_file . '.avif' ) !== $server_info['avif_md5'] ) {
							self::debug( '❌ Failed to pull optimized avif img: file md5 mismatch, server md5: ' . $server_info['avif_md5'] );

							// Delete working table
							$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
							$wpdb->query( $wpdb->prepare( $q, $row_img->id ) );

							$msg = __( 'Pulled AVIF image md5 does not match the notified AVIF image md5.', 'litespeed-cache' );
							Admin_Display::error( $msg );
							return;
						}

						self::debug( 'Pulled optimized img AVIF: ' . $local_file . '.avif' );

						$avif_size = filesize( $local_file . '.avif' );

						/**
						 * API for AVIF
						 *
						 * @since 7.0
						 */
						do_action( 'litespeed_img_pull_avif', $row_img, $local_file . '.avif' );

						++$total_pulled_avif;
					} else {
						// "ori" image type
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
						file_put_contents( $local_file . '.tmp', $response->body );

						if ( ! file_exists( $local_file . '.tmp' ) || ! filesize( $local_file . '.tmp' ) || md5_file( $local_file . '.tmp' ) !== $server_info['ori_md5'] ) {
							self::debug(
								'❌ Failed to pull optimized img: file md5 mismatch [url] ' .
									$server_info['server'] .
									'/' .
									$server_info['ori'] .
									' [server_md5] ' .
									$server_info['ori_md5']
							);

							// Delete working table
							$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
							$wpdb->query( $wpdb->prepare( $q, $row_img->id ) );

							$msg = __( 'One or more pulled images does not match with the notified image md5', 'litespeed-cache' );
							Admin_Display::error( $msg );
							return;
						}

						// Backup ori img
						if ( ! $rm_ori_bkup ) {
							$extension = pathinfo( $local_file, PATHINFO_EXTENSION );
							$bk_file   = substr( $local_file, 0, -strlen( $extension ) ) . 'bk.' . $extension;
							// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
							file_exists( $local_file ) && rename( $local_file, $bk_file );
						}

						// Replace ori img
						// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
						rename( $local_file . '.tmp', $local_file );

						self::debug( 'Pulled optimized img: ' . $local_file );

						/**
						 * API Hook
						 *
						 * @since  2.9.5
						 * @since  3.0 $row_img has less elements now. Most useful ones are `post_id`/`src`
						 */
						do_action( 'litespeed_img_pull_ori', $row_img, $local_file );

						self::debug2( 'Remove _table_img_optming record [id] ' . $row_img->id );
					}

					// Delete working table
					$q = "DELETE FROM `$this->_table_img_optming` WHERE id = %d ";
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->query( $wpdb->prepare( $q, $row_img->id ) );

					// Save server_list to notify taken
					if ( empty( $server_list[ $server_info['server'] ] ) ) {
						$server_list[ $server_info['server'] ] = [];
					}

					$server_info_id                          = ! empty( $server_info['file_id'] ) ? $server_info['file_id'] : $server_info['id'];
					$server_list[ $server_info['server'] ][] = $server_info_id;

					++$total_pulled_ori;
				};

				$force_wp_remote_get = defined( 'LITESPEED_FORCE_WP_REMOTE_GET' ) && constant( 'LITESPEED_FORCE_WP_REMOTE_GET' );
				if ( ! $force_wp_remote_get && class_exists( '\WpOrg\Requests\Requests' ) && class_exists( '\WpOrg\Requests\Autoload' ) ) {
					// Make sure Requests can load internal classes.
					Autoload::register();

					// Run pull requests in parallel
					Requests::request_multiple( $requests, [
						'timeout'         => 60,
						'connect_timeout' => 60,
						'complete'        => $complete_action,
						'verify'          => false,
						'verifyname'      => false,
					] );
				} else {
					foreach ( $requests as $cnt => $req ) {
						$wp_response      = wp_safe_remote_get( $req['url'], [ 'timeout' => 60 ] );
						$request_response = [
							'success'     => false,
							'status_code' => 0,
							'body'        => null,
							'sslverify'   => false,
						];
						if ( is_wp_error( $wp_response ) ) {
							$error_message = $wp_response->get_error_message();
							self::debug( '❌ failed to pull image: ' . $error_message );
						} else {
							$request_response['success']     = true;
							$request_response['status_code'] = $wp_response['response']['code'];
							$request_response['body']        = $wp_response['body'];
						}
						self::debug( 'response code [code] ' . $wp_response['response']['code'] . ' [url] ' . $req['url'] );

						$request_response = (object) $request_response;

						$complete_action( $request_response, $cnt );
					}
				}
				self::debug( 'Current batch pull finished' );
			}
		} catch ( \Exception $e ) {
			Admin_Display::error( 'Image pull process failure: ' . $e->getMessage() );
		}

		// Notify IAPI images taken
		foreach ( $server_list as $server => $img_list ) {
			$data = [
				'action' => self::CLOUD_ACTION_TAKEN,
				'list'   => $img_list,
				'server' => $server,
			];
			// TODO: improve this so we do not call once per server, but just once and then filter on the server side
			Cloud::post( Cloud::SVC_IMG_OPTM, $data );
		}

		if ( empty( $this->_summary['img_taken'] ) ) {
			$this->_summary['img_taken'] = 0;
		}
		$this->_summary['img_taken'] += $total_pulled_ori + $total_pulled_webp + $total_pulled_avif;
		self::save_summary();

		// Manually running needs to roll back timestamp for next running
		if ( $manual ) {
			$this->_update_cron_running( true );
		}

		// $msg = sprintf(__('Pulled %d image(s)', 'litespeed-cache'), $total_pulled_ori + $total_pulled_webp);
		// Admin_Display::success($msg);

		// Check if there is still task in queue
		$q = "SELECT * FROM `$this->_table_img_optming` WHERE optm_status = %d LIMIT 1";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$to_be_continued = $wpdb->get_row( $wpdb->prepare( $q, self::STATUS_NOTIFIED ) );
		if ( $to_be_continued ) {
			self::debug( 'Task in queue, to be continued...' );
			return;
			// return Router::self_redirect(Router::ACTION_IMG_OPTM, self::TYPE_PULL);
		}

		// If all pulled, update tag to done
		self::debug( 'Marked pull status to all pulled' );
		self::update_option( self::DB_NEED_PULL, self::STATUS_PULLED );
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

		self::debug( 'Push image back to new status [id] ' . $id );

		// Reset the image to gathered status
		$q = "UPDATE `$this->_table_img_optming` SET optm_status = %d WHERE id = %d ";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $q, [ self::STATUS_RAW, $id ] ) );
	}
}
