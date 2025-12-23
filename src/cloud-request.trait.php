<?php
/**
 * Cloud request trait
 *
 * @package LiteSpeed
 * @since 7.8
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Cloud_Request
 *
 * Handles HTTP requests to QUIC.cloud servers.
 */
trait Cloud_Request {

	/**
	 * Get data from QUIC cloud server
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $service Service.
	 * @param array  $data    Data.
	 * @return mixed
	 */
	public static function get( $service, $data = [] ) {
		$instance = self::cls();
		return $instance->_get( $service, $data );
	}

	/**
	 * Get data from QUIC cloud server (private)
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string     $service Service.
	 * @param array|bool $data    Data array or false to omit.
	 * @return mixed
	 */
	private function _get( $service, $data = false ) {
		$service_tag = $service;
		if ( ! empty( $data['action'] ) ) {
			$service_tag .= '-' . $data['action'];
		}

		$maybe_cloud = $this->_maybe_cloud( $service_tag );
		if ( ! $maybe_cloud || 'svc_hot' === $maybe_cloud ) {
			return $maybe_cloud;
		}

		$server = $this->detect_cloud( $service );
		if ( ! $server ) {
			return;
		}

		$url = $server . '/' . $service;

		$param = [
			'site_url'   => site_url(),
			'main_domain'=> ! empty( $this->_summary['main_domain'] ) ? $this->_summary['main_domain'] : '',
			'ver'        => Core::VER,
		];

		if ( $data ) {
			$param['data'] = $data;
		}

		$url .= '?' . http_build_query( $param );

		self::debug( 'getting from : ' . $url );

		self::save_summary( [ 'curr_request.' . $service_tag => time() ] );
		File::save( $this->_qc_time_file( $service_tag, 'curr' ), time(), true );

		$response = wp_safe_remote_get(
			$url,
			[
				'timeout' => 15,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		return $this->_parse_response( $response, $service, $service_tag, $server );
	}

	/**
	 * Check if is able to do cloud request or not
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string $service_tag Service tag.
	 * @return bool|string
	 */
	private function _maybe_cloud( $service_tag ) {
		$site_url = site_url();
		if ( ! wp_http_validate_url( $site_url ) ) {
			self::debug( 'wp_http_validate_url failed: ' . $site_url );
			return false;
		}

		// Deny if is IP
		if ( preg_match( '#^(([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)\.){3}([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)$#', Utility::parse_url_safe( $site_url, PHP_URL_HOST ) ) ) {
			self::debug( 'IP home url is not allowed for cloud service.' );
			$msg = __( 'In order to use QC services, need a real domain name, cannot use an IP.', 'litespeed-cache' );
			Admin_Display::error( $msg );
			return false;
		}

		// If in valid err_domains, bypass request
		if ( $this->_is_err_domain( $site_url ) ) {
			self::debug( 'home url is in err_domains, bypass request: ' . $site_url );
			return false;
		}

		// we don't want the `img_optm-taken` to fail at any given time
		if ( self::IMGOPTM_TAKEN === $service_tag ) {
			return true;
		}

		if ( self::SVC_D_SYNC_CONF === $service_tag && ! $this->activated() ) {
			self::debug( 'Skip sync conf as QC not activated yet.' );
			return false;
		}

		// Check TTL
		if ( ! empty( $this->_summary[ 'ttl.' . $service_tag ] ) ) {
			$ttl = (int) $this->_summary[ 'ttl.' . $service_tag ] - time();
			if ( $ttl > 0 ) {
				self::debug( '❌ TTL limit. [srv] ' . $service_tag . ' [TTL cool down] ' . $ttl . ' seconds' );
				return 'svc_hot';
			}
		}

		$expiration_req = self::EXPIRATION_REQ;
		// Limit frequent unfinished request to 5min
		$timestamp_tag = 'curr';
		if ( self::SVC_IMG_OPTM . '-' . Img_Optm::TYPE_NEW_REQ === $service_tag ) {
			$timestamp_tag = 'last';
		}

		// For all other requests, if is under debug mode, will always allow
		if ( ! $this->conf( self::O_DEBUG ) ) {
			if ( ! empty( $this->_summary[ $timestamp_tag . '_request.' . $service_tag ] ) ) {
				$expired = (int) $this->_summary[ $timestamp_tag . '_request.' . $service_tag ] + $expiration_req - time();
				if ( $expired > 0 ) {
					self::debug( '❌ try [' . $service_tag . '] after ' . $expired . ' seconds' );

					if ( self::API_VER !== $service_tag ) {
						$msg =
							__( 'Cloud Error', 'litespeed-cache' ) .
							': ' .
							sprintf(
								__( 'Please try after %1$s for service %2$s.', 'litespeed-cache' ),
								Utility::readable_time( $expired, 0, true ),
								'<code>' . $service_tag . '</code>'
							);
						Admin_Display::error( [ 'cloud_trylater' => $msg ] );
					}

					return false;
				}
			} else {
				// May fail to store to db if db is oc cached/dead/locked/readonly. Need to store to file to prevent from duplicate calls
				$file_path = $this->_qc_time_file( $service_tag, $timestamp_tag );
				if ( file_exists( $file_path ) ) {
					$last_request = File::read( $file_path );
					$expired      = (int) $last_request + $expiration_req * 10 - time();
					if ( $expired > 0 ) {
						self::debug( '❌ try [' . $service_tag . '] after ' . $expired . ' seconds' );
						return false;
					}
				}
				// For ver check, additional check to prevent frequent calls as old DB ver may be cached
				if ( self::API_VER === $service_tag ) {
					$file_path = $this->_qc_time_file( $service_tag );
					if ( file_exists( $file_path ) ) {
						$last_request = File::read( $file_path );
						$expired      = (int) $last_request + $expiration_req * 10 - time();
						if ( $expired > 0 ) {
							self::debug( '❌❌ Unusual req! try [' . $service_tag . '] after ' . $expired . ' seconds' );
							return false;
						}
					}
				}
			}
		}

		if ( in_array( $service_tag, self::$_pub_svc_set, true ) ) {
			return true;
		}

		if ( ! $this->activated() && self::SVC_D_ACTIVATE !== $service_tag ) {
			Admin_Display::error( Error::msg( 'qc_setup_required' ) );
			return false;
		}

		return true;
	}

	/**
	 * Get QC req ts file path
	 *
	 * @since 7.5
	 *
	 * @param string $service_tag Service tag.
	 * @param string $type        Type: 'last' or 'curr'.
	 * @return string
	 */
	private function _qc_time_file( $service_tag, $type = 'last' ) {
		if ( 'curr' !== $type ) {
			$type = 'last';
		}
		$legacy_file = LITESPEED_STATIC_DIR . '/qc_' . $type . '_request' . md5( $service_tag );
		if ( file_exists( $legacy_file ) ) {
			wp_delete_file( $legacy_file );
		}
		$service_tag = preg_replace( '/[^a-zA-Z0-9]/', '', $service_tag );
		return LITESPEED_STATIC_DIR . '/qc.' . $type . '.' . $service_tag;
	}

	/**
	 * Check if a service tag ttl is valid or not
	 *
	 * @since 7.1
	 *
	 * @param string $service_tag Service tag.
	 * @return int|false Seconds remaining or false if not hot.
	 */
	public function service_hot( $service_tag ) {
		if ( empty( $this->_summary[ 'ttl.' . $service_tag ] ) ) {
			return false;
		}

		$ttl = (int) $this->_summary[ 'ttl.' . $service_tag ] - time();
		if ( $ttl <= 0 ) {
			return false;
		}

		return $ttl;
	}

	/**
	 * Post data to QUIC.cloud server
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param string     $service  Service name/route.
	 * @param array|bool $data     Payload data or false to omit.
	 * @param int|false  $time_out Timeout seconds or false for default.
	 * @return mixed Response payload or false on failure.
	 */
	public static function post( $service, $data = false, $time_out = false ) {
		$instance = self::cls();
		return $instance->_post( $service, $data, $time_out );
	}

	/**
	 * Post data to cloud server
	 *
	 * @since  3.0
	 * @access private
	 *
	 * @param string     $service  Service name/route.
	 * @param array|bool $data     Payload data or false to omit.
	 * @param int|false  $time_out Timeout seconds or false for default.
	 * @return mixed Response payload or false on failure.
	 */
	private function _post( $service, $data = false, $time_out = false ) {
		$service_tag = $service;
		if ( ! empty( $data['action'] ) ) {
			$service_tag .= '-' . $data['action'];
		}

		$maybe_cloud = $this->_maybe_cloud( $service_tag );
		if ( ! $maybe_cloud || 'svc_hot' === $maybe_cloud ) {
			self::debug( 'Maybe cloud failed: ' . wp_json_encode( $maybe_cloud ) );
			return $maybe_cloud;
		}

		$server = $this->detect_cloud( $service );
		if ( ! $server ) {
			return;
		}

		$url = $server . '/' . $this->_maybe_queue( $service );

		self::debug( 'posting to : ' . $url );

		if ( $data ) {
			$data['service_type'] = $service; // For queue distribution usage
		}

		// Encrypt service as signature
		// $signature_ts = time();
		// $sign_data = [
		// 'service_tag' => $service_tag,
		// 'ts' => $signature_ts,
		// ];
		// $data['signature_b64'] = $this->_sign_b64(implode('', $sign_data));
		// $data['signature_ts'] = $signature_ts;

		self::debug( 'data', $data );
		$param = [
			'site_url'    => site_url(), // Need to use site_url() as WPML case may change home_url() for diff langs (no need to treat as alias for multi langs)
			'main_domain' => ! empty( $this->_summary['main_domain'] ) ? $this->_summary['main_domain'] : '',
			'wp_pk_b64'   => ! empty( $this->_summary['pk_b64'] ) ? $this->_summary['pk_b64'] : '',
			'ver'         => Core::VER,
			'data'        => $data,
		];

		self::save_summary( [ 'curr_request.' . $service_tag => time() ] );
		File::save( $this->_qc_time_file( $service_tag, 'curr' ), time(), true );

		$response = wp_safe_remote_post(
			$url,
			[
				'body'    => $param,
				'timeout' => $time_out ? $time_out : 30,
				'headers' => [
					'Accept' => 'application/json',
					'Expect' => '',
				],
			]
		);

		return $this->_parse_response( $response, $service, $service_tag, $server );
	}

	/**
	 * Parse response JSON
	 * Mark the request successful if the response status is ok
	 *
	 * @since  3.0
	 *
	 * @param array|mixed $response    WP HTTP API response.
	 * @param string      $service     Service name.
	 * @param string      $service_tag Service tag including action.
	 * @param string      $server      Server URL.
	 * @return array|false Parsed JSON array or false on failure.
	 */
	private function _parse_response( $response, $service, $service_tag, $server ) {
		// If show the error or not if failed
		$visible_err = self::API_VER !== $service && self::API_NEWS !== $service && self::SVC_D_DASH !== $service;

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			self::debug( 'failed to request: ' . $error_message );

			if ( $visible_err ) {
				$msg = esc_html__( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . esc_html( $error_message ) . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
				Admin_Display::error( $msg );

				// Tmp disabled this node from reusing in 1 day
				if ( empty( $this->_summary['disabled_node'] ) ) {
					$this->_summary['disabled_node'] = [];
				}
				$this->_summary['disabled_node'][ $server ] = time();
				self::save_summary();

				// Force redetect node
				self::debug( 'Node error, redetecting node [svc] ' . $service );
				$this->detect_cloud( $service, true );
			}
			return false;
		}

		$json = \json_decode( $response['body'], true );

		if ( ! is_array( $json ) ) {
			self::debugErr( 'failed to decode response json: ' . $response['body'] );

			if ( $visible_err ) {
				$msg = esc_html__( 'Failed to request via WordPress', 'litespeed-cache' ) . ': ' . esc_html( $response['body'] ) . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
				Admin_Display::error( $msg );

				// Tmp disabled this node from reusing in 1 day
				if ( empty( $this->_summary['disabled_node'] ) ) {
					$this->_summary['disabled_node'] = [];
				}
				$this->_summary['disabled_node'][ $server ] = time();
				self::save_summary();

				// Force redetect node
				self::debugErr( 'Node error, redetecting node [svc] ' . $service );
				$this->detect_cloud( $service, true );
			}

			return false;
		}

		// Check and save TTL data
		if ( ! empty( $json['_ttl'] ) ) {
			$ttl = (int) $json['_ttl'];
			self::debug( 'Service TTL to save: ' . $ttl );
			if ( $ttl > 0 && $ttl < 86400 ) {
				self::save_summary([
					'ttl.' . $service_tag => $ttl + time(),
				]);
			}
		}

		if ( ! empty( $json['_code'] ) ) {
			self::debugErr( 'Hit err _code: ' . $json['_code'] );
			if ( 'unpulled_images' === $json['_code'] ) {
				$msg = __( 'Cloud server refused the current request due to unpulled images. Please pull the images first.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}
			if ( 'blocklisted' === $json['_code'] ) {
				$msg = __( 'Your domain_key has been temporarily blocklisted to prevent abuse. You may contact support at QUIC.cloud to learn more.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}

			if ( 'rate_limit' === $json['_code'] ) {
				self::debugErr( 'Cloud server rate limit exceeded.' );
				$msg = __( 'Cloud server refused the current request due to rate limiting. Please try again later.', 'litespeed-cache' );
				Admin_Display::error( $msg );
				return false;
			}

			if ( 'heavy_load' === $json['_code'] || 'redetect_node' === $json['_code'] ) {
				// Force redetect node
				self::debugErr( 'Node redetecting node [svc] ' . $service );
				Admin_Display::info( __( 'Redetected node', 'litespeed-cache' ) . ': ' . Error::msg( $json['_code'] ) );
				$this->detect_cloud( $service, true );
			}
		}

		if ( ! empty( $json['_503'] ) ) {
			self::debugErr( 'service 503 unavailable temporarily. ' . $json['_503'] );

			$msg  = __(
				'We are working hard to improve your online service experience. The service will be unavailable while we work. We apologize for any inconvenience.',
				'litespeed-cache'
			);
			$msg .= ' ' . $json['_503'] . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
			Admin_Display::error( $msg );

			// Force redetect node
			self::debugErr( 'Node error, redetecting node [svc] ' . $service );
			$this->detect_cloud( $service, true );

			return false;
		}

		list( $json, $return ) = $this->extract_msg( $json, $service, $server );
		if ( $return ) {
			return false;
		}

		$curr_request = $this->_summary[ 'curr_request.' . $service_tag ];
		self::save_summary([
			'last_request.' . $service_tag => $curr_request,
			'curr_request.' . $service_tag => 0,
		]);
		File::save( $this->_qc_time_file( $service_tag ), $curr_request, true );
		File::save( $this->_qc_time_file( $service_tag, 'curr' ), 0, true );

		if ( $json ) {
			self::debug2( 'response ok', $json );
		} else {
			self::debug2( 'response ok' );
		}

		// Only successful request return Array
		return $json;
	}

	/**
	 * Extract msg from json
	 *
	 * @since 5.0
	 *
	 * @param array       $json        Response JSON.
	 * @param string      $service     Service name.
	 * @param string|bool $server      Server URL or false.
	 * @param bool        $is_callback Whether called from callback context.
	 * @return array Array with [json array, bool should_return_false]
	 */
	public function extract_msg( $json, $service, $server = false, $is_callback = false ) {
		if ( ! empty( $json['_info'] ) ) {
			self::debug( '_info: ' . $json['_info'] );
			$msg  = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json['_info'];
			$msg .= $this->_parse_link( $json );
			Admin_Display::info( $msg );
			unset( $json['_info'] );
		}

		if ( ! empty( $json['_note'] ) ) {
			self::debug( '_note: ' . $json['_note'] );
			$msg  = __( 'Message from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json['_note'];
			$msg .= $this->_parse_link( $json );
			Admin_Display::note( $msg );
			unset( $json['_note'] );
		}

		if ( ! empty( $json['_success'] ) ) {
			self::debug( '_success: ' . $json['_success'] );
			$msg  = __( 'Good news from QUIC.cloud server', 'litespeed-cache' ) . ': ' . $json['_success'];
			$msg .= $this->_parse_link( $json );
			Admin_Display::success( $msg );
			unset( $json['_success'] );
		}

		// Upgrade is required
		if ( ! empty( $json['_err_req_v'] ) ) {
			self::debug( '_err_req_v: ' . $json['_err_req_v'] );
			$msg = sprintf( __( '%1$s plugin version %2$s required for this action.', 'litespeed-cache' ), Core::NAME, 'v' . $json['_err_req_v'] . '+' ) .
				' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );

			// Append upgrade link
			$msg2 = ' ' . GUI::plugin_upgrade_link( Core::NAME, Core::PLUGIN_NAME, $json['_err_req_v'] );

			$msg2 .= $this->_parse_link( $json );
			Admin_Display::error( $msg . $msg2 );
			return [ $json, true ];
		}

		// Parse _carry_on info
		if ( ! empty( $json['_carry_on'] ) ) {
			self::debug( 'Carry_on usage', $json['_carry_on'] );
			// Store generic info
			foreach ( [ 'usage', 'promo', 'mini_html', 'partner', '_error', '_info', '_note', '_success' ] as $v ) {
				if ( isset( $json['_carry_on'][ $v ] ) ) {
					switch ( $v ) {
						case 'usage':
                        $usage_svc_tag                               = in_array( $service, [ self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI ], true ) ? self::SVC_PAGE_OPTM : $service;
                        $this->_summary[ 'usage.' . $usage_svc_tag ] = $json['_carry_on'][ $v ];
							break;

						case 'promo':
                        if ( empty( $this->_summary[ $v ] ) || ! is_array( $this->_summary[ $v ] ) ) {
								$this->_summary[ $v ] = [];
							}
                        $this->_summary[ $v ][] = $json['_carry_on'][ $v ];
							break;

						case 'mini_html':
                        foreach ( $json['_carry_on'][ $v ] as $k2 => $v2 ) {
								if ( 0 === strpos( $k2, 'ttl.' ) ) {
                                $v2 += time();
									}
								$this->_summary[ $v ][ $k2 ] = $v2;
							}
							break;

						case 'partner':
                        $this->_summary[ $v ] = $json['_carry_on'][ $v ];
							break;

						case '_error':
						case '_info':
						case '_note':
						case '_success':
                        $color_mode = substr( $v, 1 );
                        $msgs       = $json['_carry_on'][ $v ];
                        Admin_Display::add_unique_notice( $color_mode, $msgs, true );
							break;

						default:
							break;
					}
				}
			}
			self::save_summary();
			unset( $json['_carry_on'] );
		}

		// Parse general error msg
		if ( ! $is_callback && ( empty( $json['_res'] ) || 'ok' !== $json['_res'] ) ) {
			$json_msg = ! empty( $json['_msg'] ) ? $json['_msg'] : 'unknown';
			self::debug( '❌ _err: ' . $json_msg, $json );

			$str_translated = Error::msg( $json_msg );
			$msg            = __( 'Failed to communicate with QUIC.cloud server', 'litespeed-cache' ) . ': ' . $str_translated . ' [server] ' . esc_html( $server ) . ' [service] ' . esc_html( $service );
			$msg           .= $this->_parse_link( $json );
			$visible_err    = self::API_VER !== $service && self::API_NEWS !== $service && self::SVC_D_DASH !== $service;
			if ( $visible_err ) {
				Admin_Display::error( $msg );
			}

			// QC may try auto alias
			// Store the domain as `err_domains` only for QC auto alias feature
			if ( 'err_alias' === $json_msg ) {
				if ( empty( $this->_summary['err_domains'] ) ) {
					$this->_summary['err_domains'] = [];
				}
				$site_url = site_url();
				if ( ! array_key_exists( $site_url, $this->_summary['err_domains'] ) ) {
					$this->_summary['err_domains'][ $site_url ] = time();
				}
				self::save_summary();
			}

			// Site not on QC, reset QC connection registration
			if ( 'site_not_registered' === $json_msg || 'err_key' === $json_msg ) {
				$this->_reset_qc_reg();
			}

			return [ $json, true ];
		}

		unset( $json['_res'] );
		if ( ! empty( $json['_msg'] ) ) {
			unset( $json['_msg'] );
		}

		return [ $json, false ];
	}

	/**
	 * Parse _links from json
	 *
	 * @since  1.6.5
	 * @since  1.6.7 Self clean the parameter
	 * @access private
	 *
	 * @param array $json JSON array (passed by reference).
	 * @return string HTML link string.
	 */
	private function _parse_link( &$json ) {
		$msg = '';

		if ( ! empty( $json['_links'] ) ) {
			foreach ( $json['_links'] as $v ) {
				$msg .= ' ' . sprintf( '<a href="%s" class="%s" target="_blank">%s</a>', esc_url( $v['link'] ), ! empty( $v['cls'] ) ? esc_attr( $v['cls'] ) : '', esc_html( $v['title'] ) );
			}

			unset( $json['_links'] );
		}

		return $msg;
	}
}
