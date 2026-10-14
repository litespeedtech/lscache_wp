<?php
/**
 * Image optimization notification handling.
 *
 * @package LiteSpeed
 * @since 7.9.1
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Img_Optm_Notify
 */
trait Img_Optm_Notify {

	/**
	 * Normalize a positive working-row ID.
	 *
	 * @since 7.9.1
	 *
	 * @param mixed $row_id Working-row ID.
	 * @return int|false
	 */
	private function _normalize_notify_row_id( $row_id ) {
		if ( ! is_int( $row_id ) && ! is_string( $row_id ) ) {
			return false;
		}

		$value = (string) $row_id;
		return preg_match( '/^[1-9]\d*$/D', $value ) && (string) (int) $row_id === $value ? (int) $row_id : false;
	}

	/**
	 * Normalize one successful image result.
	 *
	 * @since 7.9.1
	 *
	 * @param array  $data           Result data.
	 * @param string $default_server Default server origin.
	 * @return array|false
	 */
	private function _normalize_notify_result( $data, $default_server ) {
		$server_info = $this->_normalize_server_info( $data, $default_server );
		$src_size    = is_array( $data ) && array_key_exists( 'src_size', $data ) && is_int( $data['src_size'] ) && 0 <= $data['src_size'] ? $data['src_size'] : false;
		if ( ! $server_info || false === $src_size ) {
			return false;
		}

		$result = [
			'server_info' => $server_info,
			'src_size'    => $src_size,
		];
		foreach ( [ 'ori', 'webp', 'avif' ] as $type ) {
			if ( empty( $server_info[ $type ] ) ) {
				continue;
			}

			$key     = $type . '_reduced';
			$reduced = array_key_exists( $key, $data ) && is_int( $data[ $key ] ) && 0 <= $data[ $key ] ? $data[ $key ] : false;
			if ( false === $reduced || $reduced > $src_size ) {
				return false;
			}
			$result[ $key ] = $reduced;
		}

		return $result;
	}

	/**
	 * Normalize the callback data map or row-ID list.
	 *
	 * @since 7.9.1
	 *
	 * @param array  $data           Callback data.
	 * @param int    $status         Callback status.
	 * @param string $default_server Default server origin.
	 * @return array|false
	 */
	private function _normalize_notify_data( $data, $status, $default_server ) {
		if ( empty( $data ) || ! is_array( $data ) || Cloud::SIGN_MAX_ITEMS < count( $data ) ) {
			return false;
		}

		$normalized = [];
		if ( self::STATUS_NOTIFIED === $status ) {
			foreach ( $data as $row_id => $result ) {
				$row_id = $this->_normalize_notify_row_id( $row_id );
				$result = $this->_normalize_notify_result( $result, $default_server );
				if ( false === $row_id || false === $result || isset( $normalized[ $row_id ] ) ) {
					return false;
				}
				$normalized[ $row_id ] = $result;
			}
			return $normalized;
		}

		if ( array_keys( $data ) !== range( 0, count( $data ) - 1 ) ) {
			return false;
		}
		foreach ( $data as $row_id ) {
			$row_id = $this->_normalize_notify_row_id( $row_id );
			if ( false === $row_id || isset( $normalized[ $row_id ] ) ) {
				return false;
			}
			$normalized[ $row_id ] = true;
		}
		return $normalized;
	}

	/**
	 * Load the size summary for one attachment.
	 *
	 * @since 7.9.1
	 *
	 * @param int $post_id Attachment ID.
	 * @return array
	 */
	private function _load_notify_meta( $post_id ) {
		$info   = [
			'ori_total'  => 0,
			'ori_saved'  => 0,
			'webp_total' => 0,
			'webp_saved' => 0,
			'avif_total' => 0,
			'avif_saved' => 0,
		];
		$stored = get_post_meta( $post_id, self::DB_SIZE, true );
		if ( is_array( $stored ) ) {
			foreach ( $stored as $key => $value ) {
				if ( is_numeric( $value ) ) {
					$info[ $key ] = array_key_exists( $key, $info ) ? max( 0, (int) $value ) : (int) $value;
				} elseif ( ! array_key_exists( $key, $info ) ) {
					$info[ $key ] = $value;
				}
			}
		}

		return [
			'info'    => $info,
			'reduced' => 0,
		];
	}

