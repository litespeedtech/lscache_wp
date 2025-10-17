<?php
/**
 * Purge handlers for X-LiteSpeed-Purge.
 *
 * @since   1.1.3
 * @since   2.2  Refactored. Changed access from public to private for most functions and class variables.
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Purge
 */
class Purge extends Base {

	const LOG_TAG = '🧹';

	/**
	 * Public purge tags for X-LiteSpeed-Purge.
	 *
	 * @var array<int,string>
	 */
	protected $_pub_purge = [];

	/**
	 * Public purge tags for X-LiteSpeed-Purge2.
	 *
	 * @var array<int,string>
	 */
	protected $_pub_purge2 = [];

	/**
	 * Private purge tags for X-LiteSpeed-Purge (private section).
	 *
	 * @var array<int,string>
	 */
	protected $_priv_purge = [];

	/**
	 * Whether to purge only current URL (QS helper).
	 *
	 * @var bool
	 */
	protected $_purge_single = false;

	const X_HEADER  = 'X-LiteSpeed-Purge';
	const X_HEADER2 = 'X-LiteSpeed-Purge2';
	const DB_QUEUE  = 'queue';
	const DB_QUEUE2 = 'queue2';

	const TYPE_PURGE_ALL          = 'purge_all';
	const TYPE_PURGE_ALL_LSCACHE  = 'purge_all_lscache';
	const TYPE_PURGE_ALL_CSSJS    = 'purge_all_cssjs';
	const TYPE_PURGE_ALL_LOCALRES = 'purge_all_localres';
	const TYPE_PURGE_ALL_CCSS     = 'purge_all_ccss';
	const TYPE_PURGE_ALL_UCSS     = 'purge_all_ucss';
	const TYPE_PURGE_ALL_LQIP     = 'purge_all_lqip';
	const TYPE_PURGE_ALL_VPI      = 'purge_all_vpi';
	const TYPE_PURGE_ALL_AVATAR   = 'purge_all_avatar';
	const TYPE_PURGE_ALL_OBJECT   = 'purge_all_object';
	const TYPE_PURGE_ALL_OPCACHE  = 'purge_all_opcache';

	const TYPE_PURGE_FRONT     = 'purge_front';
	const TYPE_PURGE_UCSS      = 'purge_ucss';
	const TYPE_PURGE_FRONTPAGE = 'purge_frontpage';
	const TYPE_PURGE_PAGES     = 'purge_pages';
	const TYPE_PURGE_ERROR     = 'purge_error';

	/**
	 * Init hooks.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function init() {
		$purge_post_events = apply_filters(
			'litespeed_purge_post_events',
			[
				'delete_post',
				'wp_trash_post',
				'wp_update_comment_count',
			]
		);

		foreach ( $purge_post_events as $event ) {
			add_action( $event, [ $this, 'purge_post' ] );
		}

		// Purge post only when status is/was publish.
		add_action( 'transition_post_status', [ $this, 'purge_publish' ], 10, 3 );

		add_action( 'wp_update_comment_count', [ $this, 'purge_feeds' ] );

		if ( $this->conf( self::O_OPTM_UCSS ) ) {
			add_action( 'edit_post', __NAMESPACE__ . '\Purge::purge_ucss' );
		}
	}

	/**
	 * Only purge publish related status post.
	 *
	 * @since 3.0
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post      Post object.
	 * @return void
	 */
	public function purge_publish( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		$this->purge_post( $post->ID );
	}

	/**
	 * Handle all request actions from main cls.
	 *
	 * @since  1.8
	 * @since  7.6 Add VPI clear.
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_PURGE_ALL:
				$this->_purge_all();
				break;

			case self::TYPE_PURGE_ALL_LSCACHE:
				$this->_purge_all_lscache();
				break;

			case self::TYPE_PURGE_ALL_CSSJS:
				$this->_purge_all_cssjs();
				break;

			case self::TYPE_PURGE_ALL_LOCALRES:
				$this->_purge_all_localres();
				break;

			case self::TYPE_PURGE_ALL_CCSS:
				$this->_purge_all_ccss();
				break;

			case self::TYPE_PURGE_ALL_UCSS:
				$this->_purge_all_ucss();
				break;

			case self::TYPE_PURGE_ALL_LQIP:
				$this->_purge_all_lqip();
				break;

			case self::TYPE_PURGE_ALL_VPI:
				$this->_purge_all_vpi();
				break;

			case self::TYPE_PURGE_ALL_AVATAR:
				$this->_purge_all_avatar();
				break;

			case self::TYPE_PURGE_ALL_OBJECT:
				$this->_purge_all_object();
				break;

			case self::TYPE_PURGE_ALL_OPCACHE:
				$this->purge_all_opcache();
				break;

			case self::TYPE_PURGE_FRONT:
				$this->_purge_front();
				break;

			case self::TYPE_PURGE_UCSS:
				$this->_purge_ucss();
				break;

			case self::TYPE_PURGE_FRONTPAGE:
				$this->_purge_frontpage();
				break;

			case self::TYPE_PURGE_PAGES:
				$this->_purge_pages();
				break;

			case ( 0 === strpos( $type, self::TYPE_PURGE_ERROR ) ):
				$this->_purge_error( substr( $type, strlen( self::TYPE_PURGE_ERROR ) ) );
				break;

			default:
				break;
		}

		Admin::redirect();
	}

	/**
	 * Shortcut to purge all lscache.
	 *
	 * @since 1.0.0
	 * @param string|false $reason Optional reason to log.
	 * @return void
	 */
	public static function purge_all( $reason = false ) {
		self::cls()->_purge_all( $reason );
	}

