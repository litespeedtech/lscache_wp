<?php
/**
 * The viewport image class.
 *
 * @since      	4.7
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class VPI extends Base {
	/**
	 * Init
	 *
	 * @since  4.7
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * The VPI content of the current page
	 *
	 * @since  4.7
	 */
	public function add_to_queue() {
		$is_mobile = $this->_separate_mobile();

		global $wp;
		$request_url = home_url( $wp->request );

		$ua = ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '';

		// Store it to prepare for cron
		$this->_queue = $this->load_queue( 'vpi' );

		if ( count( $this->_queue ) > 500 ) {
			self::debug( 'Queue is full - 500' );
			return;
		}

		if ( ! is_singular() ) {
			self::debug( 'not single post ID' );
			return;
		}

		$post_id = get_the_ID();

		$queue_k = ( $is_mobile ? 'mobile' : '' ) . ' ' . $request_url;
		if ( ! empty( $this->_queue[ $queue_k ] ) ) {
			self::debug( 'queue k existed ' . $queue_k );
			return;
		}

		$this->_queue[ $queue_k ] = array(
			'url'			=> apply_filters( 'litespeed_vpi_url', $request_url ),
			'post_id' 		=> $post_id,
			'user_agent'	=> substr( $ua, 0, 200 ),
			'is_mobile'		=> $this->_separate_mobile(),
		); // Current UA will be used to request
		$this->save_queue( 'vpi', $this->_queue );
		self::debug( 'Added queue_vpi [url] ' . $queue_k . ' [UA] ' . $ua );

		// Prepare cache tag for later purge
		Tag::add( 'VPI.' . md5( $queue_k ) );

		return null;
	}

	/**
	 * Notify finished from server
	 * @since 4.7
	 */
	public function notify() {
		$post_data = json_decode(file_get_contents('php://input'), true);
		if( is_null( $post_data ) ) {
			$post_data = $_POST;
		}

		// Validate key
		if ( empty( $post_data[ 'domain_key' ] ) || $post_data[ 'domain_key' ] !== md5( $this->conf( self::O_API_KEY ) ) ) {
			$this->_summary[ 'notify_ts_err' ] = time();
			self::save_summary();
			return Cloud::err( 'wrong_key' );
		}

		global $wpdb;

		$notified_data = $post_data[ 'data' ];
		if ( empty( $notified_data ) || ! is_array( $notified_data ) ) {
			self::debug( '❌ notify exit: no notified data' );
			return Cloud::err( 'no notified data' );
		}

		// Check if its in queue or not
		$valid_i = 0;
		foreach ( $notified_data as $v ) {
			if ( empty( $v[ 'request_url' ] ) ) continue;
			$is_mobile = !empty( $v[ 'is_mobile' ] );
			$queue_k = ( $is_mobile ? 'mobile' : '' ) . ' ' . $v[ 'request_url' ];

			if ( empty( $this->_queue[ $queue_k ] ) ) continue;

			// Save data
			if ( ! empty( $v[ 'data' ] ) ) {
				$post_id = $this->_queue[ $queue_k ][ 'post_id' ];
				$name = $is_mobile ? 'litespeed_vpi_list_mobile' : 'litespeed_vpi_list';
				$this->cls( 'Metabox' )->save( $post_id, $name, $v[ 'data' ], true );

				$valid_i ++;
			}

			unset( $this->_queue[ $queue_k ] );
		}

		return Cloud::ok( array( 'count' => $valid_i ) );
	}

}