	/**
	 * Persist successful results.
	 *
	 * @since 7.9.1
	 *
	 * @param array $results Normalized results keyed by working-row ID.
	 * @return array
	 */
	private function _persist_notify_results( $results ) {
		global $wpdb;
		$ids = array_keys( $results );
		$q   = "SELECT * FROM `$this->_table_img_optming` WHERE id IN ( " . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ' )';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$list = $wpdb->get_results( $wpdb->prepare( $q, $ids ) );
		if ( ! is_array( $list ) ) {
			return Cloud::err( 'failed to load image rows' );
		}

		$rows = [];
		foreach ( $list as $row ) {
			if ( self::STATUS_RAW === (int) $row->optm_status ) {
				continue;
			}
			$rows[ (int) $row->id ] = $row;
		}
		foreach ( $rows as $row ) {
			if ( self::STATUS_REQUESTED !== (int) $row->optm_status && self::STATUS_NOTIFIED !== (int) $row->optm_status ) {
				return Cloud::err( 'image row is not awaiting a result' );
			}
		}

		$pending       = [];
		$total_reduced = 0;
		$write_error   = '';
		foreach ( $rows as $row_id => $row ) {
			$result      = $results[ $row_id ];
			$server_info = $result['server_info'];
			$old_status  = (int) $row->optm_status;
			if ( self::STATUS_NOTIFIED === $old_status ) {
				$q = "UPDATE `$this->_table_img_optming` SET server_info = %s WHERE id = %d AND optm_status = %d";
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
				if ( false === $wpdb->query( $wpdb->prepare( $q, [ wp_json_encode( $server_info ), $row_id, self::STATUS_NOTIFIED ] ) ) ) {
					$write_error = 'failed to refresh image result';
				}
				continue;
			}

			$q = "UPDATE `$this->_table_img_optming` SET optm_status = %d, server_info = %s WHERE id = %d AND optm_status = %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$claimed = $wpdb->query( $wpdb->prepare( $q, [ self::STATUS_NOTIFIED, wp_json_encode( $server_info ), $row_id, $old_status ] ) );
			if ( 1 !== (int) $claimed ) {
				$write_error = 'image row changed';
				continue;
			}

			$post_id = (int) $row->post_id;
			if ( ! isset( $pending[ $post_id ] ) ) {
				$pending[ $post_id ] = $this->_load_notify_meta( $post_id );
			}

			foreach ( [ 'ori', 'webp', 'avif' ] as $type ) {
				if ( empty( $server_info[ $type ] ) ) {
					continue;
				}
				$pending[ $post_id ]['info'][ $type . '_total' ] += $result['src_size'];
				$pending[ $post_id ]['info'][ $type . '_saved' ] += $result[ $type . '_reduced' ];
				$pending[ $post_id ]['reduced']                  += $result[ $type . '_reduced' ];
			}
		}

		foreach ( $pending as $post_id => $meta ) {
			update_post_meta( $post_id, self::DB_SIZE, $meta['info'] );
			if ( get_post_meta( $post_id, self::DB_SIZE, true ) !== $meta['info'] ) {
				$write_error = 'failed to update image summary';
				continue;
			}
			$total_reduced += $meta['reduced'];
		}

		if ( $total_reduced ) {
			self::reload_summary();
			$this->_summary['reduced'] = ! empty( $this->_summary['reduced'] ) ? (int) $this->_summary['reduced'] + $total_reduced : $total_reduced;
			self::save_summary();
		}
		if ( $rows ) {
			self::update_option( self::DB_NEED_PULL, self::STATUS_NOTIFIED );
		}

		return $write_error ? Cloud::err( $write_error ) : Cloud::ok( [ 'count' => count( $rows ) ] );
	}

	/**
	 * Delete terminally failed working rows.
	 *
	 * @since 7.9.1
	 *
	 * @param array $ids Row IDs keyed by ID.
	 * @return array
	 */
	private function _delete_notify_rows( $ids ) {
		global $wpdb;
		$ids = array_keys( $ids );
		$q   = "DELETE FROM `$this->_table_img_optming` WHERE id IN ( " . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ' ) AND optm_status = %d';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( $q, array_merge( $ids, [ self::STATUS_REQUESTED ] ) ) );
		return false === $deleted ? Cloud::err( 'failed to delete working rows' ) : Cloud::ok( [ 'count' => count( $ids ) ] );
	}

	/**
	 * Handle a verified Cloud image notification.
	 *
	 * @since 7.9.1
	 *
	 * @param string|null $raw_body Verified raw JSON body.
	 * @return array
	 */
	public function notify_img( $raw_body = null ) {
		$payload = is_string( $raw_body ) ? json_decode( $raw_body, true, 32 ) : false;
		if ( ! is_array( $payload ) || ! isset( $payload['status'] ) || ! is_int( $payload['status'] ) ) {
			return Cloud::err( 'invalid data' );
		}

		$terminal_statuses = [ self::STATUS_FAILED, self::STATUS_MISS, self::STATUS_ERR_FETCH, self::STATUS_ERR_404, self::STATUS_ERR_OPTM, self::STATUS_XMETA, self::STATUS_ERR ];
		$status            = $payload['status'];
		if ( self::STATUS_NOTIFIED !== $status && ! in_array( $status, $terminal_statuses, true ) ) {
			return Cloud::err( 'invalid status' );
		}

		$default_server = isset( $payload['server'] ) ? Img::normalize_cloud_url( $payload['server'], true ) : false;
		$data           = isset( $payload['data'] ) ? $this->_normalize_notify_data( $payload['data'], $status, $default_server ) : false;
		if ( ! $default_server || false === $data ) {
			return Cloud::err( 'invalid image result' );
		}
		if ( ! $this->__data->tb_exist( 'img_optming' ) ) {
			return Cloud::ok( [ 'count' => 0 ] );
		}

		return self::STATUS_NOTIFIED === $status ? $this->_persist_notify_results( $data ) : $this->_delete_notify_rows( $data );
	}
}
