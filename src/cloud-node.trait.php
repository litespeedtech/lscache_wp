<?php
/**
 * Cloud node detection trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Cloud_Node
 *
 * Handles cloud node detection and management.
 */
trait Cloud_Node {

	/**
	 * Clear all existing cloud nodes for future reconnect
	 *
	 * @since 3.0
	 * @access public
	 */
	public function clear_cloud() {
		foreach ( self::$services as $service ) {
			if ( isset( $this->_summary[ 'server.' . $service ] ) ) {
				unset( $this->_summary[ 'server.' . $service ] );
			}
			if ( isset( $this->_summary[ 'server_date.' . $service ] ) ) {
				unset( $this->_summary[ 'server_date.' . $service ] );
			}
		}
		self::save_summary();

		self::debug( 'Cleared all local service node caches' );
	}

	/**
	 * Ping clouds to find the fastest node
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $service Service.
	 * @param bool   $force   Force redetect.
	 * @return string|false
	 */
	public function detect_cloud( $service, $force = false ) {
		if ( in_array( $service, self::$center_svc_set, true ) ) {
			return $this->_cloud_server;
		}

		if ( in_array( $service, self::$wp_svc_set, true ) ) {
			return $this->_cloud_server_wp;
		}

		// Check if the stored server needs to be refreshed
		if ( ! $force ) {
			if (
				! empty( $this->_summary[ 'server.' . $service ] ) &&
				! empty( $this->_summary[ 'server_date.' . $service ] ) &&
				(int) $this->_summary[ 'server_date.' . $service ] > time() - 86400 * self::TTL_NODE
			) {
				$server = $this->_summary[ 'server.' . $service ];
				if ( false === strpos( $this->_cloud_server, 'preview.' ) && false === strpos( $server, 'preview.' ) ) {
					return $server;
				}
				if ( false !== strpos( $this->_cloud_server, 'preview.' ) && false !== strpos( $server, 'preview.' ) ) {
					return $server;
				}
			}
		}

		if ( ! $service || ! in_array( $service, self::$services, true ) ) {
			$msg = __( 'Cloud Error', 'litespeed-cache' ) . ': ' . $service;
			Admin_Display::error( $msg );
			return false;
		}

		// Send request to Quic Online Service
		$json = $this->_post( self::SVC_D_NODES, [ 'svc' => $this->_maybe_queue( $service ) ] );

		// Check if get list correctly
		if ( empty( $json['list'] ) || ! is_array( $json['list'] ) ) {
			self::debug( 'request cloud list failed: ', $json );

			if ( $json ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . wp_json_encode( $json );
				Admin_Display::error( $msg );
			}

			return false;
		}

		// Ping closest cloud
		$valid_clouds = false;
		if ( ! empty( $json['list_preferred'] ) ) {
			$valid_clouds = $this->_get_closest_nodes( $json['list_preferred'], $service );
		}
		if ( ! $valid_clouds ) {
			$valid_clouds = $this->_get_closest_nodes( $json['list'], $service );
		}
		if ( ! $valid_clouds ) {
			return false;
		}

		// Check server load
		if ( in_array( $service, self::$services_load_check, true ) ) {
			// TODO
			$valid_cloud_loads = [];
			foreach ( $valid_clouds as $v ) {
				$response = wp_safe_remote_get( $v, [ 'timeout' => 5 ] );
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					self::debug( 'failed to do load checker: ' . $error_message );
					continue;
				}

				$curr_load = \json_decode( $response['body'], true );
				if ( ! empty( $curr_load['_res'] ) && 'ok' === $curr_load['_res'] && isset( $curr_load['load'] ) ) {
					$valid_cloud_loads[ $v ] = $curr_load['load'];
				}
			}

			if ( ! $valid_cloud_loads ) {
				$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . __( 'No available Cloud Node after checked server load.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}

			self::debug( 'Closest nodes list after load check', $valid_cloud_loads );

			$qualified_list = array_keys( $valid_cloud_loads, min( $valid_cloud_loads ), true );
		} else {
			$qualified_list = $valid_clouds;
		}

		$closest = $qualified_list[ array_rand( $qualified_list ) ];

		self::debug( 'Chose node: ' . $closest );

		// store data into option locally
		$this->_summary[ 'server.' . $service ]      = $closest;
		$this->_summary[ 'server_date.' . $service ] = time();
		self::save_summary();

		return $this->_summary[ 'server.' . $service ];
	}

	/**
	 * Ping to choose the closest nodes
	 *
	 * @since 7.0
	 *
	 * @param array  $nodes_list    Node list.
	 * @param string $service Service.
	 * @return array|false
	 */
	private function _get_closest_nodes( $nodes_list, $service ) {
		$speed_list = [];
		foreach ( $nodes_list as $v ) {
			// Exclude possible failed 503 nodes
			if ( ! empty( $this->_summary['disabled_node'] ) && ! empty( $this->_summary['disabled_node'][ $v ] ) && time() - (int) $this->_summary['disabled_node'][ $v ] < 86400 ) {
				continue;
			}
			$speed_list[ $v ] = Utility::ping( $v );
		}

		if ( ! $speed_list ) {
			self::debug( 'nodes are in 503 failed nodes' );
			return false;
		}

		$min = min( $speed_list );

		if ( 99999 === (int) $min ) {
			self::debug( 'failed to ping all clouds' );
			return false;
		}

		// Random pick same time range ip (230ms 250ms)
		$range_len    = strlen( $min );
		$range_num    = substr( $min, 0, 1 );
		$valid_clouds = [];
		foreach ( $speed_list as $node => $speed ) {
			if ( strlen( $speed ) === $range_len && substr( $speed, 0, 1 ) === $range_num ) {
				$valid_clouds[] = $node;
			} elseif ( $speed < $min * 4 ) { // Append the lower speed ones
				$valid_clouds[] = $node;
			}
		}

		if ( ! $valid_clouds ) {
			$msg = __( 'Cloud Error', 'litespeed-cache' ) . ": [Service] $service [Info] " . __( 'No available Cloud Node.', 'litespeed-cache' );
			Admin_Display::error( $msg );
			return false;
		}

		self::debug( 'Closest nodes list', $valid_clouds );
		return $valid_clouds;
	}

	/**
	 * May need to convert to queue service
	 *
	 * @param string $service Service.
	 * @return string
	 */
	private function _maybe_queue( $service ) {
		if ( in_array( $service, self::$_queue_svc_set, true ) ) {
			return self::SVC_QUEUE;
		}
		return $service;
	}
}
