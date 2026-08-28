<?php
/**
 * The viewport image (VPI) class.
 *
 * Handles discovering above-the-fold images for posts/pages and stores the
 * viewport image list per post (desktop & mobile). Coordinates with the
 * remote svc via queue + cron.
 *
 * @since   4.7
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Generate and manage ViewPort Images (VPI) for pages.
 */
class VPI extends Cloud_Queue_Svc {

	/**
	 * Log tag for debug output.
	 *
	 * @var string
	 */
	const LOG_TAG = '[VPI]';

	/**
	 * VPI Desktop Meta name.
	 *
	 * @since  7.6
	 * @var string
	 */
	const POST_META = 'litespeed_vpi_list';

	/**
	 * VPI Mobile Meta name.
	 *
	 * @since  7.6
	 * @var string
	 */
	const POST_META_MOBILE = 'litespeed_vpi_list_mobile';

	/**
	 * Init.
	 *
	 * @since 4.7
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Svc id slug — drives queue type, Cloud::SVC_VPI, and summary key prefix.
	 *
	 * @return string
	 */
	protected function _svc_id() {
		return 'vpi';
	}

	/**
	 * Response field carrying the viewport image data.
	 *
	 * @return string
	 */
	protected function _data_key() {
		return 'data_vpi';
	}

	/**
	 * UI templates read `last_request` directly (settings_vpi.tpl.php,
	 * dashboard.tpl.php). Keep the legacy unsuffixed key.
	 *
	 * @return string
	 */
	protected function _last_request_key() {
		return 'last_request';
	}

	/**
	 * Build the request body for Cloud::post.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return array
	 */
	protected function _build_payload( $queue_k, $v ) {
		return [
			'url'        => $v['url'],
			'queue_k'    => $queue_k,
			'user_agent' => $v['user_agent'],
			'is_mobile'  => ! empty( $v['is_mobile'] ) ? 1 : 0,
		];
	}

	/**
	 * Persist the viewport image list to post meta. Empty/null payload is
	 * persisted as an empty list (matches pre-refactor VPI behavior) so the
	 * post is marked processed and not re-queued.
	 *
	 * @param mixed  $data    VPI data (array, string, or null).
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool
	 */
	protected function _save_result( $data, $queue_k, $v ) {
		$name = ! empty( $v['is_mobile'] ) ? self::POST_META_MOBILE : self::POST_META;
		if ( is_array( $data ) ) {
			$urldecode = array_map( 'urldecode', $data );
		} elseif ( null === $data || '' === $data ) {
			$urldecode = '';
		} else {
			$urldecode = urldecode( (string) $data );
		}
		self::debug( 'save data_vpi', $urldecode );
		$this->cls( 'Metabox' )->save( (int) $v['post_id'], $name, $urldecode );
		return true;
	}

	/**
	 * Queue the current page for VPI generation.
	 *
	 * @since 4.7
	 * @return void
	 */
	public function add_to_queue() {
		$is_mobile   = $this->_separate_mobile();
		$request_url = Utility::request_url();

		if ( ! apply_filters( 'litespeed_vpi_should_queue', true, $request_url ) ) {
			return;
		}

		// Sanitize user agent coming from the server superglobal.
		$ua = ! empty( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		// Store it to prepare for cron.
		$this->_queue = $this->load_queue( 'vpi' );

		if ( count( $this->_queue ) >= $this->_max_queue_size() ) {
			self::debug( 'Queue is full - ' . $this->_max_queue_size() );
			return;
		}

		$home_id = (int) get_option( 'page_for_posts' );

		if ( ! is_singular() && ! ( $home_id > 0 && is_home() ) ) {
			self::debug( 'not single post ID' );
			return;
		}

		$post_id = is_home() ? $home_id : get_the_ID();

		$queue_k = ( $is_mobile ? 'mobile' : '' ) . ' ' . $request_url;
		if ( ! empty( $this->_queue[ $queue_k ] ) ) {
			self::debug( 'queue k existed ' . $queue_k );
			return;
		}

		$this->_queue[ $queue_k ] = [
			'url'        => apply_filters( 'litespeed_vpi_url', $request_url ),
			'post_id'    => $post_id,
			'user_agent' => substr( $ua, 0, 200 ),
			'is_mobile'  => $is_mobile,
		]; // Current UA will be used to request.
		$this->save_queue( 'vpi', $this->_queue );
		self::debug( 'Added queue_vpi [url] ' . $queue_k . ' [UA] ' . $ua );

		// Prepare cache tag for later purge.
		Tag::add( 'VPI.' . md5( $queue_k ) );
	}
}