	/**
	 * Purge all caches (LSCache/CSS/JS/localres/object/opcache).
	 *
	 * @since 2.2
	 * @param string|false $reason Optional log string.
	 * @return void
	 */
	private function _purge_all( $reason = false ) {
		$this->_purge_all_lscache( true );
		$this->_purge_all_cssjs( true );
		$this->_purge_all_localres( true );
		$this->_purge_all_object( true );
		$this->purge_all_opcache( true );

		if ( $this->conf( self::O_CDN_CLOUDFLARE_CLEAR ) ) {
			CDN\Cloudflare::purge_all( 'Purge All' );
		}

		$reason = is_string( $reason ) ? "( $reason )" : '';

		self::debug( 'Purge all ' . $reason, 3 );

		$msg = __( 'Purged all caches successfully.', 'litespeed-cache' );
		if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
			Admin_Display::success( $msg );
		}

		do_action( 'litespeed_purged_all' );
	}

	/**
	 * Alerts LiteSpeed Web Server to purge all pages.
	 *
	 * @since 2.2
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	private function _purge_all_lscache( $silence = false ) {
		$this->_add( '*' );

		do_action( 'litespeed_purged_all_lscache' );

		if ( ! $silence ) {
			$msg = __( 'Notified LiteSpeed Web Server to purge all LSCache entries.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}
	}

	/**
	 * Delete all critical CSS.
	 *
	 * @since 2.3
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	private function _purge_all_ccss( $silence = false ) {
		do_action( 'litespeed_purged_all_ccss' );

		$this->cls( 'CSS' )->rm_cache_folder( 'ccss' );
		$this->cls( 'Data' )->url_file_clean( 'ccss' );

		if ( ! $silence ) {
			$msg = __( 'Cleaned all Critical CSS files.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}
	}

	/**
	 * Delete all unique CSS.
	 *
	 * @since 2.3
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	private function _purge_all_ucss( $silence = false ) {
		do_action( 'litespeed_purged_all_ucss' );

		$this->cls( 'CSS' )->rm_cache_folder( 'ucss' );
		$this->cls( 'Data' )->url_file_clean( 'ucss' );

		if ( ! $silence ) {
			$msg = __( 'Cleaned all Unique CSS files.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}
	}

	/**
	 * Purge one UCSS by URL or post ID.
	 *
	 * @since 4.5
	 * @param int|string $post_id_or_url Post ID or URL.
	 * @return void
	 */
	public static function purge_ucss( $post_id_or_url ) {
		self::debug( 'Purge a single UCSS: ' . $post_id_or_url );

		// If is post_id, generate URL.
		if ( ! preg_match( '/\D/', (string) $post_id_or_url ) ) {
			$post_id_or_url = get_permalink( (int) $post_id_or_url );
		}

		$post_id_or_url = untrailingslashit( (string) $post_id_or_url );

		$existing_url_files = Data::cls()->mark_as_expired( $post_id_or_url, true );
		if ( $existing_url_files ) {
			self::cls( 'UCSS' )->add_to_q( $existing_url_files );
		}
	}

	/**
	 * Delete all LQIP images.
	 *
	 * @since 3.0
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	private function _purge_all_lqip( $silence = false ) {
		do_action( 'litespeed_purged_all_lqip' );

		$this->cls( 'Placeholder' )->rm_cache_folder( 'lqip' );

		if ( ! $silence ) {
			$msg = __( 'Cleaned all LQIP files.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}
	}

	/**
	 * Delete all VPI data generated
	 *
	 * @since 7.6
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 * @access private
	 */
	private function _purge_all_vpi( $silence = false ) {
		global $wpdb;
		do_action( 'litespeed_purged_all_vpi' );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				'DELETE FROM `' . $wpdb->postmeta . '` WHERE meta_key = %s',
				VPI::POST_META
			)
		);
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				'DELETE FROM `' . $wpdb->postmeta . '` WHERE meta_key = %s',
				VPI::POST_META_MOBILE
			)
		);
		$this->cls( 'Placeholder' )->rm_cache_folder( 'vpi' );

		if ( !$silence ) {
			$msg = __( 'Cleaned all VPI data.', 'litespeed-cache' );
			!defined( 'LITESPEED_PURGE_SILENT' ) && Admin_Display::success( $msg );
		}
	}

	/**
	 * Delete all avatar images
	 *
	 * @since 3.0
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	private function _purge_all_avatar( $silence = false ) {
		do_action( 'litespeed_purged_all_avatar' );

		// Clear Database table
		$this->cls( 'Data' )->table_truncate( 'avatar' );
		// Remove the folder
		$this->cls( 'Avatar' )->rm_cache_folder( 'avatar' );

		if ( ! $silence ) {
			$msg = __( 'Cleaned all Gravatar files.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}
	}

	/**
	 * Delete all localized resources.
	 *
	 * @since 3.3
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	private function _purge_all_localres( $silence = false ) {
		do_action( 'litespeed_purged_all_localres' );

		$this->_add( Tag::TYPE_LOCALRES );

		if ( ! $silence ) {
			$msg = __( 'Cleaned all localized resource entries.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}
	}

	/**
	 * Purge CSS/JS assets and related LSCache entries.
	 *
	 * @since 1.2.2
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	private function _purge_all_cssjs( $silence = false ) {
		if ( wp_doing_cron() || defined( 'LITESPEED_DID_send_headers' ) ) {
			self::debug( '❌ Bypassed cssjs delete as header sent (lscache purge after this point will fail) or doing cron' );
			return;
		}

		$this->_purge_all_lscache( $silence ); // Purge CSSJS must purge lscache too to avoid 404

		do_action( 'litespeed_purged_all_cssjs' );

		Optimize::update_option( Optimize::ITEM_TIMESTAMP_PURGE_CSS, time() );

		$this->_add( Tag::TYPE_MIN );

		$this->cls( 'CSS' )->rm_cache_folder( 'css' );
		$this->cls( 'CSS' )->rm_cache_folder( 'js' );
		$this->cls( 'Data' )->url_file_clean( 'css' );
		$this->cls( 'Data' )->url_file_clean( 'js' );

		// Clear UCSS queue as it used combined CSS to generate.
		$this->clear_q( 'ucss', true );

		if ( ! $silence ) {
			$msg = __( 'Notified LiteSpeed Web Server to purge CSS/JS entries.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}
	}

	/**
	 * Purge opcode cache.
	 *
	 * @since 1.8.2
	 * @since 7.3   Added test for opcode cache restriction.
	 * @param bool $silence If true, don't show admin notice.
	 * @return bool True on success.
	 */
	public function purge_all_opcache( $silence = false ) {
		if ( ! Router::opcache_enabled() ) {
			self::debug( '❌ Failed to reset OPcache due to OPcache not enabled' );

			if ( ! $silence ) {
				$msg = __( 'OPcache is not enabled.', 'litespeed-cache' );
				if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
					Admin_Display::error( $msg );
				}
			}

			return false;
		}

		if ( Router::opcache_restricted( __FILE__ ) ) {
			self::debug( '❌ Failed to reset OPcache due to OPcache is restricted. File requesting the clear is not allowed.' );

			if ( ! $silence ) {
				$msg = sprintf( __( 'OPcache is restricted by %s setting.', 'litespeed-cache' ), '<code>restrict_api</code>' );
				if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
					Admin_Display::error( $msg );
				}
			}

			return false;
		}

		if ( ! opcache_reset() ) {
			self::debug( '❌ Reset OPcache not worked' );

			if ( ! $silence ) {
				$msg = __( 'Reset the OPcache failed.', 'litespeed-cache' );
				if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
					Admin_Display::success( $msg );
				}
			}

			return false;
		}

		do_action( 'litespeed_purged_all_opcache' );

		self::debug( 'Reset OPcache' );

		if ( ! $silence ) {
			$msg = __( 'Reset the entire OPcache successfully.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}

		return true;
	}

	/**
	 * Purge object cache (public wrapper).
	 *
	 * @since 3.4
	 * @param bool $silence If true, don't show admin notice.
	 * @return void
	 */
	public static function purge_all_object( $silence = true ) {
		self::cls()->_purge_all_object( $silence );
	}

	/**
	 * Purge object cache.
	 *
	 * @since 1.8
	 * @param bool $silence If true, don't show admin notice.
	 * @return bool True on success.
	 */
	private function _purge_all_object( $silence = false ) {
		if ( ! defined( 'LSCWP_OBJECT_CACHE' ) ) {
			self::debug( 'Failed to flush object cache due to object cache not enabled' );

			if ( ! $silence ) {
				$msg = __( 'Object cache is not enabled.', 'litespeed-cache' );
				Admin_Display::error( $msg );
			}

			return false;
		}

		do_action( 'litespeed_purged_all_object' );

		$this->cls( 'Object_Cache' )->flush();
		self::debug( 'Flushed object cache' );

		if ( ! $silence ) {
			$msg = __( 'Purge all object caches successfully.', 'litespeed-cache' );
			if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
				Admin_Display::success( $msg );
			}
		}

		return true;
	}

	/**
	 * Add public purge tags for current request.
	 *
	 * @since 1.1.3
	 * @param string|array<int,string> $tags   Tags to add.
	 * @param bool                     $purge2 Whether to send via X-LiteSpeed-Purge2.
	 * @return void
	 */
	public static function add( $tags, $purge2 = false ) {
		self::cls()->_add( $tags, $purge2 );
	}

	/**
	 * Add tags to purge list.
	 *
	 * @since 2.2
	 * @param string|array<int,string> $tags   Tags.
	 * @param bool                     $purge2 Use Purge2 header.
	 * @return void
	 */
	private function _add( $tags, $purge2 = false ) {
		if ( ! is_array( $tags ) ) {
			$tags = [ $tags ];
		}

		$tags = $this->_prepend_bid( $tags );

		if ( ! array_diff( $tags, $purge2 ? $this->_pub_purge2 : $this->_pub_purge ) ) {
			return;
		}

		if ( $purge2 ) {
			$this->_pub_purge2 = array_unique( array_merge( $this->_pub_purge2, $tags ) );
		} else {
			$this->_pub_purge = array_unique( array_merge( $this->_pub_purge, $tags ) );
		}

		self::debug( 'added ' . implode( ',', $tags ) . ( $purge2 ? ' [Purge2]' : '' ), 8 );

		// Send purge header immediately or queue if headers already sent or delayed.
		$curr_built = $this->_build( $purge2 );

		if ( defined( 'LITESPEED_CLI' ) ) {
			// Can't send, already has output, need to save and wait for next run
			self::update_option($purge2 ? self::DB_QUEUE2 : self::DB_QUEUE, $curr_built);
			self::debug( 'CLI request, queue stored: ' . $curr_built );
		} else {
			if ( ! headers_sent() ) {
				header( $curr_built );
			}
			if ( wp_doing_cron() || defined( 'LITESPEED_DID_send_headers' ) || apply_filters( 'litespeed_delay_purge', false ) ) {
				self::update_option( $purge2 ? self::DB_QUEUE2 : self::DB_QUEUE, $curr_built );
				self::debug( 'Output existed, queue stored: ' . $curr_built );
			}
			self::debug( $curr_built );
		}
	}

	/**
	 * Add private purge tags for current request.
	 *
	 * @since 1.1.3
	 * @param string|array<int,string> $tags Tags.
	 * @return void
	 */
	public static function add_private( $tags ) {
		self::cls()->_add_private( $tags );
	}

	/**
	 * Add private ESI tag to purge list.
	 *
	 * @since 3.0
	 * @param string $tag ESI tag.
	 * @return void
	 */
	public static function add_private_esi( $tag ) {
		self::add_private( Tag::TYPE_ESI . $tag );
	}

	/**
	 * Add private all tag to purge list.
	 *
	 * @since 3.0
	 * @return void
	 */
	public static function add_private_all() {
		self::add_private( '*' );
	}

	/**
	 * Add private purge tags.
	 *
	 * @since 2.2
	 * @param string|array<int,string> $tags Tags.
	 * @return void
	 */
	private function _add_private( $tags ) {
		if ( ! is_array( $tags ) ) {
			$tags = [ $tags ];
		}

		$tags = $this->_prepend_bid( $tags );

		if ( ! array_diff( $tags, $this->_priv_purge ) ) {
			return;
		}

		self::debug( 'added [private] ' . implode( ',', $tags ), 3 );

		$this->_priv_purge = array_unique( array_merge( $this->_priv_purge, $tags ) );

		// Send header immediately or skip if sent.
		$built = $this->_build();
		if ( $built && ! headers_sent() ) {
			header( $built );
		}
	}

	/**
	 * Add current blog ID prefix to tags (multisite).
	 *
	 * @since 4.0
	 * @param array<int,string> $tags Tags.
	 * @return array<int,string>
	 */
	private function _prepend_bid( $tags ) {
		if ( in_array( '*', $tags, true ) ) {
			return [ '*' ];
		}

		$curr_bid = is_multisite() ? get_current_blog_id() : '';

		foreach ( $tags as $k => $v ) {
			$tags[ $k ] = $curr_bid . '_' . $v;
		}
		return $tags;
	}

	/**
	 * Activate `purge single url tag` for Admin QS.
	 *
	 * @since 1.1.3
	 * @return void
	 */
	public static function set_purge_single() {
		self::cls()->_purge_single = true;
		do_action( 'litespeed_purged_single' );
	}

	/**
	 * Purge frontend url (based on HTTP_REFERER).
	 *
	 * @since 1.3
	 * @since 2.2 Access changed from public to private, renamed from `frontend_purge`.
	 * @return void
	 */
	private function _purge_front() {
		if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
			exit( 'no referer' );
		}

		$ref = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );

		$this->purge_url( $ref );

		do_action( 'litespeed_purged_front', $ref );

		wp_safe_redirect( $ref );
		exit;
	}

	/**
	 * Purge single UCSS (via referer or `url_tag`).
	 *
	 * @since 4.7
	 * @return void
	 */
	private function _purge_ucss() {
		if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
			exit( 'no referer' );
		}

		$ref = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );

		$url_tag = ! empty( $_GET['url_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['url_tag'] ) ) : $ref; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		self::debug( 'Purge ucss [url_tag] ' . $url_tag );

		do_action( 'litespeed_purge_ucss', $url_tag );
		$this->purge_url( $ref );

		wp_safe_redirect( $ref );
		exit;
	}

	/**
	 * Purge the front page.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	private function _purge_frontpage() {
		$this->_add( Tag::TYPE_FRONTPAGE );
		if ( 'LITESPEED_SERVER_OLS' !== LITESPEED_SERVER_TYPE ) {
			$this->_add_private( Tag::TYPE_FRONTPAGE );
		}

		$msg = __( 'Notified LiteSpeed Web Server to purge the front page.', 'litespeed-cache' );
		if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
			Admin_Display::success( $msg );
		}
		do_action( 'litespeed_purged_frontpage' );
	}

	/**
	 * Purge all pages.
	 *
	 * @since 1.0.15
	 * @return void
	 */
	private function _purge_pages() {
		$this->_add( Tag::TYPE_PAGES );

		$msg = __( 'Notified LiteSpeed Web Server to purge all pages.', 'litespeed-cache' );
		if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
			Admin_Display::success( $msg );
		}
		do_action( 'litespeed_purged_pages' );
	}

	/**
	 * Purge error pages (403/404/500).
	 *
	 * @since 1.0.14
	 * @param string|false $type Error type.
	 * @return void
	 */
	private function _purge_error( $type = false ) {
		$this->_add( Tag::TYPE_HTTP );

		if ( ! $type || ! in_array( (string) $type, [ '403', '404', '500' ], true ) ) {
			return;
		}

		$this->_add( Tag::TYPE_HTTP . $type );

		$msg = __( 'Notified LiteSpeed Web Server to purge error pages.', 'litespeed-cache' );
		if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
			Admin_Display::success( $msg );
		}
	}

	/**
	 * Purge selected category by slug.
	 *
	 * @since 1.0.7
	 * @param string $value Category slug.
	 * @return void
	 */
	public function purge_cat( $value ) {
		$val = trim( (string) $value );
		if ( '' === $val ) {
			return;
		}
		if ( 0 === preg_match( '/^[a-zA-Z0-9-]+$/', $val ) ) {
			self::debug( "$val cat invalid" );
			return;
		}
		$cat = get_category_by_slug( $val );
		if ( false === $cat ) {
			self::debug( "$val cat not existed/published" );
			return;
		}

		self::add( Tag::TYPE_ARCHIVE_TERM . $cat->term_id );

		if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
			Admin_Display::success( sprintf( __( 'Purge category %s', 'litespeed-cache' ), $val ) );
		}

		do_action( 'litespeed_purged_cat', $value );
	}

	/**
	 * Purge selected tag by slug.
	 *
	 * @since 1.0.7
	 * @param string $val Tag slug.
	 * @return void
	 */
	public function purge_tag( $val ) {
		$val = trim( (string) $val );
		if ( '' === $val ) {
			return;
		}
		if ( 0 === preg_match( '/^[a-zA-Z0-9-]+$/', $val ) ) {
			self::debug( "$val tag invalid" );
			return;
		}
		$term = get_term_by( 'slug', $val, 'post_tag' );
		if ( false === $term ) {
			self::debug( "$val tag not exist" );
			return;
		}

		self::add( Tag::TYPE_ARCHIVE_TERM . $term->term_id );

		if ( ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
			Admin_Display::success( sprintf( __( 'Purge tag %s', 'litespeed-cache' ), $val ) );
		}

		do_action( 'litespeed_purged_tag', $val );
	}

	/**
	 * Purge selected url (relative allowed).
	 *
	 * @since 1.0.7
	 * @param string $url    URL.
	 * @param bool   $purge2 Use Purge2 header.
	 * @param bool   $quite  If true, do not show admin notice.
	 * @return void
	 */
	public function purge_url( $url, $purge2 = false, $quite = false ) {
		$val = trim( (string) $url );
		if ( '' === $val ) {
			return;
		}

		if ( false !== strpos( $val, '<' ) ) {
			self::debug( "$val url contains <" );
			return;
		}

		$val  = Utility::make_relative( $val );
		$hash = Tag::get_uri_tag( $val );

		if ( false === $hash ) {
			self::debug( "$val url invalid" );
			return;
		}

		self::add( $hash, $purge2 );

		if ( ! $quite && ! defined( 'LITESPEED_PURGE_SILENT' ) ) {
			Admin_Display::success( sprintf( __( 'Purge url %s', 'litespeed-cache' ), $val ) );
		}

		do_action( 'litespeed_purged_link', $url );
	}

	/**
	 * Purge a list based on admin selection.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public function purge_list() {
		if ( ! isset( $_REQUEST[ Admin_Display::PURGEBYOPT_SELECT ], $_REQUEST[ Admin_Display::PURGEBYOPT_LIST ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$sel      = sanitize_text_field( wp_unslash( $_REQUEST[ Admin_Display::PURGEBYOPT_SELECT ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$list_buf = sanitize_textarea_field( wp_unslash( $_REQUEST[ Admin_Display::PURGEBYOPT_LIST ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $list_buf ) {
			return;
		}

		$list_buf = str_replace( ',', "\n", $list_buf );
		$raw_list = explode( "\n", $list_buf );

		switch ( $sel ) {
			case Admin_Display::PURGEBY_CAT:
            $cb = 'purge_cat';
				break;
			case Admin_Display::PURGEBY_PID:
            $cb = 'purge_post';
				break;
			case Admin_Display::PURGEBY_TAG:
            $cb = 'purge_tag';
				break;
			case Admin_Display::PURGEBY_URL:
            $cb = 'purge_url';
				break;
			default:
				return;
		}

		array_map( [ $this, $cb ], $raw_list );

		// For redirection (safe copy back to GET).
		$_GET[ Admin_Display::PURGEBYOPT_SELECT ] = $sel; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Purge ESI.
	 *
	 * @since 3.0
	 * @param string $tag ESI tag.
	 * @return void
	 */
	public static function purge_esi( $tag ) {
		self::add( Tag::TYPE_ESI . $tag );
		do_action( 'litespeed_purged_esi', $tag );
	}

	/**
	 * Purge a certain post type.
	 *
	 * @since 3.0
	 * @param string $post_type Post type.
	 * @return void
	 */
	public static function purge_posttype( $post_type ) {
		self::add( Tag::TYPE_ARCHIVE_POSTTYPE . $post_type );
		self::add( $post_type );

		do_action( 'litespeed_purged_posttype', $post_type );
	}

	/**
	 * Purge all related tags to a post.
	 *
	 * @since 1.0.0
	 * @param int $pid Post ID.
	 * @return void
	 */
	public function purge_post( $pid ) {
		$pid = (int) $pid;

		// Ignore the status we don't care.
		$status = get_post_status( $pid );
		if ( ! $pid || ! in_array( $status, [ 'publish', 'trash', 'private', 'draft' ], true ) ) {
			return;
		}

		$purge_tags = $this->_get_purge_tags_by_post( $pid );
		if ( ! $purge_tags ) {
			return;
		}

		self::add( $purge_tags );
		if ( $this->conf( self::O_CACHE_REST ) ) {
			self::add( Tag::TYPE_REST );
		}

		do_action( 'litespeed_purged_post', $pid );
	}

	/**
	 * Purge a widget by ID (or discover Recent Comments widget).
	 *
	 * Hooked to load-widgets.php.
	 *
	 * @since 1.1.3
	 * @param string|null $widget_id Widget ID.
	 * @return void
	 */
	public static function purge_widget( $widget_id = null ) {
		if ( null === $widget_id ) {
			if ( empty( $_POST['widget-id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}
			$widget_id = sanitize_text_field( wp_unslash( $_POST['widget-id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( '' === $widget_id ) {
				return;
			}
		}

		self::add( Tag::TYPE_WIDGET . $widget_id );
		self::add_private( Tag::TYPE_WIDGET . $widget_id );

		do_action( 'litespeed_purged_widget', $widget_id );
	}

	/**
	 * Purges the comment widget when the count is updated.
	 *
	 * @since 1.1.3
	 * @global \WP_Widget_Factory $wp_widget_factory
	 * @return void
	 */
	public static function purge_comment_widget() {
		global $wp_widget_factory;
		if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ) {
			return;
		}

		$recent_comments = $wp_widget_factory->widgets['WP_Widget_Recent_Comments'];
		if ( null !== $recent_comments ) {
			self::add( Tag::TYPE_WIDGET . $recent_comments->id );
			self::add_private( Tag::TYPE_WIDGET . $recent_comments->id );

			do_action( 'litespeed_purged_comment_widget', $recent_comments->id );
		}
	}

	/**
	 * Purges feeds on comment count update.
	 *
	 * @since 1.0.9
	 * @return void
	 */
	public function purge_feeds() {
		if ( $this->conf( self::O_CACHE_TTL_FEED ) > 0 ) {
			self::add( Tag::TYPE_FEED );
		}
		do_action( 'litespeed_purged_feeds' );
	}

	/**
	 * Purges all private cache entries when the user logs out.
	 *
	 * @since 1.1.3
	 * @return void
	 */
	public static function purge_on_logout() {
		self::add_private_all();
		do_action( 'litespeed_purged_on_logout' );
	}

	/**
	 * Finalize purge tags before output.
	 *
	 * @since 1.1.3
	 * @return void
	 */
	private function _finalize() {
		if ( ! defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			define( 'LITESPEED_DID_' . __FUNCTION__, true );
		} else {
			return;
		}

		do_action( 'litespeed_purge_finalize' );

		// Append unique uri purge tags if Admin QS is `PURGESINGLE` or `PURGE`.
		if ( $this->_purge_single ) {
			$tags             = [ Tag::build_uri_tag() ];
			$this->_pub_purge = array_merge( $this->_pub_purge, $this->_prepend_bid( $tags ) );
		}

		if ( ! empty( $this->_pub_purge ) ) {
			$this->_pub_purge = array_unique( $this->_pub_purge );
		}

		if ( ! empty( $this->_priv_purge ) ) {
			$this->_priv_purge = array_unique( $this->_priv_purge );
		}
	}

	/**
	 * Gather and return purge header string.
	 *
	 * @since 1.1.0
	 * @return string Purge header line.
	 */
	public static function output() {
		$instance = self::cls();

		$instance->_finalize();

		return $instance->_build();
	}

	/**
	 * Build the current purge header(s).
	 *
	 * @since 1.1.5
	 * @param bool $purge2 Whether to build X-LiteSpeed-Purge2.
	 * @return string Purge header line.
	 */
	private function _build( $purge2 = false ) {
		if ( $purge2 ) {
			if ( empty( $this->_pub_purge2 ) ) {
				return '';
			}
		} elseif ( empty( $this->_pub_purge ) && empty( $this->_priv_purge ) ) {
			return '';
		}

		$purge_header   = '';
		$private_prefix = self::X_HEADER . ': private,';

		// Handle purge2.
		if ( $purge2 ) {
			$public_tags = $this->_append_prefix( $this->_pub_purge2 );
			if ( empty( $public_tags ) ) {
				return '';
			}
			$purge_header = self::X_HEADER2 . ': public,';
			if ( Control::is_stale() ) {
				$purge_header .= 'stale,';
			}
			$purge_header .= implode( ',', $public_tags );
			return $purge_header;
		}

		if ( ! empty( $this->_pub_purge ) ) {
			$public_tags = $this->_append_prefix( $this->_pub_purge );
			if ( empty( $public_tags ) ) {
				return ''; // If this ends up empty, private will also end up empty
			}
			$purge_header = self::X_HEADER . ': public,';
			if ( Control::is_stale() ) {
				$purge_header .= 'stale,';
			}
			$purge_header  .= implode( ',', $public_tags );
			$private_prefix = ';private,';
		}

		// Private purge tags.
		if ( ! empty( $this->_priv_purge ) ) {
			$private_tags  = $this->_append_prefix( $this->_priv_purge, true );
			$purge_header .= $private_prefix . implode( ',', $private_tags );
		}

		return $purge_header;
	}

	/**
	 * Append LS tag prefix to tags; handle '*' across network.
	 *
	 * @since 1.1.0
	 * @param array<int,string> $purge_tags Tags.
	 * @param bool              $is_private Private tags.
	 * @return array<int,string>
	 */
	private function _append_prefix( $purge_tags, $is_private = false ) {
		$curr_bid = is_multisite() ? get_current_blog_id() : '';

		$purge_tags = apply_filters( 'litespeed_purge_tags', $purge_tags, $is_private );
		if ( ! in_array( '*', $purge_tags, true ) ) {
			$tags = [];
			foreach ( $purge_tags as $val ) {
				$tags[] = LSWCP_TAG_PREFIX . $val;
			}
			return $tags;
		}

		// Purge All: maybe reset crawler.
		if ( ! $is_private && $this->conf( self::O_CRAWLER ) ) {
			Crawler::cls()->reset_pos();
		}

		if ( ( defined( 'LSWCP_EMPTYCACHE' ) && LSWCP_EMPTYCACHE ) || $is_private ) {
			return [ '*' ];
		}

		if ( is_multisite() && ! $this->_is_subsite_purge() ) {
			$blogs = Activation::get_network_ids();
			if ( empty( $blogs ) ) {
				self::debug( 'build_purge_headers: blog list is empty' );
				return [];
			}
			$tags = [];
			foreach ( $blogs as $blog_id ) {
				$tags[] = LSWCP_TAG_PREFIX . $blog_id . '_';
			}
			return $tags;
		}

		return [ LSWCP_TAG_PREFIX . $curr_bid . '_' ];
	}

	/**
	 * Check if this is a subsite purge in multisite.
	 *
	 * @since 4.0
	 * @return bool
	 */
	private function _is_subsite_purge() {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( is_network_admin() ) {
			return false;
		}

		if ( defined( 'LSWCP_EMPTYCACHE' ) && LSWCP_EMPTYCACHE ) {
			return false;
		}

		// Ajax network contexts.
		if ( Router::is_ajax() && ( check_ajax_referer( 'updates', false, false ) || check_ajax_referer( 'litespeed-purgeall-network', false, false ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get purge tags related to a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array<int,string>
	 */
	private function _get_purge_tags_by_post( $post_id ) {
		if ( $this->conf( self::O_PURGE_POST_ALL ) ) {
			return [ '*' ];
		}

		do_action( 'litespeed_api_purge_post', $post_id );

		$purge_tags = [];

		// Post itself.
		$purge_tags[] = Tag::TYPE_POST . $post_id;

		$post_status = get_post_status( $post_id );
		if ( function_exists( 'is_post_status_viewable' ) && is_post_status_viewable( $post_status ) ) {
			$purge_tags[] = Tag::get_uri_tag( wp_make_link_relative( get_permalink( $post_id ) ) );
		}

		// Avoid overriding global $post: use explicit post object.
		$the_post  = get_post( $post_id );
		$post_type = $the_post ? $the_post->post_type : '';

		// Widgets: recent posts.
		global $wp_widget_factory;
		$recent_posts = isset( $wp_widget_factory->widgets['WP_Widget_Recent_Posts'] ) ? $wp_widget_factory->widgets['WP_Widget_Recent_Posts'] : null;
		if ( null !== $recent_posts ) {
			$purge_tags[] = Tag::TYPE_WIDGET . $recent_posts->id;
		}

		// get adjacent posts id as related post tag
		if ( 'post' === $post_type ) {
			$prev_post = get_previous_post();
			$next_post = get_next_post();
			if ( ! empty( $prev_post->ID ) ) {
				$purge_tags[] = Tag::TYPE_POST . $prev_post->ID;
				self::debug( '--------purge_tags prev is: ' . $prev_post->ID );
			}
			if ( ! empty( $next_post->ID ) ) {
				$purge_tags[] = Tag::TYPE_POST . $next_post->ID;
				self::debug( '--------purge_tags next is: ' . $next_post->ID );
			}
		}

		if ( $this->conf( self::O_PURGE_POST_TERM ) ) {
			$taxonomies = get_object_taxonomies( $post_type );
			// self::debug('purge by post, check tax = ' . var_export($taxonomies, true));
			foreach ( $taxonomies as $tax ) {
				$terms = get_the_terms( $post_id, $tax );
				if ( ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$purge_tags[] = Tag::TYPE_ARCHIVE_TERM . $term->term_id;
					}
				}
			}
		}

		if ( $this->conf( self::O_CACHE_TTL_FEED ) ) {
			$purge_tags[] = Tag::TYPE_FEED;
		}

		// Author archives.
		if ( $this->conf( self::O_PURGE_POST_AUTHOR ) ) {
			$purge_tags[] = Tag::TYPE_AUTHOR . get_post_field( 'post_author', $post_id );
		}

		// Post type archives.
		if ( $this->conf( self::O_PURGE_POST_POSTTYPE ) && get_post_type_archive_link( $post_type ) ) {
			$purge_tags[] = Tag::TYPE_ARCHIVE_POSTTYPE . $post_type;
			$purge_tags[] = $post_type;
		}

		if ( $this->conf( self::O_PURGE_POST_FRONTPAGE ) ) {
			$purge_tags[] = Tag::TYPE_FRONTPAGE;
		}

		if ( $this->conf( self::O_PURGE_POST_HOMEPAGE ) ) {
			$purge_tags[] = Tag::TYPE_HOME;
		}

		if ( $this->conf( self::O_PURGE_POST_PAGES ) ) {
			$purge_tags[] = Tag::TYPE_PAGES;
		}

		if ( $this->conf( self::O_PURGE_POST_PAGES_WITH_RECENT_POSTS ) ) {
			$purge_tags[] = Tag::TYPE_PAGES_WITH_RECENT_POSTS;
		}

		// Date archives (use gmdate as per WPCS).
		$date_gmt = $the_post ? strtotime( $the_post->post_date_gmt ) : false;
		if ( $date_gmt ) {
			if ( $this->conf( self::O_PURGE_POST_DATE ) ) {
				$purge_tags[] = Tag::TYPE_ARCHIVE_DATE . gmdate( 'Ymd', $date_gmt );
			}
			if ( $this->conf( self::O_PURGE_POST_MONTH ) ) {
				$purge_tags[] = Tag::TYPE_ARCHIVE_DATE . gmdate( 'Ym', $date_gmt );
			}
			if ( $this->conf( self::O_PURGE_POST_YEAR ) ) {
				$purge_tags[] = Tag::TYPE_ARCHIVE_DATE . gmdate( 'Y', $date_gmt );
			}
		}

		return array_unique( array_filter( $purge_tags ) );
	}

	/**
	 * Run a filter and also purge all (utility for hooks).
	 *
	 * @since 1.1.5
	 * @param string $val Filter value.
	 * @return string Same value.
	 */
	public static function filter_with_purge_all( $val ) {
		self::purge_all();
		return $val;
	}
}
